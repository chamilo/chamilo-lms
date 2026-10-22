<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Prepare certificate-owner publication approval tracking for issue #2669.
 * No blockchain integration or publication is performed by this migration.
 */
final class Version20261021150000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add explicit certificate publication approval and revocation dates';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('gradebook_certificate')) {
            return;
        }

        $table = $schema->getTable('gradebook_certificate');
        if (!$table->hasColumn('user_publication_approval_date')) {
            $this->addSql('ALTER TABLE gradebook_certificate ADD user_publication_approval_date DATETIME DEFAULT NULL');
        }

        if (!$table->hasColumn('user_publication_revocation_date')) {
            $this->addSql('ALTER TABLE gradebook_certificate ADD user_publication_revocation_date DATETIME DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // Approval history must not be silently destroyed during an application rollback.
    }
}
