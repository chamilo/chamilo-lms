<?php
/* For licensing terms, see /license.txt */

/**
*	@package chamilo.work
* 	@author Thomas, Hugues, Christophe - original version
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University - ability for course admins to specify wether uploaded documents are visible or invisible by default.
* 	@author Roan Embrechts, code refactoring and virtual course support
* 	@author Frederic Vauthier, directories management
*   @author Julio Montoya <gugli100@gmail.com> BeezNest 2011 LOTS of bug fixes
*
* 	@todo refactor more code into functions, use quickforms, coding standards, ... jm
*/

/**
 * 	STUDENT PUBLICATIONS MODULE
 *
 * Note: for a more advanced module, see the dropbox tool.
 * This one is easier with less options.
 * This tool is better used for publishing things,
 * sending in assignments is better in the dropbox.
 *
 * GOALS
 * *****
 * Allow student to quickly send documents immediately visible on the Course 
 *
 * The script does 5 things:
 *
 * 	1. Upload documents
 * 	2. Give them a name
 * 	3. Modify data about documents
 * 	4. Delete link to documents and simultaneously remove them
 * 	5. Show documents list to students and visitors
 *
 * On the long run, the idea is to allow sending realvideo . Which means only
 * establish a correspondence between RealServer Content Path and the user's
 * documents path.
 * 
 *
*/

/*		INIT SECTION */

$language_file = array('exercice', 'work', 'document', 'admin');

require_once '../inc/global.inc.php';

// @todo why is this needed?
//session
if (isset ($_GET['id_session'])) {
	$_SESSION['id_session'] = Database::escape_string($_GET['id_session']);
}
isset($_SESSION['id_session']) ? $id_session = $_SESSION['id_session'] : $id_session = null;

// Including necessary files
require_once 'work.lib.php';
require_once api_get_path(LIBRARY_PATH).'document.lib.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'fckeditor/fckeditor.php';
include_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileDisplay.lib.php';


$course_id  = api_get_course_int_id();
$course_info = api_get_course_info();

$user_id 	= api_get_user_id();

// Section (for the tabs)
$this_section = SECTION_COURSES;
$ctok = $_SESSION['sec_token'];
$stok = Security::get_token();

$work_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$my_folder_data = get_work_data_by_id($work_id);

$curdirpath = '';
$is_special = false;
if (!empty($my_folder_data)) {	
	if ($my_folder_data['filetype'] == 'folder') {	
		$curdirpath = $my_folder_data['url'];			
		if (!empty($my_folder_data['has_properties'])) {
			$is_special = true;
		} 
	}
}


$htmlHeadXtra[] = to_javascript_work();
$htmlHeadXtra[] = '<script type="text/javascript">
function setFocus(){
    $("#work_title").focus();
}
$(document).ready(function () {
    setFocus();
});
</script>';

// Table definitions
$main_course_table 	= Database :: get_main_table(TABLE_MAIN_COURSE);
$work_table 		= Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
$iprop_table 		= Database :: get_course_table(TABLE_ITEM_PROPERTY);
$TSTDPUBASG			= Database :: get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
$table_course_user	= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$table_user			= Database :: get_main_table(TABLE_MAIN_USER);
$table_session		= Database :: get_main_table(TABLE_MAIN_SESSION);
$table_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$table_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

/*	Constants and variables */

$tool_name 		= get_lang('StudentPublications');
$course_code 	= api_get_course_id();
$session_id 	= api_get_session_id();

$is_course_member = CourseManager::is_user_subscribed_in_real_or_linked_course($user_id, $course_code,$session_id);
$is_course_member = $is_course_member || api_is_platform_admin();

$currentCourseRepositorySys = api_get_path(SYS_COURSE_PATH) . $_course['path'] . '/';
$currentCourseRepositoryWeb = api_get_path(WEB_COURSE_PATH) . $_course['path'] . '/';

$currentUserFirstName 	= $_user['firstName'];
$currentUserLastName 	= $_user['lastName'];
$currentUserEmail 		= $_user['mail'];

$delete			= isset($_REQUEST['delete']) ? Database::escape_string($_REQUEST['delete']) : '';
$description 	= isset($_REQUEST['description']) ? Database::escape_string($_REQUEST['description']) : '';

$item_id 		= isset($_REQUEST['item_id']) ? intval($_REQUEST['item_id']) : null;
$parent_id 		= isset($_REQUEST['parent_id']) ? Database::escape_string($_REQUEST['parent_id']) : '';
$origin 		= isset($_REQUEST['origin']) ? Security::remove_XSS($_REQUEST['origin']) : '';

$submitGroupWorkUrl = isset($_REQUEST['submitGroupWorkUrl']) ? Security::remove_XSS($_REQUEST['submitGroupWorkUrl']) : '';
$title 			= isset($_REQUEST['title']) ? Database::escape_string($_REQUEST['title']) : '';
$uploadvisibledisabled = isset($_REQUEST['uploadvisibledisabled']) ? Database::escape_string($_REQUEST['uploadvisibledisabled']) : '';


// get data for publication assignment
$has_expired = false;
$has_ended = false;

//directories management
$sys_course_path 	= api_get_path(SYS_COURSE_PATH);
$course_dir 		= $sys_course_path . $_course['path'];
$base_work_dir 		= $course_dir . '/work';

$cur_dir_path = '';
if (isset($curdirpath) && $curdirpath != '') {
	//now using common security approach with security lib
	$in_course = Security :: check_abs_path($base_work_dir.$curdirpath, $base_work_dir);	
	if (!$in_course) {
		$curdirpath = "/";
	}	
}
if ($curdirpath == '.') {
	$curdirpath = '/';
}

/*	Configuration settings */

$link_target_parameter = ""; //or e.g. "target=\"_blank\"";

$display_list_users_without_publication = isset($_GET['list']) && Security::remove_XSS($_GET['list']) == 'without';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

if ($action == 'downloadfolder') {
	require 'downloadfolder.inc.php';
}

api_protect_course_script(true);

/*	More init stuff */

if (isset ($_POST['cancelForm']) && !empty ($_POST['cancelForm'])) {
	header('Location: ' . api_get_self() . '?origin='.$origin.'&amp;gradebook='.$gradebook);
	exit;
}

// If the POST's size exceeds 8M (default value in php.ini) the $_POST array is emptied
// If that case happens, we set $submitWork to 1 to allow displaying of the error message
// The redirection with header() is needed to avoid apache to show an error page on the next request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !sizeof($_POST)) {
	if (strstr($_SERVER['REQUEST_URI'], '?')) {
		header('Location: ' . $_SERVER['REQUEST_URI'] . '&submitWork=1');
		exit ();
	} else {
		header('Location: ' . $_SERVER['REQUEST_URI'] . '?submitWork=1');
		exit ();
	}
}

//toolgroup comes from group. the but of tis variable is to limit post to the group of the student
//if (!api_is_course_admin()) {
    
if (isset($_GET['toolgroup'])) {
	$toolgroup = Database::escape_string($_GET['toolgroup']);
	api_session_register('toolgroup');
}

$toolgroup = isset($_SESSION['toolgroup']) ? $_SESSION['toolgroup'] : api_get_group_id();  

//}

