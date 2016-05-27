<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version111
 * Migrate file to updated to Chamilo 1.11
 *
 */
class Version111 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $this->addSql('CREATE TABLE extra_field_saved_search (id INT AUTO_INCREMENT NOT NULL, field_id INT DEFAULT NULL, user_id INT DEFAULT NULL, value LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_16ABE32A443707B0 (field_id), INDEX IDX_16ABE32AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE extra_field_saved_search ADD CONSTRAINT FK_16ABE32A443707B0 FOREIGN KEY (field_id) REFERENCES extra_field (id)');
        $this->addSql('ALTER TABLE extra_field_saved_search ADD CONSTRAINT FK_16ABE32AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');

        $this->addSql('CREATE TABLE c_lp_category_user (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, INDEX IDX_61F042712469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE c_lp_category_user ADD CONSTRAINT FK_61F042712469DE2 FOREIGN KEY (category_id) REFERENCES c_lp_category (iid)');

        $this->addSql('ALTER TABLE c_lp_category_user ADD user_id INT DEFAULT NULL;');
        $this->addSql('ALTER TABLE c_lp_category_user ADD CONSTRAINT FK_61F0427A76ED395 FOREIGN KEY (user_id) REFERENCES user (id);');
        $this->addSql('CREATE INDEX IDX_61F0427A76ED395 ON c_lp_category_user (user_id);');

        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_my_files',NULL,'radio','Platform','true','AllowMyFilesTitle','AllowMyFilesComment','',NULL, 1)");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_my_files','true','Yes') ");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_my_files','false','No') ");

        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('exercise_invisible_in_session',NULL,'radio','Session','false','ExerciseInvisibleInSessionTitle','ExerciseInvisibleInSessionComment','',NULL, 1)");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('exercise_invisible_in_session','true','Yes') ");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('exercise_invisible_in_session','false','No') ");

        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('configure_exercise_visibility_in_course',NULL,'radio','Session','false','ConfigureExerciseVisibilityInCourseTitle','ConfigureExerciseVisibilityInCourseComment','',NULL, 1)");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('configure_exercise_visibility_in_course','true','Yes') ");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('configure_exercise_visibility_in_course','false','No') ");
        $this->addSql("ALTER TABLE c_forum_forum ADD moderated TINYINT(1) DEFAULT NULL");
        $this->addSql("ALTER TABLE c_forum_post ADD status INT DEFAULT NULL");
        $this->addSql("CREATE TABLE c_quiz_rel_category (iid BIGINT AUTO_INCREMENT NOT NULL, c_id INT NOT NULL, category_id INT NOT NULL, exercise_id INT NOT NULL, count_questions INT NOT NULL, PRIMARY KEY(iid))");
        $this->addSql("ALTER TABLE c_quiz ADD COLUMN question_selection_type INT");

        $this->addSql("CREATE TABLE faq_question_translation (id INT AUTO_INCREMENT NOT NULL, translatable_id INT DEFAULT NULL, headline VARCHAR(255) NOT NULL, body LONGTEXT DEFAULT NULL, slug VARCHAR(50) NOT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_C2D1A2C2AC5D3 (translatable_id), UNIQUE INDEX faq_question_translation_unique_translation (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        $this->addSql("CREATE TABLE faq_category_translation (id INT AUTO_INCREMENT NOT NULL, translatable_id INT DEFAULT NULL, headline VARCHAR(255) NOT NULL, body LONGTEXT DEFAULT NULL, slug VARCHAR(50) NOT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_5493B0FC2C2AC5D3 (translatable_id), UNIQUE INDEX faq_category_translation_unique_translation (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        $this->addSql("CREATE TABLE faq_category (id INT AUTO_INCREMENT NOT NULL, rank INT NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX is_active_idx (is_active), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        $this->addSql("CREATE TABLE faq_question (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, is_active TINYINT(1) NOT NULL, rank INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, only_auth_users TINYINT(1) NOT NULL, INDEX IDX_4A55B05912469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        $this->addSql("ALTER TABLE faq_question_translation ADD CONSTRAINT FK_C2D1A2C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES faq_question (id) ON DELETE CASCADE;");
        $this->addSql("ALTER TABLE faq_category_translation ADD CONSTRAINT FK_5493B0FC2C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES faq_category (id) ON DELETE CASCADE;");
        $this->addSql("ALTER TABLE faq_question ADD CONSTRAINT FK_4A55B05912469DE2 FOREIGN KEY (category_id) REFERENCES faq_category (id);");

        $this->addSql("CREATE TABLE contact_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
        $table = $schema->getTable('session_rel_user');
        if (!$table->hasColumn('duration')) {
            $this->addSql("ALTER TABLE session_rel_user ADD duration INT DEFAULT NULL");
        }

        $this->addSql('CREATE TABLE access_url_rel_course_category (id INT AUTO_INCREMENT NOT NULL, access_url_id INT NOT NULL, course_category_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql("INSERT INTO access_url_rel_course_category (access_url_id, course_category_id) VALUES (1, 1) ");
        $this->addSql("INSERT INTO access_url_rel_course_category (access_url_id, course_category_id) VALUES (1, 2) ");
        $this->addSql("INSERT INTO access_url_rel_course_category (access_url_id, course_category_id) VALUES (1, 3) ");

        $this->addSql('ALTER TABLE notification CHANGE content content TEXT');

        // Needed to update 0000-00-00 00:00:00 values
        $this->addSql('SET sql_mode = ""');

        $this->addSql('UPDATE c_lp SET publicated_on = NULL WHERE publicated_on = "0000-00-00 00:00:00"');
        $this->addSql('UPDATE c_lp SET expired_on = NULL WHERE expired_on = "0000-00-00 00:00:00"');

        $this->addSql('ALTER TABLE c_lp CHANGE publicated_on publicated_on DATETIME');
        $this->addSql('ALTER TABLE c_lp CHANGE expired_on expired_on DATETIME');

        $this->addSql('UPDATE c_quiz SET start_time = NULL WHERE start_time = "0000-00-00 00:00:00"');
        $this->addSql('UPDATE c_quiz SET end_time = NULL WHERE end_time = "0000-00-00 00:00:00"');

        $this->addSql('ALTER TABLE c_quiz CHANGE start_time start_time DATETIME');
        $this->addSql('ALTER TABLE c_quiz CHANGE end_time end_time DATETIME');

        $this->addSql('UPDATE c_calendar_event SET start_date = NULL WHERE start_date = "0000-00-00 00:00:00"');
        $this->addSql('UPDATE c_calendar_event SET end_date = NULL WHERE end_date = "0000-00-00 00:00:00"');

        $this->addSql('ALTER TABLE c_calendar_event CHANGE start_date start_date DATETIME');
        $this->addSql('ALTER TABLE c_calendar_event CHANGE end_date end_date DATETIME');

        $this->addSql('UPDATE personal_agenda SET date = NULL WHERE date = "0000-00-00 00:00:00"');
        $this->addSql('UPDATE personal_agenda SET enddate = NULL WHERE enddate = "0000-00-00 00:00:00"');

        $this->addSql('ALTER TABLE personal_agenda CHANGE date date DATETIME');
        $this->addSql('ALTER TABLE personal_agenda CHANGE enddate enddate DATETIME');

        $this->addSql('UPDATE c_forum_forum SET start_time = NULL WHERE start_time = "0000-00-00 00:00:00"');
        $this->addSql('UPDATE c_forum_forum SET end_time = NULL WHERE end_time = "0000-00-00 00:00:00"');

        $this->addSql('ALTER TABLE c_forum_forum CHANGE start_time start_time DATETIME');
        $this->addSql('ALTER TABLE c_forum_forum CHANGE end_time end_time DATETIME');

        $this->addSql('UPDATE sys_calendar SET start_date = NULL WHERE start_date = "0000-00-00 00:00:00"');
        $this->addSql('UPDATE sys_calendar SET end_date = NULL WHERE end_date = "0000-00-00 00:00:00"');

        $this->addSql('ALTER TABLE sys_calendar CHANGE start_date start_date DATETIME');
        $this->addSql('ALTER TABLE sys_calendar CHANGE end_date end_date DATETIME');

        $this->addSql('UPDATE message SET update_date = NULL WHERE update_date = "0000-00-00 00:00:00"');
        $this->addSql('ALTER TABLE message CHANGE update_date update_date DATETIME');

        $this->addSql('UPDATE c_wiki_conf SET startdate_assig = NULL WHERE startdate_assig = "0000-00-00 00:00:00"');
        $this->addSql('UPDATE c_wiki_conf SET enddate_assig = NULL WHERE enddate_assig = "0000-00-00 00:00:00"');

        $this->addSql('ALTER TABLE c_wiki_conf CHANGE startdate_assig startdate_assig DATETIME');
        $this->addSql('ALTER TABLE c_wiki_conf CHANGE enddate_assig enddate_assig DATETIME');

        $this->addSql('UPDATE c_wiki SET time_edit = NULL WHERE time_edit = "0000-00-00 00:00:00"');
        $this->addSql('ALTER TABLE c_wiki CHANGE time_edit time_edit DATETIME');

        $this->addSql('UPDATE c_wiki SET dtime = NULL WHERE dtime = "0000-00-00 00:00:00"');
        $this->addSql('ALTER TABLE c_wiki CHANGE dtime dtime DATETIME');

        $this->addSql('UPDATE access_url SET tms = NULL WHERE tms = "0000-00-00 00:00:00"');
        $this->addSql('ALTER TABLE access_url CHANGE tms tms DATETIME');

        $this->addSql('UPDATE track_e_attempt SET tms = NULL WHERE tms = "0000-00-00 00:00:00"');
        $this->addSql('ALTER TABLE track_e_attempt CHANGE tms tms DATETIME');

        $this->addSql('UPDATE track_e_default SET default_date = NULL WHERE default_date = "0000-00-00 00:00:00"');
        $this->addSql('ALTER TABLE track_e_default CHANGE default_date default_date DATETIME');

        $this->addSql('ALTER TABLE track_e_exercises CHANGE expired_time_control expired_time_control DATETIME');

        $this->addSql('DROP TABLE group_rel_user');
        $this->addSql('DROP TABLE group_rel_tag');
        $this->addSql('DROP TABLE group_rel_group');
        $this->addSql('DROP TABLE groups');

        if ($schema->hasTable('plugin_ticket_ticket')) {
            $this->addSql('ALTER TABLE plugin_ticket_ticket ADD COLUMN subject varchar(255) DEFAULT NULL;');
            $this->addSql('ALTER TABLE plugin_ticket_ticket ADD COLUMN message text NOT NULL;');
            $this->addSql('UPDATE plugin_ticket_ticket t INNER JOIN plugin_ticket_message as m  ON(t.ticket_id = m.ticket_id and message_id =1)  SET t.subject = m.subject');
            $this->addSql('UPDATE plugin_ticket_ticket t INNER JOIN plugin_ticket_message as m  ON(t.ticket_id = m.ticket_id and message_id =1)  SET t.message = m.message');
            $this->addSql('DELETE FROM plugin_ticket_message WHERE message_id = 1');

            $this->addSql('RENAME TABLE plugin_ticket_assigned_log TO ticket_assigned_log');
            $this->addSql('RENAME TABLE plugin_ticket_category TO ticket_category');
            $this->addSql('RENAME TABLE plugin_ticket_category_rel_user TO ticket_category_rel_user');
            $this->addSql('RENAME TABLE plugin_ticket_message TO ticket_message');
            $this->addSql('RENAME TABLE plugin_ticket_message_attachments TO ticket_message_attachments');
            $this->addSql('RENAME TABLE plugin_ticket_priority TO ticket_priority');
            $this->addSql('RENAME TABLE plugin_ticket_project TO ticket_project');
            $this->addSql('RENAME TABLE plugin_ticket_status TO ticket_status');
            $this->addSql('RENAME TABLE plugin_ticket_ticket TO ticket_ticket');
        }
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE c_lp_category_user');
        $this->addSql('DROP TABLE access_url_rel_course_category');
    }
}
