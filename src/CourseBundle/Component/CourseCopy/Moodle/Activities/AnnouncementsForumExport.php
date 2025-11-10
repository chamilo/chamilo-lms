<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleExport;

use const PHP_EOL;

/**
 * AnnouncementsForumExport
 *
 * Exports Chamilo announcements as a Moodle "News forum" (type=news),
 * using the same activity skeleton used by other exporters (module.xml,
 * inforef.xml, optional XMLs) and the same discussions/posts layout
 * used by ForumExport (discussions inside forum.xml).
 */
class AnnouncementsForumExport extends ActivityExport
{
    /** Synthetic module ID default if caller passes 0 */
    public const DEFAULT_MODULE_ID = 48000001;

    /**
     * Export announcements as a News forum activity.
     *
     * @param int    $activityId Unused (kept for signature compatibility)
     * @param string $exportDir  Destination base directory of the export
     * @param int    $moduleId   Module id used to name the activity folder
     * @param int    $sectionId  Moodle section id where the activity will live
     */
    public function export(int $activityId, string $exportDir, int $moduleId, int $sectionId): void
    {
        $moduleId = $moduleId > 0 ? $moduleId : self::DEFAULT_MODULE_ID;
        $forumDir = $this->prepareActivityDirectory($exportDir, 'forum', $moduleId);

        // Build forum payload from announcements
        $forumData = $this->getDataFromAnnouncements($moduleId, $sectionId);

        // Primary XMLs
        $this->createForumXml($forumData, $forumDir);
        $this->createModuleXml($forumData, $forumDir);
        $this->createInforefXml($forumData, $forumDir);

        // Optional skeletons (keeps structure consistent)
        $this->createFiltersXml($forumData, $forumDir);
        $this->createGradesXml($forumData, $forumDir);
        $this->createGradeHistoryXml($forumData, $forumDir);
        $this->createCompletionXml($forumData, $forumDir);
        $this->createCommentsXml($forumData, $forumDir);
        $this->createCompetenciesXml($forumData, $forumDir);
        $this->createRolesXml($forumData, $forumDir);
        $this->createCalendarXml($forumData, $forumDir);
    }

    /** Build forum data (1 discussion per announcement). */
    private function getDataFromAnnouncements(int $moduleId, int $sectionId): array
    {
        $anns = $this->collectAnnouncements();

        // Use export admin user; fallback to 2 (typical Moodle admin id)
        $adminData = MoodleExport::getAdminUserData();
        $adminId   = (int) ($adminData['id'] ?? 2);
        if ($adminId <= 0) {
            $adminId = 2;
        }

        $threads = [];
        $postId = 1;
        $discId = 1;

        foreach ($anns as $a) {
            $created = (int) ($a['created_ts'] ?? time());
            $subject = (string) ($a['subject'] ?? 'Announcement');
            $message = (string) ($a['message'] ?? '');

            // One discussion per announcement, one post inside (by admin export user)
            $threads[] = [
                'id'           => $discId,
                'title'        => $subject,
                'userid'       => $adminId,
                'timemodified' => $created,
                'usermodified' => $adminId,
                'firstpost'    => $postId,
                'posts'        => [[
                    'id'       => $postId,
                    'parent'   => 0,
                    'userid'   => $adminId,
                    'created'  => $created,
                    'modified' => $created,
                    'mailed'   => 0,
                    'subject'  => $subject,
                    // Keep rich HTML safely
                    'message'  => $message,
                ]],
            ];

            $postId++;
            $discId++;
        }

        return [
            // Identity & placement
            'id'            => $moduleId,
            'moduleid'      => $moduleId,
            'modulename'    => 'forum',
            'contextid'     => (int) ($this->course->info['real_id'] ?? 0),
            'sectionid'     => $sectionId,
            'sectionnumber' => 1,

            // News forum config
            'name'           => 'Announcements',
            'description'    => '',
            'type'           => 'news',
            'forcesubscribe' => 1,

            // Timing
            'timecreated'  => time(),
            'timemodified' => time(),

            // Content
            'threads' => $threads,

            // Refs → drives users.xml + userinfo=1
            'users' => [$adminId],
            'files' => [],
        ];
    }

