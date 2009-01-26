<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos Latinoamerica SAC
	Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
 * Gradebook link to student publication item
 * @author Bert Steppé
 * @package dokeos.gradebook
 */
class ForumThreadLink extends AbstractLink
{

// INTERNAL VARIABLES

    private $forum_thread_table = null;
    private $itemprop_table = null;


// CONSTRUCTORS

    function ForumThreadLink() {
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
				.' WHERE thread_id NOT IN'
				.' (SELECT ref_id FROM '.$tbl_grade_links
				.' WHERE type = '.LINK_FORUM_THREAD
				." AND course_code = '".$this->get_course_code()."'"
				.')';

		$result = api_sql_query($sql, __FILE__, __LINE__);

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
    	$course_info = api_get_course_info($this->course_code);
    	$tbl_grade_links = Database :: get_course_table(TABLE_FORUM_THREAD,$course_info['dbName']);
    	$tbl_item_property=Database :: get_course_table(TABLE_ITEM_PROPERTY,$course_info['dbName']);
		$sql = 'SELECT tl.thread_id,tl.thread_title,tl.thread_title_qualify FROM '.$tbl_grade_links.' tl ,'.$tbl_item_property.' ip where tl.thread_id=ip.ref and ip.tool="forum_thread" and ip.visibility<>2 group by ip.ref';
		$result = api_sql_query($sql, __FILE__, __LINE__);
		
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
    	$course_info = api_get_course_info($this->course_code);
    	$tbl_grade_links = Database :: get_course_table(TABLE_FORUM_POST,$course_info['dbName']);
		$sql = 'SELECT count(*) AS number FROM '.$tbl_grade_links." WHERE thread_id = '".$this->get_ref_id()."'";
    	$result = api_sql_query($sql, __FILE__, __LINE__);
		$number=Database::fetch_row($result);
		return ($number[0] != 0);
    }

  public function calc_score($stud_id = null) {
	    	$course_info = Database :: get_course_info($this->get_course_code());
			$database_name = (empty($course_info['db_name']))?$course_info['dbName']:$course_info['db_name'];
			if ($database_name!="") {
			$thread_qualify = Database :: get_course_table('forum_thread_qualify', $database_name);
			
	  		$sql = 'SELECT thread_qualify_max FROM '.Database :: get_course_table(TABLE_FORUM_THREAD, $database_name)." WHERE thread_id = '".$this->get_ref_id()."'";
			$query = api_sql_query($sql,__FILE__,__LINE__);
			$assignment = Database::fetch_array($query);
	  	
	  	    $sql = 'SELECT * FROM '.$thread_qualify.' WHERE thread_id = '.$this->get_ref_id();
	    	
	    	if (isset($stud_id)){
	    		$sql .= ' AND user_id = '."'".$stud_id."'";
	    	}
	    		
	    	// order by id, that way the student's first attempt is accessed first
			$sql .= ' ORDER BY qualify_time DESC';
			 
	    	$scores = api_sql_query($sql, __FILE__, __LINE__);
	
			// for 1 student
	    	if (isset($stud_id))
	    	{
	    		if ($data=Database::fetch_array($scores)) {
	    			return array ($data['qualify'], $assignment['thread_qualify_max']);    			
	    		} else {
	      			return null;  			
	    		}
	    	} else {// all students -> get average
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
    	$course_info = Database :: get_course_info($this->get_course_code());
		$database_name = isset($course_info['db_name']) ? $course_info['db_name'] : '';
		if ($database_name!='') {
			if (!isset($this->forum_thread_table)) {
				$this->forum_thread_table = Database :: get_course_table(TABLE_FORUM_THREAD, $database_name);
    		}
   			return $this->forum_thread_table;
		} else {
			return '';
		}

    }

    /**
     * Lazy load function to get the database table of the item properties
     */
    private function get_itemprop_table () {
    	if (!isset($this->itemprop_table)) {
	    	$course_info = Database :: get_course_info($this->get_course_code());
			$database_name = $course_info['db_name'];
			$this->itemprop_table = Database :: get_course_table(TABLE_ITEM_PROPERTY, $database_name);
    	}
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
        $sql = 'SELECT count(id) from '.$this->get_forum_thread_table()
                .' WHERE thread_id = '.$this->get_ref_id();
        $result = api_sql_query($sql, __FILE__, __LINE__);
        $number=Database::fetch_row($result);
        return ($number[0] != 0);
    }
        
    public function get_test_id() {
    	return 'DEBUG:ID';
    } 
    
    public function get_link() {
    	//it was extracts the forum id
   		$tbl_name=$this->get_forum_thread_table();
   		if ($tbl_name!="") {
    	$sql = 'SELECT * FROM '.$this->get_forum_thread_table()." WHERE thread_id = '".$this->get_ref_id()."'";
		$result = api_sql_query($sql,__FILE__,__LINE__);
		$row    = Database::fetch_array($result,'ASSOC');
		$forum_id=$row['forum_id'];
		
    	$url = api_get_path(WEB_PATH)
		.'main/forum/viewthread.php?cidReq='.$this->get_course_code().'&thread='.$this->get_ref_id().'&gradebook=view&forum='.$forum_id;
		return $url;
   		}
	}
	private function get_exercise_data() {
		$tbl_name=$this->get_forum_thread_table();
		if ($tbl_name=='') {
			return false;
		}elseif (!isset($this->exercise_data)) {
    			$sql = 'SELECT * FROM '.$this->get_forum_thread_table()." WHERE thread_id = '".$this->get_ref_id()."'";
				$query = api_sql_query($sql,__FILE__,__LINE__);
				$this->exercise_data = Database::fetch_array($query);
    	}
    	return $this->exercise_data;
    }
    
}