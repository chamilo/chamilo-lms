<?php
/* For licensing terms, see /license.txt */
/**
 * Reporting page on the user's own progress
 * @package chamilo.tracking
 */
/**
 * Code
 */

// name of the language file that needs to be included
$language_file = array('registration', 'tracking', 'exercice', 'admin', 'learnpath');

$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';
      
$this_section = SECTION_TRACKING;

$nameTools = get_lang('MyProgress');

api_block_anonymous_users();

$htmlHeadXtra[] = api_get_js('jquery.timelinr-0.9.5.js');

$htmlHeadXtra[] = '
<script language="javascript">
$(function() {
    
    $().timelinr();

    $(".dialog").dialog("destroy");        
    $(".dialog").dialog({
            autoOpen: false,
            show: "blind",                
            resizable: false,
            height:300,
            width:550,
            modal: true
     });     

    $(".opener").click(function() {
        var my_id = $(this).attr(\'id\'); 
        var big_image = \'#main_graph_\' + my_id;
        $( big_image ).dialog("open");
        return false;
    });
});
</script>';



require_once api_get_path(SYS_CODE_PATH).'mySpace/myspace.lib.php';



$user_id = api_get_user_id();

// Code to 
$course_user_list = CourseManager::get_courses_list_by_user_id($user_id);
$dates = $issues = '';
foreach($course_user_list as $course) {    
    $items = MySpace::get_connections_to_course($user_id, $course['code']);
    foreach($items as $result) {
        $login = $result['login']; 
        $course_info = api_get_course_info($course['code']);
        $course_image = '<img src="'.$course_info['course_image'].'">';
        $dates .= '<li><a href="#'.$login.'">'.  api_get_utc_datetime($login).'</a></li>';
        $issues .= '<li id ="'.$login.'"><div class="row"><div class="span1">'.$course_image.'</div>
                <div class="span3">Has ingresado al curso <b>'.$course['code'].'</b> el
            '.  api_convert_and_format_date($login, DATE_FORMAT_LONG).'</div></li>';
    }    
}

$content .= Tracking::show_user_progress(api_get_user_id());
if (!empty($content)) {
    $content .= '<br /><br />';
}
$content .= '<div class="span12"><h2>'.get_lang('Timeline').'</h2></span>';

$content .= '<div id="timeline">
   <ul id="dates">
    '.$dates.'  
    </ul>
   <ul id="issues">
      '.$issues.'
   </ul>
   <a href="#" id="next">+</a> <!-- optional -->
   <a href="#" id="prev">-</a> <!-- optional -->
   </div>';

$content .= Tracking::show_course_detail(api_get_user_id(), $_GET['course'], $_GET['session_id']);

if (empty($content)) {
    $message = Display::return_message(get_lang('NoDataAvailable'), 'warning');
}



$tpl = new Template($tool_name);

//$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
