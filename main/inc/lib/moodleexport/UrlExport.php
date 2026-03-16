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
     * Export one URL activity.
     *
     * @param int    $activityId The ID of the URL.
     * @param string $exportDir  The directory where the URL will be exported.
     * @param int    $moduleId   The exported module ID.
     * @param int    $sectionId  The ID of the section.
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        $effectiveModuleId = (int) $moduleId;
        if ($effectiveModuleId <= 0) {
            $effectiveModuleId = (int) $activityId;
        }

        $urlDir = $this->prepareActivityDirectory($exportDir, 'url', $effectiveModuleId);
        $urlData = $this->getData((int) $activityId, (int) $sectionId, $effectiveModuleId);

        if (empty($urlData)) {
            return;
        }

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
     * Get URL data for the course.
     */
    public function getData(int $activityId, int $sectionId, ?int $moduleId = null): ?array
    {
        if (empty($this->course->resources['link'][$activityId])) {
            return null;
        }

        $url = $this->course->resources['link'][$activityId];

        $effectiveModuleId = (int) ($moduleId ?? $activityId);
        if ($effectiveModuleId <= 0) {
            $effectiveModuleId = $activityId;
        }

        $name = (string) ($url->title ?? '');
        if ($sectionId > 0) {
            $name = $this->lpItemTitle($sectionId, RESOURCE_LINK, $activityId, $name);
        }
        $name = $this->sanitizeMoodleActivityName($name, 255);

        $descriptionResult = $this->extractEmbeddedFilesAndNormalizeContent(
            (string) ($url->description ?? ''),
            $effectiveModuleId,
            'mod_url',
            'intro',
            0,
            fn (int $sequence): int => $this->buildUrlEmbeddedFileId($effectiveModuleId, $sequence)
        );

        return [
            'id' => $activityId,
            'moduleid' => $effectiveModuleId,
            'modulename' => 'url',
            'contextid' => $effectiveModuleId,
            'name' => $name,
            'description' => $descriptionResult['content'],
            'externalurl' => (string) ($url->url ?? ''),
            'timecreated' => time(),
            'timemodified' => time(),
            'sectionid' => $sectionId,
            'sectionnumber' => 0,
            'users' => [],
            'files' => $descriptionResult['files'],
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
        $xmlContent .= '    <name>'.htmlspecialchars((string) $urlData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '    <intro><![CDATA['.(string) $urlData['description'].']]></intro>'.PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>'.PHP_EOL;
        $xmlContent .= '    <externalurl>'.htmlspecialchars((string) $urlData['externalurl']).'</externalurl>'.PHP_EOL;
        $xmlContent .= '    <display>0</display>'.PHP_EOL;
        $xmlContent .= '    <displayoptions>a:1:{s:10:"printintro";i:1;}</displayoptions>'.PHP_EOL;
        $xmlContent .= '    <parameters>a:0:{}</parameters>'.PHP_EOL;
        $xmlContent .= '    <timemodified>'.$urlData['timemodified'].'</timemodified>'.PHP_EOL;
        $xmlContent .= '  </url>'.PHP_EOL;
        $xmlContent .= '</activity>';

        $this->createXmlFile('url', $xmlContent, $urlDir);
    }

    /**
     * Build a stable embedded file id for URL intro files.
     */
    private function buildUrlEmbeddedFileId(int $moduleId, int $sequence): int
    {
        return 1200000000 + max(0, $moduleId) + max(1, $sequence);
    }
}
