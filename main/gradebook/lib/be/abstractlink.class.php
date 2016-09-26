<?php
/* For licensing terms, see /license.txt */

/**
 * Class AbstractLink
 * Defines a gradebook AbstractLink object.
 * To implement specific links,
 * extend this class and define a type in LinkFactory.
 * Use the methods in LinkFactory to create link objects.
 * @author Bert Steppé
 * @author Julio Montoya <gugli100@gmail.com> security improvements
 * @package chamilo.gradebook
 */
abstract class AbstractLink implements GradebookItem
{
    protected $id;
    protected $type;
    protected $ref_id;
    protected $user_id;
    protected $course_code;
    /** @var Category */
    protected $category;
    protected $created_at;
    protected $weight;
    protected $visible;
    protected $session_id;
    public $course_id;
    public $studentList;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->course_id = api_get_course_int_id();
    }

    /**
     * @return int
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function get_ref_id()
    {
        return (int) $this->ref_id;
    }

    /**
     * @return int
     */
    public function get_session_id()
    {
        return $this->session_id;
    }

    /**
     * @return int
     */
    public function get_user_id()
    {
        return $this->user_id;
    }

    /**
     * @return string
     */
    public function get_course_code()
    {
        return $this->course_code;
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return int
     */
    public function get_category_id()
    {
        return $this->category->get_id();
    }

    /**
     * @param int $category_id
     */
    public function set_category_id($category_id)
    {
        $categories = Category::load($category_id);
        if (isset($categories[0])) {
            $this->setCategory($categories[0]);
        }
    }

    public function get_date()
    {
        return $this->created_at;
    }

    public function get_weight()
    {
        return $this->weight;
    }

    public function is_locked()
    {
        return isset($this->locked) && $this->locked == 1 ? true : false;
    }

    public function is_visible()
    {
        return $this->visible;
    }

    public function set_id ($id)
    {
        $this->id = $id;
    }

    public function set_type ($type)
    {
        $this->type = $type;
    }

    public function set_ref_id ($ref_id)
    {
        $this->ref_id = $ref_id;
    }

    public function set_user_id ($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * @param string $course_code
     */
    public function set_course_code($course_code)
    {
        $this->course_code = $course_code;
        $course_info = api_get_course_info($course_code);
        $this->course_id = $course_info['real_id'];
    }

    public function getStudentList()
    {
        return $this->studentList;
    }

    public function setStudentList($list)
    {
        $this->studentList = $list;
    }

    public function set_date($date)
    {
        $this->created_at = $date;
    }

    public function set_weight($weight)
    {
        $this->weight = $weight;
    }

    public function set_visible($visible)
    {
        $this->visible = $visible;
    }

    /**
     * @param integer $id
     */
    public function set_session_id($id)
    {
        $this->session_id = $id;
    }

    /**
     * @param $locked
     */
    public function set_locked($locked)
    {
        $this->locked = $locked;
    }

    /**
     * @return int
     */
    public function getCourseId()
    {
        return $this->course_id;
    }

    /**
     * Retrieve links and return them as an array of extensions of AbstractLink.
     * To keep consistency, do not call this method but LinkFactory::load instead.
     * @param integer $id
     * @param integer $type
     * @param integer $user_id
     * @param string $course_code
     * @param integer $category_id
     * @param integer $visible
     */
    public static function load(
        $id = null,
        $type = null,
        $ref_id = null,
        $user_id = null,
        $course_code = null,
        $category_id = null,
        $visible = null
    ) {
        $tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
        $sql = 'SELECT * FROM '.$tbl_grade_links;
        $paramcount = 0;
        if (isset($id)) {
            $sql.= ' WHERE id = '.intval($id);
            $paramcount ++;
        }
        if (isset($type)) {
            if ($paramcount != 0) $sql .= ' AND';
            else $sql .= ' WHERE';
            $sql .= ' type = '.intval($type);
            $paramcount ++;
        }
        if (isset($ref_id)) {
            if ($paramcount != 0) $sql .= ' AND';
            else $sql .= ' WHERE';
            $sql .= ' ref_id = '.intval($ref_id);
            $paramcount ++;
        }
        if (isset($user_id)) {
            if ($paramcount != 0) {
                $sql .= ' AND';
            } else {
                $sql .= ' WHERE';
            }
            $sql .= ' user_id = '.intval($user_id);
            $paramcount ++;
        }
        if (isset($course_code)) {
            if ($paramcount != 0) {
                $sql .= ' AND';
            } else {
                $sql .= ' WHERE';
            }
            $sql .= " course_code = '".Database::escape_string($course_code)."'";
            $paramcount ++;
        }
        if (isset($category_id)) {
            if ($paramcount != 0) {
                $sql .= ' AND';
            } else {
                $sql .= ' WHERE';
            }
            $sql .= ' category_id = '.intval($category_id);
            $paramcount ++;
        }
        if (isset($visible)) {
            if ($paramcount != 0) {
                $sql .= ' AND';
            } else {
                $sql .= ' WHERE';
            }
            $sql .= ' visible = '.intval($visible);
        }

        $result = Database::query($sql);
        $links = AbstractLink::create_objects_from_sql_result($result);

        return $links;
    }

    /**
     * @param Doctrine\DBAL\Driver\Statement|null $result
     * @return array
     */
    private static function create_objects_from_sql_result($result)
    {
        $links = array();
        while ($data = Database::fetch_array($result)) {
            $link = LinkFactory::create($data['type']);
            $link->set_id($data['id']);
            $link->set_type($data['type']);
            $link->set_ref_id($data['ref_id']);
            $link->set_user_id($data['user_id']);
            $link->set_course_code($data['course_code']);
            $link->set_category_id($data['category_id']);
            $link->set_date($data['created_at']);
            $link->set_weight($data['weight']);
            $link->set_visible($data['visible']);
            $link->set_locked($data['locked']);

            //session id should depend of the category --> $data['category_id']
            $session_id = api_get_session_id();

            $link->set_session_id($session_id);
            $links[]=$link;
        }
        return $links;
    }

    /**
     * Insert this link into the database
     */
    public function add()
    {
        $this->add_linked_data();
        if (isset($this->type) &&
            isset($this->ref_id) &&
            isset($this->user_id) &&
            isset($this->course_code) &&
            isset($this->category) &&
            isset($this->weight) &&
            isset($this->visible)
        ) {
            $tbl_grade_links = Database:: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
            $sql = "SELECT count(*) FROM ".$tbl_grade_links."
                    WHERE
                        ref_id=".$this->get_ref_id()." AND
                        category_id =  ".$this->category->get_id()." AND
                        course_code = '".$this->course_code."' AND
                        type =  ".$this->type." ";

            $result = Database::query($sql);
            $row_testing = Database::fetch_array($result);

            if ($row_testing[0] == 0) {
                $params = [
                    'type' => $this->get_type(),
                    'ref_id' => $this->get_ref_id(),
                    'user_id' => $this->get_user_id(),
                    'course_code' => $this->get_course_code(),
                    'category_id' => $this->get_category_id(),
                    'weight' => $this->get_weight(),
                    'visible' => $this->is_visible(),
                    'created_at' => api_get_utc_datetime(),
                    'locked' => 0
                ];
                $inserted_id = Database::insert($tbl_grade_links, $params);
                $this->set_id($inserted_id);

                return $inserted_id;
            }
        }

        return false;
    }

    /**
     * Update the properties of this link in the database
     */
    public function save()
    {
        $em = Database::getManager();

        $link = $em->find('ChamiloCoreBundle:GradebookLink', $this->id);

        if (!$link) {
            return;
        }

        AbstractLink::add_link_log($this->id);

        $this->save_linked_data();

        $link
            ->setType($this->get_type())
            ->setRefId($this->get_ref_id())
            ->setUserId($this->get_user_id())
            ->setCourseCode($this->get_course_code())
            ->setCategoryId($this->get_category_id())
            ->setWeight($this->get_weight())
            ->setVisible($this->is_visible());

        $em->merge($link);
        $em->flush();
    }

    /**
     * @param int $idevaluation
     */
    public static function add_link_log($idevaluation, $nameLog = null)
    {
        $table = Database:: get_main_table(TABLE_MAIN_GRADEBOOK_LINKEVAL_LOG);
        $dateobject = AbstractLink::load($idevaluation, null, null, null, null);
        $current_date_server = api_get_utc_datetime();
        $arreval = get_object_vars($dateobject[0]);
        $description_log = isset($arreval['description']) ? $arreval['description']:'';
        if (empty($nameLog)) {
            if (isset($_POST['name_link'])) {
                $name_log = isset($_POST['name_link']) ? $_POST['name_link'] : $arreval['course_code'];
            } elseif (isset($_POST['link_' . $idevaluation]) && $_POST['link_' . $idevaluation]) {
                $name_log = $_POST['link_' . $idevaluation];
            } else {
                $name_log = $arreval['course_code'];
            }
        } else {
            $name_log = $nameLog;
        }

        $params = [
            'id_linkeval_log' => $arreval['id'],
            'name' => $name_log,
            'description' => $description_log,
            'created_at' => $current_date_server,
            'weight' => $arreval['weight'],
            'visible' => $arreval['visible'],
            'type' => 'Link',
            'user_id_log' => api_get_user_id(),
        ];
        Database::insert($table, $params);
    }

    /**
     * Delete this link from the database
     */
    public function delete()
    {
        $this->delete_linked_data();
        $tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
        $sql = 'DELETE FROM '.$tbl_grade_links.'
                WHERE id = '.intval($this->id);
        Database::query($sql);
    }

    /**
     * Generate an array of possible categories where this link can be moved to.
     * Notice: its own parent will be included in the list: it's up to the frontend
     * to disable this element.
     * @return array 2-dimensional array - every element contains 3 subelements (id, name, level)
     */
    public function get_target_categories()
    {
        // links can only be moved to categories inside this course
        $targets = array();
        $level = 0;

        $crscats = Category::load(null,null,$this->get_course_code(),0);
        foreach ($crscats as $cat) {
            $targets[] = array($cat->get_id(), $cat->get_name(), $level+1);
            $targets = $this->add_target_subcategories($targets, $level+1, $cat->get_id());
        }

        return $targets;
    }

    /**
     * Internal function used by get_target_categories()
     * @param integer $level
     */
    private function add_target_subcategories($targets, $level, $catid)
    {
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
    public function move_to_cat($cat)
    {
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
     * @param string $name_mask
     */
    public function find_links ($name_mask,$selectcat)
    {
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

    /**
     * @return string
     */
    public function get_item_type()
    {
        return 'L';
    }

    /**
     * @return string
     */
    public function get_icon_name()
    {
        return 'link';
    }

    abstract function has_results();
    abstract function get_link();
    abstract function is_valid_link();
    abstract function get_type_name();
    abstract function needs_name_and_description();
    abstract function needs_max();
    abstract function needs_results();
    abstract function is_allowed_to_change_name();

    /* Seems to be not used anywhere */
    public function get_not_created_links()
    {
        return null;
    }

    public function get_all_links()
    {
        return null;
    }

    public function add_linked_data()
    {
    }

    public function save_linked_data()
    {
    }

    /**
     *
     */
    public function delete_linked_data()
    {
    }

    /**
     * @param string $name
     */
    public function set_name($name)
    {
    }

    /**
     * @param string $description
     */
    public function set_description($description)
    {
    }

    /**
     * @param integer $max
     */
    public function set_max($max)
    {
    }

    public function get_view_url($stud_id)
    {
        return null;
    }

    /**
     * Locks a link
     * @param int $locked 1 or unlocked 0
     *
     * */
    public function lock($locked)
    {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
        $sql = "UPDATE $table SET locked = '".intval($locked)."'
                WHERE id='".$this->id."'";
        Database::query($sql);
    }

    /**
     * Get current user ranking
     *
     * @param int $userId
     * @param array $studentList Array with user id and scores
     * Example: [1 => 5.00, 2 => 8.00]
     */
    public static function getCurrentUserRanking($userId, $studentList)
    {
        $ranking = null;
        $currentUserId = $userId;
        if (!empty($studentList) && !empty($currentUserId)) {
            $studentList = array_map('floatval', $studentList);
            asort($studentList);
            $ranking = $count = count($studentList);

            foreach ($studentList as $userId => $position) {
                if ($currentUserId == $userId) {
                    break;
                }
                $ranking--;
            }

            // If no ranking was detected.
            if ($ranking == 0) {
                return [];
            }

            return array($ranking, $count);
        }

        return array();
    }
}
