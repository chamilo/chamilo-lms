<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\FileExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleExport;
use DocumentManager;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const PHP_EOL;

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
     * @param int    $activityId the ID of the page
     * @param string $exportDir  the directory where the page will be exported
     * @param int    $moduleId   the ID of the module
     * @param int    $sectionId  the ID of the section
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        // Ensure target directory for activity exists
        $pageDir = $this->prepareActivityDirectory($exportDir, 'page', $moduleId);

        // Resolve page data
        $pageData = $this->getData((int) $activityId, (int) $sectionId);
        if (null === $pageData) {
            // Nothing to export for this page
            return;
        }

        // Write activity files
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

        // Course homepage (Introduction) as a Page
        if (0 === $pageId) {
            $introText = trim($this->course->resources[RESOURCE_TOOL_INTRO]['course_homepage']->intro_text ?? '');

            if ('' !== $introText) {
                $files = [];
                $resources = DocumentManager::get_resources_from_source_html($introText);
                $courseInfo = api_get_course_info($this->course->code);
                $adminId = MoodleExport::getAdminUserData()['id'] ?? 0;

                foreach ($resources as [$src]) {
                    if (preg_match('#/document(/[^"\']+)#', $src, $matches)) {
                        $path = $matches[1];
                        $docId = DocumentManager::get_document_id($courseInfo, $path);
                        if ($docId) {
                            $this->course->used_page_doc_ids[] = $docId;
                            $document = DocumentManager::get_document_data_by_id($docId, $this->course->code);
                            if ($document) {
                                $contenthash = hash('sha1', basename($document['path']));
                                $mimetype = (new FileExport($this->course))->getMimeType($document['path']);

                                $files[] = [
                                    'id' => (int) $document['id'],
                                    'contenthash' => $contenthash,
                                    'contextid' => $contextid,
                                    'component' => 'mod_page',
                                    'filearea' => 'content',
                                    'itemid' => 1,
                                    'filepath' => '/Documents/',
                                    'documentpath' => 'document'.$document['path'],
                                    'filename' => basename($document['path']),
                                    'userid' => $adminId,
                                    'filesize' => (int) $document['size'],
                                    'mimetype' => $mimetype,
                                    'status' => 0,
                                    'timecreated' => time() - 3600,
                                    'timemodified' => time(),
                                    'source' => (string) $document['title'],
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

        // Regular HTML Document exported as Page
        $pageResources = $this->course->resources[RESOURCE_DOCUMENT] ?? [];
        foreach ($pageResources as $page) {
            if ((int) $page->source_id === $pageId) {
                return [
                    'id' => (int) $page->source_id,
                    'moduleid' => (int) $page->source_id,
                    'modulename' => 'page',
                    'contextid' => $contextid,
                    'name' => (string) $page->title,
                    'intro' => (string) ($page->comment ?? ''),
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
     * Normalize HTML content by rewriting course document URLs to @@PLUGINFILE@@ tokens.
     * Covers:
     *  - <img src="..."> (existing behavior)
     *  - <a href="...">
     *  - <link href="...">
     *  - <source src="..."> (audio/video)
     *  - poster="..." on <video>
     *  - url(...) in inline style="" and <style> blocks.
     */
    private function normalizeContent(string $html): string
    {
        if ('' === $html) {
            return $html;
        }

        // 1) Generic attributes: src|href|poster on any tag
        $html = (string) preg_replace_callback(
            '~\b(src|href|poster)\s*=\s*([\'"])([^\'"]+)\2~i',
            function (array $m): string {
                $attr = $m[1];
                $q = $m[2];
                $url = $m[3];
                $new = $this->rewriteDocUrl($url);
                // If nothing to rewrite, keep original attribute
                if ($new === $url) {
                    return $m[0];
                }

                return $attr.'='.$q.$new.$q;
            },
            $html
        );

        // 2) Inline CSS: style="... url('...') ..."
        $html = (string) preg_replace_callback(
            '~\bstyle\s*=\s*([\'"])(.*?)\1~is',
            function (array $m): string {
                $q = $m[1];
                $style = $m[2];
                $style = (string) preg_replace_callback(
                    '~url\((["\']?)([^)\'"]+)\1\)~i',
                    function (array $mm): string {
                        $q2 = $mm[1];
                        $url = $mm[2];
                        $new = $this->rewriteDocUrl($url);
                        if ($new === $url) {
                            return $mm[0];
                        }

                        return 'url('.$q2.$new.$q2.')';
                    },
                    $style
                );

                return 'style='.$q.$style.$q;
            },
            $html
        );

        // 3) <style> blocks: url('...') or url(...)
        return (string) preg_replace_callback(
            '~(<style\b[^>]*>)(.*?)(</style>)~is',
            function (array $m): string {
                $open = $m[1];
                $css = $m[2];
                $close = $m[3];
                $css = (string) preg_replace_callback(
                    '~url\((["\']?)([^)\'"]+)\1\)~i',
                    function (array $mm): string {
                        $q = $mm[1];
                        $url = $mm[2];
                        $new = $this->rewriteDocUrl($url);
                        if ($new === $url) {
                            return $mm[0];
                        }

                        return 'url('.$q.$new.$q.')';
                    },
                    $css
                );

                return $open.$css.$close;
            },
            $html
        );
    }

    /**
     * Rewrite course document URLs (/document/... or /courses/<code>/document/...)
     * to @@PLUGINFILE@@/Documents/<filename>. Leaves other URLs untouched.
     */
    private function rewriteDocUrl(string $url): string
    {
        // Already rewritten or non-course URL
        if ('' === $url || str_contains($url, '@@PLUGINFILE@@')) {
            return $url;
        }

        // Matches /document/... or /courses/<code>/document/...
        if (preg_match('#/(?:courses/[^/]+/)?document(/[^?\'" )]+)#i', $url, $m)) {
            // Keep current behavior: use basename for the final filename
            $filename = basename($m[1]);

            return '@@PLUGINFILE@@/Documents/'.$filename;
        }

        return $url;
    }

    /**
     * Retrieves the content of the page from disk when the resource is a file.
     */
    private function getPageContent(object $page): string
    {
        if (($page->file_type ?? null) === 'file') {
            $file = $this->course->path.$page->path;
            if (is_file($file) && is_readable($file)) {
                return (string) file_get_contents($file);
            }
        }

        return '';
    }
}
