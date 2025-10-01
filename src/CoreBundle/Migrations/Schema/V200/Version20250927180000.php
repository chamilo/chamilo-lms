<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20250927180000 extends AbstractMigrationChamilo
{
    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $tblPortfolioRelTag = $schema->getTable('portfolio_rel_tag');
        $tblPortfolio = $schema->getTable('portfolio');
        $tblPortfolioCategory = $schema->getTable('portfolio_category');

        if ($tblPortfolioRelTag->hasForeignKey('FK_DB73447291D79BD3')) {
            $this->addSql("ALTER TABLE portfolio_rel_tag DROP FOREIGN KEY FK_DB73447291D79BD3");
        }

        if ($tblPortfolioRelTag->hasForeignKey('FK_DB734472BAD26311')) {
            $this->addSql("ALTER TABLE portfolio_rel_tag DROP FOREIGN KEY FK_DB734472BAD26311");
        }

        if ($tblPortfolioRelTag->hasForeignKey('FK_DB734472613FECDF')) {
            $this->addSql("ALTER TABLE portfolio_rel_tag DROP FOREIGN KEY FK_DB734472613FECDF");
        }

        $this->addSql("DELETE FROM portfolio_rel_tag WHERE c_id NOT IN (SELECT id FROM course)");
        $this->addSql("DELETE FROM portfolio_rel_tag WHERE session_id NOT IN (SELECT id FROM session)");

        $this->addSql("ALTER TABLE portfolio_rel_tag ADD CONSTRAINT FK_DB73447291D79BD3 FOREIGN KEY (c_id) REFERENCES course (id)");
        $this->addSql("ALTER TABLE portfolio_rel_tag ADD CONSTRAINT FK_DB734472BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id)");
        $this->addSql("ALTER TABLE portfolio_rel_tag ADD CONSTRAINT FK_DB734472613FECDF FOREIGN KEY (session_id) REFERENCES session (id)");

        $this->addSql("ALTER TABLE portfolio_category CHANGE title title LONGTEXT NOT NULL, CHANGE parent_id parent_id INT DEFAULT NULL");
        $this->addSql("UPDATE portfolio_category SET parent_id = NULL WHERE parent_id NOT IN (SELECT id FROM portfolio_category)");

        if (!$tblPortfolioCategory->hasForeignKey('FK_7AC64359727ACA70')) {
            $this->addSql("ALTER TABLE portfolio_category ADD CONSTRAINT FK_7AC64359727ACA70 FOREIGN KEY (parent_id) REFERENCES portfolio_category (id) ON DELETE SET NULL");
        }

        if (!$tblPortfolioCategory->hasIndex('IDX_7AC64359727ACA70')) {
            $this->addSql("CREATE INDEX IDX_7AC64359727ACA70 ON portfolio_category (parent_id)");
        }

        $this->addSql("ALTER TABLE portfolio_comment ADD resource_node_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE portfolio_comment ADD CONSTRAINT FK_C2C17DA21BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_C2C17DA21BAD783F ON portfolio_comment (resource_node_id)");

        $this->addSql("ALTER TABLE portfolio ADD resource_node_id INT DEFAULT NULL, CHANGE title title LONGTEXT NOT NULL");
        $this->addSql("ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED10621BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_A9ED10621BAD783F ON portfolio (resource_node_id)");

        if ($tblPortfolio->hasForeignKey('FK_A9ED106212469DE2')) {
            $this->addSql('ALTER TABLE portfolio DROP FOREIGN KEY FK_A9ED106212469DE2');
        }

        $this->addSql('UPDATE portfolio SET category_id = NULL WHERE category_id NOT IN (SELECT id FROM portfolio_category)');
        $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED106212469DE2 FOREIGN KEY (category_id) REFERENCES portfolio_category (id) ON DELETE SET NULL');

        if (!$tblPortfolio->hasColumn('duplicated_from')) {
            $this->addSql('ALTER TABLE portfolio ADD duplicated_from INT DEFAULT NULL');
            $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED1062FC4CB679 FOREIGN KEY (duplicated_from) REFERENCES portfolio (id) ON DELETE SET NULL');
            $this->addSql("CREATE INDEX IDX_A9ED1062FC4CB679 ON portfolio (duplicated_from)");
        } else {
            $this->addSql('UPDATE portfolio SET duplicated_from = NULL WHERE duplicated_from NOT IN (SELECT id FROM portfolio)');;
        }

        if ($tblPortfolio->hasColumn('is_visible')) {
            $this->addSql('ALTER TABLE portfolio CHANGE is_visible visibility SMALLINT DEFAULT 1 NOT NULL');
        }

        if ($tblPortfolio->hasForeignKey('FK_A9ED106291D79BD3')) {
            $this->addSql("ALTER TABLE portfolio DROP FOREIGN KEY FK_A9ED106291D79BD3");
            //borrar columna c_id
        }

        $this->addSql("ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED106291D79BD3 FOREIGN KEY (c_id) REFERENCES course (id)");

        if ($tblPortfolio->hasForeignKey('FK_A9ED1062613FECDF')) {
            $this->addSql("ALTER TABLE portfolio DROP FOREIGN KEY FK_A9ED1062613FECDF");
            //borrar columna session_id
        }

        $this->addSql("ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED1062613FECDF FOREIGN KEY (session_id) REFERENCES session (id)");
    }
}
