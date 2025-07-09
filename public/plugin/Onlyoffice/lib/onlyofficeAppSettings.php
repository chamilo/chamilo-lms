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
     * The config key for the demo data.
     *
     * @var string
     */
    public $useDemoName = 'onlyoffice_connect_demo_data';

    /**
     * Chamilo plugin.
     */
    public $plugin;

    public $newSettings;

    /**
     * The config key for JWT secret key.
     *
     * @var string
     */
    protected $jwtKey = 'jwt_secret';

    public function __construct(Plugin $plugin, ?array $newSettings = null)
    {
        parent::__construct();
        $this->plugin = $plugin;
        $this->newSettings = $newSettings;
    }

    public function getSetting($settingName)
    {
        $value = null;
        if (null !== $this->newSettings) {
            if (isset($this->newSettings[$settingName])) {
                $value = $this->newSettings[$settingName];
            }

            if (empty($value)) {
                $prefix = $this->plugin->getPluginName();

                if (substr($settingName, 0, strlen($prefix)) == $prefix) {
                    $settingNameWithoutPrefix = substr($settingName, strlen($prefix) + 1);
                }

                if (isset($this->newSettings[$settingNameWithoutPrefix])) {
                    $value = $this->newSettings[$settingNameWithoutPrefix];
                }
            }
            if ($this->isSettingUrl($value)) {
                $value = $this->processUrl($value);
            }
            if (!empty($value)) {
                return $value;
            }
        }
        switch ($settingName) {
            case $this->jwtHeader:
                $settings = api_get_setting($settingName);
                $value = is_array($settings) && array_key_exists($this->plugin->getPluginName(), $settings)
                    ? $settings[$this->plugin->getPluginName()]
                    : null;

                if (empty($value)) {
                    $value = 'Authorization';
                }
                break;
            case $this->documentServerInternalUrl:
                $settings = api_get_setting($settingName);
                $value = is_array($settings) ? ($settings[$this->plugin->getPluginName()] ?? null) : null;
                break;
            case $this->useDemoName:
                $settings = api_get_setting($settingName);
                $value = is_array($settings) ? ($settings[0] ?? null) : null;
                break;
            case $this->jwtPrefix:
                $value = 'Bearer ';
                break;
            default:
                if (!empty($this->plugin) && method_exists($this->plugin, 'get')) {
                    $value = $this->plugin->get($settingName);
                }
        }
        if (empty($value)) {
            $value = api_get_configuration_value($settingName);
        }

        return $value;
    }

    public function setSetting($settingName, $value, $createSetting = false)
    {
        if (($settingName === $this->useDemoName) && $createSetting) {
            api_add_setting($value, $settingName, null, 'setting', 'Plugins');

            return;
        }

        $prefix = $this->plugin->getPluginName();
        if (!(substr($settingName, 0, strlen($prefix)) == $prefix)) {
            $settingName = $prefix.'_'.$settingName;
        }
        api_set_setting($settingName, $value);
    }

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

    public function isSettingUrl($settingName)
    {
        return in_array($settingName, [$this->documentServerUrl, $this->documentServerInternalUrl, $this->storageUrl]);
    }
}
