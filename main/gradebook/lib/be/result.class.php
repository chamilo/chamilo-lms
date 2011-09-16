<?php
/* For licensing terms, see /license.txt */
/**
 * Defines a gradebook Result object
 * @author Bert Steppé, Stijn Konings
 * @package chamilo.gradebook
 */
/**
 * Class
 * @package chamilo.gradebook
 */
class Result
{

// PROPERTIES

	private $id;
	private $user_id;
	private $evaluation;
	private $created_at;
	private $score;

// CONSTRUCTORS

    function Result() {
		$this->created_at = api_get_utc_datetime();
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
		return $this->created_at;
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
		$this->created_at = $creation_date;
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
		$tbl_grade_results 				= Database :: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
		$tbl_course_rel_course 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$tbl_session_rel_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		
		if (is_null($id ) && is_null($user_id) && !is_null($evaluation_id)) {

			$sql_verified_if_exist_evaluation	= 'SELECT COUNT(*) AS count FROM '.$tbl_grade_results.' WHERE evaluation_id="'.Database::escape_string($evaluation_id).'";';
			$res_verified_if_exist_evaluation	= Database::query($sql_verified_if_exist_evaluation);
			$info_verified_if_exist_evaluation	= Database::result($res_verified_if_exist_evaluation,0,0);
			
			if ($info_verified_if_exist_evaluation != 0 ) {

				$sql_course_rel_user= '';
				if (api_get_session_id()) {
					$sql_course_rel_user = 'SELECT course_code, id_user as user_id, status FROM '.$tbl_session_rel_course_user.' 
												 WHERE status=0 AND course_code="'.api_get_course_id().'" AND id_session='.api_get_session_id();
				} else {
					$sql_course_rel_user = 'SELECT course_code,user_id,status FROM '.$tbl_course_rel_course.' WHERE status ="'.STUDENT.'" AND course_code="'.api_get_course_id().'" ';
				}

				$res_course_rel_user = Database::query($sql_course_rel_user);

				$list_user_course_list = array();
				while ($row_course_rel_user = Database::fetch_array($res_course_rel_user, 'ASSOC')) {
					$list_user_course_list[]= $row_course_rel_user;
				}
				$current_date=api_get_utc_datetime();
				for ($i=0; $i<count($list_user_course_list); $i++) {
					$sql_verified   = 'SELECT COUNT(*) AS count FROM '.$tbl_grade_results.' WHERE user_id="'.intval($list_user_course_list[$i]['user_id']).'" AND evaluation_id="'.intval($evaluation_id).'";';
					$res_verified 	= Database::query($sql_verified);
					$info_verified  = Database::result($res_verified,0,0);
					if ($info_verified == 0) {
						$sql_insert='INSERT INTO '.$tbl_grade_results.'(user_id,evaluation_id,created_at,score) 
									 VALUES ("'.intval($list_user_course_list[$i]['user_id']).'","'.intval($evaluation_id).'","'.$current_date.'",0);';
						$res_insert=Database::query($sql_insert);
					}
				}
				$list_user_course_list = array();
			}
		}

		$sql = 'SELECT id,user_id,evaluation_id,created_at,score FROM '.$tbl_grade_results;
		$paramcount = 0;
		if (!empty ($id)) {
			$sql.= ' WHERE id = '.Database::escape_string($id);
			$paramcount ++;
		}
		if (!empty ($user_id)) {
			if ($paramcount != 0) $sql .= ' AND';
			else $sql .= ' WHERE';
			$sql .= ' user_id = '.Database::escape_string($user_id);
			$paramcount ++;
		}
		if (!empty ($evaluation_id)) {
			if ($paramcount != 0) {
				$sql .= ' AND';
			} else {
			 $sql .= ' WHERE';
			}
			$sql .= ' evaluation_id = '.Database::escape_string($evaluation_id);
			$paramcount ++;
		}

		$result = Database::query($sql);
		$allres=array();
		while ($data=Database::fetch_array($result)) {
			$res= new Result();
			$res->set_id($data['id']);
			$res->set_user_id($data['user_id']);
			$res->set_evaluation_id($data['evaluation_id']);
			$res->set_date(api_get_local_time($data['created_at']));
			$res->set_score($data['score']);
			$allres[]=$res;
		}
		return $allres;
	}

    /**
     * Insert this result into the database
     */
    public function add() {
		if (isset($this->user_id) && isset($this->evaluation) ) {
			$tbl_grade_results = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
			$sql = "INSERT INTO ".$tbl_grade_results
					." (user_id, evaluation_id,
					created_at";
			if (isset($this->score)) {
			 $sql .= ",score";
			}
			$sql .= ") VALUES
					(".(int)$this->get_user_id().", ".(int)$this->get_evaluation_id()
					.", '".$this->get_date()."' ";
			if (isset($this->score)) {
			 $sql .= ", ".$this->get_score();
			}
			$sql .= ")";
			Database::query($sql);
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
					.' (id_result,user_id, evaluation_id,created_at';
			if (isset($arr['score'])) {
			 	$sql .= ',score';
			}
				$sql .= ') VALUES
					('.(int)$arr['id'].','.(int)$arr['user_id'].', '.(int)$arr['evaluation']
					.", '".api_get_utc_datetime()."'";
			if (isset($arr['score'])) {
				 $sql .= ', '.$arr['score'];
			}
			$sql .= ')';

			Database::query($sql);
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
		Database::query($sql);
	}

	/**
	 * Delete this result from the database
	 */
	public function delete() {
		$tbl_grade_results = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
		$sql = 'DELETE FROM '.$tbl_grade_results.' WHERE id = '.$this->id;
		Database::query($sql);
	}
}
