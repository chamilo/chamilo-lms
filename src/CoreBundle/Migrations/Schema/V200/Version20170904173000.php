<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

/**
 * Class Version20170904173000.
 */
class Version20170904173000 extends AbstractMigrationChamilo
{
    public function getOrder()
    {
        return 3;
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema): void
    {
        $tblQuestion = $schema->getTable('c_survey_question');

        if (!$tblQuestion->hasColumn('is_required')) {
            $tblQuestion
                ->addColumn('is_required', Types::BOOLEAN)
                ->setDefault(false);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
