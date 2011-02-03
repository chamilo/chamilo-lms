<?php
/* For licensing terms, see /license.txt */
/**
*   Session view
*   @package chamilo.session
*   @author Julio Montoya <gugli100@gmail.com>
*/

// Language files that should be included.
$language_file = array('courses', 'index','tracking','exercice', 'admin');
$cidReset = true;
require_once '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'course.lib.php';
//require_once $libpath.'usermanager.lib.php';
require_once $libpath.'sessionmanager.lib.php';
require_once $libpath.'usermanager.lib.php';
require_once $libpath.'formvalidator/FormValidator.class.php';
require_once $libpath.'text.lib.php';
require_once $libpath.'tracking.lib.php';

require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathList.class.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.class.php';

api_block_anonymous_users(); // Only users who are logged in can proceed.

$this_section = SECTION_COURSES;
$htmlHeadXtra[] = api_get_jquery_ui_js(true);
    
Display :: display_header($nameTools);

$session_id     = intval($_GET['session_id']);
if (empty($session_id)) {
	api_not_allowed();
}

$session_info   = SessionManager::fetch($session_id);
$session_list   = SessionManager::get_sessions_by_coach(api_get_user_id());
$course_list    = SessionManager::get_course_list_by_session_id($session_id);

$course_select = array();

$session_select = array();
foreach ($session_list as $item) {
    $session_select[$item['id']] =  $item['name'];
}

// Session list form

if (count($session_select) > 1) {
    $form = new FormValidator('exercise_admin', 'get', api_get_self().'?session_id='.$session_id);
    $form->addElement('select', 'session_id', get_lang('SessionList'), $session_select, 'onchange="javascript:change_session()"');
    $defaults['session_id'] = $session_id;
    $form->setDefaults($defaults);
    $form->display();
    //if ($form->validate()) {}        
}

//Listing LPs from all courses
/*
$lps = array();
if (!empty($course_list)) {
    foreach ($course_list as $item) {    
        $list       = new LearnpathList(api_get_user_id(),$item['code']);
        $flat_list  = $list->get_flat_list();        
        $lps[$item['code']] = $flat_list;
        foreach ($flat_list as $item) {        
            //var_dump(get_week_from_day($item['publicated_on']));	
        }    
    }
}*/

//Getting all sessions where I'm subscribed
$new_session_list = UserManager::get_personal_session_course_list(api_get_user_id());

$my_session_list = array();
$final_array = array();

if (!empty($new_session_list)) {
    foreach($new_session_list as $item) {
        $my_session_id = $item['id_session'];    
        if (isset($my_session_id) && !in_array($my_session_id, $my_session_list) && $session_id == $my_session_id) {
        	$final_array[$my_session_id]['name'] = $item['session_name'];
            
            //Get all courses by session where I'm subscribed
            $my_course_list = UserManager::get_courses_list_by_session(api_get_user_id(), $my_session_id );            
            $course = array();        
            foreach ($my_course_list as $my_course) {
            
                $course_info   = api_get_course_info($my_course['code']);
                //Getting all exercises from the current course            
                $exercise_list = get_all_exercises($course_info, $my_session_id);
                           
                //Exercises we skip
                /*if (empty($exercise_list)) {
                    continue;
                } */   
                $exercise_course_list = array();
                $course['name'] = $course_info['name'];
                if (!empty($exercise_list)) {        
                    foreach($exercise_list as $exercise_item) {                
                        $exercise = new Exercise($course_info['real_id']);
                        $exercise->read($exercise_item['id']);                                    
                        $exercise_course_list[$exercise_item['id']] = $exercise;
                        //Reading all Exercise results by user, exercise_id, code, and session
                        $user_results = get_all_exercise_results_by_user(api_get_user_id(), $exercise_item['id'], $my_course['code'], $my_session_id);
                        //print_r($user_results);
                        
                        $course['exercises'][$exercise_item['id']]['name'] =  $exercise->exercise;                            
                        $course['exercises'][$exercise_item['id']]['data'] =  $user_results;      
                        //print_r($user_results);          
                    }
                    $final_array[$my_session_id]['data'][$my_course['code']] = $course;        
                }   
            }            
        }
        $my_session_list[] =  $my_session_id;      
    }
}

