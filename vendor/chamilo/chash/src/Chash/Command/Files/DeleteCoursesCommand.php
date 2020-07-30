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
class DeleteCoursesCommand extends CommonDatabaseCommand
{
    /**
     * Define options for the command
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('files:delete_courses')
            ->setDescription('Given an ID, code or category code, deletes one or several courses completely')
            ->addOption(
                'course-id',
                null,
                InputOption::VALUE_REQUIRED,
                'The numerical course ID'
            )
            ->addOption(
                'course-code',
                null,
                InputOption::VALUE_REQUIRED,
                'The literal course code'
            )
            ->addOption(
                'course-category',
                null,
                InputOption::VALUE_REQUIRED,
                'The literal course category code'
            )
            ->addOption(
                'before-date',
                null,
                InputOption::VALUE_REQUIRED,
                'Course creation date in YYYY-MM-DD format to use as filter: delete all created before this date'
            )
            ->addOption(
                'show-disk-usage',
                null,
                InputOption::VALUE_NONE,
                'Show the disk usage of each course'
            )
            ->addOption(
                'delete',
                null,
                InputOption::VALUE_NONE,
                'Use to confirm deletion (otherwise corresponding courses are only printed)'
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
            $this->writeCommandHeader($output, 'Deleting courses');
            $courseId = $input->getOption('course-id'); //1 if the option was set
            $courseCode = $input->getOption('course-code'); //1 if the option was set
            $courseCategory = $input->getOption('course-category'); //1 if the option was set
            $beforeDate = $input->getOption('before-date'); //1 if the option was set
            $confirmDelete = $input->getOption('delete'); //1 if the option was set
            $du = $input->getOption('show-disk-usage'); //1 if the option was set
            $connection = $this->getConnection($input);

            if (empty($courseId) && empty($courseCode) && empty($courseCategory) && empty($beforeDate)) {
                $output->writeln('At least one search criteria (id, code, category or date) must be provided.');
                $output->writeln('Use "--help" param for details.');
                return;
            }

            // Check the courses that match the search criteria
            if (!empty($courseId)) {
                $courseId = intval($courseId);
                $sql = "SELECT id, code, category_code, creation_date
                        FROM course
                        WHERE id = $courseId ";
                if (!empty($beforeDate)) {
                    $output->writeln('ID-based course search: ' . $courseId . ' and date < ' . $beforeDate);
                    $sql .= " AND creation_date < '$beforeDate' ";
                } else {
                    $output->writeln('ID-based course search: ' . $courseId);
                }
                $sql .= " ORDER BY creation_date";
            } elseif (!empty($courseCode)) {
                $sql = "SELECT id, code, category_code, creation_date
                        FROM course
                        WHERE code = '$courseCode' ";
                if (!empty($beforeDate)) {
                    $output->writeln('Code-based course search: ' . $courseCode . ' and date < ' . $beforeDate);
                    $sql .= " AND creation_date < '$beforeDate' ";
                } else {
                    $output->writeln('Code-based course search: ' . $courseCode);
                }
                $sql .= " ORDER BY creation_date";
            } elseif (!empty($courseCategory)) {
                $sql = "SELECT id, code, category_code, creation_date
                        FROM course
                        WHERE category_code = '$courseCategory'";
                if (!empty($beforeDate)) {
                    $output->writeln('Category-based course search: ' . $courseCategory . ' and date < ' . $beforeDate);
                    $sql .= " AND creation_date < '$beforeDate' ";
                } else {
                    $output->writeln('Category-based course search: ' . $courseCategory);
                }
                $sql .= " ORDER BY creation_date";
            } elseif (!empty($beforeDate)) {
                $output->writeln('Category-based course search: ' . $beforeDate);
                $sql = "SELECT id, code, category_code, creation_date
                        FROM course
                        WHERE creation_date < '$beforeDate'
                        ORDER BY creation_date";
            }

            $stmt = $connection->query($sql);
            $courses = array();
            $courseIdsString = '';
            while ($row = $stmt->fetch()) {
                $courses[$row['id']] = array(
                    'code' => $row['code'],
                    'category' => $row['category_code'],
                    'date' => $row['creation_date']
                );
                $courseIdsString .= $row['id'].', ';
            }
            $courseIdsString = substr($courseIdsString, 0, -2);

            if (count($courses) >= 1) {
                $output->writeln('ID' . "\t" . 'Code         ' . "\t\t" . 'Category' . "\t" . 'Creation date');
                foreach ($courses as $id => $course) {
                    $output->writeln(
                        $id . "\t" .
                        $course['code'] . "\t\t" .
                        (empty($course['category'])?'--none--':$course['category']) . "\t" .
                        $course['date']
                    );
                }
            } else {
                $output->writeln('No course found with that criteria. Bye bye.');
                return;
            }



            // Get courses vs URL match and measure disk usage
            $sql = "SELECT c.id, u.course_code, u.access_url_id, c.directory
                    FROM access_url_rel_course u, course c
                    WHERE u.course_code = c.code
                    AND c.id IN ($courseIdsString)
                    ORDER BY access_url_id, course_code ASC";
            $stmt = $connection->query($sql);
            $urlCourses = array();
            $coursesUrl = array();
            $coursesDir = array();
            while ($row = $stmt->fetch()) {
                $urlCourses[$row['access_url_id']][] = $row['id'];
                $coursesUrl[$row['id']][] = $row['access_url_id'];
                $coursesDir[$row['id']] = $row['directory'];
            }
            $urls = array_keys($urlCourses);

            $sysPath = $this->getConfigurationHelper()->getSysPath();
            $coursesPath = $sysPath.'courses/';
            $totalDiskUsage = 0;

            $output->writeln('');
            if ($du) {
                $output->writeln('Listing courses size (in KB)...');
            }
            $count = count($urlCourses);
            if ($count > 0) {
                foreach ($urlCourses as $url => $coursesList) {
                    foreach ($coursesList as $id) {
                        $countUrl = count($coursesUrl[$id]);
                        $unique = ($countUrl <= 1 ? 'yes' : 'no');
                        $diskUsage = '';
                        if ($du) {
                            $courseDir = $coursesPath.$coursesDir[$id];
                            if (!is_dir($courseDir)) {
                                $size = 'N/A';
                            } else {
                                $res = @exec('du -s ' . $courseDir);
                                $res = preg_split('/\s/', $res);
                                $size = $res[0];
                                $output->writeln($id . ":\t" . $size);
                                if ($unique == 'yes') {
                                    $totalDiskUsage += $size;
                                }
                            }
                        }
                    }
                }
            }
            if ($du) {
                $output->writeln('Total size of courses on disk (in KB): '.$totalDiskUsage);
            }

            $output->writeln('');
            $dialog = $this->getHelperSet()->get('dialog');
            if (!$dialog->askConfirmation(
                $output,
                '<question>Are you sure you want to clean the listed courses? (y/N)</question>',
                false
            )
            ) {
                return;
            }

            if (count($urls) > 1) {
                $output->writeln('Detected multi-url. Courses will be unlinked from URLs before being erased.');
            }

            // Proceed with deletion, taking it by URL first

            // If the course is used in just the one URL
            // then delete it and its relations with sessions.
            // If it is available in other URLs, only delete its relations
            // to the given URLs
            foreach ($courses as $id => $course) {
                $cUrls = $coursesUrl[$id];
                if (count($cUrls) > 1) {
                    $output->writeln(
                        'Course ' . $course['code'] . ' is used ' .
                        'by more than one URL (' . implode(',', $coursesUrl[$id]) . ').'
                    );
                }
                foreach ($cUrls as $urlId) {
                    $output->writeln(
                        'Deleting references to course ID ' . $id . ' in URL (url ' . $urlId . ')...'
                    );
                    $this->unlinkCourse($input, $output, $course['code'], $urlId);
                }
                // Removal of the course linking in all URLs is over. Delete the
                // course itself
                $output->writeln('All references clear. Now deleting course ' . $id);
                $this->deleteCourse($intput, $output, $course['code']);
            }
        }
        $output->writeln('');
        $output->writeln('All done. ' . $totalDiskUsage . 'KB have been freed. Bye bye.');
        $output->writeln('');
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
                WHERE src.course_code = '" . $courseCode . "'
                AND aurs.access_url_id = $urlId";
        $stmt = $connection->query($sql);
        $sessions = array();
        while ($row = $stmt->fetch()) {
            $sessions[] = $row['id_session'];
        }
        $output->writeln('Sessions using course ' . $courseCode . ' in URL ' . $urlId . ': ' . implode(',',
                $sessions));

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
        $connection->query($sql);
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
        $output->writeln($sql);
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
            $connection->query($sql);
        }
        $output->writeln(
            'Deleted all references to course ' . $courseCode . ' in c_* tables.'
        );
        $sysPath = $this->getConfigurationHelper()->getSysPath();
        $coursePath = $sysPath . 'courses/' . $courseDir;
        $fs = new Filesystem();
        $fs->remove($coursePath);
        $output->writeln('Removed files from ' . $coursePath);
        // Delete the course itself from the course table
        $sql = "DELETE FROM course WHERE id = $cid";
        $connection->query($sql);
        $output->writeln(
            'Deleted course ' . $courseCode . ' reference in course table.'
        );
        return true;
    }
}
