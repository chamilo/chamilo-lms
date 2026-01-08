<?php
/* For license terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleExport;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const PHP_EOL;

/**
 * WikiExport — exports legacy CWiki pages as Moodle "wiki" activities.
 * - Writes activities/wiki_{moduleId}/{wiki.xml,module.xml,inforef.xml,...}
 * - One Chamilo wiki page => one Moodle wiki activity (single subwiki + single page + version #1).
 * - Keeps the same auxiliary XMLs consistency as LabelExport (module.xml, inforef.xml, etc.).
 */
final class WikiExport extends ActivityExport
{
    /**
     * Export a single Wiki activity.
     *
     * @param int    $activityId Source page identifier (we try pageId, iid, or array key)
     * @param string $exportDir  Root temp export directory
     * @param int    $moduleId   Module id used in directory name (usually = $activityId)
     * @param int    $sectionId  Resolved course section (0 = General)
     */
    public function export(int $activityId, string $exportDir, int $moduleId, int $sectionId): void
    {
        $wikiDir = $this->prepareActivityDirectory($exportDir, 'wiki', $moduleId);

        $data = $this->getData($activityId, $sectionId);
        if (null === $data) {
            // Nothing to export
            return;
        }

        // Primary XMLs
        $this->createWikiXml($data, $wikiDir);     // activities/wiki_{id}/wiki.xml
        $this->createModuleXml($data, $wikiDir);   // activities/wiki_{id}/module.xml
        $this->createInforefXml($data, $wikiDir);  // activities/wiki_{id}/inforef.xml

        // Optional auxiliaries to keep structure consistent with other exporters
        $this->createFiltersXml($data, $wikiDir);
        $this->createGradesXml($data, $wikiDir);
        $this->createGradeHistoryXml($data, $wikiDir);
        $this->createCompletionXml($data, $wikiDir);
        $this->createCommentsXml($data, $wikiDir);
        $this->createCompetenciesXml($data, $wikiDir);
        $this->createRolesXml($data, $wikiDir);
        $this->createCalendarXml($data, $wikiDir);
    }

    /**
     * Build wiki payload from legacy "wiki" bucket (CWiki).
     *
     * Returns a structure with:
     * - id, moduleid, modulename='wiki', sectionid, sectionnumber
     * - name (title), intro (empty), introformat=1 (HTML)
     * - wikimode ('collaborative'), defaultformat ('html'), forceformat=1
     * - firstpagetitle, timecreated, timemodified
     * - pages[]: one page with versions[0]
     */
    public function getData(int $activityId, int $sectionId): ?array
    {
        $bag =
            $this->course->resources[\defined('RESOURCE_WIKI') ? RESOURCE_WIKI : 'wiki']
            ?? $this->course->resources['wiki']
            ?? [];

        if (empty($bag) || !\is_array($bag)) {
            return null;
        }

        $pages  = [];
        $users  = [];
        $firstTitle = null;

        foreach ($bag as $key => $wrap) {
            if (!\is_object($wrap)) { continue; }
            $p = (isset($wrap->obj) && \is_object($wrap->obj)) ? $wrap->obj : $wrap;

            $pid = (int)($p->pageId ?? $p->iid ?? $key ?? 0);
            if ($pid <= 0) { continue; }

            $title = trim((string)($p->title ?? 'Wiki page '.$pid));
            if ($title === '') { $title = 'Wiki page '.$pid; }
            $rawHtml  = (string)($p->content ?? '');
            $content  = $this->normalizeContent($rawHtml);

            $userId   = (int)($p->userId ?? 0);
            $created  = $this->toTimestamp((string)($p->dtime ?? ''), time());
            $modified = $created;

            $pages[] = [
                'id'            => $pid,
                'title'         => $title,
                'content'       => $content,
                'contentformat' => 'html',
                'version'       => 1,
                'timecreated'   => $created,
                'timemodified'  => $modified,
                'userid'        => $userId,
            ];

            if ($userId > 0) { $users[$userId] = true; }
            if (null === $firstTitle) { $firstTitle = $title; }
        }

        if (empty($pages)) {
            return null;
        }

        return [
            'id'            => $activityId,
            'moduleid'      => $activityId,
            'modulename'    => 'wiki',
            'sectionid'     => $sectionId,
            'sectionnumber' => $sectionId,
            'name'          => 'Wiki',
            'intro'         => '',
            'introformat'   => 1,
            'timemodified'  => max(array_column($pages, 'timemodified')),
            'editbegin'     => 0,
            'editend'       => 0,

            'wikimode'        => 'collaborative',
            'defaultformat'   => 'html',
            'forceformat'     => 1,
            'firstpagetitle'  => $firstTitle ?? 'Home',
            'timecreated'     => min(array_column($pages, 'timecreated')),
            'timemodified2'   => max(array_column($pages, 'timemodified')),

            'pages'           => $pages,
            'userids'         => array_keys($users),
        ];
    }

