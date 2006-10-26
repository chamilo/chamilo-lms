<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Alexey Borzov <avb@php.net>                                  |
// +----------------------------------------------------------------------+
//
// $Id: Back.php 6184 2005-09-07 10:08:17Z bmol $

require_once 'HTML/QuickForm/Action.php';

/**
 * The action for a 'back' button of wizard-type multipage form. 
 * 
 * @author  Alexey Borzov <avb@php.net>
 * @package HTML_QuickForm_Controller
 * @version $Revision: 6184 $
 */
class HTML_QuickForm_Action_Back extends HTML_QuickForm_Action
{
    function perform(&$page, $actionName)
    {
        // save the form values and validation status to the session
        $page->isFormBuilt() or $page->buildForm();
        $pageName =  $page->getAttribute('id');
        $data     =& $page->controller->container();
        $data['values'][$pageName] = $page->exportValues();
        if (!$page->controller->isModal()) {
            $data['valid'][$pageName]  = $page->validate();
        }

        // get the previous page and go to it
        // we don't check validation status here, 'jump' handler should
        if (null === ($prevName = $page->controller->getPrevName($pageName))) {
            return $page->handle('jump');
        } else {
            $prev =& $page->controller->getPage($prevName);
            return $prev->handle('jump');
        }
    }
}

?>
