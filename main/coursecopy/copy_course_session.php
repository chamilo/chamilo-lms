<?php
/* For licensing terms, see /license.txt */
/**
 * Copy resources from one course in a session to another one.
 *
 * @author Christian Fasanando <christian.fasanando@dokeos.com>
 * @author Julio Montoya	<gugli100@gmail.com> Lots of bug fixes/improvements
 * @package chamilo.backup
 */
/**
 * Code
 */
/*  INIT SECTION  */

// Language files that need to be included
$language_file = array('coursebackup', 'admin');

$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'xajax/xajax.inc.php';

require_once 'classes/CourseBuilder.class.php';
require_once 'classes/CourseRestorer.class.php';
require_once 'classes/CourseSelectForm.class.php';

$xajax = new xajax();
$xajax->registerFunction('search_courses');

if (!api_is_allowed_to_edit() && !api_is_session_admin()) {
	api_not_allowed(true);
}

// Remove memory and time limits as much as possible as this might be a long process...
if (function_exists('ini_set')) {
	ini_set('memory_limit', '256M');
	ini_set('max_execution_time', 1800);
}

$this_section = SECTION_PLATFORM_ADMIN;

$nameTools = get_lang('CopyCourse');
$interbreadcrumb[] = array('url' => '../admin/index.php', 'name' => get_lang('PlatformAdmin'));

// Database Table Definitions
$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);

/* FUNCTIONS */

function make_select_session_list($name, $sessions, $attr = array()) {
	
	$attrs = '';
	if (count($attr) > 0) {
		foreach ($attr as $key => $value) {
			$attrs .= ' '.$key.'="'.$value.'"';
		}
	}	
	$output = '<select name="'.$name.'" '.$attrs.'>';
	
	if (count($sessions) == 0) {
		$output .= '<option value = "0">'.get_lang('ThereIsNotStillASession').'</option>';
	} else {
		$output .= '<option value = "0">'.get_lang('SelectASession').'</option>';
	}

	if (is_array($sessions)) {
		foreach ($sessions as $session) {
            $category_name = '';
            if (!empty($session['category_name'])) {
                $category_name = ' ('.$session['category_name'].')';
            }
            
			$output .= '<option value="'.$session['id'].'">'.$session['name'].' '.$category_name.'</option>';
		}
	}
	$output .= '</select>';
 	return $output;
}

function display_form() {
	$html  = '';
	$sessions = SessionManager::get_sessions_list(null, array('name ASC'));
	
	// Actions
	$html .= '<div class="actions">';
	// Link back to the documents overview
	$html .= '<a href="../admin/index.php">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'),'',ICON_SIZE_MEDIUM).'</a>';
	$html .= '</div>';

    $html .= Display::return_message(get_lang('CopyCourseFromSessionToSessionExplanation'));
    
	$html .= '<form name="formulaire" method="post" action="'.api_get_self().'" >';    
	$html .= '<table border="0" cellpadding="5" cellspacing="0" width="100%">';
    
	// origin
	$html .= '<tr><td width="15%"><b>'.get_lang('OriginCoursesFromSession').':</b></td>';
	$html .= '<td width="10%" align="left">'.make_select_session_list('sessions_list_origin', $sessions, array('onchange' => 'javascript: xajax_search_courses(this.value,\'origin\');')).'</td>';	
	$html .= '<td width="50%"><div id="ajax_list_courses_origin">';
	$html .= '<select id="origin" name="SessionCoursesListOrigin[]"  style="width:380px;"></select></div></td></tr>';
    
    //destination    
    $html .= '<tr><td width="15%"><b>'.get_lang('DestinationCoursesFromSession').':</b></td>';    
    $html .= '<td width="10%" align="left"><div id="ajax_sessions_list_destination">';
	$html .= '<select name="sessions_list_destination" onchange="javascript: xajax_search_courses(this.value,\'destination\');">';
	$html .= '<option value = "0">'.get_lang('ThereIsNotStillASession').'</option></select ></div></td>';    
	
	$html .= '<td width="50%">';
	$html .= '<div id="ajax_list_courses_destination">';
	$html .= '<select id="destination" name="SessionCoursesListDestination[]" style="width:380px;" ></select></div></td>';
	$html .= '</tr></table>';
	
	$html .= '<h3>'.get_lang('TypeOfCopy').'</h3>';
    $html .= '<label class="radio"><input type="radio" id="copy_option_1" name="copy_option" value="full_copy" checked="checked"/>';
    $html .= get_lang('FullCopy').'</label><br/>';
    $html .= '<label class="radio"><input type="radio" id="copy_option_2" name="copy_option" value="select_items" disabled="disabled"/>';
    $html .= ' '.get_lang('LetMeSelectItems').'</label><br/>';
    
    $html .= '<label class="checkbox"><input type="checkbox" id="copy_base_content_id" name="copy_only_session_items" />'.get_lang('CopyOnlySessionItems').'</label><br /><br/>';
	
	$html .= '<button class="save" type="submit" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;">'.get_lang('CopyCourse').'</button>';
    $html .= '</form>';
	echo $html;
}

