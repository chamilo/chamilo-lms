<?php
/**
 * (c) Copyright Ascensio System SIA 2024.
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

/**
 * Plugin class for the Onlyoffice plugin.
 *
 * @author Asensio System SIA
 */
class OnlyofficePlugin extends Plugin implements HookPluginInterface
{
    /**
     * OnlyofficePlugin name.
     */
    private $pluginName = 'onlyoffice';

    /**
     * OnlyofficePlugin constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '1.5.0',
            'Asensio System SIA',
            [
                'enable_onlyoffice_plugin' => 'boolean',
                'document_server_url' => 'text',
                'jwt_secret' => 'text',
                'jwt_header' => 'text',
                'document_server_internal' => 'text',
                'storage_url' => 'text',
            ]
        );
    }

    /**
     * Create OnlyofficePlugin object.
     */
    public static function create(): OnlyofficePlugin
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * This method install the plugin tables.
     */
    public function install()
    {
        $this->installHook();
    }

    /**
     * This method drops the plugin tables.
     */
    public function uninstall()
    {
        $this->uninstallHook();
    }

    /**
     * Install the "create" hooks.
     */
    public function installHook()
    {
        $itemActionObserver = OnlyofficeItemActionObserver::create();
        HookDocumentItemAction::create()->attach($itemActionObserver);

        $actionObserver = OnlyofficeActionObserver::create();
        HookDocumentAction::create()->attach($actionObserver);

        $viewObserver = OnlyofficeItemViewObserver::create();
        HookDocumentItemView::create()->attach($viewObserver);
    }

    /**
     * Uninstall the "create" hooks.
     */
    public function uninstallHook()
    {
        $itemActionObserver = OnlyofficeItemActionObserver::create();
        HookDocumentItemAction::create()->detach($itemActionObserver);

        $actionObserver = OnlyofficeActionObserver::create();
        HookDocumentAction::create()->detach($actionObserver);

        $viewObserver = OnlyofficeItemViewObserver::create();
        HookDocumentItemView::create()->detach($viewObserver);
    }

    /**
     * Get link to plugin settings.
     *
     * @return string
     */
    public function getConfigLink()
    {
        return api_get_path(WEB_PATH).'main/admin/configure_plugin.php?name='.$this->pluginName;
    }

    /**
     * Get plugin name.
     *
     * @return string
     */
    public function getPluginName()
    {
        return $this->pluginName;
    }

    public static function isExtensionAllowed(string $extension): bool
    {
        $officeExtensions = [
            'ppt',
            'pptx',
            'odp',
            'xls',
            'xlsx',
            'ods',
            'csv',
            'doc',
            'docx',
            'odt',
            'pdf',
        ];

        return in_array($extension, $officeExtensions);
    }
}
