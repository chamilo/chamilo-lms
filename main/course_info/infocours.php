<?php
/* For licensing terms, see /license.txt */

/**
 *	Code to display the course settings form (for the course admin)
 *	and activate the changes.
 *
 *	See ./inc/conf/course_info.conf.php for settings
 * @todo Move $canBeEmpty from course_info.conf.php to config-settings
 * @todo Take those config settings into account in this script
 * @author Patrick Cool <patrick.cool@UGent.be>
 * @author Roan Embrechts, refactoring
 * and improved course visibility|subscribe|unsubscribe options
 * @package chamilo.course_info
 */
/**
 * Code
 */


/*	   INIT SECTION */

// Language files that need to be included
$language_file = array('create_course', 'course_info', 'admin');
require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;

$nameTools = get_lang('ModifInfo');

/*	Libraries */
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(INCLUDE_PATH).'conf/course_info.conf.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'pdf.lib.php';

/*	Constants and variables */
define('MODULE_HELP_NAME', 'Settings');
define('COURSE_CHANGE_PROPERTIES', 'COURSE_CHANGE_PROPERTIES');

$TABLECOURSE 				= Database :: get_main_table(TABLE_MAIN_COURSE);
$TABLEFACULTY 				= Database :: get_main_table(TABLE_MAIN_CATEGORY);
$TABLECOURSEHOME 			= Database :: get_course_table(TABLE_TOOL_LIST);
$TABLELANGUAGES 			= Database :: get_main_table(TABLE_MAIN_LANGUAGE);
$TABLEBBCONFIG 				= Database :: get_course_table(TOOL_FORUM_CONFIG_TABLE);
$currentCourseID 			= $_course['sysCode'];
$currentCourseRepository    = $_course['path'];
$is_allowedToEdit 			= $is_courseAdmin || $is_platformAdmin;
$course_setting_table 		= Database::get_course_table(TABLE_COURSE_SETTING);

$course_code = $_course['sysCode'];
$course_access_settings = CourseManager :: get_access_settings($course_code);

//LOGIC FUNCTIONS
function is_settings_editable() {
	return $GLOBALS['course_info_is_editable'];
}

/*		MAIN CODE */

if (!$is_allowedToEdit) {
	api_not_allowed(true);
}

$show_delete_watermark_text_message = false;
if (api_get_setting('pdf_export_watermark_by_course') == 'true') {
    if (isset($_GET['delete_watermark'])) {
        PDF::delete_watermark($course_code);
        $show_delete_watermark_text_message = true;        
    }
}
$table_course_category = Database :: get_main_table(TABLE_MAIN_CATEGORY);
$tbl_user              = Database :: get_main_table(TABLE_MAIN_USER);
$tbl_admin             = Database :: get_main_table(TABLE_MAIN_ADMIN);
$tbl_course_user       = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_course            = Database :: get_main_table(TABLE_MAIN_COURSE);

// Get all course categories
$sql = "SELECT code,name FROM ".$table_course_category." WHERE auth_course_child ='TRUE'  OR code = '".Database::escape_string($_course['categoryCode'])."'  ORDER BY tree_pos";
$res = Database::query($sql);

$s_select_course_tutor_name = "SELECT tutor_name FROM $tbl_course WHERE code='$course_code'";
$q_tutor = Database::query($s_select_course_tutor_name);
$s_tutor = Database::result($q_tutor, 0, 'tutor_name');

$target_name = api_sort_by_first_name() ? 'firstname' : 'lastname';
$s_sql_course_titular = "SELECT DISTINCT username, lastname, firstname FROM $tbl_user as user, $tbl_course_user as course_rel_user WHERE (course_rel_user.status='1') AND user.user_id=course_rel_user.user_id AND course_code='".$course_code."' ORDER BY ".$target_name." ASC";
$q_result_titulars = Database::query($s_sql_course_titular);

