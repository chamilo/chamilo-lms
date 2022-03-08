<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\TrackEExerciseConfirmation;
use Chamilo\CoreBundle\Entity\TrackEHotspot;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CExerciseCategory;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizRelQuestionCategory;
use ChamiloSession as Session;

/**
 * @author Olivier Brouckaert
 * @author Julio Montoya Cleaning exercises
 * @author Hubert Borderiou #294
 */
class Exercise
{
    public const PAGINATION_ITEMS_PER_PAGE = 20;
    public $iId;
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
    public $questionList; // array with the list of this exercise's questions
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
    public $lpList = [];
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
    public $questionFeedbackEnabled = false;
    public $questionTypeWithFeedback;
    public $showPreviousButton;
    public $notifications;
    public $export = false;
    public $autolaunch;
    public $exerciseCategoryId;
    public $pageResultConfiguration;
    public $hideQuestionNumber;
    public $preventBackwards;
    public $currentQuestion;
    public $hideComment;
    public $hideNoAnswer;
    public $hideExpectedAnswer;
    public $forceShowExpectedChoiceColumn;
    public $disableHideCorrectAnsweredQuestions;

    /**
     * @param int $courseId
     */
    public function __construct($courseId = 0)
    {
        $this->iId = 0;
        $this->id = 0;
        $this->exercise = '';
        $this->description = '';
        $this->sound = '';
        $this->type = ALL_ON_ONE_PAGE;
        $this->random = 0;
        $this->random_answers = 0;
        $this->active = 1;
        $this->questionList = [];
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
        $this->pass_percentage = 0;
        $this->modelType = 1;
        $this->questionSelectionType = EX_Q_SELECTION_ORDERED;
        $this->endButton = 0;
        $this->scoreTypeModel = 0;
        $this->globalCategoryId = null;
        $this->notifications = [];
        $this->exerciseCategoryId = 0;
        $this->pageResultConfiguration = null;
        $this->hideQuestionNumber = 0;
        $this->preventBackwards = 0;
        $this->hideComment = false;
        $this->hideNoAnswer = false;
        $this->hideExpectedAnswer = false;
        $this->disableHideCorrectAnsweredQuestions = false;

        if (!empty($courseId)) {
            $courseInfo = api_get_course_info_by_id($courseId);
        } else {
            $courseInfo = api_get_course_info();
        }
        $this->course_id = $courseInfo['real_id'];
        $this->course = $courseInfo;
        $this->sessionId = api_get_session_id();

        // ALTER TABLE c_quiz_question ADD COLUMN feedback text;
        $this->questionFeedbackEnabled = api_get_configuration_value('allow_quiz_question_feedback');
        $this->showPreviousButton = true;
    }

    /**
     * Reads exercise information from the data base.
     *
     * @param int  $id                - exercise Id
     * @param bool $parseQuestionList
     *
     * @return bool - true if exercise exists, otherwise false
     */
    public function read($id, $parseQuestionList = true)
    {
        $table = Database::get_course_table(TABLE_QUIZ_TEST);

        $id = (int) $id;
        if (empty($this->course_id) || empty($id)) {
            return false;
        }

        $sql = "SELECT * FROM $table
                WHERE iid = $id";
        $result = Database::query($sql);

        // if the exercise has been found
        if ($object = Database::fetch_object($result)) {
            $this->id = $this->iId = (int) $object->iid;
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
            //$this->sessionId = $object->session_id;
            $this->propagate_neg = $object->propagate_neg;
            $this->saveCorrectAnswers = $object->save_correct_answers;
            $this->randomByCat = $object->random_by_category;
            $this->text_when_finished = $object->text_when_finished;
            $this->display_category_name = $object->display_category_name;
            $this->pass_percentage = $object->pass_percentage;
            $this->is_gradebook_locked = api_resource_is_locked_by_gradebook($id, LINK_EXERCISE);
            $this->review_answers = isset($object->review_answers) && 1 == $object->review_answers ? true : false;
            $this->globalCategoryId = isset($object->global_category_id) ? $object->global_category_id : null;
            $this->questionSelectionType = isset($object->question_selection_type) ? (int) $object->question_selection_type : null;
            $this->hideQuestionTitle = isset($object->hide_question_title) ? (int) $object->hide_question_title : 0;
            $this->autolaunch = isset($object->autolaunch) ? (int) $object->autolaunch : 0;
            $this->exerciseCategoryId = isset($object->exercise_category_id) ? (int) $object->exercise_category_id : null;
            $this->preventBackwards = isset($object->prevent_backwards) ? (int) $object->prevent_backwards : 0;
            $this->exercise_was_added_in_lp = false;
            $this->lpList = [];
            $this->notifications = [];
            if (!empty($object->notifications)) {
                $this->notifications = explode(',', $object->notifications);
            }

            if (!empty($object->page_result_configuration)) {
                //$this->pageResultConfiguration = $object->page_result_configuration;
            }

            $this->hideQuestionNumber = 1 == $object->hide_question_number;

            if (isset($object->show_previous_button)) {
                $this->showPreviousButton = 1 == $object->show_previous_button ? true : false;
            }

            $list = self::getLpListFromExercise($id, $this->course_id);
            if (!empty($list)) {
                $this->exercise_was_added_in_lp = true;
                $this->lpList = $list;
            }

            $this->force_edit_exercise_in_lp = api_get_configuration_value('force_edit_exercise_in_lp');
            $this->edit_exercise_in_lp = true;
            if ($this->exercise_was_added_in_lp) {
                $this->edit_exercise_in_lp = true == $this->force_edit_exercise_in_lp;
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
                $this->setQuestionList(true);
            }

            //overload questions list with recorded questions list
            //load questions only for exercises of type 'one question per page'
            //this is needed only is there is no questions

            // @todo not sure were in the code this is used somebody mess with the exercise tool
            // @todo don't know who add that config and why $_configuration['live_exercise_tracking']
            /*global $_configuration, $questionList;
            if ($this->type == ONE_PER_PAGE && $_SERVER['REQUEST_METHOD'] != 'POST'
                && defined('QUESTION_LIST_ALREADY_LOGGED') &&
                isset($_configuration['live_exercise_tracking']) && $_configuration['live_exercise_tracking']
            ) {
                $this->questionList = $questionList;
            }*/

            return true;
        }

        return false;
    }

    public function getCutTitle(): string
    {
        $title = $this->getUnformattedTitle();

        return cut($title, EXERCISE_MAX_NAME_SIZE);
    }

    public function getId()
    {
        return (int) $this->iId;
    }

    /**
     * returns the exercise title.
     *
     * @param bool $unformattedText Optional. Get the title without HTML tags
     *
     * @return string - exercise title
     */
    public function selectTitle($unformattedText = false)
    {
        if ($unformattedText) {
            return $this->getUnformattedTitle();
        }

        return $this->exercise;
    }

    /**
     * returns the number of attempts setted.
     *
     * @return int - exercise attempts
     */
    public function selectAttempts()
    {
        return $this->attempts;
    }

    /**
     * Returns the number of FeedbackType
     *  0: Feedback , 1: DirectFeedback, 2: NoFeedback.
     *
     * @return int - exercise attempts
     */
    public function getFeedbackType()
    {
        return (int) $this->feedback_type;
    }

    /**
     * returns the time limit.
     *
     * @return int
     */
    public function selectTimeLimit()
    {
        return $this->timeLimit;
    }

    /**
     * returns the exercise description.
     *
     * @return string - exercise description
     */
    public function selectDescription()
    {
        return $this->description;
    }

    /**
     * returns the exercise sound file.
     */
    public function getSound()
    {
        return $this->sound;
    }

    /**
     * returns the exercise type.
     *
     * @return int - exercise type
     *
     * @author Olivier Brouckaert
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
     * @return int : do we display the question category name for students
     *
     * @author hubert borderiou 30-11-11
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
     * Modify object to update the switch display_category_name.
     *
     * @param int $value is an integer 0 or 1
     *
     * @author hubert borderiou 30-11-11
     */
    public function updateDisplayCategoryName($value)
    {
        $this->display_category_name = $value;
    }

    /**
     * @return string html text : the text to display ay the end of the test
     *
     * @author hubert borderiou 28-11-11
     */
    public function getTextWhenFinished()
    {
        return $this->text_when_finished;
    }

    /**
     * @param string $text
     *
     * @author hubert borderiou 28-11-11
     */
    public function updateTextWhenFinished($text)
    {
        $this->text_when_finished = $text;
    }

    /**
     * return 1 or 2 if randomByCat.
     *
     * @return int - quiz random by category
     *
     * @author hubert borderiou
     */
    public function getRandomByCategory()
    {
        return $this->randomByCat;
    }

    /**
     * return 0 if no random by cat
     * return 1 if random by cat, categories shuffled
     * return 2 if random by cat, categories sorted by alphabetic order.
     *
     * @return int - quiz random by category
     *
     * @author hubert borderiou
     */
    public function isRandomByCat()
    {
        $res = EXERCISE_CATEGORY_RANDOM_DISABLED;
        if (EXERCISE_CATEGORY_RANDOM_SHUFFLED == $this->randomByCat) {
            $res = EXERCISE_CATEGORY_RANDOM_SHUFFLED;
        } elseif (EXERCISE_CATEGORY_RANDOM_ORDERED == $this->randomByCat) {
            $res = EXERCISE_CATEGORY_RANDOM_ORDERED;
        }

        return $res;
    }

    /**
     * return nothing
     * update randomByCat value for object.
     *
     * @param int $random
     *
     * @author hubert borderiou
     */
    public function updateRandomByCat($random)
    {
        $this->randomByCat = EXERCISE_CATEGORY_RANDOM_DISABLED;
        if (in_array(
            $random,
            [
                EXERCISE_CATEGORY_RANDOM_SHUFFLED,
                EXERCISE_CATEGORY_RANDOM_ORDERED,
                EXERCISE_CATEGORY_RANDOM_DISABLED,
            ]
        )) {
            $this->randomByCat = $random;
        }
    }

    /**
     * Tells if questions are selected randomly, and if so returns the draws.
     *
     * @return int - results disabled exercise
     *
     * @author Carlos Vargas
     */
    public function selectResultsDisabled()
    {
        return $this->results_disabled;
    }

    /**
     * tells if questions are selected randomly, and if so returns the draws.
     *
     * @return bool
     *
     * @author Olivier Brouckaert
     */
    public function isRandom()
    {
        $isRandom = false;
        // "-1" means all questions will be random
        if ($this->random > 0 || -1 == $this->random) {
            $isRandom = true;
        }

        return $isRandom;
    }

    /**
     * returns random answers status.
     *
     * @author Juan Carlos Rana
     */
    public function getRandomAnswers()
    {
        return $this->random_answers;
    }

    /**
     * Same as isRandom() but has a name applied to values different than 0 or 1.
     *
     * @return int
     */
    public function getShuffle()
    {
        return $this->random;
    }

    /**
     * returns the exercise status (1 = enabled ; 0 = disabled).
     *
     * @return int - 1 if enabled, otherwise 0
     *
     * @author Olivier Brouckaert
     */
    public function selectStatus()
    {
        return $this->active;
    }

