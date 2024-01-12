<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20170625154000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_course_description, c_notebook tables';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('c_course_description');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_course_description ADD resource_node_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE c_course_description ADD CONSTRAINT FK_EC3CD8091BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_EC3CD8091BAD783F ON c_course_description (resource_node_id)');
        }

        if ($table->hasIndex('session_id')) {
            $this->addSql('DROP INDEX session_id ON c_course_description');
        }

        $table = $schema->getTable('c_notebook');

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_notebook');
        }

        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_notebook ADD resource_node_id INT DEFAULT NULL, DROP notebook_id');
            $this->addSql('ALTER TABLE c_notebook ADD CONSTRAINT FK_E7EE1CE01BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_E7EE1CE01BAD783F ON c_notebook (resource_node_id)');
        }

        $this->addSql('ALTER TABLE c_notebook CHANGE user_id user_id INT DEFAULT NULL');
        if (!$table->hasForeignKey('FK_E7EE1CE0A76ED395')) {
            $this->addSql('ALTER TABLE c_notebook ADD CONSTRAINT FK_E7EE1CE0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        }
        if (!$table->hasIndex('IDX_E7EE1CE0A76ED395')) {
            $this->addSql('CREATE INDEX IDX_E7EE1CE0A76ED395 ON c_notebook (user_id)');
        }
    }
}
