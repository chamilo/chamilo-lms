<?php
// Language files that should be included.
$language_file = array('courses', 'index');
require_once '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'course.lib.php';
//require_once $libpath.'usermanager.lib.php';
require_once $libpath.'sessionmanager.lib.php';
require_once $libpath.'usermanager.lib.php';
require_once $libpath.'formvalidator/FormValidator.class.php';
require_once $libpath.'text.lib.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathList.class.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.class.php';

api_block_anonymous_users(); // Only users who are logged in can proceed.


$this_section = SECTION_COURSES;

$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/cupertino/jquery-ui-1.8.7.custom.css" type="text/css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-1.4.4.min.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/cupertino/jquery-ui-1.8.7.custom.min.js" type="text/javascript" language="javascript"></script>';

$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqgrid/css/ui.jqgrid.css" type="text/css">';

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqgrid/js/i18n/grid.locale-en.js" type="text/javascript" language="javascript"></script>'; 
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqgrid/js/jquery.jqGrid.min.js" type="text/javascript" language="javascript"></script>';

    
Display :: display_header($nameTools);



$session_id     = intval($_GET['session_id']);
$session_info   = SessionManager::fetch($session_id);
$session_list   = SessionManager::get_sessions_by_coach(api_get_user_id());


$course_list    = SessionManager::get_course_list_by_session_id($session_id);
$course_select = array();

$session_select = array();
foreach ($session_list as $item) {
    $session_select[$item['id']] =  $item['name'];
}
/*
foreach ($course_list as $course_item) {
	$course_select[$course_item['id']] =  $course_item['title'];
}*/
// Session list form

if (count($session_select) > 1) {
    $form = new FormValidator('exercise_admin', 'get', api_get_self().'?session_id='.$session_id);
    $form->addElement('select', 'session_id', get_lang('SessionList'), $session_select, 'onchange="javascript:change_session()"');
    $defaults['session_id'] = $session_id;
    $form->setDefaults($defaults);
    $form->display();
    
    
    if ($form->validate()) {
        
    }
}

echo Display::tag('h1', $session_info['name']);


//Listing LPs from all courses
$lps = array();

foreach ($course_list as $item) {    
    $list       = new LearnpathList(api_get_user_id(),$item['code']);
    $flat_list  = $list->get_flat_list();        
    $lps[$item['code']] = $flat_list;
    foreach ($flat_list as $item) {        
        //var_dump(get_week_from_day($item['publicated_on']));	
    }    
}


//Getting all sessions where I'm subscribed 

$new_session_list = UserManager::get_personal_session_course_list(api_get_user_id());

echo '<pre>';
$my_session_list = array();
foreach($new_session_list as $item) {
    if (isset($item['id_session'])) {    	
        $my_course_list = UserManager::get_courses_list_by_session(api_get_user_id(),$item['id_session'] );
        foreach ($my_course_list as $my_course) {            
        	$course_info = api_get_course_info($my_course['code']);            
            $exercise_list = get_all_exercises($course_info);             
            foreach($exercise_list as $exercise_item) {                
                $exercise = new Exercise($course_info['real_id']);
                $exercise->read($exercise_item['id']);
                $exercise->exercise;            	
            } 
        }        
        $my_session_list[$item['id_session']]['session_name']= $item['session_name'];
        $my_session_list[$item['id_session']]['courses']= $my_course_list; 
    }    
}
foreach ($my_session_list as $my_session_id => $data) {
	//$my_course_list = $data();
}
//print_r($my_session_list);

//Exercise list
/*
$exercise_grid_url            = api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_default&session_id='.$session_id;
$exercise_grid_columns        =array(get_lang('Session'), get_lang(''))
$exercise_grid_column_model
$exercise_grid_settings       =
*/

//Default grid settings
$url            = api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_default&session_id='.$session_id;
$columns        = array('Date','Course', 'LP');
$column_model   = array(array('name'=>'date',   'index'=>'date',   'width'=>'120', 'align'=>'right'),
                        array('name'=>'course', 'index'=>'course', 'width'=>'120', 'align'=>'right'),
                        array('name'=>'lp',     'index'=>'lp',     'width'=>'120', 'align'=>'right'));
                        
//Course grid settings
$url_course             = api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_by_course&session_id='.$session_id;
$extra_params_course['grouping'] = 'true';
$extra_params_course['groupingView'] = array('groupField'=>array('course'),
                                            'groupColumnShow'=>array('false'),
                                            'groupText' => array('<b>Course {0} - {1} Item(s)</b>'));
                              
//Week grid
$url_week             = api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_by_course&session_id='.$session_id;
$column_week = array('Week','Date','Course', 'LP');
$column_week_model =array(array('name'=>'week', 'index'=>'week',   'width'=>'120', 'align'=>'right'),       
                          array('name'=>'date', 'index'=>'date',   'width'=>'120', 'align'=>'right'),
                          array('name'=>'course', 'index'=>'course',   'width'=>'120', 'align'=>'right'),
                          array('name'=>'lp', 'index'=>'lp',   'width'=>'120','align'=>'right'));
$extra_params_week['grouping'] = 'true';
$extra_params_week['groupingView'] = array('groupField'=>array('week'),
                                            'groupColumnShow'=>array('false'),
                                            'groupText' => array('<b>Week {0} - {1} Item(s)</b>'));
?>
<br />
<script>
    function change_session() {
            document.exercise_admin.submit();
    }
        
    
$(function() {
    $( "#tabs" ).tabs();
    $( "#sub_tab" ).tabs();
        
     <?php 
     echo Display::grid_js('list_default',  $url,       $columns,$column_model);
     echo Display::grid_js('list_course',   $url_course,$columns,$column_model,$extra_params_course);
     echo Display::grid_js('list_week',     $url_week,  $column_week,$column_week_model, $extra_params_week);
     
     echo Display::grid_js('exercise_list', $exercise_grid_url,  $column_week,$column_week_model, $extra_params_week);  
     ?>
  
});
</script>

<?php 

$headers = array(get_lang('LearningPaths'), get_lang('MyQCM'), get_lang('MyResults'));
$sub_header = array(get_lang('AllLearningPaths'), get_lang('PerWeek'), get_lang('ByCourse'));
$tabs =  Display::tabs($sub_header, array(Display::grid_html('list_default'), Display::grid_html('list_week'), Display::grid_html('list_course')),'sub_tab');
echo Display::tabs($headers, array($tabs,'bbb','ccc'));

// Footer
Display :: display_footer();