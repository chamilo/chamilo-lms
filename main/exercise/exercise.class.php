<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class Exercise
 *
 * Allows to instantiate an object of type Exercise
 * @package chamilo.exercise
 * @todo use doctrine object
 * @author Olivier Brouckaert
 * @author Julio Montoya Cleaning exercises
 * Modified by Hubert Borderiou #294
 */
class Exercise
{
    public $id;
    public $name;
    public $title;
    public $exercise;
    public $description;
    public $sound;
    public $type; //ALL_ON_ONE_PAGE or ONE_PER_PAGE
    public $random;
    public $random_answers;
    public $active;
    public $timeLimit;
    public $attempts;
    public $feedback_type;
    public $end_time;
    public $start_time;
    public $questionList;  // array with the list of this exercise's questions
    /* including question list of the media */
    public $questionListUncompressed;
    public $results_disabled;
    public $expired_time;
    public $course;
    public $course_id;
    public $propagate_neg;
    public $saveCorrectAnswers;
    public $review_answers;
    public $randomByCat;
    public $text_when_finished;
    public $display_category_name;
    public $pass_percentage;
    public $edit_exercise_in_lp = false;
    public $is_gradebook_locked = false;
    public $exercise_was_added_in_lp = false;
    public $lpList = array();
    public $force_edit_exercise_in_lp = false;
    public $categories;
    public $categories_grouping = true;
    public $endButton = 0;
    public $categoryWithQuestionList;
    public $mediaList;
    public $loadQuestionAJAX = false;
    // Notification send to the teacher.
    public $emailNotificationTemplate = null;
    // Notification send to the student.
    public $emailNotificationTemplateToUser = null;
    public $countQuestions = 0;
    public $fastEdition = false;
    public $modelType = 1;
    public $questionSelectionType = EX_Q_SELECTION_ORDERED;
    public $hideQuestionTitle = 0;
    public $scoreTypeModel = 0;
    public $categoryMinusOne = true; // Shows the category -1: See BT#6540
    public $globalCategoryId = null;
    public $onSuccessMessage = null;
    public $onFailedMessage = null;
    public $emailAlert;
    public $notifyUserByEmail = '';
    public $sessionId = 0;

    /**
     * Constructor of the class
     *
     * @author Olivier Brouckaert
     */
    public function __construct($course_id = null)
    {
        $this->id = 0;
        $this->exercise = '';
        $this->description = '';
        $this->sound = '';
        $this->type = ALL_ON_ONE_PAGE;
        $this->random = 0;
        $this->random_answers = 0;
        $this->active = 1;
        $this->questionList = array();
        $this->timeLimit = 0;
        $this->end_time = '';
        $this->start_time = '';
        $this->results_disabled = 1;
        $this->expired_time = 0;
        $this->propagate_neg = 0;
        $this->saveCorrectAnswers = 0;
        $this->review_answers = false;
        $this->randomByCat = 0;
        $this->text_when_finished = '';
        $this->display_category_name = 0;
        $this->pass_percentage = '';

        $this->modelType = 1;
        $this->questionSelectionType = EX_Q_SELECTION_ORDERED;
        $this->endButton = 0;
        $this->scoreTypeModel = 0;
        $this->globalCategoryId = null;

        if (!empty($course_id)) {
            $course_info = api_get_course_info_by_id($course_id);
        } else {
            $course_info = api_get_course_info();
        }
        $this->course_id = $course_info['real_id'];
        $this->course = $course_info;
    }

    /**
     * Reads exercise information from the data base
     *
     * @author Olivier Brouckaert
     * @param integer $id - exercise Id
     *
     * @return boolean - true if exercise exists, otherwise false
     */
    public function read($id, $parseQuestionList = true)
    {
        $TBL_EXERCISES = Database::get_course_table(TABLE_QUIZ_TEST);
        $table_lp_item = Database::get_course_table(TABLE_LP_ITEM);

        $id  = intval($id);
        if (empty($this->course_id)) {

            return false;
        }
        $sql = "SELECT * FROM $TBL_EXERCISES 
                WHERE c_id = ".$this->course_id." AND id = ".$id;
        $result = Database::query($sql);

        // if the exercise has been found
        if ($object = Database::fetch_object($result)) {
            $this->id = $id;
            $this->exercise = $object->title;
            $this->name = $object->title;
            $this->title = $object->title;
            $this->description = $object->description;
            $this->sound = $object->sound;
            $this->type = $object->type;
            if (empty($this->type)) {
                $this->type = ONE_PER_PAGE;
            }
            $this->random = $object->random;
            $this->random_answers = $object->random_answers;
            $this->active = $object->active;
            $this->results_disabled = $object->results_disabled;
            $this->attempts = $object->max_attempt;
            $this->feedback_type = $object->feedback_type;
            $this->propagate_neg = $object->propagate_neg;
            $this->saveCorrectAnswers = $object->save_correct_answers;
            $this->randomByCat = $object->random_by_category;
            $this->text_when_finished = $object->text_when_finished;
            $this->display_category_name = $object->display_category_name;
            $this->pass_percentage = $object->pass_percentage;
            $this->sessionId = $object->session_id;
            $this->is_gradebook_locked = api_resource_is_locked_by_gradebook($id, LINK_EXERCISE);
            $this->review_answers = (isset($object->review_answers) && $object->review_answers == 1) ? true : false;
            $this->globalCategoryId = isset($object->global_category_id) ? $object->global_category_id : null;
            $this->questionSelectionType = isset($object->question_selection_type) ? $object->question_selection_type : null;

            $sql = "SELECT lp_id, max_score
                    FROM $table_lp_item
                    WHERE   c_id = {$this->course_id} AND
                            item_type = '".TOOL_QUIZ."' AND
                            path = '".$id."'";
            $result = Database::query($sql);

            if (Database::num_rows($result) > 0) {
                $this->exercise_was_added_in_lp = true;
                $this->lpList = Database::store_result($result, 'ASSOC');
            }

            $this->force_edit_exercise_in_lp = api_get_configuration_value('force_edit_exercise_in_lp');

            if ($this->exercise_was_added_in_lp) {
                $this->edit_exercise_in_lp = $this->force_edit_exercise_in_lp == true;
            } else {
                $this->edit_exercise_in_lp = true;
            }

            if (!empty($object->end_time)) {
                $this->end_time = $object->end_time;
            }
            if (!empty($object->start_time)) {
                $this->start_time = $object->start_time;
            }

            // Control time
            $this->expired_time = $object->expired_time;

            // Checking if question_order is correctly set
            if ($parseQuestionList) {
                $this->setQuestionList();
            }

            //overload questions list with recorded questions list
            //load questions only for exercises of type 'one question per page'
            //this is needed only is there is no questions
            /*
			// @todo not sure were in the code this is used somebody mess with the exercise tool
			// @todo don't know who add that config and why $_configuration['live_exercise_tracking']
			global $_configuration, $questionList;
			if ($this->type == ONE_PER_PAGE && $_SERVER['REQUEST_METHOD'] != 'POST' && defined('QUESTION_LIST_ALREADY_LOGGED') &&
			isset($_configuration['live_exercise_tracking']) && $_configuration['live_exercise_tracking']) {
				$this->questionList = $questionList;
			}*/
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getCutTitle()
    {
        return cut($this->exercise, EXERCISE_MAX_NAME_SIZE);
    }

    /**
     * returns the exercise ID
     *
     * @author Olivier Brouckaert
     * @return int - exercise ID
     */
    public function selectId()
    {
        return $this->id;
    }

    /**
     * returns the exercise title
     *
     * @author Olivier Brouckaert
     * @return string - exercise title
     */
    public function selectTitle()
    {
        return $this->exercise;
    }

    /**
     * returns the number of attempts setted
     *
     * @return int - exercise attempts
     */
    public function selectAttempts()
    {
        return $this->attempts;
    }

    /** returns the number of FeedbackType  *
     *  0=>Feedback , 1=>DirectFeedback, 2=>NoFeedback
     * @return int - exercise attempts
     */
    public function selectFeedbackType()
    {
        return $this->feedback_type;
    }

    /**
     * returns the time limit
     */
    public function selectTimeLimit()
    {
        return $this->timeLimit;
    }

    /**
     * returns the exercise description
     *
     * @author Olivier Brouckaert
     * @return string - exercise description
     */
    public function selectDescription()
    {
        return $this->description;
    }

    /**
     * returns the exercise sound file
     *
     * @author Olivier Brouckaert
     * @return string - exercise description
     */
    public function selectSound()
    {
        return $this->sound;
    }

    /**
     * returns the exercise type
     *
     * @author Olivier Brouckaert
     * @return integer - exercise type
     */
    public function selectType()
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getModelType()
    {
        return $this->modelType;
    }

    /**
     * @return int
     */
    public function selectEndButton()
    {
        return $this->endButton;
    }

    /**
     * @return string
     */
    public function getOnSuccessMessage()
    {
        return $this->onSuccessMessage;
    }

    /**
     * @return string
     */
    public function getOnFailedMessage()
    {
        return $this->onFailedMessage;
    }

    /**
     * @author hubert borderiou 30-11-11
     * @return integer : do we display the question category name for students
     */
    public function selectDisplayCategoryName()
    {
        return $this->display_category_name;
    }

    /**
     * @return int
     */
    public function selectPassPercentage()
    {
        return $this->pass_percentage;
    }

    /**
     *
     * Modify object to update the switch display_category_name
     * @author hubert borderiou 30-11-11
     * @param int $in_txt is an integer 0 or 1
     */
    public function updateDisplayCategoryName($in_txt)
    {
        $this->display_category_name = $in_txt;
    }

    /**
     * @author hubert borderiou 28-11-11
     * @return string html text : the text to display ay the end of the test.
     */
    public function selectTextWhenFinished()
    {
        return $this->text_when_finished;
    }

    /**
     * @author hubert borderiou 28-11-11
     * @return string  html text : update the text to display ay the end of the test.
     */
    public function updateTextWhenFinished($in_txt)
    {
        $this->text_when_finished = $in_txt;
    }

    /**
     * return 1 or 2 if randomByCat
     * @author hubert borderiou
     * @return integer - quiz random by category
     */
    public function selectRandomByCat()
    {
        return $this->randomByCat;
    }

    /**
     * return 0 if no random by cat
     * return 1 if random by cat, categories shuffled
     * return 2 if random by cat, categories sorted by alphabetic order
     * @author hubert borderiou
     * @return integer - quiz random by category
     */
    public function isRandomByCat()
    {
        /*$res = 0;
        if ($this->randomByCat == 1) {
            $res = 1;
        } else if ($this->randomByCat == 2) {
            $res = 2;
        }
        */

        $res = EXERCISE_CATEGORY_RANDOM_DISABLED;
        if ($this->randomByCat == EXERCISE_CATEGORY_RANDOM_SHUFFLED) {
            $res = EXERCISE_CATEGORY_RANDOM_SHUFFLED;
        } else if ($this->randomByCat == EXERCISE_CATEGORY_RANDOM_ORDERED) {
            $res = EXERCISE_CATEGORY_RANDOM_ORDERED;
        }

        return $res;
    }

    /**
     * return nothing
     * update randomByCat value for object
     * @param int $random
     *
     * @author hubert borderiou
     */
    public function updateRandomByCat($random)
    {
        if (in_array($random, array(
                EXERCISE_CATEGORY_RANDOM_SHUFFLED,
                EXERCISE_CATEGORY_RANDOM_ORDERED,
                EXERCISE_CATEGORY_RANDOM_DISABLED
            )
        )) {
            $this->randomByCat = $random;
        } else {
            $this->randomByCat = EXERCISE_CATEGORY_RANDOM_DISABLED;
        }
    }

    /**
     * Tells if questions are selected randomly, and if so returns the draws
     *
     * @author Carlos Vargas
     * @return integer - results disabled exercise
     */
    public function selectResultsDisabled()
    {
        return $this->results_disabled;
    }

    /**
     * tells if questions are selected randomly, and if so returns the draws
     *
     * @author Olivier Brouckaert
     * @return integer - 0 if not random, otherwise the draws
     */
    public function isRandom()
    {
        if($this->random > 0 || $this->random == -1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * returns random answers status.
     *
     * @author Juan Carlos Rana
     */
    public function selectRandomAnswers()
    {
        return $this->random_answers;
    }

    /**
     * Same as isRandom() but has a name applied to values different than 0 or 1
     */
    public function getShuffle()
    {
        return $this->random;
    }

    /**
     * returns the exercise status (1 = enabled ; 0 = disabled)
     *
     * @author Olivier Brouckaert
     * @return boolean - true if enabled, otherwise false
     */
    public function selectStatus()
    {
        return $this->active;
    }

    /**
     * If false the question list will be managed as always if true the question will be filtered
     * depending of the exercise settings (table c_quiz_rel_category)
     * @param bool active or inactive grouping
     **/
    public function setCategoriesGrouping($status)
    {
        $this->categories_grouping = (bool) $status;
    }

    /**
     * @return int
     */
    public function getHideQuestionTitle()
    {
        return $this->hideQuestionTitle;
    }

    /**
     * @param $value
     */
    public function setHideQuestionTitle($value)
    {
        $this->hideQuestionTitle = intval($value);
    }

    /**
     * @return int
     */
    public function getScoreTypeModel()
    {
        return $this->scoreTypeModel;
    }

    /**
     * @param int $value
     */
    public function setScoreTypeModel($value)
    {
        $this->scoreTypeModel = intval($value);
    }

    /**
     * @return int
     */
    public function getGlobalCategoryId()
    {
        return $this->globalCategoryId;
    }

    /**
     * @param int $value
     */
    public function setGlobalCategoryId($value)
    {
        if (is_array($value) && isset($value[0])) {
            $value = $value[0];
        }
        $this->globalCategoryId = intval($value);
    }

    /**
     *
     * @param int $start
     * @param int $limit
     * @param int $sidx
     * @param string $sord
     * @param array $where_condition
     * @param array $extraFields
     */
    public function getQuestionListPagination($start, $limit, $sidx, $sord, $where_condition = array(), $extraFields = array())
    {
        if (!empty($this->id)) {
            $category_list = TestCategory::getListOfCategoriesNameForTest($this->id, false);
            $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
            $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);

            $sql = "SELECT q.iid
                    FROM $TBL_EXERCICE_QUESTION e INNER JOIN $TBL_QUESTIONS  q
                        ON (e.question_id = q.iid AND e.c_id = ".$this->course_id." )
					WHERE e.exercice_id	= '".Database::escape_string($this->id)."'
					";

            $orderCondition = "ORDER BY question_order";

            if (!empty($sidx) && !empty($sord)) {
                if ($sidx == 'question') {

                    if (in_array(strtolower($sord), array('desc', 'asc'))) {
                        $orderCondition = " ORDER BY q.$sidx $sord";
                    }
                }
            }

            $sql .= $orderCondition;

            $limitCondition = null;

            if (isset($start) && isset($limit)) {
                $start = intval($start);
                $limit = intval($limit);
                $limitCondition = " LIMIT $start, $limit";
            }
            $sql .= $limitCondition;
            $result = Database::query($sql);
            $questions = array();
            if (Database::num_rows($result)) {
                if (!empty($extraFields)) {
                    $extraFieldValue = new ExtraFieldValue('question');
                }
                while ($question = Database::fetch_array($result, 'ASSOC')) {
                    /** @var Question $objQuestionTmp */
                    $objQuestionTmp = Question::read($question['iid']);
                    $category_labels = TestCategory::return_category_labels($objQuestionTmp->category_list, $category_list);

                    if (empty($category_labels)) {
                        $category_labels = "-";
                    }

                    // Question type
                    list($typeImg, $typeExpl) = $objQuestionTmp->get_type_icon_html();

                    $question_media = null;
                    if (!empty($objQuestionTmp->parent_id)) {
                        $objQuestionMedia = Question::read($objQuestionTmp->parent_id);
                        $question_media  = Question::getMediaLabel($objQuestionMedia->question);
                    }

                    $questionType = Display::tag('div', Display::return_icon($typeImg, $typeExpl, array(), ICON_SIZE_MEDIUM).$question_media);

                    $question = array(
                        'id' => $question['iid'],
                        'question' => $objQuestionTmp->selectTitle(),
                        'type' => $questionType,
                        'category' => Display::tag('div', '<a href="#" style="padding:0px; margin:0px;">'.$category_labels.'</a>'),
                        'score' => $objQuestionTmp->selectWeighting(),
                        'level' => $objQuestionTmp->level
                    );
                    if (!empty($extraFields)) {
                        foreach ($extraFields as $extraField) {
                            $value = $extraFieldValue->get_values_by_handler_and_field_id($question['id'], $extraField['id']);
                            $stringValue = null;
                            if ($value) {
                                $stringValue = $value['field_value'];
                            }
                            $question[$extraField['field_variable']] = $stringValue;
                        }
                    }
                    $questions[] = $question;
                }
            }
            return $questions;
        }
    }

    /**
     * Get question count per exercise from DB (any special treatment)
     * @return int
     */
    public function getQuestionCount()
    {
        $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $sql = "SELECT count(q.id) as count
                FROM $TBL_EXERCICE_QUESTION e INNER JOIN $TBL_QUESTIONS q
                    ON (e.question_id = q.id)
                WHERE e.c_id = {$this->course_id} AND e.exercice_id	= ".Database::escape_string($this->id);
        $result = Database::query($sql);

        $count = 0;
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result);
            $count = $row['count'];
        }

        return $count;
    }

    /**
     * @return array
     */
    public function getQuestionOrderedListByName()
    {
        $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);

        // Getting question list from the order (question list drag n drop interface ).
        $sql = "SELECT e.question_id
                FROM $TBL_EXERCICE_QUESTION e INNER JOIN $TBL_QUESTIONS q
                    ON (e.question_id= q.id)
                WHERE e.c_id = {$this->course_id} AND e.exercice_id	= '".Database::escape_string($this->id)."'
                ORDER BY q.question";
        $result = Database::query($sql);
        $list = array();
        if (Database::num_rows($result)) {
            $list = Database::store_result($result, 'ASSOC');
        }
        return $list;
    }

    /**
     * Gets the question list ordered by the question_order setting (drag and drop)
     * @return array
     */
    private function getQuestionOrderedList()
    {
        $questionList = array();

        $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);

        // Getting question_order to verify that the question
        // list is correct and all question_order's were set
        $sql = "SELECT DISTINCT e.question_order
                FROM $TBL_EXERCICE_QUESTION e
                INNER JOIN $TBL_QUESTIONS q
                    ON (e.question_id = q.id)
                WHERE
                  e.c_id = {$this->course_id} AND
                  e.exercice_id	= ".Database::escape_string($this->id);

        $result = Database::query($sql);

        $count_question_orders = Database::num_rows($result);

        // Getting question list from the order (question list drag n drop interface ).
        $sql = "SELECT DISTINCT e.question_id, e.question_order
                FROM $TBL_EXERCICE_QUESTION e
                INNER JOIN $TBL_QUESTIONS q
                    ON (e.question_id= q.id)
                WHERE
                    e.c_id = {$this->course_id} AND
                    e.exercice_id	= '".Database::escape_string($this->id)."'
                ORDER BY question_order";

        $result = Database::query($sql);

        // Fills the array with the question ID for this exercise
        // the key of the array is the question position
        $temp_question_list = array();

        $counter = 1;
        while ($new_object = Database::fetch_object($result)) {
            // Correct order.
            $questionList[$new_object->question_order] = $new_object->question_id;
            // Just in case we save the order in other array
            $temp_question_list[$counter] = $new_object->question_id;
            $counter++;
        }

        if (!empty($temp_question_list)) {
            /* If both array don't match it means that question_order was not correctly set
               for all questions using the default mysql order */
            if (count($temp_question_list) != $count_question_orders) {
                $questionList = $temp_question_list;
            }
        }

