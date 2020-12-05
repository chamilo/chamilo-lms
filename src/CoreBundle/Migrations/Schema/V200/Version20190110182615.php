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

        if (false === $schema->hasTable('c_lp_rel_usergroup') ) {
            $this->addSql(
                'CREATE TABLE c_lp_rel_usergroup (id INT AUTO_INCREMENT NOT NULL, lp_id INT DEFAULT NULL, session_id INT DEFAULT NULL, c_id INT NOT NULL, usergroup_id INT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_DB8689FF68DFD1EF (lp_id), INDEX IDX_DB8689FF613FECDF (session_id), INDEX IDX_DB8689FF91D79BD3 (c_id), INDEX IDX_DB8689FFD2112630 (usergroup_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
            );
            $this->addSql(
                'ALTER TABLE c_lp_rel_usergroup ADD CONSTRAINT FK_DB8689FF68DFD1EF FOREIGN KEY (lp_id) REFERENCES c_lp (iid);'
            );
            $this->addSql(
                'ALTER TABLE c_lp_rel_usergroup ADD CONSTRAINT FK_DB8689FF613FECDF FOREIGN KEY (session_id) REFERENCES session (id);'
            );
            $this->addSql(
                'ALTER TABLE c_lp_rel_usergroup ADD CONSTRAINT FK_DB8689FF91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);'
            );
            $this->addSql(
                'ALTER TABLE c_lp_rel_usergroup ADD CONSTRAINT FK_DB8689FFD2112630 FOREIGN KEY (usergroup_id) REFERENCES usergroup (id);'
            );

        }
    }

    public function down(Schema $schema): void
    {
    }
}
