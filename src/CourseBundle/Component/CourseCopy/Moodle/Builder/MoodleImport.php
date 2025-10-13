<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder;

use Chamilo\CoreBundle\Entity\Course as CourseEntity;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use Chamilo\CourseBundle\Component\CourseCopy\Course;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use DocumentManager;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PharData;
use RuntimeException;
use stdClass;
use Throwable;
use ZipArchive;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const FILEINFO_MIME_TYPE;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PATHINFO_EXTENSION;

/**
 * Moodle importer for Chamilo.
 */
class MoodleImport
{
    public function __construct(
        private bool $debug = false
    ) {}

    /**
     * Builds a Course ready for CourseRestorer::restore().
     */
    public function buildLegacyCourseFromMoodleArchive(string $archivePath): object
    {
        // Extract Moodle backup in a temp working directory
        [$workDir] = $this->extractToTemp($archivePath);

        $mbx = $workDir.'/moodle_backup.xml';
        if (!is_file($mbx)) {
            throw new RuntimeException('Not a Moodle backup (moodle_backup.xml missing)');
        }

        // Optional files.xml (used for documents/resources restore)
        $fx = $workDir.'/files.xml';
        $fileIndex = is_file($fx) ? $this->buildFileIndex($fx, $workDir) : ['byId' => [], 'byHash' => []];

        // Read backup structure (sections + activities)
        $mbDoc = $this->loadXml($mbx);
        $mb = new DOMXPath($mbDoc);

        $sections = $this->readSections($mb);
        $lpMap = $this->sectionsToLearnpaths($sections);

        // Initialize resource buckets (legacy snapshot shape)
        $resources = [
            'document' => [],
            'Forum_Category' => [],
            'forum' => [],
            'link' => [],
            // 'Link_Category' / 'learnpath' / 'scorm' will be created on demand
        ];

        // Ensure document folder structure
        $this->ensureDir($workDir.'/document');
        $this->ensureDir($workDir.'/document/moodle_pages');

        // Root folder as a legacy "document" entry (folder)
        $docFolderId = $this->nextId($resources['document']);
        $resources['document'][$docFolderId] = $this->mkLegacyItem(
            'document',
            $docFolderId,
            [
                'file_type' => 'folder',
                'path' => '/document/moodle_pages',
                'title' => 'moodle_pages',
            ]
        );

        // Default forum category (used as fallback)
        $defaultForumCatId = 1;
        $resources['Forum_Category'][$defaultForumCatId] = $this->mkLegacyItem(
            'Forum_Category',
            $defaultForumCatId,
            [
                'id' => $defaultForumCatId,
                'cat_title' => 'General',
                'cat_comment' => '',
            ]
        );

        // Iterate Moodle activities
        foreach ($mb->query('//activity') as $node) {
            /** @var DOMElement $node */
            $modName = (string) ($node->getElementsByTagName('modulename')->item(0)?->nodeValue ?? '');
            $dir = (string) ($node->getElementsByTagName('directory')->item(0)?->nodeValue ?? '');
            $sectionId = (int) ($node->getElementsByTagName('sectionid')->item(0)?->nodeValue ?? 0);
            $moduleXml = ('' !== $modName && '' !== $dir) ? $workDir.'/'.$dir.'/'.$modName.'.xml' : null;

            if ($this->debug) {
                error_log("MOODLE_IMPORT: activity={$modName} dir={$dir} section={$sectionId}");
            }

            switch ($modName) {
                case 'label':
                case 'page':
                    if (!$moduleXml || !is_file($moduleXml)) {
                        break;
                    }
                    $data = $this->readHtmlModule($moduleXml, $modName);

                    // Dump HTML content into /document/moodle_pages
                    $docId = $this->nextId($resources['document']);
                    $slug = $data['slug'] ?: ('page_'.$docId);
                    $rel = 'document/moodle_pages/'.$slug.'.html';
                    $abs = $workDir.'/'.$rel;
                    $this->ensureDir(\dirname($abs));
                    $html = $this->wrapHtmlIfNeeded($data['content'] ?? '', $data['name'] ?? ucfirst($modName));
                    file_put_contents($abs, $html);

                    // Legacy document entry (file)
                    $resources['document'][$docId] = $this->mkLegacyItem(
                        'document',
                        $docId,
                        [
                            'file_type' => 'file',
                            'path' => '/'.$rel,
                            'title' => (string) ($data['name'] ?? ucfirst($modName)),
                            'size' => @filesize($abs) ?: 0,
                            'comment' => '',
                        ]
                    );

                    // Add to LP if section map exists
                    if ($sectionId > 0 && isset($lpMap[$sectionId])) {
                        $lpMap[$sectionId]['items'][] = [
                            'item_type' => 'document',
                            'ref' => $docId,
                            'title' => $data['name'] ?? ucfirst($modName),
                        ];
                    }

                    break;

                    // Forums (+categories from intro hints)
                case 'forum':
                    if (!$moduleXml || !is_file($moduleXml)) {
                        break;
                    }
                    $f = $this->readForumModule($moduleXml);

                    $resources['forum'] ??= [];
                    $resources['Forum_Category'] ??= [];

                    $catId = (int) ($f['category_id'] ?? 0);
                    $catTitle = (string) ($f['category_title'] ?? '');

                    // Create Forum_Category if Moodle intro provided hints
                    if ($catId > 0 && !isset($resources['Forum_Category'][$catId])) {
                        $resources['Forum_Category'][$catId] = $this->mkLegacyItem(
                            'Forum_Category',
                            $catId,
                            [
                                'id' => $catId,
                                'cat_title' => ('' !== $catTitle ? $catTitle : ('Category '.$catId)),
                                'cat_comment' => '',
                                'title' => ('' !== $catTitle ? $catTitle : ('Category '.$catId)),
                                'description' => '',
                            ]
                        );
                    }

                    // Forum entry pointing to detected category or fallback
                    $dstCatId = $catId > 0 ? $catId : $defaultForumCatId;
                    $fid = $this->nextId($resources['forum']);
                    $resources['forum'][$fid] = $this->mkLegacyItem(
                        'forum',
                        $fid,
                        [
                            'id' => $fid,
                            'forum_title' => (string) ($f['name'] ?? 'Forum'),
                            'forum_comment' => (string) ($f['description'] ?? ''),
                            'forum_category' => $dstCatId,
                            'default_view' => 'flat',
                        ]
                    );

                    // Add to LP if section map exists
                    if ($sectionId > 0 && isset($lpMap[$sectionId])) {
                        $lpMap[$sectionId]['items'][] = [
                            'item_type' => 'forum',
                            'ref' => $fid,
                            'title' => $f['name'] ?? 'Forum',
                        ];
                    }

                    break;

                    // URL => link (+ Link_Category from intro hints)
                case 'url':
                    if (!$moduleXml || !is_file($moduleXml)) {
                        break;
                    }
                    $u = $this->readUrlModule($moduleXml);

                    $urlVal = trim((string) ($u['url'] ?? ''));
                    if ('' === $urlVal) {
                        break;
                    }

                    $resources['link'] ??= [];
                    $resources['Link_Category'] ??= [];

                    $catId = (int) ($u['category_id'] ?? 0);
                    $catTitle = (string) ($u['category_title'] ?? '');
                    if ($catId > 0 && !isset($resources['Link_Category'][$catId])) {
                        $resources['Link_Category'][$catId] = $this->mkLegacyItem(
                            'Link_Category',
                            $catId,
                            [
                                'id' => $catId,
                                'title' => ('' !== $catTitle ? $catTitle : ('Category '.$catId)),
                                'description' => '',
                                'category_title' => ('' !== $catTitle ? $catTitle : ('Category '.$catId)),
                            ]
                        );
                    }

                    $lid = $this->nextId($resources['link']);
                    $linkTitle = ($u['name'] ?? '') !== '' ? (string) $u['name'] : $urlVal;

                    $resources['link'][$lid] = $this->mkLegacyItem(
                        'link',
                        $lid,
                        [
                            'id' => $lid,
                            'title' => $linkTitle,
                            'description' => '',
                            'url' => $urlVal,
                            'target' => '',
                            'category_id' => $catId,
                            'on_homepage' => false,
                        ]
                    );

                    break;

                    // SCORM
                case 'scorm':
                    if (!$moduleXml || !is_file($moduleXml)) {
                        break;
                    }
                    $scorm = $this->readScormModule($moduleXml);
                    $resources['scorm'] ??= [];

                    $sid = $this->nextId($resources['scorm']);
                    $resources['scorm'][$sid] = $this->mkLegacyItem(
                        'scorm',
                        $sid,
                        [
                            'id' => $sid,
                            'title' => (string) ($scorm['name'] ?? 'SCORM package'),
                        ]
                    );

                    if ($sectionId > 0 && isset($lpMap[$sectionId])) {
                        $lpMap[$sectionId]['items'][] = [
                            'item_type' => 'scorm',
                            'ref' => $sid,
                            'title' => $scorm['name'] ?? 'SCORM package',
                        ];
                    }

                    break;

                default:
                    if ($this->debug) {
                        error_log("MOODLE_IMPORT: unhandled module {$modName}");
                    }

                    break;
            }
        }

        // Read Documents and Resource files using files.xml + activities/resource
        $this->readDocuments($workDir, $mb, $fileIndex, $resources, $lpMap);

        // Build learnpaths (one per section) with linked resources map
        if (!empty($lpMap)) {
            $resources['learnpath'] ??= [];
            foreach ($lpMap as $sid => $lp) {
                $linked = $this->collectLinkedFromLpItems($lp['items']);

                $lid = $this->nextId($resources['learnpath']);
                $resources['learnpath'][$lid] = $this->mkLegacyItem(
                    'learnpath',
                    $lid,
                    [
                        'id' => $lid,
                        'name' => (string) $lp['title'],
                    ],
                    ['items', 'linked_resources']
                );
                $resources['learnpath'][$lid]->items = array_map(
                    static fn (array $i) => [
                        'item_type' => (string) $i['item_type'],
                        'title' => (string) $i['title'],
                        'path' => '',
                        'ref' => $i['ref'] ?? null,
                    ],
                    $lp['items']
                );
                $resources['learnpath'][$lid]->linked_resources = $linked;
            }
        }

        // Compose Course snapshot
        $course = new Course();
        $course->resources = $resources;
        $course->backup_path = $workDir;

        // Meta: keep a stable place (Course::$meta) and optionally mirror into resources['__meta']
        $course->meta = [
            'import_source' => 'moodle',
            'generated_at' => date('c'),
        ];
        $course->resources['__meta'] = $course->meta; // if you prefer not to iterate over this, skip it in your loops

        // Basic course info (optional)
        $ci = \function_exists('api_get_course_info') ? (api_get_course_info() ?: []) : [];
        if (property_exists($course, 'code')) {
            $course->code = (string) ($ci['code'] ?? '');
        }
        if (property_exists($course, 'type')) {
            $course->type = 'partial';
        }
        if (property_exists($course, 'encoding')) {
            $course->encoding = \function_exists('api_get_system_encoding')
                ? api_get_system_encoding()
                : 'UTF-8';
        }

        if ($this->debug) {
            error_log('MOODLE_IMPORT: resources='.json_encode(
                array_map(static fn ($b) => \is_array($b) ? \count($b) : 0, $resources),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ));
            error_log('MOODLE_IMPORT: backup_path='.$course->backup_path);
            if (property_exists($course, 'code') && property_exists($course, 'encoding')) {
                error_log('MOODLE_IMPORT: course_code='.$course->code.' encoding='.$course->encoding);
            }
        }

        return $course;
    }

