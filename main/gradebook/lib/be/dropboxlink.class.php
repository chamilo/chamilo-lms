<?php
/* For licensing terms, see /license.txt */
/**
 * Gradebook link to dropbox item
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
/**
 * Class
 * @package chamilo.gradebook
 */
class DropboxLink extends EvalLink
{

// INTERNAL VARIABLES

    private $dropbox_table = null;

// CONSTRUCTORS

    function __construct() {
    	parent::__construct();
    	$this->set_type(LINK_DROPBOX);
    }


    /**
     * 
     * Returns the URL of a document
     * This funcion is loaded when using a gradebook as a tab (gradebook = -1) see issue #2705
     */

	public function get_view_url ($stud_id) {
		// find a file uploaded by the given student,
		// with the same title as the evaluation name
		
    	$eval = $this->get_evaluation();
		$sql = 'SELECT filename FROM '.$this->get_dropbox_table()
				.' WHERE c_id = '.$this->course_id.' AND uploader_id = '.intval($stud_id)
				." AND title = '".Database::escape_string($eval->get_name())."'";

		$result = Database::query($sql);
		if ($fileurl = Database::fetch_row($result)) {
	    	$course_info = Database :: get_course_info($this->get_course_code());
	        return null;
		} else {
			return null;
		}
	}

    public function get_type_name() {
    	return get_lang('DokeosDropbox');
    }

	public function is_allowed_to_change_name() {
		return false;
	}


// INTERNAL FUNCTIONS

    /**
     * Lazy load function to get the dropbox database table
     */
    private function get_dropbox_table () {
    	$this->dropbox_table = Database :: get_course_table(TABLE_DROPBOX_FILE);
   		return $this->dropbox_table;
    }

    public function get_icon_name() {
		return 'dropbox';
	}

}