    /** Same shape as ForumExport but type=news and CDATA for HTML messages. */
    private function createForumXml(array $data, string $forumDir): void
    {
        $introCdata = '<![CDATA['.(string) $data['description'].']]>';

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<activity id="'.$data['id'].'" moduleid="'.$data['moduleid'].'" modulename="forum" contextid="'.$data['contextid'].'">'.PHP_EOL;
        $xml .= '  <forum id="'.$data['id'].'">'.PHP_EOL;
        $xml .= '    <type>'.htmlspecialchars((string) ($data['type'] ?? 'news')).'</type>'.PHP_EOL;
        $xml .= '    <name>'.htmlspecialchars((string) $data['name']).'</name>'.PHP_EOL;
        $xml .= '    <intro>'.$introCdata.'</intro>'.PHP_EOL;
        $xml .= '    <introformat>1</introformat>'.PHP_EOL;
        $xml .= '    <duedate>0</duedate>'.PHP_EOL;
        $xml .= '    <cutoffdate>0</cutoffdate>'.PHP_EOL;
        $xml .= '    <assessed>0</assessed>'.PHP_EOL;
        $xml .= '    <assesstimestart>0</assesstimestart>'.PHP_EOL;
        $xml .= '    <assesstimefinish>0</assesstimefinish>'.PHP_EOL;
        $xml .= '    <scale>100</scale>'.PHP_EOL;
        $xml .= '    <maxbytes>512000</maxbytes>'.PHP_EOL;
        $xml .= '    <maxattachments>9</maxattachments>'.PHP_EOL;
        $xml .= '    <forcesubscribe>'.(int) ($data['forcesubscribe'] ?? 1).'</forcesubscribe>'.PHP_EOL;
        $xml .= '    <trackingtype>1</trackingtype>'.PHP_EOL;
        $xml .= '    <rsstype>0</rsstype>'.PHP_EOL;
        $xml .= '    <rssarticles>0</rssarticles>'.PHP_EOL;
        $xml .= '    <timemodified>'.$data['timemodified'].'</timemodified>'.PHP_EOL;
        $xml .= '    <warnafter>0</warnafter>'.PHP_EOL;
        $xml .= '    <blockafter>0</blockafter>'.PHP_EOL;
        $xml .= '    <blockperiod>0</blockperiod>'.PHP_EOL;
        $xml .= '    <completiondiscussions>0</completiondiscussions>'.PHP_EOL;
        $xml .= '    <completionreplies>0</completionreplies>'.PHP_EOL;
        $xml .= '    <completionposts>0</completionposts>'.PHP_EOL;
        $xml .= '    <displaywordcount>0</displaywordcount>'.PHP_EOL;
        $xml .= '    <lockdiscussionafter>0</lockdiscussionafter>'.PHP_EOL;
        $xml .= '    <grade_forum>0</grade_forum>'.PHP_EOL;

        $xml .= '    <discussions>'.PHP_EOL;
        foreach ($data['threads'] as $thread) {
            $xml .= '      <discussion id="'.$thread['id'].'">'.PHP_EOL;
            $xml .= '        <name>'.htmlspecialchars((string) $thread['title']).'</name>'.PHP_EOL;
            $xml .= '        <firstpost>'.(int) $thread['firstpost'].'</firstpost>'.PHP_EOL;
            $xml .= '        <userid>'.$thread['userid'].'</userid>'.PHP_EOL;
            $xml .= '        <groupid>-1</groupid>'.PHP_EOL;
            $xml .= '        <assessed>0</assessed>'.PHP_EOL;
            $xml .= '        <timemodified>'.$thread['timemodified'].'</timemodified>'.PHP_EOL;
            $xml .= '        <usermodified>'.$thread['usermodified'].'</usermodified>'.PHP_EOL;
            $xml .= '        <timestart>0</timestart>'.PHP_EOL;
            $xml .= '        <timeend>0</timeend>'.PHP_EOL;
            $xml .= '        <pinned>0</pinned>'.PHP_EOL;
            $xml .= '        <timelocked>0</timelocked>'.PHP_EOL;

            $xml .= '        <posts>'.PHP_EOL;
            foreach ($thread['posts'] as $post) {
                $xml .= '          <post id="'.$post['id'].'">'.PHP_EOL;
                $xml .= '            <parent>'.(int) $post['parent'].'</parent>'.PHP_EOL;
                $xml .= '            <userid>'.$post['userid'].'</userid>'.PHP_EOL;
                $xml .= '            <created>'.$post['created'].'</created>'.PHP_EOL;
                $xml .= '            <modified>'.$post['modified'].'</modified>'.PHP_EOL;
                $xml .= '            <mailed>'.(int) $post['mailed'].'</mailed>'.PHP_EOL;
                $xml .= '            <subject>'.htmlspecialchars((string) $post['subject']).'</subject>'.PHP_EOL;
                $xml .= '            <message><![CDATA['.$post['message'].']]></message>'.PHP_EOL;
                $xml .= '            <messageformat>1</messageformat>'.PHP_EOL;
                $xml .= '            <messagetrust>0</messagetrust>'.PHP_EOL;
                $xml .= '            <attachment></attachment>'.PHP_EOL;
                $xml .= '            <totalscore>0</totalscore>'.PHP_EOL;
                $xml .= '            <mailnow>0</mailnow>'.PHP_EOL;
                $xml .= '            <privatereplyto>0</privatereplyto>'.PHP_EOL;
                $xml .= '            <ratings></ratings>'.PHP_EOL;
                $xml .= '          </post>'.PHP_EOL;
            }
            $xml .= '        </posts>'.PHP_EOL;

            $xml .= '        <discussion_subs>'.PHP_EOL;
            $xml .= '          <discussion_sub id="'.$thread['id'].'">'.PHP_EOL;
            $xml .= '            <userid>'.$thread['userid'].'</userid>'.PHP_EOL;
            $xml .= '            <preference>'.$thread['timemodified'].'</preference>'.PHP_EOL;
            $xml .= '          </discussion_sub>'.PHP_EOL;
            $xml .= '        </discussion_subs>'.PHP_EOL;

            $xml .= '      </discussion>'.PHP_EOL;
        }
        $xml .= '    </discussions>'.PHP_EOL;

        $xml .= '  </forum>'.PHP_EOL;
        $xml .= '</activity>';

        $this->createXmlFile('forum', $xml, $forumDir);
    }

