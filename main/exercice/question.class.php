<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.

    Contact:
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/


/**
*	File containing the Question class.
*	@package dokeos.exercise
* 	@author Olivier Brouckaert
* 	@version $Id: question.class.php 13692 2007-11-14 14:38:13Z elixir_inter $
*/


if(!class_exists('Question')):

// answer types
define(UNIQUE_ANSWER,	1);
define(MULTIPLE_ANSWER,	2);
define(FILL_IN_BLANKS,	3);
define(MATCHING,		4);
define(FREE_ANSWER,     5);
define(HOT_SPOT, 		6);
define(HOT_SPOT_ORDER, 	7);



/**
	CLASS QUESTION
 *
 *	This class allows to instantiate an object of type Question
 *
 *	@author Olivier Brouckaert, original author
 *	@author Patrick Cool, LaTeX support
 *	@package dokeos.exercise
 */
abstract class Question
{
	var $id;
	var $question;
	var $description;
	var $weighting;
	var $position;
	var $type;

	var $picture;

	var $exerciseList;  // array with the list of exercises which this question is in

	static $typePicture = 'new_question.png';
	static $explanationLangVar = '';
	static $questionTypes = array(
							UNIQUE_ANSWER => array('unique_answer.class.php' , 'UniqueAnswer'),
							MULTIPLE_ANSWER => array('multiple_answer.class.php' , 'MultipleAnswer'),
							FILL_IN_BLANKS => array('fill_blanks.class.php' , 'FillBlanks'),
							MATCHING => array('matching.class.php' , 'Matching'),
							FREE_ANSWER => array('freeanswer.class.php' , 'FreeAnswer'),
							HOT_SPOT => array('hotspot.class.php' , 'HotSpot')
							);

	/**
	 * constructor of the class
	 *
	 * @author - Olivier Brouckaert
	 */
	function Question()
	{
		$this->id=0;
		$this->question='';
		$this->description='';
		$this->weighting=0;
		$this->position=1;
		$this->picture='';

		$this->exerciseList=array();
	}

