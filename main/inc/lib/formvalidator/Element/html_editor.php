<?php
/* For licensing terms, see /license.txt */
use Chamilo\CoreBundle\Framework\Container;

/**
 * A html editor field to use with QuickForm
 */
class HTML_QuickForm_html_editor extends HTML_QuickForm_textarea
{
    /** @var \Chamilo\Component\Editor\Editor */
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

        HTML_QuickForm_element::HTML_QuickForm_element($name, $label, $attributes);
        $this->_persistantFreeze = true;
        $this->_type = 'html_editor';

        global $fck_attribute;

        $editor = Container::getHtmlEditor();
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
     * Build this element using an editor
     */
    public function buildEditor()
    {
        $result = null;
        if ($this->editor) {
            $this->editor->value = $this->getValue();
            $this->editor->setName($this->getName());
            $result = $this->editor->createHtml();
        }
        return $result;
    }
}
