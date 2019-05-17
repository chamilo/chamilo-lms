<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * A concrete renderer for HTML_QuickForm, based on QuickForm 2.x built-in one
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
 * @author      Alexey Borzov <avb@php.net>
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @copyright   2001-2009 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     CVS: $Id$
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * A concrete renderer for HTML_QuickForm, based on QuickForm 2.x built-in one
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Alexey Borzov <avb@php.net>
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @version     Release: 3.2.11
 * @since       3.0
 */
class HTML_QuickForm_Renderer_Default extends HTML_QuickForm_Renderer
{
    private $form;
    private $customElementTemplate;

    /**
     * @return mixed
     */
    public function getCustomElementTemplate()
    {
        return $this->customElementTemplate;
    }

    /**
     * This template will be taken instead of the default templates by element
     * @param string $customElementTemplate
     */
    public function setCustomElementTemplate($customElementTemplate)
    {
        $this->customElementTemplate = $customElementTemplate;
    }

   /**
    * The HTML of the form
    * @var      string
    * @access   private
    */
    var $_html;

   /**
    * Header Template string
    * @var      string
    * @access   private
    */
    var $_headerTemplate =
        "\n\t<tr>\n\t\t<td style=\"white-space: nowrap; background-color: #CCCCCC;\" align=\"left\" valign=\"top\" colspan=\"2\"><b>{header}</b></td>\n\t</tr>";

   /**
    * Element template string
    * @var      string
    * @access   private
    */
    var $_elementTemplate =
        "\n\t<tr>\n\t\t<td align=\"right\" valign=\"top\"><!-- BEGIN required --><span style=\"color: #ff0000\">*</span><!-- END required --><b>{label}</b></td>\n\t\t<td valign=\"top\" align=\"left\"><!-- BEGIN error --><span style=\"color: #ff0000\">{error}</span><br /><!-- END error -->\t{element}</td>\n\t</tr>";

   /**
    * Form template string
    * @var      string
    * @access   private
    */
    var $_formTemplate =
        "\n<form{attributes}>\n<div>\n{hidden}<table border=\"0\">\n{content}\n</table>\n</div>\n</form>";

   /**
    * Required Note template string
    * @var      string
    * @access   private
    */
    var $_requiredNoteTemplate =
        "\n\t<tr>\n\t\t<td></td>\n\t<td align=\"left\" valign=\"top\">{requiredNote}</td>\n\t</tr>";

   /**
    * Array containing the templates for customised elements
    * @var      array
    * @access   private
    */
    var $_templates = array();

   /**
    * Array containing the templates for group wraps.
    *
    * These templates are wrapped around group elements and groups' own
    * templates wrap around them. This is set by setGroupTemplate().
    *
    * @var      array
    * @access   private
    */
    var $_groupWraps = array();

   /**
    * Array containing the templates for elements within groups
    * @var      array
    * @access   private
    */
    var $_groupTemplates = array();

   /**
    * True if we are inside a group
    * @var      bool
    * @access   private
    */
    var $_inGroup = false;

   /**
    * Array with HTML generated for group elements
    * @var      array
    * @access   private
    */
    var $_groupElements = array();

   /**
    * Template for an element inside a group
    * @var      string
    * @access   private
    */
    var $_groupElementTemplate = '';

   /**
    * HTML that wraps around the group elements
    * @var      string
    * @access   private
    */
    var $_groupWrap = '';

   /**
    * HTML for the current group
    * @var      string
    * @access   private
    */
    var $_groupTemplate = '';

   /**
    * Collected HTML of the hidden fields
    * @var      string
    * @access   private
    */
    var $_hiddenHtml = '';

   /**
    * Constructor
    *
    * @access public
    */
    public function __construct()
    {
        parent::__construct();
    } // end constructor

