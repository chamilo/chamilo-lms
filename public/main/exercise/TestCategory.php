<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use ChamiloSession as Session;

/**
 * Class TestCategory.
 * Manage question categories inside an exercise.
 *
 * @author hubert.borderiou
 * @author Julio Montoya - several fixes
 *
 * @todo   rename to ExerciseCategory
 */
class TestCategory
{
    public $id;
    public $name;
    public $description;

    /**
     * Constructor of the class Category.
     */
    public function __construct()
    {
        $this->name = '';
        $this->description = '';
    }

    /**
     * return the TestCategory object with id=in_id.
     *
     * @param int $id
     * @param int $courseId
     *
     * @return TestCategory
     */
    public function getCategory($id, $courseId = 0)
    {
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
        $id = (int) $id;
        $courseId = empty($courseId) ? api_get_course_int_id() : (int) $courseId;
        $sql = "SELECT * FROM $table
                WHERE iid = $id";
        $res = Database::query($sql);

        if (Database::num_rows($res)) {
            $row = Database::fetch_array($res);

            $this->id = $row['iid'];
            $this->name = $row['title'];
            $this->description = $row['description'];

            return $this;
        }

        return false;
    }

    /**
     * Save TestCategory in the database if name doesn't exists.
     *
     * @param int $courseId
     *
     * @return bool
     */
    public function save($courseId = 0)
    {
        $courseId = empty($courseId) ? api_get_course_int_id() : $courseId;

        if (empty($courseId)) {
            return false;
        }

        $course = api_get_course_entity($courseId);
        $repo = Container::getQuestionCategoryRepository();
        $category = $repo->findCourseResourceByTitle($this->name, $course->getResourceNode(), $course);

        if (null === $category) {
            $category = new CQuizQuestionCategory();
            $category
                ->setTitle($this->name)
                ->setDescription($this->description)
                ->setParent($course)
                ->addCourseLink($course, api_get_session_entity());
            $repo->create($category);

            if ($category) {
                return $category->getIid();
            }
        }

        return false;
    }

    /**
     * Removes the category from the database
     * if there were question in this category, the link between question and category is removed.
     *
     * @param int $id
     *
     * @return bool
     */
    public function removeCategory($id)
    {
        $tbl_question_rel_cat = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $id = (int) $id;
        $course_id = api_get_course_int_id();
        $category = $this->getCategory($id, $course_id);

        if ($category) {
            // remove link between question and category
            $sql = "DELETE FROM $tbl_question_rel_cat
                    WHERE category_id = $id ";
            Database::query($sql);

            $repo = Container::getQuestionCategoryRepository();
            $category = $repo->find($id);
            $repo->hardDelete($category);

            return true;
        }

        return false;
    }

    /**
     * Modify category name or description of category with id=in_id.
     *
     * @param int $courseId
     *
     * @return bool
     */
    public function modifyCategory($courseId = 0)
    {
        $courseId = empty($courseId) ? api_get_course_int_id() : (int) $courseId;
        $courseInfo = api_get_course_info_by_id($courseId);
        if (empty($courseInfo)) {
            return false;
        }

        $repo = Container::getQuestionCategoryRepository();
        /** @var CQuizQuestionCategory $category */
        $category = $repo->find($this->id);
        if ($category) {
            $category
                ->setTitle($this->name)
                ->setDescription($this->description);

            $repo->update($category);

            return true;
        }

        return false;
    }

    /**
     * Gets the number of question of category id=in_id.
     */
    public function getCategoryQuestionsNumber()
    {
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $id = (int) $this->id;
        $sql = "SELECT count(*) AS nb
                FROM $table
                WHERE category_id = $id AND c_id=".api_get_course_int_id();
        $res = Database::query($sql);
        $row = Database::fetch_array($res);

        return $row['nb'];
    }

