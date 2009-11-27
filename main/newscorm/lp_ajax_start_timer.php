<?php //$id$
/**
 * This script contains the server part of the xajax interaction process. The client part is located
 * in lp_api.php or other api's.
 * This is a first attempt at using xajax and AJAX in general, so the code might be a bit unsettling.
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Start a timer and hand it back to the JS by assigning the current time (of start) to
 * var asset_timer
 */
function start_timer()
{
    //$objResponse = new xajaxResponse();
    $time = time();
    //$objResponse->addScript("asset_timer='$time';asset_timer_total=0;");
    //return $objResponse;
    return "asset_timer='$time';asset_timer_total=0;";
}
echo start_timer();