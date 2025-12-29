<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;

/**
 * Class Plugin
 * Base class for plugins.
 *
 * This class has to be extended by every plugin. It defines basic methods
 * to install/uninstall and get information about a plugin
 *
 * @author    Julio Montoya <gugli100@gmail.com>
 * @author    Yannick Warnier <ywarnier@beeznest.org>
 * @author    Laurent Opprecht    <laurent@opprecht.info>
 * @copyright 2012 University of Geneva
 * @license   GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 */
class Plugin
{
    const TAB_FILTER_NO_STUDENT = '::no-student';
    const TAB_FILTER_ONLY_STUDENT = '::only-student';
    public $isCoursePlugin = false;
    public $isAdminPlugin = false;
    public $isMailPlugin = false;
    // Adds icon in the course home
    public $addCourseTool = true;
    public $hasPersonalEvents = false;

    /**
     * When creating a new course, these settings are added to the course, in
     * the course_info/infocours.php
     * To show the plugin course icons you need to add these icons:
     * main/img/icons/22/plugin_name.png
     * main/img/icons/64/plugin_name.png
     * main/img/icons/64/plugin_name_na.png.
     *
     * @example
     * $course_settings = array(
    array('name' => 'big_blue_button_welcome_message',  'type' => 'text'),
    array('name' => 'big_blue_button_record_and_store', 'type' => 'checkbox')
    );
     */
    public $course_settings = [];
    /**
     * This indicates whether changing the setting should execute the callback
     * function.
     */
    public $course_settings_callback = false;

    protected $version = '';
    protected $author = '';
    protected $fields = [];
    private $settings = [];
    // Translation strings.
    private $strings = null;

    /**
     * Default constructor for the plugin class. By default, it only sets
     * a few attributes of the object.
     *
     * @param string $version  of this plugin
     * @param string $author   of this plugin
     * @param array  $settings settings to be proposed to configure the plugin
     */
    protected function __construct($version, $author, $settings = [])
    {
        $this->version = $version;
        $this->author = $author;
        $this->fields = $settings;

        global $language_files;
        $language_files[] = 'plugin_'.$this->get_name();

        if ($this->isCoursePlugin) {
            if (!isset($this->fields['defaultVisibilityInCourseHomepage'])) {
                $this->fields['defaultVisibilityInCourseHomepage'] = [
                    'type' => 'select',
                    'options' => [
                        'visible' => 'Visible',
                        'hidden'  => 'Hidden',
                    ],
                ];
            }
        }
    }

    /**
     * Gets an array of information about this plugin (name, version, ...).
     *
     * @return array Array of information elements about this plugin
     */
    public function get_info()
    {
        $pluginRepo = Container::getPluginRepository();

        $result = [];
        $result['obj'] = $this;
        $result['title'] = $this->get_title();
        $result['comment'] = $this->get_comment();
        $result['version'] = $this->get_version();
        $result['author'] = $this->get_author();
        $result['plugin_class'] = static::class;
        $result['is_course_plugin'] = $this->isCoursePlugin;
        $result['is_admin_plugin'] = $this->isAdminPlugin;
        $result['is_mail_plugin'] = $this->isMailPlugin;
        $result['entity'] = $pluginRepo->findOneByTitle($this->get_name());

        if ($form = $this->getSettingsForm()) {
            $result['settings_form'] = $form;

            foreach ($this->fields as $name => $type) {
                $value = $this->get($name);

                if (is_array($type)) {
                    $value = $type['options'];
                }
                $result[$name] = $value;
            }
        }

        return $result;
    }

    /**
     * Returns the "system" name of the plugin in lowercase letters.
     */
    public function get_name(): string
    {
        return str_replace('Plugin', '', static::class);
    }

    /**
     * Returns the title of the plugin.
     *
     * @return string
     */
    public function get_title()
    {
        return $this->get_lang('plugin_title');
    }