	/**
	 * reads question informations from the data base
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $id - question ID
	 * @return - boolean - true if question exists, otherwise false
	 */
	static function read($id)
	{
		global $_course;

		$TBL_EXERCICES         = Database::get_course_table(TABLE_QUIZ_TEST);
		$TBL_QUESTIONS         = Database::get_course_table(TABLE_QUIZ_QUESTION);
		$TBL_EXERCICE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
		$sql="SELECT question,description,ponderation,position,type,picture FROM $TBL_QUESTIONS WHERE id='$id'";

		$result=api_sql_query($sql,__FILE__,__LINE__);

		// if the question has been found
		if($object=mysql_fetch_object($result))
		{
			$objQuestion = Question::getInstance($object->type);
			$objQuestion->id=$id;
			$objQuestion->question=$object->question;
			$objQuestion->description=$object->description;
			$objQuestion->weighting=$object->ponderation;
			$objQuestion->position=$object->position;
			$objQuestion->type=$object->type;
			$objQuestion->picture=$object->picture;

			$sql="SELECT exercice_id FROM $TBL_EXERCICE_QUESTION WHERE question_id='".intval($id)."'";
			$result=api_sql_query($sql,__FILE__,__LINE__);

			// fills the array with the exercises which this question is in
			while($object=mysql_fetch_object($result))
			{
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
	function selectId()
	{
		return $this->id;
	}

	/**
	 * returns the question title
	 *
	 * @author - Olivier Brouckaert
	 * @return - string - question title
	 */
	function selectTitle()
	{
		$this->question=api_parse_tex($this->question);
		return $this->question;
	}

	/**
	 * returns the question description
	 *
	 * @author - Olivier Brouckaert
	 * @return - string - question description
	 */
	function selectDescription()
	{
		$this->description=api_parse_tex($this->description);
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
	 * returns the picture name
	 *
	 * @author - Olivier Brouckaert
	 * @return - string - picture name
	 */
	function selectPicture()
	{
		return $this->picture;
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
	 * changes the answer type. If the user changes the type from "unique answer" to "multiple answers"
	 * (or conversely) answers are not deleted, otherwise yes
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $type - answer type
	 */
	function updateType($type)
	{
		global $TBL_REPONSES;

		// if we really change the type
		if($type != $this->type)
		{
			// if we don't change from "unique answer" to "multiple answers" (or conversely)
			if(!in_array($this->type,array(UNIQUE_ANSWER,MULTIPLE_ANSWER)) || !in_array($type,array(UNIQUE_ANSWER,MULTIPLE_ANSWER)))
			{
				// removes old answers
				$sql="DELETE FROM $TBL_REPONSES WHERE question_id='".$this->id."'";
				api_sql_query($sql,__FILE__,__LINE__);
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
	function uploadPicture($Picture,$PictureName)
	{
		global $picturePath, $_course, $_user;

		// if the question has got an ID
		if($this->id)
		{
			
			$extension = pathinfo($PictureName, PATHINFO_EXTENSION);
			$this->picture='quiz-'.$this->id.'.jpg';
			if($extension == 'gif' || $extension == 'png')
			{
				$o_img = new image($Picture);
				$o_img->send_image('JPG',$picturePath.'/'.$this->picture);
				$document_id = add_document($_course, '/images/'.$this->picture, 'file', filesize($picturePath.'/'.$this->picture),$this->picture);
			}
			else
			{
				move_uploaded_file($Picture,$picturePath.'/'.$this->picture)?true:false;	
			}
			$document_id = add_document($_course, '/images/'.$this->picture, 'file', filesize($picturePath.'/'.$this->picture),$this->picture);
			if($document_id)
			{
				return api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'DocumentAdded', $_user['user_id']);
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
	function resizePicture($Dimension, $Max)
	{
		global $picturePath;

		// if the question has an ID
		if($this->id)
		{
	  		// Get dimensions from current image.
	  		$current_img = imagecreatefromjpeg($picturePath.'/'.$this->picture);

	  		$current_image_size = getimagesize($picturePath.'/'.$this->picture);
	  		$current_height = imagesy($current_img);
			$current_width = imagesx($current_img);

			if($current_image_size[0] < $Max && $current_image_size[1] <$Max)
				return true;
			elseif($current_height == "")
				return false;

			// Resize according to height.
			if ($Dimension == "height")
			{
				$resize_scale = $current_height / $Max;
				$new_height = $Max;
				$new_width = ceil($current_width / $resize_scale);
			}

			// Resize according to width
			if ($Dimension == "width")
			{
				$resize_scale = $current_width / $Max;
				$new_width = $Max;
				$new_height = ceil($current_height / $resize_scale);
			}

			// Resize according to height or width, both should not be larger than $Max after resizing.
			if ($Dimension == "any")
			{
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

			// Create new image
		    $new_img = imagecreatetruecolor($new_width, $new_height);
			$bgColor = imagecolorallocate($new_img, 255,255,255);
			imagefill($new_img , 0,0 , $bgColor);

			// Resize image
			imagecopyresized($new_img, $current_img, 0, 0, 0, 0, $new_width, $new_height, $current_width, $current_height);

			// Write image to file
		    $result = imagejpeg($new_img, $picturePath.'/'.$this->picture, 100);

		    // Delete temperory images, clear memory
			imagedestroy($current_img);
			imagedestroy($new_img);

			if ($result)
			{
				return true;
			}
			else
			{
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
	 * exports a picture to another question
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $questionId - ID of the target question
	 * @return - boolean - true if copied, otherwise false
	 */
	function exportPicture($questionId)
	{
		global $TBL_QUESTIONS, $picturePath;

		// if the question has got an ID and if the picture exists
		if($this->id && !empty($this->picture))
		{
			$picture=explode('.',$this->picture);
			$Extension=$picture[sizeof($picture)-1];
			$picture='quiz-'.$questionId.'.'.$Extension;

			$sql="UPDATE $TBL_QUESTIONS SET picture='$picture' WHERE id='$questionId'";
			api_sql_query($sql,__FILE__,__LINE__);

			return @copy($picturePath.'/'.$this->picture,$picturePath.'/'.$picture)?true:false;
		}

		return false;
	}

	/**
	 * saves the picture coming from POST into a temporary file
	 * Temporary pictures are used when we don't want to save a picture right after a form submission.
	 * For example, if we first show a confirmation box.
	 *
	 * @author - Olivier Brouckaert
	 * @param - string $Picture - temporary path of the picture to move
	 * @param - string $PictureName - Name of the picture
	 */
	function setTmpPicture($Picture,$PictureName)
	{
		global $picturePath;

		$PictureName=explode('.',$PictureName);
		$Extension=$PictureName[sizeof($PictureName)-1];

		// saves the picture into a temporary file
		@move_uploaded_file($Picture,$picturePath.'/tmp.'.$Extension);
	}

	/**
	 * moves the temporary question "tmp" to "quiz-$questionId"
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
	function save($exerciseId=0)
	{
		global $TBL_QUESTIONS, $TBL_EXERCICE_QUESTION, $_course;

		$id=$this->id;
		$question=addslashes($this->question);
		$description=addslashes($this->description);
		$weighting=$this->weighting;
		$position=$this->position;
		$type=$this->type;
		$picture=addslashes($this->picture);

		// question already exists
		if($id)
		{
			$sql="UPDATE $TBL_QUESTIONS SET question='$question',description='$description',ponderation='$weighting',position='$position',type='$type',picture='$picture' WHERE id='$id'";
			api_sql_query($sql,__FILE__,__LINE__);
		}
		// creates a new question
		else
		{
			$sql="SELECT max(position) FROM $TBL_QUESTIONS as question, $TBL_EXERCICE_QUESTION as test_question WHERE question.id=test_question.question_id AND test_question.exercice_id='$exerciseId'";
			$result=api_sql_query($sql);
			$current_position=mysql_result($result,0,0);
			$this -> updatePosition($current_position+1);
			$position = $this -> position;
			
			$sql="INSERT INTO $TBL_QUESTIONS(question,description,ponderation,position,type,picture) VALUES('$question','$description','$weighting','$position','$type','$picture')";
			api_sql_query($sql,__FILE__,__LINE__);

			$this->id=mysql_insert_id();

			// If hotspot, create first answer
			if ($type == HOT_SPOT || $type == HOT_SPOT_ORDER) {
				$TBL_ANSWERS = Database::get_course_table(TABLE_QUIZ_ANSWER);

				$sql="INSERT INTO $TBL_ANSWERS (`id` , `question_id` , `answer` , `correct` , `comment` , `ponderation` , `position` , `hotspot_coordinates` , `hotspot_type` ) VALUES ('1', '$this->id', '', NULL , '', NULL , '1', '0;0|0|0', 'square')";
				api_sql_query($sql,__FILE__,__LINE__);
			}
		}

		// if the question is created in an exercise
		if($exerciseId)
		{
			// adds the exercise into the exercise list of this question
			$this->addToList($exerciseId);
		}
	}

	/**
	 * adds an exercise into the exercise list
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $exerciseId - exercise ID
	 */
	function addToList($exerciseId)
	{
		global $TBL_EXERCICE_QUESTION;

		$id=$this->id;

		// checks if the exercise ID is not in the list
		if(!in_array($exerciseId,$this->exerciseList))
		{
			$this->exerciseList[]=$exerciseId;

			$sql="INSERT INTO $TBL_EXERCICE_QUESTION(question_id,exercice_id) VALUES('$id','$exerciseId')";

			api_sql_query($sql,__FILE__,__LINE__);
		}
	}

	/**
	 * removes an exercise from the exercise list
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $exerciseId - exercise ID
	 * @return - boolean - true if removed, otherwise false
	 */
	function removeFromList($exerciseId)
	{
		global $TBL_EXERCICE_QUESTION;

		$id=$this->id;

		// searches the position of the exercise ID in the list
		$pos=array_search($exerciseId,$this->exerciseList);

		// exercise not found
		if($pos === false)
		{
			return false;
		}
		else
		{
			// deletes the position in the array containing the wanted exercise ID
			unset($this->exerciseList[$pos]);

			$sql="DELETE FROM $TBL_EXERCICE_QUESTION WHERE question_id='$id' AND exercice_id='$exerciseId'";
			api_sql_query($sql,__FILE__,__LINE__);

			return true;
		}
	}

	/**
	 * deletes a question from the database
	 * the parameter tells if the question is removed from all exercises (value = 0),
	 * or just from one exercise (value = exercise ID)
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $deleteFromEx - exercise ID if the question is only removed from one exercise
	 */
	function delete($deleteFromEx=0)
	{
		global $TBL_EXERCICE_QUESTION, $TBL_QUESTIONS, $TBL_REPONSES;

		$id=$this->id;

		// if the question must be removed from all exercises
		if(!$deleteFromEx)
		{
			$sql="DELETE FROM $TBL_EXERCICE_QUESTION WHERE question_id='$id'";
			api_sql_query($sql,__FILE__,__LINE__);

			$sql="DELETE FROM $TBL_QUESTIONS WHERE id='$id'";
			api_sql_query($sql,__FILE__,__LINE__);

			$sql="DELETE FROM $TBL_REPONSES WHERE question_id='$id'";
			api_sql_query($sql,__FILE__,__LINE__);

			$this->removePicture();

			// resets the object
			$this->Question();
		}
		// just removes the exercise from the list
		else
		{
			$this->removeFromList($deleteFromEx);
		}
	}

	/**
	 * duplicates the question
	 *
	 * @author - Olivier Brouckaert
	 * @return - integer - ID of the new question
	 */
	function duplicate()
	{
		global $TBL_QUESTIONS, $picturePath;

		$question=addslashes($this->question);
		$description=addslashes($this->description);
		$weighting=$this->weighting;
		$position=$this->position;
		$type=$this->type;

		$sql="INSERT INTO $TBL_QUESTIONS(question,description,ponderation,position,type) VALUES('$question','$description','$weighting','$position','$type')";
		api_sql_query($sql,__FILE__,__LINE__);

		$id=mysql_insert_id();

		// duplicates the picture
		$this->exportPicture($id);

		return $id;
	}

	/**
	 * Returns an instance of the class corresponding to the type
	 * @param integer $type the type of the question
	 * @return an instance of a Question subclass (or of Questionc class by default)
	 */
	static function getInstance ($type) {

		list($file_name,$class_name) = self::$questionTypes[$type];

		include_once ($file_name);


		if(class_exists($class_name))
		{
			return new $class_name();
		}
		else
		{
			echo 'Can\'t instanciate class '.$class_name.' of type '.$type;
			return null;
		}

	}

	/**
	 * Creates the form to create / edit a question
	 * A subclass can redifine this function to add fields...
	 * @param FormValidator $form the formvalidator instance (by reference)
	 */
	function createForm (&$form) {

		echo '	<style>
					div.row div.label{ width: 10%; }
					div.row div.formw{ width: 89%; }
				</style>';
		
		// question name
		$test=$form->addElement('text','questionName',get_lang('Question'),'size="60"');
		$renderer = $form->defaultRenderer();
		$renderer->setElementTemplate('<div class="row"><div class="label">{label}</div><div class="formw">{element}</div></div>','questionName');
		$form->addRule('questionName', get_lang('GiveQuestion'), 'required');

		// question type
		$answerType= intval($_REQUEST['answerType']);
		$form->addElement('hidden','answerType',$_REQUEST['answerType']);


		// html editor
		global $fck_attribute;
		$fck_attribute = array();
		$fck_attribute['Width'] = '100%';
		$fck_attribute['Height'] = '300';
		$fck_attribute['ToolbarSet'] = 'TestComment';
		$fck_attribute['Config']['IMUploadPath'] = 'upload/test/';
		$fck_attribute['Config']['FlashUploadPath'] = 'upload/test/';
		if(!api_is_allowed_to_edit()) $fck_attribute['Config']['UserStatus'] = 'student';

		$form->add_html_editor('questionDescription', get_lang('QuestionDescription'), false);
		$renderer->setElementTemplate('<div class="row"><div class="label">{label}</div><div class="formw">{element}</div></div>','questionDescription');

		// hidden values
		$form->addElement('hidden','myid',$_REQUEST['myid']);


		// default values
		$defaults = array();
		$defaults['questionName'] = $this -> question;
		$defaults['questionDescription'] = $this -> description;
		$form -> setDefaults($defaults);
	}

	/**
	 * function which process the creation of questions
	 * @param FormValidator $form the formvalidator instance
	 * @param Exercise $objExercise the Exercise instance
	 */
	function processCreation ($form, $objExercise) {

		$this -> updateTitle($form->getSubmitValue('questionName'));
	    $this -> updateDescription($form->getSubmitValue('questionDescription'));
	    $this -> save($objExercise -> id);

	    // modify the exercise
	    $objExercise->addToList($this -> id);
	    $objExercise->save();

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
	static function display_type_menu ()
	{
		global $exerciseId;
		echo '<strong>'.get_lang('AddQ').' :</strong> <div>';
		foreach(self::$questionTypes as $i=>$a_type)
		{
			
			// include the class of the type
			include_once($a_type[0]); 
			
			 // get the picture of the type and the langvar which describes it
			eval('$img = '.$a_type[1].'::$typePicture;');
			eval('$explanation = get_lang('.$a_type[1].'::$explanationLangVar);');

			echo '
			<div id="answer_type_'.$i.'" style="float: left; width:120px; text-align:center">
				<a href="admin.php?newQuestion=yes&answerType='.$i.'">
					<div>
						<img src="'.api_get_path(WEB_IMG_PATH).'/'.$img.'" />
					</div>
					<div>
						'.$explanation.'
					</div>
				</a>
			</div>';
			
		}
		echo '
			<div id="answer_type_'.$i.'" style="float: left; width:120px; text-align:center">
				<a href="question_pool.php?fromExercise='.$exerciseId.'">
			<div>
				<img src="'.api_get_path(WEB_IMG_PATH).'/database.gif" />
			</div>
			<div>
				'.get_lang('GetExistingQuestion').'
			</div>				
			</a>
			</div>
		</div>
		<div style="clear:both"></div>';

	}
}

endif;


?>
