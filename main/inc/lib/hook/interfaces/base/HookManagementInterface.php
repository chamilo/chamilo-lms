<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 *
 * @package chamilo.library.hook
 */
interface HookManagementInterface
{
    /**
     * Insert hook into Database. Return insert id.
     *
     * @param string $eventName
     * @param string $observerClassName
     * @param int    $type
     *
     * @return int
     */
    public function insertHook($eventName, $observerClassName, $type);

    /**
     * Delete hook from Database. Return deleted rows number.
     *
     * @param string $eventName
     * @param string $observerClassName
     * @param int    $type
     *
     * @return int
     */
    public function deleteHook($eventName, $observerClassName, $type);

    /**
     * Update hook observer order by hook event.
     *
     * @param $eventName
     * @param $type
     * @param $newOrder
     *
     * @return int
     */
    public function orderHook($eventName, $type, $newOrder);

    /**
     * Return a list an associative array where keys are the hook observer class name.
     *
     * @param $eventName
     *
     * @return array
     */
    public function listHookObservers($eventName);

    /**
     * Check if hooks (event, observer, call) exist in Database, if not,
     * Will insert them into their respective table.
     *
     * @param string $eventName
     * @param string $observerClassName
     *
     * @return int
     */
    public function insertHookIfNotExist($eventName = null, $observerClassName = null);

    /**
     * Return the hook call id identified by hook event, hook observer and type.
     *
     * @param string $eventName
     * @param string $observerClassName
     * @param int    $type
     *
     * @return mixed
     */
    public function getHookCallId($eventName, $observerClassName, $type);
}
