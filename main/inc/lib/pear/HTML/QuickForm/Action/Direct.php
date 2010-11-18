<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This action allows to go to a specific page of a multipage form.
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
 * @version     SVN: $Id: Direct.php 289084 2009-10-02 06:53:09Z avb $
 * @link        http://pear.php.net/package/HTML_QuickForm_Controller
 */

/**
 * Class representing an action to perform on HTTP request.
 */
require_once 'HTML/QuickForm/Action.php';

/**
 * This action allows to go to a specific page of a multipage form.
 *
 * Please note that the name for this action in addAction() should NOT be
 * 'direct', but the name of the page you wish to go to.
 *
 * @category    HTML
 * @package     HTML_QuickForm_Controller
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 1.0.10
 */
class HTML_QuickForm_Action_Direct extends HTML_QuickForm_Action
{
    function perform(&$page, $actionName)
    {
        // save the form values and validation status to the session
        $page->isFormBuilt() or $page->buildForm();
        $pageName =  $page->getAttribute('id');
        $data     =& $page->controller->container();
        $data['values'][$pageName] = $page->exportValues();
        if (PEAR::isError($valid = $page->validate())) {
            return $valid;
        }
        $data['valid'][$pageName] = $valid;

        $target =& $page->controller->getPage($actionName);
        if (PEAR::isError($target)) {
            return $target;
        } else {
            return $target->handle('jump');
        }
    }
}
?>
