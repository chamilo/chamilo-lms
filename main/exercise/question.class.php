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
 *
 * @package chamilo.exercise
 */
abstract class Question
{
    public $id;
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
    public $isContent;
    public $course;
    public $feedback;
    public static $typePicture = 'new_question.png';
    public static $explanationLangVar = '';
    public $question_table_class = 'table table-striped';
    public $questionTypeWithFeedback;
    public $extra;
    public $export = false;
    public static $questionTypes = [
        UNIQUE_ANSWER => ['unique_answer.class.php', 'UniqueAnswer'],
        MULTIPLE_ANSWER => ['multiple_answer.class.php', 'MultipleAnswer'],
        FILL_IN_BLANKS => ['fill_blanks.class.php', 'FillBlanks'],
        MATCHING => ['matching.class.php', 'Matching'],
        FREE_ANSWER => ['freeanswer.class.php', 'FreeAnswer'],
        ORAL_EXPRESSION => ['oral_expression.class.php', 'OralExpression'],
        HOT_SPOT => ['hotspot.class.php', 'HotSpot'],
        HOT_SPOT_DELINEATION => ['hotspot.class.php', 'HotspotDelineation'],
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

    /**
     * @return int|null
     */
    public function getIsContent()
    {
        $isContent = null;
        if (isset($_REQUEST['isContent'])) {
            $isContent = intval($_REQUEST['isContent']);
        }

        return $this->isContent = $isContent;
    }

    /**
     * Reads question information from the data base.
     *
     * @param int $id        - question ID
     * @param int $course_id
     *
     * @return Question
     *
     * @author Olivier Brouckaert
     */
    public static function read($id, $course_id = null)
    {
        $id = (int) $id;
        if (!empty($course_id)) {
            $course_info = api_get_course_info_by_id($course_id);
        } else {
            $course_info = api_get_course_info();
        }

        $course_id = $course_info['real_id'];

        if (empty($course_id) || $course_id == -1) {
            return false;
        }

        $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $TBL_EXERCISE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);

        $sql = "SELECT *
                FROM $TBL_QUESTIONS
                WHERE c_id = $course_id AND id = $id ";
        $result = Database::query($sql);

        // if the question has been found
        if ($object = Database::fetch_object($result)) {
            $objQuestion = self::getInstance($object->type);
            if (!empty($objQuestion)) {
                $objQuestion->id = (int) $id;
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

                $tblQuiz = Database::get_course_table(TABLE_QUIZ_TEST);
                $sql = "SELECT DISTINCT q.exercice_id
                        FROM $TBL_EXERCISE_QUESTION q
                        INNER JOIN $tblQuiz e
                        ON e.c_id = q.c_id AND e.id = q.exercice_id
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
        return $this->id;
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
     * @param int $itemNumber
     *
     * @return string
     */
    public function getTitleToDisplay($itemNumber)
    {
        $showQuestionTitleHtml = api_get_configuration_value('save_titles_as_html');

        $title = $showQuestionTitleHtml ? '' : '<strong>';
        $title .= $itemNumber.'. '.$this->selectTitle();
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
        return sizeof($this->exerciseList);
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
        $this->parent_id = intval($id);
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
                $question_id = (int) $this->id;
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
            if ($row['nb'] > 0) {
                $sql = "UPDATE $table
                        SET category_id = $categoryId
                        WHERE
                            question_id = $question_id AND
                            c_id = ".$courseId;
                Database::query($sql);
            } else {
                $sql = "INSERT INTO $table (c_id, question_id, category_id)
                        VALUES (".$courseId.", $question_id, $categoryId)";
                Database::query($sql);
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
        $questionId = (int) $this->id;
        if (empty($courseId) || empty($questionId)) {
            return false;
        }
        $sql = "DELETE FROM $table
                WHERE
                    question_id = $questionId AND
                    c_id = ".$courseId;
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
                        WHERE c_id = $course_id AND question_id = ".intval($this->id);
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
        if ($this->id) {
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
        if ($this->id) {
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
    public function exportPicture($questionId, $courseInfo)
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
        if (!$this->id || empty($this->picture)) {
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
                WHERE c_id = $course_id AND id='".intval($questionId)."'";
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
        $exerciseId = $exercise->id;

        $id = $this->id;
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
                ['c_id = ? AND id = ?' => [$c_id, $id]]
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
            if (api_get_setting('search_enabled') == 'true') {
                if ($exerciseId != 0) {
                    $this->search_engine_edit($exerciseId);
                } else {
                    /**
                     * actually there is *not* an user interface for
                     * creating questions without a relation with an exercise.
                     */
                }
            }
        } else {
            // creates a new question
            $sql = "SELECT max(position)
                    FROM $TBL_QUESTIONS as question,
                    $TBL_EXERCISE_QUESTION as test_question
                    WHERE
                        question.id = test_question.question_id AND
                        test_question.exercice_id = ".intval($exerciseId)." AND
                        question.c_id = $c_id AND
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
            $this->id = Database::insert($TBL_QUESTIONS, $params);

            if ($this->id) {
                $sql = "UPDATE $TBL_QUESTIONS SET id = iid WHERE iid = {$this->id}";
                Database::query($sql);

                api_item_property_update(
                    $this->course,
                    TOOL_QUIZ,
                    $this->id,
                    'QuizQuestionAdded',
                    api_get_user_id()
                );

                // If hotspot, create first answer
                if ($type == HOT_SPOT || $type == HOT_SPOT_ORDER) {
                    $quizAnswer = new CQuizAnswer();
                    $quizAnswer
                        ->setCId($c_id)
                        ->setQuestionId($this->id)
                        ->setAnswer('')
                        ->setPonderation(10)
                        ->setPosition(1)
                        ->setHotspotCoordinates('0;0|0|0')
                        ->setHotspotType('square');

                    $em->persist($quizAnswer);
                    $em->flush();

                    $id = $quizAnswer->getIid();

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
                        ->setQuestionId($this->id)
                        ->setAnswer('')
                        ->setPonderation(10)
                        ->setPosition(1)
                        ->setHotspotCoordinates('0;0|0|0')
                        ->setHotspotType('delineation');

                    $em->persist($quizAnswer);
                    $em->flush();

                    $id = $quizAnswer->getIid();

                    if ($id) {
                        $quizAnswer
                            ->setId($id)
                            ->setIdAuto($id);

                        $em->merge($quizAnswer);
                        $em->flush();
                    }
                }

                if (api_get_setting('search_enabled') == 'true') {
                    if ($exerciseId != 0) {
                        $this->search_engine_edit($exerciseId, true);
                    } else {
                        /**
                         * actually there is *not* an user interface for
                         * creating questions without a relation with an exercise.
                         */
                    }
                }
            }
        }

        // if the question is created in an exercise
        if ($exerciseId) {
            // adds the exercise into the exercise list of this question
            $this->addToList($exerciseId, true);
        }
    }

    public function search_engine_edit(
        $exerciseId,
        $addQs = false,
        $rmQs = false
    ) {
        // update search engine and its values table if enabled
        if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
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
                if ($se_doc !== false) {
                    if (($se_doc_data = $di->get_document_data($se_doc)) !== false) {
                        $se_doc_data = unserialize($se_doc_data);
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
                $ic_slide->addValue("title", $this->question);
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
                $ic_slide->addValue("content", $this->description);

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
        $id = $this->id;
        // checks if the exercise ID is not in the list
        if (!in_array($exerciseId, $this->exerciseList)) {
            $this->exerciseList[] = $exerciseId;
            $new_exercise = new Exercise();
            $new_exercise->read($exerciseId);
            $count = $new_exercise->selectNbrQuestions();
            $count++;
            $sql = "INSERT INTO $exerciseRelQuestionTable (c_id, question_id, exercice_id, question_order)
                    VALUES ({$this->course['real_id']}, ".intval($id).", ".intval($exerciseId).", '$count')";
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
     *
     * @return bool - true if removed, otherwise false
     */
    public function removeFromList($exerciseId)
    {
        $TBL_EXERCISE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $id = $this->id;

        // searches the position of the exercise ID in the list
        $pos = array_search($exerciseId, $this->exerciseList);
        $course_id = api_get_course_int_id();

        // exercise not found
        if ($pos === false) {
            return false;
        } else {
            // deletes the position in the array containing the wanted exercise ID
            unset($this->exerciseList[$pos]);
            //update order of other elements
            $sql = "SELECT question_order
                    FROM $TBL_EXERCISE_QUESTION
                    WHERE
                        c_id = $course_id
                        AND question_id = ".intval($id)."
                        AND exercice_id = ".intval($exerciseId);
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $row = Database::fetch_array($res);
                if (!empty($row['question_order'])) {
                    $sql = "UPDATE $TBL_EXERCISE_QUESTION
                        SET question_order = question_order-1
                        WHERE
                            c_id = $course_id
                            AND exercice_id = ".intval($exerciseId)."
                            AND question_order > ".$row['question_order'];
                    Database::query($sql);
                }
            }

            $sql = "DELETE FROM $TBL_EXERCISE_QUESTION
                    WHERE
                        c_id = $course_id
                        AND question_id = ".intval($id)."
                        AND exercice_id = ".intval($exerciseId);
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
     */
    public function delete($deleteFromEx = 0)
    {
        $course_id = api_get_course_int_id();

        $TBL_EXERCISE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $TBL_REPONSES = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $TBL_QUIZ_QUESTION_REL_CATEGORY = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);

        $id = (int) $this->id;

        // if the question must be removed from all exercises
        if (!$deleteFromEx) {
            //update the question_order of each question to avoid inconsistencies
            $sql = "SELECT exercice_id, question_order 
                    FROM $TBL_EXERCISE_QUESTION
                    WHERE c_id = $course_id AND question_id = ".intval($id);

            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                while ($row = Database::fetch_array($res)) {
                    if (!empty($row['question_order'])) {
                        $sql = "UPDATE $TBL_EXERCISE_QUESTION
                                SET question_order = question_order-1
                                WHERE
                                    c_id = $course_id AND 
                                    exercice_id = ".intval($row['exercice_id'])." AND 
                                    question_order > ".$row['question_order'];
                        Database::query($sql);
                    }
                }
            }

            $sql = "DELETE FROM $TBL_EXERCISE_QUESTION
                    WHERE c_id = $course_id AND question_id = ".$id;
            Database::query($sql);

            $sql = "DELETE FROM $TBL_QUESTIONS
                    WHERE c_id = $course_id AND id = ".$id;
            Database::query($sql);

            $sql = "DELETE FROM $TBL_REPONSES
                    WHERE c_id = $course_id AND question_id = ".$id;
            Database::query($sql);

            // remove the category of this question in the question_rel_category table
            $sql = "DELETE FROM $TBL_QUIZ_QUESTION_REL_CATEGORY
                    WHERE 
                        c_id = $course_id AND 
                        question_id = ".$id;
            Database::query($sql);

            api_item_property_update(
                $this->course,
                TOOL_QUIZ,
                $id,
                'QuizQuestionDeleted',
                api_get_user_id()
            );
            $this->removePicture();
        } else {
            // just removes the exercise from the list
            $this->removeFromList($deleteFromEx);
            if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
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
        }
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
        $options = self::readQuestionOption($this->id, $this->course['real_id']);

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
            $sql = "UPDATE $questionTable 
                    SET id = iid
                    WHERE iid = $newQuestionId";
            Database::query($sql);

            if (!empty($options)) {
                // Saving the quiz_options
                foreach ($options as $item) {
                    $item['question_id'] = $newQuestionId;
                    $item['c_id'] = $course_id;
                    unset($item['id']);
                    unset($item['iid']);
                    $id = Database::insert($TBL_QUESTION_OPTIONS, $item);
                    if ($id) {
                        $sql = "UPDATE $TBL_QUESTION_OPTIONS 
                                SET id = iid
                                WHERE iid = $id";
                        Database::query($sql);
                    }
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
    public static function get_question_type_list()
    {
        if (api_get_setting('enable_record_audio') !== 'true') {
            self::$questionTypes[ORAL_EXPRESSION] = null;
            unset(self::$questionTypes[ORAL_EXPRESSION]);
        }
        if (api_get_setting('enable_quiz_scenario') !== 'true') {
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

        // question name
        if (api_get_configuration_value('save_titles_as_html')) {
            $editorConfig = ['ToolbarSet' => 'Minimal'];
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
        $isContent = isset($_REQUEST['isContent']) ? intval($_REQUEST['isContent']) : null;

        // Question type
        $answerType = isset($_REQUEST['answerType']) ? intval($_REQUEST['answerType']) : null;
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
        $form->addElement('html', '<div id="advanced_params_options" style="display:none">');
        $form->addHtmlEditor(
            'questionDescription',
            get_lang('QuestionDescription'),
            false,
            false,
            $editorConfig
        );

        // hidden values
        $my_id = isset($_REQUEST['myid']) ? intval($_REQUEST['myid']) : null;
        $form->addElement('hidden', 'myid', $my_id);

        if ($this->type != MEDIA_QUESTION) {
            // Advanced parameters
            $select_level = self::get_default_levels();
            $form->addElement(
                'select',
                'questionLevel',
                get_lang('Difficulty'),
                $select_level
            );

            // Categories
            $tabCat = TestCategory::getCategoriesIdAndName();
            $form->addElement(
                'select',
                'questionCategory',
                get_lang('Category'),
                $tabCat
            );

            global $text;

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
                        get_lang('ConvertToMultipleAnswer'),
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
                        get_lang('ConvertToUniqueAnswer'),
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

        // default values
        $defaults = [];
        $defaults['questionName'] = $this->question;
        $defaults['questionDescription'] = $this->description;
        $defaults['questionLevel'] = $this->level;
        $defaults['questionCategory'] = $this->category;
        $defaults['feedback'] = $this->feedback;

        // Came from he question pool
        if (isset($_GET['fromExercise'])) {
            $form->setDefaults($defaults);
        }

        if (!empty($_REQUEST['myid'])) {
            $form->setDefaults($defaults);
        } else {
            if ($isContent == 1) {
                $form->setDefaults($defaults);
            }
        }
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
        $this->setFeedback($form->getSubmitValue('feedback'));

        //Save normal question if NOT media
        if ($this->type != MEDIA_QUESTION) {
            $this->save($exercise);

            // modify the exercise
            $exercise->addToList($this->id);
            $exercise->update_question_positions();
        }
    }

    /**
     * abstract function which creates the form to create / edit the answers of the question.
     *
     * @param FormValidator $form
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
    public static function display_type_menu($objExercise)
    {
        $feedback_type = $objExercise->feedback_type;
        $exerciseId = $objExercise->id;

        // 1. by default we show all the question types
        $question_type_custom_list = self::get_question_type_list();

        if (!isset($feedback_type)) {
            $feedback_type = 0;
        }

        if ($feedback_type == 1) {
            //2. but if it is a feedback DIRECT we only show the UNIQUE_ANSWER type that is currently available
            $question_type_custom_list = [
                UNIQUE_ANSWER => self::$questionTypes[UNIQUE_ANSWER],
                HOT_SPOT_DELINEATION => self::$questionTypes[HOT_SPOT_DELINEATION],
            ];
        } else {
            unset($question_type_custom_list[HOT_SPOT_DELINEATION]);
        }

        echo '<div class="panel panel-default">';
        echo '<div class="panel-body">';
        echo '<ul class="question_menu">';
        foreach ($question_type_custom_list as $i => $a_type) {
            // @todo remove require_once classes are already loaded using composer
            // include the class of the type
            require_once $a_type[0];
            // get the picture of the type and the langvar which describes it
            $img = $explanation = '';
            eval('$img = '.$a_type[1].'::$typePicture;');
            eval('$explanation = get_lang('.$a_type[1].'::$explanationLangVar);');
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
            if ($feedback_type == 1) {
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
        $result = self::readQuestionOption($question_id, $course_id);
        $last_id = Database::insert($table, $params);
        if ($last_id) {
            $sql = "UPDATE $table SET id = iid WHERE iid = $last_id";
            Database::query($sql);
        }

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
                'c_id = ? AND question_id = ?' => [
                    $course_id,
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
        $result = Database::update(
            $table,
            $params,
            ['c_id = ? AND id = ?' => [$course_id, $id]]
        );

        return $result;
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
        $result = Database::select(
            '*',
            $table,
            [
                'where' => [
                    'c_id = ? AND question_id = ?' => [
                        $course_id,
                        $question_id,
                    ],
                ],
                'order' => 'id ASC',
            ]
        );

        return $result;
    }

    /**
     * Shows question title an description.
     *
     * @param Exercise $exercise
     * @param int      $counter
     * @param array    $score
     *
     * @return string HTML string with the header of the question (before the answers table)
     */
    public function return_header($exercise, $counter = null, $score = [])
    {
        $counterLabel = '';
        if (!empty($counter)) {
            $counterLabel = (int) $counter;
        }
        $score_label = get_lang('Wrong');
        $class = 'error';
        if ($score['pass'] == true) {
            $score_label = get_lang('Correct');
            $class = 'success';
        }

        if (in_array($this->type, [FREE_ANSWER, ORAL_EXPRESSION, ANNOTATION])) {
            $score['revised'] = isset($score['revised']) ? $score['revised'] : false;
            if ($score['revised'] == true) {
                $score_label = get_lang('Revised');
                $class = '';
            } else {
                $score_label = get_lang('NotRevised');
                $class = 'warning';
                $weight = float_format($score['weight'], 1);
                $score['result'] = " ? / ".$weight;
                $model = ExerciseLib::getCourseScoreModel();
                if (!empty($model)) {
                    $score['result'] = ' ? ';
                }

                $hide = api_get_configuration_value('hide_free_question_score');
                if ($hide === true) {
                    $score['result'] = '-';
                }
            }
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
            'used' => $score['score'],
            'missing' => $score['weight'],
        ];
        $header .= Display::page_subheader2($counterLabel.'. '.$this->question);
        // dont display score for certainty degree questions
        if ($this->type != MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY) {
            $header .= $exercise->getQuestionRibbon($class, $score_label, $score['result'], $scoreCurrent);
        }

        if ($this->type != READING_COMPREHENSION) {
            // Do not show the description (the text to read) if the question is of type READING_COMPREHENSION
            $header .= Display::div(
                $this->description,
                ['class' => 'question_description']
            );
        } else {
            if ($score['pass'] == true) {
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

        if (isset($score['pass']) && $score['pass'] === false) {
            if ($this->showFeedback($exercise)) {
                $header .= $this->returnFormatFeedback();
            }
        }

        return $header;
    }

    /**
     * Create a question from a set of parameters.
     *
     * @param   int     Quiz ID
     * @param   string  Question name
     * @param   int     Maximum result for the question
     * @param   int     Type of question (see constants at beginning of question.class.php)
     * @param   int     Question level/category
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

        $quiz_id = intval($quiz_id);
        $max_score = (float) $max_score;
        $type = intval($type);
        $level = intval($level);

        // Get the max position
        $sql = "SELECT max(position) as max_position
                FROM $tbl_quiz_question q 
                INNER JOIN $tbl_quiz_rel_question r
                ON
                    q.id = r.question_id AND
                    exercice_id = $quiz_id AND
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
            $sql = "UPDATE $tbl_quiz_question  
                    SET id = iid WHERE iid = $question_id";
            Database::query($sql);

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
     * @return array the image filename of the question type
     */
    public function get_type_icon_html()
    {
        $type = $this->selectType();
        $tabQuestionList = self::get_question_type_list(); // [0]=file to include [1]=type name

        require_once $tabQuestionList[$type][0];

        $img = $explanation = null;
        eval('$img = '.$tabQuestionList[$type][1].'::$typePicture;');
        eval('$explanation = get_lang('.$tabQuestionList[$type][1].'::$explanationLangVar);');

        return [$img, $explanation];
    }

    /**
     * Get course medias.
     *
     * @param int course id
     * @param int $course_id
     *
     * @return array
     */
    public static function get_course_medias(
        $course_id,
        $start = 0,
        $limit = 100,
        $sidx = "question",
        $sord = "ASC",
        $where_condition = []
    ) {
        $table_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $default_where = [
            'c_id = ? AND parent_id = 0 AND type = ?' => [
                $course_id,
                MEDIA_QUESTION,
            ],
        ];
        $result = Database::select(
            '*',
            $table_question,
            [
                'limit' => " $start, $limit",
                'where' => $default_where,
                'order' => "$sidx $sord",
            ]
        );

        return $result;
    }

    /**
     * Get count course medias.
     *
     * @param int course id
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
        $levels = [
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
        ];

        return $levels;
    }

    /**
     * @return string
     */
    public function show_media_content()
    {
        $html = '';
        if ($this->parent_id != 0) {
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
        return
            in_array($this->type, $this->questionTypeWithFeedback) &&
            $exercise->feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM;
    }

    /**
     * @return string
     */
    public function returnFormatFeedback()
    {
        return '<br />'.Display::return_message($this->feedback, 'normal', false);
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
        if (!$this->id) {
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
