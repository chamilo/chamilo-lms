<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
 * @package dokeos.glossary
 * @author Christian Fasanando
 * This library enables maintenance of the glossary tool
 */
/**
* This function retrieves glossary details by course
* @return	array Array of type ([glossary_id=>w,name=>x,description=>y],[])
* @author Christian Fasanando <christian.fasanando@dokeos.com>, 
* @version november 2008, dokeos 1.8.6
*/

function get_glossary_details() {
	$t_glossary = Database :: get_course_table(TABLE_GLOSSARY);
	$sql = "SELECT glossary_id, name, description
			  FROM $t_glossary";

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
	$t_glossary = Database :: get_course_table(TABLE_GLOSSARY);
    if (empty($name) || empty($description)) {
        return false;
    }
	$safe_name = Database::escape_string($name);
	$safe_description = Database::escape_string($description);
	
	$sql = "INSERT INTO $t_glossary (name, description) VALUES('$safe_name', '$safe_description')";
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
	$t_glossary 			= Database :: get_course_table(TABLE_GLOSSARY);

    if (empty($glossary_id) || empty($name) || empty($description)) { return false; }

	$safe_name 			= Database::escape_string($name);
	$safe_description 	= Database::escape_string($description);
	$safe_glossary_id 	= Database::escape_string($glossary_id);

	$sql = "UPDATE $t_glossary SET name='$safe_name', description='$safe_description' WHERE glossary_id=$safe_glossary_id";
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
	$t_glossary 			= Database :: get_course_table(TABLE_GLOSSARY);	
	$safe_glossary_id 	= Database::escape_string($glossary_id);
	if (empty($glossary_id)) { return false; }

	$sql = "DELETE FROM $t_glossary WHERE glossary_id=$safe_glossary_id";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	return $result;
}