   /**
    * returns the HTML generated for the form
    *
    * @access public
    * @return string
    */
    public function toHtml()
    {
        // _hiddenHtml is cleared in finishForm(), so this only matters when
        // finishForm() was not called (e.g. group::toHtml(), bug #3511)
        return $this->_hiddenHtml . $this->_html;
    } // end func toHtml

   /**
    * Called when visiting a form, before processing any form elements
    *
    * @param    HTML_QuickForm  form object being visited
    * @access   public
    * @return   void
    */
    function startForm(&$form)
    {
        $this->setForm($form);

        $this->_html = '';
        $this->_hiddenHtml = '';
    }

    /**
     * @return FormValidator
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param mixed $form
     */
    public function setForm($form)
    {
        $this->form = $form;
    } // end func startForm



   /**
    * Called when visiting a form, after processing all form elements
    * Adds required note, form attributes, validation javascript and form content.
    *
    * @param    HTML_QuickForm  form object being visited
    * @access   public
    * @return   void
    */
    public function finishForm(&$form)
    {
        // add a required note, if one is needed
        if (!empty($form->_required) && !$form->_freezeAll) {
            $this->_html .= str_replace('{requiredNote}', $form->getRequiredNote(), $this->_requiredNoteTemplate);
        }
        // add form attributes and content
        $html = str_replace('{attributes}', $form->getAttributes(true), $this->_formTemplate);

        if (strpos($this->_formTemplate, '{hidden}')) {
            $html = str_replace('{hidden}', $this->_hiddenHtml, $html);
        } else {
            $this->_html .= $this->_hiddenHtml;
        }
        $this->_hiddenHtml = '';
        $this->_html = str_replace('{content}', $this->_html, $html);
        // add a validation script
        if ('' != ($script = $form->getValidationScript())) {
            $this->_html = $script . "\n" . $this->_html;
        }
    } // end func finishForm

   /**
    * Called when visiting a header element
    *
    * @param    HTML_QuickForm_header   header element being visited
    * @access   public
    * @return   void
    */
    function renderHeader(&$header)
    {
        $name = $header->getName();
        if (!empty($name) && isset($this->_templates[$name])) {
            $this->_html .= str_replace('{header}', $header->toHtml(), $this->_templates[$name]);
        } else {
            $this->_html .= str_replace('{header}', $header->toHtml(), $this->_headerTemplate);
        }
    } // end func renderHeader

