<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use ChamiloSession as Session;

/**
 * Class Evaluation.
 */
class Evaluation implements GradebookItem
{
    public $studentList;
    /** @var GradebookEvaluation */
    public $entity;
    private $id;
    private $name;
    private $description;
    private $user_id;
    private $course_code;
    /** @var Category */
    private $category;
    private $created_at;
    private $weight;
    private $eval_max;
    private $visible;
    private $sessionId;

    /**
     * Construct.
     */
    public function __construct()
    {
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

    /**
     * @return int
     */
    public function get_id()
    {
        return (int) $this->id;
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

    public function get_user_id()
    {
        return $this->user_id;
    }

    public function get_course_code()
    {
        return $this->course_code;
    }

    /**
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param int $sessionId
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = (int) $sessionId;
    }

    public function set_session_id($sessionId)
    {
        $this->setSessionId($sessionId);
    }

    public function get_date()
    {
        return $this->created_at;
    }

    public function get_weight()
    {
        return $this->weight;
    }

    public function get_max()
    {
        return $this->eval_max;
    }

    public function get_type()
    {
        return $this->type;
    }

    public function is_visible()
    {
        return $this->visible;
    }

    public function get_locked()
    {
        return $this->locked;
    }

    public function is_locked()
    {
        return isset($this->locked) && 1 == $this->locked ? true : false;
    }

    public function set_id($id)
    {
        $this->id = (int) $id;
    }

    public function set_name($name)
    {
        $this->name = $name;
    }

    public function set_description($description)
    {
        $this->description = $description;
    }

    public function set_user_id($user_id)
    {
        $this->user_id = $user_id;
    }

    public function set_course_code($course_code)
    {
        $this->course_code = $course_code;
    }

    public function set_date($date)
    {
        $this->created_at = $date;
    }

    public function set_weight($weight)
    {
        $this->weight = $weight;
    }

    public function set_max($max)
    {
        $this->eval_max = $max;
    }

    public function set_visible($visible)
    {
        $this->visible = $visible;
    }

    public function set_type($type)
    {
        $this->type = $type;
    }

    public function set_locked($locked)
    {
        $this->locked = $locked;
    }

    /**
     * Retrieve evaluations and return them as an array of Evaluation objects.
     *
     * @param int    $id          evaluation id
     * @param int    $user_id     user id (evaluation owner)
     * @param string $course_code course code
     * @param int    $category_id parent category
     * @param int    $visible     visible
     *
     * @return array
     */
    public static function load(
        $id = null,
        $user_id = null,
        $course_code = null,
        $category_id = null,
        $visible = null,
        $locked = null
    ) {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
        $sql = 'SELECT * FROM '.$table;
        $paramcount = 0;

        if (isset($id)) {
            $sql .= ' WHERE id = '.intval($id);
            $paramcount++;
        }

        if (isset($user_id)) {
            if (0 != $paramcount) {
                $sql .= ' AND';
            } else {
                $sql .= ' WHERE';
            }
            $sql .= ' user_id = '.intval($user_id);
            $paramcount++;
        }

        if (isset($course_code) && $course_code != '-1') {
            if ($paramcount != 0) {
                $sql .= ' AND';
            } else {
                $sql .= ' WHERE';
            }
            $sql .= " course_code = '".Database::escape_string($course_code)."'";
            $paramcount++;
        }

        if (isset($category_id)) {
            if ($paramcount != 0) {
                $sql .= ' AND';
            } else {
                $sql .= ' WHERE';
            }
            $sql .= ' category_id = '.intval($category_id);
            $paramcount++;
        }

        if (isset($visible)) {
            if ($paramcount != 0) {
                $sql .= ' AND';
            } else {
                $sql .= ' WHERE';
            }
            $sql .= ' visible = '.intval($visible);
            $paramcount++;
        }

        if (isset($locked)) {
            if ($paramcount != 0) {
                $sql .= ' AND';
            } else {
                $sql .= ' WHERE';
            }
            $sql .= ' locked = '.intval($locked);
        }

        $result = Database::query($sql);
        $allEval = self::create_evaluation_objects_from_sql_result($result);

        return $allEval;
    }

    /**
     * Insert this evaluation into the database.
     */
    public function add()
    {
        if (isset($this->name) &&
            isset($this->user_id) &&
            isset($this->weight) &&
            isset($this->eval_max) &&
            isset($this->visible)
        ) {
            $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);

            $sql = 'INSERT INTO '.$table
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
                .','.api_float_val($this->get_weight())
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
        } else {
            return false;
            //die('Error in Evaluation add: required field empty');
        }
    }

    /**
     * @param int $id
     */
    public function addEvaluationLog($id)
    {
        if (!empty($id)) {
            $tbl_grade_evaluations = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
            $tbl_grade_linkeval_log = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINKEVAL_LOG);
            $eval = new Evaluation();
            $dateobject = $eval->load($id, null, null, null, null);
            $arreval = get_object_vars($dateobject[0]);
            if (!empty($arreval['id'])) {
                $sql = 'SELECT weight from '.$tbl_grade_evaluations.'
                        WHERE id='.$arreval['id'];
                $rs = Database::query($sql);
                $row_old_weight = Database::fetch_array($rs, 'ASSOC');
                $current_date = api_get_utc_datetime();
                $params = [
                    'id_linkeval_log' => $arreval['id'],
                    'name' => $arreval['name'],
                    'description' => $arreval['description'],
                    'created_at' => $current_date,
                    'weight' => $row_old_weight['weight'],
                    'visible' => $arreval['visible'],
                    'type' => 'evaluation',
                    'user_id_log' => api_get_user_id(),
                ];
                Database::insert($tbl_grade_linkeval_log, $params);
            }
        }
    }

