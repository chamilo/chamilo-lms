<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160304151300
 */
class Version20160304151300 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $this->addSql("UPDATE extra_field SET visible = 0 WHERE variable IN('mail_notify_invitation', 'mail_notify_message', 'mail_notify_group_message') AND extra_field_type = 1");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
