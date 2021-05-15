<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20
 * Migrate file to updated to Chamilo 2.0.
 */
class Version20 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Basic changes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('set sql_mode=""');

        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        /*$connection = $em->getConnection();
        $sql = "SELECT * FROM c_quiz WHERE iid <> id";
        $result = $connection->executeQuery($sql);*/

        $table = $schema->getTable('user');
        if (false === $table->hasColumn('uuid')) {
            $this->addSql("ALTER TABLE user ADD uuid BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)'");
        }

        if (false === $schema->hasTable('asset')) {
            $this->addSql("CREATE TABLE asset (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, category VARCHAR(255) NOT NULL, compressed TINYINT(1) NOT NULL, mime_type LONGTEXT DEFAULT NULL, original_name LONGTEXT DEFAULT NULL, dimensions LONGTEXT DEFAULT NULL COMMENT '(DC2Type:simple_array)', size INT NOT NULL, crop VARCHAR(255) DEFAULT NULL, metadata LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;");
        }

        $this->addSql('DELETE FROM track_e_attempt WHERE exe_id IS NULL');
        $this->addSql('ALTER TABLE track_e_attempt CHANGE exe_id exe_id INT NOT NULL');

        $this->addSql('UPDATE branch_transaction_status SET title = "No title" WHERE title IS NULL');
        $this->addSql('ALTER TABLE branch_transaction_status CHANGE title title VARCHAR(255) NOT NULL');

        $this->addSql('UPDATE room SET title = "No title" WHERE title IS NULL');
        $this->addSql('ALTER TABLE room CHANGE title title VARCHAR(255) NOT NULL');

        $this->addSql('UPDATE session_rel_course_rel_user SET legal_agreement = 0 WHERE legal_agreement IS NULL');
        $this->addSql('ALTER TABLE session_rel_course_rel_user CHANGE legal_agreement legal_agreement INT NOT NULL');

        $this->addSql('UPDATE session SET nbr_courses = 0 WHERE nbr_courses IS NULL');
        $this->addSql('ALTER TABLE session CHANGE nbr_courses nbr_courses INT NOT NULL');

        $this->addSql('UPDATE session SET nbr_users = 0 WHERE nbr_users IS NULL');
        $this->addSql('ALTER TABLE session CHANGE nbr_users nbr_users INT NOT NULL');

        $this->addSql('UPDATE session SET nbr_classes = 0 WHERE nbr_classes IS NULL');
        $this->addSql('ALTER TABLE session CHANGE nbr_classes nbr_classes INT NOT NULL');

        $this->addSql('UPDATE user_friend_relation_type SET title = "No title" WHERE title IS NULL');
        $this->addSql('ALTER TABLE user_friend_relation_type CHANGE title title VARCHAR(20) NOT NULL');

        $this->addSql('UPDATE settings_options SET variable = "No variable name" WHERE variable IS NULL');
        $this->addSql('ALTER TABLE settings_options CHANGE variable variable VARCHAR(190) NOT NULL');

        if ($schema->hasTable('mail_template')) {
            $this->addSql('UPDATE mail_template SET name = "No name" WHERE name IS NULL');
            $this->addSql('ALTER TABLE mail_template CHANGE name name VARCHAR(255) NOT NULL');
        }

        $this->addSql('UPDATE session_rel_user SET duration = "0" WHERE duration IS NULL');
        $this->addSql('ALTER TABLE session_rel_user CHANGE duration duration INT NOT NULL');

        if ($schema->hasTable('portfolio_category')) {
            $this->addSql('UPDATE portfolio_category SET title = "No name" WHERE title IS NULL');
            $this->addSql('ALTER TABLE portfolio_category CHANGE title title LONGTEXT NOT NULL');
        }

        $this->addSql('UPDATE gradebook_linkeval_log SET name = "No name" WHERE name IS NULL');
        $this->addSql('ALTER TABLE gradebook_linkeval_log CHANGE name name LONGTEXT NOT NULL');

        $this->addSql('UPDATE session_category SET name = "No name" WHERE name IS NULL');
        $this->addSql('ALTER TABLE session_category CHANGE name name VARCHAR(100) NOT NULL');

        $this->addSql('UPDATE c_group_info SET name = "No name" WHERE name IS NULL');
        $this->addSql('ALTER TABLE c_group_info CHANGE name name VARCHAR(100) NOT NULL');

        $this->addSql('UPDATE c_group_info SET status = 0 WHERE status IS NULL');
        $this->addSql('ALTER TABLE c_group_info CHANGE status status TINYINT(1) NOT NULL');

        $this->addSql('UPDATE c_quiz_question_option SET name = "No name" WHERE name IS NULL');
        $this->addSql('ALTER TABLE c_quiz_question_option CHANGE name name VARCHAR(255) NOT NULL');

        $this->addSql('UPDATE c_survey SET title = "No title" WHERE title IS NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE title title LONGTEXT NOT NULL');

        $this->addSql('UPDATE c_dropbox_file SET title = "No title" WHERE title IS NULL');
        $this->addSql('ALTER TABLE c_dropbox_file CHANGE title title VARCHAR(255) NOT NULL');

        $this->addSql('UPDATE c_announcement SET title = "No title" WHERE title IS NULL');
        $this->addSql('ALTER TABLE c_announcement CHANGE title title LONGTEXT NOT NULL');

        $this->addSql('UPDATE c_quiz SET hide_question_title = 0 WHERE hide_question_title IS NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE hide_question_title hide_question_title TINYINT(1) NOT NULL');

        $this->addSql('UPDATE c_link SET title = "No name" WHERE title IS NULL');
        $this->addSql('ALTER TABLE c_link CHANGE title title VARCHAR(255) NOT NULL');

        $this->addSql('UPDATE c_forum_post SET post_title = "No name" WHERE post_title IS NULL');
        $this->addSql('ALTER TABLE c_forum_post CHANGE post_title post_title VARCHAR(250) NOT NULL');

        $this->addSql('UPDATE c_forum_post SET visible = 0 WHERE visible IS NULL');
        $this->addSql('ALTER TABLE c_forum_post CHANGE visible visible TINYINT(1) NOT NULL');

        $this->addSql('UPDATE c_link SET title = "No title" WHERE title IS NULL OR title = "" OR title = "/" ');
        $this->addSql('ALTER TABLE c_link CHANGE title title VARCHAR(255) NOT NULL');

        $this->addSql('UPDATE c_document SET title = "No title" WHERE title IS NULL OR title = "" OR title = "/" ');
        $this->addSql('ALTER TABLE c_document CHANGE title title VARCHAR(255) NOT NULL');

        $this->addSql('UPDATE c_forum_thread SET thread_title = "No name" WHERE thread_title IS NULL');
        $this->addSql('ALTER TABLE c_forum_thread CHANGE thread_title thread_title VARCHAR(255) NOT NULL');

        $this->addSql('UPDATE c_forum_thread SET thread_sticky = 0 WHERE thread_sticky IS NULL');
        $this->addSql('ALTER TABLE c_forum_thread CHANGE thread_sticky thread_sticky TINYINT(1) NOT NULL');

        $this->addSql('UPDATE ticket_message SET subject = "Ticket #"+ id WHERE subject IS NULL');
        $this->addSql('ALTER TABLE ticket_message CHANGE subject subject VARCHAR(255) NOT NULL');

        $this->addSql('UPDATE settings_current SET variable = "No name" WHERE variable IS NULL');
        $this->addSql('ALTER TABLE settings_current CHANGE variable variable VARCHAR(190) NOT NULL;');

        // Global tool.
        if (false === $schema->hasTable('tool')) {
            $this->addSql(
                'CREATE TABLE IF NOT EXISTS tool (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_20F33ED15E237E06 ON tool (name)');
        }

        $table = $schema->getTable('language');
        if ($table->hasIndex('idx_language_dokeos_folder')) {
            $this->addSql('DROP INDEX idx_language_dokeos_folder ON language;');
            $this->addSql('ALTER TABLE language DROP dokeos_folder;');
        }

        // Update language to ISO
        $this->addSql('UPDATE course SET course_language = (SELECT isocode FROM language WHERE english_name = course_language)');
        $this->addSql('UPDATE sys_announcement SET lang = (SELECT isocode FROM language WHERE english_name = lang);');
        $this->addSql("UPDATE settings_current SET selected_value = (SELECT isocode FROM language WHERE english_name = selected_value) WHERE variable = 'platformLanguage'");

        $this->addSql('UPDATE language SET english_name = "No name" WHERE english_name IS NULL');
        $this->addSql('ALTER TABLE language CHANGE english_name english_name VARCHAR(255) NOT NULL');

        $this->addSql('UPDATE language SET isocode = "en" WHERE isocode IS NULL');
        $this->addSql('ALTER TABLE language CHANGE isocode isocode VARCHAR(10) NOT NULL');

        $table = $schema->getTable('fos_group');
        if (false === $table->hasColumn('name')) {
            $this->addSql(
                'ALTER TABLE fos_group ADD name VARCHAR(180) NOT NULL, ADD roles LONGTEXT NOT NULL COMMENT "(DC2Type:array)";'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_4B019DDB5E237E06 ON fos_group (name);');
        }
        $this->addSql('ALTER TABLE fos_group CHANGE name name VARCHAR(255) NOT NULL');

        $table = $schema->getTable('fos_user_user_group');
        if ($table->hasForeignKey('FK_B3C77447A76ED395')) {
            $this->addSql('ALTER TABLE fos_user_user_group DROP FOREIGN KEY FK_B3C77447A76ED395');
            $this->addSql('ALTER TABLE fos_user_user_group ADD CONSTRAINT FK_B3C77447A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        } else {
            $this->addSql('ALTER TABLE fos_user_user_group ADD CONSTRAINT FK_B3C77447A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        }

        $this->addSql('ALTER TABLE course_request CHANGE user_id user_id INT DEFAULT NULL;');

        $table = $schema->getTable('course_request');
        if (false === $table->hasForeignKey('FK_33548A73A76ED395')) {
            $this->addSql(
                'ALTER TABLE course_request ADD CONSTRAINT FK_33548A73A76ED395 FOREIGN KEY (user_id) REFERENCES user (id);'
            );
            $this->addSql('CREATE INDEX IDX_33548A73A76ED395 ON course_request (user_id);');
        }

        if (false === $table->hasColumn('directory')) {
            $this->addSql('ALTER TABLE course_request DROP directory');
        }

        if (false === $table->hasColumn('db_name')) {
            $this->addSql('ALTER TABLE course_request DROP db_name');
        }

        $this->addSql('ALTER TABLE course_request CHANGE course_language course_language VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE course_request CHANGE title title VARCHAR(250) NOT NULL');

        $table = $schema->getTable('search_engine_ref');
        if (false === $table->hasColumn('c_id')) {
            $this->addSql('ALTER TABLE search_engine_ref ADD c_id INT DEFAULT NULL');
            $this->addSql('UPDATE search_engine_ref SET c_id = (SELECT id FROM course WHERE code = course_code)');
            $this->addSql('ALTER TABLE search_engine_ref DROP course_code');
            $this->addSql(
                'ALTER TABLE search_engine_ref ADD CONSTRAINT FK_473F037891D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);'
            );
            $this->addSql('CREATE INDEX IDX_473F037891D79BD3 ON search_engine_ref (c_id);');
        }

        $this->addSql('ALTER TABLE hook_observer CHANGE class_name class_name VARCHAR(190) DEFAULT NULL');
        $this->addSql('ALTER TABLE hook_event CHANGE class_name class_name VARCHAR(190) DEFAULT NULL;');
        $this->addSql('ALTER TABLE sequence_value CHANGE user_id user_id INT DEFAULT NULL');

        $table = $schema->getTable('sequence_value');
        if (false === $table->hasForeignKey('FK_66FBF12DA76ED395')) {
            $this->addSql(
                'ALTER TABLE sequence_value ADD CONSTRAINT FK_66FBF12DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        if (false === $table->hasIndex('IDX_66FBF12DA76ED395')) {
            $this->addSql('CREATE INDEX IDX_66FBF12DA76ED395 ON sequence_value (user_id)');
        }

        $table = $schema->getTable('branch_sync');
        $this->addSql('ALTER TABLE branch_sync CHANGE access_url_id access_url_id INT DEFAULT NULL');
        if (false === $table->hasForeignKey('FK_F62F45ED73444FD5')) {
            $this->addSql('ALTER TABLE branch_sync ADD CONSTRAINT FK_F62F45ED73444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id)');
        }
        if (false === $table->hasIndex('IDX_F62F45ED73444FD5')) {
            $this->addSql('CREATE INDEX IDX_F62F45ED73444FD5 ON branch_sync (access_url_id)');
        }

        $table = $schema->getTable('personal_agenda');
        if ($table->hasColumn('course')) {
            $this->addSql('ALTER TABLE personal_agenda DROP course');
        }

        if (false === $table->hasForeignKey('FK_D86124608D93D649')) {
            $this->addSql('ALTER TABLE personal_agenda ADD CONSTRAINT FK_D86124608D93D649 FOREIGN KEY (user) REFERENCES user (id) ON DELETE CASCADE');
        }

        //$this->addSql('ALTER TABLE c_tool_intro CHANGE id tool VARCHAR(255) NOT NULL');

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
            'templates',
        ];

        foreach ($tables as $table) {
            //$tableObj = $schema->getTable($table);
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
        */

        // Create tables.
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS scheduled_announcements (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, date DATETIME DEFAULT NULL, sent TINYINT(1) NOT NULL, session_id INT NOT NULL, c_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
        );
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS ext_translations (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(8) NOT NULL, object_class VARCHAR(190) NOT NULL, field VARCHAR(32) NOT NULL, foreign_key VARCHAR(64) NOT NULL, content LONGTEXT DEFAULT NULL, INDEX translations_lookup_idx (locale, object_class, foreign_key), UNIQUE INDEX lookup_unique_idx (locale, object_class, field, foreign_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
        );
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS ext_log_entries (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) NOT NULL, logged_at DATETIME NOT NULL, object_id VARCHAR(64) DEFAULT NULL, object_class VARCHAR(191) NOT NULL, version INT NOT NULL, data LONGTEXT DEFAULT NULL COMMENT "(DC2Type:array)", username VARCHAR(191) DEFAULT NULL, INDEX log_class_lookup_idx (object_class), INDEX log_date_lookup_idx (logged_at), INDEX log_user_lookup_idx (username), INDEX log_version_lookup_idx (object_id, object_class, version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
        );
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS mail_template (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, template LONGTEXT DEFAULT NULL, type VARCHAR(255) NOT NULL, score DOUBLE PRECISION DEFAULT NULL, result_id INT NOT NULL, default_template TINYINT(1) NOT NULL, `system` INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );

        $table = $schema->getTable('usergroup');
        if (false === $table->hasColumn('author_id')) {
            $this->addSql('ALTER TABLE usergroup ADD author_id INT DEFAULT NULL');
        }

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE usergroup ADD resource_node_id INT DEFAULT NULL');
        }

        // sequence_resource.
        $table = $schema->getTable('sequence_resource');

        if ($table->hasForeignKey('FK_34ADA43998FB19AE')) {
            $this->addSql('ALTER TABLE sequence_resource DROP FOREIGN KEY FK_34ADA43998FB19AE;');
        }

        $this->addSql('ALTER TABLE sequence_resource ADD CONSTRAINT FK_34ADA43998FB19AE FOREIGN KEY (sequence_id) REFERENCES sequence (id) ON DELETE CASCADE');

        // Update template.
        $table = $schema->getTable('templates');
        if ($table->hasColumn('course_code')) {
            $this->addSql('DELETE FROM templates WHERE course_code NOT IN (SELECT code FROM course)');
        }
        if (false === $table->hasColumn('c_id')) {
            $this->addSql('ALTER TABLE templates ADD c_id INT DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_6F287D8E91D79BD3 ON templates (c_id)');
            $this->addSql(
                'ALTER TABLE templates ADD CONSTRAINT FK_6F287D8E91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id)'
            );
            $this->addSql('UPDATE templates SET c_id = (SELECT id FROM course WHERE code = course_code)');
        }
        $this->addSql('ALTER TABLE templates CHANGE user_id user_id INT DEFAULT NULL');

        if (false === $table->hasIndex('IDX_6F287D8EA76ED395')) {
            $this->addSql('CREATE INDEX IDX_6F287D8EA76ED395 ON templates (user_id)');
        }
        if (false === $table->hasForeignKey('FK_6F287D8EA76ED395')) {
            $this->addSql('ALTER TABLE templates ADD CONSTRAINT FK_6F287D8EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        }

        // Drop unused columns
        $dropColumnsAndIndex = [
            'track_e_uploads' => [
                'columns' => ['upload_cours_id'],
                'index' => ['upload_cours_id'],
            ],
            'track_e_hotspot' => [
                'columns' => ['hotspot_course_code'],
                'index' => ['hotspot_course_code'],
            ],
            'templates' => [
                'columns' => ['course_code'],
                'index' => [],
            ],
            'personal_agenda' => [
                'columns' => ['hotspot_course_code'],
                'index' => [],
            ],
        ];

        foreach ($dropColumnsAndIndex as $tableName => $data) {
            if ($schema->hasTable($tableName)) {
                $indexList = $data['index'];
                foreach ($indexList as $index) {
                    if ($table->hasIndex($index)) {
                        $table->dropIndex($index);
                    }
                }

                $columns = $data['columns'];
                $table = $schema->getTable($tableName);
                foreach ($columns as $column) {
                    if ($table->hasColumn($column)) {
                        $table->dropColumn($column);
                    }
                }
            }
        }

        // Drop unused tables.
        $dropTables = [
            'event_email_template',
            'event_sent',
            'user_rel_event_type',
            'openid_association',
            'track_stored_values',
            'track_stored_values_stack',
            'course_module',
        ];
        foreach ($dropTables as $table) {
            if ($schema->hasTable($table)) {
                $schema->dropTable($table);
            }
        }
        $this->addSql('DROP TABLE c_resource');
        $this->addSql('DROP TABLE track_e_item_property');
    }

    public function down(Schema $schema): void
    {
    }
}
