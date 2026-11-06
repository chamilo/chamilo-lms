<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

/**
 * Register the optional mass-mailing DSN on existing installations.
 * Do not re-run broad settings sync migrations or overwrite mail credentials.
 */
final class Version20261105193000 extends AbstractMigrationChamilo
{
    private const string SETTING_VARIABLE = 'mailer_dsn_massmailing';

    public function getDescription(): string
    {
        return 'Register the optional mass-mailing transport setting without changing existing mail settings.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            throw new RuntimeException('The settings table does not exist.');
        }

        $setting = null;
        foreach (SettingsCurrentFixtures::getNewConfigurationSettings()['mail'] ?? [] as $candidate) {
            if (self::SETTING_VARIABLE === ($candidate['name'] ?? null)) {
                $setting = $candidate;

                break;
            }
        }

        if (null === $setting) {
            throw new RuntimeException('Mass mailing setting metadata is missing from SettingsCurrentFixtures.');
        }

        $existingCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM settings WHERE variable = ? AND access_url = 1 AND subkey IS NULL',
            [self::SETTING_VARIABLE]
        );

        if ($existingCount > 1) {
            throw new RuntimeException('Duplicate main access URL mass mailing settings.');
        }

        if (1 === $existingCount) {
            return;
        }

        $this->addSql(
            'INSERT INTO settings (variable, category, title, comment, selected_value, access_url, access_url_changeable) VALUES (?, ?, ?, ?, ?, 1, 0)',
            [
                self::SETTING_VARIABLE,
                'mail',
                (string) $setting['title'],
                (string) $setting['comment'],
                '',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        // Preserve administrator configuration if the migration is rolled back.
    }
}