    private function extractToTemp(string $archivePath): array
    {
        $base = rtrim(sys_get_temp_dir(), '/').'/moodle_'.date('Ymd_His').'_'.bin2hex(random_bytes(3));
        if (!@mkdir($base, 0775, true)) {
            throw new RuntimeException('Cannot create temp dir');
        }

        $ext = strtolower(pathinfo($archivePath, PATHINFO_EXTENSION));
        if (\in_array($ext, ['zip', 'mbz'], true)) {
            $zip = new ZipArchive();
            if (true !== $zip->open($archivePath)) {
                throw new RuntimeException('Cannot open zip');
            }
            if (!$zip->extractTo($base)) {
                $zip->close();

                throw new RuntimeException('Cannot extract zip');
            }
            $zip->close();
        } elseif (\in_array($ext, ['gz', 'tgz'], true)) {
            $phar = new PharData($archivePath);
            $phar->extractTo($base, null, true);
        } else {
            throw new RuntimeException('Unsupported archive type');
        }

        if (!is_file($base.'/moodle_backup.xml')) {
            throw new RuntimeException('Not a Moodle backup (moodle_backup.xml missing)');
        }

        return [$base];
    }

    private function loadXml(string $path): DOMDocument
    {
        $xml = @file_get_contents($path);
        if (false === $xml || '' === $xml) {
            throw new RuntimeException('Cannot read XML: '.$path);
        }
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        if (!@$doc->loadXML($xml)) {
            throw new RuntimeException('Invalid XML: '.$path);
        }

        return $doc;
    }

    /**
     * Build an index from files.xml.
     * Returns ['byId' => [id => row], 'byHash' => [hash => row]].
     * Each row contains: id, hash, filename, filepath, component, filearea, mimetype, filesize, contextid, blob(abs path).
     */
    private function buildFileIndex(string $filesXmlPath, string $workDir): array
    {
        $doc = $this->loadXml($filesXmlPath);
        $xp = new DOMXPath($doc);

        $byId = [];
        $byHash = [];

        foreach ($xp->query('//file') as $f) {
            /** @var DOMElement $f */
            $id = (int) ($f->getAttribute('id') ?? 0);
            $hash = (string) ($f->getElementsByTagName('contenthash')->item(0)?->nodeValue ?? '');
            if ('' === $hash) {
                continue;
            }

            $name = (string) ($f->getElementsByTagName('filename')->item(0)?->nodeValue ?? '');
            $fp = (string) ($f->getElementsByTagName('filepath')->item(0)?->nodeValue ?? '/');
            $comp = (string) ($f->getElementsByTagName('component')->item(0)?->nodeValue ?? '');
            $fa = (string) ($f->getElementsByTagName('filearea')->item(0)?->nodeValue ?? '');
            $mime = (string) ($f->getElementsByTagName('mimetype')->item(0)?->nodeValue ?? '');
            $size = (int) ($f->getElementsByTagName('filesize')->item(0)?->nodeValue ?? 0);
            $ctx = (int) ($f->getElementsByTagName('contextid')->item(0)?->nodeValue ?? 0);

            $blob = $this->contentHashPath($workDir, $hash);

            $row = [
                'id' => $id,
                'hash' => $hash,
                'filename' => $name,
                'filepath' => $fp,
                'component' => $comp,
                'filearea' => $fa,
                'mimetype' => $mime,
                'filesize' => $size,
                'contextid' => $ctx,
                'blob' => $blob,
            ];

            if ($id > 0) {
                $byId[$id] = $row;
            }
            $byHash[$hash] = $row;
        }

        return ['byId' => $byId, 'byHash' => $byHash];
    }

    private function readSections(DOMXPath $xp): array
    {
        $out = [];
        foreach ($xp->query('//section') as $s) {
            /** @var DOMElement $s */
            $id = (int) ($s->getElementsByTagName('sectionid')->item(0)?->nodeValue ?? 0);
            if ($id <= 0) {
                $id = (int) ($s->getElementsByTagName('number')->item(0)?->nodeValue
                    ?? $s->getElementsByTagName('id')->item(0)?->nodeValue
                    ?? 0);
            }
            $name = (string) ($s->getElementsByTagName('name')->item(0)?->nodeValue ?? '');
            $summary = (string) ($s->getElementsByTagName('summary')->item(0)?->nodeValue ?? '');
            if ($id > 0) {
                $out[$id] = ['id' => $id, 'name' => $name, 'summary' => $summary];
            }
        }

        return $out;
    }

    private function sectionsToLearnpaths(array $sections): array
    {
        $map = [];
        foreach ($sections as $sid => $s) {
            $title = $s['name'] ?: ('Section '.$sid);
            $map[(int) $sid] = [
                'title' => $title,
                'items' => [],
            ];
        }

        return $map;
    }

    private function readHtmlModule(string $xmlPath, string $type): array
    {
        $doc = $this->loadXml($xmlPath);
        $xp = new DOMXPath($doc);

        $name = (string) ($xp->query('//name')->item(0)?->nodeValue ?? ucfirst($type));

        $content = (string) ($xp->query('//intro')->item(0)?->nodeValue
            ?? $xp->query('//content')->item(0)?->nodeValue
            ?? '');

        return [
            'name' => $name,
            'content' => $content,
            'slug' => $this->slugify($name),
        ];
    }

