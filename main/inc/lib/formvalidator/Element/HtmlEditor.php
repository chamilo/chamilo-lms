<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

/**
 * A html editor field to use with QuickForm.
 */
class HtmlEditor extends HTML_QuickForm_textarea
{
    /** @var \Chamilo\CoreBundle\Component\Editor\Editor */
    public $editor;

    /**
     * Full page.
     */
    public $fullPage;

    /**
     * Class Constructor.
     *
     * @param string $name
     * @param string $elementLabel HTML editor  label
     * @param array  $attributes   Attributes for the textarea
     * @param array  $config       optional configuration settings for the online editor
     */
    public function __construct(
        $name = null,
        $elementLabel = null,
        $attributes = [],
        $config = []
    ) {
        if (empty($name)) {
            throw new Exception('The html editor needs a name');
        }

        parent::__construct($name, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_type = 'html_editor';

        $editor = Container::getHtmlEditor();

        if ($editor) {
            $this->editor = $editor;
            $this->editor->setName($name);
            $this->editor->processConfig($config);
        }
    }

    /**
     * Return the HTML editor in HTML.
     *
     * @return string
     */
    public function toHtml()
    {
        if ($this->editor) {
            if ($this->editor->getConfigAttribute('fullPage')) {
                $value = $this->getValue();
                if (strlen(trim($value)) == 0) {
                    // TODO: To be considered whether here to add
                    // language and character set declarations.
                    $value = '<!DOCTYPE html><html><head><title></title></head><body></body></html>';
                    $this->setValue($value);
                }
            }
        }

        if ($this->isFrozen()) {
            return $this->getFrozenHtml();
        } else {
            $styleCss = $this->editor->getConfigAttribute('style');
            $style = false;
            if ($styleCss) {
                $style = true;
            }

            return $this->buildEditor($style);
        }
    }

    /**
     * Returns the html area content in HTML.
     *
     * @return string
     */
    public function getFrozenHtml()
    {
        return Security::remove_XSS($this->getValue());
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
            $value = $this->getCleanValue();

            $this->editor->setName($this->getName());
            if ($style === true) {
                $result = $this->editor->createHtmlStyle($value);
            } else {
                $result = $this->editor->createHtml($value);
            }
        }

        return $result;
    }
}
