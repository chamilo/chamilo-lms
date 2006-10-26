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
// $Id: Jump.php 6184 2005-09-07 10:08:17Z bmol $

require_once 'HTML/QuickForm/Action.php';

/**
 * The action handles the HTTP redirect to a specific page.
 * 
 * @author  Alexey Borzov <avb@php.net>
 * @package HTML_QuickForm_Controller
 * @version $Revision: 6184 $
 */
class HTML_QuickForm_Action_Jump extends HTML_QuickForm_Action
{
    function perform(&$page, $actionName)
    {
        // check whether the page is valid before trying to go to it
        if ($page->controller->isModal()) {
            // we check whether *all* pages up to current are valid
            // if there is an invalid page we go to it, instead of the
            // requested one
            $pageName = $page->getAttribute('id');
            if (!$page->controller->isValid($pageName)) {
                $pageName = $page->controller->findInvalid();
            }
            $current =& $page->controller->getPage($pageName);

        } else {
            $current =& $page;
        }
        // generate the URL for the page 'display' event and redirect to it
        $action = $current->getAttribute('action');
        $url    = $action . (false === strpos($action, '?')? '?': '&') .
                  $current->getButtonName('display') . '=true' .
                  ((!defined('SID') || '' == SID)? '': '&' . SID);
        header('Location: ' . $url);
        exit;
    }
}
?>
