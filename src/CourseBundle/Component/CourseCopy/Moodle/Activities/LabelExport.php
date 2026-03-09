<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\FileExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleExport;
use Chamilo\CourseBundle\Entity\CDocument;
use DocumentManager;
use Symfony\Component\Uid\Uuid;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const PHP_EOL;

/**
 * LabelExport exports legacy course descriptions as Moodle label activities.
 */
class LabelExport extends ActivityExport
{
    /**
     * Export this label activity.
     */
    public function export(int $activityId, string $exportDir, int $moduleId, int $sectionId): void
    {
        $labelDir = $this->prepareActivityDirectory($exportDir, 'label', $moduleId);

        $data = $this->getData($activityId, $sectionId, $moduleId);
        if (null === $data) {
            return;
        }

        $this->createLabelXml($data, $labelDir);
        $this->createModuleXml($data, $labelDir);
        $this->createInforefXml($data, $labelDir);
        $this->createFiltersXml($data, $labelDir);
        $this->createGradesXml($data, $labelDir);
        $this->createGradeHistoryXml($data, $labelDir);
        $this->createCompletionXml($data, $labelDir);
        $this->createCommentsXml($data, $labelDir);
        $this->createCompetenciesXml($data, $labelDir);
        $this->createRolesXml($data, $labelDir);
        $this->createCalendarXml($data, $labelDir);
    }

    /**
     * Build label payload from legacy course_description resources.
     *
     * @return array<string,mixed>|null
     */
    public function getData(int $labelId, int $sectionId, ?int $moduleId = null): ?array
    {
        $bag =
            $this->course->resources[\defined('RESOURCE_COURSEDESCRIPTION') ? RESOURCE_COURSEDESCRIPTION : 'course_description']
            ?? $this->course->resources['course_description']
            ?? [];

        if (empty($bag) || !\is_array($bag)) {
            return null;
        }

        $wrap = $bag[$labelId] ?? null;
        if (!$wrap || !\is_object($wrap)) {
            return null;
        }

        $desc = (isset($wrap->obj) && \is_object($wrap->obj)) ? $wrap->obj : $wrap;

        $sourceId = (int) ($desc->source_id ?? $labelId);
        $effectiveModuleId = (int) ($moduleId ?? $sourceId);
        if ($effectiveModuleId <= 0) {
            $effectiveModuleId = $sourceId;
        }

        $title = $this->sanitizeMoodleActivityName($this->resolveTitle($desc), 255);
        $introRaw = (string) ($desc->content ?? '');
        $introResult = $this->extractEmbeddedFilesAndNormalizeContent($introRaw, $effectiveModuleId, $effectiveModuleId);

        return [
            'id' => $sourceId,
            'moduleid' => $effectiveModuleId,
            'modulename' => 'label',
            'contextid' => $effectiveModuleId,
            'sectionid' => $sectionId,
            'sectionnumber' => $sectionId,
            'name' => '' !== $title ? $title : 'Description',
            'intro' => $introResult['content'],
            'introformat' => 1,
            'timemodified' => time(),
            'users' => [],
            'files' => $introResult['files'],
        ];
    }

    /**
     * Title resolver with fallback by description type.
     */
    private function resolveTitle(object $desc): string
    {
        $title = trim((string) ($desc->title ?? ''));
        if ('' !== $title) {
            return $title;
        }

        $map = [
            1 => 'Description',
            2 => 'Objectives',
            3 => 'Topics',
        ];

        return $map[(int) ($desc->description_type ?? 0)] ?? 'Description';
    }

