<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Search\Xapian\XapianIndexService;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionOption;

/**
 * Class Question.
 *
 * This class allows to instantiate an object of type Question
 *
 * @author Olivier Brouckaert, original author
 * @author Patrick Cool, LaTeX support
 * @author Julio Montoya <gugli100@gmail.com> lot of bug fixes
 * @author hubert.borderiou@grenet.fr - add question categories
 */
abstract class Question
{
    public $id;
    public $iid;
    public $question;
    public $description;
    public $weighting;
    public $position;
    public $type;
    public $level;
    public $picture;
    public $exerciseList; // array with the list of exercises which this question is in
    public $category_list;
    public $parent_id;
    public $category;
    public $mandatory;
    public $isContent;
    public $course;
    public $feedback;
    public $typePicture = 'new_question.png';
    public $explanationLangVar = '';
    public $questionTableClass = 'table table-striped question-answer-result__detail';
    public $questionTypeWithFeedback;
    public $extra;
    public $export = false;
    public $code;
    public static $questionTypes = [
        // — Single-choice
        UNIQUE_ANSWER                => ['unique_answer.class.php', 'UniqueAnswer', 'Unique answer'],
        UNIQUE_ANSWER_IMAGE          => ['UniqueAnswerImage.php', 'UniqueAnswerImage', 'Unique answer (image)'],
        UNIQUE_ANSWER_NO_OPTION      => ['unique_answer_no_option.class.php', 'UniqueAnswerNoOption', 'Unique answer (no options)'],

        // — Multiple-choice (all variants together)
        MULTIPLE_ANSWER              => ['multiple_answer.class.php', 'MultipleAnswer', 'Multiple answer'],
        GLOBAL_MULTIPLE_ANSWER       => ['global_multiple_answer.class.php', 'GlobalMultipleAnswer', 'Global multiple answer'],
        MULTIPLE_ANSWER_DROPDOWN     => ['MultipleAnswerDropdown.php', 'MultipleAnswerDropdown', 'Multiple answer (dropdown)'],
        MULTIPLE_ANSWER_DROPDOWN_COMBINATION => ['MultipleAnswerDropdownCombination.php', 'MultipleAnswerDropdownCombination', 'Multiple answer (dropdown combination)'],
        MULTIPLE_ANSWER_COMBINATION  => ['multiple_answer_combination.class.php', 'MultipleAnswerCombination', 'Multiple answer (combination)'],
        MULTIPLE_ANSWER_TRUE_FALSE   => ['multiple_answer_true_false.class.php', 'MultipleAnswerTrueFalse', 'True/False multiple answer'],
        MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE => [
            'multiple_answer_combination_true_false.class.php', 'MultipleAnswerCombinationTrueFalse', 'True/False combination multiple answer'
        ],
        MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY => [
            'MultipleAnswerTrueFalseDegreeCertainty.php', 'MultipleAnswerTrueFalseDegreeCertainty', 'True/False with degree of certainty'
        ],

        // — Matching / draggable
        MATCHING                     => ['matching.class.php', 'Matching', 'Matching'],
        MATCHING_COMBINATION         => ['MatchingCombination.php', 'MatchingCombination', 'Matching (combination)'],
        DRAGGABLE                    => ['Draggable.php', 'Draggable', 'Draggable'],
        MATCHING_DRAGGABLE           => ['MatchingDraggable.php', 'MatchingDraggable', 'Matching (draggable)'],
        MATCHING_DRAGGABLE_COMBINATION => ['MatchingDraggableCombination.php', 'MatchingDraggableCombination', 'Matching (draggable combination)'],

        // — Fill-in-the-blanks / calculated
        FILL_IN_BLANKS               => ['fill_blanks.class.php', 'FillBlanks', 'Fill in the blanks'],
        FILL_IN_BLANKS_COMBINATION   => ['FillBlanksCombination.php', 'FillBlanksCombination', 'Fill in the blanks (combination)'],
        CALCULATED_ANSWER            => ['calculated_answer.class.php', 'CalculatedAnswer', 'Calculated answer'],

        // — Open answers / expression
        FREE_ANSWER                  => ['freeanswer.class.php', 'FreeAnswer', 'Free answer'],
        ORAL_EXPRESSION              => ['oral_expression.class.php', 'OralExpression', 'Oral expression'],

        // — Hotspot
        HOT_SPOT                     => ['hotspot.class.php', 'HotSpot', 'Hotspot'],
        HOT_SPOT_COMBINATION         => ['HotSpotCombination.php', 'HotSpotCombination', 'Hotspot (combination)'],
        HOT_SPOT_DELINEATION         => ['HotSpotDelineation.php', 'HotSpotDelineation', 'Hotspot delineation'],

        // — Media / annotation
        MEDIA_QUESTION               => ['MediaQuestion.php', 'MediaQuestion', 'Media question'],
        ANNOTATION                   => ['Annotation.php', 'Annotation', 'Annotation'],

        // — Special
        READING_COMPREHENSION        => ['ReadingComprehension.php', 'ReadingComprehension', 'Reading comprehension'],
        PAGE_BREAK                   => ['PageBreakQuestion.php', 'PageBreakQuestion', 'Page break'],
        UPLOAD_ANSWER                => ['UploadAnswer.php', 'UploadAnswer', 'Upload answer'],
    ];

    /**
     * Question types that support adaptive scenario.
     */
    protected static $adaptiveScenarioTypes = [
        UNIQUE_ANSWER,
        MULTIPLE_ANSWER,
        MULTIPLE_ANSWER_COMBINATION,
        MULTIPLE_ANSWER_TRUE_FALSE,
        MATCHING,
        MATCHING_COMBINATION,
        DRAGGABLE,
        MATCHING_DRAGGABLE,
        MATCHING_DRAGGABLE_COMBINATION,
        HOT_SPOT_DELINEATION,
        FILL_IN_BLANKS,
        FILL_IN_BLANKS_COMBINATION,
        CALCULATED_ANSWER,
        ANNOTATION,
        // Do NOT include FREE_ANSWER, ORAL_EXPRESSION, UPLOAD_ANSWER, MEDIA_QUESTION, PAGE_BREAK, etc.
    ];

    /**
     * constructor of the class.
     *
     * @author Olivier Brouckaert
     */
    public function __construct()
    {
        $this->id = 0;
        $this->iid = 0;
        $this->question = '';
        $this->description = '';
        $this->weighting = 0;
        $this->position = 1;
        $this->picture = '';
        $this->level = 1;
        $this->category = 0;
        // This variable is used when loading an exercise like an scenario with
        // an special hotspot: final_overlap, final_missing, final_excess
        $this->extra = '';
        $this->exerciseList = [];
        $this->course = api_get_course_info();
        $this->category_list = [];
        $this->parent_id = 0;
        $this->mandatory = 0;
        // See BT#12611
        $this->questionTypeWithFeedback = [
            MATCHING,
            MATCHING_COMBINATION,
            MATCHING_DRAGGABLE,
            MATCHING_DRAGGABLE_COMBINATION,
            DRAGGABLE,
            FILL_IN_BLANKS,
            FILL_IN_BLANKS_COMBINATION,
            FREE_ANSWER,
            UPLOAD_ANSWER,
            ORAL_EXPRESSION,
            CALCULATED_ANSWER,
            ANNOTATION,
        ];
    }

    public function getId()
    {
        return $this->iid;
    }

    /**
     * @return int|null
     */
    public function getIsContent()
    {
        $isContent = null;
        if (isset($_REQUEST['isContent'])) {
            $isContent = (int) $_REQUEST['isContent'];
        }

        return $this->isContent = $isContent;
    }

    /**
     * Reads question information from the data base.
     *
     * @param int   $id              - question ID
     * @param array $course_info
     * @param bool  $getExerciseList
     *
     * @return Question
     *
     * @author Olivier Brouckaert
     */
    public static function read($id, $course_info = [], $getExerciseList = true)
    {
        $id = (int) $id;
        if (empty($course_info)) {
            $course_info = api_get_course_info();
        }
        $course_id = $course_info['real_id'];

        if (empty($course_id) || -1 == $course_id) {
            return false;
        }

        $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $TBL_EXERCISE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);

        $sql = "SELECT *
                FROM $TBL_QUESTIONS
                WHERE iid = $id ";
        $result = Database::query($sql);

