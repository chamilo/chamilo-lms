<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V111;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160623143200
 * Remove chatcall_date, chatcall_text, chatcall_user_id from User table.
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\V111
 */
class Version20160623143200 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $schema
            ->getTable('user')
            ->dropColumn('chatcall_user_id')
            ->dropColumn('chatcall_date')
            ->dropColumn('chatcall_text');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
