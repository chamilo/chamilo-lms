<?php

namespace Chash\Command\Files;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class DeleteMultiUrlCommand
 * Clean the files and database from one URL of the multi-URLs list, avoiding
 * all resources used by more than one URL, but trying to disassociate them
 * progressively
 * @package Chash\Command\Files
 * @todo Add support for version 2.*
 */
class DeleteMultiUrlCommand extends CommonDatabaseCommand
{
    /**
     * Define options for the command
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('files:delete_multi_url')
            ->setDescription('Deletes one URL out of a multi-url campus')
            ->addArgument(
                'url',
                InputArgument::OPTIONAL,
                'The ID of the URL to be deleted'
            )
            ->addOption(
                'list',
                null,
                InputOption::VALUE_NONE,
                'Show the list of multi-url portals'
            )
            ->addOption(
                'show',
                null,
                InputOption::VALUE_NONE,
                'Show the list of courses directories to be deleted'
            )
            ->addOption(
                'du',
                null,
                InputOption::VALUE_NONE,
                'Show disk usage for each course (only with --show)'
            )
            ->addOption(
                'delete-users',
                null,
                InputOption::VALUE_NONE,
                'Also delete users that are only defined in the main portal and the given URL'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool|int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if ($input->isInteractive()) {
            $this->writeCommandHeader($output, 'Deleting URL');
            $list = $input->getOption('list'); //1 if the option was set
            $connection = $this->getConnection($input);
            $sql = "SELECT * FROM access_url";
            $stmt = $connection->query($sql);
            $urls = array();
            while ($row = $stmt->fetch()) {
                $urls[$row['id']] = array(
                    'url' => $row['url'],
                    'active' => $row['active'],
                );
            }
            if ($list) {
                if (count($urls) > 1) {
                    $output->writeln('ID' . "\t" . 'Active?' . "\t" . 'URL');
                    $preventDelete = '';
                    foreach ($urls as $id => $url) {
                        if ($id == 1) {
                            $preventDelete = '(cannot be deleted!)';
                        } else {
                            $preventDelete = '';
                        }
                        $output->writeln(
                            $id . "\t" .
                            $url['active'] . "\t" .
                            $url['url'] . "\t" .
                            $preventDelete
                        );
                    }
                } else {
                    $output->writeln('Only one URL. Please use another command to delete');
                    return;
                }
            }

            $show = $input->getOption('show'); //1 if the option was set

            // Get all courses by URL
            $sql = "SELECT u.course_code, u.access_url_id, c.directory "
                . " FROM access_url_rel_course u, course c "
                . " WHERE u.course_code = c.code "
                //." WHERE access_url_id != 1 "
                . " ORDER BY access_url_id, course_code ASC";
            $stmt = $connection->query($sql);
            $urlCourses = array();
            $coursesUrl = array();
            $coursesDir = array();
            while ($row = $stmt->fetch()) {
                $urlCourses[$row['access_url_id']][] = $row['course_code'];
                $coursesUrl[$row['course_code']][] = $row['access_url_id'];
                $coursesDir[$row['course_code']] = $row['directory'];
            }

            $urlId = $input->getArgument('url');
            $du = $input->getOption('du'); //1 if the option was set
            $sysPath = $this->getConfigurationHelper()->getSysPath();
            $coursesPath = $sysPath.'courses/';
            $totalDiskUsage = 0;

            if ($show) {
                $output->writeln('');
                $count = count($urlCourses);
                if ($count > 0) {
                    $output->writeln('List of URLs vs courses');
                    $output->writeln('URL ID' . "\t" . 'Only in this URL?' . "\t" . ($du ? 'Size (KB)' . "\t\t" : '') . 'Course code' );
                    foreach ($urlCourses as $url => $courses) {
                        if (!empty($urlId)) {
                            // if a URL was defined, skip other URLs
                            if ($url != $urlId) {
                                continue;
                            }
                        }
                        foreach ($courses as $code) {
                            $countUrl = count($coursesUrl[$code]);
                            $unique = ($countUrl <= 1 ? 'yes' : 'no');
                            $diskUsage = '';
                            if ($du) {
                                $courseDir = $coursesPath.$coursesDir[$code];
                                if (!is_dir($courseDir)) {
                                    $size = 'N/A';
                                } else {
                                    $res = @exec('du -s ' . $courseDir);
                                    $res = preg_split('/\s/', $res);
                                    $size = $res[0];
                                    if ($unique == 'yes') {
                                        $totalDiskUsage += $size;
                                    }
                                }
                            }
                            $output->writeln($url . "\t" . $unique . "\t\t\t" . ($du ? $size . "\t\t" : '') . $code);
                        }
                    }
                }
            }
            if ($du) {
                $output->writeln('Total size of courses only in this URL: '.$totalDiskUsage);
            }

            if (!empty($urlId)) {
                $output->writeln('');
                if ($urlId == 1) {
                    $output->writeln('URL 1 cannot be deleted as it is the main URL');
                    return;
                }
                $output->writeln('Selected URL: ' . $urlId . ' (' . $urls[$urlId]['url'] . ')');
                if (!in_array($urlId, array_keys($urls))) {
                    $output->writeln(
                        'URL ' . $urlId . ' does not exist. ' .
                        'Please use the --list param to see the list of available URLs'
                    );
                    return;
                }
                $dialog = $this->getHelperSet()->get('dialog');
                if (!$dialog->askConfirmation(
                    $output,
                    '<question>Are you sure you want to delete URL ' . $urlId . ' and all the courses that are used only in this URL? (y/N)</question>',
                    false
                )
                ) {
                    return;
                }
                // Now get the list of courses for that URL, and check, for each
                // course, whether it is used in just one or more URLs.
                // If the course is used in just the one URL that was scheduled
                // for deletion, then delete it and its relations with sessions.
                // If it is available in other URLs, only delete its relations
                // to the given URLs
                if (count($urlCourses) > 0 && isset($urlCourses[$urlId]) && count($urlCourses[$urlId]) > 0) {
                    foreach ($urlCourses[$urlId] as $courseCode) {
                        if (count($coursesUrl[$courseCode]) > 1) {
                            $output->writeln(
                                'Course ' . $courseCode . ' is used ' .
                                'by more than one URL (' .
                                implode(',', $coursesUrl[$courseCode]) .
                                ').'
                            );
                            $output->writeln(
                                'Deleting only references to given URL (' . $urlId . ')...'
                            );
                            $this->unlinkCourse($input, $output, $courseCode, $urlId);
                        } else { // The course is only in the given URL
                            $output->writeln(
                                'Course ' . $courseCode . ' is only used ' .
                                'on this portal. Proceeding with deletion...'
                            );
                            $this->unlinkCourse($input, $output, $courseCode, $urlId);
                            $this->deleteCourse($input, $output, $courseCode);
                        }
                    }
                }
                // We removed all courses from that unique URL, so check if
                // there are any users left and delete the access URL reference
                $deleteUsers = $input->getOption('delete-users'); //1 if the option was set
                if ($deleteUsers) {
                    // Check if some users are *only* in the given URL (if so,
                    // remove them)
                    $urlUsers = array();
                    $userUrls = array();
                    $sql = 'SELECT access_url_id, user_id FROM access_url_rel_user ORDER BY access_url_id, user_id';
                    $stmt = $connection->query($sql);
                    while ($row = $stmt->fetch()) {
                        $urlUsers[$row['access_url_id']][] = $row['user_id'];
                        $userUrls[$row['user_id']][] = $row['access_url_id'];
                    }
                    foreach ($userUrls as $user => $urls) {
                        if (count($urls) > 1) {
                            // user is in more than one URL, so only remove
                            // reference to this URL
                            $sql = "DELETE FROM access_url_rel_user where access_url_id = $urlId AND user_id = $user";
                            $connection->query($sql);
                        } else {
                            if ($urls[0] == $urlId) {
                                $output->writeln('User ' . $user . ' is only in URL ' . $urlId . ', deleting...');
                                // DELETE user
                                $this->deleteUser($input, $output, $user);
                            } else {
                                $output->writeln('User ' . $user . 'is in only one URL, but not this one. Skipping.');
                            }
                        }
                    }
                }
                // Everything removed. Delete URL
                $sql = 'DELETE FROM access_url WHERE id = ' . $urlId;
                $connection->query($sql);
            }
        }
    }

    /**
     * Delete all references to a course inside a given URL, but do not delete
     * the course itself
     * @param   OutputInterface $output
     * @param   string  $courseCode
     * @param   int     $urlId
     * @return  bool
     */
    private function unlinkCourse($input, OutputInterface $output, $courseCode, $urlId)
    {
        /* Check tables:
         * session, session_rel_course, session_rel_course_rel_user
         * (if the session has only one course, delete it as well)
         * course_rel_user (if user is only in the given URL)
         */
        $connection = $this->getConnection($input);
        // 1. List the sessions in that URL that include this course
        $sql = "SELECT src.id_session 
                FROM session_rel_course src 
                JOIN access_url_rel_session aurs 
                ON aurs.session_id = src.id_session 
                WHERE 
                    src.course_code = '" . $courseCode . "' AND 
                    aurs.access_url_id = $urlId";
        $stmt = $connection->query($sql);
        $sessions = array();
        while ($row = $stmt->fetch()) {
            $sessions[] = $row['id_session'];
        }
        $output->writeln(
            'Sessions using course '.$courseCode.' in URL '.$urlId.': '.implode(
                ',',
                $sessions
            )
        );

        // 2. Delete the session_rel_course and session_rel_course_rel_user
        foreach ($sessions as $sessionId) {
            $sql = "DELETE FROM session_rel_course_rel_user "
                . "WHERE id_session = $sessionId "
                . " AND course_code = '$courseCode' ";
            $stmt = $connection->query($sql);
            $sql = "DELETE FROM session_rel_course "
                . "WHERE id_session = $sessionId "
                . " AND course_code = '$courseCode' ";
            $stmt = $connection->query($sql);
            $sql = "SELECT count(*) as courseCount FROM session_rel_course WHERE id_session = $sessionId";
            $stmt = $connection->query($sql);
            while ($row = $stmt->fetch()) {
                if ($row['courseCount'] === 0) {
                    $output->writeln('No course left in session ' . $sessionId . ' so deleting the session');
                    // No course within this session => delete the session
                    // @todo: use sessionmanager::delete_session($sessionId)
                    $sqlDelete = "DELETE FROM session WHERE id = $sessionId";
                    $stmtDelete = $connection->query($sqlDelete);
                    $sqlDelete = "DELETE FROM session_rel_course_rel_user WHERE id_session = $sessionId";
                    $stmtDelete = $connection->query($sqlDelete);
                    $sqlDelete = "DELETE FROM session_rel_user WHERE id_session = $sessionId";
                    $stmtDelete = $connection->query($sqlDelete);
                    $sqlDelete = "DELETE FROM session_rel_course WHERE id_session = $sessionId";
                    $stmtDelete = $connection->query($sqlDelete);
                    $sqlDelete = "DELETE FROM access_url_rel_session WHERE session_id = $sessionId";
                    $stmtDelete = $connection->query($sqlDelete);
                    $sqlDelete = "DELETE FROM session_field_values WHERE session_id = $sessionId";
                    $stmtDelete = $connection->query($sqlDelete);
                    $sqlDelete = "DELETE FROM session WHERE id = $sessionId";
                    $stmtDelete = $connection->query($sqlDelete);
                }
            }
        }

        // 3. Delete the access_url_rel_course reference
        $sql = "DELETE FROM access_url_rel_course "
            . " WHERE access_url_id = $urlId "
            . " AND course_code = '$courseCode'";
        $stmt = $connection->query($sql);
    }

