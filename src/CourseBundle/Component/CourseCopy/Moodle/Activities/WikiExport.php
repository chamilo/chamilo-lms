<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleExport;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const PHP_EOL;

/**
 * Exports all Chamilo Wiki logical pages and their complete version history
 * as one Moodle Wiki activity.
 */
final class WikiExport extends ActivityExport
{
    public function export(int $activityId, string $exportDir, int $moduleId, int $sectionId): void
    {
        $wikiDir = $this->prepareActivityDirectory($exportDir, 'wiki', $moduleId);
        $data = $this->getData($activityId, $sectionId);
        if (null === $data) {
            return;
        }

        $this->createWikiXml($data, $wikiDir);
        $this->createModuleXml($data, $wikiDir);
        $this->createInforefXml($data, $wikiDir);
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
     * @return array<string, mixed>|null
     */
    public function getData(int $activityId, int $sectionId): ?array
    {
        $bag = $this->course->resources[\defined('RESOURCE_WIKI') ? RESOURCE_WIKI : 'wiki']
            ?? $this->course->resources['wiki']
            ?? [];
        if (!\is_array($bag) || [] === $bag) {
            return null;
        }

        $normalizedPages = (new WikiVersionCollectionNormalizer())->normalize($bag);
        if ([] === $normalizedPages) {
            return null;
        }

        $pages = [];
        $users = [];
        $firstTitle = null;
        $earliest = time();
        $latestTime = 0;

        foreach ($normalizedPages as $normalizedPage) {
            $versions = [];
            foreach ($normalizedPage['versions'] as $versionData) {
                $timestamp = $this->toTimestamp((string) $versionData['timestamp'], time());
                $content = $this->normalizeContent((string) $versionData['content']);
                if ('' === trim($content)) {
                    $content = '<p></p>';
                }
                $userId = (int) $versionData['userid'];
                if ($userId > 0) {
                    $users[$userId] = true;
                }
                $versions[] = [
                    'id' => (int) $versionData['id'],
                    'content' => $content,
                    'contentformat' => 'html',
                    'version' => (int) $versionData['version'],
                    'timecreated' => $timestamp,
                    'userid' => $userId,
                ];
                $earliest = min($earliest, $timestamp);
                $latestTime = max($latestTime, $timestamp);
            }

            if ([] === $versions) {
                continue;
            }

            $firstVersion = $versions[0];
            $latestVersion = $versions[array_key_last($versions)];
            $title = trim((string) $normalizedPage['title']);
            if ('' === $title) {
                $title = 'Wiki page '.(int) $normalizedPage['id'];
            }
            if ('index' === (string) $normalizedPage['reflink']) {
                $firstTitle = $title;
            } elseif (null === $firstTitle) {
                $firstTitle = $title;
            }

            $pages[] = [
                'id' => (int) $normalizedPage['id'],
                'title' => $title,
                'userid' => (int) $latestVersion['userid'],
                'cachedcontent' => (string) $latestVersion['content'],
                'timecreated' => (int) $firstVersion['timecreated'],
                'timemodified' => (int) $latestVersion['timecreated'],
                'firstversionid' => (int) $firstVersion['id'],
                'versions' => $versions,
            ];
        }

        if ([] === $pages) {
            return null;
        }

        return [
            'id' => $activityId,
            'moduleid' => $activityId,
            'modulename' => 'wiki',
            'sectionid' => $sectionId,
            'sectionnumber' => $sectionId,
            'name' => 'Wiki',
            'intro' => '',
            'introformat' => 1,
            'timemodified' => $latestTime > 0 ? $latestTime : time(),
            'editbegin' => 0,
            'editend' => 0,
            'wikimode' => 'collaborative',
            'defaultformat' => 'html',
            'forceformat' => 1,
            'firstpagetitle' => $firstTitle ?? 'Home',
            'timecreated' => $earliest,
            'timemodified2' => $latestTime > 0 ? $latestTime : time(),
            'pages' => $pages,
            'users' => array_keys($users),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createWikiXml(array $data, string $directory): void
    {
        $adminId = (int) (MoodleExport::getAdminUserData()['id'] ?? 2);
        if ($adminId <= 0) {
            $adminId = 2;
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<activity id="'.(int) $data['id'].'" moduleid="'.(int) $data['moduleid'].'" modulename="wiki" contextid="'.(int) ($this->course->info['real_id'] ?? 0).'">'.PHP_EOL;
        $xml .= '  <wiki id="'.(int) $data['id'].'">'.PHP_EOL;
        $xml .= '    <name>'.$this->h((string) $data['name']).'</name>'.PHP_EOL;
        $xml .= '    <intro><![CDATA['.(string) $data['intro'].']]></intro>'.PHP_EOL;
        $xml .= '    <introformat>'.(int) $data['introformat'].'</introformat>'.PHP_EOL;
        $xml .= '    <wikimode>'.$this->h((string) $data['wikimode']).'</wikimode>'.PHP_EOL;
        $xml .= '    <defaultformat>'.$this->h((string) $data['defaultformat']).'</defaultformat>'.PHP_EOL;
        $xml .= '    <forceformat>'.(int) $data['forceformat'].'</forceformat>'.PHP_EOL;
        $xml .= '    <firstpagetitle>'.$this->h((string) $data['firstpagetitle']).'</firstpagetitle>'.PHP_EOL;
        $xml .= '    <timecreated>'.(int) $data['timecreated'].'</timecreated>'.PHP_EOL;
        $xml .= '    <timemodified>'.(int) $data['timemodified2'].'</timemodified>'.PHP_EOL;
        $xml .= '    <editbegin>'.(int) $data['editbegin'].'</editbegin>'.PHP_EOL;
        $xml .= '    <editend>'.(int) $data['editend'].'</editend>'.PHP_EOL;
        $xml .= '    <subwikis>'.PHP_EOL;
        $xml .= '      <subwiki id="1">'.PHP_EOL;
        $xml .= '        <groupid>0</groupid>'.PHP_EOL;
        $xml .= '        <userid>0</userid>'.PHP_EOL;
        $xml .= '        <pages>'.PHP_EOL;

        foreach ($data['pages'] as $page) {
            $pageUserId = (int) ($page['userid'] ?? 0);
            if ($pageUserId <= 0) {
                $pageUserId = $adminId;
            }
            $cachedContent = trim((string) ($page['cachedcontent'] ?? ''));
            if ('' === $cachedContent) {
                $cachedContent = '<p></p>';
            }

            $xml .= '          <page id="'.(int) $page['id'].'">'.PHP_EOL;
            $xml .= '            <title>'.$this->h((string) $page['title']).'</title>'.PHP_EOL;
            $xml .= '            <userid>'.$pageUserId.'</userid>'.PHP_EOL;
            $xml .= '            <cachedcontent><![CDATA['.$cachedContent.']]></cachedcontent>'.PHP_EOL;
            $xml .= '            <timecreated>'.(int) $page['timecreated'].'</timecreated>'.PHP_EOL;
            $xml .= '            <timemodified>'.(int) $page['timemodified'].'</timemodified>'.PHP_EOL;
            $xml .= '            <firstversionid>'.(int) $page['firstversionid'].'</firstversionid>'.PHP_EOL;
            $xml .= '            <versions>'.PHP_EOL;

            foreach ($page['versions'] as $version) {
                $versionUserId = (int) ($version['userid'] ?? 0);
                if ($versionUserId <= 0) {
                    $versionUserId = $adminId;
                }
                $xml .= '              <version id="'.(int) $version['id'].'">'.PHP_EOL;
                $xml .= '                <content><![CDATA['.(string) $version['content'].']]></content>'.PHP_EOL;
                $xml .= '                <contentformat>'.$this->h((string) $version['contentformat']).'</contentformat>'.PHP_EOL;
                $xml .= '                <version>'.(int) $version['version'].'</version>'.PHP_EOL;
                $xml .= '                <timecreated>'.(int) $version['timecreated'].'</timecreated>'.PHP_EOL;
                $xml .= '                <userid>'.$versionUserId.'</userid>'.PHP_EOL;
                $xml .= '              </version>'.PHP_EOL;
            }

            $xml .= '            </versions>'.PHP_EOL;
            $xml .= '          </page>'.PHP_EOL;
        }

        $xml .= '        </pages>'.PHP_EOL;
        $xml .= '      </subwiki>'.PHP_EOL;
        $xml .= '    </subwikis>'.PHP_EOL;
        $xml .= '  </wiki>'.PHP_EOL;
        $xml .= '</activity>';

        $this->createXmlFile('wiki', $xml, $directory);
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
            $rel = ltrim((string) $m[1], '/');

            // Do not rewrite unresolved uuid/view wiki paths.
            if (preg_match('~^files/[0-9a-f-]{36}/(?:view|download|link)$~i', $rel)) {
                return $url;
            }

            return '@@PLUGINFILE@@/'.$rel;
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
