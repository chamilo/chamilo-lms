<?php //$id:$
/**
 * This file contains the lp_item class, that inherits from the learnpath class
 * @package	dokeos.learnpath
 * @author	Yannick Warnier <ywarnier@beeznest.org>
 * @license	GNU/GPL - See Dokeos license directory for details
 */
/**
 * lp_item defines items belonging to a learnpath. Each item has a name, a score, a use time and additional
 * information that enables tracking a user's progress in a learning path
 * @package	dokeos.learnpath
 */
class learnpathItem{
	var $attempt_id; //also called "objectives" SCORM-wise
    var $audio; //the path to an audio file (stored in document/audio/)
	var $children = array(); //contains the ids of children items
	var $condition; //if this item has a special condition embedded
	var $current_score;
	var $current_start_time;
	var $current_stop_time;
	var $current_data = '';
	var $db_id;
	var $db_item_view_id = '';
	var $description = '';
	var $file;
	//at the moment, interactions are just an array of arrays with a structure of 8 text fields
	//id(0), type(1), time(2), weighting(3),correct_responses(4),student_response(5),result(6),latency(7)
	var $interactions = array();
	var $interactions_count = 0;
	var $objectives = array();
	var $objectives_count = 0;
	var $launch_data = '';
	var $lesson_location = '';
	var $level = 0;
	//var $location; //only set this for SCORM?
	var $lp_id;
	var $max_score;
	var $mastery_score;
	var $min_score;
	var $max_time_allowed = '';
	var $name;
	var $next;
	var $parent;
	var $path;
	var $possible_status = array('not attempted','incomplete','completed','passed','failed','browsed');
	var $prereq_string = '';
	var $prereq_alert = '';
	var $prereqs = array();
	var $previous;
	var $prevent_reinit = 1;
	var $ref;
	var $save_on_close = true;
    var $search_did = NULL;
	var $status;
	var $title;
	var $type; // this attribute can contain chapter|link|student_publication|module|quiz|document|forum|thread
	var $view_id;
	
	var $debug = 0; //logging param
    /**
     * Class constructor. Prepares the learnpath_item for later launch
     * 
     * Don't forget to use set_lp_view() if applicable after creating the item.
     * Setting an lp_view will finalise the item_view data collection
     * @param	integer	Learnpath item ID
     * @param	integer	User ID
     * @return	boolean	True on success, false on failure
     */
    function learnpathItem($db_id, $user_id) {
    	//get items table
    	if($this->debug>0){error_log('New LP - In learnpathItem constructor: '.$db_id.','.$user_id,0);}
    	$items_table = Database::get_course_table('lp_item');
    	$id = (int) $db_id;
    	$sql = "SELECT * FROM $items_table WHERE id = $id";
    	//error_log('New LP - Creating item object from DB: '.$sql,0);
    	$res = @api_sql_query($sql);
    	if(Database::num_rows($res)<1)
    	{
    		$this->error = "Could not find given learnpath item in learnpath_item table";
    		//error_log('New LP - '.$this->error,0);
    		return false;
    	}
    	$row = Database::fetch_array($res);
    	$this->lp_id	 = $row['lp_id'];
    	$this->max_score = $row['max_score'];
    	$this->min_score = $row['min_score'];
    	$this->name 	 = $row['title'];
    	$this->type 	 = $row['item_type'];
    	$this->ref		 = $row['ref'];
    	$this->title	 = $row['title'];
    	$this->description = $row['description'];
    	$this->path		 = $row['path'];
    	$this->mastery_score = $row['mastery_score'];
    	$this->parent	 = $row['parent_item_id'];
    	$this->next		 = $row['next_item_id'];
    	$this->previous	 = $row['previous_item_id'];
    	$this->display_order = $row['display_order'];
    	$this->prereq_string = $row['prerequisite'];
    	$this->max_time_allowed = $row['max_time_allowed'];
    	if(isset($row['launch_data'])){
    		$this->launch_data = $row['launch_data'];
    	}
		$this->save_on_close = true;
		$this->db_id = $id;

        // get search_did
        if (api_get_setting('search_enabled')=='true') {
            $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
            $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level=%d LIMIT 1';
            // TODO: verify if it's possible to assume the actual course instead of getting it from db
            $sql = sprintf($sql, $tbl_se_ref, api_get_course_id(), TOOL_LEARNPATH, $this->lp_id, $id);
            $res = api_sql_query($sql, __FILE__, __LINE__);
            if (Database::num_rows($res) >  0) {
    	        $se_ref = Database::fetch_array($res);
    	        $this->search_did = (int)$se_ref['search_did'];
            }
        }
        $this->audio = $row['audio'];
		
		//error_log('New LP - End of learnpathItem constructor for item '.$id,0);
    	return true;
    }
    /**
     * Adds a child to the current item
     */
    function add_child($item)
    {
    	if($this->debug>0){error_log('New LP - In learnpathItem::add_child()',0);}
    	if(!empty($item))
    	{
    		//do not check in DB as we expect the call to come from the learnpath class which should
    		//be aware of any fake
    		$this->children[] = $item;
    	}
    }
    /**
	 * Adds an interaction to the current item
	 * @param	int		Index (order ID) of the interaction inside this item
	 * @param	array	Array of parameters: id(0), type(1), time(2), weighting(3),correct_responses(4),student_response(5),result(6),latency(7)
	 * @result	void
     */
    function add_interaction($index,$params)
    {
		$this->interactions[$index] = $params;
		//take the current maximum index to generate the interactions_count
		if(($index+1)>$this->interactions_count){
			$this->interactions_count = $index+1;
		}
    	/*
		if(is_array($this->interactions[$index]) && count($this->interactions[$index])>0){
    		$this->interactions[$index] = $params;
    		return false;
    	}else{
    		if(count($params)==8){//we rely on the calling script to provide parameters in the right order
    			$this->interactions[$index] = $params;
    			return true;
    		}else{
    			return false;
    		}
    	}
    	*/
    }
    /**
	 * Adds an objective to the current item
	 * @param	array	Array of parameters: id(0), status(1), score_raw(2), score_max(3), score_min(4)
	 * @result	void
     */
    function add_objective($index,$params)
    {
    	if(empty($params[0])){return null;}
		$this->objectives[$index] = $params;
		//take the current maximum index to generate the objectives_count
		if((count($this->objectives)+1)>$this->objectives_count){
			$this->objectives_count = (count($this->objectives)+1);
		}
    }
    
