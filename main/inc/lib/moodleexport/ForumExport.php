<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

/**
 * Class ForumExport.
 *
 * Handles the export of forums within a course.
 */
class ForumExport extends ActivityExport
{
    private static int $embeddedFileGlobalSeq = 0;

    /**
     * Export all forum data into a single Moodle forum activity.
     *
     * @param int    $activityId The ID of the forum.
     * @param string $exportDir  The directory where the forum will be exported.
     * @param int    $moduleId   The ID of the module.
     * @param int    $sectionId  The ID of the section.
     */
    public function export($activityId, $exportDir, $moduleId, $sectionId): void
    {
        $effectiveModuleId = (int) $moduleId;
        if ($effectiveModuleId <= 0) {
            $effectiveModuleId = (int) $activityId;
        }

        $forumDir = $this->prepareActivityDirectory($exportDir, 'forum', $effectiveModuleId);
        $forumData = $this->getData((int) $activityId, (int) $sectionId, $effectiveModuleId);

        if (empty($forumData)) {
            return;
        }

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
     * Get all forum data from the course.
     */
    public function getData(int $forumId, int $sectionId, ?int $moduleId = null): ?array
    {
        if (empty($this->course->resources[RESOURCE_FORUM][$forumId])) {
            return null;
        }

        $forum = $this->course->resources[RESOURCE_FORUM][$forumId]->obj;

        $effectiveModuleId = (int) ($moduleId ?? $forumId);
        if ($effectiveModuleId <= 0) {
            $effectiveModuleId = $forumId;
        }

        $adminData = MoodleExport::getAdminUserData();
        $adminId = (int) ($adminData['id'] ?? 1);

        $name = (string) ($forum->forum_title ?? '');
        if ($sectionId > 0) {
            $name = $this->lpItemTitle($sectionId, RESOURCE_FORUM, $forumId, $name);
        }
        $name = $this->sanitizeMoodleActivityName($name, 255);

        $descriptionResult = $this->extractEmbeddedFilesAndNormalizeContent(
            (string) ($forum->forum_comment ?? ''),
            $effectiveModuleId,
            'mod_forum',
            'intro',
            0,
            fn (int $sequence): int => $this->buildForumEmbeddedFileId()
        );

        $forumFiles = $descriptionResult['files'];
        $threads = [];

        $threadResources = $this->course->resources['thread'] ?? [];
        $postResources = $this->course->resources['post'] ?? [];

        foreach ($threadResources as $threadId => $thread) {
            if ((int) ($thread->obj->forum_id ?? 0) !== $forumId) {
                continue;
            }

            $threadPosts = [];
            foreach ($postResources as $post) {
                if ((int) ($post->obj->thread_id ?? 0) !== (int) $threadId) {
                    continue;
                }

                $postId = (int) ($post->obj->post_id ?? 0);
                $postDate = strtotime((string) ($post->obj->post_date ?? 'now'));
                if ($postDate === false) {
                    $postDate = time();
                }

                $messageResult = $this->extractEmbeddedFilesAndNormalizeContent(
                    (string) ($post->obj->post_text ?? ''),
                    $effectiveModuleId,
                    'mod_forum',
                    'post',
                    $postId,
                    fn (int $sequence): int => $this->buildForumEmbeddedFileId()
                );

                if (!empty($messageResult['files'])) {
                    $forumFiles = array_merge($forumFiles, $messageResult['files']);
                }

                $threadPosts[] = [
                    'id' => $postId,
                    'userid' => $adminId,
                    'message' => $messageResult['content'],
                    'created' => $postDate,
                    'modified' => $postDate,
                ];
            }

            usort($threadPosts, static function (array $a, array $b): int {
                return ((int) $a['created']) <=> ((int) $b['created']);
            });

            $firstPostId = 0;
            foreach ($threadPosts as $index => &$postData) {
                if ($index === 0) {
                    $firstPostId = (int) $postData['id'];
                    $postData['parent'] = 0;
                } else {
                    $postData['parent'] = $firstPostId;
                }

                $postData['mailed'] = 0;
                $postData['subject'] = (string) ($thread->obj->thread_title ?? get_lang('Forum'));
            }
            unset($postData);

            $threadDate = strtotime((string) ($thread->obj->thread_date ?? 'now'));
            if ($threadDate === false) {
                $threadDate = time();
            }

            $threads[] = [
                'id' => (int) ($thread->obj->thread_id ?? 0),
                'title' => (string) ($thread->obj->thread_title ?? ''),
                'firstpost' => $firstPostId,
                'userid' => $adminId,
                'timemodified' => $threadDate,
                'usermodified' => $adminId,
                'posts' => $threadPosts,
            ];
        }

        return [
            'id' => $forumId,
            'moduleid' => $effectiveModuleId,
            'modulename' => 'forum',
            'contextid' => $effectiveModuleId,
            'name' => $name,
            'description' => $descriptionResult['content'],
            'timecreated' => time(),
            'timemodified' => time(),
            'sectionid' => $sectionId,
            'sectionnumber' => 1,
            'userid' => $adminId,
            'threads' => $threads,
            'users' => [$adminId],
            'files' => $forumFiles,
        ];
    }

    /**
     * Create the forum.xml file with all forum data.
     */
    private function createForumXml(array $forumData, string $forumDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity id="'.$forumData['id'].'" moduleid="'.$forumData['moduleid'].'" modulename="'.$forumData['modulename'].'" contextid="'.$forumData['contextid'].'">'.PHP_EOL;
        $xmlContent .= '  <forum id="'.$forumData['id'].'">'.PHP_EOL;
        $xmlContent .= '    <type>general</type>'.PHP_EOL;
        $xmlContent .= '    <name>'.htmlspecialchars((string) $forumData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '    <intro><![CDATA['.(string) $forumData['description'].']]></intro>'.PHP_EOL;
        $xmlContent .= '    <introformat>1</introformat>'.PHP_EOL;
        $xmlContent .= '    <duedate>0</duedate>'.PHP_EOL;
        $xmlContent .= '    <cutoffdate>0</cutoffdate>'.PHP_EOL;
        $xmlContent .= '    <assessed>0</assessed>'.PHP_EOL;
        $xmlContent .= '    <assesstimestart>0</assesstimestart>'.PHP_EOL;
        $xmlContent .= '    <assesstimefinish>0</assesstimefinish>'.PHP_EOL;
        $xmlContent .= '    <scale>100</scale>'.PHP_EOL;
        $xmlContent .= '    <maxbytes>512000</maxbytes>'.PHP_EOL;
        $xmlContent .= '    <maxattachments>9</maxattachments>'.PHP_EOL;
        $xmlContent .= '    <forcesubscribe>0</forcesubscribe>'.PHP_EOL;
        $xmlContent .= '    <trackingtype>1</trackingtype>'.PHP_EOL;
        $xmlContent .= '    <rsstype>0</rsstype>'.PHP_EOL;
        $xmlContent .= '    <rssarticles>0</rssarticles>'.PHP_EOL;
        $xmlContent .= '    <timemodified>'.$forumData['timemodified'].'</timemodified>'.PHP_EOL;
        $xmlContent .= '    <warnafter>0</warnafter>'.PHP_EOL;
        $xmlContent .= '    <blockafter>0</blockafter>'.PHP_EOL;
        $xmlContent .= '    <blockperiod>0</blockperiod>'.PHP_EOL;
        $xmlContent .= '    <completiondiscussions>0</completiondiscussions>'.PHP_EOL;
        $xmlContent .= '    <completionreplies>0</completionreplies>'.PHP_EOL;
        $xmlContent .= '    <completionposts>0</completionposts>'.PHP_EOL;
        $xmlContent .= '    <displaywordcount>0</displaywordcount>'.PHP_EOL;
        $xmlContent .= '    <lockdiscussionafter>0</lockdiscussionafter>'.PHP_EOL;
        $xmlContent .= '    <grade_forum>0</grade_forum>'.PHP_EOL;
        $xmlContent .= '    <discussions>'.PHP_EOL;

        foreach ($forumData['threads'] as $thread) {
            $xmlContent .= '      <discussion id="'.$thread['id'].'">'.PHP_EOL;
            $xmlContent .= '        <name>'.htmlspecialchars((string) $thread['title']).'</name>'.PHP_EOL;
            $xmlContent .= '        <firstpost>'.(int) ($thread['firstpost'] ?? 0).'</firstpost>'.PHP_EOL;
            $xmlContent .= '        <userid>'.$thread['userid'].'</userid>'.PHP_EOL;
            $xmlContent .= '        <groupid>-1</groupid>'.PHP_EOL;
            $xmlContent .= '        <assessed>0</assessed>'.PHP_EOL;
            $xmlContent .= '        <timemodified>'.$thread['timemodified'].'</timemodified>'.PHP_EOL;
            $xmlContent .= '        <usermodified>'.$thread['usermodified'].'</usermodified>'.PHP_EOL;
            $xmlContent .= '        <timestart>0</timestart>'.PHP_EOL;
            $xmlContent .= '        <timeend>0</timeend>'.PHP_EOL;
            $xmlContent .= '        <pinned>0</pinned>'.PHP_EOL;
            $xmlContent .= '        <timelocked>0</timelocked>'.PHP_EOL;
            $xmlContent .= '        <posts>'.PHP_EOL;

            foreach ($thread['posts'] as $post) {
                $xmlContent .= '          <post id="'.$post['id'].'">'.PHP_EOL;
                $xmlContent .= '            <parent>'.(int) ($post['parent'] ?? 0).'</parent>'.PHP_EOL;
                $xmlContent .= '            <userid>'.$post['userid'].'</userid>'.PHP_EOL;
                $xmlContent .= '            <created>'.$post['created'].'</created>'.PHP_EOL;
                $xmlContent .= '            <modified>'.$post['modified'].'</modified>'.PHP_EOL;
                $xmlContent .= '            <mailed>'.(int) ($post['mailed'] ?? 0).'</mailed>'.PHP_EOL;
                $xmlContent .= '            <subject>'.htmlspecialchars((string) ($post['subject'] ?? '')).'</subject>'.PHP_EOL;
                $xmlContent .= '            <message><![CDATA['.(string) ($post['message'] ?? '').']]></message>'.PHP_EOL;
                $xmlContent .= '            <messageformat>1</messageformat>'.PHP_EOL;
                $xmlContent .= '            <messagetrust>0</messagetrust>'.PHP_EOL;
                $xmlContent .= '            <attachment></attachment>'.PHP_EOL;
                $xmlContent .= '            <totalscore>0</totalscore>'.PHP_EOL;
                $xmlContent .= '            <mailnow>0</mailnow>'.PHP_EOL;
                $xmlContent .= '            <privatereplyto>0</privatereplyto>'.PHP_EOL;
                $xmlContent .= '            <ratings>'.PHP_EOL;
                $xmlContent .= '            </ratings>'.PHP_EOL;
                $xmlContent .= '          </post>'.PHP_EOL;
            }

            $xmlContent .= '        </posts>'.PHP_EOL;
            $xmlContent .= '        <discussion_subs>'.PHP_EOL;
            $xmlContent .= '          <discussion_sub id="'.$thread['id'].'">'.PHP_EOL;
            $xmlContent .= '            <userid>'.$thread['userid'].'</userid>'.PHP_EOL;
            $xmlContent .= '            <preference>'.$thread['timemodified'].'</preference>'.PHP_EOL;
            $xmlContent .= '          </discussion_sub>'.PHP_EOL;
            $xmlContent .= '        </discussion_subs>'.PHP_EOL;
            $xmlContent .= '      </discussion>'.PHP_EOL;
        }

        $xmlContent .= '    </discussions>'.PHP_EOL;
        $xmlContent .= '  </forum>'.PHP_EOL;
        $xmlContent .= '</activity>';

        $this->createXmlFile('forum', $xmlContent, $forumDir);
    }

    /**
     * Build a stable embedded file id for forum files.
     */
    private function buildForumEmbeddedFileId(): int
    {
        self::$embeddedFileGlobalSeq++;

        return 1400000000 + self::$embeddedFileGlobalSeq;
    }
}
