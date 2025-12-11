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
 * LabelExport — exports legacy Course Descriptions as Moodle "label" activities.
 * - Writes activities/label_{moduleId}/{label.xml,module.xml,inforef.xml,...}
 * - Uses ActivityExport helpers for module.xml, inforef.xml, etc.
 */
class LabelExport extends ActivityExport
{
    /**
     * Export this label activity.
     *
     * @param int    $activityId  source_id of the course_description
     * @param string $exportDir   root temp export directory
     * @param int    $moduleId    module id used in directory name (usually = $activityId)
     * @param int    $sectionId   resolved section (LP) or 0 for General
     */
    public function export(int $activityId, string $exportDir, int $moduleId, int $sectionId): void
    {
        // Ensure activity folder
        $labelDir = $this->prepareActivityDirectory($exportDir, 'label', $moduleId);

        // Resolve payload
        $data = $this->getData($activityId, $sectionId);
        if (null === $data) {
            // Nothing to export
            return;
        }

        // Write primary XMLs
        $this->createLabelXml($data, $labelDir);
        $this->createModuleXml($data, $labelDir);
        $this->createInforefXml($data, $labelDir);

        // Optional, but keeps structure consistent with other exporters
        $this->createFiltersXml($data, $labelDir);
        $this->createGradesXml($data, $labelDir);
        $this->createGradeHistoryXml($data, $labelDir);
        $this->createCompletionXml($data, $labelDir);
        $this->createCommentsXml($data, $labelDir);
        $this->createCompetenciesXml($data, $labelDir);
        $this->createRolesXml($data, $labelDir);
        $this->createCalendarXml($data, $labelDir);

    }

    /**
     * Build label payload from legacy "course_description" bucket.
     */
    public function getData(int $labelId, int $sectionId): ?array
    {
        // Accept both constant and plain string, defensively
        $bag =
            $this->course->resources[\defined('RESOURCE_COURSEDESCRIPTION') ? RESOURCE_COURSEDESCRIPTION : 'course_description']
            ?? $this->course->resources['course_description']
            ?? [];

        if (empty($bag) || !\is_array($bag)) {
            return null;
        }

        $wrap = $bag[$labelId] ?? null;
        if (!$wrap || !\is_object($wrap)) {
            return null;
        }

        // Unwrap ->obj if present
        $desc = (isset($wrap->obj) && \is_object($wrap->obj)) ? $wrap->obj : $wrap;

        $title = $this->resolveTitle($desc);
        $introRaw = (string) ($desc->content ?? '');
        $intro    = $this->normalizeContent($introRaw);

        // Collect files referenced by intro (so inforef can point to them)
        $files = $this->collectIntroFiles($introRaw, (string) ($this->course->code ?? ''));

        // Build the minimal dataset required by ActivityExport::createModuleXml()
        return [
            'id'            => (int) ($desc->source_id ?? $labelId),
            'moduleid'      => (int) ($desc->source_id ?? $labelId),
            'modulename'    => 'label',
            'sectionid'     => $sectionId,
            // Use section number = section id; falls back to 0 (General) if not in LP
            'sectionnumber' => $sectionId,
            'name'          => $title,
            'intro'         => $intro,
            'introformat'   => 1,
            'timemodified'  => time(),
            'users'         => [],
            'files'         => $files,
        ];
    }

    /**
     * Title resolver with fallback by description_type.
     */
    private function resolveTitle(object $desc): string
    {
        $t = trim((string) ($desc->title ?? ''));
        if ('' !== $t) {
            return $t;
        }
        $map = [1 => 'Descripción', 2 => 'Objetivos', 3 => 'Temas'];
        return $map[(int) ($desc->description_type ?? 0)] ?? 'Descripción';
    }

