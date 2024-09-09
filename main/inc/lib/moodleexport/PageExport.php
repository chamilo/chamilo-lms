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
     * @param int $activityId The ID of the page.
     * @param string $exportDir The directory where the page will be exported.
     * @param int $moduleId The ID of the module.
     * @param int $sectionId The ID of the section.
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        // Prepare the directory where the page export will be saved
        $pageDir = $this->prepareActivityDirectory($exportDir, 'page', $moduleId);

        // Retrieve page data
        $pageData = $this->getPageData($activityId, $sectionId);

        // Generate XML files
        $this->createPageXml($pageData, $pageDir);
        $this->createModuleXml($pageData, $pageDir);
        $this->createGradesXml($pageData, $pageDir);
        $this->createFiltersXml($pageData, $pageDir);
        $this->createGradeHistoryXml($pageData, $pageDir);
        $this->createInforefXml($pageData, $pageDir);
        $this->createRolesXml($pageDir);
    }

    /**
     * Create the XML file for the page.
     */
    private function createPageXml(array $pageData, string $pageDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<activity id="' . $pageData['id'] . '" moduleid="' . $pageData['moduleid'] . '" modulename="page" contextid="' . $pageData['contextid'] . '">' . PHP_EOL;
        $xmlContent .= '  <page id="' . $pageData['id'] . '">' . PHP_EOL;
        $xmlContent .= '    <name>' . htmlspecialchars($pageData['name']) . '</name>' . PHP_EOL;
        $xmlContent .= '    <intro>' . htmlspecialchars($pageData['intro']) . '</intro>' . PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>' . PHP_EOL;
        $xmlContent .= '    <content>' . htmlspecialchars($pageData['content']) . '</content>' . PHP_EOL;
        $xmlContent .= '    <contentformat>1</contentformat>' . PHP_EOL;
        $xmlContent .= '    <legacyfiles>0</legacyfiles>' . PHP_EOL;
        $xmlContent .= '    <display>5</display>' . PHP_EOL;
        $xmlContent .= '    <displayoptions>a:3:{s:12:"printheading";s:1:"1";s:10:"printintro";s:1:"0";s:17:"printlastmodified";s:1:"1";}</displayoptions>' . PHP_EOL;
        $xmlContent .= '    <revision>1</revision>' . PHP_EOL;
        $xmlContent .= '    <timemodified>' . $pageData['timemodified'] . '</timemodified>' . PHP_EOL;
        $xmlContent .= '  </page>' . PHP_EOL;
        $xmlContent .= '</activity>';

        $this->createXmlFile('page', $xmlContent, $pageDir);
    }

    /**
     * Get page data dynamically from the course.
     */
    public function getPageData(int $pageId, int $sectionId): ?array
    {
        $pageResources = $this->course->resources[RESOURCE_DOCUMENT];

        foreach ($pageResources as $page) {
            if ($page->source_id == $pageId) {
                $contextid = $this->course->info['real_id'];

                return [
                    'id' => $page->source_id,
                    'moduleid' => $page->source_id,
                    'modulename' => 'page',
                    'contextid' => $contextid,
                    'name' => $page->title,
                    'intro' => $page->comment ?? '',
                    'content' => $this->getPageContent($page),
                    'sectionid' => $sectionId,
                    'sectionnumber' => 1,
                    'display' => 0,
                    'timemodified' => time(),
                ];
            }
        }

        return null;
    }

    /**
     * Retrieves the content of the page.
     */
    private function getPageContent(object $page): string
    {
        if ($page->file_type === 'file') {
            return file_get_contents($this->course->path . $page->path);
        }

        return '';
    }
}