    /**
     * Write activities/wiki_{id}/wiki.xml
     * NOTE: We ensure non-null <cachedcontent> and present <userid> at <page> level
     * to satisfy Moodle restore expectations (mdl_wiki_pages.userid and cachedcontent NOT NULL).
     */
    private function createWikiXml(array $d, string $dir): void
    {
        $admin = MoodleExport::getAdminUserData();
        $adminId = (int)($admin['id'] ?? 2);
        if ($adminId <= 0) { $adminId = 2; }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<activity id="'.(int)$d['id'].'" moduleid="'.(int)$d['moduleid'].'" modulename="wiki" contextid="'.(int)($this->course->info['real_id'] ?? 0).'">'.PHP_EOL;
        $xml .= '  <wiki id="'.(int)$d['id'].'">'.PHP_EOL;
        $xml .= '    <name>'.$this->h($d['name']).'</name>'.PHP_EOL;
        $xml .= '    <intro><![CDATA['.$d['intro'].']]></intro>'.PHP_EOL;
        $xml .= '    <introformat>'.(int)($d['introformat'] ?? 1).'</introformat>'.PHP_EOL;
        $xml .= '    <wikimode>'.$this->h((string)$d['wikimode']).'</wikimode>'.PHP_EOL;
        $xml .= '    <defaultformat>'.$this->h((string)$d['defaultformat']).'</defaultformat>'.PHP_EOL;
        $xml .= '    <forceformat>'.(int)$d['forceformat'].'</forceformat>'.PHP_EOL;
        $xml .= '    <firstpagetitle>'.$this->h((string)$d['firstpagetitle']).'</firstpagetitle>'.PHP_EOL;
        $xml .= '    <timecreated>'.(int)$d['timecreated'].'</timecreated>'.PHP_EOL;
        $xml .= '    <timemodified>'.(int)$d['timemodified2'].'</timemodified>'.PHP_EOL;
        $xml .= '    <editbegin>'.(int)($d['editbegin'] ?? 0).'</editbegin>'.PHP_EOL;
        $xml .= '    <editend>'.(int)($d['editend'] ?? 0).'</editend>'.PHP_EOL;

        // single subwiki (no groups/users)
        $xml .= '    <subwikis>'.PHP_EOL;
        $xml .= '      <subwiki id="1">'.PHP_EOL;
        $xml .= '        <groupid>0</groupid>'.PHP_EOL;
        $xml .= '        <userid>0</userid>'.PHP_EOL;

        // pages
        $xml .= '        <pages>'.PHP_EOL;
        foreach ($d['pages'] as $i => $p) {
            $pid = (int)$p['id'];
            $pageUserId = (int)($p['userid'] ?? 0);
            if ($pageUserId <= 0) { $pageUserId = $adminId; } // fallback user id

            // Ensure non-empty cachedcontent; Moodle expects NOT NULL.
            $pageHtml = trim((string)($p['content'] ?? ''));
            if ($pageHtml === '') { $pageHtml = '<p></p>'; }

            $xml .= '          <page id="'.$pid.'">'.PHP_EOL;
            $xml .= '            <title>'.$this->h((string)$p['title']).'</title>'.PHP_EOL;
            $xml .= '            <userid>'.$pageUserId.'</userid>'.PHP_EOL; // <-- new: page-level userid
            $xml .= '            <cachedcontent><![CDATA['.$pageHtml.']]></cachedcontent>'.PHP_EOL; // <-- not NULL
            $xml .= '            <timecreated>'.(int)$p['timecreated'].'</timecreated>'.PHP_EOL;
            $xml .= '            <timemodified>'.(int)$p['timemodified'].'</timemodified>'.PHP_EOL;
            $xml .= '            <firstversionid>'.$pid.'</firstversionid>'.PHP_EOL;

            // one version
            $xml .= '            <versions>'.PHP_EOL;
            $xml .= '              <version id="'.$pid.'">'.PHP_EOL;
            $xml .= '                <content><![CDATA['.$pageHtml.']]></content>'.PHP_EOL;
            $xml .= '                <contentformat>'.$this->h((string)$p['contentformat']).'</contentformat>'.PHP_EOL;
            $xml .= '                <version>'.(int)$p['version'].'</version>'.PHP_EOL;
            $xml .= '                <timecreated>'.(int)$p['timecreated'].'</timecreated>'.PHP_EOL;
            $xml .= '                <userid>'.$pageUserId.'</userid>'.PHP_EOL;
            $xml .= '              </version>'.PHP_EOL;
            $xml .= '            </versions>'.PHP_EOL;

            $xml .= '          </page>'.PHP_EOL;
        }
        $xml .= '        </pages>'.PHP_EOL;

        $xml .= '      </subwiki>'.PHP_EOL;
        $xml .= '    </subwikis>'.PHP_EOL;

        $xml .= '  </wiki>'.PHP_EOL;
        $xml .= '</activity>';

        $this->createXmlFile('wiki', $xml, $dir);
    }

