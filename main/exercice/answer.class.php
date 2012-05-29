<?php
/* For licensing terms, see /license.txt */
/**
*	This class allows to instantiate an object of type Answer
*	5 arrays are created to receive the attributes of each answer belonging to a specified question
* 	@package chamilo.exercise
* 	@author Olivier Brouckaert
* 	@version $Id: answer.class.php 21172 2009-06-01 20:58:05Z darkvela $
*/
/**
 * Code
 */
if(!class_exists('Answer')):
/**
 * Answer class
* @package chamilo.exercise
 */
class Answer {
	public $questionId;

	// these are arrays
	public $answer;
	public $correct;
	public $comment;
	public $weighting;
	public $position;
	public $hotspot_coordinates;
	public $hotspot_type;
	public $destination;
	// these arrays are used to save temporarily new answers
	// then they are moved into the arrays above or deleted in the event of cancellation
	public $new_answer;
	public $new_correct;
	public $new_comment;
	public $new_weighting;
	public $new_position;
	public $new_hotspot_coordinates;
	public $new_hotspot_type;

	public $nbrAnswers;
	public $new_nbrAnswers;
	public $new_destination; // id of the next question if feedback option is set to Directfeedback
    public $course; //Course information

/**
	 * constructor of the class
	 *
	 * @author 	Olivier Brouckaert
	 * @param 	integer	Question ID that answers belong to
	 */
	function Answer($questionId, $course_id = null) {
		
		$this->questionId			= intval($questionId);
		$this->answer				= array();
		$this->correct				= array();
		$this->comment				= array();
		$this->weighting			= array();
		$this->position				= array();
		$this->hotspot_coordinates	= array();
		$this->hotspot_type 		= array();
		$this->destination  		= array();
		// clears $new_* arrays
		$this->cancel();
        
        if (!empty($course_id)) {            
            $course_info = api_get_course_info_by_id($course_id);
        } else {            
            $course_info = api_get_course_info();
        }
        $this->course    = $course_info;
        $this->course_id = $course_info['real_id'];
        

		// fills arrays
		$objExercise = new Exercise($this->course['real_id']);
		$objExercise->read($_REQUEST['exerciseId']);		
		if ($objExercise->random_answers=='1') {
			$this->readOrderedBy('rand()', '');// randomize answers
		} else {
			$this->read(); // natural order
		}
	}

	/**
	 * Clears $new_* arrays
	 *
	 * @author - Olivier Brouckaert
	 */
	function cancel() {
		$this->new_answer				= array();
		$this->new_correct				= array();
		$this->new_comment				= array();
		$this->new_weighting			= array();
		$this->new_position				= array();
		$this->new_hotspot_coordinates	= array();
		$this->new_hotspot_type			= array();
		$this->new_nbrAnswers			= 0;
		$this->new_destination			= array();
	}