    /**
     * Closes/stops the item viewing. Finalises runtime values. If required, save to DB.
     * @return	boolean	True on success, false otherwise
     */
    function close()
    {
		if($this->debug>0){error_log('New LP - In learnpathItem::close()',0);}
	   	$this->current_stop_time = time();
	   	$type = $this->get_type();
    	if($type != 'sco'){
    		if($type == TOOL_QUIZ or $type == TOOL_HOTPOTATOES)
    		{
    			$this->get_status(true,true);//update status (second option forces the update)		
    		}
    		else
    		{
    			$this->status = $this->possible_status[2];
    		}
    	}
    	if($this->save_on_close)
    	{
    		$this->save();
    	}	
    	return true;
    }
    /**
     * Deletes all traces of this item in the database
     * @return	boolean	true. Doesn't check for errors yet.
     */
    function delete()
    {
    	if($this->debug>0){error_log('New LP - In learnpath_item::delete() for item '.$this->db_id,0);}
    	$lp_item_view = Database::get_course_table('lp_item_view');
    	$lp_item = Database::get_course_table('lp_item');
    	$sql_del_view = "DELETE FROM $lp_item_view WHERE item_id = ".$this->db_id;
		//error_log('New LP - Deleting from lp_item_view: '.$sql_del_view,0);
		$res_del_view = api_sql_query($sql_del_view);

        $sql_sel = "SELECT * FROM $lp_item WHERE id = ".$this->db_id;
        $res_sel = api_sql_query($sql_sel,__FILE__,__LINE__);
        if(Database::num_rows($res_sel)<1){return false;}
        $row = Database::fetch_array($res_sel);

    	$sql_del_item = "DELETE FROM $lp_item WHERE id = ".$this->db_id;
		//error_log('New LP - Deleting from lp_item: '.$sql_del_view,0);
        $res_del_item = api_sql_query($sql_del_item);

        if (api_get_setting('search_enabled') == 'true') {
        	if (!is_null($this->search_did)) {
        		require_once(api_get_path(LIBRARY_PATH) .'search/DokeosIndexer.class.php');
        		$di = new DokeosIndexer();
        		$di->remove_document($this->search_did);
        	}
        }

    	return true;
    }
    /**
     * Drops a child from the children array
     * @param	string index of child item to drop
     * @return 	void
     */
    function drop_child($item)
    {
    	if($this->debug>0){error_log('New LP - In learnpathItem::drop_child()',0);}
    	if(!empty($item))
    	{
    		foreach($this->children as $index => $child)
    		{
    			if($child == $item){
    				$this->children[$index] = null;
    			}
    		}
    	}
    }
    /**
     * Gets the current attempt_id for this user on this item
     * @return	integer	The attempt_id for this item view by this user, or 1 if none defined
     */
    function get_attempt_id()
    {
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_attempt_id() on item '.$this->db_id,0);}
    	$res = 1;
    	if(!empty($this->attempt_id))
    	{
    		$res = $this->attempt_id;
    	}
    	if($this->debug>0){error_log('New LP - End of learnpathItem::get_attempt_id() on item '.$this->db_id.' - Returning '.$res,0);}
    	return $res;
    }
    /**
     * Gets a list of the item's children
     * @return	array	Array of children items IDs
     */
    function get_children()
    {
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_children()',0);}
    	$list = array();
    	foreach($this->children as $child){
    		if(!empty($child))
    		{
    			//error_log('New LP - Found '.$child,0);
    			$list[] = $child;
    		}
    	}
    	return $list;
    }
    /**
     * Gets the core_exit value from the database
     */
    function get_core_exit()
    {
    	return $this->core_exit;
    }
    /**
     * Gets the credit information (rather scorm-stuff) based on current status and reinit
     * autorization. Credit tells the sco(content) if Dokeos will record the data it is sent (credit) or not (no-credit)
     * @return	string	'credit' or 'no-credit'. Defaults to 'credit' because if we don't know enough about this item, it's probably because it was never used before.
     */
    function get_credit(){
    	if(!empty($this->debug) && $this->debug>1){error_log('New LP - In learnpathItem::get_credit()',0);}
		$credit = 'credit';
    	//now check the value of prevent_reinit (if it's 0, return credit as the default was)
		if($this->get_prevent_reinit() != 0){ //if prevent_reinit == 1 (or more)
			//if status is not attempted or incomplete, credit anyway. Otherwise:
    		//check the status in the database rather than in the object, as checking in the object
			//would always return "no-credit" when we want to set it to completed
			$status = $this->get_status(true);
			if(!empty($this->debug) && $this->debug>2){error_log('New LP - In learnpathItem::get_credit() - get_prevent_reinit!=0 and status is '.$status,0);}			
			if($status != $this->possible_status[0] AND $status != $this->possible_status[1]){
				$credit = 'no-credit';
			}
		}
		return $credit;
    }
    /**
     * Gets the current start time property
     * @return	integer	Current start time, or current time if none
     */
    function get_current_start_time()
    {
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_current_start_time()',0);}
    	if(empty($this->current_start_time))
    	{
    		return time();
    	}else{
    		return $this->current_start_time;
    	}
    }
    /**
     * Gets the item's description
     * @return	string	Description
     */
    function get_description(){
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_description()',0);}
    	if(empty($this->description)){return '';}
    	return $this->description;
    }
    /**
     * Gets the file path from the course's root directory, no matter what tool it is from.
     * @return	string	The file path, or an empty string if there is no file attached, or '-1' if the file must be replaced by an error page
     */
    function get_file_path($path_to_scorm_dir=''){
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_file_path()',0);}
    	$path = $this->get_path();
   		$type = $this->get_type();
    	if(empty($path))
    	{
    		if($type == 'dokeos_chapter' OR $type=='chapter' OR $type == 'dir')
    		{
    			return '';
    		}
    		else
    		{
    			return '-1';
    		}
    	}elseif($path == strval(intval($path))){
    		//the path is numeric, so it is a reference to a Dokeos object
    		switch($type)
    		{
    			case 'dokeos_chapter':
    			case 'dir':
    			case 'chapter':
    				return '';
    			case TOOL_DOCUMENT:
    				$table_doc = Database::get_course_table(TABLE_DOCUMENT);
    				$sql = 'SELECT path FROM '.$table_doc.' WHERE id = '.$path;
    				$res = api_sql_query($sql,__FILE__,__LINE__);
    				$row = Database::fetch_array($res);
    				$real_path = 'document'.$row['path'];
    				return $real_path;
    			case TOOL_STUDENTPUBLICATION:
    			case TOOL_QUIZ:
    			case TOOL_FORUM:
    			case TOOL_THREAD:
    			case TOOL_LINK:
    			default:
    				return '-1';
    		}
    	}else{
    		if(!empty($path_to_scorm_dir))
    		{
    			$path = $path_to_scorm_dir.$path;
    		}
    		return $path;
    	}
    }
    /**
     * Gets the DB ID
     * @return	integer	Database ID for the current item
     */
    function get_id(){
    	if($this->debug>1){error_log('New LP - In learnpathItem::get_id()',0);}
    	if(!empty($this->db_id))
    	{
    		return $this->db_id;
    	}
    	//TODO check this return value is valid for children classes (SCORM?)
    	return 0;
    }
    /**
     * Loads the interactions into the item object, from the database.
     * If object interactions exist, they will be overwritten by this function,
     * using the database elements only.
     * @return void Directly sets the interactions attribute in memory
     */
    function load_interactions() {
            $this->interactions = array();
            $tbl = Database::get_course_table('lp_item_view');
            $sql = "SELECT id FROM $tbl " .
                    "WHERE lp_item_id = ".$this->db_id." " .
                    "AND   lp_view_id = ".$this->view_id." " .
                    "AND   view_count = ".$this->attempt_id;
            $res = api_sql_query($sql,__FILE__,__LINE__);
            if (Database::num_rows($res)>0) {
                $row = Database::fetch_array($res);
                $lp_iv_id = $row[0];
                $iva_table = Database::get_course_table('lp_iv_interaction');
                $iva_sql = "SELECT * FROM $iva_table " .
                            "WHERE lp_iv_id = $lp_iv_id ";
                $res_sql = api_sql_query($iva_sql);
                while ($row = Database::fetch_array($res_sql)) {
                    $this->interactions[$row['interaction_id']] = array($row['interaction_id'],$row['interaction_type'],$row['weighting'],$row['completion_time'],$row['correct_responses'],$row['student_responses'],$row['result'],$row['latency']);
                }
            }
    }
    /**
     * Gets the current count of interactions recorded in the database
     * @param   bool    Whether to count from database or not (defaults to no)
     * @return	int	The current number of interactions recorder
     */
    function get_interactions_count($checkdb=false)
    {
    	if($this->debug>1){error_log('New LP - In learnpathItem::get_interactions_count()',0);}
    	$return = 0;
        if ($checkdb) {
            $tbl = Database::get_course_table('lp_item_view');
            $sql = "SELECT id FROM $tbl " .
                    "WHERE lp_item_id = ".$this->db_id." " .
                    "AND   lp_view_id = ".$this->view_id." " .
                    "AND   view_count = ".$this->attempt_id;
            $res = api_sql_query($sql,__FILE__,__LINE__);
            if (Database::num_rows($res)>0) {
                $row = Database::fetch_array($res);
                $lp_iv_id = $row[0];
                $iva_table = Database::get_course_table('lp_iv_interaction');
                $iva_sql = "SELECT count(id) as mycount FROM $iva_table " .
                            "WHERE lp_iv_id = $lp_iv_id ";
                $res_sql = api_sql_query($iva_sql);
                if (Database::num_rows($res_sql)>0) {
                	$row = Database::fetch_array($res_sql);
                    $return = $row['mycount'];
                }
            }
        } else {
        	if(!empty($this->interactions_count)){
        		$return = $this->interactions_count;
        	}
        }
    	return $return;
    }
    /**
     * Gets the JavaScript array content to fill the interactions array.
     * @params  bool    Whether to check directly into the database (default no)
     * @return  string  An empty string if no interaction, a JS array definition otherwise
     */
    function get_interactions_js_array($checkdb=false) {
        $return = '';
        if ($checkdb) {
            $this->load_interactions(true);
        }
        foreach ($this->interactions as $id=>$in) {
    		$return .= "['$id','".$in[1]."','".$in[2]."','".$in[3]."','".$in[4]."','".$in[5]."','".$in[6]."','".$in[7]."'],";
    	}
        if (!empty($return)) {
        	$return = substr($return,0,-1);
        }
        return $return;
    }
    /**
     * Gets the current count of objectives recorded in the database
     * @return	int	The current number of objectives recorder
     */
    function get_objectives_count()
    {
    	if($this->debug>1){error_log('New LP - In learnpathItem::get_objectives_count()',0);}
    	$res = 0;
    	if(!empty($this->objectives_count)){
    		$res = $this->objectives_count;
    	}
    	return $res;
    }
    /**
     * Gets the launch_data field found in imsmanifests (this is SCORM- or AICC-related, really)
     * @return	string	Launch data as found in imsmanifest and stored in Dokeos (read only). Defaults to ''.
     */
    function get_launch_data(){
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_launch_data()',0);}
    	if(!empty($this->launch_data)){
    		return $this->launch_data;
    	}
    	return '';
    }
    /**
     * Gets the lesson location
     * @return string	lesson location as recorded by the SCORM and AICC elements. Defaults to ''
     */
	function get_lesson_location(){
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_lesson_location()',0);}
    	if(!empty($this->lesson_location)){return $this->lesson_location;}else{return '';}
	}
	/**
	 * Gets the lesson_mode (scorm feature, but might be used by aicc as well as dokeos paths)
	 * 
	 * The "browse" mode is not supported yet (because there is no such way of seeing a sco in Dokeos)
	 * @return	string	'browse','normal' or 'review'. Defaults to 'normal'
	 */
	function get_lesson_mode(){
		$mode = 'normal';
		if($this->get_prevent_reinit() != 0){ //if prevent_reinit == 0
			$my_status = $this->get_status();
			if($my_status != $this->possible_status[0] AND $my_status != $this->possible_status[1]){	
				$mode = 'review';
			}
		}
		return $mode;
	}
    /**
     * Gets the depth level
     * @return int	Level. Defaults to 0
     */
    function get_level(){
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_level()',0);}
    	if(empty($this->level)){return 0;}
    	return $this->level;
    }
    /**
     * Gets the mastery score
     */
    function get_mastery_score()
    {
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_mastery_score()',0);}
    	if(isset($this->mastery_score)){return $this->mastery_score;}else{return -1;}    	    	
    }
    /**
     * Gets the maximum (score)
     * @return	int	Maximum score. Defaults to 100 if nothing else is defined
     */
    function get_max(){
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_max()',0);}
    	if($this->type == 'sco')
    	{
    		if(!empty($this->view_max_score) and $this->view_max_score>0)
    		{
    			return $this->view_max_score;
    		}
    		elseif($this->view_max_score === '')
    		{
    			return $this->view_max_score;
    		}
    		else
    		{
		    	if(!empty($this->max_score)){return $this->max_score;}else{return 100;}
    		}
    	}
    	else
    	{
    		if(!empty($this->max_score)){return $this->max_score;}else{return 100;}
    	}
    }
    /**
     * Gets the maximum time allowed for this user in this attempt on this item
     * @return	string	Time string in SCORM format (HH:MM:SS or HH:MM:SS.SS or HHHH:MM:SS.SS)
     */
    function get_max_time_allowed()
    {
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_max_time_allowed()',0);}
    	if(!empty($this->max_time_allowed)){return $this->max_time_allowed;}else{return '';}    	
    }
    /**
     * Gets the minimum (score)
     * @return int	Minimum score. Defaults to 0
     */
    function get_min(){
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_min()',0);}
    	if(!empty($this->min_score)){return $this->min_score;}else{return 0;}
    }
    /**
     * Gets the parent ID
     * @return	int	Parent ID. Defaults to null
     */
    function get_parent(){
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_parent()',0);}
    	if(!empty($this->parent))
    	{
    		return $this->parent;
    	}
    	//TODO check this return value is valid for children classes (SCORM?)
    	return null;
    }
    /**
     * Gets the path attribute.
     * @return	string	Path. Defaults to ''
     */
    function get_path(){
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_path()',0);}
    	if(empty($this->path)){return '';}
    	return $this->path;
    }
    /**
     * Gets the prerequisites string
     * @return	string	Empty string or prerequisites string if defined. Defaults to 
     */
    function get_prereq_string()
    {
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_prereq_string()',0);}
    	if(!empty($this->prereq_string))
    	{
    		return $this->prereq_string;
    	}else{
    		return '';
    	}
    }
    /**
     * Gets the prevent_reinit attribute value (and sets it if not set already)
     * @return	int	1 or 0 (defaults to 1)
     */
    function get_prevent_reinit(){
		if($this->debug>2){error_log('New LP - In learnpathItem::get_prevent_reinit()',0);}
    	if(!isset($this->prevent_reinit)){
	    	if(!empty($this->lp_id)){
	    		$db = Database::get_course_table('lp');
			   	$sql = "SELECT * FROM $db WHERE id = ".$this->lp_id;
		    	$res = @api_sql_query($sql);
		    	if(Database::num_rows($res)<1)
		    	{
		    		$this->error = "Could not find parent learnpath in learnpath table";
					if($this->debug>2){error_log('New LP - End of learnpathItem::get_prevent_reinit() - Returning false',0);}
		    		return false;
		    	}else{
		    		$row = Database::fetch_array($res);
		    		$this->prevent_reinit = $row['prevent_reinit']; 
		    	}
	    	}else{
	    		$this->prevent_reinit = 1;//prevent reinit is always 1 by default - see learnpath.class.php
	    	}
    	}
		if($this->debug>2){error_log('New LP - End of learnpathItem::get_prevent_reinit() - Returned '.$this->prevent_reinit,0);}
    	return $this->prevent_reinit;
    }
    /**
     * Gets the item's reference column
     * @return string	The item's reference field (generally used for SCORM identifiers)
     */
    function get_ref()
    {
    	return $this->ref;
    }
    /**
     * Gets the list of included resources as a list of absolute or relative paths of
     * resources included in the current item. This allows for a better SCORM export.
     * The list will generally include pictures, flash objects, java applets, or any other
     * stuff included in the source of the current item. The current item is expected
     * to be an HTML file. If it is not, then the function will return and empty list.
     * @param	string	type (one of the Dokeos tools) - optional (otherwise takes the current item's type)
     * @param	string	path (absolute file path) - optional (otherwise takes the current item's path)
     * @param	int		level of recursivity we're in
     * @return	array	List of file paths. An additional field containing 'local' or 'remote' helps determine if the file should be copied into the zip or just linked
     */
    function get_resources_from_source($type=null,$abs_path=null, $recursivity=1)
    {
    	$max = 5;
    	if($recursivity > $max)
    	{
    		return array();
    	}
    	if(!isset($type))
    	{
    		$type = $this->get_type();
    	}
    	if(!isset($abs_path))
    	{
    		$path = $this->get_file_path();
   			$abs_path = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/'.$path;
   			//echo "Abs path coming from item : ".$abs_path."<br/>\n";
    	}
    	/*
    	else
    	{
    		echo "Abs path coming from param: ".$abs_path."<br/>\n";
    	}
    	*/
    	//error_log(str_repeat(' ',$recursivity).'Analyse file '.$abs_path,0);
    	$files_list = array();
    	$type = $this->get_type();
    	switch($type)
    	{
    		case TOOL_DOCUMENT : 
    		case TOOL_QUIZ:
    		case 'sco':
    			//get the document and, if HTML, open it
    			
    			if(is_file($abs_path))
    			{
	    			//for now, read the whole file in one go (that's gonna be a problem when the file is too big)
					$info = pathinfo($abs_path);
					$ext = $info['extension'];
					switch(strtolower($ext))
					{
						case 'html':
						case 'htm':
						case 'shtml':
						case 'css':
					 		$wanted_attributes = array('src','url','@import','href','value');
			    			//parse it for included resources
							$file_content = file_get_contents($abs_path);
							//get an array of attributes from the HTML source
							$attributes = learnpathItem::parse_HTML_attributes($file_content,$wanted_attributes);
							//look at 'src' attributes in this file
							foreach($wanted_attributes as $attr)
							{
								if(isset($attributes[$attr]))
								{
									//find which kind of path these are (local or remote)
									$sources = $attributes[$attr];
									
									foreach($sources as $source)
									{
										//skip what is obviously not a resource
										if(strpos($source,"+this.")) continue; //javascript code - will still work unaltered
										if(strpos($source,'.')=== false) continue; //no dot, should not be an external file anyway
										if(strpos($source,'mailto:')) continue; //mailto link
										if(strpos($source,';') && !strpos($source,'&amp;')) continue; //avoid code - that should help
										
										if($attr == 'value')
										{
											if(strpos($source , 'mp3file'))
											{
												$files_list[] = array(substr($source, 0, strpos($source , '.swf')+4),'local','abs');
												$mp3file = substr($source , strpos($source , 'mp3file=')+8);
												if(substr($mp3file,0,1) == '/')
													$files_list[] = array($mp3file,'local','abs');
												else
													$files_list[] = array($mp3file,'local','rel');
											}
                                            elseif(strpos($source, 'flv=')===0)
                                            {
                                            	$source = substr($source, 4);
                                                if(strpos($source, '&')>0)
                                                {
                                                	$source = substr($source,0,strpos($source, '&'));
                                                }
                                            	if(strpos($source,'://')>0)
                                                {
                                                    if(strpos($source,api_get_path(WEB_PATH))!==false)
                                                    {
                                                        //we found the current portal url
                                                        $files_list[] = array($source,'local','url');
                                                    }
                                                    else
                                                    {
                                                        //we didn't find any trace of current portal
                                                        $files_list[] = array($source,'remote','url');
                                                    }
                                                }
                                                else
                                                {
                                                    $files_list[] = array($source,'local','abs');
                                                }
                                                continue; //skipping anything else to avoid two entries (while the others can have sub-files in their url, flv's can't)
                                            }
										}
										if(strpos($source,'://') > 0)
										{
											
											//cut at '?' in a URL with params
											if(strpos($source,'?')>0)
											{
												$second_part = substr($source,strpos($source,'?'));
												if(strpos($second_part,'://')>0)
												{//if the second part of the url contains a url too, treat the second one before cutting

													$pos1 = strpos($second_part,'=');
													$pos2 = strpos($second_part,'&');
													$second_part = substr($second_part,$pos1+1,$pos2-($pos1+1));
													if(strpos($second_part,api_get_path(WEB_PATH))!==false)
													{
														//we found the current portal url
														$files_list[] = array($second_part,'local','url');
														$in_files_list[] = learnpathItem::get_resources_from_source(TOOL_DOCUMENT,$second_part,$recursivity+1);
														if(count($in_files_list)>0)
														{
															$files_list = array_merge($files_list,$in_files_list);
														}
													}
													else
													{
														//we didn't find any trace of current portal
														$files_list[] = array($second_part,'remote','url');
													}
												}
												elseif(strpos($second_part,'=')>0)
												{
													if(substr($second_part,0,1) === '/')
													{	//link starts with a /, making it absolute (relative to DocumentRoot)
														$files_list[] = array($second_part,'local','abs');
														$in_files_list[] = learnpathItem::get_resources_from_source(TOOL_DOCUMENT,$second_part,$recursivity+1); 
														if(count($in_files_list)>0)
														{
															$files_list = array_merge($files_list,$in_files_list);
														} 
													}
													elseif(strstr($second_part,'..') === 0)
													{	//link is relative but going back in the hierarchy
														$files_list[] = array($second_part,'local','rel');
														$dir = dirname($abs_path);
														$new_abs_path = realpath($dir.'/'.$second_part);
														$in_files_list[] = learnpathItem::get_resources_from_source(TOOL_DOCUMENT,$new_abs_path,$recursivity+1); 
														if(count($in_files_list)>0)
														{
															$files_list = array_merge($files_list,$in_files_list);
														} 
													}
													else
													{	//no starting '/', making it relative to current document's path
														if(substr($second_part,0,2) == './')
														{
															$second_part = substr($second_part,2);
														}
														$files_list[] = array($second_part,'local','rel');
														$dir = dirname($abs_path);
														$new_abs_path = realpath($dir.'/'.$second_part);
														$in_files_list[] = learnpathItem::get_resources_from_source(TOOL_DOCUMENT,$new_abs_path,$recursivity+1); 
														if(count($in_files_list)>0)
														{
															$files_list = array_merge($files_list,$in_files_list);
														} 
													}
													
												}
												//leave that second part behind now
												$source = substr($source,0,strpos($source,'?'));
												if(strpos($source,'://') > 0)
												{
													if(strpos($source,api_get_path(WEB_PATH))!==false)
													{
														//we found the current portal url
														$files_list[] = array($source,'local','url');
														$in_files_list[] = learnpathItem::get_resources_from_source(TOOL_DOCUMENT,$source,$recursivity+1);
														if(count($in_files_list)>0)
														{
															$files_list = array_merge($files_list,$in_files_list);
														} 
													}
													else
													{
														//we didn't find any trace of current portal
														$files_list[] = array($source,'remote','url');
													}
												}
												else
												{
													//no protocol found, make link local
													if(substr($source,0,1) === '/')
													{	//link starts with a /, making it absolute (relative to DocumentRoot)
														$files_list[] = array($source,'local','abs');
														$in_files_list[] = learnpathItem::get_resources_from_source(TOOL_DOCUMENT,$source,$recursivity+1); 
														if(count($in_files_list)>0)
														{
															$files_list = array_merge($files_list,$in_files_list);
														} 
													}
													elseif(strstr($source,'..') === 0)
													{	//link is relative but going back in the hierarchy
														$files_list[] = array($source,'local','rel');
														$dir = dirname($abs_path);
														$new_abs_path = realpath($dir.'/'.$source);
														$in_files_list[] = learnpathItem::get_resources_from_source(TOOL_DOCUMENT,$new_abs_path,$recursivity+1); 
														if(count($in_files_list)>0)
														{
															$files_list = array_merge($files_list,$in_files_list);
														} 
													}
													else
													{	//no starting '/', making it relative to current document's path
														if(substr($source,0,2) == './')
														{
															$source = substr($source,2);
														}
														$files_list[] = array($source,'local','rel');
														$dir = dirname($abs_path);
														$new_abs_path = realpath($dir.'/'.$source);
														$in_files_list[] = learnpathItem::get_resources_from_source(TOOL_DOCUMENT,$new_abs_path,$recursivity+1); 
														if(count($in_files_list)>0)
														{
															$files_list = array_merge($files_list,$in_files_list);
														} 
													}
												}
											}
											//found some protocol there
											if(strpos($source,api_get_path(WEB_PATH))!==false)
											{
												//we found the current portal url
												$files_list[] = array($source,'local','url');
												$in_files_list[] = learnpathItem::get_resources_from_source(TOOL_DOCUMENT,$source,$recursivity+1);
												if(count($in_files_list)>0)
												{
													$files_list = array_merge($files_list,$in_files_list);
												} 
											}
											else
											{
												//we didn't find any trace of current portal
												$files_list[] = array($source,'remote','url');
											}
										}
										else
										{
											//no protocol found, make link local
											if(substr($source,0,1) === '/')
											{	//link starts with a /, making it absolute (relative to DocumentRoot)
												$files_list[] = array($source,'local','abs');
												$in_files_list[] = learnpathItem::get_resources_from_source(TOOL_DOCUMENT,$source,$recursivity+1); 
												if(count($in_files_list)>0)
												{
													$files_list = array_merge($files_list,$in_files_list);
												} 
											}
											elseif(strstr($source,'..') === 0)
											{	//link is relative but going back in the hierarchy
												$files_list[] = array($source,'local','rel');
												$dir = dirname($abs_path);
												$new_abs_path = realpath($dir.'/'.$source);
												$in_files_list[] = learnpathItem::get_resources_from_source(TOOL_DOCUMENT,$new_abs_path,$recursivity+1); 
												if(count($in_files_list)>0)
												{
													$files_list = array_merge($files_list,$in_files_list);
												} 
											}
											else
											{	//no starting '/', making it relative to current document's path
												if(substr($source,0,2) == './')
												{
													$source = substr($source,2);
												}
												$files_list[] = array($source,'local','rel');
												$dir = dirname($abs_path);
												$new_abs_path = realpath($dir.'/'.$source);
												$in_files_list[] = learnpathItem::get_resources_from_source(TOOL_DOCUMENT,$new_abs_path,$recursivity+1); 
												if(count($in_files_list)>0)
												{
													$files_list = array_merge($files_list,$in_files_list);
												} 
											}
										}
									}
								}
							}
							break;
						default:
							break;
					}
					
    			}
    			else
    			{
    				//the file could not be found
    				return false;
    			}
    			break;
    		default: //ignore
    			break;
    	}
    	//error_log(str_repeat(' ',$recursivity),'found files '.print_r($files_list,true),0);
		//return $files_list;
		$checked_files_list = array();
    	$checked_array_list = array();
    	foreach($files_list as $idx => $file)
    	{
    		if(!empty($file[0]))
    		{
	    		if(!in_array($file[0],$checked_files_list))
	    		{
	    			$checked_files_list[] = $files_list[$idx][0];
	    			$checked_array_list[] = $files_list[$idx];
	    		}
    		}
    	}
    	return $checked_array_list;
    }
    /**
     * Gets the score
     * @return	float	The current score or 0 if no score set yet
     */
    function get_score(){
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_score()',0);}
    	$res = 0;
    	if(!empty($this->current_score))
    	{
    		$res = $this->current_score;
    	}
    	if($this->debug>1){error_log('New LP - Out of learnpathItem::get_score() - returning '.$res,0);}
    	return $res;
    }
    /**
     * Gets the item status
     * @param	boolean	Do or don't check into the database for the latest value. Optional. Default is true
     * @param	boolean	Do or don't update the local attribute value with what's been found in DB
     * @return	string	Current status or 'Nnot attempted' if no status set yet
     */
    function get_status($check_db=true,$update_local=false) {
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_status() on item '.$this->db_id,0);}
    	if($check_db) {
    		if($this->debug>2){error_log('New LP - In learnpathItem::get_status(): checking db',0);}
    		$table = Database::get_course_table('lp_item_view');
    		$sql = "SELECT * FROM $table WHERE id = '".$this->db_item_view_id."' AND view_count = '".$this->get_attempt_id()."'";
    		if($this->debug>2){error_log('New LP - In learnpathItem::get_status() - Checking DB: '.$sql,0);}

    		$res = api_sql_query($sql);
    		if (Database::num_rows($res)==1) {
    			$row = Database::fetch_array($res);
    			if($update_local==true){
    				$this->set_status($row['status']);
    			}
	    		if($this->debug>2){error_log('New LP - In learnpathItem::get_status() - Returning db value '.$row['status'],0);}
    			return $row['status'];
    		}
    	} else {
    		if($this->debug>2){error_log('New LP - In learnpathItem::get_status() - in get_status: using attrib',0);}
	    	if(!empty($this->status)) {
	    		if($this->debug>2){error_log('New LP - In learnpathItem::get_status() - Returning attrib: '.$this->status,0);}
	    		return $this->status;
	    	}
    	}
   		if($this->debug>2){error_log('New LP - In learnpathItem::get_status() - Returning default '.$this->possible_status[0],0);}
    	return $this->possible_status[0];
    }
    /**
     * Gets the suspend data
     */
    function get_suspend_data()
    {
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_suspend_data()',0);}
    	//TODO : improve cleaning of breaklines ... it works but is it really a beautiful way to do it ?
    	if(!empty($this->current_data)){return str_replace(array("\r","\n"),array('\r','\n'),$this->current_data);}else{return '';}
    }
    /**
     * Gets the total time spent on this item view so far
     * @param	string	Origin of the request. If coming from PHP, send formatted as xxhxx'xx", otherwise use scorm format 00:00:00
     * @param	integer	Given time is a default time to return formatted
     */
    function get_scorm_time($origin='php',$given_time=null){
    	//if($this->debug>0){error_log('New LP - In learnpathItem::get_scorm_time()',0);}
    	$h = get_lang('h');
    	if(!isset($given_time)){
	    	if(!empty($this->current_start_time) && is_object($this)){
	    		if(!empty($this->current_stop_time)){
	    			$time = $this->current_stop_time - $this->current_start_time;
	    		}else{
	    			$time = time() - $this->current_start_time;
	    		}
	    	}else{
	    		if($origin == 'js'){
	    			return '00:00:00';
	    		}else{
	    			return '00'.$h.'00\'00"';
	    		}
	    	}
    	}else{
    		$time = $given_time;
    	}
		$hours = $time/3600;
		$mins  = ($time%3600)/60;
		$secs  = ($time%60);
  		if($origin == 'js'){
			$scorm_time = trim(sprintf("%4d:%02d:%02d",$hours,$mins,$secs));
  		}else{
			$scorm_time = trim(sprintf("%4d$h%02d'%02d\"",$hours,$mins,$secs));
  		}
		return $scorm_time;
    }
    
function get_terms()
          {
                $lp_item = Database::get_course_table(TABLE_LP_ITEM);
                $sql = "SELECT * FROM $lp_item WHERE id='".Database::escape_string($this->db_id)."'";
                $res = api_sql_query($sql,__FILE__,__LINE__);
                $row = Database::fetch_array($res);
                return $row['terms'];
          }
    /**
     * Returns the item's title
     * @return	string	Title
     */
    function get_title(){
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_title()',0);}
    	if(empty($this->title)){return '';}
    	return $this->title;
    }
    /**
     * Returns the total time used to see that item
     * @return	integer	Total time
     */
    function get_total_time(){
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_total_time()',0);}
    	if($this->current_start_time == 0){ //shouldn't be necessary thanks to the open() method
    		$this->current_start_time = time();
    	}
    	//$this->current_stop_time=time();
    	$time = $this->current_stop_time - $this->current_start_time;
    	if($time < 0){
    		return 0;
    	}else{
    	if($this->debug>2){error_log('New LP - In learnpathItem::get_total_time() - Current stop time = '.$this->current_stop_time.', current start time = '.$this->current_start_time.' Returning '.$time,0);}
    		return $time;
    	}
    }
    /**
     * Gets the item type
     * @return	string	The item type (can be doc, dir, sco, asset)
     */
    function get_type()
    {
    	$res = 'asset';
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_type() on item '.$this->db_id,0);}
    	if(!empty($this->type))
    	{
    		//error_log('In item::get_type() - returning '.$this->type,0);
    		$res = $this->type;
    	}
    	if($this->debug>2){error_log('New LP - In learnpathItem::get_type() - Returning '.$res.' for item '.$this->db_id,0);}
    	return $res;    	
    }
    /**
     * Gets the view count for this item
     */
	function get_view_count(){
    	if($this->debug>0){error_log('New LP - In learnpathItem::get_view_count()',0);}
		if(!empty($this->attempt_id)){
			return $this->attempt_id;
		}else{
			return 0;
		}
	}
	/**
	 * Tells if an item is done ('completed','passed','succeeded') or not
	 * @return	bool	True if the item is done ('completed','passed','succeeded'), false otherwise
	 */
	function is_done(){
   		if($this->status_is(array('completed','passed','succeeded'))){
   			if($this->debug>2){error_log('New LP - In learnpath::is_done() - Item '.$this->get_id().' is complete',0);}
   			return true;
   		}else{
   			if($this->debug>2){error_log('New LP - In learnpath::is_done() - Item '.$this->get_id().' is not complete',0);}
   			return false;
   		}
	}
	/**
	 * Tells if a restart is allowed (take it from $this->prevent_reinit and $this->status)
	 * @return	integer	-1 if retaking the sco another time for credit is not allowed,
	 * 					 0 if it is not allowed but the item has to be finished 
	 * 					 1 if it is allowed. Defaults to 1
	 */
	function is_restart_allowed()
	{
		if($this->debug>2){error_log('New LP - In learnpathItem::is_restart_allowed()',0);}
		$restart = 1;
		$mystatus = $this->get_status(true);
		if($this->get_prevent_reinit() > 0){ //if prevent_reinit == 1 (or more)
			//if status is not attempted or incomplete, authorize retaking (of the same) anyway. Otherwise:
			if($mystatus != $this->possible_status[0] AND $mystatus != $this->possible_status[1]){
				$restart = -1;
			}else{
				$restart = 0;
			}
		}else{
			if($mystatus == $this->possible_status[0] OR $mystatus == $this->possible_status[1]){
				$restart = -1;
			}
		}
		if($this->debug>2){error_log('New LP - End of learnpathItem::is_restart_allowed() - Returning '.$restart,0);}
		return $restart;
	}
    /**
     * Opens/launches the item. Initialises runtime values.
     * @return	boolean	True on success, false on failure.
     */
    function open($allow_new_attempt=false){
    	if($this->debug>0){error_log('New LP - In learnpathItem::open()',0);}
    	if($this->prevent_reinit == 0)
    	{
	    	$this->current_score = 0;
	    	$this->current_start_time = time();
	    	//In this case, as we are opening the item, what is important to us
	    	//is the database status, in order to know if this item has already
	    	//been used in the past (rather than just loaded and modified by
	    	//some javascript but not written in the database).
	    	//If the database status is different from 'not attempted', we can
	    	//consider this item has already been used, and as such we can
	    	//open a new attempt. Otherwise, we'll just reuse the current
	    	//attempt, which is generally created the first time the item is
	    	//loaded (for example as part of the table of contents)
	    	$stat = $this->get_status(true);
	    	if($allow_new_attempt && isset($stat) && ($stat != $this->possible_status[0]))
	    	{
	    		$this->attempt_id = $this->attempt_id + 1; //open a new attempt
	    	}
	    	$this->status = $this->possible_status[1];
    	}
    	else
    	{
    		if($this->current_start_time == 0)
    		{ //small exception for start time, to avoid amazing values
    			$this->current_start_time = time();
    		}
    		//error_log('New LP - reinit blocked by setting',0);
    	}
    }
    /**
     * Outputs the item contents
     * @return	string	HTML file (displayable in an <iframe>) or empty string if no path defined
     */
    function output()
    {
    	if($this->debug>0){error_log('New LP - In learnpathItem::output()',0);}
    	if(!empty($this->path) and is_file($this->path))
    	{
	    	$output = '';
	    	$output .= file_get_contents($this->path);
	    	return $output;
    	}
    	return '';
    }
    /**
     * Parses the prerequisites string with the AICC logic language
     * @param	string	The prerequisites string as it figures in imsmanifest.xml
     * @param	Array	Array of items in the current learnpath object. Although we're in the learnpathItem object, it's necessary to have a list of all items to be able to check the current item's prerequisites
	 * @param	Array	List of references (the "ref" column in the lp_item table) that are strings used in the expression of prerequisites.
	 * @param	integer	The user ID. In some cases like Dokeos quizzes, it's necessary to have the user ID to query other tables (like the results of quizzes)
     * @return	boolean	True if the list of prerequisites given is entirely satisfied, false otherwise
     */
    function parse_prereq($prereqs_string, $items, $refs_list,$user_id){
    	if($this->debug>0){error_log('New LP - In learnpathItem::parse_prereq() for learnpath '.$this->lp_id.' with string '.$prereqs_string,0);}
    	//deal with &, |, ~, =, <>, {}, ,, X*, () in reverse order
		$this->prereq_alert = '';
		// First parse all parenthesis by using a sequential loop (looking for less-inclusives first)
		if($prereqs_string == '_true_'){return true;}
		if($prereqs_string == '_false_'){    	
			if(empty($this->prereq_alert)){
    			$this->prereq_alert = get_lang('_prereq_not_complete');
    		}
			return false;
		}		
		while(strpos($prereqs_string,'(')!==false){
			//remove any () set and replace with its value
			$matches = array();
			$res = preg_match_all('/(\(([^\(\)]*)\))/',$prereqs_string,$matches);
			if($res){
				foreach($matches[2] as $id=>$match){
					$str_res = $this->parse_prereq($match,$items,$refs_list,$user_id);
					if($str_res){
						$prereqs_string = str_replace($matches[1][$id],'_true_',$prereqs_string);
					}else{
						$prereqs_string = str_replace($matches[1][$id],'_false_',$prereqs_string);
					}
				}
			}
		}
		
		//parenthesis removed, now look for ORs as it is the lesser-priority binary operator (= always uses one text operand)
		if(strpos($prereqs_string,"|")===false){
    		if($this->debug>1){error_log('New LP - Didnt find any OR, looking for AND',0);}
	    	if(strpos($prereqs_string,"&")!==false){
	    		$list = split("&",$prereqs_string);
	    		if(count($list)>1){
					$andstatus = true;
					foreach($list as $condition){
	    				$andstatus = $andstatus && $this->parse_prereq($condition,$items,$refs_list,$user_id);
	    				if($andstatus==false){
							if($this->debug>1){error_log('New LP - One condition in AND was false, short-circuit',0);}
							break;	    					
	    				}
					}
					if(empty($this->prereq_alert) && !$andstatus){
    					$this->prereq_alert = get_lang('_prereq_not_complete');
    				}
					return $andstatus;
	    		}else{
	    			if(isset($items[$refs_list[$list[0]]])){
	    				$status = $items[$refs_list[$list[0]]]->get_status(true);
		    			$returnstatus = (($status == $this->possible_status[2]) OR ($status == $this->possible_status[3]));
				    	if(empty($this->prereq_alert) && !$returnstatus){
				    		$this->prereq_alert = get_lang('_prereq_not_complete');
				    	}
		    			return $returnstatus;
	    			}
		    		$this->prereq_alert = get_lang('_prereq_not_complete');
					return false;
	    		}
	    	}else{
	    		
	    		//no ORs found, now look for ANDs
	    		
    			if($this->debug>1){error_log('New LP - Didnt find any AND, looking for =',0);}	    		
	    		if(strpos($prereqs_string,"=")!==false){
		    		if($this->debug>1){error_log('New LP - Found =, looking into it',0);}
		    		//we assume '=' signs only appear when there's nothing else around
		    		$params = split('=',$prereqs_string);
		    		if(count($params) == 2){
		    			//right number of operands
		    			if(isset($items[$refs_list[$params[0]]])){
		    				$status = $items[$refs_list[$params[0]]]->get_status(true);
			    			$returnstatus = ($status == $params[1]);
					    	if(empty($this->prereq_alert) && !$returnstatus){
					    		$this->prereq_alert = get_lang('_prereq_not_complete');
					    	}
			    			return $returnstatus;
		    			}
			    		$this->prereq_alert = get_lang('_prereq_not_complete');
						return false;
		    		}
	    		}else{
	    		
	    			//No ANDs found, look for <>
	    		
	    			if($this->debug>1){error_log('New LP - Didnt find any =, looking for <>',0);}	    		
		    		if(strpos($prereqs_string,"<>")!==false){
			    		if($this->debug>1){error_log('New LP - Found <>, looking into it',0);}
			    		//we assume '<>' signs only appear when there's nothing else around
			    		$params = split('<>',$prereqs_string);
			    		if(count($params) == 2){
			    			//right number of operands
			    			if(isset($items[$refs_list[$params[0]]])){
			    				$status = $items[$refs_list[$params[0]]]->get_status(true);
				    			$returnstatus =  ($status != $params[1]);
						    	if(empty($this->prereq_alert) && !$returnstatus){
						    		$this->prereq_alert = get_lang('_prereq_not_complete');
						    	}
				    			return $returnstatus;
			    			}
				    		$this->prereq_alert = get_lang('_prereq_not_complete');
			    			return false;
			    		}
		    		}else{
		    			
		    			//No <> found, look for ~ (unary)
		    			
		    			if($this->debug>1){error_log('New LP - Didnt find any =, looking for ~',0);}
		    			//only remains: ~ and X*{}
		    			if(strpos($prereqs_string,"~")!==false){
		    				//found NOT
				    		if($this->debug>1){error_log('New LP - Found ~, looking into it',0);}
		    				$list = array();
		    				$myres = preg_match('/~([^(\d+\*)\{]*)/',$prereqs_string,$list);
		    				if($myres){
		    					$returnstatus = !$this->parse_prereq($list[1],$items,$refs_list,$user_id);
						    	if(empty($this->prereq_alert) && !$returnstatus){
						    		$this->prereq_alert = get_lang('_prereq_not_complete');
						    	}
								return $returnstatus;
		    				}else{
		    					//strange...
					    		if($this->debug>1){error_log('New LP - Found ~ but strange string: '.$prereqs_string,0);}
		    				}
		    			}else{
		    				
		    				//Finally, look for sets/groups
		    				
			    			if($this->debug>1){error_log('New LP - Didnt find any ~, looking for groups',0);}	    		
		    				//only groups here
					    	$groups = array();
					    	$groups_there = preg_match_all('/((\d+\*)?\{([^\}]+)\}+)/',$prereqs_string,$groups);
					    	if($groups_there){
						    	foreach($groups[1] as $gr) //only take the results that correspond to the big brackets-enclosed condition
						    	{
						    		if($this->debug>1){error_log('New LP - Dealing with group '.$gr,0);}
						    		$multi = array();
									$mycond = false;
						    		if(preg_match('/(\d+)\*\{([^\}]+)\}/',$gr,$multi)){
						    			if($this->debug>1){error_log('New LP - Found multiplier '.$multi[0],0);}
						    			$count = $multi[1];
						    			$list = split(',',$multi[2]);
						    			$mytrue = 0;
						    			foreach($list as $cond){
							    			if(isset($items[$refs_list[$cond]])){
							    				$status = $items[$refs_list[$cond]]->get_status(true);
								    			if (($status == $this->possible_status[2]) OR ($status == $this->possible_status[3])){
						    						$mytrue ++;
						    						if($this->debug>1){error_log('New LP - Found true item, counting.. ('.($mytrue).')',0);}
								    			}
						    				}else{
							    				if($this->debug>1){error_log('New LP - item '.$cond.' does not exist in items list',0);}
							    			}
						    			}
										if($mytrue >= $count){
					    					if($this->debug>1){error_log('New LP - Got enough true results, return true',0);}
											$mycond = true;
										}else{
					    					if($this->debug>1){error_log('New LP - Not enough true results',0);}
										}
						    		}
						    		else{
						    			if($this->debug>1){error_log('New LP - No multiplier',0);}
						    			$list = split(',',$gr);
						    			$mycond = true;
						    			foreach($list as $cond){
							    			if(isset($items[$refs_list[$cond]])){
							    				$status = $items[$refs_list[$cond]]->get_status(true);
								    			if (($status == $this->possible_status[2]) OR ($status == $this->possible_status[3])){
						    						$mycond = true;
							    					if($this->debug>1){error_log('New LP - Found true item',0);}						    					
								    			}else{
							    					if($this->debug>1){error_log('New LP - Found false item, the set is not true, return false',0);}						    					
								    				$mycond = false;
								    				break;
								    			}
						    				}else{
							    				if($this->debug>1){error_log('New LP - item '.$cond.' does not exist in items list',0);}
						    					if($this->debug>1){error_log('New LP - Found false item, the set is not true, return false',0);}			    					
							    				$mycond = false;
							    				break;
							    			}
						    			}
						    		}
							    	if(!$mycond && empty($this->prereq_alert)){
							    		$this->prereq_alert = get_lang('_prereq_not_complete');
							    	}
						    		return $mycond;
						    	}
					    	}else{
					    		
					    		//Nothing found there either. Now return the value of the corresponding resource completion status
					    		
				    			if($this->debug>1){error_log('New LP - Didnt find any group, returning value for '.$prereqs_string,0);}
				    			if(isset($items[$refs_list[$prereqs_string]])){				    				
				    				if($items[$refs_list[$prereqs_string]]->type == 'quiz')
				    				{
				    					$sql = 'SELECT exe_result, exe_weighting
												FROM '.Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES).'
												WHERE exe_exo_id = '.$items[$refs_list[$prereqs_string]]->path.' 
												AND exe_user_id = '.$user_id.'
												AND status <> "incomplete"		
												ORDER BY exe_date DESC
												LIMIT 0, 1';
										$rs_quiz = api_sql_query($sql, __FILE__, __LINE__);
										if($quiz = Database :: fetch_array($rs_quiz))
										{
											if($quiz['exe_result'] >= $items[$refs_list[$prereqs_string]]->get_mastery_score())
											{
												$returnstatus = true;
											}
											else
											{
												$this->prereq_alert = get_lang('_prereq_not_complete');
												$returnstatus = false;
											}
										}
										else
										{
											$this->prereq_alert = get_lang('_prereq_not_complete');
											$returnstatus = false;
										}
										return $returnstatus;
				    				}
				    				else
				    				{
					    				$status = $items[$refs_list[$prereqs_string]]->get_status(true);
						    			$returnstatus = (($status == $this->possible_status[2]) OR ($status == $this->possible_status[3]));
								    	if(!$returnstatus && empty($this->prereq_alert)){
								    		$this->prereq_alert = get_lang('_prereq_not_complete');
								    	}
								    	if(!$returnstatus){
								    		if($this->debug>1){error_log('New LP - Prerequisite '.$prereqs_string.' not complete',0);}
								    	}else{
								    		if($this->debug>1){error_log('New LP - Prerequisite '.$prereqs_string.' complete',0);}
								    	}
						    			return $returnstatus;
				    				}
				    			}else{
				    				if($this->debug>1){error_log('New LP - Could not find '.$prereqs_string.' in '.print_r($refs_list,true),0);}
				    			}
					    	}
		    			}
		    		}
	    		}
	    	}
    	}else{
    		$list = split("\|",$prereqs_string);
    		if(count($list)>1){
	    		if($this->debug>1){error_log('New LP - Found OR, looking into it',0);}
				$orstatus = false;
				foreach($list as $condition){
					if($this->debug>1){error_log('New LP - Found OR, adding it ('.$condition.')',0);}
    				$orstatus = $orstatus || $this->parse_prereq($condition,$items,$refs_list,$user_id);
    				if($orstatus == true){
    					//shortcircuit OR
						if($this->debug>1){error_log('New LP - One condition in OR was true, short-circuit',0);}
    					break;
    				}
				}
		    	if(!$orstatus && empty($this->prereq_alert)){
		    		$this->prereq_alert = get_lang('_prereq_not_complete');
		    	}
				return $orstatus;
    		}else{
	    		if($this->debug>1){error_log('New LP - OR was found but only one elem present !?',0);}
    			if(isset($items[$refs_list[$list[0]]])){
    				$status = $items[$refs_list[$list[0]]]->get_status(true);
	    			$returnstatus = (($status == 'completed') OR ($status == 'passed'));
			    	if(!$returnstatus && empty($this->prereq_alert)){
			    		$this->prereq_alert = get_lang('_prereq_not_complete');
			    	}
	    			return $returnstatus;
    			}
    		}
    	}
    	if(empty($this->prereq_alert)){
    		$this->prereq_alert = get_lang('_prereq_not_complete');
    	}
    	if($this->debug>1){error_log('New LP - End of parse_prereq. Error code is now '.$this->prereq_alert,0);}
    	return false;
    }
    /**
	 * Parses the HTML attributes given as string.
	 * 
	 * @param    string  HTML attribute string
	 * @param	 array	 List of attributes that we want to get back
	 * @return   array   An associative array of attributes
	 * @author Based on a function from the HTML_Common2 PEAR module
	 */
	function parse_HTML_attributes($attrString,$wanted=array())
	{
	    $attributes = array();
	    $regs = array();
	    $reduced = false;
	    if(count($wanted)>0)
	    {
	    	$reduced = true;
	    }
	    try {
	       //Find all occurences of something that looks like a URL
           // The structure of this regexp is:
           // (find protocol) then 
           // (optionally find some kind of space 1 or more times) then
           // find (either an equal sign or a bracket) followed by an optional space
           // followed by some text without quotes (between quotes itself or not)
           // then possible closing brackets if we were in the opening bracket case
           // OR something like @import() 
	    	$res = preg_match_all(
                '/(((([A-Za-z_:])([A-Za-z0-9_:\.-]*))' .
//	            '/(((([A-Za-z_:])([A-Za-z0-9_:\.-]|[^\x00-\x7F])*)' . -> seems to be taking too much
//                '/(((([A-Za-z_:])([^\x00-\x7F])*)' . -> takes only last letter of parameter name 
	            '([ \n\t\r]+)?(' .
//	              '(=([ \n\t\r]+)?("[^"]+"|\'[^\']+\'|[^ \n\t\r]+))' . -> doesn't restrict close enough to the url itself
                  '(=([ \n\t\r]+)?("[^"\)]+"|\'[^\'\)]+\'|[^ \n\t\r\)]+))' .
	              '|' .
//	              '(\(([ \n\t\r]+)?("[^"]+"|\'[^\']+\'|[^ \n\t\r]+)\))' . -> doesn't restrict close enough to the url itself
                  '(\(([ \n\t\r]+)?("[^"\)]+"|\'[^\'\)]+\'|[^ \n\t\r\)]+)\))' .
	            '))' .
	            '|' .
//	            '(@import([ \n\t\r]+)?("[^"]+"|\'[^\']+\'|[^ \n\t\r]+)))?/', -> takes a lot (like 100's of thousands of empty possibilities) 
                '(@import([ \n\t\r]+)?("[^"]+"|\'[^\']+\'|[^ \n\t\r]+)))/', 
	            $attrString, 
	            $regs
	       );

		} catch (Exception $e) {
    		error_log('Caught exception: '. $e->getMessage(),0) ;
		}
	    if ($res) {
	        for ($i = 0; $i < count($regs[1]); $i++) {
	            $name  = trim($regs[3][$i]);
	            $check = trim($regs[0][$i]);
	            $value = trim($regs[10][$i]);
	            if(empty($value) and !empty($regs[13][$i])){
					$value = $regs[13][$i];
	            }
	            if(empty($name) && !empty($regs[16][$i]))
	            {
	            	$name = '@import';
	            	$value = trim($regs[16][$i]);
	            }
	            if(!empty($name))
	            {     
					if(!$reduced OR in_array(strtolower($name),$wanted))
		            {
			            if ($name == $check) {		                	
		            		$attributes[strtolower($name)][] = strtolower($name);				            	
			            } else {			            	
			                if (!empty($value) && ($value[0] == '\'' || $value[0] == '"')) {			                	
			                    $value = substr($value, 1, -1);			                    
			                }
						    if ($value=='API.LMSGetValue(name') {
						    	$value='API.LMSGetValue(name)';
						    }		                			                
			                $attributes[strtolower($name)][] = $value;
			            }
		            }
	            }
	        }
	    }else{
	    	error_log('preg_match did not find anything',0);
	    }
	    return $attributes;
	}
    /**
     * Reinits all local values as the learnpath is restarted
     * @return	boolean	True on success, false otherwise	
     */
    function restart()
    {
		if($this->debug>0){error_log('New LP - In learnpathItem::restart()',0);}
		$this->save();
		$allowed = $this->is_restart_allowed();
		if($allowed === -1){
			//nothing allowed, do nothing
		}elseif($allowed === 1){
			//restart as new attempt is allowed, record a new attempt
	    	$this->attempt_id = $this->attempt_id + 1; //simply reuse the previous attempt_id
			$this->current_score = 0;
			$this->current_start_time = 0;
			$this->current_stop_time = 0;
			$this->current_data = '';
			$this->status = $this->possible_status[0];
			$this->interactions_count = 0;
			$this->interactions = array();
			$this->objectives_count = 0;
			$this->objectives = array();
			$this->lesson_location = '';
			if ($this->type != TOOL_QUIZ) {
				$this->write_to_db();
			}
		}else{
			//restart current element is allowed (because it's not finished yet), 
			// reinit current
			$this->current_score = 0;
			$this->current_start_time = 0;
			$this->current_stop_time = 0;
			$this->current_data = '';
			$this->status = $this->possible_status[0];
            $this->interactions_count = $this->get_interactions_count(true);
		}
    	return true;
    }
    /**
     * Saves data in the database
     * @param	boolean	Save from URL params (1) or from object attributes (0)
     * @param	boolean	The results of a check on prerequisites for this item. True if prerequisites are completed, false otherwise. Defaults to false. Only used if not sco or au
     * @return	boolean	True on success, false on failure
     */
    function save($from_outside=true,$prereqs_complete=false)
    {
		if($this->debug>0){error_log('New LP - In learnpathItem::save()',0);}

		//$item_view_table = Database::get_course_table(COURSEID,LEARNPATH_ITEM_VIEW_TABLE);
     	$item_id = $this->get_id();
     	//first check if parameters passed via GET can be saved here
     	//in case it's a SCORM, we should get:
		if($this->type == 'sco' || $this->type== 'au'){
			$s = $this->get_status(true);
			if($this->prevent_reinit == 1 AND
				$s != $this->possible_status[0] AND $s != $this->possible_status[1]){
				if($this->debug>1){error_log('New LP - In learnpathItem::save() - save reinit blocked by setting',0);}
				//do nothing because the status has already been set. Don't allow it to change.
				//TODO check there isn't a special circumstance where this should be saved
			}else{
			  if($this->debug>1){error_log('New LP - In learnpathItem::save() - SCORM save request received',0);}
			  //get all new settings from the URL
				if($from_outside==true){
				  if($this->debug>1){error_log('New LP - In learnpathItem::save() - Getting item data from outside',0);}
		     	  foreach($_GET as $param => $value)
		     	  {
		     		$value = Database::escape_string($value);
		     		switch($param){
		     			case 'score':
		   					$this->set_score($value);
		   					if($this->debug>2){error_log('New LP - In learnpathItem::save() - setting score to '.$value,0);}
		     				break;
		     			case 'max':
		     				$this->set_max_score($value);
		   					if($this->debug>2){error_log('New LP - In learnpathItem::save() - setting view_max_score to '.$value,0);}
		     				break;
		     			case 'min':
		     				$this->min_score = $value;
		   					if($this->debug>2){error_log('New LP - In learnpathItem::save() - setting min_score to '.$value,0);}
		     				break;
		     			case 'lesson_status':
		     				if(!empty($value)){
			     				$this->set_status($value); 
			   					if($this->debug>2){error_log('New LP - In learnpathItem::save() - setting status to '.$value,0);}
		     				}
		     				break;
		     			case 'time':
		     				$this->set_time($value);
		   					if($this->debug>2){error_log('New LP - In learnpathItem::save() - setting time to '.$value,0);}
		     				break;
		     			case 'suspend_data':
		     				$this->current_data = $value;
		     				if($this->debug>2){error_log('New LP - In learnpathItem::save() - setting suspend_data to '.$value,0);}
		     				break;
		     			case 'lesson_location':
		     				$this->set_lesson_location($value);
		     				if($this->debug>2){error_log('New LP - In learnpathItem::save() - setting lesson_location to '.$value,0);}
		     				break;
		     			case 'core_exit':
		     				$this->set_core_exit($value);
		     				if($this->debug>2){error_log('New LP - In learnpathItem::save() - setting core_exit to '.$value,0);}
		     				break;
		     			case 'interactions':
		     				//$interactions = unserialize($value);
		     				//foreach($interactions as $interaction){
		     				//	;
		     				//}
		     				break;
		     			case 'objectives':
		     				break;
		     			//case 'maxtimeallowed':
		     			//	$this->set_max_time_allowed($value);
		     			//	break;
		     			/*
		     			case 'objectives._count':
		     				$this->attempt_id = $value;
		     				break;
		     			*/
		     			default:
		     				//ignore
		     				break;
		     		}
		     	  }
				}else{
					if($this->debug>1){error_log('New LP - In learnpathItem::save() - Using inside item status',0);}
					//do nothing, just let the local attributes be used
				}
			}
		}else{ //if not SCO, such messages should not be expected
			$type = strtolower($this->type);
			switch($type){
				case 'asset':
		 			if($prereqs_complete)
		 			{
			 			$this->set_status($this->possible_status[2]);
		 			}
		 			break;
		 		case TOOL_HOTPOTATOES:
		 		case TOOL_QUIZ: return false;break;
				default:
		 			//for now, everything that is not sco and not asset is set to
		 			//completed when saved
		 			if($prereqs_complete)
		 			{
		 				$this->set_status($this->possible_status[2]);
		 			}	 			
				break;
	 		}
		}
		//$time = $this->time
		if($this->debug>1){error_log('New LP - End of learnpathItem::save() - Calling write_to_db()',0);}
		return $this->write_to_db();
    }
    /**
     * Sets the number of attempt_id to a given value
     * @param	integer	The given value to set attempt_id to
     * @return	boolean	TRUE on success, FALSE otherwise
     */
    function set_attempt_id($num)
    {
		if($this->debug>0){error_log('New LP - In learnpathItem::set_attempt_id()',0);}
     	if($num == strval(intval($num)) && $num>=0){
     		$this->attempt_id = $num;
     		return true;
     	}
     	return false;
    }
    /**
     * Sets the core_exit value to the one given
     */
    function set_core_exit($value)
    {
    	switch($value){
    		case '':
    			$this->core_exit = '';
    			break;
    		case 'suspend':
    			$this->core_exit = 'suspend';
    			break;
    		default:
    			$this->core_exit = 'none';
    			break;
    	}
    	return true;
    }
    /**
     * Sets the item's description
     * @param	string	Description
     */
    function set_description($string=''){
		if($this->debug>0){error_log('New LP - In learnpathItem::set_description()',0);}
    	if(!empty($string)){$this->description = $string;}
    }
    /**
     * Sets the lesson_location value
     * @param	string	lesson_location as provided by the SCO
     * @return	boolean	True on success, false otherwise
     */
    function set_lesson_location($location)
    {
   		if($this->debug>0){error_log('New LP - In learnpathItem::set_lesson_location()',0);}
		if(isset($location)){
   			$this->lesson_location = Database::escape_string($location);
  			return true;
  		}
     	return false;
    }
    /**
     * Sets the item's depth level in the LP tree (0 is at root)
     * @param	integer	Level
     */
    function set_level($int=0){
		if($this->debug>0){error_log('New LP - In learnpathItem::set_level('.$int.')',0);}
    	if(!empty($int) AND $int == strval(intval($int))){$this->level = $int;}
    }
    /**
     * Sets the lp_view id this item view is registered to
     * @param	integer	lp_view DB ID
     * @todo //todo insert into lp_item_view if lp_view not exists
     */
    function set_lp_view($lp_view_id)
    {
		if($this->debug>0){error_log('New LP - In learnpathItem::set_lp_view('.$lp_view_id.')',0);}
    	if(!empty($lp_view_id) and $lp_view_id = intval(strval($lp_view_id)))
     	{
     		$this->view_id = $lp_view_id;
	     	$item_view_table = Database::get_course_table('lp_item_view');
	     	//get the lp_item_view with the highest view_count
	     	$sql = "SELECT * FROM $item_view_table WHERE lp_item_id = ".$this->get_id()." " .
	     			" AND lp_view_id = ".$lp_view_id." ORDER BY view_count DESC";
	     	if($this->debug>2){error_log('New LP - In learnpathItem::set_lp_view() - Querying lp_item_view: '.$sql,0);}
	     	$res = api_sql_query($sql,__FILE__,__LINE__);
	     	if(Database::num_rows($res)>0){
	     		$row = Database::fetch_array($res);
	     		$this->db_item_view_id  = $row['id'];
	     		$this->attempt_id 		= $row['view_count'];
				$this->current_score	= $row['score'];
				$this->current_data		= $row['suspend_data'];
		    	$this->view_max_score 	= $row['max_score'];
		    	//$this->view_min_score 	= $row['min_score'];
				$this->status			= $row['status'];
				$this->current_start_time	= $row['start_time'];
				$this->current_stop_time 	= $this->current_start_time + $row['total_time']; 
				$this->lesson_location  = $row['lesson_location'];
				$this->core_exit		= $row['core_exit'];
		     	if($this->debug>2){error_log('New LP - In learnpathItem::set_lp_view() - Updated item object with database values',0);}

		     	//now get the number of interactions for this little guy
		     	$item_view_interaction_table = Database::get_course_table('lp_iv_interaction');
		     	$sql = "SELECT * FROM $item_view_interaction_table WHERE lp_iv_id = '".$this->db_item_view_id."'"; 
				$res = api_sql_query($sql,__FILE__,__LINE__);
				if($res !== false){
					$this->interactions_count = Database::num_rows($res);
				}else{
					$this->interactions_count = 0;
				}
		     	//now get the number of objectives for this little guy
		     	$item_view_objective_table = Database::get_course_table('lp_iv_objective');
		     	$sql = "SELECT * FROM $item_view_objective_table WHERE lp_iv_id = '".$this->db_item_view_id."'"; 
				$res = api_sql_query($sql,__FILE__,__LINE__);
				if($res !== false){
					$this->objectives_count = Database::num_rows($res);
				}else{
					$this->objectives_count = 0;
				}
	     	}
     	}
		//end
		if($this->debug>2){error_log('New LP - End of learnpathItem::set_lp_view()',0);}
    }
    /**
     * Sets the path
     * @param	string	Path
     */
    function set_path($string=''){
		if($this->debug>0){error_log('New LP - In learnpathItem::set_path()',0);}
    	if(!empty($string)){$this->path = $string;}
    }
    /**
     * Sets the prevent_reinit attribute. This is based on the LP value and is set at creation time for
     * each learnpathItem. It is a (bad?) way of avoiding a reference to the LP when saving an item.
     * @param	integer	1 for "prevent", 0 for "don't prevent" saving freshened values (new "not attempted" status etc)
     */
    function set_prevent_reinit($prevent){
		if($this->debug>0){error_log('New LP - In learnpathItem::set_prevent_reinit()',0);}
    	if($prevent){
    		$this->prevent_reinit = 1;
    	}else{
    		$this->prevent_reinit = 0;
    	}
    }
    /**
     * Sets the score value. If the mastery_score is set and the score reaches
     * it, then set the status to 'passed'.
     * @param	float	Score
     * @return	boolean	True on success, false otherwise
     */
    function set_score($score)
    {
   		if($this->debug>0){error_log('New LP - In learnpathItem::set_score('.$score.')',0);}
   		if(($this->max_score<=0 || $score <= $this->max_score) && ($score >= $this->min_score))
   		{
   			$this->current_score = $score;
   			$master = $this->get_mastery_score();
   			$current_status = $this->get_status(false);
   			//if mastery_score is set AND the current score reaches the mastery score AND the current status is different from 'completed', then set it to 'passed'
   			if($master != -1 && $this->current_score >= $master && $current_status != $this->possible_status[2])
   			{
   				$this->set_status($this->possible_status[3]);
   			}
   			elseif($master != -1 && $this->current_score<$master)
   			{
   				$this->set_status($this->possible_status[4]);
   			}
  			return true;
  		}
     	return false;
    }
    /**
     * Sets the maximum score for this item
     * @param	int		Maximum score - must be a decimal or an empty string
     * @return	boolean	True on success, false on error
     */
    function set_max_score($score)
    {
   		if($this->debug>0){error_log('New LP - In learnpathItem::set_max_score('.$score.')',0);}
     	if(is_int($score) or $score == '')
     	{
     		$this->view_max_score = Database::escape_string($score);
     		if($this->debug>1){error_log('New LP - In learnpathItem::set_max_score() - Updated object score of item '.$this->db_id.' to '.$this->view_max_score,0);}
     		return true;
     	}
     	return false;
    }
    /**
     * Sets the status for this item
     * @param	string	Status - must be one of the values defined in $this->possible_status
     * @return	boolean	True on success, false on error
     */
    function set_status($status)
    {
   		if($this->debug>0){error_log('New LP - In learnpathItem::set_status('.$status.')',0);}
     	$found = false;
     	foreach($this->possible_status  as $possible){
     		if(preg_match('/^'.$possible.'$/i',$status)){
     			$found = true;
     		}
     	}
     	//if(in_array($status,$this->possible_status))
     	if($found)
     	{
     		$this->status = Database::escape_string($status);
     		if($this->debug>1){error_log('New LP - In learnpathItem::set_status() - Updated object status of item '.$this->db_id.' to '.$this->status,0);}
     		return true;
     	}
     	//error_log('New LP - '.$status.' was not in the possible status',0);
     	$this->status = $this->possible_status[0];
     	return false;
    }
    /**
     * Set the terms for this learnpath item
     * @param   string  Terms, as a comma-split list
     * @return  boolean Always return true
     */
    function set_terms($terms) {
		global $charset;
        $lp_item = Database::get_course_table(TABLE_LP_ITEM);
        require_once(api_get_path(LIBRARY_PATH).'search/DokeosIndexer.class.php');
        $a_terms = split(',',$terms);
        $i_terms = split(',',$this->get_terms());
        foreach ( $i_terms as $term ) {
            if ( !in_array($term,$a_terms) ) { array_push($a_terms,$term); }
        }
        $new_terms = $a_terms;
        $new_terms_string = implode(',',$new_terms);
        $terms_update_sql='';
        //TODO: validate csv string
        $terms_update_sql = "UPDATE $lp_item SET terms = '". Database::escape_string(api_htmlentities($new_terms_string, ENT_QUOTES, $charset)) . "' WHERE id=".$this->get_id();
        $res = api_sql_query($terms_update_sql,__FILE__,__LINE__);
        // save it to search engine
        if (api_get_setting('search_enabled') == 'true') {
            $di = new DokeosIndexer();
            $di->update_terms($this->get_search_did(), $new_terms);
        }
        return true;
    }
    /**
     * Get the document ID from inside the text index database
     * @return  int     Search index database document ID
     */
    function get_search_did()
    {
        return $this->search_did;
    }
           