    /**
     * Update the properties of this evaluation in the database.
     */
    public function save()
    {
        $tbl_grade_evaluations = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
        $sql = 'UPDATE '.$tbl_grade_evaluations
            ." SET name = '".Database::escape_string($this->get_name())."'"
            .', description = ';
        if (isset($this->description)) {
            $sql .= "'".Database::escape_string($this->get_description())."'";
        } else {
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
            .', max = '.intval($this->get_max())
            .', visible = '.intval($this->is_visible())
            .' WHERE id = '.intval($this->id);
        //recorded history

        $eval_log = new Evaluation();
        $eval_log->addEvaluationLog($this->id);
        Database::query($sql);
    }

    /**
     * Delete this evaluation from the database.
     */
    public function delete()
    {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
        $sql = 'DELETE FROM '.$table.'
                WHERE id = '.$this->get_id();
        Database::query($sql);
    }

    /**
     * Check if an evaluation name (with the same parent category) already exists.
     *
     * @param string $name to check (if not given, the name property of this object will be checked)
     * @param $parent parent category
     *
     * @return bool
     */
    public function does_name_exist($name, $parent)
    {
        if (!isset($name)) {
            $name = $this->name;
            $parent = $this->category;
        }
        $tbl_grade_evaluations = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
        $sql = "SELECT count(id) AS number
                FROM $tbl_grade_evaluations
                WHERE name = '".Database::escape_string($name)."'";

        if (api_is_allowed_to_edit()) {
            $parent = Category::load($parent);
            $code = $parent[0]->get_course_code();
            $courseInfo = api_get_course_info($code);
            $courseId = $courseInfo['real_id'];

            if (isset($code) && $code != '0') {
                $main_course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
                $sql .= ' AND user_id IN (
                     SELECT user_id FROM '.$main_course_user_table.'
                     WHERE
                        c_id = '.$courseId.' AND
                        status = '.COURSEMANAGER.'
                    )';
            } else {
                $sql .= ' AND user_id = '.api_get_user_id();
            }
        } else {
            $sql .= ' AND user_id = '.api_get_user_id();
        }

        if (!isset($parent)) {
            $sql .= ' AND category_id is null';
        } else {
            $sql .= ' AND category_id = '.intval($parent);
        }
        $result = Database::query($sql);
        $number = Database::fetch_row($result);

        return $number[0] != 0;
    }

    /**
     * Are there any results for this evaluation yet ?
     * The 'max' property should not be changed then.
     *
     * @return bool
     */
    public function has_results()
    {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
        $sql = 'SELECT count(id) AS number
                FROM '.$table.'
                WHERE evaluation_id = '.intval($this->get_id());
        $result = Database::query($sql);
        $number = Database::fetch_row($result);

        return 0 != $number[0];
    }