	/**
	 * Reads answer informations from the data base
	 *
	 * @author - Olivier Brouckaert
	 */
	function read() {		
		$TBL_ANSWER = Database::get_course_table(TABLE_QUIZ_ANSWER);
		$questionId=$this->questionId;
        
		$sql="SELECT id,answer,correct,comment,ponderation, position, hotspot_coordinates, hotspot_type, destination, id_auto FROM
		      $TBL_ANSWER WHERE c_id = {$this->course_id} AND question_id ='".$questionId."' ORDER BY position";

		$result=Database::query($sql);
		$i=1;

		// while a record is found
		while($object=Database::fetch_object($result)) {
			$this->id[$i]					= $object->id;
			$this->answer[$i]				= $object->answer;
			$this->correct[$i]				= $object->correct;
			$this->comment[$i]				= $object->comment;
			$this->weighting[$i]			= $object->ponderation;            
			$this->position[$i]				= $object->position;
			$this->hotspot_coordinates[$i]	= $object->hotspot_coordinates;
			$this->hotspot_type[$i]			= $object->hotspot_type;
			$this->destination[$i]			= $object->destination;
			$this->autoId[$i]				= $object->id_auto;
			$i++;
		}
		$this->nbrAnswers=$i-1;
	}
	/**
	 * reads answer informations from the data base ordered by parameter
	 * @param	string	Field we want to order by
	 * @param	string	DESC or ASC
	 * @author 	Frederic Vauthier
	 */
	function readOrderedBy($field, $order='ASC') {		
		$field = Database::escape_string($field);
		if (empty($field)) {
			$field = 'position';
		}
        
		if ($order != 'ASC' && $order!='DESC') {
			$order = 'ASC';
		}
        
                
		$TBL_ANSWER   = Database::get_course_table(TABLE_QUIZ_ANSWER);
		$TBL_QUIZ     = Database::get_course_table(TABLE_QUIZ_QUESTION);
		$questionId=intval($this->questionId);
		
		$sql = "SELECT type FROM $TBL_QUIZ WHERE c_id = {$this->course_id} AND id = $questionId";
		$result_question=Database::query($sql);
		$question_type=Database::fetch_array($result_question);
		
		$sql="SELECT answer,correct,comment,ponderation,position, hotspot_coordinates, hotspot_type, destination, id_auto " .
				"FROM $TBL_ANSWER WHERE c_id = {$this->course_id} AND question_id='".$questionId."'   " .
				"ORDER BY $field $order";		
		$result=Database::query($sql);	
		
		$i=1;
		// while a record is found
		$doubt_data = null;
		while($object=Database::fetch_object($result)) {
		    if ($question_type['type'] == UNIQUE_ANSWER_NO_OPTION && $object->position == 666) {
		        $doubt_data = $object;
                continue;
		    }
			$this->answer[$i]		= $object->answer;
			$this->correct[$i]		= $object->correct;
			$this->comment[$i]		= $object->comment;
			$this->weighting[$i]	= $object->ponderation;
			$this->position[$i]		= $object->position;
			$this->destination[$i]	= $object->destination;
			$this->autoId[$i]		= $object->id_auto;
			$i++;
		}		
		
		if ($question_type['type'] == UNIQUE_ANSWER_NO_OPTION && !empty($doubt_data)) {		    
		    $this->answer[$i]       = $doubt_data->answer;
			$this->correct[$i]		= $doubt_data->correct;
			$this->comment[$i]		= $doubt_data->comment;
			$this->weighting[$i]	= $doubt_data->ponderation;
			$this->position[$i]		= $doubt_data->position;
			$this->destination[$i]	= $doubt_data->destination;
			$this->autoId[$i]		= $doubt_data->id_auto;
			$i++;		     
	    }
        $this->nbrAnswers=$i-1;
	}


	/**
	 * returns the autoincrement id identificator
	 *
	 * @author - Juan Carlos Ra�a
	 * @return - integer - answer num
	 */
	function selectAutoId($id) {
		return $this->autoId[$id];
	}


	/**
	 * returns the number of answers in this question
	 *
	 * @author - Olivier Brouckaert
	 * @return - integer - number of answers
	 */
	function selectNbrAnswers() {
		return $this->nbrAnswers;
	}

	/**
	 * returns the question ID which the answers belong to
	 *
	 * @author - Olivier Brouckaert
	 * @return - integer - the question ID
	 */
	function selectQuestionId() {
		return $this->questionId;
	}

	/**
	 * returns the question ID of the destination question
	 *
	 * @author - Julio Montoya
	 * @return - integer - the question ID
	 */
	function selectDestination($id) {
		return $this->destination[$id];
	}

/**
	 * returns the answer title
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $id - answer ID
	 * @return - string - answer title
	 */
	function selectAnswer($id) {
		return $this->answer[$id];
	}

