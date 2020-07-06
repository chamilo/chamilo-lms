<?php

/**
 * HTML class for a textarea type field
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
 * @version     CVS: $Id: textarea.php,v 1.13 2009/04/04 21:34:04 avb Exp $
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * HTML class for a textarea type field
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @version     Release: 3.2.11
 * @since       1.0
 */
class HTML_QuickForm_textarea extends HTML_QuickForm_element
{
    /**
     * Field value
     * @var       string
     * @since     1.0
     * @access    private
     */
    public $_value;

    /**
     * Class constructor
     *
     * @param string       $elementName Input field name attribute
     * @param string|array $label       Label(s) for a field
     * @param mixed        $attributes  Either a typical HTML attribute string or an associative array
     */
    public function __construct(
        $elementName = null,
        $label = null,
        $attributes = null
    ) {
        $attributes['class'] = isset($attributes['class']) ? $attributes['class'] : 'form-control';
        $columnsSize = isset($attributes['cols-size']) ? $attributes['cols-size'] : null;
        $this->setColumnsSize($columnsSize);
        parent::__construct($elementName, $label, $attributes);

        $id = $this->getAttribute('id');

        if (empty($id)) {
            $name = $this->getAttribute('name');
            $this->setAttribute('id', uniqid($name.'_'));
        }

        $this->_persistantFreeze = true;
        $this->_type = 'textarea';
        $this->_value = null;
    }

    /**
     * Sets the input field name
     *
     * @param     string    $name   Input field name attribute
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function setName($name)
    {
        $this->updateAttributes(array('name' => $name));
    }

    /**
     * Returns the element name
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    public function getName()
    {
        return $this->getAttribute('name');
    }

    /**
     * Sets value for textarea element
     *
     * @param     string    $value  Value for textarea element
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function setValue($value)
    {
        $this->_value = $value;
    }

    /**
     * Returns the value of the form element
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * Sets height in rows for textarea element
     *
     * @param     string    $rows  Height expressed in rows
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function setRows($rows)
    {
        $this->updateAttributes(array('rows' => $rows));
    }

    /**
     * Sets width in cols for textarea element
     *
     * @param     string    $cols  Width expressed in cols
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function setCols($cols)
    {
        $this->updateAttributes(array('cols' => $cols));
    }

    /**
     * Returns the textarea element in HTML
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    public function toHtml()
    {
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        } else {
            return $this->_getTabs().
                   '<textarea' . $this->_getAttrString($this->_attributes) . '>' .
                   // because we wrap the form later we don't want the text indented
                   // Modified by Ivan Tcholakov, 16-MAR-2010.
                   //preg_replace("/(\r\n|\n|\r)/", '&#010;', htmlspecialchars($this->_value)) .
                   preg_replace("/(\r\n|\n|\r)/", '&#010;', $this->getCleanValue()) .
                   //
                   '</textarea>';
        }
    }

    /**
     * Returns the value of field without HTML tags (in this case, value is changed to a mask)
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    public function getFrozenHtml()
    {
        $value = $this->getCleanValue();
        if ($this->getAttribute('wrap') == 'off') {
            $html = $this->_getTabs() . '<pre>' . $value."</pre>\n";
        } else {
            $html = nl2br($value)."\n";
        }
        return $html . $this->_getPersistantData();
    }

    /**
     * @param string $layout
     *
     * @return string
     */
    public function getTemplate($layout)
    {
        $size = $this->getColumnsSize();
        $this->removeAttribute('cols-size');

        if (empty($size)) {
            $size = [2, 8, 2];
        }

        switch ($layout) {
            case FormValidator::LAYOUT_INLINE:
                return '
                <div class="form-group {error_class}">
                    <label {label-for} >
                        <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                        {label}
                    </label>
                    {element}
                </div>';
                break;
            case FormValidator::LAYOUT_HORIZONTAL:
                return '
                <div class="form-group {error_class}">
                    <label {label-for} class="col-sm-'.$size[0].' control-label" >
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
                        <div class="input-group">

                            {icon}
                            {element}
                        </div>';
                break;
            case FormValidator::LAYOUT_GRID:
            case FormValidator::LAYOUT_BOX:
                return '
                        <label {label-for}>{label}</label>
                        <div class="input-group">
                            {label}
                            {icon}
                            {element}
                        </div>';
                break;
        }
    }
}
