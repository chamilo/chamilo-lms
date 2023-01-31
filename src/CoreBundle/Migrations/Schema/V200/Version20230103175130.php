<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230103175130 extends AbstractMigrationChamilo
{
    /**
     * Return desription of the migration step.
     */
    public function getDescription(): string
    {
        return 'change field color length 20 characters';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE personal_agenda CHANGE color color varchar(20) NULL;'
        );
        $this->addSql(
            'ALTER TABLE c_calendar_event CHANGE color color varchar(20) NULL;'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE c_calendar_event CHANGE color color varchar(100) NULL;'
        );
        $this->addSql(
            'ALTER TABLE personal_agenda CHANGE color color varchar(255) NULL;'
        );
    }
}