	/**
	 * return array answer by id else return a bool
	 */
	function selectAnswerByAutoId($auto_id) {
		$TBL_ANSWER = Database::get_course_table(TABLE_QUIZ_ANSWER);        
        
		$auto_id = intval($auto_id);
		$sql="SELECT id, answer FROM $TBL_ANSWER WHERE c_id = {$this->course_id} AND id_auto='$auto_id'";
		$rs = Database::query($sql);

		if (Database::num_rows($rs)>0) {
			$row = Database::fetch_array($rs);
			return $row;
		}
		return false;
	}

	/**
	 * returns the answer title from an answer's position
	 *
	 * @author - Yannick Warnier
	 * @param - integer $id - answer ID
	 * @return - bool - answer title
	 */
	function selectAnswerIdByPosition($pos) {
		foreach ($this->position as $k => $v) {
			if ($v != $pos) { continue; }
			return $k;
		}
		return false;
	}

	/**
	 * Returns a list of answers
	 * @author Yannick Warnier <ywarnier@beeznest.org>
	 * @return array	List of answers where each answer is an array of (id, answer, comment, grade) and grade=weighting
	 */
	 function getAnswersList($decode = false) {
	 	$list = array();
	 	for($i = 1; $i<=$this->nbrAnswers;$i++){
	 		if(!empty($this->answer[$i])){
	 			
	 			//Avoid problems when parsing elements with accents
	 			if ($decode) {
	        		$this->answer[$i] 	= api_html_entity_decode($this->answer[$i], ENT_QUOTES, api_get_system_encoding());
	        		$this->comment[$i]	= api_html_entity_decode($this->comment[$i], ENT_QUOTES, api_get_system_encoding());
	 			}
	        	
	 			$list[] = array(
						'id'            => $i,
						'answer'        => $this->answer[$i],
						'comment'       => $this->comment[$i],
						'grade'         => $this->weighting[$i],
						'hotspot_coord' => $this->hotspot_coordinates[$i],
						'hotspot_type'	=> $this->hotspot_type[$i],
						'correct'		=> $this->correct[$i],
						'destination'	=> $this->destination[$i]
				);
	 		}
	 	}
	 	return $list;
	 }
	/**
	 * Returns a list of grades
	 * @author Yannick Warnier <ywarnier@beeznest.org>
	 * @return array	List of grades where grade=weighting (?)
	 */
	 function getGradesList() {
	 	$list = array();
	 	for($i = 0; $i<$this->nbrAnswers;$i++){
	 		if(!empty($this->answer[$i])){
	 			$list[$i] = $this->weighting[$i];
	 		}
	 	}
	 	return $list;
	 }

	 /**
	  * Returns the question type
	  * @author	Yannick Warnier <ywarnier@beeznest.org>
	  * @return	integer	The type of the question this answer is bound to
	  */
	 function getQuestionType() {
	 	$TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
	 	$sql = "SELECT type FROM $TBL_QUESTIONS WHERE c_id = {$this->course_id} AND id = '".$this->questionId."'";
	 	$res = Database::query($sql);
	 	if(Database::num_rows($res)<=0){
	 		return null;
	 	}
	 	$row = Database::fetch_array($res);
	 	return $row['type'];
	 }


	/**
	 * tells if answer is correct or not
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $id - answer ID
	 * @return - integer - 0 if bad answer, not 0 if good answer
	 */
	function isCorrect($id)
	{
		return $this->correct[$id];
	}

	/**
	 * returns answer comment
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $id - answer ID
	 * @return - string - answer comment
	 */
	function selectComment($id)
	{
		return $this->comment[$id];
	}

	/**
	 * returns answer weighting
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $id - answer ID
	 * @return - integer - answer weighting
	 */
	function selectWeighting($id)
	{
		return $this->weighting[$id];
	}

	/**
	 * returns answer position
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $id - answer ID
	 * @return - integer - answer position
	 */
	function selectPosition($id)
	{
		return $this->position[$id];
	}

