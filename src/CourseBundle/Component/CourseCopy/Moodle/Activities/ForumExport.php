<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleExport;

use const PHP_EOL;

/**
 * Handles the export of forums within a course.
 */
class ForumExport extends ActivityExport
{
    /**
     * Export all forum data into a single Moodle forum activity.
     *
     * @param int    $activityId the ID of the forum
     * @param string $exportDir  destination base directory of the export
     * @param int    $moduleId   module id used to name the activity folder
     * @param int    $sectionId  moodle section id where the activity will live
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        $forumDir = $this->prepareActivityDirectory($exportDir, 'forum', (int) $moduleId);
        $forumData = $this->getData((int) $activityId, (int) $sectionId);

        $this->createForumXml($forumData, $forumDir);
        $this->createModuleXml($forumData, $forumDir);
        $this->createGradesXml($forumData, $forumDir);
        $this->createGradeHistoryXml($forumData, $forumDir);
        $this->createInforefXml($forumData, $forumDir);
        $this->createRolesXml($forumData, $forumDir);
        $this->createCalendarXml($forumData, $forumDir);
        $this->createCommentsXml($forumData, $forumDir);
        $this->createCompetenciesXml($forumData, $forumDir);
        $this->createFiltersXml($forumData, $forumDir);
    }

    /**
     * Build all forum data from the course resources.
     */
    public function getData(int $forumId, int $sectionId): array
    {
        $forumRes = $this->course->resources['forum'][$forumId] ?? null;
        $forumObj = $forumRes ? ($forumRes->obj ?? null) : null;

        $adminData = MoodleExport::getAdminUserData();
        $adminId = (int) ($adminData['id'] ?? 0);

        $catId = 0;
        $catTitle = '';
        if ($forumObj) {
            $catId = (int) ($forumObj->forum_category ?? $forumObj->forum_category_id ?? $forumObj->category_id ?? 0);
        }
        if ($catId > 0) {
            $catRes = $this->course->resources['Forum_Category'][$catId]
                ?? $this->course->resources['forum_category'][$catId]
                ?? null;
            if ($catRes) {
                $src = $catRes->obj ?? $catRes;
                $catTitle = (string) ($src->cat_title ?? $src->title ?? '');
            }
        }

        $threads = [];
        if (!empty($this->course->resources['thread'])) {
            foreach ($this->course->resources['thread'] as $threadId => $thread) {
                if (($thread->obj->forum_id ?? null) != $forumId) {
                    continue;
                }

                // Collect posts for this thread
                $posts = [];
                if (!empty($this->course->resources['post'])) {
                    foreach ($this->course->resources['post'] as $postId => $post) {
                        if (($post->obj->thread_id ?? null) == $threadId) {
                            $created = strtotime((string) ($post->obj->post_date ?? 'now'));
                            $msg = (string) ($post->obj->post_text ?? '');
                            $posts[] = [
                                'id' => (int) ($post->obj->post_id ?? $postId),
                                'parent' => (int) ($post->obj->parent_id ?? 0),
                                'userid' => $adminId,
                                'created' => $created,
                                'modified' => $created,
                                'mailed' => 0,
                                'subject' => $this->buildPostSubject($post->obj->post_title ?? '', $msg),
                                'message' => $msg,
                            ];
                        }
                    }
                }

                // Determine first post id (Moodle expects it)
                $firstpostId = 0;
                if (!empty($posts)) {
                    usort($posts, static fn ($a, $b) => $a['created'] <=> $b['created']);
                    $firstpostId = (int) $posts[0]['id'];
                }

                $threads[] = [
                    'id' => (int) ($thread->obj->thread_id ?? $threadId),
                    'title' => (string) ($thread->obj->thread_title ?? 'Discussion'),
                    'userid' => $adminId,
                    'timemodified' => strtotime((string) ($thread->obj->thread_date ?? 'now')),
                    'usermodified' => $adminId,
                    'firstpost' => $firstpostId,
                    'posts' => $posts,
                ];
            }
        }

        return [
            'id' => $forumId,
            'moduleid' => $forumId,
            'modulename' => 'forum',
            'contextid' => (int) ($this->course->info['real_id'] ?? 0),
            'name' => (string) ($forumObj->forum_title ?? 'Forum'),
            'description' => (string) ($forumObj->forum_comment ?? ''),
            'category_id' => $catId,
            'category_title' => $catTitle,
            'timecreated' => time(),
            'timemodified' => time(),
            'sectionid' => $sectionId,
            'sectionnumber' => 1,
            'userid' => $adminId,
            'threads' => $threads,
            'users' => [$adminId],
            'files' => [],
        ];
    }