        // if the question has been found
        if ($object = Database::fetch_object($result)) {
            $objQuestion = self::getInstance($object->type);
            if (!empty($objQuestion)) {
                $objQuestion->id = $id;
                $objQuestion->iid = (int) $object->iid;
                $objQuestion->question = $object->question;
                $objQuestion->description = $object->description;
                $objQuestion->weighting = $object->ponderation;
                $objQuestion->position = $object->position;
                $objQuestion->type = (int) $object->type;
                $objQuestion->picture = $object->picture;
                $objQuestion->level = (int) $object->level;
                $objQuestion->extra = $object->extra;
                $objQuestion->course = $course_info;
                $objQuestion->feedback = isset($object->feedback) ? $object->feedback : '';
                $objQuestion->category = TestCategory::getCategoryForQuestion($id, $course_id);
                $objQuestion->code = isset($object->code) ? $object->code : '';
                $categoryInfo = TestCategory::getCategoryInfoForQuestion($id, $course_id);

                if (!empty($categoryInfo)) {
                    if (isset($categoryInfo['category_id'])) {
                        $objQuestion->category = (int) $categoryInfo['category_id'];
                    }

                    if (('true' === api_get_setting('exercise.allow_mandatory_question_in_category')) &&
                        isset($categoryInfo['mandatory'])
                    ) {
                        $objQuestion->mandatory = (int) $categoryInfo['mandatory'];
                    }
                }

                if ($getExerciseList) {
                    $tblQuiz   = Database::get_course_table(TABLE_QUIZ_TEST);
                    $sessionId = (int) api_get_session_id();
                    $sessionJoin = $sessionId > 0 ? "rl.session_id = $sessionId" : "rl.session_id IS NULL";

                    $sql = "SELECT DISTINCT q.quiz_id
            FROM $TBL_EXERCISE_QUESTION q
            INNER JOIN $tblQuiz e  ON e.iid = q.quiz_id
            INNER JOIN resource_node rn ON rn.id = e.resource_node_id
            INNER JOIN resource_link rl ON rl.resource_node_id = rn.id AND $sessionJoin
            WHERE q.question_id = $id
              AND rl.deleted_at IS NULL";
                    $result = Database::query($sql);
                    while ($obj = Database::fetch_object($result)) {
                        $objQuestion->exerciseList[] = (int) $obj->quiz_id;
                    }
                }

                $objQuestion->parent_id = isset($object->parent_media_id)
                    ? (int) $object->parent_media_id
                    : 0;

                return $objQuestion;
            }
        }

