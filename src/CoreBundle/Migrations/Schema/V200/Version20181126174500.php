<?php

declare(strict_types=1);

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
        if (!$schema->hasTable('lti_external_tool')) {
            $this->addSql('
                CREATE TABLE lti_external_tool (
                    id INT AUTO_INCREMENT NOT NULL,
                    resource_node_id INT DEFAULT NULL,
                    c_id INT DEFAULT NULL,
                    gradebook_eval_id INT DEFAULT NULL,
                    parent_id INT DEFAULT NULL,
                    title VARCHAR(255) NOT NULL,
                    description LONGTEXT DEFAULT NULL,
                    launch_url VARCHAR(255) NOT NULL,
                    consumer_key VARCHAR(255) DEFAULT NULL,
                    shared_secret VARCHAR(255) DEFAULT NULL,
                    custom_params LONGTEXT DEFAULT NULL,
                    active_deep_linking TINYINT(1) DEFAULT 0 NOT NULL,
                    privacy LONGTEXT DEFAULT NULL,
                    client_id VARCHAR(255) DEFAULT NULL,
                    login_url VARCHAR(255) DEFAULT NULL,
                    redirect_url VARCHAR(255) DEFAULT NULL,
                    advantage_services LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\',
                    version VARCHAR(255) DEFAULT \'lti1p1\' NOT NULL,
                    launch_presentation LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\',
                    replacement_params LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\',
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
            ');
        }
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP TABLE IF EXISTS lti_external_tool;');
    }
}
