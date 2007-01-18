<?php
/**
*
* The Plain File driver for SharedMemory
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
* The methods PEAR SharedMemory uses to interact with plain file
* for interacting with shared memory via plain files
*
* These methods overload the ones declared System_SharedMemory_Common
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


require_once 'Common.php';
require_once "PEAR.php";

// {{{ class System_SharedMemory

class System_SharedMemory_File extends System_SharedMemory_Common
{
    // {{{ properties
    /**
    * Contains internal options
    *
    * @var string
    *
    * @access private
    */
    var $_options;

    /**
    * true if plugin was connected to backend
    *
    * @var bool
    *
    * @access private
    */
    var $_connected;
    // }}}
    // {{{ constructor

    /**
     * Constructor. Init all variables.
     *
     * @param array $options
     *
     * @access public
     */
    function System_SharedMemory_File($options)
    {
        $this->_options = $this->_default($options, array
        (
            'tmp'  => '/tmp',
        ));

        $this->_connected = is_writeable($this->_options['tmp']) && is_dir($this->_options['tmp']);
    }
    // }}}
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
        return $this->_connected;
    }
    // }}}
    // {{{ get()

    /**
     * returns value of variable in shared mem
     *
     * @param string $name  name of the variable
     * @param string $value value of the variable
     *
     * @return mixed true on success or PEAR_error on fail
     * @access public
     */
    function get($name)
    {
        $name = $this->_options['tmp'].'/smf_'.md5($name);

        if (!file_exists($name)) {
            return array();
        }

        $fp = fopen($name, 'rb');
        if (is_resource($fp)) {
            flock ($fp, LOCK_SH);

            $str = fread($fp, filesize($name));
            fclose($fp);
            return $str == '' ? array() : unserialize($str);
        }

        return PEAR::raiseError('Cannot open file.', 1);
    }
    // }}}
    // {{{ set()

    /**
     * set value of variable in shared mem
     *
     * @param string $name  name of the variable
     * @param string $value value of the variable
     *
     * @return mixed true on success or PEAR_error on fail
     * @access public
     */
    function set($name, $value)
    {
        $fp = fopen($this->_options['tmp'].'/smf_'.md5($name), 'ab');
        if (is_resource($fp)) {
            flock ($fp, LOCK_EX);
            ftruncate($fp, 0);
            fseek($fp, 0);

            fwrite($fp, serialize($value));
            fclose($fp);
            clearstatcache();
            return true;
        }

        return PEAR::raiseError('Cannot write to file.', 2);
    }
    // }}}
    // {{{ rm()

    /**
     * remove variable from memory
     *
     * @param string $name  name of the variable
     *
     * @return mixed true on success or PEAR_error on fail
     * @access public
     */
    function rm($name)
    {
        $name = $this->_options['tmp'].'/smf_'.md5($name);

        if (file_exists($name)) {
            unlink($name);
        }
    }
    // }}}

}
// }}}
?>