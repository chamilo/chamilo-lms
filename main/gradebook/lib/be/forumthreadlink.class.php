<?php
/* For licensing terms, see /license.txt */
/**
 * Gradebook link to student publication item
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
/**
 * Class
 * @package chamilo.gradebook
 */
class ForumThreadLink extends AbstractLink
{

    // INTERNAL VARIABLES
    private $forum_thread_table = null;
    private $itemprop_table = null;

    function __construct() {
    	parent::__construct();
    	$this->set_type(LINK_FORUM_THREAD);
    }

    public function get_type_name() {
    	return get_lang('ForumThreads');
    }

	public function is_allowed_to_change_name() {
		return false;
	}

    // FUNCTIONS IMPLEMENTING ABSTRACTLINK

	/**
	 * Generate an array of exercises that a teacher hasn't created a link for.
	 * @return array 2-dimensional array - every element contains 2 subelements (id, name)
	 */
    public function get_not_created_links() {
    	if (empty($this->course_code)) {
    		die('Error in get_not_created_links() : course code not set');
    	}
    	$tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

		$sql = 'SELECT thread_id,thread_title,thread_title_qualify from '.$this->get_forum_thread_table()
				.' forum_thread WHERE thread_id NOT IN'
				.' (SELECT ref_id FROM '.$tbl_grade_links
				.' WHERE type = '.LINK_FORUM_THREAD
				." AND course_code = '".Database::escape_string($this->get_course_code())."'"
				.') AND forum_thread.session_id='.api_get_session_id().'';

		$result = Database::query($sql);

		$cats=array();
		while ($data=Database::fetch_array($result)) {
			if ( isset($data['thread_title_qualify']) and $data['thread_title_qualify']!=""){
				$cats[] = array ($data['thread_id'], $data['thread_title_qualify']);
			} else {
				$cats[] = array ($data['thread_id'], $data['thread_title']);
			}
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
    	$tbl_grade_links 	= Database :: get_course_table(TABLE_FORUM_THREAD);
    	$tbl_item_property	= Database :: get_course_table(TABLE_ITEM_PROPERTY);
        $session_id = api_get_session_id();
        
        if ($session_id) {
            $session_condition = 'tl.session_id='.api_get_session_id();
        } else {
            $session_condition = '(tl.session_id = 0 OR tl.session_id IS NULL)';
        }
    	
		$sql = 'SELECT tl.thread_id, tl.thread_title, tl.thread_title_qualify 
				FROM '.$tbl_grade_links.' tl ,'.$tbl_item_property.' ip 
				WHERE 	tl.c_id 		= '.$this->course_id.' AND
						ip.c_id 		= '.$this->course_id.' AND 
						tl.thread_id	= ip.ref AND 
						ip.tool			= "forum_thread" AND 
						ip.visibility<>2 AND  '.$session_condition.' GROUP BY ip.ref ';
        
		$result = Database::query($sql);

		while ($data=Database::fetch_array($result)) {
			if ( isset($data['thread_title_qualify']) and $data['thread_title_qualify']!=""){
				$cats[] = array ($data['thread_id'], $data['thread_title_qualify']);
			} else {
				$cats[] = array ($data['thread_id'], $data['thread_title']);
			}
		}
		$my_cats=isset($cats)?$cats:null;
		return $my_cats;
    }


    /**
     * Has anyone done this exercise yet ?
     */
    public function has_results() {    	
    	$tbl_grade_links = Database :: get_course_table(TABLE_FORUM_POST);
		$sql = 'SELECT count(*) AS number FROM '.$tbl_grade_links." 
				WHERE c_id = ".$this->course_id." AND thread_id = '".$this->get_ref_id()."'";
    	$result = Database::query($sql);
		$number=Database::fetch_row($result);
		return ($number[0] != 0);
    }

    public function calc_score($stud_id = null) {    			
		if (!empty($database_name)) {
			$thread_qualify = Database :: get_course_table(TABLE_FORUM_THREAD_QUALIFY);

	  		$sql = 'SELECT thread_qualify_max FROM '.Database :: get_course_table(TABLE_FORUM_THREAD)." 
	  				WHERE c_id = ".$this->course_id." AND thread_id = '".$this->get_ref_id()."'";
			$query = Database::query($sql);
			$assignment = Database::fetch_array($query);

	  	    $sql = "SELECT * FROM $thread_qualify WHERE c_id = ".$this->course_id." AND thread_id = ".$this->get_ref_id();
	    	if (isset($stud_id)) {
	    		$sql .= ' AND user_id = '."'".intval($stud_id)."'";
	    	}

	    	// order by id, that way the student's first attempt is accessed first
			$sql .= ' ORDER BY qualify_time DESC';

	    	$scores = Database::query($sql);

			// for 1 student
	    	if (isset($stud_id)) {
	    	    
	    		if ($data = Database::fetch_array($scores)) {	    		    
	    			return array ($data['qualify'], $assignment['thread_qualify_max']);
	    		} else {
	    			//We sent the 0/thread_qualify_max instead of null for correct calculations
	      			//return null;
	      			return array (0, $assignment['thread_qualify_max']);
	    		}
	    	} else {
	    	    // all students -> get average
	    		$students=array();  // user list, needed to make sure we only
	    							// take first attempts into account
				$rescount = 0;
				$sum = 0;

				while ($data=Database::fetch_array($scores)) {
					if (!(array_key_exists($data['user_id'],$students))) {
						if ($assignment['thread_qualify_max'] != 0) {
							$students[$data['user_id']] = $data['qualify'];
							$rescount++;
							$sum += ($data['qualify'] / $assignment['thread_qualify_max']);
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
    }

// INTERNAL FUNCTIONS

    /**
     * Lazy load function to get the database table of the student publications
     */
    private function get_forum_thread_table () {
    	return $this->forum_thread_table = Database :: get_course_table(TABLE_FORUM_THREAD);
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
    	$this->get_exercise_data();        
    	$thread_title=isset($this->exercise_data['thread_title']) ? $this->exercise_data['thread_title'] : '';
    	$thread_title_qualify=isset($this->exercise_data['thread_title_qualify']) ? $this->exercise_data['thread_title_qualify'] : '';
    	if ( isset($thread_title_qualify) && $thread_title_qualify!="") {
    		return $this->exercise_data['thread_title_qualify'];
    	} else {
    		return $thread_title;
    	}
    }

    public function get_description() {
    	return '';//$this->exercise_data['description'];
    }
    /**
     * Check if this still links to an exercise
     */
    public function is_valid_link() {
        $sql = 'SELECT count(id) from '.$this->get_forum_thread_table().' 
        		WHERE c_id = '.$this->course_id.' AND thread_id = '.$this->get_ref_id().' AND session_id='.api_get_session_id().'';
        $result = Database::query($sql);
        $number = Database::fetch_row($result);
        return ($number[0] != 0);
    }

    public function get_test_id() {
    	return 'DEBUG:ID';
    }

    public function get_link() {
    	//it was extracts the forum id
    	$sql = 'SELECT * FROM '.$this->get_forum_thread_table()." 
    			WHERE c_id = '.$this->course_id.' AND thread_id = '".$this->get_ref_id()."' AND session_id = ".api_get_session_id()."";
		$result = Database::query($sql);
		$row    = Database::fetch_array($result,'ASSOC');
		$forum_id=$row['forum_id'];

    	$url = api_get_path(WEB_PATH).'main/forum/viewthread.php?cidReq='.$this->get_course_code().'&thread='.$this->get_ref_id().'&gradebook=view&forum='.$forum_id;
		return $url;
   		
	}
	private function get_exercise_data() {        
        $session_id = api_get_session_id();        
        if ($session_id) {
            $session_condition = 'session_id='.api_get_session_id();
        } else {
            $session_condition = '(session_id = 0 OR session_id IS NULL)';
        }
        
		if (!isset($this->exercise_data)) {
    		$sql = 'SELECT * FROM '.$this->get_forum_thread_table().'
                    WHERE c_id = '.$this->course_id.' AND  thread_id = '.$this->get_ref_id().' AND '.$session_condition;
			$query = Database::query($sql);
			$this->exercise_data = Database::fetch_array($query);
    	}
    	return $this->exercise_data;
    }

    public function get_icon_name() {
		return 'forum';
	}
    
    function save_linked_data() {
        $weight = (float)$this->get_weight();
        $ref_id = $this->get_ref_id();
        
        if (!empty($ref_id)) {
            $sql = 'UPDATE '.$this->get_forum_thread_table().' SET thread_weight='.$weight.'
                    WHERE c_id = '.$this->course_id.' AND thread_id= '.$ref_id;
            Database::query($sql);
        }
    }


    function delete_linked_data() {
        $ref_id = $this->get_ref_id();
        if (!empty($ref_id)) {
            //Cleans forum
            $sql = 'UPDATE '.$this->get_forum_thread_table().' SET thread_qualify_max=0,thread_weight=0,thread_title_qualify="" 
                    WHERE c_id = '.$this->course_id.' AND thread_id= '.$ref_id;
            Database::query($sql);
        }
    }
}