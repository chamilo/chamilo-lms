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
 * Handles the export of Moodle page activities.
 *
 * Key points:
 * - Root HTML documents become page activities.
 * - Embedded files remain owned by mod_page/content.
 * - File ownership must not be rewritten later by FileExport.
 */
class PageExport extends ActivityExport
{
    /**
     * Export a page.
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        $pageDir = $this->prepareActivityDirectory($exportDir, 'page', (int) $moduleId);

        $pageData = $this->getData((int) $activityId, (int) $sectionId, (int) $moduleId);
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
     * Get page data dynamically from the course.
     *
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
                $effectiveModuleId = (int) ($moduleId ?? ActivityExport::INTRO_PAGE_MODULE_ID);
                if ($effectiveModuleId <= 0) {
                    $effectiveModuleId = ActivityExport::INTRO_PAGE_MODULE_ID;
                }

                $introResult = $this->extractEmbeddedFilesAndNormalizeContent(
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
                    'content' => $introResult['content'],
                    'sectionid' => $sectionId,
                    'sectionnumber' => 1,
                    'display' => 0,
                    'timemodified' => time(),
                    'users' => [],
                    'files' => $introResult['files'],
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

            if ((int) ($page->source_id ?? 0) !== $pageId) {
                continue;
            }

            $effectiveModuleId = (int) ($moduleId ?? ($page->source_id ?? 0));
            if ($effectiveModuleId <= 0) {
                $effectiveModuleId = (int) ($page->source_id ?? 0);
            }

            $pageName = (string) ($page->title ?? ('Page '.$pageId));
            if ($sectionId > 0) {
                $pageName = $this->lpItemTitle(
                    $sectionId,
                    \defined('RESOURCE_DOCUMENT') ? (string) RESOURCE_DOCUMENT : 'document',
                    $pageId,
                    $pageName
                );
            }
            $pageName = $this->sanitizeMoodleActivityName($pageName, 255);

            $rawContent = $this->getPageContent($page);
            $pageResult = $this->extractEmbeddedFilesAndNormalizeContent(
                $rawContent,
                $effectiveModuleId,
                $effectiveModuleId,
                false,
                (string) ($page->path ?? '')
            );

            return [
                'id' => (int) ($page->source_id ?? 0),
                'moduleid' => $effectiveModuleId,
                'modulename' => 'page',
                'contextid' => $effectiveModuleId,
                'name' => $pageName,
                'intro' => (string) ($page->comment ?? ''),
                'content' => $pageResult['content'],
                'sectionid' => $sectionId,
                'sectionnumber' => 1,
                'display' => 0,
                'timemodified' => time(),
                'users' => [],
                'files' => $pageResult['files'],
            ];
        }

        return null;
    }

    /**
     * Direct inforef using file ids.
     *
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
     * Create page.xml.
     *
     * @param array<string,mixed> $pageData
     */
    private function createPageXml(array $pageData, string $pageDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity id="'.$pageData['id'].'" moduleid="'.$pageData['moduleid'].'" modulename="page" contextid="'.$pageData['contextid'].'">'.PHP_EOL;
        $xmlContent .= '  <page id="'.$pageData['id'].'">'.PHP_EOL;
        $xmlContent .= '    <name>'.htmlspecialchars((string) $pageData['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</name>'.PHP_EOL;
        $xmlContent .= '    <intro>'.htmlspecialchars((string) $pageData['intro'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</intro>'.PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>'.PHP_EOL;
        $xmlContent .= '    <content>'.htmlspecialchars((string) $pageData['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</content>'.PHP_EOL;
        $xmlContent .= '    <contentformat>1</contentformat>'.PHP_EOL;
        $xmlContent .= '    <legacyfiles>0</legacyfiles>'.PHP_EOL;
        $xmlContent .= '    <display>5</display>'.PHP_EOL;
        $xmlContent .= '    <displayoptions>a:3:{s:12:"printheading";s:1:"1";s:10:"printintro";s:1:"0";s:17:"printlastmodified";s:1:"1";}</displayoptions>'.PHP_EOL;
        $xmlContent .= '    <revision>1</revision>'.PHP_EOL;
        $xmlContent .= '    <timemodified>'.$pageData['timemodified'].'</timemodified>'.PHP_EOL;
        $xmlContent .= '  </page>'.PHP_EOL;
        $xmlContent .= '</activity>';

        $this->createXmlFile('page', $xmlContent, $pageDir);
    }

    /**
     * Extract embedded document files from HTML and normalize content to @@PLUGINFILE@@ paths.
     *
     * @return array{content:string, files:array<int,array<string,mixed>>}
     */
    private function extractEmbeddedFilesAndNormalizeContent(
        string $html,
        int $contextId,
        int $moduleId,
        bool $isIntro
    ): array {
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

        $resources = DocumentManager::get_resources_from_source_html($html) ?: [];
        foreach ($resources as $resource) {
            $src = $resource[0] ?? null;
            if (!\is_string($src) || '' === $src) {
                continue;
            }

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
            $fileId = $this->buildPageFileId($moduleId, $contextId, $sequence, $isIntro);

            $contenthash = hash('sha1', $filename);
            if (is_file((string) $absolutePath)) {
                $contenthash = sha1_file((string) $absolutePath);
            }

            $files[] = [
                'id' => $fileId,
                'contenthash' => $contenthash,
                'contextid' => $contextId,
                'component' => 'mod_page',
                'filearea' => 'content',
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
     * Build a unique files.xml id for page embedded files.
     */
    private function buildPageFileId(int $moduleId, int $contextId, int $sequence, bool $isIntro): int
    {
        if ($isIntro) {
            return 1150000000 + max(0, $contextId) + max(1, $sequence);
        }

        return 1100000000 + max(0, $moduleId) + max(1, $sequence);
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
     * Normalize HTML content by rewriting course document URLs to @@PLUGINFILE@@ tokens.
     */
    private function normalizeContent(string $html, array $rewrites = []): string
    {
        if ('' === $html) {
            return $html;
        }

        $html = (string) preg_replace_callback(
            '~\bsrcset\s*=\s*([\'"])(.*?)\1~is',
            function (array $m) use ($rewrites): string {
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

                    $tokens[0] = $this->rewriteDocUrl($tokens[0], $rewrites);
                    $part = implode(' ', $tokens);
                }

                return 'srcset='.$q.implode(', ', $parts).$q;
            },
            $html
        );

        $html = (string) preg_replace_callback(
            '~\b(src|href|poster|data)\s*=\s*([\'"])([^\'"]+)\2~i',
            function (array $m) use ($rewrites): string {
                $attr = $m[1];
                $q = $m[2];
                $url = $m[3];

                return $attr.'='.$q.$this->rewriteDocUrl($url, $rewrites).$q;
            },
            $html
        );

        $html = (string) preg_replace_callback(
            '~\bstyle\s*=\s*([\'"])(.*?)\1~is',
            function (array $m) use ($rewrites): string {
                $q = $m[1];
                $style = $m[2];

                $style = (string) preg_replace_callback(
                    '~url\((["\']?)([^)\'"]+)\1\)~i',
                    function (array $mm) use ($rewrites): string {
                        $q2 = $mm[1];
                        $url = $mm[2];

                        return 'url('.$q2.$this->rewriteDocUrl($url, $rewrites).$q2.')';
                    },
                    $style
                );

                return 'style='.$q.$style.$q;
            },
            $html
        );

        return (string) preg_replace_callback(
            '~(<style\b[^>]*>)(.*?)(</style>)~is',
            function (array $m) use ($rewrites): string {
                $open = $m[1];
                $css = $m[2];
                $close = $m[3];

                $css = (string) preg_replace_callback(
                    '~url\((["\']?)([^)\'"]+)\1\)~i',
                    function (array $mm) use ($rewrites): string {
                        $q = $mm[1];
                        $url = $mm[2];

                        return 'url('.$q.$this->rewriteDocUrl($url, $rewrites).$q.')';
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

    private function resolveEmbeddedDocumentFromUrl(
        string $url,
        array $courseInfo,
        string $courseCode,
        string $currentDocumentPath = ''
    ): ?array {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        if ('' === $url) {
            return null;
        }

        if (
            str_starts_with($url, '@@PLUGINFILE@@')
            || str_starts_with($url, 'data:')
            || str_starts_with($url, 'mailto:')
            || str_starts_with($url, '#')
        ) {
            return null;
        }

        // C2 resource route variants:
        // - /r/document/files/<uuid>/view
        // - /document/files/<uuid>/view
        // - /Documents/files/<uuid>/view
        if (preg_match('#(?:^|/)(?:r/)?(?:Documents|document)/files/(?P<uuid>[0-9a-f-]{36})/view(?:\?.*)?$#i', $url, $m)) {
            return $this->resolveDocumentDataByUuid((string) $m['uuid'], $courseCode);
        }

        $pathCandidates = [];

        if (preg_match('#/(?:courses/[^/]+/)?document(?P<path>/[^?\'" )]+)#i', $url, $m)) {
            $pathCandidates[] = (string) $m['path'];
        }

        if (preg_match('#^document(?P<path>/[^?\'" )]+)#i', $url, $m)) {
            $pathCandidates[] = (string) $m['path'];
        }

        if (preg_match('#^/?Documents/(?P<path>[^?\'" )]+)$#i', $url, $m)) {
            $pathCandidates[] = '/'.ltrim((string) $m['path'], '/');
        }

        if (
            !preg_match('#^(?:https?:)?//#i', $url)
            && !str_starts_with($url, '/')
            && '' !== $currentDocumentPath
        ) {
            $currentDir = dirname(ltrim($this->stripChamiloDocumentPrefix($currentDocumentPath), '/'));
            $currentDir = '.' === $currentDir ? '' : trim($currentDir, '/').'/';
            $pathCandidates[] = '/'.$currentDir.ltrim($url, '/');
        }

        foreach (array_values(array_unique($pathCandidates)) as $candidatePath) {
            $docId = DocumentManager::get_document_id($courseInfo, $candidatePath);
            if (empty($docId)) {
                $docId = DocumentManager::get_document_id($courseInfo, ltrim($candidatePath, '/'));
            }

            if (!empty($docId)) {
                return $this->resolveDocumentDataById((int) $docId, $courseCode);
            }
        }

        return null;
    }

    private function resolveDocumentDataById(int $documentId, string $courseCode): ?array
    {
        if ($documentId <= 0) {
            return null;
        }

        $document = DocumentManager::get_document_data_by_id($documentId, $courseCode);

        return (!empty($document) && !empty($document['path'])) ? $document : null;
    }

    private function resolveDocumentDataByUuid(string $uuid, string $courseCode): ?array
    {
        if ('' === trim($uuid)) {
            return null;
        }

        try {
            $em = \Database::getManager();
            $docRepo = Container::getDocumentRepository();
            $nodeRepo = $em->getRepository(ResourceNode::class);

            $node = null;

            try {
                $node = $nodeRepo->findOneBy(['uuid' => $uuid]);
            } catch (\Throwable $e) {
                @error_log('[PageExport::resolveDocumentDataByUuid][string] '.$e->getMessage());
            }

            if (
                !$node instanceof ResourceNode
                && class_exists(Uuid::class)
            ) {
                try {
                    $uuidObject = Uuid::fromString($uuid);
                    $node = $nodeRepo->findOneBy(['uuid' => $uuidObject]);
                } catch (\Throwable $e) {
                    @error_log('[PageExport::resolveDocumentDataByUuid][UuidObject] '.$e->getMessage());
                }
            }

            if ($node instanceof ResourceNode) {
                $doc = $docRepo->findOneBy(['resourceNode' => $node]);
                if ($doc instanceof CDocument) {
                    return $this->resolveDocumentDataById((int) $doc->getIid(), $courseCode);
                }
            }

            // Fallback scan across exported documents
            $documents =
                $this->course->resources[\defined('RESOURCE_DOCUMENT') ? RESOURCE_DOCUMENT : 'document']
                ?? $this->course->resources['document']
                ?? [];

            if (\is_array($documents)) {
                foreach ($documents as $document) {
                    if (!\is_object($document)) {
                        continue;
                    }

                    $docId = (int) ($document->source_id ?? 0);
                    if ($docId <= 0) {
                        continue;
                    }

                    try {
                        $doc = $docRepo->findOneBy(['iid' => $docId]);
                        if (!$doc instanceof CDocument) {
                            continue;
                        }

                        $resourceNode = $doc->getResourceNode();
                        if (!$resourceNode instanceof ResourceNode) {
                            continue;
                        }

                        $nodeUuid = $resourceNode->getUuid();
                        if ($nodeUuid && (string) $nodeUuid === $uuid) {
                            return $this->resolveDocumentDataById($docId, $courseCode);
                        }
                    } catch (\Throwable $e) {
                        @error_log('[PageExport::resolveDocumentDataByUuid][fallback-scan] '.$e->getMessage());
                    }
                }
            }
        } catch (\Throwable $e) {
            @error_log('[PageExport::resolveDocumentDataByUuid] '.$e->getMessage());
        }

        return null;
    }

    /**
     * Retrieve the content of the page from disk.
     */
    private function getPageContent(object $page): string
    {
        if (($page->file_type ?? null) === 'file') {
            $relative = ltrim((string) ($page->path ?? ''), '/');
            $file = rtrim((string) $this->course->path, '/').'/'.$relative;
            if (is_file($file) && is_readable($file)) {
                return (string) file_get_contents($file);
            }
        }

        return '';
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
            } catch (\Throwable $e) {
                @error_log('[PageExport::resolveDocumentAbsolutePath] '.$e->getMessage());
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

        if (preg_match(
            '#^r/[^/]+/[^/]+/(?P<uuid>[A-Za-z0-9-]{16,64})/(?:view|download|link)/?$#i',
            $path,
            $matches
        )) {
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
     * Resolve the Chamilo logical document path from a URL.
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
                    $docData = \DocumentManager::get_document_data_by_id((int) $doc->getIid(), $courseCode);

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
                        'title' => (string) ($doc->getTitle() ?? basename($documentPath)),
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
        $docId = (int) DocumentManager::get_document_id($courseInfo, $documentPath);
        if ($docId <= 0) {
            return null;
        }

        $document = DocumentManager::get_document_data_by_id($docId, $courseCode);
        if (!\is_array($document) || empty($document['path'])) {
            return null;
        }

        return [
            'id' => $docId,
            'path' => (string) $document['path'],
            'title' => (string) ($document['title'] ?? basename((string) $document['path'])),
            'size' => (int) ($document['size'] ?? 0),
            'abs_path' => $this->resolveDocumentAbsolutePath($docId, (string) $document['path']),
        ];
    }
}
