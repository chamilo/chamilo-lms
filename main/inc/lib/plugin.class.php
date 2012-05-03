<?php

/**
 * Base class for plugins
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class Plugin {

    protected $version = '';
    protected $author = '';
    protected $fields = array();

    protected function __construct($version, $author, $settings = array()) {
        $this->version = $version;
        $this->author = $author;
        $this->fields = $settings;

        global $language_files;
        $language_files[] = 'plugin_' . $this->get_name();
    }

    function get_info() {
        $result = array();

        $result['title'] = $this->get_title();
        $result['comment'] = $this->get_comment();
        $result['version'] = $this->get_version();
        $result['author'] = $this->get_author();
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
        $root = api_get_path(SYS_PLUGIN_PATH);
        $path = "$root/$name/resources/$name.css";
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
            $type = $type ? $type : 'text';
            if ($type == 'wysiwyg') {
                $result->add_html_editor($name, $this->get_lang($name));
            } else {
                $result->addElement($type, $name, $this->get_lang($name));
            }
        }
        $result->setDefaults($defaults);

        $result->addElement('style_submit_button', 'submit_button', $this->get_lang('Save'));
        return $result;
    }

    function get($name) {
        $content = '';
        $title = 'Static';
        $settings = $this->get_settings();
        foreach ($settings as $setting) {
            if ($setting['variable'] == ($this->get_name() . '_' . $name)) {
                return $setting['selected_value'];
            }
        }

        return false;
    }

    private $settings = null;

    public function get_settings() {
        if (is_null($this->settings)) {
            $settings = api_get_settings_params(array("subkey = ? AND category = ? AND type = ? " => array($this->get_name(), 'Plugins', 'setting')));
            $this->settings = $settings;
        }
        return $this->settings;
    }

    private $strings = null;

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
}