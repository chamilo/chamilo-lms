<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20240414120300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Update configuration title and comment values in settings';
    }

    public function up(Schema $schema): void
    {
        $existingSettings = SettingsCurrentFixtures::getExistingSettings();
        $newConfigurationSettings = SettingsCurrentFixtures::getNewConfigurationSettings();

        $flattenedExistingSettings = $this->flattenConfigurationSettings($existingSettings);
        $flattenedNewSettings = $this->flattenConfigurationSettings($newConfigurationSettings);

        $settingsToUpdate = array_merge($flattenedExistingSettings, $flattenedNewSettings);

        foreach ($settingsToUpdate as $settingData) {
            $variableExists = $this->connection->fetchOne(
                'SELECT COUNT(*) FROM settings WHERE variable = ?',
                [$settingData['name']]
            );

            if ($variableExists) {
                $this->addSql(
                    'UPDATE settings SET title = :title, comment = :comment WHERE variable = :name',
                    [
                        'title' => $settingData['title'],
                        'comment' => $settingData['comment'],
                        'name' => $settingData['name'],
                    ]
                );
            }
        }
    }

    public function down(Schema $schema): void {}

    private function flattenConfigurationSettings(array $categorizedSettings): array
    {
        $flattenedSettings = [];
        foreach ($categorizedSettings as $category => $settings) {
            foreach ($settings as $setting) {
                $flattenedSettings[] = $setting;
            }
        }

        return $flattenedSettings;
    }
}