    /**
     * Returns the description of the plugin.
     *
     * @return string
     */
    public function get_comment()
    {
        return $this->get_lang('plugin_comment');
    }

    /**
     * Returns the version of the plugin.
     *
     * @return string
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Returns the author of the plugin.
     *
     * @return string
     */
    public function get_author()
    {
        return $this->author;
    }

    /**
     * Returns the contents of the CSS defined by the plugin.
     *
     * @return string
     */
    public function get_css()
    {
        $name = $this->get_name();
        $path = api_get_path(SYS_PLUGIN_PATH)."$name/resources/$name.css";
        if (!is_readable($path)) {
            return '';
        }
        $css = [];
        $css[] = file_get_contents($path);
        $result = implode($css);

        return $result;
    }

    /**
     * Returns an HTML form (generated by FormValidator) of the plugin settings.
     *
     * @return FormValidator FormValidator-generated form
     */
    public function getSettingsForm()
    {
        $result = new FormValidator($this->get_name());

        $fields = $this->fields;
        unset($fields['tool_enable']);

        $defaults = [];
        $checkboxGroup = [];
        $checkboxCollection = [];

        if ($checkboxNames = array_keys($fields, 'checkbox')) {
            $pluginInfoCollection = api_get_settings('Plugins');
            foreach ($pluginInfoCollection as $pluginInfo) {
                if (false !== array_search($pluginInfo['title'], $checkboxNames)) {
                    $checkboxCollection[$pluginInfo['title']] = $pluginInfo;
                }
            }
        }

        foreach ($fields as $name => $type) {
            $options = null;
            if (is_array($type) && isset($type['type']) && 'select' === $type['type']) {
                $attributes = isset($type['attributes']) ? $type['attributes'] : [];
                if (!empty($type['options']) && isset($type['translate_options']) && $type['translate_options']) {
                    foreach ($type['options'] as $key => &$optionName) {
                        $optionName = $this->get_lang($optionName);
                    }
                }
                $options = $type['options'];
                $type = $type['type'];
            }

            $value = $this->get($name);
            $defaults[$name] = $value;
            $type = isset($type) ? $type : 'text';

            $help = null;
            if ($this->get_lang_plugin_exists($name.'_help')) {
                $help = $this->get_lang($name.'_help');
                if ("show_main_menu_tab" === $name) {
                    $pluginName = $this->get_name();
                    $pluginUrl = api_get_path(WEB_PATH)."plugin/$pluginName/index.php";
                    $pluginUrl = "<a href=$pluginUrl>$pluginUrl</a>";
                    $help = sprintf($help, $pluginUrl);
                }
            }

            switch ($type) {
                case 'html':
                    $result->addHtml($this->get_lang($name));
                    break;
                case 'wysiwyg':
                    $result->addHtmlEditor($name, $this->get_lang($name), false);
                    break;
                case 'text':
                    $result->addElement($type, $name, [$this->get_lang($name), $help]);
                    break;
                case 'boolean':
                    $group = [];
                    $group[] = $result->createElement(
                        'radio',
                        $name,
                        '',
                        get_lang('Yes'),
                        'true'
                    );
                    $group[] = $result->createElement(
                        'radio',
                        $name,
                        '',
                        get_lang('No'),
                        'false'
                    );
                    $result->addGroup($group, null, [$this->get_lang($name), $help]);
                    break;
                case 'checkbox':
                    $selectedValue = null;
                    if (isset($checkboxCollection[$name])) {
                        if ('true' === $checkboxCollection[$name]['selected_value']) {
                            $selectedValue = 'checked';
                        }
                    }

                    $element = $result->createElement(
                        $type,
                        $name,
                        '',
                        $this->get_lang($name),
                        $selectedValue
                    );
                    $element->_attributes['value'] = 'true';
                    $checkboxGroup[] = $element;
                    break;
                case 'select':
                    $result->addElement(
                        $type,
                        $name,
                        [$this->get_lang($name), $help],
                        $options,
                        $attributes
                    );
                    break;
                case 'user':
                    $options = [];
                    if (!empty($value)) {
                        $userInfo = api_get_user_info($value);
                        if ($userInfo) {
                            $options[$value] = $userInfo['complete_name'];
                        }
                    }
                    $result->addSelectAjax(
                        $name,
                        [$this->get_lang($name), $help],
                        $options,
                        ['url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_like']
                    );
                    break;
            }
        }

        if (!empty($checkboxGroup)) {
            $result->addGroup(
                $checkboxGroup,
                null,
                ['', $help]
            );
        }
        $result->setDefaults($defaults);
        $result->addButtonSave($this->get_lang('Save'), 'submit_button');

        return $result;
    }

