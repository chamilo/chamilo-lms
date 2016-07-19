<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150713132630
 *
 * @package Application\Migrations\Schema\V11010
 */
class Version20150805161000 extends AbstractMigrationChamilo
{

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sessionTable = $schema->getTable('session');

        $sessionTable->addColumn(
            'send_subscription_notification',
            \Doctrine\DBAL\Types\Type::BOOLEAN,
            ['default' => false]
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $sessionTable = $schema->getTable('session');
        $sessionTable->dropColumn('send_subscription_notification');
    }

}
