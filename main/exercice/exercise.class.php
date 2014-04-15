<?php
/* For licensing terms, see /license.txt */
/**
 * Exercise class: This class allows to instantiate an object of type Exercise
 * @package chamilo.exercise
 * @author Olivier Brouckaert
 * @author Julio Montoya Cleaning exercises, adding multiple categories, media questions, commitee
 * Modified by Hubert Borderiou #294
 */
/**
 * Code
 */
use \ChamiloSession as Session;

// Page options
define('ALL_ON_ONE_PAGE', 1);
define('ONE_PER_PAGE', 2);

define('EXERCISE_MODEL_TYPE_NORMAL', 1);
define('EXERCISE_MODEL_TYPE_COMMITTEE', 2);

define('EXERCISE_FEEDBACK_TYPE_END', 0); //Feedback 		 - show score and expected answers
define('EXERCISE_FEEDBACK_TYPE_DIRECT', 1); //DirectFeedback - Do not show score nor answers
define('EXERCISE_FEEDBACK_TYPE_EXAM', 2); //NoFeedback 	 - Show score only

define('RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS', 0); //show score and expected answers
define('RESULT_DISABLE_NO_SCORE_AND_EXPECTED_ANSWERS', 1); //Do not show score nor answers
define('RESULT_DISABLE_SHOW_SCORE_ONLY', 2); //Show score only
define('RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES', 3); //Show final score only with categories

define('EXERCISE_MAX_NAME_SIZE', 80);

define('EXERCISE_CATEGORY_RANDOM_SHUFFLED', 1);
define('EXERCISE_CATEGORY_RANDOM_ORDERED', 2);
define('EXERCISE_CATEGORY_RANDOM_DISABLED', 0);

// Question selection type
define('EX_Q_SELECTION_ORDERED', 1);
define('EX_Q_SELECTION_RANDOM', 2);
define('EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED', 3);
define('EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_ORDERED', 4);
define('EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM', 5);
define('EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM', 6);
define('EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_ORDERED_NO_GROUPED', 7);
define('EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM_NO_GROUPED', 8);

