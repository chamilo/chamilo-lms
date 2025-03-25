<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250306100000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create the plugin table to manage plugin installation and activation.';
    }

    public function up(Schema $schema): void
    {
        // Create the plugin table
        $this->addSql("CREATE TABLE plugin (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, installed TINYINT(1) NOT NULL, installed_version VARCHAR(20) NOT NULL, source VARCHAR(20) DEFAULT 'third_party' NOT NULL, UNIQUE INDEX UNIQ_E96E27942B36786B (title), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");

        $this->addSql("CREATE TABLE access_url_rel_plugin (id INT AUTO_INCREMENT NOT NULL, plugin_id INT NOT NULL, url_id INT NOT NULL, active TINYINT(1) NOT NULL, configuration LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', INDEX IDX_7167B425EC942BCF (plugin_id), INDEX IDX_7167B42581CFDAE7 (url_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");
        $this->addSql("ALTER TABLE access_url_rel_plugin ADD CONSTRAINT FK_7167B425EC942BCF FOREIGN KEY (plugin_id) REFERENCES plugin (id)");
        $this->addSql("ALTER TABLE access_url_rel_plugin ADD CONSTRAINT FK_7167B42581CFDAE7 FOREIGN KEY (url_id) REFERENCES access_url (id)");
    }

    public function down(Schema $schema): void
    {
        // Drop the plugin table if rolling back the migration
        $this->addSql("DROP TABLE access_url_rel_plugin;");
        $this->addSql("DROP TABLE plugin;");
    }
}
