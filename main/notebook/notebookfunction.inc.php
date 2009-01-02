<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
 * @package dokeos.notebook
 * @author Christian Fasanando
 * This library enables maintenance of the notebook tool
 */
 
/**
* This function retrieves notebook details by course 
* and order by  a type (1 = By Creation Date, 2 = By Update Date, 3 = By Title)
* @param int $user_id - User ID
* @param string course - Course ID
* @return	array Array of type ([notebook_id=>a,user_id=>b,course=>c,session_id=>d,description=>e,start_date=>f,end_date=>g,status=>h],[])
* @author Christian Fasanando <christian.fasanando@dokeos.com>, 
* @version octubre 2008, dokeos 1.8.6
*/
function get_notebook_details($user_id,$course,$type) {
	
	if ($user_id != strval(intval($user_id))) { return false; }	
	if (!empty($type) && $type != strval(intval($type))) { return false; }	
	$safe_course = Database::escape_string($course);
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);
	
	if ($type==3) {
		$sql = "SELECT note.notebook_id,note.user_id,note.course,note.session_id,
			  note.title,note.description,DATE_FORMAT(note.creation_date,'%d/%m/%Y %H:%i:%s') as creation_date,DATE_FORMAT(note.update_date,'%d/%m/%Y %H:%i:%s') as update_date,note.status
			  FROM $t_notebook note where note.user_id='$user_id' AND note.course='$safe_course' ORDER BY note.title";		
	} elseif($type==2) {
		$sql = "SELECT note.notebook_id,note.user_id,note.course,note.session_id,
			  note.title,note.description,DATE_FORMAT(note.creation_date,'%d/%m/%Y %H:%i:%s') as creation_date,DATE_FORMAT(note.update_date,'%d/%m/%Y %H:%i:%s') as update_date,note.status
			  FROM $t_notebook note where note.user_id='$user_id' AND note.course='$safe_course' ORDER BY note.update_date DESC";			   
	}  else {
		$sql = "SELECT note.notebook_id,note.user_id,note.course,note.session_id,
			  note.title,note.description,DATE_FORMAT(note.creation_date,'%d/%m/%Y %H:%i:%s') as creation_date,DATE_FORMAT(note.update_date,'%d/%m/%Y %H:%i:%s') as update_date,note.status
			  FROM $t_notebook note where note.user_id='$user_id' AND note.course='$safe_course' ORDER BY note.creation_date DESC";
	}
		
	$result = api_sql_query($sql, __FILE__, __LINE__);
	return $result;
} 


/**
* This function retrieves notebook details by title into a course
* @param int $user_id - User ID
* @param string course - Course ID
* @param string title - title you want to search for
* @return	array Array of type ([notebook_id=>a,user_id=>b,course=>c,session_id=>d,description=>e,start_date=>f,end_date=>g,status=>h],[])
* @author Christian Fasanando <christian.fasanando@dokeos.com>, 
* @version octubre 2008, dokeos 1.8.6
*/
function get_notebook_details_by_title($user_id,$course,$title='') {
	
	if ($user_id != strval(intval($user_id))) { return false; }			
	$safe_course = Database::escape_string($course);
	$safe_title = Database::escape_string($title);
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);
		
	$sql = "SELECT note.notebook_id,note.user_id,note.course,note.session_id,
			 note.title,note.description,DATE_FORMAT(note.creation_date,'%d/%m/%Y %H:%i:%s') as creation_date,DATE_FORMAT(note.update_date,'%d/%m/%Y %H:%i:%s') as update_date,note.status
			 FROM $t_notebook note where note.user_id='$user_id' AND note.course='$safe_course' AND title like '$title%' ORDER BY note.creation_date DESC";
			
	$result = api_sql_query($sql, __FILE__, __LINE__);
	return $result;
} 

/**
* This function add notebook details by course
* @param int $user_id - User ID
* @param string $course - Course ID
* @param int $session_id - Session ID
* @param  string $title - A title about the note
* @param string $description - A description about the note
* @return	boolean
* @author Christian Fasanando <christian.fasanando@dokeos.com>, 
* @version octubre 2008, dokeos 1.8
*/
function add_notebook_details($user_id,$course,$session_id=0,$title='',$description='') {
	if (empty($description)) {
		return false;
	}
	if ($user_id != strval(intval($user_id))) { return false; }
    if (!empty($session_id) && $session_id != strval(intval($session_id))) { return false; }
	$safe_course = Database::escape_string($course);	
	$safe_title = Database::escape_string($title);
	$safe_description = Database::escape_string($description);
	$date = date('Y-m-d H:i:s');

	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);
    $sql = "INSERT INTO $t_notebook(user_id,course,session_id,title,description,creation_date,status)  
			VALUES('$user_id' , '$safe_course','$session_id','$safe_title','$safe_description','$date',0)";

	$result = api_sql_query($sql, __FILE__, __LINE__);
	return $result;
} 