define('EX_Q_SELECTION_CATEGORIES_ORDERED_BY_PARENT_QUESTIONS_ORDERED', 9);
define('EX_Q_SELECTION_CATEGORIES_ORDERED_BY_PARENT_QUESTIONS_RANDOM', 10);
$debug = false; //All exercise scripts should depend in this debug variable

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
    /* show media id */
    public $questionList; // medias as id
    /* including question list of the media */
    public $questionListUncompressed;
    public $results_disabled;
    public $expired_time;
    public $course;
    public $course_id;
    public $propagate_neg;
    public $review_answers;
    public $randomByCat;
    public $text_when_finished;
    public $display_category_name;
    public $pass_percentage;
    public $edit_exercise_in_lp = false;
    public $is_gradebook_locked = false;
    public $exercise_was_added_in_lp = false;
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
    public $notifyUserByEmail = 0;

    /**
     * Constructor of the class
     * @author - Olivier Brouckaert
     * @param int $course_id
     * @throws Exception
     */
    public function Exercise($course_id = null)
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
        $this->end_time = '0000-00-00 00:00:00';
        $this->start_time = '0000-00-00 00:00:00';
        $this->results_disabled = 1;
        $this->expired_time = '0000-00-00 00:00:00';
        $this->propagate_neg = 0;
        $this->review_answers = false;
        $this->randomByCat = 0;
        $this->text_when_finished = "";
        $this->display_category_name = 0;
        $this->pass_percentage = null;
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
        /* Make sure that we have a valid $course_info. */
        if (!isset($course_info['real_id'])) {
            throw new Exception('Could not get a valid $course_info with the provided $course_id: ' . $course_id . '.');
        }
        $this->course_id = $course_info['real_id'];
        $this->course = $course_info;
        $this->fastEdition = api_get_course_setting('allow_fast_exercise_edition') == 1 ? true : false;
        $this->emailAlert = api_get_course_setting('email_alert_manager_on_new_quiz') == 1 ? true : false;
        $this->hideQuestionTitle = 0;
    }

    /**
     * Reads exercise information from the database
     *
     * @author Olivier Brouckaert
     * @todo use Doctrine to manage read/writes
     * @param int $id - exercise ID
     * @param bool parse exercise question list
     * @return boolean - true if exercise exists, otherwise false
     */
    public function read($id, $parseQuestionList = true)
    {
        if (empty($this->course_id)) {
            return false;
        }
        global $_configuration;

        $TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST);
        $table_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $id = intval($id);
        $sql = "SELECT * FROM $TBL_EXERCICES WHERE c_id = ".$this->course_id." AND iid = ".$id;
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
            $this->randomByCat = $object->random_by_category;
            $this->text_when_finished = $object->text_when_finished;
            $this->display_category_name = $object->display_category_name;
            $this->pass_percentage = $object->pass_percentage;
            $this->is_gradebook_locked = api_resource_is_locked_by_gradebook($id, LINK_EXERCISE);
            $this->endButton = $object->end_button;
            $this->onSuccessMessage = $object->on_success_message;
            $this->onFailedMessage= $object->on_failed_message;
            $this->emailNotificationTemplate = $object->email_notification_template;
            $this->emailNotificationTemplateToUser = $object->email_notification_template_to_user;
            $this->notifyUserByEmail = $object->notify_user_by_email;
            $this->modelType = $object->model_type;
            $this->questionSelectionType = $object->question_selection_type;
            $this->hideQuestionTitle = $object->hide_question_title;
            $this->scoreTypeModel = $object->score_type_model;
            $this->globalCategoryId = $object->global_category_id;

            $this->review_answers = (isset($object->review_answers) && $object->review_answers == 1) ? true : false;
            $sql = "SELECT max_score FROM $table_lp_item
                    WHERE   c_id = {$this->course_id} AND
                            item_type = '".TOOL_QUIZ."' AND
                            path = '".$id."'";
            $result = Database::query($sql);

            if (Database::num_rows($result) > 0) {
                $this->exercise_was_added_in_lp = true;
            }

            $this->force_edit_exercise_in_lp = isset($_configuration['force_edit_exercise_in_lp']) ? $_configuration['force_edit_exercise_in_lp'] : false;

            if ($this->exercise_was_added_in_lp) {
                $this->edit_exercise_in_lp = $this->force_edit_exercise_in_lp == true;
            } else {
                $this->edit_exercise_in_lp = true;
            }

            if ($object->end_time != '0000-00-00 00:00:00') {
                $this->end_time = $object->end_time;
            }
            if ($object->start_time != '0000-00-00 00:00:00') {
                $this->start_time = $object->start_time;
            }

            // Control time
            $this->expired_time = $object->expired_time;

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
            } */

            return true;
        }

        // exercise not found
        return false;
    }

    /**
     * @return string
     */
    public function getCutTitle()
    {
        return Text::cut($this->exercise, EXERCISE_MAX_NAME_SIZE);
    }

    /**
     * Returns the exercise ID
     *
     * @author - Olivier Brouckaert
     *
     * @return int - exercise ID
     */
    public function selectId()
    {
        return $this->id;
    }

    /**
     * returns the exercise title
     *
     * @author - Olivier Brouckaert
     *
     * @return string - exercise title
     */
    public function selectTitle()
    {
        return $this->exercise;
    }

    /**
     * returns the number of attempts setted
     *
     * @return numeric - exercise attempts
     */
    public function selectAttempts()
    {
        return $this->attempts;
    }

    /** returns the number of FeedbackType  *
     *  0=>Feedback , 1=>DirectFeedback, 2=>NoFeedback
     *
     * @return int exercise attempts
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
     * @author - Olivier Brouckaert
     *
     * @return string - exercise description
     */
    public function selectDescription()
    {
        return $this->description;
    }

    /**
     * Returns the exercise sound file
     *
     * @author Olivier Brouckaert
     * @return string - exercise description
     */
    public function selectSound()
    {
        return $this->sound;
    }

    /**
     * Returns the exercise type
     *
     * @author - Olivier Brouckaert
     * @return integer - exercise type
     */
    public function selectType()
    {
        return $this->type;
    }

    /**
     * @author - hubert borderiou 30-11-11
     * @return integer : do we display the question category name for students
     */
    public function selectDisplayCategoryName()
    {
        return $this->display_category_name;
    }

    /**
     * @return string
     */
    public function selectPassPercentage()
    {
        return $this->pass_percentage;
    }

    /**
     * @return string
     */
    public function selectEmailNotificationTemplate()
    {
        return $this->emailNotificationTemplate;
    }

    /**
     * @return string
     */
    public function selectEmailNotificationTemplateToUser()
    {
        return $this->emailNotificationTemplateToUser;
    }

    /**
     * @return string
     */
    public function getNotifyUserByEmail()
    {
        return $this->notifyUserByEmail;
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
     * @author Hubert borderiou 30-11-11
     * @return void modify object to update the switch display_category_name
     * $in_txt is an integer 0 or 1
     */
    public function updateDisplayCategoryName($text)
    {
        $this->display_category_name = $text;
    }

    /**
     * @author - hubert borderiou 28-11-11
     * @return string html text : the text to display ay the end of the test.
     */
    public function selectTextWhenFinished()
    {
        return $this->text_when_finished;
    }

    /**
     * @author Hubert borderiou 28-11-11
     * @return string html text : update the text to display ay the end of the test.
     */
    public function updateTextWhenFinished($text)
    {
        $this->text_when_finished = $text;
    }

    /**
     * return 1 or 2 if randomByCat
     * @author - hubert borderiou
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
     * @author Hubert borderiou
     *
     * @return integer - quiz random by category
     */
    public function isRandomByCat()
    {
        $res = EXERCISE_CATEGORY_RANDOM_DISABLED;
        if ($this->randomByCat == EXERCISE_CATEGORY_RANDOM_SHUFFLED) {
            $res = EXERCISE_CATEGORY_RANDOM_SHUFFLED;
        } else if ($this->randomByCat == EXERCISE_CATEGORY_RANDOM_ORDERED) {
            $res = EXERCISE_CATEGORY_RANDOM_ORDERED;
        }

        return $res;
    }

    /**
     * Sets the random by category value
     * @author Julio Montoya
     * @param int random by category
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
     * @author - Carlos Vargas
     *
     * @return int results disabled exercise
     */
    public function selectResultsDisabled()
    {
        return $this->results_disabled;
    }

    /**
     * tells if questions are selected randomly, and if so returns the draws
     *
     * @author - Olivier Brouckaert
     *
     * @return bool - 0 if not random, otherwise the draws
     */
    public function isRandom()
    {
        if ($this->random > 0 || $this->random == -1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * returns random answers status.
     *
     * @author - Juan Carlos Raï¿½a
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
     * Returns the exercise status (1 = enabled ; 0 = disabled)
     *
     * @author - Olivier Brouckaert
     * @return - boolean - true if enabled, otherwise false
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
            $category_list = Testcategory::getListOfCategoriesNameForTest($this->id, false);
            //$category_list = Testcategory::getListOfCategoriesIDForTestObject($this);

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
                    $category_labels = Testcategory::return_category_labels($objQuestionTmp->category_list, $category_list);

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
        $sql = "SELECT count(e.iid) as count
                FROM $TBL_EXERCICE_QUESTION e INNER JOIN $TBL_QUESTIONS q
                    ON (e.question_id = q.iid)
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
                    ON (e.question_id= q.iid)
                WHERE e.c_id = {$this->course_id} AND e.exercice_id	= '".Database::escape_string($this->id)."'
                ORDER BY q.question";
        $result = Database::query($sql);
        return $result->fetchAll();
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

        // Getting question_order to verify that the question list is correct and all question_order's were set
        $sql = "SELECT DISTINCT e.question_order
                FROM $TBL_EXERCICE_QUESTION e INNER JOIN $TBL_QUESTIONS q
                    ON (e.question_id = q.iid)
                WHERE e.c_id = {$this->course_id} AND e.exercice_id	= ".Database::escape_string($this->id);

        $result = Database::query($sql);

        $count_question_orders = Database::num_rows($result);

        // Getting question list from the order (question list drag n drop interface ).
        $sql = "SELECT e.question_id, e.question_order
                FROM $TBL_EXERCICE_QUESTION e INNER JOIN $TBL_QUESTIONS q
                    ON (e.question_id= q.iid)
                WHERE e.c_id = {$this->course_id} AND e.exercice_id	= '".Database::escape_string($this->id)."'
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
     * @param array $question_list
     * @param array $questions_by_category per category
     * @param array how many questions per category
     * @param bool $randomizeQuestions
     * @param bool flat result
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
                    $elements = Testcategory::getNElementsFromArray($categoryQuestionList, $numberOfQuestions, $randomizeQuestions);
                if (!empty($elements)) {
                        $temp_question_list[$category_id] = $elements;
                        $categoryQuestionList = $elements;
                    }
                }
            }

            if (!empty($temp_question_list)) {
                if ($flatResult) {
                    $temp_question_list = ArrayClass::array_flatten($temp_question_list);
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
        $cat = new Testcategory();

        // Setting category order.

        switch ($questionSelectionType) {
            case EX_Q_SELECTION_ORDERED: // 1
            case EX_Q_SELECTION_RANDOM:  // 2
                // This options are not allowed here.
                break;
            case EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED: // 3
                $categoriesAddedInExercise = $cat->getCategoryExerciseTree($this->id, $this->course['real_id'], 'title DESC', false, true);
                $questions_by_category = Testcategory::getQuestionsByCat($this->id, $question_list, $categoriesAddedInExercise);
                $question_list = $this->pickQuestionsPerCategory($categoriesAddedInExercise, $question_list, $questions_by_category, true, false);
                break;
            case EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_ORDERED: // 4
            case EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_ORDERED_NO_GROUPED: // 7
                $categoriesAddedInExercise = $cat->getCategoryExerciseTree($this->id, $this->course['real_id'], null, true, true);
                $questions_by_category = Testcategory::getQuestionsByCat($this->id, $question_list, $categoriesAddedInExercise);
                $question_list = $this->pickQuestionsPerCategory($categoriesAddedInExercise, $question_list, $questions_by_category, true, false);
                break;
            case EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM: // 5
                $categoriesAddedInExercise = $cat->getCategoryExerciseTree($this->id, $this->course['real_id'], 'title DESC', false, true);
                $questions_by_category = Testcategory::getQuestionsByCat($this->id, $question_list, $categoriesAddedInExercise);
                $question_list = $this->pickQuestionsPerCategory($categoriesAddedInExercise, $question_list, $questions_by_category, true, true);
                    break;
            case EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM: // 6
            case EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM_NO_GROUPED:
                $categoriesAddedInExercise = $cat->getCategoryExerciseTree($this->id, $this->course['real_id'], null, true, true);
                $questions_by_category = Testcategory::getQuestionsByCat($this->id, $question_list, $categoriesAddedInExercise);
                $question_list = $this->pickQuestionsPerCategory($categoriesAddedInExercise, $question_list, $questions_by_category, true, true);
                    break;
            case EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_ORDERED_NO_GROUPED: // 7
                    break;
            case EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM_NO_GROUPED: // 8
                    break;
            case EX_Q_SELECTION_CATEGORIES_ORDERED_BY_PARENT_QUESTIONS_ORDERED: // 9
                $categoriesAddedInExercise = $cat->getCategoryExerciseTree($this->id, $this->course['real_id'], 'root ASC, lft ASC', false, true);
                $questions_by_category = Testcategory::getQuestionsByCat($this->id, $question_list, $categoriesAddedInExercise);
                $question_list = $this->pickQuestionsPerCategory($categoriesAddedInExercise, $question_list, $questions_by_category, true, false);
                    break;
            case EX_Q_SELECTION_CATEGORIES_ORDERED_BY_PARENT_QUESTIONS_RANDOM: // 10
                $categoriesAddedInExercise = $cat->getCategoryExerciseTree($this->id, $this->course['real_id'], 'root, lft ASC', false, true);
                $questions_by_category = Testcategory::getQuestionsByCat($this->id, $question_list, $categoriesAddedInExercise);
                $question_list = $this->pickQuestionsPerCategory($categoriesAddedInExercise, $question_list, $questions_by_category, true, true);
                    break;
        }

        $result['question_list'] = isset($question_list) ? $question_list : array();
        $result['category_with_questions_list'] = isset($questions_by_category) ? $questions_by_category : array();

        // Adding category info in the category list with question list:

        if (!empty($questions_by_category)) {
            global $app;
            $em = $app['orm.em'];
            $repo = $em->getRepository('ChamiloLMS\Entity\CQuizCategory');

            $newCategoryList = array();

            foreach ($questions_by_category as $categoryId => $questionList) {

                $cat = new Testcategory($categoryId);
                $cat = (array)$cat;
                $cat['iid'] = $cat['id'];
                $cat['name'] = $cat['title'];

                $categoryParentInfo = null;

                if (!empty($cat['parent_id'])) {
                    if (!isset($parentsLoaded[$cat['parent_id']])) {
                        $categoryEntity = $em->find('ChamiloLMS\Entity\CQuizCategory', $cat['parent_id']);
                        $parentsLoaded[$cat['parent_id']] = $categoryEntity;
                    } else {
                        $categoryEntity = $parentsLoaded[$cat['parent_id']];
                    }
                    $path = $repo->getPath($categoryEntity);
                    $index = 0;
                    if ($this->categoryMinusOne) {
                        //$index = 1;
                    }
                    /** @var \ChamiloLMS\Entity\CQuizCategory $categoryParent*/

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
     * Returns the array with the question ID list ordered by question order (including question list in medias)
     *
     * @author Olivier Brouckaert
     * @param bool $from_db
     * @return array question ID list
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
                    $result = $this->getQuestionListWithCategoryListFilteredByCategorySettings($questionList, $questionSelectionType);

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
     * @author - Olivier Brouckaert
     * @return integer - number of questions
     */
    public function selectNbrQuestions()
    {
        return sizeof($this->questionList);
    }

    public function selectPropagateNeg()
    {
        return $this->propagate_neg;
    }

    /**
     * Selects questions randomly in the question list
     * If the exercise is not set to take questions randomly, returns the question list
     * without randomizing, otherwise, returns the list with questions selected randomly
     *
     * @author Olivier Brouckaert, Modified by Hubert Borderiou 15 nov 2011
     * @param array question list
     * @return array question list modified or unmodified
     *
     */
    public function selectRandomList()
    {
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
     * @author - Olivier Brouckaert
     * @param - integer $questionId - question ID
     *
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
     * @author - Olivier Brouckaert
     * @param - string $title - exercise title
     */
    public function updateTitle($title)
    {
        $this->exercise = $title;
    }

    /**
     * changes the exercise max attempts
     *
     * @param - numeric $attempts - exercise max attempts
     */
    public function updateAttempts($attempts)
    {
        $this->attempts = $attempts;
    }

    public function updateActive($active)
    {
        $this->active = $active;
    }

    /**
     * changes the exercise feedback type
     *
     * @param - numeric $attempts - exercise max attempts
     */
    public function updateFeedbackType($feedback_type)
    {
        $this->feedback_type = $feedback_type;
    }

    /**
     * changes the exercise description
     *
     * @author - Olivier Brouckaert
     * @param - string $description - exercise description
     */
    public function updateDescription($description)
    {
        $this->description = $description;
    }

    /**
     * changes the exercise expired_time
     *
     * @author - Isaac flores
     * @param - int The expired time of the quiz
     */
    public function updateExpiredTime($expired_time)
    {
        $this->expired_time = $expired_time;
    }

    public function updatePropagateNegative($value)
    {
        $this->propagate_neg = $value;
    }

    /**
     * @param $value
     */
    public function updateReviewAnswers($value)
    {
        $this->review_answers = (isset($value) && $value) ? true : false;
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
     * @param intval $value
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
     * @author - Olivier Brouckaert
     * @param - string $sound - exercise sound file
     * @param - string $delete - ask to delete the file
     */
    public function updateSound($sound, $delete)
    {
        global $audioPath, $documentPath;
        $TBL_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);

        if ($sound['size'] && (strstr($sound['type'], 'audio') || strstr($sound['type'], 'video'))) {
            $this->sound = $sound['name'];

            if (@move_uploaded_file($sound['tmp_name'], $audioPath.'/'.$this->sound)) {
                $query = "SELECT 1 FROM $TBL_DOCUMENT  WHERE c_id = ".$this->course_id." AND path='".str_replace(
                    $documentPath,
                    '',
                    $audioPath
                ).'/'.$this->sound."'";
                $result = Database::query($query);

                if (!Database::num_rows($result)) {
                    $id = FileManager::add_document(
                        $this->course,
                        str_replace($documentPath, '', $audioPath).'/'.$this->sound,
                        'file',
                        $sound['size'],
                        $sound['name']
                    );
                    api_item_property_update($this->course, TOOL_DOCUMENT, $id, 'DocumentAdded', api_get_user_id());
                    FileManager::item_property_update_on_folder(
                        $this->course,
                        str_replace($documentPath, '', $audioPath),
                        api_get_user_id()
                    );
                }
            }
        } elseif ($delete && is_file($audioPath.'/'.$this->sound)) {
            $this->sound = '';
        }
    }

    /**
     * changes the exercise type
     *
     * @author - Olivier Brouckaert
     * @param - integer $type - exercise type
     */
    public function updateType($type)
    {
        $this->type = $type;
    }

    /**
     * sets to 0 if questions are not selected randomly
     * if questions are selected randomly, sets the draws
     *
     * @author - Olivier Brouckaert
     * @param - integer $random - 0 if not random, otherwise the draws
     */
    public function setRandom($random)
    {
        $this->random = $random;
    }

    /**
     * sets to 0 if answers are not selected randomly
     * if answers are selected randomly
     * @author - Juan Carlos Raï¿½a
     * @param - integer $random_answers - random answers
     */
    public function updateRandomAnswers($random_answers)
    {
        $this->random_answers = $random_answers;
    }

    /**
     * Enables the exercise
     *
     * @author - Olivier Brouckaert
     */
    public function enable()
    {
        $this->active = 1;
    }

    /**
     * Disables the exercise
     *
     * @author - Olivier Brouckaert
     */
    public function disable()
    {
        $this->active = 0;
    }

    /**
     * Disable results
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
     * @author - Olivier Brouckaert
     */
    public function save($type_e = '')
    {
        $_course = $this->course;
        $TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST);

        $id = $this->id;
        $exercise = $this->exercise;
        $sound = $this->sound;
        $type = $this->type;
        $attempts = $this->attempts;
        $feedback_type = $this->feedback_type;
        $random = $this->random;
        $random_answers = $this->random_answers;
        $active = $this->active;
        $propagate_neg = $this->propagate_neg;
        $review_answers = (isset($this->review_answers) && $this->review_answers) ? 1 : 0;
        $randomByCat = $this->randomByCat;
        $text_when_finished = $this->text_when_finished;
        $display_category_name = intval($this->display_category_name);
        $pass_percentage = intval($this->pass_percentage);
        $session_id = api_get_session_id();

        // If direct we do not show results
        if ($feedback_type == EXERCISE_FEEDBACK_TYPE_DIRECT) {
            $results_disabled = 0;
        } else {
            $results_disabled = intval($this->results_disabled);
        }

        $expired_time = intval($this->expired_time);

        if (!empty($this->start_time) && $this->start_time != '0000-00-00 00:00:00') {
            $start_time = Database::escape_string(api_get_utc_datetime($this->start_time));
        } else {
            $start_time = '0000-00-00 00:00:00';
        }
        if (!empty($this->end_time) && $this->end_time != '0000-00-00 00:00:00') {
            $end_time = Database::escape_string(api_get_utc_datetime($this->end_time));
        } else {
            $end_time = '0000-00-00 00:00:00';
        }

        // Exercise already exists
        if ($id) {
            $sql = "UPDATE $TBL_EXERCICES SET
				    title='".Database::escape_string($exercise)."',
					description='".Database::escape_string($this->description)."'";

            if ($type_e != 'simple') {
                $sql .= ",sound='".Database::escape_string($sound)."',
					type           ='".Database::escape_string($type)."',
					random         ='".Database::escape_string($random)."',
					random_answers ='".Database::escape_string($random_answers)."',
					active         ='".Database::escape_string($active)."',
					feedback_type  ='".Database::escape_string($feedback_type)."',
					start_time     = '$start_time',
					end_time       = '$end_time',
					max_attempt    ='".Database::escape_string($attempts)."',
     			    expired_time   ='".Database::escape_string($expired_time)."',
         			propagate_neg  ='".Database::escape_string($propagate_neg)."',
         			review_answers  ='".Database::escape_string($review_answers)."',
        	        random_by_category='".Database::escape_string($randomByCat)."',
        	        text_when_finished = '".Database::escape_string($text_when_finished)."',
        	        display_category_name = '".Database::escape_string($display_category_name)."',
                    pass_percentage = '".Database::escape_string($pass_percentage)."',
                    end_button = '".$this->selectEndButton()."',
                    on_success_message = '".Database::escape_string($this->getOnSuccessMessage())."',
                    on_failed_message = '".Database::escape_string($this->getOnFailedMessage())."',
                    email_notification_template = '".Database::escape_string($this->selectEmailNotificationTemplate())."',
                    email_notification_template_to_user = '".Database::escape_string($this->selectEmailNotificationTemplateToUser())."',
                    notify_user_by_email = '".Database::escape_string($this->getNotifyUserByEmail())."',
                    model_type = '".$this->getModelType()."',
                    question_selection_type = '".$this->getQuestionSelectionType()."',
                    hide_question_title = '".$this->getHideQuestionTitle()."',
                    score_type_model = '".$this->getScoreTypeModel()."',
                    global_category_id = '".$this->getGlobalCategoryId()."',
					results_disabled='".Database::escape_string($results_disabled)."'";
            }
            $sql .= " WHERE iid = ".Database::escape_string($id)." AND c_id = {$this->course_id}";
            Database::query($sql);

            // Update into the item_property table
            api_item_property_update($_course, TOOL_QUIZ, $id, 'QuizUpdated', api_get_user_id());

            if (api_get_setting('search_enabled') == 'true') {
                $this->search_engine_edit();
            }
        } else {
            // Creates a new exercise
            $sql = "INSERT INTO $TBL_EXERCICES (
                        c_id,
                        start_time,
                        end_time,
                        title,
                        description,
                        sound,
                        type,
                        random,
                        random_answers,
                        active,
                        max_attempt,
                        feedback_type,
                        expired_time,
                        session_id,
                        review_answers,
                        random_by_category,
                        text_when_finished,
                        display_category_name,
                        pass_percentage,
                        end_button,
                        on_success_message,
                        on_failed_message,
                        email_notification_template,
                        email_notification_template_to_user,
                        notify_user_by_email,
                        results_disabled,
                        model_type,
                        question_selection_type,
                        score_type_model,
                        global_category_id,
                        hide_question_title
                    ) VALUES (
						".$this->course_id.",
						'$start_time',
                        '$end_time',
						'".Database::escape_string($exercise)."',
						'".Database::escape_string($this->description)."',
						'".Database::escape_string($sound)."',
						'".Database::escape_string($type)."',
						'".Database::escape_string($random)."',
						'".Database::escape_string($random_answers)."',
						'".Database::escape_string($active)."',
						'".Database::escape_string($attempts)."',
						'".Database::escape_string($feedback_type)."',
						'".Database::escape_string($expired_time)."',
						'".Database::escape_string($session_id)."',
						'".Database::escape_string($review_answers)."',
						'".Database::escape_string($randomByCat)."',
						'".Database::escape_string($text_when_finished)."',
						'".Database::escape_string($display_category_name)."',
                        '".Database::escape_string($pass_percentage)."',
                        '".Database::escape_string($this->selectEndButton())."',
                        '".Database::escape_string($this->getOnSuccessMessage())."',
                        '".Database::escape_string($this->getOnFailedMessage())."',
                        '".Database::escape_string($this->selectEmailNotificationTemplate())."',
                        '".Database::escape_string($this->selectEmailNotificationTemplateToUser())."',
                        '".Database::escape_string($this->getNotifyUserByEmail())."',
                        '".Database::escape_string($results_disabled)."',
                        '".Database::escape_string($this->getModelType())."',
                        '".Database::escape_string($this->getQuestionSelectionType())."',
                        '".Database::escape_string($this->getScoreTypeModel())."',
                        '".Database::escape_string($this->getGlobalCategoryId())."',
                        '".Database::escape_string($this->getHideQuestionTitle())."'
						)";
            Database::query($sql);
            $this->id = Database::insert_id();

            $this->addExerciseToOrderTable();

            // insert into the item_property table
            api_item_property_update($this->course, TOOL_QUIZ, $this->id, 'QuizAdded', api_get_user_id());
            api_set_default_visibility($this->course, $this->id, TOOL_QUIZ);

            if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
                $this->search_engine_save();
            }
        }
        $this->save_categories_in_exercise($this->categories);

        // Updates the question position.
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
        if (!empty($question_list) && !empty($this->course_id) && !empty($this->id)) {
            foreach ($question_list as $position => $questionId) {
                $sql = "UPDATE $quiz_question_table SET question_order ='".intval($position)."'".
                "WHERE c_id = ".$this->course_id." AND question_id = ".intval($questionId)." AND exercice_id = ".$this->id;
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
     * @author - Olivier Brouckaert
     * @param integer $questionId - question ID
     * @return boolean - true if the question has been removed, otherwise false
     */
    public function removeFromList($questionId)
    {
        // searches the position of the question ID in the list
        $pos = array_search($questionId, $this->questionList);

        // question not found
        if ($pos === false) {
            return false;
        } else {
            // dont reduce the number of random question if we use random by category option, or if
            // random all questions
            if ($this->isRandom() && $this->isRandomByCat() == EXERCISE_CATEGORY_RANDOM_DISABLED) {
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
     * @author - Olivier Brouckaert
     */
    public function delete()
    {
        $TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST);
        $sql = "UPDATE $TBL_EXERCICES
                SET active='-1' WHERE c_id = ".$this->course_id." AND iid='".Database::escape_string($this->id)."'";
        Database::query($sql);
        api_item_property_update($this->course, TOOL_QUIZ, $this->id, 'QuizDeleted', api_get_user_id());
        $this->delete_exercise_order();

        if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
            $this->search_engine_delete();
        }
    }

    /**
     * Creates the form to create / edit an exercise
     * @param FormValidator $form the formvalidator instance (by reference)
     * @param string
     */
    public function createForm($form, $type = 'full')
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

        // title
        $form->addElement(
            'text',
            'exerciseTitle',
            get_lang('ExerciseName'),
            array('class' => 'span6', 'id' => 'exercise_title')
        );
        $editor_config = array('ToolbarSet' => 'TestQuestionDescription', 'Width' => '100%', 'Height' => '150');
        if (is_array($type)) {
            $editor_config = array_merge($editor_config, $type);
        }

        $form->add_html_editor('exerciseDescription', get_lang('ExerciseDescription'), false, false, $editor_config);
        $form->addElement('advanced_settings', 'options', get_lang('AdvancedParameters'));

        $form->addElement('html', '<div id="options_options" style="display:none">');

        // Model type
        $radio = array(
            $form->createElement('radio', 'model_type', null, get_lang('Normal'), EXERCISE_MODEL_TYPE_NORMAL),
            $form->createElement('radio', 'model_type', null, get_lang('Committee'), EXERCISE_MODEL_TYPE_COMMITTEE)
        );

        $form->addGroup($radio, null, get_lang('ModelType'), '');
        $modelType = $this->getModelType();

        $scoreTypeDisplay = 'display:none';
        if ($modelType == EXERCISE_MODEL_TYPE_COMMITTEE) {
            $scoreTypeDisplay = null;
        }

        $form->addElement('html', '<div id="score_type" style="'.$scoreTypeDisplay.'">');

        // QuestionScoreType

        global $app;
        $em = $app['orm.em'];
        $types = $em->getRepository('ChamiloLMS\Entity\QuestionScore')->findAll();
        $options = array(
            '0' => get_lang('SelectAnOption')
        );

        foreach ($types as $questionType) {
            $options[$questionType->getId()] = $questionType->getName();
        }

        $form->addElement(
            'select',
            'score_type_model',
            array(get_lang('QuestionScoreType')),
            $options,
            array('id' => 'score_type_model')
        );

        $form->addElement('html', '</div>');

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
                    array('id' => 'exerciseType_0', 'onclick' => 'check_feedback()')
                );

                if (api_get_setting('enable_quiz_scenario') == 'true') {
                    /* Can't convert a question from one feedback to another if there
                    is more than 1 question already added */
                    if ($this->selectNbrQuestions() == 0) {
                        $radios_feedback[] = $form->createElement(
                            'radio',
                            'exerciseFeedbackType',
                            null,
                            get_lang('DirectFeedback'),
                            '1',
                            array('id' => 'exerciseType_1', 'onclick' => 'check_direct_feedback()')
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
                $form->addGroup($radios_feedback, null, get_lang('FeedbackType'), '');

                // question
                $radios = array(
                    $form->createElement('radio', 'exerciseType', null, get_lang('SimpleExercise'), ALL_ON_ONE_PAGE, array('onclick' => 'check_per_page_all()', 'id' => 'option_page_all')),
                    $form->createElement('radio', 'exerciseType', null, get_lang('SequentialExercise'), ONE_PER_PAGE, array('onclick' => 'check_per_page_one()', 'id' => 'option_page_one')),
                );

                $form->addGroup($radios, null, get_lang('QuestionsPerPage'), '');

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
                    array('id' => 'result_disabled_2', 'onclick' => 'check_results_disabled()')
                );

                $form->addGroup($radios_results_disabled, null, get_lang('ShowResultsToStudents'), '');
            } else {
                // if is Directfeedback but has not questions we can allow to modify the question type
                if ($this->selectNbrQuestions() == 0) {

                    // feedback type
                    $radios_feedback = array();
                    $radios_feedback[] = $form->createElement(
                        'radio',
                        'exerciseFeedbackType',
                        null,
                        get_lang('ExerciseAtTheEndOfTheTest'),
                        '0',
                        array('id' => 'exerciseType_0', 'onclick' => 'check_feedback()')
                    );

                    if (api_get_setting('enable_quiz_scenario') == 'true') {
                        $radios_feedback[] = $form->createElement(
                            'radio',
                            'exerciseFeedbackType',
                            null,
                            get_lang('DirectFeedback'),
                            '1',
                            array('id' => 'exerciseType_1', 'onclick' => 'check_direct_feedback()')
                        );
                    }
                    $radios_feedback[] = $form->createElement(
                        'radio',
                        'exerciseFeedbackType',
                        null,
                        get_lang('NoFeedback'),
                        '2',
                        array('id' => 'exerciseType_2')
                    );
                    $form->addGroup($radios_feedback, null, get_lang('FeedbackType'));

                    // Exercise type
                    $radios = array(
                        $form->createElement('radio', 'exerciseType', null, get_lang('SimpleExercise'), '1'),
                        $form->createElement('radio', 'exerciseType', null, get_lang('SequentialExercise'), '2'),
                        $form->createElement('radio', 'exerciseType', null, get_lang('Committee'), '3')
                    );
                    $form->addGroup($radios, null, get_lang('ExerciseType'));

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
                        array('id' => 'result_disabled_2', 'onclick' => 'check_results_disabled()')
                    );
                    $form->addGroup($radios_results_disabled, null, get_lang('ShowResultsToStudents'), '');
                } else {
                    //Show options freeze
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
                        array('id' => 'result_disabled_2', 'onclick' => 'check_results_disabled()')
                    );
                    $result_disable_group = $form->addGroup(
                        $radios_results_disabled,
                        null,
                        get_lang('ShowResultsToStudents'),
                        ''
                    );
                    $result_disable_group->freeze();

                    $radios[] = $form->createElement(
                        'radio',
                        'exerciseType',
                        null,
                        get_lang('SimpleExercise'),
                        '1',
                        array('onclick' => 'check_per_page_all()', 'id' => 'option_page_all')
                    );
                    $radios[] = $form->createElement(
                        'radio',
                        'exerciseType',
                        null,
                        get_lang('SequentialExercise'),
                        '2',
                        array('onclick' => 'check_per_page_one()', 'id' => 'option_page_one')
                    );

                    $type_group = $form->addGroup($radios, null, get_lang('QuestionsPerPage'), '');
                    $type_group->freeze();

                    //we force the options to the DirectFeedback exercisetype
                    $form->addElement('hidden', 'exerciseFeedbackType', EXERCISE_FEEDBACK_TYPE_DIRECT);
                    $form->addElement('hidden', 'exerciseType', ONE_PER_PAGE);
                }
            }

            $option = array(
                EX_Q_SELECTION_ORDERED => get_lang('OrderedByUser'), //  defined by user
                EX_Q_SELECTION_RANDOM => get_lang('Random'), // 1-10, All
                'per_categories' => '--------'.get_lang('UsingCategories').'----------',

                // Base (A 123 {3} B 456 {3} C 789{2} D 0{0}) --> Matrix {3, 3, 2, 0}
                EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED => get_lang('OrderedCategoriesAlphabeticallyWithQuestionsOrdered'), // A 123 B 456 C 78 (0, 1, all)
                EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_ORDERED => get_lang('RandomCategoriesWithQuestionsOrdered'), // C 78 B 456 A 123

                EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM => get_lang('OrderedCategoriesAlphabeticallyWithRandomQuestions'), // A 321 B 654 C 87
                EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM => get_lang('RandomCategoriesWithRandomQuestions'), //C 87 B 654 A 321

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
                EX_Q_SELECTION_CATEGORIES_ORDERED_BY_PARENT_QUESTIONS_ORDERED => get_lang('OrderedCategoriesByParentWithQuestionsOrdered'),
                EX_Q_SELECTION_CATEGORIES_ORDERED_BY_PARENT_QUESTIONS_RANDOM => get_lang('OrderedCategoriesByParentWithQuestionsRandom'),
            );

            $form->addElement(
                'select',
                'question_selection_type',
                array(get_lang('QuestionSelection')),
                $option,
                array('id' => 'questionSelection', 'onclick' => 'checkQuestionSelection()')
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

            $form->addElement('html', '<div id="hidden_random" style="display:'.$displayRandom.'">');
            // Number of random question.
            $max = ($this->id > 0) ? $this->selectNbrQuestions() : 10;
            $option = range(0, $max);
            $option[0] = get_lang('No');
            $option[-1] = get_lang('AllQuestionsShort');
            $form->addElement(
                'select',
                'randomQuestions',
                array(get_lang('RandomQuestions'), get_lang('RandomQuestionsHelp')),
                $option,
                array('id' => 'randomQuestions')
            );
            $form->addElement('html', '</div>');

            $form->addElement('html', '<div id="hidden_matrix" style="display:'.$displayMatrix.'">');

            // Category selection.
            $cat = new Testcategory();
            $cat_form = $cat->returnCategoryForm($this);
            $form->addElement('label', null, $cat_form);
            $form->addElement('html', '</div>');

            // Category name.
            $radio_display_cat_name = array(
                $form->createElement('radio', 'display_category_name', null, get_lang('Yes'), '1'),
                $form->createElement('radio', 'display_category_name', null, get_lang('No'), '0')
            );
            $form->addGroup($radio_display_cat_name, null, get_lang('QuestionDisplayCategoryName'), '');

            // Random answers.
            $radios_random_answers = array(
                $form->createElement('radio', 'randomAnswers', null, get_lang('Yes'), '1'),
                $form->createElement('radio', 'randomAnswers', null, get_lang('No'), '0')
            );
            $form->addGroup($radios_random_answers, null, get_lang('RandomAnswers'), '');

            // Hide question title.
            $group = array(
                $form->createElement('radio', 'hide_question_title', null, get_lang('Yes'), '1'),
                $form->createElement('radio', 'hide_question_title', null, get_lang('No'), '0')
            );
            $form->addGroup($group, null, get_lang('HideQuestionTitle'), '');

            // Attempts.
            $attempt_option = range(0, 10);
            $attempt_option[0] = get_lang('Infinite');

            $form->addElement(
                'select',
                'exerciseAttempts',
                get_lang('ExerciseAttempts'),
                $attempt_option,
                array('id' => 'exerciseAttempts')
            );

            // Exercise time limit.
            $form->addElement(
                'checkbox',
                'activate_start_date_check',
                null,
                get_lang('EnableStartTime'),
                array('onclick' => 'activate_start_date()')
            );

            // Start time.
            if (($this->start_time != '0000-00-00 00:00:00')) {
                $form->addElement('html', '<div id="start_date_div" style="display:block;">');
            } else {
                $form->addElement('html', '<div id="start_date_div" style="display:none;">');
            }
            $form->addElement('datepicker', 'start_time', '', array('form_name' => 'exercise_admin'), 5);
            $form->addElement('html', '</div>');

            // End time.
            $form->addElement(
                'checkbox',
                'activate_end_date_check',
                null,
                get_lang('EnableEndTime'),
                array('onclick' => 'activate_end_date()')
            );

            if (($this->end_time != '0000-00-00 00:00:00')) {
                $form->addElement('html', '<div id="end_date_div" style="display:block;">');
            } else {
                $form->addElement('html', '<div id="end_date_div" style="display:none;">');
            }
            $form->addElement('datepicker', 'end_time', '', array('form_name' => 'exercise_admin'), 5);
            $form->addElement('html', '</div>');

            // Propagate negative values.
            $display = 'block';
            $form->addElement('checkbox', 'propagate_neg', null, get_lang('PropagateNegativeResults'));
            $form->addElement('html', '<div class="clear">&nbsp;</div>');
            $form->addElement('checkbox', 'review_answers', null, get_lang('ReviewAnswers'));
            $form->addElement('html', '<div id="divtimecontrol"  style="display:'.$display.';">');

            // Exercise timer.
            $form->addElement(
                'checkbox',
                'enabletimercontrol',
                null,
                get_lang('EnableTimerControl'),
                array(
                    'onclick' => 'option_time_expired()',
                    'id' => 'enabletimercontrol',
                    'onload' => 'check_load_time()'
                )
            );
            $expired_date = (int)$this->selectExpiredTime();

            if (($expired_date != '0')) {
                $form->addElement('html', '<div id="timercontrol" style="display:block;">');
            } else {
                $form->addElement('html', '<div id="timercontrol" style="display:none;">');
            }

            $form->addElement(
                'text',
                'enabletimercontroltotalminutes',
                get_lang('ExerciseTotalDurationInMinutes'),
                array('style' => 'width : 35px', 'id' => 'enabletimercontroltotalminutes')
            );
            $form->addElement('html', '</div>');

            // Pass percentage.
            $form->addElement(
                'text',
                'pass_percentage',
                array(get_lang('PassPercentage'), null, '%'),
                array('id' => 'pass_percentage')
            );

            $form->addRule('pass_percentage', get_lang('Numeric'), 'numeric');

            // On success
            $form->add_html_editor('on_success_message', get_lang('MessageOnSuccess'), false, false, $editor_config);
            // On failed
            $form->add_html_editor('on_failed_message', get_lang('MessageOnFailed'), false, false, $editor_config);

            $url = api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?1=1';


            $js = '<script>

            function check() {
                var counter = 0;
                $("#global_category_id option:selected").each(function() {
                    var id = $(this).val();
                    var name = $(this).text();
                    if (id != "" ) {

                        $.ajax({
                            async: false,
                            url: "'.$url.'&a=exercise_category_exists&type=global",
                            data: "id="+id,
                            success: function(return_value) {
                                if (return_value == 0 ) {
                                    alert("'.addslashes(get_lang('CategoryDoesNotExists')).'");
                                    // Deleting select option tag
                                    $("#global_category_id").find("option").remove();

                                    $(".holder li").each(function () {
                                        if ($(this).attr("rel") == id) {
                                            $(this).remove();
                                        }
                                    });
                                }
                            },
                        });
                    }
                    counter++;
                });
            }

            $(function() {
                $("#global_category_id").fcbkcomplete({
                    json_url: "'.$url.'&a=search_category_parent&type=global&",
                    maxitems: 1 ,
                    addontab: false,
                    input_min_size: 1,
                    cache: false,
                    complete_text:"'.get_lang('StartToType').'",
                    firstselected: false,
                    onselect: check,
                    filter_selected: true,
                    newel: true
                });
            });

            </script>';
            $form->addElement('html', $js);

            $categoryJS = null;
            $globalCategoryId = $this->getGlobalCategoryId();
            if (!empty($globalCategoryId)) {
                $cat = new Testcategory($globalCategoryId);
                $trigger = '$("#global_category_id").trigger("addItem",[{ "title": "'.$cat->title.'", "value": "'.$globalCategoryId.'"}]);';
                $categoryJS .= '<script>$(function() { '.$trigger.' });</script>';
            }
            $form->addElement('html', $categoryJS);

             // Global category id.
            $form->addElement(
                'select',
                'global_category_id',
                array(get_lang('GlobalCategory')),
                array(),
                array('id' => 'global_category_id')
            );

            // Text when ending an exam.
            $form->add_html_editor('text_when_finished', get_lang('TextWhenFinished'), false, false, $editor_config);

            // Exam end button.
            $group = array(
                $form->createElement('radio', 'end_button', null, get_lang('ExerciseEndButtonCourseHome'), '0'),
                $form->createElement('radio', 'end_button', null, get_lang('ExerciseEndButtonExerciseHome'), '1'),
                $form->createElement('radio', 'end_button', null, get_lang('ExerciseEndButtonDisconnect'), '2'),
                $form->createElement('radio', 'end_button', null, get_lang('ExerciseEndButtonNoButton'), '3')
            );
            $form->addGroup($group, null, get_lang('ExerciseEndButton'));
            $form->addElement('html', '<div class="clear">&nbsp;</div>');

            $defaults = array();

            if (api_get_setting('search_enabled') === 'true') {
                require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';

                $form->addElement('checkbox', 'index_document', '', get_lang('SearchFeatureDoIndexDocument'));
                $form->addElement('select_language', 'language', get_lang('SearchFeatureDocumentLanguage'));

                $specific_fields = get_specific_field_list();

                foreach ($specific_fields as $specific_field) {
                    $form->addElement('text', $specific_field['code'], $specific_field['name']);
                    $filter = array(
                        'c_id' => "'".api_get_course_int_id()."'",
                        'field_id' => $specific_field['id'],
                        'ref_id' => $this->id,
                        'tool_id' => '\''.TOOL_QUIZ.'\''
                    );
                    $values = get_specific_field_values_list($filter, array('value'));
                    if (!empty($values)) {
                        $arr_str_values = array();
                        foreach ($values as $value) {
                            $arr_str_values[] = $value['value'];
                        }
                        $defaults[$specific_field['code']] = implode(', ', $arr_str_values);
                    }
                }
            }

            if ($this->emailAlert) {

                // Email notification template
                $form->add_html_editor(
                    'email_notification_template',
                    array(get_lang('EmailNotificationTemplateToTeacher'), get_lang('EmailNotificationTemplateToTeacherDescription')),
                    null,
                    false,
                    $editor_config
                );
            }

            $group = array(
                $form->createElement(
                    'radio', 'notify_user_by_email', null, get_lang('Yes'), '1', array('id' => 'notify_user_by_email_on', 'class' => 'advanced_options_open', 'rel' => 'notify_user_by_email_options')
                ),
                $form->createElement(
                    'radio', 'notify_user_by_email', null, get_lang('No'), '0', array('id' => 'notify_user_by_email_off', 'class' => 'advanced_options_close', 'rel' => 'notify_user_by_email_options')
                )
            );

            $form->addGroup($group, null, get_lang('NotifyUserByEmail'));
            $hide = 'style="display:none"';

            if ($this->notifyUserByEmail == 1) {
                $hide = null;
            }

            $form->addElement('html', '<div id="notify_user_by_email_options" '.$hide.'>');

            // Email notification template to user
            $form->add_html_editor(
                'email_notification_template_to_user',
                array(get_lang('EmailNotificationTemplateToUser'), get_lang('EmailNotificationTemplateToUserDescription')),
                null,
                false,
                $editor_config
            );
            $form->addElement('html', '</div>');

            // End advanced setting.
            $form->addElement('html', '</div>');
            $form->addElement('html', '</div>');
        }

        // Category selection.
        $cat = new Testcategory();
        $cat_form = $cat->returnCategoryForm($this);
        $form->addElement('html', $cat_form);

        // submit
        $text = isset($_GET['exerciseId']) ? get_lang('ModifyExercise') : get_lang('ProcedToQuestions');

        $form->addElement('style_submit_button', 'submitExercise', $text, 'class="save"');

        $form->addRule('exerciseTitle', get_lang('GiveExerciseName'), 'required');

        if ($type == 'full') {
            // rules
            $form->addRule('exerciseAttempts', get_lang('Numeric'), 'numeric');
            $form->addRule('start_time', get_lang('InvalidDate'), 'date');
            $form->addRule('end_time', get_lang('InvalidDate'), 'date');
        }

        // defaults
        if ($type == 'full') {
            if ($this->id > 0) {
                if ($this->random > $this->selectNbrQuestions()) {
                    $defaults['randomQuestions'] = $this->selectNbrQuestions();
                } else {
                    $defaults['randomQuestions'] = $this->random;
                }

                $defaults['randomAnswers'] = $this->selectRandomAnswers();
                $defaults['exerciseType'] = $this->selectType();
                $defaults['exerciseTitle'] = $this->selectTitle();
                $defaults['exerciseDescription'] = $this->selectDescription();
                $defaults['exerciseAttempts'] = $this->selectAttempts();
                $defaults['exerciseFeedbackType'] = $this->selectFeedbackType();
                $defaults['results_disabled'] = $this->selectResultsDisabled();
                $defaults['propagate_neg'] = $this->selectPropagateNeg();
                $defaults['review_answers'] = $this->review_answers;
                $defaults['randomByCat'] = $this->selectRandomByCat();
                $defaults['text_when_finished'] = $this->selectTextWhenFinished();
                $defaults['display_category_name'] = $this->selectDisplayCategoryName();
                $defaults['pass_percentage'] = $this->selectPassPercentage();
                $defaults['end_button'] = $this->selectEndButton();
                $defaults['on_success_message'] = $this->getOnSuccessMessage();
                $defaults['on_failed_message'] = $this->getOnFailedMessage();
                $defaults['email_notification_template'] = $this->selectEmailNotificationTemplate();
                $defaults['email_notification_template_to_user'] = $this->selectEmailNotificationTemplateToUser();
                $defaults['notify_user_by_email'] = $this->getNotifyUserByEmail();
                $defaults['model_type'] = $this->getModelType();
                $defaults['question_selection_type'] = $this->getQuestionSelectionType();
                $defaults['score_type_model'] = $this->getScoreTypeModel();
                //$defaults['global_category_id'] = $this->getScoreTypeModel();

                $defaults['hide_question_title'] = $this->getHideQuestionTitle();

                if (($this->start_time != '0000-00-00 00:00:00')) {
                    $defaults['activate_start_date_check'] = 1;
                }
                if ($this->end_time != '0000-00-00 00:00:00') {
                    $defaults['activate_end_date_check'] = 1;
                }

                $defaults['start_time'] = ($this->start_time != '0000-00-00 00:00:00') ? api_get_local_time(
                    $this->start_time
                ) : date('Y-m-d 12:00:00');
                $defaults['end_time'] = ($this->end_time != '0000-00-00 00:00:00') ? api_get_local_time(
                    $this->end_time
                ) : date('Y-m-d 12:00:00', time() + 84600);

                //Get expired time
                if ($this->expired_time != '0') {
                    $defaults['enabletimercontrol'] = 1;
                    $defaults['enabletimercontroltotalminutes'] = $this->expired_time;
                } else {
                    $defaults['enabletimercontroltotalminutes'] = 0;
                }
            } else {
                $defaults['model_type'] = 1;
                $defaults['exerciseType'] = 2;
                $defaults['exerciseAttempts'] = 0;
                $defaults['randomQuestions'] = 0;
                $defaults['randomAnswers'] = 0;
                $defaults['exerciseDescription'] = '';
                $defaults['exerciseFeedbackType'] = 0;
                $defaults['results_disabled'] = 0;
                $defaults['randomByCat'] = 0; //
                $defaults['text_when_finished'] = ""; //
                $defaults['start_time'] = date('Y-m-d 12:00:00');
                $defaults['display_category_name'] = 1; //
                $defaults['end_time'] = date('Y-m-d 12:00:00', time() + 84600);
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
    }

    /**
     * function which process the creation of exercises
     * @param FormValidator $form the formvalidator instance
     */
    function processCreation($form, $type = '')
    {
        $values = $form->exportValues();
        $this->updateTitle($form->getSubmitValue('exerciseTitle'));
        $this->updateDescription($form->getSubmitValue('exerciseDescription'));
        $this->updateAttempts($form->getSubmitValue('exerciseAttempts'));
        $this->updateFeedbackType($form->getSubmitValue('exerciseFeedbackType'));
        $this->updateType($form->getSubmitValue('exerciseType'));
        $this->setRandom($form->getSubmitValue('randomQuestions'));
        $this->updateRandomAnswers($form->getSubmitValue('randomAnswers'));
        $this->updateResultsDisabled($form->getSubmitValue('results_disabled'));
        $this->updateExpiredTime($form->getSubmitValue('enabletimercontroltotalminutes'));
        $this->updatePropagateNegative($form->getSubmitValue('propagate_neg'));
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

        $this->setScoreTypeModel($form->getSubmitValue('score_type_model'));
        $this->setGlobalCategoryId($form->getSubmitValue('global_category_id'));

        if ($form->getSubmitValue('activate_start_date_check') == 1) {
            $start_time = $form->getSubmitValue('start_time');
            $start_time['F'] = sprintf('%02d', $start_time['F']);
            $start_time['i'] = sprintf('%02d', $start_time['i']);
            $start_time['d'] = sprintf('%02d', $start_time['d']);

            $this->start_time = $start_time['Y'].'-'.$start_time['F'].'-'.$start_time['d'].' '.$start_time['H'].':'.$start_time['i'].':00';
        } else {
            $this->start_time = '0000-00-00 00:00:00';
        }

        if ($form->getSubmitValue('activate_end_date_check') == 1) {
            $end_time = $form->getSubmitValue('end_time');
            $end_time['F'] = sprintf('%02d', $end_time['F']);
            $end_time['i'] = sprintf('%02d', $end_time['i']);
            $end_time['d'] = sprintf('%02d', $end_time['d']);

            $this->end_time = $end_time['Y'].'-'.$end_time['F'].'-'.$end_time['d'].' '.$end_time['H'].':'.$end_time['i'].':00';
        } else {
            $this->end_time = '0000-00-00 00:00:00';
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
            $this->random_answers = 1;
        } else {
            $this->random_answers = 0;
        }
        $this->save($type);
    }

    public function search_engine_save()
    {
        if ($_POST['index_document'] != 1) {
            return;
        }
        $course_id = api_get_course_id();

        require_once api_get_path(LIBRARY_PATH).'search/ChamiloIndexer.class.php';
        require_once api_get_path(LIBRARY_PATH).'search/IndexableChunk.class.php';
        require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';

        $specific_fields = get_specific_field_list();
        $ic_slide = new IndexableChunk();

        $all_specific_terms = '';
        foreach ($specific_fields as $specific_field) {
            if (isset($_REQUEST[$specific_field['code']])) {
                $sterms = trim($_REQUEST[$specific_field['code']]);
                if (!empty($sterms)) {
                    $all_specific_terms .= ' '.$sterms;
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
        $exercise_description = $all_specific_terms.' '.$this->description;
        $ic_slide->addValue("content", $exercise_description);

        $di = new ChamiloIndexer();
        isset($_POST['language']) ? $lang = Database::escape_string($_POST['language']) : $lang = 'english';
        $di->connectDb(null, null, $lang);
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
        if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
            $course_id = api_get_course_id();

            // actually, it consists on delete terms from db, insert new ones, create a new search engine document, and remove the old one
            // get search_did
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
            $res = Database::query($sql);

            if (Database::num_rows($res) > 0) {
                require_once(api_get_path(LIBRARY_PATH).'search/ChamiloIndexer.class.php');
                require_once(api_get_path(LIBRARY_PATH).'search/IndexableChunk.class.php');
                require_once(api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php');

                $se_ref = Database::fetch_array($res);
                $specific_fields = get_specific_field_list();
                $ic_slide = new IndexableChunk();

                $all_specific_terms = '';
                foreach ($specific_fields as $specific_field) {
                    delete_all_specific_field_value($course_id, $specific_field['id'], TOOL_QUIZ, $this->id);
                    if (isset($_REQUEST[$specific_field['code']])) {
                        $sterms = trim($_REQUEST[$specific_field['code']]);
                        $all_specific_terms .= ' '.$sterms;
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
                $exercise_description = $all_specific_terms.' '.$this->description;
                $ic_slide->addValue("content", $exercise_description);

                $di = new ChamiloIndexer();
                isset($_POST['language']) ? $lang = Database::escape_string($_POST['language']) : $lang = 'english';
                $di->connectDb(null, null, $lang);
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

    public function search_engine_delete()
    {
        // remove from search engine if enabled
        if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
            $course_id = api_get_course_id();
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level IS NULL LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $row = Database::fetch_array($res);
                require_once(api_get_path(LIBRARY_PATH).'search/ChamiloIndexer.class.php');
                $di = new ChamiloIndexer();
                $di->remove_document((int)$row['search_did']);
                unset($di);
                $tbl_quiz_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
                foreach ($this->questionList as $question_i) {
                    $sql = 'SELECT type FROM %s WHERE id=%s';
                    $sql = sprintf($sql, $tbl_quiz_question, $question_i);
                    $qres = Database::query($sql);
                    if (Database::num_rows($qres) > 0) {
                        $qrow = Database::fetch_array($qres);
                        $objQuestion = Question::getInstance($qrow['type']);
                        $objQuestion = Question::read((int)$question_i);
                        $objQuestion->search_engine_edit($this->id, false, true);
                        unset($objQuestion);
                    }
                }
            }
            $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level IS NULL LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
            Database::query($sql);

            // remove terms from db
            require_once(api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php');
            delete_all_values_for_item($course_id, TOOL_QUIZ, $this->id);
        }
    }

    public function selectExpiredTime()
    {
        return $this->expired_time;
    }

    /**
     * Cleans the student's results only for the Exercise tool (Not from the LP)
     * The LP results are NOT deleted
     * Works with exercises in sessions
     * @return int quantity of user's exercises deleted
     */
    public function clean_results()
    {
        $table_track_e_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
        $table_track_e_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $sql = "SELECT exe_id FROM $table_track_e_exercises
					   WHERE 	c_id = '".api_get_course_int_id()."' AND
								exe_exo_id = ".$this->id." AND
								orig_lp_id = 0 AND
								orig_lp_item_id = 0 AND
								session_id = ".api_get_session_id()."";

        $result = Database::query($sql);
        $exe_list = Database::store_result($result);

        //deleting TRACK_E_ATTEMPT table
        $i = 0;
        if (is_array($exe_list) && count($exe_list) > 0) {
            foreach ($exe_list as $item) {
                $sql = "DELETE FROM $table_track_e_attempt WHERE exe_id = '".$item['exe_id']."'";
                Database::query($sql);
                $i++;
            }
        }

        //delete TRACK_E_EXERCICES table
        $sql = "DELETE FROM $table_track_e_exercises
				WHERE c_id = '".api_get_course_int_id()."' AND
				        exe_exo_id = ".$this->id." AND
				        orig_lp_id = 0  AND
				        orig_lp_item_id = 0 AND
				        session_id = ".api_get_session_id();
        Database::query($sql);

        return $i;
    }

    /**
     * Gets the latest exercise order
     * @return int
     */
    public function getLastExerciseOrder()
    {
        $table = Database::get_course_table(TABLE_QUIZ_ORDER);
        $course_id = intval($this->course_id);
        $sql = "SELECT exercise_order FROM $table WHERE c_id = $course_id ORDER BY exercise_order DESC LIMIT 1";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result);

            return $row['exercise_order'];
        }

        return 0;
    }

    /**
     * Get exercise order
     * @return mixed
     */
    public function getExerciseOrder()
    {
        $table = Database::get_course_table(TABLE_QUIZ_ORDER);
        $courseId = $this->course_id;
        $sessionId = api_get_session_id();

        $sql = "SELECT exercise_order FROM $table
                WHERE exercise_id = {$this->id} AND c_id = $courseId AND session_id  = $sessionId ";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result);
            return $row['exercise_order'];
        }

        return false;
    }

    /**
     * Add the exercise to the exercise order table
     */
    public function addExerciseToOrderTable()
    {
        $table = Database::get_course_table(TABLE_QUIZ_ORDER);
        $last_order = $this->getLastExerciseOrder();
        $course_id = $this->course_id;

        if ($last_order == 0) {
            Database::insert(
                $table,
                array(
                    'exercise_id' => $this->id,
                    'exercise_order' => 1,
                    'c_id' => $course_id,
                    'session_id' => api_get_session_id(),
                )
            );
        } else {
            $current_exercise_order = $this->getExerciseOrder();
            if ($current_exercise_order == false) {
                Database::insert(
                    $table,
                    array(
                        'exercise_id' => $this->id,
                        'exercise_order' => $last_order + 1,
                        'c_id' => $course_id,
                        'session_id' => api_get_session_id(),
                    )
                );
            }
        }
    }

    public function update_exercise_list_order($new_exercise_list, $course_id, $session_id)
    {
        $table = Database::get_course_table(TABLE_QUIZ_ORDER);
        $counter = 1;
        // Drop all
        $session_id = intval($session_id);
        $course_id = intval($course_id);

        Database::query("DELETE FROM $table WHERE session_id = $session_id AND c_id = $course_id");
        //Insert all
        foreach ($new_exercise_list as $new_order_id) {
            Database::insert(
                $table,
                array(
                    'exercise_order' => $counter,
                    'session_id' => $session_id,
                    'exercise_id' => intval($new_order_id),
                    'c_id' => $course_id
                )
            );
            $counter++;
        }
    }

    function delete_exercise_order()
    {
        $table = Database::get_course_table(TABLE_QUIZ_ORDER);
        $session_id = api_get_session_id();
        $course_id = $this->course_id;
        Database::query(
            "DELETE FROM $table WHERE exercise_id = {$this->id} AND session_id = $session_id AND c_id = $course_id"
        );
    }

    function save_exercise_list_order($course_id, $session_id)
    {
        $TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST);
        $ordered_list = $this->get_exercise_list_ordered();
        $ordered_count = count($ordered_list);

        $session_id = intval($session_id);
        $course_id = intval($course_id);

        //Check if order exists and matchs the current status
        $sql = "SELECT iid FROM $TBL_EXERCICES WHERE c_id = $course_id AND active = '1' AND session_id = $session_id ORDER BY title";
        $result = Database::query($sql);
        $unordered_count = Database::num_rows($result);

        if ($unordered_count != $ordered_count) {
            $exercise_list = array();
            while ($row = Database::fetch_array($result)) {
                $exercise_list[] = $row['iid'];
            }
            $this->update_exercise_list_order($exercise_list, $course_id, $session_id);
        }
    }

    /**
     * Copies an exercise (duplicate all questions and answers)
     */
    public function copy_exercise()
    {
        $original_exercise = $this;

        $exercise_obj = new Exercise();
        $exercise_obj->setCategoriesGrouping(false);
        $exercise_obj->read($this->id);

        // force the creation of a new exercise
        $exercise_obj->updateTitle($exercise_obj->selectTitle().' - '.get_lang('Copy'));
        //Hides the new exercise
        $exercise_obj->updateStatus(false);
        $exercise_obj->updateId(0);
        $exercise_obj->save();

        $exercise_obj->save_exercise_list_order($this->course['real_id'], api_get_session_id());

        $new_exercise_id = $exercise_obj->selectId();
        if ($new_exercise_id) {

            $original_exercise->copy_exercise_categories($exercise_obj);

            $question_list = $exercise_obj->getQuestionListWithMediasUncompressed();

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
                            //$new_answer_obj->read();
                            $new_answer_obj->duplicate($new_id);
                        }
                    }
                }
            }
        }
    }

    /**
     *
     */
    public function set_autolaunch()
    {
        $table = Database::get_course_table(TABLE_QUIZ_TEST);
        $session_id = api_get_session_id();

        $course_id = $this->course_id;
        $sql = "UPDATE $table SET autolaunch = 0 WHERE c_id = $course_id AND session_id = $session_id ";
        Database::query($sql);

        $sql = "UPDATE $table SET autolaunch = 1 WHERE iid = {$this->id} ";
        Database::query($sql);
    }

    /**
     * Copy exercise categories to a new exercise
     * @params obj exercise object
     */
    function copy_exercise_categories($new_exercise_obj)
    {
        $categories = $this->get_categories_in_exercise();
        if ($categories) {
            $cat_list = array();
            foreach($categories as $cat) {
                $cat_list[$cat['category_id']] = $cat['count_questions'];
            }
            $new_exercise_obj->save_categories_in_exercise($cat_list);
        }
    }

    /**
     * Changes the exercise id
     *
     * @param - in $id - exercise id
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
    public function getStatTrackExerciseInfo(
        $lp_id = 0,
        $lp_item_id = 0,
        $lp_item_view_id = 0,
        $status = 'incomplete'
    ) {
        $track_exercises = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
        if (empty($lp_id)) {
            $lp_id = 0;
        }
        if (empty($lp_item_id)) {
            $lp_item_id = 0;
        }
        if (empty($lp_item_view_id)) {
            $lp_item_view_id = 0;
        }
        $condition = ' WHERE exe_exo_id 	= '."'".$this->id."'".' AND
					   exe_user_id 			= '."'".api_get_user_id()."'".' AND
					   c_id 		        = '."'".api_get_course_int_id()."'".' AND
					   status 				= '."'".Database::escape_string($status)."'".' AND
					   orig_lp_id 			= '."'".$lp_id."'".' AND
					   orig_lp_item_id 		= '."'".$lp_item_id."'".' AND
                       orig_lp_item_view_id = '."'".$lp_item_view_id."'".' AND
					   session_id 			= '."'".api_get_session_id()."' LIMIT 1"; //Adding limit 1 just in case

        $sql_track = 'SELECT * FROM '.$track_exercises.$condition;

        $result = Database::query($sql_track);
        $new_array = array();
        if (Database::num_rows($result) > 0) {
            $new_array = Database::fetch_array($result, 'ASSOC');
        }

        return $new_array;
    }

    /**
     * Saves a test attempt
     *
     * @param int  clock_expired_time
     * @param int  lp id
     * @param int  lp item id
     * @param int  lp item_view id
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
        $track_exercises = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
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
            $clock_expired_time = 0;
        }
        if ($this->expired_time != 0) {
            $sql_fields = "expired_time_control, ";
            $sql_fields_values = "'"."$clock_expired_time"."',";
        } else {
            $sql_fields = "";
            $sql_fields_values = "";
        }
        $questionList = array_map('intval', $questionList);
        $weight = Database::escape_string($weight);
        $sql = "INSERT INTO $track_exercises ($sql_fields exe_exo_id, exe_user_id, c_id, status, session_id, data_tracking, start_date, orig_lp_id, orig_lp_item_id, exe_weighting)
                VALUES($sql_fields_values '".$this->id."','".api_get_user_id()."','".api_get_course_int_id()."', 'incomplete','".api_get_session_id()."','".implode(',', $questionList)."', '".api_get_utc_datetime(
        )."', '$safe_lp_id', '$safe_lp_item_id', '$weight')";

        Database::query($sql);
        $id = Database::insert_id();

        return $id;
    }

    /**
     * @param int $question_id
     * @param int $questionNum
     * @param array $questions_in_media
     * @param array $remindList
     * @return string
     */
    public function show_button($question_id, $questionNum, $questions_in_media = array(), $remindList = array())
    {
        global $origin, $safe_lp_id, $safe_lp_item_id, $safe_lp_item_view_id;
        $nbrQuestions = $this->getCountCompressedQuestionList();

        $all_button = $html = $label = '';
        $hotspot_get = isset($_POST['hotspot']) ? Security::remove_XSS($_POST['hotspot']) : null;

        if ($this->selectFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT && $this->type == ONE_PER_PAGE) {
            $html .= '<a href="exercise_submit_modal.php?learnpath_id='.$safe_lp_id.'&learnpath_item_id='.$safe_lp_item_id.'&learnpath_item_view_id='.$safe_lp_item_view_id.'&origin='.$origin.'&hotspot='.$hotspot_get.'&nbrQuestions='.$nbrQuestions.'&num='.$questionNum.'&exerciseType='.$this->type.'&exerciseId='.$this->id.'&placeValuesBeforeTB_=savedValues&TB_iframe=true&height=480&width=640&modal=true" title="" class="thickbox btn">';
            if ($questionNum == count($this->questionList)) {
                $html .= get_lang('EndTest').'</a>';
            } else {
                $html .= get_lang('ContinueTest').'</a>';
            }
            $html .= '<br />';
        } else {
            // User
            $showEndWarning = 0;
            if (api_is_allowed_to_session_edit()) {
                if ($this->type == ALL_ON_ONE_PAGE || $nbrQuestions == $questionNum) {
                    if ($this->review_answers) {
                        $label = get_lang('EndTest');
                        $class = 'btn btn-warning';
                    } else {
                        $label = get_lang('EndTest');
                        $class = 'btn btn-warning';
                    }
                    $showEndWarning = 1;
                } else {
                    $label = get_lang('NextQuestion');
                    $class = 'btn btn-primary';
                }

                if ($this->type == ONE_PER_PAGE) {
                    if ($questionNum != 1) {
                        $prev_question = $questionNum - 2;
                        $all_button .= '<a href="javascript://" class="btn" onclick="previous_question_and_save('.$prev_question.', '.$question_id.' ); ">'.get_lang('PreviousQuestion').'</a>';
                    }

                    //Next question
                    if (isset($questions_in_media) && !empty($questions_in_media) && is_array($questions_in_media)) {
                        $questions_in_media = "['".implode("','", $questions_in_media)."']";
                        $all_button .= '&nbsp;<a href="javascript://" class="'.$class.'" onclick="save_question_list('.$questions_in_media.', '.$showEndWarning.'); ">'.$label.'</a>';
                    } else {
                        $all_button .= '&nbsp;<a href="javascript://" class="'.$class.'" onclick="save_now('.$question_id.', null, true, '.$showEndWarning.'); ">'.$label.'</a>';
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
                    $all_button = '&nbsp;<a href="javascript://" class="'.$class.'" onclick="validate_all(); ">'.$all_label.'</a>';
                    $all_button .= '&nbsp;<span id="save_all_reponse"></span>';
                    $html .= $all_button;
                }
            }
        }

        return $html;
    }

    /**
     * So the time control will work
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
                        },
                    },
                    close: function() {
                        send_form();
                    }
                });

                $('#clock_warning').dialog('open');

                $('#counter_to_redirect').epiclock({
                    mode: $.epiclock.modes.countdown,
                    offset: { seconds: 5 },
                    format: 's'
                }).bind('timer', function () {
                    send_form();
                });

            }

            function send_form() {
                if ($('#exercise_form').length) {
                    $('#exercise_form').submit();
                } else {
                    // In reminder.
                    final_submit();
                }
            }

            function onExpiredTimeExercise() {
                $('#wrapper-clock').hide();
                $('#exercise_form').hide();
                $('#expired-message-id').show();

                // Fixes bug #5263.
                $('#num_current_id').attr('value', '".$this->selectNbrQuestions()."');
                open_clock_warning();
            }

			$(document).ready(function() {

				var current_time = new Date().getTime();
				// Time in seconds. When using minutes there are some seconds lost.
                var time_left    = parseInt(".$time_left.");
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

        return "<script type=\"text/javascript\" src=\"../plugin/hotspot/JavaScriptFlashGateway.js\"></script>
                    <script src=\"../plugin/hotspot/hotspot.js\" type=\"text/javascript\"></script>
                    <script language=\"JavaScript\" type=\"text/javascript\">
                    <!--
                    // -----------------------------------------------------------------------------
                    // Globals
                    // Major version of Flash required
                    var requiredMajorVersion = 7;
                    // Minor version of Flash required
                    var requiredMinorVersion = 0;
                    // Minor version of Flash required
                    var requiredRevision = 0;
                    // the version of javascript supported
                    var jsVersion = 1.0;
                    // -----------------------------------------------------------------------------
                    // -->
                    </script>
                    <script language=\"VBScript\" type=\"text/vbscript\">
                    <!-- // Visual basic helper required to detect Flash Player ActiveX control version information
                    Function VBGetSwfVer(i)
                      on error resume next
                      Dim swControl, swVersion
                      swVersion = 0

                      set swControl = CreateObject(\"ShockwaveFlash.ShockwaveFlash.\" + CStr(i))
                      if (IsObject(swControl)) then
                        swVersion = swControl.GetVariable(\"\$version\")
                      end if
                      VBGetSwfVer = swVersion
                    End Function
                    // -->
                    </script>

                    <script language=\"JavaScript1.1\" type=\"text/javascript\">
                    <!-- // Detect Client Browser type
                    var isIE  = (navigator.appVersion.indexOf(\"MSIE\") != -1) ? true : false;
                    var isWin = (navigator.appVersion.toLowerCase().indexOf(\"win\") != -1) ? true : false;
                    var isOpera = (navigator.userAgent.indexOf(\"Opera\") != -1) ? true : false;
                    jsVersion = 1.1;
                    // JavaScript helper required to detect Flash Player PlugIn version information
                    function JSGetSwfVer(i){
                        // NS/Opera version >= 3 check for Flash plugin in plugin array
                        if (navigator.plugins != null && navigator.plugins.length > 0) {
                            if (navigator.plugins[\"Shockwave Flash 2.0\"] || navigator.plugins[\"Shockwave Flash\"]) {
                                var swVer2 = navigator.plugins[\"Shockwave Flash 2.0\"] ? \" 2.0\" : \"\";
                                var flashDescription = navigator.plugins[\"Shockwave Flash\" + swVer2].description;
                                descArray = flashDescription.split(\" \");
                                tempArrayMajor = descArray[2].split(\".\");
                                versionMajor = tempArrayMajor[0];
                                versionMinor = tempArrayMajor[1];
                                if ( descArray[3] != \"\" ) {
                                    tempArrayMinor = descArray[3].split(\"r\");
                                } else {
                                    tempArrayMinor = descArray[4].split(\"r\");
                                }
                                versionRevision = tempArrayMinor[1] > 0 ? tempArrayMinor[1] : 0;
                                flashVer = versionMajor + \".\" + versionMinor + \".\" + versionRevision;
                            } else {
                                flashVer = -1;
                            }
                        }
                        // MSN/WebTV 2.6 supports Flash 4
                        else if (navigator.userAgent.toLowerCase().indexOf(\"webtv/2.6\") != -1) flashVer = 4;
                        // WebTV 2.5 supports Flash 3
                        else if (navigator.userAgent.toLowerCase().indexOf(\"webtv/2.5\") != -1) flashVer = 3;
                        // older WebTV supports Flash 2
                        else if (navigator.userAgent.toLowerCase().indexOf(\"webtv\") != -1) flashVer = 2;
                        // Can't detect in all other cases
                        else {

                            flashVer = -1;
                        }
                        return flashVer;
                    }
                    // When called with reqMajorVer, reqMinorVer, reqRevision returns true if that version or greater is available
                    function DetectFlashVer(reqMajorVer, reqMinorVer, reqRevision)
                    {
                        reqVer = parseFloat(reqMajorVer + \".\" + reqRevision);
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
                                    tempArray         = versionStr.split(\" \");
                                    tempString        = tempArray[1];
                                    versionArray      = tempString .split(\",\");
                                } else {
                                    versionArray      = versionStr.split(\".\");
                                }
                                versionMajor      = versionArray[0];
                                versionMinor      = versionArray[1];
                                versionRevision   = versionArray[2];

                                versionString     = versionMajor + \".\" + versionRevision;   // 7.0r24 == 7.24
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
                    </script>";
    }

    /**
     * This function was originally found in the exercise_show.php
     * @param   int     exe id
     * @param   int     question id
     * @param   int     the choice the user selected
     * @param   array   the hotspot coordinates $hotspot[$question_id] = coordinates
     * @param   string  function is called from 'exercise_show' or 'exercise_result'
     * @param   bool    save results in the DB or just show the reponse
     * @param   bool    gets information from DB or from the current selection
     * @param   bool    show results or not
     * @todo    reduce parameters of this function
     * @return  string  html code
     */
    public function manageAnswers(
        $exeId,
        $questionId,
        $choice,
        $from = 'exercise_show',
        $exerciseResultCoordinates = array(),
        $saved_results = true,
        $from_database = false,
        $show_result = true,
        $hotspot_delineation_result = array(),
        $updateResults = false
    ) {
        global $debug;
        global $learnpath_id, $learnpath_item_id; //needed in order to use in the exercise_attempt() for the time
        require_once api_get_path(LIBRARY_PATH).'geometry.lib.php';

        $feedback_type = $this->feedback_type;

        $propagate_neg = $this->selectPropagateNeg();

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
        $arrans = null;

        $questionId = intval($questionId);
        $exeId = intval($exeId);
        $TBL_TRACK_ATTEMPT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $table_ans = Database::get_course_table(TABLE_QUIZ_ANSWER);

        // Creates a temporary Question object
        $course_id = api_get_course_int_id();
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
        //Extra information of the question
        if (!empty($extra)) {
            $extra = explode(':', $extra);
            if ($debug) {
                error_log(print_r($extra, 1));
            }
            //Fixes problems with negatives values using intval
            $true_score = intval($extra[0]);
            $false_score = intval($extra[1]);
            $doubt_score = intval($extra[2]);
        }

        $totalWeighting = 0;
        $totalScore = 0;

        // Destruction of the Question object
        unset($objQuestionTmp);

        // Construction of the Answer object
        $objAnswerTmp = new Answer($questionId, null, $this);
        $nbrAnswers = $objAnswerTmp->selectNbrAnswers();

        if ($debug) {
            error_log('Count of answers: '.$nbrAnswers);
            error_log('$answerType: '.$answerType);
        }

        if ($answerType == FREE_ANSWER || $answerType == ORAL_EXPRESSION) {
            $nbrAnswers = 1;
        }

        $nano = null;

        if ($answerType == ORAL_EXPRESSION) {
            $exe_info = get_exercise_results_by_attempt($exeId);
            $exe_info = $exe_info[$exeId];

            $params = array();
            $params['course_id'] = api_get_course_int_id();
            $params['session_id'] = api_get_session_id();
            $params['user_id'] = isset($exe_info['exe_user_id']) ? $exe_info['exe_user_id'] : api_get_user_id();
            $params['exercise_id'] = isset($exe_info['exe_exo_id']) ? $exe_info['exe_exo_id'] : $this->id;
            $params['question_id'] = $questionId;
            $params['exe_id'] = isset($exe_info['exe_id']) ? $exe_info['exe_id'] : $exeId;

            $nano = new Nanogong($params);

            //probably this attempt came in an exercise all question by page
            if ($feedback_type == 0) {
                $nano->replace_with_real_exe($exeId);
            }
        }

        $user_answer = '';

        // Get answer list for matching
        $sql_answer = 'SELECT iid, answer FROM '.$table_ans.' WHERE question_id = "'.$questionId.'" ';
        $res_answer = Database::query($sql_answer);

        $answer_matching = array();
        $answer_list = array();
        while ($real_answer = Database::fetch_array($res_answer)) {
            $answer_matching[$real_answer['iid']] = $real_answer['answer'];
            $answer_list[] = $real_answer['iid'];
        }

        if ($answerType == FREE_ANSWER || $answerType == ORAL_EXPRESSION) {
            $nbrAnswers = 1;
            $answer_list[] = 1;
        }

        $real_answers = array();
        $quiz_question_options = Question::readQuestionOption($questionId, $course_id);

        $organs_at_risk_hit = 0;
        $questionScore = 0;

        if ($debug) {
            error_log('<<-- Start answer loop -->');
        }

        $answer_correct_array = array();

        //for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
        $counter = 1;

        foreach ($answer_list as $answerId) {
            /** @var \Answer $objAnswerTmp */
            $answer = $objAnswerTmp->selectAnswer($answerId);
            $answerComment = $objAnswerTmp->selectComment($answerId);
            $answerCorrect = $objAnswerTmp->isCorrect($answerId);

            $answerWeighting = (float)$objAnswerTmp->selectWeighting($answerId);

            //$numAnswer = $objAnswerTmp->selectAutoId($answerId);
            $numAnswer = $answerId;

            $answer_correct_array[$answerId] = (bool)$answerCorrect;

            if ($debug) {
                error_log("answer auto id: $numAnswer ");
                error_log("answer correct: $answerCorrect ");
            }

            //Delineation
            $delineation_cord = $objAnswerTmp->selectHotspotCoordinates(1);
            $answer_delineation_destination = $objAnswerTmp->selectDestination(1);

            switch ($answerType) {
                // for unique answer
                case UNIQUE_ANSWER:
                case UNIQUE_ANSWER_IMAGE:
                case UNIQUE_ANSWER_NO_OPTION:
                    if ($from_database) {
                        $queryans = "SELECT answer FROM ".$TBL_TRACK_ATTEMPT." WHERE exe_id = '".$exeId."' AND question_id= '".$questionId."'";
                        $resultans = Database::query($queryans);
                        $choice = Database::result($resultans, 0, "answer");

                        $studentChoice = ($choice == $numAnswer) ? 1 : 0;
                        if ($studentChoice) {
                            $questionScore += $answerWeighting;
                            $totalScore += $answerWeighting;
                        }
                    } else {
                        $studentChoice = ($choice == $numAnswer) ? 1 : 0;
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
                        $queryans = "SELECT answer FROM ".$TBL_TRACK_ATTEMPT." WHERE exe_id = ".$exeId." AND question_id = ".$questionId;
                        $resultans = Database::query($queryans);
                        while ($row = Database::fetch_array($resultans)) {
                            $ind = $row['answer'];
                            $result = explode(':', $ind);
                            $my_answer_id = $result[0];
                            $option = $result[1];
                            $choice[$my_answer_id] = $option;
                        }
                        $studentChoice = $choice[$numAnswer];
                    } else {
                        $studentChoice = $choice[$numAnswer];
                    }

                    if (!empty($studentChoice)) {
                        if ($studentChoice == $answerCorrect) {
                            $questionScore += $true_score;
                        } else {
                            if ($quiz_question_options[$studentChoice]['name'] != "Don't know") {
                                $questionScore += $false_score;
                            } else {
                                $questionScore += $doubt_score;
                            }
                        }
                    } else {
                        //if no result then the user just hit don't know
                        $studentChoice = 3;
                        $questionScore += $doubt_score;
                    }
                    $totalScore = $questionScore;
                    break;
                case MULTIPLE_ANSWER: //2
                    if ($from_database) {
                        $choice = array();
                        $queryans = "SELECT answer FROM ".$TBL_TRACK_ATTEMPT." WHERE exe_id = '".$exeId."' AND question_id= '".$questionId."'";
                        $resultans = Database::query($queryans);
                        while ($row = Database::fetch_array($resultans)) {
                            $ind = $row['answer'];
                            $choice[$ind] = 1;
                        }

                        $studentChoice = isset($choice[$numAnswer]) ? $choice[$numAnswer] : null;
                        $real_answers[$answerId] = (bool)$studentChoice;

                        if ($studentChoice) {
                            $questionScore += $answerWeighting;
                        }
                    } else {
                        $studentChoice = isset($choice[$numAnswer]) ? $choice[$numAnswer] : null;
                        $real_answers[$answerId] = (bool)$studentChoice;

                        if (isset($studentChoice)) {
                            $questionScore += $answerWeighting;
                        }
                    }
                    $totalScore += $answerWeighting;

                    if ($debug) {
                        error_log("studentChoice: $studentChoice");
                    }
                    break;
                case GLOBAL_MULTIPLE_ANSWER :
                    if ($from_database) {
                        $choice = array();
                        $queryans = "SELECT answer FROM $TBL_TRACK_ATTEMPT WHERE exe_id = '".$exeId."' AND question_id= '".$questionId."'";
                        $resultans = Database::query($queryans);
                        while ($row = Database::fetch_array($resultans)) {
                            $ind = $row['answer'];
                            $choice[$ind] = 1;
                        }
                        $studentChoice = $choice[$numAnswer];
                        $real_answers[$answerId] = (bool)$studentChoice;
                        if ($studentChoice) {
                            $questionScore += $answerWeighting;
                        }
                    } else {
                        $studentChoice = $choice[$numAnswer];
                        if (isset($studentChoice)) {
                            $questionScore += $answerWeighting;
                        }
                        $real_answers[$answerId] = (bool)$studentChoice;
                    }
                    $totalScore += $answerWeighting;
                    if ($debug) {
                        error_log("studentChoice: $studentChoice");
                    }
                    break;
                case MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE:
                    if ($from_database) {
                        $queryans = "SELECT answer FROM ".$TBL_TRACK_ATTEMPT." where exe_id = ".$exeId." AND question_id= ".$questionId;
                        $resultans = Database::query($queryans);
                        while ($row = Database::fetch_array($resultans)) {
                            $ind = $row['answer'];
                            $result = explode(':', $ind);
                            $my_answer_id = $result[0];
                            $option = $result[1];
                            $choice[$my_answer_id] = $option;
                        }
                        //$numAnswer = $objAnswerTmp->selectAutoId($answerId);
                        $numAnswer = $answerId;
                        $studentChoice = $choice[$numAnswer];

                        if ($answerCorrect == $studentChoice) {
                            //$answerCorrect = 1;
                            $real_answers[$answerId] = true;
                        } else {
                            //$answerCorrect = 0;
                            $real_answers[$answerId] = false;
                        }
                    } else {
                        $studentChoice = $choice[$numAnswer];
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
                        $queryans = "SELECT answer FROM ".$TBL_TRACK_ATTEMPT." where exe_id = '".$exeId."' and question_id= '".$questionId."'";
                        $resultans = Database::query($queryans);
                        while ($row = Database::fetch_array($resultans)) {
                            $choice[$row['answer']] = 1;
                        }
                        $studentChoice = $choice[$answerId];
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
                        $studentChoice = $choice[$numAnswer];
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
                // for fill in the blanks
                case FILL_IN_BLANKS :
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
                    if (isset($is_set_switchable[1]) && $is_set_switchable[1] == 1) {
                        $switchable_answer_set = true;
                    }
                    $answer = '';
                    for ($k = 0; $k < $last; $k++) {
                        $answer .= $pre_array[$k];
                    }
                    // splits weightings that are joined with a comma
                    $answerWeighting = explode(',', $is_set_switchable[0]);

                    // we save the answer because it will be modified
                    //$temp = $answer;
                    $temp = $answer;

                    $answer = '';
                    $j = 0;
                    //initialise answer tags
                    $user_tags = $correct_tags = $real_text = array();
                    // the loop will stop at the end of the text
                    while (1) {
                        // quits the loop if there are no more blanks (detect '[')
                        if (($pos = api_strpos($temp, '[')) === false) {
                            // adds the end of the text
                            $answer = $temp;
                            /* // Deprecated code
                              // TeX parsing - replacement of texcode tags
                              $answer = str_replace("{texcode}", $texstring, $answer);
                             */
                            $real_text[] = $answer;
                            break; //no more "blanks", quit the loop
                        }
                        // adds the piece of text that is before the blank
                        //and ends with '[' into a general storage array
                        $real_text[] = api_substr($temp, 0, $pos + 1);
                        $answer .= api_substr($temp, 0, $pos + 1);
                        //take the string remaining (after the last "[" we found)
                        $temp = api_substr($temp, $pos + 1);
                        // quit the loop if there are no more blanks, and update $pos to the position of next ']'
                        if (($pos = api_strpos($temp, ']')) === false) {
                            // adds the end of the text
                            $answer .= $temp;
                            break;
                        }

                        if ($from_database) {
                            $queryfill = "SELECT answer FROM ".$TBL_TRACK_ATTEMPT." WHERE exe_id = ".$exeId." AND question_id= ".Database::escape_string($questionId);
                            $resfill = Database::query($queryfill);
                            if (Database::num_rows($resfill)) {
                                $str = Database::result($resfill, 0, 'answer');
                                api_preg_match_all('#\[([^[]*)\]#', $str, $arr);
                                $str = str_replace('\r\n', '', $str);
                                $choice = $arr[1];
                                $tmp = api_strrpos($choice[$j], ' / ');
                                $choice[$j] = api_substr($choice[$j], 0, $tmp);
                                $choice[$j] = trim($choice[$j]);

                                //Needed to let characters ' and " to work as part of an answer
                                $choice[$j] = stripslashes($choice[$j]);
                            }
                        } else {
                            $choice[$j] = trim($choice[$j]);
                        }

                        //No idea why we api_strtolower user reponses
                        //$user_tags[] = api_strtolower($choice[$j]);
                        $user_tags[] = $choice[$j];
                        //put the contents of the [] answer tag into correct_tags[]
                        //$correct_tags[] = api_strtolower(api_substr($temp, 0, $pos));
                        $correct_tags[] = api_substr($temp, 0, $pos);
                        $j++;
                        $temp = api_substr($temp, $pos + 1);
                    }
                    $answer = '';
                    $real_correct_tags = $correct_tags;
                    $chosen_list = array();

                    for ($i = 0; $i < count($real_correct_tags); $i++) {
                        if ($i == 0) {
                            $answer .= $real_text[0];
                        }
                        if (!$switchable_answer_set) {
                            //needed to parse ' and " characters
                            $user_tags[$i] = stripslashes($user_tags[$i]);
                            if ($correct_tags[$i] == $user_tags[$i]) {
                                // gives the related weighting to the student
                                $questionScore += $answerWeighting[$i];
                                // increments total score
                                $totalScore += $answerWeighting[$i];
                                // adds the word in green at the end of the string
                                $answer .= $correct_tags[$i];
                            } // else if the word entered by the student IS NOT the same as the one defined by the professor
                            elseif (!empty($user_tags[$i])) {
                                // adds the word in red at the end of the string, and strikes it
                                $answer .= '<font color="red"><s>'.$user_tags[$i].'</s></font>';
                            } else {
                                // adds a tabulation if no word has been typed by the student
                                $answer .= '&nbsp;&nbsp;&nbsp;';
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
                            } elseif (!empty($user_tags[$i])) {
                                // else if the word entered by the student IS NOT the same as the one defined by the professor
                                // adds the word in red at the end of the string, and strikes it
                                $answer .= '<font color="red"><s>'.$user_tags[$i].'</s></font>';
                            } else {
                                // adds a tabulation if no word has been typed by the student
                                $answer .= '&nbsp;&nbsp;&nbsp;';
                            }
                        }
                        // adds the correct word, followed by ] to close the blank
                        $answer .= ' / <font color="green"><b>'.$real_correct_tags[$i].'</b></font>]';
                        if (isset($real_text[$i + 1])) {
                            $answer .= $real_text[$i + 1];
                        }
                    }
                    break;
                // for free answer
                case FREE_ANSWER:
                    if ($from_database) {
                        $query = "SELECT answer, marks FROM ".$TBL_TRACK_ATTEMPT." WHERE exe_id = '".$exeId."' AND question_id= '".$questionId."'";
                        $resq = Database::query($query);
                        $questionScore = 0;
                        $choice = null;
                        if ($resq) {
                            $row = Database::fetch_array($resq);
                            $choice = $row['answer'];
                            $questionScore = $row['marks'];
                            $choice = str_replace('\r\n', '', $choice);
                            $choice = stripslashes($choice);
                        }

                        if ($questionScore == -1) {
                            $totalScore += 0;
                        } else {
                            $totalScore += $questionScore;
                        }

                        if ($questionScore == '') {
                            $questionScore = 0;
                        }

                        $arrques = $questionName;
                        $arrans = $choice;
                    } else {
                        $studentChoice = $choice;
                        if ($studentChoice) {
                            //Fixing negative puntation see #2193
                            $questionScore = 0;
                            $totalScore += 0;
                        }
                    }
                    break;
                case ORAL_EXPRESSION :
                    if ($from_database) {
                        $query = "SELECT answer, marks FROM ".$TBL_TRACK_ATTEMPT." WHERE exe_id = '".$exeId."' AND question_id= '".$questionId."'";
                        $resq = Database::query($query);
                        $choice = Database::result($resq, 0, 'answer');
                        $choice = str_replace('\r\n', '', $choice);
                        $choice = stripslashes($choice);
                        $questionScore = Database::result($resq, 0, "marks");
                        if ($questionScore == -1) {
                            $totalScore += 0;
                        } else {
                            $totalScore += $questionScore;
                        }
                        $arrques = $questionName;
                        $arrans = $choice;
                    } else {
                        $studentChoice = $choice;
                        if ($studentChoice) {
                            //Fixing negative puntation see #2193
                            $questionScore = 0;
                            $totalScore += 0;
                        }
                    }
                    break;
                // for matching
                case DRAGGABLE:
                case MATCHING :
                    if ($from_database) {
                        $sql_answer = 'SELECT iid, answer FROM '.$table_ans.' WHERE c_id = '.$course_id.' AND question_id="'.$questionId.'" AND correct=0';
                        $res_answer = Database::query($sql_answer);
                        // getting the real answer
                        $real_list = array();
                        while ($real_answer = Database::fetch_array($res_answer)) {
                            $real_list[$real_answer['iid']] = $real_answer['answer'];
                        }

                        $sql_select_answer = "SELECT iid, answer, correct FROM $table_ans
                                              WHERE c_id = $course_id AND question_id = '$questionId' AND correct <> 0
                                              ORDER BY iid";
                        $res_answers = Database::query($sql_select_answer);

                        $questionScore = 0;

                        while ($a_answers = Database::fetch_array($res_answers)) {
                            $i_answer_id = $a_answers['iid']; //3
                            $s_answer_label = $a_answers['answer'];  // your daddy - your mother
                            $i_answer_correct_answer = $a_answers['correct']; //1 - 2
                            $answerIdCorrect = $a_answers['correct'];

                            $sql_user_answer = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                                WHERE exe_id = '$exeId' AND
                                                      question_id = '$questionId' AND
                                                      position = '$i_answer_id'";

                            $res_user_answer = Database::query($sql_user_answer);

                            if (Database::num_rows($res_user_answer)) {
                                //  Rich - good looking
                                $result = Database::fetch_array($res_user_answer, 'ASSOC');
                                $s_user_answer = $result['answer'];
                            } else {
                                $s_user_answer = 0;
                            }

                            $i_answerWeighting = $objAnswerTmp->selectWeighting($i_answer_id);
                            if ($answerType ==  MATCHING) {
                                $i_answer_correct_answer = $objAnswerTmp->getAnswerIdFromList($i_answer_correct_answer);
                            }

                            $user_answer = '';
                            if (!empty($s_user_answer)) {
                                if ($s_user_answer == $i_answer_correct_answer) {
                                    $questionScore += $i_answerWeighting;
                                    $totalScore += $i_answerWeighting;
                                    if ($answerType == DRAGGABLE) {
                                        $user_answer = Display::label(get_lang('Correct'), 'success');
                                    } else {
                                        $user_answer = '<span>'.$real_list[$answerIdCorrect].'</span>';
                                    }
                                } else {
                                    if ($answerType == DRAGGABLE) {
                                        $user_answer = Display::label(get_lang('NotCorrect'), 'important');
                                    } else {
                                        $user_answer = '<span style="color: #FF0000; text-decoration: line-through;">'.$real_list[$s_user_answer].'</span>';
                                    }
                                }
                            } else {
                                if ($answerType == DRAGGABLE) {
                                    $user_answer = Display::label(get_lang('Incorrect'), 'important');
                                }
                            }

                            if ($show_result) {
                                echo '<tr>';
                                echo '<td>'.$s_answer_label.'</td>';
                                echo '<td>'.$user_answer.'';
                                if ($answerType == MATCHING) {
                                    echo '<b><span style="color: #008000;"> '.$real_list[$answerIdCorrect].'</span></b>';
                                }
                                echo '</td>';
                                echo '</tr>';
                            }
                        }
                        break(2); //break the switch and the "for" condition
                    } else {
                        $numAnswer = $answerId;
                        if ($answerCorrect) {
                            $matchingKey = $choice[$numAnswer];

                            if ($answerType == DRAGGABLE) {
                                $matchingKey = $numAnswer;
                            }

                            if ($answerType == MATCHING) {
                                $answerCorrect = $objAnswerTmp->getAnswerIdFromList($answerCorrect);
                                $matchingKey = $numAnswer;
                            }

                            if ($answerCorrect == $choice[$numAnswer]) {
                                $questionScore += $answerWeighting;
                                $totalScore += $answerWeighting;
                                $user_answer = '<span>'.$answer_matching[$matchingKey].'</span>';
                            } else {
                                if ($choice[$numAnswer]) {
                                    $user_answer = '<span style="color: #FF0000; text-decoration: line-through;">'.$answer_matching[$matchingKey].'</span>';
                                }
                            }
                            $matching[$numAnswer] = $choice[$numAnswer];
                        }
                        break;
                    }
                // for hotspot with no order
                case HOT_SPOT :
                    if ($from_database) {
                        if ($show_result) {
                            $TBL_TRACK_HOTSPOT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
                            $query = "SELECT hotspot_correct
                                     FROM ".$TBL_TRACK_HOTSPOT."
                                     WHERE  hotspot_exe_id = '".$exeId."' and
                                            hotspot_question_id= '".$questionId."' AND
                                            hotspot_answer_id='".Database::escape_string($answerId)."'";
                            $resq = Database::query($query);
                            $studentChoice = Database::result($resq, 0, "hotspot_correct");

                            if ($studentChoice == 1) {
                                $questionScore += $answerWeighting;
                                $totalScore += $answerWeighting;
                            }
                        }
                    } else {
                        if (isset($choice[$answerId])) {
                            $studentChoice = $choice[$answerId];
                        } else {
                            $studentChoice = false;
                        }
                        if ($studentChoice) {
                            $questionScore += $answerWeighting;
                            $totalScore += $answerWeighting;
                        }
                    }
                    break;
                // @todo never added to chamilo
                //for hotspot with fixed order
                case HOT_SPOT_ORDER :
                    $studentChoice = $choice['order'][$answerId];
                    if ($studentChoice == $answerId) {
                        $questionScore += $answerWeighting;
                        $totalScore += $answerWeighting;
                        $studentChoice = true;
                    } else {
                        $studentChoice = false;
                    }
                    break;
                // for hotspot with delineation
                case HOT_SPOT_DELINEATION :
                    if ($from_database) {
                        // getting the user answer
                        $TBL_TRACK_HOTSPOT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
                        $query = "SELECT hotspot_correct, hotspot_coordinate
                                  FROM ".$TBL_TRACK_HOTSPOT."
                                  WHERE hotspot_exe_id = '".$exeId."' AND
                                        hotspot_question_id= '".$questionId."' AND
                                        hotspot_answer_id='1'"; //by default we take 1 because it's a delineation
                        $resq = Database::query($query);
                        $row = Database::fetch_array($resq, 'ASSOC');

                        $choice = $row['hotspot_correct'];
                        $user_answer = $row['hotspot_coordinate'];

                        // THIS is very important otherwise the poly_compile will throw an error!!
                        // round-up the coordinates
                        $coords = explode('/', $user_answer);
                        $user_array = '';
                        foreach ($coords as $coord) {
                            list($x, $y) = explode(';', $coord);
                            $user_array .= round($x).';'.round($y).'/';
                        }
                        $user_array = substr($user_array, 0, -1);
                    } else {
                        if ($studentChoice) {
                            $newquestionList[] = $questionId;
                        }

                        if ($answerId === 1) {
                            $studentChoice = $choice[$answerId];
                            $questionScore += $answerWeighting;

                            if ($hotspot_delineation_result[1] == 1) {
                                $totalScore += $answerWeighting; //adding the total
                            }
                        }
                    }
                    $_SESSION['hotspot_coord'][1] = $delineation_cord;
                    $_SESSION['hotspot_dest'][1] = $answer_delineation_destination;
                    break;
            } // end switch Answertype

            global $origin;

            if ($show_result) {

                if ($debug) {
                    error_log('show result '.$show_result);
                }

                if ($from == 'exercise_result') {
                    if ($debug) {
                        error_log('Showing questions $from '.$from);
                    }

                    //display answers (if not matching type, or if the answer is correct)
                    if (!in_array($answerType, array(DRAGGABLE, MATCHING))|| $answerCorrect) {
                        if (in_array(
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
                                0
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
                                0
                            );

                        } elseif ($answerType == MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE) {
                            ExerciseShowFunctions::display_multiple_answer_combination_true_false(
                                $feedback_type,
                                $answerType,
                                $studentChoice,
                                $answer,
                                $answerComment,
                                $answerCorrect,
                                0,
                                0,
                                0
                            );
                        } elseif ($answerType == FILL_IN_BLANKS) {
                            ExerciseShowFunctions::display_fill_in_blanks_answer($feedback_type, $answer, 0, 0);
                        } elseif ($answerType == FREE_ANSWER) {
                            ExerciseShowFunctions::display_free_answer(
                                $feedback_type,
                                $choice,
                                $exeId,
                                $questionId,
                                $questionScore
                            );
                        } elseif ($answerType == ORAL_EXPRESSION) {
                            // to store the details of open questions in an array to be used in mail
                            ExerciseShowFunctions::display_oral_expression_answer($choice, 0, 0, $nano);

                        } elseif ($answerType == HOT_SPOT) {
                            ExerciseShowFunctions::display_hotspot_answer($feedback_type, $counter, $answer, $studentChoice, $answerComment);
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

                            // Round-up the coordinates
                            $coords = explode('/', $user_answer);
                            $user_array = '';
                            foreach ($coords as $coord) {
                                list($x, $y) = explode(';', $coord);
                                $user_array .= round($x).';'.round($y).'/';
                            }
                            $user_array = substr($user_array, 0, -1);

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

                                //$overlap = round(polygons_overlap($poly_answer,$poly_user)); //this is an area in pixels
                                if ($debug > 0) {
                                    error_log(__LINE__.' - Polygons results are '.print_r($poly_results, 1), 0);
                                }

                                if ($overlap < 1) {
                                    //shortcut to avoid complicated calculations
                                    $final_overlap = 0;
                                    $final_missing = 100;
                                    $final_excess = 100;
                                } else {
                                    // the final overlap is the percentage of the initial polygon that is overlapped by the user's polygon
                                    $final_overlap = round(((float)$overlap / (float)$poly_answer_area) * 100);
                                    if ($debug > 1) {
                                        error_log(__LINE__.' - Final overlap is '.$final_overlap, 0);
                                    }
                                    // the final missing area is the percentage of the initial polygon that is not overlapped by the user's polygon
                                    $final_missing = 100 - $final_overlap;
                                    if ($debug > 1) {
                                        error_log(__LINE__.' - Final missing is '.$final_missing, 0);
                                    }
                                    // the final excess area is the percentage of the initial polygon's size that is covered by the user's polygon outside of the initial polygon
                                    $final_excess = round(
                                        (((float)$poly_user_area - (float)$overlap) / (float)$poly_answer_area) * 100
                                    );
                                    if ($debug > 1) {
                                        error_log(__LINE__.' - Final excess is '.$final_excess, 0);
                                    }
                                }

                                //checking the destination parameters parsing the "@@"
                                $destination_items = explode('@@', $answerDestination);
                                $threadhold_total = $destination_items[0];
                                $threadhold_items = explode(';', $threadhold_total);
                                $threadhold1 = $threadhold_items[0]; // overlap
                                $threadhold2 = $threadhold_items[1]; // excess
                                $threadhold3 = $threadhold_items[2]; //missing
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
                                            error_log(__LINE__.' - answerId is of type noerror', 0);
                                        }
                                        //type no error shouldn't be treated
                                        $next = 1;
                                        continue;
                                    }
                                    if ($debug > 0) {
                                        error_log(__LINE__.' - answerId is >1 so we\'re probably in OAR', 0);
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
                                    $overlap = poly_touch($poly_user_compiled, $poly_answer_compiled, $max_coord);

                                    if ($overlap == false) {
                                        //all good, no overlap
                                        $next = 1;
                                        continue;
                                    } else {
                                        if ($debug > 0) {
                                            error_log(__LINE__.' - Overlap is '.$overlap.': OAR hit', 0);
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
                                        $url_hotspot = $destination_items[4];
                                    }
                                }
                            } else { // the first delineation feedback
                                if ($debug > 0) {
                                    error_log(__LINE__.' first', 0);
                                }
                            }
                        } elseif ($answerType == MATCHING) {
                            //if ($origin != 'learnpath') {
                                echo '<tr>';
                                echo '<td>'.$answer_matching[$answerId].'</td><td>'.$user_answer.' / <b><span style="color: #008000;">'.
                                    $answer_matching[$answerCorrect]
                                .'</span></b></td>';
                                echo '</tr>';
                            //}
                        }
                    }
                } else {
                    if ($debug) {
                        error_log('Showing questions $from '.$from);
                    }

                    switch ($answerType) {
                        case UNIQUE_ANSWER :
                        case UNIQUE_ANSWER_IMAGE :
                        case UNIQUE_ANSWER_NO_OPTION:
                        case MULTIPLE_ANSWER :
                        case GLOBAL_MULTIPLE_ANSWER :
                        case MULTIPLE_ANSWER_COMBINATION :
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
                                    $answerId
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
                                    ""
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
                                    $answerId
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
                                    ""
                                );
                            }
                            break;
                        case MULTIPLE_ANSWER_TRUE_FALSE :
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
                                    $answerId
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
                                    ""
                                );
                            }
                            break;
                        case FILL_IN_BLANKS:
                            ExerciseShowFunctions::display_fill_in_blanks_answer($feedback_type, $answer, $exeId, $questionId);
                            break;
                        case FREE_ANSWER:
                            echo ExerciseShowFunctions::display_free_answer(
                                $feedback_type,
                                $choice,
                                $exeId,
                                $questionId,
                                $questionScore
                            );
                            break;
                        case ORAL_EXPRESSION:
                            echo '<tr>
		                            <td valign="top">'.ExerciseShowFunctions::display_oral_expression_answer(
                                    $feedback_type,
                                    $choice,
                                    $exeId,
                                    $questionId,
                                    $nano).'</td>
		                            </tr>
		                            </table>';
                            break;
                        case HOT_SPOT:
                            ExerciseShowFunctions::display_hotspot_answer($feedback_type, $counter, $answer, $studentChoice, $answerComment);
                            break;
                        case HOT_SPOT_DELINEATION:
                            $user_answer = $user_array;
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

                                //$overlap = round(polygons_overlap($poly_answer,$poly_user)); //this is an area in pixels
                                if ($debug > 0) {
                                    error_log(__LINE__.' - Polygons results are '.print_r($poly_results, 1), 0);
                                }
                                if ($overlap < 1) {
                                    //shortcut to avoid complicated calculations
                                    $final_overlap = 0;
                                    $final_missing = 100;
                                    $final_excess = 100;
                                } else {
                                    // the final overlap is the percentage of the initial polygon that is overlapped by the user's polygon
                                    $final_overlap = round(((float)$overlap / (float)$poly_answer_area) * 100);
                                    if ($debug > 1) {
                                        error_log(__LINE__.' - Final overlap is '.$final_overlap, 0);
                                    }
                                    // the final missing area is the percentage of the initial polygon that is not overlapped by the user's polygon
                                    $final_missing = 100 - $final_overlap;
                                    if ($debug > 1) {
                                        error_log(__LINE__.' - Final missing is '.$final_missing, 0);
                                    }
                                    // the final excess area is the percentage of the initial polygon's size that is covered by the user's polygon outside of the initial polygon
                                    $final_excess = round(
                                        (((float)$poly_user_area - (float)$overlap) / (float)$poly_answer_area) * 100
                                    );
                                    if ($debug > 1) {
                                        error_log(__LINE__.' - Final excess is '.$final_excess, 0);
                                    }
                                }

                                //checking the destination parameters parsing the "@@"
                                $destination_items = explode('@@', $answerDestination);
                                $threadhold_total = $destination_items[0];
                                $threadhold_items = explode(';', $threadhold_total);
                                $threadhold1 = $threadhold_items[0]; // overlap
                                $threadhold2 = $threadhold_items[1]; // excess
                                $threadhold3 = $threadhold_items[2]; //missing
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
                                            error_log(__LINE__.' - answerId is of type noerror', 0);
                                        }
                                        //type no error shouldn't be treated
                                        $next = 1;
                                        continue;
                                    }
                                    if ($debug > 0) {
                                        error_log(__LINE__.' - answerId is >1 so we\'re probably in OAR', 0);
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
                                    $overlap = poly_touch($poly_user_compiled, $poly_answer_compiled, $max_coord);

                                    if ($overlap == false) {
                                        //all good, no overlap
                                        $next = 1;
                                        continue;
                                    } else {
                                        if ($debug > 0) {
                                            error_log(__LINE__.' - Overlap is '.$overlap.': OAR hit', 0);
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
                                        $url_hotspot = $destination_items[4];
                                    }
                                }
                            } else { // the first delineation feedback
                                if ($debug > 0) {
                                    error_log(__LINE__.' first', 0);
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
                        case MATCHING:
                            //if ($origin != 'learnpath') {
                                echo '<tr>';
                                echo '<td>'.$answer_matching[$answerId].'</td><td>'.$user_answer.' / <b><span style="color: #008000;">'.$answer_matching[$answerCorrect].'</span></b></td>';
                                echo '</tr>';
                            //}
                            break;
                    }
                }
            }
            $counter++;
        } // end for that loops over all answers of the current question

        if ($debug) {
            error_log('<-- end answer loop -->');
        }
        $final_answer = true;
        foreach ($real_answers as $my_answer) {
            if (!$my_answer) {
                $final_answer = false;
            }
        }

        //we add the total score after dealing with the answers
        if ($answerType == MULTIPLE_ANSWER_COMBINATION || $answerType == MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE) {
            if ($final_answer) {
                //getting only the first score where we save the weight of all the question
                $answerWeighting = $objAnswerTmp->selectWeighting($objAnswerTmp->getRealAnswerIdFromList(1));
                $questionScore += $answerWeighting;
                $totalScore += $answerWeighting;
            }
        }

        //Fixes multiple answer question in order to be exact
        if ($answerType == MULTIPLE_ANSWER || $answerType == GLOBAL_MULTIPLE_ANSWER) {
            $diff = @array_diff($answer_correct_array, $real_answers);
            /*
             * All good answers or nothing works like exact
              $counter = 1;
              $correct_answer = true;
              foreach ($real_answers as $my_answer) {
              if ($debug) error_log(" my_answer: $my_answer answer_correct_array[counter]: ".$answer_correct_array[$counter]);
              if ($my_answer != $answer_correct_array[$counter]) {
              $correct_answer = false;
              break;
              }
              $counter++;
              } */
            if ($debug) {
                error_log("answer_correct_array: ".print_r($answer_correct_array, 1)."");
                error_log("real_answers: ".print_r($real_answers, 1)."");
            }
            // This makes the result non exact
            if (!empty($diff)) {
                //$questionScore = 0;
            }
        }

        $extra_data = array(
            'final_overlap' => $final_overlap,
            'final_missing' => $final_missing,
            'final_excess' => $final_excess,
            'overlap_color' => $overlap_color,
            'missing_color' => $missing_color,
            'excess_color' => $excess_color,
            'threadhold1' => $threadhold1,
            'threadhold2' => $threadhold2,
            'threadhold3' => $threadhold3,
        );

        if ($from == 'exercise_result') {
            // if answer is hotspot. To the difference of exercise_show.php, we use the results from the session (from_db=0)
            // TODO Change this, because it is wrong to show the user some results that haven't been stored in the database yet
            if ($answerType == HOT_SPOT || $answerType == HOT_SPOT_ORDER || $answerType == HOT_SPOT_DELINEATION) {

                if ($debug) {
                    error_log('$from AND this is a hotspot kind of question ');
                }

                $my_exe_id = 0;
                $from_database = 0;
                if ($answerType == HOT_SPOT_DELINEATION) {
                    if (0) {
                        if ($overlap_color) {
                            $overlap_color = 'green';
                        } else {
                            $overlap_color = 'red';
                        }
                        if ($missing_color) {
                            $missing_color = 'green';
                        } else {
                            $missing_color = 'red';
                        }
                        if ($excess_color) {
                            $excess_color = 'green';
                        } else {
                            $excess_color = 'red';
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

                        if ($final_overlap > 100) {
                            $final_overlap = 100;
                        }

                        $table_resume = '<table class="data_table">
        				<tr class="row_odd" >
        					<td></td>
        					<td ><b>'.get_lang('Requirements').'</b></td>
        					<td><b>'.get_lang('YourAnswer').'</b></td>
        				</tr>
        				<tr class="row_even">
        					<td><b>'.get_lang('Overlap').'</b></td>
        					<td>'.get_lang('Min').' '.$threadhold1.'</td>
        					<td><div style="color:'.$overlap_color.'">'.(($final_overlap < 0) ? 0 : intval(
                            $final_overlap
                        )).'</div></td>
        				</tr>
        				<tr>
        					<td><b>'.get_lang('Excess').'</b></td>
        					<td>'.get_lang('Max').' '.$threadhold2.'</td>
        					<td><div style="color:'.$excess_color.'">'.(($final_excess < 0) ? 0 : intval(
                            $final_excess
                        )).'</div></td>
        				</tr>
        				<tr class="row_even">
        					<td><b>'.get_lang('Missing').'</b></td>
        					<td>'.get_lang('Max').' '.$threadhold3.'</td>
        					<td><div style="color:'.$missing_color.'">'.(($final_missing < 0) ? 0 : intval(
                            $final_missing
                        )).'</div></td>
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

                        echo '<h1><div style="color:#333;">'.get_lang('Feedback').'</div></h1>
        				<p style="text-align:center">';

                        $message = '<p>'.get_lang('YourDelineation').'</p>';
                        $message .= $table_resume;
                        $message .= '<br />'.get_lang('ResultIs').' '.$result_comment.'<br />';
                        if ($organs_at_risk_hit > 0) {
                            $message .= '<p><b>'.get_lang('OARHit').'</b></p>';
                        }
                        $message .= '<p>'.$comment.'</p>';
                        echo $message;
                    } else {
                        echo $hotspot_delineation_result[0]; //prints message
                        $from_database = 1; // the hotspot_solution.swf needs this variable
                    }

                    //save the score attempts

                    if (1) {
                        $final_answer = $hotspot_delineation_result[1]; //getting the answer 1 or 0 comes from exercise_submit_modal.php
                        if ($final_answer == 0) {
                            $questionScore = 0;
                        }
                        saveQuestionAttempt($questionScore, 1, $quesId, $exeId, 0, null, $updateResults); // we always insert the answer_id 1 = delineation
                        //in delineation mode, get the answer from $hotspot_delineation_result[1]
                        saveExerciseAttemptHotspot($exeId, $quesId, $objAnswerTmp->getRealAnswerIdFromList(1), $hotspot_delineation_result[1], $exerciseResultCoordinates[$quesId], $updateResults);
                    } else {
                        if ($final_answer == 0) {
                            $questionScore = 0;
                            $answer = 0;
                            saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0, null, $updateResults);
                            if (is_array($exerciseResultCoordinates[$quesId])) {
                                foreach ($exerciseResultCoordinates[$quesId] as $idx => $val) {
                                    saveExerciseAttemptHotspot($exeId, $quesId, $objAnswerTmp->getRealAnswerIdFromList($idx), 0, $val, $updateResults);
                                }
                            }
                        } else {
                            saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0, null, $updateResults);
                            if (is_array($exerciseResultCoordinates[$quesId])) {
                                foreach ($exerciseResultCoordinates[$quesId] as $idx => $val) {
                                    saveExerciseAttemptHotspot($exeId, $quesId, $objAnswerTmp->getRealAnswerIdFromList($idx), $choice[$idx], $val, $updateResults);
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
                    //if ($origin != 'learnpath') {
                        echo '</table></td></tr>';
                        echo '<tr>
                            <td colspan="2">';
                        echo '<i>'.get_lang('HotSpot').'</i><br /><br />';

                        echo '<object type="application/x-shockwave-flash" data="'.api_get_path(
                            WEB_CODE_PATH
                        ).'plugin/hotspot/hotspot_solution.swf?modifyAnswers='.Security::remove_XSS(
                            $questionId
                        ).'&exe_id='.$exeId.'&from_db=1" width="552" height="352">
								<param name="movie" value="../plugin/hotspot/hotspot_solution.swf?modifyAnswers='.Security::remove_XSS(
                            $questionId
                        ).'&exe_id='.$exeId.'&from_db=1" />
							</object>';
                        echo '</td>
                        </tr>';
                    //}
                }
            }

            if ($origin != 'learnpath') {
                if ($show_result) {
                    echo '</table>';
                }
            }
        }

        $totalWeighting += $questionWeighting;
        // Store results directly in the database
        // For all in one page exercises, the results will be
        // stored by exercise_results.php (using the session)

        if ($saved_results) {
            if ($debug) {
                error_log("Save question results: $saved_results");
                error_log(print_r($choice, 1));
            }

            if (empty($choice)) {
                $choice = 0;
            }
            if ($answerType == MULTIPLE_ANSWER_TRUE_FALSE || $answerType == MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE) {
                if ($choice != 0) {
                    $reply = array_keys($choice);
                    for ($i = 0; $i < sizeof($reply); $i++) {
                        $ans = $reply[$i];
                        saveQuestionAttempt($questionScore, $ans.':'.$choice[$ans], $quesId, $exeId, $i, $this->id, $updateResults);
                        if ($debug) {
                            error_log('result =>'.$questionScore.' '.$ans.':'.$choice[$ans]);
                        }
                    }
                } else {
                    saveQuestionAttempt($questionScore, 0, $quesId, $exeId, 0, $this->id, $updateResults);
                }
            } elseif ($answerType == MULTIPLE_ANSWER || $answerType == GLOBAL_MULTIPLE_ANSWER) {
                if ($choice != 0) {
                    $reply = array_keys($choice);

                    if ($debug) {
                        error_log("reply ".print_r($reply, 1)."");
                    }
                    for ($i = 0; $i < sizeof($reply); $i++) {
                        $ans = $reply[$i];
                        saveQuestionAttempt($questionScore, $ans, $quesId, $exeId, $i, $this->id, $updateResults);
                    }
                } else {
                    saveQuestionAttempt($questionScore, 0, $quesId, $exeId, 0, $this->id, $updateResults);
                }
            } elseif ($answerType == MULTIPLE_ANSWER_COMBINATION) {
                if ($choice != 0) {
                    $reply = array_keys($choice);
                    for ($i = 0; $i < sizeof($reply); $i++) {
                        $ans = $reply[$i];
                        saveQuestionAttempt($questionScore, $ans, $quesId, $exeId, $i, $this->id, $updateResults);
                    }
                } else {
                    saveQuestionAttempt($questionScore, 0, $quesId, $exeId, 0, $this->id, $updateResults);
                }
            } elseif ($answerType == MATCHING || $answerType == DRAGGABLE) {
                if (isset($matching)) {
                    foreach ($matching as $j => $val) {
                        saveQuestionAttempt($questionScore, $val, $quesId, $exeId, $j, $this->id, $updateResults);
                    }
                }
            } elseif ($answerType == FREE_ANSWER) {
                $answer = $choice;
                saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0, $this->id, $updateResults);
            } elseif ($answerType == ORAL_EXPRESSION) {
                $answer = $choice;
                saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0, $this->id, $updateResults, $nano);
            } elseif ($answerType == UNIQUE_ANSWER || $answerType == UNIQUE_ANSWER_IMAGE || $answerType == UNIQUE_ANSWER_NO_OPTION) {
                $answer = $choice;
                saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0, $this->id, $updateResults);
                //            } elseif ($answerType == HOT_SPOT || $answerType == HOT_SPOT_DELINEATION) {
            } elseif ($answerType == HOT_SPOT) {
                saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0, $this->id, $updateResults);
                if (isset($exerciseResultCoordinates[$questionId]) && !empty($exerciseResultCoordinates[$questionId])) {
                    foreach ($exerciseResultCoordinates[$questionId] as $idx => $val) {
                        saveExerciseAttemptHotspot($exeId, $quesId, $objAnswerTmp->getRealAnswerIdFromList($idx), $choice[$idx], $val, $updateResults, $this->id);
                    }
                }
            } else {
                saveQuestionAttempt($questionScore, $answer, $quesId, $exeId, 0, $this->id, $updateResults);
            }
        }

        if ($propagate_neg == 0 && $questionScore < 0) {
            $questionScore = 0;
        }

        if ($saved_results) {
            $stat_table = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
            $sql_update = 'UPDATE '.$stat_table.' SET exe_result = exe_result + '.floatval($questionScore).' WHERE exe_id = '.$exeId;
            if ($debug) {
                error_log($sql_update);
            }
            Database::query($sql_update);
        }

        $return_array = array(
            'score' => $questionScore,
            'weight' => $questionWeighting,
            'extra' => $extra_data,
            'open_question' => $arrques,
            'open_answer' => $arrans,
            'answer_type' => $answerType,
            'user_choices' => $choice,
        );

        return $return_array;
    }

    /**
     * @return array
     */
    public function returnNotificationTag()
    {
        return array(
            '{{ student.username }}',
            '{{ student.firstname }}',
            '{{ student.lastname }}',
            '{{ exercise.title }}',
            '{{ exercise.start_time }}',
            '{{ exercise.end_time }}',
            '{{ exercise.question_and_answer_ids }}',
            '{{ exercise.assert_count }}',
            '{{ exercise_result_message }}' // if success or failed
        );
    }

    /**
     * @param int $exeId
     * @param array
     * @param bool
     * @return bool
     */
    public function sendCustomNotification($exeId, $exerciseResult = array(), $exerciseWasPassed = false)
    {
        if (!empty($this->emailNotificationTemplate) or !empty($this->emailNotificationTemplateToUser)) {

            // Getting attempt info
            $trackExerciseInfo = ExerciseLib::get_exercise_track_exercise_info($exeId);

            if (empty($trackExerciseInfo)) {
                return false;
            }
        }

        if ($this->emailAlert) {

            if (!empty($this->emailNotificationTemplate)) {
                $twig = new \Twig_Environment(new \Twig_Loader_String());
                $twig->addFilter('var_dump', new Twig_Filter_Function('var_dump'));
                $template = "{% autoescape false %} ".$this->emailNotificationTemplate."{% endautoescape %}";
            } else {
                global $app;
                $twig = $app['twig'];
                $template = 'default/mail/exercise/end_exercise_notification.tpl';
            }

            $userInfo = api_get_user_info($trackExerciseInfo['exe_user_id'], false, false, true);
            $courseInfo = api_get_course_info_by_id($trackExerciseInfo['c_id']);

            $twig->addGlobal('student', $userInfo);
            $twig->addGlobal('exercise', $this);
            $twig->addGlobal('exercise.start_time', $trackExerciseInfo['start_time']);
            $twig->addGlobal('exercise.end_time', $trackExerciseInfo['end_time']);
            $twig->addGlobal('course', $courseInfo);

            if ($exerciseWasPassed) {
                $twig->addGlobal('exercise_result_message', $this->getOnSuccessMessage());
            } else {
                $twig->addGlobal('exercise_result_message', $this->getOnFailedMessage());
            }

            $resultInfo = array();
            $resultInfoToString = null;
            $countCorrectToString = null;

            if (!empty($exerciseResult)) {

                $countCorrect = array();
                $countCorrect['correct'] = 0;
                $countCorrect['total'] = 0;
                $counter = 1;
                foreach ($exerciseResult as $questionId => $result) {
                    $resultInfo[$questionId] = isset($result['details']['user_choices']) ? $result['details']['user_choices'] : null;
                    $correct = $result['score']['pass'] ? 1 : 0;
                    $countCorrect['correct'] += $correct;
                    $countCorrect['total'] = $counter;
                    $counter++;
                }

                if (!empty($resultInfo)) {
                    $resultInfoToString = json_encode($resultInfo);
                }

                if (!empty($countCorrect)) {
                    $countCorrectToString = json_encode($countCorrect);
                }
            }

            $twig->addGlobal('question_and_answer_ids', $resultInfoToString);
            $twig->addGlobal('asserts', $countCorrectToString);

            if (api_get_session_id()) {
                $teachers = CourseManager::get_coach_list_from_course_code($courseInfo['real_id'], api_get_session_id());
            } else {
                $teachers = CourseManager::get_teacher_list_from_course_code($courseInfo['real_id']);
            }

            try {
                $twig->parse($twig->tokenize($template));
                $content = $twig->render($template);

                $subject = get_lang('ExerciseResult');

                if (!empty($teachers)) {
                    foreach ($teachers as $user_id => $teacher_data) {
                        MessageManager::send_message_simple($user_id, $subject, $content);
                    }
                }


            } catch (Twig_Error_Syntax $e) {
                // $template contains one or more syntax errors
                Display::display_warning_message(get_lang('ThereIsAnErrorInTheTemplate'));
               echo $e->getMessage();
            }
        }

        // Message send only to student.
        if ($this->notifyUserByEmail == 1) {

            // Message send only to student.

            if (!empty($this->emailNotificationTemplateToUser)) {
                $twig = new \Twig_Environment(new \Twig_Loader_String());
                $twig->addFilter('var_dump', new Twig_Filter_Function('var_dump'));
                $template = "{% autoescape false %} ".$this->emailNotificationTemplateToUser."{% endautoescape %}";
            } else {
                global $app;
                $twig = $app['twig'];
                $template = 'default/mail/exercise/end_exercise_notification_to_user.tpl';
            }

            $userInfo = api_get_user_info($trackExerciseInfo['exe_user_id'], false, false, true);
            $courseInfo = api_get_course_info_by_id($trackExerciseInfo['c_id']);

            $twig->addGlobal('student', $userInfo);
            $twig->addGlobal('exercise', $this);
            $twig->addGlobal('exercise.start_time', $trackExerciseInfo['start_time']);
            $twig->addGlobal('exercise.end_time', $trackExerciseInfo['end_time']);
            $twig->addGlobal('course', $courseInfo);

            if ($exerciseWasPassed) {
                $twig->addGlobal('exercise_result_message', $this->getOnSuccessMessage());
            } else {
                $twig->addGlobal('exercise_result_message', $this->getOnFailedMessage());
            }

            $resultInfo = array();
            $resultInfoToString = null;
            $countCorrectToString = null;

            if (!empty($exerciseResult)) {

                $countCorrect = array();
                $countCorrect['correct'] = 0;
                $countCorrect['total'] = 0;
                $counter = 1;
                foreach ($exerciseResult as $questionId => $result) {
                    $resultInfo[$questionId] = isset($result['details']['user_choices']) ? $result['details']['user_choices'] : null;
                    $correct = $result['score']['pass'] ? 1 : 0;
                    $countCorrect['correct'] += $correct;
                    $countCorrect['total'] = $counter;
                    $counter++;
                }

                if (!empty($resultInfo)) {
                    $resultInfoToString = json_encode($resultInfo);
                }

                if (!empty($countCorrect)) {
                    $countCorrectToString = json_encode($countCorrect);
                }
            }

            $twig->addGlobal('question_and_answer_ids', $resultInfoToString);
            $twig->addGlobal('asserts', $countCorrectToString);

            try {
                $twig->parse($twig->tokenize($template));
                $content = $twig->render($template);
                // Student who finish the exercise
                MessageManager::send_message_simple(api_get_user_id(), get_lang('ExerciseResult'), $content);

            } catch (Twig_Error_Syntax $e) {
                // $template contains one or more syntax errors
                Display::display_warning_message(get_lang('ThereIsAnErrorInTheTemplate'));
                echo $e->getMessage();
            }


        }
    }

    /**
     * Sends a notification when a user ends an examn
     * @param array $question_list_answers
     * @param string $origin
     * @param int $exe_id
     * @return null
     */
    public function sendNotificationForOpenQuestions($question_list_answers, $origin, $exe_id)
    {
        if ($this->emailAlert == false) {
            return null;
        }
        // Email configuration settings
        $course_info = api_get_course_info(api_get_course_id());

        $url_email = api_get_path(WEB_CODE_PATH).'exercice/exercise_show.php?'.api_get_cidreq().'&id_session='.api_get_session_id().'&id='.$exe_id.'&action=qualify';
        $user_info = UserManager::get_user_info_by_id(api_get_user_id());

        $msg = '<p>'.get_lang('OpenQuestionsAttempted').' :</p>
                    <p>'.get_lang('AttemptDetails').' : </p>
                    <table class="data_table">
                        <tr>
                            <td><h3>'.get_lang('CourseName').'</h3></td>
                            <td><h3>#course#</h3></td>
                        </tr>
                        <tr>
                            <td>'.get_lang('TestAttempted').'</span></td>
                            <td>#exercise#</td>
                        </tr>
                        <tr>
                            <td>'.get_lang('StudentName').'</td>
                            <td>#firstName# #lastName#</td>
                        </tr>
                        <tr>
                            <td>'.get_lang('StudentEmail').'</td>
                            <td>#mail#</td>
                        </tr>
                    </table>';
        $open_question_list = null;
        foreach ($question_list_answers as $item) {
            $question = $item['question'];
            $answer = $item['answer'];
            $answer_type = $item['answer_type'];

            if (!empty($question) && !empty($answer) && $answer_type == FREE_ANSWER) {
                $open_question_list .= '<tr>
                            <td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;'.get_lang('Question').'</td>
                            <td width="473" valign="top" bgcolor="#F3F3F3">'.$question.'</td>
                        </tr>
                        <tr>
                            <td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;'.get_lang('Answer').'</td>
                            <td valign="top" bgcolor="#F3F3F3">'.$answer.'</td>
                        </tr>';
            }
        }

        if (!empty($open_question_list)) {
            $msg .= '<p><br />'.get_lang('OpenQuestionsAttemptedAre').' :</p>
                    <table width="730" height="136" border="0" cellpadding="3" cellspacing="3">';
            $msg .= $open_question_list;
            $msg .= '</table><br />';


            $msg1 = str_replace("#exercise#", $this->exercise, $msg);
            $msg = str_replace("#firstName#", $user_info['firstname'], $msg1);
            $msg1 = str_replace("#lastName#", $user_info['lastname'], $msg);
            $msg = str_replace("#mail#", $user_info['email'], $msg1);
            $msg = str_replace("#course#", $course_info['name'], $msg1);

            if ($origin != 'learnpath') {
                $msg .= get_lang('ClickToCommentAndGiveFeedback').', <br />
                            <a href="#url#">#url#</a>';
            }
            $msg1 = str_replace("#url#", $url_email, $msg);
            $mail_content = $msg1;
            $subject = get_lang('OpenQuestionsAttempted');

            if (api_get_session_id()) {
                $teachers = CourseManager::get_coach_list_from_course_code($course_info['real_id'], api_get_session_id());
            } else {
                $teachers = CourseManager::get_teacher_list_from_course_code($course_info['real_id']);
            }

            if (!empty($teachers)) {
                foreach ($teachers as $user_id => $teacher_data) {
                    MessageManager::send_message_simple($user_id, $subject, $mail_content);
                }
            }
        }
    }

    public function sendNotificationForOralQuestions($question_list_answers, $origin, $exe_id)
    {
        if ($this->emailAlert == false) {
            return null;
        }
        // Email configuration settings
        $coursecode = api_get_course_id();
        $course_info = api_get_course_info(api_get_course_id());

        $url_email = api_get_path(WEB_CODE_PATH).'exercice/exercise_show.php?'.api_get_cidreq(
        ).'&id_session='.api_get_session_id().'&id='.$exe_id.'&action=qualify';
        $user_info = UserManager::get_user_info_by_id(api_get_user_id());


        $oral_question_list = null;
        foreach ($question_list_answers as $item) {
            $question = $item['question'];
            $answer = $item['answer'];
            $answer_type = $item['answer_type'];

            if (!empty($question) && !empty($answer) && $answer_type == ORAL_EXPRESSION) {
                $oral_question_list .= '<br /><table width="730" height="136" border="0" cellpadding="3" cellspacing="3"><tr>
                            <td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;'.get_lang('Question').'</td>
                            <td width="473" valign="top" bgcolor="#F3F3F3">'.$question.'</td>
                        </tr>
                        <tr>
                            <td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;'.get_lang('Answer').'</td>
                            <td valign="top" bgcolor="#F3F3F3">'.$answer.'</td>
                        </tr></table>';
            }
        }

        if (!empty($oral_question_list)) {
            $msg = '<p>'.get_lang('OralQuestionsAttempted').' :</p>
                    <p>'.get_lang('AttemptDetails').' : </p>
                    <table class="data_table">
                        <tr>
                            <td><h3>'.get_lang('CourseName').'</h3></td>
                            <td><h3>#course#</h3></td>
                        </tr>
                        <tr>
                            <td>'.get_lang('TestAttempted').'</span></td>
                            <td>#exercise#</td>
                        </tr>
                        <tr>
                            <td>'.get_lang('StudentName').'</td>
                            <td>#firstName# #lastName#</td>
                        </tr>
                        <tr>
                            <td>'.get_lang('StudentEmail').'</td>
                            <td>#mail#</td>
                        </tr>
                    </table>';
            $msg .= '<br />'.sprintf(get_lang('OralQuestionsAttemptedAreX'), $oral_question_list).'<br />';
            $msg1 = str_replace("#exercise#", $this->exercise, $msg);
            $msg = str_replace("#firstName#", $user_info['firstname'], $msg1);
            $msg1 = str_replace("#lastName#", $user_info['lastname'], $msg);
            $msg = str_replace("#mail#", $user_info['email'], $msg1);
            $msg = str_replace("#course#", $course_info['name'], $msg1);

            if ($origin != 'learnpath') {
                $msg .= get_lang('ClickToCommentAndGiveFeedback').', <br />
                            <a href="#url#">#url#</a>';
            }
            $msg1 = str_replace("#url#", $url_email, $msg);
            $mail_content = $msg1;
            $subject = get_lang('OralQuestionsAttempted');

            if (api_get_session_id()) {
                $teachers = CourseManager::get_coach_list_from_course_code($course_info['real_id'], api_get_session_id());
            } else {
                $teachers = CourseManager::get_teacher_list_from_course_code($course_info['real_id']);
            }

            if (!empty($teachers)) {
                foreach ($teachers as $user_id => $teacher_data) {
                    MessageManager::send_message_simple($user_id, $subject, $mail_content);
                }
            }
        }
    }

    /**
     * @param string $user_data
     * @param string $start_date
     * @param int $duration
     * @return string
     */
    function show_exercise_result_header($user_data, $start_date = null, $duration = null)
    {
        $array = array();

        if (!empty($user_data)) {
            $array[] = array('title' => get_lang("User"), 'content' => $user_data);
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
            $array[] = array('title' => get_lang("StartDate"), 'content' => $start_date);
        }

        if (!empty($duration)) {
            $array[] = array('title' => get_lang("Duration"), 'content' => $duration);
        }

        $html = Display::page_subheader(
            Display::return_icon('quiz_big.png', get_lang('Result')).' '.$this->exercise.' : '.get_lang('Result')
        );
        $html .= Display::description($array);

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
    function create_quiz($title, $expired_time = 0, $type = 2, $random = 0, $active = 1, $results_disabled = 0, $max_attempt = 0, $feedback = 3) {
        $this->updateTitle($title);
        $this->updateActive($active);
        $this->updateAttempts($max_attempt);
        $this->updateFeedbackType($feedback);
        $this->updateType($type);
        $this->setRandom($random);
        //$this->updateRandomAnswers($form->getSubmitValue('randomAnswers'));
        $this->updateResultsDisabled($results_disabled);
        $this->updateExpiredTime($expired_time);
        /*$this->updatePropagateNegative($form->getSubmitValue('propagate_neg'));
        $this->updateRandomByCat($form->getSubmitValue('randomByCat'));
        $this->updateTextWhenFinished($form->getSubmitValue('text_when_finished'));
        $this->updateDisplayCategoryName($form->getSubmitValue('display_category_name'));
        $this->updateReviewAnswers($form->getSubmitValue('review_answers'));
        $this->updatePassPercentage($form->getSubmitValue('pass_percentage'));
        $this->updateCategories($form->getSubmitValue('category'));*/
        $this->save();
        $quiz_id = $this->selectId();
        return $quiz_id;
    }

    function process_geometry()
    {

    }

    /**
     * Returns the exercise result
     * @param     int        attempt id
     * @return     float     exercise result
     */
    public function get_exercise_result($exe_id)
    {
        $result = array();
        $track_exercise_info = ExerciseLib::get_exercise_track_exercise_info($exe_id);
        $totalScore = 0;
        if (!empty($track_exercise_info)) {
            $objExercise = new Exercise();
            $objExercise->read($track_exercise_info['exe_exo_id']);
            if (!empty($track_exercise_info['data_tracking'])) {
                $question_list = explode(',', $track_exercise_info['data_tracking']);
            }
            foreach ($question_list as $questionId) {
                $question_result = $objExercise->manageAnswers(
                    $exe_id,
                    $questionId,
                    '',
                    'exercise_show',
                    array(),
                    false,
                    true,
                    false
                );
                // $questionScore = $question_result['score'];
                $totalScore += $question_result['score'];
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
     * Checks if the exercise is visible due a lot of conditions - visibility, time limits, student attempts
     *
     * @param int $lp_id
     * @param int $lp_item_id
     * @param int $lp_item_view_id
     * @param bool $filter_by_admin
     * @return array
     */
    public function is_visible($lp_id = 0, $lp_item_id = 0, $lp_item_view_id = 0, $filter_by_admin = true)
    {
        //1. By default the exercise is visible
        $is_visible = true;
        $message = null;

        //1.1 Admins and teachers can access to the exercise
        if ($filter_by_admin) {
            if (api_is_platform_admin() || api_is_course_admin()) {
                return array('value' => true, 'message' => '');
            }
        }

        //Checking visibility in the item_property table
        $visibility = api_get_item_visibility(api_get_course_info(), TOOL_QUIZ, $this->id, api_get_session_id());

        if ($visibility == 0) {
            $this->active = 0;
        }

        //2. If the exercise is not active
        if (empty($lp_id)) {
            //2.1 LP is OFF
            if ($this->active == 0) {
                return array(
                    'value' => false,
                    'message' => Display::return_message(get_lang('ExerciseNotFound'), 'warning', false)
                );
            }
        } else {
            //2.1 LP is loaded
            if ($this->active == 0 AND !learnpath::is_lp_visible_for_student($lp_id, api_get_user_id())) {
                return array(
                    'value' => false,
                    'message' => Display::return_message(get_lang('ExerciseNotFound'), 'warning', false)
                );
            }
        }

        //3. We check if the time limits are on
        $limit_time_exists = ((!empty($this->start_time) && $this->start_time != '0000-00-00 00:00:00') || (!empty($this->end_time) && $this->end_time != '0000-00-00 00:00:00')) ? true : false;

        if ($limit_time_exists) {
            $time_now = time();

            if (!empty($this->start_time) && $this->start_time != '0000-00-00 00:00:00') {
                $is_visible = (($time_now - api_strtotime($this->start_time, 'UTC')) > 0) ? true : false;
            }

            if ($is_visible == false) {
                $message = sprintf(get_lang('ExerciseAvailableFromX'), api_convert_and_format_date($this->start_time));
            }

            if ($is_visible == true) {
                if ($this->end_time != '0000-00-00 00:00:00') {
                    $is_visible = ((api_strtotime($this->end_time, 'UTC') > $time_now) > 0) ? true : false;
                    if ($is_visible == false) {
                        $message = sprintf(
                            get_lang('ExerciseAvailableUntilX'),
                            api_convert_and_format_date($this->end_time)
                        );
                    }
                }
            }
            if ($is_visible == false && $this->start_time != '0000-00-00 00:00:00' && $this->end_time != '0000-00-00 00:00:00') {
                $message = sprintf(
                    get_lang('ExerciseWillBeActivatedFromXToY'),
                    api_convert_and_format_date($this->start_time),
                    api_convert_and_format_date($this->end_time)
                );
            }
        }

        // 4. We check if the student have attempts
        if ($is_visible) {
            if ($this->selectAttempts() > 0) {
                $attempt_count = get_attempt_count_not_finished(
                    api_get_user_id(),
                    $this->id,
                    $lp_id,
                    $lp_item_id,
                    $lp_item_view_id
                );

                if ($attempt_count >= $this->selectAttempts()) {
                    $message = sprintf(get_lang('ReachedMaxAttempts'), $this->name, $this->selectAttempts());
                    $is_visible = false;
                }
            }
        }
        if (!empty($message)) {
            $message = Display :: return_message($message, 'warning', false);
        }

        return array('value' => $is_visible, 'message' => $message);
    }

    /**
     * @return bool
     */
    function added_in_lp()
    {
        $TBL_LP_ITEM = Database::get_course_table(TABLE_LP_ITEM);
        $sql = "SELECT max_score FROM $TBL_LP_ITEM WHERE c_id = ".$this->course_id." AND item_type = '".TOOL_QUIZ."' AND path = '".$this->id."'";
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
        $mediaList= array();
        if (!empty($questionList)) {
            foreach ($questionList as $questionId) {
                $objQuestionTmp = Question::read($questionId);

                // If a media question exists
                if (isset($objQuestionTmp->parent_id) && $objQuestionTmp->parent_id != 0) {
                    $mediaList[$objQuestionTmp->parent_id][] = $objQuestionTmp->id;
                } else {
                    // Always the last item
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
                    $new_question_list = ArrayClass::array_flatten($new_question_list);
                }
            } else {
                $new_question_list = $question_list;
            }
        }
        return $new_question_list;
    }

    /**
     * @param int $exe_id
     * @return array
     */
    public function getStatTrackExerciseInfoByExeId($exe_id)
    {
        $track_exercises = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
        $exe_id = intval($exe_id);
        $sql_track = "SELECT * FROM $track_exercises WHERE exe_id = $exe_id ";
        $result = Database::query($sql_track);
        $new_array = array();
        if (Database::num_rows($result) > 0) {
            $new_array = Database::fetch_array($result, 'ASSOC');

            $new_array['duration'] = null;

            $start_date = api_get_utc_datetime($new_array['start_date'], true);
            $end_date = api_get_utc_datetime($new_array['exe_date'], true);

            if (!empty($start_date) && !empty($end_date)) {
                $start_date = api_strtotime($start_date, 'UTC');
                $end_date = api_strtotime($end_date, 'UTC');
                if ($start_date && $end_date) {
                    $mytime = $end_date - $start_date;
                    $new_learnpath_item = new learnpathItem(null);
                    $time_attemp = $new_learnpath_item->get_scorm_time('js', $mytime);
                    $h = get_lang('h');
                    $time_attemp = str_replace('NaN', '00'.$h.'00\'00"', $time_attemp);
                    $new_array['duration'] = $time_attemp;
                }
            }
        }

        return $new_array;
    }

    /**
     * @param int $exe_id
     * @param int $question_id
     * @param string $action
     */
    public function edit_question_to_remind($exe_id, $question_id, $action = 'add')
    {
        $exercise_info = self::getStatTrackExerciseInfoByExeId($exe_id);
        $question_id = intval($question_id);
        $exe_id = intval($exe_id);
        $track_exercises = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
        if ($exercise_info) {

            if (empty($exercise_info['questions_to_check'])) {
                if ($action == 'add') {
                    $sql = "UPDATE $track_exercises SET questions_to_check = '$question_id' WHERE exe_id = $exe_id ";
                    Database::query($sql);
                }
            } else {
                $remind_list = explode(',', $exercise_info['questions_to_check']);
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
                } elseif ($action == 'delete') {
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

    /**
     * @param string $answer
     * @return mixed
     */
    public function fill_in_blank_answer_to_array($answer)
    {
        api_preg_match_all('/\[[^]]+\]/', $answer, $teacher_answer_list);
        $teacher_answer_list = $teacher_answer_list[0];

        return $teacher_answer_list;
    }

    /**
     * @param $answer
     * @return string
     */
    public function fill_in_blank_answer_to_string($answer)
    {
        $teacher_answer_list = $this->fill_in_blank_answer_to_array($answer);
        $result = '';
        if (!empty($teacher_answer_list)) {
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

    /**
     * Returns a time limit message
     * @return string
     */
    public function returnTimeLeftDiv()
    {
        $message = Display::return_message(
            get_lang('ReachedTimeLimit'), 'warning' ).' '.sprintf(get_lang('YouWillBeRedirectedInXSeconds'),
            '<span id="counter_to_redirect" class="red_alert"></span>'
        );
        $html = '<div id="clock_warning" style="display:none">'.$message.'</div>';
        $html .= '<div class="row"><div class="pull-right"><div id="exercise_clock_warning" class="well count_down"></div></div></div>';

        return $html;
    }

    /**
     * @param string $url
     * @return string
     */
    public function returnWarningJs($url)
    {
        $condition = "
            var dialog = $('#dialog-confirm');

            if (dialog.data('question_list') != '' && dialog.data('question_list') != undefined) {
                saveQuestionList(dialog.data('question_list'));
            } else {
                saveNow(dialog.data('question_id'), dialog.data('url_extra'), dialog.data('redirect'));
            }
            $(this).dialog('close');
        ";

        if (!empty($url)) {
           $condition = 'window.location = "'.$url.'&" + lp_data;';
        }

        return '<script>
            $(function() {
                $("#dialog-confirm").dialog({
                    autoOpen: false,
                    resizable: false,
                    height:200,
                    width:550,
                    modal: true,
                    buttons: {
                        "cancel": {
                            click: function() {
                                $(this).dialog("close");
                            },
                            text : "'.get_lang("NoIWantToTurnBack").'",
                            class : "btn btn-danger"
                        },
                        "ok": {
                            click : function() {
                                '.$condition.'
                            },
                            text: "'.get_lang("YesImSure").'",
                            class : "btn btn-success"
                        }
                    }
                });
            });
        </script>';
    }

    /**
     * @return string
     */
    public function returnWarningHtml()
    {
        return  '<div id="dialog-confirm" title="'.get_lang('Exercise').'" style="display:none">
          <p><span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 20px 0;"></span>
          '.get_lang('IfYouContinueYourAnswerWillBeSavedAnyChangeWillBeNotAllowed').'
            </p>
        </div>';
    }

    /**
     * Get question list (including question ids of the media)
     * @return int
     */
    public function getCountUncompressedQuestionList()
    {
        $question_count = 0;
        $questionList = $this->questionList;
        if (!empty($questionList)) {
            $question_count = count($questionList);
        }

        return $question_count;
    }

    /**
     * Get question list (excluding question ids of the media)
     * @return int
     */
    public function getCountCompressedQuestionList()
    {
        $mediaQuestions = $this->getMediaList();
        $questionCount = 0;
        foreach ($mediaQuestions as $mediaKey => $questionList) {
            if ($mediaKey == 999) {
                $questionCount += count($questionList);
            } else {
                $questionCount++;
            }
        }
        return $questionCount;
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
     * Get question list (excluding question ids of the media)
     * @return int
     */
    public function getPositionOfQuestionInCompresedQuestionList()
    {
        $mediaQuestions = $this->getMediaList();
        $questionCount = 0;
        foreach ($mediaQuestions as $mediaKey => $questionList) {
            if ($mediaKey == 999) {
                $questionCount += count($questionList);
            } else {
                $questionCount++;
            }
        }
        return $questionCount;
    }

    /**
     * @return array
     */
    function get_exercise_list_ordered()
    {
        $table_exercise_order = Database::get_course_table(TABLE_QUIZ_ORDER);
        $course_id = api_get_course_int_id();
        $session_id = api_get_session_id();
        $sql = "SELECT exercise_id, exercise_order FROM $table_exercise_order
                       WHERE c_id = $course_id AND session_id = $session_id ORDER BY exercise_order";

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
            $sql = "SELECT * FROM $table WHERE exercise_id = {$this->id} AND c_id = {$this->course_id} ";
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
        $table_category = Database::get_course_table(TABLE_QUIZ_CATEGORY);
        $sql = "SELECT * FROM $table qc INNER JOIN $table_category c ON (category_id = c.iid)
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
            $sql = "SELECT SUM(count_questions) count_questions FROM $table
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
            $sql = "DELETE FROM $table WHERE exercise_id = {$this->id} AND c_id = {$this->course_id}";
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
     * Returns a HTML link when the exercise ends (exercise result page)
     * @return string
     */
    public function returnEndButtonHTML()
    {
        $endButtonSetting = $this->selectEndButton();
        $html = '';
        switch ($endButtonSetting) {
            case '0':
                $html = Display::url(get_lang('ReturnToCourseHomepage'), api_get_course_url(), array('class' => 'btn btn-large'));
                break;
            case '1':
                $html = Display::url(get_lang('ReturnToExerciseList'), api_get_path(WEB_CODE_PATH).'exercice/exercice.php?'.api_get_cidreq(), array('class' => 'btn btn-large'));
                break;
            case '2':
                global $app;
                $url = $app['url_generator']->generate('logout');
                $html = Display::url(get_lang('Logout'), $url, array('class' => 'btn btn-large'));
                break;
            case '3':
                break;
        }
        return $html;

    }

    /**
     * Gets a list of numbers with links to the questions, like a pagination. If there are categories associated,
     * the list is organized by categories.
     *
     * @param int $exe_id
     * @param array $questionList
     * @param array $questionListFlatten
     * @param array $remindList
     * @param int $reminder
     * @param int $remindQuestionId
     * @param string $url
     * @param int $current_question
     * @return string
     */
    public function getProgressPagination(
        $exe_id,
        $questionList,
        $questionListFlatten,
        $remindList,
        $reminder,
        $remindQuestionId,
        $url,
        $current_question
    ) {
        $exercise_result = getAnsweredQuestionsFromAttempt($exe_id, $this);

        $fixedRemindList = array();
        if (!empty($remindList)) {
            foreach ($questionListFlatten as $questionId) {
                if (in_array($questionId, $remindList)) {
                    $fixedRemindList[] = $questionId;
                }
            }
        }

        if (isset($reminder) && $reminder == 2) {
            $values = array_flip($questionListFlatten);
            if (!empty($current_question)) {
                $current_question = isset($values[$remindQuestionId]) ? $values[$remindQuestionId] + 1 : $values[$fixedRemindList[0]] +1;
            }
        }

        $categoryList = Session::read('categoryList');
        $categoryList = null;

        if (empty($categoryList)) {
            $categoryList = $this->getListOfCategoriesWithQuestionForTest();
            Session::write('categoryList', $categoryList);
        }

        $conditions = array();
        $conditions[] = array("class" => 'answered', 'items' => $exercise_result);
        $conditions[] = array("class" => 'remind', 'mode' => 'overwrite', 'items' => $remindList);

        $link = $url.'&num=';

        $html = '<div class="row" id="exercise_progress_block">';
        $html .= '<div class="span10" id="exercise_progress_bars">';
        if (!empty($categoryList)) {
            $html .= $this->progressExercisePaginationBarWithCategories($categoryList, $current_question, $conditions, $link);
        } else {
            $html .= $this->progressExercisePaginationBar($questionList, $current_question, $conditions, $link);
        }
        $html .= '</div>';

        $html .= '<div class="span2" id="exercise_progress_legend"><div class="legend_static">';

        $reviewAnswerLabel = null;
        if ($this->review_answers) {
            $reviewAnswerLabel = Display::label(sprintf(get_lang('ToReviewZ'),'c'), 'warning').'<br />';
        }
        $currentAnswerLabel = null;
        if (!empty($current_question)) {
            $currentAnswerLabel = Display::label(sprintf(get_lang('CurrentQuestionZ'),'d'), 'info');
        }

        // Count the number of answered, unanswered and 'for review' questions - see BT#6523
        $numa = count(array_flip(array_merge($exercise_result,$remindList)));
        $numu = count($questionListFlatten)-$numa;
        $numr = count($remindList);
        $html .= Display::label(sprintf(get_lang('AnsweredZ'),'a'), 'success').'<br />'.Display::label(sprintf(get_lang('UnansweredZ'),'b')).'<br />'.
                 $reviewAnswerLabel.$currentAnswerLabel.
                 '</div><div class="legend_dynamic">'.
                 sprintf(get_lang('AnsweredXYZ'),str_pad($numa,2,'0',STR_PAD_LEFT),'a','c').'<br />'.
                 sprintf(get_lang('UnansweredXYZ'),str_pad($numu,2,'0',STR_PAD_LEFT),'b').'<br />'.
                 sprintf(get_lang('ToReviewXYZ'),str_pad($numr,2,'0',STR_PAD_LEFT),'c').'</div>'.
                 '</div>';

        $html .= '</div>';
        return $html;
    }


    /**
     * @param array media question array
     * @return array question list (flatten not grouped by Medias)
     */
    public function getListOfCategoriesWithQuestionForTest()
    {
        $newMediaList = array();
        $mediaQuestions = $this->getMediaList();
        foreach ($mediaQuestions as $mediaId => $questionMediaList) {
            foreach ($questionMediaList as $questionId) {
                $newMediaList[$questionId] = $mediaId;
            }
        }
        $categoryList = $this->categoryWithQuestionList;
        $categoriesWithQuestion = array();

        if (!empty($categoryList)) {
            foreach ($categoryList as $categoryId => $category) {
                $categoriesWithQuestion[$categoryId] = $category['category'];
                $categoriesWithQuestion[$categoryId]['question_list'] = $category['question_list'];

                if (!empty($newMediaList)) {
                    foreach ($category['question_list'] as $questionId) {
                        if (isset($newMediaList[$questionId])) {
                           $categoriesWithQuestion[$categoryId]['media_question'] = $newMediaList[$questionId];
                        }  else {
                           $categoriesWithQuestion[$categoryId]['media_question'] = 999;
                        }
                    }
                }
            }
        }
        return $categoriesWithQuestion;
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

        if (in_array($this->results_disabled, array(RESULT_DISABLE_SHOW_SCORE_ONLY, RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES))) {
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
                    $question_content .= Testcategory::getCategoryNamesForQuestion($questionId, null, true, $this->categoryMinusOne);
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
            echo Testcategory::get_stats_table_by_attempt($this->id, $category_list, $this->categoryMinusOne);
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
     * @param $score
     * @param $weight
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
        $categoryTable = Database::get_course_table(TABLE_QUIZ_CATEGORY);
        $categoryRelTable = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $sql = "SELECT DISTINCT cat.*
                FROM $TBL_EXERCICE_QUESTION e INNER JOIN $TBL_QUESTIONS q
                    ON (e.question_id = q.iid and e.c_id = {$this->course_id})
                    INNER JOIN $categoryRelTable catRel
                    ON (catRel.question_id = e.question_id)
                    INNER JOIN $categoryTable cat
                    ON (cat.iid = catRel.category_id)
                WHERE e.c_id = {$this->course_id} AND e.exercice_id	= ".Database::escape_string($this->id);

        $result = Database::query($sql);
        $categoriesInExercise = array();
        if (Database::num_rows($result)) {
            $categoriesInExercise = Database::store_result($result, 'ASSOC');
        }
        return $categoriesInExercise;
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
                    $categoryName = Testcategory::getCategoryNamesForQuestion($objQuestionTmp->id, null, true, $categoryMinusOne);
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
                if (api_get_setting('enable_nanogong') == 'true') {

                    //@todo pass this as a parameter
                    global $exercise_stat_info, $exerciseId;

                    if (!empty($exercise_stat_info)) {
                        $params = array(
                            'exercise_id' => $exercise_stat_info['exe_exo_id'],
                            'exe_id' => $exercise_stat_info['exe_id'],
                            'question_id' => $questionId
                        );
                    } else {
                        $params = array(
                            'exercise_id' => $exerciseId,
                            'exe_id' => 'temp_exe',
                            'question_id' => $questionId
                        );
                    }

                    $nano = new Nanogong($params);
                    $s .= $nano->show_button();
                }

                $form->addElement('html_editor', "choice[".$questionId."]", null, array('id' => "choice[".$questionId."]"), array('ToolbarSet' => 'TestFreeAnswer'));
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
                            //$s .= '<option value="'.$val['id'].'" '.$selected.'>'.$val['letter'].'</option>';
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
                    $html .=  Testcategory::getCategoryNamesForQuestion($objQuestionTmp->id);
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
}
