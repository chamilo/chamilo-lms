<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261026193000 extends AbstractMigrationChamilo
{
    private const array VARIABLES = [
        'meta_twitter_creator',
        'meta_twitter_site',
        'meta_image_path',
    ];

    public function getDescription(): string
    {
        return 'Update X social metadata setting labels and help text without changing saved values.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            return;
        }

        // Apply the same labels on existing portals that fresh installs receive
        // from fixtures. Keep the setting keys and all saved values unchanged.
        foreach (SettingsCurrentFixtures::getExistingSettings()['tracking'] as $setting) {
            if (!\in_array($setting['name'], self::VARIABLES, true)) {
                continue;
            }

            $this->addSql(
                'UPDATE settings SET title = ?, comment = ? WHERE category = ? AND variable = ?',
                [$setting['title'], $setting['comment'], 'tracking', $setting['name']],
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Do not revert administrator-facing metadata to outdated branding.
    }
}
