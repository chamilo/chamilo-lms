<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
 * @package dokeos.notebook
 * @author Christian Fasanando
 * This library enables maintenance of the notebook tool
 */
 
 
 
/**
* This function retrieves notebook details by users
* @return	array Array of type ([notebook_id=>a,user_id=>b,course=>c,session_id=>d,description=>e,start_date=>f,end_date=>g,status=>h],[])
* @author Christian Fasanando <christian.fasanando@dokeos.com>, 
* @version octubre 2008, dokeos 1.8.6
*/
function get_notebook_details($user_id) {
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);
	$safe_user_id = Database::escape_string($user_id);
	$sql = "SELECT note.notebook_id,note.user_id,note.course,note.session_id,
			  note.description,DATE_FORMAT(note.start_date,'%d/%m/%Y %H:%i:%s') as start_date,DATE_FORMAT(note.end_date,'%d/%m/%Y %H:%i:%s') as end_date,note.status
			  FROM $t_notebook note where note.user_id='$safe_user_id' ORDER BY note.start_date";

	$result = api_sql_query($sql, __FILE__, __LINE__);
	return $result;
} 

/**
* This function add notebook details by users
* @param user_id type int
* @param course type String
* @param session_id type int
* @param description type String
* @param start_date type Date
* @return	boolean
* @author Christian Fasanando <christian.fasanando@dokeos.com>, 
* @version octubre 2008, dokeos 1.8
*/
function add_notebook_details($user_id,$course,$session_id,$description,$start_date) {
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);
	$safe_user_id = (int)$user_id;
	$safe_course = Database::escape_string($course);
	$safe_session_id = (int)$session_id;
	$safe_description = Database::escape_string($description);
	
	if (empty($description) || empty($start_date)) {
		return false;
	}
		
	$sql = "INSERT INTO $t_notebook(user_id,course,session_id,description,start_date,status)  
			VALUES('$safe_user_id' , '$safe_course','$safe_session_id','$safe_description','$start_date',0)";

	$result = api_sql_query($sql, __FILE__, __LINE__);
	return $result;
} 

/**
* This function modify notebook details by users
* @param notebook_id type int
* @param user_id type int
* @param course type String
* @param session_id type int
* @param description type String
* @param end_date type Date
* @return	boolean
* @author Christian Fasanando <christian.fasanando@dokeos.com>, 
* @version octubre 2008, dokeos 1.8
*/
function edit_notebook_details($notebook_id,$user_id,$course,$session_id,$description,$end_date) {
	
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);
	$safe_notebook_id = (int)$notebook_id;
	$safe_user_id = (int)$user_id;
	$safe_course = Database::escape_string($course);
	$safe_session_id = (int)$session_id;
	$safe_description = Database::escape_string($description);
	
	if (empty($description) || empty($end_date)) {
		return false;
	}
	
	$sql = "UPDATE $t_notebook SET user_id='$safe_user_id' , course='$safe_course',session_id='$safe_session_id',description='$safe_description',end_date='$end_date',status='1' WHERE notebook_id='$notebook_id'";

	$result = api_sql_query($sql, __FILE__, __LINE__);
	return $result;
}

/**
* This function delete notebook details by users
* @param notebook_id type int 
* @return	boolean
* @author Christian Fasanando <christian.fasanando@dokeos.com>, 
* @version octubre 2008, dokeos 1.8
*/
function delete_notebook_details($notebook_id) {
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);
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
				if (confirm(\" ". get_lang("AreYouSureToDeleteThis") ." \"+ name + \" ?\"))
					{return true;}
				else
					{return false;}
			}
					
			function add_notebook() {
				msg_error='".get_lang("YouMustWriteANote")."';	
				msg='<<".get_lang("WriteHereYourNote").">>';		
				if(document.frm_add_notebook.description.value=='' || document.frm_add_notebook.description.value==msg) {
					document.getElementById('msg_add_error').style.display='block';	
					document.getElementById('msg_add_error').innerHTML=msg_error;
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
				msg_error='".get_lang("YouMustWriteANote")."';			
				if(document.frm_edit_notebook.upd_description.value=='') {
					document.getElementById('msg_edit_error').style.display='block';	
					document.getElementById('msg_edit_error').innerHTML=msg_error;
				} else {
					document.frm_edit_notebook.submit();
				}
			}									
			</script>";	
}  