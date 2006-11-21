<?php

//Include the librarie ajax
require_once(api_get_path(LIBRARY_PATH)."xajax/xajax.inc.php");

$xajax_course_tracking = new xajax(api_get_path(REL_PATH)."main/inc/update_course_tracking.php");

$xajax_course_tracking->registerFunction("updateCourseTracking");

?>
