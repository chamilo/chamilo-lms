<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

/**
 * Registers registration.firstname_required (#1783) for existing installations.
 * Existing values are preserved; the new setting defaults to enabled.
 */
final class Version20261114100000 extends AbstractMigrationChamilo
{
    private const string VARIABLE = 'firstname_required';

    public function getDescription(): string
    {
        return 'Add the registration.firstname_required setting (enabled by default).';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            throw new RuntimeException('The settings table does not exist.');
        }

        $existing = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM settings WHERE variable = ? AND access_url = 1 AND subkey IS NULL',
            [self::VARIABLE],
        );
        if ($existing > 1) {
            throw new RuntimeException('Duplicate main URL first-name settings found.');
        }
        if (1 === $existing) {
            return;
        }

        $this->addSql(
            'INSERT INTO settings (variable, category, title, comment, selected_value, access_url, access_url_changeable, access_url_locked)'
            .' VALUES (?, ?, ?, ?, ?, 1, 1, 0)',
            [
                self::VARIABLE,
                'registration',
                'First name required',
                'When disabled, users can register, edit their profile and be created by administrators without a first name, for people who only have one name. The last name stays required.',
                'true',
            ],
        );
    }

    public function down(Schema $schema): void
    {
        // Preserve administrator settings on rollback.
    }
}