    /**
     * Delete all results for this evaluation.
     */
    public function delete_results()
    {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
        $sql = 'DELETE FROM '.$table.'
                WHERE evaluation_id = '.$this->get_id();
        Database::query($sql);
    }

    /**
     * Delete this evaluation and all underlying results.
     */
    public function delete_with_results()
    {
        $this->delete_results();
        $this->delete();
    }

    /**
     * Check if the given score is possible for this evaluation.
     */
    public function is_valid_score($score)
    {
        return is_numeric($score) && $score >= 0 && $score <= $this->eval_max;
    }

    /**
     * Calculate the score of this evaluation.
     *
     * @param int    $stud_id (default: all students who have results for this eval - then the average is returned)
     * @param string $type    (best, average, ranking)
     *
     * @return array (score, max) if student is given
     *               array (sum of scores, number of scores) otherwise
     *               or null if no scores available
     */
    public function calc_score($stud_id = null, $type = null)
    {
        $allowStats = api_get_configuration_value('allow_gradebook_stats');

        if ($allowStats) {
            $evaluation = $this->entity;
            if (!empty($evaluation)) {
                $weight = $evaluation->getMax();
                switch ($type) {
                    case 'best':
                        $bestResult = $evaluation->getBestScore();
                        $result = [$bestResult, $weight];

                        return $result;
                        break;
                    case 'average':
                        $count = count($evaluation->getUserScoreList());
                        if (empty($count)) {
                            $result = [0, $weight];

                            return $result;
                        }

                        $sumResult = array_sum($evaluation->getUserScoreList());
                        $result = [$sumResult / $count, $weight];

                        return $result;
                        break;
                    case 'ranking':
                        $ranking = AbstractLink::getCurrentUserRanking($stud_id, $evaluation->getUserScoreList());

                        return $ranking;
                        break;
                    default:
                        $weight = $evaluation->getMax();
                        if (!empty($stud_id)) {
                            $scoreList = $evaluation->getUserScoreList();
                            $result = [0, $weight];
                            if (isset($scoreList[$stud_id])) {
                                $result = [$scoreList[$stud_id], $weight];
                            }

                            return $result;
                        } else {
                            $studentCount = count($evaluation->getUserScoreList());
                            $sumResult = array_sum($evaluation->getUserScoreList());
                            $result = [$sumResult, $studentCount];
                        }

                        return $result;
                        break;
                }
            }
        }

        $useSession = true;
        if (isset($stud_id) && empty($type)) {
            $key = 'result_score_student_list_'.api_get_course_int_id().'_'.api_get_session_id().'_'.$this->id.'_'.$stud_id;
            $data = Session::read('calc_score');
            $results = isset($data[$key]) ? $data[$key] : null;

            if (false == $useSession) {
                $results = null;
            }
            $results = null;
            if (empty($results)) {
                $results = Result::load(null, $stud_id, $this->id);
                Session::write('calc_score', [$key => $results]);
            }

            $score = null;
            if (!empty($results)) {
                /** @var Result $res */
                foreach ($results as $res) {
                    $score = $res->get_score();
                }
            }

            return [$score, $this->get_max()];
        } else {
            $count = 0;
            $sum = 0;
            $bestResult = 0;
            $weight = 0;
            $sumResult = 0;

            $key = 'result_score_student_list_'.api_get_course_int_id().'_'.api_get_session_id().'_'.$this->id;
            $data = Session::read('calc_score');
            $allResults = isset($data[$key]) ? $data[$key] : null;
            if (false == $useSession) {
                $allResults = null;
            }

            if (empty($allResults)) {
                $allResults = Result::load(null, null, $this->id);
                Session::write($key, $allResults);
            }

            $students = [];
            /** @var Result $res */
            foreach ($allResults as $res) {
                $score = $res->get_score();
                if (!empty($score) || '0' == $score) {
                    $count++;
                    $sum += $score / $this->get_max();
                    $sumResult += $score;
                    if ($score > $bestResult) {
                        $bestResult = $score;
                    }
                    $weight = $this->get_max();
                }
                $students[$res->get_user_id()] = $score;
            }

            if (empty($count)) {
                return [null, null];
            }

            switch ($type) {
                case 'best':
                    return [$bestResult, $weight];
                    break;
                case 'average':
                    return [$sumResult / $count, $weight];
                    break;
                case 'ranking':
                    $students = [];
                    /** @var Result $res */
                    foreach ($allResults as $res) {
                        $score = $res->get_score();
                        $students[$res->get_user_id()] = $score;
                    }

                    return AbstractLink::getCurrentUserRanking($stud_id, $students);
                    break;
                default:
                    return [$sum, $count];
                    break;
            }
        }
    }