    /**
     * Returns the value of a given plugin global setting.
     */
    public function get(string $name): mixed
    {
        if ('tool_enable' === $name) {
            $isEnabled = Container::getPluginHelper()
                ->isPluginEnabled($this->get_name());

            return $isEnabled ? 'true' : 'false';
        }

        $settings = $this->get_settings();

        if (isset($settings[$name])) {
            return $settings[$name];
        }

        foreach ($settings as $setting) {
            if (is_array($setting) && isset($setting[$name])) {
                return $setting[$name];
            }
        }

        return null;
    }

    /**
     * Returns an array with the global settings for this plugin.
     *
     * @param bool $forceFromDB Optional. Force get settings from the database
     *
     * @return array Plugin settings as an array
     *
     * @throws Exception
     */
    public function get_settings(bool $forceFromDB = false): array
    {
        $plugin = Container::getPluginRepository()->findOneByTitle($this->get_name());

        if ($plugin && empty($this->settings) || $forceFromDB) {
            $configByUrl = $plugin->getConfigurationsByAccessUrl(
                Container::getAccessUrlUtil()->getCurrent()
            );

            $this->settings = $configByUrl?->getConfiguration() ?? [];
        }

        return $this->settings;
    }

    /**
     * Tells whether language variables are defined for this plugin or not.
     *
     * @param string $name System name of the plugin
     *
     * @return bool True if the plugin has language variables defined, false otherwise
     */
    public function get_lang_plugin_exists($name)
    {
        return isset($this->strings[$name]);
    }

    /**
     * Hook for the get_lang() function to check for plugin-defined language terms.
     *
     * @param string $name of the language variable we are looking for
     *
     * @return string The translated language term of the plugin
     */
    public function get_lang($name)
    {
        // Check whether the language strings for the plugin have already been
        // loaded. If so, no need to load them again.
        if (is_null($this->strings)) {
            $language_interface = api_get_language_isocode();
            $root = api_get_path(SYS_PLUGIN_PATH);
            $plugin_name = $this->get_name();

            $interfaceLanguageId = api_get_language_id($language_interface);
            if (empty($interfaceLanguageId)) {
                $language_interface = api_get_setting('platformLanguage');
                $interfaceLanguageId = api_get_language_id($language_interface);
            }
            $interfaceLanguageInfo = api_get_language_info($interfaceLanguageId);
            $languageParentId = !empty($interfaceLanguageInfo['parent_id']) ? (int) $interfaceLanguageInfo['parent_id'] : 0;

            // 1. Loading english if exists
            $english_path = $root.$plugin_name."/lang/english.php";

            if (is_readable($english_path)) {
                $strings = [];
                include $english_path;
                $this->strings = $strings;
            }

            $path = $root.$plugin_name."/lang/$language_interface.php";
            // 2. Loading the system language
            if (is_readable($path)) {
                include $path;
                if (!empty($strings)) {
                    foreach ($strings as $key => $string) {
                        $this->strings[$key] = $string;
                    }
                }
            } elseif ($languageParentId > 0) {
                $languageParentInfo = api_get_language_info($languageParentId);
                $languageParentFolder = $languageParentInfo['english_name'];

                $parentPath = "{$root}{$plugin_name}/lang/{$languageParentFolder}.php";
                if (is_readable($parentPath)) {
                    include $parentPath;
                    if (!empty($strings)) {
                        foreach ($strings as $key => $string) {
                            $this->strings[$key] = $string;
                        }
                    }
                }
            }
        }
        if (isset($this->strings[$name])) {
            return $this->strings[$name];
        }

        return get_lang($name);
    }