$display_upload_form = false;	
if ($action == 'upload_form') {
	$display_upload_form = true;
}


/*	Header */

if (!empty($_GET['gradebook']) && $_GET['gradebook'] == 'view') {
	$_SESSION['gradebook'] = Security::remove_XSS($_GET['gradebook']);
	$gradebook =	$_SESSION['gradebook'];
} elseif (empty($_GET['gradebook'])) {
	unset($_SESSION['gradebook']);
	$gradebook = '';
}

if (!empty($gradebook) && $gradebook == 'view') {    
    $interbreadcrumb[] = array ('url' => '../gradebook/' . $_SESSION['gradebook_dest'],'name' => get_lang('ToolGradebook'));    
}

if (!empty($_SESSION['toolgroup'])) {
	$_clean['toolgroup'] = (int)$_SESSION['toolgroup'];
	$group_properties  = GroupManager :: get_group_properties($_clean['toolgroup']);
	$interbreadcrumb[] = array ('url' => '../group/group.php', 'name' => get_lang('Groups'));
	$interbreadcrumb[] = array ('url' => '../group/group_space.php?gidReq='.$_SESSION['toolgroup'], 'name' => get_lang('GroupSpace').' '.$group_properties['name']);

	$url_dir ='';
	$interbreadcrumb[] = array ('url' =>'work.php?gidReq='.$_SESSION['toolgroup'],'name' => get_lang('StudentPublications'));


	$url_dir = 'work.php?&id=' . $work_id;
	$interbreadcrumb[] = array ('url' => $url_dir,'name' =>  $my_folder_data['title']);
	

	if ($display_upload_form) {
		$interbreadcrumb[] = array ('url' => 'work.php','name' => get_lang('UploadADocument'));
	}

	if ($display_tool_options) {
		$interbreadcrumb[] = array (
			'url' => 'work.php',
			'name' => get_lang('EditToolOptions'));
	}

	if ($action == 'create_dir') {
		$interbreadcrumb[] = array ('url' => 'work.php','name' => get_lang('CreateAssignment'));
	}
	Display :: display_header(null);
} else { 
	if (isset($origin) && $origin != 'learnpath') {
		
	    if (isset($_GET['id']) && !empty($_GET['id']) || $display_upload_form || $action == 'settings' || $action == 'create_dir') {
            $interbreadcrumb[] = array ('url' => 'work.php', 'name' => get_lang('StudentPublications'));
        } else {        	
            $interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('StudentPublications'));
        }
        
        $url_dir = 'work.php?id=' . $work_id;
		$interbreadcrumb[] = array ('url' => $url_dir,'name' =>  $my_folder_data['title']);	
		
		if ($display_upload_form) {
			$interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('UploadADocument'));
		}

		if ($action == 'settings') {
			$interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('EditToolOptions'));
		}
		if ($action == 'create_dir') {
			$interbreadcrumb[] = array ('url' => '#','name' => get_lang('CreateAssignment'));
		}
		Display :: display_header(null);

	} else {
		//we are in the learnpath tool
		require api_get_path(INCLUDE_PATH).'reduced_header.inc.php';
	}
}


//stats
event_access_tool(TOOL_STUDENTPUBLICATION);

$is_allowed_to_edit = api_is_allowed_to_edit(); //has to come after display_tool_view_option();

Display :: display_introduction_section(TOOL_STUDENTPUBLICATION);

// introduction section

if ($origin == 'learnpath') {
	echo '<div style="height:15px">&nbsp;</div>';
}

/*	Display links to upload form and tool options */

if ($is_special) {
	$homework = get_work_assignment_by_id($my_folder_data['id']);
	$has_expired = $has_ended = false;
	$has_expiry_date = false;

	if ($homework['expires_on'] != '0000-00-00 00:00:00' || $homework['ends_on'] != '0000-00-00 00:00:00') {
		$time_now		= time();

		if (!empty($homework['expires_on']) && $homework['expires_on'] != '0000-00-00 00:00:00') {
			$time_expires 	= api_strtotime($homework['expires_on']);
			$difference 	= $time_expires - $time_now;
			if ($difference < 0) {
				$has_expired = true;
				$has_expiry_date = true;
			}
		}
		if (!empty($homework['ends_on']) && $homework['ends_on'] != '0000-00-00 00:00:00') {
			$time_ends 		= api_strtotime($homework['ends_on']);
			$difference2 	= $time_ends - $time_now;
			if ($difference2 < 0) {
				$has_ended = true;
			}
		}
		if (empty($homework['expires_on']) || $homework['expires_on'] == '0000-00-00 00:00:00') {
			$has_expiry_date = false;
		}
		$ends_on 	= api_convert_and_format_date($homework['ends_on']);
		$expires_on = api_convert_and_format_date($homework['expires_on']);

		if ($has_ended) {
			display_action_links($work_id, $curdirpath, $always_show_tool_options, $display_upload_form);
			Display :: display_error_message(get_lang('EndDateAlreadyPassed').' '.$ends_on);
		} elseif ($has_expired) {
			display_action_links($work_id,$curdirpath, $always_show_tool_options, $display_upload_form);
			Display :: display_warning_message(get_lang('ExpiryDateAlreadyPassed').' '.$expires_on);
		} else {
			display_action_links($work_id,$curdirpath, $always_show_tool_options, $display_upload_form);
			if ($has_expiry_date) {
				Display :: display_normal_message(get_lang('ExpiryDateToSendWorkIs').' '.$expires_on);
			}
		}
	} else {
		display_action_links($work_id,$curdirpath, $always_show_tool_options, $display_upload_form);
	}
} else {
	display_action_links($work_id, $curdirpath, $always_show_tool_options, $display_upload_form);
}