//If the requested session does not exist in my list we stop the script
if (!api_is_platform_admin()) {    
    if (!in_array($session_id, $my_session_list)) {
        api_not_allowed();
    }
}

//print_r($my_session_list); exit;
require_once api_get_path(LIBRARY_PATH).'pear/HTML/Table.php';
$html = '';
//Final data to be show
$my_real_array =array();

foreach($final_array as $session_data) {
    //Session name    
	$html .=Display::tag('h1',$session_data['name']);
    $course_list = $session_data['data'];    
    if (!empty($course_list))     
    foreach ($course_list as $my_course_code=>$course_data) {
        //Course table        
        $table = new HTML_Table(array('class' => 'data_table'));
        $row = 0;
        $column = 0;
        
        //Course headers
        /*$header_names = array(get_lang('Course'),get_lang('Exercise'),get_lang('Attempt'),get_lang('Results'),get_lang('Score'), get_lang('Ranking'));
        foreach ($header_names as $item) {
            $table->setHeaderContents($row, $column, $item);
            $column++;
        }        
        $row = 1;
        $column = 0;
        $table->setCellContents($row, $column, $course_data['name']);
        $column++;*/           
        if (!empty($course_data['exercises'])) {
            //Exercises            
            foreach ($course_data['exercises'] as $my_exercise_id => $exercise_data) {
                //Exercise results                              
                $counter = 1;                    
                foreach ($exercise_data['data'] as $exercise_result) {                    
                    $my_exercise_result = array($exercise_data['name'], $exercise_result['exe_id']);
                    $column = 1;      
                    $score          = $exercise_result['exe_result'].' / '.$exercise_result['exe_weighting'];
                    $platform_score = show_score($exercise_result['exe_result'], $exercise_result['exe_weighting'], false);
                    if (!empty($exercise_result['exe_weighting']) && intval($exercise_result['exe_weighting']) != 0 ) {                        
                        $my_score = $exercise_result['exe_result']/$exercise_result['exe_weighting'];
                    } else {
                    	$my_score = 0;
                    }
                    $position       = get_exercise_result_ranking($my_score, $exercise_result['exe_id'], $my_exercise_id,  $my_course_code,$session_id);
                    $my_real_array[]= array('course'=>$course_data['name'], 'exercise'=>$exercise_data['name'],'attempt'=>$counter,'result'=>$score,'note'=>$platform_score,'position'=>$position);
                    $counter++;   
                    foreach ($my_exercise_result as $data) {                            
                        //$my_real_array[]= array('session'=>$session_data['name'],'course'=>$course_data['name'], 'exercise'=>$exercise_data['name'],'result'=>$exercise_result['exe_id'])  ;                                                     
                        $table->setCellContents($row, $column, $data);                        
                        //$table->updateCellAttributes($row, $column, 'align="center"');
                        $column++;        
                    }
                    $row++;
                }               
            }
        }
        $html .=$table->toHtml();
    }
}     
echo Display::tag('h1', $session_info['name']);

//All Learnpaths grid settings (First tab, first subtab)

$url            = api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_default&session_id='.$session_id;
$columns        = array(get_lang('Date'),get_lang('Course'), get_lang('LearningPath'));

$column_model   = array(array('name'=>'date',   'index'=>'date',   'width'=>'80',  'align'=>'left',  'sortable'=>'false'),
                        array('name'=>'course', 'index'=>'course', 'width'=>'500', 'align'=>'left',  'sortable'=>'false'),
                        array('name'=>'lp',     'index'=>'lp',     'width'=>'200', 'align'=>'center','sortable'=>'false'));
                        
$extra_params['autowidth'] = 'true'; //use the width of the parent
//$extra_params['forceFit'] = 'true'; //use the width of the parent
//$extra_params['altRows'] = 'true'; //zebra style
                        
//Per course grid settings
$url_course             = api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_by_course&session_id='.$session_id;
$extra_params_course['grouping'] = 'true';
$extra_params_course['groupingView'] = array('groupField'       => array('course'),
                                             'groupColumnShow'  => array('false'),
                                             'groupText'        => array('<b>'.get_lang('Course').' {0} - {1} Item(s)</b>'));
