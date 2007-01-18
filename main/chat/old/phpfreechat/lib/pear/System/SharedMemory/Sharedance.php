<?php
/**
*
* The Sharedance driver for SharedMemory
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
* The methods PEAR SharedMemory uses to interact with Sharedance
* for interacting with Sharedance shared memory
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

// {{{ class System_SharedMemory_Sharedance

class System_SharedMemory_Sharedance extends System_SharedMemory_Common
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
    * connection handler
    *
    * @var string
    *
    * @access private
    */
    var $_h;

    /**
    * Contains internal options
    *
    * @var string
    *
    * @access private
    */
    var $_options;
    // }}}

    // {{{ constructor

    /**
     * Constructor. Init all variables.
     *
     * @param array $options
     *
     * @access public
     */
    function System_SharedMemory_Sharedance($options)
    {
        $this->_options = ($this->_default($options, array
        (
            'host' => '127.0.0.1',
            'port' => 1042,
            'timeout' => 10,
        )));

        $this->_h = null;
        $this->_open();
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
     * @param string $name name of variable
     *
     * @return mixed value of the variable
     * @access public
     */
     function get($name)
     {
         $this->_open();
         $s = 'F' . pack('N', strlen($name)) . $name;
         fwrite($this->_h, $s);

         for ($data = ''; !feof($this->_h);) {
             $data .= fread($this->_h, 4096);
         }

         $this->_close();

         return $data === '' ? null : unserialize($data);
     }
     // }}}
     // {{{ set()

    /**
     * set value of variable in shared mem
     *
     * @param string $name  name of the variable
     * @param string $value value of the variable
     * @param int $ttl (optional) time to life of the variable
     *
     * @return bool true on success
     * @access public
     */
    function set($name, $value)
    {
        $this->_open();
        $value = serialize($value);
        $s = 'S' . pack('NN', strlen($name), strlen($value)) . $name . $value;

        fwrite($this->_h, $s);
        $ret = fgets($this->_h);
        $this->_close();

        return $ret === "OK\n";
    }
    // }}}
    // {{{ rm()

    /**
     * remove variable from memory
     *
     * @param string $name  name of the variable
     *
     * @return bool true on success
     * @access public
     */
     function rm($name)
     {
         $this->_open();
         $s = 'D' . pack('N', strlen($name)) . $name;
         fwrite($this->_h, $s);
         $ret = fgets($this->_h);
         $this->_close();

         return $ret === "OK\n";
     }
     // }}}
     // {{{ _close()

     /**
     * close connection to backend
     * (sharedance isn't support persistent connection)
     *
     * @access private
     */
     function _close()
     {
         fclose($this->_h);
         $this->_h = false;
     }
     // }}}
     // {{{ _open()

     /**
     * open connection to backend if it doesn't connected yet
     *
     * @access private
     */
     function _open()
     {
         if (!is_resource($this->_h)) {
             $this->_h = fsockopen($this->_options['host'], $this->_options['port'], $_, $_, $this->_options['timeout']);
             $this->_connected = is_resource($this->_h);         
         }
     }
     // }}}
}
// }}}
?>