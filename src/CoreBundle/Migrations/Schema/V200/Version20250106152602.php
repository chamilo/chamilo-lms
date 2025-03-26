<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250106152602 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Change iso code length to 8 characters.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE language CHANGE isocode isocode VARCHAR('.Language::ISO_MAX_LENGTH.') NOT NULL');
    }
}
