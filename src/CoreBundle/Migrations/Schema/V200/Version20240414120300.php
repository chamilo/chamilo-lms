<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Doctrine\DBAL\Schema\Schema;

class Version20240414120300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Update configuration title and comment values in settings_current';
    }

    public function up(Schema $schema): void
    {
        $settingsToUpdate = array_merge(
            SettingsCurrentFixtures::getExistingSettings(),
            SettingsCurrentFixtures::getNewConfigurationSettings()
        );

        foreach ($settingsToUpdate as $settingData) {
            $variableExists = $this->connection->fetchOne(
                'SELECT COUNT(*) FROM settings_current WHERE variable = ?',
                [$settingData['name']]
            );

            if ($variableExists) {
                $this->addSql(
                    'UPDATE settings_current SET title = :title, comment = :comment WHERE variable = :name',
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
}
