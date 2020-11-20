<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * HTML class for an <input type="button" /> elements
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
 * @version     CVS: $Id: button.php,v 1.6 2009/04/04 21:34:02 avb Exp $
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * HTML class for an <input type="button" /> elements
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @version     Release: 3.2.11
 * @since       1.0
 */
class HTML_QuickForm_button extends HTML_QuickForm_input
{
    private $icon;
    private $style;
    private $size;
    private $class;

    /**
     * @param string $name input name example 'submit'
     * @param string $text button text to show
     * @param string $icon icons based in font-awesome
     * @param string $style i.e default|primary|success|info|warning|danger|link
     * @param string $size large|default|small|extra-small
     * @param string $class
     * @param array $attributes
     */
    public function __construct(
        $name,
        $text,
        $icon = 'check',
        $style = 'default',
        $size = 'default',
        $class = null,
        $attributes = array()
    ) {
        $this->setIcon($icon);
        $this->setStyle($style);
        $this->setSize($size);
        $this->setClass($class);
        $columnsSize = isset($attributes['cols-size']) ? $attributes['cols-size'] : null;
        $this->setColumnsSize($columnsSize);

        parent::__construct(
            $name,
            null,
            $attributes
        );
        $this->_persistantFreeze = false;
        $this->setValue($text);
        $this->setType('submit');
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        } else {
            $value = null;
            if (isset($this->_attributes['value'])) {
                $value = $this->_attributes['value'];
                unset($this->_attributes['value']);
            }

            unset($this->_attributes['class']);

            $icon = $this->getIcon();

            if (!empty($icon)) {
                $icon = '<em class="' . $this->getIcon() . '"></em> ';
            }

            $class = $this->getClass().' '.$this->getStyle().' '.$this->getSize();

            return
                $this->_getTabs() . '
                <button class="'.$class.'" ' . $this->_getAttrString($this->_attributes) . '>'.
                $icon.
                $value.
                '</button>';
        }
    }

    /**
     * @return mixed
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * @return mixed
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param mixed $icon
     */
    public function setIcon($icon)
    {
        // Try and sanitize $icon in case it's an array (take the first element and consider it's a string)
        if (is_array($icon)) {
            $icon = @strval($icon[0]);
        }
        $this->icon = !empty($icon) ? 'fa fa-'.$icon : null;
    }

    /**
     * @return mixed
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param mixed $style
     */
    public function setStyle($style)
    {
        $style = !empty($style) ? 'btn btn-'.$style : null;
        $this->style = $style;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param mixed $size
     */
    public function setSize($size)
    {
        switch ($size) {
            case 'large':
                $size = 'btn-lg';
                break;
            case 'small':
                $size = 'btn-sm';
                break;
            case 'extra-small':
                $size = 'btn-xs';
                break;
            case 'default':
                $size = null;
                break;
        }

        $size = !empty($size) ? $size : null;
        $this->size = $size;
    }

    /**
     * Freeze the element so that only its value is returned
     *
     * @access    public
     * @return    void
     */
    public function freeze()
    {
        return false;
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

        $template = ' {element} ';

        switch ($layout) {
            case FormValidator::LAYOUT_HORIZONTAL:
                if (isset($attributes['custom']) && $attributes['custom'] == true) {
                    $template = '
                        {icon}
                        {element}
                    ';
                } else {
                    $template = '
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
                }

                break;
            case FormValidator::LAYOUT_INLINE:
            case FormValidator::LAYOUT_GRID:
            default:
                $template = '<div class="form-group"> {element}  </div>';
                break;
            case FormValidator::LAYOUT_BOX_NO_LABEL:
                break;
        }

        return $template;
    }
}
