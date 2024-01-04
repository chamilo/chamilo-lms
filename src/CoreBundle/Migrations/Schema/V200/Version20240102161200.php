<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240102161200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add contact category table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE contact_form_contact_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");
    }
}
