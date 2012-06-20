<?php
/* For licensing terms, see /license.txt */

/**
 * Base class for plugins
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 * @author Julio Montoya <gugli100@gmail.com> added course settings support + lang variable fixes
 *
 */
class Plugin {

    protected $version = '';
    protected $author = '';
    protected $fields = array();

    private $settings = null;
    private $strings = null; //translation strings

    /**
     * When creating a new course, these settings are added to the course, in 
     * the course_info/infocours.php
     * To show the plugin course icons you need to add these icons:
     * main/img/icons/22/plugin_name.png
     * main/img/icons/64/plugin_name.png
     * main/img/icons/64/plugin_name_na.png
     * @example
     * $course_settings = array(
                    array('name' => 'big_blue_button_welcome_message',  'type' => 'text'),
                    array('name' => 'big_blue_button_record_and_store', 'type' => 'checkbox')
        );
     */
    public  $course_settings = array();

    protected function __construct($version, $author, $settings = array()) {
        $this->version = $version;
        $this->author = $author;
        $this->fields = $settings;

        global $language_files;
        $language_files[] = 'plugin_' . $this->get_name();
    }

    function get_info() {
        $result = array();

        $result['title']        = $this->get_title();
        $result['comment']      = $this->get_comment();
        $result['version']      = $this->get_version();
        $result['author']       = $this->get_author();
        $result['plugin_class'] = get_class($this);

        if ($form = $this->get_settings_form()) {
            $result['settings_form'] = $form;
            foreach ($this->fields as $name => $type) {
                $value = $this->get($name);
                $result[$name] = $value;
            }
        }
        return $result;
    }

    function get_name() {
        $result = get_class($this);
        $result = str_replace('Plugin', '', $result);
        $result = strtolower($result);
        return $result;
    }

    function get_title() {
        return $this->get_lang('plugin_title');
    }

    function get_comment() {
        return $this->get_lang('plugin_comment');
    }

    function get_version() {
        return $this->version;
    }

    function get_author() {
        return $this->author;
    }

    function get_css() {
        $name = $this->get_name();
        $path = api_get_path(SYS_PLUGIN_PATH)."$name/resources/$name.css";
        if (!is_readable($path)) {
            return '';
        }
        $css = array();
        $css[] = file_get_contents($path);
        $result = implode($css);
        return $result;
    }

    /**
     *
     * @return FormValidator
     */
    function get_settings_form() {
        $result = new FormValidator($this->get_name());

        $defaults = array();
        foreach ($this->fields as $name => $type) {
            $value = $this->get($name);

            $defaults[$name] = $value;
            $type = isset($type) ? $type : 'text';

            $help = null;
            if ($this->get_lang_plugin_exists($name.'_help')) {
                $help = $this->get_lang($name.'_help');
            }

            switch ($type) {
                case 'html':
                    $result->addElement('html', $this->get_lang($name));
                    break;
                case 'wysiwyg':
                    $result->add_html_editor($name, $this->get_lang($name));
                    break;
                case 'text':
                    $result->addElement($type, $name, array($this->get_lang($name), $help));
                    break;
                case 'boolean':
                    $group = array();
                    $group[] = $result->createElement('radio', $name, '', get_lang('Yes'), 'true');
                    $group[] = $result->createElement('radio', $name, '', get_lang('No'),  'false');
                    $result->addGroup($group, null, array($this->get_lang($name), $help));
                    break;
            }
        }
        $result->setDefaults($defaults);
        $result->addElement('style_submit_button', 'submit_button', $this->get_lang('Save'));
        return $result;
    }

    function get($name) {
        $settings = $this->get_settings();
        foreach ($settings as $setting) {
            if ($setting['variable'] == ($this->get_name() . '_' . $name)) {
                return $setting['selected_value'];
            }
        }
        return false;
    }

    public function get_settings() {
        if (is_null($this->settings)) {
            $settings = api_get_settings_params(array("subkey = ? AND category = ? AND type = ? " => array($this->get_name(), 'Plugins', 'setting')));
            $this->settings = $settings;
        }
        return $this->settings;
    }

