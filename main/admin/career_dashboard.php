<?php
/* For licensing terms, see /license.txt */

// Language files that should be included.
$language_file = array('courses', 'index', 'admin');
$cidReset = true;
require_once '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'course.lib.php';
//require_once $libpath.'usermanager.lib.php';
require_once $libpath.'career.lib.php';
require_once $libpath.'promotion.lib.php';
require_once $libpath.'sessionmanager.lib.php';
require_once $libpath.'formvalidator/FormValidator.class.php';

require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathList.class.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.class.php';

api_protect_admin_script();

$this_section = SECTION_PLATFORM_ADMIN;

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jquery_ui_js(true);

// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => 'career_dashboard.php','name' => get_lang('CareersAndPromotions'));

Display :: display_header($nameTools);



$form = new FormValidator('filter_form','GET', api_get_self());

$career = new Career();

$condition = array('status = ?' => 1);
if ($form->validate()) {
    $data = $form->getSubmitValues(); 
    $filter = intval($data['filter']);   
    if (!empty($filter)) {
        $condition = array('status = ? AND id = ? ' => array(1, $filter));
    }
}

$careers = $career->get_all(array('status = ?' => 1)); //only status =1
$career_select_list = array();
$career_select_list[0] = ' -- '.get_lang('Select').' --';
foreach ($careers as $item) {    
    $career_select_list[$item['id']] = $item['name'];
}

$form->addElement('select', 'filter', get_lang('Career'), $career_select_list);
$form->addElement('style_submit_button', 'submit', get_lang('Filter'), 'class="search"');


// action links
echo '<div class="actions" style="margin-bottom:20px">';
    echo  '<a href="../admin/index.php">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'),'','32').'</a>';
    echo '<a href="careers.php">'.Display::return_icon('career.png',get_lang('Careers'),'','32').'</a>';
    echo '<a href="promotions.php">'.Display::return_icon('promotion.png',get_lang('Promotions'),'','32').'</a>'; 
echo '</div>';

$form->display();

$careers = $career->get_all($condition); //only status =1

$column_count = 3;
$i = 0;
$grid_js = '';
$career_array = array();
if (!empty($careers)) {
    foreach($careers as $career_item) {   
        $promotion = new Promotion();
        //Getting all promotions
        $promotions = $promotion->get_all_promotions_by_career_id($career_item['id'], 'name DESC');        
        $career_content = '';        
        $promotion_array = array();
        if (!empty($promotions)) {            
            foreach($promotions as $promotion_item) {
                if (!$promotion_item['status']) {
                    continue; //avoid status = 0
                }
                //Getting all sessions from this promotion      
                $sessions = SessionManager::get_all_sessions_by_promotion($promotion_item['id']); 
                   
                $session_list = array();    
                foreach($sessions as $session_item) {       
                    $course_list = SessionManager::get_course_list_by_session_id($session_item['id']);
                    $session_list[] = array('data'=>$session_item,'courses'=>$course_list);
                }   
                $promotion_array[$promotion_item['id']] =array('name'=>$promotion_item['name'], 'sessions'=>$session_list);  
            }
        }
        $career_array[$career_item['id']] = array('name'=>$career_item['name'],'promotions'=>$promotion_array);
    }   
}

echo '<table class="data_table">';

foreach($career_array as $career_id => $data) {
    $career     = $data['name'];
    $promotions = $data['promotions'];        
    $career = Display::url($career,'careers.php?action=edit&id='.$career_id);
    $career = Display::tag('h3',$career);
    echo '<tr><td style="background-color:#eee" colspan="3">'.$career.'</td></tr>';   
    foreach($promotions as $promotion_id => $promotion) {         
    	$promotion_name = $promotion['name'];
        $promotion_url  = Display::url($promotion_name,'promotions.php?action=edit&id='.$promotion_id);
        $sessions       = $promotion['sessions'];
        echo '<tr>';
        $count = count($sessions);
        $rowspan = '';
        if (!empty($count)) {     
            $count++;
        	$rowspan = 'rowspan="'.$count.'"';
        }
        echo '<td '.$rowspan.'>';        
        //echo $promotion_url;
          echo Display::tag('h4',$promotion_url);
        echo '</td>';
        echo '</tr>';      
         
        if (!empty($sessions))
        foreach($sessions as $session) {
            $course_list = $session['courses'];
            
            $url = Display::url($session['data']['name'], 'resume_session.php?id_session='.$session['data']['id']); 
            echo '<tr>';
                //Session name
                echo Display::tag('td',$url);         
                echo '<td>';
                    //Courses
                    echo '<table>';
                    foreach($course_list as $course) {
                       echo '<tr>';
                       
                       $url = Display::url($course['title'], api_get_path(WEB_COURSE_PATH).$course['directory'].'/?id_session='.$session['data']['id']);                       
                       echo Display::tag('td',$url);
                       echo '</tr>';	
                    }
                    echo '</table>';
                echo '</td>';    
            echo '</tr>';       
        }
    }
}
echo '</table>';
Display::display_footer();
