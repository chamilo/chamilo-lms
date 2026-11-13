<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261113195000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add scoped personality categories to modern surveys without restoring legacy survey groups.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('c_survey_personality_category')) {
            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE c_survey_personality_category (
    iid INT AUTO_INCREMENT NOT NULL,
    survey_id INT NOT NULL,
    label VARCHAR(120) NOT NULL,
    sort INT NOT NULL,
    INDEX idx_personality_category_survey (survey_id),
    UNIQUE INDEX uniq_personality_survey_label (survey_id, label),
    PRIMARY KEY(iid),
    CONSTRAINT fk_personality_category_survey FOREIGN KEY (survey_id) REFERENCES c_survey (iid) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('c_survey_personality_category')) {
            $this->addSql('DROP TABLE c_survey_personality_category');
        }
    }
}
