<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20240313111800 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Removing display_order, position, cat_order columns from c_lp, c_lp_category, c_forum_category tables';
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_lp DROP display_order');
        $this->addSql('ALTER TABLE c_lp_category DROP position');
        $this->addSql('ALTER TABLE c_forum_category DROP cat_order');
    }
}
