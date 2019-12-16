<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150713132630.
 */
class Version20150805161000 extends AbstractMigrationChamilo
{
    public function up(Schema $schema)
    {
        $sessionTable = $schema->getTable('session');

        $sessionTable->addColumn(
            'send_subscription_notification',
            \Doctrine\DBAL\Types\Type::BOOLEAN,
            ['default' => false]
        );
    }

    public function down(Schema $schema)
    {
        $sessionTable = $schema->getTable('session');
        $sessionTable->dropColumn('send_subscription_notification');
    }
}
