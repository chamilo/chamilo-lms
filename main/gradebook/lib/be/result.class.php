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
 * Defines a gradebook Result object
 * @author Bert Steppé, Stijn Konings
 * @package dokeos.gradebook
 */
class Result
{

// PROPERTIES

	private $id;
	private $user_id;
	private $evaluation;
	private $creation_date;
	private $score;

// CONSTRUCTORS

    function Result() {
    	$this->creation_date = time();
    }
    
// GETTERS AND SETTERS

   	public function get_id() {
		return $this->id;
	}

   	public function get_user_id() {
		return $this->user_id;
	}

   	public function get_evaluation_id() {
		return $this->evaluation;
	}

    public function get_date() {
		return $this->creation_date;
	}

   	public function get_score() {
		return $this->score;
	}
    
    public function set_id ($id) {
		$this->id = $id;
	}

   	public function set_user_id ($user_id) {
		$this->user_id = $user_id;
	}

   	public function set_evaluation_id ($evaluation_id) {
		$this->evaluation = $evaluation_id;
	}

    public function set_date ($creation_date) {
		$this->creation_date = $creation_date;
	}

   	public function set_score ($score) {
		$this->score = $score;
	}

// CRUD FUNCTIONS

	/**
	 * Retrieve results and return them as an array of Result objects
	 * @param $id result id
	 * @param $user_id user id (student)
	 * @param $evaluation_id evaluation where this is a result for
	 */
	public function load ($id = null, $user_id = null, $evaluation_id = null) {
		$tbl_grade_results = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
		$sql='SELECT id,user_id,evaluation_id,date,score FROM '.$tbl_grade_results;
		$paramcount = 0;
		if (!empty ($id)) {
			$sql.= ' WHERE id = '.$id;
			$paramcount ++;
		}
		if (!empty ($user_id)) {
			if ($paramcount != 0) $sql .= ' AND';
			else $sql .= ' WHERE';
			$sql .= ' user_id = '.$user_id;
			$paramcount ++;
		}
		if (!empty ($evaluation_id)) {
			if ($paramcount != 0) {
				$sql .= ' AND';
			} else {
			 $sql .= ' WHERE';	
			}
			$sql .= ' evaluation_id = '.$evaluation_id;
			$paramcount ++;
		}
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$allres=array();
		while ($data=Database::fetch_array($result)) {
			$res= new Result();
			$res->set_id($data['id']);
			$res->set_user_id($data['user_id']);
			$res->set_evaluation_id($data['evaluation_id']);
			$res->set_date($data['date']);
			$res->set_score($data['score']);
			$allres[]=$res;
		}
		return $allres;
	}
    
    /**
     * Insert this result into the database
     */
    public function add() {
		if (isset($this->user_id) && isset($this->evaluation) && isset($this->creation_date) ) {
			$tbl_grade_results = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
			$sql = 'INSERT INTO '.$tbl_grade_results
					.' (user_id, evaluation_id,
					date';
			if (isset($this->score)) {
			 $sql .= ',score';	
			}
			$sql .= ') VALUES
					('.$this->get_user_id().', '.$this->get_evaluation_id()
					.', '.$this->get_date();
			if (isset($this->score)) {
			 $sql .= ', '.$this->get_score();
			}
			$sql .= ')';

			api_sql_query($sql, __FILE__, __LINE__);
		} else {
			die('Error in Result add: required field empty');			
		}

	}
	/**
	 * insert log result
	 */
	 public function add_result__log($userid,$evaluationid){

	 	if (isset($userid) && isset($evaluationid) ) {
			$tbl_grade_results_log = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT_LOG);
			$result=new Result();

			$arr_result=$result->load (null, $userid, $evaluationid);
			$arr=get_object_vars($arr_result[0]);
			
			$sql = 'INSERT INTO '.$tbl_grade_results_log
					.' (id_result,user_id, evaluation_id,
					date';
			if (isset($arr['score'])) {
			 	$sql .= ',score';	
			}
				$sql .= ') VALUES
					('.$arr['id'].','.$arr['user_id'].', '.$arr['evaluation']
					.', '.$arr['creation_date'];
			if (isset($arr['score'])) {
				 $sql .= ', '.$arr['score'];
			}
			$sql .= ')';

			api_sql_query($sql, __FILE__, __LINE__);
		} else {
			die('Error in Result add: required field empty');			
		}
	 }
	/**
	 * Update the properties of this result in the database
	 */
	public function save() {
		$tbl_grade_results = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
		$sql = 'UPDATE '.$tbl_grade_results
				.' SET user_id = '.$this->get_user_id()
				.', evaluation_id = '.$this->get_evaluation_id()
				.', score = ';

		if (isset($this->score)) {
			$sql .= $this->get_score();			
		} else {
			$sql .= 'null';			
		}
		$sql .= ' WHERE id = '.$this->id;
		// no need to update creation date
		api_sql_query($sql, __FILE__, __LINE__);
	}
	
	/**
	 * Delete this result from the database
	 */
	public function delete() {
		$tbl_grade_results = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
		$sql = 'DELETE FROM '.$tbl_grade_results.' WHERE id = '.$this->id;
		api_sql_query($sql, __FILE__, __LINE__);
	}
}