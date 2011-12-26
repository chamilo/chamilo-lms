<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';

$action = $_REQUEST['a'];

$user_id = api_get_user_id();

switch ($action) {    
    case 'add_course_vote':
        if (!api_is_anonymous()) {
     	    $course_id = intval($_REQUEST['course_id']);
            $star      = intval($_REQUEST['star']);
            $result    = CourseManager::add_course_vote($user_id, $star, $course_id, 0);
            echo $result;            
        }
        break;
    default:
        echo '';
}
exit;