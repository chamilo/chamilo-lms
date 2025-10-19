<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Builder;

use Chamilo\CoreBundle\Entity\Course as CourseEntity;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Database;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMElement;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;
use ZipArchive;

use const DIRECTORY_SEPARATOR;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PATHINFO_EXTENSION;
use const PHP_EOL;

/**
 * Common Cartridge 1.3 exporter for Chamilo 2.
 *
 * - Inputs: legacy Course bag from CourseBuilder.
 * - Exports:
 *   * Documents → webcontent files under "resources/...".
 *   * Web Links → IMS WebLink (imswl_xmlv1p1) under "weblinks/...".
 *   * Discussions → IMS Discussion Topic (imsdt_xmlv1p1) under "discussions/...".
 * - Manifest: <resources> + a hierarchical <organization> tree.
 */
class Cc13Export
{
    /**
     * Legacy course container (DTO with resources[...]).
     */
    private object $course;

    private bool $selectionMode;
    private bool $debug;

    /**
     * Working directory on disk.
     */
    private string $workdir = '';

    /**
     * Absolute path to the resulting .imscc file.
     */
    private string $packagePath = '';

    /**
     * Doctrine & repositories.
     */
    private EntityManagerInterface $em;
    private CDocumentRepository $docRepo;
    private ResourceNodeRepository $rnRepo;

    /**
     * Project base dir (for var/upload/resource).
     */
    private string $projectDir = '';

    /**
     * Cached CourseEntity for the current course code.
     */
    private ?CourseEntity $courseEntity = null;

    /**
     * Course code kept for legacy FS fallback.
     */
    private string $courseCodeForLegacy = '';

    /**
     * Path to debug log file (in system temp dir).
     */
    private string $logFile = '';

    /**
     * CC 1.3 namespaces.
     */
    private array $ns = [
        'imscc' => 'http://www.imsglobal.org/xsd/imsccv1p3/imscp_v1p1',
        'lomimscc' => 'http://ltsc.ieee.org/xsd/imsccv1p3/LOM/manifest',
        'lom' => 'http://ltsc.ieee.org/xsd/imsccv1p3/LOM/resource',
        'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
    ];

    /**
     * schemaLocation map (optional).
     */
    private array $schemaLocations = [
        'imscc' => 'http://www.imsglobal.org/profile/cc/ccv1p3/ccv1p3_imscp_v1p2_v1p0.xsd',
        'lomimscc' => 'http://www.imsglobal.org/profile/cc/ccv1p3/LOM/ccv1p3_lommanifest_v1p0.xsd',
        'lom' => 'http://www.imsglobal.org/profile/cc/ccv1p3/LOM/ccv1p3_lomresource_v1p0.xsd',
    ];

    public function __construct(object $course, bool $selectionMode = false, bool $debug = false)
    {
        $this->course = $course;
        $this->selectionMode = $selectionMode;
        $this->debug = $debug;

        // Resolve services safely (throw if missing)
        $this->em = Database::getManager();

        $docRepo = Container::getDocumentRepository();
        if (!$docRepo instanceof CDocumentRepository) {
            throw new RuntimeException('CDocumentRepository service not available');
        }
        $this->docRepo = $docRepo;

        $rnRepoRaw = Container::$container->get(ResourceNodeRepository::class);
        if (!$rnRepoRaw instanceof ResourceNodeRepository) {
            throw new RuntimeException('ResourceNodeRepository service not available');
        }
        $this->rnRepo = $rnRepoRaw;

        $kernel = Container::$container->get('kernel');
        if (!$kernel instanceof KernelInterface) {
            throw new RuntimeException('Kernel service not available');
        }
        $this->projectDir = rtrim($kernel->getProjectDir(), '/');

        // Prepare log file in system temp dir
        $this->logFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'cc13_export.log';
        if ($this->debug) {
            $this->log('Exporter constructed', [
                'class_file' => (new ReflectionClass(self::class))->getFileName(),
                'projectDir' => $this->projectDir,
                'tempDir' => sys_get_temp_dir(),
            ]);
        }
    }

    /**
     * Build the .imscc package and return its absolute path.
     *
     * @param string      $courseCode current course code (api_get_course_id())
     * @param string|null $exportDir  optional subdir name; defaults to "cc13_YYYYmmdd_His"
     */
    public function export(string $courseCode, ?string $exportDir = null): string
    {
        $this->log('Export start', ['courseCode' => $courseCode]);

        $this->courseEntity = $this->em->getRepository(CourseEntity::class)->findOneBy(['code' => $courseCode]);
        if (!$this->courseEntity instanceof CourseEntity) {
            $this->log('Course not found', ['courseCode' => $courseCode], 'error');

            throw new RuntimeException('Course not found for code: '.$courseCode);
        }

        $this->courseCodeForLegacy = (string) $this->courseEntity->getCode();

        $exportDir = $exportDir ?: ('cc13_'.date('Ymd_His'));
        $baseTmp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        $this->workdir = $baseTmp.DIRECTORY_SEPARATOR.$exportDir;

        $this->log('Workdir prepare', ['workdir' => $this->workdir]);

        // Prepare working dir
        $this->rrmdir($this->workdir);
        $this->mkdirp($this->workdir.'/resources');
        $this->mkdirp($this->workdir.'/_generated'); // for inline-content exports

        // Environment checks
        $this->checkEnvironment();

        // Collect candidate resources
        $docList = $this->collectDocuments();
        $this->mkdirp($this->workdir.'/weblinks');
        $wlList = $this->collectWebLinks();
        $this->mkdirp($this->workdir.'/discussions');
        $dtList = $this->collectDiscussionTopics();

        $this->log('Collected resources', [
            'documents' => \count($docList),
            'weblinks' => \count($wlList),
            'discussions' => \count($dtList),
        ]);

        // Materialize into package and build resource entries
        $resourceEntries = [];
        $resourceEntries = array_merge(
            $resourceEntries,
            $this->copyDocumentsIntoPackage($docList)
        );
        $resourceEntries = array_merge(
            $resourceEntries,
            $this->writeWebLinksIntoPackage($wlList)
        );
        $resourceEntries = array_merge(
            $resourceEntries,
            $this->writeDiscussionTopicsIntoPackage($dtList)
        );

        // CHANGED: allow export as long as there is at least ONE resource of any supported type.
        if (empty($resourceEntries)) {
            $this->log('Nothing to export (no CC-compatible resources)', [], 'warn');

            throw new RuntimeException('Nothing to export (no CC 1.3-compatible resources: documents, links or discussions).');
        }

        // Write imsmanifest.xml
        $this->writeManifest($resourceEntries);
        $this->log('imsmanifest.xml written', ['resourceCount' => \count($resourceEntries)]);

        // Zip → .imscc
        $filename = \sprintf('%s_cc13_%s.imscc', $this->courseCodeForLegacy, date('Ymd_His'));
        $this->packagePath = $this->normalizePath($this->workdir.'/../'.$filename);

        $this->zipDir($this->workdir, $this->packagePath);
        $this->log('Package zipped', ['packagePath' => $this->packagePath]);

        // Cleanup temp working dir
        $this->rrmdir($this->workdir);
        $this->log('Workdir cleaned up', ['workdir' => $this->workdir]);

        $this->log('Export done', ['packagePath' => $this->packagePath]);

        return $this->packagePath;
    }

