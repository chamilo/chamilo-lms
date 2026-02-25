<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

/**
 * Class PageExport.
 *
 * Handles the export of pages within a course.
 */
class PageExport extends ActivityExport
{
    /**
     * Export a page to the specified directory.
     *
     * @param int    $activityId The ID of the page.
     * @param string $exportDir  The directory where the page will be exported.
     * @param int    $moduleId   The ID of the module.
     * @param int    $sectionId  The ID of the section.
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        // Prepare the directory where the page export will be saved
        $pageDir = $this->prepareActivityDirectory($exportDir, 'page', (int) $moduleId);

        // Retrieve page data using the exported module id
        $pageData = $this->getData((int) $activityId, (int) $sectionId, (int) $moduleId);
        if (empty($pageData)) {
            return;
        }

        // Generate XML files
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
     */
    public function getData(int $pageId, int $sectionId, ?int $moduleId = null): ?array
    {
        $courseContextId = (int) ($this->course->info['real_id'] ?? 0);

        // Course introduction pseudo-page (used only to export embedded files)
        if ($pageId === 0) {
            $introText = trim($this->course->resources[RESOURCE_TOOL_INTRO]['course_homepage']->intro_text ?? '');

            if (!empty($introText)) {
                $introResult = $this->extractEmbeddedFilesAndNormalizeContent(
                    $introText,
                    $courseContextId, // Keep course context for intro assets
                    0,                // No real module id for intro pseudo-page
                    true
                );

                return [
                    'id' => 0,
                    'moduleid' => 0,
                    'modulename' => 'page',
                    'contextid' => $courseContextId,
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

        $pageResources = $this->course->resources[RESOURCE_DOCUMENT] ?? [];
        foreach ($pageResources as $page) {
            if ((int) $page->source_id !== $pageId) {
                continue;
            }

            $effectiveModuleId = (int) ($moduleId ?? $page->source_id);
            if ($effectiveModuleId <= 0) {
                $effectiveModuleId = (int) $page->source_id;
            }

            $pageName = $page->title;
            if ($sectionId > 0) {
                $pageName = $this->lpItemTitle($sectionId, RESOURCE_DOCUMENT, $pageId, $pageName);
            }

            $rawContent = $this->getPageContent($page);
            $pageResult = $this->extractEmbeddedFilesAndNormalizeContent(
                $rawContent,
                $effectiveModuleId, // Page activity context must match exported module id
                $effectiveModuleId,
                false
            );

            return [
                'id' => $page->source_id,
                'moduleid' => $effectiveModuleId,
                'modulename' => 'page',
                'contextid' => $effectiveModuleId,
                'name' => $pageName,
                'intro' => $page->comment ?? '',
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
     * Create the XML file for the page.
     */
    private function createPageXml(array $pageData, string $pageDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity id="'.$pageData['id'].'" moduleid="'.$pageData['moduleid'].'" modulename="page" contextid="'.$pageData['contextid'].'">'.PHP_EOL;
        $xmlContent .= '  <page id="'.$pageData['id'].'">'.PHP_EOL;
        $xmlContent .= '    <name>'.htmlspecialchars($pageData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '    <intro>'.htmlspecialchars($pageData['intro']).'</intro>'.PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>'.PHP_EOL;
        $xmlContent .= '    <content>'.htmlspecialchars($pageData['content']).'</content>'.PHP_EOL;
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

    private function normalizeContent(string $html): string
    {
        return preg_replace_callback(
            '#<img[^>]+src=["\'](?<url>[^"\']+)["\']#i',
            function ($match) {
                $src = $match['url'];

                if (!preg_match('#/document(?P<path>/[^"\']+)#', $src, $parts)) {
                    return $match[0];
                }

                $pluginFilePath = $this->buildPluginFilePathFromChamiloDocumentPath((string) $parts['path']);

                return str_replace($src, '@@PLUGINFILE@@'.$pluginFilePath, $match[0]);
            },
            $html
        );
    }

    /**
     * Extract embedded document files from HTML content and normalize URLs to @@PLUGINFILE@@.
     *
     * @return array{content:string, files:array<int,array<string,mixed>>}
     */
    private function extractEmbeddedFilesAndNormalizeContent(
        string $html,
        int $contextId,
        int $moduleId,
        bool $isIntro
    ): array {
        if ($html === '') {
            return [
                'content' => '',
                'files' => [],
            ];
        }

        $courseInfo = api_get_course_info($this->course->code);
        $adminId = (int) (MoodleExport::getAdminUserData()['id'] ?? 1);
        $fileExport = new FileExport($this->course);

        $files = [];
        $seenDocIds = [];
        $sequence = 0;

        $normalizedHtml = preg_replace_callback(
            '#<img[^>]+src=["\'](?<url>[^"\']+)["\']#i',
            function ($match) use (
                $courseInfo,
                $contextId,
                $moduleId,
                $isIntro,
                $adminId,
                $fileExport,
                &$files,
                &$seenDocIds,
                &$sequence
            ) {
                $src = (string) ($match['url'] ?? '');

                // Match Chamilo document URLs and keep the relative path
                if (!preg_match('#/document(?P<path>/[^"\']+)#', $src, $m)) {
                    return $match[0];
                }

                $documentRelativePath = (string) $m['path']; // Example: /folder/image.jpg
                $docId = \DocumentManager::get_document_id($courseInfo, $documentRelativePath);

                if (empty($docId)) {
                    return $match[0];
                }

                $docId = (int) $docId;
                $this->course->used_page_doc_ids[] = $docId;

                if (!isset($seenDocIds[$docId])) {
                    $document = \DocumentManager::get_document_data_by_id($docId, $this->course->code);

                    if (!empty($document)) {
                        $documentPath = (string) ($document['path'] ?? '');
                        $absolutePath = $this->course->path.$documentPath;
                        $filename = basename($documentPath);

                        $sequence++;
                        $fileId = $this->buildPageFileId($moduleId, $contextId, $sequence, $isIntro);

                        $files[] = [
                            'id' => $fileId,
                            'contenthash' => is_file($absolutePath) ? sha1_file($absolutePath) : hash('sha1', $filename),
                            'contextid' => $contextId,
                            'component' => 'mod_page',
                            'filearea' => 'content',
                            'itemid' => 0,
                            'filepath' => $this->buildPluginFileDirectoryFromChamiloDocumentPath($documentPath),
                            'documentpath' => 'document'.$documentPath,
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
                        ];
                    }

                    $seenDocIds[$docId] = true;
                }

                $pluginFilePath = $this->buildPluginFilePathFromChamiloDocumentPath($documentRelativePath);

                return str_replace($src, '@@PLUGINFILE@@'.$pluginFilePath, $match[0]);
            },
            $html
        );

        return [
            'content' => $normalizedHtml,
            'files' => $files,
        ];
    }

    /**
     * Build a unique files.xml id for page embedded files.
     * Uses a dedicated range to avoid collisions with folder/resource ids.
     */
    private function buildPageFileId(int $moduleId, int $contextId, int $sequence, bool $isIntro): int
    {
        if ($isIntro) {
            // Intro pseudo-page files use a separate range
            return 1150000000 + max(0, $contextId) + max(1, $sequence);
        }

        // Regular page activity files (moduleId can be the LP-based cmid like 900000000+lpItemId)
        return 1100000000 + max(0, $moduleId) + max(1, $sequence);
    }

    /**
     * Build the pluginfile directory path (filepath in files.xml) from a Chamilo document path.
     * Example:
     *   /folder/image.jpg -> /Documents/folder/
     *   image.jpg         -> /Documents/
     */
    private function buildPluginFileDirectoryFromChamiloDocumentPath(string $documentPath): string
    {
        $relative = $this->stripChamiloDocumentPrefix($documentPath);
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        $dir = dirname($relative);
        if ($dir === '.' || $dir === '/') {
            return '/Documents/';
        }

        return '/Documents/'.trim($dir, '/').'/';
    }

    /**
     * Build the pluginfile full path used in HTML content.
     * Example:
     *   /folder/image.jpg -> /Documents/folder/image.jpg
     *   image.jpg         -> /Documents/image.jpg
     */
    private function buildPluginFilePathFromChamiloDocumentPath(string $documentPath): string
    {
        $relative = $this->stripChamiloDocumentPrefix($documentPath);
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        return '/Documents/'.$relative;
    }

    /**
     * Remove the internal Chamilo "document/" prefix if present.
     */
    private function stripChamiloDocumentPrefix(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('#^/?document/#', '', $path);

        return (string) $path;
    }

    /**
     * Retrieves the content of the page.
     */
    private function getPageContent(object $page): string
    {
        if ($page->file_type === 'file') {
            return file_get_contents($this->course->path.$page->path);
        }

        return '';
    }
}