    /**
     * Extract embedded document files and rewrite HTML to Moodle pluginfile URLs.
     *
     * @return array{content:string, files:array<int,array<string,mixed>>}
     */
    private function extractEmbeddedFilesAndNormalizeContent(string $html, int $contextId, int $moduleId): array
    {
        if ('' === $html) {
            return [
                'content' => '',
                'files' => [],
            ];
        }

        $adminId = (int) (MoodleExport::getAdminUserData()['id'] ?? 1);
        $fileExport = new FileExport($this->course);

        $files = [];
        $seenDocIds = [];
        $sequence = 0;

        $urls = $this->extractDocumentReferenceUrlsFromHtml($html);
        foreach ($urls as $src) {
            $document = $this->resolveEmbeddedDocumentData($src);
            if (null === $document) {
                continue;
            }

            $docId = (int) ($document['id'] ?? 0);
            if ($docId <= 0 || isset($seenDocIds[$docId])) {
                continue;
            }

            $documentPath = (string) ($document['path'] ?? '');
            if ('' === $documentPath) {
                continue;
            }

            $absolutePath = $document['abs_path'] ?? $this->resolveDocumentAbsolutePath($docId, $documentPath);
            $filename = basename($documentPath);

            $sequence++;
            $fileId = $this->buildLabelFileId($moduleId, $contextId, $sequence);

            $contenthash = hash('sha1', $filename);
            if (is_file((string) $absolutePath)) {
                $contenthash = sha1_file((string) $absolutePath);
            }

            $files[] = [
                'id' => $fileId,
                'contenthash' => $contenthash,
                'contextid' => $contextId,
                'component' => 'mod_label',
                'filearea' => 'intro',
                'itemid' => 0,
                'filepath' => $this->buildPluginFileDirectoryFromChamiloDocumentPath($documentPath),
                'documentpath' => 'document/'.ltrim($documentPath, '/'),
                'filename' => $filename,
                'userid' => $adminId,
                'filesize' => (int) ($document['size'] ?? 0),
                'mimetype' => $fileExport->getMimeType($documentPath),
                'status' => 0,
                'timecreated' => time() - 3600,
                'timemodified' => time(),
                'source' => (string) ($document['title'] ?? $filename),
                'author' => 'Unknown',
                'license' => 'allrightsreserved',
                'abs_path' => $absolutePath,
            ];

            $seenDocIds[$docId] = true;
        }

        return [
            'content' => $this->normalizeContent($html),
            'files' => $files,
        ];
    }

    /**
     * Build a unique files.xml id for embedded label files.
     */
    private function buildLabelFileId(int $moduleId, int $contextId, int $sequence): int
    {
        return 1160000000 + max(0, $moduleId) + max(0, $contextId) + max(1, $sequence);
    }

    /**
     * Build the pluginfile directory path from a Chamilo document path.
     */
    private function buildPluginFileDirectoryFromChamiloDocumentPath(string $documentPath): string
    {
        $relative = $this->stripChamiloDocumentPrefix($documentPath);
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        $dir = dirname($relative);
        if ('.' === $dir || '/' === $dir) {
            return '/Documents/';
        }

        return '/Documents/'.trim($dir, '/').'/';
    }

    /**
     * Build the pluginfile full path used in HTML content.
     */
    private function buildPluginFilePathFromChamiloDocumentPath(string $documentPath): string
    {
        $relative = $this->stripChamiloDocumentPrefix($documentPath);
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        return '/Documents/'.$relative;
    }

    /**
     * Remove the internal Chamilo document prefix from a path.
     */
    private function stripChamiloDocumentPrefix(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        return (string) preg_replace('#^(?:document/?)+#i', '', $path);
    }

