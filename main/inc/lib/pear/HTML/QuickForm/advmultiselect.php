<?php
/**
 * Copyright (c) 2005-2009, Laurent Laville <pear@laurent-laville.org>
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the authors nor the names of its contributors
 *       may be used to endorse or promote products derived from this software
 *       without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * PHP versions 4 and 5
 *
 * @category  HTML
 * @package   HTML_QuickForm_advmultiselect
 * @author    Laurent Laville <pear@laurent-laville.org>
 * @copyright 2005-2009 Laurent Laville
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version   CVS: $Id: advmultiselect.php,v 1.36 2009/04/05 07:03:39 farell Exp $
 * @link      http://pear.php.net/package/HTML_QuickForm_advmultiselect
 * @since     File available since Release 0.4.0
 */
/**
 * Code
 */
require_once 'HTML/QuickForm/select.php';

/**
 * Basic error codes
 *
 * @var        integer
 * @since      1.5.0
 */
define('HTML_QUICKFORM_ADVMULTISELECT_ERROR_INVALID_INPUT', 1);

/**
 * Element for HTML_QuickForm that emulate a multi-select.
 *
 * The HTML_QuickForm_advmultiselect package adds an element to the
 * HTML_QuickForm package that is two select boxes next to each other
 * emulating a multi-select.
 *
 * @category  HTML
 * @package   HTML_QuickForm_advmultiselect
 * @author    Laurent Laville <pear@laurent-laville.org>
 * @copyright 2005-2009 Laurent Laville
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/HTML_QuickForm_advmultiselect
 * @since     Class available since Release 0.4.0
 */
class HTML_QuickForm_advmultiselect extends HTML_QuickForm_select
{
    /**
     * Prefix function name in javascript move selections
     *
     * @var        string
     * @access     private
     * @since      0.4.0
     */
    var $_jsPrefix;

    /**
     * Postfix function name in javascript move selections
     *
     * @var        string
     * @access     private
     * @since      0.4.0
     */
    var $_jsPostfix;

    /**
     * Associative array of the multi select container attributes
     *
     * @var        array
     * @access     private
     * @since      0.4.0
     */
    var $_tableAttributes;

    /**
     * Associative array of the add button attributes
     *
     * @var        array
     * @access     private
     * @since      0.4.0
     */
    var $_addButtonAttributes;

    /**
     * Associative array of the remove button attributes
     *
     * @var        array
     * @access     private
     * @since      0.4.0
     */
    var $_removeButtonAttributes;

    /**
     * Associative array of the select all button attributes
     *
     * @var        array
     * @access     private
     * @since      1.1.0
     */
    var $_allButtonAttributes;

    /**
     * Associative array of the select none button attributes
     *
     * @var        array
     * @access     private
     * @since      1.1.0
     */
    var $_noneButtonAttributes;

    /**
     * Associative array of the toggle selection button attributes
     *
     * @var        array
     * @access     private
     * @since      1.1.0
     */
    var $_toggleButtonAttributes;

    /**
     * Associative array of the move up button attributes
     *
     * @var        array
     * @access     private
     * @since      0.5.0
     */
    var $_upButtonAttributes;

    /**
     * Associative array of the move up button attributes
     *
     * @var        array
     * @access     private
     * @since      0.5.0
     */
    var $_downButtonAttributes;

    /**
     * Associative array of the move top button attributes
     *
     * @var        array
     * @access     private
     * @since      1.5.0
     */
    var $_topButtonAttributes;

    /**
     * Associative array of the move bottom button attributes
     *
     * @var        array
     * @access     private
     * @since      1.5.0
     */
    var $_bottomButtonAttributes;

    /**
     * Defines if both list (unselected, selected) will have their elements be
     * arranged from lowest to highest (or reverse)
     * depending on comparaison function.
     *
     * SORT_ASC  is used to sort in ascending order
     * SORT_DESC is used to sort in descending order
     *
     * @var    string    ('none' == false, 'asc' == SORT_ASC, 'desc' == SORT_DESC)
     * @access private
     * @since  0.5.0
     */
    var $_sort;

    /**
     * Associative array of the unselected item box attributes
     *
     * @var        array
     * @access     private
     * @since      0.4.0
     */
    var $_attributesUnselected;

    /**
     * Associative array of the selected item box attributes
     *
     * @var        array
     * @access     private
     * @since      0.4.0
     */
    var $_attributesSelected;

    /**
     * Associative array of the internal hidden box attributes
     *
     * @var        array
     * @access     private
     * @since      0.4.0
     */
    var $_attributesHidden;

    /**
     * Default Element template string
     *
     * @var        string
     * @access     private
     * @since      0.4.0
     */
    var $_elementTemplate;

