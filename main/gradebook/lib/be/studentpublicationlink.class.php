<?php

/**
 * Gradebook link to student publication item
 * @author Bert Steppé
 * @package dokeos.gradebook
 */
class StudentPublicationLink extends EvalLink
{

// INTERNAL VARIABLES

    private $studpub_table = null;
    private $itemprop_table = null;


// CONSTRUCTORS

    function StudentPublicationLink()
    {
    	$this->set_type(LINK_STUDENTPUBLICATION);
    }


// FUNCTIONS IMPLEMENTING ABSTRACTLINK

	public function get_view_url ($stud_id)
	{
		// find a file uploaded by the given student,
		// with the same title as the evaluation name

    	$eval = $this->get_evaluation();

		$sql = 'SELECT pub.url'
				.' FROM '.$this->get_itemprop_table().' prop, '
						 .$this->get_studpub_table().' pub'
				." WHERE prop.tool = 'work'"
				.' AND prop.insert_user_id = '.$stud_id
				.' AND prop.ref = pub.id'
				." AND pub.title = '".mysql_real_escape_string($eval->get_name())."'";

		$result = api_sql_query($sql, __FILE__, __LINE__);
		if ($fileurl = mysql_fetch_row($result))
		{
	    	$course_info = Database :: get_course_info($this->get_course_code());

			$url = api_get_path(WEB_PATH)
					.'main/gradebook/open_document.php?file='
					.$course_info['directory']
					.'/'
					.$fileurl[0];

			return $url;
		}
		else
			return null;
		
	}
	
    
    public function get_type_name()
    {
    	return get_lang('DokeosStudentPublications');
    }
    

	public function is_allowed_to_change_name()
	{
		return false;
	}


    
// INTERNAL FUNCTIONS
    
    /**
     * Lazy load function to get the database table of the student publications
     */
    private function get_studpub_table ()
    {
    	if (!isset($this->studpub_table))
    	{
	    	$course_info = Database :: get_course_info($this->get_course_code());
			$database_name = $course_info['db_name'];
			$this->studpub_table = Database :: get_course_table(TABLE_STUDENT_PUBLICATION, $database_name);
    	}
   		return $this->studpub_table;
    }

    /**
     * Lazy load function to get the database table of the item properties
     */
    private function get_itemprop_table ()
    {
    	if (!isset($this->itemprop_table))
    	{
	    	$course_info = Database :: get_course_info($this->get_course_code());
			$database_name = $course_info['db_name'];
			$this->itemprop_table = Database :: get_course_table(TABLE_ITEM_PROPERTY, $database_name);
    	}
   		return $this->itemprop_table;
    }
	

}
?>