$extra_params_course['autowidth'] = 'true'; //use the width of the parent                                          
                              
//Per Week grid
$url_week           = api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_by_week&session_id='.$session_id;
$column_week        = array(get_lang('PeriodWeek'), get_lang('Date'),get_lang('Course'), get_lang('LearningPath'));
$column_week_model  = array(
                          array('name'=>'week',     'index'=>'week',    'width'=>'80', 'align'=>'left', 'sortable'=>'false'),       
                          array('name'=>'date',     'index'=>'date',    'width'=>'80', 'align'=>'right','sortable'=>'false'),
                          array('name'=>'course',   'index'=>'course',  'width'=>'500','align'=>'left', 'sortable'=>'false'),
                          array('name'=>'lp',       'index'=>'lp',      'width'=>'200','align'=>'center','sortable'=>'false'));
                          
                          
$extra_params_week['grouping'] = 'true';
$extra_params_week['groupingView'] = array('groupField'=>array('week'),
                                            'groupColumnShow'=>'false',
                                            'groupText' => array('<b>'.get_lang('PeriodWeek').' {0} - {1} </b>'));
$extra_params_week['autowidth'] = 'true'; //use the width of the parent

//MyQCM grid
$column_exercise        = array(get_lang('Course'),get_lang('Exercise'), get_lang('Attempts'), get_lang('Result'), get_lang('Score'), get_lang('Position'));
$column_exercise_model  = array(array('name'=>'course',     'index'=>'course',    'width'=>'450', 'align'=>'left',  'sortable'=>'false'),
                                array('name'=>'exercise',   'index'=>'exercise',  'width'=>'250', 'align'=>'left',  'sortable'=>'false'),
                                array('name'=>'attempt',    'index'=>'attempt',   'width'=>'50', 'align'=>'center', 'sortable'=>'false'),
                                array('name'=>'result',     'index'=>'result',    'width'=>'50', 'align'=>'center', 'sortable'=>'false'),
                                array('name'=>'note',       'index'=>'note',      'width'=>'50', 'align'=>'center', 'sortable'=>'false'),
                                array('name'=>'position',   'index'=>'position',  'width'=>'50', 'align'=>'center', 'sortable'=>'false')
                                );                        
$extra_params_exercise['grouping'] = 'true';
$extra_params_exercise['groupingView'] = array('groupField'=>array('course'),'groupColumnShow'=>'false','groupText' => array('<b>Course {0} - {1} Item(s)</b>'));
//$extra_params_exercise['altRows'] = 'true';
                                          
?>
<br />
<script>
function change_session() {
    document.exercise_admin.submit();
}        
    
$(function() {    
    /*Binds a tab id in the url */
    $("#tabs").bind('tabsselect', function(event, ui) {
            window.location.href=ui.tab;
    });
    $('#tabs').tabs();
    $( "#sub_tab" ).tabs();     
         
<?php 
     echo Display::grid_js('list_default',  $url,           $columns,        $column_model,$extra_params);
     echo Display::grid_js('list_course',   $url_course,    $columns,        $column_model,$extra_params_course);
     echo Display::grid_js('list_week',     $url_week,      $column_week,    $column_week_model, $extra_params_week);     
     echo Display::grid_js('exercises',      '',            $column_exercise,$column_exercise_model, $extra_params_exercise, $my_real_array);        
?>

});
</script>

<?php 
$my_reporting   = Tracking::show_user_progress(api_get_user_id(), $session_id, '&session_id='.$session_id.'#tabs-3', false);
$my_reporting   .= '<br />'.Tracking::show_course_detail(api_get_user_id(), $_GET['course'], $_GET['session_id']);

//Main headers
$headers        = array(get_lang('LearningPaths'), get_lang('MyQCM'), get_lang('MyResults'));
//Subheaders
$sub_header     = array(get_lang('AllLearningPaths'), get_lang('PerWeek'), get_lang('ByCourse'));

//Sub header tab
$tabs           =  Display::tabs($sub_header, array(Display::grid_html('list_default'), Display::grid_html('list_week'), Display::grid_html('list_course')),'sub_tab');
//Main tabs
echo Display::tabs($headers, array($tabs, Display::grid_html('exercises'),$my_reporting));

Display :: display_footer();
