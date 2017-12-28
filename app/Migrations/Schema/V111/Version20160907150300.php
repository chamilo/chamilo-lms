<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160907150300
 * Change tables engine to InnoDB
 * @package Application\Migrations\Schema\V111
 */
class Version20160907150300 extends AbstractMigrationChamilo
{
    private $names = [
        'access_url_rel_session',
        'access_url_rel_user',
        'admin',
        'announcement_rel_group',
        'block',
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
        'c_group_rel_tutor',
        'c_group_rel_user',
        'c_link',
        'c_link_category',
        'c_lp',
        'c_lp_item',
        'c_lp_item_view',
        'c_lp_iv_interaction',
        'c_lp_iv_objective',
        'c_lp_view',
        'c_notebook',
        'c_online_connected',
        'c_online_link',
        'c_permission_group',
        'c_permission_task',
        'c_permission_user',
        'c_quiz',
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
        'c_thematic_plan',
        'c_tool',
        'c_tool_intro',
        'c_userinfo_content',
        'c_userinfo_def',
        'c_wiki',
        'c_wiki_conf',
        'c_wiki_discuss',
        'c_wiki_mailcue',
        'career',
        'chat',
        'class_user',
        'course_category',
        'course_module',
        'course_rel_class',
        'course_request',
        'course_type',
        'event_email_template',
        'event_sent',
        'grade_components',
        'grade_model',
        'gradebook_category',
        'gradebook_certificate',
        'gradebook_evaluation',
        'gradebook_link',
        'gradebook_linkeval_log',
        'gradebook_result',
        'gradebook_result_log',
        'gradebook_score_display',
        'language',
        'legal',
        'message',
        'message_attachment',
        'notification',
        'openid_association',
        'personal_agenda',
        'personal_agenda_repeat',
        'personal_agenda_repeat_not',
        'promotion',
        'search_engine_ref',
        'shared_survey',
        'shared_survey_question',
        'shared_survey_question_option',
        'skill',
        'skill_profile',
        'skill_rel_gradebook',
        'skill_rel_profile',
        'skill_rel_skill',
        'skill_rel_user',
        'specific_field',
        'specific_field_values',
        'sys_announcement',
        'sys_calendar',
        'system_template',
        'tag',
        'templates',
        'track_course_ranking',
        'track_e_access',
        'track_e_attempt',
        'track_e_attempt_coeff',
        'track_e_attempt_recording',
        'track_e_course_access',
        'track_e_default',
        'track_e_downloads',
        'track_e_exercises',
        'track_e_hotpotatoes',
        'track_e_hotspot',
        'track_e_item_property',
        'track_e_lastaccess',
        'track_e_links',
        'track_e_login',
        'track_e_online',
        'track_e_open',
        'track_e_uploads',
        'track_stored_values',
        'track_stored_values_stack',
        'user_api_key',
        'user_course_category',
        'user_friend_relation_type',
        'user_rel_course_vote',
        'user_rel_event_type',
        'user_rel_tag',
        'user_rel_user',
        'usergroup_rel_course',
        'usergroup_rel_question',
        'usergroup_rel_session'
    ];

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        error_log('Version20160907150300');
        foreach ($this->names as $name) {
            if (!$schema->hasTable($name)) {
                continue;
            }
            $sql = "ALTER TABLE $name ENGINE=InnoDB";
            $this->addSql($sql);
            error_log($sql);
        }
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema)
    {
        foreach ($this->names as $name) {
            if (!$schema->hasTable($name)) {
                continue;
            }

            $this->addSql("ALTER TABLE $name ENGINE=MyISAM");
        }
    }
}
