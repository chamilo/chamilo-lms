<?php
/**
* In this page all the possible rights inside a course are defined.
* This is the start for the Matrix that you'll use to assign rights to
* a user, group or role
* @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
*/

// possible permissions
$rights_full=array("View","Add","Edit","Delete","Visibility","Move");
$rights_limited=array("Add","Edit","Delete");


// first we determine what can be done in each tool. We do this for each tool.
$tool_rights[TOOL_LINK]=array("View","Add","Edit","Delete","Visibility", "Move");
$tool_rights[TOOL_DOCUMENT]=array("View","Add","Edit","Delete","Visibility","Move");
//$tool_rights[TOOL_CALENDAR_EVENT]=array("View","Add","Edit","Delete","Visibility");
$tool_rights[TOOL_ANNOUNCEMENT]=array("View","Add","Edit","Delete","Visibility", "Move");
//$tool_rights[TOOL_STUDENTPUBLICATION]=array("View","Edit","Delete","Visibility");
//$tool_rights[TOOL_COURSE_DESCRIPTION]=array("View","Add","Edit","Delete","Visibility");
//$tool_rights[TOOL_LEARNPATH]=array("View","Add","Edit","Delete","Visibility");
//$tool_rights[TOOL_BB_FORUM]=array("View","Add","Edit","Delete");
//$tool_rights[TOOL_BB_POST]=array("View","Add","Edit","Delete");
//$tool_rights[TOOL_DROPBOX]=array("View","Add","Delete");
//$tool_rights[TOOL_QUIZ]=array("View","Add","Edit","Delete","Visibility");
$tool_rights[TOOL_USER]=array("View","Add","Edit","Delete");
//$tool_rights[TOOL_GROUP]=array("View","Add","Edit","Delete");
//$tool_rights[TOOL_CHAT]=array("View","Delete");
//$tool_rights[TOOL_CONFERENCE]=array("View","Add","Edit","Delete");
//$tool_rights[TOOL_STUDENTPUBLICATION]=array("View","Add","Edit","Delete");

// this value can be checkbox or image
$setting_visualisation='image';

?>