   /**
    * Helper method for renderElement
    *
    * @param    HTML_QuickForm_element $element
    * @param    bool        Whether an element is required
    * @param    string      $required Error message associated with the element
    * @param    string      $error Label for ID
    * @access   private
    * @see      renderElement()
    * @return   string      Html for element
    */
    private function _prepareTemplate(HTML_QuickForm_element $element, $required, $error)
    {
        $name = $element->getName();
        $label = $element->getLabel();
        $labelForId = $element->getAttribute('id');
        $extraLabelClass = $element->getAttribute('extra_label_class');

        $icon = $element->getIconToHtml();

        if (is_array($label)) {
            $nameLabel = array_shift($label);
            // In some cases, label (coming from display_text) might be a
            // double-level array. In this case, take the first item of the
            // sub-array as label
            if (is_array($nameLabel)) {
                $nameLabel = $nameLabel[0];
            }
        } else {
            $nameLabel = $label;
        }

        $labelFor = !empty($labelForId) ? 'for="' . $labelForId . '"' : 'for="' . $element->getName() . '"';

        if (isset($this->_templates[$name])) {
            // Custom template
            $html = str_replace('{label}', $nameLabel, $this->_templates[$name]);
        } else {
            $customElementTemplate = $this->getCustomElementTemplate();

            if (empty($customElementTemplate)) {
                if (method_exists($element, 'getTemplate')) {
                    $template = $element->getTemplate(
                        $this->getForm()->getLayout()
                    );
                    if ($element->isFrozen()) {
                        $customFrozentemplate = $element->getCustomFrozenTemplate();
                        if (!empty($customFrozentemplate)) {
                            $template = $customFrozentemplate;
                        }
                    }
                } else {
                    $template = $this->getForm()->getDefaultElementTemplate();
                }
            } else {
                $template = $customElementTemplate;
            }
            $html = str_replace('{label}', $nameLabel, $template);
        }
        $html = str_replace('{label-for}', $labelFor, $html);
        $html = str_replace('{icon}', $icon, $html);
        $html = str_replace('{extra_label_class}', $extraLabelClass, $html);

        if ($required) {
            $html = str_replace('<!-- BEGIN required -->', '', $html);
            $html = str_replace('<!-- END required -->', '', $html);
        } else {
            $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN required -->.*<!-- END required -->([ \t\n\r]*)?/isU", '', $html);
        }

        if (isset($error)) {
            $html = str_replace('{error}', $error, $html);
            $html = str_replace('{error_class}', 'error has-error', $html);
            $html = str_replace('<!-- BEGIN error -->', '', $html);
            $html = str_replace('<!-- END error -->', '', $html);
        } else {
            $html = str_replace('{error_class}', '', $html);
            $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN error -->.*<!-- END error -->([ \t\n\r]*)?/isU", '', $html);
        }
        if (is_array($label)) {
            foreach ($label as $key => $text) {
                $key  = is_int($key)? $key + 2: $key;
                $html = str_replace("{label_{$key}}", $text, $html);
                $html = str_replace("<!-- BEGIN label_{$key} -->", '', $html);
                $html = str_replace("<!-- END label_{$key} -->", '', $html);
            }
        }
        if (strpos($html, '{label_')) {
            $html = preg_replace('/\s*<!-- BEGIN label_(\S+) -->.*<!-- END label_\1 -->\s*/is', '', $html);
        }

        return $html;
    }

   /**
    * Renders an element Html
    * Called when visiting an element
    *
    * @param HTML_QuickForm_element form element being visited
    * @param bool                   Whether an element is required
    * @param string                 An error message associated with an element
    * @access public
    * @return void
    */
    public function renderElement(&$element, $required, $error)
    {
        if (!$this->_inGroup) {
            $html = $this->_prepareTemplate(
                $element,
                $required,
                $error
            );
            $this->_html .= str_replace('{element}', $element->toHtml(), $html);
        } elseif (!empty($this->_groupElementTemplate)) {
            $html = str_replace('{label}', $element->getLabel(), $this->_groupElementTemplate);
            $html = str_replace('{label-for}', $element->getLabelFor(), $this->_groupElementTemplate);
            if ($required) {
                $html = str_replace('<!-- BEGIN required -->', '', $html);
                $html = str_replace('<!-- END required -->', '', $html);
            } else {
                $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN required -->.*<!-- END required -->([ \t\n\r]*)?/isU", '', $html);
            }
            $this->_groupElements[] = str_replace('{element}', $element->toHtml(), $html);
        } else {
            $this->_groupElements[] = $element->toHtml();
        }
    } // end func renderElement

   /**
    * Renders an hidden element
    * Called when visiting a hidden element
    *
    * @param HTML_QuickForm_element     form element being visited
    * @access public
    * @return void
    */
    function renderHidden(&$element)
    {
        $this->_hiddenHtml .= $element->toHtml() . "\n";
    } // end func renderHidden

   /**
    * Called when visiting a raw HTML/text pseudo-element
    *
    * @param  HTML_QuickForm_html   element being visited
    * @access public
    * @return void
    */
    function renderHtml(&$data)
    {
        $this->_html .= $data->toHtml();
    } // end func renderHtml

