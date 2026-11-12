<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

/**
 * Registers the configurable cap without changing previously chosen values.
 * Only this setting is affected; existing URL-specific overrides are preserved.
 */
final class Version20261111194000 extends AbstractMigrationChamilo
{
    private const string VARIABLE = 'max_time_spent_per_session_threshold';

    public function getDescription(): string
    {
        return 'Register the maximum counted time per connection in tracking settings.';
    }

    public function up(Schema $schema): void
    {
        $metadata = null;
        foreach (SettingsCurrentFixtures::getNewConfigurationSettings()['tracking'] ?? [] as $setting) {
            if (self::VARIABLE === ($setting['name'] ?? null)) {
                $metadata = $setting;

                break;
            }
        }

        if (null === $metadata) {
            throw new RuntimeException('Tracking time threshold metadata is missing.');
        }

        foreach (['settings', 'settings_current'] as $table) {
            if (!$schema->hasTable($table)) {
                continue;
            }

            $count = (int) $this->connection->fetchOne(
                "SELECT COUNT(*) FROM $table WHERE variable = ? AND access_url = 1 AND subkey IS NULL",
                [self::VARIABLE],
            );
            if ($count > 1) {
                throw new RuntimeException('Duplicate tracking threshold for the main access URL.');
            }

            if (1 === $count) {
                // Do not alter selected_value or any per-URL overrides.
                $this->addSql(
                    "UPDATE $table SET title = ?, comment = ?, category = 'tracking', access_url_changeable = 1, access_url_locked = 0 WHERE variable = ? AND access_url = 1 AND subkey IS NULL",
                    [(string) $metadata['title'], (string) $metadata['comment'], self::VARIABLE],
                );

                continue;
            }

            $sourceCount = (int) $this->connection->fetchOne(
                "SELECT COUNT(*) FROM $table WHERE variable = ? AND access_url = 1 AND subkey IS NULL",
                ['maximum_inactive_time_to_consider'],
            );
            if (1 !== $sourceCount) {
                throw new RuntimeException('A single existing tracking setting is required to copy the settings row format.');
            }

            $this->addSql(
                "INSERT INTO $table (
                    access_url, variable, subkey, type, category, selected_value,
                    title, comment, scope, subkeytext, access_url_changeable,
                    access_url_locked, value_template_id
                ) SELECT
                    source.access_url, ?, source.subkey, source.type, 'tracking', '360',
                    ?, ?, source.scope, source.subkeytext, 1, 0, NULL
                FROM $table source
                WHERE source.variable = 'maximum_inactive_time_to_consider'
                  AND source.access_url = 1 AND source.subkey IS NULL",
                [self::VARIABLE, (string) $metadata['title'], (string) $metadata['comment']],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Do not remove administrator-selected limits or access URL overrides.
    }
}
