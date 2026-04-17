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
    public function getData(int $activityId, int $sectionId): ?array
    {
        $url = $this->course->resources['link'][$activityId] ?? null;

        if (null === $url) {
            return [
                'id' => $activityId,
                'moduleid' => $activityId,
                'modulename' => 'url',
                'contextid' => (int) ($this->course->info['real_id'] ?? 0),
                'name' => 'URL '.$activityId,
                'description' => '',
                'externalurl' => '',
                'display' => 6,
                'displayoptions' => $this->buildUrlDisplayOptions(),
                'timecreated' => time(),
                'timemodified' => time(),
                'sectionid' => $sectionId,
                'sectionnumber' => 0,
                'users' => [],
                'files' => [],
            ];
        }

        $src = isset($url->obj) ? $url->obj : $url;

        return [
            'id' => (int) $activityId,
            'moduleid' => (int) $activityId,
            'modulename' => 'url',
            'contextid' => (int) $this->course->info['real_id'],
            'name' => (string) ($src->title ?: $src->url),
            'description' => (string) ($src->description ?? ''),
            'externalurl' => (string) $src->url,
            'display' => 6,
            'displayoptions' => $this->buildUrlDisplayOptions(),
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
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<activity id="'.$urlData['id'].'" moduleid="'.$urlData['moduleid'].'" modulename="url" contextid="'.$urlData['contextid'].'">'.PHP_EOL;
        $xml .= '  <url id="'.$urlData['id'].'">'.PHP_EOL;
        $xml .= '    <name>'.htmlspecialchars((string) $urlData['name']).'</name>'.PHP_EOL;
        $xml .= '    <intro></intro>'.PHP_EOL;
        $xml .= '    <introformat>1</introformat>'.PHP_EOL;
        $xml .= '    <externalurl>'.htmlspecialchars((string) $urlData['externalurl']).'</externalurl>'.PHP_EOL;
        $xml .= '    <display>'.(int) ($urlData['display'] ?? 6).'</display>'.PHP_EOL;
        $xml .= '    <displayoptions>'.htmlspecialchars((string) ($urlData['displayoptions'] ?? 'a:0:{}')).'</displayoptions>'.PHP_EOL;
        $xml .= '    <parameters>a:0:{}</parameters>'.PHP_EOL;
        $xml .= '    <timemodified>'.(int) $urlData['timemodified'].'</timemodified>'.PHP_EOL;
        $xml .= '  </url>'.PHP_EOL;
        $xml .= '</activity>'.PHP_EOL;

        $this->createXmlFile('url', $xml, $urlDir);
    }

    /**
     * Build a stable embedded file id for URL intro files.
     */
    private function buildUrlEmbeddedFileId(int $moduleId, int $sequence): int
    {
        return 1200000000 + max(0, $moduleId) + max(1, $sequence);
    }

    private function buildUrlDisplayOptions(): string
    {
        return serialize([
            'popupwidth' => 1024,
            'popupheight' => 768,
        ]);
    }
}
