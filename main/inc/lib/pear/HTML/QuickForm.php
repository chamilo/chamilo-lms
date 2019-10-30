<?php
/**
 * Create, validate and process HTML forms
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
 * @version     CVS: $Id: QuickForm.php,v 1.166 2009/04/04 21:34:02 avb Exp $
 * @link        http://pear.php.net/package/HTML_QuickForm
 */


/**
 * Validation rules known to HTML_QuickForm
 * @see HTML_QuickForm::registerRule(), HTML_QuickForm::getRegisteredRules(),
 *      HTML_QuickForm::isRuleRegistered()
 * @global array $GLOBALS['_HTML_QuickForm_registered_rules']
 */

/**
 * Error codes for HTML_QuickForm
 *
 * Codes are mapped to textual messages by errorMessage() method, if you add a
 * new code be sure to add a new message for it to errorMessage()
 *
 * @see HTML_QuickForm::errorMessage()
 */
define('QUICKFORM_OK', 1);
define('QUICKFORM_ERROR', -1);
define('QUICKFORM_INVALID_RULE', -2);
define('QUICKFORM_NONEXIST_ELEMENT', -3);
define('QUICKFORM_INVALID_FILTER', -4);
define('QUICKFORM_UNREGISTERED_ELEMENT', -5);
define('QUICKFORM_INVALID_ELEMENT_NAME', -6);
define('QUICKFORM_INVALID_PROCESS', -7);
define('QUICKFORM_DEPRECATED', -8);
define('QUICKFORM_INVALID_DATASOURCE', -9);

/**
 * Class HTML_QuickForm
 * Create, validate and process HTML forms
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 3.2.11
 */
class HTML_QuickForm extends HTML_Common
{
    const MAX_ELEMENT_ARGUMENT = 10;

    /**
     * Array containing the form fields
     * @since     1.0
     * @var  array
     * @access   private
     */
    public $_elements = array();

    /**
     * Array containing element name to index map
     * @since     1.1
     * @var  array
     * @access   private
     */
    public $_elementIndex = array();

    /**
     * Array containing indexes of duplicate elements
     * @since     2.10
     * @var  array
     * @access   private
     */
    public $_duplicateIndex = array();

    /**
     * Array containing required field IDs
     * @since     1.0
     * @var  array
     * @access   private
     */
    public $_required = array();

    /**
     * Prefix message in javascript alert if error
     * @since     1.0
     * @var  string
     * @access   public
     */
    public $_jsPrefix = 'Invalid information entered.';

    /**
     * Postfix message in javascript alert if error
     * @since     1.0
     * @var  string
     * @access   public
     */
    public $_jsPostfix = 'Please correct these fields.';

    /**
     * Datasource object implementing the informal
     * datasource protocol
     * @since     3.3
     * @var  object
     * @access   private
     */
    public $_datasource;

    /**
     * Array of default form values
     * @since     2.0
     * @var  array
     * @access   private
     */
    public $_defaultValues = array();

    /**
     * Array of constant form values
     * @since     2.0
     * @var  array
     * @access   private
     */
    public $_constantValues = array();

    /**
     * Array of submitted form values
     * @since     1.0
     * @var  array
     * @access   private
     */
    public $_submitValues = array();

    /**
     * Array of submitted form files
     * @since     1.0
     * @var  integer
     * @access   public
     */
    public $_submitFiles = array();

    /**
     * Value for maxfilesize hidden element if form contains file input
     * @since     1.0
     * @var  integer
     * @access   public
     */
    public $_maxFileSize = 1048576; // 1 Mb = 1048576

    /**
     * Flag to know if all fields are frozen
     * @since     1.0
     * @var  boolean
     * @access   private
     */
    public $_freezeAll = false;

    /**
     * Array containing the form rules
     * @since     1.0
     * @var  array
     * @access   private
     */
    public $_rules = array();

    /**
     * Form rules, global variety
     * @var     array
     * @access  private
     */
    public $_formRules = array();

    /**
     * Array containing the validation errors
     * @since     1.0
     * @var  array
     * @access   private
     */
    public $_errors = array();

    /**
     * Note for required fields in the form
     * @var       string
     * @since     1.0
     * @access    private
     */
    public $_requiredNote = '<span style="font-size:80%; color:#ff0000;">*</span><span style="font-size:80%;"> denotes required field</span>';

    /**
     * Whether the form was submitted
     * @var       boolean
     * @access    private
     */
    public $_flagSubmitted = false;

    /**
     * Class constructor
     * @param    string      $formName          Form's name.
     * @param    string      $method            (optional)Form's method defaults to 'POST'
     * @param    string      $action            (optional)Form's action
     * @param    string      $target            (optional)Form's target defaults to '_self'
     * @param    mixed       $attributes        (optional)Extra attributes for <form> tag
     * @param    bool        $trackSubmit       (optional)Whether to track if the form was submitted by adding a special hidden field
     * @access   public
     */
    public function __construct(
        $formName = '',
        $method = 'post',
        $action = '',
        $target = '',
        $attributes = null,
        $trackSubmit = false
    ) {
        parent::__construct($attributes);
        $method = (strtoupper($method) == 'GET') ? 'get' : 'post';
        $action = ($action == '') ? api_get_self() : $action;
        $target = empty($target) ? array() : array('target' => $target);
        $form_id = $formName;
        if (isset($attributes['id']) && !empty($attributes['id'])) {
            $form_id = Security::remove_XSS($attributes['id']);
        }
        $attributes = array(
            'action' => $action,
            'method' => $method,
            'name' => $formName,
            'id' => $form_id
        ) + $target;
        $this->updateAttributes($attributes);
        if (!$trackSubmit || isset($_REQUEST['_qf__' . $formName])) {
            $this->_submitValues = 'get' == $method ? $_GET : $_POST;
            $this->_submitFiles = $_FILES;
            $this->_flagSubmitted = count($this->_submitValues) > 0 || count($this->_submitFiles) > 0;
        }
        if ($trackSubmit) {
            unset($this->_submitValues['_qf__' . $formName]);
            $this->addElement('hidden', '_qf__' . $formName, null);
        }

        if (preg_match('/^([0-9]+)([a-zA-Z]*)$/', ini_get('upload_max_filesize'), $matches)) {
            // see http://www.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
            switch (strtoupper($matches['2'])) {
                case 'G':
                    $this->_maxFileSize = $matches['1'] * 1073741824;
                    break;
                case 'M':
                    $this->_maxFileSize = $matches['1'] * 1048576;
                    break;
                case 'K':
                    $this->_maxFileSize = $matches['1'] * 1024;
                    break;
                default:
                    $this->_maxFileSize = $matches['1'];
            }
        }
    }

    /**
     * Returns the current API version
     *
     * @since     1.0
     * @access    public
     * @return    float
     */
    function apiVersion()
    {
        return 3.2;
    }

