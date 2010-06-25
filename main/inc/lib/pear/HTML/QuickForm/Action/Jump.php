<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This action performs HTTP redirect to a specific page.
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
 * @version     SVN: $Id: Jump.php 289084 2009-10-02 06:53:09Z avb $
 * @link        http://pear.php.net/package/HTML_QuickForm_Controller
 */

/**
 * Class representing an action to perform on HTTP request.
 */
require_once 'HTML/QuickForm/Action.php';

/**
 * This action performs HTTP redirect to a specific page.
 *
 * @category    HTML
 * @package     HTML_QuickForm_Controller
 * @author      Alexey Borzov <avb@php.net>
 * @version     Release: 1.0.10
 */
class HTML_QuickForm_Action_Jump extends HTML_QuickForm_Action
{
   /**
    * Splits (part of) the URI into path and query components
    *
    * @param    string  String of the form 'foo?bar'
    * @return   array   Array of the form array('foo', '?bar)
    * @access   private
    */
    function _splitUri($uri)
    {
        if (false === ($qm = strpos($uri, '?'))) {
            return array($uri, '');
        } else {
            return array(substr($uri, 0, $qm), substr($uri, $qm));
        }
    }

   /**
    * Removes the '..' and '.' segments from the path component
    *
    * @param    string  Path component of the URL, possibly with '.' and '..' segments
    * @return   string  Path component of the URL with '.' and '..' segments removed
    * @access   private
    */
    function _normalizePath($path)
    {
        $pathAry = explode('/', $path);
        $i       = 1;

        do {
            if ('.' == $pathAry[$i]) {
                if ($i < count($pathAry) - 1) {
                    array_splice($pathAry, $i, 1);
                } else {
                    $pathAry[$i] = '';
                    $i++;
                }

            } elseif ('..' == $pathAry[$i] && $i > 1 && '..' != $pathAry[$i - 1]) {
                if ($i < count($pathAry) -1) {
                    array_splice($pathAry, $i - 1, 2);
                    $i--;
                } else {
                    array_splice($pathAry, $i - 1, 2, '');
                }

            } else {
                $i++;
            }
        } while ($i < count($pathAry));

        return implode('/', $pathAry);
    }

   /**
    * Resolves relative URL using current page's URL as base
    *
    * The method follows procedure described in section 4 of RFC 1808 and
    * passes the examples provided in section 5 of said RFC. Values from
    * $_SERVER array are used for calculation of "current URL"
    *
    * @param    string  Relative URL, probably from form's action attribute
    * @return   string  Absolute URL
    * @access   private
    */
    function _resolveRelativeURL($url)
    {
        $https  = !empty($_SERVER['HTTPS']) && ('off' != strtolower($_SERVER['HTTPS']));
        $scheme = ($https? 'https:': 'http:');
        if ('//' == substr($url, 0, 2)) {
            return $scheme . $url;

        } else {
            $host   = $scheme . '//' . $_SERVER['SERVER_NAME'] .
                      (($https && 443 == $_SERVER['SERVER_PORT'] ||
                        !$https && 80 == $_SERVER['SERVER_PORT'])? '': ':' . $_SERVER['SERVER_PORT']);
            if ('' == $url) {
                return $host . $_SERVER['REQUEST_URI'];

            } elseif ('/' == $url[0]) {
                return $host . $url;

            } else {
                list($basePath, $baseQuery) = $this->_splitUri($_SERVER['REQUEST_URI']);
                list($actPath, $actQuery)   = $this->_splitUri($url);
                if ('' == $actPath) {
                    return $host . $basePath . $actQuery;
                } else {
                    $path = substr($basePath, 0, strrpos($basePath, '/') + 1) . $actPath;
                    return $host . $this->_normalizePath($path) . $actQuery;
                }
            }
        }
    }

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
        // Bug #13087: RFC 2616 requires an absolute URI in Location header
        if (!preg_match('!^https?://!i', $action)) {
            $action = $this->_resolveRelativeURL($action);
        }
        $url    = $action . (false === strpos($action, '?')? '?': '&') .
                  $current->getButtonName('display') . '=true' .
                  ((!defined('SID') || '' == SID || ini_get('session.use_only_cookies'))? '': '&' . SID);
        header('Location: ' . $url);
        exit;
    }
}
?>
