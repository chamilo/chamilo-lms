<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20170904173000
 * @package Application\Migrations\Schema\V200
 */
class Version20170904173000 extends AbstractMigrationChamilo
{
    /**
    * {@inheritdoc}
    */
    public function getOrder()
    {
        return 3;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $tblQuestion = $schema->getTable('c_survey_question');

        if (!$tblQuestion->hasColumn('is_required')) {
            $tblQuestion
                ->addColumn('is_required', Type::BOOLEAN)
                ->setDefault(false);
        }
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
