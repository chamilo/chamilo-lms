<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Component\Editor;

class Editor
{
    /**
     * Name of the instance.
     *
     * @access protected
     * @var string
     */
    public $name;

    /**
     * Name of the toolbar to load.
     *
     * @var string
     */
    public $toolbarSet;

    /**
     * Initial value.
     *
     * @var string
     */
    public $value;

    /**
     * @var array
     */
    public $config;

    /** @var \Symfony\Component\Translation\Translator */
    public $translator;

    /**
     * @param string $name
     * @param \Symfony\Component\Translation\Translator $translator
     */
    public function __construct($name, \Symfony\Component\Translation\Translator $translator)
    {
        $this->name = $name;
        $this->toolbarSet   = 'Basic';
        $this->value        = '';
        $this->config       = array();
        $this->setConfigAttribute('width', '100%');
        $this->setConfigAttribute('height', '200');
        $this->setConfigAttribute('fullPage', false);
        $this->translator = $translator;
    }

    /**
     * Return the HTML code required to run editor.
     *
     * @return string
     */
    public function createHtml()
    {
        $Html = '<textarea id="'.$this->name.'" name="'.$this->name.'" class="ckeditor" >'.$this->value.'</textarea>';
        $Html .= $this->editorReplace();

        return $Html;
    }

    /**
     * @return string
     */
    public function editorReplace()
    {
        $toolbar  = new Toolbar\Basic($this->toolbarSet);
        $toolbar->setLanguage($this->translator->getLocale());
        $config = $toolbar->getConfig();
        $javascript = $this->toJavascript($config);
        $html = "<script>
           CKEDITOR.replace('".$this->name."',
               $javascript
           );
           </script>";

        return $html;
    }


    /**
     * Converts a PHP variable into its Javascript equivalent.
     * The code of this method has been "borrowed" from the function drupal_to_js() within the Drupal CMS.
     * @param mixed $var    The variable to be converted into Javascript syntax
     * @return string        Returns a string
     * Note: This function is similar to json_encode(), in addition it produces HTML-safe strings, i.e. with <, > and & escaped.
     * @link http://drupal.org/
     */
    private function toJavascript($var)
    {
        switch (gettype($var)) {
            case 'boolean':
                return $var ? 'true' : 'false'; // Lowercase necessary!
            case 'integer':
            case 'double':
                return (string)$var;
            case 'resource':
            case 'string':
                return '"'.str_replace(
                    array("\r", "\n", "<", ">", "&"),
                    array('\r', '\n', '\x3c', '\x3e', '\x26'),
                    addslashes($var)
                ).'"';
            case 'array':
                // Arrays in JSON can't be associative. If the array is empty or if it
                // has sequential whole number keys starting with 0, it's not associative
                // so we can go ahead and convert it as an array.
                if (empty($var) || array_keys($var) === range(0, sizeof($var) - 1)) {
                    $output = array();
                    foreach ($var as $v) {
                        $output[] = $this->toJavascript($v);
                    }

                    return '[ '.implode(', ', $output).' ]';
                }
            case 'object':
                // Otherwise, fall through to convert the array as an object.
                $output = array();
                foreach ($var as $k => $v) {
                    $output[] = $this->toJavascript(strval($k)).': '.$this->toJavascript($v);
                }
                return '{ '.implode(', ', $output).' }';
            default:
                return 'null';
        }
    }

    /**
     * This method determines editor's interface language and returns it as compatible with the editor langiage code.
     * @return array
     */
    private function getEditorLanguage()
    {
        static $config;
        if (!is_array($config)) {
            $code_translation_table         = array('' => 'en', 'sr' => 'sr-latn', 'zh' => 'zh-cn', 'zh-tw' => 'zh');
            $editor_lang                    = strtolower(str_replace('_', '-', api_get_language_isocode()));
            $editor_lang                    = isset($code_translation_table[$editor_lang]) ? $code_translation_table[$editor_lang] : $editor_lang;
            $editor_lang                    = file_exists(
                api_get_path(SYS_PATH).'main/inc/lib/fckeditor/editor/lang/'.$editor_lang.'.js'
            ) ? $editor_lang : 'en';
            $config['DefaultLanguage']      = $editor_lang;
            $config['ContentLangDirection'] = api_get_text_direction($editor_lang);
        }

        return $config;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setConfigAttribute($key, $value)
    {
        $this->config[$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getConfigAttribute($key)
    {
        return isset($this->config[$key]) ? $this->config[$key] : null;
    }
}
