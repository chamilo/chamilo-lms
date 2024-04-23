<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240413234500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create and alter portfolio tables for new features including tags and categories';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('portfolio_rel_tag')) {
            $this->addSql('CREATE TABLE portfolio_rel_tag (id INT AUTO_INCREMENT NOT NULL, tag_id INT NOT NULL, c_id INT NOT NULL, session_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
            $this->addSql('CREATE INDEX IDX_DB734472BAD26311 ON portfolio_rel_tag (tag_id)');
            $this->addSql('CREATE INDEX IDX_DB73447291D79BD3 ON portfolio_rel_tag (c_id)');
            $this->addSql('CREATE INDEX IDX_DB734472613FECDF ON portfolio_rel_tag (session_id)');
        }

        if ($schema->hasTable('portfolio_comment')) {
            $table = $schema->getTable('portfolio_comment');
            if (!$table->hasColumn('visibility')) {
                $this->addSql('ALTER TABLE portfolio_comment ADD visibility SMALLINT DEFAULT 1 NOT NULL');
            }
        }

        if ($schema->hasTable('portfolio_category')) {
            $table = $schema->getTable('portfolio_category');
            if (!$table->hasColumn('parent_id')) {
                $this->addSql('ALTER TABLE portfolio_category ADD parent_id INT DEFAULT NULL');
                $this->addSql('ALTER TABLE portfolio_category ADD CONSTRAINT FK_7AC64359727ACA70 FOREIGN KEY (parent_id) REFERENCES portfolio_category (id) ON DELETE SET NULL');
            }
            if (!$table->hasIndex('IDX_7AC64359727ACA70')) {
                $this->addSql('CREATE INDEX IDX_7AC64359727ACA70 ON portfolio_category (parent_id)');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('portfolio_rel_tag')) {
            $this->addSql('DROP TABLE portfolio_rel_tag');
        }

        if ($schema->hasTable('portfolio_comment')) {
            $table = $schema->getTable('portfolio_comment');
            if ($table->hasColumn('visibility')) {
                $this->addSql('ALTER TABLE portfolio_comment DROP COLUMN visibility');
            }
        }

        if ($schema->hasTable('portfolio_category')) {
            $table = $schema->getTable('portfolio_category');
            if ($table->hasForeignKey('FK_7AC64359727ACA70')) {
                $this->addSql('ALTER TABLE portfolio_category DROP FOREIGN KEY FK_7AC64359727ACA70');
            }
            if ($table->hasColumn('parent_id')) {
                $this->addSql('ALTER TABLE portfolio_category DROP COLUMN parent_id');
            }
            if ($table->hasIndex('IDX_7AC64359727ACA70')) {
                $this->addSql('DROP INDEX IDX_7AC64359727ACA70 ON portfolio_category');
            }
        }
    }
}
