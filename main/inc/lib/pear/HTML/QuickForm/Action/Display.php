<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This action handles output of the form.
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
 * @copyright   2003-2009 The PHP Group
 * @license     http://www.php.net/license/3_01.txt PHP License 3.01
 * @version     SVN: $Id: Display.php 289084 2009-10-02 06:53:09Z avb $
 * @link        http://pear.php.net/package/HTML_QuickForm_Controller
 */

/**
 * Class representing an action to perform on HTTP request.
 */
require_once 'HTML/QuickForm/Action.php';

/**
 * This action handles output of the form.
 *
 * If you want to customize the form display, subclass this class and
 * override the _renderForm() method, you don't need to change the perform()
 * method itself.
 *
 * @category    HTML
 * @package     HTML_QuickForm_Controller
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 1.0.10
 */
class HTML_QuickForm_Action_Display extends HTML_QuickForm_Action
{
    function perform(&$page, $actionName)
    {
        $pageName = $page->getAttribute('id');
        // If the original action was 'display' and we have values in container then we load them
        // BTW, if the page was invalid, we should later call validate() to get the errors
        list(, $oldName) = $page->controller->getActionName();
        if ('display' == $oldName) {
            // If the controller is "modal" we should not allow direct access to a page
            // unless all previous pages are valid (see also bug #2323)
            if ($page->controller->isModal() && !$page->controller->isValid($page->getAttribute('id'))) {
                $target =& $page->controller->getPage($page->controller->findInvalid());
                // Modified by Chamilo team, 16-MAR-2010.
                //$target->handle('jump');
                return $target->handle('jump');
                //
            }
            $data =& $page->controller->container();
            if (!empty($data['values'][$pageName])) {
                $page->loadValues($data['values'][$pageName]);
                $validate = false === $data['valid'][$pageName];
            }
        }
        // set "common" defaults and constants
        $page->controller->applyDefaults($pageName);
        $page->isFormBuilt() or $page->buildForm();
        // if we had errors we should show them again
        if (isset($validate) && $validate) {
            if (PEAR::isError($err = $page->validate())) {
                return $err;
            }
        }
        // Modified by Chamilo team, 16-MAR-2010.
        //$this->_renderForm($page);
        return $this->_renderForm($page);
        //
    }


   /**
    * Actually outputs the form.
    *
    * If you want to customize the form's appearance (you most certainly will),
    * then you should override this method. There is no need to override perform()
    *
    * @access private
    * @param  HTML_QuickForm_Page  the page being processed
    */
    function _renderForm(&$page)
    {
        $page->display();
    }
}
?>
