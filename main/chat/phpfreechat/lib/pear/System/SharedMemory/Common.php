<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4: */
/**
*
* Contains the System_SharedMemory_Common base class
*
* PHP versions 4 and 5
*
* LICENSE: This source file is subject to version 3.0 of the PHP license
* that is available through the world-wide-web at the following URI:
* http://www.php.net/license/3_0.txt.  If you did not receive a copy of
* the PHP License and are unable to obtain it through the web, please
* send a note to license@php.net so we can mail you a copy immediately.
*
* @category   System
* @package    System_Sharedmemory
* @author     Evgeny Stepanischev <bolk@lixil.ru>
* @copyright  2005 Evgeny Stepanischev
* @license    http://www.php.net/license/3_0.txt  PHP License 3.0
* @version    CVS: $Id:$
* @link       http://pear.php.net/package/System_SharedMemory
*/

/**
*
* System_SharedMemory_Common is the base class from which each database driver class extends
*
* @category   System
* @package    System_Sharedmemory
* @package    System_Sharedmemory
* @author     Evgeny Stepanischev <bolk@lixil.ru>
* @copyright  2005 Evgeny Stepanischev
* @license    http://www.php.net/license/3_0.txt  PHP License 3.0
* @version    CVS: $Id:$
* @link       http://pear.php.net/package/System_SharedMemory
*/

// {{{ class System_SharedMemory_Common

class System_SharedMemory_Common
{
    // {{{ isConnected()

   /**
    * returns true if plugin was 
    * successfully connected to backend
    *
    * @return bool true if connected
    * @access public
    */
    function isConnected()
    {
        return true;
    }

    // }}}
    // {{{ engineName()

    /**
     * returns name of current engine
     *
     * @return string name of engine
     * @access public
     */
    function engineName()
    {
        return strtolower(substr(basename(__FILE__), 0, -4));
    }

    // }}}
    // {{{ _default()

    /**
     * fill non-set properties by def values
     *
     * @param array options array
     * @param array hash of pairs keys and default values
     *
     * @return array filled array
     * @access public
     */
    function _default($options, $def)
    {
        foreach ($def as $key=>$val) {
            if (!isset($options[$key])) {
                $options[$key] = $val;
            }
        }

        return $options;
    }
    // }}}
}
// }}}
?>