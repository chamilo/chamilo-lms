<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V200;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Class Version20180115082400
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\V200
 */
class Version20180115082400 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $trackExe = $schema->getTable('track_e_exercises');
        $colSession = $trackExe->getColumn('session_id');

        if ($colSession->getType() != Type::INTEGER) {
            $this->addSql('ALTER TABLE track_e_exercises CHANGE session_id session_id INT NOT NULL');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
