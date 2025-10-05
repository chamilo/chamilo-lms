<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20251002110001 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return "Check and add columns on LTI tables";
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $lineItem = $schema->getTable('lti_lineitem');

        if ($lineItem->hasForeignKey('FK_5C76B75D8F7B22CC')) {
            $this->addSql("ALTER TABLE lti_lineitem DROP FOREIGN KEY FK_5C76B75D8F7B22CC");
        }

        if ($lineItem->hasIndex('IDX_5C76B75D8F7B22CC')) {
            $this->addSql("DROP INDEX IDX_5C76B75D8F7B22CC ON lti_lineitem");
        }

        if ($lineItem->hasColumn('tool_id')) {
            $this->addSql('ALTER TABLE lti_lineitem DROP COLUMN tool_id');
        }

        $this->addSql("ALTER TABLE lti_lineitem ADD tool_id INT NOT NULL");
        $this->addSql("ALTER TABLE lti_lineitem ADD CONSTRAINT FK_5C76B75D8F7B22CC FOREIGN KEY (tool_id) REFERENCES lti_external_tool (id) ON DELETE CASCADE");
        $this->addSql("CREATE INDEX IDX_5C76B75D8F7B22CC ON lti_lineitem (tool_id)");

        if ($lineItem->hasForeignKey('FK_5C76B75D1323A575')) {
            $this->addSql("ALTER TABLE lti_lineitem DROP FOREIGN KEY FK_5C76B75D1323A575");
        }

        if ($lineItem->hasIndex('UNIQ_5C76B75D1323A575')) {
            $this->addSql("DROP INDEX UNIQ_5C76B75D1323A575 ON lti_lineitem");
        }

        if ($lineItem->hasColumn('evaluation')) {
            $this->addSql("ALTER TABLE lti_lineitem DROP COLUMN evaluation");
        }

        $this->addSql("ALTER TABLE lti_lineitem ADD evaluation INT NOT NULL");
        $this->addSql("ALTER TABLE lti_lineitem ADD CONSTRAINT FK_5C76B75D1323A575 FOREIGN KEY (evaluation) REFERENCES gradebook_evaluation (id) ON DELETE CASCADE");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_5C76B75D1323A575 ON lti_lineitem (evaluation)");

        if ($lineItem->hasColumn('resource_id')) {
            $this->addSql("ALTER TABLE lti_lineitem DROP COLUMN resource_id");
        }

        $this->addSql("ALTER TABLE lti_lineitem ADD resource_id VARCHAR(255) DEFAULT NULL");

        if ($lineItem->hasColumn('tag')) {
            $this->addSql("ALTER TABLE lti_lineitem DROP COLUMN tag");
        }

        $this->addSql("ALTER TABLE lti_lineitem ADD tag VARCHAR(255) DEFAULT NULL");

        if ($lineItem->hasColumn('start_date')) {
            $this->addSql("ALTER TABLE lti_lineitem DROP COLUMN start_date");
        }

        $this->addSql("ALTER TABLE lti_lineitem ADD start_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)'");

        if ($lineItem->hasColumn('end_date')) {
            $this->addSql("ALTER TABLE lti_lineitem DROP COLUMN end_date");
        }

        $this->addSql("ALTER TABLE lti_lineitem ADD end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)'");

        $externalTool = $schema->getTable('lti_external_tool');

        if ($externalTool->hasForeignKey('FK_DB0E04E41BAD783F')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP FOREIGN KEY FK_DB0E04E41BAD783F");
        }

        if ($externalTool->hasIndex('UNIQ_DB0E04E41BAD783F')) {
            $this->addSql("DROP INDEX UNIQ_DB0E04E41BAD783F ON lti_external_tool");
        }

        if ($externalTool->hasColumn('resource_node_id')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN resource_node_id");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD resource_node_id INT DEFAULT NULL");
        $this->addSql('ALTER TABLE lti_external_tool ADD CONSTRAINT FK_DB0E04E41BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE');
        $this->addSql("CREATE UNIQUE INDEX UNIQ_DB0E04E41BAD783F ON lti_external_tool (resource_node_id)");

        if ($externalTool->hasForeignKey('FK_DB0E04E482F80D8B')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP FOREIGN KEY FK_DB0E04E482F80D8B");
        }

        if ($externalTool->hasIndex('IDX_DB0E04E482F80D8B')) {
            $this->addSql("DROP INDEX IDX_DB0E04E482F80D8B ON lti_external_tool");
        }

        if ($externalTool->hasColumn('gradebook_eval_id')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN gradebook_eval_id");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD gradebook_eval_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE lti_external_tool ADD CONSTRAINT FK_DB0E04E482F80D8B FOREIGN KEY (gradebook_eval_id) REFERENCES gradebook_evaluation (id) ON DELETE SET NULL");
        $this->addSql("CREATE INDEX IDX_DB0E04E482F80D8B ON lti_external_tool (gradebook_eval_id)");

        if ($externalTool->hasColumn('title')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN title");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD title VARCHAR(255) NOT NULL");

        if ($externalTool->hasColumn('description')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN description");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD description LONGTEXT DEFAULT NULL");

        if ($externalTool->hasColumn('public_key')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN public_key");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD public_key LONGTEXT DEFAULT NULL");

        if ($externalTool->hasColumn('launch_url')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN launch_url");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD launch_url VARCHAR(255) NOT NULL");

        if ($externalTool->hasColumn('consumer_key')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN consumer_key");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD consumer_key VARCHAR(255) DEFAULT NULL");

        if ($externalTool->hasColumn('shared_secret')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN shared_secret");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD shared_secret VARCHAR(255) DEFAULT NULL");

        if ($externalTool->hasColumn('custom_params')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN custom_params");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD custom_params LONGTEXT DEFAULT NULL");

        if ($externalTool->hasColumn('active_deep_linking')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN active_deep_linking");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD active_deep_linking TINYINT(1) DEFAULT 0 NOT NULL");

        if ($externalTool->hasColumn('privacy')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN privacy");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD privacy LONGTEXT DEFAULT NULL");

        if ($externalTool->hasColumn('client_id')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN client_id");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD client_id VARCHAR(255) DEFAULT NULL");

        if ($externalTool->hasColumn('login_url')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN login_url");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD login_url VARCHAR(255) DEFAULT NULL");

        if ($externalTool->hasColumn('redirect_url')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN redirect_url");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD redirect_url VARCHAR(255) DEFAULT NULL");

        if ($externalTool->hasColumn('jwks_url')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN jwks_url");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD jwks_url VARCHAR(255) DEFAULT NULL");

        if ($externalTool->hasColumn('advantage_services')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN advantage_services");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD advantage_services JSON DEFAULT NULL COMMENT '(DC2Type:json)'");

        if ($externalTool->hasColumn('version')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN version");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD version VARCHAR(255) DEFAULT 'lti1p3' NOT NULL");

        if ($externalTool->hasColumn('launch_presentation')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN launch_presentation");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD launch_presentation JSON NOT NULL COMMENT '(DC2Type:json)'");

        if ($externalTool->hasColumn('replacement_params')) {
            $this->addSql("ALTER TABLE lti_external_tool DROP COLUMN replacement_params");
        }

        $this->addSql("ALTER TABLE lti_external_tool ADD replacement_params JSON NOT NULL COMMENT '(DC2Type:json)'");

        $token = $schema->getTable('lti_token');

        if ($token->hasForeignKey('FK_EA71C468F7B22CC')) {
            $this->addSql("ALTER TABLE lti_token DROP FOREIGN KEY FK_EA71C468F7B22CC");
        }

        if ($token->hasIndex('IDX_EA71C468F7B22CC')) {
            $this->addSql("DROP INDEX IDX_EA71C468F7B22CC ON lti_token");
        }

        if ($token->hasColumn('tool_id')) {
            $this->addSql("ALTER TABLE lti_token DROP COLUMN tool_id");
        }

        $this->addSql("ALTER TABLE lti_token ADD tool_id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE lti_token ADD CONSTRAINT FK_EA71C468F7B22CC FOREIGN KEY (tool_id) REFERENCES lti_external_tool (id) ON DELETE CASCADE");
        $this->addSql("CREATE INDEX IDX_EA71C468F7B22CC ON lti_token (tool_id)");

        if ($token->hasColumn('scope')) {
            $this->addSql("ALTER TABLE lti_token DROP COLUMN scope");
        }

        $this->addSql("ALTER TABLE lti_token ADD scope JSON NOT NULL COMMENT '(DC2Type:json)'");

        if ($token->hasColumn('hash')) {
            $this->addSql("ALTER TABLE lti_token DROP COLUMN hash");
        }

        $this->addSql("ALTER TABLE lti_token ADD hash VARCHAR(255) NOT NULL");

        if ($token->hasColumn('created_at')) {
            $this->addSql("ALTER TABLE lti_token DROP COLUMN created_at");
        }

        $this->addSql("ALTER TABLE lti_token ADD created_at INT NOT NULL");

        if ($token->hasColumn('expires_at')) {
            $this->addSql("ALTER TABLE lti_token DROP COLUMN expires_at");
        }

        $this->addSql("ALTER TABLE lti_token ADD expires_at INT NOT NULL");

        $platform = $schema->getTable('lti_platform');

        if ($platform->hasColumn('public_key')) {
            $this->addSql("ALTER TABLE lti_platform DROP COLUMN public_key");
        }

        $this->addSql("ALTER TABLE lti_platform ADD public_key LONGTEXT NOT NULL");

        if ($platform->hasColumn('kid')) {
            $this->addSql("ALTER TABLE lti_platform DROP COLUMN kid");
        }

        $this->addSql("ALTER TABLE lti_platform ADD kid VARCHAR(255) NOT NULL");

        if ($platform->hasColumn('private_key')) {
            $this->addSql("ALTER TABLE lti_platform DROP COLUMN private_key");
        }

        $this->addSql("ALTER TABLE lti_platform ADD private_key LONGTEXT NOT NULL");
    }
}
