<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\GradebookCategory;

/**
 * Class Category
 * Defines a gradebook Category object
 * @package chamilo.gradebook
 */
class Category implements GradebookItem
{
    private $id;
    private $name;
    private $description;
    private $user_id;
    private $course_code;
    private $parent;
    private $weight;
    private $visible;
    private $certificate_min_score;
    private $session_id;
    private $skills = array();
    private $grade_model_id;
    private $generateCertificates;
    private $isRequirement;
    public $studentList;

    public $evaluations;
    public $links;
    public $subCategories;

    /**
     * Consctructor
     */
    public function __construct()
    {
        $this->id = 0;
        $this->name = null;
        $this->description = null;
        $this->user_id = 0;
        $this->course_code = null;
        $this->parent = 0;
        $this->weight = 0;
        $this->visible = false;
        $this->certificate_min_score = 0;
        $this->session_id = 0;
        $this->grade_model_id = 0;
        $this->generateCertificates = false;
        $this->isRequirement = false;
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
    public function get_name()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function get_description()
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function get_user_id()
    {
        return $this->user_id;
    }

    /**
     * @return integer|null
     */
    public function get_certificate_min_score()
    {
        if (!empty($this->certificate_min_score)) {
            return $this->certificate_min_score;
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function get_course_code()
    {
        return $this->course_code;
    }

    /**
     * @return integer
     */
    public function get_parent_id()
    {
        return $this->parent;
    }

    /**
     * @return integer
     */
    public function get_weight()
    {
        return $this->weight;
    }

    /**
     * @return bool
     */
    public function is_locked()
    {
        return isset($this->locked) && $this->locked == 1 ? true : false;
    }

    /**
     * @return boolean
     */
    public function is_visible()
    {
        return $this->visible;
    }

    /**
     * Get $isRequirement
     * @return int
     */
    public function getIsRequirement()
    {
        return $this->isRequirement;
    }

    /**
     * @param int $id
     */
    public function set_id($id)
    {
        $this->id = $id;
    }

    /**
     * @param string $name
     */
    public function set_name($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $description
     */
    public function set_description($description)
    {
        $this->description = $description;
    }

    /**
     * @param int $user_id
     */
    public function set_user_id($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * @param string $course_code
     */
    public function set_course_code($course_code)
    {
        $this->course_code = $course_code;
    }

    /**
     * @param float $min_score
     */
    public function set_certificate_min_score($min_score = null)
    {
        $this->certificate_min_score = $min_score;
    }

    /**
     * @param int $parent
     */
    public function set_parent_id($parent)
    {
        $this->parent = intval($parent);
    }

    /**
     * Filters to int and sets the session ID
     * @param   int     The session ID from the Dokeos course session
     */
    public function set_session_id($session_id = 0)
    {
        $this->session_id = (int) $session_id;
    }

    /**
     * @param $weight
     */
    public function set_weight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * @param $visible
     */
    public function set_visible($visible)
    {
        $this->visible = $visible;
    }

    /**
     * @param int $id
     */
    public function set_grade_model_id($id)
    {
        $this->grade_model_id = $id;
    }

    /**
     * @param $locked
     */
    public function set_locked($locked)
    {
        $this->locked = $locked;
    }

    /**
     * Set $isRequirement
     * @param int $isRequirement
     */
    public function setIsRequirement($isRequirement)
    {
        $this->isRequirement = $isRequirement;
    }

    /**
     * @return null|integer
     */
    public function get_grade_model_id()
    {
        if ($this->grade_model_id < 0) {
            return null;
        }
        return $this->grade_model_id;
    }

    /**
     * @return string
     */
    public function get_type()
    {
        return 'category';
    }

    /**
     * @param bool $from_db
     * @return array|resource
     */
    public function get_skills($from_db = true)
    {
        if ($from_db) {
            $cat_id = $this->get_id();

            $gradebook = new Gradebook();
            $skills = $gradebook->get_skills_by_gradebook($cat_id);
        } else {
            $skills = $this->skills;
        }

        return $skills;
    }

    /**
     * @return array
     */
    public function get_skills_for_select()
    {
        $skills = $this->get_skills();
        $skill_select = array();
        if (!empty($skills)) {
            foreach ($skills as $skill) {
                $skill_select[$skill['id']] = $skill['name'];
            }
        }

        return $skill_select;
    }

    /**
     * Set the generate_certificates value
     * @param int $generateCertificates
     */
    public function setGenerateCertificates($generateCertificates)
    {
        $this->generateCertificates = $generateCertificates;
    }

    /**
     * Get the generate_certificates value
     * @return int
     */
    public function getGenerateCertificates()
    {
        return $this->generateCertificates;
    }

    /**
     * @param int $id
     * @param int $session_id
     *
     * @return array
     */
    public static function load_session_categories($id = null, $session_id = null)
    {
        if (isset($id) && (int) $id === 0) {
            $cats = array();
            $cats[] = self::create_root_category();
            return $cats;
        }

        $courseInfo = api_get_course_info_by_id(api_get_course_int_id());
        $courseCode = $courseInfo['code'];
        $session_id = intval($session_id);

        if (!empty($session_id)) {
            $tbl_grade_categories = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
            $sql = 'SELECT id, course_code
                    FROM '.$tbl_grade_categories.'
                    WHERE session_id = '.$session_id;
            $result_session = Database::query($sql);
            if (Database::num_rows($result_session) > 0) {
                $categoryList = array();
                while ($data_session = Database::fetch_array($result_session)) {
                    $parent_id = $data_session['id'];
                    if ($data_session['course_code'] == $courseCode) {
                        $categories = self::load($parent_id);
                        $categoryList = array_merge($categoryList, $categories);
                    }
                }

                return $categoryList;
            }
        }
    }

    /**
     * Retrieve categories and return them as an array of Category objects
     * @param int $id category id
     * @param int $user_id (category owner)
     * @param string $course_code
     * @param int $parent_id parent category
     * @param bool $visible
     * @param int $session_id (in case we are in a session)
     * @param bool $order_by Whether to show all "session"
     * categories (true) or hide them (false) in case there is no session id
     *
     * @return array
     */
    public static function load(
        $id = null,
        $user_id = null,
        $course_code = null,
        $parent_id = null,
        $visible = null,
        $session_id = null,
        $order_by = null
    ) {
        //if the category given is explicitly 0 (not null), then create
        // a root category object (in memory)
        if (isset($id) && (int) $id === 0) {
            $cats = array();
            $cats[] = self::create_root_category();

            return $cats;
        }

        $tbl_grade_categories = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $sql = 'SELECT * FROM '.$tbl_grade_categories;
        $paramcount = 0;
        if (isset($id)) {
            $sql .= ' WHERE id = '.intval($id);
            $paramcount++;
        }

        if (isset($user_id)) {
            $user_id = intval($user_id);
            if ($paramcount != 0) {
                $sql .= ' AND';
            } else {
                $sql .= ' WHERE';
            }
            $sql .= ' user_id = '.intval($user_id);
            $paramcount++;
        }

        if (isset($course_code)) {
            if ($paramcount != 0) {
                $sql .= ' AND';
            } else {
                $sql .= ' WHERE';
            }

            if ($course_code == '0') {
                $sql .= ' course_code is null ';
            } else {
                $sql .= " course_code = '".Database::escape_string($course_code)."'";
            }

            /*if ($show_session_categories !== true) {
                // a query on the course should show all
                // the categories inside sessions for this course
                // otherwise a special parameter is given to ask explicitely
                $sql .= " AND (session_id IS NULL OR session_id = 0) ";
            } else {*/
            if (empty($session_id)) {
                $sql .= ' AND (session_id IS NULL OR session_id = 0) ';
            } else {
                $sql .= ' AND session_id = '.(int) $session_id.' ';
            }
            //}
            $paramcount++;
        }

        if (isset($parent_id)) {
            if ($paramcount != 0) {
                $sql .= ' AND ';
            } else {
                $sql .= ' WHERE ';
            }
            $sql .= ' parent_id = '.intval($parent_id);
            $paramcount++;
        }

        if (isset($visible)) {
            if ($paramcount != 0) {
                $sql .= ' AND';
            } else {
                $sql .= ' WHERE';
            }
            $sql .= ' visible = '.intval($visible);
        }

        if (!empty($order_by)) {
            if (!empty($order_by) && $order_by != '') {
                $sql .= ' '.$order_by;
            }
        }

        $result = Database::query($sql);

        $categories = array();
        if (Database::num_rows($result) > 0) {
            $categories = self::create_category_objects_from_sql_result(
                $result
            );
        }

        return $categories;
    }

    /**
     * @return Category
     */
    private static function create_root_category()
    {
        $cat = new Category();
        $cat->set_id(0);
        $cat->set_name(get_lang('RootCat'));
        $cat->set_description(null);
        $cat->set_user_id(0);
        $cat->set_course_code(null);
        $cat->set_parent_id(null);
        $cat->set_weight(0);
        $cat->set_visible(1);
        $cat->setGenerateCertificates(0);
        $cat->setIsRequirement(false);

        return $cat;
    }

    /**
     * @param Doctrine\DBAL\Driver\Statement|null $result
     *
     * @return array
     */
    private static function create_category_objects_from_sql_result($result)
    {
        $categories = array();
        while ($data = Database::fetch_array($result)) {
            $cat = new Category();
            $cat->set_id($data['id']);
            $cat->set_name($data['name']);
            $cat->set_description($data['description']);
            $cat->set_user_id($data['user_id']);
            $cat->set_course_code($data['course_code']);
            $cat->set_parent_id($data['parent_id']);
            $cat->set_weight($data['weight']);
            $cat->set_visible($data['visible']);
            $cat->set_session_id($data['session_id']);
            $cat->set_certificate_min_score($data['certif_min_score']);
            $cat->set_grade_model_id($data['grade_model_id']);
            $cat->set_locked($data['locked']);
            $cat->setGenerateCertificates($data['generate_certificates']);
            $cat->setIsRequirement($data['is_requirement']);
            $categories[] = $cat;
        }

        return $categories;
    }

    /**
     * Create a category object from a GradebookCategory entity
     * @param GradebookCategory $gradebookCategory  The entity
     * @return \Category
     */
    public static function createCategoryObjectFromEntity(GradebookCategory $gradebookCategory)
    {
        $category = new Category();
        $category->set_id($gradebookCategory->getId());
        $category->set_name($gradebookCategory->getName());
        $category->set_description($gradebookCategory->getDescription());
        $category->set_user_id($gradebookCategory->getUserId());
        $category->set_course_code($gradebookCategory->getCourseCode());
        $category->set_parent_id($gradebookCategory->getParentId());
        $category->set_weight($gradebookCategory->getWeight());
        $category->set_visible($gradebookCategory->getVisible());
        $category->set_session_id($gradebookCategory->getSessionId());
        $category->set_certificate_min_score(
            $gradebookCategory->getCertifMinScore()
        );
        $category->set_grade_model_id($gradebookCategory->getGradeModelId());
        $category->set_locked($gradebookCategory->getLocked());
        $category->setGenerateCertificates(
            $gradebookCategory->getGenerateCertificates()
        );
        $category->setIsRequirement($gradebookCategory->getIsRequirement());

        return $category;
    }

    /**
     * Insert this category into the database
     */
    public function add()
    {
        if (isset($this->name) && '-1' == $this->name) {
            return false;
        }

        if (isset($this->name) && isset($this->user_id)) {
            $em = Database::getManager();

            $category = new GradebookCategory();
            $category->setName($this->name);
            $category->setDescription($this->description);
            $category->setUserId($this->user_id);
            $category->setCourseCode($this->course_code);
            $category->setParentId($this->parent);
            $category->setWeight($this->weight);
            $category->setVisible($this->visible);
            $category->setCertifMinScore($this->certificate_min_score);
            $category->setSessionId($this->session_id);
            $category->setGenerateCertificates($this->generateCertificates);
            $category->setGradeModelId($this->grade_model_id);
            $category->setIsRequirement($this->isRequirement);
            $category->setLocked(false);

            $em->persist($category);
            $em->flush();

            $id = $category->getId();
            $this->set_id($id);

            if (!empty($id)) {
                $parent_id = $this->get_parent_id();
                $grade_model_id = $this->get_grade_model_id();
                if ($parent_id == 0) {
                    //do something
                    if (isset($grade_model_id) && !empty($grade_model_id) && $grade_model_id != '-1') {
                        $obj = new GradeModel();
                        $components = $obj->get_components($grade_model_id);
                        $default_weight_setting = api_get_setting('gradebook_default_weight');
                        $default_weight = 100;
                        if (isset($default_weight_setting)) {
                            $default_weight = $default_weight_setting;
                        }
                        foreach ($components as $component) {
                            $gradebook = new Gradebook();
                            $params = array();

                            $params['name'] = $component['acronym'];
                            $params['description'] = $component['title'];
                            $params['user_id'] = api_get_user_id();
                            $params['parent_id'] = $id;
                            $params['weight'] = $component['percentage'] / 100 * $default_weight;
                            $params['session_id'] = api_get_session_id();
                            $params['course_code'] = $this->get_course_code();

                            $gradebook->save($params);
                        }
                    }
                }
            }

            $gradebook = new Gradebook();
            $gradebook->update_skills_to_gradebook(
                $this->id,
                $this->get_skills(false)
            );

            return $id;
        }
    }

    /**
     * Update the properties of this category in the database
     * @todo fix me
     */
    public function save()
    {
        $em = Database::getManager();

        $gradebookCategory = $em
            ->getRepository('ChamiloCoreBundle:GradebookCategory')
            ->find($this->id);

        if (empty($gradebookCategory)) {
            return false;
        }

        $gradebookCategory->setName($this->name);
        $gradebookCategory->setDescription($this->description);
        $gradebookCategory->setUserId($this->user_id);
        $gradebookCategory->setCourseCode($this->course_code);
        $gradebookCategory->setParentId($this->parent);
        $gradebookCategory->setWeight($this->weight);
        $gradebookCategory->setVisible($this->visible);
        $gradebookCategory->setCertifMinScore($this->certificate_min_score);
        $gradebookCategory->setGenerateCertificates(
            $this->generateCertificates
        );
        $gradebookCategory->setGradeModelId($this->grade_model_id);
        $gradebookCategory->setIsRequirement($this->isRequirement);

        $em->merge($gradebookCategory);
        $em->flush();

        if (!empty($this->id)) {
            $parent_id = $this->get_parent_id();
            $grade_model_id = $this->get_grade_model_id();
            if ($parent_id == 0) {
                if (isset($grade_model_id) && !empty($grade_model_id) && $grade_model_id != '-1') {
                    $obj = new GradeModel();
                    $components = $obj->get_components($grade_model_id);
                    $default_weight_setting = api_get_setting('gradebook_default_weight');
                    $default_weight = 100;
                    if (isset($default_weight_setting)) {
                        $default_weight = $default_weight_setting;
                    }
                    $final_weight = $this->get_weight();
                    if (!empty($final_weight)) {
                        $default_weight = $this->get_weight();
                    }
                    foreach ($components as $component) {
                        $gradebook = new Gradebook();
                        $params = array();
                        $params['name'] = $component['acronym'];
                        $params['description'] = $component['title'];
                        $params['user_id'] = api_get_user_id();
                        $params['parent_id'] = $this->id;
                        $params['weight'] = $component['percentage'] / 100 * $default_weight;
                        $params['session_id'] = api_get_session_id();
                        $params['course_code'] = $this->get_course_code();
                        $gradebook->save($params);
                    }
                }
            }
        }

        $gradebook = new Gradebook();
        $gradebook->update_skills_to_gradebook(
            $this->id,
            $this->get_skills(false),
            true
        );
    }

    /**
     * Update link weights see #5168
     * @param type $new_weight
     */
    public function update_children_weight($new_weight)
    {
        $links = $this->get_links();
        $old_weight = $this->get_weight();

        if (!empty($links)) {
            foreach ($links as $link_item) {
                if (isset($link_item)) {
                    $new_item_weight = $new_weight * $link_item->get_weight() / $old_weight;
                    $link_item->set_weight($new_item_weight);
                    $link_item->save();
                }
            }
        }
    }

    /**
     * Delete this evaluation from the database
     */
    public function delete()
    {
        $tbl_grade_categories = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $sql = 'DELETE FROM '.$tbl_grade_categories.' WHERE id = '.intval($this->id);
        Database::query($sql);
    }

    /**
     * Not delete this category from the database,when visible=3 is category eliminated
     */
    public function update_category_delete($course_id)
    {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $sql = 'UPDATE '.$table.' SET 
                    visible = 3
                WHERE course_code ="'.Database::escape_string($course_id).'"';
        Database::query($sql);
    }

    /**
     * Show message resource delete
     */
    public function show_message_resource_delete($course_id)
    {
        $tbl_grade_categories = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $sql = 'SELECT count(*) AS num 
                FROM '.$tbl_grade_categories.'
                WHERE
                    course_code = "'.Database::escape_string($course_id).'" AND
                    visible = 3';
        $res = Database::query($sql);
        $option = Database::fetch_array($res, 'ASSOC');
        if ($option['num'] >= 1) {
            return '&nbsp;&nbsp;<span class="resource-deleted">(&nbsp;'.get_lang('ResourceDeleted').'&nbsp;)</span>';
        } else {
            return false;
        }
    }

    /**
     * Shows all information of an category
     */
    public function shows_all_information_an_category($selectcat = '')
    {
        if ($selectcat == '') {
            return null;
        } else {
            $tbl_category = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
            $sql = 'SELECT name,description,user_id,course_code,parent_id,weight,visible,certif_min_score,session_id, generate_certificates, is_requirement
                    FROM '.$tbl_category.' c
                    WHERE c.id='.intval($selectcat);
            $result = Database::query($sql);
            $row = Database::fetch_array($result, 'ASSOC');

            return $row;
        }
    }

    /**
     * Check if a category name (with the same parent category) already exists
     * @param string $name name to check (if not given, the name property of this object will be checked)
     * @param int $parent parent category
     *
     * @return bool
     */
    public function does_name_exist($name, $parent)
    {
        if (!isset($name)) {
            $name = $this->name;
            $parent = $this->parent;
        }
        $tbl_grade_categories = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $sql = "SELECT count(id) AS number
                FROM $tbl_grade_categories
                WHERE name = '".Database::escape_string($name)."'";

        if (api_is_allowed_to_edit()) {
            $parent = self::load($parent);
            $code = $parent[0]->get_course_code();
            $courseInfo = api_get_course_info($code);
            $courseId = $courseInfo['real_id'];
            if (isset($code) && $code != '0') {
                $main_course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
                $sql .= ' AND user_id IN (
                            SELECT user_id FROM '.$main_course_user_table.'
                            WHERE c_id = '.$courseId.' AND status = '.COURSEMANAGER.'
                        )';
            } else {
                $sql .= ' AND user_id = '.api_get_user_id();
            }
        } else {
            $sql .= ' AND user_id = '.api_get_user_id();
        }

        if (!isset($parent)) {
            $sql .= ' AND parent_id is null';
        } else {
            $sql .= ' AND parent_id = '.intval($parent);
        }

        $result = Database::query($sql);
        $number = Database::fetch_row($result);

        return $number[0] != 0;
    }

    /**
     * Checks if the certificate is available for the given user in this category
     * @param   integer    $user_id User ID
     * @return  boolean    True if conditions match, false if fails
     */
    public function is_certificate_available($user_id)
    {
        $score = $this->calc_score(
            $user_id,
            null,
            $this->course_code,
            $this->session_id
        );

        if (isset($score) && isset($score[0])) {
            // Get a percentage score to compare to minimum certificate score
            //$certification_score = $score[0] / $score[1] * 100;

            // Get real score not a percentage.
            $certification_score = $score[0];

            if ($certification_score >= $this->certificate_min_score) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is this category a course ?
     * A category is a course if it has a course code and no parent category.
     */
    public function is_course()
    {
        return (isset($this->course_code) && !empty($this->course_code)
            && (!isset($this->parent) || $this->parent == 0));
    }

    /**
     * Calculate the score of this category
     * @param integer $stud_id student id (default: all students - then the average is returned)
     * @param integer $session_id
     * @return    array (score sum, weight sum)
     *             or null if no scores available
     */
    public function calc_score(
        $stud_id = null,
        $type = null,
        $course_code = '',
        $session_id = null
    ) {
        // Classic
        if (!empty($stud_id) && $type == '') {
            if (!empty($course_code)) {
                $cats = $this->get_subcategories(
                    $stud_id,
                    $course_code,
                    $session_id
                );
                $evals = $this->get_evaluations($stud_id, false, $course_code);
                $links = $this->get_links($stud_id, false, $course_code);
            } else {
                $cats = $this->get_subcategories($stud_id);
                $evals = $this->get_evaluations($stud_id);
                $links = $this->get_links($stud_id);
            }

            // Calculate score
            $count = 0;
            $ressum = 0;
            $weightsum = 0;

            if (!empty($cats)) {
                /** @var Category $cat */
                foreach ($cats as $cat) {
                    $cat->set_session_id($session_id);
                    $cat->set_course_code($course_code);
                    $cat->setStudentList($this->getStudentList());
                    $score = $cat->calc_score(
                        $stud_id,
                        null,
                        $course_code,
                        $session_id
                    );

                    $catweight = 0;
                    if ($cat->get_weight() != 0) {
                        $catweight = $cat->get_weight();
                        $weightsum += $catweight;
                    }

                    if (isset($score) && !empty($score[1]) && !empty($catweight)) {
                        $ressum += $score[0] / $score[1] * $catweight;
                    }
                }
            }

            $students = array();

            if (!empty($evals)) {
                /** @var Evaluation $eval */
                foreach ($evals as $eval) {
                    $eval->setStudentList($this->getStudentList());
                    $evalres = $eval->calc_score($stud_id, null);

                    if (isset($evalres) && $eval->get_weight() != 0) {
                        $evalweight = $eval->get_weight();
                        $weightsum += $evalweight;
                        $count++;
                        if (!empty($evalres[1])) {
                            $ressum += $evalres[0] / $evalres[1] * $evalweight;
                        }
                    } else {
                        if ($eval->get_weight() != 0) {
                            $evalweight = $eval->get_weight();
                            $weightsum += $evalweight;
                        }
                    }
                }
            }

            if (!empty($links)) {
                /** @var EvalLink|ExerciseLink $link */
                foreach ($links as $link) {
                    $link->setStudentList($this->getStudentList());

                    if ($session_id) {
                        $link->set_session_id($session_id);
                    }

                    $linkres = $link->calc_score($stud_id, null);
                    if (!empty($linkres) && $link->get_weight() != 0) {
                        $students[$stud_id] = $linkres[0];
                        $linkweight = $link->get_weight();
                        $link_res_denom = $linkres[1] == 0 ? 1 : $linkres[1];
                        $count++;
                        $weightsum += $linkweight;
                        $ressum += $linkres[0] / $link_res_denom * $linkweight;
                    } else {
                        // Adding if result does not exists
                        if ($link->get_weight() != 0) {
                            $linkweight = $link->get_weight();
                            $weightsum += $linkweight;
                        }
                    }
                }
            }
        } else {
            if (!empty($course_code)) {
                $cats = $this->get_subcategories(
                    null,
                    $course_code,
                    $session_id
                );
                $evals = $this->get_evaluations(null, false, $course_code);
                $links = $this->get_links(null, false, $course_code);
            } else {
                $cats = $this->get_subcategories(null);
                $evals = $this->get_evaluations(null);
                $links = $this->get_links(null);
            }

            // Calculate score
            $count = 0;
            $ressum = 0;
            $weightsum = 0;
            $bestResult = 0;

            if (!empty($cats)) {
                /** @var Category $cat */
                foreach ($cats as $cat) {
                    $cat->setStudentList($this->getStudentList());
                    $score = $cat->calc_score(
                        null,
                        $type,
                        $course_code,
                        $session_id
                    );

                    $catweight = 0;
                    if ($cat->get_weight() != 0) {
                        $catweight = $cat->get_weight();
                        $weightsum += $catweight;
                    }

                    if (isset($score) && !empty($score[1]) && !empty($catweight)) {
                        $ressum += $score[0] / $score[1] * $catweight;

                        if ($ressum > $bestResult) {
                            $bestResult = $ressum;
                        }
                    }

                }
            }

            if (!empty($evals)) {
                /** @var Evaluation $eval */
                foreach ($evals as $eval) {
                    $evalres = $eval->calc_score(null, $type);
                    $eval->setStudentList($this->getStudentList());

                    if (isset($evalres) && $eval->get_weight() != 0) {
                        $evalweight = $eval->get_weight();
                        $weightsum += $evalweight;
                        $count++;
                        if (!empty($evalres[1])) {
                            $ressum += $evalres[0] / $evalres[1] * $evalweight;
                        }

                        if ($ressum > $bestResult) {
                            $bestResult = $ressum;
                        }

                    } else {
                        if ($eval->get_weight() != 0) {
                            $evalweight = $eval->get_weight();
                            $weightsum += $evalweight;
                        }
                    }
                }
            }
            if (!empty($links)) {
                /** @var EvalLink|ExerciseLink $link */
                foreach ($links as $link) {
                    $link->setStudentList($this->getStudentList());

                    if ($session_id) {
                        $link->set_session_id($session_id);
                    }

                    $linkres = $link->calc_score($stud_id, $type);
                    if (!empty($linkres) && $link->get_weight() != 0) {
                        $students[$stud_id] = $linkres[0];
                        $linkweight = $link->get_weight();
                        $link_res_denom = $linkres[1] == 0 ? 1 : $linkres[1];

                        $count++;
                        $weightsum += $linkweight;
                        $ressum += $linkres[0] / $link_res_denom * $linkweight;

                        if ($ressum > $bestResult) {
                            $bestResult = $ressum;
                        }
                    } else {
                        // Adding if result does not exists
                        if ($link->get_weight() != 0) {
                            $linkweight = $link->get_weight();
                            $weightsum += $linkweight;
                        }
                    }
                }
            }
        }

        switch ($type) {
            case 'best':
                if (empty($bestResult)) {
                    return null;
                }
                return array($bestResult, $weightsum);
                break;
            case 'average':
                if (empty($ressum)) {
                    return null;
                }
                return array($ressum, $weightsum);
                break;
            case 'ranking':
                // category ranking is calculated in gradebook_data_generator.class.php
                // function get_data
                return null;
                return AbstractLink::getCurrentUserRanking($stud_id, array());
                break;
            default:
                return array($ressum, $weightsum);
                break;
        }
    }

    /**
     * Delete this category and every subcategory, evaluation and result inside
     */
    public function delete_all()
    {
        $cats = self::load(null, null, $this->course_code, $this->id, null);
        $evals = Evaluation::load(
            null,
            null,
            $this->course_code,
            $this->id,
            null
        );

        $links = LinkFactory::load(
            null,
            null,
            null,
            null,
            $this->course_code,
            $this->id,
            null
        );

        if (!empty($cats)) {
            /** @var Category $cat */
            foreach ($cats as $cat) {
                $cat->delete_all();
                $cat->delete();
            }
        }

        if (!empty($evals)) {
            /** @var Evaluation $eval */
            foreach ($evals as $eval) {
                $eval->delete_with_results();
            }
        }

        if (!empty($links)) {
            /** @var AbstractLink $link */
            foreach ($links as $link) {
                $link->delete();
            }
        }

        $this->delete();
    }

    /**
     * Return array of Category objects where a student is subscribed to.
     *
     * @param integer $stud_id
     * @param string $course_code
     * @param integer $session_id
     * @return array
     */
    public function get_root_categories_for_student($stud_id, $course_code = null, $session_id = null)
    {
        $main_course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_grade_categories = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);

        $sql = "SELECT * FROM $tbl_grade_categories WHERE parent_id = 0";

        if (!api_is_allowed_to_edit()) {
            $sql .= ' AND visible = 1';
            //proceed with checks on optional parameters course & session
            if (!empty($course_code)) {
                // TODO: considering it highly improbable that a user would get here
                // if he doesn't have the rights to view this course and this
                // session, we don't check his registration to these, but this
                // could be an improvement
                if (!empty($session_id)) {
                    $sql .= " AND course_code = '".Database::escape_string($course_code)."' AND session_id = ".(int) $session_id;
                } else {
                    $sql .= " AND course_code = '".Database::escape_string($course_code)."' AND session_id is null OR session_id=0";
                }
            } else {
                //no optional parameter, proceed as usual
                $sql .= ' AND course_code in
                     (
                        SELECT c.code
                        FROM '.$main_course_user_table.' cu INNER JOIN '.$courseTable.' c
                        ON (cu.c_id = c.id)
                        WHERE cu.user_id = '.intval($stud_id).'
                        AND cu.status = '.STUDENT.'
                    )';
            }
        } elseif (api_is_allowed_to_edit() && !api_is_platform_admin()) {
            //proceed with checks on optional parameters course & session
            if (!empty($course_code)) {
                // TODO: considering it highly improbable that a user would get here
                // if he doesn't have the rights to view this course and this
                // session, we don't check his registration to these, but this
                // could be an improvement
                $sql .= " AND course_code  = '".Database::escape_string($course_code)."'";
                if (!empty($session_id)) {
                    $sql .= " AND session_id = ".(int) $session_id;
                } else {
                    $sql .= "AND session_id IS NULL OR session_id=0";
                }
            } else {
                $sql .= ' AND course_code IN
                     (
                        SELECT c.code
                        FROM '.$main_course_user_table.' cu INNER JOIN '.$courseTable.' c
                        ON (cu.c_id = c.id)
                        WHERE
                            cu.user_id = '.api_get_user_id().' AND
                            cu.status = '.COURSEMANAGER.'
                    )';
            }
        } elseif (api_is_platform_admin()) {
            if (isset($session_id) && $session_id != 0) {
                $sql .= ' AND session_id='.intval($session_id);
            } else {
                $sql .= ' AND coalesce(session_id,0)=0';
            }
        }
        $result = Database::query($sql);
        $cats = self::create_category_objects_from_sql_result($result);

        // course independent categories
        if (empty($course_code)) {
            $cats = self::get_independent_categories_with_result_for_student(
                0,
                $stud_id,
                $cats
            );
        }

        return $cats;
    }

    /**
     * Return array of Category objects where a teacher is admin for.
     *
     * @param integer $user_id (to return everything, use 'null' here)
     * @param string $course_code (optional)
     * @param integer $session_id (optional)
     * @return array
     */
    public function get_root_categories_for_teacher($user_id, $course_code = null, $session_id = null)
    {
        if ($user_id == null) {
            return self::load(null, null, $course_code, 0, null, $session_id);
        }

        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $main_course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_grade_categories = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);

        $sql = 'SELECT * FROM '.$tbl_grade_categories.'
                WHERE parent_id = 0 ';
        if (!empty($course_code)) {
            $sql .= " AND course_code = '".Database::escape_string($course_code)."' ";
            if (!empty($session_id)) {
                $sql .= " AND session_id = ".(int) $session_id;
            }
        } else {
            $sql .= ' AND course_code in
                 (
                    SELECT c.code
                    FROM '.$main_course_user_table.' cu
                    INNER JOIN '.$courseTable.' c
                    ON (cu.c_id = c.id)
                    WHERE user_id = '.intval($user_id).'
                )';
        }
        $result = Database::query($sql);
        $cats = self::create_category_objects_from_sql_result($result);
        // course independent categories
        if (isset($course_code)) {
            $indcats = self::load(
                null,
                $user_id,
                $course_code,
                0,
                null,
                $session_id
            );
            $cats = array_merge($cats, $indcats);
        }

        return $cats;
    }

    /**
     * Can this category be moved to somewhere else ?
     * The root and courses cannot be moved.
     * @return bool
     */
    public function is_movable()
    {
        return !(!isset($this->id) || $this->id == 0 || $this->is_course());
    }

    /**
     * Generate an array of possible categories where this category can be moved to.
     * Notice: its own parent will be included in the list: it's up to the frontend
     * to disable this element.
     * @return array 2-dimensional array - every element contains 3 subelements (id, name, level)
     */
    public function get_target_categories()
    {
        // the root or a course -> not movable
        if (!$this->is_movable()) {
            return null;
        } else {
            // otherwise:
            // - course independent category
            //   -> movable to root or other independent categories
            // - category inside a course
            //   -> movable to root, independent categories or categories inside the course

            $user = (api_is_platform_admin() ? null : api_get_user_id());
            $targets = array();
            $level = 0;

            $root = array(0, get_lang('RootCat'), $level);
            $targets[] = $root;

            if (isset($this->course_code) && !empty($this->course_code)) {
                $crscats = self::load(null, null, $this->course_code, 0);
                foreach ($crscats as $cat) {
                    if ($this->can_be_moved_to_cat($cat)) {
                        $targets[] = array($cat->get_id(), $cat->get_name(), $level + 1);
                        $targets = $this->add_target_subcategories($targets, $level + 1, $cat->get_id());
                    }
                }
            }

            $indcats = self::load(null, $user, 0, 0);
            foreach ($indcats as $cat) {
                if ($this->can_be_moved_to_cat($cat)) {
                    $targets[] = array($cat->get_id(), $cat->get_name(), $level + 1);
                    $targets = $this->add_target_subcategories($targets, $level + 1, $cat->get_id());
                }
            }

            return $targets;
        }
    }

    /**
     * Internal function used by get_target_categories()
     * @param array $targets
     * @param integer $level
     * @param int $catid
     *
     * @return array
     */
    private function add_target_subcategories($targets, $level, $catid)
    {
        $subcats = self::load(null, null, null, $catid);
        foreach ($subcats as $cat) {
            if ($this->can_be_moved_to_cat($cat)) {
                $targets[] = array($cat->get_id(), $cat->get_name(), $level + 1);
                $targets = $this->add_target_subcategories($targets, $level + 1, $cat->get_id());
            }
        }

        return $targets;
    }

    /**
     * Internal function used by get_target_categories() and add_target_subcategories()
     * Can this category be moved to the given category ?
     * Impossible when origin and target are the same... children won't be processed
     * either. (a category can't be moved to one of its own children)
     */
    private function can_be_moved_to_cat($cat)
    {
        return $cat->get_id() != $this->get_id();
    }

    /**
     * Move this category to the given category.
     * If this category moves from inside a course to outside,
     * its course code must be changed, as well as the course code
     * of all underlying categories and evaluations. All links will
     * be deleted as well !
     */
    public function move_to_cat($cat)
    {
        $this->set_parent_id($cat->get_id());
        if ($this->get_course_code() != $cat->get_course_code()) {
            $this->set_course_code($cat->get_course_code());
            $this->apply_course_code_to_children();
        }
        $this->save();
    }

    /**
     * Internal function used by move_to_cat()
     */
    private function apply_course_code_to_children()
    {
        $cats = self::load(null, null, null, $this->id, null);
        $evals = Evaluation::load(null, null, null, $this->id, null);
        $links = LinkFactory::load(null, null, null, null, null, $this->id, null);

        foreach ($cats as $cat) {
            $cat->set_course_code($this->get_course_code());
            $cat->save();
            $cat->apply_course_code_to_children();
        }

        foreach ($evals as $eval) {
            $eval->set_course_code($this->get_course_code());
            $eval->save();
        }

        foreach ($links as $link) {
            $link->delete();
        }
    }

    /**
     * Generate an array of all categories the user can navigate to
     */
    public function get_tree()
    {
        $targets = array();
        $level = 0;
        $root = array(0, get_lang('RootCat'), $level);
        $targets[] = $root;

        // course or platform admin
        if (api_is_allowed_to_edit()) {
            $user = (api_is_platform_admin() ? null : api_get_user_id());
            $cats = self::get_root_categories_for_teacher($user);
            foreach ($cats as $cat) {
                $targets[] = array($cat->get_id(), $cat->get_name(), $level + 1);
                $targets = self::add_subtree($targets, $level + 1, $cat->get_id(), null);
            }
        } else {
            // student
            $cats = self::get_root_categories_for_student(api_get_user_id());
            foreach ($cats as $cat) {
                $targets[] = array($cat->get_id(), $cat->get_name(), $level + 1);
                $targets = self::add_subtree($targets, $level + 1, $cat->get_id(), 1);
            }
        }

        return $targets;
    }

    /**
     * Internal function used by get_tree()
     * @param integer $level
     * @param null|integer $visible
     */
    private function add_subtree($targets, $level, $catid, $visible)
    {
        $subcats = self::load(null, null, null, $catid, $visible);

        if (!empty($subcats)) {
            foreach ($subcats as $cat) {
                $targets[] = array($cat->get_id(), $cat->get_name(), $level + 1);
                $targets = self::add_subtree($targets, $level + 1, $cat->get_id(), $visible);
            }
        }

        return $targets;
    }

    /**
     * Generate an array of courses that a teacher hasn't created a category for.
     * @param integer $user_id
     * @return array 2-dimensional array - every element contains 2 subelements (code, title)
     */
    public function get_not_created_course_categories($user_id)
    {
        $tbl_main_courses = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_main_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_grade_categories = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);

        $sql = 'SELECT DISTINCT(code), title
                FROM '.$tbl_main_courses.' cc, '.$tbl_main_course_user.' cu
                WHERE 
                    cc.id = cu.c_id AND 
                    cu.status = '.COURSEMANAGER;
        if (!api_is_platform_admin()) {
            $sql .= ' AND cu.user_id = '.$user_id;
        }
        $sql .= ' AND cc.code NOT IN
             (
                SELECT course_code FROM '.$tbl_grade_categories.'
                WHERE
                    parent_id = 0 AND
                    course_code IS NOT NULL
                )';
        $result = Database::query($sql);

        $cats = array();
        while ($data = Database::fetch_array($result)) {
            $cats[] = array($data['code'], $data['title']);
        }

        return $cats;
    }

    /**
     * Generate an array of all courses that a teacher is admin of.
     * @param integer $user_id
     * @return array 2-dimensional array - every element contains 2 subelements (code, title)
     */
    public function get_all_courses($user_id)
    {
        $tbl_main_courses = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_main_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $sql = 'SELECT DISTINCT(code), title
                FROM '.$tbl_main_courses.' cc, '.$tbl_main_course_user.' cu
                WHERE cc.id = cu.c_id AND cu.status = '.COURSEMANAGER;
        if (!api_is_platform_admin()) {
            $sql .= ' AND cu.user_id = '.intval($user_id);
        }

        $result = Database::query($sql);
        $cats = array();
        while ($data = Database::fetch_array($result)) {
            $cats[] = array($data['code'], $data['title']);
        }

        return $cats;
    }

    /**
     * Apply the same visibility to every subcategory, evaluation and link
     */
    public function apply_visibility_to_children()
    {
        $cats = self::load(null, null, null, $this->id, null);
        $evals = Evaluation::load(null, null, null, $this->id, null);
        $links = LinkFactory::load(null, null, null, null, null, $this->id, null);
        if (!empty($cats)) {
            foreach ($cats as $cat) {
                $cat->set_visible($this->is_visible());
                $cat->save();
                $cat->apply_visibility_to_children();
            }
        }
        if (!empty($evals)) {
            foreach ($evals as $eval) {
                $eval->set_visible($this->is_visible());
                $eval->save();
            }
        }
        if (!empty($links)) {
            foreach ($links as $link) {
                $link->set_visible($this->is_visible());
                $link->save();
            }
        }
    }

    /**
     * Check if a category contains evaluations with a result for a given student
     */
    public function has_evaluations_with_results_for_student($stud_id)
    {
        $evals = Evaluation::get_evaluations_with_result_for_student($this->id, $stud_id);
        if (count($evals) != 0) {
            return true;
        } else {
            $cats = self::load(
                null,
                null,
                null,
                $this->id,
                api_is_allowed_to_edit() ? null : 1
            );
            foreach ($cats as $cat) {
                if ($cat->has_evaluations_with_results_for_student($stud_id)) {
                    return true;
                }
            }

            return false;
        }
    }

    /**
     * Retrieve all categories inside a course independent category
     * that should be visible to a student.
     * @param integer $cat_id parent category
     * @param $stud_id student id
     * @param array $cats optional: if defined, the categories will be added to this array
     * @return array
     */
    public function get_independent_categories_with_result_for_student($cat_id, $stud_id, $cats = array())
    {
        $creator = api_is_allowed_to_edit() && !api_is_platform_admin() ? api_get_user_id() : null;

        $crsindcats = self::load(
            null,
            $creator,
            '0',
            $cat_id,
            api_is_allowed_to_edit() ? null : 1
        );

        if (!empty($crsindcats)) {
            foreach ($crsindcats as $crsindcat) {
                if ($crsindcat->has_evaluations_with_results_for_student($stud_id)) {
                    $cats[] = $crsindcat;
                }
            }
        }

        return $cats;
    }

    /**
     * Return the session id (in any case, even if it's null or 0)
     * @return  int Session id (can be null)
     */
    public function get_session_id()
    {
        return $this->session_id;
    }

    /**
     * Get appropriate subcategories visible for the user (and optionally the course and session)
     * @param int    $stud_id student id (default: all students)
     * @param string $course_code Course code (optional)
     * @param int    $session_id Session ID (optional)
     * @param bool   $order

     * @return array Array of subcategories
     */
    public function get_subcategories($stud_id = null, $course_code = null, $session_id = null, $order = null)
    {
        if (!empty($session_id)) {
            /*$tbl_grade_categories = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
            $sql = 'SELECT id FROM '.$tbl_grade_categories. ' WHERE session_id = '.$session_id;
            $result_session = Database::query($sql);
            if (Database::num_rows($result_session) > 0) {
                $data_session = Database::fetch_array($result_session);
                $parent_id = $data_session['id'];
                return self::load(null, null, null, $parent_id, null, null, $order);
            }*/
        }

        // 1 student
        if (isset($stud_id)) {
            // Special case: this is the root
            if ($this->id == 0) {
                return self::get_root_categories_for_student($stud_id, $course_code, $session_id);
            } else {
                return self::load(
                    null,
                    null,
                    $course_code,
                    $this->id,
                    api_is_allowed_to_edit() ? null : 1,
                    $session_id,
                    $order
                );
            }
        } else {
            // All students
            // Course admin
            if (api_is_allowed_to_edit() && !api_is_platform_admin()) {

                // root
                if ($this->id == 0) {
                    return $this->get_root_categories_for_teacher(api_get_user_id(), $course_code, $session_id, false);
                    // inside a course
                } elseif (!empty($this->course_code)) {
                    return self::load(null, null, $this->course_code, $this->id, null, $session_id, $order);
                } elseif (!empty($course_code)) {
                    return self::load(null, null, $course_code, $this->id, null, $session_id, $order);
                    // course independent
                } else {
                    return self::load(null, api_get_user_id(), 0, $this->id, null);
                }
            } elseif (api_is_platform_admin()) {
                // platform admin
                // we explicitly avoid listing subcats from another session
                return self::load(null, null, $course_code, $this->id, null, $session_id, $order);
            }
        }

        return array();
    }

    /**
     * Get appropriate evaluations visible for the user
     * @param int $stud_id student id (default: all students)
     * @param boolean $recursive process subcategories (default: no recursion)
     * @param string $course_code
     * @param int $sessionId
     *
     * @return array
     */
    public function get_evaluations(
        $stud_id = null,
        $recursive = false,
        $course_code = '',
        $sessionId = 0
    ) {
        $evals = array();

        if (empty($course_code)) {
            $course_code = api_get_course_id();
        }

        if (empty($sessionId)) {
            $sessionId = api_get_session_id();
        }

        // 1 student
        if (isset($stud_id) && !empty($stud_id)) {
            // Special case: this is the root
            if ($this->id == 0) {
                $evals = Evaluation::get_evaluations_with_result_for_student(0, $stud_id);
            } else {
                $evals = Evaluation::load(
                    null,
                    null,
                    $course_code,
                    $this->id,
                    api_is_allowed_to_edit() ? null : 1
                );
            }
        } else {
            // All students
            // course admin
            if ((api_is_allowed_to_edit() || api_is_drh() || api_is_session_admin()) &&
                !api_is_platform_admin()
            ) {
                // root
                if ($this->id == 0) {
                    $evals = Evaluation::load(null, api_get_user_id(), null, $this->id, null);
                } elseif (isset($this->course_code) && !empty($this->course_code)) {
                    // inside a course
                    $evals = Evaluation::load(null, null, $course_code, $this->id, null);
                } else {
                    // course independent
                    $evals = Evaluation::load(null, api_get_user_id(), null, $this->id, null);
                }
            } else {
                $evals = Evaluation::load(null, null, $course_code, $this->id, null);
            }
        }

        if ($recursive) {
            $subcats = $this->get_subcategories($stud_id, $course_code, $sessionId);

            if (!empty($subcats)) {
                foreach ($subcats as $subcat) {
                    $subevals = $subcat->get_evaluations($stud_id, true, $course_code);
                    $evals = array_merge($evals, $subevals);
                }
            }
        }

        return $evals;
    }

    /**
     * Get appropriate links visible for the user
     * @param int $stud_id student id (default: all students)
     * @param boolean $recursive process subcategories (default: no recursion)
     * @param string $course_code
     * @param int $sessionId
     *
     * @return array
     */
    public function get_links(
        $stud_id = null,
        $recursive = false,
        $course_code = '',
        $sessionId = 0
    ) {
        $links = array();

        if (empty($course_code)) {
            $course_code = api_get_course_id();
        }

        if (empty($sessionId)) {
            $sessionId = api_get_session_id();
        }

        // no links in root or course independent categories
        if ($this->id == 0) {
        } elseif (isset($stud_id)) {
            // 1 student $stud_id
            $links = LinkFactory::load(
                null,
                null,
                null,
                null,
                empty($this->course_code) ? null : $course_code,
                $this->id,
                api_is_allowed_to_edit() ? null : 1
            );
            //} elseif (api_is_allowed_to_edit() || api_is_drh() || api_is_session_admin()) {
        } else {
            // All students -> only for course/platform admin
            $links = LinkFactory::load(
                null,
                null,
                null,
                null,
                empty($this->course_code) ? null : $this->course_code,
                $this->id,
                null
            );
        }

        if ($recursive) {
            $subcats = $this->get_subcategories(
                $stud_id,
                $course_code,
                $sessionId
            );
            if (!empty($subcats)) {
                /** @var Category $subcat */
                foreach ($subcats as $subcat) {
                    $sublinks = $subcat->get_links(
                        $stud_id,
                        false,
                        $course_code,
                        $sessionId
                    );
                    $links = array_merge($links, $sublinks);
                }
            }
        }

        return $links;
    }

    /**
     * Get all the categories from with the same given direct parent
     * @param int $catId Category parent ID
     * @return array Array of Category objects
     */
    public function getCategories($catId)
    {
        $tblGradeCategories = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $sql = 'SELECT * FROM '.$tblGradeCategories.'
                WHERE parent_id = '.intval($catId);

        $result = Database::query($sql);
        $categories = self::create_category_objects_from_sql_result($result);

        return $categories;
    }

    /**
     * Gets the type for the current object
     * @return string 'C' to represent "Category" object type
     */
    public function get_item_type()
    {
        return 'C';
    }

    /**
     * @param array $skills
     */
    public function set_skills($skills)
    {
        $this->skills = $skills;
    }

    /**
     * @return null
     */
    public function get_date()
    {
        return null;
    }

    /**
     * @return string
     */
    public function get_icon_name()
    {
        return 'cat';
    }

    /**
     * Find category by name
     * @param string $name_mask search string
     * @return array category objects matching the search criterium
     */
    public function find_category($name_mask, $allcat)
    {
        $categories = array();
        foreach ($allcat as $search_cat) {
            if (!(strpos(strtolower($search_cat->get_name()), strtolower($name_mask)) === false)) {
                $categories[] = $search_cat;
            }
        }

        return $categories;
    }

    /**
     * This function, locks a category , only one who can unlock it is
     * the platform administrator.
     * @param int locked 1 or unlocked 0

     * @return boolean|null
     * */
    public function lock($locked)
    {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $sql = "UPDATE $table SET locked = '".intval($locked)."'
                WHERE id='".intval($this->id)."'";
        Database::query($sql);
    }

    /**
     * @param $locked
     */
    public function lock_all_items($locked)
    {
        if (api_get_setting('gradebook_locking_enabled') == 'true') {
            $this->lock($locked);
            $evals_to_lock = $this->get_evaluations();
            if (!empty($evals_to_lock)) {
                foreach ($evals_to_lock as $item) {
                    $item->lock($locked);
                }
            }

            $link_to_lock = $this->get_links();
            if (!empty($link_to_lock)) {
                foreach ($link_to_lock as $item) {
                    $item->lock($locked);
                }
            }

            $event_type = LOG_GRADEBOOK_UNLOCKED;
            if ($locked == 1) {
                $event_type = LOG_GRADEBOOK_LOCKED;
            }
            Event::addEvent($event_type, LOG_GRADEBOOK_ID, $this->id);
        }
    }

    /**
     * Generates a certificate for this user if everything matches
     * @param int $category_id
     * @param int $user_id
     * @return bool|string
     */
    public static function register_user_certificate($category_id, $user_id)
    {
        $courseId = api_get_course_int_id();
        $courseCode = api_get_course_id();
        $sessionId = api_get_session_id();

        // Generating the total score for a course
        $cats_course = self::load(
            $category_id,
            null,
            null,
            null,
            null,
            $sessionId,
            false
        );

        /** @var Category $category */
        $category = $cats_course[0];

        //@todo move these in a function
        $sum_categories_weight_array = array();
        if (isset($cats_course) && !empty($cats_course)) {
            $categories = self::load(null, null, null, $category_id);
            if (!empty($categories)) {
                foreach ($categories as $subCategory) {
                    $sum_categories_weight_array[$subCategory->get_id()] = $subCategory->get_weight();
                }
            } else {
                $sum_categories_weight_array[$category_id] = $cats_course[0]->get_weight();
            }
        }

        $main_weight = $cats_course[0]->get_weight();
        $cattotal = self::load($category_id);
        $scoretotal = $cattotal[0]->calc_score($user_id);

        // Do not remove this the gradebook/lib/fe/gradebooktable.class.php
        // file load this variable as a global
        $scoredisplay = ScoreDisplay::instance();
        $my_score_in_gradebook = $scoredisplay->display_score($scoretotal, SCORE_SIMPLE);

        // A student always sees only the teacher's repartition
        $scoretotal_display = $scoredisplay->display_score($scoretotal, SCORE_DIV_PERCENT);

        $userFinishedCourse = self::userFinishedCourse(
            $user_id,
            $cats_course[0],
            0,
            $courseCode,
            $sessionId,
            true
        );

        if (!$userFinishedCourse) {
            return false;
        }

        $skillToolEnabled = api_get_setting('allow_skills_tool') == 'true';
        $userHasSkills = false;

        if ($skillToolEnabled) {
            $skill = new Skill();
            $skill->add_skill_to_user(
                $user_id,
                $category_id,
                $courseId,
                $sessionId
            );

            $objSkillRelUser = new SkillRelUser();
            $userSkills = $objSkillRelUser->get_user_skills($user_id, $courseId, $sessionId);
            $userHasSkills = !empty($userSkills);

            if (!$category->getGenerateCertificates() && $userHasSkills) {
                return [
                    'badge_link' => Display::toolbarButton(
                        get_lang('ExportBadges'),
                        api_get_path(WEB_CODE_PATH)."gradebook/get_badges.php?user=$user_id",
                        'external-link'
                    ),
                ];
            }
        }

        $my_certificate = GradebookUtils::get_certificate_by_user_id(
            $cats_course[0]->get_id(),
            $user_id
        );

        if (empty($my_certificate)) {
            GradebookUtils::registerUserInfoAboutCertificate(
                $category_id,
                $user_id,
                $my_score_in_gradebook,
                api_get_utc_datetime()
            );
            $my_certificate = GradebookUtils::get_certificate_by_user_id(
                $cats_course[0]->get_id(),
                $user_id
            );
        }

        $html = array();
        if (!empty($my_certificate)) {
            $certificate_obj = new Certificate($my_certificate['id']);
            $fileWasGenerated = $certificate_obj->html_file_is_generated();

            if (!empty($fileWasGenerated)) {
                $url = api_get_path(WEB_PATH).'certificates/index.php?id='.$my_certificate['id'];
                $certificates = Display::toolbarButton(
                    get_lang('DisplayCertificate'),
                    $url,
                    'eye',
                    'primary'
                );

                $exportToPDF = Display::url(
                    Display::return_icon(
                        'pdf.png',
                        get_lang('ExportToPDF'),
                        array(),
                        ICON_SIZE_MEDIUM
                    ),
                    "$url&action=export"
                );

                $hideExportLink = api_get_setting('hide_certificate_export_link');
                $hideExportLinkStudent = api_get_setting('hide_certificate_export_link_students');
                if ($hideExportLink === 'true' || (api_is_student() && $hideExportLinkStudent === 'true')) {
                    $exportToPDF = null;
                }

                $html = array(
                    'certificate_link' => $certificates,
                    'pdf_link' => $exportToPDF,
                    'pdf_url' => "$url&action=export",
                );

                if ($skillToolEnabled && $userHasSkills) {
                    $html['badge_link'] = Display::toolbarButton(
                        get_lang('ExportBadges'),
                        api_get_path(WEB_CODE_PATH)."gradebook/get_badges.php?user=$user_id",
                        'external-link'
                    );
                }
            }

            return $html;
        }
    }

    /**
     * @param int $catId
     * @param array $userList
     */
    public static function generateCertificatesInUserList($catId, $userList)
    {
        if (!empty($userList)) {
            foreach ($userList as $userInfo) {
                self::register_user_certificate($catId, $userInfo['user_id']);
            }
        }
    }

    /**
     * @param int $catId
     * @param array $userList
     */
    public static function exportAllCertificates(
        $catId,
        $userList = array()
    ) {
        $orientation = api_get_configuration_value('certificate_pdf_orientation');

        $params['orientation'] = 'landscape';
        if (!empty($orientation)) {
            $params['orientation'] = $orientation;
        }

        $params['left'] = 0;
        $params['right'] = 0;
        $params['top'] = 0;
        $params['bottom'] = 0;
        $page_format = $params['orientation'] == 'landscape' ? 'A4-L' : 'A4';
        $pdf = new PDF($page_format, $params['orientation'], $params);

        $certificate_list = GradebookUtils::get_list_users_certificates($catId, $userList);
        $certificate_path_list = array();

        if (!empty($certificate_list)) {
            foreach ($certificate_list as $index=>$value) {
                $list_certificate = GradebookUtils::get_list_gradebook_certificates_by_user_id(
                    $value['user_id'],
                    $catId
                );
                foreach ($list_certificate as $value_certificate) {
                    $certificate_obj = new Certificate($value_certificate['id']);
                    $certificate_obj->generate(array('hide_print_button' => true));
                    if ($certificate_obj->html_file_is_generated()) {
                        $certificate_path_list[] = $certificate_obj->html_file;
                    }
                }
            }
        }

        if (!empty($certificate_path_list)) {
            // Print certificates (without the common header/footer/watermark
            //  stuff) and return as one multiple-pages PDF
            $pdf->html_to_pdf(
                $certificate_path_list,
                get_lang('Certificates'),
                null,
                false,
                false
            );
        }
    }

    /**
     * @param int $catId
     */
    public static function deleteAllCertificates($catId)
    {
        $certificate_list = GradebookUtils::get_list_users_certificates($catId);
        if (!empty($certificate_list)) {
            foreach ($certificate_list as $index => $value) {
                $list_certificate = GradebookUtils::get_list_gradebook_certificates_by_user_id(
                    $value['user_id'],
                    $catId
                );
                foreach ($list_certificate as $value_certificate) {
                    $certificate_obj = new Certificate($value_certificate['id']);
                    $certificate_obj->delete(true);
                }
            }
        }
    }

    /**
     * Check whether a user has finished a course by its gradebook
     * @param int $userId The user ID
     * @param \Category $category Optional. The gradebook category.
     *         To check by the gradebook category
     * @param int $categoryId Optional. The gradebook category ID.
     *         To check by the category ID
     * @param string $courseCode Optional. The course code
     * @param int $sessionId Optional. The session ID
     * @param boolean $recalcutateScore Whether recalculate the score
     * @return boolean
     */
    public static function userFinishedCourse(
        $userId,
        \Category $category = null,
        $categoryId = 0,
        $courseCode = null,
        $sessionId = 0,
        $recalcutateScore = false
    ) {
        if (is_null($category) && empty($categoryId)) {
            return false;
        }

        $courseCode = empty($courseCode) ? api_get_course_id() : $courseCode;
        $sessionId = empty($sessionId) ? api_get_session_id() : $sessionId;

        if (is_null($category) && !empty($categoryId)) {
            $cats_course = self::load(
                $categoryId,
                null,
                $courseCode,
                null,
                null,
                $sessionId,
                false
            );

            if (empty($cats_course)) {
                return false;
            }

            $category = $cats_course[0];
        }

        $currentScore = self::getCurrentScore(
            $userId,
            $category->get_id(),
            $courseCode,
            $sessionId,
            $recalcutateScore
        );

        $minCertificateScore = $category->get_certificate_min_score();

        return !empty($minCertificateScore) && $currentScore >= $minCertificateScore;
    }

    /**
     * Get the current score (as percentage) on a gradebook category for a user
     * @param int $userId The user id
     * @param int $categoryId The gradebook category
     * @param int $courseCode The course code
     * @param int $sessionId Optional. The session id
     * @param bool $recalculate
     *
     * @return float The score
     */
    public static function getCurrentScore($userId, $categoryId, $courseCode, $sessionId = 0, $recalculate = false)
    {
        if ($recalculate) {
            return self::calculateCurrentScore($userId, $categoryId, $courseCode, $sessionId);
        }

        $resultData = Database::select(
            '*',
            Database::get_main_table(TABLE_MAIN_GRADEBOOK_SCORE_LOG),
            [
                'where' => [
                    'category_id = ? AND user_id = ?' => [$categoryId, $userId],
                ],
                'order' => 'registered_at DESC',
                'limit' => '1',
            ],
            'first'
        );

        if (empty($resultData)) {
            return 0;
        }

        return $resultData['score'];
    }

    /**
     * Calculate the current score on a gradebook category for a user
     * @param int $userId The user id
     * @param int $categoryId The gradebook category
     * @param int $courseCode The course code
     * @param int $sessionId Optional. The session id
     * @return float The score
     */
    private static function calculateCurrentScore($userId, $categoryId, $courseCode, $sessionId)
    {
        $cats_course = self::load(
            $categoryId,
            null,
            $courseCode,
            null,
            null,
            $sessionId,
            false
        );

        if (empty($cats_course)) {
            return 0;
        }

        $category = $cats_course[0];
        $courseEvaluations = $category->get_evaluations($userId, true);
        $courseLinks = $category->get_links($userId, true);
        $evaluationsAndLinks = array_merge($courseEvaluations, $courseLinks);
        $categoryScore = 0;
        for ($i = 0; $i < count($evaluationsAndLinks); $i++) {
            $item = $evaluationsAndLinks[$i];
            $score = $item->calc_score($userId);
            $itemValue = 0;

            if (!empty($score)) {
                $divider = $score[1] == 0 ? 1 : $score[1];
                $itemValue = $score[0] / $divider * $item->get_weight();
            }

            $categoryScore += $itemValue;
        }

        return api_float_val($categoryScore);
    }

    /**
     * Register the current score for a user on a category gradebook
     * @param float $score The achieved score
     * @param int $userId The user id
     * @param int $categoryId The gradebook category
     * @return false|string The insert id
     */
    public static function registerCurrentScore($score, $userId, $categoryId)
    {
        return Database::insert(
            Database::get_main_table(TABLE_MAIN_GRADEBOOK_SCORE_LOG),
            [
                'category_id' => intval($categoryId),
                'user_id' => intval($userId),
                'score' => api_float_val($score),
                'registered_at' => api_get_utc_datetime(),
            ]
        );
    }

    /**
     * @return array
     */
    public function getStudentList()
    {
        return $this->studentList;
    }

    /**
     * @param array $list
     */
    public function setStudentList($list)
    {
        $this->studentList = $list;
    }
}
