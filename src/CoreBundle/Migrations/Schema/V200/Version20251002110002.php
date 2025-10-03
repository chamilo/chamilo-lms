<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20251002110002 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove unused foreign keys and columns from lti_external_tool table';
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $externalTool = $schema->getTable('lti_external_tool');

        if ($externalTool->hasForeignKey('FK_DB0E04E491D79BD3')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP FOREIGN KEY FK_DB0E04E491D79BD3");
        }

        if ($externalTool->hasForeignKey('FK_DB0E04E4727ACA70')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP FOREIGN KEY FK_DB0E04E4727ACA70");
        }

        if ($externalTool->hasIndex('IDX_DB0E04E4727ACA70')) {
            $this->addSql("DROP INDEX IDX_DB0E04E4727ACA70 ON lti_external_tool");
        }

        if ($externalTool->hasIndex('IDX_DB0E04E491D79BD3')) {
            $this->addSql("DROP INDEX IDX_DB0E04E491D79BD3 ON lti_external_tool");
        }

        if ($externalTool->hasColumn('c_id')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN c_id");
        }

        if ($externalTool->hasColumn('parent_id')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN parent_id");
        }
    }
}