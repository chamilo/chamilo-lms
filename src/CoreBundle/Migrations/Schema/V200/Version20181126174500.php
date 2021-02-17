<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20181126174500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate plugin_ims_lti_tool';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('plugin_ims_lti_tool')) {
            $schema->renameTable('plugin_ims_lti_tool', 'lti_external_tool');

            return;
        }

        if (false === $schema->hasTable('lti_external_tool')) {
            $this->addSql(
                'CREATE TABLE lti_external_tool (
                id INT AUTO_INCREMENT NOT NULL,
                c_id INT DEFAULT NULL,
                gradebook_eval_id INT DEFAULT NULL,
                parent_id INT DEFAULT NULL,
                name VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                launch_url VARCHAR(255) NOT NULL,
                consumer_key VARCHAR(255) DEFAULT NULL,
                shared_secret VARCHAR(255) DEFAULT NULL,
                custom_params LONGTEXT DEFAULT NULL,
                active_deep_linking TINYINT(1) DEFAULT \'0\' NOT NULL,
                privacy LONGTEXT DEFAULT NULL,
                INDEX IDX_DB0E04E491D79BD3 (c_id),
                INDEX IDX_DB0E04E482F80D8B (gradebook_eval_id),
                INDEX IDX_DB0E04E4727ACA70 (parent_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
            $this->addSql(
                'ALTER TABLE lti_external_tool ADD CONSTRAINT FK_DB0E04E491D79BD3 FOREIGN KEY (c_id) REFERENCES course (id)'
            );
            $this->addSql(
                'ALTER TABLE lti_external_tool ADD CONSTRAINT FK_DB0E04E482F80D8B FOREIGN KEY (gradebook_eval_id) REFERENCES gradebook_evaluation (id) ON DELETE SET NULL;'
            );
            $this->addSql(
                'ALTER TABLE lti_external_tool ADD CONSTRAINT FK_DB0E04E4727ACA70 FOREIGN KEY (parent_id) REFERENCES lti_external_tool (id);'
            );
        }
    }

    public function down(Schema $schema): void
    {
    }
}