    private function readForumModule(string $xmlPath): array
    {
        $doc = $this->loadXml($xmlPath);
        $xp = new DOMXPath($doc);

        $name = trim((string) ($xp->query('//forum/name')->item(0)?->nodeValue ?? ''));
        $description = (string) ($xp->query('//forum/intro')->item(0)?->nodeValue ?? '');
        $type = trim((string) ($xp->query('//forum/type')->item(0)?->nodeValue ?? 'general'));

        $catId = 0;
        $catTitle = '';
        if (preg_match('/CHAMILO2:forum_category_id:(\d+)/', $description, $m)) {
            $catId = (int) $m[1];
        }
        if (preg_match('/CHAMILO2:forum_category_title:([^\-]+?)\s*-->/u', $description, $m)) {
            $catTitle = trim($m[1]);
        }

        return [
            'name' => ('' !== $name ? $name : 'Forum'),
            'description' => $description,
            'type' => ('' !== $type ? $type : 'general'),
            'category_id' => $catId,
            'category_title' => $catTitle,
        ];
    }

    private function readUrlModule(string $xmlPath): array
    {
        $doc = $this->loadXml($xmlPath);
        $xp = new DOMXPath($doc);
        $name = trim($xp->query('//url/name')->item(0)?->nodeValue ?? '');
        $url = trim($xp->query('//url/externalurl')->item(0)?->nodeValue ?? '');
        $intro = (string) ($xp->query('//url/intro')->item(0)?->nodeValue ?? '');

        $catId = 0;
        $catTitle = '';
        if (preg_match('/CHAMILO2:link_category_id:(\d+)/', $intro, $m)) {
            $catId = (int) $m[1];
        }
        if (preg_match('/CHAMILO2:link_category_title:([^\-]+?)\s*-->/u', $intro, $m)) {
            $catTitle = trim($m[1]);
        }

        return ['name' => $name, 'url' => $url, 'category_id' => $catId, 'category_title' => $catTitle];
    }

    private function readScormModule(string $xmlPath): array
    {
        $doc = $this->loadXml($xmlPath);
        $xp = new DOMXPath($doc);

        return [
            'name' => (string) ($xp->query('//name')->item(0)?->nodeValue ?? 'SCORM'),
        ];
    }

    private function collectLinkedFromLpItems(array $items): array
    {
        $map = [
            'document' => 'document',
            'forum' => 'forum',
            'url' => 'link',
            'link' => 'link',
            'weblink' => 'link',
            'work' => 'works',
            'student_publication' => 'works',
            'quiz' => 'quiz',
            'exercise' => 'quiz',
            'survey' => 'survey',
            'scorm' => 'scorm',
        ];

        $out = [];
        foreach ($items as $i) {
            $t = (string) ($i['item_type'] ?? '');
            $r = $i['ref'] ?? null;
            if ('' === $t || null === $r) {
                continue;
            }
            $bag = $map[$t] ?? $t;
            $out[$bag] ??= [];
            $out[$bag][] = (int) $r;
        }

        return $out;
    }

    private function nextId(array $bucket): int
    {
        $max = 0;
        foreach ($bucket as $k => $_) {
            $i = is_numeric($k) ? (int) $k : 0;
            if ($i > $max) {
                $max = $i;
            }
        }

        return $max + 1;
    }

    private function slugify(string $s): string
    {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        $t = strtolower(preg_replace('/[^a-z0-9]+/', '-', $t ?: $s));

        return trim($t, '-') ?: 'item';
    }

