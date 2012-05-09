<?php
/* For licensing terms, see /license.txt */
/**
 * Defines a gradebook Evaluation object
 * @package chamilo.gradebook
 */
/**
 * Class
 * @package chamilo.gradebook
 */
class Evaluation implements GradebookItem
{

// PROPERTIES

	private $id;
	private $name;
	private $description;
	private $user_id;
	private $course_code;
	private $category;
	private $created_at;
	private $weight;
	private $eval_max;
	private $visible;

    // CONSTRUCTORS

    function __construct() {    	
    }

    // GETTERS AND SETTERS

   	public function get_id() {
		return $this->id;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_description() {
		return $this->description;
	}

	public function get_user_id() {
		return $this->user_id;
	}

	public function get_course_code() {
		return $this->course_code;
	}

	public function get_category_id() {
		return $this->category;
	}

	public function get_date() {
		return $this->created_at;
	}

	public function get_weight() {
		return $this->weight;
	}

	public function get_max() {
		return $this->eval_max;
	}
	
	public function get_type() {
		return $this->type;
	}	

	public function is_visible() {
		return $this->visible;
	}
	
	public function get_locked() {
		return $this->locked;
	}
	public function set_id ($id) {
		$this->id = $id;
	}

	public function set_name ($name) {
		$this->name = $name;
	}

	public function set_description ($description) {
		$this->description = $description;
	}

	public function set_user_id ($user_id) {
		$this->user_id = $user_id;
	}

	public function set_course_code ($course_code) {
		$this->course_code = $course_code;
	}

	public function set_category_id ($category_id) {
		$this->category = $category_id;
	}

	public function set_date ($date) {
		$this->created_at = $date;
	}

	public function set_weight ($weight) {
		$this->weight = $weight;
	}

	public function set_max ($max) {
		$this->eval_max = $max;
	}

	public function set_visible ($visible) {
		$this->visible = $visible;
	}
	
    public function set_type ($type) {
		$this->type = $type;
	}
 
	public function set_locked ($locked) {
		$this->locked = $locked;
	}
    
    
    // CRUD FUNCTIONS

	/**
	 * Retrieve evaluations and return them as an array of Evaluation objects
	 * @param $id evaluation id
	 * @param $user_id user id (evaluation owner)
	 * @param $course_code course code
	 * @param $category_id parent category
	 * @param $visible visible
	 */
	public function load ($id = null, $user_id = null, $course_code = null, $category_id = null, $visible = null, $locked = null) {
    	$tbl_grade_evaluations = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
		$sql = 'SELECT * FROM '.$tbl_grade_evaluations;
		$paramcount = 0;
		if (isset ($id)) {
			$sql.= ' WHERE id = '.intval($id);
			$paramcount ++;
		}
		if (isset ($user_id)) {
			if ($paramcount != 0) $sql .= ' AND';
			else $sql .= ' WHERE';
			$sql .= ' user_id = '.intval($user_id);
			$paramcount ++;
		}
		if (isset ($course_code) && $course_code <> '-1') {
			if ($paramcount != 0) $sql .= ' AND';
			else $sql .= ' WHERE';
			$sql .= " course_code = '".Database::escape_string($course_code)."'";
			$paramcount ++;
		}
		if (isset ($category_id)) {
			if ($paramcount != 0) $sql .= ' AND';
			else $sql .= ' WHERE';
			$sql .= ' category_id = '.intval($category_id);
			$paramcount ++;
		}
		if (isset ($visible)) {
			if ($paramcount != 0) $sql .= ' AND';
			else $sql .= ' WHERE';
			$sql .= ' visible = '.intval($visible);
			$paramcount ++;
		}
		if (isset ($locked)) {
			if ($paramcount != 0) $sql .= ' AND';
			else $sql .= ' WHERE';
			$sql .= ' locked = '.intval($locked);
			$paramcount ++;
		}		
		$result = Database::query($sql);
		$alleval = Evaluation::create_evaluation_objects_from_sql_result($result);
		return $alleval;
	}

    private function create_evaluation_objects_from_sql_result($result) {
    	$alleval=array();
    	if (Database::num_rows($result)) {
    		while ($data = Database::fetch_array($result)) {
    			$eval= new Evaluation();
    			$eval->set_id($data['id']);
    			$eval->set_name($data['name']);
    			$eval->set_description($data['description']);
    			$eval->set_user_id($data['user_id']);
    			$eval->set_course_code($data['course_code']);
    			$eval->set_category_id($data['category_id']);
    			$eval->set_date(api_get_local_time($data['created_at']));
    			$eval->set_weight($data['weight']);
    			$eval->set_max($data['max']);
    			$eval->set_visible($data['visible']);
    			$eval->set_type($data['type']);
    			$eval->set_locked($data['locked']);
    			
    			$alleval[]=$eval;
    		}
    	}
		return $alleval;
    }

    /**
     * Insert this evaluation into the database
     */
    public function add() {
		if (isset($this->name) && isset($this->user_id) && isset($this->weight) && isset ($this->eval_max) && isset($this->visible)) {
			$tbl_grade_evaluations = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);

			$sql = 'INSERT INTO '.$tbl_grade_evaluations
					.' (name, user_id, weight, max, visible';
			if (isset($this->description)) {
			 $sql .= ',description';
			}
			if (isset($this->course_code)) {
				$sql .= ', course_code';
			}
			if (isset($this->category)) {
				$sql .= ', category_id';
			}
			$sql .= ', created_at';
			$sql .= ',type';
			$sql .= ") VALUES ('".Database::escape_string($this->get_name())."'"
					.','.intval($this->get_user_id())
					.','.floatval($this->get_weight())
					.','.intval($this->get_max())
					.','.intval($this->is_visible());
			if (isset($this->description)) {
				 $sql .= ",'".Database::escape_string($this->get_description())."'";
			}
			if (isset($this->course_code)) {
				 $sql .= ",'".Database::escape_string($this->get_course_code())."'";
			}
			if (isset($this->category)) {
				 $sql .= ','.intval($this->get_category_id());
			}
			if (empty($this->type)) {
				$this->type = 'evaluation';	
			}
			$sql .= ", '".api_get_utc_datetime()."'";
			
			$sql .= ',\''.Database::escape_string($this->type).'\'';
			
			$sql .= ")";
			
			Database::query($sql);
			$this->set_id(Database::insert_id());
		}
		else {
			die('Error in Evaluation add: required field empty');
		}
	}

