<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261127090000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Store survey invitation dispatch history and its recipient associations.';
    }

    public function up(Schema $schema): void
    {
        $liveSchema = $this->connection->createSchemaManager()->introspectSchema();
        if (!$liveSchema->hasTable('c_survey_invitation_history')) {
            $this->addSql(<<<'SQL'
CREATE TABLE c_survey_invitation_history (
    iid INT AUTO_INCREMENT NOT NULL,
    survey_id INT NOT NULL,
    c_id INT NOT NULL,
    session_id INT DEFAULT NULL,
    sent_at DATETIME NOT NULL,
    email_title LONGTEXT NOT NULL,
    email_body LONGTEXT NOT NULL,
    recipient_count INT NOT NULL,
    unanswered_only TINYINT(1) NOT NULL,
    hide_link TINYINT(1) NOT NULL,
    INDEX idx_survey_history_context (survey_id, c_id, session_id, iid),
    INDEX idx_survey_history_course (c_id),
    INDEX idx_survey_history_session (session_id),
    PRIMARY KEY(iid),
    CONSTRAINT fk_survey_history_survey FOREIGN KEY (survey_id) REFERENCES c_survey (iid) ON DELETE CASCADE,
    CONSTRAINT fk_survey_history_course FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE,
    CONSTRAINT fk_survey_history_session FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }

        if (!$liveSchema->hasTable('c_survey_invitation_history_rel_invitation')) {
            $this->addSql(<<<'SQL'
CREATE TABLE c_survey_invitation_history_rel_invitation (
    history_id INT NOT NULL,
    invitation_id INT NOT NULL,
    INDEX idx_survey_history_invitation (invitation_id),
    PRIMARY KEY(history_id, invitation_id),
    CONSTRAINT fk_survey_history_rel_history FOREIGN KEY (history_id) REFERENCES c_survey_invitation_history (iid) ON DELETE CASCADE,
    CONSTRAINT fk_survey_history_rel_invitation FOREIGN KEY (invitation_id) REFERENCES c_survey_invitation (iid) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }
    }

    public function down(Schema $schema): void
    {
        // Do not erase historical dispatches during rollback. Old code ignores both tables.
    }
}