if (Database::num_rows($q_result_titulars) == 0) {
	$sql = "SELECT username, lastname, firstname FROM $tbl_user as user, $tbl_admin as admin WHERE admin.user_id=user.user_id ORDER BY ".$target_name." ASC";
	$q_result_titulars = Database::query($sql);
}

$a_profs[0] = '-- '.get_lang('NoManager').' --';
while ($a_titulars = Database::fetch_array($q_result_titulars)) {
	$s_username = $a_titulars['username'];
	$s_lastname = $a_titulars['lastname'];
	$s_firstname = $a_titulars['firstname'];

	if (api_get_person_name($s_firstname, $s_lastname) == $s_tutor) {
		$s_selected_tutor = api_get_person_name($s_firstname, $s_lastname);
	}
	$s_disabled_select_titular = '';
	if (!$is_courseAdmin) {
		$s_disabled_select_titular = 'disabled=disabled';
	}
	$a_profs[api_get_person_name($s_firstname, $s_lastname)] = api_get_person_name($s_lastname, $s_firstname).' ('.$s_username.')';
}

while ($cat = Database::fetch_array($res)) {
	$categories[$cat['code']] = '('.$cat['code'].') '.$cat['name'];
	ksort($categories);
}

$linebreak = '<div class="row"><div class="label"></div><div class="formw" style="border-bottom:1px dashed grey"></div></div>';

// Build the form
$form = new FormValidator('update_course');

// COURSE SETTINGS
$form->addElement('html', '<div class="sectiontitle"><a href="#header" style="float:right;">'.Display::return_icon('top.gif', get_lang('Top')).'</a><a name="coursesettings" id="coursesettings"></a>'.Display::return_icon('settings.png', get_lang('CourseSettings'),'','22').' '.get_lang('CourseSettings').'</div>');

$image_html = '';

// Sending image
if ($form->validate() && is_settings_editable()) {	
    // update course picture
    $picture = $_FILES['picture'];
    if (!empty($picture['name'])) {
        $picture_uri = CourseManager::update_course_picture($course_code, $picture['name'], $picture['tmp_name']);
    }
}
    
// Display course picture
$course_path = api_get_path(SYS_COURSE_PATH).$currentCourseRepository;   // course path
if (file_exists($course_path.'/course-pic85x85.png')) {
    $course_web_path = api_get_path(WEB_COURSE_PATH).$currentCourseRepository;   // course web path
    $course_medium_image = $course_web_path.'/course-pic85x85.png?'.rand(1,1000); // redimensioned image 85x85
    $image_html =  '<div class="row"><div class="formw"><img src="'.$course_medium_image.'" /></div></div>';
}
$form->addElement('html', $image_html);

$visual_code=$form->addElement('text', 'visual_code', get_lang('Code'));
$visual_code->freeze();
$form->applyFilter('visual_code', 'strtoupper');
//$form->add_textfield('tutor_name', get_lang('Professors'), true, array ('size' => '60'));
$prof = &$form->addElement('select', 'tutor_name', get_lang('Teacher'), $a_profs, array('style'=>'width:350px'));
$form->applyFilter('tutor_name', 'html_filter');

$prof -> setSelected($s_selected_tutor);
$form->add_textfield('title', get_lang('Title'), true, array('size' => '60'));
//$form->applyFilter('title', 'html_filter');
$form->applyFilter('title', 'trim');

$form->addElement('select', 'category_code', get_lang('Fac'), $categories, array('style'=>'width:350px'));
$form->add_textfield('department_name', get_lang('Department'), false, array('size' => '60'));
//$form->applyFilter('department_name', 'html_filter');
$form->applyFilter('department_name', 'trim');

$form->add_textfield('department_url', get_lang('DepartmentUrl'), false, array('size' => '60'));
//$form->applyFilter('department_url', 'html_filter');

$form->addRule('tutor_name', get_lang('ThisFieldIsRequired'), 'required');
$form->addElement('select_language', 'course_language', get_lang('Ln'));
$form->addElement('static', null, '&nbsp;', get_lang('TipLang'));

