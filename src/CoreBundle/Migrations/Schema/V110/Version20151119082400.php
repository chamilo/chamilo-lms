<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add new indexes.
 */
class Version20151119082400 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $user = $schema->getTable('user');
        $user->addIndex(['user_id']);

        $userRelTag = $schema->getTable('user_rel_tag');
        $userRelTag->addIndex(['user_id']);
        $userRelTag->addIndex(['tag_id']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
