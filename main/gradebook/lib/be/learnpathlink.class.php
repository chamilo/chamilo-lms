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
 * Defines a gradebook LearnpathLink object.
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 * @author Bert Steppé
 * @package dokeos.gradebook
 */
class LearnpathLink extends AbstractLink
{
// INTERNAL VARIABLES

    private $course_info = null;
    private $learnpath_table = null;
    private $learnpath_data = null;


// CONSTRUCTORS

    function LearnpathLink()
    {
    	$this->set_type(LINK_LEARNPATH);
    }


// FUNCTIONS IMPLEMENTING ABSTRACTLINK

	/**
	 * Generate an array of learnpaths that a teacher hasn't created a link for.
	 * @return array 2-dimensional array - every element contains 2 subelements (id, name)
	 */
    public function get_not_created_links()
    {
    	if (empty($this->course_code))
    		die('Error in get_not_created_links() : course code not set');

    	$tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

		$sql = 'SELECT id,name from '.$this->get_learnpath_table()
				.' WHERE id NOT IN'
				.' (SELECT ref_id FROM '.$tbl_grade_links
				.' WHERE type = '.LINK_LEARNPATH
				." AND course_code = '".$this->get_course_code()."'"
				.')';

		$result = api_sql_query($sql, __FILE__, __LINE__);

		$cats=array();
		while ($data=Database::fetch_array($result))
		{
			$cats[] = array ($data['id'], $data['name']);
		}
		return $cats;
    }
	/**
	 * Generate an array of all learnpaths available.
	 * @return array 2-dimensional array - every element contains 2 subelements (id, name)
	 */
    public function get_all_links()
    {
    	if (empty($this->course_code))
    		die('Error in get_not_created_links() : course code not set');

		$course_info = api_get_course_info($this->course_code);
    	$tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK,$course_info['dbName']);

		$sql = 'SELECT id,name from '.$this->get_learnpath_table();
		$result = api_sql_query($sql, __FILE__, __LINE__);

		$cats=array();
		while ($data=Database::fetch_array($result))
		{
			$cats[] = array ($data['id'], $data['name']);
		}
		return $cats;
    }


    /**
     * Has anyone used this learnpath yet ?
     */
    public function has_results()
    {
    	$course_info = api_get_course_info($this->get_course_code());
    	$tbl_stats = Database::get_course_table(TABLE_LP_VIEW,$course_info['dbName']);
		$sql = 'SELECT count(id) AS number FROM '.$tbl_stats
				." WHERE lp_id = '".$this->get_ref_id()."'";
    	$result = api_sql_query($sql, __FILE__, __LINE__);
		$number=Database::fetch_array($result,'NUM');
		return ($number[0] != 0);
    }

    /**
	 * Get the progress of this learnpath. Only the last attempt are taken into account.
	 * @param $stud_id student id (default: all students who have results - then the average is returned)
	 * @return	array (score, max) if student is given
	 * 			array (sum of scores, number of scores) otherwise
	 * 			or null if no scores available
	 */
    public function calc_score($stud_id = null)
    {
    	$course_info = api_get_course_info($this->get_course_code());
    	$tbl_stats = Database::get_course_table(TABLE_LP_VIEW,$course_info['dbName']);
    	if (is_null($course_info['dbName'])===true) {
			return false;
		}
    	$sql = 'SELECT * FROM '.$tbl_stats
    			." WHERE lp_id = ".$this->get_ref_id();

    	if (isset($stud_id))
    		$sql .= ' AND user_id = '.$stud_id;

    	// order by id, that way the student's first attempt is accessed first
		$sql .= ' ORDER BY view_count DESC';
    	$scores = api_sql_query($sql, __FILE__, __LINE__);
		// for 1 student
    	if (isset($stud_id))
    	{
    		if ($data=Database::fetch_array($scores))
    		{
    			return array ($data['progress'], 100);
    		}
    		else
    			return null;
    	}

    	// all students -> get average
    	else
    	{
    		$students=array();  // user list, needed to make sure we only
    							// take first attempts into account
			$rescount = 0;
			$sum = 0;

			while ($data=Database::fetch_array($scores))
			{
				if (!(array_key_exists($data['user_id'],$students)))
				{
					$students[$data['user_id']] = $data['progress'];
					$rescount++;
					$sum += ($data['progress'] / 100);
				}
			}

			if ($rescount == 0)
				return null;
			else
				return array ($sum , $rescount);
    	}
    }

    /**
     * Get URL where to go to if the user clicks on the link.
     */
	public function get_link()
	{
		$url = api_get_path(WEB_PATH)
			.'main/newscorm/lp_controller.php?cidReq='.$this->get_course_code().'&gradebook=view';
		if (!api_is_allowed_to_create_course()
			|| $this->calc_score(api_get_user_id()) == null)
		{
			$url .= '&action=view&lp_id='.$this->get_ref_id();
		}
		else
		{
			$url .= '&action=build&lp_id='.$this->get_ref_id();
		}
		return $url;
	}

    /**
     * Get name to display: same as learnpath title
     */
    public function get_name()
    {
    	$data = $this->get_learnpath_data();
    	return $data['name'];
    }

    /**
     * Get description to display: same as learnpath description
     */
    public function get_description()
    {
    	$data = $this->get_learnpath_data();
    	return $data['description'];
    }

    /**
     * Check if this still links to a learnpath
     */
    public function is_valid_link()
    {
    	$sql = 'SELECT count(id) from '.$this->get_learnpath_table()
				.' WHERE id = '.$this->get_ref_id();
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$number=Database::fetch_row($result,'NUM');
		return ($number[0] != 0);
    }

    public function get_type_name()
    {
    	return get_lang('DokeosLearningPaths');
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

	public function is_allowed_to_change_name()
	{
		return false;
	}

// INTERNAL FUNCTIONS

    /**
     * Lazy load function to get the database table of the learnpath
     */
    private function get_learnpath_table ()
    {
    	$course_info = api_get_course_info($this->get_course_code());
		$database_name = isset($course_info['dbName']) ? $course_info['dbName'] : '';
		if ($database_name=='') {
			return '';
		} elseif (!isset($this->learnpath_table)) {
			$this->learnpath_table = Database :: get_course_table(TABLE_LP_MAIN, $database_name);
    	}
   		return $this->learnpath_table;
    }

    /**
     * Lazy load function to get the database contents of this learnpath
     */
    private function get_learnpath_data() {
    	$tb_learnpath=$this->get_learnpath_table();
    	if ($tb_learnpath=='') {
    		return false;
    	} elseif (!isset($this->learnpath_data)) {
			$sql = 'SELECT * from '.$this->get_learnpath_table()
					.' WHERE id = '.$this->get_ref_id();
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$this->learnpath_data=Database::fetch_array($result);
    	}
    	return $this->learnpath_data;
    }
}