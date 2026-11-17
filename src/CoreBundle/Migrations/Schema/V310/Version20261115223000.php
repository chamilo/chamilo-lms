<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

/**
 * Register only the new global encryption policy in existing installations.
 * Preserve selected values and avoid re-synchronizing unrelated settings.
 */
final class Version20261115223000 extends AbstractMigrationChamilo
{
    private const string VARIABLE = 'encrypted_user_fields';

    public function getDescription(): string
    {
        return 'Register the global allowlisted encryption-at-rest profile setting.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            throw new RuntimeException('The settings table does not exist.');
        }

        $metadata = null;
        foreach (SettingsCurrentFixtures::getNewConfigurationSettings()['security'] ?? [] as $candidate) {
            if (self::VARIABLE === ($candidate['name'] ?? null)) {
                $metadata = $candidate;

                break;
            }
        }

        if (null === $metadata) {
            throw new RuntimeException('Encryption setting metadata is missing.');
        }

        $existing = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM settings WHERE variable = ? AND access_url = 1 AND subkey IS NULL',
            [self::VARIABLE],
        );
        if ($existing > 1) {
            throw new RuntimeException('Duplicate global encryption settings found.');
        }

        if (1 === $existing) {
            $this->addSql(
                'UPDATE settings SET category = ?, title = ?, comment = ?, access_url_changeable = 0, access_url_locked = 1'
                .' WHERE variable = ? AND access_url = 1 AND subkey IS NULL',
                ['security', (string) $metadata['title'], (string) $metadata['comment'], self::VARIABLE],
            );

            return;
        }

        $this->addSql(
            'INSERT INTO settings (variable, category, title, comment, selected_value, access_url, access_url_changeable, access_url_locked)'
            .' VALUES (?, ?, ?, ?, ?, 1, 0, 1)',
            [self::VARIABLE, 'security', (string) $metadata['title'], (string) $metadata['comment'], ''],
        );
    }

    public function down(Schema $schema): void
    {
        // An encrypted database must remain readable after any code rollback.
    }
}
