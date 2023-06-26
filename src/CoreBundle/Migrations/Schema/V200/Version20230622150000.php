<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20230622150000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Rename table c_lp_category_user';
    }

    public function up(Schema $schema): void
    {

        if ($schema->hasTable('c_lp_category_user')) {
            $this->addSql("ALTER TABLE c_lp_category_user RENAME TO c_lp_category_rel_user");
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('c_lp_category_rel_user')) {
            $this->addSql("ALTER TABLE c_lp_category_rel_user RENAME TO c_lp_category_user");
        }
    }
}