    /**
     * Extract all candidate document URLs from HTML attributes and inline CSS.
     *
     * @return array<int,string>
     */
    private function extractDocumentReferenceUrlsFromHtml(string $html): array
    {
        if ('' === $html) {
            return [];
        }

        $urls = [];

        if (preg_match_all("~\\b(?:src|href|poster|data)\\s*=\\s*([\"'])([^\"']+)\\1~i", $html, $matches)) {
            foreach ($matches[2] as $url) {
                $url = trim((string) $url);
                if ('' !== $url) {
                    $urls[] = $url;
                }
            }
        }

        if (preg_match_all("~\\bsrcset\\s*=\\s*([\"'])(.*?)\\1~is", $html, $matches)) {
            foreach ($matches[2] as $srcset) {
                foreach (array_map('trim', explode(',', (string) $srcset)) as $candidate) {
                    if ('' === $candidate) {
                        continue;
                    }

                    $tokens = preg_split('/\s+/', $candidate, -1, PREG_SPLIT_NO_EMPTY);
                    $url = $tokens[0] ?? '';
                    if ('' !== $url) {
                        $urls[] = $url;
                    }
                }
            }
        }

        if (preg_match_all("~url\\(([\"']?)([^)'\"]+)\\1\\)~i", $html, $matches)) {
            foreach ($matches[2] as $url) {
                $url = trim((string) $url);
                if ('' !== $url) {
                    $urls[] = $url;
                }
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * Normalize HTML content by rewriting course document URLs to @@PLUGINFILE@@ tokens.
     */
    private function normalizeContent(string $html): string
    {
        if ('' === $html) {
            return $html;
        }

        $html = (string) preg_replace_callback(
            '~\bsrcset\s*=\s*([\'"])(.*?)\1~is',
            function (array $m): string {
                $q = $m[1];
                $val = $m[2];

                $parts = array_map('trim', explode(',', $val));
                foreach ($parts as &$part) {
                    if ('' === $part) {
                        continue;
                    }

                    $tokens = preg_split('/\s+/', $part, -1, PREG_SPLIT_NO_EMPTY);
                    if (empty($tokens)) {
                        continue;
                    }

                    $tokens[0] = $this->rewriteDocUrl($tokens[0]);
                    $part = implode(' ', $tokens);
                }

                return 'srcset='.$q.implode(', ', $parts).$q;
            },
            $html
        );

        $html = (string) preg_replace_callback(
            '~\b(src|href|poster|data)\s*=\s*([\'"])([^\'"]+)\2~i',
            function (array $m): string {
                return $m[1].'='.$m[2].$this->rewriteDocUrl($m[3]).$m[2];
            },
            $html
        );

        $html = (string) preg_replace_callback(
            '~\bstyle\s*=\s*([\'"])(.*?)\1~is',
            function (array $m): string {
                $q = $m[1];
                $style = $m[2];

                $style = (string) preg_replace_callback(
                    '~url\((["\']?)([^)\'"]+)\1\)~i',
                    function (array $mm): string {
                        return 'url('.$mm[1].$this->rewriteDocUrl($mm[2]).$mm[1].')';
                    },
                    $style
                );

                return 'style='.$q.$style.$q;
            },
            $html
        );

        return (string) preg_replace_callback(
            '~(<style\b[^>]*>)(.*?)(</style>)~is',
            function (array $m): string {
                $open = $m[1];
                $css = $m[2];
                $close = $m[3];

                $css = (string) preg_replace_callback(
                    '~url\((["\']?)([^)\'"]+)\1\)~i',
                    function (array $mm): string {
                        return 'url('.$mm[1].$this->rewriteDocUrl($mm[2]).$mm[1].')';
                    },
                    $css
                );

                return $open.$css.$close;
            },
            $html
        );
    }

    /**
     * Rewrite course document URLs to @@PLUGINFILE@@/Documents/... .
     */
    private function rewriteDocUrl(string $url): string
    {
        if ('' === $url || str_contains($url, '@@PLUGINFILE@@')) {
            return $url;
        }

        $documentPath = $this->resolveChamiloDocumentPathFromUrl($url);
        if (null === $documentPath || '' === trim($documentPath)) {
            return $url;
        }

        return '@@PLUGINFILE@@'.$this->buildPluginFilePathFromChamiloDocumentPath($documentPath);
    }

    /**
     * Resolve a Chamilo logical document path from legacy or modern resource URLs.
     */
    private function resolveChamiloDocumentPathFromUrl(string $url): ?string
    {
        $url = trim($url);
        if ('' === $url) {
            return null;
        }

        $uuid = $this->extractResourceUuidFromUrl($url);
        if (null !== $uuid) {
            $doc = $this->findDocumentByResourceUuid($uuid);
            if ($doc instanceof CDocument) {
                $courseCode = (string) ($this->course->code ?? $this->course->info['code'] ?? '');
                if ('' !== $courseCode) {
                    $docData = DocumentManager::get_document_data_by_id((int) $doc->getIid(), $courseCode);
                    if (\is_array($docData) && !empty($docData['path'])) {
                        return '/'.ltrim((string) $docData['path'], '/');
                    }
                }

                $resourceNode = $doc->getResourceNode();
                if ($resourceNode instanceof ResourceNode) {
                    try {
                        $rawPath = (string) ($resourceNode->getPath() ?? '');
                        if ('' !== $rawPath) {
                            $displayPath = (string) $resourceNode->convertPathForDisplay($rawPath);
                            $displayPath = preg_replace('~^/?Documents/?~i', '', $displayPath) ?? $displayPath;
                            $displayPath = trim($displayPath, '/');
                            if ('' !== $displayPath) {
                                return '/'.$displayPath;
                            }
                        }
                    } catch (\Throwable) {
                        // Ignore and continue with URL parsing fallback.
                    }
                }
            }
        }

        $decoded = urldecode($url);
        $path = (string) (parse_url($decoded, PHP_URL_PATH) ?? '');
        if ('' === $path) {
            $path = $decoded;
        }

        if (preg_match('#/(?:courses/[^/]+/)?document(?P<docpath>/[^?\'" )]+)#i', $path, $m)) {
            return '/'.ltrim((string) $m['docpath'], '/');
        }

        if (preg_match('#^/?document(?P<docpath>/[^?\'" )]+)$#i', $path, $m)) {
            return '/'.ltrim((string) $m['docpath'], '/');
        }

        return null;
    }

    /**
     * Resolve embedded document data from either a legacy document URL
     * or a modern /r/.../{uuid}/view URL.
     *
     * @return array<string,mixed>|null
     */
    private function resolveEmbeddedDocumentData(string $url): ?array
    {
        $courseCode = (string) ($this->course->code ?? $this->course->info['code'] ?? '');
        if ('' === $courseCode) {
            return null;
        }

        $uuid = $this->extractResourceUuidFromUrl($url);
        if (null !== $uuid) {
            $doc = $this->findDocumentByResourceUuid($uuid);
            if ($doc instanceof CDocument) {
                $documentPath = null;

                $docData = DocumentManager::get_document_data_by_id((int) $doc->getIid(), $courseCode);
                if (\is_array($docData) && !empty($docData['path'])) {
                    $documentPath = (string) $docData['path'];
                }

                if ((null === $documentPath || '' === trim($documentPath)) && $doc->getResourceNode()) {
                    try {
                        $resourceNode = $doc->getResourceNode();
                        $rawPath = (string) ($resourceNode?->getPath() ?? '');
                        if ('' !== $rawPath) {
                            $displayPath = (string) $resourceNode->convertPathForDisplay($rawPath);
                            $displayPath = preg_replace('~^/?Documents/?~i', '', $displayPath) ?? $displayPath;
                            $displayPath = trim($displayPath, '/');
                            if ('' !== $displayPath) {
                                $documentPath = '/'.$displayPath;
                            }
                        }
                    } catch (\Throwable) {
                        // Ignore and continue with fallback logic.
                    }
                }

                if (null !== $documentPath && '' !== trim($documentPath)) {
                    $absolutePath = $this->resolveDocumentAbsolutePath((int) $doc->getIid(), $documentPath);
                    $size = 0;
                    $node = $doc->getResourceNode();
                    if ($node) {
                        $files = $node->getResourceFiles();
                        if ($files && $files->count() > 0) {
                            $first = $files->first();
                            if ($first instanceof ResourceFile) {
                                $size = (int) $first->getSize();
                            }
                        }
                    }

                    return [
                        'id' => (int) $doc->getIid(),
                        'path' => $documentPath,
                        'title' => method_exists($doc, 'getTitle') ? (string) $doc->getTitle() : basename($documentPath),
                        'size' => $size,
                        'abs_path' => $absolutePath,
                    ];
                }
            }
        }

        $documentPath = $this->resolveChamiloDocumentPathFromUrl($url);
        if (null === $documentPath || '' === trim($documentPath)) {
            return null;
        }

        $courseInfo = api_get_course_info($courseCode);
        if (empty($courseInfo)) {
            return null;
        }

        $docId = DocumentManager::get_document_id($courseInfo, $documentPath);
        if (empty($docId)) {
            $docId = DocumentManager::get_document_id($courseInfo, ltrim($documentPath, '/'));
        }

        if (empty($docId)) {
            return null;
        }

        $document = DocumentManager::get_document_data_by_id((int) $docId, $courseCode);
        if (empty($document) || empty($document['path'])) {
            return null;
        }

        $document['abs_path'] = $this->resolveDocumentAbsolutePath((int) $docId, (string) $document['path']);

        return $document;
    }

    /**
     * Resolve the absolute document path.
     */
    private function resolveDocumentAbsolutePath(int $documentId, string $documentPath): ?string
    {
        if ($documentId > 0 && class_exists(Container::class)) {
            try {
                $repo = Container::getDocumentRepository();
                $doc = $repo->findOneBy(['iid' => $documentId]);
                if ($doc instanceof CDocument) {
                    $absPath = $repo->getAbsolutePathForDocument($doc);
                    if (is_file((string) $absPath)) {
                        return (string) $absPath;
                    }
                }
            } catch (\Throwable) {
                // Ignore and continue with fallback.
            }
        }

        $fallback = rtrim((string) $this->course->path, '/').'/document/'.ltrim($documentPath, '/');

        return is_file($fallback) ? $fallback : null;
    }

    /**
     * Extract a ResourceNode UUID from a modern resource URL.
     */
    private function extractResourceUuidFromUrl(string $url): ?string
    {
        if ('' === $url) {
            return null;
        }

        $decoded = urldecode($url);
        $path = (string) (parse_url($decoded, PHP_URL_PATH) ?? '');
        if ('' === $path) {
            $path = $decoded;
        }

        $path = ltrim($path, '/');

        if (preg_match('#^r/[^/]+/[^/]+/(?P<uuid>[A-Za-z0-9-]{16,64})/(?:view|download|link)/?$#i', $path, $matches)) {
            return (string) $matches['uuid'];
        }

        return null;
    }

    /**
     * Resolve a CDocument from a ResourceNode UUID.
     */
    private function findDocumentByResourceUuid(string $uuid): ?CDocument
    {
        if ('' === trim($uuid) || !class_exists(Container::class) || null === Container::$container) {
            return null;
        }

        try {
            /** @var ResourceNodeRepository $resourceNodeRepo */
            $resourceNodeRepo = Container::$container->get(ResourceNodeRepository::class);

            $resourceNode = $resourceNodeRepo->findOneBy(['uuid' => $uuid]);
            if (null === $resourceNode && class_exists(Uuid::class)) {
                try {
                    $resourceNode = $resourceNodeRepo->findOneBy(['uuid' => Uuid::fromString($uuid)]);
                } catch (\Throwable) {
                    $resourceNode = null;
                }
            }

            if (null === $resourceNode) {
                return null;
            }

            $docRepo = Container::getDocumentRepository();
            $doc = $docRepo->findOneBy(['resourceNode' => $resourceNode]);

            return $doc instanceof CDocument ? $doc : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Write label.xml for the activity.
     *
     * @param array<string,mixed> $data
     */
    private function createLabelXml(array $data, string $dir): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<activity id="'.(int) $data['id'].'" moduleid="'.(int) $data['moduleid'].'" modulename="label" contextid="'.(int) ($data['contextid'] ?? $data['moduleid']).'">'.PHP_EOL;
        $xml .= '  <label id="'.(int) $data['id'].'">'.PHP_EOL;
        $xml .= '    <name>'.htmlspecialchars((string) $data['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</name>'.PHP_EOL;
        $xml .= '    <intro><![CDATA['.$data['intro'].']]></intro>'.PHP_EOL;
        $xml .= '    <introformat>'.(int) ($data['introformat'] ?? 1).'</introformat>'.PHP_EOL;
        $xml .= '    <timemodified>'.(int) $data['timemodified'].'</timemodified>'.PHP_EOL;
        $xml .= '  </label>'.PHP_EOL;
        $xml .= '</activity>';

        $this->createXmlFile('label', $xml, $dir);
    }
}
