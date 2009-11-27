<?php
/* For licensing terms, see /dokeos_license.txt */

/**
 * ==============================================================================
 * Copy resources from one course in a session to another one.
 *
 * @author Christian Fasanando <christian.fasanando@dokeos.com>
 * @package dokeos.backup
 * ==============================================================================
 */

/*  INIT SECTION  */

// name of the language file that needs to be included
$language_file = array('coursebackup','admin');
$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'sessionmanager.lib.php';
require_once 'classes/CourseBuilder.class.php';
require_once 'classes/CourseRestorer.class.php';
require_once 'classes/CourseSelectForm.class.php';
require_once '../inc/lib/xajax/xajax.inc.php';

$xajax = new xajax();
$xajax -> registerFunction('search_courses');


if (!api_is_allowed_to_edit()) {
	api_not_allowed(true);
}

//remove memory and time limits as much as possible as this might be a long process...
if(function_exists('ini_set')) {
	ini_set('memory_limit','256M');
	ini_set('max_execution_time',1800);
}

$this_section=SECTION_PLATFORM_ADMIN;



$nameTools = get_lang('CopyCourse');
$interbreadcrumb[]=array('url' => '../admin/index.php',"name" => get_lang('PlatformAdmin'));

// Database Table Definitions
$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);

/* FUNCTIONS */

function make_select_session_list($name,$sessions,$attr = array()) {

	$attrs = '';
	if (count($attr) > 0) {
		foreach ($attr as $key => $value) {
			$attrs .= ' '.$key.'='.$value. ' ';
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
			$output .= '<option value="'.$session['id'].'">'.$session['name'].' ('.$session['category_name'].')</option>';
		}
	}
	$output .= '</select>';
 	return $output;
}

function display_form() {

	global $charset;

	$html  = '';
	$sessions = SessionManager::get_sessions_list();

	// actions
	$html .= '<div class="sectiontitle">';
	// link back to the documents overview
	$html .= '<a href="../admin/index.php">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('PlatformAdmin'),array('style'=>'vertical-align:middle')).get_lang('BackTo').' '.get_lang('PlatformAdmin').'</a>';
	$html .= '</div>';

	$html .= '<div class="row"><div class="form_header">'.get_lang('CopyCourse').'</div></div>'; 
	$html .= '<form name="formulaire" method="post" action="'.api_get_self().'" >';

	$html .= '<table border="0" cellpadding="5" cellspacing="0" width="100%" align="center">';

	$html .= '<tr><td width="30%" align="center"><b>'.get_lang('OriginCoursesFromSession').':</b></td>';
	$html .= '<td >&nbsp;</td><td align="center" width="30%"><b>'.get_lang('DestinationCoursesFromSession').':</b></td></tr>';
	$html .= '<tr><td width="30%" align="center">'.make_select_session_list('sessions_list_origin',$sessions,array('onchange'=>'xajax_search_courses(this.value,\'origin\')')).'</td>';
	$html .= '<td>&nbsp;</td><td width="30%" align="center"><div id="ajax_sessions_list_destination">';
	$html .= '<select name="sessions_list_destination" onchange = "xajax_search_courses(this.value,\'destination\')">';
	$html .= '<option value = "0">'.get_lang('ThereIsNotStillASession').'</option></select ></div></td></tr>';

	$html .= '<tr><td width="30%" align="center"><div id="ajax_list_courses_origin">';
	$html .= '<select id="origin" name="SessionCoursesListOrigin[]" multiple="multiple" size="20" style="width:320px;"></select></div></td>';

	// Options configuration
	//$html .= '<td align="top"><div class="sectiontitle">'.get_lang('CopyCourse').'</div>';
	$html .= '<td align="top">';
	$introduction = get_lang('CopyCourseFromSessionToSessionExplanation',true);
	$html .= '<div class="normal-message">'.$introduction.'</div>';
	$html .= '<div style="height:150px;padding-top:10px;padding-bottom:50px"><h3>'.get_lang('TypeOfCopy').'</h3>';
	$html .= '<input type="radio" class="checkbox" id="copy_option_1" name="copy_option" value="full_copy" checked="checked"/>';
	$html .= '<label for="copy_option_1">'.get_lang('FullCopy').'</label><br/>';
	$html .= '<input type="radio" class="checkbox" id="copy_option_2" name="copy_option" value="select_items" disabled="disabled"/>';
	$html .= '<label for="copy_option_2"><span id="title_option2" style="color:#aaa">'.get_lang('LetMeSelectItems').'</span></label><br/><br/>';
	$html .= '<button class="save" type="submit" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."'".')) return false;">'.get_lang('CopyCourse').'</button></div>';




	$html .= '</td><td width="30%" align="center">';
	$html .= '<div id="ajax_list_courses_destination">';
	$html .= '<select id="destination" name="SessionCoursesListDestination[]" multiple="multiple" size="20" style="width:320px;" ></select></div></td>';
	$html .= '</tr></table></form>';

	echo $html;
}

