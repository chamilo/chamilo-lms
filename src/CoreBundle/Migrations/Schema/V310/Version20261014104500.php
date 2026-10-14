<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261014104500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add Support Bot decision-tree and conversation tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE support_bot_tree (
            id INT AUTO_INCREMENT NOT NULL,
            access_url_id INT DEFAULT NULL,
            entry_node_id INT DEFAULT NULL,
            default_ticket_project_id INT DEFAULT NULL,
            created_by_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            version INT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_support_bot_tree_status_url (status, access_url_id),
            INDEX idx_support_bot_tree_access_url (access_url_id),
            INDEX idx_support_bot_tree_entry_node (entry_node_id),
            INDEX idx_support_bot_tree_default_project (default_ticket_project_id),
            INDEX idx_support_bot_tree_created_by (created_by_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE support_bot_node (
            id INT AUTO_INCREMENT NOT NULL,
            tree_id INT NOT NULL,
            content LONGTEXT NOT NULL,
            sort_order INT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_support_bot_node_tree (tree_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE support_bot_option (
            id INT AUTO_INCREMENT NOT NULL,
            node_id INT NOT NULL,
            target_node_id INT DEFAULT NULL,
            ticket_category_id INT DEFAULT NULL,
            label VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL,
            action_type VARCHAR(32) NOT NULL,
            target_url VARCHAR(500) DEFAULT NULL,
            closing_message LONGTEXT DEFAULT NULL,
            INDEX idx_support_bot_option_node (node_id),
            INDEX idx_support_bot_option_target_node (target_node_id),
            INDEX idx_support_bot_option_ticket_category (ticket_category_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE support_bot_conversation (
            id INT AUTO_INCREMENT NOT NULL,
            tree_id INT NOT NULL,
            user_id INT NOT NULL,
            access_url_id INT DEFAULT NULL,
            current_node_id INT DEFAULT NULL,
            resulting_ticket_id INT DEFAULT NULL,
            tree_version INT NOT NULL,
            outcome VARCHAR(20) NOT NULL,
            started_at DATETIME NOT NULL,
            ended_at DATETIME DEFAULT NULL,
            INDEX idx_support_bot_conv_tree_user (tree_id, user_id),
            INDEX idx_support_bot_conv_access_url (access_url_id),
            INDEX idx_support_bot_conv_current_node (current_node_id),
            INDEX idx_support_bot_conv_result_ticket (resulting_ticket_id),
            INDEX idx_support_bot_conv_outcome_started (outcome, started_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE support_bot_step (
            id INT AUTO_INCREMENT NOT NULL,
            conversation_id INT NOT NULL,
            node_id INT DEFAULT NULL,
            selected_option_id INT DEFAULT NULL,
            node_content LONGTEXT NOT NULL,
            selected_option_label VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_support_bot_step_conv_created (conversation_id, created_at),
            INDEX idx_support_bot_step_node (node_id),
            INDEX idx_support_bot_step_option (selected_option_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE support_bot_tree ADD CONSTRAINT fk_support_bot_tree_access_url FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE support_bot_tree ADD CONSTRAINT fk_support_bot_tree_default_project FOREIGN KEY (default_ticket_project_id) REFERENCES ticket_project (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE support_bot_tree ADD CONSTRAINT fk_support_bot_tree_created_by FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE support_bot_node ADD CONSTRAINT fk_support_bot_node_tree FOREIGN KEY (tree_id) REFERENCES support_bot_tree (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE support_bot_option ADD CONSTRAINT fk_support_bot_option_node FOREIGN KEY (node_id) REFERENCES support_bot_node (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE support_bot_option ADD CONSTRAINT fk_support_bot_option_target_node FOREIGN KEY (target_node_id) REFERENCES support_bot_node (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE support_bot_option ADD CONSTRAINT fk_support_bot_option_ticket_category FOREIGN KEY (ticket_category_id) REFERENCES ticket_category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE support_bot_conversation ADD CONSTRAINT fk_support_bot_conv_tree FOREIGN KEY (tree_id) REFERENCES support_bot_tree (id)');
        $this->addSql('ALTER TABLE support_bot_conversation ADD CONSTRAINT fk_support_bot_conv_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE support_bot_conversation ADD CONSTRAINT fk_support_bot_conv_access_url FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE support_bot_conversation ADD CONSTRAINT fk_support_bot_conv_current_node FOREIGN KEY (current_node_id) REFERENCES support_bot_node (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE support_bot_conversation ADD CONSTRAINT fk_support_bot_conv_ticket FOREIGN KEY (resulting_ticket_id) REFERENCES ticket_ticket (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE support_bot_step ADD CONSTRAINT fk_support_bot_step_conversation FOREIGN KEY (conversation_id) REFERENCES support_bot_conversation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE support_bot_step ADD CONSTRAINT fk_support_bot_step_node FOREIGN KEY (node_id) REFERENCES support_bot_node (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE support_bot_step ADD CONSTRAINT fk_support_bot_step_option FOREIGN KEY (selected_option_id) REFERENCES support_bot_option (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE support_bot_tree ADD CONSTRAINT fk_support_bot_tree_entry_node FOREIGN KEY (entry_node_id) REFERENCES support_bot_node (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE support_bot_tree DROP FOREIGN KEY fk_support_bot_tree_entry_node');
        $this->addSql('DROP TABLE support_bot_step');
        $this->addSql('DROP TABLE support_bot_conversation');
        $this->addSql('DROP TABLE support_bot_option');
        $this->addSql('DROP TABLE support_bot_node');
        $this->addSql('DROP TABLE support_bot_tree');
    }
}
