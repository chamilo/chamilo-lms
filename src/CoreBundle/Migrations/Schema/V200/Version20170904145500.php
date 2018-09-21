<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20170904145500.
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\V200
 */
class Version20170904145500 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('DELETE FROM c_group_rel_user WHERE user_id NOT IN (SELECT id FROM user)');
        $this->addSql('DELETE FROM c_group_rel_user WHERE group_id NOT IN (SELECT iid FROM c_group_info)');
        $this->addSql('ALTER TABLE c_group_rel_user ADD CONSTRAINT FK_C5D3D49FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE c_group_rel_user ADD CONSTRAINT FK_C5D3D49FFE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid)');
        $this->addSql('CREATE INDEX IDX_C5D3D49FA76ED395 ON c_group_rel_user (user_id)');
        $this->addSql('CREATE INDEX IDX_C5D3D49FFE54D947 ON c_group_rel_user (group_id)');

        $this->addSql('DELETE FROM c_group_rel_tutor WHERE user_id NOT IN (SELECT id FROM user)');
        $this->addSql('DELETE FROM c_group_rel_tutor WHERE group_id NOT IN (SELECT iid FROM c_group_info)');
        $this->addSql('ALTER TABLE c_group_rel_tutor ADD CONSTRAINT FK_F6FF71ABA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE c_group_rel_tutor ADD CONSTRAINT FK_F6FF71ABFE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid)');
        $this->addSql('CREATE INDEX IDX_F6FF71ABA76ED395 ON c_group_rel_tutor (user_id)');
        $this->addSql('CREATE INDEX IDX_F6FF71ABFE54D947 ON c_group_rel_tutor (group_id)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
