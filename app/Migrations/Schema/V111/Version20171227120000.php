<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20171227120000
 *
 * Fix more missing queries for migration from 1.10 to 1.11 (GH#2214)
 * These are minor changes caused by the move from static SQL to ORM entities
 *
 * @package Application\Migrations\Schema\V111
 */
class Version20171227120000 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        error_log('Version20171227120000');
        $this->addSql('ALTER TABLE access_url CHANGE description description LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE career CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE chat CHANGE sent sent DATETIME NOT NULL');
        $this->addSql('ALTER TABLE course_category CHANGE auth_course_child auth_course_child VARCHAR(40) DEFAULT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE request_date request_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE extra_field CHANGE visible_to_self visible_to_self TINYINT DEFAULT NULL, CHANGE visible_to_others visible_to_others TINYINT DEFAULT NULL');
        $this->addSql('ALTER TABLE gradebook_certificate CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE gradebook_evaluation CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE gradebook_link CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE gradebook_linkeval_log CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE gradebook_result CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE gradebook_result_log CHANGE created_at created_at DATETIME NOT NULL');
        // Fails because of FK on id field
        //$this->addSql('ALTER TABLE language CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE message CHANGE send_date send_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE promotion CHANGE status status INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE session CHANGE name name VARCHAR(150) NOT NULL');
        $this->addSql('ALTER TABLE session_rel_user CHANGE registered_at registered_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE shared_survey CHANGE creation_date creation_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE skill CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE skill_rel_user_comment CHANGE skill_rel_user_id skill_rel_user_id INT DEFAULT NULL, CHANGE feedback_giver_id feedback_giver_id INT DEFAULT NULL, CHANGE feedback_text feedback_text LONGTEXT NOT NULL, CHANGE feedback_value feedback_value INT DEFAULT 1, CHANGE feedback_datetime feedback_datetime DATETIME NOT NULL');
        $this->addSql('ALTER TABLE sys_announcement CHANGE date_start date_start DATETIME NOT NULL, CHANGE date_end date_end DATETIME NOT NULL');
        $this->addSql('ALTER TABLE track_e_attempt_recording CHANGE insert_date insert_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE track_e_course_access CHANGE login_course_date login_course_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE track_e_downloads CHANGE down_date down_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE track_e_exercises CHANGE exe_date exe_date DATETIME NOT NULL, CHANGE start_date start_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE track_e_hotpotatoes CHANGE exe_date exe_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE track_e_item_property CHANGE lastedit_date lastedit_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE track_e_links CHANGE links_date links_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE track_e_online CHANGE login_date login_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE track_e_open CHANGE open_date open_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE track_e_uploads CHANGE upload_date upload_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE email_canonical email_canonical VARCHAR(100) NOT NULL, CHANGE credentials_expired credentials_expired TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE c_attendance_calendar CHANGE date_time date_time DATETIME NOT NULL');
        $this->addSql('ALTER TABLE c_blog CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE c_blog_comment CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE c_blog_post CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE c_blog_task_rel_user CHANGE target_date target_date DATE NOT NULL');
        $this->addSql('ALTER TABLE c_chat_connected CHANGE last_connection last_connection DATETIME NOT NULL');
        $this->addSql('ALTER TABLE c_dropbox_feedback CHANGE feedback_date feedback_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE c_dropbox_file CHANGE upload_date upload_date DATETIME NOT NULL, CHANGE last_upload_date last_upload_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE c_dropbox_post CHANGE feedback_date feedback_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE c_item_property CHANGE visibility visibility INT NOT NULL');
        $this->addSql('ALTER TABLE c_lp CHANGE created_on created_on DATETIME NOT NULL, CHANGE modified_on modified_on DATETIME NOT NULL');
        $this->addSql('ALTER TABLE c_notebook CHANGE creation_date creation_date DATETIME NOT NULL, CHANGE update_date update_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE c_online_connected CHANGE last_connection last_connection DATETIME NOT NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE hide_question_title hide_question_title TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz_rel_category CHANGE category_id category_id INT DEFAULT NULL, CHANGE count_questions count_questions INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE creation_date creation_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE c_survey_invitation CHANGE invitation_date invitation_date DATETIME NOT NULL, CHANGE reminder_date reminder_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE c_wiki_discuss CHANGE dtime dtime DATETIME NOT NULL');
        $this->addSql('ALTER TABLE skill_level CHANGE profile_id profile_id INT DEFAULT NULL, CHANGE position position INT NOT NULL, CHANGE short_name short_name VARCHAR(255) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // No need to revert those database changes as they are minor.
        // There would be no real use to that
    }
}
