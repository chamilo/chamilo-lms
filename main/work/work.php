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

$language_file = array('exercice', 'work', 'document', 'admin', 'gradebook');

require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_STUDENTPUBLICATION;

/*	Configuration settings */

api_protect_course_script(true);

// Including necessary files
require_once 'work.lib.php';

require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';
include_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileDisplay.lib.php';

$course_id      = api_get_course_int_id();
$course_info    = api_get_course_info();
$user_id 	    = api_get_user_id();
$id_session     = api_get_session_id();

// Section (for the tabs)
$this_section = SECTION_COURSES;
$work_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$my_folder_data = get_work_data_by_id($work_id);

$curdirpath = '';
$htmlHeadXtra[] = api_get_jqgrid_js();

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
$TSTDPUBASG			= Database :: get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
$table_course_user	= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$table_user			= Database :: get_main_table(TABLE_MAIN_USER);
$table_session		= Database :: get_main_table(TABLE_MAIN_SESSION);
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

$description 	        = isset($_REQUEST['description']) ? Database::escape_string($_REQUEST['description']) : '';

$item_id 		        = isset($_REQUEST['item_id']) ? intval($_REQUEST['item_id']) : null;
$parent_id 		        = isset($_REQUEST['parent_id']) ? Database::escape_string($_REQUEST['parent_id']) : '';
$origin 		        = isset($_REQUEST['origin']) ? Security::remove_XSS($_REQUEST['origin']) : '';

$submitGroupWorkUrl     = isset($_REQUEST['submitGroupWorkUrl']) ? Security::remove_XSS($_REQUEST['submitGroupWorkUrl']) : '';
$title 			        = isset($_REQUEST['title']) ? Database::escape_string($_REQUEST['title']) : '';
$uploadvisibledisabled  = isset($_REQUEST['uploadvisibledisabled']) ? Database::escape_string($_REQUEST['uploadvisibledisabled']) : $course_info['show_score'];

// get data for publication assignment
$has_expired = false;
$has_ended   = false;

//directories management
$sys_course_path 	= api_get_path(SYS_COURSE_PATH);
$course_dir 		= $sys_course_path . $_course['path'];
$base_work_dir 		= $course_dir . '/work';

$link_target_parameter = ""; // e.g. "target=\"_blank\"";

$display_list_users_without_publication = isset($_GET['list']) && Security::remove_XSS($_GET['list']) == 'without' ? true : false;

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

//Download folder
if ($action == 'downloadfolder') {
	require 'downloadfolder.inc.php';
}

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

$group_id = api_get_group_id();
  
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

