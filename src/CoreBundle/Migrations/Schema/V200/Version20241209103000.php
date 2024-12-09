<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20241209103000 extends AbstractMigrationChamilo
{

    public function getDescription(): string
    {
        return "Change extra field boolean columns (visible_to_self, visible_to_others, changeable, filter) to not accept null values.";
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE extra_field SET visible_to_self = 0 WHERE visible_to_self IS NULL");
        $this->addSql("UPDATE extra_field SET visible_to_others = 0 WHERE visible_to_others IS NULL");
        $this->addSql("UPDATE extra_field SET changeable = 0 WHERE changeable IS NULL");
        $this->addSql("UPDATE extra_field SET filter = 0 WHERE filter IS NULL");

        $this->addSql("ALTER TABLE extra_field CHANGE visible_to_self visible_to_self TINYINT(1) DEFAULT 0 NOT NULL, CHANGE visible_to_others visible_to_others TINYINT(1) DEFAULT 0 NOT NULL, CHANGE changeable changeable TINYINT(1) DEFAULT 0 NOT NULL, CHANGE filter filter TINYINT(1) DEFAULT 0 NOT NULL");
    }
}