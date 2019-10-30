<?php

/**
 * HTML class for a text field
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @copyright   2001-2009 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     CVS: $Id: text.php,v 1.7 2009/04/04 21:34:04 avb Exp $
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * HTML class for a text field
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @version     Release: 3.2.11
 * @since       1.0
 */
class HTML_QuickForm_text extends HTML_QuickForm_input
{
    /**
     * Class constructor
     *
     * @param string $elementName    (optional)Input field name attribute
     * @param string $elementLabel   (optional)Input field label
     * @param mixed  $attributes     (optional)Either a typical HTML attribute string
     *                                      or an associative array
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function __construct(
        $elementName = null,
        $elementLabel = null,
        $attributes = []
    ) {
        if (is_string($attributes) && empty($attributes)) {
            $attributes = [];
        }
        if (is_array($attributes) || empty($attributes)) {
            $classFromAttributes = isset($attributes['class']) ? $attributes['class'] : '';
            $attributes['class'] = $classFromAttributes.' form-control';
        }
        $inputSize = isset($attributes['input-size']) ? $attributes['input-size'] : null;
        $this->setInputSize($inputSize);
        $columnsSize = isset($attributes['cols-size']) ? $attributes['cols-size'] : null;
        $this->setColumnsSize($columnsSize);
        $icon = isset($attributes['icon']) ? $attributes['icon'] : null;
        $this->setIcon($icon);

        if (!empty($inputSize)) {
            unset($attributes['input-size']);
        }

        if (!empty($icon)) {
            unset($attributes['icon']);
        }

        parent::__construct($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->setType('text');
    }

    /**
     * Show an icon at the left side of an input
     * @return string
     */
    public function getIconToHtml()
    {
        $icon = $this->getIcon();
        $isButton = $this->getButton();

        if (empty($icon)) {
            return '';
        }
        $element = '<span class="input-group-text"><em class="fa fa-' . $icon . '"></em></span>';

        if ($isButton) {
            $element = '<button class="btn btn-outline-secondary" type="submit">
                            <em class="fa fa-' . $icon . '"></em>
                        </button>';
        }

        return '<div class="input-group-append">
                    ' . $element . '
                </div>';
    }

    /**
     * @param string $layout
     *
     * @return string
     */
    public function getTemplate($layout)
    {
        $size = $this->calculateSize();
        $attributes = $this->getAttributes();

        switch ($layout) {
            case FormValidator::LAYOUT_INLINE:
                return '                
                    <label class="sr-only"  {label-for} >
                        <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                        {label}
                    </label>                    
                    {element}
                ';
                break;
            case FormValidator::LAYOUT_HORIZONTAL:
                return '
                <div class="form-group row {error_class}">
                    <label {label-for} class="col-sm-'.$size[0].' col-form-label" >
                        <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                        {label}
                    </label>
                    <div class="col-sm-'.$size[1].'">
                        {icon}
                        {element}

                        <!-- BEGIN label_2 -->
                            <p class="help-block">{label_2}</p>
                        <!-- END label_2 -->

                        <!-- BEGIN error -->
                            <span class="help-inline help-block">{error}</span>
                        <!-- END error -->
                    </div>
                    <div class="col-sm-'.$size[2].'">
                        <!-- BEGIN label_3 -->
                            {label_3}
                        <!-- END label_3 -->
                    </div>
                </div>';
                break;
            case FormValidator::LAYOUT_BOX_NO_LABEL:
                return '
                        <label {label-for}>{label}</label>
                        <div class="input-group mb-3">
                            {icon}
                            {element}
                        </div>';
                break;
            case FormValidator::LAYOUT_BOX_SEARCH:
                return '
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label" {label-for}>{label}</label>
                            <div class="col-sm-8">
                                <div class="input-group mb-3">
                                    {element}
                                    {icon}
                                </div>
                            </div>
                            <div class="col-sm-2"></div>
                        </div>';
                break;
        }
    }

    /**
     * Sets size of text field
     *
     * @param     string    $size  Size of text field
     * @since     1.3
     * @access    public
     * @return    void
     */
    public function setSize($size)
    {
        $this->updateAttributes(array('size' => $size));
    }

    /**
     * Sets maxlength of text field
     *
     * @param     string    $maxlength  Maximum length of text field
     * @since     1.3
     * @access    public
     * @return    void
     */
    public function setMaxlength($maxlength)
    {
        $this->updateAttributes(array('maxlength' => $maxlength));
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        if ($this->isFrozen()) {
            return $this->getFrozenHtml();
        } else {
            return '<input '.$this->_getAttrString($this->_attributes).' />';
        }
    }
}
