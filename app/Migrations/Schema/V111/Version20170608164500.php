<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Class Version20170608164500
 *
 * Fix c_quiz_question changing data type of type field to integer
 *
 * @package Application\Migrations\Schema\V111
 */
class Version20170608164500 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        error_log('Version20170608164500');
        $schema
            ->getTable('c_quiz_question')
            ->getColumn('type')
            ->setType(Type::getType(Type::INTEGER));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema
            ->getTable('c_quiz_question')
            ->getColumn('type')
            ->setType(Type::getType(Type::BOOLEAN));
    }
}
