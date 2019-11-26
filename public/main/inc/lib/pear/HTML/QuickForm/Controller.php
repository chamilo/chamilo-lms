<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * The class representing a Controller of MVC design pattern.
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
 * @package     HTML_QuickForm_Controller
 * @author      Alexey Borzov <avb@php.net>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @copyright   2003-2009 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     SVN: $Id: Controller.php 289084 2009-10-02 06:53:09Z avb $
 * @link        http://pear.php.net/package/HTML_QuickForm_Controller
 */


/**
 * The class representing a Controller of MVC design pattern.
 *
 * This class keeps track of pages and (default) action handlers for the form,
 * it manages keeping the form values in session, setting defaults and
 * constants for the form as a whole and getting its submit values.
 *
 * Generally you don't need to subclass this.
 *
 * @category    HTML
 * @package     HTML_QuickForm_Controller
 * @author      Alexey Borzov <avb@php.net>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @version     Release: 1.0.10
 */
class HTML_QuickForm_Controller
{
   /**
    * Contains the pages (HTML_QuickForm_Page objects) of the miultipage form
    * @var array
    */
    var $_pages = array();

   /**
    * Contains the mapping of actions to corresponding HTML_QuickForm_Action objects
    * @var array
    */
    var $_actions = array();

   /**
    * Name of the form, used to store the values in session
    * @var string
    */
    var $_name;

   /**
    * Whether the form is modal
    * @var bool
    */
    var $_modal = true;

   /**
    * The action extracted from HTTP request: array('page', 'action')
    * @var array
    */
    var $_actionName = null;

   /**
    * Class constructor.
    *
    * Sets the form name and modal/non-modal behaviuor. Different multipage
    * forms should have different names, as they are used to store form
    * values in session. Modal forms allow passing to the next page only when
    * all of the previous pages are valid.
    *
    * @access public
    * @param  string  form name
    * @param  bool    whether the form is modal
    */
    function HTML_QuickForm_Controller($name, $modal = true)
    {
        $this->_name  = $name;
        $this->_modal = $modal;
    }


   /**
    * Returns a reference to a session variable containing the form-page
    * values and pages' validation status.
    *
    * This is a "low-level" method, use exportValues() if you want just to
    * get the form's values.
    *
    * @access public
    * @param  bool      If true, then reset the container: clear all default, constant and submitted values
    * @return array
    */
    function &container($reset = false)
    {
        $name = '_' . $this->_name . '_container';
        if (!isset($_SESSION[$name]) || $reset) {
            $_SESSION[$name] = array(
                'defaults'  => array(),
                'constants' => array(),
                'values'    => array(),
                'valid'     => array()
            );
        }
        foreach (array_keys($this->_pages) as $pageName) {
            if (!isset($_SESSION[$name]['values'][$pageName])) {
                $_SESSION[$name]['values'][$pageName] = array();
                $_SESSION[$name]['valid'][$pageName]  = null;
            }
        }
        return $_SESSION[$name];
    }


   /**
    * Processes the request.
    *
    * This finds the current page, the current action and passes the action
    * to the page's handle() method.
    *
    * @access public
    * @throws PEAR_Error
    */
    function run()
    {
        // the names of the action and page should be saved
        list($page, $action) = $this->_actionName = $this->getActionName();
        return $this->_pages[$page]->handle($action);
    }


   /**
    * Registers a handler for a specific action.
    *
    * @access public
    * @param  string                name of the action
    * @param  HTML_QuickForm_Action the handler for the action
    */
    function addAction($actionName, &$action)
    {
        $this->_actions[$actionName] =& $action;
    }


   /**
    * Adds a new page to the form
    *
    * @access public
    * @param  HTML_QuickForm_Page
    */
    function addPage(&$page)
    {
        $page->controller =& $this;
        $this->_pages[$page->getAttribute('id')] =& $page;
    }


   /**
    * Returns a page
    *
    * @access public
    * @param  string                Name of a page
    * @return HTML_QuickForm_Page   A reference to the page
    * @throws PEAR_Error
    */
    function &getPage($pageName)
    {
        if (!isset($this->_pages[$pageName])) {
            throw new \Exception('HTML_QuickForm_Controller: Unknown page "' . $pageName . '"');
        }

        return $this->_pages[$pageName];
    }


