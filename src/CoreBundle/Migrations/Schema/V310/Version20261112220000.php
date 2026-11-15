<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261112220000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add manually triggered course automation actions with execution state.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('course_automation_action')) {
            $this->addSql(<<<'SQL'
CREATE TABLE course_automation_action (
    id INT AUTO_INCREMENT NOT NULL,
    c_id INT NOT NULL,
    session_id INT DEFAULT NULL,
    resource_link_id INT DEFAULT NULL,
    created_by_id INT DEFAULT NULL,
    last_executed_by_id INT DEFAULT NULL,
    event_type VARCHAR(16) NOT NULL,
    action_type VARCHAR(32) NOT NULL,
    target_id INT DEFAULT NULL,
    setting_value INT DEFAULT NULL,
    configuration_key VARCHAR(64) NOT NULL,
    status VARCHAR(16) DEFAULT 'pending' NOT NULL,
    attempts INT DEFAULT 0 NOT NULL,
    last_error LONGTEXT DEFAULT NULL,
    last_executed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    INDEX idx_course_automation_scope (c_id, session_id, event_type),
    INDEX idx_course_automation_session (session_id),
    INDEX idx_course_automation_link (resource_link_id),
    INDEX idx_course_automation_creator (created_by_id),
    INDEX idx_course_automation_executor (last_executed_by_id),
    UNIQUE INDEX uniq_course_automation_configuration (configuration_key),
    PRIMARY KEY(id),
    CONSTRAINT fk_course_automation_course FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE,
    CONSTRAINT fk_course_automation_session FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE,
    CONSTRAINT fk_course_automation_link FOREIGN KEY (resource_link_id) REFERENCES resource_link (id) ON DELETE CASCADE,
    CONSTRAINT fk_course_automation_creator FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL,
    CONSTRAINT fk_course_automation_executor FOREIGN KEY (last_executed_by_id) REFERENCES user (id) ON DELETE SET NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }

        if (!$schema->hasTable('course_automation_delivery')) {
            $this->addSql(<<<'SQL'
CREATE TABLE course_automation_delivery (
    id INT AUTO_INCREMENT NOT NULL,
    action_id INT NOT NULL,
    user_id INT NOT NULL,
    status VARCHAR(16) DEFAULT 'sending' NOT NULL,
    created_at DATETIME NOT NULL,
    sent_at DATETIME DEFAULT NULL,
    last_error LONGTEXT DEFAULT NULL,
    UNIQUE INDEX uniq_course_automation_recipient (action_id, user_id),
    INDEX idx_course_automation_delivery_user (user_id),
    PRIMARY KEY(id),
    CONSTRAINT fk_course_automation_delivery_action FOREIGN KEY (action_id) REFERENCES course_automation_action (id) ON DELETE CASCADE,
    CONSTRAINT fk_course_automation_delivery_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }
    }

    public function down(Schema $schema): void
    {
        // Keep configured actions and execution history during a rollback.
        $this->write('Course automation data is retained; drop the table manually only after a backup.');
    }
}
