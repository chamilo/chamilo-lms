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
 * PageExport exports Chamilo intro HTML and root HTML documents as Moodle page activities.
 */
class PageExport extends ActivityExport
{
    public function export(int $activityId, string $exportDir, int $moduleId, int $sectionId): void
    {
        $pageDir = $this->prepareActivityDirectory($exportDir, 'page', $moduleId);
        $pageData = $this->getData($activityId, $sectionId, $moduleId);
        if (null === $pageData) {
            return;
        }

        $this->createPageXml($pageData, $pageDir);
        $this->createModuleXml($pageData, $pageDir);
        $this->createGradesXml($pageData, $pageDir);
        $this->createFiltersXml($pageData, $pageDir);
        $this->createGradeHistoryXml($pageData, $pageDir);
        $this->createInforefXml($pageData, $pageDir);
        $this->createRolesXml($pageData, $pageDir);
        $this->createCommentsXml($pageData, $pageDir);
        $this->createCalendarXml($pageData, $pageDir);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getData(int $pageId, int $sectionId, ?int $moduleId = null): ?array
    {
        if (0 === $pageId) {
            $introBucket =
                $this->course->resources[\defined('RESOURCE_TOOL_INTRO') ? RESOURCE_TOOL_INTRO : 'tool_intro']
                ?? $this->course->resources['tool_intro']
                ?? [];

            $introText = trim((string) ($introBucket['course_homepage']->intro_text ?? ''));
            if ('' !== $introText) {
                $effectiveModuleId = (int) ($moduleId ?? self::INTRO_PAGE_MODULE_ID);
                if ($effectiveModuleId <= 0) {
                    $effectiveModuleId = self::INTRO_PAGE_MODULE_ID;
                }

                $result = $this->extractEmbeddedFilesAndNormalizeContent(
                    $introText,
                    $effectiveModuleId,
                    $effectiveModuleId,
                    true,
                    ''
                );

                return [
                    'id' => 0,
                    'moduleid' => $effectiveModuleId,
                    'modulename' => 'page',
                    'contextid' => $effectiveModuleId,
                    'name' => get_lang('Introduction'),
                    'intro' => '',
                    'content' => $result['content'],
                    'sectionid' => $sectionId,
                    'sectionnumber' => max(0, $sectionId),
                    'display' => 5,
                    'timemodified' => time(),
                    'users' => [],
                    'files' => $result['files'],
                ];
            }
        }

        $pageResources =
            $this->course->resources[\defined('RESOURCE_DOCUMENT') ? RESOURCE_DOCUMENT : 'document']
            ?? $this->course->resources['document']
            ?? [];

        foreach ($pageResources as $page) {
            if (!\is_object($page)) {
                continue;
            }

            $payload = (isset($page->obj) && \is_object($page->obj)) ? $page->obj : $page;
            if (!\is_object($payload)) {
                continue;
            }

            $sourceId = (int) ($payload->source_id ?? $payload->id ?? 0);
            if ($sourceId !== $pageId) {
                continue;
            }

            $effectiveModuleId = (int) ($moduleId ?? $sourceId);
            if ($effectiveModuleId <= 0) {
                $effectiveModuleId = $sourceId;
            }

            $pageName = (string) ($payload->title ?? ('Page '.$pageId));
            if ($sectionId > 0) {
                $pageName = $this->lpItemTitle(
                    $sectionId,
                    \defined('RESOURCE_DOCUMENT') ? (string) RESOURCE_DOCUMENT : 'document',
                    $pageId,
                    $pageName
                );
            }
            $pageName = $this->sanitizeMoodleActivityName($pageName, 255);

            $rawContent = $this->getPageContent($payload, $pageId);
            $result = $this->extractEmbeddedFilesAndNormalizeContent(
                $rawContent,
                $effectiveModuleId,
                $effectiveModuleId,
                false,
                (string) ($payload->path ?? '')
            );

            return [
                'id' => $sourceId,
                'moduleid' => $effectiveModuleId,
                'modulename' => 'page',
                'contextid' => $effectiveModuleId,
                'name' => '' !== $pageName ? $pageName : ('Page '.$pageId),
                'intro' => (string) ($payload->comment ?? ''),
                'content' => $result['content'],
                'sectionid' => $sectionId,
                'sectionnumber' => max(0, $sectionId),
                'display' => 5,
                'timemodified' => time(),
                'users' => [],
                'files' => $result['files'],
            ];
        }

        return null;
    }

    /**
     * @param array<string,mixed> $references
     */
    protected function createInforefXml(array $references, string $directory): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<inforef>'.PHP_EOL;

        if (!empty($references['files']) && \is_array($references['files'])) {
            $xmlContent .= '  <fileref>'.PHP_EOL;
            foreach ($references['files'] as $file) {
                $fileId = \is_array($file) ? (int) ($file['id'] ?? 0) : (int) $file;
                if ($fileId <= 0) {
                    continue;
                }
                $xmlContent .= '    <file>'.PHP_EOL;
                $xmlContent .= '      <id>'.$fileId.'</id>'.PHP_EOL;
                $xmlContent .= '    </file>'.PHP_EOL;
            }
            $xmlContent .= '  </fileref>'.PHP_EOL;
        }

        $xmlContent .= '</inforef>'.PHP_EOL;
        $this->createXmlFile('inforef', $xmlContent, $directory);
    }

