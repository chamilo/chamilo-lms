<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;
use ChamiloSession as Session;

/**
 * Class Category
 * Defines a gradebook Category object.
 */
class Category implements GradebookItem
{
    public $studentList;
    public $evaluations;
    public $links;
    public $subCategories;
    /** @var GradebookCategory */
    public $entity;
    private int $id;
    private $name;
    private $description;
    private $user_id;
    private $course_code;
    private $courseId;
    private $parent;
    private $weight;
    private $visible;
    private $certificate_min_score;
    private $session_id;
    private $skills = [];
    private $grade_model_id;
    private $generateCertificates;
    private $isRequirement;
    private $courseDependency;
    private $minimumToValidate;
    private $documentId;
    /** @var int */
    private $gradeBooksToValidateInDependence;

    /**
     * Consctructor.
     */
    public function __construct()
    {
        $this->id = 0;
        $this->name = null;
        $this->description = '';
        $this->user_id = 0;
        $this->courseId = 0;
        $this->parent = 0;
        $this->weight = 0;
        $this->visible = false;
        $this->certificate_min_score = 0;
        $this->session_id = 0;
        $this->grade_model_id = 0;
        $this->generateCertificates = false;
        $this->isRequirement = false;
        $this->courseDependency = [];
        $this->documentId = 0;
        $this->minimumToValidate = null;
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
     * @return int|null
     */
    public function getCertificateMinScore()
    {
        if (!empty($this->certificate_min_score)) {
            return $this->certificate_min_score;
        }

        return null;
    }

    /**
     * @return string
     */
    public function get_course_code()
    {
        return $this->course_code;
    }

    /**
     * @return int
     */
    public function get_parent_id()
    {
        return $this->parent;
    }

    /**
     * @return int
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
        return isset($this->locked) && 1 == $this->locked ? true : false;
    }

    /**
     * @return bool
     */
    public function is_visible()
    {
        return $this->visible;
    }

    /**
     * Get $isRequirement.
     *
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
        $this->parent = (int) $parent;
    }

    /**
     * Filters to int and sets the session ID.
     *
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
     * Set $isRequirement.
     *
     * @param int $isRequirement
     */
    public function setIsRequirement($isRequirement)
    {
        $this->isRequirement = $isRequirement;
    }

    /**
     * @param $value
     */
    public function setCourseListDependency($value)
    {
        $this->courseDependency = [];

        $unserialized = UnserializeApi::unserialize('not_allowed_classes', $value, true);

        if (false !== $unserialized) {
            $this->courseDependency = $unserialized;
        }
    }

    /**
     * Course id list.
     *
     * @return array
     */
    public function getCourseListDependency()
    {
        return $this->courseDependency;
    }

    /**
     * @param int $value
     */
    public function setMinimumToValidate($value)
    {
        $this->minimumToValidate = $value;
    }

    public function getMinimumToValidate()
    {
        return $this->minimumToValidate;
    }

    /**
     * @return int|null
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
     *
     * @return array|resource
     */
    public function get_skills($from_db = true)
    {
        if ($from_db) {
            $categoryId = $this->get_id();
            $gradebook = new Gradebook();
            $skills = $gradebook->getSkillsByGradebook($categoryId);
        } else {
            $skills = $this->skills;
        }

        return $skills;
    }

    /**
     * @return array
     */
    public function getSkillsForSelect()
    {
        $skills = $this->get_skills();
        $skill_select = [];
        if (!empty($skills)) {
            foreach ($skills as $skill) {
                $skill_select[$skill['id']] = $skill['name'];
            }
        }

        return $skill_select;
    }

    /**
     * Set the generate_certificates value.
     *
     * @param int $generateCertificates
     */
    public function setGenerateCertificates($generateCertificates)
    {
        $this->generateCertificates = $generateCertificates;
    }

    /**
     * Get the generate_certificates value.
     *
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
    public static function loadSessionCategories(
        $id = null,
        $session_id = null
    ) {
        if (isset($id) && 0 === (int) $id) {
            $cats = [];
            $cats[] = self::create_root_category();

            return $cats;
        }
        $courseId = api_get_course_int_id();
        $session_id = (int) $session_id;

        if (!empty($session_id)) {
            $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
            $sql = 'SELECT id, c_id
                    FROM '.$table.'
                    WHERE session_id = '.$session_id;
            $result_session = Database::query($sql);
            if (Database::num_rows($result_session) > 0) {
                $categoryList = [];
                while ($data_session = Database::fetch_array($result_session)) {
                    $parent_id = $data_session['id'];
                    if ($data_session['c_id'] == $courseId) {
                        $categories = self::load($parent_id);
                        $categoryList = array_merge($categoryList, $categories);
                    }
                }

                return $categoryList;
            }
        }
    }

    /**
     * Retrieve categories and return them as an array of Category objects.
     *
     * @param ?int  $id category id
     * @param ?int  $user_id (category owner)
     * @param ?int  $courseId course id (int)
     * @param ?int  $parent_id parent category
     * @param ?int  $visible 0 or 1
     * @param ?int  $session_id (in case we are in a session)
     * @param ?bool $order_by Whether to show all "session"
     *                            categories (true) or hide them (false) in case there is no session id
     *
     * @return array<static>
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function load(
        ?int $id = null,
        ?int $user_id = null,
        ?int $courseId = 0,
        ?int $parent_id = null,
        ?int $visible = null,
        ?int $session_id = null,
        ?string $order_by = null
    ): array {
        //if the category given is explicitly 0 (not null), then create
        // a root category object (in memory)
        if (isset($id) && 0 === $id) {
            $cats = [];
            $cats[] = self::create_root_category();

            return $cats;
        }

        $bond = ' WHERE';

        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $sql = 'SELECT * FROM '.$table;
        if (!empty($id)) {
            $sql .= ' WHERE id = '.$id;
            $bond = ' AND';
        }

        if (isset($user_id)) {
            $sql .= $bond.' user_id = '.$user_id;
            $bond = ' AND';
        }



        if (!empty($courseId)) {
            $sql .= $bond." c_id = $courseId";
            $bond = ' AND';
        }

        if (!isset($session_id)) {
            $session_id = api_get_session_id();
        }

        if (!empty($session_id)) {
            $sql .= $bond.' session_id = '.$session_id;
        } else {
            if (empty($id)) {
                $sql .= $bond.' (session_id IS NULL OR session_id = 0) ';
            }
        }
        $bond = ' AND';

        if (!empty($parent_id)) {
            $sql .= $bond.' parent_id = '.$parent_id;
        }

        if (isset($visible)) {
            $sql .= $bond.' visible = '.$visible;
        }

        if (isset($order_by)) {
            $sql .= ' '.Database::escape_string($order_by);
        }

        $result = Database::query($sql);
        $categories = [];
        if (Database::num_rows($result) > 0) {
            $categories = self::create_category_objects_from_sql_result($result);
        }

        return $categories;
    }

    /**
     * Insert this category into the database.
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    public function add()
    {
        if (isset($this->name) && '-1' == $this->name) {
            return false;
        }

        if (isset($this->name) && isset($this->user_id)) {
            $em = Database::getManager();

            $course = api_get_course_entity($this->courseId);
            $parent = null;
            if (!empty($this->parent)) {
                $parent = $em->getRepository(GradebookCategory::class)->find($this->parent);
            }

            $category = new GradebookCategory();
            $category->setTitle($this->name);
            $category->setDescription($this->description);
            $userId = is_numeric($this->user_id) ? (int) $this->user_id : api_get_user_id();
            $category->setUser(api_get_user_entity($userId));
            $category->setUser(api_get_user_entity($this->user_id));
            $category->setCourse($course);
            $category->setParent($parent);
            $category->setWeight(api_float_val($this->weight));
            $category->setVisible($this->visible ? true : false);
            $category->setCertifMinScore($this->certificate_min_score);
            $category->setSession(api_get_session_entity($this->session_id));
            $category->setGenerateCertificates($this->generateCertificates);
            if (!empty($this->grade_model_id)) {
                $model = $em->getRepository(\Chamilo\CoreBundle\Entity\GradeModel::class)->find($this->grade_model_id);
                $category->setGradeModel($model);
            }

            $category->setIsRequirement($this->isRequirement);
            $category->setLocked(0);

            $em->persist($category);
            $em->flush();

            $id = $category->getId();
            $this->set_id($id);

            if (!empty($id)) {
                $parent_id = $this->get_parent_id();
                $grade_model_id = $this->get_grade_model_id();
                if (0 == $parent_id) {
                    //do something
                    if (isset($grade_model_id) &&
                        !empty($grade_model_id) &&
                        '-1' != $grade_model_id
                    ) {
                        $obj = new GradeModel();
                        $components = $obj->get_components($grade_model_id);
                        $default_weight_setting = api_get_setting('gradebook_default_weight');
                        $default_weight = 100;
                        if (isset($default_weight_setting)) {
                            $default_weight = $default_weight_setting;
                        }
                        foreach ($components as $component) {
                            $gradebook = new Gradebook();
                            $params = [];

                            $params['name'] = $component['acronym'];
                            $params['description'] = $component['title'];
                            $params['user_id'] = api_get_user_id();
                            $params['parent_id'] = $id;
                            $params['weight'] = $component['percentage'] / 100 * $default_weight;
                            $params['session_id'] = api_get_session_id();
                            $params['c_id'] = $this->getCourseId();

                            $gradebook->save($params);
                        }
                    }
                }
            }

            $gradebook = new Gradebook();
            $gradebook->updateSkillsToGradeBook(
                $this->id,
                $this->get_skills(false)
            );

            return $id;
        }
    }

    /**
     * Update the properties of this category in the database.
     *
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Doctrine\ORM\Exception\ORMException
     * @todo fix me
     */
    public function save()
    {
        $em = Database::getManager();
        $repo = $em->getRepository(GradebookCategory::class);

        /** @var GradebookCategory $category */
        $category = $repo->find($this->id);

        if (null === $category) {
            return false;
        }

        $parent = null;
        if (!empty($this->parent)) {
            $parent = $repo->find($this->parent);
        }
        $course = api_get_course_entity();

        $category->setTitle($this->name);
        $category->setDescription($this->description);
        $userId = is_numeric($this->user_id) ? (int) $this->user_id : api_get_user_id();
        $category->setUser(api_get_user_entity($userId));
        $category->setCourse($course);
        $category->setParent($parent);
        $category->setWeight($this->weight);
        $category->setVisible($this->visible);
        $category->setCertifMinScore($this->certificate_min_score);
        $category->setGenerateCertificates($this->generateCertificates);
        if (!empty($this->grade_model_id)) {
            $model = $em->getRepository(\Chamilo\CoreBundle\Entity\GradeModel::class)->find($this->grade_model_id);
            $category->setGradeModel($model);
        }

        $category->setIsRequirement($this->isRequirement);

        $em->persist($category);
        $em->flush();

        if (!empty($this->id)) {
            $parent_id = $this->get_parent_id();
            $grade_model_id = $this->get_grade_model_id();
            if (0 == $parent_id) {
                if (!empty($grade_model_id) &&
                    '-1' != $grade_model_id
                ) {
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
                        $params = [];
                        $params['name'] = $component['acronym'];
                        $params['description'] = $component['title'];
                        $params['user_id'] = api_get_user_id();
                        $params['parent_id'] = $this->id;
                        $params['weight'] = $component['percentage'] / 100 * $default_weight;
                        $params['session_id'] = api_get_session_id();
                        $params['c_id'] = $this->getCourseId();
                        $gradebook->save($params);
                    }
                }
            }
        }

        $gradebook = new Gradebook();
        $gradebook->updateSkillsToGradeBook(
            $this->id,
            $this->get_skills(false),
            true
        );
    }

