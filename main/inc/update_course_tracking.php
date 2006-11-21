<?php
$noajax=true;
include("global.inc.php");
require('common_course_tracking.ajax.php');

function updateCourseTracking($i_user_id){
	
	$objResponse = new xajaxResponse();
	
	
	
	$course_tracking_table = Database :: get_statistic_table(STATISTIC_TRACK_E_COURSE_ACCESS_TABLE);
	
	//We select the last record for the current course in the course tracking table
	$sql="SELECT course_access_id FROM $course_tracking_table WHERE user_id='$i_user_id' ORDER BY login_course_date DESC LIMIT 0,1";
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$i_course_access_id = mysql_result($result,0,0);
	
	//We update the course tracking table
	$sql="UPDATE $course_tracking_table " .
			"SET logout_course_date = NOW() " .
			"WHERE course_access_id='$i_course_access_id'";

	api_sql_query($sql,__FILE__,__LINE__);
	
	return $objResponse;
	
}

$xajax_course_tracking->processRequests();

?>
