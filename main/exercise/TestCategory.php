<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class TestCategory
 * @author hubert.borderiou
 * @author Julio Montoya - several fixes
 * @todo rename to ExerciseCategory
 */
class TestCategory
{
    public $id;
    public $name;
    public $description;

    /**
     * Constructor of the class Category
     */
    public function __construct()
    {
    }

    /**
     * return the TestCategory object with id=in_id
     * @param int $id
     * @param int $courseId
     * @return TestCategory
     */
    public function getCategory($id, $courseId = 0)
    {
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
        $id = intval($id);
        $courseId = empty($courseId) ? api_get_course_int_id() : (int) $courseId;
        $sql = "SELECT * FROM $table
                WHERE id = $id AND c_id = ".$courseId;
        $res = Database::query($sql);

        if (Database::num_rows($res)) {
            $row = Database::fetch_array($res);

            $this->id = $row['id'];
            $this->name = $row['title'];
            $this->description  = $row['description'];

            return $this;
        }

        return false;
    }

    /**
     * Save TestCategory in the database if name doesn't exists
     * @param int $courseId
     * @return bool
     */
    public function save($courseId = 0)
    {
        $courseId = empty($courseId) ? api_get_course_int_id() : (int) $courseId;
        $courseInfo = api_get_course_info_by_id($courseId);
        if (empty($courseInfo)) {
            return false;
        }

        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);

