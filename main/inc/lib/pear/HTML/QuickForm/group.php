<?php

/**
 * HTML class for a form element group.
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
 *
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @author      Alexey Borzov <avb@php.net>
 * @copyright   2001-2009 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 *
 * @version     CVS: $Id: group.php,v 1.40 2009/04/04 21:34:03 avb Exp $
 *
 * @see        http://pear.php.net/package/HTML_QuickForm
 */

/**
 * HTML class for a form element group.
 *
 * @category    HTML
 *
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @author      Alexey Borzov <avb@php.net>
 *
 * @version     Release: 3.2.11
 *
 * @since       1.0
 */
class HTML_QuickForm_group extends HTML_QuickForm_element
{
    /**
     * Name of the element.
     *
     * @var string
     *
     * @since     1.0
     */
    private $_name = '';

    /**
     * Array of grouped elements.
     *
     * @var array
     *
     * @since     1.0
     */
    private $_elements = [];

    /**
     * String to separate elements.
     *
     * @var mixed
     *
     * @since     2.5
     */
    public $_separator = null;

    /**
     * Required elements in this group.
     *
     * @var array
     *
     * @since     2.5
     */
    private $_required = [];

    /**
     * Whether to change elements' names to $groupName[$elementName] or leave them as is.
     *
     * @var bool
     *
     * @since    3.0
     */
    private $_appendName = true;

    /**
     * Class constructor.
     *
     * @param string $elementName  (optional)Group name
     * @param array  $elementLabel (optional)Group label
     * @param array  $elements     (optional)Group elements
     * @param mixed  $separator    (optional)Use a string for one separator,
     *                             use an array to alternate the separators
     * @param bool   $appendName   (optional)whether to change elements' names to
     *                             the form $groupName[$elementName] or leave
     *                             them as is
     *
     * @since     1.0
     *
     * @return void
     */
    public function __construct(
        $elementName = null,
        $elementLabel = null,
        $elements = null,
        $separator = null,
        $appendName = true
    ) {
        parent::__construct($elementName, $elementLabel);
        $this->_type = 'group';
        if (isset($elements) && is_array($elements)) {
            $this->setElements($elements);
        }

        $this->_separator = '';
        if (isset($separator)) {
            $this->_separator = $separator;
        }
        if (isset($appendName)) {
            $this->_appendName = $appendName;
        }
    }

    /**
     * Sets the group name.
     *
     * @param string $name Group name
     *
     * @since     1.0
     *
     * @return void
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * Returns the group name.
     *
     * @since     1.0
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets values for group's elements.
     *
     * @param     mixed    Values for group's elements
     *
     * @since     1.0
     *
     * @return void
     */
    public function setValue($value)
    {
        $this->_createElementsIfNotExist();
        foreach (array_keys($this->_elements) as $key) {
            if (!$this->_appendName) {
                $v = $this->_elements[$key]->_findValue($value);
                if (null !== $v) {
                    $this->_elements[$key]->onQuickFormEvent('setGroupValue', $v, $this);
                }
            } else {
                $elementName = $this->_elements[$key]->getName();
                $index = strlen($elementName) ? $elementName : $key;
                if (is_array($value)) {
                    if (isset($value[$index])) {
                        $this->_elements[$key]->onQuickFormEvent('setGroupValue', $value[$index], $this);
                    }
                } elseif (isset($value)) {
                    $this->_elements[$key]->onQuickFormEvent('setGroupValue', $value, $this);
                }
            }
        }
    }

    /**
     * Returns the value of the group.
     *
     * @since     1.0
     *
     * @return mixed
     */
    public function getValue()
    {
        $value = null;
        foreach (array_keys($this->_elements) as $key) {
            $element = &$this->_elements[$key];
            switch ($element->getType()) {
                case 'radio':
                    $v = $element->getChecked() ? $element->getValue() : null;
                    break;
                case 'checkbox':
                    $v = $element->getChecked() ? true : null;
                    break;
                default:
                    $v = $element->getValue();
            }
            if (null !== $v) {
                $elementName = $element->getName();
                if (is_null($elementName)) {
                    $value = $v;
                } else {
                    if (!is_array($value)) {
                        $value = is_null($value) ? [] : [$value];
                    }
                    if ('' === $elementName) {
                        $value[] = $v;
                    } else {
                        $value[$elementName] = $v;
                    }
                }
            }
        }

        return $value;
    }

