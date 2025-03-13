<?php

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
        $this->addSql("
            CREATE TABLE plugin (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) UNIQUE NOT NULL,
                installed TINYINT(1) NOT NULL DEFAULT 0,
                active TINYINT(1) NOT NULL DEFAULT 0,
                version VARCHAR(20) NOT NULL DEFAULT '1.0.0',
                access_url_id INT NOT NULL,
                configuration JSON DEFAULT NULL,
                source ENUM('official', 'third_party') NOT NULL DEFAULT 'third_party'
            );
        ");
    }

    public function down(Schema $schema): void
    {
        // Drop the plugin table if rolling back the migration
        $this->addSql("DROP TABLE plugin;");
    }
}
