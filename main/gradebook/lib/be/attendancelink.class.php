<?php
/* For licensing terms, see /license.txt */
/**
 * Gradebook link to attendance item
 * @author Christian Fasanando (christian1827@gmail.com)
 * @package chamilo.gradebook
 */
/**
 * Class
 * @package chamilo.gradebook
 */
class AttendanceLink extends AbstractLink
{

// INTERNAL VARIABLES

    private $attendance_table = null;
    private $itemprop_table = null;


// CONSTRUCTORS

    function __construct() {
    	parent::__construct();
    	$this->set_type(LINK_ATTENDANCE);    	
    }

    public function get_type_name() {
    	return get_lang('Attendance');
    }


	public function is_allowed_to_change_name() {
		return false;
	}


// FUNCTIONS IMPLEMENTING ABSTRACTLINK

	/**
	 * Generate an array of attendances that a teacher hasn't created a link for.
	 * @return array 2-dimensional array - every element contains 2 subelements (id, name)
	 */
    public function get_not_created_links() {
    	if (empty($this->course_code)) {
    		die('Error in get_not_created_links() : course code not set');
    	}
    	$tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
    	
		$sql = 'SELECT att.id, att.name, att.attendance_qualify_title
				FROM '.$this->get_attendance_table().' att
				WHERE att.c_id = '.$this->course_id.' AND att.id NOT IN (SELECT ref_id FROM '.$tbl_grade_links.' WHERE type = '.LINK_ATTENDANCE.' AND course_code = "'.Database::escape_string($this->get_course_code()).'")
				AND att.session_id='.api_get_session_id().'';
		$result = Database::query($sql);

		$cats=array();
		while ($data=Database::fetch_array($result)) {
			if ( isset($data['attendance_qualify_title']) && $data['attendance_qualify_title'] != ''){
				$cats[] = array ($data['id'], $data['attendance_qualify_title']);
			} else {
				$cats[] = array ($data['id'], $data['name']);
			}
		}
		return $cats;
    }

	/**
	 * Generate an array of all attendances available.
	 * @return array 2-dimensional array - every element contains 2 subelements (id, name)
	 */
    public function get_all_links() {
    	if (empty($this->course_code)) {
    		die('Error in get_not_created_links() : course code not set');
    	}
    	$tbl_attendance = $this->get_attendance_table();
    	$session_id = api_get_session_id();
    	$sql = 'SELECT att.id, att.name, att.attendance_qualify_title 
    			FROM '.$tbl_attendance.' att 
    			WHERE att.c_id = '.$this->course_id.' AND att.active = 1 AND att.session_id = '.intval($session_id).'';
		$result = Database::query($sql);
		while ($data=Database::fetch_array($result)) {
			if (isset($data['attendance_qualify_title']) && $data['attendance_qualify_title'] != ''){
				$cats[] = array ($data['id'], $data['attendance_qualify_title']);
			} else {
				$cats[] = array ($data['id'], $data['name']);
			}
		}
		$my_cats = isset($cats)?$cats:null;
		return $my_cats;
    }


    /**
     * Has anyone done this exercise yet ?
     */
    public function has_results() {    	
    	$tbl_attendance_result = Database :: get_course_table(TABLE_ATTENDANCE_RESULT);
		$sql = 'SELECT count(*) AS number FROM '.$tbl_attendance_result." 
				WHERE c_id = '.$this->course_id.' AND attendance_id = '".intval($this->get_ref_id())."'";
    	$result = Database::query($sql);
		$number = Database::fetch_row($result);
		return ($number[0] != 0);
    }