    /**
     * If false the question list will be managed as always if true
     * the question will be filtered
     * depending of the exercise settings (table c_quiz_rel_category).
     *
     * @param bool $status active or inactive grouping
     */
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
        $this->hideQuestionTitle = (int) $value;
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
        $this->scoreTypeModel = (int) $value;
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
        $this->globalCategoryId = (int) $value;
    }

    /**
     * @param int    $start
     * @param int    $limit
     * @param string $sidx
     * @param string $sord
     * @param array  $whereCondition
     * @param array  $extraFields
     *
     * @return array
     */
    public function getQuestionListPagination(
        $start,
        $limit,
        $sidx,
        $sord,
        $whereCondition = [],
        $extraFields = []
    ) {
        if (!empty($this->id)) {
            $category_list = TestCategory::getListOfCategoriesNameForTest(
                $this->id,
                false
            );
            $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
            $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);

            $sql = "SELECT q.iid
                    FROM $TBL_EXERCICE_QUESTION e
                    INNER JOIN $TBL_QUESTIONS  q
                    ON (e.question_id = q.iid)
					WHERE e.quiz_id	= '".$this->id."' ";

            $orderCondition = ' ORDER BY question_order ';

            if (!empty($sidx) && !empty($sord)) {
                if ('question' === $sidx) {
                    if (in_array(strtolower($sord), ['desc', 'asc'])) {
                        $orderCondition = " ORDER BY `q.$sidx` $sord";
                    }
                }
            }

            $sql .= $orderCondition;
            $limitCondition = null;
            if (isset($start) && isset($limit)) {
                $start = (int) $start;
                $limit = (int) $limit;
                $limitCondition = " LIMIT $start, $limit";
            }
            $sql .= $limitCondition;
            $result = Database::query($sql);
            $questions = [];
            if (Database::num_rows($result)) {
                if (!empty($extraFields)) {
                    $extraFieldValue = new ExtraFieldValue('question');
                }
                while ($question = Database::fetch_array($result, 'ASSOC')) {
                    /** @var Question $objQuestionTmp */
                    $objQuestionTmp = Question::read($question['iid']);
                    $category_labels = '';
                    // @todo not implemented in 1.11.x
                    /*$category_labels = TestCategory::return_category_labels(
                        $objQuestionTmp->category_list,
                        $category_list
                    );*/

                    if (empty($category_labels)) {
                        $category_labels = '-';
                    }

                    // Question type
                    $typeImg = $objQuestionTmp->getTypePicture();
                    $typeExpl = $objQuestionTmp->getExplanation();

                    $question_media = null;
                    if (!empty($objQuestionTmp->parent_id)) {
                        // @todo not implemented in 1.11.x
                        //$objQuestionMedia = Question::read($objQuestionTmp->parent_id);
                        //$question_media = Question::getMediaLabel($objQuestionMedia->question);
                    }

                    $questionType = Display::tag(
                        'div',
                        Display::return_icon($typeImg, $typeExpl, [], ICON_SIZE_MEDIUM).$question_media
                    );

                    $question = [
                        'id' => $question['iid'],
                        'question' => $objQuestionTmp->selectTitle(),
                        'type' => $questionType,
                        'category' => Display::tag(
                            'div',
                            '<a href="#" style="padding:0px; margin:0px;">'.$category_labels.'</a>'
                        ),
                        'score' => $objQuestionTmp->selectWeighting(),
                        'level' => $objQuestionTmp->level,
                    ];

                    if (!empty($extraFields)) {
                        foreach ($extraFields as $extraField) {
                            $value = $extraFieldValue->get_values_by_handler_and_field_id(
                                $question['id'],
                                $extraField['id']
                            );
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
     * Get question count per exercise from DB (any special treatment).
     *
     * @return int
     */
    public function getQuestionCount()
    {
        $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $sql = "SELECT count(q.iid) as count
                FROM $TBL_EXERCICE_QUESTION e
                INNER JOIN $TBL_QUESTIONS q
                ON (e.question_id = q.iid)
                WHERE
                    e.quiz_id = ".$this->getId();
        $result = Database::query($sql);

        $count = 0;
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result);
            $count = (int) $row['count'];
        }

        return $count;
    }

    /**
     * @return array
     */
    public function getQuestionOrderedListByName()
    {
        if (empty($this->course_id) || empty($this->getId())) {
            return [];
        }

        $exerciseQuestionTable = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $questionTable = Database::get_course_table(TABLE_QUIZ_QUESTION);

        // Getting question list from the order (question list drag n drop interface ).
        $sql = "SELECT e.question_id
                FROM $exerciseQuestionTable e
                INNER JOIN $questionTable q
                ON (e.question_id= q.iid)
                WHERE
                    e.quiz_id = '".$this->getId()."'
                ORDER BY q.question";
        $result = Database::query($sql);
        $list = [];
        if (Database::num_rows($result)) {
            $list = Database::store_result($result, 'ASSOC');
        }

        return $list;
    }

    /**
     * Selecting question list depending in the exercise-category
     * relationship (category table in exercise settings).
     *
     * @param array $question_list
     * @param int   $questionSelectionType
     *
     * @return array
     */
    public function getQuestionListWithCategoryListFilteredByCategorySettings(
        $question_list,
        $questionSelectionType
    ) {
        $result = [
            'question_list' => [],
            'category_with_questions_list' => [],
        ];

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
                    $this->getId(),
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
                    $this->getId(),
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
                    'title ASC',
                    false,
                    true
                );
                $questions_by_category = TestCategory::getQuestionsByCat(
                    $this->getId(),
                    $question_list,
                    $categoriesAddedInExercise
                );
                $questionsByCategoryMandatory = [];
                if (EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM == $this->getQuestionSelectionType() &&
                    api_get_configuration_value('allow_mandatory_question_in_category')
                ) {
                    $questionsByCategoryMandatory = TestCategory::getQuestionsByCat(
                        $this->id,
                        $question_list,
                        $categoriesAddedInExercise,
                        true
                    );
                }
                $question_list = $this->pickQuestionsPerCategory(
                    $categoriesAddedInExercise,
                    $question_list,
                    $questions_by_category,
                    true,
                    true,
                    $questionsByCategoryMandatory
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
                    $this->getId(),
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
            case EX_Q_SELECTION_CATEGORIES_ORDERED_BY_PARENT_QUESTIONS_ORDERED: // 9
                $categoriesAddedInExercise = $cat->getCategoryExerciseTree(
                    $this,
                    $this->course['real_id'],
                    'root ASC, lft ASC',
                    false,
                    true
                );
                $questions_by_category = TestCategory::getQuestionsByCat(
                    $this->getId(),
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
                    $this->getId(),
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

        $result['question_list'] = isset($question_list) ? $question_list : [];
        $result['category_with_questions_list'] = isset($questions_by_category) ? $questions_by_category : [];
        $parentsLoaded = [];
        // Adding category info in the category list with question list:
        if (!empty($questions_by_category)) {
            $newCategoryList = [];
            $em = Database::getManager();
            $repo = $em->getRepository(CQuizRelQuestionCategory::class);

            foreach ($questions_by_category as $categoryId => $questionList) {
                $category = new TestCategory();
                $cat = (array) $category->getCategory($categoryId);
                if ($cat) {
                    $cat['iid'] = $cat['id'];
                }

                $categoryParentInfo = null;
                // Parent is not set no loop here
                if (isset($cat['parent_id']) && !empty($cat['parent_id'])) {
                    /** @var CQuizRelQuestionCategory $categoryEntity */
                    if (!isset($parentsLoaded[$cat['parent_id']])) {
                        $categoryEntity = $em->find(CQuizRelQuestionCategory::class, $cat['parent_id']);
                        $parentsLoaded[$cat['parent_id']] = $categoryEntity;
                    } else {
                        $categoryEntity = $parentsLoaded[$cat['parent_id']];
                    }
                    $path = $repo->getPath($categoryEntity);

                    $index = 0;
                    if ($this->categoryMinusOne) {
                        //$index = 1;
                    }

                    /** @var CQuizRelQuestionCategory $categoryParent */
                    // @todo not implemented in 1.11.x
                    /*foreach ($path as $categoryParent) {
                        $visibility = $categoryParent->getVisibility();
                        if (0 == $visibility) {
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
                    }*/
                }
                $cat['parent_info'] = $categoryParentInfo;
                $newCategoryList[$categoryId] = [
                    'category' => $cat,
                    'question_list' => $questionList,
                ];
            }

            $result['category_with_questions_list'] = $newCategoryList;
        }

        return $result;
    }

    /**
     * returns the array with the question ID list.
     *
     * @param bool $fromDatabase Whether the results should be fetched in the database or just from memory
     * @param bool $adminView    Whether we should return all questions (admin view) or
     *                           just a list limited by the max number of random questions
     *
     * @return array - question ID list
     */
    public function selectQuestionList($fromDatabase = false, $adminView = false)
    {
        //var_dump($this->getId());exit;
        if ($fromDatabase && !empty($this->getId())) {
            $nbQuestions = $this->getQuestionCount();

            $questionSelectionType = $this->getQuestionSelectionType();

            switch ($questionSelectionType) {
                case EX_Q_SELECTION_ORDERED:
                    $questionList = $this->getQuestionOrderedList($adminView);

                    break;
                case EX_Q_SELECTION_RANDOM:
                    // Not a random exercise, or if there are not at least 2 questions
                    if (0 == $this->random || $nbQuestions < 2) {
                        $questionList = $this->getQuestionOrderedList($adminView);
                    } else {
                        $questionList = $this->getRandomList($adminView);
                    }

                    break;
                default:
                    $questionList = $this->getQuestionOrderedList($adminView);
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
     * returns the number of questions in this exercise.
     *
     * @return int - number of questions
     */
    public function selectNbrQuestions()
    {
        return count($this->questionList);
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
    public function getSaveCorrectAnswers()
    {
        return $this->saveCorrectAnswers;
    }

    /**
     * Selects questions randomly in the question list.
     *
     * @param bool $adminView Whether we should return all
     *                        questions (admin view) or just a list limited by the max number of random questions
     *
     * @return array - if the exercise is not set to take questions randomly, returns the question list
     *               without randomizing, otherwise, returns the list with questions selected randomly
     *
     * @author Olivier Brouckaert
     * @author Hubert Borderiou 15 nov 2011
     */
    public function getRandomList($adminView = false)
    {
        $quizRelQuestion = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $question = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $random = isset($this->random) && !empty($this->random) ? $this->random : 0;

        // Random with limit
        $randomLimit = " ORDER BY RAND() LIMIT $random";

        // Random with no limit
        if (-1 == $random) {
            $randomLimit = ' ORDER BY RAND() ';
        }

        // Admin see the list in default order
        if (true === $adminView) {
            // If viewing it as admin for edition, don't show it randomly, use title + id
            $randomLimit = 'ORDER BY e.question_order';
        }

        $sql = "SELECT e.question_id
                FROM $quizRelQuestion e
                INNER JOIN $question q
                ON (e.question_id= q.iid)
                WHERE
                    e.quiz_id = '".$this->getId()."'
                    $randomLimit ";
        $result = Database::query($sql);
        $questionList = [];
        while ($row = Database::fetch_object($result)) {
            $questionList[] = $row->question_id;
        }

        return $questionList;
    }

    /**
     * returns 'true' if the question ID is in the question list.
     *
     * @param int $questionId - question ID
     *
     * @return bool - true if in the list, otherwise false
     *
     * @author Olivier Brouckaert
     */
    public function isInList($questionId)
    {
        $inList = false;
        if (is_array($this->questionList)) {
            $inList = in_array($questionId, $this->questionList);
        }

        return $inList;
    }

    /**
     * If current exercise has a question.
     *
     * @param int $questionId
     *
     * @return int
     */
    public function hasQuestion($questionId)
    {
        $questionId = (int) $questionId;

        $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $sql = "SELECT q.iid
                FROM $TBL_EXERCICE_QUESTION e
                INNER JOIN $TBL_QUESTIONS q
                ON (e.question_id = q.iid)
                WHERE
                    q.iid = $questionId AND
                    e.quiz_id = ".$this->getId();

        $result = Database::query($sql);

        return Database::num_rows($result) > 0;
    }

    public function hasQuestionWithType($type)
    {
        $type = (int) $type;

        $table = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $tableQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $sql = "SELECT q.iid
                FROM $table e
                INNER JOIN $tableQuestion q
                ON (e.question_id = q.iid)
                WHERE
                    q.type = $type AND
                    e.quiz_id = ".$this->getId();

        $result = Database::query($sql);

        return Database::num_rows($result) > 0;
    }

    public function hasQuestionWithTypeNotInList(array $questionTypeList)
    {
        if (empty($questionTypeList)) {
            return false;
        }

        $questionTypeToString = implode("','", array_map('intval', $questionTypeList));

        $table = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $tableQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $sql = "SELECT q.iid
                FROM $table e
                INNER JOIN $tableQuestion q
                ON (e.question_id = q.iid)
                WHERE
                    q.type NOT IN ('$questionTypeToString')  AND

                    e.quiz_id = ".$this->getId();

        $result = Database::query($sql);

        return Database::num_rows($result) > 0;
    }

    /**
     * changes the exercise title.
     *
     * @param string $title - exercise title
     *
     * @author Olivier Brouckaert
     */
    public function updateTitle($title)
    {
        $this->title = $this->exercise = $title;
    }

    /**
     * changes the exercise max attempts.
     *
     * @param int $attempts - exercise max attempts
     */
    public function updateAttempts($attempts)
    {
        $this->attempts = $attempts;
    }

    /**
     * changes the exercise feedback type.
     *
     * @param int $feedback_type
     */
    public function updateFeedbackType($feedback_type)
    {
        $this->feedback_type = $feedback_type;
    }

    /**
     * changes the exercise description.
     *
     * @param string $description - exercise description
     *
     * @author Olivier Brouckaert
     */
    public function updateDescription($description)
    {
        $this->description = $description;
    }

    /**
     * changes the exercise expired_time.
     *
     * @param int $expired_time The expired time of the quiz
     *
     * @author Isaac flores
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
     * @param int $value
     */
    public function updateSaveCorrectAnswers($value)
    {
        $this->saveCorrectAnswers = (int) $value;
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
    public function setEmailNotificationTemplateToUser($text)
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
        $this->endButton = (int) $value;
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
        $this->modelType = (int) $value;
    }

    /**
     * @param int $value
     */
    public function setQuestionSelectionType($value)
    {
        $this->questionSelectionType = (int) $value;
    }

    /**
     * @return int
     */
    public function getQuestionSelectionType()
    {
        return (int) $this->questionSelectionType;
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
     * changes the exercise sound file.
     *
     * @param string $sound  - exercise sound file
     * @param string $delete - ask to delete the file
     *
     * @author Olivier Brouckaert
     */
    public function updateSound($sound, $delete)
    {
        global $audioPath, $documentPath;
        $TBL_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);

        if ($sound['size'] &&
            (strstr($sound['type'], 'audio') || strstr($sound['type'], 'video'))
        ) {
            $this->sound = $sound['name'];

            if (@move_uploaded_file($sound['tmp_name'], $audioPath.'/'.$this->sound)) {
                $sql = "SELECT 1 FROM $TBL_DOCUMENT
                        WHERE
                            c_id = ".$this->course_id." AND
                            path = '".str_replace($documentPath, '', $audioPath).'/'.$this->sound."'";
                $result = Database::query($sql);

                if (!Database::num_rows($result)) {
                    DocumentManager::addDocument(
                        $this->course,
                        str_replace($documentPath, '', $audioPath).'/'.$this->sound,
                        'file',
                        $sound['size'],
                        $sound['name']
                    );
                }
            }
        } elseif ($delete && is_file($audioPath.'/'.$this->sound)) {
            $this->sound = '';
        }
    }

    /**
     * changes the exercise type.
     *
     * @param int $type - exercise type
     *
     * @author Olivier Brouckaert
     */
    public function updateType($type)
    {
        $this->type = $type;
    }

    /**
     * sets to 0 if questions are not selected randomly
     * if questions are selected randomly, sets the draws.
     *
     * @param int $random - 0 if not random, otherwise the draws
     *
     * @author Olivier Brouckaert
     */
    public function setRandom($random)
    {
        $this->random = $random;
    }

    /**
     * sets to 0 if answers are not selected randomly
     * if answers are selected randomly.
     *
     * @param int $random_answers - random answers
     *
     * @author Juan Carlos Rana
     */
    public function updateRandomAnswers($random_answers)
    {
        $this->random_answers = $random_answers;
    }

    /**
     * enables the exercise.
     *
     * @author Olivier Brouckaert
     */
    public function enable()
    {
        $this->active = 1;
    }

    /**
     * disables the exercise.
     *
     * @author Olivier Brouckaert
     */
    public function disable()
    {
        $this->active = 0;
    }

    /**
     * Set disable results.
     */
    public function disable_results()
    {
        $this->results_disabled = true;
    }

    /**
     * Enable results.
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
        $this->results_disabled = (int) $results_disabled;
    }

    /**
     * updates the exercise in the data base.
     *
     * @author Olivier Brouckaert
     */
    public function save()
    {
        $id = $this->getId();
        $title = $this->exercise;
        $description = $this->description;
        $sound = $this->sound;
        $type = $this->type;
        $attempts = isset($this->attempts) ? (int) $this->attempts : 0;
        $feedback_type = isset($this->feedback_type) ? (int) $this->feedback_type : 0;
        $random = $this->random;
        $random_answers = $this->random_answers;
        $active = $this->active;
        $propagate_neg = (int) $this->propagate_neg;
        $saveCorrectAnswers = isset($this->saveCorrectAnswers) ? (int) $this->saveCorrectAnswers : 0;
        $review_answers = isset($this->review_answers) && $this->review_answers ? 1 : 0;
        $randomByCat = (int) $this->randomByCat;
        $text_when_finished = $this->text_when_finished;
        $display_category_name = (int) $this->display_category_name;
        $pass_percentage = (int) $this->pass_percentage;

        // If direct we do not show results
        $results_disabled = (int) $this->results_disabled;
        if (in_array($feedback_type, [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])) {
            $results_disabled = 0;
        }
        $expired_time = (int) $this->expired_time;

        $repo = Container::getQuizRepository();
        $repoCategory = Container::getExerciseCategoryRepository();

        // we prepare date in the database using the api_get_utc_datetime() function
        $start_time = null;
        if (!empty($this->start_time)) {
            $start_time = $this->start_time;
        }

        $end_time = null;
        if (!empty($this->end_time)) {
            $end_time = $this->end_time;
        }

        // Exercise already exists
        if ($id) {
            /** @var CQuiz $exercise */
            $exercise = $repo->find($id);
        } else {
            $exercise = new CQuiz();
        }

        $exercise
            ->setStartTime($start_time)
            ->setEndTime($end_time)
            ->setTitle($title)
            ->setDescription($description)
            ->setSound($sound)
            ->setType($type)
            ->setRandom((int) $random)
            ->setRandomAnswers((bool) $random_answers)
            ->setActive((int) $active)
            ->setResultsDisabled($results_disabled)
            ->setMaxAttempt($attempts)
            ->setFeedbackType($feedback_type)
            ->setExpiredTime($expired_time)
            ->setReviewAnswers($review_answers)
            ->setRandomByCategory($randomByCat)
            ->setTextWhenFinished($text_when_finished)
            ->setDisplayCategoryName($display_category_name)
            ->setPassPercentage($pass_percentage)
            ->setSaveCorrectAnswers($saveCorrectAnswers)
            ->setPropagateNeg($propagate_neg)
            ->setHideQuestionTitle(1 === (int) $this->getHideQuestionTitle())
            ->setQuestionSelectionType($this->getQuestionSelectionType())
            ->setHideQuestionNumber((int) $this->hideQuestionNumber)
        ;

        $allow = api_get_configuration_value('allow_exercise_categories');
        if (true === $allow && !empty($this->getExerciseCategoryId())) {
            $exercise->setExerciseCategory($repoCategory->find($this->getExerciseCategoryId()));
        }

        $exercise->setPreventBackwards($this->getPreventBackwards());

        $allow = api_get_configuration_value('allow_quiz_show_previous_button_setting');
        if (true === $allow) {
            $exercise->setShowPreviousButton($this->showPreviousButton());
        }

        $allow = api_get_configuration_value('allow_notification_setting_per_exercise');
        if (true === $allow) {
            $notifications = $this->getNotifications();
            if (!empty($notifications)) {
                $notifications = implode(',', $notifications);
                $exercise->setNotifications($notifications);
            }
        }

        if (!empty($this->pageResultConfiguration)) {
            $exercise->setPageResultConfiguration($this->pageResultConfiguration);
        }

        $em = Database::getManager();

        if ($id) {
            $repo->updateNodeForResource($exercise);

            if ('true' === api_get_setting('search_enabled')) {
                $this->search_engine_edit();
            }
            $em->persist($exercise);
            $em->flush();
        } else {
            // Creates a new exercise
            $courseEntity = api_get_course_entity($this->course_id);
            $exercise
                ->setParent($courseEntity)
                ->addCourseLink($courseEntity, api_get_session_entity());
            $em->persist($exercise);
            $em->flush();
            $id = $exercise->getIid();
            $this->iId = $this->id = $id;
            if ($id) {
                if ('true' === api_get_setting('search_enabled') && extension_loaded('xapian')) {
                    $this->search_engine_save();
                }
            }
        }

        $this->save_categories_in_exercise($this->categories);

        return $id;
    }

    /**
     * Updates question position.
     *
     * @return bool
     */
    public function update_question_positions()
    {
        $table = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        // Fixes #3483 when updating order
        $questionList = $this->selectQuestionList(true);

        if (empty($this->getId())) {
            return false;
        }

        if (!empty($questionList)) {
            foreach ($questionList as $position => $questionId) {
                $position = (int) $position;
                $questionId = (int) $questionId;
                $sql = "UPDATE $table SET
                            question_order = $position
                        WHERE
                            question_id = $questionId AND
                            quiz_id= ".$this->getId();
                Database::query($sql);
            }
        }

        return true;
    }

    /**
     * Adds a question into the question list.
     *
     * @param int $questionId - question ID
     *
     * @return bool - true if the question has been added, otherwise false
     *
     * @author Olivier Brouckaert
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
     * removes a question from the question list.
     *
     * @param int $questionId - question ID
     *
     * @return bool - true if the question has been removed, otherwise false
     *
     * @author Olivier Brouckaert
     */
    public function removeFromList($questionId)
    {
        // searches the position of the question ID in the list
        $pos = array_search($questionId, $this->questionList);
        // question not found
        if (false === $pos) {
            return false;
        } else {
            // dont reduce the number of random question if we use random by category option, or if
            // random all questions
            if ($this->isRandom() && 0 == $this->isRandomByCat()) {
                if (count($this->questionList) >= $this->random && $this->random > 0) {
                    $this->random--;
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
     * Notice : leaves the question in the data base.
     *
     * @author Olivier Brouckaert
     */
    public function delete()
    {
        $limitTeacherAccess = api_get_configuration_value('limit_exercise_teacher_access');

        if ($limitTeacherAccess && !api_is_platform_admin()) {
            return false;
        }

        $exerciseId = $this->iId;

        $repo = Container::getQuizRepository();
        $exercise = $repo->find($exerciseId);

        if (null === $exercise) {
            return false;
        }

        $locked = api_resource_is_locked_by_gradebook(
            $exerciseId,
            LINK_EXERCISE
        );

        if ($locked) {
            return false;
        }

        $table = Database::get_course_table(TABLE_QUIZ_TEST);
        $sql = "UPDATE $table SET active='-1'
                WHERE iid = $exerciseId";
        Database::query($sql);

        $repo->softDelete($exercise);

        SkillModel::deleteSkillsFromItem($exerciseId, ITEM_TYPE_EXERCISE);

        if ('true' === api_get_setting('search_enabled') &&
            extension_loaded('xapian')
        ) {
            $this->search_engine_delete();
        }

        $linkInfo = GradebookUtils::isResourceInCourseGradebook(
            $this->course['code'],
            LINK_EXERCISE,
            $exerciseId,
            $this->sessionId
        );
        if (false !== $linkInfo) {
            GradebookUtils::remove_resource_from_course_gradebook($linkInfo['id']);
        }

        return true;
    }

    /**
     * Creates the form to create / edit an exercise.
     *
     * @param FormValidator $form
     * @param string        $type
     */
    public function createForm($form, $type = 'full')
    {
        if (empty($type)) {
            $type = 'full';
        }

        // Form title
        $form_title = get_lang('Create a new test');
        if (!empty($_GET['id'])) {
            $form_title = get_lang('Edit test name and settings');
        }

        $form->addHeader($form_title);

        // Title.
        if (api_get_configuration_value('save_titles_as_html')) {
            $form->addHtmlEditor(
                'exerciseTitle',
                get_lang('Test name'),
                false,
                false,
                ['ToolbarSet' => 'TitleAsHtml']
            );
        } else {
            $form->addElement(
                'text',
                'exerciseTitle',
                get_lang('Test name'),
                ['id' => 'exercise_title']
            );
        }

        $form->addElement('advanced_settings', 'advanced_params', get_lang('Advanced settings'));
        $form->addElement('html', '<div id="advanced_params_options" style="display:none">');

        if (api_get_configuration_value('allow_exercise_categories')) {
            $categoryManager = new ExerciseCategoryManager();
            $categories = $categoryManager->getCategories(api_get_course_int_id());
            $options = [];
            if (!empty($categories)) {
                /** @var CExerciseCategory $category */
                foreach ($categories as $category) {
                    $options[$category->getId()] = $category->getName();
                }
            }

            $form->addSelect(
                'exercise_category_id',
                get_lang('Category'),
                $options,
                ['placeholder' => get_lang('Please select an option')]
            );
        }

        $editor_config = [
            'ToolbarSet' => 'TestQuestionDescription',
            'Width' => '100%',
            'Height' => '150',
        ];

        if (is_array($type)) {
            $editor_config = array_merge($editor_config, $type);
        }

        $form->addHtmlEditor(
            'exerciseDescription',
            get_lang('Give a context to the test'),
            false,
            false,
            $editor_config
        );

        $skillList = [];
        if ('full' === $type) {
            // Can't modify a DirectFeedback question.
            if (!in_array($this->getFeedbackType(), [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])) {
                $this->setResultFeedbackGroup($form);

                // Type of results display on the final page
                $this->setResultDisabledGroup($form);

                // Type of questions disposition on page
                $radios = [];
                $radios[] = $form->createElement(
                    'radio',
                    'exerciseType',
                    null,
                    get_lang('All questions on one page'),
                    '1',
                    [
                        'onclick' => 'check_per_page_all()',
                        'id' => 'option_page_all',
                    ]
                );
                $radios[] = $form->createElement(
                    'radio',
                    'exerciseType',
                    null,
                    get_lang('One question by page'),
                    '2',
                    [
                        'onclick' => 'check_per_page_one()',
                        'id' => 'option_page_one',
                    ]
                );

                $form->addGroup($radios, null, get_lang('Questions per page'));
            } else {
                // if is Direct feedback but has not questions we can allow to modify the question type
                if (empty($this->iId) || 0 === $this->getQuestionCount()) {
                    $this->setResultFeedbackGroup($form);
                    $this->setResultDisabledGroup($form);

                    // Type of questions disposition on page
                    $radios = [];
                    $radios[] = $form->createElement(
                        'radio',
                        'exerciseType',
                        null,
                        get_lang('All questions on one page'),
                        '1'
                    );
                    $radios[] = $form->createElement(
                        'radio',
                        'exerciseType',
                        null,
                        get_lang('One question by page'),
                        '2'
                    );
                    $form->addGroup($radios, null, get_lang('Sequential'));
                } else {
                    $this->setResultFeedbackGroup($form, true);
                    $group = $this->setResultDisabledGroup($form);
                    $group->freeze();

                    // we force the options to the DirectFeedback exercisetype
                    //$form->addElement('hidden', 'exerciseFeedbackType', $this->getFeedbackType());
                    //$form->addElement('hidden', 'exerciseType', ONE_PER_PAGE);

                    // Type of questions disposition on page
                    $radios[] = $form->createElement(
                        'radio',
                        'exerciseType',
                        null,
                        get_lang('All questions on one page'),
                        '1',
                        [
                            'onclick' => 'check_per_page_all()',
                            'id' => 'option_page_all',
                        ]
                    );
                    $radios[] = $form->createElement(
                        'radio',
                        'exerciseType',
                        null,
                        get_lang('One question by page'),
                        '2',
                        [
                            'onclick' => 'check_per_page_one()',
                            'id' => 'option_page_one',
                        ]
                    );

                    $type_group = $form->addGroup($radios, null, get_lang('Questions per page'));
                    $type_group->freeze();
                }
            }

            $option = [
                EX_Q_SELECTION_ORDERED => get_lang('Ordered by user'),
                //  Defined by user
                EX_Q_SELECTION_RANDOM => get_lang('Random'),
                // 1-10, All
                'per_categories' => '--------'.get_lang('Using categories').'----------',
                // Base (A 123 {3} B 456 {3} C 789{2} D 0{0}) --> Matrix {3, 3, 2, 0}
                EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED => get_lang(
                    'Ordered categories alphabetically with questions ordered'
                ),
                // A 123 B 456 C 78 (0, 1, all)
                EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_ORDERED => get_lang(
                    'Random categories with questions ordered'
                ),
                // C 78 B 456 A 123
                EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM => get_lang(
                    'Ordered categories alphabetically with random questions'
                ),
                // A 321 B 654 C 87
                EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM => get_lang(
                    'Random categories with random questions'
                ),
                // C 87 B 654 A 321
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
            ];

            $form->addSelect(
                'question_selection_type',
                [get_lang('Question selection type')],
                $option,
                [
                    'id' => 'questionSelection',
                    'onchange' => 'checkQuestionSelection()',
                ]
            );

            $group = [
                $form->createElement(
                    'checkbox',
                    'hide_expected_answer',
                    null,
                    get_lang('Hide expected answers column')
                ),
                $form->createElement(
                    'checkbox',
                    'hide_total_score',
                    null,
                    get_lang('Hide total score')
                ),
                $form->createElement(
                    'checkbox',
                    'hide_question_score',
                    null,
                    get_lang('Hide question score')
                ),
                $form->createElement(
                    'checkbox',
                    'hide_category_table',
                    null,
                    get_lang('Hide category table')
                ),
                $form->createElement(
                    'checkbox',
                    'hide_correct_answered_questions',
                    null,
                    get_lang('Hide correct answered questions')
                ),
            ];
            $form->addGroup($group, null, get_lang('Results and feedback page configuration'));

            $group = [
                $form->createElement('radio', 'hide_question_number', null, get_lang('Yes'), '1'),
                $form->createElement('radio', 'hide_question_number', null, get_lang('No'), '0'),
            ];
            $form->addGroup($group, null, get_lang('HideQuestionNumber'));

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

            $form->addHtml('<div id="hidden_random" style="display:'.$displayRandom.'">');
            // Number of random question.
            $max = $this->getId() > 0 ? $this->getQuestionCount() : 10;
            $option = range(0, $max);
            $option[0] = get_lang('No');
            $option[-1] = get_lang('All');
            $form->addSelect(
                'randomQuestions',
                [
                    get_lang('Random questions'),
                    get_lang('Random questionsHelp'),
                ],
                $option,
                ['id' => 'randomQuestions']
            );
            $form->addHtml('</div>');
            $form->addHtml('<div id="hidden_matrix" style="display:'.$displayMatrix.'">');

            // Category selection.
            $cat = new TestCategory();
            $cat_form = $cat->returnCategoryForm($this);
            if (empty($cat_form)) {
                $cat_form = '<span class="label label-warning">'.get_lang('No categories defined').'</span>';
            }
            $form->addElement('label', null, $cat_form);
            $form->addHtml('</div>');

            // Random answers.
            $radios_random_answers = [
                $form->createElement('radio', 'randomAnswers', null, get_lang('Yes'), '1'),
                $form->createElement('radio', 'randomAnswers', null, get_lang('No'), '0'),
            ];
            $form->addGroup($radios_random_answers, null, get_lang('Shuffle answers'));

            // Category name.
            $radio_display_cat_name = [
                $form->createElement('radio', 'display_category_name', null, get_lang('Yes'), '1'),
                $form->createElement('radio', 'display_category_name', null, get_lang('No'), '0'),
            ];
            $form->addGroup($radio_display_cat_name, null, get_lang('Display questions category'));

            // Hide question title.
            $group = [
                $form->createElement('radio', 'hide_question_title', null, get_lang('Yes'), '1'),
                $form->createElement('radio', 'hide_question_title', null, get_lang('No'), '0'),
            ];
            $form->addGroup($group, null, get_lang('Hide question title'));

            $allow = api_get_configuration_value('allow_quiz_show_previous_button_setting');

            if (true === $allow) {
                // Hide question title.
                $group = [
                    $form->createElement(
                        'radio',
                        'show_previous_button',
                        null,
                        get_lang('Yes'),
                        '1'
                    ),
                    $form->createElement(
                        'radio',
                        'show_previous_button',
                        null,
                        get_lang('No'),
                        '0'
                    ),
                ];
                $form->addGroup($group, null, get_lang('Show previous button'));
            }

            $form->addElement(
                'number',
                'exerciseAttempts',
                get_lang('max. 20 characters, e.g. <i>INNOV21</i> number of attempts'),
                null,
                ['id' => 'exerciseAttempts']
            );

            // Exercise time limit
            $form->addElement(
                'checkbox',
                'activate_start_date_check',
                null,
                get_lang('Enable start time'),
                ['onclick' => 'activate_start_date()']
            );

            if (!empty($this->start_time)) {
                $form->addElement('html', '<div id="start_date_div" style="display:block;">');
            } else {
                $form->addElement('html', '<div id="start_date_div" style="display:none;">');
            }

            $form->addElement('date_time_picker', 'start_time');
            $form->addElement('html', '</div>');
            $form->addElement(
                'checkbox',
                'activate_end_date_check',
                null,
                get_lang('Enable end time'),
                ['onclick' => 'activate_end_date()']
            );

            if (!empty($this->end_time)) {
                $form->addHtml('<div id="end_date_div" style="display:block;">');
            } else {
                $form->addHtml('<div id="end_date_div" style="display:none;">');
            }

            $form->addElement('date_time_picker', 'end_time');
            $form->addElement('html', '</div>');

            $display = 'block';
            $form->addElement(
                'checkbox',
                'propagate_neg',
                null,
                get_lang('Propagate negative results between questions')
            );

            $options = [
                '' => get_lang('Please select an option'),
                1 => get_lang('Save the correct answer for the next attempt'),
                2 => get_lang('Pre-fill with answers from previous attempt'),
            ];
            $form->addSelect(
                'save_correct_answers',
                get_lang('Save answers'),
                $options
            );

            $form->addElement('html', '<div class="clear">&nbsp;</div>');
            $form->addCheckBox('review_answers', null, get_lang('Review my answers'));
            $form->addElement('html', '<div id="divtimecontrol"  style="display:'.$display.';">');

            // Timer control
            $form->addElement(
                'checkbox',
                'enabletimercontrol',
                null,
                get_lang('Enable time control'),
                [
                    'onclick' => 'option_time_expired()',
                    'id' => 'enabletimercontrol',
                    'onload' => 'check_load_time()',
                ]
            );

            $expired_date = (int) $this->selectExpiredTime();

            if (('0' != $expired_date)) {
                $form->addElement('html', '<div id="timercontrol" style="display:block;">');
            } else {
                $form->addElement('html', '<div id="timercontrol" style="display:none;">');
            }
            $form->addText(
                'enabletimercontroltotalminutes',
                get_lang('Total duration in minutes of the test'),
                false,
                [
                    'id' => 'enabletimercontroltotalminutes',
                    'cols-size' => [2, 2, 8],
                ]
            );
            $form->addElement('html', '</div>');
            $form->addCheckBox('prevent_backwards', null, get_lang('QuizPreventBackwards'));
            $form->addElement(
                'text',
                'pass_percentage',
                [get_lang('Pass percentage'), null, '%'],
                ['id' => 'pass_percentage']
            );

            $form->addRule('pass_percentage', get_lang('Numeric'), 'numeric');
            $form->addRule('pass_percentage', get_lang('Value is too small.'), 'min_numeric_length', 0);
            $form->addRule('pass_percentage', get_lang('Value is too big.'), 'max_numeric_length', 100);

            // add the text_when_finished textbox
            $form->addHtmlEditor(
                'text_when_finished',
                get_lang('Text appearing at the end of the test'),
                false,
                false,
                $editor_config
            );

            $allow = api_get_configuration_value('allow_notification_setting_per_exercise');
            if (true === $allow) {
                $settings = ExerciseLib::getNotificationSettings();
                $group = [];
                foreach ($settings as $itemId => $label) {
                    $group[] = $form->createElement(
                        'checkbox',
                        'notifications[]',
                        null,
                        $label,
                        ['value' => $itemId]
                    );
                }
                $form->addGroup($group, '', [get_lang('E-mail notifications')]);
            }

            $form->addCheckBox('update_title_in_lps', null, get_lang('Update this title in learning paths'));

            $defaults = [];
            if ('true' === api_get_setting('search_enabled')) {
                $form->addCheckBox('index_document', '', get_lang('Index document text?'));
                $form->addSelectLanguage('language', get_lang('Document language for indexation'));
                $specific_fields = get_specific_field_list();

                foreach ($specific_fields as $specific_field) {
                    $form->addElement('text', $specific_field['code'], $specific_field['name']);
                    $filter = [
                        'c_id' => api_get_course_int_id(),
                        'field_id' => $specific_field['id'],
                        'ref_id' => $this->getId(),
                        'tool_id' => "'".TOOL_QUIZ."'",
                    ];
                    $values = get_specific_field_values_list($filter, ['value']);
                    if (!empty($values)) {
                        $arr_str_values = [];
                        foreach ($values as $value) {
                            $arr_str_values[] = $value['value'];
                        }
                        $defaults[$specific_field['code']] = implode(', ', $arr_str_values);
                    }
                }
            }

            $skillList = SkillModel::addSkillsToForm($form, ITEM_TYPE_EXERCISE, $this->iId);

            $extraField = new ExtraField('exercise');
            $extraField->addElements(
                $form,
                $this->iId,
                ['notifications'], //exclude
                false, // filter
                false, // tag as select
                [], //show only fields
                [], // order fields
                [] // extra data
            );
            $settings = api_get_configuration_value('exercise_finished_notification_settings');
            if (!empty($settings)) {
                $options = [];
                foreach ($settings as $name => $data) {
                    $options[$name] = $name;
                }
                $form->addSelect(
                    'extra_notifications',
                    get_lang('Notifications'),
                    $options,
                    ['placeholder' => get_lang('SelectAnOption')]
                );
            }
            $form->addElement('html', '</div>'); //End advanced setting
            $form->addElement('html', '</div>');
        }

        // submit
        if (isset($_GET['id'])) {
            $form->addButtonSave(get_lang('Edit test name and settings'), 'submitExercise');
        } else {
            $form->addButtonUpdate(get_lang('Proceed to questions'), 'submitExercise');
        }

        $form->addRule('exerciseTitle', get_lang('Name'), 'required');

        // defaults
        if ('full' == $type) {
            // rules
            $form->addRule('exerciseAttempts', get_lang('Numeric'), 'numeric');
            $form->addRule('start_time', get_lang('Invalid date'), 'datetime');
            $form->addRule('end_time', get_lang('Invalid date'), 'datetime');

            if ($this->getId() > 0) {
                $defaults['randomQuestions'] = $this->random;
                $defaults['randomAnswers'] = $this->getRandomAnswers();
                $defaults['exerciseType'] = $this->selectType();
                $defaults['exerciseTitle'] = $this->get_formated_title();
                $defaults['exerciseDescription'] = $this->selectDescription();
                $defaults['exerciseAttempts'] = $this->selectAttempts();
                $defaults['exerciseFeedbackType'] = $this->getFeedbackType();
                $defaults['results_disabled'] = $this->selectResultsDisabled();
                $defaults['propagate_neg'] = $this->selectPropagateNeg();
                $defaults['save_correct_answers'] = $this->getSaveCorrectAnswers();
                $defaults['review_answers'] = $this->review_answers;
                $defaults['randomByCat'] = $this->getRandomByCategory();
                $defaults['text_when_finished'] = $this->getTextWhenFinished();
                $defaults['display_category_name'] = $this->selectDisplayCategoryName();
                $defaults['pass_percentage'] = $this->selectPassPercentage();
                $defaults['question_selection_type'] = $this->getQuestionSelectionType();
                $defaults['hide_question_title'] = $this->getHideQuestionTitle();
                $defaults['show_previous_button'] = $this->showPreviousButton();
                $defaults['exercise_category_id'] = $this->getExerciseCategoryId();
                $defaults['prevent_backwards'] = $this->getPreventBackwards();
                $defaults['hide_question_number'] = $this->getHideQuestionNumber();

                if (!empty($this->start_time)) {
                    $defaults['activate_start_date_check'] = 1;
                }
                if (!empty($this->end_time)) {
                    $defaults['activate_end_date_check'] = 1;
                }

                $defaults['start_time'] = !empty($this->start_time) ? api_get_local_time($this->start_time) : date(
                    'Y-m-d 12:00:00'
                );
                $defaults['end_time'] = !empty($this->end_time) ? api_get_local_time($this->end_time) : date(
                    'Y-m-d 12:00:00',
                    time() + 84600
                );

                // Get expired time
                if ('0' != $this->expired_time) {
                    $defaults['enabletimercontrol'] = 1;
                    $defaults['enabletimercontroltotalminutes'] = $this->expired_time;
                } else {
                    $defaults['enabletimercontroltotalminutes'] = 0;
                }
                $defaults['skills'] = array_keys($skillList);
                $defaults['notifications'] = $this->getNotifications();
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
                $defaults['end_time'] = date('Y-m-d 12:00:00', time() + 84600);
                $defaults['pass_percentage'] = '';
                $defaults['end_button'] = $this->selectEndButton();
                $defaults['question_selection_type'] = 1;
                $defaults['hide_question_title'] = 0;
                $defaults['show_previous_button'] = 1;
                $defaults['on_success_message'] = null;
                $defaults['on_failed_message'] = null;
            }
        } else {
            $defaults['exerciseTitle'] = $this->selectTitle();
            $defaults['exerciseDescription'] = $this->selectDescription();
        }

        if ('true' === api_get_setting('search_enabled')) {
            $defaults['index_document'] = 'checked="checked"';
        }

        $this->setPageResultConfigurationDefaults($defaults);
        $form->setDefaults($defaults);

        // Freeze some elements.
        if (0 != $this->getId() && false == $this->edit_exercise_in_lp) {
            $elementsToFreeze = [
                'randomQuestions',
                //'randomByCat',
                'exerciseAttempts',
                'propagate_neg',
                'enabletimercontrol',
                'review_answers',
            ];

            foreach ($elementsToFreeze as $elementName) {
                /** @var HTML_QuickForm_element $element */
                $element = $form->getElement($elementName);
                $element->freeze();
            }
        }
    }

    public function setResultFeedbackGroup(FormValidator $form, $checkFreeze = true)
    {
        // Feedback type.
        $feedback = [];
        $warning = sprintf(
            get_lang('TheSettingXWillChangeToX'),
            get_lang('ShowResultsToStudents'),
            get_lang('ShowScoreAndRightAnswer')
        );
        $endTest = $form->createElement(
            'radio',
            'exerciseFeedbackType',
            null,
            get_lang('At end of test'),
            EXERCISE_FEEDBACK_TYPE_END,
            [
                'id' => 'exerciseType_'.EXERCISE_FEEDBACK_TYPE_END,
                //'onclick' => 'if confirm() check_feedback()',
                'onclick' => 'javascript:if(confirm('."'".addslashes($warning)."'".')) { check_feedback(); } else { return false;} ',
            ]
        );

        $noFeedBack = $form->createElement(
            'radio',
            'exerciseFeedbackType',
            null,
            get_lang('Exam (no feedback)'),
            EXERCISE_FEEDBACK_TYPE_EXAM,
            [
                'id' => 'exerciseType_'.EXERCISE_FEEDBACK_TYPE_EXAM,
            ]
        );

        $feedback[] = $endTest;
        $feedback[] = $noFeedBack;

        $scenarioEnabled = 'true' === api_get_setting('enable_quiz_scenario');
        $freeze = true;
        if ($scenarioEnabled) {
            if ($this->getQuestionCount() > 0) {
                $hasDifferentQuestion = $this->hasQuestionWithTypeNotInList([UNIQUE_ANSWER, HOT_SPOT_DELINEATION]);
                if (false === $hasDifferentQuestion) {
                    $freeze = false;
                }
            } else {
                $freeze = false;
            }
            // Can't convert a question from one feedback to another
            $direct = $form->createElement(
                'radio',
                'exerciseFeedbackType',
                null,
                get_lang('Adaptative test with immediate feedback'),
                EXERCISE_FEEDBACK_TYPE_DIRECT,
                [
                    'id' => 'exerciseType_'.EXERCISE_FEEDBACK_TYPE_DIRECT,
                    'onclick' => 'check_direct_feedback()',
                ]
            );

            $directPopUp = $form->createElement(
                'radio',
                'exerciseFeedbackType',
                null,
                get_lang('ExerciseDirectPopUp'),
                EXERCISE_FEEDBACK_TYPE_POPUP,
                ['id' => 'exerciseType_'.EXERCISE_FEEDBACK_TYPE_POPUP, 'onclick' => 'check_direct_feedback()']
            );
            if ($freeze) {
                $direct->freeze();
                $directPopUp->freeze();
            }

            // If has delineation freeze all.
            $hasDelineation = $this->hasQuestionWithType(HOT_SPOT_DELINEATION);
            if ($hasDelineation) {
                $endTest->freeze();
                $noFeedBack->freeze();
                $direct->freeze();
                $directPopUp->freeze();
            }

            $feedback[] = $direct;
            $feedback[] = $directPopUp;
        }

        $form->addGroup(
            $feedback,
            null,
            [
                get_lang('Feedback'),
                get_lang(
                    'How should we show the feedback/comment for each question? This option defines how it will be shown to the learner when taking the test. We recommend you try different options by editing your test options before having learners take it.'
                ),
            ]
        );
    }

    /**
     * function which process the creation of exercises.
     *
     * @param FormValidator $form
     *
     * @return int c_quiz.iid
     */
    public function processCreation($form)
    {
        $this->updateTitle(self::format_title_variable($form->getSubmitValue('exerciseTitle')));
        $this->updateDescription($form->getSubmitValue('exerciseDescription'));
        $this->updateAttempts($form->getSubmitValue('exerciseAttempts'));
        $this->updateFeedbackType($form->getSubmitValue('exerciseFeedbackType'));
        $this->updateType($form->getSubmitValue('exerciseType'));

        // If direct feedback then force to One per page
        if (EXERCISE_FEEDBACK_TYPE_DIRECT == $form->getSubmitValue('exerciseFeedbackType')) {
            $this->updateType(ONE_PER_PAGE);
        }

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
        $this->setEmailNotificationTemplateToUser($form->getSubmitValue('email_notification_template_to_user'));
        $this->setNotifyUserByEmail($form->getSubmitValue('notify_user_by_email'));
        $this->setModelType($form->getSubmitValue('model_type'));
        $this->setQuestionSelectionType($form->getSubmitValue('question_selection_type'));
        $this->setHideQuestionTitle($form->getSubmitValue('hide_question_title'));
        $this->sessionId = api_get_session_id();
        $this->setQuestionSelectionType($form->getSubmitValue('question_selection_type'));
        $this->setScoreTypeModel($form->getSubmitValue('score_type_model'));
        $this->setGlobalCategoryId($form->getSubmitValue('global_category_id'));
        $this->setShowPreviousButton($form->getSubmitValue('show_previous_button'));
        $this->setNotifications($form->getSubmitValue('notifications'));
        $this->setExerciseCategoryId($form->getSubmitValue('exercise_category_id'));
        $this->setPageResultConfiguration($form->getSubmitValues());
        $this->setHideQuestionNumber($form->getSubmitValue('hide_question_number'));
        $this->preventBackwards = (int) $form->getSubmitValue('prevent_backwards');

        $this->start_time = null;
        if (1 == $form->getSubmitValue('activate_start_date_check')) {
            $start_time = $form->getSubmitValue('start_time');
            $this->start_time = api_get_utc_datetime($start_time);
        }

        $this->end_time = null;
        if (1 == $form->getSubmitValue('activate_end_date_check')) {
            $end_time = $form->getSubmitValue('end_time');
            $this->end_time = api_get_utc_datetime($end_time);
        }

        $this->expired_time = 0;
        if (1 == $form->getSubmitValue('enabletimercontrol')) {
            $expired_total_time = $form->getSubmitValue('enabletimercontroltotalminutes');
            if (0 == $this->expired_time) {
                $this->expired_time = $expired_total_time;
            }
        }

        $this->random_answers = 0;
        if (1 == $form->getSubmitValue('randomAnswers')) {
            $this->random_answers = 1;
        }

        // Update title in all LPs that have this quiz added
        if (1 == $form->getSubmitValue('update_title_in_lps')) {
            $table = Database::get_course_table(TABLE_LP_ITEM);
            $sql = "SELECT iid FROM $table
                    WHERE
                        item_type = 'quiz' AND
                        path = '".$this->getId()."'
                    ";
            $result = Database::query($sql);
            $items = Database::store_result($result);
            if (!empty($items)) {
                foreach ($items as $item) {
                    $itemId = $item['iid'];
                    $sql = "UPDATE $table
                            SET title = '".$this->title."'
                            WHERE iid = $itemId ";
                    Database::query($sql);
                }
            }
        }

        $iId = $this->save();
        if (!empty($iId)) {
            $values = $form->getSubmitValues();
            $values['item_id'] = $iId;
            $extraFieldValue = new ExtraFieldValue('exercise');
            $extraFieldValue->saveFieldValues($values);

            SkillModel::saveSkills($form, ITEM_TYPE_EXERCISE, $iId);
        }
    }

    public function search_engine_save()
    {
        if (1 != $_POST['index_document']) {
            return;
        }
        $course_id = api_get_course_id();
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
                        add_specific_field_value($specific_field['id'], $course_id, TOOL_QUIZ, $this->getId(), $sterm);
                    }
                }
            }
        }

        // build the chunk to index
        $ic_slide->addValue('title', $this->exercise);
        $ic_slide->addCourseId($course_id);
        $ic_slide->addToolId(TOOL_QUIZ);
        $xapian_data = [
            SE_COURSE_ID => $course_id,
            SE_TOOL_ID => TOOL_QUIZ,
            SE_DATA => ['type' => SE_DOCTYPE_EXERCISE_EXERCISE, 'exercise_id' => (int) $this->getId()],
            SE_USER => (int) api_get_user_id(),
        ];
        $ic_slide->xapian_data = serialize($xapian_data);
        $exercise_description = $all_specific_terms.' '.$this->description;
        $ic_slide->addValue('content', $exercise_description);

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
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->getId(), $did);
            Database::query($sql);
        }
    }

    public function search_engine_edit()
    {
        // update search enchine and its values table if enabled
        if ('true' == api_get_setting('search_enabled') && extension_loaded('xapian')) {
            $course_id = api_get_course_id();

            // actually, it consists on delete terms from db,
            // insert new ones, create a new search engine document, and remove the old one
            // get search_did
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->getId());
            $res = Database::query($sql);

            if (Database::num_rows($res) > 0) {
                $se_ref = Database::fetch_array($res);
                $specific_fields = get_specific_field_list();
                $ic_slide = new IndexableChunk();

                $all_specific_terms = '';
                foreach ($specific_fields as $specific_field) {
                    delete_all_specific_field_value($course_id, $specific_field['id'], TOOL_QUIZ, $this->getId());
                    if (isset($_REQUEST[$specific_field['code']])) {
                        $sterms = trim($_REQUEST[$specific_field['code']]);
                        $all_specific_terms .= ' '.$sterms;
                        $sterms = explode(',', $sterms);
                        foreach ($sterms as $sterm) {
                            $ic_slide->addTerm(trim($sterm), $specific_field['code']);
                            add_specific_field_value(
                                $specific_field['id'],
                                $course_id,
                                TOOL_QUIZ,
                                $this->getId(),
                                $sterm
                            );
                        }
                    }
                }

                // build the chunk to index
                $ic_slide->addValue('title', $this->exercise);
                $ic_slide->addCourseId($course_id);
                $ic_slide->addToolId(TOOL_QUIZ);
                $xapian_data = [
                    SE_COURSE_ID => $course_id,
                    SE_TOOL_ID => TOOL_QUIZ,
                    SE_DATA => ['type' => SE_DOCTYPE_EXERCISE_EXERCISE, 'exercise_id' => (int) $this->getId()],
                    SE_USER => (int) api_get_user_id(),
                ];
                $ic_slide->xapian_data = serialize($xapian_data);
                $exercise_description = $all_specific_terms.' '.$this->description;
                $ic_slide->addValue('content', $exercise_description);

                $di = new ChamiloIndexer();
                isset($_POST['language']) ? $lang = Database::escape_string($_POST['language']) : $lang = 'english';
                $di->connectDb(null, null, $lang);
                $di->remove_document($se_ref['search_did']);
                $di->addChunk($ic_slide);

                //index and return search engine document id
                $did = $di->index();
                if ($did) {
                    // save it to db
                    $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=\'%s\'';
                    $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->getId());
                    Database::query($sql);
                    $sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, search_did)
                        VALUES (NULL , \'%s\', \'%s\', %s, %s)';
                    $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->getId(), $did);
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
        if ('true' == api_get_setting('search_enabled') && extension_loaded('xapian')) {
            $course_id = api_get_course_id();
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            $sql = 'SELECT * FROM %s
                    WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level IS NULL
                    LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->getId());
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $row = Database::fetch_array($res);
                $di = new ChamiloIndexer();
                $di->remove_document($row['search_did']);
                unset($di);
                $tbl_quiz_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
                foreach ($this->questionList as $question_i) {
                    $sql = 'SELECT type FROM %s WHERE id=%s';
                    $sql = sprintf($sql, $tbl_quiz_question, $question_i);
                    $qres = Database::query($sql);
                    if (Database::num_rows($qres) > 0) {
                        $qrow = Database::fetch_array($qres);
                        $objQuestion = Question::getInstance($qrow['type']);
                        $objQuestion = Question::read((int) $question_i);
                        $objQuestion->search_engine_edit($this->getId(), false, true);
                        unset($objQuestion);
                    }
                }
            }
            $sql = 'DELETE FROM %s
                    WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level IS NULL
                    LIMIT 1';
            $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->getId());
            Database::query($sql);

            // remove terms from db
            delete_all_values_for_item($course_id, TOOL_QUIZ, $this->getId());
        }
    }

    public function selectExpiredTime()
    {
        return $this->expired_time;
    }

    /**
     * Cleans the student's results only for the Exercise tool (Not from the LP)
     * The LP results are NOT deleted by default, otherwise put $cleanLpTests = true
     * Works with exercises in sessions.
     *
     * @param bool   $cleanLpTests
     * @param string $cleanResultBeforeDate
     *
     * @return int quantity of user's exercises deleted
     */
    public function cleanResults($cleanLpTests = false, $cleanResultBeforeDate = null)
    {
        $sessionId = api_get_session_id();
        $table_track_e_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $table_track_e_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $sql_where = '  AND
                        orig_lp_id = 0 AND
                        orig_lp_item_id = 0';

        // if we want to delete results from LP too
        if ($cleanLpTests) {
            $sql_where = '';
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

        $sessionCondition = api_get_session_condition($sessionId);
        $sql = "SELECT exe_id
                FROM $table_track_e_exercises
                WHERE
                    c_id = ".api_get_course_int_id().' AND
                    exe_exo_id = '.$this->getId()."
                    $sessionCondition
                    $sql_where";

        $result = Database::query($sql);
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

        // delete TRACK_E_EXERCISES table
        $sql = "DELETE FROM $table_track_e_exercises
                WHERE
                  c_id = ".api_get_course_int_id().' AND
                  exe_exo_id = '.$this->getId()." $sql_where $sessionCondition";
        Database::query($sql);

        $this->generateStats($this->getId(), api_get_course_info(), $sessionId);

        Event::addEvent(
            LOG_EXERCISE_RESULT_DELETE,
            LOG_EXERCISE_ID,
            $this->getId(),
            null,
            null,
            api_get_course_int_id(),
            $sessionId
        );

        return $i;
    }

    /**
     * Copies an exercise (duplicate all questions and answers).
     */
    public function copyExercise()
    {
        $exerciseObject = $this;
        $categories = $exerciseObject->getCategoriesInExercise(true);
        // Get all questions no matter the order/category settings
        $questionList = $exerciseObject->getQuestionOrderedList();
        $sourceId = $exerciseObject->iId;
        // Force the creation of a new exercise
        $exerciseObject->updateTitle($exerciseObject->selectTitle().' - '.get_lang('Copy'));
        // Hides the new exercise
        $exerciseObject->updateStatus(false);
        $exerciseObject->iId = 0;
        $exerciseObject->sessionId = api_get_session_id();
        $courseId = api_get_course_int_id();
        $exerciseObject->save();
        $newId = $exerciseObject->getId();
        $exerciseRelQuestionTable = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);

        $count = 1;
        $batchSize = 20;
        $em = Database::getManager();
        if ($newId && !empty($questionList)) {
            $extraField = new ExtraFieldValue('exercise');
            $extraField->copy($sourceId, $newId);
            // Question creation
            foreach ($questionList as $oldQuestionId) {
                $oldQuestionObj = Question::read($oldQuestionId, null, false);
                $newQuestionId = $oldQuestionObj->duplicate();
                if ($newQuestionId) {
                    $newQuestionObj = Question::read($newQuestionId, null, false);
                    if (isset($newQuestionObj) && $newQuestionObj) {
                        $sql = "INSERT INTO $exerciseRelQuestionTable (c_id, question_id, quiz_id, question_order)
                                VALUES ($courseId, ".$newQuestionId.", ".$newId.", '$count')";
                        Database::query($sql);
                        $count++;
                        if (!empty($oldQuestionObj->category)) {
                            $newQuestionObj->saveCategory($oldQuestionObj->category);
                        }

                        // This should be moved to the duplicate function
                        $newAnswerObj = new Answer($oldQuestionId, $courseId, $exerciseObject);
                        $newAnswerObj->read();
                        $newAnswerObj->duplicate($newQuestionObj);
                        if (($count % $batchSize) === 0) {
                            $em->clear(); // Detaches all objects from Doctrine!
                        }
                    }
                }
            }
            if (!empty($categories)) {
                $newCategoryList = [];
                foreach ($categories as $category) {
                    $newCategoryList[$category['category_id']] = $category['count_questions'];
                }
                $exerciseObject->save_categories_in_exercise($newCategoryList);
            }
        }
    }

    /**
     * Changes the exercise status.
     *
     * @param string $status - exercise status
     */
    public function updateStatus($status)
    {
        $this->active = $status;
    }

    /**
     * @param int    $lp_id
     * @param int    $lp_item_id
     * @param int    $lp_item_view_id
     * @param string $status
     *
     * @return array
     */
    public function get_stat_track_exercise_info(
        $lp_id = 0,
        $lp_item_id = 0,
        $lp_item_view_id = 0,
        $status = 'incomplete'
    ) {
        $track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $lp_id = (int) $lp_id;
        $lp_item_id = (int) $lp_item_id;
        $lp_item_view_id = (int) $lp_item_view_id;

        $sessionCondition = api_get_session_condition(api_get_session_id());
        $condition = " WHERE exe_exo_id 	= ".$this->getId()." AND
					   exe_user_id 			= '".api_get_user_id()."' AND
					   c_id                 = ".api_get_course_int_id()." AND
					   status 				= '".Database::escape_string($status)."' AND
					   orig_lp_id 			= $lp_id AND
					   orig_lp_item_id 		= $lp_item_id AND
                       orig_lp_item_view_id =  $lp_item_view_id
					   ";

        $sql_track = " SELECT * FROM  $track_exercises $condition $sessionCondition LIMIT 1 ";

        $result = Database::query($sql_track);
        $new_array = [];
        if (Database::num_rows($result) > 0) {
            $new_array = Database::fetch_array($result, 'ASSOC');
            $new_array['num_exe'] = Database::num_rows($result);
        }

        return $new_array;
    }

    /**
     * Saves a test attempt.
     *
     * @param int   $clock_expired_time   clock_expired_time
     * @param int   $safe_lp_id           lp id
     * @param int   $safe_lp_item_id      lp item id
     * @param int   $safe_lp_item_view_id lp item_view id
     * @param array $questionList
     * @param float $weight
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return int
     */
    public function save_stat_track_exercise_info(
        $clock_expired_time,
        $safe_lp_id = 0,
        $safe_lp_item_id = 0,
        $safe_lp_item_view_id = 0,
        $questionList = [],
        $weight = 0
    ) {
        $safe_lp_id = (int) $safe_lp_id;
        $safe_lp_item_id = (int) $safe_lp_item_id;
        $safe_lp_item_view_id = (int) $safe_lp_item_view_id;

        if (empty($clock_expired_time)) {
            $clock_expired_time = null;
        }

        $questionList = array_map('intval', $questionList);
        $em = Database::getManager();

        $quiz = $em->find(CQuiz::class, $this->getId());

        $trackExercise = (new TrackEExercise())
            ->setSession(api_get_session_entity())
            ->setCourse(api_get_course_entity())
            ->setMaxScore($weight)
            ->setDataTracking(implode(',', $questionList))
            ->setUser(api_get_user_entity())
            ->setUserIp(api_get_real_ip())
            ->setOrigLpId($safe_lp_id)
            ->setOrigLpItemId($safe_lp_item_id)
            ->setOrigLpItemViewId($safe_lp_item_view_id)
            ->setExpiredTimeControl($clock_expired_time)
            ->setQuiz($quiz)
        ;
        $em->persist($trackExercise);
        $em->flush();

        return $trackExercise->getExeId();
    }

    /**
     * @param int    $question_id
     * @param int    $questionNum
     * @param array  $questions_in_media
     * @param string $currentAnswer
     * @param array  $myRemindList
     * @param bool   $showPreviousButton
     *
     * @return string
     */
    public function show_button(
        $question_id,
        $questionNum,
        $questions_in_media = [],
        $currentAnswer = '',
        $myRemindList = [],
        $showPreviousButton = true
    ) {
        global $safe_lp_id, $safe_lp_item_id, $safe_lp_item_view_id;
        $nbrQuestions = $this->countQuestionsInExercise();
        $buttonList = [];
        $html = $label = '';
        $hotspotGet = isset($_POST['hotspot']) ? Security::remove_XSS($_POST['hotspot']) : null;

        if (in_array($this->getFeedbackType(), [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP]) &&
            ONE_PER_PAGE == $this->type
        ) {
            $urlTitle = get_lang('Proceed with the test');
            if ($questionNum == count($this->questionList)) {
                $urlTitle = get_lang('End test');
            }

            $url = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit_modal.php?'.api_get_cidreq();
            $url .= '&'.http_build_query(
                    [
                        'learnpath_id' => $safe_lp_id,
                        'learnpath_item_id' => $safe_lp_item_id,
                        'learnpath_item_view_id' => $safe_lp_item_view_id,
                        'hotspot' => $hotspotGet,
                        'nbrQuestions' => $nbrQuestions,
                        'num' => $questionNum,
                        'exerciseType' => $this->type,
                        'exerciseId' => $this->getId(),
                        'reminder' => empty($myRemindList) ? null : 2,
                        'tryagain' => isset($_REQUEST['tryagain']) && 1 === (int) $_REQUEST['tryagain'] ? 1 : 0,
                    ]
                );

            $params = [
                'class' => 'ajax btn btn-default no-close-button',
                'data-title' => Security::remove_XSS(get_lang('Comment')),
                'data-size' => 'md',
                'id' => "button_$question_id",
            ];

            if (EXERCISE_FEEDBACK_TYPE_POPUP === $this->getFeedbackType()) {
                //$params['data-block-div-after-closing'] = "question_div_$question_id";
                $params['data-block-closing'] = 'true';
                $params['class'] .= ' no-header ';
            }

            $html .= Display::url($urlTitle, $url, $params);
            $html .= '<br />';

            return $html;
        }

        if (!api_is_allowed_to_session_edit()) {
            return '';
        }

        $isReviewingAnswers = isset($_REQUEST['reminder']) && 2 == $_REQUEST['reminder'];

        // User
        $endReminderValue = false;
        if (!empty($myRemindList) && $isReviewingAnswers) {
            $endValue = end($myRemindList);
            if ($endValue == $question_id) {
                $endReminderValue = true;
            }
        }
        $endTest = false;
        if (ALL_ON_ONE_PAGE == $this->type || $nbrQuestions == $questionNum || $endReminderValue) {
            if ($this->review_answers) {
                $label = get_lang('ReviewQuestions');
                $class = 'btn btn-success';
            } else {
                $endTest = true;
                $label = get_lang('End Test');
                $class = 'btn btn-warning';
            }
        } else {
            $label = get_lang('Next question');
            $class = 'btn btn-primary';
        }
        // used to select it with jquery
        $class .= ' question-validate-btn';
        if (ONE_PER_PAGE == $this->type) {
            if (1 != $questionNum && $this->showPreviousButton()) {
                $prev_question = $questionNum - 2;
                $showPreview = true;
                if (!empty($myRemindList) && $isReviewingAnswers) {
                    $beforeId = null;
                    for ($i = 0; $i < count($myRemindList); $i++) {
                        if (isset($myRemindList[$i]) && $myRemindList[$i] == $question_id) {
                            $beforeId = isset($myRemindList[$i - 1]) ? $myRemindList[$i - 1] : null;

                            break;
                        }
                    }

                    if (empty($beforeId)) {
                        $showPreview = false;
                    } else {
                        $num = 0;
                        foreach ($this->questionList as $originalQuestionId) {
                            if ($originalQuestionId == $beforeId) {
                                break;
                            }
                            $num++;
                        }
                        $prev_question = $num;
                    }
                }

                if ($showPreviousButton && $showPreview && 0 === $this->getPreventBackwards()) {
                    $buttonList[] = Display::button(
                        'previous_question_and_save',
                        get_lang('Previous question'),
                        [
                            'type' => 'button',
                            'class' => 'btn btn-default',
                            'data-prev' => $prev_question,
                            'data-question' => $question_id,
                        ]
                    );
                }
            }

            // Next question
            if (!empty($questions_in_media)) {
                $buttonList[] = Display::button(
                    'save_question_list',
                    $label,
                    [
                        'type' => 'button',
                        'class' => $class,
                        'data-list' => implode(',', $questions_in_media),
                    ]
                );
            } else {
                $attributes = ['type' => 'button', 'class' => $class, 'data-question' => $question_id];
                $name = 'save_now';
                if ($endTest && api_get_configuration_value('quiz_check_all_answers_before_end_test')) {
                    $name = 'check_answers';
                }
                $buttonList[] = Display::button(
                    $name,
                    $label,
                    $attributes
                );
            }
            $buttonList[] = '<span id="save_for_now_'.$question_id.'" class="exercise_save_mini_message"></span>';

            $html .= implode(PHP_EOL, $buttonList).PHP_EOL;

            return $html;
        }

        if ($this->review_answers) {
            $all_label = get_lang('Review selected questions');
            $class = 'btn btn-success';
        } else {
            $all_label = get_lang('End test');
            $class = 'btn btn-warning';
        }
        // used to select it with jquery
        $class .= ' question-validate-btn';
        $buttonList[] = Display::button(
            'validate_all',
            $all_label,
            ['type' => 'button', 'class' => $class]
        );
        $buttonList[] = Display::span(null, ['id' => 'save_all_response']);
        $html .= implode(PHP_EOL, $buttonList).PHP_EOL;

        return $html;
    }

    /**
     * @param int    $timeLeft in seconds
     * @param string $url
     *
     * @return string
     */
    public function showSimpleTimeControl($timeLeft, $url = '')
    {
        $timeLeft = (int) $timeLeft;

        return "<script>
            function openClockWarning() {
                $('#clock_warning').dialog({
                    modal:true,
                    height:320,
                    width:550,
                    closeOnEscape: false,
                    resizable: false,
                    buttons: {
                        '".addslashes(get_lang('Close'))."': function() {
                            $('#clock_warning').dialog('close');
                        }
                    },
                    close: function() {
                        window.location.href = '$url';
                    }
                });
                $('#clock_warning').dialog('open');
                $('#counter_to_redirect').epiclock({
                    mode: $.epiclock.modes.countdown,
                    offset: {seconds: 5},
                    format: 's'
                }).bind('timer', function () {
                    window.location.href = '$url';
                });
            }

            function onExpiredTimeExercise() {
                $('#wrapper-clock').hide();
                $('#expired-message-id').show();
                // Fixes bug #5263
                $('#num_current_id').attr('value', '".$this->selectNbrQuestions()."');
                openClockWarning();
            }

			$(function() {
				// time in seconds when using minutes there are some seconds lost
                var time_left = parseInt(".$timeLeft.");
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
     * So the time control will work.
     *
     * @param int    $timeLeft
     * @param string $redirectToUrl
     *
     * @return string
     */
    public function showTimeControlJS($timeLeft, $redirectToUrl = '')
    {
        $timeLeft = (int) $timeLeft;
        $script = 'redirectExerciseToResult();';
        if (ALL_ON_ONE_PAGE == $this->type) {
            $script = "save_now_all('validate');";
        } elseif (ONE_PER_PAGE == $this->type) {
            $script = 'window.quizTimeEnding = true;
                $(\'[name="save_now"]\').trigger(\'click\');';
        }

        $exerciseSubmitRedirect = '';
        if (!empty($redirectToUrl)) {
            $exerciseSubmitRedirect = "window.location = '$redirectToUrl'";
        }

        return "<script>
            function openClockWarning() {
                $('#clock_warning').dialog({
                    modal:true,
                    height:320,
                    width:550,
                    closeOnEscape: false,
                    resizable: false,
                    buttons: {
                        '".addslashes(get_lang('End test'))."': function() {
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
                    $script
                } else {
                    $exerciseSubmitRedirect
                    // In exercise_reminder.php
                    final_submit();
                }
            }

            function onExpiredTimeExercise() {
                $('#wrapper-clock').hide();
                $('#expired-message-id').show();
                // Fixes bug #5263
                $('#num_current_id').attr('value', '".$this->selectNbrQuestions()."');
                openClockWarning();
            }

			$(function() {
				// time in seconds when using minutes there are some seconds lost
                var time_left = parseInt(".$timeLeft.");
                $('#exercise_clock_warning').epiclock({
                    mode: $.epiclock.modes.countdown,
                    offset: {seconds: time_left},
                    format: 'x:C:s',
                    renderer: 'minute'
                }).bind('timer', function () {
                    onExpiredTimeExercise();
                });
	       		$('#submit_save').click(function () {});
	        });
	    </script>";
    }

    /**
     * This function was originally found in the exercise_show.php.
     *
     * @param int    $exeId
     * @param int    $questionId
     * @param mixed  $choice                                    the user-selected option
     * @param string $from                                      function is called from 'exercise_show' or
     *                                                          'exercise_result'
     * @param array  $exerciseResultCoordinates                 the hotspot coordinates $hotspot[$question_id] =
     *                                                          coordinates
     * @param bool   $save_results                              save results in the DB or just show the response
     * @param bool   $from_database                             gets information from DB or from the current selection
     * @param bool   $show_result                               show results or not
     * @param int    $propagate_neg
     * @param array  $hotspot_delineation_result
     * @param bool   $showTotalScoreAndUserChoicesInLastAttempt
     * @param bool   $updateResults
     * @param bool   $showHotSpotDelineationTable
     * @param int    $questionDuration                          seconds
     *
     * @return string html code
     *
     * @todo    reduce parameters of this function
     */
    public function manage_answer(
        $exeId,
        $questionId,
        $choice,
        $from = 'exercise_show',
        $exerciseResultCoordinates = [],
        $save_results = true,
        $from_database = false,
        $show_result = true,
        $propagate_neg = 0,
        $hotspot_delineation_result = [],
        $showTotalScoreAndUserChoicesInLastAttempt = true,
        $updateResults = false,
        $showHotSpotDelineationTable = false,
        $questionDuration = 0
    ) {
        $debug = false;
        //needed in order to use in the exercise_attempt() for the time
        global $learnpath_id, $learnpath_item_id;
        require_once api_get_path(LIBRARY_PATH).'geometry.lib.php';
        $em = Database::getManager();
        $feedback_type = $this->getFeedbackType();
        $results_disabled = $this->selectResultsDisabled();
        $questionDuration = (int) $questionDuration;

        if ($debug) {
            error_log('<------ manage_answer ------> ');
            error_log('exe_id: '.$exeId);
            error_log('$from:  '.$from);
            error_log('$save_results: '.(int) $save_results);
            error_log('$from_database: '.(int) $from_database);
            error_log('$show_result: '.(int) $show_result);
            error_log('$propagate_neg: '.$propagate_neg);
            error_log('$exerciseResultCoordinates: '.print_r($exerciseResultCoordinates, 1));
            error_log('$hotspot_delineation_result: '.print_r($hotspot_delineation_result, 1));
            error_log('$learnpath_id: '.$learnpath_id);
            error_log('$learnpath_item_id: '.$learnpath_item_id);
            error_log('$choice: '.print_r($choice, 1));
            error_log('-----------------------------');
        }

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
        $studentChoice = null;
        $expectedAnswer = '';
        $calculatedChoice = '';
        $calculatedStatus = '';
        $questionId = (int) $questionId;
        $exeId = (int) $exeId;
        $TBL_TRACK_ATTEMPT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $table_ans = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $studentChoiceDegree = null;

        // Creates a temporary Question object
        $course_id = $this->course_id;
        $objQuestionTmp = Question::read($questionId, $this->course);

        if (false === $objQuestionTmp) {
            return false;
        }

        $questionName = $objQuestionTmp->selectTitle();
        $questionWeighting = $objQuestionTmp->selectWeighting();
        $answerType = $objQuestionTmp->selectType();
        $quesId = $objQuestionTmp->getId();
        $extra = $objQuestionTmp->extra;
        $next = 1; //not for now
        $totalWeighting = 0;
        $totalScore = 0;

        // Extra information of the question
        if ((
                MULTIPLE_ANSWER_TRUE_FALSE == $answerType ||
                MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY == $answerType
            )
            && !empty($extra)
        ) {
            $extra = explode(':', $extra);
            // Fixes problems with negatives values using intval
            $true_score = (float) trim($extra[0]);
            $false_score = (float) trim($extra[1]);
            $doubt_score = (float) trim($extra[2]);
        }

        // Construction of the Answer object
        $objAnswerTmp = new Answer($questionId, $course_id);
        $nbrAnswers = $objAnswerTmp->selectNbrAnswers();

        if ($debug) {
            error_log('Count of possible answers: '.$nbrAnswers);
            error_log('$answerType: '.$answerType);
        }

        if (MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY == $answerType) {
            $choiceTmp = $choice;
            $choice = isset($choiceTmp['choice']) ? $choiceTmp['choice'] : '';
            $choiceDegreeCertainty = isset($choiceTmp['choiceDegreeCertainty']) ? $choiceTmp['choiceDegreeCertainty'] : '';
        }

        if (FREE_ANSWER == $answerType ||
            ORAL_EXPRESSION == $answerType ||
            CALCULATED_ANSWER == $answerType ||
            ANNOTATION == $answerType
        ) {
            $nbrAnswers = 1;
        }

        $user_answer = '';
        // Get answer list for matching
        $sql = "SELECT iid, answer
                FROM $table_ans
                WHERE question_id = $questionId";
        $res_answer = Database::query($sql);

        $answerMatching = [];
        while ($real_answer = Database::fetch_array($res_answer)) {
            $answerMatching[$real_answer['iid']] = $real_answer['answer'];
        }

        // Get first answer needed for global question, no matter the answer shuffle option;
        $firstAnswer = [];
        if (MULTIPLE_ANSWER_COMBINATION == $answerType ||
            MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE == $answerType
        ) {
            $sql = "SELECT *
                    FROM $table_ans
                    WHERE question_id = $questionId
                    ORDER BY position
                    LIMIT 1";
            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $firstAnswer = Database::fetch_array($result);
            }
        }

        $real_answers = [];
        $quiz_question_options = Question::readQuestionOption($questionId, $course_id);

        $organs_at_risk_hit = 0;
        $questionScore = 0;
        $orderedHotSpots = [];
        if (HOT_SPOT == $answerType || ANNOTATION == $answerType) {
            $orderedHotSpots = $em->getRepository(TrackEHotspot::class)->findBy(
                [
                    'hotspotQuestionId' => $questionId,
                    'course' => $course_id,
                    'hotspotExeId' => $exeId,
                ],
                ['hotspotAnswerId' => 'ASC']
            );
        }

        if ($debug) {
            error_log('-- Start answer loop --');
        }

        $answerDestination = null;
        $userAnsweredQuestion = false;
        $correctAnswerId = [];
        for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
            $answer = $objAnswerTmp->selectAnswer($answerId);
            $answerComment = $objAnswerTmp->selectComment($answerId);
            $answerCorrect = $objAnswerTmp->isCorrect($answerId);
            $answerWeighting = (float) $objAnswerTmp->selectWeighting($answerId);
            $answerAutoId = $objAnswerTmp->selectAutoId($answerId);
            $answerIid = isset($objAnswerTmp->iid[$answerId]) ? (int) $objAnswerTmp->iid[$answerId] : 0;

            if ($debug) {
                error_log("c_quiz_answer.id_auto: $answerAutoId ");
                error_log("Answer marked as correct in db (0/1)?: $answerCorrect ");
                error_log("answerWeighting: $answerWeighting");
            }

            // Delineation
            $delineation_cord = $objAnswerTmp->selectHotspotCoordinates(1);
            $answer_delineation_destination = $objAnswerTmp->selectDestination(1);

            switch ($answerType) {
                case UNIQUE_ANSWER:
                case UNIQUE_ANSWER_IMAGE:
                case UNIQUE_ANSWER_NO_OPTION:
                case READING_COMPREHENSION:
                    if ($from_database) {
                        $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                WHERE
                                    exe_id = $exeId AND
                                    question_id = $questionId";
                        $result = Database::query($sql);
                        $choice = Database::result($result, 0, 'answer');

                        if (false === $userAnsweredQuestion) {
                            $userAnsweredQuestion = !empty($choice);
                        }
                        $studentChoice = $choice == $answerAutoId ? 1 : 0;
                        if ($studentChoice) {
                            $questionScore += $answerWeighting;
                            $answerDestination = $objAnswerTmp->selectDestination($answerId);
                            $correctAnswerId[] = $answerId;
                        }
                    } else {
                        $studentChoice = $choice == $answerAutoId ? 1 : 0;
                        if ($studentChoice) {
                            $questionScore += $answerWeighting;
                            $answerDestination = $objAnswerTmp->selectDestination($answerId);
                            $correctAnswerId[] = $answerId;
                        }
                    }

                    break;
                case MULTIPLE_ANSWER_TRUE_FALSE:
                    if ($from_database) {
                        $choice = [];
                        $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                WHERE
                                    exe_id = $exeId AND
                                    question_id = ".$questionId;

                        $result = Database::query($sql);
                        while ($row = Database::fetch_array($result)) {
                            $values = explode(':', $row['answer']);
                            $my_answer_id = isset($values[0]) ? $values[0] : '';
                            $option = isset($values[1]) ? $values[1] : '';
                            $choice[$my_answer_id] = $option;
                        }
                        $userAnsweredQuestion = !empty($choice);
                    }

                    $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;
                    if (!empty($studentChoice)) {
                        $correctAnswerId[] = $answerAutoId;
                        if ($studentChoice == $answerCorrect) {
                            $questionScore += $true_score;
                        } else {
                            if ("Don't know" == $quiz_question_options[$studentChoice]['name'] ||
                                'DoubtScore' == $quiz_question_options[$studentChoice]['name']
                            ) {
                                $questionScore += $doubt_score;
                            } else {
                                $questionScore += $false_score;
                            }
                        }
                    } else {
                        // If no result then the user just hit don't know
                        $studentChoice = 3;
                        $questionScore += $doubt_score;
                    }
                    $totalScore = $questionScore;

                    break;
                case MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY:
                    if ($from_database) {
                        $choice = [];
                        $choiceDegreeCertainty = [];
                        $sql = "SELECT answer
                                FROM $TBL_TRACK_ATTEMPT
                                WHERE exe_id = $exeId AND question_id = $questionId";

                        $result = Database::query($sql);
                        while ($row = Database::fetch_array($result)) {
                            $ind = $row['answer'];
                            $values = explode(':', $ind);
                            $myAnswerId = $values[0] ?? null;
                            $option = $values[1] ?? null;
                            $percent = $values[2] ?? null;
                            $choice[$myAnswerId] = $option;
                            $choiceDegreeCertainty[$myAnswerId] = $percent;
                        }
                    }

                    $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;
                    $studentChoiceDegree = isset($choiceDegreeCertainty[$answerAutoId]) ? $choiceDegreeCertainty[$answerAutoId] : null;

                    // student score update
                    if (!empty($studentChoice)) {
                        if ($studentChoice == $answerCorrect) {
                            // correct answer and student is Unsure or PrettySur
                            if (isset($quiz_question_options[$studentChoiceDegree]) &&
                                $quiz_question_options[$studentChoiceDegree]['position'] >= 3 &&
                                $quiz_question_options[$studentChoiceDegree]['position'] < 9
                            ) {
                                $questionScore += $true_score;
                            } else {
                                // student ignore correct answer
                                $questionScore += $doubt_score;
                            }
                        } else {
                            // false answer and student is Unsure or PrettySur
                            if ($quiz_question_options[$studentChoiceDegree]['position'] >= 3
                                && $quiz_question_options[$studentChoiceDegree]['position'] < 9) {
                                $questionScore += $false_score;
                            } else {
                                // student ignore correct answer
                                $questionScore += $doubt_score;
                            }
                        }
                    }
                    $totalScore = $questionScore;

                    break;
                case MULTIPLE_ANSWER:
                    if ($from_database) {
                        $choice = [];
                        $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                WHERE exe_id = $exeId AND question_id = $questionId ";
                        $resultans = Database::query($sql);
                        while ($row = Database::fetch_array($resultans)) {
                            $choice[$row['answer']] = 1;
                        }

                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;
                        $real_answers[$answerId] = (bool) $studentChoice;

                        if ($studentChoice) {
                            $questionScore += $answerWeighting;
                        }
                    } else {
                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;
                        $real_answers[$answerId] = (bool) $studentChoice;

                        if (isset($studentChoice)) {
                            $correctAnswerId[] = $answerAutoId;
                            $questionScore += $answerWeighting;
                        }
                    }
                    $totalScore += $answerWeighting;

                    break;
                case GLOBAL_MULTIPLE_ANSWER:
                    if ($from_database) {
                        $choice = [];
                        $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                WHERE exe_id = $exeId AND question_id = $questionId ";
                        $resultans = Database::query($sql);
                        while ($row = Database::fetch_array($resultans)) {
                            $choice[$row['answer']] = 1;
                        }
                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;
                        $real_answers[$answerId] = (bool) $studentChoice;
                        if ($studentChoice) {
                            $questionScore += $answerWeighting;
                        }
                    } else {
                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;
                        if (isset($studentChoice)) {
                            $questionScore += $answerWeighting;
                        }
                        $real_answers[$answerId] = (bool) $studentChoice;
                    }
                    $totalScore += $answerWeighting;
                    if ($debug) {
                        error_log("studentChoice: $studentChoice");
                    }

                    break;
                case MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE:
                    if ($from_database) {
                        $choice = [];
                        $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                WHERE exe_id = $exeId AND question_id = $questionId";
                        $resultans = Database::query($sql);
                        while ($row = Database::fetch_array($resultans)) {
                            $result = explode(':', $row['answer']);
                            if (isset($result[0])) {
                                $my_answer_id = isset($result[0]) ? $result[0] : '';
                                $option = isset($result[1]) ? $result[1] : '';
                                $choice[$my_answer_id] = $option;
                            }
                        }
                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : '';

                        $real_answers[$answerId] = false;
                        if ($answerCorrect == $studentChoice) {
                            $real_answers[$answerId] = true;
                        }
                    } else {
                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : '';
                        $real_answers[$answerId] = false;
                        if ($answerCorrect == $studentChoice) {
                            $real_answers[$answerId] = true;
                        }
                    }

                    break;
                case MULTIPLE_ANSWER_COMBINATION:
                    if ($from_database) {
                        $choice = [];
                        $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                WHERE exe_id = $exeId AND question_id = $questionId";
                        $resultans = Database::query($sql);
                        while ($row = Database::fetch_array($resultans)) {
                            $choice[$row['answer']] = 1;
                        }

                        $studentChoice = isset($choice[$answerAutoId]) ? $choice[$answerAutoId] : null;
                        if (1 == $answerCorrect) {
                            $real_answers[$answerId] = false;
                            if ($studentChoice) {
                                $real_answers[$answerId] = true;
                            }
                        } else {
                            $real_answers[$answerId] = true;
                            if ($studentChoice) {
                                $real_answers[$answerId] = false;
                            }
                        }
                    } else {
                        $studentChoice = $choice[$answerAutoId] ?? null;
                        if (1 == $answerCorrect) {
                            $real_answers[$answerId] = false;
                            if ($studentChoice) {
                                $real_answers[$answerId] = true;
                            }
                        } else {
                            $real_answers[$answerId] = true;
                            if ($studentChoice) {
                                $real_answers[$answerId] = false;
                            }
                        }
                    }

                    break;
                case FILL_IN_BLANKS:
                    $str = '';
                    $answerFromDatabase = '';
                    if ($from_database) {
                        $sql = "SELECT answer
                                FROM $TBL_TRACK_ATTEMPT
                                WHERE
                                    exe_id = $exeId AND
                                    question_id= $questionId ";
                        $result = Database::query($sql);
                        $str = $answerFromDatabase = Database::result($result, 0, 'answer');
                    }

                    // if ($saved_results == false && strpos($answerFromDatabase, 'font color') !== false) {
                    if (false) {
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
                        if (isset($is_set_switchable[1]) && 1 == $is_set_switchable[1]) {
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
                        $user_tags = $correct_tags = $real_text = [];
                        // the loop will stop at the end of the text
                        while (1) {
                            // quits the loop if there are no more blanks (detect '[')
                            if (false == $temp || false === ($pos = api_strpos($temp, '['))) {
                                // adds the end of the text
                                $answer = $temp;
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
                            if (false === ($pos = api_strpos($temp, ']'))) {
                                // adds the end of the text
                                $answer .= $temp;

                                break;
                            }
                            if ($from_database) {
                                $str = $answerFromDatabase;
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
                                // This value is the user input, not escaped while correct answer is escaped by ckeditor
                                $choice[$j] = api_htmlentities(trim($choice[$j]));
                            }

                            $user_tags[] = $choice[$j];
                            // Put the contents of the [] answer tag into correct_tags[]
                            $correct_tags[] = api_substr($temp, 0, $pos);
                            $j++;
                            $temp = api_substr($temp, $pos + 1);
                        }
                        $answer = '';
                        $real_correct_tags = $correct_tags;
                        $chosen_list = [];

                        for ($i = 0; $i < count($real_correct_tags); $i++) {
                            if (0 == $i) {
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
                                    // else if the word entered by the student IS NOT the same as
                                    // the one defined by the professor
                                    // adds the word in red at the end of the string, and strikes it
                                    $answer .= '<font color="red"><s>'.$user_tags[$i].'</s></font>';
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
                                } elseif (!empty($user_tags[$i])) {
                                    // else if the word entered by the student IS NOT the same
                                    // as the one defined by the professor
                                    // adds the word in red at the end of the string, and strikes it
                                    $answer .= '<font color="red"><s>'.$user_tags[$i].'</s></font>';
                                } else {
                                    // adds a tabulation if no word has been typed by the student
                                    $answer .= ''; // remove &nbsp; that causes issue
                                }
                            }

                            // adds the correct word, followed by ] to close the blank
                            $answer .= ' / <font color="green"><b>'.$real_correct_tags[$i].'</b></font>]';
                            if (isset($real_text[$i + 1])) {
                                $answer .= $real_text[$i + 1];
                            }
                        }
                    } else {
                        // insert the student result in the track_e_attempt table, field answer
                        // $answer is the answer like in the c_quiz_answer table for the question
                        // student data are choice[]
                        $listCorrectAnswers = FillBlanks::getAnswerInfo($answer);
                        $switchableAnswerSet = $listCorrectAnswers['switchable'];
                        $answerWeighting = $listCorrectAnswers['weighting'];
                        // user choices is an array $choice

                        // get existing user data in n the BDD
                        if ($from_database) {
                            $listStudentResults = FillBlanks::getAnswerInfo(
                                $answerFromDatabase,
                                true
                            );
                            $choice = $listStudentResults['student_answer'];
                        }

                        // loop other all blanks words
                        if (!$switchableAnswerSet) {
                            // not switchable answer, must be in the same place than teacher order
                            for ($i = 0; $i < count($listCorrectAnswers['words']); $i++) {
                                $studentAnswer = isset($choice[$i]) ? $choice[$i] : '';
                                $correctAnswer = $listCorrectAnswers['words'][$i];

                                if ($debug) {
                                    error_log("Student answer: $i");
                                    error_log($studentAnswer);
                                }

                                // This value is the user input, not escaped while correct answer is escaped by ckeditor
                                // Works with cyrillic alphabet and when using ">" chars see #7718 #7610 #7618
                                // ENT_QUOTES is used in order to transform ' to &#039;
                                if (!$from_database) {
                                    $studentAnswer = FillBlanks::clearStudentAnswer($studentAnswer);
                                    if ($debug) {
                                        error_log('Student answer cleaned:');
                                        error_log($studentAnswer);
                                    }
                                }

                                $isAnswerCorrect = 0;
                                if (FillBlanks::isStudentAnswerGood($studentAnswer, $correctAnswer, $from_database)) {
                                    // gives the related weighting to the student
                                    $questionScore += $answerWeighting[$i];
                                    // increments total score
                                    $totalScore += $answerWeighting[$i];
                                    $isAnswerCorrect = 1;
                                }
                                if ($debug) {
                                    error_log("isAnswerCorrect $i: $isAnswerCorrect");
                                }

                                $studentAnswerToShow = $studentAnswer;
                                $type = FillBlanks::getFillTheBlankAnswerType($correctAnswer);
                                if ($debug) {
                                    error_log("Fill in blank type: $type");
                                }
                                if (FillBlanks::FILL_THE_BLANK_MENU == $type) {
                                    $listMenu = FillBlanks::getFillTheBlankMenuAnswers($correctAnswer, false);
                                    if ('' != $studentAnswer) {
                                        foreach ($listMenu as $item) {
                                            if (sha1($item) == $studentAnswer) {
                                                $studentAnswerToShow = $item;
                                            }
                                        }
                                    }
                                }
                                $listCorrectAnswers['student_answer'][$i] = $studentAnswerToShow;
                                $listCorrectAnswers['student_score'][$i] = $isAnswerCorrect;
                            }
                        } else {
                            // switchable answer
                            $listStudentAnswerTemp = $choice;
                            $listTeacherAnswerTemp = $listCorrectAnswers['words'];

                            // for every teacher answer, check if there is a student answer
                            for ($i = 0; $i < count($listStudentAnswerTemp); $i++) {
                                $studentAnswer = trim($listStudentAnswerTemp[$i]);
                                $studentAnswerToShow = $studentAnswer;

                                if (empty($studentAnswer)) {
                                    break;
                                }

                                if ($debug) {
                                    error_log("Student answer: $i");
                                    error_log($studentAnswer);
                                }

                                if (!$from_database) {
                                    $studentAnswer = FillBlanks::clearStudentAnswer($studentAnswer);
                                    if ($debug) {
                                        error_log("Student answer cleaned:");
                                        error_log($studentAnswer);
                                    }
                                }

                                $found = false;
                                for ($j = 0; $j < count($listTeacherAnswerTemp); $j++) {
                                    $correctAnswer = $listTeacherAnswerTemp[$j];

                                    if (!$found) {
                                        if (FillBlanks::isStudentAnswerGood(
                                            $studentAnswer,
                                            $correctAnswer,
                                            $from_database
                                        )) {
                                            $questionScore += $answerWeighting[$i];
                                            $totalScore += $answerWeighting[$i];
                                            $listTeacherAnswerTemp[$j] = '';
                                            $found = true;
                                        }
                                    }

                                    $type = FillBlanks::getFillTheBlankAnswerType($correctAnswer);
                                    if (FillBlanks::FILL_THE_BLANK_MENU == $type) {
                                        $listMenu = FillBlanks::getFillTheBlankMenuAnswers($correctAnswer, false);
                                        if (!empty($studentAnswer)) {
                                            foreach ($listMenu as $key => $item) {
                                                if ($key == $correctAnswer) {
                                                    $studentAnswerToShow = $item;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                                $listCorrectAnswers['student_answer'][$i] = $studentAnswerToShow;
                                $listCorrectAnswers['student_score'][$i] = $found ? 1 : 0;
                            }
                        }
                        $answer = FillBlanks::getAnswerInStudentAttempt($listCorrectAnswers);
                    }

                    break;
                case CALCULATED_ANSWER:
                    $calculatedAnswerList = Session::read('calculatedAnswerId');
                    if (!empty($calculatedAnswerList)) {
                        $answer = $objAnswerTmp->selectAnswer($calculatedAnswerList[$questionId]);
                        $preArray = explode('@@', $answer);
                        $last = count($preArray) - 1;
                        $answer = '';
                        for ($k = 0; $k < $last; $k++) {
                            $answer .= $preArray[$k];
                        }
                        $answerWeighting = [$answerWeighting];
                        // we save the answer because it will be modified
                        $temp = $answer;
                        $answer = '';
                        $j = 0;
                        // initialise answer tags
                        $userTags = $correctTags = $realText = [];
                        // the loop will stop at the end of the text
                        while (1) {
                            // quits the loop if there are no more blanks (detect '[')
                            if (false == $temp || false === ($pos = api_strpos($temp, '['))) {
                                // adds the end of the text
                                $answer = $temp;
                                $realText[] = $answer;

                                break; //no more "blanks", quit the loop
                            }
                            // adds the piece of text that is before the blank
                            // and ends with '[' into a general storage array
                            $realText[] = api_substr($temp, 0, $pos + 1);
                            $answer .= api_substr($temp, 0, $pos + 1);
                            // take the string remaining (after the last "[" we found)
                            $temp = api_substr($temp, $pos + 1);
                            // quit the loop if there are no more blanks, and update $pos to the position of next ']'
                            if (false === ($pos = api_strpos($temp, ']'))) {
                                // adds the end of the text
                                $answer .= $temp;

                                break;
                            }

                            if ($from_database) {
                                $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                        WHERE
                                            exe_id = $exeId AND
                                            question_id = $questionId ";
                                $result = Database::query($sql);
                                $str = Database::result($result, 0, 'answer');
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
                                // This value is the user input not escaped while correct answer is escaped by ckeditor
                                $choice[$j] = api_htmlentities(trim($choice[$j]));
                            }
                            $userTags[] = $choice[$j];
                            // put the contents of the [] answer tag into correct_tags[]
                            $correctTags[] = api_substr($temp, 0, $pos);
                            $j++;
                            $temp = api_substr($temp, $pos + 1);
                        }
                        $answer = '';
                        $realCorrectTags = $correctTags;
                        $calculatedStatus = Display::label(get_lang('Incorrect'), 'danger');
                        $expectedAnswer = '';
                        $calculatedChoice = '';

                        for ($i = 0; $i < count($realCorrectTags); $i++) {
                            if (0 == $i) {
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
                                $calculatedChoice = $correctTags[$i];
                            } elseif (!empty($userTags[$i])) {
                                // else if the word entered by the student IS NOT the same as
                                // the one defined by the professor
                                // adds the word in red at the end of the string, and strikes it
                                $answer .= '<font color="red"><s>'.$userTags[$i].'</s></font>';
                                $calculatedChoice = $userTags[$i];
                            } else {
                                // adds a tabulation if no word has been typed by the student
                                $answer .= ''; // remove &nbsp; that causes issue
                            }
                            // adds the correct word, followed by ] to close the blank
                            if (EXERCISE_FEEDBACK_TYPE_EXAM != $this->results_disabled) {
                                $answer .= ' / <font color="green"><b>'.$realCorrectTags[$i].'</b></font>';
                                $calculatedStatus = Display::label(get_lang('Correct'), 'success');
                                $expectedAnswer = $realCorrectTags[$i];
                            }
                            $answer .= ']';
                            if (isset($realText[$i + 1])) {
                                $answer .= $realText[$i + 1];
                            }
                        }
                    } else {
                        if ($from_database) {
                            $sql = "SELECT *
                                    FROM $TBL_TRACK_ATTEMPT
                                    WHERE
                                        exe_id = $exeId AND
                                        question_id = $questionId ";
                            $result = Database::query($sql);
                            $resultData = Database::fetch_array($result, 'ASSOC');
                            $answer = $resultData['answer'];
                            $questionScore = $resultData['marks'];
                        }
                    }

                    break;
                case FREE_ANSWER:
                    if ($from_database) {
                        $sql = "SELECT answer, marks FROM $TBL_TRACK_ATTEMPT
                                 WHERE
                                    exe_id = $exeId AND
                                    question_id= ".$questionId;
                        $result = Database::query($sql);
                        $data = Database::fetch_array($result);
                        $choice = '';
                        $questionScore = 0;
                        if ($data) {
                            $choice = $data['answer'];
                            $questionScore = $data['marks'];
                        }

                        $choice = str_replace('\r\n', '', $choice);
                        $choice = stripslashes($choice);

                        if (-1 == $questionScore) {
                            $totalScore += 0;
                        } else {
                            $totalScore += $questionScore;
                        }
                        if ('' == $questionScore) {
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
                        $choice = '';
                        $questionScore = 0;

                        if (is_array($row)) {
                            $choice = $row['answer'];
                            $choice = str_replace('\r\n', '', $choice);
                            $choice = stripslashes($choice);
                            $questionScore = $row['marks'];
                        }

                        if (-1 == $questionScore) {
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
                case DRAGGABLE:
                case MATCHING_DRAGGABLE:
                case MATCHING:
                    if ($from_database) {
                        $sql = "SELECT iid, answer
                                FROM $table_ans
                                WHERE
                                    question_id = $questionId AND
                                    correct = 0
                                ";
                        $result = Database::query($sql);
                        // Getting the real answer
                        $real_list = [];
                        while ($realAnswer = Database::fetch_array($result)) {
                            $real_list[$realAnswer['iid']] = $realAnswer['answer'];
                        }

                        $orderBy = ' ORDER BY iid ';
                        if (DRAGGABLE == $answerType) {
                            $orderBy = ' ORDER BY correct ';
                        }

                        $sql = "SELECT iid, answer, correct, ponderation
                                FROM $table_ans
                                WHERE
                                    question_id = $questionId AND
                                    correct <> 0
                                $orderBy";
                        $result = Database::query($sql);
                        $options = [];
                        $correctAnswers = [];
                        while ($row = Database::fetch_array($result, 'ASSOC')) {
                            $options[] = $row;
                            $correctAnswers[$row['correct']] = $row['answer'];
                        }

                        $questionScore = 0;
                        $counterAnswer = 1;
                        foreach ($options as $a_answers) {
                            $i_answer_id = $a_answers['iid']; //3
                            $s_answer_label = $a_answers['answer']; // your daddy - your mother
                            $i_answer_correct_answer = $a_answers['correct']; //1 - 2
                            $i_answer_id_auto = $a_answers['iid']; // 3 - 4

                            $sql = "SELECT answer FROM $TBL_TRACK_ATTEMPT
                                    WHERE
                                        exe_id = '$exeId' AND
                                        question_id = '$questionId' AND
                                        position = '$i_answer_id_auto'";
                            $result = Database::query($sql);
                            $s_user_answer = 0;
                            if (Database::num_rows($result) > 0) {
                                //  rich - good looking
                                $s_user_answer = Database::result($result, 0, 0);
                            }
                            $i_answerWeighting = $a_answers['ponderation'];
                            $user_answer = '';
                            $status = Display::label(get_lang('Incorrect'), 'danger');

                            if (!empty($s_user_answer)) {
                                if (DRAGGABLE == $answerType) {
                                    if ($s_user_answer == $i_answer_correct_answer) {
                                        $questionScore += $i_answerWeighting;
                                        $totalScore += $i_answerWeighting;
                                        $user_answer = Display::label(get_lang('Correct'), 'success');
                                        if ($this->showExpectedChoice() && !empty($i_answer_id_auto)) {
                                            $user_answer = $answerMatching[$i_answer_id_auto];
                                        }
                                        $status = Display::label(get_lang('Correct'), 'success');
                                    } else {
                                        $user_answer = Display::label(get_lang('Incorrect'), 'danger');
                                        if ($this->showExpectedChoice() && !empty($s_user_answer)) {
                                            /*$data = $options[$real_list[$s_user_answer] - 1];
                                            $user_answer = $data['answer'];*/
                                            $user_answer = $correctAnswers[$s_user_answer] ?? '';
                                        }
                                    }
                                } else {
                                    if ($s_user_answer == $i_answer_correct_answer) {
                                        $questionScore += $i_answerWeighting;
                                        $totalScore += $i_answerWeighting;
                                        $status = Display::label(get_lang('Correct'), 'success');

                                        // Try with id
                                        if (isset($real_list[$i_answer_id])) {
                                            $user_answer = Display::span(
                                                $real_list[$i_answer_id],
                                                ['style' => 'color: #008000; font-weight: bold;']
                                            );
                                        }

                                        // Try with $i_answer_id_auto
                                        if (empty($user_answer)) {
                                            if (isset($real_list[$i_answer_id_auto])) {
                                                $user_answer = Display::span(
                                                    $real_list[$i_answer_id_auto],
                                                    ['style' => 'color: #008000; font-weight: bold;']
                                                );
                                            }
                                        }

                                        if (isset($real_list[$i_answer_correct_answer])) {
                                            $user_answer = Display::span(
                                                $real_list[$i_answer_correct_answer],
                                                ['style' => 'color: #008000; font-weight: bold;']
                                            );
                                        }
                                    } else {
                                        $user_answer = Display::span(
                                            $real_list[$s_user_answer],
                                            ['style' => 'color: #FF0000; text-decoration: line-through;']
                                        );
                                        if ($this->showExpectedChoice()) {
                                            if (isset($real_list[$s_user_answer])) {
                                                $user_answer = Display::span($real_list[$s_user_answer]);
                                            }
                                        }
                                    }
                                }
                            } elseif (DRAGGABLE == $answerType) {
                                $user_answer = Display::label(get_lang('Incorrect'), 'danger');
                                if ($this->showExpectedChoice()) {
                                    $user_answer = '';
                                }
                            } else {
                                $user_answer = Display::span(
                                    get_lang('Incorrect').' &nbsp;',
                                    ['style' => 'color: #FF0000; text-decoration: line-through;']
                                );
                                if ($this->showExpectedChoice()) {
                                    $user_answer = '';
                                }
                            }

                            if ($show_result) {
                                if (false === $this->showExpectedChoice() &&
                                    false === $showTotalScoreAndUserChoicesInLastAttempt
                                ) {
                                    $user_answer = '';
                                }
                                switch ($answerType) {
                                    case MATCHING:
                                    case MATCHING_DRAGGABLE:
                                        if (RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK == $this->results_disabled) {
                                            if (false === $showTotalScoreAndUserChoicesInLastAttempt && empty($s_user_answer)) {
                                                break;
                                            }
                                        }
                                        echo '<tr>';
                                        if (!in_array(
                                            $this->results_disabled,
                                            [
                                                RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
                                            ]
                                        )
                                        ) {
                                            echo '<td>'.$s_answer_label.'</td>';
                                            echo '<td>'.$user_answer.'</td>';
                                        } else {
                                            echo '<td>'.$s_answer_label.'</td>';
                                            $status = Display::label(get_lang('Correct'), 'success');
                                        }

                                        if ($this->showExpectedChoice()) {
                                            if ($this->showExpectedChoiceColumn()) {
                                                echo '<td>';
                                                if (in_array($answerType, [MATCHING, MATCHING_DRAGGABLE])) {
                                                    if (isset($real_list[$i_answer_correct_answer]) &&
                                                        true == $showTotalScoreAndUserChoicesInLastAttempt
                                                    ) {
                                                        echo Display::span(
                                                            $real_list[$i_answer_correct_answer]
                                                        );
                                                    }
                                                }
                                                echo '</td>';
                                            }
                                            echo '<td class="text-center">'.$status.'</td>';
                                        } else {
                                            if (in_array($answerType, [MATCHING, MATCHING_DRAGGABLE])) {
                                                if (isset($real_list[$i_answer_correct_answer]) &&
                                                    true === $showTotalScoreAndUserChoicesInLastAttempt
                                                ) {
                                                    if ($this->showExpectedChoiceColumn()) {
                                                        echo '<td>';
                                                        echo Display::span(
                                                            $real_list[$i_answer_correct_answer],
                                                            ['style' => 'color: #008000; font-weight: bold;']
                                                        );
                                                        echo '</td>';
                                                    }
                                                }
                                            }
                                        }
                                        echo '</tr>';

                                        break;
                                    case DRAGGABLE:
                                        if (false == $showTotalScoreAndUserChoicesInLastAttempt) {
                                            $s_answer_label = '';
                                        }
                                        if (RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK == $this->results_disabled) {
                                            if (false === $showTotalScoreAndUserChoicesInLastAttempt && empty($s_user_answer)) {
                                                break;
                                            }
                                        }
                                        echo '<tr>';
                                        if ($this->showExpectedChoice()) {
                                            if (!in_array(
                                                $this->results_disabled,
                                                [
                                                    RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
                                                    //RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
                                                ]
                                            )
                                            ) {
                                                echo '<td>'.$user_answer.'</td>';
                                            } else {
                                                $status = Display::label(get_lang('Correct'), 'success');
                                            }
                                            echo '<td>'.$s_answer_label.'</td>';
                                            echo '<td class="text-center">'.$status.'</td>';
                                        } else {
                                            echo '<td>'.$s_answer_label.'</td>';
                                            echo '<td>'.$user_answer.'</td>';
                                            echo '<td>';
                                            if (in_array($answerType, [MATCHING, MATCHING_DRAGGABLE])) {
                                                if (isset($real_list[$i_answer_correct_answer]) &&
                                                    true === $showTotalScoreAndUserChoicesInLastAttempt
                                                ) {
                                                    echo Display::span(
                                                        $real_list[$i_answer_correct_answer],
                                                        ['style' => 'color: #008000; font-weight: bold;']
                                                    );
                                                }
                                            }
                                            echo '</td>';
                                        }
                                        echo '</tr>';

                                        break;
                                }
                            }
                            $counterAnswer++;
                        }

                        break 2; // break the switch and the "for" condition
                    } else {
                        if ($answerCorrect) {
                            if (isset($choice[$answerAutoId]) &&
                                $answerCorrect == $choice[$answerAutoId]
                            ) {
                                $correctAnswerId[] = $answerAutoId;
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
                    }

                    break;
                case HOT_SPOT:
                    if ($from_database) {
                        $TBL_TRACK_HOTSPOT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
                        // Check auto id
                        $foundAnswerId = $answerAutoId;
                        $sql = "SELECT hotspot_correct
                                FROM $TBL_TRACK_HOTSPOT
                                WHERE
                                    hotspot_exe_id = $exeId AND
                                    hotspot_question_id= $questionId AND
                                    hotspot_answer_id = $answerAutoId
                                ORDER BY hotspot_id ASC";
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
                                    hotspot_answer_id = ".(int) $answerId.'
                                ORDER BY hotspot_id ASC';
                            $result = Database::query($sql);

                            $foundAnswerId = $answerId;
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
                                                hotspot_answer_id = $answerIid
                                            ORDER BY hotspot_id ASC";
                                    $result = Database::query($sql);

                                    $foundAnswerId = $answerIid;
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
                case HOT_SPOT_ORDER:
                    // @todo never added to chamilo
                    // for hotspot with fixed order
                    $studentChoice = $choice['order'][$answerId];
                    if ($studentChoice == $answerId) {
                        $questionScore += $answerWeighting;
                        $totalScore += $answerWeighting;
                        $studentChoice = true;
                    } else {
                        $studentChoice = false;
                    }

                    break;
                case HOT_SPOT_DELINEATION:
                    // for hotspot with delineation
                    if ($from_database) {
                        // getting the user answer
                        $TBL_TRACK_HOTSPOT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
                        $query = "SELECT hotspot_correct, hotspot_coordinate
                                    FROM $TBL_TRACK_HOTSPOT
                                    WHERE
                                        hotspot_exe_id = $exeId AND
                                        hotspot_question_id= $questionId AND
                                        hotspot_answer_id = '1'";
                        // By default we take 1 because it's a delineation
                        $resq = Database::query($query);
                        $row = Database::fetch_array($resq, 'ASSOC');

                        $choice = $row['hotspot_correct'];
                        $user_answer = $row['hotspot_coordinate'];

                        // THIS is very important otherwise the poly_compile will throw an error!!
                        // round-up the coordinates
                        $coords = explode('/', $user_answer);
                        $coords = array_filter($coords);
                        $user_array = '';
                        foreach ($coords as $coord) {
                            [$x, $y] = explode(';', $coord);
                            $user_array .= round($x).';'.round($y).'/';
                        }
                        $user_array = substr($user_array, 0, -1) ?: '';
                    } else {
                        if (!empty($studentChoice)) {
                            $correctAnswerId[] = $answerAutoId;
                            $newquestionList[] = $questionId;
                        }

                        if (1 === $answerId) {
                            $studentChoice = $choice[$answerId];
                            $questionScore += $answerWeighting;
                        }
                        if (isset($_SESSION['exerciseResultCoordinates'][$questionId])) {
                            $user_array = $_SESSION['exerciseResultCoordinates'][$questionId];
                        }
                    }
                    $_SESSION['hotspot_coord'][$questionId][1] = $delineation_cord;
                    $_SESSION['hotspot_dest'][$questionId][1] = $answer_delineation_destination;

                    break;
                case ANNOTATION:
                    if ($from_database) {
                        $sql = "SELECT answer, marks
                                FROM $TBL_TRACK_ATTEMPT
                                WHERE
                                  exe_id = $exeId AND
                                  question_id = $questionId ";
                        $resq = Database::query($sql);
                        $data = Database::fetch_array($resq);

                        $questionScore = empty($data['marks']) ? 0 : $data['marks'];
                        $arrques = $questionName;

                        break;
                    }
                    $studentChoice = $choice;
                    if ($studentChoice) {
                        $questionScore = 0;
                    }

                    break;
            }

            if ($show_result) {
                if ('exercise_result' === $from) {
                    // Display answers (if not matching type, or if the answer is correct)
                    if (!in_array($answerType, [MATCHING, DRAGGABLE, MATCHING_DRAGGABLE]) ||
                        $answerCorrect
                    ) {
                        if (in_array(
                            $answerType,
                            [
                                UNIQUE_ANSWER,
                                UNIQUE_ANSWER_IMAGE,
                                UNIQUE_ANSWER_NO_OPTION,
                                MULTIPLE_ANSWER,
                                MULTIPLE_ANSWER_COMBINATION,
                                GLOBAL_MULTIPLE_ANSWER,
                                READING_COMPREHENSION,
                            ]
                        )) {
                            ExerciseShowFunctions::display_unique_or_multiple_answer(
                                $this,
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
                                $showTotalScoreAndUserChoicesInLastAttempt,
                                $this->export
                            );
                        } elseif (MULTIPLE_ANSWER_TRUE_FALSE == $answerType) {
                            ExerciseShowFunctions::display_multiple_answer_true_false(
                                $this,
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
                        } elseif (MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY == $answerType) {
                            ExerciseShowFunctions::displayMultipleAnswerTrueFalseDegreeCertainty(
                                $this,
                                $feedback_type,
                                $studentChoice,
                                $studentChoiceDegree,
                                $answer,
                                $answerComment,
                                $answerCorrect,
                                $questionId,
                                $results_disabled
                            );
                        } elseif (MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE == $answerType) {
                            ExerciseShowFunctions::display_multiple_answer_combination_true_false(
                                $this,
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
                        } elseif (FILL_IN_BLANKS == $answerType) {
                            ExerciseShowFunctions::display_fill_in_blanks_answer(
                                $this,
                                $feedback_type,
                                $answer,
                                0,
                                0,
                                $results_disabled,
                                '',
                                $showTotalScoreAndUserChoicesInLastAttempt
                            );
                        } elseif (CALCULATED_ANSWER == $answerType) {
                            ExerciseShowFunctions::display_calculated_answer(
                                $this,
                                $feedback_type,
                                $answer,
                                0,
                                0,
                                $results_disabled,
                                $showTotalScoreAndUserChoicesInLastAttempt,
                                $expectedAnswer,
                                $calculatedChoice,
                                $calculatedStatus
                            );
                        } elseif (FREE_ANSWER == $answerType) {
                            ExerciseShowFunctions::display_free_answer(
                                $feedback_type,
                                $choice,
                                $exeId,
                                $questionId,
                                $questionScore,
                                $results_disabled
                            );
                        } elseif (ORAL_EXPRESSION == $answerType) {
                            // to store the details of open questions in an array to be used in mail
                            /** @var OralExpression $objQuestionTmp */
                            ExerciseShowFunctions::display_oral_expression_answer(
                                $feedback_type,
                                $choice,
                                $exeId,
                                $questionId,
                                $results_disabled,
                                $questionScore,
                                true
                            );
                        } elseif (HOT_SPOT == $answerType) {
                            $correctAnswerId = 0;
                            /** @var TrackEHotspot $hotspot */
                            foreach ($orderedHotSpots as $correctAnswerId => $hotspot) {
                                if ($hotspot->getHotspotAnswerId() == $answerAutoId) {
                                    break;
                                }
                            }

                            // force to show whether the choice is correct or not
                            $showTotalScoreAndUserChoicesInLastAttempt = true;
                            ExerciseShowFunctions::display_hotspot_answer(
                                $this,
                                $feedback_type,
                                $answerId,
                                $answer,
                                $studentChoice,
                                $answerComment,
                                $results_disabled,
                                $answerId,
                                $showTotalScoreAndUserChoicesInLastAttempt
                            );
                        } elseif (HOT_SPOT_ORDER == $answerType) {
                            /*ExerciseShowFunctions::display_hotspot_order_answer(
                                $feedback_type,
                                $answerId,
                                $answer,
                                $studentChoice,
                                $answerComment
                            );*/
                        } elseif (HOT_SPOT_DELINEATION == $answerType) {
                            $user_answer = $_SESSION['exerciseResultCoordinates'][$questionId];

                            // Round-up the coordinates
                            $coords = explode('/', $user_answer);
                            $coords = array_filter($coords);
                            $user_array = '';
                            foreach ($coords as $coord) {
                                if (!empty($coord)) {
                                    $parts = explode(';', $coord);
                                    if (!empty($parts)) {
                                        $user_array .= round($parts[0]).';'.round($parts[1]).'/';
                                    }
                                }
                            }
                            $user_array = substr($user_array, 0, -1) ?: '';
                            if ($next) {
                                $user_answer = $user_array;
                                // We compare only the delineation not the other points
                                $answer_question = $_SESSION['hotspot_coord'][$questionId][1];
                                $answerDestination = $_SESSION['hotspot_dest'][$questionId][1];

                                // Calculating the area
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

                                // //this is an area in pixels
                                if ($debug > 0) {
                                    error_log(__LINE__.' - Polygons results are '.print_r($poly_results, 1), 0);
                                }

                                if ($overlap < 1) {
                                    // Shortcut to avoid complicated calculations
                                    $final_overlap = 0;
                                    $final_missing = 100;
                                    $final_excess = 100;
                                } else {
                                    // the final overlap is the percentage of the initial polygon
                                    // that is overlapped by the user's polygon
                                    $final_overlap = round(((float) $overlap / (float) $poly_answer_area) * 100);
                                    if ($debug > 1) {
                                        error_log(__LINE__.' - Final overlap is '.$final_overlap, 0);
                                    }
                                    // the final missing area is the percentage of the initial polygon
                                    // that is not overlapped by the user's polygon
                                    $final_missing = 100 - $final_overlap;
                                    if ($debug > 1) {
                                        error_log(__LINE__.' - Final missing is '.$final_missing, 0);
                                    }
                                    // the final excess area is the percentage of the initial polygon's size
                                    // that is covered by the user's polygon outside of the initial polygon
                                    $final_excess = round(
                                        (((float) $poly_user_area - (float) $overlap) / (float) $poly_answer_area) * 100
                                    );
                                    if ($debug > 1) {
                                        error_log(__LINE__.' - Final excess is '.$final_excess, 0);
                                    }
                                }

                                // Checking the destination parameters parsing the "@@"
                                $destination_items = explode('@@', $answerDestination);
                                $threadhold_total = $destination_items[0];
                                $threadhold_items = explode(';', $threadhold_total);
                                $threadhold1 = $threadhold_items[0]; // overlap
                                $threadhold2 = $threadhold_items[1]; // excess
                                $threadhold3 = $threadhold_items[2]; // missing

                                // if is delineation
                                if (1 === $answerId) {
                                    //setting colors
                                    if ($final_overlap >= $threadhold1) {
                                        $overlap_color = true;
                                    }
                                    if ($final_excess <= $threadhold2) {
                                        $excess_color = true;
                                    }
                                    if ($final_missing <= $threadhold3) {
                                        $missing_color = true;
                                    }

                                    // if pass
                                    if ($final_overlap >= $threadhold1 &&
                                        $final_missing <= $threadhold3 &&
                                        $final_excess <= $threadhold2
                                    ) {
                                        $next = 1; //go to the oars
                                        $result_comment = get_lang('Acceptable');
                                        $final_answer = 1; // do not update with  update_exercise_attempt
                                    } else {
                                        $next = 0;
                                        $result_comment = get_lang('Unacceptable');
                                        $comment = $answerDestination = $objAnswerTmp->selectComment(1);
                                        $answerDestination = $objAnswerTmp->selectDestination(1);
                                        // checking the destination parameters parsing the "@@"
                                        $destination_items = explode('@@', $answerDestination);
                                    }
                                } elseif ($answerId > 1) {
                                    if ('noerror' == $objAnswerTmp->selectHotspotType($answerId)) {
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
                                    $delineation_cord = $objAnswerTmp->selectHotspotCoordinates($answerId);
                                    $poly_answer = convert_coordinates($delineation_cord, '|');
                                    $max_coord = poly_get_max($poly_user, $poly_answer);
                                    $poly_answer_compiled = poly_compile($poly_answer, $max_coord);
                                    $overlap = poly_touch($poly_user_compiled, $poly_answer_compiled, $max_coord);

                                    if (false == $overlap) {
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
                            } else {
                                // the first delineation feedback
                                if ($debug > 0) {
                                    error_log(__LINE__.' first', 0);
                                }
                            }
                        } elseif (in_array($answerType, [MATCHING, MATCHING_DRAGGABLE])) {
                            echo '<tr>';
                            echo Display::tag('td', $answerMatching[$answerId]);
                            echo Display::tag(
                                'td',
                                "$user_answer / ".Display::tag(
                                    'strong',
                                    $answerMatching[$answerCorrect],
                                    ['style' => 'color: #008000; font-weight: bold;']
                                )
                            );
                            echo '</tr>';
                        } elseif (ANNOTATION == $answerType) {
                            ExerciseShowFunctions::displayAnnotationAnswer(
                                $feedback_type,
                                $exeId,
                                $questionId,
                                $questionScore,
                                $results_disabled
                            );
                        }
                    }
                } else {
                    if ($debug) {
                        error_log('Showing questions $from '.$from);
                    }

                    switch ($answerType) {
                        case UNIQUE_ANSWER:
                        case UNIQUE_ANSWER_IMAGE:
                        case UNIQUE_ANSWER_NO_OPTION:
                        case MULTIPLE_ANSWER:
                        case GLOBAL_MULTIPLE_ANSWER:
                        case MULTIPLE_ANSWER_COMBINATION:
                        case READING_COMPREHENSION:
                            if (1 == $answerId) {
                                ExerciseShowFunctions::display_unique_or_multiple_answer(
                                    $this,
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
                                    $showTotalScoreAndUserChoicesInLastAttempt,
                                    $this->export
                                );
                            } else {
                                ExerciseShowFunctions::display_unique_or_multiple_answer(
                                    $this,
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
                                    $showTotalScoreAndUserChoicesInLastAttempt,
                                    $this->export
                                );
                            }

                            break;
                        case MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE:
                            if (1 == $answerId) {
                                ExerciseShowFunctions::display_multiple_answer_combination_true_false(
                                    $this,
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
                                    $this,
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
                            if (1 == $answerId) {
                                ExerciseShowFunctions::display_multiple_answer_true_false(
                                    $this,
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
                                    $this,
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
                        case MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY:
                            if (1 == $answerId) {
                                ExerciseShowFunctions::displayMultipleAnswerTrueFalseDegreeCertainty(
                                    $this,
                                    $feedback_type,
                                    $studentChoice,
                                    $studentChoiceDegree,
                                    $answer,
                                    $answerComment,
                                    $answerCorrect,
                                    $questionId,
                                    $results_disabled
                                );
                            } else {
                                ExerciseShowFunctions::displayMultipleAnswerTrueFalseDegreeCertainty(
                                    $this,
                                    $feedback_type,
                                    $studentChoice,
                                    $studentChoiceDegree,
                                    $answer,
                                    $answerComment,
                                    $answerCorrect,
                                    $questionId,
                                    $results_disabled
                                );
                            }

                            break;
                        case FILL_IN_BLANKS:
                            ExerciseShowFunctions::display_fill_in_blanks_answer(
                                $this,
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
                                $this,
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
                            /** @var OralExpression $objQuestionTmp */
                            echo '<tr>
                                <td valign="top">'.
                                ExerciseShowFunctions::display_oral_expression_answer(
                                    $feedback_type,
                                    $choice,
                                    $exeId,
                                    $questionId,
                                    $results_disabled,
                                    $questionScore
                                ).'</td>
                                </tr>
                                </table>';
                            break;
                        case HOT_SPOT:
                            $correctAnswerId = 0;

                            foreach ($orderedHotSpots as $correctAnswerId => $hotspot) {
                                if ($hotspot->getHotspotAnswerId() == $foundAnswerId) {
                                    break;
                                }
                            }
                            ExerciseShowFunctions::display_hotspot_answer(
                                $this,
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
                                $user_answer = $user_array;
                                // we compare only the delineation not the other points
                                $answer_question = $_SESSION['hotspot_coord'][$questionId][1];
                                $answerDestination = $_SESSION['hotspot_dest'][$questionId][1];

                                // calculating the area
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
                                if ($debug > 0) {
                                    error_log(__LINE__.' - Polygons results are '.print_r($poly_results, 1), 0);
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

                                    // the final missing area is the percentage of the initial polygon that
                                    // is not overlapped by the user's polygon
                                    $final_missing = 100 - $final_overlap;
                                    // the final excess area is the percentage of the initial polygon's size that is
                                    // covered by the user's polygon outside of the initial polygon
                                    $final_excess = round(
                                        (((float) $poly_user_area - (float) $overlap) / (float) $poly_answer_area) * 100
                                    );

                                    if ($debug > 1) {
                                        error_log(__LINE__.' - Final overlap is '.$final_overlap);
                                        error_log(__LINE__.' - Final excess is '.$final_excess);
                                        error_log(__LINE__.' - Final missing is '.$final_missing);
                                    }
                                }

                                // Checking the destination parameters parsing the "@@"
                                $destination_items = explode('@@', $answerDestination);
                                $threadhold_total = $destination_items[0];
                                $threadhold_items = explode(';', $threadhold_total);
                                $threadhold1 = $threadhold_items[0]; // overlap
                                $threadhold2 = $threadhold_items[1]; // excess
                                $threadhold3 = $threadhold_items[2]; //missing
                                // if is delineation
                                if (1 === $answerId) {
                                    //setting colors
                                    if ($final_overlap >= $threadhold1) {
                                        $overlap_color = true;
                                    }
                                    if ($final_excess <= $threadhold2) {
                                        $excess_color = true;
                                    }
                                    if ($final_missing <= $threadhold3) {
                                        $missing_color = true;
                                    }

                                    // if pass
                                    if ($final_overlap >= $threadhold1 &&
                                        $final_missing <= $threadhold3 &&
                                        $final_excess <= $threadhold2
                                    ) {
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
                                    if ('noerror' === $objAnswerTmp->selectHotspotType($answerId)) {
                                        if ($debug > 0) {
                                            error_log(__LINE__.' - answerId is of type noerror', 0);
                                        }
                                        //type no error shouldn't be treated
                                        $next = 1;

                                        break;
                                    }
                                    if ($debug > 0) {
                                        error_log(__LINE__.' - answerId is >1 so we\'re probably in OAR', 0);
                                    }
                                    $delineation_cord = $objAnswerTmp->selectHotspotCoordinates($answerId);
                                    $poly_answer = convert_coordinates($delineation_cord, '|');
                                    $max_coord = poly_get_max($poly_user, $poly_answer);
                                    $poly_answer_compiled = poly_compile($poly_answer, $max_coord);
                                    $overlap = poly_touch($poly_user_compiled, $poly_answer_compiled, $max_coord);

                                    if (false == $overlap) {
                                        //all good, no overlap
                                        $next = 1;

                                        break;
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
                            }

                            break;
                        case HOT_SPOT_ORDER:
                            /*ExerciseShowFunctions::display_hotspot_order_answer(
                                $feedback_type,
                                $answerId,
                                $answer,
                                $studentChoice,
                                $answerComment
                            );*/

                            break;
                        case DRAGGABLE:
                        case MATCHING_DRAGGABLE:
                        case MATCHING:
                            echo '<tr>';
                            echo Display::tag('td', $answerMatching[$answerId]);
                            echo Display::tag(
                                'td',
                                "$user_answer / ".Display::tag(
                                    'strong',
                                    $answerMatching[$answerCorrect],
                                    ['style' => 'color: #008000; font-weight: bold;']
                                )
                            );
                            echo '</tr>';

                            break;
                        case ANNOTATION:
                            ExerciseShowFunctions::displayAnnotationAnswer(
                                $feedback_type,
                                $exeId,
                                $questionId,
                                $questionScore,
                                $results_disabled
                            );

                            break;
                    }
                }
            }
        } // end for that loops over all answers of the current question

        if ($debug) {
            error_log('-- End answer loop --');
        }

        $final_answer = true;

        foreach ($real_answers as $my_answer) {
            if (!$my_answer) {
                $final_answer = false;
            }
        }

        //we add the total score after dealing with the answers
        if (MULTIPLE_ANSWER_COMBINATION == $answerType ||
            MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE == $answerType
        ) {
            if ($final_answer) {
                //getting only the first score where we save the weight of all the question
                $answerWeighting = $objAnswerTmp->selectWeighting(1);
                if (empty($answerWeighting) && !empty($firstAnswer) && isset($firstAnswer['ponderation'])) {
                    $answerWeighting = $firstAnswer['ponderation'];
                }
                $questionScore += $answerWeighting;
            }
        }

        $extra_data = [
            'final_overlap' => $final_overlap,
            'final_missing' => $final_missing,
            'final_excess' => $final_excess,
            'overlap_color' => $overlap_color,
            'missing_color' => $missing_color,
            'excess_color' => $excess_color,
            'threadhold1' => $threadhold1,
            'threadhold2' => $threadhold2,
            'threadhold3' => $threadhold3,
        ];

        if ('exercise_result' === $from) {
            // if answer is hotspot. To the difference of exercise_show.php,
            //  we use the results from the session (from_db=0)
            // TODO Change this, because it is wrong to show the user
            //  some results that haven't been stored in the database yet
            if (HOT_SPOT == $answerType || HOT_SPOT_ORDER == $answerType || HOT_SPOT_DELINEATION == $answerType) {
                if ($debug) {
                    error_log('$from AND this is a hotspot kind of question ');
                }
                if (HOT_SPOT_DELINEATION === $answerType) {
                    if ($showHotSpotDelineationTable) {
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

                        $overlap = 0;
                        if ($final_overlap > 0) {
                            $overlap = (int) $final_overlap;
                        }

                        $excess = 0;
                        if ($final_excess > 0) {
                            $excess = (int) $final_excess;
                        }

                        $missing = 0;
                        if ($final_missing > 0) {
                            $missing = (int) $final_missing;
                        }

                        $table_resume = '<table class="table table-hover table-striped data_table">
                                <tr class="row_odd" >
                                    <td></td>
                                    <td ><b>'.get_lang('Requirements').'</b></td>
                                    <td><b>'.get_lang('Your answer').'</b></td>
                                </tr>
                                <tr class="row_even">
                                    <td><b>'.get_lang('Overlapping areaping area').'</b></td>
                                    <td>'.get_lang('Minimum').' '.$threadhold1.'</td>
                                    <td class="text-right '.($overlap_color ? 'text-success' : 'text-danger').'">'
                                    .$overlap.'</td>
                                </tr>
                                <tr>
                                    <td><b>'.get_lang('Excessive areaive area').'</b></td>
                                    <td>'.get_lang('max. 20 characters, e.g. <i>INNOV21</i>').' '.$threadhold2.'</td>
                                    <td class="text-right '.($excess_color ? 'text-success' : 'text-danger').'">'
                                    .$excess.'</td>
                                </tr>
                                <tr class="row_even">
                                    <td><b>'.get_lang('Missing area area').'</b></td>
                                    <td>'.get_lang('max. 20 characters, e.g. <i>INNOV21</i>').' '.$threadhold3.'</td>
                                    <td class="text-right '.($missing_color ? 'text-success' : 'text-danger').'">'
                                    .$missing.'</td>
                                </tr>
                            </table>';
                        if (0 == $next) {
                        } else {
                            $comment = $answerComment = $objAnswerTmp->selectComment($nbrAnswers);
                            $answerDestination = $objAnswerTmp->selectDestination($nbrAnswers);
                        }

                        $message = '<h1><div style="color:#333;">'.get_lang('Feedback').'</div></h1>
                                    <p style="text-align:center">';
                        $message .= '<p>'.get_lang('Your delineation :').'</p>';
                        $message .= $table_resume;
                        $message .= '<br />'.get_lang('Your result is :').' '.$result_comment.'<br />';
                        if ($organs_at_risk_hit > 0) {
                            $message .= '<p><b>'.get_lang('One (or more) area at risk has been hit').'</b></p>';
                        }
                        $message .= '<p>'.$comment.'</p>';
                        echo $message;

                        $_SESSION['hotspot_delineation_result'][$this->getId()][$questionId][0] = $message;
                        $_SESSION['hotspot_delineation_result'][$this->getId()][$questionId][1] = $_SESSION['exerciseResultCoordinates'][$questionId];
                    } else {
                        echo $hotspot_delineation_result[0];
                    }

                    // Save the score attempts
                    if (1) {
                        //getting the answer 1 or 0 comes from exercise_submit_modal.php
                        $final_answer = $hotspot_delineation_result[1] ?? '';
                        if (0 == $final_answer) {
                            $questionScore = 0;
                        }
                        // we always insert the answer_id 1 = delineation
                        Event::saveQuestionAttempt($this, $questionScore, 1, $quesId, $exeId, 0);
                        //in delineation mode, get the answer from $hotspot_delineation_result[1]
                        $hotspotValue = isset($hotspot_delineation_result[1]) ? 1 === (int) $hotspot_delineation_result[1] ? 1 : 0 : 0;
                        Event::saveExerciseAttemptHotspot(
                            $this,
                            $exeId,
                            $quesId,
                            1,
                            $hotspotValue,
                            $exerciseResultCoordinates[$quesId],
                            false,
                            0,
                            $learnpath_id,
                            $learnpath_item_id
                        );
                    } else {
                        if (0 == $final_answer) {
                            $questionScore = 0;
                            $answer = 0;
                            Event::saveQuestionAttempt($this, $questionScore, $answer, $quesId, $exeId, 0);
                            if (is_array($exerciseResultCoordinates[$quesId])) {
                                foreach ($exerciseResultCoordinates[$quesId] as $idx => $val) {
                                    Event::saveExerciseAttemptHotspot(
                                        $this,
                                        $exeId,
                                        $quesId,
                                        $idx,
                                        0,
                                        $val,
                                        false,
                                        0,
                                        $learnpath_id,
                                        $learnpath_item_id
                                    );
                                }
                            }
                        } else {
                            Event::saveQuestionAttempt($this, $questionScore, $answer, $quesId, $exeId, 0);
                            if (is_array($exerciseResultCoordinates[$quesId])) {
                                foreach ($exerciseResultCoordinates[$quesId] as $idx => $val) {
                                    $hotspotValue = 1 === (int) $choice[$idx] ? 1 : 0;
                                    Event::saveExerciseAttemptHotspot(
                                        $this,
                                        $exeId,
                                        $quesId,
                                        $idx,
                                        $hotspotValue,
                                        $val,
                                        false,
                                        0,
                                        $learnpath_id,
                                        $learnpath_item_id
                                    );
                                }
                            }
                        }
                    }
                }
            }

            $relPath = api_get_path(WEB_CODE_PATH);

            if (HOT_SPOT == $answerType || HOT_SPOT_ORDER == $answerType) {
                // We made an extra table for the answers
                if ($show_result) {
                    echo '</table></td></tr>';
                    echo '
                        <tr>
                            <td colspan="2">
                                <p><em>'.get_lang('Image zones')."</em></p>
                                <div id=\"hotspot-solution-$questionId\"></div>
                                <script>
                                    $(function() {
                                        new HotspotQuestion({
                                            questionId: $questionId,
                                            exerciseId: {$this->getId()},
                                            exeId: $exeId,
                                            selector: '#hotspot-solution-$questionId',
                                            for: 'solution',
                                            relPath: '$relPath'
                                        });
                                    });
                                </script>
                            </td>
                        </tr>
                    ";
                }
            } elseif (ANNOTATION == $answerType) {
                if ($show_result) {
                    echo '
                        <p><em>'.get_lang('Annotation').'</em></p>
                        <div id="annotation-canvas-'.$questionId.'"></div>
                        <script>
                            AnnotationQuestion({
                                questionId: parseInt('.$questionId.'),
                                exerciseId: parseInt('.$exeId.'),
                                relPath: \''.$relPath.'\',
                                courseId: parseInt('.$course_id.')
                            });
                        </script>
                    ';
                }
            }

            if ($show_result && ANNOTATION != $answerType) {
                echo '</table>';
            }
        }
        unset($objAnswerTmp);

        $totalWeighting += $questionWeighting;
        // Store results directly in the database
        // For all in one page exercises, the results will be
        // stored by exercise_results.php (using the session)
        if ($save_results) {
            if ($debug) {
                error_log("Save question results $save_results");
                error_log("Question score: $questionScore");
                error_log('choice: ');
                error_log(print_r($choice, 1));
            }

            if (empty($choice)) {
                $choice = 0;
            }
            // with certainty degree
            if (empty($choiceDegreeCertainty)) {
                $choiceDegreeCertainty = 0;
            }
            if (MULTIPLE_ANSWER_TRUE_FALSE == $answerType ||
                MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE == $answerType ||
                MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY == $answerType
            ) {
                if (0 != $choice) {
                    $reply = array_keys($choice);
                    $countReply = count($reply);
                    for ($i = 0; $i < $countReply; $i++) {
                        $chosenAnswer = $reply[$i];
                        if (MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY == $answerType) {
                            if (0 != $choiceDegreeCertainty) {
                                $replyDegreeCertainty = array_keys($choiceDegreeCertainty);
                                $answerDegreeCertainty = isset($replyDegreeCertainty[$i]) ? $replyDegreeCertainty[$i] : '';
                                $answerValue = isset($choiceDegreeCertainty[$answerDegreeCertainty]) ? $choiceDegreeCertainty[$answerDegreeCertainty] : '';
                                Event::saveQuestionAttempt(
                                    $this,
                                    $questionScore,
                                    $chosenAnswer.':'.$choice[$chosenAnswer].':'.$answerValue,
                                    $quesId,
                                    $exeId,
                                    $i,
                                    $this->getId(),
                                    $updateResults,
                                    $questionDuration
                                );
                            }
                        } else {
                            Event::saveQuestionAttempt(
                                $this,
                                $questionScore,
                                $chosenAnswer.':'.$choice[$chosenAnswer],
                                $quesId,
                                $exeId,
                                $i,
                                $this->getId(),
                                $updateResults,
                                $questionDuration
                            );
                        }
                        if ($debug) {
                            error_log('result =>'.$questionScore.' '.$chosenAnswer.':'.$choice[$chosenAnswer]);
                        }
                    }
                } else {
                    Event::saveQuestionAttempt(
                        $this,
                        $questionScore,
                        0,
                        $quesId,
                        $exeId,
                        0,
                        $this->getId(),
                        false,
                        $questionDuration
                    );
                }
            } elseif (MULTIPLE_ANSWER == $answerType || GLOBAL_MULTIPLE_ANSWER == $answerType) {
                if (0 != $choice) {
                    $reply = array_keys($choice);
                    for ($i = 0; $i < count($reply); $i++) {
                        $ans = $reply[$i];
                        Event::saveQuestionAttempt(
                            $this,
                            $questionScore,
                            $ans,
                            $quesId,
                            $exeId,
                            $i,
                            $this->id,
                            false,
                            $questionDuration
                        );
                    }
                } else {
                    Event::saveQuestionAttempt(
                        $this,
                        $questionScore,
                        0,
                        $quesId,
                        $exeId,
                        0,
                        $this->id,
                        false,
                        $questionDuration
                    );
                }
            } elseif (MULTIPLE_ANSWER_COMBINATION == $answerType) {
                if (0 != $choice) {
                    $reply = array_keys($choice);
                    for ($i = 0; $i < count($reply); $i++) {
                        $ans = $reply[$i];
                        Event::saveQuestionAttempt(
                            $this,
                            $questionScore,
                            $ans,
                            $quesId,
                            $exeId,
                            $i,
                            $this->id,
                            false,
                            $questionDuration
                        );
                    }
                } else {
                    Event::saveQuestionAttempt(
                        $this,
                        $questionScore,
                        0,
                        $quesId,
                        $exeId,
                        0,
                        $this->id,
                        false,
                        $questionDuration
                    );
                }
            } elseif (in_array($answerType, [MATCHING, DRAGGABLE, MATCHING_DRAGGABLE])) {
                if (isset($matching)) {
                    foreach ($matching as $j => $val) {
                        Event::saveQuestionAttempt(
                            $this,
                            $questionScore,
                            $val,
                            $quesId,
                            $exeId,
                            $j,
                            $this->id,
                            false,
                            $questionDuration
                        );
                    }
                }
            } elseif (FREE_ANSWER == $answerType) {
                $answer = $choice;
                Event::saveQuestionAttempt(
                    $this,
                    $questionScore,
                    $answer,
                    $quesId,
                    $exeId,
                    0,
                    $this->id,
                    false,
                    $questionDuration
                );
            } elseif (ORAL_EXPRESSION == $answerType) {
                $answer = $choice;
                /** @var OralExpression $objQuestionTmp */
                $questionAttemptId = Event::saveQuestionAttempt(
                    $this,
                    $questionScore,
                    $answer,
                    $quesId,
                    $exeId,
                    0,
                    $this->id,
                    false,
                    $questionDuration
                );

                if (false !== $questionAttemptId) {
                    OralExpression::saveAssetInQuestionAttempt($questionAttemptId);
                }
            } elseif (
            in_array(
                $answerType,
                [UNIQUE_ANSWER, UNIQUE_ANSWER_IMAGE, UNIQUE_ANSWER_NO_OPTION, READING_COMPREHENSION]
            )
            ) {
                $answer = $choice;
                Event::saveQuestionAttempt(
                    $this,
                    $questionScore,
                    $answer,
                    $quesId,
                    $exeId,
                    0,
                    $this->id,
                    false,
                    $questionDuration
                );
            } elseif (HOT_SPOT == $answerType || ANNOTATION == $answerType) {
                $answer = [];
                if (isset($exerciseResultCoordinates[$questionId]) && !empty($exerciseResultCoordinates[$questionId])) {
                    if ($debug) {
                        error_log('Checking result coordinates');
                    }
                    Database::delete(
                        Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT),
                        [
                            'hotspot_exe_id = ? AND hotspot_question_id = ? AND c_id = ?' => [
                                $exeId,
                                $questionId,
                                api_get_course_int_id(),
                            ],
                        ]
                    );

                    foreach ($exerciseResultCoordinates[$questionId] as $idx => $val) {
                        $answer[] = $val;
                        $hotspotValue = 1 === (int) $choice[$idx] ? 1 : 0;
                        if ($debug) {
                            error_log('Hotspot value: '.$hotspotValue);
                        }
                        Event::saveExerciseAttemptHotspot(
                            $this,
                            $exeId,
                            $quesId,
                            $idx,
                            $hotspotValue,
                            $val,
                            false,
                            $this->id,
                            $learnpath_id,
                            $learnpath_item_id
                        );
                    }
                } else {
                    if ($debug) {
                        error_log('Empty: exerciseResultCoordinates');
                    }
                }
                Event::saveQuestionAttempt(
                    $this,
                    $questionScore,
                    implode('|', $answer),
                    $quesId,
                    $exeId,
                    0,
                    $this->id,
                    false,
                    $questionDuration
                );
            } else {
                Event::saveQuestionAttempt(
                    $this,
                    $questionScore,
                    $answer,
                    $quesId,
                    $exeId,
                    0,
                    $this->id,
                    false,
                    $questionDuration
                );
            }
        }

        if (0 == $propagate_neg && $questionScore < 0) {
            $questionScore = 0;
        }

        if ($save_results) {
            $statsTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
            $sql = "UPDATE $statsTable SET
                        score = score + ".(float) $questionScore."
                    WHERE exe_id = $exeId";
            Database::query($sql);
        }

        return [
            'score' => $questionScore,
            'weight' => $questionWeighting,
            'extra' => $extra_data,
            'open_question' => $arrques,
            'open_answer' => $arrans,
            'answer_type' => $answerType,
            'generated_oral_file' => '',
            'user_answered' => $userAnsweredQuestion,
            'correct_answer_id' => $correctAnswerId,
            'answer_destination' => $answerDestination,
        ];
    }

    /**
     * Sends a notification when a user ends an examn.
     *
     * @param string $type                  'start' or 'end' of an exercise
     * @param array  $question_list_answers
     * @param string $origin
     * @param int    $exe_id
     * @param float  $score
     * @param float  $weight
     *
     * @return bool
     */
    public function send_mail_notification_for_exam(
        $type,
        $question_list_answers,
        $origin,
        $exe_id,
        $score = null,
        $weight = null
    ) {
        $setting = api_get_course_setting('email_alert_manager_on_new_quiz');

        if (empty($setting) && empty($this->getNotifications())) {
            return false;
        }

        $settingFromExercise = $this->getNotifications();
        if (!empty($settingFromExercise)) {
            $setting = $settingFromExercise;
        }

        // Email configuration settings
        $courseCode = api_get_course_id();
        $courseInfo = api_get_course_info($courseCode);

        if (empty($courseInfo)) {
            return false;
        }

        $sessionId = api_get_session_id();

        $sessionData = '';
        if (!empty($sessionId)) {
            $sessionInfo = api_get_session_info($sessionId);
            if (!empty($sessionInfo)) {
                $sessionData = '<tr>'
                    .'<td>'.get_lang('Session name').'</td>'
                    .'<td>'.$sessionInfo['name'].'</td>'
                    .'</tr>';
            }
        }

        $sendStart = false;
        $sendEnd = false;
        $sendEndOpenQuestion = false;
        $sendEndOralQuestion = false;

        foreach ($setting as $option) {
            switch ($option) {
                case 0:
                    return false;

                    break;
                case 1: // End
                    if ('end' == $type) {
                        $sendEnd = true;
                    }

                    break;
                case 2: // start
                    if ('start' == $type) {
                        $sendStart = true;
                    }

                    break;
                case 3: // end + open
                    if ('end' == $type) {
                        $sendEndOpenQuestion = true;
                    }

                    break;
                case 4: // end + oral
                    if ('end' == $type) {
                        $sendEndOralQuestion = true;
                    }

                    break;
            }
        }

        $user_info = api_get_user_info(api_get_user_id());
        $url = api_get_path(WEB_CODE_PATH).'exercise/exercise_show.php?'.
            api_get_cidreq(true, true, 'qualify').'&id='.$exe_id.'&action=qualify';

        if (!empty($sessionId)) {
            $addGeneralCoach = true;
            $setting = api_get_configuration_value('block_quiz_mail_notification_general_coach');
            if (true === $setting) {
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

        if ($sendEndOpenQuestion) {
            $this->sendNotificationForOpenQuestions(
                $question_list_answers,
                $origin,
                $user_info,
                $url,
                $teachers
            );
        }

        if ($sendEndOralQuestion) {
            $this->sendNotificationForOralQuestions(
                $question_list_answers,
                $origin,
                $exe_id,
                $user_info,
                $url,
                $teachers
            );
        }

        if (!$sendEnd && !$sendStart) {
            return false;
        }

        $scoreLabel = '';
        if ($sendEnd &&
            true == api_get_configuration_value('send_score_in_exam_notification_mail_to_manager')
        ) {
            $notificationPercentage = api_get_configuration_value('send_notification_score_in_percentage');
            $scoreLabel = ExerciseLib::show_score($score, $weight, $notificationPercentage, true);
            $scoreLabel = '<tr>
                            <td>'.get_lang('Score')."</td>
                            <td>&nbsp;$scoreLabel</td>
                        </tr>";
        }

        if ($sendEnd) {
            $msg = get_lang('A learner attempted an exercise').'<br /><br />';
        } else {
            $msg = get_lang('Student just started an exercise').'<br /><br />';
        }

        $msg .= get_lang('Attempt details').' : <br /><br />
                    <table>
                        <tr>
                            <td>'.get_lang('Course name').'</td>
                            <td>#course#</td>
                        </tr>
                        '.$sessionData.'
                        <tr>
                            <td>'.get_lang('Test').'</td>
                            <td>&nbsp;#exercise#</td>
                        </tr>
                        <tr>
                            <td>'.get_lang('Learner name').'</td>
                            <td>&nbsp;#student_complete_name#</td>
                        </tr>
                        <tr>
                            <td>'.get_lang('Learner e-mail').'</td>
                            <td>&nbsp;#email#</td>
                        </tr>
                        '.$scoreLabel.'
                    </table>';

        $variables = [
            '#email#' => $user_info['email'],
            '#exercise#' => $this->exercise,
            '#student_complete_name#' => $user_info['complete_name'],
            '#course#' => Display::url(
                $courseInfo['title'],
                $courseInfo['course_public_url'].'?sid='.$sessionId
            ),
        ];

        if ($sendEnd) {
            $msg .= '<br /><a href="#url#">'.get_lang(
                    'Click this link to check the answer and/or give feedback'
                ).'</a>';
            $variables['#url#'] = $url;
        }

        $content = str_replace(array_keys($variables), array_values($variables), $msg);

        if ($sendEnd) {
            $subject = get_lang('A learner attempted an exercise');
        } else {
            $subject = get_lang('Student just started an exercise');
        }

        if (!empty($teachers)) {
            foreach ($teachers as $user_id => $teacher_data) {
                MessageManager::send_message_simple(
                    $user_id,
                    $subject,
                    $content
                );
            }
        }
    }

    /**
     * @param array $user_data         result of api_get_user_info()
     * @param array $trackExerciseInfo result of get_stat_track_exercise_info
     * @param bool  $saveUserResult
     * @param bool  $allowSignature
     * @param bool  $allowExportPdf
     *
     * @return string
     */
    public function showExerciseResultHeader(
        $user_data,
        $trackExerciseInfo,
        $saveUserResult,
        $allowSignature = false,
        $allowExportPdf = false
    ) {
        if (api_get_configuration_value('hide_user_info_in_quiz_result')) {
            return '';
        }

        $start_date = null;
        if (isset($trackExerciseInfo['start_date'])) {
            $start_date = api_convert_and_format_date($trackExerciseInfo['start_date']);
        }
        $duration = isset($trackExerciseInfo['duration_formatted']) ? $trackExerciseInfo['duration_formatted'] : null;
        $ip = isset($trackExerciseInfo['user_ip']) ? $trackExerciseInfo['user_ip'] : null;

        if (!empty($user_data)) {
            $userFullName = $user_data['complete_name'];
            if (api_is_teacher() || api_is_platform_admin(true, true)) {
                $userFullName = '<a href="'.$user_data['profile_url'].'" title="'.get_lang('GoToStudentDetails').'">'.
                    $user_data['complete_name'].'</a>';
            }

            $data = [
                'name_url' => $userFullName,
                'complete_name' => $user_data['complete_name'],
                'username' => $user_data['username'],
                'avatar' => $user_data['avatar_medium'],
                'url' => $user_data['profile_url'],
            ];

            if (!empty($user_data['official_code'])) {
                $data['code'] = $user_data['official_code'];
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

        $data['start_date'] = $start_date;
        $data['duration'] = $duration;
        $data['ip'] = $ip;

        if (api_get_configuration_value('save_titles_as_html')) {
            $data['title'] = $this->get_formated_title().get_lang('Result');
        } else {
            $data['title'] = PHP_EOL.$this->exercise.' : '.get_lang('Result');
        }

        $questionsCount = count(explode(',', $trackExerciseInfo['data_tracking']));
        $savedAnswersCount = $this->countUserAnswersSavedInExercise($trackExerciseInfo['exe_id']);

        $data['number_of_answers'] = $questionsCount;
        $data['number_of_answers_saved'] = $savedAnswersCount;
        $exeId = $trackExerciseInfo['exe_id'];

        if (false !== api_get_configuration_value('quiz_confirm_saved_answers')) {
            $em = Database::getManager();

            if ($saveUserResult) {
                $trackConfirmation = new TrackEExerciseConfirmation();
                $trackConfirmation
                    ->setUser(api_get_user_entity($trackExerciseInfo['exe_user_id']))
                    ->setQuizId($trackExerciseInfo['exe_exo_id'])
                    ->setAttemptId($trackExerciseInfo['exe_id'])
                    ->setQuestionsCount($questionsCount)
                    ->setSavedAnswersCount($savedAnswersCount)
                    ->setCourseId($trackExerciseInfo['c_id'])
                    ->setSessionId($trackExerciseInfo['session_id'])
                    ->setCreatedAt(api_get_utc_datetime(null, false, true));

                $em->persist($trackConfirmation);
                $em->flush();
            } else {
                $trackConfirmation = $em
                    ->getRepository(TrackEExerciseConfirmation::class)
                    ->findOneBy(
                        [
                            'attemptId' => $trackExerciseInfo['exe_id'],
                            'quizId' => $trackExerciseInfo['exe_exo_id'],
                            'courseId' => $trackExerciseInfo['c_id'],
                            'sessionId' => $trackExerciseInfo['session_id'],
                        ]
                    );
            }

            $data['track_confirmation'] = $trackConfirmation;
        }

        $signature = '';
        if (ExerciseSignaturePlugin::exerciseHasSignatureActivated($this)) {
            $signature = ExerciseSignaturePlugin::getSignature($trackExerciseInfo['exe_user_id'], $trackExerciseInfo);
        }
        $tpl = new Template(null, false, false, false, false, false, false);
        $tpl->assign('data', $data);
        $tpl->assign('allow_signature', $allowSignature);
        $tpl->assign('signature', $signature);
        $tpl->assign('allow_export_pdf', $allowExportPdf);
        $tpl->assign(
            'export_url',
            api_get_path(WEB_CODE_PATH).'exercise/result.php?action=export&id='.$exeId.'&'.api_get_cidreq()
        );
        $layoutTemplate = $tpl->get_template('exercise/partials/result_exercise.tpl');

        return $tpl->fetch($layoutTemplate);
    }

    /**
     * Returns the exercise result.
     *
     * @param int        attempt id
     *
     * @return array
     */
    public function get_exercise_result($exe_id)
    {
        $result = [];
        $track_exercise_info = ExerciseLib::get_exercise_track_exercise_info($exe_id);

        if (!empty($track_exercise_info)) {
            $totalScore = 0;
            $objExercise = new self();
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
                    [],
                    false,
                    true,
                    false,
                    $objExercise->selectPropagateNeg()
                );
                $totalScore += $question_result['score'];
            }

            if (0 == $objExercise->selectPropagateNeg() && $totalScore < 0) {
                $totalScore = 0;
            }
            $result = [
                'score' => $totalScore,
                'weight' => $track_exercise_info['max_score'],
            ];
        }

        return $result;
    }

    /**
     * Checks if the exercise is visible due a lot of conditions
     * visibility, time limits, student attempts
     * Return associative array
     * value : true if exercise visible
     * message : HTML formatted message
     * rawMessage : text message.
     *
     * @param int  $lpId
     * @param int  $lpItemId
     * @param int  $lpItemViewId
     * @param bool $filterByAdmin
     *
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
            if (api_is_platform_admin() || api_is_course_admin() || api_is_course_tutor()) {
                return ['value' => true, 'message' => ''];
            }
        }

        // Deleted exercise.
        if (-1 == $this->active) {
            return [
                'value' => false,
                'message' => Display::return_message(
                    get_lang('TestNotFound'),
                    'warning',
                    false
                ),
                'rawMessage' => get_lang('TestNotFound'),
            ];
        }

        $repo = Container::getQuizRepository();
        $exercise = $repo->find($this->iId);

        if (null === $exercise) {
            return [];
        }

        $course = api_get_course_entity($this->course_id);
        $link = $exercise->getFirstResourceLinkFromCourseSession($course);

        if ($link->isDraft()) {
            $this->active = 0;
        }

        // 2. If the exercise is not active.
        if (empty($lpId)) {
            // 2.1 LP is OFF
            if (0 == $this->active) {
                return [
                    'value' => false,
                    'message' => Display::return_message(
                        get_lang('TestNotFound'),
                        'warning',
                        false
                    ),
                    'rawMessage' => get_lang('TestNotFound'),
                ];
            }
        } else {
            $lp = Container::getLpRepository()->find($lpId);
            // 2.1 LP is loaded
            if ($lp && 0 == $this->active &&
                !learnpath::is_lp_visible_for_student($lp, api_get_user_id(), $course)
            ) {
                return [
                    'value' => false,
                    'message' => Display::return_message(
                        get_lang('TestNotFound'),
                        'warning',
                        false
                    ),
                    'rawMessage' => get_lang('TestNotFound'),
                ];
            }
        }

        // 3. We check if the time limits are on
        $limitTimeExists = false;
        if (!empty($this->start_time) || !empty($this->end_time)) {
            $limitTimeExists = true;
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
                    $message = sprintf(
                        get_lang('TestAvailableSinceX'),
                        api_convert_and_format_date($this->start_time)
                    );
                } else {
                    // before start date, no end date
                    $isVisible = false;
                    $message = sprintf(
                        get_lang('TestAvailableFromX'),
                        api_convert_and_format_date($this->start_time)
                    );
                }
            } elseif (!$existsStartDate && $existsEndDate) {
                // doesnt exist start date, exists end date
                if ($nowIsBeforeEndDate) {
                    // before end date, no start date
                    $isVisible = true;
                    $message = sprintf(
                        get_lang('TestAvailableUntilX'),
                        api_convert_and_format_date($this->end_time)
                    );
                } else {
                    // after end date, no start date
                    $isVisible = false;
                    $message = sprintf(
                        get_lang('TestAvailableUntilX'),
                        api_convert_and_format_date($this->end_time)
                    );
                }
            } elseif ($existsStartDate && $existsEndDate) {
                // exists start date and end date
                if ($nowIsAfterStartDate) {
                    if ($nowIsBeforeEndDate) {
                        // after start date and before end date
                        $isVisible = true;
                        $message = sprintf(
                            get_lang('TestIsActivatedFromXToY'),
                            api_convert_and_format_date($this->start_time),
                            api_convert_and_format_date($this->end_time)
                        );
                    } else {
                        // after start date and after end date
                        $isVisible = false;
                        $message = sprintf(
                            get_lang('TestWasActivatedFromXToY'),
                            api_convert_and_format_date($this->start_time),
                            api_convert_and_format_date($this->end_time)
                        );
                    }
                } else {
                    if ($nowIsBeforeEndDate) {
                        // before start date and before end date
                        $isVisible = false;
                        $message = sprintf(
                            get_lang('TestWillBeActivatedFromXToY'),
                            api_convert_and_format_date($this->start_time),
                            api_convert_and_format_date($this->end_time)
                        );
                    }
                    // case before start date and after end date is impossible
                }
            } elseif (!$existsStartDate && !$existsEndDate) {
                // doesnt exist start date nor end date
                $isVisible = true;
                $message = '';
            }
        }

        // 4. We check if the student have attempts
        if ($isVisible) {
            $exerciseAttempts = $this->selectAttempts();

            if ($exerciseAttempts > 0) {
                $attemptCount = Event::get_attempt_count_not_finished(
                    api_get_user_id(),
                    $this->getId(),
                    $lpId,
                    $lpItemId,
                    $lpItemViewId
                );

                if ($attemptCount >= $exerciseAttempts) {
                    $message = sprintf(
                        get_lang('Reachedmax. 20 characters, e.g. <i>INNOV21</i>Attempts'),
                        $this->name,
                        $exerciseAttempts
                    );
                    $isVisible = false;
                } else {
                    // Check blocking exercise.
                    $extraFieldValue = new ExtraFieldValue('exercise');
                    $blockExercise = $extraFieldValue->get_values_by_handler_and_field_variable(
                        $this->iId,
                        'blocking_percentage'
                    );
                    if ($blockExercise && isset($blockExercise['value']) && !empty($blockExercise['value'])) {
                        $blockPercentage = (int) $blockExercise['value'];
                        $userAttempts = Event::getExerciseResultsByUser(
                            api_get_user_id(),
                            $this->iId,
                            $this->course_id,
                            $this->sessionId,
                            $lpId,
                            $lpItemId
                        );

                        if (!empty($userAttempts)) {
                            $currentAttempt = current($userAttempts);
                            if ($currentAttempt['total_percentage'] <= $blockPercentage) {
                                $message = sprintf(
                                    get_lang('ExerciseBlockBecausePercentageX'),
                                    $blockPercentage
                                );
                                $isVisible = false;
                            }
                        }
                    }
                }
            }
        }

        $rawMessage = '';
        if (!empty($message)) {
            $rawMessage = $message;
            $message = Display::return_message($message, 'warning', false);
        }

        return [
            'value' => $isVisible,
            'message' => $message,
            'rawMessage' => $rawMessage,
        ];
    }

    /**
     * @return bool
     */
    public function added_in_lp()
    {
        $TBL_LP_ITEM = Database::get_course_table(TABLE_LP_ITEM);
        $sql = "SELECT max_score FROM $TBL_LP_ITEM
                WHERE
                    item_type = '".TOOL_QUIZ."' AND
                    path = '{$this->getId()}'";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Returns an array with this form.
     *
     * @return array
     *
     * @example
     * <code>
     * array (size=3)
     * 999 =>
     * array (size=3)
     * 0 => int 3422
     * 1 => int 3423
     * 2 => int 3424
     * 100 =>
     * array (size=2)
     * 0 => int 3469
     * 1 => int 3470
     * 101 =>
     * array (size=1)
     * 0 => int 3482
     * </code>
     * The array inside the key 999 means the question list that belongs to the media id = 999,
     * this case is special because 999 means "no media".
     */
    public function getMediaList()
    {
        return $this->mediaList;
    }

    /**
     * Is media question activated?
     *
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
            } elseif (1 == $media_count) {
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
     * Gets question list from the exercise.
     *
     * @return array
     */
    public function getQuestionList()
    {
        return $this->questionList;
    }

    /**
     * Question list with medias compressed like this.
     *
     * @return array
     *
     * @example
     *      <code>
     *      array(
     *      question_id_1,
     *      question_id_2,
     *      media_id, <- this media id contains question ids
     *      question_id_3,
     *      )
     *      </code>
     */
    public function getQuestionListWithMediasCompressed()
    {
        return $this->questionList;
    }

    /**
     * Question list with medias uncompressed like this.
     *
     * @return array
     *
     * @example
     *      <code>
     *      array(
     *      question_id,
     *      question_id,
     *      question_id, <- belongs to a media id
     *      question_id, <- belongs to a media id
     *      question_id,
     *      )
     *      </code>
     */
    public function getQuestionListWithMediasUncompressed()
    {
        return $this->questionListUncompressed;
    }

    /**
     * Sets the question list when the exercise->read() is executed.
     *
     * @param bool $adminView Whether to view the set the list of *all* questions or just the normal student view
     */
    public function setQuestionList($adminView = false)
    {
        // Getting question list.
        $questionList = $this->selectQuestionList(true, $adminView);
        $this->setMediaList($questionList);
        $this->questionList = $this->transformQuestionListWithMedias($questionList, false);
        $this->questionListUncompressed = $this->transformQuestionListWithMedias(
            $questionList,
            true
        );
    }

    /**
     * @params array question list
     * @params bool expand or not question list (true show all questions,
     * false show media question id instead of the question ids)
     */
    public function transformQuestionListWithMedias(
        $question_list,
        $expand_media_questions = false
    ) {
        $new_question_list = [];
        if (!empty($question_list)) {
            $media_questions = $this->getMediaList();
            $media_active = $this->mediaIsActivated($media_questions);

            if ($media_active) {
                $counter = 1;
                foreach ($question_list as $question_id) {
                    $add_question = true;
                    foreach ($media_questions as $media_id => $question_list_in_media) {
                        if (999 != $media_id && in_array($question_id, $question_list_in_media)) {
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
     * Get question list depend on the random settings.
     *
     * @return array
     */
    public function get_validated_question_list()
    {
        $isRandomByCategory = $this->isRandomByCat();
        if (0 == $isRandomByCategory) {
            if ($this->isRandom()) {
                return $this->getRandomList();
            }

            return $this->selectQuestionList();
        }

        if ($this->isRandom()) {
            // USE question categories
            // get questions by category for this exercise
            // we have to choice $objExercise->random question in each array values of $tabCategoryQuestions
            // key of $tabCategoryQuestions are the categopy id (0 for not in a category)
            // value is the array of question id of this category
            $questionList = [];
            $categoryQuestions = TestCategory::getQuestionsByCat($this->id);
            $isRandomByCategory = $this->getRandomByCategory();
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
            if (2 == $isRandomByCategory) {
                $categoryQuestions = TestCategory::sortTabByBracketLabel($categoryQuestions);
            }
            foreach ($categoryQuestions as $question) {
                $number_of_random_question = $this->random;
                if (-1 == $this->random) {
                    $number_of_random_question = count($this->questionList);
                }
                $questionList = array_merge(
                    $questionList,
                    TestCategory::getNElementsFromArray(
                        $question,
                        $number_of_random_question
                    )
                );
            }
            // shuffle the question list if test is not grouped by categories
            if (1 == $isRandomByCategory) {
                shuffle($questionList); // or not
            }

            return $questionList;
        }

        // Problem, random by category has been selected and
        // we have no $this->isRandom number of question selected
        // Should not happened

        return [];
    }

    public function get_question_list($expand_media_questions = false)
    {
        $question_list = $this->get_validated_question_list();
        $question_list = $this->transform_question_list_with_medias($question_list, $expand_media_questions);

        return $question_list;
    }

    public function transform_question_list_with_medias($question_list, $expand_media_questions = false)
    {
        $new_question_list = [];
        if (!empty($question_list)) {
            $media_questions = $this->getMediaList();
            $media_active = $this->mediaIsActivated($media_questions);

            if ($media_active) {
                $counter = 1;
                foreach ($question_list as $question_id) {
                    $add_question = true;
                    foreach ($media_questions as $media_id => $question_list_in_media) {
                        if (999 != $media_id && in_array($question_id, $question_list_in_media)) {
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
     *
     * @return array
     */
    public function get_stat_track_exercise_info_by_exe_id($exe_id)
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $exe_id = (int) $exe_id;
        $sql_track = "SELECT * FROM $table WHERE exe_id = $exe_id ";
        $result = Database::query($sql_track);
        $new_array = [];
        if (Database::num_rows($result) > 0) {
            $new_array = Database::fetch_array($result, 'ASSOC');
            $start_date = api_get_utc_datetime($new_array['start_date'], true);
            $end_date = api_get_utc_datetime($new_array['exe_date'], true);
            $new_array['duration_formatted'] = '';
            if (!empty($new_array['exe_duration']) && !empty($start_date) && !empty($end_date)) {
                $time = api_format_time($new_array['exe_duration'], 'js');
                $new_array['duration_formatted'] = $time;
            }
        }

        return $new_array;
    }

    /**
     * @param int $exeId
     *
     * @return bool
     */
    public function removeAllQuestionToRemind($exeId)
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $exeId = (int) $exeId;
        if (empty($exeId)) {
            return false;
        }
        $sql = "UPDATE $table
                SET questions_to_check = ''
                WHERE exe_id = $exeId ";
        Database::query($sql);

        return true;
    }

    /**
     * @param int   $exeId
     * @param array $questionList
     *
     * @return bool
     */
    public function addAllQuestionToRemind($exeId, $questionList = [])
    {
        $exeId = (int) $exeId;
        if (empty($questionList)) {
            return false;
        }

        $questionListToString = implode(',', $questionList);
        $questionListToString = Database::escape_string($questionListToString);

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $sql = "UPDATE $table
                SET questions_to_check = '$questionListToString'
                WHERE exe_id = $exeId";
        Database::query($sql);

        return true;
    }

    /**
     * @param int    $exeId
     * @param int    $questionId
     * @param string $action
     */
    public function editQuestionToRemind($exeId, $questionId, $action = 'add')
    {
        $exercise_info = self::get_stat_track_exercise_info_by_exe_id($exeId);
        $questionId = (int) $questionId;
        $exeId = (int) $exeId;

        if ($exercise_info) {
            $track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
            if (empty($exercise_info['questions_to_check'])) {
                if ('add' == $action) {
                    $sql = "UPDATE $track_exercises
                            SET questions_to_check = '$questionId'
                            WHERE exe_id = $exeId ";
                    Database::query($sql);
                }
            } else {
                $remind_list = explode(',', $exercise_info['questions_to_check']);
                $remind_list_string = '';
                if ('add' === $action) {
                    if (!in_array($questionId, $remind_list)) {
                        $newRemindList = [];
                        $remind_list[] = $questionId;
                        $questionListInSession = Session::read('questionList');
                        if (!empty($questionListInSession)) {
                            foreach ($questionListInSession as $originalQuestionId) {
                                if (in_array($originalQuestionId, $remind_list)) {
                                    $newRemindList[] = $originalQuestionId;
                                }
                            }
                        }
                        $remind_list_string = implode(',', $newRemindList);
                    }
                } elseif ('delete' == $action) {
                    if (!empty($remind_list)) {
                        if (in_array($questionId, $remind_list)) {
                            $remind_list = array_flip($remind_list);
                            unset($remind_list[$questionId]);
                            $remind_list = array_flip($remind_list);

                            if (!empty($remind_list)) {
                                sort($remind_list);
                                array_filter($remind_list);
                                $remind_list_string = implode(',', $remind_list);
                            }
                        }
                    }
                }
                $value = Database::escape_string($remind_list_string);
                $sql = "UPDATE $track_exercises
                        SET questions_to_check = '$value'
                        WHERE exe_id = $exeId ";
                Database::query($sql);
            }
        }
    }

    /**
     * @param string $answer
     */
    public function fill_in_blank_answer_to_array($answer)
    {
        $list = null;
        api_preg_match_all('/\[[^]]+\]/', $answer, $list);

        if (empty($list)) {
            return '';
        }

        return $list[0];
    }

    /**
     * @param string $answer
     *
     * @return string
     */
    public function fill_in_blank_answer_to_string($answer)
    {
        $teacher_answer_list = $this->fill_in_blank_answer_to_array($answer);
        $result = '';
        if (!empty($teacher_answer_list)) {
            foreach ($teacher_answer_list as $teacher_item) {
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
     * @return string
     */
    public function returnTimeLeftDiv()
    {
        $html = '<div id="clock_warning" style="display:none">';
        $html .= Display::return_message(
            get_lang('Time limit reached'),
            'warning'
        );
        $html .= ' ';
        $html .= sprintf(
            get_lang('Just a moment, please. You will be redirected in %s seconds...'),
            '<span id="counter_to_redirect" class="red_alert"></span>'
        );
        $html .= '</div>';
        $icon = Display::getMdiIcon('clock-outline');
        $html .= '<div class="count_down">
                    '.get_lang('RemainingTimeToFinishExercise').'
                    '.$icon.'<span id="exercise_clock_warning"></span>
                </div>';

        return $html;
    }

    /**
     * Get categories added in the exercise--category matrix.
     *
     * @return array
     */
    public function getCategoriesInExercise()
    {
        $table = Database::get_course_table(TABLE_QUIZ_REL_CATEGORY);
        if (!empty($this->getId())) {
            $sql = "SELECT * FROM $table
                    WHERE exercise_id = {$this->getId()} ";
            $result = Database::query($sql);
            $list = [];
            if (Database::num_rows($result)) {
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    $list[$row['category_id']] = $row;
                }

                return $list;
            }
        }

        return [];
    }

    /**
     * Get total number of question that will be parsed when using the category/exercise.
     *
     * @return int
     */
    public function getNumberQuestionExerciseCategory()
    {
        $table = Database::get_course_table(TABLE_QUIZ_REL_CATEGORY);
        if (!empty($this->getId())) {
            $sql = "SELECT SUM(count_questions) count_questions
                    FROM $table
                    WHERE exercise_id = {$this->getId()} AND c_id = {$this->course_id}";
            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $row = Database::fetch_array($result);

                return (int) $row['count_questions'];
            }
        }

        return 0;
    }

    /**
     * Save categories in the TABLE_QUIZ_REL_CATEGORY table.
     *
     * @param array $categories
     */
    public function save_categories_in_exercise($categories)
    {
        if (!empty($categories) && !empty($this->getId())) {
            $table = Database::get_course_table(TABLE_QUIZ_REL_CATEGORY);
            $sql = "DELETE FROM $table
                    WHERE exercise_id = {$this->getId()} AND c_id = {$this->course_id}";
            Database::query($sql);
            if (!empty($categories)) {
                foreach ($categories as $categoryId => $countQuestions) {
                    $params = [
                        'c_id' => $this->course_id,
                        'exercise_id' => $this->getId(),
                        'category_id' => $categoryId,
                        'count_questions' => $countQuestions,
                    ];
                    Database::insert($table, $params);
                }
            }
        }
    }

    /**
     * @param array  $questionList
     * @param int    $currentQuestion
     * @param array  $conditions
     * @param string $link
     *
     * @return string
     */
    public function progressExercisePaginationBar(
        $questionList,
        $currentQuestion,
        $conditions,
        $link
    ) {
        $mediaQuestions = $this->getMediaList();

        $html = '<div class="exercise_pagination pagination pagination-mini"><ul>';
        $counter = 0;
        $nextValue = 0;
        $wasMedia = false;
        $before = 0;
        $counterNoMedias = 0;
        foreach ($questionList as $questionId) {
            $isCurrent = $currentQuestion == $counterNoMedias + 1 ? true : false;

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

                $counter += count($mediaQuestions[$questionId]) - 1;
                $before = count($questionList);
                $wasMedia = true;
                $nextValue += count($questionList);
            } else {
                $html .= Display::parsePaginationItem(
                    $questionId,
                    $isCurrent,
                    $conditions,
                    $link,
                    $counter
                );
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
     *  Shows a list of numbers that represents the question to answer in a exercise.
     *
     * @param array  $categories
     * @param int    $current
     * @param array  $conditions
     * @param string $link
     *
     * @return string
     */
    public function progressExercisePaginationBarWithCategories(
        $categories,
        $current,
        $conditions = [],
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
                [
                    EX_Q_SELECTION_CATEGORIES_ORDERED_BY_PARENT_QUESTIONS_ORDERED,
                    EX_Q_SELECTION_CATEGORIES_ORDERED_BY_PARENT_QUESTIONS_RANDOM,
                ]
            )) {
                $useRootAsCategoryTitle = true;
            }

            // If the exercise is set to only show the titles of the categories
            // at the root of the tree, then pre-order the categories tree by
            // removing children and summing their questions into the parent
            // categories
            if ($useRootAsCategoryTitle) {
                // The new categories list starts empty
                $newCategoryList = [];
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
                        $category['question_list'] = array_merge($oldQuestionList, $category['question_list']);
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
                if (999 != $mediaQuestionId) {
                    $isMedia = true;
                    $fixedValue = $counterNoMedias;
                }

                //$categoryName = $category['path']; << show the path
                $categoryName = $category['name'];

                if ($useRootAsCategoryTitle) {
                    if (isset($category['parent_info'])) {
                        $categoryName = $category['parent_info']['title'];
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

                if (999 == $mediaQuestionId) {
                    $counterNoMedias += count($questionList);
                } else {
                    $counterNoMedias++;
                }

                $nextValue += count($questionList);
                $before = count($questionList);

                if (999 != $mediaQuestionId) {
                    $wasMedia = true;
                } else {
                    $wasMedia = false;
                }
            }
        }

        return $html;
    }

    /**
     * Renders a question list.
     *
     * @param array $questionList    (with media questions compressed)
     * @param int   $currentQuestion
     * @param array $exerciseResult
     * @param array $attemptList
     * @param array $remindList
     */
    public function renderQuestionList(
        $questionList,
        $currentQuestion,
        $exerciseResult,
        $attemptList,
        $remindList
    ) {
        $mediaQuestions = $this->getMediaList();
        $i = 0;

        // Normal question list render (medias compressed)
        foreach ($questionList as $questionId) {
            $i++;
            // For sequential exercises

            if (ONE_PER_PAGE == $this->type) {
                // If it is not the right question, goes to the next loop iteration
                if ($currentQuestion != $i) {
                    continue;
                } else {
                    if (!in_array(
                        $this->getFeedbackType(),
                        [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP]
                    )) {
                        // if the user has already answered this question
                        if (isset($exerciseResult[$questionId])) {
                            echo Display::return_message(
                                get_lang('You already answered the question'),
                                'normal'
                            );

                            break;
                        }
                    }
                }
            }

            // The $questionList contains the media id we check
            // if this questionId is a media question type
            if (isset($mediaQuestions[$questionId]) &&
                999 != $mediaQuestions[$questionId]
            ) {
                // The question belongs to a media
                $mediaQuestionList = $mediaQuestions[$questionId];
                $objQuestionTmp = Question::read($questionId);

                $counter = 1;
                if (MEDIA_QUESTION == $objQuestionTmp->type) {
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
            }

            // For sequential exercises.
            if (ONE_PER_PAGE == $this->type) {
                // quits the loop
                break;
            }
        }
        // end foreach()

        if (ALL_ON_ONE_PAGE == $this->type) {
            $exercise_actions = $this->show_button($questionId, $currentQuestion);
            echo Display::div($exercise_actions, ['class' => 'exercise_actions']);
        }
    }

    /**
     * Not implemented in 1.11.x.
     *
     * @param int   $questionId
     * @param array $attemptList
     * @param array $remindList
     * @param int   $i
     * @param int   $current_question
     * @param array $questions_in_media
     * @param bool  $last_question_in_media
     * @param array $realQuestionList
     * @param bool  $generateJS
     */
    public function renderQuestion(
        $questionId,
        $attemptList,
        $remindList,
        $i,
        $current_question,
        $questions_in_media = [],
        $last_question_in_media = false,
        $realQuestionList = [],
        $generateJS = true
    ) {
        // With this option on the question is loaded via AJAX
        //$generateJS = true;
        //$this->loadQuestionAJAX = true;

        if ($generateJS && $this->loadQuestionAJAX) {
            $url = api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?a=get_question&id='.$questionId.'&'.api_get_cidreq();
            $params = [
                'questionId' => $questionId,
                'attemptList' => $attemptList,
                'remindList' => $remindList,
                'i' => $i,
                'current_question' => $current_question,
                'questions_in_media' => $questions_in_media,
                'last_question_in_media' => $last_question_in_media,
            ];
            $params = json_encode($params);

            $script = '<script>
            $(function(){
                var params = '.$params.';
                $.ajax({
                    type: "GET",
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
            $origin = api_get_origin();
            $question_obj = Question::read($questionId);
            $user_choice = isset($attemptList[$questionId]) ? $attemptList[$questionId] : null;
            $remind_highlight = null;

            // Hides questions when reviewing a ALL_ON_ONE_PAGE exercise
            // see #4542 no_remind_highlight class hide with jquery
            if (ALL_ON_ONE_PAGE == $this->type && isset($_GET['reminder']) && 2 == $_GET['reminder']) {
                $remind_highlight = 'no_remind_highlight';
                // @todo not implemented in 1.11.x
                /*if (in_array($question_obj->type, Question::question_type_no_review())) {
                    return null;
                }*/
            }

            $attributes = ['id' => 'remind_list['.$questionId.']'];

            // Showing the question
            $exercise_actions = null;
            echo '<a id="questionanchor'.$questionId.'"></a><br />';
            echo '<div id="question_div_'.$questionId.'" class="main_question '.$remind_highlight.'" >';

            // Shows the question + possible answers
            $showTitle = 1 == $this->getHideQuestionTitle() ? false : true;
            // @todo not implemented in 1.11.x
            /*echo $this->showQuestion(
                $question_obj,
                false,
                $origin,
                $i,
                $showTitle,
                false,
                $user_choice,
                false,
                null,
                false,
                $this->getModelType(),
                $this->categoryMinusOne
            );*/

            // Button save and continue
            switch ($this->type) {
                case ONE_PER_PAGE:
                    $exercise_actions .= $this->show_button(
                        $questionId,
                        $current_question,
                        null,
                        $remindList
                    );

                    break;
                case ALL_ON_ONE_PAGE:
                    if (api_is_allowed_to_session_edit()) {
                        $button = [
                            Display::button(
                                'save_now',
                                get_lang('Save and continue'),
                                ['type' => 'button', 'class' => 'btn btn-primary', 'data-question' => $questionId]
                            ),
                            '<span id="save_for_now_'.$questionId.'" class="exercise_save_mini_message"></span>',
                        ];
                        $exercise_actions .= Display::div(
                            implode(PHP_EOL, $button),
                            ['class' => 'exercise_save_now_button']
                        );
                    }

                    break;
            }

            if (!empty($questions_in_media)) {
                $count_of_questions_inside_media = count($questions_in_media);
                if ($count_of_questions_inside_media > 1 && api_is_allowed_to_session_edit()) {
                    $button = [
                        Display::button(
                            'save_now',
                            get_lang('Save and continue'),
                            ['type' => 'button', 'class' => 'btn btn-primary', 'data-question' => $questionId]
                        ),
                        '<span id="save_for_now_'.$questionId.'" class="exercise_save_mini_message"></span>&nbsp;',
                    ];
                    $exercise_actions = Display::div(
                        implode(PHP_EOL, $button),
                        ['class' => 'exercise_save_now_button']
                    );
                }

                if ($last_question_in_media && ONE_PER_PAGE == $this->type) {
                    $exercise_actions = $this->show_button($questionId, $current_question, $questions_in_media);
                }
            }

            // Checkbox review answers. Not implemented.
            /*if ($this->review_answers &&
                !in_array($question_obj->type, Question::question_type_no_review())
            ) {
                $remind_question_div = Display::tag(
                    'label',
                    Display::input(
                        'checkbox',
                        'remind_list['.$questionId.']',
                        '',
                        $attributes
                    ).get_lang('Revise question later'),
                    [
                        'class' => 'checkbox',
                        'for' => 'remind_list['.$questionId.']',
                    ]
                );
                $exercise_actions .= Display::div(
                    $remind_question_div,
                    ['class' => 'exercise_save_now_button']
                );
            }*/

            echo Display::div(' ', ['class' => 'clear']);

            $paginationCounter = null;
            if (ONE_PER_PAGE == $this->type) {
                if (empty($questions_in_media)) {
                    $paginationCounter = Display::paginationIndicator(
                        $current_question,
                        count($realQuestionList)
                    );
                } else {
                    if ($last_question_in_media) {
                        $paginationCounter = Display::paginationIndicator(
                            $current_question,
                            count($realQuestionList)
                        );
                    }
                }
            }

            echo '<div class="row"><div class="pull-right">'.$paginationCounter.'</div></div>';
            echo Display::div($exercise_actions, ['class' => 'form-actions']);
            echo '</div>';
        }
    }

    /**
     * Returns an array of categories details for the questions of the current
     * exercise.
     *
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
                ON (e.question_id = q.iid)
                INNER JOIN $categoryRelTable catRel
                ON (catRel.question_id = e.question_id)
                INNER JOIN $categoryTable cat
                ON (cat.iid = catRel.category_id)
                WHERE
                  e.quiz_id	= ".(int) ($this->getId());

        $result = Database::query($sql);
        $categoriesInExercise = [];
        if (Database::num_rows($result)) {
            $categoriesInExercise = Database::store_result($result, 'ASSOC');
        }

        return $categoriesInExercise;
    }

    /**
     * Calculate the max_score of the quiz, depending of question inside, and quiz advanced option.
     */
    public function get_max_score()
    {
        $out_max_score = 0;
        // list of question's id !!! the array key start at 1 !!!
        $questionList = $this->selectQuestionList(true);

        // test is randomQuestions - see field random of test
        if ($this->random > 0 && 0 == $this->randomByCat) {
            $numberRandomQuestions = $this->random;
            $questionScoreList = [];
            foreach ($questionList as $questionId) {
                $tmpobj_question = Question::read($questionId);
                if (is_object($tmpobj_question)) {
                    $questionScoreList[] = $tmpobj_question->weighting;
                }
            }

            rsort($questionScoreList);
            // add the first $numberRandomQuestions value of score array to get max_score
            for ($i = 0; $i < min($numberRandomQuestions, count($questionScoreList)); $i++) {
                $out_max_score += $questionScoreList[$i];
            }
        } elseif ($this->random > 0 && $this->randomByCat > 0) {
            // test is random by category
            // get the $numberRandomQuestions best score question of each category
            $numberRandomQuestions = $this->random;
            $tab_categories_scores = [];
            foreach ($questionList as $questionId) {
                $question_category_id = TestCategory::getCategoryForQuestion($questionId);
                if (!is_array($tab_categories_scores[$question_category_id])) {
                    $tab_categories_scores[$question_category_id] = [];
                }
                $tmpobj_question = Question::read($questionId);
                if (is_object($tmpobj_question)) {
                    $tab_categories_scores[$question_category_id][] = $tmpobj_question->weighting;
                }
            }

            // here we've got an array with first key, the category_id, second key, score of question for this cat
            foreach ($tab_categories_scores as $tab_scores) {
                rsort($tab_scores);
                for ($i = 0; $i < min($numberRandomQuestions, count($tab_scores)); $i++) {
                    $out_max_score += $tab_scores[$i];
                }
            }
        } else {
            // standard test, just add each question score
            foreach ($questionList as $questionId) {
                $question = Question::read($questionId, $this->course);
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
        if (api_get_configuration_value('save_titles_as_html')) {
        }

        return api_html_entity_decode($this->selectTitle());
    }

    /**
     * @param string $title
     *
     * @return string
     */
    public static function get_formated_title_variable($title)
    {
        return api_html_entity_decode($title);
    }

    /**
     * @return string
     */
    public function format_title()
    {
        return api_htmlentities($this->title);
    }

    /**
     * @param string $title
     *
     * @return string
     */
    public static function format_title_variable($title)
    {
        return api_htmlentities($title);
    }

    /**
     * @param int $courseId
     * @param int $sessionId
     *
     * @return array exercises
     */
    public function getExercisesByCourseSession($courseId, $sessionId)
    {
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;

        $tbl_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
        $sql = "SELECT * FROM $tbl_quiz cq
                WHERE
                    cq.c_id = %s AND
                    (cq.session_id = %s OR cq.session_id = 0) AND
                    cq.active = 0
                ORDER BY cq.iid";
        $sql = sprintf($sql, $courseId, $sessionId);

        $result = Database::query($sql);

        $rows = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param int   $courseId
     * @param int   $sessionId
     * @param array $quizId
     *
     * @return array exercises
     */
    public function getExerciseAndResult($courseId, $sessionId, $quizId = [])
    {
        if (empty($quizId)) {
            return [];
        }

        $sessionId = (int) $sessionId;
        $courseId = (int) $courseId;

        $ids = is_array($quizId) ? $quizId : [$quizId];
        $ids = array_map('intval', $ids);
        $ids = implode(',', $ids);
        $track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        if (0 != $sessionId) {
            $sql = "SELECT * FROM $track_exercises te
              INNER JOIN c_quiz cq
              ON cq.id = te.exe_exo_id AND te.c_id = cq.c_id
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
        $rows = [];
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
     *
     * @return int|null
     */
    public static function getNextQuestionId(
        $exeId,
        $exercise_stat_info,
        $remindList,
        $currentQuestion
    ) {
        $result = Event::get_exercise_results_by_attempt($exeId, 'incomplete');

        if (isset($result[$exeId])) {
            $result = $result[$exeId];
        } else {
            return null;
        }

        $data_tracking = $exercise_stat_info['data_tracking'];
        $data_tracking = explode(',', $data_tracking);

        // if this is the final question do nothing.
        if ($currentQuestion == count($data_tracking)) {
            return null;
        }

        $currentQuestion--;

        if (!empty($result['question_list'])) {
            $answeredQuestions = [];
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
     * Gets the position of a questionId in the question list.
     *
     * @param $questionId
     *
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
     * Get the correct answers in all attempts.
     *
     * @param int  $learnPathId
     * @param int  $learnPathItemId
     * @param bool $onlyCorrect
     *
     * @return array
     */
    public function getAnswersInAllAttempts($learnPathId = 0, $learnPathItemId = 0, $onlyCorrect = true)
    {
        $attempts = Event::getExerciseResultsByUser(
            api_get_user_id(),
            $this->getId(),
            api_get_course_int_id(),
            api_get_session_id(),
            $learnPathId,
            $learnPathItemId,
            'DESC'
        );

        $list = [];
        foreach ($attempts as $attempt) {
            foreach ($attempt['question_list'] as $answers) {
                foreach ($answers as $answer) {
                    $objAnswer = new Answer($answer['question_id']);
                    if ($onlyCorrect) {
                        switch ($objAnswer->getQuestionType()) {
                            case FILL_IN_BLANKS:
                                $isCorrect = FillBlanks::isCorrect($answer['answer']);

                                break;
                            case MATCHING:
                            case DRAGGABLE:
                            case MATCHING_DRAGGABLE:
                                $isCorrect = Matching::isCorrect(
                                    $answer['position'],
                                    $answer['answer'],
                                    $answer['question_id']
                                );

                                break;
                            case ORAL_EXPRESSION:
                                $isCorrect = false;

                                break;
                            default:
                                $isCorrect = $objAnswer->isCorrectByAutoId($answer['answer']);
                        }
                        if ($isCorrect) {
                            $list[$answer['question_id']][] = $answer;
                        }
                    } else {
                        $list[$answer['question_id']][] = $answer;
                    }
                }
            }

            if (false === $onlyCorrect) {
                // Only take latest attempt
                break;
            }
        }

        return $list;
    }

    /**
     * Get the correct answers in all attempts.
     *
     * @param int $learnPathId
     * @param int $learnPathItemId
     *
     * @return array
     */
    public function getCorrectAnswersInAllAttempts($learnPathId = 0, $learnPathItemId = 0)
    {
        return $this->getAnswersInAllAttempts($learnPathId, $learnPathItemId);
    }

    /**
     * @return bool
     */
    public function showPreviousButton()
    {
        $allow = api_get_configuration_value('allow_quiz_show_previous_button_setting');
        if (false === $allow) {
            return true;
        }

        return $this->showPreviousButton;
    }

    public function getPreventBackwards()
    {
        return (int) $this->preventBackwards;
    }

    /**
     * @return int
     */
    public function getExerciseCategoryId()
    {
        if (empty($this->exerciseCategoryId)) {
            return null;
        }

        return (int) $this->exerciseCategoryId;
    }

    /**
     * @param int $value
     */
    public function setExerciseCategoryId($value)
    {
        if (!empty($value)) {
            $this->exerciseCategoryId = (int) $value;
        }
    }

    /**
     * Set the value to 1 to hide the question number.
     *
     * @param int $value
     */
    public function setHideQuestionNumber($value = 0)
    {
        $this->hideQuestionNumber = (int) $value;
    }

    /**
     * Gets the value to hide or show the question number. If it does not exist, it is set to 0.
     *
     * @return int 1 if the question number must be hidden
     */
    public function getHideQuestionNumber()
    {
        return (int) $this->hideQuestionNumber;
    }

    public function setPageResultConfiguration(array $values)
    {
        $pageConfig = api_get_configuration_value('allow_quiz_results_page_config');
        if ($pageConfig) {
            $params = [
                'hide_expected_answer' => $values['hide_expected_answer'] ?? '',
                'hide_question_score' => $values['hide_question_score'] ?? '',
                'hide_total_score' => $values['hide_total_score'] ?? '',
                'hide_category_table' => $values['hide_category_table'] ?? '',
                'hide_correct_answered_questions' => $values['hide_correct_answered_questions'] ?? '',
            ];
            $this->pageResultConfiguration = $params;
        }
    }

    /**
     * @param array $defaults
     */
    public function setPageResultConfigurationDefaults(&$defaults)
    {
        $configuration = $this->getPageResultConfiguration();
        if (!empty($configuration) && !empty($defaults)) {
            $defaults = array_merge($defaults, $configuration);
        }
    }

    /**
     * @return array
     */
    public function getPageResultConfiguration()
    {
        $pageConfig = api_get_configuration_value('allow_quiz_results_page_config');
        if ($pageConfig) {
            return $this->pageResultConfiguration;
        }

        return [];
    }

    /**
     * @param string $attribute
     *
     * @return mixed|null
     */
    public function getPageConfigurationAttribute($attribute)
    {
        $result = $this->getPageResultConfiguration();

        if (!empty($result)) {
            return $result[$attribute] ?? null;
        }

        return null;
    }

    /**
     * @param bool $showPreviousButton
     *
     * @return Exercise
     */
    public function setShowPreviousButton($showPreviousButton)
    {
        $this->showPreviousButton = $showPreviousButton;

        return $this;
    }

    /**
     * @param array $notifications
     */
    public function setNotifications($notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * @return array
     */
    public function getNotifications()
    {
        return $this->notifications;
    }

    /**
     * @return bool
     */
    public function showExpectedChoice()
    {
        return api_get_configuration_value('show_exercise_expected_choice');
    }

    /**
     * @return bool
     */
    public function showExpectedChoiceColumn()
    {
        if (true === $this->forceShowExpectedChoiceColumn) {
            return true;
        }
        if ($this->hideExpectedAnswer) {
            return false;
        }
        if (!in_array(
            $this->results_disabled,
            [
                RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
            ]
        )
        ) {
            $hide = (int) $this->getPageConfigurationAttribute('hide_expected_answer');
            if (1 === $hide) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function getQuestionRibbon(string $class, string $scoreLabel, ?string $result, array $array): string
    {
        $hide = (int) $this->getPageConfigurationAttribute('hide_question_score');
        if (1 === $hide) {
            return '';
        }

        $ribbon = '<div class="question-answer-result__header-ribbon-title question-answer-result__header-ribbon-title--'.$class.'">'.$scoreLabel.'</div>';
        if (!empty($result)) {
            $ribbon .= '<div class="question-answer-result__header-ribbon-detail">'
                .get_lang('Score').': '.$result
                .'</div>';
        }

        $ribbonClassModifier = '';

        if ($this->showExpectedChoice()) {
            $hideLabel = api_get_configuration_value('exercise_hide_label');
            if (true === $hideLabel) {
                $ribbonClassModifier = 'question-answer-result__header-ribbon--no-ribbon';
                $html = '';
                $answerUsed = (int) $array['used'];
                $answerMissing = (int) $array['missing'] - $answerUsed;
                for ($i = 1; $i <= $answerUsed; $i++) {
                    $html .= Display::return_icon('attempt-check.png');
                }
                for ($i = 1; $i <= $answerMissing; $i++) {
                    $html .= Display::return_icon('attempt-nocheck.png');
                }
                $ribbon = '<div class="question-answer-result__header-ribbon-title">'
                    .get_lang('Correct answers').': '.$result.'</div>'
                    .'<div class="question-answer-result__header-ribbon-detail">'.$html.'</div>';
            }
        }

        return Display::div(
            $ribbon,
            ['class' => "question-answer-result__header-ribbon $ribbonClassModifier"]
        );
    }

    /**
     * @return int
     */
    public function getAutoLaunch()
    {
        return $this->autolaunch;
    }

    /**
     * Clean auto launch settings for all exercise in course/course-session.
     */
    public function enableAutoLaunch()
    {
        $table = Database::get_course_table(TABLE_QUIZ_TEST);
        $sql = "UPDATE $table SET autolaunch = 1
                WHERE iid = ".$this->iId;
        Database::query($sql);
    }

    /**
     * Clean auto launch settings for all exercise in course/course-session.
     */
    public function cleanCourseLaunchSettings()
    {
        $table = Database::get_course_table(TABLE_QUIZ_TEST);
        $sql = "UPDATE $table SET autolaunch = 0
                WHERE c_id = ".$this->course_id.' AND session_id = '.$this->sessionId;
        Database::query($sql);
    }

    /**
     * Get the title without HTML tags.
     *
     * @return string
     */
    public function getUnformattedTitle()
    {
        return strip_tags(api_html_entity_decode($this->title));
    }

    /**
     * Get the question IDs from quiz_rel_question for the current quiz,
     * using the parameters as the arguments to the SQL's LIMIT clause.
     * Because the exercise_id is known, it also comes with a filter on
     * the session, so sessions are not specified here.
     *
     * @param int $start  At which question do we want to start the list
     * @param int $length Up to how many results we want
     *
     * @return array A list of question IDs
     */
    public function getQuestionForTeacher($start = 0, $length = 10)
    {
        $start = (int) $start;
        if ($start < 0) {
            $start = 0;
        }

        $length = (int) $length;

        $quizRelQuestion = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $question = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $sql = "SELECT DISTINCT e.question_id
                FROM $quizRelQuestion e
                INNER JOIN $question q
                ON (e.question_id = q.iid)
                WHERE

                    e.quiz_id = '".$this->getId()."'
                ORDER BY question_order
                LIMIT $start, $length
            ";
        $result = Database::query($sql);
        $questionList = [];
        while ($object = Database::fetch_object($result)) {
            $questionList[] = $object->question_id;
        }

        return $questionList;
    }

    /**
     * @param int   $exerciseId
     * @param array $courseInfo
     * @param int   $sessionId
     *
     * @return bool
     */
    public function generateStats($exerciseId, $courseInfo, $sessionId)
    {
        $allowStats = api_get_configuration_value('allow_gradebook_stats');
        if (!$allowStats) {
            return false;
        }

        if (empty($courseInfo)) {
            return false;
        }

        $courseId = $courseInfo['real_id'];

        $sessionId = (int) $sessionId;
        $exerciseId = (int) $exerciseId;

        $result = $this->read($exerciseId);

        if (empty($result)) {
            api_not_allowed(true);
        }

        $statusToFilter = empty($sessionId) ? STUDENT : 0;

        $studentList = CourseManager::get_user_list_from_course_code(
            $courseInfo['code'],
            $sessionId,
            null,
            null,
            $statusToFilter
        );

        if (empty($studentList)) {
            Display::addFlash(Display::return_message(get_lang('No users in course')));
            header('Location: '.api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq());
            exit;
        }

        $tblStats = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

        $studentIdList = [];
        if (!empty($studentList)) {
            $studentIdList = array_column($studentList, 'user_id');
        }

        $sessionCondition = api_get_session_condition($sessionId);
        if (false == $this->exercise_was_added_in_lp) {
            $sql = "SELECT * FROM $tblStats
                        WHERE
                            exe_exo_id = $exerciseId AND
                            orig_lp_id = 0 AND
                            orig_lp_item_id = 0 AND
                            status <> 'incomplete' AND
                            c_id = $courseId
                            $sessionCondition
                        ";
        } else {
            $lpId = null;
            if (!empty($this->lpList)) {
                // Taking only the first LP
                $lpId = $this->getLpBySession($sessionId);
                $lpId = $lpId['lp_id'];
            }

            $sql = "SELECT *
                        FROM $tblStats
                        WHERE
                            exe_exo_id = $exerciseId AND
                            orig_lp_id = $lpId AND
                            status <> 'incomplete' AND
                            session_id = $sessionId AND
                            c_id = $courseId ";
        }

        $sql .= ' ORDER BY exe_id DESC';

        $studentCount = 0;
        $sum = 0;
        $bestResult = 0;
        $sumResult = 0;
        $result = Database::query($sql);
        while ($data = Database::fetch_array($result, 'ASSOC')) {
            // Only take into account users in the current student list.
            if (!empty($studentIdList)) {
                if (!in_array($data['exe_user_id'], $studentIdList)) {
                    continue;
                }
            }

            if (!isset($students[$data['exe_user_id']])) {
                if (0 != $data['max_score']) {
                    $students[$data['exe_user_id']] = $data['score'];
                    if ($data['score'] > $bestResult) {
                        $bestResult = $data['score'];
                    }
                    $sumResult += $data['score'];
                }
            }
        }

        $count = count($studentList);
        $average = $sumResult / $count;
        $em = Database::getManager();

        $links = AbstractLink::getGradebookLinksFromItem(
            $this->getId(),
            LINK_EXERCISE,
            $courseInfo['code'],
            $sessionId
        );

        if (empty($links)) {
            $links = AbstractLink::getGradebookLinksFromItem(
                $this->iId,
                LINK_EXERCISE,
                $courseInfo['code'],
                $sessionId
            );
        }

        if (!empty($links)) {
            $repo = $em->getRepository(GradebookLink::class);

            foreach ($links as $link) {
                $linkId = $link['id'];
                /** @var GradebookLink $exerciseLink */
                $exerciseLink = $repo->find($linkId);
                if ($exerciseLink) {
                    $exerciseLink
                        ->setUserScoreList($students)
                        ->setBestScore($bestResult)
                        ->setAverageScore($average)
                        ->setScoreWeight($this->get_max_score());
                    $em->persist($exerciseLink);
                    $em->flush();
                }
            }
        }
    }

    /**
     * Return an HTML table of exercises for on-screen printing, including
     * action icons. If no exercise is present and the user can edit the
     * course, show a "create test" button.
     *
     * @param int    $categoryId
     * @param string $keyword
     * @param int    $userId
     * @param int    $courseId
     * @param int    $sessionId
     * @param bool   $returnData
     * @param int    $minCategoriesInExercise
     * @param int    $filterByResultDisabled
     * @param int    $filterByAttempt
     *
     * @return string|SortableTableFromArrayConfig
     */
    public static function exerciseGridResource(
        $categoryId,
        $keyword = '',
        $userId = 0,
        $courseId = 0,
        $sessionId = 0,
        $returnData = false,
        $minCategoriesInExercise = 0,
        $filterByResultDisabled = 0,
        $filterByAttempt = 0,
        $myActions = null,
        $returnTable = false
    ) {
        $is_allowedToEdit = api_is_allowed_to_edit(null, true);
        $courseId = $courseId ? (int) $courseId : api_get_course_int_id();
        $sessionId = $sessionId ? (int) $sessionId : api_get_session_id();

        $course = api_get_course_entity($courseId);
        $session = api_get_session_entity($sessionId);

        $userId = $userId ? (int) $userId : api_get_user_id();
        $user = api_get_user_entity($userId);

        $repo = Container::getQuizRepository();

        // 2. Get query builder from repo.
        $qb = $repo->getResourcesByCourse($course, $session);

        if (!empty($categoryId)) {
            $qb->andWhere($qb->expr()->eq('resource.exerciseCategory', $categoryId));
        } else {
            $qb->andWhere($qb->expr()->isNull('resource.exerciseCategory'));
        }

        $allowDelete = self::allowAction('delete');
        $allowClean = self::allowAction('clean_results');

        $TBL_TRACK_EXERCISES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

        $categoryId = (int) $categoryId;
        $keyword = Database::escape_string($keyword);
        $learnpath_id = isset($_REQUEST['learnpath_id']) ? (int) $_REQUEST['learnpath_id'] : null;
        $learnpath_item_id = isset($_REQUEST['learnpath_item_id']) ? (int) $_REQUEST['learnpath_item_id'] : null;
        $autoLaunchAvailable = false;
        if (1 == api_get_course_setting('enable_exercise_auto_launch') &&
            api_get_configuration_value('allow_exercise_auto_launch')
        ) {
            $autoLaunchAvailable = true;
        }

        $courseId = $course->getId();
        $tableRows = [];
        $origin = api_get_origin();
        $charset = 'utf-8';
        $token = Security::get_token();
        $isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh($userId, ['real_id' => $courseId]);
        $limitTeacherAccess = api_get_configuration_value('limit_exercise_teacher_access');
        $content = '';
        $column = 0;
        if ($is_allowedToEdit) {
            $column = 1;
        }

        $table = new SortableTableFromArrayConfig(
            [],
            $column,
            self::PAGINATION_ITEMS_PER_PAGE,
            'exercises_cat_'.$categoryId.'_'.api_get_course_int_id().'_'.api_get_session_id()
        );

        $limit = $table->per_page;
        $page = $table->page_nr;
        $from = $limit * ($page - 1);

        $categoryCondition = '';
        if (api_get_configuration_value('allow_exercise_categories')) {
            if (!empty($categoryId)) {
                $categoryCondition = " AND exercise_category_id = $categoryId ";
            } else {
                $categoryCondition = ' AND exercise_category_id IS NULL ';
            }
        }

        if (!empty($keyword)) {
            $qb->andWhere($qb->expr()->eq('resource.title', ':keyword'));
            $qb->setParameter('keyword', $keyword);
        }

        // Only for administrators
        if ($is_allowedToEdit) {
            $qb->andWhere($qb->expr()->neq('resource.active', -1));
        } else {
            $qb->andWhere($qb->expr()->eq('resource.active', 1));
        }

        $qb->setFirstResult($from);
        $qb->setMaxResults($limit);

        $filterByResultDisabledCondition = '';
        $filterByResultDisabled = (int) $filterByResultDisabled;
        if (!empty($filterByResultDisabled)) {
            $filterByResultDisabledCondition = ' AND e.results_disabled = '.$filterByResultDisabled;
        }
        $filterByAttemptCondition = '';
        $filterByAttempt = (int) $filterByAttempt;
        if (!empty($filterByAttempt)) {
            $filterByAttemptCondition = ' AND e.max_attempt = '.$filterByAttempt;
        }

        $exerciseList = $qb->getQuery()->getResult();

        $total = $repo->getCount($qb);

        $webPath = api_get_path(WEB_CODE_PATH);
        if (!empty($exerciseList)) {
            $visibilitySetting = api_get_configuration_value('show_hidden_exercise_added_to_lp');
            //avoid sending empty parameters
            $mylpid = empty($learnpath_id) ? '' : '&learnpath_id='.$learnpath_id;
            $mylpitemid = empty($learnpath_item_id) ? '' : '&learnpath_item_id='.$learnpath_item_id;

            /** @var CQuiz $exerciseEntity */
            foreach ($exerciseList as $exerciseEntity) {
                $currentRow = [];
                $exerciseId = $exerciseEntity->getIid();
                $actions = '';
                $attempt_text = '';
                $exercise = new Exercise($courseId);
                $exercise->read($exerciseId, false);

                if (empty($exercise->iId)) {
                    continue;
                }

                $allowToEditBaseCourse = true;
                $visibility = $visibilityInCourse = $exerciseEntity->isVisible($course);
                $visibilityInSession = false;
                if (!empty($sessionId)) {
                    // If we are in a session, the test is invisible
                    // in the base course, it is included in a LP
                    // *and* the setting to show it is *not*
                    // specifically set to true, then hide it.
                    if (false === $visibility) {
                        if (!$visibilitySetting) {
                            if ($exercise->exercise_was_added_in_lp) {
                                continue;
                            }
                        }
                    }

                    $visibility = $visibilityInSession = $exerciseEntity->isVisible($course, $session);
                }

                // Validation when belongs to a session
                $isBaseCourseExercise = true;
                if (!($visibilityInCourse && $visibilityInSession)) {
                    $isBaseCourseExercise = false;
                }

                if (!empty($sessionId) && $isBaseCourseExercise) {
                    $allowToEditBaseCourse = false;
                }

                $sessionStar = null;
                if (!$isBaseCourseExercise) {
                    $sessionStar = api_get_session_image($sessionId, $user);
                }

                $locked = $exercise->is_gradebook_locked;

                $startTime = $exerciseEntity->getStartTime();
                $endTime = $exerciseEntity->getEndTime();
                $time_limits = false;
                if (!empty($startTime) || !empty($endTime)) {
                    $time_limits = true;
                }

                $is_actived_time = false;
                if ($time_limits) {
                    // check if start time
                    $start_time = false;
                    if (!empty($startTime)) {
                        $start_time = api_strtotime($startTime->format('Y-m-d H:i:s'), 'UTC');
                    }
                    $end_time = false;
                    if (!empty($endTime)) {
                        $end_time = api_strtotime($endTime->format('Y-m-d H:i:s'), 'UTC');
                    }
                    $now = time();
                    //If both "clocks" are enable
                    if ($start_time && $end_time) {
                        if ($now > $start_time && $end_time > $now) {
                            $is_actived_time = true;
                        }
                    } else {
                        //we check the start and end
                        if ($start_time) {
                            if ($now > $start_time) {
                                $is_actived_time = true;
                            }
                        }
                        if ($end_time) {
                            if ($end_time > $now) {
                                $is_actived_time = true;
                            }
                        }
                    }
                }

                // Blocking empty start times see BT#2800
                // @todo replace global
                /*global $_custom;
                if (isset($_custom['exercises_hidden_when_no_start_date']) &&
                    $_custom['exercises_hidden_when_no_start_date']
                ) {
                    if (empty($startTime)) {
                        $time_limits = true;
                        $is_actived_time = false;
                    }
                }*/

                $cut_title = $exercise->getCutTitle();
                $alt_title = '';
                if ($cut_title != $exerciseEntity->getTitle()) {
                    $alt_title = ' title = "'.$exercise->getUnformattedTitle().'" ';
                }

                // Teacher only.
                if ($is_allowedToEdit) {
                    $lp_blocked = null;
                    if (true == $exercise->exercise_was_added_in_lp) {
                        $lp_blocked = Display::div(
                            get_lang(
                                'This exercise has been included in a learning path, so it cannot be accessed by students directly from here. If you want to put the same exercise available through the exercises tool, please make a copy of the current exercise using the copy icon.'
                            ),
                            ['class' => 'lp_content_type_label']
                        );
                    }

                    $style = '';
                    if (0 === $exerciseEntity->getActive() || false === $visibility) {
                        $style = 'color:grey';
                        //$title = Display::tag('font', $cut_title, ['style' => 'color:grey']);
                    }

                    $title = $cut_title;

                    $url = '<a
                        '.$alt_title.'
                        id="tooltip_'.$exerciseId.'"
                        href="overview.php?'.api_get_cidreq().$mylpid.$mylpitemid.'&exerciseId='.$exerciseId.'"
                        style = "'.$style.'"
                        >
                         '.Display::return_icon('quiz.png', $title).$title.
                        '</a>';

                    if (ExerciseLib::isQuizEmbeddable($exerciseEntity)) {
                        $embeddableIcon = Display::return_icon(
                            'om_integration.png',
                            get_lang('ThisQuizCanBeEmbeddable')
                        );
                        $url .= Display::div($embeddableIcon, ['class' => 'pull-right']);
                    }

                    $currentRow['title'] = $url.' '.$sessionStar.$lp_blocked;
                    $rowi = $exerciseEntity->getQuestions()->count();

                    if ($repo->isGranted('EDIT', $exerciseEntity) && $allowToEditBaseCourse) {
                        // Questions list
                        $actions = Display::url(
                            Display::return_icon('edit.png', get_lang('Edit')),
                            'admin.php?'.api_get_cidreq().'&exerciseId='.$exerciseId
                        );

                        // Test settings
                        $settings = Display::url(
                            Display::return_icon('settings.png', get_lang('Configure')),
                            'exercise_admin.php?'.api_get_cidreq().'&exerciseId='.$exerciseId
                        );

                        if ($limitTeacherAccess && !api_is_platform_admin()) {
                            $settings = '';
                        }
                        $actions .= $settings;

                        // Exercise results
                        $resultsLink = '<a href="exercise_report.php?'.api_get_cidreq().'&exerciseId='.$exerciseId.'">'.
                            Display::return_icon('test_results.png', get_lang('Results')).'</a>';

                        if ($limitTeacherAccess) {
                            if (api_is_platform_admin()) {
                                $actions .= $resultsLink;
                            }
                        } else {
                            // Exercise results
                            $actions .= $resultsLink;
                        }

                        // Auto launch
                        if ($autoLaunchAvailable) {
                            $autoLaunch = $exercise->getAutoLaunch();
                            if (empty($autoLaunch)) {
                                $actions .= Display::url(
                                    Display::return_icon(
                                        'launch_na.png',
                                        get_lang('Enable')
                                    ),
                                    'exercise.php?'.api_get_cidreq(
                                    ).'&action=enable_launch&sec_token='.$token.'&exerciseId='.$exerciseId
                                );
                            } else {
                                $actions .= Display::url(
                                    Display::return_icon(
                                        'launch.png',
                                        get_lang('Disable')
                                    ),
                                    'exercise.php?'.api_get_cidreq(
                                    ).'&action=disable_launch&sec_token='.$token.'&exerciseId='.$exerciseId
                                );
                            }
                        }

                        // Export
                        $actions .= Display::url(
                            Display::return_icon('cd.png', get_lang('Copy this exercise as a new one')),
                            '',
                            [
                                'onclick' => "javascript:if(!confirm('".addslashes(
                                        api_htmlentities(get_lang('Are you sure to copy'), ENT_QUOTES)
                                    )." ".addslashes($title)."?"."')) return false;",
                                'href' => 'exercise.php?'.api_get_cidreq(
                                    ).'&action=copy_exercise&sec_token='.$token.'&exerciseId='.$exerciseId,
                            ]
                        );

                        // Clean exercise
                        $clean = '';
                        if (true === $allowClean) {
                            if (!$locked) {
                                $clean = Display::url(
                                    Display::return_icon(
                                        'clean.png',
                                        get_lang('CleanStudentResults')
                                    ),
                                    '',
                                    [
                                        'onclick' => "javascript:if(!confirm('".
                                            addslashes(
                                                api_htmlentities(
                                                    get_lang('Are you sure to delete results'),
                                                    ENT_QUOTES
                                                )
                                            )." ".addslashes($title)."?"."')) return false;",
                                        'href' => 'exercise.php?'.api_get_cidreq(
                                            ).'&action=clean_results&sec_token='.$token.'&exerciseId='.$exerciseId,
                                    ]
                                );
                            } else {
                                $clean = Display::return_icon(
                                    'clean_na.png',
                                    get_lang('ResourceLockedByGradebook')
                                );
                            }
                        }

                        $actions .= $clean;
                        // Visible / invisible
                        // Check if this exercise was added in a LP
                        if ($exercise->exercise_was_added_in_lp) {
                            $visibility = Display::return_icon(
                                'invisible.png',
                                get_lang('AddedToLPCannotBeAccessed')
                            );
                        } else {
                            if (0 === $exerciseEntity->getActive()) {
                                $visibility = Display::url(
                                    Display::return_icon(
                                        'invisible.png',
                                        get_lang('Activate')
                                    ),
                                    'exercise.php?'.api_get_cidreq(
                                    ).'&choice=enable&sec_token='.$token.'&exerciseId='.$exerciseId
                                );
                            } else {
                                // else if not active
                                $visibility = Display::url(
                                    Display::return_icon(
                                        'visible.png',
                                        get_lang('Deactivate')
                                    ),
                                    'exercise.php?'.api_get_cidreq(
                                    ).'&choice=disable&sec_token='.$token.'&exerciseId='.$exerciseId
                                );
                            }
                        }

                        if ($limitTeacherAccess && !api_is_platform_admin()) {
                            $visibility = '';
                        }

                        $actions .= $visibility;

                        // Export qti ...
                        $export = Display::url(
                            Display::return_icon(
                                'export_qti2.png',
                                'IMS/QTI'
                            ),
                            'exercise.php?action=exportqti2&exerciseId='.$exerciseId.'&'.api_get_cidreq()
                        );

                        if ($limitTeacherAccess && !api_is_platform_admin()) {
                            $export = '';
                        }

                        $actions .= $export;
                    } else {
                        // not session
                        $actions = Display::return_icon(
                            'edit_na.png',
                            get_lang('ExerciseEditionNotAvailableInSession')
                        );

                        // Check if this exercise was added in a LP
                        if ($exercise->exercise_was_added_in_lp) {
                            $visibility = Display::return_icon(
                                'invisible.png',
                                get_lang('AddedToLPCannotBeAccessed')
                            );
                        } else {
                            if (0 === $exerciseEntity->getActive() || 0 == $visibility) {
                                $visibility = Display::url(
                                    Display::return_icon(
                                        'invisible.png',
                                        get_lang('Activate')
                                    ),
                                    'exercise.php?'.api_get_cidreq(
                                    ).'&choice=enable&sec_token='.$token.'&exerciseId='.$exerciseId
                                );
                            } else {
                                // else if not active
                                $visibility = Display::url(
                                    Display::return_icon(
                                        'visible.png',
                                        get_lang('Deactivate')
                                    ),
                                    'exercise.php?'.api_get_cidreq(
                                    ).'&choice=disable&sec_token='.$token.'&exerciseId='.$exerciseId
                                );
                            }
                        }

                        if ($limitTeacherAccess && !api_is_platform_admin()) {
                            $visibility = '';
                        }

                        $actions .= $visibility;
                        $actions .= '<a href="exercise_report.php?'.api_get_cidreq().'&exerciseId='.$exerciseId.'">'.
                            Display::return_icon('test_results.png', get_lang('Results')).'</a>';
                        $actions .= Display::url(
                            Display::return_icon('cd.gif', get_lang('Copy this exercise as a new one')),
                            '',
                            [
                                'onclick' => "javascript:if(!confirm('".addslashes(
                                        api_htmlentities(get_lang('Are you sure to copy'), ENT_QUOTES)
                                    )." ".addslashes($title)."?"."')) return false;",
                                'href' => 'exercise.php?'.api_get_cidreq(
                                    ).'&choice=copy_exercise&sec_token='.$token.'&exerciseId='.$exerciseId,
                            ]
                        );
                    }

                    // Delete
                    $delete = '';
                    if ($repo->isGranted('DELETE', $exerciseEntity) && $allowToEditBaseCourse) {
                        if (!$locked) {
                            $deleteUrl = 'exercise.php?'.api_get_cidreq().'&action=delete&sec_token='.$token.'&exerciseId='.$exerciseId;
                            $delete = Display::url(
                                Display::return_icon(
                                    'delete.png',
                                    get_lang('Delete')
                                ),
                                '',
                                [
                                    'onclick' => "javascript:if(!confirm('".
                                        addslashes(api_htmlentities(get_lang('Are you sure to delete?')))." ".
                                        addslashes($exercise->getUnformattedTitle())."?"."')) return false;",
                                    'href' => $deleteUrl,
                                ]
                            );
                        } else {
                            $delete = Display::return_icon(
                                'delete_na.png',
                                get_lang(
                                    'This option is not available because this activity is contained by an assessment, which is currently locked. To unlock the assessment, ask your platform administrator.'
                                )
                            );
                        }
                    }

                    if ($limitTeacherAccess && !api_is_platform_admin()) {
                        $delete = '';
                    }

                    if (!empty($minCategoriesInExercise)) {
                        $cats = TestCategory::getListOfCategoriesForTest($exercise);
                        if (!(count($cats) >= $minCategoriesInExercise)) {
                            continue;
                        }
                    }
                    $actions .= $delete;

                    // Number of questions.
                    $random = $exerciseEntity->getRandom();
                    if ($random > 0 || -1 == $random) {
                        // if random == -1 means use random questions with all questions
                        $random_number_of_question = $random;
                        if (-1 == $random_number_of_question) {
                            $random_number_of_question = $rowi;
                        }
                        if ($exerciseEntity->getRandomByCategory() > 0) {
                            $nbQuestionsTotal = TestCategory::getNumberOfQuestionRandomByCategory(
                                $exerciseId,
                                $random_number_of_question
                            );
                            $number_of_questions = $nbQuestionsTotal.' ';
                            $number_of_questions .= ($nbQuestionsTotal > 1) ? get_lang('QuestionsLowerCase') : get_lang(
                                'QuestionLowerCase'
                            );
                            $number_of_questions .= ' - ';
                            $number_of_questions .= min(
                                    TestCategory::getNumberMaxQuestionByCat($exerciseId),
                                    $random_number_of_question
                                ).' '.get_lang('QuestionByCategory');
                        } else {
                            $random_label = ' ('.get_lang('Random').') ';
                            $number_of_questions = $random_number_of_question.' '.$random_label.' / '.$rowi;
                            // Bug if we set a random value bigger than the real number of questions
                            if ($random_number_of_question > $rowi) {
                                $number_of_questions = $rowi.' '.$random_label;
                            }
                        }
                    } else {
                        $number_of_questions = $rowi;
                    }

                    $currentRow['count_questions'] = $number_of_questions;
                } else {
                    // Student only.
                    $visibility = $exerciseEntity->isVisible($course, null);
                    if (false === $visibility && !empty($sessionId)) {
                        $visibility = $exerciseEntity->isVisible($course, $session);
                    }

                    if (false === $visibility) {
                        continue;
                    }

                    $url = '<a '.$alt_title.'
                        href="overview.php?'.api_get_cidreq().$mylpid.$mylpitemid.'&exerciseId='.$exerciseId.'">'.
                        $cut_title.'</a>';

                    // Link of the exercise.
                    $currentRow['title'] = $url.' '.$sessionStar;
                    // This query might be improved later on by ordering by the new "tms" field rather than by exe_id
                    if ($returnData) {
                        $currentRow['title'] = $exercise->getUnformattedTitle();
                    }

                    $sessionCondition = api_get_session_condition(api_get_session_id());
                    // Don't remove this marker: note-query-exe-results
                    $sql = "SELECT * FROM $TBL_TRACK_EXERCISES
                            WHERE
                                exe_exo_id = ".$exerciseId." AND
                                exe_user_id = $userId AND
                                c_id = ".api_get_course_int_id()." AND
                                status <> 'incomplete' AND
                                orig_lp_id = 0 AND
                                orig_lp_item_id = 0
                                $sessionCondition
                            ORDER BY exe_id DESC";

                    $qryres = Database::query($sql);
                    $num = Database:: num_rows($qryres);

                    // Hide the results.
                    $my_result_disabled = $exerciseEntity->getResultsDisabled();
                    $attempt_text = '-';
                    // Time limits are on
                    if ($time_limits) {
                        // Exam is ready to be taken
                        if ($is_actived_time) {
                            // Show results
                            if (
                            in_array(
                                $my_result_disabled,
                                [
                                    RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS,
                                    RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
                                    RESULT_DISABLE_SHOW_SCORE_ONLY,
                                    RESULT_DISABLE_RANKING,
                                ]
                            )
                            ) {
                                // More than one attempt
                                if ($num > 0) {
                                    $row_track = Database:: fetch_array($qryres);
                                    $attempt_text = get_lang('Latest attempt').' : ';
                                    $attempt_text .= ExerciseLib::show_score(
                                        $row_track['score'],
                                        $row_track['max_score']
                                    );
                                } else {
                                    //No attempts
                                    $attempt_text = get_lang('Not attempted');
                                }
                            } else {
                                $attempt_text = '-';
                            }
                        } else {
                            // Quiz not ready due to time limits
                            //@todo use the is_visible function
                            if (!empty($startTime) && !empty($endTime)) {
                                $today = time();
                                if ($today < $start_time) {
                                    $attempt_text = sprintf(
                                        get_lang('ExerciseWillBeActivatedFromXToY'),
                                        api_convert_and_format_date($start_time),
                                        api_convert_and_format_date($end_time)
                                    );
                                } else {
                                    if ($today > $end_time) {
                                        $attempt_text = sprintf(
                                            get_lang('ExerciseWasActivatedFromXToY'),
                                            api_convert_and_format_date($start_time),
                                            api_convert_and_format_date($end_time)
                                        );
                                    }
                                }
                            } else {
                                if (!empty($startTime)) {
                                    $attempt_text = sprintf(
                                        get_lang('ExerciseAvailableFromX'),
                                        api_convert_and_format_date($start_time)
                                    );
                                }
                                if (!empty($endTime)) {
                                    $attempt_text = sprintf(
                                        get_lang('ExerciseAvailableUntilX'),
                                        api_convert_and_format_date($end_time)
                                    );
                                }
                            }
                        }
                    } else {
                        // Normal behaviour.
                        // Show results.
                        if (
                        in_array(
                            $my_result_disabled,
                            [
                                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS,
                                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
                                RESULT_DISABLE_SHOW_SCORE_ONLY,
                                RESULT_DISABLE_RANKING,
                                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK,
                            ]
                        )
                        ) {
                            if ($num > 0) {
                                $row_track = Database::fetch_array($qryres);
                                $attempt_text = get_lang('Latest attempt').' : ';
                                $attempt_text .= ExerciseLib::show_score(
                                    $row_track['score'],
                                    $row_track['max_score']
                                );
                            } else {
                                $attempt_text = get_lang('Not attempted');
                            }
                        }
                    }
                    if ($returnData) {
                        $attempt_text = $num;
                    }
                }

                $currentRow['attempt'] = $attempt_text;

                if ($is_allowedToEdit) {
                    $additionalActions = ExerciseLib::getAdditionalTeacherActions($exerciseId);

                    if (!empty($additionalActions)) {
                        $actions .= $additionalActions.PHP_EOL;
                    }

                    if (!empty($myActions) && is_callable($myActions)) {
                        $actions = $myActions($row);
                    }
                    $currentRow = [
                        $exerciseId,
                        $currentRow['title'],
                        $currentRow['count_questions'],
                        $actions,
                    ];
                } else {
                    $currentRow = [
                        $currentRow['title'],
                        $currentRow['attempt'],
                    ];

                    if ($isDrhOfCourse) {
                        $currentRow[] = '<a
                            href="exercise_report.php?'.api_get_cidreq().'&exerciseId='.$exerciseId.'">'.
                            Display::return_icon('test_results.png', get_lang('Results')).
                            '</a>';
                    }
                    if ($returnData) {
                        $currentRow['id'] = $exercise->id;
                        $currentRow['url'] = $webPath.'exercise/overview.php?'
                            .api_get_cidreq_params($courseId, $sessionId).'&'
                            ."$mylpid$mylpitemid&exerciseId={$exercise->id}";
                        $currentRow['name'] = $currentRow[0];
                    }
                }
                $tableRows[] = $currentRow;
            }
        }

        if (empty($tableRows) && empty($categoryId)) {
            if ($is_allowedToEdit && 'learnpath' !== $origin) {
                $content .= Display::noDataView(
                    get_lang('Quiz'),
                    Display::return_icon('quiz.png', '', [], 64),
                    get_lang('Create a new test'),
                    'exercise_admin.php?'.api_get_cidreq()
                );
            }
        } else {
            if (empty($tableRows)) {
                return '';
            }
            $table->setTableData($tableRows);
            $table->setTotalNumberOfItems($total);
            $table->set_additional_parameters(
                [
                    'cid' => api_get_course_int_id(),
                    'sid' => api_get_session_id(),
                    'category_id' => $categoryId,
                ]
            );

            if ($is_allowedToEdit) {
                $formActions = [];
                $formActions['visible'] = get_lang('Activate');
                $formActions['invisible'] = get_lang('Deactivate');
                $formActions['delete'] = get_lang('Delete');
                $table->set_form_actions($formActions);
            }

            $i = 0;
            if ($is_allowedToEdit) {
                $table->set_header($i++, '', false, 'width="18px"');
            }
            $table->set_header($i++, get_lang('Test name'), false);

            if ($is_allowedToEdit) {
                $table->set_header($i++, get_lang('Questions'), false);
                $table->set_header($i++, get_lang('Actions'), false, ['class' => 'text-right']);
            } else {
                $table->set_header($i++, get_lang('Status'), false);
                if ($isDrhOfCourse) {
                    $table->set_header($i++, get_lang('Actions'), false, ['class' => 'text-right']);
                }
            }

            if ($returnTable) {
                return $table;
            }
            $content .= $table->return_table();
        }

        return $content;
    }

    /**
     * @return int value in minutes
     */
    public function getResultAccess()
    {
        $extraFieldValue = new ExtraFieldValue('exercise');
        $value = $extraFieldValue->get_values_by_handler_and_field_variable(
            $this->iId,
            'results_available_for_x_minutes'
        );

        if (!empty($value) && isset($value['value'])) {
            return (int) $value['value'];
        }

        return 0;
    }

    /**
     * @param array $exerciseResultInfo
     *
     * @return bool
     */
    public function getResultAccessTimeDiff($exerciseResultInfo)
    {
        $value = $this->getResultAccess();
        if (!empty($value)) {
            $endDate = new DateTime($exerciseResultInfo['exe_date'], new DateTimeZone('UTC'));
            $endDate->add(new DateInterval('PT'.$value.'M'));
            $now = time();
            if ($endDate->getTimestamp() > $now) {
                return (int) $endDate->getTimestamp() - $now;
            }
        }

        return 0;
    }

    /**
     * @param array $exerciseResultInfo
     *
     * @return bool
     */
    public function hasResultsAccess($exerciseResultInfo)
    {
        $diff = $this->getResultAccessTimeDiff($exerciseResultInfo);
        if (0 === $diff) {
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public function getResultsAccess()
    {
        $extraFieldValue = new ExtraFieldValue('exercise');
        $value = $extraFieldValue->get_values_by_handler_and_field_variable(
            $this->iId,
            'results_available_for_x_minutes'
        );
        if (!empty($value)) {
            return (int) $value;
        }

        return 0;
    }

    /**
     * @param int   $questionId
     * @param bool  $show_results
     * @param array $question_result
     */
    public function getDelineationResult(Question $objQuestionTmp, $questionId, $show_results, $question_result)
    {
        $id = (int) $objQuestionTmp->id;
        $questionId = (int) $questionId;

        $final_overlap = $question_result['extra']['final_overlap'];
        $final_missing = $question_result['extra']['final_missing'];
        $final_excess = $question_result['extra']['final_excess'];

        $overlap_color = $question_result['extra']['overlap_color'];
        $missing_color = $question_result['extra']['missing_color'];
        $excess_color = $question_result['extra']['excess_color'];

        $threadhold1 = $question_result['extra']['threadhold1'];
        $threadhold2 = $question_result['extra']['threadhold2'];
        $threadhold3 = $question_result['extra']['threadhold3'];

        if ($show_results) {
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

            if ($final_excess > 100) {
                $final_excess = 100;
            }

            $table_resume = '
                    <table class="table table-hover table-striped data_table">
                        <tr class="row_odd" >
                            <td>&nbsp;</td>
                            <td><b>'.get_lang('Requirements').'</b></td>
                            <td><b>'.get_lang('YourAnswer').'</b></td>
                        </tr>
                        <tr class="row_even">
                            <td><b>'.get_lang('Overlap').'</b></td>
                            <td>'.get_lang('Min').' '.$threadhold1.'</td>
                            <td>
                                <div style="color:'.$overlap_color.'">
                                    '.(($final_overlap < 0) ? 0 : intval($final_overlap)).'
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><b>'.get_lang('Excess').'</b></td>
                            <td>'.get_lang('Max').' '.$threadhold2.'</td>
                            <td>
                                <div style="color:'.$excess_color.'">
                                    '.(($final_excess < 0) ? 0 : intval($final_excess)).'
                                </div>
                            </td>
                        </tr>
                        <tr class="row_even">
                            <td><b>'.get_lang('Missing').'</b></td>
                            <td>'.get_lang('Max').' '.$threadhold3.'</td>
                            <td>
                                <div style="color:'.$missing_color.'">
                                    '.(($final_missing < 0) ? 0 : intval($final_missing)).'
                                </div>
                            </td>
                        </tr>
                    </table>
                ';

            $answerType = $objQuestionTmp->selectType();
            /*if ($next == 0) {
                $try = $try_hotspot;
                $lp = $lp_hotspot;
                $destinationid = $select_question_hotspot;
                $url = $url_hotspot;
            } else {
                //show if no error
                $comment = $answerComment = $objAnswerTmp->selectComment($nbrAnswers);
                $answerDestination = $objAnswerTmp->selectDestination($nbrAnswers);
            }
            echo '<h1><div style="color:#333;">'.get_lang('Feedback').'</div></h1>';
            if ($organs_at_risk_hit > 0) {
                $message = '<br />'.get_lang('ResultIs').' <b>'.$result_comment.'</b><br />';
                $message .= '<p style="color:#DC0A0A;"><b>'.get_lang('OARHit').'</b></p>';
            } else {
                $message = '<p>'.get_lang('YourDelineation').'</p>';
                $message .= $table_resume;
                $message .= '<br />'.get_lang('ResultIs').' <b>'.$result_comment.'</b><br />';
            }
            $message .= '<p>'.$comment.'</p>';
            echo $message;*/

            // Showing the score
            /*$queryfree = "SELECT marks FROM $TBL_TRACK_ATTEMPT
                          WHERE exe_id = $id AND question_id =  $questionId";
            $resfree = Database::query($queryfree);
            $questionScore = Database::result($resfree, 0, 'marks');
            $totalScore += $questionScore;*/
            $relPath = api_get_path(REL_CODE_PATH);
            echo '</table></td></tr>';
            echo "
                        <tr>
                            <td colspan=\"2\">
                                <div id=\"hotspot-solution\"></div>
                                <script>
                                    $(function() {
                                        new HotspotQuestion({
                                            questionId: $questionId,
                                            exerciseId: {$this->id},
                                            exeId: $id,
                                            selector: '#hotspot-solution',
                                            for: 'solution',
                                            relPath: '$relPath'
                                        });
                                    });
                                </script>
                            </td>
                        </tr>
                    </table>
                ";
        }
    }

    /**
     * Clean exercise session variables.
     */
    public static function cleanSessionVariables()
    {
        Session::erase('objExercise');
        Session::erase('exe_id');
        Session::erase('calculatedAnswerId');
        Session::erase('duration_time_previous');
        Session::erase('duration_time');
        Session::erase('objQuestion');
        Session::erase('objAnswer');
        Session::erase('questionList');
        Session::erase('categoryList');
        Session::erase('exerciseResult');
        Session::erase('firstTime');

        Session::erase('time_per_question');
        Session::erase('question_start');
        Session::erase('exerciseResultCoordinates');
        Session::erase('hotspot_coord');
        Session::erase('hotspot_dest');
        Session::erase('hotspot_delineation_result');
    }

    /**
     * Get the first LP found matching the session ID.
     *
     * @param int $sessionId
     *
     * @return array
     */
    public function getLpBySession($sessionId)
    {
        if (!empty($this->lpList)) {
            $sessionId = (int) $sessionId;

            foreach ($this->lpList as $lp) {
                if ((int) $lp['session_id'] == $sessionId) {
                    return $lp;
                }
            }

            return current($this->lpList);
        }

        return [
            'lp_id' => 0,
            'max_score' => 0,
            'session_id' => 0,
        ];
    }

    public static function saveExerciseInLp($safe_item_id, $safe_exe_id)
    {
        $lp = Session::read('oLP');

        $safe_exe_id = (int) $safe_exe_id;
        $safe_item_id = (int) $safe_item_id;

        if (empty($lp) || empty($safe_exe_id) || empty($safe_item_id)) {
            return false;
        }

        $viewId = $lp->get_view_id();
        $course_id = api_get_course_int_id();
        $userId = (int) api_get_user_id();
        $viewId = (int) $viewId;

        $TBL_TRACK_EXERCICES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $TBL_LP_ITEM_VIEW = Database::get_course_table(TABLE_LP_ITEM_VIEW);
        $TBL_LP_ITEM = Database::get_course_table(TABLE_LP_ITEM);

        $sql = "SELECT start_date, exe_date, score, max_score, exe_exo_id, exe_duration
                FROM $TBL_TRACK_EXERCICES
                WHERE exe_id = $safe_exe_id AND exe_user_id = $userId";
        $res = Database::query($sql);
        $row_dates = Database::fetch_array($res);

        if (empty($row_dates)) {
            return false;
        }

        $duration = (int) $row_dates['exe_duration'];
        $score = (float) $row_dates['score'];
        $max_score = (float) $row_dates['max_score'];

        $sql = "UPDATE $TBL_LP_ITEM SET
                    max_score = '$max_score'
                WHERE iid = $safe_item_id";
        Database::query($sql);

        $sql = "SELECT iid FROM $TBL_LP_ITEM_VIEW
                WHERE
                    lp_item_id = $safe_item_id AND
                    lp_view_id = $viewId
                ORDER BY iid DESC
                LIMIT 1";
        $res_last_attempt = Database::query($sql);

        if (Database::num_rows($res_last_attempt) && !api_is_invitee()) {
            $row_last_attempt = Database::fetch_row($res_last_attempt);
            $lp_item_view_id = $row_last_attempt[0];

            $exercise = new Exercise($course_id);
            $exercise->read($row_dates['exe_exo_id']);
            $status = 'completed';

            if (!empty($exercise->pass_percentage)) {
                $status = 'failed';
                $success = ExerciseLib::isSuccessExerciseResult(
                    $score,
                    $max_score,
                    $exercise->pass_percentage
                );
                if ($success) {
                    $status = 'passed';
                }
            }

            $sql = "UPDATE $TBL_LP_ITEM_VIEW SET
                        status = '$status',
                        score = '$score',
                        total_time = '$duration'
                    WHERE iid = $lp_item_view_id";
            Database::query($sql);

            $sql = "UPDATE $TBL_TRACK_EXERCICES SET
                        orig_lp_item_view_id = '$lp_item_view_id'
                    WHERE exe_id = ".$safe_exe_id;
            Database::query($sql);
        }
    }

    /**
     * Get the user answers saved in exercise.
     *
     * @param int $attemptId
     *
     * @return array
     */
    public function getUserAnswersSavedInExercise($attemptId)
    {
        $exerciseResult = [];

        $attemptList = Event::getAllExerciseEventByExeId($attemptId);

        foreach ($attemptList as $questionId => $options) {
            foreach ($options as $option) {
                $question = Question::read($option['question_id']);

                if ($question) {
                    switch ($question->type) {
                        case FILL_IN_BLANKS:
                            $option['answer'] = $this->fill_in_blank_answer_to_string($option['answer']);
                            break;
                    }
                }

                if (!empty($option['answer'])) {
                    $exerciseResult[] = $questionId;

                    break;
                }
            }
        }

        return $exerciseResult;
    }

    /**
     * Get the number of user answers saved in exercise.
     *
     * @param int $attemptId
     *
     * @return int
     */
    public function countUserAnswersSavedInExercise($attemptId)
    {
        $answers = $this->getUserAnswersSavedInExercise($attemptId);

        return count($answers);
    }

    public static function allowAction($action)
    {
        if (api_is_platform_admin()) {
            return true;
        }

        $limitTeacherAccess = api_get_configuration_value('limit_exercise_teacher_access');
        $disableClean = api_get_configuration_value('disable_clean_exercise_results_for_teachers');

        switch ($action) {
            case 'delete':
                if (api_is_allowed_to_edit(null, true)) {
                    if ($limitTeacherAccess) {
                        return false;
                    }

                    return true;
                }
                break;
            case 'clean_results':
                if (api_is_allowed_to_edit(null, true)) {
                    if ($limitTeacherAccess) {
                        return false;
                    }

                    if ($disableClean) {
                        return false;
                    }

                    return true;
                }

                break;
        }

        return false;
    }

    public static function getLpListFromExercise($exerciseId, $courseId)
    {
        $tableLpItem = Database::get_course_table(TABLE_LP_ITEM);
        $tblLp = Database::get_course_table(TABLE_LP_MAIN);

        $exerciseId = (int) $exerciseId;
        $courseId = (int) $courseId;

        $sql = "SELECT
                    lp.name,
                    lpi.lp_id,
                    lpi.max_score
                FROM $tableLpItem lpi
                INNER JOIN $tblLp lp
                ON (lpi.lp_id = lp.iid)
                WHERE
                    lpi.item_type = '".TOOL_QUIZ."' AND
                    lpi.path = '$exerciseId'";
        $result = Database::query($sql);
        $lpList = [];
        if (Database::num_rows($result) > 0) {
            $lpList = Database::store_result($result, 'ASSOC');
        }

        return $lpList;
    }

    public function getReminderTable($questionList, $exercise_stat_info, $disableCheckBoxes = false)
    {
        $learnpath_id = isset($_REQUEST['learnpath_id']) ? (int) $_REQUEST['learnpath_id'] : 0;
        $learnpath_item_id = isset($_REQUEST['learnpath_item_id']) ? (int) $_REQUEST['learnpath_item_id'] : 0;
        $learnpath_item_view_id = isset($_REQUEST['learnpath_item_view_id']) ? (int) $_REQUEST['learnpath_item_view_id'] : 0;
        $categoryId = isset($_REQUEST['category_id']) ? (int) $_REQUEST['category_id'] : 0;

        if (empty($exercise_stat_info)) {
            return '';
        }

        $remindList = $exercise_stat_info['questions_to_check'];
        $remindList = explode(',', $remindList);

        $exeId = $exercise_stat_info['exe_id'];
        $exerciseId = $exercise_stat_info['exe_exo_id'];
        $exercise_result = $this->getUserAnswersSavedInExercise($exeId);

        $content = Display::label(get_lang('QuestionWithNoAnswer'), 'danger');
        $content .= '<div class="clear"></div><br />';
        $table = '';
        $counter = 0;
        // Loop over all question to show results for each of them, one by one
        foreach ($questionList as $questionId) {
            $objQuestionTmp = Question::read($questionId);
            $check_id = 'remind_list['.$questionId.']';
            $attributes = [
                'id' => $check_id,
                'onclick' => "save_remind_item(this, '$questionId');",
                'data-question-id' => $questionId,
            ];
            if (in_array($questionId, $remindList)) {
                $attributes['checked'] = 1;
            }

            $checkbox = Display::input('checkbox', 'remind_list['.$questionId.']', '', $attributes);
            $checkbox = '<div class="pretty p-svg p-curve">
                        '.$checkbox.'
                        <div class="state p-primary ">
                         <svg class="svg svg-icon" viewBox="0 0 20 20">
                            <path d="M7.629,14.566c0.125,0.125,0.291,0.188,0.456,0.188c0.164,0,0.329-0.062,0.456-0.188l8.219-8.221c0.252-0.252,0.252-0.659,0-0.911c-0.252-0.252-0.659-0.252-0.911,0l-7.764,7.763L4.152,9.267c-0.252-0.251-0.66-0.251-0.911,0c-0.252,0.252-0.252,0.66,0,0.911L7.629,14.566z" style="stroke: white;fill:white;"></path>
                         </svg>
                         <label>&nbsp;</label>
                        </div>
                    </div>';
            $counter++;
            $questionTitle = $counter.'. '.strip_tags($objQuestionTmp->selectTitle());
            // Check if the question doesn't have an answer.
            if (!in_array($questionId, $exercise_result)) {
                $questionTitle = Display::label($questionTitle, 'danger');
            }

            $label_attributes = [];
            $label_attributes['for'] = $check_id;
            if (false === $disableCheckBoxes) {
                $questionTitle = Display::tag('label', $checkbox.$questionTitle, $label_attributes);
            }
            $table .= Display::div($questionTitle, ['class' => 'exercise_reminder_item ']);
        }

        $content .= Display::div('', ['id' => 'message']).
            Display::div($table, ['class' => 'question-check-test']);

        $content .= '<script>
        var lp_data = $.param({
            "learnpath_id": '.$learnpath_id.',
            "learnpath_item_id" : '.$learnpath_item_id.',
            "learnpath_item_view_id": '.$learnpath_item_view_id.'
        });

        function final_submit() {
            // Normal inputs.
            window.location = "'.api_get_path(WEB_CODE_PATH).'exercise/exercise_result.php?'.api_get_cidreq().'&exe_id='.$exeId.'&" + lp_data;
        }

        function selectAll() {
            $("input[type=checkbox]").each(function () {
                $(this).prop("checked", 1);
                var question_id = $(this).data("question-id");
                var action = "add";
                $.ajax({
                    url: "'.api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?'.api_get_cidreq().'&a=add_question_to_reminder",
                    data: "question_id="+question_id+"&exe_id='.$exeId.'&action="+action,
                    success: function(returnValue) {
                    }
                });
            });
        }

        function changeOptionStatus(status)
        {
            $("input[type=checkbox]").each(function () {
                $(this).prop("checked", status);
            });

            var action = "";
            var option = "remove_all";
            if (status == 1) {
                option = "add_all";
            }
            $.ajax({
                url: "'.api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?'.api_get_cidreq().'&a=add_question_to_reminder",
                data: "option="+option+"&exe_id='.$exeId.'&action="+action,
                success: function(returnValue) {
                }
            });
        }

        function reviewQuestions() {
            var isChecked = 1;
            $("input[type=checkbox]").each(function () {
                if ($(this).prop("checked")) {
                    isChecked = 2;
                    return false;
                }
            });

            if (isChecked == 1) {
                $("#message").addClass("warning-message");
                $("#message").html("'.addslashes(get_lang('SelectAQuestionToReview')).'");
            } else {
                window.location = "exercise_submit.php?'.api_get_cidreq().'&category_id='.$categoryId.'&exerciseId='.$exerciseId.'&reminder=2&" + lp_data;
            }
        }

        function save_remind_item(obj, question_id) {
            var action = "";
            if ($(obj).prop("checked")) {
                action = "add";
            } else {
                action = "delete";
            }
            $.ajax({
                url: "'.api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?'.api_get_cidreq().'&a=add_question_to_reminder",
                data: "question_id="+question_id+"&exe_id='.$exeId.'&action="+action,
                success: function(returnValue) {
                }
            });
        }
        </script>';

        return $content;
    }

    public function getRadarsFromUsers($userList, $exercises, $dataSetLabels, $courseId, $sessionId)
    {
        $dataSet = [];
        $labels = [];
        $labelsWithId = [];
        /** @var Exercise $exercise */
        foreach ($exercises as $exercise) {
            if (empty($labels)) {
                $categoryNameList = TestCategory::getListOfCategoriesNameForTest($exercise->iId);
                if (!empty($categoryNameList)) {
                    $labelsWithId = array_column($categoryNameList, 'title', 'id');
                    asort($labelsWithId);
                    $labels = array_values($labelsWithId);
                }
            }

            foreach ($userList as $userId) {
                $results = Event::getExerciseResultsByUser(
                    $userId,
                    $exercise->iId,
                    $courseId,
                    $sessionId
                );

                if ($results) {
                    $firstAttempt = end($results);
                    $exeId = $firstAttempt['exe_id'];

                    ob_start();
                    $stats = ExerciseLib::displayQuestionListByAttempt(
                        $exercise,
                        $exeId,
                        false
                    );
                    ob_end_clean();

                    $categoryList = $stats['category_list'];
                    $tempResult = [];
                    foreach ($labelsWithId as $category_id => $title) {
                        if (isset($categoryList[$category_id])) {
                            $category_item = $categoryList[$category_id];
                            $tempResult[] = round($category_item['score'] / $category_item['total'] * 10);
                        } else {
                            $tempResult[] = 0;
                        }
                    }
                    $dataSet[] = $tempResult;
                }
            }
        }

        return $this->getRadar($labels, $dataSet, $dataSetLabels);
    }

    public function getAverageRadarsFromUsers($userList, $exercises, $dataSetLabels, $courseId, $sessionId)
    {
        $dataSet = [];
        $labels = [];
        $labelsWithId = [];

        $tempResult = [];
        /** @var Exercise $exercise */
        foreach ($exercises as $exercise) {
            $exerciseId = $exercise->iId;
            if (empty($labels)) {
                $categoryNameList = TestCategory::getListOfCategoriesNameForTest($exercise->iId);
                if (!empty($categoryNameList)) {
                    $labelsWithId = array_column($categoryNameList, 'title', 'id');
                    asort($labelsWithId);
                    $labels = array_values($labelsWithId);
                }
            }

            foreach ($userList as $userId) {
                $results = Event::getExerciseResultsByUser(
                    $userId,
                    $exerciseId,
                    $courseId,
                    $sessionId
                );

                if ($results) {
                    $firstAttempt = end($results);
                    $exeId = $firstAttempt['exe_id'];

                    ob_start();
                    $stats = ExerciseLib::displayQuestionListByAttempt(
                        $exercise,
                        $exeId,
                        false
                    );
                    ob_end_clean();

                    $categoryList = $stats['category_list'];
                    foreach ($labelsWithId as $category_id => $title) {
                        if (isset($categoryList[$category_id])) {
                            $category_item = $categoryList[$category_id];
                            if (!isset($tempResult[$exerciseId][$category_id])) {
                                $tempResult[$exerciseId][$category_id] = 0;
                            }
                            $tempResult[$exerciseId][$category_id] += $category_item['score'] / $category_item['total'] * 10;
                        }
                    }
                }
            }
        }

        $totalUsers = count($userList);

        foreach ($exercises as $exercise) {
            $exerciseId = $exercise->iId;
            $data = [];
            foreach ($labelsWithId as $category_id => $title) {
                if (isset($tempResult[$exerciseId]) && isset($tempResult[$exerciseId][$category_id])) {
                    $data[] = round($tempResult[$exerciseId][$category_id] / $totalUsers);
                } else {
                    $data[] = 0;
                }
            }
            $dataSet[] = $data;
        }

        return $this->getRadar($labels, $dataSet, $dataSetLabels);
    }

    public function getRadar($labels, $dataSet, $dataSetLabels = [])
    {
        if (empty($labels) || empty($dataSet)) {
            return '';
        }

        $displayLegend = 0;
        if (!empty($dataSetLabels)) {
            $displayLegend = 1;
        }

        $labels = json_encode($labels);

        $colorList = ChamiloApi::getColorPalette(true, true);

        $dataSetToJson = [];
        $counter = 0;
        foreach ($dataSet as $index => $resultsArray) {
            $color = isset($colorList[$counter]) ? $colorList[$counter] : 'rgb('.rand(0, 255).', '.rand(0, 255).', '.rand(0, 255).', 1.0)';

            $label = isset($dataSetLabels[$index]) ? $dataSetLabels[$index] : '';
            $background = str_replace('1.0', '0.2', $color);
            $dataSetToJson[] = [
                'fill' => false,
                'label' => $label,
                'backgroundColor' => $background,
                'borderColor' => $color,
                'pointBackgroundColor' => $color,
                'pointBorderColor' => '#fff',
                'pointHoverBackgroundColor' => '#fff',
                'pointHoverBorderColor' => $color,
                'pointRadius' => 6,
                'pointBorderWidth' => 3,
                'pointHoverRadius' => 10,
                'data' => $resultsArray,
            ];
            $counter++;
        }
        $resultsToJson = json_encode($dataSetToJson);

        return "
                <canvas id='categoryRadar' height='200'></canvas>
                <script>
                    var data = {
                        labels: $labels,
                        datasets: $resultsToJson
                    }
                    var options = {
                        responsive: true,
                        scale: {
                            angleLines: {
                                display: false
                            },
                            ticks: {
                                beginAtZero: true,
                                  min: 0,
                                  max: 10,
                                stepSize: 1,
                            },
                            pointLabels: {
                              fontSize: 14,
                              //fontStyle: 'bold'
                            },
                        },
                        elements: {
                            line: {
                                tension: 0,
                                borderWidth: 3
                            }
                        },
                        legend: {
                            //position: 'bottom'
                            display: $displayLegend
                        },
                        animation: {
                            animateScale: true,
                            animateRotate: true
                        },
                    };
                    var ctx = document.getElementById('categoryRadar').getContext('2d');
                    var myRadarChart = new Chart(ctx, {
                        type: 'radar',
                        data: data,
                        options: options
                    });
                </script>
                ";
    }

    /**
     * Returns true if the exercise is locked by percentage. an exercise attempt must be passed.
     */
    public function isBlockedByPercentage(array $attempt = []): bool
    {
        if (empty($attempt)) {
            return false;
        }
        $extraFieldValue = new ExtraFieldValue('exercise');
        $blockExercise = $extraFieldValue->get_values_by_handler_and_field_variable(
            $this->iId,
            'blocking_percentage'
        );

        if (empty($blockExercise['value'])) {
            return false;
        }

        $blockPercentage = (int) $blockExercise['value'];

        if (0 === $blockPercentage) {
            return false;
        }

        $resultPercentage = 0;

        if (isset($attempt['score']) && isset($attempt['max_score'])) {
            $weight = (int) $attempt['max_score'];
            $weight = (0 == $weight) ? 1 : $weight;
            $resultPercentage = float_format(
                ($attempt['score'] / $weight) * 100,
                1
            );
        }
        if ($resultPercentage <= $blockPercentage) {
            return true;
        }

        return false;
    }

    /**
     * Gets the question list ordered by the question_order setting (drag and drop).
     *
     * @param bool $adminView Optional.
     *
     * @return array
     */
    public function getQuestionOrderedList($adminView = false)
    {
        $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);

        // Getting question_order to verify that the question
        // list is correct and all question_order's were set
        $sql = "SELECT DISTINCT count(e.question_order) as count
                FROM $TBL_EXERCICE_QUESTION e
                INNER JOIN $TBL_QUESTIONS q
                ON (e.question_id = q.iid)
                WHERE
                  e.quiz_id	= ".$this->getId();

        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $count_question_orders = $row['count'];

        // Getting question list from the order (question list drag n drop interface).
        $sql = "SELECT DISTINCT e.question_id, e.question_order
                FROM $TBL_EXERCICE_QUESTION e
                INNER JOIN $TBL_QUESTIONS q
                ON (e.question_id = q.iid)
                WHERE

                    e.quiz_id = '".$this->getId()."'
                ORDER BY question_order";
        $result = Database::query($sql);

        // Fills the array with the question ID for this exercise
        // the key of the array is the question position
        $temp_question_list = [];
        $counter = 1;
        $questionList = [];
        while ($new_object = Database::fetch_object($result)) {
            if (!$adminView) {
                // Correct order.
                $questionList[$new_object->question_order] = $new_object->question_id;
            } else {
                $questionList[$counter] = $new_object->question_id;
            }

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
     * Get number of questions in exercise by user attempt.
     *
     * @return int
     */
    private function countQuestionsInExercise()
    {
        $lpId = isset($_REQUEST['learnpath_id']) ? (int) $_REQUEST['learnpath_id'] : 0;
        $lpItemId = isset($_REQUEST['learnpath_item_id']) ? (int) $_REQUEST['learnpath_item_id'] : 0;
        $lpItemViewId = isset($_REQUEST['learnpath_item_view_id']) ? (int) $_REQUEST['learnpath_item_view_id'] : 0;

        $trackInfo = $this->get_stat_track_exercise_info($lpId, $lpItemId, $lpItemViewId);

        if (!empty($trackInfo)) {
            $questionIds = explode(',', $trackInfo['data_tracking']);

            return count($questionIds);
        }

        return $this->getQuestionCount();
    }

    /**
     * Select N values from the questions per category array.
     *
     * @param array $categoriesAddedInExercise
     * @param array $question_list
     * @param array $questions_by_category
     * @param bool  $flatResult
     * @param bool  $randomizeQuestions
     * @param array $questionsByCategoryMandatory
     *
     * @return array
     */
    private function pickQuestionsPerCategory(
        $categoriesAddedInExercise,
        $question_list,
        &$questions_by_category,
        $flatResult = true,
        $randomizeQuestions = false,
        $questionsByCategoryMandatory = []
    ) {
        $addAll = true;
        $categoryCountArray = [];

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
                    $categoryCountArray[$category_id] = $count;
                    if (-1 == $count) {
                        $categoryCountArray[$category_id] = 999;
                    }
                }
            }
        }

        if (!empty($questions_by_category)) {
            $temp_question_list = [];
            foreach ($questions_by_category as $category_id => &$categoryQuestionList) {
                if (isset($categoryCountArray) && !empty($categoryCountArray)) {
                    $numberOfQuestions = 0;
                    if (isset($categoryCountArray[$category_id])) {
                        $numberOfQuestions = $categoryCountArray[$category_id];
                    }
                }

                if ($addAll) {
                    $numberOfQuestions = 999;
                }
                if (!empty($numberOfQuestions)) {
                    $mandatoryQuestions = [];
                    if (isset($questionsByCategoryMandatory[$category_id])) {
                        $mandatoryQuestions = $questionsByCategoryMandatory[$category_id];
                    }

                    $elements = TestCategory::getNElementsFromArray(
                        $categoryQuestionList,
                        $numberOfQuestions,
                        $randomizeQuestions,
                        $mandatoryQuestions
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
     * Sends a notification when a user ends an examn.
     *
     * @param array  $question_list_answers
     * @param string $origin
     * @param array  $user_info
     * @param string $url_email
     * @param array  $teachers
     */
    private function sendNotificationForOpenQuestions(
        $question_list_answers,
        $origin,
        $user_info,
        $url_email,
        $teachers
    ) {
        // Email configuration settings
        $courseCode = api_get_course_id();
        $courseInfo = api_get_course_info($courseCode);
        $sessionId = api_get_session_id();
        $sessionData = '';
        if (!empty($sessionId)) {
            $sessionInfo = api_get_session_info($sessionId);
            if (!empty($sessionInfo)) {
                $sessionData = '<tr>'
                    .'<td><em>'.get_lang('Session name').'</em></td>'
                    .'<td>&nbsp;<b>'.$sessionInfo['name'].'</b></td>'
                    .'</tr>';
            }
        }

        $msg = get_lang('A learner has answered an open question').'<br /><br />'
            .get_lang('Attempt details').' : <br /><br />'
            .'<table>'
            .'<tr>'
            .'<td><em>'.get_lang('Course name').'</em></td>'
            .'<td>&nbsp;<b>#course#</b></td>'
            .'</tr>'
            .$sessionData
            .'<tr>'
            .'<td>'.get_lang('Test attempted').'</td>'
            .'<td>&nbsp;#exercise#</td>'
            .'</tr>'
            .'<tr>'
            .'<td>'.get_lang('Learner name').'</td>'
            .'<td>&nbsp;#firstName# #lastName#</td>'
            .'</tr>'
            .'<tr>'
            .'<td>'.get_lang('Learner e-mail').'</td>'
            .'<td>&nbsp;#mail#</td>'
            .'</tr>'
            .'</table>';

        $open_question_list = null;
        foreach ($question_list_answers as $item) {
            $question = $item['question'];
            $answer = $item['answer'];
            $answer_type = $item['answer_type'];

            if (!empty($question) && !empty($answer) && FREE_ANSWER == $answer_type) {
                $open_question_list .=
                    '<tr>
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
            $msg .= '<p><br />'.get_lang('A learner has answered an open questionAre').' :</p>'.
                '<table width="730" height="136" border="0" cellpadding="3" cellspacing="3">';
            $msg .= $open_question_list;
            $msg .= '</table><br />';

            $msg = str_replace('#exercise#', $this->exercise, $msg);
            $msg = str_replace('#firstName#', $user_info['firstname'], $msg);
            $msg = str_replace('#lastName#', $user_info['lastname'], $msg);
            $msg = str_replace('#mail#', $user_info['email'], $msg);
            $msg = str_replace(
                '#course#',
                Display::url($courseInfo['title'], $courseInfo['course_public_url'].'?sid='.$sessionId),
                $msg
            );

            if ('learnpath' !== $origin) {
                $msg .= '<br /><a href="#url#">'.get_lang(
                        'Click this link to check the answer and/or give feedback'
                    ).'</a>';
            }
            $msg = str_replace('#url#', $url_email, $msg);
            $subject = get_lang('A learner has answered an open question');

            if (!empty($teachers)) {
                foreach ($teachers as $user_id => $teacher_data) {
                    MessageManager::send_message_simple(
                        $user_id,
                        $subject,
                        $msg
                    );
                }
            }
        }
    }

    /**
     * Send notification for oral questions.
     *
     * @param array  $question_list_answers
     * @param string $origin
     * @param int    $exe_id
     * @param array  $user_info
     * @param string $url_email
     * @param array  $teachers
     */
    private function sendNotificationForOralQuestions(
        $question_list_answers,
        $origin,
        $exe_id,
        $user_info,
        $url_email,
        $teachers
    ) {
        // Email configuration settings
        $courseCode = api_get_course_id();
        $courseInfo = api_get_course_info($courseCode);
        $oral_question_list = null;
        foreach ($question_list_answers as $item) {
            $question = $item['question'];
            $file = $item['generated_oral_file'];
            $answer = $item['answer'];
            if (0 == $answer) {
                $answer = '';
            }
            $answer_type = $item['answer_type'];
            if (!empty($question) && (!empty($answer) || !empty($file)) && ORAL_EXPRESSION == $answer_type) {
                if (!empty($file)) {
                    $file = Display::url($file, $file);
                }
                $oral_question_list .= '<br />
                    <table width="730" height="136" border="0" cellpadding="3" cellspacing="3">
                    <tr>
                        <td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;'.get_lang('Question').'</td>
                        <td width="473" valign="top" bgcolor="#F3F3F3">'.$question.'</td>
                    </tr>
                    <tr>
                        <td width="220" valign="top" bgcolor="#E5EDF8">&nbsp;&nbsp;'.get_lang('Answer').'</td>
                        <td valign="top" bgcolor="#F3F3F3">'.$answer.$file.'</td>
                    </tr></table>';
            }
        }

        if (!empty($oral_question_list)) {
            $msg = get_lang('A learner has attempted one or more oral question').'<br /><br />
                    '.get_lang('Attempt details').' : <br /><br />
                    <table>
                        <tr>
                            <td><em>'.get_lang('Course name').'</em></td>
                            <td>&nbsp;<b>#course#</b></td>
                        </tr>
                        <tr>
                            <td>'.get_lang('Test attempted').'</td>
                            <td>&nbsp;#exercise#</td>
                        </tr>
                        <tr>
                            <td>'.get_lang('Learner name').'</td>
                            <td>&nbsp;#firstName# #lastName#</td>
                        </tr>
                        <tr>
                            <td>'.get_lang('Learner e-mail').'</td>
                            <td>&nbsp;#mail#</td>
                        </tr>
                    </table>';
            $msg .= '<br />'.sprintf(
                    get_lang('A learner has attempted one or more oral questionAreX'),
                    $oral_question_list
                ).'<br />';
            $msg1 = str_replace('#exercise#', $this->exercise, $msg);
            $msg = str_replace('#firstName#', $user_info['firstname'], $msg1);
            $msg1 = str_replace('#lastName#', $user_info['lastname'], $msg);
            $msg = str_replace('#mail#', $user_info['email'], $msg1);
            $msg = str_replace('#course#', $courseInfo['name'], $msg1);

            if (!in_array($origin, ['learnpath', 'embeddable'])) {
                $msg .= '<br /><a href="#url#">'.get_lang(
                        'Click this link to check the answer and/or give feedback'
                    ).'</a>';
            }
            $msg1 = str_replace('#url#', $url_email, $msg);
            $mail_content = $msg1;
            $subject = get_lang('A learner has attempted one or more oral question');

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
     * Returns an array with the media list.
     *
     * @param array $questionList question list
     *
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
     */
    private function setMediaList($questionList)
    {
        $mediaList = [];
        /*
         * Media feature is not activated in 1.11.x
        if (!empty($questionList)) {
            foreach ($questionList as $questionId) {
                $objQuestionTmp = Question::read($questionId, $this->course_id);
                // If a media question exists
                if (isset($objQuestionTmp->parent_id) && $objQuestionTmp->parent_id != 0) {
                    $mediaList[$objQuestionTmp->parent_id][] = $objQuestionTmp->id;
                } else {
                    // Always the last item
                    $mediaList[999][] = $objQuestionTmp->id;
                }
            }
        }*/

        $this->mediaList = $mediaList;
    }

    /**
     * @return HTML_QuickForm_group
     */
    private function setResultDisabledGroup(FormValidator $form)
    {
        $resultDisabledGroup = [];

        $resultDisabledGroup[] = $form->createElement(
            'radio',
            'results_disabled',
            null,
            get_lang('Auto-evaluation mode: show score and expected answers'),
            RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS,
            ['id' => 'result_disabled_0']
        );

        $warning = sprintf(
            get_lang('TheSettingXWillChangeToX'),
            get_lang('FeedbackType'),
            get_lang('NoFeedback')
        );
        $resultDisabledGroup[] = $form->createElement(
            'radio',
            'results_disabled',
            null,
            get_lang('Exam mode: Do not show score nor answers'),
            RESULT_DISABLE_NO_SCORE_AND_EXPECTED_ANSWERS,
            [
                'id' => 'result_disabled_1',
                //'onclick' => 'check_results_disabled()'
                'onclick' => 'javascript:if(confirm('."'".addslashes($warning)."'".')) { check_results_disabled(); } else { return false;} ',
            ]
        );

        $resultDisabledGroup[] = $form->createElement(
            'radio',
            'results_disabled',
            null,
            get_lang('Practice mode: Show score only, by category if at least one is used'),
            RESULT_DISABLE_SHOW_SCORE_ONLY,
            [
                'id' => 'result_disabled_2',
                //'onclick' => 'check_results_disabled()'
                'onclick' => 'javascript:if(confirm('."'".addslashes($warning)."'".')) { check_results_disabled(); } else { return false;} ',
            ]
        );

        if (in_array($this->getFeedbackType(), [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])) {
            return $form->addGroup(
                $resultDisabledGroup,
                null,
                get_lang(
                    'Show score to learner'
                )
            );
        }

        $resultDisabledGroup[] = $form->createElement(
            'radio',
            'results_disabled',
            null,
            get_lang(
                'Show score on every attempt, show correct answers only on last attempt (only works with an attempts limit)'
            ),
            RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT,
            ['id' => 'result_disabled_4']
        );

        $resultDisabledGroup[] = $form->createElement(
            'radio',
            'results_disabled',
            null,
            get_lang(
                'Do not show the score (only when user finishes all attempts) but show feedback for each attempt.'
            ),
            RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK,
            [
                'id' => 'result_disabled_5',
                //'onclick' => 'check_results_disabled()'
                'onclick' => 'javascript:if(confirm('."'".addslashes($warning)."'".')) { check_results_disabled(); } else { return false;} ',
            ]
        );

        $resultDisabledGroup[] = $form->createElement(
            'radio',
            'results_disabled',
            null,
            get_lang(
                'Ranking mode: Do not show results details question by question and show a table with the ranking of all other users.'
            ),
            RESULT_DISABLE_RANKING,
            ['id' => 'result_disabled_6']
        );

        $resultDisabledGroup[] = $form->createElement(
            'radio',
            'results_disabled',
            null,
            get_lang(
                'Show only global score (not question score) and show only the correct answers, do not show incorrect answers at all'
            ),
            RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
            ['id' => 'result_disabled_7']
        );

        $resultDisabledGroup[] = $form->createElement(
            'radio',
            'results_disabled',
            null,
            get_lang('Auto-evaluation mode and ranking'),
            RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
            ['id' => 'result_disabled_8']
        );

        $resultDisabledGroup[] = $form->createElement(
            'radio',
            'results_disabled',
            null,
            get_lang('ExerciseCategoriesRadarMode'),
            RESULT_DISABLE_RADAR,
            ['id' => 'result_disabled_9']
        );

        $resultDisabledGroup[] = $form->createElement(
            'radio',
            'results_disabled',
            null,
            get_lang('Show the result to the learner: Show the score, the learner\'s choice and his feedback on each attempt, add the correct answer and his feedback when the chosen limit of attempts is reached.'),
            RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK,
            ['id' => 'result_disabled_10']
        );

        return $form->addGroup(
            $resultDisabledGroup,
            null,
            get_lang('Show score to learner')
        );
    }
}
