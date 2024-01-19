<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Editor\Editor;
use Chamilo\CoreBundle\Framework\Container;

/**
 * A html editor field to use with QuickForm.
 */
class HtmlEditor extends HTML_QuickForm_textarea
{
    /** @var Editor */
    public $editor;

    /**
     * Full page.
     */
    public $fullPage;

    /**
     * @param string       $name
     * @param string|array $label      HTML editor  label
     * @param array        $attributes Attributes for the textarea
     * @param array        $config     optional configuration settings for the online editor
     */
    public function __construct(
        $name,
        $label = null,
        $attributes = [],
        $config = []
    ) {
        if (empty($name)) {
            throw new \Exception('Name is required');
        }

        parent::__construct($name, $label, $attributes);
        $id = $this->getAttribute('id');
        $this->_persistantFreeze = true;
        $this->_type = 'html_editor';

        $editor = Container::getHtmlEditor();
        if ($editor) {
            $this->editor = $editor;
            $this->editor->setTextareaId($id);
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
                if (0 == strlen(trim($value))) {
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
     */
    public function getFrozenHtml(): string
    {
        return Security::remove_XSS($this->getValue());
    }

    public function buildEditor(bool $style = false): string
    {
        $result = '';
        if ($this->editor) {
            $value = $this->getCleanValue();
            $this->editor->setName($this->getName());
            $this->editor->setTextareaId($this->getAttribute('id'));
            if ($style) {
                $result = $this->editor->createHtmlStyle($value);
            } else {
                $result = $this->editor->createHtml($value);
            }
        }

        return $result;
    }

    public function getTemplate(string $layout): string
    {
        if (FormValidator::LAYOUT_HORIZONTAL === $layout) {
            return '
                <div class="field">
                    <div class="p-float-label">
                        <div class="html-editor-container">
                            {element}
                            {icon}
                        </div>
                        <label {label-for}>
                            <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                            {label}
                        </label>
                    </div>
                    <!-- BEGIN label_2 -->
                        <small>{label_2}</small>
                    <!-- END label_2 -->

                     <!-- BEGIN label_3 -->
                        <small>{label_3}</small>
                    <!-- END label_3 -->

                    <!-- BEGIN error -->
                        <small class="p-error">{error}</small>
                    <!-- END error -->
                </div>';
        }

        return parent::getTemplate($layout);
    }
}