    private function wrapHtmlIfNeeded(string $content, string $title = 'Page'): string
    {
        $trim = ltrim($content);
        $looksHtml = str_contains(strtolower(substr($trim, 0, 200)), '<html')
            || str_contains(strtolower(substr($trim, 0, 200)), '<!doctype');

        if ($looksHtml) {
            return $content;
        }

        return "<!doctype html>\n<html><head><meta charset=\"utf-8\"><title>".
            htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').
            "</title></head><body>\n".$content."\n</body></html>";
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create directory: '.$dir);
        }
    }

    /**
     * Resolve physical path for a given contenthash.
     * Our exporter writes blobs in: files/<first two letters of hash>/<hash>.
     */
    private function contentHashPath(string $workDir, string $hash): string
    {
        $h = trim($hash);
        if ('' === $h || \strlen($h) < 2) {
            return $workDir.'/files/'.$h;
        }

        // export convention: files/<two first letters>/<full-hash>
        return $workDir.'/files/'.substr($h, 0, 2).'/'.$h;
    }

    /**
     * Fast-path: persist only Links (and Link Categories) from a Moodle backup
     * directly with Doctrine entities. This bypasses the generic Restorer so we
     * avoid ResourceType#tool and UserAuthSource#url cascade issues.
     *
     * @return array{categories:int,links:int}
     */
    public function restoreLinks(
        string $archivePath,
        EntityManagerInterface $em,
        int $courseRealId,
        int $sessionId = 0,
        ?object $courseArg = null
    ): array {
        // Resolve parent entities
        /** @var CourseEntity|null $course */
        $course = $em->getRepository(CourseEntity::class)->find($courseRealId);
        if (!$course) {
            throw new RuntimeException('Destination course entity not found (real_id='.$courseRealId.')');
        }

        /** @var SessionEntity|null $session */
        $session = $sessionId > 0
            ? $em->getRepository(SessionEntity::class)->find($sessionId)
            : null;

        // Fast-path: use filtered snapshot if provided (import/resources selection)
        if ($courseArg && isset($courseArg->resources) && \is_array($courseArg->resources)) {
            $linksBucket = (array) ($courseArg->resources['link'] ?? []);
            $catsBucket = (array) ($courseArg->resources['Link_Category'] ?? []);

            if (empty($linksBucket)) {
                if ($this->debug) {
                    error_log('MOODLE_IMPORT[restoreLinks]: snapshot has no selected links');
                }

                return ['categories' => 0, 'links' => 0];
            }

            // Build set of category ids actually referenced by selected links
            $usedCatIds = [];
            foreach ($linksBucket as $L) {
                $oldCatId = (int) ($L->category_id ?? 0);
                if ($oldCatId > 0) {
                    $usedCatIds[$oldCatId] = true;
                }
            }

            // Persist only needed categories
            $catMapByOldId = [];
            $newCats = 0;

            foreach ($catsBucket as $oldId => $C) {
                if (!isset($usedCatIds[$oldId])) {
                    continue;
                }

                $cat = (new CLinkCategory())
                    ->setTitle((string) ($C->title ?? ('Category '.$oldId)))
                    ->setDescription((string) ($C->description ?? ''))
                ;

                // Parent & course/session links BEFORE persist (prePersist needs a parent)
                if (method_exists($cat, 'setParent')) {
                    $cat->setParent($course);
                } elseif (method_exists($cat, 'setParentResourceNode') && method_exists($course, 'getResourceNode')) {
                    $cat->setParentResourceNode($course->getResourceNode());
                }
                if (method_exists($cat, 'addCourseLink')) {
                    $cat->addCourseLink($course, $session);
                }

                $em->persist($cat);
                $catMapByOldId[(int) $oldId] = $cat;
                $newCats++;
            }
            if ($newCats > 0) {
                $em->flush();
            }

            // Persist selected links
            $newLinks = 0;
            foreach ($linksBucket as $L) {
                $url = trim((string) ($L->url ?? ''));
                if ('' === $url) {
                    continue;
                }

                $title = (string) ($L->title ?? '');
                if ('' === $title) {
                    $title = $url;
                }

                $link = (new CLink())
                    ->setUrl($url)
                    ->setTitle($title)
                    ->setDescription((string) ($L->description ?? ''))
                    ->setTarget((string) ($L->target ?? ''))
                ;

                if (method_exists($link, 'setParent')) {
                    $link->setParent($course);
                } elseif (method_exists($link, 'setParentResourceNode') && method_exists($course, 'getResourceNode')) {
                    $link->setParentResourceNode($course->getResourceNode());
                }
                if (method_exists($link, 'addCourseLink')) {
                    $link->addCourseLink($course, $session);
                }

                $oldCatId = (int) ($L->category_id ?? 0);
                if ($oldCatId > 0 && isset($catMapByOldId[$oldCatId])) {
                    $link->setCategory($catMapByOldId[$oldCatId]);
                }

                $em->persist($link);
                $newLinks++;
            }

            $em->flush();

            if ($this->debug) {
                error_log('MOODLE_IMPORT[restoreLinks]: persisted (snapshot)='.
                    json_encode(['cats' => $newCats, 'links' => $newLinks], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            return ['categories' => $newCats, 'links' => $newLinks];
        }

        // Extract & open main XML
        [$workDir] = $this->extractToTemp($archivePath);

        $mbx = $workDir.'/moodle_backup.xml';
        if (!is_file($mbx)) {
            throw new RuntimeException('Not a Moodle backup (moodle_backup.xml missing)');
        }
        $mbDoc = $this->loadXml($mbx);
        $mb = new DOMXPath($mbDoc);

        // Collect URL activities -> { name, url, category hints }
        $links = [];
        $categories = []; // oldCatId => ['title' => ...]
        foreach ($mb->query('//activity') as $node) {
            /** @var DOMElement $node */
            $modName = (string) ($node->getElementsByTagName('modulename')->item(0)?->nodeValue ?? '');
            if ('url' !== $modName) {
                continue;
            }

            $dir = (string) ($node->getElementsByTagName('directory')->item(0)?->nodeValue ?? '');
            $moduleXml = ('' !== $dir) ? $workDir.'/'.$dir.'/url.xml' : null;
            if (!$moduleXml || !is_file($moduleXml)) {
                if ($this->debug) {
                    error_log('MOODLE_IMPORT[restoreLinks]: skip url (url.xml not found)');
                }

                continue;
            }

            $u = $this->readUrlModule($moduleXml);

            $urlVal = trim((string) ($u['url'] ?? ''));
            if ('' === $urlVal) {
                if ($this->debug) {
                    error_log('MOODLE_IMPORT[restoreLinks]: skip url (empty externalurl)');
                }

                continue;
            }

            $oldCatId = (int) ($u['category_id'] ?? 0);
            $oldCatTitle = (string) ($u['category_title'] ?? '');
            if ($oldCatId > 0 && !isset($categories[$oldCatId])) {
                $categories[$oldCatId] = [
                    'title' => ('' !== $oldCatTitle ? $oldCatTitle : ('Category '.$oldCatId)),
                    'description' => '',
                ];
            }

            $links[] = [
                'name' => (string) ($u['name'] ?? ''),
                'url' => $urlVal,
                'description' => '',
                'target' => '',
                'old_cat_id' => $oldCatId,
            ];
        }

        if ($this->debug) {
            error_log('MOODLE_IMPORT[restoreLinks]: to_persist='.
                json_encode(['cats' => \count($categories), 'links' => \count($links)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        if (empty($links) && empty($categories)) {
            return ['categories' => 0, 'links' => 0];
        }

        // Helper: robustly resolve an IID as int after flush
        $resolveIid = static function ($entity): int {
            // try entity->getIid()
            if (method_exists($entity, 'getIid')) {
                $iid = $entity->getIid();
                if (\is_int($iid)) {
                    return $iid;
                }
                if (is_numeric($iid)) {
                    return (int) $iid;
                }
            }
            // fallback: resource node iid
            if (method_exists($entity, 'getResourceNode')) {
                $node = $entity->getResourceNode();
                if ($node && method_exists($node, 'getIid')) {
                    $nid = $node->getIid();
                    if (\is_int($nid)) {
                        return $nid;
                    }
                    if (is_numeric($nid)) {
                        return (int) $nid;
                    }
                }
            }
            // last resort: primary ID
            if (method_exists($entity, 'getId')) {
                $id = $entity->getId();
                if (\is_int($id)) {
                    return $id;
                }
                if (is_numeric($id)) {
                    return (int) $id;
                }
            }

            return 0;
        };

        // Persist categories first -> flush -> refresh -> map iid
        $catMapByOldId = [];   // oldCatId => CLinkCategory entity
        $iidMapByOldId = [];   // oldCatId => int iid
        $newCats = 0;

        foreach ($categories as $oldId => $payload) {
            $cat = (new CLinkCategory())
                ->setTitle((string) $payload['title'])
                ->setDescription((string) $payload['description'])
            ;

            // Parent & course/session links BEFORE persist (prePersist needs a parent)
            if (method_exists($cat, 'setParent')) {
                $cat->setParent($course);
            } elseif (method_exists($cat, 'setParentResourceNode') && method_exists($course, 'getResourceNode')) {
                $cat->setParentResourceNode($course->getResourceNode());
            }
            if (method_exists($cat, 'addCourseLink')) {
                $cat->addCourseLink($course, $session);
            }

            $em->persist($cat);
            $catMapByOldId[(int) $oldId] = $cat;
            $newCats++;
        }

        // Flush categories to get identifiers assigned
        if ($newCats > 0) {
            $em->flush();
            // Refresh & resolve iid
            foreach ($catMapByOldId as $oldId => $cat) {
                $em->refresh($cat);
                $iidMapByOldId[$oldId] = $resolveIid($cat);
                if ($this->debug) {
                    error_log('MOODLE_IMPORT[restoreLinks]: category persisted {old='.$oldId.', iid='.$iidMapByOldId[$oldId].', title='.$cat->getTitle().'}');
                }
            }
        }

        // Persist links (single flush at the end)
        $newLinks = 0;
        foreach ($links as $L) {
            $url = trim((string) $L['url']);
            if ('' === $url) {
                continue;
            }

            $title = (string) ($L['name'] ?? '');
            if ('' === $title) {
                $title = $url;
            }

            $link = (new CLink())
                ->setUrl($url)
                ->setTitle($title)
                ->setDescription((string) ($L['description'] ?? ''))
                ->setTarget((string) ($L['target'] ?? ''))
            ;

            // Parent & course/session links
            if (method_exists($link, 'setParent')) {
                $link->setParent($course);
            } elseif (method_exists($link, 'setParentResourceNode') && method_exists($course, 'getResourceNode')) {
                $link->setParentResourceNode($course->getResourceNode());
            }
            if (method_exists($link, 'addCourseLink')) {
                $link->addCourseLink($course, $session);
            }

            // Attach category if it existed in Moodle
            $oldCatId = (int) ($L['old_cat_id'] ?? 0);
            if ($oldCatId > 0 && isset($catMapByOldId[$oldCatId])) {
                $link->setCategory($catMapByOldId[$oldCatId]);
            }

            $em->persist($link);
            $newLinks++;
        }

        $em->flush();

        if ($this->debug) {
            error_log('MOODLE_IMPORT[restoreLinks]: persisted='.
                json_encode(['cats' => $newCats, 'links' => $newLinks], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return ['categories' => $newCats, 'links' => $newLinks];
    }

    /**
     * Fast-path: persist only Forum Categories and Forums from a Moodle backup,
     * wiring proper parents and course/session links with Doctrine entities.
     *
     * @return array{categories:int,forums:int}
     */
    public function restoreForums(
        string $archivePath,
        EntityManagerInterface $em,
        int $courseRealId,
        int $sessionId = 0,
        ?object $courseArg = null
    ): array {
        /** @var CourseEntity|null $course */
        $course = $em->getRepository(CourseEntity::class)->find($courseRealId);
        if (!$course) {
            throw new RuntimeException('Destination course entity not found (real_id='.$courseRealId.')');
        }

        /** @var SessionEntity|null $session */
        $session = $sessionId > 0
            ? $em->getRepository(SessionEntity::class)->find($sessionId)
            : null;

        // Fast-path: use filtered snapshot if provided (import/resources selection)
        if ($courseArg && isset($courseArg->resources) && \is_array($courseArg->resources)) {
            $forumsBucket = (array) ($courseArg->resources['forum'] ?? []);
            $catsBucket = (array) ($courseArg->resources['Forum_Category'] ?? []);

            if (empty($forumsBucket)) {
                if ($this->debug) {
                    error_log('MOODLE_IMPORT[restoreForums]: snapshot has no selected forums');
                }

                return ['categories' => 0, 'forums' => 0];
            }

            // Categories actually referenced by selected forums
            $usedCatIds = [];
            foreach ($forumsBucket as $F) {
                $oldCatId = (int) ($F->forum_category ?? 0);
                if ($oldCatId > 0) {
                    $usedCatIds[$oldCatId] = true;
                }
            }

            // Persist only needed categories
            $catMapByOldId = [];
            $newCats = 0;
            foreach ($catsBucket as $oldId => $C) {
                if (!isset($usedCatIds[$oldId])) {
                    continue;
                }

                $cat = (new CForumCategory())
                    ->setTitle((string) ($C->cat_title ?? $C->title ?? ('Category '.$oldId)))
                    ->setCatComment((string) ($C->cat_comment ?? $C->description ?? ''))
                    ->setParent($course)
                    ->addCourseLink($course, $session)
                ;
                $em->persist($cat);
                $catMapByOldId[(int) $oldId] = $cat;
                $newCats++;
            }
            if ($newCats > 0) {
                $em->flush();
            }

            // Fallback default category if none referenced
            $defaultCat = null;
            $ensureDefault = function () use (&$defaultCat, $course, $session, $em): CForumCategory {
                if ($defaultCat instanceof CForumCategory) {
                    return $defaultCat;
                }
                $defaultCat = (new CForumCategory())
                    ->setTitle('General')
                    ->setCatComment('')
                    ->setParent($course)
                    ->addCourseLink($course, $session)
                ;
                $em->persist($defaultCat);
                $em->flush();

                return $defaultCat;
            };

            // Persist selected forums
            $newForums = 0;
            foreach ($forumsBucket as $F) {
                $title = (string) ($F->forum_title ?? $F->title ?? 'Forum');
                $comment = (string) ($F->forum_comment ?? $F->description ?? '');

                $dstCategory = null;
                $oldCatId = (int) ($F->forum_category ?? 0);
                if ($oldCatId > 0 && isset($catMapByOldId[$oldCatId])) {
                    $dstCategory = $catMapByOldId[$oldCatId];
                } elseif (1 === \count($catMapByOldId)) {
                    $dstCategory = reset($catMapByOldId);
                } else {
                    $dstCategory = $ensureDefault();
                }

                $forum = (new CForum())
                    ->setTitle($title)
                    ->setForumComment($comment)
                    ->setForumCategory($dstCategory)
                    ->setAllowAttachments(1)
                    ->setAllowNewThreads(1)
                    ->setDefaultView('flat')
                    ->setParent($dstCategory)
                    ->addCourseLink($course, $session)
                ;

                $em->persist($forum);
                $newForums++;
            }

            $em->flush();

            if ($this->debug) {
                error_log('MOODLE_IMPORT[restoreForums]: persisted (snapshot) cats='.$newCats.' forums='.$newForums);
            }

            return ['categories' => $newCats + ($defaultCat ? 1 : 0), 'forums' => $newForums];
        }

        [$workDir] = $this->extractToTemp($archivePath);

        $mbx = $workDir.'/moodle_backup.xml';
        if (!is_file($mbx)) {
            throw new RuntimeException('Not a Moodle backup (moodle_backup.xml missing)');
        }
        $mbDoc = $this->loadXml($mbx);
        $mb = new DOMXPath($mbDoc);

        $forums = [];
        $categories = [];
        foreach ($mb->query('//activity') as $node) {
            /** @var DOMElement $node */
            $modName = (string) ($node->getElementsByTagName('modulename')->item(0)?->nodeValue ?? '');
            if ('forum' !== $modName) {
                continue;
            }

            $dir = (string) ($node->getElementsByTagName('directory')->item(0)?->nodeValue ?? '');
            $moduleXml = ('' !== $dir) ? $workDir.'/'.$dir.'/forum.xml' : null;
            if (!$moduleXml || !is_file($moduleXml)) {
                if ($this->debug) {
                    error_log('MOODLE_IMPORT[restoreForums]: skip (forum.xml not found)');
                }

                continue;
            }

            $f = $this->readForumModule($moduleXml);

            $oldCatId = (int) ($f['category_id'] ?? 0);
            $oldCatTitle = (string) ($f['category_title'] ?? '');
            if ($oldCatId > 0 && !isset($categories[$oldCatId])) {
                $categories[$oldCatId] = [
                    'title' => ('' !== $oldCatTitle ? $oldCatTitle : ('Category '.$oldCatId)),
                    'description' => '',
                ];
            }

            $forums[] = [
                'name' => (string) ($f['name'] ?? 'Forum'),
                'description' => (string) ($f['description'] ?? ''),
                'type' => (string) ($f['type'] ?? 'general'),
                'old_cat_id' => $oldCatId,
            ];
        }

        if ($this->debug) {
            error_log('MOODLE_IMPORT[restoreForums]: found forums='.\count($forums).' cats='.\count($categories));
        }

        if (empty($forums) && empty($categories)) {
            return ['categories' => 0, 'forums' => 0];
        }

        $catMapByOldId = []; // oldCatId => CForumCategory
        $newCats = 0;

        foreach ($categories as $oldId => $payload) {
            $cat = (new CForumCategory())
                ->setTitle((string) $payload['title'])
                ->setCatComment((string) $payload['description'])
                ->setParent($course)
                ->addCourseLink($course, $session)
            ;
            $em->persist($cat);
            $catMapByOldId[(int) $oldId] = $cat;
            $newCats++;
        }
        if ($newCats > 0) {
            $em->flush();
        }

        $defaultCat = null;
        $ensureDefault = function () use (&$defaultCat, $course, $session, $em): CForumCategory {
            if ($defaultCat instanceof CForumCategory) {
                return $defaultCat;
            }
            $defaultCat = (new CForumCategory())
                ->setTitle('General')
                ->setCatComment('')
                ->setParent($course)
                ->addCourseLink($course, $session)
            ;
            $em->persist($defaultCat);
            $em->flush();

            return $defaultCat;
        };

        $newForums = 0;

        foreach ($forums as $F) {
            $title = (string) ($F['name'] ?? 'Forum');
            $comment = (string) ($F['description'] ?? '');

            $dstCategory = null;
            $oldCatId = (int) ($F['old_cat_id'] ?? 0);
            if ($oldCatId > 0 && isset($catMapByOldId[$oldCatId])) {
                $dstCategory = $catMapByOldId[$oldCatId];
            } elseif (1 === \count($catMapByOldId)) {
                $dstCategory = reset($catMapByOldId);
            } else {
                $dstCategory = $ensureDefault();
            }

            $forum = (new CForum())
                ->setTitle($title)
                ->setForumComment($comment)
                ->setForumCategory($dstCategory)
                ->setAllowAttachments(1)
                ->setAllowNewThreads(1)
                ->setDefaultView('flat')
                ->setParent($dstCategory)
                ->addCourseLink($course, $session)
            ;

            $em->persist($forum);
            $newForums++;
        }

        $em->flush();

        if ($this->debug) {
            error_log('MOODLE_IMPORT[restoreForums]: persisted cats='.$newCats.' forums='.$newForums);
        }

        return ['categories' => $newCats, 'forums' => $newForums];
    }

    /**
     * Fast-path: restore only Documents from a Moodle backup, wiring ResourceFiles directly.
     * CHANGE: We already normalize paths and explicitly strip a leading "Documents/" segment,
     * so the Moodle top-level "Documents" folder is treated as the document root in Chamilo.
     */
    public function restoreDocuments(
        string $archivePath,
        EntityManagerInterface $em,
        int $courseRealId,
        int $sessionId = 0,
        int $sameFileNameOption = 2,
        ?object $courseArg = null
    ): array {
        // Use filtered snapshot if provided; otherwise build from archive
        $legacy = $courseArg ?: $this->buildLegacyCourseFromMoodleArchive($archivePath);

        if (!\defined('FILE_SKIP')) {
            \define('FILE_SKIP', 1);
        }
        if (!\defined('FILE_RENAME')) {
            \define('FILE_RENAME', 2);
        }
        if (!\defined('FILE_OVERWRITE')) {
            \define('FILE_OVERWRITE', 3);
        }
        $filePolicy = \in_array($sameFileNameOption, [1, 2, 3], true) ? $sameFileNameOption : FILE_RENAME;

        /** @var CDocumentRepository $docRepo */
        $docRepo = Container::getDocumentRepository();
        $courseEntity = api_get_course_entity($courseRealId);
        $sessionEntity = api_get_session_entity((int) $sessionId);
        $groupEntity = api_get_group_entity(0);

        if (!$courseEntity) {
            throw new RuntimeException('Destination course entity not found (real_id='.$courseRealId.')');
        }

        $srcRoot = rtrim((string) ($legacy->backup_path ?? ''), '/').'/';
        if (!is_dir($srcRoot)) {
            throw new RuntimeException('Moodle working directory not found: '.$srcRoot);
        }

        $docs = [];
        if (!empty($legacy->resources['document']) && \is_array($legacy->resources['document'])) {
            $docs = $legacy->resources['document'];
        } elseif (!empty($legacy->resources['Document']) && \is_array($legacy->resources['Document'])) {
            $docs = $legacy->resources['Document'];
        }
        if (empty($docs)) {
            if ($this->debug) {
                error_log('MOODLE_IMPORT[restoreDocuments]: no document bucket found');
            }

            return ['documents' => 0, 'folders' => 0];
        }

        $courseInfo = api_get_course_info();
        $courseDir = (string) ($courseInfo['directory'] ?? $courseInfo['code'] ?? '');

        $DBG = function (string $msg, array $ctx = []): void {
            error_log('[MOODLE_IMPORT:RESTORE_DOCS] '.$msg.(empty($ctx) ? '' : ' '.json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
        };

        // Path normalizer: strip moodle-specific top-level segments like t/, moodle_pages/, Documents/
        // NOTE: This is what makes "Documents" behave as root in Chamilo.
        $normalizeMoodleRel = static function (string $rawPath): string {
            $p = ltrim($rawPath, '/');

            // Drop "document/" prefix if present
            if (str_starts_with($p, 'document/')) {
                $p = substr($p, 9);
            }

            // Strip known moodle export prefixes (order matters: most specific first)
            $strip = ['t/', 'moodle_pages/', 'Documents/'];
            foreach ($strip as $pre) {
                if (str_starts_with($p, $pre)) {
                    $p = substr($p, \strlen($pre));
                }
            }

            $p = ltrim($p, '/');

            return '' === $p ? '/' : '/'.$p;
        };

        $isFolderItem = static function (object $item): bool {
            $e = (isset($item->obj) && \is_object($item->obj)) ? $item->obj : $item;
            $ft = strtolower((string) ($e->file_type ?? $e->filetype ?? ''));
            if ('folder' === $ft) {
                return true;
            }
            $p = (string) ($e->path ?? '');

            return '' !== $p && '/' === substr($p, -1);
        };
        $effectiveEntity = static function (object $item): object {
            return (isset($item->obj) && \is_object($item->obj)) ? $item->obj : $item;
        };

        // Ensure folder chain and return destination parent iid
        $ensureFolder = function (string $relPath) use ($docRepo, $courseEntity, $courseInfo, $sessionId, $DBG) {
            $rel = '/'.ltrim($relPath, '/');
            if ('/' === $rel || '' === $rel) {
                return 0;
            }
            $parts = array_values(array_filter(explode('/', trim($rel, '/'))));

            // If first segment is "document", skip it; we are already under the course document root.
            $start = (isset($parts[0]) && 'document' === strtolower($parts[0])) ? 1 : 0;

            $accum = '';
            $parentId = 0;
            for ($i = $start; $i < \count($parts); $i++) {
                $seg = $parts[$i];
                $accum = $accum.'/'.$seg;
                $title = $seg;
                $parent = $parentId ? $docRepo->find($parentId) : $courseEntity;

                $existing = $docRepo->findCourseResourceByTitle(
                    $title,
                    $parent->getResourceNode(),
                    $courseEntity,
                    api_get_session_entity((int) $sessionId),
                    api_get_group_entity(0)
                );

                if ($existing) {
                    $parentId = method_exists($existing, 'getIid') ? (int) $existing->getIid() : 0;

                    continue;
                }

                $entity = DocumentManager::addDocument(
                    ['real_id' => (int) $courseInfo['real_id'], 'code' => (string) $courseInfo['code']],
                    $accum,
                    'folder',
                    0,
                    $title,
                    null,
                    0,
                    null,
                    0,
                    (int) $sessionId,
                    0,
                    false,
                    '',
                    $parentId,
                    ''
                );
                $parentId = method_exists($entity, 'getIid') ? (int) $entity->getIid() : 0;
                $DBG('ensureFolder:create', ['accum' => $accum, 'iid' => $parentId]);
            }

            return $parentId;
        };

        $isHtmlFile = static function (string $filePath, string $nameGuess): bool {
            $ext1 = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $ext2 = strtolower(pathinfo($nameGuess, PATHINFO_EXTENSION));
            if (\in_array($ext1, ['html', 'htm'], true) || \in_array($ext2, ['html', 'htm'], true)) {
                return true;
            }
            $peek = (string) @file_get_contents($filePath, false, null, 0, 2048);
            if ('' === $peek) {
                return false;
            }
            $s = strtolower($peek);
            if (str_contains($s, '<html') || str_contains($s, '<!doctype html')) {
                return true;
            }
            if (\function_exists('finfo_open')) {
                $fi = finfo_open(FILEINFO_MIME_TYPE);
                if ($fi) {
                    $mt = @finfo_buffer($fi, $peek) ?: '';
                    finfo_close($fi);
                    if (str_starts_with($mt, 'text/html')) {
                        return true;
                    }
                }
            }

            return false;
        };

        // Create folders (preserve tree) with normalized paths; track destination iids
        $folders = []; // map: normalized folder rel -> iid
        $nFolders = 0;

        foreach ($docs as $k => $wrap) {
            $e = $effectiveEntity($wrap);
            if (!$isFolderItem($wrap)) {
                continue;
            }

            $rawPath = (string) ($e->path ?? '');
            if ('' === $rawPath) {
                continue;
            }

            // Normalize to avoid 't/', 'moodle_pages/', 'Documents/' phantom roots
            $rel = $normalizeMoodleRel($rawPath);
            if ('/' === $rel) {
                continue;
            }

            $parts = array_values(array_filter(explode('/', trim($rel, '/'))));
            $accum = '';
            $parentId = 0;

            foreach ($parts as $i => $seg) {
                $accum .= '/'.$seg;
                if (isset($folders[$accum])) {
                    $parentId = $folders[$accum];

                    continue;
                }

                $parentRes = $parentId ? $docRepo->find($parentId) : $courseEntity;
                $title = ($i === \count($parts) - 1) ? ((string) ($e->title ?? $seg)) : $seg;

                $existing = $docRepo->findCourseResourceByTitle(
                    $title,
                    $parentRes->getResourceNode(),
                    $courseEntity,
                    $sessionEntity,
                    $groupEntity
                );

                if ($existing) {
                    $iid = method_exists($existing, 'getIid') ? (int) $existing->getIid() : 0;
                    $DBG('folder:reuse', ['title' => $title, 'iid' => $iid]);
                } else {
                    $entity = DocumentManager::addDocument(
                        ['real_id' => (int) $courseInfo['real_id'], 'code' => (string) $courseInfo['code']],
                        $accum,
                        'folder',
                        0,
                        $title,
                        null,
                        0,
                        null,
                        0,
                        (int) $sessionId,
                        0,
                        false,
                        '',
                        $parentId,
                        ''
                    );
                    $iid = method_exists($entity, 'getIid') ? (int) $entity->getIid() : 0;
                    $DBG('folder:create', ['title' => $title, 'iid' => $iid]);
                    $nFolders++;
                }

                $folders[$accum] = $iid;
                $parentId = $iid;
            }

            if (isset($legacy->resources['document'][$k])) {
                $legacy->resources['document'][$k]->destination_id = $parentId;
            }
        }

        // PRE-SCAN: build URL maps for HTML rewriting if helpers exist
        $urlMapByRel = [];
        $urlMapByBase = [];
        foreach ($docs as $k => $wrap) {
            $e = $effectiveEntity($wrap);
            if ($isFolderItem($wrap)) {
                continue;
            }

            $title = (string) ($e->title ?? basename((string) $e->path));
            $src = $srcRoot.(string) $e->path;

            if (!is_file($src) || !is_readable($src)) {
                continue;
            }
            if (!$isHtmlFile($src, $title)) {
                continue;
            }

            $html = (string) @file_get_contents($src);
            if ('' === $html) {
                continue;
            }

            try {
                $maps = ChamiloHelper::buildUrlMapForHtmlFromPackage(
                    $html,
                    $courseDir,
                    $srcRoot,
                    $folders,
                    $ensureFolder,
                    $docRepo,
                    $courseEntity,
                    $sessionEntity,
                    $groupEntity,
                    (int) $sessionId,
                    (int) $filePolicy,
                    $DBG
                );

                foreach ($maps['byRel'] ?? [] as $kRel => $vUrl) {
                    if (!isset($urlMapByRel[$kRel])) {
                        $urlMapByRel[$kRel] = $vUrl;
                    }
                }
                foreach ($maps['byBase'] ?? [] as $kBase => $vUrl) {
                    if (!isset($urlMapByBase[$kBase])) {
                        $urlMapByBase[$kBase] = $vUrl;
                    }
                }
            } catch (Throwable $te) {
                $DBG('html:map:failed', ['err' => $te->getMessage()]);
            }
        }
        $DBG('global.map.stats', ['byRel' => \count($urlMapByRel), 'byBase' => \count($urlMapByBase)]);

        // Import files (HTML rewritten before addDocument; binaries via realPath)
        $nFiles = 0;
        foreach ($docs as $k => $wrap) {
            $e = $effectiveEntity($wrap);
            if ($isFolderItem($wrap)) {
                continue;
            }

            $rawTitle = (string) ($e->title ?? basename((string) $e->path));
            $srcPath = $srcRoot.(string) $e->path;

            if (!is_file($srcPath) || !is_readable($srcPath)) {
                $DBG('file:skip:src-missing', ['src' => $srcPath, 'title' => $rawTitle]);

                continue;
            }

            // Parent folder: from normalized path (this strips "Documents/")
            $rel = $normalizeMoodleRel((string) $e->path);
            $parentRel = rtrim(\dirname($rel), '/');
            $parentId = $folders[$parentRel] ?? 0;
            if (!$parentId) {
                $parentId = $ensureFolder($parentRel);
                $folders[$parentRel] = $parentId;
            }
            $parentRes = $parentId ? $docRepo->find($parentId) : $courseEntity;

            // Handle name collisions based on $filePolicy
            $findExistingIid = function (string $title) use ($docRepo, $parentRes, $courseEntity, $sessionEntity, $groupEntity): ?int {
                $ex = $docRepo->findCourseResourceByTitle(
                    $title,
                    $parentRes->getResourceNode(),
                    $courseEntity,
                    $sessionEntity,
                    $groupEntity
                );

                return $ex && method_exists($ex, 'getIid') ? (int) $ex->getIid() : null;
            };

            $baseTitle = $rawTitle;
            $finalTitle = $baseTitle;

            $existsIid = $findExistingIid($finalTitle);
            if ($existsIid) {
                $DBG('file:collision', ['title' => $finalTitle, 'policy' => $filePolicy]);
                if (FILE_SKIP === $filePolicy) {
                    if (isset($legacy->resources['document'][$k])) {
                        $legacy->resources['document'][$k]->destination_id = $existsIid;
                    }

                    continue;
                }
                if (FILE_RENAME === $filePolicy) {
                    $pi = pathinfo($baseTitle);
                    $name = $pi['filename'] ?? $baseTitle;
                    $ext2 = isset($pi['extension']) && '' !== $pi['extension'] ? '.'.$pi['extension'] : '';
                    $i = 1;
                    while ($findExistingIid($finalTitle)) {
                        $finalTitle = $name.'_'.$i.$ext2;
                        $i++;
                    }
                }
                // FILE_OVERWRITE => let DocumentManager handle it
            }

            // Prepare payload for addDocument
            $isHtml = $isHtmlFile($srcPath, $rawTitle);
            $content = '';
            $realPath = '';

            if ($isHtml) {
                $raw = @file_get_contents($srcPath) ?: '';
                if (\defined('UTF8_CONVERT') && UTF8_CONVERT) {
                    $raw = utf8_encode($raw);
                }
                $DBG('html:rewrite:before', ['title' => $finalTitle, 'maps' => [\count($urlMapByRel), \count($urlMapByBase)]]);

                try {
                    $rew = ChamiloHelper::rewriteLegacyCourseUrlsWithMap(
                        $raw,
                        $courseDir,
                        $urlMapByRel,
                        $urlMapByBase
                    );
                    $content = (string) ($rew['html'] ?? $raw);
                    $DBG('html:rewrite:after', ['replaced' => (int) ($rew['replaced'] ?? 0), 'misses' => (int) ($rew['misses'] ?? 0)]);
                } catch (Throwable $te) {
                    $content = $raw; // fallback to original HTML
                    $DBG('html:rewrite:error', ['err' => $te->getMessage()]);
                }
            } else {
                $realPath = $srcPath; // binary: pass physical path to be streamed into ResourceFile
            }

            try {
                $entity = DocumentManager::addDocument(
                    ['real_id' => (int) $courseInfo['real_id'], 'code' => (string) $courseInfo['code']],
                    $rel,
                    'file',
                    (int) ($e->size ?? 0),
                    $finalTitle,
                    (string) ($e->comment ?? ''),
                    0,
                    null,
                    0,
                    (int) $sessionId,
                    0,
                    false,
                    $content,
                    $parentId,
                    $realPath
                );
                $iid = method_exists($entity, 'getIid') ? (int) $entity->getIid() : 0;

                if (isset($legacy->resources['document'][$k])) {
                    $legacy->resources['document'][$k]->destination_id = $iid;
                }

                $nFiles++;
                $DBG('file:created', ['title' => $finalTitle, 'iid' => $iid, 'html' => $isHtml ? 1 : 0]);
            } catch (Throwable $eX) {
                $DBG('file:create:failed', ['title' => $finalTitle, 'error' => $eX->getMessage()]);
            }
        }

        $DBG('summary', ['files' => $nFiles, 'folders' => $nFolders]);

        return ['documents' => $nFiles, 'folders' => $nFolders];
    }

    /**
     * Read documents from activities/resource + files.xml and populate $resources['document'].
     * NEW behavior:
     * - Treat Moodle's top-level "Documents" folder as the ROOT of /document (do NOT create a "Documents" node).
     * - Preserve any real subfolders beneath "Documents/".
     * - Copies blobs from files/<hash> to the target /document/... path
     * - Adds LP items when section map exists.
     */
    private function readDocuments(
        string $workDir,
        DOMXPath $mb,
        array $fileIndex,
        array &$resources,
        array &$lpMap
    ): void {
        $resources['document'] ??= [];

        // Ensure physical /document dir exists in the working dir (snapshot points there).
        $this->ensureDir($workDir.'/document');

        // Helper: strip an optional leading "/Documents" segment *once*
        $stripDocumentsRoot = static function (string $p): string {
            $p = '/'.ltrim($p, '/');
            if (preg_match('~^/Documents(/|$)~i', $p)) {
                $p = substr($p, \strlen('/Documents'));
                if (false === $p) {
                    $p = '/';
                }
            }

            return '' === $p ? '/' : $p;
        };

        // Small helper: ensure folder chain (legacy snapshot + filesystem) under /document,
        // skipping an initial "Documents" segment if present.
        $ensureFolderChain = function (string $base, string $fp) use (&$resources, $workDir, $stripDocumentsRoot): string {
            // Normalize base and fp
            $base = rtrim($base, '/');               // expected "/document"
            $fp = $this->normalizeSlash($fp ?: '/'); // "/sub/dir/" or "/"
            $fp = $stripDocumentsRoot($fp);

            if ('/' === $fp || '' === $fp) {
                // Just the base /document
                $this->ensureDir($workDir.$base);

                return $base;
            }

            // Split and ensure each segment (both on disk and in legacy snapshot)
            $parts = array_values(array_filter(explode('/', trim($fp, '/'))));
            $accRel = $base;
            foreach ($parts as $seg) {
                $accRel .= '/'.$seg;
                // Create on disk
                $this->ensureDir($workDir.$accRel);
                // Create in legacy snapshot as a folder node (idempotent)
                $this->ensureFolderLegacy($resources['document'], $accRel, $seg);
            }

            return $accRel; // final parent folder rel path (under /document)
        };

        // A) Restore "resource" activities (single-file resources)
        foreach ($mb->query('//activity[modulename="resource"]') as $node) {
            /** @var DOMElement $node */
            $dir = (string) ($node->getElementsByTagName('directory')->item(0)?->nodeValue ?? '');
            if ('' === $dir) {
                continue;
            }

            $resourceXml = $workDir.'/'.$dir.'/resource.xml';
            $inforefXml = $workDir.'/'.$dir.'/inforef.xml';
            if (!is_file($resourceXml) || !is_file($inforefXml)) {
                continue;
            }

            // 1) Read resource name/intro
            [$resName, $resIntro] = $this->readResourceMeta($resourceXml);

            // 2) Resolve referenced file ids
            $fileIds = $this->parseInforefFileIds($inforefXml);
            if (empty($fileIds)) {
                continue;
            }

            foreach ($fileIds as $fid) {
                $f = $fileIndex['byId'][$fid] ?? null;
                if (!$f) {
                    continue;
                }

                // Keep original structure from files.xml under /document (NOT /document/Documents)
                $fp = $this->normalizeSlash($f['filepath'] ?? '/'); // e.g. "/sub/dir/"
                $fp = $stripDocumentsRoot($fp);
                $base = '/document'; // root in Chamilo
                $parentRel = $ensureFolderChain($base, $fp);

                $fileName = ltrim((string) ($f['filename'] ?? ''), '/');
                if ('' === $fileName) {
                    $fileName = 'file_'.$fid;
                }
                $targetRel = rtrim($parentRel, '/').'/'.$fileName;
                $targetAbs = $workDir.$targetRel;

                // Copy binary into working dir
                $this->ensureDir(\dirname($targetAbs));
                $this->safeCopy($f['blob'], $targetAbs);

                // Register in legacy snapshot
                $docId = $this->nextId($resources['document']);
                $resources['document'][$docId] = $this->mkLegacyItem(
                    'document',
                    $docId,
                    [
                        'file_type' => 'file',
                        'path' => $targetRel,
                        'title' => ('' !== $resName ? $resName : (string) $fileName),
                        'comment' => $resIntro,
                        'size' => (string) ($f['filesize'] ?? 0),
                    ]
                );

                // Add to LP of the section, if present (keeps current behavior)
                $sectionId = (int) ($node->getElementsByTagName('sectionid')->item(0)?->nodeValue ?? 0);
                if ($sectionId > 0 && isset($lpMap[$sectionId])) {
                    $resourcesDocTitle = $resources['document'][$docId]->title ?? (string) $fileName;
                    $lpMap[$sectionId]['items'][] = [
                        'item_type' => 'document',
                        'ref' => $docId,
                        'title' => $resourcesDocTitle,
                    ];
                }
            }
        }

        // B) Restore files that belong to mod_folder activities.
        foreach ($fileIndex['byId'] as $f) {
            if (($f['component'] ?? '') !== 'mod_folder') {
                continue;
            }

            // Keep inner structure from files.xml under /document; strip leading "Documents/"
            $fp = $this->normalizeSlash($f['filepath'] ?? '/'); // e.g. "/unit1/slide/"
            $fp = $stripDocumentsRoot($fp);
            $base = '/document';

            // Ensure folder chain exists on disk and in legacy map; get parent rel
            $parentRel = $ensureFolderChain($base, $fp);

            // Final rel path for the file
            $fileName = ltrim((string) ($f['filename'] ?? ''), '/');
            if ('' === $fileName) {
                // Defensive: generate name if missing (rare, but keeps import resilient)
                $fileName = 'file_'.$this->nextId($resources['document']);
            }
            $rel = rtrim($parentRel, '/').'/'.$fileName;

            // Copy to working dir
            $abs = $workDir.$rel;
            $this->ensureDir(\dirname($abs));
            $this->safeCopy($f['blob'], $abs);

            // Register the file in legacy snapshot (folder nodes were created by ensureFolderChain)
            $docId = $this->nextId($resources['document']);
            $resources['document'][$docId] = $this->mkLegacyItem(
                'document',
                $docId,
                [
                    'file_type' => 'file',
                    'path' => $rel,
                    'title' => (string) ($fileName ?: 'file '.$docId),
                    'size' => (string) ($f['filesize'] ?? 0),
                    'comment' => '',
                ]
            );
        }
    }

    /**
     * Extract resource name and intro from activities/resource/resource.xml.
     */
    private function readResourceMeta(string $resourceXml): array
    {
        $doc = $this->loadXml($resourceXml);
        $xp = new DOMXPath($doc);
        $name = (string) ($xp->query('//resource/name')->item(0)?->nodeValue ?? '');
        $intro = (string) ($xp->query('//resource/intro')->item(0)?->nodeValue ?? '');

        return [$name, $intro];
    }

    /**
     * Parse file ids referenced by inforef.xml (<inforef><fileref><file><id>..</id>).
     */
    private function parseInforefFileIds(string $inforefXml): array
    {
        $doc = $this->loadXml($inforefXml);
        $xp = new DOMXPath($doc);
        $ids = [];
        foreach ($xp->query('//inforef/fileref/file/id') as $n) {
            $v = (int) ($n->nodeValue ?? 0);
            if ($v > 0) {
                $ids[] = $v;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Create (if missing) a legacy folder entry at $folderPath in $bucket and return its id.
     */
    private function ensureFolderLegacy(array &$bucket, string $folderPath, string $title): int
    {
        foreach ($bucket as $k => $it) {
            if (($it->file_type ?? '') === 'folder' && (($it->path ?? '') === $folderPath)) {
                return (int) $k;
            }
        }
        $id = $this->nextId($bucket);
        $bucket[$id] = $this->mkLegacyItem('document', $id, [
            'file_type' => 'folder',
            'path' => $folderPath,
            'title' => $title,
            'size' => '0',
        ]);

        return $id;
    }

    /**
     * Copy a file if present (tolerant if blob is missing).
     */
    private function safeCopy(string $src, string $dst): void
    {
        if (!is_file($src)) {
            if ($this->debug) {
                error_log('MOODLE_IMPORT: blob not found: '.$src);
            }

            return;
        }
        if (!is_file($dst)) {
            @copy($src, $dst);
        }
    }

    /**
     * Normalize a path to have single slashes and end with a slash.
     */
    private function normalizeSlash(string $p): string
    {
        if ('' === $p || '.' === $p) {
            return '/';
        }
        $p = preg_replace('#/+#', '/', $p);

        return rtrim($p, '/').'/';
    }

    /**
     * Igual que en CourseBuilder: crea la “caja” legacy (obj, type, source_id, destination_id, etc.).
     */
    private function mkLegacyItem(string $type, int $sourceId, array|object $obj, array $arrayKeysToPromote = []): stdClass
    {
        $o = new stdClass();
        $o->type = $type;
        $o->source_id = $sourceId;
        $o->destination_id = null;
        $o->has_obj = true;
        $o->obj = (object) $obj;

        if (!isset($o->obj->iid)) {
            $o->obj->iid = $sourceId;
        }
        if (!isset($o->id)) {
            $o->id = $sourceId;
        }
        if (!isset($o->obj->id)) {
            $o->obj->id = $sourceId;
        }

        // Promote scalars to top-level (like the builder)
        foreach ((array) $obj as $k => $v) {
            if (\is_scalar($v) || null === $v) {
                if (!property_exists($o, $k)) {
                    $o->{$k} = $v;
                }
            }
        }
        // Promote array keys (e.g., items, linked_resources in learnpath)
        foreach ($arrayKeysToPromote as $k) {
            if (isset($obj[$k]) && \is_array($obj[$k])) {
                $o->{$k} = $obj[$k];
            }
        }

        // Special adjustments for documents
        if ('document' === $type) {
            $o->path = (string) ($o->path ?? $o->full_path ?? $o->obj->path ?? $o->obj->full_path ?? '');
            $o->full_path = (string) ($o->full_path ?? $o->path ?? $o->obj->full_path ?? $o->obj->path ?? '');
            $o->file_type = (string) ($o->file_type ?? $o->filetype ?? $o->obj->file_type ?? $o->obj->filetype ?? '');
            $o->filetype = (string) ($o->filetype ?? $o->file_type ?? $o->obj->filetype ?? $o->obj->file_type ?? '');
            $o->title = (string) ($o->title ?? $o->obj->title ?? '');
            if (!isset($o->name) || '' === $o->name || null === $o->name) {
                $o->name = '' !== $o->title ? $o->title : ('document '.$sourceId);
            }
        }

        // Default name if missing
        if (!isset($o->name) || '' === $o->name || null === $o->name) {
            if (isset($obj['name']) && '' !== $obj['name']) {
                $o->name = (string) $obj['name'];
            } elseif (isset($obj['title']) && '' !== $obj['title']) {
                $o->name = (string) $obj['title'];
            } else {
                $o->name = $type.' '.$sourceId;
            }
        }

        return $o;
    }
}