/**
* This function modify notebook details by course
* @param int $notebook_id - Notebook ID
* @param int $user_id - User ID
* @param string $course - Course ID
* @param int $session_id - Session ID
* @param  string $title - A title about the note
* @param string $description - A description about the note
* @return	boolean
* @author Christian Fasanando <christian.fasanando@dokeos.com>, 
* @version octubre 2008, dokeos 1.8
*/
function edit_notebook_details($notebook_id,$user_id,$course,$session_id=0,$title='',$description='') {
	
	if (empty($description) || empty($title)) {
		return false;
	}
	if ($notebook_id != strval(intval($notebook_id))) { return false;}
    if ($user_id != strval(intval($user_id))) { return false; }
    if (!empty($session_id) && $session_id != strval(intval($session_id))) { return false; }
	$safe_notebook_id = (int)$notebook_id;
	$safe_course = Database::escape_string($course);
	$safe_title = Database::escape_string($title);
	$safe_description = Database::escape_string($description);
    $date = date('Y-m-d H:i:s');
	
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);
    $sql = "UPDATE $t_notebook SET user_id='$user_id' , course='$safe_course',session_id='$session_id',title='$safe_title',description='$safe_description',update_date='$date',status='1' WHERE notebook_id='$notebook_id'";

	$result = api_sql_query($sql, __FILE__, __LINE__);
	return $result;
}

/**
* This function delete notebook details by users
* @param int $notebook_id - Notebook ID 
* @return	boolean
* @author Christian Fasanando <christian.fasanando@dokeos.com>, 
* @version octubre 2008, dokeos 1.8
*/
function delete_notebook_details($notebook_id) {
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);
    if ($notebook_id != strval(intval($notebook_id))) { return false;}
	$safe_notebook_id = (int)$notebook_id;
			
	$sql = "DELETE FROM $t_notebook  WHERE notebook_id=$safe_notebook_id";

	$result = api_sql_query($sql, __FILE__, __LINE__);
	return $result;
}

/**
* returns all the javascript that is required in notebook/index.php
* this goes into the $htmlHeadXtra[] array
*/
function to_javascript_notebook() {
	return "<script type=\"text/javascript\">
			function confirmation (name)
			{
				if (confirm(\" ". get_lang('AreYouSureToDelete') ." \"+ name + \" ?\"))
					{return true;}
				else
					{return false;}
			}
					
			function add_notebook() {
				msg_error_desc='".get_lang('YouMustWriteANote')."';
				msg_error_title='".get_lang('YouMustWriteATitle')."';			
				msg_title='<<".get_lang('WriteTheTitleHere').">>';		
				msg_description='<<".get_lang('WriteYourNoteHere').">>';		
				if(document.frm_add_notebook.title.value=='' || document.frm_add_notebook.title.value==msg_title) {
					document.getElementById('msg_add_error').style.display='block';	
					document.getElementById('msg_add_error').innerHTML=msg_error_title;
				}else if(document.frm_add_notebook.description.value=='' || document.frm_add_notebook.description.value==msg_description) {
					document.getElementById('msg_add_error').style.display='block';	
					document.getElementById('msg_add_error').innerHTML=msg_error_desc;
				} else {
					document.frm_add_notebook.submit();
				}
			}
					
			function edit_cancel_notebook() {
				document.frm_edit_notebook.upd_notebook_id.value = '';		
				document.frm_edit_notebook.upd_description.value = '';		
				document.frm_edit_notebook.submit();	
			}		
			
			function edit_notebook() {
				msg_error_desc='".get_lang('YouMustWriteANote')."';
				msg_error_title='".get_lang('YouMustWriteATitle')."';
				if(document.frm_edit_notebook.upd_title.value=='') {
					document.getElementById('msg_edit_error').style.display='block';	
					document.getElementById('msg_edit_error').innerHTML=msg_error_title;
				}else if(document.frm_edit_notebook.upd_description.value=='') {
					document.getElementById('msg_edit_error').style.display='block';	
					document.getElementById('msg_edit_error').innerHTML=msg_error_desc;
				} else {
					document.frm_edit_notebook.submit();
				}
			}									
			</script>";	
}  