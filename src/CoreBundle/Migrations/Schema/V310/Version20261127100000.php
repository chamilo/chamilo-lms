<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261127100000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Store external survey recipients and explicit automatic invitation policies.';
    }

    public function up(Schema $schema): void
    {
        $liveSchema = $this->connection->createSchemaManager()->introspectSchema();
        if (!$liveSchema->hasTable('c_survey_external_invitation')) {
            $this->addSql(<<<'SQL'
CREATE TABLE c_survey_external_invitation (
    id INT AUTO_INCREMENT NOT NULL,
    invitation_id INT NOT NULL,
    access_url_id INT NOT NULL,
    email VARCHAR(254) NOT NULL,
    UNIQUE INDEX uniq_survey_external_invitation (invitation_id),
    INDEX idx_survey_external_portal (access_url_id),
    PRIMARY KEY(id),
    CONSTRAINT fk_survey_external_invitation FOREIGN KEY (invitation_id) REFERENCES c_survey_invitation (iid) ON DELETE CASCADE,
    CONSTRAINT fk_survey_external_portal FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }

        if (!$liveSchema->hasTable('c_survey_auto_invitation')) {
            $this->addSql(<<<'SQL'
CREATE TABLE c_survey_auto_invitation (
    context_key VARCHAR(100) NOT NULL,
    survey_id INT NOT NULL,
    c_id INT NOT NULL,
    session_id INT DEFAULT NULL,
    access_url_id INT NOT NULL,
    after_subscription_id INT NOT NULL,
    subject LONGTEXT NOT NULL,
    body LONGTEXT NOT NULL,
    INDEX idx_survey_auto_survey (survey_id),
    INDEX idx_survey_auto_course (c_id),
    INDEX idx_survey_auto_session (session_id),
    INDEX idx_survey_auto_portal (access_url_id),
    PRIMARY KEY(context_key),
    CONSTRAINT fk_survey_auto_survey FOREIGN KEY (survey_id) REFERENCES c_survey (iid) ON DELETE CASCADE,
    CONSTRAINT fk_survey_auto_course FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE,
    CONSTRAINT fk_survey_auto_session FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE,
    CONSTRAINT fk_survey_auto_portal FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }
    }

    public function down(Schema $schema): void
    {
        // Preserve external recipients and policies. Disable scheduling before rolling back the code.
    }
}
