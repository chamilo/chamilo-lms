<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20250926174000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Update settings.category using categories from SettingsCurrentFixtures';
    }

    public function up(Schema $schema): void
    {
        foreach (SettingsCurrentFixtures::getExistingSettings() as $category => $settings) {
            $category = strtolower((string) $category);
            foreach ($settings as $setting) {
                $variable = $setting['name'];
                $this->addSql("UPDATE settings SET category = '{$category}' WHERE variable = '{$variable}'");
            }
        }

        foreach (SettingsCurrentFixtures::getNewConfigurationSettings() as $category => $settings) {
            $category = strtolower((string) $category);
            foreach ($settings as $setting) {
                $variable = $setting['name'];
                $this->addSql("UPDATE settings SET category = '{$category}' WHERE variable = '{$variable}'");
            }
        }
    }

    public function down(Schema $schema): void
    {
        // no-op
    }
}
