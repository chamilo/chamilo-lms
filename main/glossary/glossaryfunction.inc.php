<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
 * @package dokeos.glossary
 * @author Christian Fasanando
 * This library enables maintenance of the glossary tool
 */
 
/**
* This function retrieves glossary details by course 
* and order by  a type (1 = By Start Date, 2 = By End Date, 3 = By Term Name)
* @return	array Array of type ([glossary_id=>w,name=>x,description=>y],[])
* @author Christian Fasanando <christian.fasanando@dokeos.com>,
* @version november 2008, dokeos 1.8.6
*/

function get_glossary_details($type) {
	$t_glossary = Database :: get_course_table(TABLE_GLOSSARY);
	$t_item_propery = Database :: get_course_table(TABLE_ITEM_PROPERTY);
	$safe_type = (int)$type;
	
	if (!empty($safe_type) && $safe_type==1) {
		$sql = "SELECT g.glossary_id, g.name, g.description, g.display_order
			   FROM $t_glossary g,$t_item_propery ip WHERE g.glossary_id=ip.ref AND tool = '".TOOL_GLOSSARY."' ORDER BY ip.insert_date DESC ";
	} elseif (!empty($safe_type) && $safe_type==2) {
		$sql = "SELECT g.glossary_id, g.name, g.description, g.display_order
			   FROM $t_glossary g,$t_item_propery ip WHERE g.glossary_id=ip.ref AND tool = '".TOOL_GLOSSARY."' ORDER BY ip.lastedit_date DESC ";
	} elseif (!empty($safe_type) && $safe_type==3) {
		$sql = "SELECT g.glossary_id, g.name, g.description, g.display_order
			   FROM $t_glossary g,$t_item_propery ip WHERE g.glossary_id=ip.ref AND tool = '".TOOL_GLOSSARY."' ORDER BY g.name ASC ";
	} else {
		$sql = "SELECT g.glossary_id, g.name, g.description, g.display_order
			   FROM $t_glossary g,$t_item_propery ip WHERE g.glossary_id=ip.ref AND tool = '".TOOL_GLOSSARY."' ORDER BY g.display_order,g.name ASC ";
	}

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
	$get_max = "SELECT MAX(display_order) FROM $t_glossary";
	$res_max = api_sql_query($get_max, __FILE__, __LINE__);
	$dsp=0;
	if (Database::num_rows($res_max)<1) {
		$dsp = 1;
	} else {
		$row = Database::fetch_array($res_max);
		$dsp = $row[0]+1;
	}
	$safe_dsp = (int)$dsp;

	// check if term name exists
	$sql = "SELECT * FROM $t_glossary WHERE name = '$safe_name'";
	$result = @api_sql_query($sql,__FILE__,__LINE__);
	$count = Database::num_rows($result);
	if ($count > 0) {
		return false;
	}
		
	$sql = "INSERT INTO $t_glossary (name, description,display_order) VALUES('$safe_name', '$safe_description',$safe_dsp)";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$id = Database::get_last_insert_id();
	if ($id>0) {
		//insert into item_property
		api_item_property_update(api_get_course_info(),TOOL_GLOSSARY,$id,'GlossaryAdded',api_get_user_id());
	}
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
	$safe_glossary_id 	= (int)$glossary_id;

	$sql = "UPDATE $t_glossary SET name='$safe_name', description='$safe_description' WHERE glossary_id=$safe_glossary_id";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	//update glossary into item_property
	api_item_property_update(api_get_course_info(),TOOL_GLOSSARY,$safe_glossary_id,'GlossaryModified',api_get_user_id());

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
	$t_glossary = Database :: get_course_table(TABLE_GLOSSARY);
	$safe_glossary_id 	= Database::escape_string($glossary_id);
	if (empty($glossary_id)) { return false; }

	$sql = "DELETE FROM $t_glossary WHERE glossary_id=$safe_glossary_id";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	// update display_order 
	$sql = "SELECT * FROM $t_glossary";
	$result = api_sql_query($sql, __FILE__, __LINE__);	
	$dsp=1;
	while ($row=Database::fetch_array($result)) {
		$id = $row['glossary_id'];		
		update_display_order($dsp,$id);
		$dsp++;
	}
	//update glossary into item_property
	api_item_property_update(api_get_course_info(),TOOL_GLOSSARY,$safe_glossary_id,'GlossaryDeleted',api_get_user_id());	
	return $result;
}