	/**
	 * returns answer hotspot coordinates
	 *
	 * @author	Olivier Brouckaert
	 * @param	integer	Answer ID
	 * @return	integer	Answer position
	 */
	function selectHotspotCoordinates($id)
	{
		return $this->hotspot_coordinates[$id];
	}

	/**
	 * returns answer hotspot type
	 *
	 * @author	Toon Keppens
	 * @param	integer		Answer ID
	 * @return	integer		Answer position
	 */
	function selectHotspotType($id)
	{
		return $this->hotspot_type[$id];
	}

	/**
	 * creates a new answer
	 *
	 * @author Olivier Brouckaert
	 * @param string 	answer title
	 * @param integer 	0 if bad answer, not 0 if good answer
	 * @param string 	answer comment
	 * @param integer 	answer weighting
	 * @param integer 	answer position
	 * @param coordinates 	Coordinates for hotspot exercises (optional)
	 * @param integer		Type for hotspot exercises (optional)
	 */
	function createAnswer($answer,$correct,$comment,$weighting,$position,$new_hotspot_coordinates = NULL, $new_hotspot_type = NULL,$destination='') {
		$this->new_nbrAnswers++;
		$id=$this->new_nbrAnswers;
		$this->new_answer[$id]=$answer;
		$this->new_correct[$id]=$correct;
		$this->new_comment[$id]=$comment;
		$this->new_weighting[$id]=$weighting;
		$this->new_position[$id]=$position;
		$this->new_hotspot_coordinates[$id]=$new_hotspot_coordinates;
		$this->new_hotspot_type[$id]=$new_hotspot_type;
		$this->new_destination[$id]=$destination;
	}

	/**
	 * updates an answer
	 *
	 * @author Toon Keppens
	 * @param	string	Answer title
	 * @param	string	Answer comment
	 * @param	integer	Answer weighting
	 * @param	integer	Answer position
	 */
	function updateAnswers($answer,$comment,$weighting,$position,$destination) {
		$TBL_REPONSES = Database :: get_course_table(TABLE_QUIZ_ANSWER);

		$questionId=$this->questionId;
		$sql = "UPDATE $TBL_REPONSES SET " .
				"answer = '".Database::escape_string($answer)."', " .
				"comment = '".Database::escape_string($comment)."', " .
				"ponderation = '".Database::escape_string($weighting)."', " .
				"position = '".Database::escape_string($position)."', " .
				"destination = '".Database::escape_string($destination)."' " .
				"WHERE c_id = {$this->course_id} AND id = '".Database::escape_string($position)."' " .
				"AND question_i = '".Database::escape_string($questionId)."'";

		Database::query($sql);
	}

	/**
	 * Records answers into the data base
	 *
	 * @author - Olivier Brouckaert
	 */
	function save() {
		$TBL_REPONSES = Database :: get_course_table(TABLE_QUIZ_ANSWER);
		$questionId   = intval($this->questionId);

		// removes old answers before inserting of new ones
		$sql = "DELETE FROM $TBL_REPONSES WHERE c_id = {$this->course_id} AND question_id = '".($questionId)."'";
		Database::query($sql);
		$c_id = $this->course['real_id'];
		// inserts new answers into data base
		$sql = "INSERT INTO $TBL_REPONSES (c_id, id, question_id, answer,correct,comment, ponderation,position,hotspot_coordinates,hotspot_type,destination) VALUES ";
		for ($i=1;$i <= $this->new_nbrAnswers;$i++) {
			$answer					= Database::escape_string($this->new_answer[$i]);
			$correct				= Database::escape_string($this->new_correct[$i]);
			$comment				= Database::escape_string($this->new_comment[$i]);
			$weighting				= Database::escape_string($this->new_weighting[$i]);
			$position				= Database::escape_string($this->new_position[$i]);
			$hotspot_coordinates	= Database::escape_string($this->new_hotspot_coordinates[$i]);
			$hotspot_type			= Database::escape_string($this->new_hotspot_type[$i]);
			$destination			= Database::escape_string($this->new_destination[$i]);

			$sql.="($c_id, '$i','$questionId','$answer','$correct','$comment','$weighting','$position','$hotspot_coordinates','$hotspot_type','$destination'),";
		}
		$sql = api_substr($sql,0,-1);
        
		Database::query($sql);

		// moves $new_* arrays
		$this->answer=$this->new_answer;
		$this->correct=$this->new_correct;
		$this->comment=$this->new_comment;
		$this->weighting=$this->new_weighting;
		$this->position=$this->new_position;
		$this->hotspot_coordinates=$this->new_hotspot_coordinates;
		$this->hotspot_type=$this->new_hotspot_type;

		$this->nbrAnswers=$this->new_nbrAnswers;
		$this->destination=$this->new_destination;
		// clears $new_* arrays
		$this->cancel();
	}

