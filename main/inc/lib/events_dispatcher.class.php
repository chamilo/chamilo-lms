<?php

/**
 *  Class EventsDispatcher
 * Entry point for every event in the application.
 *
 * @deprecated to be removed in 2.x
 * Fires the functions linked to the events according to the event's conf.
 * Every function got its own filter, it's fired inside the functiones fired
 * by this class. The filter config is next to the event config, in conf/events.conf.php
 */
class EventsDispatcher
{
    /**
     * @param string $event_name
     * @param array  $event_data
     *
     * @return bool
     */
    public static function events($event_name, $event_data = [])
    {
        global $event_config;
        // get the config for the event passed in parameter ($event_name)
        // and execute every actions with the values

        foreach ($event_config[$event_name]["actions"] as $func) {
            $execute = true;
            // if the function doesn't exist, we log
            if (!function_exists($func)) {
                error_log("EventsDispatcher warning : ".$func." does not exist.");
                $execute = false;
            }

            // check if the event's got a filter
            if (function_exists($event_name."_".$func."_filter_func")) {
                $filter = $event_name."_".$func."_filter_func";
                // if it does, we execute the filter (which changes the data
                // in-place and returns true on success or false on error)
                $execute = $filter($event_data);
            } else {
                // if there's no filter
                error_log("EventsDispatcher warning : ".$event_name."_".$func."_filter_func does not exist.");
            }

            if (!$execute) {
                // if the filter says we cannot send the mail, we get out of here
                return false;
            }
            // finally, if the filter says yes (or the filter doesn't exist),
            // we execute the in-between function that will call the needed
            // function
            $func($event_name, $event_data);
        }
    }
}
