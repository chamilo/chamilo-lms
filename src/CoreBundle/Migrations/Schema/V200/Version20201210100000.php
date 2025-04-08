<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20201210100000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add MFA (2FA) fields to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE user ADD mfa_enabled BOOLEAN NOT NULL DEFAULT false,
                             ADD mfa_service VARCHAR(255) DEFAULT NULL,
                             ADD mfa_secret VARCHAR(255) DEFAULT NULL,
                             ADD mfa_backup_codes TEXT DEFAULT NULL,
                             ADD mfa_last_used DATETIME DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE user DROP mfa_enabled,
                             DROP mfa_service,
                             DROP mfa_secret,
                             DROP mfa_backup_codes,
                             DROP mfa_last_used
        ');
    }
}