   /**
    * Handles an action.
    *
    * This will be called if the page itself does not have a handler
    * to a specific action. The method also loads and uses default handlers
    * for common actions, if specific ones were not added.
    *
    * @access public
    * @param  HTML_QuickForm_Page   The page that failed to handle the action
    * @param  string                Name of the action
    * @throws PEAR_Error
    */
    function handle(&$page, $actionName)
    {
        if (isset($this->_actions[$actionName])) {
            return $this->_actions[$actionName]->perform($page, $actionName);
        }
        switch ($actionName) {
            case 'next':
            case 'back':
            case 'submit':
            case 'display':
            case 'jump':
                include_once 'HTML/QuickForm/Action/' . ucfirst($actionName) . '.php';
                $className = 'HTML_QuickForm_Action_' . $actionName;
                $this->_actions[$actionName] =& new $className();
                return $this->_actions[$actionName]->perform($page, $actionName);
                break;
            default:
                throw new \Exception('HTML_QuickForm_Controller: Unhandled action "' . $actionName . '" in page "' . $page->getAttribute('id') . '"');

        } // switch
    }


   /**
    * Checks whether the form is modal.
    *
    * @access public
    * @return bool
    */
    function isModal()
    {
        return $this->_modal;
    }


   /**
    * Checks whether the pages of the controller are valid
    *
    * @access public
    * @param  string    If set, check only the pages before (not including) that page
    * @return bool
    * @throws PEAR_Error
    */
    function isValid($pageName = null)
    {
        $data =& $this->container();
        foreach (array_keys($this->_pages) as $key) {
            if (isset($pageName) && $pageName == $key) {
                return true;
            } elseif (!$data['valid'][$key]) {
                // We should handle the possible situation when the user has never
                // seen a page of a non-modal multipage form
                if (!$this->isModal() && null === $data['valid'][$key]) {
                    $page =& $this->_pages[$key];
                    // Fix for bug #8687: the unseen page was considered
                    // submitted, so defaults for checkboxes and multiselects
                    // were not used. Shouldn't break anything since this flag
                    // will be reset right below in loadValues().
                    $page->_flagSubmitted = false;
                    // Use controller's defaults and constants, if present
                    $this->applyDefaults($key);
                    $page->isFormBuilt() or $page->BuildForm();
                    // We use defaults and constants as if they were submitted
                    $data['values'][$key] = $page->exportValues();
                    $page->loadValues($data['values'][$key]);
                    // Is the page now valid?
                    if (PEAR::isError($valid = $page->validate())) {
                        return $valid;
                    }
                    $data['valid'][$key] = $valid;
                    if (true === $valid) {
                        continue;
                    }
                }
                return false;
            }
        }
        return true;
    }


   /**
    * Returns the name of the page before the given.
    *
    * @access public
    * @param  string
    * @return string
    */
    function getPrevName($pageName)
    {
        $prev = null;
        foreach (array_keys($this->_pages) as $key) {
            if ($key == $pageName) {
                return $prev;
            }
            $prev = $key;
        }
    }


   /**
    * Returns the name of the page after the given.
    *
    * @access public
    * @param  string
    * @return string
    */
    function getNextName($pageName)
    {
        $prev = null;
        foreach (array_keys($this->_pages) as $key) {
            if ($prev == $pageName) {
                return $key;
            }
            $prev = $key;
        }
        return null;
    }


   /**
    * Finds the (first) invalid page
    *
    * @access public
    * @return string  Name of an invalid page
    */
    function findInvalid()
    {
        $data =& $this->container();
        foreach (array_keys($this->_pages) as $key) {
            if (!$data['valid'][$key]) {
                return $key;
            }
        }
        return null;
    }


   /**
    * Extracts the names of the current page and the current action from
    * HTTP request data.
    *
    * @access public
    * @return array     first element is page name, second is action name
    */
    function getActionName()
    {
        if (is_array($this->_actionName)) {
            return $this->_actionName;
        }
        $names = array_map('preg_quote', array_keys($this->_pages));
        $regex = '/^_qf_(' . implode('|', $names) . ')_(.+?)(_x)?$/';
        foreach (array_keys($_REQUEST) as $key) {
            if (preg_match($regex, $key, $matches)) {
                return array($matches[1], $matches[2]);
            }
        }
        if (isset($_REQUEST['_qf_default'])) {
            $matches = explode(':', $_REQUEST['_qf_default'], 2);
            if (isset($this->_pages[$matches[0]])) {
                return $matches;
            }
        }
        reset($this->_pages);
        return array(key($this->_pages), 'display');
    }


