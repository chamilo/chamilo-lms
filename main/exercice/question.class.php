<?php
/* For licensing terms, see /license.txt */
/**
 * File containing the Question class.
 * @package chamilo.exercise
 * @author Olivier Brouckaert
 * @author Julio Montoya <gugli100@gmail.com> lot of bug fixes
 * Modified by hubert.borderiou@grenet.fr - add question categories
 */
/**
 * Code
 */

/**
 *    QUESTION CLASS
 *
 *  This class allows to instantiate an object of type Question
 *
 * @author Olivier Brouckaert, original author
 * @author Patrick Cool, LaTeX support
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
    public $isContent;
    public $course;
    public static $typePicture = 'new_question.png';
    public static $explanationLangVar = '';
    public $question_table_class = 'table table-striped';
    public $editionMode = 'normal';
    /** @var  Exercise $exercise */
    public $exercise;
    public $setDefaultValues = false;
    public $submitClass;
    public $submitText;
    public $setDefaultQuestionValues = false;
    public $c_id = null;
    // if fastEdition is on
    public $textareaSettings = ' cols="50" rows="5" ';

    public static $questionTypes = array(
        UNIQUE_ANSWER                          => array('unique_answer.class.php', 'UniqueAnswer'),
        MULTIPLE_ANSWER                        => array('multiple_answer.class.php', 'MultipleAnswer'),
        FILL_IN_BLANKS                         => array('fill_blanks.class.php', 'FillBlanks'),
        MATCHING                               => array('matching.class.php', 'Matching'),
        FREE_ANSWER                            => array('freeanswer.class.php', 'FreeAnswer'),
        ORAL_EXPRESSION                        => array('oral_expression.class.php', 'OralExpression'),
        HOT_SPOT                               => array('hotspot.class.php', 'HotSpot'),
        HOT_SPOT_DELINEATION                   => array('hotspot.class.php', 'HotspotDelineation'),
        MULTIPLE_ANSWER_COMBINATION            => array('multiple_answer_combination.class.php', 'MultipleAnswerCombination' ),
        UNIQUE_ANSWER_NO_OPTION                => array('unique_answer_no_option.class.php', 'UniqueAnswerNoOption'),
        MULTIPLE_ANSWER_TRUE_FALSE             => array('multiple_answer_true_false.class.php', 'MultipleAnswerTrueFalse'),
        MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE => array('multiple_answer_combination_true_false.class.php', 'MultipleAnswerCombinationTrueFalse'),
        GLOBAL_MULTIPLE_ANSWER                 => array('global_multiple_answer.class.php', 'GlobalMultipleAnswer'),
        MEDIA_QUESTION                         => array('media_question.class.php', 'MediaQuestion'),
        UNIQUE_ANSWER_IMAGE                    => array('unique_answer_image.class.php', 'UniqueAnswerImage'),
        DRAGGABLE                              => array('draggable.class.php', 'Draggable')
    );

    /**
     * constructor of the class
     *
     * @author - Olivier Brouckaert
     */
    public function Question()
    {
        $this->id            = 0;
        $this->question      = '';
        $this->description   = '';
        $this->weighting     = 0;
        $this->position      = 1;
        $this->picture       = '';
        $this->level         = 1;
        // This variable is used when loading an exercise like an scenario
        //with an special hotspot: final_overlap, final_missing, final_excess
        $this->extra         = '';
        $this->exerciseList  = array();
        $this->course        = api_get_course_info();
        $this->category_list = array();
        $this->parent_id     = 0;
        $this->editionMode   = 'normal';
    }

    public function getIsContent()
    {
        $isContent = null;
        if (isset($_REQUEST['isContent'])) {
            $isContent = intval($_REQUEST['isContent']);
        }

        return $this->isContent = $isContent;
    }

    /**
     * @param string $title
     * @param int $course_id
     * @return mixed|bool
     */
    public function readByTitle($title, $course_id = null)
    {
        if (!empty($course_id)) {
            $course_info = api_get_course_info_by_id($course_id);
        } else {
            $course_info = api_get_course_info();
        }
        $course_id = $course_info['real_id'];

        $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $title         = Database::escape_string($title);
        $sql           = "SELECT iid FROM $TBL_QUESTIONS WHERE question = '$title' AND c_id = $course_id ";
        $result        = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result);

            return self::read($row['iid'], $course_id);
        }

        return false;
    }

    /**
     * Reads question information from the database
     *
     * @author Olivier Brouckaert
     * @param int $id - question ID
     * @param int $course_id
     * @param Exercise
     *
     * @return boolean - true if question exists, otherwise false
     */
    public static function read($id, $course_id = null, Exercise $exercise = null)
    {
        $id = intval($id);

        if (!empty($course_id)) {
            $course_info = api_get_course_info_by_id($course_id);
        } else {
            $course_info = api_get_course_info();
        }

        $course_id = $course_info['real_id'];

        if (empty($course_id) || $course_id == -1) {
            //return false;
        }

        $TBL_QUESTIONS         = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);

        $sql = "SELECT * FROM $TBL_QUESTIONS WHERE iid = $id";

        $result = Database::query($sql);

        // if the question has been found
        if ($object = Database::fetch_object($result)) {

            $objQuestion = Question::getInstance($object->type);
            if (!empty($objQuestion)) {

                $objQuestion->id            = $id;
                $objQuestion->question      = $object->question;
                $objQuestion->description   = $object->description;
                $objQuestion->weighting     = $object->ponderation;
                $objQuestion->position      = $object->position;
                $objQuestion->type          = $object->type;
                $objQuestion->picture       = $object->picture;
                $objQuestion->level         = (int)$object->level;
                $objQuestion->extra         = $object->extra;
                $objQuestion->course        = $course_info;
                $objQuestion->parent_id     = $object->parent_id;
                $objQuestion->category_list = Testcategory::getCategoryForQuestion($id);
                $objQuestion->exercise      = $exercise;
                $objQuestion->c_id          = $object->c_id;

                $sql = "SELECT exercice_id FROM $TBL_EXERCICE_QUESTION WHERE question_id = $id";
                $result_exercise_list = Database::query($sql);

                // fills the array with the exercises which this question is in
                if ($result_exercise_list) {
                    while ($obj = Database::fetch_object($result_exercise_list)) {
                        $objQuestion->exerciseList[] = $obj->exercice_id;
                    }
                }

                return $objQuestion;
            }
        }

        // question not found
        return false;
    }

    public function setEditionMode($mode)
    {
        $this->editionMode = $mode;
    }

    public function getEditionMode()
    {
        return $this->editionMode;
    }

    /**
     * Returns the question ID
     *
     * @author - Olivier Brouckaert
     * @return int - question ID
     */
    public function selectId()
    {
        return $this->id;
    }

    /**
     * Returns the question title
     *
     * @author - Olivier Brouckaert
     * @return string - question title
     */
    public function selectTitle()
    {
        return $this->question;
    }

    /**
     * returns the question description
     *
     * @author - Olivier Brouckaert
     * @return string - question description
     */
    public function selectDescription()
    {
        $this->description = $this->description;

        return $this->description;
    }

    /**
     * returns the question weighting
     *
     * @author - Olivier Brouckaert
     * @return int - question weighting
     */
    public function selectWeighting()
    {
        return $this->weighting;
    }

    /**
     * returns the question position
     *
     * @author - Olivier Brouckaert
     * @return int - question position
     */
    public function selectPosition()
    {
        return $this->position;
    }

    /**
     * returns the answer type
     *
     * @author - Olivier Brouckaert
     * @return int - answer type
     */
    public function selectType()
    {
        return $this->type;
    }

    /**
     * returns the level of the question
     *
     * @author - Nicolas Raynaud
     * @return int - level of the question, 0 by default.
     */
    public function selectLevel()
    {
        return $this->level;
    }

    /**
     * returns the picture name
     *
     * @author - Olivier Brouckaert
     * @return string - picture name
     */
    public function selectPicture()
    {
        return $this->picture;
    }

    public function selectPicturePath()
    {
        if (!empty($this->picture)) {
            return api_get_path(WEB_COURSE_PATH).$this->course['path'].'/document/images/'.$this->picture;
        }

        return false;
    }

    /**
     * returns the array with the exercise ID list
     *
     * @author - Olivier Brouckaert
     * @return array - list of exercise ID which the question is in
     */
    public function selectExerciseList()
    {
        return $this->exerciseList;
    }

    /**
     * returns the number of exercises which this question is in
     *
     * @author - Olivier Brouckaert
     * @return integer - number of exercises
     */
    public function selectNbrExercises()
    {
        return sizeof($this->exerciseList);
    }

    /**
     * @param $course_id
     */
    public function updateCourse($course_id)
    {
        $this->course = api_get_course_info_by_id($course_id);
    }

    /**
     * changes the question title
     *
     * @author - Olivier Brouckaert
     * @param - string $title - question title
     */
    public function updateTitle($title)
    {
        $this->question = $title;
    }

    public function updateParentId($id)
    {
        $this->parent_id = intval($id);
    }

    /**
     * changes the question description
     *
     * @author - Olivier Brouckaert
     * @param - string $description - question description
     */
    public function updateDescription($description)
    {
        $this->description = $description;
    }

    /**
     * changes the question weighting
     *
     * @author Olivier Brouckaert
     * @param integer $weighting - question weighting
     */
    public function updateWeighting($weighting)
    {
        $this->weighting = $weighting;
    }

    /**
     * @author Hubert Borderiou 12-10-2011
     * @param array of category $in_category
     */
    public function updateCategory($category_list)
    {
        $this->category_list = $category_list;
    }

    /**
     * @author Hubert Borderiou 12-10-2011
     * @param interger $in_positive
     */
    public function updateScoreAlwaysPositive($in_positive)
    {
        $this->scoreAlwaysPositive = $in_positive;
    }

    /**
     * @author Hubert Borderiou 12-10-2011
     * @param interger $in_positive
     */
    public function updateUncheckedMayScore($in_positive)
    {
        $this->uncheckedMayScore = $in_positive;
    }

    /**
     * Save category of a question
     *
     * A question can have n categories
     * if category is empty, then question has no category then delete the category entry
     *
     * @param  array category list
     * @author Julio Montoya - Adding multiple cat support
     */
    public function saveCategories($category_list)
    {
        $course_id = $this->course['real_id'];

        if (!empty($category_list)) {
            $this->deleteCategory();
            $TBL_QUESTION_REL_CATEGORY = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
            $category_list             = array_filter($category_list);

            // update or add category for a question
            foreach ($category_list as $category_id) {
                if (empty($category_id)) {
                    continue;
                }
                $category_id = intval($category_id);
                $question_id = Database::escape_string($this->id);
                $sql         = "SELECT count(*) AS nb FROM $TBL_QUESTION_REL_CATEGORY
                                WHERE category_id = $category_id AND question_id = $question_id AND c_id=".$course_id;

                $res         = Database::query($sql);
                $row         = Database::fetch_array($res);
                if ($row['nb'] > 0) {
                    //DO nothing
                    //$sql = "UPDATE $TBL_QUESTION_REL_CATEGORY SET category_id = $category_id WHERE question_id=$question_id AND c_id=".api_get_course_int_id();
                    //$res = Database::query($sql);
                } else {
                    $sql = "INSERT INTO $TBL_QUESTION_REL_CATEGORY (c_id, question_id, category_id) VALUES (".$course_id.", $question_id, $category_id)";
                    Database::query($sql);
                }
            }
        } else {
            $this->deleteCategory();
        }
    }

    /**
     * @author - Hubert Borderiou 12-10-2011
     * @param - interger $in_positive
     * in this version, a question can only have 1 category
     * if category is 0, then question has no category then delete the category entry
     */
    public function saveCategory($in_category)
    {
        if ($in_category <= 0) {
            $this->deleteCategory();
        } else {
            // update or add category for a question

            $TBL_QUESTION_REL_CATEGORY = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
            $category_id               = Database::escape_string($in_category);
            $question_id               = Database::escape_string($this->id);
            $sql                       = "SELECT count(*) AS nb FROM $TBL_QUESTION_REL_CATEGORY WHERE question_id= $question_id AND c_id=".api_get_course_int_id(
            );
            $res                       = Database::query($sql);
            $row                       = Database::fetch_array($res);
            if ($row['nb'] > 0) {
                $sql = "UPDATE $TBL_QUESTION_REL_CATEGORY SET category_id= $category_id
                        WHERE question_id=$question_id AND c_id=".api_get_course_int_id();
                Database::query($sql);
            } else {
                $sql = "INSERT INTO $TBL_QUESTION_REL_CATEGORY (c_id, question_id, category_id) VALUES (".api_get_course_int_id().", $question_id, $category_id)";
                Database::query($sql);
            }
        }
    }

    /**
     * Deletes any category entry for question id
     * @author hubert borderiou 12-10-2011
     */
    public function deleteCategory()
    {
        $course_id                 = $this->course['real_id'];
        $TBL_QUESTION_REL_CATEGORY = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
        $question_id               = Database::escape_string($this->id);
        $sql                       = "DELETE FROM $TBL_QUESTION_REL_CATEGORY WHERE question_id = $question_id AND c_id = ".$course_id;
        Database::query($sql);
    }

    /**
     * Changes the question position
     *
     * @author Olivier Brouckaert
     * @param integer $position - question position
     */
    public function updatePosition($position)
    {
        $this->position = $position;
    }

    /**
     * Changes the question level
     *
     * @author Nicolas Raynaud
     * @param integer $level - question level
     */
    public function updateLevel($level)
    {
        $this->level = $level;
    }

    /**
     * changes the answer type. If the user changes the type from "unique answer" to "multiple answers"
     * (or conversely) answers are not deleted, otherwise yes
     *
     * @author Olivier Brouckaert
     * @param integer $type - answer type
     */
    public function updateType($type)
    {
        $TBL_REPONSES = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $course_id    = $this->course['real_id'];

        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        }
        // if we really change the type
        if ($type != $this->type) {
            // if we don't change from "unique answer" to "multiple answers" (or conversely)
            if (!in_array($this->type, array(UNIQUE_ANSWER, MULTIPLE_ANSWER)) || !in_array(
                $type,
                array(UNIQUE_ANSWER, MULTIPLE_ANSWER)
            )
            ) {
                // removes old answers
                $sql = "DELETE FROM $TBL_REPONSES WHERE question_id='".Database::escape_string($this->id)."'";
                Database::query($sql);
            }

            $this->type = $type;
        }
    }

    /**
     * Adds a picture to the question
     *
     * @author Olivier Brouckaert
     * @param string $Picture - temporary path of the picture to upload
     * @param string $PictureName - Name of the picture
     * @param string
     * @return bool - true if uploaded, otherwise false
     */
    public function uploadPicture($Picture, $PictureName, $picturePath = null)
    {
        if (empty($picturePath)) {
            global $picturePath;
        }

        if (!file_exists($picturePath)) {
            if (mkdir($picturePath, api_get_permissions_for_new_directories())) {
                // document path
                $documentPath = api_get_path(SYS_COURSE_PATH).$this->course['path']."/document";
                $path         = str_replace($documentPath, '', $picturePath);
                $title_path   = basename($picturePath);
                $doc_id       = FileManager::add_document($this->course, $path, 'folder', 0, $title_path);
                api_item_property_update($this->course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', api_get_user_id());
            }
        }

        // if the question has got an ID
        if ($this->id) {
            $extension     = pathinfo($PictureName, PATHINFO_EXTENSION);
            $this->picture = 'quiz-'.$this->id.'.jpg';
            $o_img         = new Image($Picture);
            $o_img->send_image($picturePath.'/'.$this->picture, -1, 'jpg');
            $document_id = FileManager::add_document(
                $this->course,
                '/images/'.$this->picture,
                'file',
                filesize($picturePath.'/'.$this->picture),
                $this->picture
            );
            if ($document_id) {
                return api_item_property_update(
                    $this->course,
                    TOOL_DOCUMENT,
                    $document_id,
                    'DocumentAdded',
                    api_get_user_id()
                );
            }
        }

        return false;
    }

    /**
     * Resizes a picture || Warning!: can only be called after uploadPicture, or if picture is already available in object.
     *
     * @author - Toon Keppens
     * @param string $Dimension - Resizing happens proportional according to given dimension: height|width|any
     * @param integer $Max - Maximum size
     * @return boolean - true if success, false if failed
     */
    public function resizePicture($Dimension, $Max)
    {
        global $picturePath;

        // if the question has an ID
        if ($this->id) {
            // Get dimensions from current image.
            $my_image = new Image($picturePath.'/'.$this->picture);

            $current_image_size = $my_image->get_image_size();
            $current_width      = $current_image_size['width'];
            $current_height     = $current_image_size['height'];

            if ($current_width < $Max && $current_height < $Max) {
                return true;
            } elseif ($current_height == "") {
                return false;
            }

            // Resize according to height.
            if ($Dimension == "height") {
                $resize_scale = $current_height / $Max;
                $new_height   = $Max;
                $new_width    = ceil($current_width / $resize_scale);
            }

            // Resize according to width
            if ($Dimension == "width") {
                $resize_scale = $current_width / $Max;
                $new_width    = $Max;
                $new_height   = ceil($current_height / $resize_scale);
            }

            // Resize according to height or width, both should not be larger than $Max after resizing.
            if ($Dimension == "any") {
                if ($current_height > $current_width || $current_height == $current_width) {
                    $resize_scale = $current_height / $Max;
                    $new_height   = $Max;
                    $new_width    = ceil($current_width / $resize_scale);
                }
                if ($current_height < $current_width) {
                    $resize_scale = $current_width / $Max;
                    $new_width    = $Max;
                    $new_height   = ceil($current_height / $resize_scale);
                }
            }

            $my_image->resize($new_width, $new_height);
            $result = $my_image->send_image($picturePath.'/'.$this->picture);

            if ($result) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Deletes the picture
     *
     * @author Olivier Brouckaert
     * @return boolean - true if removed, otherwise false
     */
    public function removePicture()
    {
        global $picturePath;

        // if the question has got an ID and if the picture exists
        if ($this->id) {
            $picture       = $this->picture;
            $this->picture = '';

            return @unlink($picturePath.'/'.$picture) ? true : false;
        }

        return false;
    }

    /**
     * Exports a picture to another question
     *
     * @author - Olivier Brouckaert
     * @param integer $questionId - ID of the target question
     * @return boolean - true if copied, otherwise false
     */
    public function exportPicture($questionId, $course_info)
    {
        $course_id        = $course_info['real_id'];
        $TBL_QUESTIONS    = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $destination_path = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/document/images';
        $source_path      = api_get_path(SYS_COURSE_PATH).$this->course['path'].'/document/images';

        // if the question has got an ID and if the picture exists
        if ($this->id && !empty($this->picture)) {
            $picture   = explode('.', $this->picture);
            $extension = $picture[sizeof($picture) - 1];
            $picture   = 'quiz-'.$questionId.'.'.$extension;
            $result    = @copy($source_path.'/'.$this->picture, $destination_path.'/'.$picture) ? true : false;
            //If copy was correct then add to the database
            if ($result) {
                $sql = "UPDATE $TBL_QUESTIONS SET picture='".Database::escape_string($picture)."'
                        WHERE c_id = $course_id AND iid='".intval($questionId)."'";
                Database::query($sql);

                $document_id = FileManager::add_document(
                    $course_info,
                    '/images/'.$picture,
                    'file',
                    filesize($destination_path.'/'.$picture),
                    $picture
                );
                if ($document_id) {
                    return api_item_property_update(
                        $course_info,
                        TOOL_DOCUMENT,
                        $document_id,
                        'DocumentAdded',
                        api_get_user_id()
                    );
                }
            }

            return $result;
        }

        return false;
    }

    /**
     * Saves the picture coming from POST into a temporary file
     * Temporary pictures are used when we don't want to save a picture right after a form submission.
     * For example, if we first show a confirmation box.
     *
     * @author - Olivier Brouckaert
     * @param - string $Picture - temporary path of the picture to move
     * @param - string $PictureName - Name of the picture
     */
    function setTmpPicture($Picture, $PictureName)
    {
        global $picturePath;
        $PictureName = explode('.', $PictureName);
        $Extension   = $PictureName[sizeof($PictureName) - 1];

        // saves the picture into a temporary file
        @move_uploaded_file($Picture, $picturePath.'/tmp.'.$Extension);
    }

    /**
    Sets the title
     */
    public function setTitle($title)
    {
        $this->question = $title;
    }

    /**
    Sets the title
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;
    }

    /**
     * Moves the temporary question "tmp" to "quiz-$questionId"
     * Temporary pictures are used when we don't want to save a picture right after a form submission.
     * For example, if we first show a confirmation box.
     *
     * @author Olivier Brouckaert
     * @return boolean - true if moved, otherwise false
     */
    public function getTmpPicture()
    {
        global $picturePath;

        // if the question has got an ID and if the picture exists
        if ($this->id) {
            if (file_exists($picturePath.'/tmp.jpg')) {
                $Extension = 'jpg';
            } elseif (file_exists($picturePath.'/tmp.gif')) {
                $Extension = 'gif';
            } elseif (file_exists($picturePath.'/tmp.png')) {
                $Extension = 'png';
            }
            $this->picture = 'quiz-'.$this->id.'.'.$Extension;

            return @rename($picturePath.'/tmp.'.$Extension, $picturePath.'/'.$this->picture) ? true : false;
        }

        return false;
    }

    /**
     * updates the question in the data base
     * if an exercise ID is provided, we add that exercise ID into the exercise list
     *
     * @author Olivier Brouckaert
     * @param integer $exerciseId - exercise ID if saving in an exercise
     */
    public function save($exerciseId = 0)
    {
        $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $TBL_QUESTIONS         = Database::get_course_table(TABLE_QUIZ_QUESTION);

        $id            = $this->id;
        $question      = $this->question;
        $description   = $this->description;
        $weighting     = $this->weighting;
        $position      = $this->position;
        $type          = $this->type;
        $picture       = $this->picture;
        $level         = $this->level;
        $extra         = $this->extra;
        $c_id          = $this->course['real_id'];
        $category_list = $this->category_list;

        // Question already exists
        if (!empty($id)) {
            $sql = "UPDATE $TBL_QUESTIONS SET
                        question 	='".Database::escape_string($question)."',
                        description	='".Database::escape_string($description)."',
                        ponderation	='".Database::escape_string($weighting)."',
                        position	='".Database::escape_string($position)."',
                        type		='".Database::escape_string($type)."',
                        picture		='".Database::escape_string($picture)."',
                        extra       ='".Database::escape_string($extra)."',
                        level		='".Database::escape_string($level)."',
                        parent_id   = ".$this->parent_id."
                    WHERE iid = '".Database::escape_string($id)."'";
            //WHERE c_id = $c_id AND iid = '".Database::escape_string($id)."'";

            Database::query($sql);

            $this->saveCategories($category_list);

            if (!empty($exerciseId)) {
                api_item_property_update($this->course, TOOL_QUIZ, $id, 'QuizQuestionUpdated', api_get_user_id());
                if (api_get_setting('search_enabled') == 'true') {
                    $this->search_engine_edit($exerciseId);
                }
            }
        } else {
            // Creates a new question
            $sql = "SELECT max(position) FROM $TBL_QUESTIONS as question, $TBL_EXERCICE_QUESTION as test_question
				    WHERE 	question.iid					= test_question.question_id AND
					        test_question.exercice_id	= '".Database::escape_string($exerciseId)."' AND
							question.c_id 				= $c_id AND
							test_question.c_id 			= $c_id ";
            $result = Database::query($sql);
            $current_position = Database::result($result, 0, 0);
            $this->updatePosition($current_position + 1);
            $position = $this->position;
            $sql = "INSERT INTO $TBL_QUESTIONS (c_id, question, description, ponderation, position, type, picture, extra, level, parent_id) VALUES ( ".
                " $c_id, ".
                " '".Database::escape_string($question)."', ".
                " '".Database::escape_string($description)."', ".
                " '".Database::escape_string($weighting)."', ".
                " '".Database::escape_string($position)."', ".
                " '".Database::escape_string($type)."', ".
                " '".Database::escape_string($picture)."', ".
                " '".Database::escape_string($extra)."', ".
                " '".Database::escape_string($level)."', ".
                " '".$this->parent_id."' ".
                " )";
            Database::query($sql);

            $this->id = Database::insert_id();

            api_item_property_update($this->course, TOOL_QUIZ, $this->id, 'QuizQuestionAdded', api_get_user_id());

            // If hotspot, create first answer
            if ($type == HOT_SPOT || $type == HOT_SPOT_ORDER) {
                $TBL_ANSWERS = Database::get_course_table(TABLE_QUIZ_ANSWER);
                $sql = "INSERT INTO $TBL_ANSWERS (question_id , answer , correct , comment , ponderation , position , hotspot_coordinates , hotspot_type )
					    VALUES ('".Database::escape_string($this->id)."', '', NULL , '', '10' , '1', '0;0|0|0', 'square')";
                Database::query($sql);
            }

            if ($type == HOT_SPOT_DELINEATION) {
                $TBL_ANSWERS = Database::get_course_table(TABLE_QUIZ_ANSWER);
                $sql = "INSERT INTO $TBL_ANSWERS (question_id , answer , correct , comment , ponderation , position , hotspot_coordinates , hotspot_type )
					    VALUES ('".Database::escape_string($this->id)."', '', NULL , '', '10' , '1', '0;0|0|0', 'delineation')";
                Database::query($sql);
            }

            if (api_get_setting('search_enabled') == 'true') {
                if ($exerciseId != 0) {
                    $this->search_engine_edit($exerciseId, true);
                }
            }
        }

        // if the question is created in an exercise
        if ($exerciseId) {
            // adds the exercise into the exercise list of this question
            $this->addToList($exerciseId, true);
        }
    }

    /**
     * @param $exerciseId
     * @param bool $addQs
     * @param bool $rmQs
     */
    public function search_engine_edit($exerciseId, $addQs = false, $rmQs = false)
    {
        // update search engine and its values table if enabled
        if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
            $course_id = api_get_course_id();
            // get search_did
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            if ($addQs || $rmQs) {
                //there's only one row per question on normal db and one document per question on search engine db
                $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_second_level=%s LIMIT 1';
                $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
            } else {
                $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level=%s LIMIT 1';
                $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $exerciseId, $this->id);
            }
            $res = Database::query($sql);

            if (Database::num_rows($res) > 0 || $addQs) {
                require_once(api_get_path(LIBRARY_PATH).'search/ChamiloIndexer.class.php');
                require_once(api_get_path(LIBRARY_PATH).'search/IndexableChunk.class.php');

                $di = new ChamiloIndexer();
                if ($addQs) {
                    $question_exercises = array((int)$exerciseId);
                } else {
                    $question_exercises = array();
                }
                isset($_POST['language']) ? $lang = Database::escape_string($_POST['language']) : $lang = 'english';
                $di->connectDb(null, null, $lang);

                // retrieve others exercise ids
                $se_ref = Database::fetch_array($res);
                $se_doc = $di->get_document((int)$se_ref['search_did']);
                if ($se_doc !== false) {
                    if (($se_doc_data = $di->get_document_data($se_doc)) !== false) {
                        $se_doc_data = unserialize($se_doc_data);
                        if (isset($se_doc_data[SE_DATA]['type']) && $se_doc_data[SE_DATA]['type'] == SE_DOCTYPE_EXERCISE_QUESTION) {
                            if (isset($se_doc_data[SE_DATA]['exercise_ids']) && is_array(
                                $se_doc_data[SE_DATA]['exercise_ids']
                            )
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
                $xapian_data           = array(
                    SE_COURSE_ID => $course_id,
                    SE_TOOL_ID   => TOOL_QUIZ,
                    SE_DATA      => array(
                        'type'         => SE_DOCTYPE_EXERCISE_QUESTION,
                        'exercise_ids' => $question_exercises,
                        'question_id'  => (int)$this->id
                    ),
                    SE_USER      => (int)api_get_user_id(),
                );
                $ic_slide->xapian_data = serialize($xapian_data);
                $ic_slide->addValue("content", $this->description);

                //TODO: index answers, see also form validation on question_admin.inc.php

                $di->remove_document((int)$se_ref['search_did']);
                $di->addChunk($ic_slide);

                //index and return search engine document id
                if (!empty($question_exercises)) { // if empty there is nothing to index
                    $did = $di->index();
                    unset($di);
                }
                if ($did || $rmQs) {
                    // save it to db
                    if ($addQs || $rmQs) {
                        $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_second_level=\'%s\'';
                        $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $this->id);
                    } else {
                        $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=\'%s\' AND ref_id_second_level=\'%s\'';
                        $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $exerciseId, $this->id);
                    }
                    Database::query($sql);
                    if ($rmQs) {
                        if (!empty($question_exercises)) {
                            $sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, ref_id_second_level, search_did)
                              VALUES (NULL , \'%s\', \'%s\', %s, %s, %s)';
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
                        $sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, ref_id_second_level, search_did)
                            VALUES (NULL , \'%s\', \'%s\', %s, %s, %s)';
                        $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, $exerciseId, $this->id, $did);
                        Database::query($sql);
                    }
                }

            }
        }

    }

    /**
     * adds an exercise into the exercise list
     *
     * @author - Olivier Brouckaert
     * @param integer $exerciseId - exercise ID
     * @param boolean $fromSave - coming from $this->save() or not
     */
    public function addToList($exerciseId, $fromSave = false)
    {
        $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $id                    = $this->id;
        // checks if the exercise ID is not in the list

        if (!in_array($exerciseId, $this->exerciseList)) {
            $this->exerciseList[] = $exerciseId;
            $new_exercise = new Exercise();
            $new_exercise->read($exerciseId);
            $count = $new_exercise->selectNbrQuestions();
            $count++;

            $sql = "INSERT INTO $TBL_EXERCICE_QUESTION (c_id, question_id, exercice_id, question_order) VALUES
				 ({$this->course['real_id']}, '".Database::escape_string($id)."','".Database::escape_string($exerciseId)."', '$count' )";
            Database::query($sql);

            // we do not want to reindex if we had just saved adnd indexed the question
            if (!$fromSave) {
                $this->search_engine_edit($exerciseId, true);
            }
        }
    }

    /**
     * Removes an exercise from the exercise list
     *
     * @author Olivier Brouckaert
     * @param integer $exerciseId - exercise ID
     * @return boolean - true if removed, otherwise false
     */
    public function removeFromList($exerciseId)
    {
        $TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);

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
            $sql = "SELECT question_order FROM $TBL_EXERCICE_QUESTION
                    WHERE c_id = $course_id AND question_id='".Database::escape_string($id)."' AND exercice_id='".Database::escape_string($exerciseId)."'";
            $res = Database::query($sql);
            if (Database::num_rows($res) > 0) {
                $row = Database::fetch_array($res);
                if (!empty($row['question_order'])) {
                    $sql = "UPDATE $TBL_EXERCICE_QUESTION SET question_order = question_order-1
                            WHERE c_id = $course_id AND exercice_id='".Database::escape_string($exerciseId)."' AND question_order > ".$row['question_order'];
                    Database::query($sql);
                }
            }

            $sql = "DELETE FROM $TBL_EXERCICE_QUESTION
            WHERE c_id = $course_id AND question_id='".Database::escape_string($id)."' AND exercice_id='".Database::escape_string($exerciseId)."'";
            Database::query($sql);

            return true;
        }
    }

    /**
     * Deletes a question from the database
     * the parameter tells if the question is removed from all exercises (value = 0),
     * or just from one exercise (value = exercise ID)
     *
     * @author Olivier Brouckaert
     * @param integer $deleteFromEx - exercise ID if the question is only removed from one exercise
     */
    public function delete($deleteFromEx = 0)
    {
        $course_id = api_get_course_int_id();

        $TBL_EXERCICE_QUESTION          = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $TBL_QUESTIONS                  = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $TBL_REPONSES                   = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $TBL_QUIZ_QUESTION_REL_CATEGORY = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);

        $id = $this->id;

        if ($this->type == MEDIA_QUESTION) {
            // Removing media for attached questions

            $sql = "UPDATE $TBL_QUESTIONS SET parent_id = '' WHERE parent_id = $id";
            Database::query($sql);

            $sql = "DELETE FROM $TBL_QUESTIONS WHERE c_id = $course_id AND iid='".Database::escape_string($id)."'";
            Database::query($sql);
            return true;
        }

        // if the question must be removed from all exercises
        if (!$deleteFromEx) {

            //update the question_order of each question to avoid inconsistencies
            $sql = "SELECT exercice_id, question_order FROM $TBL_EXERCICE_QUESTION
                    WHERE c_id = $course_id AND question_id='".Database::escape_string($id)."'";
            $res = Database::query($sql);

            if (Database::num_rows($res) > 0) {
                while ($row = Database::fetch_array($res)) {
                    if (!empty($row['question_order'])) {
                        $sql = "UPDATE $TBL_EXERCICE_QUESTION
                                SET question_order = question_order - 1
                                WHERE c_id = $course_id AND
                                exercice_id='".Database::escape_string($row['exercice_id'])."' AND
                                question_order > ".$row['question_order'];
                        Database::query($sql);
                    }
                }
            }

            $sql = "DELETE FROM $TBL_EXERCICE_QUESTION WHERE c_id = $course_id AND question_id='".Database::escape_string($id)."'";
            Database::query($sql);

            $sql = "DELETE FROM $TBL_QUESTIONS WHERE c_id = $course_id AND iid='".Database::escape_string($id)."'";
            Database::query($sql);

            $sql = "DELETE FROM $TBL_REPONSES WHERE question_id='".Database::escape_string($id)."'";
            Database::query($sql);

            // remove the category of this question in the question_rel_category table
            $sql = "DELETE FROM $TBL_QUIZ_QUESTION_REL_CATEGORY
                    WHERE c_id = $course_id AND question_id='".Database::escape_string($id)."' AND c_id=".api_get_course_int_id();
            Database::query($sql);

            api_item_property_update($this->course, TOOL_QUIZ, $id, 'QuizQuestionDeleted', api_get_user_id());
            $this->removePicture();

            // resets the object
            $this->Question();
        } else {
            // just removes the exercise from the list
            $this->removeFromList($deleteFromEx);
            if (api_get_setting('search_enabled') == 'true' && extension_loaded('xapian')) {
                // disassociate question with this exercise
                $this->search_engine_edit($deleteFromEx, false, true);
            }
            api_item_property_update($this->course, TOOL_QUIZ, $id, 'QuizQuestionDeleted', api_get_user_id());

        }
    }

    /**
     * Duplicates the question
     *
     * @author Olivier Brouckaert
     * @param  array   Course info of the destination course
     * @return int     ID of the new question
     */

    public function duplicate($course_info = null)
    {
        if (empty($course_info)) {
            $course_info = $this->course;
        }
        $TBL_QUESTIONS        = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $TBL_QUESTION_OPTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);

        $question    = $this->question;
        $description = $this->description;

        //Using the same method used in the course copy to transform URLs

        if ($this->course['id'] != $course_info['id']) {
            $description = DocumentManager::replace_urls_inside_content_html_from_copy_course(
                $description,
                $this->course['id'],
                $course_info['id']
            );
            $question    = DocumentManager::replace_urls_inside_content_html_from_copy_course(
                $question,
                $this->course['id'],
                $course_info['id']
            );
        }

        $course_id = $course_info['real_id'];

        //Read the source options
        $options = self::readQuestionOption($this->id, $this->course['real_id']);

        //Inserting in the new course db / or the same course db
        $params          = array(
            'c_id'        => $course_id,
            'question'    => $question,
            'description' => $description,
            'ponderation' => $this->weighting,
            'position'    => $this->position,
            'type'        => $this->type,
            'level'       => $this->level,
            'extra'       => $this->extra,
            'parent_id'   => $this->parent_id,
        );
        $new_question_id = Database::insert($TBL_QUESTIONS, $params);

        if (!empty($options)) {
            //Saving the quiz_options
            foreach ($options as $item) {
                $item['question_id'] = $new_question_id;
                $item['c_id']        = $course_id;
                unset($item['iid']);
                Database::insert($TBL_QUESTION_OPTIONS, $item);
            }
        }
        $this->duplicate_category_question($new_question_id, $course_id);

        // Duplicates the picture of the hotspot
        $this->exportPicture($new_question_id, $course_info);

        return $new_question_id;
    }

    public function get_categories_from_question()
    {
        return Testcategory::getCategoryForQuestion($this->id);
    }

    public function duplicate_category_question($question_id, $course_id)
    {
        $question   = Question::read($question_id, $course_id);
        $categories = $this->get_categories_from_question();
        if (!empty($categories)) {
            $question->saveCategories($categories);
        }
    }

    public function get_question_type_name()
    {
        $key = self::$questionTypes[$this->type];

        return get_lang($key[1]);
    }

    public static function get_question_type($type)
    {
        if ($type == ORAL_EXPRESSION && api_get_setting('enable_nanogong') != 'true') {
            return null;
        }

        return self::$questionTypes[$type];
    }

    public static function get_question_type_list()
    {
        if (api_get_setting('enable_nanogong') != 'true') {
            self::$questionTypes[ORAL_EXPRESSION] = null;
            unset(self::$questionTypes[ORAL_EXPRESSION]);
        }

        return self::$questionTypes;
    }

    /**
     * Returns an instance of the class corresponding to the type
     * @param integer $type the type of the question
     * @param \Exercise
     * @return \Question an instance of a Question subclass (or of Questionc class by default)
     */
    public static function getInstance($type, Exercise $exercise = null)
    {
        if (!is_null($type)) {
            list($file_name, $class_name) = self::get_question_type($type);
            if (!empty($file_name)) {
                include_once $file_name;
                if (class_exists($class_name)) {
                    $obj = new $class_name();
                    $obj->exercise = $exercise;
                    return $obj;
                } else {
                    echo 'Can\'t instanciate class '.$class_name.' of type '.$type;
                }
            }
        }

        return null;
    }

    /**
     * Creates the form to create / edit a question
     * A subclass can redifine this function to add fields...
     *
     * @param \FormValidator $form the formvalidator instance (by reference)
     * @param array $fck_config
     */
    public function createForm(&$form, $fck_config = array())
    {
        $maxCategories = 1;
        $url = api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?1=1';
        $js = null;

        if ($this->type != MEDIA_QUESTION) {
            $js = '<script>

            function check() {
                var counter = 0;
                $("#category_id option:selected").each(function() {
                    var id = $(this).val();
                    var name = $(this).text();
                    if (id != "" ) {
                        // if a media question was selected
                        $("#parent_id option:selected").each(function() {
                            var questionId = $(this).val();
                            if (questionId != 0) {
                                if (counter >= 1) {
                                    alert("'.addslashes(get_lang('YouCantAddAnotherCategory')).'");
                                    $("#category_id").trigger("removeItem",[{ "value" : id}]);
                                    return;
                                }
                            }
                        });

                        $.ajax({
                            async: false,
                            url: "'.$url.'&a=exercise_category_exists",
                            data: "id="+id,
                            success: function(return_value) {
                                if (return_value == 0 ) {
                                    alert("'.addslashes(get_lang('CategoryDoesNotExists')).'");
                                    // Deleting select option tag
                                    $("#category_id").find("option").remove();

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
                $("#category_id").fcbkcomplete({
                    json_url: "'.$url.'&a=search_category_parent&type=all&filter_by_global='.$this->exercise->getGlobalCategoryId().'",
                    maxitems: "'.$maxCategories.'",
                    addontab: false,
                    input_min_size: 1,
                    cache: false,
                    complete_text:"'.get_lang('StartToType').'",
                    firstselected: false,
                    onselect: check,
                    filter_selected: true,
                    newel: true
                });

                // Change select media
                $("#parent_id").change(function(){
                    $("#parent_id option:selected").each(function() {
                        var questionId = $(this).val();
                        if (questionId != 0) {
                            $.ajax({
                                async: false,
                                dataType: "json",
                                url: "'.$url.'&a=get_categories_by_media&questionId='.$this->id.'&exerciseId='.$this->exercise->id.'",
                                data: "mediaId="+questionId,
                                success: function(data) {
                                    if (data != -1) {
                                        var all = $("#category_id").trigger("selectAll");
                                        all.each(function(index, value) {
                                            var selected = $(value).find("option:selected");
                                            selected.each(function( indexSelect, valueSelect) {
                                                valueToRemove = $(valueSelect).val();
                                                $("#category_id").trigger("removeItem",[{ "value" : valueToRemove}]);
                                            });
                                        });

                                        if (data != 0) {
                                            $("#category_id").trigger("addItem",[{"title": data.title, "value": data.value}]);
                                        }
                                    }
                                },
                            });
                        } else {

                            // Removes all items
                            var all = $("#category_id").trigger("selectAll");
                            all.each(function(index, value) {
                                var selected = $(value).find("option:selected");
                                selected.each(function( indexSelect, valueSelect) {
                                    valueToRemove = $(valueSelect).val();
                                    $("#category_id").trigger("removeItem", [{ "value" : valueToRemove}]);
                                });
                            });
                        }
                    });
                });
            });

            // hub 13-12-2010
            function visiblerDevisibler(in_id) {
                if (document.getElementById(in_id)) {

                    if (document.getElementById(in_id).style.display == "none") {
                        document.getElementById(in_id).style.display = "block";
                        if (document.getElementById(in_id+"Img")) {
                            document.getElementById(in_id+"Img").html = "'.addslashes(Display::return_icon('div_hide.gif')).'";
                        }
                    } else {
                        document.getElementById(in_id).style.display = "none";
                        if (document.getElementById(in_id+"Img")) {
                            document.getElementById(in_id+"Img").html = "dsdsds'.addslashes(Display::return_icon('div_show.gif')).'";
                        }
                    }
                }
            }
            </script>';
            $form->addElement('html', $js);
        }

        // question name
        $form->addElement('text', 'questionName', get_lang('Question'), array('class' => 'span6'));
        $form->addRule('questionName', get_lang('GiveQuestion'), 'required');

        // Default content.
        $isContent = isset($_REQUEST['isContent']) ? intval($_REQUEST['isContent']) : null;

        // Question type
        $answerType = isset($_REQUEST['answerType']) ? intval($_REQUEST['answerType']) : $this->selectType();
        $form->addElement('hidden', 'answerType', $answerType);

        // html editor
        $editor_config = array('ToolbarSet' => 'TestQuestionDescription', 'Width' => '100%', 'Height' => '150');
        if (is_array($fck_config)) {
            $editor_config = array_merge($editor_config, $fck_config);
        }

        if (!api_is_allowed_to_edit(null, true)) {
            $editor_config['UserStatus'] = 'student';
        }

        $form->add_html_editor('questionDescription', get_lang('QuestionDescription'), false, false, $editor_config);

        // hidden values
        $my_id = isset($_REQUEST['myid']) ? intval($_REQUEST['myid']) : null;
        $form->addElement('hidden', 'myid', $my_id);

        if ($this->type != MEDIA_QUESTION) {

            if ($this->exercise->fastEdition == false) {
                // Advanced parameters
                $form->addElement('advanced_settings', 'advanced_params', get_lang('AdvancedParameters'));
                $form->addElement('html', '<div id="advanced_params_options" style="display:none;">');
            }

            // Level (difficulty).
            $select_level = Question::get_default_levels();
            $form->addElement('select', 'questionLevel', get_lang('Difficulty'), $select_level);

            // Media question.

            $course_medias = Question::prepare_course_media_select(api_get_course_int_id());
            $form->addElement('select', 'parent_id', get_lang('AttachToMedia'), $course_medias, array('id' => 'parent_id'));

            // Categories.
            $categoryJS = null;

            if (!empty($this->category_list)) {
                $trigger = '';
                foreach ($this->category_list as $category_id) {
                    if (!empty($category_id)) {
                        $cat = new Testcategory($category_id);
                        if ($cat->id) {
                            $trigger .= '$("#category_id").trigger("addItem",[{"title": "'.$cat->parent_path.'", "value": "'.$cat->id.'"}]);';
                        }
                    }
                }
                $categoryJS .= '<script>$(function() { '.$trigger.' });</script>';
            }
            $form->addElement('html', $categoryJS);

            $form->addElement(
                'select',
                'questionCategory',
                get_lang('Category'),
                array(),
                array('id' => 'category_id')
            );

            // Extra fields. (Injecting question extra fields!)
            $extraFields = new ExtraField('question');
            $extraFields->addElements($form, $this->id);

            if ($this->exercise->fastEdition == false) {
                $form->addElement('html', '</div>');
            }
        }

        // @todo why we need this condition??
        if ($this->setDefaultQuestionValues) {
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

        // default values
        $defaults = array();
        $defaults['questionName']        = $this->question;
        $defaults['questionDescription'] = $this->description;
        $defaults['questionLevel']       = $this->level;
        $defaults['questionCategory']    = $this->category_list;
        $defaults['parent_id']           = $this->parent_id;

        if (!empty($_REQUEST['myid'])) {
            $form->setDefaults($defaults);
        } else {
            if ($isContent == 1) {
                $form->setDefaults($defaults);
            }
        }

        if ($this->setDefaultValues) {
            $form->setDefaults($defaults);
        }

    }

    /**
     * function which process the creation of questions
     * @param FormValidator $form the formvalidator instance
     * @param Exercise $objExercise the Exercise instance
     */
    public function processCreation($form, $objExercise = null)
    {
        $this->updateParentId($form->getSubmitValue('parent_id'));
        $this->updateTitle($form->getSubmitValue('questionName'));
        $this->updateDescription($form->getSubmitValue('questionDescription'));
        $this->updateLevel($form->getSubmitValue('questionLevel'));
        $this->updateCategory($form->getSubmitValue('questionCategory'));

        // Save normal question if NOT media
        if ($this->type != MEDIA_QUESTION) {
            $this->save($objExercise->id);

            $field_value = new ExtraFieldValue('question');
            $params = $form->getSubmitValues();
            $params['question_id'] = $this->id;
            $field_value->save_field_values($params);

            if ($objExercise) {
                // modify the exercise
                $objExercise->addToList($this->id);
                $objExercise->update_question_positions();
            }
        }
    }

    /**
     * abstract function which creates the form to create / edit the answers of the question
     * @param FormValidator instance
     */
    abstract public function createAnswersForm($form);

    /**
     * abstract function which process the creation of answers
     * @param FormValidator instance
     */
    abstract public function processAnswersCreation($form);

    /**
     * Displays the menu of question types
     * @param Exercise $objExercise
     */
    public static function display_type_menu(Exercise $objExercise)
    {
        $feedback_type = $objExercise->feedback_type;
        $exerciseId    = $objExercise->id;

        // 1. by default we show all the question types
        $question_type_custom_list = self::get_question_type_list();

        if (!isset($feedback_type)) {
            $feedback_type = 0;
        }

        if ($feedback_type == 1) {
            //2. but if it is a feedback DIRECT we only show the UNIQUE_ANSWER type that is currently available
            $question_type_custom_list = array(
                UNIQUE_ANSWER        => self::$questionTypes[UNIQUE_ANSWER],
                HOT_SPOT_DELINEATION => self::$questionTypes[HOT_SPOT_DELINEATION]
            );
        } else {
            unset($question_type_custom_list[HOT_SPOT_DELINEATION]);
        }

        echo '<div class="actionsbig">';
        echo '<ul class="question_menu">';
        $modelType = $objExercise->getModelType();

        foreach ($question_type_custom_list as $i => $a_type) {
            if ($modelType == EXERCISE_MODEL_TYPE_COMMITTEE) {
                if ($a_type[1] != 'FreeAnswer') {
                    continue;
                }
            }

            // include the class of the type
            require_once $a_type[0];
            // get the picture of the type and the langvar which describes it
            $img = $explanation = '';
            eval('$img = '.$a_type[1].'::$typePicture;');
            eval('$explanation = get_lang('.$a_type[1].'::$explanationLangVar);');
            echo '<li>';
            echo '<div class="icon_image_content">';
            if ($objExercise->exercise_was_added_in_lp == true) {
                $img = pathinfo($img);
                $img = $img['filename'].'_na.'.$img['extension'];
                echo Display::return_icon($img, $explanation, array(), ICON_SIZE_BIG);
            } else {
                echo '<a href="admin.php?'.api_get_cidreq(
                ).'&newQuestion=yes&answerType='.$i.'&exerciseId='.$exerciseId.'">'.Display::return_icon(
                    $img,
                    $explanation,
                    array(),
                    ICON_SIZE_BIG
                ).'</a>';
            }
            echo '</div>';
            echo '</li>';
        }

        echo '<li>';
        echo '<div class="icon_image_content">';
        if ($objExercise->exercise_was_added_in_lp == true) {
            echo Display::return_icon('database_na.png', get_lang('GetExistingQuestion'));
        } else {
            if ($feedback_type == 1) {
                //echo $url = '<a href="question_pool.php?'.api_get_cidreq().'&type=1&fromExercise='.$exerciseId.'">';
            } else {
                //echo $url = '<a href="question_pool.php?'.api_get_cidreq().'&fromExercise='.$exerciseId.'">';
            }
            echo $url = '<a href="'.api_get_path(WEB_PUBLIC_PATH).'courses/'.api_get_course_path().'/'.api_get_session_id().'/exercise/'.$exerciseId.'/question-pool">';
            echo Display::return_icon('database.png', get_lang('GetExistingQuestion'));
        }
        echo '</a>';
        echo '</div></li>';
        echo '</ul>';
        echo '</div>';
    }

    public static function saveQuestionOption($question_id, $name, $course_id, $position = 0)
    {
        $TBL_EXERCICE_QUESTION_OPTION = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);
        $params['question_id']        = intval($question_id);
        $params['name']               = $name;
        $params['position']           = $position;
        $params['c_id']               = $course_id;
        //$result  = self::readQuestionOption($question_id, $course_id);
        $last_id = Database::insert($TBL_EXERCICE_QUESTION_OPTION, $params);

        return $last_id;
    }

    public static function deleteAllQuestionOptions($question_id, $course_id)
    {
        $TBL_EXERCICE_QUESTION_OPTION = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);
        Database::delete(
            $TBL_EXERCICE_QUESTION_OPTION,
            array('c_id = ? AND question_id = ?' => array($course_id, $question_id))
        );
    }

    public static function updateQuestionOption($id, $params, $course_id)
    {
        $TBL_EXERCICE_QUESTION_OPTION = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);
        $result = Database::update($TBL_EXERCICE_QUESTION_OPTION, $params, array('c_id = ? AND id = ?' => array($course_id, $id)));

        return $result;
    }

    /**
     * @param int $question_id
     * @param int $course_id
     * @return array
     */
    public static function readQuestionOption($question_id, $course_id)
    {
        $TBL_EXERCICE_QUESTION_OPTION = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);
        $result = Database::select('*', $TBL_EXERCICE_QUESTION_OPTION, array(
            'where' => array(
                'c_id = ? AND question_id = ?' => array($course_id, $question_id)),
                'order' => 'iid ASC'
            )
        );
        if (!empty($result)) {
            $new_result = array();
            foreach ($result as $item) {
                $new_result[$item['iid']] = $item;
            }

            return $new_result;
        }

        return array();
    }

    /**
     * Shows question title an description
     *
     * @param int $feedback_type
     * @param int $counter
     * @param array $score
     * @param bool $show_media
     * @param int $hideTitle
     *
     * @return string
     */
    public function return_header($feedbackType = null, $counter = null, $score = null, $show_media = false, $hideTitle = 0)
    {
        $counterLabel = null;
        if (!empty($counter)) {
            $counterLabel = $counter;
        }

        $score_label = get_lang('Wrong');
        $class       = 'error';
        if ($score['pass'] == true) {
            $score_label = get_lang('Correct');
            $class       = 'success';
        }

        if ($this->type == FREE_ANSWER || $this->type == ORAL_EXPRESSION) {
            if ($score['revised'] == true) {
                $score_label = get_lang('Revised');
                $class       = '';
            } else {
                $score_label = get_lang('NotRevised');
                $class       = 'error';
            }
        }


        $header = null;
        // Display question category, if any
        if ($show_media) {
            $header .= $this->show_media_content();
        }
        if ($hideTitle == 1) {
            $header .= Display::page_subheader2($counterLabel);
        } else {
            $header .= Display::page_subheader2($counterLabel.". ".$this->question);
        }
        $header .= Display::div(
            '<div class="rib rib-'.$class.'"><h3>'.$score_label.'</h3></div><h4>'.$score['result'].' </h4>',
            array('class' => 'ribbon')
        );
        $header .= Display::div($this->description, array('id' => 'question_description'));

        return $header;
    }

    /**
     * Create a question from a set of parameters
     * @param   int     Quiz ID
     * @param   string  Question name
     * @param   int     Maximum result for the question
     * @param   int     Type of question (see constants at beginning of question.class.php)
     * @param   int     Question level/category
     */
    public function create_question($quiz_id, $question_name, $max_score = 0, $type = 1, $level = 1)
    {
        $course_id = api_get_course_int_id();

        $tbl_quiz_question     = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $tbl_quiz_rel_question = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);

        $quiz_id   = intval($quiz_id);
        $max_score = (float)$max_score;
        $type      = intval($type);
        $level     = intval($level);

        // Get the max position
        $sql          = "SELECT max(position) as max_position"
            ." FROM $tbl_quiz_question q INNER JOIN $tbl_quiz_rel_question r"
            ." ON q.iid = r.question_id"
            ." AND exercice_id = $quiz_id AND q.c_id = $course_id AND r.c_id = $course_id";
        $rs_max       = Database::query($sql);
        $row_max      = Database::fetch_object($rs_max);
        $max_position = $row_max->max_position + 1;

        // Insert the new question
        $sql = "INSERT INTO $tbl_quiz_question (c_id, question, ponderation, position, type, level)
                VALUES ($course_id, '".Database::escape_string($question_name)."', '$max_score', $max_position, $type, $level)";
        Database::query($sql);
        // Get the question ID
        $question_id = Database::insert_id();

        // Get the max question_order
        $sql = "SELECT max(question_order) as max_order FROM $tbl_quiz_rel_question
                WHERE c_id = $course_id AND exercice_id = $quiz_id ";
        $rs_max_order  = Database::query($sql);
        $row_max_order = Database::fetch_object($rs_max_order);
        $max_order     = $row_max_order->max_order + 1;
        // Attach questions to quiz
        $sql = "INSERT INTO $tbl_quiz_rel_question (c_id, question_id, exercice_id, question_order)
                VALUES ($course_id, $question_id, $quiz_id, $max_order)";
        Database::query($sql);

        return $question_id;
    }

    /**
     * return the image filename of the question type
     * @todo don't use eval
     */
    public function get_type_icon_html()
    {
        $type = $this->selectType();
        // [0]=file to include [1]=type name
        $tabQuestionList = Question::get_question_type_list();
        require_once $tabQuestionList[$type][0];
        eval('$img = '.$tabQuestionList[$type][1].'::$typePicture;');
        eval('$explanation = get_lang('.$tabQuestionList[$type][1].'::$explanationLangVar);');

        return array($img, $explanation);
    }

    /**
     * Get course medias
     * @param int course id
     */
    public static function get_course_medias(
        $course_id,
        $start = 0,
        $limit = 100,
        $sidx = "question",
        $sord = "ASC",
        $where_condition = array()
    ) {
        $table_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $default_where  = array('c_id = ? AND parent_id = 0 AND type = ?' => array($course_id, MEDIA_QUESTION));
        if (!empty($where_condition)) {
            //$where_condition
        }
        $result = Database::select(
            '*',
            $table_question,
            array(
                'limit' => " $start, $limit",
                'where' => $default_where,
                'order' => "$sidx $sord"
            )
        );

        return $result;
    }

    /**
     * Get count course medias
     * @param int course id
     */
    public static function get_count_course_medias($course_id)
    {
        $table_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $result         = Database::select(
            'count(*) as count',
            $table_question,
            array('where' => array('c_id = ? AND parent_id = 0 AND type = ?' => array($course_id, MEDIA_QUESTION))),
            'first'
        );

        if ($result && isset($result['count'])) {
            return $result['count'];
        }

        return 0;
    }

    public static function prepare_course_media_select($course_id)
    {
        $medias        = self::get_course_medias($course_id);
        $media_list    = array();
        $media_list[0] = get_lang('NoMedia');

        if (!empty($medias)) {
            foreach ($medias as $media) {
                $media_list[$media['iid']] = empty($media['question']) ? get_lang('Untitled') : $media['question'];
            }
        }

        return $media_list;
    }

    public static function get_default_levels()
    {
        $select_level = array(
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5
        );

        return $select_level;
    }

    public function show_media_content()
    {
        $html = null;
        if ($this->parent_id != 0) {
            $parent_question = Question::read($this->parent_id);
            $html            = $parent_question->show_media_content();
        } else {
            $html .= Display::page_subheader($this->selectTitle());
            $html .= $this->selectDescription();
        }

        return $html;
    }

    public static function question_type_no_review()
    {
        return array(
            HOT_SPOT,
            HOT_SPOT_ORDER,
            HOT_SPOT_DELINEATION
        );
    }

    public static function getMediaLabels()
    {
        // Shows media questions
        $courseMedias = Question::prepare_course_media_select(api_get_course_int_id());
        $labels = null;
        if (!empty($courseMedias)) {
            $labels .= get_lang('MediaQuestions').'<br />';
            foreach ($courseMedias as $mediaId => $media) {
                $editLink  = '<a href="'.api_get_self().'?'.api_get_cidreq().'&type='.MEDIA_QUESTION.'&myid=1&editQuestion='.$mediaId.'">'.Display::return_icon('edit.png',get_lang('Modify'), array(), ICON_SIZE_SMALL).'</a>';
                $deleteLink = '<a id="delete_'.$mediaId.'" class="opener"  href="'.api_get_self().'?'.api_get_cidreq().'&deleteQuestion='.$mediaId.'" >'.Display::return_icon('delete.png',get_lang('Delete'), array(), ICON_SIZE_SMALL).'</a>';

                if (!empty($mediaId)) {
                    $labels .= self::getMediaLabel($media).''.$editLink.$deleteLink.'<br />';
                }
            }
        }

        return $labels;
    }

    /**
     * Get question columns needed for the new question pool page
     * @param int course code
     * @return array
     */
    public static function getQuestionColumns($courseCode = null, $extraFields = array(), $questionFields = array(), $checkFields = false)
    {
        // The order is important you need to check the the $column variable in the model.ajax.php file
        $columns = array('id', get_lang('Name'));

        // Column config.
        $columnModel = array(
            array(
                'name' => 'iid',
                'index' => 'iid',
                'width' => '20',
                'align' => 'left'
            ),
            array(
                'name' => 'question',
                'index' => 'question',
                'width' => '200',
                'align' => 'left'
            )
        );

        // Extra field rules.
        $extraField = new \ExtraField('question');

        $rules = $extraField->getRules($columns, $columnModel, $extraFields, $checkFields);

        // Exercise rules.
        self::getRules($courseCode, $rules, $columns, $columnModel, $questionFields, $checkFields);

        // Adding actions.
        $columns[] = get_lang('Actions');
        $columnModel[] = array(
            'name'      => 'actions',
            'index'     => 'actions',
            'width'     => '30'
        );

        foreach ($columnModel as $col) {
            $simple_column_name[] = $col['name'];
        }

        $return_array =  array(
            'columns' => $columns,
            'column_model' => $columnModel,
            'rules' => $rules,
            'simple_column_name' => $simple_column_name
        );

        return $return_array;
    }

    /**
     * Get all questions
     * @param Application $app
     * @param int $categoryId
     * @param int $exerciseId
     * @param int $courseId
     * @param array $options
     * @param bool $get_count
     * @return array
     */
    public static function getQuestions($app, $categoryId, $exerciseId, $courseId, $options, $get_count = false)
    {
        $questionTable = Database::get_course_table(TABLE_QUIZ_QUESTION);

        $questionPoolFields = array(
            'question_session_id' => array(
                'innerjoin' => " INNER JOIN ".Database::get_course_table(TABLE_QUIZ_TEST_QUESTION)." as quiz_rel_question_session ON (quiz_rel_question_session.question_id = s.iid)
                                 INNER JOIN ".Database::get_course_table(TABLE_QUIZ_TEST)." as quizsession ON (quizsession.iid = quiz_rel_question_session.exercice_id)
                                 INNER JOIN ".Database::get_main_table(TABLE_MAIN_SESSION)." session ON (session.id = quizsession.session_id)",
                'where' => 'session_id',
                'inject_fields' => 'session.name as question_session_id, ',
            ),
            'question_category_id' => array(
                'innerjoin' => " INNER JOIN ".Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY)." as quiz_rel_cat ON (quiz_rel_cat.question_id = s.iid)
                                 INNER JOIN ".Database::get_course_table(TABLE_QUIZ_CATEGORY)." as cat ON (cat.iid = quiz_rel_cat.category_id)",
                'where' =>  'quiz_rel_cat.category_id',
                'inject_fields' => 'cat.title as question_category_id, ',
            ),
            'question_exercise_id' => array(
                'innerjoin' => " INNER JOIN ".Database::get_course_table(TABLE_QUIZ_TEST_QUESTION)." as quiz_rel_question ON (quiz_rel_question.question_id = s.iid)
                                 INNER JOIN ".Database::get_course_table(TABLE_QUIZ_TEST)." as quizexercise ON (quizexercise.iid = quiz_rel_question.exercice_id) ",
                'where' =>  'quiz_rel_question.exercice_id',
                'inject_fields' => 'quizexercise.title as question_exercise_id, ',
            ),
            'question_c_id' => array(
                'where' => 's.c_id',
                'innerjoin' => " INNER JOIN ".Database::get_main_table(TABLE_MAIN_COURSE)." as course ON (course.id = s.c_id) ",
                'inject_fields' => 'course.title as question_c_id, '
            ),
            'question_question_type' => array(
                'where' => 's.type ',
                'inject_fields' => 's.type as question_question_type,'
            ),
            'question_difficulty' => array(
                'where' => 's.level',
                'inject_fields' => 's.level as question_difficulty, '
            )
        );

        // Checking if you're looking for orphan questions.
        $isOrphanQuestion = false;

        if (isset($options['question'])) {
            foreach ($options['question'] as $option) {
                if (isset($option['field']) && $option['field'] == 'question_exercise_id') {
                    if ($option['data'] == 0) {
                        $isOrphanQuestion = true;
                        break;
                    }
                }
            }
        }

        // Special case for orphan questions.
        if ($isOrphanQuestion) {
            $questionPoolFields['question_exercise_id'] = array(
                'innerjoin' => " LEFT JOIN ".Database::get_course_table(TABLE_QUIZ_TEST_QUESTION)." as quiz_rel_question ON (quiz_rel_question.question_id = s.iid)
                                 LEFT JOIN ".Database::get_course_table(TABLE_QUIZ_TEST)." as quizexercise ON (quizexercise.iid = quiz_rel_question.exercice_id) ",
                'where' =>  'quiz_rel_question.exercice_id',
                'inject_fields' => 'quizexercise.title as question_exercise_id, ',
            );
        }

        $inject_extra_fields = null;
        $inject_joins = null;

        $where = $options['where'];

        $newQuestionPoolField = array();

        if (isset($options['question'])) {
            foreach ($options['question'] as $question) {
                if (isset($questionPoolFields[$question['field']])) {
                    $newQuestionPoolField[$question['field']] = $questionPoolFields[$question['field']];
                }
            }
        }

        $inject_question_fields = null;

        $questionPoolFields = $newQuestionPoolField;
        // Injecting inner joins.
        foreach ($questionPoolFields as $field => $option) {
            $where = str_replace($field, $option['where'], $where);
            if (isset($option['innerjoin']) && !empty($option['innerjoin'])) {
                $inject_joins .= $option['innerjoin'];
            }
            if (isset($option['inject_fields']) && !empty($option['inject_fields'])) {
                $inject_question_fields .= $option['inject_fields'];
            }
        }

        $options['where'] = $where;

        $extra_field = new ExtraField('question');
        $conditions = $extra_field->parseConditions($options);

        $inject_joins .= $conditions['inject_joins'];
        $where = $conditions['where'];

        $inject_where = $conditions['inject_where'];
        $inject_extra_fields = $conditions['inject_extra_fields'];
        $order = $conditions['order'];
        $limit = $conditions['limit'];

        if ($get_count == true) {
            $select = " SELECT count(*) as total_rows";
        } else {
            $select = " SELECT s.*, $inject_extra_fields $inject_question_fields 1 ";
        }

        $extraCondition = null;

        // Used by the question manager

        if (!empty($categoryId)) {
            $categoryRelQuestionTable = Database::get_course_table(TABLE_QUIZ_QUESTION_REL_CATEGORY);
            $extraCondition = " INNER JOIN $categoryRelQuestionTable c ON (s.iid = c.question_id)";
            $categoryId = intval($categoryId);
            $where .= " AND category_id = $categoryId ";
        }

        /*if (!empty($exerciseId)) {
            $exerciseRelQuestionTable = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
            $extraCondition .= " INNER JOIN $exerciseRelQuestionTable e ON (s.iid = e.question_id)";
            $exerciseId = intval($exerciseId);
            $where .= " AND exercice_id = $exerciseId ";
        }*/

        // Orphan questions
        if ($isOrphanQuestion) {
            //$exerciseRelQuestionTable = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
            //$extraCondition .= " INNER JOIN $exerciseRelQuestionTable e ON (s.iid = e.question_id)";
            $where .= " OR quizexercise.active = -1 OR quiz_rel_question.exercice_id IS NULL";
        }

        if (!empty($courseId)) {
            $courseId = intval($courseId);
            $where .= " AND s.c_id = $courseId ";
        }

        if (isset($options['question'])) {
            $courseList = CourseManager::get_course_list_of_user_as_course_admin(api_get_user_id());
            foreach ($options['question'] as $questionOption) {
                if ($questionOption['field'] == 'question_c_id') {
                    if (isset($questionOption['data'])) {
                        if (!isset($courseList[$questionOption['data']])) {
                            return array();
                        }
                    }
                }
            }
        }
        //var_dump(CourseManager::get_teacher_list_from_course_code())

        //var_dump($inject_joins);

        $query = " $select FROM $questionTable s $inject_joins $extraCondition WHERE 1=1 $where $inject_where $order $limit";
        //echo $query.'<br />';
        //var_dump($extraCondition);
        //var_dump($where);

        $result = Database::query($query);
        $questions = array();

        $exerciseList = null;
        if (!empty($exerciseId)) {
            $exercise = new Exercise();
            $exercise->read($exerciseId);
            $exerciseList = $exercise->questionList;
        }

        if (Database::num_rows($result)) {
            $questions = Database::store_result($result, 'ASSOC');

            if ($get_count) {
                return $questions[0]['total_rows'];
            }

            $previewIcon = Display::return_icon('preview.gif', get_lang('View'), array(), ICON_SIZE_SMALL);
            $copyIcon = Display::return_icon('copy.png', get_lang('Copy'), array(), ICON_SIZE_SMALL);
            $reuseIcon = Display::return_icon('view_more_stats.gif', get_lang('InsertALinkToThisQuestionInTheExercise'), array(), ICON_SIZE_SMALL);
            $editIcon = Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL);
            //$deleteIcon = Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL);
            //var_dump($exerciseId);
            // Including actions
            foreach ($questions as &$question) {
                $type = self::get_question_type($question['type']);
                $question['type'] = get_lang($type[1]);
                $question['question_question_type'] = get_lang($type[1]);
                if (empty($exerciseId)) {
                    // View.
                    $actions = Display::url(
                        $previewIcon,
                        $app['url_generator']->generate(
                            'admin_questions_show',
                            array(
                                'id' => $question['iid']
                            )
                        )
                    );

                    // Edit.
                    $actions .= Display::url(
                        $editIcon,
                        $app['url_generator']->generate(
                            'admin_questions_edit',
                            array(
                                'id' => $question['iid']
                            )
                        )
                    );
                } else {
                    // View.
                    $actions = Display::url(
                        $previewIcon,
                        $app['url_generator']->generate(
                            'question_show',
                            array(
                                'cidReq' => api_get_course_id(),
                                'id_session' => api_get_session_id(),
                                'exerciseId' => $exerciseId,
                                'id' => $question['iid']
                            )
                        )
                    );

                    if (isset($exerciseList) && !empty($exerciseList) && (in_array($question['iid'], $exerciseList))) {
                        // Copy.
                        //$actions .= $copyIconDisabled;
                    } else {

                        // Copy.
                        $actions .= Display::url(
                            $copyIcon,
                            'javascript:void(0);',
                            array(
                                'onclick' => 'ajaxAction(this);',
                                'data-url' => $app['url_generator']->generate(
                                'exercise_copy_question',
                                array(
                                    'cidReq' => api_get_course_id(),
                                    'id_session' => api_get_session_id(),
                                    'questionId' => $question['iid'],
                                    'exerciseId' => $exerciseId
                                    )
                                )
                            )
                        );

                         // Reuse.
                        $actions .= Display::url(
                            $reuseIcon,
                            'javascript:void(0);',
                            array(
                                'onclick' => 'ajaxAction(this);',
                                'data-url' => $app['url_generator']->generate(
                                'exercise_reuse_question',
                                array(
                                    'cidReq' => api_get_course_id(),
                                    'id_session' => api_get_session_id(),
                                    'questionId' => $question['iid'],
                                    'exerciseId' => $exerciseId
                                )
                                ),
                            )
                        );
                    }

                    // Edit.
                    $actions .= Display::url(
                        $editIcon,
                        $app['url_generator']->generate(
                            'exercise_question_edit',
                            array(
                                'cidReq' => api_get_course_id(),
                                'id_session' => api_get_session_id(),
                                'id' => $question['iid']
                            )
                        )
                    );
                }
                $question['actions'] = $actions;
            }
        }
        return $questions;
    }

    public static function getMediaLabel($title)
    {
        return Display::label($title, 'warning');
    }

    /**
     * @param string $courseCode
     * @param array $rules
     * @param array $columns
     * @param array $column_model
     * @return array
     */
    public static function getRules($courseCode, &$rules, &$columns, &$column_model, $questionFields, $checkFields = false)
    {
        // sessions
        // course
        // categories
        // exercises
        // difficult
        // type

        if (empty($courseCode)) {

            // Session.
            $sessionList = SessionManager::get_sessions_by_general_coach(api_get_user_id());
            $fields = array();
            if (!empty($sessionList)) {
                $new_options = array();
                $new_options[] = "-1:".get_lang('All');
                foreach ($sessionList as $session) {
                    $new_options[] = "{$session['id']}:{$session['name']}";
                }
                $string = implode(';', $new_options);
                $fields[] = array(
                    'field_display_text' => get_lang('Session'),
                    'field_variable' => 'session_id',
                    'field_type' => ExtraField::FIELD_TYPE_SELECT,
                    'field_default_value' => null,
                    'field_options' => $string
                );
            }


        } else {
            // $courseList = array(api_get_course_info());
            //$courseList = CourseManager::get_course_list_of_user_as_course_admin(api_get_user_id());
        }

        // Courses.
        $courseList = CourseManager::get_course_list_of_user_as_course_admin(api_get_user_id());

        if (!empty($courseList)) {
            $new_options = array();
            $new_options[] = "-1:".get_lang('All');
            foreach ($courseList as $course) {
                $new_options[] = "{$course['id']}:{$course['title']}";
            }
            $string = implode(';', $new_options);
            $fields[] = array(
                'field_display_text' => get_lang('Course'),
                'field_variable' => 'c_id',
                'field_type' => ExtraField::FIELD_TYPE_SELECT,
                'field_default_value' => null,
                'field_options' => $string
            );
        }

        // Categories.
        $string = null;
        if (!empty($courseList)) {

            $new_options = array();
            $new_options[] = "-1:".get_lang('All');

            // Global categories

            // @todo use tree view

            $categories = Testcategory::getCategoriesIdAndName(0);
            if (!empty($categories)) {
                foreach ($categories as $id => $category) {
                    if (!empty($id)) {
                        $new_options[] = "$id:[Global] - ".$category;
                    }
                }
            }

            foreach ($courseList as $course) {
                $categories = Testcategory::getCategoriesIdAndName($course['real_id']);
                if (!empty($categories)) {
                    foreach ($categories as $id => $category) {
                        if (!empty($id)) {
                            $new_options[] = "$id:".$course['title']." - ".$category;
                        }
                    }
                }
            }

            $string = implode(';', $new_options);

            $fields[] = array(
                'field_display_text' => get_lang('Category'),
                'field_variable' => 'category_id',
                'field_type' => ExtraField::FIELD_TYPE_SELECT,
                'field_default_value' => null,
                'field_options' => $string
            );
        }

        $course = api_get_course_int_id();
        $sessionId = api_get_session_id();

        // Exercises.
        $exerciseList  = ExerciseLib::get_all_exercises_for_course_id($sessionId, $course);

        if (!empty($exerciseList)) {
            $new_options = array();
            $new_options[] = "-1:".get_lang('All');
            $new_options[] = "0:".get_lang('Orphan');
            foreach ($exerciseList as $exercise) {
                $new_options[] = "{$exercise['iid']}:{$exercise['title']}";
            }
            $string = implode(';', $new_options);
            $fields[] = array(
                'field_display_text' => get_lang('Exercise'),
                'field_variable' => 'exercise_id',
                'field_type' => ExtraField::FIELD_TYPE_SELECT,
                'field_default_value' => null,
                'field_options' => $string
            );
        }

        // Question type.
        $questionList = Question::get_question_type_list();

        if (!empty($questionList)) {
            $new_options = array();
            $new_options[] = "-1:".get_lang('All');
            foreach ($questionList as $key => $question) {
                $new_options[] = "{$key}:".get_lang($question['1']);
            }
            $string = implode(';', $new_options);
            $fields[] = array(
                'field_display_text' => get_lang('AnswerType'),
                'field_variable' => 'question_type',
                'field_type' => ExtraField::FIELD_TYPE_SELECT,
                'field_default_value' => null,
                'field_options' => $string
            );
        }

        // Difficult.
        $levels = Question::get_default_levels();

        if (!empty($levels)) {
            $new_options = array();
            $new_options[] = "-1:".get_lang('All');
            foreach ($levels as $key => $level) {
                $new_options[] ="{$key}:{$level}";
            }
            $string = implode(';', $new_options);
            $fields[] = array(
                'field_display_text' => get_lang('Difficulty'),
                'field_variable' => 'difficulty',
                'field_type' => ExtraField::FIELD_TYPE_SELECT,
                'field_default_value' => null,
                'field_options' => $string
            );
        }

        $questionFieldsKeys = array();
        if (!empty($questionFields)) {
            foreach ($questionFields as $question) {
                $questionFieldsKeys[] = $question['field'];
            }
        }

        if (!empty($fields)) {
            foreach ($fields as $field) {
                $search_options = array();
                $type           = 'text';
                if (in_array($field['field_type'], array(ExtraField::FIELD_TYPE_SELECT, ExtraField::FIELD_TYPE_DOUBLE_SELECT))) {
                    $type                   = 'select';
                    $search_options['sopt'] = array('eq', 'ne'); //equal not equal
                    //$search_options['sopt'] = array('cn', 'nc'); //contains not contains
                } else {
                    $search_options['sopt'] = array('cn', 'nc'); //contains not contains
                }

                $search_options['searchhidden'] = 'true';
                $search_options['defaultValue'] = isset($search_options['field_default_value']) ? $search_options['field_default_value'] : null;
                $search_options['value'] = $field['field_options'];

                $column_model[] = array(
                    'name'          => 'question_'.$field['field_variable'],
                    'index'         => 'question_'.$field['field_variable'],
                    'width'         => '100',
                    'hidden'        => 'true',
                    'search'        => 'true',
                    'stype'         => $type,
                    'searchoptions' => $search_options
                );
                $columns[] = $field['field_display_text'];
                $rules[] = array(
                    'field' => 'question_'.$field['field_variable'],
                    'op' => 'eq'
                );
            }
        }
        return $rules;
    }

    /**
     *
     * @param $exerciseId
     * @param $mediaId
     * @return array|bool
     */
    public function getQuestionsPerMediaWithCategories($exerciseId, $mediaId)
    {
        $exerciseId = intval($exerciseId);
        $mediaId = intval($mediaId);
        $questionTable = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $questionRelExerciseTable = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);

        $sql = "SELECT q.* FROM $questionTable q INNER JOIN $questionRelExerciseTable r ON (q.iid = r.question_id)
                WHERE (r.exercice_id = $exerciseId AND q.parent_id = $mediaId) ";

        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            return Database::store_result($result, 'ASSOC');
        }
        return false;
    }

    /**
     * @param int $exerciseId
     * @param int $mediaId
     * @return array
     */
    public function getQuestionCategoriesOfMediaQuestions($exerciseId, $mediaId)
    {
        $questions = $this->getQuestionsPerMediaWithCategories($exerciseId, $mediaId);
        $questionCategoryList = array();
        if (!empty($questions)) {
            foreach ($questions as $question) {
                $categories = TestCategory::getCategoryForQuestionWithCategoryData($question['iid']);
                if (!empty($categories)) {
                    foreach ($categories as $category) {
                        $questionCategoryList[$question['iid']][] = $category['iid'];
                    }
                }
            }
        }
        return $questionCategoryList;
    }

    /**
     * Check if the media sent matches other medias sent before
     * @param int $exerciseId
     * @param int $mediaId
     * @return array
     */
    public function allQuestionWithMediaHaveTheSameCategory($exerciseId, $mediaId, $categoryListToCompare = array(), $ignoreQuestionId = null, $returnCategoryId = false)
    {
        $questions = $this->getQuestionCategoriesOfMediaQuestions($exerciseId, $mediaId);

        $result = false;
        $categoryId = null;
        if (empty($questions)) {
            $result = true;
        } else {
            $tempArray = array();
            foreach ($questions as $categories) {
                $diff = array_diff($tempArray, $categories);
                $categoryId = $categories[0];
                $tempArray = $categories;
                if (empty($diff)) {
                    $result = true;
                    continue;
                } else {
                    $result = false;
                    break;
                }
            }
        }

        if (isset($categoryListToCompare) && !empty($categoryListToCompare)) {
            $result = false;
            foreach ($questions as $questionId => $categories) {
                if ($ignoreQuestionId == $questionId) {
                    continue;
                }
                $diff = array_diff($categoryListToCompare, $categories);
                $categoryId = $categories[0];
                if (empty($diff)) {
                    $result = true;
                    continue;
                } else {
                    $result = false;
                    break;
                }
            }
        }

        if ($returnCategoryId) {
            return $categoryId;
        }
        return $result;
    }
}
