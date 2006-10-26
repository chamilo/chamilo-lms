<?php

/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4: */
/**
*
* common OO-style shared memory API
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
* Backend independent OO-interface
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

// {{{ class System_SharedMemory

class System_SharedMemory
{
    // {{{ &factory()

    /**
     * Create a new shared mem object
     *
     * @param string $type  the shared mem type (or false on autodetect)
     * @param array  $options  an associative array of option names and values
     *
     * @return object  a new System_Shared object
     *
     */
    
    function &factory($type = false, $options = array())
    {
        if ($type === false) {
            $type = System_SharedMemory::getAvailableTypes(true);
        } else {
            $type = ucfirst(strtolower($type));
        }

        require_once dirname(__FILE__).'/SharedMemory/'.$type . '.php';
        $class = 'System_SharedMemory_' . $type;

        $ref = &new $class($options);
        return $ref;
    }

    // }}}
    // {{{ getAvailableTypes()

    /**
     * Get available types or first one
     *
     * @param bool $only_first false if need all types and true if only first one
     *
     * @return mixed list of available types (array) or first one (string)
     *
     */

     function getAvailableTypes($only_first = false)
     {
        $detect = array
        (
            'eaccelerator' => 'Eaccelerator',   // Eaccelerator (Turck MMcache fork)
            'mmcache'      => 'Mmcache',        // Turck MMCache
            'Memcache'     => 'Memcache',       // Memched
            'shmop_open'   => 'Shmop',          // Shmop
            'apc_fetch'    => 'Apc',            // APC
            'apache_note'  => 'Apachenote',     // Apache note
            'shm_get_var'  => 'Systemv',        // System V
            /*'sqlite_open'  => 'Sqlite',      */   // SQLite
            'file'         => 'File',           // Plain text
            'fsockopen'    => 'Sharedance',     // Sharedance
        );

        $types = array();

        foreach ($detect as $func=>$val) {
            if (function_exists($func) || class_exists($func)) {
                if ($only_first) {
                    return $val;
                }

                $types[] = $val;
            }
        }

        return $types;
     }

    // }}}
}
// }}}
?>