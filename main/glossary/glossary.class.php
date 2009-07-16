<?php

class GlossaryManager {

    function __construct() {
    	
    }
    /**
     * Get all glossary terms
     * @author Isaac Flores <isaac.flores@dokeos.com>
     * @return Array Contain glossary terms
     */
	public static function get_glossary_terms () {
		global $course;
		$glossary_id=array();
		$glossary_name=array();
		$glossary_desc=array();				
		$glossary_table  = Database::get_course_table(TABLE_GLOSSARY);
		$sql='SELECT glossary_id as id,name,description FROM '.$glossary_table;
		$rs=Database::query($sql,__FILE__,__LINE__);
		while ($row=Database::fetch_array($rs)) {
			$glossary_data[]=$row;						
		}
		return $glossary_data;
	}
	/**
	 * Get glossary term by glossary id
	 * @author Isaac Flores <florespaz@bidsoftperu.com>
	 * @param Integer The glossary id
	 * @return String The glossary description
	 */
	public static function get_glossary_term_by_glossary_id ($glossary_id) {
		global $course;
		$glossary_table  = Database::get_course_table(TABLE_GLOSSARY);
		$sql='SELECT description FROM '.$glossary_table.' WHERE glossary_id="'.Database::escape_string($glossary_id).'"';
		$rs=Database::query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($rs);
		return $row['description'];		
	}
	/**
	 * Get glossary term by glossary id
	 * @author Isaac Flores <florespaz_isaac@hotmail.com>
	 * @param String The glossary term name
	 * @return String The glossary description
	 */	
	public static function get_glossary_term_by_glossary_name ($glossary_name) {
		global $course;
		$glossary_table  = Database::get_course_table(TABLE_GLOSSARY);
		$sql='SELECT description FROM '.$glossary_table.' WHERE name like trim("'.Database::escape_string($glossary_name).'") ';
		$rs=Database::query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($rs);
		return $row['description'];		
	} 
}
?>