    /**
     * Delete a course completely
     * This operation follows the "unlink course" operation so that it just
     * completes it, but only in case the course is used only once.
     * @param   object  Output interface
     * @param   string  Course code
     * @return  bool
     */
    private function deleteCourse($input, OutputInterface $output, $courseCode)
    {
        $connection = $this->getConnection($input);
        $sql = "SELECT id, directory FROM course WHERE code = '$courseCode'";
        $stmt = $connection->query($sql);
        while ($row = $stmt->fetch()) {
            $cid = $row['id'];
            $courseDir = $row['directory'];
        }
        $tables = array(
            'c_announcement',
            'c_announcement_attachment',
            'c_attendance',
            'c_attendance_calendar',
            'c_attendance_result',
            'c_attendance_sheet',
            'c_attendance_sheet_log',
            'c_blog',
            'c_blog_attachment',
            'c_blog_comment',
            'c_blog_post',
            'c_blog_rating',
            'c_blog_rel_user',
            'c_blog_task',
            'c_blog_task_rel_user',
            'c_calendar_event',
            'c_calendar_event_attachment',
            'c_calendar_event_repeat',
            'c_calendar_event_repeat_not',
            'c_chat_connected',
            'c_course_description',
            'c_course_setting',
            'c_document',
            'c_dropbox_category',
            'c_dropbox_feedback',
            'c_dropbox_file',
            'c_dropbox_person',
            'c_dropbox_post',
            'c_forum_attachment',
            'c_forum_category',
            'c_forum_forum',
            'c_forum_mailcue',
            'c_forum_notification',
            'c_forum_post',
            'c_forum_thread',
            'c_forum_thread_qualify',
            'c_forum_thread_qualify_log',
            'c_glossary',
            'c_group_category',
            'c_group_info',
            'c_group_rel_tutor',
            'c_group_rel_user',
            'c_item_property',
            'c_link',
            'c_link_category',
            'c_lp',
            'c_lp_item',
            'c_lp_item_view',
            'c_lp_iv_interaction',
            'c_lp_iv_objective',
            'c_lp_view',
            'c_metadata',
            'c_notebook',
            'c_online_connected',
            'c_online_link',
            'c_permission_group',
            'c_permission_task',
            'c_permission_user',
            'c_quiz',
            'c_quiz_answer',
            'c_quiz_question',
            'c_quiz_question_category',
            'c_quiz_question_option',
            'c_quiz_question_rel_category',
            'c_quiz_rel_question',
            'c_resource',
            'c_role',
            'c_role_group',
            'c_role_permissions',
            'c_role_user',
            'c_student_publication',
            'c_student_publication_assignment',
            'c_survey',
            'c_survey_answer',
            'c_survey_group',
            'c_survey_invitation',
            'c_survey_question',
            'c_survey_question_option',
            'c_thematic',
            'c_thematic_advance',
            'c_thematic_plan',
            'c_tool',
            'c_tool_intro',
            'c_userinfo_content',
            'c_userinfo_def',
            'c_wiki',
            'c_wiki_conf',
            'c_wiki_discuss',
            'c_wiki_mailcue',
        );
        foreach ($tables as $table) {
            $sql = "DELETE FROM $table WHERE c_id = $cid";
            $stmt = $connection->query($sql);
        }
        $output->writeln(
            'Deleted all references to course ' . $courseCode . ' in c_* tables.'
        );
        $sysPath = $this->getConfigurationHelper()->getSysPath();
        $coursePath = $sysPath . 'courses/' . $courseDir;
        $fs = new Filesystem();
        $fs->remove($coursePath);
        $output->writeln('Removed files from ' . $coursePath);
        return true;
    }

