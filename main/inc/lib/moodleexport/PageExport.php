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
        $pageDir = $this->prepareActivityDirectory($exportDir, 'page', $moduleId);

        // Retrieve page data
        $pageData = $this->getData($activityId, $sectionId);

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
    public function getData(int $pageId, int $sectionId): ?array
    {
        $contextid = $this->course->info['real_id'];
        if ($pageId === 0) {
            $introText = trim($this->course->resources[RESOURCE_TOOL_INTRO]['course_homepage']->intro_text ?? '');

            if (!empty($introText)) {
                $files = [];
                $resources = \DocumentManager::get_resources_from_source_html($introText);
                $courseInfo = api_get_course_info($this->course->code);
                $adminId = MoodleExport::getAdminUserData()['id'];

                foreach ($resources as [$src]) {
                    if (preg_match('#/document(/[^"\']+)#', $src, $matches)) {
                        $path = $matches[1];
                        $docId = \DocumentManager::get_document_id($courseInfo, $path);
                        if ($docId) {
                            $this->course->used_page_doc_ids[] = $docId;
                            $document = \DocumentManager::get_document_data_by_id($docId, $this->course->code);
                            if ($document) {
                                $contenthash = hash('sha1', basename($document['path']));
                                $mimetype = (new FileExport($this->course))->getMimeType($document['path']);

                                $files[] = [
                                    'id' => $document['id'],
                                    'contenthash' => $contenthash,
                                    'contextid' => $contextid,
                                    'component' => 'mod_page',
                                    'filearea' => 'content',
                                    'itemid' => 1,
                                    'filepath' => '/Documents/',
                                    'documentpath' => 'document' . $document['path'],
                                    'filename' => basename($document['path']),
                                    'userid' => $adminId,
                                    'filesize' => $document['size'],
                                    'mimetype' => $mimetype,
                                    'status' => 0,
                                    'timecreated' => time() - 3600,
                                    'timemodified' => time(),
                                    'source' => $document['title'],
                                    'author' => 'Unknown',
                                    'license' => 'allrightsreserved',
                                ];
                            }
                        }
                    }
                }

                return [
                    'id' => 0,
                    'moduleid' => 0,
                    'modulename' => 'page',
                    'contextid' => $contextid,
                    'name' => get_lang('Introduction'),
                    'intro' => '',
                    'content' => $this->normalizeContent($introText),
                    'sectionid' => $sectionId,
                    'sectionnumber' => 1,
                    'display' => 0,
                    'timemodified' => time(),
                    'users' => [],
                    'files' => $files,
                ];
            }
        }

        $pageResources = $this->course->resources[RESOURCE_DOCUMENT] ?? [];
        foreach ($pageResources as $page) {
            if ($page->source_id == $pageId) {
                return [
                    'id' => $page->source_id,
                    'moduleid' => $page->source_id,
                    'modulename' => 'page',
                    'contextid' => $contextid,
                    'name' => $page->title,
                    'intro' => $page->comment ?? '',
                    'content' => $this->normalizeContent($this->getPageContent($page)),
                    'sectionid' => $sectionId,
                    'sectionnumber' => 1,
                    'display' => 0,
                    'timemodified' => time(),
                    'users' => [],
                    'files' => [],
                ];
            }
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

                if (preg_match('#/courses/[^/]+/document/(.+)$#', $src, $parts)) {
                    $filename = basename($parts[1]);
                    return str_replace($src, '@@PLUGINFILE@@/Documents/' . $filename, $match[0]);
                }

                return $match[0];
            },
            $html
        );
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