function search_courses($id_session,$type) {
	global $tbl_course, $tbl_session_rel_course, $course_list;

	$xajax_response = new XajaxResponse();
	$return_origin = '';
	$select_destination = '';
	if (!empty($type)) {

		$id_session = intval($id_session);

		if ($type == 'origin') {

			// search courses by id_session for origin list
			$sql = "SELECT c.code, c.visual_code, c.title, src.id_session
					FROM $tbl_course c, $tbl_session_rel_course src
					WHERE src.course_code = c.code
					AND src.id_session = '".$id_session."'";
			$rs = Database::query($sql, __FILE__, __LINE__);

			$course_list = array();

			$return .= '<select id="origin" name="SessionCoursesListOrigin[]" multiple="multiple" size="20" style="width:320px;" onclick="checkSelected(this.id,\'copy_option_2\',\'title_option2\',\'destination\')">';
			while($course = Database :: fetch_array($rs)) {
				$course_list[] = "'{$course['code']}'";
				$course_title=str_replace("'","\'",$course_title);

				$return .= '<option value="'.$course['code'].'" title="'.htmlspecialchars($course['title'].' ('.$course['visual_code'].')',ENT_QUOTES).'">'.$course['title'].' ('.$course['visual_code'].')</option>';

			}

			$return .= '</select>';
			$_SESSION['course_list'] = $course_list;
			$_SESSION['session_origin'] = $id_session;

			// Build select for destination sessions where is not included current session from select origin
			if (!empty($id_session)) {
				$session_table =Database::get_main_table(TABLE_MAIN_SESSION);
				$session_category_table = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);

				$sql = " 	SELECT s.id, s.name, sc.name as category_name
							FROM $session_table s , $session_category_table sc
							WHERE s.session_category_id = sc.id AND s.id NOT IN('$id_session')";

				$rs_select_destination = Database::query($sql, __FILE__, __LINE__);

				$select_destination .= '<select name="sessions_list_destination" onchange = "xajax_search_courses(this.value,\'destination\')">';
				$select_destination .= '<option value = "0">'.get_lang('SelectASession').'</option>';
				while($session = Database :: fetch_array($rs_select_destination)) {

					$select_destination .= '<option value="'.$session['id'].'">'.$session['name'].' ('.$session['category_name'].')</option>';
				}
				$select_destination .= '</select>';
				$xajax_response -> addAssign('ajax_sessions_list_destination','innerHTML',api_utf8_encode($select_destination));
			} else{
				$select_destination .= '<select name="sessions_list_destination" onchange = "xajax_search_courses(this.value,\'destination\')">';
				$select_destination .= '<option value = "0">'.get_lang('ThereIsNotStillASession').'</option>';
				$select_destination .= '</select>';
				$xajax_response -> addAssign('ajax_sessions_list_destination','innerHTML',api_utf8_encode($select_destination));
			}

			// select multiple destination empty
			$select_multiple_empty = '<select id="destination" name="SessionCoursesListDestination[]" multiple="multiple" size="20" style="width:320px;"></select>';

			// send response by ajax
			$xajax_response -> addAssign('ajax_list_courses_origin','innerHTML',api_utf8_encode($return));
			$xajax_response -> addAssign('ajax_list_courses_destination','innerHTML',api_utf8_encode($select_multiple_empty));

		} else {

			$list_courses_origin = implode(',',$_SESSION['course_list']);
			$session_origin = $_SESSION['session_origin'];

			// search courses by id_session where course codes is include en courses list destination
			$sql = "SELECT c.code, c.visual_code, c.title, src.id_session
					FROM $tbl_course c, $tbl_session_rel_course src
					WHERE src.course_code = c.code
					AND src.id_session = '".intval($id_session)."'
					AND c.code IN ($list_courses_origin)";
			$rs = Database::query($sql, __FILE__, __LINE__);

			$course_list_destination = array();
			$return .= '<select id="destination" name="SessionCoursesListDestination[]" multiple="multiple" size="20" style="width:320px;" onmouseover="this.disabled=true;" onmouseout="this.disabled=false;">';
			while($course = Database :: fetch_array($rs)) {
				$course_list_destination[] = $course['code'];
				$course_title=str_replace("'","\'",$course_title);
				$return .= '<option value="'.$course['code'].'" title="'.htmlspecialchars($course['title'].' ('.$course['visual_code'].')',ENT_QUOTES).'">'.$course['title'].' ('.$course['visual_code'].')</option>';
			}
			$return .= '</select>';
			$_SESSION['course_list_destination'] = $course_list_destination;

			// send response by ajax
			$xajax_response -> addAssign('ajax_list_courses_destination','innerHTML',api_utf8_encode($return));

			// disable option from session courses list origin where if no the same con the destination
			$sql = "SELECT c.code, c.visual_code, c.title, src.id_session
					FROM $tbl_course c, $tbl_session_rel_course src
					WHERE src.course_code = c.code
					AND src.id_session = '".intval($session_origin)."'";
			$result = Database::query($sql, __FILE__, __LINE__);

			$return_option_disabled = '<select id="origin" name="SessionCoursesListOrigin[]" multiple="multiple" size="20" style="width:320px;" onclick="checkSelected(this.id,\'copy_option_2\',\'title_option2\',\'destination\')">';
			while($cours = Database :: fetch_array($result)) {
				$course_title=str_replace("'","\'",$course_title);
				if (count($course_list_destination) > 0) {
					if (!in_array($cours['code'],$course_list_destination)) {
						$return_option_disabled .= '<optgroup style="color:#ccc" label="'.$cours['title'].' ('.$cours['visual_code'].')" >'.$cours['title'].' ('.$cours['visual_code'].')</optgroup>';
					} else {
						$return_option_disabled .= '<option value="'.$cours['code'].'" title="'.htmlspecialchars($cours['title'].' ('.$cours['visual_code'].')',ENT_QUOTES).'">'.$cours['title'].' ('.$cours['visual_code'].')</option>';
					}
				} else {
					if (empty($id_session)) {
						$return_option_disabled .= '<option value="'.$cours['code'].'" title="'.htmlspecialchars($cours['title'].' ('.$cours['visual_code'].')',ENT_QUOTES).'">'.$cours['title'].' ('.$cours['visual_code'].')</option>';
					} else {
						$return_option_disabled .= '<optgroup style="color:#ccc" label="'.$cours['title'].'('.$cours['visual_code'].')" >'.$cours['title'].' ('.$cours['visual_code'].')</optgroup>';
					}

				}
			}
			$return_option_disabled .= '</select>';
			// send response by ajax
			$xajax_response -> addAssign('ajax_list_courses_origin','innerHTML',api_utf8_encode($return_option_disabled));
		}

	}
	return $xajax_response;
}
$xajax -> processRequests();

