<?php

/**
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
 * @link        http://pear.php.net/package/HTML_QuickForm *
 * @version     Release: 3.2.11
 * @since       1.0
 */
class HTML_QuickForm_text extends HTML_QuickForm_input
{
    /**
     * @param string $elementName    (optional)Input field name attribute
     * @param string $elementLabel   (optional)Input field label
     * @param mixed  $attributes     (optional)Either a typical HTML attribute string
     *                                      or an associative array
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function __construct($elementName = null, $elementLabel = null, $attributes = [])
    {
        if (is_string($attributes) && empty($attributes)) {
            $attributes = [];
        }

        if (is_array($attributes) || empty($attributes)) {
            $classFromAttributes = $attributes['class'] ?? '';

            $attributes['class'] = $classFromAttributes;
        }

        $inputSize = $attributes['input-size'] ?? null;
        $this->setInputSize($inputSize);
        $columnsSize = $attributes['cols-size'] ?? null;
        $this->setColumnsSize($columnsSize);
        $icon = $attributes['icon'] ?? null;
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

        if (empty($icon)) {
            return '';
        }

        return '<div class="input-group-addon">
                <em class="fa fa-'.$icon.'"></em>
                </div>';
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

    public function toHtml()
    {
        if ($this->isFrozen()) {
            return $this->getFrozenHtml();
        }
        $layout = $this->getLayout();
        $class = '';
        if (FormValidator::LAYOUT_HORIZONTAL === $layout) {
            $class = 'w-full mt-1';
        }
        $extraClass = "
            sm:text-sm
            text-gray-600
            bg-white
            font-normal
            h-10
            $class
        ";
        if (isset($this->_attributes['class'])) {
            $this->_attributes['class'] .= $extraClass;
        } else {
            $this->_attributes['class'] = $extraClass;
        }

        return '<input '.$this->_getAttrString($this->_attributes).' />';
    }
}
