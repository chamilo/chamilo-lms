<?php

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Base\Validator\ManifestValidator;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use DOMDocument;
use DOMElement;
use DOMXPath;
use FilesystemIterator;
use PclZip;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;
use ZipArchive;

use const DIRECTORY_SEPARATOR;
use const PCLZIP_OPT_PATH;

class Imscc13Import
{
    public const FORMAT_IMSCC13 = 'imscc13';

    public function log(string $message, string|int $level = 'info', $a = null, $depth = null, bool $display = false): void
    {
        // Minimal, central logger for importer
        error_log("(imscc13) $message , level: $level , extra: ".json_encode($a));
    }

    /**
     * Quick check to verify an extracted folder looks like CC 1.3.
     * Less strict: accepts manifests whose default NS is plain imscp_v1p1
     * as long as we detect CC 1.3 traits (schemaversion 1.3.x or v1p3 tokens).
     */
    public static function detectFormat(string $extractedDir): ?string
    {
        $manifest = Cc1p3Convert::getManifest($extractedDir);
        if (!$manifest || !is_file($manifest)) {
            return null;
        }

        // Read a small chunk (up to 64 KiB) to detect tokens fast.
        $buf = (string) @file_get_contents($manifest, false, null, 0, 65536);
        if ('' === $buf) {
            return null;
        }
        $lc = strtolower($buf);

        // Heuristics that signal CC 1.3 packages:
        //  - schemaversion 1.3.x
        //  - resource/@type or xmlns entries containing "v1p3"
        //  - lomimscc CC 1.3 LOM namespace
        $has13 = (str_contains($lc, '<schemaversion>1.3') || str_contains($lc, 'schemaversion">1.3'));
        $hasV13Tokens = (str_contains($lc, 'v1p3') || str_contains($lc, 'imsccv1p3'));
        $hasLomImscc = str_contains($lc, 'http://ltsc.ieee.org/xsd/imsccv1p3/lom/manifest');

        if ($has13 || $hasV13Tokens || $hasLomImscc) {
            return self::FORMAT_IMSCC13;
        }

        return null;
    }

    /**
     * Validates the manifest and triggers the converter pipeline that creates
     * Chamilo resources (documents, links, forums, quizzes).
     *
     * After the standard converter runs, a "best-effort" importer kicks in for
     * types not (yet) handled by the converter:
     *   - imswl_xmlv1p1 (Web Links)  -> Links tool
     *   - imsdt_xmlv1p1 (Discussions)-> Forum + Thread + Post
     */
    public function execute(string $extractedDir): void
    {
        $manifest = Cc1p3Convert::getManifest($extractedDir);
        if (!$manifest || !is_file($manifest)) {
            throw new RuntimeException('No imsmanifest.xml detected.');
        }

        // Resolve schema dir inside the component
        $schemaDir = __DIR__.'/Base/Validator/schemas13';
        if (!is_file($schemaDir.'/cc13libxml2validator.xsd')) {
            $alt = __DIR__.'/schemas13';
            if (is_file($alt.'/cc13libxml2validator.xsd')) {
                $schemaDir = $alt;
            } else {
                throw new RuntimeException('Manifest validation error(s): XSD file not found at '.$schemaDir.' nor '.$alt);
            }
        }

        $this->log('imscc13: using schemaDir='.$schemaDir.' skip=0');

        // 1st pass: raw validation
        $validator = new ManifestValidator($schemaDir);
        if (!$validator->validate($manifest)) {
            $this->log('imscc13: first validation failed; retry with normalized schema labels', 'warn');

            // Build a patched copy for validation-only, using DOM (no substr/strpos).
            $manifestForValidation = self::makeManifestValidationCopy($manifest);

            $this->log('imscc13: validating using patched manifest copy', 'info', [
                'original' => $manifest,
                'patched' => $manifestForValidation,
            ]);

            $validator2 = new ManifestValidator($schemaDir);
            if (!$validator2->validate($manifestForValidation)) {
                // Do not block the import anymore; continue best-effort as agreed
                $this->log('imscc13: validation still failing; proceeding in best-effort mode (schema check skipped).', 'warn');
            }
        }

        self::assertResourceFsWritable();

        // Standard converter pipeline (keep existing behavior)
        try {
            $cc = new Cc1p3Convert($manifest);
            if ($cc->isAuth()) {
                // CC with basiclti/authorization not supported in this importer
                throw new RuntimeException('Protected Common Cartridge is not supported.');
            }
            $cc->generateImportData();
            $this->log('imscc13: converter pipeline executed', 'info');
        } catch (Throwable $e) {
            // We don't fail; we will try our best-effort importer below.
            $this->log('imscc13: converter pipeline failed; falling back to built-in importer', 'warn', [
                'error' => $e->getMessage(),
            ]);
        }

        // --- Best-effort importer for WebLinks + Discussions (non-destructive) ---
        try {
            $added = $this->bestEffortImportLinksAndDiscussions($manifest, $extractedDir);
            $this->log('imscc13: best-effort import finished', 'info', $added);
            // If nothing was added, that's fine (converter may have handled it).
        } catch (Throwable $e) {
            $this->log('imscc13: best-effort importer failed', 'error', [
                'error' => $e->getMessage(),
            ]);
            // Do not rethrow; execute() must remain resilient.
        }
    }