   /**
    * Called when visiting a group, before processing any group elements
    *
    * @param HTML_QuickForm_group   group being visited
    * @param bool       Whether a group is required
    * @param string     An error message associated with a group
    * @access public
    * @return void
    */
    function startGroup(&$group, $required, $error)
    {
        $name = $group->getName();
        $this->_groupTemplate        = $this->_prepareTemplate($group, $required, $error);
        $this->_groupElementTemplate = empty($this->_groupTemplates[$name])? '': $this->_groupTemplates[$name];
        $this->_groupWrap            = empty($this->_groupWraps[$name])? '': $this->_groupWraps[$name];
        $this->_groupElements        = array();
        $this->_inGroup              = true;
    } // end func startGroup

   /**
    * Called when visiting a group, after processing all group elements
    *
    * @param    HTML_QuickForm_group    group being visited
    * @access   public
    * @return   void
    */
    function finishGroup(&$group)
    {
        $separator = $group->_separator;
        if (is_array($separator)) {
            $count = count($separator);
            $html  = '';
            for ($i = 0; $i < count($this->_groupElements); $i++) {
                $html .= (0 == $i? '': $separator[($i - 1) % $count]) . $this->_groupElements[$i];
            }
        } else {
            if (is_null($separator)) {
                $separator = '&nbsp;';
            }
            $html = implode((string)$separator, $this->_groupElements);
        }
        if (!empty($this->_groupWrap)) {
            $html = str_replace('{content}', $html, $this->_groupWrap);
        }
        $this->_html   .= str_replace('{element}', $html, $this->_groupTemplate);
        $this->_inGroup = false;
    } // end func finishGroup

    /**
     * Sets element template
     *
     * @param       string      The HTML surrounding an element
     * @param       string      (optional) Name of the element to apply template for
     * @access      public
     * @return      void
     */
    function setElementTemplate($html, $element = null)
    {
        if (is_null($element)) {
            $this->_elementTemplate = $html;
        } else {
            $this->_templates[$element] = $html;
        }
    } // end func setElementTemplate


    /**
     * Sets template for a group wrapper
     *
     * This template is contained within a group-as-element template
     * set via setTemplate() and contains group's element templates, set
     * via setGroupElementTemplate()
     *
     * @param       string      The HTML surrounding group elements
     * @param       string      Name of the group to apply template for
     * @access      public
     * @return      void
     */
    function setGroupTemplate($html, $group)
    {
        $this->_groupWraps[$group] = $html;
    } // end func setGroupTemplate

    /**
     * Sets element template for elements within a group
     *
     * @param       string      The HTML surrounding an element
     * @param       string      Name of the group to apply template for
     * @access      public
     * @return      void
     */
    function setGroupElementTemplate($html, $group)
    {
        $this->_groupTemplates[$group] = $html;
    } // end func setGroupElementTemplate

    /**
     * Sets header template
     *
     * @param       string      The HTML surrounding the header
     * @access      public
     * @return      void
     */
    function setHeaderTemplate($html)
    {
        $this->_headerTemplate = $html;
    } // end func setHeaderTemplate

    /**
     * Sets form template
     *
     * @param     string    The HTML surrounding the form tags
     * @access    public
     * @return    void
     */
    function setFormTemplate($html) {
        $this->_formTemplate = $html;
    } // end func setFormTemplate

    /**
     * Sets the note indicating required fields template
     *
     * @param       string      The HTML surrounding the required note
     * @access      public
     * @return      void
     */
    function setRequiredNoteTemplate($html)
    {
        $this->_requiredNoteTemplate = $html;
    } // end func setRequiredNoteTemplate

    /**
     * Clears all the HTML out of the templates that surround notes, elements, etc.
     * Useful when you want to use addData() to create a completely custom form look
     *
     * @access  public
     * @return  void
     */
    function clearAllTemplates()
    {
        $this->setElementTemplate('{element}');
        $this->setFormTemplate("\n\t<form{attributes}>{content}\n\t</form>\n");
        $this->setRequiredNoteTemplate('');
        $this->_templates = array();
    } // end func clearAllTemplates
} // end class HTML_QuickForm_Renderer_Default
