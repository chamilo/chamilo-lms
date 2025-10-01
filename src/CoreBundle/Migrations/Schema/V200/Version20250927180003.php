<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\PortfolioRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Doctrine\DBAL\Schema\Schema;

class Version20250927180003 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate portfolio comments to resource nodes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE portfolio DROP FOREIGN KEY FK_A9ED106291D79BD3");
        $this->addSql("ALTER TABLE portfolio DROP FOREIGN KEY FK_A9ED1062A76ED395");
        $this->addSql("ALTER TABLE portfolio DROP FOREIGN KEY FK_A9ED1062613FECDF");
        $this->addSql("DROP INDEX course ON portfolio");
        $this->addSql("DROP INDEX session ON portfolio");
        $this->addSql("DROP INDEX user ON portfolio");
        $this->addSql("ALTER TABLE portfolio DROP user_id, DROP c_id, DROP session_id, DROP creation_date, DROP update_date");

        $this->addSql("ALTER TABLE portfolio_comment DROP FOREIGN KEY FK_C2C17DA2A977936C");
        $this->addSql("ALTER TABLE portfolio_comment DROP FOREIGN KEY FK_C2C17DA2F675F31B");
        $this->addSql("ALTER TABLE portfolio_comment DROP FOREIGN KEY FK_C2C17DA2727ACA70");
        $this->addSql("DROP INDEX IDX_C2C17DA2A977936C ON portfolio_comment");
        $this->addSql("DROP INDEX IDX_C2C17DA2727ACA70 ON portfolio_comment");
        $this->addSql("DROP INDEX IDX_C2C17DA2F675F31B ON portfolio_comment");
        $this->addSql("ALTER TABLE portfolio_comment DROP author_id, DROP tree_root, DROP parent_id, DROP lft, DROP lvl, DROP rgt");
    }
}