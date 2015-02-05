<?php
/* For licensing terms, see /license.txt */


abstract class HookObserver implements HookObserverInterface
{
    public $path;
    public $pluginName;

    /**
     * Construct method
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
     * If Hook Management plugin is not enabled, will return NULL
     * @return HookEventInterface|null
     */
    public static function create()
    {
        static $result = null;

        if ($result) {
            return $result;
        } else {
            try {
                $class = get_called_class();
                return new $class;
            } catch (Exception $e) {
                return null;
            }
        }
    }

    /**
     * Return the path from the class, needed to store location or autoload later.
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Return the plugin name where is the Hook Observer.
     * @return string
     */
    public function getPluginName()
    {
        return $this->pluginName;
    }
}