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
        // Prepare the directory where the forum export will be saved
        $forumDir = $this->prepareActivityDirectory($exportDir, 'forum', $moduleId);

        // Retrieve forum data
        $forumData = $this->getData($activityId, $sectionId);

        // Generate XML files for the forum
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
    public function getData(int $forumId, int $sectionId): ?array
    {
        $forum = $this->course->resources['forum'][$forumId]->obj;

        $adminData = MoodleExport::getAdminUserData();
        $adminId = $adminData['id'];

        $threads = [];
        foreach ($this->course->resources['thread'] as $threadId => $thread) {
            if ($thread->obj->forum_id == $forumId) {
                // Get the posts for each thread
                $posts = [];
                foreach ($this->course->resources['post'] as $postId => $post) {
                    if ($post->obj->thread_id == $threadId) {
                        $posts[] = [
                            'id' => $post->obj->post_id,
                            'userid' => $adminId,
                            'message' => $post->obj->post_text,
                            'created' => strtotime($post->obj->post_date),
                            'modified' => strtotime($post->obj->post_date),
                        ];
                    }
                }

                $threads[] = [
                    'id' => $thread->obj->thread_id,
                    'title' => $thread->obj->thread_title,
                    'userid' => $adminId,
                    'timemodified' => strtotime($thread->obj->thread_date),
                    'usermodified' => $adminId,
                    'posts' => $posts,
                ];
            }
        }

        $fileIds = [];

        return [
            'id' => $forumId,
            'moduleid' => $forumId,
            'modulename' => 'forum',
            'contextid' => $this->course->info['real_id'],
            'name' => $forum->forum_title,
            'description' => $forum->forum_comment,
            'timecreated' => time(),
            'timemodified' => time(),
            'sectionid' => $sectionId,
            'sectionnumber' => 1,
            'userid' => $adminId,
            'threads' => $threads,
            'users' => [$adminId],
            'files' => $fileIds,
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
        $xmlContent .= '    <name>'.htmlspecialchars($forumData['name']).'</name>'.PHP_EOL;
        $xmlContent .= '    <intro>'.htmlspecialchars($forumData['description']).'</intro>'.PHP_EOL;
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

        // Add forum threads
        $xmlContent .= '    <discussions>'.PHP_EOL;
        foreach ($forumData['threads'] as $thread) {
            $xmlContent .= '      <discussion id="'.$thread['id'].'">'.PHP_EOL;
            $xmlContent .= '        <name>'.htmlspecialchars($thread['title']).'</name>'.PHP_EOL;
            $xmlContent .= '        <firstpost>'.$thread['firstpost'].'</firstpost>'.PHP_EOL;
            $xmlContent .= '        <userid>'.$thread['userid'].'</userid>'.PHP_EOL;
            $xmlContent .= '        <groupid>-1</groupid>'.PHP_EOL;
            $xmlContent .= '        <assessed>0</assessed>'.PHP_EOL;
            $xmlContent .= '        <timemodified>'.$thread['timemodified'].'</timemodified>'.PHP_EOL;
            $xmlContent .= '        <usermodified>'.$thread['usermodified'].'</usermodified>'.PHP_EOL;
            $xmlContent .= '        <timestart>0</timestart>'.PHP_EOL;
            $xmlContent .= '        <timeend>0</timeend>'.PHP_EOL;
            $xmlContent .= '        <pinned>0</pinned>'.PHP_EOL;
            $xmlContent .= '        <timelocked>0</timelocked>'.PHP_EOL;

            // Add forum posts to the thread
            $xmlContent .= '        <posts>'.PHP_EOL;
            foreach ($thread['posts'] as $post) {
                $xmlContent .= '          <post id="'.$post['id'].'">'.PHP_EOL;
                $xmlContent .= '            <parent>'.$post['parent'].'</parent>'.PHP_EOL;
                $xmlContent .= '            <userid>'.$post['userid'].'</userid>'.PHP_EOL;
                $xmlContent .= '            <created>'.$post['created'].'</created>'.PHP_EOL;
                $xmlContent .= '            <modified>'.$post['modified'].'</modified>'.PHP_EOL;
                $xmlContent .= '            <mailed>'.$post['mailed'].'</mailed>'.PHP_EOL;
                $xmlContent .= '            <subject>'.htmlspecialchars($post['subject']).'</subject>'.PHP_EOL;
                $xmlContent .= '            <message>'.htmlspecialchars($post['message']).'</message>'.PHP_EOL;
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
            $xmlContent .= '            <discussion_sub id="'.$thread['id'].'">'.PHP_EOL;
            $xmlContent .= '              <userid>'.$thread['userid'].'</userid>'.PHP_EOL;
            $xmlContent .= '              <preference>'.$thread['timemodified'].'</preference>'.PHP_EOL;
            $xmlContent .= '            </discussion_sub>'.PHP_EOL;
            $xmlContent .= '        </discussion_subs>'.PHP_EOL;
            $xmlContent .= '      </discussion>'.PHP_EOL;
        }
        $xmlContent .= '    </discussions>'.PHP_EOL;
        $xmlContent .= '  </forum>'.PHP_EOL;
        $xmlContent .= '</activity>';

        $this->createXmlFile('forum', $xmlContent, $forumDir);
    }
}
