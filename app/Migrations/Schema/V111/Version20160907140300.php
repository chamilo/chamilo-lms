<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160907140300
 * Change tables engine to InnoDB
 * @package Application\Migrations\Schema\V111
 */
class Version20160907140300 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        error_log('Version20160907140300');
        $data = [
            'career' => [
                'created_at',
                'updated_at',
            ],
            'chat' => [
                'sent',

            ],
            'course' => [
                'last_visit',
                'last_edit',
                'creation_date',
                'expiration_date',
            ],
            'course_request' => [
                'request_date',
            ],
            'gradebook_certificate' => [
                'created_at',
            ],
            'gradebook_evaluation' => [
                'created_at',
            ],
            'gradebook_link' => [
                'created_at',
            ],
            'gradebook_linkeval_log' => [
                'created_at',
            ],
            'gradebook_result' => [
                'created_at',
            ],
            'gradebook_result_log' => [
                'created_at',
            ],
            'message' => [
                'send_date',
            ],
            'notification' => [
                'sent_at'
            ],
            'promotion' => [
                'created_at',
                'updated_at',
            ],
            'shared_survey' => [
                'creation_date',
            ],
            'sequence_value' => [
                'success_date',
                'available_start_date',
                'available_end_date',
            ],
            'session_rel_user' => [
                'moved_at',
                'registered_at',
            ],
            'skill' => [
                'updated_at',
            ],
            'sys_announcement' => [
                'date_start',
                'date_end',
            ],
            'track_e_attempt_recording' => [
                'insert_date',

            ],
            'track_e_course_access' => [
                'login_course_date',
                'logout_course_date',
            ],
            'track_e_downloads' => [
                'down_date',
            ],
            'track_e_exercises' => [
                'start_date',
                'exe_date',
            ],
            'track_e_hotpotatoes' => [
                'exe_date',
            ],
            'track_e_item_property' => [
                'lastedit_date',
            ],
            'track_e_links' => [
                'links_date',
            ],
            'track_e_login' => [
                'logout_date',
            ],
            'track_e_online' => [
                'login_date',
            ],
            'track_e_open' => [
                'open_date',
            ],
            'track_e_uploads' => [
                'upload_date',
            ],
            'user_api_key' => [
                'created_date',
                'validity_start_date',
                'validity_end_date',
            ],
            'user_rel_user' => [
                'last_edit',
            ],
            'c_attendance_calendar' => [
                'date_time',
            ],
            'c_attendance_sheet_log' => [
                'calendar_date_value',
            ],
            'c_blog' => [
                'date_creation',
            ],
            'c_blog_comment' => [
                'date_creation',
            ],
            'c_blog_post' => [
                'date_creation',
            ],
            'c_blog_task_rel_user' => [
                'target_date',
            ],
            'c_chat_connected' => [
                'last_connection',
            ],
            'c_dropbox_feedback' => [
                'feedback_date',
            ],
            'c_dropbox_file' => [
                'upload_date',
                'last_upload_date',
            ],
            'c_dropbox_post' => [
                'feedback_date',
            ],
            'c_forum_post' => [
                'post_date',
            ],
            'c_forum_thread' => [
                'thread_date',
                'thread_close_date',
            ],
            'c_forum_thread_qualify' => [
                'qualify_time',
            ],
            'c_forum_thread_qualify_log' => [
                'qualify_time',
            ],
            'c_lp' => [
                'created_on',
                'modified_on',
            ],
            'c_notebook' => [
                'creation_date',
                'update_date',
            ],
            'c_online_connected' => [
                'last_connection',
            ],
            'c_survey' => [
                'creation_date',
            ],
            'c_survey_invitation' => [
                'invitation_date',
                'reminder_date',
            ],
            'c_userinfo_content' => [
                'edition_time'
            ],
            'c_wiki_discuss' => [
                'dtime',
            ],

        ];
        // Needed to update 0000-00-00 00:00:00 values
        $this->addSql('SET sql_mode = ""');
        // In case this one didn't work, also try this
        $this->addSql('SET SESSION sql_mode = ""');

        // The whole point of this version is to ensure that all tricky (or most)
        // tricky datetime fields are null if = 0000-00-00 00:00:00, because
        // this value is not tolerated in NO_ZERO_DATE mode nor to convert
        // the table to InnoDB, and we want all tables to be converted to
        // InnoDB (that's the point of the following migration)
        // To try and avoid errors to the maximum, we first convert the fields
        // to a non-DATETIME type, then change the value of zero-valued times
        // to NULL, then change the field back to DATETIME
        foreach ($data as $table => $fields) {
            foreach ($fields as $field) {
                error_log("$table . $field");
                $this->addSql("ALTER TABLE $table CHANGE $field $field char(19)");
                $this->addSql("UPDATE $table SET $field = NULL WHERE $field = '0000-00-00 00:00:00'");
                $this->addSql("UPDATE $table SET $field = NULL WHERE $field = '0000-00-00 23:59:59'");
                $this->addSql("ALTER TABLE $table CHANGE $field $field DATETIME");
            }
        }
        // Same with DATE instead of DATETIME
        $data = [
            'c_announcement' => [
                'end_date',
            ],
        ];
        foreach ($data as $table => $fields) {
            foreach ($fields as $field) {
                error_log("$table . $field");
                $this->addSql("ALTER TABLE $table CHANGE $field $field char(10)");
                $this->addSql("UPDATE $table SET $field = NULL WHERE $field = '0000-00-00'");
                $this->addSql("ALTER TABLE $table CHANGE $field $field DATE");
            }
        }
    }

    public function down(Schema $schema)
    {
    }
}
