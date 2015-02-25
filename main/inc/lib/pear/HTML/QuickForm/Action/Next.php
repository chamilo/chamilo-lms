<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * The action for a 'next' button of wizard-type multipage form.
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
 * @version     SVN: $Id: Next.php 289084 2009-10-02 06:53:09Z avb $
 * @link        http://pear.php.net/package/HTML_QuickForm_Controller
 */

/**
 * The action for a 'next' button of wizard-type multipage form.
 *
 * @category    HTML
 * @package     HTML_QuickForm_Controller
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 1.0.10
 */
class HTML_QuickForm_Action_Next extends HTML_QuickForm_Action
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

        // Modal form and page is invalid: don't go further
        if ($page->controller->isModal() && !$data['valid'][$pageName]) {
            return $page->handle('display');
        }
        // More pages?
        if (null !== ($nextName = $page->controller->getNextName($pageName))) {
            $next =& $page->controller->getPage($nextName);
            // Modified by Chamilo team, 16-MAR-2010.
            //$next->handle('jump');
            return $next->handle('jump');
            //
        // Consider this a 'finish' button, if there is no explicit one
        } elseif($page->controller->isModal()) {
            if ($page->controller->isValid()) {
                // Modified by Chamilo team, 16-MAR-2010.
                //$page->handle('process');
                return $page->handle('process');
                //
            } else {
                // this should redirect to the first invalid page
                // Modified by Chamilo team, 16-MAR-2010.
                //$page->handle('jump');
                return $page->handle('jump');
                //
            }
        } else {
            // Modified by Chamilo team, 16-MAR-2010.
            //$page->handle('display');
            return $page->handle('display');
            //
        }
    }
}

?>