    /**
     * Generate an array of possible categories where this evaluation can be moved to.
     * Notice: its own parent will be included in the list: it's up to the frontend
     * to disable this element.
     *
     * @return array 2-dimensional array - every element contains 3 subelements (id, name, level)
     */
    public function get_target_categories()
    {
        // - course independent evaluation
        //   -> movable to root or other course independent categories
        // - evaluation inside a course
        //   -> movable to root, independent categories or categories inside the course
        $user = api_is_platform_admin() ? null : api_get_user_id();
        $targets = [];
        $level = 0;
        $root = [0, get_lang('RootCat'), $level];
        $targets[] = $root;

        if (isset($this->course_code) && !empty($this->course_code)) {
            $crscats = Category::load(null, null, $this->course_code, 0);
            foreach ($crscats as $cat) {
                $targets[] = [$cat->get_id(), $cat->get_name(), $level + 1];
                $targets = $this->addTargetSubcategories(
                    $targets,
                    $level + 1,
                    $cat->get_id()
                );
            }
        }

        $indcats = Category::load(null, $user, 0, 0);
        foreach ($indcats as $cat) {
            $targets[] = [$cat->get_id(), $cat->get_name(), $level + 1];
            $targets = $this->addTargetSubcategories(
                $targets,
                $level + 1,
                $cat->get_id()
            );
        }

        return $targets;
    }

    /**
     * Move this evaluation to the given category.
     * If this evaluation moves from inside a course to outside,
     * its course code is also changed.
     */
    public function move_to_cat($cat)
    {
        $this->set_category_id($cat->get_id());
        if ($this->get_course_code() != $cat->get_course_code()) {
            $this->set_course_code($cat->get_course_code());
        }
        $this->save();
    }

    /**
     * Retrieve evaluations where a student has results for
     * and return them as an array of Evaluation objects.
     *
     * @param int $cat_id  parent category (use 'null' to retrieve them in all categories)
     * @param int $stud_id student id
     *
     * @return array
     */
    public static function get_evaluations_with_result_for_student($cat_id, $stud_id)
    {
        $tbl_grade_evaluations = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
        $tbl_grade_results = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);

        $sql = 'SELECT * FROM '.$tbl_grade_evaluations.'
                WHERE id IN (
                    SELECT evaluation_id FROM '.$tbl_grade_results.'
                    WHERE user_id = '.intval($stud_id).' AND score IS NOT NULL
                )';
        if (!api_is_allowed_to_edit()) {
            $sql .= ' AND visible = 1';
        }
        if (isset($cat_id)) {
            $sql .= ' AND category_id = '.intval($cat_id);
        } else {
            $sql .= ' AND category_id >= 0';
        }

        $result = Database::query($sql);
        $alleval = self::create_evaluation_objects_from_sql_result($result);

