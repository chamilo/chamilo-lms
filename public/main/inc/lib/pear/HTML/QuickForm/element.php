<?php

/**
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category    HTML
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @author      Alexey Borzov <avb@php.net>
 * @copyright   2001-2009 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     CVS: $Id: element.php,v 1.37 2009/04/04 21:34:02 avb Exp $
 * @link        http://pear.php.net/package/HTML_QuickForm
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 3.2.11
 * @since       1.0
 * @abstract
 */
class HTML_QuickForm_element extends HTML_Common
{
    private $layout;
    private $icon;
    private $template;
    private $customFrozenTemplate = '';
    protected $inputSize;

    /**
     * Label of the field
     * @var       string
     */
    public $_label = '';

    /**
     * Label "for" a field... (Chamilo LMS customization)
     * @var     string
     * @access  private
     */
    public $_label_for = '';

    /**
     * Form element type
     * @var       string
     * @since     1.0
     * @access    private
     */
    public $_type = '';

    /**
     * Flag to tell if element is frozen
     * @var       boolean
     * @since     1.0
     * @access    private
     */
    public $_flagFrozen = false;

    /**
     * Does the element support persistant data when frozen
     * @var       boolean
     * @since     1.3
     * @access    private
     */
    public $_persistantFreeze = false;
    protected $columnsSize;

    /**
     * @param string     Name of the element
     * @param string|array      Label(s) for the element
     * @param mixed      Associative array of tag attributes or HTML attributes name="value" pairs
     */
    public function __construct($elementName = null, $elementLabel = null, $attributes = null)
    {
        parent::__construct($attributes);
        if (isset($elementName)) {
            $this->setName($elementName);
        }
        if (isset($elementLabel)) {
            $labelFor = '';
            // Default Inputs generate this
            if (!empty($attributes['id'])) {
                $labelFor = $attributes['id'];
            }
            // Default Labels generate this
            if (!empty($attributes['for'])) {
                $labelFor = $attributes['for'];
            }
            $this->setLabel($elementLabel, $labelFor);
        }
    }

     /**
     * @return null
     */
    public function getColumnsSize()
    {
        return $this->columnsSize;
    }

    /**
     * @param null $columnsSize
     */
    public function setColumnsSize($columnsSize)
    {
        $this->columnsSize = $columnsSize;
    }

    /**
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @return string
     */
    public function getIconToHtml()
    {
        return $this->icon;
    }

    /**
     * @param mixed $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * Returns the current API version
     *
     * @since     1.0
     * @access    public
     * @return    float
     */
    public function apiVersion()
    {
        return 3.2;
    }

    /**
     * Returns element type
     *
     * @since     1.0
     * @return    string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Sets the input field name
     *
     * @param     string    $name   Input field name attribute
     * @since     1.0
     * @return    void
     */
    public function setName($name)
    {
    }

    /**
     * Returns the element name
     *
     * @since     1.0
     * @return    string
     */
    public function getName()
    {
    }

    /**
     * Sets the value of the form element
     *
     * @param     string    $value      Default value of the form element
     * @since     1.0
     * @return    void
     */
    public function setValue($value)
    {
    }

    /**
     * Returns the value of the form element
     *
     * @since     1.0
     * @access    public
     * @return    mixed
     */
    public function getValue()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getCleanValue(): string
    {
        return $this->cleanValueFromParameter($this->getValue());
    }

    /**
     * @param string $value
     */
    public function cleanValueFromParameter($value): string
    {
        return @htmlspecialchars($value, ENT_COMPAT, HTML_Common::charset());
    }

    /**
     * Freeze the element so that only its value is returned
     */
    public function freeze()
    {
        $this->_flagFrozen = true;
    }

   /**
    * Unfreezes the element so that it becomes editable
    *
    * @since  3.2.4
    */
    public function unfreeze()
    {
        $this->_flagFrozen = false;
    }

    /**
     * Returns the value of field without HTML tags
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    public function getFrozenHtml()
    {
        $value = $this->getValue();
        // Modified by Ivan Tcholakov, 16-MAR-2010.
        //return ('' != $value? htmlspecialchars($value): '&nbsp;') .
        //       $this->_getPersistantData();
        if (!empty($value)) {
            $value = $this->getCleanValue();
        } else {
            $value = '&nbsp;';
        }

        $value .= $this->_getPersistantData();

        return '<span class="freeze">'.$value.'</span>';
    }

   /**
    * Used by getFrozenHtml() to pass the element's value if _persistantFreeze is on
    *
    * @access private
    * @return string
    */
    function _getPersistantData()
    {
        if (!$this->_persistantFreeze) {
            return '';
        }

        $id = $this->getAttribute('id');

        return '<input' . $this->_getAttrString(array(
                   'type'  => 'hidden',
                   'name'  => $this->getName(),
                   'value' => $this->getValue()
               ) + (isset($id)? array('id' => $id): array())) . ' />';
    }

