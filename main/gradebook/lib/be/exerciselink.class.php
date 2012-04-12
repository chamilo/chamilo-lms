<?php
/* For licensing terms, see /license.txt */
/**
 * Defines a gradebook ExerciseLink object.
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
/**
 * Class
 * @package chamilo.gradebook
 */
class ExerciseLink extends AbstractLink
{
    // INTERNAL VARIABLES
    private $course_info = null;
    private $exercise_table = null;
    private $exercise_data = null;

    // CONSTRUCTORS
    function __construct() {
    	parent::__construct();
    	$this->set_type(LINK_EXERCISE);
    }
    
    // FUNCTIONS IMPLEMENTING ABSTRACTLINK

	/**
	 * Generate an array of exercises that a teacher hasn't created a link for.
	 * @return array 2-dimensional array - every element contains 2 subelements (id, name)
	 */
    public function get_not_created_links() {
        return false;
    	if (empty($this->course_code)) {
            die('Error in get_not_created_links() : course code not set');
    	}
    	$tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

		$sql = 'SELECT id, title from '.$this->get_exercise_table().' exe 
                WHERE id NOT IN (
                        SELECT ref_id FROM '.$tbl_grade_links.' 
                              WHERE type        = '.LINK_EXERCISE." AND 
                                    course_code = '".$this->get_course_code()."'
                        ) AND                
                exe.c_id        = ".$this->course_id;

		$result = Database::query($sql);
		$cats = array();
		while ($data=Database::fetch_array($result)) {
			$cats[] = array ($data['id'], $data['title']);
		}
		return $cats;
    }
    
	/**
	 * Generate an array of all exercises available.
	 * @return array 2-dimensional array - every element contains 2 subelements (id, name)
	 */
    public function get_all_links() {
    	if (empty($this->course_code)) {
    		die('Error in get_not_created_links() : course code not set');
    	}    	    	
        $session_id = api_get_session_id();
        if (empty($session_id)) {
            $session_condition = api_get_session_condition(0, true);
        } else {
            $session_condition = api_get_session_condition($session_id, true, true);
        }        
        
		$sql = 'SELECT id,title from '.$this->get_exercise_table().' 
				WHERE c_id = '.$this->course_id.' AND active=1  '.$session_condition;        
        
        
  /*      $sql = 'SELECT id,title from '.$this->get_exercise_table().' 
				WHERE c_id = '.$this->course_id.' AND active=1 AND session_id='.api_get_session_id().'';
	*/	
		$result = Database::query($sql);
		$cats = array();
		while ($data=Database::fetch_array($result)) {
			$cats[] = array ($data['id'], $data['title']);
		}
		return $cats;
    }

    /**
     * Has anyone done this exercise yet ?
     */
    public function has_results() {
    	$tbl_stats = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
        $session_id = api_get_session_id();
		$sql = 'SELECT count(exe_id) AS number FROM '.$tbl_stats." 
                    WHERE   session_id = $session_id AND 
                            exe_cours_id = '".$this->get_course_code()."'".' AND 
                            exe_exo_id   = '.(int)$this->get_ref_id();
    	$result = Database::query($sql);
		$number=Database::fetch_row($result);
		return ($number[0] != 0);
    }