/**
 * This function update glossary display order 
 * @param $n_order int
 * @param $glossary_id int
 * 
 */
function update_display_order($n_order,$glossary_id) {	
	$t_glossary = Database::get_course_table(TABLE_GLOSSARY);
	$safe_n_order = (int)$n_order;
	$safe_glossary_id = (int)$glossary_id;	
		
	if (empty($n_order) || empty($glossary_id)) { 
		return false; 
	}
		
	$sql = "UPDATE $t_glossary SET display_order = $safe_n_order WHERE glossary_id = $safe_glossary_id";	
	$result = @api_sql_query($sql,__FILE__,__LINE__);	
	return $result;	
}

/**
* Move a term up (display_order)
* @param	integer	Glossary ID
*/
function move_up($glossary_id)
{
    	$tbl_glossary = Database::get_course_table(TABLE_GLOSSARY);
    	$sql = "SELECT * FROM $tbl_glossary ORDER BY display_order";
    	$res = api_sql_query($sql, __FILE__, __LINE__);
    	if ($res === false) {
    		return false;
    	}
    	$gs = array();
    	$g_order = array();
    	$num = Database::num_rows($res);
    	//first check the order is correct, globally (might be wrong because
    	//of versions < 1.8.4)
    	if ($num > 0) {
    		$i = 1;
			while ($row = Database::fetch_array($res)) {
				if ($row['display_order'] != $i) {	//if we find a gap in the order, we need to fix it
					$need_fix = true;
					$sql_u = "UPDATE $tbl_glossary SET display_order = $i WHERE glossary_id = ".$row['glossary_id'];
					$res_u = api_sql_query($sql_u, __FILE__, __LINE__);
				}
				$row['display_order'] = $i;
				$gs[$row['glossary_id']] = $row;
				$g_order[$i] = $row['glossary_id'];
				$i++;
			}
    	}

    	if($num>1) {
    		//if there's only one element, no need to sort
    		$order = $gs[$glossary_id]['display_order'];
    		if ($order>1) {
    			//if it's the first element, no need to move up
    			$sql_u1 = "UPDATE $tbl_glossary SET display_order = $order WHERE glossary_id = ".$g_order[$order-1];
    			$res_u1 = api_sql_query($sql_u1, __FILE__, __LINE__);
    			$sql_u2 = "UPDATE $tbl_glossary SET display_order = ".($order-1)." WHERE glossary_id = ".$glossary_id;
    			$res_u2 = api_sql_query($sql_u2, __FILE__, __LINE__);
    		}
    	}
}

/**
* Move a term down (display_order)
* @param	integer	Glossary ID
*/
function move_down($glossary_id)
{
    	$tbl_glossary = Database::get_course_table(TABLE_GLOSSARY);
    	$sql = "SELECT * FROM $tbl_glossary ORDER BY display_order";
    	$res = api_sql_query($sql, __FILE__, __LINE__);
    	if ($res === false) {
    		return false;
    	}
    	$gs = array();
    	$g_order = array();
    	$num = Database::num_rows($res);
    	$max = 0;
    	//first check the order is correct, globally (might be wrong because
    	//of versions < 1.8.4)
    	if ($num>0) {
    		$i = 1;
			while ($row = Database::fetch_array($res)) {
				$max = $i;
				if ($row['display_order'] != $i) {	
				//if we find a gap in the order, we need to fix it
					$need_fix = true;
					$sql_u = "UPDATE $tbl_glossary SET display_order = $i WHERE glossary_id = ".$row['glossary_id'];
					$res_u = api_sql_query($sql_u, __FILE__, __LINE__);
				}
				$row['display_order'] = $i;
				$gs[$row['glossary_id']] = $row;
				$g_order[$i] = $row['glossary_id'];
				$i++;
			}
    	}

    	if ($num>1) {
    		//if there's only one element, no need to sort
    		$order = $gs[$glossary_id]['display_order'];
    		if($order<$max) {
    			//if it's the first element, no need to move up
    			$sql_u1 = "UPDATE $tbl_glossary SET display_order = $order WHERE glossary_id = ".$g_order[$order+1];
    			$res_u1 = api_sql_query($sql_u1, __FILE__, __LINE__);
    			$sql_u2 = "UPDATE $tbl_glossary SET display_order = ".($order+1)." WHERE glossary_id = ".$glossary_id;
    			$res_u2 = api_sql_query($sql_u2, __FILE__, __LINE__);
    		}
    	}
}