    /**
     * Delete a user from the platform, and all its belongings. This is a
     * very dangerous function that should only be accessible by
     * super-admins. Other roles should only be able to disable a user,
     * which removes access to the platform but doesn't delete anything.
     * @param   object  Output interface
     * @param   int     User ID
     * @return  bool
     * @todo Use UserManager::delete_user() instead
     */
    private function deleteUser($input, OutputInterface $output, $userId)
    {
        // No validation of permissions or variables is done because Chash is
        // only for sysadmins anyway
        $connection = $this->getConnection($input);

        // Unsubscribe the user from all groups in all his courses
        $sql = "SELECT c.id as courseId FROM course c, course_rel_user cu
                WHERE cu.user_id = $userId AND c.code = cu.course_code";
        $stmt = $connection->query($sql);

        while ($course = $stmt->fetch()) {
            $sql = "DELETE FROM c_group_rel_user AND user_id = $userId";
            $stmt2 = $connection->query($sql);
        }

        // Unsubscribe user from usergroup_rel_user
        $sql = "DELETE FROM usergroup_rel_user WHERE user_id = $userId";
        $stmt = $connection->query($sql);

        // Unsubscribe user from all courses
        $sql = "DELETE FROM course_rel_user WHERE user_id = $userId";
        $stmt = $connection->query($sql);

        // Unsubscribe user from all courses in sessions
        $sql = "DELETE FROM session_rel_course_rel_user WHERE id_user = $userId";
        $stmt = $connection->query($sql);

        // If the user was added as a id_coach then set the current admin as coach see BT#
        $sql = "UPDATE session SET id_coach = 1 WHERE id_coach = $userId";
        $stmt = $connection->query($sql);

        $sql = "UPDATE session SET id_coach = 1 WHERE session_admin_id = $userId";
        $stmt = $connection->query($sql);

        // Unsubscribe user from all sessions
        $sql = "DELETE FROM session_rel_user WHERE id_user = $userId";
        $stmt = $connection->query($sql);

        // Delete user picture
        /* TODO: Logic about api_get_setting('split_users_upload_directory') == 'true'
        a user has 4 different sized photos to be deleted. */
        $sysPath = $this->getConfigurationHelper()->getSysPath();
        $sub = '';
        $sql = "SELECT selected_value FROM settings_current WHERE variable = 'split_users_upload_directory'";
        $stmt = $connection->query($sql);
        $row = $stmt->fetch();
        if ($row['selected_value'] == 'true') {
            $sub = substr($userId, 0, 1) . '/';
        }
        $img_path = $sysPath . 'main/upload/users/'. $sub . $userId;
        if (file_exists($img_path)) {
            unlink($img_path);
        }

        // Delete the personal course categories
        $sql = "DELETE FROM user_course_category WHERE user_id = $userId";
        $stmt = $connection->query($sql);

        // Delete user from database
        $sql = "DELETE FROM user WHERE user_id = $userId";
        $stmt = $connection->query($sql);

        // Delete user from the admin table
        $sql = "DELETE FROM admin WHERE user_id = $userId";
        $stmt = $connection->query($sql);

        // Delete the personal agenda-items from this user
        $sql = "DELETE FROM personal_agenda WHERE user = $userId";
        $stmt = $connection->query($sql);

        $sql = "DELETE FROM gradebook_result WHERE user_id = $userId";
        $stmt = $connection->query($sql);

        $sql = "DELETE FROM user_field_values WHERE user_id = $userId";
        $stmt = $connection->query($sql);

        $sql = "DELETE FROM group_rel_user WHERE user_id = $userId";
        $stmt = $connection->query($sql);

        $sql = "DELETE FROM user_rel_user WHERE user_id = $userId";
        $stmt = $connection->query($sql);

        // Removing survey invitation
        //survey_manager::delete_all_survey_invitations_by_user($user_id);

        // Delete students works
        $sql = "DELETE FROM c_student_publication WHERE user_id = $userId AND c_id <> 0";
        $stmt = $connection->query($sql);

        // Add event to system log
        //$user_id_manager = api_get_user_id();
        //event_system(LOG_USER_DELETE, LOG_USER_ID, $userId, api_get_utc_datetime(), $user_id_manager, null, $user_info);
        //event_system(LOG_USER_DELETE, LOG_USER_OBJECT, $user_info, api_get_utc_datetime(), $user_id_manager, null, $user_info);
        $output->writeln('Removed user ' . $userId);
        return true;
    }
}
