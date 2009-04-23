<?php
/**
* Element for HTML_QuickForm that emulate a multi-select.
*
* The HTML_QuickForm_advmultiselect package adds an element to the
* HTML_QuickForm package that is two select boxes next to each other
* emulating a multi-select.
*
* PHP versions 4 and 5
*
* LICENSE: This source file is subject to version 3.0 of the PHP license
* that is available through the world-wide-web at the following URI:
* http://www.php.net/license/3_0.txt.  If you did not receive a copy of
* the PHP License and are unable to obtain it through the web, please
* send a note to license@php.net so we can mail you a copy immediately.
*
* @category   HTML
* @package    HTML_QuickForm_advmultiselect
* @author     Laurent Laville <pear@laurent-laville.org>
* @copyright  1997-2005 The PHP Group
* @license    http://www.php.net/license/3_0.txt  PHP License 3.0
* @version    CVS: $Id: advmultiselect.php 20028 2009-04-23 19:32:35Z cfasanando $
* @link       http://pear.php.net/package/HTML_QuickForm_advmultiselect
*/

require_once 'HTML/QuickForm/select.php';

/**
* Replace PHP_EOL constant
*
*  category    PHP
*  package     PHP_Compat
* @link        http://php.net/reserved.constants.core
* @author      Aidan Lister <aidan@php.net>
* @since       PHP 5.0.2
*/
if (!defined('PHP_EOL')) {
    switch (strtoupper(substr(PHP_OS, 0, 3))) {
        // Windows
        case 'WIN':
            define('PHP_EOL', "\r\n");
            break;

        // Mac
        case 'DAR':
            define('PHP_EOL', "\r");
            break;

        // Unix
        default:
            define('PHP_EOL', "\n");
    }
}

/**
* Element for HTML_QuickForm that emulate a multi-select.
*
* The HTML_QuickForm_advmultiselect package adds an element to the
* HTML_QuickForm package that is two select boxes next to each other
* emulating a multi-select.
*
* PHP versions 4 and 5
*
* LICENSE: This source file is subject to version 3.0 of the PHP license
* that is available through the world-wide-web at the following URI:
* http://www.php.net/license/3_0.txt.  If you did not receive a copy of
* the PHP License and are unable to obtain it through the web, please
* send a note to license@php.net so we can mail you a copy immediately.
*
* @category   HTML
* @package    HTML_QuickForm_advmultiselect
* @author     Laurent Laville <pear@laurent-laville.org>
* @copyright  1997-2005 The PHP Group
* @license    http://www.php.net/license/3_0.txt  PHP License 3.0
* @version    Release: 0.5.1
* @link       http://pear.php.net/package/HTML_QuickForm_advmultiselect
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
     * Defines if both list (unselected, selected) will have their elements be
     * arranged from lowest to highest (or reverse) depending on comparaison function.
     *
     * SORT_ASC  is used to sort in ascending order
     * SORT_DESC is used to sort in descending order
     *
     * @var        integer
     * @access     private
     * @since      0.5.0
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
    var $_elementTemplate = '
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

    /**
     * Default Element stylesheet string
     *
     * @var        string
     * @access     private
     * @since      0.4.0
     */
    var $_elementCSS = '