    /**
     * Write imsmanifest.xml:
     * - <manifest> root with minimal LOM metadata
     * - <organizations> containing a hierarchical <organization>
     * - <resources> with entries for webcontent, weblink, and discussion topic
     *
     * @param array<int,array{identifier:string, href:string, type:string, title?:string, path?:string}> $resources
     */
    private function writeManifest(array $resources): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $imscc = $this->ns['imscc'];

        // Root <manifest>
        $manifest = $doc->createElementNS($imscc, 'manifest');
        $manifest->setAttribute('identifier', 'MANIFEST-'.bin2hex(random_bytes(6)));
        // Helpful schema attributes (harmless if ignored)
        $manifest->setAttribute('schema', 'IMS Common Cartridge');
        $manifest->setAttribute('schemaversion', '1.3.0');

        // Namespace declarations
        foreach ($this->ns as $prefix => $uri) {
            $manifest->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:'.$prefix, $uri);
        }

        // xsi:schemaLocation (optional)
        $schemaLocation = '';
        foreach ($this->schemaLocations as $k => $loc) {
            if (!isset($this->ns[$k])) {
                continue;
            }
            $schemaLocation .= ($schemaLocation ? ' ' : '').$this->ns[$k].' '.$loc;
        }
        if ($schemaLocation) {
            $manifest->setAttributeNS($this->ns['xsi'], 'xsi:schemaLocation', $schemaLocation);
        }

        // Optional LOM metadata (very small payload)
        $metadata = $doc->createElementNS($imscc, 'metadata');
        $lom = $doc->createElementNS($this->ns['lomimscc'], 'lom');
        $general = $doc->createElementNS($this->ns['lomimscc'], 'general');
        $title = $doc->createElementNS($this->ns['lomimscc'], 'title');
        $titleStr = $doc->createElementNS($this->ns['lomimscc'], 'string');
        $courseTitle = '';

        try {
            if ($this->courseEntity instanceof CourseEntity) {
                if (method_exists($this->courseEntity, 'getTitle')) {
                    $courseTitle = (string) $this->courseEntity->getTitle();
                }
                if ('' === $courseTitle && method_exists($this->courseEntity, 'getName')) {
                    $courseTitle = (string) $this->courseEntity->getName();
                }
            }
        } catch (Throwable) {
            // swallow
        }
        if ('' === $courseTitle) {
            $courseTitle = (string) ($this->courseCodeForLegacy ?: 'Course package');
        }
        $titleStr->nodeValue = $courseTitle;
        $title->appendChild($titleStr);
        $general->appendChild($title);

        $lang = $doc->createElementNS($this->ns['lomimscc'], 'language');
        $lang->nodeValue = 'en';
        $general->appendChild($lang);

        $lom->appendChild($general);
        $metadata->appendChild($lom);
        $manifest->appendChild($metadata);

        // <organizations> (single org with hierarchical items)
        $orgs = $doc->createElementNS($imscc, 'organizations');
        $orgId = 'ORG-'.bin2hex(random_bytes(4));
        $orgs->setAttribute('default', $orgId);
        $org = $doc->createElementNS($imscc, 'organization');
        $org->setAttribute('identifier', $orgId);
        $org->setAttribute('structure', 'hierarchical');

        // Build <item> tree based on 'path' (preferred) or href-derived path
        $folderNodeByPath = [];
        $getOrCreateFolder = function (array $parts) use ($doc, $imscc, $org, &$folderNodeByPath): DOMElement {
            $acc = '';
            $parent = $org;
            foreach ($parts as $seg) {
                if ('' === $seg) {
                    continue;
                }
                $acc = ('' === $acc) ? $seg : ($acc.'/'.$seg);
                if (!isset($folderNodeByPath[$acc])) {
                    $item = $doc->createElementNS($imscc, 'item');
                    $item->setAttribute('identifier', 'ITEM-'.substr(sha1($acc), 0, 12));
                    $titleEl = $doc->createElementNS($imscc, 'title');
                    $titleEl->nodeValue = $seg;
                    $item->appendChild($titleEl);
                    $parent->appendChild($item);
                    $folderNodeByPath[$acc] = $item;
                }
                $parent = $folderNodeByPath[$acc];
            }

            return $parent;
        };

