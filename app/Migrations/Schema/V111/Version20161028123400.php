<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20161028123400
 * Add primary key as auto increment in c_student_publication_comment
 * @package Application\Migrations\Schema\V111
 */
class Version20161028123400 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $iidColumn = $schema
            ->getTable('c_student_publication_comment')
            ->getColumn('iid');

        if (!$iidColumn->getAutoincrement()) {
            $iidColumn->setAutoincrement(true);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema
            ->getTable('c_student_publication_comment')
            ->getColumn('iid')
            ->setAutoincrement(false);
    }
}