    /**
     * @param string $variable
     * @param string $language
     *
     * @return string
     */
    public function getLangFromFile($variable, $language)
    {
        static $langStrings = [];

        if (empty($langStrings[$language])) {
            $root = api_get_path(SYS_PLUGIN_PATH);
            $pluginName = $this->get_name();

            $englishPath = "$root$pluginName/lang/$language.php";

            if (is_readable($englishPath)) {
                $strings = [];
                include $englishPath;

                $langStrings[$language] = $strings;
            }
        }

        if (isset($langStrings[$language][$variable])) {
            return $langStrings[$language][$variable];
        }

        return $this->get_lang($variable);
    }

    /**
     * Caller for the install_course_fields() function.
     *
     * @param int  $courseId
     * @param bool $addToolLink Whether to add a tool link on the course homepage
     */
    public function course_install($courseId, $addToolLink = true)
    {
        $this->install_course_fields($courseId, $addToolLink);
    }

    /**
     * Add course settings and, if not asked otherwise, add a tool link on the course homepage.
     *
     * @param int  $courseId      Course integer ID
     * @param bool $add_tool_link Whether to add a tool link or not
     *                            (some tools might just offer a configuration section and act on the backend)
     *
     * @return bool|null False on error, null otherwise
     */
    public function install_course_fields($courseId, $add_tool_link = true)
    {
        $plugin_name = $this->get_name();
        $t_course = Database::get_course_table(TABLE_COURSE_SETTING);
        $courseId = (int) $courseId;

        if (empty($courseId)) {
            return false;
        }

        // Adding course settings.
        if (!empty($this->course_settings)) {
            foreach ($this->course_settings as $setting) {
                $variable = $setting['name'];
                $value = '';
                if (isset($setting['init_value'])) {
                    $value = $setting['init_value'];
                }

                $pluginGlobalValue = api_get_plugin_setting($plugin_name, $variable);
                if (null !== $pluginGlobalValue) {
                    $value = 1;
                }

                $type = 'textfield';
                if (isset($setting['type'])) {
                    $type = $setting['type'];
                }

                if (isset($setting['group'])) {
                    $group = $setting['group'];
                    $sql = "SELECT value
                            FROM $t_course
                            WHERE
                                c_id = $courseId AND
                                variable = '".Database::escape_string($group)."' AND
                                subkey = '".Database::escape_string($variable)."'
                            ";
                    $result = Database::query($sql);
                    if (!Database::num_rows($result)) {
                        $params = [
                            'c_id' => $courseId,
                            'variable' => $group,
                            'subkey' => $variable,
                            'value' => $value,
                            'category' => 'plugins',
                            'type' => $type,
                            'title' => '',
                        ];
                        Database::insert($t_course, $params);
                    }
                } else {
                    $sql = "SELECT value FROM $t_course
                            WHERE c_id = $courseId AND variable = '$variable' ";
                    $result = Database::query($sql);
                    if (!Database::num_rows($result)) {
                        $params = [
                            'c_id' => $courseId,
                            'variable' => $variable,
                            'subkey' => $plugin_name,
                            'value' => $value,
                            'category' => 'plugins',
                            'type' => $type,
                            'title' => '',
                        ];
                        Database::insert($t_course, $params);
                    }
                }
            }
        }

        // Stop here if we don't want a tool link on the course homepage
        if (!$add_tool_link || false == $this->addCourseTool) {
            return true;
        }

        // Add an icon in the table tool list
        $this->createLinkToCourseTool($plugin_name, $courseId);
    }