    /**
     * Collect announcements from CourseBuilder bag.
     *
     * Supports multiple bucket names and shapes defensively:
     * - resources[RESOURCE_ANNOUNCEMENT] or resources['announcements'] or ['announcement']
     * - items wrapped as {obj: …} or direct objects/arrays
     */
    private function collectAnnouncements(): array
    {
        $res = \is_array($this->course->resources ?? null) ? $this->course->resources : [];

        $bag =
            ($res[\defined('RESOURCE_ANNOUNCEMENT') ? RESOURCE_ANNOUNCEMENT : 'announcements'] ?? null)
            ?? ($res['announcements'] ?? null)
            ?? ($res['announcement'] ?? null)
            ?? [];

        $out = [];
        foreach ((array) $bag as $maybe) {
            $o = $this->unwrap($maybe);
            if (!$o) { continue; }

            $title = $this->firstNonEmpty($o, ['title','name','subject'], 'Announcement');
            $html  = $this->firstNonEmpty($o, ['content','message','description','text','body'], '');
            if ($html === '') { continue; }

            $ts = $this->firstTimestamp($o, ['created','ctime','date','add_date','time']);
            $out[] = ['subject' => $title, 'message' => $html, 'created_ts' => $ts];
        }

        return $out;
    }

    private function unwrap(mixed $maybe): ?object
    {
        if (\is_object($maybe)) {
            return (isset($maybe->obj) && \is_object($maybe->obj)) ? $maybe->obj : $maybe;
        }
        if (\is_array($maybe)) {
            return (object) $maybe;
        }
        return null;
    }

    private function firstNonEmpty(object $o, array $keys, string $fallback = ''): string
    {
        foreach ($keys as $k) {
            if (!empty($o->{$k}) && \is_string($o->{$k})) {
                $v = trim((string) $o->{$k});
                if ($v !== '') { return $v; }
            }
        }
        return $fallback;
    }

    private function firstTimestamp(object $o, array $keys): int
    {
        foreach ($keys as $k) {
            if (isset($o->{$k})) {
                $v = $o->{$k};
                if (\is_numeric($v)) { return (int) $v; }
                if (\is_string($v)) {
                    $t = strtotime($v);
                    if (false !== $t) { return (int) $t; }
                }
            }
        }
        return time();
    }
}
