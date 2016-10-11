<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor;

/**
 * Class Finder
 *
 * This class just modifies this line:
 * $class = 'elFinderVolume'.(isset($o['driver']) ? $o['driver'] : '');
 * in order to use normal classes and not a custom 'elFinderVolume' class.
 *
 * @package Chamilo\CoreBundle\Component\Editor
 */
class Finder extends \elFinder
{

    /**
     * Constructor
     *
     * @param  array  elFinder and roots configurations
     * @return void
     * @author Dmitry (dio) Levashov
     **/
    public function __construct($opts)
    {
        if (session_id() == '') {
            session_start();
        }

        $this->time  = $this->utime();
        $this->debug = (isset($opts['debug']) && $opts['debug'] ? true : false);
        $this->timeout = (isset($opts['timeout']) ? $opts['timeout'] : 0);
        $this->netVolumesSessionKey = !empty($opts['netVolumesSessionKey'])? $opts['netVolumesSessionKey'] : 'elFinderNetVolumes';
        $this->callbackWindowURL = (isset($opts['callbackWindowURL']) ? $opts['callbackWindowURL'] : '');

        // setlocale and global locale regists to elFinder::locale
        self::$locale = !empty($opts['locale']) ? $opts['locale'] : 'en_US.UTF-8';

        if (false === @setlocale(LC_ALL, self::$locale)) {
            self::$locale = setlocale(LC_ALL, '');
        }

        // bind events listeners
        if (!empty($opts['bind']) && is_array($opts['bind'])) {
            $_req = $_SERVER["REQUEST_METHOD"] == 'POST' ? $_POST : $_GET;
            $_reqCmd = isset($_req['cmd']) ? $_req['cmd'] : '';
            foreach ($opts['bind'] as $cmd => $handlers) {
                $doRegist = (strpos($cmd, '*') !== false);
                if (! $doRegist) {
                    $_getcmd = create_function('$cmd', 'list($ret) = explode(\'.\', $cmd);return trim($ret);');
                    $doRegist = ($_reqCmd && in_array($_reqCmd, array_map($_getcmd, explode(' ', $cmd))));
                }
                if ($doRegist) {
                    if (! is_array($handlers) || is_object($handlers[0])) {
                        $handlers = array($handlers);
                    }
                    foreach($handlers as $handler) {
                        if ($handler) {
                            if (is_string($handler) && strpos($handler, '.')) {
                                list($_domain, $_name, $_method) = array_pad(explode('.', $handler), 3, '');
                                if (strcasecmp($_domain, 'plugin') === 0) {
                                    if ($plugin = $this->getPluginInstance($_name, isset($opts['plugin'][$_name])? $opts['plugin'][$_name] : array())
                                        and method_exists($plugin, $_method)) {
                                        $this->bind($cmd, array($plugin, $_method));
                                    }
                                }
                            } else {
                                $this->bind($cmd, $handler);
                            }
                        }
                    }
                }
            }
        }

        if (!isset($opts['roots']) || !is_array($opts['roots'])) {
            $opts['roots'] = array();
        }

        // check for net volumes stored in session
        foreach ($this->getNetVolumes() as $root) {
            $opts['roots'][] = $root;
        }

        // "mount" volumes
        foreach ($opts['roots'] as $i => $o) {
            //$class = 'elFinderVolume'.(isset($o['driver']) ? $o['driver'] : '');
            $class = isset($o['driver']) ? $o['driver'] : '';

            if (class_exists($class)) {
                $volume = new $class();

                if ($volume->mount($o)) {
                    // unique volume id (ends on "_") - used as prefix to files hash
                    $id = $volume->id();

                    $this->volumes[$id] = $volume;
                    if (!$this->default && $volume->isReadable()) {
                        $this->default = $this->volumes[$id];
                    }
                } else {
                    $this->mountErrors[] = 'Driver "'.$class.'" : '.implode(' ', $volume->error());
                }
            } else {
                $this->mountErrors[] = 'Driver "'.$class.'" does not exists';
            }
        }

        // if at least one readable volume - ii desu >_<
        $this->loaded = !empty($this->default);
    }
}
