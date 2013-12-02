<?php
/* For licensing terms, see /license.txt */

require_once 'HTML/QuickForm/textarea.php';

/**
 * A html editor field to use with QuickForm
 */
class HTML_QuickForm_html_editor extends HTML_QuickForm_textarea
{
    public $editor;

    /**
     * Class constructor
     * @param string  HTML editor name/id
     * @param string  HTML editor  label
     * @param array  Attributes for the textarea
     * @param array $config	Optional configuration settings for the online editor.
     * @return bool
     */
    public function HTML_QuickForm_html_editor($name = null, $label = null, $attributes = null, $config = null)
    {
        if (empty($name)) {
            return false;
        }

        HTML_QuickForm_element :: HTML_QuickForm_element($name, $label, $attributes);
        $this->_persistantFreeze = true;
        $this->_type = 'html_editor';

        global $app, $fck_attribute;
        $this->editor = new ChamiloLMS\Component\Editor\Editor($name, $app['translator']);
        $this->editor->toolbarSet = $fck_attribute['ToolbarSet'];
        //We get the optionals config parameters in $fck_attribute array
        $this->editor->config = !empty($fck_attribute['Config']) ? $fck_attribute['Config'] : array();

        $width = !empty($fck_attribute['Width']) ? $fck_attribute['Width'] : '990';
        $this->editor->setConfigAttribute('width', $width);
        $height = !empty($fck_attribute['Height']) ? $fck_attribute['Height'] : '400';
        $this->editor->setConfigAttribute('height', $height);

        if (isset($fck_attribute['FullPage'])) {
            $fullPage = is_bool($config['FullPage']) ? $config['FullPage'] : ($config['FullPage'] === 'true');
            $this->editor->setConfigAttribute('fullPage', $fullPage);
        }

        // This is an alternative (a better) way to pass configuration data to the editor.
        if (is_array($config)) {
            foreach ($config as $key => $value) {
                $this->editor->setConfigAttribute($key, $value);
            }
        }
    }

    /**
     * Return the HTML editor in HTML
     * @return string
     */
    public function toHtml()
    {
        $value = $this->getValue();
        if ($this->editor->getConfigAttribute('fullPage')) {
            if (strlen(trim($value)) == 0) {
                // TODO: To be considered whether here to be added DOCTYPE, language and character set declarations.
                $value = '<html><head><title></title></head><body></body></html>';
                $this->setValue($value);
            }
        }

        if ($this->isFrozen()) {
            return $this->getFrozenHtml();
        } else {
            return $this->buildEditor();
        }
    }

    /**
     * Returns the html area content in HTML
     * @return string
     */
    public function getFrozenHtml()
    {
        return $this->getValue();
    }

    /**
     * Build this element using FCKeditor
     */
    public function buildEditor()
    {
        $this->editor->value = $this->getValue();
        $result = $this->editor->createHtml();
        return $result;
    }
}