    /**
     * Registers a new element type
     *
     * @param     string    $typeName   Name of element type
     * @param     string    $include    Include path for element type
     * @param     string    $className  Element class name
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function registerElementType($typeName, $include, $className)
    {
        $GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES'][strtolower($typeName)] = array($include, $className);
    }

    /**
     * Registers a new validation rule
     *
     * @param     string    $ruleName   Name of validation rule
     * @param     string    $type       Either: 'regex', 'function' or 'rule' for an HTML_QuickForm_Rule object
     * @param     string    $data1      Name of function, regular expression or HTML_QuickForm_Rule classname
     * @param     string    $data2      Object parent of above function or HTML_QuickForm_Rule file path
     * @since     1.0
     * @access    public
     * @return    void
     */
    public static function registerRule($ruleName, $type, $data1, $data2 = null)
    {
        $registry =& HTML_QuickForm_RuleRegistry::singleton();
        $registry->registerRule($ruleName, $type, $data1, $data2);
    }

    /**
     * Returns true if element is in the form
     *
     * @param     string   $element         form name of element to check
     * @since     1.0
     * @access    public
     * @return    boolean
     */
    public function elementExists($element = null)
    {
        return isset($this->_elementIndex[$element]);
    }

    /**
     * Initializes default form values
     *
     * @param     array $defaultValues values used to fill the form
     * @param     mixed $filter (optional) filter(s) to apply to all default values
     * @since     1.0
     * @access    public
     */
    public function setDefaults($defaultValues = null, $filter = null)
    {
        if (is_array($defaultValues)) {
            if (isset($filter)) {
                if (is_array($filter) && (2 != count($filter) || !is_callable($filter))) {
                    foreach ($filter as $val) {
                        if (!is_callable($val)) {
                            throw new \Exception('Callback function does not exist in QuickForm::setDefaults()');
                        } else {
                            $defaultValues = $this->_recursiveFilter($val, $defaultValues);
                        }
                    }
                } elseif (!is_callable($filter)) {
                    throw new \Exception('Callback function does not exist in QuickForm::setDefaults()');
                } else {
                    $defaultValues = $this->_recursiveFilter($filter, $defaultValues);
                }
            }

            $this->_defaultValues = self::arrayMerge($this->_defaultValues, $defaultValues);
            $this->_constantValues = $this->_defaultValues;
            foreach (array_keys($this->_elements) as $key) {
                $this->_elements[$key]->onQuickFormEvent('updateValue', null, $this);
            }
        }
    }

    /**
     * Initializes constant form values.
     * These values won't get overridden by POST or GET vars
     *
     * @param     array   $constantValues        values used to fill the form
     * @param     mixed    $filter              (optional) filter(s) to apply to all default values
     *
     * @since     2.0
     * @access    public
     * @return    void
     */
    public function setConstants($constantValues = null, $filter = null)
    {
        if (is_array($constantValues)) {
            if (isset($filter)) {
                if (is_array($filter) && (2 != count($filter) || !is_callable($filter))) {
                    foreach ($filter as $val) {
                        if (!is_callable($val)) {
                            throw new \Exception("Callback function does not exist in QuickForm::setConstants()");
                        } else {
                            $constantValues = $this->_recursiveFilter($val, $constantValues);
                        }
                    }
                } elseif (!is_callable($filter)) {
                    throw new \Exception("Callback function does not exist in QuickForm::setConstants()");
                } else {
                    $constantValues = $this->_recursiveFilter($filter, $constantValues);
                }
            }
            $this->_constantValues = HTML_QuickForm::arrayMerge($this->_constantValues, $constantValues);
            foreach (array_keys($this->_elements) as $key) {
                $this->_elements[$key]->onQuickFormEvent('updateValue', null, $this);
            }
        }
    }

    /**
     * Sets the value of MAX_FILE_SIZE hidden element
     *
     * @param     int    $bytes    Size in bytes
     * @since     3.0
     * @access    public
     * @return    void
     */
    public function setMaxFileSize($bytes = 0)
    {
        if ($bytes > 0) {
            $this->_maxFileSize = $bytes;
        }
        if (!$this->elementExists('MAX_FILE_SIZE')) {
            $this->addElement('hidden', 'MAX_FILE_SIZE', $this->_maxFileSize);
        } else {
            $el =& $this->getElement('MAX_FILE_SIZE');
            $el->updateAttributes(array('value' => $this->_maxFileSize));
        }
    }

    /**
     * Returns the value of MAX_FILE_SIZE hidden element
     *
     * @since     3.0
     * @access    public
     * @return    int   max file size in bytes
     */
    public function getMaxFileSize()
    {
        return $this->_maxFileSize;
    }

    /**
     * Creates a new form element of the given type.
     *
     * This method accepts variable number of parameters, their
     * meaning and count depending on $elementType
     *
     * @param     string     $elementType    type of element to add (text, textarea, file...)
     * @since     1.0
     * @access    public
     * @return    HTML_QuickForm_Element
     * @throws    HTML_QuickForm_Error
     */
    public function &createElement($elementType)
    {
        $args = func_get_args();
        $element = $this->_loadElement(
            'createElement',
            $elementType,
            array_slice($args, 1)
        );

        return $element;
    }

    /**
     * Returns a form element of the given type
     *
     * @param     string   $event   event to send to newly created element ('createElement' or 'addElement')
     * @param     string   $type    element type
     * @param     array    $args    arguments for event
     * @since     2.0
     * @access    private
     * @return    HTML_QuickForm_Element
     * @throws    HTML_QuickForm_Error
     */
    public function &_loadElement($event, $type, $args)
    {
        $className = null;

        // Try if class exists
        if (!class_exists($type)) {
            // Try classic class name HTML_QuickForm_
            $className = 'HTML_QuickForm_'.$type;
            if (!class_exists($className)) {
                // Try classic class name HTML_QuickForm_ with strtolower
                $lowerType = strtolower($type);
                $className = 'HTML_QuickForm_'.$lowerType;
                if (!class_exists($className)) {
                    // Try new class name CamelCase
                    $className = underScoreToCamelCase($type);
                    if (!class_exists($className)) {
                        throw new \Exception("Class '$className' does not exist. ");
                    }
                }
            }
        } else {
            $className = $type;
        }

        if (empty($className)) {
            throw new \Exception("Class '$className' does not exist. ");
        }

        for ($i = 0; $i < self::MAX_ELEMENT_ARGUMENT; $i++) {
            if (!isset($args[$i])) {
                $args[$i] = null;
            }
        }

        /** @var HTML_QuickForm_element $element */

        $element = new $className(
            $args[0],
            $args[1],
            $args[2],
            $args[3],
            $args[4],
            $args[5],
            $args[6],
            $args[7],
            $args[8],
            $args[9]
        );

        if ($event != 'createElement') {
            $err = $element->onQuickFormEvent($event, $args, $this);
            if ($err !== true) {
                return $err;
            }
        }

        return $element;
    }