    /**
     * Return the TestCategory id for question with question_id = $questionId
     * In this version, a question has only 1 TestCategory.
     * Return the TestCategory id, 0 if none.
     *
     * @param int $questionId
     * @param int $courseId
     *
     * @return int
     */
    public static function getCategoryForQuestion($questionId, $courseId = 0)
    {
        $categoryInfo = self::getCategoryInfoForQuestion($questionId, $courseId);

        if (!empty($categoryInfo) && isset($categoryInfo['category_id'])) {
            return (int) $categoryInfo['category_id'];
        }

        return 0;
    }

    public static function getCategoryInfoForQuestion($questionId, $courseId = 0)
    {
        $courseId = (int) $courseId;
        $questionId = (int) $questionId;

        if (empty($courseId)) {
            $courseId = api_get_course_int_id();
        }

        if (empty($courseId) || empty($questionId)) {
            return 0;
        }

        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $sql = "SELECT *
                FROM $table
                WHERE question_id = $questionId";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            return Database::fetch_array($res, 'ASSOC');
        }

        return [];
    }

    /**
     * Return the category name for question with question_id = $questionId
     * In this version, a question has only 1 category.
     *
     * @param $questionId
     * @param int $courseId
     *
     * @return string
     */
    public static function getCategoryNameForQuestion($questionId, $courseId = 0)
    {
        if (empty($courseId)) {
            $courseId = api_get_course_int_id();
        }
        $courseId = (int) $courseId;
        $categoryId = self::getCategoryForQuestion($questionId, $courseId);
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
        $sql = "SELECT title
                FROM $table
                WHERE iid = $categoryId ";
        $res = Database::query($sql);
        $data = Database::fetch_array($res);
        $result = '';
        if (Database::num_rows($res) > 0) {
            $result = $data['title'];
        }

        return $result;
    }

    /**
     * Return the list of different categories ID for a test in the current course
     * hubert.borderiou 07-04-2011.
     *
     * @param int $exerciseId
     * @param int $courseId
     *
     * @return array
     */
    public static function getListOfCategoriesIDForTest($exerciseId, $courseId = 0)
    {
        // parcourir les questions d'un test, recup les categories uniques dans un tableau
        $exercise = new Exercise($courseId);
        $exercise->read($exerciseId, false);
        $categoriesInExercise = $exercise->getQuestionWithCategories();
        // the array given by selectQuestionList start at indice 1 and not at indice 0 !!! ???
        $categories = [];
        if (!empty($categoriesInExercise)) {
            foreach ($categoriesInExercise as $category) {
                $categories[$category['iid']] = $category;
            }
        }

        return $categories;
    }

    /**
     * @return array
     */
    public static function getListOfCategoriesIDForTestObject(Exercise $exercise)
    {
        // parcourir les questions d'un test, recup les categories uniques dans un tableau
        $categories_in_exercise = [];
        $question_list = $exercise->getQuestionOrderedListByName();

        // the array given by selectQuestionList start at indice 1 and not at indice 0 !!! ???
        foreach ($question_list as $questionInfo) {
            $question_id = $questionInfo['question_id'];
            $category_list = self::getCategoryForQuestion($question_id);
            if (is_numeric($category_list)) {
                $category_list = [$category_list];
            }

            if (!empty($category_list)) {
                $categories_in_exercise = array_merge($categories_in_exercise, $category_list);
            }
        }
        if (!empty($categories_in_exercise)) {
            $categories_in_exercise = array_unique(array_filter($categories_in_exercise));
        }

        return $categories_in_exercise;
    }

    /**
     * Return the list of different categories NAME for a test.
     *
     * @param int $exerciseId
     * @param bool
     *
     * @return array
     *
     * @author function rewrote by jmontoya
     */
    public static function getListOfCategoriesNameForTest($exerciseId, $grouped_by_category = true)
    {
        $result = [];
        $categories = self::getListOfCategoriesIDForTest($exerciseId);

        foreach ($categories as $catInfo) {
            $categoryId = $catInfo['iid'];
            if (!empty($categoryId)) {
                $result[$categoryId] = [
                    'id' => $categoryId,
                    'title' => $catInfo['title'],
                    //'parent_id' =>  $catInfo['parent_id'],
                    'parent_id' => '',
                ];
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public static function getListOfCategoriesForTest(Exercise $exercise)
    {
        $result = [];
        $categories = self::getListOfCategoriesIDForTestObject($exercise);
        foreach ($categories as $cat_id) {
            $cat = new self();
            $cat = (array) $cat->getCategory($cat_id);
            $cat['iid'] = $cat['id'];
            $cat['title'] = $cat['name'];
            $result[$cat['id']] = $cat;
        }

        return $result;
    }

    /**
     * return the number of question of a category id in a test.
     *
     * @param int $exerciseId
     * @param int $categoryId
     *
     * @return int
     *
     * @author hubert.borderiou 07-04-2011
     */
    public static function getNumberOfQuestionsInCategoryForTest($exerciseId, $categoryId)
    {
        $nbCatResult = 0;
        $quiz = new Exercise();
        $quiz->read($exerciseId);
        $questionList = $quiz->selectQuestionList();
        // the array given by selectQuestionList start at indice 1 and not at indice 0 !!! ? ? ?
        for ($i = 1; $i <= count($questionList); $i++) {
            if (self::getCategoryForQuestion($questionList[$i]) == $categoryId) {
                $nbCatResult++;
            }
        }

        return $nbCatResult;
    }

    /**
     * return the number of question for a test using random by category
     * input  : test_id, number of random question (min 1).
     *
     * @param int $exerciseId
     * @param int $random
     *
     * @return int
     *             hubert.borderiou 07-04-2011
     *             question without categories are not counted
     */
    public static function getNumberOfQuestionRandomByCategory($exerciseId, $random)
    {
        $count = 0;
        $categories = self::getListOfCategoriesIDForTest($exerciseId);
        foreach ($categories as $category) {
            if (empty($category['id'])) {
                continue;
            }

            $nbQuestionInThisCat = self::getNumberOfQuestionsInCategoryForTest(
                $exerciseId,
                $category['id']
            );

            if ($nbQuestionInThisCat > $random) {
                $count += $random;
            } else {
                $count += $nbQuestionInThisCat;
            }
        }

        return $count;
    }

    /**
     * Return an array (id=>name)
     * array[0] = get_lang('NoCategory');.
     *
     * @param int $courseId
     *
     * @return array
     */
    public static function getCategoriesIdAndName($courseId = 0)
    {
        if (empty($courseId)) {
            $courseId = api_get_course_int_id();
        }
        $categories = self::getCategories($courseId);
        $result = [0 => get_lang('No category selected')];
        foreach ($categories as $category) {
            $result[$category->getIid()] = $category->getTitle();
        }

        return $result;
    }

    /**
     * Returns an array of question ids for each category
     * $categories[1][30] = 10, array with category id = 1 and question_id = 10
     * A question has "n" categories.
     *
     */
    public static function getQuestionsByCat(
        int   $exerciseId,
        array $checkInQuestionList = [],
        array $categoriesAddedInExercise = [],
        $onlyMandatory = false
    ): array {
        $tableQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $tblExerciseQuestion = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $tblQuestionRelCategory = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $categoryTable = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
        $exerciseId = (int) $exerciseId;

        $mandatoryCondition = '';
        if ($onlyMandatory) {
            $mandatoryCondition = ' AND qrc.mandatory = 1';
        }
        $sql = "SELECT DISTINCT qrc.question_id, qrc.category_id
                FROM $tblQuestionRelCategory qrc
                INNER JOIN $tblExerciseQuestion eq
                ON (eq.question_id = qrc.question_id)
                INNER JOIN $categoryTable c
                ON (c.iid = qrc.category_id)
                INNER JOIN $tableQuestion q
                ON (q.iid = qrc.question_id)
                WHERE
                    quiz_id = $exerciseId
                    $mandatoryCondition
                ";

        $res = Database::query($sql);
        $categories = [];
        while ($data = Database::fetch_array($res)) {
            if (!empty($checkInQuestionList) && !in_array($data['question_id'], $checkInQuestionList)) {
                continue;
            }

            if (!isset($categories[$data['category_id']]) ||
                !is_array($categories[$data['category_id']])
            ) {
                $categories[$data['category_id']] = [];
            }
            $categories[$data['category_id']][] = $data['question_id'];
        }

        if (!empty($categoriesAddedInExercise)) {
            $newCategoryList = [];
            foreach ($categoriesAddedInExercise as $category) {
                $categoryId = $category['category_id'];
                if (isset($categories[$categoryId])) {
                    $newCategoryList[$categoryId] = $categories[$categoryId];
                }
            }

            $checkQuestionsWithNoCategory = false;
            foreach ($categoriesAddedInExercise as $category) {
                if (empty($category['category_id'])) {
                    // Check
                    $checkQuestionsWithNoCategory = true;

                    break;
                }
            }

            // Select questions that don't have any category related
            if ($checkQuestionsWithNoCategory) {
                $originalQuestionList = $checkInQuestionList;
                foreach ($originalQuestionList as $questionId) {
                    $categoriesFlatten = array_flatten($categories);
                    if (!in_array($questionId, $categoriesFlatten)) {
                        $newCategoryList[0][] = $questionId;
                    }
                }
            }
            $categories = $newCategoryList;
        }

        return $categories;
    }

    /**
     * Returns an array of $numberElements from $elements.
     *
     * @param array $elements
     * @param int   $numberElements
     * @param bool  $shuffle
     * @param array $mandatoryElements
     *
     * @return array
     */
    public static function getNElementsFromArray($elements, $numberElements, $shuffle = true, $mandatoryElements = [])
    {
        $countElements = count($elements);
        $countMandatory = count($mandatoryElements);

        if (!empty($countMandatory)) {
            if ($countMandatory >= $numberElements) {
                if ($shuffle) {
                    shuffle($mandatoryElements);
                }
                $elements = array_slice($mandatoryElements, 0, $numberElements);

                return $elements;
            }

            $diffCount = $numberElements - $countMandatory;
            $diffElements = array_diff($elements, $mandatoryElements);
            if ($shuffle) {
                shuffle($diffElements);
            }
            $elements = array_slice($diffElements, 0, $diffCount);
            $totalElements = array_merge($mandatoryElements, $elements);
            if ($shuffle) {
                shuffle($totalElements);
            }

            return $totalElements;
        }

        if ($shuffle) {
            shuffle($elements);
        }

        if ($numberElements < $countElements) {
            $elements = array_slice($elements, 0, $numberElements);
        }

        return $elements;
    }

    /**
     * @param int $questionId
     * @param int $displayCategoryName
     */
    public static function displayCategoryAndTitle($questionId, $displayCategoryName = 1)
    {
        echo self::returnCategoryAndTitle($questionId, $displayCategoryName);
    }

    /**
     * @param int $questionId
     * @param int $in_display_category_name
     *
     * @return string|null
     */
    public static function returnCategoryAndTitle($questionId, $in_display_category_name = 1)
    {
        $is_student = !(api_is_allowed_to_edit(null, true) || api_is_session_admin());
        $objExercise = Session::read('objExercise');
        if (!empty($objExercise)) {
            $in_display_category_name = $objExercise->display_category_name;
        }
        $content = null;
        if ('' != self::getCategoryNameForQuestion($questionId) &&
            (1 == $in_display_category_name || !$is_student)
        ) {
            $content .= '<div class="page-header">';
            $content .= '<h4>'.get_lang('Category').': '.self::getCategoryNameForQuestion($questionId).'</h4>';
            $content .= '</div>';
        }

        return $content;
    }

    /**
     * sortTabByBracketLabel ($tabCategoryQuestions)
     * key of $tabCategoryQuestions are the category id (0 for not in a category)
     * value is the array of question id of this category
     * Sort question by Category.
     */
    public static function sortTabByBracketLabel($in_tab)
    {
        $tabResult = [];
        $tabCatName = []; // tab of category name
        foreach ($in_tab as $cat_id => $tabquestion) {
            $category = new self();
            $category = $category->getCategory($cat_id);
            $tabCatName[$cat_id] = $category->name;
        }
        reset($in_tab);
        // sort table by value, keeping keys as they are
        asort($tabCatName);
        // keys of $tabCatName are keys order for $in_tab
        foreach ($tabCatName as $key => $val) {
            $tabResult[$key] = $in_tab[$key];
        }

        return $tabResult;
    }

    /**
     * Return the number max of question in a category
     * count the number of questions in all categories, and return the max.
     *
     * @param int $exerciseId
     *
     * @author - hubert borderiou
     *
     * @return int
     */
    public static function getNumberMaxQuestionByCat($exerciseId)
    {
        $res_num_max = 0;
        $categories = self::getListOfCategoriesIDForTest($exerciseId);
        foreach ($categories as $category) {
            if (empty($category['id'])) {
                continue;
            }

            $nbQuestionInThisCat = self::getNumberOfQuestionsInCategoryForTest(
                $exerciseId,
                $category['id']
            );

            if ($nbQuestionInThisCat > $res_num_max) {
                $res_num_max = $nbQuestionInThisCat;
            }
        }

        return $res_num_max;
    }

    /**
     * Returns a category summary report.
     *
     * @param Exercise $exercise
     * @param array    $category_list
     *                                pre filled array with the category_id, score, and weight
     *                                example: array(1 => array('score' => '10', 'total' => 20));
     *
     * @return string
     */
    public static function get_stats_table_by_attempt($exercise, $category_list = [])
    {
        if (empty($category_list) || empty($exercise)) {
            return '';
        }

        $hide = (int) $exercise->getPageConfigurationAttribute('hide_category_table');
        if (1 === $hide) {
            return '';
        }
        $exerciseId = $exercise->iId;
        $categoryNameList = self::getListOfCategoriesNameForTest($exerciseId);
        $table = new HTML_Table(
            [
                'class' => 'table table-hover table-striped table-bordered',
                'id' => 'category_results',
            ]
        );
        $table->setHeaderContents(0, 0, get_lang('Categories'));
        $table->setHeaderContents(0, 1, get_lang('Absolute score'));
        $table->setHeaderContents(0, 2, get_lang('Relative score'));
        $row = 1;

        $none_category = [];
        if (isset($category_list['none'])) {
            $none_category = $category_list['none'];
            unset($category_list['none']);
        }

        $total = [];
        if (isset($category_list['total'])) {
            $total = $category_list['total'];
            unset($category_list['total']);
        }
        $radar = '';
        $countCategories = count($category_list);
        if ($countCategories > 1) {
            $tempResult = [];
            $labels = [];
            $labelsWithId = array_column($categoryNameList, 'title', 'id');
            asort($labelsWithId);
            foreach ($labelsWithId as $category_id => $title) {
                if (!isset($category_list[$category_id])) {
                    continue;
                }
                $labels[] = $title;
                $category_item = $category_list[$category_id];

                $table->setCellContents($row, 0, $title);
                $table->setCellContents(
                    $row,
                    1,
                    ExerciseLib::show_score(
                        $category_item['score'],
                        $category_item['total'],
                        false
                    )
                );
                $table->setCellContents(
                    $row,
                    2,
                    ExerciseLib::show_score(
                        $category_item['score'],
                        $category_item['total'],
                        true,
                        false,
                        true
                    )
                );
                $tempResult[$category_id] = round($category_item['score'] / $category_item['total'] * 10);
                $row++;
            }

            if ($countCategories > 2 && RESULT_DISABLE_RADAR === (int) $exercise->results_disabled) {
                $resultsArray = [];
                foreach ($labelsWithId as $categoryId => $label) {
                    if (isset($tempResult[$categoryId])) {
                        $resultsArray[] = $tempResult[$categoryId];
                    } else {
                        $resultsArray[] = 0;
                    }
                }
                $radar = $exercise->getRadar($labels, [$resultsArray]);
            }
            if (!empty($none_category)) {
                $table->setCellContents($row, 0, get_lang('None'));
                $table->setCellContents(
                    $row,
                    1,
                    ExerciseLib::show_score(
                        $none_category['score'],
                        $none_category['total'],
                        false
                    )
                );
                $table->setCellContents(
                    $row,
                    2,
                    ExerciseLib::show_score(
                        $none_category['score'],
                        $none_category['total'],
                        true,
                        false,
                        true
                    )
                );
                $row++;
            }
            if (!empty($total)) {
                $table->setCellContents($row, 0, get_lang('Total'));
                $table->setCellContents(
                    $row,
                    1,
                    ExerciseLib::show_score(
                        $total['score'],
                        $total['total'],
                        false
                    )
                );
                $table->setCellContents(
                    $row,
                    2,
                    ExerciseLib::show_score(
                        $total['score'],
                        $total['total'],
                        true,
                        false,
                        true
                    )
                );
            }

            return $radar.$table->toHtml();
        }

        return '';
    }

    /**
     * Get the category in exercise tree.
     *
     */
    public function getCategoryExerciseTree(
        Exercise $exercise,
        string   $order = null,
        bool     $shuffle = false,
        bool $excludeCategoryWithNoQuestions = true
    ): array {
        if (empty($exercise)) {
            return [];
        }

        $table = Database::get_course_table(TABLE_QUIZ_REL_CATEGORY);
        $categoryTable = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
        $exercise->id = (int) $exercise->id;

        $sql = "SELECT * FROM $table qc
                LEFT JOIN $categoryTable c
                ON (qc.category_id = c.iid)
                WHERE exercise_id = {$exercise->id} ";

        if (!empty($order)) {
            $order = Database::escape_string($order);
            $sql .= "ORDER BY $order";
        }

        $categories = [];
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                if ($excludeCategoryWithNoQuestions && 0 == $row['count_questions']) {
                    continue;
                }
                if (empty($row['title']) && empty($row['category_id'])) {
                    $row['title'] = get_lang('General');
                }
                $categories[$row['category_id']] = $row;
            }
        }

        if ($shuffle) {
            shuffle_assoc($categories);
        }

        return $categories;
    }

    /**
     * @param FormValidator $form
     * @param string        $action
     */
    public function getForm(&$form, $action = 'new')
    {
        switch ($action) {
            case 'new':
                $header = get_lang('Add category');
                $submit = get_lang('Add test category');

                break;
            case 'edit':
                $header = get_lang('Edit this category');
                $submit = get_lang('Edit category');

                break;
        }

        // Setting the form elements
        $form->addElement('header', $header);
        $form->addElement('hidden', 'category_id');
        $form->addElement(
            'text',
            'category_name',
            get_lang('Category name'),
            ['class' => 'span6']
        );
        $form->addHtmlEditor(
            'category_description',
            get_lang('Category description'),
            false,
            false,
            [
                'ToolbarSet' => 'test_category',
                'Width' => '90%',
                'Height' => '200',
            ]
        );
        $category_parent_list = [];

        $options = [
            '1' => get_lang('Visible'),
            '0' => get_lang('Hidden'),
        ];
        $form->addSelect(
            'visibility',
            get_lang('Visibility'),
            $options
        );
        $script = null;
        if (!empty($this->parent_id)) {
            $parent_cat = new self();
            $parent_cat = $parent_cat->getCategory($this->parent_id);
            $category_parent_list = [$parent_cat->id => $parent_cat->name];
            $script .= '<script>$(function() { $("#parent_id").trigger("addItem",[{"title": "'.$parent_cat->name.'", "value": "'.$parent_cat->id.'"}]); });</script>';
        }
        $form->addElement('html', $script);

        $form->addSelect('parent_id', get_lang('Parent'), $category_parent_list, ['id' => 'parent_id']);
        $form->addElement('style_submit_button', 'SubmitNote', $submit, 'class="add"');

        // setting the defaults
        $defaults = [];
        $defaults['category_id'] = $this->id;
        $defaults['category_name'] = $this->name;
        $defaults['category_description'] = $this->description;
        $defaults['parent_id'] = $this->parent_id;
        $defaults['visibility'] = $this->visibility;
        $form->setDefaults($defaults);

        // setting the rules
        $form->addRule('category_name', get_lang('Required field'), 'required');
    }

    /**
     * Returns the category form.
     *
     * @return string
     */
    public function returnCategoryForm(Exercise $exercise)
    {
        $categories = $this->getListOfCategoriesForTest($exercise);
        $sortedCategories = [];
        foreach ($categories as $catId => $cat) {
            $sortedCategories[$cat['title']] = $cat;
        }
        ksort($sortedCategories);
        $saved_categories = $exercise->getCategoriesInExercise();
        $return = null;

        if (!empty($sortedCategories)) {
            $nbQuestionsTotal = $exercise->getNumberQuestionExerciseCategory();
            $exercise->setCategoriesGrouping(true);
            $real_question_count = count($exercise->getQuestionList());

            $warning = null;
            if ($nbQuestionsTotal != $real_question_count) {
                $warning = Display::return_message(
                    get_lang('Make sure you have enough questions in your categories.'),
                    'warning'
                );
            }

            $return .= $warning;
            $return .= '<table class="table table-hover table-bordered data_table">';
            $return .= '<tr>';
            $return .= '<th height="24">'.get_lang('Categories').'</th>';
            $return .= '<th width="70" height="24">'.get_lang('N°').'</th></tr>';

            $emptyCategory = [
                'id' => '0',
                'name' => get_lang('General'),
                'description' => '',
                'iid' => '0',
                'title' => get_lang('General'),
            ];

            $sortedCategories[] = $emptyCategory;

            foreach ($sortedCategories as $category) {
                $cat_id = $category['iid'];
                $return .= '<tr>';
                $return .= '<td>';
                $return .= Display::div($category['name']);
                $return .= '</td>';
                $return .= '<td>';
                $value = isset($saved_categories) && isset($saved_categories[$cat_id]) ? $saved_categories[$cat_id]['count_questions'] : -1;
                $return .= Display::input(
                    'number',
                    "category[$cat_id]",
                    $value,
                    ['class' => 'form-control', 'min' => -1, 'step' => 1]
                );
                $return .= '</td>';
                $return .= '</tr>';
            }

            $return .= '</table>';
            $return .= get_lang('-1 = All questions will be selected.');
        }

        return $return;
    }

    /**
     * Return true if a category already exists with the same name.
     *
     * @param string $name
     * @param int    $courseId
     *
     * @return bool
     */
    public static function categoryTitleExists($name, $courseId = 0)
    {
        $repo = Container::getQuestionCategoryRepository();
        $courseEntity = api_get_course_entity($courseId);
        $resource = $repo->findCourseResourceByTitle($name, $courseEntity->getResourceNode(), $courseEntity);

        return null !== $resource;
    }

    /**
     * Return the id of the test category with title = $in_title.
     *
     * @param string $title
     * @param int    $courseId
     *
     * @return int is id of test category
     */
    public static function get_category_id_for_title($title, $courseId = 0)
    {
        $out_res = 0;
        $tableCategory = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
        $sql = "SELECT iid FROM $tableCategory
                WHERE title = '".Database::escape_string($title)."'";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $data = Database::fetch_array($res);
            $out_res = $data['id'];
        }

        return $out_res;
    }

    /**
     * Add a relation between question and category in table c_quiz_question_rel_category.
     *
     * @param int $categoryId
     * @param int $questionId
     * @param int $courseId
     *
     * @return string|false
     */
    public static function addCategoryToQuestion($categoryId, $questionId, $courseId)
    {
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        // if question doesn't have a category
        // @todo change for 1.10 when a question can have several categories
        if (0 == self::getCategoryForQuestion($questionId, $courseId) &&
            $questionId > 0 &&
            $courseId > 0
        ) {
            $sql = "INSERT INTO $table (c_id, question_id, category_id)
                    VALUES (".intval($courseId).", ".intval($questionId).", ".intval($categoryId).")";
            Database::query($sql);
            $id = Database::insert_id();

            return $id;
        }

        return false;
    }

    /**
     * @param int $courseId
     * @param int $sessionId
     *
     * @return CQuizQuestionCategory[]
     */
    public static function getCategories($courseId, $sessionId = 0)
    {
        if (empty($courseId)) {
            return [];
        }

        $sessionId = (int) $sessionId;
        $courseId = (int) $courseId;
        $sessionEntity = null;
        if (!empty($sessionId)) {
            $sessionEntity = api_get_session_entity($sessionId);
        }

        $courseEntity = api_get_course_entity($courseId);
        $repo = Container::getQuestionCategoryRepository();
        $resources = $repo->getResourcesByCourse($courseEntity, $sessionEntity);

        return $resources->getQuery()->getResult();
    }

    /**
     * @param int $courseId
     * @param int $sessionId
     *
     * @return string
     */
    public function displayCategories($courseId, $sessionId = 0)
    {
        $course = api_get_course_entity($courseId);
        $session = api_get_session_entity($sessionId);

        $sessionId = (int) $sessionId;
        $categories = $this->getCategories($courseId, $sessionId);
        $html = '';
        foreach ($categories as $category) {
            $id = $category->getIid();
            $count = $category->getQuestions()->count();
            $rowname = self::protectJSDialogQuote($category->getTitle());
            $label = 1 === $count ? $count.' '.get_lang('Question') : $count.' '.get_lang('Questions');
            $content = "<span style='float:right'>".$label."</span>";
            $content .= '<div class="sectioncomment">';
            $content .= $category->getDescription();
            $content .= '</div>';
            $links = '';

            if (!$sessionId) {
                $links .= '<a
                    href="'.api_get_self().'?action=editcategory&id='.$id.'&'.api_get_cidreq().'">'.
                    Display::return_icon('edit.png', get_lang('Edit'), []).'</a>';
                $links .= ' <a
                    href="'.api_get_self().'?'.api_get_cidreq().'&action=deletecategory&id='.$id.'" ';
                $links .= 'onclick="return confirmDelete(\''.
                    self::protectJSDialogQuote(get_lang('DeleteCategoryAreYouSure').'['.$rowname).'] ?\', \'id_cat'.$id.'\');">';
                $links .= Display::return_icon('delete.png', get_lang('Delete'), []).'</a>';
            }

            $html .= Display::panel($content, $category->getTitle().$links);
        }

        return $html;
    }

    /**
     * To allowed " in javascript dialog box without bad surprises
     * replace " with two '.
     *
     * @param string $text
     *
     * @return mixed
     */
    public function protectJSDialogQuote($text)
    {
        $res = $text;
        $res = str_replace("'", "\'", $res);
        // 8. Set route and request
        $res = str_replace('"', "\'\'", $res);

        return $res;
    }
}
