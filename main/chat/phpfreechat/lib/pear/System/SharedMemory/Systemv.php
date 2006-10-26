<?php
/**
*
* The System V driver for SharedMemory
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
* The methods PEAR SharedMemory uses to interact with PHP's System V extension
* for interacting with System V shared memory
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

// {{{ class System_SharedMemory_Systemv

class System_SharedMemory_Systemv extends System_SharedMemory_Common
{
    // {{{ properties
    /**
    * true if plugin was connected to backend
    *
    * @var bool
    *
    * @access private
    */
    var $_connected;

    /**
    * handler for shmop_* functions
    *
    * @var string
    *
    * @access private
    */
    var $_h;
    // }}}
    // {{{ constructor

    /**
     * Constructor. Init all variables.
     *
     * @param array $options
     *
     * @access public
     */
    function System_SharedMemory_Systemv($options)
    {
        extract($this->_default($options, array
        (
            'size'    => false,
            'project' => 's',
        )));

       if ($size === false) {
           $this->_h = shm_attach($this->_ftok($project));
       } else {
           if ($size < SHMMIN || $size > SHMMAX) {
               return $this->_connection = false;
           }

           $this->_h = shm_attach($this->_ftok($project), $size);
       }

       $this->_connection = true;
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
     * @param int $name name of variable
     *
     * @return mixed value of the variable
     * @access public
     */
    function get($name)
    {
        return shm_get_var($this->_h, $this->_s2i($name));
    }
    // }}}
    // {{{ set()

    /**
     * set value of variable in shared mem
     *
     * @param string $name  name of the variable
     * @param string $value value of the variable
     *
     * @return bool true on success, false on fail
     * @access public
     */
    function set($name, $value)
    {
        return shm_put_var($this->_h, $this->_s2i($name), $value);
    }
    // }}}
    // {{{ rm()

    /**
     * remove variable from memory
     *
     * @param string $name  name of the variable
     *
     * @return bool true on success, false on fail
     * @access public
     */
    function rm($name)
    {
        return shm_remove_var($this->_h, $this->_s2i($name));
    }
    // }}}
    // {{{ _ftok()

    /**
     * ftok emulation for Windows
     *
     * @param string $project project ID
     *
     * @access private
     */
    function _ftok($project)
    {
        if (function_exists('ftok')) {
            return ftok(__FILE__, $project);
        }

        $s = stat(__FILE__);
        return sprintf("%u", (($s['ino'] & 0xffff) | (($s['dev'] & 0xff) << 16) |
        (($project & 0xff) << 24)));
    }
    // }}}
    // {{{ _s2i()

    /**
     * convert string to int
     *
     * @param string $name string to conversion
     *
     * @access private
     */
    function _s2i($name)
    {
        return unpack('N', str_pad($name, 4, "\0", STR_PAD_LEFT));
    }
    // }}}
}
// }}}
?>