    /**
	 * Get the score of this exercise. Only the first attempts are taken into account.
	 * @param $stud_id student id (default: all students who have results - then the average is returned)
	 * @return	array (score, max) if student is given
	 * 			array (sum of scores, number of scores) otherwise
	 * 			or null if no scores available
	 */
    public function calc_score($stud_id = null) {
    	$tbl_stats = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
    	//$tbl_stats_e_attempt_recording = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
        //the following query should be similar (in conditions) to the one used in exercice/exercice.php, look for note-query-exe-results marker
        $session_id = api_get_session_id();
		$sql = "SELECT * FROM $tbl_stats
                WHERE   exe_exo_id      = ".intval($this->get_ref_id())." AND 
                        orig_lp_id      = 0 AND 
                        orig_lp_item_id = 0 AND 
                        status      <> 'incomplete' AND
                        session_id = $session_id                            
                ";

		if (isset($stud_id)) {
    		$course_code_exe = $this->get_course_code();
    		$sql .= " AND exe_cours_id = '$course_code_exe' AND 
                      exe_user_id = '$stud_id' ";
    	}
		$sql .= ' ORDER BY exe_id DESC';
        $scores = Database::query($sql);
        
    	if (isset($stud_id)) {
    		// for 1 student
    		if ($data = Database::fetch_array($scores)) {
    			return array ($data['exe_result'], $data['exe_weighting']);
       		} else {
                return null;
       		}
    	} else {
            /// all students -> get average
    		// normal way of getting the info

    		$students = array();  // user list, needed to make sure we only
    							// take first attempts into account
			$student_count = 0;
			$sum = 0;
			while ($data = Database::fetch_array($scores, 'ASSOC')) {
				if (!in_array($data['exe_user_id'], $students)) {
					if ($data['exe_weighting'] != 0) {
						$students[] = $data['exe_user_id'];
						$student_count++;                        
						$sum += $data['exe_result'] / $data['exe_weighting'];
					}
				}
			}         

			if ($student_count == 0) {
				return null;
			} else {
				return array ($sum , $student_count);
			}
    	}
    }

    /**
     * Get URL where to go to if the user clicks on the link.
     * First we go to exercise_jump.php and then to the result page.
     * Check this php file for more info.
     */
	public function get_link() {
		//status student
		$user_id = api_get_user_id();
		$course_code = $this->get_course_code();
		$status_user=api_get_status_of_user_in_course ($user_id, $course_code);
        $session_id =api_get_session_id();
		$url = api_get_path(WEB_PATH).'main/gradebook/exercise_jump.php?session_id='.$session_id.'&cidReq='.$this->get_course_code().'&gradebook=view&exerciseId='.$this->get_ref_id();
		if ((!api_is_allowed_to_edit() && $this->calc_score(api_get_user_id()) == null) || $status_user!=1) {
			$url .= '&amp;doexercise='.$this->get_ref_id();
        }
		return $url;
	}

    /**
     * Get name to display: same as exercise title
     */
    public function get_name() {
    	$data = $this->get_exercise_data();        
    	return $data['title'];
    }

    /**
     * Get description to display: same as exercise description
     */
    public function get_description() {
    	$data = $this->get_exercise_data();
    	return $data['description'];
    }

    /**
     * Check if this still links to an exercise
     */
    public function is_valid_link() {
    	//$sql = 'SELECT count(id) from '.$this->get_exercise_table().' WHERE c_id = '.$this->course_id.' AND id = '.(int)$this->get_ref_id().' AND session_id='.api_get_session_id().'';
        $sql = 'SELECT count(id) from '.$this->get_exercise_table().' WHERE c_id = '.$this->course_id.' AND id = '.(int)$this->get_ref_id().' ';
		$result = Database::query($sql);
		$number=Database::fetch_row($result);
		return ($number[0] != 0);
    }

    public function get_type_name() {
    	return get_lang('Quiz');
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

	public function is_allowed_to_change_name() {
		return false;
	}


    // INTERNAL FUNCTIONS

    /**
     * Lazy load function to get the database table of the exercise
     */
    private function get_exercise_table () {
    	$this->exercise_table = Database :: get_course_table(TABLE_QUIZ_TEST);
    	return $this->exercise_table;   		
    }

    /**
     * Lazy load function to get the database contents of this exercise
     */
    private function get_exercise_data() {
    	$tbl_exercise = $this->get_exercise_table();
    	if ($tbl_exercise=='') {
    		return false;
    	} elseif (!isset($this->exercise_data)) {
            $sql = 'SELECT * FROM '.$this->get_exercise_table().' 
					WHERE c_id = '.$this->course_id.' AND id = '.(int)$this->get_ref_id().' ';
			
			$result = Database::query($sql);
			$this->exercise_data=Database::fetch_array($result);
    	}
    	return $this->exercise_data;
    }

    public function get_icon_name() {
		return 'exercise';
	}
}