<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

/**
 * Register only the three new GH-2282 quota settings on existing installations.
 * Do not re-run the broad settings-sync migrations or modify existing quotas.
 */
final class Version20261028170000 extends AbstractMigrationChamilo
{
    private const array SETTINGS = [
        'platform' => ['personal_files_quota_mb'],
        'dropbox' => ['dropbox_user_quota_mb', 'dropbox_course_quota_mb'],
    ];

    public function getDescription(): string
    {
        return 'Register GH-2282 personal files and Dropbox storage quotas without changing existing values.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            throw new RuntimeException('The settings table does not exist.');
        }

        $sources = [
            SettingsCurrentFixtures::getExistingSettings(),
            SettingsCurrentFixtures::getNewConfigurationSettings(),
        ];

        foreach (self::SETTINGS as $category => $variables) {
            foreach ($variables as $variable) {
                $setting = null;

                foreach ($sources as $source) {
                    foreach ($source[$category] ?? [] as $candidate) {
                        if ($variable === ($candidate['name'] ?? null)) {
                            $setting = $candidate;

                            break 2;
                        }
                    }
                }

                if (null === $setting) {
                    throw new RuntimeException('Quota setting metadata is missing: '.$category.'.'.$variable);
                }

                $existingCount = (int) $this->connection->fetchOne(
                    'SELECT COUNT(*) FROM settings WHERE variable = ? AND access_url = 1 AND subkey IS NULL',
                    [$variable],
                );

                if ($existingCount > 1) {
                    throw new RuntimeException('Duplicate main URL quota setting: '.$variable);
                }

                if (0 === $existingCount) {
                    $this->addSql(
                        'INSERT INTO settings (variable, category, title, comment, selected_value, access_url, access_url_changeable) VALUES (?, ?, ?, ?, ?, 1, 0)',
                        [$variable, $category, (string) $setting['title'], (string) $setting['comment'], '0'],
                    );
                } else {
                    // Preserve explicit administrator values. Only repair a blank default
                    // potentially inserted by an older broad settings-sync migration.
                    $this->addSql(
                        "UPDATE settings SET selected_value = ? WHERE variable = ? AND access_url = 1 AND subkey IS NULL AND (selected_value IS NULL OR selected_value = '')",
                        ['0', $variable],
                    );
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Deliberately do not delete administrator configuration on rollback.
    }
}
