<?php
/* For licensing terms, see /license.txt */

require_once (api_get_path(LIBRARY_PATH).'xajax/xajax.inc.php');
//require_once (api_get_path(SYS_CODE_PATH).'admin/add_courses_to_session.php');


class AddCourseToSession {

	public function search_courses($needle,$type) {
		global $tbl_course, $tbl_session_rel_course, $id_session;

		$xajax_response = new XajaxResponse();
		$return = '';
		if(!empty($needle) && !empty($type)) {
			// xajax send utf8 datas... datas in db can be non-utf8 datas
			$charset = api_get_system_encoding();
			$needle = api_convert_encoding($needle, $charset, 'utf-8');

			$cond_course_code = '';
			if (!empty($id_session)) {
			$id_session = Database::escape_string($id_session);
				// check course_code from session_rel_course table
				$sql = 'SELECT course_code FROM '.$tbl_session_rel_course.' WHERE id_session ="'.(int)$id_session.'"';
				$res = Database::query($sql);
				$course_codes = '';
				if (Database::num_rows($res) > 0) {
					while ($row = Database::fetch_row($res)) {
						$course_codes .= '\''.$row[0].'\',';
					}
					$course_codes = substr($course_codes,0,(strlen($course_codes)-1));

					$cond_course_code = ' AND course.code NOT IN('.$course_codes.') ';
				}
			}

			if ($type=='single') {
			// search users where username or firstname or lastname begins likes $needle
			$sql = 'SELECT course.code, course.visual_code, course.title, session_rel_course.id_session
					FROM '.$tbl_course.' course
					LEFT JOIN '.$tbl_session_rel_course.' session_rel_course
						ON course.code = session_rel_course.course_code
						AND session_rel_course.id_session = '.intval($id_session).'
					WHERE course.visual_code LIKE "'.$needle.'%"
					OR course.title LIKE "'.$needle.'%"';
			} else {

			$sql = 'SELECT course.code, course.visual_code, course.title
					FROM '.$tbl_course.' course
					WHERE course.visual_code LIKE "'.$needle.'%" '.$cond_course_code.' ORDER BY course.code ';
			}

			global $_configuration;
			if ($_configuration['multiple_access_urls']) {
				$tbl_course_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
				$access_url_id = api_get_current_access_url_id();
				if ($access_url_id != -1){

					if ($type=='single') {
						$sql = 'SELECT course.code, course.visual_code, course.title, session_rel_course.id_session
								FROM '.$tbl_course.' course
								LEFT JOIN '.$tbl_session_rel_course.' session_rel_course
									ON course.code = session_rel_course.course_code
									AND session_rel_course.id_session = '.intval($id_session).'
								INNER JOIN '.$tbl_course_rel_access_url.' url_course ON (url_course.course_code=course.code)
								WHERE access_url_id = '.$access_url_id.' AND (course.visual_code LIKE "'.$needle.'%"
								OR course.title LIKE "'.$needle.'%" )';
					} else {
						$sql = 'SELECT course.code, course.visual_code, course.title
								FROM '.$tbl_course.' course, '.$tbl_course_rel_access_url.' url_course
								WHERE url_course.course_code=course.code AND access_url_id = '.$access_url_id.'
								AND course.visual_code LIKE "'.$needle.'%" '.$cond_course_code.' ORDER BY course.code ';
					}
				}
			}

			$rs = Database::query($sql);
			$course_list = array();
			if ($type=='single') {

				while($course = Database :: fetch_array($rs)) {
					$course_list[] = $course['code'];
					$course_title=str_replace("'","\'",$course_title);
					$return .= '<a href="javascript: void(0);" onclick="javascript: add_course_to_session(\''.$course['code'].'\',\''.$course_title.' ('.$course['visual_code'].')'.'\')">'.$course['title'].' ('.$course['visual_code'].')</a><br />';
				}

				$xajax_response -> addAssign('ajax_list_courses_single','innerHTML',api_utf8_encode($return));

			} else {

				$return .= '<select id="origin" name="NoSessionCoursesList[]" multiple="multiple" size="20" style="width:340px;">';
				while($course = Database :: fetch_array($rs)) {
					$course_list[] = $course['code'];
					$course_title=str_replace("'","\'",$course_title);
					$return .= '<option value="'.$course['code'].'" title="'.htmlspecialchars($course['title'].' ('.$course['visual_code'].')',ENT_QUOTES).'">'.$course['title'].' ('.$course['visual_code'].')</option>';
				}
				$return .= '</select>';

				$xajax_response -> addAssign('ajax_list_courses_multiple','innerHTML',api_utf8_encode($return));
			}
		}
		$_SESSION['course_list'] = $course_list;
		return $xajax_response;
	}
}
?>
