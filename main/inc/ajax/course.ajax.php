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
		
		$course_id = intval($_REQUEST['course_id']);
        $star      = intval($_REQUEST['star']);
		
        if (!api_is_anonymous()) {     	    
            CourseManager::add_course_vote($user_id, $star, $course_id, 0);                
        }		
		$point_info = CourseManager::get_course_ranking($course_id, 0); 		
		$ajax_url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=add_course_vote';		
	    $rating = Display::return_rating_system('star_'.$course_id, $ajax_url.'&amp;course_id='.$course_id, $point_info, false);
		echo $rating;
		
        break;
    case  'get_user_courses':
        if (api_is_platform_admin()) {
            $user_id = intval($_POST['user_id']);            
            $list_course_all_info = CourseManager::get_courses_list_by_user_id($user_id, false);
            if (!empty($list_course_all_info)) {
                foreach($list_course_all_info as $course_item) {                
                    $course_info = api_get_course_info($course_item['code']);                    
                    echo $course_info['title'].'<br />';
                }
            } else {            
                echo get_lang('HaveNoCourse');
            } 
        }
        break;
    default:
        echo '';
}
exit;