	public function add_evaluation_log($idevaluation){
		if (!empty($idevaluation)) {
			$tbl_grade_evaluations = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
			$tbl_grade_linkeval_log = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINKEVAL_LOG);
			$eval=new Evaluation();
			$dateobject=$eval->load ($idevaluation,null,null,null,null);
			$arreval=get_object_vars($dateobject[0]);
			if (!empty($arreval['id'])) {
				$sql_eval='SELECT weight from '.$tbl_grade_evaluations.' WHERE id='.$arreval['id'];
				$rs=Database::query($sql_eval);
				$row_old_weight=Database::fetch_array($rs,'ASSOC');
				$current_date=api_get_utc_datetime();
				$sql="INSERT INTO ".$tbl_grade_linkeval_log."(id_linkeval_log,name,description,created_at,weight,visible,type,user_id_log)
					  VALUES('".Database::escape_string($arreval['id'])."','".Database::escape_string($arreval['name'])."','".Database::escape_string($arreval['description'])."','".$current_date."','".Database::escape_string($row_old_weight['weight'])."','".Database::escape_string($arreval['visible'])."','evaluation',".api_get_user_id().")";
				Database::query($sql);
			}
		}
	}
	/**
	 * Update the properties of this evaluation in the database
	 */
	public function save() {
		$tbl_grade_evaluations = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
		$sql = 'UPDATE '.$tbl_grade_evaluations
			." SET name = '".Database::escape_string($this->get_name())."'"
			.', description = ';
		if (isset($this->description)) {
			$sql .= "'".Database::escape_string($this->get_description())."'";
		}else {
			$sql .= 'null';
		}
		$sql .= ', user_id = '.intval($this->get_user_id())
				.', course_code = ';
		if (isset($this->course_code)) {
			$sql .= "'".Database::escape_string($this->get_course_code())."'";
		} else {
			$sql .= 'null';
		}
		$sql .= ', category_id = ';
		if (isset($this->category)) {
			$sql .= intval($this->get_category_id());
		} else {
			$sql .= 'null';
		}
		$sql .= ', weight = "'.Database::escape_string($this->get_weight()).'" '
				.', max = '.Database::escape_string($this->get_max())
				.', visible = '.intval($this->is_visible())
				.' WHERE id = '.intval($this->id);
		//recorded history
        
		$eval_log=new Evaluation();
		$eval_log->add_evaluation_log($this->id);
		Database::query($sql);
	}

	/**
	 * Delete this evaluation from the database
	 */
	public function delete() {
		$tbl_grade_evaluations = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
		$sql = 'DELETE FROM '.$tbl_grade_evaluations.' WHERE id = '.intval($this->id);
		Database::query($sql);
	}