    /**
     * @param array<string,mixed> $pageData
     */
    private function createPageXml(array $pageData, string $dir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity id="'.(int) $pageData['id'].'" moduleid="'.(int) $pageData['moduleid'].'" modulename="page" contextid="'.(int) ($pageData['contextid'] ?? $pageData['moduleid']).'">'.PHP_EOL;
        $xmlContent .= '  <page id="'.(int) $pageData['id'].'">'.PHP_EOL;
        $xmlContent .= '    <name>'.htmlspecialchars((string) $pageData['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</name>'.PHP_EOL;
        $xmlContent .= '    <intro>'.htmlspecialchars((string) ($pageData['intro'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</intro>'.PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>'.PHP_EOL;
        $xmlContent .= '    <content>'.htmlspecialchars((string) ($pageData['content'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</content>'.PHP_EOL;
        $xmlContent .= '    <contentformat>1</contentformat>'.PHP_EOL;
        $xmlContent .= '    <legacyfiles>0</legacyfiles>'.PHP_EOL;
        $xmlContent .= '    <display>'.(int) ($pageData['display'] ?? 5).'</display>'.PHP_EOL;
        $xmlContent .= '    <displayoptions>a:3:{s:12:"printheading";s:1:"1";s:10:"printintro";s:1:"0";s:17:"printlastmodified";s:1:"1";}</displayoptions>'.PHP_EOL;
        $xmlContent .= '    <revision>1</revision>'.PHP_EOL;
        $xmlContent .= '    <timemodified>'.(int) ($pageData['timemodified'] ?? time()).'</timemodified>'.PHP_EOL;
        $xmlContent .= '  </page>'.PHP_EOL;
        $xmlContent .= '</activity>';

        $this->createXmlFile('page', $xmlContent, $dir);
    }

    /**
     * @return array{content:string, files:array<int,array<string,mixed>>}
     */
    private function extractEmbeddedFilesAndNormalizeContent(
        string $html,
        int $contextId,
        int $moduleId,
        bool $isIntro,
        string $documentPath
    ): array {
        if ('' === $html) {
            return ['content' => '', 'files' => []];
        }

        $adminId = (int) (MoodleExport::getAdminUserData()['id'] ?? 1);
        $fileExport = new FileExport($this->course);
        $files = [];
        $seenDocIds = [];
        $sequence = 0;

        foreach ($this->extractDocumentReferenceUrlsFromHtml($html) as $src) {
            $document = $this->resolveEmbeddedDocumentData($src);
            if (null === $document) {
                continue;
            }

            $docId = (int) ($document['id'] ?? 0);
            if ($docId <= 0 || isset($seenDocIds[$docId])) {
                continue;
            }

            $resolvedPath = (string) ($document['path'] ?? '');
            if ('' === $resolvedPath) {
                continue;
            }

            $sequence++;
            $filename = basename($resolvedPath);
            $absolutePath = $document['abs_path'] ?? $this->resolveDocumentAbsolutePath($docId, $resolvedPath);

            $files[] = [
                'id' => 1180000000 + max(0, $moduleId) + $sequence,
                'contenthash' => is_file((string) $absolutePath) ? sha1_file((string) $absolutePath) : hash('sha1', $filename),
                'contextid' => $contextId,
                'component' => 'mod_page',
                'filearea' => 'content',
                'itemid' => 0,
                'filepath' => $this->buildPluginFileDirectoryFromChamiloDocumentPath($resolvedPath),
                'documentpath' => 'document/'.ltrim($resolvedPath, '/'),
                'filename' => $filename,
                'userid' => $adminId,
                'filesize' => (int) ($document['size'] ?? 0),
                'mimetype' => $fileExport->getMimeType($resolvedPath),
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

        if (!$isIntro && '' !== trim($documentPath)) {
            $doc = $this->resolveEmbeddedDocumentData('/document/'.ltrim($documentPath, '/'));
            if (null !== $doc) {
                $docId = (int) ($doc['id'] ?? 0);
                if ($docId > 0 && !isset($seenDocIds[$docId])) {
                    $resolvedPath = (string) ($doc['path'] ?? '');
                    if ('' !== $resolvedPath) {
                        $sequence++;
                        $filename = basename($resolvedPath);
                        $absolutePath = $doc['abs_path'] ?? $this->resolveDocumentAbsolutePath($docId, $resolvedPath);
                        $files[] = [
                            'id' => 1180000000 + max(0, $moduleId) + $sequence,
                            'contenthash' => is_file((string) $absolutePath) ? sha1_file((string) $absolutePath) : hash('sha1', $filename),
                            'contextid' => $contextId,
                            'component' => 'mod_page',
                            'filearea' => 'content',
                            'itemid' => 0,
                            'filepath' => $this->buildPluginFileDirectoryFromChamiloDocumentPath($resolvedPath),
                            'documentpath' => 'document/'.ltrim($resolvedPath, '/'),
                            'filename' => $filename,
                            'userid' => $adminId,
                            'filesize' => (int) ($doc['size'] ?? 0),
                            'mimetype' => $fileExport->getMimeType($resolvedPath),
                            'status' => 0,
                            'timecreated' => time() - 3600,
                            'timemodified' => time(),
                            'source' => (string) ($doc['title'] ?? $filename),
                            'author' => 'Unknown',
                            'license' => 'allrightsreserved',
                            'abs_path' => $absolutePath,
                        ];
                    }
                }
            }
        }

        return [
            'content' => $this->normalizeContent($html),
            'files' => $files,
        ];
    }

    /**
     * @return array<int,string>
     */
    private function extractDocumentReferenceUrlsFromHtml(string $html): array
    {
        if ('' === $html) {
            return [];
        }

        $urls = [];

        if (preg_match_all('~\b(?:src|href|poster|data)\s*=\s*(["\'])([^"\']+)\1~i', $html, $matches)) {
            foreach ($matches[2] as $url) {
                $url = trim((string) $url);
                if ('' !== $url) {
                    $urls[] = $url;
                }
            }
        }

        if (preg_match_all('~\bsrcset\s*=\s*(["\'])(.*?)\1~is', $html, $matches)) {
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

        if (preg_match_all('~url\((["\']?)([^)\'\"]+)\1\)~i', $html, $matches)) {
            foreach ($matches[2] as $url) {
                $url = trim((string) $url);
                if ('' !== $url) {
                    $urls[] = $url;
                }
            }
        }

        return array_values(array_unique($urls));
    }

    private function normalizeContent(string $html): string
    {
        if ('' === $html) {
            return $html;
        }

        $html = (string) preg_replace_callback(
            '~\bsrcset\s*=\s*([\'\"])(.*?)\1~is',
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
            '~\b(src|href|poster|data)\s*=\s*([\'\"])([^\'\"]+)\2~i',
            fn(array $m) => $m[1].'='.$m[2].$this->rewriteDocUrl($m[3]).$m[2],
            $html
        );

        $html = (string) preg_replace_callback(
            '~\bstyle\s*=\s*([\'\"])(.*?)\1~is',
            function (array $m): string {
                $q = $m[1];
                $style = $m[2];
                $style = (string) preg_replace_callback(
                    '~url\((["\']?)([^)\'\"]+)\1\)~i',
                    fn(array $mm) => 'url('.$mm[1].$this->rewriteDocUrl($mm[2]).$mm[1].')',
                    $style
                );
                return 'style='.$q.$style.$q;
            },
            $html
        );

        return $html;
    }

    private function rewriteDocUrl(string $url): string
    {
        if ('' === $url || str_contains($url, '@@PLUGINFILE@@')) {
            return $url;
        }

        $documentPath = $this->resolveChamiloDocumentPathFromUrl($url);
        if (null === $documentPath) {
            return $url;
        }

        return '@@PLUGINFILE@@'.$this->buildPluginFilePathFromChamiloDocumentPath($documentPath);
    }

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

    private function buildPluginFilePathFromChamiloDocumentPath(string $documentPath): string
    {
        $relative = $this->stripChamiloDocumentPrefix($documentPath);
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        return '/Documents/'.$relative;
    }

    private function stripChamiloDocumentPrefix(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        return (string) preg_replace('#^(?:document/?)+#i', '', $path);
    }

    private function getPageContent(object $page, int $pageId): string
    {
        foreach (['content', 'htmlcontent', 'description', 'comment'] as $field) {
            if (isset($page->$field) && '' !== trim((string) $page->$field)) {
                return (string) $page->$field;
            }
        }

        $courseCode = (string) ($this->course->code ?? $this->course->info['code'] ?? '');
        if ('' === $courseCode) {
            return '';
        }

        $document = DocumentManager::get_document_data_by_id($pageId, $courseCode);
        if (!\is_array($document) || empty($document['path'])) {
            return '';
        }

        $absolutePath = $this->resolveDocumentAbsolutePath($pageId, (string) $document['path']);
        if (null === $absolutePath || !is_file($absolutePath)) {
            return '';
        }

        $content = @file_get_contents($absolutePath);

        return false === $content ? '' : (string) $content;
    }

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
            }
        }

        $fallback = rtrim((string) $this->course->path, '/').'/document/'.ltrim($documentPath, '/');

        return is_file($fallback) ? $fallback : null;
    }

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
}