function search_courses($id_session, $type) {	
	global $tbl_course, $tbl_session_rel_course, $course_list;
	$xajax_response = new XajaxResponse();
	$select_destination = '';
	if (!empty($type)) {

		$id_session = intval($id_session);

		if ($type == 'origin') {
		    
            $course_list = SessionManager::get_course_list_by_session_id($id_session);
     
			$temp_course_list = array();
			$return .= '<select id="origin" name="SessionCoursesListOrigin[]" style="width:380px;" onclick="javascript: checkSelected(this.id,\'copy_option_2\',\'title_option2\',\'destination\');">';
			
			foreach ($course_list as $course) {			    
				$temp_course_list[] = "'{$course['code']}'";
				$course_title=str_replace("'","\'",$course_title);
				$return .= '<option value="'.$course['code'].'" title="'.@htmlspecialchars($course['title'].' ('.$course['visual_code'].')', ENT_QUOTES, api_get_system_encoding()).'">'.$course['title'].' ('.$course['visual_code'].')</option>';
			}

			$return .= '</select>';
			$_SESSION['course_list']     = $temp_course_list;
			$_SESSION['session_origin']  = $id_session;

			// Build select for destination sessions where is not included current session from select origin
			if (!empty($id_session)) {
			    
			    $sessions = SessionManager::get_sessions_list(null, array('name ASC'));

				$select_destination .= '<select name="sessions_list_destination" width="380px" onchange = "javascript: xajax_search_courses(this.value,\'destination\');">';
				$select_destination .= '<option value = "0">-- '.get_lang('SelectASession').' --</option>';
				foreach ($sessions as $session) {
				    if ($id_session == $session['id']) { continue; };
				    if (!empty($session['category_name'])) {
				        $session['category_name'] = ' ('.$session['category_name'].') '; 
				    }
					$select_destination .= '<option value="'.$session['id'].'">'.$session['name'].' '.$session['category_name'].'</option>';
				}
				$select_destination .= '</select>';
				$xajax_response -> addAssign('ajax_sessions_list_destination', 'innerHTML', api_utf8_encode($select_destination));
			} else {
				$select_destination .= '<select name="sessions_list_destination" width="380px" onchange = "javascript: xajax_search_courses(this.value,\'destination\');">';
				$select_destination .= '<option value = "0">'.get_lang('ThereIsNotStillASession').'</option>';
				$select_destination .= '</select>';
				$xajax_response -> addAssign('ajax_sessions_list_destination', 'innerHTML', api_utf8_encode($select_destination));
			}

			// Select multiple destination empty
			$select_multiple_empty = '<select id="destination" name="SessionCoursesListDestination[]" style="width:380px;"></select>';

			// Send response by ajax
			$xajax_response -> addAssign('ajax_list_courses_origin', 'innerHTML', api_utf8_encode($return));
			$xajax_response -> addAssign('ajax_list_courses_destination', 'innerHTML', api_utf8_encode($select_multiple_empty));
		} else {
			//Left Select - Destination
			
			$list_courses_origin = implode(',', $_SESSION['course_list']);
			$session_origin = $_SESSION['session_origin'];

			// Search courses by id_session where course codes is include en courses list destination
			$sql = "SELECT c.code, c.visual_code, c.title, src.id_session
					FROM $tbl_course c, $tbl_session_rel_course src
					WHERE src.course_code = c.code
					AND src.id_session = '".intval($id_session)."'";
					//AND c.code IN ($list_courses_origin)";
			$rs = Database::query($sql);

			$course_list_destination = array();
			//onmouseover="javascript: this.disabled=true;" onmouseout="javascript: this.disabled=false;"
			$return .= '<select id="destination" name="SessionCoursesListDestination[]" style="width:380px;" >';
			while ($course = Database :: fetch_array($rs)) {
				$course_list_destination[] = $course['code'];
				$course_title = str_replace("'", "\'", $course_title);
				$return .= '<option value="'.$course['code'].'" title="'.@htmlspecialchars($course['title'].' ('.$course['visual_code'].')', ENT_QUOTES, api_get_system_encoding()).'">'.$course['title'].' ('.$course['visual_code'].')</option>';
			}
			$return .= '</select>';
			$_SESSION['course_list_destination'] = $course_list_destination;

			// Send response by ajax
			$xajax_response -> addAssign('ajax_list_courses_destination', 'innerHTML', api_utf8_encode($return));
/*
			// Disable option from session courses list origin where if no the same con the destination
			$sql = "SELECT c.code, c.visual_code, c.title, src.id_session
					FROM $tbl_course c, $tbl_session_rel_course src
					WHERE src.course_code = c.code
					AND src.id_session = '".intval($session_origin)."'";
			$result = Database::query($sql);

			$return_option_disabled = '<select id="origin" name="SessionCoursesListOrigin[]" multiple="multiple" size="20" style="width:320px;" onclick="javascript: checkSelected(this.id,\'copy_option_2\',\'title_option2\',\'destination\');">';
			while ($cours = Database :: fetch_array($result)) {
				$course_title=str_replace("'", "\'", $course_title);
				if (count($course_list_destination) > 0) {
					if (!in_array($cours['code'], $course_list_destination)) {
						$return_option_disabled .= '<optgroup style="color:#ccc" label="'.$cours['title'].' ('.$cours['visual_code'].')" >'.$cours['title'].' ('.$cours['visual_code'].')</optgroup>';
					} else {
						$return_option_disabled .= '<option value="'.$cours['code'].'" title="'.@htmlspecialchars($cours['title'].' ('.$cours['visual_code'].')', ENT_QUOTES, api_get_system_encoding()).'">'.$cours['title'].' ('.$cours['visual_code'].')</option>';
					}
				} else {
					if (empty($id_session)) {
						$return_option_disabled .= '<option value="'.$cours['code'].'" title="'.@htmlspecialchars($cours['title'].' ('.$cours['visual_code'].')', ENT_QUOTES, api_get_system_encoding()).'">'.$cours['title'].' ('.$cours['visual_code'].')</option>';
					} else {
						$return_option_disabled .= '<optgroup style="color:#ccc" label="'.$cours['title'].'('.$cours['visual_code'].')" >'.$cours['title'].' ('.$cours['visual_code'].')</optgroup>';
					}
				}
			}
			$return_option_disabled .= '</select>';*/
			// Send response by ajax
			//$xajax_response -> addAssign('ajax_list_courses_origin', 'innerHTML', api_utf8_encode($return_option_disabled));
		}
	}
	return $xajax_response;
}
$xajax -> processRequests();

