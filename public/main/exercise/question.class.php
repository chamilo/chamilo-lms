<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
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
    public $question_table_class = 'table table-striped';
    public $questionTypeWithFeedback;
    public $extra;
    public $export = false;
    public $code;
    public static $questionTypes = [
        UNIQUE_ANSWER => ['unique_answer.class.php', 'UniqueAnswer'],
        MULTIPLE_ANSWER => ['multiple_answer.class.php', 'MultipleAnswer'],
        FILL_IN_BLANKS => ['fill_blanks.class.php', 'FillBlanks'],
        MATCHING => ['matching.class.php', 'Matching'],
        FREE_ANSWER => ['freeanswer.class.php', 'FreeAnswer'],
        ORAL_EXPRESSION => ['oral_expression.class.php', 'OralExpression'],
        HOT_SPOT => ['hotspot.class.php', 'HotSpot'],
        HOT_SPOT_DELINEATION => ['HotSpotDelineation.php', 'HotSpotDelineation'],
        MULTIPLE_ANSWER_COMBINATION => ['multiple_answer_combination.class.php', 'MultipleAnswerCombination'],
        UNIQUE_ANSWER_NO_OPTION => ['unique_answer_no_option.class.php', 'UniqueAnswerNoOption'],
        MULTIPLE_ANSWER_TRUE_FALSE => ['multiple_answer_true_false.class.php', 'MultipleAnswerTrueFalse'],
        MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY => [
            'MultipleAnswerTrueFalseDegreeCertainty.php',
            'MultipleAnswerTrueFalseDegreeCertainty',
        ],
        MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE => [
            'multiple_answer_combination_true_false.class.php',
            'MultipleAnswerCombinationTrueFalse',
        ],
        GLOBAL_MULTIPLE_ANSWER => ['global_multiple_answer.class.php', 'GlobalMultipleAnswer'],
        CALCULATED_ANSWER => ['calculated_answer.class.php', 'CalculatedAnswer'],
        UNIQUE_ANSWER_IMAGE => ['UniqueAnswerImage.php', 'UniqueAnswerImage'],
        DRAGGABLE => ['Draggable.php', 'Draggable'],
        MATCHING_DRAGGABLE => ['MatchingDraggable.php', 'MatchingDraggable'],
        //MEDIA_QUESTION => array('media_question.class.php' , 'MediaQuestion')
        ANNOTATION => ['Annotation.php', 'Annotation'],
        READING_COMPREHENSION => ['ReadingComprehension.php', 'ReadingComprehension'],
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
            MATCHING_DRAGGABLE,
            DRAGGABLE,
            FILL_IN_BLANKS,
            FREE_ANSWER,
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

                    if (api_get_configuration_value('allow_mandatory_question_in_category') &&
                        isset($categoryInfo['mandatory'])
                    ) {
                        $objQuestion->mandatory = (int) $categoryInfo['mandatory'];
                    }
                }

                if ($getExerciseList) {
                    $tblQuiz = Database::get_course_table(TABLE_QUIZ_TEST);
                    $sql = "SELECT DISTINCT q.quiz_id
                            FROM $TBL_EXERCISE_QUESTION q
                            INNER JOIN $tblQuiz e
                            ON e.iid = q.quiz_id
                            WHERE
                                q.question_id = $id AND
                                e.active >= 0";

                    $result = Database::query($sql);

                    // fills the array with the exercises which this question is in
                    if ($result) {
                        while ($obj = Database::fetch_object($result)) {
                            $objQuestion->exerciseList[] = $obj->quiz_id;
                        }
                    }
                }

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
        if (!api_get_configuration_value('save_titles_as_html')) {
            return $this->question;
        }

        return Display::div($this->question, ['style' => 'display: inline-block;']);
    }

    public function getTitleToDisplay(Exercise $exercise, int $itemNumber): string
    {
        $showQuestionTitleHtml = api_get_configuration_value('save_titles_as_html');
        $title = '';
        if (api_get_configuration_value('show_question_id')) {
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
     * @param int $categoryId
     * @param int $courseId
     *
     * @return bool
     *
     * @author Hubert Borderiou 12-10-2011
     */
    public function saveCategory($categoryId, $courseId = 0)
    {
        $courseId = empty($courseId) ? api_get_course_int_id() : (int) $courseId;

        if (empty($courseId)) {
            return false;
        }

        if ($categoryId <= 0) {
            $this->deleteCategory($courseId);
        } else {
            // update or add category for a question
            $table = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
            $categoryId = (int) $categoryId;
            $question_id = (int) $this->id;
            $sql = "SELECT count(*) AS nb FROM $table
                    WHERE
                        question_id = $question_id AND
                        c_id = ".$courseId;
            $res = Database::query($sql);
            $row = Database::fetch_array($res);
            $allowMandatory = api_get_configuration_value('allow_mandatory_question_in_category');
            if ($row['nb'] > 0) {
                $extraMandatoryCondition = '';
                if ($allowMandatory) {
                    $extraMandatoryCondition = ", mandatory = {$this->mandatory}";
                }
                $sql = "UPDATE $table
                        SET category_id = $categoryId
                        $extraMandatoryCondition
                        WHERE
                            question_id = $question_id ";
                Database::query($sql);
            } else {
                $sql = "INSERT INTO $table (question_id, category_id)
                        VALUES ($question_id, $categoryId)";
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

            return true;
        }
    }

    /**
     * @author hubert borderiou 12-10-2011
     *
     * @param int $courseId
     *                      delete any category entry for question id
     *                      delete the category for question
     *
     * @todo Check if deprecated
     *
     * @return bool
     */
    public function deleteCategory($courseId = 0)
    {
        $courseId = empty($courseId) ? api_get_course_int_id() : (int) $courseId;
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $questionId = (int) $this->id;
        if (empty($courseId) || empty($questionId)) {
            return false;
        }
        $sql = "DELETE FROM $table
                WHERE
                    question_id = $questionId";
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
                    ->setFeedback($this->feedback);

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
                if ('true' === api_get_setting('search_enabled')) {
                    $this->search_engine_edit($exerciseId);
                }
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

            $question = new CQuizQuestion();
            $question
                ->setQuestion($this->question)
                ->setDescription($this->description)
                ->setPonderation($this->weighting)
                ->setPosition($position)
                ->setType($this->type)
                ->setExtra($this->extra)
                ->setLevel((int) $this->level)
                ->setFeedback($this->feedback)
                ->setParent($courseEntity)
                ->addCourseLink(
                    $courseEntity,
                    api_get_session_entity(),
                    api_get_group_entity()
                )
            ;

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
                if (HOT_SPOT == $type || HOT_SPOT_ORDER == $type) {
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

                if ('true' === api_get_setting('search_enabled')) {
                    $this->search_engine_edit($exerciseId, true);
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
            if ('true' == api_get_setting('search_enabled') && extension_loaded('xapian')) {
                // disassociate question with this exercise
                $this->search_engine_edit($deleteFromEx, false, true);
            }
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

        $question = new CQuizQuestion();
        $question
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
                throw new Exception('implement copy of hotspot question');
            }
        }

        return $newQuestionId;
    }

    /**
     * @return string
     */
    public function get_question_type_name()
    {
        $key = self::$questionTypes[$this->type];

        return get_lang($key[1]);
    }

    /**
     * @param string $type
     */
    public static function get_question_type($type)
    {
        if (ORAL_EXPRESSION == $type && 'true' !== api_get_setting('enable_record_audio')) {
            return null;
        }

        return self::$questionTypes[$type];
    }

    /**
     * @return array
     */
    public static function getQuestionTypeList()
    {
        if ('true' !== api_get_setting('enable_record_audio')) {
            self::$questionTypes[ORAL_EXPRESSION] = null;
            unset(self::$questionTypes[ORAL_EXPRESSION]);
        }
        if ('true' !== api_get_setting('enable_quiz_scenario')) {
            self::$questionTypes[HOT_SPOT_DELINEATION] = null;
            unset(self::$questionTypes[HOT_SPOT_DELINEATION]);
        }

        return self::$questionTypes;
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
        $zoomOptions = api_get_configuration_value('quiz_image_zoom');
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

        // question name
        if (api_get_configuration_value('save_titles_as_html')) {
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

        // default content
        $isContent = isset($_REQUEST['isContent']) ? (int) $_REQUEST['isContent'] : null;

        // Question type
        $answerType = isset($_REQUEST['answerType']) ? (int) $_REQUEST['answerType'] : null;
        $form->addHidden('answerType', $answerType);

        // html editor
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
            $form->addElement('text', 'imageZoom', get_lang('ImageURL'));
            $form->addElement('text', 'imageWidth', get_lang('PixelWidth'));
            $form->addButton('btn_create_img', get_lang('AddToEditor'), 'plus', 'info', 'small', 'create_img_link');
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
            if (EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM == $exercise->getQuestionSelectionType() &&
                api_get_configuration_value('allow_mandatory_question_in_category')
            ) {
                $form->addCheckBox('mandatory', get_lang('IsMandatory'));
            }

            //global $text;
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
            //Medias
            //$course_medias = self::prepare_course_media_select(api_get_course_int_id());
            //$form->addSelect('parent_id', get_lang('Attach to media'), $course_medias);
        }

        $form->addElement('html', '</div>');

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

        if (null !== $exercise) {
            if ($exercise->questionFeedbackEnabled && $this->showFeedback($exercise)) {
                $form->addTextarea('feedback', get_lang('Feedback if not correct'));
            }
        }

        $extraField = new ExtraField('question');
        $extraField->addElements($form, $this->iid);

        // default values
        $defaults = [];
        $defaults['questionName'] = $this->question;
        $defaults['questionDescription'] = $this->description;
        $defaults['questionLevel'] = $this->level;
        $defaults['questionCategory'] = $this->category;
        $defaults['feedback'] = $this->feedback;
        $defaults['mandatory'] = $this->mandatory;

        // Came from he question pool
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
    public static function displayTypeMenu($objExercise)
    {
        if (empty($objExercise)) {
            return '';
        }

        $feedbackType = $objExercise->getFeedbackType();
        $exerciseId = $objExercise->id;

        // 1. by default we show all the question types
        $questionTypeList = self::getQuestionTypeList();

        if (!isset($feedbackType)) {
            $feedbackType = 0;
        }

        switch ($feedbackType) {
            case EXERCISE_FEEDBACK_TYPE_DIRECT:
                $questionTypeList = [
                    UNIQUE_ANSWER => self::$questionTypes[UNIQUE_ANSWER],
                    HOT_SPOT_DELINEATION => self::$questionTypes[HOT_SPOT_DELINEATION],
                ];

                break;
            case EXERCISE_FEEDBACK_TYPE_POPUP:
                $questionTypeList = [
                    UNIQUE_ANSWER => self::$questionTypes[UNIQUE_ANSWER],
                    MULTIPLE_ANSWER => self::$questionTypes[MULTIPLE_ANSWER],
                    DRAGGABLE => self::$questionTypes[DRAGGABLE],
                    HOT_SPOT_DELINEATION => self::$questionTypes[HOT_SPOT_DELINEATION],
                    CALCULATED_ANSWER => self::$questionTypes[CALCULATED_ANSWER],
                ];

                break;
            default:
                unset($questionTypeList[HOT_SPOT_DELINEATION]);

                break;
        }

        echo '<div class="card">';
        echo '<div class="card-body">';
        echo '<ul class="question_menu">';
        foreach ($questionTypeList as $i => $type) {
            /** @var Question $type */
            $type = new $type[1]();
            $img = $type->getTypePicture();
            $explanation = $type->getExplanation();
            echo '<li>';
            echo '<div class="icon-image">';
            $icon = '<a href="admin.php?'.api_get_cidreq().'&newQuestion=yes&answerType='.$i.'&exerciseId='.$exerciseId.'">'.
                Display::return_icon($img, $explanation, null, ICON_SIZE_BIG).'</a>';

            if (false === $objExercise->force_edit_exercise_in_lp) {
                if (true == $objExercise->exercise_was_added_in_lp) {
                    $img = pathinfo($img);
                    $img = $img['filename'].'_na.'.$img['extension'];
                    $icon = Display::return_icon($img, $explanation, null, ICON_SIZE_BIG);
                }
            }
            echo $icon;
            echo '</div>';
            echo '</li>';
        }

        echo '<li>';
        echo '<div class="icon_image_content">';
        if (true == $objExercise->exercise_was_added_in_lp) {
            echo Display::return_icon(
                'database_na.png',
                get_lang('Recycle existing questions'),
                null,
                ICON_SIZE_BIG
            );
        } else {
            if (in_array($feedbackType, [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])) {
                echo $url = '<a href="question_pool.php?'.api_get_cidreq()."&type=1&fromExercise=$exerciseId\">";
            } else {
                echo $url = '<a href="question_pool.php?'.api_get_cidreq().'&fromExercise='.$exerciseId.'">';
            }
            echo Display::return_icon(
                'database.png',
                get_lang('Recycle existing questions'),
                null,
                ICON_SIZE_BIG
            );
        }
        echo '</a>';
        echo '</div></li>';
        echo '</ul>';
        echo '</div>';
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
            ->setName($name)
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
            case ORAL_EXPRESSION:
            case ANNOTATION:
                $score['revised'] = isset($score['revised']) ? $score['revised'] : false;
                if (true == $score['revised']) {
                    $scoreLabel = get_lang('Revised');
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

                    $hide = api_get_configuration_value('hide_free_question_score');
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
            $ribbonResult = api_get_configuration_value('show_exercise_question_certainty_ribbon_result');
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
                ['class' => 'question_description']
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

        if ($exercise->hideComment && HOT_SPOT == $this->type) {
            $header .= Display::return_message(get_lang('ResultsOnlyAvailableOnline'));

            return $header;
        }

        if (isset($score['pass']) && false === $score['pass']) {
            if ($this->showFeedback($exercise)) {
                $header .= $this->returnFormatFeedback();
            }
        }

        return $header;
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
        $course_id = api_get_course_int_id();
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
                    q.id = r.question_id AND
                    quiz_id = $quiz_id AND
                    q.c_id = $course_id AND
                    r.c_id = $course_id";
        $rs_max = Database::query($sql);
        $row_max = Database::fetch_object($rs_max);
        $max_position = $row_max->max_position + 1;

        $params = [
            'c_id' => $course_id,
            'question' => $question_name,
            'description' => $question_description,
            'ponderation' => $max_score,
            'position' => $max_position,
            'type' => $type,
            'level' => $level,
        ];
        $question_id = Database::insert($tbl_quiz_question, $params);

        if ($question_id) {
            // Get the max question_order
            $sql = "SELECT max(question_order) as max_order
                    FROM $tbl_quiz_rel_question
                    WHERE c_id = $course_id AND quiz_id = $quiz_id ";
            $rs_max_order = Database::query($sql);
            $row_max_order = Database::fetch_object($rs_max_order);
            $max_order = $row_max_order->max_order + 1;
            // Attach questions to quiz
            $sql = "INSERT INTO $tbl_quiz_rel_question (c_id, question_id, quiz_id, question_order)
                    VALUES($course_id, $question_id, $quiz_id, $max_order)";
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
    public static function prepare_course_media_select($course_id)
    {
        $medias = self::get_course_medias($course_id);
        $media_list = [];
        $media_list[0] = get_lang('Not linked to media');

        if (!empty($medias)) {
            foreach ($medias as $media) {
                $media_list[$media['id']] = empty($media['question']) ? get_lang('Untitled') : $media['question'];
            }
        }

        return $media_list;
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
                SELECT COUNT(qq.iid) FROM ChamiloCourseBundle:CQuizRelQuestion qq
                WHERE qq.question = :id
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
    public function getExerciseListWhereQuestionExists()
    {
        $em = Database::getManager();

        return $em
            ->createQuery('
                SELECT e
                FROM ChamiloCourseBundle:CQuizRelQuestion qq
                JOIN ChamiloCourseBundle:CQuiz e
                WHERE e.iid = qq.exerciceId AND qq.questionId = :id
            ')
            ->setParameters(['id' => (int) $this->id])
            ->getResult();
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
}