    /**
     * Sets the grouped elements.
     *
     * @param array $elements Array of elements
     *
     * @since     1.1
     *
     * @return void
     */
    public function setElements($elements)
    {
        $this->_elements = array_values($elements);
        if ($this->_flagFrozen) {
            $this->freeze();
        }
    }

    /**
     * Gets the grouped elements.
     *
     * @since     2.4
     *
     * @return array
     */
    public function &getElements()
    {
        $this->_createElementsIfNotExist();

        return $this->_elements;
    }

    /**
     * Gets the group type based on its elements
     * Will return 'mixed' if elements contained in the group
     * are of different types.
     *
     * @return string group elements type
     */
    public function getGroupType()
    {
        $this->_createElementsIfNotExist();
        $prevType = '';
        foreach (array_keys($this->_elements) as $key) {
            $type = $this->_elements[$key]->getType();
            if ($type != $prevType && '' != $prevType) {
                return 'mixed';
            }
            $prevType = $type;
        }

        return $type;
    }

    /**
     * Returns Html for the group.
     *
     * @since       1.0
     *
     * @return string
     */
    public function toHtml()
    {
        $renderer = new HTML_QuickForm_Renderer_Default();
        $renderer->setElementTemplate('{element}');
        $this->accept($renderer);

        return $renderer->toHtml();
    }

    /**
     * Returns the element name inside the group such as found in the html form.
     *
     * @param mixed $index Element name or element index in the group
     *
     * @since     3.0
     *
     * @return mixed string with element name, false if not found
     */
    public function getElementName($index)
    {
        $this->_createElementsIfNotExist();
        $elementName = false;
        if (is_int($index) && isset($this->_elements[$index])) {
            $elementName = $this->_elements[$index]->getName();
            if (isset($elementName) && '' == $elementName) {
                $elementName = $index;
            }
            if ($this->_appendName) {
                if (is_null($elementName)) {
                    $elementName = $this->getName();
                } else {
                    $elementName = $this->getName().'['.$elementName.']';
                }
            }
        } elseif (is_string($index)) {
            foreach (array_keys($this->_elements) as $key) {
                $elementName = $this->_elements[$key]->getName();
                if ($index == $elementName) {
                    if ($this->_appendName) {
                        $elementName = $this->getName().'['.$elementName.']';
                    }
                    break;
                } elseif ($this->_appendName && $this->getName().'['.$elementName.']' == $index) {
                    break;
                }
            }
        }

        return $elementName;
    }

    /**
     * Returns the value of field without HTML tags.
     *
     * @since     1.3
     *
     * @return string
     */
    public function getFrozenHtml()
    {
        $flags = [];
        $this->_createElementsIfNotExist();
        foreach (array_keys($this->_elements) as $key) {
            if (false === ($flags[$key] = $this->_elements[$key]->isFrozen())) {
                $this->_elements[$key]->freeze();
            }
        }
        $html = $this->toHtml();
        foreach (array_keys($this->_elements) as $key) {
            if (!$flags[$key]) {
                $this->_elements[$key]->unfreeze();
            }
        }

        return $html;
    }

    /**
     * Called by HTML_QuickForm whenever form event is made on this element.
     *
     * @param string $event   Name of event
     * @param mixed  $arg     event arguments
     * @param object &$caller calling object
     *
     * @since     1.0
     *
     * @return void
     */
    public function onQuickFormEvent($event, $arg, &$caller)
    {
        switch ($event) {
            case 'updateValue':
                $this->_createElementsIfNotExist();

                foreach (array_keys($this->_elements) as $key) {
                    if ($this->_appendName) {
                        $elementName = $this->_elements[$key]->getName();

                        if (is_null($elementName)) {
                            $this->_elements[$key]->setName($this->getName());
                        } elseif ('' === $elementName) {
                            $this->_elements[$key]->setName($this->getName().'['.$key.']');
                        } else {
                            $this->_elements[$key]->setName($this->getName().'['.$elementName.']');
                        }
                    }
                    $this->_elements[$key]->onQuickFormEvent('updateValue', $arg, $caller);
                    if ($this->_appendName) {
                        $this->_elements[$key]->setName($elementName);
                    }
                }
                break;
            default:
                parent::onQuickFormEvent($event, $arg, $caller);
        }

        return true;
    }

