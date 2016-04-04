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
    public $fullPage;

    /**
     * Class Constructor
     * @param string $name
     * @param string $elementLabel HTML editor  label
     * @param array  $attributes Attributes for the textarea
     * @param array  $config Optional configuration settings for the online editor.
     *
     */
    public function __construct(
        $name = null,
        $elementLabel = null,
        $attributes = null,
        $config = array()
    ) {
        if (empty($name)) {
            
            return false;
        }

        parent::__construct($name, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_type = 'html_editor';

        //$editor = Container::getHtmlEditor();
        $editor = new CkEditor();
        if ($editor) {
            $this->editor = $editor;
            $this->editor->setName($name);
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
                    // TODO: To be considered whether here to be added DOCTYPE,
                    // language and character set declarations.
                    $value = '<html><head><title></title></head><body></body></html>';
                    $this->setValue($value);
                }
            }
        }


        if ($this->isFrozen()) {
            return $this->getFrozenHtml();
        } else {
            $styleCss = $this->editor->getConfigAttribute('style');

            if ($styleCss) {
               $style = true;
            } else {
               $style = false;
            }

            return $this->buildEditor($style);
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
     * @param bool $style
     *
     * @return string
     */
    public function buildEditor($style = false)
    {
        $result = '';
        if ($this->editor) {
            $this->editor->value = $this->getValue();
            $this->editor->setName($this->getName());

            if ($style == true) {
                $result = $this->editor->createHtmlStyle();
            } else {
                $result = $this->editor->createHtml();
            }

        }

        return $result;
    }
}