  	public function calc_score($stud_id = null) {    	    	
		$tbl_attendance_result = Database::get_course_table(TABLE_ATTENDANCE_RESULT);
		$session_id = api_get_session_id();

		// get attendance qualify max
  		$sql = 'SELECT att.attendance_qualify_max FROM '.$this->get_attendance_table().' att 
  				WHERE att.c_id = '.$this->course_id.' AND att.id = '.intval($this->get_ref_id()).' AND att.session_id='.intval($session_id).'';
		$query = Database::query($sql);
		$attendance = Database::fetch_array($query);

		// get results
  	    $sql = 'SELECT * FROM '.$tbl_attendance_result.' 
  	    		WHERE c_id = '.$this->course_id.' AND attendance_id = '.intval($this->get_ref_id());
    	if (isset($stud_id)) {
    		$sql .= ' AND user_id = '.intval($stud_id);
    	}
    	$scores = Database::query($sql);
		// for 1 student
    	if (isset($stud_id)) {    		
    		if ($data = Database::fetch_array($scores)) {
    			return array ($data['score'], $attendance['attendance_qualify_max']);
    		} else {
    			//We sent the 0/attendance_qualify_max instead of null for correct calculations
      			return array (0, $attendance['attendance_qualify_max']);
    		}
    	} else {// all students -> get average
    		$students=array();  // user list, needed to make sure we only
    							// take first attempts into account
			$rescount = 0;
			$sum = 0;
			while ($data=Database::fetch_array($scores)) {
				if (!(array_key_exists($data['user_id'],$students))) {
					if ($attendance['attendance_qualify_max'] != 0) {
						$students[$data['user_id']] = $data['score'];
						$rescount++;
						$sum += ($data['score'] / $attendance['attendance_qualify_max']);
					}
				}
			}
			if ($rescount == 0) {
				return null;
			  } else {
				return array ($sum , $rescount);
			 }
    	}		
    }

	// INTERNAL FUNCTIONS

    /**
     * Lazy load function to get the database table of the student publications
     */
    private function get_attendance_table() {
    	$this->attendance_table = Database :: get_course_table(TABLE_ATTENDANCE);
    	return $this->attendance_table;
    }

    /**
     * Lazy load function to get the database table of the item properties
     */
    private function get_itemprop_table () {    	
    	$this->itemprop_table = Database :: get_course_table(TABLE_ITEM_PROPERTY);
   		return $this->itemprop_table;
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

    public function get_name() {
    	$this->get_attendance_data();
    	$attendance_title = isset($this->attendance_data['name']) ? $this->attendance_data['name'] : '';
    	$attendance_qualify_title = isset($this->attendance_data['attendance_qualify_title']) ? $this->attendance_data['attendance_qualify_title'] : '';
    	if ( isset($attendance_qualify_title) && $attendance_qualify_title != '') {
    		return $this->attendance_data['attendance_qualify_title'];
    	} else {
    		return $attendance_title;
    	}
    }

    public function get_description() {
    	return '';
    }
    /**
     * Check if this still links to an exercise
     */
    public function is_valid_link() {
    	$session_id = api_get_session_id();
        $sql = 'SELECT count(att.id) FROM '.$this->get_attendance_table().' att
        		 WHERE att.c_id = '.$this->course_id.' AND att.id = '.intval($this->get_ref_id()).' AND att.session_id='.intval($session_id).'';
        $result = Database::query($sql);
        $number = Database::fetch_row($result);
        return ($number[0] != 0);
    }

    public function get_test_id() {
    	return 'DEBUG:ID';
    }

    public function get_link() {
    	//it was extracts the attendance id
   		$tbl_name = $this->get_attendance_table();
   		$session_id = api_get_session_id();
   		if ($tbl_name != '') {
    	$sql = 'SELECT * FROM '.$this->get_attendance_table().' att
    			WHERE att.c_id = '.$this->course_id.' AND att.id = '.intval($this->get_ref_id()).' AND att.session_id = '.intval($session_id).' ';
		$result = Database::query($sql);
		$row    = Database::fetch_array($result,'ASSOC');
		$attendance_id = $row['id'];
    	$url = api_get_path(WEB_PATH).'main/attendance/index.php?action=attendance_sheet_list&gradebook=view&attendance_id='.$attendance_id.'&cidReq='.$this->get_course_code();
		return $url;
   		}
	}

	private function get_attendance_data() {
		$tbl_name = $this->get_attendance_table();
		$session_id = api_get_session_id();
		if ($tbl_name == '') {
			return false;
		} elseif (!isset($this->attendance_data)) {
			$sql = 'SELECT * FROM '.$this->get_attendance_table().' att 
					WHERE att.c_id = '.$this->course_id.' AND att.id = '.intval($this->get_ref_id()).' AND att.session_id='.intval($session_id).'';
			$query = Database::query($sql);
			$this->attendance_data = Database::fetch_array($query);
    	}
    	return $this->attendance_data;
    }

    public function get_icon_name() {
		return 'attendance';
	}
}