<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20230405123419 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Resize c_document.filetype to VARCHAR(15) to allow for more types (i.e. certificate)';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('c_document')) {
            $this->addSql(
                'ALTER TABLE c_document MODIFY filetype VARCHAR(15);'
            );
        }
        if ($schema->hasTable('gradebook_category')) {
            $this->addSql('UPDATE gradebook_category SET document_id = NULL WHERE document_id NOT IN (SELECT iid FROM c_document)');
            $this->addSql(
                'ALTER TABLE gradebook_category ADD CONSTRAINT FK_96A4C705C33F7837 FOREIGN KEY (document_id) REFERENCES c_document (iid) ON DELETE SET NULL;'
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('gradebook_category')) {
            $this->addSql(
                'ALTER TABLE gradebook_category DROP CONSTRAINT FK_96A4C705C33F7837;'
            );
        }
        if ($schema->hasTable('c_document')) {
            $this->addSql(
                'ALTER TABLE c_document MODIFY filetype VARCHAR(10);'
            );
        }
    }
}