    /**
     * Adds an element into the form
     *
     * If $element is a string representing element type, then this
     * method accepts variable number of parameters, their meaning
     * and count depending on $element
     *
     * @param    mixed      $element        element object or type of element to add (text, textarea, file...)
     * @since    1.0
     * @return   HTML_QuickForm_element     a reference to newly added element
     * @access   public
     * @throws   HTML_QuickForm_Error
     */
    public function &addElement($element)
    {
        if (is_object($element) && is_subclass_of($element, 'html_quickform_element')) {
            $elementObject = &$element;
            $elementObject->onQuickFormEvent('updateValue', null, $this);
        } else {
            $args = func_get_args();
            $elementObject =& $this->_loadElement('addElement', $element, array_slice($args, 1));
            if (PEAR::isError($elementObject)) {
                return $elementObject;
            }
        }
        $elementName = $elementObject->getName();

        // Add the element if it is not an incompatible duplicate
        if (!empty($elementName) &&
            isset($this->_elementIndex[$elementName])
        ) {
            if ($this->_elements[$this->_elementIndex[$elementName]]->getType() == $elementObject->getType()) {
                $this->_elements[] =& $elementObject;
                $elKeys = array_keys($this->_elements);
                $this->_duplicateIndex[$elementName][] = end($elKeys);
            } else {
                throw new \Exception("Element '$elementName' already exists in HTML_QuickForm::addElement()");
            }
        } else {
            $this->_elements[] =& $elementObject;
            $elKeys = array_keys($this->_elements);
            $this->_elementIndex[$elementName] = end($elKeys);
        }

        $elId = $elementObject->getAttribute('id');

        if (empty($elId)) {
            $elementObject->setAttribute('id', "{$this->getAttribute('name')}_$elementName");
        }

        if ($this->_freezeAll) {
            $elementObject->freeze();
        }

        return $elementObject;
    }

    /**
     * @return array
     */
    public function getElements()
    {
        return $this->_elements;
    }

   /**
    * Inserts a new element right before the other element
    *
    * Warning: it is not possible to check whether the $element is already
    * added to the form, therefore if you want to move the existing form
    * element to a new position, you'll have to use removeElement():
    * $form->insertElementBefore($form->removeElement('foo', false), 'bar');
    *
    * @access   public
    * @since    3.2.4
    * @param    HTML_QuickForm_element  Element to insert
    * @param    string                  Name of the element before which the new
    *                                   one is inserted
    * @return   HTML_QuickForm_element  reference to inserted element
    */
    public function &insertElementBefore(&$element, $nameAfter)
    {
        if (!empty($this->_duplicateIndex[$nameAfter])) {
            throw new \Exception('Several elements named "' . $nameAfter . '" exist in HTML_QuickForm::insertElementBefore().');

        } elseif (!$this->elementExists($nameAfter)) {
            throw new \Exception("Element '$nameAfter' does not exist in HTML_QuickForm::insertElementBefore()");
        }
        $elementName = $element->getName();
        $targetIdx   = $this->_elementIndex[$nameAfter];
        $duplicate   = false;
        // Like in addElement(), check that it's not an incompatible duplicate
        if (!empty($elementName) && isset($this->_elementIndex[$elementName])) {
            if ($this->_elements[$this->_elementIndex[$elementName]]->getType() != $element->getType()) {
                throw new \Exception("Element '$elementName' already exists in HTML_QuickForm::insertElementBefore()");
            }
            $duplicate = true;
        }
        // Move all the elements after added back one place, reindex _elementIndex and/or _duplicateIndex
        $elKeys = array_keys($this->_elements);
        for ($i = end($elKeys); $i >= $targetIdx; $i--) {
            if (isset($this->_elements[$i])) {
                $currentName = $this->_elements[$i]->getName();
                $this->_elements[$i + 1] =& $this->_elements[$i];
                if ($this->_elementIndex[$currentName] == $i) {
                    $this->_elementIndex[$currentName] = $i + 1;
                } else {
                    $dupIdx = array_search($i, $this->_duplicateIndex[$currentName]);
                    $this->_duplicateIndex[$currentName][$dupIdx] = $i + 1;
                }
                unset($this->_elements[$i]);
            }
        }
        // Put the element in place finally
        $this->_elements[$targetIdx] =& $element;
        if (!$duplicate) {
            $this->_elementIndex[$elementName] = $targetIdx;
        } else {
            $this->_duplicateIndex[$elementName][] = $targetIdx;
        }
        $element->onQuickFormEvent('updateValue', null, $this);
        if ($this->_freezeAll) {
            $element->freeze();
        }
        // If not done, the elements will appear in reverse order
        ksort($this->_elements);
        return $element;
    }

    /**
     * Adds an element group
     * @param    array      $elements       array of elements composing the group
     * @param    string     $name           (optional)group name
     * @param    string     $groupLabel     (optional)group label
     * @param    string     $separator      (optional)string to separate elements
     * @param    bool       $appendName     (optional)specify whether the group name should be
     *                                      used in the form element name ex: group[element]
     * @return   HTML_QuickForm_group       reference to a newly added group
     * @since    2.8
     * @access   public
     * @throws   HTML_QuickForm_Error
     */
    public function &addGroup(
        $elements,
        $name = null,
        $groupLabel = '',
        $separator = null,
        $appendName = true,
        $createElement = false
    ) {
        static $anonGroups = 1;

        if (0 == strlen($name)) {
            $name = 'qf_group_'.$anonGroups++;
            $appendName = false;
        }
        if ($createElement) {
            return $this->createElement('group', $name, $groupLabel, $elements, $separator, $appendName);
        }
        $group = & $this->addElement('group', $name, $groupLabel, $elements, $separator, $appendName);
        return $group;
    }

