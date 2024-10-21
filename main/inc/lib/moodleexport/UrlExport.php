<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

/**
 * Class UrlExport.
 *
 * Handles the export of URLs within a course.
 */
class UrlExport extends ActivityExport
{
    /**
     * Export all URL resources into a single Moodle activity.
     *
     * @param int    $activityId The ID of the URL.
     * @param string $exportDir  The directory where the URL will be exported.
     * @param int    $moduleId   The ID of the module.
     * @param int    $sectionId  The ID of the section.
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        // Prepare the directory where the URL export will be saved
        $urlDir = $this->prepareActivityDirectory($exportDir, 'url', $moduleId);

        // Retrieve URL data
        $urlData = $this->getData($activityId, $sectionId);

        // Generate XML file for the URL
        $this->createUrlXml($urlData, $urlDir);
        $this->createModuleXml($urlData, $urlDir);
        $this->createGradesXml($urlData, $urlDir);
        $this->createGradeHistoryXml($urlData, $urlDir);
        $this->createInforefXml($urlData, $urlDir);
        $this->createRolesXml($urlData, $urlDir);
        $this->createCommentsXml($urlData, $urlDir);
        $this->createCalendarXml($urlData, $urlDir);
        $this->createFiltersXml($urlData, $urlDir);
    }

    /**
     * Get all URL data for the course.
     */
    public function getData(int $activityId, int $sectionId): ?array
    {
        // Extract the URL information from the course data
        $url = $this->course->resources['link'][$activityId];

        // Return the URL data formatted for export
        return [
            'id' => $activityId,
            'moduleid' => $activityId,
            'modulename' => 'url',
            'contextid' => $this->course->info['real_id'],
            'name' => $url->title,
            'description' => $url->description,
            'externalurl' => $url->url,
            'timecreated' => time(),
            'timemodified' => time(),
            'sectionid' => $sectionId,
            'sectionnumber' => 0,
            'users' => [],
            'files' => [],
        ];
    }

    /**
     * Create the XML file for the URL.
     */
    private function createUrlXml(array $urlData, string $urlDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity id="'.$urlData['id'].'" moduleid="'.$urlData['moduleid'].'" modulename="'.$urlData['modulename'].'" contextid="'.$urlData['contextid'].'">'.PHP_EOL;
        $xmlContent .= '  <url id="'.$urlData['id'].'">'.PHP_EOL;
        $xmlContent .= '    <name>'.htmlspecialchars($urlData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '    <intro><![CDATA['.htmlspecialchars($urlData['description']).']]></intro>'.PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>'.PHP_EOL;
        $xmlContent .= '    <externalurl>'.htmlspecialchars($urlData['externalurl']).'</externalurl>'.PHP_EOL;
        $xmlContent .= '    <display>0</display>'.PHP_EOL;
        $xmlContent .= '    <displayoptions>a:1:{s:10:"printintro";i:1;}</displayoptions>'.PHP_EOL;
        $xmlContent .= '    <parameters>a:0:{}</parameters>'.PHP_EOL;
        $xmlContent .= '    <timemodified>'.$urlData['timemodified'].'</timemodified>'.PHP_EOL;
        $xmlContent .= '  </url>'.PHP_EOL;
        $xmlContent .= '</activity>';

        $this->createXmlFile('url', $xmlContent, $urlDir);
    }
}
