<?php
/* For licensing terms, see /license.txt */
/**
*	File containing the Question class.
*	@package chamilo.exercise
* 	@author Olivier Brouckaert
*   @author Julio Montoya <gugli100@gmail.com> lot of bug fixes
*/
/**
 * Code
 */
if(!class_exists('Question')):

// answer types
define('UNIQUE_ANSWER',					1);
define('MULTIPLE_ANSWER',				2);
define('FILL_IN_BLANKS',				3);
define('MATCHING',						4);
define('FREE_ANSWER',    				5);
define('HOT_SPOT', 						6);
define('HOT_SPOT_ORDER', 				7);
define('HOT_SPOT_DELINEATION', 			8);
define('MULTIPLE_ANSWER_COMBINATION', 	9);
define('UNIQUE_ANSWER_NO_OPTION',       10);
define('MULTIPLE_ANSWER_TRUE_FALSE',    11);
define('MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE',    12);


/**
	QUESTION CLASS
 *
 *	This class allows to instantiate an object of type Question
 *
 *	@author Olivier Brouckaert, original author
 *	@author Patrick Cool, LaTeX support
*	@package chamilo.exercise
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
	public $exerciseList;  // array with the list of exercises which this question is in
	private $isContent;
    public $course;
    
	static $typePicture = 'new_question.png';
	static $explanationLangVar = '';
	static $questionTypes = array(
							UNIQUE_ANSWER => 				array('unique_answer.class.php' , 	'UniqueAnswer'),
							MULTIPLE_ANSWER => 				array('multiple_answer.class.php' , 'MultipleAnswer'),
							FILL_IN_BLANKS => 				array('fill_blanks.class.php' , 	'FillBlanks'),
							MATCHING => 					array('matching.class.php' , 		'Matching'),
							FREE_ANSWER => 					array('freeanswer.class.php' , 		'FreeAnswer'),
							HOT_SPOT => 					array('hotspot.class.php' , 		'HotSpot'),
							HOT_SPOT_DELINEATION => 		array('hotspot.class.php' , 'HotspotDelineation'),
							MULTIPLE_ANSWER_COMBINATION =>	array('multiple_answer_combination.class.php' , 'MultipleAnswerCombination'),
                            UNIQUE_ANSWER_NO_OPTION =>      array('unique_answer_no_option.class.php' ,   'UniqueAnswerNoOption'),
                            MULTIPLE_ANSWER_TRUE_FALSE =>   array('multiple_answer_true_false.class.php' , 'MultipleAnswerTrueFalse'),
                            MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE =>   array('multiple_answer_combination_true_false.class.php' , 'MultipleAnswerCombinationTrueFalse'),
                            
							);

	/**
	 * constructor of the class
	 *
	 * @author - Olivier Brouckaert
	 */
	function Question() {
		$this->id=0;
		$this->question='';
		$this->description='';
		$this->weighting=0;
		$this->position=1;
		$this->picture='';
		$this->level = 1;
        $this->extra='';
		$this->exerciseList=array();
	}

	public function getIsContent() {
	    $isContent = null;
	    if (isset($_REQUEST['isContent'])) {
		    $isContent = intval($_REQUEST['isContent']);
	    }
		return $this->isContent = $isContent;
	}

	/**
	 * Reads question informations from the data base
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $id - question ID
	 * @return - boolean - true if question exists, otherwise false
	 */
	static function read($id, $course_id = null) {
        $id = intval($id);	
        
        if (!empty($course_id)) {            
            $course_info =  api_get_course_info_by_id($course_id);
        } else {
            global $course;
            $course_info = api_get_course_info();
        }

		$TBL_EXERCICES         = Database::get_course_table(TABLE_QUIZ_TEST,          $course_info['db_name']);
		$TBL_QUESTIONS         = Database::get_course_table(TABLE_QUIZ_QUESTION,      $course_info['db_name']);
		$TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION, $course_info['db_name']);
        
		$sql="SELECT question,description,ponderation,position,type,picture,level,extra FROM $TBL_QUESTIONS WHERE id= $id ";
        
		$result=Database::query($sql);

		// if the question has been found
		if ($object = Database::fetch_object($result)) {
			$objQuestion 				= Question::getInstance($object->type);
			$objQuestion->id			= $id;
			$objQuestion->question		= $object->question;
			$objQuestion->description	= $object->description;
			$objQuestion->weighting		= $object->ponderation;
			$objQuestion->position		= $object->position;
			$objQuestion->type			= $object->type;
			$objQuestion->picture		= $object->picture;
			$objQuestion->level			= (int) $object->level;
            $objQuestion->extra         = $object->extra;
            $objQuestion->course        = $course_info;

			$sql="SELECT exercice_id FROM $TBL_EXERCICE_QUESTION WHERE question_id='".$id."'";
			$result=Database::query($sql);

			// fills the array with the exercises which this question is in
			while($object=Database::fetch_object($result)) {
				$objQuestion->exerciseList[]=$object->exercice_id;
			}
			return $objQuestion;
		}
		// question not found
		return false;
	}

	/**
	 * returns the question ID
	 *
	 * @author - Olivier Brouckaert
	 * @return - integer - question ID
	 */
	function selectId() {
		return $this->id;
	}

	/**
	 * returns the question title
	 *
	 * @author - Olivier Brouckaert
	 * @return - string - question title
	 */
	function selectTitle() {
		$this->question=text_filter($this->question);
		return $this->question;
	}

	/**
	 * returns the question description
	 *
	 * @author - Olivier Brouckaert
	 * @return - string - question description
	 */
	function selectDescription() {
		$this->description=text_filter($this->description);
		return $this->description;
	}

	/**
	 * returns the question weighting
	 *
	 * @author - Olivier Brouckaert
	 * @return - integer - question weighting
	 */
	function selectWeighting()
	{
		return $this->weighting;
	}

	/**
	 * returns the question position
	 *
	 * @author - Olivier Brouckaert
	 * @return - integer - question position
	 */
	function selectPosition()
	{
		return $this->position;
	}

	/**
	 * returns the answer type
	 *
	 * @author - Olivier Brouckaert
	 * @return - integer - answer type
	 */
	function selectType()
	{
		return $this->type;
	}

	/**
	 * returns the level of the question
	 *
	 * @author - Nicolas Raynaud
	 * @return - integer - level of the question, 0 by default.
	 */
	function selectLevel()
	{
		return $this->level;
	}

	/**
	 * returns the picture name
	 *
	 * @author - Olivier Brouckaert
	 * @return - string - picture name
	 */
	function selectPicture()
	{
		return $this->picture;
	}
    
    function selectPicturePath() {
        if (!empty($this->picture)) {
            return api_get_path(WEB_COURSE_PATH).$this->course['path'].'/document/images/'.$this->picture;
        }
        return false;        
    }

	/**
	 * returns the array with the exercise ID list
	 *
	 * @author - Olivier Brouckaert
	 * @return - array - list of exercise ID which the question is in
	 */
	function selectExerciseList()
	{
		return $this->exerciseList;
	}

	/**
	 * returns the number of exercises which this question is in
	 *
	 * @author - Olivier Brouckaert
	 * @return - integer - number of exercises
	 */
	function selectNbrExercises()
	{
		return sizeof($this->exerciseList);
	}

	/**
	 * changes the question title
	 *
	 * @author - Olivier Brouckaert
	 * @param - string $title - question title
	 */
	function updateTitle($title)
	{
		$this->question=$title;
	}

	/**
	 * changes the question description
	 *
	 * @author - Olivier Brouckaert
	 * @param - string $description - question description
	 */
	function updateDescription($description)
	{
		$this->description=$description;
	}

	/**
	 * changes the question weighting
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $weighting - question weighting
	 */
	function updateWeighting($weighting)
	{
		$this->weighting=$weighting;
	}

	/**
	 * changes the question position
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $position - question position
	 */
	function updatePosition($position)
	{
		$this->position=$position;
	}

	/**
	 * changes the question level
	 *
	 * @author - Nicolas Raynaud
	 * @param - integer $level - question level
	 */
	function updateLevel($level)
	{
		$this->level=$level;
	}

	/**
	 * changes the answer type. If the user changes the type from "unique answer" to "multiple answers"
	 * (or conversely) answers are not deleted, otherwise yes
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $type - answer type
	 */
	function updateType($type) {
		$TBL_REPONSES           = Database::get_course_table(TABLE_QUIZ_ANSWER, $this->course['db_name']);

		// if we really change the type
		if($type != $this->type) {
			// if we don't change from "unique answer" to "multiple answers" (or conversely)
			if(!in_array($this->type,array(UNIQUE_ANSWER,MULTIPLE_ANSWER)) || !in_array($type,array(UNIQUE_ANSWER,MULTIPLE_ANSWER))) {
				// removes old answers
				$sql="DELETE FROM $TBL_REPONSES WHERE question_id='".Database::escape_string($this->id)."'";
				Database::query($sql);
			}

			$this->type=$type;
		}
	}

	/**
	 * adds a picture to the question
	 *
	 * @author - Olivier Brouckaert
	 * @param - string $Picture - temporary path of the picture to upload
	 * @param - string $PictureName - Name of the picture
	 * @return - boolean - true if uploaded, otherwise false
	 */
	function uploadPicture($Picture,$PictureName) {
		global $picturePath;

		if (!file_exists($picturePath)) {
			if (mkdir($picturePath, api_get_permissions_for_new_directories())) {
				// document path
				$documentPath = api_get_path(SYS_COURSE_PATH) . $this->course['path'] . "/document";
				$path = str_replace($documentPath,'',$picturePath);
				$title_path = basename($picturePath);
				$doc_id = add_document($this->course, $path, 'folder', 0,$title_path);
				api_item_property_update($this->course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', api_get_user_id());
			}
		}

		// if the question has got an ID
		if ($this->id) {
			$extension = pathinfo($PictureName, PATHINFO_EXTENSION);						
			$this->picture = 'quiz-'.$this->id.'.jpg';						    			
    		$o_img = new Image($Picture);
    		$o_img->send_image($picturePath.'/'.$this->picture, -1, 'jpg');
            $document_id = add_document($this->course, '/images/'.$this->picture, 'file', filesize($picturePath.'/'.$this->picture),$this->picture);
    	    if ($document_id) {
                return api_item_property_update($this->course, TOOL_DOCUMENT, $document_id, 'DocumentAdded', api_get_user_id);    			
            }
		}

		return false;
	}

	/**
	 * Resizes a picture || Warning!: can only be called after uploadPicture, or if picture is already available in object.
	 *
	 * @author - Toon Keppens
	 * @param - string $Dimension - Resizing happens proportional according to given dimension: height|width|any
	 * @param - integer $Max - Maximum size
	 * @return - boolean - true if success, false if failed
	 */
	function resizePicture($Dimension, $Max) {
		global $picturePath;

		// if the question has an ID
		if($this->id) {
	  		// Get dimensions from current image.
	  		$my_image = new Image($picturePath.'/'.$this->picture);
	  		
	  		$current_image_size = $my_image->get_image_size();
	  		$current_width      = $current_image_size['width'];
	  		$current_height     = $current_image_size['height'];		

			if($current_width < $Max && $current_height <$Max)
				return true;
			elseif($current_height == "")
				return false;

			// Resize according to height.
			if ($Dimension == "height") {
				$resize_scale = $current_height / $Max;
				$new_height = $Max;
				$new_width = ceil($current_width / $resize_scale);
			}

			// Resize according to width
			if ($Dimension == "width") {
				$resize_scale = $current_width / $Max;
				$new_width = $Max;
				$new_height = ceil($current_height / $resize_scale);
			}

			// Resize according to height or width, both should not be larger than $Max after resizing.
			if ($Dimension == "any") {
				if ($current_height > $current_width || $current_height == $current_width)
				{
					$resize_scale = $current_height / $Max;
					$new_height = $Max;
					$new_width = ceil($current_width / $resize_scale);
				}
				if ($current_height < $current_width)
				{
					$resize_scale = $current_width / $Max;
					$new_width = $Max;
					$new_height = ceil($current_height / $resize_scale);
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
	 * deletes the picture
	 *
	 * @author - Olivier Brouckaert
	 * @return - boolean - true if removed, otherwise false
	 */
	function removePicture()
	{
		global $picturePath;

		// if the question has got an ID and if the picture exists
		if($this->id)
		{
			$picture=$this->picture;
			$this->picture='';

			return @unlink($picturePath.'/'.$picture)?true:false;
		}

		return false;
	}

	/**
	 * Exports a picture to another question
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $questionId - ID of the target question
	 * @return - boolean - true if copied, otherwise false
	 */
	function exportPicture($questionId, $course_info) {
        $TBL_QUESTIONS  = Database::get_course_table(TABLE_QUIZ_QUESTION, $course_info['db_name']);
        $destination_path    = api_get_path(SYS_COURSE_PATH).$course_info['path'].'/document/images';
        $source_path         = api_get_path(SYS_COURSE_PATH).$this->course['path'].'/document/images';        		

		// if the question has got an ID and if the picture exists
		if($this->id && !empty($this->picture)) {
			$picture=explode('.',$this->picture);
			$Extension=$picture[sizeof($picture)-1];
			$picture='quiz-'.$questionId.'.'.$Extension;
			$sql="UPDATE $TBL_QUESTIONS SET picture='".Database::escape_string($picture)."' WHERE id='".intval($questionId)."'";            
			Database::query($sql);            
			return @copy($source_path.'/'.$this->picture, $destination_path.'/'.$picture)?true:false;
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
	function setTmpPicture($Picture,$PictureName) {
		global $picturePath;

		$PictureName=explode('.',$PictureName);
		$Extension=$PictureName[sizeof($PictureName)-1];

		// saves the picture into a temporary file
		@move_uploaded_file($Picture,$picturePath.'/tmp.'.$Extension);
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
	 * @author - Olivier Brouckaert
	 * @return - boolean - true if moved, otherwise false
	 */
	function getTmpPicture()
	{
		global $picturePath;

		// if the question has got an ID and if the picture exists
		if($this->id)
		{
			if(file_exists($picturePath.'/tmp.jpg'))
			{
				$Extension='jpg';
			}
			elseif(file_exists($picturePath.'/tmp.gif'))
			{
				$Extension='gif';
			}
			elseif(file_exists($picturePath.'/tmp.png'))
			{
				$Extension='png';
			}

			$this->picture='quiz-'.$this->id.'.'.$Extension;

			return @rename($picturePath.'/tmp.'.$Extension,$picturePath.'/'.$this->picture)?true:false;
		}

		return false;
	}

	/**
	 * updates the question in the data base
	 * if an exercise ID is provided, we add that exercise ID into the exercise list
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $exerciseId - exercise ID if saving in an exercise
	 */
	function save($exerciseId=0) {

		$TBL_EXERCICE_QUESTION	= Database::get_course_table(TABLE_QUIZ_TEST_QUESTION, $this->course['db_name']);
		$TBL_QUESTIONS			= Database::get_course_table(TABLE_QUIZ_QUESTION, $this->course['db_name']);

		$id=$this->id;
		$question=$this->question;
		$description=$this->description;
		$weighting=$this->weighting;
		$position=$this->position;
		$type=$this->type;
		$picture=$this->picture;
		$level=$this->level;
        $extra=$this->extra;

		// question already exists
		if(!empty($id)) {
			$sql="UPDATE $TBL_QUESTIONS SET
					question 	='".Database::escape_string($question)."',
					description	='".Database::escape_string($description)."',
					ponderation	='".Database::escape_string($weighting)."',
					position	='".Database::escape_string($position)."',
					type		='".Database::escape_string($type)."',
					picture		='".Database::escape_string($picture)."',
                    extra       ='".Database::escape_string($extra)."',
					level		='".Database::escape_string($level)."'
				WHERE id='".Database::escape_string($id)."'";
			Database::query($sql);
			if(!empty($exerciseId)) {
			api_item_property_update($this->course, TOOL_QUIZ, $id,'QuizQuestionUpdated',api_get_user_id);
			}
            if (api_get_setting('search_enabled')=='true') {
                if ($exerciseId != 0) {
                    $this -> search_engine_edit($exerciseId);
                } else {
                    /**
                     * actually there is *not* an user interface for
                     * creating questions without a relation with an exercise
                     */
                }
            }

		} else {
			// creates a new question
			$sql="SELECT max(position) FROM $TBL_QUESTIONS as question, $TBL_EXERCICE_QUESTION as test_question WHERE question.id=test_question.question_id AND test_question.exercice_id='".Database::escape_string($exerciseId)."'";
			$result=Database::query($sql);
			$current_position=Database::result($result,0,0);
			$this -> updatePosition($current_position+1);
			$position = $this -> position;

			$sql="INSERT INTO $TBL_QUESTIONS(question,description,ponderation,position,type,picture,extra, level) VALUES(
					'".Database::escape_string($question)."',
					'".Database::escape_string($description)."',
					'".Database::escape_string($weighting)."',
					'".Database::escape_string($position)."',
					'".Database::escape_string($type)."',
					'".Database::escape_string($picture)."',
					'".Database::escape_string($extra)."',
                    '".Database::escape_string($level)."'
					)";
			Database::query($sql);

			$this->id=Database::insert_id();

			api_item_property_update($this->course, TOOL_QUIZ, $this->id,'QuizQuestionAdded',api_get_user_id());

			// If hotspot, create first answer
			if ($type == HOT_SPOT || $type == HOT_SPOT_ORDER) {
				$TBL_ANSWERS = Database::get_course_table(TABLE_QUIZ_ANSWER);
				$sql="INSERT INTO $TBL_ANSWERS (id , question_id , answer , correct , comment , ponderation , position , hotspot_coordinates , hotspot_type ) VALUES ('1', '".Database::escape_string($this->id)."', '', NULL , '', '10' , '1', '0;0|0|0', 'square')";
				Database::query($sql);
            }
            
			if ($type == HOT_SPOT_DELINEATION ) {
				$TBL_ANSWERS = Database::get_course_table(TABLE_QUIZ_ANSWER);
				$sql="INSERT INTO $TBL_ANSWERS (id , question_id , answer , correct , comment , ponderation , position , hotspot_coordinates , hotspot_type ) VALUES ('1', '".Database::escape_string($this->id)."', '', NULL , '', '10' , '1', '0;0|0|0', 'delineation')";
				api_sql_query($sql,__FILE__,__LINE__);
				
				//$TBL_ANSWERS = Database::get_course_table(TABLE_QUIZ_ANSWER);
				//$sql="INSERT INTO $TBL_ANSWERS (`id` , `question_id` , `answer` , `correct` , `comment` , `ponderation` , `position` , `hotspot_coordinates` , `hotspot_type` ) VALUES ('2', '".Database::escape_string($this->id)."', '', NULL , '', NULL , '1', '0;0|0|0', 'noerror')";
				//api_sql_query($sql,__FILE__,__LINE__);		
				
			}
			

            if (api_get_setting('search_enabled')=='true') {
                if ($exerciseId != 0) {
                    $this -> search_engine_edit($exerciseId, TRUE);
                } else {
                    /**
                     * actually there is *not* an user interface for
                     * creating questions without a relation with an exercise
                     */
                }
            }
		}

		// if the question is created in an exercise
		if($exerciseId) {
			/*
			$sql = 'UPDATE '.Database::get_course_table(TABLE_LP_ITEM).'
					SET max_score = '.intval($weighting).'
					WHERE item_type = "'.TOOL_QUIZ.'"
					AND path='.intval($exerciseId);
			Database::query($sql);
			*/
			// adds the exercise into the exercise list of this question
			$this->addToList($exerciseId, TRUE);
		}
	}

    function search_engine_edit($exerciseId, $addQs=FALSE, $rmQs=FALSE) {
        // update search engine and its values table if enabled
        if (api_get_setting('search_enabled')=='true' && extension_loaded('xapian')) {
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
                require_once(api_get_path(LIBRARY_PATH) . 'search/DokeosIndexer.class.php');
                require_once(api_get_path(LIBRARY_PATH) . 'search/IndexableChunk.class.php');

                $di = new DokeosIndexer();
                if ($addQs) {
                	$question_exercises = array((int)$exerciseId);
                } else {
                    $question_exercises = array();
                }
                isset($_POST['language'])? $lang=Database::escape_string($_POST['language']): $lang = 'english';
                $di->connectDb(NULL, NULL, $lang);

                // retrieve others exercise ids
                $se_ref = Database::fetch_array($res);
                $se_doc = $di->get_document((int)$se_ref['search_did']);
                if ($se_doc !== FALSE) {
	                if ( ($se_doc_data=$di->get_document_data($se_doc)) !== FALSE ) {
		                $se_doc_data = unserialize($se_doc_data);
		                if (isset($se_doc_data[SE_DATA]['type']) && $se_doc_data[SE_DATA]['type'] == SE_DOCTYPE_EXERCISE_QUESTION) {
			                if (isset($se_doc_data[SE_DATA]['exercise_ids']) && is_array($se_doc_data[SE_DATA]['exercise_ids'])) {
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
                	while ( ($key=array_search($exerciseId, $question_exercises)) !== FALSE) {
                		unset($question_exercises[$key]);
                	}
                }

                // build the chunk to index
                $ic_slide = new IndexableChunk();
                $ic_slide->addValue("title", $this->question);
                $ic_slide->addCourseId($course_id);
                $ic_slide->addToolId(TOOL_QUIZ);
                $xapian_data = array(
                    SE_COURSE_ID => $course_id,
                    SE_TOOL_ID => TOOL_QUIZ,
                    SE_DATA => array('type' => SE_DOCTYPE_EXERCISE_QUESTION, 'exercise_ids' => $question_exercises, 'question_id' => (int)$this->id),
                    SE_USER => (int)api_get_user_id(),
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
                          $sql = sprintf($sql, $tbl_se_ref, $course_id, TOOL_QUIZ, array_shift($question_exercises), $this->id, $did);
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
     * @param - integer $exerciseId - exercise ID
     * @param - boolean $fromSave - comming from $this->save() or not
	 */
	function addToList($exerciseId, $fromSave = FALSE) {
		$TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION, $this->course['db_name']);
		$id = $this->id;
		// checks if the exercise ID is not in the list
		if (!in_array($exerciseId,$this->exerciseList)) {            
			$this->exerciseList[]=$exerciseId;            
            $new_exercise = new Exercise();
            $new_exercise->read($exerciseId);
            $count = $new_exercise->selectNbrQuestions();
            $count++;
			$sql="INSERT INTO $TBL_EXERCICE_QUESTION (question_id, exercice_id, question_order) VALUES ('".Database::escape_string($id)."','".Database::escape_string($exerciseId)."', '$count' )";
			Database::query($sql);
            
            // we do not want to reindex if we had just saved adnd indexed the question
            if (!$fromSave) {
            	$this->search_engine_edit($exerciseId, TRUE);
            }
		}
	}

	/**
	 * removes an exercise from the exercise list
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $exerciseId - exercise ID
	 * @return - boolean - true if removed, otherwise false
	 */
	function removeFromList($exerciseId) {
		global $TBL_EXERCICE_QUESTION;

		$id=$this->id;

		// searches the position of the exercise ID in the list
		$pos=array_search($exerciseId,$this->exerciseList);

		// exercise not found
		if($pos === false) {
			return false;
		} else {
			// deletes the position in the array containing the wanted exercise ID
			unset($this->exerciseList[$pos]);
            //update order of other elements
            $sql = "SELECT question_order FROM $TBL_EXERCICE_QUESTION WHERE question_id='".Database::escape_string($id)."' AND exercice_id='".Database::escape_string($exerciseId)."'";
            $res = Database::query($sql);
            if (Database::num_rows($res)>0) {
                $row = Database::fetch_array($res);
                if (!empty($row['question_order'])) {
                    $sql = "UPDATE $TBL_EXERCICE_QUESTION SET question_order = question_order-1 WHERE exercice_id='".Database::escape_string($exerciseId)."' AND question_order > ".$row['question_order'];
                    $res = Database::query($sql);
                }
            }

			$sql="DELETE FROM $TBL_EXERCICE_QUESTION WHERE question_id='".Database::escape_string($id)."' AND exercice_id='".Database::escape_string($exerciseId)."'";
			Database::query($sql);

			return true;
		}
	}

	/**
	 * Deletes a question from the database
	 * the parameter tells if the question is removed from all exercises (value = 0),
	 * or just from one exercise (value = exercise ID)
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $deleteFromEx - exercise ID if the question is only removed from one exercise
	 */
	function delete($deleteFromEx=0) {
		global $_course,$_user;

		$TBL_EXERCICE_QUESTION	= Database::get_course_table(TABLE_QUIZ_TEST_QUESTION, $this->course['db_name']);
		$TBL_QUESTIONS			= Database::get_course_table(TABLE_QUIZ_QUESTION, $this->course['db_name']);
		$TBL_REPONSES           = Database::get_course_table(TABLE_QUIZ_ANSWER, $this->course['db_name']);

		$id=$this->id;

		// if the question must be removed from all exercises
		if(!$deleteFromEx)
		{
            //update the question_order of each question to avoid inconsistencies
            $sql = "SELECT exercice_id, question_order FROM $TBL_EXERCICE_QUESTION WHERE question_id='".Database::escape_string($id)."'";
            $res = Database::query($sql);
            if (Database::num_rows($res)>0) {
                while ($row = Database::fetch_array($res)) {
                    if (!empty($row['question_order'])) {
                        $sql = "UPDATE $TBL_EXERCICE_QUESTION SET question_order = question_order-1 WHERE exercice_id='".Database::escape_string($row['exercice_id'])."' AND question_order > ".$row['question_order'];
                        $res = Database::query($sql);
                    }
                }
            }
			$sql="DELETE FROM $TBL_EXERCICE_QUESTION WHERE question_id='".Database::escape_string($id)."'";
			Database::query($sql);

			$sql="DELETE FROM $TBL_QUESTIONS WHERE id='".Database::escape_string($id)."'";
			Database::query($sql);

			$sql="DELETE FROM $TBL_REPONSES WHERE question_id='".Database::escape_string($id)."'";
			Database::query($sql);

			api_item_property_update($this->course, TOOL_QUIZ, $id,'QuizQuestionDeleted',api_get_user_id());
			$this->removePicture();

			// resets the object
			$this->Question();
		}
		// just removes the exercise from the list
		else
		{
			$this->removeFromList($deleteFromEx);
            if (api_get_setting('search_enabled')=='true' && extension_loaded('xapian')) {
                // disassociate question with this exercise
                $this -> search_engine_edit($deleteFromEx, FALSE, TRUE);
            }
            api_item_property_update($this->course, TOOL_QUIZ, $id,'QuizQuestionDeleted',api_get_user_id());
		}
	}

	/**
	 * Duplicates the question
	 *
	 * @author Olivier Brouckaert
     * @param  array   Course info of the destination course
	 * @return int     ID of the new question
    */
     
	function duplicate($course_info = null) {        
        if (empty($course_info)) {
        	$course_info = $this->course;
        } else {
        	$course_info = $course_info;
        }
        $TBL_QUESTIONS        = Database::get_course_table(TABLE_QUIZ_QUESTION, $course_info['db_name']);
        $TBL_QUESTION_OPTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION, $course_info['db_name']);

		$question     = $this->question;
		$description  = $this->description;
		$weighting    = $this->weighting;
		$position     = $this->position;
		$type         = $this->type;
		$level        = intval($this->level);
        $extra        = $this->extra;
        
        //Using the same method used in the course copy to transform URLs
        require_once api_get_path(LIBRARY_PATH).'document.lib.php';
        if ($course_info['db_name'] != $this->course['db_name']) {
            $description  = DocumentManager::replace_urls_inside_content_html_from_copy_course($description, $this->course['id'], $course_info['id']);
            $question     = DocumentManager::replace_urls_inside_content_html_from_copy_course($question, $this->course['id'], $course_info['id']);
        }
                
        $options = self::readQuestionOption($this->id);  
        //Inserting in the new course db / or the same course db
		$sql="INSERT INTO $TBL_QUESTIONS(question, description, ponderation, position, type, level, extra ) VALUES('".Database::escape_string($question)."','".Database::escape_string($description)."','".Database::escape_string($weighting)."','".Database::escape_string($position)."','".Database::escape_string($type)."' ,'".Database::escape_string($level)."' ,'".Database::escape_string($extra)."'  )";
		Database::query($sql);    
            
		$new_question_id =Database::insert_id();        
        
        if (!empty($options)) {
            //Saving the quiz_options      
            foreach ($options as $item) {
                $item['question_id'] = $new_question_id;
                unset($item['id']); 
                Database::insert($TBL_QUESTION_OPTIONS,$item);	
            }
        }
        
		// Duplicates the picture 
		$this->exportPicture($new_question_id, $course_info);
		return $new_question_id;
	}

	/**
	 * Returns an instance of the class corresponding to the type
	 * @param integer $type the type of the question
	 * @return an instance of a Question subclass (or of Questionc class by default)
	 */
	static function getInstance ($type) {
		if (!is_null($type)) {
			list($file_name,$class_name) = self::$questionTypes[$type];
			include_once($file_name);
			if (class_exists($class_name)) {
				return new $class_name();
			} else {
				echo 'Can\'t instanciate class '.$class_name.' of type '.$type;
				return null;
			}
		}
	}

	/**
	 * Creates the form to create / edit a question
	 * A subclass can redifine this function to add fields...
	 * @param FormValidator $form the formvalidator instance (by reference)
	 */
	function createForm (&$form,$fck_config=0) {
		echo '	<style>
					div.row div.label{ width: 8%; }
					div.row div.formw{ width: 88%; }
					.media { display:none;}
				</style>';

		echo '<script>
			// hack to hide http://cksource.com/forums/viewtopic.php?f=6&t=8700

			function FCKeditor_OnComplete( editorInstance )
			{
			   if (document.getElementById ( \'HiddenFCK\' + editorInstance.Name )) {
			      HideFCKEditorByInstanceName (editorInstance.Name);
			   }
			}

			function HideFCKEditorByInstanceName ( editorInstanceName ) {
			   if (document.getElementById ( \'HiddenFCK\' + editorInstanceName ).className == "HideFCKEditor" ) {
			      document.getElementById ( \'HiddenFCK\' + editorInstanceName ).className = "media";
			      }
			}

			function show_media()
			{
				var my_display = document.getElementById(\'HiddenFCKquestionDescription\').style.display;
				if(my_display== \'none\' || my_display == \'\') {
				document.getElementById(\'HiddenFCKquestionDescription\').style.display = \'block\';
				document.getElementById(\'media_icon\').innerHTML=\'&nbsp;<img style="vertical-align: middle;" src="../img/looknfeelna.png" alt="" />&nbsp;'.get_lang('EnrichQuestion').'\';
			} else {
				document.getElementById(\'HiddenFCKquestionDescription\').style.display = \'none\';
				document.getElementById(\'media_icon\').innerHTML=\'&nbsp;<img style="vertical-align: middle;" src="../img/looknfeel.png" alt="" />&nbsp;'.get_lang('EnrichQuestion').'\';
			}
		}
		</script>';


		$renderer = $form->defaultRenderer();
		$form->addElement('html','<div class="form">');
		// question name
		$form->addElement('text','questionName','<span class="form_required">*</span> '.get_lang('Question'),'size="80"');
		//$form->applyFilter('questionName','html_filter');

		//$radios_results_enabled[] = $form->createElement('static', null, null, null);
		//$test=FormValidator :: createElement ('text', 'questionName');

		//$radios_results_enabled[]=$test;
		// question level
		//@todo move levles into a table
		$select_level = array (1,2,3,4,5);
		//$radios_results_enabled[] =
		foreach($select_level as $val) {
			$radios_results_enabled[] = FormValidator :: createElement ('radio', null, null,$val,$val);
		}
		$form->addGroup($radios_results_enabled,'questionLevel',get_lang('Difficulty'));

		$renderer->setElementTemplate('<div class="row"><div class="label">{label}</div><div class="formw" >{element}</div></div>','questionName');
		$renderer->setElementTemplate('<div class="row"><div class="label">{label}</div><div class="formw">{element}</div></div>','questionLevel');
		$form->addRule('questionName', get_lang('GiveQuestion'), 'required');

		// default content
		$isContent = intval($_REQUEST['isContent']);

		// question type
		$answerType= intval($_REQUEST['answerType']);
		$form->addElement('hidden','answerType',$_REQUEST['answerType']);

		// html editor
		$editor_config = array('ToolbarSet' => 'TestQuestionDescription', 'Width' => '100%', 'Height' => '150');
		if(is_array($fck_config)){
			$editor_config = array_merge($editor_config, $fck_config);
		}
		if(!api_is_allowed_to_edit(null,true)) $editor_config['UserStatus'] = 'student';

		$form -> addElement('html','<div class="row">
		<div class="label"></div>
		<div class="formw" style="height:50px">
			<a href="javascript://" onclick=" return show_media()"> <span id="media_icon"> <img style="vertical-align: middle;" src="../img/looknfeel.png" alt="" />&nbsp;'.get_lang('EnrichQuestion').'</span></a>
		</div>
		</div>');

		$form -> addElement ('html','<div class="HideFCKEditor" id="HiddenFCKquestionDescription" >');
		$form->add_html_editor('questionDescription', get_lang('langQuestionDescription'), false, false, $editor_config);
		$form -> addElement ('html','</div>');

		$renderer->setElementTemplate('<div class="row"><div class="label">{label}</div><div class="formw">{element}</div></div>','questionDescription');

		// hidden values
		$form->addElement('hidden','myid',$_REQUEST['myid']);
        

        if (!isset($_GET['fromExercise'])) {            
    		switch($answerType) {
    			case 1:	$this->question = get_lang('langDefaultUniqueQuestion'); break;
    			case 2:	$this->question = get_lang('langDefaultMultipleQuestion'); break;
    			case 3:	$this->question = get_lang('langDefaultFillBlankQuestion'); break;
    			case 4:	$this->question = get_lang('langDefaultMathingQuestion'); break;
    			case 5:	$this->question = get_lang('langDefaultOpenQuestion');	break;
    			case 9:	$this->question = get_lang('langDefaultMultipleQuestion'); break;
    		}
        }
		$form->addElement('html','</div>');
		// default values
		$defaults = array();		
		$defaults['questionName']          = $this -> question;
		$defaults['questionDescription']   = $this -> description;
		$defaults['questionLevel']         = $this -> level;
        
        //Came from he question pool        
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
	 * function which process the creation of questions
	 * @param FormValidator $form the formvalidator instance
	 * @param Exercise $objExercise the Exercise instance
	 */
	function processCreation ($form, $objExercise) {
		$this -> updateTitle($form->getSubmitValue('questionName'));
	    $this -> updateDescription($form->getSubmitValue('questionDescription'));
	    $this -> updateLevel($form->getSubmitValue('questionLevel'));
	    $this -> save($objExercise -> id);
	    // modify the exercise
	    $objExercise->addToList($this -> id);
        $objExercise->update_question_positions();

	}

	/**
	 * abstract function which creates the form to create / edit the answers of the question
	 * @param the formvalidator instance
	 */
	abstract function createAnswersForm ($form);

	/**
	 * abstract function which process the creation of answers
	 * @param the formvalidator instance
	 */
	abstract function processAnswersCreation ($form);


	/**
	 * Displays the menu of question types
	 */
	static function display_type_menu ($feedbacktype = 0) {
		global $exerciseId;
		// 1. by default we show all the question types
		$question_type_custom_list = self::$questionTypes;

		if (!isset($feedbacktype)) $feedbacktype=0;
		if ($feedbacktype==1) {
			//2. but if it is a feedback DIRECT we only show the UNIQUE_ANSWER type that is currently available
			//$question_type_custom_list = array ( UNIQUE_ANSWER => self::$questionTypes[UNIQUE_ANSWER]);
			$question_type_custom_list = array ( UNIQUE_ANSWER => self::$questionTypes[UNIQUE_ANSWER],HOT_SPOT_DELINEATION => self::$questionTypes[HOT_SPOT_DELINEATION]);  
		} else {
			unset($question_type_custom_list[HOT_SPOT_DELINEATION]);
		}

		//blocking edition

		$show_quiz_edition = true;
		if (isset($exerciseId) && !empty($exerciseId)) {
			$TBL_LP_ITEM	= Database::get_course_table(TABLE_LP_ITEM);
			$sql="SELECT max_score FROM $TBL_LP_ITEM
				  WHERE item_type = '".TOOL_QUIZ."' AND path ='".Database::escape_string($exerciseId)."'";
			$result = Database::query($sql);
			if (Database::num_rows($result) > 0) {
				$show_quiz_edition = false;
			}
		}

		echo '<ul class="question_menu">';

		foreach ($question_type_custom_list as $i=>$a_type) {
			// include the class of the type
			require_once($a_type[0]);
 		    // get the picture of the type and the langvar which describes it
            $img = $explanation = '';
			eval('$img = '.$a_type[1].'::$typePicture;');
			eval('$explanation = get_lang('.$a_type[1].'::$explanationLangVar);');
			echo '<li>';
			echo '<div class="icon_image_content">';
			if ($show_quiz_edition) {
                 
				echo '<a href="admin.php?'.api_get_cidreq().'&newQuestion=yes&answerType='.$i.'">'.Display::return_icon($img, $explanation).'</a>';
				//echo '<br>';
				//echo '<a href="admin.php?'.api_get_cidreq().'&newQuestion=yes&answerType='.$i.'">'.$explanation.'</a>';
			} else {
				$img = pathinfo($img);
				$img = $img['filename'];
				echo ''.Display::return_icon($img.'_na.gif',$explanation).'';
				//echo '<br>';
               
				//echo ''.$explanation.'';
			}
			echo '</div>';
			echo '</li>';
		}

		echo '<li>';
		echo '<div class="icon_image_content">';
		if ($show_quiz_edition) {
			if ($feedbacktype==1) {
				echo $url = '<a href="question_pool.php?'.api_get_cidreq().'&type=1&fromExercise='.$exerciseId.'">';
			} else {
				echo $url = '<a href="question_pool.php?'.api_get_cidreq().'&fromExercise='.$exerciseId.'">';
			}
			echo Display::return_icon('database.png', get_lang('GetExistingQuestion'), '');
		} else {
			echo Display::return_icon('database_na.png', get_lang('GetExistingQuestion'), '');
		}
		//echo '<br>';
		//echo $url;
		//echo get_lang('GetExistingQuestion');
		echo '</a>';
		echo '</div></li>';
		echo '</ul>';
	}

	static function get_types_information(){
		return self::$questionTypes;
	}

	static function updateId() {
		return self::$questionTypes;
	}
    
    static function saveQuestionOption($question_id, $name, $position = 0) {
        $TBL_EXERCICE_QUESTION_OPTION    = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);   
        $params['question_id']  = intval($question_id);
        $params['name']         = $name;        
        $params['position']     = $position;             
        $result  = self::readQuestionOption($question_id);
        $last_id = Database::insert($TBL_EXERCICE_QUESTION_OPTION, $params);
        return $last_id; 
    }
    
    static function deleteAllQuestionOptions($question_id) {
    	$TBL_EXERCICE_QUESTION_OPTION    = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);
        Database::delete($TBL_EXERCICE_QUESTION_OPTION, array('question_id = ?'=> $question_id));
    }
    
    static function updateQuestionOption($id, $params) {
        $TBL_EXERCICE_QUESTION_OPTION    = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);
        $result = Database::update($TBL_EXERCICE_QUESTION_OPTION, $params, array('id = ?'=>$id));             
        return $result;        
    }
    
    
    static function readQuestionOption($question_id, $db_name = null) {
        if (empty($db_name)) {            
            $TBL_EXERCICE_QUESTION_OPTION    = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION);	
        } else {
            $TBL_EXERCICE_QUESTION_OPTION    = Database::get_course_table(TABLE_QUIZ_QUESTION_OPTION, $db_name);	
        }        
        $result = Database::select('*', $TBL_EXERCICE_QUESTION_OPTION, array('where'=>array('question_id = ?' =>$question_id), 'order'=>'id ASC'));
        return $result;        
    }
    
	function return_header($feedback_type, $counter = null) {
	    $counter_label = '';
	    if (!is_null($counter)) {
	        $counter = intval($counter);
	    }	
	    echo Display::div(get_lang("Question").' '.($counter_label).' : '.$this->question, array('id'=>'question_title', 'class'=>'sectiontitle'));
	    echo Display::div($this->description, array('id'=>'question_description'));	    
	}
    /**
     * Create a question from a set of parameters
     * @param   int     Quiz ID
     * @param   string  Question name
     * @param   int     Maximum result for the question
     * @param   int     Type of question (see constants at beginning of question.class.php)
     * @param   int     Question level/category
     */
    function create_question ($quiz_id, $question_name, $max_score = 0, $type = 1, $level = 1) {
        $tbl_quiz_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $tbl_quiz_rel_question = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $quiz_id = filter_var($quiz_id,FILTER_SANITIZE_NUMBER_INT);
        $max_score = filter_var($max_score,FILTER_SANITIZE_NUMBER_FLOAT);
        $type = filter_var($type,FILTER_SANITIZE_NUMBER_INT);
        $level = filter_var($level,FILTER_SANITIZE_NUMBER_INT);
        // Get the max position
        $sql = "SELECT max(position) as max_position"
               ." FROM $tbl_quiz_question q INNER JOIN $tbl_quiz_rel_question r"
               ." ON q.id = r.question_id"
               ." AND exercice_id = $quiz_id";
        $rs_max = Database::query($sql, __FILE__, __LINE__);
        $row_max = Database::fetch_object($rs_max);
        $max_position = $row_max->max_position +1;
   
        // Insert the new question
        $sql = "INSERT INTO $tbl_quiz_question"
              ." (question,ponderation,position,type,level) "
              ." VALUES('".Database::escape_string($question_name)."',"
              ." $max_score , $max_position, $type, $level)";
        $rs = Database::query($sql);
        // Get the question ID
        $question_id = Database::get_last_insert_id();
        // Get the max question_order
        $sql = "SELECT max(question_order) as max_order "
              ."FROM $tbl_quiz_rel_question WHERE exercice_id = $quiz_id ";
        $rs_max_order = Database::query($sql);
        $row_max_order = Database::fetch_object($rs_max_order);
        $max_order = $row_max_order->max_order + 1;
        // Attach questions to quiz
        $sql = "INSERT INTO $tbl_quiz_rel_question "
              ."(question_id,exercice_id,question_order)"
              ." VALUES($question_id, $quiz_id, $max_order)";
        $rs = Database::query($sql);
        return $question_id;
    }
}
endif;
