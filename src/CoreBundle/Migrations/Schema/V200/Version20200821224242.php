<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20200821224242 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Messages';
    }

    public function up(Schema $schema): void
    {
        $connection = $this->getEntityManager()->getConnection();

        $table = $schema->getTable('message');
        $this->addSql('ALTER TABLE message CHANGE parent_id parent_id BIGINT DEFAULT NULL');

        if (!$table->hasColumn('msg_read')) {
            $this->addSql('ALTER TABLE message ADD msg_read TINYINT(1) NOT NULL');

            $this->addSql('UPDATE message SET msg_read = 1 WHERE msg_status = 1');
            $this->addSql('UPDATE message SET msg_read = 0 WHERE msg_status = 0');
            $this->addSql('UPDATE message SET msg_status = 1 WHERE msg_status = 0');
            $this->addSql('ALTER TABLE message CHANGE msg_status msg_type SMALLINT NOT NULL');
        }

        if (!$table->hasColumn('starred')) {
            $this->addSql('ALTER TABLE message ADD starred TINYINT(1) NOT NULL');
        }

        if (!$table->hasColumn('status')) {
            $this->addSql('ALTER TABLE message ADD status SMALLINT NOT NULL');
        }

        if ($table->hasIndex('idx_message_parent')) {
            $this->addSql('DROP INDEX idx_message_parent ON message');
        }

        $this->addSql('UPDATE message SET parent_id = NULL WHERE parent_id = 0');

        $sql = 'SELECT id, parent_id FROM message WHERE parent_id IS NOT NULL';
        $result = $connection->executeQuery($sql);
        $items = $result->fetchAllAssociative();
        foreach ($items as $item) {
            $id = $item['id'];
            $parentId = $item['parent_id'];
            $sql = "SELECT id FROM message WHERE id = $parentId";
            $result = $connection->executeQuery($sql);
            $subItem = $result->fetchAllAssociative();
            if (empty($subItem)) {
                $sql = "DELETE FROM message WHERE id = $id";
                $connection->executeQuery($sql);
            }
        }

        //$this->addSql('DELETE FROM message WHERE parent_id IS NOT NULL AND parent_id NOT IN (SELECT id FROM message)');

        $this->addSql('ALTER TABLE message CHANGE group_id group_id INT DEFAULT NULL');
        $this->addSql('UPDATE message SET group_id = NULL WHERE group_id = 0');

        //$this->addSql('DELETE FROM message WHERE parent_id IS NOT NULL AND parent_id in (select id FROM message WHERE user_sender_id NOT IN (SELECT id FROM user))');
        //$this->addSql('DELETE FROM message WHERE parent_id IS NOT NULL AND parent_id in (select id FROM message WHERE user_receiver_id NOT IN (SELECT id FROM user))');

        // Replace user_sender_id = 0 with the admin.
        $adminId = $this->getAdmin()->getId();
        $this->addSql("UPDATE message SET user_sender_id = $adminId WHERE user_sender_id IS NOT NULL AND user_sender_id NOT IN (SELECT id FROM user) ");

        //$this->addSql('DELETE FROM message WHERE user_sender_id NOT IN (SELECT id FROM user)');
        $this->addSql('DELETE FROM message WHERE user_receiver_id IS NOT NULL AND user_receiver_id NOT IN (SELECT id FROM user)');

        if (!$table->hasForeignKey('FK_B6BD307FFE54D947')) {
            $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FFE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid) ON DELETE CASCADE');
        }

        if (!$table->hasForeignKey('FK_B6BD307F727ACA70')) {
            $this->addSql(
                'ALTER TABLE message ADD CONSTRAINT FK_B6BD307F727ACA70 FOREIGN KEY (parent_id) REFERENCES message (id);'
            );
            $this->addSql('CREATE INDEX IDX_B6BD307F727ACA70 ON message (parent_id)');
        }
        $this->addSql('DELETE FROM message WHERE user_sender_id IS NULL OR user_sender_id = 0');

        if ($table->hasForeignKey('FK_B6BD307F64482423')) {
            $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F64482423');
        }

        if (!$schema->hasTable('message_rel_user')) {
            $this->addSql(
                'CREATE TABLE message_rel_user (id BIGINT AUTO_INCREMENT NOT NULL, message_id BIGINT NOT NULL, user_id INT NOT NULL, msg_read TINYINT(1) NOT NULL, starred TINYINT(1) NOT NULL, receiver_type SMALLINT NOT NULL, INDEX IDX_325D70B9537A1329 (message_id), INDEX IDX_325D70B9A76ED395 (user_id), UNIQUE INDEX message_receiver (message_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
            $this->addSql(
                'CREATE TABLE message_rel_user_rel_tags (message_rel_user_id BIGINT NOT NULL, message_tag_id BIGINT NOT NULL, INDEX IDX_B4B37A20962B5422 (message_rel_user_id), INDEX IDX_B4B37A208DF5FE1E (message_tag_id), PRIMARY KEY(message_rel_user_id, message_tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
            $this->addSql(
                'ALTER TABLE message_rel_user ADD CONSTRAINT FK_325D70B9537A1329 FOREIGN KEY (message_id) REFERENCES message (id)'
            );
            $this->addSql(
                'ALTER TABLE message_rel_user ADD CONSTRAINT FK_325D70B9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE message_rel_user_rel_tags ADD CONSTRAINT FK_B4B37A20962B5422 FOREIGN KEY (message_rel_user_id) REFERENCES message_rel_user (id) ON DELETE CASCADE'
            );
        }

        //$this->addSql('ALTER TABLE message CHANGE user_receiver_id user_receiver_id INT DEFAULT NULL');
        $this->addSql('UPDATE message SET user_receiver_id = NULL WHERE user_receiver_id = 0');

        $connection = $this->getEntityManager()->getConnection();
        $result = $connection->executeQuery('SELECT * FROM message WHERE user_receiver_id IS NOT NULL');
        $messages = $result->fetchAllAssociative();

        if ($messages) {
            foreach ($messages as $message) {
                $messageId = $message['id'];
                $receiverId = $message['user_receiver_id'];

                $result = $connection->executeQuery(" SELECT * FROM message_rel_user WHERE message_id = $messageId AND user_id = $receiverId");
                $exists = $result->fetchAllAssociative();

                if (empty($exists)) {
                    $this->addSql("INSERT INTO message_rel_user (message_id, user_id, msg_read, starred) VALUES('$messageId', '$receiverId', 1, 0) ");
                }
                //$this->addSql("UPDATE message SET user_receiver_id = NULL WHERE id = $messageId");
            }
        }

        if (false === $table->hasForeignKey('FK_B6BD307FF6C43E79')) {
            $this->addSql(
                'ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF6C43E79 FOREIGN KEY (user_sender_id) REFERENCES user (id)'
            );
        }

        if ($table->hasIndex('idx_message_user_receiver_status')) {
            $this->addSql('DROP INDEX idx_message_user_receiver_status ON message');
        }

        if ($table->hasIndex('idx_message_receiver_status_send_date')) {
            $this->addSql('DROP INDEX idx_message_receiver_status_send_date ON message');
        }

        if ($table->hasIndex('idx_message_status')) {
            $this->addSql('DROP INDEX idx_message_status ON message');
        }

        if ($table->hasIndex('idx_message_user_receiver_type')) {
            $this->addSql('DROP INDEX idx_message_user_receiver_type ON message');
        }

        if ($table->hasIndex('idx_message_receiver_type_send_date')) {
            $this->addSql('DROP INDEX idx_message_receiver_type_send_date ON message');
        }

        if ($table->hasIndex('idx_message_user_receiver')) {
            $this->addSql('DROP INDEX idx_message_user_receiver ON message');
        }

        if ($table->hasIndex('idx_message_user_sender_user_receiver')) {
            $this->addSql('DROP INDEX idx_message_user_sender_user_receiver ON message');
        }

        //ALTER TABLE message DROP user_receiver_id;
        if (!$table->hasIndex('idx_message_type')) {
            $this->addSql('CREATE INDEX idx_message_type ON message (msg_type)');
        }

        //$this->addSql('ALTER TABLE message CHANGE msg_status msg_status SMALLINT NOT NULL;');

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

        $this->addSql('DELETE FROM message_attachment WHERE message_id NOT IN (SELECT id FROM message)');

        $table = $schema->getTable('message_attachment');
        if (false === $table->hasIndex('IDX_B68FF524537A1329')) {
            $this->addSql('CREATE INDEX IDX_B68FF524537A1329 ON message_attachment (message_id)');
        }
        $this->addSql('ALTER TABLE message_attachment CHANGE message_id message_id BIGINT NOT NULL');

        if (false === $table->hasForeignKey('FK_B68FF524537A1329')) {
            $this->addSql('ALTER TABLE message_attachment ADD CONSTRAINT FK_B68FF524537A1329 FOREIGN KEY (message_id) REFERENCES message (id)');
        }

        if (!$table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE message_attachment ADD resource_node_id BIGINT DEFAULT NULL;');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B68FF5241BAD783F ON message_attachment (resource_node_id);');
        }

        if (!$table->hasForeignKey('FK_B68FF5241BAD783F')) {
            $this->addSql(' ALTER TABLE message_attachment ADD CONSTRAINT FK_B68FF5241BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;');
        }

        if (!$schema->hasTable('c_chat_conversation')) {
            $this->addSql('CREATE TABLE c_chat_conversation (id INT AUTO_INCREMENT NOT NULL, resource_node_id BIGINT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_CD09E33F1BAD783F (resource_node_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;');
            $this->addSql('ALTER TABLE c_chat_conversation ADD CONSTRAINT FK_CD09E33F1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE');
        }

        if (!$schema->hasTable('message_tag')) {
            $this->addSql("CREATE TABLE message_tag (id BIGINT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, tag VARCHAR(255) NOT NULL, color VARCHAR(255) NOT NULL, position INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', INDEX IDX_2ABC3D6FA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;");
            $this->addSql('CREATE TABLE message_rel_tags (message_id BIGINT NOT NULL, message_tag_id BIGINT NOT NULL, INDEX IDX_D07232D6537A1329 (message_id), INDEX IDX_D07232D68DF5FE1E (message_tag_id), PRIMARY KEY(message_id, message_tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;');
            $this->addSql('ALTER TABLE message_tag ADD CONSTRAINT FK_2ABC3D6FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE message_rel_tags ADD CONSTRAINT FK_D07232D6537A1329 FOREIGN KEY (message_id) REFERENCES message (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE message_rel_tags ADD CONSTRAINT FK_D07232D68DF5FE1E FOREIGN KEY (message_tag_id) REFERENCES message_tag (id) ON DELETE CASCADE');
            $this->addSql('CREATE UNIQUE INDEX user_tag ON message_tag (user_id, tag)');

            $this->addSql(
                'ALTER TABLE message_rel_user_rel_tags ADD CONSTRAINT FK_B4B37A208DF5FE1E FOREIGN KEY (message_tag_id) REFERENCES message_tag (id) ON DELETE CASCADE '
            );
        }
    }

    public function down(Schema $schema): void
    {
    }
}