    /**
     * Accepts a renderer.
     *
     * @param HTML_QuickForm_Renderer    renderer object
     * @param bool                       Whether a group is required
     * @param string                     An error message associated with a group
     *
     * @return void
     */
    public function accept(&$renderer, $required = false, $error = null)
    {
        $this->_createElementsIfNotExist();
        $renderer->startGroup($this, $required, $error);
        $name = $this->getName();
        /** @var HTML_QuickForm_element $element */
        foreach (array_keys($this->_elements) as $key) {
            $element = &$this->_elements[$key];
            $element->setLayout($this->getLayout());

            if ($this->_appendName) {
                $elementName = $element->getName();

                if (isset($elementName)) {
                    $element->setName($name.'['.(strlen($elementName) ? $elementName : $key).']');
                } else {
                    $element->setName($name);
                }
            }

            $required = !$element->isFrozen() && in_array($element->getName(), $this->_required);
            $element->accept($renderer, $required);

            // restore the element's name
            if ($this->_appendName) {
                $element->setName($elementName);
            }
        }
        $renderer->finishGroup($this);
    }

    /**
     * As usual, to get the group's value we access its elements and call
     * their exportValue() methods.
     */
    public function exportValue(&$submitValues, $assoc = false)
    {
        $value = null;
        foreach (array_keys($this->_elements) as $key) {
            $elementName = $this->_elements[$key]->getName();
            if ($this->_appendName) {
                if (is_null($elementName)) {
                    $this->_elements[$key]->setName($this->getName());
                } elseif ('' === $elementName) {
                    $this->_elements[$key]->setName($this->getName().'['.$key.']');
                } else {
                    $this->_elements[$key]->setName($this->getName().'['.$elementName.']');
                }
            }
            $v = $this->_elements[$key]->exportValue($submitValues, $assoc);
            if ($this->_appendName) {
                $this->_elements[$key]->setName($elementName);
            }
            if (null !== $v) {
                // Make $value an array, we will use it like one
                if (null === $value) {
                    $value = [];
                }
                if ($assoc) {
                    // just like HTML_QuickForm::exportValues()
                    $value = HTML_QuickForm::arrayMerge($value, $v);
                } else {
                    // just like getValue(), but should work OK every time here
                    if (is_null($elementName)) {
                        $value = $v;
                    } elseif ('' === $elementName) {
                        $value[] = $v;
                    } else {
                        $value[$elementName] = $v;
                    }
                }
            }
        }

        // do not pass the value through _prepareValue, we took care of this already
        return $value;
    }

    /**
     * Creates the group's elements.
     *
     * This should be overriden by child classes that need to create their
     * elements. The method will be called automatically when needed, calling
     * it from the constructor is discouraged as the constructor is usually
     * called _twice_ on element creation, first time with _no_ parameters.
     *
     * @abstract
     */
    public function _createElements()
    {
        // abstract
    }

    /**
     * A wrapper around _createElements().
     *
     * This method calls _createElements() if the group's _elements array
     * is empty. It also performs some updates, e.g. freezes the created
     * elements if the group is already frozen.
     */
    public function _createElementsIfNotExist()
    {
        if (empty($this->_elements)) {
            $this->_createElements();
            if ($this->_flagFrozen) {
                $this->freeze();
            }
        }
    }

    public function freeze()
    {
        parent::freeze();
        foreach (array_keys($this->_elements) as $key) {
            $this->_elements[$key]->freezeSeeOnlySelected = $this->freezeSeeOnlySelected;
            $this->_elements[$key]->freeze();
        }
    }

    public function unfreeze()
    {
        parent::unfreeze();
        foreach (array_keys($this->_elements) as $key) {
            $this->_elements[$key]->unfreeze();
        }
    }

    public function setPersistantFreeze($persistant = false)
    {
        parent::setPersistantFreeze($persistant);
        foreach (array_keys($this->_elements) as $key) {
            $this->_elements[$key]->setPersistantFreeze($persistant);
        }
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
                <div class="input-group">
                    <label {label-for} >
                        <!-- BEGIN required --><span class="form_required">*</span><!-- END required -->
                        {label}
                    </label>
                </div>
                <div class="input-group {error_class}">
                    {element}
                </div>
                ';
                break;
            case FormValidator::LAYOUT_HORIZONTAL:
                return '
                <div class="form-group {error_class}" id="'.$this->getName().'-group">
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
            case FormValidator::LAYOUT_BOX:
                return '

                        <div class="input-group">
                            <label>{label}</label>
                            {icon}
                            {element}
                        </div>';
                break;
        }
    }
}
