<?php

declare (strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20230405123419 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Resize c_document.filetype to VARCHAR(15)';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('c_document')) {
            $this->addSql(
                'ALTER TABLE c_document MODIFY filetype VARCHAR(15);'
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('c_document')) {
            $this->addSql(
                'ALTER TABLE c_document MODIFY filetype VARCHAR(10);'
            );
        }
    }
}
