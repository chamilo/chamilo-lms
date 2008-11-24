<?php
/*
 * Created on 19/11/2008
 * @author Christian Fasanando
 * 
 * This class allows the maintenance of the tool glossary
 */
 
/**
* This function retrieves glossary details by course
* @return	array Array of type ([glossary_id=>w,name=>x,description=>y],[])
* @author Christian Fasanando <christian.fasanando@dokeos.com>, 
* @version november 2008, dokeos 1.8.6
*/

function get_glossary_details() {
	$t_glosary = Database :: get_course_table(TABLE_GLOSSARY);
	$sql = "SELECT glossary_id,name,description
			  FROM $t_glosary";

	$result = api_sql_query($sql, __FILE__, __LINE__);
	return $result;
} 

/**
* This function add glosary details by course
* @param name type String
* @param description type String
* @return	boolean
* @author Christian Fasanando <christian.fasanando@dokeos.com>, 
* @version november 2008, dokeos 1.8.6
*/
function add_glossary_details($name,$description) {
	$t_glosary = Database :: get_course_table(TABLE_GLOSSARY);
	$safe_name = Database::escape_string($name);
	$safe_description = Database::escape_string($description);
	
	if (empty($name) || empty($description)) {
		return false;
	}
	
	$sql = "INSERT INTO $t_glosary(name,description) VALUES('$safe_name' , '$safe_description')";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	return $result;
} 

/**
* This function edit glosary details by course
* @param glossary_id int
* @param name type String
* @param description type String
* @return	boolean
* @author Christian Fasanando <christian.fasanando@dokeos.com>, 
* @version november 2008, dokeos 1.8.6
*/
function edit_glossary_details($glossary_id,$name,$description) {
	$t_glosary 			= Database :: get_course_table(TABLE_GLOSSARY);
	$safe_name 			= Database::escape_string($name);
	$safe_description 	= Database::escape_string($description);
	$safe_glossary_id 	= Database::escape_string($glossary_id);
	
	if (empty($name) || empty($description))return false;
	
	$sql = "UPDATE $t_glosary SET name='$safe_name' , description='$safe_description' WHERE glossary_id=$safe_glossary_id";

	$result = api_sql_query($sql, __FILE__, __LINE__);
	return $result;
}

/**
* This function delete glosary details by course
* @param glossary_id int
* @return	boolean
* @author Christian Fasanando <christian.fasanando@dokeos.com>, 
* @version november 2008, dokeos 1.8.6
*/

function delete_glossary_details($glossary_id) {
	$t_glosary 			= Database :: get_course_table(TABLE_GLOSSARY);	
	$safe_glossary_id 	= Database::escape_string($glossary_id);
			
	$sql = "DELETE FROM $t_glosary  WHERE glossary_id=$safe_glossary_id";

	$result = api_sql_query($sql, __FILE__, __LINE__);
	return $result;
}  