        foreach ($resources as $r) {
            $href = (string) ($r['href'] ?? '');
            if ('' === $href) {
                continue;
            }

            // Prefer explicit 'path' (e.g., "Web Links/Title.xml" or "Discussions/Title.xml")
            if (!empty($r['path'])) {
                $relForTree = trim((string) $r['path'], '/');
            } else {
                $relForTree = preg_replace('#^resources/#', '', $href) ?? $href;
                $relForTree = ltrim($relForTree, '/');
            }

            $parts = array_values(array_filter(explode('/', $relForTree), static fn ($s) => '' !== $s));
            if (empty($parts)) {
                continue;
            }

            $fileName = array_pop($parts);
            $folderParent = $getOrCreateFolder($parts);

            $item = $doc->createElementNS($imscc, 'item');
            $item->setAttribute('identifier', 'ITEM-'.substr(sha1($href.($r['identifier'] ?? '')), 0, 12));
            $item->setAttribute('identifierref', $r['identifier']);

            $t = $doc->createElementNS($imscc, 'title');
            $title = (string) ($r['title'] ?? $fileName);
            $t->nodeValue = '' !== $title ? $title : $fileName;
            $item->appendChild($t);

            $folderParent->appendChild($item);
        }

        $orgs->appendChild($org);
        $manifest->appendChild($orgs);

        // <resources>
        $resNode = $doc->createElementNS($imscc, 'resources');
        foreach ($resources as $r) {
            $res = $doc->createElementNS($imscc, 'resource');
            $res->setAttribute('identifier', $r['identifier']);
            $res->setAttribute('type', $r['type']);
            $res->setAttribute('href', $r['href']);

            $file = $doc->createElementNS($imscc, 'file');
            $file->setAttribute('href', $r['href']);
            $res->appendChild($file);

            $resNode->appendChild($res);
        }
        $manifest->appendChild($resNode);

        $doc->appendChild($manifest);

