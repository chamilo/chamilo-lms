<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\Kernel;
use Doctrine\DBAL\Schema\Schema;

class Version20190110182615 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_lp';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        /** @var Kernel $kernel */
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();
        $doctrine = $container->get('doctrine');

        $this->addSql('ALTER TABLE c_lp CHANGE author author LONGTEXT NOT NULL');

        $table = $schema->getTable('c_lp');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_lp ADD resource_node_id INT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE c_lp ADD CONSTRAINT FK_F67ABBEB1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_F67ABBEB1BAD783F ON c_lp (resource_node_id);');
        }

        if (false === $table->hasColumn('asset_id')) {
            $this->addSql('ALTER TABLE c_lp ADD asset_id INT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE c_lp ADD CONSTRAINT FK_F67ABBEB5DA1941 FOREIGN KEY (asset_id) REFERENCES asset (id);'
            );
            $this->addSql('CREATE INDEX IDX_F67ABBEB5DA1941 ON c_lp (asset_id);');
        }

        if ($table->hasIndex('session')) {
            $this->addSql('DROP INDEX session ON c_lp');
        }

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_lp');
        }

        if (false === $table->hasColumn('accumulate_work_time')) {
            $this->addSql('ALTER TABLE c_lp ADD accumulate_work_time INT DEFAULT 0 NOT NULL');
        }

        $this->addSql('ALTER TABLE c_lp CHANGE category_id category_id INT DEFAULT NULL');

        if (false === $table->hasColumn('id')) {
            $this->addSql('ALTER TABLE c_lp DROP id');
        }

        if (false === $table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_lp');
        }
        if (false === $table->hasIndex('session')) {
            $this->addSql('DROP INDEX session ON c_lp');
        }

        $this->addSql('UPDATE c_lp SET category_id = NULL WHERE category_id = 0');
        if (false === $table->hasForeignKey('FK_F67ABBEB12469DE2')) {
            $this->addSql(
                'ALTER TABLE c_lp ADD CONSTRAINT FK_F67ABBEB12469DE2 FOREIGN KEY (category_id) REFERENCES c_lp_category (iid)'
            );
        }

        $table = $schema->getTable('c_lp_category');
        if (false === $table->hasColumn('session_id')) {
            $this->addSql('ALTER TABLE c_lp_category ADD session_id INT DEFAULT NULL');
        }

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_lp_category ADD resource_node_id INT DEFAULT NULL');
        }

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_lp_category');
        }

        if ($table->hasForeignKey('FK_90A0FC07613FECDF')) {
            $this->addSql(
                'ALTER TABLE c_lp_category DROP FOREIGN KEY FK_90A0FC07613FECDF;'
            );
        }
        if (false === $table->hasForeignKey('FK_90A0FC071BAD783F')) {
            $this->addSql(
                'ALTER TABLE c_lp_category ADD CONSTRAINT FK_90A0FC071BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
        }

        if ($table->hasIndex('IDX_90A0FC07613FECDF')) {
            $this->addSql('DROP INDEX IDX_90A0FC07613FECDF ON c_lp_category;');
        }
        if (false === $table->hasIndex('UNIQ_90A0FC071BAD783F')) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_90A0FC071BAD783F ON c_lp_category (resource_node_id)');
        }

        if (false === $schema->getTable('c_lp')->hasIndex('IDX_F67ABBEB12469DE2')) {
            $this->addSql('CREATE INDEX IDX_F67ABBEB12469DE2 ON c_lp (category_id)');
        }

        $table = $schema->getTable('c_lp_item');
        if ($table->hasColumn('id')) {
            $this->addSql('ALTER TABLE c_lp_item DROP id');
        }
        $this->addSql('ALTER TABLE c_lp_item CHANGE lp_id lp_id INT DEFAULT NULL');

        if (false === $table->hasForeignKey('FK_CCC9C1ED68DFD1EF')) {
            $this->addSql(
                'ALTER TABLE c_lp_item ADD CONSTRAINT FK_CCC9C1ED68DFD1EF FOREIGN KEY (lp_id) REFERENCES c_lp (iid)'
            );
        }
        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_lp_item;');
        }

        if ($table->hasIndex('idx_c_lp_item_cid_lp_id')) {
            $this->addSql('DROP INDEX idx_c_lp_item_cid_lp_id ON c_lp_item;');
        }

        $table = $schema->getTable('c_lp_view');
        if ($table->hasColumn('id')) {
            $this->addSql('ALTER TABLE c_lp_view DROP id');
        }

        $table = $schema->getTable('c_lp_item_view');
        if ($table->hasColumn('id')) {
            $this->addSql('ALTER TABLE c_lp_item_view DROP id');
        }

        if (false === $table->hasIndex('idx_c_lp_item_view_cid_id_view_count')) {
            $this->addSql('CREATE INDEX idx_c_lp_item_view_cid_id_view_count ON c_lp_item_view (c_id, iid, view_count)');
        }

        $table = $schema->getTable('c_lp_iv_interaction');
        if ($table->hasColumn('id')) {
            $this->addSql('ALTER TABLE c_lp_iv_interaction DROP id');
        }

        $table = $schema->getTable('c_lp_iv_objective');
        if ($table->hasColumn('id')) {
            $this->addSql('ALTER TABLE c_lp_iv_objective DROP id');
        }

        if (false === $schema->hasTable('c_lp_rel_usergroup')) {
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
}