    /**
     * Helper to build a short subject when none is provided.
     * Uses the post title, else a trimmed version of the message.
     */
    private function buildPostSubject(string $title, string $message): string
    {
        // Prefer explicit title
        $subject = trim($title);
        if ('' !== $subject) {
            return $subject;
        }

        // Fallback: derive from message
        $plain = trim(strip_tags($message));
        if ('' === $plain) {
            return 'Post';
        }
        $short = mb_substr($plain, 0, 80);

        return $short.(mb_strlen($plain) > 80 ? '…' : '');
    }

    /**
     * Write forum.xml including discussions and posts.
     */
    private function createForumXml(array $forumData, string $forumDir): void
    {
        // ----- intro with category hints (mirrors UrlExport) -----
        $intro = (string) $forumData['description'];
        if (!empty($forumData['category_id'])) {
            $intro .= "\n<!-- CHAMILO2:forum_category_id:{$forumData['category_id']} -->";
            if (!empty($forumData['category_title'])) {
                $intro .= "\n<!-- CHAMILO2:forum_category_title:".
                    htmlspecialchars((string) $forumData['category_title']).' -->';
            }
        }
        $introCdata = '<![CDATA['.$intro.']]>';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<activity id="'.$forumData['id'].'" moduleid="'.$forumData['moduleid'].'" modulename="forum" contextid="'.$forumData['contextid'].'">'.PHP_EOL;
        $xml .= '  <forum id="'.$forumData['id'].'">'.PHP_EOL;
        $xml .= '    <type>general</type>'.PHP_EOL;
        $xml .= '    <name>'.htmlspecialchars((string) $forumData['name']).'</name>'.PHP_EOL;
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
        $xml .= '    <forcesubscribe>0</forcesubscribe>'.PHP_EOL;
        $xml .= '    <trackingtype>1</trackingtype>'.PHP_EOL;
        $xml .= '    <rsstype>0</rsstype>'.PHP_EOL;
        $xml .= '    <rssarticles>0</rssarticles>'.PHP_EOL;
        $xml .= '    <timemodified>'.$forumData['timemodified'].'</timemodified>'.PHP_EOL;
        $xml .= '    <warnafter>0</warnafter>'.PHP_EOL;
        $xml .= '    <blockafter>0</blockafter>'.PHP_EOL;
        $xml .= '    <blockperiod>0</blockperiod>'.PHP_EOL;
        $xml .= '    <completiondiscussions>0</completiondiscussions>'.PHP_EOL;
        $xml .= '    <completionreplies>0</completionreplies>'.PHP_EOL;
        $xml .= '    <completionposts>0</completionposts>'.PHP_EOL;
        $xml .= '    <displaywordcount>0</displaywordcount>'.PHP_EOL;
        $xml .= '    <lockdiscussionafter>0</lockdiscussionafter>'.PHP_EOL;
        $xml .= '    <grade_forum>0</grade_forum>'.PHP_EOL;

        // Discussions
        $xml .= '    <discussions>'.PHP_EOL;
        foreach ($forumData['threads'] as $thread) {
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

            // Posts
            $xml .= '        <posts>'.PHP_EOL;
            foreach ($thread['posts'] as $post) {
                $xml .= '          <post id="'.$post['id'].'">'.PHP_EOL;
                $xml .= '            <parent>'.(int) $post['parent'].'</parent>'.PHP_EOL;
                $xml .= '            <userid>'.$post['userid'].'</userid>'.PHP_EOL;
                $xml .= '            <created>'.$post['created'].'</created>'.PHP_EOL;
                $xml .= '            <modified>'.$post['modified'].'</modified>'.PHP_EOL;
                $xml .= '            <mailed>'.(int) $post['mailed'].'</mailed>'.PHP_EOL;
                $xml .= '            <subject>'.htmlspecialchars((string) $post['subject']).'</subject>'.PHP_EOL;
                $xml .= '            <message>'.htmlspecialchars((string) $post['message']).'</message>'.PHP_EOL;
                $xml .= '            <messageformat>1</messageformat>'.PHP_EOL;
                $xml .= '            <messagetrust>0</messagetrust>'.PHP_EOL;
                $xml .= '            <attachment></attachment>'.PHP_EOL;
                $xml .= '            <totalscore>0</totalscore>'.PHP_EOL;
                $xml .= '            <mailnow>0</mailnow>'.PHP_EOL;
                $xml .= '            <privatereplyto>0</privatereplyto>'.PHP_EOL;
                $xml .= '            <ratings>'.PHP_EOL;
                $xml .= '            </ratings>'.PHP_EOL;
                $xml .= '          </post>'.PHP_EOL;
            }
            $xml .= '        </posts>'.PHP_EOL;

            // Subscriptions (basic placeholder)
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
}