    /**
     * Create a validation-only patched copy of the manifest, normalizing
     * schema label and removing constructs that the CC 1.3 XSD rejects.
     * All changes are applied via DOM to avoid substring pitfalls.
     */
    private static function makeManifestValidationCopy(string $manifestPath): string
    {
        $xml = @file_get_contents($manifestPath);
        if (false === $xml) {
            throw new RuntimeException('Could not read manifest for validation patching.');
        }

        // Normalize <schema> label (1EdTech -> IMS) for v1.3 validator
        $xml = self::normalizeSchemaLabels($xml);

        // Parse with DOM to make safe structural tweaks for validation only
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        // Load as XML (NOT HTML); suppress warnings but we control edits
        if (!@$dom->loadXML($xml)) {
            // If DOM fails, just write normalized string to a temp file
            return self::writeTempValidatedCopy($xml);
        }

        $xp = new DOMXPath($dom);
        $xp->registerNamespace('ims', 'http://www.imsglobal.org/xsd/imsccv1p3/imscp_v1p1');

        // CC 1.3 XSD: top-level <organizations>/<organization>/<item> does not allow identifierref
        foreach ($xp->query('/ims:manifest/ims:organizations/ims:organization/ims:item[@identifierref]') as $item) {
            $item->removeAttribute('identifierref');
        }

        // CC 1.3 XSD: within that same level, <title> is not allowed directly; expected item|metadata
        foreach ($xp->query('/ims:manifest/ims:organizations/ims:organization/ims:item/ims:title') as $titleNode) {
            $titleNode->parentNode?->removeChild($titleNode);
        }

        $patched = $dom->saveXML();
        if (false === $patched) {
            $patched = $xml;
        }

        return self::writeTempValidatedCopy($patched);
    }

    /**
     * Normalize the <schema> value once (validation copy only).
     * Converts "1EdTech Common Cartridge" or variants to "IMS Common Cartridge".
     */
    private static function normalizeSchemaLabels(string $xml): string
    {
        // Use a plain replacement with backreferences (no closures here).
        $re = '/(<metadata\b[^>]*>\s*<schema>)(.*?)(<\/schema>)/is';

        // Replace only the first occurrence inside <metadata>...</metadata>.
        $patched = preg_replace($re, '$1IMS Common Cartridge$3', $xml, 1);

        // preg_replace can return null on PCRE error; fall back to original.
        return null !== $patched ? $patched : $xml;
    }

    /**
     * Write a patched manifest into a temp folder and return its path.
     */
    private static function writeTempValidatedCopy(string $content): string
    {
        $tmp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.'cc13_val_'.bin2hex(random_bytes(3));
        if (!@mkdir($tmp, 0777, true) && !is_dir($tmp)) {
            throw new RuntimeException('Cannot create temp directory for validation copy: '.$tmp);
        }
        $dest = $tmp.DIRECTORY_SEPARATOR.'imsmanifest.xml';
        if (false === @file_put_contents($dest, $content)) {
            throw new RuntimeException('Cannot write validation copy: '.$dest);
        }

        return $dest;
    }

