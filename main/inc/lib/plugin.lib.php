<?php
/* See license terms in /license.txt */

use ChamiloSession as Session;

/**
 * Class AppPlugin.
 */
class AppPlugin
{
    public $plugin_regions = [
        'main_top',
        'main_bottom',
        'login_top',
        'login_bottom',
        'menu_top',
        'menu_bottom',
        'content_top',
        'content_bottom',
        'header_main',
        'header_center',
        'header_left',
        'header_right',
        'pre_footer',
        'footer_left',
        'footer_center',
        'footer_right',
        'menu_administrator',
        'course_tool_plugin',
    ];

    public $installedPluginListName = [];
    public $installedPluginListObject = [];
    private static $instance;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return AppPlugin
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Read plugin from path.
     *
     * @return array
     */
    public function read_plugins_from_path()
    {
        /* We scan the plugin directory. Each folder is a potential plugin. */
        $pluginPath = api_get_path(SYS_PLUGIN_PATH);
        $plugins = [];
        $handle = @opendir($pluginPath);
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..' && is_dir(api_get_path(SYS_PLUGIN_PATH).$file)) {
                $plugins[] = $file;
            }
        }
        @closedir($handle);
        sort($plugins);

        return $plugins;
    }

    /**
     * @return array
     */
    public function getInstalledPluginListName()
    {
        if (empty($this->installedPluginListName)) {
            $this->installedPluginListName = $this->getInstalledPlugins();
        }

        return $this->installedPluginListName;
    }

    /**
     * @return array List of Plugin
     */
    public function getInstalledPluginListObject()
    {
        if (empty($this->installedPluginListObject)) {
            $this->setInstalledPluginListObject();
        }

        return $this->installedPluginListObject;
    }

    public function setInstalledPluginListObject()
    {
        $pluginListName = $this->getInstalledPluginListName();
        $pluginList = [];
        if (!empty($pluginListName)) {
            foreach ($pluginListName as $pluginName) {
                $pluginInfo = $this->getPluginInfo($pluginName, true);
                if (isset($pluginInfo['plugin_class'])) {
                    $pluginList[] = $pluginInfo['plugin_class']::create();
                }
            }
        }
        $this->installedPluginListObject = $pluginList;
    }

    /**
     * @param string $plugin
     *
     * @return bool
     */
    public function isInstalled($plugin)
    {
        $list = self::getInstalledPlugins(false);

        return in_array($plugin, $list);
    }

    /**
     * @deprecated
     */
    public function get_installed_plugins($fromDatabase = true)
    {
        return $this->getInstalledPlugins($fromDatabase);
    }

    /**
     * @param bool $fromDatabase
     *
     * @return array
     */
    public function getInstalledPlugins($fromDatabase = true)
    {
        static $installedPlugins = null;

        if ($fromDatabase === false) {
            if (is_array($installedPlugins)) {
                return $installedPlugins;
            }
        }

        if ($fromDatabase || $installedPlugins === null) {
            $installedPlugins = [];
            $plugins = api_get_settings_params(
                [
                    'variable = ? AND selected_value = ? AND category = ? ' => ['status', 'installed', 'Plugins'],
                ]
            );

            if (!empty($plugins)) {
                foreach ($plugins as $row) {
                    $installedPlugins[$row['subkey']] = true;
                }
                $installedPlugins = array_keys($installedPlugins);
            }
        }

        return $installedPlugins;
    }

    /**
     * @param string $pluginName
     * @param int    $urlId
     */
    public function install($pluginName, $urlId = null)
    {
        $urlId = (int) $urlId;
        if (empty($urlId)) {
            $urlId = api_get_current_access_url_id();
        }

        api_add_setting(
            'installed',
            'status',
            $pluginName,
            'setting',
            'Plugins',
            $pluginName,
            '',
            '',
            '',
            $urlId,
            1
        );

        $pluginPath = api_get_path(SYS_PLUGIN_PATH).$pluginName.'/install.php';

        if (is_file($pluginPath) && is_readable($pluginPath)) {
            // Execute the install procedure.

            require $pluginPath;
        }
    }

    /**
     * @param string $pluginName
     * @param int    $urlId
     */
    public function uninstall($pluginName, $urlId = null)
    {
        $urlId = (int) $urlId;
        if (empty($urlId)) {
            $urlId = api_get_current_access_url_id();
        }

        // First call the custom uninstall to allow full access to global settings
        $pluginPath = api_get_path(SYS_PLUGIN_PATH).$pluginName.'/uninstall.php';
        if (is_file($pluginPath) && is_readable($pluginPath)) {
            // Execute the uninstall procedure.

            require $pluginPath;
        }

        // Second remove all remaining global settings
        api_delete_settings_params(
            ['category = ? AND access_url = ? AND subkey = ? ' => ['Plugins', $urlId, $pluginName]]
        );
    }

    /**
     * @param string $pluginName
     *
     * @return array
     */
    public function get_areas_by_plugin($pluginName)
    {
        $result = api_get_settings('Plugins');
        $areas = [];
        foreach ($result as $row) {
            if ($pluginName == $row['selected_value']) {
                $areas[] = $row['variable'];
            }
        }

        return $areas;
    }

    /**
     * @param string $pluginName
     *
     * @return bool
     */
    public function is_valid_plugin($pluginName)
    {
        if (is_dir(api_get_path(SYS_PLUGIN_PATH).$pluginName)) {
            if (is_file(api_get_path(SYS_PLUGIN_PATH).$pluginName.'/index.php')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getPluginRegions()
    {
        sort($this->plugin_regions);

        return $this->plugin_regions;
    }

    /**
     * @param array            $pluginRegionList
     * @param string           $region
     * @param Twig_Environment $template
     * @param bool             $forced
     *
     * @return string|null
     */
    public function loadRegion($pluginName, $region, $template, $forced = false)
    {
        if ($region == 'course_tool_plugin') {
            return '';
        }

        ob_start();
        $this->getAllPluginContentsByRegion($pluginName, $region, $template, $forced);
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Loads the translation files inside a plugin if exists.
     * It loads by default english see the hello world plugin.
     *
     * @param string $plugin_name
     *
     * @todo add caching
     */
    public function load_plugin_lang_variables($plugin_name)
    {
        $language_interface = api_get_interface_language();
        $root = api_get_path(SYS_PLUGIN_PATH);
        $strings = null;

        // 1. Loading english if exists
        $english_path = $root.$plugin_name.'/lang/english.php';
        if (is_readable($english_path)) {
            include $english_path;

            foreach ($strings as $key => $string) {
                $GLOBALS[$key] = $string;
            }
        }

        // 2. Loading the system language
        if ($language_interface != 'english') {
            $path = $root.$plugin_name."/lang/$language_interface.php";
            if (is_readable($path)) {
                include $path;
                if (!empty($strings)) {
                    foreach ($strings as $key => $string) {
                        $GLOBALS[$key] = $string;
                    }
                }
            } else {
                /*$interfaceLanguageId = api_get_language_id($language_interface);
                $interfaceLanguageInfo = api_get_language_info($interfaceLanguageId);
                $languageParentId = intval($interfaceLanguageInfo['parent_id']);

                if ($languageParentId > 0) {
                    $languageParentInfo = api_get_language_info($languageParentId);
                    $languageParentFolder = $languageParentInfo['dokeos_folder'];

                    $parentPath = "{$root}{$plugin_name}/lang/{$languageParentFolder}.php";
                    if (is_readable($parentPath)) {
                        include $parentPath;
                        if (!empty($strings)) {
                            foreach ($strings as $key => $string) {
                                $this->strings[$key] = $string;
                            }
                        }
                    }
                }*/
            }
        }
    }

    /**
     * @param array            $_plugins
     * @param string           $region
     * @param Twig_Environment $template
     * @param bool             $forced
     *
     * @return bool
     *
     * @todo improve this function
     */
    public function getAllPluginContentsByRegion($plugin_name, $region, $template, $forced = false)
    {
        // The plugin_info variable is available inside the plugin index
        $plugin_info = $this->getPluginInfo($plugin_name, $forced);

        // We also know where the plugin is
        $plugin_info['current_region'] = $region;

        // Loading the plugin/XXX/index.php file
        $plugin_file = api_get_path(SYS_PLUGIN_PATH)."$plugin_name/index.php";

        if (file_exists($plugin_file)) {
            //Loading the lang variables of the plugin if exists
            self::load_plugin_lang_variables($plugin_name);

            // Printing the plugin index.php file
            require $plugin_file;

            // If the variable $_template is set we assign those values to be accessible in Twig
            if (isset($_template)) {
                $_template['plugin_info'] = $plugin_info;
            } else {
                $_template = [];
                $_template['plugin_info'] = $plugin_info;
            }

            // Setting the plugin info available in the template if exists.
            //$template->addGlobal($plugin_name, $_template);

            // Loading the Twig template plugin files if exists
            $templateList = [];
            if (isset($plugin_info) && isset($plugin_info['templates'])) {
                $templateList = $plugin_info['templates'];
            }

            if (!empty($templateList)) {
                foreach ($templateList as $pluginTemplate) {
                    if (!empty($pluginTemplate)) {
                        $templatePluginFile = "$plugin_name/$pluginTemplate"; // for twig
                        //$template->render($templatePluginFile, []);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Loads plugin info.
     *
     * @staticvar array $plugin_data
     *
     * @param string $pluginName
     * @param bool   $forced     load from DB or from the static array
     *
     * @return array
     *
     * @todo filter setting_form
     */
    public function getPluginInfo($pluginName, $forced = false)
    {
        //$pluginData = Session::read('plugin_data');
        if (0) {
            //if (isset($pluginData[$pluginName]) && $forced == false) {
            return $pluginData[$pluginName];
        } else {
            $plugin_file = api_get_path(SYS_PLUGIN_PATH)."$pluginName/plugin.php";

            $plugin_info = [];
            if (file_exists($plugin_file)) {
                require $plugin_file;
            }

            // @todo check if settings are already added
            // Extra options
            $plugin_settings = api_get_settings_params(
                [
                    'subkey = ? AND category = ? AND type = ? AND access_url = ?' => [
                        $pluginName,
                        'Plugins',
                        'setting',
                        api_get_current_access_url_id(),
                    ],
                ]
            );

            $settings_filtered = [];
            foreach ($plugin_settings as $item) {
                if (!empty($item['selected_value'])) {
                    //if (unserialize($item['selected_value']) !== false) {
                        //$item['selected_value'] = unserialize($item['selected_value']);
                    //}
                }
                $settings_filtered[$item['variable']] = $item['selected_value'];
            }

            $plugin_info['settings'] = $settings_filtered;
            $pluginData[$pluginName] = $plugin_info;
            //Session::write('plugin_data', $pluginData);

            return $plugin_info;
        }
    }

    /**
     * Get the template list.
     *
     * @param string $pluginName
     *
     * @return bool
     */
    public function get_templates_list($pluginName)
    {
        $plugin_info = $this->getPluginInfo($pluginName);
        if (isset($plugin_info) && isset($plugin_info['templates'])) {
            return $plugin_info['templates'];
        }

        return false;
    }

    /**
     * Remove all regions of an specific plugin.
     *
     * @param string $plugin
     */
    public function removeAllRegions($plugin)
    {
        if (!empty($plugin)) {
            api_delete_settings_params(
                [
                    'category = ? AND type = ? AND access_url = ? AND subkey = ? ' => [
                        'Plugins',
                        'region',
                        api_get_current_access_url_id(),
                        $plugin,
                    ],
                ]
            );
        }
    }

    /**
     * Add a plugin to a region.
     *
     * @param string $plugin
     * @param string $region
     */
    public function add_to_region($plugin, $region)
    {
        api_add_setting(
            $plugin,
            $region,
            $plugin,
            'region',
            'Plugins',
            $plugin,
            '',
            '',
            '',
            api_get_current_access_url_id(),
            1
        );
    }

    /**
     * @param int $courseId
     */
    public function install_course_plugins($courseId)
    {
        $pluginList = $this->getInstalledPluginListObject();

        if (!empty($pluginList)) {
            /** @var Plugin $obj */
            foreach ($pluginList as $obj) {
                $pluginName = $obj->get_name();
                $plugin_path = api_get_path(SYS_PLUGIN_PATH).$pluginName.'/plugin.php';

                if (file_exists($plugin_path)) {
                    require $plugin_path;
                    if (isset($plugin_info) && isset($plugin_info['plugin_class']) && $obj->isCoursePlugin) {
                        $obj->course_install($courseId);
                    }
                }
            }
        }
    }

    /**
     * Trigger for Plugin::doWhenDeleting[Item] functions.
     *
     * @param string $itemType
     * @param int    $itemId
     */
    public function performActionsWhenDeletingItem($itemType, $itemId)
    {
        $pluginList = $this->getInstalledPluginListObject();

        if (empty($pluginList)) {
            return;
        }

        /** @var Plugin $pluginObj */
        foreach ($pluginList as $pluginObj) {
            switch ($itemType) {
                case 'course':
                    $pluginObj->doWhenDeletingCourse($itemId);
                    break;
                case 'session':
                    $pluginObj->doWhenDeletingSession($itemId);
                    break;
                case 'user':
                    $pluginObj->doWhenDeletingUser($itemId);
                    break;
            }
        }
    }

    /**
     * Add the course settings to the course settings form.
     *
     * @param FormValidator $form
     */
    public function add_course_settings_form($form)
    {
        $pluginList = $this->getInstalledPluginListObject();
        /** @var Plugin $obj */
        foreach ($pluginList as $obj) {
            $pluginName = $obj->get_name();
            $pluginTitle = $obj->get_title();
            if (!empty($obj->course_settings)) {
                if (is_file(api_get_path(SYS_CODE_PATH).'img/icons/'.ICON_SIZE_SMALL.'/'.$pluginName.'.png')) {
                    $icon = Display::return_icon(
                        $pluginName.'.png',
                        Security::remove_XSS($pluginTitle),
                        '',
                        ICON_SIZE_SMALL
                    );
                } else {
                    $icon = Display::return_icon(
                        'plugins.png',
                        Security::remove_XSS($pluginTitle),
                        '',
                        ICON_SIZE_SMALL
                    );
                }

                $form->addHtml('<div class="panel panel-default">');
                $form->addHtml('
                    <div class="panel-heading" role="tab" id="heading-'.$pluginName.'-settings">
                        <h4 class="panel-title">
                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse-'.$pluginName.'-settings" aria-expanded="false" aria-controls="collapse-'.$pluginName.'-settings">
                ');
                $form->addHtml($icon.' '.$pluginTitle);
                $form->addHtml('
                            </a>
                        </h4>
                    </div>
                ');
                $form->addHtml('
                    <div id="collapse-'.$pluginName.'-settings" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading-'.$pluginName.'-settings">
                        <div class="panel-body">
                ');

                $groups = [];
                foreach ($obj->course_settings as $setting) {
                    if ($obj->validateCourseSetting($setting['name']) === false) {
                        continue;
                    }
                    if ($setting['type'] != 'checkbox') {
                        $form->addElement($setting['type'], $setting['name'], $obj->get_lang($setting['name']));
                    } else {
                        $element = &$form->createElement(
                            $setting['type'],
                            $setting['name'],
                            '',
                            $obj->get_lang($setting['name'])
                        );
                        if (isset($setting['init_value']) && $setting['init_value'] == 1) {
                            $element->setChecked(true);
                        }
                        $form->addElement($element);

                        if (isset($setting['group'])) {
                            $groups[$setting['group']][] = $element;
                        }
                    }
                }
                foreach ($groups as $k => $v) {
                    $form->addGroup($groups[$k], $k, [$obj->get_lang($k)]);
                }
                $form->addButtonSave(get_lang('Save settings'));
                $form->addHtml('
                        </div>
                    </div>
                ');
                $form->addHtml('</div>');
            }
        }
    }

    /**
     * Get all course settings from all installed plugins.
     *
     * @return array
     */
    public function getAllPluginCourseSettings()
    {
        $pluginList = $this->getInstalledPluginListObject();
        /** @var Plugin $obj */
        $courseSettings = [];
        if (!empty($pluginList)) {
            foreach ($pluginList as $obj) {
                $pluginCourseSetting = $obj->getCourseSettings();
                $courseSettings = array_merge($courseSettings, $pluginCourseSetting);
            }
        }

        return $courseSettings;
    }

    /**
     * When saving the plugin values in the course settings, check whether
     * a callback method should be called and send it the updated settings.
     *
     * @param array $values The new settings the user just saved
     */
    public function saveCourseSettingsHook($values)
    {
        $pluginList = $this->getInstalledPluginListObject();

        /** @var Plugin $obj */
        foreach ($pluginList as $obj) {
            $settings = $obj->getCourseSettings();
            $subValues = [];
            if (!empty($settings)) {
                foreach ($settings as $v) {
                    if (isset($values[$v])) {
                        $subValues[$v] = $values[$v];
                    }
                }
            }

            if (!empty($subValues)) {
                $obj->course_settings_updated($subValues);
            }
        }
    }

    /**
     * Get first SMS plugin name.
     *
     * @return string|bool
     */
    public function getSMSPluginName()
    {
        $installedPluginsList = $this->getInstalledPluginListObject();
        foreach ($installedPluginsList as $installedPlugin) {
            if ($installedPlugin->isMailPlugin) {
                return get_class($installedPlugin);
            }
        }

        return false;
    }

    /**
     * @return SmsPluginLibraryInterface
     */
    public function getSMSPluginLibrary()
    {
        $className = $this->getSMSPluginName();
        $className = str_replace('Plugin', '', $className);

        if (class_exists($className)) {
            return new $className();
        }

        return false;
    }

    /**
     * @param array            $pluginRegionList
     * @param string           $pluginRegion
     * @param Twig_Environment $twig
     */
    public function setPluginRegion($pluginRegionList, $pluginRegion, $twig)
    {
        $regionContent = $this->loadRegion(
            $pluginRegionList,
            $pluginRegion,
            $twig,
            true //$this->force_plugin_load
        );

        //$twig->addGlobal('plugin_'.$pluginRegion, $regionContent);
    }
}
