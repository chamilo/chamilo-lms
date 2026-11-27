<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

/**
 * Register the learner reporting settings restored from 1.11.x on existing installations, all disabled.
 * Preserve any configured value and do not re-sync unrelated settings.
 */
final class Version20261127110000 extends AbstractMigrationChamilo
{
    private const string CATEGORY = 'tracking';
    private const array VARIABLES = [
        'display_session_subscription_column',
        'improve_tracking_in_mystudent_php',
        'display_theoretical_time',
    ];

    public function getDescription(): string
    {
        return 'Register the learner reporting settings restored from 1.11.x, disabled by default.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            throw new RuntimeException('The settings table does not exist.');
        }

        $metadataByName = [];
        foreach (SettingsCurrentFixtures::getNewConfigurationSettings()[self::CATEGORY] ?? [] as $candidate) {
            $metadataByName[$candidate['name'] ?? ''] = $candidate;
        }

        foreach (self::VARIABLES as $variable) {
            $metadata = $metadataByName[$variable] ?? null;
            if (null === $metadata) {
                throw new RuntimeException(\sprintf('Metadata of the setting %s is missing.', $variable));
            }

            $existing = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM settings WHERE variable = ? AND access_url = 1 AND subkey IS NULL',
                [$variable],
            );
            if ($existing > 1) {
                throw new RuntimeException(\sprintf('Duplicate global settings %s found.', $variable));
            }

            if (1 === $existing) {
                $this->addSql(
                    'UPDATE settings SET category = ?, title = ?, comment = ? WHERE variable = ? AND access_url = 1 AND subkey IS NULL',
                    [self::CATEGORY, (string) $metadata['title'], (string) $metadata['comment'], $variable],
                );

                continue;
            }

            $this->addSql(
                'INSERT INTO settings (variable, category, title, comment, selected_value, access_url, access_url_changeable, access_url_locked)'
                .' VALUES (?, ?, ?, ?, ?, 1, 0, 0)',
                [$variable, self::CATEGORY, (string) $metadata['title'], (string) $metadata['comment'], 'false'],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Keep the administrator's choice on rollback.
    }
}
