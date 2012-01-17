<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains a class used like library provides functions for 
 * course description tool. It's also used like model to 
 * course_description_controller (MVC pattern)
 * @author Christian Fasanando <christian1827@gmail.com>
 * @package chamilo.course_description
 */
/**
 * Code
 */
/**
 * CourseDescription can be used to instanciate objects or as a library to manage course descriptions
 * @package chamilo.course_description
 */
class CourseDescription
{
	private $id;
	private $title;
	private $content;
    private $session_id;
    private $description_type;
    private $progress;

   	/**
	 * Constructor
	 */
	public function __construct() {}
	
	/**
	 * Returns an array of objects of type CourseDescription corresponding to a specific course, without session ids (session id = 0)
	 * 
	 * @param int Course id
	 * @return array Array of CourseDescriptions
	 */
	public static function get_descriptions($course_id) {
		// Get course code
		$course_info = api_get_course_info_by_id($course_id);
        if (!empty($course_info)) {
            $course_id = $course_info['real_id'];
        } else {
            return array();
        }		
		$t_course_desc = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
		$sql = "SELECT * FROM $t_course_desc WHERE c_id = $course_id AND session_id = '0';";
		$sql_result = Database::query($sql);
		$results = array();
		while($row = Database::fetch_array($sql_result)) {
			$desc_tmp = new CourseDescription();
			$desc_tmp->set_id($row['id']);
			$desc_tmp->set_title($row['title']);
			$desc_tmp->set_content($row['content']);
			$desc_tmp->set_session_id($row['session_id']);
			$desc_tmp->set_description_type($row['description_type']);
			$desc_tmp->set_progress($row['progress']);
			$results[] = $desc_tmp;
		}
		return $results;
	}
		

    /**
     * Get all data of course description by session id,
     * first you must set session_id property with the object CourseDescription
     * @return array
     */
	public function get_description_data() {
		$tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
		$condition_session = api_get_session_condition($this->session_id, true, true);
        $course_id = api_get_course_int_id();
		$sql = "SELECT * FROM $tbl_course_description WHERE c_id = $course_id $condition_session ORDER BY id ";
		$rs = Database::query($sql);
		$data = array();
		while ($description = Database::fetch_array($rs)) {			
			$data['descriptions'][$description['id']] = Security::remove_XSS($description, STUDENT);
			//reload titles to ensure we have the last version (after edition)
			//$data['default_description_titles'][$description['id']] = Security::remove_XSS($description['title'], STUDENT);
		}
		return $data;
	}

	/**
     * Get all data of course description by session id,
     * first you must set session_id property with the object CourseDescription
     * @return array
     */
	public function get_description_history($description_type) {
		$tbl_stats_item_property = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ITEM_PROPERTY);
		$tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
		
		$description_id = $this->get_id_by_description_type($description_type);
		$item_property_id = api_get_item_property_id($course_id, TOOL_COURSE_DESCRIPTION, $description_id);
		
		$course_id = api_get_course_int_id();

		$sql = "SELECT tip.id, tip.course_id, tip.item_property_id, tip.title, tip.content, tip.progress, tip.lastedit_date, tip.session_id 
				FROM $tbl_stats_item_property tip INNER JOIN $tbl_item_property ip 
				ON ip.tool = '".TOOL_COURSE_DESCRIPTION."' AND ip.id = tip.item_property_id
				WHERE ip.c_id = $course_id AND tip.course_id = '$course_id' AND tip.session_id = '".intval($this->session_id)."' 
				ORDER BY tip.lastedit_date DESC";