    /**
     * Returns whether or not the element is frozen
     *
     * @since     1.3
     * @return    bool
     */
    public function isFrozen()
    {
        return $this->_flagFrozen;
    }

    /**
     * Sets wether an element value should be kept in an hidden field
     * when the element is frozen or not
     *
     * @param     bool    $persistant   True if persistant value
     * @since     2.0
     */
    public function setPersistantFreeze($persistant=false)
    {
        $this->_persistantFreeze = $persistant;
    }

    /**
     * Sets display text for the element
     *
     * @param     string    $label  Display text for the element
     * @param     string    $label_for Optionally add a "for" attribute
     * @since     1.3
     */
    public function setLabel($label, $labelFor = null)
    {
        $this->_label = $label;
        if (!empty($labelFor)) {
            $this->_label_for = $labelFor;
        }
    }

    /**
     * Returns display text for the element
     *
     * @since     1.3
     * @return    string
     */
    public function getLabel()
    {
        return $this->_label;
    }

    /**
     * Returns "for" attribute for the element
     *
     * @return    string
     */
    public function getLabelFor()
    {
        return $this->_label_for;
    }

    /**
     * Tries to find the element value from the values array
     *
     * @since     2.7
     * @return    mixed
     */
    protected function _findValue(&$values)
    {
        if (empty($values)) {
            return null;
        }
        $elementName = $this->getName();
        if (isset($values[$elementName])) {
            return $values[$elementName];
        } elseif (strpos($elementName, '[')) {
            // Fix checkbox
            if ($this->_type === 'checkbox') {
                $attributeValue = $this->getAttribute('value');
                $elementNameCheckBox = str_replace('[]', '', $elementName);
                if (isset($values[$elementNameCheckBox]) &&
                    is_array($values[$elementNameCheckBox])
                ) {
                    if (in_array($attributeValue, $values[$elementNameCheckBox])) {
                        return true;
                    }

                    return false;
                }
            }

            /*$replacedName = str_replace(
                array('\\', '\'', ']', '['),
                array('\\\\', '\\\'', '', "']['"),
                $elementName
            );
            $myVar = "['$replacedName']";
            $result =  eval("return (isset(\$values$myVar)) ? \$values$myVar : null;");
            //var_dump($result);
            return $result;*/

            // $elementName = extra_statusocial[extra_statusocial] ;
            preg_match('/(.*)\[(.*)\]/', $elementName, $matches);

            // Getting extra_statusocial
            $elementKey = $matches[1];
            $secondElementKey = '';
            if (isset($matches[2])) {
                $secondElementKey = $matches[2];
            }

            if (isset($values[$elementKey]) && isset($values[$elementKey][$secondElementKey])) {
                return $values[$elementKey][$secondElementKey];
            }
        }

        return null;
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param     string    $event  Name of event
     * @param     mixed     $arg    event arguments
     * @param     object    &$caller calling object
     * @since     1.0
     */
    public function onQuickFormEvent($event, $arg, &$caller)
    {
        switch ($event) {
            case 'createElement':
                //$className = get_class($this);
                //$this->$className($arg[0], $arg[1], $arg[2], $arg[3], $arg[4], $arg[5], $arg[6]);
                break;
            case 'addElement':
                $this->onQuickFormEvent('createElement', $arg, $caller);
                $this->onQuickFormEvent('updateValue', null, $caller);
                break;
            case 'updateValue':
                // constant values override both default and submitted ones
                // default values are overridden by submitted.
                $value = $this->_findValue($caller->_constantValues);
                if (null === $value) {
                    $value = $this->_findValue($caller->_submitValues);
                    if (null === $value) {
                        $value = $this->_findValue($caller->_defaultValues);
                    }
                }
                if (null !== $value) {
                    $this->setValue($value);
                }
                break;
            case 'setGroupValue':
                $this->setValue($arg);
        }

        return true;
    }

   /**
    * Accepts a renderer
    *
    * @param HTML_QuickForm_Renderer    renderer object
    * @param bool                       Whether an element is required
    * @param string                     An error message associated with an element
    * @return void
    */
    public function accept(&$renderer, $required = false, $error = null)
    {
        $renderer->renderElement($this, $required, $error);
    }

   /**
    * Automatically generates and assigns an 'id' attribute for the element.
    *
    * Currently used to ensure that labels work on radio buttons and
    * checkboxes. Per idea of Alexander Radivanovich.
    *
    * @access private
    * @return void
    */
    public function _generateId()
    {
        static $idx = 1;

        if (!$this->getAttribute('id')) {
            $this->updateAttributes(['id' => 'qf_'.substr(md5(microtime().$idx++), 0, 6)]);
        }
    }

   /**
    * Returns a 'safe' element's value
    *
    * @param  array   array of submitted values to search
    * @param  bool    whether to return the value as associative array
    * @access public
    * @return mixed
    */
    public function exportValue(&$submitValues, $assoc = false)
    {
        $value = $this->_findValue($submitValues);
        if (null === $value) {
            $value = $this->getValue();
        }

        return $this->_prepareValue($value, $assoc);
    }

   /**
    * Used by exportValue() to prepare the value for returning
    *
    * @param  mixed   the value found in exportValue()
    * @param  bool    whether to return the value as associative array
    * @access private
    * @return mixed
    */
    public function _prepareValue($value, $assoc)
    {
        if (null === $value) {
            return null;
        } elseif (!$assoc) {
            return $value;
        } else {
            $name = $this->getName();
            if (!strpos($name, '[')) {
                return array($name => $value);
            } else {
                $valueAry = array();
                $myIndex  = "['" . str_replace(
                                array('\\', '\'', ']', '['), array('\\\\', '\\\'', '', "']['"),
                                $name
                            ) . "']";
                eval("\$valueAry$myIndex = \$value;");
                return $valueAry;
            }
        }
    }

    /**
     * @param mixed $template
     * @return HTML_QuickForm_element
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomFrozenTemplate()
    {
        return $this->customFrozenTemplate;
    }

    /**
     * @param string $customFrozenTemplate
     * @return HTML_QuickForm_element
     */
    public function setCustomFrozenTemplate($customFrozenTemplate)
    {
        $this->customFrozenTemplate = $customFrozenTemplate;

        return $this;
    }

    /**
     * @return null
     */
    public function getInputSize()
    {
        return $this->inputSize;
    }

    /**
     * @param null $inputSize
     */
    public function setInputSize($inputSize)
    {
        $this->inputSize = $inputSize;
    }

    /**
     * @return array
     */
    public function calculateSize()
    {
        $size = $this->getColumnsSize();

        if (empty($size)) {
            $sizeTemp = $this->getInputSize();
            if (empty($size)) {
                $sizeTemp = 8;
            }
            $size = array(2, $sizeTemp, 2);
        } else {
            if (is_array($size)) {
                if (count($size) != 3) {
                    $sizeTemp = $this->getInputSize();
                    if (empty($size)) {
                        $sizeTemp = 8;
                    }
                    $size = array(2, $sizeTemp, 2);
                }
            } else {
                // else just keep the $size array as received
                $size = array(2, (int) $size, 2);
            }
        }

        return $size;
    }

    public function getTemplate(string $layout): string
    {
        $size = $this->calculateSize();
        $attributes = $this->getAttributes();

        $hasBottomLabel = is_array($this->getLabel());
        $height = 'h-4';
        if ($hasBottomLabel) {
            $height = 'h-8';
        }

        $template = '<label {label-for}>{label}</label>
                     <div class="input-group">
                         {icon}
                         {element}
                     </div>';

        switch ($layout) {
            case FormValidator::LAYOUT_BOX_SEARCH:
            case FormValidator::LAYOUT_INLINE:
                // <label {label-for} >
                //         <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                //           {label}
                //         </label>
                $template = '{element}';
                break;
            case FormValidator::LAYOUT_GRID:
                $template = '
                <div class="form-group {error_class}">
                    <label {label-for} >
                        <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                        {label}
                    </label>
                    {element}
                </div>';
                break;
            case FormValidator::LAYOUT_HORIZONTAL:
                $template = '
                <div class="mb-6 '.$size[0].' {error_class}">
                    <label {label-for} class="
                        '.$height.'
                        block
                        text-sm
                        font-medium
                        text-gray-700
                        mb-2
                    " >
                        <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                        {label}
                    </label>
                    <div class=" '.$size[1].'">
                        {icon}
                        {element}
                        <!-- BEGIN label_2 -->
                            <p class="help-block">{label_2}</p>
                        <!-- END label_2 -->

                         <!-- BEGIN label_3 -->
                            <p class="help-block">{label_3}</p>
                        <!-- END label_3 -->

                        <!-- BEGIN error -->
                            <span class="help-inline help-block">{error}</span>
                        <!-- END error -->
                    </div>
                </div>';
                break;
            case FormValidator::LAYOUT_BOX_NO_LABEL:
                if (isset($attributes['custom']) && $attributes['custom'] == true) {
                    $template = '
                        <div class="input-group">
                            {icon}
                            {element}
                            <div class="input-group-btn">
                                <button class="btn btn--plain" type="submit">
                                    <em class="fa fa-search"></em>
                                </button>
                            </div>
                        </div>
                    ';
                }
                break;
        }

        return $template;
    }
}