    /**
     * Default Element stylesheet string
     *
     * @var        string
     * @access     private
     * @since      0.4.0
     */
    var $_elementCSS = '
#qfams_{id} {
  font: 13.3px sans-serif;
  background-color: #fff;
  overflow: auto;
  height: 14.3em;
  width: 12em;
  border-left:   1px solid #404040;
  border-top:    1px solid #404040;
  border-bottom: 1px solid #d4d0c8;
  border-right:  1px solid #d4d0c8;
}
#qfams_{id} label {
  padding-right: 3px;
  display: block;
}
';

    /**
     * Class constructor
     *
     * Class constructors :
     * Zend Engine 1 uses HTML_QuickForm_advmultiselect, while
     * Zend Engine 2 uses __construct
     *
     * @param string  $elementName  Dual Select name attribute
     * @param mixed   $elementLabel Label(s) for the select boxes
     * @param mixed   $options      Data to be used to populate options
     * @param mixed   $attributes   Either a typical HTML attribute string or
     *                              an associative array
     * @param integer $sort         Either SORT_ASC for auto ascending arrange,
     *                                     SORT_DESC for auto descending arrange, or
     *                                     NULL for no sort (append at end: default)
     *
     * @access     public
     * @return     void
     * @since      version 0.4.0 (2005-06-25)
     */
    function HTML_QuickForm_advmultiselect($elementName = null, $elementLabel = null,
                                           $options = null, $attributes = null,
                                           $sort = null)
    {
        $opts    = $options;
        $options = null;  // prevent to use the default select element load options
        $this->HTML_QuickForm_select($elementName, $elementLabel,
            $options, $attributes);

        // allow to load options at once and take care of fancy attributes
        $this->load($opts);

        // add multiple selection attribute by default if missing
        $this->updateAttributes(array('multiple' => 'multiple'));

        if (is_null($this->getAttribute('size'))) {
            // default size is ten item on each select box (left and right)
            $this->updateAttributes(array('size' => 10));
        }
        if (is_null($this->getAttribute('style'))) {
            // default width of each select box
            $this->updateAttributes(array('style' => 'width:100px;'));
        }
        $this->_tableAttributes = $this->getAttribute('class');
        if (is_null($this->_tableAttributes)) {
            // default table layout
            $attr = array('border' => '0',
                          'cellpadding' => '10', 'cellspacing' => '0');
        } else {
            $attr = array('class' => $this->_tableAttributes);
            $this->_removeAttr('class', $this->_attributes);
        }
        $this->_tableAttributes = $this->_getAttrString($attr);

        // set default add button attributes
        $this->setButtonAttributes('add');
        // set default remove button attributes
        $this->setButtonAttributes('remove');
        // set default selectall button attributes
        $this->setButtonAttributes('all');
        // set default selectnone button attributes
        $this->setButtonAttributes('none');
        // set default toggle selection button attributes
        $this->setButtonAttributes('toggle');
        // set default move up button attributes
        $this->setButtonAttributes('moveup');
        // set default move up button attributes
        $this->setButtonAttributes('movedown');
        // set default move top button attributes
        $this->setButtonAttributes('movetop');
        // set default move bottom button attributes
        $this->setButtonAttributes('movebottom');
        // defines javascript functions names
        $this->_jsPrefix  = 'QFAMS.';
        $this->_jsPostfix = 'moveSelection';

        // set select boxes sort order (none by default)
        if (!isset($sort)) {
            $sort = false;
        }
        if ($sort === SORT_ASC) {
            $this->_sort = 'asc';
        } elseif ($sort === SORT_DESC) {
            $this->_sort = 'desc';
        } else {
            $this->_sort = 'none';
        }

        // set the default advmultiselect element template (with javascript embedded)
        $this->setElementTemplate();
    }

    /**
     * Sets the button attributes
     *
     * In <b>custom example 1</b>, the <i>add</i> and <i>remove</i> buttons
     * have look set by the css class <i>inputCommand</i>.
     *
     * In <b>custom example 2</b>, the basic text <i>add</i> and <i>remove</i>
     * buttons are now replaced by images.
     *
     * In <b>custom example 5</b>, we have ability to sort the selection list
     * (on right side) by :
     * <pre>
     *  - <b>user-end</b>: with <i>Up</i> and <i>Down</i> buttons
     *  - <b>programming</b>: with the QF element constructor $sort option
     * </pre>
     *
     * @param string $button     Button identifier, either 'add', 'remove',
     *                                                     'all', 'none', 'toggle',
     *                                                     'movetop', 'movebottom'
     *                                                     'moveup' or 'movedown'
     * @param mixed  $attributes (optional) Either a typical HTML attribute string
     *                                      or an associative array
     *
     * @return     void
     * @throws     PEAR_Error   $button argument
     *                          is not a string, or not in range
     *                          (add, remove, all, none, toggle,
     *                           movetop, movebottom, moveup, movedown)
     * @access     public
     * @since      version 0.4.0 (2005-06-25)
     *
     * @example    examples/qfams_custom_5.php
     *             Custom example 5: source code
     * @link       http://www.laurent-laville.org/img/qfams/screenshot/custom5.png
     *             Custom example 5: screenshot
     *
     * @example    examples/qfams_custom_2.php
     *             Custom example 2: source code
     * @link       http://www.laurent-laville.org/img/qfams/screenshot/custom2.png
     *             Custom example 2: screenshot
     *
     * @example    examples/qfams_custom_1.php
     *             Custom example 1: source code
     * @link       http://www.laurent-laville.org/img/qfams/screenshot/custom1.png
     *             Custom example 1: screenshot
     */
    function setButtonAttributes($button, $attributes = null)
    {
        if (!is_string($button)) {
            return PEAR::throwError('Argument 1 of HTML_QuickForm_advmultiselect::' .
                       'setButtonAttributes is not a string',
                       HTML_QUICKFORM_ADVMULTISELECT_ERROR_INVALID_INPUT,
                       array('level' => 'exception'));
        }

        switch ($button) {
        case 'add':
            if (is_null($attributes)) {
                $this->_addButtonAttributes
                    = array('name'  => 'add',
                            'value' => '  ',
                            'type'  => 'button');
            } else {
                $this->_updateAttrArray($this->_addButtonAttributes,
                                        $this->_parseAttributes($attributes));
            }
            break;
        case 'remove':
            if (is_null($attributes)) {
                $this->_removeButtonAttributes
                    = array('name'  => 'remove',
                            'value' => '  ',
                            'type'  => 'button');
            } else {
                $this->_updateAttrArray($this->_removeButtonAttributes,
                                        $this->_parseAttributes($attributes));
            }
            break;
        case 'all':
            if (is_null($attributes)) {
                $this->_allButtonAttributes
                    = array('name'  => 'all',
                            'value' => ' Select All ',
                            'type'  => 'button');
            } else {
                $this->_updateAttrArray($this->_allButtonAttributes,
                                        $this->_parseAttributes($attributes));
            }
            break;
        case 'none':
            if (is_null($attributes)) {
                $this->_noneButtonAttributes
                    = array('name'  => 'none',
                            'value' => ' Select None ',
                            'type'  => 'button');
            } else {
                $this->_updateAttrArray($this->_noneButtonAttributes,
                                        $this->_parseAttributes($attributes));
            }
            break;
        case 'toggle':
            if (is_null($attributes)) {
                $this->_toggleButtonAttributes
                    = array('name'  => 'toggle',
                            'value' => ' Toggle Selection ',
                            'type'  => 'button');
            } else {
                $this->_updateAttrArray($this->_toggleButtonAttributes,
                                        $this->_parseAttributes($attributes));
            }
            break;
        case 'moveup':
            if (is_null($attributes)) {
                $this->_upButtonAttributes
                    = array('name'  => 'up',
                            'value' => ' Up ',
                            'type'  => 'button');
            } else {
                $this->_updateAttrArray($this->_upButtonAttributes,
                                        $this->_parseAttributes($attributes));
            }
            break;
        case 'movedown':
            if (is_null($attributes)) {
                $this->_downButtonAttributes
                    = array('name'  => 'down',
                            'value' => ' Down ',
                            'type'  => 'button');
            } else {
                $this->_updateAttrArray($this->_downButtonAttributes,
                                        $this->_parseAttributes($attributes));
            }
            break;
        case 'movetop':
            if (is_null($attributes)) {
                $this->_topButtonAttributes
                    = array('name'  => 'top',
                            'value' => ' Top ',
                            'type'  => 'button');
            } else {
                $this->_updateAttrArray($this->_topButtonAttributes,
                                        $this->_parseAttributes($attributes));
            }
            break;
        case 'movebottom':
            if (is_null($attributes)) {
                $this->_bottomButtonAttributes
                    = array('name'  => 'bottom',
                            'value' => ' Bottom ',
                            'type'  => 'button');
            } else {
                $this->_updateAttrArray($this->_bottomButtonAttributes,
                                        $this->_parseAttributes($attributes));
            }
            break;
        default;
            return PEAR::throwError('Argument 1 of HTML_QuickForm_advmultiselect::' .
                       'setButtonAttributes has unexpected value',
                       HTML_QUICKFORM_ADVMULTISELECT_ERROR_INVALID_INPUT,
                       array('level' => 'error'));
        }
    }

    /**
     * Sets element template
     *
     * @param string $html (optional) The HTML surrounding select boxes and buttons
     * @param string $js   (optional) if we need to include qfams javascript handler
     *
     * @access     public
     * @return     string
     * @since      version 0.4.0 (2005-06-25)
     */
    function setElementTemplate($html = null, $js = true)
    {
        $oldTemplate = $this->_elementTemplate;

        if (isset($html) && is_string($html)) {
            $this->_elementTemplate = $html;
        } else {
            $this->_elementTemplate = '
{javascript}
<table{class}>
<!-- BEGIN label_2 --><tr><th>{label_2}</th><!-- END label_2 -->
<!-- BEGIN label_3 --><th>&nbsp;</th><th>{label_3}</th></tr><!-- END label_3 -->
<tr>
  <td valign="top">{unselected}</td>
  <td align="center">{add}{remove}</td>
  <td valign="top">{selected}</td>
</tr>
</table>
';
        }
        if ($js == false) {
            $this->_elementTemplate = str_replace('{javascript}', '',
                                                  $this->_elementTemplate);
        }
        return $oldTemplate;
    }

    /**
     * Gets default element stylesheet for a single multi-select shape render
     *
     * In <b>custom example 4</b>, the template defined allows
     * a single multi-select checkboxes shape. Useful when javascript is disabled
     * (or when browser is not js compliant). In our example, no need to add
     * javascript code, but css is mandatory.
     *
     * @param boolean $raw (optional) html output with style tags or just raw data
     *
     * @access     public
     * @return     string
     * @since      version 0.4.0 (2005-06-25)
     *
     * @example    qfams_custom_4.php
     *             Custom example 4: source code
     * @link       http://www.laurent-laville.org/img/qfams/screenshot/custom4.png
     *             Custom example 4: screenshot
     */
    function getElementCss($raw = true)
    {
        $id  = $this->getAttribute('name');
        $css = str_replace('{id}', $id, $this->_elementCSS);

        if ($raw !== true) {
            $css = '<style type="text/css">' . PHP_EOL
                 // Modified by Chamilo team, 16-MAR-2010.
                 //. '<!--' . $css . '// -->'  . PHP_EOL
                 . '/*<![CDATA[*/' . PHP_EOL
                 . $css . PHP_EOL
                 . '/*]]>*/'  . PHP_EOL
                 //
                 . '</style>';
        }
        return $css;
    }

    /**
     * Returns the HTML generated for the advanced mutliple select component
     *
     * @access     public
     * @return     string
     * @since      version 0.4.0 (2005-06-25)
     */
    function toHtml()
    {
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        }

        $tabs    = $this->_getTabs();
        $tab     = $this->_getTab();

        $selectId       = $this->getName();
        $selectName     = $this->getName() . '[]';
        $selectNameFrom = $this->getName() . '-f[]';
        $selectNameTo   = $this->getName() . '-t[]';
        $selected_count = 0;

        // placeholder {unselected} existence determines if we will render
        if (strpos($this->_elementTemplate, '{unselected}') === false) {
            // ... a single multi-select with checkboxes
            $this->_jsPostfix = 'editSelection';

            $id = $this->getAttribute('name');

            $strHtmlSelected  = $tab . '<div id="qfams_'.$id.'">'  . PHP_EOL;
            $unselected_count = count($this->_options);

            $checkbox_id_suffix = 0;

            foreach ($this->_options as $option) {
                $_labelAttributes
                    = array('style', 'class', 'onmouseover', 'onmouseout');
                $labelAttributes = array();
                foreach ($_labelAttributes as $attr) {
                    if (isset($option['attr'][$attr])) {
                        $labelAttributes[$attr] = $option['attr'][$attr];
                        unset($option['attr'][$attr]);
                    }
                }

                if (is_array($this->_values)
                    && in_array((string)$option['attr']['value'], $this->_values)) {
                    // The items is *selected*
                    $checked = ' checked="checked"';
                    $selected_count++;
                } else {
                    // The item is *unselected* so we want to put it
                    $checked = '';
                }
                $checkbox_id_suffix++;
                $strHtmlSelected .= $tab
                                 .  '<label'
                                 .  $this->_getAttrString($labelAttributes) .'>'
                                 .  '<input type="checkbox"'
                                 .  ' id="'.$selectId . $checkbox_id_suffix.'"'
                                 .  ' name="'.$selectName.'"'
                                 .  $checked
                                 .  $this->_getAttrString($option['attr'])
                                 .  ' />' .  $option['text'] . '</label>'
                                 .  PHP_EOL;
            }
            $strHtmlSelected .= $tab . '</div>'. PHP_EOL;

            $strHtmlHidden     = '';
            $strHtmlUnselected = '';
            $strHtmlAdd        = '';
            $strHtmlRemove     = '';

            // build the select all button with all its attributes
            $jsName     = $this->_jsPrefix . $this->_jsPostfix;
            $attributes = array('onclick' => $jsName .
                                             "('". $selectId ."', 1);");
            $this->_allButtonAttributes
                        = array_merge($this->_allButtonAttributes, $attributes);
            $attrStrAll = $this->_getAttrString($this->_allButtonAttributes);
            $strHtmlAll = "<input$attrStrAll />". PHP_EOL;

            // build the select none button with all its attributes
            $attributes = array('onclick' => $jsName .
                                             "('". $selectId ."', 0);");
            $this->_noneButtonAttributes
                         = array_merge($this->_noneButtonAttributes, $attributes);
            $attrStrNone = $this->_getAttrString($this->_noneButtonAttributes);
            $strHtmlNone = "<input$attrStrNone />". PHP_EOL;

            // build the toggle selection button with all its attributes
            $attributes = array('onclick' => $jsName .
                                             "('". $selectId ."', 2);");
            $this->_toggleButtonAttributes
                           = array_merge($this->_toggleButtonAttributes,
                                 $attributes);
            $attrStrToggle = $this->_getAttrString($this->_toggleButtonAttributes);
            $strHtmlToggle = "<input$attrStrToggle />". PHP_EOL;

            $strHtmlMoveUp      = '';
            $strHtmlMoveDown    = '';
            $strHtmlMoveTop     = '';
            $strHtmlMoveBottom  = '';

            // default selection counters
            $strHtmlSelectedCount = $selected_count . '/' . $unselected_count;
        } else {
            // ... or a dual multi-select
            $this->_jsPostfix = 'moveSelection';
            $jsName           = $this->_jsPrefix . $this->_jsPostfix;

            // set name of Select From Box
            $this->_attributesUnselected
                = array('id' => $selectId . '-f',
                        'name' => $selectNameFrom,
                        'ondblclick' => $jsName .
                            "('{$selectId}', ".
                            "this.form.elements['" . $selectNameFrom . "'], " .
                            "this.form.elements['" . $selectNameTo . "'], " .
                            "this.form.elements['" . $selectName . "'], " .
                            "'add', '{$this->_sort}')");
            $this->_attributesUnselected
                = array_merge($this->_attributes, $this->_attributesUnselected);
            $attrUnselected = $this->_getAttrString($this->_attributesUnselected);

            // set name of Select To Box
            $this->_attributesSelected
                = array('id' => $selectId . '-t',
                        'name' => $selectNameTo,
                        'ondblclick' => $jsName .
                            "('{$selectId}', " .
                            "this.form.elements['" . $selectNameFrom . "'], " .
                            "this.form.elements['" . $selectNameTo . "'], ".
                            "this.form.elements['" . $selectName . "'], " .
                            "'remove', '{$this->_sort}')");
            $this->_attributesSelected
                = array_merge($this->_attributes, $this->_attributesSelected);
            $attrSelected = $this->_getAttrString($this->_attributesSelected);

            // set name of Select hidden Box
            $this->_attributesHidden
                = array('name' => $selectName,
                        'style' => 'overflow: hidden; visibility: hidden; ' .
                                   'width: 1px; height: 0;');
            $this->_attributesHidden
                = array_merge($this->_attributes, $this->_attributesHidden);
            $attrHidden = $this->_getAttrString($this->_attributesHidden);

            // prepare option tables to be displayed as in POST order
            $append = count($this->_values);
            if ($append > 0) {
                $arrHtmlSelected = array_fill(0, $append, ' ');
            } else {
                $arrHtmlSelected = array();
            }

            $options           = count($this->_options);
            $arrHtmlUnselected = array();
            if ($options > 0) {
                $arrHtmlHidden = array_fill(0, $options, ' ');

                foreach ($this->_options as $option) {
                    if (is_array($this->_values)
                        && in_array((string)$option['attr']['value'],
                               $this->_values)) {
                        // Get the post order
                        $key = array_search($option['attr']['value'],
                                   $this->_values);

                        /** The items is *selected* so we want to put it
                            in the 'selected' multi-select */
                        $arrHtmlSelected[$key] = $option;
                        /** Add it to the 'hidden' multi-select
                            and set it as 'selected' */
                        if (isset($option['attr']['disabled'])) {
                            unset($option['attr']['disabled']);
                        }
                        $option['attr']['selected'] = 'selected';
                        $arrHtmlHidden[$key]        = $option;
                    } else {
                        /** The item is *unselected* so we want to put it
                            in the 'unselected' multi-select */
                        $arrHtmlUnselected[] = $option;
                        // Add it to the hidden multi-select as 'unselected'
                        $arrHtmlHidden[$append] = $option;
                        $append++;
                    }
                }
            } else {
                $arrHtmlHidden = array();
            }

            // The 'unselected' multi-select which appears on the left
            $unselected_count = count($arrHtmlUnselected);

            if ($unselected_count == 0) {
                $this->_attributesUnselected['disabled'] = 'disabled';
                $this->_attributesUnselected
                    = array_merge($this->_attributes, $this->_attributesUnselected);
                $attrUnselected = $this->_getAttrString($this->_attributesUnselected);
            }
            $strHtmlUnselected = "<select$attrUnselected>". PHP_EOL;
            if ($unselected_count > 0) {
                foreach ($arrHtmlUnselected as $data) {
                    $strHtmlUnselected
                        .= $tabs . $tab
                        . '<option' . $this->_getAttrString($data['attr']) . '>'
                        . $data['text'] . '</option>' . PHP_EOL;
                }
            } else {
                $strHtmlUnselected .= '<option value="">&nbsp;</option>';
            }
            $strHtmlUnselected .= '</select>';

            // The 'selected' multi-select which appears on the right
            $selected_count = count($arrHtmlSelected);

            if ($selected_count == 0) {
                $this->_attributesSelected['disabled'] = 'disabled';
                $this->_attributesSelected
                    = array_merge($this->_attributes, $this->_attributesSelected);
                $attrSelected = $this->_getAttrString($this->_attributesSelected);
            }
            $strHtmlSelected = "<select$attrSelected>". PHP_EOL;
            if ($selected_count > 0) {
                foreach ($arrHtmlSelected as $data) {
                    $strHtmlSelected
                        .= $tabs . $tab
                        . '<option' . $this->_getAttrString($data['attr']) . '>'
                        . $data['text'] . '</option>' . PHP_EOL;
                }
            } else {
                $strHtmlSelected .= '<option value="">&nbsp;</option>';
            }
            $strHtmlSelected .= '</select>';

            // The 'hidden' multi-select
            $strHtmlHidden = "<select$attrHidden>". PHP_EOL;
            if (count($arrHtmlHidden) > 0) {
                foreach ($arrHtmlHidden as $data) {
                    $strHtmlHidden
                        .= $tabs . $tab
                        . '<option' . $this->_getAttrString($data['attr']) . '>'
                        . $data['text'] . '</option>' . PHP_EOL;
                }
            }
            $strHtmlHidden .= '</select>';

            // build the remove button with all its attributes
            $attributes
                = array('onclick' => $jsName .
                            "('{$selectId}', " .
                            "this.form.elements['" . $selectNameFrom . "'], " .
                            "this.form.elements['" . $selectNameTo . "'], " .
                            "this.form.elements['" . $selectName . "'], " .
                            "'remove', '{$this->_sort}'); return false;");
            $this->_removeButtonAttributes
                = array_merge($this->_removeButtonAttributes, $attributes);
            $attrStrRemove = $this->_getAttrString($this->_removeButtonAttributes);
            $strHtmlRemove = "<input$attrStrRemove />". PHP_EOL;

            // build the add button with all its attributes
            $attributes
                = array('onclick' => $jsName .
                            "('{$selectId}', " .
                            "this.form.elements['" . $selectNameFrom . "'], " .
                            "this.form.elements['" . $selectNameTo . "'], " .
                            "this.form.elements['" . $selectName . "'], " .
                            "'add', '{$this->_sort}'); return false;");
            $this->_addButtonAttributes
                = array_merge($this->_addButtonAttributes, $attributes);
            $attrStrAdd = $this->_getAttrString($this->_addButtonAttributes);
            $strHtmlAdd = "<input$attrStrAdd />". PHP_EOL;

            // build the select all button with all its attributes
            $attributes
                = array('onclick' => $jsName .
                            "('{$selectId}', " .
                            "this.form.elements['" . $selectNameFrom . "'], " .
                            "this.form.elements['" . $selectNameTo . "'], " .
                            "this.form.elements['" . $selectName . "'], " .
                            "'all', '{$this->_sort}'); return false;");
            $this->_allButtonAttributes
                = array_merge($this->_allButtonAttributes, $attributes);
            $attrStrAll = $this->_getAttrString($this->_allButtonAttributes);
            $strHtmlAll = "<input$attrStrAll />". PHP_EOL;

            // build the select none button with all its attributes
            $attributes
                = array('onclick' => $jsName .
                            "('{$selectId}', " .
                            "this.form.elements['" . $selectNameFrom . "'], " .
                            "this.form.elements['" . $selectNameTo . "'], " .
                            "this.form.elements['" . $selectName . "'], " .
                            "'none', '{$this->_sort}'); return false;");
            $this->_noneButtonAttributes
                = array_merge($this->_noneButtonAttributes, $attributes);
            $attrStrNone = $this->_getAttrString($this->_noneButtonAttributes);
            $strHtmlNone = "<input$attrStrNone />". PHP_EOL;

            // build the toggle button with all its attributes
            $attributes
                = array('onclick' => $jsName .
                            "('{$selectId}', " .
                            "this.form.elements['" . $selectNameFrom . "'], " .
                            "this.form.elements['" . $selectNameTo . "'], " .
                            "this.form.elements['" . $selectName . "'], " .
                            "'toggle', '{$this->_sort}'); return false;");
            $this->_toggleButtonAttributes
                = array_merge($this->_toggleButtonAttributes, $attributes);
            $attrStrToggle = $this->_getAttrString($this->_toggleButtonAttributes);
            $strHtmlToggle = "<input$attrStrToggle />". PHP_EOL;

            // build the move up button with all its attributes
            $attributes
                = array('onclick' => "{$this->_jsPrefix}moveUp" .
                            "(this.form.elements['" . $selectNameTo . "'], " .
                            "this.form.elements['" . $selectName . "']); " .
                            "return false;");
            $this->_upButtonAttributes
                = array_merge($this->_upButtonAttributes, $attributes);
            $attrStrUp     = $this->_getAttrString($this->_upButtonAttributes);
            $strHtmlMoveUp = "<input$attrStrUp />". PHP_EOL;

            // build the move down button with all its attributes
            $attributes
                = array('onclick' => "{$this->_jsPrefix}moveDown" .
                            "(this.form.elements['" . $selectNameTo . "'], " .
                            "this.form.elements['" . $selectName . "']); " .
                            "return false;");
            $this->_downButtonAttributes
                = array_merge($this->_downButtonAttributes, $attributes);
            $attrStrDown     = $this->_getAttrString($this->_downButtonAttributes);
            $strHtmlMoveDown = "<input$attrStrDown />". PHP_EOL;

            // build the move top button with all its attributes
            $attributes
                = array('onclick' => "{$this->_jsPrefix}moveTop" .
                            "(this.form.elements['" . $selectNameTo . "'], " .
                            "this.form.elements['" . $selectName . "']); " .
                            "return false;");
            $this->_topButtonAttributes
                = array_merge($this->_topButtonAttributes, $attributes);
            $attrStrTop     = $this->_getAttrString($this->_topButtonAttributes);
            $strHtmlMoveTop = "<input$attrStrTop />". PHP_EOL;

            // build the move bottom button with all its attributes
            $attributes
                = array('onclick' => "{$this->_jsPrefix}moveBottom" .
                            "(this.form.elements['" . $selectNameTo . "'], " .
                            "this.form.elements['" . $selectName . "']); " .
                            "return false;");
            $this->_bottomButtonAttributes
                = array_merge($this->_bottomButtonAttributes, $attributes);
            $attrStrBottom     = $this->_getAttrString($this->_bottomButtonAttributes);
            $strHtmlMoveBottom = "<input$attrStrBottom />". PHP_EOL;

            // default selection counters
            $strHtmlSelectedCount = $selected_count;
        }
        $strHtmlUnselectedCount = $unselected_count;

        $strHtmlSelectedCountId   = $selectId .'_selected';
        $strHtmlUnselectedCountId = $selectId .'_unselected';

        // render all part of the multi select component with the template
        $strHtml = $this->_elementTemplate;

        // Prepare multiple labels
        $labels = $this->getLabel();
        if (is_array($labels)) {
            array_shift($labels);
        }
        // render extra labels, if any
        if (is_array($labels)) {
            foreach ($labels as $key => $text) {
                $key     = is_int($key)? $key + 2: $key;
                $strHtml = str_replace("{label_{$key}}", $text, $strHtml);
                $strHtml = str_replace("<!-- BEGIN label_{$key} -->", '', $strHtml);
                $strHtml = str_replace("<!-- END label_{$key} -->", '', $strHtml);
            }
        }
        // clean up useless label tags
        if (strpos($strHtml, '{label_')) {
            $strHtml = preg_replace('/\s*<!-- BEGIN label_(\S+) -->'.
                           '.*<!-- END label_\1 -->\s*/i', '', $strHtml);
        }

        $placeHolders = array(
            '{stylesheet}', '{javascript}',
            '{class}',
            '{unselected_count_id}', '{selected_count_id}',
            '{unselected_count}', '{selected_count}',
            '{unselected}', '{selected}',
            '{add}', '{remove}',
            '{all}', '{none}', '{toggle}',
            '{moveup}', '{movedown}',
            '{movetop}', '{movebottom}'
        );
        $htmlElements = array(
            $this->getElementCss(false), $this->getElementJs(false),
            $this->_tableAttributes,
            $strHtmlUnselectedCountId, $strHtmlSelectedCountId,
            $strHtmlUnselectedCount, $strHtmlSelectedCount,
            $strHtmlUnselected, $strHtmlSelected . $strHtmlHidden,
            $strHtmlAdd, $strHtmlRemove,
            $strHtmlAll, $strHtmlNone, $strHtmlToggle,
            $strHtmlMoveUp, $strHtmlMoveDown,
            $strHtmlMoveTop, $strHtmlMoveBottom
        );

        $strHtml = str_replace($placeHolders, $htmlElements, $strHtml);

        $comment = $this->getComment();

        if (!empty($comment)) {
            $strHtml = $tabs . '<!-- ' . $comment . " //-->" . PHP_EOL . $strHtml;
        }

        return $strHtml;
    }

    /**
     * Returns the javascript code generated to handle this element
     *
     * @param boolean $raw (optional) html output with script tags or just raw data
     * @param boolean $min (optional) uses javascript compressed version
     *
     * @access     public
     * @return     string
     * @since      version 0.4.0 (2005-06-25)
     */
    function getElementJs($raw = true, $min = true)
    {
        $js = '@data_dir@' . DIRECTORY_SEPARATOR
            . '@package_name@' . DIRECTORY_SEPARATOR;
        
        $js = api_get_path(LIBRARY_PATH).'javascript'.DIRECTORY_SEPARATOR.'pear'.DIRECTORY_SEPARATOR; 
		
        if ($min) {
            $js .= 'qfamsHandler-min.js';
        } else {
            $js .= 'qfamsHandler.js';
        }

        if (file_exists($js)) {        	
            $js = file_get_contents($js);            
        } else {
            $js = '';
        }
		
        if ($raw !== true) {
            $js = '<script type="text/javascript">'
                . PHP_EOL . '//<![CDATA['
                . PHP_EOL . $js
                . PHP_EOL . '//]]>'
                . PHP_EOL . '</script>'
                . PHP_EOL;
        }
        return $js;
    }

    /**
     * Loads options from different types of data sources
     *
     * This method overloaded parent method of select element, to allow
     * loading options with fancy attributes.
     *
     * @param mixed &$options Options source currently supports assoc array or DB_result
     * @param mixed $param1   (optional) See function detail
     * @param mixed $param2   (optional) See function detail
     * @param mixed $param3   (optional) See function detail
     * @param mixed $param4   (optional) See function detail
     *
     * @access     public
     * @since      version 1.5.0 (2009-02-15)
     * @return     PEAR_Error|NULL on error and TRUE on success
     * @throws     PEAR_Error
     * @see        loadArray()
     */
    function load(&$options,
                  $param1 = null, $param2 = null, $param3 = null, $param4 = null)
    {
        if (is_array($options)) {
            $ret = $this->loadArray($options, $param1);
        } else {
            $ret = parent::load($options, $param1, $param2, $param3, $param4);
        }
        return $ret;
    }

    /**
     * Loads the options from an associative array
     *
     * This method overloaded parent method of select element, to allow to load
     * array of options with fancy attributes.
     *
     * @param array $arr    Associative array of options
     * @param mixed $values (optional) Array or comma delimited string of selected values
     *
     * @since      version 1.5.0 (2009-02-15)
     * @access     public
     * @return     PEAR_Error on error and TRUE on success
     * @throws     PEAR_Error
     * @see        load()
     */
    function loadArray($arr, $values = null)
    {
        if (!is_array($arr)) {
            return PEAR::throwError('Argument 1 of HTML_QuickForm_advmultiselect::' .
                       'loadArray is not a valid array',
                       HTML_QUICKFORM_ADVMULTISELECT_ERROR_INVALID_INPUT,
                       array('level' => 'exception'));
        }
        if (isset($values)) {
            $this->setSelected($values);
        }
        if (is_array($arr)) {
            foreach ($arr as $key => $val) {
                if (is_array($val)) {
                    $this->addOption($val[0], $key, $val[1]);
                } else {
                    $this->addOption($val, $key);
                }
            }
        }
        return true;
    }

    /**
     * Sets which items should be persistant
     *
     * Sets which items should have the disabled attribute
     * to keep it persistant
     *
     * @param mixed $optionValues Options (key-values) that should be persistant
     * @param bool  $persistant   (optional) TRUE if persistant, FALSE otherwise
     *
     * @since      version 1.5.0 (2009-02-15)
     * @access     public
     * @return     PEAR_Error on error and TRUE on success
     * @throws     PEAR_Error
     */
    function setPersistantOptions($optionValues, $persistant = true)
    {
        if (!is_bool($persistant)) {
            return PEAR::throwError('Argument 2 of HTML_QuickForm_advmultiselect::' .
                       'setPersistantOptions is not a boolean',
                       HTML_QUICKFORM_ADVMULTISELECT_ERROR_INVALID_INPUT,
                       array('level' => 'exception'));
        }
        if (is_string($optionValues)) {
            $optionValues = array($optionValues);
        }
        if (!is_array($optionValues)) {
            return PEAR::throwError('Argument 1 of HTML_QuickForm_advmultiselect::' .
                       'setPersistantOptions is not a valid array',
                       HTML_QUICKFORM_ADVMULTISELECT_ERROR_INVALID_INPUT,
                       array('level' => 'exception'));
        }

        foreach ($this->_options as $k => $v) {
            if (in_array($v['attr']['value'], $optionValues)) {
                if ($persistant) {
                    $this->_options[$k]['attr']['disabled'] = 'disabled';
                } else {
                    unset($this->_options[$k]['attr']['disabled']);
                }
            }
        }
        return true;
    }

    /**
     * Returns list of persistant options
     *
     * Returns list of persistant options (key-values) that could not
     * be selected or unselected.
     *
     * @since      version 1.5.0 (2009-02-15)
     * @access     public
     * @return     array
     */
    function getPersistantOptions()
    {
        $options = array();

        foreach ($this->_options as $k => $v) {
            if (isset($v['attr']['disabled'])) {
                $options[] = $this->_options[$k]['attr']['value'];
            }
        }

        return $options;
    }
}

// @todo this fires an PHP notice, needed in order to parse the advmultiselect test it in main/group/group_edit.php
if (class_exists('HTML_QuickForm')) {    
    @HTML_QuickForm::registerElementType('advmultiselect','HTML/QuickForm/advmultiselect.php', 'HTML_QuickForm_advmultiselect');
}
