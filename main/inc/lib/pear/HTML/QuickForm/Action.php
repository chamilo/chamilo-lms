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
// | Authors: Alexey Borzov <avb@php.net>                                 |
// |          Bertrand Mansion <bmansion@mamasam.com>                     |
// +----------------------------------------------------------------------+
//
// $Id: Action.php 6184 2005-09-07 10:08:17Z bmol $

/**
 * Class representing an action to perform on HTTP request. The Controller
 * will select the appropriate Action to call on the request and call its
 * perform() method. The subclasses of this class should implement all the
 * necessary business logic.
 *
 * @author  Alexey Borzov <avb@php.net>
 * @package HTML_QuickForm_Controller
 * @version $Revision: 6184 $
 */
class HTML_QuickForm_Action
{
   /**
    * Processes the request. This method should be overriden by child classes to
    * provide the necessary logic.
    *
    * @access public
    * @param  object HTML_QuickForm_Page    the current form-page
    * @param  string    Current action name, as one Action object can serve multiple actions
    */
    function perform(&$page, $actionName)
    {
    }
}

?>
