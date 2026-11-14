<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelPlugin;
use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;

use const DATE_ATOM;

/**
 * Read-only diagnostic export for a single resolved access URL.
 */
final readonly class ConfigurationDumpExporter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PluginHelper $pluginHelper,
        private ConfigurationDumpSanitizer $sanitizer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function export(AccessUrl $url): array
    {
        $settings = $this->entityManager->getRepository(SettingsCurrent::class)
            ->findBy(['url' => $url], ['category' => 'ASC', 'variable' => 'ASC'])
        ;
        $settingsDump = [];

        foreach ($settings as $setting) {
            if (!$setting instanceof SettingsCurrent) {
                continue;
            }

            $filtered = $this->sanitizer->sanitize(
                $setting->getVariable(),
                $setting->getSelectedValue(),
                $setting->isConfidential(),
            );
            $settingsDump[] = [
                'category' => $setting->getCategory(),
                'variable' => $setting->getVariable(),
                'subkey' => $this->sanitizer->sanitize('subkey', $setting->getSubkey())['value'],
                'value' => $filtered['value'],
                'redacted' => $filtered['redacted'],
            ];
        }

        $pluginRelations = $this->entityManager->getRepository(AccessUrlRelPlugin::class)
            ->findBy(['url' => $url])
        ;
        $pluginDump = [];

        foreach ($pluginRelations as $relation) {
            if (!$relation instanceof AccessUrlRelPlugin) {
                continue;
            }

            $plugin = $relation->getPlugin();
            if (null === $plugin) {
                continue;
            }

            $title = $plugin->getTitle();
            $pluginObject = $this->pluginHelper->loadLegacyPlugin($title);
            $policy = $pluginObject && method_exists($pluginObject, 'getConfigurationExportPolicy')
                ? $pluginObject->getConfigurationExportPolicy()
                : [];

            $configuration = $relation->getConfiguration() ?? [];
            $values = [];
            foreach ($configuration as $key => $value) {
                if (!\is_string($key) || !preg_match('/^[a-z\d_\-\.]{1,128}$/i', $key)) {
                    // Arbitrary keys can themselves contain private identifiers.
                    continue;
                }

                // Plugin fields are confidential by default unless declared public.
                $values[$key] = $this->sanitizer->sanitize($key, $value, $policy[$key] ?? true);
            }
            ksort($values);

            $pluginDump[] = [
                'name' => $this->sanitizer->sanitize('plugin_name', $title)['value'],
                'installed' => $plugin->isInstalled(),
                'active' => $relation->isActive(),
                'configuration' => (object) $values,
            ];
        }

        usort($pluginDump, static fn (array $a, array $b): int => strcmp((string) $a['name'], (string) $b['name']));

        return [
            'format' => 'chamilo-configuration-dump',
            'version' => 1,
            'exported_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM),
            'scope' => 'current_access_url_only',
            'access_url_id' => $url->getId(),
            'settings' => $settingsDump,
            'plugins' => $pluginDump,
        ];
    }
}
