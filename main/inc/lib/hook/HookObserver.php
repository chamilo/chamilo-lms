<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains an abstract Hook observer class
 * Used for Hook Observers in plugins, called when a hook event happens
 * (e.g Create user, Webservice registration).
 *
 * @package chamilo.library.hook
 */

/**
 * Class HookObserver
 * This abstract class implements Hook Observer Interface to build the base
 * for Hook Observer. This class have some public static method,
 * e.g for create Hook Observers.
 */
abstract class HookObserver implements HookObserverInterface
{
    public $path;
    public $pluginName;

    /**
     * Construct method
     * Save the path of Hook Observer class implementation and
     * the plugin name where this class is included.
     *
     * @param string $path
     * @param string $pluginName
     */
    protected function __construct($path, $pluginName)
    {
        $this->path = $path;
        $this->pluginName = $pluginName;
    }

    /**
     * Return the singleton instance of Hook observer.
     * If Hook Management plugin is not enabled, will return NULL.
     *
     * @return HookObserver
     */
    public static function create()
    {
        static $result = null;

        if ($result) {
            return $result;
        } else {
            try {
                $class = get_called_class();

                return new $class();
            } catch (Exception $e) {
                return null;
            }
        }
    }

    /**
     * Return the path from the class, needed to store location or autoload later.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Return the plugin name where is the Hook Observer.
     *
     * @return string
     */
    public function getPluginName()
    {
        return $this->pluginName;
    }
}
