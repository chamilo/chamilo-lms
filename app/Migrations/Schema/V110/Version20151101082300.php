<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Calendar color
 */
class Version20151101082300 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $accessUrl = $schema->getTable('access_url');
        $accessUrl->getColumn('id')->setUnsigned(false);

        $this->connection->executeQuery('UPDATE access_url_rel_course SET access_url_id = NULL WHERE access_url_id NOT IN (SELECT id FROM access_url)');

        $accessUrlRelCourse = $schema->getTable('access_url_rel_course');
        $accessUrlRelCourse->getColumn('access_url_id')->setUnsigned(false);
        $accessUrlRelCourse->addForeignKeyConstraint('access_url', ['access_url_id'], ['id']);
        $accessUrlRelCourse->addForeignKeyConstraint('course', ['c_id'], ['id']);

        if ($schema->hasTable('class')) {
            $schema->renameTable('class', 'class_item');
        }

        if ($schema->hasTable('class_user')) {
            $classUser = $schema->getTable('class_user');
            $classUser->getColumn('class_id')->setUnsigned(false);
            $classUser->getColumn('user_id')->setUnsigned(false);
        }

        $course = $schema->getTable('course');
        $course->getColumn('course_type_id')->setUnsigned(false);
        $course->addForeignKeyConstraint('room', ['room_id'], ['id']);

        if ($schema->hasTable('course_rel_class')) {
            $courseRelClass = $schema->getTable('course_rel_class');
            $courseRelClass->getColumn('class_id')->setUnsigned(false)->setType(Type::getType(Type::INTEGER));
        }

        $courseRelUser = $schema->getTable('course_rel_user');
        $courseRelUser->addForeignKeyConstraint('course', ['c_id'], ['id']);
        $courseRelUser->addForeignKeyConstraint('user', ['user_id'], ['id']);

        if ($schema->hasTable('course_type')) {
            $courseType = $schema->getTable('course_type');
            $courseType->getColumn('id')->setUnsigned(false);
        }

        $schema->getTable('c_announcement')->addIndex(['c_id']);
        $schema->getTable('c_announcement_attachment')->addIndex(['c_id']);
        $schema->getTable('c_attendance')->addIndex(['c_id']);
        $schema->getTable('c_attendance_calendar')->addIndex(['c_id']);

        $cAttendanceCalendarRelGroup = $schema->getTable('c_attendance_calendar_rel_group');
        $cAttendanceCalendarRelGroup->addIndex(['c_id']);
        $cAttendanceCalendarRelGroup->addIndex(['group_id']);

        $schema->getTable('c_attendance_result')->addIndex(['c_id']);

        $cAttendanceSheet = $schema->getTable('c_attendance_sheet');
        $cAttendanceSheet->addIndex(['c_id']);
        $cAttendanceSheet->addIndex(['user_id']);

        $schema->getTable('c_attendance_sheet_log')->addIndex(['c_id']);
        $schema->getTable('c_blog')->addIndex(['c_id']);
        $schema->getTable('c_blog_attachment')->addIndex(['c_id']);
        $schema->getTable('c_blog_comment')->addIndex(['c_id']);
        $schema->getTable('c_blog_post')->addIndex(['c_id']);
        $schema->getTable('c_blog_rating')->addIndex(['c_id']);

        $cBlogRelUser = $schema->getTable('c_blog_rel_user');
        $cBlogRelUser->getColumn('blog_id')->setUnsigned(false);
        $cBlogRelUser->getColumn('user_id')->setUnsigned(false);
        $cBlogRelUser->addIndex(['c_id']);

        $schema->getTable('c_blog_task')->addIndex(['c_id']);

        $cBlogTaskRelUser = $schema->getTable('c_blog_task_rel_user');
        $cBlogTaskRelUser->getColumn('blog_id')->setUnsigned(false);
        $cBlogTaskRelUser->getColumn('user_id')->setUnsigned(false);
        $cBlogTaskRelUser->getColumn('task_id')->setUnsigned(false);
        $cBlogTaskRelUser->addIndex(['c_id']);
        $cBlogTaskRelUser->addIndex(['user_id']);
        $cBlogTaskRelUser->addIndex(['task_id']);

        $cCalendarEvent = $schema->getTable('c_calendar_event');
        $cCalendarEvent->addIndex(['c_id']);

        $schema->getTable('c_calendar_event_attachment')->addIndex(['c_id']);
        $schema->getTable('c_calendar_event_repeat')->addIndex(['c_id']);
        $schema->getTable('c_calendar_event_repeat_not')->addIndex(['c_id']);

        $cChatConnected = $schema->getTable('c_chat_connected');
        $cChatConnected->addIndex(['c_id']);
        $cChatConnected->addIndex(['user_id']);

        $schema->getTable('c_course_setting')->addIndex(['c_id']);
        $schema->getTable('c_document')->addIndex(['c_id']);
        $schema->getTable('c_dropbox_category')->addIndex(['c_id']);
        $schema->getTable('c_dropbox_feedback')->addIndex(['c_id']);
        $schema->getTable('c_dropbox_file')->addIndex(['c_id']);

        $cDropboxPerson = $schema->getTable('c_dropbox_person');
        $cDropboxPerson->addIndex(['c_id']);
        $cDropboxPerson->addIndex(['user_id']);

        $cDropboxPost = $schema->getTable('c_dropbox_post');
        $cDropboxPost->addIndex(['c_id']);
        $cDropboxPost->addIndex(['dest_user_id']);

        $schema->getTable('c_forum_attachment')->addIndex(['c_id']);
        $schema->getTable('c_forum_category')->addIndex(['c_id']);
        $schema->getTable('c_forum_forum')->addIndex(['c_id']);

        $cForumMailcue = $schema->getTable('c_forum_mailcue');
        $cForumMailcue->addIndex(['c_id']);
        $cForumMailcue->addIndex(['thread_id']);
        $cForumMailcue->addIndex(['user_id']);
        $cForumMailcue->addIndex(['post_id']);

        $cForumNotification = $schema->getTable('c_forum_notification');
        $cForumNotification->addIndex(['c_id']);
        $cForumNotification->addIndex(['thread_id']);
        $cForumNotification->addIndex(['post_id']);

        $schema->getTable('c_forum_post')->addIndex(['c_id']);
        $schema->getTable('c_forum_thread')->addIndex(['c_id']);
        $schema->getTable('c_forum_thread_qualify')->addIndex(['c_id']);
        $schema->getTable('c_forum_thread_qualify_log')->addIndex(['c_id']);
        $schema->getTable('c_glossary')->addIndex(['c_id']);
        $schema->getTable('c_group_category')->addIndex(['c_id']);
        $schema->getTable('c_group_info')->addIndex(['c_id']);
        $schema->getTable('c_group_rel_tutor')->addIndex(['c_id']);

        $schema->getTable('c_group_rel_user')->addIndex(['c_id']);
        $schema->getTable('c_link')->addIndex(['c_id']);
        $schema->getTable('c_link_category')->addIndex(['c_id']);
        $schema->getTable('c_lp')->addIndex(['c_id']);
        $schema->getTable('c_lp_category')->addIndex(['c_id']);
        $schema->getTable('c_lp_item')->addIndex(['c_id']);
        $schema->getTable('c_lp_item_view')->addIndex(['c_id']);
        $schema->getTable('c_lp_iv_interaction')->addIndex(['c_id']);
        $schema->getTable('c_lp_iv_objective')->addIndex(['c_id']);
        $schema->getTable('c_lp_view')->addIndex(['c_id']);
        $schema->getTable('c_notebook')->addIndex(['c_id']);
        $schema->getTable('c_online_connected')->addIndex(['c_id']);
        $schema->getTable('c_online_link')->addIndex(['c_id']);
        $schema->getTable('c_permission_group')->addIndex(['c_id']);
        $schema->getTable('c_permission_task')->addIndex(['c_id']);
        $schema->getTable('c_permission_user')->addIndex(['c_id']);
        $schema->getTable('c_quiz')->addIndex(['c_id']);
        $schema->getTable('c_quiz_answer')->addIndex(['c_id']);
        $schema->getTable('c_quiz_question')->addIndex(['c_id']);
        $schema->getTable('c_quiz_question_category')->addIndex(['c_id']);
        $schema->getTable('c_quiz_question_option')->addIndex(['c_id']);
        $schema->getTable('c_quiz_question_rel_category')->addIndex(['c_id']);

        $cQuizRelQuestion = $schema->getTable('c_quiz_rel_question');
        $cQuizRelQuestion->addIndex(['c_id']);
        $cQuizRelQuestion->addIndex(['question_id']);
        $cQuizRelQuestion->addIndex(['exercice_id']);

        $schema->getTable('c_resource')->addIndex(['c_id']);

        $schema->getTable('c_role')->addIndex(['c_id']);

        $cRoleGroup = $schema->getTable('c_role_group');
        $cRoleGroup->addIndex(['c_id']);
        $cRoleGroup->addIndex(['group_id']);

        $cRolePermissions = $schema->getTable('c_role_permissions');
        $cRolePermissions->addIndex(['c_id']);
        $cRolePermissions->addIndex(['role_id']);

        $cRoleUser = $schema->getTable('c_role_user');
        $cRoleUser->addIndex(['c_id']);
        $cRoleUser->addIndex(['user_id']);

        $schema->getTable('c_student_publication')->addIndex(['c_id']);
        $schema->getTable('c_student_publication_assignment')->addIndex(['c_id']);

        $cStudentPublicationComment = $schema->getTable('c_student_publication_comment');
        $cStudentPublicationComment->addIndex(['c_id']);
        $cStudentPublicationComment->addIndex(['user_id']);
        $cStudentPublicationComment->addIndex(['work_id']);

        $cStudentPublicationComment = $schema->getTable('c_student_publication_rel_document');
        $cStudentPublicationComment->addIndex(['c_id']);
        $cStudentPublicationComment->addIndex(['work_id']);
        $cStudentPublicationComment->addIndex(['document_id']);

        $cStudentPublicationComment = $schema->getTable('c_student_publication_rel_user');
        $cStudentPublicationComment->addIndex(['c_id']);
        $cStudentPublicationComment->addIndex(['work_id']);
        $cStudentPublicationComment->addIndex(['user_id']);

        $schema->getTable('c_survey')->addIndex(['c_id']);
        $schema->getTable('c_survey_answer')->addIndex(['c_id']);
        $schema->getTable('c_survey_group')->addIndex(['c_id']);
        $schema->getTable('c_survey_invitation')->addIndex(['c_id']);
        $schema->getTable('c_survey_question')->addIndex(['c_id']);
        $schema->getTable('c_survey_question_option')->addIndex(['c_id']);
        $schema->getTable('c_thematic')->addIndex(['c_id']);
        $schema->getTable('c_thematic_advance')->addIndex(['c_id']);
        $schema->getTable('c_thematic_plan')->addIndex(['c_id']);
        $schema->getTable('c_tool')->addIndex(['c_id']);
        $schema->getTable('c_tool_intro')->addIndex(['c_id']);
        $schema->getTable('c_userinfo_content')->addIndex(['c_id']);
        $schema->getTable('c_userinfo_def')->addIndex(['c_id']);
        $schema->getTable('c_wiki')->addIndex(['c_id']);
        $schema->getTable('c_wiki_conf')->addIndex(['c_id']);
        $schema->getTable('c_wiki_discuss')->addIndex(['c_id']);

        $cWikiMailcue = $schema->getTable('c_wiki_mailcue');
        $cWikiMailcue->addIndex(['c_id']);
        $cWikiMailcue->addIndex(['user_id']);

        $schema->getTable('extra_field_values')->addForeignKeyConstraint('extra_field', ['field_id'], ['id']);

        $session = $schema->getTable('session');
        $session->getColumn('id_coach')->setUnsigned(false);
        $session->addIndex(['session_category_id']);
        $session->addIndex(['id_coach']);
        $session->addForeignKeyConstraint('session_category', ['session_category_id'], ['id']);
        $session->addForeignKeyConstraint('user', ['id_coach'], ['id']);

        $this->connection->executeQuery('UPDATE session_category SET access_url_id = 1 WHERE access_url_id NOT IN (SELECT id FROM access_url)');

        $sessionCategory = $schema->getTable('session_category');
        $sessionCategory->addIndex(['access_url_id']);
        $sessionCategory->addForeignKeyConstraint('access_url', ['access_url_id'], ['id']);

        $sessionRelCourse = $schema->getTable('session_rel_course');
        $sessionRelCourse->dropColumn('course_code');
        $sessionRelCourse->addColumn('id', Type::INTEGER)->setAutoincrement(true);
        $sessionRelCourse->getColumn('c_id')->setUnsigned(false);
        $sessionRelCourse->setPrimaryKey(['id']);
        $sessionRelCourse->addIndex(['c_id']);
        $sessionRelCourse->addIndex(['session_id']);
        $sessionRelCourse->addForeignKeyConstraint('course', ['c_id'], ['id']);
        $sessionRelCourse->addForeignKeyConstraint('session', ['session_id'], ['id']);

        $this->connection->executeQuery('DELETE FROM session_rel_course_rel_user WHERE c_id NOT IN (SELECT id FROM course)');
        $this->connection->executeQuery('DELETE FROM session_rel_course_rel_user WHERE session_id NOT IN (SELECT id FROM session)');
        $this->connection->executeQuery('DELETE FROM session_rel_course_rel_user WHERE user_id NOT IN (SELECT id FROM user)');

        $sessionRelCourseRelUser = $schema->getTable('session_rel_course_rel_user');
        $sessionRelCourseRelUser->dropColumn('course_code');
        $sessionRelCourseRelUser->addColumn('id', Type::INTEGER)->setAutoincrement(true);
        $sessionRelCourseRelUser->getColumn('c_id')->setUnsigned(false);
        $sessionRelCourseRelUser->setPrimaryKey(['id']);
        $sessionRelCourseRelUser->addIndex(['c_id']);
        $sessionRelCourseRelUser->addIndex(['session_id']);
        $sessionRelCourseRelUser->addForeignKeyConstraint('course', ['c_id'], ['id']);
        $sessionRelCourseRelUser->addForeignKeyConstraint('session', ['session_id'], ['id']);
        $sessionRelCourseRelUser->addForeignKeyConstraint('user', ['user_id'], ['id']);

        $this->connection->executeQuery('DELETE FROM session_rel_user WHERE user_id NOT IN (SELECT id FROM user)');
        $this->connection->executeQuery('DELETE FROM session_rel_user WHERE session_id NOT IN (SELECT id FROM session)');

        $sessionRelUser = $schema->getTable('session_rel_user');
        $sessionRelUser->addColumn('moved_to', Type::INTEGER)->setNotnull(false);
        $sessionRelUser->addColumn('moved_status', Type::INTEGER)->setNotnull(false);
        $sessionRelUser->addColumn('moved_at', Type::DATETIME)->setNotnull(false);
        $sessionRelUser->addIndex(['session_id']);
        $sessionRelUser->addIndex(['user_id']);
        $sessionRelUser->addIndex(['user_id', 'moved_to']);
        $sessionRelUser->addForeignKeyConstraint('user', ['user_id'], ['id']);
        $sessionRelUser->addForeignKeyConstraint('session', ['session_id'], ['id']);

        $settingsCurrent = $schema->getTable('settings_current');
        $settingsCurrent->addUniqueIndex(['variable', 'subkey', 'access_url']);

        $settingsCurrent = $schema->getTable('settings_options');
        $settingsCurrent->dropIndex('id');
        $settingsCurrent->addUniqueIndex(['variable', 'value']);

        $schema->getTable('track_e_access')->addIndex(['c_id']);
        $schema->getTable('track_e_attempt')->addIndex(['c_id']);
        $schema->getTable('track_e_course_access')->addIndex(['c_id']);

        $trackEDefault = $schema->getTable('track_e_default');
        $trackEDefault->addIndex(['c_id']);
        $trackEDefault->addIndex(['session_id']);

        $schema->getTable('track_e_downloads')->addIndex(['c_id']);
        $schema->getTable('track_e_exercises')->addIndex(['c_id']);
        $schema->getTable('track_e_hotpotatoes')->addIndex(['c_id']);
        $schema->getTable('track_e_lastaccess')->addIndex(['c_id']);
        $schema->getTable('track_e_links')->addIndex(['c_id']);
        $schema->getTable('track_e_online')->addIndex(['c_id']);
        $schema->getTable('track_e_uploads')->addIndex(['c_id']);
        $schema->getTable('user')->addUniqueIndex(['username_canonical']);

        $this->connection->executeQuery('DELETE FROM usergroup_rel_user WHERE user_id NOT IN (SELECT id FROM user)');
        $this->connection->executeQuery('DELETE FROM usergroup_rel_user WHERE usergroup_id NOT IN (SELECT id FROM usergroup)');

        $usergroupRelUSer = $schema->getTable('usergroup_rel_user');
        $usergroupRelUSer->addIndex(['user_id']);
        $usergroupRelUSer->addIndex(['usergroup_id']);
        $usergroupRelUSer->addForeignKeyConstraint('usergroup', ['usergroup_id'], ['id']);
        $usergroupRelUSer->addForeignKeyConstraint('user', ['user_id'], ['id']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