    /**
     * Normalize HTML: rewrite /document/... to @@PLUGINFILE@@/<file>, including srcset, style url(...), etc.
     */
    private function normalizeContent(string $html): string
    {
        if ('' === $html) {
            return $html;
        }

        // Handle srcset
        $html = (string) preg_replace_callback(
            '~\bsrcset\s*=\s*([\'"])(.*?)\1~is',
            function (array $m): string {
                $q = $m[1]; $val = $m[2];
                $parts = array_map('trim', explode(',', $val));
                foreach ($parts as &$p) {
                    if ($p === '') { continue; }
                    $tokens = preg_split('/\s+/', $p, -1, PREG_SPLIT_NO_EMPTY);
                    if (!$tokens) { continue; }
                    $url = $tokens[0];
                    $new = $this->rewriteDocUrl($url);
                    if ($new !== $url) {
                        $tokens[0] = $new;
                        $p = implode(' ', $tokens);
                    }
                }
                return 'srcset='.$q.implode(', ', $parts).$q;
            },
            $html
        );

        // Generic attributes
        $html = (string) preg_replace_callback(
            '~\b(src|href|poster|data)\s*=\s*([\'"])([^\'"]+)\2~i',
            fn(array $m) => $m[1].'='.$m[2].$this->rewriteDocUrl($m[3]).$m[2],
            $html
        );

        // Inline CSS
        $html = (string) preg_replace_callback(
            '~\bstyle\s*=\s*([\'"])(.*?)\1~is',
            function (array $m): string {
                $q = $m[1]; $style = $m[2];
                $style = (string) preg_replace_callback(
                    '~url\((["\']?)([^)\'"]+)\1\)~i',
                    fn(array $mm) => 'url('.$mm[1].$this->rewriteDocUrl($mm[2]).$mm[1].')',
                    $style
                );
                return 'style='.$q.$style.$q;
            },
            $html
        );

        // <style> blocks
        $html = (string) preg_replace_callback(
            '~(<style\b[^>]*>)(.*?)(</style>)~is',
            function (array $m): string {
                $open = $m[1]; $css = $m[2]; $close = $m[3];
                $css = (string) preg_replace_callback(
                    '~url\((["\']?)([^)\'"]+)\1\)~i',
                    fn(array $mm) => 'url('.$mm[1].$this->rewriteDocUrl($mm[2]).$mm[1].')',
                    $css
                );
                return $open.$css.$close;
            },
            $html
        );

        return $html;
    }

    /**
     * Rewrite /document/... (or /courses/<code>/document/...) to @@PLUGINFILE@@/<basename>.
     */
    private function rewriteDocUrl(string $url): string
    {
        if ($url === '' || str_contains($url, '@@PLUGINFILE@@')) {
            return $url;
        }
        if (preg_match('#/(?:courses/[^/]+/)?document(/[^?\'" )]+)#i', $url, $m)) {
            return '@@PLUGINFILE@@/'.basename($m[1]);
        }
        return $url;
    }

    /**
     * Collect referenced intro files for files.xml (component=mod_label, filearea=intro).
     *
     * @return array<int,array<string,mixed>>
     */
    private function collectIntroFiles(string $introHtml, string $courseCode): array
    {
        if ($introHtml === '') {
            return [];
        }

        $files = [];
        $contextid = (int) ($this->course->info['real_id'] ?? 0);
        $adminId   = MoodleExport::getAdminUserData()['id'] ?? ($this->getAdminUserData()['id'] ?? 0);

        $resources = DocumentManager::get_resources_from_source_html($introHtml);
        $courseInfo = api_get_course_info($courseCode);

        foreach ($resources as [$src]) {
            if (preg_match('#/document(/[^"\']+)#', $src, $matches)) {
                $path = $matches[1];
                $docId = DocumentManager::get_document_id($courseInfo, $path);
                if (!$docId) {
                    continue;
                }
                $document = DocumentManager::get_document_data_by_id($docId, $courseCode);
                if (!$document) {
                    continue;
                }

                $contenthash = hash('sha1', basename($document['path']));
                $mimetype = (new FileExport($this->course))->getMimeType($document['path']);

                $files[] = [
                    'id'          => (int) $document['id'],
                    'contenthash' => $contenthash,
                    'contextid'   => $contextid,
                    'component'   => 'mod_label',
                    'filearea'    => 'intro',
                    'itemid'      => 0,
                    'filepath'    => '/',
                    'documentpath'=> 'document'.$document['path'],
                    'filename'    => basename($document['path']),
                    'userid'      => $adminId,
                    'filesize'    => (int) $document['size'],
                    'mimetype'    => $mimetype,
                    'status'      => 0,
                    'timecreated' => time() - 3600,
                    'timemodified'=> time(),
                    'source'      => (string) $document['title'],
                    'author'      => 'Unknown',
                    'license'     => 'allrightsreserved',
                ];
            }
        }

        return $files;
    }

    /**
     * Write label.xml for the activity.
     */
    private function createLabelXml(array $data, string $dir): void
    {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<activity id="'.(int) $data['id'].'" moduleid="'.(int) $data['moduleid'].'" modulename="label" contextid="'.(int) ($this->course->info['real_id'] ?? 0).'">'.PHP_EOL;
        $xml .= '  <label id="'.(int) $data['id'].'">'.PHP_EOL;
        $xml .= '    <name>'.htmlspecialchars((string) $data['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</name>'.PHP_EOL;
        $xml .= '    <intro><![CDATA['.$data['intro'].']]></intro>'.PHP_EOL;
        $xml .= '    <introformat>'.(int) ($data['introformat'] ?? 1).'</introformat>'.PHP_EOL;
        $xml .= '    <timemodified>'.(int) $data['timemodified'].'</timemodified>'.PHP_EOL;
        $xml .= '  </label>'.PHP_EOL;
        $xml .= '</activity>';

        $this->createXmlFile('label', $xml, $dir);
    }
}
