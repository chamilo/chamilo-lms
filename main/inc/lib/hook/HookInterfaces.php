<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes
 * @package chamilo.library.hook
 */

/**
 * Interface HookEventInterface
 */
interface HookEventInterface
{
    /**
     * Attach an HookObserver
     * @link http://php.net/manual/en/splsubject.attach.php
     * @param \HookObserverInterface| $observer <p>
     * The <b>HookObserver</b> to attach.
     * </p>
     * @return void
     */
    public function attach(HookObserverInterface $observer);

    /**
     * Detach an HookObserver
     * @link http://php.net/manual/en/splsubject.detach.php
     * @param \HookObserverInterface| $observer <p>
     * The <b>HookObserver</b> to detach.
     * </p>
     * @return void
     */
    public function detach(HookObserverInterface $observer);

    /**
     * Return the singleton instance of Hook event.
     * @return HookEventInterface|null
     */
    public static function create();


    /**
     * Return an array containing all data needed by the hook observer to update
     * @return array
     */
    public function getEventData();

    /**
     * Set an array with data needed by hooks
     * @param array $data
     * @return $this
     */
    public function setEventData(array $data);

    /**
     * Return the event name refer to where hook is used
     * @return string
     */
    public function getEventName();


    /**
     * Clear all hookObservers without detach them
     * @return mixed
     */
    public function clearAttachments();


    /**
     * Load all hook observer already registered from Session or Database
     * @return $this
     */
    public function loadAttachments();

    /**
     * Detach all hook observers
     * @return $this
     */
    public function detachAll();

    /**
     * Hook Auto Loader. Search for Hook Observers from plugins
     * @param string $observerClass
     * @param string $path
     * @return int
     */
    public static function autoLoadHooks($observerClass, $path);
}

interface HookObserverInterface
{
    /**
     * Return the singleton instance of Hook observer.
     * @return HookEventInterface|null
     */
    public static function create();

    /**
     * Return the path from the class, needed to store location or autoload later.
     * @return string
     */
    public function getPath();

    /**
     * Return the plugin name where is the Hook Observer.
     * @return string
     */
    public function getPluginName();
}

interface HookManagementInterface
{
    /**
     * Insert hook into Database. Return insert id
     * @param string $eventName
     * @param string $observerClassName
     * @param int $type
     * @return int
     */
    public function insertHook($eventName, $observerClassName, $type);

    /**
     * Delete hook from Database. Return deleted rows number
     * @param string $eventName
     * @param string $observerClassName
     * @param int $type
     * @return int
     */
    public function deleteHook($eventName, $observerClassName, $type);

    /**
     * Update hook observer order by hook event
     * @param $eventName
     * @param $type
     * @param $newOrder
     * @return int
     */
    public function orderHook($eventName, $type, $newOrder);

    /**
     * Return a list an associative array where keys are the hook observer class name
     * @param $eventName
     * @return array
     */
    public function listHookObservers($eventName);


    /**
     * Check if hooks (event, observer, call) exist in Database, if not,
     * Will insert them into their respective table
     * @param string $eventName
     * @param string $observerClassName
     * @return int
     */
    public function insertHookIfNotExist($eventName = null, $observerClassName = null);


    /**
     * Return the hook call id identified by hook event, hook observer and type
     * @param string $eventName
     * @param string $observerClassName
     * @param int $type
     * @return mixed
     */
    public function getHookCallId($eventName, $observerClassName, $type);
}

/**
 * Interface HookPluginInterface
 * This interface should be implemented by plugins to implements Hook Observer
 */
interface HookPluginInterface
{
    /**
     * This method will call the Hook management insertHook to add Hook observer from this plugin
     * @return int
     */
    public function installHook();

    /**
     * This method will call the Hook management deleteHook to disable Hook observer from this plugin
     * @return int
     */
    public function uninstallHook();
}

/**
 * Interface HookCreateUserEventInterface
 */
interface HookCreateUserEventInterface extends HookEventInterface
{
    /**
     * Update all the observers
     * @param int $type
     * @return int
     */
    public function notifyCreateUser($type);
}

/**
 * Interface CreateUserHookInterface
 */
interface HookCreateUserObserverInterface extends HookObserverInterface
{
    /**
     * @param HookCreateUserEventInterface $hook
     * @return int
     */
    public function hookCreateUser(HookCreateUserEventInterface $hook);
}

/**
 * Interface HookUpdateUserEventInterface
 */
interface HookUpdateUserEventInterface extends HookEventInterface
{
    /**
     * Update all the observers
     * @param int $type
     * @return int
     */
    public function notifyUpdateUser($type);
}

/**
 * Interface UpdateUserHookInterface
 */
interface HookUpdateUserObserverInterface extends HookObserverInterface
{
    /**
     * @param HookUpdateUserEventInterface $hook
     * @return int
     */
    public function hookUpdateUser(HookUpdateUserEventInterface $hook);
}

/**
 * Interface HookAdminBlockEventInterface
 */
interface HookAdminBlockEventInterface extends HookEventInterface
{
    /**
     * @param int $type
     * @return int
     */
    public function notifyAdminBlock($type);
}

/**
 * Interface HookAdminBlockObserverInterface
 */
interface HookAdminBlockObserverInterface extends HookObserverInterface
{
    /**
     * @param HookAdminBlockEventInterface $hook
     * @return int
     */
    public function hookAdminBlock(HookAdminBlockEventInterface $hook);
}

/**
 * Interface HookWSRegistrationEventInterface
 */
interface HookWSRegistrationEventInterface extends HookEventInterface
{
    /**
     * @param int $type
     * @return int
     */
    public function notifyWSRegistration($type);
}

/**
 * Interface HookWSRegistrationObserverInterface
 */
interface HookWSRegistrationObserverInterface extends HookObserverInterface
{
    /**
     * @param HookWSRegistrationEventInterface $hook
     * @return int
     */
    public function hookWSRegistration(HookWSRegistrationEventInterface $hook);
}
