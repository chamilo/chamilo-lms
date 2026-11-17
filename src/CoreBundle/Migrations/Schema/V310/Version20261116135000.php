<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

/**
 * Register the optional teacher JavaScript policy on existing installations.
 * Preserve the configured value and do not re-sync unrelated settings.
 */
final class Version20261116135000 extends AbstractMigrationChamilo
{
    private const string VARIABLE = 'teacher_use_js';

    public function getDescription(): string
    {
        return 'Register the global teacher JavaScript setting, preserving legacy default behavior.';
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
            throw new RuntimeException('Teacher JavaScript setting metadata is missing.');
        }

        $existing = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM settings WHERE variable = ? AND access_url = 1 AND subkey IS NULL',
            [self::VARIABLE],
        );
        if ($existing > 1) {
            throw new RuntimeException('Duplicate global teacher JavaScript settings found.');
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
            [self::VARIABLE, 'security', (string) $metadata['title'], (string) $metadata['comment'], 'true'],
        );
    }

    public function down(Schema $schema): void
    {
        // Preserve administrator security policy on rollback.
    }
}