/* HTML head extra */

$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '<script language="javascript">

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


/*  MAIN CODE  */

if ((isset ($_POST['action']) && $_POST['action'] == 'course_select_form') || (isset ($_POST['copy_option']) && $_POST['copy_option'] == 'full_copy')) {

	$destination_course = $origin_course = $destination_session = $origin_session = '';

	if (isset ($_POST['action']) && $_POST['action'] == 'course_select_form') {

		$destination_course	 	= $_POST['destination_course'];
		$origin_course 			= $_POST['origin_course'];
		$destination_session 	= $_POST['destination_session'];
		$origin_session 		= $_POST['origin_session'];

		$course = CourseSelectForm :: get_posted_course('copy_course',$origin_session,$origin_course);
		$cr = new CourseRestorer($course);
		//$cr->set_file_option($_POST['same_file_name_option']);
		$cr->restore($destination_course,$destination_session);
		Display::display_normal_message(get_lang('CopyFinished'));
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

		if ((is_array($arr_course_origin) && count($arr_course_origin) > 0)  && !empty($destination_session)) {

			foreach ($arr_course_origin as $course_origin) {

				$cb = new CourseBuilder();
				$course = $cb->build($origin_session,$course_origin);
				$cr = new CourseRestorer($course);
				//$cr->set_file_option($_POST['same_file_name_option']);
				$cr->restore($course_origin,$destination_session);

			}
			Display::display_normal_message(get_lang('CopyFinished'));
			display_form();
		} else {
			Display::display_error_message(get_lang('YouMustSelectACourseFromOriginalSession'));
			display_form();
		}

	}

} elseif (isset ($_POST['copy_option']) && $_POST['copy_option'] == 'select_items') {

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
		$arr_course_destination 		= $_POST['SessionCoursesListDestination'];
	}
	if (isset($_POST['sessions_list_destination'])) {
		$destination_session 	= $_POST['sessions_list_destination'];
	}
	if (isset($_POST['sessions_list_origin'])) {
		$origin_session 		= $_POST['sessions_list_origin'];
	}

	if ((is_array($arr_course_origin) && count($arr_course_origin) > 0) && !empty($destination_session)) {
		Display::display_normal_message(get_lang('ToExportLearnpathWithQuizYouHaveToSelectQuiz'));
		$cb = new CourseBuilder();
		$course = $cb->build($origin_session,$arr_course_origin[0]);
		//$hidden_fields['same_file_name_option'] = $_POST['same_file_name_option'];
		$hidden_fields['destination_course'] 	= $arr_course_origin[0];
		$hidden_fields['origin_course'] 		= $arr_course_origin[0];
		$hidden_fields['destination_session'] 	= $destination_session;
		$hidden_fields['origin_session'] 		= $origin_session;
		CourseSelectForm :: display_form($course,$hidden_fields, true);
		echo '<div style="float:right"><a href="javascript:window.back();">'.Display::return_icon('back.png',get_lang('Back').' '.get_lang('To').' '.get_lang('PlatformAdmin'),array('style'=>'vertical-align:middle')).get_lang('Back').'</a></div>';
	} else {
		Display::display_error_message(get_lang('You must select a course from original session and select a destination session'));
		display_form();
	}

} else {
	display_form();
}

/*  FOOTER  */

Display::display_footer();
