<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

/**
 * Group changes.
 */
class Version20170904173000 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('c_group_rel_user');

        $this->addSql('DELETE FROM c_group_rel_user WHERE user_id NOT IN (SELECT id FROM user)');
        $this->addSql('DELETE FROM c_group_rel_user WHERE group_id NOT IN (SELECT iid FROM c_group_info)');
        if (false === $table->hasForeignKey('FK_C5D3D49FA76ED395')) {
            $this->addSql(
                'ALTER TABLE c_group_rel_user ADD CONSTRAINT FK_C5D3D49FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)'
            );
        }

        if (false === $table->hasForeignKey('FK_C5D3D49FFE54D947')) {
            $this->addSql(
                'ALTER TABLE c_group_rel_user ADD CONSTRAINT FK_C5D3D49FFE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid)'
            );
        }
        if (false === $table->hasIndex('IDX_C5D3D49FA76ED395')) {
            $this->addSql('CREATE INDEX IDX_C5D3D49FA76ED395 ON c_group_rel_user (user_id)');
        }
        if (false === $table->hasIndex('IDX_C5D3D49FFE54D947')) {
            $this->addSql('CREATE INDEX IDX_C5D3D49FFE54D947 ON c_group_rel_user (group_id)');
        }

        $this->addSql('DELETE FROM c_group_rel_tutor WHERE user_id NOT IN (SELECT id FROM user)');
        $this->addSql('DELETE FROM c_group_rel_tutor WHERE group_id NOT IN (SELECT iid FROM c_group_info)');

        $table = $schema->getTable('c_group_rel_tutor');
        if (false === $table->hasForeignKey('FK_F6FF71ABA76ED395')) {
            $this->addSql(
                'ALTER TABLE c_group_rel_tutor ADD CONSTRAINT FK_F6FF71ABA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)'
            );
        }
        if (false === $table->hasForeignKey('FK_F6FF71ABFE54D947')) {
            $this->addSql(
                'ALTER TABLE c_group_rel_tutor ADD CONSTRAINT FK_F6FF71ABFE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid)'
            );
        }
        if (false === $table->hasIndex('IDX_F6FF71ABA76ED395')) {
            $this->addSql('CREATE INDEX IDX_F6FF71ABA76ED395 ON c_group_rel_tutor (user_id)');
        }
        if (false === $table->hasIndex('IDX_F6FF71ABFE54D947')) {
            $this->addSql('CREATE INDEX IDX_F6FF71ABFE54D947 ON c_group_rel_tutor (group_id)');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