    public function get_lang_plugin_exists($name) {
        return isset($this->strings[$name]);
    }

    public function get_lang($name) {
        if (is_null($this->strings)) {
            global $language_interface;
            $root = api_get_path(SYS_PLUGIN_PATH);
            $plugin_name = $this->get_name();

            //1. Loading english if exists
            $english_path = $root.$plugin_name."/lang/english.php";
            if (is_readable($english_path)) {
                include $english_path;
                $this->strings = $strings;
            }

            $path = $root.$plugin_name."/lang/$language_interface.php";
            //2. Loading the system language
            if (is_readable($path)) {
                include $path;
                if (!empty($strings)) {
                    foreach ($strings as $key => $string) {
                        $this->strings[$key] = $string;
                    }
                }
            } else {
                $this->strings = array();
            }
        }

        if (isset($this->strings[$name])) {
            return $this->strings[$name];
        }
        return get_lang($name);
    }

    function course_install($course_id) {
        $this->install_course_fields($course_id);
    }


    /* Add course settings and add a tool link */
    public function install_course_fields($course_id) {
        $plugin_name = $this->get_name();
        $t_course = Database::get_course_table(TABLE_COURSE_SETTING);

        $course_id = intval($course_id);
        if (empty($course_id)) {
            return false;
        }
        //Ads course settings
        if (!empty($this->course_settings)) {
            foreach ($this->course_settings as $setting) {
                $variable = Database::escape_string($setting['name']);
                $sql = "SELECT value FROM $t_course WHERE c_id = $course_id AND variable = '$variable' ";
                $result = Database::query($sql);
                if (!Database::num_rows($result)) {
                    $sql_course = "INSERT INTO $t_course (c_id, variable, value, category, subkey) VALUES ($course_id, '$variable','', 'plugins', '$plugin_name')";
                    $r = Database::query($sql_course);
                }
            }
        }

        //Add an icon in the table tool list
        $t_tool = Database::get_course_table(TABLE_TOOL_LIST);
        $sql = "SELECT name FROM $t_tool WHERE c_id = $course_id AND name = '$plugin_name' ";
        $result = Database::query($sql);
        if (!Database::num_rows($result)) {
            $tool_link = "$plugin_name/start.php";
            $visibility = string2binary(api_get_setting('course_create_active_tools', $plugin_name));
            $sql_course = "INSERT INTO $t_tool VALUES ($course_id, NULL, '$plugin_name', '$tool_link', '$plugin_name.png',' ".$visibility."','0', 'squaregrey.gif','NO','_self','plugin','0')";
            $r = Database::query($sql_course);
        }
    }

    public function uninstall_course_fields($course_id) {
        $course_id = intval($course_id);
        if (empty($course_id)) {
            return false;
        }
        $plugin_name = $this->get_name();

        $t_course = Database::get_course_table(TABLE_COURSE_SETTING);
        $t_tool = Database::get_course_table(TABLE_TOOL_LIST);

        if (!empty($this->course_settings)) {
            foreach ($this->course_settings as $setting) {
                $variable = Database::escape_string($setting['name']);
                $sql_course = "DELETE FROM $t_course WHERE c_id = $course_id AND variable = '$variable'";
                Database::query($sql_course);
            }
        }

        $sql_course = "DELETE FROM $t_tool WHERE  c_id = $course_id AND name = '$plugin_name'";
        Database::query($sql_course);
    }

    function install_course_fields_in_all_courses() {
        // Update existing courses to add conference settings
        $t_courses = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT id, code FROM $t_courses ORDER BY id";
        $res = Database::query($sql);
        while ($row = Database::fetch_assoc($res)) {
            $this->install_course_fields($row['id']);
        }
    }

    function uninstall_course_fields_in_all_courses() {
        // Update existing courses to add conference settings
        $t_courses = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT id, code FROM $t_courses ORDER BY id";
        $res = Database::query($sql);
        while ($row = Database::fetch_assoc($res)) {
            $this->uninstall_course_fields($row['id']);
        }
    }
}