// Picture
$form->addElement('file', 'picture', get_lang('AddPicture'));
$allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);

if (api_get_setting('pdf_export_watermark_by_course') == 'true') {
    $url =  PDF::get_watermark($course_code);
    $form->add_textfield('pdf_export_watermark_text', get_lang('PDFExportWatermarkTextTitle'), false, array('size' => '60'));
    $form->addElement('file', 'pdf_export_watermark_path', get_lang('AddWaterMark'));
    if ($url != false) {        
        $delete_url = '<a href="?delete_watermark">'.Display::return_icon('delete.png',get_lang('DelImage')).'</a>';
        $form->addElement('html', '<div class="row"><div class="formw"><a href="'.$url.'">'.$url.' '.$delete_url.'</a></div></div>');
    }
    $form->addRule('pdf_export_watermark_path', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);    
}

$form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');


// COURSE ACCESS
$form->addElement('html', '<div class="sectiontitle" style="margin-top: 40px;"><a href="#header" style="float:right;">'.Display::return_icon('top.gif', get_lang('Top')).'</a><a name="coursesaccess" id="coursesaccess"></a>'.Display::return_icon('course.png', get_lang('CourseAccess'),'','22').' '.get_lang('CourseAccess').'</div>');
$form->addElement('radio', 'visibility', get_lang("CourseAccess"), get_lang('OpenToTheWorld'), COURSE_VISIBILITY_OPEN_WORLD);
$form->addElement('radio', 'visibility', null, get_lang('OpenToThePlatform'), COURSE_VISIBILITY_OPEN_PLATFORM);
$form->addElement('radio', 'visibility', null, get_lang('Private'), COURSE_VISIBILITY_REGISTERED);
$form->addElement('radio', 'visibility', null, get_lang('CourseVisibilityClosed'), COURSE_VISIBILITY_CLOSED);
$form->addElement('static', null, null, get_lang("CourseAccessConfigTip"));
$form->addElement('html', $linebreak);

