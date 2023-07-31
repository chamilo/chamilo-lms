<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CTool;

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
    public const TAB_FILTER_NO_STUDENT = '::no-student';
    public const TAB_FILTER_ONLY_STUDENT = '::only-student';
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
    }

    /**
     * Gets an array of information about this plugin (name, version, ...).
     *
     * @return array Array of information elements about this plugin
     */
    public function get_info()
    {
        $result = [];
        $result['obj'] = $this;
        $result['title'] = $this->get_title();
        $result['comment'] = $this->get_comment();
        $result['version'] = $this->get_version();
        $result['author'] = $this->get_author();
        $result['plugin_class'] = get_class($this);
        $result['is_course_plugin'] = $this->isCoursePlugin;
        $result['is_admin_plugin'] = $this->isAdminPlugin;
        $result['is_mail_plugin'] = $this->isMailPlugin;

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
     *
     * @return string
     */
    public function get_name()
    {
        $result = get_class($this);
        $result = str_replace('Plugin', '', $result);
        $result = strtolower($result);

        return $result;
    }

    /**
     * @return string
     */
    public function getCamelCaseName()
    {
        $result = get_class($this);

        return str_replace('Plugin', '', $result);
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
        $defaults = [];
        $checkboxGroup = [];
        $checkboxCollection = [];

        if ($checkboxNames = array_keys($this->fields, 'checkbox')) {
            $pluginInfoCollection = api_get_settings('Plugins');
            foreach ($pluginInfoCollection as $pluginInfo) {
                if (array_search($pluginInfo['title'], $checkboxNames) !== false) {
                    $checkboxCollection[$pluginInfo['title']] = $pluginInfo;
                }
            }
        }

        $disableSettings = $this->disableSettings();

        foreach ($this->fields as $name => $type) {
            $options = null;
            if (is_array($type) && isset($type['type']) && $type['type'] === 'select') {
                $attributes = isset($type['attributes']) ? $type['attributes'] : [];
                if (!empty($type['options']) && isset($type['translate_options']) && $type['translate_options']) {
                    foreach ($type['options'] as $key => &$optionName) {
                        $optionName = $this->get_lang($optionName);
                    }
                }
                $options = $type['options'];
                $type = $type['type'];
            }

            if (!empty($disableSettings)) {
                if (in_array($name, $disableSettings)) {
                    continue;
                }
            }

            $value = $this->get($name);
            $defaults[$name] = $value;
            $type = isset($type) ? $type : 'text';

            $help = null;
            if ($this->get_lang_plugin_exists($name.'_help')) {
                $help = $this->get_lang($name.'_help');
                if ($name === 'show_main_menu_tab') {
                    $pluginName = strtolower(str_replace('Plugin', '', get_class($this)));
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
                        if ($checkboxCollection[$name]['selected_value'] === 'true') {
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
     *
     * @param string $name of the plugin setting
     *
     * @return string Value of the plugin setting
     */
    public function get($name)
    {
        $settings = api_get_configuration_value('plugin_settings');
        if (!empty($settings) && isset($settings[$this->get_name()])) {
            $prioritySettings = $settings[$this->get_name()];
            if (!empty($prioritySettings)) {
                if (isset($prioritySettings[$name])) {
                    return $prioritySettings[$name];
                }
            }
        }

        $settings = $this->get_settings();
        foreach ($settings as $setting) {
            if ($setting['variable'] == $this->get_name().'_'.$name) {
                $unserialized = UnserializeApi::unserialize('not_allowed_classes', $setting['selected_value'], true);

                if (!empty($setting['selected_value']) &&
                    false !== $unserialized
                ) {
                    $setting['selected_value'] = $unserialized;
                }

                return $setting['selected_value'];
            }
        }

        return false;
    }

    /**
     * Returns an array with the global settings for this plugin.
     *
     * @param bool $forceFromDB Optional. Force get settings from the database
     *
     * @return array Plugin settings as an array
     */
    public function get_settings($forceFromDB = false)
    {
        if (empty($this->settings) || $forceFromDB) {
            $settings = api_get_settings_params(
                [
                    "subkey = ? AND category = ? AND type = ? AND access_url = ?" => [
                        $this->get_name(),
                        'Plugins',
                        'setting',
                        api_get_current_access_url_id(),
                    ],
                ]
            );
            $this->settings = $settings;
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
            $language_interface = api_get_interface_language();
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
    public function install_course_fields($courseId, $add_tool_link = true, $iconName = '')
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
        if (!$add_tool_link || $this->addCourseTool == false) {
            return true;
        }

        // Add an icon in the table tool list
        $this->createLinkToCourseTool($plugin_name, $courseId, $iconName);
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
                  name = '$pluginName' OR
                  name = '$pluginName:student' OR
                  name = '$pluginName:teacher'
                )";
        Database::query($sql);
    }

    /**
     * Install the course fields and tool link of this plugin in all courses.
     *
     * @param bool $add_tool_link Whether we want to add a plugin link on the course homepage
     */
    public function install_course_fields_in_all_courses($add_tool_link = true, $iconName = '')
    {
        // Update existing courses to add plugin settings
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT id FROM $table ORDER BY id";
        $res = Database::query($sql);
        while ($row = Database::fetch_assoc($res)) {
            $this->install_course_fields($row['id'], $add_tool_link, $iconName);
        }
    }

    /**
     * Uninstall the plugin settings fields from all courses.
     */
    public function uninstall_course_fields_in_all_courses()
    {
        // Update existing courses to add conference settings
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT id FROM $table
                ORDER BY id";
        $res = Database::query($sql);
        while ($row = Database::fetch_assoc($res)) {
            $this->uninstall_course_fields($row['id']);
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
     * Add a tab to platform.
     *
     * @param string $tabName
     * @param string $url
     * @param string $userFilter Optional. Filter tab type
     *
     * @return false|string
     */
    public function addTab($tabName, $url, $userFilter = null)
    {
        $sql = "SELECT * FROM settings_current
                WHERE
                    variable = 'show_tabs' AND
                    subkey LIKE 'custom_tab_%'";
        $result = Database::query($sql);
        $customTabsNum = Database::num_rows($result);

        $tabNum = $customTabsNum + 1;

        // Avoid Tab Name Spaces
        $tabNameNoSpaces = preg_replace('/\s+/', '', $tabName);
        $subkeytext = "Tabs".$tabNameNoSpaces;

        // Check if it is already added
        $checkCondition = [
            'where' => [
                    "variable = 'show_tabs' AND subkeytext = ?" => [
                        $subkeytext,
                    ],
                ],
        ];

        $checkDuplicate = Database::select('*', 'settings_current', $checkCondition);
        if (!empty($checkDuplicate)) {
            return false;
        }

        // End Check
        $subkey = 'custom_tab_'.$tabNum;

        if (!empty($userFilter)) {
            switch ($userFilter) {
                case self::TAB_FILTER_NO_STUDENT:
                case self::TAB_FILTER_ONLY_STUDENT:
                    $subkey .= $userFilter;
                    break;
            }
        }

        $currentUrlId = api_get_current_access_url_id();
        $attributes = [
            'variable' => 'show_tabs',
            'subkey' => $subkey,
            'type' => 'checkbox',
            'category' => 'Platform',
            'selected_value' => 'true',
            'title' => $tabName,
            'comment' => $url,
            'subkeytext' => $subkeytext,
            'access_url' => $currentUrlId,
            'access_url_changeable' => 1,
            'access_url_locked' => 0,
        ];
        $resp = Database::insert('settings_current', $attributes);

        // Save the id
        $settings = $this->get_settings();
        $setData = [
            'comment' => $subkey,
        ];
        $whereCondition = [
            'id = ?' => key($settings),
        ];
        Database::update('settings_current', $setData, $whereCondition);

        return $resp;
    }

    /**
     * Delete a tab to chamilo's platform.
     *
     * @param string $key
     *
     * @return bool $resp Transaction response
     */
    public function deleteTab($key)
    {
        $table = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        $sql = "SELECT *
                FROM $table
                WHERE variable = 'show_tabs'
                AND subkey <> '$key'
                AND subkey like 'custom_tab_%'
                ";
        $resp = $result = Database::query($sql);
        $customTabsNum = Database::num_rows($result);

        if (!empty($key)) {
            $whereCondition = [
                'variable = ? AND subkey = ?' => ['show_tabs', $key],
            ];
            $resp = Database::delete('settings_current', $whereCondition);

            //if there is more than one tab
            //re enumerate them
            if (!empty($customTabsNum) && $customTabsNum > 0) {
                $tabs = Database::store_result($result, 'ASSOC');
                $i = 1;
                foreach ($tabs as $row) {
                    $newSubKey = "custom_tab_$i";

                    if (strpos($row['subkey'], self::TAB_FILTER_NO_STUDENT) !== false) {
                        $newSubKey .= self::TAB_FILTER_NO_STUDENT;
                    } elseif (strpos($row['subkey'], self::TAB_FILTER_ONLY_STUDENT) !== false) {
                        $newSubKey .= self::TAB_FILTER_ONLY_STUDENT;
                    }

                    $attributes = ['subkey' => $newSubKey];
                    $this->updateTab($row['subkey'], $attributes);
                    $i++;
                }
            }
        }

        return $resp;
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
        $resp = Database::update('settings_current', $attributes, $whereCondition);

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
        $langString = str_replace('Plugin', '', get_class($this));
        $pluginName = strtolower($langString);
        $pluginUrl = 'plugin/'.$pluginName.'/'.$filePath;

        if ($showTab === 'true') {
            $tabAdded = $this->addTab($langString, $pluginUrl);
            if ($tabAdded) {
                // The page must be refreshed to show the recently created tab
                echo "<script>location.href = '".Security::remove_XSS($_SERVER['REQUEST_URI'])."';</script>";
            }
        } else {
            $settingsCurrentTable = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
            $conditions = [
                'where' => [
                    "variable = 'show_tabs' AND title = ? AND comment = ? " => [
                        $langString,
                        $pluginUrl,
                    ],
                ],
            ];
            $result = Database::select('subkey', $settingsCurrentTable, $conditions);
            if (!empty($result)) {
                $this->deleteTab($result[0]['subkey']);
            }
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
    public function isEnabled(bool $checkEnabled = false): bool
    {
        $settings = api_get_settings_params_simple(
            [
                "subkey = ? AND category = ? AND type = ? AND variable = 'status' " => [
                    $this->get_name(),
                    'Plugins',
                    'setting',
                ],
            ]
        );
        if (is_array($settings) && isset($settings['selected_value']) && $settings['selected_value'] == 'installed') {
            // The plugin is installed
            // If we need a check on whether it is enabled, also check for
            // *plugin*_tool_enable and make sure it is *NOT* false
            if ($checkEnabled) {
                $enabled = api_get_settings_params_simple(
                    [
                        "variable = ? AND subkey = ? AND category = 'Plugins' " => [
                            $this->get_name().'_tool_enable',
                            $this->get_name(),
                        ],
                    ]
                );
                if (is_array($enabled) && isset($enabled['selected_value']) && $enabled['selected_value'] == 'false') {
                    // Only return false if the setting exists and it is
                    // *specifically* set to false
                    return false;
                }
            }

            return true;
        }

        return false;
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
        return true;
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
     * Disable the settings configured in configuration.php ($configuration[plugin_settings]).
     */
    public function disableSettings()
    {
        $settings = api_get_configuration_value('plugin_settings');
        if (!empty($settings) && isset($settings[$this->get_name()])) {
            return array_keys($settings[$this->get_name()]);
        }

        return [];
    }

    /**
     * Add an link for a course tool.
     *
     * @param string $name     The tool name
     * @param int    $courseId The course ID
     * @param string $iconName Optional. Icon file name
     * @param string $link     Optional. Link URL
     *
     * @return CTool|null
     */
    protected function createLinkToCourseTool(
        $name,
        $courseId,
        $iconName = null,
        $link = null,
        $sessionId = 0,
        $category = 'plugin'
    ) {
        if (!$this->addCourseTool) {
            return null;
        }

        $visibilityPerStatus = $this->getToolIconVisibilityPerUserStatus();
        $visibility = $this->isIconVisibleByDefault();

        $em = Database::getManager();

        /** @var CTool $tool */
        $tool = $em
            ->getRepository('ChamiloCourseBundle:CTool')
            ->findOneBy([
                'name' => $name,
                'cId' => $courseId,
                'category' => $category,
            ]);

        if (!$tool) {
            $pluginName = $this->get_name();

            $tool = new CTool();
            $tool
                ->setCId($courseId)
                ->setName($name.$visibilityPerStatus)
                ->setLink($link ?: "$pluginName/start.php")
                ->setImage($iconName ?: "$pluginName.png")
                ->setVisibility($visibility)
                ->setAdmin(0)
                ->setAddress('squaregrey.gif')
                ->setAddedTool(false)
                ->setTarget('_self')
                ->setCategory($category)
                ->setSessionId($sessionId);

            $em->persist($tool);
            $em->flush();

            $tool->setId(
                $tool->getIid()
            );

            $em->persist($tool);
            $em->flush();
        }

        return $tool;
    }
}