        // question not found
        return false;
    }

    /**
     * returns the question title.
     *
     * @author Olivier Brouckaert
     *
     * @return string - question title
     */
    public function selectTitle()
    {
        if ('true' !== api_get_setting('editor.save_titles_as_html')) {
            return $this->question;
        }

        return Display::div($this->question, ['style' => 'display: inline-block;']);
    }

    public function getTitleToDisplay(Exercise $exercise, int $itemNumber): string
    {
        $showQuestionTitleHtml = ('true' === api_get_setting('editor.save_titles_as_html'));
        $title = '';
        if ('true' === api_get_setting('exercise.show_question_id')) {
            $title .= '<h4>#'.$this->course['code'].'-'.$this->iid.'</h4>';
        }

        $title .= $showQuestionTitleHtml ? '' : '<strong>';
        if (1 !== $exercise->getHideQuestionNumber()) {
            $title .= $itemNumber.'. ';
        }
        $title .= $this->selectTitle();
        $title .= $showQuestionTitleHtml ? '' : '</strong>';

        return Display::div(
            $title,
            ['class' => 'question_title']
        );
    }

    /**
     * returns the question description.
     *
     * @author Olivier Brouckaert
     *
     * @return string - question description
     */
    public function selectDescription()
    {
        return $this->description;
    }

    /**
     * returns the question weighting.
     *
     * @author Olivier Brouckaert
     *
     * @return int - question weighting
     */
    public function selectWeighting()
    {
        return $this->weighting;
    }

    /**
     * returns the answer type.
     *
     * @author Olivier Brouckaert
     *
     * @return int - answer type
     */
    public function selectType()
    {
        return $this->type;
    }

    /**
     * returns the level of the question.
     *
     * @author Nicolas Raynaud
     *
     * @return int - level of the question, 0 by default
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * changes the question title.
     *
     * @param string $title - question title
     *
     * @author Olivier Brouckaert
     */
    public function updateTitle($title)
    {
        $this->question = $title;
    }

    /**
     * changes the question description.
     *
     * @param string $description - question description
     *
     * @author Olivier Brouckaert
     */
    public function updateDescription($description)
    {
        $this->description = $description;
    }

    /**
     * changes the question weighting.
     *
     * @param int $weighting - question weighting
     *
     * @author Olivier Brouckaert
     */
    public function updateWeighting($weighting)
    {
        $this->weighting = $weighting;
    }

    /**
     * @param array $category
     *
     * @author Hubert Borderiou 12-10-2011
     */
    public function updateCategory($category)
    {
        $this->category = $category;
    }

    public function setMandatory($value)
    {
        $this->mandatory = (int) $value;
    }

    /**
     * in this version, a question can only have 1 category
     * if category is 0, then question has no category then delete the category entry.
     *
     * @author Hubert Borderiou 12-10-2011
     */
    public function saveCategory(int $categoryId): bool
    {
        if ($categoryId <= 0) {
            $this->deleteCategory();
        } else {
            // update or add category for a question
            $table = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
            $categoryId = (int) $categoryId;
            $questionId = (int) $this->id;
            $sql = "SELECT count(*) AS nb FROM $table
                    WHERE
                        question_id = $questionId
                    ";
            $res = Database::query($sql);
            $row = Database::fetch_array($res);
            $allowMandatory = ('true' === api_get_setting('exercise.allow_mandatory_question_in_category'));
            if ($row['nb'] > 0) {
                $extraMandatoryCondition = '';
                if ($allowMandatory) {
                    $extraMandatoryCondition = ", mandatory = {$this->mandatory}";
                }
                $sql = "UPDATE $table
                        SET category_id = $categoryId
                        $extraMandatoryCondition
                        WHERE
                            question_id = $questionId
                        ";
                Database::query($sql);
            } else {
                $sql = "INSERT INTO $table (question_id, category_id)
                        VALUES ($questionId, $categoryId)
                        ";
                Database::query($sql);
                if ($allowMandatory) {
                    $id = Database::insert_id();
                    if ($id) {
                        $sql = "UPDATE $table SET mandatory = {$this->mandatory}
                                WHERE iid = $id";
                        Database::query($sql);
                    }
                }
            }
        }

        return true;
    }

    /**
     * @author hubert borderiou 12-10-2011
     *
     *                      delete any category entry for question id
     *                      delete the category for question
     */
    public function deleteCategory(): bool
    {
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $questionId = (int) $this->id;
        if (empty($questionId)) {
            return false;
        }
        $sql = "DELETE FROM $table
                WHERE
                    question_id = $questionId
                ";
        Database::query($sql);

        return true;
    }

    /**
     * changes the question position.
     *
     * @param int $position - question position
     *
     * @author Olivier Brouckaert
     */
    public function updatePosition($position)
    {
        $this->position = $position;
    }

    /**
     * changes the question level.
     *
     * @param int $level - question level
     *
     * @author Nicolas Raynaud
     */
    public function updateLevel($level)
    {
        $this->level = $level;
    }

    /**
     * changes the answer type. If the user changes the type from "unique answer" to "multiple answers"
     * (or conversely) answers are not deleted, otherwise yes.
     *
     * @param int $type - answer type
     *
     * @author Olivier Brouckaert
     */
    public function updateType($type)
    {
        $table = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $course_id = $this->course['real_id'];

        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        }
        // if we really change the type
        if ($type != $this->type) {
            // if we don't change from "unique answer" to "multiple answers" (or conversely)
            if (!in_array($this->type, [UNIQUE_ANSWER, MULTIPLE_ANSWER]) ||
                !in_array($type, [UNIQUE_ANSWER, MULTIPLE_ANSWER])
            ) {
                // removes old answers
                $sql = "DELETE FROM $table
                        WHERE c_id = $course_id AND question_id = ".(int) ($this->id);
                Database::query($sql);
            }

            $this->type = $type;
        }
    }

    /**
     * Set title.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->question = $title;
    }

    /**
     * Sets extra info.
     *
     * @param string $extra
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;
    }

    /**
     * updates the question in the data base
     * if an exercise ID is provided, we add that exercise ID into the exercise list.
     *
     * @author Olivier Brouckaert
     *
     * @param Exercise $exercise
     */
    public function save($exercise)
    {
        $TBL_EXERCISE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $em = Database::getManager();
        $exerciseId = $exercise->iId;

        $id = $this->id;
        $type = $this->type;
        $c_id = $this->course['real_id'];

        $courseEntity = api_get_course_entity($c_id);
        $categoryId = $this->category;

        $questionCategoryRepo = Container::getQuestionCategoryRepository();
        $questionRepo = Container::getQuestionRepository();

        // question already exists
        if (!empty($id)) {
            /** @var CQuizQuestion $question */
            $question = $questionRepo->find($id);
            if ($question) {
                $question
                    ->setQuestion($this->question)
                    ->setDescription($this->description)
                    ->setPonderation($this->weighting)
                    ->setPosition($this->position)
                    ->setType($this->type)
                    ->setExtra($this->extra)
                    ->setLevel((int) $this->level)
                    ->setFeedback($this->feedback)
                    ->setParentMediaId($this->parent_id);

                if (!empty($categoryId)) {
                    $category = $questionCategoryRepo->find($categoryId);
                    $question->updateCategory($category);
                }

                $em->persist($question);
                $em->flush();

                Event::addEvent(
                    LOG_QUESTION_UPDATED,
                    LOG_QUESTION_ID,
                    $this->iid
                );
            }
        } else {
            // Creates a new question
            $sql = "SELECT max(position)
                    FROM $TBL_QUESTIONS as question,
                    $TBL_EXERCISE_QUESTION as test_question
                    WHERE
                        question.iid = test_question.question_id AND
                        test_question.quiz_id = ".$exerciseId;
            $result = Database::query($sql);
            $current_position = Database::result($result, 0, 0);
            $this->updatePosition($current_position + 1);
            $position = $this->position;
            //$exerciseEntity = $exerciseRepo->find($exerciseId);

            $question = (new CQuizQuestion())
                ->setQuestion($this->question)
                ->setDescription($this->description)
                ->setPonderation($this->weighting)
                ->setPosition($position)
                ->setType($this->type)
                ->setExtra($this->extra)
                ->setLevel((int) $this->level)
                ->setFeedback($this->feedback)
                ->setParentMediaId($this->parent_id)
                ->setParent($courseEntity)
                ->setCreator(api_get_user_entity())
                ->addCourseLink($courseEntity, api_get_session_entity(), api_get_group_entity());

            $em->persist($question);
            $em->flush();

            $this->id = $question->getIid();

            if ($this->id) {
                Event::addEvent(
                    LOG_QUESTION_CREATED,
                    LOG_QUESTION_ID,
                    $this->id
                );

                $questionRepo->addFileFromFileRequest($question, 'imageUpload');

                // If hotspot, create first answer
                if (in_array($type, [HOT_SPOT, HOT_SPOT_COMBINATION, HOT_SPOT_ORDER])) {
                    $quizAnswer = new CQuizAnswer();
                    $quizAnswer
                        ->setQuestion($question)
                        ->setPonderation(10)
                        ->setPosition(1)
                        ->setHotspotCoordinates('0;0|0|0')
                        ->setHotspotType('square');

                    $em->persist($quizAnswer);
                    $em->flush();
                }

                if (HOT_SPOT_DELINEATION == $type) {
                    $quizAnswer = new CQuizAnswer();
                    $quizAnswer
                        ->setQuestion($question)
                        ->setPonderation(10)
                        ->setPosition(1)
                        ->setHotspotCoordinates('0;0|0|0')
                        ->setHotspotType('delineation');

                    $em->persist($quizAnswer);
                    $em->flush();
                }
            }
        }

        // if the question is created in an exercise
        if (!empty($exerciseId)) {
            // adds the exercise into the exercise list of this question
            $this->addToList($exerciseId, true);
        }
    }

    /**
     * @param int  $exerciseId
     * @param bool $addQs
     * @param bool $rmQs
     */
    public function search_engine_edit(
        $exerciseId,
        $addQs = false,
        $rmQs = false
    ) {
        // Chamilo 2 uses Symfony-based indexing. Legacy indexer (course_code) is not compatible.
        if (class_exists(XapianIndexService::class)) {
            return;
        }
        // update search engine and its values table if enabled
        if (!empty($exerciseId) && 'true' == api_get_setting('search_enabled') &&
            extension_loaded('xapian')
        ) {
            $course_id = api_get_course_id();
            // get search_did
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            if ($addQs || $rmQs) {
                //there's only one row per question on normal db and one document per question on search engine db
                $sql = 'SELECT * FROM %s
                    WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_second_level=%s LIMIT 1';
                $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
            } else {
                $sql = 'SELECT * FROM %s
                    WHERE course_code=\'%s\' AND tool_id=\'%s\'
                    AND ref_id_high_level=%s AND ref_id_second_level=%s LIMIT 1';
                $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $exerciseId, $this->id);
            }
            $res = Database::query($sql);

            if (Database::num_rows($res) > 0 || $addQs) {
                $di = new ChamiloIndexer();
                if ($addQs) {
                    $question_exercises = [(int) $exerciseId];
                } else {
                    $question_exercises = [];
                }
                isset($_POST['language']) ? $lang = Database::escape_string($_POST['language']) : $lang = 'english';
                $di->connectDb(null, null, $lang);

                // retrieve others exercise ids
                $se_ref = Database::fetch_array($res);
                $se_doc = $di->get_document((int) $se_ref['search_did']);
                if (false !== $se_doc) {
                    if (false !== ($se_doc_data = $di->get_document_data($se_doc))) {
                        $se_doc_data = UnserializeApi::unserialize(
                            'not_allowed_classes',
                            $se_doc_data
                        );
                        if (isset($se_doc_data[SE_DATA]['type']) &&
                            SE_DOCTYPE_EXERCISE_QUESTION == $se_doc_data[SE_DATA]['type']
                        ) {
                            if (isset($se_doc_data[SE_DATA]['exercise_ids']) &&
                                is_array($se_doc_data[SE_DATA]['exercise_ids'])
                            ) {
                                foreach ($se_doc_data[SE_DATA]['exercise_ids'] as $old_value) {
                                    if (!in_array($old_value, $question_exercises)) {
                                        $question_exercises[] = $old_value;
                                    }
                                }
                            }
                        }
                    }
                }
                if ($rmQs) {
                    while (false !== ($key = array_search($exerciseId, $question_exercises))) {
                        unset($question_exercises[$key]);
                    }
                }

                // build the chunk to index
                $ic_slide = new IndexableChunk();
                $ic_slide->addValue('title', $this->question);
                $ic_slide->addCourseId($course_id);
                $ic_slide->addToolId(TOOL_QUIZ);
                $xapian_data = [
                    SE_COURSE_ID => $course_id,
                    SE_TOOL_ID => TOOL_QUIZ,
                    SE_DATA => [
                        'type' => SE_DOCTYPE_EXERCISE_QUESTION,
                        'exercise_ids' => $question_exercises,
                        'question_id' => (int) $this->id,
                    ],
                    SE_USER => (int) api_get_user_id(),
                ];
                $ic_slide->xapian_data = serialize($xapian_data);
                $ic_slide->addValue('content', $this->description);

                //TODO: index answers, see also form validation on question_admin.inc.php

                $di->remove_document($se_ref['search_did']);
                $di->addChunk($ic_slide);

                //index and return search engine document id
                if (!empty($question_exercises)) { // if empty there is nothing to index
                    $did = $di->index();
                    unset($di);
                }
                if ($did || $rmQs) {
                    // save it to db
                    if ($addQs || $rmQs) {
                        $sql = "DELETE FROM %s
                            WHERE course_code = '%s' AND tool_id = '%s' AND ref_id_second_level = '%s'";
                        $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
                    } else {
                        $sql = "DELETE FROM %S
                            WHERE
                                course_code = '%s'
                                AND tool_id = '%s'
                                AND tool_id = '%s'
                                AND ref_id_high_level = '%s'
                                AND ref_id_second_level = '%s'";
                        $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $exerciseId, $this->id);
                    }
                    Database::query($sql);
                    if ($rmQs) {
                        if (!empty($question_exercises)) {
                            $sql = "INSERT INTO %s (
                                    id, course_code, tool_id, ref_id_high_level, ref_id_second_level, search_did
                                )
                                VALUES (
                                    NULL, '%s', '%s', %s, %s, %s
                                )";
                            $sql = sprintf(
                                $sql,
                                $tbl_se_ref,
                                $course_id,
                                TOOL_QUIZ,
                                array_shift($question_exercises),
                                $this->id,
                                $did
                            );
                            Database::query($sql);
                        }
                    } else {
                        $sql = "INSERT INTO %s (
                                id, course_code, tool_id, ref_id_high_level, ref_id_second_level, search_did
                            )
                            VALUES (
                                NULL , '%s', '%s', %s, %s, %s
                            )";
                        $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $exerciseId, $this->id, $did);
                        Database::query($sql);
                    }
                }
            }
        }
    }

    /**
     * adds an exercise into the exercise list.
     *
     * @author Olivier Brouckaert
     *
     * @param int  $exerciseId - exercise ID
     * @param bool $fromSave   - from $this->save() or not
     */
    public function addToList($exerciseId, $fromSave = false)
    {
        $exerciseRelQuestionTable = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $id = (int) $this->id;
        $exerciseId = (int) $exerciseId;

        // checks if the exercise ID is not in the list
        if (!empty($exerciseId) && !in_array($exerciseId, $this->exerciseList)) {
            $this->exerciseList[] = $exerciseId;
            $courseId = isset($this->course['real_id']) ? $this->course['real_id'] : 0;
            $newExercise = new Exercise($courseId);
            $newExercise->read($exerciseId, false);
            $count = $newExercise->getQuestionCount();
            $count++;
            $sql = "INSERT INTO $exerciseRelQuestionTable (question_id, quiz_id, question_order)
                    VALUES (".$id.', '.$exerciseId.", '$count')";
            Database::query($sql);

            // we do not want to reindex if we had just saved adnd indexed the question
            if (!$fromSave) {
                $this->search_engine_edit($exerciseId, true);
            }
        }
    }

    /**
     * removes an exercise from the exercise list.
     *
     * @author Olivier Brouckaert
     *
     * @param int $exerciseId - exercise ID
     * @param int $courseId
     *
     * @return bool - true if removed, otherwise false
     */
    public function removeFromList($exerciseId, $courseId = 0)
    {
        $table = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $tableQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $id = (int) $this->id;
        $exerciseId = (int) $exerciseId;

        // searches the position of the exercise ID in the list
        $pos = array_search($exerciseId, $this->exerciseList);
        $courseId = empty($courseId) ? api_get_course_int_id() : (int) $courseId;

        // exercise not found
        if (false === $pos) {
            return false;
        } else {
            // deletes the position in the array containing the wanted exercise ID
            unset($this->exerciseList[$pos]);
            //update order of other elements
            $sql = "SELECT question_order
                    FROM $table
                    WHERE
                        question_id = $id AND
                        quiz_id = $exerciseId";
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $row = Database::fetch_array($res);
                if (!empty($row['question_order'])) {
                    $sql = "UPDATE $table
                            SET question_order = question_order-1
                            WHERE
                                quiz_id = $exerciseId AND
                                question_order > ".$row['question_order'];
                    Database::query($sql);
                }
            }

            $sql = "DELETE FROM $table
                    WHERE
                        question_id = $id AND
                        quiz_id = $exerciseId";
            Database::query($sql);

            $reset = "UPDATE $tableQuestion
                  SET parent_media_id = NULL
                  WHERE parent_media_id = $id";
            Database::query($reset);

            return true;
        }
    }

    /**
     * Deletes a question from the database
     * the parameter tells if the question is removed from all exercises (value = 0),
     * or just from one exercise (value = exercise ID).
     *
     * @author Olivier Brouckaert
     *
     * @param int $deleteFromEx - exercise ID if the question is only removed from one exercise
     *
     * @return bool
     */
    public function delete($deleteFromEx = 0)
    {
        if (empty($this->course)) {
            return false;
        }

        $courseId = $this->course['real_id'];

        if (empty($courseId)) {
            return false;
        }

        $TBL_EXERCISE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $TBL_REPONSES = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $TBL_QUIZ_QUESTION_REL_CATEGORY = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);

        $id = (int) $this->id;

        // if the question must be removed from all exercises
        if (!$deleteFromEx) {
            //update the question_order of each question to avoid inconsistencies
            $sql = "SELECT quiz_id, question_order
                    FROM $TBL_EXERCISE_QUESTION
                    WHERE question_id = ".$id;

            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                while ($row = Database::fetch_array($res)) {
                    if (!empty($row['question_order'])) {
                        $sql = "UPDATE $TBL_EXERCISE_QUESTION
                                SET question_order = question_order-1
                                WHERE
                                    quiz_id = ".(int) ($row['quiz_id']).' AND
                                    question_order > '.$row['question_order'];
                        Database::query($sql);
                    }
                }
            }

            $reset = "UPDATE $TBL_QUESTIONS
                  SET parent_media_id = NULL
                  WHERE parent_media_id = $id";
            Database::query($reset);

            $sql = "DELETE FROM $TBL_EXERCISE_QUESTION
                    WHERE question_id = ".$id;
            Database::query($sql);

            $sql = "DELETE FROM $TBL_QUESTIONS
                    WHERE iid = ".$id;
            Database::query($sql);

            $sql = "DELETE FROM $TBL_REPONSES
                    WHERE question_id = ".$id;
            Database::query($sql);

            // remove the category of this question in the question_rel_category table
            $sql = "DELETE FROM $TBL_QUIZ_QUESTION_REL_CATEGORY
                    WHERE
                        question_id = ".$id;
            Database::query($sql);

            // Add extra fields.
            $extraField = new ExtraFieldValue('question');
            $extraField->deleteValuesByItem($this->iid);

            /*api_item_property_update(
                $this->course,
                TOOL_QUIZ,
                $id,
                'QuizQuestionDeleted',
                api_get_user_id()
            );*/
            Event::addEvent(
                LOG_QUESTION_DELETED,
                LOG_QUESTION_ID,
                $this->iid
            );
            //$this->removePicture();
        } else {
            // just removes the exercise from the list
            $this->removeFromList($deleteFromEx, $courseId);
            /*
            api_item_property_update(
                $this->course,
                TOOL_QUIZ,
                $id,
                'QuizQuestionDeleted',
                api_get_user_id()
            );*/
            Event::addEvent(
                LOG_QUESTION_REMOVED_FROM_QUIZ,
                LOG_QUESTION_ID,
                $this->iid
            );
        }

        return true;
    }

    /**
     * Duplicates the question.
     *
     * @author Olivier Brouckaert
     *
     * @param array $courseInfo Course info of the destination course
     *
     * @return false|string ID of the new question
     */
    public function duplicate($courseInfo = [])
    {
        $courseInfo = empty($courseInfo) ? $this->course : $courseInfo;

        if (empty($courseInfo)) {
            return false;
        }
        $TBL_QUESTION_OPTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);

        $questionText = $this->question;
        $description = $this->description;

        // Using the same method used in the course copy to transform URLs
        if ($this->course['id'] != $courseInfo['id']) {
            $description = DocumentManager::replaceUrlWithNewCourseCode(
                $description,
                $this->course['code'],
                $courseInfo['id']
            );
            $questionText = DocumentManager::replaceUrlWithNewCourseCode(
                $questionText,
                $this->course['code'],
                $courseInfo['id']
            );
        }

        $course_id = $courseInfo['real_id'];

        // Read the source options
        $options = self::readQuestionOption($this->id, $this->course['real_id']);

        $em = Database::getManager();
        $courseEntity = api_get_course_entity($course_id);

        $question = (new CQuizQuestion())
            ->setQuestion($questionText)
            ->setDescription($description)
            ->setPonderation($this->weighting)
            ->setPosition($this->position)
            ->setType($this->type)
            ->setExtra($this->extra)
            ->setLevel($this->level)
            ->setFeedback($this->feedback)
            ->setParent($courseEntity)
            ->addCourseLink($courseEntity)
        ;

        $em->persist($question);
        $em->flush();
        $newQuestionId = $question->getIid();

        if ($newQuestionId) {
            // Add extra fields.
            $extraField = new ExtraFieldValue('question');
            $extraField->copy($this->iid, $newQuestionId);

            if (!empty($options)) {
                // Saving the quiz_options
                foreach ($options as $item) {
                    $item['question_id'] = $newQuestionId;
                    $item['c_id'] = $course_id;
                    unset($item['iid']);
                    unset($item['iid']);
                    Database::insert($TBL_QUESTION_OPTIONS, $item);
                }
            }

            // Duplicates the picture of the hotspot
            // @todo implement copy of hotspot question
            if (HOT_SPOT == $this->type) {
                @error_log('[Question::duplicate] Hotspot picture copy not implemented yet. Skipping to avoid fatal error.');
            }
        }

        return $newQuestionId;
    }

    /**
     * @return string
     */
    public function get_question_type_name(): string
    {
        $labelKey = trim((string) $this->explanationLangVar);
        if ($labelKey !== '') {
            $translated = get_lang($labelKey);
            if ($translated !== $labelKey) {
                return $translated;
            }
        }

        $def = self::$questionTypes[$this->type] ?? null;
        $className = is_array($def) ? ($def[1] ?? '') : '';
        if ($className !== '') {
            $human = preg_replace('/(?<!^)(?=[A-Z])/', ' ', $className) ?: $className;
            $translated = get_lang($human);

            return $translated !== $human ? $translated : $human;
        }

        return '';
    }

    /**
     * @param string $type
     */
    public static function get_question_type($type)
    {
        return self::$questionTypes[$type];
    }

    /**
     * @return array
     */
    public static function getQuestionTypeList(): array
    {
        $list = self::$questionTypes;

        if ('true' !== api_get_setting('enable_quiz_scenario')) {
            unset($list[HOT_SPOT_DELINEATION]);
        }

        ksort($list, SORT_NUMERIC);

        return $list;
    }

    /**
     * Returns an instance of the class corresponding to the type.
     *
     * @param int $type the type of the question
     *
     * @return $this instance of a Question subclass (or of Questionc class by default)
     */
    public static function getInstance($type)
    {
        if (null !== $type) {
            [$fileName, $className] = self::get_question_type($type);
            if (!empty($fileName)) {
                if (class_exists($className)) {
                    return new $className();
                } else {
                    echo 'Can\'t instanciate class '.$className.' of type '.$type;
                }
            }
        }

        return null;
    }

    /**
     * Creates the form to create / edit a question
     * A subclass can redefine this function to add fields...
     *
     * @param FormValidator $form
     * @param Exercise      $exercise
     */
    public function createForm(&$form, $exercise)
    {
        $zoomOptions = api_get_setting('exercise.quiz_image_zoom', true);
        if (isset($zoomOptions['options'])) {
            $finderFolder = api_get_path(WEB_PATH).'vendor/studio-42/elfinder/';
            echo '<!-- elFinder CSS (REQUIRED) -->';
            echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$finderFolder.'css/elfinder.full.css">';
            echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$finderFolder.'css/theme.css">';

            echo '<!-- elFinder JS (REQUIRED) -->';
            echo '<script src="'.$finderFolder.'js/elfinder.full.js"></script>';

            echo '<!-- elFinder translation (OPTIONAL) -->';
            $language = 'en';
            $platformLanguage = api_get_language_isocode();
            $iso = api_get_language_isocode($platformLanguage);
            $filePart = "vendor/studio-42/elfinder/js/i18n/elfinder.$iso.js";
            $file = api_get_path(SYS_PATH).$filePart;
            $includeFile = '';
            if (file_exists($file)) {
                $includeFile = '<script src="'.api_get_path(WEB_PATH).$filePart.'"></script>';
                $language = $iso;
            }
            echo $includeFile;
            echo '<script>
        $(function() {
            $(".create_img_link").click(function(e){
                e.preventDefault();
                e.stopPropagation();
                var imageZoom = $("input[name=\'imageZoom\']").val();
                var imageWidth = $("input[name=\'imageWidth\']").val();
                CKEDITOR.instances.questionDescription.insertHtml(\'<img id="zoom_picture" class="zoom_picture" src="\'+imageZoom+\'" data-zoom-image="\'+imageZoom+\'" width="\'+imageWidth+\'px" />\');
            });

            $("input[name=\'imageZoom\']").on("click", function(){
                var elf = $("#elfinder").elfinder({
                    url : "'.api_get_path(WEB_LIBRARY_PATH).'elfinder/connectorAction.php?'.api_get_cidreq().'",
                    getFileCallback: function(file) {
                        var filePath = file; //file contains the relative url.
                        var imgPath = "<img src = \'"+filePath+"\'/>";
                        $("input[name=\'imageZoom\']").val(filePath.url);
                        $("#elfinder").remove(); //close the window after image is selected
                    },
                    startPathHash: "l2_Lw", // Sets the course driver as default
                    resizable: false,
                    lang: "'.$language.'"
                }).elfinder("instance");
            });
        });
        </script>';
            echo '<div id="elfinder"></div>';
        }

        // Question name
        if ('true' === api_get_setting('editor.save_titles_as_html')) {
            $editorConfig = ['ToolbarSet' => 'TitleAsHtml'];
            $form->addHtmlEditor(
                'questionName',
                get_lang('Question'),
                false,
                false,
                $editorConfig
            );
        } else {
            $form->addText('questionName', get_lang('Question'));
        }

        $form->addRule('questionName', get_lang('Please type the question'), 'required');

        // Default content
        $isContent = isset($_REQUEST['isContent']) ? (int) $_REQUEST['isContent'] : null;

        // Question type (answer type)
        $answerType = isset($_REQUEST['answerType']) ? (int) $_REQUEST['answerType'] : null;
        $form->addHidden('answerType', $answerType);

        // HTML editor for description
        $editorConfig = [
            'ToolbarSet' => 'TestQuestionDescription',
            'Height' => '150',
        ];

        if (!api_is_allowed_to_edit(null, true)) {
            $editorConfig['UserStatus'] = 'student';
        }

        $form->addButtonAdvancedSettings('advanced_params');
        $form->addHtml('<div id="advanced_params_options" style="display:none">');

        if (isset($zoomOptions['options'])) {
            $form->addElement('text', 'imageZoom', get_lang('Image URL'));
            $form->addElement('text', 'imageWidth', get_lang('px width'));
            $form->addButton('btn_create_img', get_lang('Add to editor'), 'plus', 'info', 'small', 'create_img_link');
        }

        $form->addHtmlEditor(
            'questionDescription',
            get_lang('Enrich question'),
            false,
            false,
            $editorConfig
        );

        if (MEDIA_QUESTION != $this->type) {
            // Advanced parameters.
            $form->addSelect(
                'questionLevel',
                get_lang('Difficulty'),
                self::get_default_levels()
            );

            // Categories.
            $form->addSelect(
                'questionCategory',
                get_lang('Category'),
                TestCategory::getCategoriesIdAndName()
            );

            $courseMedias = self::prepare_course_media_select($exercise->iId);
            $form->addSelect(
                'parent_id',
                get_lang('Attach to media'),
                $courseMedias
            );

            if (EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM == $exercise->getQuestionSelectionType() &&
                ('true' === api_get_setting('exercise.allow_mandatory_question_in_category'))
            ) {
                $form->addCheckBox('mandatory', get_lang('Mandatory?'));
            }

            $text = get_lang('Save the question');
            switch ($this->type) {
                case UNIQUE_ANSWER:
                    $buttonGroup = [];
                    $buttonGroup[] = $form->addButtonSave(
                        $text,
                        'submitQuestion',
                        true
                    );
                    $buttonGroup[] = $form->addButton(
                        'convertAnswer',
                        get_lang('Convert to multiple answer'),
                        'dot-circle-o',
                        'default',
                        null,
                        null,
                        null,
                        true
                    );
                    $form->addGroup($buttonGroup);

                    break;
                case MULTIPLE_ANSWER:
                    $buttonGroup = [];
                    $buttonGroup[] = $form->addButtonSave(
                        $text,
                        'submitQuestion',
                        true
                    );
                    $buttonGroup[] = $form->addButton(
                        'convertAnswer',
                        get_lang('Convert to unique answer'),
                        'check-square-o',
                        'default',
                        null,
                        null,
                        null,
                        true
                    );
                    $form->addGroup($buttonGroup);

                    break;
            }
        }

        $form->addElement('html', '</div>');

        // Sample default questions when creating from templates
        if (!isset($_GET['fromExercise'])) {
            switch ($answerType) {
                case 1:
                    $this->question = get_lang('Select the good reasoning');

                    break;
                case 2:
                    $this->question = get_lang('The marasmus is a consequence of');

                    break;
                case 3:
                    $this->question = get_lang('Calculate the Body Mass Index');

                    break;
                case 4:
                    $this->question = get_lang('Order the operations');

                    break;
                case 5:
                    $this->question = get_lang('List what you consider the 10 top qualities of a good project manager?');

                    break;
                case 9:
                    $this->question = get_lang('The marasmus is a consequence of');

                    break;
            }
        }

        // -------------------------------------------------------------------------
        // Adaptive scenario (success/failure) — centralised for supported types
        // -------------------------------------------------------------------------
        $scenarioEnabled    = ('true' === api_get_setting('enable_quiz_scenario'));
        $hasExercise        = ($exercise instanceof Exercise);
        $isAdaptiveFeedback = $hasExercise &&
            EXERCISE_FEEDBACK_TYPE_DIRECT === $exercise->getFeedbackType();
        $supportsScenario   = in_array(
            (int) $this->type,
            static::$adaptiveScenarioTypes,
            true
        );

        if ($scenarioEnabled && $isAdaptiveFeedback && $supportsScenario && $hasExercise) {
            // Build the question list once per exercise to feed the scenario selector
            $exercise->setQuestionList(true);
            $questionList = $exercise->getQuestionList();

            if (is_array($questionList) && !empty($questionList)) {
                $this->addAdaptiveScenarioFields($form, $questionList);
                // Pre-fill selector defaults when editing an existing question in this exercise.
                if (!empty($this->id)) {
                    $this->loadAdaptiveScenarioDefaults($form, $exercise);
                }
            }
        }

        if (null !== $exercise) {
            if ($exercise->questionFeedbackEnabled && $this->showFeedback($exercise)) {
                $form->addTextarea('feedback', get_lang('Feedback if not correct'));
            }
        }

        $extraField = new ExtraField('question');
        $extraField->addElements($form, $this->iid);

        // Default values
        $defaults = [
            'questionName'        => $this->question,
            'questionDescription' => $this->description,
            'questionLevel'       => $this->level,
            'questionCategory'    => $this->category,
            'feedback'            => $this->feedback,
            'mandatory'           => $this->mandatory,
            'parent_id'           => $this->parent_id,
        ];

        // Came from the question pool
        if (isset($_GET['fromExercise'])) {
            $form->setDefaults($defaults);
        }

        if (!isset($_GET['newQuestion']) || $isContent) {
            $form->setDefaults($defaults);
        }

        /*if (!empty($_REQUEST['myid'])) {
            $form->setDefaults($defaults);
        } else {
            if ($isContent == 1) {
                $form->setDefaults($defaults);
            }
        }*/
    }

    /**
     * Function which process the creation of questions.
     */
    public function processCreation(FormValidator $form, Exercise $exercise)
    {
        $this->parent_id = (int) $form->getSubmitValue('parent_id');
        $this->updateTitle($form->getSubmitValue('questionName'));
        $this->updateDescription($form->getSubmitValue('questionDescription'));
        $this->updateLevel($form->getSubmitValue('questionLevel'));
        $this->updateCategory($form->getSubmitValue('questionCategory'));
        $this->setMandatory($form->getSubmitValue('mandatory'));
        $this->setFeedback($form->getSubmitValue('feedback'));

        //Save normal question if NOT media
        if (MEDIA_QUESTION != $this->type) {
            $this->save($exercise);
            // modify the exercise
            $exercise->addToList($this->id);
            $exercise->update_question_positions();

            $params = $form->exportValues();
            $params['item_id'] = $this->id;

            $extraFieldValues = new ExtraFieldValue('question');
            $extraFieldValues->saveFieldValues($params);
        }
    }

    /**
     * Creates the form to create / edit the answers of the question.
     */
    abstract public function createAnswersForm(FormValidator $form);

    /**
     * Process the creation of answers.
     *
     * @param FormValidator $form
     * @param Exercise      $exercise
     */
    abstract public function processAnswersCreation($form, $exercise);

    /**
     * Displays the menu of question types.
     *
     * @param Exercise $objExercise
     */
    public static function displayTypeMenu(Exercise $objExercise)
    {
        if (empty($objExercise)) {
            return '';
        }

        $feedbackType = $objExercise->getFeedbackType();
        $exerciseId   = $objExercise->id;

        $questionTypeList = self::getQuestionTypeList();

        if (!isset($feedbackType)) {
            $feedbackType = 0;
        }

        switch ($feedbackType) {
            case EXERCISE_FEEDBACK_TYPE_DIRECT:
                // Keep original behavior: base types for adaptative tests.
                $questionTypeList = [
                    UNIQUE_ANSWER        => self::$questionTypes[UNIQUE_ANSWER],
                    HOT_SPOT_DELINEATION => self::$questionTypes[HOT_SPOT_DELINEATION],
                ];

                // Add all other non-open question types.
                $allTypes = self::getQuestionTypeList();

                // Exclude the classic open question types from the filter list
                // as the system cannot provide immediate feedback on these.
                if (isset($allTypes[FREE_ANSWER])) {
                    unset($allTypes[FREE_ANSWER]);
                }
                if (isset($allTypes[ORAL_EXPRESSION])) {
                    unset($allTypes[ORAL_EXPRESSION]);
                }
                if (isset($allTypes[ANNOTATION])) {
                    unset($allTypes[ANNOTATION]);
                }
                if (isset($allTypes[MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY])) {
                    unset($allTypes[MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY]);
                }
                if (isset($allTypes[UPLOAD_ANSWER])) {
                    unset($allTypes[UPLOAD_ANSWER]);
                }
                if (isset($allTypes[ANSWER_IN_OFFICE_DOC])) {
                    unset($allTypes[ANSWER_IN_OFFICE_DOC]);
                }
                if (isset($allTypes[PAGE_BREAK])) {
                    unset($allTypes[PAGE_BREAK]);
                }

                // Append remaining types, without overriding the original ones.
                foreach ($allTypes as $typeId => $def) {
                    if (!isset($questionTypeList[$typeId])) {
                        $questionTypeList[$typeId] = $def;
                    }
                }

                break;
            case EXERCISE_FEEDBACK_TYPE_POPUP:
                $questionTypeList = [
                    UNIQUE_ANSWER        => self::$questionTypes[UNIQUE_ANSWER],
                    MULTIPLE_ANSWER      => self::$questionTypes[MULTIPLE_ANSWER],
                    DRAGGABLE            => self::$questionTypes[DRAGGABLE],
                    HOT_SPOT_DELINEATION => self::$questionTypes[HOT_SPOT_DELINEATION],
                    CALCULATED_ANSWER    => self::$questionTypes[CALCULATED_ANSWER],
                ];

                break;
            default:
                unset($questionTypeList[HOT_SPOT_DELINEATION]);

                break;
        }

        echo '<div class="card">';
        echo '  <div class="card-body">';
        echo '    <ul class="qtype-menu flex flex-wrap gap-x-2 gap-y-2 items-center justify-start w-full">';
        foreach ($questionTypeList as $i => $type) {
            /** @var Question $type */
            $type = new $type[1]();
            $img  = $type->getTypePicture();
            $expl = $type->getExplanation();

            echo '      <li class="flex items-center justify-center">';

            $icon = Display::url(
                Display::return_icon($img, $expl, null, ICON_SIZE_BIG),
                'admin.php?' . api_get_cidreq() . '&' . http_build_query([
                    'newQuestion' => 'yes',
                    'answerType'  => $i,
                    'exerciseId'  => $exerciseId,
                ]),
                ['title' => $expl, 'class' => 'block']
            );

            if (false === $objExercise->force_edit_exercise_in_lp && $objExercise->exercise_was_added_in_lp) {
                $img  = pathinfo($img);
                $img  = $img['filename'].'_na.'.$img['extension'];
                $icon = Display::return_icon($img, $expl, null, ICON_SIZE_BIG);
            }
            echo $icon;
            echo '      </li>';
        }

        echo '      <li class="flex items-center justify-center">';
        if ($objExercise->exercise_was_added_in_lp) {
            echo Display::getMdiIcon('database', 'ch-tool-icon-disabled', null, ICON_SIZE_BIG, get_lang('Recycle existing questions'));
        } else {
            $href = in_array($feedbackType, [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])
                ? 'question_pool.php?' . api_get_cidreq() . "&type=1&fromExercise={$exerciseId}"
                : 'question_pool.php?' . api_get_cidreq() . "&fromExercise={$exerciseId}";

            echo Display::url(
                Display::getMdiIcon('database', 'ch-tool-icon', null, ICON_SIZE_BIG, get_lang('Recycle existing questions')),
                $href,
                ['class' => 'block', 'title' => get_lang('Recycle existing questions')]
            );
        }
        echo '      </li>';

        echo '    </ul>';
        echo '  </div>';
        echo '</div>';
    }

    /**
     * @param string $name
     * @param int    $position
     *
     * @return CQuizQuestion|null
     */
    public static function saveQuestionOption(CQuizQuestion $question, $name, $position = 0)
    {
        $option = new CQuizQuestionOption();
        $option
            ->setQuestion($question)
            ->setTitle($name)
            ->setPosition($position)
        ;
        $em = Database::getManager();
        $em->persist($option);
        $em->flush();
    }

    /**
     * @param int $question_id
     * @param int $course_id
     */
    public static function deleteAllQuestionOptions($question_id, $course_id)
    {
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);
        Database::delete(
            $table,
            [
                'c_id = ? AND question_id = ?' => [
                    $course_id,
                    $question_id,
                ],
            ]
        );
    }

    /**
     * @param int $question_id
     *
     * @return array
     */
    public static function readQuestionOption($question_id)
    {
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);

        return Database::select(
            '*',
            $table,
            [
                'where' => [
                    'question_id = ?' => [
                        $question_id,
                    ],
                ],
                'order' => 'iid ASC',
            ]
        );
    }

    /**
     * Shows question title an description.
     *
     * @param int   $counter
     * @param array $score
     *
     * @return string HTML string with the header of the question (before the answers table)
     */
    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $counterLabel = '';
        if (!empty($counter)) {
            $counterLabel = (int) $counter;
        }

        $scoreLabel = get_lang('Wrong');
        if (in_array($exercise->results_disabled, [
            RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
            RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
        ])
        ) {
            $scoreLabel = get_lang('Wrong answer. The correct one was:');
        }

        $class = 'error';
        if (isset($score['pass']) && true == $score['pass']) {
            $scoreLabel = get_lang('Correct');

            if (in_array($exercise->results_disabled, [
                RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
            ])
            ) {
                $scoreLabel = get_lang('Correct answer');
            }
            $class = 'success';
        }

        switch ($this->type) {
            case FREE_ANSWER:
            case UPLOAD_ANSWER:
            case ORAL_EXPRESSION:
            case ANNOTATION:
                $score['revised'] = isset($score['revised']) ? $score['revised'] : false;
                if (true == $score['revised']) {
                    $scoreLabel = get_lang('Reviewed');
                    $class = '';
                } else {
                    $scoreLabel = get_lang('Not reviewed');
                    $class = 'warning';
                    if (isset($score['weight'])) {
                        $weight = float_format($score['weight'], 1);
                        $score['result'] = ' ? / '.$weight;
                    }
                    $model = ExerciseLib::getCourseScoreModel();
                    if (!empty($model)) {
                        $score['result'] = ' ? ';
                    }

                    $hide = ('true' === api_get_setting('exercise.hide_free_question_score'));
                    if (true === $hide) {
                        $score['result'] = '-';
                    }
                }

                break;
            case UNIQUE_ANSWER:
                if (in_array($exercise->results_disabled, [
                    RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
                    RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
                ])
                ) {
                    if (isset($score['user_answered'])) {
                        if (false === $score['user_answered']) {
                            $scoreLabel = get_lang('Unanswered');
                            $class = 'info';
                        }
                    }
                }

                break;
        }

        // display question category, if any
        $header = '';
        if ($exercise->display_category_name) {
            $header = TestCategory::returnCategoryAndTitle($this->id);
        }
        $show_media = '';
        if ($show_media) {
            $header .= $this->show_media_content();
        }

        $scoreCurrent = [
            'used' => isset($score['score']) ? $score['score'] : '',
            'missing' => isset($score['weight']) ? $score['weight'] : '',
        ];
        $header .= Display::page_subheader2($counterLabel.'. '.$this->question);

        $showRibbon = true;
        // dont display score for certainty degree questions
        if (MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY == $this->type) {
            $showRibbon = false;
            $ribbonResult = ('true' === api_get_setting('exercise.show_exercise_question_certainty_ribbon_result'));
            if (true === $ribbonResult) {
                $showRibbon = true;
            }
        }

        if ($showRibbon && isset($score['result'])) {
            if (in_array($exercise->results_disabled, [
                RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
            ])
            ) {
                $score['result'] = null;
            }
            $header .= $exercise->getQuestionRibbon($class, $scoreLabel, $score['result'], $scoreCurrent);
        }

        if (READING_COMPREHENSION != $this->type) {
            // Do not show the description (the text to read) if the question is of type READING_COMPREHENSION
            $header .= Display::div(
                $this->description,
                ['class' => 'question-answer-result__header-description']
            );
        } else {
            /** @var ReadingComprehension $this */
            if (true === $score['pass']) {
                $message = Display::div(
                    sprintf(
                        get_lang(
                            'Congratulations, you have reached and correctly understood, at a speed of %s words per minute, a text of a total %s words.'
                        ),
                        ReadingComprehension::$speeds[$this->level],
                        $this->getWordsCount()
                    )
                );
            } else {
                $message = Display::div(
                    sprintf(
                        get_lang(
                            'Sorry, it seems like a speed of %s words/minute was too fast for this text of %s words.'
                        ),
                        ReadingComprehension::$speeds[$this->level],
                        $this->getWordsCount()
                    )
                );
            }
            $header .= $message.'<br />';
        }

        if ($exercise->hideComment && in_array($this->type, [HOT_SPOT, HOT_SPOT_COMBINATION])) {
            $header .= Display::return_message(get_lang('Results only available online'));

            return $header;
        }

        if (isset($score['pass']) && false === $score['pass']) {
            if ($this->showFeedback($exercise)) {
                $header .= $this->returnFormatFeedback();
            }
        }

        return Display::div(
            $header,
            ['class' => 'question-answer-result__header']
        );
    }

    /**
     * @deprecated
     * Create a question from a set of parameters
     *
     * @param int    $question_name        Quiz ID
     * @param string $question_description Question name
     * @param int    $max_score            Maximum result for the question
     * @param int    $type                 Type of question (see constants at beginning of question.class.php)
     * @param int    $level                Question level/category
     * @param string $quiz_id
     */
    public function create_question(
        $quiz_id,
        $question_name,
        $question_description = '',
        $max_score = 0,
        $type = 1,
        $level = 1
    ) {
        $tbl_quiz_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $tbl_quiz_rel_question = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);

        $quiz_id = (int) $quiz_id;
        $max_score = (float) $max_score;
        $type = (int) $type;
        $level = (int) $level;

        // Get the max position
        $sql = "SELECT max(position) as max_position
                FROM $tbl_quiz_question q
                INNER JOIN $tbl_quiz_rel_question r
                ON
                    q.iid = r.question_id AND
                    quiz_id = $quiz_id";
        $rs_max = Database::query($sql);
        $row_max = Database::fetch_object($rs_max);
        $max_position = $row_max->max_position + 1;

        $params = [
            'question' => $question_name,
            'description' => $question_description,
            'ponderation' => $max_score,
            'position' => $max_position,
            'type' => $type,
            'level' => $level,
            'mandatory' => 0,
        ];
        $question_id = Database::insert($tbl_quiz_question, $params);

        if ($question_id) {
            // Get the max question_order
            $sql = "SELECT max(question_order) as max_order
                    FROM $tbl_quiz_rel_question
                    WHERE quiz_id = $quiz_id ";
            $rs_max_order = Database::query($sql);
            $row_max_order = Database::fetch_object($rs_max_order);
            $max_order = $row_max_order->max_order + 1;
            // Attach questions to quiz
            $sql = "INSERT INTO $tbl_quiz_rel_question (question_id, quiz_id, question_order)
                    VALUES($question_id, $quiz_id, $max_order)";
            Database::query($sql);
        }

        return $question_id;
    }

    /**
     * @return string
     */
    public function getTypePicture()
    {
        return $this->typePicture;
    }

    /**
     * @return string
     */
    public function getExplanation()
    {
        return get_lang($this->explanationLangVar);
    }

    /**
     * Get course medias.
     *
     * @param int $course_id
     *
     * @return array
     */
    public static function get_course_medias(
        $course_id,
        $start = 0,
        $limit = 100,
        $sidx = 'question',
        $sord = 'ASC',
        $where_condition = []
    ) {
        $table_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $default_where = [
            'c_id = ? AND parent_id = 0 AND type = ?' => [
                $course_id,
                MEDIA_QUESTION,
            ],
        ];

        return Database::select(
            '*',
            $table_question,
            [
                'limit' => " $start, $limit",
                'where' => $default_where,
                'order' => "$sidx $sord",
            ]
        );
    }

    /**
     * Get count course medias.
     *
     * @param int $course_id course id
     *
     * @return int
     */
    public static function get_count_course_medias($course_id)
    {
        $table_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $result = Database::select(
            'count(*) as count',
            $table_question,
            [
                'where' => [
                    'c_id = ? AND parent_id = 0 AND type = ?' => [
                        $course_id,
                        MEDIA_QUESTION,
                    ],
                ],
            ],
            'first'
        );

        if ($result && isset($result['count'])) {
            return $result['count'];
        }

        return 0;
    }

    /**
     * @param int $course_id
     *
     * @return array
     */
    public static function prepare_course_media_select(int $quizId): array
    {
        $tableQuestion     = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $tableRelQuestion  = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);

        $medias = Database::select(
            '*',
            "$tableQuestion q
         JOIN $tableRelQuestion rq ON rq.question_id = q.iid",
            [
                'where' => [
                    'rq.quiz_id = ? AND (q.parent_media_id IS NULL OR q.parent_media_id = 0) AND q.type = ?'
                    => [$quizId, MEDIA_QUESTION],
                ],
                'order' => 'question ASC',
            ]
        );

        $mediaList = [
            0 => get_lang('Not linked to media'),
        ];

        foreach ($medias as $media) {
            $mediaList[$media['question_id']] = empty($media['question'])
                ? get_lang('Untitled')
                : $media['question'];
        }

        return $mediaList;
    }

    /**
     * @return array
     */
    public static function get_default_levels()
    {
        return [
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
        ];
    }

    /**
     * @return string
     */
    public function show_media_content()
    {
        $html = '';
        if (0 != $this->parent_id) {
            $parent_question = self::read($this->parent_id);
            $html = $parent_question->show_media_content();
        } else {
            $html .= Display::page_subheader($this->selectTitle());
            $html .= $this->selectDescription();
        }

        return $html;
    }

    /**
     * Swap between unique and multiple type answers.
     *
     * @return UniqueAnswer|MultipleAnswer
     */
    public function swapSimpleAnswerTypes()
    {
        $oppositeAnswers = [
            UNIQUE_ANSWER => MULTIPLE_ANSWER,
            MULTIPLE_ANSWER => UNIQUE_ANSWER,
        ];
        $this->type = $oppositeAnswers[$this->type];
        Database::update(
            Database::get_course_table(TABLE_QUIZ_QUESTION),
            ['type' => $this->type],
            ['c_id = ? AND id = ?' => [$this->course['real_id'], $this->id]]
        );
        $answerClasses = [
            UNIQUE_ANSWER => 'UniqueAnswer',
            MULTIPLE_ANSWER => 'MultipleAnswer',
            MULTIPLE_ANSWER_DROPDOWN => 'MultipleAnswerDropdown',
            MULTIPLE_ANSWER_DROPDOWN_COMBINATION => 'MultipleAnswerDropdownCombination',
        ];
        $swappedAnswer = new $answerClasses[$this->type]();
        foreach ($this as $key => $value) {
            $swappedAnswer->$key = $value;
        }

        return $swappedAnswer;
    }

    /**
     * @param array $score
     *
     * @return bool
     */
    public function isQuestionWaitingReview($score)
    {
        $isReview = false;
        if (!empty($score)) {
            if (!empty($score['comments']) || $score['score'] > 0) {
                $isReview = true;
            }
        }

        return $isReview;
    }

    /**
     * @param string $value
     */
    public function setFeedback($value)
    {
        $this->feedback = $value;
    }

    /**
     * @param Exercise $exercise
     *
     * @return bool
     */
    public function showFeedback($exercise)
    {
        if (false === $exercise->hideComment) {
            return false;
        }

        return
            in_array($this->type, $this->questionTypeWithFeedback) &&
            EXERCISE_FEEDBACK_TYPE_EXAM != $exercise->getFeedbackType();
    }

    /**
     * @return string
     */
    public function returnFormatFeedback()
    {
        return '<br />'.Display::return_message($this->feedback, 'normal', false);
    }

    /**
     * Check if this question exists in another exercise.
     *
     * @throws \Doctrine\ORM\Query\QueryException
     *
     * @return bool
     */
    public function existsInAnotherExercise()
    {
        $count = $this->getCountExercise();

        return $count > 1;
    }

    /**
     * @throws \Doctrine\ORM\Query\QueryException
     *
     * @return int
     */
    public function getCountExercise()
    {
        $em = Database::getManager();

        $count = $em
            ->createQuery('
            SELECT COUNT(qq.iid)
            FROM ChamiloCourseBundle:CQuizRelQuestion qq
            WHERE IDENTITY(qq.question) = :id
        ')
            ->setParameters(['id' => (int) $this->id])
            ->getSingleScalarResult();

        return (int) $count;
    }

    /**
     * Check if this question exists in another exercise.
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getExerciseListWhereQuestionExists(): array
    {
        $em = Database::getManager();
        $questionId = (int) $this->id;

        // Doctrine does not allow selecting only a JOIN alias unless it is a root alias.
        // So we select CQuiz as root and join the relation entity with a WITH clause.
        $dql = '
        SELECT DISTINCT q
        FROM ChamiloCourseBundle:CQuiz q
        JOIN ChamiloCourseBundle:CQuizRelQuestion qq WITH qq.quiz = q
        WHERE IDENTITY(qq.question) = :id
    ';

        try {
            return $em->createQuery($dql)
                ->setParameter('id', $questionId)
                ->getResult();
        } catch (\Throwable $e) {
            // Fallback (best effort): use DBAL on the relation table and then load quizzes.
            // We keep this non-fatal to avoid breaking the admin listing.
        }

        try {
            $conn = Database::getConnection();

            // DBAL 2/3 schema manager compatibility
            $sm = method_exists($conn, 'createSchemaManager')
                ? $conn->createSchemaManager()
                : $conn->getSchemaManager();

            $tableNames = method_exists($sm, 'listTableNames') ? $sm->listTableNames() : [];

            $relCandidates = [
                'c_quiz_rel_question',
                'quiz_rel_question',
                'c_quiz_question_rel_exercise',
                'quiz_question_rel_exercise',
            ];

            $relTable = null;
            foreach ($relCandidates as $t) {
                if (\in_array($t, $tableNames, true)) {
                    $relTable = $t;
                    break;
                }
            }

            if (empty($relTable)) {
                return [];
            }

            // Detect the exercise/quiz id column name
            $columns = [];
            try {
                foreach ($sm->listTableColumns($relTable) as $colName => $col) {
                    $columns[] = $colName;
                }
            } catch (\Throwable $e) {
                $columns = [];
            }

            $qCol = \in_array('question_id', $columns, true) ? 'question_id' : 'question_id';
            $eCol = null;
            foreach (['quiz_id', 'exercise_id', 'exercice_id', 'quizid'] as $c) {
                if (\in_array($c, $columns, true)) {
                    $eCol = $c;
                    break;
                }
            }
            if (null === $eCol) {
                return [];
            }

            $sql = "SELECT DISTINCT $eCol AS quiz_id FROM $relTable WHERE $qCol = ?";

            // DBAL 3: fetchFirstColumn, DBAL 2: fetchAll
            if (method_exists($conn, 'fetchFirstColumn')) {
                $ids = $conn->fetchFirstColumn($sql, [$questionId]);
            } else {
                $rows = (array) $conn->fetchAll($sql, [$questionId]);
                $ids = [];
                foreach ($rows as $r) {
                    $ids[] = (int) ($r['quiz_id'] ?? (is_array($r) ? reset($r) : 0));
                }
            }

            $ids = array_values(array_filter(array_map('intval', (array) $ids), static fn ($v) => $v > 0));
            if (empty($ids)) {
                return [];
            }

            // Load quizzes by ids (iid is the usual PK in Chamilo entities)
            return $em->getRepository(\Chamilo\CourseBundle\Entity\CQuiz::class)->findBy(['iid' => $ids]);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return int
     */
    public function countAnswers()
    {
        $result = Database::select(
            'COUNT(1) AS c',
            Database::get_course_table(TABLE_QUIZ_ANSWER),
            ['where' => ['question_id = ?' => [$this->id]]],
            'first'
        );

        return (int) $result['c'];
    }

    /**
     * Add adaptive scenario selector fields (success/failure) to the question form.
     *
     * @param FormValidator $form
     * @param array         $questionList List of question IDs from the exercise.
     */
    protected function addAdaptiveScenarioFields(FormValidator $form, array $questionList): void
    {
        // Section header
        $form->addHtml('<h4 class="m-4">'.get_lang('Adaptive behavior (success/failure)').'</h4>');

        // Options for redirection behavior
        $questionListOptions = [
            ''      => get_lang('Select destination'),
            'repeat'=> get_lang('Repeat question'),
            '-1'    => get_lang('End of test'),
            'url'   => get_lang('Other (custom URL)'),
        ];

        // Append available questions to the dropdown
        foreach ($questionList as $index => $qid) {
            if (!is_numeric($qid)) {
                continue;
            }

            $q = self::read((int) $qid);
            if (!$q) {
                continue;
            }

            $questionListOptions[(string) $qid] = 'Q'.$index.': '.strip_tags($q->selectTitle());
        }

        // Success selector and optional URL field
        $form->addSelect(
            'scenario_success_selector',
            get_lang('On success'),
            $questionListOptions,
            ['id' => 'scenario_success_selector']
        );
        $form->addText(
            'scenario_success_url',
            get_lang('Custom URL'),
            false,
            [
                'class'       => 'form-control mb-5',
                'id'          => 'scenario_success_url',
                'placeholder' => '/main/lp/134',
            ]
        );

        // Failure selector and optional URL field
        $form->addSelect(
            'scenario_failure_selector',
            get_lang('On failure'),
            $questionListOptions,
            ['id' => 'scenario_failure_selector']
        );
        $form->addText(
            'scenario_failure_url',
            get_lang('Custom URL'),
            false,
            [
                'class'       => 'form-control mb-5',
                'id'          => 'scenario_failure_url',
                'placeholder' => '/main/lp/134',
            ]
        );

        // JavaScript to toggle custom URL fields when 'url' is selected
        $form->addHtml('
            <script>
                function toggleScenarioUrlFields() {
                    var successSelector = document.getElementById("scenario_success_selector");
                    var successUrlRow = document.getElementById("scenario_success_url").parentNode.parentNode;

                    var failureSelector = document.getElementById("scenario_failure_selector");
                    var failureUrlRow = document.getElementById("scenario_failure_url").parentNode.parentNode;

                    if (successSelector && successSelector.value === "url") {
                        successUrlRow.style.display = "table-row";
                    } else {
                        successUrlRow.style.display = "none";
                    }

                    if (failureSelector && failureSelector.value === "url") {
                        failureUrlRow.style.display = "table-row";
                    } else {
                        failureUrlRow.style.display = "none";
                    }
                }

                document.addEventListener("DOMContentLoaded", toggleScenarioUrlFields);
                document.getElementById("scenario_success_selector")
                    .addEventListener("change", toggleScenarioUrlFields);
                document.getElementById("scenario_failure_selector")
                    .addEventListener("change", toggleScenarioUrlFields);
            </script>
        ');
    }

    /**
     * Persist adaptive scenario (success/failure) configuration for this question.
     *
     * This stores the "destination" JSON in TABLE_QUIZ_TEST_QUESTION for the
     * current (question, exercise) pair. It is intended to be called from
     * processAnswersCreation() implementations.
     *
     * @param FormValidator $form
     * @param Exercise      $exercise
     */
    public function saveAdaptiveScenario(FormValidator $form, Exercise $exercise): void
    {
        // Global feature flag disabled → nothing to do.
        if ('true' !== api_get_setting('enable_quiz_scenario')) {
            return;
        }

        // We only support adaptive scenarios when feedback is "direct".
        if (EXERCISE_FEEDBACK_TYPE_DIRECT !== $exercise->getFeedbackType()) {
            return;
        }

        // This question type is not listed as "scenario capable".
        if (!$this->supportsAdaptiveScenario()) {
            return;
        }

        $successSelector = trim((string) $form->getSubmitValue('scenario_success_selector'));
        $successUrl      = trim((string) $form->getSubmitValue('scenario_success_url'));
        $failureSelector = trim((string) $form->getSubmitValue('scenario_failure_selector'));
        $failureUrl      = trim((string) $form->getSubmitValue('scenario_failure_url'));

        // Map "url" selector to the actual custom URL, keep other values as-is.
        $success = ('url' === $successSelector) ? $successUrl : $successSelector;
        $failure = ('url' === $failureSelector) ? $failureUrl : $failureSelector;

        // If nothing is configured at all, avoid touching the DB.
        if ('' === $success && '' === $failure) {
            return;
        }

        $destination = json_encode(
            [
                'success' => $success ?: '',
                'failure' => $failure ?: '',
            ],
            JSON_UNESCAPED_UNICODE
        );

        $table      = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $questionId = (int) $this->id;
        $exerciseId = (int) $exercise->id; // Consistent with existing code in UniqueAnswer

        if ($questionId <= 0 || $exerciseId <= 0) {
            // The (question, exercise) relation does not exist yet.
            return;
        }

        Database::update(
            $table,
            ['destination' => $destination],
            ['question_id = ? AND quiz_id = ?' => [$questionId, $exerciseId]]
        );
    }

    /**
     * Pre-fill adaptive scenario fields from the stored (question, exercise) relation.
     */
    protected function loadAdaptiveScenarioDefaults(FormValidator $form, Exercise $exercise): void
    {
        if ('true' !== api_get_setting('enable_quiz_scenario')) {
            return;
        }

        if (!$this->supportsAdaptiveScenario()) {
            return;
        }

        if (empty($this->id) || empty($exercise->id)) {
            return;
        }

        $table = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);

        $row = Database::select(
            'destination',
            $table,
            [
                'where' => [
                    'question_id = ? AND quiz_id = ?' => [
                        (int) $this->id,
                        (int) $exercise->id,
                    ],
                ],
                'limit' => 1,
            ],
            'first'
        );

        if (empty($row['destination'])) {
            return;
        }

        $json = json_decode((string) $row['destination'], true) ?: [];

        $defaults = [];

        if (!empty($json['success'])) {
            if (str_starts_with($json['success'], '/')) {
                $defaults['scenario_success_selector'] = 'url';
                $defaults['scenario_success_url']      = $json['success'];
            } else {
                $defaults['scenario_success_selector'] = $json['success'];
            }
        }

        if (!empty($json['failure'])) {
            if (str_starts_with($json['failure'], '/')) {
                $defaults['scenario_failure_selector'] = 'url';
                $defaults['scenario_failure_url']      = $json['failure'];
            } else {
                $defaults['scenario_failure_selector'] = $json['failure'];
            }
        }

        if (!empty($defaults)) {
            $form->setDefaults($defaults);
        }
    }

    /**
     * Check if this question type supports adaptive scenarios.
     */
    protected function supportsAdaptiveScenario(): bool
    {
        return in_array((int) $this->type, static::$adaptiveScenarioTypes, true);
    }
}
