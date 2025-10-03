<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20251001120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add missing foreign keys for LTI tables';
    }

    public function up(Schema $schema): void
    {
        $tblExternalTool = $schema->getTable('lti_external_tool');

        if (!$tblExternalTool->hasForeignKey('FK_DB0E04E41BAD783F')) {
            $this->addSql('ALTER TABLE lti_external_tool ADD CONSTRAINT FK_DB0E04E41BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE');
        }

        if (!$tblExternalTool->hasForeignKey('FK_DB0E04E491D79BD3')) {
            $this->addSql('ALTER TABLE lti_external_tool ADD CONSTRAINT FK_DB0E04E491D79BD3 FOREIGN KEY (c_id) REFERENCES course (id)');
        }

        if (!$tblExternalTool->hasForeignKey('FK_DB0E04E482F80D8B')) {
            $this->addSql('ALTER TABLE lti_external_tool ADD CONSTRAINT FK_DB0E04E482F80D8B FOREIGN KEY (gradebook_eval_id) REFERENCES gradebook_evaluation (id) ON DELETE SET NULL');
        }

        if (!$tblExternalTool->hasForeignKey('FK_DB0E04E4727ACA70')) {
            $this->addSql('ALTER TABLE lti_external_tool ADD CONSTRAINT FK_DB0E04E4727ACA70 FOREIGN KEY (parent_id) REFERENCES lti_external_tool (id)');
        }
    }
}