    /**
     * Sets the item viewing time in a usable form, given that SCORM packages often give it as 00:00:00.0000
     * @param	string	Time as given by SCORM
     */
    function set_time($scorm_time,$format='scorm')
    {
   		if($this->debug>0){error_log('New LP - In learnpathItem::set_time('.$scorm_time.')',0);}
     	if($scorm_time == 0 and ($this->type!='sco') and $this->current_start_time!=0){
     		$my_time = time() - $this->current_start_time;
     		if($my_time > 0){
     			$this->update_time($my_time);
     			if($this->debug>0){error_log('New LP - In learnpathItem::set_time('.$scorm_time.') - found asset - set time to '.$my_time,0);}
     		}
     	}else{
     		if($format == 'scorm'){
		     	$res = array();
		     	if(preg_match('/^(\d{1,4}):(\d{2}):(\d{2})(\.\d{1,4})?/',$scorm_time,$res)){
		     		$time = time();
					$hour = $res[1];
					$min = $res[2];
					$sec = $res[3];
					//getting total number of seconds spent
		     		$total_sec = $hour*3600 + $min*60 + $sec;
		     		$this->update_time($total_sec);
		     	}
     		}elseif($format == 'int'){
     			$this->update_time($scorm_time);
     		}
     	}
    }
    /**
     * Sets the item's title
     * @param	string	Title
     */
    function set_title($string=''){
   		if($this->debug>0){error_log('New LP - In learnpathItem::set_title()',0);}
    	if(!empty($string)){$this->title = $string;}
    }
    /**
     * Sets the item's type
     * @param	string	Type
     */
    function set_type($string=''){
   		if($this->debug>0){error_log('New LP - In learnpathItem::set_type()',0);}
    	if(!empty($string)){$this->type = $string;}
    }
	/**
	 * Checks if the current status is part of the list of status given
	 * @param	strings_array	An array of status to check for. If the current status is one of the strings, return true
	 * @return	boolean			True if the status was one of the given strings, false otherwise	
	 */
	function status_is($list=array())
	{
   		if($this->debug>1){error_log('New LP - In learnpathItem::status_is('.print_r($list,true).') on item '.$this->db_id,0);}
		$mystatus = $this->get_status(true);
		if(empty($mystatus)){
			return false;
		}
		$found = false;
		foreach($list as $status)
		{
			if(preg_match('/^'.$status.'$/i',$mystatus))
			{
				if($this->debug>2){error_log('New LP - learnpathItem::status_is() - Found status '.$status.' corresponding to current status',0);}
				$found = true;
				return $found;
			}
		}
		if($this->debug>2){error_log('New LP - learnpathItem::status_is() - Status '.$mystatus.' did not match request',0);}
		return $found;
	}
    /**
     * Updates the time info according to the given session_time
     * @param	integer	Time in seconds
     * //TODO @TODO make this method better by allowing better/multiple time slices
     */
    function update_time($total_sec=0){
   		if($this->debug>0){error_log('New LP - In learnpathItem::update_time('.$total_sec.')',0);}
    	if($total_sec>=0){
	 		//getting start time from finish time. The only problem in the calculation is it might be
	 		//modified by the scripts processing time
	 		$now = time();
	 		$start = $now-$total_sec;
			$this->current_start_time = $start;
			$this->current_stop_time  = $now;
	 		/*if(empty($this->current_start_time)){
	 			$this->current_start_time = $start;
	 			$this->current_stop_time  = $now;
	 		}else{
	 			//if($this->current_stop_time != $this->current_start_time){
		 			//if the stop time has already been set before to something else
		 			//than the start time, add the given time to what's already been
		 			//recorder.
		 			//This is the SCORM way of doing things, because the time comes from
		 			//core.session_time, not core.total_time
     			// UPDATE: adding time to previous time is only done on SCORM's finish()
     			// call, not normally, so for now ignore this section
		 		//	$this->current_stop_time = $this->current_stop_time + $stop;
		 		//	error_log('New LP - Adding '.$stop.' seconds - now '.$this->current_stop_time,0);
		 		//}else{
		 			//if no previous stop time set, use the one just calculated now from
		 			//start time
		 			//$this->current_start_time = $start;
		 			//$this->current_stop_time  = $now;
		 			//error_log('New LP - Setting '.$stop.' seconds - now '.$this->current_stop_time,0);
		 		//}
	 		}*/
    	}
    }
    /**
     * Write objectives to DB. This method is separate from write_to_db() because otherwise
     * objectives are lost as a side effect to AJAX and session concurrent access
     * @return	boolean		True or false on error
     */
    function write_objectives_to_db()
    {
   		if($this->debug>0){error_log('New LP - In learnpathItem::write_objectives_to_db()',0);}
     	if(is_array($this->objectives) && count($this->objectives)>0){
     		//save objectives
     		$tbl = Database::get_course_table('lp_item_view');
     		$sql = "SELECT id FROM $tbl " .
     				"WHERE lp_item_id = ".$this->db_id." " .
     				"AND   lp_view_id = ".$this->view_id." " .
     				"AND   view_count = ".$this->attempt_id;
     		$res = api_sql_query($sql,__FILE__,__LINE__);
     		if(Database::num_rows($res)>0){
     			$row = Database::fetch_array($res);
     			$lp_iv_id = $row[0];
     			if($this->debug>2){error_log('New LP - In learnpathItem::write_to_db() - Got item_view_id '.$lp_iv_id.', now checking objectives ',0);}
	     		foreach($this->objectives as $index => $objective){
	     			$iva_table = Database::get_course_table('lp_iv_objective');
	     			$iva_sql = "SELECT id FROM $iva_table " .
	     					"WHERE lp_iv_id = $lp_iv_id " .
	     					//"AND order_id = $index";
							//also check for the objective ID as it must be unique for this SCO view
	     					"AND objective_id = '".Database::escape_string($objective[0])."'";
	     			$iva_res = api_sql_query($iva_sql,__FILE__,__LINE__);
					//id(0), type(1), time(2), weighting(3),correct_responses(4),student_response(5),result(6),latency(7)
	     			if(Database::num_rows($iva_res)>0){
	     				//update (or don't)
	     				$iva_row = Database::fetch_array($iva_res);
	     				$iva_id = $iva_row[0];
	     				$ivau_sql = "UPDATE $iva_table " .
	     					"SET objective_id = '".Database::escape_string($objective[0])."'," .
	     					"status = '".Database::escape_string($objective[1])."'," .
	     					"score_raw = '".Database::escape_string($objective[2])."'," .
	     					"score_min = '".Database::escape_string($objective[4])."'," .
	     					"score_max = '".Database::escape_string($objective[3])."' " .
	     					"WHERE id = $iva_id";
	     				$ivau_res = api_sql_query($ivau_sql,__FILE__,__LINE__);
	     				//error_log($ivau_sql,0);
	     			}else{
	     				//insert new one
	     				$ivai_sql = "INSERT INTO $iva_table " .
	     						"(lp_iv_id, order_id, objective_id, status, score_raw, score_min, score_max )" .
	     						"VALUES" .
	     						"(".$lp_iv_id.", ".$index.",'".Database::escape_string($objective[0])."','".Database::escape_string($objective[1])."'," .
	     						"'".Database::escape_string($objective[2])."','".Database::escape_string($objective[4])."','".Database::escape_string($objective[3])."')";
	     				$ivai_res = api_sql_query($ivai_sql,__FILE__,__LINE__);
	     				//error_log($ivai_sql);
	     			}
	     		}
     		}
     	}
     	else
     	{
     		//error_log('no objective to save: '.print_r($this->objectives,1));
     	}
    }
    /**
     * Writes the current data to the database
     * @return	boolean	Query result
     */
     function write_to_db()
     {
	
   		if($this->debug>0){error_log('New LP - In learnpathItem::write_to_db()',0);}
   		$mode = $this->get_lesson_mode();
   		$credit = $this->get_credit();
   		$my_verified_status=$this->get_status(false);
   		
   		$item_view_table = Database::get_course_table('lp_item_view');
		$sql_verified='SELECT status FROM '.$item_view_table.' WHERE lp_item_id="'.$this->db_id.'" AND lp_view_id="'.$this->view_id.'" AND view_count="'.$this->attempt_id.'" ;';
		$rs_verified=api_sql_query($sql_verified,__FILE__,__LINE__);
		$row_verified=Database::fetch_array($rs_verified);
					
   		$my_case_completed=array('completed','passed','browsed','failed');//added by isaac flores
   		if (in_array($sql_verified['status'],$my_case_completed)) {
   			$save=false;
   		} else {
   			$save=true;
   		}

   		if (($save===false && $this->type == 'sco') ||(($this->type == 'sco') && ($credit == 'no-credit' OR $mode == 'review' OR $mode == 'browse')))
   		{
   			//this info shouldn't be saved as the credit or lesson mode info prevent it
   			if($this->debug>1){error_log('New LP - In learnpathItem::write_to_db() - credit('.$credit.') or lesson_mode('.$mode.') prevent recording!',0);}
   		} else {
      		//check the row exists
      		$inserted = false;
      		
      		// this a special case for multiple attempts and Dokeos exercises
      		if ($this->type == 'quiz' && $this->get_prevent_reinit()==0 && $this->get_status()=='completed') {     			
      			// we force the item to be restarted    		
      			$this->restart();
      			      		
      			$sql = "INSERT INTO $item_view_table " .
		     			"(total_time, " .
		     			"start_time, " .
		     			"score, " .
		     			"status, " .
		     			"max_score, ".
		     			"lp_item_id, " .
		     			"lp_view_id, " .
		     			"view_count, " .
		     			"suspend_data, " .
		     			//"max_time_allowed," .
		     			"lesson_location)" .
		     			"VALUES" .
		     			"(".$this->get_total_time()."," .
		     			"".$this->current_start_time."," .
		     			"".$this->get_score()."," .
		     			"'".$this->get_status(false)."'," .
		     			"'".$this->get_max()."'," .
		     			"".$this->db_id."," .
		     			"".$this->view_id."," .
		     			"".$this->get_attempt_id()."," .
		     			"'".Database::escape_string($this->current_data)."'," .
		     			//"'".$this->get_max_time_allowed()."'," .
		     			"'".$this->lesson_location."')";
		     			
      			if($this->debug>2){error_log('New LP - In learnpathItem::write_to_db() - Inserting into item_view forced: '.$sql,0);}
		     	$res = api_sql_query($sql,__FILE__,__LINE__);
		     	$this->db_item_view_id = Database::get_last_insert_id();	     	
		     	$inserted = true;	
		   }     		
      		
	     	$item_view_table = Database::get_course_table('lp_item_view');
	     	$check = "SELECT * FROM $item_view_table " .
	     			"WHERE lp_item_id = ".$this->db_id. " " .
	     			"AND   lp_view_id = ".$this->view_id. " ".
	     			"AND   view_count = ".$this->get_attempt_id();
	     	if($this->debug>2){error_log('New LP - In learnpathItem::write_to_db() - Querying item_view: '.$check,0);}
	     	$check_res = api_sql_query($check);
	     	//depending on what we want (really), we'll update or insert a new row
	     	//now save into DB
	     	$res = 0;
	     	if( $inserted==false && Database::num_rows($check_res)<1){
                /*$my_status = '';
	     		if ($this->type!=TOOL_QUIZ) {
	     				$my_status = $this->get_status(false);
	     		}*/
		     	$sql = "INSERT INTO $item_view_table " .
		     			"(total_time, " .
		     			"start_time, " .
		     			"score, " .
		     			"status, " .
		     			"max_score, ".
		     			"lp_item_id, " .
		     			"lp_view_id, " .
		     			"view_count, " .
		     			"suspend_data, " .
		     			//"max_time_allowed," .
		     			"lesson_location)" .
		     			"VALUES" .
		     			"(".$this->get_total_time()."," .
		     			"".$this->current_start_time."," .
		     			"".$this->get_score()."," .
		     			"'".$this->get_status(false)."'," .
		     			"'".$this->get_max()."'," .
		     			"".$this->db_id."," .
		     			"".$this->view_id."," .
		     			"".$this->get_attempt_id()."," .
		     			"'".Database::escape_string($this->current_data)."'," .
		     			//"'".$this->get_max_time_allowed()."'," .
		     			"'".$this->lesson_location."')";
		     	if($this->debug>2){error_log('New LP - In learnpathItem::write_to_db() - Inserting into item_view: '.$sql,0);}
		     	$res = api_sql_query($sql,__FILE__,__LINE__);
		     	$this->db_item_view_id = Database::get_last_insert_id();
	     	} else {
	     		$sql = '';
	     		if($this->type=='hotpotatoes') {
	     			//make an exception for HotPotatoes, don't update the score
		     		//because it has been saved outside of this tool
			     	$sql = "UPDATE $item_view_table " .
			     			"SET total_time = ".$this->get_total_time().", " .
			     			" start_time = ".$this->get_current_start_time().", " .
			     			" score = ".$this->get_score().", " .
			     			" status = '".$this->get_status(false)."'," .
			     			" max_score = '".$this->get_max()."'," .
			     			" suspend_data = '".Database::escape_string($this->current_data)."'," .
			     			" lesson_location = '".$this->lesson_location."' " .
			     			"WHERE lp_item_id = ".$this->db_id." " .
			     			"AND lp_view_id = ".$this->view_id." " .
			     			"AND view_count = ".$this->attempt_id;
	     		} else {
	     			//for all other content types...	
	     			if ($this->type=='quiz') {
	     				$my_status = ' ';	
	     				$total_time = ' ';	     				 
	     				if (!empty($_REQUEST['exeId'])) {
							$TBL_TRACK_EXERCICES	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
							
							$safe_exe_id = Database::escape_string($_REQUEST['exeId']);
		     				$sql = 'SELECT start_date,exe_date FROM ' . $TBL_TRACK_EXERCICES . ' WHERE exe_id = '.(int)$safe_exe_id;
							$res = api_sql_query($sql,__FILE__,__LINE__);
							$row_dates = Database::fetch_array($res);		
	
							$time_start_date = convert_mysql_date($row_dates['start_date']);
							$time_exe_date 	 = convert_mysql_date($row_dates['exe_date']);
							$mytime = ((int)$time_exe_date-(int)$time_start_date);
							$total_time =" total_time = ".$mytime.", ";
	     				}																							     				    					     			
	     			} else {
	     				$my_type_lp=learnpath::get_type_static($this->lp_id);
	     				// this is a array containing values finished
	     				$case_completed=array('completed','passed','browsed');

	     				//is not multiple attempts
	     				if ($this->get_prevent_reinit()==1) {				
			  		     	// process of status verified into data base
		     				
		     				$sql_verified='SELECT status FROM '.$item_view_table.' WHERE lp_item_id="'.$this->db_id.'" AND lp_view_id="'.$this->view_id.'" AND view_count="'.$this->attempt_id.'" ;';
		     				$rs_verified=api_sql_query($sql_verified,__FILE__,__LINE__);
		     				$row_verified=Database::fetch_array($rs_verified);
		     				
		     				//get type lp: 1=lp dokeos and  2=scorm 	
			    			// if not is completed or passed or browsed and learning path is scorm			     			
		     				if(!in_array($this->get_status(false),$case_completed) && $my_type_lp==2 ) {//&& $this->type!='dir'
		     					$total_time =" total_time = total_time +".$this->get_total_time().", "; 
		     					$my_status = " status = '".$this->get_status(false)."' ,"; 
		     				} else {
		     					//verified into data base
		     					if (!in_array($row_verified['status'],$case_completed) && $my_type_lp==2 ) { //&& $this->type!='dir'
		     						$total_time =" total_time = total_time +".$this->get_total_time().", ";
		     						$my_status = " status = '".$this->get_status(false)."' ,"; 
		     	 				} elseif (in_array($row_verified['status'],$case_completed) && $my_type_lp==2 && $this->type!='sco' ) {//&& $this->type!='dir'
		     	 					$total_time =" total_time = total_time +".$this->get_total_time().", ";
		     	 					$my_status = " status = '".$this->get_status(false)."' ,"; 
		     					}else {
		     	 				//&& !in_array($row_verified['status'],$case_completed)			     	 				
		     	 				//is lp dokeos
		     	 					if ($my_type_lp==1 && $this->type!='chapter') {
	     								$total_time =" total_time = total_time + ".$this->get_total_time().", ";
	     								$my_status = " status = '".$this->get_status(false)."' ,"; 
	     							}	
		     	 				}		     					
		     				}
	     				} else {
	     					// is multiple attempts
	     					if (in_array($this->get_status(false),$case_completed) &&  $my_type_lp==2) {
	     						//reset zero new attempt ?	     						
	     						$my_status = " status = '".$this->get_status(false)."' ,";
	     					}  elseif (!in_array($this->get_status(false),$case_completed) &&  $my_type_lp==2){
	     						$total_time =" total_time = ".$this->get_total_time().", ";
	     						$my_status = " status = '".$this->get_status(false)."' ,";	     						
	     					} else {
	     						//is dokeos LP
	     						$total_time =" total_time = total_time +".$this->get_total_time().", ";
	     						$my_status = " status = '".$this->get_status(false)."' ,"; 
	     					}	
	     					
	     					//code added by isaac flores
	     					//this code line fix the problem of wrong status
	     					if ( $my_type_lp==2) {
				     			//verify current status in multiples attempts
				     			$sql_status='SELECT status FROM '.$item_view_table.' WHERE lp_item_id="'.$this->db_id.'" AND lp_view_id="'.$this->view_id.'" AND view_count="'.$this->attempt_id.'" ';
						     	$rs_status=Database::query($sql_status,__FILE__,__LINE__);
						     	$current_status=Database::result($rs_status,0,'status');
						     	if (in_array($current_status,$case_completed)) {
						     		$my_status='';
						     		$total_time='';
						     	} else {
						     		$total_time =" total_time = total_time +".$this->get_total_time().", ";
						     	}
	     					}     					
	     				}	     						
	     				/*if ($my_type_lp==1 && !in_array($row_verified['status'],$case_completed)) {
	     					$total_time =" total_time = total_time + ".$this->get_total_time().", ";
	     				}*/
	     				
	     			}
			     	$sql = "UPDATE $item_view_table " .
			     			"SET " .$total_time.
			     			" start_time = ".$this->get_current_start_time().", " .
			     			" score = ".$this->get_score().", " .
			     			$my_status.
			     			" max_score = '".$this->get_max()."'," .
			     			" suspend_data = '".Database::escape_string($this->current_data)."'," .
			     			//" max_time_allowed = '".$this->get_max_time_allowed()."'," .
			     			" lesson_location = '".$this->lesson_location."' " .
			     			"WHERE lp_item_id = ".$this->db_id." " .
			     			"AND lp_view_id = ".$this->view_id." " .
			     			"AND view_count = ".$this->attempt_id;
			     			
			     			$this->current_start_time = time();
	     		}
	    	 	if($this->debug>2){error_log('New LP - In learnpathItem::write_to_db() - Updating item_view: '.$sql,0);}
		     	$res = api_sql_query($sql,__FILE__,__LINE__);
	     	}
	     	//if(!$res)
	     	//{
	     	//	$this->error = 'Could not update item_view table...'.mysql_error();
	     	//}
	     	if(is_array($this->interactions) && count($this->interactions)>0){
	     		//save interactions
	     		$tbl = Database::get_course_table('lp_item_view');
	     		$sql = "SELECT id FROM $tbl " .
	     				"WHERE lp_item_id = ".$this->db_id." " .
	     				"AND   lp_view_id = ".$this->view_id." " .
	     				"AND   view_count = ".$this->attempt_id;
	     		$res = api_sql_query($sql,__FILE__,__LINE__);
	     		if(Database::num_rows($res)>0){
	     			$row = Database::fetch_array($res);
	     			$lp_iv_id = $row[0];
	     			if($this->debug>2){error_log('New LP - In learnpathItem::write_to_db() - Got item_view_id '.$lp_iv_id.', now checking interactions ',0);}
		     		foreach($this->interactions as $index => $interaction){
		     			$correct_resp = '';
		     			if(is_array($interaction[4]) && !empty($interaction[4][0]) ){
		     				foreach($interaction[4] as $resp){
		     					$correct_resp .= $resp.',';
		     				}
		     				$correct_resp = substr($correct_resp,0,strlen($correct_resp)-1);
		     			}
		     			$iva_table = Database::get_course_table('lp_iv_interaction');
		     			$iva_sql = "SELECT id FROM $iva_table " .
		     					"WHERE lp_iv_id = $lp_iv_id " .
//		     					"AND order_id = $index";
								//also check for the interaction ID as it must be unique for this SCO view
		     					"AND (order_id = $index " .
		     					"OR interaction_id = '".Database::escape_string($interaction[0])."')";
		     			$iva_res = api_sql_query($iva_sql,__FILE__,__LINE__);
						//id(0), type(1), time(2), weighting(3),correct_responses(4),student_response(5),result(6),latency(7)
		     			if(Database::num_rows($iva_res)>0){
		     				//update (or don't)
		     				$iva_row = Database::fetch_array($iva_res);
		     				$iva_id = $iva_row[0];
		     				$ivau_sql = "UPDATE $iva_table " .
		     					"SET interaction_id = '".Database::escape_string($interaction[0])."'," .
		     					"interaction_type = '".Database::escape_string($interaction[1])."'," .
		     					"weighting = '".Database::escape_string($interaction[3])."'," .
		     					"completion_time = '".Database::escape_string($interaction[2])."'," .
		     					"correct_responses = '".Database::escape_string($correct_resp)."'," .
		     					"student_response = '".Database::escape_string($interaction[5])."'," .
		     					"result = '".Database::escape_string($interaction[6])."'," .
		     					"latency = '".Database::escape_string($interaction[7])."'" .
		     					"WHERE id = $iva_id";
		     				$ivau_res = api_sql_query($ivau_sql,__FILE__,__LINE__);
		     			} else {
		     				//insert new one
		     				$ivai_sql = "INSERT INTO $iva_table " .
		     						"(order_id, lp_iv_id, interaction_id, interaction_type, " .
		     						"weighting, completion_time, correct_responses, " .
		     						"student_response, result, latency)" .
		     						"VALUES" .
		     						"(".$index.",".$lp_iv_id.",'".Database::escape_string($interaction[0])."','".Database::escape_string($interaction[1])."'," .
		     						"'".Database::escape_string($interaction[3])."','".Database::escape_string($interaction[2])."','".Database::escape_string($correct_resp)."'," .
		     						"'".Database::escape_string($interaction[5])."','".Database::escape_string($interaction[6])."','".Database::escape_string($interaction[7])."'" .
		     						")";
		     				$ivai_res = api_sql_query($ivai_sql,__FILE__,__LINE__);
		     			}
		     		}
	     		}
	     	}
   		}
		if($this->debug>2){error_log('New LP - End of learnpathItem::write_to_db()',0);}
     	return true;
     }
}
?>