	/**
	 * Duplicates answers by copying them into another question
	 *
	 * @author Olivier Brouckaert
	 * @param  int question id
     * @param  array course info (result of the function api_get_course_info() ) 
	 */
	function duplicate($newQuestionId, $course_info = null) {
        require_once api_get_path(LIBRARY_PATH).'document.lib.php';
        
        if (empty($course_info)) {
            $course_info = $this->course;
        } else {
            $course_info = $course_info;
        }
        
		$TBL_REPONSES = Database :: get_course_table(TABLE_QUIZ_ANSWER);
        
        if (self::getQuestionType() == MULTIPLE_ANSWER_TRUE_FALSE) {                
                           
            //Selecting origin options
            $origin_options = Question::readQuestionOption($this->selectQuestionId(), $course_info['real_id']);
            
            if (!empty($origin_options)) {
                foreach($origin_options as $item) {
            	   $new_option_list[]=$item['id'];
                }
            }            
            
            $destination_options = Question::readQuestionOption($newQuestionId, $course_info['real_id']);
            $i=0;
            $fixed_list = array();
            if (!empty($destination_options)) {
                foreach($destination_options as $item) {                 
                    $fixed_list[$new_option_list[$i]] = $item['id'];
                    $i++;
                }
            }            
        }

		// if at least one answer
		if ($this->nbrAnswers) {
			// inserts new answers into data base
			$sql="INSERT INTO $TBL_REPONSES (c_id, id,question_id,answer,correct,comment, ponderation,position,hotspot_coordinates,hotspot_type,destination) VALUES";
			$c_id = $course_info['real_id'];
			
			for($i=1;$i <= $this->nbrAnswers;$i++) {
                if ($course_info['db_name'] != $this->course['db_name']) {                    
                	$this->answer[$i]  = DocumentManager::replace_urls_inside_content_html_from_copy_course($this->answer[$i],$this->course['id'], $course_info['id']) ;
                    $this->comment[$i] = DocumentManager::replace_urls_inside_content_html_from_copy_course($this->comment[$i],$this->course['id'], $course_info['id']) ;                    
                }
                
				$answer					= Database::escape_string($this->answer[$i]);
				$correct				= Database::escape_string($this->correct[$i]);
                
                if (self::getQuestionType() == MULTIPLE_ANSWER_TRUE_FALSE) {                    
                    $correct = $fixed_list[intval($correct)];
                }
                
				$comment				= Database::escape_string($this->comment[$i]);
				$weighting				= Database::escape_string($this->weighting[$i]);
				$position				= Database::escape_string($this->position[$i]);
				$hotspot_coordinates	= Database::escape_string($this->hotspot_coordinates[$i]);
				$hotspot_type			= Database::escape_string($this->hotspot_type[$i]);
				$destination			= Database::escape_string($this->destination[$i]);
				$sql.="($c_id, '$i','$newQuestionId','$answer','$correct','$comment'," .
						"'$weighting','$position','$hotspot_coordinates','$hotspot_type','$destination'),";
			}

			$sql=api_substr($sql,0,-1);
			Database::query($sql);
		}
	}
}
endif;