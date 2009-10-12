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
class StudentPublicationLink extends AbstractLink
{

// INTERNAL VARIABLES

    private $studpub_table = null;
    private $itemprop_table = null;


// CONSTRUCTORS

    function StudentPublicationLink() {
    	$this->set_type(LINK_STUDENTPUBLICATION);
    }


// FUNCTIONS IMPLEMENTING ABSTRACTLINK

	public function get_view_url ($stud_id) {
		// find a file uploaded by the given student,
		// with the same title as the evaluation name

    	$eval = $this->get_evaluation();
        $stud_id = (int) $stud_id;

		$sql = 'SELECT pub.url'
				.' FROM '.$this->get_itemprop_table().' prop, '
						 .$this->get_studpub_table().' pub'
				." WHERE prop.tool = 'work'"
				.' AND prop.insert_user_id = '.$stud_id
				.' AND prop.ref = pub.id'
				." AND pub.title = '".Database::escape_string($eval->get_name())."'";

		$result = Database::query($sql, __FILE__, __LINE__);
		if ($fileurl = Database::fetch_row($result)) {
	    	$course_info = Database :: get_course_info($this->get_course_code());

			$url = api_get_path(WEB_PATH)
					.'main/gradebook/open_document.php?file='
					.$course_info['directory']
					.'/'
					.$fileurl[0];

			return $url;
		 } else {
			return null;
		}
	}