#{id}amsSelected {
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
#{id}amsSelected label {
  padding-right: 3px;
  display: block;
}
';

    /**
     * Class constructor
     *
     * @param      string    $elementName   Dual Select name attribute
     * @param      mixed     $elementLabel  Label(s) for the select boxes
     * @param      mixed     $options       Data to be used to populate options
     * @param      mixed     $attributes    Either a typical HTML attribute string or an associative array
     * @param      integer   $sortOptions   Either SORT_ASC for auto ascending arrange,
     *                                             SORT_DESC for auto descending arrange, or
     *                                             NULL for no sort (append at end: default)
     *
     * @access     public
     * @return     void
     * @since      0.4.0
     */
    function HTML_QuickForm_advmultiselect($elementName = null, $elementLabel = null,
                                           $options = null, $attributes = null,
                                           $sortOptions = null)
    {
        $this->HTML_QuickForm_select($elementName, $elementLabel, $options, $attributes);

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
            $attr = array('border' => '0', 'cellpadding' => '10', 'cellspacing' => '0');
        } else {
            $attr = array('class' => $this->_tableAttributes);
            $this->_removeAttr('class', $this->_attributes);
        }
        $this->_tableAttributes = $this->_getAttrString($attr);

        // set default add button attributes
        $this->setButtonAttributes('add');
        // set default remove button attributes
        $this->setButtonAttributes('remove');
        // set default move up button attributes
        $this->setButtonAttributes('moveup');
        // set default move up button attributes
        $this->setButtonAttributes('movedown');
        // defines javascript functions names
        $this->setJsElement();

        // set select boxes sort order (none by default)
        if (isset($sortOptions)) {
            $this->_sort = $sortOptions;
        } else {
            $this->_sort = false;
        }
    }

    /**
     * Sets the button attributes
     *
     * In <b>custom example 1</b>, the <i>add</i> and <i>remove</i> buttons have look set
     * by the css class <i>inputCommand</i>. See especially lines 43-48 and 98-103.
     *
     * In <b>custom example 2</b>, the basic text <i>add</i> and <i>remove</i> buttons
     * are now replaced by images. See lines 43-44.
     *
     * In <b>custom example 5</b>, we have ability to sort the selection list (on right side)
     * by :
     * <pre>
     *  - <b>user-end</b>: with <i>Up</i> and <i>Down</i> buttons
     *    (see lines 65,65,76 and 128-130)
     *  - <b>programming</b>: with the QF element constructor $sort option
     *    (see lines 34,36,38 and 59)
     * </pre>
     *
     * @example    examples/qfams_custom_5.php                                      Custom example 5: source code
     * @link       http://www.laurent-laville.org/img/qfams/screenshot/custom5.png  Custom example 5: screenshot
     *
     * @example    examples/qfams_custom_2.php                                      Custom example 2: source code
     * @link       http://www.laurent-laville.org/img/qfams/screenshot/custom2.png  Custom example 2: screenshot
     *
     * @example    examples/qfams_custom_1.php                                      Custom example 1: source code
     * @link       http://www.laurent-laville.org/img/qfams/screenshot/custom1.png  Custom example 1: screenshot
     *
     * @param      string    $button        Button identifier, either 'add', 'remove', 'moveup' or 'movedown'
     * @param      mixed     $attributes    (optional) Either a typical HTML attribute string
     *                                      or an associative array
     * @access     public
     * @since      0.4.0
     */
    function setButtonAttributes($button, $attributes = null)
    {
        if (!is_string($button)) {
            return PEAR::raiseError('Argument 1 of advmultiselect::setButtonAttributes'
                                   .' is not a string');
        }

        switch ($button) {
            case 'add':
                if (is_null($attributes)) {
                    $this->_addButtonAttributes = array('name'  => 'add',
                                                        'value' => ' >> ',
                                                        'type'  => 'button'
                                                       );
                } else {
                    $this->_updateAttrArray($this->_addButtonAttributes,
                                            $this->_parseAttributes($attributes)
                    );
                }
                break;
            case 'remove':
                if (is_null($attributes)) {
                    $this->_removeButtonAttributes = array('name'  => 'remove',
                                                           'value' => ' << ',
                                                           'type'  => 'button'
                                                          );
                } else {
                    $this->_updateAttrArray($this->_removeButtonAttributes,
                                            $this->_parseAttributes($attributes)
                    );
                }
                break;
            case 'moveup':
                if (is_null($attributes)) {
                    $this->_upButtonAttributes = array('name'  => 'up',
                                                       'value' => ' Up ',
                                                       'type'  => 'button'
                                                      );
                } else {
                    $this->_updateAttrArray($this->_upButtonAttributes,
                                            $this->_parseAttributes($attributes)
                    );
                }
                break;
            case 'movedown':
                if (is_null($attributes)) {
                    $this->_downButtonAttributes = array('name'  => 'down',
                                                         'value' => ' Down ',
                                                         'type'  => 'button'
                                                        );
                } else {
                    $this->_updateAttrArray($this->_downButtonAttributes,
                                            $this->_parseAttributes($attributes)
                    );
                }
                break;
            default;
                return PEAR::raiseError('Argument 1 of advmultiselect::setButtonAttributes'
                                       .' has unexpected value');
        }
    }

    /**
     * Sets element template
     *
     * @param      string    $html          The HTML surrounding select boxes and buttons
     *
     * @access     public
     * @return     void
     * @since      0.4.0
     */
    function setElementTemplate($html)
    {
        $this->_elementTemplate = $html;
    }

    /**
     * Sets JavaScript function name parts. Maybe usefull to avoid conflict names
     *
     * In <b>multiple example 1</b>, the javascript function prefix is set to not null
     * (see line 60).
     *
     * @example    examples/qfams_multiple_1.php                                      Multiple example 1: source code
     * @link       http://www.laurent-laville.org/img/qfams/screenshot/multiple1.png  Multiple example 1: screenshot
     *
     * @param      string    $pref          (optional) Prefix name
     * @param      string    $post          (optional) Postfix name
     *
     * @access     public
     * @return     void
     * @see        getElementJs()
     * @since      0.4.0
     */
    function setJsElement($pref = null, $post = 'moveSelections')
    {
        $this->_jsPrefix  = $pref;
        $this->_jsPostfix = $post;
    }

    /**
     * Gets default element stylesheet for a single multi-select shape render
     *
     * In <b>custom example 4</b>, the template defined lines 80-87 allows
     * a single multi-select checkboxes shape. Useful when javascript is disabled
     * (or when browser is not js compliant). In our example, no need to add javascript code
     * (see lines 170-172), but css is mandatory (see line 142).
     *
     * @example    qfams_custom_4.php                                               Custom example 4: source code
     * @link       http://www.laurent-laville.org/img/qfams/screenshot/custom4.png  Custom example 4: screenshot
     *
     * @param      boolean   $raw           (optional) html output with style tags or just raw data
     *
     * @access     public
     * @return     string
     * @since      0.4.0
     */
    function getElementCss($raw = true)
    {
        $id = $this->getAttribute('id');
        $css = str_replace('{id}', $id, $this->_elementCSS);

        if ($raw !== true) {
            $css = '<style type="text/css">' . PHP_EOL
                 . '/*<![CDATA[*/' . PHP_EOL
                 . $css . PHP_EOL
                 . '/*]]>*/'  . PHP_EOL
                 . '</style>';
        }
        return $css;
    }

    /**
     * Returns the HTML generated for the advanced mutliple select component
     *
     * @access     public
     * @return     string
     * @since      0.4.0
     */
    function toHtml()
    {
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        }

        $tabs    = $this->_getTabs();
        $tab     = $this->_getTab();
        $strHtml = '';

        if ($this->getComment() != '') {
            $strHtml .= $tabs . '<!-- ' . $this->getComment() . " //-->" . PHP_EOL;
        }

        $selectName = $this->getName() . '[]';

        // placeholder {unselected} existence determines if we will render
        if (strpos($this->_elementTemplate, '{unselected}') === false) {
            // ... a single multi-select with checkboxes

            $id = $this->getAttribute('id');

            $strHtmlSelected = $tab . '<div id="'.$id.'amsSelected">'  . PHP_EOL;

            foreach ($this->_options as $option) {

                $_labelAttributes  = array('style', 'class', 'onmouseover', 'onmouseout');
                $labelAttributes = array();
                foreach ($_labelAttributes as $attr) {
                    if (isset($option['attr'][$attr])) {
                        $labelAttributes[$attr] = $option['attr'][$attr];
                        unset($option['attr'][$attr]);
                    }
                }

                if (is_array($this->_values) && in_array((string)$option['attr']['value'], $this->_values)) {
                    // The items is *selected*
                    $checked = ' checked="checked"';
                } else {
                    // The item is *unselected* so we want to put it
                    $checked = '';
                }
                $strHtmlSelected .= $tab
                                 .  '<label'
                                 .  $this->_getAttrString($labelAttributes) .'>'
                                 .  '<input type="checkbox"'
                                 .  ' name="'.$selectName.'"'
                                 .  $checked
                                 .  $this->_getAttrString($option['attr'])
                                 .  ' />' .  $option['text'] . '</label>'
                                 .  PHP_EOL;
            }
            $strHtmlSelected    .= $tab . '</div>'. PHP_EOL;

            $strHtmlHidden = '';
            $strHtmlUnselected = '';
            $strHtmlAdd = '';
            $strHtmlRemove = '';
            $strHtmlMoveUp = '';
            $strHtmlMoveDown = '';

        } else {
            // ... or a dual multi-select

            // set name of Select From Box
            $this->_attributesUnselected = array('name' => '__'.$selectName, 'ondblclick' => "{$this->_jsPrefix}{$this->_jsPostfix}(this.form.elements['__" . $selectName . "'], this.form.elements['_" . $selectName . "'], this.form.elements['" . $selectName . "'], 'add')");
            $this->_attributesUnselected = array_merge($this->_attributes, $this->_attributesUnselected);
            $attrUnselected = $this->_getAttrString($this->_attributesUnselected);

            // set name of Select To Box
            $this->_attributesSelected = array('name' => '_'.$selectName, 'ondblclick' => "{$this->_jsPrefix}{$this->_jsPostfix}(this.form.elements['__" . $selectName . "'], this.form.elements['_" . $selectName . "'], this.form.elements['" . $selectName . "'], 'remove')");
            $this->_attributesSelected = array_merge($this->_attributes, $this->_attributesSelected);
            $attrSelected = $this->_getAttrString($this->_attributesSelected);

            // set name of Select hidden Box
            $this->_attributesHidden = array('name' => $selectName, 'style' => 'overflow: hidden; visibility: hidden; width: 1px; height: 0;');
            $this->_attributesHidden = array_merge($this->_attributes, $this->_attributesHidden);
            $attrHidden = $this->_getAttrString($this->_attributesHidden);

            // prepare option tables to be displayed as in POST order
            $append = count($this->_values);
            if ($append > 0) {
                $arrHtmlSelected = array_fill(0, $append, ' ');
            }
            $arrHtmlHidden = array_fill(0, count($this->_options), ' ');

            foreach ($this->_options as $option) {
                if (is_array($this->_values) &&
                    in_array((string)$option['attr']['value'], $this->_values)) {
                    // Get the post order
                    $key = array_search($option['attr']['value'], $this->_values);

                    // The items is *selected* so we want to put it in the 'selected' multi-select
                    $arrHtmlSelected[$key] = $option;
                    // Add it to the 'hidden' multi-select and set it as 'selected'
                    $option['attr']['selected'] = 'selected';
                    $arrHtmlHidden[$key] = $option;
                } else {
                    // The item is *unselected* so we want to put it in the 'unselected' multi-select
                    $arrHtmlUnselected[] = $option;
                    // Add it to the hidden multi-select as 'unselected'
                    $arrHtmlHidden[$append] = $option;
                    $append++;
                }
            }

            // The 'unselected' multi-select which appears on the left
            $strHtmlUnselected = "<select$attrUnselected>". PHP_EOL;
            if (is_array($arrHtmlUnselected) && count($arrHtmlUnselected) > 0) {
		        foreach ($arrHtmlUnselected as $data) {
		            $strHtmlUnselected .= $tabs . $tab
		                               . '<option' . $this->_getAttrString($data['attr']) . '>'
		                               . $data['text'] . '</option>' . PHP_EOL;
		        }
            }
            
            $strHtmlUnselected .= '</select>';

            // The 'selected' multi-select which appears on the right
            $strHtmlSelected = "<select$attrSelected>". PHP_EOL;
            if (isset($arrHtmlSelected)) {
                foreach ($arrHtmlSelected as $data) {
                    $strHtmlSelected .= $tabs . $tab
                                     . '<option' . $this->_getAttrString($data['attr']) . '>'
                                     . $data['text'] . '</option>' . PHP_EOL;
                }
            }
            $strHtmlSelected   .= '</select>';

            // The 'hidden' multi-select
            $strHtmlHidden = "<select$attrHidden>". PHP_EOL;
            foreach ($arrHtmlHidden as $data) {
                $strHtmlHidden .= $tabs . $tab
                               . '<option' . $this->_getAttrString($data['attr']) . '>'
                               . $data['text'] . '</option>' . PHP_EOL;
            }
            $strHtmlHidden     .= '</select>';

            // build the remove button with all its attributes
            $attributes = array('onclick' => "{$this->_jsPrefix}{$this->_jsPostfix}(this.form.elements['__" . $selectName . "'], this.form.elements['_" . $selectName . "'], this.form.elements['" . $selectName . "'], 'remove'); return false;");
            $this->_removeButtonAttributes = array_merge($this->_removeButtonAttributes, $attributes);
            $attrStrRemove = $this->_getAttrString($this->_removeButtonAttributes);
            $strHtmlRemove = "<input$attrStrRemove />". PHP_EOL;

            // build the add button with all its attributes
            $attributes = array('onclick' => "{$this->_jsPrefix}{$this->_jsPostfix}(this.form.elements['__" . $selectName . "'], this.form.elements['_" . $selectName . "'], this.form.elements['" . $selectName . "'], 'add'); return false;");
            $this->_addButtonAttributes = array_merge($this->_addButtonAttributes, $attributes);
            $attrStrAdd = $this->_getAttrString($this->_addButtonAttributes);
            $strHtmlAdd = "<input$attrStrAdd />". PHP_EOL;

            // build the move up button with all its attributes
            $attributes = array('onclick' => "{$this->_jsPrefix}moveUp(this.form.elements['_" . $selectName . "'], this.form.elements['" . $selectName . "']); return false;");
            $this->_upButtonAttributes = array_merge($this->_upButtonAttributes, $attributes);
            $attrStrUp = $this->_getAttrString($this->_upButtonAttributes);
            $strHtmlMoveUp = "<input$attrStrUp />". PHP_EOL;

            // build the move down button with all its attributes
            $attributes = array('onclick' => "{$this->_jsPrefix}moveDown(this.form.elements['_" . $selectName . "'], this.form.elements['" . $selectName . "']); return false;");
            $this->_downButtonAttributes = array_merge($this->_downButtonAttributes, $attributes);
            $attrStrDown = $this->_getAttrString($this->_downButtonAttributes);
            $strHtmlMoveDown = "<input$attrStrDown />". PHP_EOL;
        }

        // render all part of the multi select component with the template
        $strHtml = $this->_elementTemplate;

        // Prepare multiple labels
        $labels = $this->getLabel();
        if (is_array($labels)) {
            array_shift($labels);
        }
        // render extra labels, if any
        if (is_array($labels)) {
            foreach($labels as $key => $text) {
                $key  = is_int($key)? $key + 2: $key;
                $strHtml = str_replace("{label_{$key}}", $text, $strHtml);
                $strHtml = str_replace("<!-- BEGIN label_{$key} -->", '', $strHtml);
                $strHtml = str_replace("<!-- END label_{$key} -->", '', $strHtml);
            }
        }
        // clean up useless label tags
        if (strpos($strHtml, '{label_')) {
            $strHtml = preg_replace('/\s*<!-- BEGIN label_(\S+) -->.*<!-- END label_\1 -->\s*/i', '', $strHtml);
        }

        $placeHolders = array(
            '{stylesheet}', '{javascript}', '{class}',
            '{unselected}', '{selected}',
            '{add}', '{remove}',
            '{moveup}', '{movedown}'
        );
        $htmlElements = array(
            $this->getElementCss(false), $this->getElementJs(false), $this->_tableAttributes,
            $strHtmlUnselected, $strHtmlSelected . $strHtmlHidden,
            $strHtmlAdd, $strHtmlRemove,
            $strHtmlMoveUp, $strHtmlMoveDown
        );

        $strHtml = str_replace($placeHolders, $htmlElements, $strHtml);

        return $strHtml;
    }

    /**
     * Returns the javascript code generated to handle this element
     *
     * @param      boolean   $raw           (optional) html output with script tags or just raw data
     *
     * @access     public
     * @return     string
     * @see        setJsElement()
     * @since      0.4.0
     */
    function getElementJs($raw = true)
    {
        $js = '';
        $jsfuncName = $this->_jsPrefix . $this->_jsPostfix;
        if (!defined('HTML_QUICKFORM_ADVMULTISELECT_'.$jsfuncName.'_EXISTS')) {
             // We only want to include the javascript code once per form
            define('HTML_QUICKFORM_ADVMULTISELECT_'.$jsfuncName.'_EXISTS', true);

            $js .= "
/* begin javascript for HTML_QuickForm_advmultiselect */
function {$jsfuncName}(selectLeft, selectRight, selectHidden, action) {
    if (action == 'add') {
        menuFrom = selectLeft;
        menuTo = selectRight;
    }
    else {
        menuFrom = selectRight;
        menuTo = selectLeft;
    }
    // Don't do anything if nothing selected. Otherwise we throw javascript errors.
    if (menuFrom.selectedIndex == -1) {
        return;
    }

    // Add items to the 'TO' list.
    for (i=0; i < menuFrom.length; i++) {
        if (menuFrom.options[i].selected == true ) {
            menuTo.options[menuTo.length]= new Option(menuFrom.options[i].text, menuFrom.options[i].value);
        }
    }

    // Remove items from the 'FROM' list.
    for (i=(menuFrom.length - 1); i>=0; i--){
        if (menuFrom.options[i].selected == true ) {
            menuFrom.options[i] = null;
        }
    }
";
            if ($this->_sort === false) {
                $js .= "
    // Set the appropriate items as 'selected in the hidden select.
    // These are the values that will actually be posted with the form.
    {$this->_jsPrefix}updateHidden(selectHidden, selectRight);
}
";
            } else {
                $reverse = ($this->_sort === SORT_DESC) ? 'options.reverse();' : '';

                $js .= "
    // Sort list if required
    {$this->_jsPrefix}sortList(menuTo, {$this->_jsPrefix}compareText);

    // Set the appropriate items as 'selected in the hidden select.
    // These are the values that will actually be posted with the form.
    {$this->_jsPrefix}updateHidden(selectHidden, selectRight);
}

function {$this->_jsPrefix}sortList(list, compareFunction) {
    var options = new Array (list.options.length);
    for (var i = 0; i < options.length; i++) {
        options[i] = new Option (
            list.options[i].text,
            list.options[i].value,
            list.options[i].defaultSelected,
            list.options[i].selected
        );
    }
    options.sort(compareFunction);
    {$reverse}
    list.options.length = 0;
    for (var i = 0; i < options.length; i++) {
        list.options[i] = options[i];
    }
}

function {$this->_jsPrefix}compareText(option1, option2) {
    if (option1.text == option2.text) {
        return 0;
    }
    return option1.text < option2.text ? -1 : 1;
}
";
            }

            $js .= "
function {$this->_jsPrefix}updateHidden(h,r) {
    for (i=0; i < h.length; i++) {
        h.options[i].selected = false;
    }

    for (i=0; i < r.length; i++) {
        h.options[h.length] = new Option(r.options[i].text, r.options[i].value);
        h.options[h.length-1].selected = true;
    }
}

function {$this->_jsPrefix}moveUp(l,h) {
    var indice = l.selectedIndex;
    if (indice < 0) {
        return;
    }
    if (indice > 0) {
        {$this->_jsPrefix}moveSwap(l, indice, indice-1);
        {$this->_jsPrefix}updateHidden(h, l);
    }
}

function {$this->_jsPrefix}moveDown(l,h) {
    var indice = l.selectedIndex;
    if (indice < 0) {
        return;
    }
    if (indice < l.options.length-1) {
        {$this->_jsPrefix}moveSwap(l, indice, indice+1);
        {$this->_jsPrefix}updateHidden(h, l);
    }
}

function {$this->_jsPrefix}moveSwap(l,i,j) {
    var valeur = l.options[i].value;
    var texte = l.options[i].text;
    l.options[i].value = l.options[j].value;
    l.options[i].text = l.options[j].text;
    l.options[j].value = valeur;
    l.options[j].text = texte;
    l.selectedIndex = j
}

/* end javascript for HTML_QuickForm_advmultiselect */
";
            if ($raw !== true) {
                $js = '<script type="text/javascript">' . PHP_EOL
                    . '/* <![CDATA[ */' . $js . '/* ]]> */' . PHP_EOL
                    . '</script>';
            }
        }
        return $js;
    }
}

if (class_exists('HTML_QuickForm')) {
    HTML_QuickForm::registerElementType('advmultiselect', 'HTML/QuickForm/advmultiselect.php', 'HTML_QuickForm_advmultiselect');
}
?>