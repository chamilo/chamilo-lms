<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260928234500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add the database-backed PHP session table.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('php_session')) {
            return;
        }

        $this->addSql(
            'CREATE TABLE php_session (
                sess_id VARBINARY(128) NOT NULL,
                sess_data LONGBLOB NOT NULL,
                sess_lifetime INT UNSIGNED NOT NULL,
                sess_time INT UNSIGNED NOT NULL,
                PRIMARY KEY(sess_id),
                INDEX sess_lifetime_idx (sess_lifetime)
            ) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ROW_FORMAT = DYNAMIC'
        );
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('php_session')) {
            $this->addSql('DROP TABLE php_session');
        }
    }
}