    public function get_type_name() {
    	return get_lang('DokeosStudentPublications');
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

		$sql = 'SELECT id,url from '.$this->get_studpub_table()
				.' WHERE has_properties != '."''".' AND id NOT IN'
				.' (SELECT ref_id FROM '.$tbl_grade_links
				.' WHERE type = '.LINK_STUDENTPUBLICATION
				." AND course_code = '".$this->get_course_code()."'"
				.')';

		$result = Database::query($sql, __FILE__, __LINE__);

		$cats=array();
		while ($data=Database::fetch_array($result)) {
			$cats[] = array ($data['id'], $data['url']);
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
    	$tbl_grade_links = Database :: get_course_table(TABLE_STUDENT_PUBLICATION,$course_info['dbName']);

		$sql = 'SELECT id,url FROM '.$tbl_grade_links.' WHERE has_properties != '."'' AND filetype='folder'";
		$result = Database::query($sql, __FILE__, __LINE__);

		while ($data=Database::fetch_array($result)) {
			$cats[] = array ($data['id'], $data['url']);
		}
		$cats=isset($cats) ? $cats : array();
		return $cats;
    }

    /**
     * Has anyone done this exercise yet ?
     */
    public function has_results() {
    	$course_info = api_get_course_info($this->course_code);
    	$tbl_grade_links = Database :: get_course_table(TABLE_STUDENT_PUBLICATION,$course_info['dbName']);
		$sql = 'SELECT count(*) AS number FROM '.$tbl_grade_links." WHERE parent_id = '".$this->get_ref_id()."'";
    	$result = Database::query($sql, __FILE__, __LINE__);
		$number=Database::fetch_row($result);
		return ($number[0] != 0);
    }


  public function calc_score($stud_id = null) {
    	$course_info = Database :: get_course_info($this->get_course_code());
		$database_name = (empty($course_info['db_name']))?$course_info['dbName']:$course_info['db_name'];
		$tbl_stats = Database :: get_course_table(TABLE_STUDENT_PUBLICATION, $database_name);
		if (is_null($database_name)===true) {
			return false;
		}
    	$sql = 'SELECT * FROM '.$tbl_stats." WHERE id = '".$this->get_ref_id()."'";
		$query = Database::query($sql,__FILE__,__LINE__);
		$assignment = Database::fetch_array($query);

    	if(count($assignment)==0) {
    		 $v_assigment_id ='0';
    	} else {
    		 $v_assigment_id = $assignment['id'];
    	}
    	$sql = 'SELECT * FROM '.$tbl_stats.' WHERE parent_id ="'.$v_assigment_id.'"';
    	if (isset($stud_id)){
    		$sql1='SELECT firstname, lastname FROM '.Database::get_main_table(TABLE_MAIN_USER)." WHERE user_id = '".((int)$stud_id)."'";
     		$query = Database::query($sql1,__FILE__,__LINE__);
			$student = Database::fetch_array($query);
    		$sql .= ' AND author = '."'".Database::escape_string(api_get_person_name($student['firstname'], $student['lastname'], null, null, $course_info['course_language']))."'";
    	}
    	// order by id, that way the student's first attempt is accessed first
		$sql .= ' ORDER BY id';
    	$scores = Database::query($sql, __FILE__, __LINE__);

		// for 1 student
    	if (isset($stud_id)) {
    		if ($data=Database::fetch_array($scores)) {
     			return array ($data['qualification'], $assignment['qualification']);
    		} else {
     			return '';
    		}
    	} else {
    		$students=array();  // user list, needed to make sure we only
    							// take first attempts into account
			$rescount = 0;
			$sum = 0;

			while ($data=Database::fetch_array($scores)) {
				if (!(array_key_exists($data['author'],$students))) {
					if ($assignment['qualification'] != 0) {
						$students[$data['author']] = $data['qualification'];
						$rescount++;
						$sum += ($data['qualification'] / $assignment['qualification']);
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
    private function get_studpub_table () {
    	$course_info = Database :: get_course_info($this->get_course_code());
		$database_name = isset($course_info['db_name']) ? $course_info['db_name'] : '';
		if ($database_name!='') {
			if (!isset($this->studpub_table)) {
				$this->studpub_table = Database :: get_course_table(TABLE_STUDENT_PUBLICATION, $database_name);
    		}
   			return $this->studpub_table;
		} else {
			return false;
		}

    }

    /**
     * Lazy load function to get the database table of the item properties
     */
    private function get_itemprop_table () {
    	if (!isset($this->itemprop_table)) {
	    	$course_info = Database :: get_course_info($this->get_course_code());
			$database_name = isset($course_info['db_name']) ? $course_info['db_name'] : '';
			$this->itemprop_table = Database :: get_course_table(TABLE_ITEM_PROPERTY, $database_name);
    	}
   		return $this->itemprop_table;
    }

   	public function needs_name_and_description() {
		return false;
	}

	public function get_name() {
    	$this->get_exercise_data();
    	return (isset($this->exercise_data['url'])&&(substr($this->exercise_data['url'],0,1)=='/')? substr($this->exercise_data['url'], 1) : null);
    }

    public function get_description() {
    	$this->get_exercise_data();
    	return isset($this->exercise_data['description']) ? $this->exercise_data['description'] : null;
    }

    public function get_test_id() {
    	return 'DEBUG:ID';
    }

    public function get_link() {
	$url = api_get_path(WEB_PATH)
			.'main/work/work.php?cidReq='.$this->get_course_code().'&gradebook=view&curdirpath='.substr($this->exercise_data['url'], 1);
		if (!api_is_allowed_to_create_course()
			&& $this->calc_score(api_get_user_id()) == null) {
		//$url .= '&curdirpath=/'.$this->get_ref_id();
			}
		return $url;
	}

	private function get_exercise_data() {
		$tbl_name=$this->get_studpub_table();
		if ($tbl_name=='') {
			return false;
		} elseif (!isset($this->exercise_data)) {
    		$sql = 'SELECT * FROM '.$this->get_studpub_table()." WHERE id = '".$this->get_ref_id()."'";
			$query = Database::query($sql,__FILE__,__LINE__);
			$this->exercise_data = Database::fetch_array($query);
    	}
    	return $this->exercise_data;
    }

    public function needs_max() {
		return false;
	}

	public function needs_results() {
		return false;
	}

    public function is_valid_link() {
    	$sql = 'SELECT count(id) from '.$this->get_studpub_table()
				.' WHERE id = '.$this->get_ref_id();
		$result = Database::query($sql, __FILE__, __LINE__);
		$number=Database::fetch_row($result);
		return ($number[0] != 0);
    }
}