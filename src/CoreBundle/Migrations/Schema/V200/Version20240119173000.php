<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240119173000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Modify track_e_downloads table structure - make down_doc_path nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE track_e_downloads CHANGE COLUMN down_doc_path down_doc_path varchar(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE track_e_downloads CHANGE COLUMN down_doc_path down_doc_path varchar(255) NOT NULL');
    }
}
