<?php
/* For licensing terms, see /license.txt */

/**
 * Gradebook link to a survey item
 * @author Ivan Tcholakov <ivantcholakov@gmail.com>, 2010
 * @package chamilo.gradebook
 */

/**
 * Class
 * @package chamilo.gradebook
 */
class SurveyLink extends AbstractLink
{
    private $survey_table = null;

    function SurveyLink() {
    	$this->set_type(LINK_SURVEY);
    }

	public function get_name() {
    	$this->get_survey_data();
    	return $this->survey_data['code'].': '.self::html_to_text($this->survey_data['title']);
    }

    public function get_description() {
    	$this->get_survey_data();
    	return $this->survey_data['subtitle'];
    }

    public function get_type_name() {
    	return get_lang('Survey');
    }

	public function is_allowed_to_change_name() {
		return false;
	}

	public function needs_name_and_description() {
		return false;
	}

    public function needs_max() {
        return false;
    }

    public function needs_results() {
        return false;
    }

    /**
	 * Generates an array of all surveys available.
	 * @return array 2-dimensional array - every element contains 2 subelements (id, name)
	 */
    public function get_all_links() {
    	if (empty($this->course_code)) {
    		die('Error in get_all_links() : course code not set');
    	}
    	$tbl_survey = $this->get_survey_table();
    	$session_id = api_get_session_id();
    	$course_id = api_get_course_int_id();
    	$sql = 'SELECT survey_id, title, code FROM '.$tbl_survey.' WHERE c_id = '.$course_id.' AND session_id = '.intval($session_id).'';
		$result = Database::query($sql);
		while ($data = Database::fetch_array($result)) {
			$links[] = array($data['survey_id'], api_trunc_str($data['code'].': '.self::html_to_text($data['title']), 80));
		};
		return isset($links) ? $links : null;
    }

    /**
	 * Generates an array of surveys that a teacher hasn't created a link for.
	 * @return array 2-dimensional array - every element contains 2 subelements (id, name)
	 */
    public function get_not_created_links() {
    	if (empty($this->course_code)) {
    		die('Error in get_not_created_links() : course code not set');
    	}
    	$tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

    	$sql = 'SELECT survey_id, title, code
    			FROM '.$this->get_survey_table().' AS srv
				WHERE survey_id NOT IN
					(SELECT ref_id FROM '.$tbl_grade_links.'
					WHERE type = '.LINK_SURVEY." AND course_code = '".$this->get_course_code()."'"
				.') AND srv.session_id='.api_get_session_id().'';
		$result = Database::query($sql);

		$links = array();
		while ($data = Database::fetch_array($result)) {
			$links[] = array($data['survey_id'], api_trunc_str($data['code'].': '.self::html_to_text($data['title']), 80));
		}
		return $links;
    }

    /**
     * Has anyone done this survey yet?
     */
    public function has_results($stud_id=null) {
    	$course_info = Database :: get_course_info($this->get_course_code());
		$database_name = (empty($course_info['db_name'])) ? $course_info['dbName'] : $course_info['db_name'];

		if ($database_name != '') {
			$ref_id = intval($this->get_ref_id());
			$session_id = api_get_session_id();
			$tbl_survey = Database::get_course_table(TABLE_SURVEY, $database_name);
			$tbl_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION, $database_name);

			$get_individual_score = !is_null($stud_id);

			$sql = "SELECT COUNT(i.answered) FROM $tbl_survey AS s JOIN $tbl_survey_invitation AS i ON s.code = i.survey_code
					WHERE 	s.c_id = {$course_info['real_id']} AND
							i.c_id = {$course_info['real_id']} AND 
							s.survey_id = $ref_id AND 
							i.session_id = $session_id";

			$sql_result = Database::query($sql);
			$data = Database::fetch_array($sql_result);
			return ($data[0] != 0);
		}

		return false;
    }

