<?php

/**
 * Class representing a page of a multipage form.
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
 * @version     SVN: $Id: Page.php 289084 2009-10-02 06:53:09Z avb $
 * @link        http://pear.php.net/package/HTML_QuickForm_Controller
 */

/**
 * Class representing a page of a multipage form.
 *
 * Generally you'll need to subclass this and define your buildForm()
 * method that will build the form. While it is also possible to instantiate
 * this class and build the form manually, this is not the recommended way.
 *
 * @category    HTML
 * @package     HTML_QuickForm_Controller
 * @author      Alexey Borzov <avb@php.net>
 * @author      Bertrand Mansion <bmansion@mamasam.com>
 * @version     Release: 1.0.10
 */
class HTML_QuickForm_Page extends HTML_QuickForm
{
    /**
     * Contains the mapping of actions to corresponding HTML_QuickForm_Action objects
     * @var array
     */
    var $_actions = array();

    /**
     * Contains a reference to a Controller object containing this page
     * @var      HTML_QuickForm_Controller
     * @access   public
     */
    var $controller = null;

    /**
     * Should be set to true on first call to buildForm()
     * @var bool
     */
    var $_formBuilt = false;

    /**
     * Class constructor
     *
     * @access public
     */
    function HTML_QuickForm_Page($formName, $method = 'post', $target = '', $attributes = null)
    {
        $this->HTML_QuickForm($formName, $method, '', $target, $attributes);
    }


    /**
     * Registers a handler for a specific action.
     *
     * @access public
     *
     * @param string                name of the action
     * @param HTML_QuickForm_Action the handler for the action
     */
    function addAction($actionName, &$action)
    {
        $this->_actions[$actionName] =& $action;
    }


    /**
     * Handles an action.
     *
     * If an Action object was not registered here, controller's handle()
     * method will be called.
     *
     * @access public
     *
     * @param string Name of the action
     *
     * @throws PEAR_Error
     */
    function handle($actionName)
    {
        if (isset($this->_actions[$actionName])) {
            return $this->_actions[$actionName]->perform($this, $actionName);
        } else {
            return $this->controller->handle($this, $actionName);
        }
    }


    /**
     * Returns a name for a submit button that will invoke a specific action.
     *
     * @access public
     *
     * @param string  Name of the action
     *
     * @return string  "name" attribute for a submit button
     */
    function getButtonName($actionName)
    {
        return '_qf_'.$this->getAttribute('id').'_'.$actionName;
    }


    /**
     * Loads the submit values from the array.
     *
     * The method is NOT intended for general usage.
     *
     * @param array  'submit' values
     *
     * @access public
     */
    function loadValues($values)
    {
        $this->_flagSubmitted = true;
        $this->_submitValues = $values;
        foreach (array_keys($this->_elements) as $key) {
            $this->_elements[$key]->onQuickFormEvent('updateValue', null, $this);
        }
    }

    /**
     * Builds a form.
     *
     * You should override this method when you subclass HTML_QuickForm_Page,
     * it should contain all the necessary addElement(), applyFilter(), addRule()
     * and possibly setDefaults() and setConstants() calls. The method will be
     * called on demand, so please be sure to set $_formBuilt property to true to
     * assure that the method works only once.
     *
     * @access public
     * @abstract
     */
    function buildForm()
    {
        $this->_formBuilt = true;
    }

    /**
     * Checks whether the form was already built.
     *
     * @access public
     * @return bool
     */
    function isFormBuilt()
    {
        return $this->_formBuilt;
    }


    /**
     * Sets the default action invoked on page-form submit
     *
     * This is necessary as the user may just press Enter instead of
     * clicking one of the named submit buttons and then no action name will
     * be passed to the script.
     *
     * @access public
     *
     * @param string    default action name
     */
    function setDefaultAction($actionName)
    {
        if ($this->elementExists('_qf_default')) {
            $element =& $this->getElement('_qf_default');
            $element->setValue($this->getAttribute('id').':'.$actionName);
        } else {
            $this->addElement('hidden', '_qf_default', $this->getAttribute('id').':'.$actionName);
        }
    }


    /**
     * Returns 'safe' elements' values
     *
     * @param mixed   Array/string of element names, whose values we want. If not set then return all elements.
     * @param bool    Whether to remove internal (_qf_...) values from the resultant array
     */
    function exportValues($elementList = null, $filterInternal = false)
    {
        $values = parent::exportValues($elementList);
        if ($filterInternal) {
            foreach (array_keys($values) as $key) {
                if (0 === strpos($key, '_qf_')) {
                    unset($values[$key]);
                }
            }
        }

        return $values;
    }
}

?>
