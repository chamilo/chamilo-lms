<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains an abstract Hook event class
 * Used for Hook Events (e.g Create user, Webservice registration).
 *
 * @package chamilo.library.hook
 */

/**
 * Class HookEvent
 * This abstract class implements Hook Event Interface to build the base
 * for Hook Events. This class have some public static method,
 * e.g for create Hook Events.
 */
abstract class HookEvent implements HookEventInterface
{
    public $observers;
    public $eventName;
    public $eventData;
    public $manager;
    public static $hook;

    /**
     * Construct Method.
     *
     * @param string $eventName
     *
     * @throws Exception
     */
    protected function __construct($eventName)
    {
        $this->observers = new SplObjectStorage();
        $this->eventName = $eventName;
        $this->eventData = [];
        $this->manager = HookManagement::create();
        $this->loadAttachments();
    }

    /**
     * Return the singleton instance of Hook event.
     *
     * @return static
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
     * Attach an HookObserver.
     *
     * @see http://php.net/manual/en/splsubject.attach.php
     *
     * @param \HookObserverInterface| $observer <p>
     *                                          The <b>HookObserver</b> to attach.
     *                                          </p>
     */
    public function attach(HookObserverInterface $observer)
    {
        $observerClass = get_class($observer);
        self::$hook[$this->eventName][$observerClass] = [
            'class_name' => $observerClass,
            'path' => $observer->getPath(),
            'plugin_name' => $observer->getPluginName(),
        ];
        $this->observers->attach($observer);
        $this->manager->insertHook($this->eventName, $observerClass, HOOK_EVENT_TYPE_ALL);
    }

    /**
     * Detach an HookObserver.
     *
     * @see http://php.net/manual/en/splsubject.detach.php
     *
     * @param \HookObserverInterface| $observer <p>
     *                                          The <b>HookObserver</b> to detach.
     *                                          </p>
     */
    public function detach(HookObserverInterface $observer)
    {
        $observerClass = get_class($observer);
        unset(self::$hook[$this->eventName][$observerClass]);
        $this->observers->detach($observer);
        $this->manager->deleteHook($this->eventName, $observerClass, HOOK_EVENT_TYPE_ALL);
    }

    /**
     * Notify an observer.
     *
     * @see http://php.net/manual/en/splsubject.notify.php
     */
    public function notify()
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }

    /**
     * Return the event name refer to where hook is used.
     *
     * @return string
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * Return an array containing all data needed by the hook observer to update.
     *
     * @return array
     */
    public function getEventData()
    {
        return $this->eventData;
    }

    /**
     * Set an array with data needed by hooks.
     *
     * @return $this
     */
    public function setEventData(array $data)
    {
        foreach ($data as $key => $value) {
            // Assign value for each array item
            $this->eventData[$key] = $value;
        }

        return $this;
    }

    /**
     * Detach all hook observers.
     *
     * @return $this
     */
    public function detachAll()
    {
        self::$hook[$this->eventName] = null;
        $this->observers->removeAll($this->observers);
    }

    /**
     * Clear all hookObservers without detach them.
     *
     * @return mixed
     */
    public function clearAttachments()
    {
        $this->observers->removeAll($this->observers);
    }

    /**
     * Load all hook observer already registered from Session or Database.
     *
     * @return $this
     */
    public function loadAttachments()
    {
        if (isset(self::$hook[$this->eventName]) && is_array(self::$hook[$this->eventName])) {
            foreach (self::$hook[$this->eventName] as $hookObserver => $val) {
                $hookObserverInstance = $hookObserver::create();
                $this->observers->attach($hookObserverInstance);
            }
        } else {
            // Load from Database and save into global name
            self::$hook[$this->eventName] = $this->manager->listHookObservers($this->eventName);
            if (isset(self::$hook[$this->eventName]) && is_array(self::$hook[$this->eventName])) {
                foreach (self::$hook[$this->eventName] as $hookObserver => $val) {
                    $hookObserverInstance = $hookObserver::create();
                    $this->observers->attach($hookObserverInstance);
                }
            }
        }

        return $this;
    }
}