    /**
     * Returns a reference to the element
     *
     * @param     string     $element    Element name
     * @since     2.0
     * @access    public
     * @return    HTML_QuickForm_element    reference to element
     * @throws    HTML_QuickForm_Error
     */
    public function &getElement($element)
    {
        if (isset($this->_elementIndex[$element])) {

            return $this->_elements[$this->_elementIndex[$element]];
        } else {
            throw new \Exception("Element '$element' does not exist in HTML_QuickForm::getElement()");
        }
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getElementByName($name)
    {
        foreach ($this->_elements as &$element) {
            $elementName = $element->getName();
            if ($elementName == $name) {
                return $element;
            }
        }
    }

    /**
     * @param string $element
     * @return bool
     */
    public function hasElement($element)
    {
        return isset($this->_elementIndex[$element]);
    }

    /**
     * Returns the element's raw value
     *
     * This returns the value as submitted by the form (not filtered)
     * or set via setDefaults() or setConstants()
     *
     * @param     string     $element    Element name
     * @since     2.0
     * @access    public
     * @return    mixed     element value
     * @throws    HTML_QuickForm_Error
     */
    public function &getElementValue($element)
    {
        if (!isset($this->_elementIndex[$element])) {
            throw new \Exception("Element '$element' does not exist in HTML_QuickForm::getElementValue()");
        }
        $value = $this->_elements[$this->_elementIndex[$element]]->getValue();
        if (isset($this->_duplicateIndex[$element])) {
            foreach ($this->_duplicateIndex[$element] as $index) {
                if (null !== ($v = $this->_elements[$index]->getValue())) {
                    if (is_array($value)) {
                        $value[] = $v;
                    } else {
                        $value = (null === $value)? $v: array($value, $v);
                    }
                }
            }
        }
        return $value;
    }

    /**
     * Returns the elements value after submit and filter
     *
     * @param     string     Element name
     * @since     2.0
     * @access    public
     * @return    mixed     submitted element value or null if not set
     */
    public function getSubmitValue($elementName)
    {
        $value = null;
        if (isset($this->_submitValues[$elementName]) || isset($this->_submitFiles[$elementName])) {
            $value = isset($this->_submitValues[$elementName])? $this->_submitValues[$elementName]: array();
            if (is_array($value) && isset($this->_submitFiles[$elementName])) {
                foreach ($this->_submitFiles[$elementName] as $k => $v) {
                    $value = HTML_QuickForm::arrayMerge(
                        $value,
                        $this->_reindexFiles($this->_submitFiles[$elementName][$k], $k)
                    );
                }
            }
        } elseif ('file' == $this->getElementType($elementName)) {
            return $this->getElementValue($elementName);

        } elseif (false !== ($pos = strpos($elementName, '['))) {
            $base = str_replace(
                array('\\', '\''),
                array('\\\\', '\\\''),
                substr($elementName, 0, $pos)
            );
            $idx = "['".str_replace(
                    array('\\', '\'', ']', '['),
                    array('\\\\', '\\\'', '', "']['"),
                    substr($elementName, $pos + 1, -1)
                )."']";
            if (isset($this->_submitValues[$base])) {
                $value = eval("return (isset(\$this->_submitValues['{$base}']{$idx})) ? \$this->_submitValues['{$base}']{$idx} : null;");
            }

            if ((is_array($value) || null === $value) && isset($this->_submitFiles[$base])) {
                $props = array('name', 'type', 'size', 'tmp_name', 'error');
                $code  = "if (!isset(\$this->_submitFiles['{$base}']['name']{$idx})) {\n" .
                         "    return null;\n" .
                         "} else {\n" .
                         "    \$v = array();\n";
                foreach ($props as $prop) {
                    $code .= "    \$v = HTML_QuickForm::arrayMerge(\$v, \$this->_reindexFiles(\$this->_submitFiles['{$base}']['{$prop}']{$idx}, '{$prop}'));\n";
                }
                $fileValue = eval($code . "    return \$v;\n}\n");
                if (null !== $fileValue) {
                    $value = null === $value? $fileValue: HTML_QuickForm::arrayMerge($value, $fileValue);
                }
            }
        }

        // This is only supposed to work for groups with appendName = false
        if (null === $value && 'group' == $this->getElementType($elementName)) {
            $group    =& $this->getElement($elementName);
            $elements =& $group->getElements();
            foreach (array_keys($elements) as $key) {
                $name = $group->getElementName($key);
                // prevent endless recursion in case of radios and such
                if ($name != $elementName) {
                    if (null !== ($v = $this->getSubmitValue($name))) {
                        $value[$name] = $v;
                    }
                }
            }
        }

        if ($this->hasElement($elementName)) {
            $element = $this->getElement($elementName);
            if (method_exists($element, 'getSubmitValue')) {
                $value = $element->getSubmitValue(
                    $value,
                    $this->_submitValues,
                    $this->_errors
                );
            }
        }

        return $value;
    }

   /**
    * A helper function to change the indexes in $_FILES array
    *
    * @param  mixed   Some value from the $_FILES array
    * @param  string  The key from the $_FILES array that should be appended
    * @return array
    */
    public function _reindexFiles($value, $key)
    {
        if (!is_array($value)) {
            return array($key => $value);
        } else {
            $ret = array();
            foreach ($value as $k => $v) {
                $ret[$k] = $this->_reindexFiles($v, $key);
            }
            return $ret;
        }
    }

    /**
     * Returns error corresponding to validated element
     *
     * @param     string    $element        Name of form element to check
     * @since     1.0
     * @access    public
     * @return    string    error message corresponding to checked element
     */
    public function getElementError($element)
    {
        if (isset($this->_errors[$element])) {
            return $this->_errors[$element];
        }
    }

    /**
     * Set error message for a form element
     *
     * @param     string    $element    Name of form element to set error for
     * @param     string    $message    Error message, if empty then removes the current error message
     * @since     1.0
     * @access    public
     * @return    void
     */
    public function setElementError($element, $message = null)
    {
        if (!empty($message)) {
            $this->_errors[$element] = $message;
        } else {
            unset($this->_errors[$element]);
        }
    }

     /**
      * Returns the type of the given element
      *
      * @param      string    $element    Name of form element
      * @since      1.1
      * @access     public
      * @return     string    Type of the element, false if the element is not found
      */
    public function getElementType($element)
     {
         if (isset($this->_elementIndex[$element])) {
             return $this->_elements[$this->_elementIndex[$element]]->getType();
         }
         return false;
     }

    /**
     * Updates Attributes for one or more elements
     *
     * @param      mixed    $elements   Array of element names/objects or string of elements to be updated
     * @param      mixed    $attrs      Array or sting of html attributes
     * @since      2.10
     * @access     public
     * @return     void
     */
    public function updateElementAttr($elements, $attrs)
    {
        if (is_string($elements)) {
            $elements = split('[ ]?,[ ]?', $elements);
        }
        foreach (array_keys($elements) as $key) {
            if (is_object($elements[$key]) && is_a($elements[$key], 'HTML_QuickForm_element')) {
                $elements[$key]->updateAttributes($attrs);
            } elseif (isset($this->_elementIndex[$elements[$key]])) {
                $this->_elements[$this->_elementIndex[$elements[$key]]]->updateAttributes($attrs);
                if (isset($this->_duplicateIndex[$elements[$key]])) {
                    foreach ($this->_duplicateIndex[$elements[$key]] as $index) {
                        $this->_elements[$index]->updateAttributes($attrs);
                    }
                }
            }
        }
    }

    /**
     * Removes an element
     *
     * The method "unlinks" an element from the form, returning the reference
     * to the element object. If several elements named $elementName exist,
     * it removes the first one, leaving the others intact.
     *
     * @param string    $elementName The element name
     * @param boolean   $removeRules True if rules for this element are to be removed too
     * @access public
     * @since 2.0
     * @return HTML_QuickForm_element    a reference to the removed element
     * @throws HTML_QuickForm_Error
     */
    public function &removeElement($elementName, $removeRules = true)
    {
        if (!isset($this->_elementIndex[$elementName])) {
            throw new \Exception("Element '$elementName' does not exist in HTML_QuickForm::removeElement()");
        }
        $el =& $this->_elements[$this->_elementIndex[$elementName]];
        unset($this->_elements[$this->_elementIndex[$elementName]]);
        if (empty($this->_duplicateIndex[$elementName])) {
            unset($this->_elementIndex[$elementName]);
        } else {
            $this->_elementIndex[$elementName] = array_shift($this->_duplicateIndex[$elementName]);
        }
        if ($removeRules) {
            $this->_required = array_diff($this->_required, array($elementName));
            unset($this->_rules[$elementName], $this->_errors[$elementName]);
            if ('group' == $el->getType()) {
                foreach (array_keys($el->getElements()) as $key) {
                    unset($this->_rules[$el->getElementName($key)]);
                }
            }
        }

        return $el;
    }

    /**
     * Adds a validation rule for the given field
     *
     * If the element is in fact a group, it will be considered as a whole.
     * To validate grouped elements as separated entities,
     * use addGroupRule instead of addRule.
     *
     * @param    string $element Form element name
     * @param    string $message Message to display for invalid data
     * @param    string $type Rule type, use getRegisteredRules() to get types
     * @param    string $format (optional)Required for extra rule data
     * @param    string $validation (optional)Where to perform validation: "server", "client"
     * @param    boolean $reset Client-side validation: reset the form element to its original value if there is an error?
     * @param    boolean $force Force the rule to be applied, even if the target form element does not exist
     * @param array|string $dependent needed when comparing values
     * @since    1.0
     * @access   public
     */
    public function addRule(
        $element,
        $message,
        $type,
        $format = null,
        $validation = 'server',
        $reset = false,
        $force = false,
        $dependent = null
    ) {
        if (!$force) {
            if (!is_array($element) && !$this->elementExists($element)) {
                throw new \Exception("Element '$element' does not exist in HTML_QuickForm::addRule()");
            } elseif (is_array($element)) {
                foreach ($element as $el) {
                    if (!$this->elementExists($el)) {
                        throw new \Exception("Element '$el' does not exist in HTML_QuickForm::addRule()");
                    }
                }
            }
        }
        if (false === ($newName = $this->isRuleRegistered($type, true))) {
            throw new \Exception("Rule '$type' is not registered in HTML_QuickForm::addRule()");
        } elseif (is_string($newName)) {
            $type = $newName;
        }

        if (is_array($element)) {
            $dependent = $element;
            $element = array_shift($dependent);
        }

        if ($type == 'required' || $type == 'uploadedfile') {
            $this->_required[] = $element;
        }
        if (!isset($this->_rules[$element])) {
            $this->_rules[$element] = array();
        }
        if ($validation == 'client') {
            $this->updateAttributes(
                array('onsubmit' => 'try { var myValidator = validate_' . $this->_attributes['id'] . '; } catch(e) { return true; } return myValidator(this);')
            );
        }
        $this->_rules[$element][] = array(
            'type' => $type,
            'format' => $format,
            'message' => $message,
            'validation' => $validation,
            'reset' => $reset,
            'dependent' => $dependent,
        );
    }

    /**
     * Adds a validation rule for the given group of elements
     *
     * Only groups with a name can be assigned a validation rule
     * Use addGroupRule when you need to validate elements inside the group.
     * Use addRule if you need to validate the group as a whole. In this case,
     * the same rule will be applied to all elements in the group.
     * Use addRule if you need to validate the group against a function.
     *
     * @param    string     $group         Form group name
     * @param    mixed      $arg1          Array for multiple elements or error message string for one element
     * @param    string     $type          (optional)Rule type use getRegisteredRules() to get types
     * @param    string     $format        (optional)Required for extra rule data
     * @param    int        $howmany       (optional)How many valid elements should be in the group
     * @param    string     $validation    (optional)Where to perform validation: "server", "client"
     * @param    bool       $reset         Client-side: whether to reset the element's value to its original state if validation failed.
     * @since    2.5
     * @access   public
     * @throws   HTML_QuickForm_Error
     */
    public function addGroupRule($group, $arg1, $type='', $format=null, $howmany=0, $validation = 'server', $reset = false)
    {
        if (!$this->elementExists($group)) {
            throw new \Exception("Group '$group' does not exist in HTML_QuickForm::addGroupRule()");
        }

        $groupObj =& $this->getElement($group);
        if (is_array($arg1)) {
            $required = 0;
            foreach ($arg1 as $elementIndex => $rules) {
                $elementName = $groupObj->getElementName($elementIndex);
                foreach ($rules as $rule) {
                    $format = (isset($rule[2])) ? $rule[2] : null;
                    $validation = (isset($rule[3]) && 'client' == $rule[3])? 'client': 'server';
                    $reset = isset($rule[4]) && $rule[4];
                    $type = $rule[1];
                    if (false === ($newName = $this->isRuleRegistered($type, true))) {
                        throw new \Exception("Rule '$type' is not registered in HTML_QuickForm::addGroupRule()");
                    } elseif (is_string($newName)) {
                        $type = $newName;
                    }

                    $this->_rules[$elementName][] = array(
                        'type' => $type,
                        'format' => $format,
                        'message' => $rule[0],
                        'validation' => $validation,
                        'reset' => $reset,
                        'group' => $group,
                    );

                    if ('required' == $type || 'uploadedfile' == $type) {
                        $groupObj->_required[] = $elementName;
                        $this->_required[] = $elementName;
                        $required++;
                    }
                    if ('client' == $validation) {
                        $this->updateAttributes(array('onsubmit' => 'try { var myValidator = validate_' . $this->_attributes['id'] . '; } catch(e) { return true; } return myValidator(this);'));
                    }
                }
            }

            if ($required > 0 && count($groupObj->getElements()) == $required) {
                $this->_required[] = $group;
            }
        } elseif (is_string($arg1)) {
            if (false === ($newName = $this->isRuleRegistered($type, true))) {
                throw new \Exception("Rule '$type' is not registered in HTML_QuickForm::addGroupRule()");
            } elseif (is_string($newName)) {
                $type = $newName;
            }

            // addGroupRule() should also handle <select multiple>
            if (is_a($groupObj, 'html_quickform_group')) {
                // Radios need to be handled differently when required
                if ($type == 'required' && $groupObj->getGroupType() == 'radio') {
                    $howmany = ($howmany == 0) ? 1 : $howmany;
                } else {
                    $howmany = ($howmany == 0) ? count($groupObj->getElements()) : $howmany;
                }
            }

            $this->_rules[$group][] = array(
                'type' => $type,
                'format' => $format,
                'message' => $arg1,
                'validation' => $validation,
                'howmany' => $howmany,
                'reset' => $reset,
            );
            if ($type == 'required') {
                $this->_required[] = $group;
            }
            if ($validation == 'client') {
                $this->updateAttributes(array('onsubmit' => 'try { var myValidator = validate_' . $this->_attributes['id'] . '; } catch(e) { return true; } return myValidator(this);'));
            }
        }
    }

   /**
    * Adds a global validation rule
    *
    * This should be used when for a rule involving several fields or if
    * you want to use some completely custom validation for your form.
    * The rule function/method should return true in case of successful
    * validation and array('element name' => 'error') when there were errors.
    *
    * @access   public
    * @param    mixed   Callback, either function name or array(&$object, 'method')
    * @throws   HTML_QuickForm_Error
    */
    public function addFormRule($rule)
    {
        if (!is_callable($rule)) {
            throw new \Exception('Callback function does not exist in HTML_QuickForm::addFormRule()');
        }
        $this->_formRules[] = $rule;
    }

    /**
     * Applies a data filter for the given field(s)
     *
     * @param    mixed     $element       Form element name or array of such names
     * @param    mixed     $filter        Callback, either function name or array(&$object, 'method')
     * @since    2.0
     * @access   public
     * @throws   HTML_QuickForm_Error
     */
    public function applyFilter($element, $filter)
    {
        if (!is_callable($filter)) {
            throw new \Exception("Callback function does not exist in QuickForm::applyFilter()");
        }
        if ($element == '__ALL__') {
            $this->_submitValues = $this->_recursiveFilter($filter, $this->_submitValues);
        } else {
            if (!is_array($element)) {
                $element = array($element);
            }
            foreach ($element as $elName) {
                $value = $this->getSubmitValue($elName);
                if (null !== $value) {
                    if (false === strpos($elName, '[')) {
                        $this->_submitValues[$elName] = $this->_recursiveFilter($filter, $value);
                    } else {
                        $idx  = "['" . str_replace(
                                    array('\\', '\'', ']', '['), array('\\\\', '\\\'', '', "']['"),
                                    $elName
                                ) . "']";
                        eval("\$this->_submitValues{$idx} = \$this->_recursiveFilter(\$filter, \$value);");
                    }
                }
            }
        }
    }

    /**
     * Recursively apply a filter function
     *
     * @param     string   $filter    filter to apply
     * @param     mixed    $value     submitted values
     * @since     2.0
     * @access    private
     * @return    cleaned values
     */
    public function _recursiveFilter($filter, $value)
    {
        if (is_array($value)) {
            $cleanValues = array();
            foreach ($value as $k => $v) {
                $cleanValues[$k] = $this->_recursiveFilter($filter, $v);
            }
            return $cleanValues;
        } else {
            return call_user_func($filter, $value);
        }
    }

   /**
    * Merges two arrays
    *
    * Merges two array like the PHP function array_merge but recursively.
    * The main difference is that existing keys will not be renumbered
    * if they are integers.
    *
    * @access   public
    * @param    array   $a  original array
    * @param    array   $b  array which will be merged into first one
    * @return   array   merged array
    */
    static function arrayMerge($a, $b)
    {
        foreach ($b as $k => $v) {
            if (is_array($v)) {
                if (isset($a[$k]) && !is_array($a[$k])) {
                    $a[$k] = $v;
                } else {
                    if (!isset($a[$k])) {
                        $a[$k] = array();
                    }
                    $a[$k] = HTML_QuickForm::arrayMerge($a[$k], $v);
                }
            } else {
                $a[$k] = $v;
            }
        }
        return $a;
    }

    /**
     * Returns whether or not the form element type is supported
     *
     * @param     string   $type     Form element type
     * @since     1.0
     * @access    public
     * @return    boolean
     */
    static function isTypeRegistered($type)
    {
        return isset($GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES'][strtolower($type)]);
    }

    /**
     * Returns an array of registered element types
     *
     * @since     1.0
     * @access    public
     * @return    array
     */
    function getRegisteredTypes()
    {
        return array_keys($GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']);
    }

    /**
     * Returns whether or not the given rule is supported
     *
     * @param     string   $name    Validation rule name
     * @param     bool     Whether to automatically register subclasses of HTML_QuickForm_Rule
     * @since     1.0
     * @access    public
     * @return    mixed    true if previously registered, false if not, new rule name if auto-registering worked
     */
    function isRuleRegistered($name, $autoRegister = false)
    {
        return true;
    }

    /**
     * Returns an array of registered validation rules
     *
     * @since     1.0
     * @access    public
     * @return    array
     */
    function getRegisteredRules()
    {
        return array_keys($GLOBALS['_HTML_QuickForm_registered_rules']);
    }

    /**
     * Returns whether or not the form element is required
     *
     * @param     string   $element     Form element name
     * @since     1.0
     * @access    public
     * @return    boolean
     */
    function isElementRequired($element)
    {
        return in_array($element, $this->_required, true);
    }

    /**
     * Returns whether or not the form element is frozen
     *
     * @param     string   $element     Form element name
     * @since     1.0
     * @access    public
     * @return    boolean
     */
    function isElementFrozen($element)
    {
         if (isset($this->_elementIndex[$element])) {
             return $this->_elements[$this->_elementIndex[$element]]->isFrozen();
         }
         return false;
    }

    /**
     * Sets JavaScript warning messages
     *
     * @param     string   $pref        Prefix warning
     * @param     string   $post        Postfix warning
     * @since     1.1
     * @access    public
     * @return    void
     */
    function setJsWarnings($pref, $post)
    {
        $this->_jsPrefix = $pref;
        $this->_jsPostfix = $post;
    }

    /**
     * Sets required-note
     *
     * @param     string   $note        Message indicating some elements are required
     * @since     1.1
     * @access    public
     * @return    void
     */
    function setRequiredNote($note)
    {
        $this->_requiredNote = $note;
    }

    /**
     * Returns the required note
     *
     * @since     2.0
     * @access    public
     * @return    string
     */
    function getRequiredNote()
    {
        return $this->_requiredNote;
    }

    /**
     * Performs the server side validation
     * @access    public
     * @since     1.0
     * @return    boolean   true if no error found
     * @throws    HTML_QuickForm_Error
     */
    public function validate()
    {
        if (count($this->_rules) == 0 && count($this->_formRules) == 0 && $this->isSubmitted()) {
            return (0 == count($this->_errors));
        } elseif (!$this->isSubmitted()) {

            return false;
        }

        $registry =& HTML_QuickForm_RuleRegistry::singleton();

        foreach ($this->_rules as $target => $rules) {
            $submitValue = $this->getSubmitValue($target);

            foreach ($rules as $rule) {
                if ((isset($rule['group']) && isset($this->_errors[$rule['group']])) ||
                     isset($this->_errors[$target])) {
                    continue 2;
                }
                // If element is not required and is empty, we shouldn't validate it
                if (!$this->isElementRequired($target)) {
                    if (!isset($submitValue) || '' == $submitValue) {
                        continue 2;
                        // Fix for bug #3501: we shouldn't validate not uploaded files, either.
                        // Unfortunately, we can't just use $element->isUploadedFile() since
                        // the element in question can be buried in group. Thus this hack.
                        // See also bug #12014, we should only consider a file that has
                        // status UPLOAD_ERR_NO_FILE as not uploaded, in all other cases
                        // validation should be performed, so that e.g. 'maxfilesize' rule
                        // will display an error if status is UPLOAD_ERR_INI_SIZE
                        // or UPLOAD_ERR_FORM_SIZE
                    } elseif (is_array($submitValue)) {
                        if (false === ($pos = strpos($target, '['))) {
                            $isUpload = !empty($this->_submitFiles[$target]);
                        } else {
                            $base = str_replace(
                                        array('\\', '\''), array('\\\\', '\\\''),
                                        substr($target, 0, $pos)
                                    );
                            $idx  = "['" . str_replace(
                                        array('\\', '\'', ']', '['), array('\\\\', '\\\'', '', "']['"),
                                        substr($target, $pos + 1, -1)
                                    ) . "']";
                            eval("\$isUpload = isset(\$this->_submitFiles['{$base}']['name']{$idx});");
                        }
                        if ($isUpload && (!isset($submitValue['error']) || UPLOAD_ERR_NO_FILE == $submitValue['error'])) {
                            continue 2;
                        }
                    }
                }

                if (isset($rule['dependent'])) {
                    $values = array($submitValue);
                    if (is_array($rule['dependent'])) {
                        foreach ($rule['dependent'] as $elName) {
                            $values[] = $this->getSubmitValue($elName);
                        }
                    } else {
                        $values[] = $rule['dependent'];
                    }
                    $result = $registry->validate(
                        $rule['type'],
                        $values,
                        $rule['format'],
                        true
                    );
                } elseif (is_array($submitValue) && !isset($rule['howmany'])) {
                    $result = $registry->validate($rule['type'], $submitValue, $rule['format'], true);
                } else {
                    $result = $registry->validate(
                        $rule['type'],
                        $submitValue,
                        $rule['format'],
                        false
                    );
                }

                if (!$result || (!empty($rule['howmany']) && $rule['howmany'] > (int)$result)) {
                    if (isset($rule['group'])) {
                        $this->_errors[$rule['group']] = $rule['message'];
                    } else {
                        $this->_errors[$target] = $rule['message'];
                    }
                }
            }
        }

        // process the global rules now
        foreach ($this->_formRules as $rule) {
            if (true !== ($res = call_user_func($rule, $this->_submitValues, $this->_submitFiles))) {
                if (is_array($res)) {
                    $this->_errors += $res;
                } else {
                    throw new \Exception('Form rule callback returned invalid value in HTML_QuickForm::validate()');
                }
            }
        }

        return (0 == count($this->_errors));
    }

    /**
     * Displays elements without HTML input tags
     *
     * @param    mixed $elementList array or string of element(s) to be frozen
     * @since     1.0
     * @access   public
     * @throws   HTML_QuickForm_Error
     */
    public function freeze($elementList = null, $setTemplateFrozen = '')
    {
        if (!isset($elementList)) {
            $this->_freezeAll = true;
            $elementList = array();
        } else {
            if (!is_array($elementList)) {
                $elementList = preg_split('/[ ]*,[ ]*/', $elementList);
            }
            $elementList = array_flip($elementList);
        }

        foreach (array_keys($this->_elements) as $key) {
            $name = $this->_elements[$key]->getName();
            if ($this->_freezeAll || isset($elementList[$name])) {
                if (!empty($setTemplateFrozen)) {
                    $this->_elements[$key]->setCustomFrozenTemplate($setTemplateFrozen);
                }
                $this->_elements[$key]->freeze();
                unset($elementList[$name]);
            }
        }

        if (!empty($elementList)) {
            throw new \Exception("Nonexistant element(s): '" . implode("', '", array_keys($elementList)) . "' in HTML_QuickForm::freeze()");
        }

        return true;
    }

    /**
     * Returns whether or not the whole form is frozen
     *
     * @since     3.0
     * @access    public
     * @return    boolean
     */
    public function isFrozen()
    {
         return $this->_freezeAll;
    }

    /**
     * Performs the form data processing
     *
     * @param    mixed     $callback        Callback, either function name or array(&$object, 'method')
     * @param    bool      $mergeFiles      Whether uploaded files should be processed too
     * @since    1.0
     * @access   public
     * @throws   HTML_QuickForm_Error
     * @return   mixed     Whatever value the $callback function returns
     */
    public function process($callback, $mergeFiles = true)
    {
        if (!is_callable($callback)) {
            throw new \Exception("Callback function does not exist in QuickForm::process()");
        }
        $values = ($mergeFiles === true) ? HTML_QuickForm::arrayMerge($this->_submitValues, $this->_submitFiles) : $this->_submitValues;

        return call_user_func($callback, $values);
    }

   /**
    * Accepts a renderer
    *
    * @param object     An HTML_QuickForm_Renderer object
    * @since 3.0
    * @access public
    * @return void
    */
    public function accept(&$renderer)
    {
        $renderer->startForm($this);
        foreach (array_keys($this->_elements) as $key) {
            $element =& $this->_elements[$key];
            $elementName = $element->getName();
            $required    = ($this->isElementRequired($elementName) && !$element->isFrozen());
            $error = $this->getElementError($elementName);
            $element->accept($renderer, $required, $error);
        }
        $renderer->finishForm($this);
    }

   /**
    * Returns a reference to default renderer object
    *
    * @access public
    * @since 3.0
    * @return HTML_QuickForm_Renderer_Default
    */
    public function &defaultRenderer()
    {
        if (!isset($GLOBALS['_HTML_QuickForm_default_renderer'])) {
            // Modified by Ivan Tcholakov, 16-MAR-2010. Suppressing a deprecation warning on PHP 5.3
            //$GLOBALS['_HTML_QuickForm_default_renderer'] =& new HTML_QuickForm_Renderer_Default();
            $GLOBALS['_HTML_QuickForm_default_renderer'] = new HTML_QuickForm_Renderer_Default();
        }
        return $GLOBALS['_HTML_QuickForm_default_renderer'];
    }

    /**
     * Returns an HTML version of the form
     *
     * @param string $in_data (optional) Any extra data to insert right
     *               before form is rendered.  Useful when using templates.
     *
     * @return   string     Html version of the form
     * @since     1.0
     * @access   public
     */
    public function toHtml($in_data = null)
    {
        if (!is_null($in_data)) {
            $this->addElement('html', $in_data);
        }
        $renderer =& $this->defaultRenderer();
        $this->accept($renderer);

        return $renderer->toHtml();
    }

    /**
     * Returns the client side validation script
     *
     * @since     2.0
     * @access    public
     * @return    string    Javascript to perform validation, empty string if no 'client' rules were added
     */
    public function getValidationScript()
    {
        if (empty($this->_rules) || empty($this->_attributes['onsubmit'])) {
            return '';
        }

        $registry =& HTML_QuickForm_RuleRegistry::singleton();
        $test = array();
        $js_escape = array(
            "\r"    => '\r',
            "\n"    => '\n',
            "\t"    => '\t',
            "'"     => "\\'",
            '"'     => '\"',
            '\\'    => '\\\\'
        );

        foreach ($this->_rules as $elementName => $rules) {
            foreach ($rules as $rule) {
                if ('client' == $rule['validation']) {
                    unset($element);

                    $dependent  = isset($rule['dependent']) && is_array($rule['dependent']);
                    $rule['message'] = strtr($rule['message'], $js_escape);

                    if (isset($rule['group'])) {
                        $group    =& $this->getElement($rule['group']);
                        // No JavaScript validation for frozen elements
                        if ($group->isFrozen()) {
                            continue 2;
                        }
                        $elements =& $group->getElements();
                        foreach (array_keys($elements) as $key) {
                            if ($elementName == $group->getElementName($key)) {
                                $element =& $elements[$key];
                                break;
                            }
                        }
                    } elseif ($dependent) {
                        $element   =  array();
                        $element[] =& $this->getElement($elementName);
                        foreach ($rule['dependent'] as $elName) {
                            $element[] =& $this->getElement($elName);
                        }
                    } else {
                        $element =& $this->getElement($elementName);
                    }
                    // No JavaScript validation for frozen elements
                    if (is_object($element) && $element->isFrozen()) {
                        continue 2;
                    } elseif (is_array($element)) {
                        foreach (array_keys($element) as $key) {
                            if ($element[$key]->isFrozen()) {
                                continue 3;
                            }
                        }
                    }

                    $test[] = $registry->getValidationScript($element, $elementName, $rule);
                }
            }
        }
        if (count($test) > 0) {
            return
                "<script>" .
                "//<![CDATA[\n" .
                "function validate_" . $this->_attributes['id'] . "(frm) {\n" .
                "  var value = '';\n" .
                "  var errFlag = new Array();\n" .
                "  var _qfGroups = {};\n" .
                "  _qfMsg = '';\n\n" .
                join("\n", $test) .
                "\n  if (_qfMsg != '') {\n" .
                "    _qfMsg = '" . strtr($this->_jsPrefix, $js_escape) . "' + _qfMsg;\n" .
                "    _qfMsg = _qfMsg + '\\n" . strtr($this->_jsPostfix, $js_escape) . "';\n" .
                "    alert(_qfMsg);\n" .
                "    return false;\n" .
                "  }\n" .
                "  return true;\n" .
                "}\n" .
                "//]]>\n" .
                "</script>";
        }
        return '';
    }

    /**
     * Returns the values submitted by the form
     *
     * @since     2.0
     * @access    public
     * @param     bool      Whether uploaded files should be returned too
     * @return    array
     */
    public function getSubmitValues($mergeFiles = false)
    {
        return $mergeFiles ? HTML_QuickForm::arrayMerge($this->_submitValues, $this->_submitFiles): $this->_submitValues;
    }

    /**
     * Returns the form's contents in an array.
     *
     * The description of the array structure is in HTML_QuickForm_Renderer_Array docs
     *
     * @since     2.0
     * @access    public
     * @param     bool      Whether to collect hidden elements (passed to the Renderer's constructor)
     * @return    array of form contents
     */
    public function toArray($collectHidden = false)
    {
        include_once 'HTML/QuickForm/Renderer/Array.php';
        // Modified by Ivan Tcholakov, 16-MAR-2010. Suppressing a deprecation warning on PHP 5.3
        //$renderer =& new HTML_QuickForm_Renderer_Array($collectHidden);
        $renderer = new HTML_QuickForm_Renderer_Array($collectHidden);
        //
        $this->accept($renderer);
        return $renderer->toArray();
     }

    /**
     * Returns a 'safe' element's value
     *
     * This method first tries to find a cleaned-up submitted value,
     * it will return a value set by setValue()/setDefaults()/setConstants()
     * if submitted value does not exist for the given element.
     *
     * @param  string   Name of an element
     * @access public
     * @return mixed
     * @throws HTML_QuickForm_Error
     */
    public function exportValue($element)
    {
        if (!isset($this->_elementIndex[$element])) {
            throw new \Exception("Element '$element' does not exist in HTML_QuickForm::getElementValue()");
        }
        $value = $this->_elements[$this->_elementIndex[$element]]->exportValue($this->_submitValues, false);
        if (isset($this->_duplicateIndex[$element])) {
            foreach ($this->_duplicateIndex[$element] as $index) {
                if (null !== ($v = $this->_elements[$index]->exportValue($this->_submitValues, false))) {
                    if (is_array($value)) {
                        $value[] = $v;
                    } else {
                        $value = (null === $value)? $v: array($value, $v);
                    }
                }
            }
        }
        return $value;
    }

    /**
     * Returns 'safe' elements' values
     *
     * Unlike getSubmitValues(), this will return only the values
     * corresponding to the elements present in the form.
     *
     * @param   mixed   Array/string of element names, whose values we want. If not set then return all elements.
     * @access  public
     * @return  array   An assoc array of elements' values
     * @throws  HTML_QuickForm_Error
     */
    public function exportValues($elementList = null)
    {
        $values = array();
        if (null === $elementList) {
            // iterate over all elements, calling their exportValue() methods
            foreach (array_keys($this->_elements) as $key) {
                $value = $this->_elements[$key]->exportValue($this->_submitValues, true);
                if (is_array($value)) {
                    // This shit throws a bogus warning in PHP 4.3.x
                    $values = HTML_QuickForm::arrayMerge($values, $value);
                }
            }
        } else {
            if (!is_array($elementList)) {
                $elementList = array_map('trim', explode(',', $elementList));
            }
            foreach ($elementList as $elementName) {
                $value = $this->exportValue($elementName);
                if (PEAR::isError($value)) {
                    return $value;
                }
                $values[$elementName] = $value;
            }
        }
        return $values;
    }

   /**
    * Tells whether the form was already submitted
    *
    * This is useful since the _submitFiles and _submitValues arrays
    * may be completely empty after the trackSubmit value is removed.
    *
    * @access public
    * @return bool
    */
    public function isSubmitted()
    {
        return $this->_flagSubmitted;
    }

    /**
     * Tell whether a result from a QuickForm method is an error (an instance of HTML_QuickForm_Error)
     *
     * @access public
     * @param mixed     result code
     * @return bool     whether $value is an error
     * @static
     */
    public function isError($value)
    {
        return (is_object($value) && is_a($value, 'html_quickform_error'));
    }

    /**
     * Return a textual error message for an QuickForm error code
     *
     * @access  public
     * @param   int     error code
     * @return  string  error message
     * @static
     */
    public static function errorMessage($value)
    {
        // make the variable static so that it only has to do the defining on the first call
        static $errorMessages;

        // define the varies error messages
        if (!isset($errorMessages)) {
            $errorMessages = array(
                QUICKFORM_OK => 'no error',
                QUICKFORM_ERROR => 'unknown error',
                QUICKFORM_INVALID_RULE => 'the rule does not exist as a registered rule',
                QUICKFORM_NONEXIST_ELEMENT => 'nonexistent html element',
                QUICKFORM_INVALID_FILTER => 'invalid filter',
                QUICKFORM_UNREGISTERED_ELEMENT => 'unregistered element',
                QUICKFORM_INVALID_ELEMENT_NAME => 'element already exists',
                QUICKFORM_INVALID_PROCESS => 'process callback does not exist',
                QUICKFORM_DEPRECATED => 'method is deprecated',
                QUICKFORM_INVALID_DATASOURCE => 'datasource is not an object',
            );
        }

        // If this is an error object, then grab the corresponding error code
        if (HTML_QuickForm::isError($value)) {
            $value = $value->getCode();
        }

        // return the textual error message corresponding to the code
        return isset($errorMessages[$value]) ? $errorMessages[$value] : $errorMessages[QUICKFORM_ERROR];
    }

    /**
     * @param HTML_QuickForm_element $element
     */
    public function setRequired(HTML_QuickForm_element $element)
    {
        $this->addRule(
            $element->getName(),
            get_lang('ThisFieldIsRequired'),
            'required'
        );
    }
}

/**
 * Class for errors thrown by HTML_QuickForm package
 *
 * @category    HTML
 * @package     HTML_QuickForm
 * @author      Adam Daniel <adaniel1@eesus.jnj.com>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @version     Release: 3.2.11
 */
class HTML_QuickForm_Error extends PEAR_Error
{
    /**
    * Prefix for all error messages
    * @var string
    */
    public $error_message_prefix = 'QuickForm Error: ';

    /**
    * Creates a quickform error object, extending the PEAR_Error class
    *
    * @param int   $code the error code
    * @param int   $mode the reaction to the error, either return, die or trigger/callback
    * @param int   $level intensity of the error (PHP error code)
    * @param mixed $debuginfo any information that can inform user as to nature of the error
    */
    public function __construct(
        $code = QUICKFORM_ERROR,
        $mode = PEAR_ERROR_RETURN,
        $level = E_USER_NOTICE,
        $debuginfo = null
    ) {
        if (is_int($code)) {
            parent::__construct(HTML_QuickForm::errorMessage($code), $code, $mode, $level, $debuginfo);
        } else {
            parent::__construct("Invalid error code: $code", QUICKFORM_ERROR, $mode, $level, $debuginfo);
        }
    }
}