    /**
     * Update link weights see #5168.
     *
     * @param int $new_weight
     */
    public function updateChildrenWeight($new_weight)
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
     * Delete this evaluation from the database.
     * @throws Exception
     */
    public function delete(): void
    {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $sql = 'DELETE FROM '.$table.' WHERE id = '.intval($this->id);
        Database::query($sql);
    }

    /**
     * Return an HTML span block if the given resource has been deleted
     * @param ?int $courseId
     *
     * @return string
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function show_message_resource_delete(?int $courseId): string
    {
        if (empty($courseId)) {
            return '';
        }
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $sql = "SELECT count(*) AS num
                FROM $table
                WHERE
                    c_id = $courseId AND
                    visible = 3";
        $res = Database::query($sql);
        $option = Database::fetch_array($res);
        if ($option['num'] >= 1) {
            return '&nbsp;&nbsp;<span class="resource-deleted">
                (&nbsp;'.get_lang('The resource has been deleted').'&nbsp;)
                </span>';
        }

        return '';
    }

    /**
     * Shows all information of a category.
     *
     * @param int $categoryId
     *
     * @return array
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public function showAllCategoryInfo(int $categoryId): array
    {
        if (empty($categoryId)) {
            return [];
        }

        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $sql = "SELECT * FROM $table
                WHERE id = $categoryId";
        $result = Database::query($sql);

        return Database::fetch_array($result);
    }

    /**
     * Checks if the certificate is available for the given user in this category.
     *
     * @param int $user_id User ID
     *
     * @return bool True if conditions match, false if fails
     */
    public function is_certificate_available($user_id)
    {
        $score = $this->calc_score(
            $user_id,
            null,
            $this->courseId,
            $this->session_id
        );

        if (isset($score) && isset($score[0])) {
            // Get a percentage score to compare to minimum certificate score
            // $certification_score = $score[0] / $score[1] * 100;
            // Get real score not a percentage.
            $certification_score = $score[0];
            if ($certification_score >= $this->certificate_min_score) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is this category the main one in a course ?
     * A category is a course if it has a course code and no parent category.
     */
    public function is_course(): bool
    {
        return !empty($this->getCourseId())
            && (!isset($this->parent) || 0 == $this->parent);
    }

    /**
     * Calculate the score of this category.
     */
    public function calc_score(
        ?int $studentId = null,
        ?string $type = null,
        ?int $courseId = 0,
        ?int $session_id = null
    ): ?array {
        $key = 'category:'.$this->id.'student:'.(int) $studentId.'type:'.$type.'course:'.$courseId.'session:'.(int) $session_id;
        $useCache = ('true' === api_get_setting('gradebook.gradebook_use_apcu_cache'));
        $cacheAvailable = api_get_configuration_value('apc') && $useCache;

        if ($cacheAvailable) {
            $cache = new \Symfony\Component\Cache\Adapter\ApcuAdapter();
            if ($cache->hasItem($key)) {
                return $cache->getItem($key)->get();
            }
        }
        // Classic
        if (!empty($studentId) && '' == $type) {
            if (!empty($courseId)) {
                $cats = $this->get_subcategories(
                    $studentId,
                    $courseId,
                    $session_id
                );
                $evals = $this->get_evaluations($studentId, false, $courseId);
                $links = $this->get_links($studentId, false, $courseId);
            } else {
                $cats = $this->get_subcategories($studentId);
                $evals = $this->get_evaluations($studentId);
                $links = $this->get_links($studentId);
            }

            // Calculate score
            $count = 0;
            $ressum = 0;
            $weightsum = 0;
            if (!empty($cats)) {
                /** @var Category $cat */
                foreach ($cats as $cat) {
                    $cat->set_session_id($session_id);
                    $cat->setCourseId($courseId);
                    $cat->setStudentList($this->getStudentList());
                    $score = $cat->calc_score(
                        $studentId,
                        null,
                        $courseId,
                        $session_id
                    );

                    $catweight = 0;
                    if (0 != $cat->get_weight()) {
                        $catweight = $cat->get_weight();
                        $weightsum += $catweight;
                    }

                    if (isset($score) && !empty($score[1]) && !empty($catweight)) {
                        $ressum += $score[0] / $score[1] * $catweight;
                    }
                }
            }

            if (!empty($evals)) {
                /** @var Evaluation $eval */
                foreach ($evals as $eval) {
                    $eval->setStudentList($this->getStudentList());
                    $evalres = $eval->calc_score($studentId);
                    if (isset($evalres) && 0 != $eval->get_weight()) {
                        $evalweight = $eval->get_weight();
                        $weightsum += $evalweight;
                        if (!empty($evalres[1])) {
                            $ressum += $evalres[0] / $evalres[1] * $evalweight;
                        }
                    } else {
                        if (0 != $eval->get_weight()) {
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

                    $linkres = $link->calc_score($studentId, null);
                    if (!empty($linkres) && 0 != $link->get_weight()) {
                        $linkweight = $link->get_weight();
                        $link_res_denom = 0 == $linkres[1] ? 1 : $linkres[1];
                        $weightsum += $linkweight;
                        $ressum += $linkres[0] / $link_res_denom * $linkweight;
                    } else {
                        // Adding if result does not exists
                        if (0 != $link->get_weight()) {
                            $linkweight = $link->get_weight();
                            $weightsum += $linkweight;
                        }
                    }
                }
            }
        } else {
            if (!empty($courseId)) {
                $cats = $this->get_subcategories(
                    null,
                    $courseId,
                    $session_id
                );
                $evals = $this->get_evaluations(null, false, $courseId);
                $links = $this->get_links(null, false, $courseId);
            } else {
                $cats = $this->get_subcategories(null);
                $evals = $this->get_evaluations(null);
                $links = $this->get_links(null);
            }

            // Calculate score
            $ressum = 0;
            $weightsum = 0;
            $bestResult = 0;
            $totalScorePerStudent = [];

            if (!empty($cats)) {
                /** @var Category $cat */
                foreach ($cats as $cat) {
                    $cat->setStudentList($this->getStudentList());
                    $score = $cat->calc_score(
                        null,
                        $type,
                        $courseId,
                        $session_id
                    );

                    $catweight = 0;
                    if (0 != $cat->get_weight()) {
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
                if ('best' === $type) {
                    $studentList = $this->getStudentList();
                    foreach ($studentList as $student) {
                        $studentId = $student['user_id'];
                        foreach ($evals as $eval) {
                            $linkres = $eval->calc_score($studentId, null);
                            $linkweight = $eval->get_weight();
                            $link_res_denom = 0 == $linkres[1] ? 1 : $linkres[1];
                            $ressum = $linkres[0] / $link_res_denom * $linkweight;

                            if (!isset($totalScorePerStudent[$studentId])) {
                                $totalScorePerStudent[$studentId] = 0;
                            }
                            $totalScorePerStudent[$studentId] += $ressum;
                        }
                    }
                } else {
                    /** @var Evaluation $eval */
                    foreach ($evals as $eval) {
                        $evalres = $eval->calc_score(null, $type);
                        $eval->setStudentList($this->getStudentList());

                        if (isset($evalres) && 0 != $eval->get_weight()) {
                            $evalweight = $eval->get_weight();
                            $weightsum += $evalweight;
                            if (!empty($evalres[1])) {
                                $ressum += $evalres[0] / $evalres[1] * $evalweight;
                            }

                            if ($ressum > $bestResult) {
                                $bestResult = $ressum;
                            }
                        } else {
                            if (0 != $eval->get_weight()) {
                                $evalweight = $eval->get_weight();
                                $weightsum += $evalweight;
                            }
                        }
                    }
                }
            }

            if (!empty($links)) {
                $studentList = $this->getStudentList();
                if ('best' === $type) {
                    foreach ($studentList as $student) {
                        $studentId = $student['user_id'];
                        foreach ($links as $link) {
                            $linkres = $link->calc_score($studentId, null);
                            $linkweight = $link->get_weight();
                            if ($linkres) {
                                $link_res_denom = 0 == $linkres[1] ? 1 : $linkres[1];
                                $ressum = $linkres[0] / $link_res_denom * $linkweight;
                            }

                            if (!isset($totalScorePerStudent[$studentId])) {
                                $totalScorePerStudent[$studentId] = 0;
                            }
                            $totalScorePerStudent[$studentId] += $ressum;
                        }
                    }
                } else {
                    /** @var EvalLink|ExerciseLink $link */
                    foreach ($links as $link) {
                        $link->setStudentList($this->getStudentList());

                        if ($session_id) {
                            $link->set_session_id($session_id);
                        }

                        $linkres = $link->calc_score($studentId, $type);

                        if (!empty($linkres) && 0 != $link->get_weight()) {
                            $linkweight = $link->get_weight();
                            $link_res_denom = 0 == $linkres[1] ? 1 : $linkres[1];

                            $weightsum += $linkweight;
                            $ressum += $linkres[0] / $link_res_denom * $linkweight;
                            if ($ressum > $bestResult) {
                                $bestResult = $ressum;
                            }
                        } else {
                            // Adding if result does not exist
                            if (0 != $link->get_weight()) {
                                $linkweight = $link->get_weight();
                                $weightsum += $linkweight;
                            }
                        }
                    }
                }
            }
        }

        switch ($type) {
            case 'best':
                arsort($totalScorePerStudent);
                $maxScore = current($totalScorePerStudent);

                return [$maxScore, $this->get_weight()];
                break;
            case 'average':
                if (empty($ressum)) {
                    if ($cacheAvailable) {
                        $cacheItem = $cache->getItem($key);
                        $cacheItem->set(null);

                        $cache->save($cacheItem);
                    }

                    return null;
                }

                if ($cacheAvailable) {
                    $cacheItem = $cache->getItem($key);
                    $cacheItem->set([$ressum, $weightsum]);

                    $cache->save($cacheItem);
                }

                return [$ressum, $weightsum];
                //break;
            case 'ranking':
                // category ranking is calculated in gradebook_data_generator.class.php
                // function get_data
                return null;

                //return AbstractLink::getCurrentUserRanking($studentId, []);
                //break;
            default:
                if ($cacheAvailable) {
                    $cacheItem = $cache->getItem($key);
                    $cacheItem->set([$ressum, $weightsum]);

                    $cache->save($cacheItem);
                }

                return [$ressum, $weightsum];
        }
    }

    /**
     * Delete this category and every subcategory, evaluation and result inside.
     */
    public function delete_all()
    {
        $cats = self::load(null, null, $this->getCourseId(), $this->id, null);
        $evals = Evaluation::load(
            null,
            null,
            $this->getCourseId(),
            $this->id,
            null
        );

        $links = LinkFactory::load(
            null,
            null,
            null,
            null,
            $this->getCourseId(),
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
     * @param int $stud_id
     * @param int $courseId
     * @param int $session_id
     *
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function get_root_categories_for_student(
        int $stud_id,
        int $courseId = 0,
        int $session_id = 0
    ) {
        $main_course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);

        $sql = "SELECT * FROM $table WHERE parent_id = 0";

        if (!api_is_allowed_to_edit()) {
            $sql .= ' AND visible = 1';
            //proceed with checks on optional parameters course & session
            if (!empty($courseId)) {
                // TODO: considering it highly improbable that a user would get here
                // if he doesn't have the rights to view this course and this
                // session, we don't check his registration to these, but this
                // could be an improvement
                if (!empty($session_id)) {
                    $sql .= " AND c_id = '".$courseId."' AND session_id = ".$session_id;
                } else {
                    $sql .= " AND c_id = '".$courseId."' AND session_id is null OR session_id=0";
                }
            } else {
                //no optional parameter, proceed as usual
                $sql .= ' AND c_id in
                     (
                        SELECT c.id
                        FROM '.$main_course_user_table.' cu
                        WHERE cu.user_id = '.intval($stud_id).'
                        AND cu.status = '.STUDENT.'
                    )';
            }
        } elseif (!api_is_platform_admin()) {
            //proceed with checks on optional parameters course & session
            if (!empty($courseId)) {
                // TODO: considering it highly improbable that a user would get here
                // if he doesn't have the rights to view this course and this
                // session, we don't check his registration to these, but this
                // could be an improvement
                $sql .= " AND c_id  = $courseId";
                if (!empty($session_id)) {
                    $sql .= " AND session_id = ".$session_id;
                } else {
                    $sql .= 'AND session_id IS NULL OR session_id = 0';
                }
            } else {
                $sql .= ' AND c_id IN
                     (
                        SELECT c.id
                        FROM '.$main_course_user_table.' cu
                        WHERE
                            cu.user_id = '.api_get_user_id().' AND
                            cu.status = '.COURSEMANAGER.'
                    )';
            }
        } elseif (api_is_platform_admin()) {
            if (0 != $session_id) {
                $sql .= ' AND session_id='.$session_id;
            } else {
                $sql .= ' AND coalesce(session_id,0)=0';
            }
        }
        $result = Database::query($sql);
        $cats = self::create_category_objects_from_sql_result($result);

        // course independent categories
        if (empty($courseId)) {
            $cats = $this->getIndependentCategoriesWithStudentResult(
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
     * @param int    $user_id (to return everything, use 'null' here)
     * @param ?int   $courseId (optional)
     * @param ?int    $session_id (optional)
     *
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function get_root_categories_for_teacher(
        int $user_id,
        ?int $courseId = null,
        ?int $session_id = null
    ) {
        if (null == $user_id) {
            return self::load(null, null, $courseId, 0, null, $session_id);
        }

        $main_course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_grade_categories = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);

        $sql = 'SELECT * FROM '.$tbl_grade_categories.'
                WHERE parent_id = 0 ';
        if (!empty($courseId)) {
            $sql .= " AND c_id = $courseId ";
            if (!empty($session_id)) {
                $sql .= " AND session_id = ".$session_id;
            }
        } else {
            $sql .= ' AND c_id in
                 (
                    SELECT cu.id
                    FROM '.$main_course_user_table.' cu
                    WHERE user_id = '.$user_id.'
                )';
        }
        $result = Database::query($sql);
        $cats = self::create_category_objects_from_sql_result($result);
        // course independent categories
        if (!empty($courseId)) {
            $indcats = self::load(
                null,
                $user_id,
                $courseId,
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
     *
     * @return bool
     */
    public function is_movable()
    {
        return !(!isset($this->id) || 0 == $this->id || $this->is_course());
    }

    /**
     * Generate an array of possible categories where this category can be moved to.
     * Notice: its own parent will be included in the list: it's up to the frontend
     * to disable this element.
     *
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
            $user = api_is_platform_admin() ? null : api_get_user_id();
            $targets = [];
            $level = 0;

            $root = [0, get_lang('Main folder'), $level];
            $targets[] = $root;

            if (!empty($this->courseId)) {
                $crscats = self::load(null, null, $this->courseId, 0);
                foreach ($crscats as $cat) {
                    if ($this->can_be_moved_to_cat($cat)) {
                        $targets[] = [
                            $cat->get_id(),
                            $cat->get_name(),
                            $level + 1,
                        ];
                        $targets = $this->addTargetSubcategories(
                            $targets,
                            $level + 1,
                            $cat->get_id()
                        );
                    }
                }
            }

            $indcats = self::load(null, $user, 0, 0);
            foreach ($indcats as $cat) {
                if ($this->can_be_moved_to_cat($cat)) {
                    $targets[] = [$cat->get_id(), $cat->get_name(), $level + 1];
                    $targets = $this->addTargetSubcategories(
                        $targets,
                        $level + 1,
                        $cat->get_id()
                    );
                }
            }

            return $targets;
        }
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
            $this->setCourseId($cat->getCourseId());
            $this->applyCourseCodeToChildren();
        }
        $this->save();
    }

    /**
     * Generate an array of all categories the user can navigate to.
     */
    public function get_tree()
    {
        $targets = [];
        $level = 0;
        $root = [0, get_lang('Main folder'), $level];
        $targets[] = $root;

        // course or platform admin
        if (api_is_allowed_to_edit()) {
            $user = api_is_platform_admin() ? null : api_get_user_id();
            $cats = self::get_root_categories_for_teacher($user);
            foreach ($cats as $cat) {
                $targets[] = [
                    $cat->get_id(),
                    $cat->get_name(),
                    $level + 1,
                ];
                $targets = $this->add_subtree(
                    $targets,
                    $level + 1,
                    $cat->get_id(),
                    null
                );
            }
        } else {
            // student
            $cats = $this->get_root_categories_for_student(api_get_user_id());
            foreach ($cats as $cat) {
                $targets[] = [
                    $cat->get_id(),
                    $cat->get_name(),
                    $level + 1,
                ];
                $targets = $this->add_subtree(
                    $targets,
                    $level + 1,
                    $cat->get_id(),
                    1
                );
            }
        }

        return $targets;
    }

    /**
     * Generate an array of courses that a teacher hasn't created a category for.
     *
     * @param int $user_id
     *
     * @return array 2-dimensional array - every element contains 2 subelements (code, title)
     */
    public static function get_not_created_course_categories($user_id)
    {
        $tbl_main_courses = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_main_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_grade_categories = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);

        $user_id = (int) $user_id;

        $sql = 'SELECT DISTINCT(cc.code), title
                FROM '.$tbl_main_courses.' cc, '.$tbl_main_course_user.' cu
                WHERE
                    cc.id = cu.c_id AND
                    cu.status = '.COURSEMANAGER;

        if (!api_is_platform_admin()) {
            $sql .= ' AND cu.user_id = '.$user_id;
        }
        $sql .= ' AND cc.id NOT IN
             (
                SELECT c_id FROM '.$tbl_grade_categories.'
                WHERE
                    parent_id = 0 AND
                    c_id IS NOT NULL
                )';
        $result = Database::query($sql);

        $cats = [];
        while ($data = Database::fetch_array($result)) {
            $cats[] = [$data['code'], $data['title']];
        }

        return $cats;
    }

    /**
     * Generate an array of all courses that a teacher is admin of.
     *
     * @param int $user_id
     *
     * @return array 2-dimensional array - every element contains 2 sub-elements (code, title)
     * @throws Exception
     */
    public static function get_all_courses(int $user_id): array
    {
        $tbl_main_courses = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_main_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $sql = 'SELECT DISTINCT(code), title, cc.id
                FROM '.$tbl_main_courses.' cc, '.$tbl_main_course_user.' cu
                WHERE cc.id = cu.c_id AND cu.status = '.COURSEMANAGER;
        if (!api_is_platform_admin()) {
            $sql .= ' AND cu.user_id = '.$user_id;
        }

        $result = Database::query($sql);
        $cats = [];
        while ($data = Database::fetch_array($result)) {
            $cats[] = [$data['code'], $data['title']];
        }

        return $cats;
    }

    /**
     * Apply the same visibility to every subcategory, evaluation and link.
     */
    public function apply_visibility_to_children()
    {
        $cats = self::load(null, null, 0, $this->id, null);
        $evals = Evaluation::load(null, null, null, $this->id, null);
        $links = LinkFactory::load(
            null,
            null,
            null,
            null,
            null,
            $this->id,
            null
        );
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
     * Check if a category contains evaluations with a result for a given student.
     *
     * @param int $studentId
     *
     * @return bool
     */
    public function hasEvaluationsWithStudentResults($studentId)
    {
        $evals = Evaluation::get_evaluations_with_result_for_student(
            $studentId,
            $this->id
        );
        if (0 != count($evals)) {
            return true;
        } else {
            $cats = self::load(
                null,
                null,
                0,
                $this->id,
                api_is_allowed_to_edit() ? null : 1
            );

            /** @var Category $cat */
            foreach ($cats as $cat) {
                if ($cat->hasEvaluationsWithStudentResults($studentId)) {
                    return true;
                }
            }

            return false;
        }
    }

    /**
     * Retrieve all categories inside a course independent category
     * that should be visible to a student.
     *
     * @param int   $categoryId parent category
     * @param int   $studentId
     * @param array $cats       optional: if defined, the categories will be added to this array
     *
     * @return array
     */
    public function getIndependentCategoriesWithStudentResult(
        $categoryId,
        $studentId,
        $cats = []
    ) {
        $creator = api_is_allowed_to_edit() && !api_is_platform_admin() ? api_get_user_id() : null;

        $categories = self::load(
            null,
            $creator,
            0,
            $categoryId,
            api_is_allowed_to_edit() ? null : 1
        );

        if (!empty($categories)) {
            /** @var Category $category */
            foreach ($categories as $category) {
                if ($category->hasEvaluationsWithStudentResults($studentId)) {
                    $cats[] = $category;
                }
            }
        }

        return $cats;
    }

    /**
     * Return the session id (in any case, even if it's null or 0).
     *
     * @return int Session id (can be null)
     */
    public function get_session_id()
    {
        return $this->session_id;
    }

    /**
     * Get appropriate subcategories visible for the user (and optionally the course and session).
     *
     * @param ?int   $studentId student id (default: all students)
     * @param ?int  $courseId Course code (optional)
     * @param ?int  $session_id Session ID (optional)
     * @param ?string $order A sorting string like 'ORDER BY id'
     *
     * @return array Array of subcategories
     * @throws \Doctrine\DBAL\Exception
     */
    public function get_subcategories(
        ?int $studentId = null,
        ?int $courseId = 0,
        ?int $session_id = 0,
        ?string $order = null
    ): array {
        // 1 student
        if (isset($studentId)) {
            // Special case: this is the root
            if (0 == $this->id) {
                return $this->get_root_categories_for_student($studentId, $courseId, $session_id);
            } else {
                return self::load(
                    null,
                    null,
                    $courseId,
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
                if (0 == $this->id) {

                    // inside a course
                    return $this->get_root_categories_for_teacher(
                        api_get_user_id(),
                        $courseId,
                        $session_id
                    );
                } elseif (!empty($this->courseId)) {

                    return self::load(
                        null,
                        null,
                        $this->courseId,
                        $this->id,
                        null,
                        $session_id,
                        $order
                    );
                } elseif (!empty($courseId)) {

                    // course independent
                    return self::load(
                        null,
                        null,
                        $courseId,
                        $this->id,
                        null,
                        $session_id,
                        $order
                    );
                } else {
                    return self::load(
                        null,
                        api_get_user_id(),
                        0,
                        $this->id,
                        null
                    );
                }
            } elseif (api_is_platform_admin()) {
                // platform admin
                // we explicitly avoid listing subcats from another session
                return self::load(
                    null,
                    null,
                    $courseId,
                    $this->id,
                    null,
                    $session_id,
                    $order
                );
            }
        }

        return [];
    }

    /**
     * Get appropriate evaluations visible for the user.
     *
     * @param ?int  $studentId student id (default: all students)
     * @param ?bool $recursive process subcategories (default: no recursion)
     * @param ?int  $courseId
     * @param ?int  $sessionId
     *
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function get_evaluations(
        ?int $studentId = null,
        ?bool $recursive = false,
        ?int $courseId = 0,
        ?int $sessionId = 0
    ): array
    {
        $evals = [];
        $courseId = empty($courseId) ? $this->getCourseId() : $courseId;
        $sessionId = empty($sessionId) ? $this->get_session_id() : $sessionId;

        // 1 student
        if (!empty($studentId)) {
            // Special case: this is the root
            if (0 == $this->id) {
                $evals = Evaluation::get_evaluations_with_result_for_student(
                    $studentId,
                    0
                );
            } else {
                $evals = Evaluation::load(
                    null,
                    null,
                    $courseId,
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
                if (0 == $this->id) {
                    $evals = Evaluation::load(
                        null,
                        api_get_user_id(),
                        null,
                        $this->id,
                        null
                    );
                } elseif (!empty($this->courseId)) {
                    // inside a course
                    $evals = Evaluation::load(
                        null,
                        null,
                        $courseId,
                        $this->id,
                        null
                    );
                } else {
                    // course independent
                    $evals = Evaluation::load(
                        null,
                        api_get_user_id(),
                        null,
                        $this->id,
                        null
                    );
                }
            } else {
                $evals = Evaluation::load(
                    null,
                    null,
                    $courseId,
                    $this->id,
                    null
                );
            }
        }

        if ($recursive) {
            $subcats = $this->get_subcategories(
                $studentId,
                $courseId,
                $sessionId
            );

            if (!empty($subcats)) {
                foreach ($subcats as $subcat) {
                    /* @var Category $subcat */
                    $subevals = $subcat->get_evaluations(
                        $studentId,
                        true,
                        $courseId
                    );
                    $evals = array_merge($evals, $subevals);
                }
            }
        }

        return $evals;
    }

    /**
     * Get appropriate links visible for the user.
     *
     * @param ?int    $studentId   student id (default: all students)
     * @param ?bool   $recursive   process subcategories (default: no recursion)
     * @param ?int $courseId
     * @param ?int    $sessionId
     *
     * @return array
     */
    public function get_links(
        ?int $studentId = null,
        ?bool $recursive = false,
        ?int $courseId = 0,
        ?int $sessionId = 0
    ): array
    {
        $links = [];
        $courseId = empty($courseId) ? $this->getCourseId() : $courseId;
        $sessionId = empty($sessionId) ? $this->get_session_id() : $sessionId;

        // no links in root or course independent categories
        if (0 == $this->id) {
        } elseif (isset($studentId)) {
            // 1 student $studentId
            $links = LinkFactory::load(
                null,
                null,
                null,
                null,
                empty($courseId) ? null : $courseId,
                $this->id,
                api_is_allowed_to_edit() ? null : 1
            );
        } else {
            // All students -> only for course/platform admin
            $links = LinkFactory::load(
                null,
                null,
                null,
                null,
                empty($courseId) ? null : $courseId,
                $this->id,
                null
            );
        }

        if ($recursive) {
            $subcats = $this->get_subcategories(
                $studentId,
                $courseId,
                $sessionId
            );
            if (!empty($subcats)) {
                /** @var Category $subcat */
                foreach ($subcats as $subcat) {
                    $sublinks = $subcat->get_links(
                        $studentId,
                        false,
                        $courseId,
                        $sessionId
                    );
                    $links = array_merge($links, $sublinks);
                }
            }
        }

        return $links;
    }

    /**
     * Get all the categories from with the same given direct parent.
     *
     * @param int $catId Category parent ID
     *
     * @return array Array of Category objects
     */
    public function getCategories($catId)
    {
        $catId = (int) $catId;
        $tblGradeCategories = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $sql = 'SELECT * FROM '.$tblGradeCategories.'
                WHERE parent_id = '.$catId;

        $result = Database::query($sql);
        $categories = self::create_category_objects_from_sql_result($result);

        return $categories;
    }

    /**
     * Gets the type for the current object.
     *
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
     * Find category by name.
     *
     * @param string $name_mask search string
     *
     * @return array category objects matching the search criterium
     */
    public static function find_category($name_mask, $allcat)
    {
        $categories = [];
        foreach ($allcat as $search_cat) {
            if (!(false === strpos(strtolower($search_cat->get_name()), strtolower($name_mask)))) {
                $categories[] = $search_cat;
            }
        }

        return $categories;
    }

    /**
     * This function, locks a category , only one who can unlock it is
     * the platform administrator.
     *
     * @param int $locked locked = 1, unlocked = 0
     *
     * @return void
     *
     * @throws Exception
     */
    public function lock(int $locked): void
    {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $sql = "UPDATE $table SET locked = '".intval($locked)."'
                WHERE id = ".$this->id;
        Database::query($sql);
    }

    /**
     * @param $locked
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws Exception
     */
    public function lockAllItems($locked)
    {
        if ('true' == api_get_setting('gradebook_locking_enabled')) {
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
            if (1 == $locked) {
                $event_type = LOG_GRADEBOOK_LOCKED;
            }
            Event::addEvent($event_type, LOG_GRADEBOOK_ID, $this->id);
        }
    }

    /**
     * Generates a certificate for this user if everything matches.
     */
    public static function generateUserCertificate(
        GradebookCategory $category,
        int $user_id,
        bool $sendNotification = false,
        bool $skipGenerationIfExists = false
    ) {
        $user_id = (int) $user_id;
        $categoryId = $category->getId();
        $sessionId = $category->getSession() ? $category->getSession()->getId() : 0;
        $courseId = $category->getCourse()->getId();

        // check if all min_score requirements are met
        if (!self::userMeetsMinimumScores($user_id, $category)) {
            return false; // Do not generate certificate if the user does not meet all min_score criteria
        }

        $skillToolEnabled = SkillModel::hasAccessToUserSkill(api_get_user_id(), $user_id);
        $userHasSkills = false;
        if ($skillToolEnabled) {
            $skill = new SkillModel();
            $skill->addSkillToUser($user_id, $category, $courseId, $sessionId);
            $objSkillRelUser = new SkillRelUserModel();
            $userSkills = $objSkillRelUser->getUserSkills($user_id, $courseId, $sessionId);
            $userHasSkills = !empty($userSkills);
        }

        // If certificate generation is disabled, return only badge link (if available)
        if (empty($category->getGenerateCertificates())) {
            if ($userHasSkills) {
                return [
                    'badge_link' => Display::toolbarButton(
                        get_lang('Export badges'),
                        api_get_path(WEB_CODE_PATH)."gradebook/get_badges.php?user=$user_id",
                        'open-in-new'
                    ),
                ];
            }

            return false;
        }
        $my_certificate = GradebookUtils::get_certificate_by_user_id($categoryId, $user_id);

        // If certificate already exists and we should skip regeneration, return false
        if ($skipGenerationIfExists && !empty($my_certificate)) {
            return false;
        }

        $categoryLegacy = self::load($categoryId);
        $categoryLegacy = $categoryLegacy[0];

        /** @var Category $categoryLegacy */
        $totalScore = $categoryLegacy->calc_score($user_id);

        // Do not remove this the gradebook/lib/fe/gradebooktable.class.php
        // file load this variable as a global
        $scoredisplay = ScoreDisplay::instance();
        $my_score_in_gradebook = $scoredisplay->display_score($totalScore, SCORE_SIMPLE);

        if (empty($my_certificate)) {
            GradebookUtils::registerUserInfoAboutCertificate(
                $categoryId,
                $user_id,
                $my_score_in_gradebook,
                api_get_utc_datetime()
            );
            $my_certificate = GradebookUtils::get_certificate_by_user_id($categoryId, $user_id);
        }

        $html = [];
        if (!empty($my_certificate)) {
            $pathToCertificate = $category->getDocument()->getResourceNode()->getResourceFiles()->first()->getFile()->getPathname();

            $certificate_obj = new Certificate(
                $my_certificate['id'],
                0,
                $sendNotification,
                true,
                $pathToCertificate
            );

            $fileWasGenerated = $certificate_obj->isHtmlFileGenerated();

            // Fix when using a custom certificate plugin
            if ('true' === api_get_plugin_setting('customcertificate', 'enable_plugin_customcertificate')) {
                $infoCertificate = CustomCertificatePlugin::getCertificateData($my_certificate['id'], $user_id);
                if (!empty($infoCertificate)) {
                    $fileWasGenerated = true;
                }
            }

            $isOwner = api_get_user_id() == $user_id;
            $isPlatformAdmin = api_is_platform_admin();
            $isCourseAdmin = api_is_course_admin($courseId);

            $canViewCertificate = $isOwner || $isPlatformAdmin || $isCourseAdmin || !empty($my_certificate['publish']);

            if (!empty($fileWasGenerated) && $canViewCertificate) {
                $certificates = '';
                $exportToPDF = null;
                $pdfUrl = null;

                if (!empty($my_certificate['pathCertificate'])) {
                    $hash = pathinfo($my_certificate['pathCertificate'], PATHINFO_FILENAME);

                    $url = api_get_path(WEB_PATH) . 'certificates/' . $hash . '.html';
                    $pdfUrl = api_get_path(WEB_PATH) . 'certificates/' . $hash . '.pdf';

                    $certificates = Display::toolbarButton(
                        get_lang('Display certificate'),
                        $url,
                        'eye',
                        'primary',
                        ['target' => '_blank']
                    );

                    $exportToPDF = Display::url(
                        Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export to PDF')),
                        $pdfUrl,
                        ['target' => '_blank']
                    );
                }

                $hideExportLink = api_get_setting('gradebook.hide_certificate_export_link');
                $hideExportLinkStudent = api_get_setting('gradebook.hide_certificate_export_link_students');
                if ('true' === $hideExportLink || (api_is_student() && 'true' === $hideExportLinkStudent)) {
                    $exportToPDF = null;
                }

                $html = [
                    'certificate_link' => $certificates,
                    'pdf_link' => $exportToPDF,
                    'pdf_url' => $pdfUrl,
                ];
            }

            if ($skillToolEnabled && $userHasSkills) {
                $html['badge_link'] = Display::toolbarButton(
                    get_lang('Export badges'),
                    api_get_path(WEB_CODE_PATH)."gradebook/get_badges.php?user=$user_id",
                    'open-in-new'
                );
            }

            return $html;
        }

        return false;
    }

    /**
     * Checks whether the user has met the minimum score (`min_score`) in all required evaluations.
     */
    public static function userMeetsMinimumScores(int $userId, GradebookCategory $category): bool
    {
        $evaluations = $category->getEvaluations();

        foreach ($evaluations as $evaluation) {
            $minScore = $evaluation->getMinScore();
            if ($minScore !== null) {
                $userScore = self::getUserScoreForEvaluation($userId, $evaluation->getId());
                if ($userScore === null || $userScore < $minScore) {
                    return false; // If at least one evaluation is below `min_score`, return false
                }
            }
        }

        return true;
    }

    /**
     * Retrieves the score of a user for a specific evaluation using the GradebookResult repository.
     */
    public static function getUserScoreForEvaluation(int $userId, int $evaluationId): ?float
    {
        $gradebookResultRepo = Container::getGradebookResultRepository();

        $gradebookResult = $gradebookResultRepo->findOneBy([
            'user' => $userId,
            'evaluation' => $evaluationId,
        ]);

        return $gradebookResult ? $gradebookResult->getScore() : null;
    }

    /**
     * @param int   $catId
     * @param array $userList
     */
    public static function exportAllCertificates($catId, $userList = [])
    {
        $orientation = api_get_setting('document.certificate_pdf_orientation');

        $params['orientation'] = 'landscape';
        if (!empty($orientation)) {
            $params['orientation'] = $orientation;
        }

        $params['left'] = 0;
        $params['right'] = 0;
        $params['top'] = 0;
        $params['bottom'] = 0;
        $page_format = 'landscape' == $params['orientation'] ? 'A4-L' : 'A4';
        $pdf = new PDF($page_format, $params['orientation'], $params);
        if ('true' === api_get_setting('certificate.add_certificate_pdf_footer')) {
            $pdf->setCertificateFooter();
        }
        $certificate_list = GradebookUtils::get_list_users_certificates($catId, $userList);
        $certificate_path_list = [];

        if (!empty($certificate_list)) {
            foreach ($certificate_list as $index => $value) {
                $list_certificate = GradebookUtils::get_list_gradebook_certificates_by_user_id(
                    $value['user_id'],
                    $catId
                );
                foreach ($list_certificate as $value_certificate) {
                    $certificate_obj = new Certificate($value_certificate['id']);
                    $certificate_obj->generate(['hide_print_button' => true]);
                    if ($certificate_obj->isHtmlFileGenerated()) {
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
     * Check whether a user has finished a course by its gradebook.
     */
    public static function userFinishedCourse(
        int $userId,
        GradebookCategory $category,
        bool $recalculateScore = false,
        ?int $courseId = null,
        ?int $sessionId = null
    ): bool {
        $currentScore = self::getCurrentScore(
            $userId,
            $category,
            $recalculateScore,
            $courseId,
            $sessionId
        );

        $minCertificateScore = $category->getCertifMinScore();

        return $currentScore >= $minCertificateScore;
    }

    /**
     * Get the current score (as percentage) on a gradebook category for a user.
     */
    public static function getCurrentScore(
        int               $userId,
        GradebookCategory $category,
        bool              $recalculate = false,
        ?int              $courseId = null,
        ?int              $sessionId = null
    ): float|int {

        if ($recalculate) {
            return self::calculateCurrentScore(
                $userId,
                $category,
                $courseId,
                $sessionId
            );
        }

        $resultData = Database::select(
            '*',
            Database::get_main_table(TABLE_MAIN_GRADEBOOK_SCORE_LOG),
            [
                'where' => [
                    'category_id = ? AND user_id = ?' => [$category->getId(), $userId],
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
     * Register the current score for a user on a category gradebook.
     *
     * @param float $score      The achieved score
     * @param int   $userId     The user id
     * @param int   $categoryId The gradebook category
     *
     * @return int The insert id
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

    /**
     * @return string
     */
    public static function getUrl()
    {
        $url = Session::read('gradebook_dest');
        if (empty($url)) {
            // We guess the link
            $courseInfo = api_get_course_info();
            if (!empty($courseInfo)) {
                return api_get_path(WEB_CODE_PATH).'gradebook/index.php?'.api_get_cidreq().'&';
            } else {
                return api_get_path(WEB_CODE_PATH).'gradebook/gradebook.php?';
            }
        }

        return $url;
    }

    /**
     * Destination is index.php or gradebook.php.
     *
     * @param string $url
     */
    public static function setUrl($url)
    {
        switch ($url) {
            case 'gradebook.php':
                $url = api_get_path(WEB_CODE_PATH).'gradebook/gradebook.php?';
                break;
            case 'index.php':
                $url = api_get_path(WEB_CODE_PATH).'gradebook/index.php?'.api_get_cidreq().'&';
                break;
        }
        Session::write('gradebook_dest', $url);
    }

    /**
     * @return int
     */
    public function getGradeBooksToValidateInDependence()
    {
        return $this->gradeBooksToValidateInDependence;
    }

    /**
     * @param int $value
     *
     * @return Category
     */
    public function setGradeBooksToValidateInDependence($value)
    {
        $this->gradeBooksToValidateInDependence = $value;

        return $this;
    }

    /**
     * Return HTML code with links to download and view certificate.
     *
     * @return string
     */
    public static function getDownloadCertificateBlock(array $certificate)
    {
        if (!isset($certificate['pdf_url'])) {
            return '';
        }

        $downloadLink = Display::toolbarButton(
            get_lang('Download certificate in PDF'),
            $certificate['pdf_url'],
            'file-pdf-box'
        );
        $viewLink = $certificate['certificate_link'];

        return "
            <div class='panel panel-default'>
                <div class='panel-body'>
                    <h3 class='text-center'>".get_lang('You can now download your certificate by clicking here')."</h3>
                    <div class='text-center'>$downloadLink $viewLink</div>
                </div>
            </div>
        ";
    }

    /**
     * Find a gradebook category by the certificate ID.
     *
     * @param int $id certificate id
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return Category|null
     */
    public static function findByCertificate($id)
    {
        $category = Database::getManager()
            ->createQuery('SELECT c.catId FROM ChamiloCoreBundle:GradebookCertificate c WHERE c.id = :id')
            ->setParameters(['id' => $id])
            ->getOneOrNullResult();

        if (empty($category)) {
            return null;
        }

        $category = self::load($category['catId']);

        if (empty($category)) {
            return null;
        }

        return $category[0];
    }

    /**
     * @param int $value
     */
    public function setDocumentId($value)
    {
        $this->documentId = (int) $value;
    }

    /**
     * @return int
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * Get the remaining weight in root category.
     *
     * @return int
     */
    public function getRemainingWeight()
    {
        $subCategories = $this->get_subcategories();

        $subWeight = 0;

        /** @var Category $subCategory */
        foreach ($subCategories as $subCategory) {
            $subWeight += $subCategory->get_weight();
        }

        return $this->weight - $subWeight;
    }

    /**
     * @return int
     */
    public function getCourseId()
    {
        return $this->courseId;
    }

    /**
     * Sets both the course ID and course code. If course ID is empty, set both to null;
     * @param ?int $courseId
     *
     * @return Category
     */
    public function setCourseId(?int $courseId = null): Category
    {
        $courseInfo = api_get_course_info_by_id($courseId);
        if (!empty($courseInfo)) {
            $this->course_code = $courseInfo['code'];
            $this->courseId = $courseId;
        } else {
            $this->course_code = null;
            $this->courseId = null;
        }

        return $this;
    }

    /**
     * @return Category
     */
    private static function create_root_category()
    {
        $cat = new Category();
        $cat->set_id(0);
        $cat->set_name(get_lang('Main folder'));
        $cat->set_description('');
        $cat->set_user_id(0);
        $cat->setCourseId(0);
        $cat->set_parent_id(0);
        $cat->set_weight(0);
        $cat->set_visible(1);
        $cat->setGenerateCertificates(0);
        $cat->setIsRequirement(0);

        return $cat;
    }

    /**
     * @param ?Doctrine\DBAL\Result $result
     *
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    private static function create_category_objects_from_sql_result(?Doctrine\DBAL\Result $result)
    {
        $categories = [];
        $allow = ('true' === api_get_setting('gradebook.allow_gradebook_stats'));
        if ($allow) {
            $em = Database::getManager();
            $repo = $em->getRepository(GradebookCategory::class);
        }

        if (!empty($result)) {
            while ($data = Database::fetch_array($result)) {
                $cat = new Category();
                $cat->set_id($data['id']);
                $cat->set_name($data['title']);
                $cat->set_description($data['description']);
                $cat->set_user_id($data['user_id']);
                $cat->setCourseId($data['c_id']);
                $cat->set_parent_id($data['parent_id']);
                $cat->set_weight($data['weight']);
                $cat->set_visible($data['visible']);
                $cat->set_session_id($data['session_id']);
                $cat->set_certificate_min_score($data['certif_min_score']);
                $cat->set_grade_model_id((int) $data['grade_model_id']);
                $cat->set_locked($data['locked']);
                $cat->setGenerateCertificates($data['generate_certificates']);
                $cat->setIsRequirement($data['is_requirement']);
                //$cat->setCourseListDependency(isset($data['depends']) ? $data['depends'] : []);
                $cat->setMinimumToValidate(isset($data['minimum_to_validate']) ? $data['minimum_to_validate'] : null);
                $cat->setGradeBooksToValidateInDependence(isset($data['gradebooks_to_validate_in_dependence']) ? $data['gradebooks_to_validate_in_dependence'] : null);
                $cat->setDocumentId($data['document_id']);
                if ($allow) {
                    $cat->entity = $repo->find($data['id']);
                }

                $categories[] = $cat;
            }
        }

        return $categories;
    }

    /**
     * Internal function used by get_target_categories().
     *
     * @param array $targets
     * @param int   $level
     * @param int   $catid
     *
     * @return array
     */
    private function addTargetSubcategories($targets, $level, $catid)
    {
        $subcats = self::load(null, null, 0, $catid);
        foreach ($subcats as $cat) {
            if ($this->can_be_moved_to_cat($cat)) {
                $targets[] = [
                    $cat->get_id(),
                    $cat->get_name(),
                    $level + 1,
                ];
                $targets = $this->addTargetSubcategories(
                    $targets,
                    $level + 1,
                    $cat->get_id()
                );
            }
        }

        return $targets;
    }

    /**
     * Internal function used by get_target_categories() and addTargetSubcategories()
     * Can this category be moved to the given category ?
     * Impossible when origin and target are the same... children won't be processed
     * either. (a category can't be moved to one of its own children).
     */
    private function can_be_moved_to_cat($cat)
    {
        return $cat->get_id() != $this->get_id();
    }

    /**
     * Internal function used by move_to_cat().
     */
    private function applyCourseCodeToChildren()
    {
        $cats = self::load(null, null, 0, $this->id, null);
        $evals = Evaluation::load(null, null, 0, $this->id, null);
        $links = LinkFactory::load(
            null,
            null,
            null,
            null,
            0,
            $this->id,
            null
        );
        /** @var Category $cat */
        foreach ($cats as $cat) {
            $cat->setCourseId($this->getCourseId());
            $cat->save();
            $cat->applyCourseCodeToChildren();
        }

        foreach ($evals as $eval) {
            $eval->setCourseId($this->getCourseId());
            $eval->save();
        }

        foreach ($links as $link) {
            $link->delete();
        }
    }

    /**
     * Internal function used by get_tree().
     *
     * @param int      $level
     * @param int|null $visible
     *
     * @return array
     */
    private function add_subtree($targets, $level, $catid, $visible)
    {
        $subcats = self::load(null, null, 0, $catid, $visible);

        if (!empty($subcats)) {
            foreach ($subcats as $cat) {
                $targets[] = [
                    $cat->get_id(),
                    $cat->get_name(),
                    $level + 1,
                ];
                $targets = self::add_subtree(
                    $targets,
                    $level + 1,
                    $cat->get_id(),
                    $visible
                );
            }
        }

        return $targets;
    }

    /**
     * Calculate the current score on a gradebook category for a user.
     *
     * @return float The score
     */
    private static function calculateCurrentScore(
        int $userId,
        ?GradebookCategory $category = null,
        ?int $courseId = null,
        ?int $sessionId = null,
    ): float|int {

        if (null === $category) {
            return 0;
        }

        $categoryList = self::load(
            null,
            null,
            $courseId,
            null,
            null,
            $sessionId
        );

        /* @var Category $category */
        $category = $categoryList[0] ?? null;

        if (null === $category) {
            return 0;
        }

        $courseEvaluations = $category->get_evaluations($userId, true);
        $courseLinks = $category->get_links($userId, true);
        $evaluationsAndLinks = array_merge($courseEvaluations, $courseLinks);
        $count = count($evaluationsAndLinks);
        if (empty($count)) {
            return 0;
        }

        $categoryScore = 0;
        for ($i = 0; $i < $count; $i++) {
            /** @var AbstractLink $item */
            $item = $evaluationsAndLinks[$i];
            // Set session id from category
            $item->set_session_id($category->get_session_id());
            $score = $item->calc_score($userId);
            $itemValue = 0;
            if (!empty($score)) {
                $divider = 0 == $score[1] ? 1 : $score[1];
                $itemValue = $score[0] / $divider * $item->get_weight();
            }

            $categoryScore += $itemValue;
        }

        return api_float_val($categoryScore);
    }
}