// OTHER FUNCTIONS

	/**
	 * Check if an evaluation name (with the same parent category) already exists
	 * @param $name name to check (if not given, the name property of this object will be checked)
	 * @param $parent parent category
	 */
	public function does_name_exist($name, $parent) {
		if (!isset ($name)) {
			$name = $this->name;
			$parent = $this->category;
		}
		$tbl_grade_evaluations = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
		$sql = 'SELECT count(id) AS number'
			 .' FROM '.$tbl_grade_evaluations
			 ." WHERE name = '".Database::escape_string($name)."'";

		if (api_is_allowed_to_edit()) {
			$parent = Category::load($parent);
			$code = $parent[0]->get_course_code();
			if (isset($code) && $code != '0') {
				$main_course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
				$sql .= ' AND user_id IN ('
						.' SELECT user_id FROM '.$main_course_user_table
						." WHERE course_code = '".Database::escape_string($code)."'"
						.' AND status = '.COURSEMANAGER
						.')';
			} else {
				$sql .= ' AND user_id = '.api_get_user_id();
			}

		}else {
			$sql .= ' AND user_id = '.api_get_user_id();
		}

		if (!isset ($parent)) {
			$sql.= ' AND category_id is null';
		} else {
			$sql.= ' AND category_id = '.intval($parent);
		}
    	$result = Database::query($sql);
		$number=Database::fetch_row($result);
		return ($number[0] != 0);
	}

	/**
	 * Are there any results for this evaluation yet ?
	 * The 'max' property should not be changed then.
	 */
    public function has_results() {
    	$tbl_grade_results = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
		$sql='SELECT count(id) AS number FROM '.$tbl_grade_results
			.' WHERE evaluation_id = '.intval($this->id);
    	$result = Database::query($sql);
		$number=Database::fetch_row($result);

		return ($number[0] != 0);
    }

    /**
     * Delete all results for this evaluation
     */
    public function delete_results() {
		$tbl_grade_results = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
		$sql = 'DELETE FROM '.$tbl_grade_results.' WHERE evaluation_id = '.intval($this->id);
		Database::query($sql);
    }


    /**
     * Delete this evaluation and all underlying results.
     */
    public function delete_with_results(){
    	$this->delete_results();
    	$this->delete();
    }


    /**
     * Check if the given score is possible for this evaluation
     */
    public function is_valid_score ($score) {
    	return (is_numeric($score) && $score >= 0 && $score <= $this->eval_max);
    }


	/**
	 * Calculate the score of this evaluation
	 * @param $stud_id student id (default: all students who have results for this eval - then the average is returned)
	 * @return	array (score, max) if student is given
	 * 			array (sum of scores, number of scores) otherwise
	 * 			or null if no scores available
	 */
    public function calc_score ($stud_id = null) {
		$results = Result::load(null,$stud_id,$this->id);

		$rescount = 0;
		$sum = 0;
		foreach ($results as $res) {
			$score = $res->get_score();
			if ((!empty ($score)) || ($score == '0')) {
				$rescount++;
				$sum += ($score / $this->get_max());
			}
		}

		if ($rescount == 0) {
			return null;
		}
		else if (isset($stud_id)) {
			return array ($score, $this->get_max());
		} else {
			return array ($sum, $rescount);
		}

    }

    /**
	 * Generate an array of possible categories where this evaluation can be moved to.
	 * Notice: its own parent will be included in the list: it's up to the frontend
	 * to disable this element.
	 * @return array 2-dimensional array - every element contains 3 subelements (id, name, level)
	 */
	public function get_target_categories() {
		// - course independent evaluation
		//   -> movable to root or other course independent categories
		// - evaluation inside a course
		//   -> movable to root, independent categories or categories inside the course
		$user = (api_is_platform_admin() ? null : api_get_user_id());
		$targets = array();
		$level = 0;

		$root = array(0, get_lang('RootCat'), $level);
		$targets[] = $root;

		if (isset($this->course_code) && !empty($this->course_code)) {
			$crscats = Category::load(null,null,$this->course_code,0);
			foreach ($crscats as $cat) {
				$targets[] = array ($cat->get_id(), $cat->get_name(), $level+1);
				$targets = $this->add_target_subcategories($targets, $level+1, $cat->get_id());
			}
		}

		$indcats = Category::load(null,$user,0,0);
		foreach ($indcats as $cat) {
			$targets[] = array ($cat->get_id(), $cat->get_name(), $level+1);
			$targets = $this->add_target_subcategories($targets, $level+1, $cat->get_id());
		}

		return $targets;
	}

	/**
	 * Internal function used by get_target_categories()
	 */
	private function add_target_subcategories($targets, $level, $catid) {
		$subcats = Category::load(null,null,null,$catid);
		foreach ($subcats as $cat) {
			$targets[] = array ($cat->get_id(), $cat->get_name(), $level+1);
			$targets = $this->add_target_subcategories($targets, $level+1, $cat->get_id());
		}
		return $targets;
	}


	/**
	 * Move this evaluation to the given category.
	 * If this evaluation moves from inside a course to outside,
	 * its course code is also changed.
	 */
	public function move_to_cat ($cat) {
		$this->set_category_id($cat->get_id());
		if ($this->get_course_code() != $cat->get_course_code()) {
			$this->set_course_code($cat->get_course_code());
		}
		$this->save();
	}



	/**
	 * Retrieve evaluations where a student has results for
	 * and return them as an array of Evaluation objects
	 * @param $cat_id parent category (use 'null' to retrieve them in all categories)
	 * @param $stud_id student id
	 */
    public function get_evaluations_with_result_for_student ($cat_id = null, $stud_id) {
		$tbl_grade_evaluations = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
    	$tbl_grade_results = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);

		$sql = 'SELECT * FROM '.$tbl_grade_evaluations
				.' WHERE id IN'
				.'(SELECT evaluation_id FROM '.$tbl_grade_results
				.' WHERE user_id = '.intval($stud_id).' AND score IS NOT NULL)';
		if (!api_is_allowed_to_edit()) {
			$sql .= ' AND visible = 1';
		}
		if (isset($cat_id)) {
			$sql .= ' AND category_id = '.intval($cat_id);
		} else {
			$sql .= ' AND category_id >= 0';
		}

		$result = Database::query($sql);
		$alleval = Evaluation::create_evaluation_objects_from_sql_result($result);
		return $alleval;
    }



    /**
     * Get a list of students that do not have a result record for this evaluation
     */
    public function get_not_subscribed_students ($first_letter_user = '') {
		$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
    	$tbl_grade_results = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);

    	$sql = 'SELECT user_id,lastname,firstname,username FROM '.$tbl_user
				." WHERE lastname LIKE '".Database::escape_string($first_letter_user)."%'"
				.' AND status = '.STUDENT
				.' AND user_id NOT IN'
				.' (SELECT user_id FROM '.$tbl_grade_results
				.' WHERE evaluation_id = '.intval($this->id)
				.' )'
				.' ORDER BY lastname';

		$result = Database::query($sql);
		$db_users = Database::store_result($result);
		return $db_users;
    }


    /**
     * Find evaluations by name
     * @param string $name_mask search string
     * @return array evaluation objects matching the search criterium
     * @todo can be written more efficiently using a new (but very complex) sql query
     */
    public function find_evaluations ($name_mask,$selectcat) {
    	$rootcat = Category::load($selectcat);
		$evals = $rootcat[0]->get_evaluations((api_is_allowed_to_create_course() ? null : api_get_user_id()), true);
		$foundevals = array();
		foreach ($evals as $eval) {
			if (!(api_strpos(api_strtolower($eval->get_name()), api_strtolower($name_mask)) === false)) {
				$foundevals[] = $eval;
			}
		}
		return $foundevals;
    }



// Other methods implementing GradebookItem

    public function get_item_type() {
		return 'E';
	}

	public function get_icon_name() {
		return $this->has_results() ? 'evalnotempty' : 'evalempty';
	}
  	/***
  	 * This function, locks an evaluation, only one who can unlock it is the platform administrator.
  	 * @param int evaluation id
  	 * @param int locked 1 or unlocked 0 
  	 * @return bool 
  	 * 
  	 * */
  	function locked_evaluation($id_evaluation, $locked) {
  		
  		$table_evaluation = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
  		$sql = "UPDATE $table_evaluation SET locked = '".intval($locked)."' WHERE id='".intval($id_evaluation)."'";
  		$rs = Database::query($sql);
  		$affected_rows = Database::affected_rows();
		if (!empty($affected_rows)) {
			return true;
		}
  	}
}
