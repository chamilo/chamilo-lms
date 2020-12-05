<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * LPs.
 */
class Version20190110182615 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_lp CHANGE author author LONGTEXT NOT NULL');

        $table = $schema->getTable('c_lp');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_lp ADD resource_node_id INT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE c_lp ADD CONSTRAINT FK_F67ABBEB1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_F67ABBEB1BAD783F ON c_lp (resource_node_id);');
        }

        $table = $schema->getTable('c_lp_item_view');
        if (false == $table->hasIndex('idx_c_lp_item_view_cid_id_view_count')) {
            /*$this->addSql(
                'CREATE INDEX idx_c_lp_item_view_cid_id_view_count ON c_lp_item_view (c_id, id, view_count)'
            );*/
        }
    }

    public function down(Schema $schema): void
    {
    }
}
