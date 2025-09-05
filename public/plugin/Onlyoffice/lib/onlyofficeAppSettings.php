<?php
/**
 * (c) Copyright Ascensio System SIA 2025.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Chamilo\CoreBundle\Entity\AccessUrlRelPlugin;
use Chamilo\CoreBundle\Entity\Plugin as PluginEntity;
use Chamilo\CoreBundle\Framework\Container;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Onlyoffice\DocsIntegrationSdk\Manager\Settings\SettingsManager;

class OnlyofficeAppsettings extends SettingsManager
{
    /**
     * Link to Docs Cloud.
     *
     * @var string
     */
    public const LINK_TO_DOCS = 'https://www.onlyoffice.com/docs-registration.aspx?referer=chamilo';
    /**
     * The settings key for the document server address.
     *
     * @var string
     */
    public $documentServerUrl = 'document_server_url';

    /**
     * The config key for the jwt header.
     *
     * @var string
     */
    public $jwtHeader = 'onlyoffice_jwt_header';

    /**
     * The config key for the internal url.
     *
     * @var string
     */
    public $documentServerInternalUrl = 'onlyoffice_internal_url';

    /**
     * The config key for the storage url.
     *
     * @var string
     */
    public $storageUrl = 'onlyoffice_storage_url';

    /**
     * The config key for the demo data flag.
     *
     * @var string
     */
    public $useDemoName = 'onlyoffice_connect_demo_data';

    /**
     * Chamilo plugin instance (legacy plugin API).
     *
     * @var Plugin
     */
    public $plugin;

    /**
     * Runtime settings coming from the form submit (take precedence).
     *
     * @var array|null
     */
    public $newSettings;

    /**
     * The config key for JWT secret key.
     *
     * @var string
     */
    protected $jwtKey = 'jwt_secret';

    /**
     * Constructor.
     *
     * @param Plugin     $plugin
     * @param array|null $newSettings
     */
    public function __construct(Plugin $plugin, ?array $newSettings = null)
    {
        parent::__construct();
        $this->plugin = $plugin;
        $this->newSettings = $newSettings;
    }

    /**
     * Return a configuration value by name, checking (in order):
     * 1) Runtime overrides ($this->newSettings)
     * 2) Persisted configuration in access_url_rel_plugin.configuration
     * 3) Special cases / defaults
     * 4) Legacy plugin storage ($plugin->get())
     * 5) Global configuration (api_get_configuration_value)
     *
     * All access to DB-backed configuration is null-safe per access URL.
     *
     * @param string $settingName
     *
     * @return mixed|null
     */
    public function getSetting($settingName)
    {
        try {
            $em = Database::getManager();
            $repo = $em->getRepository(PluginEntity::class);

            // Find plugin entity (handle common casings)
            $pluginEntity = $repo->findOneBy(['title' => 'Onlyoffice'])
                ?: $repo->findOneBy(['title' => 'OnlyOffice']);

            // Load configuration array for current access URL (null-safe)
            $configuration = null;
            if ($pluginEntity) {
                $currentUrl = Container::getAccessUrlUtil()->getCurrent();
                $rel = $pluginEntity->getConfigurationsByAccessUrl($currentUrl); // might be null
                $configuration = $rel ? $rel->getConfiguration() : null; // might be null
            }

            // 1) Runtime overrides take precedence (posted form values)
            if (null !== $this->newSettings) {
                // Exact key
                if (array_key_exists($settingName, $this->newSettings)) {
                    $val = $this->newSettings[$settingName];
                    if ($this->isSettingUrl($settingName) && is_string($val)) {
                        $val = $this->processUrl($val);
                    }
                    if ($val !== '' && $val !== null) {
                        return $val;
                    }
                }

                // Allow prefix-less variant (e.g. 'document_server_url' when setting is 'Onlyoffice_document_server_url')
                $prefix = $this->plugin->get_name();
                if (0 === strpos((string) $settingName, $prefix.'_')) {
                    $stripped = substr($settingName, strlen($prefix) + 1);
                    if (array_key_exists($stripped, $this->newSettings)) {
                        $val = $this->newSettings[$stripped];
                        if ($this->isSettingUrl($stripped) && is_string($val)) {
                            $val = $this->processUrl($val);
                        }
                        if ($val !== '' && $val !== null) {
                            return $val;
                        }
                    }
                }

                // Try alternate key mappings (legacy ↔ short names)
                foreach ($this->getAlternateKeys($settingName) as $alt) {
                    if (array_key_exists($alt, $this->newSettings)) {
                        $val = $this->newSettings[$alt];
                        if ($this->isSettingUrl($alt) && is_string($val)) {
                            $val = $this->processUrl($val);
                        }
                        if ($val !== '' && $val !== null) {
                            return $val;
                        }
                    }
                }
            }

            // 2) Persisted configuration in access_url_rel_plugin
            if (is_array($configuration)) {
                // Exact key
                if (array_key_exists($settingName, $configuration)) {
                    $val = $configuration[$settingName];
                    if ($this->isSettingUrl($settingName) && is_string($val)) {
                        $val = $this->processUrl($val);
                    }
                    if ($val !== '' && $val !== null) {
                        return $val;
                    }
                }
                // Alternate keys
                foreach ($this->getAlternateKeys($settingName) as $alt) {
                    if (array_key_exists($alt, $configuration)) {
                        $val = $configuration[$alt];
                        if ($this->isSettingUrl($alt) && is_string($val)) {
                            $val = $this->processUrl($val);
                        }
                        if ($val !== '' && $val !== null) {
                            return $val;
                        }
                    }
                }
            }

            // 3) Special cases / defaults
            switch ($settingName) {
                case $this->jwtHeader:
                    // Legacy platform aggregated setting (array by plugin name)
                    $settings = api_get_setting($settingName);
                    $val = is_array($settings) && array_key_exists($this->plugin->get_name(), $settings)
                        ? $settings[$this->plugin->get_name()]
                        : null;
                    return $val ?: 'Authorization';

                case $this->documentServerInternalUrl:
                    // Legacy platform aggregated setting (array by plugin name)
                    $settings = api_get_setting($settingName);
                    $val = is_array($settings) ? ($settings[$this->plugin->get_name()] ?? null) : null;
                    return $val;

                case $this->useDemoName:
                    // Explicit fallback from per-URL configuration
                    return (is_array($configuration) && array_key_exists($settingName, $configuration))
                        ? $configuration[$settingName]
                        : null;

                // Note: $this->jwtPrefix may be declared by the parent SDK class.
                case $this->jwtPrefix:
                    return 'Bearer ';
            }

            // 4) Legacy plugin storage as last resort
            if (!empty($this->plugin) && method_exists($this->plugin, 'get')) {
                $val = $this->plugin->get($settingName);
                if ($val !== '' && $val !== null) {
                    return $val;
                }
            }

            // 5) Global configuration fallback
            $val = api_get_configuration_value($settingName);
            return $val !== false ? $val : null;

        } catch (\Throwable $e) {
            // Log and return null to avoid fatal errors on admin UI
            error_log('[OnlyOffice] getSetting error: '.$e->getMessage());
            return null;
        }
    }

    /**
     * Persist a configuration value into access_url_rel_plugin.configuration for the current URL.
     * URL-like values are normalized via processUrl().
     * Keeps alternate legacy/short keys in sync to maximize compatibility.
     *
     * @param string $settingName
     * @param mixed  $value
     * @param bool   $createSetting
     *
     * @return void
     *
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     */
    public function setSetting($settingName, $value, $createSetting = false): void
    {
        // Normalize URL values if applicable
        if ($this->isSettingUrl($settingName) && is_string($value)) {
            $value = $this->processUrl($value);
        }

        $em = Database::getManager();
        $repo = $em->getRepository(PluginEntity::class);

        // Find plugin entity (handle common casings)
        $pluginEntity = $repo->findOneBy(['title' => 'Onlyoffice'])
            ?: $repo->findOneBy(['title' => 'OnlyOffice']);

        if (!$pluginEntity) {
            // Safeguard: if plugin entity does not exist, avoid fatal and log
            error_log('[OnlyOffice] setSetting: plugin entity not found');
            return;
        }

        // Get or create relation for current Access URL
        $currentUrl = Container::getAccessUrlUtil()->getCurrent();
        $rel = $pluginEntity->getConfigurationsByAccessUrl($currentUrl);

        if (!$rel) {
            // Create relation with default inactive state until explicitly enabled
            $rel = (new AccessUrlRelPlugin())
                ->setUrl($currentUrl)
                ->setActive(false)
                ->setConfiguration([]);
            $pluginEntity->addConfigurationsInUrl($rel);
        }

        // Update configuration array
        $settings = $rel->getConfiguration() ?? [];
        $settings[$settingName] = $value;

        // Keep alternate keys in sync (legacy ↔ short)
        foreach ($this->getAlternateKeys($settingName) as $alt) {
            $settings[$alt] = $value;
        }

        $rel->setConfiguration($settings);

        // Persist changes
        $em->persist($pluginEntity);
        $em->flush();
    }

    /**
     * Returns the Chamilo server base URL.
     *
     * @return string
     */
    public function getServerUrl()
    {
        return api_get_path(WEB_PATH);
    }

    /**
     * Get link to Docs Cloud.
     *
     * @return string
     */
    public function getLinkToDocs()
    {
        return self::LINK_TO_DOCS;
    }

    /**
     * Determine if a given setting name refers to a URL value.
     * Accepts both legacy and short key variants.
     *
     * @param string $settingName
     *
     * @return bool
     */
    public function isSettingUrl($settingName)
    {
        // Note: we check by key name, not by value
        return in_array($settingName, [
            $this->documentServerUrl,       // 'document_server_url'
            $this->documentServerInternalUrl, // 'onlyoffice_internal_url' (legacy)
            'document_server_internal',     // short variant
            $this->storageUrl,              // 'onlyoffice_storage_url' (legacy)
            'storage_url',                  // short variant
        ], true);
    }

    /**
     * Map OnlyOffice setting keys between legacy and short names, both directions.
     *
     * @param string $key
     *
     * @return array<string>
     */
    private function getAlternateKeys(string $key): array
    {
        switch ($key) {
            // Header name
            case 'onlyoffice_jwt_header':
                return ['jwt_header'];
            case 'jwt_header':
                return ['onlyoffice_jwt_header'];

            // Internal URL
            case 'onlyoffice_internal_url':
                return ['document_server_internal'];
            case 'document_server_internal':
                return ['onlyoffice_internal_url'];

            // Storage URL
            case 'onlyoffice_storage_url':
                return ['storage_url'];
            case 'storage_url':
                return ['onlyoffice_storage_url'];

            default:
                return [];
        }
    }
}