    public function calc_score($stud_id = null) {

    	// Note: Max score is assumed to be always 1 for surveys,
    	// only student's participation is to be taken into account.
    	$max_score = 1;

    	$course_info = Database :: get_course_info($this->get_course_code());
		$database_name = (empty($course_info['db_name'])) ? $course_info['dbName'] : $course_info['db_name'];

		if ($database_name != '') {
			$ref_id = intval($this->get_ref_id());
			$session_id = api_get_session_id();
			$tbl_survey = Database::get_course_table(TABLE_SURVEY, $database_name);
			$tbl_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION, $database_name);

			$get_individual_score = !is_null($stud_id);

			$sql = "SELECT i.answered FROM $tbl_survey AS s JOIN $tbl_survey_invitation AS i
				ON s.code = i.survey_code
				WHERE 	s.c_id = {$course_info['real_id']} AND
						i.c_id = {$course_info['real_id']} AND 
						s.survey_id = $ref_id AND i.session_id = $session_id";

			if ($get_individual_score) {
	    		$sql .= ' AND i.user = '.intval($stud_id);
	    	}

			$sql_result = Database::query($sql);

			if ($get_individual_score) {

				// for 1 student

				if ($data = Database::fetch_array($sql_result)) {
					return array ($data['answered'] ? $max_score : 0, $max_score);
				}
				return array(0, $max_score);

			} else {

				// for all the students -> get average

				$students = array();
				$rescount = 0;
				$sum = 0;

				while ($data = Database::fetch_array($sql_result)) {
					$sum += $data['answered'] ? $max_score : 0;
					$rescount++;
				}
				$sum = $sum / $max_score;

				if ($rescount == 0) {
					return null;
				}
				return array($sum, $rescount);
			}
		}

		return null;
    }

    /**
     * Lazy load function to get the database table of the surveys
     */
    private function get_survey_table() {
    	$this->survey_table = Database :: get_course_table(TABLE_SURVEY);
   		return $this->survey_table;		
    }

    /**
     * Check if this still links to a survey
     */
    public function is_valid_link() {
    	$session_id = api_get_session_id();
        $sql = 'SELECT count(survey_id) FROM '.$this->get_survey_table().'
        		 WHERE survey_id = '.intval($this->get_ref_id()).' AND session_id='.intval($session_id).'';
        $result = Database::query($sql);
        $number = Database::fetch_row($result);
        return ($number[0] != 0);
    }

    public function get_test_id() {
    	return 'DEBUG:ID';
    }

    public function get_link() {
   		if (api_is_allowed_to_create_course()) { // Let students make access only through "Surveys" tool.
   			$tbl_name = $this->get_survey_table();
   			$session_id = api_get_session_id();
   			if ($tbl_name != '') {
   				$sql = 'SELECT survey_id FROM '.$this->get_survey_table().'
    				WHERE survey_id = '.intval($this->get_ref_id()).' AND session_id = '.intval($session_id).' ';
   				$result = Database::query($sql);
   				$row = Database::fetch_array($result, 'ASSOC');
   				$survey_id = $row['survey_id'];
   				return api_get_path(WEB_PATH).'main/survey/reporting.php?cidReq='.$this->get_course_code().'&survey_id='.$survey_id;
   			}
   		}
   		return null;
    }

	private function get_survey_data() {
		$tbl_name = $this->get_survey_table();
		$session_id = api_get_session_id();
		if ($tbl_name == '') {
			return false;
		} elseif (!isset($this->survey_data)) {
			$sql = 'SELECT * FROM '.$tbl_name.' WHERE survey_id = '.intval($this->get_ref_id()).' AND session_id='.intval($session_id).'';
			$query = Database::query($sql);
			$this->survey_data = Database::fetch_array($query);
    	}
    	return $this->survey_data;
    }

    public function get_icon_name() {
		return 'survey';
	}

	private static function html_to_text($string) {
		return trim(api_html_entity_decode(strip_tags(str_ireplace(array('<p>', '</p>', '<br />', '<br/>', '<br>'), array('', ' ', ' ', ' ', ' '), $string)), ENT_QUOTES));
	}

}
?>