        // check if name already exists
        $sql = "SELECT count(*) AS nb FROM $table
                WHERE title = '".$this->name."' AND c_id=$courseId";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        // lets add in BDD if not the same name
        if ($row['nb'] <= 0) {
            $params = [
                'c_id' => $courseId,
                'title' => $this->name,
                'description' => $this->description
            ];
            $newId = Database::insert($table, $params);

            if ($newId) {
                $sql = "UPDATE $table SET id = iid WHERE iid = $newId";
                Database::query($sql);

                api_item_property_update(
                    $courseInfo,
                    TOOL_TEST_CATEGORY,
                    $newId,
                    'TestCategoryAdded',
                    api_get_user_id()
                );
            }

            return $newId;
        } else {
            return false;
        }
    }

    /**
     * Removes the category from the database
     * if there were question in this category, the link between question and category is removed
     */
    public function removeCategory($id)
    {
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
        $tbl_question_rel_cat = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $id = intval($id);
        $course_id = api_get_course_int_id();
        $category = $this->getCategory($id);

        if ($category) {
            $sql = "DELETE FROM $table
                    WHERE id= $id AND c_id=".$course_id;
            Database::query($sql);

            // remove link between question and category
            $sql = "DELETE FROM $tbl_question_rel_cat
                    WHERE category_id = $id AND c_id=".$course_id;
            Database::query($sql);
            // item_property update
            $course_info = api_get_course_info_by_id($course_id);
            api_item_property_update(
                $course_info,
                TOOL_TEST_CATEGORY,
                $this->id,
                'TestCategoryDeleted',
                api_get_user_id()
            );

            return true;
        }

        return false;
    }

    /**
     * Modify category name or description of category with id=in_id
     * @param int $courseId
     * @return bool
     */
    public function modifyCategory($courseId = 0)
    {
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
        $id = intval($this->id);
        $name = Database::escape_string($this->name);
        $description = Database::escape_string($this->description);
        $cat = $this->getCategory($id);
        $courseId = empty($courseId) ? api_get_course_int_id() : (int) $courseId;
        $courseInfo = api_get_course_info_by_id($courseId);
        if (empty($courseInfo)) {
            return false;
        }

        if ($cat) {
            $sql = "UPDATE $table SET
                        title = '$name',
                        description = '$description'
                    WHERE id = $id AND c_id = ".$courseId;
            Database::query($sql);

            // item_property update
            api_item_property_update(
                $courseInfo,
                TOOL_TEST_CATEGORY,
                $this->id,
                'TestCategoryModified',
                api_get_user_id()
            );
            return true;
        }

        return false;
    }

    /**
     * Gets the number of question of category id=in_id
     */
    public function getCategoryQuestionsNumber()
    {
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $id = intval($this->id);
        $sql = "SELECT count(*) AS nb
                FROM $table
                WHERE category_id = $id AND c_id=".api_get_course_int_id();
        $res = Database::query($sql);
        $row = Database::fetch_array($res);

        return $row['nb'];
    }

    /**
     * Return an array of all Category objects in the database
     * If $field=="" Return an array of all category objects in the database
     * Otherwise, return an array of all in_field value
     * in the database (in_field = id or name or description)
     *
     * @param string $field
     * @param int $courseId
     * @return array
     */
    public static function getCategoryListInfo($field = '', $courseId = 0)
    {
        $courseId = empty($courseId) ? api_get_course_int_id() : (int) $courseId;

        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
        $field = Database::escape_string($field);
        $categories = array();
        if (empty($field)) {
            $sql = "SELECT id FROM $table
                    WHERE c_id = $courseId 
                    ORDER BY title ASC";
            $res = Database::query($sql);
            while ($row = Database::fetch_array($res)) {
                $category = new TestCategory();
                $categories[] = $category->getCategory($row['id']);
            }
        } else {
            $sql = "SELECT $field FROM $table
                    WHERE c_id = $courseId
                    ORDER BY $field ASC";
            $res = Database::query($sql);
            while ($row = Database::fetch_array($res)) {
                $categories[] = $row[$field];
            }
        }
        return $categories;
    }

    /**
     * Return the TestCategory id for question with question_id = $questionId
     * In this version, a question has only 1 TestCategory.
     * Return the TestCategory id, 0 if none
     * @param int $questionId
     * @param int $courseId
     *
     * @return int
     */
    public static function getCategoryForQuestion($questionId, $courseId = 0)
    {
        $courseId = (int)$courseId;
        if (empty($courseId)) {
            $courseId = api_get_course_int_id();
        }

        if (empty($courseId) || empty($questionId)) {
            return 0;
        }

        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $questionId = intval($questionId);
        $sql = "SELECT category_id
                FROM $table
                WHERE question_id = $questionId AND c_id = $courseId";
        $res = Database::query($sql);
        $result = 0;
        if (Database::num_rows($res) > 0) {
            $data = Database::fetch_array($res);
            $result = (int) $data['category_id'];
        }

        return $result;
    }

    /**
     * true if question id has a category
     *
     * @param int $questionId
     * @return bool
     */
    public static function isQuestionHasCategory($questionId)
    {
        if (self::getCategoryForQuestion($questionId) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Return the category name for question with question_id = $questionId
     * In this version, a question has only 1 category.
     * @param $questionId
     * @param int $courseId
     * @return string
     */
    public static function getCategoryNameForQuestion(
        $questionId,
        $courseId = 0
    ) {
        if (empty($courseId)) {
            $courseId = api_get_course_int_id();
        }
        $courseId = (int) $courseId;
        $categoryId = self::getCategoryForQuestion($questionId, $courseId);
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
        $sql = "SELECT title 
                FROM $table
                WHERE id = $categoryId AND c_id = $courseId";
        $res = Database::query($sql);
        $data = Database::fetch_array($res);
        $result = '';
        if (Database::num_rows($res) > 0) {
            $result = $data['title'];
        }

        return $result;
    }

    /**
     * Return the list of differents categories ID for a test in the current course
     * input : test_id
     * return : array of category id (integer)
     * hubert.borderiou 07-04-2011
     * @param int $exerciseId
     *
     * @return array
     */
    public static function getListOfCategoriesIDForTest($exerciseId)
    {
        // parcourir les questions d'un test, recup les categories uniques dans un tableau
        $exercise = new Exercise();
        $exercise->read($exerciseId, false);
        $categoriesInExercise = $exercise->getQuestionWithCategories();
        // the array given by selectQuestionList start at indice 1 and not at indice 0 !!! ???
        $categories = array();
        if (!empty($categoriesInExercise)) {
            foreach ($categoriesInExercise as $category) {
                $categories[$category['id']] = $category;
            }
        }

        return $categories;
    }

    /**
     * @param Exercise $exercise_obj
     * @return array
     */
    public static function getListOfCategoriesIDForTestObject(Exercise $exercise_obj)
    {
        // parcourir les questions d'un test, recup les categories uniques dans un tableau
        $categories_in_exercise = array();
        $question_list = $exercise_obj->getQuestionOrderedListByName();

        // the array given by selectQuestionList start at indice 1 and not at indice 0 !!! ???
        foreach ($question_list as $questionInfo) {
            $question_id = $questionInfo['question_id'];
            $category_list = self::getCategoryForQuestion($question_id);
            if (is_numeric($category_list)) {
                $category_list = array($category_list);
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
     * Return the list of different categories NAME for a test
     * @param int exercise id
     * @param bool
     * @return integer of string
     *
     * @author function rewrote by jmontoya
     */
    public static function getListOfCategoriesNameForTest($exercise_id, $grouped_by_category = true)
    {
        $result = array();
        $categories = self::getListOfCategoriesIDForTest($exercise_id, $grouped_by_category);

        foreach ($categories as $catInfo) {
            $categoryId = $catInfo['id'];
            if (!empty($categoryId)) {
                $result[$categoryId] = array(
                    'title' => $catInfo['title'],
                    //'parent_id' =>  $catInfo['parent_id'],
                    'parent_id' => '',
                    'c_id' => $catInfo['c_id']
                );
            }
        }

        return $result;
    }

    /**
     * @param Exercise $exercise_obj
     * @return array
     */
    public static function getListOfCategoriesForTest(Exercise $exercise_obj)
    {
        $result = array();
        $categories = self::getListOfCategoriesIDForTestObject($exercise_obj);
        foreach ($categories as $cat_id) {
            $cat = new TestCategory();
            $cat = (array)$cat->getCategory($cat_id);
            $cat['iid'] = $cat['id'];
            $cat['title'] = $cat['name'];
            $result[$cat['id']] = $cat;
        }

        return $result;
    }

    /**
     * return the number of differents categories for a test
     * input : test_id
     * return : integer
     * hubert.borderiou 07-04-2011
     */
    public static function getNumberOfCategoriesForTest($id)
    {
        return count(self::getListOfCategoriesIDForTest($id));
    }

    /**
     * return the number of question of a category id in a test
     * @param int $exerciseId
     * @param int $categoryId
     *
     * @return integer
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
        for ($i=1; $i <= count($questionList); $i++) {
            if (self::getCategoryForQuestion($questionList[$i]) == $categoryId) {
                $nbCatResult++;
            }
        }

        return $nbCatResult;
    }

    /**
     * return the number of question for a test using random by category
     * input  : test_id, number of random question (min 1)
     * hubert.borderiou 07-04-2011
     * question without categories are not counted
     */
    public static function getNumberOfQuestionRandomByCategory($exerciseId, $in_nbrandom)
    {
        $count = 0;
        $categories = self::getListOfCategoriesIDForTest($exerciseId);
        foreach ($categories as $category) {
            if (empty($category['id'])) {
                continue;
            }

            $nbQuestionInThisCat = self::getNumberOfQuestionsInCategoryForTest($exerciseId, $category['id']);

            if ($nbQuestionInThisCat > $in_nbrandom) {
                $count += $in_nbrandom;
            } else {
                $count += $nbQuestionInThisCat;
            }
        }

        return $count;
    }

    /**
     * Return an array (id=>name)
     * tabresult[0] = get_lang('NoCategory');
     *
     * @param int $courseId
     *
     * @return array
     *
     */
    public static function getCategoriesIdAndName($courseId = 0)
    {
        if (empty($courseId)) {
            $courseId = api_get_course_int_id();
        }
        $categories = self::getCategoryListInfo('', $courseId);
        $result = array('0' => get_lang('NoCategorySelected'));
        for ($i = 0; $i < count($categories); $i++) {
            $result[$categories[$i]->id] = $categories[$i]->name;
        }

        return $result;
    }

    /**
     * Returns an array of question ids for each category
     * $categories[1][30] = 10, array with category id = 1 and question_id = 10
     * A question has "n" categories
     * @param int exercise
     * @param array $check_in_question_list
     * @param array $categoriesAddedInExercise
    *
    * @param int $exerciseId
    * @return array
    */
    public static function getQuestionsByCat(
        $exerciseId,
        $check_in_question_list = array(),
        $categoriesAddedInExercise = array()
    ) {
        $tableQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $TBL_QUESTION_REL_CATEGORY = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $categoryTable = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
        $exerciseId = intval($exerciseId);
        $courseId = api_get_course_int_id();

        $sql = "SELECT DISTINCT qrc.question_id, qrc.category_id
                FROM $TBL_QUESTION_REL_CATEGORY qrc
                INNER JOIN $TBL_EXERCICE_QUESTION eq
                ON (eq.question_id = qrc.question_id AND qrc.c_id = eq.c_id)
                INNER JOIN $categoryTable c
                ON (c.id = qrc.category_id AND c.c_id = eq.c_id)
                INNER JOIN $tableQuestion q
                ON (q.id = qrc.question_id AND q.c_id = eq.c_id)
                WHERE
                    exercice_id = $exerciseId AND
                    qrc.c_id = $courseId
                ";

        $res = Database::query($sql);
        $categories = array();
        while ($data = Database::fetch_array($res)) {
            if (!empty($check_in_question_list)) {
                if (!in_array($data['question_id'], $check_in_question_list)) {
                    continue;
                }
            }

            if (!isset($categories[$data['category_id']]) ||
                !is_array($categories[$data['category_id']])
            ) {
                $categories[$data['category_id']] = array();
            }

            $categories[$data['category_id']][] = $data['question_id'];
        }

        if (!empty($categoriesAddedInExercise)) {
            $newCategoryList = array();
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
                $originalQuestionList = $check_in_question_list;
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
     * Returns an array of $numberElements from $array
     * @param array
     * @param int
     *
     * @return array
     */
    public static function getNElementsFromArray($array, $numberElements)
    {
        $list = $array;
        shuffle($list);
        if ($numberElements < count($list)) {
            $list = array_slice($list, 0, $numberElements);
        }

        return $list;
    }

    /**
     * @param int $questionId
     * @param int $in_display_category_name
     */
    public static function displayCategoryAndTitle($questionId, $in_display_category_name = 1)
    {
        echo self::returnCategoryAndTitle($questionId, $in_display_category_name);
    }

    /**
     * @param int $questionId
     * @param int $in_display_category_name
     * @return null|string
     */
    public static function returnCategoryAndTitle($questionId, $in_display_category_name = 1)
    {
        $is_student = !(api_is_allowed_to_edit(null,true) || api_is_session_admin());
        // @todo fix $_SESSION['objExercise']
        $objExercise = Session::read('objExercise');
        if (!empty($objExercise)) {
            $in_display_category_name = $objExercise->display_category_name;
        }
        $content = null;
        if (self::getCategoryNameForQuestion($questionId) != '' &&
            ($in_display_category_name == 1 || !$is_student)
        ) {
            $content .= '<div class="page-header">';
            $content .= '<h4>'.get_lang('Category').": ".self::getCategoryNameForQuestion($questionId).'</h4>';
            $content .= "</div>";
        }
        return $content;
    }

    /**
    * Display signs [+] and/or (>0) after question title if question has options
    * scoreAlwaysPositive and/or uncheckedMayScore
    */
    public function displayQuestionOption($in_objQuestion)
    {
        if ($in_objQuestion->type == MULTIPLE_ANSWER && $in_objQuestion->scoreAlwaysPositive) {
            echo "<span style='font-size:75%'> (>0)</span>";
        }
        if ($in_objQuestion->type == MULTIPLE_ANSWER && $in_objQuestion->uncheckedMayScore) {
            echo "<span style='font-size:75%'> [+]</span>";
        }
    }

    /**
     * sortTabByBracketLabel ($tabCategoryQuestions)
     * key of $tabCategoryQuestions are the category id (0 for not in a category)
     * value is the array of question id of this category
     * Sort question by Category
    */
    public static function sortTabByBracketLabel($in_tab)
    {
        $tabResult = array();
        $tabCatName = array();	// tab of category name
        while (list($cat_id, $tabquestion) = each($in_tab)) {
            $category = new TestCategory();
            $category = $category->getCategory($cat_id);
            $tabCatName[$cat_id] = $category->name;
        }
        reset($in_tab);
        // sort table by value, keeping keys as they are
        asort($tabCatName);
        // keys of $tabCatName are keys order for $in_tab
        while (list($key, $val) = each($tabCatName)) {
            $tabResult[$key] = $in_tab[$key];
        }

        return $tabResult;
    }

    /**
     * return total score for test exe_id for all question in the category $in_cat_id for user
     * If no question for this category, return ""
     */
    public static function getCatScoreForExeidForUserid($in_cat_id, $in_exe_id, $in_user_id)
    {
        $tbl_track_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $tbl_question_rel_category = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $in_cat_id = intval($in_cat_id);
        $in_exe_id = intval($in_exe_id);
        $in_user_id = intval($in_user_id);

        $query = "SELECT DISTINCT
                        marks, 
                        exe_id, 
                        user_id, 
                        ta.question_id, 
                        category_id
                  FROM $tbl_track_attempt ta 
                  INNER JOIN $tbl_question_rel_category qrc
                  ON (ta.question_id=qrc.question_id)
                  WHERE
                    qrc.category_id = $in_cat_id AND
                    exe_id = $in_exe_id AND 
                    user_id = $in_user_id";
        $res = Database::query($query);
        $score = '';
        while ($data = Database::fetch_array($res)) {
            $score += $data['marks'];
        }

        return $score;
    }

    /**
     * Return the number max of question in a category
     * count the number of questions in all categories, and return the max
     * @param int $exerciseId
     * @author - hubert borderiou
     * @return int
    */
    public static function getNumberMaxQuestionByCat($exerciseId)
    {
        $res_num_max = 0;
        // foreach question
        $categories = self::getListOfCategoriesIDForTest($exerciseId);

        foreach ($categories as $category) {
            if (empty($category['id'])) {
                continue;
            }

            $nbQuestionInThisCat = self::getNumberOfQuestionsInCategoryForTest($exerciseId, $category['id']);

            if ($nbQuestionInThisCat > $res_num_max) {
                $res_num_max = $nbQuestionInThisCat;
            }
        }

        return $res_num_max;
    }

    /**
     * Returns a category summary report
     * @params int $exerciseId
     * @params array pre filled array with the category_id, score, and weight
     * example: array(1 => array('score' => '10', 'total' => 20));
     *
     * @return string
     */
    public static function get_stats_table_by_attempt($exerciseId, $category_list = array())
    {
        if (empty($category_list)) {
            return null;
        }
        $category_name_list = self::getListOfCategoriesNameForTest($exerciseId);

        $table = new HTML_Table(array('class' => 'data_table'));
        $table->setHeaderContents(0, 0, get_lang('Categories'));
        $table->setHeaderContents(0, 1, get_lang('AbsoluteScore'));
        $table->setHeaderContents(0, 2, get_lang('RelativeScore'));
        $row = 1;

        $none_category = array();
        if (isset($category_list['none'])) {
            $none_category = $category_list['none'];
            unset($category_list['none']);
        }

        $total = array();
        if (isset($category_list['total'])) {
            $total = $category_list['total'];
            unset($category_list['total']);
        }
        if (count($category_list) > 1) {
            foreach ($category_list as $category_id => $category_item) {
                $table->setCellContents($row, 0, $category_name_list[$category_id]);
                $table->setCellContents($row, 1, ExerciseLib::show_score($category_item['score'], $category_item['total'], false));
                $table->setCellContents($row, 2, ExerciseLib::show_score($category_item['score'], $category_item['total'], true, false, true));
                $row++;
            }

            if (!empty($none_category)) {
                $table->setCellContents($row, 0, get_lang('None'));
                $table->setCellContents($row, 1, ExerciseLib::show_score($none_category['score'], $none_category['total'], false));
                $table->setCellContents($row, 2, ExerciseLib::show_score($none_category['score'], $none_category['total'], true, false, true));
                $row++;
            }
            if (!empty($total)) {
                $table->setCellContents($row, 0, get_lang('Total'));
                $table->setCellContents($row, 1, ExerciseLib::show_score($total['score'], $total['total'], false));
                $table->setCellContents($row, 2, ExerciseLib::show_score($total['score'], $total['total'], true, false, true));
            }
            return $table->toHtml();
        }

        return '';
    }

    /**
     * @return array
     */
    public static function get_all_categories()
    {
        $table = Database::get_course_table(TABLE_QUIZ_CATEGORY);
        $sql = "SELECT * FROM $table ORDER BY title ASC";
        $res = Database::query($sql);
        $array = [];
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $array[] = $row;
        }

        return $array;
    }

    /**
     * @param Exercise $exercise
     * @param int $courseId
     * @param string $order
     * @param bool $shuffle
     * @param bool $excludeCategoryWithNoQuestions
     * @return array|bool
     */
    public function getCategoryExerciseTree(
        $exercise,
        $courseId,
        $order = null,
        $shuffle = false,
        $excludeCategoryWithNoQuestions = true
    ) {
        if (empty($exercise)) {
            return array();
        }

        $courseId = (int) $courseId;
        $table = Database::get_course_table(TABLE_QUIZ_REL_CATEGORY);
        $categoryTable = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
        $sql = "SELECT * FROM $table qc
                LEFT JOIN $categoryTable c
                ON (qc.c_id = c.c_id AND c.id = qc.category_id)
                WHERE qc.c_id = $courseId AND exercise_id = {$exercise->id} ";

        if (!empty($order)) {
            $sql .= "ORDER BY $order";
        }

        $categories = array();

        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                if ($excludeCategoryWithNoQuestions) {
                    if ($row['count_questions'] == 0) {
                        continue;
                    }
                }
                if (empty($row['title']) && empty($row['category_id'])) {
                    $row['title'] = get_lang('NoCategory');
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
     * @param $form
     * @param string $action
     */
    public function getForm(& $form, $action = 'new')
    {
        switch ($action) {
            case 'new':
                $header = get_lang('AddACategory');
                $submit = get_lang('AddTestCategory');
                break;
            case 'edit':
                $header = get_lang('EditCategory');
                $submit = get_lang('ModifyCategory');
                break;
        }

        // Setting the form elements
        $form->addElement('header', $header);
        $form->addElement('hidden', 'category_id');
        $form->addElement('text', 'category_name', get_lang('CategoryName'), array('class' => 'span6'));
        $form->add_html_editor(
            'category_description',
            get_lang('CategoryDescription'),
            false,
            false,
            array(
                'ToolbarSet' => 'test_category',
                'Width' => '90%',
                'Height' => '200',
            )
        );
        $category_parent_list = array();

        $options = array(
                '1' => get_lang('Visible'),
                '0' => get_lang('Hidden')
        );
        $form->addElement('select', 'visibility', get_lang('Visibility'), $options);
        $script = null;
        if (!empty($this->parent_id)) {
            $parent_cat = new TestCategory();
            $parent_cat = $parent_cat->getCategory($this->parent_id);
            $category_parent_list = array($parent_cat->id => $parent_cat->name);
            $script .= '<script>$(function() { $("#parent_id").trigger("addItem",[{"title": "'.$parent_cat->name.'", "value": "'.$parent_cat->id.'"}]); });</script>';
        }
        $form->addElement('html', $script);

        $form->addElement('select', 'parent_id', get_lang('Parent'), $category_parent_list, array('id' => 'parent_id'));
        $form->addElement('style_submit_button', 'SubmitNote', $submit, 'class="add"');

        // setting the defaults
        $defaults = array();
        $defaults["category_id"] = $this->id;
        $defaults["category_name"] = $this->name;
        $defaults["category_description"] = $this->description;
        $defaults["parent_id"] = $this->parent_id;
        $defaults["visibility"] = $this->visibility;
        $form->setDefaults($defaults);

        // setting the rules
        $form->addRule('category_name', get_lang('ThisFieldIsRequired'), 'required');
    }

    /**
     * Returns the category form.
     * @param Exercise $exercise_obj
     * @return string
     */
    public function returnCategoryForm(Exercise $exercise_obj)
    {
        $categories = $this->getListOfCategoriesForTest($exercise_obj);
        $saved_categories = $exercise_obj->get_categories_in_exercise();
        $return = null;

        if (!empty($categories)) {
            $nbQuestionsTotal = $exercise_obj->getNumberQuestionExerciseCategory();
            $exercise_obj->setCategoriesGrouping(true);
            $real_question_count = count($exercise_obj->getQuestionList());

            $warning = null;
            if ($nbQuestionsTotal != $real_question_count) {
                $warning = Display::return_message(
                    get_lang('CheckThatYouHaveEnoughQuestionsInYourCategories'),
                    'warning'
                );
            }

            $return .= $warning;
            $return .= '<table class="data_table">';
            $return .= '<tr>';
            $return .= '<th height="24">' . get_lang('Categories') . '</th>';
            $return .= '<th width="70" height="24">' . get_lang('Number') . '</th></tr>';

            $emptyCategory = array(
                'id' => '0',
                'name' => get_lang('NoCategory'),
                'description' => '',
                'iid' => '0',
                'title' => get_lang('NoCategory')
            );

            $categories[] = $emptyCategory;

            foreach ($categories as $category) {
                $cat_id = $category['iid'];
                $return .= '<tr>';
                $return .= '<td>';
                //$return .= Display::div(isset($category['parent_path']) ? $category['parent_path'] : '');
                $return .= Display::div($category['name']);
                $return .= '</td>';
                $return .= '<td>';
                $value = isset($saved_categories) && isset($saved_categories[$cat_id]) ? $saved_categories[$cat_id]['count_questions'] : -1;
                $return .= '<input name="category['.$cat_id.']" value="' .$value.'" />';
                $return .= '</td>';
                $return .= '</tr>';
            }

            $return .= '</table>';
            $return .= get_lang('ZeroMeansNoQuestionWillBeSelectedMinusOneMeansThatAllQuestionsWillBeSelected');
        }
        return $return;
    }

    /**
     * Sorts an array
     * @param $array
     * @return mixed
     */
    public function sort_tree_array($array)
    {
        foreach ($array as $key => $row) {
            $parent[$key] = $row['parent_id'];
        }
        if (count($array) > 0) {
            array_multisort($parent, SORT_ASC, $array);
        }
        return $array;
    }

    /**
     * Return true if a category already exists with the same name
     * @param string $name
     * @param int $courseId
     *
     * @return bool
     */
    public static function categoryTitleExists($name, $courseId = 0)
    {
        $categories = self::getCategoryListInfo('title', $courseId);
        foreach ($categories as $title) {
            if ($title == $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the id of the test category with title = $in_title
     * @param string $title
     * @param int $courseId
     *
     * @return int is id of test category
     */
    public static function get_category_id_for_title($title, $courseId = 0)
    {
        $out_res = 0;
        if (empty($courseId)) {
            $courseId = api_get_course_int_id();
        }
        $courseId = intval($courseId);
        $tbl_cat = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
        $sql = "SELECT id FROM $tbl_cat
                WHERE c_id = $courseId AND title = '".Database::escape_string($title)."'";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $data = Database::fetch_array($res);
            $out_res = $data['id'];
        }
        return $out_res;
    }

    /**
     * Add a relation between question and category in table c_quiz_question_rel_category
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
        if (self::getCategoryForQuestion($questionId, $courseId) == 0 &&
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
     * @return array
     */
    public function getCategories($courseId, $sessionId = 0)
    {
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
        $itemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $sessionId = intval($sessionId);
        $courseId = intval($courseId);

        if (empty($sessionId)) {
            $sessionCondition = api_get_session_condition(
                $sessionId,
                true,
                false,
                'i.session_id'
            );
        } else {
            $sessionCondition = api_get_session_condition(
                $sessionId,
                true,
                true,
                'i.session_id'
            );
        }

        if (empty($courseId)) {
            return array();
        }

        $sql = "SELECT c.* FROM $table c
                INNER JOIN $itemProperty i
                ON c.c_id = i.c_id AND i.ref = c.id
                WHERE
                    c.c_id = $courseId AND
                    i.tool = '".TOOL_TEST_CATEGORY."'
                    $sessionCondition
                ORDER BY title";
        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param int $courseId
     * @param int $sessionId
     * @return string
     */
    public function displayCategories($courseId, $sessionId = 0)
    {
        $categories = $this->getCategories($courseId, $sessionId);
        $html = '';
        foreach ($categories as $category) {
            $tmpobj = new TestCategory();
            $tmpobj = $tmpobj->getCategory($category['id']);
            $nb_question = $tmpobj->getCategoryQuestionsNumber();
            $rowname = self::protectJSDialogQuote($category['title']);
            $nb_question_label = $nb_question == 1 ? $nb_question . ' ' . get_lang('Question') : $nb_question . ' ' . get_lang('Questions');
            $content = "<span style='float:right'>" . $nb_question_label . "</span>";
            $content .= '<div class="sectioncomment">';
            $content .= $category['description'];
            $content .= '</div>';
            $links = '<a href="' . api_get_self() . '?action=editcategory&category_id=' . $category['id'] . '&'.api_get_cidreq().'">' .
                Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL) . '</a>';
            $links .= ' <a href="' . api_get_self() . '?'.api_get_cidreq().'&action=deletecategory&category_id=' . $category['id'] . '" ';
            $links .= 'onclick="return confirmDelete(\'' . self::protectJSDialogQuote(get_lang('DeleteCategoryAreYouSure') . '[' . $rowname) . '] ?\', \'id_cat' . $category['id'] . '\');">';
            $links .= Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL) . '</a>';
            $html .= Display::panel($content, $category['title'].$links);
        }

        return $html;
    }

    // To allowed " in javascript dialog box without bad surprises
    // replace " with two '
    public function protectJSDialogQuote($in_txt)
    {
        $res = $in_txt;
        $res = str_replace("'", "\'", $res);
        $res = str_replace('"', "\'\'", $res); // super astuce pour afficher les " dans les boite de dialogue
        return $res;
    }
}