    /**
     * Unzip a file into the specified directory. Throws a RuntimeException if extraction fails.
     * Returns the extraction directory.
     */
    public static function unzip(string $file, ?string $to = null): string
    {
        @ini_set('memory_limit', '512M');

        $to = $to ?: (rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'cc13_'.date('Ymd_His').'_'.bin2hex(random_bytes(3)));
        if (!is_dir($to) && !@mkdir($to, 0777, true) && !is_dir($to)) {
            throw new RuntimeException("Cannot create temp directory: $to");
        }

        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();
            $res = $zip->open($file);
            if (true === $res) {
                if (!$zip->extractTo($to)) {
                    $zip->close();

                    throw new RuntimeException('Could not extract zip file using ZipArchive.');
                }
                $zip->close();
            } else {
                throw new RuntimeException('Could not open zip file using ZipArchive.');
            }
        } else {
            if (!class_exists('PclZip')) {
                throw new RuntimeException('Zip support not available (ZipArchive nor PclZip).');
            }
            $zip = new PclZip($file);
            if (0 === $zip->extract(PCLZIP_OPT_PATH, $to)) {
                throw new RuntimeException('Could not extract zip file using PclZip.');
            }
        }

        return $to;
    }

    /**
     * Best-effort recursive delete (used to cleanup temp dirs).
     */
    public static function rrmdir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $fs) {
            $fs->isDir() ? @rmdir($fs->getPathname()) : @unlink($fs->getPathname());
        }
        @rmdir($path);
    }

    private static function assertResourceFsWritable(): void
    {
        // ResourceFile base path used by Chamilo 2
        $base = rtrim((string) Container::getParameter('kernel.project_dir'), DIRECTORY_SEPARATOR).'/var/upload/resource';

        $fs = new Filesystem();
        // Ensure base directory exists
        if (!is_dir($base)) {
            try {
                $fs->mkdir($base, 0775);
            } catch (Throwable $e) {
                throw new RuntimeException("Resource FS not available: failed to create {$base}: ".$e->getMessage());
            }
        }

        // Check writability
        if (!is_writable($base)) {
            $who = \function_exists('posix_geteuid') ? ('uid='.posix_geteuid()) : (get_current_user() ?: 'unknown-user');

            throw new RuntimeException("Resource FS not writable: {$base} (php user: {$who}). ".'Fix permissions: chown -R www-data:www-data var && chmod -R 775 var');
        }
    }

    /**
     * Parse the manifest and import imswl_xmlv1p1 + imsdt_xmlv1p1 resources.
     * Non-destructive: if converter already did it, this can end up importing zero.
     *
     * @return array<string,int> counts per bucket created
     */
    private function bestEffortImportLinksAndDiscussions(string $manifestPath, string $extractDir): array
    {
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        if (!@$doc->load($manifestPath)) {
            throw new RuntimeException('Invalid imsmanifest.xml (XML load failed)');
        }

        $xp = new DOMXPath($doc);
        $xp->registerNamespace('imscp', 'http://www.imsglobal.org/xsd/imsccv1p3/imscp_v1p1');

        $resNodes = $xp->query('/imscp:manifest/imscp:resources/imscp:resource');
        if (!$resNodes || 0 === $resNodes->length) {
            return ['links' => 0, 'forums' => 0, 'threads' => 0, 'posts' => 0];
        }

        $links = [];
        $linkCategories = [];
        $forumCats = [];
        $forums = [];
        $threads = [];
        $posts = [];

        $linkCatId = 1;
        $linkCategories[$linkCatId] = (object) [
            'id' => $linkCatId,
            'title' => 'Imported CC Links',
            'description' => '',
        ];

        $forumCatId = 1001;
        $forumId = 1002;
        $forumCats[$forumCatId] = (object) [
            'id' => $forumCatId,
            'cat_title' => 'Imported CC Discussions',
            'cat_comment' => '',
        ];
        $forums[$forumId] = (object) [
            'id' => $forumId,
            'forum_title' => 'Imported discussions',
            'forum_comment' => '',
            'forum_category' => $forumCatId,
        ];

        $nextId = 1;
        $added = ['links' => 0, 'forums' => 0, 'threads' => 0, 'posts' => 0];

        /** @var DOMElement $res */
        foreach ($resNodes as $res) {
            $typeRaw = (string) $res->getAttribute('type');
            $hrefRaw = (string) $res->getAttribute('href');

            // Some exporters put href only in <file href="...">
            if ('' === trim($hrefRaw)) {
                // read first <file> regardless of prefix
                $fileHref = '';
                // try with ns
                $files = $res->getElementsByTagNameNS('http://www.imsglobal.org/xsd/imsccv1p3/imscp_v1p1', 'file');
                if ($files->length > 0) {
                    $fileHref = (string) $files->item(0)->getAttribute('href');
                }
                // fallback without ns
                if ('' === $fileHref) {
                    $files = $res->getElementsByTagName('file');
                    if ($files->length > 0) {
                        $fileHref = (string) $files->item(0)->getAttribute('href');
                    }
                }
                $hrefRaw = $this->firstNonEmpty($hrefRaw, $fileHref);
            }

            $kind = $this->classifyCcResourceType($typeRaw, $hrefRaw);
            if ('other' === $kind || '' === $hrefRaw) {
                continue;
            }

            $abs = rtrim($extractDir, '/').'/'.$hrefRaw;

            if ('weblink' === $kind) {
                $wl = $this->parseWebLink($abs);
                if (!$wl) {
                    continue;
                }
                $id = $nextId++;
                $links[$id] = (object) [
                    'id' => $id,
                    'title' => (string) $wl['title'],
                    'url' => (string) $wl['url'],
                    'description' => (string) ($wl['description'] ?? ''),
                    'category_id' => $linkCatId,
                    'target' => '_blank',
                ];
                $added['links']++;

                continue;
            }

            if ('discussion' === $kind) {
                $dt = $this->parseDiscussionTopic($abs);
                if (!$dt) {
                    continue;
                }
                $tid = $nextId++;
                $threads[$tid] = (object) [
                    'id' => $tid,
                    'forum_id' => $forumId,
                    'thread_title' => '' !== $dt['title'] ? (string) $dt['title'] : 'Discussion',
                    'thread_date' => date('Y-m-d H:i:s'),
                    'poster_name' => 'importer',
                ];
                $pid = $nextId++;
                $posts[$pid] = (object) [
                    'id' => $pid,
                    'thread_id' => $tid,
                    'post_text' => (string) $dt['body'],
                    'post_date' => date('Y-m-d H:i:s'),
                ];
                $added['threads']++;
                $added['posts']++;
            }
        }

        // Nothing to import? done
        if (0 === $added['links'] && 0 === $added['threads'] && 0 === $added['posts']) {
            return $added;
        }

        // Build minimal legacy Course object
        $legacy = (object) ['resources' => []];
        if (!empty($links)) {
            $legacy->resources['link'] = $this->wrapAsLegacy($links);
            $legacy->resources['link_category'] = $this->wrapAsLegacy($linkCategories);
        }
        if ($added['threads'] > 0 || $added['posts'] > 0) {
            $legacy->resources['Forum_Category'] = $this->wrapAsLegacy($forumCats);
            $legacy->resources['forum'] = $this->wrapAsLegacy($forums);
            $legacy->resources['thread'] = $this->wrapAsLegacy($threads);
            $legacy->resources['post'] = $this->wrapAsLegacy($posts);
            $added['forums'] = \count($forums);
        }

        // Restore into the current course using the standard restorer
        $restorer = new CourseRestorer($legacy);
        if (method_exists($restorer, 'setDebug')) {
            $restorer->setDebug(true); // keep verbose while stabilizing the importer
        }
        $restorer->restore();

        return $added;
    }

    /**
     * Convert a plain [id => entity] array into the legacy wrapper form
     * [id => (object)['obj' => entity]] used by CourseRestorer/CourseBuilder.
     *
     * @param array<int|string,object> $bucket
     *
     * @return array<int|string,object>
     */
    private function wrapAsLegacy(array $bucket): array
    {
        $out = [];
        foreach ($bucket as $id => $entity) {
            $out[$id] = (object) ['obj' => $entity];
        }

        return $out;
    }

    /**
     * Parse IMS Web Link (v1p1).
     * Returns ['title' => string, 'url' => string, 'description' => string].
     */
    private function parseWebLink(string $file): ?array
    {
        if (!is_file($file)) {
            $this->log('weblink xml not found', 'warn', ['file' => $file]);

            return null;
        }
        $d = new DOMDocument();
        if (!@$d->load($file)) {
            $this->log('weblink xml invalid', 'warn', ['file' => $file]);

            return null;
        }
        $xp = new DOMXPath($d);
        // Support both v1p1 and v1p3, or even no namespace
        $xp->registerNamespace('wl11', 'http://www.imsglobal.org/xsd/imswl_v1p1');
        $xp->registerNamespace('wl13', 'http://www.imsglobal.org/xsd/imswl_v1p3');

        // Query by local-name() to ignore the namespace version
        $title = trim((string) $xp->evaluate('string(/*[local-name()="webLink"]/*[local-name()="title"])'));
        $url = trim((string) $xp->evaluate('string(/*[local-name()="webLink"]/*[local-name()="url"]/@href)'));
        if ('' === $url) {
            // Some exports put the URL as text node inside <url>
            $url = trim((string) $xp->evaluate('string(/*[local-name()="webLink"]/*[local-name()="url"])'));
        }
        $desc = trim((string) $xp->evaluate('string(/*[local-name()="webLink"]/*[local-name()="description"])'));

        if ('' === $title) {
            // Try LOM-like nested <string>
            $title = trim((string) $xp->evaluate('string(/*[local-name()="webLink"]/*[local-name()="title"]/*[local-name()="string"])'));
        }
        if ('' === $title) {
            $title = $url;
        }
        if ('' === $url) {
            $this->log('weblink missing href', 'warn', ['file' => $file]);

            return null;
        }

        return ['title' => $title, 'url' => $url, 'description' => $desc];
    }

    /**
     * Parse IMS Discussion Topic (v1p1).
     * Returns ['title' => string, 'body' => html].
     * Keeps inner HTML of <dt:text> exactly as-is (CDATA-safe).
     */
    private function parseDiscussionTopic(string $file): ?array
    {
        if (!is_file($file)) {
            $this->log('discussion xml not found', 'warn', ['file' => $file]);

            return null;
        }
        $d = new DOMDocument();
        if (!@$d->load($file)) {
            $this->log('discussion xml invalid', 'warn', ['file' => $file]);

            return null;
        }
        $xp = new DOMXPath($d);
        $xp->registerNamespace('dt11', 'http://www.imsglobal.org/xsd/imsdt_v1p1');
        $xp->registerNamespace('dt13', 'http://www.imsglobal.org/xsd/imsdt_v1p3');

        $title = trim((string) $xp->evaluate('string(/*[local-name()="topic"]/*[local-name()="title"])'));

        // Body can be <text> or <message> depending on exporter
        $node = $xp->query('/*[local-name()="topic"]/*[local-name()="text"]')->item(0)
            ?: $xp->query('/*[local-name()="topic"]/*[local-name()="message"]')->item(0);

        $body = '';
        if ($node) {
            foreach ($node->childNodes as $child) {
                $chunk = $d->saveXML($child);
                if (false !== $chunk) {
                    $body .= $chunk;
                }
            }
        }

        return ['title' => $title, 'body' => $body];
    }

    /**
     * helper to classify resource types more loosely.
     */
    private function classifyCcResourceType(string $type, string $href): string
    {
        $t = strtolower(trim($type));
        if ('' === $t) {
            return 'other';
        }

        // WebLink: imswl_* (v1p1, v1p3, vendor prefixes)
        if (str_contains($t, 'imswl')) {
            return 'weblink';
        }

        // Discussion: imsdt_* (v1p1, v1p3)
        if (str_contains($t, 'imsdt')) {
            return 'discussion';
        }

        // Many exports mark files as webcontent; keep for future doc import
        if (str_contains($t, 'webcontent')) {
            return 'webcontent';
        }

        // Fallback by extension (rare but harmless)
        if ('' !== $href && preg_match('~\.(xml)$~i', $href)) {
            // Heuristic: WL-*.xml under /weblinks/
            if (preg_match('~weblinks/[^/]+\.xml$~i', $href)) {
                return 'weblink';
            }
            if (preg_match('~discussions/[^/]+\.xml$~i', $href)) {
                return 'discussion';
            }
        }

        return 'other';
    }

    private function firstNonEmpty(string ...$vals): string
    {
        foreach ($vals as $v) {
            if ('' !== trim($v)) {
                return $v;
            }
        }

        return '';
    }
}
