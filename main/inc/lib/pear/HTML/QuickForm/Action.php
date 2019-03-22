<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class representing an action to perform on HTTP request.
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
 * @version     SVN: $Id: Action.php 289084 2009-10-02 06:53:09Z avb $
 * @link        http://pear.php.net/package/HTML_QuickForm_Controller
 */

/**
 * Class representing an action to perform on HTTP request.
 *
 * The Controller will select the appropriate Action to call on the request and
 * call its perform() method. The subclasses of this class should implement all
 * the necessary business logic.
 *
 * @category    HTML
 * @package     HTML_QuickForm_Controller
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 1.0.10
 * @abstract
 */
class HTML_QuickForm_Action
{
   /**
    * Processes the request. This method should be overriden by child classes to
    * provide the necessary logic.
    *
    * @access   public
    * @param    HTML_QuickForm_Page    The current form-page
    * @param    string                 Current action name, as one Action object
    *                                  can serve multiple actions
    * @throws   PEAR_Error
    * @abstract
    */
    function perform(&$page, $actionName)
    {
    }
}

?>
