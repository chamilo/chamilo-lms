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
    public const INTRO_PAGE_MODULE_ID = 910000000;
    private const CONTENT_REVISION = 1;

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
        $effectiveModuleId = (int) $moduleId;
        if ($effectiveModuleId <= 0 && (int) $activityId === 0) {
            $effectiveModuleId = self::INTRO_PAGE_MODULE_ID;
        }
        if ($effectiveModuleId <= 0) {
            $effectiveModuleId = (int) $activityId;
        }

        $pageDir = $this->prepareActivityDirectory($exportDir, 'page', $effectiveModuleId);

        $pageData = $this->getData((int) $activityId, (int) $sectionId, $effectiveModuleId);
        if (empty($pageData)) {
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
     */
    public function getData(int $pageId, int $sectionId, ?int $moduleId = null): ?array
    {
        if ($pageId === 0) {
            $introText = trim((string) ($this->course->resources[RESOURCE_TOOL_INTRO]['course_homepage']->intro_text ?? ''));
            if ($introText === '') {
                return null;
            }

            $effectiveModuleId = (int) ($moduleId ?? self::INTRO_PAGE_MODULE_ID);
            if ($effectiveModuleId <= 0) {
                $effectiveModuleId = self::INTRO_PAGE_MODULE_ID;
            }

            $introResult = $this->extractEmbeddedFilesAndNormalizeContent(
                $introText,
                $effectiveModuleId,
                'mod_page',
                'content',
                0,
                fn (int $sequence): int => $this->buildPageFileId($effectiveModuleId, $effectiveModuleId, $sequence, true)
            );

            return [
                'id' => $effectiveModuleId,
                'moduleid' => $effectiveModuleId,
                'modulename' => 'page',
                'contextid' => $effectiveModuleId,
                'name' => $this->sanitizeMoodleActivityName((string) get_lang('Introduction'), 255),
                'intro' => '',
                'content' => $introResult['content'],
                'sectionid' => $sectionId,
                'sectionnumber' => 1,
                'display' => 5,
                'timemodified' => time(),
                'users' => [],
                'files' => $introResult['files'],
            ];
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

            $pageName = (string) ($page->title ?? '');
            if ($sectionId > 0) {
                $pageName = $this->lpItemTitle($sectionId, RESOURCE_DOCUMENT, $pageId, $pageName);
            }
            $pageName = $this->sanitizeMoodleActivityName($pageName, 255);

            $rawContent = $this->getPageContent($page);
            $pageResult = $this->extractEmbeddedFilesAndNormalizeContent(
                $rawContent,
                $effectiveModuleId,
                'mod_page',
                'content',
                0,
                fn (int $sequence): int => $this->buildPageFileId($effectiveModuleId, $effectiveModuleId, $sequence, false)
            );

            return [
                'id' => (int) $page->source_id,
                'moduleid' => $effectiveModuleId,
                'modulename' => 'page',
                'contextid' => $effectiveModuleId,
                'name' => $pageName,
                'intro' => (string) ($page->comment ?? ''),
                'content' => $pageResult['content'],
                'sectionid' => $sectionId,
                'sectionnumber' => 1,
                'display' => 5,
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
        $xmlContent .= '    <name>'.htmlspecialchars((string) $pageData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '    <intro><![CDATA['.(string) $pageData['intro'].']]></intro>'.PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>'.PHP_EOL;
        $xmlContent .= '    <content><![CDATA['.(string) $pageData['content'].']]></content>'.PHP_EOL;
        $xmlContent .= '    <contentformat>1</contentformat>'.PHP_EOL;
        $xmlContent .= '    <legacyfiles>0</legacyfiles>'.PHP_EOL;
        $xmlContent .= '    <display>'.(int) ($pageData['display'] ?? 5).'</display>'.PHP_EOL;
        $xmlContent .= '    <displayoptions>a:3:{s:12:"printheading";s:1:"1";s:10:"printintro";s:1:"0";s:17:"printlastmodified";s:1:"1";}</displayoptions>'.PHP_EOL;
        $xmlContent .= '    <revision>'.self::CONTENT_REVISION.'</revision>'.PHP_EOL;
        $xmlContent .= '    <timemodified>'.$pageData['timemodified'].'</timemodified>'.PHP_EOL;
        $xmlContent .= '  </page>'.PHP_EOL;
        $xmlContent .= '</activity>';

        $this->createXmlFile('page', $xmlContent, $pageDir);
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
     * Retrieves the content of the page.
     */
    private function getPageContent(object $page): string
    {
        if (($page->file_type ?? '') !== 'file') {
            return '';
        }

        $absolutePath = $this->course->path.$page->path;
        if (!is_file($absolutePath)) {
            return '';
        }

        return (string) file_get_contents($absolutePath);
    }
}