    /**
     * Delete the fields added to the course settings page and the link to the
     * tool on the course's homepage.
     *
     * @param int $courseId
     *
     * @return false|null
     */
    public function uninstall_course_fields($courseId)
    {
        $courseId = (int) $courseId;

        if (empty($courseId)) {
            return false;
        }
        $pluginName = $this->get_name();

        $t_course = Database::get_course_table(TABLE_COURSE_SETTING);
        $t_tool = Database::get_course_table(TABLE_TOOL_LIST);

        if (!empty($this->course_settings)) {
            foreach ($this->course_settings as $setting) {
                $variable = Database::escape_string($setting['name']);
                if (!empty($setting['group'])) {
                    $variable = Database::escape_string($setting['group']);
                }
                if (empty($variable)) {
                    continue;
                }
                $sql = "DELETE FROM $t_course
                        WHERE c_id = $courseId AND variable = '$variable'";
                Database::query($sql);
            }
        }

        $pluginName = Database::escape_string($pluginName);
        $sql = "DELETE FROM $t_tool
                WHERE c_id = $courseId AND
                (
                  title = '$pluginName' OR
                  title = '$pluginName:student' OR
                  title = '$pluginName:teacher'
                )";
        Database::query($sql);
    }

    /**
     * Install the course fields and tool link of this plugin in all courses.
     *
     * @param bool $add_tool_link Whether we want to add a plugin link on the course homepage
     */
    public function install_course_fields_in_all_courses($add_tool_link = true)
    {
        $accessUrlId = api_get_current_access_url_id();

        $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
        $tableRel    = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

        $sql = "SELECT c.id
            FROM $tableCourse c
            INNER JOIN $tableRel r ON r.c_id = c.id
            WHERE r.access_url_id = ".(int)$accessUrlId."
            ORDER BY c.id";
        $res = Database::query($sql);
        while ($row = Database::fetch_assoc($res)) {
            $this->install_course_fields((int)$row['id'], $add_tool_link);
        }
    }

    /**
     * Uninstall the plugin settings fields from all courses.
     */
    public function uninstall_course_fields_in_all_courses()
    {
        $accessUrlId = api_get_current_access_url_id();

        $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
        $tableRel    = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

        $sql = "SELECT c.id
            FROM $tableCourse c
            INNER JOIN $tableRel r ON r.c_id = c.id
            WHERE r.access_url_id = ".(int)$accessUrlId."
            ORDER BY c.id";

        $res = Database::query($sql);
        while ($row = Database::fetch_assoc($res)) {
            $this->uninstall_course_fields((int)$row['id']);
        }
    }

    /**
     * @return array
     */
    public function getCourseSettings()
    {
        $settings = [];
        if (is_array($this->course_settings)) {
            foreach ($this->course_settings as $item) {
                // Skip html type
                if ('html' === $item['type']) {
                    continue;
                }
                if (isset($item['group'])) {
                    if (!in_array($item['group'], $settings)) {
                        $settings[] = $item['group'];
                    }
                } else {
                    $settings[] = $item['name'];
                }
            }
        }

        return $settings;
    }

    /**
     * Method to be extended when changing the setting in the course
     * configuration should trigger the use of a callback method.
     *
     * @param array $values sent back from the course configuration script
     */
    public function course_settings_updated($values = [])
    {
    }

