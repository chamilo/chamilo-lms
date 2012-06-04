<?php
include_once api_get_path(CONFIGURATION_PATH).'events.conf.php';

/**
 * 
 * Entry point for every event in the application. 
 * Fires the functions linked to the events according to the event's conf.
 * Every function got its own filter, it's fired inside the functiones fired
 * by this class. The filter config is next to the event config, in conf/events.conf.php
 *  
 */
class EventsDispatcher 
{
    public static function events($event_name, $event_data)
    {
        global $event_config;
        // get the config for the event passed in parameter ($event_name)
        // and execute every actions with the values
        foreach ($event_config[$event_name]["actions"] as $func) 
        {
            if (!function_exists($func)) // if the function doesn't exist, we log
            {
                error_log("EventsDispatcher warning : ".$func." does not exist.");
            }
            
            if (function_exists($event_name."_".$func."_filter_func")) // check if the event's got a filter
            {
                $filter = $event_name."_".$func."_filter_func";
                $execute = $filter($event_data); // if it does, we execute the filter
            }
            else // if there's no filter
            {
                error_log("EventsDispatcher warning : ".$event_name."_".$func."_filter_func does not exist.");
            }

            if (!$execute) // if the filter says we cannot send the mail, we get out of here
            {
                return false;
            }
            // finally, if the filter says yes, we execute the in-between function that will call the needed function
            $func($event_name, $event_data);
        }
    }
}

?>
