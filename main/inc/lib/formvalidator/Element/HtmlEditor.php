<?php
/* For licensing terms, see /license.txt */

use \Chamilo\CoreBundle\Component\Editor\CkEditor\CkEditor;

/**
 * A html editor field to use with QuickForm
 */
class HtmlEditor extends HTML_QuickForm_textarea
{
    /** @var \Chamilo\CoreBundle\Component\Editor\Editor */
    public $editor;

    /**
     * Full page
     */
    var $fullPage;
    var $fck_editor;
    var $content;

    /**
     * Class constructor
     * @param string  HTML editor name/id
     * @param string  HTML editor  label
     * @param array  Attributes for the textarea
     * @param array $config	Optional configuration settings for the online editor.
     * @return bool
     */
    public function HtmlEditor($name = null, $elementLabel = null, $attributes = null, $config = null)
    {
        if (empty($name)) {
            return false;
        }

        HTML_QuickForm_element :: HTML_QuickForm_element($name, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_type = 'html_editor';

        global $fck_attribute;

        //$editor = Container::getHtmlEditor();
        $editor = new CkEditor();
        if ($editor) {
            $this->editor = $editor;
            $this->editor->setName($name);
            $this->editor->processConfig($fck_attribute);
            $this->editor->processConfig($config);
        }
    }

    /**
     * Return the HTML editor in HTML
     * @return string
     */
    public function toHtml()
    {
        $value = $this->getValue();
        if ($this->editor) {
            if ($this->editor->getConfigAttribute('fullPage')) {
                if (strlen(trim($value)) == 0) {
                    // TODO: To be considered whether here to be added DOCTYPE, language and character set declarations.
                    $value = '<html><head><title></title></head><body></body></html>';
                    $this->setValue($value);
                }
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
     * @return string
     */
    public function buildEditor()
    {
        $result = '';
        if ($this->editor) {
            $this->editor->value = $this->getValue();
            $this->editor->setName($this->getName());
            $result = $this->editor->createHtml();
        }

        return $result;
    }
}
