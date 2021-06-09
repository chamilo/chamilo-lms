<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20190110182620 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_blog, c_wiki';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('c_blog');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_blog ADD resource_node_id BIGINT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE c_blog ADD CONSTRAINT FK_64B00A121BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_64B00A121BAD783F ON c_blog (resource_node_id);');
        }

        $table = $schema->getTable('c_wiki');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_wiki ADD resource_node_id BIGINT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE c_wiki ADD CONSTRAINT FK_866887571BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_866887571BAD783F ON c_wiki (resource_node_id);');
        }
    }
}
