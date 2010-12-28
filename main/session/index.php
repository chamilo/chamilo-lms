<?php
// Language files that should be included.
$language_file = array('courses', 'index');
require_once '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'course.lib.php';
//require_once $libpath.'usermanager.lib.php';
require_once $libpath.'sessionmanager.lib.php';
require_once $libpath.'formvalidator/FormValidator.class.php';


api_block_anonymous_users(); // Only users who are logged in can proceed.


$this_section = SECTION_COURSES;


$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/cupertino/jquery-ui-1.8.7.custom.css" type="text/css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-1.4.4.min.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/cupertino/jquery-ui-1.8.7.custom.min.js" type="text/javascript" language="javascript"></script>'; //jQuery
   
Display :: display_header($nameTools);



$session_id     = intval($_GET['id_session']);
$session_info   = SessionManager::fetch($session_id);
$course_list    = SessionManager::get_course_list_by_session_id($session_id);
$course_select = array();

echo Display::tag('h1', $session_info['name']);

foreach ($course_list as $course_item) {
	$course_select[$course_item['id']] =  $course_item['title'];
}

$form = new FormValidator('exercise_admin', 'post', api_get_self());
$form->addElement('select', 'course_id', get_lang('CourseList'),$course_select,'onchange="javascript:feedbackselection()"');
$form->display();



if ($form->validate()) {
    
}
?>
<br />
<script>
    $(function() {
        $( "#tabs" ).tabs();
        $( "#sub_tab" ).tabs();
    });
</script>


<?php 

$headers = array(get_lang('MyCourses'), get_lang('MyQCM'), get_lang('MyResults'));
$sub_header = array(get_lang('AllCourses'), get_lang('PerWeek'), get_lang('ParMatiere'));
$tabs =  Display::tabs($sub_header, array('aaaa','bbb','ccc'),'sub_tab');
echo Display::tabs($headers, array($tabs,'bbb','ccc'));
exit;
// Footer
Display :: display_footer();