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
// | Author: Ron McClain <ron@humaniq.com>                                |
// +----------------------------------------------------------------------+
//
// $Id: ObjectFlexy.php 20456 2009-05-10 17:27:44Z ivantcholakov $

require_once("HTML/QuickForm/Renderer/Object.php");

/**
 * QuickForm renderer for Flexy template engine, static version.
 *
 * A static renderer for HTML_Quickform.  Makes a QuickFormFlexyObject
 * from the form content suitable for use with a Flexy template
 *
 * Usage:
 * $form =& new HTML_QuickForm('form', 'POST');
 * $template =& new HTML_Template_Flexy();
 * $renderer =& new HTML_QuickForm_Renderer_ObjectFlexy(&$template);
 * $renderer->setHtmlTemplate("html.html");
 * $renderer->setLabelTemplate("label.html");
 * $form->accept($renderer);
 * $view = new StdClass;
 * $view->form = $renderer->toObject();
 * $template->compile("mytemplate.html");
 *
 * Based on the code for HTML_QuickForm_Renderer_ArraySmarty
 *
 * @see QuickFormFlexyObject
 * @access public
 */
class HTML_QuickForm_Renderer_ObjectFlexy extends HTML_QuickForm_Renderer_Object
{
    /**
     * HTML_Template_Flexy instance
     * @var object $_flexy
     */
    var $_flexy;

    /**
     * Current element index
     * @var integer $_elementIdx
     */
    var $_elementIdx;

    /**
     * The current element index inside a group
     * @var integer $_groupElementIdx
     */
    var $_groupElementIdx = 0;

    /**
     * Name of template file for form html
     * @var string $_html
     * @see     setRequiredTemplate()
     */
    var $_html = '';

    /**
     * Name of template file for form labels
     * @var string $label
     * @see        setErrorTemplate()
     */
    var $label = '';

    /**
     * Class of the element objects, so you can add your own
     * element methods
     * @var string $_elementType
     */
    var $_elementType = 'QuickformFlexyElement';

    /**
     * Constructor
     *
     * @param $flexy object   HTML_Template_Flexy instance
     * @public
     */
    function HTML_QuickForm_Renderer_ObjectFlexy(&$flexy)
    {
        $this->HTML_QuickForm_Renderer_Object(true);
        $this->_obj = new QuickformFlexyForm();
        $this->_flexy =& $flexy;
    } // end constructor

    function renderHeader(&$header)
    {
        if($name = $header->getName()) {
            $this->_obj->header->$name = $header->toHtml();
        } else {
            $this->_obj->header[$this->_sectionCount] = $header->toHtml();
        }
        $this->_currentSection = $this->_sectionCount++;
    } // end func renderHeader

    function startGroup(&$group, $required, $error)
    {
        parent::startGroup($group, $required, $error);
        $this->_groupElementIdx = 1;
    } //end func startGroup

    /**
     * Creates an object representing an element containing
     * the key for storing this
     *
     * @access private
     * @param element object     An HTML_QuickForm_element object
     * @param required bool        Whether an element is required
     * @param error string    Error associated with the element
     * @return object
     */
    function _elementToObject(&$element, $required, $error)
    {
        $ret = parent::_elementToObject($element, $required, $error);
        if($ret->type == 'group') {
            $ret->html = $element->toHtml();
            unset($ret->elements);
        }
        if(!empty($this->_label)) {
            $this->_renderLabel($ret);
        }

        if(!empty($this->_html)) {
            $this->_renderHtml($ret);
            $ret->error = $error;
        }

        // Create an element key from the name
        if (false !== ($pos = api_strpos($ret->name, '[')) || is_object($this->_currentGroup)) {
            if (!$pos) {
                $keys = '->{\'' . str_replace(array('\\', '\''), array('\\\\', '\\\''), $ret->name) . '\'}';
            } else {
                $keys = '->{\'' . str_replace(
                            array('\\', '\'', '[', ']'), array('\\\\', '\\\'', '\'}->{\'', ''),
                            $ret->name
                        ) . '\'}';
            }
            // special handling for elements in native groups
            if (is_object($this->_currentGroup)) {
                // skip unnamed group items unless radios: no name -> no static access
                // identification: have the same key string as the parent group
                if ($this->_currentGroup->keys == $keys && 'radio' != $ret->type) {
                    return false;
                }
                // reduce string of keys by remove leading group keys
                if (0 === api_strpos($keys, $this->_currentGroup->keys)) {
                    $keys = api_substr_replace($keys, '', 0, api_strlen($this->_currentGroup->keys));
                }
            }
        } elseif (0 == api_strlen($ret->name)) {
            $keys = '->{\'element_' . $this->_elementIdx . '\'}';
        } else {
            $keys = '->{\'' . str_replace(array('\\', '\''), array('\\\\', '\\\''), $ret->name) . '\'}';
        }
        // for radios: add extra key from value
        if ('radio' == $ret->type && '[]' != api_substr($keys, -2)) {
            $keys .= '->{\'' . str_replace(array('\\', '\''), array('\\\\', '\\\''), $ret->value) . '\'}';
        }
        $ret->keys = $keys;
        $this->_elementIdx++;
        return $ret;
    }

    /**
     * Stores an object representation of an element in the
     * QuickformFormObject instance
     *
     * @access private
     * @param elObj object  Object representation of an element
     * @return void
     */
    function _storeObject($elObj)
    {
        if ($elObj) {
            $keys = $elObj->keys;
            unset($elObj->keys);
            if(is_object($this->_currentGroup) && ('group' != $elObj->type)) {
                $code = '$this->_currentGroup' . $keys . ' = $elObj;';
            } else {
                $code = '$this->_obj' . $keys . ' = $elObj;';
            }
            eval($code);
        }
    }

    /**
     * Set the filename of the template to render html elements.
     * In your template, {html} is replaced by the unmodified html.
     * If the element is required, {required} will be true.
     * Eg.
     * {if:error}
     *   <font color="red" size="1">{error:h}</font><br />
     * {end:}
     * {html:h}
     *
     * @access public
     * @param template string   Filename of template
     * @return void
     */
    function setHtmlTemplate($template)
    {
        $this->_html = $template;
    }

    /**
     * Set the filename of the template to render form labels
     * In your template, {label} is replaced by the unmodified label.
     * {error} will be set to the error, if any.  {required} will
     * be true if this is a required field
     * Eg.
     * {if:required}
     * <font color="orange" size="1">*</font>
     * {end:}
     * {label:h}
     *
     * @access public
     * @param template string   Filename of template
     * @return void
     */
    function setLabelTemplate($template)
    {
        $this->_label = $template;
    }

    function _renderLabel(&$ret)
    {
        $this->_flexy->compile($this->_label);
        $ret->label = $this->_flexy->bufferedOutputObject($ret);
    }

    function _renderHtml(&$ret)
    {
        $this->_flexy->compile($this->_html);
        $ret->html = $this->_flexy->bufferedOutputObject($ret);
    }
} // end class HTML_QuickForm_Renderer_ObjectFlexy

/**
 * Adds nothing to QuickformForm, left for backwards compatibility
 */
class QuickformFlexyForm extends QuickformForm
{
}

/**
 * Adds nothing to QuickformElement, left for backwards compatibility
 */
class QuickformFlexyElement extends QuickformElement
{
}
?>
