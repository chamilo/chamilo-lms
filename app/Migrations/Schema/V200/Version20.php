<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V200;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Class Version20
 * Migrate file to updated to Chamilo 2.0
 *
 */
class Version20 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // Use $schema->createTable
        $this->addSql('set sql_mode=""');
        $this->addSql('ALTER TABLE access_url_rel_user DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE access_url_rel_session DROP PRIMARY KEY');

        $this->addSql('CREATE TABLE IF NOT EXISTS page__page (id INT AUTO_INCREMENT NOT NULL, site_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, target_id INT DEFAULT NULL, route_name VARCHAR(255) NOT NULL, page_alias VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, position INT NOT NULL, enabled TINYINT(1) NOT NULL, decorate TINYINT(1) NOT NULL, edited TINYINT(1) NOT NULL, name VARCHAR(255) NOT NULL, slug LONGTEXT DEFAULT NULL, url LONGTEXT DEFAULT NULL, custom_url LONGTEXT DEFAULT NULL, request_method VARCHAR(255) DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, meta_keyword VARCHAR(255) DEFAULT NULL, meta_description VARCHAR(255) DEFAULT NULL, javascript LONGTEXT DEFAULT NULL, stylesheet LONGTEXT DEFAULT NULL, raw_headers LONGTEXT DEFAULT NULL, template VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_2FAE39EDF6BD1646 (site_id), INDEX IDX_2FAE39ED727ACA70 (parent_id), INDEX IDX_2FAE39ED158E0B66 (target_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS page__site (id INT AUTO_INCREMENT NOT NULL, enabled TINYINT(1) NOT NULL, name VARCHAR(255) NOT NULL, relative_path VARCHAR(255) DEFAULT NULL, host VARCHAR(255) NOT NULL, enabled_from DATETIME DEFAULT NULL, enabled_to DATETIME DEFAULT NULL, is_default TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, locale VARCHAR(7) DEFAULT NULL, title VARCHAR(64) DEFAULT NULL, meta_keywords VARCHAR(255) DEFAULT NULL, meta_description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS page__snapshot (id INT AUTO_INCREMENT NOT NULL, site_id INT DEFAULT NULL, page_id INT DEFAULT NULL, route_name VARCHAR(255) NOT NULL, page_alias VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, position INT NOT NULL, enabled TINYINT(1) NOT NULL, decorate TINYINT(1) NOT NULL, name VARCHAR(255) NOT NULL, url LONGTEXT DEFAULT NULL, parent_id INT DEFAULT NULL, target_id INT DEFAULT NULL, content LONGTEXT DEFAULT NULL COMMENT "(DC2Type:json)", publication_date_start DATETIME DEFAULT NULL, publication_date_end DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_3963EF9AF6BD1646 (site_id), INDEX IDX_3963EF9AC4663E4 (page_id), INDEX idx_snapshot_dates_enabled (publication_date_start, publication_date_end, enabled), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS page__bloc (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, page_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, type VARCHAR(64) NOT NULL, settings LONGTEXT NOT NULL COMMENT "(DC2Type:json)", enabled TINYINT(1) DEFAULT NULL, position INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_FCDC1A97727ACA70 (parent_id), INDEX IDX_FCDC1A97C4663E4 (page_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS timeline__timeline (id INT AUTO_INCREMENT NOT NULL, action_id INT DEFAULT NULL, subject_id INT DEFAULT NULL, context VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_FFBC6AD59D32F035 (action_id), INDEX IDX_FFBC6AD523EDC87 (subject_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS timeline__component (id INT AUTO_INCREMENT NOT NULL, model VARCHAR(255) NOT NULL, identifier LONGTEXT NOT NULL COMMENT "(DC2Type:array)", hash VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_1B2F01CDD1B862B8 (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS timeline__action (id INT AUTO_INCREMENT NOT NULL, verb VARCHAR(255) NOT NULL, status_current VARCHAR(255) NOT NULL, status_wanted VARCHAR(255) NOT NULL, duplicate_key VARCHAR(255) DEFAULT NULL, duplicate_priority INT DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS timeline__action_component (id INT AUTO_INCREMENT NOT NULL, action_id INT DEFAULT NULL, component_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, text VARCHAR(255) DEFAULT NULL, INDEX IDX_6ACD1B169D32F035 (action_id), INDEX IDX_6ACD1B16E2ABAFFF (component_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS classification__tag (id INT AUTO_INCREMENT NOT NULL, context VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, slug VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_CA57A1C7E25D857E (context), UNIQUE INDEX tag_context (slug, context), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS classification__collection (id INT AUTO_INCREMENT NOT NULL, context VARCHAR(255) DEFAULT NULL, media_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, slug VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_A406B56AE25D857E (context), INDEX IDX_A406B56AEA9FDD75 (media_id), UNIQUE INDEX tag_collection (slug, context), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS classification__context (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS classification__category (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, context VARCHAR(255) DEFAULT NULL, media_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, slug VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, position INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_43629B36727ACA70 (parent_id), INDEX IDX_43629B36E25D857E (context), INDEX IDX_43629B36EA9FDD75 (media_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS media__gallery_media (id INT AUTO_INCREMENT NOT NULL, gallery_id INT DEFAULT NULL, media_id INT DEFAULT NULL, position INT NOT NULL, enabled TINYINT(1) NOT NULL, updated_at DATETIME NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_80D4C5414E7AF8F (gallery_id), INDEX IDX_80D4C541EA9FDD75 (media_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS media__gallery (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, context VARCHAR(64) NOT NULL, default_format VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, updated_at DATETIME NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS media__media (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, enabled TINYINT(1) NOT NULL, provider_name VARCHAR(255) NOT NULL, provider_status INT NOT NULL, provider_reference VARCHAR(255) NOT NULL, provider_metadata LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', width INT DEFAULT NULL, height INT DEFAULT NULL, length NUMERIC(10, 0) DEFAULT NULL, content_type VARCHAR(255) DEFAULT NULL, content_size INT DEFAULT NULL, copyright VARCHAR(255) DEFAULT NULL, author_name VARCHAR(255) DEFAULT NULL, context VARCHAR(64) DEFAULT NULL, cdn_is_flushable TINYINT(1) DEFAULT NULL, cdn_flush_identifier VARCHAR(64) DEFAULT NULL, cdn_flush_at DATETIME DEFAULT NULL, cdn_status INT DEFAULT NULL, updated_at DATETIME NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_5C6DD74E12469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS faq_question_translation (id INT AUTO_INCREMENT NOT NULL, translatable_id INT DEFAULT NULL, headline VARCHAR(255) NOT NULL, body LONGTEXT DEFAULT NULL, slug VARCHAR(50) NOT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_C2D1A2C2AC5D3 (translatable_id), UNIQUE INDEX faq_question_translation_unique_translation (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS faq_category_translation (id INT AUTO_INCREMENT NOT NULL, translatable_id INT DEFAULT NULL, headline VARCHAR(255) NOT NULL, body LONGTEXT DEFAULT NULL, slug VARCHAR(50) NOT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_5493B0FC2C2AC5D3 (translatable_id), UNIQUE INDEX faq_category_translation_unique_translation (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS faq_category (id INT AUTO_INCREMENT NOT NULL, rank INT NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX is_active_idx (is_active), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS faq_question (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, rank INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, only_auth_users TINYINT(1) NOT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_4A55B05912469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS contact_category_translation (id INT AUTO_INCREMENT NOT NULL, translatable_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, locale VARCHAR(255) NOT NULL, INDEX IDX_3E770F302C2AC5D3 (translatable_id), UNIQUE INDEX contact_category_translation_unique_translation (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS contact_category (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('ALTER TABLE page__bloc ADD CONSTRAINT FK_FCDC1A97727ACA70 FOREIGN KEY (parent_id) REFERENCES page__bloc (id) ON DELETE CASCADE;');
        $this->addSql('ALTER TABLE page__bloc ADD CONSTRAINT FK_FCDC1A97C4663E4 FOREIGN KEY (page_id) REFERENCES page__page (id) ON DELETE CASCADE;');
        $this->addSql('ALTER TABLE timeline__timeline ADD CONSTRAINT FK_FFBC6AD59D32F035 FOREIGN KEY (action_id) REFERENCES timeline__action (id);');
        $this->addSql('ALTER TABLE timeline__timeline ADD CONSTRAINT FK_FFBC6AD523EDC87 FOREIGN KEY (subject_id) REFERENCES timeline__component (id) ON DELETE CASCADE;');
        $this->addSql('ALTER TABLE timeline__action_component ADD CONSTRAINT FK_6ACD1B169D32F035 FOREIGN KEY (action_id) REFERENCES timeline__action (id) ON DELETE CASCADE;');
        $this->addSql('ALTER TABLE timeline__action_component ADD CONSTRAINT FK_6ACD1B16E2ABAFFF FOREIGN KEY (component_id) REFERENCES timeline__component (id) ON DELETE CASCADE;');
        $this->addSql('ALTER TABLE classification__tag ADD CONSTRAINT FK_CA57A1C7E25D857E FOREIGN KEY (context) REFERENCES classification__context (id);');
        $this->addSql('ALTER TABLE classification__collection ADD CONSTRAINT FK_A406B56AE25D857E FOREIGN KEY (context) REFERENCES classification__context (id);');
        $this->addSql('ALTER TABLE classification__collection ADD CONSTRAINT FK_A406B56AEA9FDD75 FOREIGN KEY (media_id) REFERENCES media__media (id) ON DELETE SET NULL;');
        $this->addSql('ALTER TABLE classification__category ADD CONSTRAINT FK_43629B36727ACA70 FOREIGN KEY (parent_id) REFERENCES classification__category (id) ON DELETE CASCADE;');
        $this->addSql('ALTER TABLE classification__category ADD CONSTRAINT FK_43629B36E25D857E FOREIGN KEY (context) REFERENCES classification__context (id);');
        $this->addSql('ALTER TABLE classification__category ADD CONSTRAINT FK_43629B36EA9FDD75 FOREIGN KEY (media_id) REFERENCES media__media (id) ON DELETE SET NULL;');

        $this->addSql('ALTER TABLE media__gallery_media ADD CONSTRAINT FK_80D4C5414E7AF8F FOREIGN KEY (gallery_id) REFERENCES media__gallery (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE media__gallery_media ADD CONSTRAINT FK_80D4C541EA9FDD75 FOREIGN KEY (media_id) REFERENCES media__media (id) ON DELETE CASCADE;');


        $this->addSql('ALTER TABLE media__media ADD CONSTRAINT FK_5C6DD74E12469DE2 FOREIGN KEY (category_id) REFERENCES classification__category (id) ON DELETE SET NULL;');

        $this->addSql('ALTER TABLE faq_question_translation ADD CONSTRAINT FK_C2D1A2C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES faq_question (id) ON DELETE CASCADE;');
        $this->addSql('ALTER TABLE faq_category_translation ADD CONSTRAINT FK_5493B0FC2C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES faq_category (id) ON DELETE CASCADE;');
        $this->addSql('ALTER TABLE faq_question ADD CONSTRAINT FK_4A55B05912469DE2 FOREIGN KEY (category_id) REFERENCES faq_category (id);');
        $this->addSql('ALTER TABLE contact_category_translation ADD CONSTRAINT FK_3E770F302C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES contact_category (id) ON DELETE CASCADE;');
        $this->addSql('ALTER TABLE page__page ADD CONSTRAINT FK_2FAE39EDF6BD1646 FOREIGN KEY (site_id) REFERENCES page__site (id) ON DELETE CASCADE;');
        $this->addSql('ALTER TABLE page__page ADD CONSTRAINT FK_2FAE39ED727ACA70 FOREIGN KEY (parent_id) REFERENCES page__page (id) ON DELETE CASCADE;');
        $this->addSql('ALTER TABLE page__page ADD CONSTRAINT FK_2FAE39ED158E0B66 FOREIGN KEY (target_id) REFERENCES page__page (id) ON DELETE CASCADE;');
        $this->addSql('ALTER TABLE page__snapshot ADD CONSTRAINT FK_3963EF9AF6BD1646 FOREIGN KEY (site_id) REFERENCES page__site (id) ON DELETE CASCADE;');
        $this->addSql('ALTER TABLE page__snapshot ADD CONSTRAINT FK_3963EF9AC4663E4 FOREIGN KEY (page_id) REFERENCES page__page (id) ON DELETE CASCADE;');

        $this->addSql('ALTER TABLE fos_group ADD name VARCHAR(180) NOT NULL, ADD roles LONGTEXT NOT NULL COMMENT "(DC2Type:array)";');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B019DDB5E237E06 ON fos_group (name);');

        $this->addSql('ALTER TABLE gradebook_evaluation ADD c_id INT DEFAULT NULL');
        $this->addSql("UPDATE gradebook_evaluation SET c_id = (SELECT id FROM course WHERE code = course_code)");
        $this->addSql('ALTER TABLE gradebook_evaluation DROP course_code');
        $this->addSql('ALTER TABLE gradebook_evaluation ADD CONSTRAINT FK_DDDED80491D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);');
        $this->addSql('CREATE INDEX IDX_DDDED80491D79BD3 ON gradebook_evaluation (c_id)');
        //$this->addSql('ALTER TABLE gradebook_evaluation RENAME INDEX fk_ddded80491d79bd3 TO IDX_DDDED80491D79BD3;');

        $this->addSql('ALTER TABLE gradebook_category ADD c_id INT DEFAULT NULL');
        $this->addSql('UPDATE gradebook_category SET c_id = (SELECT id FROM course WHERE code = course_code)');
        $this->addSql('ALTER TABLE gradebook_category DROP course_code');

        $this->addSql('ALTER TABLE gradebook_category ADD CONSTRAINT FK_96A4C70591D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);');
        $this->addSql('CREATE INDEX IDX_96A4C70591D79BD3 ON gradebook_category (c_id);');

        $this->addSql('ALTER TABLE gradebook_link ADD c_id INT DEFAULT NULL');
        $this->addSql('UPDATE gradebook_link SET c_id = (SELECT id FROM course WHERE code = course_code)');
        $this->addSql('ALTER TABLE gradebook_link DROP course_code');
        $this->addSql('ALTER TABLE gradebook_link ADD CONSTRAINT FK_4F0F595F91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);');
        $this->addSql('CREATE INDEX IDX_4F0F595F91D79BD3 ON gradebook_link (c_id);');

        $this->addSql('ALTER TABLE access_url_rel_user ADD id INT AUTO_INCREMENT NOT NULL, CHANGE access_url_id access_url_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL, ADD PRIMARY KEY (id);');
        $this->addSql('ALTER TABLE access_url ADD limit_courses INT DEFAULT NULL, ADD limit_active_courses INT DEFAULT NULL, ADD limit_sessions INT DEFAULT NULL, ADD limit_users INT DEFAULT NULL, ADD limit_teachers INT DEFAULT NULL, ADD limit_disk_space INT DEFAULT NULL, ADD email VARCHAR(255) DEFAULT NULL;');

        $this->addSql('ALTER TABLE course_request CHANGE user_id user_id INT DEFAULT NULL;');
        $this->addSql('ALTER TABLE course_request ADD CONSTRAINT FK_33548A73A76ED395 FOREIGN KEY (user_id) REFERENCES user (id);');
        $this->addSql('CREATE INDEX IDX_33548A73A76ED395 ON course_request (user_id);');

        $this->addSql('ALTER TABLE search_engine_ref ADD c_id INT DEFAULT NULL');
        $this->addSql('UPDATE search_engine_ref SET c_id = (SELECT id FROM course WHERE code = course_code)');
        $this->addSql('ALTER TABLE search_engine_ref DROP course_code');

        $this->addSql('ALTER TABLE search_engine_ref ADD CONSTRAINT FK_473F037891D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);');
        $this->addSql('CREATE INDEX IDX_473F037891D79BD3 ON search_engine_ref (c_id);');

        $connection = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM course_category";
        $result = $connection->executeQuery($sql);
        $all = $result->fetchAll();

        $categories = array_column($all, 'parent_id', 'id');
        $categoryCodeList = array_column($all, 'id', 'code');

        foreach ($categories as $categoryId => $parentId) {
            if (empty($parentId)) {
                continue;
            }
            $newParentId = $categoryCodeList[$parentId];
            if (!empty($newParentId)) {
                $this->addSql("UPDATE course_category SET parent_id = $newParentId WHERE id = $categoryId");
            }
        }

        $this->addSql('ALTER TABLE course_category CHANGE parent_id parent_id INT DEFAULT NULL;');

        $this->addSql('ALTER TABLE course_category ADD CONSTRAINT FK_AFF87497727ACA70 FOREIGN KEY (parent_id) REFERENCES course_category (id);');
        $this->addSql('ALTER TABLE settings_current ADD CONSTRAINT FK_62F79C3B9436187B FOREIGN KEY (access_url) REFERENCES access_url (id);');

        $this->addSql('ALTER TABLE access_url_rel_session ADD id INT AUTO_INCREMENT NOT NULL, CHANGE access_url_id access_url_id INT DEFAULT NULL, CHANGE session_id session_id INT DEFAULT NULL, ADD PRIMARY KEY (id);');
        $this->addSql('ALTER TABLE access_url_rel_session ADD CONSTRAINT FK_6CBA5F5D613FECDF FOREIGN KEY (session_id) REFERENCES session (id);');
        $this->addSql('ALTER TABLE access_url_rel_session ADD CONSTRAINT FK_6CBA5F5D73444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id);');
        $this->addSql('CREATE INDEX IDX_6CBA5F5D613FECDF ON access_url_rel_session (session_id);');
        $this->addSql('CREATE INDEX IDX_6CBA5F5D73444FD5 ON access_url_rel_session (access_url_id);');

        $this->addSql('ALTER TABLE c_tool ADD CONSTRAINT FK_8456658091D79BD3 FOREIGN KEY (c_id) REFERENCES course (id)');

        $this->addSql('DROP INDEX user_sco_course_sv ON track_stored_values;');
        $this->addSql('DROP INDEX user_sco_course_sv_stack ON track_stored_values_stack;');

        $this->addSql('UPDATE c_tool SET name = "blog" WHERE name = "blog_management" ');
        $this->addSql('UPDATE c_tool SET name = "agenda" WHERE name = "calendar_event" ');
        $this->addSql('UPDATE c_tool SET name = "maintenance" WHERE name = "course_maintenance" ');
        $this->addSql('UPDATE c_tool SET name = "assignment" WHERE name = "student_publication" ');
        $this->addSql('UPDATE c_tool SET name = "settings" WHERE name = "course_setting" ');

        $this->addSql('UPDATE session_category SET date_start = NULL WHERE date_start = "0000-00-00"');
        $this->addSql('UPDATE session_category SET date_end = NULL WHERE date_end = "0000-00-00"');

        $table = $schema->getTable('message');
        if (!$table->hasIndex('idx_message_user_receiver_status')) {
            $this->addSql('CREATE INDEX idx_message_user_receiver_status ON message (user_receiver_id, msg_status)');
        }

        if (!$table->hasIndex('idx_message_receiver_status_send_date')) {
            $this->addSql('CREATE INDEX idx_message_receiver_status_send_date ON message (user_receiver_id, msg_status, send_date)');
        }

        $table = $schema->getTable('track_e_course_access');
        if (!$table->hasIndex('user_course_session_date')) {
            $this->addSql(
                'CREATE INDEX user_course_session_date ON track_e_course_access (user_id, c_id, session_id, login_course_date)'
            );
        }

        $table = $schema->getTable('c_quiz_answer');
        if (!$table->hasIndex('c_id_auto')) {
            $this->addSql('CREATE INDEX c_id_auto ON c_quiz_answer (c_id, id_auto)');
        }

        $table = $schema->getTable('c_forum_post');
        if (!$table->hasIndex('c_id_visible_post_date')) {
            $this->addSql('CREATE INDEX c_id_visible_post_date ON c_forum_post (c_id, visible, post_date)');
        }

        $table = $schema->getTable('track_e_access');
        if (!$table->hasIndex('user_course_session_date')) {
            $this->addSql('CREATE INDEX user_course_session_date ON track_e_access (access_user_id, c_id, access_session_id, access_date)');
        }

         // Update iso
        $sql = "UPDATE course SET course_language = (SELECT isocode FROM language WHERE english_name = course_language);";
        $this->addSql($sql);

        $sql = "UPDATE sys_announcement SET lang = (SELECT isocode FROM language WHERE english_name = lang);";
        $this->addSql($sql);
        //$this->addSql('ALTER TABLE c_tool_intro CHANGE id tool VARCHAR(255) NOT NULL');

        $this->addSql('ALTER TABLE user ADD date_of_birth DATETIME DEFAULT NULL, ADD website VARCHAR(64) DEFAULT NULL, ADD biography VARCHAR(1000) DEFAULT NULL, ADD gender VARCHAR(1) DEFAULT NULL, ADD locale VARCHAR(8) DEFAULT NULL, ADD timezone VARCHAR(64) DEFAULT NULL, ADD facebook_uid VARCHAR(255) DEFAULT NULL, ADD facebook_name VARCHAR(255) DEFAULT NULL, ADD facebook_data LONGTEXT DEFAULT NULL COMMENT "(DC2Type:json)", ADD twitter_uid VARCHAR(255) DEFAULT NULL, ADD twitter_name VARCHAR(255) DEFAULT NULL, ADD twitter_data LONGTEXT DEFAULT NULL COMMENT "(DC2Type:json)", ADD gplus_uid VARCHAR(255) DEFAULT NULL, ADD gplus_name VARCHAR(255) DEFAULT NULL, ADD gplus_data LONGTEXT DEFAULT NULL COMMENT "(DC2Type:json)", ADD token VARCHAR(255) DEFAULT NULL, ADD two_step_code VARCHAR(255) DEFAULT NULL, CHANGE username_canonical username_canonical VARCHAR(180) NOT NULL, CHANGE lastname lastname VARCHAR(64) DEFAULT NULL, CHANGE firstname firstname VARCHAR(64) DEFAULT NULL, CHANGE phone phone VARCHAR(64) DEFAULT NULL, CHANGE salt salt VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE confirmation_token confirmation_token VARCHAR(180) DEFAULT NULL;');
        $this->addSql('ALTER TABLE c_item_property CHANGE lastedit_user_id lastedit_user_id INT DEFAULT NULL');

        // Fixes missing options show_glossary_in_extra_tools
        $this->addSql("DELETE FROM settings_options WHERE variable = 'show_glossary_in_extra_tools'");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'none', 'None')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'exercise', 'Exercise')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'lp', 'LearningPath')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'exercise_and_lp', 'ExerciseAndLearningPath')");


        $table = $schema->getTable('sys_announcement');
        if (!$table->hasColumn('visible_drh')) {
            $this->addSql(
                "ALTER TABLE sys_announcement ADD COLUMN visible_drh INT DEFAULT 0;"
            );
        }

        if (!$table->hasColumn('visible_session_admin')) {
            $this->addSql(
                "ALTER TABLE sys_announcement ADD COLUMN visible_session_admin INT DEFAULT 0;"
            );
        }

        if (!$table->hasColumn('visible_boss')) {
            $this->addSql(
                "ALTER TABLE sys_announcement ADD COLUMN visible_boss INT DEFAULT 0;"
            );
        }

        $cSurvey = $schema->getTable('c_survey');

        if (!$cSurvey->hasColumn('is_mandatory')) {
            $cSurvey->addColumn('is_mandatory', Type::BOOLEAN)->setDefault(false);
        }

        $this->addSql('ALTER TABLE c_student_publication ADD filesize INT DEFAULT NULL');
        $this->addSql('CREATE TABLE IF NOT EXISTS c_group_info_audit (iid INT NOT NULL, rev INT NOT NULL, c_id INT DEFAULT NULL, id INT DEFAULT NULL, name VARCHAR(100) DEFAULT NULL, status TINYINT(1) DEFAULT NULL, category_id INT DEFAULT NULL, description LONGTEXT DEFAULT NULL, max_student INT DEFAULT NULL, doc_state TINYINT(1) DEFAULT NULL, calendar_state TINYINT(1) DEFAULT NULL, work_state TINYINT(1) DEFAULT NULL, announcements_state TINYINT(1) DEFAULT NULL, forum_state TINYINT(1) DEFAULT NULL, wiki_state TINYINT(1) DEFAULT NULL, chat_state TINYINT(1) DEFAULT NULL, secret_directory VARCHAR(255) DEFAULT NULL, self_registration_allowed TINYINT(1) DEFAULT NULL, self_unregistration_allowed TINYINT(1) DEFAULT NULL, session_id INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, PRIMARY KEY(iid, rev)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');

        /*$table = $schema->getTable('course_rel_class');
        if (!$table->hasColumn('c_id')) {
            $this->addSql("ALTER TABLE course_rel_class ADD c_id int NOT NULL");
        }

        if ($table->hasColumn('course_code')) {
            $this->addSql("
                UPDATE course_rel_class cc
                SET cc.c_id = (SELECT id FROM course WHERE code = cc.course_code)
            ");

            $this->addSql("ALTER TABLE course_rel_class DROP course_code");
            $this->addSql("ALTER TABLE course_rel_class DROP PRIMARY KEY");
            $this->addSql("ALTER TABLE course_rel_class MODIFY COLUMN class_id INT DEFAULT NULL");
            $this->addSql("ALTER TABLE course_rel_class ADD PRIMARY KEY (class_id, c_id)");
            $this->addSql("ALTER TABLE course_rel_class ADD FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE RESTRICT");
        }*/

        $tables = [
            'shared_survey',
            'specific_field_values',
            'templates'
        ];

        foreach ($tables as $table) {
            $tableObj = $schema->getTable($table);
            /*if (!$tableObj->hasColumn('c_id')) {
                $this->addSql("ALTER TABLE $table ADD c_id int NOT NULL");

                if ($tableObj->hasColumn('course_code')) {
                    $this->addSql("
                      UPDATE $table t
                      SET t.c_id = (SELECT id FROM course WHERE code = t.course_code)
                    ");
                    $this->addSql("ALTER TABLE $table DROP course_code");
                }
            }*/
            /*$this->addSql("
                ALTER TABLE $table ADD FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE RESTRICT
            ");*/
        }
/*
        $this->addSql("ALTER TABLE personal_agenda DROP course");

        $this->addSql("
            ALTER TABLE specific_field_values
            ADD c_id int(11) NOT NULL,
            ADD FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE RESTRICT;
        ");

        $this->addSql("
            ALTER TABLE track_e_hotspot
            CHANGE c_id c_id int(11) NOT NULL AFTER hotspot_course_code,
            ADD FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE RESTRICT;
        ");
        $this->addSql("
            UPDATE track_e_hotspot teh
            SET teh.c_id = (SELECT id FROM course WHERE code = teh.hotspot_course_code)
            WHERE teh.hotspot_course_code != NULL OR hotspot_course_code != ''
        ");
        $this->addSql("ALTER TABLE personal_agenda DROP hotspot_course_code");*/

        // Update settings variable name
        $settings = [
            'Institution' => 'institution',
            'SiteName' => 'site_name',
            'InstitutionUrl' => 'institution_url',
            'registration' => 'required_profile_fields',
            'profile' => 'changeable_options',
            'timezone_value' => 'timezone',
            'stylesheets' => 'theme',
            'platformLanguage' => 'platform_language',
            'languagePriority1' => 'language_priority_1',
            'languagePriority2' => 'language_priority_2',
            'languagePriority3' => 'language_priority_3',
            'languagePriority4' => 'language_priority_4',
            'gradebook_score_display_coloring' => 'my_display_coloring',
            'document_if_file_exists_option' => 'if_file_exists_option',
            'ProfilingFilterAddingUsers' => 'profiling_filter_adding_users',
            'course_create_active_tools' => 'active_tools_on_create',
            'EmailAdministrator' => 'administrator_email',
            'administratorSurname' => 'administrator_surname',
            'administratorName' => 'administrator_name',
            'administratorTelephone' => 'administrator_phone',
            'registration.soap.php.decode_utf8' => 'decode_utf8',
        ];

        foreach ($settings as $oldSetting => $newSetting) {
            $sql = "UPDATE settings_current SET variable = '$newSetting'
                    WHERE variable = '$oldSetting'";
            $this->addSql($sql);
        }

        // Update settings category
        $settings = [
            'cookie_warning' => 'platform',
            'donotlistcampus' => 'platform',
            'administrator_email' => 'admin',
            'administrator_surname' => 'admin',
            'administrator_name' => 'admin',
            'administrator_phone' => 'admin',
            'exercise_max_ckeditors_in_page' => 'exercise',
            'allow_hr_skills_management' => 'skill',
            'accessibility_font_resize' => 'display',
            'account_valid_duration' => 'profile',
            'activate_email_template' => 'mail',
            'allow_global_chat' => 'chat',
            'allow_lostpassword' => 'registration',
            'allow_registration' => 'registration',
            'allow_registration_as_teacher' => 'registration',
            'allow_skills_tool' => 'skill',
            'allow_students_to_browse_courses' => 'display',
            'allow_terms_conditions' => 'registration',
            'allow_users_to_create_courses' => 'course',
            'auto_detect_language_custom_pages' => 'language',
            'course_validation' => 'course',
            'course_validation_terms_and_conditions_url' => 'course',
            'display_categories_on_homepage' => 'display',
            'display_coursecode_in_courselist' => 'course',
            'display_teacher_in_courselist' => 'course',
            'drh_autosubscribe' => 'registration',
            'drh_page_after_login' => 'registration',
            'enable_help_link' => 'display',
            'example_material_course_creation' => 'course',
            'login_is_email' => 'profile',
            'noreply_email_address' => 'mail',
            'page_after_login' => 'registration',
            'pdf_export_watermark_by_course' => 'document',
            'pdf_export_watermark_enable' => 'document',
            'pdf_export_watermark_text' => 'document',
            'platform_unsubscribe_allowed' => 'registration',
            'send_email_to_admin_when_create_course' => 'course',
            'show_admin_toolbar' => 'display',
            'show_administrator_data' => 'display',
            'show_back_link_on_top_of_tree' => 'display',
            'show_closed_courses' => 'display',
            'show_different_course_language' => 'display',
            'show_email_addresses' => 'display',
            'show_empty_course_categories' => 'display',
            'show_full_skill_name_on_skill_wheel' => 'skill',
            'show_hot_courses' => 'display',
            'show_link_bug_notification' => 'display',
            'show_number_of_courses' => 'display',
            'show_teacher_data' => 'display',
            'showonline' => 'display',
            'student_autosubscribe' => 'registration',
            'student_page_after_login' => 'registration',
            'student_view_enabled' => 'course',
            'teacher_autosubscribe' => 'registration',
            'teacher_page_after_login' => 'registration',
            'time_limit_whosonline' => 'display',
            'user_selected_theme' => 'profile',
            'hide_global_announcements_when_not_connected' => 'announcement',
            'hide_home_top_when_connected' => 'display',
            'hide_logout_button' => 'display',
            'institution_address' => 'platform',
            'redirect_admin_to_courses_list' => 'admin',
            'decode_utf8' => 'webservice',
            'use_custom_pages' => 'platform',
            'allow_group_categories' => 'group',
            'allow_user_headings' => 'display',
            'default_document_quotum' => 'document',
            'default_forum_view' => 'forum',
            'default_group_quotum' => 'document',
            'enable_quiz_scenario' => 'exercise',
            'exercise_max_score' => 'exercise',
            'exercise_min_score' => 'exercise',
            'pdf_logo_header' => 'platform',
            'show_glossary_in_documents' => 'document',
            'show_glossary_in_extra_tools' => 'glossary',
            //'show_toolshortcuts' => '',
            'survey_email_sender_noreply'=> 'survey',
            'allow_coach_feedback_exercises' => 'exercise',
            'sessionadmin_autosubscribe' => 'registration',
            'sessionadmin_page_after_login' => 'registration',
            'show_tutor_data' => 'display',
            'chamilo_database_version' => 'platform',
            'add_gradebook_certificates_cron_task_enabled' => 'gradebook',
            'icons_mode_svg' => 'display',
            'server_type' => 'platform',
            'show_official_code_whoisonline' => 'platform',
            'show_terms_if_profile_completed' => 'ticket'
        ];

        foreach ($settings as $variable => $category) {
            $sql = "UPDATE settings_current SET category = '$category'
                    WHERE variable = '$variable'";
            $this->addSql($sql);
        }

        // Update settings value
        $settings = [
            'upload_extensions_whitelist' => 'htm;html;jpg;jpeg;gif;png;swf;avi;mpg;mpeg;mov;flv;doc;docx;xls;xlsx;ppt;pptx;odt;odp;ods;pdf;webm;oga;ogg;ogv;h264',
        ];

        foreach ($settings as $variable => $value) {
            $sql = "UPDATE settings_current SET selected_value = '$value'
                    WHERE variable = '$variable'";
            $this->addSql($sql);
        }

        // Delete settings
        $settings = [
            'use_session_mode',
            'show_toolshortcuts',
            'show_tabs',
            'display_mini_month_calendar',
            'number_of_upcoming_events',
            'facebook_description',
            'ldap_description',
            'openid_authentication',
            //'platform_charset',
            'shibboleth_description'
        ];

        foreach ($settings as $setting) {
            $sql = "DELETE FROM settings_current WHERE variable = '$setting'";
            $this->addSql($sql);
        }

        $this->addSql('UPDATE settings_current SET category = LOWER(category)');
        $this->addSql("ALTER TABLE c_quiz_question_category CHANGE description description LONGTEXT DEFAULT NULL;");
        $this->addSql("ALTER TABLE c_survey_invitation ADD answered_at DATETIME DEFAULT NULL;");

        $this->addSql('CREATE TABLE IF NOT EXISTS scheduled_announcements (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, date DATETIME DEFAULT NULL, sent TINYINT(1) NOT NULL, session_id INT NOT NULL, c_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('ALTER TABLE gradebook_certificate ADD downloaded_at DATETIME DEFAULT NULL;');
        $this->addSql('UPDATE gradebook_certificate gc SET downloaded_at = (select value from extra_field e inner join extra_field_values v on v.field_id = e.id where variable = "downloaded_at" and extra_field_type = 11 and item_id = gc.id)');

        $table = $schema->getTable('c_quiz');
        if ($table->hasColumn('show_previous_button') === false) {
            $this->addSql(
                'ALTER TABLE c_quiz ADD COLUMN show_previous_button TINYINT(1) DEFAULT 1;'
            );
        }

        if ($table->hasColumn('notifications') === false) {
            $this->addSql(
                'ALTER TABLE c_quiz ADD COLUMN notifications VARCHAR(255) NULL DEFAULT NULL;'
            );
        }

        $table = $schema->getTable('c_lp_item_view');
        if ($table->hasIndex('idx_c_lp_item_view_cid_id_view_count') == false) {
            $this->addSql(
                'CREATE INDEX idx_c_lp_item_view_cid_id_view_count ON c_lp_item_view (c_id, id, view_count)'
            );
        }

        $table = $schema->getTable('session');
        if (!$table->hasColumn('position')) {
            $this->addSql('ALTER TABLE session ADD COLUMN position INT DEFAULT 0 NOT NULL');
        } else {
            $this->addSql('ALTER TABLE session CHANGE position position INT DEFAULT 0 NOT NULL');
        }

        $this->addSql("UPDATE settings_current SET selected_value = 'true' WHERE variable = 'decode_utf8'");
        $this->addSql('ALTER TABLE extra_field_values CHANGE value value LONGTEXT DEFAULT NULL;');
        $this->addSql('ALTER TABLE message CHANGE msg_status msg_status SMALLINT NOT NULL;');

        // Portfolio
        if (!$schema->hasTable('portfolio')) {
            $this->addSql('CREATE TABLE portfolio_category (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, is_visible TINYINT(1) DEFAULT "1" NOT NULL, INDEX user (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
            $this->addSql('CREATE TABLE portfolio (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, c_id INT DEFAULT NULL, session_id INT DEFAULT NULL, category_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, creation_date DATETIME NOT NULL, update_date DATETIME NOT NULL, is_visible TINYINT(1) DEFAULT "1" NOT NULL, INDEX user (user_id), INDEX course (c_id), INDEX session (session_id), INDEX category (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
            $this->addSql('ALTER TABLE portfolio_category ADD CONSTRAINT FK_7AC64359A76ED395 FOREIGN KEY (user_id) REFERENCES user (id);');
            $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED1062A76ED395 FOREIGN KEY (user_id) REFERENCES user (id);');
            $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED106291D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);');
            $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED1062613FECDF FOREIGN KEY (session_id) REFERENCES session (id);');
            $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED106212469DE2 FOREIGN KEY (category_id) REFERENCES portfolio_category (id);');
        }

        // Skills
        if (!$schema->hasTable('skill_rel_item_rel_user')) {
            $this->addSql('CREATE TABLE skill_rel_item_rel_user (id INT AUTO_INCREMENT NOT NULL, skill_rel_item_id INT NOT NULL, user_id INT NOT NULL, result_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, created_by INT NOT NULL, updated_by INT NOT NULL, INDEX IDX_D1133E0DFD4B12DC (skill_rel_item_id), INDEX IDX_D1133E0DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
            $this->addSql('CREATE TABLE skill_rel_item (id INT AUTO_INCREMENT NOT NULL, skill_id INT DEFAULT NULL, item_type INT NOT NULL, item_id INT NOT NULL, obtain_conditions VARCHAR(255) DEFAULT NULL, requires_validation TINYINT(1) NOT NULL, is_real TINYINT(1) NOT NULL, c_id INT DEFAULT NULL, session_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, created_by INT NOT NULL, updated_by INT NOT NULL, INDEX IDX_EB5B2A0D5585C142 (skill_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
            $this->addSql('CREATE TABLE skill_rel_course (id INT AUTO_INCREMENT NOT NULL, skill_id INT DEFAULT NULL, c_id INT NOT NULL, session_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_E7CEC7FA5585C142 (skill_id), INDEX IDX_E7CEC7FA91D79BD3 (c_id), INDEX IDX_E7CEC7FA613FECDF (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
            $this->addSql('ALTER TABLE skill_rel_item_rel_user ADD CONSTRAINT FK_D1133E0DFD4B12DC FOREIGN KEY (skill_rel_item_id) REFERENCES skill_rel_item (id);');
            $this->addSql('ALTER TABLE skill_rel_item_rel_user ADD CONSTRAINT FK_D1133E0DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);');
            $this->addSql('ALTER TABLE skill_rel_item ADD CONSTRAINT FK_EB5B2A0D5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id);');
            $this->addSql('ALTER TABLE skill_rel_course ADD CONSTRAINT FK_E7CEC7FA5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id);');
            $this->addSql('ALTER TABLE skill_rel_course ADD CONSTRAINT FK_E7CEC7FA91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);');
            $this->addSql('ALTER TABLE skill_rel_course ADD CONSTRAINT FK_E7CEC7FA613FECDF FOREIGN KEY (session_id) REFERENCES session (id);');
        }

        $this->addSql('ALTER TABLE skill_rel_user ADD validation_status INT NOT NULL');
        $this->addSql('ALTER TABLE gradebook_category ADD gradebooks_to_validate_in_dependence INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649C05FB297 ON user (confirmation_token)');

        $this->addSql('CREATE TABLE IF NOT EXISTS ext_translations (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(8) NOT NULL, object_class VARCHAR(255) NOT NULL, field VARCHAR(32) NOT NULL, foreign_key VARCHAR(64) NOT NULL, content LONGTEXT DEFAULT NULL, INDEX translations_lookup_idx (locale, object_class, foreign_key), UNIQUE INDEX lookup_unique_idx (locale, object_class, field, foreign_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS ext_log_entries (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) NOT NULL, logged_at DATETIME NOT NULL, object_id VARCHAR(64) DEFAULT NULL, object_class VARCHAR(255) NOT NULL, version INT NOT NULL, data LONGTEXT DEFAULT NULL COMMENT "(DC2Type:array)", username VARCHAR(255) DEFAULT NULL, INDEX log_class_lookup_idx (object_class), INDEX log_date_lookup_idx (logged_at), INDEX log_user_lookup_idx (username), INDEX log_version_lookup_idx (object_id, object_class, version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS tool (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS resource_node (id INT AUTO_INCREMENT NOT NULL, tool_id INT DEFAULT NULL, creator_id INT NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, level INT DEFAULT NULL, path VARCHAR(3000) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_8A5F48FF8F7B22CC (tool_id), INDEX IDX_8A5F48FF61220EA6 (creator_id), INDEX IDX_8A5F48FF727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS resource_rights (id INT AUTO_INCREMENT NOT NULL, resource_link_id INT DEFAULT NULL, role VARCHAR(255) NOT NULL, mask INT NOT NULL, UNIQUE INDEX UNIQ_C99C3BF9F004E599 (resource_link_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS resource_link (id INT AUTO_INCREMENT NOT NULL, resource_node_id INT DEFAULT NULL, session_id INT DEFAULT NULL, user_id INT DEFAULT NULL, c_id INT DEFAULT NULL, group_id INT DEFAULT NULL, usergroup_id INT DEFAULT NULL, private TINYINT(1) DEFAULT NULL, public TINYINT(1) DEFAULT NULL, start_visibility_at DATETIME DEFAULT NULL, end_visibility_at DATETIME DEFAULT NULL, INDEX IDX_398C394B1BAD783F (resource_node_id), INDEX IDX_398C394B613FECDF (session_id), INDEX IDX_398C394BA76ED395 (user_id), INDEX IDX_398C394B91D79BD3 (c_id), INDEX IDX_398C394BFE54D947 (group_id), INDEX IDX_398C394BD2112630 (usergroup_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS tool_resource_rights (id INT AUTO_INCREMENT NOT NULL, tool_id INT DEFAULT NULL, role VARCHAR(255) NOT NULL, mask INT NOT NULL, INDEX IDX_95CE3398F7B22CC (tool_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE IF NOT EXISTS notification__message (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL COMMENT "(DC2Type:json)", state INT NOT NULL, restart_count INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, started_at DATETIME DEFAULT NULL, completed_at DATETIME DEFAULT NULL, INDEX notification_message_state_idx (state), INDEX notification_message_created_at_idx (created_at), INDEX idx_state (state), INDEX idx_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE sylius_settings (id INT AUTO_INCREMENT NOT NULL, schema_alias VARCHAR(255) NOT NULL, namespace VARCHAR(255) DEFAULT NULL, parameters LONGTEXT NOT NULL COMMENT "(DC2Type:json_array)", UNIQUE INDEX UNIQ_1AFEFB2A894A31AD33E16B56 (schema_alias, namespace), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');

        $this->addSql('ALTER TABLE resource_node ADD CONSTRAINT FK_8A5F48FF8F7B22CC FOREIGN KEY (tool_id) REFERENCES tool (id);');
        $this->addSql('ALTER TABLE resource_node ADD CONSTRAINT FK_8A5F48FF61220EA6 FOREIGN KEY (creator_id) REFERENCES user (id) ON DELETE CASCADE;');
        $this->addSql('ALTER TABLE resource_node ADD CONSTRAINT FK_8A5F48FF727ACA70 FOREIGN KEY (parent_id) REFERENCES resource_node (id) ON DELETE CASCADE;');
        $this->addSql('ALTER TABLE resource_rights ADD CONSTRAINT FK_C99C3BF9F004E599 FOREIGN KEY (resource_link_id) REFERENCES resource_link (id);');
        $this->addSql('ALTER TABLE resource_link ADD CONSTRAINT FK_398C394B1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id);');
        $this->addSql('ALTER TABLE resource_link ADD CONSTRAINT FK_398C394B613FECDF FOREIGN KEY (session_id) REFERENCES session (id);');
        $this->addSql('ALTER TABLE resource_link ADD CONSTRAINT FK_398C394BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);');
        $this->addSql('ALTER TABLE resource_link ADD CONSTRAINT FK_398C394B91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);');
        $this->addSql('ALTER TABLE resource_link ADD CONSTRAINT FK_398C394BFE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid);');
        $this->addSql('ALTER TABLE resource_link ADD CONSTRAINT FK_398C394BD2112630 FOREIGN KEY (usergroup_id) REFERENCES usergroup (id);');
        $this->addSql('ALTER TABLE tool_resource_rights ADD CONSTRAINT FK_95CE3398F7B22CC FOREIGN KEY (tool_id) REFERENCES tool (id);');

        $table = $schema->getTable('sys_announcement');

        if ($table->hasColumn('visible_drh')) {
            $this->addSql('ALTER TABLE sys_announcement CHANGE visible_drh visible_drh TINYINT(1) NOT NULL');
        } else {
            $this->addSql('ALTER TABLE sys_announcement ADD COLUMN visible_drh TINYINT(1) NOT NULL');
        }

        if ($table->hasColumn('visible_session_admin')) {
            $this->addSql(
                'ALTER TABLE sys_announcement CHANGE visible_session_admin visible_session_admin TINYINT(1) NOT NULL'
            );
        } else {
            $this->addSql(
                'ALTER TABLE sys_announcement ADD COLUMN visible_session_admin TINYINT(1) NOT NULL'
            );
        }

        if ($table->hasColumn('visible_boss')) {
            $this->addSql('ALTER TABLE sys_announcement CHANGE visible_boss visible_boss TINYINT(1) NOT NULL');
        } else {
            $this->addSql('ALTER TABLE sys_announcement ADD COLUMN visible_boss TINYINT(1) NOT NULL');
        }

        $table = $schema->getTable('c_group_info');
        if (!$table->hasColumn('document_access')) {
            $this->addSql('ALTER TABLE c_group_info ADD document_access INT DEFAULT 0 NOT NULL;');
        }

        $table = $schema->getTable('c_group_category');
        if (!$table->hasColumn('document_access')) {
            $this->addSql('ALTER TABLE c_group_category ADD document_access INT DEFAULT 0 NOT NULL;');
        }

        $table = $schema->getTable('c_quiz');
        if (!$table->hasColumn('autolaunch')) {
            $this->addSql('ALTER TABLE c_quiz ADD autolaunch TINYINT(1) DEFAULT 0 NOT NULL');
        }
    }

    /**
     *
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
