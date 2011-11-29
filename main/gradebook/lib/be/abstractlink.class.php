<?php
/* For licensing terms, see /license.txt */
/**
 * Defines a gradebook AbstractLink object.
 * To implement specific links,
 * extend this class and define a type in LinkFactory.
 * Use the methods in LinkFactory to create link objects.
 * @author Bert Steppé
 * @author Julio Montoya <gugli100@gmail.com> security improvements
 * @package chamilo.gradebook
 */
/**
 * Class
 * @package chamilo.gradebook
 */
abstract class AbstractLink implements GradebookItem {
	// PROPERTIES

	protected $id;
	protected $type;
	protected $ref_id;
	protected $user_id;
	protected $course_code;
	protected $category;
	protected $created_at;
	protected $weight;
	protected $visible;
	protected $session_id;
	public    $course_id;

	// CONSTRUCTORS

    function __construct() {    	
    	$this->course_id = api_get_course_int_id();    	
    }

	// GETTERS AND SETTERS

   	public function get_id() {
		return $this->id;
	}

	public function get_type() {
		return $this->type;
	}

	public function get_ref_id() {
		return $this->ref_id;
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

	public function is_visible() {
		return $this->visible;
	}

	public function set_id ($id) {
		$this->id = $id;
	}

	public function set_type ($type) {
		$this->type = $type;
	}

	public function set_ref_id ($ref_id) {
		$this->ref_id = $ref_id;
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

	public function set_visible ($visible) {
		$this->visible = $visible;
	}

// CRUD FUNCTIONS

	/**
	 * Retrieve links and return them as an array of extensions of AbstractLink.
	 * To keep consistency, do not call this method but LinkFactory::load instead.
	 */
	public function load ($id = null, $type = null, $ref_id = null, $user_id = null, $course_code = null, $category_id = null, $visible = null) {
    	$tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
		$sql='SELECT id, type, ref_id, user_id,course_code,category_id,created_at,weight,visible FROM '.$tbl_grade_links;
		$paramcount = 0;
		if (isset ($id)) {
			$sql.= ' WHERE id = '.Database::escape_string($id);
			$paramcount ++;
		}
		if (isset ($type)) {
			if ($paramcount != 0) $sql .= ' AND';
			else $sql .= ' WHERE';
			$sql .= ' type = '.Database::escape_string($type);
			$paramcount ++;
		}
		if (isset ($ref_id)) {
			if ($paramcount != 0) $sql .= ' AND';
			else $sql .= ' WHERE';
			$sql .= ' ref_id = '.intval($ref_id);
			$paramcount ++;
		}
		if (isset ($user_id)) {
			if ($paramcount != 0) {
			$sql .= ' AND';
			}else {
			 $sql .= ' WHERE';
			}
			$sql .= ' user_id = '.intval($user_id);
			$paramcount ++;
		}
		if (isset ($course_code)) {
			if ($paramcount != 0) {
			 $sql .= ' AND';
			} else {
			 $sql .= ' WHERE';
			}
			$sql .= " course_code = '".Database::escape_string($course_code)."'";
			$paramcount ++;
		}
		if (isset ($category_id)) {
			if ($paramcount != 0) {
				$sql .= ' AND';
			}else {
			 	$sql .= ' WHERE';
			}
			$sql .= ' category_id = '.intval($category_id);
			$paramcount ++;
		}
		if (isset ($visible)) {
			if ($paramcount != 0) {
				$sql .= ' AND';
			} else {
				 $sql .= ' WHERE';
			}
			$sql .= ' visible = '.intval($visible);
			$paramcount ++;
		}
		
		$result = Database::query($sql);
		$links = AbstractLink::create_objects_from_sql_result($result);
		return $links;
	}

    private function create_objects_from_sql_result($result) {
    	$links=array();
		while ($data=Database::fetch_array($result)) {
			$link = LinkFactory::create(intval($data['type']));
			$link->set_id($data['id']);
			$link->set_type($data['type']);
			$link->set_ref_id($data['ref_id']);
			$link->set_user_id($data['user_id']);
			$link->set_course_code($data['course_code']);
			$link->set_category_id($data['category_id']);
			$link->set_date($data['created_at']);
			$link->set_weight($data['weight']);
			$link->set_visible($data['visible']);
			$links[]=$link;
		}
		return $links;
    }

    public function set_session_id($id) {
    	$this->session_id = $id;
    }

    /**
     * Insert this link into the database
     */
    public function add() {
		$this->add_linked_data();

		if (isset($this->type) && isset($this->ref_id) && isset($this->user_id) && isset($this->course_code) && isset($this->category) && isset($this->weight) && isset($this->visible)) {
			$tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
			$sql_testing="SELECT count(*) FROM ".$tbl_grade_links." WHERE ref_id=".$this->get_ref_id()." AND category_id =  ".$this->category." AND type =  ".$this->type." ;";
			$result_testing=Database::query($sql_testing);
			$row_testing=Database::fetch_array($result_testing);
						
			if ($row_testing[0]==0) {
				$sql = 'INSERT INTO '.$tbl_grade_links.' (type,ref_id, user_id, course_code, category_id, weight, visible, created_at';
				$sql .= ') VALUES ('
					.intval($this->get_type())
					.','.intval($this->get_ref_id())
					.','.intval($this->get_user_id())
					.",'".Database::escape_string($this->get_course_code())."'"
					.','.intval($this->get_category_id())
					.','.intval($this->get_weight())
					.','.intval($this->is_visible());
				$sql .= ','.'"'.$date_current=api_get_local_time().'"';
				$sql .= ")";				
				Database::query($sql);
				$this->set_id(Database::insert_id());
		 	}
		} else {
			die('Error in AbstractLink add: required field empty');
		}
	}

	/**
	 * Update the properties of this link in the database
	 */
	public function save() {
		$this->save_linked_data();

		$tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
		$sql = 'UPDATE '.$tbl_grade_links
			.' SET type = '.intval($this->get_type())
				.', ref_id = '.intval($this->get_ref_id())
				.', user_id = '.intval($this->get_user_id())
				.", course_code = '".Database::escape_string($this->get_course_code())."'"
				.', category_id = '.intval($this->get_category_id());
		$sql .= ', weight = '.intval($this->get_weight())
				.', visible = '.intval($this->is_visible())
				.' WHERE id = '.intval($this->id);
	
		AbstractLink::add_link_log($this->id);
		Database::query($sql);
	}

	public function add_link_log($idevaluation) {
		$tbl_grade_linkeval_log = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINKEVAL_LOG);
		$dateobject=AbstractLink::load ($idevaluation,null,null,null,null);
		$current_date_server=api_get_utc_datetime();
		$arreval=get_object_vars($dateobject[0]);
		$description_log=isset($arreval['description'])?$arreval['description']:'';
		if (isset($_POST['name_link'])) {
			$name_log=isset($_POST['name_link'])?Security::remove_XSS($_POST['name_link']):$arreval['course_code'];
		} elseif ($_POST['link_'.$idevaluation]) {
			$name_log=$_POST['link_'.$idevaluation];
		} else {
			$name_log=$arreval['course_code'];
		}
		//error_log($name_log);
		$sql="INSERT INTO ".$tbl_grade_linkeval_log."(id_linkeval_log,name,description,created_at,weight,visible,type,user_id_log)
			  VALUES('".Database::escape_string($arreval['id'])."','".Database::escape_string($name_log)."','".Database::escape_string($description_log)."','".Database::escape_string($current_date_server)."','".Database::escape_string($arreval['weight'])."','".Database::escape_string($arreval['visible'])."','Link',".api_get_user_id().")";
	
		Database::query($sql);

	}
	/**
	 * Delete this link from the database
	 */
	public function delete() {

		$this->delete_linked_data();

		$tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
		$sql = 'DELETE FROM '.$tbl_grade_links.' WHERE id = '.intval($this->id);
		Database::query($sql);
	}


// OTHER FUNCTIONS

	/**
	 * Generate an array of possible categories where this link can be moved to.
	 * Notice: its own parent will be included in the list: it's up to the frontend
	 * to disable this element.
	 * @return array 2-dimensional array - every element contains 3 subelements (id, name, level)
	 */
    public function get_target_categories() {
    	// links can only be moved to categories inside this course

		$targets = array();
		$level = 0;

		$crscats = Category::load(null,null,$this->get_course_code(),0);
		foreach ($crscats as $cat) {
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
	 * Move this link to the given category.
	 * If this link moves to outside a course, delete it.
	 */
    public function move_to_cat($cat) {
		if ($this->get_course_code() != $cat->get_course_code()) {
			$this->delete();
		} else {
			$this->set_category_id($cat->get_id());
			$this->save();
		}
    }


    /**
     * Find links by name
	 * To keep consistency, do not call this method but LinkFactory::find_links instead.
     * @todo can be written more efficiently using a new (but very complex) sql query
     */
    public function find_links ($name_mask,$selectcat) {
    	$rootcat = Category::load($selectcat);
		$links = $rootcat[0]->get_links((api_is_allowed_to_edit() ? null : api_get_user_id()), true);
		$foundlinks = array();
		foreach ($links as $link) {
			if (!(api_strpos(api_strtolower($link->get_name()), api_strtolower($name_mask)) === false)) {
				$foundlinks[] = $link;
			}
		}
		return $foundlinks;
    }


// Other methods implementing GradebookItem

	public function get_item_type() {
		return 'L';
	}

	public function get_icon_name() {
		return 'link';
	}
// ABSTRACT FUNCTIONS - to be implemented by subclass

    abstract function has_results();
	abstract function get_link();
	abstract function is_valid_link();
	abstract function get_type_name();

	// The following methods are already defined in GradebookItem,
	// and must be implemented by the subclass as well !
//	abstract function get_name();
//	abstract function get_description();
//  abstract function calc_score($stud_id = null);

	abstract function needs_name_and_description();
	abstract function needs_max();
	abstract function needs_results();
	abstract function is_allowed_to_change_name();

// TRIVIAL FUNCTIONS - to be overwritten by subclass if needed
	public function get_not_created_links() {
		return null;
	}
	public function get_all_links() {
		return null;
	}

	public function add_linked_data() {
	}

	public function save_linked_data() {
	}

	public function delete_linked_data() {
	}

	public function set_name ($name) {
	}

	public function set_description ($description) {
	}

	public function set_max ($max) {
	}
	public function get_view_url ($stud_id) {
		return null;
	}
}