    /**
     * Add a tab to the platform.
     */
    public function addTab(string $tabName, string $url, ?string $userFilter = null): bool
    {
        $settingsManager = Container::getSettingsManager();

        $currentUrl = Container::getAccessUrlUtil()->getCurrent();

        $pluginInUrl = Container::getPluginRepository()
            ->findOneByTitle($this->get_name())
            ->getConfigurationsByAccessUrl($currentUrl)
        ;
        $pluginConf = $pluginInUrl->getConfiguration();

        $showTabs = $pluginConf['show_tabs'] ?? [];

        if (!isset($showTabs[$tabName])) {
            $showTabs[$tabName] = $url;
            $pluginConf['show_tabs'] = $showTabs;

            $pluginInUrl->setConfiguration($pluginConf);
        }

        $showTabsSetting = $settingsManager->getSetting('display.show_tabs', true);

//        $subkey = 'custom_tab_'.$tabNum;
//
//        if (!empty($userFilter)) {
//            switch ($userFilter) {
//                case self::TAB_FILTER_NO_STUDENT:
//                case self::TAB_FILTER_ONLY_STUDENT:
//                    $subkey .= $userFilter;
//                    break;
//            }
//        }

        if (!in_array($tabName, $showTabsSetting)) {
            $showTabsSetting[] = $tabName;
        }

        try {
            $settingsManager->updateSetting('display.show_tabs', $showTabsSetting);

            Container::getEntityManager()->flush();
        } catch (OptimisticLockException|ORMException) {
            return false;
        }

        return true;
    }

    /**
     * Delete a tab to chamilo's platform.
     *
     * @param string $key
     *
     * @return bool $resp Transaction response
     */
    public function deleteTab($tabName): bool
    {
        $settingsManager = Container::getSettingsManager();

        $showTabsSetting = $settingsManager->getSetting('display.show_tabs', true);

        try {
            if (in_array($tabName, $showTabsSetting)) {
                $key = array_search($tabName, $showTabsSetting, true);

                if ($key !== false) {
                    unset($showTabsSetting[$key]);

                    $showTabsSetting = array_values($showTabsSetting);
                }
            }

            $settingsManager->updateSetting('display.show_tabs', $showTabsSetting);
        } catch (OptimisticLockException|ORMException) {
            return false;
        }

        return true;
    }

    /**
     * Update the tabs attributes.
     *
     * @param string $key
     * @param array  $attributes
     *
     * @return bool
     */
    public function updateTab($key, $attributes)
    {
        $whereCondition = [
            'variable = ? AND subkey = ?' => ['show_tabs', $key],
        ];
        $resp = Database::update(TABLE_MAIN_SETTINGS, $attributes, $whereCondition);

        return $resp;
    }

    /**
     * This method shows or hides plugin's tab.
     *
     * @param bool   $showTab  Shows or hides the main menu plugin tab
     * @param string $filePath Plugin starter file path
     */
    public function manageTab($showTab, $filePath = 'index.php')
    {
        $langString = $this->get_name();
        $pluginUrl = 'plugin/'.$langString.'/'.$filePath;

        if ('true' === $showTab) {
            $tabAdded = $this->addTab($langString, $pluginUrl);
            if ($tabAdded) {
                // The page must be refreshed to show the recently created tab
                echo "<script>location.href = '".Security::remove_XSS($_SERVER['REQUEST_URI'])."';</script>";
            }
        } else {
            $this->deleteTab($langString);
        }
    }

    /**
     * @param string $variable
     *
     * @return bool
     */
    public function validateCourseSetting($variable)
    {
        return true;
    }

    /**
     * @param string $region
     *
     * @return string
     */
    public function renderRegion($region)
    {
        return '';
    }

    /**
     * Returns true if the plugin is installed, false otherwise.
     *
     * @param bool $checkEnabled Also check if enabled (instead of only installed)
     *
     * @return bool True if plugin is installed/enabled, false otherwise
     */
    public function isEnabled(bool $checkEnabled = false)
    {
        $settings = $this->get_settings();

        if (empty($settings)) {
            // plugin not installed or no configuration for current URL
            if ($checkEnabled) {
                return Container::getPluginHelper()->isPluginEnabled($this->get_name());
            }
            return false;
        }

        if ($checkEnabled) {
            // Source of truth in C2 is access_url_rel_plugin.active
            return Container::getPluginHelper()->isPluginEnabled($this->get_name());
        }

        return true;
    }