        return $questionList;
    }

    /**
     * Select N values from the questions per category array
     *
     * @param array $categoriesAddedInExercise
     * @param array $question_list
     * @param array $questions_by_category per category
     * @param bool $flatResult
     * @param bool $randomizeQuestions
     *
     * @return array
     */
    private function pickQuestionsPerCategory(
        $categoriesAddedInExercise,
        $question_list,
        & $questions_by_category,
        $flatResult = true,
        $randomizeQuestions = false
    ) {
        $addAll = true;
        $categoryCountArray = array();

        // Getting how many questions will be selected per category.
        if (!empty($categoriesAddedInExercise)) {
            $addAll = false;
            // Parsing question according the category rel exercise settings
            foreach ($categoriesAddedInExercise as $category_info) {
                $category_id = $category_info['category_id'];
                if (isset($questions_by_category[$category_id])) {
                    // How many question will be picked from this category.
                    $count = $category_info['count_questions'];
                    // -1 means all questions
                    if ($count == -1) {
                        $categoryCountArray[$category_id] = 999;
                    } else {
                        $categoryCountArray[$category_id] = $count;
                    }
                }
            }
        }

        if (!empty($questions_by_category)) {
            $temp_question_list = array();

            foreach ($questions_by_category as $category_id => & $categoryQuestionList) {
                if (isset($categoryCountArray) && !empty($categoryCountArray)) {
                    if (isset($categoryCountArray[$category_id])) {
                        $numberOfQuestions = $categoryCountArray[$category_id];
                    } else {
                        $numberOfQuestions = 0;
                    }
                }

                if ($addAll) {
                    $numberOfQuestions = 999;
                }

                if (!empty($numberOfQuestions)) {
                    $elements = TestCategory::getNElementsFromArray(
                        $categoryQuestionList,
                        $numberOfQuestions,
                        $randomizeQuestions
                    );

                    if (!empty($elements)) {
                        $temp_question_list[$category_id] = $elements;
                        $categoryQuestionList = $elements;
                    }
                }
            }

            if (!empty($temp_question_list)) {
                if ($flatResult) {
                    $temp_question_list = array_flatten($temp_question_list);
                }
                $question_list = $temp_question_list;
            }
        }

        return $question_list;
    }

    /**
     * Selecting question list depending in the exercise-category
     * relationship (category table in exercise settings)
     *
     * @param array $question_list
     * @param int $questionSelectionType
     * @return array
     */
    public function getQuestionListWithCategoryListFilteredByCategorySettings($question_list, $questionSelectionType)
    {
        $result = array(
            'question_list' => array(),
            'category_with_questions_list' => array()
        );

        // Order/random categories
        $cat = new TestCategory();

        // Setting category order.

        switch ($questionSelectionType) {
            case EX_Q_SELECTION_ORDERED: // 1
            case EX_Q_SELECTION_RANDOM:  // 2
                // This options are not allowed here.
                break;
            case EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED: // 3
                $categoriesAddedInExercise = $cat->getCategoryExerciseTree(
                    $this,
                    $this->course['real_id'],
                    'title ASC',
                    false,
                    true
                );

                $questions_by_category = TestCategory::getQuestionsByCat(
                    $this->id,
                    $question_list,
                    $categoriesAddedInExercise
                );


                $question_list = $this->pickQuestionsPerCategory(
                    $categoriesAddedInExercise,
                    $question_list,
                    $questions_by_category,
                    true,
                    false
                );
                break;
            case EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_ORDERED: // 4
            case EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_ORDERED_NO_GROUPED: // 7
                $categoriesAddedInExercise = $cat->getCategoryExerciseTree(
                    $this,
                    $this->course['real_id'],
                    null,
                    true,
                    true
                );
                $questions_by_category = TestCategory::getQuestionsByCat(
                    $this->id,
                    $question_list,
                    $categoriesAddedInExercise
                );
                $question_list = $this->pickQuestionsPerCategory(
                    $categoriesAddedInExercise,
                    $question_list,
                    $questions_by_category,
                    true,
                    false
                );
            break;
            case EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM: // 5
                $categoriesAddedInExercise = $cat->getCategoryExerciseTree(
                    $this,
                    $this->course['real_id'],
                    'title DESC',
                    false,
                    true
                );
                $questions_by_category = TestCategory::getQuestionsByCat(
                    $this->id,
                    $question_list,
                    $categoriesAddedInExercise
                );
                $question_list = $this->pickQuestionsPerCategory(
                    $categoriesAddedInExercise,
                    $question_list,
                    $questions_by_category,
                    true,
                    true
                );
                break;
            case EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM: // 6
            case EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM_NO_GROUPED:
                $categoriesAddedInExercise = $cat->getCategoryExerciseTree(
                    $this,
                    $this->course['real_id'],
                    null,
                    true,
                    true
                );
                $questions_by_category = TestCategory::getQuestionsByCat(
                    $this->id,
                    $question_list,
                    $categoriesAddedInExercise
                );
                $question_list = $this->pickQuestionsPerCategory(
                    $categoriesAddedInExercise,
                    $question_list,
                    $questions_by_category,
                    true,
                    true
                );
                break;
            case EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_ORDERED_NO_GROUPED: // 7
                break;
            case EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM_NO_GROUPED: // 8
                break;
            case EX_Q_SELECTION_CATEGORIES_ORDERED_BY_PARENT_QUESTIONS_ORDERED: // 9
                $categoriesAddedInExercise = $cat->getCategoryExerciseTree(
                    $this,
                    $this->course['real_id'],
                    'root ASC, lft ASC',
                    false,
                    true
                );
                $questions_by_category = TestCategory::getQuestionsByCat(
                    $this->id,
                    $question_list,
                    $categoriesAddedInExercise
                );
                $question_list = $this->pickQuestionsPerCategory(
                    $categoriesAddedInExercise,
                    $question_list,
                    $questions_by_category,
                    true,
                    false
                );
                break;
            case EX_Q_SELECTION_CATEGORIES_ORDERED_BY_PARENT_QUESTIONS_RANDOM: // 10
                $categoriesAddedInExercise = $cat->getCategoryExerciseTree(
                    $this,
                    $this->course['real_id'],
                    'root, lft ASC',
                    false,
                    true
                );
                $questions_by_category = TestCategory::getQuestionsByCat(
                    $this->id,
                    $question_list,
                    $categoriesAddedInExercise
                );
                $question_list = $this->pickQuestionsPerCategory(
                    $categoriesAddedInExercise,
                    $question_list,
                    $questions_by_category,
                    true,
                    true
                );
                break;
        }

        $result['question_list'] = isset($question_list) ? $question_list : array();
        $result['category_with_questions_list'] = isset($questions_by_category) ? $questions_by_category : array();

        // Adding category info in the category list with question list:

        if (!empty($questions_by_category)) {

            /*$em = Database::getManager();
            $repo = $em->getRepository('ChamiloCoreBundle:CQuizCategory');*/

            $newCategoryList = array();

            foreach ($questions_by_category as $categoryId => $questionList) {
                $cat = new TestCategory();
                $cat = $cat->getCategory($categoryId);

                $cat = (array)$cat;
                $cat['iid'] = $cat['id'];
                //*$cat['name'] = $cat['name'];

                $categoryParentInfo = null;
                // Parent is not set no loop here
                if (!empty($cat['parent_id'])) {
                    if (!isset($parentsLoaded[$cat['parent_id']])) {
                        $categoryEntity = $em->find('ChamiloCoreBundle:CQuizCategory', $cat['parent_id']);
                        $parentsLoaded[$cat['parent_id']] = $categoryEntity;
                    } else {
                        $categoryEntity = $parentsLoaded[$cat['parent_id']];
                    }
                    $path = $repo->getPath($categoryEntity);
                    $index = 0;
                    if ($this->categoryMinusOne) {
                        //$index = 1;
                    }
                    /** @var \Chamilo\Entity\CQuizCategory $categoryParent*/

                    foreach ($path as $categoryParent) {
                        $visibility = $categoryParent->getVisibility();

                        if ($visibility == 0) {
                            $categoryParentId = $categoryId;
                            $categoryTitle = $cat['title'];
                            if (count($path) > 1) {
                                continue;
                            }
                        } else {
                            $categoryParentId = $categoryParent->getIid();
                            $categoryTitle = $categoryParent->getTitle();
                        }

                        $categoryParentInfo['id'] = $categoryParentId;
                        $categoryParentInfo['iid'] = $categoryParentId;
                        $categoryParentInfo['parent_path'] = null;
                        $categoryParentInfo['title'] = $categoryTitle;
                        $categoryParentInfo['name'] = $categoryTitle;
                        $categoryParentInfo['parent_id'] = null;
                        break;
                    }
                }
                $cat['parent_info'] = $categoryParentInfo;
                $newCategoryList[$categoryId] = array(
                    'category' => $cat,
                    'question_list' => $questionList
                );
            }

            $result['category_with_questions_list'] = $newCategoryList;
        }
        return $result;
    }

    /**
     * returns the array with the question ID list
     *
     * @author Olivier Brouckaert
     * @return array - question ID list
     */
    public function selectQuestionList($from_db = false)
    {
        if ($from_db && !empty($this->id)) {
            $nbQuestions = $this->getQuestionCount();
            $questionSelectionType = $this->getQuestionSelectionType();

            switch ($questionSelectionType) {
                case EX_Q_SELECTION_ORDERED:
                    $questionList = $this->getQuestionOrderedList();
                    break;
                case EX_Q_SELECTION_RANDOM:
                    // Not a random exercise, or if there are not at least 2 questions
                    if ($this->random == 0 || $nbQuestions < 2) {
                        $questionList = $this->getQuestionOrderedList();
                    } else {
                        $questionList = $this->selectRandomList();
                    }
                    break;
                default:
                    $questionList = $this->getQuestionOrderedList();
                    $result = $this->getQuestionListWithCategoryListFilteredByCategorySettings(
                        $questionList,
                        $questionSelectionType
                    );
                    $this->categoryWithQuestionList = $result['category_with_questions_list'];
                    $questionList = $result['question_list'];
                    break;
            }

            return $questionList;
        }

        return $this->questionList;
    }

    /**
     * returns the number of questions in this exercise
     *
     * @author Olivier Brouckaert
     * @return integer - number of questions
     */
    public function selectNbrQuestions()
    {
        return sizeof($this->questionList);
    }

    /**
     * @return int
     */
    public function selectPropagateNeg()
    {
        return $this->propagate_neg;
    }

    /**
     * @return int
     */
    public function selectSaveCorrectAnswers()
    {
        return $this->saveCorrectAnswers;
    }

    /**
     * Selects questions randomly in the question list
     *
     * @author Olivier Brouckaert
     * @author Hubert Borderiou 15 nov 2011
     * @return array - if the exercise is not set to take questions randomly, returns the question list
     *					 without randomizing, otherwise, returns the list with questions selected randomly
     */
    public function selectRandomList()
    {
        /*$nbQuestions	= $this->selectNbrQuestions();
        $temp_list		= $this->questionList;

        //Not a random exercise, or if there are not at least 2 questions
        if($this->random == 0 || $nbQuestions < 2) {
            return $this->questionList;
        }
        if ($nbQuestions != 0) {
            shuffle($temp_list);
            $my_random_list = array_combine(range(1,$nbQuestions),$temp_list);
            $my_question_list = array();
            // $this->random == -1 if random with all questions
            if ($this->random > 0) {
                $i = 0;
                foreach ($my_random_list as $item) {
                    if ($i < $this->random) {
                        $my_question_list[$i] = $item;
                    } else {
                        break;
                    }
                    $i++;
                }
            } else {
                $my_question_list = $my_random_list;
            }
            return $my_question_list;
        }*/

        $TBL_EXERCISE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);

        $random = isset($this->random) && !empty($this->random) ? $this->random : 0;

        $randomLimit = "LIMIT $random";
        // Random all questions so no limit
        if ($random == -1) {
            $randomLimit = null;
        }

        // @todo improve this query
        $sql = "SELECT e.question_id
                FROM $TBL_EXERCISE_QUESTION e INNER JOIN $TBL_QUESTIONS q
                    ON (e.question_id= q.iid)
                WHERE e.c_id = {$this->course_id} AND e.exercice_id	= '".Database::escape_string($this->id)."'
                ORDER BY RAND()
                $randomLimit ";
        $result = Database::query($sql);
        $questionList = array();
        while ($row = Database::fetch_object($result)) {
            $questionList[] = $row->question_id;
        }

        return $questionList;
    }

    /**
     * returns 'true' if the question ID is in the question list
     *
     * @author Olivier Brouckaert
     * @param integer $questionId - question ID
     * @return boolean - true if in the list, otherwise false
     */
    public function isInList($questionId)
    {
        if (is_array($this->questionList)) {
            return in_array($questionId, $this->questionList);
        } else {
            return false;
        }
    }

    /**
     * changes the exercise title
     *
     * @author Olivier Brouckaert
     * @param string $title - exercise title
     */
    public function updateTitle($title)
    {
        $this->exercise=$title;
    }

    /**
     * changes the exercise max attempts
     *
     * @param int $attempts - exercise max attempts
     */
    public function updateAttempts($attempts)
    {
        $this->attempts=$attempts;
    }

    /**
     * changes the exercise feedback type
     *
     * @param int $feedback_type
     */
    public function updateFeedbackType($feedback_type)
    {
        $this->feedback_type=$feedback_type;
    }

    /**
     * changes the exercise description
     *
     * @author Olivier Brouckaert
     * @param string $description - exercise description
     */
    public function updateDescription($description)
    {
        $this->description=$description;
    }

    /**
     * changes the exercise expired_time
     *
     * @author Isaac flores
     * @param int $expired_time The expired time of the quiz
     */
    public function updateExpiredTime($expired_time)
    {
        $this->expired_time = $expired_time;
    }

    /**
     * @param $value
     */
    public function updatePropagateNegative($value)
    {
        $this->propagate_neg = $value;
    }

    /**
     * @param $value int
     */
    public function updateSaveCorrectAnswers($value)
    {
        $this->saveCorrectAnswers = $value;
    }

    /**
     * @param $value
     */
    public function updateReviewAnswers($value)
    {
        $this->review_answers = isset($value) && $value ? true : false;
    }

    /**
     * @param $value
     */
    public function updatePassPercentage($value)
    {
        $this->pass_percentage = $value;
    }

    /**
     * @param string $text
     */
    public function updateEmailNotificationTemplate($text)
    {
        $this->emailNotificationTemplate = $text;
    }

    /**
     * @param string $text
     */
    public function updateEmailNotificationTemplateToUser($text)
    {
        $this->emailNotificationTemplateToUser = $text;
    }

    /**
     * @param string $value
     */
    public function setNotifyUserByEmail($value)
    {
        $this->notifyUserByEmail = $value;
    }

    /**
     * @param int $value
     */
    public function updateEndButton($value)
    {
        $this->endButton = intval($value);
    }

    /**
     * @param string $value
     */
    public function setOnSuccessMessage($value)
    {
        $this->onSuccessMessage = $value;
    }

    /**
     * @param string $value
     */
    public function setOnFailedMessage($value)
    {
        $this->onFailedMessage = $value;
    }

    /**
     * @param $value
     */
    public function setModelType($value)
    {
        $this->modelType = intval($value);
    }

    /**
     * @param int $value
     */
    public function setQuestionSelectionType($value)
    {
        $this->questionSelectionType = intval($value);
    }

    /**
     * @return int
     */
    public function getQuestionSelectionType()
    {
        return $this->questionSelectionType;
    }

    /**
     * @param array $categories
     */
    public function updateCategories($categories)
    {
        if (!empty($categories)) {
            $categories = array_map('intval', $categories);
            $this->categories = $categories;
        }
    }

    /**
     * changes the exercise sound file
     *
     * @author Olivier Brouckaert
     * @param string $sound - exercise sound file
     * @param string $delete - ask to delete the file
     */
    public function updateSound($sound,$delete)
    {
        global $audioPath, $documentPath;
        $TBL_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);

        if ($sound['size'] && (strstr($sound['type'],'audio') || strstr($sound['type'],'video'))) {
            $this->sound=$sound['name'];

            if (@move_uploaded_file($sound['tmp_name'],$audioPath.'/'.$this->sound)) {
                $sql = "SELECT 1 FROM $TBL_DOCUMENT
                        WHERE c_id = ".$this->course_id." AND path='".str_replace($documentPath,'',$audioPath).'/'.$this->sound."'";
                $result = Database::query($sql);

                if (!Database::num_rows($result)) {
                    $id = add_document(
                        $this->course,
                        str_replace($documentPath,'',$audioPath).'/'.$this->sound,
                        'file',
                        $sound['size'],
                        $sound['name']
                    );
                    api_item_property_update(
                        $this->course,
                        TOOL_DOCUMENT,
                        $id,
                        'DocumentAdded',
                        api_get_user_id()
                    );
                    item_property_update_on_folder(
                        $this->course,
                        str_replace($documentPath, '', $audioPath),
                        api_get_user_id()
                    );
                }
            }
        } elseif($delete && is_file($audioPath.'/'.$this->sound)) {
            $this->sound='';
        }
    }

    /**
     * changes the exercise type
     *
     * @author Olivier Brouckaert
     * @param integer $type - exercise type
     */
    public function updateType($type)
    {
        $this->type = $type;
    }

    /**
     * sets to 0 if questions are not selected randomly
     * if questions are selected randomly, sets the draws
     *
     * @author Olivier Brouckaert
     * @param integer $random - 0 if not random, otherwise the draws
     */
    public function setRandom($random)
    {
        /*if ($random == 'all') {
            $random = $this->selectNbrQuestions();
        }*/
        $this->random = $random;
    }

    /**
     * sets to 0 if answers are not selected randomly
     * if answers are selected randomly
     * @author Juan Carlos Rana
     * @param integer $random_answers - random answers
     */
    public function updateRandomAnswers($random_answers)
    {
        $this->random_answers = $random_answers;
    }

    /**
     * enables the exercise
     *
     * @author Olivier Brouckaert
     */
    public function enable()
    {
        $this->active=1;
    }

    /**
     * disables the exercise
     *
     * @author Olivier Brouckaert
     */
    public function disable()
    {
        $this->active=0;
    }

    /**
     * Set disable results
     */
    public function disable_results()
    {
        $this->results_disabled = true;
    }

    /**
     * Enable results
     */
    public function enable_results()
    {
        $this->results_disabled = false;
    }

    /**
     * @param int $results_disabled
     */
    public function updateResultsDisabled($results_disabled)
    {
        $this->results_disabled = intval($results_disabled);
    }

    /**
     * updates the exercise in the data base
     *
     * @author Olivier Brouckaert
     */
    public function save($type_e = '')
    {
        $_course = $this->course;
        $TBL_EXERCISES = Database::get_course_table(TABLE_QUIZ_TEST);

        $id = $this->id;
        $exercise = $this->exercise;
        $description = $this->description;
        $sound = $this->sound;
        $type = $this->type;
        $attempts = isset($this->attempts) ? $this->attempts : 0;
        $feedback_type = isset($this->feedback_type) ? $this->feedback_type : 0;
        $random = $this->random;
        $random_answers = $this->random_answers;
        $active = $this->active;
        $propagate_neg = (int) $this->propagate_neg;
        $saveCorrectAnswers = isset($this->saveCorrectAnswers) && $this->saveCorrectAnswers ? 1 : 0;
        $review_answers = isset($this->review_answers) && $this->review_answers ? 1 : 0;
        $randomByCat = intval($this->randomByCat);
        $text_when_finished = $this->text_when_finished;
        $display_category_name = intval($this->display_category_name);
        $pass_percentage = intval($this->pass_percentage);
        $session_id = $this->sessionId;

        //If direct we do not show results
        if ($feedback_type == EXERCISE_FEEDBACK_TYPE_DIRECT) {
            $results_disabled = 0;
        } else {
            $results_disabled = intval($this->results_disabled);
        }

        $expired_time = intval($this->expired_time);

        // Exercise already exists
        if ($id) {
            // we prepare date in the database using the api_get_utc_datetime() function
            if (!empty($this->start_time)) {
                $start_time = $this->start_time;
            } else {
                $start_time = null;
            }

            if (!empty($this->end_time)) {
                $end_time = $this->end_time;
            } else {
                $end_time = null;
            }

            $params = [
                'title' => $exercise,
                'description' => $description,
            ];

            $paramsExtra = [];
            if ($type_e != 'simple') {
                $paramsExtra = [
                    'sound' => $sound,
                    'type' => $type,
                    'random' => $random,
                    'random_answers' => $random_answers,
                    'active' => $active,
                    'feedback_type' => $feedback_type,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'max_attempt' => $attempts,
                    'expired_time' => $expired_time,
                    'propagate_neg' => $propagate_neg,
                    'save_correct_answers' => $saveCorrectAnswers,
                    'review_answers' => $review_answers,
                    'random_by_category' => $randomByCat,
                    'text_when_finished' => $text_when_finished,
                    'display_category_name' => $display_category_name,
                    'pass_percentage' => $pass_percentage,
                    'results_disabled' => $results_disabled,
                    'question_selection_type' => $this->getQuestionSelectionType(),
                    'hide_question_title' => $this->getHideQuestionTitle()
                ];
            }

            $params = array_merge($params, $paramsExtra);

            Database::update(
                $TBL_EXERCISES,
                $params,
                ['c_id = ? AND id = ?' => [$this->course_id, $id]]
            );

            // update into the item_property table
            api_item_property_update(
                $_course,
                TOOL_QUIZ,
                $id,
                'QuizUpdated',
                api_get_user_id()
            );

            if (api_get_setting('search_enabled')=='true') {
                $this->search_engine_edit();
            }
        } else {
            // Creates a new exercise

            // In this case of new exercise, we don't do the api_get_utc_datetime()
            // for date because, bellow, we call function api_set_default_visibility()
            // In this function, api_set_default_visibility,
            // the Quiz is saved too, with an $id and api_get_utc_datetime() is done.
            // If we do it now, it will be done twice (cf. https://support.chamilo.org/issues/6586)
            if (!empty($this->start_time)) {
                $start_time = $this->start_time;
            } else {
                $start_time = null;
            }

            if (!empty($this->end_time)) {
                $end_time = $this->end_time;
            } else {
                $end_time = null;
            }

            $params = [
                'c_id' => $this->course_id,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'title' => $exercise,
                'description' => $description,
                'sound' => $sound,
                'type' => $type,
                'random' => $random,
                'random_answers' => $random_answers,
                'active' => $active,
                'results_disabled' => $results_disabled,
                'max_attempt' => $attempts,
                'feedback_type' => $feedback_type,
                'expired_time' => $expired_time,
                'session_id' => $session_id,
                'review_answers' => $review_answers,
                'random_by_category' => $randomByCat,
                'text_when_finished' => $text_when_finished,
                'display_category_name' => $display_category_name,
                'pass_percentage' => $pass_percentage,
                'save_correct_answers' => (int) $saveCorrectAnswers,
                'propagate_neg' => $propagate_neg,
                'hide_question_title' => $this->getHideQuestionTitle()
            ];

            $this->id = Database::insert($TBL_EXERCISES, $params);

            if ($this->id) {

                $sql = "UPDATE $TBL_EXERCISES SET id = iid WHERE iid = {$this->id} ";
                Database::query($sql);

                $sql = "UPDATE $TBL_EXERCISES
                        SET question_selection_type= ".intval($this->getQuestionSelectionType())."
                        WHERE id = ".$this->id." AND c_id = ".$this->course_id;
                Database::query($sql);

                // insert into the item_property table
                api_item_property_update(
                    $this->course,
                    TOOL_QUIZ,
                    $this->id,
                    'QuizAdded',
                    api_get_user_id()
                );

                // This function save the quiz again, carefull about start_time
                // and end_time if you remove this line (see above)
                api_set_default_visibility(
                    $this->id,
                    TOOL_QUIZ,
                    null,
                    $this->course
                );

                if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
                    $this->search_engine_save();
                }
            }
        }

        $this->save_categories_in_exercise($this->categories);

        // Updates the question position
        $this->update_question_positions();
    }

    /**
     * Updates question position
     */
    public function update_question_positions()
    {
        $quiz_question_table = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        //Fixes #3483 when updating order
        $question_list = $this->selectQuestionList(true);
        if (!empty($question_list)) {
            foreach ($question_list as $position => $questionId) {
                $sql = "UPDATE $quiz_question_table SET
                        question_order ='".intval($position)."'
                        WHERE
                            c_id = ".$this->course_id." AND
                            question_id = ".intval($questionId)." AND
                            exercice_id=".intval($this->id);
                Database::query($sql);
            }
        }
    }

    /**
     * Adds a question into the question list
     *
     * @author Olivier Brouckaert
     * @param integer $questionId - question ID
     * @return boolean - true if the question has been added, otherwise false
     */
    public function addToList($questionId)
    {
        // checks if the question ID is not in the list
        if (!$this->isInList($questionId)) {
            // selects the max position
            if (!$this->selectNbrQuestions()) {
                $pos = 1;
            } else {
                if (is_array($this->questionList)) {
                    $pos = max(array_keys($this->questionList)) + 1;
                }
            }
            $this->questionList[$pos] = $questionId;

            return true;
        }

        return false;
    }

    /**
     * removes a question from the question list
     *
     * @author Olivier Brouckaert
     * @param integer $questionId - question ID
     * @return boolean - true if the question has been removed, otherwise false
     */
    public function removeFromList($questionId)
    {
        // searches the position of the question ID in the list
        $pos = array_search($questionId,$this->questionList);

        // question not found
        if ($pos === false) {
            return false;
        } else {
            // dont reduce the number of random question if we use random by category option, or if
            // random all questions
            if ($this->isRandom() && $this->isRandomByCat() == 0) {
                if (count($this->questionList) >= $this->random && $this->random > 0) {
                    $this->random -= 1;
                    $this->save();
                }
            }
            // deletes the position from the array containing the wanted question ID
            unset($this->questionList[$pos]);

            return true;
        }
    }

    /**
     * deletes the exercise from the database
     * Notice : leaves the question in the data base
     *
     * @author Olivier Brouckaert
     */
    public function delete()
    {
        $TBL_EXERCISES = Database::get_course_table(TABLE_QUIZ_TEST);
        $sql = "UPDATE $TBL_EXERCISES SET active='-1'
                WHERE c_id = ".$this->course_id." AND id = ".intval($this->id);
        Database::query($sql);
        api_item_property_update($this->course, TOOL_QUIZ, $this->id, 'QuizDeleted', api_get_user_id());
        api_item_property_update($this->course, TOOL_QUIZ, $this->id, 'delete', api_get_user_id());

        if (api_get_setting('search_enabled')=='true' && extension_loaded('xapian') ) {
            $this->search_engine_delete();
        }
    }

    /**
     * Creates the form to create / edit an exercise
     * @param FormValidator $form
     */
    public function createForm($form, $type='full')
    {
        if (empty($type)) {
            $type = 'full';
        }

        // form title
        if (!empty($_GET['exerciseId'])) {
            $form_title = get_lang('ModifyExercise');
        } else {
            $form_title = get_lang('NewEx');
        }

        $form->addElement('header', $form_title);

        // Title.
        $form->addElement(
            'text',
            'exerciseTitle',
            get_lang('ExerciseName'),
            array('id' => 'exercise_title')
        );

        $form->addElement('advanced_settings', 'advanced_params', get_lang('AdvancedParameters'));
        $form->addElement('html', '<div id="advanced_params_options" style="display:none">');

        $editor_config = array(
            'ToolbarSet' => 'TestQuestionDescription',
            'Width' => '100%',
            'Height' => '150',
        );
        if (is_array($type)){
            $editor_config = array_merge($editor_config, $type);
        }

        $form->addHtmlEditor(
            'exerciseDescription',
            get_lang('ExerciseDescription'),
            false,
            false,
            $editor_config
        );

        if ($type == 'full') {
            //Can't modify a DirectFeedback question
            if ($this->selectFeedbackType() != EXERCISE_FEEDBACK_TYPE_DIRECT) {
                // feedback type
                $radios_feedback = array();
                $radios_feedback[] = $form->createElement(
                    'radio',
                    'exerciseFeedbackType',
                    null,
                    get_lang('ExerciseAtTheEndOfTheTest'),
                    '0',
                    array(
                        'id' => 'exerciseType_0',
                        'onclick' => 'check_feedback()',
                    )
                );

                if (api_get_setting('enable_quiz_scenario') == 'true') {
                    //Can't convert a question from one feedback to another if there is more than 1 question already added
                    if ($this->selectNbrQuestions() == 0) {
                        $radios_feedback[] = $form->createElement(
                            'radio',
                            'exerciseFeedbackType',
                            null,
                            get_lang('DirectFeedback'),
                            '1',
                            array(
                                'id' => 'exerciseType_1',
                                'onclick' => 'check_direct_feedback()',
                            )
                        );
                    }
                }

                $radios_feedback[] = $form->createElement(
                    'radio',
                    'exerciseFeedbackType',
                    null,
                    get_lang('NoFeedback'),
                    '2',
                    array('id' => 'exerciseType_2')
                );
                $form->addGroup($radios_feedback, null, array(get_lang('FeedbackType'),get_lang('FeedbackDisplayOptions')));

                // Type of results display on the final page
                $radios_results_disabled = array();
                $radios_results_disabled[] = $form->createElement(
                    'radio',
                    'results_disabled',
                    null,
                    get_lang('ShowScoreAndRightAnswer'),
                    '0',
                    array('id' => 'result_disabled_0')
                );
                $radios_results_disabled[] = $form->createElement(
                    'radio',
                    'results_disabled',
                    null,
                    get_lang('DoNotShowScoreNorRightAnswer'),
                    '1',
                    array('id' => 'result_disabled_1', 'onclick' => 'check_results_disabled()')
                );
                $radios_results_disabled[] = $form->createElement(
                    'radio',
                    'results_disabled',
                    null,
                    get_lang('OnlyShowScore'),
                    '2',
                    array('id' => 'result_disabled_2')
                );


                $radios_results_disabled[] = $form->createElement(
                    'radio',
                    'results_disabled',
                    null,
                    get_lang('ShowScoreEveryAttemptShowAnswersLastAttempt'),
                    '4',
                    array('id' => 'result_disabled_4')
                );

                $form->addGroup($radios_results_disabled, null, get_lang('ShowResultsToStudents'));

                // Type of questions disposition on page
                $radios = array();

                $radios[] = $form->createElement('radio', 'exerciseType', null, get_lang('SimpleExercise'),    '1', array('onclick' => 'check_per_page_all()', 'id'=>'option_page_all'));
                $radios[] = $form->createElement('radio', 'exerciseType', null, get_lang('SequentialExercise'),'2', array('onclick' => 'check_per_page_one()', 'id'=>'option_page_one'));

                $form->addGroup($radios, null, get_lang('QuestionsPerPage'));

            } else {
                // if is Directfeedback but has not questions we can allow to modify the question type
                if ($this->selectNbrQuestions() == 0) {

                    // feedback type
                    $radios_feedback = array();
                    $radios_feedback[] = $form->createElement('radio', 'exerciseFeedbackType', null, get_lang('ExerciseAtTheEndOfTheTest'),'0',array('id' =>'exerciseType_0', 'onclick' => 'check_feedback()'));

                    if (api_get_setting('enable_quiz_scenario') == 'true') {
                        $radios_feedback[] = $form->createElement('radio', 'exerciseFeedbackType', null, get_lang('DirectFeedback'), '1', array('id' =>'exerciseType_1' , 'onclick' => 'check_direct_feedback()'));
                    }
                    $radios_feedback[] = $form->createElement('radio', 'exerciseFeedbackType', null, get_lang('NoFeedback'),'2',array('id' =>'exerciseType_2'));
                    $form->addGroup($radios_feedback, null, array(get_lang('FeedbackType'),get_lang('FeedbackDisplayOptions')));

                    //$form->addElement('select', 'exerciseFeedbackType',get_lang('FeedbackType'),$feedback_option,'onchange="javascript:feedbackselection()"');
                    $radios_results_disabled = array();
                    $radios_results_disabled[] = $form->createElement('radio', 'results_disabled', null, get_lang('ShowScoreAndRightAnswer'), '0', array('id'=>'result_disabled_0'));
                    $radios_results_disabled[] = $form->createElement('radio', 'results_disabled', null, get_lang('DoNotShowScoreNorRightAnswer'),  '1',array('id'=>'result_disabled_1','onclick' => 'check_results_disabled()'));
                    $radios_results_disabled[] = $form->createElement('radio', 'results_disabled', null, get_lang('OnlyShowScore'),  '2',array('id'=>'result_disabled_2','onclick' => 'check_results_disabled()'));
                    $form->addGroup($radios_results_disabled, null, get_lang('ShowResultsToStudents'),'');

                    // Type of questions disposition on page
                    $radios = array();
                    $radios[] = $form->createElement('radio', 'exerciseType', null, get_lang('SimpleExercise'),    '1');
                    $radios[] = $form->createElement('radio', 'exerciseType', null, get_lang('SequentialExercise'),'2');
                    $form->addGroup($radios, null, get_lang('ExerciseType'));

                } else {
                    //Show options freeze
                    $radios_results_disabled[] = $form->createElement('radio', 'results_disabled', null, get_lang('ShowScoreAndRightAnswer'), '0', array('id'=>'result_disabled_0'));
                    $radios_results_disabled[] = $form->createElement('radio', 'results_disabled', null, get_lang('DoNotShowScoreNorRightAnswer'),  '1',array('id'=>'result_disabled_1','onclick' => 'check_results_disabled()'));
                    $radios_results_disabled[] = $form->createElement('radio', 'results_disabled', null, get_lang('OnlyShowScore'),  '2',array('id'=>'result_disabled_2','onclick' => 'check_results_disabled()'));
                    $result_disable_group = $form->addGroup($radios_results_disabled, null, get_lang('ShowResultsToStudents'));
                    $result_disable_group->freeze();

                    //we force the options to the DirectFeedback exercisetype
                    $form->addElement('hidden', 'exerciseFeedbackType', EXERCISE_FEEDBACK_TYPE_DIRECT);
                    $form->addElement('hidden', 'exerciseType', ONE_PER_PAGE);

                    // Type of questions disposition on page
                    $radios[] = $form->createElement('radio', 'exerciseType', null, get_lang('SimpleExercise'),    '1', array('onclick' => 'check_per_page_all()', 'id'=>'option_page_all'));
                    $radios[] = $form->createElement('radio', 'exerciseType', null, get_lang('SequentialExercise'),'2', array('onclick' => 'check_per_page_one()', 'id'=>'option_page_one'));

                    $type_group = $form->addGroup($radios, null, get_lang('QuestionsPerPage'));
                    $type_group->freeze();
                }
            }

            if (true) {
                $option = array(
                    EX_Q_SELECTION_ORDERED => get_lang('OrderedByUser'),
                    //  defined by user
                    EX_Q_SELECTION_RANDOM => get_lang('Random'),
                    // 1-10, All
                    'per_categories' => '--------'.get_lang(
                            'UsingCategories'
                        ).'----------',

                    // Base (A 123 {3} B 456 {3} C 789{2} D 0{0}) --> Matrix {3, 3, 2, 0}
                    EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED => get_lang(
                        'OrderedCategoriesAlphabeticallyWithQuestionsOrdered'
                    ),
                    // A 123 B 456 C 78 (0, 1, all)
                    EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_ORDERED => get_lang(
                        'RandomCategoriesWithQuestionsOrdered'
                    ),
                    // C 78 B 456 A 123

                    EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM => get_lang(
                        'OrderedCategoriesAlphabeticallyWithRandomQuestions'
                    ),
                    // A 321 B 654 C 87
                    EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM => get_lang(
                        'RandomCategoriesWithRandomQuestions'
                    ),
                    //C 87 B 654 A 321

                    //EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_ORDERED_NO_GROUPED => get_lang('RandomCategoriesWithQuestionsOrderedNoQuestionGrouped'),
                    /*    B 456 C 78 A 123
                            456 78 123
                            123 456 78
                    */
                    //EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM_NO_GROUPED => get_lang('RandomCategoriesWithRandomQuestionsNoQuestionGrouped'),
                    /*
                        A 123 B 456 C 78
                        B 456 C 78 A 123
                        B 654 C 87 A 321
                        654 87 321
                        165 842 73
                    */
                    //EX_Q_SELECTION_CATEGORIES_ORDERED_BY_PARENT_QUESTIONS_ORDERED => get_lang('OrderedCategoriesByParentWithQuestionsOrdered'),
                    //EX_Q_SELECTION_CATEGORIES_ORDERED_BY_PARENT_QUESTIONS_RANDOM => get_lang('OrderedCategoriesByParentWithQuestionsRandom'),
                );

                $form->addElement(
                    'select',
                    'question_selection_type',
                    array(get_lang('QuestionSelection')),
                    $option,
                    array(
                        'id' => 'questionSelection',
                        'onclick' => 'checkQuestionSelection()'
                    )
                );

                $displayMatrix = 'none';
                $displayRandom = 'none';
                $selectionType = $this->getQuestionSelectionType();
                switch ($selectionType) {
                    case EX_Q_SELECTION_RANDOM:
                        $displayRandom = 'block';
                        break;
                    case $selectionType >= EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED:
                        $displayMatrix = 'block';
                        break;
                }

                $form->addElement(
                    'html',
                    '<div id="hidden_random" style="display:'.$displayRandom.'">'
                );
                // Number of random question.
                $max = ($this->id > 0) ? $this->selectNbrQuestions() : 10;
                $option = range(0, $max);
                $option[0] = get_lang('No');
                $option[-1] = get_lang('AllQuestionsShort');
                $form->addElement(
                    'select',
                    'randomQuestions',
                    array(
                        get_lang('RandomQuestions'),
                        get_lang('RandomQuestionsHelp')
                    ),
                    $option,
                    array('id' => 'randomQuestions')
                );
                $form->addElement('html', '</div>');

                $form->addElement(
                    'html',
                    '<div id="hidden_matrix" style="display:'.$displayMatrix.'">'
                );

                // Category selection.
                $cat = new TestCategory();
                $cat_form = $cat->returnCategoryForm($this);
                $form->addElement('label', null, $cat_form);
                $form->addElement('html', '</div>');

                // Category name.
                $radio_display_cat_name = array(
                    $form->createElement('radio', 'display_category_name', null, get_lang('Yes'), '1'),
                    $form->createElement('radio', 'display_category_name', null, get_lang('No'), '0')
                );
                $form->addGroup($radio_display_cat_name, null, get_lang('QuestionDisplayCategoryName'));

                // Random answers.
                $radios_random_answers = array(
                    $form->createElement('radio', 'randomAnswers', null, get_lang('Yes'), '1'),
                    $form->createElement('radio', 'randomAnswers', null, get_lang('No'), '0')
                );
                $form->addGroup($radios_random_answers, null, get_lang('RandomAnswers'));

                // Hide question title.
                $group = array(
                    $form->createElement('radio', 'hide_question_title', null, get_lang('Yes'), '1'),
                    $form->createElement('radio', 'hide_question_title', null, get_lang('No'), '0')
                );
                $form->addGroup($group, null, get_lang('HideQuestionTitle'));
            } else {

                // number of random question
                /*
                $max = ($this->id > 0) ? $this->selectNbrQuestions() : 10 ;
                $option = range(0, $max);
                $option[0] = get_lang('No');
                $option[-1] = get_lang('AllQuestionsShort');
                $form->addElement('select', 'randomQuestions',array(get_lang('RandomQuestions'), get_lang('RandomQuestionsHelp')), $option, array('id'=>'randomQuestions'));

                // Random answers
                $radios_random_answers = array();
                $radios_random_answers[] = $form->createElement('radio', 'randomAnswers', null, get_lang('Yes'),'1');
                $radios_random_answers[] = $form->createElement('radio', 'randomAnswers', null, get_lang('No'),'0');
                $form->addGroup($radios_random_answers, null, get_lang('RandomAnswers'), '');

                // Random by category
                $form->addElement('html','<div class="clear">&nbsp;</div>');
                $radiocat = array();
                $radiocat[] = $form->createElement('radio', 'randomByCat', null, get_lang('YesWithCategoriesShuffled'),'1');
                $radiocat[] = $form->createElement('radio', 'randomByCat', null, get_lang('YesWithCategoriesSorted'),'2');
                $radiocat[] = $form->createElement('radio', 'randomByCat', null, get_lang('No'),'0');
                $radioCatGroup = $form->addGroup($radiocat, null, get_lang('RandomQuestionByCategory'), '');
                $form->addElement('html','<div class="clear">&nbsp;</div>');

                // add the radio display the category name for student
                $radio_display_cat_name = array();
                $radio_display_cat_name[] = $form->createElement('radio', 'display_category_name', null, get_lang('Yes'), '1');
                $radio_display_cat_name[] = $form->createElement('radio', 'display_category_name', null, get_lang('No'), '0');
                $form->addGroup($radio_display_cat_name, null, get_lang('QuestionDisplayCategoryName'), '');*/
            }
            // Attempts
            $attempt_option = range(0, 10);
            $attempt_option[0] = get_lang('Infinite');

            $form->addElement(
                'select',
                'exerciseAttempts',
                get_lang('ExerciseAttempts'),
                $attempt_option,
                ['id' => 'exerciseAttempts']
            );

            // Exercise time limit
            $form->addElement('checkbox', 'activate_start_date_check',null, get_lang('EnableStartTime'), array('onclick' => 'activate_start_date()'));

            $var = Exercise::selectTimeLimit();

            if (!empty($this->start_time)) {
                $form->addElement('html', '<div id="start_date_div" style="display:block;">');
            } else {
                $form->addElement('html', '<div id="start_date_div" style="display:none;">');
            }

            $form->addElement('date_time_picker', 'start_time');

            $form->addElement('html','</div>');

            $form->addElement('checkbox', 'activate_end_date_check', null , get_lang('EnableEndTime'), array('onclick' => 'activate_end_date()'));

            if (!empty($this->end_time)) {
                $form->addElement('html', '<div id="end_date_div" style="display:block;">');
            } else {
                $form->addElement('html', '<div id="end_date_div" style="display:none;">');
            }

            $form->addElement('date_time_picker', 'end_time');
            $form->addElement('html','</div>');

            //$check_option=$this->selectType();
            $diplay = 'block';
            $form->addElement('checkbox', 'propagate_neg', null, get_lang('PropagateNegativeResults'));
            $form->addCheckBox(
                'save_correct_answers',
                null,
                get_lang('SaveTheCorrectAnswersForTheNextAttempt')
            );
            $form->addElement('html','<div class="clear">&nbsp;</div>');
            $form->addElement('checkbox', 'review_answers', null, get_lang('ReviewAnswers'));

            $form->addElement('html','<div id="divtimecontrol"  style="display:'.$diplay.';">');

            //Timer control
            //$time_hours_option = range(0,12);
            //$time_minutes_option = range(0,59);
            $form->addElement(
                'checkbox',
                'enabletimercontrol',
                null,
                get_lang('EnableTimerControl'),
                array(
                    'onclick' => 'option_time_expired()',
                    'id' => 'enabletimercontrol',
                    'onload' => 'check_load_time()',
                )
            );

            $expired_date = (int)$this->selectExpiredTime();

            if (($expired_date!='0')) {
                $form->addElement('html','<div id="timercontrol" style="display:block;">');
            } else {
                $form->addElement('html','<div id="timercontrol" style="display:none;">');
            }
            $form->addText(
                'enabletimercontroltotalminutes',
                get_lang('ExerciseTotalDurationInMinutes'),
                false,
                [
                    'id' => 'enabletimercontroltotalminutes',
                    'cols-size' => [2, 2, 8]
                ]
            );
            $form->addElement('html','</div>');

            $form->addElement(
                'text',
                'pass_percentage',
                array(get_lang('PassPercentage'), null, '%'),
                array('id' => 'pass_percentage')
            );

            $form->addRule('pass_percentage', get_lang('Numeric'), 'numeric');
            $form->addRule('pass_percentage', get_lang('ValueTooSmall'), 'min_numeric_length', 0);
            $form->addRule('pass_percentage', get_lang('ValueTooBig'), 'max_numeric_length', 100);

            // add the text_when_finished textbox
            $form->addHtmlEditor(
                'text_when_finished',
                get_lang('TextWhenFinished'),
                false,
                false,
                $editor_config
            );

            $defaults = array();

            if (api_get_setting('search_enabled') === 'true') {
                require_once api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php';

                $form->addElement('checkbox', 'index_document', '', get_lang('SearchFeatureDoIndexDocument'));
                $form->addElement('select_language', 'language', get_lang('SearchFeatureDocumentLanguage'));

                $specific_fields = get_specific_field_list();

                foreach ($specific_fields as $specific_field) {
                    $form->addElement ('text', $specific_field['code'], $specific_field['name']);
                    $filter = array(
                        'c_id' => api_get_course_int_id(),
                        'field_id' => $specific_field['id'],
                        'ref_id' => $this->id,
                        'tool_id' => "'" . TOOL_QUIZ . "'"
                    );
                    $values = get_specific_field_values_list($filter, array('value'));
                    if ( !empty($values) ) {
                        $arr_str_values = array();
                        foreach ($values as $value) {
                            $arr_str_values[] = $value['value'];
                        }
                        $defaults[$specific_field['code']] = implode(', ', $arr_str_values);
                    }
                }
            }

            $form->addElement('html','</div>');  //End advanced setting
            $form->addElement('html','</div>');
        }

        // submit
        if (isset($_GET['exerciseId'])) {
            $form->addButtonSave(get_lang('ModifyExercise'), 'submitExercise');
        } else {
            $form->addButtonUpdate(get_lang('ProcedToQuestions'), 'submitExercise');
        }

        $form->addRule('exerciseTitle', get_lang('GiveExerciseName'), 'required');

        if ($type == 'full') {
            // rules
            $form->addRule('exerciseAttempts', get_lang('Numeric'), 'numeric');
            $form->addRule('start_time', get_lang('InvalidDate'), 'datetime');
            $form->addRule('end_time', get_lang('InvalidDate'), 'datetime');
        }

        // defaults
        if ($type=='full') {
            if ($this->id > 0) {
                if ($this->random > $this->selectNbrQuestions()) {
                    $defaults['randomQuestions'] =  $this->selectNbrQuestions();
                } else {
                    $defaults['randomQuestions'] = $this->random;
                }

                $defaults['randomAnswers'] = $this->selectRandomAnswers();
                $defaults['exerciseType'] = $this->selectType();
                $defaults['exerciseTitle'] = $this->get_formated_title();
                $defaults['exerciseDescription'] = $this->selectDescription();
                $defaults['exerciseAttempts'] = $this->selectAttempts();
                $defaults['exerciseFeedbackType'] = $this->selectFeedbackType();
                $defaults['results_disabled'] = $this->selectResultsDisabled();
                $defaults['propagate_neg'] = $this->selectPropagateNeg();
                $defaults['save_correct_answers'] = $this->selectSaveCorrectAnswers();
                $defaults['review_answers'] = $this->review_answers;
                $defaults['randomByCat'] = $this->selectRandomByCat();
                $defaults['text_when_finished'] = $this->selectTextWhenFinished();
                $defaults['display_category_name'] = $this->selectDisplayCategoryName();
                $defaults['pass_percentage'] = $this->selectPassPercentage();
                $defaults['question_selection_type'] = $this->getQuestionSelectionType();
                $defaults['hide_question_title'] = $this->getHideQuestionTitle();

                if (!empty($this->start_time)) {
                    $defaults['activate_start_date_check'] = 1;
                }
                if (!empty($this->end_time)) {
                    $defaults['activate_end_date_check'] = 1;
                }

                $defaults['start_time'] = !empty($this->start_time) ? api_get_local_time($this->start_time) : date('Y-m-d 12:00:00');
                $defaults['end_time'] = empty($this->end_time) ? api_get_local_time($this->end_time) : date('Y-m-d 12:00:00', time()+84600);

                // Get expired time
                if ($this->expired_time != '0') {
                    $defaults['enabletimercontrol'] = 1;
                    $defaults['enabletimercontroltotalminutes'] = $this->expired_time;
                } else {
                    $defaults['enabletimercontroltotalminutes'] = 0;
                }
            } else {
                $defaults['exerciseType'] = 2;
                $defaults['exerciseAttempts'] = 0;
                $defaults['randomQuestions'] = 0;
                $defaults['randomAnswers'] = 0;
                $defaults['exerciseDescription'] = '';
                $defaults['exerciseFeedbackType'] = 0;
                $defaults['results_disabled'] = 0;
                $defaults['randomByCat'] = 0;
                $defaults['text_when_finished'] = '';
                $defaults['start_time'] = date('Y-m-d 12:00:00');
                $defaults['display_category_name'] = 1;
                $defaults['end_time']   = date('Y-m-d 12:00:00', time()+84600);
                $defaults['pass_percentage'] = '';
                $defaults['end_button'] = $this->selectEndButton();
                $defaults['question_selection_type'] = 1;
                $defaults['hide_question_title'] = 0;
                $defaults['on_success_message'] = null;
                $defaults['on_failed_message'] = null;
            }
        } else {
            $defaults['exerciseTitle'] = $this->selectTitle();
            $defaults['exerciseDescription'] = $this->selectDescription();
        }
        if (api_get_setting('search_enabled') === 'true') {
            $defaults['index_document'] = 'checked="checked"';
        }
        $form->setDefaults($defaults);

        // Freeze some elements.
        if ($this->id != 0 && $this->edit_exercise_in_lp == false) {
            $elementsToFreeze = array(
                'randomQuestions',
                //'randomByCat',
                'exerciseAttempts',
                'propagate_neg',
                'enabletimercontrol',
                'review_answers'
            );

            foreach ($elementsToFreeze as $elementName) {
                /** @var HTML_QuickForm_element $element */
                $element = $form->getElement($elementName);
                $element->freeze();
            }

            //$radioCatGroup->freeze();
        }
    }

    /**
     * function which process the creation of exercises
     * @param FormValidator $form
     * @param string
     */
    public function processCreation($form, $type = '')
    {
        $this->updateTitle(Exercise::format_title_variable($form->getSubmitValue('exerciseTitle')));
        $this->updateDescription($form->getSubmitValue('exerciseDescription'));
        $this->updateAttempts($form->getSubmitValue('exerciseAttempts'));
        $this->updateFeedbackType($form->getSubmitValue('exerciseFeedbackType'));
        $this->updateType($form->getSubmitValue('exerciseType'));
        $this->setRandom($form->getSubmitValue('randomQuestions'));
        $this->updateRandomAnswers($form->getSubmitValue('randomAnswers'));
        $this->updateResultsDisabled($form->getSubmitValue('results_disabled'));
        $this->updateExpiredTime($form->getSubmitValue('enabletimercontroltotalminutes'));
        $this->updatePropagateNegative($form->getSubmitValue('propagate_neg'));
        $this->updateSaveCorrectAnswers($form->getSubmitValue('save_correct_answers'));
        $this->updateRandomByCat($form->getSubmitValue('randomByCat'));
        $this->updateTextWhenFinished($form->getSubmitValue('text_when_finished'));
        $this->updateDisplayCategoryName($form->getSubmitValue('display_category_name'));
        $this->updateReviewAnswers($form->getSubmitValue('review_answers'));
        $this->updatePassPercentage($form->getSubmitValue('pass_percentage'));
        $this->updateCategories($form->getSubmitValue('category'));
        $this->updateEndButton($form->getSubmitValue('end_button'));
        $this->setOnSuccessMessage($form->getSubmitValue('on_success_message'));
        $this->setOnFailedMessage($form->getSubmitValue('on_failed_message'));
        $this->updateEmailNotificationTemplate($form->getSubmitValue('email_notification_template'));
        $this->updateEmailNotificationTemplateToUser($form->getSubmitValue('email_notification_template_to_user'));
        $this->setNotifyUserByEmail($form->getSubmitValue('notify_user_by_email'));
        $this->setModelType($form->getSubmitValue('model_type'));
        $this->setQuestionSelectionType($form->getSubmitValue('question_selection_type'));
        $this->setHideQuestionTitle($form->getSubmitValue('hide_question_title'));
        $this->sessionId = api_get_session_id();
        $this->setQuestionSelectionType($form->getSubmitValue('question_selection_type'));
        $this->setScoreTypeModel($form->getSubmitValue('score_type_model'));
        $this->setGlobalCategoryId($form->getSubmitValue('global_category_id'));

        if ($form->getSubmitValue('activate_start_date_check') == 1) {
            $start_time = $form->getSubmitValue('start_time');
            $this->start_time = api_get_utc_datetime($start_time);
        } else {
            $this->start_time = null;
        }

        if ($form->getSubmitValue('activate_end_date_check') == 1) {
            $end_time = $form->getSubmitValue('end_time');
            $this->end_time = api_get_utc_datetime($end_time);
        } else {
            $this->end_time = null;
        }

        if ($form->getSubmitValue('enabletimercontrol') == 1) {
            $expired_total_time = $form->getSubmitValue('enabletimercontroltotalminutes');
            if ($this->expired_time == 0) {
                $this->expired_time = $expired_total_time;
            }
        } else {
            $this->expired_time = 0;
        }

        if ($form->getSubmitValue('randomAnswers') == 1) {
            $this->random_answers=1;
        } else {
            $this->random_answers=0;
        }
        $this->save($type);
    }

    function search_engine_save()
    {
        if ($_POST['index_document'] != 1) {
            return;
        }
        $course_id = api_get_course_id();

        require_once api_get_path(LIBRARY_PATH) . 'search/ChamiloIndexer.class.php';
        require_once api_get_path(LIBRARY_PATH) . 'search/IndexableChunk.class.php';
        require_once api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php';

        $specific_fields = get_specific_field_list();
        $ic_slide = new IndexableChunk();

        $all_specific_terms = '';
        foreach ($specific_fields as $specific_field) {
            if (isset($_REQUEST[$specific_field['code']])) {
                $sterms = trim($_REQUEST[$specific_field['code']]);
                if (!empty($sterms)) {
                    $all_specific_terms .= ' '. $sterms;
                    $sterms = explode(',', $sterms);
                    foreach ($sterms as $sterm) {
                        $ic_slide->addTerm(trim($sterm), $specific_field['code']);
                        add_specific_field_value($specific_field['id'], $course_id, TOOL_QUIZ, $this->id, $sterm);
                    }
                }
            }
        }

        // build the chunk to index
        $ic_slide->addValue("title", $this->exercise);
        $ic_slide->addCourseId($course_id);
        $ic_slide->addToolId(TOOL_QUIZ);
        $xapian_data = array(
            SE_COURSE_ID => $course_id,
            SE_TOOL_ID => TOOL_QUIZ,
            SE_DATA => array('type' => SE_DOCTYPE_EXERCISE_EXERCISE, 'exercise_id' => (int)$this->id),
            SE_USER => (int)api_get_user_id(),
        );
        $ic_slide->xapian_data = serialize($xapian_data);
        $exercise_description = $all_specific_terms .' '. $this->description;
        $ic_slide->addValue("content", $exercise_description);

        $di = new ChamiloIndexer();
        isset($_POST['language'])? $lang=Database::escape_string($_POST['language']): $lang = 'english';
        $di->connectDb(NULL, NULL, $lang);
        $di->addChunk($ic_slide);

        //index and return search engine document id
        $did = $di->index();
        if ($did) {
            // save it to db
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            $sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, search_did)
			    VALUES (NULL , \'%s\', \'%s\', %s, %s)';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id, $did);
            Database::query($sql);
        }
    }

    function search_engine_edit()
    {
        // update search enchine and its values table if enabled
        if (api_get_setting('search_enabled')=='true' && extension_loaded('xapian')) {
            $course_id = api_get_course_id();

            // actually, it consists on delete terms from db,
            // insert new ones, create a new search engine document, and remove the old one
            // get search_did
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
            $res = Database::query($sql);

            if (Database::num_rows($res) > 0) {
                require_once(api_get_path(LIBRARY_PATH) . 'search/ChamiloIndexer.class.php');
                require_once(api_get_path(LIBRARY_PATH) . 'search/IndexableChunk.class.php');
                require_once(api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php');

                $se_ref = Database::fetch_array($res);
                $specific_fields = get_specific_field_list();
                $ic_slide = new IndexableChunk();

                $all_specific_terms = '';
                foreach ($specific_fields as $specific_field) {
                    delete_all_specific_field_value($course_id, $specific_field['id'], TOOL_QUIZ, $this->id);
                    if (isset($_REQUEST[$specific_field['code']])) {
                        $sterms = trim($_REQUEST[$specific_field['code']]);
                        $all_specific_terms .= ' '. $sterms;
                        $sterms = explode(',', $sterms);
                        foreach ($sterms as $sterm) {
                            $ic_slide->addTerm(trim($sterm), $specific_field['code']);
                            add_specific_field_value($specific_field['id'], $course_id, TOOL_QUIZ, $this->id, $sterm);
                        }
                    }
                }

                // build the chunk to index
                $ic_slide->addValue("title", $this->exercise);
                $ic_slide->addCourseId($course_id);
                $ic_slide->addToolId(TOOL_QUIZ);
                $xapian_data = array(
                    SE_COURSE_ID => $course_id,
                    SE_TOOL_ID => TOOL_QUIZ,
                    SE_DATA => array('type' => SE_DOCTYPE_EXERCISE_EXERCISE, 'exercise_id' => (int)$this->id),
                    SE_USER => (int)api_get_user_id(),
                );
                $ic_slide->xapian_data = serialize($xapian_data);
                $exercise_description = $all_specific_terms .' '. $this->description;
                $ic_slide->addValue("content", $exercise_description);

                $di = new ChamiloIndexer();
                isset($_POST['language'])? $lang=Database::escape_string($_POST['language']): $lang = 'english';
                $di->connectDb(NULL, NULL, $lang);
                $di->remove_document((int)$se_ref['search_did']);
                $di->addChunk($ic_slide);

                //index and return search engine document id
                $did = $di->index();
                if ($did) {
                    // save it to db
                    $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=\'%s\'';
                    $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
                    Database::query($sql);
                    $sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, search_did)
                        VALUES (NULL , \'%s\', \'%s\', %s, %s)';
                    $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id, $did);
                    Database::query($sql);
                }
            } else {
                $this->search_engine_save();
            }
        }

    }

    function search_engine_delete()
    {
        // remove from search engine if enabled
        if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian') ) {
            $course_id = api_get_course_id();
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level IS NULL LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $row = Database::fetch_array($res);
                require_once(api_get_path(LIBRARY_PATH) .'search/ChamiloIndexer.class.php');
                $di = new ChamiloIndexer();
                $di->remove_document((int)$row['search_did']);
                unset($di);
                $tbl_quiz_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
                foreach ( $this->questionList as $question_i) {
                    $sql = 'SELECT type FROM %s WHERE id=%s';
                    $sql = sprintf($sql, $tbl_quiz_question, $question_i);
                    $qres = Database::query($sql);
                    if (Database::num_rows($qres) > 0) {
                        $qrow = Database::fetch_array($qres);
                        $objQuestion = Question::getInstance($qrow['type']);
                        $objQuestion = Question::read((int)$question_i);
                        $objQuestion->search_engine_edit($this->id, FALSE, TRUE);
                        unset($objQuestion);
                    }
                }
            }
            $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level IS NULL LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
            Database::query($sql);

            // remove terms from db
            require_once api_get_path(LIBRARY_PATH) .'specific_fields_manager.lib.php';
            delete_all_values_for_item($course_id, TOOL_QUIZ, $this->id);
        }
    }

    public function selectExpiredTime()
    {
        return $this->expired_time;
    }

    /**
     * Cleans the student's results only for the Exercise tool (Not from the LP)
     * The LP results are NOT deleted by default, otherwise put $cleanLpTests = true
     * Works with exercises in sessions
     * @param bool $cleanLpTests
     * @param string $cleanResultBeforeDate
     *
     * @return int quantity of user's exercises deleted
     */
    public function clean_results($cleanLpTests = false, $cleanResultBeforeDate = null)
    {
        $table_track_e_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $table_track_e_attempt   = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $sql_where = '  AND
                        orig_lp_id = 0 AND
                        orig_lp_item_id = 0';

        // if we want to delete results from LP too
        if ($cleanLpTests) {
            $sql_where = "";
        }

        // if we want to delete attempts before date $cleanResultBeforeDate
        // $cleanResultBeforeDate must be a valid UTC-0 date yyyy-mm-dd

        if (!empty($cleanResultBeforeDate)) {
            $cleanResultBeforeDate = Database::escape_string($cleanResultBeforeDate);
            if (api_is_valid_date($cleanResultBeforeDate)) {
                $sql_where .= "  AND exe_date <= '$cleanResultBeforeDate' ";
            } else {
                return 0;
            }
        }

        $sql = "SELECT exe_id
                FROM $table_track_e_exercises
                WHERE
                    c_id = ".api_get_course_int_id()." AND
                    exe_exo_id = ".$this->id." AND
                    session_id = ".api_get_session_id()." ".
                    $sql_where;

        $result   = Database::query($sql);
        $exe_list = Database::store_result($result);

        // deleting TRACK_E_ATTEMPT table
        // check if exe in learning path or not
        $i = 0;
        if (is_array($exe_list) && count($exe_list) > 0) {
            foreach ($exe_list as $item) {
                $sql = "DELETE FROM $table_track_e_attempt
                        WHERE exe_id = '".$item['exe_id']."'";
                Database::query($sql);
                $i++;
            }
        }

        $session_id = api_get_session_id();
        // delete TRACK_E_EXERCISES table
        $sql = "DELETE FROM $table_track_e_exercises
                WHERE c_id = ".api_get_course_int_id()."
                AND exe_exo_id = ".$this->id."
                $sql_where
                AND session_id = ".$session_id."";
        Database::query($sql);

        Event::addEvent(
            LOG_EXERCISE_RESULT_DELETE,
            LOG_EXERCISE_ID,
            $this->id,
            null,
            null,
            api_get_course_int_id(),
            $session_id
        );

        return $i;
    }

    /**
     * Copies an exercise (duplicate all questions and answers)
     */
    public function copy_exercise()
    {
        $exercise_obj = $this;

        // force the creation of a new exercise
        $exercise_obj->updateTitle($exercise_obj->selectTitle().' - '.get_lang('Copy'));
        //Hides the new exercise
        $exercise_obj->updateStatus(false);
        $exercise_obj->updateId(0);
        $exercise_obj->save();

        $new_exercise_id = $exercise_obj->selectId();
        $question_list 	 = $exercise_obj->selectQuestionList();

        if (!empty($question_list)) {
            //Question creation

            foreach ($question_list as $old_question_id) {
                $old_question_obj = Question::read($old_question_id);
                $new_id = $old_question_obj->duplicate();
                if ($new_id) {
                    $new_question_obj = Question::read($new_id);

                    if (isset($new_question_obj) && $new_question_obj) {
                        $new_question_obj->addToList($new_exercise_id);
                        // This should be moved to the duplicate function
                        $new_answer_obj = new Answer($old_question_id);
                        $new_answer_obj->read();
                        $new_answer_obj->duplicate($new_id);
                    }
                }
            }
        }
    }

    /**
     * Changes the exercise id
     *
     * @param int $id - exercise id
     */
    private function updateId($id)
    {
        $this->id = $id;
    }

    /**
     * Changes the exercise status
     *
     * @param string $status - exercise status
     */
    function updateStatus($status)
    {
        $this->active = $status;
    }

    /**
     * @param int $lp_id
     * @param int $lp_item_id
     * @param int $lp_item_view_id
     * @param string $status
     * @return array
     */
    public function get_stat_track_exercise_info(
        $lp_id = 0,
        $lp_item_id = 0,
        $lp_item_view_id = 0,
        $status = 'incomplete'
    ) {
        $track_exercises = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        if (empty($lp_id)) {
            $lp_id = 0;
        }
        if (empty($lp_item_id)) {
            $lp_item_id   = 0;
        }
        if (empty($lp_item_view_id)) {
            $lp_item_view_id = 0;
        }
        $condition = ' WHERE exe_exo_id 	= ' . "'" . $this->id . "'" .' AND
					   exe_user_id 			= ' . "'" . api_get_user_id() . "'" . ' AND
					   c_id                 = ' . api_get_course_int_id() . ' AND
					   status 				= ' . "'" . Database::escape_string($status). "'" . ' AND
					   orig_lp_id 			= ' . "'" . $lp_id . "'" . ' AND
					   orig_lp_item_id 		= ' . "'" . $lp_item_id . "'" . ' AND
                       orig_lp_item_view_id = ' . "'" . $lp_item_view_id . "'" . ' AND
					   session_id 			= ' . "'" . api_get_session_id() . "' LIMIT 1"; //Adding limit 1 just in case

        $sql_track = 'SELECT * FROM '.$track_exercises.$condition;

        $result = Database::query($sql_track);
        $new_array = array();
        if (Database::num_rows($result) > 0 ) {
            $new_array = Database::fetch_array($result, 'ASSOC');
            $new_array['num_exe'] = Database::num_rows($result);
        }

        return $new_array;
    }

    /**
     * Saves a test attempt
     *
     * @param int  clock_expired_time
     * @param int  int lp id
     * @param int  int lp item id
     * @param int  int lp item_view id
     * @param float $weight
     * @param array question list
     */
    public function save_stat_track_exercise_info(
        $clock_expired_time = 0,
        $safe_lp_id = 0,
        $safe_lp_item_id = 0,
        $safe_lp_item_view_id = 0,
        $questionList = array(),
        $weight = 0
    ) {
        $track_exercises = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $safe_lp_id = intval($safe_lp_id);
        $safe_lp_item_id = intval($safe_lp_item_id);
        $safe_lp_item_view_id = intval($safe_lp_item_view_id);

        if (empty($safe_lp_id)) {
            $safe_lp_id = 0;
        }
        if (empty($safe_lp_item_id)) {
            $safe_lp_item_id = 0;
        }
        if (empty($clock_expired_time)) {
            $clock_expired_time = null;
        }

        $questionList = array_map('intval', $questionList);

        $params = array(
            'exe_exo_id' => $this->id ,
            'exe_user_id' => api_get_user_id(),
            'c_id' => api_get_course_int_id(),
            'status' =>  'incomplete',
            'session_id'  => api_get_session_id(),
            'data_tracking'  => implode(',', $questionList) ,
            'start_date' => api_get_utc_datetime(),
            'orig_lp_id' => $safe_lp_id,
            'orig_lp_item_id'  => $safe_lp_item_id,
            'orig_lp_item_view_id'  => $safe_lp_item_view_id,
            'exe_weighting'=> $weight,
            'user_ip' => api_get_real_ip(),
            'exe_date' => api_get_utc_datetime(),
            'exe_result' => 0,
            'steps_counter' => 0,
            'exe_duration' => 0,
            'expired_time_control' => $clock_expired_time,
            'questions_to_check' => ''
        );

        $id = Database::insert($track_exercises, $params);

        return $id;
    }

    /**
     * @param int $question_id
     * @param int $questionNum
     * @param array $questions_in_media
     * @param string $currentAnswer
     * @return string
     */
    public function show_button($question_id, $questionNum, $questions_in_media = array(), $currentAnswer = '')
    {
        global $origin, $safe_lp_id, $safe_lp_item_id, $safe_lp_item_view_id;

        $nbrQuestions = $this->get_count_question_list();

        $all_button = $html = $label = '';
        $hotspot_get = isset($_POST['hotspot']) ? Security::remove_XSS($_POST['hotspot']):null;

        if ($this->selectFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT && $this->type == ONE_PER_PAGE) {
            $urlTitle = get_lang('ContinueTest');

            if ($questionNum == count($this->questionList)) {
                $urlTitle = get_lang('EndTest');
            }

            $html .= Display::url(
                $urlTitle,
                'exercise_submit_modal.php?' . http_build_query([
                    'learnpath_id' => $safe_lp_id,
                    'learnpath_item_id' => $safe_lp_item_id,
                    'learnpath_item_view_id' => $safe_lp_item_view_id,
                    'origin' => $origin,
                    'hotspot' => $hotspot_get,
                    'nbrQuestions' => $nbrQuestions,
                    'num' => $questionNum,
                    'exerciseType' => $this->type,
                    'exerciseId' => $this->id
                ]),
                [
                    'class' => 'ajax btn btn-default',
                    'data-title' => $urlTitle,
                    'data-size' => 'md'
                ]
            );
            $html .='<br />';
        } else {
            // User
            if (api_is_allowed_to_session_edit()) {
                if ($this->type == ALL_ON_ONE_PAGE || $nbrQuestions == $questionNum) {
                    if ($this->review_answers) {
                        $label = get_lang('ReviewQuestions');
                        $class = 'btn btn-success';
                    } else {
                        $label = get_lang('EndTest');
                        $class = 'btn btn-warning';
                    }
                } else {
                    $label = get_lang('NextQuestion');
                    $class = 'btn btn-primary';
                }
				$class .= ' question-validate-btn'; // used to select it with jquery
                if ($this->type == ONE_PER_PAGE) {
                    if ($questionNum != 1) {
                        $prev_question = $questionNum - 2;
                        $all_button .= '<a href="javascript://" class="btn btn-default" onclick="previous_question_and_save('.$prev_question.', '.$question_id.' ); ">'.get_lang('PreviousQuestion').'</a>';
                    }

                    //Next question
                    if (!empty($questions_in_media)) {
                        $questions_in_media = "['".implode("','",$questions_in_media)."']";
                        $all_button .= '&nbsp;<a href="javascript://" class="'.$class.'" onclick="save_question_list('.$questions_in_media.'); ">'.$label.'</a>';
                    } else {
                        $all_button .= '&nbsp;<a href="javascript://" class="'.$class.'" onclick="save_now('.$question_id.', \'\', \''.$currentAnswer.'\'); ">'.$label.'</a>';
                    }
                    $all_button .= '<span id="save_for_now_'.$question_id.'" class="exercise_save_mini_message"></span>&nbsp;';

                    $html .= $all_button;
                } else {
                    if ($this->review_answers) {
                        $all_label = get_lang('ReviewQuestions');
                        $class = 'btn btn-success';
                    } else {
                        $all_label = get_lang('EndTest');
                        $class = 'btn btn-warning';
                    }
					$class .= ' question-validate-btn'; // used to select it with jquery
                    $all_button = '&nbsp;<a href="javascript://" class="'.$class.'" onclick="validate_all(); ">'.$all_label.'</a>';
                    $all_button .= '&nbsp;' . Display::span(null, ['id' => 'save_all_reponse']);
                    $html .= $all_button;
                }
            }
        }

        return $html;
    }

    /**
     * So the time control will work
     *
     * @param string $time_left
     * @return string
     */
    public function show_time_control_js($time_left)
    {
        $time_left = intval($time_left);
        return "<script>

            function get_expired_date_string(expired_time) {
                var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                var day, month, year, hours, minutes, seconds, date_string;
                var obj_date = new Date(expired_time);
                day     = obj_date.getDate();
                if (day < 10) day = '0' + day;
                    month   = obj_date.getMonth();
                    year    = obj_date.getFullYear();
                    hours   = obj_date.getHours();
                if (hours < 10) hours = '0' + hours;
                minutes = obj_date.getMinutes();
                if (minutes < 10) minutes = '0' + minutes;
                seconds = obj_date.getSeconds();
                if (seconds < 10) seconds = '0' + seconds;
                date_string = months[month] +' ' + day + ', ' + year + ' ' + hours + ':' + minutes + ':' + seconds;
                return date_string;
            }

            function open_clock_warning() {
                $('#clock_warning').dialog({
                    modal:true,
                    height:250,
                    closeOnEscape: false,
                    resizable: false,
                    buttons: {
                        '".addslashes(get_lang("EndTest"))."': function() {
                            $('#clock_warning').dialog('close');
                        }
                    },
                    close: function() {
                        send_form();
                    }
                });
                $('#clock_warning').dialog('open');

                $('#counter_to_redirect').epiclock({
                    mode: $.epiclock.modes.countdown,
                    offset: {seconds: 5},
                    format: 's'
                }).bind('timer', function () {
                    send_form();
                });

            }

            function send_form() {
                if ($('#exercise_form').length) {
                    $('#exercise_form').submit();
                } else {
                    //In reminder
                    final_submit();
                }
            }

            function onExpiredTimeExercise() {
                $('#wrapper-clock').hide();
                $('#exercise_form').hide();
                $('#expired-message-id').show();

                //Fixes bug #5263
                $('#num_current_id').attr('value', '".$this->selectNbrQuestions()."');
                open_clock_warning();
            }

			$(document).ready(function() {

				var current_time = new Date().getTime();
                var time_left    = parseInt(".$time_left."); // time in seconds when using minutes there are some seconds lost
				var expired_time = current_time + (time_left*1000);
				var expired_date = get_expired_date_string(expired_time);

                $('#exercise_clock_warning').epiclock({
                    mode: $.epiclock.modes.countdown,
                    offset: {seconds: time_left},
                    format: 'x:i:s',
                    renderer: 'minute'
                }).bind('timer', function () {
                    onExpiredTimeExercise();
                });
	       		$('#submit_save').click(function () {});
	    });
	    </script>";
    }

    /**
     * Lp javascript for hotspots
     */
    public function show_lp_javascript()
    {
        return '';
    }

    /**
     * This function was originally found in the exercise_show.php
     * @param int       $exeId
     * @param int       $questionId
     * @param int       $choice the user selected
     * @param string    $from  function is called from 'exercise_show' or 'exercise_result'
     * @param array     $exerciseResultCoordinates the hotspot coordinates $hotspot[$question_id] = coordinates
     * @param bool      $saved_results save results in the DB or just show the reponse
     * @param bool      $from_database gets information from DB or from the current selection
     * @param bool      $show_result show results or not
     * @param int       $propagate_neg
     * @param array     $hotspot_delineation_result
     * @param boolean $showTotalScoreAndUserChoicesInLastAttempt
     * @todo    reduce parameters of this function
     * @return  string  html code
     */
    public function manage_answer(
        $exeId,
        $questionId,
        $choice,
        $from = 'exercise_show',
        $exerciseResultCoordinates = array(),
        $saved_results = true,
        $from_database = false,
        $show_result = true,
        $propagate_neg = 0,
        $hotspot_delineation_result = array(),
        $showTotalScoreAndUserChoicesInLastAttempt = true
    ) {
        global $debug;
        //needed in order to use in the exercise_attempt() for the time
        global $learnpath_id, $learnpath_item_id;
        require_once api_get_path(LIBRARY_PATH).'geometry.lib.php';

        $em = Database::getManager();

        $feedback_type = $this->selectFeedbackType();
        $results_disabled = $this->selectResultsDisabled();

        if ($debug) {
            error_log("<------ manage_answer ------> ");
            error_log('exe_id: '.$exeId);
            error_log('$from:  '.$from);
            error_log('$saved_results: '.intval($saved_results));
            error_log('$from_database: '.intval($from_database));
            error_log('$show_result: '.$show_result);
            error_log('$propagate_neg: '.$propagate_neg);
            error_log('$exerciseResultCoordinates: '.print_r($exerciseResultCoordinates, 1));
            error_log('$hotspot_delineation_result: '.print_r($hotspot_delineation_result, 1));
            error_log('$learnpath_id: '.$learnpath_id);
            error_log('$learnpath_item_id: '.$learnpath_item_id);
            error_log('$choice: '.print_r($choice, 1));
        }

        $extra_data = array();
        $final_overlap = 0;
        $final_missing = 0;
        $final_excess = 0;
        $overlap_color = 0;
        $missing_color = 0;
        $excess_color = 0;
        $threadhold1 = 0;
        $threadhold2 = 0;
        $threadhold3 = 0;

        $arrques = null;
        $arrans  = null;

        $questionId = intval($questionId);
        $exeId = intval($exeId);
        $TBL_TRACK_ATTEMPT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $table_ans = Database::get_course_table(TABLE_QUIZ_ANSWER);

        // Creates a temporary Question object
        $course_id = $this->course_id;
        $objQuestionTmp = Question::read($questionId, $course_id);

        if ($objQuestionTmp === false) {
            return false;
        }

        $questionName = $objQuestionTmp->selectTitle();
        $questionWeighting = $objQuestionTmp->selectWeighting();
        $answerType = $objQuestionTmp->selectType();
        $quesId = $objQuestionTmp->selectId();
        $extra = $objQuestionTmp->extra;

        $next = 1; //not for now

        // Extra information of the question
        if (!empty($extra)) {
            $extra = explode(':', $extra);
            if ($debug) {
                error_log(print_r($extra, 1));
            }
            // Fixes problems with negatives values using intval
            $true_score = floatval(trim($extra[0]));
            $false_score = floatval(trim($extra[1]));
            $doubt_score = floatval(trim($extra[2]));
        }

        $totalWeighting = 0;
        $totalScore = 0;

        // Construction of the Answer object
        $objAnswerTmp = new Answer($questionId);
        $nbrAnswers = $objAnswerTmp->selectNbrAnswers();

        if ($debug) {
            error_log('Count of answers: '.$nbrAnswers);
            error_log('$answerType: '.$answerType);
        }

        if ($answerType == FREE_ANSWER ||
            $answerType == ORAL_EXPRESSION ||
            $answerType == CALCULATED_ANSWER
        ) {
            $nbrAnswers = 1;
        }

        if ($answerType == ORAL_EXPRESSION) {
            $exe_info = Event::get_exercise_results_by_attempt($exeId);
            $exe_info = isset($exe_info[$exeId]) ? $exe_info[$exeId] : null;

            $objQuestionTmp->initFile(
                api_get_session_id(),
                isset($exe_info['exe_user_id']) ? $exe_info['exe_user_id'] : api_get_user_id(),
                isset($exe_info['exe_exo_id']) ? $exe_info['exe_exo_id'] : $this->id,
                isset($exe_info['exe_id']) ? $exe_info['exe_id'] : $exeId
            );

            //probably this attempt came in an exercise all question by page
            if ($feedback_type == 0) {
                $objQuestionTmp->replaceWithRealExe($exeId);
            }
        }

        $user_answer = '';

        // Get answer list for matching
        $sql = "SELECT id_auto, id, answer
                FROM $table_ans
                WHERE c_id = $course_id AND question_id = $questionId";
        $res_answer = Database::query($sql);

        $answerMatching = array();
        while ($real_answer = Database::fetch_array($res_answer)) {
            $answerMatching[$real_answer['id_auto']] = $real_answer['answer'];
        }

        $real_answers = array();
        $quiz_question_options = Question::readQuestionOption(
            $questionId,
            $course_id
        );

        $organs_at_risk_hit = 0;
        $questionScore = 0;
        $answer_correct_array = array();
        $orderedHotspots = [];

        if ($answerType == HOT_SPOT) {
            $orderedHotspots = $em
                ->getRepository('ChamiloCoreBundle:TrackEHotspot')
                ->findBy([
                        'hotspotQuestionId' => $questionId,
                        'cId' => $course_id,
                        'hotspotExeId' => $exeId
                    ],
                    ['hotspotId' => 'ASC']
                );
        }

        if ($debug) error_log('Start answer loop ');

        for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
            $answer = $objAnswerTmp->selectAnswer($answerId);
            $answerComment = $objAnswerTmp->selectComment($answerId);
            $answerCorrect = $objAnswerTmp->isCorrect($answerId);
            $answerWeighting = (float)$objAnswerTmp->selectWeighting($answerId);
            $answerAutoId = $objAnswerTmp->selectAutoId($answerId);
            $answerIid = isset($objAnswerTmp->iid[$answerId]) ? $objAnswerTmp->iid[$answerId] : '';

            $answer_correct_array[$answerId] = (bool)$answerCorrect;

            if ($debug) {
                error_log("answer auto id: $answerAutoId ");
                error_log("answer correct: $answerCorrect ");
            }

            // Delineation
            $delineation_cord = $objAnswerTmp->selectHotspotCoordinates(1);
            $answer_delineation_destination=$objAnswerTmp->selectDestination(1);

            switch ($answerType) {
                // for unique answer
                case UNIQUE_ANSWER:
                case UNIQUE_ANSWER_IMAGE:
                case UNIQUE_ANSWER_NO_OPTION:
                    if ($from_database) {
                        $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                WHERE
                                    exe_id = '".$exeId."' AND
                                    question_id= '".$questionId."'";
                        $result = Database::query($sql);
                        $choice = Database::result($result,0,"answer");

                        $studentChoice = $choice == $answerAutoId ? 1 : 0;
                        if ($studentChoice) {
                            $questionScore += $answerWeighting;
                            $totalScore += $answerWeighting;
                        }
                    } else {
                        $studentChoice = $choice == $answerAutoId ? 1 : 0;
                        if ($studentChoice) {
                            $questionScore += $answerWeighting;
                            $totalScore += $answerWeighting;
                        }
                    }
                    break;
                // for multiple answers
                case MULTIPLE_ANSWER_TRUE_FALSE:
                    if ($from_database) {
                        $choice = array();
                        $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                WHERE
                                    exe_id = $exeId AND
                                    question_id = ".$questionId;

                        $result = Database::query($sql);
                        while ($row = Database::fetch_array($result)) {
                            $ind = $row['answer'];
                            $values = explode(':', $ind);
                            $my_answer_id = isset($values[0]) ? $values[0] : '';
                            $option = isset($values[1]) ? $values[1] : '';
                            $choice[$my_answer_id] = $option;
                        }
                    }

                    $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;

                    if (!empty($studentChoice)) {
                        if ($studentChoice == $answerCorrect) {
                            $questionScore += $true_score;
                        } else {
                            if ($quiz_question_options[$studentChoice]['name'] == "Don't know" ||
                                $quiz_question_options[$studentChoice]['name'] == "DoubtScore"
                            ) {
                                $questionScore += $doubt_score;
                            } else {
                                $questionScore += $false_score;
                            }
                        }
                    } else {
                        // If no result then the user just hit don't know
                        $studentChoice = 3;
                        $questionScore  +=  $doubt_score;
                    }
                    $totalScore = $questionScore;
                    break;
                case MULTIPLE_ANSWER: //2
                    if ($from_database) {
                        $choice = array();
                        $sql = "SELECT answer FROM ".$TBL_TRACK_ATTEMPT."
                                WHERE exe_id = '".$exeId."' AND question_id= '".$questionId."'";
                        $resultans = Database::query($sql);
                        while ($row = Database::fetch_array($resultans)) {
                            $ind = $row['answer'];
                            $choice[$ind] = 1;
                        }

                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;
                        $real_answers[$answerId] = (bool)$studentChoice;

                        if ($studentChoice) {
                            $questionScore  +=$answerWeighting;
                        }
                    } else {
                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;
                        $real_answers[$answerId] = (bool)$studentChoice;

                        if (isset($studentChoice)) {
                            $questionScore  += $answerWeighting;
                        }
                    }
                    $totalScore += $answerWeighting;

                    if ($debug) error_log("studentChoice: $studentChoice");
                    break;
                case GLOBAL_MULTIPLE_ANSWER:
                    if ($from_database) {
                        $choice = array();
                        $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                WHERE exe_id = '".$exeId."' AND question_id= '".$questionId."'";
                        $resultans = Database::query($sql);
                        while ($row = Database::fetch_array($resultans)) {
                            $ind = $row['answer'];
                            $choice[$ind] = 1;
                        }
                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;
                        $real_answers[$answerId] = (bool)$studentChoice;
                        if ($studentChoice) {
                            $questionScore +=$answerWeighting;
                        }
                    } else {
                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;
                        if (isset($studentChoice)) {
                            $questionScore += $answerWeighting;
                        }
                        $real_answers[$answerId] = (bool)$studentChoice;
                    }
                    $totalScore += $answerWeighting;
                    if ($debug) error_log("studentChoice: $studentChoice");
                    break;
                case MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE:
                    if ($from_database) {
                        $sql = "SELECT answer FROM ".$TBL_TRACK_ATTEMPT."
                                WHERE exe_id = $exeId AND question_id= ".$questionId;
                        $resultans = Database::query($sql);
                        while ($row = Database::fetch_array($resultans)) {
                            $ind = $row['answer'];
                            $result = explode(':',$ind);
                            if (isset($result[0])) {
                                $my_answer_id = isset($result[0]) ? $result[0] : '';
                                $option = isset($result[1]) ? $result[1] : '';
                                $choice[$my_answer_id] = $option;
                            }
                        }
                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : '';

                        if ($answerCorrect == $studentChoice) {
                            //$answerCorrect = 1;
                            $real_answers[$answerId] = true;
                        } else {
                            //$answerCorrect = 0;
                            $real_answers[$answerId] = false;
                        }
                    } else {
                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : '';
                        if ($answerCorrect == $studentChoice) {
                            //$answerCorrect = 1;
                            $real_answers[$answerId] = true;
                        } else {
                            //$answerCorrect = 0;
                            $real_answers[$answerId] = false;
                        }
                    }
                    break;
                case MULTIPLE_ANSWER_COMBINATION:
                    if ($from_database) {
                        $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                WHERE exe_id = $exeId AND question_id= $questionId";
                        $resultans = Database::query($sql);
                        while ($row = Database::fetch_array($resultans)) {
                            $ind = $row['answer'];
                            $choice[$ind] = 1;
                        }

                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;

                        if ($answerCorrect == 1) {
                            if ($studentChoice) {
                                $real_answers[$answerId] = true;
                            } else {
                                $real_answers[$answerId] = false;
                            }
                        } else {
                            if ($studentChoice) {
                                $real_answers[$answerId] = false;
                            } else {
                                $real_answers[$answerId] = true;
                            }
                        }
                    } else {
                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;

                        if ($answerCorrect == 1) {
                            if ($studentChoice) {
                                $real_answers[$answerId] = true;
                            } else {
                                $real_answers[$answerId] = false;
                            }
                        } else {
                            if ($studentChoice) {
                                $real_answers[$answerId] = false;
                            } else {
                                $real_answers[$answerId] = true;
                            }
                        }
                    }
                    break;
                case FILL_IN_BLANKS:
                    $str = '';
                    if ($from_database) {
                        $sql = "SELECT answer
                                    FROM $TBL_TRACK_ATTEMPT
                                    WHERE
                                        exe_id = $exeId AND
                                        question_id= ".intval($questionId);
                        $result = Database::query($sql);
                        $str = Database::result($result, 0, 'answer');
                    }

                    if ($saved_results == false && strpos($str, 'font color') !== false) {
                        // the question is encoded like this
                        // [A] B [C] D [E] F::10,10,10@1
                        // number 1 before the "@" means that is a switchable fill in blank question
                        // [A] B [C] D [E] F::10,10,10@ or  [A] B [C] D [E] F::10,10,10
                        // means that is a normal fill blank question
                        // first we explode the "::"
                        $pre_array = explode('::', $answer);

                        // is switchable fill blank or not
                        $last = count($pre_array) - 1;
                        $is_set_switchable = explode('@', $pre_array[$last]);
                        $switchable_answer_set = false;
                        if (isset ($is_set_switchable[1]) && $is_set_switchable[1] == 1) {
                            $switchable_answer_set = true;
                        }
                        $answer = '';
                        for ($k = 0; $k < $last; $k++) {
                            $answer .= $pre_array[$k];
                        }
                        // splits weightings that are joined with a comma
                        $answerWeighting = explode(',', $is_set_switchable[0]);
                        // we save the answer because it will be modified
                        $temp = $answer;
                        $answer = '';
                        $j = 0;
                        //initialise answer tags
                        $user_tags = $correct_tags = $real_text = array();
                        // the loop will stop at the end of the text
                        while (1) {
                            // quits the loop if there are no more blanks (detect '[')
                            if ($temp == false || ($pos = api_strpos($temp, '[')) === false) {
                                // adds the end of the text
                                $answer = $temp;
                                $real_text[] = $answer;
                                break; //no more "blanks", quit the loop
                            }
                            // adds the piece of text that is before the blank
                            //and ends with '[' into a general storage array
                            $real_text[] = api_substr($temp, 0, $pos +1);
                            $answer .= api_substr($temp, 0, $pos +1);
                            //take the string remaining (after the last "[" we found)
                            $temp = api_substr($temp, $pos +1);
                            // quit the loop if there are no more blanks, and update $pos to the position of next ']'
                            if (($pos = api_strpos($temp, ']')) === false) {
                                // adds the end of the text
                                $answer .= $temp;
                                break;
                            }
                            if ($from_database) {
                                $queryfill = "SELECT answer FROM ".$TBL_TRACK_ATTEMPT."
                                          WHERE
                                            exe_id = '".$exeId."' AND
                                            question_id= ".intval($questionId)."";
                                $resfill = Database::query($queryfill);
                                $str = Database::result($resfill, 0, 'answer');
                                api_preg_match_all('#\[([^[]*)\]#', $str, $arr);
                                $str = str_replace('\r\n', '', $str);

                                $choice = $arr[1];
                                if (isset($choice[$j])) {
                                    $tmp = api_strrpos($choice[$j], ' / ');
                                    $choice[$j] = api_substr($choice[$j], 0, $tmp);
                                    $choice[$j] = trim($choice[$j]);
                                    // Needed to let characters ' and " to work as part of an answer
                                    $choice[$j] = stripslashes($choice[$j]);
                                } else {
                                    $choice[$j] = null;
                                }
                            } else {
                                // This value is the user input, not escaped while correct answer is escaped by fckeditor
                                $choice[$j] = api_htmlentities(trim($choice[$j]));
                            }

                            $user_tags[] = $choice[$j];
                            //put the contents of the [] answer tag into correct_tags[]
                            $correct_tags[] = api_substr($temp, 0, $pos);
                            $j++;
                            $temp = api_substr($temp, $pos +1);
                        }
                        $answer = '';
                        $real_correct_tags = $correct_tags;
                        $chosen_list = array();

                        for ($i = 0; $i < count($real_correct_tags); $i++) {
                            if ($i == 0) {
                                $answer .= $real_text[0];
                            }
                            if (!$switchable_answer_set) {
                                // Needed to parse ' and " characters
                                $user_tags[$i] = stripslashes($user_tags[$i]);
                                if ($correct_tags[$i] == $user_tags[$i]) {
                                    // gives the related weighting to the student
                                    $questionScore += $answerWeighting[$i];
                                    // increments total score
                                    $totalScore += $answerWeighting[$i];
                                    // adds the word in green at the end of the string
                                    $answer .= $correct_tags[$i];
                                } elseif (!empty($user_tags[$i])) {
                                    // else if the word entered by the student IS NOT the same as the one defined by the professor
                                    // adds the word in red at the end of the string, and strikes it
                                    $answer .= '<font color="red"><s>' . $user_tags[$i] . '</s></font>';
                                } else {
                                    // adds a tabulation if no word has been typed by the student
                                    $answer .= ''; // remove &nbsp; that causes issue
                                }
                            } else {
                                // switchable fill in the blanks
                                if (in_array($user_tags[$i], $correct_tags)) {
                                    $chosen_list[] = $user_tags[$i];
                                    $correct_tags = array_diff($correct_tags, $chosen_list);
                                    // gives the related weighting to the student
                                    $questionScore += $answerWeighting[$i];
                                    // increments total score
                                    $totalScore += $answerWeighting[$i];
                                    // adds the word in green at the end of the string
                                    $answer .= $user_tags[$i];
                                } elseif (!empty ($user_tags[$i])) {
                                    // else if the word entered by the student IS NOT the same as the one defined by the professor
                                    // adds the word in red at the end of the string, and strikes it
                                    $answer .= '<font color="red"><s>' . $user_tags[$i] . '</s></font>';
                                } else {
                                    // adds a tabulation if no word has been typed by the student
                                    $answer .= '';  // remove &nbsp; that causes issue
                                }
                            }

                            // adds the correct word, followed by ] to close the blank
                            $answer .= ' / <font color="green"><b>' . $real_correct_tags[$i] . '</b></font>]';
                            if (isset($real_text[$i +1])) {
                                $answer .= $real_text[$i +1];
                            }
                        }
                    } else {
                        // insert the student result in the track_e_attempt table, field answer
                        // $answer is the answer like in the c_quiz_answer table for the question
                        // student data are choice[]
                        $listCorrectAnswers = FillBlanks::getAnswerInfo(
                            $answer
                        );
                        $switchableAnswerSet = $listCorrectAnswers["switchable"];
                        $answerWeighting = $listCorrectAnswers["tabweighting"];
                        // user choices is an array $choice

                        // get existing user data in n the BDD
                        if ($from_database) {
                            $sql = "SELECT answer
                                    FROM $TBL_TRACK_ATTEMPT
                                    WHERE
                                        exe_id = $exeId AND
                                        question_id= ".intval($questionId);
                            $result = Database::query($sql);
                            $str = Database::result($result, 0, 'answer');
                            $listStudentResults = FillBlanks::getAnswerInfo(
                                $str,
                                true
                            );
                            $choice = $listStudentResults['studentanswer'];
                        }

                        // loop other all blanks words
                        if (!$switchableAnswerSet) {
                            // not switchable answer, must be in the same place than teacher order
                            for ($i = 0; $i < count($listCorrectAnswers['tabwords']); $i++) {
                                $studentAnswer = isset($choice[$i]) ? trim($choice[$i]) : '';

                                // This value is the user input, not escaped while correct answer is escaped by fckeditor
                                // Works with cyrillic alphabet and when using ">" chars see #7718 #7610 #7618
                                if (!$from_database) {
                                    $studentAnswer = htmlentities(
                                        api_utf8_encode($studentAnswer)
                                    );
                                }

                                $correctAnswer = $listCorrectAnswers['tabwords'][$i];
                                $isAnswerCorrect = 0;
                                if (FillBlanks::isGoodStudentAnswer(
                                    $studentAnswer,
                                    $correctAnswer
                                )
                                ) {
                                    // gives the related weighting to the student
                                    $questionScore += $answerWeighting[$i];
                                    // increments total score
                                    $totalScore += $answerWeighting[$i];
                                    $isAnswerCorrect = 1;
                                }
                                $listCorrectAnswers['studentanswer'][$i] = $studentAnswer;
                                $listCorrectAnswers['studentscore'][$i] = $isAnswerCorrect;
                            }
                        } else {
                            // switchable answer
                            $listStudentAnswerTemp = $choice;
                            $listTeacherAnswerTemp = $listCorrectAnswers['tabwords'];
                            // for every teacher answer, check if there is a student answer
                            for ($i = 0; $i < count($listStudentAnswerTemp); $i++) {
                                $studentAnswer = trim(
                                    $listStudentAnswerTemp[$i]
                                );
                                $found = false;
                                for ($j = 0; $j < count($listTeacherAnswerTemp); $j++) {
                                    $correctAnswer = $listTeacherAnswerTemp[$j];
                                    if (!$found) {
                                        if (FillBlanks::isGoodStudentAnswer(
                                            $studentAnswer,
                                            $correctAnswer
                                        )
                                        ) {
                                            $questionScore += $answerWeighting[$i];
                                            $totalScore += $answerWeighting[$i];
                                            $listTeacherAnswerTemp[$j] = "";
                                            $found = true;
                                        }
                                    }
                                }
                                $listCorrectAnswers['studentanswer'][$i] = $studentAnswer;
                                if (!$found) {
                                    $listCorrectAnswers['studentscore'][$i] = 0;
                                } else {
                                    $listCorrectAnswers['studentscore'][$i] = 1;
                                }
                            }
                        }
                        $answer = FillBlanks::getAnswerInStudentAttempt(
                            $listCorrectAnswers
                        );
                    }
                    break;
                case CALCULATED_ANSWER:
                    $answer = $objAnswerTmp->selectAnswer($_SESSION['calculatedAnswerId'][$questionId]);
                    $preArray = explode('@@', $answer);
                    $last = count($preArray) - 1;
                    $answer = '';
                    for ($k = 0; $k < $last; $k++) {
                        $answer .= $preArray[$k];
                    }
                    $answerWeighting = array($answerWeighting);
                    // we save the answer because it will be modified
                    $temp = $answer;
                    $answer = '';
                    $j = 0;
                    //initialise answer tags
                    $userTags = $correctTags = $realText = array();
                    // the loop will stop at the end of the text
                    while (1) {
                        // quits the loop if there are no more blanks (detect '[')
                        if ($temp == false || ($pos = api_strpos($temp, '[')) === false) {
                            // adds the end of the text
                            $answer = $temp;
                            $realText[] = $answer;
                            break; //no more "blanks", quit the loop
                        }
                        // adds the piece of text that is before the blank
                        //and ends with '[' into a general storage array
                        $realText[] = api_substr($temp, 0, $pos +1);
                        $answer .= api_substr($temp, 0, $pos +1);
                        //take the string remaining (after the last "[" we found)
                        $temp = api_substr($temp, $pos +1);
                        // quit the loop if there are no more blanks, and update $pos to the position of next ']'
                        if (($pos = api_strpos($temp, ']')) === false) {
                            // adds the end of the text
                            $answer .= $temp;
                            break;
                        }
                        if ($from_database) {
                            $queryfill = "SELECT answer FROM ".$TBL_TRACK_ATTEMPT."
                                          WHERE
                                            exe_id = '".$exeId."' AND
                                            question_id= ".intval($questionId);
                            $resfill = Database::query($queryfill);
                            $str = Database::result($resfill, 0, 'answer');
                            api_preg_match_all('#\[([^[]*)\]#', $str, $arr);
                            $str = str_replace('\r\n', '', $str);
                            $choice = $arr[1];
                            if (isset($choice[$j])) {
                                $tmp = api_strrpos($choice[$j], ' / ');

                                if ($tmp) {
                                    $choice[$j] = api_substr($choice[$j], 0, $tmp);
                                } else {
                                    $tmp = ltrim($tmp, '[');
                                    $tmp = rtrim($tmp, ']');
                                }

                                $choice[$j] = trim($choice[$j]);
                                // Needed to let characters ' and " to work as part of an answer
                                $choice[$j] = stripslashes($choice[$j]);
                            } else {
                                $choice[$j] = null;
                            }
                        } else {
                            // This value is the user input, not escaped while correct answer is escaped by fckeditor
                            $choice[$j] = api_htmlentities(trim($choice[$j]));
                        }
                        $userTags[] = $choice[$j];
                        //put the contents of the [] answer tag into correct_tags[]
                        $correctTags[] = api_substr($temp, 0, $pos);
                        $j++;
                        $temp = api_substr($temp, $pos +1);
                    }
                    $answer = '';
                    $realCorrectTags = $correctTags;
                    for ($i = 0; $i < count($realCorrectTags); $i++) {
                        if ($i == 0) {
                            $answer .= $realText[0];
                        }
                        // Needed to parse ' and " characters
                        $userTags[$i] = stripslashes($userTags[$i]);
                        if ($correctTags[$i] == $userTags[$i]) {
                            // gives the related weighting to the student
                            $questionScore += $answerWeighting[$i];
                            // increments total score
                            $totalScore += $answerWeighting[$i];
                            // adds the word in green at the end of the string
                            $answer .= $correctTags[$i];
                        } elseif (!empty($userTags[$i])) {
                            // else if the word entered by the student IS NOT the same as the one defined by the professor
                            // adds the word in red at the end of the string, and strikes it
                            $answer .= '<font color="red"><s>' . $userTags[$i] . '</s></font>';
                        } else {
                            // adds a tabulation if no word has been typed by the student
                            $answer .= ''; // remove &nbsp; that causes issue
                        }
                        // adds the correct word, followed by ] to close the blank

                        if (
                            $this->results_disabled != EXERCISE_FEEDBACK_TYPE_EXAM
                        ) {
                            $answer .= ' / <font color="green"><b>' . $realCorrectTags[$i] . '</b></font>';
                        }

                        $answer .= ']';

                        if (isset($realText[$i +1])) {
                            $answer .= $realText[$i +1];
                        }
                    }
                    break;
                case FREE_ANSWER:
                    if ($from_database) {
                        $query  = "SELECT answer, marks FROM ".$TBL_TRACK_ATTEMPT."
                                   WHERE exe_id = '".$exeId."' AND question_id= '".$questionId."'";
                        $resq = Database::query($query);
                        $data = Database::fetch_array($resq);

                        $choice = $data['answer'];
                        $choice = str_replace('\r\n', '', $choice);
                        $choice = stripslashes($choice);
                        $questionScore = $data['marks'];

                        if ($questionScore == -1) {
                            $totalScore+= 0;
                        } else {
                            $totalScore+= $questionScore;
                        }
                        if ($questionScore == '') {
                            $questionScore = 0;
                        }
                        $arrques = $questionName;
                        $arrans  = $choice;
                    } else {
                        $studentChoice = $choice;
                        if ($studentChoice) {
                            //Fixing negative puntation see #2193
                            $questionScore = 0;
                            $totalScore += 0;
                        }
                    }
                    break;
                case ORAL_EXPRESSION:
                    if ($from_database) {
                        $query = "SELECT answer, marks 
                                  FROM $TBL_TRACK_ATTEMPT
                                  WHERE 
                                        exe_id = $exeId AND 
                                        question_id = $questionId
                                 ";
                        $resq = Database::query($query);
                        $row = Database::fetch_assoc($resq);
                        $choice = $row['answer'];
                        $choice = str_replace('\r\n', '', $choice);
                        $choice = stripslashes($choice);
                        $questionScore = $row['marks'];
                        if ($questionScore == -1) {
                            $totalScore += 0;
                        } else {
                            $totalScore += $questionScore;
                        }
                        $arrques = $questionName;
                        $arrans  = $choice;
                    } else {
                        $studentChoice = $choice;
                        if ($studentChoice) {
                            //Fixing negative puntation see #2193
                            $questionScore = 0;
                            $totalScore += 0;
                        }
                    }
                    break;
                case DRAGGABLE:
                    //no break
                case MATCHING_DRAGGABLE:
                    //no break
                case MATCHING:
                    if ($from_database) {
                        $sql = "SELECT id, answer, id_auto
                                FROM $table_ans
                                WHERE
                                    c_id = $course_id AND
                                    question_id = $questionId AND
                                    correct = 0
                                ";
                        $res_answer = Database::query($sql);
                        // Getting the real answer
                        $real_list = array();
                        while ($real_answer = Database::fetch_array($res_answer)) {
                            $real_list[$real_answer['id_auto']] = $real_answer['answer'];
                        }

                        $sql = "SELECT id, answer, correct, id_auto, ponderation
                                FROM $table_ans
                                WHERE
                                    c_id = $course_id AND
                                    question_id = $questionId AND
                                    correct <> 0
                                ORDER BY id_auto";
                        $res_answers = Database::query($sql);

                        $questionScore = 0;

                        while ($a_answers = Database::fetch_array($res_answers)) {
                            $i_answer_id = $a_answers['id']; //3
                            $s_answer_label = $a_answers['answer'];  // your daddy - your mother
                            $i_answer_correct_answer = $a_answers['correct']; //1 - 2
                            $i_answer_id_auto = $a_answers['id_auto']; // 3 - 4

                            $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                    WHERE
                                        exe_id = '$exeId' AND
                                        question_id = '$questionId' AND
                                        position = '$i_answer_id_auto'";

                            $res_user_answer = Database::query($sql);

                            if (Database::num_rows($res_user_answer) > 0) {
                                //  rich - good looking
                                $s_user_answer = Database::result($res_user_answer, 0, 0);
                            } else {
                                $s_user_answer = 0;
                            }

                            $i_answerWeighting = $a_answers['ponderation'];

                            $user_answer = '';
                            if (!empty($s_user_answer)) {
                                if ($answerType == DRAGGABLE) {
                                    if ($s_user_answer == $i_answer_correct_answer) {
                                        $questionScore += $i_answerWeighting;
                                        $totalScore += $i_answerWeighting;
                                        $user_answer = Display::label(get_lang('Correct'), 'success');
                                    } else {
                                        $user_answer = Display::label(get_lang('Incorrect'), 'danger');
                                    }
                                } else {
                                    if ($s_user_answer == $i_answer_correct_answer) {
                                        $questionScore += $i_answerWeighting;
                                        $totalScore += $i_answerWeighting;

                                        // Try with id
                                        if (isset($real_list[$i_answer_id])) {
                                            $user_answer = Display::span($real_list[$i_answer_id]);
                                        }

                                        // Try with $i_answer_id_auto
                                        if (empty($user_answer)) {
                                            if (isset($real_list[$i_answer_id_auto])) {
                                                $user_answer = Display::span(
                                                    $real_list[$i_answer_id_auto]
                                                );
                                            }
                                        }
                                    } else {
                                        $user_answer = Display::span(
                                            $real_list[$s_user_answer],
                                            ['style' => 'color: #FF0000; text-decoration: line-through;']
                                        );
                                    }
                                }
                            } elseif ($answerType == DRAGGABLE) {
                                $user_answer = Display::label(get_lang('Incorrect'), 'danger');
                            } else {
                                $user_answer = Display::span(
                                    get_lang('Incorrect').' &nbsp;',
                                    ['style' => 'color: #FF0000; text-decoration: line-through;']
                                );
                            }

                            if ($show_result) {
                                if ($showTotalScoreAndUserChoicesInLastAttempt === false) {
                                    $user_answer = '';
                                }
                                echo '<tr>';
                                echo '<td>' . $s_answer_label . '</td>';
                                echo '<td>' . $user_answer;

                                if (in_array($answerType, [MATCHING, MATCHING_DRAGGABLE])) {
                                    if (isset($real_list[$i_answer_correct_answer]) &&
                                        $showTotalScoreAndUserChoicesInLastAttempt === true
                                    ) {
                                        echo Display::span(
                                            $real_list[$i_answer_correct_answer],
                                            ['style' => 'color: #008000; font-weight: bold;']
                                        );
                                    }
                                }
                                echo '</td>';
                                echo '</tr>';
                            }
                        }
                        break(2); // break the switch and the "for" condition
                    } else {
                        if ($answerCorrect) {
                            if (isset($choice[$answerAutoId]) &&
                                $answerCorrect == $choice[$answerAutoId]
                            ) {
                                $questionScore += $answerWeighting;
                                $totalScore += $answerWeighting;
                                $user_answer = Display::span($answerMatching[$choice[$answerAutoId]]);
                            } else {
                                if (isset($answerMatching[$choice[$answerAutoId]])) {
                                    $user_answer = Display::span(
                                        $answerMatching[$choice[$answerAutoId]],
                                        ['style' => 'color: #FF0000; text-decoration: line-through;']
                                    );
                                }
                            }
                            $matching[$answerAutoId] = $choice[$answerAutoId];
                        }
                        break;
                    }
                case HOT_SPOT:
                    if ($from_database) {
                        $TBL_TRACK_HOTSPOT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
                        // Check auto id
                        $sql = "SELECT hotspot_correct
                                FROM $TBL_TRACK_HOTSPOT
                                WHERE
                                    hotspot_exe_id = $exeId AND
                                    hotspot_question_id= $questionId AND
                                    hotspot_answer_id = ".intval($answerAutoId);
                        $result = Database::query($sql);
                        if (Database::num_rows($result)) {
                            $studentChoice = Database::result(
                                $result,
                                0,
                                'hotspot_correct'
                            );

                            if ($studentChoice) {
                                $questionScore += $answerWeighting;
                                $totalScore += $answerWeighting;
                            }
                        } else {
                            // If answer.id is different:
                            $sql = "SELECT hotspot_correct
                                FROM $TBL_TRACK_HOTSPOT
                                WHERE
                                    hotspot_exe_id = $exeId AND
                                    hotspot_question_id= $questionId AND
                                    hotspot_answer_id = ".intval($answerId);
                            $result = Database::query($sql);

                            if (Database::num_rows($result)) {
                                $studentChoice = Database::result(
                                    $result,
                                    0,
                                    'hotspot_correct'
                                );

                                if ($studentChoice) {
                                    $questionScore += $answerWeighting;
                                    $totalScore += $answerWeighting;
                                }
                            } else {
                                // check answer.iid
                                if (!empty($answerIid)) {
                                    $sql = "SELECT hotspot_correct
                                            FROM $TBL_TRACK_HOTSPOT
                                            WHERE
                                                hotspot_exe_id = $exeId AND
                                                hotspot_question_id= $questionId AND
                                                hotspot_answer_id = ".intval($answerIid);
                                    $result = Database::query($sql);

                                    $studentChoice = Database::result(
                                        $result,
                                        0,
                                        'hotspot_correct'
                                    );

                                    if ($studentChoice) {
                                        $questionScore += $answerWeighting;
                                        $totalScore += $answerWeighting;
                                    }
                                }
                            }
                        }
                    } else {
                        if (!isset($choice[$answerAutoId]) && !isset($choice[$answerIid])) {
                            $choice[$answerAutoId] = 0;
                            $choice[$answerIid] = 0;
                        } else {
                            $studentChoice = $choice[$answerAutoId];
                            if (empty($studentChoice)) {
                                $studentChoice = $choice[$answerIid];
                            }
                            $choiceIsValid = false;
                            if (!empty($studentChoice)) {
                                $hotspotType = $objAnswerTmp->selectHotspotType($answerId);
                                $hotspotCoordinates = $objAnswerTmp->selectHotspotCoordinates($answerId);
                                $choicePoint = Geometry::decodePoint($studentChoice);

                                switch ($hotspotType) {
                                    case 'square':
                                        $hotspotProperties = Geometry::decodeSquare($hotspotCoordinates);
                                        $choiceIsValid = Geometry::pointIsInSquare($hotspotProperties, $choicePoint);
                                        break;
                                    case 'circle':
                                        $hotspotProperties = Geometry::decodeEllipse($hotspotCoordinates);
                                        $choiceIsValid = Geometry::pointIsInEllipse($hotspotProperties, $choicePoint);
                                        break;
                                    case 'poly':
                                        $hotspotProperties = Geometry::decodePolygon($hotspotCoordinates);
                                        $choiceIsValid = Geometry::pointIsInPolygon($hotspotProperties, $choicePoint);
                                        break;
                                }
                            }

                            $choice[$answerAutoId] = 0;
                            if ($choiceIsValid) {
                                $questionScore += $answerWeighting;
                                $totalScore += $answerWeighting;
                                $choice[$answerAutoId] = 1;
                                $choice[$answerIid] = 1;
                            }
                        }
                    }
                    break;
                // @todo never added to chamilo
                //for hotspot with fixed order
                case HOT_SPOT_ORDER:
                    $studentChoice = $choice['order'][$answerId];
                    if ($studentChoice == $answerId) {
                        $questionScore  += $answerWeighting;
                        $totalScore     += $answerWeighting;
                        $studentChoice = true;
                    } else {
                        $studentChoice = false;
                    }
                    break;
                // for hotspot with delineation
                case HOT_SPOT_DELINEATION:
                    if ($from_database) {
                        // getting the user answer
                        $TBL_TRACK_HOTSPOT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
                        $query   = "SELECT hotspot_correct, hotspot_coordinate
                                    FROM $TBL_TRACK_HOTSPOT
                                    WHERE
                                        hotspot_exe_id = '".$exeId."' AND
                                        hotspot_question_id= '".$questionId."' AND
                                        hotspot_answer_id='1'";
                        //by default we take 1 because it's a delineation
                        $resq = Database::query($query);
                        $row = Database::fetch_array($resq,'ASSOC');

                        $choice = $row['hotspot_correct'];
                        $user_answer = $row['hotspot_coordinate'];

                        // THIS is very important otherwise the poly_compile will throw an error!!
                        // round-up the coordinates
                        $coords = explode('/',$user_answer);
                        $user_array = '';
                        foreach ($coords as $coord) {
                            list($x,$y) = explode(';',$coord);
                            $user_array .= round($x).';'.round($y).'/';
                        }
                        $user_array = substr($user_array,0,-1);
                    } else {
                        if (!empty($studentChoice)) {
                            $newquestionList[] = $questionId;
                        }

                        if ($answerId === 1) {
                            $studentChoice = $choice[$answerId];
                            $questionScore += $answerWeighting;

                            if ($hotspot_delineation_result[1]==1) {
                                $totalScore += $answerWeighting; //adding the total
                            }
                        }
                    }
                    $_SESSION['hotspot_coord'][1]	= $delineation_cord;
                    $_SESSION['hotspot_dest'][1]	= $answer_delineation_destination;
                    break;
            } // end switch Answertype

            if ($show_result) {
                if ($debug) error_log('Showing questions $from '.$from);
                if ($from == 'exercise_result') {
                    //display answers (if not matching type, or if the answer is correct)
                    if (
                        !in_array($answerType, [MATCHING, DRAGGABLE, MATCHING_DRAGGABLE]) ||
                        $answerCorrect
                    ) {
                        if (
                            in_array(
                                $answerType,
                                array(
                                    UNIQUE_ANSWER,
                                    UNIQUE_ANSWER_IMAGE,
                                    UNIQUE_ANSWER_NO_OPTION,
                                    MULTIPLE_ANSWER,
                                    MULTIPLE_ANSWER_COMBINATION,
                                    GLOBAL_MULTIPLE_ANSWER
                                )
                            )
                        ) {
                            ExerciseShowFunctions::display_unique_or_multiple_answer(
                                $feedback_type,
                                $answerType,
                                $studentChoice,
                                $answer,
                                $answerComment,
                                $answerCorrect,
                                0,
                                0,
                                0,
                                $results_disabled,
                                $showTotalScoreAndUserChoicesInLastAttempt
                            );
                        } elseif ($answerType == MULTIPLE_ANSWER_TRUE_FALSE) {
                            ExerciseShowFunctions::display_multiple_answer_true_false(
                                $feedback_type,
                                $answerType,
                                $studentChoice,
                                $answer,
                                $answerComment,
                                $answerCorrect,
                                0,
                                $questionId,
                                0,
                                $results_disabled,
                                $showTotalScoreAndUserChoicesInLastAttempt
                            );
                        } elseif ($answerType == MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE ) {
                            ExerciseShowFunctions::display_multiple_answer_combination_true_false(
                                $feedback_type,
                                $answerType,
                                $studentChoice,
                                $answer,
                                $answerComment,
                                $answerCorrect,
                                0,
                                0,
                                0,
                                $results_disabled,
                                $showTotalScoreAndUserChoicesInLastAttempt
                            );
                        } elseif ($answerType == FILL_IN_BLANKS) {
                            ExerciseShowFunctions::display_fill_in_blanks_answer(
                                $feedback_type,
                                $answer,
                                0,
                                0,
                                $results_disabled,
                                '',
                                $showTotalScoreAndUserChoicesInLastAttempt
                            );
                        } elseif ($answerType == CALCULATED_ANSWER) {
                            ExerciseShowFunctions::display_calculated_answer(
                                $feedback_type,
                                $answer,
                                0,
                                0,
                                $results_disabled,
                                $showTotalScoreAndUserChoicesInLastAttempt
                            );
                        } elseif ($answerType == FREE_ANSWER) {
                            ExerciseShowFunctions::display_free_answer(
                                $feedback_type,
                                $choice,
                                $exeId,
                                $questionId,
                                $questionScore,
                                $results_disabled
                            );
                        } elseif ($answerType == ORAL_EXPRESSION) {
                            // to store the details of open questions in an array to be used in mail
                            /** @var OralExpression $objQuestionTmp */
                            ExerciseShowFunctions::display_oral_expression_answer(
                                $feedback_type,
                                $choice,
                                0,
                                0,
                                $objQuestionTmp->getFileUrl(true),
                                $results_disabled
                            );
                        } elseif ($answerType == HOT_SPOT) {
                            foreach ($orderedHotspots as $correctAnswerId => $hotspot) {
                                if ($hotspot->getHotspotAnswerId() == $answerAutoId) {
                                    break;
                                }
                            }

                            ExerciseShowFunctions::display_hotspot_answer(
                                $feedback_type,
                                $answerId,
                                $answer,
                                $studentChoice,
                                $answerComment,
                                $results_disabled,
                                $answerId,
                                $showTotalScoreAndUserChoicesInLastAttempt
                            );
                        } elseif ($answerType == HOT_SPOT_ORDER) {
                            ExerciseShowFunctions::display_hotspot_order_answer(
                                $feedback_type,
                                $answerId,
                                $answer,
                                $studentChoice,
                                $answerComment
                            );
                        } elseif ($answerType == HOT_SPOT_DELINEATION) {
                            $user_answer = $_SESSION['exerciseResultCoordinates'][$questionId];

                            //round-up the coordinates
                            $coords = explode('/',$user_answer);
                            $user_array = '';
                            foreach ($coords as $coord) {
                                list($x,$y) = explode(';',$coord);
                                $user_array .= round($x).';'.round($y).'/';
                            }
                            $user_array = substr($user_array,0,-1);

                            if ($next) {

                                $user_answer = $user_array;

                                // we compare only the delineation not the other points
                                $answer_question = $_SESSION['hotspot_coord'][1];
                                $answerDestination = $_SESSION['hotspot_dest'][1];

                                //calculating the area
                                $poly_user = convert_coordinates($user_answer, '/');
                                $poly_answer = convert_coordinates($answer_question, '|');
                                $max_coord = poly_get_max($poly_user, $poly_answer);
                                $poly_user_compiled = poly_compile($poly_user, $max_coord);
                                $poly_answer_compiled = poly_compile($poly_answer, $max_coord);
                                $poly_results = poly_result($poly_answer_compiled, $poly_user_compiled, $max_coord);

                                $overlap = $poly_results['both'];
                                $poly_answer_area = $poly_results['s1'];
                                $poly_user_area = $poly_results['s2'];
                                $missing = $poly_results['s1Only'];
                                $excess = $poly_results['s2Only'];

                                //$overlap = round(polygons_overlap($poly_answer,$poly_user));
                                // //this is an area in pixels
                                if ($debug > 0) {
                                    error_log(__LINE__ . ' - Polygons results are ' . print_r($poly_results, 1), 0);
                                }

                                if ($overlap < 1) {
                                    //shortcut to avoid complicated calculations
                                    $final_overlap = 0;
                                    $final_missing = 100;
                                    $final_excess = 100;
                                } else {
                                    // the final overlap is the percentage of the initial polygon
                                    // that is overlapped by the user's polygon
                                    $final_overlap = round(((float) $overlap / (float) $poly_answer_area) * 100);
                                    if ($debug > 1) {
                                        error_log(__LINE__ . ' - Final overlap is ' . $final_overlap, 0);
                                    }
                                    // the final missing area is the percentage of the initial polygon
                                    // that is not overlapped by the user's polygon
                                    $final_missing = 100 - $final_overlap;
                                    if ($debug > 1) {
                                        error_log(__LINE__ . ' - Final missing is ' . $final_missing, 0);
                                    }
                                    // the final excess area is the percentage of the initial polygon's size
                                    // that is covered by the user's polygon outside of the initial polygon
                                    $final_excess = round((((float) $poly_user_area - (float) $overlap) / (float) $poly_answer_area) * 100);
                                    if ($debug > 1) {
                                        error_log(__LINE__ . ' - Final excess is ' . $final_excess, 0);
                                    }
                                }

                                //checking the destination parameters parsing the "@@"
                                $destination_items= explode('@@', $answerDestination);
                                $threadhold_total = $destination_items[0];
                                $threadhold_items=explode(';',$threadhold_total);
                                $threadhold1 = $threadhold_items[0]; // overlap
                                $threadhold2 = $threadhold_items[1]; // excess
                                $threadhold3 = $threadhold_items[2];	 //missing

                                // if is delineation
                                if ($answerId===1) {
                                    //setting colors
                                    if ($final_overlap>=$threadhold1) {
                                        $overlap_color=true; //echo 'a';
                                    }
                                    //echo $excess.'-'.$threadhold2;
                                    if ($final_excess<=$threadhold2) {
                                        $excess_color=true; //echo 'b';
                                    }
                                    //echo '--------'.$missing.'-'.$threadhold3;
                                    if ($final_missing<=$threadhold3) {
                                        $missing_color=true; //echo 'c';
                                    }

                                    // if pass
                                    if (
                                        $final_overlap >= $threadhold1 &&
                                        $final_missing <= $threadhold3 &&
                                        $final_excess <= $threadhold2
                                    ) {
                                        $next=1; //go to the oars
                                        $result_comment=get_lang('Acceptable');
                                        $final_answer = 1;	// do not update with  update_exercise_attempt
                                    } else {
                                        $next=0;
                                        $result_comment=get_lang('Unacceptable');
                                        $comment=$answerDestination=$objAnswerTmp->selectComment(1);
                                        $answerDestination=$objAnswerTmp->selectDestination(1);
                                        //checking the destination parameters parsing the "@@"
                                        $destination_items= explode('@@', $answerDestination);
                                    }
                                } elseif($answerId>1) {
                                    if ($objAnswerTmp->selectHotspotType($answerId) == 'noerror') {
                                        if ($debug>0) {
                                            error_log(__LINE__.' - answerId is of type noerror',0);
                                        }
                                        //type no error shouldn't be treated
                                        $next = 1;
                                        continue;
                                    }
                                    if ($debug>0) {
                                        error_log(__LINE__.' - answerId is >1 so we\'re probably in OAR',0);
                                    }
                                    //check the intersection between the oar and the user
                                    //echo 'user';	print_r($x_user_list);		print_r($y_user_list);
                                    //echo 'official';print_r($x_list);print_r($y_list);
                                    //$result = get_intersection_data($x_list,$y_list,$x_user_list,$y_user_list);
                                    $inter= $result['success'];

                                    //$delineation_cord=$objAnswerTmp->selectHotspotCoordinates($answerId);
                                    $delineation_cord=$objAnswerTmp->selectHotspotCoordinates($answerId);

                                    $poly_answer = convert_coordinates($delineation_cord,'|');
                                    $max_coord = poly_get_max($poly_user,$poly_answer);
                                    $poly_answer_compiled = poly_compile($poly_answer,$max_coord);
                                    $overlap = poly_touch($poly_user_compiled, $poly_answer_compiled,$max_coord);

                                    if ($overlap == false) {
                                        //all good, no overlap
                                        $next = 1;
                                        continue;
                                    } else {
                                        if ($debug>0) {
                                            error_log(__LINE__.' - Overlap is '.$overlap.': OAR hit',0);
                                        }
                                        $organs_at_risk_hit++;
                                        //show the feedback
                                        $next=0;
                                        $comment=$answerDestination=$objAnswerTmp->selectComment($answerId);
                                        $answerDestination=$objAnswerTmp->selectDestination($answerId);

                                        $destination_items= explode('@@', $answerDestination);
                                        $try_hotspot=$destination_items[1];
                                        $lp_hotspot=$destination_items[2];
                                        $select_question_hotspot=$destination_items[3];
                                        $url_hotspot=$destination_items[4];
                                    }
                                }
                            } else {	// the first delineation feedback
                                if ($debug>0) {
                                    error_log(__LINE__.' first',0);
                                }
                            }
                        } elseif (in_array($answerType, [MATCHING, MATCHING_DRAGGABLE])) {
                            echo '<tr>';
                            echo Display::tag('td', $answerMatching[$answerId]);
                            echo Display::tag(
                                'td',
                                "$user_answer / " . Display::tag(
                                    'strong',
                                    $answerMatching[$answerCorrect],
                                    ['style' => 'color: #008000; font-weight: bold;']
                                )
                            );
                            echo '</tr>';
                        }
                    }
                } else {
                    if ($debug) error_log('Showing questions $from '.$from);

                    switch ($answerType) {
                        case UNIQUE_ANSWER:
                        case UNIQUE_ANSWER_IMAGE:
                        case UNIQUE_ANSWER_NO_OPTION:
                        case MULTIPLE_ANSWER:
                        case GLOBAL_MULTIPLE_ANSWER :
                        case MULTIPLE_ANSWER_COMBINATION:
                            if ($answerId == 1) {
                                ExerciseShowFunctions::display_unique_or_multiple_answer(
                                    $feedback_type,
                                    $answerType,
                                    $studentChoice,
                                    $answer,
                                    $answerComment,
                                    $answerCorrect,
                                    $exeId,
                                    $questionId,
                                    $answerId,
                                    $results_disabled,
                                    $showTotalScoreAndUserChoicesInLastAttempt
                                );
                            } else {
                                ExerciseShowFunctions::display_unique_or_multiple_answer(
                                    $feedback_type,
                                    $answerType,
                                    $studentChoice,
                                    $answer,
                                    $answerComment,
                                    $answerCorrect,
                                    $exeId,
                                    $questionId,
                                    '',
                                    $results_disabled,
                                    $showTotalScoreAndUserChoicesInLastAttempt
                                );
                            }
                            break;
                        case MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE:
                            if ($answerId == 1) {
                                ExerciseShowFunctions::display_multiple_answer_combination_true_false(
                                    $feedback_type,
                                    $answerType,
                                    $studentChoice,
                                    $answer,
                                    $answerComment,
                                    $answerCorrect,
                                    $exeId,
                                    $questionId,
                                    $answerId,
                                    $results_disabled,
                                    $showTotalScoreAndUserChoicesInLastAttempt
                                );
                            } else {
                                ExerciseShowFunctions::display_multiple_answer_combination_true_false(
                                    $feedback_type,
                                    $answerType,
                                    $studentChoice,
                                    $answer,
                                    $answerComment,
                                    $answerCorrect,
                                    $exeId,
                                    $questionId,
                                    '',
                                    $results_disabled,
                                    $showTotalScoreAndUserChoicesInLastAttempt
                                );
                            }
                            break;
                        case MULTIPLE_ANSWER_TRUE_FALSE:
                            if ($answerId == 1) {
                                ExerciseShowFunctions::display_multiple_answer_true_false(
                                    $feedback_type,
                                    $answerType,
                                    $studentChoice,
                                    $answer,
                                    $answerComment,
                                    $answerCorrect,
                                    $exeId,
                                    $questionId,
                                    $answerId,
                                    $results_disabled,
                                    $showTotalScoreAndUserChoicesInLastAttempt
                                );
                            } else {
                                ExerciseShowFunctions::display_multiple_answer_true_false(
                                    $feedback_type,
                                    $answerType,
                                    $studentChoice,
                                    $answer,
                                    $answerComment,
                                    $answerCorrect,
                                    $exeId,
                                    $questionId,
                                    '',
                                    $results_disabled,
                                    $showTotalScoreAndUserChoicesInLastAttempt
                                );
                            }
                            break;
                        case FILL_IN_BLANKS:
                            ExerciseShowFunctions::display_fill_in_blanks_answer(
                                $feedback_type,
                                $answer,
                                $exeId,
                                $questionId,
                                $results_disabled,
                                $str,
                                $showTotalScoreAndUserChoicesInLastAttempt
                            );
                            break;
                        case CALCULATED_ANSWER:
                            ExerciseShowFunctions::display_calculated_answer(
                                $feedback_type,
                                $answer,
                                $exeId,
                                $questionId,
                                $results_disabled,
                                '',
                                $showTotalScoreAndUserChoicesInLastAttempt
                            );
                            break;
                        case FREE_ANSWER:
                            echo ExerciseShowFunctions::display_free_answer(
                                $feedback_type,
                                $choice,
                                $exeId,
                                $questionId,
                                $questionScore,
                                $results_disabled
                            );
                            break;
                        case ORAL_EXPRESSION:
                            echo '<tr>
                                <td valign="top">' . ExerciseShowFunctions::display_oral_expression_answer(
                                    $feedback_type,
                                    $choice,
                                    $exeId,
                                    $questionId,
                                    $objQuestionTmp->getFileUrl(),
                                    $results_disabled
                                ) . '</td>
                                </tr>
                                </table>';
                            break;
                        case HOT_SPOT:
                            ExerciseShowFunctions::display_hotspot_answer(
                                $feedback_type,
                                $answerId,
                                $answer,
                                $studentChoice,
                                $answerComment,
                                $results_disabled,
                                $answerId,
                                $showTotalScoreAndUserChoicesInLastAttempt
                            );
                            break;
                        case HOT_SPOT_DELINEATION:
                            $user_answer = $user_array;
                            if ($next) {
                                //$tbl_track_e_hotspot = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
                                // Save into db
                                /*	$sql = "INSERT INTO $tbl_track_e_hotspot (
                                 * hotspot_user_id,
                                 *  hotspot_course_code,
                                 *  hotspot_exe_id,
                                 *  hotspot_question_id,
                                 *  hotspot_answer_id,
                                 *  hotspot_correct,
                                 *  hotspot_coordinate
                                 *  )
                                VALUES (
                                 * '".Database::escape_string($_user['user_id'])."',
                                 *  '".Database::escape_string($_course['id'])."',
                                 *  '".Database::escape_string($exeId)."', '".Database::escape_string($questionId)."',
                                 *  '".Database::escape_string($answerId)."',
                                 *  '".Database::escape_string($studentChoice)."',
                                 *  '".Database::escape_string($user_array)."')";
                                $result = Database::query($sql,__FILE__,__LINE__);
                                 */
                                $user_answer = $user_array;

                                // we compare only the delineation not the other points
                                $answer_question = $_SESSION['hotspot_coord'][1];
                                $answerDestination = $_SESSION['hotspot_dest'][1];

                                //calculating the area
                                $poly_user = convert_coordinates($user_answer, '/');
                                $poly_answer = convert_coordinates($answer_question, '|');

                                $max_coord = poly_get_max($poly_user, $poly_answer);
                                $poly_user_compiled = poly_compile($poly_user, $max_coord);
                                $poly_answer_compiled = poly_compile($poly_answer, $max_coord);
                                $poly_results = poly_result($poly_answer_compiled, $poly_user_compiled, $max_coord);

                                $overlap = $poly_results['both'];
                                $poly_answer_area = $poly_results['s1'];
                                $poly_user_area = $poly_results['s2'];
                                $missing = $poly_results['s1Only'];
                                $excess = $poly_results['s2Only'];

                                //$overlap = round(polygons_overlap($poly_answer,$poly_user)); //this is an area in pixels
                                if ($debug > 0) {
                                    error_log(__LINE__ . ' - Polygons results are ' . print_r($poly_results, 1), 0);
                                }
                                if ($overlap < 1) {
                                    //shortcut to avoid complicated calculations
                                    $final_overlap = 0;
                                    $final_missing = 100;
                                    $final_excess = 100;
                                } else {
                                    // the final overlap is the percentage of the initial polygon that is overlapped by the user's polygon
                                    $final_overlap = round(((float) $overlap / (float) $poly_answer_area) * 100);
                                    if ($debug > 1) {
                                        error_log(__LINE__ . ' - Final overlap is ' . $final_overlap, 0);
                                    }
                                    // the final missing area is the percentage of the initial polygon that is not overlapped by the user's polygon
                                    $final_missing = 100 - $final_overlap;
                                    if ($debug > 1) {
                                        error_log(__LINE__ . ' - Final missing is ' . $final_missing, 0);
                                    }
                                    // the final excess area is the percentage of the initial polygon's size that is covered by the user's polygon outside of the initial polygon
                                    $final_excess = round((((float) $poly_user_area - (float) $overlap) / (float) $poly_answer_area) * 100);
                                    if ($debug > 1) {
                                        error_log(__LINE__ . ' - Final excess is ' . $final_excess, 0);
                                    }
                                }

                                //checking the destination parameters parsing the "@@"
                                $destination_items = explode('@@', $answerDestination);
                                $threadhold_total = $destination_items[0];
                                $threadhold_items = explode(';', $threadhold_total);
                                $threadhold1 = $threadhold_items[0]; // overlap
                                $threadhold2 = $threadhold_items[1]; // excess
                                $threadhold3 = $threadhold_items[2];  //missing
                                // if is delineation
                                if ($answerId === 1) {
                                    //setting colors
                                    if ($final_overlap >= $threadhold1) {
                                        $overlap_color = true; //echo 'a';
                                    }
                                    //echo $excess.'-'.$threadhold2;
                                    if ($final_excess <= $threadhold2) {
                                        $excess_color = true; //echo 'b';
                                    }
                                    //echo '--------'.$missing.'-'.$threadhold3;
                                    if ($final_missing <= $threadhold3) {
                                        $missing_color = true; //echo 'c';
                                    }

                                    // if pass
                                    if ($final_overlap >= $threadhold1 && $final_missing <= $threadhold3 && $final_excess <= $threadhold2) {
                                        $next = 1; //go to the oars
                                        $result_comment = get_lang('Acceptable');
                                        $final_answer = 1; // do not update with  update_exercise_attempt
                                    } else {
                                        $next = 0;
                                        $result_comment = get_lang('Unacceptable');
                                        $comment = $answerDestination = $objAnswerTmp->selectComment(1);
                                        $answerDestination = $objAnswerTmp->selectDestination(1);
                                        //checking the destination parameters parsing the "@@"
                                        $destination_items = explode('@@', $answerDestination);
                                    }
                                } elseif ($answerId > 1) {
                                    if ($objAnswerTmp->selectHotspotType($answerId) == 'noerror') {
                                        if ($debug > 0) {
                                            error_log(__LINE__ . ' - answerId is of type noerror', 0);
                                        }
                                        //type no error shouldn't be treated
                                        $next = 1;
                                        continue;
                                    }
                                    if ($debug > 0) {
                                        error_log(__LINE__ . ' - answerId is >1 so we\'re probably in OAR', 0);
                                    }
                                    //check the intersection between the oar and the user
                                    //echo 'user';	print_r($x_user_list);		print_r($y_user_list);
                                    //echo 'official';print_r($x_list);print_r($y_list);
                                    //$result = get_intersection_data($x_list,$y_list,$x_user_list,$y_user_list);
                                    $inter = $result['success'];

                                    //$delineation_cord=$objAnswerTmp->selectHotspotCoordinates($answerId);
                                    $delineation_cord = $objAnswerTmp->selectHotspotCoordinates($answerId);

                                    $poly_answer = convert_coordinates($delineation_cord, '|');
                                    $max_coord = poly_get_max($poly_user, $poly_answer);
                                    $poly_answer_compiled = poly_compile($poly_answer, $max_coord);
                                    $overlap = poly_touch($poly_user_compiled, $poly_answer_compiled,$max_coord);

                                    if ($overlap == false) {
                                        //all good, no overlap
                                        $next = 1;
                                        continue;
                                    } else {
                                        if ($debug > 0) {
                                            error_log(__LINE__ . ' - Overlap is ' . $overlap . ': OAR hit', 0);
                                        }
                                        $organs_at_risk_hit++;
                                        //show the feedback
                                        $next = 0;
                                        $comment = $answerDestination = $objAnswerTmp->selectComment($answerId);
                                        $answerDestination = $objAnswerTmp->selectDestination($answerId);

                                        $destination_items = explode('@@', $answerDestination);
                                        $try_hotspot = $destination_items[1];
                                        $lp_hotspot = $destination_items[2];
                                        $select_question_hotspot = $destination_items[3];
                                        $url_hotspot=$destination_items[4];
                                    }
                                }
                            } else {	// the first delineation feedback
                                if ($debug > 0) {
                                    error_log(__LINE__ . ' first', 0);
                                }
                            }
                            break;
                        case HOT_SPOT_ORDER:
                            ExerciseShowFunctions::display_hotspot_order_answer(
                                $feedback_type,
                                $answerId,
                                $answer,
                                $studentChoice,
                                $answerComment
                            );
                            break;
                        case DRAGGABLE:
                            //no break
                        case MATCHING_DRAGGABLE:
                            //no break
                        case MATCHING:
                            echo '<tr>';
                            echo Display::tag('td', $answerMatching[$answerId]);
                            echo Display::tag(
                                'td',
                                "$user_answer / " . Display::tag(
                                    'strong',
                                    $answerMatching[$answerCorrect],
                                    ['style' => 'color: #008000; font-weight: bold;']
                                )
                            );
                            echo '</tr>';

                            break;
                    }
                }
            }
            if ($debug) error_log(' ------ ');
        } // end for that loops over all answers of the current question

        if ($debug) error_log('-- end answer loop --');

        $final_answer = true;

        foreach ($real_answers as $my_answer) {
            if (!$my_answer) {
                $final_answer = false;
            }
        }

        //we add the total score after dealing with the answers
        if ($answerType == MULTIPLE_ANSWER_COMBINATION ||
            $answerType == MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE
        ) {
            if ($final_answer) {
                //getting only the first score where we save the weight of all the question
                $answerWeighting = $objAnswerTmp->selectWeighting(1);
                $questionScore += $answerWeighting;
                $totalScore += $answerWeighting;
            }
        }

        //Fixes multiple answer question in order to be exact
        //if ($answerType == MULTIPLE_ANSWER || $answerType == GLOBAL_MULTIPLE_ANSWER) {
       /* if ($answerType == GLOBAL_MULTIPLE_ANSWER) {
            $diff = @array_diff($answer_correct_array, $real_answers);

            // All good answers or nothing works like exact

            $counter = 1;
            $correct_answer = true;
            foreach ($real_answers as $my_answer) {
                if ($debug)
                    error_log(" my_answer: $my_answer answer_correct_array[counter]: ".$answer_correct_array[$counter]);
                if ($my_answer != $answer_correct_array[$counter]) {
                    $correct_answer = false;
                    break;
                }
                $counter++;
            }

            if ($debug) error_log(" answer_correct_array: ".print_r($answer_correct_array, 1)."");
            if ($debug) error_log(" real_answers: ".print_r($real_answers, 1)."");
            if ($debug) error_log(" correct_answer: ".$correct_answer);

            if ($correct_answer == false) {
                $questionScore = 0;
            }

            // This makes the result non exact
            if (!empty($diff)) {
                $questionScore = 0;
            }
        }*/

        $extra_data = array(
            'final_overlap' => $final_overlap,
            'final_missing'=>$final_missing,
            'final_excess'=> $final_excess,
            'overlap_color' => $overlap_color,
            'missing_color'=>$missing_color,
            'excess_color'=> $excess_color,
            'threadhold1'   => $threadhold1,
            'threadhold2'=>$threadhold2,
            'threadhold3'=> $threadhold3,
        );
        if ($from == 'exercise_result') {
            // if answer is hotspot. To the difference of exercise_show.php,
            //  we use the results from the session (from_db=0)
            // TODO Change this, because it is wrong to show the user
            //  some results that haven't been stored in the database yet
            if ($answerType == HOT_SPOT || $answerType == HOT_SPOT_ORDER || $answerType == HOT_SPOT_DELINEATION ) {

                if ($debug) error_log('$from AND this is a hotspot kind of question ');

                $my_exe_id = 0;
                $from_database = 0;
                if ($answerType == HOT_SPOT_DELINEATION) {
                    if (0) {
                        if ($overlap_color) {
                            $overlap_color='green';
                        } else {
                            $overlap_color='red';
                        }
                        if ($missing_color) {
                            $missing_color='green';
                        } else {
                            $missing_color='red';
                        }
                        if ($excess_color) {
                            $excess_color='green';
                        } else {
                            $excess_color='red';
                        }
                        if (!is_numeric($final_overlap)) {
                            $final_overlap = 0;
                        }
                        if (!is_numeric($final_missing)) {
                            $final_missing = 0;
                        }
                        if (!is_numeric($final_excess)) {
                            $final_excess = 0;
                        }

                        if ($final_overlap>100) {
                            $final_overlap = 100;
                        }

                        $table_resume='<table class="data_table">
                                <tr class="row_odd" >
                                    <td></td>
                                    <td ><b>' . get_lang('Requirements') . '</b></td>
                                    <td><b>' . get_lang('YourAnswer') . '</b></td>
                                </tr>
                                <tr class="row_even">
                                    <td><b>' . get_lang('Overlap') . '</b></td>
                                    <td>' . get_lang('Min') . ' ' . $threadhold1 . '</td>
                                    <td><div style="color:' . $overlap_color . '">'
                                        . (($final_overlap < 0) ? 0 : intval($final_overlap)) . '</div></td>
                                </tr>
                                <tr>
                                    <td><b>' . get_lang('Excess') . '</b></td>
                                    <td>' . get_lang('Max') . ' ' . $threadhold2 . '</td>
                                    <td><div style="color:' . $excess_color . '">'
                                        . (($final_excess < 0) ? 0 : intval($final_excess)) . '</div></td>
                                </tr>
                                <tr class="row_even">
                                    <td><b>' . get_lang('Missing') . '</b></td>
                                    <td>' . get_lang('Max') . ' ' . $threadhold3 . '</td>
                                    <td><div style="color:' . $missing_color . '">'
                                        . (($final_missing < 0) ? 0 : intval($final_missing)) . '</div></td>
                                </tr>
                            </table>';
                        if ($next == 0) {
                            $try = $try_hotspot;
                            $lp = $lp_hotspot;
                            $destinationid = $select_question_hotspot;
                            $url = $url_hotspot;
                        } else {
                            //show if no error
                            //echo 'no error';
                            $comment = $answerComment = $objAnswerTmp->selectComment($nbrAnswers);
                            $answerDestination = $objAnswerTmp->selectDestination($nbrAnswers);
                        }

                        echo '<h1><div style="color:#333;">' . get_lang('Feedback') . '</div></h1>
                            <p style="text-align:center">';

                        $message = '<p>' . get_lang('YourDelineation') . '</p>';
                        $message .= $table_resume;
                        $message .= '<br />' . get_lang('ResultIs') . ' ' . $result_comment . '<br />';
                        if ($organs_at_risk_hit > 0) {
                            $message .= '<p><b>' . get_lang('OARHit') . '</b></p>';
                        }
                        $message .='<p>' . $comment . '</p>';
                        echo $message;
                    } else {
                        echo $hotspot_delineation_result[0]; //prints message
                        $from_database = 1;  // the hotspot_solution.swf needs this variable
                    }

                    //save the score attempts

                    if (1) {
                        //getting the answer 1 or 0 comes from exercise_submit_modal.php
                        $final_answer = $hotspot_delineation_result[1];
                        if ($final_answer == 0) {
                            $questionScore = 0;
                        }
                        // we always insert the answer_id 1 = delineation
                        Event::saveQuestionAttempt($questionScore, 1, $quesId, $exeId, 0);
                        //in delineation mode, get the answer from $hotspot_delineation_result[1]
                        $hotspotValue = (int) $hotspot_delineation_result[1] === 1 ? 1 : 0;
                        Event::saveExerciseAttemptHotspot(
                            $exeId,
                            $quesId,
                            1,
                            $hotspotValue,
                            $exerciseResultCoordinates[$quesId]
                        );
                    } else {
                        if ($final_answer==0) {
                            $questionScore = 0;
                            $answer=0;
                            Event::saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0);
                            if (is_array($exerciseResultCoordinates[$quesId])) {
                                foreach($exerciseResultCoordinates[$quesId] as $idx => $val) {
                                    Event::saveExerciseAttemptHotspot(
                                        $exeId,
                                        $quesId,
                                        $idx,
                                        0,
                                        $val
                                    );
                                }
                            }
                        } else {
                            Event::saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0);
                            if (is_array($exerciseResultCoordinates[$quesId])) {
                                foreach($exerciseResultCoordinates[$quesId] as $idx => $val) {
                                    $hotspotValue = (int) $choice[$idx] === 1 ? 1 : 0;
                                    Event::saveExerciseAttemptHotspot(
                                        $exeId,
                                        $quesId,
                                        $idx,
                                        $hotspotValue,
                                        $val
                                    );
                                }
                            }
                        }
                    }
                    $my_exe_id = $exeId;
                }
            }

            if ($answerType == HOT_SPOT || $answerType == HOT_SPOT_ORDER) {
                // We made an extra table for the answers

                if ($show_result) {
                    $relPath = api_get_path(WEB_CODE_PATH);
                    //	if ($origin != 'learnpath') {
                    echo '</table></td></tr>';
                    echo "
                        <tr>
                            <td colspan=\"2\">
                                <p><em>" . get_lang('HotSpot') . "</em></p>
                                <div id=\"hotspot-solution-$questionId\"></div>
                                <script>
                                    $(document).on('ready', function () {
                                        new HotspotQuestion({
                                            questionId: $questionId,
                                            exerciseId: $exeId,
                                            selector: '#hotspot-solution-$questionId',
                                            for: 'solution',
                                            relPath: '$relPath'
                                        });
                                    });
                                </script>
                            </td>
                        </tr>
                    ";
                    //	}
                }
            }

            //if ($origin != 'learnpath') {
            if ($show_result) {
                echo '</table>';
            }
            //	}
        }
        unset ($objAnswerTmp);

        $totalWeighting += $questionWeighting;
        // Store results directly in the database
        // For all in one page exercises, the results will be
        // stored by exercise_results.php (using the session)

        if ($saved_results) {
            if ($debug) error_log("Save question results $saved_results");
            if ($debug) error_log(print_r($choice ,1 ));

            if (empty($choice)) {
                $choice = 0;
            }
            if ($answerType == MULTIPLE_ANSWER_TRUE_FALSE || $answerType == MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE) {
                if ($choice != 0) {
                    $reply = array_keys($choice);
                    for ($i = 0; $i < sizeof($reply); $i++) {
                        $ans = $reply[$i];
                        Event::saveQuestionAttempt(
                            $questionScore,
                            $ans . ':' . $choice[$ans],
                            $quesId,
                            $exeId,
                            $i,
                            $this->id
                        );
                        if ($debug) {
                            error_log('result =>' . $questionScore . ' ' . $ans . ':' . $choice[$ans]);
                        }
                    }
                } else {
                    Event::saveQuestionAttempt($questionScore, 0, $quesId, $exeId, 0, $this->id);
                }
            } elseif ($answerType == MULTIPLE_ANSWER || $answerType == GLOBAL_MULTIPLE_ANSWER) {
                if ($choice != 0) {
                    $reply = array_keys($choice);

                    if ($debug) {
                        error_log("reply " . print_r($reply, 1) . "");
                    }
                    for ($i = 0; $i < sizeof($reply); $i++) {
                        $ans = $reply[$i];
                        Event::saveQuestionAttempt($questionScore, $ans, $quesId, $exeId, $i, $this->id);
                    }
                } else {
                    Event::saveQuestionAttempt($questionScore, 0, $quesId, $exeId, 0, $this->id);
                }
            } elseif ($answerType == MULTIPLE_ANSWER_COMBINATION) {
                if ($choice != 0) {
                    $reply = array_keys($choice);
                    for ($i = 0; $i < sizeof($reply); $i++) {
                        $ans = $reply[$i];
                        Event::saveQuestionAttempt($questionScore, $ans, $quesId, $exeId, $i, $this->id);
                    }
                } else {
                    Event::saveQuestionAttempt($questionScore, 0, $quesId, $exeId, 0, $this->id);
                }
            } elseif (in_array($answerType, [MATCHING, DRAGGABLE, MATCHING_DRAGGABLE])) {
                if (isset($matching)) {
                    foreach ($matching as $j => $val) {
                        Event::saveQuestionAttempt($questionScore, $val, $quesId, $exeId, $j, $this->id);
                    }
                }
            } elseif ($answerType == FREE_ANSWER) {
                $answer = $choice;
                Event::saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0, $this->id);
            } elseif ($answerType == ORAL_EXPRESSION) {
                $answer = $choice;
                Event::saveQuestionAttempt(
                    $questionScore,
                    $answer,
                    $quesId,
                    $exeId,
                    0,
                    $this->id,
                    false,
                    $objQuestionTmp->getAbsoluteFilePath()
                );
            } elseif (in_array($answerType, [UNIQUE_ANSWER, UNIQUE_ANSWER_IMAGE, UNIQUE_ANSWER_NO_OPTION])) {
                $answer = $choice;
                Event::saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0, $this->id);
                //            } elseif ($answerType == HOT_SPOT || $answerType == HOT_SPOT_DELINEATION) {
            } elseif ($answerType == HOT_SPOT) {
                $answer = [];
                if (isset($exerciseResultCoordinates[$questionId]) && !empty($exerciseResultCoordinates[$questionId])) {
                    Database::delete(
                        Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT),
                        [
                            'hotspot_exe_id = ? AND hotspot_question_id = ? AND c_id = ?' => [
                                $exeId,
                                $questionId,
                                api_get_course_int_id()
                            ]
                        ]
                    );

                    foreach ($exerciseResultCoordinates[$questionId] as $idx => $val) {
                        $answer[] = $val;
                        $hotspotValue = (int) $choice[$idx] === 1 ? 1 : 0;
                        Event::saveExerciseAttemptHotspot(
                            $exeId,
                            $quesId,
                            $idx,
                            $hotspotValue,
                            $val,
                            false,
                            $this->id
                        );
                    }
                }

                Event::saveQuestionAttempt($questionScore, implode('|', $answer), $quesId, $exeId, 0, $this->id);
            } else {
                Event::saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0,$this->id);
            }
        }

        if ($propagate_neg == 0 && $questionScore < 0) {
            $questionScore = 0;
        }

        if ($saved_results) {
            $stat_table = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
            $sql = 'UPDATE ' . $stat_table . ' SET
                        exe_result = exe_result + ' . floatval($questionScore) . '
                    WHERE exe_id = ' . $exeId;
            Database::query($sql);
        }

        $return_array = array(
            'score'         => $questionScore,
            'weight'        => $questionWeighting,
            'extra'         => $extra_data,
            'open_question' => $arrques,
            'open_answer'   => $arrans,
            'answer_type'   => $answerType
        );

        return $return_array;
    }

    /**
     * Sends a notification when a user ends an examn
     *
     * @param integer $exe_id
     */
    public function send_mail_notification_for_exam($question_list_answers, $origin, $exe_id)
    {
        if (api_get_course_setting('email_alert_manager_on_new_quiz') != 1 ) {
            return null;
        }
        // Email configuration settings
        $courseCode = api_get_course_id();
        $courseInfo = api_get_course_info($courseCode);
        $sessionId = api_get_session_id();

        if (empty($courseInfo)) {
            return false;
        }

        $url_email = api_get_path(WEB_CODE_PATH)
            . 'exercise/exercise_show.php?'
            . api_get_cidreq()
            . '&id_session='
            . $sessionId
            . '&id='
            . $exe_id
            . '&action=qualify';
        $user_info = api_get_user_info(api_get_user_id());

        $msg = get_lang('ExerciseAttempted').'<br /><br />'
                    .get_lang('AttemptDetails').' : <br /><br />'.
                    '<table>'
                        .'<tr>'
                            .'<td><em>'.get_lang('CourseName').'</em></td>'
                            .'<td>&nbsp;<b>#course#</b></td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('TestAttempted').'</td>'
                            .'<td>&nbsp;#exercise#</td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('StudentName').'</td>'
                            .'<td>&nbsp;#firstName# #lastName#</td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('StudentEmail').'</td>'
                            .'<td>&nbsp;#email#</td>'
                        .'</tr>'
                    .'</table>';
        $open_question_list = null;

        $msg = str_replace("#email#", $user_info['email'], $msg);
        $msg1 = str_replace("#exercise#", $this->exercise, $msg);
        $msg = str_replace("#firstName#", $user_info['firstname'], $msg1);
        $msg1 = str_replace("#lastName#", $user_info['lastname'], $msg);
        $msg = str_replace("#course#", $courseInfo['name'], $msg1);

        if ($origin != 'learnpath') {
            $msg.= '<br /><a href="#url#">'.get_lang('ClickToCommentAndGiveFeedback').'</a>';
        }
        $msg1 = str_replace("#url#", $url_email, $msg);
        $mail_content = $msg1;
        $subject = get_lang('ExerciseAttempted');

        if (!empty($sessionId)) {
            $addGeneralCoach = true;
            $setting = api_get_configuration_value('block_quiz_mail_notification_general_coach');
            if ($setting === true) {
                $addGeneralCoach = false;
            }
            $teachers = CourseManager::get_coach_list_from_course_code(
                $courseCode,
                $sessionId,
                $addGeneralCoach
            );
        } else {
            $teachers = CourseManager::get_teacher_list_from_course_code($courseCode);
        }

        if (!empty($teachers)) {
            foreach ($teachers as $user_id => $teacher_data) {
                MessageManager::send_message_simple(
                    $user_id,
                    $subject,
                    $mail_content
                );
            }
        }
    }

    /**
     * Sends a notification when a user ends an examn
     *
     * @param integer $exe_id
     */
    function send_notification_for_open_questions($question_list_answers, $origin, $exe_id)
    {
        if (api_get_course_setting('email_alert_manager_on_new_quiz') != 1 ) {
            return null;
        }
        // Email configuration settings
        $courseCode     = api_get_course_id();
        $course_info    = api_get_course_info($courseCode);

        $url_email = api_get_path(WEB_CODE_PATH)
            . 'exercise/exercise_show.php?'
            . api_get_cidreq()
            . '&id_session='
            . api_get_session_id()
            . '&id='
            . $exe_id
            . '&action=qualify';
        $user_info = api_get_user_info(api_get_user_id());

        $msg = get_lang('OpenQuestionsAttempted').'<br /><br />'
                    .get_lang('AttemptDetails').' : <br /><br />'
                    .'<table>'
                        .'<tr>'
                            .'<td><em>'.get_lang('CourseName').'</em></td>'
                            .'<td>&nbsp;<b>#course#</b></td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('TestAttempted').'</td>'
                            .'<td>&nbsp;#exercise#</td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('StudentName').'</td>'
                            .'<td>&nbsp;#firstName# #lastName#</td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('StudentEmail').'</td>'
                            .'<td>&nbsp;#mail#</td>'
                        .'</tr>'
                    .'</table>';
        $open_question_list = null;
        foreach ($question_list_answers as $item) {
            $question    = $item['question'];
            $answer      = $item['answer'];
            $answer_type = $item['answer_type'];

            if (!empty($question) && !empty($answer) && $answer_type == FREE_ANSWER) {
                $open_question_list .=
                    '<tr>'
                        .'<td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;'.get_lang('Question').'</td>'
                        .'<td width="473" valign="top" bgcolor="#F3F3F3">'.$question.'</td>'
                    .'</tr>'
                    .'<tr>'
                        .'<td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;'.get_lang('Answer').'</td>'
                        .'<td valign="top" bgcolor="#F3F3F3">'.$answer.'</td>'
                    .'</tr>';
            }
        }

        if (!empty($open_question_list)) {
            $msg .= '<p><br />'.get_lang('OpenQuestionsAttemptedAre').' :</p>'.
                    '<table width="730" height="136" border="0" cellpadding="3" cellspacing="3">';
            $msg .= $open_question_list;
            $msg .= '</table><br />';


            $msg1   = str_replace("#exercise#",    $this->exercise, $msg);
            $msg    = str_replace("#firstName#",   $user_info['firstname'],$msg1);
            $msg1   = str_replace("#lastName#",    $user_info['lastname'],$msg);
            $msg    = str_replace("#mail#",        $user_info['email'],$msg1);
            $msg    = str_replace("#course#",      $course_info['name'],$msg1);

            if ($origin != 'learnpath') {
                $msg .= '<br /><a href="#url#">'.get_lang('ClickToCommentAndGiveFeedback').'</a>';
            }
            $msg1 = str_replace("#url#", $url_email, $msg);
            $mail_content = $msg1;
            $subject = get_lang('OpenQuestionsAttempted');

            if (api_get_session_id()) {
                $teachers = CourseManager::get_coach_list_from_course_code($courseCode, api_get_session_id());
            } else {
                $teachers = CourseManager::get_teacher_list_from_course_code($courseCode);
            }

            if (!empty($teachers)) {
                foreach ($teachers as $user_id => $teacher_data) {
                    MessageManager::send_message_simple(
                        $user_id,
                        $subject,
                        $mail_content
                    );
                }
            }
        }
    }

    function send_notification_for_oral_questions($question_list_answers, $origin, $exe_id)
    {
        if (api_get_course_setting('email_alert_manager_on_new_quiz') != 1 ) {
            return null;
        }
        // Email configuration settings
        $courseCode     = api_get_course_id();
        $course_info    = api_get_course_info($courseCode);

        $url_email = api_get_path(WEB_CODE_PATH)
            . 'exercise/exercise_show.php?'
            . api_get_cidreq()
            . '&id_session='
            . api_get_session_id()
            . '&id='
            . $exe_id
            . '&action=qualify';
        $user_info = api_get_user_info(api_get_user_id());

        $oral_question_list = null;
        foreach ($question_list_answers as $item) {
            $question    = $item['question'];
            $answer      = $item['answer'];
            $answer_type = $item['answer_type'];

            if (!empty($question) && !empty($answer) && $answer_type == ORAL_EXPRESSION) {
                $oral_question_list.='<br /><table width="730" height="136" border="0" cellpadding="3" cellspacing="3">'
                    .'<tr>'
                        .'<td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;'.get_lang('Question').'</td>'
                        .'<td width="473" valign="top" bgcolor="#F3F3F3">'.$question.'</td>'
                    .'</tr>'
                    .'<tr>'
                        .'<td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;'.get_lang('Answer').'</td>'
                        .'<td valign="top" bgcolor="#F3F3F3">'.$answer.'</td>'
                    .'</tr></table>';
            }
        }

        if (!empty($oral_question_list)) {
            $msg = get_lang('OralQuestionsAttempted').'<br /><br />
                    '.get_lang('AttemptDetails').' : <br /><br />'
                    .'<table>'
                        .'<tr>'
                            .'<td><em>'.get_lang('CourseName').'</em></td>'
                            .'<td>&nbsp;<b>#course#</b></td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('TestAttempted').'</td>'
                            .'<td>&nbsp;#exercise#</td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('StudentName').'</td>'
                            .'<td>&nbsp;#firstName# #lastName#</td>'
                        .'</tr>'
                        .'<tr>'
                            .'<td>'.get_lang('StudentEmail').'</td>'
                            .'<td>&nbsp;#mail#</td>'
                        .'</tr>'
                    .'</table>';
            $msg .=  '<br />'.sprintf(get_lang('OralQuestionsAttemptedAreX'),$oral_question_list).'<br />';
            $msg1 = str_replace("#exercise#", $this->exercise, $msg);
            $msg = str_replace("#firstName#", $user_info['firstname'], $msg1);
            $msg1 = str_replace("#lastName#", $user_info['lastname'], $msg);
            $msg = str_replace("#mail#", $user_info['email'], $msg1);
            $msg = str_replace("#course#", $course_info['name'], $msg1);

            if ($origin != 'learnpath') {
                $msg.= '<br /><a href="#url#">'.get_lang('ClickToCommentAndGiveFeedback').'</a>';
            }
            $msg1 = str_replace("#url#", $url_email, $msg);
            $mail_content = $msg1;
            $subject = get_lang('OralQuestionsAttempted');

            if (api_get_session_id()) {
                $teachers = CourseManager::get_coach_list_from_course_code($courseCode, api_get_session_id());
            } else {
                $teachers = CourseManager::get_teacher_list_from_course_code($courseCode);
            }

            if (!empty($teachers)) {
                foreach ($teachers as $user_id => $teacher_data) {
                    MessageManager::send_message_simple(
                        $user_id,
                        $subject,
                        $mail_content
                    );
                }
            }
        }
    }

    /**
     * @param array $user_data result of api_get_user_info()
     * @param string $start_date
     * @param null $duration
     * @param string $ip Optional. The user IP
     * @return string
     */
    public function show_exercise_result_header($user_data, $start_date = null, $duration = null, $ip = null)
    {
        $array = array();

        if (!empty($user_data)) {
            $array[] = array('title' => get_lang('Name'), 'content' => $user_data['complete_name']);
            $array[] = array('title' => get_lang('Username'), 'content' => $user_data['username']);
            if (!empty($user_data['official_code'])) {
                $array[] = array(
                    'title' => get_lang('OfficialCode'),
                    'content' => $user_data['official_code']
                );
            }
        }
        // Description can be very long and is generally meant to explain
        //   rules *before* the exam. Leaving here to make display easier if
        //   necessary
        /*
        if (!empty($this->description)) {
            $array[] = array('title' => get_lang("Description"), 'content' => $this->description);
        }
        */
        if (!empty($start_date)) {
            $array[] = array('title' => get_lang('StartDate'), 'content' => $start_date);
        }

        if (!empty($duration)) {
            $array[] = array('title' => get_lang('Duration'), 'content' => $duration);
        }

        if (!empty($ip)) {
            $array[] = array('title' => get_lang('IP'), 'content' => $ip);
        }
        $html  = '<div class="question-result">';
        $html .= Display::page_header(
            Display::return_icon('test-quiz.png', get_lang('Result'),null, ICON_SIZE_MEDIUM).' '.$this->exercise.' : '.get_lang('Result')
        );
        $html .= Display::description($array);
        $html .="</div>";
        return $html;
    }

    /**
     * Create a quiz from quiz data
     * @param string  Title
     * @param int     Time before it expires (in minutes)
     * @param int     Type of exercise
     * @param int     Whether it's randomly picked questions (1) or not (0)
     * @param int     Whether the exercise is visible to the user (1) or not (0)
     * @param int     Whether the results are show to the user (0) or not (1)
     * @param int     Maximum number of attempts (0 if no limit)
     * @param int     Feedback type
     * @todo this was function was added due the import exercise via CSV
     * @return    int New exercise ID
     */
    public function createExercise(
        $title,
        $expired_time = 0,
        $type = 2,
        $random = 0,
        $active = 1,
        $results_disabled = 0,
        $max_attempt = 0,
        $feedback = 3,
        $propagateNegative = 0
    ) {
        $tbl_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
        $type = intval($type);
        $random = intval($random);
        $active = intval($active);
        $results_disabled = intval($results_disabled);
        $max_attempt = intval($max_attempt);
        $feedback = intval($feedback);
        $expired_time = intval($expired_time);
        $title = Database::escape_string($title);
        $propagateNegative = intval($propagateNegative);
        $sessionId = api_get_session_id();
        $course_id = api_get_course_int_id();
        // Save a new quiz
        $sql = "INSERT INTO $tbl_quiz (
                c_id,
                title,
                type,
                random,
                active,
                results_disabled,
                max_attempt,
                start_time,
                end_time,
                feedback_type,
                expired_time,
                session_id,
                propagate_neg
            )
            VALUES (
                '$course_id',
                '$title',
                $type,
                $random,
                $active,
                $results_disabled,
                $max_attempt,
                '',
                '',
                $feedback,
                $expired_time,
                $sessionId,
                $propagateNegative
            )";
        Database::query($sql);
        $quiz_id = Database::insert_id();

        if ($quiz_id) {

            $sql = "UPDATE $tbl_quiz SET id = iid WHERE iid = {$quiz_id} ";
            Database::query($sql);
        }

        return $quiz_id;
    }

    function process_geometry()
    {

    }

    /**
     * Returns the exercise result
     * @param 	int		attempt id
     * @return 	float 	exercise result
     */
    public function get_exercise_result($exe_id)
    {
        $result = array();
        $track_exercise_info = ExerciseLib::get_exercise_track_exercise_info($exe_id);

        if (!empty($track_exercise_info)) {
            $totalScore = 0;
            $objExercise = new Exercise();
            $objExercise->read($track_exercise_info['exe_exo_id']);
            if (!empty($track_exercise_info['data_tracking'])) {
                $question_list = explode(',', $track_exercise_info['data_tracking']);
            }
            foreach ($question_list as $questionId) {
                $question_result = $objExercise->manage_answer(
                    $exe_id,
                    $questionId,
                    '',
                    'exercise_show',
                    array(),
                    false,
                    true,
                    false,
                    $objExercise->selectPropagateNeg()
                );
                $totalScore      += $question_result['score'];
            }

            if ($objExercise->selectPropagateNeg() == 0 && $totalScore < 0) {
                $totalScore = 0;
            }
            $result = array(
                'score' => $totalScore,
                'weight' => $track_exercise_info['exe_weighting']
            );
        }
        return $result;
    }

    /**
     * Checks if the exercise is visible due a lot of conditions
     * visibility, time limits, student attempts
     * Return associative array
     * value : true if execise visible
     * message : HTML formated message
     * rawMessage : text message
     * @param int $lpId
     * @param int $lpItemId
     * @param int $lpItemViewId
     * @param bool $filterByAdmin
     * @return array
     */
    public function is_visible(
        $lpId = 0,
        $lpItemId = 0,
        $lpItemViewId = 0,
        $filterByAdmin = true
    ) {
        // 1. By default the exercise is visible
        $isVisible = true;
        $message = null;

        // 1.1 Admins and teachers can access to the exercise
        if ($filterByAdmin) {
            if (api_is_platform_admin() || api_is_course_admin()) {
                return array('value' => true, 'message' => '');
            }
        }

        // Deleted exercise.
        if ($this->active == -1) {
            return array(
                'value' => false,
                'message' => Display::return_message(get_lang('ExerciseNotFound'), 'warning', false),
                'rawMessage' => get_lang('ExerciseNotFound')
            );
        }

        // Checking visibility in the item_property table.
        $visibility = api_get_item_visibility(
            api_get_course_info(),
            TOOL_QUIZ,
            $this->id,
            api_get_session_id()
        );

        if ($visibility == 0 || $visibility == 2) {
            $this->active = 0;
        }

        // 2. If the exercise is not active.
        if (empty($lpId)) {
            // 2.1 LP is OFF
            if ($this->active == 0) {
                return array(
                    'value' => false,
                    'message' => Display::return_message(get_lang('ExerciseNotFound'), 'warning', false),
                    'rawMessage' => get_lang('ExerciseNotFound')
                );
            }
        } else {
            // 2.1 LP is loaded
            if ($this->active == 0 && !learnpath::is_lp_visible_for_student($lpId, api_get_user_id())) {
                return array(
                    'value' => false,
                    'message' => Display::return_message(get_lang('ExerciseNotFound'), 'warning', false),
                    'rawMessage' => get_lang('ExerciseNotFound')
                );
            }
        }

        //3. We check if the time limits are on
        if (!empty($this->start_time) || !empty($this->end_time)) {
            $limitTimeExists = true;
        } else {
            $limitTimeExists = false;
        }

        if ($limitTimeExists) {
            $timeNow = time();

            $existsStartDate = false;
            $nowIsAfterStartDate = true;
            $existsEndDate = false;
            $nowIsBeforeEndDate = true;

            if (!empty($this->start_time)) {
                $existsStartDate = true;
            }

            if (!empty($this->end_time)) {
                $existsEndDate = true;
            }

            // check if we are before-or-after end-or-start date
            if ($existsStartDate && $timeNow < api_strtotime($this->start_time, 'UTC')) {
                $nowIsAfterStartDate = false;
                    }

            if ($existsEndDate & $timeNow >= api_strtotime($this->end_time, 'UTC')) {
                $nowIsBeforeEndDate = false;
                }

            // lets check all cases
            if ($existsStartDate && !$existsEndDate) {
                // exists start date and dont exists end date
                if ($nowIsAfterStartDate) {
                    // after start date, no end date
                    $isVisible = true;
                    $message = sprintf(get_lang('ExerciseAvailableSinceX'),
                        api_convert_and_format_date($this->start_time));
                } else {
                    // before start date, no end date
                    $isVisible = false;
                    $message = sprintf(get_lang('ExerciseAvailableFromX'),
                        api_convert_and_format_date($this->start_time));
            }
            } else if (!$existsStartDate && $existsEndDate) {
                // doesnt exist start date, exists end date
                if ($nowIsBeforeEndDate) {
                    // before end date, no start date
                    $isVisible = true;
                    $message = sprintf(get_lang('ExerciseAvailableUntilX'),
                        api_convert_and_format_date($this->end_time));
                } else {
                    // after end date, no start date
                    $isVisible = false;
                    $message = sprintf(get_lang('ExerciseAvailableUntilX'),
                        api_convert_and_format_date($this->end_time));
                }
            } elseif ($existsStartDate && $existsEndDate) {
                // exists start date and end date
                if ($nowIsAfterStartDate) {
                    if ($nowIsBeforeEndDate) {
                        // after start date and before end date
                        $isVisible = true;
                        $message = sprintf(get_lang('ExerciseIsActivatedFromXToY'),
                    api_convert_and_format_date($this->start_time),
                            api_convert_and_format_date($this->end_time));
                    } else {
                        // after start date and after end date
                        $isVisible = false;
                        $message = sprintf(get_lang('ExerciseWasActivatedFromXToY'),
                            api_convert_and_format_date($this->start_time),
                            api_convert_and_format_date($this->end_time));
                    }
                } else {
                    if ($nowIsBeforeEndDate) {
                        // before start date and before end date
                        $isVisible = false;
                        $message = sprintf(get_lang('ExerciseWillBeActivatedFromXToY'),
                            api_convert_and_format_date($this->start_time),
                            api_convert_and_format_date($this->end_time));
                    }
                    // case before start date and after end date is impossible
                }
            } elseif (!$existsStartDate && !$existsEndDate) {
                // doesnt exist start date nor end date
                $isVisible = true;
                $message = "";
            }
        }

        // 4. We check if the student have attempts
        $exerciseAttempts = $this->selectAttempts();

        if ($isVisible) {
            if ($exerciseAttempts > 0) {

                $attemptCount = Event::get_attempt_count_not_finished(
                    api_get_user_id(),
                    $this->id,
                    $lpId,
                    $lpItemId,
                    $lpItemViewId
                );

                if ($attemptCount >= $exerciseAttempts) {
                    $message = sprintf(
                        get_lang('ReachedMaxAttempts'),
                        $this->name,
                        $exerciseAttempts
                    );
                    $isVisible = false;
                }
            }
        }

        $rawMessage = "";
        if (!empty($message)){
            $rawMessage = $message;
            $message = Display::return_message($message, 'warning', false);
        }

        return array(
            'value' => $isVisible,
            'message' => $message,
            'rawMessage' => $rawMessage
        );
    }

    public function added_in_lp()
    {
        $TBL_LP_ITEM = Database::get_course_table(TABLE_LP_ITEM);
        $sql = "SELECT max_score FROM $TBL_LP_ITEM
            WHERE c_id = {$this->course_id} AND item_type = '" . TOOL_QUIZ . "' AND path = '{$this->id}'";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Returns an array with the media list
     * @param array question list
     * @example there's 1 question with iid 5 that belongs to the media question with iid = 100
     * <code>
     * array (size=2)
     *  999 =>
     *    array (size=3)
     *      0 => int 7
     *      1 => int 6
     *      2 => int 3254
     *  100 =>
     *   array (size=1)
     *      0 => int 5
     *  </code>
     * @return array
     */
    private function setMediaList($questionList)
    {
        $mediaList = array();
        if (!empty($questionList)) {
            foreach ($questionList as $questionId) {
                $objQuestionTmp = Question::read($questionId, $this->course_id);

                // If a media question exists
                if (isset($objQuestionTmp->parent_id) && $objQuestionTmp->parent_id != 0) {
                    $mediaList[$objQuestionTmp->parent_id][] = $objQuestionTmp->id;
                } else {
                    //Always the last item
                    $mediaList[999][] = $objQuestionTmp->id;
                }
            }
        }
        $this->mediaList = $mediaList;
    }

    /**
     * Returns an array with this form
     * @example
     * <code>
     * array (size=3)
    999 =>
    array (size=3)
    0 => int 3422
    1 => int 3423
    2 => int 3424
    100 =>
    array (size=2)
    0 => int 3469
    1 => int 3470
    101 =>
    array (size=1)
    0 => int 3482
     * </code>
     * The array inside the key 999 means the question list that belongs to the media id = 999,
     * this case is special because 999 means "no media".
     * @return array
     */
    public function getMediaList()
    {
        return $this->mediaList;
    }

    /**
     * Is media question activated?
     * @return bool
     */
    public function mediaIsActivated()
    {
        $mediaQuestions = $this->getMediaList();
        $active = false;
        if (isset($mediaQuestions) && !empty($mediaQuestions)) {
            $media_count = count($mediaQuestions);
            if ($media_count > 1) {
                return true;
            } elseif ($media_count == 1) {
                if (isset($mediaQuestions[999])) {
                    return false;
                } else {
                    return true;
                }
            }
        }

        return $active;
    }

    /**
     * Gets question list from the exercise
     *
     * @return array
     */
    public function getQuestionList()
    {
        return $this->questionList;
    }

    /**
     * Question list with medias compressed like this
     * @example
     * <code>
     * array(
     *      question_id_1,
     *      question_id_2,
     *      media_id, <- this media id contains question ids
     *      question_id_3,
     * )
     * </code>
     * @return array
     */
    public function getQuestionListWithMediasCompressed()
    {
        return $this->questionList;
    }

    /**
     * Question list with medias uncompressed like this
     * @example
     * <code>
     * array(
     *      question_id,
     *      question_id,
     *      question_id, <- belongs to a media id
     *      question_id, <- belongs to a media id
     *      question_id,
     * )
     * </code>
     * @return array
     */
    public function getQuestionListWithMediasUncompressed()
    {
        return $this->questionListUncompressed;
    }

    /**
     * Sets the question list when the exercise->read() is executed
     */
    public function setQuestionList()
    {
        // Getting question list.
        $questionList = $this->selectQuestionList(true);

        $this->setMediaList($questionList);

        $this->questionList = $this->transformQuestionListWithMedias($questionList, false);
        $this->questionListUncompressed = $this->transformQuestionListWithMedias($questionList, true);
    }

    /**
     *
     * @params array question list
     * @params bool expand or not question list (true show all questions, false show media question id instead of the question ids)
     *
     **/
    public function transformQuestionListWithMedias($question_list, $expand_media_questions = false)
    {
        $new_question_list = array();
        if (!empty($question_list)) {
            $media_questions = $this->getMediaList();

            $media_active = $this->mediaIsActivated($media_questions);

            if ($media_active) {
                $counter = 1;
                foreach ($question_list as $question_id) {
                    $add_question = true;
                    foreach ($media_questions as $media_id => $question_list_in_media) {
                        if ($media_id != 999 && in_array($question_id, $question_list_in_media)) {
                            $add_question = false;
                            if (!in_array($media_id, $new_question_list)) {
                                $new_question_list[$counter] = $media_id;
                                $counter++;
                            }
                            break;
                        }
                    }
                    if ($add_question) {
                        $new_question_list[$counter] = $question_id;
                        $counter++;
                    }
                }
                if ($expand_media_questions) {
                    $media_key_list = array_keys($media_questions);
                    foreach ($new_question_list as &$question_id) {
                        if (in_array($question_id, $media_key_list)) {
                            $question_id = $media_questions[$question_id];
                        }
                    }
                    $new_question_list = array_flatten($new_question_list);
                }
            } else {
                $new_question_list = $question_list;
            }
        }

        return $new_question_list;
    }

    function get_validated_question_list()
    {
        $tabres = array();
        $isRandomByCategory = $this->isRandomByCat();
        if ($isRandomByCategory == 0) {
            if ($this->isRandom()) {
                $tabres = $this->selectRandomList();
            } else {
                $tabres = $this->selectQuestionList();
            }
        } else {
            if ($this->isRandom()) {
                // USE question categories
                // get questions by category for this exercise
                // we have to choice $objExercise->random question in each array values of $tabCategoryQuestions
                // key of $tabCategoryQuestions are the categopy id (0 for not in a category)
                // value is the array of question id of this category
                $questionList = array();
                $tabCategoryQuestions = TestCategory::getQuestionsByCat($this->id);
                $isRandomByCategory = $this->selectRandomByCat();
                // We sort categories based on the term between [] in the head
                // of the category's description
                /* examples of categories :
                 * [biologie] Maitriser les mecanismes de base de la genetique
                 * [biologie] Relier les moyens de depenses et les agents infectieux
                 * [biologie] Savoir ou est produite l'enrgie dans les cellules et sous quelle forme
                 * [chimie] Classer les molles suivant leur pouvoir oxydant ou reacteur
                 * [chimie] Connaître la denition de la theoie acide/base selon Brönsted
                 * [chimie] Connaître les charges des particules
                 * We want that in the order of the groups defined by the term
                 * between brackets at the beginning of the category title
                */
                // If test option is Grouped By Categories
                if ($isRandomByCategory == 2) {
                    $tabCategoryQuestions = TestCategory::sortTabByBracketLabel($tabCategoryQuestions);
                }
                while (list($cat_id, $tabquestion) = each($tabCategoryQuestions)) {
                    $number_of_random_question = $this->random;
                    if ($this->random == -1) {
                        $number_of_random_question = count($this->questionList);
                    }
                    $questionList = array_merge(
                        $questionList,
                        TestCategory::getNElementsFromArray(
                            $tabquestion,
                            $number_of_random_question
                        )
                    );
                }
                // shuffle the question list if test is not grouped by categories
                if ($isRandomByCategory == 1) {
                    shuffle($questionList); // or not
                }
                $tabres = $questionList;
            } else {
                // Problem, random by category has been selected and
                // we have no $this->isRandom number of question selected
                // Should not happened
            }
        }
        return $tabres;
    }

    function get_question_list($expand_media_questions = false)
    {
        $question_list = $this->get_validated_question_list();
        $question_list = $this->transform_question_list_with_medias($question_list, $expand_media_questions);
        return $question_list;
    }

    function transform_question_list_with_medias($question_list, $expand_media_questions = false)
    {
        $new_question_list = array();
        if (!empty($question_list)) {
            $media_questions = $this->getMediaList();
            $media_active = $this->mediaIsActivated($media_questions);

            if ($media_active) {
                $counter = 1;
                foreach ($question_list as $question_id) {
                    $add_question = true;
                    foreach ($media_questions as $media_id => $question_list_in_media) {
                        if ($media_id != 999 && in_array($question_id, $question_list_in_media)) {
                            $add_question = false;
                            if (!in_array($media_id, $new_question_list)) {
                                $new_question_list[$counter] = $media_id;
                                $counter++;
                            }
                            break;
                        }
                    }
                    if ($add_question) {
                        $new_question_list[$counter] = $question_id;
                        $counter++;
                    }
                }
                if ($expand_media_questions) {
                    $media_key_list = array_keys($media_questions);
                    foreach ($new_question_list as &$question_id) {
                        if (in_array($question_id, $media_key_list)) {
                            $question_id = $media_questions[$question_id];
                        }
                    }
                    $new_question_list = array_flatten($new_question_list);
                }
            } else {
                $new_question_list = $question_list;
            }
        }
        return $new_question_list;
    }

    /**
     * @param int $exe_id
     * @return array|mixed
     */
    public function get_stat_track_exercise_info_by_exe_id($exe_id)
    {
        $track_exercises = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $exe_id = intval($exe_id);
        $sql_track = "SELECT * FROM $track_exercises WHERE exe_id = $exe_id ";
        $result = Database::query($sql_track);
        $new_array = array();
        if (Database::num_rows($result) > 0 ) {
            $new_array = Database::fetch_array($result, 'ASSOC');

            $new_array['duration'] = null;

            $start_date = api_get_utc_datetime($new_array['start_date'], true);
            $end_date = api_get_utc_datetime($new_array['exe_date'], true);

            if (!empty($start_date) && !empty($end_date)) {
                $start_date = api_strtotime($start_date, 'UTC');
                $end_date = api_strtotime($end_date, 'UTC');
                if ($start_date && $end_date) {
                    $mytime = $end_date- $start_date;
                    $new_learnpath_item = new learnpathItem(null);
                    $time_attemp = $new_learnpath_item->get_scorm_time('js', $mytime);
                    $h = get_lang('h');
                    $time_attemp = str_replace('NaN', '00' . $h . '00\'00"', $time_attemp);
                    $new_array['duration'] = $time_attemp;
                }
            }
        }
        return $new_array;
    }

    public function edit_question_to_remind($exe_id, $question_id, $action = 'add')
    {
        $exercise_info = self::get_stat_track_exercise_info_by_exe_id($exe_id);
        $question_id = intval($question_id);
        $exe_id = intval($exe_id);
        $track_exercises = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        if ($exercise_info) {

            if (empty($exercise_info['questions_to_check'])) {
                if ($action == 'add') {
                    $sql = "UPDATE $track_exercises SET questions_to_check = '$question_id' WHERE exe_id = $exe_id ";
                    Database::query($sql);
                }
            } else {
                $remind_list = explode(',',$exercise_info['questions_to_check']);

                $remind_list_string = '';
                if ($action == 'add') {
                    if (!in_array($question_id, $remind_list)) {
                        $remind_list[] = $question_id;
                        if (!empty($remind_list)) {
                            sort($remind_list);
                            array_filter($remind_list);
                        }
                        $remind_list_string = implode(',', $remind_list);
                    }
                } elseif ($action == 'delete')  {
                    if (!empty($remind_list)) {
                        if (in_array($question_id, $remind_list)) {
                            $remind_list = array_flip($remind_list);
                            unset($remind_list[$question_id]);
                            $remind_list = array_flip($remind_list);

                            if (!empty($remind_list)) {
                                sort($remind_list);
                                array_filter($remind_list);
                                $remind_list_string = implode(',', $remind_list);
                            }
                        }
                    }
                }
                $remind_list_string = Database::escape_string($remind_list_string);
                $sql = "UPDATE $track_exercises SET questions_to_check = '$remind_list_string' WHERE exe_id = $exe_id ";
                Database::query($sql);
            }
        }
    }

    public function fill_in_blank_answer_to_array($answer)
    {
        api_preg_match_all('/\[[^]]+\]/', $answer, $teacher_answer_list);
        $teacher_answer_list = $teacher_answer_list[0];
        return $teacher_answer_list;
    }

    public function fill_in_blank_answer_to_string($answer)
    {
        $teacher_answer_list = $this->fill_in_blank_answer_to_array($answer);
        $result = '';
        if (!empty($teacher_answer_list)) {
            $i = 0;
            foreach ($teacher_answer_list as $teacher_item) {
                $value = null;
                //Cleaning student answer list
                $value = strip_tags($teacher_item);
                $value = api_substr($value, 1, api_strlen($value) - 2);
                $value = explode('/', $value);
                if (!empty($value[0])) {
                    $value = trim($value[0]);
                    $value = str_replace('&nbsp;', '', $value);
                    $result .= $value;
                }
            }
        }
        return $result;
    }

    function return_time_left_div()
    {
        $html = '<div id="clock_warning" style="display:none">';
        $html .= Display::return_message(
            get_lang('ReachedTimeLimit'),
            'warning'
        );
        $html .= ' ';
        $html .= sprintf(
            get_lang('YouWillBeRedirectedInXSeconds'),
            '<span id="counter_to_redirect" class="red_alert"></span>'
        );
        $html .= '</div>';
        $html .= '<div id="exercise_clock_warning" class="count_down"></div>';
        return $html;
    }

    function get_count_question_list()
    {
        //Real question count
        $question_count = 0;
        $question_list = $this->get_question_list();
        if (!empty($question_list)) {
            $question_count = count($question_list);
        }
        return $question_count;
    }

    function get_exercise_list_ordered()
    {
        $table_exercise_order = Database::get_course_table(TABLE_QUIZ_ORDER);
        $course_id = api_get_course_int_id();
        $session_id = api_get_session_id();
        $sql = "SELECT exercise_id, exercise_order
                FROM $table_exercise_order
                WHERE c_id = $course_id AND session_id = $session_id
                ORDER BY exercise_order";
        $result = Database::query($sql);
        $list = array();
        if (Database::num_rows($result)) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $list[$row['exercise_order']] = $row['exercise_id'];
            }
        }
        return $list;
    }

    /**
     * Get categories added in the exercise--category matrix
     * @return bool
     */
    public function get_categories_in_exercise()
    {
        $table = Database::get_course_table(TABLE_QUIZ_REL_CATEGORY);
        if (!empty($this->id)) {
            $sql = "SELECT * FROM $table
                    WHERE exercise_id = {$this->id} AND c_id = {$this->course_id} ";
            $result = Database::query($sql);
            $list = array();
            if (Database::num_rows($result)) {
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    $list[$row['category_id']] = $row;
                }
                return $list;
            }
        }
        return false;
    }

    /**
     * @param null $order
     * @return bool
     */
    public function get_categories_with_name_in_exercise($order = null)
    {
        $table = Database::get_course_table(TABLE_QUIZ_REL_CATEGORY);
        $table_category = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
        $sql = "SELECT * FROM $table qc
                INNER JOIN $table_category c
                ON (category_id = c.iid)
                WHERE exercise_id = {$this->id} AND qc.c_id = {$this->course_id} ";
        if (!empty($order)) {
            $sql .= "ORDER BY $order ";
        }
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $list[$row['category_id']] = $row;
            }
            return $list;
        }
        return false;
    }

    /**
     * Get total number of question that will be parsed when using the category/exercise
     */
    public function getNumberQuestionExerciseCategory()
    {
        $table = Database::get_course_table(TABLE_QUIZ_REL_CATEGORY);
        if (!empty($this->id)) {
            $sql = "SELECT SUM(count_questions) count_questions
                    FROM $table
                    WHERE exercise_id = {$this->id} AND c_id = {$this->course_id}";
            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $row = Database::fetch_array($result);
                return $row['count_questions'];
            }
        }
        return 0;
    }

    /**
     * Save categories in the TABLE_QUIZ_REL_CATEGORY table
     * @param array $categories
     */
    public function save_categories_in_exercise($categories)
    {
        if (!empty($categories) && !empty($this->id)) {
            $table = Database::get_course_table(TABLE_QUIZ_REL_CATEGORY);
            $sql = "DELETE FROM $table
                    WHERE exercise_id = {$this->id} AND c_id = {$this->course_id}";
            Database::query($sql);
            if (!empty($categories)) {
                foreach ($categories as $category_id => $count_questions) {
                    $params = array(
                        'c_id' => $this->course_id,
                        'exercise_id' => $this->id,
                        'category_id' => $category_id,
                        'count_questions' => $count_questions
                    );
                    Database::insert($table, $params);
                }
            }
        }
    }

    /**
     * @param array $questionList
     * @param int $currentQuestion
     * @param array $conditions
     * @param string $link
     * @return string
     */
    public function progressExercisePaginationBar($questionList, $currentQuestion, $conditions, $link)
    {
        $mediaQuestions = $this->getMediaList();

        $html = '<div class="exercise_pagination pagination pagination-mini"><ul>';
        $counter = 0;
        $nextValue = 0;
        $wasMedia = false;
        $before = 0;
        $counterNoMedias = 0;
        foreach ($questionList as $questionId) {
            $isCurrent = $currentQuestion == ($counterNoMedias + 1) ? true : false;

            if (!empty($nextValue)) {
                if ($wasMedia) {
                    $nextValue = $nextValue - $before + 1;
                }
            }

            if (isset($mediaQuestions) && isset($mediaQuestions[$questionId])) {
                $fixedValue = $counterNoMedias;

                $html .= Display::progressPaginationBar(
                    $nextValue,
                    $mediaQuestions[$questionId],
                    $currentQuestion,
                    $fixedValue,
                    $conditions,
                    $link,
                    true,
                    true
                );

                $counter += count($mediaQuestions[$questionId]) - 1 ;
                $before = count($questionList);
                $wasMedia = true;
                $nextValue += count($questionList);
            } else {
                $html .= Display::parsePaginationItem($questionId, $isCurrent, $conditions, $link, $counter);
                $counter++;
                $nextValue++;
                $wasMedia = false;
            }
            $counterNoMedias++;
        }
        $html .= '</ul></div>';
        return $html;
    }


    /**
     *  Shows a list of numbers that represents the question to answer in a exercise
     *
     * @param array $categories
     * @param int $current
     * @param array $conditions
     * @param string $link
     * @return string
     */
    public function progressExercisePaginationBarWithCategories(
        $categories,
        $current,
        $conditions = array(),
        $link = null
    ) {
        $html = null;
        $counterNoMedias = 0;
        $nextValue = 0;
        $wasMedia = false;
        $before = 0;

        if (!empty($categories)) {
            $selectionType = $this->getQuestionSelectionType();
            $useRootAsCategoryTitle = false;

            // Grouping questions per parent category see BT#6540

            if (in_array(
                $selectionType,
                array(
                    EX_Q_SELECTION_CATEGORIES_ORDERED_BY_PARENT_QUESTIONS_ORDERED,
                    EX_Q_SELECTION_CATEGORIES_ORDERED_BY_PARENT_QUESTIONS_RANDOM
                )
            )) {
                $useRootAsCategoryTitle = true;
            }

            // If the exercise is set to only show the titles of the categories
            // at the root of the tree, then pre-order the categories tree by
            // removing children and summing their questions into the parent
            // categories

            if ($useRootAsCategoryTitle) {
                // The new categories list starts empty
                $newCategoryList = array();
                foreach ($categories as $category) {
                    $rootElement = $category['root'];

                    if (isset($category['parent_info'])) {
                        $rootElement = $category['parent_info']['id'];
                    }

                    //$rootElement = $category['id'];
                    // If the current category's ancestor was never seen
                    // before, then declare it and assign the current
                    // category to it.
                    if (!isset($newCategoryList[$rootElement])) {
                        $newCategoryList[$rootElement] = $category;
                    } else {
                        // If it was already seen, then merge the previous with
                        // the current category
                        $oldQuestionList = $newCategoryList[$rootElement]['question_list'];
                        $category['question_list'] = array_merge($oldQuestionList , $category['question_list']);
                        $newCategoryList[$rootElement] = $category;
                    }
                }
                // Now use the newly built categories list, with only parents
                $categories = $newCategoryList;
            }

            foreach ($categories as $category) {
                $questionList = $category['question_list'];
                // Check if in this category there questions added in a media
                $mediaQuestionId = $category['media_question'];
                $isMedia = false;
                $fixedValue = null;

                // Media exists!
                if ($mediaQuestionId != 999) {
                    $isMedia = true;
                    $fixedValue = $counterNoMedias;
                }

                //$categoryName = $category['path']; << show the path
                $categoryName = $category['name'];

                if ($useRootAsCategoryTitle) {
                    if (isset($category['parent_info'])) {
                        $categoryName  = $category['parent_info']['title'];
                    }
                }
                $html .= '<div class="row">';
                $html .= '<div class="span2">'.$categoryName.'</div>';
                $html .= '<div class="span8">';

                if (!empty($nextValue)) {
                    if ($wasMedia) {
                        $nextValue = $nextValue - $before + 1;
                    }
                }
                $html .= Display::progressPaginationBar(
                    $nextValue,
                    $questionList,
                    $current,
                    $fixedValue,
                    $conditions,
                    $link,
                    $isMedia,
                    true
                );
                $html .= '</div>';
                $html .= '</div>';

                if ($mediaQuestionId == 999) {
                    $counterNoMedias += count($questionList);
                } else {
                    $counterNoMedias++;
                }

                $nextValue += count($questionList);
                $before = count($questionList);

                if ($mediaQuestionId != 999) {
                    $wasMedia = true;
                } else {
                    $wasMedia = false;
                }

            }
        }
        return $html;
    }

    /**
     * Renders a question list
     *
     * @param array $questionList (with media questions compressed)
     * @param int $currentQuestion
     * @param array $exerciseResult
     * @param array $attemptList
     * @param array $remindList
     */
    public function renderQuestionList($questionList, $currentQuestion, $exerciseResult, $attemptList, $remindList)
    {
        $mediaQuestions = $this->getMediaList();
        $i = 0;

        // Normal question list render (medias compressed)
        foreach ($questionList as $questionId) {
            $i++;
            // For sequential exercises

            if ($this->type == ONE_PER_PAGE) {
                // If it is not the right question, goes to the next loop iteration
                if ($currentQuestion != $i) {
                    continue;
                } else {
                    if ($this->feedback_type != EXERCISE_FEEDBACK_TYPE_DIRECT) {
                        // if the user has already answered this question
                        if (isset($exerciseResult[$questionId])) {
                            Display::display_normal_message(get_lang('AlreadyAnswered'));
                            break;
                        }
                    }
                }
            }

            // The $questionList contains the media id we check if this questionId is a media question type

            if (isset($mediaQuestions[$questionId]) && $mediaQuestions[$questionId] != 999) {

                // The question belongs to a media
                $mediaQuestionList = $mediaQuestions[$questionId];
                $objQuestionTmp = Question::read($questionId);

                $counter = 1;
                if ($objQuestionTmp->type == MEDIA_QUESTION) {
                    echo $objQuestionTmp->show_media_content();

                    $countQuestionsInsideMedia = count($mediaQuestionList);

                    // Show questions that belongs to a media
                    if (!empty($mediaQuestionList)) {
                        // In order to parse media questions we use letters a, b, c, etc.
                        $letterCounter = 97;
                        foreach ($mediaQuestionList as $questionIdInsideMedia) {
                            $isLastQuestionInMedia = false;
                            if ($counter == $countQuestionsInsideMedia) {
                                $isLastQuestionInMedia = true;
                            }
                            $this->renderQuestion(
                                $questionIdInsideMedia,
                                $attemptList,
                                $remindList,
                                chr($letterCounter),
                                $currentQuestion,
                                $mediaQuestionList,
                                $isLastQuestionInMedia,
                                $questionList
                            );
                            $letterCounter++;
                            $counter++;
                        }
                    }
                } else {
                    $this->renderQuestion(
                        $questionId,
                        $attemptList,
                        $remindList,
                        $i,
                        $currentQuestion,
                        null,
                        null,
                        $questionList
                    );
                    $i++;
                }
            } else {
                // Normal question render.
                $this->renderQuestion($questionId, $attemptList, $remindList, $i, $currentQuestion, null, null, $questionList);
            }

            // For sequential exercises.
            if ($this->type == ONE_PER_PAGE) {
                // quits the loop
                break;
            }
        }
        // end foreach()

        if ($this->type == ALL_ON_ONE_PAGE) {
            $exercise_actions =  $this->show_button($questionId, $currentQuestion);
            echo Display::div($exercise_actions, array('class'=>'exercise_actions'));
        }
    }

    /**
     * @param int $questionId
     * @param array $attemptList
     * @param array $remindList
     * @param int $i
     * @param int $current_question
     * @param array $questions_in_media
     * @param bool $last_question_in_media
     * @param array $realQuestionList
     * @param bool $generateJS
     * @return null
     */
    public function renderQuestion(
        $questionId,
        $attemptList,
        $remindList,
        $i,
        $current_question,
        $questions_in_media = array(),
        $last_question_in_media = false,
        $realQuestionList,
        $generateJS = true
    ) {

        // With this option on the question is loaded via AJAX
        //$generateJS = true;
        //$this->loadQuestionAJAX = true;

        if ($generateJS && $this->loadQuestionAJAX) {
            $url = api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?a=get_question&id='.$questionId;
            $params = array(
                'questionId' => $questionId,
                'attemptList'=> $attemptList,
                'remindList' => $remindList,
                'i' => $i,
                'current_question' => $current_question,
                'questions_in_media' => $questions_in_media,
                'last_question_in_media' => $last_question_in_media
            );
            $params = json_encode($params);

            $script = '<script>
            $(function(){
                var params = '.$params.';
                $.ajax({
                    type: "GET",
                    async: false,
                    data: params,
                    url: "'.$url.'",
                    success: function(return_value) {
                        $("#ajaxquestiondiv'.$questionId.'").html(return_value);
                    }
                });
            });
            </script>
            <div id="ajaxquestiondiv'.$questionId.'"></div>';
            echo $script;
        } else {

            global $origin;
            $question_obj = Question::read($questionId);
            $user_choice = isset($attemptList[$questionId]) ? $attemptList[$questionId] : null;

            $remind_highlight = null;

            //Hides questions when reviewing a ALL_ON_ONE_PAGE exercise see #4542 no_remind_highlight class hide with jquery
            if ($this->type == ALL_ON_ONE_PAGE && isset($_GET['reminder']) && $_GET['reminder'] == 2) {
                $remind_highlight = 'no_remind_highlight';
                if (in_array($question_obj->type, Question::question_type_no_review())) {
                    return null;
                }
            }

            $attributes = array('id' =>'remind_list['.$questionId.']');
            if (is_array($remindList) && in_array($questionId, $remindList)) {
                //$attributes['checked'] = 1;
                //$remind_highlight = ' remind_highlight ';
            }

            // Showing the question

            $exercise_actions  = null;

            echo '<a id="questionanchor'.$questionId.'"></a><br />';
            echo '<div id="question_div_'.$questionId.'" class="main_question '.$remind_highlight.'" >';

            // Shows the question + possible answers
            $showTitle = $this->getHideQuestionTitle() == 1 ? false : true;
            echo $this->showQuestion($question_obj, false, $origin, $i, $showTitle, false, $user_choice, false, null, false, $this->getModelType(), $this->categoryMinusOne);

            // Button save and continue
            switch ($this->type) {
                case ONE_PER_PAGE:
                    $exercise_actions .= $this->show_button($questionId, $current_question, null, $remindList);
                    break;
                case ALL_ON_ONE_PAGE:
                    $button  = '<a href="javascript://" class="btn" onclick="save_now(\''.$questionId.'\', null, true, 1); ">'.get_lang('SaveForNow').'</a>';
                    $button .= '<span id="save_for_now_'.$questionId.'" class="exercise_save_mini_message"></span>&nbsp;';
                    $exercise_actions .= Display::div($button, array('class'=>'exercise_save_now_button'));
                    break;
            }

            if (!empty($questions_in_media)) {
                $count_of_questions_inside_media = count($questions_in_media);
                if ($count_of_questions_inside_media > 1) {
                    $button  = '<a href="javascript://" class="btn" onclick="save_now(\''.$questionId.'\', false, false, 0); ">'.get_lang('SaveForNow').'</a>';
                    $button .= '<span id="save_for_now_'.$questionId.'" class="exercise_save_mini_message"></span>&nbsp;';
                    $exercise_actions = Display::div($button, array('class'=>'exercise_save_now_button'));
                }

                if ($last_question_in_media && $this->type == ONE_PER_PAGE) {
                    $exercise_actions = $this->show_button($questionId, $current_question, $questions_in_media);
                }
            }

            // Checkbox review answers
            if ($this->review_answers && !in_array($question_obj->type, Question::question_type_no_review())) {
                $remind_question_div = Display::tag('label', Display::input('checkbox', 'remind_list['.$questionId.']', '', $attributes).get_lang('ReviewQuestionLater'), array('class' => 'checkbox', 'for' =>'remind_list['.$questionId.']'));
                $exercise_actions   .= Display::div($remind_question_div, array('class'=>'exercise_save_now_button'));
            }

            echo Display::div(' ', array('class'=>'clear'));

            $paginationCounter = null;
            if ($this->type == ONE_PER_PAGE) {
                if (empty($questions_in_media)) {
                    $paginationCounter = Display::paginationIndicator($current_question, count($realQuestionList));
                } else {
                    if ($last_question_in_media) {
                        $paginationCounter = Display::paginationIndicator($current_question, count($realQuestionList));
                    }
                }
            }

            echo '<div class="row"><div class="pull-right">'.$paginationCounter.'</div></div>';
            echo Display::div($exercise_actions, array('class'=>'form-actions'));
            echo '</div>';
        }
    }

    /**
     * Shows a question
     * @param Question $objQuestionTmp
     * @param bool $only_questions if true only show the questions, no exercise title
     * @param bool $origin origin i.e = learnpath
     * @param string $current_item current item from the list of questions
     * @param bool $show_title
     * @param bool $freeze
     * @param array $user_choice
     * @param bool $show_comment
     * @param null $exercise_feedback
     * @param bool $show_answers
     * @param null $modelType
     * @param bool $categoryMinusOne
     * @return bool|null|string
     */
    public function showQuestion(
        Question $objQuestionTmp,
        $only_questions = false,
        $origin = false,
        $current_item = '',
        $show_title = true,
        $freeze = false,
        $user_choice = array(),
        $show_comment = false,
        $exercise_feedback = null,
        $show_answers = false,
        $modelType = null,
        $categoryMinusOne = true
    ) {
        // Text direction for the current language
        //$is_ltr_text_direction = api_get_text_direction() != 'rtl';
        // Change false to true in the following line to enable answer hinting
        $debug_mark_answer = $show_answers; //api_is_allowed_to_edit() && false;
        // Reads question information
        if (!$objQuestionTmp) {
            // Question not found
            return false;
        }

        $html = null;

        $questionId = $objQuestionTmp->id;

        if ($exercise_feedback != EXERCISE_FEEDBACK_TYPE_END) {
            $show_comment = false;
        }

        $answerType = $objQuestionTmp->selectType();
        $pictureName = $objQuestionTmp->selectPicture();

        $s = null;
        $form = new FormValidator('question');
        $renderer = $form->defaultRenderer();
        $form_template = '{content}';
        $renderer->setFormTemplate($form_template);

        if ($answerType != HOT_SPOT && $answerType != HOT_SPOT_DELINEATION) {
            // Question is not a hotspot
            if (!$only_questions) {
                $questionDescription = $objQuestionTmp->selectDescription();
                if ($show_title) {
                    $categoryName = TestCategory::getCategoryNamesForQuestion($objQuestionTmp->id, null, true, $categoryMinusOne);
                    $html .= $categoryName;
                    $html .= Display::div($current_item.'. '.$objQuestionTmp->selectTitle(), array('class' => 'question_title'));
                    if (!empty($questionDescription)) {
                        $html .= Display::div($questionDescription, array('class' => 'question_description'));
                    }
                } else {
                    $html .= '<div class="media">';
                    $html .= '<div class="pull-left">';
                    $html .= '<div class="media-object">';
                    $html .= Display::div($current_item, array('class' => 'question_no_title'));
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<div class="media-body">';
                    if (!empty($questionDescription)) {
                        $html .= Display::div($questionDescription, array('class' => 'question_description'));
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                }
            }

            if (in_array($answerType, array(FREE_ANSWER, ORAL_EXPRESSION)) && $freeze) {
                return null;
            }

            $html .= '<div class="question_options">';
            // construction of the Answer object (also gets all answers details)
            $objAnswerTmp = new Answer($questionId, null, $this);

            $nbrAnswers = $objAnswerTmp->selectNbrAnswers();
            $course_id = api_get_course_int_id();
            $sessionId = api_get_session_id();
            $quiz_question_options = Question::readQuestionOption($questionId, $course_id);

            // For "matching" type here, we need something a little bit special
            // because the match between the suggestions and the answers cannot be
            // done easily (suggestions and answers are in the same table), so we
            // have to go through answers first (elems with "correct" value to 0).
            $select_items = array();
            //This will contain the number of answers on the left side. We call them
            // suggestions here, for the sake of comprehensions, while the ones
            // on the right side are called answers
            $num_suggestions = 0;

            if ($answerType == MATCHING || $answerType == DRAGGABLE) {
                if ($answerType == DRAGGABLE) {
                    $s .= '<div class="ui-widget ui-helper-clearfix">
                            <ul class="drag_question ui-helper-reset ui-helper-clearfix">';
                } else {
                    $s .= '<div id="drag'.$questionId.'_question" class="drag_question">';
                    $s .= '<table class="data_table">';
                }

                $j = 1; //iterate through answers
                $letter = 'A'; //mark letters for each answer
                $answer_matching = array();
                $capital_letter = array();
                //for ($answerId=1; $answerId <= $nbrAnswers; $answerId++) {
                foreach ($objAnswerTmp->answer as $answerId => $answer_item) {
                    $answerCorrect = $objAnswerTmp->isCorrect($answerId);
                    $answer = $objAnswerTmp->selectAnswer($answerId);
                    if ($answerCorrect == 0) {
                        // options (A, B, C, ...) that will be put into the list-box
                        // have the "correct" field set to 0 because they are answer
                        $capital_letter[$j] = $letter;
                        //$answer_matching[$j]=$objAnswerTmp->selectAnswerByAutoId($numAnswer);
                        $answer_matching[$j] = array('id' => $answerId, 'answer' => $answer);
                        $j++;
                        $letter++;
                    }
                }

                $i = 1;

                $select_items[0]['id'] = 0;
                $select_items[0]['letter'] = '--';
                $select_items[0]['answer'] = '';

                foreach ($answer_matching as $id => $value) {
                    $select_items[$i]['id'] = $value['id'];
                    $select_items[$i]['letter'] = $capital_letter[$id];
                    $select_items[$i]['answer'] = $value['answer'];
                    $i++;
                }
                $num_suggestions = ($nbrAnswers - $j) + 1;
            } elseif ($answerType == FREE_ANSWER) {
                $content = isset($user_choice[0]) && !empty($user_choice[0]['answer']) ? $user_choice[0]['answer'] : null;
                $toolBar = 'TestFreeAnswer';
                if ($modelType == EXERCISE_MODEL_TYPE_COMMITTEE) {
                    $toolBar = 'TestFreeAnswerStrict';
                }
                $form->addElement('html_editor', "choice[".$questionId."]", null, array('id' => "choice[".$questionId."]"), array('ToolbarSet' => $toolBar));
                $form->setDefaults(array("choice[".$questionId."]" => $content));
                $s .= $form->return_form();
            } elseif ($answerType == ORAL_EXPRESSION) {
                // Add nanogong
                if (api_get_setting('enable_record_audio') === 'true') {

                    //@todo pass this as a parameter
                    global $exercise_stat_info, $exerciseId;

                    if (!empty($exercise_stat_info)) {
                        $objQuestionTmp->initFile(
                            api_get_session_id(),
                            api_get_user_id(),
                            $exercise_stat_info['exe_exo_id'],
                            $exercise_stat_info['exe_id']
                        );
                    } else {
                        $objQuestionTmp->initFile(
                            api_get_session_id(),
                            api_get_user_id(),
                            $exerciseId,
                            'temp_exe'
                        );
                    }

                    $s .= $objQuestionTmp->returnRecorder();
                }

                $form->addElement(
                    'html_editor',
                    "choice[".$questionId."]",
                    null,
                    array('id' => "choice[".$questionId."]"),
                    array('ToolbarSet' => 'TestFreeAnswer')
                );
                //$form->setDefaults(array("choice[".$questionId."]" => $content));
                $s .= $form->return_form();
            }

            // Now navigate through the possible answers, using the max number of
            // answers for the question as a limiter
            $lines_count = 1; // a counter for matching-type answers

            if ($answerType == MULTIPLE_ANSWER_TRUE_FALSE || $answerType == MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE) {
                $header = Display::tag('th', get_lang('Options'));
                foreach ($objQuestionTmp->options as $item) {
                    $header .= Display::tag('th', $item);
                }
                if ($show_comment) {
                    $header .= Display::tag('th', get_lang('Feedback'));
                }
                $s .= '<table class="data_table">';
                $s .= Display::tag('tr', $header, array('style' => 'text-align:left;'));
            }

            if ($show_comment) {
                if (in_array($answerType, array(MULTIPLE_ANSWER, MULTIPLE_ANSWER_COMBINATION, UNIQUE_ANSWER, UNIQUE_ANSWER_NO_OPTION, GLOBAL_MULTIPLE_ANSWER))) {
                    $header = Display::tag('th', get_lang('Options'));
                    if ($exercise_feedback == EXERCISE_FEEDBACK_TYPE_END) {
                        $header .= Display::tag('th', get_lang('Feedback'));
                    }
                    $s .= '<table class="data_table">';
                    $s.= Display::tag('tr', $header, array('style' => 'text-align:left;'));
                }
            }

            $matching_correct_answer = 0;
            $user_choice_array = array();
            if (!empty($user_choice)) {
                foreach ($user_choice as $item) {
                    $user_choice_array[] = $item['answer'];
                }
            }

            foreach ($objAnswerTmp->answer as $answerId => $answer_item) {
                $answer = $objAnswerTmp->selectAnswer($answerId);
                $answerCorrect = $objAnswerTmp->isCorrect($answerId);
                $comment = $objAnswerTmp->selectComment($answerId);

                //$numAnswer       = $objAnswerTmp->selectAutoId($answerId);
                $numAnswer = $answerId;

                $attributes = array();
                // Unique answer
                if (in_array($answerType, array(UNIQUE_ANSWER, UNIQUE_ANSWER_IMAGE, UNIQUE_ANSWER_NO_OPTION))) {

                    $input_id = 'choice-'.$questionId.'-'.$answerId;
                    if (isset($user_choice[0]['answer']) && $user_choice[0]['answer'] == $numAnswer) {
                        $attributes = array('id' => $input_id, 'checked' => 1, 'selected' => 1);
                    } else {
                        $attributes = array('id' => $input_id);
                    }

                    if ($debug_mark_answer) {
                        if ($answerCorrect) {
                            $attributes['checked'] = 1;
                            $attributes['selected'] = 1;
                        }
                    }

                    $answer = Security::remove_XSS($answer);
                    $s .= Display::input('hidden', 'choice2['.$questionId.']', '0');

                    $answer_input = null;
                    if ($answerType == UNIQUE_ANSWER_IMAGE) {
                        $attributes['style'] = 'display:none';
                        $answer_input .= '<div id="answer'.$questionId.$numAnswer.'" style="float:left" class="highlight_image_default highlight_image">';
                    }

                    $answer_input .= '<label class="radio">';
                    $answer_input .= Display::input('radio', 'choice['.$questionId.']', $numAnswer, $attributes);
                    $answer_input .= $answer;
                    $answer_input .= '</label>';

                    if ($answerType == UNIQUE_ANSWER_IMAGE) {
                        $answer_input .= "</div>";
                    }

                    if ($show_comment) {
                        $s .= '<tr><td>';
                        $s .= $answer_input;
                        $s .= '</td>';
                        $s .= '<td>';
                        $s .= $comment;
                        $s .= '</td>';
                        $s .= '</tr>';
                    } else {
                        $s .= $answer_input;
                    }

                } elseif (in_array($answerType, array(MULTIPLE_ANSWER, MULTIPLE_ANSWER_TRUE_FALSE, GLOBAL_MULTIPLE_ANSWER))) {
                    $input_id = 'choice-'.$questionId.'-'.$answerId;
                    $answer = Security::remove_XSS($answer);

                    if (in_array($numAnswer, $user_choice_array)) {
                        $attributes = array('id' => $input_id, 'checked' => 1, 'selected' => 1);
                    } else {
                        $attributes = array('id' => $input_id);
                    }

                    if ($debug_mark_answer) {
                        if ($answerCorrect) {
                            $attributes['checked'] = 1;
                            $attributes['selected'] = 1;
                        }
                    }

                    if ($answerType == MULTIPLE_ANSWER || $answerType == GLOBAL_MULTIPLE_ANSWER) {
                        $s .= '<input type="hidden" name="choice2['.$questionId.']" value="0" />';

                        $answer_input = '<label class="checkbox">';
                        $answer_input .= Display::input('checkbox', 'choice['.$questionId.']['.$numAnswer.']', $numAnswer, $attributes);
                        $answer_input .= $answer;
                        $answer_input .= '</label>';

                        if ($show_comment) {
                            $s .= '<tr><td>';
                            $s .= $answer_input;
                            $s .= '</td>';
                            $s .= '<td>';
                            $s .= $comment;
                            $s .= '</td>';
                            $s .='</tr>';
                        } else {
                            $s .= $answer_input;
                        }
                    } elseif ($answerType == MULTIPLE_ANSWER_TRUE_FALSE) {

                        $my_choice = array();
                        if (!empty($user_choice_array)) {
                            foreach ($user_choice_array as $item) {
                                $item = explode(':', $item);
                                $my_choice[$item[0]] = $item[1];
                            }
                        }

                        $s .='<tr>';
                        $s .= Display::tag('td', $answer);

                        if (!empty($quiz_question_options)) {
                            foreach ($quiz_question_options as $id => $item) {
                                $id = $item['iid'];
                                if (isset($my_choice[$numAnswer]) && $id == $my_choice[$numAnswer]) {
                                    $attributes = array('checked' => 1, 'selected' => 1);
                                } else {
                                    $attributes = array();
                                }

                                if ($debug_mark_answer) {
                                    if ($id == $answerCorrect) {
                                        $attributes['checked'] = 1;
                                        $attributes['selected'] = 1;
                                    }
                                }
                                $s .= Display::tag('td', Display::input('radio', 'choice['.$questionId.']['.$numAnswer.']', $id, $attributes), array('style' => ''));
                            }
                        }

                        if ($show_comment) {
                            $s .= '<td>';
                            $s .= $comment;
                            $s .= '</td>';
                        }
                        $s.='</tr>';
                    }

                } elseif ($answerType == MULTIPLE_ANSWER_COMBINATION) {

                    // multiple answers
                    $input_id = 'choice-'.$questionId.'-'.$answerId;

                    if (in_array($numAnswer, $user_choice_array)) {
                        $attributes = array('id' => $input_id, 'checked' => 1, 'selected' => 1);
                    } else {
                        $attributes = array('id' => $input_id);
                    }

                    if ($debug_mark_answer) {
                        if ($answerCorrect) {
                            $attributes['checked'] = 1;
                            $attributes['selected'] = 1;
                        }
                    }

                    $answer = Security::remove_XSS($answer);
                    $answer_input = '<input type="hidden" name="choice2['.$questionId.']" value="0" />';
                    $answer_input .= '<label class="checkbox">';
                    $answer_input .= Display::input('checkbox', 'choice['.$questionId.']['.$numAnswer.']', 1, $attributes);
                    $answer_input .= $answer;
                    $answer_input .= '</label>';

                    if ($show_comment) {
                        $s.= '<tr>';
                        $s .= '<td>';
                        $s.= $answer_input;
                        $s .= '</td>';
                        $s .= '<td>';
                        $s .= $comment;
                        $s .= '</td>';
                        $s.= '</tr>';
                    } else {
                        $s.= $answer_input;
                    }
                } elseif ($answerType == MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE) {
                    $s .= '<input type="hidden" name="choice2['.$questionId.']" value="0" />';

                    $my_choice = array();
                    if (!empty($user_choice_array)) {
                        foreach ($user_choice_array as $item) {
                            $item = explode(':', $item);
                            $my_choice[$item[0]] = $item[1];
                        }
                    }
                    $answer = Security::remove_XSS($answer);
                    $s .='<tr>';
                    $s .= Display::tag('td', $answer);

                    foreach ($objQuestionTmp->options as $key => $item) {
                        if (isset($my_choice[$numAnswer]) && $key == $my_choice[$numAnswer]) {
                            $attributes = array('checked' => 1, 'selected' => 1);
                        } else {
                            $attributes = array();
                        }

                        if ($debug_mark_answer) {
                            if ($key == $answerCorrect) {
                                $attributes['checked'] = 1;
                                $attributes['selected'] = 1;
                            }
                        }
                        $s .= Display::tag('td', Display::input('radio', 'choice['.$questionId.']['.$numAnswer.']', $key, $attributes));
                    }

                    if ($show_comment) {
                        $s .= '<td>';
                        $s .= $comment;
                        $s .= '</td>';
                    }
                    $s.='</tr>';
                } elseif ($answerType == FILL_IN_BLANKS) {
                    list($answer) = explode('::', $answer);

                    //Correct answer
                    api_preg_match_all('/\[[^]]+\]/', $answer, $correct_answer_list);

                    //Student's answezr
                    if (isset($user_choice[0]['answer'])) {
                        api_preg_match_all('/\[[^]]+\]/', $user_choice[0]['answer'], $student_answer_list);
                        $student_answer_list = $student_answer_list[0];
                    }

                    //If debug
                    if ($debug_mark_answer) {
                        $student_answer_list = $correct_answer_list[0];
                    }

                    if (!empty($correct_answer_list) && !empty($student_answer_list)) {
                        $correct_answer_list = $correct_answer_list[0];
                        $i = 0;
                        foreach ($correct_answer_list as $correct_item) {
                            $value = null;
                            if (isset($student_answer_list[$i]) && !empty($student_answer_list[$i])) {

                                //Cleaning student answer list
                                $value = strip_tags($student_answer_list[$i]);
                                $value = api_substr($value, 1, api_strlen($value) - 2);
                                $value = explode('/', $value);

                                if (!empty($value[0])) {
                                    $value = str_replace('&nbsp;', '', trim($value[0]));
                                }
                                $correct_item = preg_quote($correct_item);
                                $correct_item = api_preg_replace('|/|', '\/', $correct_item);   // to prevent error if there is a / in the text to find
                                $answer = api_preg_replace('/'.$correct_item.'/', Display::input('text', "choice[$questionId][]", $value), $answer, 1);
                            }
                            $i++;
                        }
                    } else {
                        $answer = api_preg_replace('/\[[^]]+\]/', Display::input('text', "choice[$questionId][]", '', $attributes), $answer);
                    }
                    $s .= $answer;
                } elseif ($answerType == MATCHING) {
                    // matching type, showing suggestions and answers
                    // TODO: replace $answerId by $numAnswer

                    if ($lines_count == 1) {
                        $s .= $objAnswerTmp->getJs();
                    }
                    if ($answerCorrect != 0) {
                        // only show elements to be answered (not the contents of
                        // the select boxes, who are correct = 0)
                        $s .= '<tr><td width="45%">';
                        $parsed_answer = $answer;
                        $windowId = $questionId.'_'.$lines_count;
                        //left part questions
                        $s .= ' <div id="window_'.$windowId.'" class="window window_left_question window'.$questionId.'_question">
                                    <b>'.$lines_count.'</b>.&nbsp'.$parsed_answer.'
                                </div>
                                </td>';

                        // middle part (matches selects)

                        $s .= '<td width="10%" align="center">&nbsp;&nbsp;';
                        $s .= '<div style="display:block">';

                        $s .= '<select id="window_'.$windowId.'_select" name="choice['.$questionId.']['.$numAnswer.']">';
                        $selectedValue = 0;
                        // fills the list-box
                        $item = 0;
                        foreach ($select_items as $val) {
                            // set $debug_mark_answer to true at public static function start to
                            // show the correct answer with a suffix '-x'
                            $selected = '';
                            if ($debug_mark_answer) {
                                if ($val['id'] == $answerCorrect) {
                                    $selected = 'selected="selected"';
                                    $selectedValue = $val['id'];
                                }
                            }
                            if (isset($user_choice[$matching_correct_answer]) && $val['id'] == $user_choice[$matching_correct_answer]['answer']) {
                                $selected = 'selected="selected"';
                                $selectedValue = $val['id'];
                            }
                            //$s .= '<option value="'.$val['id'].'" '.$selected.'>'.$val['letter'].'</option>';
                            $s .= '<option value="'.$item.'" '.$selected.'>'.$val['letter'].'</option>';
                            $item++;
                        }

                        if (!empty($answerCorrect) && !empty($selectedValue)) {
                            $s.= '<script>
                                jsPlumb.ready(function() {
                                    jsPlumb.connect({
                                        source: "window_'.$windowId.'",
                                        target: "window_'.$questionId.'_'.$selectedValue.'_answer",
                                        endpoint:["Blank", { radius:15 }],
                                        anchor:["RightMiddle","LeftMiddle"],
                                        paintStyle:{ strokeStyle:"#8a8888" , lineWidth:8 },
                                        connector: [connectorType, { curviness: curvinessValue } ],
                                    })
                                });
                                </script>';
                        }
                        $s .= '</select></div></td>';

                        $s.='<td width="45%" valign="top" >';

                        if (isset($select_items[$lines_count])) {
                            $s.= '<div id="window_'.$windowId.'_answer" class="window window_right_question">
                                    <b>'.$select_items[$lines_count]['letter'].'.</b> '.$select_items[$lines_count]['answer'].'
                                  </div>';
                        } else {
                            $s.='&nbsp;';
                        }

                        $s .= '</td>';
                        $s .= '</tr>';
                        $lines_count++;
                        //if the left side of the "matching" has been completely
                        // shown but the right side still has values to show...
                        if (($lines_count - 1) == $num_suggestions) {
                            // if it remains answers to shown at the right side
                            while (isset($select_items[$lines_count])) {
                                $s .= '<tr>
                                      <td colspan="2"></td>
                                      <td valign="top">';
                                $s.='<b>'.$select_items[$lines_count]['letter'].'.</b>';
                                $s .= $select_items[$lines_count]['answer'];
                                $s.="</td>
                                </tr>";
                                $lines_count++;
                            } // end while()
                        }  // end if()
                        $matching_correct_answer++;
                    }
                } elseif ($answerType ==  DRAGGABLE) {
                    // matching type, showing suggestions and answers
                    // TODO: replace $answerId by $numAnswer

                    if ($answerCorrect != 0) {
                        // only show elements to be answered (not the contents of
                        // the select boxes, who are correct = 0)
                        $s .= '<td>';
                        $parsed_answer = $answer;
                        $windowId = $questionId.'_'.$numAnswer; //67_293 - 67_294

                        //left part questions
                        $s .= '<li class="ui-state-default" id="'.$windowId.'">';
                        $s .= ' <div id="window_'.$windowId.'" class="window'.$questionId.'_question_draggable question_draggable">
                                   '.$parsed_answer.'
                                </div>';

                        $s .= '<div style="display:none">';
                        $s .= '<select id="window_'.$windowId.'_select" name="choice['.$questionId.']['.$numAnswer.']" class="select_option">';
                        $selectedValue = 0;
                        // fills the list-box
                        $item = 0;
                        foreach ($select_items as $val) {
                            // set $debug_mark_answer to true at function start to
                            // show the correct answer with a suffix '-x'
                            $selected = '';
                            if ($debug_mark_answer) {
                                if ($val['id'] == $answerCorrect) {
                                    $selected = 'selected="selected"';
                                    $selectedValue = $val['id'];
                                }
                            }
                            if (isset($user_choice[$matching_correct_answer]) && $val['id'] == $user_choice[$matching_correct_answer]['answer']) {
                                $selected = 'selected="selected"';
                                $selectedValue = $val['id'];
                            }
                            $s .= '<option value="'.$item.'" '.$selected.'>'.$val['letter'].'</option>';
                            $item++;
                        }
                        $s .= '</select>';

                        if (!empty($answerCorrect) && !empty($selectedValue)) {
                            $s.= '<script>
                                $(function() {
                                    deleteItem($("#'.$questionId.'_'.$selectedValue.'"), $("#drop_'.$windowId.'"));
                                });
                                </script>';
                        }

                        if (isset($select_items[$lines_count])) {
                            $s.= '<div id="window_'.$windowId.'_answer" class="">
                                    <b>'.$select_items[$lines_count]['letter'].'.</b> '.$select_items[$lines_count]['answer'].'
                                  </div>';
                        } else {
                            $s.='&nbsp;';
                        }
                        $lines_count++;
                        //if the left side of the "matching" has been completely
                        // shown but the right side still has values to show...

                        if (($lines_count - 1) == $num_suggestions) {
                            // if it remains answers to shown at the right side
                            while (isset($select_items[$lines_count])) {
                                $s.='<b>'.$select_items[$lines_count]['letter'].'.</b>';
                                $s .= $select_items[$lines_count]['answer'];
                                $lines_count++;
                            }
                        }
                        $s .= '</div>';
                        $matching_correct_answer++;
                        $s .= '</li>';
                    }
                }
            } // end for()

            if ($show_comment) {
                $s .= '</table>';
            } else {
                if ($answerType == MATCHING || $answerType == UNIQUE_ANSWER_NO_OPTION || $answerType == MULTIPLE_ANSWER_TRUE_FALSE ||
                    $answerType == MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE) {
                    $s .= '</table>';
                }
            }

            if ($answerType == DRAGGABLE) {
                $s .= '</ul><div class="clear"></div>';

                $counterAnswer = 1;
                foreach ($objAnswerTmp->answer as $answerId => $answer_item) {
                    $answerCorrect = $objAnswerTmp->isCorrect($answerId);
                    $windowId = $questionId.'_'.$counterAnswer;
                    if ($answerCorrect == 0) {
                        $s .= '<div id="drop_'.$windowId.'" class="droppable ui-state-default">'.$counterAnswer.'</div>';
                        $counterAnswer++;
                    }
                }
            }

            if ($answerType == MATCHING) {
                $s .= '</div>';
            }

            $s .= '</div>';

            // destruction of the Answer object
            unset($objAnswerTmp);

            // destruction of the Question object
            unset($objQuestionTmp);

            $html .= $s;
            return $html;
        } elseif ($answerType == HOT_SPOT || $answerType == HOT_SPOT_DELINEATION) {
            // Question is a HOT_SPOT
            //checking document/images visibility
            if (api_is_platform_admin() || api_is_course_admin()) {
                $course = api_get_course_info();
                $doc_id = DocumentManager::get_document_id($course, '/images/'.$pictureName);
                if (is_numeric($doc_id)) {
                    $images_folder_visibility = api_get_item_visibility($course, 'document', $doc_id, api_get_session_id());
                    if (!$images_folder_visibility) {
                        //This message is shown only to the course/platform admin if the image is set to visibility = false
                        Display::display_warning_message(get_lang('ChangeTheVisibilityOfTheCurrentImage'));
                    }
                }
            }
            $questionName = $objQuestionTmp->selectTitle();
            $questionDescription = $objQuestionTmp->selectDescription();

            if ($freeze) {
                $s .= Display::img($objQuestionTmp->selectPicturePath());
                $html .= $s;
                return $html;
            }

            // Get the answers, make a list
            $objAnswerTmp = new Answer($questionId);

            // get answers of hotpost
            $answers_hotspot = array();
            foreach ($objAnswerTmp->answer as $answerId => $answer_item) {
                //$answers = $objAnswerTmp->selectAnswerByAutoId($objAnswerTmp->selectAutoId($answerId));
                $answers_hotspot[$answerId] = $objAnswerTmp->selectAnswer($answerId);
            }

            // display answers of hotpost order by id
            $answer_list = '<div style="padding: 10px; margin-left: 0px; border: 1px solid #A4A4A4; height: 408px; width: 200px;"><b>'.get_lang('HotspotZones').'</b><dl>';
            if (!empty($answers_hotspot)) {
                ksort($answers_hotspot);
                foreach ($answers_hotspot as $key => $value) {
                    $answer_list .= '<dt>'.$key.'.- '.$value.'</dt><br />';
                }
            }
            $answer_list .= '</dl></div>';

            if ($answerType == HOT_SPOT_DELINEATION) {
                $answer_list = '';
                $swf_file = 'hotspot_delineation_user';
                $swf_height = 405;
            } else {
                $swf_file = 'hotspot_user';
                $swf_height = 436;
            }

            if (!$only_questions) {
                if ($show_title) {
                    $html .=  TestCategory::getCategoryNamesForQuestion($objQuestionTmp->id);
                    $html .=  '<div class="question_title">'.$current_item.'. '.$questionName.'</div>';
                    $html .=  $questionDescription;
                } else {
                    $html .= '<div class="media">';
                    $html .= '<div class="pull-left">';
                    $html .= '<div class="media-object">';
                    $html .= Display::div($current_item.'. ', array('class' => 'question_no_title'));
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<div class="media-body">';
                    if (!empty($questionDescription)) {
                        $html .= Display::div($questionDescription, array('class' => 'question_description'));
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                }
                //@todo I need to the get the feedback type
                $html .=  '<input type="hidden" name="hidden_hotspot_id" value="'.$questionId.'" />';
                $html .=  '<table class="exercise_questions">
                           <tr>
                            <td valign="top" colspan="2">';
                $html .=  '</td></tr>';
            }

            $canClick = isset($_GET['editQuestion']) ? '0' : (isset($_GET['modifyAnswers']) ? '0' : '1');

            $s .= ' <script type="text/javascript" src="../plugin/hotspot/JavaScriptFlashGateway.js"></script>
                    <script src="../plugin/hotspot/hotspot.js" type="text/javascript" ></script>
                    <script type="text/javascript">
                    <!--
                    // Globals
                    // Major version of Flash required
                    var requiredMajorVersion = 7;
                    // Minor version of Flash required
                    var requiredMinorVersion = 0;
                    // Minor version of Flash required
                    var requiredRevision = 0;
                    // the version of javascript supported
                    var jsVersion = 1.0;
                    // -->
                    </script>
                    <script language="VBScript" type="text/vbscript">
                    <!-- // Visual basic helper required to detect Flash Player ActiveX control version information
                    Function VBGetSwfVer(i)
                      on error resume next
                      Dim swControl, swVersion
                      swVersion = 0

                      set swControl = CreateObject("ShockwaveFlash.ShockwaveFlash." + CStr(i))
                      if (IsObject(swControl)) then
                        swVersion = swControl.GetVariable("$version")
                      end if
                      VBGetSwfVer = swVersion
                    End Function
                    // -->
                    </script>

                    <script language="JavaScript1.1" type="text/javascript">
                    <!-- // Detect Client Browser type
                    var isIE  = (navigator.appVersion.indexOf("MSIE") != -1) ? true : false;
                    var isWin = (navigator.appVersion.toLowerCase().indexOf("win") != -1) ? true : false;
                    var isOpera = (navigator.userAgent.indexOf("Opera") != -1) ? true : false;
                    jsVersion = 1.1;
                    // JavaScript helper required to detect Flash Player PlugIn version information
                    function JSGetSwfVer(i) {
                        // NS/Opera version >= 3 check for Flash plugin in plugin array
                        if (navigator.plugins != null && navigator.plugins.length > 0) {
                            if (navigator.plugins["Shockwave Flash 2.0"] || navigator.plugins["Shockwave Flash"]) {
                                var swVer2 = navigator.plugins["Shockwave Flash 2.0"] ? " 2.0" : "";
                                var flashDescription = navigator.plugins["Shockwave Flash" + swVer2].description;
                                descArray = flashDescription.split(" ");
                                tempArrayMajor = descArray[2].split(".");
                                versionMajor = tempArrayMajor[0];
                                versionMinor = tempArrayMajor[1];
                                if ( descArray[3] != "" ) {
                                    tempArrayMinor = descArray[3].split("r");
                                } else {
                                    tempArrayMinor = descArray[4].split("r");
                                }
                                versionRevision = tempArrayMinor[1] > 0 ? tempArrayMinor[1] : 0;
                                flashVer = versionMajor + "." + versionMinor + "." + versionRevision;
                            } else {
                                flashVer = -1;
                            }
                        }
                        // MSN/WebTV 2.6 supports Flash 4
                        else if (navigator.userAgent.toLowerCase().indexOf("webtv/2.6") != -1) flashVer = 4;
                        // WebTV 2.5 supports Flash 3
                        else if (navigator.userAgent.toLowerCase().indexOf("webtv/2.5") != -1) flashVer = 3;
                        // older WebTV supports Flash 2
                        else if (navigator.userAgent.toLowerCase().indexOf("webtv") != -1) flashVer = 2;
                        // Can\'t detect in all other cases
                        else {
                            flashVer = -1;
                        }
                        return flashVer;
                    }
                    // When called with reqMajorVer, reqMinorVer, reqRevision returns true if that version or greater is available

                    function DetectFlashVer(reqMajorVer, reqMinorVer, reqRevision) {
                        reqVer = parseFloat(reqMajorVer + "." + reqRevision);
                        // loop backwards through the versions until we find the newest version
                        for (i=25;i>0;i--) {
                            if (isIE && isWin && !isOpera) {
                                versionStr = VBGetSwfVer(i);
                            } else {
                                versionStr = JSGetSwfVer(i);
                            }
                            if (versionStr == -1 ) {
                                return false;
                            } else if (versionStr != 0) {
                                if(isIE && isWin && !isOpera) {
                                    tempArray         = versionStr.split(" ");
                                    tempString        = tempArray[1];
                                    versionArray      = tempString .split(",");
                                } else {
                                    versionArray      = versionStr.split(".");
                                }
                                versionMajor      = versionArray[0];
                                versionMinor      = versionArray[1];
                                versionRevision   = versionArray[2];

                                versionString     = versionMajor + "." + versionRevision;   // 7.0r24 == 7.24
                                versionNum        = parseFloat(versionString);
                                // is the major.revision >= requested major.revision AND the minor version >= requested minor
                                if ( (versionMajor > reqMajorVer) && (versionNum >= reqVer) ) {
                                    return true;
                                } else {
                                    return ((versionNum >= reqVer && versionMinor >= reqMinorVer) ? true : false );
                                }
                            }
                        }
                    }
                    // -->
                    </script>';
            $s .= '<tr><td valign="top" colspan="2" width="520"><table><tr><td width="520">
                    <script>
                        // Version check based upon the values entered above in "Globals"
                        var hasReqestedVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);

                        // Check to see if the version meets the requirements for playback
                        if (hasReqestedVersion) {  // if we\'ve detected an acceptable version
                            var oeTags = \'<object type="application/x-shockwave-flash" data="../plugin/hotspot/'.$swf_file.'.swf?modifyAnswers='.$questionId.'&amp;canClick:'.$canClick.'" width="600" height="'.$swf_height.'">\'
                                        + \'<param name="wmode" value="transparent">\'
                                        + \'<param name="movie" value="../plugin/hotspot/'.$swf_file.'.swf?modifyAnswers='.$questionId.'&amp;canClick:'.$canClick.'" />\'
                                        + \'<\/object>\';
                            document.write(oeTags);   // embed the Flash Content SWF when all tests are passed
                        } else {  // flash is too old or we can\'t detect the plugin
                            var alternateContent = "Error<br \/>"
                                + "Hotspots requires Macromedia Flash 7.<br \/>"
                                + "<a href=\"http://www.macromedia.com/go/getflash/\">Get Flash<\/a>";
                            document.write(alternateContent);  // insert non-flash content
                        }
                    </script>
                    </td>
                    <td valign="top" align="left">'.$answer_list.'</td></tr>
                    </table>
            </td></tr>';
            $html .= $s;
            $html .= '</table>';
            return $html;
        }
        return $nbrAnswers;
    }

    /**
     * @param int $exeId
     * @return array
     */
    public function returnQuestionListByAttempt($exeId)
    {
        return $this->displayQuestionListByAttempt($exeId, false, true);
    }

    /**
     * Display the exercise results
     * @param int  $exe_id
     * @param bool $saveUserResult save users results (true) or just show the results (false)
     * @param bool $returnExerciseResult return array with exercise result info
     * @return mixed
     */
    public function displayQuestionListByAttempt($exe_id, $saveUserResult = false, $returnExerciseResult = false)
    {
        global $origin, $debug;

        //Getting attempt info
        $exercise_stat_info = $this->getStatTrackExerciseInfoByExeId($exe_id);

        //Getting question list
        $question_list = array();
        if (!empty($exercise_stat_info['data_tracking'])) {
            $question_list = explode(',', $exercise_stat_info['data_tracking']);
        } else {
            //Try getting the question list only if save result is off
            if ($saveUserResult == false) {
                $question_list = $this->selectQuestionList();
            }
            error_log("Data tracking is empty! exe_id: $exe_id");
        }

        $counter = 1;
        $total_score = 0;
        $total_weight = 0;

        $exercise_content = null;

        //Hide results
        $show_results = false;
        $show_only_score = false;

        if ($this->results_disabled == RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS) {
            $show_results = true;
        }

        $showScoreOptions = [
            RESULT_DISABLE_SHOW_SCORE_ONLY,
            RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES,
            RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT
        ];

        if (in_array($this->results_disabled, $showScoreOptions)) {
            $show_only_score = true;
        }

        if ($show_results || $show_only_score) {
            $user_info = api_get_user_info($exercise_stat_info['exe_user_id']);
            // Shows exercise header.
            echo $this->show_exercise_result_header(
                $user_info['complete_name'],
                api_convert_and_format_date($exercise_stat_info['start_date'], DATE_TIME_FORMAT_LONG),
                $exercise_stat_info['duration']
            );
        }

        // Display text when test is finished #4074 and for LP #4227
        $end_of_message = $this->selectTextWhenFinished();
        if (!empty($end_of_message)) {
            Display::display_normal_message($end_of_message, false);
            echo "<div class='clear'>&nbsp;</div>";
        }

        $question_list_answers = array();
        $media_list = array();
        $category_list = array();
        $tempParentId = null;
        $mediaCounter = 0;

        $exerciseResultInfo = array();

        // Loop over all question to show results for each of them, one by one
        if (!empty($question_list)) {
            if ($debug) {
                error_log('Looping question_list '.print_r($question_list, 1));
            }

            foreach ($question_list as $questionId) {

                // Creates a temporary Question object
                $objQuestionTmp = Question::read($questionId);

                // This variable commes from exercise_submit_modal.php
                ob_start();
                $hotspot_delineation_result = null;

                // We're inside *one* question. Go through each possible answer for this question
                $result = $this->manageAnswers(
                    $exercise_stat_info['exe_id'],
                    $questionId,
                    null,
                    'exercise_result',
                    array(),
                    $saveUserResult,
                    true,
                    $show_results,
                    $hotspot_delineation_result
                );

                if (empty($result)) {
                    continue;
                }

                $total_score += $result['score'];
                $total_weight += $result['weight'];

                $question_list_answers[] = array(
                    'question' => $result['open_question'],
                    'answer' => $result['open_answer'],
                    'answer_type' => $result['answer_type']
                );

                $my_total_score = $result['score'];
                $my_total_weight = $result['weight'];

                // Category report
                $category_was_added_for_this_test = false;
                $categoryExerciseList = $this->getListOfCategoriesWithQuestionForTest();

                $category_list = array();
                if (isset($categoryExerciseList) && !empty($categoryExerciseList)) {
                    foreach ($categoryExerciseList as $category_id => $categoryInfo) {
                        if (!isset($category_list[$category_id])) {
                            $category_list[$category_id] = array();
                            $category_list[$category_id]['score'] = 0;
                            $category_list[$category_id]['total'] = 0;
                        }
                        $category_list[$category_id]['score'] += $my_total_score;
                        $category_list[$category_id]['total'] += $my_total_weight;
                        $category_was_added_for_this_test = true;
                    }
                }

                // No category for this question!
                if ($category_was_added_for_this_test == false) {
                    if (!isset($category_list['none'])) {
                        $category_list['none'] = array();
                        $category_list['none']['score'] = 0;
                        $category_list['none']['total'] = 0;
                    }

                    $category_list['none']['score'] += $my_total_score;
                    $category_list['none']['total'] += $my_total_weight;
                }

                if ($this->selectPropagateNeg() == 0 && $my_total_score < 0) {
                    $my_total_score = 0;
                }

                $comnt = null;
                if ($show_results) {
                    $comnt = get_comments($exe_id, $questionId);
                    if (!empty($comnt)) {
                        echo '<b>'.get_lang('Feedback').'</b>';
                        echo '<div id="question_feedback">'.$comnt.'</div>';
                    }
                }

                $score = array();
                $score['result'] = get_lang('Score')." : ".ExerciseLib::show_score($my_total_score, $my_total_weight, false, true);
                $score['pass'] = $my_total_score >= $my_total_weight ? true : false;
                $score['score'] = $my_total_score;
                $score['weight'] = $my_total_weight;
                $score['comments'] = $comnt;

                $exerciseResultInfo[$questionId]['score'] = $score;
                $exerciseResultInfo[$questionId]['details'] = $result;

                // If no results we hide the results
                if ($show_results == false) {
                    $score = array();
                }
                $contents = ob_get_clean();

                $question_content = '<div class="question_row">';

                if ($show_results) {

                    $show_media = false;
                    $counterToShow = $counter;
                    if ($objQuestionTmp->parent_id != 0) {

                        if (!in_array($objQuestionTmp->parent_id, $media_list)) {
                            $media_list[] = $objQuestionTmp->parent_id;
                            $show_media = true;
                        }
                        if ($tempParentId == $objQuestionTmp->parent_id) {
                            $mediaCounter++;
                        } else {
                            $mediaCounter = 0;
                        }
                        $counterToShow = chr(97 + $mediaCounter);
                        $tempParentId = $objQuestionTmp->parent_id;
                    }

                    // Shows question title an description.
                    $question_content .= $objQuestionTmp->return_header(null, $counterToShow, $score, $show_media, $this->getHideQuestionTitle());

                    // display question category, if any
                    $question_content .= TestCategory::getCategoryNamesForQuestion($questionId, null, true, $this->categoryMinusOne);
                }
                $counter++;

                $question_content .= $contents;
                $question_content .= '</div>';

                $exercise_content .= $question_content;
            } // end foreach() block that loops over all questions
        }

        $total_score_text = null;

        if ($returnExerciseResult) {
            return $exerciseResultInfo;
        }

        if ($origin != 'learnpath') {
            if ($show_results || $show_only_score) {
                $total_score_text .= $this->get_question_ribbon($total_score, $total_weight, true);
            }
        }

        if (!empty($category_list) && ($show_results || $show_only_score)) {
            //Adding total
            $category_list['total'] = array('score' => $total_score, 'total' => $total_weight);
            echo TestCategory::get_stats_table_by_attempt($this->id, $category_list, $this->categoryMinusOne);
        }

        echo $total_score_text;
        echo $exercise_content;

        if (!$show_only_score) {
            echo $total_score_text;
        }

        if ($saveUserResult) {

            // Tracking of results
            $learnpath_id = $exercise_stat_info['orig_lp_id'];
            $learnpath_item_id = $exercise_stat_info['orig_lp_item_id'];
            $learnpath_item_view_id = $exercise_stat_info['orig_lp_item_view_id'];

            if (api_is_allowed_to_session_edit()) {
                update_event_exercise(
                    $exercise_stat_info['exe_id'],
                    $this->selectId(),
                    $total_score,
                    $total_weight,
                    api_get_session_id(),
                    $learnpath_id,
                    $learnpath_item_id,
                    $learnpath_item_view_id,
                    $exercise_stat_info['exe_duration'],
                    '',
                    array()
                );
            }

            // Send notification.
            if (!api_is_allowed_to_edit(null, true)) {
                $isSuccess = ExerciseLib::is_success_exercise_result($total_score, $total_weight, $this->selectPassPercentage());
                $this->sendCustomNotification($exe_id, $exerciseResultInfo, $isSuccess);
                $this->sendNotificationForOpenQuestions($question_list_answers, $origin, $exe_id);
                $this->sendNotificationForOralQuestions($question_list_answers, $origin, $exe_id);
            }
        }
    }

    /**
     * Returns an HTML ribbon to show on top of the exercise result, with
     * colouring depending on the success or failure of the student
     * @param integer $score
     * @param integer $weight
     * @param bool $check_pass_percentage
     * @return string
     */
    public function get_question_ribbon($score, $weight, $check_pass_percentage = false)
    {
        $eventMessage = null;
        $ribbon = '<div class="question_row">';
        $ribbon .= '<div class="ribbon">';
        if ($check_pass_percentage) {
            $is_success = ExerciseLib::is_success_exercise_result($score, $weight, $this->selectPassPercentage());
            // Color the final test score if pass_percentage activated
            $ribbon_total_success_or_error = "";
            if (ExerciseLib::is_pass_pourcentage_enabled($this->selectPassPercentage())) {
                if ($is_success) {
                    $eventMessage = $this->getOnSuccessMessage();
                    $ribbon_total_success_or_error = ' ribbon-total-success';
                } else {
                    $eventMessage = $this->getOnFailedMessage();
                    $ribbon_total_success_or_error = ' ribbon-total-error';
                }
            }
            $ribbon .= '<div class="rib rib-total '.$ribbon_total_success_or_error.'">';
        } else {
            $ribbon .= '<div class="rib rib-total">';
        }
        $ribbon .= '<h3>'.get_lang('YourTotalScore').":&nbsp;";
        $ribbon .= ExerciseLib::show_score($score, $weight, false, true);
        $ribbon .= '</h3>';
        $ribbon .= '</div>';

        if ($check_pass_percentage) {
            $ribbon .= ExerciseLib::show_success_message($score, $weight, $this->selectPassPercentage());
        }
        $ribbon .= '</div>';
        $ribbon .= '</div>';

        $ribbon .= $eventMessage;

        return $ribbon;
    }

    /**
     * Returns an array of categories details for the questions of the current
     * exercise.
     * @return array
     */
    public function getQuestionWithCategories()
    {
        $categoryTable = Database::get_course_table(TABLE_QUIZ_QUESTION_CATEGORY);
        $categoryRelTable = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $sql = "SELECT DISTINCT cat.*
                FROM $TBL_EXERCICE_QUESTION e
                INNER JOIN $TBL_QUESTIONS q
                ON (e.question_id = q.id AND e.c_id = q.c_id)
                INNER JOIN $categoryRelTable catRel
                ON (catRel.question_id = e.question_id)
                INNER JOIN $categoryTable cat
                ON (cat.id = catRel.category_id)
                WHERE
                  e.c_id = {$this->course_id} AND
                  e.exercice_id	= ".intval($this->id);

        $result = Database::query($sql);
        $categoriesInExercise = array();
        if (Database::num_rows($result)) {
            $categoriesInExercise = Database::store_result($result, 'ASSOC');
        }

        return $categoriesInExercise;
    }

    /**
     * Calculate the max_score of the quiz, depending of question inside, and quiz advanced option
     */
    public function get_max_score()
    {
        $out_max_score = 0;
        // list of question's id !!! the array key start at 1 !!!
        $questionList = $this->selectQuestionList(true);

        // test is randomQuestions - see field random of test
        if ($this->random > 0 && $this->randomByCat == 0) {
            $numberRandomQuestions = $this->random;
            $questionScoreList = array();
            for ($i = 1; $i <= count($questionList); $i++) {
                $tmpobj_question = Question::read($questionList[$i]);
                $questionScoreList[] = $tmpobj_question->weighting;
            }
            rsort($questionScoreList);
            // add the first $numberRandomQuestions value of score array to get max_score
            for ($i = 0; $i < min($numberRandomQuestions, count($questionScoreList)); $i++) {
                $out_max_score += $questionScoreList[$i];
            }
        } else if ($this->random > 0 && $this->randomByCat > 0) {
            // test is random by category
            // get the $numberRandomQuestions best score question of each category

            $numberRandomQuestions = $this->random;
            $tab_categories_scores = array();
            for ($i = 1; $i <= count($questionList); $i++) {
                $question_category_id = TestCategory::getCategoryForQuestion($questionList[$i]);
                if (!is_array($tab_categories_scores[$question_category_id])) {
                    $tab_categories_scores[$question_category_id] = array();
                }
                $tmpobj_question = Question::read($questionList[$i]);
                $tab_categories_scores[$question_category_id][] = $tmpobj_question->weighting;
            }

            // here we've got an array with first key, the category_id, second key, score of question for this cat
            while (list($key, $tab_scores) = each($tab_categories_scores)) {
                rsort($tab_scores);
                for ($i = 0; $i < min($numberRandomQuestions, count($tab_scores)); $i++) {
                    $out_max_score += $tab_scores[$i];
                }
            }
        } else {
            // standard test, just add each question score
            foreach ($questionList as $questionId) {
                $question = Question::read($questionId, $this->course_id);
                $out_max_score += $question->weighting;
            }
        }

        return $out_max_score;
    }

    /**
    * @return string
    */
    public function get_formated_title()
    {
        return api_html_entity_decode($this->selectTitle());
    }

    /**
     * @param $in_title
     * @return string
     */
    public static function get_formated_title_variable($in_title)
    {
        return api_html_entity_decode($in_title);
    }

    /**
     * @return string
     */
    public function format_title()
    {
        return api_htmlentities($this->title);
    }

    /**
     * @param $in_title
     * @return string
     */
    public static function format_title_variable($in_title)
    {
        return api_htmlentities($in_title);
    }

    /**
     * @param int $courseId
     * @param int $sessionId
     * @return array exercises
     */
    public function getExercisesByCouseSession($courseId, $sessionId)
    {
        $courseId = intval($courseId);
        $sessionId = intval($sessionId);

        $tbl_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
        $sql = "SELECT * FROM $tbl_quiz cq
                WHERE
                    cq.c_id = %s AND
                    (cq.session_id = %s OR cq.session_id = 0) AND
                    cq.active = 0
                ORDER BY cq.id";
        $sql = sprintf($sql, $courseId, $sessionId);

        $result = Database::query($sql);

        $rows = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     *
     * @param int $courseId
     * @param int $sessionId
     * @param array $quizId
     * @return array exercises
     */
    public function getExerciseAndResult($courseId, $sessionId, $quizId = array())
    {
        if (empty($quizId)) {
            return array();
        }

        $sessionId = intval($sessionId);

        $ids = is_array($quizId) ? $quizId : array($quizId);
        $ids = array_map('intval', $ids);
        $ids = implode(',', $ids);
        $track_exercises = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        if ($sessionId != 0) {
            $sql = "SELECT * FROM $track_exercises te
              INNER JOIN c_quiz cq ON cq.id = te.exe_exo_id AND te.c_id = cq.c_id
              WHERE
              te.id = %s AND
              te.session_id = %s AND
              cq.id IN (%s)
              ORDER BY cq.id";

            $sql = sprintf($sql, $courseId, $sessionId, $ids);
        } else {
            $sql = "SELECT * FROM $track_exercises te
              INNER JOIN c_quiz cq ON cq.id = te.exe_exo_id AND te.c_id = cq.c_id
              WHERE
              te.id = %s AND
              cq.id IN (%s)
              ORDER BY cq.id";
            $sql = sprintf($sql, $courseId, $ids);
        }
        $result = Database::query($sql);
        $rows = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param $exeId
     * @param $exercise_stat_info
     * @param $remindList
     * @param $currentQuestion
     * @return int|null
     */
    public static function getNextQuestionId($exeId, $exercise_stat_info, $remindList, $currentQuestion)
    {
        $result = get_exercise_results_by_attempt($exeId, 'incomplete');

        if (isset($result[$exeId])) {
            $result = $result[$exeId];
        } else {
            return null;
        }

        $data_tracking  = $exercise_stat_info['data_tracking'];
        $data_tracking  = explode(',', $data_tracking);

        // if this is the final question do nothing.
        if ($currentQuestion == count($data_tracking)) {
            return null;
        }

        $currentQuestion = $currentQuestion - 1;

        if (!empty($result['question_list'])) {
            $answeredQuestions = array();

            foreach ($result['question_list'] as $question) {
                if (!empty($question['answer'])) {
                    $answeredQuestions[] = $question['question_id'];
                }
            }

            // Checking answered questions

            $counterAnsweredQuestions = 0;
            foreach ($data_tracking as $questionId) {
                if (!in_array($questionId, $answeredQuestions)) {
                    if ($currentQuestion != $counterAnsweredQuestions) {
                        break;
                    }
                }
                $counterAnsweredQuestions++;
            }

            $counterRemindListQuestions = 0;
            // Checking questions saved in the reminder list

            if (!empty($remindList)) {
                foreach ($data_tracking as $questionId) {
                    if (in_array($questionId, $remindList)) {
                        // Skip the current question
                        if ($currentQuestion != $counterRemindListQuestions) {
                            break;
                        }
                    }
                    $counterRemindListQuestions++;
                }

                if ($counterRemindListQuestions < $currentQuestion) {
                    return null;
                }

                if (!empty($counterRemindListQuestions)) {
                    if ($counterRemindListQuestions > $counterAnsweredQuestions) {
                        return $counterAnsweredQuestions;
                    } else {
                        return $counterRemindListQuestions;
                    }
                }
            }

            return $counterAnsweredQuestions;
        }
    }

    /**
     * Gets the position of a questionId in the question list
     * @param $questionId
     * @return int
     */
    public function getPositionInCompressedQuestionList($questionId)
    {
        $questionList = $this->getQuestionListWithMediasCompressed();
        $mediaQuestions = $this->getMediaList();
        $position = 1;
        foreach ($questionList as $id) {
            if (isset($mediaQuestions[$id]) && in_array($questionId, $mediaQuestions[$id])) {
                $mediaQuestionList = $mediaQuestions[$id];
                if (in_array($questionId, $mediaQuestionList)) {
                    return $position;
                } else {
                    $position++;
                }
            } else {
                if ($id == $questionId) {
                    return $position;
                } else {
                    $position++;
                }
            }
        }
        return 1;
    }

    /**
     * Get the correct answers in all attempts
     * @param int $learnPathId
     * @param int $learnPathItemId
     * @return array
     */
    public function getCorrectAnswersInAllAttempts($learnPathId = 0, $learnPathItemId = 0)
    {
        $attempts = Event::getExerciseResultsByUser(
            api_get_user_id(),
            $this->id,
            api_get_course_int_id(),
            api_get_session_id(),
            $learnPathId,
            $learnPathItemId,
            'asc'
        );

        $corrects = [];

        foreach ($attempts as $attempt) {
            foreach ($attempt['question_list'] as $answer) {
                $objAnswer = new Answer($answer['question_id']);
                $isCorrect = $objAnswer->isCorrectByAutoId($answer['answer']);

                if ($isCorrect) {
                    $corrects[$answer['question_id']][] = $answer;
                }
            }
        }

        return $corrects;
    }
}
