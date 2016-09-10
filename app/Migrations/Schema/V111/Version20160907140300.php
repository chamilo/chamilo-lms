<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Class Version20160907140300
 * Change tables engine to InnoDB
 * @package Application\Migrations\Schema\V111
 */
class Version20160907140300 extends AbstractMigrationChamilo
{

    public function up(Schema $schema)
    {
        $data = [
            'course' => 'last_visit',
            'course' => 'last_edit',
            'course' => 'creation_date',
            'course' => 'expiration_date',
            'notification' => 'sent_at',
            'sequence_value' => 'success_date',
            'sequence_value' => 'available_start_date',
            'sequence_value' => 'available_end_date',
            'session_rel_user' => 'moved_at',
            'track_e_course_access' => 'logout_course_date',
            'track_e_exercises' => 'expired_time_control',
            'track_e_login' => 'logout_date',
            'user_api_key' => 'created_date',
            'user_api_key' => 'validity_start_date',
            'user_api_key' => 'validity_end_date',
            'user_rel_user' => 'last_edit',
            'c_attendance_sheet_log' => 'calendar_date_value',
            'c_forum_post' => 'post_date',
            'c_forum_thread' => 'thread_date',
            'c_forum_thread' => 'thread_close_date',
            'c_forum_thread_qualify' => 'qualify_time',
            'c_forum_thread_qualify_log' => 'qualify_time',
            'c_userinfo_content' => 'edition_time',
        ];

        foreach ($data as $table => $field) {
            $this->addSql("ALTER TABLE $table CHANGE $field $field DATETIME");
            $this->addSql("UPDATE $table SET $field = NULL WHERE TO_DAYS(STR_TO_DATE($field, \"%Y-%m-%d %T\")) IS NULL");
        }
    }

    public function down(Schema $schema)
    {

    }
}