        $path = $this->workdir.'/imsmanifest.xml';
        if (false === file_put_contents($path, $doc->saveXML() ?: '')) {
            $this->log('Failed to write imsmanifest.xml', ['path' => $path], 'error');

            throw new RuntimeException('Failed to write imsmanifest.xml');
        }
    }

    /**
     * Collect documents from the legacy bag.
     * For files, resolve absolute filesystem path via multi-step strategy.
     *
     * @return array<int,array{src:string, relZip:string, is_dir:bool, debug?:array}>
     */
    private function collectDocuments(): array
    {
        $docs = [];

        $res = \is_array($this->course->resources ?? null) ? $this->course->resources : [];
        $docKey = $this->firstExistingKey(
            $res,
            ['document', 'Document', \defined('RESOURCE_DOCUMENT') ? (string) RESOURCE_DOCUMENT : '']
        );

        if (!$docKey || empty($res[$docKey]) || !\is_array($res[$docKey])) {
            $this->log('No "document" bucket found in course bag', [], 'warn');

            return $docs;
        }

        $this->log('Scanning document bucket', ['items' => \count($res[$docKey])]);

        foreach ($res[$docKey] as $id => $wrap) {
            if (!\is_object($wrap)) {
                continue;
            }

            $entity = $this->unwrap($wrap);
            $attempts = []; // per-item trace

            // Path (tolerant)
            $raw = (string) ($entity->path ?? $entity->full_path ?? '');
            if ('' === $raw) {
                $raw = '/document/'.((string) ($entity->title ?? ''));
            }

            $segments = $this->extractDocumentSegmentsFromPath($raw);
            if (empty($segments)) {
                $this->log('Skipping non-document path', ['raw' => $raw, 'id' => (string) $id], 'debug');

                continue;
            }

            $fileType = strtolower((string) ($entity->file_type ?? $entity->filetype ?? ''));
            $isDir = ('folder' === $fileType) || str_ends_with($raw, '/');

            $relDoc = implode('/', $segments);
            $relZip = 'resources/'.$relDoc;

            if ($isDir) {
                $docs[] = ['src' => '', 'relZip' => rtrim($relZip, '/').'/', 'is_dir' => true, 'debug' => ['note' => 'folder']];
                $this->log('Folder queued', ['rel' => $relZip], 'debug');

                continue;
            }

            // Resolution pipeline
            $abs = null;

            $abs = $this->resolveByWrapperSourceId($wrap, $attempts);
            if (!$abs) {
                $abs = $this->resolveByEntityResourceNodeBestFile($entity, $attempts);
            }
            if (!$abs) {
                $abs = $this->resolveAbsoluteForDocumentSegments($segments, $attempts);
            }
            if (!$abs) {
                $abs = $this->resolveLegacyFilesystemBySegments($segments, $attempts);
            }
            $generatedAbs = null;
            if (!$abs) {
                $generatedAbs = $this->generateFromInlineContentIfAny($entity, $relDoc, $attempts);
                if ($generatedAbs) {
                    $abs = $generatedAbs;
                }
            }

            if (!$abs) {
                $this->log('Missing file after pipeline', [
                    'raw' => $raw,
                    'iid' => (int) ($entity->iid ?? $entity->id ?? 0),
                    'attempts' => $attempts,
                ], 'warn');

                $docs[] = [
                    'src' => '',
                    'relZip' => $relZip,
                    'is_dir' => false,
                    'debug' => $attempts,
                ];

                continue;
            }

            $docs[] = [
                'src' => $abs,
                'relZip' => $relZip,
                'is_dir' => false,
                'debug' => $attempts + ['resolved' => $abs, 'generated' => (bool) $generatedAbs],
            ];

            $this->log('Resolved document', [
                'relDoc' => $relDoc,
                'abs' => $abs,
                'generated' => (bool) $generatedAbs,
                'pipeline' => $attempts,
            ], 'info');
        }

        return $docs;
    }

    /**
     * Collect Web Links from legacy bag.
     * Tries common keys and fields: url|href|link, title|name, description|comment.
     *
     * @return array<int,array{title:string,url:string,description:string}>
     */
    private function collectWebLinks(): array
    {
        $out = [];
        $res = \is_array($this->course->resources ?? null) ? $this->course->resources : [];

        $wlKey = $this->firstExistingKey($res, ['weblink', 'weblinks', 'link', 'links', 'Link', 'Links', 'URL', 'Urls', 'urls']);
        if (!$wlKey || empty($res[$wlKey]) || !\is_array($res[$wlKey])) {
            $this->log('No Web Links bucket found', ['candidates' => $wlKey], 'debug');

            return $out;
        }

        foreach ($res[$wlKey] as $wrap) {
            if (!\is_object($wrap)) {
                continue;
            }
            $e = $this->unwrap($wrap);

            // URL
            $url = (string) ($e->url ?? $e->href ?? $e->link ?? '');
            if ('' === $url && !empty($e->path) && \is_string($e->path) && preg_match('~^https?://~i', $e->path)) {
                $url = (string) $e->path;
            }
            if ('' === $url || !preg_match('~^https?://~i', $url)) {
                $this->log('Skipping weblink without valid URL', ['raw' => $url], 'debug');

                continue;
            }

            // Title
            $title = (string) ($e->title ?? $e->name ?? $e->label ?? $url);
            if ('' === $title) {
                $title = $url;
            }

            // Description (optional)
            $desc = (string) ($e->description ?? $e->comment ?? $e->content ?? '');

            $out[] = ['title' => $title, 'url' => $url, 'description' => $desc];
        }

        $this->log('Web Links collected', ['count' => \count($out)], 'info');

        return $out;
    }

    /**
     * Collect Discussion Topics from legacy bag (Chamilo-friendly).
     * - Prefer explicit topic buckets: forum_topic | ForumTopic | thread | Thread
     * - Pull body from the first post in post/forum_post if topic has no text
     * - Fallback: if no topics, create one topic per forum using forum title/description.
     *
     * @return array<int,array{title:string,body:string}>
     */
    private function collectDiscussionTopics(): array
    {
        $out = [];
        $res = \is_array($this->course->resources ?? null) ? $this->course->resources : [];

        // Buckets
        $topicKey = $this->firstExistingKey($res, ['forum_topic', 'ForumTopic', 'thread', 'Thread']);
        $postKey = $this->firstExistingKey($res, ['forum_post', 'Forum_Post', 'post', 'Post']);
        $forumKey = $this->firstExistingKey($res, ['forum', 'Forum']);

        // Map first post text by thread/topic id
        $firstPostByThread = [];
        if ($postKey && \is_array($res[$postKey])) {
            foreach ($res[$postKey] as $pid => $pWrap) {
                if (!\is_object($pWrap)) {
                    continue;
                }
                $p = $this->unwrap($pWrap);
                $tid = (int) ($p->thread_id ?? $p->topic_id ?? 0);
                if ($tid <= 0) {
                    continue;
                }

                // Common fields for post content
                $txt = (string) ($p->post_text ?? $p->message ?? $p->text ?? $p->content ?? $p->comment ?? '');
                if ('' === $txt) {
                    continue;
                }

                // keep the first one we see (starter post)
                if (!isset($firstPostByThread[$tid])) {
                    $firstPostByThread[$tid] = $txt;
                }
            }
        }

        // Topics from topic bucket(s)
        if ($topicKey && \is_array($res[$topicKey])) {
            foreach ($res[$topicKey] as $tid => $tWrap) {
                if (!\is_object($tWrap)) {
                    continue;
                }
                $t = $this->unwrap($tWrap);

                // Title fields typical in Chamilo forum topics/threads
                $title = (string) ($t->thread_title ?? $t->title ?? $t->subject ?? '');
                if ('' === $title) {
                    $title = 'Discussion';
                }

                // Body: look on the topic first, otherwise fallback to first post text
                $body = (string) ($t->text ?? $t->description ?? $t->comment ?? $t->content ?? '');
                if ('' === $body) {
                    $body = $firstPostByThread[(int) ($t->id ?? $tid)]
                        ?? $firstPostByThread[(int) ($t->thread_id ?? 0)]
                        ?? '';
                }

                $out[] = ['title' => $title, 'body' => $body];
            }
        }

        // Fallback: if no explicit topics, create one topic per forum
        if (empty($out) && $forumKey && \is_array($res[$forumKey])) {
            foreach ($res[$forumKey] as $fid => $fWrap) {
                if (!\is_object($fWrap)) {
                    continue;
                }
                $f = $this->unwrap($fWrap);
                $title = (string) ($f->forum_title ?? $f->title ?? 'Forum');
                $desc = (string) ($f->forum_comment ?? $f->description ?? $f->comment ?? '');
                $out[] = ['title' => $title, 'body' => $desc];
            }
        }

        $this->log('Discussion Topics collected', ['count' => \count($out)], 'info');

        return $out;
    }

    /**
     * Copy documents into workdir/resources and produce manifest resource list.
     *
     * @param array<int,array{src:string, relZip:string, is_dir:bool, debug?:array}> $docs
     *
     * @return array<int,array{identifier:string, href:string, type:string, title?:string, path?:string}>
     */
    private function copyDocumentsIntoPackage(array $docs): array
    {
        $out = [];

        foreach ($docs as $d) {
            $targetRel = $d['relZip'];
            $targetAbs = $this->workdir.'/'.$targetRel;

            if ($d['is_dir']) {
                $this->mkdirp($targetAbs);

                continue; // folders are not listed as <resource>
            }

            // Ensure parent dir exists
            $this->mkdirp(\dirname($targetAbs));

            // Copy or write placeholder + diagnostics
            if ('' !== $d['src'] && is_file($d['src'])) {
                if ($this->pathsAreSame($d['src'], $targetAbs)) {
                    // Already generated in place
                    $this->log('Skipping copy (already in place)', ['path' => $targetAbs], 'debug');
                } elseif (!@copy($d['src'], $targetAbs)) {
                    $msg = "Failed to copy source file: {$d['src']}\n";
                    @file_put_contents($targetAbs.'.missing.txt', $msg);
                    $this->writeWhyFile($targetAbs, $d['debug'] ?? [], 'copy_failed', $d['src']);
                    $targetRel .= '.missing.txt';
                    $this->log('Copy failed', ['src' => $d['src'], 'dst' => $targetAbs], 'warn');
                }
            } else {
                @file_put_contents($targetAbs.'.missing.txt', "Missing source file (unresolved)\n");
                $this->writeWhyFile($targetAbs, $d['debug'] ?? [], 'unresolved', null);
                $targetRel .= '.missing.txt';
                $this->log('Wrote missing placeholder', ['dst' => $targetAbs.'.missing.txt'], 'debug');
            }

            // Derive a friendly title and a normalized relative "document" path for organization
            $relPathUnderResources = preg_replace('#^resources/#', '', $targetRel) ?? $targetRel;
            $relPathUnderResources = ltrim($relPathUnderResources, '/');
            $friendlyTitle = basename(rtrim($relPathUnderResources, '/'));

            // Basic webcontent resource
            $out[] = [
                'identifier' => 'RES-'.bin2hex(random_bytes(5)),
                'href' => $targetRel,
                'type' => 'webcontent',
                'title' => $friendlyTitle,
                'path' => $relPathUnderResources,
            ];
        }

        return $out;
    }

    /**
     * Extract segments after "/document", e.g. "/document/Folder/file.pdf" → ["Folder","file.pdf"].
     *
     * @return array<int,string>
     */
    private function extractDocumentSegmentsFromPath(string $raw): array
    {
        $decoded = urldecode($raw);
        // Remove optional leading slash and "document" root (case-insensitive)
        $rel = preg_replace('~^/?document/?~i', '', ltrim($decoded, '/')) ?? '';
        $rel = trim($rel, '/');

        if ('' === $rel) {
            return [];
        }

        // Split and normalize
        $parts = array_values(array_filter(explode('/', $rel), static fn ($s) => '' !== $s));

        return array_map(static fn ($s) => trim($s), $parts);
    }

    /**
     * STEP 0: Resolve by wrapper source_id (copy scenarios).
     */
    private function resolveByWrapperSourceId(object $wrap, ?array &$log = null): ?string
    {
        try {
            $sourceId = null;
            if (isset($wrap->source_id) && is_numeric($wrap->source_id)) {
                $sourceId = (int) $wrap->source_id;
            } elseif (isset($wrap->extra['source_id']) && is_numeric($wrap->extra['source_id'])) {
                $sourceId = (int) $wrap->extra['source_id'];
            }
            $this->appendAttempt($log, 'source_id_check', ['source_id' => $sourceId]);

            if ($sourceId && $sourceId > 0) {
                /** @var CDocument|null $doc */
                $doc = $this->em->getRepository(CDocument::class)->find($sourceId);
                $this->appendAttempt($log, 'source_id_entity', ['found' => (bool) $doc]);
                if ($doc) {
                    $tmp = $this->resolveByDocumentEntityBestFile($doc, $log, 'source_id');
                    if ($tmp) {
                        return $tmp;
                    }
                }
            }
        } catch (Throwable $e) {
            $this->appendAttempt($log, 'source_id_error', ['error' => $e->getMessage()]);
            $this->log('resolveByWrapperSourceId error', ['e' => $e->getMessage()], 'debug');
        }

        return null;
    }

    /**
     * STEP 1: Resolve by the actual entity ResourceNode (handles multiple files).
     */
    private function resolveByEntityResourceNodeBestFile(object $entity, ?array &$log = null): ?string
    {
        try {
            $iid = null;
            if (isset($entity->iid) && is_numeric($entity->iid)) {
                $iid = (int) $entity->iid;
            } elseif (isset($entity->id) && is_numeric($entity->id)) {
                $iid = (int) $entity->id;
            } elseif (method_exists($entity, 'getIid')) {
                $iid = (int) $entity->getIid();
            }
            $this->appendAttempt($log, 'entity_iid', ['iid' => $iid]);

            if ($iid && $iid > 0) {
                /** @var CDocument|null $doc */
                $doc = $this->em->getRepository(CDocument::class)->find($iid);
                $this->appendAttempt($log, 'entity_doc_found', ['found' => (bool) $doc]);
                if ($doc) {
                    return $this->resolveByDocumentEntityBestFile($doc, $log, 'entity_rn');
                }
            }
        } catch (Throwable $e) {
            $this->appendAttempt($log, 'entity_rn_error', ['error' => $e->getMessage()]);
            $this->log('RN resolve error', ['e' => $e->getMessage()], 'debug');
        }

        return null;
    }

    /**
     * Resolve best absolute path for any CDocument entity by inspecting all ResourceFiles.
     * Strategy: prefer the largest regular file (non-zero size), otherwise the first readable file.
     */
    private function resolveByDocumentEntityBestFile(CDocument $doc, ?array &$log = null, string $tag = 'rn'): ?string
    {
        $rn = $doc->getResourceNode();
        $this->appendAttempt($log, $tag.'_rn_present', ['present' => (bool) $rn]);
        if (!$rn) {
            return null;
        }

        // Try "first file" fast-path
        if (method_exists($rn, 'getFirstResourceFile') && ($file = $rn->getFirstResourceFile())) {
            $abs = $this->absolutePathFromResourceFile($file, $log, $tag.'_first');
            if ($abs) {
                return $abs;
            }
        }

        // Iterate all files if available
        if (method_exists($rn, 'getResourceFiles')) {
            $bestAbs = null;
            $bestSize = -1;

            foreach ($rn->getResourceFiles() as $idx => $file) {
                $abs = $this->absolutePathFromResourceFile($file, $log, $tag.'_iter_'.$idx);
                if (!$abs) {
                    continue;
                }
                $sz = @filesize($abs);
                if (false === $sz) {
                    $sz = -1;
                }
                $this->appendAttempt($log, $tag.'_iter_size_'.$idx, ['size' => $sz]);
                if ($sz > $bestSize) {
                    $bestSize = $sz;
                    $bestAbs = $abs;
                }
            }

            if ($bestAbs) {
                $this->appendAttempt($log, $tag.'_best', ['abs' => $bestAbs, 'size' => $bestSize]);

                return $bestAbs;
            }
        } else {
            $this->appendAttempt($log, $tag.'_no_iter', ['hint' => 'getResourceFiles not available']);
        }

        return null;
    }

    /**
     * Convert a ResourceFile into an absolute path under any of the known upload roots.
     * Tries several common roots: var/upload/resource, var/data/upload/resource, public/upload/resource, web/upload/resource.
     */
    private function absolutePathFromResourceFile(object $resourceFile, ?array &$log = null, string $tag = 'rf'): ?string
    {
        try {
            // Most Chamilo builds: repository returns a stored relative filename like "/ab/cd/ef123.bin"
            $storedRel = (string) $this->rnRepo->getFilename($resourceFile);
            $this->appendAttempt($log, $tag.'_storedRel', ['storedRel' => $storedRel]);

            if ('' !== $storedRel) {
                foreach ($this->getUploadBaseCandidates() as $root) {
                    $abs = rtrim($root, '/').$storedRel;
                    $ok = (is_readable($abs) && is_file($abs));
                    $this->appendAttempt($log, $tag.'_abs_try', ['root' => $root, 'abs' => $abs, 'ok' => $ok]);
                    if ($ok) {
                        return $abs;
                    }
                }
            }

            // Some builds might offer a direct path method
            if (method_exists($this->rnRepo, 'getAbsolutePath')) {
                $abs2 = (string) $this->rnRepo->getAbsolutePath($resourceFile);
                $ok2 = ('' !== $abs2 && is_readable($abs2) && is_file($abs2));
                $this->appendAttempt($log, $tag.'_abs2_try', ['abs2' => $abs2, 'ok' => $ok2]);
                if ($ok2) {
                    return $abs2;
                }
            }
        } catch (Throwable $e) {
            $this->appendAttempt($log, $tag.'_error', ['error' => $e->getMessage()]);
            $this->log('absolutePathFromResourceFile error', ['e' => $e->getMessage()], 'debug');
        }

        return null;
    }

    /**
     * Walk children by title from the course documents root and return the absolute file path.
     *
     * @param array<int,string> $segments
     *
     * @return string|null Absolute path or null if not found
     */
    private function resolveAbsoluteForDocumentSegments(array $segments, ?array &$log = null): ?string
    {
        if (!$this->courseEntity instanceof CourseEntity) {
            $this->appendAttempt($log, 'titlewalk_no_course', []);

            return null;
        }

        $root = $this->docRepo->getCourseDocumentsRootNode($this->courseEntity);
        $this->appendAttempt($log, 'titlewalk_root_present', ['present' => (bool) $root]);
        if (!$root) {
            return null;
        }

        $node = $root;
        foreach ($segments as $title) {
            $child = $this->docRepo->findChildNodeByTitle($node, $title);
            $this->appendAttempt($log, 'titlewalk_step', ['title' => $title, 'found' => (bool) $child]);
            if (!$child) {
                return null;
            }
            $node = $child;
        }

        $file = method_exists($node, 'getFirstResourceFile') ? $node->getFirstResourceFile() : null;
        $this->appendAttempt($log, 'titlewalk_file_present', ['present' => (bool) $file]);
        if (!$file) {
            return null;
        }

        $abs = $this->absolutePathFromResourceFile($file, $log, 'titlewalk_rf');
        if ($abs && is_readable($abs) && is_file($abs)) {
            return $abs;
        }

        return null;
    }

    /**
     * Legacy filesystem fallback under courses/<CODE>/document/...
     * Tries a few common base paths used in older deployments.
     */
    private function resolveLegacyFilesystemBySegments(array $segments, ?array &$log = null): ?string
    {
        if ('' === $this->courseCodeForLegacy) {
            $this->appendAttempt($log, 'legacy_no_code', []);

            return null;
        }

        $bases = [
            $this->projectDir.'/var/courses/'.$this->courseCodeForLegacy.'/document',
            $this->projectDir.'/app/courses/'.$this->courseCodeForLegacy.'/document',
            $this->projectDir.'/courses/'.$this->courseCodeForLegacy.'/document',
        ];
        $rel = implode('/', $segments);

        foreach ($bases as $base) {
            $cand = rtrim($base, '/').'/'.$rel;
            $ok = (is_readable($cand) && is_file($cand));
            $this->appendAttempt($log, 'legacy_try', ['candidate' => $cand, 'ok' => $ok]);
            if ($ok) {
                return $cand;
            }
        }

        return null;
    }

    /**
     * Some documents are inline HTML stored in DB (no ResourceFile).
     * If we can read text content from the entity, generate a temp file to export.
     *
     * Returns the absolute path of the generated file, or null.
     */
    private function generateFromInlineContentIfAny(object $entity, string $relDoc, ?array &$log = null): ?string
    {
        try {
            $content = null;
            if (method_exists($entity, 'getContent')) {
                $content = $entity->getContent();
            } elseif (isset($entity->content)) {
                $content = $entity->content;
            } elseif (isset($entity->comment) && \is_string($entity->comment) && strip_tags($entity->comment) !== $entity->comment) {
                $content = $entity->comment;
            }

            $text = (string) ($content ?? '');
            $this->appendAttempt($log, 'inline_check', ['hasContent' => ('' !== $text)]);

            if ('' === $text) {
                return null;
            }

            $ext = strtolower(pathinfo($relDoc, PATHINFO_EXTENSION));
            if ('' === $ext) {
                $relDoc .= '.html';
            }

            $generatedAbs = $this->workdir.'/_generated/'.$relDoc;
            $this->mkdirp(\dirname($generatedAbs));

            $payload = $text;
            $looksHtml = false !== stripos($text, '<html') || false !== stripos($text, '<!doctype') || false !== stripos($text, '<body');
            if (!$looksHtml) {
                $payload = "<!doctype html><html><meta charset=\"utf-8\"><body>\n".$text."\n</body></html>";
            }

            if (false === @file_put_contents($generatedAbs, $payload)) {
                $this->appendAttempt($log, 'inline_write_fail', ['path' => $generatedAbs]);

                return null;
            }

            $this->appendAttempt($log, 'inline_generated', ['path' => $generatedAbs]);

            return $generatedAbs;
        } catch (Throwable $e) {
            $this->appendAttempt($log, 'inline_error', ['error' => $e->getMessage()]);
            $this->log('generateFromInlineContentIfAny error', ['e' => $e->getMessage()], 'debug');

            return null;
        }
    }

    private function unwrap(object $o): object
    {
        return (isset($o->obj) && \is_object($o->obj)) ? $o->obj : $o;
    }

    /**
     * Get first existing key from a set of candidates.
     */
    private function firstExistingKey(array $arr, array $candidates): ?string
    {
        foreach ($candidates as $k) {
            if ('' === $k || null === $k) {
                continue;
            }
            if (isset($arr[$k]) && \is_array($arr[$k]) && !empty($arr[$k])) {
                return (string) $k;
            }
        }

        return null;
    }

    private function mkdirp(string $path): void
    {
        if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
            $this->log('Failed to create directory', ['path' => $path], 'error');

            throw new RuntimeException('Failed to create directory: '.$path);
        }
    }

    private function rrmdir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $it = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($ri as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }
        @rmdir($path);
    }

    private function normalizePath(string $p): string
    {
        $p = str_replace('\\', '/', $p);

        return preg_replace('#/+#', '/', $p) ?? $p;
    }

    private function zipDir(string $dir, string $zipPath): void
    {
        $zip = new ZipArchive();
        if (true !== $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            $this->log('Cannot open zip', ['zipPath' => $zipPath], 'error');

            throw new RuntimeException('Cannot open zip: '.$zipPath);
        }

        $dir = rtrim($dir, DIRECTORY_SEPARATOR);

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($it as $file) {
            /** @var SplFileInfo $file */
            if (!$file->isFile()) {
                continue;
            }
            $abs = $file->getRealPath();
            $rel = substr($abs, \strlen($dir) + 1);
            $rel = str_replace('\\', '/', $rel);
            $zip->addFile($abs, $rel);
        }

        $zip->close();
    }

    private function pathsAreSame(string $a, string $b): bool
    {
        $na = $this->normalizePath($a);
        $nb = $this->normalizePath($b);

        return rtrim($na, '/') === rtrim($nb, '/');
    }

    /**
     * Write a companion .why.txt file with pipeline diagnostics for a given missing file.
     */
    private function writeWhyFile(string $targetAbsNoExt, array $attempts, string $reason, ?string $src): void
    {
        try {
            $whyPath = $targetAbsNoExt.'.why.txt';
            $payload = "CC13 resolution diagnostics\n"
                ."Reason: {$reason}\n"
                .'Source: '.($src ?? '(none)')."\n"
                ."Attempts (JSON):\n"
                .json_encode($attempts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                ."\n";
            @file_put_contents($whyPath, $payload);
        } catch (Throwable $e) {
            $this->log('Failed to write .why.txt', ['dst' => $targetAbsNoExt, 'e' => $e->getMessage()], 'debug');
        }
    }

    /**
     * Write Web Links (IMS CC) files and return resource entries.
     *
     * @param array<int,array{title:string,url:string,description:string}> $links
     *
     * @return array<int,array{identifier:string, href:string, type:string, title:string, path:string}>
     */
    private function writeWebLinksIntoPackage(array $links): array
    {
        $out = [];
        foreach ($links as $idx => $l) {
            $id = 'WL-'.substr(sha1($l['title'].$l['url'].$idx.random_bytes(2)), 0, 10);
            $fn = $this->workdir.'/weblinks/'.$id.'.xml';
            $rel = 'weblinks/'.$id.'.xml';

            // Build minimal IMS WebLink XML (v1p1 works across CC versions)
            $doc = new DOMDocument('1.0', 'UTF-8');
            $doc->formatOutput = true;

            $ns = 'http://www.imsglobal.org/xsd/imswl_v1p1';
            $root = $doc->createElementNS($ns, 'wl:webLink');

            $t = $doc->createElementNS($ns, 'wl:title');
            $t->nodeValue = $l['title'];
            $root->appendChild($t);

            $url = $doc->createElementNS($ns, 'wl:url');
            $url->setAttribute('href', $l['url']);
            $root->appendChild($url);

            if ('' !== $l['description']) {
                $d = $doc->createElementNS($ns, 'wl:description');
                $d->nodeValue = $l['description'];
                $root->appendChild($d);
            }

            $doc->appendChild($root);
            if (false === @file_put_contents($fn, $doc->saveXML() ?: '')) {
                $this->log('Failed to write WebLink XML', ['path' => $fn], 'warn');

                continue;
            }

            $out[] = [
                'identifier' => 'RES-'.substr($id, 3),
                'href' => $rel,
                'type' => 'imswl_xmlv1p1',
                'title' => $l['title'],
                'path' => 'Web Links/'.$l['title'].'.xml',
            ];
        }

        return $out;
    }

    /**
     * Write Discussion Topics (IMS CC) files and return resource entries.
     *
     * @param array<int,array{title:string,body:string}> $topics
     *
     * @return array<int,array{identifier:string, href:string, type:string, title:string, path:string}>
     */
    private function writeDiscussionTopicsIntoPackage(array $topics): array
    {
        $out = [];
        foreach ($topics as $idx => $tpc) {
            $id = 'DT-'.substr(sha1($tpc['title'].$tpc['body'].$idx.random_bytes(2)), 0, 10);
            $fn = $this->workdir.'/discussions/'.$id.'.xml';
            $rel = 'discussions/'.$id.'.xml';

            // Build minimal IMS Discussion Topic XML (v1p1 for broad compatibility)
            $doc = new DOMDocument('1.0', 'UTF-8');
            $doc->formatOutput = true;

            $ns = 'http://www.imsglobal.org/xsd/imsdt_v1p1';
            $root = $doc->createElementNS($ns, 'dt:topic');

            $title = $doc->createElementNS($ns, 'dt:title');
            $title->nodeValue = '' !== $tpc['title'] ? $tpc['title'] : 'Discussion';
            $root->appendChild($title);

            $text = $doc->createElementNS($ns, 'dt:text');
            $text->setAttribute('texttype', 'text/html');

            // Put body inside CDATA to preserve HTML
            $body = '' !== $tpc['body'] ? $tpc['body'] : '';
            $cdata = $doc->createCDATASection($body);
            $text->appendChild($cdata);

            $root->appendChild($text);
            $doc->appendChild($root);

            if (false === @file_put_contents($fn, $doc->saveXML() ?: '')) {
                $this->log('Failed to write Discussion XML', ['path' => $fn], 'warn');

                continue;
            }

            // Safe filename in the logical path used by <organizations>
            $safeTitle = $this->safeFileName('' !== $tpc['title'] ? $tpc['title'] : 'Discussion');

            $out[] = [
                'identifier' => 'RES-'.substr($id, 3),
                'href' => $rel,
                'type' => 'imsdt_xmlv1p1',
                'title' => $tpc['title'],
                'path' => 'Discussions/'.$safeTitle.'.xml',
            ];
        }

        return $out;
    }

    /**
     * Create a filesystem-safe short filename (still human readable).
     */
    private function safeFileName(string $name): string
    {
        $name = trim($name);
        // Replace slashes and other risky chars
        $name = preg_replace('/[\/\x00-\x1F?<>\:\"|*]+/u', '_', $name) ?? 'item';
        // Collapse spaces/underscores and trim length
        $name = preg_replace('/[ _]+/u', ' ', $name) ?? $name;
        $name = trim($name);
        if ('' === $name) {
            $name = 'item';
        }
        // Keep it reasonable for manifest readability
        if (\function_exists('mb_substr')) {
            $name = mb_substr($name, 0, 80);
        } else {
            $name = substr($name, 0, 80);
        }

        return $name;
    }

    /**
     * Environment checks to help diagnose missing files.
     */
    private function checkEnvironment(): void
    {
        // Upload roots
        foreach ($this->getUploadBaseCandidates() as $root) {
            $this->log('Upload root check', [
                'root' => $root,
                'exists' => file_exists($root),
                'is_dir' => is_dir($root),
                'readable' => is_readable($root),
            ], 'debug');
        }

        // Legacy doc dirs
        $paths = [
            'legacy_var_courses' => $this->projectDir.'/var/courses/'.$this->courseCodeForLegacy.'/document',
            'legacy_app_courses' => $this->projectDir.'/app/courses/'.$this->courseCodeForLegacy.'/document',
            'legacy_courses' => $this->projectDir.'/courses/'.$this->courseCodeForLegacy.'/document',
        ];
        foreach ($paths as $name => $path) {
            $this->log('Legacy path check', [
                'name' => $name,
                'path' => $path,
                'exists' => file_exists($path),
                'is_dir' => is_dir($path),
                'readable' => is_readable($path),
            ], 'debug');
        }
    }

    /**
     * Candidate base directories for uploaded ResourceFiles.
     * The storedRel from ResourceNodeRepository::getFilename() is appended to each.
     */
    private function getUploadBaseCandidates(): array
    {
        return [
            $this->projectDir.'/var/upload/resource',
            $this->projectDir.'/var/data/upload/resource',
            $this->projectDir.'/public/upload/resource',
            $this->projectDir.'/web/upload/resource',
        ];
    }

    /**
     * Append a step/attempt into the per-item debug array.
     */
    private function appendAttempt(?array &$log, string $step, array $data): void
    {
        if (null === $log) {
            return;
        }
        $log[] = ['step' => $step, 'data' => $data];
    }

    /**
     * Centralized logger. Writes both to PHP error_log and to a dedicated log file.
     */
    private function log(string $msg, array $ctx = [], string $level = 'info'): void
    {
        if (!$this->debug && 'debug' === $level) {
            return; // skip noisy logs if not in debug
        }

        $line = '[CC13]['.strtoupper($level).'] '.date('c').' '.$msg;
        if (!empty($ctx)) {
            $json = json_encode($ctx, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $line .= ' | '.$json;
        }

        // Standard PHP error log
        error_log($line);

        // Dedicated file in /tmp (or system temp dir)
        @error_log($line.PHP_EOL, 3, $this->logFile);
    }
}