		$rs = Database::query($sql);
		$data = array();
		while ($description = Database::fetch_array($rs)) {
			$data['descriptions'][] = $description;
		}
		return $data;
	}

	/**
     * Get all data by description and session id,
     * first you must set session_id property with the object CourseDescription
     * @param 	int		description type
     * @param   string  course code (optional)
     * @param	int		session id (optional)
     * @return array
     */
	public function get_data_by_description_type($description_type, $course_code = '', $session_id = null) {
		$tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
		$course_id = api_get_course_int_id();
		
		if (!isset($session_id)) {
			$session_id = $this->session_id;
		}		
		$condition_session = api_get_session_condition($session_id);		
		if (!empty($course_code)) {
			$course_info = api_get_course_info($course_code);	
            $course_id = $course_info['real_id'];
		}
        $description_type = intval($description_type);
		$sql = "SELECT * FROM $tbl_course_description WHERE c_id = $course_id AND description_type='$description_type' $condition_session ";
		$rs = Database::query($sql);
		$data = array();
		if ($description = Database::fetch_array($rs)) {
			$data['description_title']	 = $description['title'];
			$data['description_content'] = $description['content'];
			$data['progress'] 			 = $description['progress'];
		}
		return $data;
	}
    
   
    public function get_data_by_id($id, $course_code = '', $session_id = null) {
		$tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
		$course_id = api_get_course_int_id();
		
		if (!isset($session_id)) {
			$session_id = $this->session_id;
		}		
		$condition_session = api_get_session_condition($session_id);		
		if (!empty($course_code)) {
			$course_info = api_get_course_info($course_code);	
            $course_id = $course_info['real_id'];
		}
        $id = intval($id);
		$sql = "SELECT * FROM $tbl_course_description WHERE c_id = $course_id AND id='$id' $condition_session ";
		$rs = Database::query($sql);
		$data = array();
		if ($description = Database::fetch_array($rs)) {
            $data['description_type']	 = $description['description_type'];
			$data['description_title']	 = $description['title'];
			$data['description_content'] = $description['content'];
			$data['progress'] 			 = $description['progress'];
		}
		return $data;
	}
    

	/**
     * Get maximum description type by session id, first you must set session_id properties with the object CourseDescription
     * @return  int  maximum description time adding one
     */
	public function get_max_description_type() {
		$tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
        $course_id = api_get_course_int_id();
        
		$sql = "SELECT MAX(description_type) as MAX FROM $tbl_course_description WHERE c_id = $course_id AND session_id='".$this->session_id."'";
		$rs  = Database::query($sql);
		$max = Database::fetch_array($rs);
		$description_type = $max['MAX']+1;
		if ($description_type < ADD_BLOCK) {
			$description_type = ADD_BLOCK;
		}
		return $description_type;
	}

	/**
     * Insert a description to the course_description table,
     * first you must set description_type, title, content, progress and session_id properties with the object CourseDescription
     * @return  int  affected rows
     */
	public function insert() {		
		$course_id = api_get_course_int_id();
		$tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION);		
		$sql = "INSERT IGNORE INTO $tbl_course_description SET
				c_id 				=  $course_id, 
				description_type	= '".intval($this->description_type)."', 
				title 				= '".Database::escape_string($this->title)."', 
				content 			= '".Database::escape_string($this->content)."', 
				progress 			= '".intval($this->progress)."', 
				session_id = '".intval($this->session_id)."' ";
		Database::query($sql);
		$last_id = Database::insert_id();
		$affected_rows = Database::affected_rows();
		if ($last_id > 0) {
			//insert into item_property
			api_item_property_update(api_get_course_info(), TOOL_COURSE_DESCRIPTION, $last_id, 'CourseDescriptionAdded', api_get_user_id());
		}
		return $affected_rows;

	}

	/**
     * Insert a row like history inside track_e_item_property table
     * first you must set description_type, title, content, progress and session_id properties with the object CourseDescription
     * @param 	int 	description type
     * @return  int		affected rows
     */
	public function insert_stats($description_type) {
		$tbl_stats_item_property = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ITEM_PROPERTY);
		$description_id = $this->get_id_by_description_type($description_type);
		$course_id = api_get_real_course_id();
		$course_code = api_get_course_id();
		$item_property_id = api_get_item_property_id($course_code, TOOL_COURSE_DESCRIPTION, $description_id);
		$sql = "INSERT IGNORE INTO $tbl_stats_item_property SET
				c_id				= ".api_get_course_int_id().",
				course_id 			= '$course_id',
			 	item_property_id 	= '$item_property_id',
			 	title 				= '".Database::escape_string($this->title)."',
			 	content 			= '".Database::escape_string($this->content)."',
			 	progress 			= '".intval($this->progress)."',
			 	lastedit_date 		= '".date('Y-m-d H:i:s')."',
			 	lastedit_user_id 	= '".api_get_user_id()."',
			 	session_id			= '".intval($this->session_id)."'";
		Database::query($sql);
		$affected_rows = Database::affected_rows();
		return $affected_rows;
	}

	/**
     * Update a description, first you must set description_type, title, content, progress
     * and session_id properties with the object CourseDescription
     * @return int	affected rows
     */
	public function update() {		
		$tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION);	
		$sql = "UPDATE $tbl_course_description SET  
						title       = '".Database::escape_string($this->title)."', 
						content     = '".Database::escape_string($this->content)."', 
						progress    = '".$this->progress."' 
				WHERE 	id          = '".intval($this->id)."' AND 
						session_id  = '".$this->session_id."' AND
						c_id = ".api_get_course_int_id()."
						";
		Database::query($sql);
		$affected_rows = Database::affected_rows();
		
		if ($this->id > 0) {
			//insert into item_property
			api_item_property_update(api_get_course_info(), TOOL_COURSE_DESCRIPTION, $this->id, 'CourseDescriptionUpdated', api_get_user_id());
		}
		return $affected_rows;
	}

	/**
     * Delete a description, first you must set description_type and session_id properties with the object CourseDescription
     * @return int	affected rows
     */
	public function delete() {
		$tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION);		
		$course_id = api_get_course_int_id();
		$sql = "DELETE FROM $tbl_course_description WHERE c_id = $course_id AND id = '".intval($this->id)."' AND session_id = '".intval($this->session_id)."'";
		Database::query($sql);
		$affected_rows = Database::affected_rows();
		if ($this->id > 0) {
			//insert into item_property
			api_item_property_update(api_get_course_info(), TOOL_COURSE_DESCRIPTION, $this->id, 'CourseDescriptionDeleted', api_get_user_id());
		}
		return $affected_rows;
	}

	/**
	 * Get description id by description type
	 * @param int description type
	 * @return int description id
	 */
	public function get_id_by_description_type($description_type) {
		$tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
        $course_id = api_get_course_int_id();
        
		$sql = "SELECT id FROM $tbl_course_description WHERE c_id = $course_id AND description_type = '".intval($description_type)."'";
		$rs  = Database::query($sql);
		$row = Database::fetch_array($rs);
		$description_id = $row['id'];
		return $description_id;
	}

	/**
	 * get thematic progress in porcent for a course,
	 * first you must set session_id property with the object CourseDescription
	 * @param bool		true for showing a icon about the progress, false otherwise (optional)
	 * @param int		Description type (optional)
	 * @return string   img html
	 */
	 public function get_progress_porcent($with_icon = false, $description_type = THEMATIC_ADVANCE) {
	 	$tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION);
	 	$session_id = intval($session_id);
        $course_id = api_get_course_int_id();
        
		$sql = "SELECT progress FROM $tbl_course_description WHERE c_id = $course_id AND description_type = '".intval($description_type)."' AND session_id = '".intval($this->session_id)."' ";
		$rs  = Database::query($sql);
		$progress = '';
		$img = '';
		$title = '0%';
		$image = 'level_0.png';
		if (Database::num_rows($rs) > 0) {
			$row = Database::fetch_array($rs);
			$progress = $row['progress'].'%';
			$image = 'level_'.$row['progress'].'.png';
		}
		if ($with_icon) {
			$img = Display::return_icon($image,get_lang('ThematicAdvance'),array('style'=>'vertical-align:middle'));
		}
		$progress = $img.$progress;
		return $progress;
	 }

	/**
	 * Get description titles by default
	 * @return array
	 */
	public function get_default_description_title() {
		$default_description_titles = array();
		$default_description_titles[1]= get_lang('GeneralDescription');
		$default_description_titles[2]= get_lang('Objectives');
		$default_description_titles[3]= get_lang('Topics');
		$default_description_titles[4]= get_lang('Methodology');
		$default_description_titles[5]= get_lang('CourseMaterial');
		$default_description_titles[6]= get_lang('HumanAndTechnicalResources');
		$default_description_titles[7]= get_lang('Assessment');
		
		$default_description_titles[8]= get_lang('Other');
		return $default_description_titles;
	}

	/**
	 * Get description titles editable by default
	 * @return array
	 */
	public function get_default_description_title_editable() {
		$default_description_title_editable = array();
		$default_description_title_editable[1] = true;
		$default_description_title_editable[2] = true;
		$default_description_title_editable[3] = true;
		$default_description_title_editable[4] = true;
		$default_description_title_editable[5] = true;
		$default_description_title_editable[6] = true;
		$default_description_title_editable[7] = true;
		//$default_description_title_editable[8] = true;
		return $default_description_title_editable;
	}

	/**
	 * Get description icons by default
	 * @return array
	 */
	public function get_default_description_icon() {
		$default_description_icon = array();
		$default_description_icon[1]= 'info.png';
		$default_description_icon[2]= 'objective.png';
		$default_description_icon[3]= 'topics.png';
		$default_description_icon[4]= 'strategy.png';
		$default_description_icon[5]= 'laptop.png';
		$default_description_icon[6]= 'teacher.png';
		$default_description_icon[7]= 'assessment.png';
		//$default_description_icon[8]= 'porcent.png';
		$default_description_icon[8]= 'wizard.png';
		return $default_description_icon;
	}

	/**
	 * Get questions by default for help
	 * @return array
	 */
	public function get_default_question() {
		$question = array();
		$question[1]= get_lang('GeneralDescriptionQuestions');
		$question[2]= get_lang('ObjectivesQuestions');
		$question[3]= get_lang('TopicsQuestions');
		$question[4]= get_lang('MethodologyQuestions');
		$question[5]= get_lang('CourseMaterialQuestions');
		$question[6]= get_lang('HumanAndTechnicalResourcesQuestions');
		$question[7]= get_lang('AssessmentQuestions');
		//$question[8]= get_lang('ThematicAdvanceQuestions');
		return $question;
	}

	/**
	 * Get informations by default for help
	 * @return array
	 */
	public function get_default_information() {
		$information = array();
		$information[1]= get_lang('GeneralDescriptionInformation');
		$information[2]= get_lang('ObjectivesInformation');
		$information[3]= get_lang('TopicsInformation');
		$information[4]= get_lang('MethodologyInformation');
		$information[5]= get_lang('CourseMaterialInformation');
		$information[6]= get_lang('HumanAndTechnicalResourcesInformation');
		$information[7]= get_lang('AssessmentInformation');
		//$information[8]= get_lang('ThematicAdvanceInformation');
		return $information;
	}

	/**
	 * Set description id
	 * @return void
	 */
	public function set_id($id) {
		$this->id = $id;
	}

   	/**
	 * Set description title
	 * @return void
	 */
	public function set_title($title) {
		$this->title = $title;
	}

    /**
	 * Set description content
	 * @return void
	 */
	public function set_content($content) {
		$this->content = $content;
	}

	/**
	 * Set description session id
	 * @return void
	 */
	public function set_session_id($session_id) {
		$this->session_id = $session_id;
	}

   	/**
	 * Set description type
	 * @return void
	 */
	public function set_description_type($description_type) {
		$this->description_type = $description_type;
	}

	/**
	 * Set progress of a description
	 * @return void
	 */
	public function set_progress($progress) {
		$this->progress = $progress;
	}

	/**
	 * get description id
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

   	/**
	 * get description title
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

   	/**
	 * get description content
	 * @return string
	 */
	public function get_content() {
		return $this->content;
	}

	/**
	 * get session id
	 * @return int
	 */
	public function get_session_id() {
		return $this->session_id;
	}

   	/**
	 * get description type
	 * @return int
	 */
	public function get_description_type() {
		return $this->description_type;
	}

	/**
	 * get progress of a description
	 * @return int
	 */
	public function get_progress() {
		return $this->progress;
	}
}