/* HTML head extra */

$htmlHeadXtra[] = $xajax->getJavascript( api_get_path(WEB_LIBRARY_PATH).'xajax/');
$htmlHeadXtra[] = '<script type="text/javascript">

						function checkSelected(id_select,id_radio,id_title,id_destination) {
						   var num=0;
						   obj_origin = document.getElementById(id_select);
						   obj_destination = document.getElementById(id_destination);

						   for (x=0;x<obj_origin.options.length;x++) {
						      if (obj_origin.options[x].selected) {
						      		if (obj_destination.options.length > 0) {
										for (y=0;y<obj_destination.options.length;y++) {
												if (obj_origin.options[x].value == obj_destination.options[y].value) {
													obj_destination.options[y].selected = true;
												}
									   	}
									}
						      		num++;
						      	} else {
						      		if (obj_destination.options.length > 0) {
										for (y=0;y<obj_destination.options.length;y++) {
												if (obj_origin.options[x].value == obj_destination.options[y].value) {
													obj_destination.options[y].selected = false;
												}
									   		}
									}
						      	}
						   }

						   if (num == 1) {
						      document.getElementById(id_radio).disabled = false;
						      document.getElementById(id_title).style.color = \'#000\';
						   } else {
						   	  document.getElementById(id_radio).disabled = true;
						      document.getElementById(id_title).style.color = \'#aaa\';
						   }

						}
</script>';


Display::display_header($nameTools);

$with_base_content = true;
if (isset($_POST['copy_only_session_items']) && $_POST['copy_only_session_items']) {
    $with_base_content = false;
}

        
/*  MAIN CODE  */