        return $alleval;
    }

    /**
     * Get a list of students that do not have a result record for this evaluation.
     *
     * @param string $first_letter_user
     *
     * @return array
     */
    public function get_not_subscribed_students($first_letter_user = '')
    {
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);

        $sql = "SELECT user_id,lastname,firstname,username
                FROM $tbl_user
                WHERE
                    lastname LIKE '".Database::escape_string($first_letter_user)."%' AND
                    status = ".STUDENT." AND user_id NOT IN (
                        SELECT user_id FROM $table
                        WHERE evaluation_id = ".$this->get_id()."
                    )
                ORDER BY lastname";

        $result = Database::query($sql);
        $users = Database::store_result($result);

        return $users;
    }

    /**
     * Find evaluations by name.
     *
     * @param string $name_mask search string
     *
     * @return array evaluation objects matching the search criterium
     *
     * @todo can be written more efficiently using a new (but very complex) sql query
     */
    public function findEvaluations($name_mask, $selectcat)
    {
        $rootcat = Category::load($selectcat);
        $evals = $rootcat[0]->get_evaluations(
            (api_is_allowed_to_create_course() ? null : api_get_user_id()),
            true
        );
        $foundevals = [];
        foreach ($evals as $eval) {
            if (!(api_strpos(api_strtolower($eval->get_name()), api_strtolower($name_mask)) === false)) {
                $foundevals[] = $eval;
            }
        }

        return $foundevals;
    }

    public function get_item_type()
    {
        return 'E';
    }

    public function get_icon_name()
    {
        return $this->has_results() ? 'evalnotempty' : 'evalempty';
    }

    /**
     * Locks an evaluation, only one who can unlock it is the platform administrator.
     *
     * @param int locked 1 or unlocked 0
     */
    public function lock($locked)
    {
        $table_evaluation = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
        $sql = "UPDATE $table_evaluation
                SET locked = '".intval($locked)."'
                WHERE id='".$this->get_id()."'";
        Database::query($sql);
    }

    public function check_lock_permissions()
    {
        if (api_is_platform_admin()) {
            return true;
        } else {
            if ($this->is_locked()) {
                api_not_allowed();
            }
        }
    }

    public function delete_linked_data()
    {
    }

    /**
     * @return mixed
     */
    public function getStudentList()
    {
        return $this->studentList;
    }

    /**
     * @param $list
     */
    public function setStudentList($list)
    {
        $this->studentList = $list;
    }

    /**
     * @param int $evaluationId
     */
    public static function generateStats($evaluationId)
    {
        $allowStats = api_get_configuration_value('allow_gradebook_stats');
        if ($allowStats) {
            $evaluation = self::load($evaluationId);

            $results = Result::load(null, null, $evaluationId);
            $sumResult = 0;
            $bestResult = 0;
            $average = 0;
            $scoreList = [];

            if (!empty($results)) {
                /** @var Result $result */
                foreach ($results as $result) {
                    $score = $result->get_score();
                    $scoreList[$result->get_user_id()] = $score;
                    $sumResult += $score;
                    if ($score > $bestResult) {
                        $bestResult = $score;
                    }
                }
                $average = $sumResult / count($results);
            }

            /** @var Evaluation $evaluation */
            $evaluation = $evaluation[0];
            $evaluation = $evaluation->entity;
            $evaluation
                ->setBestScore($bestResult)
                ->setAverageScore($average)
                ->setUserScoreList($scoreList)
            ;

            $em = Database::getManager();
            $em->persist($evaluation);
            $em->flush();
        }
    }

    /**
     * Gets the skills related to this item from the skill_rel_item table.
     */
    public function getSkillsFromItem(): string
    {
        return Skill::getSkillRelItemsToString(ITEM_TYPE_GRADEBOOK_EVALUATION, $this->get_id());
    }

    /**
     * @param array $result
     *
     * @return array
     */
    private static function create_evaluation_objects_from_sql_result($result)
    {
        $alleval = [];
        $allow = api_get_configuration_value('allow_gradebook_stats');
        if ($allow) {
            $em = Database::getManager();
            $repo = $em->getRepository('ChamiloCoreBundle:GradebookEvaluation');
        }

        if (Database::num_rows($result)) {
            while ($data = Database::fetch_array($result)) {
                $eval = new Evaluation();
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
                $eval->setSessionId(api_get_session_id());

                if ($allow) {
                    $eval->entity = $repo->find($data['id']);
                }

                $alleval[] = $eval;
            }
        }

        return $alleval;
    }

    /**
     * Internal function used by get_target_categories().
     *
     * @param array $targets
     * @param int   $level
     * @param int   $categoryId
     *
     * @return array
     */
    private function addTargetSubcategories($targets, $level, $categoryId)
    {
        $subcats = Category::load(null, null, null, $categoryId);
        foreach ($subcats as $cat) {
            $targets[] = [$cat->get_id(), $cat->get_name(), $level + 1];
            $targets = $this->addTargetSubcategories(
                $targets,
                $level + 1,
                $cat->get_id()
            );
        }

        return $targets;
    }
}
