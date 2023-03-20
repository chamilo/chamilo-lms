<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20230124123419 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Rename c_lp.publicated_on to c_lp.published_on';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('c_lp')) {
            $this->addSql(
                'ALTER TABLE c_lp CHANGE publicated_on published_on datetime NULL;'
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('c_lp')) {
            $this->addSql(
                'ALTER TABLE c_lp CHANGE published_on publicated_on datetime NULL;'
            );
        }
    }
}
