<?php
/**
*
* The SQLite driver for SharedMemory
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
* The methods PEAR SharedMemory uses to interact with PHP's SQLite extension
* for interacting with SQLite shared memory
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

// {{{ 

class System_SharedMemory_Sqlite extends System_SharedMemory_Common
{
    // {{{ properties
    /**
    * SQLite object handler
    *
    * @var object
    *
    * @access private
    */
    var $_h;

    /**
    * true if plugin was connected to backend
    *
    * @var bool
    *
    * @access private
    */
    var $_connected;

    /**
    * hash of SQLite table options
    *
    * @var array
    *
    * @access private
    */
    var $_options;
    // }}}
    // {{{ constructor

    /**
     * Constructor. Init all variables.
     * SQLite table must be created:
     * CREATE sharedmemory(var text PRIMARY KEY, value TEXT)
     * It's very important!
     *
     * @param array $options
     *
     * @access public
     */
    function System_SharedMemory_Sqlite($options)
    {
        $this->_options = $this->_default($options, array
        (
            'db' => ':memory:',
            'table'  => 'sharedmemory',
            'var' => 'var',
            'value' => 'value',
            'persistent' => false,
        ));

        $func = $this->_options['persistent'] ? 'sqlite_popen' : 'sqlite_open';

        $this->_h = $func($this->_options['db']);
        $this->_connected = is_resource($this->_h);
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
        $name   = sqlite_escape_string($name);
        $sql = 'SELECT '.$this->_options['value'].
               ' FROM '.$this->_options['table'].
               ' WHERE '.$this->_options['var'].'=\''.$name.'\''.
               ' LIMIT 1';

        $result = sqlite_query($this->_h, $sql);
        if (sqlite_num_rows($result)) {
            return unserialize(sqlite_fetch_single($result));
        }

        return null;
    }
    // }}}
    // {{{ set()

    /**
     * set value of variable in shared mem
     *
     * @param string $name  name of the variable
     * @param string $value value of the variable
     *
     * @return bool true on success
     * @access public
     */
    function set($name, $value)
    {
        $name  = sqlite_escape_string($name);
        $value = sqlite_escape_string(serialize($value));

        $sql  = 'REPLACE INTO '.$this->_options['table'].
                ' ('.$this->_options['var'].', '.$this->_options['value'].
                'VALUES (\''.$name.'\', \''.$value.'\')';

        sqlite_query($this->_h, $sql);
        return true;
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
        $name  = sqlite_escape_string($name);

        $sql  = 'DELETE FROM '.$this->_options['table'].
               ' WHERE '.$this->_options['var'].'=\''.$name.'\'';

        sqlite_query($this->_h, $sql);
        return true;
    }
    // }}}
}
// }}}
?>