switch ($action) {
	case 'mark_work':
	case 'upload_form':			
		if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
			api_not_allowed();
		}
		
		if (empty($item_id)) {		
			$parent_data = get_work_data_by_id($work_id);
			$parent_data['qualification'] = intval($parent_data['qualification']);
			
			if (!empty($parent_data) && !empty($parent_data['qualification']))  {
				$count =  get_work_count_by_student($user_id, $work_id);		
				if ($count  >= 1 ) {
					if (api_get_course_setting('student_delete_own_publication') == '1') {
						Display::display_warning_message(get_lang('CantUploadDeleteYourPaperFirst'));					
					} else {
						Display::display_warning_message(get_lang('CantUpload'));
					}				
					Display::display_footer();
					exit;
				}
			}
		}
		
		$is_author = false;
		if ($item_id) {
			//we found the current user is the author
			$sql = "SELECT * FROM  $work_table WHERE c_id = $course_id AND id = $item_id";
			$result = Database::query($sql);
			$work_item = array();
			if ($result) {
				$work_item = Database::fetch_array($result);
			}			
			
			//Get the author ID for that document from the item_property table
			$author_sql = "SELECT * FROM $iprop_table
						   WHERE c_id = $course_id AND tool = 'work' AND insert_user_id = '$user_id' AND ref = " . $item_id;
			$author_qry = Database::query($author_sql);
			if (Database :: num_rows($author_qry)) {
				$is_author = true;
			}
		}
		
		$form = new FormValidator('form', 'POST', api_get_self() . "?action=upload&id=".$work_id."&curdirpath=" . rtrim(Security :: remove_XSS($curdirpath),'/') . "&gradebook=".Security::remove_XSS($_GET['gradebook'])."&origin=$origin", '', 'enctype="multipart/form-data"');
	
		// form title
		if ($item_id) {
			$form_title = get_lang('EditMedia');
		} else {
			$form_title = get_lang('UploadADocument');
		}
		$form->addElement('header', '', $form_title);
	
		if (!empty ($error_message)) {
			Display :: display_error_message($error_message);
		}
		$show_progress_bar = false;
	
		if ($submitGroupWorkUrl) {
			// For user comming from group space to publish his work
			$realUrl = str_replace($_configuration['root_sys'], api_get_path(WEB_PATH), str_replace("\\", '/', realpath($submitGroupWorkUrl)));
			$form->addElement('hidden', 'newWorkUrl', $submitGroupWorkUrl);
			$text_document = & $form->addElement('text', 'document', get_lang('Document'));
			$defaults['document'] = '<a href="' . format_url($submitGroupWorkUrl) . '">' . $realUrl . '</a>';
			$text_document->freeze();
		} elseif ($item_id && ($is_allowed_to_edit or $is_author)) {
			$workUrl = $currentCourseRepositoryWeb . $workUrl;
			
            $form->addElement('html', $html);
		} else {
			// else standard upload option
			$form->addElement('file', 'file', get_lang('UploadADocument'), 'size="40" onchange="updateDocumentTitle(this.value)"');
			$show_progress_bar = true;
		}		
		$form->addElement('hidden', 'id', $item_id);
		if (empty($item_id)) {
			$form->addElement('checkbox', 'contains_file', null, get_lang('ContainsAfile'), array('id'=>'contains_file_id'));
		}
		$form->addElement('text', 'title', get_lang('Title'), 'id="file_upload"  style="width: 350px;"');
		$form->addElement('textarea', 'description', get_lang("Description"), 'style="width: 350px; height: 60px;"');
		
		if ($item_id && !empty($work_item)) {
			$defaults['title'] 			= $work_item['title'];
			$defaults["description"] 	= $work_item['description'];
			$defaults['qualification']  = $work_item['qualification'];			
		}
	
		if ($is_allowed_to_edit && !empty($item_id)) {
			// Get qualification from parent_id that'll allow the validation qualification over
			$sql = "SELECT qualification FROM $work_table WHERE c_id = $course_id AND id ='$parent_id' ";
			$result = Database::query($sql);
			$row = Database::fetch_array($result);			
			$form->addElement('text', 'qualification', get_lang('Qualification'), 'size="10"');
			$form->addElement('html', '<div class="row"><div class="formw">'.get_lang('QualificationNumeric').'&nbsp;:&nbsp;'.$row['qualification'].'</div></div>');
			$form->addElement('hidden', 'qualification_over', $row['qualification']);
		}
	
		
		$form->addElement('hidden', 'active',   1);
		$form->addElement('hidden', 'accepted', 1);
		$form->addElement('hidden', 'item_to_edit', $item_id);
		$form->addElement('hidden', 'sec_token', $stok);
		
		if ($item_id) {
			$text = get_lang('UpdateWork');
			$class = 'save';
		} else {
			$text = get_lang('Send');
			$class = 'upload';
		}
	
		// fix the Ok button when we see the tool in the learn path
		if ($origin == 'learnpath') {
			$form->addElement('html', '<div style="margin-left:137px">');			
			$form->addElement('style_submit_button', 'submitWork', $text, array('class'=> $class, 'value' => "submitWork"));
			$form->addElement('html', '</div>');
		} else {
			if ($item_id) {
				$form->addElement('style_submit_button', 'editWork', $text, array('class'=> $class, 'value' => "editWork"));
			} else {
				$form->addElement('style_submit_button', 'submitWork', $text, array('class'=> $class, 'value' => "submitWork"));				
			}			
		}
	
		if (!empty($_POST['submitWork']) || $item_id) {
			$form->addElement('style_submit_button', 'cancelForm', get_lang('Cancel'), 'class="cancel"');
		}
	
		if ($show_progress_bar) {
			$form->add_real_progress_bar('uploadWork', 'file');
		}
		$form->setDefaults($defaults);
		//fixes bug when showing modification form
		if (empty($item_id) || (!empty($item_id) && ($is_allowed_to_edit or $is_author))) {
			$form->display();
		}		
		break;
		
		//download of an completed folder
	case 'downloadfolder':
		//require 'downloadfolder.inc.php';
		break;
		
	case 'send_mail': 
		if ($_GET['sec_token'] == $_SESSION['token']) {
			send_reminder_users_without_publication($my_folder_data);
			unset($_SESSION['token']);
		}
		break;
		
	case 'settings':
		//if posts
		if (!empty($_POST['changeProperties'])) {
			// changing the tool setting: default visibility of an uploaded document
			$query = "UPDATE " . $main_course_table . " SET show_score='" . $uploadvisibledisabled . "' WHERE code='" . api_get_course_id() . "'";
			Database::query($query);
		
			// changing the tool setting: is a student allowed to delete his/her own document
			// database table definition
			$table_course_setting = Database :: get_course_table(TOOL_COURSE_SETTING);
		
			// counting the number of occurrences of this setting (if 0 => add, if 1 => update)
			$query = "SELECT * FROM " . $table_course_setting . " WHERE c_id = $course_id AND variable = 'student_delete_own_publication'";
			$result = Database::query($query);
			$number_of_setting = Database::num_rows($result);
		
			if ($number_of_setting == 1) {
				$query = "UPDATE " . $table_course_setting . " SET value='" . Database::escape_string($_POST['student_delete_own_publication']) . "'
						WHERE variable='student_delete_own_publication' AND c_id = $course_id";
				Database::query($query);
			} else {
				$query = "INSERT INTO " . $table_course_setting . " (c_id, variable, value, category) VALUES
				($course_id, 'student_delete_own_publication','" . Database::escape_string($_POST['student_delete_own_publication']) . "','work')";
				Database::query($query);
			}
			Display::display_confirmation_message(get_lang('Saved'));
			//$_course['show_score'] = $uploadvisibledisabled;
		} else {
			$query = "SELECT * FROM " . $main_course_table . " WHERE code=\"" . $_course['sysCode'] . "\"";
			$result = Database::query($query);
			$row = Database::fetch_array($result);
			//$uploadvisibledisabled = $row["show_score"];
		}
		
		/*	Display of tool options */
		display_tool_options($uploadvisibledisabled, $origin);		
		break;		
	case 'create_dir':				
		//show them the form for the directory name
		if ($is_allowed_to_edit) {
			//create the form that asks for the directory name
			$new_folder_text = '<form name="form1" method="POST">';
			$new_folder_text .= '<div class="row"><div class="form_header">'.get_lang('CreateAssignment').'</div></div>';
			$new_folder_text .= '<input type="hidden" name="action" value="add"/>';
			$new_folder_text .= '<input type="hidden" name="curdirpath" value="' . Security :: remove_XSS($curdirpath) . '"/>';
			$new_folder_text .= '<input type="hidden" name="sec_token" value="'.$stok.'" />';
			$new_folder_text .= '<div class="row">
										<div class="label">
											<span class="form_required">*</span> '.get_lang('AssignmentName').'
										</div>
										<div class="formw">
											<div id="msg_error1" style="display:none;color:red"></div>
											<input type="text" id="work_title" name="new_dir" onfocus="document.getElementById(\'msg_error1\').style.display=\'none\';"/>
										</div>
									</div>';
		
			//new additional fields inside the "if condition" just to agroup
			$new_folder_text .= '<div class="row">
										<div class="label">
											'.get_lang('Description').'
										</div>
										<div class="formw">';
			$oFCKeditor = new FCKeditor('description') ;
			$oFCKeditor->ToolbarSet = 'work';
			$oFCKeditor->Width		= '100%';
			$oFCKeditor->Height		= '200';
			$oFCKeditor->Value		= isset($message) ? $message : null;
			$return =	$oFCKeditor->CreateHtml();
			$new_folder_text .= $return;
			$new_folder_text .= '</div>
									</div>';
		
			// Advanced parameters
			$addtext  = '<div id="options" style="display: none;">';
			$addtext .= '<div style="padding:10px">';
			$addtext .= '<b>'.get_lang('QualificationOfAssignment').'</b>';
			$addtext .= '<table cellspacing="0" cellpading="0" border="0"><tr>';
			$addtext .= '<td colspan="2">&nbsp;&nbsp;'.get_lang('QualificationNumeric').'&nbsp;';
			$addtext .= '<input type="text" name="qualification_value" value="" size="5"/></td><tr><td colspan="2">';
			$addtext .= '<input type="checkbox" value="1" name="make_calification" onclick="javascript: if(this.checked){document.getElementById(\'option1\').style.display=\'block\';}else{document.getElementById(\'option1\').style.display=\'none\';}"/>'.get_lang('MakeQualifiable').'</td></tr><tr>';
			$addtext .= '<td colspan="2"><div id="option1" style="display:none">';
			$addtext .= '<div id="msg_error_weight" style="display:none;color:red"></div>';
			$addtext .=	'&nbsp;&nbsp;'.get_lang('WeightInTheGradebook').'&nbsp;';
			$addtext .= '<input type="text" name="weight" value="" size="5" onfocus="document.getElementById(\'msg_error_weight\').style.display=\'none\';"/></div></td></tr>';
			$addtext .= '</tr></table>';
			$addtext .= '<br />';
			$addtext .= '<b>'.get_lang('DatesAvailables').'</b><br />';
			$addtext .= '<input type="checkbox" value="1" name="type1" onclick="javascript: if(this.checked){document.getElementById(\'option2\').style.display=\'block\';}else{document.getElementById(\'option2\').style.display=\'none\';}"/>'.get_lang('EnableExpiryDate').'';
			$addtext .= '&nbsp;&nbsp;&nbsp;<span id="msg_error2" style="display:none;color:red"></span>';
			$addtext .= '&nbsp;&nbsp;&nbsp;<span id="msg_error3" style="display:none;color:red"></span>';
			$addtext .= '<div id="option2" style="padding:4px;display:none">&nbsp;&nbsp;';
			$addtext .= draw_date_picker('expires').'</div>';
			$addtext .= '<br /><input type="checkbox" value="1" name="type2" onclick="javascript: if(this.checked){document.getElementById(\'option3\').style.display=\'block\';}else{document.getElementById(\'option3\').style.display=\'none\';}"/>'.get_lang('EnableEndDate').'';
			$addtext .= '<div id="option3" style="padding:4px;display:none">';
			$addtext .= '&nbsp;&nbsp;&nbsp;<div id="msg_error4" style="display:none;color:red"></div>';
			$addtext .= draw_date_picker('ends').'<br />';
			$addtext .= '</div>';
			$addtext .= '<br /><br /><b>'.get_lang('Agenda').'</b><br />';
			$addtext .= '&nbsp;&nbsp;'.make_checkbox('add_to_calendar', false, get_lang('AddToCalendar'));			
			
			$addtext .= '<br /><br /><b>'.get_lang('AllowTextAssignments').'</b><br />';
			$addtext .= '&nbsp;&nbsp;'.make_checkbox('allow_text_assignment', false, get_lang('AllowTextAssignments')).'</div>';						
			$addtext .= '</div>';		
						
			$new_folder_text .= '<div class="row">
										<div class="label"></div>
										<div class="formw"><a href="javascript: void(0);" onclick="javascript: return plus();"><span id="plus">'.Display::return_icon('div_show.gif',get_lang('AdvancedParameters'), array('style' => 'vertical-align:center')).' '.get_lang('AdvancedParameters').'</span></a><br />
											'.$addtext.'
										</div>
									</div>';
			$new_folder_text .= '<div class="row">
										<div class="label"></div>
										<div class="formw">
											<button type="button" class="add" name="create_dir" onClick="javascript: validate();" value="' . addslashes(get_lang('CreateDirectory')) . '"/>' . addslashes(get_lang('ButtonCreateAssignment')) . '</button>
										</div>
									</div>';
			$new_folder_text .= '</form>';
			//show the form
			echo $new_folder_text;
		}
		break;
	case 'add':
		if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
			api_not_allowed();
		}
		
		$fexpire 		= get_date_from_select('expires');
		$fend 	 		= get_date_from_select('ends');
		
		$directory 		= Security::remove_XSS($_POST['new_dir']);
		$directory 		= replace_dangerous_char($directory);
		$directory 		= disable_dangerous_file($directory);
		$dir_name 		= $curdirpath.$directory;
		$created_dir 	= create_unexisting_work_directory($base_work_dir, $dir_name);
	
		// we insert here the directory in the table $work_table
		$dir_name_sql = '';
	
		if ($ctok == $_POST['sec_token']) {
			
			if (!empty($created_dir)) {
				if ($curdirpath == '/') {
					$dir_name_sql = $created_dir;
				} else {
					$dir_name_sql = '/'.$created_dir;
				}
				$time = time();
				$today = api_get_utc_datetime($time);
	
				$sql_add_publication = "INSERT INTO " . $work_table . " SET
										   c_id				= $course_id,  
										   url         		= '".Database::escape_string($dir_name_sql)."',
									       title        	= '".Database::escape_string($_POST['new_dir'])."',
						                   description 		= '".Database::escape_string($_POST['description'])."',
						                   author      		= '',
										   active			= '0',
										   accepted			= '1',
										   filetype 		= 'folder',
										   post_group_id 	= '".$toolgroup."',
										   sent_date		= '".$today."',
										   qualification	= '".(($_POST['qualification_value']!='') ? Database::escape_string($_POST['qualification_value']) : '') ."',
										   parent_id		= '',
										   qualificator_id	= '',
										   date_of_qualification	= '0000-00-00 00:00:00',
										   weight   		= '".Database::escape_string($_POST['weight'])."',
										   session_id   	= '".intval($id_session)."',
										   allow_text_assignment   = '".Database::escape_string($_POST['allow_text_assignment'])."',
										   contains_file    = 0, 
										   user_id 			= '".$user_id."'";
	
				Database::query($sql_add_publication);
	
				// add the directory
				$id = Database::insert_id();
				if ($id) {
					// Insert into agenda
					$agenda_id = 0;
					$end_date = '';
					if (isset($_POST['add_to_calendar']) && $_POST['add_to_calendar'] == 1) {
						require_once api_get_path(SYS_CODE_PATH).'calendar/agenda.inc.php';
						require_once api_get_path(SYS_CODE_PATH).'resourcelinker/resourcelinker.inc.php';						
							
						// Setting today date
						$date = $end_date = $time;
							
						$title = sprintf(get_lang('HandingOverOfTaskX'), $_POST['new_dir']);
						if (!empty($_POST['type1'])) {
							$end_date = get_date_from_select('expires');
							$date	  = $end_date;
						}
						$description = isset($_POST['description']) ? $_POST['description'] : '';
						$content = '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;curdirpath='.api_substr($dir_name_sql, 1).'" >'.$_POST['new_dir'].'</a>'.$description;
	
						$agenda_id = agenda_add_item($course_info, $title, $content, $date, $end_date, array('GROUP:'.$toolgroup), 0);
					}
				}
					
				//Folder created
				api_item_property_update($course_info, 'work', $id, 'DirectoryCreated', $user_id);
				Display :: display_confirmation_message(get_lang('DirectoryCreated'), false);
				
				// insert into student_publication_assignment	
				//return something like this: 2008-02-45 00:00:00
	
				$enable_calification = isset($_POST['qualification_value']) ? 1 : 0;
				
				if (!empty($_POST['type1']) || !empty($_POST['type2'])) {
					$sql_add_homework = "INSERT INTO $TSTDPUBASG SET
											c_id = $course_id ,
	    								    expires_on       		= '".((isset($_POST['type1']) && $_POST['type1']==1) ? api_get_utc_datetime(get_date_from_select('expires')) : '0000-00-00 00:00:00'). "',
	    							        ends_on        	 		= '".((isset($_POST['type2']) && $_POST['type2']==1) ? api_get_utc_datetime(get_date_from_select('ends')) : '0000-00-00 00:00:00')."',
	    				                    add_to_calendar  		= '$agenda_id',
	    				                    enable_qualification 	= '".$enable_calification."',
	    				                    publication_id 			= '".$id."'";
					Database::query($sql_add_homework);	
					$sql_add_publication = "UPDATE ".$work_table." SET "."has_properties  = ".Database::insert_id().", view_properties = 1 ".' where id = '.$id;
					Database::query($sql_add_publication);
				} else {
					$sql_add_homework = "INSERT INTO $TSTDPUBASG SET
											c_id = $course_id ,
	    								    expires_on     = '0000-00-00 00:00:00',
	    							        ends_on        = '0000-00-00 00:00:00',
	    				                    add_to_calendar  = '$agenda_id',
	    				                    enable_qualification = '".$enable_calification."',
	    				                    publication_id = '".$id."'";
					Database::query($sql_add_homework);
	                $inserted_id = Database::insert_id();
					$sql_add_publication = "UPDATE $work_table SET has_properties  = $inserted_id, view_properties = 0 WHERE id = $id";
					Database::query($sql_add_publication);	
				}
	
				if (isset($_POST['make_calification']) && $_POST['make_calification'] == 1) {
	
					require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/gradebookitem.class.php';
					require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/evaluation.class.php';
					require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/abstractlink.class.php';
					require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
	
					//$resource_name = (empty($_POST['qualification_name'])) ? $_POST['new_dir'] : $_POST['qualification_name'];
					$resource_name = $_POST['new_dir'];
					add_resource_to_course_gradebook(api_get_course_id(), 3, $id, $resource_name, $_POST['weight'], $_POST['qualification_value'], $_POST['description'], time(), 1, api_get_session_id());
				}	
				// end features
	
				if (api_get_course_setting('email_alert_students_on_new_homework') == 1) {
					send_email_on_homework_creation(api_get_course_id());
				}
			} else {
				Display :: display_error_message(get_lang('CannotCreateDir'));
			}
		}
	case 'upload':
		if (isset($_POST['sec_token']) && $ctok == $_POST['sec_token']) {
			
			//check the token inserted into the form
			if (isset($_POST['submitWork']) && !empty($is_course_member)) {			
				$authors = api_get_person_name($currentUserFirstName, $currentUserLastName);
				$url = null;
				if ($_POST['contains_file'] && !empty($_FILES['file']['size'])) {					
					$updir = $currentCourseRepositorySys . 'work/'; //directory path to upload
		
					// Try to add an extension to the file if it has'nt one
					$new_file_name = add_ext_on_mime(stripslashes($_FILES['file']['name']), $_FILES['file']['type']);
		
					// Replace dangerous characters
					$new_file_name = replace_dangerous_char($new_file_name, 'strict');
		
					// Transform any .php file in .phps fo security
					$new_file_name = php2phps($new_file_name);
						
					$filesize = filesize($_FILES['file']['tmp_name']);
						
					if (empty($filesize)) {
						Display :: display_error_message(get_lang('UplUploadFailedSizeIsZero'));
						$succeed = false;
					} elseif (!filter_extension($new_file_name)) {
						//filter extension
						Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
						$succeed = false;
					}
					if (!$title) {
						$title = $_FILES['file']['name'];
					}
					
					// Compose a unique file name to avoid any conflict
					$new_file_name = api_get_unique_id();
					if (isset ($_SESSION['toolgroup'])) {
						$post_group_id = $_SESSION['toolgroup'];
					} else {
						$post_group_id = '0';
					}
					
					//if we come from the group tools the groupid will be saved in $work_table
					@move_uploaded_file($_FILES['file']['tmp_name'], $updir.$curdirpath.'/'.$new_file_name);
					$url = 'work'.$curdirpath.'/'.$new_file_name;
				}
				
				if (empty($title)) {
					$title = get_lang('Untitled');
				}
				
				$active = '1';
				$sql_add_publication = "INSERT INTO " . $work_table . " SET
										   c_id 		= $course_id ,
									       url         	= '" . $url . "',
									       title       	= '" . Database::escape_string($title) . "',
						                   description	= '" . Database::escape_string($description) . "',
						                   author      	= '" . Database::escape_string($authors) . "',
						                   contains_file = '".intval($_POST['contains_file'])."',  
										   active		= '" . $active . "',
										   accepted		= '1',
										   post_group_id = '".$post_group_id."',
										   sent_date	=  '".api_get_utc_datetime()."',
										   parent_id 	=  '".$work_id."' ,
                                           session_id	= '".intval($id_session)."' ,
                                           user_id 		= '".$user_id."'";
				//var_dump($sql_add_publication);
				Database::query($sql_add_publication);
				$id = Database::insert_id();				
				if ($id) {				
					api_item_property_update($_course, 'work', $id, 'DocumentAdded', $user_id);
					$succeed = true;
				}
				
				// update all the parents in the table item propery
				$list_id = get_parent_directories($my_cur_dir_path);
				for ($i = 0; $i < count($list_id); $i++) {
					api_item_property_update($_course, 'work', $list_id[$i], 'FolderUpdated', $user_id);
				}														
			} elseif ($newWorkUrl) {
		
				if (isset ($_SESSION['toolgroup'])) {
					$post_group_id = $_SESSION['toolgroup'];
				} else {
					$post_group_id = '0';
				}
	
				// SPECIAL CASE ! For a work coming from another area (i.e. groups)
	
				$url = str_replace('../../' . $_course['path'] . '/', '', $newWorkUrl);
	
				if (!$title) {
					$title = basename($workUrl);
				}	
				$sql = "INSERT INTO  " . $work_table . " SET
									c_id = $course_id,
									url        	= '" . $url . "',
						            title       	= '" . Database::escape_string($title) . "',
						            description 	= '" . Database::escape_string($description) . "',
						            author      	= '" . Database::escape_string($authors) . "',
								    post_group_id   = '".$post_group_id."',
						            sent_date    	= '".api_get_utc_datetime()."',
						            session_id 		= '".intval($id_session)."',
						            user_id 		= '".$user_id."'";
	
				Database::query($sql);
	
				$insertId = Database::insert_id();
				api_item_property_update($_course, 'work', $insertId, 'DocumentAdded', $user_id);
				$succeed = true;
	
				/*// update all the parents in the table item propery
				 $list_id=get_parent_directories($my_cur_dir_path);
				for ($i = 0; $i < count($list_id); $i++) {
				api_item_property_update($_course, 'work', $list_id[$i], 'FolderUpdated', $user_id);
				}*/
			} elseif (isset($_POST['editWork'])) {
				
				/*
				 * SPECIAL CASE ! For a work edited
				*/
					
				//Get the author ID for that document from the item_property table
				$is_author 			= false;
				$item_to_edit_id 	= intval($_POST['item_to_edit']);
				$item_to_edit_data 	= api_get_item_property_info(api_get_course_int_id(), 'work', $item_to_edit_id);
					
				if ($is_allowed_to_edit) {
					$is_author = true;
				} else {
					if ($item_to_edit_data['insert_user_id'] == api_get_user_id()) {
						$is_author = true;
					}
				}
					
				if ($is_author) {

					$work_data = get_work_data_by_id($item_to_edit_id);
					if (!empty($_POST['title']))
					$title 		 = isset($_POST['title']) ? $_POST['title'] : $work_data['title'];
					$description = isset($_POST['description']) ? $_POST['description'] : $work_data['description'];
					
	
					if ($is_allowed_to_edit && ($_POST['qualification'] !='' )) {
						$add_to_update = ', qualificator_id ='."'".api_get_user_id()."',";
						$add_to_update .= ' qualification ='."'".Database::escape_string($_POST['qualification'])."',";
						$add_to_update .= ' date_of_qualification ='."'".api_get_utc_datetime()."'";
					}
	
					if ((int)$_POST['qualification'] > (int)$_POST['qualification_over']) {
						Display::display_error_message(get_lang('QualificationMustNotBeMoreThanQualificationOver'));
					} else {
						$sql = "UPDATE  " . $work_table . "
						        SET	title       = '" . Database::escape_string($title) . "',
						            description = '" . Database::escape_string($description) . "'
						            ".$add_to_update."
						        WHERE c_id = $course_id AND id = $item_to_edit_id";					
						Database::query($sql);
					}
					api_item_property_update($_course, 'work', $item_to_edit_id, 'DocumentUpdated', $user_id);
					$succeed = true;
				} else {
					$error_message = get_lang('IsNotPosibleSaveTheDocument');
				}
			}
		}
						
		if (!empty($_POST['submitWork']) && !empty($succeed) && !$id) {
			//last value is to check this is not "just" an edit
			//YW Tis part serve to send a e-mail to the tutors when a new file is sent
			$send = api_get_course_setting('email_alert_manager_on_new_doc');
			
			if ($send > 0) {
				// Lets predefine some variables. Be sure to change the from address!
				
				$emailto = array ();
				if (empty ($id_session)) {
					$sql_resp = 'SELECT u.email as myemail FROM ' . $table_course_user . ' cu, ' . $table_user . ' u 
								 WHERE cu.course_code = ' . "'" . api_get_course_id() . "'" . ' AND cu.status = 1 AND u.user_id = cu.user_id';
					$res_resp = Database::query($sql_resp);
					while ($row_email = Database :: fetch_array($res_resp)) {
						if (!empty ($row_email['myemail'])) {
						$emailto[$row_email['myemail']] = $row_email['myemail'];
						}
					}
				} else {					
					// coachs of the session
					$sql_resp = 'SELECT user.email as myemail
										FROM ' . $table_session . ' session INNER JOIN ' . $table_user . ' user
										ON user.user_id = session.id_coach
										WHERE session.id = ' . intval($id_session);
					$res_resp = Database::query($sql_resp);
					while ($row_email = Database :: fetch_array($res_resp)) {
						if (!empty ($row_email['myemail'])) {
							$emailto[$row_email['myemail']] = $row_email['myemail'];
						}
					}
			
					//coach of the course
					$sql_resp = 'SELECT user.email as myemail
								FROM ' . $table_session_course_user . ' scu
													INNER JOIN ' . $table_user . ' user
														ON user.user_id = scu.id_user AND scu.status=2
													WHERE scu.id_session = ' . intval($id_session);
					$res_resp = Database::query($sql_resp);
					while ($row_email = Database :: fetch_array($res_resp)) {
						if (!empty ($row_email['myemail'])) {
							$emailto[$row_email['myemail']] = $row_email['myemail'];
						}
					}
				}
			
				if (count($emailto) > 0) {
			
					$emailto = implode(',', $emailto);
					$emailfromaddr = api_get_setting('emailAdministrator');
					$emailfromname = api_get_setting('siteName');
					$emailsubject = "[" . api_get_setting('siteName') . "] ";
					$sender_name = api_get_setting('administratorName').' '.api_get_setting('administratorSurname');
					$email_admin = api_get_setting('emailAdministrator');
							// The body can be as long as you wish, and any combination of text and variables
				
					$emailbody = get_lang('SendMailBody')."\n".get_lang('CourseName')." : ".$_course['name']."\n";
					$emailbody .= get_lang('WorkName')." : ".substr($my_cur_dir_path, 0, -1)."\n";
					$emailbody .= get_lang('UserName')." : ".$currentUserFirstName .' '.$currentUserLastName ."\n";
					$emailbody .= get_lang('DateSent')." : ".api_format_date(api_get_local_time())."\n";
					$emailbody .= get_lang('FileName')." : ".$title."\n\n".get_lang('DownloadLink')."\n";
					$emailbody .= api_get_path(WEB_CODE_PATH)."work/work.php?".api_get_cidreq()."&amp;curdirpath=".$my_cur_dir_path."\n\n" . api_get_setting('administratorName') . " " . api_get_setting('administratorSurname') . "\n" . get_lang('Manager') . " " . api_get_setting('siteName') . "\n" . get_lang('Email') . " : " . api_get_setting('emailAdministrator');
							// Here we are forming one large header line
					// Every header must be followed by a \n except the last
					@api_mail('', $emailto, $emailsubject, $emailbody, $sender_name,$email_admin);
				
					$emailbody_user = get_lang('Dear')." ".$currentUserFirstName .' '.$currentUserLastName ."\n";
					$emailbody_user .= get_lang('MessageConfirmSendingOfTask')."\n".get_lang('CourseName')." : ".$_course['name']."\n";
					$emailbody_user .= get_lang('WorkName')." : ".substr($my_cur_dir_path, 0, -1)."\n";
					$emailbody_user .= get_lang('DateSent')." : ".api_format_date(api_get_local_time())."\n";
					$emailbody_user .= get_lang('FileName')." : ".$title."\n\n".api_get_setting('administratorName')." ".api_get_setting('administratorSurname') . "\n" . get_lang('Manager') . " " . api_get_setting('siteName') . "\n" . get_lang('Email') . " : " . api_get_setting('emailAdministrator');;
				
					//Mail to user
					@api_mail('', $currentUserEmail, $emailsubject, $emailbody_user, $sender_name,$email_admin);
				}
			}
			$message = get_lang('DocAdd');
			//stats
			if (!$Id) {
				$Id = $insertId;
			}
			event_upload($Id);
			$submit_success_message = $message . "<br />";
			Display :: display_confirmation_message($submit_success_message, false);
		}
	case 'make_visible':
	case 'make_invisible':
	case 'mark_work':
	case 'move':
	case 'move_to':
	case 'list':
		if ($action == 'mark_work') {
			
		}
		/*	Move file command */
		
		if ($action == 'move_to') {
			
			$move_to_path = get_work_path($_REQUEST['move_to_id']);
		
			if ($move_to_path==-1) {
				$move_to_path = '/';
			} elseif (substr($move_to_path, -1, 1) != '/') {
				$move_to_path = $move_to_path .'/';
			}
			
			//security fix: make sure they can't move files that are not in the document table			
			if ($path = get_work_path($item_id)) {				
				if (move($course_dir . '/' . $path, $base_work_dir . $move_to_path)) {
					//update db		
					update_work_url($item_id, 'work' . $move_to_path, $move_to);
					// update all the parents in the table item propery
					$list_id = get_parent_directories($move_to_path);
					for ($i = 0; $i < count($list_id); $i++) {
						api_item_property_update($_course, 'work', $list_id[$i], 'FolderUpdated', $user_id);
					}		
					Display :: display_confirmation_message(get_lang('DirMv'));
				} else {
					Display :: display_error_message(get_lang('Impossible'));
				}
			} else {
				Display :: display_error_message(get_lang('Impossible'));
			}
		}
		

		/*	Move file form request */
		if ($action == 'move') {
			if (!empty($item_id)) {
				$folders = array();
				$session_id = api_get_session_id();
				$session_id == 0 ? $withsession = " AND session_id = 0 " : $withsession = " AND session_id='".$session_id."'";
			
				$sql = "SELECT id, url FROM $work_table WHERE url LIKE '/%' AND post_group_id = '".(empty($_SESSION['toolgroup'])?0:intval($_SESSION['toolgroup']))."'".$withsession;
				$res = Database::query($sql);
				while($folder = Database::fetch_array($res)) {
					$folders[$folder['id']] = substr($folder['url'], 1, strlen($folder['url']) - 1);
				}
				echo build_work_move_to_selector($folders, $curdirpath, $item_id);
			}
		}
		
		/*	MAKE VISIBLE WORK COMMAND */
		if ($action == 'make_visible') {
			if (!empty($item_id)) {
				if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
					api_not_allowed();
				}
					
				if (isset($item_id) && $item_id == 'all') {
					//never happens
					/*
					$sql = "ALTER TABLE  " . $work_table . " CHANGE accepted accepted TINYINT(1) DEFAULT '1'";
					Database::query($sql);
					$sql = "UPDATE  " . $work_table . " SET accepted = 1";
					Database::query($sql);
					Display::display_confirmation_message(get_lang('AllFilesVisible'));*/
				} else {
					$sql = "UPDATE " . $work_table . "	SET accepted = 1 WHERE c_id = $course_id AND id = '" . $item_id . "'";
					Database::query($sql);                    
                    api_item_property_update($course_info, 'work', $item_id, 'visible', api_get_user_id());
					Display::display_confirmation_message(get_lang('FileVisible'));
				}
			}
		}	
		
		if ($action == 'make_invisible') {
		
			/*	MAKE INVISIBLE WORK COMMAND */
			if (!empty($item_id)) {
				if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
					api_not_allowed();
				}
				if (isset($item_id) && $item_id == 'all') {
					/*
					$sql = "ALTER TABLE " . $work_table . "
						CHANGE accepted accepted TINYINT(1) DEFAULT '0'";
					Database::query($sql);
					$sql = "UPDATE  " . $work_table . " SET accepted = 0";
					Database::query($sql);
					Display::display_confirmation_message(get_lang('AllFilesInvisible'));*/
				} else {
					$sql = "UPDATE  " . $work_table . " SET accepted = 0
							WHERE c_id = $course_id AND id = '" . $item_id . "'";
					Database::query($sql);
                    api_item_property_update($course_info, 'work', $item_id, 'invisible', api_get_user_id());
					Display::display_confirmation_message(get_lang('FileInvisible'));
				}
			}
		}
		
		
		/*	Delete dir command */
		
		if (!empty($_REQUEST['delete_dir'])) {
		
			if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
				api_not_allowed();
			}		
			$delete_directory	= $_REQUEST['delete_dir'];
			$id					= $_REQUEST['delete2'];
			del_dir($base_work_dir . '/', $delete_directory, $id);		
			Display :: display_confirmation_message(get_lang('DirDeleted') . ': '.$delete_directory);
		}
		
		if (!empty($_REQUEST['delete2'])) {		
			if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
				api_not_allowed();
			}
			$delete_2 = intval($_REQUEST['delete2']);
			// gets calendar_id from student_publication_assigment
			$sql = "SELECT add_to_calendar FROM $TSTDPUBASG WHERE c_id = $course_id AND publication_id ='$delete_2'";
			$res = Database::query($sql);
			$calendar_id = Database::fetch_row($res);
			// delete from agenda if it exists
			if (!empty($calendar_id[0])) {
				$t_agenda   = Database::get_course_table(TABLE_AGENDA);
				$sql = "DELETE FROM $t_agenda WHERE c_id = $course_id AND id ='".$calendar_id[0]."'";
				Database::query($sql);
			}
			$sql2 = "DELETE FROM $TSTDPUBASG WHERE c_id = $course_id AND publication_id ='$delete_2'";
			$result2 = Database::query($sql2);
		
			$link_id = is_resource_in_course_gradebook(api_get_course_id(), 3 , $delete_2, api_get_session_id());
			if ($link_id !== false) {
				remove_resource_from_course_gradebook($link_id);
			}
		}
		
		/*	DELETE WORK COMMAND */
		
		if ($delete) {
			if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
				api_not_allowed();
			}
			
			if ($delete == 'all' && api_is_allowed_to_edit(null, true)) {
				
				//we can't delete all documents

				/*
				$path = $currentCourseRepositorySys;
				$t_agenda   = Database::get_course_table(TABLE_AGENDA);
		
				$sql = "SELECT id, url, filetype FROM  ".$work_table." WHERE c_id = $course_id AND session_id = ".api_get_session_id().' ORDER BY url DESC'; // do not change the "order by", otherwise the work assignments will not be renamed
				$result = Database::query($sql);
		
				while($row = Database::fetch_array($result)) {
					$url = $row['url'];
					//Deleting works
					$delete_query = "DELETE FROM  ".$work_table." WHERE c_id = $course_id AND id  = ".$row['id'];
					Database::query($delete_query);
		
					//Deleting agenda calendar for that work assignment
					$sql_agenda = "SELECT add_to_calendar FROM  ".$TSTDPUBASG." WHERE c_id = $course_id AND publication_id = ".$row['id'];
		
					$rs_agenda = Database::query($sql_agenda);
					while ($row_agenda = Database::fetch_array($rs_agenda)) {
						if (!empty($row_agenda['add_to_calendar'])) {
							$delete_agenda = "DELETE FROM  ".$t_agenda." WHERE c_id = $course_id AND id = ".$row_agenda['add_to_calendar'];
							Database::query($delete_agenda);
						}
					}
					//Deleting the work assignment
					$delete_query = "DELETE FROM  ".$TSTDPUBASG. " WHERE c_id = $course_id AND publication_id = ".$row['id'];
					Database::query($delete_query);
		
					if ($row['filetype'] == 'folder') {
						$url = 'work'.$url;
					}
		
					if (api_get_setting('permanently_remove_deleted_files') == 'true') {
						if (file_exists($path.$url)) {
							rmdirr($path.$url);
						}
					} else {
						if ($row['filetype'] == 'folder') {
							$new_file = $path.'work/DELETED_'.basename($url);
						} else {
							$new_file = $path.dirname($url).'/DELETED_'.basename($url);
						}
						if (file_exists($path.$url)) {
							rename($path.$url, $new_file);
						}
					}
				}*/
			} else {
				
				$file_deleted = false;
				//Get the author ID for that document from the item_property table
				$author_sql = "SELECT * FROM $iprop_table WHERE c_id = $course_id AND tool = 'work' AND insert_user_id='$user_id' AND ref=" .Database::escape_string($delete);
				$author_qry = Database::query($author_sql);
		
				if ((Database :: num_rows($author_qry) == 1 AND api_get_course_setting('student_delete_own_publication') == 1) || api_is_allowed_to_edit(null,true)) {
					//we found the current user is the author
					$queryString1 	= "SELECT url, contains_file FROM  " . $work_table . "  WHERE c_id = $course_id AND id = $delete";
					$result1 		= Database::query($queryString1);
					$row 			= Database::fetch_array($result1);
		
					if (Database::num_rows($result1) > 0) {
						$queryString2 	= "UPDATE " . $work_table . "  SET active  = 2 WHERE c_id = $course_id AND id = $delete";
						$queryString3 	= "DELETE FROM  " . $TSTDPUBASG . "  WHERE c_id = $course_id AND publication_id = $delete";
						$result2 		= Database::query($queryString2);
						$result3 		= Database::query($queryString3);						 
						api_item_property_update($_course, 'work', $delete, 'DocumentDeleted', $user_id);
						$work = $row['url'];
						
						if ($row['contains_file'] == 1) {
							if (!empty($work)) {
								if (api_get_setting('permanently_remove_deleted_files') == 'true') {
									
									my_delete($currentCourseRepositorySys.'/'.$work);
									Display::display_confirmation_message(get_lang('TheDocumentHasBeenDeleted'));
									$file_deleted = true;
								} else {
									require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
									$extension = pathinfo($work, PATHINFO_EXTENSION);
									$basename_file = basename($work, '.'.$extension);
									$new_dir = $work.'_DELETED_'.$delete.'.'.$extension;
									
									if (file_exists($currentCourseRepositorySys.'/'.$work)) {
										rename($currentCourseRepositorySys.'/'.$work, $currentCourseRepositorySys.'/'.$new_dir);
										Display::display_confirmation_message(get_lang('TheDocumentHasBeenDeleted'));
										$file_deleted = true;
									}
								}
							}
						} else {
							$file_deleted = true;
						}
					}
					if (!$file_deleted) {
						Display::display_error_message(get_lang('YouAreNotAllowedToDeleteThisDocument'));
					}
				} else {
					Display::display_error_message(get_lang('YouAreNotAllowedToDeleteThisDocument'));
				}
			}
		}		
		
		/*	Display list of student publications */
		
		if ($curdirpath == '/') {
			$my_cur_dir_path = '';
		} else {
			$my_cur_dir_path = $curdirpath;
		}
		
		$add_query = '';
		//Getting if I'm a teacher
		$sql = "SELECT user.firstname, user.lastname FROM $table_user user, $table_course_user course_user
				WHERE course_user.user_id=user.user_id AND course_user.course_code='".api_get_course_id()."' AND course_user.status='1'";
		$res = Database::query($sql);
		$admin_course = '';
		while ($row = Database::fetch_row($res)) {
			$admin_course .='\''.api_get_person_name($row[0], $row[1]).'\',';
		}
		
		//If I'm student & I'm in a special work and check the work setting: "New documents are visible for all users"		
		if (!$is_allowed_to_edit && $is_special && $uploadvisibledisabled == 1) {
			$add_query = ' AND author IN('.$admin_course.'\''.api_get_person_name($_user['firstName'], $_user['lastName']).'\')';
		}
		if ($is_allowed_to_edit && $is_special) {
		
			if (!empty($_REQUEST['filter'])) {
				switch($_REQUEST['filter']) {
					case 1:
						$add_query = ' AND qualification = '."''";
						break;
					case 2:
						$add_query = ' AND qualification != '."''";
						break;
					case 3:
						$add_query = ' AND sent_date < '."'".$homework['expires_on']."'";
						break;
					default:
						$add_query = '';
				}
			}
			$cidreq = isset($_GET['cidreq']) ? Security::remove_XSS($_GET['cidreq']) : '';
			$curdirpath = isset($_REQUEST['curdirpath']) ? Security::remove_XSS($_REQUEST['curdirpath']) : '';
			$filter = isset($_REQUEST['filter']) ? (int)$_REQUEST['filter'] : '';
		
			if ($origin != 'learnpath') {
				$form_filter = '<form method="post" action="'.api_get_self().'?cidReq='.$cidreq.'&id='.$work_id.'&curdirpath='.$curdirpath.'&gradebook='.$gradebook.'">';
				$form_filter .= make_select('filter', array(0 => get_lang('SelectAFilter'), 1 => get_lang('FilterByNotRevised'), 2 => get_lang('FilterByRevised'), 3 => get_lang('FilterByNotExpired')), $filter).'&nbsp&nbsp';
				$form_filter .= '<button type="submit" class="save" value="'.get_lang('FilterAssignments').'">'.get_lang('FilterAssignments').'</button></form>';
				echo $form_filter;
			}
		}
		
		if (!empty($my_folder_data['description'])) {
			echo '<p><div><strong>'.get_lang('Description').':</strong><p>'.Security::remove_XSS($my_folder_data['description'], STUDENT).'</p></div></p>';
		}
        
		if ($display_list_users_without_publication) {
			display_list_users_without_publication($my_folder_data['id']);
		} else {			
			display_student_publications_list($work_id, $link_target_parameter, $dateFormatLong, $origin, $add_query);
		}
		break;	
}

if ($origin != 'learnpath') {
	//we are not in the learning path tool
	Display :: display_footer();
}