    /** Normalize HTML like LabelExport: rewrite /document/... to @@PLUGINFILE@@/<file>. */
    private function normalizeContent(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        // srcset
        $html = (string)preg_replace_callback(
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

        // generic attributes
        $html = (string)preg_replace_callback(
            '~\b(src|href|poster|data)\s*=\s*([\'"])([^\'"]+)\2~i',
            fn(array $m) => $m[1].'='.$m[2].$this->rewriteDocUrl($m[3]).$m[2],
            $html
        );

        // inline CSS
        $html = (string)preg_replace_callback(
            '~\bstyle\s*=\s*([\'"])(.*?)\1~is',
            function (array $m): string {
                $q = $m[1]; $style = $m[2];
                $style = (string)preg_replace_callback(
                    '~url\((["\']?)([^)\'"]+)\1\)~i',
                    fn(array $mm) => 'url('.$mm[1].$this->rewriteDocUrl($mm[2]).$mm[1].')',
                    $style
                );
                return 'style='.$q.$style.$q;
            },
            $html
        );

        // <style> blocks
        $html = (string)preg_replace_callback(
            '~(<style\b[^>]*>)(.*?)(</style>)~is',
            function (array $m): string {
                $open = $m[1]; $css = $m[2]; $close = $m[3];
                $css = (string)preg_replace_callback(
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

    /** Replace Chamilo /document URLs by @@PLUGINFILE@@/basename */
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

    private function toTimestamp(string $value, int $fallback): int
    {
        if ($value === '') { return $fallback; }
        if (\is_numeric($value)) { return (int)$value; }
        $t = strtotime($value);
        return false !== $t ? (int)$t : $fallback;
    }

    private function h(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
