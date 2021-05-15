<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\ToolChain;
use Doctrine\DBAL\Schema\Schema;

class Version20170625122900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'c_document changes';
    }

    public function up(Schema $schema): void
    {
        // Install tools.
        $em = $this->getEntityManager();
        $container = $this->getContainer();
        $toolChain = $container->get(ToolChain::class);
        $toolChain->createTools($em);

        $table = $schema->getTable('c_document');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_document ADD resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_document ADD CONSTRAINT FK_C9FA0CBD1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_C9FA0CBD1BAD783F ON c_document (resource_node_id);');
        }

        if (false === $table->hasColumn('template')) {
            $this->addSql('ALTER TABLE c_document ADD template TINYINT(1) NOT NULL');
        }

        if ($table->hasColumn('id')) {
            $this->addSql('ALTER TABLE c_document DROP id');
            //$this->addSql('ALTER TABLE c_document DROP id, DROP c_id, DROP path, DROP size, DROP session_id');
        }

        $this->addSql('ALTER TABLE c_document CHANGE c_id c_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_document CHANGE path path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE c_document CHANGE size size INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_document CHANGE session_id session_id INT DEFAULT NULL');

        if (false === $table->hasIndex('idx_cdoc_type')) {
            $this->addSql('CREATE INDEX idx_cdoc_type ON c_document (filetype)');
        }

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_document');
        }

        //$this->addSql('ALTER TABLE c_document CHANGE path path VARCHAR(255) DEFAULT NULL;');
        $table = $schema->getTable('c_announcement');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_announcement ADD resource_node_id INT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE c_announcement ADD CONSTRAINT FK_39912E021BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_39912E021BAD783F ON c_announcement (resource_node_id);');
        }
        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_announcement');
        }
        if ($table->hasIndex('session_id')) {
            $this->addSql('DROP INDEX session_id ON c_announcement');
        }

        $table = $schema->getTable('c_announcement_attachment');
        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_announcement_attachment');
        }

        $this->addSql('ALTER TABLE c_announcement_attachment CHANGE announcement_id announcement_id INT DEFAULT NULL');

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_announcement_attachment ADD resource_node_id INT DEFAULT NULL');
            $this->addSql(
                'ALTER TABLE c_announcement_attachment ADD CONSTRAINT FK_5480BD4A913AEA17 FOREIGN KEY (announcement_id) REFERENCES c_announcement (iid) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE c_announcement_attachment ADD CONSTRAINT FK_5480BD4A1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
            $this->addSql('CREATE INDEX IDX_5480BD4A913AEA17 ON c_announcement_attachment (announcement_id)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_5480BD4A1BAD783F ON c_announcement_attachment (resource_node_id)');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
