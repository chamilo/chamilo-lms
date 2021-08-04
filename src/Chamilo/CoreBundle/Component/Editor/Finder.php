<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor;

use elFinder;
use elFinderSession;
use elFinderSessionInterface;
use Exception;

/**
 * Based in \elFinder this class only has a small change that allows use
 * drivers with out adding elFinderVolume as class name.
 *
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
     * Constructor.
     *
     * @param array $opts elFinder and roots configurations
     *
     * @author Dmitry (dio) Levashov
     */
    public function __construct($opts)
    {
        // set default_charset
        if (version_compare(PHP_VERSION, '5.6', '>=')) {
            if (($_val = ini_get('iconv.internal_encoding')) && strtoupper($_val) !== 'UTF-8') {
                ini_set('iconv.internal_encoding', '');
            }
            if (($_val = ini_get('mbstring.internal_encoding')) && strtoupper($_val) !== 'UTF-8') {
                ini_set('mbstring.internal_encoding', '');
            }
            if (($_val = ini_get('internal_encoding')) && strtoupper($_val) !== 'UTF-8') {
                ini_set('internal_encoding', '');
            }
        } else {
            if (function_exists('iconv_set_encoding') && strtoupper(iconv_get_encoding('internal_encoding')) !== 'UTF-8') {
                iconv_set_encoding('internal_encoding', 'UTF-8');
            }
            if (function_exists('mb_internal_encoding') && strtoupper(mb_internal_encoding()) !== 'UTF-8') {
                mb_internal_encoding('UTF-8');
            }
        }
        ini_set('default_charset', 'UTF-8');
        // define accept constant of server commands path
        !defined('ELFINDER_TAR_PATH') && define('ELFINDER_TAR_PATH', 'tar');
        !defined('ELFINDER_GZIP_PATH') && define('ELFINDER_GZIP_PATH', 'gzip');
        !defined('ELFINDER_BZIP2_PATH') && define('ELFINDER_BZIP2_PATH', 'bzip2');
        !defined('ELFINDER_XZ_PATH') && define('ELFINDER_XZ_PATH', 'xz');
        !defined('ELFINDER_ZIP_PATH') && define('ELFINDER_ZIP_PATH', 'zip');
        !defined('ELFINDER_UNZIP_PATH') && define('ELFINDER_UNZIP_PATH', 'unzip');
        !defined('ELFINDER_RAR_PATH') && define('ELFINDER_RAR_PATH', 'rar');
        !defined('ELFINDER_UNRAR_PATH') && define('ELFINDER_UNRAR_PATH', 'unrar');
        !defined('ELFINDER_7Z_PATH') && define('ELFINDER_7Z_PATH', (substr(PHP_OS, 0, 3) === 'WIN') ? '7z' : '7za');
        !defined('ELFINDER_CONVERT_PATH') && define('ELFINDER_CONVERT_PATH', 'convert');
        !defined('ELFINDER_IDENTIFY_PATH') && define('ELFINDER_IDENTIFY_PATH', 'identify');
        !defined('ELFINDER_EXIFTRAN_PATH') && define('ELFINDER_EXIFTRAN_PATH', 'exiftran');
        !defined('ELFINDER_JPEGTRAN_PATH') && define('ELFINDER_JPEGTRAN_PATH', 'jpegtran');
        !defined('ELFINDER_FFMPEG_PATH') && define('ELFINDER_FFMPEG_PATH', 'ffmpeg');

        !defined('ELFINDER_DISABLE_ZIPEDITOR') && define('ELFINDER_DISABLE_ZIPEDITOR', false);

        // enable(true)/disable(false) handling postscript on ImageMagick
        // Should be `false` as long as there is a Ghostscript vulnerability
        // see https://artifex.com/news/ghostscript-security-resolved/
        !defined('ELFINDER_IMAGEMAGICK_PS') && define('ELFINDER_IMAGEMAGICK_PS', false);

        // for backward compat
        $this->version = (string) self::$ApiVersion;

        // set error handler of WARNING, NOTICE
        $errLevel = E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_STRICT | E_RECOVERABLE_ERROR;
        if (defined('E_DEPRECATED')) {
            $errLevel |= E_DEPRECATED | E_USER_DEPRECATED;
        }
        set_error_handler('elFinder::phpErrorHandler', $errLevel);

        // convert PATH_INFO to GET query
        if (!empty($_SERVER['PATH_INFO'])) {
            $_ps = explode('/', trim($_SERVER['PATH_INFO'], '/'));
            if (!isset($_GET['cmd'])) {
                $_cmd = $_ps[0];
                if (isset($this->commands[$_cmd])) {
                    $_GET['cmd'] = $_cmd;
                    $_i = 1;
                    foreach (array_keys($this->commands[$_cmd]) as $_k) {
                        if (isset($_ps[$_i])) {
                            if (!isset($_GET[$_k])) {
                                $_GET[$_k] = $_ps[$_i];
                            }
                        } else {
                            break;
                        }
                    }
                }
            }
        }

        // set elFinder instance
        elFinder::$instance = $this;

        // setup debug mode
        $this->debug = (isset($opts['debug']) && $opts['debug'] ? true : false);
        if ($this->debug) {
            error_reporting(defined('ELFINDER_DEBUG_ERRORLEVEL') ? ELFINDER_DEBUG_ERRORLEVEL : -1);
            ini_set('diaplay_errors', '1');
        }

        if (!interface_exists('elFinderSessionInterface')) {
            include_once __DIR__.'/elFinderSessionInterface.php';
        }

        // session handler
        if (!empty($opts['session']) && $opts['session'] instanceof elFinderSessionInterface) {
            $this->session = $opts['session'];
        } else {
            $sessionOpts = [
                'base64encode' => !empty($opts['base64encodeSessionData']),
                'keys' => [
                    'default' => !empty($opts['sessionCacheKey']) ? $opts['sessionCacheKey'] : 'elFinderCaches',
                    'netvolume' => !empty($opts['netVolumesSessionKey']) ? $opts['netVolumesSessionKey'] : 'elFinderNetVolumes',
                ],
            ];
            if (!class_exists('elFinderSession')) {
                include_once __DIR__.'/elFinderSession.php';
            }
            $this->session = new elFinderSession($sessionOpts);
        }
        // try session start | restart
        $this->session->start();

        $sessionUseCmds = [];
        if (isset($opts['sessionUseCmds']) && is_array($opts['sessionUseCmds'])) {
            $sessionUseCmds = $opts['sessionUseCmds'];
        }

        // set self::$volumesCnt by HTTP header "X-elFinder-VolumesCntStart"
        if (isset($_SERVER['HTTP_X_ELFINDER_VOLUMESCNTSTART']) && ($volumesCntStart = intval($_SERVER['HTTP_X_ELFINDER_VOLUMESCNTSTART']))) {
            self::$volumesCnt = $volumesCntStart;
        }

        $this->time = $this->utime();
        $this->sessionCloseEarlier = isset($opts['sessionCloseEarlier']) ? (bool) $opts['sessionCloseEarlier'] : true;
        $this->sessionUseCmds = array_flip($sessionUseCmds);
        $this->timeout = (isset($opts['timeout']) ? $opts['timeout'] : 0);
        $this->uploadTempPath = (isset($opts['uploadTempPath']) ? $opts['uploadTempPath'] : '');
        $this->callbackWindowURL = (isset($opts['callbackWindowURL']) ? $opts['callbackWindowURL'] : '');
        $this->maxTargets = (isset($opts['maxTargets']) ? intval($opts['maxTargets']) : $this->maxTargets);
        elFinder::$commonTempPath = (isset($opts['commonTempPath']) ? $opts['commonTempPath'] : './.tmp');
        if (!is_writable(elFinder::$commonTempPath)) {
            elFinder::$commonTempPath = sys_get_temp_dir();
            if (!is_writable(elFinder::$commonTempPath)) {
                elFinder::$commonTempPath = '';
            }
        }
        $this->maxArcFilesSize = isset($opts['maxArcFilesSize']) ? intval($opts['maxArcFilesSize']) : 0;
        $this->optionsNetVolumes = (isset($opts['optionsNetVolumes']) && is_array($opts['optionsNetVolumes'])) ? $opts['optionsNetVolumes'] : [];
        if (isset($opts['itemLockExpire'])) {
            $this->itemLockExpire = intval($opts['itemLockExpire']);
        }

        // deprecated settings
        $this->netVolumesSessionKey = !empty($opts['netVolumesSessionKey']) ? $opts['netVolumesSessionKey'] : 'elFinderNetVolumes';
        self::$sessionCacheKey = !empty($opts['sessionCacheKey']) ? $opts['sessionCacheKey'] : 'elFinderCaches';

        // check session cache
        $_optsMD5 = md5(json_encode($opts['roots']));
        if ($this->session->get('_optsMD5') !== $_optsMD5) {
            $this->session->set('_optsMD5', $_optsMD5);
        }

        // setlocale and global locale regists to elFinder::locale
        self::$locale = !empty($opts['locale']) ? $opts['locale'] : 'en_US.UTF-8';
        if (false === setlocale(LC_ALL, self::$locale)) {
            self::$locale = setlocale(LC_ALL, '');
        }

        // set defaultMimefile
        elFinder::$defaultMimefile = (isset($opts['defaultMimefile']) ? $opts['defaultMimefile'] : '');

        // bind events listeners
        if (!empty($opts['bind']) && is_array($opts['bind'])) {
            $_req = $_SERVER["REQUEST_METHOD"] == 'POST' ? $_POST : $_GET;
            $_reqCmd = isset($_req['cmd']) ? $_req['cmd'] : '';
            foreach ($opts['bind'] as $cmd => $handlers) {
                $doRegist = (strpos($cmd, '*') !== false);
                if (!$doRegist) {
                    $doRegist = ($_reqCmd && in_array($_reqCmd, array_map('self::getCmdOfBind', explode(' ', $cmd))));
                }
                if ($doRegist) {
                    // for backward compatibility
                    if (!is_array($handlers)) {
                        $handlers = [$handlers];
                    } else {
                        if (count($handlers) === 2 && is_object($handlers[0])) {
                            $handlers = [$handlers];
                        }
                    }
                    foreach ($handlers as $handler) {
                        if ($handler) {
                            if (is_string($handler) && strpos($handler, '.')) {
                                list($_domain, $_name, $_method) = array_pad(explode('.', $handler), 3, '');
                                if (strcasecmp($_domain, 'plugin') === 0) {
                                    if ($plugin = $this->getPluginInstance($_name, isset($opts['plugin'][$_name]) ? $opts['plugin'][$_name] : [])
                                            and method_exists($plugin, $_method)) {
                                        $this->bind($cmd, [$plugin, $_method]);
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
            $opts['roots'] = [];
        }

        // check for net volumes stored in session
        $netVolumes = $this->getNetVolumes();
        foreach ($netVolumes as $key => $root) {
            if (!isset($root['id'])) {
                // given fixed unique id
                if (!$root['id'] = $this->getNetVolumeUniqueId($netVolumes)) {
                    $this->mountErrors[] = 'Netmount Driver "'.$root['driver'].'" : Could\'t given volume id.';
                    continue;
                }
            }
            $opts['roots'][$key] = $root;
        }

        // "mount" volumes
        foreach ($opts['roots'] as $i => $o) {
            //$class = 'elFinderVolume'.(isset($o['driver']) ? $o['driver'] : '');
            // Chamilo change
            $class = (isset($o['driver']) ? $o['driver'] : '');

            if (class_exists($class)) {
                $volume = new $class();

                try {
                    if ($this->maxArcFilesSize && (empty($o['maxArcFilesSize']) || $this->maxArcFilesSize < $o['maxArcFilesSize'])) {
                        $o['maxArcFilesSize'] = $this->maxArcFilesSize;
                    }
                    // pass session handler
                    $volume->setSession($this->session);
                    if ($volume->mount($o)) {
                        // unique volume id (ends on "_") - used as prefix to files hash
                        $id = $volume->id();

                        $this->volumes[$id] = $volume;
                        if ((!$this->default || $volume->root() !== $volume->defaultPath()) && $volume->isReadable()) {
                            $this->default = $this->volumes[$id];
                        }
                    } else {
                        $this->removeNetVolume($i, $volume);
                        $this->mountErrors[] = 'Driver "'.$class.'" : '.implode(' ', $volume->error());
                    }
                } catch (Exception $e) {
                    $this->removeNetVolume($i, $volume);
                    $this->mountErrors[] = 'Driver "'.$class.'" : '.$e->getMessage();
                }
            } else {
                $this->mountErrors[] = 'Driver "'.$class.'" does not exist';
            }
        }

        // if at least one readable volume - ii desu >_<
        $this->loaded = !empty($this->default);

        // restore error handler for now
        restore_error_handler();
    }
}
