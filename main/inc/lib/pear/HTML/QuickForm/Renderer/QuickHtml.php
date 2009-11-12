<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Jason Rust <jrust@rustyparts.com>                           |
// +----------------------------------------------------------------------+
//
// $Id: QuickHtml.php 6184 2005-09-07 10:08:17Z bmol $

require_once('HTML/QuickForm/Renderer/Default.php');

/**
 * A renderer that makes it quick and easy to create customized forms.
 *
 * This renderer has three main distinctives: an easy way to create
 * custom-looking forms, the ability to separate the creation of form
 * elements from their display, and being able to use QuickForm in
 * widget-based template systems.  See the online docs for more info.
 * For a usage example see: docs/renderers/QuickHtml_example.php
 *
 * @access public
 * @package QuickForm
 */
class HTML_QuickForm_Renderer_QuickHtml extends HTML_QuickForm_Renderer_Default {
    // {{{ properties

    /**
     * The array of rendered elements
     * @var array
     */
    var $renderedElements = array();

    // }}}
    // {{{ constructor

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    function HTML_QuickForm_Renderer_QuickHtml()
    {
        $this->HTML_QuickForm_Renderer_Default();
        // The default templates aren't used for this renderer
        $this->clearAllTemplates();
    } // end constructor

    // }}}
    // {{{ toHtml()

    /**
     * returns the HTML generated for the form
     *
     * @param string $data (optional) Any extra data to put before the end of the form
     *
     * @access public
     * @return string
     */
    function toHtml($data = '')
    {
        // Render any elements that haven't been rendered explicitly by elementToHtml()
        foreach (array_keys($this->renderedElements) as $key) {
            if (!$this->renderedElements[$key]['rendered']) {
                $this->renderedElements[$key]['rendered'] = true;
                $data .= $this->renderedElements[$key]['html'] . "\n";
            }
        }

        // Insert the extra data and form elements at the end of the form
        $this->_html = str_replace('</form>', $data . "\n</form>", $this->_html);
        return $this->_html;
    } // end func toHtml

    // }}}
    // {{{ elementToHtml()

    /**
     * Gets the html for an element and marks it as rendered.
     *
     * @param string $elementName The element name
     * @param string $elementValue (optional) The value of the element.  This is only useful
     *               for elements that have the same name (i.e. radio and checkbox), but
     *               different values
     *
     * @access public
     * @return string The html for the QuickForm element
     */
    function elementToHtml($elementName, $elementValue = null)
    {
        $elementKey = null;
        // Find the key for the element
        foreach ($this->renderedElements as $key => $data) {
            if ($data['name'] == $elementName &&
                // See if the value must match as well
                (is_null($elementValue) ||
                 $data['value'] == $elementValue)) {
                $elementKey = $key;
                break;
            }
        }

        if (is_null($elementKey)) {
            $msg = is_null($elementValue) ? "Element $elementName does not exist." :
                "Element $elementName with value of $elementValue does not exist.";
            return PEAR::raiseError(null, QUICKFORM_UNREGISTERED_ELEMENT, null, E_USER_WARNING, $msg, 'HTML_QuickForm_Error', true);
        } else {
            if ($this->renderedElements[$elementKey]['rendered']) {
                $msg = is_null($elementValue) ? "Element $elementName has already been rendered." :
                    "Element $elementName with value of $elementValue has already been rendered.";
                return PEAR::raiseError(null, QUICKFORM_ERROR, null, E_USER_WARNING, $msg, 'HTML_QuickForm_Error', true);
            } else {
                $this->renderedElements[$elementKey]['rendered'] = true;
                return $this->renderedElements[$elementKey]['html'];
            }
        }
    } // end func elementToHtml

    // }}}
    // {{{ renderElement()

    /**
     * Gets the html for an element and adds it to the array by calling
     * parent::renderElement()
     *
     * @param object     An HTML_QuickForm_element object
     * @param bool       Whether an element is required
     * @param string     An error message associated with an element
     *
     * @access public
     * @return mixed HTML string of element if $immediateRender is set, else we just add the
     *               html to the global _html string
     */
    function renderElement(&$element, $required, $error)
    {
        $this->_html = '';
        parent::renderElement($element, $required, $error);
        if (!$this->_inGroup) {
            $this->renderedElements[] = array(
                    'name' => $element->getName(),
                    'value' => $element->getValue(),
                    'html' => $this->_html,
                    'rendered' => false);
        }
        $this->_html = '';
    } // end func renderElement

    // }}}
    // {{{ renderHidden()

    /**
     * Gets the html for a hidden element and adds it to the array.
     *
     * @param object     An HTML_QuickForm_hidden object being visited
     * @access public
     * @return void
     */
    function renderHidden(&$element)
    {
        $this->renderedElements[] = array(
                'name' => $element->getName(),
                'value' => $element->getValue(),
                'html' => $element->toHtml(),
                'rendered' => false);
    } // end func renderHidden

    // }}}
    // {{{ finishGroup()

    /**
     * Gets the html for the group element and adds it to the array by calling
     * parent::finishGroup()
     *
     * @param    object      An HTML_QuickForm_group object being visited
     * @access   public
     * @return   void
     */
    function finishGroup(&$group)
    {
        $this->_html = '';
        parent::finishGroup($group);
        $this->renderedElements[] = array(
                'name' => $group->getName(),
                'value' => $group->getValue(),
                'html' => $this->_html,
                'rendered' => false);
        $this->_html = '';
    } // end func finishGroup

    // }}}
} // end class HTML_QuickForm_Renderer_QuickHtml
?>
