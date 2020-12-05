<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

/**
 * Class Version20
 * Migrate file to updated to Chamilo 2.0.
 */
class Version20 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        // Use $schema->createTable
        $this->addSql('set sql_mode=""');

        $table = $schema->getTable('access_url_rel_user');
        if (false === $table->hasColumn('id')) {
            $this->addSql('ALTER TABLE access_url_rel_user MODIFY COLUMN access_url_id INT NOT NULL');
            $this->addSql('ALTER TABLE access_url_rel_user MODIFY COLUMN user_id INT NOT NULL');
            $this->addSql('ALTER TABLE access_url_rel_user DROP PRIMARY KEY');
            $this->addSql(
                'ALTER TABLE access_url_rel_user ADD id INT AUTO_INCREMENT NOT NULL, CHANGE access_url_id access_url_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL, ADD PRIMARY KEY (id);'
            );
        }

        $table = $schema->getTable('access_url_rel_session');
        if (false === $table->hasColumn('id')) {
            $this->addSql('ALTER TABLE access_url_rel_session DROP PRIMARY KEY');
            $this->addSql(
                'ALTER TABLE access_url_rel_session ADD id INT AUTO_INCREMENT NOT NULL, CHANGE access_url_id access_url_id INT DEFAULT NULL, CHANGE session_id session_id INT DEFAULT NULL, ADD PRIMARY KEY (id);'
            );
            $this->addSql(
                'ALTER TABLE access_url_rel_session ADD CONSTRAINT FK_6CBA5F5D613FECDF FOREIGN KEY (session_id) REFERENCES session (id);'
            );
            $this->addSql(
                'ALTER TABLE access_url_rel_session ADD CONSTRAINT FK_6CBA5F5D73444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id);'
            );
            $this->addSql('CREATE INDEX IDX_6CBA5F5D613FECDF ON access_url_rel_session (session_id);');
            $this->addSql('CREATE INDEX IDX_6CBA5F5D73444FD5 ON access_url_rel_session (access_url_id);');
        }

        $table = $schema->getTable('fos_group');
        if (false === $table->hasColumn('name')) {
            $this->addSql(
                'ALTER TABLE fos_group ADD name VARCHAR(180) NOT NULL, ADD roles LONGTEXT NOT NULL COMMENT "(DC2Type:array)";'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_4B019DDB5E237E06 ON fos_group (name);');
        }

        $table = $schema->getTable('access_url');
        if (false === $table->hasColumn('limit_courses')) {
            $this->addSql(
                'ALTER TABLE access_url ADD limit_courses INT DEFAULT NULL, ADD limit_active_courses INT DEFAULT NULL, ADD limit_sessions INT DEFAULT NULL, ADD limit_users INT DEFAULT NULL, ADD limit_teachers INT DEFAULT NULL, ADD limit_disk_space INT DEFAULT NULL, ADD email VARCHAR(255) DEFAULT NULL;'
            );
        }
        $this->addSql('ALTER TABLE course_request CHANGE user_id user_id INT DEFAULT NULL;');
        $table = $schema->getTable('course_request');
        if (false === $table->hasForeignKey('FK_33548A73A76ED395')) {
            $this->addSql(
                'ALTER TABLE course_request ADD CONSTRAINT FK_33548A73A76ED395 FOREIGN KEY (user_id) REFERENCES user (id);'
            );
            $this->addSql('CREATE INDEX IDX_33548A73A76ED395 ON course_request (user_id);');
        }

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

        $connection = $this->getEntityManager()->getConnection();
        $sql = 'SELECT * FROM course_category';
        $result = $connection->executeQuery($sql);
        $all = $result->fetchAllAssociative();

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

        $table = $schema->getTable('course_category');
        if (false === $table->hasForeignKey('FK_AFF87497727ACA70')) {
            $this->addSql(
                'ALTER TABLE course_category ADD CONSTRAINT FK_AFF87497727ACA70 FOREIGN KEY (parent_id) REFERENCES course_category (id);'
            );
        }

        $this->addSql('ALTER TABLE hook_event CHANGE class_name class_name VARCHAR(190) DEFAULT NULL;');


        $table = $schema->getTable('c_tool');
        if (false === $table->hasForeignKey('FK_8456658091D79BD3')) {
            $this->addSql(
                'ALTER TABLE c_tool ADD CONSTRAINT FK_8456658091D79BD3 FOREIGN KEY (c_id) REFERENCES course (id)'
            );
        }

        $this->addSql('UPDATE c_tool SET name = "blog" WHERE name = "blog_management" ');
        $this->addSql('UPDATE c_tool SET name = "agenda" WHERE name = "calendar_event" ');
        $this->addSql('UPDATE c_tool SET name = "maintenance" WHERE name = "course_maintenance" ');
        $this->addSql('UPDATE c_tool SET name = "assignment" WHERE name = "student_publication" ');
        $this->addSql('UPDATE c_tool SET name = "settings" WHERE name = "course_setting" ');

        $this->addSql('UPDATE session_category SET date_start = NULL WHERE date_start = "0000-00-00"');
        $this->addSql('UPDATE session_category SET date_end = NULL WHERE date_end = "0000-00-00"');

        $this->addSql('DELETE FROM message WHERE user_sender_id IS NULL OR user_sender_id = 0');

        $this->addSql('ALTER TABLE message CHANGE user_receiver_id user_receiver_id INT DEFAULT NULL');
        $this->addSql('UPDATE message SET user_receiver_id = NULL WHERE user_receiver_id = 0');

        $this->addSql('DELETE FROM message WHERE user_sender_id NOT IN (SELECT id FROM user)');
        $this->addSql(
            'DELETE FROM message WHERE user_receiver_id IS NOT NULL AND user_receiver_id NOT IN (SELECT id FROM user)'
        );

        $table = $schema->getTable('message');
        if (false === $table->hasForeignKey('FK_B6BD307FF6C43E79')) {
            $this->addSql(
                'ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF6C43E79 FOREIGN KEY (user_sender_id) REFERENCES user (id)'
            );
        }
        if (false === $table->hasForeignKey('FK_B6BD307F64482423')) {
            $this->addSql(
                'ALTER TABLE message ADD CONSTRAINT FK_B6BD307F64482423 FOREIGN KEY (user_receiver_id) REFERENCES user (id)'
            );
        }

        //$table = $schema->getTable('c_document');
        /*if (!$table->hasIndex('idx_cdoc_path')) {
            $this->addSql('CREATE INDEX idx_cdoc_path ON c_document (path)');
        }
        if (!$table->hasIndex('idx_cdoc_size')) {
            $this->addSql('CREATE INDEX idx_cdoc_size ON c_document (size)');
        }
        if (!$table->hasIndex('idx_cdoc_id')) {
            $this->addSql('CREATE INDEX idx_cdoc_id ON c_document (id)');
        }
        if (!$table->hasIndex('idx_cdoc_type')) {
            $this->addSql('CREATE INDEX idx_cdoc_type ON c_document (filetype)');
        }
        if (!$table->hasIndex('idx_cdoc_sid')) {
            $this->addSql('CREATE INDEX idx_cdoc_sid ON c_document (session_id)');
        }*/

        /*$table = $schema->getTable('c_item_property');
        if (!$table->hasIndex('idx_cip_lasteditu')) {
            $this->addSql('CREATE INDEX idx_cip_lasteditu ON c_item_property (lastedit_user_id)');
        }
        if (!$table->hasIndex('idx_item_property_visibility')) {
            $this->addSql('CREATE INDEX idx_item_property_visibility ON c_item_property (visibility)');
        }*/

        $table = $schema->getTable('personal_agenda');
        if ($table->hasColumn('course')) {
            $this->addSql('ALTER TABLE personal_agenda DROP course');
        }

        $table = $schema->getTable('message');
        if (!$table->hasIndex('idx_message_user_receiver_status')) {
            $this->addSql('CREATE INDEX idx_message_user_receiver_status ON message (user_receiver_id, msg_status)');
        }

        if (!$table->hasIndex('idx_message_status')) {
            $this->addSql('CREATE INDEX idx_message_status ON message (msg_status)');
        }

        if (!$table->hasIndex('idx_message_receiver_status_send_date')) {
            $this->addSql(
                'CREATE INDEX idx_message_receiver_status_send_date ON message (user_receiver_id, msg_status, send_date)'
            );
        }

        $sql = 'UPDATE sys_announcement SET lang = (SELECT isocode FROM language WHERE english_name = lang);';
        $this->addSql($sql);
        //$this->addSql('ALTER TABLE c_tool_intro CHANGE id tool VARCHAR(255) NOT NULL');

        $table = $schema->getTable('user');
        if (!$table->hasColumn('date_of_birth')) {
            $this->addSql('ALTER TABLE user ADD date_of_birth DATETIME DEFAULT NULL');
        }
        if (!$table->hasColumn('website')) {
            $this->addSql('ALTER TABLE user ADD website VARCHAR(255) DEFAULT NULL');
        }
        if (!$table->hasColumn('biography')) {
            $this->addSql('ALTER TABLE user ADD biography VARCHAR(1000) DEFAULT NULL');
        }
        if (!$table->hasColumn('gender')) {
            $this->addSql('ALTER TABLE user ADD gender VARCHAR(1) DEFAULT NULL');
        }
        if (!$table->hasColumn('locale')) {
            $this->addSql('ALTER TABLE user ADD locale VARCHAR(8) DEFAULT NULL');
        }
        if (!$table->hasColumn('timezone')) {
            $this->addSql('ALTER TABLE user ADD timezone VARCHAR(64) DEFAULT NULL');
        }

        if (!$table->hasColumn('confirmation_token')) {
            $this->addSql('ALTER TABLE user ADD confirmation_token VARCHAR(180) DEFAULT NULL');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649C05FB297 ON user (confirmation_token)');
        } else {
            $this->addSql('ALTER TABLE user CHANGE confirmation_token confirmation_token VARCHAR(180) DEFAULT NULL');
        }

        $this->addSql('ALTER TABLE user CHANGE username_canonical username_canonical VARCHAR(180) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE lastname lastname VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE firstname firstname VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE phone phone VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE salt salt VARCHAR(255) DEFAULT NULL');

        //$this->addSql('ALTER TABLE c_student_publication ADD filesize INT DEFAULT NULL');
        $this->addSql(
            'UPDATE user SET created_at = registration_date WHERE CAST(created_at AS CHAR(20)) = "0000-00-00 00:00:00"'
        );
        $this->addSql(
            'UPDATE user SET updated_at = registration_date WHERE CAST(updated_at AS CHAR(20)) = "0000-00-00 00:00:00"'
        );

        $table = $schema->hasTable('message_feedback');
        if (false === $table) {
            $this->addSql(
                'CREATE TABLE message_feedback (id BIGINT AUTO_INCREMENT NOT NULL, message_id BIGINT NOT NULL, user_id INT NOT NULL, liked TINYINT(1) DEFAULT 0 NOT NULL, disliked TINYINT(1) DEFAULT 0 NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_DB0F8049537A1329 (message_id), INDEX IDX_DB0F8049A76ED395 (user_id), INDEX idx_message_feedback_uid_mid (message_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
            );
            $this->addSql(
                'ALTER TABLE message_feedback ADD CONSTRAINT FK_DB0F8049537A1329 FOREIGN KEY (message_id) REFERENCES message (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE message_feedback ADD CONSTRAINT FK_DB0F8049A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;'
            );
        }


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

        $this->addSql(
            'CREATE TABLE IF NOT EXISTS scheduled_announcements (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, date DATETIME DEFAULT NULL, sent TINYINT(1) NOT NULL, session_id INT NOT NULL, c_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
        );

        $table = $schema->getTable('session');
        if (!$table->hasColumn('position')) {
            $this->addSql('ALTER TABLE session ADD COLUMN position INT DEFAULT 0 NOT NULL');
        } else {
            $this->addSql('ALTER TABLE session CHANGE position position INT DEFAULT 0 NOT NULL');
        }

        $this->addSql('ALTER TABLE message CHANGE msg_status msg_status SMALLINT NOT NULL;');

        // Portfolio
        if (!$schema->hasTable('portfolio')) {
            $this->addSql(
                'CREATE TABLE portfolio_category (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, is_visible TINYINT(1) DEFAULT "1" NOT NULL, INDEX user (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
            );
            $this->addSql(
                'CREATE TABLE portfolio (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, c_id INT DEFAULT NULL, session_id INT DEFAULT NULL, category_id INT DEFAULT NULL, title LONGTEXT NOT NULL, content LONGTEXT NOT NULL, creation_date DATETIME NOT NULL, update_date DATETIME NOT NULL, is_visible TINYINT(1) DEFAULT "1" NOT NULL, INDEX user (user_id), INDEX course (c_id), INDEX session (session_id), INDEX category (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
            );
            $this->addSql(
                'ALTER TABLE portfolio_category ADD CONSTRAINT FK_7AC64359A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED1062A76ED395 FOREIGN KEY (user_id) REFERENCES user (id);'
            );
            $this->addSql(
                'ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED106291D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);'
            );
            $this->addSql(
                'ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED1062613FECDF FOREIGN KEY (session_id) REFERENCES session (id);'
            );
            $this->addSql(
                'ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED106212469DE2 FOREIGN KEY (category_id) REFERENCES portfolio_category (id);'
            );
        } else {
            $this->addSql('ALTER TABLE portfolio_category CHANGE title title LONGTEXT DEFAULT NULL');
        }

        // Skills
        if (!$schema->hasTable('skill_rel_item_rel_user')) {
            $this->addSql(
                'CREATE TABLE skill_rel_item_rel_user (id INT AUTO_INCREMENT NOT NULL, skill_rel_item_id INT NOT NULL, user_id INT NOT NULL, result_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, created_by INT NOT NULL, updated_by INT NOT NULL, INDEX IDX_D1133E0DFD4B12DC (skill_rel_item_id), INDEX IDX_D1133E0DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
            );
            $this->addSql(
                'CREATE TABLE skill_rel_item (id INT AUTO_INCREMENT NOT NULL, skill_id INT DEFAULT NULL, item_type INT NOT NULL, item_id INT NOT NULL, obtain_conditions VARCHAR(255) DEFAULT NULL, requires_validation TINYINT(1) NOT NULL, is_real TINYINT(1) NOT NULL, c_id INT DEFAULT NULL, session_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, created_by INT NOT NULL, updated_by INT NOT NULL, INDEX IDX_EB5B2A0D5585C142 (skill_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
            );
            $this->addSql(
                'CREATE TABLE skill_rel_course (id INT AUTO_INCREMENT NOT NULL, skill_id INT DEFAULT NULL, c_id INT NOT NULL, session_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_E7CEC7FA5585C142 (skill_id), INDEX IDX_E7CEC7FA91D79BD3 (c_id), INDEX IDX_E7CEC7FA613FECDF (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
            );
            $this->addSql(
                'ALTER TABLE skill_rel_item_rel_user ADD CONSTRAINT FK_D1133E0DFD4B12DC FOREIGN KEY (skill_rel_item_id) REFERENCES skill_rel_item (id);'
            );
            $this->addSql(
                'ALTER TABLE skill_rel_item_rel_user ADD CONSTRAINT FK_D1133E0DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);'
            );
            $this->addSql(
                'ALTER TABLE skill_rel_item ADD CONSTRAINT FK_EB5B2A0D5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id);'
            );
            $this->addSql(
                'ALTER TABLE skill_rel_course ADD CONSTRAINT FK_E7CEC7FA5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id);'
            );
            $this->addSql(
                'ALTER TABLE skill_rel_course ADD CONSTRAINT FK_E7CEC7FA91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);'
            );
            $this->addSql(
                'ALTER TABLE skill_rel_course ADD CONSTRAINT FK_E7CEC7FA613FECDF FOREIGN KEY (session_id) REFERENCES session (id);'
            );
        }

        $table = $schema->getTable('skill_rel_user');
        if (!$table->hasColumn('validation_status')) {
            $this->addSql('ALTER TABLE skill_rel_user ADD validation_status INT NOT NULL');
        }

        $this->addSql(
            'CREATE TABLE IF NOT EXISTS ext_translations (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(8) NOT NULL, object_class VARCHAR(190) NOT NULL, field VARCHAR(32) NOT NULL, foreign_key VARCHAR(64) NOT NULL, content LONGTEXT DEFAULT NULL, INDEX translations_lookup_idx (locale, object_class, foreign_key), UNIQUE INDEX lookup_unique_idx (locale, object_class, field, foreign_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
        );
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS ext_log_entries (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) NOT NULL, logged_at DATETIME NOT NULL, object_id VARCHAR(64) DEFAULT NULL, object_class VARCHAR(255) NOT NULL, version INT NOT NULL, data LONGTEXT DEFAULT NULL COMMENT "(DC2Type:array)", username VARCHAR(255) DEFAULT NULL, INDEX log_class_lookup_idx (object_class), INDEX log_date_lookup_idx (logged_at), INDEX log_user_lookup_idx (username), INDEX log_version_lookup_idx (object_id, object_class, version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
        );
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS tool (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
        );
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS tool_resource_right (id INT AUTO_INCREMENT NOT NULL, tool_id INT DEFAULT NULL, role VARCHAR(255) NOT NULL, mask INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
        );
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS resource_link (id INT AUTO_INCREMENT NOT NULL, resource_node_id INT DEFAULT NULL, session_id INT DEFAULT NULL, user_id INT DEFAULT NULL, c_id INT DEFAULT NULL, group_id INT DEFAULT NULL, usergroup_id INT DEFAULT NULL, visibility INT NOT NULL, start_visibility_at DATETIME DEFAULT NULL, end_visibility_at DATETIME DEFAULT NULL, INDEX IDX_398C394B1BAD783F (resource_node_id), INDEX IDX_398C394B613FECDF (session_id), INDEX IDX_398C394BA76ED395 (user_id), INDEX IDX_398C394B91D79BD3 (c_id), INDEX IDX_398C394BFE54D947 (group_id), INDEX IDX_398C394BD2112630 (usergroup_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
        );
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS resource_right (id INT AUTO_INCREMENT NOT NULL, resource_link_id INT DEFAULT NULL, role VARCHAR(255) NOT NULL, mask INT NOT NULL, INDEX IDX_9F710F26F004E599 (resource_link_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
        );
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS resource_node (id INT AUTO_INCREMENT NOT NULL, resource_type_id INT NOT NULL, resource_file_id INT DEFAULT NULL, creator_id INT NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, level INT DEFAULT NULL, path VARCHAR(3000) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_8A5F48FF98EC6B7B (resource_type_id), UNIQUE INDEX UNIQ_8A5F48FFCE6B9E84 (resource_file_id), INDEX IDX_8A5F48FF61220EA6 (creator_id), INDEX IDX_8A5F48FF727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
        );
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS resource_type (id INT AUTO_INCREMENT NOT NULL, tool_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_83FEF7938F7B22CC (tool_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
        );
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS resource_file (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, size INT NOT NULL, media_id INT DEFAULT NULL, enabled TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_83BF96AAEA9FDD75 (media_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
        );

        $table = $schema->getTable('resource_link');
        if (false === $table->hasForeignKey('FK_398C394B1BAD783F')) {
            $this->addSql(
                'ALTER TABLE resource_link ADD CONSTRAINT FK_398C394B1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id);'
            );
        }
        if (false === $table->hasForeignKey('FK_398C394B613FECDF')) {
            $this->addSql(
                'ALTER TABLE resource_link ADD CONSTRAINT FK_398C394B613FECDF FOREIGN KEY (session_id) REFERENCES session (id);'
            );
        }
        if (false === $table->hasForeignKey('FK_398C394BA76ED395')) {
            $this->addSql(
                'ALTER TABLE resource_link ADD CONSTRAINT FK_398C394BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);'
            );
        }
        if (false === $table->hasForeignKey('FK_398C394B91D79BD3')) {
            $this->addSql(
                'ALTER TABLE resource_link ADD CONSTRAINT FK_398C394B91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);'
            );
        }
        if (false === $table->hasForeignKey('FK_398C394BFE54D947')) {
            $this->addSql(
                'ALTER TABLE resource_link ADD CONSTRAINT FK_398C394BFE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid);'
            );
        }
        if (false === $table->hasForeignKey('FK_398C394BD2112630')) {
            $this->addSql(
                'ALTER TABLE resource_link ADD CONSTRAINT FK_398C394BD2112630 FOREIGN KEY (usergroup_id) REFERENCES usergroup (id);'
            );
        }

        $table = $schema->getTable('resource_right');
        if (false === $table->hasForeignKey('FK_9F710F26F004E599')) {
            $this->addSql(
                'ALTER TABLE resource_right ADD CONSTRAINT FK_9F710F26F004E599 FOREIGN KEY (resource_link_id) REFERENCES resource_link (id);'
            );
        }

        $table = $schema->getTable('resource_node');
        if (false === $table->hasForeignKey('FK_8A5F48FF98EC6B7B')) {
            $this->addSql(
                'ALTER TABLE resource_node ADD CONSTRAINT FK_8A5F48FF98EC6B7B FOREIGN KEY (resource_type_id) REFERENCES resource_type (id);'
            );
        }
        if (false === $table->hasForeignKey('FK_8A5F48FFCE6B9E84')) {
            $this->addSql(
                'ALTER TABLE resource_node ADD CONSTRAINT FK_8A5F48FFCE6B9E84 FOREIGN KEY (resource_file_id) REFERENCES resource_file (id);'
            );
        }
        if (false === $table->hasForeignKey('FK_8A5F48FF61220EA6')) {
            $this->addSql(
                'ALTER TABLE resource_node ADD CONSTRAINT FK_8A5F48FF61220EA6 FOREIGN KEY (creator_id) REFERENCES user (id) ON DELETE CASCADE;'
            );
        }
        if (false === $table->hasForeignKey('FK_8A5F48FF727ACA70')) {
            $this->addSql(
                'ALTER TABLE resource_node ADD CONSTRAINT FK_8A5F48FF727ACA70 FOREIGN KEY (parent_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
        }

        $table = $schema->getTable('resource_type');
        if (false === $table->hasForeignKey('FK_83FEF7938F7B22CC')) {
            $this->addSql(
                'ALTER TABLE resource_type ADD CONSTRAINT FK_83FEF7938F7B22CC FOREIGN KEY (tool_id) REFERENCES tool (id);'
            );
        }

        /*$table = $schema->getTable('resource_type');
        if (false === $table->hasForeignKey('FK_83BF96AAEA9FDD75')) {
            $this->addSql(
                'ALTER TABLE resource_file ADD CONSTRAINT FK_83BF96AAEA9FDD75 FOREIGN KEY (media_id) REFERENCES media__media (id);'
            );
        }*/

        $table = $schema->getTable('tool_resource_right');
        if (false === $table->hasForeignKey('FK_E5C562598F7B22CC')) {
            $this->addSql(
                'ALTER TABLE tool_resource_right ADD CONSTRAINT FK_E5C562598F7B22CC FOREIGN KEY (tool_id) REFERENCES tool (id);'
            );
        }

        $table = $schema->getTable('c_document');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_document ADD resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_document ADD CONSTRAINT FK_C9FA0CBD1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id);'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_C9FA0CBD1BAD783F ON c_document (resource_node_id);');
        }

        /*$this->addSql(
            'CREATE TABLE sylius_settings (id INT AUTO_INCREMENT NOT NULL, schema_alias VARCHAR(190) NOT NULL, namespace VARCHAR(190) DEFAULT NULL, parameters LONGTEXT NOT NULL COMMENT "(DC2Type:json_array)", UNIQUE INDEX UNIQ_1AFEFB2A894A31AD33E16B56 (schema_alias, namespace), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
        );*/

        // From configuration.dist.php 1.11.x
        $this->addSql('ALTER TABLE c_dropbox_file CHANGE filename filename VARCHAR(190) NOT NULL');
        $this->addSql('ALTER TABLE course_category CHANGE name name LONGTEXT NOT NULL;');
        $this->addSql('ALTER TABLE c_course_description CHANGE title title LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_thematic CHANGE title title LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE title title LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE c_lp_category CHANGE name name LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE c_glossary CHANGE name name LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE c_tool CHANGE name name LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE portfolio CHANGE title title LONGTEXT NOT NULL');


        $table = $schema->getTable('course_category');
        if (!$table->hasColumn('image')) {
            $this->addSql('ALTER TABLE course_category ADD image VARCHAR(255) DEFAULT NULL');
        }
        if (!$table->hasColumn('description')) {
            $this->addSql('ALTER TABLE course_category ADD description LONGTEXT DEFAULT NULL');
        }

        $this->addSql('ALTER TABLE block CHANGE path path VARCHAR(190) NOT NULL');

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

        $table = $schema->getTable('usergroup');
        if (!$table->hasColumn('author_id')) {
            $this->addSql('ALTER TABLE usergroup ADD author_id INT DEFAULT NULL');
        }

        //$table = $schema->getTable('c_group_info');
        /*if (false === $table->hasForeignKey('FK_CE06532491D79BD3')) {
            $this->addSql(
                'ALTER TABLE c_group_info ADD CONSTRAINT FK_CE06532491D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);'
            );
        }*/

        $this->addSql(
            'ALTER TABLE course_category CHANGE auth_course_child auth_course_child VARCHAR(40) DEFAULT NULL'
        );

        // WIP: Document - resource
        /*$this->addSql('ALTER TABLE c_document CHANGE c_id c_id INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE c_document ADD CONSTRAINT FK_C9FA0CBD91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE;'
        );
        $this->addSql('ALTER TABLE c_document CHANGE session_id session_id INT DEFAULT NULL;');
        $this->addSql('UPDATE c_document SET session_id = null WHERE session_id = 0');
        $this->addSql(
            'ALTER TABLE c_document ADD CONSTRAINT FK_C9FA0CBD613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE'
        );
        $this->addSql('CREATE INDEX IDX_C9FA0CBD613FECDF ON c_document (session_id)');*/

        $this->addSql(
            'ALTER TABLE access_url_rel_course_category CHANGE access_url_id access_url_id INT DEFAULT NULL, CHANGE course_category_id course_category_id INT DEFAULT NULL'
        );
        $table = $schema->getTable('access_url_rel_course_category');
        if (false === $table->hasForeignKey('FK_3545C2A673444FD5')) {
            $this->addSql(
                'ALTER TABLE access_url_rel_course_category ADD CONSTRAINT FK_3545C2A673444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id)'
            );
        }
        if (false === $table->hasForeignKey('FK_3545C2A66628AD36')) {
            $this->addSql(
                'ALTER TABLE access_url_rel_course_category ADD CONSTRAINT FK_3545C2A66628AD36 FOREIGN KEY (course_category_id) REFERENCES course_category (id)'
            );
        }
        if (false === $table->hasIndex('IDX_3545C2A673444FD5')) {
            $this->addSql('CREATE INDEX IDX_3545C2A673444FD5 ON access_url_rel_course_category (access_url_id)');
        }
        if (false === $table->hasIndex('IDX_3545C2A66628AD36')) {
            $this->addSql('CREATE INDEX IDX_3545C2A66628AD36 ON access_url_rel_course_category (course_category_id)');
        }

        $this->addSql('ALTER TABLE access_url_rel_usergroup CHANGE access_url_id access_url_id INT DEFAULT NULL');

        $table = $schema->getTable('access_url_rel_usergroup');
        if (false === $table->hasForeignKey('FK_AD488DD573444FD5')) {
            $this->addSql(
                'ALTER TABLE access_url_rel_usergroup ADD CONSTRAINT FK_AD488DD573444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id)'
            );
        }
        if (false === $table->hasIndex('IDX_AD488DD573444FD5')) {
            $this->addSql('CREATE INDEX IDX_AD488DD573444FD5 ON access_url_rel_usergroup (access_url_id)');
        }

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

        $this->addSql('ALTER TABLE c_group_info CHANGE category_id category_id INT DEFAULT NULL');


        $table = $schema->getTable('message_attachment');
        if (false === $table->hasIndex('IDX_B68FF524537A1329')) {
            $this->addSql('CREATE INDEX IDX_B68FF524537A1329 ON message_attachment (message_id)');
        }
        $this->addSql('ALTER TABLE message_attachment CHANGE message_id message_id BIGINT NOT NULL');

        if (false === $table->hasForeignKey('FK_B68FF524537A1329')) {
            $this->addSql('ALTER TABLE message_attachment ADD CONSTRAINT FK_B68FF524537A1329 FOREIGN KEY (message_id) REFERENCES message (id)');
        }

        $table = $schema->getTable('user_course_category');
        if (!$table->hasColumn('collapsed')) {
            $this->addSql('ALTER TABLE user_course_category ADD collapsed TINYINT(1) DEFAULT NULL');
        }

        // Drop unused columns
        $dropColumnsAndIndex = [
            'track_e_uploads' => ['columns' => ['upload_cours_id'], 'index' => ['upload_cours_id']],
            'track_e_hotspot' => ['columns' => ['hotspot_course_code'], 'index' => ['hotspot_course_code']],
            'templates' => ['columns' => ['course_code'], 'index' => []],
            'personal_agenda' => ['columns' => ['hotspot_course_code'], 'index' => []],
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

        // Drop tables
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

        if (false === $schema->hasTable('illustration')) {
            $this->addSql(
                'CREATE TABLE illustration (id INT AUTO_INCREMENT NOT NULL, resource_node_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_D67B9A421BAD783F (resource_node_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
            );
            $this->addSql(
                'ALTER TABLE illustration ADD CONSTRAINT FK_D67B9A421BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
        }
        //$this->addSql('ALTER TABLE c_document CHANGE path path VARCHAR(255) DEFAULT NULL;');

        $table = $schema->getTable('c_announcement');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_announcement ADD resource_node_id INT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE c_announcement ADD CONSTRAINT FK_39912E021BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_39912E021BAD783F ON c_announcement (resource_node_id);');
        }

        $table = $schema->getTable('c_link');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_link ADD resource_node_id INT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE c_link ADD CONSTRAINT FK_9209C2A01BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_9209C2A01BAD783F ON c_link (resource_node_id);');
        }

        $table = $schema->getTable('user');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE user ADD resource_node_id INT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE user ADD CONSTRAINT FK_8D93D6491BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6491BAD783F ON user (resource_node_id);');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