if ((isset($_POST['action']) && $_POST['action'] == 'course_select_form') || (isset($_POST['copy_option']) && $_POST['copy_option'] == 'full_copy')) {

	$destination_course = $origin_course = $destination_session = $origin_session = '';

	if (isset ($_POST['action']) && $_POST['action'] == 'course_select_form') {
	
		$destination_course	 	= $_POST['destination_course'];
		$origin_course 			= $_POST['origin_course'];
		$destination_session 	= $_POST['destination_session'];
		$origin_session 		= $_POST['origin_session'];
		
		$course = CourseSelectForm :: get_posted_course('copy_course', $origin_session, $origin_course);
		
		$cr = new CourseRestorer($course);
		//$cr->set_file_option($_POST['same_file_name_option']);
		$cr->restore($destination_course, $destination_session);
		Display::display_confirmation_message(get_lang('CopyFinished'));
		display_form();
	} else {

		$arr_course_origin 		= array();
		$arr_course_destination = array();
		$destination_session 	= '';
		$origin_session 		= '';

		if (isset($_POST['SessionCoursesListOrigin'])) {
			$arr_course_origin 		= $_POST['SessionCoursesListOrigin'];
		}
		if (isset($_POST['SessionCoursesListDestination'])) {
			$arr_course_destination = $_POST['SessionCoursesListDestination'];
		}
		if (isset($_POST['sessions_list_destination'])) {
			$destination_session 	= $_POST['sessions_list_destination'];
		}
		if (isset($_POST['sessions_list_origin'])) {
			$origin_session 		= $_POST['sessions_list_origin'];
		}
  
		if ((is_array($arr_course_origin) && count($arr_course_origin) > 0) && !empty($destination_session)) {
			//We need only one value			
			if (count($arr_course_origin) > 1 || count($arr_course_destination) > 1) {
				Display::display_error_message(get_lang('YouMustSelectACourseFromOriginalSession'));				
			} else {
				//foreach ($arr_course_origin as $course_origin) {
				//first element of the array
				$course_code = $arr_course_origin[0];
				$course_destinatination = $arr_course_destination[0];
				
				$course_origin = api_get_course_info($course_code);				
				$cb = new CourseBuilder('', $course_origin);
				$course = $cb->build($origin_session, $course_code, $with_base_content);
				$cr = new CourseRestorer($course);				
				$cr->restore($course_destinatination, $destination_session);
				
			}
			Display::display_confirmation_message(get_lang('CopyFinished'));
			display_form();
		} else {
			Display::display_error_message(get_lang('YouMustSelectACourseFromOriginalSession'));
			display_form();
		}
	}
} elseif (isset($_POST['copy_option']) && $_POST['copy_option'] == 'select_items') {

	// Else, if a CourseSelectForm is requested, show it
	if (api_get_setting('show_glossary_in_documents') != 'none') {
		Display::display_normal_message(get_lang('ToExportDocumentsWithGlossaryYouHaveToSelectGlossary'));
	}

	$arr_course_origin 		= array();
	$arr_course_destination = array();
	$destination_session 	= '';
	$origin_session 		= '';

	if (isset($_POST['SessionCoursesListOrigin'])) {
		$arr_course_origin 		= $_POST['SessionCoursesListOrigin'];
	}
	if (isset($_POST['SessionCoursesListDestination'])) {
		$arr_course_destination = $_POST['SessionCoursesListDestination'];
	}
	if (isset($_POST['sessions_list_destination'])) {
		$destination_session 	= $_POST['sessions_list_destination'];
	}
	if (isset($_POST['sessions_list_origin'])) {
		$origin_session 		= $_POST['sessions_list_origin'];
	}

	if ((is_array($arr_course_origin) && count($arr_course_origin) > 0) && !empty($destination_session)) {
		Display::display_normal_message(get_lang('ToExportLearnpathWithQuizYouHaveToSelectQuiz'));
		$course_origin = api_get_course_info($arr_course_origin[0]);		
		$cb = new CourseBuilder('', $course_origin);
		$course = $cb->build($origin_session, $arr_course_origin[0], $with_base_content);
		//$hidden_fields['same_file_name_option'] = $_POST['same_file_name_option'];
		$hidden_fields['destination_course'] 	= $arr_course_destination[0];
		$hidden_fields['origin_course'] 		= $arr_course_origin[0];
		$hidden_fields['destination_session'] 	= $destination_session;
		$hidden_fields['origin_session'] 		= $origin_session;				
				
		CourseSelectForm :: display_form($course, $hidden_fields, true);
		echo '<div style="float:right"><a href="javascript:window.back();">'.Display::return_icon('back.png', get_lang('Back').' '.get_lang('To').' '.get_lang('PlatformAdmin'), array('style' => 'vertical-align:middle')).get_lang('Back').'</a></div>';
	} else {
		Display::display_error_message(get_lang('You must select a course from original session and select a destination session'));
		display_form();
	}
} else {
	display_form();
}

Display::display_footer();