    /**
     * Allow make some actions after configure the plugin parameters
     * This function is called from main/admin/configure_plugin.php page
     * when saving the plugin parameters.
     *
     * @return \Plugin
     */
    public function performActionsAfterConfigure()
    {
        return $this;
    }

    /**
     * This function allows to change the visibility of the icon inside a course
     * :student tool will be visible only for students
     * :teacher tool will be visible only for teachers
     * If nothing it's set then tool will be visible for both as a normal icon.
     *
     * @return string
     */
    public function getToolIconVisibilityPerUserStatus()
    {
        return '';
    }

    /**
     * Default tool icon visibility.
     *
     * @return bool
     */
    public function isIconVisibleByDefault()
    {
        $v = $this->get('defaultVisibilityInCourseHomepage');

        if (null === $v) {
            return true;
        }

        return in_array($v, ['visible', 'true', true, 1, '1'], true);
    }

    /**
     * Get the admin URL for the plugin if Plugin::isAdminPlugin is true.
     *
     * @return string
     */
    public function getAdminUrl()
    {
        if (!$this->isAdminPlugin) {
            return '';
        }

        $name = $this->get_name();
        $sysPath = api_get_path(SYS_PLUGIN_PATH).$name;
        $webPath = api_get_path(WEB_PLUGIN_PATH).$name;

        if (file_exists("$sysPath/admin.php")) {
            return "$webPath/admin.php";
        }

        if (file_exists("$sysPath/start.php")) {
            return "$webPath/start.php";
        }

        return '';
    }

    /**
     * @param bool $value
     */
    public function setHasPersonalEvents($value)
    {
        $this->hasPersonalEvents = $value;
    }

    /**
     * Overwrite to perform some actions when deleting a user.
     *
     * @param int $userId
     */
    public function doWhenDeletingUser($userId)
    {
    }

    /**
     * Overwrite to perform some actions when deleting a course.
     *
     * @param int $courseId
     */
    public function doWhenDeletingCourse($courseId)
    {
    }

    /**
     * Overwrite to perform some actions when deleting a session.
     *
     * @param int $sessionId
     */
    public function doWhenDeletingSession($sessionId)
    {
    }

    /**
     * Add a link for a course tool.
     *
     * @param string      $name     The tool name
     * @param int         $courseId The course ID
     *
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function createLinkToCourseTool(string $name, int $courseId): ?CTool
    {
        if (!$this->addCourseTool) {
            return null;
        }

        $visibilityPerStatus = $this->getToolIconVisibilityPerUserStatus();
        $visibility = $this->isIconVisibleByDefault();

        $course = api_get_course_entity($courseId);
        $user = api_get_user_entity();

        $em = Database::getManager();

        $toolRepo = $em->getRepository(Tool::class);
        $cToolRepo = $em->getRepository(CTool::class);

        /** @var CTool $cTool */
        $cTool = $cToolRepo->findOneBy([
            'title' => $name.$visibilityPerStatus,
            'course' => $course,
        ]);

        if (!$cTool) {
            $tool = $toolRepo->findOneBy(['title' => $name]);

            if (!$tool) {
                $tool = new Tool();
                $tool->setTitle($name);

                $em->persist($tool);
                $em->flush();
            }

            $cTool = new CTool();
            $cTool
                ->setTool($tool)
                ->setTitle($name.$visibilityPerStatus)
                ->setParent($course)
                ->setCreator($user)
                ->addCourseLink(
                    $course,
                    null,
                    null,
                    $visibility ? ResourceLink::VISIBILITY_PUBLISHED : ResourceLink::VISIBILITY_DRAFT
                )
            ;

            $course->addTool($cTool);

            $em->persist($cTool);
            $em->flush();
        }

        return $cTool;
    }

    public function getFieldNames(): array
    {
        return array_keys($this->fields);
    }
}