   /**
    * Initializes default form values.
    *
    * @access public
    * @param  array  default values
    * @param  mixed  filter(s) to apply to default values
    * @throws PEAR_Error
    */
    function setDefaults($defaultValues = null, $filter = null)
    {
        if (is_array($defaultValues)) {
            $data =& $this->container();
            return $this->_setDefaultsOrConstants($data['defaults'], $defaultValues, $filter);
        }
    }


   /**
    * Initializes constant form values.
    * These values won't get overridden by POST or GET vars
    *
    * @access public
    * @param  array  constant values
    * @param  mixed  filter(s) to apply to constant values
    * @throws PEAR_Error
    */
    function setConstants($constantValues = null, $filter = null)
    {
        if (is_array($constantValues)) {
            $data =& $this->container();
            return $this->_setDefaultsOrConstants($data['constants'], $constantValues, $filter);
        }
    }


   /**
    * Adds new values to defaults or constants array
    *
    * @access   private
    * @param    array   array to add values to (either defaults or constants)
    * @param    array   values to add
    * @param    mixed   filters to apply to new values
    * @throws   PEAR_Error
    */
    function _setDefaultsOrConstants(&$values, $newValues, $filter = null)
    {
        if (isset($filter)) {
            if (is_array($filter) && (2 != count($filter) || !is_callable($filter))) {
                foreach ($filter as $val) {
                    if (!is_callable($val)) {
                        throw new \Exception("Callback function does not exist in QuickForm_Controller::_setDefaultsOrConstants()");
                    } else {
                        $newValues = $this->_arrayMapRecursive($val, $newValues);
                    }
                }
            } elseif (!is_callable($filter)) {
                throw new \Exception("Callback function does not exist in QuickForm_Controller::_setDefaultsOrConstants()");
            } else {
                $newValues = $this->_arrayMapRecursive($val, $newValues);
            }
        }
        $values = HTML_QuickForm::arrayMerge($values, $newValues);
    }


   /**
    * Recursively applies the callback function to the value
    *
    * @param    mixed   Callback function
    * @param    mixed   Value to process
    * @access   private
    * @return   mixed   Processed values
    */
    function _arrayMapRecursive($callback, $value)
    {
        if (!is_array($value)) {
            return call_user_func($callback, $value);
        } else {
            $map = array();
            foreach ($value as $k => $v) {
                $map[$k] = $this->_arrayMapRecursive($callback, $v);
            }
            return $map;
        }
    }


   /**
    * Sets the default values for the given page
    *
    * @access public
    * @param  string  Name of a page
    */
    function applyDefaults($pageName)
    {
        $data =& $this->container();
        if (!empty($data['defaults'])) {
            $this->_pages[$pageName]->setDefaults($data['defaults']);
        }
        if (!empty($data['constants'])) {
            $this->_pages[$pageName]->setConstants($data['constants']);
        }
    }


   /**
    * Returns the form's values
    *
    * @access public
    * @param  string    name of the page, if not set then returns values for all pages
    * @return array
    */
    function exportValues($pageName = null)
    {
        $data   =& $this->container();
        $values =  array();
        if (isset($pageName)) {
            $pages = array($pageName);
        } else {
            $pages = array_keys($data['values']);
        }
        foreach ($pages as $page) {
            // skip elements representing actions
            foreach ($data['values'][$page] as $key => $value) {
                if (0 !== strpos($key, '_qf_')) {
                    if (isset($values[$key]) && is_array($value)) {
                        $values[$key] = HTML_QuickForm::arrayMerge($values[$key], $value);
                    } else {
                        $values[$key] = $value;
                    }
                }
            }
        }
        return $values;
    }


   /**
    * Returns the element's value
    *
    * @access public
    * @param  string    name of the page
    * @param  string    name of the element in the page
    * @return mixed     value for the element
    */
    function exportValue($pageName, $elementName)
    {
        $data =& $this->container();
        return isset($data['values'][$pageName][$elementName])? $data['values'][$pageName][$elementName]: null;
    }
}
?>
