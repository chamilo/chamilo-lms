<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use AppPlugin;
use Chamilo\CoreBundle\Entity\Plugin;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;

final class Version20250306101000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate data from the settings table to the new plugin table.';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->getPluginTitles() as $pluginTitle) {
            if (\is_array($pluginTitle) && isset($pluginTitle['title'])) {
                $pluginTitle = (string) $pluginTitle['title'];
            }

            $pluginId = $this->insertPlugin($pluginTitle);

            $settingsByUrl = $this->getPluginSettingsByUrl($pluginTitle);

            $this->insertPluginSettingsByUrl($pluginId, $settingsByUrl);
        }
    }

    public function down(Schema $schema): void {}

    /**
     * @throws Exception
     */
    private function getPluginTitles(): array
    {
        return $this->connection
            ->executeQuery(
                "SELECT title FROM settings
                    WHERE category = 'plugins' AND type = 'setting' AND subkey IS NOT NULL AND variable <> 'status'
                    GROUP BY subkey"
            )
            ->fetchAllAssociative()
        ;
    }

    /**
     * @throws Exception
     */
    private function insertPlugin(string $pluginTitle): int
    {
        $pluginSource = \in_array($pluginTitle, AppPlugin::getOfficialPlugins())
            ? Plugin::SOURCE_OFFICIAL
            : Plugin::SOURCE_THIRD_PARTY;

        $this->connection->insert(
            'plugin',
            [
                'title' => $pluginTitle,
                'installed' => 1,
                'installed_version' => '1.0.0',
                'source' => $pluginSource,
            ]
        );

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @throws Exception
     */
    private function getPluginSettingsByUrl(string $pluginTitle): array
    {
        $settingsByUrl = [];

        $pluginSettings = $this->connection
            ->executeQuery(
                "SELECT variable, selected_value, access_url, title
                        FROM settings
                        WHERE category = 'plugins'
                            AND type = 'setting'
                            AND subkey IS NOT NULL
                            AND variable <> 'status'
                            AND subkey = '$pluginTitle'"
            )
            ->fetchAllAssociative()
        ;

        foreach ($pluginSettings as $pluginSetting) {
            if (!isset($settingsByUrl[$pluginSetting['access_url']])) {
                $settingsByUrl[$pluginSetting['access_url']] = [];
            }

            $variable = str_replace($pluginSetting['title'].'_', '', $pluginSetting['variable']);

            $settingsByUrl[$pluginSetting['access_url']][$variable] = $pluginSetting['selected_value'];
        }

        return $settingsByUrl;
    }

    /**
     * @param array<int, array<string, mixed>> $settingsByUrl
     *
     * @throws Exception
     */
    private function insertPluginSettingsByUrl(int $pluginId, array $settingsByUrl): void
    {
        foreach ($settingsByUrl as $accessUrlId => $pluginSettings) {
            $this->connection->insert(
                'access_url_rel_plugin',
                [
                    'plugin_id' => $pluginId,
                    'url_id' => $accessUrlId,
                    'active' => 1,
                    'configuration' => json_encode($pluginSettings),
                ]
            );
        }
    }
}
