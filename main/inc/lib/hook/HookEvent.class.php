<?php
/* For licensing terms, see /license.txt */


abstract class HookEvent implements HookEventInterface
{
    public $observers;
    public $eventName;
    public $eventData;

    /**
     * Construct Method
     * @param $eventName
     * @throws Exception
     */
    protected function __construct($eventName)
    {
        if (self::isHookPluginActive()) {
            $this->observers = new SplObjectStorage();
            $this->eventName = $eventName;
            $this->eventData = array();
            $this->plugin = HookManagementPlugin::create();
            $this->loadAttachments();
        } else {
            throw new \Exception('Hook Management Plugin is not active');
        }
    }

    /**
     * Return the singleton instance of Hook event.
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
     * Attach an HookObserver
     * @link http://php.net/manual/en/splsubject.attach.php
     * @param \HookObserverInterface| $observer <p>
     * The <b>HookObserver</b> to attach.
     * </p>
     * @return void
     */
    public function attach(HookObserverInterface $observer)
    {
        global $_hook;
        $observerClass = get_class($observer);
        $_hook[$this->eventName][$observerClass] = array(
            'class_name' => $observerClass,
            'path' => $observer->getPath(),
            'plugin_name' => $observer->getPluginName(),
        );
        $this->observers->attach($observer);
        $this->plugin->insertHook($this->eventName, $observerClass, HOOK_TYPE_ALL);
    }

    /**
     * Detach an HookObserver
     * @link http://php.net/manual/en/splsubject.detach.php
     * @param \HookObserverInterface| $observer <p>
     * The <b>HookObserver</b> to detach.
     * </p>
     * @return void
     */
    public function detach(HookObserverInterface $observer)
    {
        global $_hook;
        $observerClass = get_class($observer);
        unset($_hook[$this->eventName][$observerClass]);
        $this->observers->detach($observer);
        $this->plugin->deleteHook($this->eventName, $observerClass, HOOK_TYPE_ALL);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Notify an observer
     * @link http://php.net/manual/en/splsubject.notify.php
     * @return void
     */
    public function notify()
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }

    /**
     * Return the event name refer to where hook is used
     * @return string
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * Return an array containing all data needed by the hook observer to update
     * @return array
     */
    public function getEventData()
    {
        return $this->eventData;
    }

    /**
     * Set an array with data needed by hooks
     * @param array $data
     * @return $this
     */
    public function setEventData(array $data)
    {
        $this->eventData = $data;
        return $this;
    }

    /**
     * Load all hook observer already registered from Session or Database
     * @return $this
     */
    public function loadAttachments()
    {
        global $_hook;
        if (isset($_hook[$this->eventName]) && is_array($_hook[$this->eventName])) {
            foreach ($_hook[$this->eventName] as $hookObserver => $val) {
                self::autoLoadHooks($hookObserver, $val['path']);
                $hookObserverInstance = $hookObserver::create();
                $this->observers->attach($hookObserverInstance);
            }
        } else {
            // Load from Database and save into global name
            $_hook[$this->eventName] = $this->plugin->listHookObservers($this->eventName);
            if (isset($_hook[$this->eventName]) && is_array($_hook[$this->eventName])) {
                foreach ($_hook[$this->eventName] as $hookObserver => $val) {
                    self::autoLoadHooks($hookObserver, $val['path']);
                    $hookObserverInstance = $hookObserver::create();
                    $this->observers->attach($hookObserverInstance);
                }
            }
        }
    }

    /**
     * Detach all hook observers
     * @return $this
     */
    public function detachAll()
    {
        global $_hook;
        $_hook[$this->eventName] = null;
        $this->observers->removeAll($this->observers);
    }

    /**
     * Clear all hookObservers without detach them
     * @return mixed
     */
    public function clearAttachments()
    {
        $this->observers->removeAll($this->observers);
    }

    /**
     * Return true if HookManagement plugin is active. Else, false.
     * This is needed to check if hook event can be instantiated
     * @return boolean
     */
    public static function isHookPluginActive()
    {
        $isActive = false;
        $appPlugin = new AppPlugin();
        $pluginList = $appPlugin->getInstalledPluginListName();
        if (in_array(HOOK_MANAGEMENT_PLUGIN, $pluginList)) {
            $isActive = true;
        }

        return $isActive;
    }

    /**
     * Hook Auto Loader. Search for Hook Observers from plugins
     * @param string $observerClass
     * @param string $path
     * @return int
     */
    public static function autoLoadHooks($observerClass, $path)
    {
        Autoload::$map[$observerClass] = $path;
    }
}