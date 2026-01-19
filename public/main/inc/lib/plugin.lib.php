<?php
/* See license terms in /license.txt */

use Chamilo\CoreBundle\Enums\ToolIcon;
use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\Finder\Finder;

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
     */
    public function read_plugins_from_path(): array
    {
        /* We scan the plugin directory. Each folder is a potential plugin. */
        $pluginPath = api_get_path(SYS_PLUGIN_PATH);
        $finder = (new Finder())->directories()->depth('== 0')->sortByName()->in($pluginPath);

        $plugins = [];

        foreach ($finder as $file) {
            $plugins[] = $file->getFilename();
        }

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

    public function isInstalled(string $plugin): bool
    {
        $list = $this->getInstalledPlugins(false);

        return in_array($plugin, $list);
    }

    public function getInstalledPlugins(bool $fromDatabase = true): array
    {
        static $installedPlugins = null;

        if (false === $fromDatabase && is_array($installedPlugins)) {
            return $installedPlugins;
        }

        if ($fromDatabase || null === $installedPlugins) {
            $installedPlugins = [];

            $plugins = Container::getPluginRepository()->getInstalledPlugins();

            foreach ($plugins as $plugin) {
                $installedPlugins[] = $plugin->getTitle();
            }
        }

        return $installedPlugins;
    }

    public function getInstalledPluginsInCurrentUrl()
    {
        $installedPlugins = [];
        $urlId = api_get_current_access_url_id();
        $plugins = api_get_settings_params(
            [
                'variable = ? AND selected_value = ? AND category = ? AND access_url = ?' => ['status', 'installed', 'Plugins', $urlId],
            ]
        );

        if (!empty($plugins)) {
            foreach ($plugins as $row) {
                $installedPlugins[$row['subkey']] = true;
            }
            $installedPlugins = array_keys($installedPlugins);
        }

        return $installedPlugins;
    }

    /**
     * Returns a list of all official (delivered with the Chamilo package)
     * plugins. This list is maintained manually and updated with every new
     * release to avoid hacking.
     *
     * @return array
     */
    public static function getOfficialPlugins(): array
    {
        // Please keep this list alphabetically sorted
        return [
            'AzureActiveDirectory',
            'Bbb',
            'BeforeLogin',
            'BuyCourses',
            'CardGame',
            'CheckExtraFieldAuthorCompany',
            'CleanDeletedFiles',
            'CourseBlock',
            'CourseHomeNotify',
            'CourseLegal',
            'CustomCertificate',
            'CustomFooter',
            'Dashboard',
            'Dictionary',
            'EmbedRegistry',
            'ExerciseSignature',
            'ExtAuthChamiloLogoutButtonBehaviour',
            'ExternalNotificationConnect',
            'ExtraMenuFromWebservice',
            'GoogleMaps',
            'GradingElectronic',
            'H5pImport',
            'HelloWorld',
            'ImsLti',
            'Justification',
            'LearningCalendar',
            'LtiProvider',
            'MaintenanceMode',
            'MigrationMoodle',
            'Mobidico',
            'NoSearchIndex',
            'NotebookTeacher',
            'PauseTraining',
            'Pens',
            'Positioning',
            'QuestionOptionsEvaluation',
            'Redirection',
            'Resubscription',
            'Rss',
            'SearchCourse',
            'ShowRegions',
            'ShowUserInfo',
            'Static',
            'StudentFollowUp',
            'SurveyExportCsv',
            'SurveyExportTxt',
            'Test2Pdf',
            'TopLinks',
            'Tour',
            'UserRemoteService',
            'XApi',
            'Zoom',
        ];
    }

    public static function isOfficial(string $title): bool
    {
        return in_array($title, self::getOfficialPlugins());
    }

    public function install(string $pluginName): void
    {
        $pluginPath = api_get_path(SYS_PLUGIN_PATH).$pluginName.'/install.php';

        if (is_file($pluginPath) && is_readable($pluginPath)) {
            // Execute the install procedure.

            require $pluginPath;
        }
    }

    public function uninstall(string $pluginName): void
    {
        // First call the custom uninstallation to allow full access to global settings
        $pluginPath = api_get_path(SYS_PLUGIN_PATH).$pluginName.'/uninstall.php';
        if (is_file($pluginPath) && is_readable($pluginPath)) {
            // Execute the uninstall procedure.

            require $pluginPath;
        }
    }

    public function getAreasByPlugin(string $pluginName): array
    {
        $currentAccessUrl = Container::getAccessUrlUtil()->getCurrent();
        $installedPlugin = Container::getPluginRepository()->getInstalledByName($pluginName);
        $regionList = [];

        if ($pluginConfigurationInAccessUrl = $installedPlugin->getConfigurationsByAccessUrl($currentAccessUrl)) {
            if ($pluginConfiguration = $pluginConfigurationInAccessUrl->getConfiguration()) {
                $regionList = $pluginConfiguration['regions'] ?? [];
            }
        }

        return $regionList;
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
     * @param string           $region
     * @param Twig_Environment $template
     * @param bool             $forced
     *
     * @return string|null
     */
    public function loadRegion($pluginName, $region, $template, $forced = false)
    {
        if ('course_tool_plugin' == $region) {
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
        $language_interface = api_get_language_isocode();
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
        if ('english' != $language_interface) {
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
        $plugin_info = [];
        $pluginPath  = api_get_path(SYS_PLUGIN_PATH);

        $posibleName = [
            $pluginName,
            strtolower($pluginName),
            ucfirst(strtolower($pluginName)),
        ];

        $plugin_file = null;

        foreach ($posibleName as $dir) {
            $path = $pluginPath."$dir/plugin.php";
            if (is_file($path)) {
                $plugin_file = $path;
                break;
            }
        }

        if ($plugin_file) {
            $fileToLoad = true;
            require $plugin_file;
        }

        if (isset($plugin_info['plugin_class']) && class_exists($plugin_info['plugin_class'], false)) {
            $cls = $plugin_info['plugin_class'];
            $instance = method_exists($cls, 'create') ? $cls::create() : new $cls();
            if (method_exists($instance, 'get_info')) {
                $plugin_info = $instance->get_info();
            }
        }

        $repo   = Container::getPluginRepository();
        $entity = $repo->findOneByTitle($pluginName) ?: $repo->findOneByTitle(ucfirst(strtolower($pluginName)));
        if ($entity) {
            $configByUrl = $entity->getConfigurationsByAccessUrl(Container::getAccessUrlUtil()->getCurrent());
            $plugin_info['settings'] = $configByUrl?->getConfiguration() ?? [];
        }

        return $plugin_info;
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
     * @param int $courseId
     */
    public function install_course_plugins(int $courseId): void
    {
        $pluginList = $this->getInstalledPluginListObject();
        if (empty($pluginList)) {
            return;
        }

        $accessUrl = Container::getAccessUrlUtil()->getCurrent();
        $pluginRepo = Container::getPluginRepository();

        /** @var Plugin $obj */
        foreach ($pluginList as $obj) {
            if (empty($obj->isCoursePlugin)) {
                continue;
            }

            $entity = $pluginRepo->findOneByTitle($obj->get_name());
            $rel    = $entity?->getConfigurationsByAccessUrl($accessUrl);
            if (!$rel || !$rel->isActive()) {
                continue;
            }

            $obj->get_settings(true);

            $obj->course_install($courseId);
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
                $icon = Display::getMdiIcon(
                    ToolIcon::PLUGIN,
                    'ch-tool-icon',
                    null,
                    ICON_SIZE_SMALL,
                    Security::remove_XSS($pluginTitle)
                );
                $form->addHtml('<div class="panel panel-default">');
                $form->addHtml('
                    <div class="panel-heading" role="tab" id="heading-'.$pluginName.'-settings">
                        <h4 class="panel-title">
                            <a class="collapsed"
                                role="button" data-toggle="collapse" data-parent="#accordion"
                                href="#collapse-'.$pluginName.'-settings" aria-expanded="false"
                                aria-controls="collapse-'.$pluginName.'-settings">
                ');
                $form->addHtml($icon.' '.$pluginTitle);
                $form->addHtml('
                            </a>
                        </h4>
                    </div>
                ');
                $form->addHtml('
                    <div
                        id="collapse-'.$pluginName.'-settings"
                        class="panel-collapse collapse" role="tabpanel"
                        aria-labelledby="heading-'.$pluginName.'-settings">
                        <div class="panel-body">
                '
                );

                $groups = [];
                foreach ($obj->course_settings as $setting) {
                    if (false === $obj->validateCourseSetting($setting['name'])) {
                        continue;
                    }
                    if ('checkbox' !== $setting['type']) {
                        $form->addElement($setting['type'], $setting['name'], $obj->get_lang($setting['name']));
                    } else {
                        $element = &$form->createElement(
                            $setting['type'],
                            $setting['name'],
                            '',
                            $obj->get_lang($setting['name'])
                        );
                        $courseSetting = api_get_course_setting($setting['name']);
                        if (-1 === $courseSetting) {
                            $defaultValue = api_get_plugin_setting($pluginName, $setting['name']);
                            if (!empty($defaultValue)) {
                                if ('true' === $defaultValue) {
                                    $element->setChecked(true);
                                }
                            }
                        }

                        if (isset($setting['init_value']) && 1 == $setting['init_value']) {
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
                $form->addHtml(
            '
                        </div>
                    </div>
                '
        );
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
