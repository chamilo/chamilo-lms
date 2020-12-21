<?php

/**
 * Class to dynamically create an HTML SELECT
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
 * @author      Alexey Borzov <avb@php.net>
 * @copyright   2001-2009 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     CVS: $Id: select.php,v 1.34 2009/04/04 21:34:04 avb Exp $
 * @link        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * Class to dynamically create an HTML SELECT
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 3.2.11
 * @since       1.0
 */
class HTML_QuickForm_select extends HTML_QuickForm_element
{
    /**
     * Contains the select options
     *
     * @var       array
     * @since     1.0
     * @access    private
     */
    protected $_options = [];
    private $_optgroups = [];

    /**
     * Default values of the SELECT
     *
     * @var       array
     * @since     1.0
     * @access    private
     */
    protected $_values = [];

    /**
     * Class constructor
     *
     * @param     string    $elementName Select name attribute
     * @param     mixed     $elementLabel Label(s) for the select
     * @param     mixed     $options Data to be used to populate options
     * @param     mixed     $attributes Either a typical HTML attribute string or an associative array
     * @since     1.0
     * @access    public
     */
    public function __construct(
        $elementName,
        $elementLabel = '',
        $options = null,
        $attributes = null
    ) {
        $addBlank = '';
        if (is_array($attributes) || empty($attributes)) {
            $oldClass = '';
            if (!empty($attributes['class'])) {
                $oldClass = $attributes['class'];
            }
            if (empty($attributes)) {
                $attributes = []; // Initialize variable to avoid warning in PHP 7.1
            }
            $attributes['class'] = $oldClass . ' selectpicker form-control';
            $attributes['data-live-search'] = 'true';

            if (isset($attributes['disable_js']) && $attributes['disable_js']) {
                $attributes['class'] = 'form-control';
                $attributes['data-live-search'] = '';
            }

            if (isset($attributes['extra_class']) && $attributes['extra_class']) {
                $attributes['class'] .= ' '.$attributes['extra_class'];
                unset($attributes['extra_class']);
            }

            if (isset($attributes['placeholder'])) {
                $addBlank =  $attributes['placeholder'];
            }
        }
        $columnsSize = isset($attributes['cols-size']) ? $attributes['cols-size'] : null;
        $this->setColumnsSize($columnsSize);
        parent::__construct($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_type = 'select';

        if ($addBlank !== '') {
            if (isset($options)) {
                $options = ['' => $addBlank] + $options;
            } else {
                $options = ['' => $addBlank];
            }
        }
        if (isset($options)) {
            $this->load($options);
        }
    }

    public function setOptions($options)
    {
        $this->load($options);
    }

     /**
     * Loads options from different types of data sources
     *
     * This method is a simulated overloaded method.  The arguments, other than the
     * first are optional and only mean something depending on the type of the first argument.
     * If the first argument is an array then all arguments are passed in order to loadArray.
     * If the first argument is a db_result then all arguments are passed in order to loadDbResult.
     * If the first argument is a string or a DB connection then all arguments are
     * passed in order to loadQuery.
     * @param     mixed     $options     Options source currently supports assoc array or DB_result
     * @param     mixed     $param1     (optional) See function detail
     * @param     mixed     $param2     (optional) See function detail
     * @param     mixed     $param3     (optional) See function detail
     * @param     mixed     $param4     (optional) See function detail
     * @since     1.1
     * @access    public
     * @return    PEAR_Error on error or true
     * @throws    PEAR_Error
     */
    protected function load(&$options, $param1=null, $param2=null, $param3=null, $param4=null)
    {
        switch (true) {
            case is_array($options):
                return $this->loadArray($options, $param1);
                break;
        }
    }

    /**
     * Loads the options from an associative array
     *
     * @param     array    $arr     Associative array of options
     * @param     mixed    $values  (optional) Array or comma delimited string of selected values
     * @since     1.0
     * @access    public
     * @return    PEAR_Error on error or true
     * @throws    PEAR_Error
     */
    private function loadArray($arr, $values = null)
    {
        if (!is_array($arr)) {
            return false;
        }
        if (isset($values)) {
            $this->setSelected($values);
        }
        foreach ($arr as $key => $val) {
            // Fix in order to use list of entities.
            if (is_object($val)) {
                $key = $val->getId();
                $val = $val->__toString();
            }

            // Warning: new API since release 2.3
            $this->addOption($val, $key);
        }

        return true;
    }

    /**
     * Returns the current API version
     *
     * @since     1.0
     * @access    public
     * @return    double
     */
    function apiVersion()
    {
        return 2.3;
    }

    /**
     * Sets the default values of the select box
     *
     * @param     mixed    $values  Array or comma delimited string of selected values
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function setSelected($values)
    {
        if (is_string($values) && $this->getMultiple()) {
            $values = explode('[ ]?,[ ]?', $values);
        }
        if (is_array($values)) {
            $this->_values = array_values($values);
        } else {
            $this->_values = array($values);
        }
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
     * Returns the element name (possibly with brackets appended)
     *
     * @since     1.0
     * @access    public
     * @return    string
     */
    public function getPrivateName()
    {
        if ($this->getAttribute('multiple')) {
            return $this->getName().'[]';
        }

        return $this->getName();
    }

    /**
     * Sets the value of the form element
     *
     * @param     mixed    $values  Array or comma delimited string of selected values
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function setValue($value)
    {
        $this->setSelected($value);
    }

    /**
     * Returns an array of the selected values
     *
     * @since     1.0
     * @access    public
     * @return    array of selected values
     */
    public function getValue()
    {
        return $this->_values;
    }

    /**
     * Sets the select field size, only applies to 'multiple' selects
     *
     * @param     int    $size  Size of select  field
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function setSize($size)
    {
        $this->updateAttributes(array('size' => $size));
    }

    /**
     * Returns the select field size
     *
     * @since     1.0
     * @access    public
     * @return    int
     */
    public function getSize()
    {
        return $this->getAttribute('size');
    }

    /**
     * Sets the select mutiple attribute
     *
     * @param     bool    $multiple  Whether the select supports multi-selections
     * @since     1.2
     * @access    public
     * @return    void
     */
    public function setMultiple($multiple)
    {
        if ($multiple) {
            $this->updateAttributes(array('multiple' => 'multiple'));
        } else {
            $this->removeAttribute('multiple');
        }
    }

    /**
     * Returns the select mutiple attribute
     *
     * @since     1.2
     * @access    public
     * @return    bool    true if multiple select, false otherwise
     */
    public function getMultiple()
    {
        return (bool) $this->getAttribute('multiple');
    }

    /**
     * Adds a new OPTION to the SELECT
     *
     * @param     string    $text       Display text for the OPTION
     * @param     string    $value      Value for the OPTION
     * @param     mixed     $attributes Either a typical HTML attribute string
     *                                  or an associative array
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function addOption($text, $value, $attributes = null, $return_array = false)
    {
        if (null === $attributes) {
            $attributes = array('value' => (string)$value);
        } else {
            $attributes = $this->_parseAttributes($attributes);
            if (isset($attributes['selected'])) {
                // the 'selected' attribute will be set in toHtml()
                $this->_removeAttr('selected', $attributes);
                if (is_null($this->_values)) {
                    $this->_values = array($value);
                } elseif (!in_array($value, $this->_values)) {
                    $this->_values[] = $value;
                }
            }
            $this->_updateAttrArray($attributes, array('value' => (string)$value));
        }
        if ($return_array) {
            return array('text' => $text, 'attr' => $attributes);
        } else {
            $this->_options[] = array('text' => $text, 'attr' => $attributes);
        }
    }

    /**
     * Adds a new OPTION to the SELECT
     *
     * @param     string    $text       Display text for the OPTION
     * @param     string    $value      Value for the OPTION
     * @param     mixed     $attributes Either a typical HTML attribute string
     *                                  or an associative array
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function addOptGroup($options, $label)
    {
        foreach ($options as $option) {
            $this->addOption($option['text'], $option['value'], $option, true);
        }
        $this->_optgroups[] = array('label' => $label, 'options' => $options);
    }

    /**
     * Returns the SELECT in HTML
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
            $tabs = $this->_getTabs();
            $strHtml = '';
            if ($this->getComment() != '') {
                $strHtml .= $tabs . '<!-- ' . $this->getComment() . " //-->\n";
            }

            if (!$this->getMultiple()) {
                $attrString = $this->_getAttrString($this->_attributes);
            } else {
                $myName = $this->getName();
                $this->setName($myName . '[]');
                $attrString = $this->_getAttrString($this->_attributes);
                $this->setName($myName);
            }

            $strHtml .= $tabs . '<select ' . $attrString . ">\n";
            $strValues = is_array($this->_values)? array_map('strval', $this->_values): array();

            foreach ($this->_options as $option) {
                if (!empty($strValues) && in_array($option['attr']['value'], $strValues, true)) {
                    $option['attr']['selected'] = 'selected';
                }
                $strHtml .= $tabs . "<option" . $this->_getAttrString($option['attr']) . '>' .
                    $option['text'] . "</option>";
            }
            foreach ($this->_optgroups as $optgroup) {
                $strHtml .= $tabs . '<optgroup label="' . $optgroup['label'] . '">';
                foreach ($optgroup['options'] as $option) {
                    $text = $option['text'];
                    unset($option['text']);

                    if (!empty($strValues) && in_array($option['value'], $strValues)) {
                        $option['selected'] = 'selected';
                    }

                    $strHtml .= $tabs . " <option" . $this->_getAttrString($option) . '>' .$text . "</option>";
                }
                $strHtml .= "</optgroup>";
            }

            return $strHtml.$tabs.'</select>';
        }
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
        $value = array();
        if (is_array($this->_values)) {
            foreach ($this->_values as $key => $val) {
                for ($i = 0, $optCount = count($this->_options); $i < $optCount; $i++) {
                    if (0 == strcmp($val, $this->_options[$i]['attr']['value'])) {
                        $value[$key] = $this->_options[$i]['text'];
                        break;
                    }
                }
            }
        }
        $html = empty($value)? '&nbsp;': join('<br />', $value);
        if ($this->_persistantFreeze) {
            $name = $this->getPrivateName();
            // Only use id attribute if doing single hidden input
            if (1 == count($value)) {
                $id     = $this->getAttribute('id');
                $idAttr = isset($id)? array('id' => $id): array();
            } else {
                $idAttr = array();
            }
            foreach ($value as $key => $item) {
                $html .= '<input' . $this->_getAttrString(array(
                             'type'  => 'hidden',
                             'name'  => $name,
                             'value' => $this->_values[$key]
                         ) + $idAttr) . ' />';
            }
        }

        return $html;
    }

   /**
    * We check the options and return only the values that _could_ have been
    * selected. We also return a scalar value if select is not "multiple"
    */
    public function exportValue(&$submitValues, $assoc = false)
    {
        $value = $this->_findValue($submitValues);
        if (is_null($value)) {
            $value = $this->getValue();
        } elseif(!is_array($value)) {
            $value = array($value);
        }
        if (is_array($value) && !empty($this->_options)) {
            $cleanValue = null;
            foreach ($value as $v) {
                for ($i = 0, $optCount = count($this->_options); $i < $optCount; $i++) {
                    if (0 == strcmp($v, $this->_options[$i]['attr']['value'])) {
                        $cleanValue[] = $v;
                        break;
                    }
                }
            }
        } else {
            $cleanValue = $value;
        }
        if (is_array($cleanValue) && !$this->getMultiple()) {
            if (empty($cleanValue)) {
                return $this->_prepareValue(null, $assoc);
            }

            return $this->_prepareValue($cleanValue[0], $assoc);
        } else {
            return $this->_prepareValue($cleanValue, $assoc);
        }
    }

    public function onQuickFormEvent($event, $arg, &$caller)
    {
        if ('updateValue' === $event) {
            $value = $this->_findValue($caller->_constantValues);
            if (null === $value) {
                $value = $this->_findValue($caller->_submitValues);
                // Fix for bug #4465 & #5269
                // XXX: should we push this to element::onQuickFormEvent()?
                if (null === $value && (!$caller->isSubmitted() || !$this->getMultiple())) {
                    $value = $this->_findValue($caller->_defaultValues);
                }
            }
            if (null !== $value) {
                $this->setValue($value);
            }
            return true;
        }

        return parent::onQuickFormEvent($event, $arg, $caller);
    }

    /**
     * @param FormValidator $form
     */
    public function updateSelectWithSelectedOption(FormValidator $form)
    {
        $id = $this->getAttribute('id');
        $form->addHtml(
            '<script>
                $(function(){
                    var optionClass = $("#'.$id.'").find("option:checked").attr("class");
                    $("#'.$id.'").attr("class", "form-control " + optionClass);
                    $("#'.$id.'").on("change", function() {
                        var optionClass = ($(this).find("option:checked").attr("class"));
                        $(this).attr("class", "form-control " + optionClass);
                    });
                });
            </script>'
        );
    }

    /**
     * @param string $layout
     *
     * @return string
     */
    public function getTemplate($layout)
    {
        $size = $this->calculateSize();

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
                    <label {label-for}  class="col-sm-'.$size[0].' control-label  {extra_label_class}" >
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
                        <div class="input-group">
                            {icon}
                            {element}
                        </div>';
                break;
            case FormValidator::LAYOUT_GRID:
            case FormValidator::LAYOUT_BOX:
                return '
                        <div class="form-group">
                            <label>{label}</label>
                            {icon}
                            {element}
                        </div>';
                break;
        }
    }
}
