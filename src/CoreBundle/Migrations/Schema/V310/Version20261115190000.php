<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Encrypted profile values exceed VARCHAR(255) once authenticated encryption and
 * base64 encoding are applied. Expand storage without rewriting any values.
 */
final class Version20261115190000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Expand user phone and address columns for opt-in authenticated encryption.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('user')) {
            return;
        }

        $user = $schema->getTable('user');
        foreach (['phone', 'address'] as $field) {
            if ($user->hasColumn($field)) {
                // The existing columns are nullable; this preserves null and stored values.
                $this->addSql('ALTER TABLE user MODIFY '.$field.' LONGTEXT DEFAULT NULL');
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Never shrink encrypted columns; doing so could irreversibly truncate values.
    }
}