$form->addElement('radio', 'subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
$form->addElement('radio', 'subscribe', null, get_lang('Denied'), 0);
$form->addElement('html', $linebreak);

$form->addElement('radio', 'unsubscribe', get_lang('Unsubscription'), get_lang('AllowedToUnsubscribe'), 1);
$form->addElement('radio', 'unsubscribe', null, get_lang('NotAllowedToUnsubscribe'), 0);
$form->addElement('html', $linebreak);

$form->add_textfield('course_registration_password', get_lang('CourseRegistrationPassword'), false, array('size' => '60'));

$form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');


// EMAIL NOTIFICATIONS
$form->addElement('html', '<div class="sectiontitle" style="margin-top: 40px;"><a href="#header" style="float:right;">'.Display::return_icon('top.gif', get_lang('Top')).'</a><a name="emailnotifications" id="emailnotifications"></a>'.Display::return_icon('mail.png', get_lang('EmailNotifications'),'','22').' '.get_lang('EmailNotifications').'</div>');

$form->addElement('radio', 'email_alert_to_teacher_on_new_user_in_course', get_lang('NewUserEmailAlert'), get_lang('NewUserEmailAlertEnable'), 1);
$form->addElement('radio', 'email_alert_to_teacher_on_new_user_in_course', null, get_lang('NewUserEmailAlertToTeacharAndTutor'), 2);
$form->addElement('radio', 'email_alert_to_teacher_on_new_user_in_course', null, get_lang('NewUserEmailAlertDisable'), 0);
$form->addElement('html', $linebreak);

$form->addElement('radio', 'email_alert_students_on_new_homework', get_lang('NewHomeworkEmailAlert'), get_lang('NewHomeworkEmailAlertEnable'), 1);
$form->addElement('radio', 'email_alert_students_on_new_homework', null, get_lang('NewHomeworkEmailAlertDisable'), 0);
$form->addElement('html', $linebreak);

$form->addElement('radio', 'email_alert_manager_on_new_doc', get_lang('WorkEmailAlert'), get_lang('WorkEmailAlertActivate'), 1);
$form->addElement('radio', 'email_alert_manager_on_new_doc', null, get_lang('WorkEmailAlertDeactivate'), 0);
$form->addElement('html', $linebreak);

$form->addElement('radio', 'email_alert_on_new_doc_dropbox', get_lang('DropboxEmailAlert'), get_lang('DropboxEmailAlertActivate'), 1);
$form->addElement('radio', 'email_alert_on_new_doc_dropbox', null, get_lang('DropboxEmailAlertDeactivate'), 0);
$form->addElement('html', $linebreak);

$form->addElement('radio', 'email_alert_manager_on_new_quiz', get_lang('QuizEmailAlert'), get_lang('QuizEmailAlertActivate'), 1);
$form->addElement('radio', 'email_alert_manager_on_new_quiz', null, get_lang('QuizEmailAlertDeactivate'), 0);

$form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');

// USER RIGHTS
$form->addElement('html', '<div class="sectiontitle" style="margin-top: 40px;"><a href="#header" style="float:right;">'.Display::return_icon('top.gif', get_lang('Top')).'</a><a name="userrights" id="userrights"></a>'.Display::return_icon('user.png', get_lang('UserRights'),'','22').' '.get_lang('UserRights').'</div>');
$form->addElement('radio', 'allow_user_edit_agenda', get_lang('AllowUserEditAgenda'), get_lang('AllowUserEditAgendaActivate'), 1);
$form->addElement('radio', 'allow_user_edit_agenda', null, get_lang('AllowUserEditAgendaDeactivate'), 0);
$form->addElement('html', $linebreak);

$form->addElement('radio', 'allow_user_edit_announcement', get_lang('AllowUserEditAnnouncement'), get_lang('AllowUserEditAnnouncementActivate'), 1);
$form->addElement('radio', 'allow_user_edit_announcement', null, get_lang('AllowUserEditAnnouncementDeactivate'), 0);
$form->addElement('html', $linebreak);

$form->addElement('radio', 'allow_user_image_forum', get_lang('AllowUserImageForum'), get_lang('AllowUserImageForumActivate'), 1);
$form->addElement('radio', 'allow_user_image_forum', null, get_lang('AllowUserImageForumDeactivate'), 0);
$form->addElement('html', $linebreak);

$form->addElement('radio', 'allow_user_view_user_list', get_lang('AllowUserViewUserList'), get_lang('AllowUserViewUserListActivate'), 1);
$form->addElement('radio', 'allow_user_view_user_list', null, get_lang('AllowUserViewUserListDeactivate'), 0);
$form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');


// CHAT SETTINGS
$form->addElement('html', '<div class="sectiontitle" style="margin-top: 40px;"><a href="#header" style="float:right;">'.Display::return_icon('top.gif', get_lang('Top')).'</a><a name="chatsettings" id="chatsettings"></a>'.Display::return_icon('chat.png', get_lang('ConfigChat'),'','22').' '.get_lang('ConfigChat').'</div>');
$form->addElement('radio', 'allow_open_chat_window', get_lang('AllowOpenchatWindow'), get_lang('AllowOpenChatWindowActivate'), 1);
$form->addElement('radio', 'allow_open_chat_window', null, get_lang('AllowOpenChatWindowDeactivate'), 0);

$form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');

// LEARNING PATH
$form->addElement('html','<div class="sectiontitle" style="margin-top: 40px;"><a href="#header" style="float:right;">'.Display::return_icon('top.gif', get_lang('Top')).'</a><a name="learnpath" id="learnpath"></a>'.Display::return_icon('scorms.png', get_lang('ConfigLearnpath'),'','22').' '.get_lang('ConfigLearnpath').'</div><div style="clear:both;"></div>');
//Auto launch LP
$form->addElement('radio', 'enable_lp_auto_launch', get_lang('LPAutoLaunch'), get_lang('RedirectToALearningPath'), 1);
$form->addElement('radio', 'enable_lp_auto_launch', get_lang('LPAutoLaunch'), get_lang('RedirectToTheLearningPathList'), 2);
$form->addElement('radio', 'enable_lp_auto_launch', null, get_lang('Deactivate'), 0);
$form -> addElement('html', $linebreak);

if (api_get_setting('allow_course_theme') == 'true') {
	// Allow theme into Learning path
	$form->addElement('radio', 'allow_learning_path_theme', get_lang('AllowLearningPathTheme'), get_lang('AllowLearningPathThemeAllow'), 1);
	$form->addElement('radio', 'allow_learning_path_theme', null, get_lang('AllowLearningPathThemeDisallow'), 0);
	
	$form->addElement('select_theme', 'course_theme', get_lang('Theme'));
	$form->applyFilter('course_theme', 'trim');
}

if (is_settings_editable()) {
	$form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');
} else {
	// Is it allowed to edit the course settings?
	if (!is_settings_editable())
		$disabled_output = "disabled";
	$form->freeze();
}


// THEMATIC ADVANCE SETTINGS
$form->addElement('html', '<div class="sectiontitle" style="margin-top: 40px;"><a href="#header" style="float:right;">'.Display::return_icon('top.gif', get_lang('Top')).'</a><a name="thematicadvance" id="thematicadvance"></a>'.Display::return_icon('course_progress.png', get_lang('ThematicAdvanceConfiguration'),'','22').' '.get_lang('ThematicAdvanceConfiguration').'</div>');
$form->addElement('radio', 'display_info_advance_inside_homecourse', get_lang('InfoAboutAdvanceInsideHomeCourse'), get_lang('DisplayAboutLastDoneAdvance'), 1);
$form->addElement('radio', 'display_info_advance_inside_homecourse', null, get_lang('DisplayAboutNextAdvanceNotDone'), 2);
$form->addElement('radio', 'display_info_advance_inside_homecourse', null, get_lang('DisplayAboutNextAdvanceNotDoneAndLastDoneAdvance'), 3);
$form->addElement('radio', 'display_info_advance_inside_homecourse', null, get_lang('DoNotDisplayAnyAdvance'), 0);

$form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"');


// Get all the course information
$all_course_information =  CourseManager::get_course_information($_course['sysCode']);


// Set the default values of the form

$values['title']                        = $_course['name'];
$values['visual_code']                  = $_course['official_code'];
$values['category_code']                = $_course['categoryCode'];
//$values['tutor_name']                 = $_course['titular'];
$values['course_language']              = $_course['language'];
$values['department_name']              = $_course['extLink']['name'];
$values['department_url']               = $_course['extLink']['url'];
$values['visibility']                   = $_course['visibility'];
$values['subscribe']                    = $course_access_settings['subscribe'];
$values['unsubscribe']                  = $course_access_settings['unsubscribe'];
$values['course_registration_password'] = $all_course_information['registration_code'];



// Get send_mail_setting (auth)from table
$values['email_alert_to_teacher_on_new_user_in_course']= api_get_course_setting('email_alert_to_teacher_on_new_user_in_course');
// Get send_mail_setting (work)from table
$values['email_alert_manager_on_new_doc']           = api_get_course_setting('email_alert_manager_on_new_doc');
// Get send_mail_setting (dropbox) from table
$values['email_alert_on_new_doc_dropbox']           = api_get_course_setting('email_alert_on_new_doc_dropbox');
// Get send_mail_setting (work)from table
$values['email_alert_manager_on_new_quiz']          = api_get_course_setting('email_alert_manager_on_new_quiz');
// Get allow_user_edit_agenda from table
$values['allow_user_edit_agenda']                   = api_get_course_setting('allow_user_edit_agenda');
// Get allow_user_edit_announcement from table
$values['allow_user_edit_announcement']             = api_get_course_setting('allow_user_edit_announcement');
// Get allow_user_image_forum from table
$values['allow_user_image_forum']                   = api_get_course_setting('allow_user_image_forum');
// Get allow_open_chat_window from table
$values['allow_open_chat_window']                   = api_get_course_setting('allow_open_chat_window');
// Get course_theme from table
$values['course_theme']                             = api_get_course_setting('course_theme');
// Get allow_learning_path_theme from table
$values['allow_learning_path_theme']                = api_get_course_setting('allow_learning_path_theme');
//Get allow show user list
$values['allow_user_view_user_list']                = api_get_course_setting('allow_user_view_user_list');
//Get allow show user list
$values['display_info_advance_inside_homecourse']   = api_get_course_setting('display_info_advance_inside_homecourse');
$values['email_alert_students_on_new_homework']     = api_get_course_setting('email_alert_students_on_new_homework');

$values['enable_lp_auto_launch']                    = api_get_course_setting('enable_lp_auto_launch');

$values['pdf_export_watermark_text']                = api_get_course_setting('pdf_export_watermark_text');

$form->setDefaults($values);

// Validate form
if ($form->validate() && is_settings_editable()) {
	$update_values = $form->exportValues();
/*
    // update course picture
    $picture = $_FILES['picture'];
    if (!empty($picture['name'])) {
        $picture_uri = CourseManager::update_course_picture($course_code, $picture['name'], $picture['tmp_name']);
    }*/
    
    $pdf_export_watermark_path = $_FILES['pdf_export_watermark_path'];
    
    if (!empty($pdf_export_watermark_path['name'])) {        
        $pdf_export_watermark_path_result = PDF::upload_watermark($pdf_export_watermark_path['name'], $pdf_export_watermark_path['tmp_name'], $course_code);        
        unset($update_values['pdf_export_watermark_path']);
    }
    
    //Variables that will be saved in the TABLE_MAIN_COURSE table
    $update_in_course_table = array('title','visual_code', 'course_language','category_code','department_name', 'department_url','visibility',  'subscribe', 'unsubscribe','tutor_name','course_registration_password');

	foreach ($update_values as $index =>$value) {
		$update_values[$index] = Database::escape_string($value);
	}
    
    
	$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
	$sql = "UPDATE $table_course SET
				title 				    = '".Security::remove_XSS($update_values['title'])."',
				visual_code 		    = '".$update_values['visual_code']."',
				course_language 	    = '".$update_values['course_language']."',
				category_code 		    = '".$update_values['category_code']."',
				department_name  	    = '".Security::remove_XSS($update_values['department_name'])."',
				department_url  	    = '".Security::remove_XSS($update_values['department_url'])."',
				visibility  		    = '".$update_values['visibility']."',
				subscribe  			    = '".$update_values['subscribe']."',
				unsubscribe  		    = '".$update_values['unsubscribe']."',
				tutor_name     		    = '".$update_values['tutor_name']."',      
				registration_code 	    = '".$update_values['course_registration_password']."'
			WHERE code = '".$course_code."'";
	Database::query($sql);

	// Update course_settings table - this assumes those records exist, otherwise triggers an error
	$table_course_setting = Database::get_course_table(TABLE_COURSE_SETTING);   
    
    foreach($update_values as $key =>$value) {
        //We do not update variables that were already saved in the TABLE_MAIN_COURSE table
        if (!in_array($key, $update_in_course_table)) {            
            Database::update($table_course_setting, array('value' => $update_values[$key]), array('variable = ? ' =>$key));
        }    	
    }
	$cidReset = true;
	$cidReq = $course_code;
	require '../inc/local.inc.php';
	header('Location: infocours.php?action=show_message&amp;cidReq='.$course_code);
	exit;
}

/*	Header */

Display :: display_header($nameTools, MODULE_HELP_NAME);
if ($show_delete_watermark_text_message) {
    Display :: display_normal_message(get_lang('FileDeleted'));
}
//api_display_tool_title($nameTools);
if (isset($_GET['action']) && $_GET['action'] == 'show_message') {
	Display :: display_normal_message(get_lang('ModifDone'));
}

// actions bar
echo '<div class="actions">';
echo '<a href="#coursesettings">'.Display::return_icon('settings.png', get_lang('CourseSettings'),'','32').'</a>';
echo '<a href="#coursesaccess">'.Display::return_icon('course.png', get_lang('CourseAccess'),'','32').'</a>';
echo '<a href="#emailnotifications">'.Display::return_icon('mail.png', get_lang('EmailNotifications'),'','32').'</a>';
echo '<a href="#userrights">'.Display::return_icon('user.png', get_lang('UserRights'),'','32').'</a>';
echo '<a href="#chatsettings">'.Display::return_icon('chat.png', get_lang('ConfigChat'),'','32').'</a>';
if (api_get_setting('allow_course_theme') == 'true') {
	echo '<a href="#learnpath">'.Display::return_icon('scorms.png', get_lang('ConfigLearnpath'),'','32').'</a>';
}
echo '<a href="#thematicadvance">'.Display::return_icon('course_progress.png', get_lang('ThematicAdvanceConfiguration'),'','32').'</a>';
echo '</div>';

// Display the form
$form->display();

// @todo this code is out dated should
/*

if ($showDiskQuota && $currentCourseDiskQuota != '') {

?>
<table>
	<tr>
	<td><?php echo get_lang("DiskQuota"); ?>&nbsp;:</td>
	<td><?php echo $currentCourseDiskQuota; ?> <?php echo $byteUnits[0] ?></td>
	</tr>
	<?php

	}
	if ($showLastEdit && $currentCourseLastEdit != "" && $currentCourseLastEdit != "0000-00-00 00:00:00")
	{
?>
	<tr>
	<td><?php echo get_lang('LastEdit'); ?>&nbsp;:</td>
	<td><?php echo api_convert_and_format_date($currentCourseLastEdit, null, date_default_timezone_get()); ?></td>
	</tr>
	<?php

	}
	if ($showLastVisit && $currentCourseLastVisit != "" && $currentCourseLastVisit != "0000-00-00 00:00:00")
	{
?>
	<tr>
	<td><?php echo get_lang('LastVisit'); ?>&nbsp;:</td>
	<td><?php echo api_convert_and_format_date($currentCourseLastVisit, null, date_default_timezone_get()); ?></td>
	</tr>
	<?php

	}
	if ($showCreationDate && $currentCourseCreationDate != "" && $currentCourseCreationDate != "0000-00-00 00:00:00")
	{
?>
	<tr>
	<td><?php echo get_lang('CreationDate'); ?>&nbsp;:</td>
	<td><?php echo api_convert_and_format_date($currentCourseCreationDate, null, date_default_timezone_get()); ?></td>
	</tr>
	<?php

	}
	if ($showExpirationDate && $currentCourseExpirationDate != "" && $currentCourseExpirationDate != "0000-00-00 00:00:00")
	{
?>
	<tr>
	<td><?php echo get_lang('ExpirationDate'); ?>&nbsp;:</td>
	<td>
	<?php

		echo api_convert_and_format_date($currentCourseExpirationDate, null, date_default_timezone_get());
		echo "<br />".get_lang('OrInTime')." : ";
		$nbJour = (strtotime($currentCourseExpirationDate) - time()) / (60 * 60 * 24);
		$nbAnnees = round($nbJour / 365);
		$nbJour = round($nbJour - $nbAnnees * 365);
		switch ($nbAnnees)
		{
			case "1" :
				echo $nbAnnees, " an ";
				break;
			case "0" :
				break;
			default :
				echo $nbAnnees, " ans ";
		};
		switch ($nbJour)
		{
			case "1" :
				echo $nbJour, " jour ";
				break;
			case "0" :
				break;
			default :
				echo $nbJour, " jours ";
		}
		if ($canReportExpirationDate)
		{
			echo " -&gt; <a href=\"".$urlScriptToReportExpirationDate."\">".get_lang('PostPone')."</a>";
		}
?>
</td>
</tr>
</table>
<?php
}
*/

Display::display_footer();
