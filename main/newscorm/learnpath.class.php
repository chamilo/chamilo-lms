<?php // $Id: index.php 16620 2008-10-25 20:03:54Z yannoo $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue Notre Dame, 152, B-1140 Evere, Belgium, info@dokeos.com
==============================================================================
*/

/**
 * This (abstract?) class defines the parent attributes and methods for the dokeos learnpaths and scorm
 * learnpaths. It is used by the scorm class as well as the dokeos_lp class.
 * @package dokeos.learnpath
 * @author	Yannick Warnier <ywarnier@beeznest.org>
 * @license	GNU/GPL - See Dokeos license directory for details
 */
/**
 * Defines the learnpath parent class
 * @package dokeos.learnpath
 */
class learnpath {

	var $attempt = 0; //the number for the current ID view
	var $cc; //course (code) this learnpath is located in
	var $current; //id of the current item the user is viewing
	var $current_score; //the score of the current item
	var $current_time_start; //the time the user loaded this resource (this does not mean he can see it yet)
	var $current_time_stop; //the time the user closed this resource
	var $default_status = 'not attempted';
	var $encoding = 'ISO-8859-1';
	var $error = '';
	var $extra_information = ''; //this string can be used by proprietary SCORM contents to store data about the current learnpath
	var $force_commit = false; //for SCORM only - if set to true, will send a scorm LMSCommit() request on each LMSSetValue()
	var $index; //the index of the active learnpath_item in $ordered_items array
	var $items = array();
	var $last; //item_id of last item viewed in the learning path
	var $last_item_seen = 0; //in case we have already come in this learnpath, reuse the last item seen if authorized
	var $license; //which license this course has been given - not used yet on 20060522
	var $lp_id; //DB ID for this learnpath
	var $lp_view_id; //DB ID for lp_view
	var $log_file; //file where to log learnpath API msg
	var $maker; //which maker has conceived the content (ENI, Articulate, ...)
	var $message = '';
	var $mode='embedded'; //holds the video display mode (fullscreen or embedded) 
	var $name; //learnpath name (they generally have one)
	var $ordered_items = array(); //list of the learnpath items in the order they are to be read
	var $path = ''; //path inside the scorm directory (if scorm)
	var $theme; // the current theme of the learning path
	var $preview_image; // the current image of the learning path  
	
	// Tells if all the items of the learnpath can be tried again. Defaults to "no" (=1)
	var $prevent_reinit = 1;
	
	// Describes the mode of progress bar display
	var $progress_bar_mode = '%'; 
	
	// Percentage progress as saved in the db
	var $progress_db = '0'; 
	var $proximity; //wether the content is distant or local or unknown
	var $refs_list = array(); //list of items by ref => db_id. Used only for prerequisites match. 
	//!!!This array (refs_list) is built differently depending on the nature of the LP. 
	//If SCORM, uses ref, if Dokeos, uses id to keep a unique value
	var $type; //type of learnpath. Could be 'dokeos', 'scorm', 'scorm2004', 'aicc', ... 
	//TODO check if this type variable is useful here (instead of just in the controller script)
	var $user_id; //ID of the user that is viewing/using the course
	var $update_queue = array();
	var $scorm_debug = 0;
	
	var $arrMenu = array(); //array for the menu items

	var $debug = 1; //logging level



	/**
	 * Class constructor. Needs a database handler, a course code and a learnpath id from the database.
	 * Also builds the list of items into $this->items.
	 * @param	string		Course code
	 * @param	integer		Learnpath ID
	 * @param	integer		User ID
	 * @return	boolean		True on success, false on error
	 */
    function learnpath($course, $lp_id, $user_id) {
    	//check params
    	//check course code
    	if($this->debug>0){error_log('New LP - In learnpath::learnpath('.$course.','.$lp_id.','.$user_id.')',0);}
    	if(empty($course)){
    		$this->error = 'Course code is empty';
    		return false;
    	}
    	else
    	{
    		$main_table = Database::get_main_table(TABLE_MAIN_COURSE);
    		//$course = Database::escape_string($course);
    		$course = $this->escape_string($course);
    		$sql = "SELECT * FROM $main_table WHERE code = '$course'";
    		if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - Querying course: '.$sql,0);}
    		//$res = Database::query($sql);
    		$res = api_sql_query($sql, __FILE__, __LINE__);
    		if(Database::num_rows($res)>0)
    		{
    			$this->cc = $course;
    		}
    		else
    		{
    			$this->error = 'Course code does not exist in database ('.$sql.')';
    			return false;
	   		}
    	}
    	//check learnpath ID
    	if(empty($lp_id))
    	{
    		$this->error = 'Learnpath ID is empty';
    		return false;
    	}
    	else
    	{    		
    		//TODO make it flexible to use any course_code (still using env course code here)
	    	$lp_table = Database::get_course_table('lp');

    		//$id = Database::escape_integer($id);
    		$lp_id = $this->escape_string($lp_id);
    		$sql = "SELECT * FROM $lp_table WHERE id = '$lp_id'";
    		if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - Querying lp: '.$sql,0);}
    		//$res = Database::query($sql);
    		$res = api_sql_query($sql, __FILE__, __LINE__);
    		if(Database::num_rows($res)>0)
    		{
    			$this->lp_id = $lp_id;
    			$row = Database::fetch_array($res);
    			$this->type = $row['lp_type'];
    			$this->name = stripslashes($row['name']);
    			$this->encoding = $row['default_encoding'];
    			$this->proximity = $row['content_local'];
    			$this->theme = $row['theme'];
    			$this->maker = $row['content_maker'];
    			$this->prevent_reinit = $row['prevent_reinit'];
    			$this->license = $row['content_license'];
    			$this->scorm_debug = $row['debug'];
	   			$this->js_lib = $row['js_lib'];
	   			$this->path = $row['path'];
	   			$this->preview_image= $row['preview_image'];
	   			$this->author= $row['author'];
	   			
	   			if($this->type == 2){
    				if($row['force_commit'] == 1){
    					$this->force_commit = true;
    				}
    			}
    			$this->mode = $row['default_view_mod'];
    		}
    		else
    		{
    			$this->error = 'Learnpath ID does not exist in database ('.$sql.')';
    			return false;
    		}
    	}
    	//check user ID
    	if(empty($user_id)){
    		$this->error = 'User ID is empty';
    		return false;
    	}
    	else
    	{
    		//$main_table = Database::get_main_user_table();
    		$main_table = Database::get_main_table(TABLE_MAIN_USER);
    		//$user_id = Database::escape_integer($user_id);
    		$user_id = $this->escape_string($user_id);
    		$sql = "SELECT * FROM $main_table WHERE user_id = '$user_id'";
    		if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - Querying user: '.$sql,0);}
    		//$res = Database::query($sql);
    		$res = api_sql_query($sql, __FILE__, __LINE__);
    		if(Database::num_rows($res)>0)
    		{
    			$this->user_id = $user_id;
    		}
    		else
    		{
    			$this->error = 'User ID does not exist in database ('.$sql.')';
    			return false;
    		}
    	}
    	//end of variables checking
    	
    	//now get the latest attempt from this user on this LP, if available, otherwise create a new one
		$lp_table = Database::get_course_table(TABLE_LP_VIEW);
		//selecting by view_count descending allows to get the highest view_count first
		$sql = "SELECT * FROM $lp_table WHERE lp_id = '$lp_id' AND user_id = '$user_id' ORDER BY view_count DESC";
		if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - querying lp_view: '.$sql,0);}
		//$res = Database::query($sql);
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$view_id = 0; //used later to query lp_item_view
		if(Database::num_rows($res)>0)
		{
			if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - Found previous view',0);}
			$row = Database::fetch_array($res);
			$this->attempt = $row['view_count'];
			$this->lp_view_id = $row['id'];
			$this->last_item_seen = $row['last_item'];
			$this->progress_db = $row['progress'];
		}
		else
		{
			if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - NOT Found previous view',0);}
			$this->attempt = 1;
			$sql_ins = "INSERT INTO $lp_table (lp_id,user_id,view_count) VALUES ($lp_id,$user_id,1)";
			$res_ins = api_sql_query($sql_ins, __FILE__, __LINE__);
			$this->lp_view_id = Database::get_last_insert_id();
			if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - inserting new lp_view: '.$sql_ins,0);}
		}

    	//initialise items
		$lp_item_table = Database::get_course_table(TABLE_LP_ITEM);
    	$sql = "SELECT * FROM $lp_item_table WHERE lp_id = '".$this->lp_id."' ORDER BY parent_item_id, display_order";
    	$res = api_sql_query($sql, __FILE__, __LINE__);
    	
    	while($row = Database::fetch_array($res))
    	{
			$oItem = '';
   			//$this->ordered_items[] = $row['id'];
   			switch($this->type){
				
   				case 3: //aicc
   					$oItem = new aiccItem('db',$row['id']);
   					if(is_object($oItem)){
   						$my_item_id = $oItem->get_id();
   						$oItem->set_lp_view($this->lp_view_id);
		   				$oItem->set_prevent_reinit($this->prevent_reinit);
		   				// Don't use reference here as the next loop will make the pointed object change
		   				$this->items[$my_item_id] = $oItem;   						
		   				$this->refs_list[$oItem->ref]=$my_item_id;
		   				if($this->debug>2){error_log('New LP - learnpath::learnpath() - aicc object with id '.$my_item_id.' set in items[]',0);}
   					}
					break;
   				case 2:

   					require_once('scorm.class.php');
   					require_once('scormItem.class.php');
   					$oItem = new scormItem('db',$row['id']);   				
		   			if(is_object($oItem)){
		   				$my_item_id = $oItem->get_id();
		   				$oItem->set_lp_view($this->lp_view_id);
		   				$oItem->set_prevent_reinit($this->prevent_reinit);
		   				// Don't use reference here as the next loop will make the pointed object change
		   				$this->items[$my_item_id] = $oItem;
		   				$this->refs_list[$oItem->ref]=$my_item_id;
		   				if($this->debug>2){error_log('New LP - object with id '.$my_item_id.' set in items[]',0);}
		   			}
   					break;

   				case 1:

   				default:
   					require_once('learnpathItem.class.php');
   					$oItem = new learnpathItem($row['id'],$user_id);
		   			if(is_object($oItem)){
		   				$my_item_id = $oItem->get_id();
		   				//$oItem->set_lp_view($this->lp_view_id); moved down to when we are sure the item_view exists
		   				$oItem->set_prevent_reinit($this->prevent_reinit);
		   				// Don't use reference here as the next loop will make the pointed object change
		   				$this->items[$my_item_id] = $oItem;
		   				$this->refs_list[$my_item_id]=$my_item_id;
		   				if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - object with id '.$my_item_id.' set in items[]',0);}
		   			}
   					break;
   			}

    		//items is a list of pointers to all items, classified by DB ID, not SCO id
    		if($row['parent_item_id'] == 0 OR empty($this->items[$row['parent_item_id']])){
   				$this->items[$row['id']]->set_level(0);
   			}else{
   				$level = $this->items[$row['parent_item_id']]->get_level()+1;
   				$this->items[$row['id']]->set_level($level);
   				if(is_object($this->items[$row['parent_item_id']])){
   					//items is a list of pointers from item DB ids to item objects
   					$this->items[$row['parent_item_id']]->add_child($row['id']);
   				}else{
   					if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - The parent item ('.$row['parent_item_id'].') of item '.$row['id'].' could not be found',0);}
   				}
   			}

	    	//get last viewing vars
	    	$lp_item_view_table = Database::get_course_table(TABLE_LP_ITEM_VIEW);
	    	//this query should only return one or zero result
	    	$sql = "SELECT * " .
	    			"FROM $lp_item_view_table " .
	    			"WHERE lp_view_id = ".$this->lp_view_id." " .
	    			"AND lp_item_id = ".$row['id']." ORDER BY view_count DESC ";
	    	if($this->debug>2){error_log('New LP - learnpath::learnpath() - Selecting item_views: '.$sql,0);}
	    	//get the item status
	    	$res2 = api_sql_query($sql, __FILE__, __LINE__);
	    	if(Database::num_rows($res2)>0)
	    	{
	    		//if this learnpath has already been used by this user, get his last attempt count and
	    		//the last item seen back into this object
	    		//$max = 0;
	    		$row2 = Database::fetch_array($res2);
	    		if($this->debug>2){error_log('New LP - learnpath::learnpath() - Got item_view: '.print_r($row2,true),0);}
	    		$this->items[$row['id']]->set_status($row2['status']); 
	    		if(empty($row2['status'])){
		    		$this->items[$row['id']]->set_status($this->default_status); 
	    		}
	    		//$this->attempt = $row['view_count'];
	    		//$this->last_item = $row['id'];	    		
	    	}
			else //no item found in lp_item_view for this view
			{
				//first attempt from this user. Set attempt to 1 and last_item to 0 (first item available)
	    		//TODO  if the learnpath has not got attempts activated, always use attempt '1'
				//$this->attempt = 1;
				//$this->last_item = 0;
	    		$this->items[$row['id']]->set_status($this->default_status);
	    		//Add that row to the lp_item_view table so that we have something to show in the stats page
	    		$sql_ins = "INSERT INTO $lp_item_view_table " .
	    				"(lp_item_id, lp_view_id, view_count, status) VALUES " .
	    				"(".$row['id'].",".$this->lp_view_id.",1,'not attempted')";
	    		if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - Inserting blank item_view : '.$sql_ins,0);}
	    		$res_ins = api_sql_query($sql_ins, __FILE__, __LINE__);
			}
			//setting the view in the item object
			$this->items[$row['id']]->set_lp_view($this->lp_view_id);
    	}
    	$this->ordered_items = $this->get_flat_ordered_items_list($this->get_id(),0);
    	$this->max_ordered_items = 0;
    	foreach($this->ordered_items as $index=>$dummy){
    		if($index > $this->max_ordered_items AND !empty($dummy)){
    			$this->max_ordered_items = $index;
    		}
    	}
    	//TODO define the current item better
    	$this->first();
    	if($this->debug>2){error_log('New LP - learnpath::learnpath() '.__LINE__.' - End of learnpath constructor for learnpath '.$this->get_id(),0);}
    }
    
    /**
     * Function rewritten based on old_add_item() from Yannick Warnier. Due the fact that users can decide where the item should come, I had to overlook this function and
     * I found it better to rewrite it. Old function is still available. Added also the possibility to add a description.
     *
     * @param int $parent
     * @param int $previous
     * @param string $type
     * @param int $id
     * @param string $title
     * @param string $description
     * @return int
     */
    function add_item($parent, $previous, $type = 'dokeos_chapter', $id, $title, $description, $prerequisites=0, $max_time_allowed=0)
    {
    	global $charset;
    	
    	if($this->debug>0){error_log('New LP - In learnpath::add_item('.$parent.','.$previous.','.$type.','.$id.','.$title.')',0);}
    	
    	$tbl_lp_item = Database::get_course_table('lp_item');
    	$parent = intval($parent);
    	$previous = intval($previous);
    	$type = $this->escape_string($type);
    	$id = intval($id);    	
    	$max_time_allowed = $this->escape_string(htmlentities($max_time_allowed));
        if (empty($max_time_allowed)) { $max_time_allowed = 0; }
    	
    	$title = $this->escape_string(mb_convert_encoding($title,$this->encoding,$charset));
    	$description = $this->escape_string(mb_convert_encoding($description,$this->encoding,$charset)); 
    	
    	$sql_count = "
    		SELECT COUNT(id) AS num
    		FROM " . $tbl_lp_item . "
    		WHERE
    			lp_id = " . $this->get_id() . " AND
    			parent_item_id = " . $parent;
    	
    	$res_count = api_sql_query($sql_count, __FILE__, __LINE__);
   		$row = Database::fetch_array($res_count);
   		
   		$num = $row['num'];
   		
   		if($num > 0) {
	    	if($previous == 0) {
	    		$sql = "
	   				SELECT
	   					id,
	   					next_item_id,
	   					display_order
	   				FROM " . $tbl_lp_item . "
	   				WHERE
	   					lp_id = " . $this->get_id() . " AND
	   					parent_item_id = " . $parent . " AND
	   					previous_item_id = 0 OR previous_item_id=".$parent;
	    		
	    		$result = api_sql_query($sql, __FILE__, __LINE__);
	   			$row = Database::fetch_array($result);
	   			
	   			$tmp_previous = 0;
	   			$next = $row['id'];
	   			$display_order = 0;
	    	} else {
	   			$previous = (int) $previous;	   			
	   			$sql = "
	   				SELECT
	   					id,
	   					previous_item_id,
	   					next_item_id,
	   					display_order
	   				FROM " . $tbl_lp_item . "
	   				WHERE
	   					lp_id = " . $this->get_id() . " AND
	   					id = " . $previous;
	   			
	   			$result = api_sql_query($sql, __FILE__, __LINE__);
	   			$row = Database::fetch_array($result);
	   			
	   			$tmp_previous = $row['id'];
	   			$next = $row['next_item_id'];
	   			
	   			$display_order = $row['display_order'];
	    	}
   		} else {
   			$tmp_previous = 0;
   			$next = 0;
   			$display_order = 0;
    	}
    	
    	$new_item_id = -1;
    	$id = $this->escape_string($id);
    	
    	if($type == 'quiz') {  
    		$sql = 'SELECT SUM(ponderation) 
					FROM '.Database :: get_course_table(TABLE_QUIZ_QUESTION).' as quiz_question
					INNER JOIN  '.Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION).' as quiz_rel_question
					ON quiz_question.id = quiz_rel_question.question_id
					AND quiz_rel_question.exercice_id = '.$id;					
			$rsQuiz = api_sql_query($sql, __FILE__, __LINE__);			
			$max_score = Database::result($rsQuiz, 0, 0);
    	} else {
    		$max_score = 100;    		
    	}
		
		if($prerequisites!=0) {
			$sql_ins = "
	    		INSERT INTO " . $tbl_lp_item . " (
	    			lp_id,
	    			item_type,
	    			ref,
	    			title,
	    			description,
	    			path,
					max_score,
	    			parent_item_id,
	    			previous_item_id,
	    			next_item_id,
	    			display_order,	    			 
					prerequisite,
					max_time_allowed
							
	    		) VALUES (
	    			" . $this->get_id() . ",
	    			'" . $type . "',
	    			'',
	    			'" . $title . "',
	    			'" . $description . "',
	    			'" . $id . "',
					'" . $max_score. "',
	    			" . $parent . ",
	    			" . $previous . ",
	    			" . $next . ",
	    			" . ($display_order + 1) . ",	    				    			
	    			" . $prerequisites . ",
	    			" . $max_time_allowed . "
	    		)";
		} else {
	    	//insert new item
	    	$sql_ins = "
	    		INSERT INTO " . $tbl_lp_item . " (
	    			lp_id,
	    			item_type,
	    			ref,
	    			title,
	    			description,
	    			path,
					max_score,
	    			parent_item_id,
	    			previous_item_id,
	    			next_item_id,	    				
	    			display_order,
	    			max_time_allowed
	    		) VALUES (
	    			" . $this->get_id() . ",
	    			'" . $type . "',
	    			'',
	    			'" . $title . "',
	    			'" . $description . "',
	    			'" . $id . "',
					'" . $max_score. "',
	    			" . $parent . ",
	    			" . $previous . ",
	    			" . $next . ",
	    			" . ($display_order + 1) . ",
	    			" . $max_time_allowed . "	    			
	    		)";
		}
    	
    	if($this->debug>2){error_log('New LP - Inserting dokeos_chapter: '.$sql_ins,0);}

    	$res_ins = api_sql_query($sql_ins, __FILE__, __LINE__);
    	
    	if($res_ins > 0)
    	{
	    	$new_item_id = Database::get_last_insert_id($res_ins);
	    	
	    	//update the item that should come after the new item
	    	$sql_update_next = "
	    		UPDATE " . $tbl_lp_item . "
	    		SET previous_item_id = " . $new_item_id . "
	    		WHERE id = " . $next;
	    	
	    	$res_update_next = api_sql_query($sql_update_next, __FILE__, __LINE__);
	    	
	    	//update the item that should be before the new item
		    $sql_update_previous = "
		    	UPDATE " . $tbl_lp_item . "
		    	SET next_item_id = " . $new_item_id . "
		    	WHERE id = " . $tmp_previous;
		    
		    $res_update_previous = api_sql_query($sql_update_previous, __FILE__, __LINE__);
	    			   
	    	//update all the items after the new item
	    	$sql_update_order = "
	    		UPDATE " . $tbl_lp_item . "
	    		SET display_order = display_order + 1
	    		WHERE
	    			lp_id = " . $this->get_id() . " AND
	    			id <> " . $new_item_id . " AND
	    			parent_item_id = " . $parent . " AND
	    			display_order > " . $display_order;
	    	
	    	$res_update_previous = api_sql_query($sql_update_order, __FILE__, __LINE__);
	    	
	    	//update the item that should come after the new item
	    	$sql_update_ref = "
	    		UPDATE " . $tbl_lp_item . "
	    		SET ref = " . $new_item_id . "
	    		WHERE id = " . $new_item_id;
	    	
	    	api_sql_query($sql_update_ref, __FILE__, __LINE__);
	    	
    	}
    	
		// upload audio
		if (!empty($_FILES['mp3']['name'])) {
			// create the audio folder if it does not exist yet
			global $_course;
			$filepath = api_get_path('SYS_COURSE_PATH').$_course['path'].'/document/';			
			if(!is_dir($filepath.'audio')) {
				$perm = api_get_setting('permissions_for_new_directories');
				$perm = octdec(!empty($perm)?$perm:'0770');
				mkdir($filepath.'audio',$perm);
				$audio_id=add_document($_course,'/audio','folder',0,'audio');
				api_item_property_update($_course, TOOL_DOCUMENT, $audio_id, 'FolderCreated', api_get_user_id());				
			}
			
			// upload the file in the documents tool			
			include_once(api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php');			
			$file_path = handle_uploaded_document($_course, $_FILES['mp3'],api_get_path('SYS_COURSE_PATH').$_course['path'].'/document','/audio',api_get_user_id(),'','','','','',false);			
			
			// getting the filename only
			$file_components = explode('/',$file_path);
			$file = $file_components[count($file_components)-1];
			
			// store the mp3 file in the lp_item table
			$sql_insert_audio = "UPDATE $tbl_lp_item SET audio = '".Database::escape_string($file)."' WHERE id = '".Database::escape_string($new_item_id)."'";
			api_sql_query($sql_insert_audio, __FILE__, __LINE__);
		}
    	return $new_item_id;
    }
   
    /**
     * Static admin function allowing addition of a learnpath to a course.
     * @param	string	Course code
     * @param	string	Learnpath name
     * @param	string	Learnpath description string, if provided
     * @param	string	Type of learnpath (default = 'guess', others = 'dokeos', 'aicc',...)
     * @param	string	Type of files origin (default = 'zip', others = 'dir','web_dir',...)
     * @param	string	Zip file containing the learnpath or directory containing the learnpath
     * @return	integer	The new learnpath ID on success, 0 on failure
     */
    function add_lp($course,$name,$description='',$learnpath='guess',$origin='zip',$zipname='')
    {
		//if($this->debug>0){error_log('New LP - In learnpath::add_lp()',0);}
    	//TODO
    	$tbl_lp = Database::get_course_table('lp');
    	//check course code exists
    	//check lp_name doesn't exist, otherwise append something
    	$i = 0;
    	$name = learnpath::escape_string(htmlentities($name)); //Kevin Van Den Haute: added htmlentities()
    	$check_name = "SELECT * FROM $tbl_lp WHERE name = '$name'";
    	//if($this->debug>2){error_log('New LP - Checking the name for new LP: '.$check_name,0);}
    	$res_name = api_sql_query($check_name, __FILE__, __LINE__);
		while(Database::num_rows($res_name)){
    		//there is already one such name, update the current one a bit
    		$i++;
    		$name = $name.' - '.$i;
	    	$check_name = "SELECT * FROM $tbl_lp WHERE name = '$name'";
	    	//if($this->debug>2){error_log('New LP - Checking the name for new LP: '.$check_name,0);}
	    	$res_name = api_sql_query($check_name, __FILE__, __LINE__);
    	}
    	//new name does not exist yet; keep it
    	//escape description
    	$description = learnpath::escape_string(htmlentities($description)); //Kevin: added htmlentities()
    	$type = 1;
    	switch($learnpath){
    		case 'guess':
    			break;
    		case 'dokeos':
    			$type = 1;
    			break;
    		case 'aicc':
    			break;
    	}
    	switch($origin){
    		case 'zip':
	    		//check zipname string. If empty, we are currently creating a new Dokeos learnpath
    			break;
    		case 'manual':
    		default:
		    	$get_max = "SELECT MAX(display_order) FROM $tbl_lp";
		    	$res_max = api_sql_query($get_max, __FILE__, __LINE__);
		    	if(Database::num_rows($res_max)<1){
		    		$dsp = 1;
		    	}else{
		    		$row = Database::fetch_array($res_max);
		    		$dsp = $row[0]+1;
		    	}
    			$sql_insert = "INSERT INTO $tbl_lp " .
    					"(lp_type,name,description,path,default_view_mod," .
    					"default_encoding,display_order,content_maker," .
    					"content_local,js_lib) " .
    					"VALUES ($type,'$name','$description','','embedded'," .
    					"'UTF-8','$dsp','Dokeos'," .
    					"'local','')";
    			//if($this->debug>2){error_log('New LP - Inserting new lp '.$sql_insert,0);}
    			$res_insert = api_sql_query($sql_insert, __FILE__, __LINE__);
				$id = Database::get_last_insert_id();
				if($id>0){
					//insert into item_property
					api_item_property_update(api_get_course_info(),TOOL_LEARNPATH,$id,'LearnpathAdded',api_get_user_id());
					return $id;
				}
    			break;
    	}
    }

    /**
     * Appends a message to the message attribute
     * @param	string	Message to append.
     */
    function append_message($string)
    {
		if($this->debug>0){error_log('New LP - In learnpath::append_message()',0);}
    	$this->message .= $string;
    }

    /**
     * Autocompletes the parents of an item in case it's been completed or passed
     * @param	integer	Optional ID of the item from which to look for parents
     */
    function autocomplete_parents($item)
    {
		if($this->debug>0){error_log('New LP - In learnpath::autocomplete_parents()',0);}
    	if(empty($item)){
    		$item = $this->current;
    	}
    	$parent_id = $this->items[$item]->get_parent();
    	if($this->debug>2){error_log('New LP - autocompleting parent of item '.$item.' (item '.$parent_id.')',0);}
    	if(is_object($this->items[$item]) and !empty($parent_id))
    	{//if $item points to an object and there is a parent
    		if($this->debug>2){error_log('New LP - '.$item.' is an item, proceed',0);}
    		$current_item =& $this->items[$item];
    		$parent =& $this->items[$parent_id]; //get the parent
			//new experiment including failed and browsed in completed status
			$current_status = $current_item->get_status();
    		if($current_item->is_done() || $current_status=='browsed' || $current_status=='failed')
    		{
    			//if the current item is completed or passes or succeeded
    			$completed = true;
    			if($this->debug>2){error_log('New LP - Status of current item is alright',0);}
    			foreach($parent->get_children() as $child)
    			{
    				//check all his brothers (his parent's children) for completion status
    				if($child!= $item)
    				{
    					if($this->debug>2){error_log('New LP - Looking at brother with ID '.$child.', status is '.$this->items[$child]->get_status(),0);}
    					//if($this->items[$child]->status_is(array('completed','passed','succeeded')))
    					//Trying completing parents of failed and browsed items as well
    					if($this->items[$child]->status_is(array('completed','passed','succeeded','browsed','failed')))
    					{
    						//keep completion status to true
    					}else{
    						if($this->debug>2){error_log('New LP - Found one incomplete child of '.$parent_id.': '.$child.' is '.$this->items[$child]->get_status(),0);}
    						$completed = false;
    					}
    				}
    			}
    			if($completed == true)
    			{ //if all the children were completed
	    			$parent->set_status('completed');
	    			$parent->save(false,$this->prerequisites_match($parent->get_id()));
    				$this->update_queue[$parent->get_id()] = $parent->get_status();
    				if($this->debug>2){error_log('New LP - Added parent to update queue '.print_r($this->update_queue,true),0);}
	    			$this->autocomplete_parents($parent->get_id()); //recursive call
    			}
    		}else{
    			//error_log('New LP - status of current item is not enough to get bothered with it',0);
    		}
    	}
    }

    /**
     * Autosaves the current results into the database for the whole learnpath
     */
    function autosave()
    {
		if($this->debug>0){error_log('New LP - In learnpath::autosave()',0);}
    	//TODO add aditionnal save operations for the learnpath itself
    }

    /**
     * Clears the message attribute
     */
    function clear_message()
    {
		if($this->debug>0){error_log('New LP - In learnpath::clear_message()',0);}
    	$this->message = '';
    }
    /**
     * Closes the current resource
     *
     * Stops the timer
     * Saves into the database if required
     * Clears the current resource data from this object
     * @return	boolean	True on success, false on failure
     */

    function close()
    {
		if($this->debug>0){error_log('New LP - In learnpath::close()',0);}
    	if(empty($this->lp_id))
    	{
    		$this->error = 'Trying to close this learnpath but no ID is set';
    		return false;
    	}
    	$this->current_time_stop = time();
    	if($this->save)
    	{
	    	$learnpath_view_table = Database::get_course_table(TABLE_LP_VIEW);
	    	/*
	    	$sql = "UPDATE $learnpath_view_table " .
	    			"SET " .
	    			"stop_time = ".$this->current_time_stop.", " .
	    			"score = ".$this->current_score.", ".
	    			"WHERE learnpath_id = '".$this->lp_id."'";
	    	//$res = Database::query($sql);
	    	$res = api_sql_query($res);
	    	if(mysql_affected_rows($res)<1)
	    	{
	    		$this->error = 'Could not update learnpath_view table while closing learnpath';
	    		return false;
	    	}
	    	*/    		
    	}
    	$this->ordered_items = array();
    	$this->index=0;
    	unset($this->lp_id);
    	//unset other stuff
    	return true;
    }

    /**
     * Static admin function allowing removal of a learnpath
     * @param	string	Course code
     * @param	integer	Learnpath ID
     * @param	string	Whether to delete data or keep it (default: 'keep', others: 'remove')
     * @return	boolean	True on success, false on failure (might change that to return number of elements deleted)
     */
    function delete($course=null,$id=null,$delete='keep')
    {
    	//TODO implement a way of getting this to work when the current object is not set
    	//In clear: implement this in the item class as well (abstract class) and use the given ID in queries
    	//if(empty($course)){$course = api_get_course_id();}
    	//if(empty($id)){$id = $this->get_id();}
    	//If an ID is specifically given and the current LP is not the same,
    	//prevent delete
    	if(!empty($id) && ($id != $this->lp_id)){return false;}

    	$lp = Database::get_course_table('lp');
    	$lp_view = Database::get_course_table('lp_view');
    	$lp_item_view = Database::get_course_table('lp_item_view');
    	
		//if($this->debug>0){error_log('New LP - In learnpath::delete()',0);}
		//delete lp item id
    	foreach($this->items as $id => $dummy) {
    		//$this->items[$id]->delete();
    		$sql_del_view = "DELETE FROM $lp_item_view WHERE lp_item_id = '".$id."'";
    		$res_del_item_view=api_sql_query($sql_del_view,__FILE__,__LINE__);
    	}

    	$sql_del_view = "DELETE FROM $lp_view WHERE lp_id = ".$this->lp_id;
    	//if($this->debug>2){error_log('New LP - Deleting views bound to lp '.$this->lp_id.': '.$sql_del_view,0);}
    	$res_del_view = api_sql_query($sql_del_view, __FILE__, __LINE__);
		$this->toggle_publish($this->lp_id,'i');
    	//if($this->debug>2){error_log('New LP - Deleting lp '.$this->lp_id.' of type '.$this->type,0);}
    	if($this->type == 2 OR $this->type==3){
    		//this is a scorm learning path, delete the files as well
    		$sql = "SELECT path FROM $lp WHERE id = ".$this->lp_id;
    		$res = api_sql_query($sql, __FILE__, __LINE__);
    		if(Database::num_rows($res)>0){
    			$row = Database::fetch_array($res);
    			$path = $row['path'];
    			$sql = "SELECT id FROM $lp WHERE path = '$path' AND id != ".$this->lp_id;
    			$res = api_sql_query($sql, __FILE__, __LINE__);
    			if(Database::num_rows($res)>0)
    			{ //another learning path uses this directory, so don't delete it 
    				if($this->debug>2){error_log('New LP - In learnpath::delete(), found other LP using path '.$path.', keeping directory',0);}
    			}else{
    				//no other LP uses that directory, delete it
			     	$course_rel_dir  = api_get_course_path().'/scorm/'; //scorm dir web path starting from /courses
					$course_scorm_dir = api_get_path(SYS_COURSE_PATH).$course_rel_dir; //absolute system path for this course
    				if($delete == 'remove' && is_dir($course_scorm_dir.$path) and !empty($course_scorm_dir)){
    					if($this->debug>2){error_log('New LP - In learnpath::delete(), found SCORM, deleting directory: '.$course_scorm_dir.$path,0);}
    					exec('rm -rf '.$course_scorm_dir.$path);
    				}
    			}
    		}
    	}
    	$sql_del_lp = "DELETE FROM $lp WHERE id = ".$this->lp_id;
    	//if($this->debug>2){error_log('New LP - Deleting lp '.$this->lp_id.': '.$sql_del_lp,0);}
    	$res_del_lp = api_sql_query($sql_del_lp, __FILE__, __LINE__);
    	$this->update_display_order();//updates the display order of all lps
 		api_item_property_update(api_get_course_info(),TOOL_LEARNPATH,$this->lp_id,'delete',api_get_user_id());
		
		require_once '../gradebook/lib/be.inc.php';
		$tbl_grade_link = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
	    //delete link of gradebook tool
	     $sql='SELECT gl.id FROM '.$tbl_grade_link.' gl WHERE gl.type="4" AND gl.ref_id="'.$id.'";';
	     $result=api_sql_query($sql,__FILE__,__LINE__);
	     $row=Database::fetch_array($result,'ASSOC');
	     $link= LinkFactory :: load($row['id']);
	     if ($link[0] != null) {
	     	$link[0]->delete();
	     }	    	
    	//TODO: also delete items and item-views
        if (api_get_setting('search_enabled') == 'true') {
          require_once(api_get_path(LIBRARY_PATH) .'specific_fields_manager.lib.php');
          $r = delete_all_values_for_item($this->cc, TOOL_LEARNPATH, $this->lp_id);
        }
    }

    /**
     * Removes all the children of one item - dangerous!
     * @param	integer	Element ID of which children have to be removed
     * @return	integer	Total number of children removed
     */
    function delete_children_items($id){
		if($this->debug>0){error_log('New LP - In learnpath::delete_children_items('.$id.')',0);}
    	$num = 0;
    	if(empty($id) || $id != strval(intval($id))){return false;}
		$lp_item = Database::get_course_table('lp_item');
		$sql = "SELECT * FROM $lp_item WHERE parent_item_id = $id";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		while($row = Database::fetch_array($res)){
			$num += $this->delete_children_items($row['id']);
			$sql_del = "DELETE FROM $lp_item WHERE id = ".$row['id'];
			$res_del = api_sql_query($sql_del, __FILE__, __LINE__);
			$num++;
		}
		return $num;
    }

    /**
     * Removes an item from the current learnpath
     * @param	integer	Elem ID (0 if first)
     * @param	integer	Whether to remove the resource/data from the system or leave it (default: 'keep', others 'remove')
     * @return	integer	Number of elements moved
     * @todo implement resource removal
     */
    function delete_item($id, $remove='keep')
    {
		if($this->debug>0){error_log('New LP - In learnpath::delete_item()',0);}
    	//TODO - implement the resource removal
    	if(empty($id) || $id != strval(intval($id))){return false;}
    	//first select item to get previous, next, and display order
		$lp_item = Database::get_course_table('lp_item');
    	$sql_sel = "SELECT * FROM $lp_item WHERE id = $id";
    	$res_sel = api_sql_query($sql_sel,__FILE__,__LINE__);
    	if(Database::num_rows($res_sel)<1){return false;}
    	$row = Database::fetch_array($res_sel);
    	$previous = $row['previous_item_id'];
    	$next = $row['next_item_id'];
    	$display = $row['display_order'];
    	$parent = $row['parent_item_id'];
    	$lp = $row['lp_id'];
    	//delete children items
    	$num = $this->delete_children_items($id);
    	if($this->debug>2){error_log('New LP - learnpath::delete_item() - deleted '.$num.' children of element '.$id,0);}
    	//now delete the item
    	$sql_del = "DELETE FROM $lp_item WHERE id = $id";
    	if($this->debug>2){error_log('New LP - Deleting item: '.$sql_del,0);}
    	$res_del = api_sql_query($sql_del,__FILE__,__LINE__);
    	//now update surrounding items
    	$sql_upd = "UPDATE $lp_item SET next_item_id = $next WHERE id = $previous";
    	$res_upd = api_sql_query($sql_upd,__FILE__,__LINE__);
    	$sql_upd = "UPDATE $lp_item SET previous_item_id = $previous WHERE id = $next";
    	$res_upd = api_sql_query($sql_upd,__FILE__,__LINE__);
    	//now update all following items with new display order
    	$sql_all = "UPDATE $lp_item SET display_order = display_order-1 WHERE lp_id = $lp AND parent_item_id = $parent AND display_order > $display";
        $res_all = api_sql_query($sql_all,__FILE__,__LINE__);
        // remove from search engine if enabled
        if (api_get_setting('search_enabled') == 'true') {
          $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
          $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level=%d LIMIT 1';
          $sql = sprintf($sql, $tbl_se_ref, $this->cc, TOOL_LEARNPATH, $lp, $id);
          $res = api_sql_query($sql, __FILE__, __LINE__);
          if (Database::num_rows($res) > 0) {
            $row2 = Database::fetch_array($res);
          require_once(api_get_path(LIBRARY_PATH) .'search/DokeosIndexer.class.php');
          $di = new DokeosIndexer();
            $di->remove_document((int)$row2['search_did']);
          }
          $sql = 'DELETE FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level=%d LIMIT 1';
          $sql = sprintf($sql, $tbl_se_ref, $this->cc, TOOL_LEARNPATH, $lp, $id);
          api_sql_query($sql, __FILE__, __LINE__);
        }
    }

    /**
     * Updates an item's content in place
     * @param	integer	Element ID
	 * @param	integer	Parent item ID
	 * @param	integer Previous item ID
	 * @param   string	Item title
	 * @param   string  Item description
	 * @param   string  Prerequisites (optional)
	 * @param   string  Indexing terms (optional)
     * @param   array   The array resulting of the $_FILES[mp3] element
     * @return	boolean	True on success, false on error
     */
    function edit_item($id, $parent, $previous, $title, $description, $prerequisites=0, $audio=NULL, $max_time_allowed=0) {
    	if($this->debug > 0){error_log('New LP - In learnpath::edit_item()', 0);}
        if(empty($max_time_allowed)) { $max_time_allowed = 0;}
    	if(empty($id) or ($id != strval(intval($id))) or empty($title)){ return false; }
    	
    	$tbl_lp_item = Database::get_course_table('lp_item');    	
    	$sql_select = "SELECT * FROM " . $tbl_lp_item . " WHERE id = " . $id;
    	$res_select = api_sql_query($sql_select, __FILE__, __LINE__);
    	$row_select = Database::fetch_array($res_select);
        $audio_update_sql = '';        
        if (is_array($audio) && !empty($audio['tmp_name']) && $audio['error']===0) {        	
        	// create the audio folder if it does not exist yet
			global $_course;
			$filepath = api_get_path('SYS_COURSE_PATH').$_course['path'].'/document/';			
			if(!is_dir($filepath.'audio')) {
				$perm = api_get_setting('permissions_for_new_directories');
				$perm = octdec(!empty($perm)?$perm:'0770');
				mkdir($filepath.'audio',$perm);
				$audio_id=add_document($_course,'/audio','folder',0,'audio');
				api_item_property_update($_course, TOOL_DOCUMENT, $audio_id, 'FolderCreated', api_get_user_id());				
			}
			
            //upload file in documents
            $pi = pathinfo($audio['name']);
            if ($pi['extension'] == 'mp3') {
                $c_det = api_get_course_info($this->cc);
                $bp = api_get_path(SYS_COURSE_PATH).$c_det['path'].'/document';
                $path = handle_uploaded_document($c_det,$audio,$bp,'/audio',api_get_user_id(),0,null,'',0,'rename',false,0);
                $path = substr($path,7);
                //update reference in lp_item - audio path is the path from inside de document/audio/ dir
            	$audio_update_sql = ", audio = '".Database::escape_string($path)."' ";
          	}
        }

    	$same_parent	= ($row_select['parent_item_id'] == $parent) ? true : false;
    	$same_previous	= ($row_select['previous_item_id'] == $previous) ? true : false;
    	
    	if($same_parent && $same_previous) {
    		//only update title and description
    		$sql_update = "
    			UPDATE " . $tbl_lp_item . " 
    			SET 
    				title = '" . $this->escape_string(htmlentities($title)) . "',
					prerequisite = '".$prerequisites."',
    				description = '" . $this->escape_string(htmlentities($description)) . "'
                    ". $audio_update_sql . ",
                    max_time_allowed = '" . $this->escape_string(htmlentities($max_time_allowed)) . "'
    			WHERE id = " . $id;
    		$res_update = api_sql_query($sql_update, __FILE__, __LINE__);
    	}
    	else
    	{
    		$old_parent		 	= $row_select['parent_item_id'];
    		$old_previous	 	= $row_select['previous_item_id'];
    		$old_next		 	= $row_select['next_item_id'];
    		$old_order		 	= $row_select['display_order'];
    		$old_prerequisite	= $row_select['prerequisite'];
    		$old_max_time_allowed	= $row_select['max_time_allowed'];
    		
    		/* BEGIN -- virtually remove the current item id */
    		/* for the next and previous item it is like the current item doesn't exist anymore */
    		
    		if($old_previous != 0)
    		{
	    		$sql_update_next = "
		    		UPDATE " . $tbl_lp_item . "
		    		SET next_item_id = " . $old_next . "
		    		WHERE id = " . $old_previous;
		    	$res_update_next = api_sql_query($sql_update_next, __FILE__, __LINE__);
	    		//echo '<p>' . $sql_update_next . '</p>';
    		}
    		
    		if($old_next != 0)
    		{
		    	$sql_update_previous = "
		    		UPDATE " . $tbl_lp_item . "
		    		SET previous_item_id = " . $old_previous . "
		    		WHERE id = " . $old_next;
		    	$res_update_previous = api_sql_query($sql_update_previous, __FILE__, __LINE__);
		    	
		    	//echo '<p>' . $sql_update_previous . '</p>';
    		}
    		
    		//display_order - 1 for every item with a display_order bigger then the display_order of the current item
	    	$sql_update_order = "
	    		UPDATE " . $tbl_lp_item . "
	    		SET display_order = display_order - 1
	    		WHERE
	    			display_order > " . $old_order . " AND
	    			parent_item_id = " . $old_parent;
	    	$res_update_order = api_sql_query($sql_update_order, __FILE__, __LINE__);
	    	
	    	//echo '<p>' . $sql_update_order . '</p>';
	    	
    		/* END -- virtually remove the current item id */
    		
    		/* BEGIN -- update the current item id to his new location */
    		
    		if($previous == 0)
    		{
	    		//select the data of the item that should come after the current item
	    		$sql_select_old = "
	    			SELECT
	    				id,
	    				display_order
	    			FROM " . $tbl_lp_item . "
	    			WHERE
	    				lp_id = " . $this->lp_id . " AND
	    				parent_item_id = " . $parent . " AND
	    				previous_item_id = " . $previous;
	    		$res_select_old = api_sql_query($sql_select_old, __FILE__, __LINE__);
		    	$row_select_old = Database::fetch_array($res_select_old);
		    	
		    	//echo '<p>' . $sql_select_old . '</p>';
		    	
		    	//if the new parent didn't have children before
		    	if(Database::num_rows($res_select_old) == 0)
		    	{
		    		$new_next	= 0;
		    		$new_order	= 1;
		    	}
		    	else
		    	{
		    		$new_next	= $row_select_old['id'];
		    		$new_order	= $row_select_old['display_order'];
		    	}
		    	
		    	//echo 'New next_item_id of current item: ' . $new_next . '<br />';
		    	//echo 'New previous_item_id of current item: ' . $previous . '<br />';
		    	//echo 'New display_order of current item: ' . $new_order . '<br />';
	
    		}
    		else
    		{
    			//select the data of the item that should come before the current item
	    		$sql_select_old = "
	    			SELECT
	    				next_item_id,
	    				display_order
	    			FROM " . $tbl_lp_item . "
	    			WHERE id = " . $previous;
	    		$res_select_old = api_sql_query($sql_select_old, __FILE__, __LINE__);
		    	$row_select_old = Database::fetch_array($res_select_old);
		    	
		    	//echo '<p>' . $sql_select_old . '</p>';
		    	
		    	//echo 'New next_item_id of current item: ' . $row_select_old['next_item_id'] . '<br />';
		    	//echo 'New previous_item_id of current item: ' . $previous . '<br />';
		    	//echo 'New display_order of current item: ' . ($row_select_old['display_order'] + 1) . '<br />';
	    		
		    	$new_next	= $row_select_old['next_item_id'];
		    	$new_order	= $row_select_old['display_order'] + 1;
    		}
	    	
    		//update the current item with the new data
    		$sql_update = "
	    		UPDATE " . $tbl_lp_item . "
	    		SET
	    			title = '" . $this->escape_string(htmlentities($title)) . "',
	    			description = '" . $this->escape_string(htmlentities($description)) . "',
	    			parent_item_id = " . $parent . ",
	    			previous_item_id = " . $previous . ",
	    			next_item_id = " . $new_next . ",
	    			display_order = " . $new_order . "
                    ". $audio_update_sql . "
	    		WHERE id = " . $id;
    		$res_update_next = api_sql_query($sql_update, __FILE__, __LINE__);
    		//echo '<p>' . $sql_update . '</p>';
    		
    		if($previous != 0)
    		{
    			//update the previous item's next_item_id
	    		$sql_update_previous = "
			    	UPDATE " . $tbl_lp_item . "
			    	SET next_item_id = " . $id . "
			    	WHERE id = " . $previous;
	    		$res_update_next = api_sql_query($sql_update_previous, __FILE__, __LINE__);
	    		//echo '<p>' . $sql_update_previous . '</p>';
    		}
    		
    		if($new_next != 0)
    		{
       			//update the next item's previous_item_id
	    		$sql_update_next = "
			    	UPDATE " . $tbl_lp_item . "
			    	SET previous_item_id = " . $id . "
			    	WHERE id = " . $new_next;
			  	$res_update_next = api_sql_query($sql_update_next, __FILE__, __LINE__);
			  	//echo '<p>' . $sql_update_next . '</p>';
    		}
    		
    		if($old_prerequisite!=$prerequisites){
    			$sql_update_next = "
		    		UPDATE " . $tbl_lp_item . "
		    		SET prerequisite = " . $prerequisites . "
		    		WHERE id = " . $id;
		    	$res_update_next = api_sql_query($sql_update_next, __FILE__, __LINE__);
    		}
    		
    		if($old_max_time_allowed!=$max_time_allowed){
    			$sql_update_max_time_allowed = "
		    		UPDATE " . $tbl_lp_item . "
		    		SET max_time_allowed = " . $max_time_allowed . "
		    		WHERE id = " . $id;
		    	$res_update_max_time_allowed = api_sql_query($sql_update_max_time_allowed, __FILE__, __LINE__);
    		}

    		
    		//update all the items with the same or a bigger display_order than 
    		//the current item
	    	$sql_update_order = "
			   	UPDATE " . $tbl_lp_item . "
			   	SET display_order = display_order + 1
			   	WHERE
			   		lp_id = " . $this->get_id() . " AND
			   		id <> " . $id . " AND
			   		parent_item_id = " . $parent . " AND
			   		display_order >= " . $new_order;
	    	
	    	$res_update_next = api_sql_query($sql_update_order, __FILE__, __LINE__);
			//echo '<p>' . $sql_update_order . '</p>';
    		
    		/* END -- update the current item id to his new location */
    	}
    }
    
    /**

     * Updates an item's prereq in place

     * @param	integer	Element ID

	 * @param	string	Prerequisite Element ID
	 * 
	 * @param	string	Prerequisite item type
	 * 
	 * @param	string	Prerequisite min score
	 * 
	 * @param	string	Prerequisite max score

     * @return	boolean	True on success, false on error

     */

    function edit_item_prereq($id, $prerequisite_id, $mastery_score = 0, $max_score = 100)

    {
		if($this->debug>0){error_log('New LP - In learnpath::edit_item_prereq('.$id.','.$prerequisite_id.','.$mastery_score.','.$max_score.')',0);}

    	if(empty($id) or ($id != strval(intval($id))) or empty($prerequisite_id)){ return false; }

    	$prerequisite_id = $this->escape_string($prerequisite_id);

		$tbl_lp_item = Database::get_course_table('lp_item');
		
		if(!is_numeric($mastery_score) || $mastery_score < 0)
			$mastery_score = 0;
		
		if(!is_numeric($max_score) || $max_score < 0)
			$max_score = 100;
		
		if($mastery_score > $max_score)
			$max_score = $mastery_score;
		
		if(!is_numeric($prerequisite_id))
			$prerequisite_id = 'NULL';
			
    	$sql_upd = "
    		UPDATE " . $tbl_lp_item . "
    		SET prerequisite = ".$prerequisite_id." WHERE id = ".$id;
    	$res_upd = api_sql_query($sql_upd ,__FILE__, __LINE__);

		if($prerequisite_id!='NULL' && $prerequisite_id!='')
		{
			$sql_upd = " UPDATE ".$tbl_lp_item." SET     	
	    			mastery_score = " . $mastery_score .
	    			//", max_score = " . $max_score . " " . //max score cannot be changed in the form anyway - see display_item_prerequisites_form()
	    			" WHERE ref = '" . $prerequisite_id."'" ; //will this be enough to ensure unicity?
	    	
	    	$res_upd = api_sql_query($sql_upd ,__FILE__, __LINE__);
		}
    	//TODO update the item object (can be ignored for now because refreshed)

    	return true;

    }

    /**

     * Escapes a string with the available database escape function

     * @param	string	String to escape

     * @return	string	String escaped

     */

    function escape_string($string){

		//if($this->debug>0){error_log('New LP - In learnpath::escape_string('.$string.')',0);}

    	return Database::escape_string($string);

    }

    /**

     * Static admin function exporting a learnpath into a zip file

     * @param	string	Export type (scorm, zip, cd)

     * @param	string	Course code

     * @param	integer Learnpath ID

     * @param	string	Zip file name

     * @return	string	Zip file path (or false on error)

     */

    function export_lp($type, $course, $id, $zipname)

    {

		//if($this->debug>0){error_log('New LP - In learnpath::export_lp()',0);}

    	//TODO

		if(empty($type) OR empty($course) OR empty($id) OR empty($zipname)){return false;}

		$url = '';

    	switch($type){

    		case 'scorm':
				
    			break;

    		case 'zip':

    			break;

    		case 'cdrom':

    			break;

    	}

    	return $url;

    }

    /**

     * Gets all the chapters belonging to the same parent as the item/chapter given

     * Can also be called as abstract method

     * @param	integer	Item ID

     * @return	array	A list of all the "brother items" (or an empty array on failure)

     */

    function get_brother_chapters($id){

		if($this->debug>0){error_log('New LP - In learnpath::get_brother_chapters()',0);}

    	if(empty($id) OR $id != strval(intval($id))){ return array();}

    	$lp_item = Database::get_course_table('lp_item');

    	$sql_parent = "SELECT * FROM $lp_item WHERE id = $id AND item_type='dokeos_chapter'";

    	$res_parent = api_sql_query($sql_parent,__FILE__,__LINE__);

    	if(Database::num_rows($res_parent)>0){

    		$row_parent = Database::fetch_array($res_parent);

    		$parent = $row_parent['parent_item_id'];

    		$sql_bros = "SELECT * FROM $lp_item WHERE parent_item_id = $parent AND id = $id AND item_type='dokeos_chapter' ORDER BY display_order";

    		$res_bros = api_sql_query($sql_bros,__FILE__,__LINE__);

    		$list = array();

    		while ($row_bro = Database::fetch_array($res_bros)){

    			$list[] = $row_bro;

    		}

    		return $list;

    	}

    	return array();

    }

    /**

     * Gets all the items belonging to the same parent as the item given

     * Can also be called as abstract method

     * @param	integer	Item ID

     * @return	array	A list of all the "brother items" (or an empty array on failure)

     */

    function get_brother_items($id){

		if($this->debug>0){error_log('New LP - In learnpath::get_brother_items('.$id.')',0);}

    	if(empty($id) OR $id != strval(intval($id))){ return array();}

    	$lp_item = Database::get_course_table('lp_item');

    	$sql_parent = "SELECT * FROM $lp_item WHERE id = $id";

    	$res_parent = api_sql_query($sql_parent,__FILE__,__LINE__);

    	if(Database::num_rows($res_parent)>0){

    		$row_parent = Database::fetch_array($res_parent);

    		$parent = $row_parent['parent_item_id'];

    		$sql_bros = "SELECT * FROM $lp_item WHERE parent_item_id = $parent ORDER BY display_order";

    		$res_bros = api_sql_query($sql_bros,__FILE__,__LINE__);

    		$list = array();

    		while ($row_bro = Database::fetch_array($res_bros)){

    			$list[] = $row_bro;

    		}

    		return $list;

    	}

    	return array();

    }

    /**
     * Get the specific prefix index terms of this learning path
     * @return  array Array of terms
     */
    function get_common_index_terms_by_prefix($prefix)
    {
    	require_once api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php';
    	$terms = get_specific_field_values_list_by_prefix($prefix, $this->cc, TOOL_LEARNPATH, $this->lp_id);
        $prefix_terms = array();
            foreach($terms as $term)
            {
			$prefix_terms[] = $term['value']; 
        }
        return $prefix_terms;
    }

    /**
     * Gets the number of items currently completed
     * @return integer The number of items currently completed
     */
    function get_complete_items_count()

    {

		if($this->debug>0){error_log('New LP - In learnpath::get_complete_items_count()',0);}

       	$i = 0;

    	foreach($this->items as $id => $dummy){

    		//if($this->items[$id]->status_is(array('completed','passed','succeeded'))){
    		//Trying failed and browsed considered "progressed" as well
    		if($this->items[$id]->status_is(array('completed','passed','succeeded','browsed','failed'))&&$this->items[$id]->get_type()!='dokeos_chapter'&&$this->items[$id]->get_type()!='dir'){

    			$i++;

    		}

    	}

    	return $i;

    }

    /**
     * Gets the current item ID
     * @return	integer	The current learnpath item id
     */
    function get_current_item_id()
    {
		$current = 0;
		if($this->debug>0){error_log('New LP - In learnpath::get_current_item_id()',0);}
    	if(!empty($this->current))
    	{
    		$current = $this->current;
    	}
		if($this->debug>2){error_log('New LP - In learnpath::get_current_item_id() - Returning '.$current,0);}
    	return $current;
    }
    
    
     /** Force to get the first learnpath item id
     * @return	integer	The current learnpath item id
     */
    function get_first_item_id()
    {
		$current = 0;
		if (is_array($this->ordered_items)) {
			$current = $this->ordered_items[0];
		}
    	return $current;
    }
    
    
    
    /**
     * Gets the total number of items available for viewing in this SCORM
     * @return	integer	The total number of items
     */
    function get_total_items_count()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_total_items_count()',0);}
    	return count($this->items);
    }
    /**
     * Gets the total number of items available for viewing in this SCORM but without chapters
     * @return	integer	The total no-chapters number of items
     */
    function get_total_items_count_without_chapters()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_total_items_count_without_chapters()',0);}
		$total=0;
		foreach($this->items as $temp=>$temp2){
			if(!in_array($temp2->get_type(), array('dokeos_chapter','chapter','dir'))) $total++;
		}
		return $total;
    }
	/**
     * Gets the first element URL.
     * @return	string	URL to load into the viewer
     */
    function first()
    {
		if($this->debug>0){error_log('New LP - In learnpath::first()',0);}
		//test if the last_item_seen exists and is not a dir
        if ( count($this->ordered_items) == 0 ) {
        	$this->index = 0;
        }
    	if(!empty($this->last_item_seen)
    		&& !empty($this->items[$this->last_item_seen])
    		&& $this->items[$this->last_item_seen]->get_type() != 'dir'
    		&& $this->items[$this->last_item_seen]->get_type() != 'dokeos_chapter'
    		&& $this->items[$this->last_item_seen]->is_done() != true
    	){
    		if($this->debug>2){error_log('New LP - In learnpath::first() - Last item seen is '.$this->last_item_seen.' of type '.$this->items[$this->last_item_seen]->get_type(),0);}
    		$index = -1;
    		foreach($this->ordered_items as $myindex => $item_id){
    			if($item_id == $this->last_item_seen){
	    			$index = $myindex;	
    				break;
    			}
    		}
    		if($index==-1){
    			//index hasn't changed, so item not found - panic (this shouldn't happen)
    			if($this->debug>2){error_log('New LP - Last item ('.$this->last_item_seen.') was found in items but not in ordered_items, panic!',0);}
    			return false;
    		}else{
    			$this->last = $this->last_item_seen;
    			$this->current = $this->last_item_seen;
    			$this->index = $index;
    		}
    	}else{
    		if($this->debug>2){error_log('New LP - In learnpath::first() - No last item seen',0);}
	    	$index = 0;
	    	//loop through all ordered items and stop at the first item that is
	    	//not a directory *and* that has not been completed yet
	    	while (!empty($this->ordered_items[$index]) 
	    			AND
	    			is_a($this->items[$this->ordered_items[$index]],'learnpathItem')
	    		AND 
	    		(
	    			$this->items[$this->ordered_items[$index]]->get_type() == 'dir'
	    		  	OR $this->items[$this->ordered_items[$index]]->get_type() == 'dokeos_chapter'
	    		  	OR $this->items[$this->ordered_items[$index]]->is_done() === true
	    		)
				AND $index < $this->max_ordered_items)
	    	{
		   		$index ++;
	    	}
	    	$this->last = $this->current;
	    	//current is 
	    	$this->current = $this->ordered_items[$index];
	    	$this->index = $index;
    		if($this->debug>2){error_log('New LP - In learnpath::first() - No last item seen. New last = '.$this->last.'('.$this->ordered_items[$index].')',0);}
    	}
    	if($this->debug>2){error_log('New LP - In learnpath::first() - First item is '.$this->get_current_item_id());}
    }

    /**

     * Gets the information about an item in a format usable as JavaScript to update

     * the JS API by just printing this content into the <head> section of the message frame

	 * @param	integer		Item ID

     * @return	string

     */

    function get_js_info($item_id=''){

		if($this->debug>0){error_log('New LP - In learnpath::get_js_info('.$item_id.')',0);}

		$info = '';

    	$item_id = $this->escape_string($item_id);

    	if(!empty($item_id) && is_object($this->items[$item_id])){

    		//if item is defined, return values from DB

    		$oItem = $this->items[$item_id];

    		$info .= '<script language="javascript">';

			$info .= "top.set_score(".$oItem->get_score().");\n";

			$info .= "top.set_max(".$oItem->get_max().");\n";

			$info .= "top.set_min(".$oItem->get_min().");\n";

			$info .= "top.set_lesson_status('".$oItem->get_status()."');";

			$info .= "top.set_session_time('".$oItem->get_scorm_time('js')."');";

			$info .= "top.set_suspend_data('".$oItem->get_suspend_data()."');";

			$info .= "top.set_saved_lesson_status('".$oItem->get_status()."');";

			$info .= "top.set_flag_synchronized();";

    		$info .= '</script>';

			if($this->debug>2){error_log('New LP - in learnpath::get_js_info('.$item_id.') - returning: '.$info,0);}

    		return $info;

    	}else{

    		//if item_id is empty, just update to default SCORM data

    		$info .= '<script language="javascript">';

			$info .= "top.set_score(".learnpathItem::get_score().");\n";

			$info .= "top.set_max(".learnpathItem::get_max().");\n";

			$info .= "top.set_min(".learnpathItem::get_min().");\n";

			$info .= "top.set_lesson_status('".learnpathItem::get_status()."');";

			$info .= "top.set_session_time('".learnpathItem::get_scorm_time('js')."');";

			$info .= "top.set_suspend_data('".learnpathItem::get_suspend_data()."');";

			$info .= "top.set_saved_lesson_status('".learnpathItem::get_status()."');";

			$info .= "top.set_flag_synchronized();";

    		$info .= '</script>';

			if($this->debug>2){error_log('New LP - in learnpath::get_js_info('.$item_id.') - returning: '.$info,0);}

    		return $info;

    	}

    }
    /**
     * Gets the js library from the database
     * @return	string	The name of the javascript library to be used
     */
	function get_js_lib(){
		$lib = '';
		if(!empty($this->js_lib)){
			$lib = $this->js_lib;
		}
		return $lib;
	}
    /**

     * Gets the learnpath database ID

     * @return	integer	Learnpath ID in the lp table

     */

    function get_id()

    {

		//if($this->debug>0){error_log('New LP - In learnpath::get_id()',0);}

    	if(!empty($this->lp_id))

    	{

    		return $this->lp_id;

    	}else{

    		return 0;

    	}

    }

    /**

     * Gets the last element URL.

     * @return string URL to load into the viewer

     */

    function get_last()

    {

		if($this->debug>0){error_log('New LP - In learnpath::get_last()',0);}

    	$this->index = count($this->ordered_items)-1;

    	return $this->ordered_items[$this->index];

    }

    /**

     * Gets the navigation bar for the learnpath display screen

     * @return	string	The HTML string to use as a navigation bar

     */

    function get_navigation_bar()

    {

		if($this->debug>0){error_log('New LP - In learnpath::get_navigation_bar()',0);}

    	//TODO find a good value for the following variables
    	$file = '';
    	$openDir = '';
    	$edoceo = '';
    	$time = 0;
    	$navbar = '';
    	$RequestUri = '';
		$mycurrentitemid = $this->get_current_item_id();
		if($this->mode == 'fullscreen')
		{
			$navbar = '<table cellpadding="0" cellspacing="0" align="left">'."\n".

    			  '  <tr> '."\n" .

    			  '    <td>'."\n" .

    			  '      <div class="buttons">'."\n" .

     			  '        <a href="lp_controller.php?action=stats" onclick="window.parent.API.save_asset();return true;" target="content_name_blank" title="stats" id="stats_link"><img border="0" src="../img/lp_stats.gif" title="'.get_lang('Reporting').'"></a>'."\n" .

    			  '        <a href="" onclick="dokeos_xajax_handler.switch_item('.$mycurrentitemid.',\'previous\');return false;" title="previous"><img border="0" src="../img/lp_leftarrow.gif" title="'.get_lang('ScormPrevious').'"></a>'."\n" .

    			  '        <a href="" onclick="dokeos_xajax_handler.switch_item('.$mycurrentitemid.',\'next\');return false;" title="next"  ><img border="0" src="../img/lp_rightarrow.gif" title="'.get_lang('ScormNext').'"></a>'."\n" .

				  //'        <a href="lp_controller.php?action=mode&mode=embedded" target="_top" title="embedded mode"><img border="0" src="../img/view_choose.gif" title="'.get_lang('ScormExitFullScreen').'"></a>'."\n" .

				  //'        <a href="lp_controller.php?action=list" target="_top" title="learnpaths list"><img border="0" src="../img/exit.png" title="Exit"></a>'."\n" .

				  '      </div>'."\n" .

    			  '    </td>'."\n" .

    			  '  </tr>'."\n" .

    			  '</table>'."\n" ;

			

		}else{

			$navbar = '<table cellpadding="0" cellspacing="0" align="left">'."\n".

    			  '  <tr> '."\n" .

    			  '    <td>'."\n" .

    			  '      <div class="buttons">'."\n" .

    			  '        <a href="lp_controller.php?action=stats" onclick="window.parent.API.save_asset();return true;" target="content_name" title="stats" id="stats_link"><img border="0" src="../img/lp_stats.gif" title="'.get_lang('Reporting').'"></a>'."\n" .

    			  '        <a href="" onclick="dokeos_xajax_handler.switch_item('.$mycurrentitemid.',\'previous\');return false;" title="previous"><img border="0" src="../img/lp_leftarrow.gif" title="'.get_lang('ScormPrevious').'"></a>'."\n" .

    			  '        <a href="" onclick="dokeos_xajax_handler.switch_item('.$mycurrentitemid.',\'next\');return false;" title="next"  ><img border="0" src="../img/lp_rightarrow.gif" title="'.get_lang('ScormNext').'"></a>'."\n" .

				 // '        <a href="lp_controller.php?action=mode&mode=fullscreen" target="_top" title="fullscreen"><img border="0" src="../img/view_fullscreen.gif" width="18" height="18" title="'.get_lang('ScormFullScreen').'"></a>'."\n" .

				  '      </div>'."\n" .

    			  '    </td>'."\n" .

    			  '  </tr>'."\n" .

    			  '</table>'."\n" ;

		}

    	return $navbar;

    }

    /**

     * Gets the next resource in queue (url).

     * @return	string	URL to load into the viewer

     */

    function get_next_index()

    {
		if($this->debug>0){error_log('New LP - In learnpath::get_next_index()',0);}
    	//TODO
    	$index = $this->index;
    	$index ++;
    	if($this->debug>2){error_log('New LP - Now looking at ordered_items['.($index).'] - type is '.$this->items[$this->ordered_items[$index]]->type,0);}
    	while(!empty($this->ordered_items[$index]) AND ($this->items[$this->ordered_items[$index]]->get_type() == 'dir' || $this->items[$this->ordered_items[$index]]->get_type() == 'dokeos_chapter') AND $index < $this->max_ordered_items)
    	{
    		$index ++;
    		if($index == $this->max_ordered_items)
    		{
    			return $this->index;
    		}
    	}
    	if(empty($this->ordered_items[$index])){
    		return $this->index;
    	}
    	if($this->debug>2){error_log('New LP - index is now '.$index,0);}
    	return $index;
    }
    /**
     * Gets item_id for the next element
     * @return	integer	Next item (DB) ID
     */
    function get_next_item_id()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_next_item_id()',0);}
    	$new_index = $this->get_next_index();
    	if(!empty($new_index))
    	{
			if(isset($this->ordered_items[$new_index]))
			{
				if($this->debug>2){error_log('New LP - In learnpath::get_next_index() - Returning '.$this->ordered_items[$new_index],0);}
	    		return $this->ordered_items[$new_index];
			}
    	}
    	if($this->debug>2){error_log('New LP - In learnpath::get_next_index() - Problem - Returning 0',0);}
		return 0;
    }
	/**
	 * Returns the package type ('scorm','aicc','scorm2004','dokeos','ppt'...)
	 * 
	 * Generally, the package provided is in the form of a zip file, so the function
	 * has been written to test a zip file. If not a zip, the function will return the
	 * default return value: ''
	 * @param	string	the path to the file
	 * @param	string 	the original name of the file
	 * @return	string	'scorm','aicc','scorm2004','dokeos' or '' if the package cannot be recognized
	 */
	function get_package_type($file_path,$file_name){
     	
     	//get name of the zip file without the extension
		$file_info = pathinfo($file_name);
		$filename = $file_info['basename'];//name including extension
		$extension = $file_info['extension'];//extension only
		
		if(!empty($_POST['ppt2lp']) && !in_array($extension,array('dll','exe')))
		{
			return 'oogie';
		}
		if(!empty($_POST['woogie']) && !in_array($extension,array('dll','exe')))
		{
			return 'woogie';
		}
		
		
		$file_base_name = str_replace('.'.$extension,'',$filename); //filename without its extension
	
		$zipFile = new pclZip($file_path);
		// Check the zip content (real size and file extension)
		$zipContentArray = $zipFile->listContent();
		$package_type='';
		$at_root = false;
		$manifest = '';

		//the following loop should be stopped as soon as we found the right imsmanifest.xml (how to recognize it?)
		if (is_array($zipContentArray) && count($zipContentArray)>0) {
            foreach($zipContentArray as $thisContent)
    		{
    			if ( preg_match('~.(php.*|phtml)$~i', $thisContent['filename']) )
    			{
    				//New behaviour: Don't do anything. These files will be removed in scorm::import_package
    			}
    			elseif(stristr($thisContent['filename'],'imsmanifest.xml')!==FALSE)
    			{
    				$manifest = $thisContent['filename']; //just the relative directory inside scorm/
    				$package_type = 'scorm';
    				break;//exit the foreach loop
    			}
    			elseif(preg_match('/aicc\//i',$thisContent['filename'])!=false)
    			{//if found an aicc directory... (!= false means it cannot be false (error) or 0 (no match))
    				$package_type='aicc';
    				//break;//don't exit the loop, because if we find an imsmanifest afterwards, we want it, not the AICC
    			}
    			else
    			{
    				$package_type = '';
    			}
    		}
        }
		return $package_type;
	}
    /**

     * Gets the previous resource in queue (url). Also initialises time values for this viewing

     * @return string URL to load into the viewer

     */

    function get_previous_index()

    {

		if($this->debug>0){error_log('New LP - In learnpath::get_previous_index()',0);}

    	$index = $this->index;

    	if(isset($this->ordered_items[$index-1])){

	    	$index --;

	    	while(isset($this->ordered_items[$index]) AND ($this->items[$this->ordered_items[$index]]->get_type() == 'dir' || $this->items[$this->ordered_items[$index]]->get_type() == 'dokeos_chapter'))

	    	{

	    		$index --;

	    		if($index < 0){

	    			return $this->index;

	    		}

	    	}

    	}else{

    		if($this->debug>2){error_log('New LP - get_previous_index() - there was no previous index available, reusing '.$index,0);}

    		//no previous item

    	}

    	return $index;

    }

    /**
     * Gets item_id for the next element
     * @return	integer	Previous item (DB) ID
     */
    function get_previous_item_id()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_previous_item_id()',0);}
    	$new_index = $this->get_previous_index();
    	return $this->ordered_items[$new_index];
    }

    /**
     * Gets the progress value from the progress_db attribute
     * @return	integer	Current progress value
     */
    function get_progress()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_progress()',0);}
    	if(!empty($this->progress_db))
    	{
    		return $this->progress_db;
    	}
    	return 0;
    }
    /**
     * Gets the progress value from the progress field in the database (allows use as abstract method)
     * @param	integer	Learnpath ID
     * @param	integer	User ID
     * @param	string	Mode of display ('%','abs' or 'both')
     * @param	string	Course database name (optional, defaults to '')
     * @param	boolean	Whether to return null if no record was found (true), or 0 (false) (optional, defaults to false)
     * @return	integer	Current progress value as found in the database
     */
	function get_db_progress($lp_id,$user_id,$mode='%', $course_db='', $sincere=false)
	{
		//if($this->debug>0){error_log('New LP - In learnpath::get_db_progress()',0);}
    	$table = Database::get_course_table('lp_view', $course_db);
    	$sql = "SELECT * FROM $table WHERE lp_id = $lp_id AND user_id = $user_id";
    	$res = api_sql_query($sql,__FILE__,__LINE__);
		$view_id = 0;
    	if(Database::num_rows($res)>0)
    	{
    		$row = Database::fetch_array($res);
    		$progress = $row['progress'];
    		$view_id = $row['id'];
    	}
    	else
    	{
    		if($sincere)
    		{
    			return null;
    		}
    	}
    	if(empty($progress))
    	{
    		$progress = '0';
    	}
    	if($mode == '%')
    	{
    			return $progress.'%';
    	}
    	else
    	{
    		//get the number of items completed and the number of items total
    		$tbl = Database::get_course_table('lp_item', $course_db);
    		$sql = "SELECT count(*) FROM $tbl WHERE lp_id = ".$lp_id." 
					AND item_type NOT IN('dokeos_chapter','chapter','dir')";
    		$res = api_sql_query($sql, __FILE__, __LINE__);
    		$row = Database::fetch_array($res);
    		$total = $row[0];
    		$tbl_item_view = Database::get_course_table('lp_item_view', $course_db);
    		$tbl_item = Database::get_course_table('lp_item', $course_db);
    		
    		//$sql = "SELECT count(distinct(lp_item_id)) FROM $tbl WHERE lp_view_id = ".$view_id." AND status IN ('passed','completed','succeeded')";
    		//trying as also counting browsed and failed items
    		$sql = "SELECT count(distinct(lp_item_id)) 
					FROM $tbl_item_view as item_view
					INNER JOIN $tbl_item as item
						ON item.id = item_view.lp_item_id
						AND item_type NOT IN('dokeos_chapter','chapter','dir')
					WHERE lp_view_id = ".$view_id." 
					AND status IN ('passed','completed','succeeded','browsed','failed')";
    		$res = api_sql_query($sql, __FILE__, __LINE__);
    		$row = Database::fetch_array($res);
    		$completed = $row[0];
    		if($mode == 'abs')
    		{
    			return $completed.'/'.$total;
    		}
    		elseif($mode == 'both')
    		{
    			if($progress<($completed/($total?$total:1)))
    			{
    				$progress = number_format(($completed/($total?$total:1))*100,0);
    			}
    			return $progress.'% ('.$completed.'/'.$total.')';
    		}
    	}
    	return $progress;
    }
	
	function get_mediaplayer()
	{
		global $_course;
		
		// Database table definition
		$tbl_lp_item	= Database::get_course_table('lp_item');
		
		// getting all the information about the item
		$sql = "SELECT * FROM " . $tbl_lp_item . " as lp WHERE lp.id = '" . $_SESSION['oLP']->current."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$row= mysql_fetch_assoc($result);
		$output='';		
		if (!empty($row['audio']))
		{
		// the mp3 player	
			$output = '<div id="container"><a href="http://www.macromedia.com/go/getflashplayer">Get the Flash Player</a> to see this player.</div>';
			$output .= '<script type="text/javascript" src="../inc/lib/mediaplayer/swfobject.js"></script>';
			$output .= '<script type="text/javascript">
						var s1 = new SWFObject("../inc/lib/mediaplayer/player.swf","ply","250","20","9","#FFFFFF");
						s1.addParam("allowscriptaccess","always");
							s1.addParam("flashvars","file='.api_get_path(WEB_COURSE_PATH).$_course['path'].'/document/audio/'.$row['audio'].'&image=preview.jpg&autostart=true");
						s1.write("container");
					</script>';
		}
		return $output;		
	}

	
	
    /**
     * Gets a progress bar for the learnpath by counting the number of items in it and the number of items
     * completed so far.
     * @param	string	Mode in which we want the values
     * @param	integer	Progress value to display (optional but mandatory if used in abstract context)
     * @param	string	Text to display near the progress value (optional but mandatory in abstract context)
     * @param	boolean true if it comes from a Diplay LP view
     * @return	string	HTML string containing the progress bar
     */
    function get_progress_bar($mode='',$percentage=-1,$text_add='',$from_lp=false)
    {
		//if($this->debug>0){error_log('New LP - In learnpath::get_progress_bar()',0);}
    	global $lp_theme_css;
    	
    	// Setting up the CSS path of the current style if exists   
    	if (!empty($lp_theme_css))
    	{    
	    	$css_path=api_get_path(WEB_CODE_PATH).'css/'.$lp_theme_css.'/images/';
    	}
    	else 
    	{
    		$css_path='../img/';	
    	}  
	    	
		//if($this->debug>0){error_log('New LP - In learnpath::get_progress_bar()',0);}
    	if(isset($this) && is_object($this) && ($percentage=='-1' OR $text_add==''))
    	{
    		list ($percentage, $text_add) = $this->get_progress_bar_text($mode);
    	}
    	$text = $percentage.$text_add;
    	
    	//Default progress bar config
    	// times that will be greater or shorter
    	$factor=1.2; 
    	if ($from_lp)
    		$progress_height='26';   
    	else
    		$progress_height='16';
    	$size = str_replace('%','',$percentage);
    	   	
    	$output = '' 
    	//.htmlentities(get_lang('ScormCompstatus'),ENT_QUOTES,'ISO-8859-1')."<br />"    	
	    .'<table border="0" cellpadding="0" cellspacing="0"><tr><td>'
	    .'<img id="progress_img_limit_left" src="'.$css_path.'bar_1.gif" width="1" height="'.$progress_height.'">'
	    .'<img id="progress_img_full" src="'.$css_path.'bar_1u.gif" width="'.$size*$factor.'px" height="'.$progress_height.'" id="full_portion">'
	    .'<img id="progress_img_limit_middle" src="'.$css_path.'bar_1m.gif" width="1" height="'.$progress_height.'">';
	    
	    if($percentage <= 98)
	    {	
	    	$output .= '<img id="progress_img_empty" src="'.$css_path.'bar_1r.gif" width="'.(100-$size)*$factor.'px" height="'.$progress_height.'" id="empty_portion">';
	    }
	    else
	    {
	    	$output .= '<img id="progress_img_empty" src="'.$css_path.'bar_1r.gif" width="0" height="'.$progress_height.'" id="empty_portion">';
	    }
	    
	    $output .= '<img id="progress_bar_img_limit_right" src="'.$css_path.'bar_1.gif" width="1" height="'.$progress_height.'"></td></tr></table>'
	    .'<div class="progresstext" id="progress_text">'.$text.'</div>';
		
					
	    return $output;
    }
    /**
     * Gets the progress bar info to display inside the progress bar. Also used by scorm_api.php
     * @param	string	Mode of display (can be '%' or 'abs').abs means we display a number of completed elements per total elements
     * //@param	integer	Additional steps to fake as completed
     * @return	list	Percentage or number and symbol (% or /xx)
     */
    function get_progress_bar_text($mode='',$add=0)
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_progress_bar_text()',0);}
    	if(empty($mode)){$mode = $this->progress_bar_mode;}
    	$total_items = $this->get_total_items_count_without_chapters();
    	if($this->debug>2){error_log('New LP - Total items available in this learnpath: '.$total_items,0);}
    	$i = $this->get_complete_items_count();
		if($this->debug>2){error_log('New LP - Items completed so far: '.$i,0);}
    	if($add != 0){
    		$i += $add;
			if($this->debug>2){error_log('New LP - Items completed so far (+modifier): '.$i,0);}
    	}
    	$text = '';
    	if($i>$total_items){
    		$i = $total_items;
    	}
    	if($mode == '%'){
    		if($total_items>0){
		    	$percentage = ((float)$i/(float)$total_items)*100;
    		}
    		else
    		{
    			$percentage = 0;
    		}
		    $percentage = number_format($percentage,0);
	    	$text = '%';
    	}elseif($mode == 'abs'){
	    	$percentage = $i;
    		$text =  '/'.$total_items;
    	}
    	return array($percentage,$text);
    }
    /**
     * Gets the progress bar mode
     * @return	string	The progress bar mode attribute
     */
     function get_progress_bar_mode()
     {
		if($this->debug>0){error_log('New LP - In learnpath::get_progress_bar_mode()',0);}
     	if(!empty($this->progress_bar_mode))
    	{
    		return $this->progress_bar_mode;
    	}else{
    		return '%';
    	}
     }
    /**
     * Gets the learnpath proximity (remote or local)
     * @return	string	Learnpath proximity
     */
    function get_proximity()
	{
		if($this->debug>0){error_log('New LP - In learnpath::get_proximity()',0);}
		if(!empty($this->proximity)){return $this->proximity;}else{return '';}
	}
	
	 /**
     * Gets the learnpath theme (remote or local)
     * @return	string	Learnpath theme
     */
    function get_theme()
	{
		if($this->debug>0){error_log('New LP - In learnpath::get_theme()',0);}
		if(!empty($this->theme)){return $this->theme;}else{return '';}
	}
	
	
	/**
     * Gets the learnpath image
     * @return	string	Web URL of the LP image
     */
    function get_preview_image()
	{
		if($this->debug>0){error_log('New LP - In learnpath::get_preview_image()',0);}
		if(!empty($this->preview_image)){return $this->preview_image;}else{return '';}
	}
	
	
	/**
     * Gets the learnpath author
     * @return	string	LP's author
     */
    function get_author()
	{
		if($this->debug>0){error_log('New LP - In learnpath::get_author()',0);}
		if(!empty($this->author)){return $this->author;}else{return '';}
	}
	
	/**
	 * Generate a new prerequisites string for a given item. If this item was a sco and
	 * its prerequisites were strings (instead of IDs), then transform those strings into
	 * IDs, knowing that SCORM IDs are kept in the "ref" field of the lp_item table.
	 * Prefix all item IDs that end-up in the prerequisites string by "ITEM_" to use the
	 * same rule as the scorm_export() method
	 * @param	integer		Item ID
	 * @return	string		Prerequisites string ready for the export as SCORM
	 */
	function get_scorm_prereq_string($item_id)
	{
		if($this->debug>0){error_log('New LP - In learnpath::get_scorm_prereq_string()',0);}
		if(!is_object($this->items[$item_id])){return false;}
		$oItem = $this->items[$item_id];
		$prereq = $oItem->get_prereq_string();
		if(empty($prereq))
		{
			return '';
		}
		if(preg_match('/^\d+$/',$prereq) && is_object($this->items[$prereq]))
		{	//if the prerequisite is a simple integer ID and this ID exists as an item ID,
			//then simply return it (with the ITEM_ prefix) 
			return 'ITEM_'.$prereq;
		}
		else
		{
			if(isset($this->refs_list[$prereq]))
			{
				//it's a simple string item from which the ID can be found in the refs list
				//so we can transform it directly to an ID for export
				return 'ITEM_'.$this->refs_list[$prereq];
			}
			else
			{
				//last case, if it's a complex form, then find all the IDs (SCORM strings)
				//and replace them, one by one, by the internal IDs (dokeos db)
				//TODO modify the '*' replacement to replace the multiplier in front of it
				//by a space as well
				$find    = array('&','|','~','=','<>','{','}','*','(',')');
				$replace = array(' ',' ',' ',' ',' ',' ',' ',' ',' ',' ');
				$prereq_mod = str_replace($find,$replace,$prereq);
				$ids = split(' ',$prereq_mod);
				foreach($ids as $id)
				{
					$id = trim($id);
					if(isset($this->refs_list[$id]))
					{
						$prereq = preg_replace('/[^a-zA-Z_0-9]('.$id.')[^a-zA-Z_0-9]/','ITEM_'.$this->refs_list[$id],$prereq);
					}
				}
				error_log('New LP - In learnpath::get_scorm_prereq_string(): returning modified string: '.$prereq,0);
				return $prereq;
			}
		}
	}
	/**
	 * Returns the XML DOM document's node
	 * @param	resource	Reference to a list of objects to search for the given ITEM_*
	 * @param	string		The identifier to look for
	 * @return	mixed		The reference to the element found with that identifier. False if not found
	 */
	 function get_scorm_xml_node(&$children,$id)
	 {
        for($i=0;$i<$children->length;$i++){
	        $item_temp = $children->item($i);
	        if ($item_temp -> nodeName == 'item')
	        {
	        	if($item_temp->getAttribute('identifier') == $id)
	        	{
	        		return $item_temp;
	        	}
	        }
	        $subchildren = $item_temp->childNodes;
	        if($subchildren->length>0)
	        {
	        	$val = $this->get_scorm_xml_node($subchildren,$id);
	        	if(is_object($val))
	        	{
	        		return $val;
	        	}
	        }
        }
        return false;
    }
	
	/**
     * Returns a usable array of stats related to the current learnpath and user
     * @return array	Well-formatted array containing status for the current learnpath
     */
    function get_stats()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_stats()',0);}
    	//TODO
    }
    /**
     * Static method. Can be re-implemented by children. Gives an array of statistics for
     * the given course (for all learnpaths and all users)
     * @param	string	Course code
     * @return array	Well-formatted array containing status for the course's learnpaths
     */
    function get_stats_course($course)
    {
		//if($this->debug>0){error_log('New LP - In learnpath::get_stats_course()',0);}
    	//TODO
    }
    /**
     * Static method. Can be re-implemented by children. Gives an array of statistics for
     * the given course and learnpath (for all users)
     * @param	string	Course code
     * @param	integer	Learnpath ID
     * @return array	Well-formatted array containing status for the specified learnpath
     */
    function get_stats_lp($course,$lp)
    {
		//if($this->debug>0){error_log('New LP - In learnpath::get_stats_lp()',0);}
    	//TODO
    }
    /**
     * Static method. Can be re-implemented by children. Gives an array of statistics for
     * the given course, learnpath and user.
     * @param	string	Course code
     * @param	integer	Learnpath ID
     * @param	integer	User ID
     * @return array	Well-formatted array containing status for the specified learnpath and user
     */
    function get_stats_lp_user($course,$lp,$user)
    {
		//if($this->debug>0){error_log('New LP - In learnpath::get_stats_lp_user()',0);}
    	//TODO
    }
    /**
     * Static method. Can be re-implemented by children. Gives an array of statistics for
     * the given course and learnpath (for all users)
     * @param	string	Course code
     * @param	integer	User ID
     * @return array	Well-formatted array containing status for the user's learnpaths
     */
    function get_stats_user($course,$user)
    {
		//if($this->debug>0){error_log('New LP - In learnpath::get_stats_user()',0);}
    	//TODO
    }
    /**
     * Gets the status list for all LP's items
   	 * @return	array	Array of [index] => [item ID => current status]
     */
    function get_items_status_list(){
		if($this->debug>0){error_log('New LP - In learnpath::get_items_status_list()',0);}
    	$list = array();
    	foreach($this->ordered_items as $item_id)
    	{
    		$list[]= array($item_id => $this->items[$item_id]->get_status());
    	}
    	return $list;
    }
	/**
	 * Return the number of interactions for the given learnpath Item View ID.
	 * This method can be used as static. 
	 * @param	integer	Item View ID
	 * @return	integer	Number of interactions
	 */
	function get_interactions_count_from_db($lp_iv_id=0){
		if(empty($lp_iv_id)){return -1;}
		$table = Database::get_course_table('lp_iv_interaction');
		$sql = "SELECT count(*) FROM $table WHERE lp_iv_id = $lp_iv_id";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$row = Database::fetch_array($res);
		$num = $row[0];
		return $num;
	}
	/**
	 * Return the interactions as an array for the given lp_iv_id.
	 * This method can be used as static.
	 * @param	integer	Learnpath Item View ID
	 * @return	array
	 * @todo 	Translate labels 
	 */
	function get_iv_interactions_array($lp_iv_id=0){
		$list = array();
		$table = Database::get_course_table('lp_iv_interaction');
		$sql = "SELECT * FROM $table WHERE lp_iv_id = $lp_iv_id ORDER BY order_id ASC";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$num = Database::num_rows($res);
		if($num>0){
			$list[] = array(
				"order_id"=>htmlentities(get_lang('Order')),
				"id"=>htmlentities(get_lang('InteractionID')),
				"type"=>htmlentities(get_lang('Type')),
				"time"=>htmlentities(get_lang('TimeFinished')),
				"correct_responses"=>htmlentities(get_lang('CorrectAnswers')),
				"student_response"=>htmlentities(get_lang('StudentResponse')),
				"result"=>htmlentities(get_lang('Result')),
				"latency"=>htmlentities(get_lang('LatencyTimeSpent')));
			while ($row = Database::fetch_array($res)){
				$list[] = array(
					"order_id"=>($row['order_id']+1),
					"id"=>urldecode($row['interaction_id']),//urldecode because they often have %2F or stuff like that
					"type"=>$row['interaction_type'],
					"time"=>$row['completion_time'],
					//"correct_responses"=>$row['correct_responses'],
					//hide correct responses from students
					"correct_responses"=>'',
					"student_response"=>$row['student_response'],
					"result"=>$row['result'],
					"latency"=>$row['latency']);
			}
		}
		return $list;	
	}
	/**
	 * Return the number of objectives for the given learnpath Item View ID.
	 * This method can be used as static. 
	 * @param	integer	Item View ID
	 * @return	integer	Number of objectives
	 */
	function get_objectives_count_from_db($lp_iv_id=0){
		if(empty($lp_iv_id)){return -1;}
		$table = Database::get_course_table('lp_iv_objective');
		$sql = "SELECT count(*) FROM $table WHERE lp_iv_id = $lp_iv_id";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$row = Database::fetch_array($res);
		$num = $row[0];
		return $num;
	}
	/**
	 * Return the objectives as an array for the given lp_iv_id.
	 * This method can be used as static.
	 * @param	integer	Learnpath Item View ID
	 * @return	array
	 * @todo 	Translate labels 
	 */
	function get_iv_objectives_array($lp_iv_id=0){
		$list = array();
		$table = Database::get_course_table('lp_iv_objective');
		$sql = "SELECT * FROM $table WHERE lp_iv_id = $lp_iv_id ORDER BY order_id ASC";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$num = Database::num_rows($res);
		if($num>0){
			$list[] = array(
				"order_id"=>htmlentities(get_lang('Order')),
				"objective_id"=>htmlentities(get_lang('ObjectiveID')),
				"score_raw"=>htmlentities(get_lang('ObjectiveRawScore')),
				"score_max"=>htmlentities(get_lang('ObjectiveMaxScore')),
				"score_min"=>htmlentities(get_lang('ObjectiveMinScore')),
				"status"=>htmlentities(get_lang('ObjectiveStatus')));
			while ($row = Database::fetch_array($res)){
				$list[] = array(
					"order_id"=>($row['order_id']+1),
					"objective_id"=>urldecode($row['objective_id']),//urldecode because they often have %2F or stuff like that
					"score_raw"=>$row['score_raw'],
					"score_max"=>$row['score_max'],
					"score_min"=>$row['score_min'],
					"status"=>$row['status']);
			}
		}
		return $list;	
	}

    /**
     * Generate and return the table of contents for this learnpath. The (flat) table returned can be
     * used by get_html_toc() to be ready to display
     * @return	array	TOC as a table with 4 elements per row: title, link, status and level
     */
    function get_toc()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_toc()',0);}
    	$toc = array();
    	//echo "<pre>".print_r($this->items,true)."</pre>";
    	foreach($this->ordered_items as $item_id)
    	{
    		if($this->debug>2){error_log('New LP - learnpath::get_toc(): getting info for item '.$item_id,0);}
			//TODO change this link generation and use new function instead
    		$toc[] = array(
				'id'=>$item_id,
				'title'=>$this->items[$item_id]->get_title(),
				//'link'=>get_addedresource_link_in_learnpath('document',$item_id,1),
				'status'=>$this->items[$item_id]->get_status(),
				'level'=>$this->items[$item_id]->get_level(),
				'type' =>$this->items[$item_id]->get_type(),
				'description'=>$this->items[$item_id]->get_description(),
				'path'=>$this->items[$item_id]->get_path(),
				);
    	}
    	if($this->debug>2){error_log('New LP - In learnpath::get_toc() - TOC array: '.print_r($toc,true),0);}
    	return $toc;
    }
    /**
     * Gets the learning path type
     * @param	boolean		Return the name? If false, return the ID. Default is false.
     * @return	mixed		Type ID or name, depending on the parameter
     */
    function get_type($get_name = false)
    {
		$res = false;
		if($this->debug>0){error_log('New LP - In learnpath::get_type()',0);}
    	if(!empty($this->type))
    	{
    		if($get_name)
    		{
    			//get it from the lp_type table in main db
    		}else{
    			$res = $this->type;
    		}
    	}
    	if($this->debug>2){error_log('New LP - In learnpath::get_type() - Returning '.($res==false?'false':$res),0);}
		return $res;
	}
    /**
     * Gets the learning path type as static method
     * @param	boolean		Return the name? If false, return the ID. Default is false.
     * @return	mixed		Type ID or name, depending on the parameter
     */
    function get_type_static($lp_id=0)
    {
    	$tbl_lp = Database::get_course_table('lp');
		$sql = "SELECT lp_type FROM $tbl_lp WHERE id = '".$lp_id."'";
		$res = api_sql_query($sql, __FILE__, __LINE__); 
		if($res===false){ return null;}
		if(Database::num_rows($res)<=0){return null;}
		$row = Database::fetch_array($res);
		return $row['lp_type'];
	}

    /**
     * Gets a flat list of item IDs ordered for display (level by level ordered by order_display)
     * This method can be used as abstract and is recursive
     * @param	integer	Learnpath ID
     * @param	integer	Parent ID of the items to look for
     * @return	mixed	Ordered list of item IDs or false on error
     */
    function get_flat_ordered_items_list($lp,$parent=0)
    {
		//if($this->debug>0){error_log('New LP - In learnpath::get_flat_ordered_items_list('.$lp.','.$parent.')',0);}
    	$list = array();
    	if(empty($lp)){return false;}
    	$tbl_lp_item = Database::get_course_table('lp_item');
    	$sql = "SELECT * FROM $tbl_lp_item WHERE lp_id = $lp AND parent_item_id = $parent ORDER BY display_order";
    	$res = api_sql_query($sql,__FILE__,__LINE__);
		while($row = Database::fetch_array($res)){
    		$sublist = learnpath::get_flat_ordered_items_list($lp,$row['id']);
    		$list[] = $row['id'];
    		foreach($sublist as $item){
    			$list[] = $item;
    		}
		}
    	return $list;
    }
    /**
     * Uses the table generated by get_toc() and returns an HTML-formatted string ready to display
     * @return	string	HTML TOC ready to display
     */
    /*function get_html_toc()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_html_toc()',0);}
    	$list = $this->get_toc();
    	//echo $this->current;
    	//$parent = $this->items[$this->current]->get_parent();
    	//if(empty($parent)){$parent = $this->ordered_items[$this->items[$this->current]->get_previous_index()];}
    	$html = '<div class="inner_lp_toc">'."\n" ;
    	//		" onchange=\"javascript:document.getElementById('toc_$parent').focus();\">\n";
		require_once('resourcelinker.inc.php');
		
		//temp variables
		$mycurrentitemid = $this->get_current_item_id();
		
    	foreach($list as $item)
    	{
    		if($this->debug>2){error_log('New LP - learnpath::get_html_toc(): using item '.$item['id'],0);}
    		//TODO complete this
    		$icon_name = array('not attempted' => '../img/notattempted.gif',
    							'incomplete'   => '../img/incomplete.gif',
    							'failed'       => '../img/failed.gif',
    							'completed'    => '../img/completed.gif',
    							'passed'	   => '../img/passed.gif',
    							'succeeded'    => '../img/succeeded.gif',
    							'browsed'      => '../img/completed.gif');
    		
    		$style = 'scorm_item';
    		if($item['id'] == $this->current){
    			$style = 'scorm_item_highlight';
    		}
    		//the anchor will let us center the TOC on the currently viewed item &^D
    		$html .= '<a name="atoc_'.$item['id'].'" /><div class="'.$style.'" style="padding-left: '.($item['level']/2).'em; padding-right:'.($item['level']/2).'em" id="toc_'.$item['id'].'" >' .
    				'<img id="toc_img_'.$item['id'].'" class="scorm_status_img" src="'.$icon_name[$item['status']].'" alt="'.substr($item['status'],0,1).'" />';
    		
    		//$title = htmlspecialchars($item['title'],ENT_QUOTES,$this->encoding);
    		$title = $item['title'];
    		if(empty($title)){
    			$title = rl_get_resource_name(api_get_course_id(),$this->get_id(),$item['id']);
    			$title = htmlspecialchars($title,ENT_QUOTES,$this->encoding);
    		}
    		if(empty($title))$title = '-';
    		
    		if($item['type']!='dokeos_chapter' and $item['type']!='dir'){
					//$html .= "<a href='lp_controller.php?".api_get_cidReq()."&action=content&lp_id=".$this->get_id()."&item_id=".$item['id']."' target='lp_content_frame_name'>".$title."</a>" ;
					$url = $this->get_link('http',$item['id']);
					//$html .= '<a href="'.$url.'" target="content_name" onclick="top.load_item('.$item['id'].',\''.$url.'\');">'.$title.'</a>' ;
					//$html .= '<a href="" onclick="top.load_item('.$item['id'].',\''.$url.'\');return false;">'.$title.'</a>' ;
					$html .= '<a href="" onclick="dokeos_xajax_handler.switch_item(' .
							$mycurrentitemid.',' .
							$item['id'].');' .
							'return false;" >'.$title.'</a>' ;
    		}else{
    				$html .= $title;
    		}
    		$html .= "</div>\n";
    	}
    	$html .= "</div>\n";
    	return $html;
    }*/
    
    
    /**
     * Uses the table generated by get_toc() and returns an HTML-formatted string ready to display
     * @return	string	HTML TOC ready to display
     */
    function get_html_toc()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_html_toc()',0);}
    	$list = $this->get_toc();
        $mych = api_get_setting('platform_charset'); 
    	//echo $this->current;
    	//$parent = $this->items[$this->current]->get_parent();
    	//if(empty($parent)){$parent = $this->ordered_items[$this->items[$this->current]->get_previous_index()];}
    	$html= '<div class="scorm_title"><div class="scorm_title_text">'.mb_convert_encoding($this->get_name(),$this->encoding,$mych).'</div></div>';
    	
    	// build, display
    	if(api_is_allowed_to_edit())
    	{
    		$html	.='<div class="actions_lp">';
    		//the icon it was removed in display (build)
    		//$html 	.= "<a href='lp_controller.php?".api_get_cidreq()."&amp;action=build&amp;lp_id=".$this->lp_id."' target='_parent'>".Display::return_icon('learnpath_build.gif', get_lang('Build')).' '.mb_convert_encoding(get_lang('Build'),$this->encoding,$mych)."</a>";
    		$html 	.= "<a href='lp_controller.php?".api_get_cidreq()."&amp;action=build&amp;lp_id=".$this->lp_id."' target='_parent'>".mb_convert_encoding(get_lang('Build'),$this->encoding,$mych)."</a>";
    		//the icon it was removed in display (organize)
    		//$html 	.= "<a href='lp_controller.php?".api_get_cidreq()."&amp;action=admin_view&amp;lp_id=".$this->lp_id."' target='_parent'>".Display::return_icon('learnpath_organize.gif', get_lang('BasicOverview')).' '.mb_convert_encoding(get_lang('BasicOverview'),$this->encoding,$mych)."</a>";
    		$html 	.= "<a href='lp_controller.php?".api_get_cidreq()."&amp;action=admin_view&amp;lp_id=".$this->lp_id."' target='_parent'>".mb_convert_encoding(get_lang('BasicOverview'),$this->encoding,$mych)."</a>";
    		//the icon it was removed in display (display)
    		//$html 	.= '<span>'.Display::return_icon('learnpath_view.gif', get_lang("Display")).' '.mb_convert_encoding(get_lang("Display"),$this->encoding,$mych).'</span>';
    		$html 	.= '<span>'.mb_convert_encoding(get_lang("Display"),$this->encoding,$mych).'</span>';
    		$html 	.= '</div>';
			unset($mych);
    	}
    	
    	$html.= '<div id="inner_lp_toc" class="inner_lp_toc">'."\n" ;
    	//$html.= '<div class="scorm_title"><div class="scorm_title_text">'.mb_convert_encoding($this->get_name(),$this->encoding,$mych).'</div></div>';
    	
    
    	//		" onchange=\"javascript:document.getElementById('toc_$parent').focus();\">\n";
		require_once('resourcelinker.inc.php');
		
		//temp variables
		$mycurrentitemid = $this->get_current_item_id();
		$color_counter=0;
		$i=0;
    	foreach($list as $item)
    	{
    		if($this->debug>2){error_log('New LP - learnpath::get_html_toc(): using item '.$item['id'],0);}
    		//TODO complete this
    		$icon_name = array('not attempted' => '../img/notattempted.gif',
    							'incomplete'   => '../img/incomplete.gif',
    							'failed'       => '../img/failed.gif',
    							'completed'    => '../img/completed.gif',
    							'passed'	   => '../img/passed.gif',
    							'succeeded'    => '../img/succeeded.gif',
    							'browsed'      => '../img/completed.gif');
    		
    		$style = 'scorm_item';    		
    		$scorm_color_background='scorm_item';
    		$style_item ='scorm_item';
    		$current=false;
    		
    		if($item['id'] == $this->current)
    		{
    			$style = 'scorm_item_highlight';
    			$scorm_color_background ='scorm_item_highlight';
    		}	
    		else 
    		
	    		if ($color_counter%2==0)
		    	{
		    		$scorm_color_background='scorm_item_1'; 			
		    	}
		    	else
		    	{
		    		$scorm_color_background='scorm_item_2'; 
		    	}
	    	
			if ($scorm_color_background!='')
			{
				$html .= '<div id="toc_'.$item['id'].'" class="'.$scorm_color_background.'">';
			}
			
    		//the anchor will let us center the TOC on the currently viewed item &^D
    		if($item['type']!='dokeos_module' AND $item['type']!='dokeos_chapter')
    		{       		
	    		$html .= '<a name="atoc_'.$item['id'].'" />';								
				$html .= '<div class="'.$style_item.'" style="padding-left: '.($item['level']*1.5).'em; padding-right:'.($item['level']/2).'em"             title="'.$item['description'].'" >';
	    	}    		
    		else
    		{  
    			$html .= '<div class="'.$style_item.'" style="padding-left: '.($item['level']*2).'em; padding-right:'.($item['level']*1.5).'em"             title="'.$item['description'].'" >';
   			}
    			
			$title=$item['title'];		
    			
    		if(empty($title))
    		{
    			$title = rl_get_resource_name(api_get_course_id(),$this->get_id(),$item['id']);    			
    		}
    		
    		$title = utf8_decode(html_entity_decode($title,ENT_QUOTES,$this->encoding));	
    	
    		if($item['type']!='dokeos_chapter' and $item['type']!='dir' AND $item['type']!='dokeos_module')
    		{
				//$html .= "<a href='lp_controller.php?".api_get_cidreq()."&action=content&lp_id=".$this->get_id()."&item_id=".$item['id']."' target='lp_content_frame_name'>".$title."</a>" ;
				$url = $this->get_link('http',$item['id']);
				//$html .= '<a href="'.$url.'" target="content_name" onclick="top.load_item('.$item['id'].',\''.$url.'\');">'.$title.'</a>' ;
				//$html .= '<a href="" onclick="top.load_item('.$item['id'].',\''.$url.'\');return false;">'.$title.'</a>' ;
				
				//<img align="absbottom" width="13" height="13" src="../img/lp_document.png">&nbsp;background:#aaa;							
				$html .= '<a href="" onclick="dokeos_xajax_handler.switch_item(' .
					$mycurrentitemid.',' .
					$item['id'].');' .
					'return false;" >'.stripslashes($title).'</a>' ;
    		}
    		elseif($item['type']=='dokeos_module' || $item['type']=='dokeos_chapter')
    		{
    			$html .= "<img align='absbottom' width='13' height='13' src='../img/lp_dokeos_module.png'>&nbsp;".stripslashes($title);
    		}    		
    		elseif($item['type']=='dir')
    		{
    			$html .= stripslashes($title);
    		}   		
    		
    		$tbl_track_e_exercises = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
			$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
			$user_id = api_get_user_id();
			$course_id = api_get_course_id();
			$sql = "SELECT path  FROM $tbl_track_e_exercises, $tbl_lp_item
					WHERE path =   '".$item['path']."' AND exe_user_id =  '$user_id' AND exe_cours_id = '$course_id' AND path = exe_exo_id AND status <> 'incomplete'";
			$result = api_sql_query($sql,__FILE__,__LINE__);
			$count = Database::num_rows($result);			
			if ($item['type']=='quiz') {
				if ($item['status']=='completed') {
				$html .= "&nbsp;<img id='toc_img_".$item['id']."' src='".$icon_name[$item['status']]."' alt='".substr($item['status'],0,1)."' width='12' height='12' />";
				}
			} else {				
					if ($item['type']!='dokeos_chapter' && $item['type']!='dokeos_module' && $item['type']!='dir') {	
						$html .= "&nbsp;<img id='toc_img_".$item['id']."' src='".$icon_name[$item['status']]."' alt='".substr($item['status'],0,1)."' width='12' height='12' />";
					}
				
			}		
    		 		    		 		
    		$html .= "</div>";
    		
    		if ($scorm_color_background!='')
			{
				$html .= '</div>';
			}
			
    		
			
			
    	$color_counter++;
    	}
    	$html .= "</div>\n";
    	return $html;
    }
    /**
     * Gets the learnpath maker name - generally the editor's name
     * @return	string	Learnpath maker name
     */
    function get_maker()
	{
		if($this->debug>0){error_log('New LP - In learnpath::get_maker()',0);}
		if(!empty($this->maker)){return $this->maker;}else{return '';}
	}    
    /**
     * Gets the user-friendly message stored in $this->message
     * @return	string	Message
     */
    function get_message(){

		if($this->debug>0){error_log('New LP - In learnpath::get_message()',0);}
    	return $this->message;
    }
    /**
     * Gets the learnpath name/title
     * @return	string	Learnpath name/title
     */
    function get_name()
	{
		if($this->debug>0){error_log('New LP - In learnpath::get_name()',0);}
		if(!empty($this->name)){return $this->name;}else{return 'N/A';}
	}
    /**
     * Gets a link to the resource from the present location, depending on item ID.
     * @param	string	Type of link expected
     * @param	integer	Learnpath item ID
     * @return	string	Link to the lp_item resource
     */
    function get_link($type='http',$item_id=null)
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_link('.$type.','.$item_id.')',0);}
    	if(empty($item_id))
    	{
    		if($this->debug>2){error_log('New LP - In learnpath::get_link() - no item id given in learnpath::get_link(), using current: '.$this->get_current_item_id(),0);}
    		$item_id = $this->get_current_item_id();
    	}

    	if(empty($item_id)){
    		if($this->debug>2){error_log('New LP - In learnpath::get_link() - no current item id found in learnpath object',0);}
    		//still empty, this means there was no item_id given and we are not in an object context or
    		//the object property is empty, return empty link
    		$item_id = $this->first();
    		return '';
    	}

    	$file = '';
		$lp_table = Database::get_course_table(TABLE_LP_MAIN);
		$lp_item_table = Database::get_course_table(TABLE_LP_ITEM);
		$lp_item_view_table = Database::get_course_table(TABLE_LP_ITEM_VIEW);
    	$sel = "SELECT l.lp_type as ltype, l.path as lpath, li.item_type as litype, li.path as lipath, li.parameters as liparams " .
    			"FROM $lp_table l, $lp_item_table li WHERE li.id = $item_id AND li.lp_id = l.id";
    	if($this->debug>2){error_log('New LP - In learnpath::get_link() - selecting item '.$sel,0);}
    	$res = api_sql_query($sel, __FILE__, __LINE__);
    	if(Database::num_rows($res)>0)
    	{
    		$row = Database::fetch_array($res);
    		//var_dump($row);
    		$lp_type = $row['ltype'];
    		$lp_path = $row['lpath'];
    		$lp_item_type = $row['litype'];
    		$lp_item_path = $row['lipath'];
    		$lp_item_params = $row['liparams'];
    		if(empty($lp_item_params) && strpos($lp_item_path,'?')!==false)
    		{
    			list($lp_item_path,$lp_item_params) = explode('?',$lp_item_path);
    		}
    		//$lp_item_params = '?'.$lp_item_params;
    		
    		//add ? if none - left commented to give freedom to scorm implementation
    		//if(substr($lp_item_params,0,1)!='?'){
    		//	$lp_item_params = '?'.$lp_item_params;
    		//}
    		$sys_course_path = api_get_path(SYS_COURSE_PATH).api_get_course_path();
    		if($type == 'http'){
    			$course_path = api_get_path(WEB_COURSE_PATH).api_get_course_path(); //web path
    		}else{
    			$course_path = $sys_course_path; //system path
    		}
    		//now go through the specific cases to get the end of the path
    		switch($lp_type){
    			case 1:
    				if($lp_item_type == 'dokeos_chapter'){
    					$file = 'lp_content.php?type=dir';
    				}else{
	    				require_once('resourcelinker.inc.php');
    					$file = rl_get_resource_link_for_learnpath(api_get_course_id(),$this->get_id(),$item_id);
    					
    					// check how much attempts of a exercise exits in lp    						    					    					    			
	    				$lp_item_id = $this->get_current_item_id();
	    				$lp_view_id = $this->get_view_id();
	    				$prevent_reinit = $this->items[$this->current]->prevent_reinit;	    				 	    			
	    				$list = $this->get_toc();
						$type_quiz = false;
						
						foreach($list as $toc) {
							if ($toc['id'] == $lp_item_id && ($toc['type']=='quiz') ) {
								$type_quiz = true;		
							}	
						}
	    					    					    				
	    				if ($type_quiz) {
	    					$lp_item_id = Database::escape_string($lp_item_id);
	    					$lp_view_id = Database::escape_string($lp_view_id);
	    					$sql = "SELECT count(*) FROM $lp_item_view_table WHERE lp_item_id='".(int)$lp_item_id."' AND lp_view_id ='".(int)$lp_view_id."' AND status='completed'";
	    					$result = api_sql_query($sql,__FILE__,__LINE__);
	    					$row_count = Database::fetch_row($result);
	    					$count_item_view = (int)$row_count[0];	    					
	    					$not_multiple_attempt = 0;
	    					if ($prevent_reinit === 1 && $count_item_view > 0) {
	    						$not_multiple_attempt = 1;	    						
	    					}
	    					$file .= '&not_multiple_attempt='.$not_multiple_attempt;	    						    						    					
	    				}
    					    					    					    					    					
    					$tmp_array=explode("/",$file);
    					$document_name=$tmp_array[count($tmp_array)-1];
    					if(strpos($document_name,'_DELETED_')){
    						$file = 'blank.php?error=document_deleted';
    					}
    				}
    				break;
    			case 2:
	   				if($this->debug>2){error_log('New LP - In learnpath::get_link() '.__LINE__.' - Item type: '.$lp_item_type,0);}
    				if($lp_item_type!='dir'){
    					//Quite complex here:
    					//we want to make sure 'http://' (and similar) links can 
    					//be loaded as is (withouth the Dokeos path in front) but
    					//some contents use this form: resource.htm?resource=http://blablabla
    					//which means we have to find a protocol at the path's start, otherwise
    					//it should not be considered as an external URL
    					
    					//if($this->prerequisites_match($item_id)){
		    				if(preg_match('#^[a-zA-Z]{2,5}://#',$lp_item_path)!=0){
		    					if($this->debug>2){error_log('New LP - In learnpath::get_link() '.__LINE__.' - Found match for protocol in '.$lp_item_path,0);}
		    					//distant url, return as is
		    					$file = $lp_item_path;
		    				}else{
		    					if($this->debug>2){error_log('New LP - In learnpath::get_link() '.__LINE__.' - No starting protocol in '.$lp_item_path,0);}
		    					//prevent getting untranslatable urls
		    					$lp_item_path = preg_replace('/%2F/','/',$lp_item_path);
		    					$lp_item_path = preg_replace('/%3A/',':',$lp_item_path);
			    				//prepare the path
			    				$file = $course_path.'/scorm/'.$lp_path.'/'.$lp_item_path;
			    				//TODO fix this for urls with protocol header
			    				$file = str_replace('//','/',$file);
			    				$file = str_replace(':/','://',$file);
								if(substr($lp_path,-1)=='/')
								{
									$lp_path = substr($lp_path,0,-1);
								}

			    				if(!is_file(realpath($sys_course_path.'/scorm/'.$lp_path.'/'.$lp_item_path)))
			    				{//if file not found
			    					$decoded = html_entity_decode($lp_item_path);
			    					list($decoded) = explode('?',$decoded);
			    					if(!is_file(realpath($sys_course_path.'/scorm/'.$lp_path.'/'.$decoded)))
			    					{
			    						require_once('resourcelinker.inc.php');
				    					$file = rl_get_resource_link_for_learnpath(api_get_course_id(),$this->get_id(),$item_id);
				    					$tmp_array=explode("/",$file);
				    					$document_name=$tmp_array[count($tmp_array)-1];
				    					if(strpos($document_name,'_DELETED_')){
				    						$file = 'blank.php?error=document_deleted';
				    					}

			    					}
			    					else
			    					{
			    						$file = $course_path.'/scorm/'.$lp_path.'/'.$decoded;
			    					}
			    				}
		    				}
    					//}else{
    						//prerequisites did not match
    						//$file = 'blank.php';
    					//}
    					//We want to use parameters if they were defined in the imsmanifest
    					if($file!='blank.php')
    					{
    						$file.= (strstr($file,'?')===false?'?':'').$lp_item_params;
    					}
    				}else{
    					$file = 'lp_content.php?type=dir';
    				}
    				break;
    			case 3:
    				if($this->debug>2){error_log('New LP - In learnpath::get_link() '.__LINE__.' - Item type: '.$lp_item_type,0);}
    				//formatting AICC HACP append URL
    				$aicc_append = '?aicc_sid='.urlencode(session_id()).'&aicc_url='.urlencode(api_get_path(WEB_CODE_PATH).'newscorm/aicc_hacp.php').'&';
    				if($lp_item_type!='dir'){
    					//Quite complex here:
    					//we want to make sure 'http://' (and similar) links can 
    					//be loaded as is (withouth the Dokeos path in front) but
    					//some contents use this form: resource.htm?resource=http://blablabla
    					//which means we have to find a protocol at the path's start, otherwise
    					//it should not be considered as an external URL
    					
	    				if(preg_match('#^[a-zA-Z]{2,5}://#',$lp_item_path)!=0){
	    					if($this->debug>2){error_log('New LP - In learnpath::get_link() '.__LINE__.' - Found match for protocol in '.$lp_item_path,0);}
	    					//distant url, return as is
	    					$file = $lp_item_path;
	    					/*
	    					if(stristr($file,'<servername>')!==false){
	    						$file = str_replace('<servername>',$course_path.'/scorm/'.$lp_path.'/',$lp_item_path);
	    					}
	    					*/
	    					$file .= $aicc_append;
	    				}else{
	    					if($this->debug>2){error_log('New LP - In learnpath::get_link() '.__LINE__.' - No starting protocol in '.$lp_item_path,0);}
	    					//prevent getting untranslatable urls
	    					$lp_item_path = preg_replace('/%2F/','/',$lp_item_path);
	    					$lp_item_path = preg_replace('/%3A/',':',$lp_item_path);
		    				//prepare the path - lp_path might be unusable because it includes the "aicc" subdir name
		    				$file = $course_path.'/scorm/'.$lp_path.'/'.$lp_item_path;
		    				//TODO fix this for urls with protocol header
		    				$file = str_replace('//','/',$file);
		    				$file = str_replace(':/','://',$file);
		    				$file .= $aicc_append;
	    				}
    				}else{
    					$file = 'lp_content.php?type=dir';
    				}
    				break;
    			case 4:
    				break;
    			default:
    				break;	
    		}
    	}
    	if($this->debug>2){error_log('New LP - In learnpath::get_link() - returning "'.$file.'" from get_link',0);}
    	return $file;
    }

    /**
     * Gets the latest usable view or generate a new one
     * @param	integer	Optional attempt number. If none given, takes the highest from the lp_view table
     * @return	integer	DB lp_view id
     */
    function get_view($attempt_num=0)
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_view()',0);}
    	$search = '';
    	//use $attempt_num to enable multi-views management (disabled so far)
    	if($attempt_num != 0 AND intval(strval($attempt_num)) == $attempt_num)
    	{
    		$search = 'AND view_count = '.$attempt_num;
    	}
    	//when missing $attempt_num, search for a unique lp_view record for this lp and user
    	$lp_view_table = Database::get_course_table('lp_view');
    	$sql = "SELECT id, view_count FROM $lp_view_table " .
    			"WHERE lp_id = ".$this->get_id()." " .
    			"AND user_id = ".$this->get_user_id()." " .
    			$search .
    			" ORDER BY view_count DESC";
    	$res = api_sql_query($sql, __FILE__, __LINE__);
    	if(Database::num_rows($res)>0)
    	{
    		$row = Database::fetch_array($res);
    		$this->lp_view_id = $row['id'];
    	}else{
    		//no database record, create one
    		$sql = "INSERT INTO $lp_view_table(lp_id,user_id,view_count)" .
    				"VALUES (".$this->get_id().",".$this->get_user_id().",1)";
    		$res = api_sql_query($sql, __FILE__, __LINE__);
    		$id = Database::get_last_insert_id();
    		$this->lp_view_id = $id;
    	}
    	return $this->lp_view_id;
    }

    /**
     * Gets the current view id
     * @return	integer	View ID (from lp_view)
     */
    function get_view_id()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_view_id()',0);}
       	if(!empty($this->lp_view_id))
    	{
    		return $this->lp_view_id;
    	}else{
    		return 0;
    	}
    }

    /**
     * Gets the update queue
     * @return	array	Array containing IDs of items to be updated by JavaScript
     */
    function get_update_queue()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_update_queue()',0);}
    	return $this->update_queue;
    }
    /**
     * Gets the user ID
     * @return	integer	User ID
     */
    function get_user_id()
    {
		if($this->debug>0){error_log('New LP - In learnpath::get_user_id()',0);}
    	if(!empty($this->user_id))
    	{
    		return $this->user_id;
    	}else{
    		return false;
    	}
    }
    /**
     * Checks if any of the items has an audio element attached
     * @return  bool    True or false
     */
    function has_audio() {
		if($this->debug>1){error_log('New LP - In learnpath::has_audio()',0);}
        $has = false;
    	foreach ($this->items as $i=>$item) {
    		if (!empty($this->items[$i]->audio)) { 
                $has = true;
                break;
            }
    	}
        return $has;
    }
    /**
     * Logs a message into a file
     * @param	string 	Message to log
     * @return	boolean	True on success, false on error or if msg empty
     */
    function log($msg)
    {
		if($this->debug>0){error_log('New LP - In learnpath::log()',0);}
    	//TODO
    	$this->error .= $msg."\n";
    	return true;
    }
    /**
     * Moves an item up and down at its level
     * @param	integer	Item to move up and down
     * @param	string	Direction 'up' or 'down'
     * @return	integer	New display order, or false on error
     */
    function move_item($id, $direction){
		if($this->debug>0){error_log('New LP - In learnpath::move_item('.$id.','.$direction.')',0);}
    	if(empty($id) or empty($direction)){return false;}
    	$tbl_lp_item = Database::get_course_table('lp_item');
		$sql_sel = "
			SELECT *
			FROM " . $tbl_lp_item . "
			WHERE id = " . $id;
    	$res_sel = api_sql_query($sql_sel,__FILE__,__LINE__);
    	//check if elem exists
    	if(Database::num_rows($res_sel)<1){return false;}
    	//gather data
    	$row = Database::fetch_array($res_sel);
    	$previous = $row['previous_item_id'];
    	$next = $row['next_item_id'];
    	$display = $row['display_order'];
    	$parent = $row['parent_item_id'];
    	$lp = $row['lp_id'];
    	//update the item (switch with previous/next one)
    	switch($direction)
		{
    		case 'up':
    			if($this->debug>2){error_log('Movement up detected',0);}
    			if($display <= 1){/*do nothing*/}
    			else{
			     	$sql_sel2 = "SELECT * 
						FROM $tbl_lp_item 
						WHERE id = $previous";

					if($this->debug>2){error_log('Selecting previous: '.$sql_sel2,0);}
			    	$res_sel2 = api_sql_query($sql_sel2,__FILE__,__LINE__);
			    	if(Database::num_rows($res_sel2)<1){$previous_previous = 0;}
			    	//gather data
			    	$row2 = Database::fetch_array($res_sel2);
			    	$previous_previous = $row2['previous_item_id'];
			 		//update previous_previous item (switch "next" with current)
			 		if($previous_previous != 0)
			 		{
				    	$sql_upd2 = "UPDATE $tbl_lp_item SET next_item_id = $id WHERE id = $previous_previous";
				    	if($this->debug>2){error_log($sql_upd2,0);}
				    	$res_upd2 = api_sql_query($sql_upd2, __FILE__, __LINE__);
			 		}
				 	//update previous item (switch with current)
			    	if($previous != 0)
			    	{
				    	$sql_upd2 = "UPDATE $tbl_lp_item SET next_item_id = $next, previous_item_id = $id, display_order = display_order +1 WHERE id = $previous";
				    	if($this->debug>2){error_log($sql_upd2,0);}
				    	$res_upd2 = api_sql_query($sql_upd2, __FILE__, __LINE__);
			    	}

			    	//update current item (switch with previous)
			    	if($id != 0){
				    	$sql_upd2 = "UPDATE $tbl_lp_item SET next_item_id = $previous, previous_item_id = $previous_previous, display_order = display_order-1 WHERE id = $id";
				    	if($this->debug>2){error_log($sql_upd2,0);}
				    	$res_upd2 = api_sql_query($sql_upd2, __FILE__, __LINE__);
			    	}
			    	//update next item (new previous item)
			    	if($next != 0){
				    	$sql_upd2 = "UPDATE $tbl_lp_item SET previous_item_id = $previous WHERE id = $next";
				    	if($this->debug>2){error_log($sql_upd2,0);}
				    	$res_upd2 = api_sql_query($sql_upd2, __FILE__, __LINE__);
			    	}
			    	$display = $display-1;    				
    			}
    			break;

    		case 'down':
    			if($this->debug>2){error_log('Movement down detected',0);}
    			if($next == 0){/*do nothing*/}
    			else{
			     	$sql_sel2 = "SELECT * FROM $tbl_lp_item WHERE id = $next";
					if($this->debug>2){error_log('Selecting next: '.$sql_sel2,0);}
			    	$res_sel2 = api_sql_query($sql_sel2,__FILE__,__LINE__);
			    	if(Database::num_rows($res_sel2)<1){$next_next = 0;}
			    	//gather data
			    	$row2 = Database::fetch_array($res_sel2);
			    	$next_next = $row2['next_item_id'];
    				//update previous item (switch with current)
					if($previous != 0)
					{
				    	$sql_upd2 = "UPDATE $tbl_lp_item SET next_item_id = $next WHERE id = $previous";
				    	$res_upd2 = api_sql_query($sql_upd2, __FILE__, __LINE__);
					}
				    //update current item (switch with previous)
			    	if($id != 0)
			    	{
				    	$sql_upd2 = "UPDATE $tbl_lp_item SET previous_item_id = $next, next_item_id = $next_next, display_order = display_order+1 WHERE id = $id";
				    	$res_upd2 = api_sql_query($sql_upd2, __FILE__, __LINE__);
			    	}

			    	//update next item (new previous item)
			    	if($next != 0)
			    	{
				    	$sql_upd2 = "UPDATE $tbl_lp_item SET previous_item_id = $previous, next_item_id = $id, display_order = display_order-1 WHERE id = $next";
				    	$res_upd2 = api_sql_query($sql_upd2, __FILE__, __LINE__);    				
			    	}

			    	//update next_next item (switch "previous" with current)
			    	if($next_next != 0)
			    	{
				    	$sql_upd2 = "UPDATE $tbl_lp_item SET previous_item_id = $id WHERE id = $next_next";
				    	$res_upd2 = api_sql_query($sql_upd2, __FILE__, __LINE__);    				
			    	}
			    	$display = $display+1;    				
    			}

    			break;
    		default:
    			return false;
    	}
		return $display;
    }
    /**
     * Move a learnpath up (display_order)
     * @param	integer	Learnpath ID
     */
    function move_up($lp_id)
    {
    	$lp_table = Database::get_course_table(TABLE_LP_MAIN);
    	$sql = "SELECT * FROM $lp_table ORDER BY display_order";
    	$res = api_sql_query($sql, __FILE__, __LINE__);
    	if($res === false) return false;
    	$lps = array();
    	$lp_order = array();
    	$num = Database::num_rows($res);
    	//first check the order is correct, globally (might be wrong because
    	//of versions < 1.8.4)
    	if($num>0)
    	{
    		$i = 1;
			while($row = Database::fetch_array($res))
			{
				if($row['display_order'] != $i)
				{	//if we find a gap in the order, we need to fix it
					$need_fix = true;
					$sql_u = "UPDATE $lp_table SET display_order = $i WHERE id = ".$row['id'];
					$res_u = api_sql_query($sql_u, __FILE__, __LINE__);
				}
				$row['display_order'] = $i;
				$lps[$row['id']] = $row;
				$lp_order[$i] = $row['id'];
				$i++;
			}
    	}
    	if($num>1) //if there's only one element, no need to sort
    	{
    		$order = $lps[$lp_id]['display_order'];
    		if($order>1) //if it's the first element, no need to move up
    		{
    			$sql_u1 = "UPDATE $lp_table SET display_order = $order WHERE id = ".$lp_order[$order-1];
    			$res_u1 = api_sql_query($sql_u1, __FILE__, __LINE__);
    			$sql_u2 = "UPDATE $lp_table SET display_order = ".($order-1)." WHERE id = ".$lp_id;
    			$res_u2 = api_sql_query($sql_u2, __FILE__, __LINE__);    			
    		}
    	}
    }
    /**
     * Move a learnpath down (display_order)
     * @param	integer	Learnpath ID
     */
    function move_down($lp_id)
    {
    	$lp_table = Database::get_course_table(TABLE_LP_MAIN);
    	$sql = "SELECT * FROM $lp_table ORDER BY display_order";
    	$res = api_sql_query($sql, __FILE__, __LINE__);
    	if($res === false) return false;
    	$lps = array();
    	$lp_order = array();
    	$num = Database::num_rows($res);
    	$max = 0;
    	//first check the order is correct, globally (might be wrong because
    	//of versions < 1.8.4)
    	if($num>0)
    	{
    		$i = 1;
			while($row = Database::fetch_array($res))
			{
				$max = $i;
				if($row['display_order'] != $i)
				{	//if we find a gap in the order, we need to fix it
					$need_fix = true;
					$sql_u = "UPDATE $lp_table SET display_order = $i WHERE id = ".$row['id'];
					$res_u = api_sql_query($sql_u, __FILE__, __LINE__);
				}
				$row['display_order'] = $i;
				$lps[$row['id']] = $row;
				$lp_order[$i] = $row['id'];
				$i++;
			}
    	}
    	if($num>1) //if there's only one element, no need to sort
    	{
    		$order = $lps[$lp_id]['display_order'];
    		if($order<$max) //if it's the first element, no need to move up
    		{
    			$sql_u1 = "UPDATE $lp_table SET display_order = $order WHERE id = ".$lp_order[$order+1];
    			$res_u1 = api_sql_query($sql_u1, __FILE__, __LINE__);
    			$sql_u2 = "UPDATE $lp_table SET display_order = ".($order+1)." WHERE id = ".$lp_id;
    			$res_u2 = api_sql_query($sql_u2, __FILE__, __LINE__);    			
    		}
    	}
    }
    /**
     * Updates learnpath attributes to point to the next element
     * The last part is similar to set_current_item but processing the other way around
     */
    function next()
    {
		if($this->debug>0){error_log('New LP - In learnpath::next()',0);}
    	$this->last = $this->get_current_item_id();
    	$this->items[$this->last]->save(false,$this->prerequisites_match($this->last));
    	$this->autocomplete_parents($this->last);
    	$new_index = $this->get_next_index();
    	if($this->debug>2){error_log('New LP - New index: '.$new_index,0);}
    	$this->index = $new_index;
    	if($this->debug>2){error_log('New LP - Now having orderedlist['.$new_index.'] = '. $this->ordered_items[$new_index],0);}
    	$this->current = $this->ordered_items[$new_index];
    	if($this->debug>2){error_log('New LP - new item id is '.$this->current.'-'.$this->get_current_item_id(),0);}
    }

    /**
     * Open a resource = initialise all local variables relative to this resource. Depending on the child
     * class, this might be redefined to allow several behaviours depending on the document type.
     * @param integer Resource ID
     * @return boolean True on success, false otherwise
     */

    function open($id)
    {
		if($this->debug>0){error_log('New LP - In learnpath::open()',0);}
    	//TODO
    	//set the current resource attribute to this resource
    	//switch on element type (redefine in child class?)
    	//set status for this item to "opened"
    	//start timer
    	//initialise score
    	$this->index = 0; //or = the last item seen (see $this->last)
    }

    /**
     * Check that all prerequisites are fulfilled. Returns true and an empty string on succes, returns false
     * and the prerequisite string on error.
     * This function is based on the rules for aicc_script language as described in the SCORM 1.2 CAM documentation page 108.
     * @param	integer	Optional item ID. If none given, uses the current open item.
     * @return	boolean	True if prerequisites are matched, false otherwise
     * @return	string	Empty string if true returned, prerequisites string otherwise.
     */
    function prerequisites_match($item = null)
    {
		if($this->debug>0){error_log('New LP - In learnpath::prerequisites_match()',0);}
    	if(empty($item)){$item = $this->current;}
    	if(is_object($this->items[$item]))
    	{
	    	$prereq_string = $this->items[$item]->get_prereq_string();
	    	if(empty($prereq_string)){return true;}
	    	//clean spaces
	    	$prereq_string = str_replace(' ','',$prereq_string);
	    	if($this->debug>0){error_log('Found prereq_string: '.$prereq_string,0);}
	    	//now send to the parse_prereq() function that will check this component's prerequisites
	    	$result = $this->items[$item]->parse_prereq($prereq_string,$this->items,$this->refs_list,$this->get_user_id());
	    	
	    	if($result === false)
	    	{
	    		$this->set_error_msg($this->items[$item]->prereq_alert);
	    	}
    	}else{
    		$result = true;
    		if($this->debug>1){error_log('New LP - $this->items['.$item.'] was not an object',0);}
    	}

    	if($this->debug>1){error_log('New LP - End of prerequisites_match(). Error message is now '.$this->error,0);}
    	return $result;
    }

    /**
     * Updates learnpath attributes to point to the previous element
     * The last part is similar to set_current_item but processing the other way around
     */

    function previous()
    {
		if($this->debug>0){error_log('New LP - In learnpath::previous()',0);}
    	$this->last = $this->get_current_item_id();
    	$this->items[$this->last]->save(false,$this->prerequisites_match($this->last));
    	$this->autocomplete_parents($this->last);
    	$new_index = $this->get_previous_index();
    	$this->index = $new_index;
    	$this->current = $this->ordered_items[$new_index];
    }    

    /**
     * Publishes a learnpath. This basically means show or hide the learnpath 
     * to normal users.
     * Can be used as abstract
	 * @param	integer	Learnpath ID
     * @param	string	New visibility
     */

    function toggle_visibility($lp_id,$set_visibility=1)
    {
		//if($this->debug>0){error_log('New LP - In learnpath::toggle_visibility()',0);}
		$action = 'visible';
		if($set_visibility != 1)
		{
			$action = 'invisible';
		}
		return api_item_property_update(api_get_course_info(),TOOL_LEARNPATH,$lp_id,$action,api_get_user_id());
	}
    /**
     * Publishes a learnpath. This basically means show or hide the learnpath
     * on the course homepage
     * Can be used as abstract
	 * @param	integer	Learnpath ID
     * @param	string	New visibility
     */

    function toggle_publish($lp_id,$set_visibility='v')
    {
		//if($this->debug>0){error_log('New LP - In learnpath::toggle_publish()',0);}
    	$tbl_lp = Database::get_course_table('lp');
		$sql="SELECT * FROM $tbl_lp where id=$lp_id";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($result);
		$name=domesticate($row['name']);
		if($set_visibility == 'i') { 
			$s=$name." ".get_lang('_no_published'); 
			$dialogBox=$s; 
			$v=0; 
		}
		if($set_visibility == 'v')
		{ 
			$s=$name." ".get_lang('_published');    
			$dialogBox=$s; 
			$v=1; 
		}

		$tbl_tool = Database::get_course_table(TABLE_TOOL_LIST);
		$link = 'newscorm/lp_controller.php?action=view&lp_id='.$lp_id;
		$sql="SELECT * FROM $tbl_tool where name='$name' and image='scormbuilder.gif' and link LIKE '$link%'";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$num=Database::num_rows($result);
		$row2=Database::fetch_array($result);
		//if($this->debug>2){error_log('New LP - '.$sql.' - '.$num,0);}
		if(($set_visibility == 'i') && ($num>0))
		{
			$sql ="DELETE FROM $tbl_tool WHERE (name='$name' and image='scormbuilder.gif' and link LIKE '$link%')";
		}
		elseif(($set_visibility == 'v') && ($num==0))
		{
			$sql ="INSERT INTO $tbl_tool (name, link, image, visibility, admin, address, added_tool) VALUES ('$name','newscorm/lp_controller.php?action=view&lp_id=$lp_id','scormbuilder.gif','$v','0','pastillegris.gif',0)";
		}
		else
		{
			//parameter and database incompatible, do nothing
		}
		$result=api_sql_query($sql,__FILE__,__LINE__);
		//if($this->debug>2){error_log('New LP - Leaving learnpath::toggle_visibility: '.$sql,0);}
	}
    /**
     * Restart the whole learnpath. Return the URL of the first element.
     * Make sure the results are saved with anoter method. This method should probably be
     * redefined in children classes.
     * To use a similar method  statically, use the create_new_attempt() method
     * @return string URL to load in the viewer
     */
    function restart()
    {
		if($this->debug>0){error_log('New LP - In learnpath::restart()',0);}
    	//TODO
    	//call autosave method to save the current progress
    	//$this->index = 0;
     	$lp_view_table = Database::get_course_table('lp_view');
   		$sql = "INSERT INTO $lp_view_table (lp_id, user_id, view_count) " .
   				"VALUES (".$this->lp_id.",".$this->get_user_id().",".($this->attempt+1).")";
   		if($this->debug>2){error_log('New LP - Inserting new lp_view for restart: '.$sql,0);}
  		$res = api_sql_query($sql, __FILE__, __LINE__);     	
    	if($view_id = Database::get_last_insert_id($res))
    	{
     		$this->lp_view_id = $view_id;
     		$this->attempt = $this->attempt+1;
     	}else{
     		$this->error = 'Could not insert into item_view table...';
     		return false;
     	}
     	$this->autocomplete_parents($this->current);
     	foreach($this->items as $index=>$dummy){
     		$this->items[$index]->restart();
     		$this->items[$index]->set_lp_view($this->lp_view_id);
     	}
     	$this->first();
     	return true;
    }
    /**
     * Saves the current item
     * @return	boolean
     */
    function save_current(){
		if($this->debug>0){error_log('New LP - In learnpath::save_current()',0);}
    	//TODO do a better check on the index pointing to the right item (it is supposed to be working
    	// on $ordered_items[] but not sure it's always safe to use with $items[])
    	if($this->debug>2){error_log('New LP - save_current() saving item '.$this->current,0);}
    	if($this->debug>2){error_log(''.print_r($this->items,true),0);}
    	if(is_object($this->items[$this->current])){
    		//$res = $this->items[$this->current]->save(false);
	    	$res = $this->items[$this->current]->save(false,$this->prerequisites_match($this->current));
    		$this->autocomplete_parents($this->current);
    		$status = $this->items[$this->current]->get_status();
    		$this->append_message('new_item_status: '.$status);
     		$this->update_queue[$this->current] = $status;
    		return $res;
    	}
    	return false;
    }
    /**
     * Saves the given item
     * @param	integer	Item ID. Optional (will take from $_REQUEST if null)
     * @param	boolean	Save from url params (true) or from current attributes (false). Optional. Defaults to true
     * @return	boolean
     */
    function save_item($item_id=null,$from_outside=true){
		if($this->debug>0){error_log('New LP - In learnpath::save_item('.$item_id.','.$from_outside.')',0);}
    	//TODO do a better check on the index pointing to the right item (it is supposed to be working
    	// on $ordered_items[] but not sure it's always safe to use with $items[])
    	if(empty($item_id)){
    		$item_id = $this->escape_string($_REQUEST['id']);
    	}
    	if(empty($item_id))
    	{
    		$item_id = $this->get_current_item_id();
    	}
    	if($this->debug>2){error_log('New LP - save_current() saving item '.$item_id,0);}
    	if(is_object($this->items[$item_id])){
	    	$res = $this->items[$item_id]->save($from_outside,$this->prerequisites_match($item_id));
    		//$res = $this->items[$item_id]->save($from_outside);
    		$this->autocomplete_parents($item_id);
    		$status = $this->items[$item_id]->get_status();
    		$this->append_message('new_item_status: '.$status);
    		$this->update_queue[$item_id] = $status;
    		return $res;
    	}
    	return false;
    }
    /**
     * Saves the last item seen's ID only in case 
     */
    function save_last(){

		if($this->debug>0){error_log('New LP - In learnpath::save_last()',0);}

   		$table = Database::get_course_table('lp_view');

    	if(isset($this->current)){

    		if($this->debug>2){error_log('New LP - Saving current item ('.$this->current.') for later review',0);}

    		$sql = "UPDATE $table SET last_item = ".$this->get_current_item_id()." " .

    				"WHERE lp_id = ".$this->get_id()." AND user_id = ".$this->get_user_id();

    		if($this->debug>2){error_log('New LP - Saving last item seen : '.$sql,0);}

			$res = api_sql_query($sql,__FILE__,__LINE__);

    	}

    	//save progress

    	list($progress,$text) = $this->get_progress_bar_text('%');

    	if($progress>=0 AND $progress<=100){

    		$progress= (int)$progress;

    		$sql = "UPDATE $table SET progress = $progress " .

    				"WHERE lp_id = ".$this->get_id()." AND " .

    						"user_id = ".$this->get_user_id();

    		$res = api_sql_query($sql,__FILE__, __LINE__); //ignore errors as some tables might not have the progress field just yet

    		$this->progress_db = $progress;

    	}

    }
    /**
     * Sets the current item ID (checks if valid and authorized first)
     * @param	integer	New item ID. If not given or not authorized, defaults to current
     */
    function set_current_item($item_id=null)
    {
		if($this->debug>0){error_log('New LP - In learnpath::set_current_item('.$item_id.')',0);}
    	if(empty($item_id)){
    		if($this->debug>2){error_log('New LP - No new current item given, ignore...',0);}
    		//do nothing
    	}else{
   			if($this->debug>2){error_log('New LP - New current item given is '.$item_id.'...',0);}
    		$item_id = $this->escape_string($item_id);
    		//TODO check in database here
    		$this->last = $this->current;
    		$this->current = $item_id;
    		//TODO update $this->index as well
    		foreach($this->ordered_items as $index => $item)
    		{
    			if($item == $this->current)
    			{
    				$this->index = $index;
	   				break;
    			}
    		}
	    	if($this->debug>2){error_log('New LP - set_current_item('.$item_id.') done. Index is now : '.$this->index,0);}
    	}
    }
    /**
     * Sets the encoding
     * @param	string	New encoding
     */
    function set_encoding($enc='ISO-8859-1'){
		if($this->debug>0){error_log('New LP - In learnpath::set_encoding()',0);}
    	$enc = strtoupper($enc);
	 	$encodings = array('UTF-8','ISO-8859-1','ISO-8859-15','cp1251','cp1252','KOI8-R','BIG5','GB2312','Shift_JIS','EUC-JP','');
		if(in_array($enc,$encodings)){
		 	$lp = $this->get_id();
		 	if($lp!=0){
		 		$tbl_lp = Database::get_course_table('lp');
		 		$sql = "UPDATE $tbl_lp SET default_encoding = '$enc' WHERE id = ".$lp;
		 		$res = api_sql_query($sql, __FILE__, __LINE__);
		 		return $res;
		 	}
		}
		return false;
    }
	/**
	 * Sets the JS lib setting in the database directly. 
	 * This is the JavaScript library file this lp needs to load on startup
	 * @param	string	Proximity setting
	 */
	 function set_jslib($lib=''){
		if($this->debug>0){error_log('New LP - In learnpath::set_jslib()',0);}
	 	$lp = $this->get_id();
	 	if($lp!=0){
	 		$tbl_lp = Database::get_course_table('lp');
	 		$sql = "UPDATE $tbl_lp SET js_lib = '$lib' WHERE id = ".$lp;
	 		$res = api_sql_query($sql, __FILE__, __LINE__);
	 		return $res;
	 	}else{
	 		return false;
	 	}
	 }
    /**
     * Sets the name of the LP maker (publisher) (and save)
     * @param	string	Optional string giving the new content_maker of this learnpath
     */
    function set_maker($name=''){

		if($this->debug>0){error_log('New LP - In learnpath::set_maker()',0);}

    	if(empty($name))return false;

    	

    	$this->maker = $this->escape_string($name);

		$lp_table = Database::get_course_table('lp');

		$lp_id = $this->get_id();

		$sql = "UPDATE $lp_table SET content_maker = '".$this->maker."' WHERE id = '$lp_id'";

		if($this->debug>2){error_log('New LP - lp updated with new content_maker : '.$this->maker,0);}

		//$res = Database::query($sql);

		$res = api_sql_query($sql, __FILE__, __LINE__);

    	return true;

    }    

    /**

     * Sets the name of the current learnpath (and save)

     * @param	string	Optional string giving the new name of this learnpath

     */

    function set_name($name=''){

		if($this->debug>0){error_log('New LP - In learnpath::set_name()',0);}

    	if(empty($name))return false;

    	

    	$this->name = $this->escape_string($name);

		$lp_table = Database::get_course_table('lp');

		$lp_id = $this->get_id();

		$sql = "UPDATE $lp_table SET name = '".$this->name."' WHERE id = '$lp_id'";

		if($this->debug>2){error_log('New LP - lp updated with new name : '.$this->name,0);}

		//$res = Database::query($sql);

		$res = api_sql_query($sql, __FILE__, __LINE__);
		
		// if the lp is visible on the homepage, change his name there
		if(mysql_affected_rows())
		{ 
			$table = Database :: get_course_table(TABLE_TOOL_LIST);
			$sql = 'UPDATE '.$table.' SET
						name = "'.$this->name.'"
					WHERE link = "newscorm/lp_controller.php?action=view&lp_id='.$lp_id.'"';
			api_sql_query($sql, __FILE__, __LINE__);
		}

    	return true;

    }

    /**
     * Set index specified prefix terms for all items in this path
     * @param   string  Comma-separated list of terms
     * @param   char Xapian term prefix
     * @return  boolean False on error, true otherwise
     */
    function set_terms_by_prefix($terms_string, $prefix) {
      if (api_get_setting('search_enabled') !== 'true')
        return FALSE;

      $terms_string = trim($terms_string);
      $terms = explode(',', $terms_string);
      array_walk($terms, 'trim_value');
      
      $stored_terms = $this->get_common_index_terms_by_prefix($prefix);
      //var_dump($stored_terms);
	  //var_dump($terms);

      // don't do anything if no change, verify only at DB, not the search engine
      if ( (count(array_diff($terms, $stored_terms))==0) && (count(array_diff($stored_terms, $terms))==0) )
        return FALSE;

      require_once('xapian.php'); //TODO try catch every xapian use or make wrappers on api
      require_once(api_get_path(LIBRARY_PATH).'search/DokeosIndexer.class.php');
      require_once(api_get_path(LIBRARY_PATH).'search/xapian/XapianQuery.php');
      require_once(api_get_path(LIBRARY_PATH).'search/IndexableChunk.class.php');

      $items_table = Database::get_course_table('lp_item');
      //TODO: make query secure agains XSS : use member attr instead of post var
      $lp_id = $_POST['lp_id'];
      $sql = "SELECT * FROM $items_table WHERE lp_id = $lp_id";
      $result = api_sql_query($sql);
      $di = new DokeosIndexer();
        
      while($lp_item = Database::fetch_array($result))
      {
        // get search_did
        $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
        $sql = 'SELECT * FROM %s WHERE course_code=\'%s\' AND tool_id=\'%s\' AND ref_id_high_level=%s AND ref_id_second_level=%d LIMIT 1';
        $sql = sprintf($sql, $tbl_se_ref, $this->cc, TOOL_LEARNPATH, $lp_id, $lp_item['id']);
        $res = api_sql_query($sql, __FILE__, __LINE__);
        $se_ref = Database::fetch_array($res);

        // compare terms
        $doc = $di->get_document($se_ref['search_did']);
        $xapian_terms = xapian_get_doc_terms($doc, $prefix);
        //var_dump($xapian_terms);
        $xterms = array();
        foreach ($xapian_terms as $xapian_term) $xterms[] = substr($xapian_term['name'],1);

        $dterms = $terms;
        //var_dump($xterms);
        //var_dump($dterms);
        
        $missing_terms = array_diff($dterms, $xterms);
        $deprecated_terms = array_diff($xterms, $dterms);

        // save it to search engine
        foreach ($missing_terms as $term)
        {
            $doc->add_term($prefix. $term, 1);
        }
        foreach ($deprecated_terms as $term)
        {
        	$doc->remove_term($prefix.$term);	
        }
        $di->getDb()->replace_document((int)$se_ref['search_did'], $doc);
        $di->getDb()->flush();
        }

        return true;
    }    

     /**
     * Sets the theme of the LP (local/remote) (and save)
     * @param	string	Optional string giving the new theme of this learnpath
     * @return bool returns true if theme name is not empty
     */
    function set_theme($name=''){
		if($this->debug>0){error_log('New LP - In learnpath::set_theme()',0);}
    	$this->theme = $this->escape_string($name);
		$lp_table = Database::get_course_table('lp');
		$lp_id = $this->get_id();
		$sql = "UPDATE $lp_table SET theme = '".$this->theme."' WHERE id = '$lp_id'";
		if($this->debug>2){error_log('New LP - lp updated with new theme : '.$this->theme,0);}
		//$res = Database::query($sql);
		$res = api_sql_query($sql, __FILE__, __LINE__);
    	return true;
    }    

  /**
     * Sets the image of an LP (and save)
     * @param	string	Optional string giving the new image of this learnpath
     * @return bool returns true if theme name is not empty
     */
    function set_preview_image($name=''){
		if($this->debug>0){error_log('New LP - In learnpath::set_preview_image()',0);}
    	$this->preview_image = $this->escape_string($name);
		$lp_table = Database::get_course_table('lp');
		$lp_id = $this->get_id(); 
		$sql = "UPDATE $lp_table SET preview_image = '".$this->preview_image."' WHERE id = '$lp_id'";
		if($this->debug>2){error_log('New LP - lp updated with new preview image : '.$this->preview_image,0);}
		$res = api_sql_query($sql, __FILE__, __LINE__);
    	return true;
    }  
    
    
     /**
     * Sets the author of a LP (and save)
     * @param	string	Optional string giving the new author of this learnpath
     * @return bool returns true if author's name is not empty
     */
    function set_author($name=''){
		if($this->debug>0){error_log('New LP - In learnpath::set_author()',0);}
    	$this->author = $this->escape_string($name);
		$lp_table = Database::get_course_table('lp');
		$lp_id = $this->get_id(); 
		$sql = "UPDATE $lp_table SET author = '".$this->author."' WHERE id = '$lp_id'";
		if($this->debug>2){error_log('New LP - lp updated with new preview author : '.$this->author,0);}
		$res = api_sql_query($sql, __FILE__, __LINE__);
    	return true;
    }  
    

    /**
     * Sets the location/proximity of the LP (local/remote) (and save)
     * @param	string	Optional string giving the new location of this learnpath
     */
    function set_proximity($name=''){
		if($this->debug>0){error_log('New LP - In learnpath::set_proximity()',0);}
    	if(empty($name))return false;
    	
    	$this->proximity = $this->escape_string($name);
		$lp_table = Database::get_course_table('lp');
		$lp_id = $this->get_id();
		$sql = "UPDATE $lp_table SET content_local = '".$this->proximity."' WHERE id = '$lp_id'";
		if($this->debug>2){error_log('New LP - lp updated with new proximity : '.$this->proximity,0);}
		//$res = Database::query($sql);
		$res = api_sql_query($sql, __FILE__, __LINE__);
    	return true;
    }   
    /**
     * Sets the previous item ID to a given ID. Generally, this should be set to the previous 'current' item
     * @param	integer	DB ID of the item
     */
    function set_previous_item($id)
    {
		if($this->debug>0){error_log('New LP - In learnpath::set_previous_item()',0);}
    	$this->last = $id;
    }
    /**
     * Sets the object's error message
     * @param	string	Error message. If empty, reinits the error string
     * @return 	void
     */
    function set_error_msg($error='')
    {
		if($this->debug>0){error_log('New LP - In learnpath::set_error_msg()',0);}
    	if(empty($error)){
    		$this->error = '';
    	}else{
    		$this->error .= $error;
    	}
    }
    /**
     * Launches the current item if not 'sco' (starts timer and make sure there is a record ready in the DB)
     * 
     */
    function start_current_item($allow_new_attempt=false){
		if($this->debug>0){error_log('New LP - In learnpath::start_current_item()',0);}
    	if($this->current != 0 AND
    		is_object($this->items[$this->current]))
    	{
    		$type = $this->get_type();
    		$item_type = $this->items[$this->current]->get_type();
			if(
				($type == 2 && $item_type!='sco')
				OR
				($type == 3 && $item_type!='au')
				OR
				($type == 1 && $item_type!=TOOL_QUIZ && $item_type!=TOOL_HOTPOTATOES)
			)
			{
	    		$this->items[$this->current]->open($allow_new_attempt);
	    		    		
	    		$this->autocomplete_parents($this->current);
	    		$prereq_check = $this->prerequisites_match($this->current);
    			$this->items[$this->current]->save(false,$prereq_check);
	    		//$this->update_queue[$this->last] = $this->items[$this->last]->get_status();
			}else{
				//if sco, then it is supposed to have been updated by some other call
			}
			if ($item_type=='sco') {
            	$this->items[$this->current]->restart();
			}
    	}
		if($this->debug>0){error_log('New LP - End of learnpath::start_current_item()',0);}
    	return true;
    }

    /**

     * Stops the processing and counters for the old item (as held in $this->last)

     * @param	

     */

    function stop_previous_item(){

		if($this->debug>0){error_log('New LP - In learnpath::stop_previous_item()',0);}

    	if($this->last != 0 AND $this->last!=$this->current AND is_object($this->items[$this->last]))
    	{
    		if($this->debug>2){error_log('New LP - In learnpath::stop_previous_item() - '.$this->last.' is object',0);}
    		switch($this->get_type()){
				case '3':
		    		if($this->items[$this->last]->get_type()!='au')
		    		{
			    		if($this->debug>2){error_log('New LP - In learnpath::stop_previous_item() - '.$this->last.' in lp_type 3 is <> au',0);}
		    			$this->items[$this->last]->close();
			    		//$this->autocomplete_parents($this->last);
		  	    		//$this->update_queue[$this->last] = $this->items[$this->last]->get_status();
		    		}else{
		    			if($this->debug>2){error_log('New LP - In learnpath::stop_previous_item() - Item is an AU, saving is managed by AICC signals',0);}
		    		}
		    	case '2':
		    		if($this->items[$this->last]->get_type()!='sco')
		    		{
			    		if($this->debug>2){error_log('New LP - In learnpath::stop_previous_item() - '.$this->last.' in lp_type 2 is <> sco',0);}
		    			$this->items[$this->last]->close();
			    		//$this->autocomplete_parents($this->last);
		  	    		//$this->update_queue[$this->last] = $this->items[$this->last]->get_status();
		    		}else{
		    			if($this->debug>2){error_log('New LP - In learnpath::stop_previous_item() - Item is a SCO, saving is managed by SCO signals',0);}
		    		}
		    		break;
		    	case '1':
		    	default:
		    		if($this->debug>2){error_log('New LP - In learnpath::stop_previous_item() - '.$this->last.' in lp_type 1 is asset',0);}
	    			$this->items[$this->last]->close();
		    		break;
    		}
    	}else{
    		if($this->debug>2){error_log('New LP - In learnpath::stop_previous_item() - No previous element found, ignoring...',0);}
    		return false;
    	}
    	return true;
    }

    /**

     * Updates the default view mode from fullscreen to embedded and inversely

     * @return	string The current default view mode ('fullscreen' or 'embedded')

     */

    function update_default_view_mode()

    {

		if($this->debug>0){error_log('New LP - In learnpath::update_default_view_mode()',0);}

    	$lp_table = Database::get_course_table('lp');

    	$sql = "SELECT * FROM $lp_table WHERE id = ".$this->get_id();

    	$res = api_sql_query($sql, __FILE__, __LINE__);

    	if(Database::num_rows($res)>0){

    		$row = Database::fetch_array($res);

    		$view_mode = $row['default_view_mod'];

    		if($view_mode == 'fullscreen'){

    			$view_mode = 'embedded';

    		}elseif($view_mode == 'embedded'){

    			$view_mode = 'fullscreen';

    		}

    		$sql = "UPDATE $lp_table SET default_view_mod = '$view_mode' WHERE id = ".$this->get_id();

    		$res = api_sql_query($sql, __FILE__, __LINE__);

   			$this->mode = $view_mode;

	    	return $view_mode;

    	}else{

    		if($this->debug>2){error_log('New LP - Problem in update_default_view() - could not find LP '.$this->get_id().' in DB',0);}

    	}

		return -1;

    }

    /**

     * Updates the default behaviour about auto-commiting SCORM updates

     * @return	boolean	True if auto-commit has been set to 'on', false otherwise

     */

    function update_default_scorm_commit(){

		if($this->debug>0){error_log('New LP - In learnpath::update_default_scorm_commit()',0);}

    	$lp_table = Database::get_course_table('lp');

    	$sql = "SELECT * FROM $lp_table WHERE id = ".$this->get_id();

    	$res = api_sql_query($sql, __FILE__, __LINE__);

    	if(Database::num_rows($res)>0){

    		$row = Database::fetch_array($res);

    		$force = $row['force_commit'];

			if($force == 1){

    			$force = 0;

    			$force_return = false;

    		}elseif($force == 0){

    			$force = 1;

    			$force_return = true;

    		}

    		$sql = "UPDATE $lp_table SET force_commit = $force WHERE id = ".$this->get_id();

    		$res = api_sql_query($sql, __FILE__, __LINE__);

			$this->force_commit = $force_return;

	    	return $force_return;

    	}else{

    		if($this->debug>2){error_log('New LP - Problem in update_default_scorm_commit() - could not find LP '.$this->get_id().' in DB',0);}

    	}

    	return -1;

    }

	/**
	 * Updates the order of learning paths (goes through all of them by order and fills the gaps)
	 * @return	bool	True on success, false on failure
	 */
	function update_display_order()
	{
    	$lp_table = Database::get_course_table(TABLE_LP_MAIN);
    	$sql = "SELECT * FROM $lp_table ORDER BY display_order";
    	$res = api_sql_query($sql, __FILE__, __LINE__);
    	if($res === false) return false;
    	$lps = array();
    	$lp_order = array();
    	$num = Database::num_rows($res);
    	//first check the order is correct, globally (might be wrong because
    	//of versions < 1.8.4)
    	if($num>0)
    	{
    		$i = 1;
			while($row = Database::fetch_array($res))
			{
				if($row['display_order'] != $i)
				{	//if we find a gap in the order, we need to fix it
					$need_fix = true;
					$sql_u = "UPDATE $lp_table SET display_order = $i WHERE id = ".$row['id'];
					$res_u = api_sql_query($sql_u, __FILE__, __LINE__);
				}
				$i++;
			}
    	}
		return true;
	}

    /**

     * Updates the "prevent_reinit" value that enables control on reinitialising items on second view

     * @return	boolean	True if prevent_reinit has been set to 'on', false otherwise (or 1 or 0 in this case)

     */

    function update_reinit(){

		if($this->debug>0){error_log('New LP - In learnpath::update_reinit()',0);}

    	$lp_table = Database::get_course_table('lp');

    	$sql = "SELECT * FROM $lp_table WHERE id = ".$this->get_id();

    	$res = api_sql_query($sql, __FILE__, __LINE__);

    	if(Database::num_rows($res)>0){

    		$row = Database::fetch_array($res);

    		$force = $row['prevent_reinit'];

			if($force == 1){

    			$force = 0;

    		}elseif($force == 0){

    			$force = 1;

    		}

    		$sql = "UPDATE $lp_table SET prevent_reinit = $force WHERE id = ".$this->get_id();

    		$res = api_sql_query($sql,__FILE__,__LINE__);

			$this->prevent_reinit = $force;

	    	return $force;

    	}else{

    		if($this->debug>2){error_log('New LP - Problem in update_reinit() - could not find LP '.$this->get_id().' in DB',0);}

    	}

    	return -1;

    }

    /**

     * Updates the "scorm_debug" value that shows or hide the debug window

     * @return	boolean	True if scorm_debug has been set to 'on', false otherwise (or 1 or 0 in this case)

     */

    function update_scorm_debug(){

		if($this->debug>0){error_log('New LP - In learnpath::update_scorm_debug()',0);}

    	$lp_table = Database::get_course_table('lp');

    	$sql = "SELECT * FROM $lp_table WHERE id = ".$this->get_id();

    	$res = api_sql_query($sql, __FILE__, __LINE__);

    	if(Database::num_rows($res)>0){

    		$row = Database::fetch_array($res);

    		$force = $row['debug'];

			if($force == 1){

    			$force = 0;

    		}elseif($force == 0){

    			$force = 1;

    		}

    		$sql = "UPDATE $lp_table SET debug = $force WHERE id = ".$this->get_id();

    		$res = api_sql_query($sql,__FILE__,__LINE__);

			$this->scorm_debug = $force;

	    	return $force;

    	}else{

    		if($this->debug>2){error_log('New LP - Problem in update_scorm_debug() - could not find LP '.$this->get_id().' in DB',0);}

    	}

    	return -1;

    }
    
   /**
	* Function that makes a call to the function sort_tree_array and create_tree_array
	*
	* @author Kevin Van Den Haute
	* 
	* @param unknown_type $array
	*/
	function tree_array($array)
	{
		if($this->debug>1){error_log('New LP - In learnpath::tree_array()',0);}
		$array = $this->sort_tree_array($array);
		$this->create_tree_array($array);
	}

	/**
	 * Creates an array with the elements of the learning path tree in it
	 *
	 * @author Kevin Van Den Haute
	 * 
	 * @param array $array
	 * @param int $parent
	 * @param int $depth
	 * @param array $tmp
	 */
	function create_tree_array($array, $parent = 0, $depth = -1, $tmp = array())
	{
		if($this->debug>1){error_log('New LP - In learnpath::create_tree_array())',0);}
		if(is_array($array))
		{
			for($i = 0; $i < count($array); $i++)
			{	
				if($array[$i]['parent_item_id'] == $parent)
				{
					if(!in_array($array[$i]['parent_item_id'], $tmp))
					{
						$tmp[] = $array[$i]['parent_item_id'];
						$depth++;
					}
					$preq = (empty($array[$i]['prerequisite'])?'':$array[$i]['prerequisite']);
					$this->arrMenu[] = array(
						'id' => $array[$i]['id'],
						'item_type' => $array[$i]['item_type'],
						'title' => $array[$i]['title'],
						'path' => $array[$i]['path'],
						'description' => $array[$i]['description'],
						'parent_item_id' => $array[$i]['parent_item_id'],
						'previous_item_id' => $array[$i]['previous_item_id'],
						'next_item_id' => $array[$i]['next_item_id'],
						'min_score' => $array[$i]['min_score'],
						'max_score' => $array[$i]['max_score'],
						'mastery_score' => $array[$i]['mastery_score'],
						'display_order' => $array[$i]['display_order'],
						'prerequisite' => $preq,
						'depth' => $depth,
						'audio' => $array[$i]['audio']
						);
					
					$this->create_tree_array($array, $array[$i]['id'], $depth, $tmp);
				}
			}
		}
	}
	
	/**
	 * Sorts a multi dimensional array by parent id and display order
	 * @author Kevin Van Den Haute
	 * 
	 * @param array $array (array with al the learning path items in it)
	 * 
	 * @return array
	 */
	function sort_tree_array($array)
	{
		foreach($array as $key => $row)
		{
			$parent[$key]	= $row['parent_item_id'];
			$position[$key]	= $row['display_order'];
		}
		
		if(count($array) > 0)
			array_multisort($parent, SORT_ASC, $position, SORT_ASC, $array);
		
		return $array;
	}
	
	
	
	/**
	 * Function that creates a table structure with a learning path his modules, chapters and documents.
	 * Also the actions for the modules, chapters and documents are in this table.
	 *
	 * @author Kevin Van Den Haute
	 * 
	 * @param int $lp_id
	 * 
	 * @return string
	 */
	function overview()
	{
		if($this->debug>0){error_log('New LP - In learnpath::overview()',0);}
		global $charset, $_course;
		$return = '';
		
		$tbl_lp_item = Database::get_course_table('lp_item');
		
		$sql = "
			SELECT *
			FROM " . $tbl_lp_item . "
			WHERE
				lp_id = " . $this->lp_id;
		
		$result = api_sql_query($sql, __FILE__, __LINE__);		
		$arrLP = array();		
		$mycharset=api_get_setting('platform_charset');		
		
		while($row = Database::fetch_array($result))
		{
				
			$row['title'] = mb_convert_encoding($row['title'], $mycharset,$this->encoding);
			
			$arrLP[] = array(
			'id' => $row['id'],
			'item_type' => $row['item_type'],
			'title' => $row['title'],
			'path' => $row['path'],
			'description' => $row['description'],
			'parent_item_id' => $row['parent_item_id'],
			'previous_item_id' => $row['previous_item_id'],
			'next_item_id' => $row['next_item_id'],
            'max_score' => $row['max_score'],
            'min_score' => $row['min_score'],
            'mastery_score' => $row['mastery_score'],
            'prerequisite' => $row['prerequisite'],
			'display_order' 	=> $row['display_order'],
			'audio'				=> $row['audio']
			);		
		}
		 
		$this->tree_array($arrLP);		
		$arrLP = $this->arrMenu;		
		unset($this->arrMenu);	 
		
		if(api_is_allowed_to_edit())
		{
			$return .= '<div class="actions">';
			$return .= '<a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=build&amp;lp_id=' . $this->lp_id . '">'.Display::return_icon('learnpath_build.gif', get_lang('Build')).' '.get_lang('Build').'</a>';			
						
			$return .= '<span>'.Display::return_icon('learnpath_organize.gif', get_lang("BasicOverview")).' '.get_lang("BasicOverview").'</span>';
									
			$return .= '<a href="lp_controller.php?cidReq='.$_GET['cidReq'].'&action=view&lp_id='.$this->lp_id.'">'.Display::return_icon('learnpath_view.gif', get_lang("Display")).' '.get_lang("Display").'</a>';			
			
			$return .= '<a href="'.api_get_self().'?cidReq='.Security::remove_XSS($_GET['cidReq']).'&amp;action='.Security::remove_XSS($_GET['action']).'&amp;lp_id='.Security::remove_XSS($_GET['lp_id']).'&amp;updateaudio=true">'.Display::return_icon('audio.gif', get_lang('UpdateAllAudioFragments')).' '.get_lang('UpdateAllAudioFragments').'</a>';
			$return .= '</div>';
		}
		
		// we need to start a form when we want to update all the mp3 files
		if ($_GET['updateaudio'] == 'true' AND count($arrLP) <> 0)
		{
			$return .= '<form action="'.api_get_self().'?cidReq='.Security::remove_XSS($_GET['cidReq']).'&amp;action='.Security::remove_XSS($_GET['action']).'&amp;lp_id='.Security::remove_XSS($_GET['lp_id']).'" method="post" enctype="multipart/form-data" name="updatemp3" id="updatemp3">';	
			$return .= Display::display_warning_message(get_lang('LeaveEmptyToKeepCurrentFile'));
		}
		
		$return .= '<table class="data_table">' . "\n";
		
			$return .= "\t" . '<tr>' . "\n";
			
				$return .= "\t" . '<th width="60%">'.get_lang("Title").'</th>' . "\n";
				//$return .= "\t" . '<th>'.get_lang("Description").'</th>' . "\n";
				$return .= "\t" . '<th>'.get_lang("Audio").'</th>' . "\n";
				$return .= "\t" . '<th>'.get_lang("Move").'</th>' . "\n";
				$return .= "\t" . '<th>'.get_lang("Actions").'</th>' . "\n";
			
			$return .= "\t" . '</tr>' . "\n";
			
			for($i = 0; $i < count($arrLP); $i++)
			{
				$title=$arrLP[$i]['title'];
				
				if($arrLP[$i]['description'] == '')
					$arrLP[$i]['description'] = '&nbsp;';
				
				if (($i % 2)==0) { $oddclass="row_odd"; } else { $oddclass="row_even"; }
				
				$return .= "\t" . '<tr class="'.$oddclass.'">' . "\n";
					
					$return .= "\t\t" . '<td style="padding-left:' . $arrLP[$i]['depth'] * 10 . 'px;"><img align="left" src="../img/lp_' . $arrLP[$i]['item_type'] . '.png" style="margin-right:3px;" />' . $title . '</td>' . "\n";
					//$return .= "\t\t" . '<td>' . stripslashes($arrLP[$i]['description']) . '</td>' . "\n";
					
					// The audio column
					$return .= "\t\t" . '<td align="center">';
					if (!$_GET['updateaudio'] OR $_GET['updateaudio'] <> 'true')
					{
						if (!empty($arrLP[$i]['audio']))
						{
							$return .= '<span id="container'.$i.'"><a href="http://www.macromedia.com/go/getflashplayer">Get the Flash Player</a> to see this player.</span>';
							$return .= '<script type="text/javascript" src="../inc/lib/mediaplayer/swfobject.js"></script>';
							$return .= '<script type="text/javascript">
											var s1 = new SWFObject("../inc/lib/mediaplayer/player.swf","ply","250","20","9","#FFFFFF");
											s1.addParam("allowscriptaccess","always");
											s1.addParam("flashvars","file=../../courses/'.$_course['path'].'/document/audio/'.$arrLP[$i]['audio'].'&image=preview.jpg");
											s1.write("container'.$i.'");
										</script>';	
						}
						else 
						{
							$return .= ' - ';
						}
					}
					else 
					{
						if ($arrLP[$i]['item_type']!='dokeos_chapter' && $arrLP[$i]['item_type'] != 'dokeos_module' && $arrLP[$i]['item_type'] != 'dir') {
							$return .= ' <input type="file" name="mp3file'.$arrLP[$i]['id'].'" id="mp3file" />';
							if (!empty($arrLP[$i]['audio']))
							{
								$return .= '<br /><input type="checkbox" name="removemp3'.$arrLP[$i]['id'].'" id="checkbox'.$arrLP[$i]['id'].'" />'.get_lang('RemoveAudio');
							}
						}
					}
					$return .= '</td>' . "\n";
					
					if(api_is_allowed_to_edit())
					{
						$return .= "\t\t" . '<td align="center">' . "\n";
							
							if($arrLP[$i]['previous_item_id'] != 0)
							{
								
								$return .= "\t\t\t" . '<a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=move_item&amp;direction=up&amp;id=' . $arrLP[$i]['id'] . '&amp;lp_id=' . $this->lp_id . '">';
									$return .= '<img style="margin:1px;" alt="" src="../img/arrow_up_' . ($arrLP[$i]['depth'] % 3). '.gif" title="' . get_lang('MoveUp') .'"/>';
								$return .= '</a>' . "\n";
								
							}
							else
								$return .= "\t\t\t" . '<img alt="" src="../img/blanco.png" title="" />' . "\n";
							
							if($arrLP[$i]['next_item_id'] != 0)
							{
								$return .= "\t\t\t" . '<a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=move_item&amp;direction=down&amp;id=' . $arrLP[$i]['id'] . '&amp;lp_id=' . $this->lp_id . '">';
									$return .= '<img style="margin:1px;" src="../img/arrow_down_' . ($arrLP[$i]['depth'] % 3) . '.gif" title="' . get_lang('MoveDown') . '" />';							
									
								$return .= '</a>' . "\n";
							}
							else
								$return .= "\t\t\t" . '<img alt="" src="../img/blanco.png" title="" />' . "\n";
							
						$return .= "\t\t" . '</td>' . "\n";
						
						$return .= "\t\t" . '<td align="center">' . "\n";
						
						if($arrLP[$i]['item_type'] != 'dokeos_chapter' && $arrLP[$i]['item_type'] != 'dokeos_module')
						{										
							$return .= "\t\t\t" . '<a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=edit_item&amp;view=build&amp;id=' . $arrLP[$i]['id'] . '&amp;lp_id=' . $this->lp_id . '&amp;path_item='.$arrLP[$i]['path'].'">';
								$return .= '<img style="margin:1px;" alt="" src="../img/edit.gif" title="' . get_lang('_edit_learnpath_module') . '" />';
							$return .= '</a>' . "\n";
						}
						else
						{							
							$return .= "\t\t\t" . '<a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=edit_item&amp;id=' . $arrLP[$i]['id'] . '&amp;lp_id=' . $this->lp_id . '&amp;path_item='.$arrLP[$i]['path'].'">';
							$return .= '<img style="margin:1px;" alt="" src="../img/edit.gif" title="' . get_lang('_edit_learnpath_module') . '" />';
							$return .= '</a>' . "\n";
						}
						
						$return .= "\t\t\t" . '<a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=delete_item&amp;id=' . $arrLP[$i]['id'] . '&amp;lp_id=' . $this->lp_id . '" onclick="return confirmation(\'' . addslashes($title). '\');">';
							$return .= '<img style="margin:1px;" alt="" src="../img/delete.gif" title="' . get_lang('_delete_learnpath_module') . '" />';
						$return .= '</a>' . "\n";
						
						$return .= "\t\t" . '</td>' . "\n";
					}
					
				$return .= "\t" . '</tr>' . "\n";
			}
			
			if(count($arrLP) == 0)
			{
				$return .= "\t" . '<tr>' . "\n";
					$return .= "\t\t" . '<td colspan="4">'.get_lang("NoItemsInLp").'</td>' . "\n";
				$return .= "\t" . '</tr>' . "\n";
			}
			$return .= '</table>' . "\n";		
		// we need to close the form when we are updating the mp3 files
		
		if ($_GET['updateaudio'] == 'true')
		{
			$return .= '<div style="margin:40px 0; float:right;"><button class="save" type="submit" name="save_audio" id="save_audio">'.get_lang('SaveAudio').'</button></div>';
		}				
				
		
		// we need to close the form when we are updating the mp3 files
		if ($_GET['updateaudio'] == 'true' AND count($arrLP) <> 0)
		{
			$return .= '</form>';	
		}		
			
		return $return;
	}
	/**
	 * This function builds the action menu
	 * 
	 * */
	function build_action_menu()
	{
		echo '<div class="actions">';
		echo '<span>'.Display::return_icon('learnpath_build.gif').' '.get_lang('Build').'</span>';
		echo '<a href="' .api_get_self(). '?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&amp;action=admin_view&amp;lp_id=' . $_SESSION['oLP']->lp_id . '">'.Display::return_icon('learnpath_organize.gif').' '.get_lang("BasicOverview").'</a>';
		echo '<a href="lp_controller.php?cidReq='.Security::remove_XSS($_GET['cidReq']).'&action=view&lp_id='.$_SESSION['oLP']->lp_id.'">'.Display::return_icon('learnpath_view.gif').' '.get_lang('Display').'</a>';
		echo '<a href="' .api_get_self(). '?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&amp;action=add_item&amp;type=chapter&amp;lp_id=' . $_SESSION['oLP']->lp_id . '" title="'.get_lang("NewChapter").'"><img alt="'.get_lang("NewChapter").'" src="../img/lp_dokeos_chapter_add.png" title="'.get_lang("NewChapter").'" />'.get_lang("NewChapter").'</a>';
		echo '<a href="' .api_get_self(). '?cidReq=' . Security::remove_XSS($_GET['cidReq']) . '&amp;action=add_item&amp;type=step&amp;lp_id=' . $_SESSION['oLP']->lp_id . '" title="'.get_lang("NewStep").'"><img alt="'.get_lang("NewStep").'" src="../img/lp_dokeos_step_add.png" title="'.get_lang("NewStep").'" />'.get_lang("NewStep").'</a>';
		echo '</div>';
	}
	
	/**
	 * This functions builds the LP tree based on data from the database.
	 *
	 * @return string
	 * @uses dtree.js :: necessary javascript for building this tree
	 */
	function build_tree()
	{
		$return = "<script type=\"text/javascript\">\n";		
		$return .= "\tm = new dTree('m');\n\n";		
		$return .= "\tm.config.folderLinks		= true;\n";
		$return .= "\tm.config.useCookies		= true;\n";
		$return .= "\tm.config.useIcons			= true;\n";
		$return .= "\tm.config.useLines			= true;\n";
		$return .= "\tm.config.useSelection		= true;\n";
		$return .= "\tm.config.useStatustext	= false;\n\n";
		
		$menu	= 0;
		$parent	= '';
		
		$return .= "\tm.add(" . $menu . ", -1, '" . addslashes($this->name) . "');\n";
		
		$tbl_lp_item = Database::get_course_table('lp_item');
		
		$sql = "
			SELECT *
			FROM " . $tbl_lp_item . "
			WHERE
				lp_id = " . $this->lp_id;
		
		$result = api_sql_query($sql, __FILE__, __LINE__);		
		$arrLP = array();	
		$mycharset=api_get_setting('platform_charset');		
				
		while($row = Database::fetch_array($result))
		{
			
			if($this->encoding!=$mycharset)
			{
				$row['title'] = mb_convert_encoding($row['title'], $mycharset,$this->encoding);
			}		
			
			$arrLP[] = array(
				'id' => $row['id'],
				'item_type' => $row['item_type'],
				'title' => $row['title'],
				'path' => $row['path'],
				'description' => $row['description'],
				'parent_item_id' => $row['parent_item_id'],
				'previous_item_id' => $row['previous_item_id'],
				'next_item_id' => $row['next_item_id'],
                'max_score' => $row['max_score'],
                'min_score' => $row['min_score'],
                'mastery_score' => $row['mastery_score'],
				'display_order' => $row['display_order']);
		}

		$this->tree_array($arrLP);
		
		$arrLP = $this->arrMenu;
		
		unset($this->arrMenu);
		$title='';
		for($i = 0; $i < count($arrLP); $i++)
		{
			$title = addslashes($arrLP[$i]['title']); 
			$menu_page = api_get_self() . '?cidReq=' . $_GET['cidReq'] . '&amp;action=view_item&amp;id=' . $arrLP[$i]['id'] . '&amp;lp_id=' . $_SESSION['oLP']->lp_id;
			if(file_exists("../img/lp_" . $arrLP[$i]['item_type'] . ".png"))
			{
				$return .= "\tm.add(" . $arrLP[$i]['id'] . ", " . $arrLP[$i]['parent_item_id'] . ", '" . $title . "', '" . $menu_page . "', '', '', '../img/lp_" . $arrLP[$i]['item_type'] . ".png', '../img/lp_" . $arrLP[$i]['item_type'] . ".png');\n";
			}
			else if(file_exists("../img/lp_" . $arrLP[$i]['item_type'] . ".gif"))
			{
				$return .= "\tm.add(" . $arrLP[$i]['id'] . ", " . $arrLP[$i]['parent_item_id'] . ", '" . $title . "', '" . $menu_page . "', '', '', '../img/lp_" . $arrLP[$i]['item_type'] . ".gif', '../img/lp_" . $arrLP[$i]['item_type'] . ".gif');\n";
			}
			else
			{
				$return .= "\tm.add(" . $arrLP[$i]['id'] . ", " . $arrLP[$i]['parent_item_id'] . ", '" . $title . "', '" . $menu_page . "', '', '', '../img/lp_document.png', '../img/lp_document.png');\n";
			}
			if($menu < $arrLP[$i]['id'])
				$menu = $arrLP[$i]['id'];
		}
		
		$return .= "\n\tdocument.write(m);\n";
		$return .= "\t if(!m.selectedNode) m.s(1);";
		$return .= "</script>\n";
		
		return $return;
	}
	
	/**
	 * Create a new document //still needs some finetuning
	 *
	 * @param array $_course
	 * @return string
	 */
	function create_document($_course)
	{
		$dir = isset($_GET['dir']) ? $_GET['dir'] : $_POST['dir']; // please do not modify this dirname formatting
		
		if(strstr($dir, '..'))
			$dir = '/';
		
		if($dir[0] == '.')
			$dir = substr($dir, 1);
		
		if($dir[0] != '/')
			$dir = '/'.$dir;
		
		if($dir[strlen($dir) - 1] != '/')
			$dir .= '/';
		
		$filepath = api_get_path('SYS_COURSE_PATH') . $_course['path'] . '/document' . $dir;
		
		if(!is_dir($filepath))
		{
			$filepath = api_get_path('SYS_COURSE_PATH') . $_course['path'] . '/document/';
			
			$dir = '/';
		}
		
		//stripslashes before calling replace_dangerous_char() because $_POST['title']
		//is already escaped twice when it gets here
		$title		= replace_dangerous_char(stripslashes($_POST['title']));
        $title      = disable_dangerous_file($title);
        $title      = replace_accents($title);
		
        $filename	= $title;
		$content	= $_POST['content_lp'];
		
		$tmp_filename = $filename;
									
		$i=0;
		while(file_exists($filepath . $tmp_filename . '.html'))
			$tmp_filename = $filename . '_' . ++$i;
									
		$filename = $tmp_filename . '.html';
		
		$content = stripslashes(text_filter($content));
			
		$content = str_replace(api_get_path('WEB_COURSE_PATH'), api_get_path(REL_PATH).'courses/', $content);
		
		// change the path of mp3 to absolute
		// first regexp deals with ../../../ urls
		$content = preg_replace("|(flashvars=\"file=)(\.+/)+|","$1".api_get_path(REL_COURSE_PATH).$_course['path'].'/document/',$content);
		//second regexp deals with audio/ urls
		$content = preg_replace("|(flashvars=\"file=)([^/]+)/|","$1".api_get_path(REL_COURSE_PATH).$_course['path'].'/document/$2/',$content);		 
				
		
		// for flv player : to prevent edition problem with firefox, we have to use a strange tip (don't blame me please)
		$content = str_replace('</body>','<style type="text/css">body{}</style></body>',$content);
		
		if(!file_exists($filepath . $filename))
		{
			if($fp = @fopen($filepath . $filename, 'w'))
			{	
				fputs($fp, $content);
				fclose($fp);
											
				$file_size = filesize($filepath . $filename);
				$save_file_path = $dir . $filename;
											
				$document_id = add_document($_course, $save_file_path, 'file', $file_size, $filename . '.html');
											
				if($document_id)
				{
					api_item_property_update($_course, TOOL_DOCUMENT, $document_id, 'DocumentAdded', api_get_user_id());
									
					//update parent folders
					//item_property_update_on_folder($_course, $_GET['dir'], $_user['user_id']);
									
					$new_comment = (isset($_POST['comment'])) ? trim($_POST['comment']) : '';
					$new_title = (isset($_POST['title'])) ? trim($_POST['title']) : '';
												
					if($new_comment || $new_title)
					{
						$tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
						$ct = '';
						
						if($new_comment)
							$ct .= ", comment='" . $new_comment . "'";
						
						if($new_title)
							$ct .= ", title='" . $new_title . ".html	'";
						
						$sql_update = "
							UPDATE " . $tbl_doc . "
							SET " . substr($ct, 1) . "
							WHERE id = " . $document_id;
						api_sql_query($sql_update, __FILE__, __LINE__);
					}
				}
											
				return $document_id;
			}
		}
	}
	
	/**
	 * Edit a document based on $_POST and $_GET parameters 'dir' and 'path'
	 *
	 * @param 	array $_course array
	 * @return 	void
	 */
	function edit_document($_course)
	{
		global $_configuration;
		
		
		$dir = isset($_GET['dir']) ? $_GET['dir'] : $_POST['dir']; // please do not modify this dirname formatting
		
		if(strstr($dir, '..'))
			$dir = '/';
		
		if($dir[0] == '.')
			$dir = substr($dir, 1);
		
		if($dir[0] != '/')
			$dir = '/'.$dir;
		
		if($dir[strlen($dir) - 1] != '/')
			$dir .= '/';
		
		$filepath = api_get_path('SYS_COURSE_PATH') . $_course['path'] . '/document'.$dir;
		
		if(!is_dir($filepath))
		{
			$filepath = api_get_path('SYS_COURSE_PATH') . $_course['path'] . '/document/';
			
			$dir = '/';
		}
		
		$table_doc = Database::get_course_table(TABLE_DOCUMENT);
		
		$sql = "
			SELECT path
			FROM " . $table_doc . "
			WHERE id = " . $_POST['path'];
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$row = Database::fetch_array($res);
		$content	= stripslashes($_POST['content_lp']);
		$file		= $filepath . $row['path'];
		
		
		if($fp = @fopen($file, 'w'))
		{
			$content = text_filter($content);
			$content = str_replace(api_get_path('WEB_COURSE_PATH'), $_configuration['url_append'].'/courses/', $content);
			
			// change the path of mp3 to absolute
			// first regexp deals with ../../../ urls
			$content = preg_replace("|(flashvars=\"file=)(\.+/)+|","$1".api_get_path(REL_COURSE_PATH).$_course['path'].'/document/',$content);
			//second regexp deals with audio/ urls
			$content = preg_replace("|(flashvars=\"file=)([^/]+)/|","$1".api_get_path(REL_COURSE_PATH).$_course['path'].'/document/$2/',$content);		 
									
			fputs($fp, $content);
			fclose($fp);
		}
	}
	
	/**
	 * Displays the selected item, with a panel for manipulating the item
	 *
	 * @param int $item_id
	 * @param string $msg
	 * @return string
	 */
	function display_item($item_id, $iframe = true, $msg = '')
	{
		global $_course; //will disappear
		
		$return = '';
		
		if(is_numeric($item_id))
		{
			$tbl_lp_item	= Database::get_course_table('lp_item');
			$tbl_doc		= Database::get_course_table(TABLE_DOCUMENT);
			$sql = "
				SELECT
					lp.*
				FROM " . $tbl_lp_item . " as lp
				WHERE
					lp.id = " . $item_id;
			
			$result = api_sql_query($sql, __FILE__, __LINE__);
			
			while($row = Database::fetch_array($result))
			{				
				$_SESSION['parent_item_id'] = ($row['item_type']=='dokeos_chapter' || $row['item_type']=='dokeos_module' || $row['item_type']=='dir')?$item_id:0;	
				 								
				$return .= $this->display_manipulate($item_id, $row['item_type']);
				
				$return .= '<div style="padding:10px;">';
				
				if($msg != '')
					$return .= $msg;
				
				if($this->encoding=='UTF-8')
				{
					$row['title'] = utf8_decode($row['title']);
				}		
				
				$return .= '<p class="lp_title">' . stripslashes($row['title']) . '</p>';
				//$return .= '<p class="lp_text">' . ((trim($row['description']) == '') ? 'no description' : stripslashes($row['description'])) . '</p>';
				
				//$return .= '<hr />';
				
				if($row['item_type'] == TOOL_DOCUMENT)
				{
					$tbl_doc = Database :: get_course_table(TABLE_DOCUMENT);
					$sql_doc = "SELECT path FROM " . $tbl_doc . " WHERE id = " . $row['path'];
					$result=api_sql_query($sql_doc, __FILE__, __LINE__);
					$path_file=Database::result($result,0,0);					
					$path_parts = pathinfo($path_file);
					
					if(in_array($path_parts['extension'],array('html','txt','png', 'jpg', 'JPG', 'jpeg', 'JPEG', 'gif', 'swf')))
					{
						$return .= $this->display_document($row['path'], true, true);
					}
				}
					
				$return .= '</div>';
			}
		}
		
		return $return;
	}
	
	/**
	 * Shows the needed forms for editing a specific item
	 *
	 * @param int $item_id
	 * @return string
	 */
	function display_edit_item($item_id)
	{
		global $_course; //will disappear
	
		$return = '';
		
		if(is_numeric($item_id))
		{
			$tbl_lp_item = Database::get_course_table('lp_item');
			
			$sql = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE id = " . $item_id;
			
			$res = api_sql_query($sql, __FILE__, __LINE__);
			$row = Database::fetch_array($res);
			
			switch($row['item_type'])
			{
				case 'dokeos_chapter': case 'dir' : case 'asset' : case 'sco' :
					if(isset($_GET['view']) && $_GET['view'] == 'build')
					{
						$return .= $this->display_manipulate($item_id, $row['item_type']);
						$return .= $this->display_item_form($row['item_type'], get_lang("EditCurrentChapter").' :', 'edit', $item_id, $row);
					}
					else
					{
						$return .= $this->display_item_small_form($row['item_type'], get_lang("EditCurrentChapter").' :', $row);
					}
					
					break;
					
				case TOOL_DOCUMENT:
				
					$tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
			
					$sql_step = "
						SELECT
							lp.*,
							doc.path as dir
						FROM " . $tbl_lp_item . " as lp
						LEFT JOIN " . $tbl_doc . " as doc ON doc.id = lp.path
						WHERE
							lp.id = " . $item_id;
					$res_step = api_sql_query($sql_step, __FILE__, __LINE__);
					$row_step = Database::fetch_array($res_step);
					
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_document_form('edit', $item_id, $row_step);
					
					break;
				
				case TOOL_LINK:
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_link_form('edit', $item_id, $row);
					
					break;
				
				case 'dokeos_module':
				
					if(isset($_GET['view']) && $_GET['view'] == 'build')
					{
						$return .= $this->display_manipulate($item_id, $row['item_type']);
						$return .= $this->display_item_form($row['item_type'], get_lang("EditCurrentModule").' :', 'edit', $item_id, $row);
					}
					else
					{
						$return .= $this->display_item_small_form($row['item_type'], get_lang("EditCurrentModule").' :', $row);
					}
		
					break;
				
				case TOOL_QUIZ:
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_quiz_form('edit', $item_id, $row);
					
					break;
					
					case TOOL_HOTPOTATOES:

					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_hotpotatoes_form('edit', $item_id, $row);

					break;
			
				
				case TOOL_STUDENTPUBLICATION:
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_student_publication_form('edit', $item_id, $row);
					
					break;
					
				case TOOL_FORUM:
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_forum_form('edit', $item_id, $row);
					
					break;
					
				case TOOL_THREAD:
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_thread_form('edit', $item_id, $row);
					
					break;
			}
		}
		
		return $return;
	}
	
	/**
	 * Function that displays a list with al the resources that could be added to the learning path
	 *
	 * @return string
	 */
	function display_resources()
	{
		global $_course; //TODO: don't use globals
		
			$return .= '<div class="sectiontitle">'.get_lang('CreateNewStep').'</div>';
		
			$return .= '<div class="sectioncomment"><a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_DOCUMENT . '&amp;lp_id=' . $_SESSION['oLP']->lp_id . '">'.'<img title="Nuevo documento" src="../img/new_doc.gif" alt="Nuevo documento"/> '.get_lang("NewDocument").'</a></div>';
		
			$return .= '<div class="sectiontitle">'.get_lang('UseAnExistingResource').'</div>';
			
			$return .= '<div class="sectioncomment">';
			
			/* get all the docs */
			$return .= $this->get_documents();
			
			/* get all the exercises */
			$return .= $this->get_exercises();
			
			/* get all the links */
			$return .= $this->get_links();
			
			/* get al the student publications */
			$return .= $this->get_student_publications();
			
			/* get al the forums */
			$return .= $this->get_forums();
		
			$return .= '</div>';
		
		return $return;
	}
	
	/**
	 * Returns the extension of a document
	 *
	 * @param unknown_type $filename
	 * @return unknown
	 */
	function get_extension($filename)
	{
		$explode = explode('.', $filename);
		
		return $explode[count($explode) - 1];
	}
	
	/**
	 * Displays a document by id
	 *
	 * @param unknown_type $id
	 * @return unknown
	 */
	function display_document($id, $show_title = false, $iframe = true, $edit_link = false)
	{
		global $_course; //temporary
			
		$return = '';
		
		$tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
		
		$sql_doc = "
			SELECT *
			FROM " . $tbl_doc . "
			WHERE id = " . $id;
		$res_doc = api_sql_query($sql_doc, __FILE__, __LINE__);	
		$row_doc = Database::fetch_array($res_doc);
		
		//if($show_title)
			//$return .= '<p class="lp_title">' . $row_doc['title'] . ($edit_link ? ' [ <a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_DOCUMENT . '&amp;file=' . $_GET['file'] . '&amp;edit=true&amp;lp_id=' . $_GET['lp_id'] . '">Edit this document</a> ]' : '') . '</p>';
		
		//TODO: add a path filter
		if($iframe){
			$return .= '<iframe id="learnpath_preview_frame" frameborder="0" height="400" width="100%" scrolling="auto" src="' . api_get_path(WEB_COURSE_PATH) . $_course['path'] . '/document' . str_replace('%2F','/',urlencode($row_doc['path'])) . '?'.api_get_cidreq().'"></iframe>';
		}
		else{
			$return .= file_get_contents(api_get_path(SYS_COURSE_PATH) . $_course['path'] . '/document' . $row_doc['path']);
		}
		
		return $return;
	}
	
	/**
	 * Return HTML form to add/edit a quiz
	 *
	 * @param	string	Action (add/edit)
	 * @param	integer	Item ID if already exists
	 * @param	mixed	Extra information (quiz ID if integer)
	 * @return	string	HTML form
	 */
	function display_quiz_form($action = 'add', $id = 0, $extra_info = '')
	{
		global $charset;
		
		$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
		$tbl_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
		
		if($id != 0 && is_array($extra_info)) {
			$item_title			= stripslashes($extra_info['title']);
			$item_description	= stripslashes($extra_info['description']);
		} elseif(is_numeric($extra_info)) {
			$sql_quiz = "
				SELECT
					title,
					description
				FROM " . $tbl_quiz . "
				WHERE id = " . $extra_info;
			
			$result = api_sql_query($sql_quiz, __FILE__, __LINE__);
			$row = Database::fetch_array($result);			
			$item_title = $row['title'];
			$item_description = $row['description'];
		} else {
			$item_title			= '';
			$item_description	= '';
		}
		
		$item_title = mb_convert_encoding($item_title,$charset,$this->encoding);		
		$return = '<div class="sectiontitle">';			
			if($id != 0 && is_array($extra_info))
				$parent = $extra_info['parent_item_id'];
			else
				$parent = 0;
			
			$sql = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE
					lp_id = " . $this->lp_id;
			
			$result = api_sql_query($sql, __FILE__, __LINE__);			
			$arrLP = array();			
			while($row = Database::fetch_array($result)) {
				$arrLP[] = array(
					'id' => $row['id'],
					'item_type' => $row['item_type'],
					'title' => $row['title'],
					'path' => $row['path'],
					'description' => $row['description'],
					'parent_item_id' => $row['parent_item_id'],
					'previous_item_id' => $row['previous_item_id'],
					'next_item_id' => $row['next_item_id'],
					'display_order' => $row['display_order'],
                    'max_score' => $row['max_score'],
                    'min_score' => $row['min_score'],
                    'mastery_score' => $row['mastery_score'],
					'prerequisite' => $row['prerequisite'],
					'max_time_allowed' => $row['max_time_allowed']);
			}
			
			$this->tree_array($arrLP);			
			$arrLP = $this->arrMenu;			
			unset($this->arrMenu);
			
			if($action == 'add')
				$return .= get_lang("CreateTheExercise").'&nbsp;:' . "\n";
			elseif($action == 'move')
				$return .= get_lang("MoveTheCurrentExercise").'&nbsp;:' . "\n";
			else
				$return .= get_lang("EditCurrentExecice").'&nbsp;:' . "\n";
			
			if(isset($_GET['edit']) && $_GET['edit'] == 'true') {
				$return .= '<div class="warning-message">';				
					$return .= '<p class="lp_title">'.get_lang("Warning").' !</p>';
					$return .= get_lang("WarningEditingDocument");				
				$return .= '</div>';
			}
			$return .= '</div>';
			$return .= '<div class="sectioncomment">';
			
			$return .= '<form method="POST">' . "\n";			
			$return .= "\t" . '<table>' . "\n";
				
					if($action != 'move') {
						$return .= "\t\t" . '<tr>' . "\n";							
							$return .= "\t\t\t" . '<td class="label"><label for="idTitle">'.get_lang('Title').'</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><input id="idTitle" name="title" style="width:98%;" type="text" class="learnpath_item_form" value="' . $item_title . '" /></td>' . "\n";						
							$return .= "\t\t" . '</tr>' . "\n";
					}				
				
					$return .= "\t\t" . '<tr>' . "\n";
					
						$return .= "\t\t\t" . '<td class="label"><label for="idParent">'.get_lang('Parent').'</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idParent" style="width:100%;" name="parent" onchange="load_cbo(this.value);" class="learnpath_item_form" size="1">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">' . $this->name . '</option>';
								
								$arrHide = array($id);
								$parent_item_id = $_SESSION['parent_item_id'];
								for($i = 0; $i < count($arrLP); $i++) {
									if($action != 'add') {
										if(($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide)) {
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding) . '</option>';
										} else {
											$arrHide[] = $arrLP[$i]['id'];
										}
									} else {
										if($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
											$return .= "\t\t\t\t\t" . '<option ' . (($parent_item_id == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding) . '</option>';
									}
								}
								if (is_array($arrLP)) {
									reset($arrLP);	
								}
								
							$return .= "\t\t\t\t" . '</select>';						
						$return .= "\t\t\t" . '</td>' . "\n";					
					$return .= "\t\t" . '</tr>' . "\n";									
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td class="label"><label for="idPosition">'.get_lang('Position').'</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select class="learnpath_item_form" style="width:100%;" id="idPosition" name="previous" size="1">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">'.get_lang('FirstPosition').'</option>';
								
								for($i = 0; $i < count($arrLP); $i++) {
									if($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id)
									{
										if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
											$selected = 'selected="selected" ';
										elseif($action == 'add')
											$selected = 'selected="selected" ';
										else
											$selected = '';
										
										$return .= "\t\t\t\t\t" . '<option ' . $selected . 'value="' . $arrLP[$i]['id'] . '">'.get_lang("After").' "' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding) . '"</option>';
									}
								}
								
							$return .= "\t\t\t\t" . '</select>';						
						$return .= "\t\t\t" . '</td>' . "\n";					
					$return .= "\t\t" . '</tr>' . "\n";					
					if($action != 'move') {
						$id_prerequisite=0;
						if (is_array($arrLP )) {
							foreach($arrLP as $key=>$value){
								if($value['id']==$id){
									$id_prerequisite=$value['prerequisite'];
									break;
								}
							}
						}						
						$arrHide=array();
						for($i = 0; $i < count($arrLP); $i++) {
							if($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter') {
								if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
									$s_selected_position=$arrLP[$i]['id'];
								elseif($action == 'add')
									$s_selected_position=0;
								$arrHide[$arrLP[$i]['id']]['value']=mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding);								
							}
						}
						/*//comented the prerequisites, only visible in edit (exercise)
						$return .= "\t\t" . '<tr>' . "\n";							
						$return .= "\t\t\t" . '<td class="label"><label for="idPrerequisites">'.get_lang('Prerequisites').'</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input"><select name="prerequisites" id="prerequisites" class="learnpath_item_form"><option value="0">'.get_lang("NoPrerequisites").'</option>';
							
							foreach($arrHide as $key => $value){
								if($key==$s_selected_position && $action == 'add'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								elseif($key==$id_prerequisite && $action == 'edit'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								else{
									$return .= '<option value="'.$key.'">'.$value['value'].'</option>';
								}
							}
							
						$return .= "</select></td>";
						*/
						$return .= "\t\t" . '</tr>' . "\n";						
						$return .= "\t\t" . '<tr>' . "\n";						
						$return .= "\t\t\t" . '<td class="label"><label for="maxTimeAllowed">'.get_lang('MaxTimeAllowed').'</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input"><input name="maxTimeAllowed" style="width:98%;" id="maxTimeAllowed" value="' . $extra_info['max_time_allowed'] . '" /></td>';
							
							//Remove temporaly the test description
							//$return .= "\t\t\t" . '<td class="label"><label for="idDescription">'.get_lang("Description").' :</label></td>' . "\n";
							//$return .= "\t\t\t" . '<td class="input"><textarea id="idDescription" name="description" rows="4">' . $item_description . '</textarea></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
					}
					
					$return .= "\t\t" . '<tr>' . "\n";						
						$return .= "\t\t\t" . '<td>&nbsp;</td><td><button class="save" name="submit_button" type="submit">'.get_lang('AddExercise').'</button></td>' . "\n";					
					$return .= "\t\t" . '</tr>' . "\n";				
				$return .= "\t" . '</table>' . "\n";	
				
				if($action == 'move') {
					$return .= "\t" . '<input name="title" type="hidden" value="' . $item_title . '" />' . "\n";
					$return .= "\t" . '<input name="description" type="hidden" value="' . $item_description . '" />' . "\n";
				}
				
				if(is_numeric($extra_info))	{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info . '" />' . "\n";
				} elseif(is_array($extra_info)) {
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info['path'] . '" />' . "\n";
				}
				
				$return .= "\t" . '<input name="type" type="hidden" value="'.TOOL_QUIZ.'" />' . "\n";
				$return .= "\t" . '<input name="post_time" type="hidden" value="' . time() . '" />' . "\n";
				
			$return .= '</form>' . "\n";		
		$return .= '</div>' . "\n";	
		return $return;
	}

/**
 * Addition of Hotpotatoes tests
 * @param	string	Action
 * @param	integer	Internal ID of the item
 * @param	mixed	Extra information - can be an array with title and description indexes
 * @return  string	HTML structure to display the hotpotatoes addition formular
 */
	function display_hotpotatoes_form($action = 'add', $id = 0, $extra_info = '')
	{
		global $charset;
		$uploadPath = DIR_HOTPOTATOES; //defined in main_api
		$tbl_lp_item = Database::get_course_table('lp_item');

		if($id != 0 && is_array($extra_info))
		{
			$item_title			= stripslashes($extra_info['title']);
			$item_description	= stripslashes($extra_info['description']);
		}
		elseif(is_numeric($extra_info))
		{
			$TBL_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);

			$sql_hot = "SELECT * FROM ".$TBL_DOCUMENT."
			WHERE path LIKE '".$uploadPath."/%/%htm%'
			ORDER BY id ASC";
		
				
		  	$res_hot = api_sql_query($sql_hot, __FILE__, __LINE__);

			$row = Database::fetch_array($res_hot);

			$item_title = $row['title'];
			$item_description = $row['description'];
		}
		else
		{
			$item_title			= '';
			$item_description	= '';
		}
				
		$item_title=mb_convert_encoding($item_title,$charset,$this->encoding);
		$item_description=mb_convert_encoding($item_description,$charset,$this->encoding);
		
		$return = '<div style="margin:3px 12px;">';

			if($id != 0 && is_array($extra_info))
				$parent = $extra_info['parent_item_id'];
			else
				$parent = 0;

			$sql = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE
					lp_id = " . $this->lp_id;

			$result = api_sql_query($sql, __FILE__, __LINE__);

			$arrLP = array();

			while($row = Database::fetch_array($result))
			{
				$arrLP[] = array(
					'id' => $row['id'],
					'item_type' => $row['item_type'],
					'title' => $row['title'],
					'path' => $row['path'],
					'description' => $row['description'],
					'parent_item_id' => $row['parent_item_id'],
					'previous_item_id' => $row['previous_item_id'],
					'next_item_id' => $row['next_item_id'],
					'display_order' => $row['display_order'],
                    'max_score' => $row['max_score'],
                    'min_score' => $row['min_score'],
                    'mastery_score' => $row['mastery_score'],
					'prerequisite' => $row['prerequisite'],
					'max_time_allowed' => $row['max_time_allowed']);
			}

			$this->tree_array($arrLP);

			$arrLP = $this->arrMenu;

			unset($this->arrMenu);

			if($action == 'add')
				$return .= '<p class="lp_title">'.get_lang("CreateTheExercise").'&nbsp;:</p>' . "\n";
			elseif($action == 'move')
				$return .= '<p class="lp_title">'.get_lang("MoveTheCurrentExercise").'&nbsp;:</p>' . "\n";
			else
				$return .= '<p class="lp_title">'.get_lang("EditCurrentExecice").'&nbsp;:</p>' . "\n";

			if(isset($_GET['edit']) && $_GET['edit'] == 'true')
			{
				$return .= '<div class="warning-message">';

					$return .= '<p class="lp_title">'.get_lang("Warning").' !</p>';
					$return .= get_lang("WarningEditingDocument");

				$return .= '</div>';
			}

			$return .= '<form method="POST">' . "\n";

				$return .= "\t" . '<table cellpadding="0" cellspacing="0" class="lp_form">' . "\n";

					$return .= "\t\t" . '<tr>' . "\n";

						$return .= "\t\t\t" . '<td class="label"><label for="idParent">'.get_lang("Parent").' :</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";

							$return .= "\t\t\t\t" . '<select id="idParent" name="parent" onchange="load_cbo(this.value);" size="1">';

								$return .= "\t\t\t\t\t" . '<option class="top" value="0">' . $this->name . '</option>';

								$arrHide = array($id);

								for($i = 0; $i < count($arrLP); $i++)
								{
									if($action != 'add')
									{
										if(($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide))
										{
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding) . '</option>';
										}
										else
										{
											$arrHide[] = $arrLP[$i]['id'];
										}
									}
									else
									{
										if($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding) . '</option>';
									}
								}

								reset($arrLP);

							$return .= "\t\t\t\t" . '</select>';

						$return .= "\t\t\t" . '</td>' . "\n";

					$return .= "\t\t" . '</tr>' . "\n";

					$return .= "\t\t" . '<tr>' . "\n";

						$return .= "\t\t\t" . '<td class="label"><label for="idPosition">'.get_lang("Position").' :</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";

							$return .= "\t\t\t\t" . '<select id="idPosition" name="previous" size="1">';

								$return .= "\t\t\t\t\t" . '<option class="top" value="0">'.get_lang('FirstPosition').'</option>';

								for($i = 0; $i < count($arrLP); $i++)
								{
									if($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id)
									{
										if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
											$selected = 'selected="selected" ';
										elseif($action == 'add')
											$selected = 'selected="selected" ';
										else
											$selected = '';

										$return .= "\t\t\t\t\t" . '<option ' . $selected . 'value="' . $arrLP[$i]['id'] . '">'.get_lang("After").' "' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding) . '"</option>';
									}
								}

							$return .= "\t\t\t\t" . '</select>';

						$return .= "\t\t\t" . '</td>' . "\n";

					$return .= "\t\t" . '</tr>' . "\n";

					if($action != 'move')
					{
						$return .= "\t\t" . '<tr>' . "\n";

							$return .= "\t\t\t" . '<td class="label"><label for="idTitle">'.get_lang("Title").' :</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><input id="idTitle" name="title" type="text" value="' . $item_title . '" /></td>' . "\n";

						$return .= "\t\t" . '</tr>' . "\n";


						$id_prerequisite=0;
						foreach($arrLP as $key=>$value){
							if($value['id']==$id){
								$id_prerequisite=$value['prerequisite'];
								break;
							}
						}

						$arrHide=array();
						for($i = 0; $i < count($arrLP); $i++)
						{
							if($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter')
							{
								if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
									$s_selected_position=$arrLP[$i]['id'];
								elseif($action == 'add')
									$s_selected_position=0;
								$arrHide[$arrLP[$i]['id']]['value']=mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding);

							}
						}

						$return .= "\t\t" . '<tr>' . "\n";

							$return .= "\t\t\t" . '<td class="label"><label for="idPrerequisites">'.get_lang("Prerequisites").' :</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><select name="prerequisites" id="prerequisites"><option value="0">'.get_lang("NoPrerequisites").'</option>';

							foreach($arrHide as $key => $value){
								if($key==$s_selected_position && $action == 'add'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								elseif($key==$id_prerequisite && $action == 'edit'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								else{
									$return .= '<option value="'.$key.'">'.$value['value'].'</option>';
								}
							}

							$return .= "</select></td>";

						$return .= "\t\t" . '</tr>' . "\n";

						$return .= "\t\t" . '<tr>' . "\n";

							//Remove temporaly the test description
							//$return .= "\t\t\t" . '<td class="label"><label for="idDescription">'.get_lang("Description").' :</label></td>' . "\n";
							//$return .= "\t\t\t" . '<td class="input"><textarea id="idDescription" name="description" rows="4">' . $item_description . '</textarea></td>' . "\n";

						$return .= "\t\t" . '</tr>' . "\n";
					}

					$return .= "\t\t" . '<tr>' . "\n";

						$return .= "\t\t\t" . '<td colspan="2"><input class="button" name="submit_button" type="submit" value="'.get_lang('Ok').'" /></td>' . "\n";

					$return .= "\t\t" . '</tr>' . "\n";

				$return .= "\t" . '</table>' . "\n";

				if($action == 'move')
				{
					$return .= "\t" . '<input name="title" type="hidden" value="' . $item_title . '" />' . "\n";
					$return .= "\t" . '<input name="description" type="hidden" value="' . $item_description . '" />' . "\n";
				}

				if(is_numeric($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info . '" />' . "\n";
				}
				elseif(is_array($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info['path'] . '" />' . "\n";
				}

				$return .= "\t" . '<input name="type" type="hidden" value="'.TOOL_HOTPOTATOES.'" />' . "\n";
				$return .= "\t" . '<input name="post_time" type="hidden" value="' . time() . '" />' . "\n";

			$return .= '</form>' . "\n";

		$return .= '</div>' . "\n";
		return $return;
	}

//fin du hotpot form


	
/**
	 * Return the form to display the forum edit/add option
	 *
	 * @param	string	Action (add/edit)
	 * @param	integer	ID of the lp_item if already exists
	 * @param	mixed	Forum ID or title
	 * @return	string	HTML form
	 */
	function display_forum_form($action = 'add', $id = 0, $extra_info = '')
	{
		global $charset;
		
		$tbl_lp_item = Database::get_course_table('lp_item');
		$tbl_forum = Database::get_course_table(TABLE_FORUM);
		
		if($id != 0 && is_array($extra_info)) {
			$item_title			= stripslashes($extra_info['title']);
		} elseif(is_numeric($extra_info)) {
			$sql_forum = "
				SELECT
					forum_title as title, forum_comment as comment 
				FROM " . $tbl_forum . "
				WHERE forum_id = " . $extra_info;
			
			$result = api_sql_query($sql_forum, __FILE__, __LINE__);
			$row = Database::fetch_array($result);
			
			$item_title = $row['title'];
			$item_description = $row['comment'];
		} else {
			$item_title			= '';
			$item_description 	= '';
		}
		
		$item_title=mb_convert_encoding($item_title,$charset,$this->encoding);
		$item_description=mb_convert_encoding($item_description,$charset,$this->encoding);
				
		$return = '<div class="sectiontitle">';
			
			if($id != 0 && is_array($extra_info))
				$parent = $extra_info['parent_item_id'];
			else
				$parent = 0;
			
			$sql = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE
					lp_id = " . $this->lp_id;
			
			$result = api_sql_query($sql, __FILE__, __LINE__);
			
			$arrLP = array();
			
			while($row = Database::fetch_array($result)) {
				$arrLP[] = array(
					'id' => $row['id'],
					'item_type' => $row['item_type'],
					'title' => $row['title'],
					'path' => $row['path'],
					'description' => $row['description'],
					'parent_item_id' => $row['parent_item_id'],
					'previous_item_id' => $row['previous_item_id'],
					'next_item_id' => $row['next_item_id'],
					'display_order' => $row['display_order'],
                    'max_score' => $row['max_score'],
                    'min_score' => $row['min_score'],
                    'mastery_score' => $row['mastery_score'],
					'prerequisite' => $row['prerequisite']);
			}
			
			$this->tree_array($arrLP);			
			$arrLP = $this->arrMenu;
			unset($this->arrMenu);
			
			if($action == 'add')
				$return .= get_lang("CreateTheForum").'&nbsp;:' . "\n";
			elseif($action == 'move')
				$return .= get_lang("MoveTheCurrentForum").'&nbsp;:' . "\n";
			else
				$return .= get_lang("EditCurrentForum").'&nbsp;:' . "\n";
				
			$return .= '</div>';
			$return .= '<div class="sectioncomment">';			
			$return .= '<form method="POST">' . "\n";
			
				$return .= "\t" . '<table>' . "\n";
				
					if($action != 'move') {
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idTitle">'.get_lang('Title').'</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><input id="idTitle" style="width:98%;" name="title" type="text" value="' . $item_title . '" class="learnpath_item_form" /></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
					}				
				
					$return .= "\t\t" . '<tr>' . "\n";
					
						$return .= "\t\t\t" . '<td class="label"><label for="idParent">'.get_lang('Parent').'</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idParent" name="parent" onchange="load_cbo(this.value);" class="learnpath_item_form" size="1">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">' . $this->name . '</option>';
								
								$arrHide = array($id);
								
								$parent_item_id = $_SESSION['parent_item_id'];
								
								for($i = 0; $i < count($arrLP); $i++)
								{
									if($action != 'add')
									{
										if(($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide))
										{
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding) . '</option>';
										}
										else
										{
											$arrHide[] = $arrLP[$i]['id'];
										}
									}
									else
									{
										if($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
											$return .= "\t\t\t\t\t" . '<option ' . (($parent_item_id == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding) . '</option>';
									}
								}
								if (is_array($arrLP)) {								
									reset($arrLP);
								}
								
							$return .= "\t\t\t\t" . '</select>';						
						$return .= "\t\t\t" . '</td>' . "\n";					
					$return .= "\t\t" . '</tr>' . "\n";
									
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td class="label"><label for="idPosition">'.get_lang('Position').'</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idPosition" name="previous" style="width:100%;" size="1" class="learnpath_item_form">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">'.get_lang('FirstPosition').'</option>';
								
								for($i = 0; $i < count($arrLP); $i++)
								{
									if($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id)
									{
										if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
											$selected = 'selected="selected" ';
										elseif($action == 'add')
											$selected = 'selected="selected" ';
										else
											$selected = '';
										
										$return .= "\t\t\t\t\t" . '<option ' . $selected . 'value="' . $arrLP[$i]['id'] . '">'.get_lang("After").' "' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding) . '"</option>';
									}
								}
								
							$return .= "\t\t\t\t" . '</select>';
						
						$return .= "\t\t\t" . '</td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
					
					if($action != 'move')
					{
						$return .= "\t\t" . '<tr>' . "\n";
							
							//Remove temporaly the test description
							//$return .= "\t\t\t" . '<td class="label"><label for="idDescription">'.get_lang("Description").' :</label></td>' . "\n";
							//$return .= "\t\t\t" . '<td class="input"><textarea id="idDescription" name="description" rows="4">' . $item_description . '</textarea></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
						$id_prerequisite=0;
						if (is_array($arrLP)) {
							foreach($arrLP as $key=>$value){
								if($value['id']==$id){
									$id_prerequisite=$value['prerequisite'];
									break;
								}
							}
						}
						
						$arrHide=array();
						for($i = 0; $i < count($arrLP); $i++) {
							if($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter') {
								if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
									$s_selected_position=$arrLP[$i]['id'];
								elseif($action == 'add')
									$s_selected_position=0;
								$arrHide[$arrLP[$i]['id']]['value']=mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding);								
							}
						}

					/*	//comented the prerequisites, only visible in edit (forum)
						$return .= "\t\t" . '<tr>' . "\n";	
						$return .= "\t\t\t" . '<td class="label"><label for="idPrerequisites">'.get_lang('Prerequisites').'</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input"><select name="prerequisites" id="prerequisites" class="learnpath_item_form"><option value="0">'.get_lang("NoPrerequisites").'</option>';
							
							foreach($arrHide as $key => $value) {
								if($key==$s_selected_position && $action == 'add') {
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}elseif($key==$id_prerequisite && $action == 'edit') {
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								} else {
									$return .= '<option value="'.$key.'">'.$value['value'].'</option>';
								}
							}
							
							$return .= "</select></td>";
					*/
						$return .= "\t\t" . '</tr>' . "\n";						
					}
					
					$return .= "\t\t" . '<tr>' . "\n";						
						$return .= "\t\t\t" .'<td>&nbsp;<td colspan="2"><button class="save" name="submit_button" type="submit"> '.get_lang('Ok').'</button></td>' . "\n";					
					$return .= "\t\t" . '</tr>' . "\n";
				
				$return .= "\t" . '</table>' . "\n";	
				
				if($action == 'move') {
					$return .= "\t" . '<input name="title" type="hidden" value="' . $item_title . '" />' . "\n";
					$return .= "\t" . '<input name="description" type="hidden" value="' . $item_description . '" />' . "\n";
				}
				
				if(is_numeric($extra_info)) {
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info . '" />' . "\n";
				} elseif(is_array($extra_info)) {
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info['path'] . '" />' . "\n";
				}
				
				$return .= "\t" . '<input name="type" type="hidden" value="'.TOOL_FORUM.'" />' . "\n";
				$return .= "\t" . '<input name="post_time" type="hidden" value="' . time() . '" />' . "\n";
				
			$return .= '</form>' . "\n";
		
		$return .= '</div>' . "\n";
		return $return;
	}
	
	/**
	 * Return HTML form to add/edit forum threads
	 * @param	string	Action (add/edit)
	 * @param	integer	Item ID if already exists in learning path
	 * @param	mixed	Extra information (thread ID if integer)
	 * @return 	string	HTML form
	 */
	function display_thread_form($action = 'add', $id = 0, $extra_info = '')
	{
		global $charset;
		echo '
		<style>
	
		div.row div.label {
			width:110px;
		}
		
		div.row div.formw {
			width: 82%;
		}
		</style>';
		
		$tbl_lp_item = Database::get_course_table('lp_item');
		$tbl_forum = Database::get_course_table(TABLE_FORUM_THREAD);
		
		if($id != 0 && is_array($extra_info))
		{
			$item_title			= stripslashes($extra_info['title']);
		}
		elseif(is_numeric($extra_info))
		{
			$sql_forum = "
				SELECT
					thread_title as title
				FROM " . $tbl_forum . "
				WHERE thread_id = " . $extra_info;
			
			$result = api_sql_query($sql_forum, __FILE__, __LINE__);
			$row = Database::fetch_array($result);
			
			$item_title = $row['title'];
			$item_description = '';
		}
		else
		{
			$item_title			= '';
			$item_description	= '';
		}
		$item_title=mb_convert_encoding($item_title,$charset,$this->encoding);
		$item_description=mb_convert_encoding($item_description,$charset,$this->encoding);
		
		$return = '<div style="margin:3px 12px;">';
			
			if($id != 0 && is_array($extra_info))
				$parent = $extra_info['parent_item_id'];
			else
				$parent = 0;
			
			$sql = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE
					lp_id = " . $this->lp_id;
			
			$result = api_sql_query($sql, __FILE__, __LINE__);
			
			$arrLP = array();
			
			while($row = Database::fetch_array($result))
			{
				$arrLP[] = array(
					'id' => $row['id'],
					'item_type' => $row['item_type'],
					'title' => $row['title'],
					'path' => $row['path'],
					'description' => $row['description'],
					'parent_item_id' => $row['parent_item_id'],
					'previous_item_id' => $row['previous_item_id'],
					'next_item_id' => $row['next_item_id'],
					'display_order' => $row['display_order'],
                    'max_score' => $row['max_score'],
                    'min_score' => $row['min_score'],
                    'mastery_score' => $row['mastery_score'],
					'prerequisite' => $row['prerequisite']);
			}
			
			$this->tree_array($arrLP);
			
			$arrLP = $this->arrMenu;
			
			unset($this->arrMenu);
			
			if($action == 'add')
				$return .= '<p class="lp_title">'.get_lang("CreateTheForum").'&nbsp;:</p>' . "\n";
			elseif($action == 'move')
				$return .= '<p class="lp_title">'.get_lang("MoveTheCurrentForum").'&nbsp;:</p>' . "\n";
			else
				$return .= '<p class="lp_title">'.get_lang("EditCurrentForum").'&nbsp;:</p>' . "\n";
			
			$return .= '<form method="POST">' . "\n";
			
				$return .= "\t" . '<table cellpadding="0" cellspacing="0" class="lp_form">' . "\n";
				
					$return .= "\t\t" . '<tr>' . "\n";
					
						$return .= "\t\t\t" . '<td class="label"><label for="idParent">'.get_lang("Parent").'&nbsp;:</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idParent" name="parent" onchange="load_cbo(this.value);" size="1">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">' . $this->name . '</option>';
								
								$arrHide = array($id);
								
								for($i = 0; $i < count($arrLP); $i++)
								{
									if($action != 'add')
									{
										if(($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide))
										{
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding) . '</option>';
										}
										else
										{
											$arrHide[] = $arrLP[$i]['id'];
										}
									}
									else
									{
										if($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding) . '</option>';
									}
								}
								if ($arrLP!=null) {
									reset($arrLP);
								}
								
							$return .= "\t\t\t\t" . '</select>';
						
						$return .= "\t\t\t" . '</td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
									
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td class="label"><label for="idPosition">'.get_lang("Position").'&nbsp;:</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idPosition" name="previous" size="1">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">'.get_lang('FirstPosition').'</option>';
								
								for($i = 0; $i < count($arrLP); $i++)
								{
									if($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id)
									{
										if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
											$selected = 'selected="selected" ';
										elseif($action == 'add')
											$selected = 'selected="selected" ';
										else
											$selected = '';
										
										$return .= "\t\t\t\t\t" . '<option ' . $selected . 'value="' . $arrLP[$i]['id'] . '">'.get_lang("After").' "' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding) . '"</option>';
									}
								}
								
							$return .= "\t\t\t\t" . '</select>';
						
						$return .= "\t\t\t" . '</td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
					
					if($action != 'move')
					{
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idTitle">'.get_lang("Title").'&nbsp;:</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><input id="idTitle" name="title" type="text" value="' . $item_title . '" /></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
						$return .= "\t\t" . '<tr>' . "\n";
							
							//Remove temporaly the test description
							//$return .= "\t\t\t" . '<td class="label"><label for="idDescription">'.get_lang("Description").' :</label></td>' . "\n";
							//$return .= "\t\t\t" . '<td class="input"><textarea id="idDescription" name="description" rows="4">' . $item_description . '</textarea></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
						$id_prerequisite=0;
						if ($arrLP!=null) {
							foreach($arrLP as $key=>$value){
								if($value['id']==$id){
									$id_prerequisite=$value['prerequisite'];
									break;
								}
							}	
						}

						
						$arrHide=array();
						for($i = 0; $i < count($arrLP); $i++)
						{
							if($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter')
							{
								if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
									$s_selected_position=$arrLP[$i]['id'];
								elseif($action == 'add')
									$s_selected_position=0;
								$arrHide[$arrLP[$i]['id']]['value']=mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding);
								
							}
						}
						
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idPrerequisites">'.get_lang("Prerequisites").'&nbsp;:</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><select name="prerequisites" id="prerequisites"><option value="0">'.get_lang("NoPrerequisites").'</option>';
							
							foreach($arrHide as $key => $value){
								if($key==$s_selected_position && $action == 'add'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								elseif($key==$id_prerequisite && $action == 'edit'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								else{
									$return .= '<option value="'.$key.'">'.$value['value'].'</option>';
								}
							}
							
							$return .= "</select></td>";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
					}
					
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td colspan="2"><input class="button" name="submit_button" type="submit" value="'.get_lang('Ok').'" /></td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
				
				$return .= "\t" . '</table>' . "\n";	
				
				if($action == 'move')
				{
					$return .= "\t" . '<input name="title" type="hidden" value="' . $item_title . '" />' . "\n";
					$return .= "\t" . '<input name="description" type="hidden" value="' . $item_description . '" />' . "\n";
				}
				
				if(is_numeric($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info . '" />' . "\n";
				}
				elseif(is_array($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info['path'] . '" />' . "\n";
				}
				
				$return .= "\t" . '<input name="type" type="hidden" value="'.TOOL_THREAD.'" />' . "\n";
				$return .= "\t" . '<input name="post_time" type="hidden" value="' . time() . '" />' . "\n";
				
			$return .= '</form>' . "\n";
		
		$return .= '</div>' . "\n";
		return $return;
	}
	
	/**
	 * Return the HTML form to display an item (generally a section/module item)
	 *
	 * @param	string	Item type (module/dokeos_module)
	 * @param	string	Title (optional, only when creating)
	 * @param	string	Action ('add'/'edit')
	 * @param	integer	lp_item ID
	 * @param	mixed	Extra info
	 * @return	string 	HTML form
	 */
	function display_item_form($item_type, $title = '', $action = 'add', $id = 0, $extra_info = 'new')
	{
		global $_course;
		global $charset;
				
		$tbl_lp_item = Database::get_course_table('lp_item');
	
		if($id != 0 && is_array($extra_info))
		{
			$item_title			= $extra_info['title'];
			$item_description	= $extra_info['description'];
			$item_path = api_get_path(WEB_COURSE_PATH) . $_course['path'].'/scorm/'.$this->path.'/'.stripslashes($extra_info['path']);
		}
		else
		{
			$item_title			= '';
			$item_description	= '';
		}
		
		$return = '<div class="sectiontitle">';
			
		if($id != 0 && is_array($extra_info))
			$parent = $extra_info['parent_item_id'];
		else
			$parent = 0;
		
		$sql = "
			SELECT *
			FROM " . $tbl_lp_item . "
			WHERE lp_id = " . $this->lp_id. " AND id != " . $id. "    ";
		
		if($item_type == 'module')
			$sql .= " AND parent_item_id = 0";
		
		$result = api_sql_query($sql, __FILE__, __LINE__);		
		$arrLP = array();
				
		while($row = Database::fetch_array($result))
		{
			$arrLP[] = array(
				'id' => $row['id'],
				'item_type' => $row['item_type'],
				'title' => $row['title'],
				'path' => $row['path'],
				'description' => $row['description'],
				'parent_item_id' => $row['parent_item_id'],
				'previous_item_id' => $row['previous_item_id'],
				'next_item_id' => $row['next_item_id'],
                'max_score' => $row['max_score'],
                'min_score' => $row['min_score'],
                'mastery_score' => $row['mastery_score'],
                'prerequisite' => $row['prerequisite'],
				'display_order' => $row['display_order']);
		}
				
		$this->tree_array($arrLP);		
		$arrLP = $this->arrMenu;		
		unset($this->arrMenu);
			
		$return .= $title . "\n";
		$return .= '</div>';
		$return .= '<div class="sectioncomment">';
		
		require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');		
		$form = new FormValidator('form','POST',api_get_self()."?".$_SERVER["QUERY_STRING"]);
		
		$defaults["title"]=mb_convert_encoding($item_title,$charset,$this->encoding);
		$defaults["description"]=mb_convert_encoding($item_description,$charset,$this->encoding); 

		$form->addElement('html',$return);
					
		//$arrHide = array($id);
		
		$arrHide[0]['value']=$this->name;
		$arrHide[0]['padding']=3;
		
		if($item_type != 'module' && $item_type != 'dokeos_module')
		{
			for($i = 0; $i < count($arrLP); $i++)
			{
				if($action != 'add')
				{
					if(($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide))
					{
						$arrHide[$arrLP[$i]['id']]['value']=mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding);
						$arrHide[$arrLP[$i]['id']]['padding']=3+ $arrLP[$i]['depth'] * 10;
						if($parent == $arrLP[$i]['id'])
						{
							$s_selected_parent=$arrHide[$arrLP[$i]['id']];
						}
					}
				}
				else
				{
					if($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
					{
						$arrHide[$arrLP[$i]['id']]['value']=mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding);
						$arrHide[$arrLP[$i]['id']]['padding']=3+ $arrLP[$i]['depth'] * 10;
						if($parent == $arrLP[$i]['id'])
						{
							$s_selected_parent=$arrHide[$arrLP[$i]['id']];
						}
					}
				}
			}
			
			if($action != 'move')
			{
				$form->addElement('text','title', get_lang('Title'),'id="idTitle" class="learnpath_chapter_form" size="40%"');
				//$form->addElement('textarea','description',get_lang("Description").' :', 'id="idDescription"');
			}
			else
			{
				$form->addElement('hidden','title');
			}			
			
			$parent_select = &$form->addElement('select', 'parent', get_lang("Parent"), '', 'class="learnpath_chapter_form" style="width:37%;" id="Parent" onchange="load_cbo(this.value);"');

			foreach($arrHide as $key => $value)
			{
				$parent_select->addOption($value['value'],$key,'style="padding-left:'.$value['padding'].'px;"');
			}
			$parent_select -> setSelected($s_selected_parent);			
		}
		if(is_array($arrLP)) { reset($arrLP); }
		
		$arrHide=array();

		//POSITION
		for($i = 0; $i < count($arrLP); $i++)
		{
			if($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id)
			{
				if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
					$s_selected_position=$arrLP[$i]['id'];
				elseif($action == 'add')
					$s_selected_position=$arrLP[$i]['id'];				

				$arrHide[$arrLP[$i]['id']]['value']=get_lang("After").' "' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding);
				
			}
		}
		
		$position = &$form->addElement('select', 'previous', get_lang('Position'), '', 'id="idPosition" class="learnpath_chapter_form" style="width:37%;"');
		
		$position->addOption(get_lang('FirstPosition'),0,'style="padding-left:'.$value['padding'].'px;"');
		
		foreach($arrHide as $key => $value)
		{
			$position->addOption($value['value'],$key,'style="padding-left:'.$value['padding'].'px;"');
		}
		
		if(!empty($s_selected_position)) { $position->setSelected($s_selected_position); }
		
		if(is_array($arrLP)) { reset($arrLP); }
		
		$form->addElement('style_submit_button', 'submit_button', get_lang('Save'),'class="save"');
		
		if($item_type == 'module' || $item_type == 'dokeos_module')
		{
			$form->addElement('hidden', 'parent', '0');
		}		

		$extension = pathinfo($item_path, PATHINFO_EXTENSION);
		if(($item_type=='asset' || $item_type=='sco') && ($extension == 'html' || $extension == 'htm'))
		{
			if($item_type=='sco')
			{
				$form->addElement('html','<script type="text/javascript">alert("'.get_lang('WarningWhenEditingScorm').'")</script>');
			}
			$renderer = $form->defaultRenderer();
			$renderer->setElementTemplate('<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{label}<br />{element}','content_lp');
			$form->addElement('html_editor','content_lp','');
			//$form->addElement('html_editor','content_lp','');
			$defaults["content_lp"]=file_get_contents($item_path);
		}

		$form->addElement('hidden', 'type', 'dokeos_'.$item_type);
		$form->addElement('hidden', 'post_time', time());		
		$form->setDefaults($defaults);
		$form->addElement('html','</div>');
		return $form->return_form();
	}
	
	
	/**
	 * Returns the form to update or create a document
	 *
	 * @param	string	Action (add/edit)
	 * @param	integer	ID of the lp_item (if already exists)
	 * @param	mixed	Integer if document ID, string if info ('new')
	 * @return	string	HTML form
	 */
	function display_document_form($action = 'add', $id = 0, $extra_info = 'new')
	{
		global $charset;
			
		$tbl_lp_item = Database::get_course_table('lp_item');
		$tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
		
		$path_parts = pathinfo($extra_info['dir']);		
		$no_display_edit_textarea=false;
		
		//If action==edit document
		//We don't display the document form if it's not an editable document (html or txt file)
		if($action=="edit"){
			if(is_array($extra_info)){
				if($path_parts['extension']!="txt" && $path_parts['extension']!="html"){
					$no_display_edit_textarea=true;
				}
			}
		}
		
		$no_display_add=false;
		
		//If action==add an existing document
		//We don't display the document form if it's not an editable document (html or txt file)
		if($action=="add"){
			if(is_numeric($extra_info)){
				
				$sql_doc = "SELECT path FROM " . $tbl_doc . "WHERE id = " . $extra_info;
				$result=api_sql_query($sql_doc, __FILE__, __LINE__);
				$path_file=Database::result($result,0,0);				
				
				$path_parts = pathinfo($path_file);
				
				if($path_parts['extension']!="txt" && $path_parts['extension']!="html"){
					$no_display_add=true;
				}
			}
		}
		
		if($id != 0 && is_array($extra_info))
		{
			$item_title			= stripslashes($extra_info['title']);
			$item_description	= stripslashes($extra_info['description']);	
            $item_terms         = stripslashes($extra_info['terms']);        
			if(empty($item_title))
			{				
				$path_parts = pathinfo($extra_info['path']);
				$item_title = stripslashes($path_parts['filename']);
			}
		}
		elseif(is_numeric($extra_info))
		{
			$sql_doc = "
				SELECT path, title
				FROM " . $tbl_doc . "
				WHERE id = " . $extra_info;
			
			$result = api_sql_query($sql_doc, __FILE__, __LINE__);
			$row = Database::fetch_array($result);
			
			$explode = explode('.', $row['title']);
			
			if(count($explode)>1){
				for($i = 0; $i < count($explode) - 1; $i++)
					$item_title .= $explode[$i];
			}
			else{
				$item_title=$row['title'];
			}
			
			$item_title = str_replace('_', ' ', $item_title);
			
			if(empty($item_title))
			{
				$path_parts = pathinfo($row['path']);
				$item_title = stripslashes($path_parts['filename']);
			}
			
		}
		else
		{
			$item_title			= '';
			$item_description	= '';
		}
		
		$return = '<div class="sectiontitle">';
			
			if($id != 0 && is_array($extra_info))
				$parent = $extra_info['parent_item_id'];
			else
				$parent = 0;
			
			$sql = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE
					lp_id = " . $this->lp_id;
			
			$result = api_sql_query($sql, __FILE__, __LINE__);
			
			$arrLP = array();
			
			while($row = Database::fetch_array($result))
			{
				$arrLP[] = array(
					'id' => $row['id'],
					'item_type' => $row['item_type'],
					'title' => $row['title'],
					'path' => $row['path'],
					'description' => $row['description'],
					'parent_item_id' => $row['parent_item_id'],
					'previous_item_id' => $row['previous_item_id'],
					'next_item_id' => $row['next_item_id'],
					'display_order' => $row['display_order'],
                    'max_score' => $row['max_score'],
                    'min_score' => $row['min_score'],
                    'mastery_score' => $row['mastery_score'],
					'prerequisite' => $row['prerequisite']);
			}
			
			$this->tree_array($arrLP);			
			$arrLP = $this->arrMenu;			
			unset($this->arrMenu);
			
			if($action == 'add')
			{
				$return .= get_lang("CreateTheDocument") . "\n";
			}
			elseif($action == 'move')
			{
				$return .= get_lang("MoveTheCurrentDocument") . "\n";
			}
			else
			{
				$return .= get_lang("EditTheCurrentDocument") . "\n";
			}

			$return .= '</div>';

			if(isset($_GET['edit']) && $_GET['edit'] == 'true')
			{
				$return .= '<div class="warning-message">';				
				$return .= '<strong>'.get_lang("Warning").' !</strong><br />';
				$return .= get_lang("WarningEditingDocument");				
				$return .= '</div>';
			}
			/*
			if($no_display_add==true){
				$return .= '<div class="warning-message">';
				$return .= get_lang("CantEditDocument");
				$return .= '</div>';
				return $return;
			}
			*/
			require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
			
			$form = new FormValidator('form','POST',api_get_self()."?".$_SERVER["QUERY_STRING"],'','enctype="multipart/form-data"');
			
			$defaults["title"] = html_entity_decode($item_title);
            $defaults["title"]=mb_convert_encoding($defaults["title"],$charset,$this->encoding);
            if(empty($defaults["title"]))
            {
                    $defaults["title"] = html_entity_decode($item_title);
            }
			$defaults["description"]=mb_convert_encoding($item_description,$charset,$this->encoding);
		
			$form->addElement('html',$return);
						
			if($action != 'move')
			{
				$form->addElement('text','title', get_lang('Title'),'id="idTitle" class="learnpath_item_form" size=44%');
			}			
						
			//$arrHide = array($id);
			
			$arrHide[0]['value']=$this->name;
			$arrHide[0]['padding']=3;
			
			for($i = 0; $i < count($arrLP); $i++)
			{
				if($action != 'add'){
					if(($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide)){
						$arrHide[$arrLP[$i]['id']]['value']=mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding);
						$arrHide[$arrLP[$i]['id']]['padding']=3+ $arrLP[$i]['depth'] * 10;
						if($parent == $arrLP[$i]['id']){
							$s_selected_parent=$arrHide[$arrLP[$i]['id']];
						}
					}
				}
				else{
					if($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir'){
						$arrHide[$arrLP[$i]['id']]['value']=mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding);
						$arrHide[$arrLP[$i]['id']]['padding']=3+ $arrLP[$i]['depth'] * 10;
						if($parent == $arrLP[$i]['id']){
							$s_selected_parent=$arrHide[$arrLP[$i]['id']];
						}
					}
				}
			}
			$parent_select = &$form->addElement('select', 'parent', get_lang('Parent'), '', 'class="learnpath_item_form" style="width:40%;" onchange="load_cbo(this.value);"');
						
			foreach($arrHide as $key => $value) {		
					$parent_select->addOption($value['value'],$key,'style="padding-left:'.$value['padding'].'px;"');								
			}
					
			if (!empty($id)) {
				$parent_select -> setSelected($parent);
			} else {
				$parent_item_id = $_SESSION['parent_item_id'];
				$parent_select -> setSelected($parent_item_id);	
			}
			
			if(is_array($arrLP)) {
				reset($arrLP);
			}
			
			$arrHide=array();
			
			//POSITION
			for($i = 0; $i < count($arrLP); $i++) {
				if($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id) {
					if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
						$s_selected_position=$arrLP[$i]['id'];
					elseif($action == 'add')
						$s_selected_position=$arrLP[$i]['id'];
					
					$arrHide[$arrLP[$i]['id']]['value']=get_lang("After").' "' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding).'"';
					
				}
			}
			
			$position = &$form->addElement('select', 'previous', get_lang('Position'), '', 'id="idPosition" class="learnpath_item_form" style="width:40%;"');
			$position->addOption(get_lang("FirstPosition"),0);
			
			foreach($arrHide as $key => $value) {
				$position->addOption($value['value'],$key,'style="padding-left:'.$value['padding'].'px;"');
			}
			$position -> setSelected($s_selected_position);
			if(is_array($arrLP)) {
				reset($arrLP);
			}
			
			if($action != 'move') {				
				$id_prerequisite=0;
				if(is_array($arrLP)) {
					foreach($arrLP as $key=>$value){
						if($value['id']==$id){
							$id_prerequisite=$value['prerequisite'];
							break;
						}
					}
				}
				//comented the prerequisites, only visible in edit (new document)
				//$select_prerequisites=$form->addElement('select', 'prerequisites', get_lang('Prerequisites'),null,'id="prerequisites" class="learnpath_item_form" style="width:263px;"');
				//$select_prerequisites->addOption(get_lang("NoPrerequisites"),0);

				// form element for uploading an mp3 file
				//$form->addElement('file','mp3',get_lang('UploadMp3audio'),'id="mp3" size="33"');
				//$form->addRule('file', 'The extension of the Song file should be *.mp3', 'filename', '/^.*\.mp3$/');
				
                /* Code deprecated - moved to lp level (not lp-item)
                if ( api_get_setting('search_enabled') === 'true' )
                {
                    //add terms field
                    $terms = $form->addElement('text','terms', get_lang('SearchFeatureTerms').'&nbsp;:','id="idTerms" class="learnpath_item_form"');
                    $terms->setValue($item_terms); 
                }
                */

				$arrHide=array();

				for($i = 0; $i < count($arrLP); $i++)
				{
					if($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter')
					{
						if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
							$s_selected_position=$arrLP[$i]['id'];
						elseif($action == 'add')
							$s_selected_position=$arrLP[$i]['id'];
						
						$arrHide[$arrLP[$i]['id']]['value']=mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding);
						
					}
				}
				
			/*	foreach($arrHide as $key => $value){
					$select_prerequisites->addOption($value['value'],$key,'style="padding-left:'.$value['padding'].'px;"');
					if($key==$s_selected_position && $action == 'add'){
						$select_prerequisites -> setSelected(0);
					}
					elseif($key==$id_prerequisite && $action == 'edit'){
						$select_prerequisites -> setSelected($id_prerequisite);
					}
				}
				*/
				if(!$no_display_add)
				{
					if(($extra_info == 'new' || $extra_info['item_type'] == TOOL_DOCUMENT || $_GET['edit'] == 'true'))
					{
						
						if(isset($_POST['content']))
							$content = stripslashes($_POST['content']);
						elseif(is_array($extra_info)){
							//If it's an html document or a text file
							if(!$no_display_edit_textarea){
								$content = $this->display_document($extra_info['path'], false, false);
							}
						}
						elseif(is_numeric($extra_info))
							$content = $this->display_document($extra_info, false, false);
						else
							$content = '';
						
						if(!$no_display_edit_textarea)
						{
							// We need to claculate here some specific settings for the online editor.
							// The calculated settings work for documents in the Documents tool
							// (on the root or in subfolders).
							// For documents in native scorm packages it is unclear whether the
							// online editor should be activated or not.
							global $fck_attribute;
							$fck_attribute['Width'] = '100%';
							$fck_attribute['Height'] = '700';
							$fck_attribute['ToolbarSet'] = 'LearnPath';
							$fck_attribute['Config']['FullPage'] = true;
							$relative_path = $extra_info['dir'];
							if ($relative_path == 'n/')
							{
								// A new document, it is in the root of the repository.
								$relative_path = '';
								$relative_prefix = '';
							}
							else
							{
								// The document already exists. Whe have to determine its relative path towards the repository root.
								$relative_path = explode('/', $relative_path);
                                $cnt = count($relative_path) - 2;
                                if ($cnt < 0) { $cnt = 0; }
								$relative_prefix = str_repeat('../', $cnt);
								$relative_path = array_slice($relative_path, 1, $cnt);
								$relative_path = implode('/', $relative_path);
								if (strlen($relative_path) > 0)
								{
									$relative_path = $relative_path.'/';
								}
							}
							$fck_attribute['Config']['CreateDocumentDir'] = $relative_prefix;
							$fck_attribute['Config']['CreateDocumentWebDir'] = api_get_path('WEB_COURSE_PATH').api_get_course_path().'/document/';
							$fck_attribute['Config']['BaseHref'] = api_get_path('WEB_COURSE_PATH').api_get_course_path().'/document/'.$relative_path;
							
							if ($_GET['action']=='add_item'){
								$class='add';
								$text=get_lang('LPCreateDocument');
							} else if ($_GET['action']=='edit_item'){
								$class='save';
								$text=get_lang('LPModifyDocument');
							}	
							
							$form->addElement('style_submit_button', 'submit_button', $text,'class="'.$class.'"');
							$renderer = $form->defaultRenderer();
							$renderer->setElementTemplate('<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{label}<br />{element}','content_lp');

							$form->addElement('html','<div style="margin:3px 12px">');
							$form->addElement('html_editor','content_lp','');
							$form->addElement('html','</div>');
							$defaults["content_lp"]=$content;
						}
						
					}
					
					elseif(is_numeric($extra_info))
					{
			
						$form->addElement('style_submit_button', 'submit_button', get_lang('SaveDocument'), 'class="save"');
			
						$return = $this->display_document($extra_info, true, true, true);
						$form->addElement('html',$return);
					}
				}
				
			}
			if($action == 'move')
			{
				$form->addElement('hidden', 'title', $item_title);
				$form->addElement('hidden', 'description', $item_description);
			}
			if(is_numeric($extra_info))
			{
				$form->addElement('style_submit_button', 'submit_button', get_lang('SaveDocument'), 'value="submit_button", class="save"');
				$form->addElement('hidden', 'path', $extra_info);
			}
			elseif(is_array($extra_info))
			{
				$form->addElement('style_submit_button', 'submit_button', get_lang('TitleManipulateDocument'), 'class="save"');
				$form->addElement('hidden', 'path', $extra_info['path']);
			}
			
			$form->addElement('hidden', 'type', TOOL_DOCUMENT);
			$form->addElement('hidden', 'post_time', time());
			
	
	 
		$form->setDefaults($defaults);	
		
		
		return $form->return_form();
	}
	
	/**
	 * Return HTML form to add/edit a link item
	 *
	 * @param string	Action (add/edit)
	 * @param integer	Item ID if exists
	 * @param mixed		Extra info
	 * @return	string	HTML form
	 */
	function display_link_form($action = 'add', $id = 0, $extra_info = '')
	{
		global $charset;
		$tbl_lp_item = Database::get_course_table('lp_item');
		$tbl_link = Database::get_course_table(TABLE_LINK);
		
		if($id != 0 && is_array($extra_info)) {
			$item_title			= stripslashes($extra_info['title']);
			$item_description	= stripslashes($extra_info['description']);
		} elseif(is_numeric($extra_info)) {
			$sql_link = "
				SELECT
					title,
					description,
					url
				FROM " . $tbl_link . "
				WHERE id = " . $extra_info;
			
			$result = api_sql_query($sql_link, __FILE__, __LINE__);
			$row = Database::fetch_array($result);
			
			$item_title = $row['title'];
			
			$item_description = $row['description'];
			$item_url = $row['url'];
		} else {
			$item_title			= '';
			$item_description	= '';
		}
		$item_title=mb_convert_encoding($item_title,$charset,$this->encoding);
		$item_description=mb_convert_encoding($item_description,$charset,$this->encoding);				
		$return = '<div class="sectiontitle">';
			
			if($id != 0 && is_array($extra_info))
				$parent = $extra_info['parent_item_id'];
			else
				$parent = 0;
			
			$sql = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE
					lp_id = " . $this->lp_id;
			
			$result = api_sql_query($sql, __FILE__, __LINE__);			
			$arrLP = array();
			
			while($row = Database::fetch_array($result)) {
				$arrLP[] = array(
					'id' => $row['id'],
					'item_type' => $row['item_type'],
					'title' => $row['title'],
					'path' => $row['path'],
					'description' => $row['description'],
					'parent_item_id' => $row['parent_item_id'],
					'previous_item_id' => $row['previous_item_id'],
					'next_item_id' => $row['next_item_id'],
					'display_order' => $row['display_order'],
                    'max_score' => $row['max_score'],
                    'min_score' => $row['min_score'],
                    'mastery_score' => $row['mastery_score'],
					'prerequisite' => $row['prerequisite']);
			}
			
			$this->tree_array($arrLP);			
			$arrLP = $this->arrMenu;			
			unset($this->arrMenu);
			
			if($action == 'add')
				$return .= get_lang("CreateTheLink").'&nbsp;:' . "\n";
			elseif($action == 'move')
				$return .= get_lang("MoveCurrentLink").'&nbsp;:' . "\n";
			else
				$return .= get_lang("EditCurrentLink").'&nbsp;:' . "\n";
			
			$return .= '</div>';
			$return .= '<div class="sectioncomment">';
			$return .= '<form method="POST">' . "\n";
			
				$return .= "\t" . '<table>' . "\n";
				
					if($action != 'move') {
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idTitle">'.get_lang('Title').'</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><input id="idTitle" name="title" style="width:99%;" type="text" value="' . $item_title . '" class="learnpath_item_form"/></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
					}				
				
					$return .= "\t\t" . '<tr>' . "\n";
					
						$return .= "\t\t\t" . '<td class="label"><label for="idParent">'.get_lang('Parent').'</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idParent" name="parent" onchange="load_cbo(this.value);" class="learnpath_item_form" size="1">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">' . $this->name . '</option>';
								
								$arrHide = array($id);
								
								$parent_item_id = $_SESSION['parent_item_id'];
								
								for($i = 0; $i < count($arrLP); $i++) {
									if($action != 'add') {
										if(($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide))
										{
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding) . '</option>';
										} else {
											$arrHide[] = $arrLP[$i]['id'];
										}
									} else {
										if($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
											$return .= "\t\t\t\t\t" . '<option ' . (($parent_item_id == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding) . '</option>';
									}
								}
								
								if(is_array($arrLP)) {								
									reset($arrLP);
								}
								
							$return .= "\t\t\t\t" . '</select>';
						
						$return .= "\t\t\t" . '</td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
									
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td class="label"><label for="idPosition">'.get_lang('Position').'</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idPosition" name="previous" style="width:100%;" size="1" class="learnpath_item_form">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">'.get_lang("FirstPosition").'</option>';
								
								for($i = 0; $i < count($arrLP); $i++)
								{
									if($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id)
									{
										if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
											$selected = 'selected="selected" ';
										elseif($action == 'add')
											$selected = 'selected="selected" ';
										else
											$selected = '';
										
										$return .= "\t\t\t\t\t" . '<option ' . $selected . 'value="' . $arrLP[$i]['id'] . '">'.get_lang("After").' "' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding) . '"</option>';
									}
								}
								
							$return .= "\t\t\t\t" . '</select>';
						
						$return .= "\t\t\t" . '</td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
					
					if($action != 'move') {
						
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idDescription">'.get_lang('Description').'</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><textarea id="idDescription" style="width:100%;" name="description" class="learnpath_item_form" rows="4">' . $item_description . '</textarea></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idURL">'.get_lang('Url').'</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><input' . (is_numeric($extra_info) ? ' disabled="disabled"' : '') . ' id="idURL" name="url" style="width:99%;" type="text" value="' . $item_url . '" class="learnpath_item_form" /></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";
						
						$id_prerequisite=0;
						if (is_array($arrLP)){						
							foreach($arrLP as $key=>$value){
								if($value['id']==$id){
									$id_prerequisite=$value['prerequisite'];
									break;
								}
							}
						}
												
						$arrHide=array();
						for($i = 0; $i < count($arrLP); $i++) {
							if($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter')
							{
								if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
									$s_selected_position=$arrLP[$i]['id'];
								elseif($action == 'add')
									$s_selected_position=0;
								$arrHide[$arrLP[$i]['id']]['value']=mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding);
								
							}
						}
						
						/*//comented the prerequisites, only visible in edit (link)
 							$return .= "\t\t" . '<tr>' . "\n";
							$return .= "\t\t\t" . '<td class="label"><label for="idPrerequisites">'.get_lang('Prerequisites').'</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><select name="prerequisites" id="prerequisites" class="learnpath_item_form"><option value="0">'.get_lang("NoPrerequisites").'</option>';
							
							foreach($arrHide as $key => $value)
							{
								if($key==$s_selected_position && $action == 'add')
								{
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								elseif($key==$id_prerequisite && $action == 'edit'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								else{
									$return .= '<option value="'.$key.'">'.$value['value'].'</option>';
								}
							}
							
							$return .= "</select></td>";
						*/						
						$return .= "\t\t" . '</tr>' . "\n";
								
					}
					
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td>&nbsp;</td><td><button class="save" name="submit_button" type="submit">'.get_lang("Ok").'</button></td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
				
				$return .= "\t" . '</table>' . "\n";	
				
				if($action == 'move')
				{
					$return .= "\t" . '<input name="title" type="hidden" value="' . $item_title . '" />' . "\n";
					$return .= "\t" . '<input name="description" type="hidden" value="' . $item_description . '" />' . "\n";
				}
				
				if(is_numeric($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info . '" />' . "\n";
				}
				elseif(is_array($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info['path'] . '" />' . "\n";
				}
				
				$return .= "\t" . '<input name="type" type="hidden" value="'.TOOL_LINK.'" />' . "\n";
				$return .= "\t" . '<input name="post_time" type="hidden" value="' . time() . '" />' . "\n";
				
			$return .= '</form>' . "\n";
		
		$return .= '</div>' . "\n";
		
		return $return;
	}
	
	/**
	 * Return HTML form to add/edit a student publication (work)
	 *
	 * @param	string	Action (add/edit)
	 * @param	integer	Item ID if already exists
	 * @param	mixed	Extra info (work ID if integer)
	 * @return	string	HTML form
	 */
	function display_student_publication_form($action = 'add', $id = 0, $extra_info = '')
	{
		global $charset;
		
		$tbl_lp_item = Database::get_course_table('lp_item');
		$tbl_publication = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
		
		if($id != 0 && is_array($extra_info)) {
			$item_title			= stripslashes($extra_info['title']);
			$item_description	= stripslashes($extra_info['description']);
		} elseif(is_numeric($extra_info)) {
			$sql_publication = "
				SELECT
					title,
					description
				FROM " . $tbl_publication . "
				WHERE id = " . $extra_info;
			
			$result = api_sql_query($sql_publication, __FILE__, __LINE__);
			$row = Database::fetch_array($result);
			
			$item_title = $row['title'];
		} else {
			$item_title			= '';
		}
		
		$item_title=mb_convert_encoding($item_title,$charset,$this->encoding);			
		$return = '<div class="sectiontitle">';
			
			if($id != 0 && is_array($extra_info))
				$parent = $extra_info['parent_item_id'];
			else
				$parent = 0;
			
			$sql = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE
					lp_id = " . $this->lp_id;
			
			$result = api_sql_query($sql, __FILE__, __LINE__);
			
			$arrLP = array();
			
			while($row = Database::fetch_array($result)) {
				$arrLP[] = array(
					'id' => $row['id'],
					'item_type' => $row['item_type'],
					'title' => $row['title'],
					'path' => $row['path'],
					'description' => $row['description'],
					'parent_item_id' => $row['parent_item_id'],
					'previous_item_id' => $row['previous_item_id'],
					'next_item_id' => $row['next_item_id'],
					'display_order' => $row['display_order'],
                    'max_score' => $row['max_score'],
                    'min_score' => $row['min_score'],
                    'mastery_score' => $row['mastery_score'],
					'prerequisite' => $row['prerequisite']);
			}
			
			$this->tree_array($arrLP);			
			$arrLP = $this->arrMenu;			
			unset($this->arrMenu);
			
			if($action == 'add')
				$return .= get_lang("Student_publication").'&nbsp;:' . "\n";
			elseif($action == 'move')
				$return .= get_lang("MoveCurrentStudentPublication").'&nbsp;:' . "\n";
			else
				$return .= get_lang("EditCurrentStudentPublication").'&nbsp;:' . "\n";
			
			$return .= '</div>';
			$return .= '<div class="sectioncomment">';
			
			$return .= '<form method="POST">' . "\n";
			
				$return .= "\t" . '<table>' . "\n";
				
					if($action != 'move')
					{
						$return .= "\t\t" . '<tr>' . "\n";
							
							$return .= "\t\t\t" . '<td class="label"><label for="idTitle">'.get_lang('Title').'</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><input id="idTitle" name="title" style="width:100%;" type="text" value="' . $item_title . '" class="learnpath_item_form" /></td>' . "\n";
						
						$return .= "\t\t" . '</tr>' . "\n";			
					}	
				
					$return .= "\t\t" . '<tr>' . "\n";
					
						$return .= "\t\t\t" . '<td class="label"><label for="idParent">'.get_lang('Parent').'</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idParent" name="parent" style="width:100%;" onchange="load_cbo(this.value);" class="learnpath_item_form" size="1">';
							
							
								$parent_item_id = $_SESSION['parent_item_id'];																					
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">' . $this->name . '</option>';
								
								$arrHide = array($id);
								
								for($i = 0; $i < count($arrLP); $i++)
								{
									if($action != 'add')
									{
										if(($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir') && !in_array($arrLP[$i]['id'], $arrHide) && !in_array($arrLP[$i]['parent_item_id'], $arrHide))
										{
											$return .= "\t\t\t\t\t" . '<option ' . (($parent == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding) . '</option>';
										}
										else
										{
											$arrHide[] = $arrLP[$i]['id'];
										}
									}
									else
									{
										if($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter' || $arrLP[$i]['item_type'] == 'dir')
											$return .= "\t\t\t\t\t" . '<option ' . (($parent_item_id == $arrLP[$i]['id']) ? 'selected="selected" ' : '') . 'style="padding-left:' . ($arrLP[$i]['depth'] * 10) . 'px;" value="' . $arrLP[$i]['id'] . '">' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding). '</option>';
									}
								}
								
								if(is_array($arrLP))
								{
									reset($arrLP);
								}
								
							$return .= "\t\t\t\t" . '</select>';
						
						$return .= "\t\t\t" . '</td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
									
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td class="label"><label for="idPosition">'.get_lang('Position').'</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input">' . "\n";
						
							$return .= "\t\t\t\t" . '<select id="idPosition" name="previous" style="width:100%;" size="1" class="learnpath_item_form">';
							
								$return .= "\t\t\t\t\t" . '<option class="top" value="0">'.get_lang("FirstPosition").'</option>';
								
								for($i = 0; $i < count($arrLP); $i++)
								{
									if($arrLP[$i]['parent_item_id'] == $parent && $arrLP[$i]['id'] != $id)
									{
										if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
											$selected = 'selected="selected" ';
										elseif($action == 'add')
											$selected = 'selected="selected" ';
										else
											$selected = '';
										
										$return .= "\t\t\t\t\t" . '<option ' . $selected . 'value="' . $arrLP[$i]['id'] . '">'.get_lang("After").' "' . mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding) . '"</option>';
									}
								}
								
							$return .= "\t\t\t\t" . '</select>';
						
						$return .= "\t\t\t" . '</td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
					
					if($action != 'move')
					{
						
						$id_prerequisite=0;
						if(is_array($arrLP))
						{
						foreach($arrLP as $key=>$value){
							if($value['id']==$id){
								$id_prerequisite=$value['prerequisite'];
								break;
							}
						}
						}						
						$arrHide=array();
						for($i = 0; $i < count($arrLP); $i++)
						{
							if($arrLP[$i]['id'] != $id && $arrLP[$i]['item_type'] != 'dokeos_chapter')
							{
								if($extra_info['previous_item_id'] == $arrLP[$i]['id'])
									$s_selected_position=$arrLP[$i]['id'];
								elseif($action == 'add')
									$s_selected_position=0;
								$arrHide[$arrLP[$i]['id']]['value']=mb_convert_encoding($arrLP[$i]['title'],$charset,$this->encoding);
								
							}
						}
						
							//comented the prerequisites, only visible in edit (work)
					/*
							$return .= "\t\t" . '<tr>' . "\n";
							$return .= "\t\t\t" . '<td class="label"><label for="idPrerequisites">'.get_lang('Prerequisites').'</label></td>' . "\n";
							$return .= "\t\t\t" . '<td class="input"><select name="prerequisites" id="prerequisites" class="learnpath_item_form"><option value="0">'.get_lang("NoPrerequisites").'</option>';
							
							foreach($arrHide as $key => $value){
								if($key==$s_selected_position && $action == 'add'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								elseif($key==$id_prerequisite && $action == 'edit'){
									$return .= '<option value="'.$key.'" selected="selected">'.$value['value'].'</option>';
								}
								else{
									$return .= '<option value="'.$key.'">'.$value['value'].'</option>';
								}
							}
							
							$return .= "</select></td>";
					*/
						$return .= "\t\t" . '</tr>' . "\n";
						
					}
					
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td>&nbsp</td><td colspan="2"><button class="save" name="submit_button" type="submit">'.get_lang("Ok").'</button></td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
				
				$return .= "\t" . '</table>' . "\n";	
				
				if($action == 'move')
				{
					$return .= "\t" . '<input name="title" type="hidden" value="' . $item_title . '" />' . "\n";
					$return .= "\t" . '<input name="description" type="hidden" value="' . $item_description . '" />' . "\n";
				}
				
				if(is_numeric($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info . '" />' . "\n";
				}
				elseif(is_array($extra_info))
				{
					$return .= "\t" . '<input name="path" type="hidden" value="' . $extra_info['path'] . '" />' . "\n";
				}
				
				$return .= "\t" . '<input name="type" type="hidden" value="'.TOOL_STUDENTPUBLICATION.'" />' . "\n";
				$return .= "\t" . '<input name="post_time" type="hidden" value="' . time() . '" />' . "\n";
				
			$return .= '</form>' . "\n";
		
		$return .= '</div>' . "\n";
		
		return $return;
	}
	
	/**
	 * Displays the menu for manipulating a step
	 *
	 * @return unknown
	 */
	function display_manipulate($item_id, $item_type = TOOL_DOCUMENT)
	{
		global $charset, $_course; 
		$return = '<div class="actions">';
		
		switch($item_type)
		{
			case 'dokeos_chapter':
			case 'chapter':
				//comented the message cause should not show it
				//$lang = get_lang('TitleManipulateChapter');
				break;
				
			case 'dokeos_module':
			case 'module':
				//comented the message cause should not show it
				//$lang = get_lang('TitleManipulateModule');
				
				break;
				
			case TOOL_DOCUMENT:
				//comented the message cause should not show it
				//$lang = get_lang('TitleManipulateDocument');
				break;
			
			case TOOL_LINK:
			case 'link':
				//comented the message cause should not show it
				//$lang = get_lang('TitleManipulateLink');
				
				break;
			
			case TOOL_QUIZ:
				//comented the message cause should not show it
				//$lang = get_lang('TitleManipulateQuiz');
				
				break;
			
			case TOOL_STUDENTPUBLICATION:
				//comented the message cause should not show it
				//$lang = get_lang('TitleManipulateStudentPublication');
				
				break;
		}
		
		$tbl_lp_item	= Database::get_course_table('lp_item');
		
		$sql = "
			SELECT
				 * 
			FROM " . $tbl_lp_item . " as lp
			WHERE
				lp.id = " . $item_id;

		$result = api_sql_query($sql, __FILE__, __LINE__);
		
		$row= mysql_fetch_assoc($result);
		$s_title = $row['title'];
		$s_title=mb_convert_encoding($s_title,$charset,$this->encoding);
		
		
				// we display an audio player if needed
		if (!empty($row['audio']))
		{
			$return .= '<div class="lp_mediaplayer" id="container"><a href="http://www.macromedia.com/go/getflashplayer">Get the Flash Player</a> to see this player.</div>';
			$return .= '<script type="text/javascript" src="../inc/lib/mediaplayer/swfobject.js"></script>';
			$return .= '<script type="text/javascript">
							var s1 = new SWFObject("../inc/lib/mediaplayer/player.swf","ply","250","20","9","#FFFFFF");
							s1.addParam("allowscriptaccess","always");
							s1.addParam("flashvars","file=../../courses/'.$_course['path'].'/document/audio/'.$row['audio'].'&image=preview.jpg&autostart=true");
							s1.write("container");
						</script>';
		}
		//commented ":" for message in step
		//$return .= $lang.': '; 
				
		$return .= '<a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=edit_item&amp;view=build&amp;id=' . $item_id . '&amp;lp_id=' . $this->lp_id . '&path_item='.$row['path'].'" title="'.get_lang('Edit').'"><img align="absbottom" alt="Edit the current item" src="../img/edit.gif" title="'.get_lang("Edit").'" /> '.get_lang("Edit").'</a>';
		$return .= '<a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=move_item&amp;view=build&amp;id=' . $item_id . '&amp;lp_id=' . $this->lp_id . '" title="Move the current item"><img align="absbottom" alt="Move the current item" src="../img/deplacer_fichier.gif" title="'.get_lang("Move").'" /> '.get_lang("Move").'</a>';
		// commented for now as prerequisites cannot be added to chapters
		if($item_type != 'dokeos_chapter' && $item_type != 'chapter')
		{
			$return .= '<a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=edit_item_prereq&amp;view=build&amp;id=' . $item_id . '&amp;lp_id=' . $this->lp_id . '" title="'.get_lang('Prerequisites').'"><img align="absbottom" alt="'.get_lang('Prerequisites').'" src="../img/right.gif" title="'.get_lang('Prerequisites').'" /> '.get_lang('Prerequisites').'</a>';
		}
		$return .= '<a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=delete_item&amp;view=build&amp;id=' . $item_id . '&amp;lp_id=' . $this->lp_id . '" onclick="return confirmation(\'' .addslashes($s_title). '\');" title="Delete the current item"><img alt="Delete the current item" align="absbottom" src="../img/delete.gif" title="'.get_lang("Delete").'" /> '.get_lang("Delete").'</a>';
		
		//$return .= '<br><br><p class="lp_text">' . ((trim($s_description) == '') ? ''.get_lang("NoDescription").'' : stripslashes(nl2br($s_description))) . '</p>';
		
		//$return.="</td><td valign='top'>";
		
		// get the audiorecorder. Use of ob_* functions since there are echos in the file
		ob_start();
		$audio_recorder_studentview = 'false';
		$audio_recorder_item_id = $item_id;
		if(api_get_setting('service_visio','active')=='true'){
			include('audiorecorder.inc.php');
		}
		$return .= ob_get_contents();
		ob_end_clean();
		// end of audiorecorder include
		
		
		$return .= '</div>';
		

		return $return;
	}
	
	/**
	 * Creates the javascript needed for filling up the checkboxes without page reload
	 *
	 * @return string
	 */
	function create_js()
	{
		$return = '<script language="javascript" type="text/javascript">' . "\n";
		
		$return .= 'function load_cbo(id){' . "\n";
		
		$return .= "var cbo = document.getElementById('idPosition');\n";
		
		$return .= 'for(var i = cbo.length - 1; i > 0; i--)';
			$return .= 'cbo.options[i] = null;'."\n";
		$return .= 'var k=0;'."\n";
		$return .= 'for(var i = 1; i <= child_name[id].length; i++){'."\n";
			$return .= '  cbo.options[i] = new Option(child_name[id][i-1], child_value[id][i-1]);'."\n";
			$return .= 'k=i;';		
		$return .= '}' . "\n\n";
		$return .= 'if( typeof cbo != "undefined" ) {cbo.options[k].selected = true;}';
		$return .='}';
		
		$return .= 'var child_name = new Array();' . "\n";
		$return .= 'var child_value = new Array();' . "\n\n";
		
		$return .= 'child_name[0] = new Array();' . "\n";
		$return .= 'child_value[0] = new Array();' . "\n\n";
		
		$tbl_lp_item = Database::get_course_table('lp_item');
		
		$sql_zero = "
			SELECT *
			FROM " . $tbl_lp_item . "
			WHERE
				lp_id = " . $this->lp_id . " AND
				parent_item_id = 0
			ORDER BY display_order ASC";
		$res_zero = api_sql_query($sql_zero, __FILE__, __LINE__);

		global $charset;		
		$i = 0;

		while($row_zero = Database::fetch_array($res_zero))
		{
			$return .= 'child_name[0][' . $i . '] = "'.get_lang("After").' \"' . mb_convert_encoding($row_zero['title'],$charset,$this->encoding) . '\"";' . "\n";
			$return .= 'child_value[0][' . $i++ . '] = "' . $row_zero['id'] . '";' . "\n";
		}
		
		$return .= "\n";
		
		$sql = "
			SELECT *
			FROM " . $tbl_lp_item . "
			WHERE
				lp_id = " . $this->lp_id;
		$res = api_sql_query($sql, __FILE__, __LINE__);
		
		while($row = Database::fetch_array($res))
		{
			$sql_parent = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE parent_item_id = " . $row['id'] . "
				ORDER BY display_order ASC";
			$res_parent = api_sql_query($sql_parent, __FILE__, __LINE__);
			
			$i = 0;
			
			$return .= 'child_name[' . $row['id'] . '] = new Array();' . "\n";
			$return .= 'child_value[' . $row['id'] . '] = new Array();' . "\n\n";
			
			while($row_parent = Database::fetch_array($res_parent))
			{
				$return .= 'child_name[' . $row['id'] . '][' . $i . '] = "'.get_lang("After").' \"' . mb_convert_encoding($row_parent['title'],$charset,$this->encoding) . '\"";' . "\n";
				$return .= 'child_value[' . $row['id'] . '][' . $i++ . '] = "' . $row_parent['id'] . '";' . "\n";
			}
			
			$return .= "\n";
		}
		
		$return .= '</script>' . "\n";
		
		return $return;
	}
	
	/**
	 * Display the form to allow moving an item
	 *
	 * @param	integer		Item ID
	 * @return	string		HTML form
	 */
	function display_move_item($item_id)
	{
		global $_course; //will disappear
		global $charset;
		$return = '';
		
		if(is_numeric($item_id))
		{
			$tbl_lp_item = Database::get_course_table('lp_item');
			
			 $sql = "
				SELECT *
				FROM " . $tbl_lp_item . "
				WHERE id = " . $item_id;
			
			$res = api_sql_query($sql, __FILE__, __LINE__);
			$row = Database::fetch_array($res);
			
			switch($row['item_type'])
			{
				case 'dokeos_chapter': case 'dir' : case 'asset' :
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_item_form($row['item_type'], get_lang('MoveCurrentChapter'), 'move', $item_id, $row);
					
					break;
				
				case 'dokeos_module':
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_item_form($row['item_type'], 'Move th current module:', 'move', $item_id, $row);
					
					break;
				
				case TOOL_DOCUMENT:
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_document_form('move', $item_id, $row);
					
					break;
			
				case TOOL_LINK:
					
			$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_link_form('move', $item_id, $row);
					
					break;
					
					case TOOL_HOTPOTATOES:

			$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_link_form('move', $item_id, $row);

					break;
					
				case TOOL_QUIZ:
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_quiz_form('move', $item_id, $row);
					
					break;
				
				case TOOL_STUDENTPUBLICATION:
					
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_student_publication_form('move', $item_id, $row);
					
					break;
					
					
				case TOOL_FORUM : 
					$return .= $this->display_manipulate($item_id, $row['item_type']);
					$return .= $this->display_forum_form('move', $item_id, $row);
			}
		}
		
		return $return;
	}
	
	/**
	 * Displays a basic form on the overview page for changing the item title and the item description.
	 *
	 * @param string $item_type
	 * @param string $title
	 * @param array $data
	 * @return string
	 */
	function display_item_small_form($item_type, $title = '', $data)
	{
		$return .= '<div class="lp_small_form">' . "\n";
						
			$return .= '<p class="lp_title">' . $title . '</p>';
			
			$return .= '<form method="post">' . "\n";
				
				$return .= '<table cellpadding="0" cellspacing="0" class="lp_form">';
				
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td class="label"><label for="idTitle">Title&nbsp;:</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input"><input class="small_form" id="idTitle" name="title" type="text" value="' . $data['title'] . '" /></td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
					
					$return .= "\t\t" . '<tr>' . "\n";
						
						$return .= "\t\t\t" . '<td class="label"><label for="idDescription">Description&nbsp;:</label></td>' . "\n";
						$return .= "\t\t\t" . '<td class="input"><textarea class="small_form" id="idDescription" name="description" rows="4">' . $data['description'] . '</textarea></td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
					
					$return .= "\t\t" . '<tr>' . "\n";
					
						$return .= "\t\t\t" . '<td colspan="2"><button class="save" name="submit_button" type="submit">'.get_lang('Save').'</button></td>' . "\n";
					
					$return .= "\t\t" . '</tr>' . "\n";
					
				$return .= "\t\t" . '</table>' . "\n";
				
				$return .= "\t" . '<input name="parent" type="hidden" value="' . $data['parent_item_id'] . '"/>' . "\n";
				$return .= "\t" . '<input name="previous" type="hidden" value="' . $data['previous_item_id'] . '"/>' . "\n";
				
			$return .= '</form>';
		
		$return .= '</div>';
		
		return $return;
	}
	
	/**
	 * Return HTML form to allow prerequisites selection
	 *
	 * @param	integer Item ID
	 * @return	string	HTML form
	 */
	function display_item_prerequisites_form($item_id)
	{
		global $charset;
		$tbl_lp_item = Database::get_course_table('lp_item');
		
		/* current prerequisite */
		$sql = "
			SELECT *
			FROM " . $tbl_lp_item . "
			WHERE id = " . $item_id;
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$row = Database::fetch_array($result);
		
		$preq_id = $row['prerequisite'];
		//$preq_mastery = $row['mastery_score'];
		//$preq_max = $row['max_score'];
		
		$return = $this->display_manipulate($item_id, TOOL_DOCUMENT);
		$return .= '<div class="sectiontitle">';
		$return .= get_lang('AddEditPrerequisites');
		$return .= '</div>';
		$return .= '<div class="sectioncomment">';
		$return .= '<form method="POST">';
		$return .= '<table class="lp_prerequisites">';
		$return .= '<tr>';
		$return .= '<th></th>';
		$return .= '<th >'.get_lang('Minimum').'</th>';
		$return .= '<th>'.get_lang('Maximum').'</th>';
		$return .= '</tr>';
		$return .= '<tr>';
		$return .= '<td class="radio">';
		$return .= '<input checked="checked" id="idNone" name="prerequisites" style="margin-left:0; margin-right:10px;" type="radio" />';
		$return .= '<label for="idNone">'.get_lang('None').'</label>';
		$return .= '</td><td colspan="2" />';
		$return .= '</tr>';
					
		$sql = "
			SELECT *
			FROM " . $tbl_lp_item . "
			WHERE
				lp_id = " . $this->lp_id;
		
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$arrLP = array();
		while($row = Database::fetch_array($result))
		{
			$arrLP[] = array(
				'id' => $row['id'],
				'item_type' => $row['item_type'],
				'title' => mb_convert_encoding($row['title'],$charset,$this->encoding),
				'ref'   => $row['ref'],
				'description' => $row['description'],
				'parent_item_id' => $row['parent_item_id'],
				'previous_item_id' => $row['previous_item_id'],
				'next_item_id' => $row['next_item_id'],
				'max_score' => $row['max_score'],
				'min_score' => $row['min_score'],
				'mastery_score' => $row['mastery_score'],
                'prerequisite' => $row['prerequisite'],
				'next_item_id' => $row['next_item_id'],
				'display_order' => $row['display_order']);
			if($row['ref'] == $preq_id)
			{
				$preq_mastery = $row['mastery_score'];
				$preq_max = $row['max_score'];
			}
		}		
		$this->tree_array($arrLP);		
		$arrLP = $this->arrMenu;		
		unset($this->arrMenu);
		
		for($i = 0; $i < count($arrLP); $i++)
		{
			if($arrLP[$i]['id'] == $item_id)
				break;
			$return .= '<tr>';
			$return .= '<td class="radio"' . (($arrLP[$i]['item_type'] != TOOL_QUIZ && $arrLP[$i]['item_type'] != TOOL_HOTPOTATOES) ? ' colspan="3"' : '') . '>';
			$return .= '<input' . (($arrLP[$i]['id'] == $preq_id) ? ' checked="checked" ' : '') . (($arrLP[$i]['item_type'] == 'dokeos_module' || $arrLP[$i]['item_type'] == 'dokeos_chapter') ? ' disabled="disabled" ' : ' ') . 'id="id' . $arrLP[$i]['id'] . '" name="prerequisites" style="margin-left:' . $arrLP[$i]['depth'] * 10 . 'px; margin-right:10px;" type="radio" value="' . $arrLP[$i]['id'] . '" />';
			$return .= '<img alt="" src="../img/lp_' . $arrLP[$i]['item_type'] . '.png" style="margin-right:5px;" title="" />';
			$return .= '<label for="id' . $arrLP[$i]['id'] . '">' . $arrLP[$i]['title'] . '</label>';
			$return .= '</td>';
			//$return .= '<td class="radio"' . (($arrLP[$i]['item_type'] != TOOL_HOTPOTATOES) ? ' colspan="3"' : '') . ' />';
			
			if($arrLP[$i]['item_type'] == TOOL_QUIZ)
			{	
				$return .= '<td class="exercise">';
				$return .= '<input maxlength="3" name="min_' . $arrLP[$i]['id'] . '" type="text" value="' . (($arrLP[$i]['id'] == $preq_id) ? $preq_mastery : 0) . '" />';
				$return .= '</td>';
				$return .= '<td class="exercise">';
				$return .= '<input maxlength="3" name="max_' . $arrLP[$i]['id'] . '" type="text" value="' . $arrLP[$i]['max_score'] . '" disabled="true" />';
				$return .= '</td>';
			}
			if($arrLP[$i]['item_type'] == TOOL_HOTPOTATOES)
			{
				$return .= '<td class="exercise">';
				$return .= '<input maxlength="3" name="min_' . $arrLP[$i]['id'] . '" type="text" value="' . (($arrLP[$i]['id'] == $preq_id) ? $preq_mastery : 0) . '" />';
				$return .= '</td>';
				$return .= '<td class="exercise">';
				$return .= '<input maxlength="3" name="max_' . $arrLP[$i]['id'] . '" type="text" value="' . $arrLP[$i]['max_score'] . '" disabled="true" />';
				$return .= '</td>';
			}
			$return .='</tr>';
		}
		$return .= '<tr>';
		$return .= '<td colspan="3">';
		$return .= '<button class="save" name="submit_button" type="submit">'.get_lang("ModifyPrerequisities").' </button></td>' . "\n";
		$return .= '</td>';
		$return .= '</tr>';
		$return .= '</table>';
		$return .= '</form>';
		$return .= '</div>';
		
		return $return;
	}
	
	/**
	 * Creates a list with all the documents in it
	 *
	 * @return string
	 */
	function get_documents()
	{
		global $_course;
		
		$tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
		
		$sql_doc = "
			SELECT *
			FROM " . $tbl_doc . "
			WHERE
				path NOT LIKE '%_DELETED_%' 
			ORDER BY path ASC";
		$res_doc = api_sql_query($sql_doc, __FILE__, __LINE__);
		
		$return = '<div class="lp_resource_header"' . " onclick=\"if(document.getElementById('resDoc').style.display == 'block') {document.getElementById('resDoc').style.display = 'none';} else {document.getElementById('resDoc').style.display = 'block';}\"" . '><img alt="" src="../img/lp_' . TOOL_DOCUMENT . '.gif" style="margin-right:5px;" title="" />'.get_lang("Documents").'</div>';
		$return .= '<div class="lp_resource_elements" id="resDoc">';
		
		
		$resources=api_store_result($res_doc);
		
		$resources_sorted = array();
		
		// if you want to debug it, I advise you to do "echo" on the eval statements
		
		foreach($resources as $resource)
		{
			$resource_paths = explode('/',$resource['path']);
			array_shift($resource_paths);
			$path_to_eval = $last_path = '';
			$is_file = false;
			foreach($resource_paths as $key => $resource_path)
			{
				if(strpos($resource_path,'.')===false && $key != count($resource_paths)-1)
				{ // it's a folder
					$path_to_eval .= '["'.$resource_path.'"]["files"]';
				}
				else if(strpos($resource_path,'.')!==false)
					$is_file = true;
				$last_path = $resource_path;
			}
			if($is_file)
			{
				eval('$resources_sorted'.$path_to_eval.'['.$resource['id'].'] = "'.$last_path.'";');
			}
			else
			{
				eval('$resources_sorted'.$path_to_eval.'["'.$last_path.'"]["id"]='.$resource['id'].';');
			}
			
		}
		$return .=$this->write_resources_tree($resources_sorted);
		
		$return .='</div>';

		if(Database::num_rows($res_doc) == 0)
			$return .= '<div class="lp_resource_element">'.get_lang("NoDocuments").'</div>';
		

		return $return;
	}
	
	/**
	 * Generate and return an HTML list of resources based on a given array.
	 * 
	 * This list is used to show the course creator a list of available resources to choose from
	 * when creating a learning path.
	 * @param	array	Array of elements to add to the list
	 * @param	integer Enables the tree display by shifting the new elements a certain distance to the right
	 * @return	string	The HTML list
	 */
	function write_resources_tree($resources_sorted, $num=0){
		
		include_once(api_get_path(LIBRARY_PATH).'fileDisplay.lib.php');
		
		if(count($resources_sorted)>0)
		{
			foreach($resources_sorted as $key=>$resource)
			{
				
				if(is_int($resource['id']))
				{ // it's a folder
					$return .= '<div><div style="margin-left:' . ($num * 15) . 'px;margin-right:5px;"><img style="cursor: pointer;" src="../img/nolines_plus.gif" align="absmiddle" id="img_'.$resource["id"].'" onclick="testResources(\''.$resource["id"].'\',\'img_'.$resource["id"].'\')"><img alt="" src="../img/lp_folder.gif" title="" align="absmiddle" />&nbsp;<span onclick="testResources(\''.$resource["id"].'\',\'img_'.$resource["id"].'\')" style="cursor: pointer;" >'.$key.'</span></div><div style="display: none;" id="'.$resource['id'].'">';
					$return .= $this->write_resources_tree($resource['files'], $num+1);
					$return .= "</div></div>\r\n";
				}
				else
				{
					// it's a file
					$icon = choose_image($resource);
					$position = strrpos($icon,'.');
					$icon=substr($icon,0,$position).'_small.gif';
					$return .= '<div><div style="margin-left:' . (($num+1) * 15) . 'px;margin-right:5px;"><a href="' . api_get_self() . '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_DOCUMENT . '&amp;file=' . $key . '&amp;lp_id=' . $this->lp_id . '"><img alt="" src="../img/'.$icon.'" title="" />&nbsp;'.$resource."</a></div></div>\r\n";
				}
				
			}
		}		
		return $return;
	}
	
	/**
	 * Creates a list with all the exercises (quiz) in it
	 *
	 * @return string
	 */
	function get_exercises()
	{
		// new for hotpotatoes
		$uploadPath = DIR_HOTPOTATOES; //defined in main_api
		$tbl_doc = Database::get_course_table(TABLE_DOCUMENT);
		$tbl_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
		
		$sql_quiz = "
			SELECT * 
			FROM " . $tbl_quiz . "
			WHERE active<>'-1'
			ORDER BY title ASC";
			
		$sql_hot = "SELECT * FROM ".$tbl_doc." " .
				" WHERE path LIKE '".$uploadPath."/%/%htm%'" .
				" ORDER BY id ASC";

		$res_quiz = api_sql_query($sql_quiz, __FILE__, __LINE__);
	  	$res_hot = api_sql_query($sql_hot, __FILE__, __LINE__);

		$return .= '<div class="lp_resource_header"' . " onclick=\"if(document.getElementById('resExercise').style.display == 'block') {document.getElementById('resExercise').style.display = 'none';} else {document.getElementById('resExercise').style.display = 'block';}\"" . ' ><img align="left" alt="" src="../img/lp_' . TOOL_QUIZ . '.gif" style="margin-right:5px;" title="" />'.get_lang('Quiz').'</div>';
		$return .= '<div class="lp_resource_elements" id="resExercise">';

		while ($row_hot = Database::fetch_array($res_hot)) {
			$return .= '<div class="lp_resource_element">';
			//display quizhotpotatoes
			$return .= '<img alt="" src="../img/jqz.gif" style="margin-right:5px;" title="" />';
			$return .= '<a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_HOTPOTATOES . '&amp;file=' . $row_hot['id'] . '&amp;lp_id=' . $this->lp_id . '">' . $row_hot['title'] . '</a>';
			//$return .= $row_quiz['title'];
			$return .= '</div>';
		}

    	while($row_quiz = Database::fetch_array($res_quiz)) {
			$return .= '<div class="lp_resource_element">';
			$return .= '<img alt="" src="../img/quizz_small.gif" style="margin-right:5px;" title="" />';
			$return .= '<a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_QUIZ . '&amp;file=' . $row_quiz['id'] . '&amp;lp_id=' . $this->lp_id . '">' . $row_quiz['title'] . '</a>';
			//$return .= $row_quiz['title'];
			$return .= '</div>'; 
		}
			
		if(Database::num_rows($res_quiz) == 0)
			$return .= '<div class="lp_resource_element">'.get_lang("NoExercisesAvailable").'</div>';
		
		$return .= '<div class="lp_resource_element">';	
			$return .= '<img alt="" src="../img/new_test_small.gif" style="margin-right:5px;" title="" />';
			$return .= '<a href="'.api_get_path(REL_CODE_PATH).'exercice/exercise_admin.php">' . get_lang('NewExercise') . '</a>';
		$return .= '</div>';

		$return .= '</div>';
		
		return $return;

	}

	/**
	 * Creates a list with all the links in it
	 *
	 * @return string
	 */

	function get_links()
	{
		$tbl_link = Database::get_course_table(TABLE_LINK);
			
		$sql_link = "
			SELECT *
			FROM " . $tbl_link . "
			ORDER BY title ASC";
		$res_link = api_sql_query($sql_link, __FILE__, __LINE__);
		
		$return .= '<div class="lp_resource_header"' . " onclick=\"if(document.getElementById('resLink').style.display == 'block') {document.getElementById('resLink').style.display = 'none';} else {document.getElementById('resLink').style.display = 'block';}\"" . '><img alt="" src="../img/lp_' . TOOL_LINK . '.gif" style="margin-right:5px;" title="" />'.get_lang("Links").'</div>';
		$return .= '<div class="lp_resource_elements" id="resLink">';
		
			while($row_link = Database::fetch_array($res_link))
			{
				$return .= '<div class="lp_resource_element">';
				
					$return .= '<img alt="" src="../img/file_html_small.gif" style="margin-right:5px;" title="" />';
					$return .= '<a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_LINK . '&amp;file=' . $row_link['id'] . '&amp;lp_id=' . $this->lp_id . '">' . $row_link['title'] . '</a>';
				
				$return .= '</div>';
			}
		$return .= '<div class="lp_resource_element">';
			$return .= '<img alt="" src="../img/file_html_new_small.gif" style="margin-right:5px;" title="" />';
			$return .= '<a href="'.api_get_path(REL_CODE_PATH).'link/link.php?'.api_get_cidreq().'&action=addlink" title="' . get_lang('LinkAdd') . '">' . get_lang('LinkAdd') . '</a>';
		$return .= '</div>';
			
			if(Database::num_rows($res_link) == 0)
				$return .= '<div class="lp_resource_element">'.get_lang("NoLinksAvailable").'</div>';
		
		$return .= '</div>';
		
		return $return;
	}
	
	/**
	 * Creates a list with all the student publications in it
	 *
	 * @return unknown
	 */
	function get_student_publications()
	{
		$tbl_student = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
			
		$sql_student = "
			SELECT *
			FROM " . $tbl_student . "
			ORDER BY title ASC";
		$res_student = api_sql_query($sql_student, __FILE__, __LINE__);
		
		$return .= '<div class="lp_resource_header"' . " onclick=\"if(document.getElementById('resStudent').style.display == 'block') {document.getElementById('resStudent').style.display = 'none';} else {document.getElementById('resStudent').style.display = 'block';}\"" . '><img alt="" src="../img/lp_' . TOOL_STUDENTPUBLICATION . '.gif" style="margin-right:5px;" title="" />'.get_lang('Student_publication').'</div>';
		$return .= '<div class="lp_resource_elements" id="resStudent">';
		$return .= '<div class="lp_resource_element">';
		$return .= '<img align="left" alt="" src="../img/works_small.gif" style="margin-right:5px;" title="" />';
		$return .= '<a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_STUDENTPUBLICATION . '&amp;lp_id=' . $this->lp_id . '">' . get_lang('AddAssignmentPage') . '</a>';
		$return .= '</div>';
		$return .= '</div>';
		
		return $return;
	}
	
	/**
	 * Creates a list with all the forums in it
	 *
	 * @return string
	 */
	function get_forums()
	{
		include ('../forum/forumfunction.inc.php');
		include ('../forum/forumconfig.inc.php');
		global $table_forums, $table_threads,$table_posts, $table_item_property, $table_users;
		$table_forums = Database :: get_course_table(TABLE_FORUM);
		$table_threads = Database :: get_course_table(TABLE_FORUM_THREAD);
		$table_posts = Database :: get_course_table(TABLE_FORUM_POST);
		$table_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY);
		$table_users = Database :: get_main_table(TABLE_MAIN_USER);
		$a_forums = get_forums();
		
		$return .= '<div class="lp_resource_header"' . " onclick=\"if(document.getElementById('forums').style.display == 'block') {document.getElementById('forums').style.display = 'none';} else {document.getElementById('forums').style.display = 'block';}\"" . '><img alt="" src="../img/lp_forum.gif" style="margin-right:5px;" title="" />'.get_lang('Forums').'</div>';
		$return .= '<div class="lp_resource_elements"  style="border:1px solid #999999;" id="forums">';
		
		foreach($a_forums as $forum)
		{
			$return .= '<div class="lp_resource_element">';
			$return .= '<script type="text/javascript">
						function toggle_forum(forum_id){
							if(document.getElementById("forum_"+forum_id+"_content").style.display == "none"){
								document.getElementById("forum_"+forum_id+"_content").style.display = "block";
								document.getElementById("forum_"+forum_id+"_opener").src = "'.api_get_path(WEB_IMG_PATH).'remove.gif";
							}
							else {
								document.getElementById("forum_"+forum_id+"_content").style.display = "none";
								document.getElementById("forum_"+forum_id+"_opener").src = "'.api_get_path(WEB_IMG_PATH).'add.gif";
							}
						}
						</script>
						';
			$return .= '<img alt="" src="../img/lp_forum.gif" style="margin-right:5px;" title="" />';
			$return .= '<a style="cursor:hand" onclick="toggle_forum('.$forum['forum_id'].')" style="vertical-align:middle"><img src="'.api_get_path(WEB_IMG_PATH).'add.gif" id="forum_'.$forum['forum_id'].'_opener" align="absbottom" /></a>
						<a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_FORUM . '&amp;forum_id=' . $forum['forum_id'] . '&amp;lp_id=' . $this->lp_id . '" style="vertical-align:middle">' . $forum['forum_title'] . '</a><ul style="display:none" id="forum_'.$forum['forum_id'].'_content">';
			$a_threads = get_threads($forum['forum_id']);
			if(is_array($a_threads)){
				foreach($a_threads as $thread)
				{
					$return .=  '<li><a href="' .api_get_self(). '?cidReq=' . $_GET['cidReq'] . '&amp;action=add_item&amp;type=' . TOOL_THREAD . '&amp;thread_id=' . $thread['thread_id'] . '&amp;lp_id=' . $this->lp_id . '">' . $thread['thread_title'] . '</a></li>';
				}
			}
			$return .= '</ul></div>';
		}
		
		$return .= '<div class="lp_resource_element">';
		$return .= '<img alt="" src="../img/forum_new_small.gif" style="margin-right:5px;" title="" />';
		$return .= '<a href="'.api_get_path(REL_CODE_PATH).'forum/index.php?'.api_get_cidreq().'&action=add&amp;content=forum" title="'.get_lang('CreateANewForum').'">'.get_lang('CreateANewForum').'</a>';
		$return .= '</div>';
		
		return $return;
	}
	
	/**
	 * Exports the learning path as a SCORM package. This is the main function that
	 * gathers the content, transforms it, writes the imsmanifest.xml file, zips the
	 * whole thing and returns the zip.
	 * 
	 * This method needs to be called in PHP5, as it will fail with non-adequate
	 * XML package (like the ones for PHP4), and it is *not* a static method, so
	 * you need to call it on a learnpath object.
	 * @TODO The method might be redefined later on in the scorm class itself to avoid
	 * creating a SCORM structure if there is one already. However, if the initial SCORM
	 * path has been modified, it should use the generic method here below.
	 * @TODO link this function with the export_lp() function in the same class
	 * @param	string	Optional name of zip file. If none, title of learnpath is
	 * 					domesticated and trailed with ".zip"
	 * @return	string	Returns the zip package string, or null if error 
	 */
	 function scorm_export()
	 {
	 	global $_course;
	 	if (!class_exists('DomDocument'))
	 	{
	 		error_log('DOM functions not supported for PHP version below 5.0',0);
			$this->error = 'PHP DOM functions not supported for PHP versions below 5.0';
			return null;
		}
		//remove memory and time limits as much as possible as this might be a long process...
		if(function_exists('ini_set'))
		{
			$mem = ini_get('memory_limit');
			if(substr($mem,-1,1)=='M')
			{
				$mem_num = substr($mem,0,-1);
				if($mem_num<128)
				{
					ini_set('memory_limit','128M');
				}
			}
			else
			{
				ini_set('memory_limit','128M');
			}
			ini_set('max_execution_time',600);
		//}else{
			//error_log('Scorm export: could not change memory and time limits',0);
		}

	 	//Create the zip handler (this will remain available throughout the method)
		$garbage_path = api_get_path(GARBAGE_PATH);
		$sys_course_path = api_get_path(SYS_COURSE_PATH);
		$temp_dir_short = uniqid();
		$temp_zip_dir = $garbage_path."/".$temp_dir_short;
		$temp_zip_file = $temp_zip_dir."/".md5(time()).".zip";
		$zip_folder=new PclZip($temp_zip_file);
		$current_course_path = api_get_path(SYS_COURSE_PATH).api_get_course_path();
		$root_path = $main_path = api_get_path(SYS_PATH);
		$files_cleanup = array();
		
		//place to temporarily stash the zipfiles
		//create the temp dir if it doesn't exist
		//or do a cleanup befor creating the zipfile
		if(!is_dir($temp_zip_dir))
		{
			mkdir($temp_zip_dir);
		}
		else 
		{//cleanup: check the temp dir for old files and delete them
			$handle=opendir($temp_zip_dir);
			while (false!==($file = readdir($handle)))
			{
				if ($file != "." && $file != "..")
				{
					unlink("$temp_zip_dir/$file");
				}
			}
		    closedir($handle);
		}
		$zip_files = $zip_files_abs = $zip_files_dist = array();
		if(is_dir($current_course_path.'/scorm/'.$this->path) && is_file($current_course_path.'/scorm/'.$this->path.'/imsmanifest.xml'))
		{	
			// remove the possible . at the end of the path
			$dest_path_to_lp = substr($this->path, -1) == '.' ? substr($this->path, 0, -1) : $this->path;
			$dest_path_to_scorm_folder = str_replace('//','/',$temp_zip_dir.'/scorm/'.$dest_path_to_lp);
			$perm = api_get_setting('permissions_for_new_directories');
			$perm = octdec(!empty($perm)?$perm:'0770');
			mkdir ($dest_path_to_scorm_folder, $perm, true);
			$zip_files_dist = copyr($current_course_path.'/scorm/'.$this->path, $dest_path_to_scorm_folder, array('imsmanifest'), $zip_files);
		}
	 	//Build a dummy imsmanifest structure. Do not add to the zip yet (we still need it)
	 	//This structure is developed following regulations for SCORM 1.2 packaging in the SCORM 1.2 Content
	 	//Aggregation Model official document, secion "2.3 Content Packaging"
	 	$xmldoc = new DOMDocument('1.0',$this->encoding);
	 	$root = $xmldoc->createElement('manifest');
	 	$root->setAttribute('identifier','SingleCourseManifest');
	 	$root->setAttribute('version','1.1');
	 	$root->setAttribute('xmlns','http://www.imsproject.org/xsd/imscp_rootv1p1p2');
	 	$root->setAttribute('xmlns:adlcp','http://www.adlnet.org/xsd/adlcp_rootv1p2');
	 	$root->setAttribute('xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
	 	$root->setAttribute('xsi:schemaLocation','http://www.imsproject.org/xsd/imscp_rootv1p1p2 imscp_rootv1p1p2.xsd
	 			http://www.imsglobal.org/xsd/imsmd_rootv1p2p1 imsmd_rootv1p2p1.xsd
                http://www.adlnet.org/xsd/adlcp_rootv1p2 adlcp_rootv1p2.xsd');
	 	//Build mandatory sub-root container elements
	 	$metadata = $xmldoc->createElement('metadata');
	 	$md_schema = $xmldoc->createElement('schema','ADL SCORM');
	 	$metadata->appendChild($md_schema);
	 	$md_schemaversion = $xmldoc->createElement('schemaversion','1.2');
	 	$metadata->appendChild($md_schemaversion);
	 	$root->appendChild($metadata);
	 	
	 	$organizations = $xmldoc->createElement('organizations');
	 	
	 	$resources = $xmldoc->createElement('resources');
	 	
	 	//Build the only organization we will use in building our learnpaths
	 	$organizations->setAttribute('default','dokeos_scorm_export');
	 	$organization = $xmldoc->createElement('organization');
	 	$organization->setAttribute('identifier','dokeos_scorm_export');
	 	//to set the title of the SCORM entity (=organization), we take the name given
	 	//in Dokeos and convert it to HTML entities using the Dokeos charset (not the
	 	//learning path charset) as it is the encoding that defines how it is stored
	 	//in the database. Then we convert it to HTML entities again as the "&" character
	 	//alone is not authorized in XML (must be &amp;)
	 	//The title is then decoded twice when extracting (see scorm::parse_manifest)
	 	global $charset;
	 	$org_title = $xmldoc->createElement('title',htmlentities(htmlentities($this->get_name(),ENT_QUOTES,$charset)));
	 	$organization->appendChild($org_title);
	 	
	 	//For each element, add it to the imsmanifest structure, then add it to the zip.
	 	//Always call the learnpathItem->scorm_export() method to change it to the SCORM
	 	//format
	 	$link_updates = array();
	 	foreach($this->items as $index => $item){
	 		if(!in_array($item->type , array(TOOL_QUIZ, TOOL_FORUM, TOOL_THREAD, TOOL_LINK, TOOL_STUDENTPUBLICATION)))
	 		{
		 		//get included documents from this item
		 		if($item->type=='sco')
		 			$inc_docs = $item->get_resources_from_source(null,api_get_path(SYS_COURSE_PATH).api_get_course_path().'/'.'scorm/'.$this->path.'/'.$item->get_path());
		 		else
		 			$inc_docs = $item->get_resources_from_source();
		 		//give a child element <item> to the <organization> element
		 		$my_item_id = $item->get_id();
		 		$my_item = $xmldoc->createElement('item');
		 		$my_item->setAttribute('identifier','ITEM_'.$my_item_id); 
		 		$my_item->setAttribute('identifierref','RESOURCE_'.$my_item_id); 
		 		$my_item->setAttribute('isvisible','true');
		 		//give a child element <title> to the <item> element
		 		$my_title = $xmldoc->createElement('title',htmlspecialchars($item->get_title(),ENT_QUOTES));
		 		$my_item->appendChild($my_title);
		 		//give a child element <adlcp:prerequisites> to the <item> element
		 		$my_prereqs = $xmldoc->createElement('adlcp:prerequisites',$this->get_scorm_prereq_string($my_item_id));
		 		$my_prereqs->setAttribute('type','aicc_script');
		 		$my_item->appendChild($my_prereqs);
		 		//give a child element <adlcp:maxtimeallowed> to the <item> element - not yet supported
		 		//$xmldoc->createElement('adlcp:maxtimeallowed','');
				//give a child element <adlcp:timelimitaction> to the <item> element - not yet supported
		 		//$xmldoc->createElement('adlcp:timelimitaction','');
		 		//give a child element <adlcp:datafromlms> to the <item> element - not yet supported
		 		//$xmldoc->createElement('adlcp:datafromlms','');
		 		//give a child element <adlcp:masteryscore> to the <item> element
		 		$my_masteryscore = $xmldoc->createElement('adlcp:masteryscore',$item->masteryscore);
		 		$my_item->appendChild($my_masteryscore);
		 		
		 		
		 		//attach this item to the organization element or hits parent if there is one
		 		if(!empty($item->parent) && $item->parent!=0)
		 		{
		 			$children = $organization->childNodes;
		 			$possible_parent = &$this->get_scorm_xml_node($children,'ITEM_'.$item->parent);
		 			if(is_object($possible_parent))
		 			{
		 				$possible_parent->appendChild($my_item);
		 			}
		 			else
		 			{
		 				if($this->debug>0){error_log('Parent ITEM_'.$item->parent.' of item ITEM_'.$my_item_id.' not found');}
		 			}
		 		}
		 		else
		 		{
		 			if($this->debug>0){error_log('No parent');}
		 			$organization->appendChild($my_item);
		 		}
		 		
		 		
		 		//get the path of the file(s) from the course directory root
				$my_file_path = $item->get_file_path('scorm/'.$this->path.'/');
				
				$my_xml_file_path = htmlentities($my_file_path); 
				$my_sub_dir = dirname($my_file_path); 
				$my_xml_sub_dir = htmlentities($my_sub_dir);
		 		//give a <resource> child to the <resources> element
		 		$my_resource = $xmldoc->createElement('resource');
		 		$my_resource->setAttribute('identifier','RESOURCE_'.$item->get_id());
		 		$my_resource->setAttribute('type','webcontent');
		 		$my_resource->setAttribute('href',$my_xml_file_path);
		 		//adlcp:scormtype can be either 'sco' or 'asset'
		 		if($item->type=='sco')
		 		{
			 		$my_resource->setAttribute('adlcp:scormtype','sco');
		 		}
		 		else
		 		{
			 		$my_resource->setAttribute('adlcp:scormtype','asset');
		 		}
		 		//xml:base is the base directory to find the files declared in this resource
		 		$my_resource->setAttribute('xml:base','');
		 		//give a <file> child to the <resource> element
		 		$my_file = $xmldoc->createElement('file');
		 		$my_file->setAttribute('href',$my_xml_file_path);
		 		$my_resource->appendChild($my_file);
	
		 		//dependency to other files - not yet supported
		 		$i = 1;
		 		foreach($inc_docs as $doc_info)
		 		{
		 			if(count($doc_info)<1 or empty($doc_info[0])){continue;}
		 			$my_dep = $xmldoc->createElement('resource');
		 			$res_id = 'RESOURCE_'.$item->get_id().'_'.$i;
		 			$my_dep->setAttribute('identifier',$res_id);
		 			$my_dep->setAttribute('type','webcontent');
		 			$my_dep->setAttribute('adlcp:scormtype','asset');
		 			$my_dep_file = $xmldoc->createElement('file');
		 			//check type of URL
		 			//error_log(__LINE__.'Now dealing with '.$doc_info[0].' of type '.$doc_info[1].'-'.$doc_info[2],0);
		 			if($doc_info[1] == 'remote')
		 			{ //remote file. Save url as is
		 				$my_dep_file->setAttribute('href',$doc_info[0]);
			 			$my_dep->setAttribute('xml:base','');
		 			}elseif($doc_info[1] == 'local'){
		 				switch($doc_info[2])
		 				{
		 					case 'url': //local URL - save path as url for now, don't zip file
								$abs_path = api_get_path(SYS_PATH).str_replace(api_get_path(WEB_PATH),'',$doc_info[0]);
								$current_dir = dirname($abs_path);
								$file_path = realpath($abs_path);
				 				$my_dep_file->setAttribute('href',$file_path);
					 			$my_dep->setAttribute('xml:base','');
			 					if(strstr($file_path,$main_path) !== false)
			 					{//the calculated real path is really inside the dokeos root path
			 						//reduce file path to what's under the DocumentRoot
			 						$file_path = substr($file_path,strlen($root_path)-1);
			 						//echo $file_path;echo '<br><br>';
			 						//error_log(__LINE__.'Reduced url path: '.$file_path,0);
			 						$zip_files_abs[] = $file_path;
			 						$link_updates[$my_file_path][] = array('orig'=>$doc_info[0],'dest'=>$file_path);
					 				$my_dep_file->setAttribute('href',$file_path);
			 			 			$my_dep->setAttribute('xml:base','');
			 					}
			 					else if (empty($file_path))
			 					{
			 						/*$document_root = substr(api_get_path(SYS_PATH), 0, strpos(api_get_path(SYS_PATH),api_get_path(REL_PATH)));
			 						if(strpos($document_root,-1)=='/')
			 						{
			 							$document_root = substr(0, -1, $document_root);
			 						}*/
			 						$file_path = $_SERVER['DOCUMENT_ROOT'].$abs_path;
			 						$file_path = str_replace('//','/',$file_path);
			 						if(file_exists($file_path))
			 						{
				 						$file_path = substr($file_path,strlen($current_dir)); // we get the relative path
				 						$zip_files[] = $my_sub_dir.'/'.$file_path;
				 						$link_updates[$my_file_path][] = array('orig'=>$doc_info[0],'dest'=>$file_path);
						 				$my_dep_file->setAttribute('href',$file_path);
				 			 			$my_dep->setAttribute('xml:base','');
			 						}
			 					}
		 						break;
		 					case 'abs': //absolute path from DocumentRoot. Save file and leave path as is in the zip
				 				$my_dep_file->setAttribute('href',$doc_info[0]);
		 			 			$my_dep->setAttribute('xml:base','');
	
			 					//$current_dir = dirname($current_course_path.'/'.$item->get_file_path()).'/';
                                //the next lines fix a bug when using the "subdir" mode of Dokeos, whereas
                                //an image path would be constructed as /var/www/subdir/subdir/img/foo.bar 
                                $abs_img_path_without_subdir = $doc_info[0];
                                $relp = api_get_path(REL_PATH); //the url-append config param
                                $pos = strpos($abs_img_path_without_subdir,$relp);
                                if($pos===0)
                                {
                                	$abs_img_path_without_subdir = '/'.substr($abs_img_path_without_subdir,strlen($relp));
                                }
								//$file_path = realpath(api_get_path(SYS_PATH).$doc_info[0]);
								$file_path = realpath(api_get_path(SYS_PATH).$abs_img_path_without_subdir);
                                $file_path = str_replace('\\','/',$file_path);
                                $file_path = str_replace('//','/',$file_path);
                                //error_log(__LINE__.'Abs path: '.$file_path,0);
								//prepare the current directory path (until just under 'document') with a trailing slash
								$cur_path = substr($current_course_path,-1)=='/'?$current_course_path:$current_course_path.'/';
								//check if the current document is in that path
								if(strstr($file_path,$cur_path) !== false)
								{
									//the document is in that path, now get the relative path
									//to the containing document
									$orig_file_path = dirname($cur_path.$my_file_path).'/';
									$relative_path ='';
									if(strstr($file_path,$cur_path)!==false)
									{
										$relative_path = substr($file_path,strlen($orig_file_path));
				 						$file_path = substr($file_path,strlen($cur_path));
									}
									else
									{
										//this case is still a problem as it's difficult to calculate a relative path easily
										//might still generate wrong links
				 						//$file_path = substr($file_path,strlen($cur_path));
				 						//calculate the directory path to the current file (without trailing slash)
										$my_relative_path = dirname($file_path);
										$my_relative_file = basename($file_path);
										//calculate the directory path to the containing file (without trailing slash)
										$my_orig_file_path = substr($orig_file_path,0,-1);
										$dotdot = '';
										$subdir = '';
										while(strstr($my_relative_path,$my_orig_file_path)===false && (strlen($my_orig_file_path)>1) && (strlen($my_relative_path)>1))
										{
											$my_relative_path2 = dirname($my_relative_path);
											$my_orig_file_path = dirname($my_orig_file_path);
											$subdir = substr($my_relative_path,strlen($my_relative_path2)+1)."/".$subdir;
											$dotdot += '../';
											$my_relative_path = $my_relative_path2;
										}
										$relative_path = $dotdot.$subdir.$my_relative_file;
									}
									//put the current document in the zip (this array is the array
									//that will manage documents already in the course folder - relative)
			 						$zip_files[] = $file_path;
			 						//update the links to the current document in the containing document (make them relative)
			 						$link_updates[$my_file_path][] = array('orig'=>$doc_info[0],'dest'=>$relative_path);
					 				$my_dep_file->setAttribute('href',$file_path);
			 			 			$my_dep->setAttribute('xml:base','');
								}
			 					elseif(strstr($file_path,$main_path) !== false)
			 					{//the calculated real path is really inside the dokeos root path
			 						//reduce file path to what's under the DocumentRoot
			 						$file_path = substr($file_path,strlen($root_path));
			 						//echo $file_path;echo '<br><br>';
			 						//error_log('Reduced path: '.$file_path,0);
			 						$zip_files_abs[] = $file_path;
			 						$link_updates[$my_file_path][] = array('orig'=>$doc_info[0],'dest'=>$file_path);
					 				$my_dep_file->setAttribute('href','document/'.$file_path);
			 			 			$my_dep->setAttribute('xml:base','');
			 					}
			 					else if (empty($file_path))
			 					{
                                    /*$document_root = substr(api_get_path(SYS_PATH), 0, strpos(api_get_path(SYS_PATH),api_get_path(REL_PATH)));
			 						if(strpos($document_root,-1)=='/')
			 						{
			 							$document_root = substr(0, -1, $document_root);
			 						}*/
			 						$file_path = $_SERVER['DOCUMENT_ROOT'].$doc_info[0];
			 						$file_path = str_replace('//','/',$file_path);
			 						if(file_exists($file_path))
			 						{
				 						$file_path = substr($file_path,strlen($current_dir)); // we get the relative path
				 						$zip_files[] = $my_sub_dir.'/'.$file_path;
				 						$link_updates[$my_file_path][] = array('orig'=>$doc_info[0],'dest'=>$file_path);
						 				$my_dep_file->setAttribute('href','document/'.$file_path);
				 			 			$my_dep->setAttribute('xml:base','');
			 						}
			 					}
		 						break;
		 					case 'rel': //path relative to the current document. Save xml:base as current document's directory and save file in zip as subdir.file_path
			 					if(substr($doc_info[0],0,2)=='..')
				 				{ //relative path going up
				 					$current_dir = dirname($current_course_path.'/'.$item->get_file_path()).'/';
				 					$file_path = realpath($current_dir.$doc_info[0]);
				 					//error_log($file_path.' <-> '.$main_path,0);
				 					if(strstr($file_path,$main_path) !== false)
				 					{//the calculated real path is really inside the dokeos root path
				 						//reduce file path to what's under the DocumentRoot
				 						$file_path = substr($file_path,strlen($root_path));
				 						//error_log('Reduced path: '.$file_path,0);
				 						$zip_files_abs[] = $file_path;
				 						$link_updates[$my_file_path][] = array('orig'=>$doc_info[0],'dest'=>$file_path);
						 				$my_dep_file->setAttribute('href','document/'.$file_path);
				 			 			$my_dep->setAttribute('xml:base','');
				 					}
				 				}else{
				 					$zip_files[] = $my_sub_dir.'/'.$doc_info[0];
					 				$my_dep_file->setAttribute('href',$doc_info[0]);
			 			 			$my_dep->setAttribute('xml:base',$my_xml_sub_dir);
				 				}
		 						break;
		 					default:
				 				$my_dep_file->setAttribute('href',$doc_info[0]);
		 			 			$my_dep->setAttribute('xml:base','');
		 						break;
		 				}
		 			}
		 			$my_dep->appendChild($my_dep_file);
		 			$resources->appendChild($my_dep);
		 			$dependency = $xmldoc->createElement('dependency');
		 			$dependency->setAttribute('identifierref',$res_id);
		 			$my_resource->appendChild($dependency);
		 			$i++;
		 		}
		 		//$my_dependency = $xmldoc->createElement('dependency');
		 		//$my_dependency->setAttribute('identifierref','');
		 		$resources->appendChild($my_resource);
		 		$zip_files[] = $my_file_path;
		 		
				//error_log('File '.$my_file_path. ' added to $zip_files',0);
	 		}
	 		else
	 		{ // if the item is a quiz or a link or whatever non-exportable, we include a step indicating it
	 				 		
	 			if($item->type == TOOL_LINK)
	 			{
		 			$my_item = $xmldoc->createElement('item');
			 		$my_item->setAttribute('identifier','ITEM_'.$item->get_id()); 
			 		$my_item->setAttribute('identifierref','RESOURCE_'.$item->get_id()); 
			 		$my_item->setAttribute('isvisible','true');
			 		//give a child element <title> to the <item> element
			 		$my_title = $xmldoc->createElement('title',htmlspecialchars($item->get_title(),ENT_QUOTES));
			 		$my_item->appendChild($my_title);
			 		//give a child element <adlcp:prerequisites> to the <item> element
			 		$my_prereqs = $xmldoc->createElement('adlcp:prerequisites',$item->get_prereq_string());
			 		$my_prereqs->setAttribute('type','aicc_script');
			 		$my_item->appendChild($my_prereqs);
			 		//give a child element <adlcp:maxtimeallowed> to the <item> element - not yet supported
			 		//$xmldoc->createElement('adlcp:maxtimeallowed','');
					//give a child element <adlcp:timelimitaction> to the <item> element - not yet supported
			 		//$xmldoc->createElement('adlcp:timelimitaction','');
			 		//give a child element <adlcp:datafromlms> to the <item> element - not yet supported
			 		//$xmldoc->createElement('adlcp:datafromlms','');
			 		//give a child element <adlcp:masteryscore> to the <item> element
			 		$my_masteryscore = $xmldoc->createElement('adlcp:masteryscore',$item->masteryscore);
			 		$my_item->appendChild($my_masteryscore);
			 		
			 		
			 		//attach this item to the organization element or its parent if there is one
			 		if(!empty($item->parent) && $item->parent!=0)
			 		{
			 			$children = $organization->childNodes;
				        for($i=0;$i<$children->length;$i++){
					        $item_temp = $children->item($i);
					        if ($item_temp -> nodeName == 'item')
					        {
					        	if($item_temp->getAttribute('identifier') == 'ITEM_'.$item->parent)
					        	{
					        		$item_temp -> appendChild($my_item);
					        	}
					        	
					        }
				        }
			 		}
			 		else
			 		{
			 			$organization->appendChild($my_item);
			 		}

		 			$my_file_path = 'link_'.$item->get_id().'.html';
	 				$sql = 'SELECT url, title FROM '.Database :: get_course_table(TABLE_LINK).' WHERE id='.$item->path;
	 				$rs = api_sql_query($sql, __FILE__, __LINE__);
	 				if($link = Database :: fetch_array($rs))
	 				{
	 					$url = $link['url'];
	 					$title = stripslashes($link['title']);
	 					$links_to_create[$my_file_path] = array('title'=>$title,'url'=>$url);
						$my_xml_file_path = htmlentities($my_file_path); 
						$my_sub_dir = dirname($my_file_path); 
						$my_xml_sub_dir = htmlentities($my_sub_dir);
				 		//give a <resource> child to the <resources> element
				 		$my_resource = $xmldoc->createElement('resource');
				 		$my_resource->setAttribute('identifier','RESOURCE_'.$item->get_id());
				 		$my_resource->setAttribute('type','webcontent');
				 		$my_resource->setAttribute('href',$my_xml_file_path);
				 		//adlcp:scormtype can be either 'sco' or 'asset'
				 		$my_resource->setAttribute('adlcp:scormtype','asset');
				 		//xml:base is the base directory to find the files declared in this resource
				 		$my_resource->setAttribute('xml:base','');
				 		//give a <file> child to the <resource> element
				 		$my_file = $xmldoc->createElement('file');
				 		$my_file->setAttribute('href',$my_xml_file_path);
				 		$my_resource->appendChild($my_file);
				 		$resources->appendChild($my_resource);
	 				}
	 			}
	 			elseif($item->type == TOOL_QUIZ)
	 			{
	 				require_once(api_get_path(SYS_CODE_PATH).'exercice/exercise.class.php');
	 				$exe_id = $item->path; //should be using ref when everything will be cleaned up in this regard
	 				$exe = new Exercise();
	 				$exe->read($exe_id);
			 		$my_item = $xmldoc->createElement('item');
			 		$my_item->setAttribute('identifier','ITEM_'.$item->get_id()); 
			 		$my_item->setAttribute('identifierref','RESOURCE_'.$item->get_id()); 
			 		$my_item->setAttribute('isvisible','true');
			 		//give a child element <title> to the <item> element
			 		$my_title = $xmldoc->createElement('title',htmlspecialchars($item->get_title(),ENT_QUOTES));
			 		$my_item->appendChild($my_title);
			 		//give a child element <adlcp:prerequisites> to the <item> element
			 		$my_prereqs = $xmldoc->createElement('adlcp:prerequisites',$item->get_prereq_string());
			 		$my_prereqs->setAttribute('type','aicc_script');
			 		$my_item->appendChild($my_prereqs);
			 		//give a child element <adlcp:masteryscore> to the <item> element
			 		$my_masteryscore = $xmldoc->createElement('adlcp:masteryscore',$item->masteryscore);
			 		$my_item->appendChild($my_masteryscore);

			 		//attach this item to the organization element or hits parent if there is one
			 		if(!empty($item->parent) && $item->parent!=0)
			 		{
			 			$children = $organization->childNodes;
				        for($i=0;$i<$children->length;$i++){
					        $item_temp = $children->item($i);
					        if ($item_temp -> nodeName == 'item')
					        {
					        	if($item_temp->getAttribute('identifier') == 'ITEM_'.$item->parent)
					        	{
					        		$item_temp -> appendChild($my_item);
					        	}
					        	
					        }
				        }
			 		}
			 		else
			 		{
			 			$organization->appendChild($my_item);
			 		}
			 		
			 		//include export scripts
			 		require_once(api_get_path(SYS_CODE_PATH).'exercice/export/scorm/scorm_export.php');

			 		//get the path of the file(s) from the course directory root
					//$my_file_path = $item->get_file_path('scorm/'.$this->path.'/');
					$my_file_path = 'quiz_'.$item->get_id().'.html';
			 		//write the contents of the exported exercise into a (big) html file
			 		//to later pack it into the exported SCORM. The file will be removed afterwards
			 		$contents = export_exercise($exe_id,true);
			 		$tmp_file_path = $garbage_path.$temp_dir_short.'/'.$my_file_path;
			 		$res = file_put_contents($tmp_file_path,$contents);
			 		if($res === false){error_log('Could not write into file '.$tmp_file_path.' '.__FILE__.' '.__LINE__,0);}
			 		$files_cleanup[] = $tmp_file_path;
			 		//error_log($tmp_path);die();
					$my_xml_file_path = htmlentities($my_file_path); 
					$my_sub_dir = dirname($my_file_path); 
					$my_xml_sub_dir = htmlentities($my_sub_dir);
			 		//give a <resource> child to the <resources> element
			 		$my_resource = $xmldoc->createElement('resource');
			 		$my_resource->setAttribute('identifier','RESOURCE_'.$item->get_id());
			 		$my_resource->setAttribute('type','webcontent');
			 		$my_resource->setAttribute('href',$my_xml_file_path);
			 		//adlcp:scormtype can be either 'sco' or 'asset'
			 		$my_resource->setAttribute('adlcp:scormtype','sco');
			 		//xml:base is the base directory to find the files declared in this resource
			 		$my_resource->setAttribute('xml:base','');
			 		//give a <file> child to the <resource> element
			 		$my_file = $xmldoc->createElement('file');
			 		$my_file->setAttribute('href',$my_xml_file_path);
			 		$my_resource->appendChild($my_file);

			 		//get included docs
		 			$inc_docs = $item->get_resources_from_source(null,$tmp_file_path);
			 		//dependency to other files - not yet supported
			 		$i = 1;
			 		foreach($inc_docs as $doc_info)
			 		{
			 			if(count($doc_info)<1 or empty($doc_info[0])){continue;}
			 			$my_dep = $xmldoc->createElement('resource');
			 			$res_id = 'RESOURCE_'.$item->get_id().'_'.$i;
			 			$my_dep->setAttribute('identifier',$res_id);
			 			$my_dep->setAttribute('type','webcontent');
			 			$my_dep->setAttribute('adlcp:scormtype','asset');
			 			$my_dep_file = $xmldoc->createElement('file');
			 			//check type of URL
			 			//error_log(__LINE__.'Now dealing with '.$doc_info[0].' of type '.$doc_info[1].'-'.$doc_info[2],0);
			 			if($doc_info[1] == 'remote')
			 			{ //remote file. Save url as is
			 				$my_dep_file->setAttribute('href',$doc_info[0]);
				 			$my_dep->setAttribute('xml:base','');
			 			}elseif($doc_info[1] == 'local'){
			 				switch($doc_info[2])
			 				{
			 					case 'url': //local URL - save path as url for now, don't zip file
						 			//save file but as local file (retrieve from URL)
									$abs_path = api_get_path(SYS_PATH).str_replace(api_get_path(WEB_PATH),'',$doc_info[0]);
									$current_dir = dirname($abs_path);
									$file_path = realpath($abs_path);
					 				$my_dep_file->setAttribute('href','document/'.$file_path);
						 			$my_dep->setAttribute('xml:base','');
				 					if(strstr($file_path,$main_path) !== false)
				 					{//the calculated real path is really inside the dokeos root path
				 						//reduce file path to what's under the DocumentRoot
				 						$file_path = substr($file_path,strlen($root_path));
				 						//echo $file_path;echo '<br><br>';
				 						//error_log('Reduced path: '.$file_path,0);
				 						$zip_files_abs[] = $file_path;
				 						$link_updates[$my_file_path][] = array('orig'=>$doc_info[0],'dest'=>'document/'.$file_path);
						 				$my_dep_file->setAttribute('href','document/'.$file_path);
				 			 			$my_dep->setAttribute('xml:base','');
				 					}
				 					else if (empty($file_path))
				 					{
				 						/*$document_root = substr(api_get_path(SYS_PATH), 0, strpos(api_get_path(SYS_PATH),api_get_path(REL_PATH)));
				 						if(strpos($document_root,-1)=='/')
				 						{
				 							$document_root = substr(0, -1, $document_root);
				 						}*/
				 						$file_path = $_SERVER['DOCUMENT_ROOT'].$abs_path;
				 						$file_path = str_replace('//','/',$file_path);
				 						if(file_exists($file_path))
				 						{
					 						$file_path = substr($file_path,strlen($current_dir)); // we get the relative path
					 						$zip_files[] = $my_sub_dir.'/'.$file_path;
					 						$link_updates[$my_file_path][] = array('orig'=>$doc_info[0],'dest'=>'document/'.$file_path);
							 				$my_dep_file->setAttribute('href','document/'.$file_path);
					 			 			$my_dep->setAttribute('xml:base','');
				 						}
				 					}
			 						break;
			 					case 'abs': //absolute path from DocumentRoot. Save file and leave path as is in the zip
				 					$current_dir = dirname($current_course_path.'/'.$item->get_file_path()).'/';
									$file_path = realpath($doc_info[0]);
					 				$my_dep_file->setAttribute('href',$file_path);
			 			 			$my_dep->setAttribute('xml:base','');
									
				 					if(strstr($file_path,$main_path) !== false)
				 					{//the calculated real path is really inside the dokeos root path
				 						//reduce file path to what's under the DocumentRoot
				 						$file_path = substr($file_path,strlen($root_path));
				 						//echo $file_path;echo '<br><br>';
				 						//error_log('Reduced path: '.$file_path,0);
				 						$zip_files_abs[] = $file_path;
				 						$link_updates[$my_file_path][] = array('orig'=>$doc_info[0],'dest'=>$file_path);
						 				$my_dep_file->setAttribute('href','document/'.$file_path);
				 			 			$my_dep->setAttribute('xml:base','');
				 					}
				 					else if (empty($file_path))
				 					{
				 						/*$document_root = substr(api_get_path(SYS_PATH), 0, strpos(api_get_path(SYS_PATH),api_get_path(REL_PATH)));
				 						if(strpos($document_root,-1)=='/')
				 						{
				 							$document_root = substr(0, -1, $document_root);
				 						}*/
				 						$file_path = $_SERVER['DOCUMENT_ROOT'].$doc_info[0];
				 						$file_path = str_replace('//','/',$file_path);
				 						if(file_exists($file_path))
				 						{
					 						$file_path = substr($file_path,strlen($current_dir)); // we get the relative path
					 						$zip_files[] = $my_sub_dir.'/'.$file_path;
					 						$link_updates[$my_file_path][] = array('orig'=>$doc_info[0],'dest'=>$file_path);
							 				$my_dep_file->setAttribute('href','document/'.$file_path);
					 			 			$my_dep->setAttribute('xml:base','');
				 						}
				 					}
			 						break;
			 					case 'rel': //path relative to the current document. Save xml:base as current document's directory and save file in zip as subdir.file_path
				 					if(substr($doc_info[0],0,2)=='..')
					 				{ //relative path going up
					 					$current_dir = dirname($current_course_path.'/'.$item->get_file_path()).'/';
					 					$file_path = realpath($current_dir.$doc_info[0]);
					 					//error_log($file_path.' <-> '.$main_path,0);
					 					if(strstr($file_path,$main_path) !== false)
					 					{//the calculated real path is really inside the dokeos root path
					 						//reduce file path to what's under the DocumentRoot
					 						$file_path = substr($file_path,strlen($root_path));
					 						//error_log('Reduced path: '.$file_path,0);
					 						$zip_files_abs[] = $file_path;
					 						$link_updates[$my_file_path][] = array('orig'=>$doc_info[0],'dest'=>$file_path);
							 				$my_dep_file->setAttribute('href','document/'.$file_path);
					 			 			$my_dep->setAttribute('xml:base','');
					 					}
					 				}else{
					 					$zip_files[] = $my_sub_dir.'/'.$doc_info[0];
						 				$my_dep_file->setAttribute('href',$doc_info[0]);
				 			 			$my_dep->setAttribute('xml:base',$my_xml_sub_dir);
					 				}
			 						break;
			 					default:
					 				$my_dep_file->setAttribute('href',$doc_info[0]);
			 			 			$my_dep->setAttribute('xml:base','');
			 						break;
			 				}
			 			}
			 			$my_dep->appendChild($my_dep_file);
			 			$resources->appendChild($my_dep);
			 			$dependency = $xmldoc->createElement('dependency');
			 			$dependency->setAttribute('identifierref',$res_id);
			 			$my_resource->appendChild($dependency);
			 			$i++;
			 		}
			 		$resources->appendChild($my_resource);
			 		$zip_files[] = $my_file_path;
	 				
				}
	 			else
	 			{
		 		
			 		//get the path of the file(s) from the course directory root
					$my_file_path = 'non_exportable.html';
					$my_xml_file_path = htmlentities($my_file_path); 
					$my_sub_dir = dirname($my_file_path); 
					$my_xml_sub_dir = htmlentities($my_sub_dir);
			 		//give a <resource> child to the <resources> element
			 		$my_resource = $xmldoc->createElement('resource');
			 		$my_resource->setAttribute('identifier','RESOURCE_'.$item->get_id());
			 		$my_resource->setAttribute('type','webcontent');
			 		$my_resource->setAttribute('href','document/'.$my_xml_file_path);
			 		//adlcp:scormtype can be either 'sco' or 'asset'
			 		$my_resource->setAttribute('adlcp:scormtype','asset');
			 		//xml:base is the base directory to find the files declared in this resource
			 		$my_resource->setAttribute('xml:base','');
			 		//give a <file> child to the <resource> element
			 		$my_file = $xmldoc->createElement('file');
			 		$my_file->setAttribute('href','document/'.$my_xml_file_path);
			 		$my_resource->appendChild($my_file);
			 		$resources->appendChild($my_resource);
			 		
	 			}
	 		}
	 	}
	 	$organizations->appendChild($organization);
	 	$root->appendChild($organizations);
	 	$root->appendChild($resources);
		$xmldoc->appendChild($root);
		//todo: add a readme file here, with a short description and a link to the Reload player
		//then add the file to the zip, then destroy the file (this is done automatically)
		// http://www.reload.ac.uk/scormplayer.html - once done, don't forget to close FS#138

		//error_log(print_r($zip_files,true),0);
		foreach($zip_files as $file_path)
		{
			if(empty($file_path)){continue;}
            //error_log(__LINE__.'getting document from '.$sys_course_path.$_course['path'].'/'.$file_path.' removing '.$sys_course_path.$_course['path'].'/',0);
			$dest_file = $garbage_path.$temp_dir_short.'/'.$file_path;
			$this->create_path($dest_file);
			//error_log('copy '.api_get_path('SYS_COURSE_PATH').$_course['path'].'/'.$file_path.' to '.api_get_path('GARBAGE_PATH').$temp_dir_short.'/'.$file_path,0);
			//echo $main_path.$file_path.'<br>';
			@copy($sys_course_path.$_course['path'].'/'.$file_path,$dest_file);
			//check if the file needs a link update
			if(in_array($file_path,array_keys($link_updates))){
				$string = file_get_contents($dest_file);
				unlink($dest_file);
				foreach($link_updates[$file_path] as $old_new)
				{
					//error_log('Replacing '.$old_new['orig'].' by '.$old_new['dest'].' in '.$file_path,0);
                    //this is an ugly hack that allows .flv files to be found by the flv player that
                    // will be added in document/main/inc/lib/flv_player/flv_player.swf and that needs
                    // to find the flv to play in document/main/, so we replace main/ in the flv path by
                    // ../../.. to return from inc/lib/flv_player to the document/main path
                    if(substr($old_new['dest'],-3)=='flv' && substr($old_new['dest'],0,5)=='main/')
                    {
                        $old_new['dest'] = str_replace('main/','../../../',$old_new['dest']);	
                    }
                    elseif(substr($old_new['dest'],-3)=='flv' && substr($old_new['dest'],0,6)=='video/')
                    {
                        $old_new['dest'] = str_replace('video/','../../../../video/',$old_new['dest']);   
                    }
					$string = str_replace($old_new['orig'],$old_new['dest'],$string);
				}
				file_put_contents($dest_file,$string);
			}
		}
		foreach($zip_files_abs as $file_path)
		{
			if(empty($file_path)){continue;}
			//error_log(__LINE__.'checking existence of '.$main_path.$file_path.'',0);
            if(!is_file($main_path.$file_path) || !is_readable($main_path.$file_path)){continue;}
			//error_log(__LINE__.'getting document from '.$main_path.$file_path.' removing '.api_get_path('SYS_COURSE_PATH').$_course['path'].'/',0);
			$dest_file = $garbage_path.$temp_dir_short.'/document/'.$file_path;
			$this->create_path($dest_file);
			//error_log('Created path '.api_get_path('GARBAGE_PATH').$temp_dir_short.'/document/'.$file_path,0);
			//error_log('copy '.api_get_path('SYS_COURSE_PATH').$_course['path'].'/'.$file_path.' to '.api_get_path('GARBAGE_PATH').$temp_dir_short.'/'.$file_path,0);
			//echo $main_path.$file_path.' - '.$dest_file.'<br>';
			
			copy($main_path.$file_path,$dest_file);
			//check if the file needs a link update
			if(in_array($file_path,array_keys($link_updates)))
			{
				$string = file_get_contents($dest_file);
				unlink($dest_file);
				foreach($link_updates[$file_path] as $old_new)
				{
					//error_log('Replacing '.$old_new['orig'].' by '.$old_new['dest'].' in '.$file_path,0);
                    //this is an ugly hack that allows .flv files to be found by the flv player that
                    // will be added in document/main/inc/lib/flv_player/flv_player.swf and that needs
                    // to find the flv to play in document/main/, so we replace main/ in the flv path by
                    // ../../.. to return from inc/lib/flv_player to the document/main path
                    if(substr($old_new['dest'],-3)=='flv' && substr($old_new['dest'],0,5)=='main/')
                    {
                        $old_new['dest'] = str_replace('main/','../../../',$old_new['dest']);   
                    }
					$string = str_replace($old_new['orig'],$old_new['dest'],$string);
				}
				file_put_contents($dest_file,$string);
			}
		}
		if(is_array($links_to_create))
		{
			foreach($links_to_create as $file=>$link)
			{
				$file_content = '<html><body><div style="text-align:center"><a href="'.$link['url'].'">'.$link['title'].'</a></div></body></html>';
				file_put_contents($garbage_path.$temp_dir_short.'/'.$file, $file_content);
			}
		}
		// add non exportable message explanation
		$lang_not_exportable = get_lang('ThisItemIsNotExportable');
		$file_content = 
<<<EOD
<html>
	<head>
		<style>
			.error-message {
				font-family: arial, verdana, helvetica, sans-serif;
				border-width: 1px;
				border-style: solid;
				left: 50%;
				margin: 10px auto;
				min-height: 30px;
				padding: 5px;
				right: 50%;
				width: 500px;
				background-color: #FFD1D1;
				border-color: #FF0000;
				color: #000;
			}
		</style>
	<body>
		<div class="error-message">
			$lang_not_exportable
		</div>
	</body>
</html>
EOD;
		if(!is_dir($garbage_path.$temp_dir_short.'/document'))
		{
			@mkdir($garbage_path.$temp_dir_short.'/document');
		}
		file_put_contents($garbage_path.$temp_dir_short.'/document/non_exportable.html', $file_content);
		
		//Add the extra files that go along with a SCORM package
		$main_code_path = api_get_path(SYS_CODE_PATH).'newscorm/packaging/';
		$extra_files = scandir($main_code_path);
		foreach($extra_files as $extra_file)
		{
			if(strpos($extra_file,'.')===0) continue;
			else
			{
				$dest_file = $garbage_path.$temp_dir_short.'/'.$extra_file;
				$this->create_path($dest_file);
				copy($main_code_path.$extra_file,$dest_file);				
			}
		}
		
	 	//Finalize the imsmanifest structure, add to the zip, then return the zip
	 	
	 	$xmldoc->save($garbage_path.'/'.$temp_dir_short.'/imsmanifest.xml');
		
		
		$zip_folder->add($garbage_path.'/'.$temp_dir_short, PCLZIP_OPT_REMOVE_PATH, $garbage_path.'/'.$temp_dir_short.'/');

		//clean possible temporary files
		foreach($files_cleanup as $file)
		{
			$res = unlink($file);
			if($res === false){error_log('Could not delete temp file '.$file.' '.__FILE__.' '.__LINE__,0);}
		}
		//Send file to client
		//$name = 'scorm_export_'.$this->lp_id.'.zip';
		require_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
		$name = preg_replace('([^a-zA-Z0-9_\.])','-',html_entity_decode($this->get_name(),ENT_QUOTES)).'.zip';
		DocumentManager::file_send_for_download($temp_zip_file,true,$name);
	}
	/**
	 * Temp function to be moved in main_api or the best place around for this. Creates a file path
	 * if it doesn't exist
	 */
	function create_path($path){
		$path_bits = split('/',dirname($path));

		// IS_WINDOWS_OS has been defined in main_api.lib.php
		$path_built = IS_WINDOWS_OS ? '' : '/';

		foreach($path_bits as $bit){
			if(!empty($bit)){
				$new_path = $path_built.$bit;
				if(is_dir($new_path)){
					$path_built = $new_path.'/';
				}
				else
				{
					mkdir($new_path);
					$path_built = $new_path.'/';
				}
			}
		}
	}
	/**
	 * Delete the image relative to this learning path. No parameter. Only works on instanciated object.
	 * @return	boolean	The results of the unlink function, or false if there was no image to start with
	 */
	function delete_lp_image()
	{
		$img = $this->get_preview_image();
		if ($img!='')
		{
			$del_file = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/upload/learning_path/images/'.$img;
			$this->set_preview_image('');
			return @unlink($del_file);	
		}
		else
		{
			return false;
		}
		
	}
	
	/**
	 * Uploads an author image to the upload/learning_path/images directory
	 * @param	array	The image array, coming from the $_FILES superglobal
	 * @return	boolean	True on success, false on error
	 */
	function upload_image($image_array)
	{		
		$image_moved=false;	
		if(!empty($image_array['name']))
		{
			$upload_ok = process_uploaded_file($image_array);
			$has_attachment=true;
		}
		else
		{
			$image_moved=true;
		}
			
		if($upload_ok)
		{			
			if ($has_attachment)
			{		
				$courseDir   = api_get_course_path().'/upload/learning_path/images'; 
				$sys_course_path = api_get_path(SYS_COURSE_PATH);		
				$updir = $sys_course_path.$courseDir;							
				// Try to add an extension to the file if it hasn't one
				$new_file_name = add_ext_on_mime(stripslashes($image_array['name']), $image_array['type']);	
			
						
				if (!filter_extension($new_file_name)) 
				{
					//Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
					$image_moved=false;							
				}
				else
				{				
					$file_extension = explode('.', $image_array['name']);
					$file_extension = strtolower($file_extension[sizeof($file_extension) - 1]);					
					$new_file_name = uniqid('').'.'.$file_extension;						
					$new_path=$updir.'/'.$new_file_name;
					
					//$result= @move_uploaded_file($image_array['tmp_name'], $new_path);
						
					// resize the image
					include_once (api_get_path(LIBRARY_PATH).'image.lib.php');		
					$temp = new image($image_array['tmp_name']);
					$picture_infos=getimagesize($image_array['tmp_name']); // $picture_infos[0]-> width
					if ($picture_infos[0]>240)
						$thumbwidth=240;
					else
						$thumbwidth=$picture_infos[0];
						
					if ($picture_infos[1]>100)
						$new_height=100;
					else
						$new_height = $picture_infos[1];
					
						
					//$new_height = round(($thumbwidth/$picture_infos[0])*$picture_infos[1]);
					
					$temp->resize($thumbwidth,$new_height,0);
					$type=$picture_infos[2];
					$result=false;
					
				    switch ($type) 
				    {
				            case 2 : 
				            	$result=$temp->send_image('JPG',$new_path);
				            break;
				            case 3 : 
				            	$result=$temp->send_image('PNG',$new_path);
				            break;
				            case 1 : 
				            	$result=$temp->send_image('GIF',$new_path);
				            break;
				    }
				    						
					// Storing the image filename
					if ($result)
					{	
						$image_moved=true;	
						$this->set_preview_image($new_file_name);
						return true;		
					}	
						
				}			 
			}			
		}
		return false;
	}	
}

if (!function_exists('trim_value')) {
	function trim_value(&$value) {
		$value = trim($value);
    }
}