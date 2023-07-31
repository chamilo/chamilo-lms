<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuizAnswer;

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
        FILL_IN_BLANKS_COMBINATION => ['FillBlanksCombination.php', 'FillBlanksCombination'],
        MATCHING => ['matching.class.php', 'Matching'],
        MATCHING_COMBINATION => ['MatchingCombination.php', 'MatchingCombination'],
        FREE_ANSWER => ['freeanswer.class.php', 'FreeAnswer'],
        ORAL_EXPRESSION => ['oral_expression.class.php', 'OralExpression'],
        HOT_SPOT => ['hotspot.class.php', 'HotSpot'],
        HOT_SPOT_COMBINATION => ['HotSpotCombination.php', 'HotSpotCombination'],
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
        MATCHING_DRAGGABLE_COMBINATION => ['MatchingDraggableCombination.php', 'MatchingDraggableCombination'],
        //MEDIA_QUESTION => array('media_question.class.php' , 'MediaQuestion')
        ANNOTATION => ['Annotation.php', 'Annotation'],
        READING_COMPREHENSION => ['ReadingComprehension.php', 'ReadingComprehension'],
        UPLOAD_ANSWER => ['UploadAnswer.php', 'UploadAnswer'],
        MULTIPLE_ANSWER_DROPDOWN => ['MultipleAnswerDropdown.php', 'MultipleAnswerDropdown'],
        MULTIPLE_ANSWER_DROPDOWN_COMBINATION => ['MultipleAnswerDropdownCombination.php', 'MultipleAnswerDropdownCombination'],
    ];

    /**
     * constructor of the class.
     *
     * @author Olivier Brouckaert
     */
    public function __construct()
    {
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
            ORAL_EXPRESSION,
            CALCULATED_ANSWER,
            ANNOTATION,
            UPLOAD_ANSWER,
        ];
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
                    $sql = "SELECT DISTINCT q.exercice_id
                            FROM $TBL_EXERCISE_QUESTION q
                            INNER JOIN $tblQuiz e
                            ON e.iid = q.exercice_id
                            WHERE
                                q.c_id = $course_id AND
                                q.question_id = $id AND
                                e.active >= 0";

                    $result = Database::query($sql);

                    // fills the array with the exercises which this question is in
                    if ($result) {
                        while ($obj = Database::fetch_object($result)) {
                            $objQuestion->exerciseList[] = $obj->exercice_id;
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
     * returns the question ID.
     *
     * @author Olivier Brouckaert
     *
     * @return int - question ID
     */
    public function selectId()
    {
        return $this->iid;
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

    /**
     * @param int $itemNumber The numerical counter of the question
     * @param int $exerciseId The iid of the corresponding c_quiz, for specific rules applied to the title
     */
    public function getTitleToDisplay(int $itemNumber, int $exerciseId): string
    {
        $showQuestionTitleHtml = api_get_configuration_value('save_titles_as_html');
        $title = '';
        if (api_get_configuration_value('show_question_id')) {
            $title .= '<h4>#'.$this->course['code'].'-'.$this->iid.'</h4>';
        }

        $title .= $showQuestionTitleHtml ? '' : '<strong>';
        $checkIfShowNumberQuestion = $this->getShowHideConfiguration($exerciseId);
        if ($checkIfShowNumberQuestion != 1) {
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
     * Gets the respective value to show or hide the number of a question in the exam.
     * If the field does not exist in the database, it will return 0.
     *
     * @param int $exerciseId The iid of the corresponding c_quiz, to avoid mix-ups when the question is used in more than one exercise
     *
     * @return int 1 if we should hide the numbering for the current question
     */
    public function getShowHideConfiguration(int $exerciseId): int
    {
        $tblQuiz = Database::get_course_table(TABLE_QUIZ_TEST);
        $tblQuizRelQuestion = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $showHideConfiguration = api_get_configuration_value('quiz_hide_question_number');
        if (!$showHideConfiguration) {
            return 0;
        }
        // Check if the field exist
        $checkFieldSql = "SHOW COLUMNS FROM $tblQuiz WHERE Field = 'hide_question_number'";
        $res = Database::query($checkFieldSql);
        $result = Database::store_result($res);
        if (count($result) != 0) {
            $sql = "
                SELECT
                    q.hide_question_number AS hide_num
                FROM
                    $tblQuiz as q
                INNER JOIN  $tblQuizRelQuestion AS qrq ON qrq.exercice_id = q.iid
                WHERE qrq.question_id = ".$this->iid."
                AND qrq.exercice_id = ".$exerciseId;
            $res = Database::query($sql);
            $result = Database::store_result($res);
            if (is_array($result) &&
                isset($result[0]) &&
                isset($result[0]['hide_num'])
            ) {
                return (int) $result[0]['hide_num'];
            }
        }

        return 0;
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
     * returns the question position.
     *
     * @author Olivier Brouckaert
     *
     * @return int - question position
     */
    public function selectPosition()
    {
        return $this->position;
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
     * returns the picture name.
     *
     * @author Olivier Brouckaert
     *
     * @return string - picture name
     */
    public function selectPicture()
    {
        return $this->picture;
    }

    /**
     * @return string
     */
    public function selectPicturePath()
    {
        if (!empty($this->picture)) {
            return api_get_path(WEB_COURSE_PATH).$this->course['directory'].'/document/images/'.$this->getPictureFilename();
        }

        return '';
    }

    /**
     * @return int|string
     */
    public function getPictureId()
    {
        // for backward compatibility
        // when in field picture we had the filename not the document id
        if (preg_match("/quiz-.*/", $this->picture)) {
            return DocumentManager::get_document_id(
                $this->course,
                $this->selectPicturePath(),
                api_get_session_id()
            );
        }

        return $this->picture;
    }

    /**
     * @param int $courseId
     * @param int $sessionId
     *
     * @return string
     */
    public function getPictureFilename($courseId = 0, $sessionId = 0)
    {
        $courseId = empty($courseId) ? api_get_course_int_id() : (int) $courseId;
        $sessionId = empty($sessionId) ? api_get_session_id() : (int) $sessionId;

        if (empty($courseId)) {
            return '';
        }
        // for backward compatibility
        // when in field picture we had the filename not the document id
        if (preg_match("/quiz-.*/", $this->picture)) {
            return $this->picture;
        }

        $pictureId = $this->getPictureId();
        $courseInfo = $this->course;
        $documentInfo = DocumentManager::get_document_data_by_id(
            $pictureId,
            $courseInfo['code'],
            false,
            $sessionId
        );
        $documentFilename = '';
        if ($documentInfo) {
            // document in document/images folder
            $documentFilename = pathinfo(
                $documentInfo['path'],
                PATHINFO_BASENAME
            );
        }

        return $documentFilename;
    }

    /**
     * returns the array with the exercise ID list.
     *
     * @author Olivier Brouckaert
     *
     * @return array - list of exercise ID which the question is in
     */
    public function selectExerciseList()
    {
        return $this->exerciseList;
    }

    /**
     * returns the number of exercises which this question is in.
     *
     * @author Olivier Brouckaert
     *
     * @return int - number of exercises
     */
    public function selectNbrExercises()
    {
        return count($this->exerciseList);
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
     * @param int $id
     */
    public function updateParentId($id)
    {
        $this->parent_id = (int) $id;
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
     * @param int $value
     *
     * @author Hubert Borderiou 12-10-2011
     */
    public function updateScoreAlwaysPositive($value)
    {
        $this->scoreAlwaysPositive = $value;
    }

    /**
     * @param int $value
     *
     * @author Hubert Borderiou 12-10-2011
     */
    public function updateUncheckedMayScore($value)
    {
        $this->uncheckedMayScore = $value;
    }

    /**
     * Save category of a question.
     *
     * A question can have n categories if category is empty,
     * then question has no category then delete the category entry
     *
     * @param array $category_list
     *
     * @author Julio Montoya - Adding multiple cat support
     */
    public function saveCategories($category_list)
    {
        if (!empty($category_list)) {
            $this->deleteCategory();
            $table = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);

            // update or add category for a question
            foreach ($category_list as $category_id) {
                $category_id = (int) $category_id;
                $question_id = (int) $this->iid;
                $sql = "SELECT count(*) AS nb
                        FROM $table
                        WHERE
                            category_id = $category_id
                            AND question_id = $question_id
                            AND c_id=".api_get_course_int_id();
                $res = Database::query($sql);
                $row = Database::fetch_array($res);
                if ($row['nb'] > 0) {
                    // DO nothing
                } else {
                    $sql = "INSERT INTO $table (c_id, question_id, category_id)
                            VALUES (".api_get_course_int_id().", $question_id, $category_id)";
                    Database::query($sql);
                }
            }
        }
    }

    /**
     * In this version, a question can only have 1 category.
     * If category is 0, then question has no category then delete the category entry.
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
            $question_id = (int) $this->iid;
            $sql = "SELECT count(*) AS nb FROM $table
                    WHERE
                        question_id = $question_id AND
                        c_id = $courseId";
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
                            question_id = $question_id AND
                            c_id = $courseId";
                Database::query($sql);
            } else {
                $sql = "INSERT INTO $table (c_id, question_id, category_id)
                        VALUES ($courseId, $question_id, $categoryId)";
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
     * @return bool
     */
    public function deleteCategory($courseId = 0)
    {
        $courseId = empty($courseId) ? api_get_course_int_id() : (int) $courseId;
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $questionId = (int) $this->iid;
        if (empty($courseId) || empty($questionId)) {
            return false;
        }
        $sql = "DELETE FROM $table
                WHERE
                    question_id = $questionId AND
                    c_id = $courseId";
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

        // if we really change the type
        if ($type != $this->type) {
            // if we don't change from "unique answer" to "multiple answers" (or conversely)
            if (!in_array($this->type, [UNIQUE_ANSWER, MULTIPLE_ANSWER]) ||
                !in_array($type, [UNIQUE_ANSWER, MULTIPLE_ANSWER])
            ) {
                // removes old answers
                $sql = "DELETE FROM $table
                        WHERE question_id = ".intval($this->iid);
                Database::query($sql);
            }

            $this->type = $type;
        }
    }

    /**
     * Get default hot spot folder in documents.
     *
     * @param array $courseInfo
     *
     * @return string
     */
    public function getHotSpotFolderInCourse($courseInfo = [])
    {
        $courseInfo = empty($courseInfo) ? $this->course : $courseInfo;

        if (empty($courseInfo) || empty($courseInfo['directory'])) {
            // Stop everything if course is not set.
            api_not_allowed();
        }

        $pictureAbsolutePath = api_get_path(SYS_COURSE_PATH).$courseInfo['directory'].'/document/images/';
        $picturePath = basename($pictureAbsolutePath);

        if (!is_dir($picturePath)) {
            create_unexisting_directory(
                $courseInfo,
                api_get_user_id(),
                0,
                0,
                0,
                dirname($pictureAbsolutePath),
                '/'.$picturePath,
                $picturePath,
                '',
                false,
                false
            );
        }

        return $pictureAbsolutePath;
    }

    /**
     * adds a picture to the question.
     *
     * @param string $picture - temporary path of the picture to upload
     *
     * @return bool - true if uploaded, otherwise false
     *
     * @author Olivier Brouckaert
     */
    public function uploadPicture($picture)
    {
        $picturePath = $this->getHotSpotFolderInCourse();

        // if the question has got an ID
        if ($this->iid) {
            $pictureFilename = self::generatePictureName();
            $img = new Image($picture);
            $img->send_image($picturePath.'/'.$pictureFilename, -1, 'jpg');
            $document_id = add_document(
                $this->course,
                '/images/'.$pictureFilename,
                'file',
                filesize($picturePath.'/'.$pictureFilename),
                $pictureFilename
            );

            if ($document_id) {
                $this->picture = $document_id;

                if (!file_exists($picturePath.'/'.$pictureFilename)) {
                    return false;
                }

                api_item_property_update(
                    $this->course,
                    TOOL_DOCUMENT,
                    $document_id,
                    'DocumentAdded',
                    api_get_user_id()
                );

                $this->resizePicture('width', 800);

                return true;
            }
        }

        return false;
    }

    /**
     * return the name for image use in hotspot question
     * to be unique, name is quiz-[utc unix timestamp].jpg.
     *
     * @param string $prefix
     * @param string $extension
     *
     * @return string
     */
    public function generatePictureName($prefix = 'quiz-', $extension = 'jpg')
    {
        // image name is quiz-xxx.jpg in folder images/
        $utcTime = time();

        return $prefix.$utcTime.'.'.$extension;
    }

    /**
     * deletes the picture.
     *
     * @author Olivier Brouckaert
     *
     * @return bool - true if removed, otherwise false
     */
    public function removePicture()
    {
        $picturePath = $this->getHotSpotFolderInCourse();

        // if the question has got an ID and if the picture exists
        if ($this->iid) {
            $picture = $this->picture;
            $this->picture = '';

            return @unlink($picturePath.'/'.$picture) ? true : false;
        }

        return false;
    }

    /**
     * Exports a picture to another question.
     *
     * @author Olivier Brouckaert
     *
     * @param int   $questionId - ID of the target question
     * @param array $courseInfo destination course info
     *
     * @return bool - true if copied, otherwise false
     */
    public function exportPicture(int $questionId, array $courseInfo)
    {
        if (empty($questionId) || empty($courseInfo)) {
            return false;
        }

        $course_id = $courseInfo['real_id'];
        $destination_path = $this->getHotSpotFolderInCourse($courseInfo);

        if (empty($destination_path)) {
            return false;
        }

        $source_path = $this->getHotSpotFolderInCourse();

        // if the question has got an ID and if the picture exists
        if (!$this->iid || empty($this->picture)) {
            return false;
        }

        $sourcePictureName = $this->getPictureFilename($course_id);
        $picture = $this->generatePictureName();
        $result = false;
        if (file_exists($source_path.'/'.$sourcePictureName)) {
            // for backward compatibility
            $result = copy(
                $source_path.'/'.$sourcePictureName,
                $destination_path.'/'.$picture
            );
        } else {
            $imageInfo = DocumentManager::get_document_data_by_id(
                $this->picture,
                $courseInfo['code']
            );
            if (file_exists($imageInfo['absolute_path'])) {
                $result = @copy(
                    $imageInfo['absolute_path'],
                    $destination_path.'/'.$picture
                );
            }
        }

        // If copy was correct then add to the database
        if (!$result) {
            return false;
        }

        $table = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $sql = "UPDATE $table SET
                picture = '".Database::escape_string($picture)."'
                WHERE iid = ".intval($questionId);
        Database::query($sql);

        $documentId = add_document(
            $courseInfo,
            '/images/'.$picture,
            'file',
            filesize($destination_path.'/'.$picture),
            $picture
        );

        if (!$documentId) {
            return false;
        }

        return api_item_property_update(
            $courseInfo,
            TOOL_DOCUMENT,
            $documentId,
            'DocumentAdded',
            api_get_user_id()
        );
    }

    /**
     * Saves the picture coming from POST into a temporary file
     * Temporary pictures are used when we don't want to save a picture right after a form submission.
     * For example, if we first show a confirmation box.
     *
     * @author Olivier Brouckaert
     *
     * @param string $picture     - temporary path of the picture to move
     * @param string $pictureName - Name of the picture
     */
    public function setTmpPicture($picture, $pictureName)
    {
        $picturePath = $this->getHotSpotFolderInCourse();
        $pictureName = explode('.', $pictureName);
        $Extension = $pictureName[sizeof($pictureName) - 1];

        // saves the picture into a temporary file
        @move_uploaded_file($picture, $picturePath.'/tmp.'.$Extension);
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
     * Updates the question in the database.
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
        $exerciseId = $exercise->iid;

        $id = $this->iid;
        $question = $this->question;
        $description = $this->description;
        $weighting = $this->weighting;
        $position = $this->position;
        $type = $this->type;
        $picture = $this->picture;
        $level = $this->level;
        $extra = $this->extra;
        $c_id = $this->course['real_id'];
        $categoryId = $this->category;

        // question already exists
        if (!empty($id)) {
            $params = [
                'question' => $question,
                'description' => $description,
                'ponderation' => $weighting,
                'position' => $position,
                'type' => $type,
                'picture' => $picture,
                'extra' => $extra,
                'level' => $level,
            ];
            if ($exercise->questionFeedbackEnabled) {
                $params['feedback'] = $this->feedback;
            }

            Database::update(
                $TBL_QUESTIONS,
                $params,
                ['iid = ?' => [$id]]
            );

            Event::addEvent(
                LOG_QUESTION_UPDATED,
                LOG_QUESTION_ID,
                $this->iid
            );
            $this->saveCategory($categoryId);

            if (!empty($exerciseId)) {
                api_item_property_update(
                    $this->course,
                    TOOL_QUIZ,
                    $id,
                    'QuizQuestionUpdated',
                    api_get_user_id()
                );
            }
            if (api_get_setting('search_enabled') === 'true') {
                $this->search_engine_edit($exerciseId);
            }
        } else {
            // Creates a new question.
            $sql = "SELECT max(position)
                    FROM $TBL_QUESTIONS as question,
                    $TBL_EXERCISE_QUESTION as test_question
                    WHERE
                        question.iid = test_question.question_id AND
                        test_question.exercice_id = ".$exerciseId." AND
                        test_question.c_id = $c_id ";
            $result = Database::query($sql);
            $current_position = Database::result($result, 0, 0);
            $this->updatePosition($current_position + 1);
            $position = $this->position;

            $params = [
                'c_id' => $c_id,
                'question' => $question,
                'description' => $description,
                'ponderation' => $weighting,
                'position' => $position,
                'type' => $type,
                'picture' => $picture,
                'extra' => $extra,
                'level' => $level,
            ];

            if ($exercise->questionFeedbackEnabled) {
                $params['feedback'] = $this->feedback;
            }
            $this->iid = Database::insert($TBL_QUESTIONS, $params);

            if ($this->iid) {
                Event::addEvent(
                    LOG_QUESTION_CREATED,
                    LOG_QUESTION_ID,
                    $this->iid
                );

                api_item_property_update(
                    $this->course,
                    TOOL_QUIZ,
                    $this->iid,
                    'QuizQuestionAdded',
                    api_get_user_id()
                );

                // If hotspot, create first answer
                if (in_array($type, [HOT_SPOT, HOT_SPOT_COMBINATION, HOT_SPOT_ORDER])) {
                    $quizAnswer = new CQuizAnswer();
                    $quizAnswer
                        ->setCId($c_id)
                        ->setQuestionId($this->iid)
                        ->setAnswer('')
                        ->setPonderation(10)
                        ->setPosition(1)
                        ->setHotspotCoordinates('0;0|0|0')
                        ->setHotspotType('square');

                    $em->persist($quizAnswer);
                    $em->flush();

                    $id = $quizAnswer->getId();

                    if ($id) {
                        $quizAnswer
                            ->setId($id)
                            ->setIdAuto($id);

                        $em->merge($quizAnswer);
                        $em->flush();
                    }
                }

                if ($type == HOT_SPOT_DELINEATION) {
                    $quizAnswer = new CQuizAnswer();
                    $quizAnswer
                        ->setCId($c_id)
                        ->setQuestionId($this->iid)
                        ->setAnswer('')
                        ->setPonderation(10)
                        ->setPosition(1)
                        ->setHotspotCoordinates('0;0|0|0')
                        ->setHotspotType('delineation');

                    $em->persist($quizAnswer);
                    $em->flush();

                    $id = $quizAnswer->getId();

                    if ($id) {
                        $quizAnswer
                            ->setId($id)
                            ->setIdAuto($id);

                        $em->merge($quizAnswer);
                        $em->flush();
                    }
                }

                if (api_get_setting('search_enabled') === 'true') {
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
        if (!empty($exerciseId) && api_get_setting('search_enabled') == 'true' &&
            extension_loaded('xapian')
        ) {
            $course_id = api_get_course_id();
            // get search_did
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            if ($addQs || $rmQs) {
                //there's only one row per question on normal db and one document per question on search engine db
                $sql = 'SELECT * FROM %s
                    WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_second_level=%s LIMIT 1';
                $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->iid);
            } else {
                $sql = 'SELECT * FROM %s
                    WHERE course_code=\'%s\' AND tool_id=\'%s\'
                    AND ref_id_high_level=%s AND ref_id_second_level=%s LIMIT 1';
                $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $exerciseId, $this->iid);
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
                if ($se_doc !== false) {
                    if (($se_doc_data = $di->get_document_data($se_doc)) !== false) {
                        $se_doc_data = UnserializeApi::unserialize(
                            'not_allowed_classes',
                            $se_doc_data
                        );
                        if (isset($se_doc_data[SE_DATA]['type']) &&
                            $se_doc_data[SE_DATA]['type'] == SE_DOCTYPE_EXERCISE_QUESTION
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
                    while (($key = array_search($exerciseId, $question_exercises)) !== false) {
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
                        'question_id' => (int) $this->iid,
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
                        $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->iid);
                    } else {
                        $sql = "DELETE FROM %S
                            WHERE
                                course_code = '%s'
                                AND tool_id = '%s'
                                AND tool_id = '%s'
                                AND ref_id_high_level = '%s'
                                AND ref_id_second_level = '%s'";
                        $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $exerciseId, $this->iid);
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
                                $this->iid,
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
                        $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $exerciseId, $this->iid, $did);
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
        $id = (int) $this->iid;
        $exerciseId = (int) $exerciseId;

        // checks if the exercise ID is not in the list
        if (!empty($exerciseId) && !in_array($exerciseId, $this->exerciseList)) {
            $this->exerciseList[] = $exerciseId;
            $courseId = isset($this->course['real_id']) ? $this->course['real_id'] : 0;
            $newExercise = new Exercise($courseId);
            $newExercise->read($exerciseId, false);
            $count = $newExercise->getQuestionCount();
            $count++;
            $sql = "INSERT INTO $exerciseRelQuestionTable (c_id, question_id, exercice_id, question_order)
                    VALUES ({$this->course['real_id']}, $id, $exerciseId, $count)";
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
     * @param int $courseId   The ID of the course, to avoid deleting re-used questions
     *
     * @return bool - true if removed, otherwise false
     */
    public function removeFromList(int $exerciseId, int $courseId = 0): bool
    {
        $table = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $id = (int) $this->iid;
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
                        c_id = $courseId AND
                        question_id = $id AND
                        exercice_id = $exerciseId";
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $row = Database::fetch_array($res);
                if (!empty($row['question_order'])) {
                    $sql = "UPDATE $table
                            SET question_order = question_order-1
                            WHERE
                                c_id = $courseId AND
                                exercice_id = $exerciseId AND
                                question_order > ".$row['question_order'];
                    Database::query($sql);
                }
            }

            $sql = "DELETE FROM $table
                    WHERE
                        c_id = $courseId AND
                        question_id = $id AND
                        exercice_id = $exerciseId";
            Database::query($sql);

            return true;
        }
    }

    /**
     * Deletes a question from the database
     * The parameter tells if the question is removed from all exercises (value = 0),
     * or just from one exercise (value = exercise ID).
     *
     * @author Olivier Brouckaert
     *
     * @param int  $deleteFromEx  Exercise ID if the question is only to be removed from one exercise
     * @param bool $deletePicture Allow for special cases where the picture would be better left alone
     */
    public function delete(int $deleteFromEx = 0, bool $deletePicture = true): bool
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

        $id = (int) $this->iid;

        // if the question must be removed from all exercises
        if (!$deleteFromEx) {
            $courseFilter = " AND c_id = $courseId";

            if (true === api_get_configuration_value('quiz_question_allow_inter_course_linking')) {
                $courseFilter = '';
            }

            //update the question_order of each question to avoid inconsistencies
            $sql = "SELECT exercice_id, question_order
                    FROM $TBL_EXERCISE_QUESTION
                    WHERE question_id = $id
                        $courseFilter";

            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                while ($row = Database::fetch_array($res)) {
                    if (!empty($row['question_order'])) {
                        $sql = "UPDATE $TBL_EXERCISE_QUESTION
                                SET question_order = question_order-1
                                WHERE
                                    exercice_id = ".intval($row['exercice_id'])." AND
                                    question_order > ".$row['question_order']
                                    .$courseFilter;
                        Database::query($sql);
                    }
                }
            }

            $sql = "DELETE FROM $TBL_EXERCISE_QUESTION
                    WHERE question_id = $id
                        $courseFilter";
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
                        question_id = $id
                        $courseFilter";
            Database::query($sql);

            // Add extra fields.
            $extraField = new ExtraFieldValue('question');
            $extraField->deleteValuesByItem($this->iid);

            $sql = "DELETE FROM $TBL_QUESTIONS
                    WHERE iid = $id";
            Database::query($sql);

            api_item_property_update(
                $this->course,
                TOOL_QUIZ,
                $id,
                'QuizQuestionDeleted',
                api_get_user_id()
            );
            Event::addEvent(
                LOG_QUESTION_DELETED,
                LOG_QUESTION_ID,
                $this->iid
            );
            if ($deletePicture) {
                $this->removePicture();
            }
        } else {
            // just removes the exercise from the list
            $this->removeFromList($deleteFromEx, $courseId);
            if (api_get_setting('search_enabled') === 'true' && extension_loaded('xapian')) {
                // disassociate question with this exercise
                $this->search_engine_edit($deleteFromEx, false, true);
            }

            api_item_property_update(
                $this->course,
                TOOL_QUIZ,
                $id,
                'QuizQuestionDeleted',
                api_get_user_id()
            );
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
     * @return false|int ID of the new question
     */
    public function duplicate($courseInfo = [])
    {
        $courseInfo = empty($courseInfo) ? $this->course : $courseInfo;

        if (empty($courseInfo)) {
            return false;
        }
        $questionTable = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $TBL_QUESTION_OPTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);

        $question = $this->question;
        $description = $this->description;
        $weighting = $this->weighting;
        $position = $this->position;
        $type = $this->type;
        $level = (int) $this->level;
        $extra = $this->extra;

        // Using the same method used in the course copy to transform URLs
        if ($this->course['id'] != $courseInfo['id']) {
            $description = DocumentManager::replaceUrlWithNewCourseCode(
                $description,
                $this->course['code'],
                $courseInfo['id']
            );
            $question = DocumentManager::replaceUrlWithNewCourseCode(
                $question,
                $this->course['code'],
                $courseInfo['id']
            );
        }

        $course_id = $courseInfo['real_id'];

        // Read the source options
        $options = self::readQuestionOption($this->iid, $this->course['real_id']);

        // Inserting in the new course db / or the same course db
        $params = [
            'c_id' => $course_id,
            'question' => $question,
            'description' => $description,
            'ponderation' => $weighting,
            'position' => $position,
            'type' => $type,
            'level' => $level,
            'extra' => $extra,
        ];
        $newQuestionId = Database::insert($questionTable, $params);

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
                    unset($item['id']);
                    $id = Database::insert($TBL_QUESTION_OPTIONS, $item);
                }
            }

            // Duplicates the picture of the hotspot
            $this->exportPicture($newQuestionId, $courseInfo);
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
        if ($type == ORAL_EXPRESSION && api_get_setting('enable_record_audio') !== 'true') {
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
        if (!is_null($type)) {
            list($fileName, $className) = self::get_question_type($type);
            if (!empty($fileName)) {
                include_once $fileName;
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
        echo '<style>
                .media { display:none;}
            </style>';

        $zoomOptions = api_get_configuration_value('quiz_image_zoom');
        if (isset($zoomOptions['options'])) {
            $finderFolder = api_get_path(WEB_PATH).'vendor/studio-42/elfinder/';
            echo '<!-- elFinder CSS (REQUIRED) -->';
            echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$finderFolder.'css/elfinder.full.css">';
            echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$finderFolder.'css/theme.css">';

            echo '<!-- elFinder JS (REQUIRED) -->';
            echo '<script type="text/javascript" src="'.$finderFolder.'js/elfinder.full.js"></script>';

            echo '<!-- elFinder translation (OPTIONAL) -->';
            $language = 'en';
            $platformLanguage = api_get_interface_language();
            $iso = api_get_language_isocode($platformLanguage);
            $filePart = "vendor/studio-42/elfinder/js/i18n/elfinder.$iso.js";
            $file = api_get_path(SYS_PATH).$filePart;
            $includeFile = '';
            if (file_exists($file)) {
                $includeFile = '<script type="text/javascript" src="'.api_get_path(WEB_PATH).$filePart.'"></script>';
                $language = $iso;
            }
            echo $includeFile;

            echo '<script type="text/javascript" charset="utf-8">
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
                $editorConfig,
                true
            );
        } else {
            $form->addElement('text', 'questionName', get_lang('Question'));
        }

        $form->addRule('questionName', get_lang('GiveQuestion'), 'required');

        // default content
        $isContent = isset($_REQUEST['isContent']) ? (int) $_REQUEST['isContent'] : null;

        // Question type
        $answerType = isset($_REQUEST['answerType']) ? (int) $_REQUEST['answerType'] : null;
        $form->addElement('hidden', 'answerType', $answerType);

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
            get_lang('QuestionDescription'),
            false,
            false,
            $editorConfig
        );

        if ($this->type != MEDIA_QUESTION) {
            // Advanced parameters.
            $form->addElement(
                'select',
                'questionLevel',
                get_lang('Difficulty'),
                self::get_default_levels()
            );

            // Categories.
            $form->addElement(
                'select',
                'questionCategory',
                get_lang('Category'),
                TestCategory::getCategoriesIdAndName()
            );

            if (EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM == $exercise->getQuestionSelectionType() &&
                api_get_configuration_value('allow_mandatory_question_in_category')
            ) {
                $form->addCheckBox(
                    'mandatory',
                    get_lang('IsMandatory')
                );
            }

            global $text;
            switch ($this->type) {
                case UNIQUE_ANSWER:
                case MULTIPLE_ANSWER_DROPDOWN:
                case MULTIPLE_ANSWER_DROPDOWN_COMBINATION:
                    $buttonGroup = [];
                    $buttonGroup[] = $form->addButtonSave(
                        $text,
                        'submitQuestion',
                        true
                    );
                    $buttonGroup[] = $form->addButton(
                        'convertAnswer',
                        get_lang('ConvertToMultipleAnswer'),
                        'check-square-o',
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
                        get_lang('ConvertToUniqueAnswer'),
                        'dot-circle-o',
                        'default',
                        null,
                        null,
                        null,
                        true
                    );
                    $buttonGroup[] = $form->addButton(
                        'convertAnswerAlt',
                        get_lang('ConvertToMultipleAnswerDropdown'),
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
            //$form->addElement('select', 'parent_id', get_lang('AttachToMedia'), $course_medias);
        }

        $form->addElement('html', '</div>');

        if (!isset($_GET['fromExercise'])) {
            switch ($answerType) {
                case 1:
                    $this->question = get_lang('DefaultUniqueQuestion');
                    break;
                case 2:
                    $this->question = get_lang('DefaultMultipleQuestion');
                    break;
                case 3:
                    $this->question = get_lang('DefaultFillBlankQuestion');
                    break;
                case 4:
                    $this->question = get_lang('DefaultMathingQuestion');
                    break;
                case 5:
                    $this->question = get_lang('DefaultOpenQuestion');
                    break;
                case 9:
                    $this->question = get_lang('DefaultMultipleQuestion');
                    break;
            }
        }

        if (!is_null($exercise)) {
            if ($exercise->questionFeedbackEnabled && $this->showFeedback($exercise)) {
                $form->addTextarea('feedback', get_lang('FeedbackIfNotCorrect'));
            }
        }

        $extraField = new ExtraField('question');
        $extraField->addElements($form, $this->iid);

        // default values

        // Came from he question pool
        if (isset($_GET['fromExercise'])
            || (!isset($_GET['newQuestion']) || $isContent)
        ) {
            try {
                $form->getElement('questionName')->setValue($this->question);
            } catch (Exception $exception) {
            }

            try {
                $form->getElement('questionDescription')->setValue($this->description);
            } catch (Exception $e) {
            }

            try {
                $form->getElement('questionLevel')->setValue($this->level);
            } catch (Exception $e) {
            }

            try {
                $form->getElement('questionCategory')->setValue($this->category);
            } catch (Exception $e) {
            }

            try {
                $form->getElement('feedback')->setValue($this->feedback);
            } catch (Exception $e) {
            }

            try {
                $form->getElement('mandatory')->setValue($this->mandatory);
            } catch (Exception $e) {
            }
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
     * function which process the creation of questions.
     *
     * @param FormValidator $form
     * @param Exercise      $exercise
     */
    public function processCreation($form, $exercise)
    {
        $this->updateTitle($form->getSubmitValue('questionName'));
        $this->updateDescription($form->getSubmitValue('questionDescription'));
        $this->updateLevel($form->getSubmitValue('questionLevel'));
        $this->updateCategory($form->getSubmitValue('questionCategory'));
        $this->setMandatory($form->getSubmitValue('mandatory'));
        $this->setFeedback($form->getSubmitValue('feedback'));

        //Save normal question if NOT media
        if (MEDIA_QUESTION != $this->type) {
            $creationMode = empty($this->iid);
            $this->save($exercise);
            $exercise->addToList($this->iid);

            // Only update position in creation and when using ordered or random types.
            if ($creationMode &&
                in_array($exercise->questionSelectionType, [EX_Q_SELECTION_ORDERED, EX_Q_SELECTION_RANDOM])
            ) {
                $exercise->update_question_positions();
            }

            $params = $form->exportValues();
            $params['item_id'] = $this->iid;

            $extraFieldValues = new ExtraFieldValue('question');
            $extraFieldValues->saveFieldValues($params);
        }
    }

    /**
     * abstract function which creates the form to create / edit the answers of the question.
     */
    abstract public function createAnswersForm($form);

    /**
     * abstract function which process the creation of answers.
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
        $exerciseId = $objExercise->iid;

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

        echo '<div class="panel panel-default">';
        echo '<div class="panel-body">';
        echo '<ul class="question_menu">';
        foreach ($questionTypeList as $i => $type) {
            /** @var Question $type */
            $type = new $type[1]();
            $img = $type->getTypePicture();
            $explanation = get_lang($type->getExplanation());
            echo '<li>';
            echo '<div class="icon-image">';
            $icon = '<a href="admin.php?'.api_get_cidreq().'&newQuestion=yes&answerType='.$i.'">'.
                Display::return_icon($img, $explanation, null, ICON_SIZE_BIG).'</a>';

            if ($objExercise->force_edit_exercise_in_lp === false) {
                if ($objExercise->exercise_was_added_in_lp == true) {
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
        if ($objExercise->exercise_was_added_in_lp == true) {
            echo Display::return_icon(
                'database_na.png',
                get_lang('GetExistingQuestion'),
                null,
                ICON_SIZE_BIG
            );
        } else {
            if (in_array($feedbackType, [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])) {
                echo $url = "<a href=\"question_pool.php?".api_get_cidreq()."&type=1&fromExercise=$exerciseId\">";
            } else {
                echo $url = '<a href="question_pool.php?'.api_get_cidreq().'&fromExercise='.$exerciseId.'">';
            }
            echo Display::return_icon(
                'database.png',
                get_lang('GetExistingQuestion'),
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
     * @param int    $question_id
     * @param string $name
     * @param int    $course_id
     * @param int    $position
     *
     * @return false|string
     */
    public static function saveQuestionOption($question_id, $name, $course_id, $position = 0)
    {
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);
        $params['question_id'] = (int) $question_id;
        $params['name'] = $name;
        $params['position'] = $position;
        $params['c_id'] = $course_id;
        //$result = self::readQuestionOption($question_id, $course_id);
        $last_id = Database::insert($table, $params);

        return $last_id;
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
                'question_id = ?' => [
                    $question_id,
                ],
            ]
        );
    }

    /**
     * @param int   $id
     * @param array $params
     * @param int   $course_id
     *
     * @return bool|int
     */
    public static function updateQuestionOption($id, $params, $course_id)
    {
        $table = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);
        if (isset($params['id'])) {
            // 'id' has been replaced by 'iid' but is still defined into
            // $params because of Database::select() which add this index
            // by default, so "undefine" it to avoid errors if the field
            // does not exist
            unset($params['id']);
        }

        return Database::update(
            $table,
            $params,
            ['iid = ?' => [$id]]
        );
    }

    /**
     * @param int $question_id
     * @param int $course_id
     *
     * @return array
     */
    public static function readQuestionOption($question_id, $course_id)
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
     * @param Exercise $exercise The current exercise object
     * @param int      $counter  A counter for the current question
     * @param array    $score    Array of optional info ['pass', 'revised', 'score', 'weight', 'user_answered']
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
            $scoreLabel = get_lang('QuizWrongAnswerHereIsTheCorrectOne');
        }

        $class = 'error';
        if (isset($score['pass']) && $score['pass'] == true) {
            $scoreLabel = get_lang('Correct');

            if (in_array($exercise->results_disabled, [
                RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
            ])
            ) {
                $scoreLabel = get_lang('CorrectAnswer');
            }
            $class = 'success';
        }

        switch ($this->type) {
            case FREE_ANSWER:
            case UPLOAD_ANSWER:
            case ORAL_EXPRESSION:
            case ANNOTATION:
                $score['revised'] = isset($score['revised']) ? $score['revised'] : false;
                if ($score['revised'] == true) {
                    $scoreLabel = get_lang('Revised');
                    $class = '';
                } else {
                    $scoreLabel = get_lang('NotRevised');
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
                        if ($score['user_answered'] === false) {
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
            $header = TestCategory::returnCategoryAndTitle($this->iid);
        }
        $show_media = '';
        if ($show_media) {
            $header .= $this->show_media_content();
        }

        $scoreCurrent = [
            'used' => isset($score['score']) ? $score['score'] : '',
            'missing' => isset($score['weight']) ? $score['weight'] : '',
        ];

        // Check whether we need to hide the question ID
        // (quiz_hide_question_number config + quiz field)
        $title = '';
        if ($exercise->getHideQuestionNumber()) {
            $title = Display::page_subheader2($this->question);
        } else {
            $title = Display::page_subheader2($counterLabel.'. '.$this->question);
        }
        $header .= $title;

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

        if ($this->type != READING_COMPREHENSION) {
            // Do not show the description (the text to read) if the question is of type READING_COMPREHENSION
            $header .= Display::div(
                $this->description,
                ['class' => 'question_description']
            );
        } else {
            if (isset($score['pass']) && true == $score['pass']) {
                $message = Display::div(
                    sprintf(
                        get_lang('ReadingQuestionCongratsSpeedXReachedForYWords'),
                        ReadingComprehension::$speeds[$this->level],
                        $this->getWordsCount()
                    )
                );
            } else {
                $message = Display::div(
                    sprintf(
                        get_lang('ReadingQuestionCongratsSpeedXNotReachedForYWords'),
                        ReadingComprehension::$speeds[$this->level],
                        $this->getWordsCount()
                    )
                );
            }
            $header .= $message.'<br />';
        }

        if ($exercise->hideComment && in_array($this->type, [HOT_SPOT, HOT_SPOT_COMBINATION])) {
            $header .= Display::return_message(get_lang('ResultsOnlyAvailableOnline'));

            return $header;
        }

        if (isset($score['pass']) && $score['pass'] === false) {
            if ($this->showFeedback($exercise)) {
                $header .= $this->returnFormatFeedback();
            }
        }

        return $header;
    }

    /**
     * @deprecated
     * Create a question from a set of parameters.
     *
     * @param   int     Quiz ID
     * @param   string  Question name
     * @param   int     Maximum result for the question
     * @param   int     Type of question (see constants at beginning of question.class.php)
     * @param   int     Question level/category
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
                    q.iid = r.question_id AND
                    exercice_id = $quiz_id AND
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
                    WHERE c_id = $course_id AND exercice_id = $quiz_id ";
            $rs_max_order = Database::query($sql);
            $row_max_order = Database::fetch_object($rs_max_order);
            $max_order = $row_max_order->max_order + 1;
            // Attach questions to quiz
            $sql = "INSERT INTO $tbl_quiz_rel_question (c_id, question_id, exercice_id, question_order)
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
        return $this->explanationLangVar;
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
        $media_list[0] = get_lang('NoMedia');

        if (!empty($medias)) {
            foreach ($medias as $media) {
                $media_list[$media['iid']] = empty($media['question']) ? get_lang('Untitled') : $media['question'];
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
    public function swapSimpleAnswerTypes($index = 0)
    {
        $oppositeAnswers = [
            UNIQUE_ANSWER => [MULTIPLE_ANSWER],
            MULTIPLE_ANSWER => [UNIQUE_ANSWER, MULTIPLE_ANSWER_DROPDOWN, MULTIPLE_ANSWER_DROPDOWN_COMBINATION],
            MULTIPLE_ANSWER_DROPDOWN => [MULTIPLE_ANSWER],
            MULTIPLE_ANSWER_DROPDOWN_COMBINATION => [MULTIPLE_ANSWER],
        ];
        $this->type = $oppositeAnswers[$this->type][$index];
        Database::update(
            Database::get_course_table(TABLE_QUIZ_QUESTION),
            ['type' => $this->type],
            ['iid = ?' => [$this->iid]]
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

        $objAnswer = new Answer($swappedAnswer->iid);
        $_POST['nb_answers'] = $objAnswer->nbrAnswers;

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
                WHERE qq.questionId = :iid
            ')
            ->setParameters(['iid' => (int) $this->iid])
            ->getSingleScalarResult();

        return (int) $count;
    }

    /**
     * Check if this question exists in another exercise.
     *
     * @throws \Doctrine\ORM\Query\QueryException
     *
     * @return mixed
     */
    public function getExerciseListWhereQuestionExists()
    {
        $em = Database::getManager();

        return $em
            ->createQuery('
                SELECT e
                FROM ChamiloCourseBundle:CQuizRelQuestion qq
                JOIN ChamiloCourseBundle:CQuiz e
                WHERE e.iid = qq.exerciceId AND qq.questionId = :iid
            ')
            ->setParameters(['iid' => (int) $this->iid])
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
            ['where' => ['question_id = ?' => [$this->iid]]],
            'first'
        );

        return (int) $result['c'];
    }

    /**
     * Count the number of quizzes that use a question.
     *
     * @param int $questionId - question ID
     *
     * @return int - The number of quizzes where the question is used
     */
    public static function countQuizzesUsingQuestion(int $questionId)
    {
        $table = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $result = Database::select(
            'count(*) as count',
            $table,
            [
                'where' => [
                    'question_id = ? ' => [
                        $questionId,
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
     * Gets the first quiz ID that uses a given question.
     * The c_quiz_rel_question result with lower iid is the master quiz.
     *
     * @param int $questionId - question ID
     *
     * @return int The quiz ID
     */
    public static function getMasterQuizForQuestion($questionId)
    {
        $table = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);

        $row = Database::select(
            '*',
            $table,
            [
                'where' => [
                    'question_id = ?' => [
                        $questionId,
                    ],
                ],
                'order' => 'iid ASC',
            ],
            'first'
        );

        if (is_array($row) && isset($row['exercice_id'])) {
            return $row['exercice_id'];
        } else {
            return false;
        }
    }

    /**
     * Resizes a picture || Warning!: can only be called after uploadPicture,
     * or if picture is already available in object.
     *
     * @param string $Dimension - Resizing happens proportional according to given dimension: height|width|any
     * @param int    $Max       - Maximum size
     *
     * @return bool|null - true if success, false if failed
     *
     * @author Toon Keppens
     */
    private function resizePicture($Dimension, $Max)
    {
        // if the question has an ID
        if (!$this->iid) {
            return false;
        }

        $picturePath = $this->getHotSpotFolderInCourse().'/'.$this->getPictureFilename();

        // Get dimensions from current image.
        $my_image = new Image($picturePath);

        $current_image_size = $my_image->get_image_size();
        $current_width = $current_image_size['width'];
        $current_height = $current_image_size['height'];

        if ($current_width < $Max && $current_height < $Max) {
            return true;
        } elseif ($current_height == '') {
            return false;
        }

        // Resize according to height.
        if ($Dimension == "height") {
            $resize_scale = $current_height / $Max;
            $new_width = ceil($current_width / $resize_scale);
        }

        // Resize according to width
        if ($Dimension == "width") {
            $new_width = $Max;
        }

        // Resize according to height or width, both should not be larger than $Max after resizing.
        if ($Dimension == "any") {
            if ($current_height > $current_width || $current_height == $current_width) {
                $resize_scale = $current_height / $Max;
                $new_width = ceil($current_width / $resize_scale);
            }
            if ($current_height < $current_width) {
                $new_width = $Max;
            }
        }

        $my_image->resize($new_width);
        $result = $my_image->send_image($picturePath);

        if ($result) {
            return true;
        }

        return false;
    }
}