if (!empty($group_id)) {
	$group_properties  = GroupManager :: get_group_properties($group_id);    
    $show_work = false;
    
    if (api_is_allowed_to_edit(false, true)) {        
        $show_work = true;
    } else {
        // you are not a teacher              
        $show_work = GroupManager::user_has_access($user_id, $group_id, GROUP_TOOL_WORK);
    }
    
    if (!$show_work) {
        api_not_allowed();
    }
    
	$interbreadcrumb[] = array ('url' => '../group/group.php', 'name' => get_lang('Groups'));
	$interbreadcrumb[] = array ('url' => '../group/group_space.php?gidReq='.$group_id, 'name' => get_lang('GroupSpace').' '.$group_properties['name']);

	$url_dir ='';
	$interbreadcrumb[] = array ('url' =>'work.php?gidReq='.$group_id,'name' => get_lang('StudentPublications'));

	$url_dir = 'work.php?&id=' . $work_id;
	$interbreadcrumb[] = array ('url' => $url_dir,'name' =>  $my_folder_data['title']);	

	if ($action == 'upload_form') {
		$interbreadcrumb[] = array ('url' => 'work.php','name' => get_lang('UploadADocument'));
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
		
		if ($action == 'upload_form') {
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
		require Display::display_reduced_header();
	}
}


//stats
event_access_tool(TOOL_STUDENTPUBLICATION);

$is_allowed_to_edit = api_is_allowed_to_edit(); //has to come after display_tool_view_option();

$student_can_edit_in_session = api_is_allowed_to_session_edit(false, true);

Display :: display_introduction_section(TOOL_STUDENTPUBLICATION);

// introduction section

if ($origin == 'learnpath') {
	echo '<div style="height:15px">&nbsp;</div>';
}

/*	Display links to upload form and tool options */

if (!in_array($action, array('send_mail','add','create_dir','upload'))) {
    $token = Security::get_token();    
}

$show_tool_options = (in_array($action, array('list', 'add'))) ? true : false;

$display_upload_link = $action == 'upload_form' ? false : true;

if (!empty($my_folder_data)) {
	$homework = get_work_assignment_by_id($my_folder_data['id']);    
	
	if ($homework['expires_on'] != '0000-00-00 00:00:00' || $homework['ends_on'] != '0000-00-00 00:00:00') {
		$time_now		= time();

		if (!empty($homework['expires_on']) && $homework['expires_on'] != '0000-00-00 00:00:00') {            
			$time_expires 	= api_strtotime($homework['expires_on'], 'UTC');
			$difference 	= $time_expires - $time_now;
			if ($difference < 0) {
				$has_expired = true;				
			}
		}
        
        if (empty($homework['expires_on']) || $homework['expires_on'] == '0000-00-00 00:00:00') {
			$has_expired = false;
		}
        
		if (!empty($homework['ends_on']) && $homework['ends_on'] != '0000-00-00 00:00:00') {
			$time_ends 		= api_strtotime($homework['ends_on'], 'UTC');
			$difference2 	= $time_ends - $time_now;
			if ($difference2 < 0) {
				$has_ended = true;
			}
		}
		
		$ends_on 	= api_convert_and_format_date($homework['ends_on']);
		$expires_on = api_convert_and_format_date($homework['expires_on']);

		if ($has_ended) {            
            //if (!api_is_allowed_to_edit()) {                
                $display_upload_link = false;
            //}
			$message = Display::return_message(get_lang('EndDateAlreadyPassed').' '.$ends_on, 'error');
		} elseif ($has_expired) {            
            $display_upload_link = true;                       	
			$message = Display::return_message(get_lang('ExpiryDateAlreadyPassed').' '.$expires_on, 'warning');
		} else {	
			if ($has_expired) {
				$message = Display::return_message(get_lang('ExpiryDateToSendWorkIs').' '.$expires_on);
			}
		}        
	}
}

display_action_links($work_id, $curdirpath, $show_tool_options, $display_upload_link, $action);

echo $message;

//for teachers

switch ($action) {
    case 'send_mail':        
		if (Security::check_token('get')) {
			$mails_sent_to = send_reminder_users_without_publication($my_folder_data);
            if (empty($mails_sent_to)) {
                Display::display_warning_message(get_lang('NoResults'));
            } else {
                Display::display_confirmation_message(get_lang('MessageHasBeenSent').' '.implode(', ', $mails_sent_to));
            }            
            Security::clear_token();			
		}
		break;		
	case 'settings':
		//if posts
		if ($is_allowed_to_edit && !empty($_POST['changeProperties'])) {
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
		}		
		/*	Display of tool options */
		display_tool_options($uploadvisibledisabled, $origin);		
		break;
	case 'mark_work':
        if (!api_is_allowed_to_edit()) {
            echo Display::return_message(get_lang('ActionNotAllowed'), 'error');
            Display::display_footer();					
        }
        break;
    case 'edit':
	case 'upload_form': //can be add or edit work
        $is_author = false;        
		if (empty($item_id)) {
			$parent_data = get_work_data_by_id($work_id);            
			$parent_data['qualification'] = intval($parent_data['qualification']);
			
			if (!empty($parent_data) && !empty($parent_data['qualification']))  {
				$count =  get_work_count_by_student($user_id, $work_id);                		
				if ($count >= 1 ) {
					if (api_get_course_setting('student_delete_own_publication') == '1') {
						Display::display_warning_message(get_lang('CantUploadDeleteYourPaperFirst'));					
					} else {
						Display::display_warning_message(get_lang('YouAlreadySentAPaperYouCantUpload'));
					}				
					Display::display_footer();
					exit;
				}
			}
		} else {
			//we found the current user is the author
			$sql = "SELECT * FROM  $work_table WHERE c_id = $course_id AND id = $item_id";
			$result = Database::query($sql);
			$work_item = array();
			if ($result) {
				$work_item = Database::fetch_array($result);
			}			
			
			//Get the author ID for that document from the item_property table	
            $is_author 			= user_is_author($item_id);   
            if (!$is_author) {
                Display::display_warning_message(get_lang('NotAllowed'));	
                Display::display_footer();
            }
		} 
                        
		$form = new FormValidator('form', 'POST', api_get_self() . "?action=upload&id=".$work_id."&gradebook=".Security::remove_XSS($_GET['gradebook'])."&origin=$origin", '', array('enctype' => "multipart/form-data"));
	
		// form title
		if ($item_id) {
			$form_title = get_lang('Edit');
		} else {
			$form_title = get_lang('UploadADocument');
		}
		$form->addElement('header', $form_title);
	
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
		} else {
			// else standard upload option
			$form->addElement('file', 'file', get_lang('UploadADocument'), 'size="40" onchange="updateDocumentTitle(this.value)"');
			$show_progress_bar = true;
		}		
		
        $form->addElement('hidden', 'id', $work_id);
		if (empty($item_id)) {
			$form->addElement('checkbox', 'contains_file', null, get_lang('ContainsAfile'), array('id'=>'contains_file_id'));
		} else {
            $form->addElement('hidden', 'item_id', $item_id);
        }
		$form->addElement('text', 'title', get_lang('Title'), array('id' => 'file_upload', 'class' => 'span4'));
		//$form->addElement('html_editor', 'description', get_lang("Description"));        
        $form->add_html_editor('description', get_lang('Description'), false, false, array('ToolbarSet' => 'Work', 'Width' => '100%', 'Height' => '200'));
		
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
            $qualification_over = $row['qualification'];
            if (!empty($qualification_over) && intval($qualification_over) > 0) {
                $form->addElement('text', 'qualification', array(get_lang('Qualification'),  null, " / ".$qualification_over), 'size="10"');			
                $form->addElement('hidden', 'qualification_over', $qualification_over);
            }
		}	
		
		$form->addElement('hidden', 'active',   1);
		$form->addElement('hidden', 'accepted', 1);
		$form->addElement('hidden', 'item_to_edit', $item_id);
        $token = Security::get_token();
		$form->addElement('hidden', 'sec_token', $token);
		
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
        if (!empty($work_id)) {
            if ($is_allowed_to_edit) {
                if (api_resource_is_locked_by_gradebook($work_id, LINK_STUDENTPUBLICATION)) {
                    echo Display::display_warning_message(get_lang('ResourceLockedByGradebook'));
                } else {
                    $form->display();
                }
            } elseif ($is_author) {
                if (empty($work_item['qualificator_id']) || $work_item['qualificator_id'] == 0) {
                    $form->display();
                } else {
                    Display::display_error_message(get_lang('ActionNotAllowed'));
                }
            } elseif ($student_can_edit_in_session && $has_ended == false) {          
                $form->display();
            } else {
                Display::display_error_message(get_lang('ActionNotAllowed'));
            }
        } else {
            Display::display_error_message(get_lang('ActionNotAllowed'));
        }        
		break;        
    case 'upload': 
        $check = Security::check_token('post');        
        //var_dump($check);
		if ($student_can_edit_in_session && $check) {
			
			//check the token inserted into the form
			if (isset($_POST['submitWork']) && !empty($is_course_member)) {
				$authors = api_get_person_name($currentUserFirstName, $currentUserLastName);
				$url = null;
                $contains_file = 0;
                
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
                    $curdirpath = basename($my_folder_data['url']);
                    
					//if we come from the group tools the groupid will be saved in $work_table
					$result = @move_uploaded_file($_FILES['file']['tmp_name'], $updir.$curdirpath.'/'.$new_file_name);
                    if ($result) {
                        $url = 'work/'.$curdirpath.'/'.$new_file_name;
                        $contains_file = 1;
                    }
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
						                   contains_file = '".$contains_file."',  
										   active		= '" . $active . "',                                           
										   accepted		= '1',
										   post_group_id = '".$group_id."',
										   sent_date	=  '".api_get_utc_datetime()."',
										   parent_id 	=  '".$work_id."' ,
                                           session_id	= '".intval($id_session)."' ,                                               
                                           user_id 		= '".$user_id."'";
				//var_dump($sql_add_publication);
				Database::query($sql_add_publication);
				$id = Database::insert_id();				
				if ($id) {				
					api_item_property_update($course_info, 'work', $id, 'DocumentAdded', $user_id, api_get_group_id());
					$succeed = true;
				}														
			} elseif ($newWorkUrl) {
			
				// SPECIAL CASE ! For a work coming from another area (i.e. groups)
	/*
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
								    post_group_id   = '".$group_id."',
						            sent_date    	= '".api_get_utc_datetime()."',
						            session_id 		= '".intval($id_session)."',
						            user_id 		= '".$user_id."'";
	
				Database::query($sql);
	
				$insertId = Database::insert_id();
				api_item_property_update($_course, 'work', $insertId, 'DocumentAdded', $user_id, $group_id);
				$succeed = true;*/
			} elseif (isset($_POST['editWork'])) {			
				/*
				 * SPECIAL CASE ! For a work edited
				*/					
				//Get the author ID for that document from the item_property table
                $item_to_edit_id 	= intval($_POST['item_to_edit']);
				$is_author 			= user_is_author($item_to_edit_id);
					
				if ($is_author) {
					$work_data = get_work_data_by_id($item_to_edit_id);
                    
					if (!empty($_POST['title']))
					$title 		 = isset($_POST['title']) ? $_POST['title'] : $work_data['title'];
					$description = isset($_POST['description']) ? $_POST['description'] : $work_data['description'];					
	
					if ($is_allowed_to_edit && ($_POST['qualification'] !='' )) {
						$add_to_update = ', qualificator_id ='."'".api_get_user_id()."',";
						$add_to_update .= ' qualification = '."'".Database::escape_string($_POST['qualification'])."',";
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
                    Display :: display_confirmation_message(get_lang('ItemUpdated'), false);
				} else {
					$error_message = get_lang('IsNotPosibleSaveTheDocument');
				}
			}    
            Security::clear_token();
		}
						
		if (!empty($succeed) && !empty($id)) {
			//last value is to check this is not "just" an edit
			//YW Tis part serve to send a e-mail to the tutors when a new file is sent
			$send = api_get_course_setting('email_alert_manager_on_new_doc');
			
			if ($send > 0) {
				// Lets predefine some variables. Be sure to change the from address!
				
				$emailto = array ();
				if (empty($id_session)) {
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
				
					$emailbody_user = get_lang('Dear')." ".$currentUserFirstName .' '.$currentUserLastName .", \n\n";
					$emailbody_user .= get_lang('MessageConfirmSendingOfTask')."\n".get_lang('CourseName')." : ".$_course['name']."\n";
					$emailbody_user .= get_lang('WorkName')." : ".substr($my_cur_dir_path, 0, -1)."\n";
					$emailbody_user .= get_lang('DateSent')." : ".api_format_date(api_get_local_time())."\n";
					$emailbody_user .= get_lang('FileName')." : ".$title."\n\n".api_get_setting('administratorName')." ".api_get_setting('administratorSurname') . "\n" . get_lang('Manager') . " " . api_get_setting('siteName') . "\n" . get_lang('Email') . " : " . api_get_setting('emailAdministrator');;
				
					//Mail to user
                    //var_dump($currentUserEmail, $emailsubject, $emailbody_user, $sender_name, $email_admin);
                    
					@api_mail('', $currentUserEmail, $emailsubject, $emailbody_user, $sender_name, $email_admin);
				}
			}
			$message = get_lang('DocAdd');
			//stats
			if (!$Id) {
				$Id = $insertId;
			}
			event_upload($Id);			
			Display :: display_confirmation_message(get_lang('DocAdd'), false);
		}
    case 'create_dir':	
	case 'add':
        //$check = Security::check_token('post');                
        //show them the form for the directory name
        
		if ($is_allowed_to_edit && in_array($action, array('create_dir','add'))) {   		    
			//create the form that asks for the directory name
            $form = new FormValidator('form1', 'post', api_get_self().'?action=create_dir&'. api_get_cidreq());
            
            $form->addElement('header', get_lang('CreateAssignment').$token);
            $form->addElement('hidden', 'action', 'add');
            $form->addElement('hidden', 'curdirpath', Security :: remove_XSS($curdirpath));            
           // $form->addElement('hidden', 'sec_token', $token);      
            
            $form->addElement('text', 'new_dir', get_lang('AssignmentName'));                        
            $form->addRule('new_dir', get_lang('ThisFieldIsRequired'), 'required');
            
            //$form->addElement('html_editor', 'description', get_lang('Description'));
            $form->add_html_editor('description', get_lang('Description'), false, false, array('ToolbarSet' => 'Work', 'Width' => '100%', 'Height' => '200'));
            
            $form->addElement('advanced_settings', '<a href="javascript: void(0);" onclick="javascript: return plus();"><span id="plus">'.Display::return_icon('div_show.gif',get_lang('AdvancedParameters'), array('style' => 'vertical-align:center')).' '.get_lang('AdvancedParameters').'</span></a>');
            
            $form->addElement('html', '<div id="options" style="display: none;">');
            
        
            
            if(Gradebook::is_active()){
                //QualificationOfAssignment
                $form->addElement('text', 'qualification_value', get_lang('QualificationNumeric'));
                $form->addElement('checkbox', 'make_calification', null, get_lang('MakeQualifiable'), array('id' =>'make_calification_id', 'onclick' => "javascript: if(this.checked){document.getElementById('option1').style.display='block';}else{document.getElementById('option1').style.display='none';}"));
            }else{
                //QualificationOfAssignment
                $form->addElement('hidden', 'qualification_value',0);
                $form->addElement('hidden', 'make_calification', false);
            }
            
            $form->addElement('html', '<div id="option1" style="display: none;">');
            
            //Loading gradebook select
            load_gradebook_select_in_tool($form);        
            
            $form->addElement('text', 'weight', get_lang('WeightInTheGradebook'));
            $form->addElement('html', '</div>');            
            
            $form->addElement('checkbox', 'type1', null, get_lang('EnableExpiryDate'), array('id' =>'make_calification_id', 'onclick' => "javascript: if(this.checked){document.getElementById('option2').style.display='block';}else{document.getElementById('option2').style.display='none';}"));
            
            $form->addElement('html', '<div id="option2" style="display: none;">');
            $form->addElement('advanced_settings',draw_date_picker('expires'));
            $form->addElement('html', '</div>');
            
            
            $form->addElement('checkbox', 'type2', null, get_lang('EnableEndDate'), array('id' =>'make_calification_id', 'onclick' => "javascript: if(this.checked){document.getElementById('option3').style.display='block';}else{document.getElementById('option3').style.display='none';}"));
            
            $form->addElement('html', '<div id="option3" style="display: none;">');
            $form->addElement('advanced_settings', draw_date_picker('ends'));
            $form->addElement('html', '</div>');
               
            $form->addElement('checkbox', 'add_to_calendar', null, get_lang('AddToCalendar'));            
            $form->addElement('checkbox', 'allow_text_assignment', null, get_lang('AllowTextAssignments'));      
            $form->addElement('html', '</div>');            
            $form->addElement('style_submit_button', 'submit', get_lang('CreateDirectory'));
		        
            if ($form->validate()) {

                $directory 		= Security::remove_XSS($_POST['new_dir']);
                $directory 		= replace_dangerous_char($directory);
                $directory 		= disable_dangerous_file($directory);
                $dir_name 		= $curdirpath.$directory;
                $created_dir 	= create_unexisting_work_directory($base_work_dir, $dir_name);

                // we insert here the directory in the table $work_table
                $dir_name_sql = '';

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
                                            title               = '".Database::escape_string($_POST['new_dir'])."',
                                            description 		= '".Database::escape_string($_POST['description'])."',
                                            author      		= '',
                                            active              = '1',
                                            accepted			= '1',
                                            filetype            = 'folder',
                                            post_group_id       = '".$group_id."',
                                            sent_date           = '".$today."',
                                            qualification       = '".(($_POST['qualification_value']!='') ? Database::escape_string($_POST['qualification_value']) : '') ."',
                                            parent_id           = '',
                                            qualificator_id     = '',
                                            date_of_qualification	= '0000-00-00 00:00:00',
                                            weight              = '".Database::escape_string($_POST['weight'])."',
                                            session_id          = '".intval($id_session)."',
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

                            $agenda_id = agenda_add_item($course_info, $title, $content, $date, $end_date, array('GROUP:'.$group_id), 0);
                        }
                    }

                    //Folder created
                    api_item_property_update($course_info, 'work', $id, 'DirectoryCreated', $user_id, $group_id);
                    Display :: display_confirmation_message(get_lang('DirectoryCreated'), false);

                    // insert into student_publication_assignment	
                    //return something like this: 2008-02-45 00:00:00

                    $enable_calification = isset($_POST['qualification_value']) && !empty($_POST['qualification_value']) ? 1 : 0;

                    if (!empty($_POST['type1']) || !empty($_POST['type2'])) {
                        
                        echo $sql_add_homework = "INSERT INTO $TSTDPUBASG SET
                                                c_id = $course_id ,
                                                expires_on       		= '".((isset($_POST['type1']) && $_POST['type1']==1) ? api_get_utc_datetime(get_date_from_select('expires')) : '0000-00-00 00:00:00'). "',
                                                ends_on        	 		= '".((isset($_POST['type2']) && $_POST['type2']==1) ? api_get_utc_datetime(get_date_from_select('ends')) : '0000-00-00 00:00:00')."',
                                                add_to_calendar  		= '$agenda_id',
                                                enable_qualification 	= '$enable_calification',
                                                publication_id 			= '$id'";
                        Database::query($sql_add_homework);	
                        $my_last_id = Database::insert_id();
                        $sql_add_publication = "UPDATE $work_table SET has_properties  = $my_last_id , view_properties = 1  WHERE c_id = $course_id AND id = $id";
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
                        $sql_add_publication = "UPDATE $work_table SET has_properties  = $inserted_id, view_properties = 0 WHERE c_id = $course_id AND id = $id";
                        Database::query($sql_add_publication);
                    }
                    if (!empty($_POST['category_id'])) {
                 
                        if (isset($_POST['make_calification']) && $_POST['make_calification'] == 1) {

                            require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/gradebookitem.class.php';
                            require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/evaluation.class.php';
                            require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/abstractlink.class.php';
                            require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';

                            $resource_name = $_POST['new_dir'];
                            add_resource_to_course_gradebook($_POST['category_id'], api_get_course_id(), 3, $id, $resource_name, $_POST['weight'], $_POST['qualification_value'], $_POST['description'], 1, api_get_session_id());
                        }	
                    }
                    
                    if (api_get_course_setting('email_alert_students_on_new_homework') == 1) {
                        send_email_on_homework_creation(api_get_course_id());
                    }
                } else {
                    Display :: display_error_message(get_lang('CannotCreateDir'));
                }
            } else {
                $form->display();     
            }     	
        }
	case 'make_visible':
    case 'delete':
	case 'make_invisible':	
	case 'move':
	case 'move_to':
	case 'list':		
		/*	Move file command */
		if ($is_allowed_to_edit && $action == 'move_to') {  
			$move_to_path = get_work_path($_REQUEST['move_to_id']);
		
			if ($move_to_path==-1) {
				$move_to_path = '/';
			} elseif (substr($move_to_path, -1, 1) != '/') {
				$move_to_path = $move_to_path .'/';
			}
			
			//security fix: make sure they can't move files that are not in the document table			
			if ($path = get_work_path($item_id)) {
			    
				if (move($course_dir.'/'.$path, $base_work_dir . $move_to_path)) {
					//update db		
					update_work_url($item_id, 'work' . $move_to_path, $_REQUEST['move_to_id']);
                    
                    api_item_property_update($_course, 'work', $_REQUEST['move_to_id'], 'FolderUpdated', $user_id);
                    
                    /*
					// update all the parents in the table item propery
					$list_id = get_parent_directories($move_to_path);
					for ($i = 0; $i < count($list_id); $i++) {
						api_item_property_update($_course, 'work', $list_id[$i], 'FolderUpdated', $user_id);
					}*/		
					Display :: display_confirmation_message(get_lang('DirMv'));
				} else {
					Display :: display_error_message(get_lang('Impossible'));
				}
			} else {
				Display :: display_error_message(get_lang('Impossible'));
			}
		}

		/*	Move file form request */
		if ($is_allowed_to_edit && $action == 'move') {
			if (!empty($item_id)) {
				$folders = array();
				$session_id = api_get_session_id();
				$session_id == 0 ? $withsession = " AND session_id = 0 " : $withsession = " AND session_id='".$session_id."'";			
				$sql = "SELECT id, url, title FROM $work_table 
				        WHERE c_id = $course_id AND active IN (0, 1) AND url LIKE '/%' AND post_group_id = '".$group_id."'".$withsession;                
				$res = Database::query($sql);
				while($folder = Database::fetch_array($res)) {
					$folders[$folder['id']] = $folder['title'];
				}
				echo build_work_move_to_selector($folders, $curdirpath, $item_id);
			}
		}
		
		/*	MAKE VISIBLE WORK COMMAND */
		if ($is_allowed_to_edit && $action == 'make_visible') {
			if (!empty($item_id)) {
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
		
		if ($is_allowed_to_edit && $action == 'make_invisible') {
		
			/*	MAKE INVISIBLE WORK COMMAND */
			if (!empty($item_id)) {
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
		
		if ($is_allowed_to_edit && !empty($_REQUEST['delete_dir'])) {
            $delete_dir_id = intval($_REQUEST['delete_dir']);
            $locked = api_resource_is_locked_by_gradebook($delete_dir_id, LINK_STUDENTPUBLICATION);
            
            if ($locked == false) {
            
                $work_to_delete = get_work_data_by_id($delete_dir_id);
                del_dir($delete_dir_id);	

                // gets calendar_id from student_publication_assigment
                $sql = "SELECT add_to_calendar FROM $TSTDPUBASG WHERE c_id = $course_id AND publication_id ='$delete_dir_id'";
                $res = Database::query($sql);
                $calendar_id = Database::fetch_row($res);

                // delete from agenda if it exists
                if (!empty($calendar_id[0])) {
                    $t_agenda   = Database::get_course_table(TABLE_AGENDA);
                    $sql = "DELETE FROM $t_agenda WHERE c_id = $course_id AND id ='".$calendar_id[0]."'";
                    Database::query($sql);
                }
                $sql = "DELETE FROM $TSTDPUBASG WHERE c_id = $course_id AND publication_id ='$delete_dir_id'";
                Database::query($sql);

                $link_info = is_resource_in_course_gradebook(api_get_course_id(), 3 , $delete_dir_id, api_get_session_id());
                $link_id = $link_info['id'];
                if ($link_info !== false) {
                    remove_resource_from_course_gradebook($link_id);
                }            
                Display :: display_confirmation_message(get_lang('DirDeleted') . ': '.$work_to_delete['title']);            
            } else {
                Display::display_warning_message(get_lang('ResourceLockedByGradebook'));
            }
		}
		
		/*	DELETE WORK COMMAND */
		
		if ($action == 'delete' && $item_id) {
							
            $file_deleted = false;	
            $is_author = user_is_author($item_id);     
            $work_data = get_work_data_by_id($item_id);            
            $locked = api_resource_is_locked_by_gradebook($work_data['parent_id'], LINK_STUDENTPUBLICATION);
            
            if ( ($is_allowed_to_edit && $locked == false) || ($locked == false AND $is_author && api_get_course_setting('student_delete_own_publication') == 1 && $work_data['qualificator_id'] == 0)) {
                //we found the current user is the author
                $queryString1 	= "SELECT url, contains_file FROM  " . $work_table . "  WHERE c_id = $course_id AND id = $item_id";
                $result1 		= Database::query($queryString1);
                $row 			= Database::fetch_array($result1);

                if (Database::num_rows($result1) > 0) {
                    $queryString2 	= "UPDATE " . $work_table . "  SET active  = 2 WHERE c_id = $course_id AND id = $item_id";
                    $queryString3 	= "DELETE FROM  " . $TSTDPUBASG . "  WHERE c_id = $course_id AND publication_id = $item_id";
                    Database::query($queryString2);
                    Database::query($queryString3);						 
                    api_item_property_update($_course, 'work', $item_id, 'DocumentDeleted', $user_id);
                    $work = $row['url'];

                    if ($row['contains_file'] == 1) {
                        if (!empty($work)) {
                            if (api_get_setting('permanently_remove_deleted_files') == 'true') {
                                my_delete($currentCourseRepositorySys.'/'.$work);
                                Display::display_confirmation_message(get_lang('TheDocumentHasBeenDeleted'));
                                $file_deleted = true;
                            } else {                                
                                $extension = pathinfo($work, PATHINFO_EXTENSION);                                
                                $new_dir = $work.'_DELETED_'.$item_id.'.'.$extension;

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
            }
            if (!$file_deleted) {
                Display::display_error_message(get_lang('YouAreNotAllowedToDeleteThisDocument'));
            }        
		}		
		
		/*	Display list of student publications */		
		if ($curdirpath == '/') {
			$my_cur_dir_path = '';
		} else {
			$my_cur_dir_path = $curdirpath;
		}		
		
		if (!empty($my_folder_data['description'])) {
			echo '<p><div><strong>'.get_lang('Description').':</strong><p>'.Security::remove_XSS($my_folder_data['description'], STUDENT).'</p></div></p>';
		}
        
        //User works
        if (isset($work_id) && !empty($work_id) && !$display_list_users_without_publication) {           
            $work_data = get_work_assignment_by_id($work_id);                    
            $check_qualification = intval($my_folder_data['qualification']);
            
            if (!empty($work_data['enable_qualification']) && !empty($check_qualification)) {
                $type = 'simple';
                $columns        = array(get_lang('Type'), get_lang('FirstName'), get_lang('LastName'), get_lang('LoginName'), 
                                        get_lang('Qualification'), get_lang('Date'),  get_lang('Status'), get_lang('Actions'));
                $column_model   = array (
                    array('name'=>'type',           'index'=>'file',            'width'=>'12',   'align'=>'left', 'search' => 'false'),                        
                    array('name'=>'firstname',      'index'=>'firstname',       'width'=>'50',   'align'=>'left', 'search' => 'true'),                        
                    array('name'=>'lastname',		'index'=>'lastname',        'width'=>'50',   'align'=>'left', 'search' => 'true'),
                    array('name'=>'username',       'index'=>'username',        'width'=>'30',   'align'=>'left', 'search' => 'true'),                    
    //                array('name'=>'file',           'index'=>'file',            'width'=>'20',   'align'=>'left', 'search' => 'false'),
                    array('name'=>'qualification',	'index'=>'qualification',	'width'=>'20',   'align'=>'left', 'search' => 'true'),                        
                    array('name'=>'sent_date',           'index'=>'sent_date',            'width'=>'60',   'align'=>'left', 'search' => 'true'),                        
                    array('name'=>'qualificator_id','index'=>'qualificator_id', 'width'=>'30',   'align'=>'left', 'search' => 'true'),      
                    array('name'=>'actions',        'index'=>'actions',         'width'=>'40',   'align'=>'left', 'search' => 'false', 'sortable'=>'false')
                    
                );
            } else {
                $type = 'complex';
                $columns        = array(get_lang('Type'), get_lang('FirstName'), get_lang('LastName'), get_lang('LoginName'), 
                                         get_lang('Date'),  get_lang('Actions'));
                $column_model   = array (
                    array('name'=>'type',           'index'=>'file',            'width'=>'12',   'align'=>'left', 'search' => 'false'),                        
                    array('name'=>'firstname',      'index'=>'firstname',       'width'=>'50',   'align'=>'left', 'search' => 'true'),                        
                    array('name'=>'lastname',		'index'=>'lastname',        'width'=>'50',   'align'=>'left', 'search' => 'true'),
                    array('name'=>'username',       'index'=>'username',        'width'=>'30',   'align'=>'left', 'search' => 'true'),                    
    //                array('name'=>'file',           'index'=>'file',            'width'=>'20',   'align'=>'left', 'search' => 'false'),
                    //array('name'=>'qualification',	'index'=>'qualification',	'width'=>'20',   'align'=>'left', 'search' => 'true'),                        
                    array('name'=>'sent_date',       'index'=>'sent_date',            'width'=>'60',   'align'=>'left', 'search' => 'true'),                        
                    //array('name'=>'qualificator_id','index'=>'qualificator_id', 'width'=>'30',   'align'=>'left', 'search' => 'true'),      
                    array('name'=>'actions',        'index'=>'actions',         'width'=>'40',   'align'=>'left', 'search' => 'false', 'sortable'=>'false')
                );
            }         

            $extra_params = array();

            //Autowidth             
            $extra_params['autowidth'] = 'true';

            //height auto 
            $extra_params['height'] = 'auto';
            //$extra_params['excel'] = 'excel';

            //$extra_params['rowList'] = array(10, 20 ,30);
            
            $extra_params['sortname'] = 'firstname';            
            $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_work_user_list&work_id='.$work_id.'&type='.$type;
            ?>
            <script>
                $(function() {
                <?php
                echo Display::grid_js('results', $url, $columns, $column_model, $extra_params);                
            ?>
                 });
            </script>
            <?php                
            echo Display::grid_html('results');                    
        } elseif (isset($_GET['list']) && $_GET['list'] == 'without') {
            //User with no works
            display_list_users_without_publication($work_id);                
        } else {
            //Work list
            display_student_publications_list($work_id, $link_target_parameter, $dateFormatLong, $origin, $add_query);
        }		
		break;
}
if ($origin != 'learnpath') {
	//we are not in the learning path tool
	Display :: display_footer();
}