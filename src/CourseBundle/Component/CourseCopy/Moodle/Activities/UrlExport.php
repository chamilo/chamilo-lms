<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use const PHP_EOL;

/**
 * Exporter for Moodle "url" activities.
 */
class UrlExport extends ActivityExport
{
    /**
     * Export a URL activity.
     *
     * @param int    $activityId the ID of the URL record in course resources
     * @param string $exportDir  destination root for the export
     * @param int    $moduleId   module id (used for directory naming)
     * @param int    $sectionId  section id where this activity belongs
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        $urlDir = $this->prepareActivityDirectory((string) $exportDir, 'url', (int) $moduleId);
        $urlData = $this->getData((int) $activityId, (int) $sectionId);

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
     * Build URL data from course structure.
     *
     * @return array<string,mixed>
     */
    public function getData(int $activityId, int $sectionId): ?array
    {
        $url = $this->course->resources['link'][$activityId] ?? null;

        $catTitle = '';
        $catId = 0;
        if ($url && isset($url->obj)) {
            $catId = (int) ($url->obj->category_id ?? 0);
        } elseif ($url) {
            $catId = (int) ($url->category_id ?? 0);
        }
        if ($catId > 0) {
            $catRes = $this->course->resources[RESOURCE_LINKCATEGORY][$catId] ?? null;
            if ($catRes) {
                $src = $catRes->obj ?? $catRes;
                $catTitle = (string) ($src->title ?? $src->category_title ?? '');
            }
        }

        error_log('URL_EXPORT activityId='.$activityId.' catId='.$catId.' catTitle='.$catTitle);

        if (null === $url) {
            return [
                'id' => $activityId,
                'moduleid' => $activityId,
                'modulename' => 'url',
                'contextid' => (int) ($this->course->info['real_id'] ?? 0),
                'name' => 'URL '.$activityId,
                'description' => '',
                'externalurl' => '',
                'target' => '',
                'category_id' => $catId,
                'category_title' => $catTitle,
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
            'target' => (string) ($src->target ?? ''),
            'category_id' => $catId,
            'category_title' => $catTitle,
            'timecreated' => time(),
            'timemodified' => time(),
            'sectionid' => $sectionId,
            'sectionnumber' => 0,
            'users' => [],
            'files' => [],
        ];
    }

    /**
     * Create url.xml for this activity.
     *
     * @param array<string,mixed> $urlData
     */
    private function createUrlXml(array $urlData, string $urlDir): void
    {
        $intro = (string) $urlData['description'];

        if (!empty($urlData['category_id'])) {
            $intro .= "\n<!-- CHAMILO2:link_category_id:{$urlData['category_id']} -->";
            if (!empty($urlData['category_title'])) {
                $intro .= "\n<!-- CHAMILO2:link_category_title:".
                    htmlspecialchars((string) $urlData['category_title']).' -->';
            }
        }
        $introCdata = '<![CDATA['.$intro.']]>';

        $display = ('_blank' === $urlData['target']) ? 3 : 0; // 3=popup, 0=default

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<activity id="'.$urlData['id'].'" moduleid="'.$urlData['moduleid'].'" modulename="url" contextid="'.$urlData['contextid'].'">'.PHP_EOL;
        $xml .= '  <url id="'.$urlData['id'].'">'.PHP_EOL;
        $xml .= '    <name>'.htmlspecialchars((string) $urlData['name']).'</name>'.PHP_EOL;
        $xml .= '    <intro>'.$introCdata.'</intro>'.PHP_EOL;
        $xml .= '    <introformat>1</introformat>'.PHP_EOL;
        $xml .= '    <externalurl>'.htmlspecialchars((string) $urlData['externalurl']).'</externalurl>'.PHP_EOL;
        $xml .= '    <display>'.$display.'</display>'.PHP_EOL;
        $xml .= '    <displayoptions>a:1:{s:10:"printintro";i:1;}</displayoptions>'.PHP_EOL;
        $xml .= '    <parameters>a:0:{}</parameters>'.PHP_EOL;
        $xml .= '    <timemodified>'.(int) $urlData['timemodified'].'</timemodified>'.PHP_EOL;
        $xml .= '  </url>'.PHP_EOL;
        $xml .= '</activity>';

        $this->createXmlFile('url', $xml, $urlDir);
    }
}
