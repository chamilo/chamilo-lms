<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20201210100005 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add access_url_id field to resource_file table';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('resource_file')) {
            $this->addSql(
                'ALTER TABLE resource_file ADD access_url_id INT DEFAULT NULL'
            );
            $this->addSql(
                'ALTER TABLE resource_file ADD CONSTRAINT FK_RESOURCE_FILE_ACCESS_URL FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE SET NULL'
            );
            $this->addSql(
                'CREATE INDEX IDX_RESOURCE_FILE_ACCESS_URL ON resource_file (access_url_id)'
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM settings WHERE variable = 'access_url_specific_files' AND category = 'course'"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO settings (variable, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('access_url_specific_files','course','false','Access Url Specific Files','','',NULL, 1)"
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('resource_file')) {
            $this->addSql(
                'ALTER TABLE resource_file DROP FOREIGN KEY FK_RESOURCE_FILE_ACCESS_URL'
            );
            $this->addSql(
                'DROP INDEX IDX_RESOURCE_FILE_ACCESS_URL ON resource_file'
            );
            $this->addSql(
                'ALTER TABLE resource_file DROP COLUMN access_url_id'
            );
        }
    }
}
