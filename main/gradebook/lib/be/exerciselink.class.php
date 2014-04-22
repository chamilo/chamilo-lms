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
    private $is_hp;

    /**
     * @param int $hp
     */
    public function __construct($hp = 0)
    {
    	parent::__construct();
    	$this->set_type(LINK_EXERCISE);
        $this->is_hp = $hp;
        if ($this->is_hp == 1) {
            $this->set_type(LINK_HOTPOTATOES);
        }
    }

	/**
	 * Generate an array of exercises that a teacher hasn't created a link for.
	 * @return array 2-dimensional array - every element contains 2 subelements (id, name)
	 */
    public function get_not_created_links()
    {
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
    public function get_all_links()
    {
        $TBL_DOCUMENT = Database :: get_course_table(TABLE_DOCUMENT);
        $TBL_ITEM_PROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY);
        $documentPath = api_get_path(SYS_COURSE_PATH).$this->course_code."/document";
        if (empty($this->course_code)) {
            die('Error in get_not_created_links() : course code not set');
        }
        $session_id = api_get_session_id();
        if (empty($session_id)) {
            $session_condition = api_get_session_condition(0, true);
        } else {
            $session_condition = api_get_session_condition($session_id, true, true);
        }

        // @todo
        $uploadPath = null;

		$sql = 'SELECT id,title from '.$this->get_exercise_table().'
				WHERE c_id = '.$this->course_id.' AND active=1  '.$session_condition;

        $sql2 = "SELECT d.path as path, d.comment as comment, ip.visibility as visibility, d.id
                FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip
                WHERE d.c_id = $this->course_id AND
                      ip.c_id = $this->course_id AND
                d.id = ip.ref AND ip.tool = '".TOOL_DOCUMENT."' AND (d.path LIKE '%htm%')AND (d.path LIKE '%HotPotatoes_files%')
                AND   d.path  LIKE '".Database :: escape_string($uploadPath)."/%/%' AND ip.visibility='1'";

        /*
        $sql = 'SELECT id,title from '.$this->get_exercise_table().'
				WHERE c_id = '.$this->course_id.' AND active=1 AND session_id='.api_get_session_id().'';
        */
        require_once api_get_path(SYS_CODE_PATH).'exercice/hotpotatoes.lib.php';
		if (!$this->is_hp) {
            $result = Database::query($sql);
        } else {
            $result2 = Database::query($sql2);
        }
		$cats = array();
        if (isset($result)) {
            if (Database::num_rows($result) > 0) {
                while ($data=Database::fetch_array($result)) {
                    $cats[] = array ($data['id'], $data['title']);
                }
            }
        }
        if (isset($result2)) {
            if (Database::num_rows($result2) > 0) {
        		while ($row=Database::fetch_array($result2)) {
                    /*$path = $data['path'];
                    $fname = GetQuizName($path,$documentPath);
        			$cats[] = array ($data['id'], $fname);*/
                    $attribute['path'][] = $row['path'];
                    $attribute['visibility'][] = $row['visibility'];
                    $attribute['comment'][] = $row['comment'];
                    $attribute['id'] = $row['id'];

                    if (isset($attribute['path']) && is_array($attribute['path'])) {
                        $hotpotatoes_exist = true;
                        while (list($key, $path) = each($attribute['path'])) {
                            $item = '';
                            $title = GetQuizName($path, $documentPath);
                            if ($title == '') {
                                $title = basename($path);
                            }
                            $cats[] = array ($attribute['id'], $title.'(HP)');
                        }
                    }
                }
            }
        }
		return $cats;
    }

    /**
     * Has anyone done this exercise yet ?
     */
    public function has_results()
    {
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
    public function calc_score($stud_id = null)
    {
        $tblStats = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
        $tblHp = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);
        $tblDoc = Database::get_course_table(TABLE_DOCUMENT);

        /* the following query should be similar (in conditions) to the one used
        in exercice/exercice.php, look for note-query-exe-results marker*/
        $session_id = api_get_session_id();
        if (!$this->is_hp) {
            $sql = "SELECT * FROM $tblStats
                    WHERE
                        exe_exo_id      = ".intval($this->get_ref_id())." AND
                        orig_lp_id      = 0 AND
                        orig_lp_item_id = 0 AND
                        status      <> 'incomplete' AND
                        session_id = $session_id";

    	    if (isset($stud_id)) {
                $course_code_exe = $this->get_course_code();
                $sql .= " AND exe_cours_id = '$course_code_exe' AND exe_user_id = '$stud_id' ";
            }
            $sql .= ' ORDER BY exe_id DESC';

        } else {
            $course_code_exe = $this->get_course_code();
            $courseId =  $this->getCourseId();
             $sql = "SELECT * FROM $tblHp hp, $tblDoc doc
                     WHERE
                        hp.exe_cours_id = '$course_code_exe' AND
                        hp.exe_user_id = '$stud_id'  AND
                        hp.exe_name = doc.path AND
                        doc.c_id = $courseId AND
                        doc.id = ".intval($this->get_ref_id())."";
        }

        $scores = Database::query($sql);

        if (isset($stud_id)) {
            // for 1 student
            if ($data = Database::fetch_array($scores)) {
                return array ($data['exe_result'], $data['exe_weighting']);
            } else {
                return null;
       	    }
        } else {
            // all students -> get average
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
    public function get_link()
    {
        //status student
        $user_id = api_get_user_id();
        $course_code = $this->get_course_code();
        $status_user=api_get_status_of_user_in_course ($user_id, $course_code);
        $session_id =api_get_session_id();

        $url = api_get_path(WEB_CODE_PATH).'gradebook/exercise_jump.php?session_id='.$session_id.'&cidReq='.$this->get_course_code().'&gradebook=view&exerciseId='.$this->get_ref_id().'&type='.$this->get_type();
        if ((!api_is_allowed_to_edit() && $this->calc_score(api_get_user_id()) == null) || $status_user!=1) {
            $url .= '&amp;doexercise='.$this->get_ref_id();
        }
        return $url;
    }

    /**
     * Get name to display: same as exercise title
     */
    public function get_name()
    {
        $documentPath = api_get_path(SYS_COURSE_PATH).$this->course_code."/document";
        require_once api_get_path(SYS_CODE_PATH).'exercice/hotpotatoes.lib.php';
        $data = $this->get_exercise_data();
        if ($this->is_hp == 1) {
            if (isset($data['path'])) {
                $hotpotatoes_exist = true;
                $item = '';
                $title = GetQuizName($data['path'], $documentPath);
                if ($title == '') {
                    $title = basename($data['path']);
                }
                return $title;
            }
        }
    	return $data['title'];
    }

    /**
     * Get description to display: same as exercise description
     */
    public function get_description()
    {
    	$data = $this->get_exercise_data();

    	return isset($data['description']) ? $data['description'] : null;
    }

    /**
     * Check if this still links to an exercise
     */
    public function is_valid_link()
    {
        $sql = 'SELECT count(id) from '.$this->get_exercise_table().'
                WHERE c_id = '.$this->course_id.' AND id = '.(int)$this->get_ref_id().' ';
		$result = Database::query($sql);
		$number=Database::fetch_row($result);
		return ($number[0] != 0);
    }

    /**
     * @return array|string
     */
    public function get_type_name()
    {
        if ($this->is_hp == 1) {
            return get_lang('HotPotatoes');
        } else {
            return get_lang('Quiz');
        }

    }

	public function needs_name_and_description()
    {
		return false;
	}

	public function needs_max()
    {
		return false;
	}

	public function needs_results()
    {
		return false;
	}

	public function is_allowed_to_change_name() {
		return false;
	}

    /**
     * Lazy load function to get the database table of the exercise
     */
    private function get_exercise_table()
    {
        $this->exercise_table = Database :: get_course_table(TABLE_QUIZ_TEST);
        return $this->exercise_table;
    }

    /**
     * Lazy load function to get the database contents of this exercise
     */
    private function get_exercise_data()
    {
        if ($this->is_hp == 1) {
           $tbl_exercise = Database :: get_course_table(TABLE_DOCUMENT);
           $TBL_ITEM_PROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY);
        } else {
            $tbl_exercise = $this->get_exercise_table();
        }
        if ($tbl_exercise=='') {
    		return false;
    	} elseif (!isset($this->exercise_data)) {
            if ($this->is_hp == 1) {
                $ref_id = intval($this->get_ref_id());
                $sql = "SELECT * FROM $tbl_exercise ex, $TBL_ITEM_PROPERTY ip
                        WHERE
                            ip.ref = ex.id AND
                            ip.c_id = $this->course_id AND
                            ex.c_id = $this->course_id AND
                            ip.ref = $ref_id AND
                            ip.tool = '".TOOL_DOCUMENT."' AND
                            ex.path LIKE '%htm%' AND
                            ex.path LIKE '%HotPotatoes_files%' AND
                            ip.visibility = 1";
            } else {
                $sql = 'SELECT * FROM '.$tbl_exercise.'
                        WHERE
                            c_id = '.$this->course_id.' AND
                            id = '.(int)$this->get_ref_id().' ';
            }
			$result = Database::query($sql);
			$this->exercise_data=Database::fetch_array($result);
    	}
    	return $this->exercise_data;
    }

    /**
     * @return string
     */
    public function get_icon_name()
    {
		return 'exercise';
	}
}
