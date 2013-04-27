<?php
/* For licensing terms, see /license.txt */
/**
 * Definition of the AddManySessionToCategoryFunctions class
 * @package chamilo.library
 */
/**
 * Init
 */
require_once dirname(__FILE__).'/xajax/xajax.inc.php';
/**
 * AddManySessionToCategoryFunctions class
 */
class AddManySessionToCategoryFunctions {
    /**
     * Search for a session based on a given search string
     * @param string A search string
     * @param string A search box type (single or anything else)
     * @return string XajaxResponse
     * @assert () !== ''
     * @assert ('abc','single') !== ''
     */
    function search_courses($needle,$type) {

		global $tbl_course, $tbl_session, $id_session;
		$xajax_response = new XajaxResponse();
		$return = '';
		if(!empty($needle) && !empty($type)) {
			// xajax send utf8 datas... datas in db can be non-utf8 datas
			$charset = api_get_system_encoding();
			$needle = api_convert_encoding($needle, $charset, 'utf-8');
			$needle = Database::escape_string($needle);

			$sql = 'SELECT * FROM '.$tbl_session.' WHERE name LIKE "'.$needle.'%" ORDER BY id';

			$rs = Database::query($sql);
			$course_list = array();

			$return .= '<select id="origin" name="NoSessionCategoryList[]" multiple="multiple" size="20" style="width:340px;">';
			while($course = Database :: fetch_array($rs)) {
				$course_list[] = $course['id'];
				$return .= '<option value="'.$course['id'].'" title="'.htmlspecialchars($course['name'],ENT_QUOTES).'">'.$course['name'].'</option>';
			}
			$return .= '</select>';
			$xajax_response -> addAssign('ajax_list_courses_multiple','innerHTML',api_utf8_encode($return));
		}
		$_SESSION['course_list'] = $course_list;
		return $xajax_response;
	}
}
