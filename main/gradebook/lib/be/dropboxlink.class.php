<?php
/* For licensing terms, see /license.txt */
/**
 * Gradebook link to dropbox item
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
class DropboxLink extends EvalLink
{

// INTERNAL VARIABLES

    private $dropbox_table = null;

// CONSTRUCTORS

    function DropboxLink() {
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
				.' WHERE uploader_id = '.intval($stud_id)
				." AND title = '".Database::escape_string($eval->get_name())."'";

		$result = Database::query($sql);
		if ($fileurl = Database::fetch_row($result)) {
	    	$course_info = Database :: get_course_info($this->get_course_code());
			//$url = api_get_path(WEB_PATH).'main/gradebook/open_document.php?file='.$course_info['directory'].'/'.$fileurl[0];
			//return $url;
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
    	if (!isset($this->dropbox_table)) {
	    	$course_info = Database :: get_course_info($this->get_course_code());
			$database_name = $course_info['db_name'];
			$this->dropbox_table = Database :: get_course_table(TABLE_DROPBOX_FILE, $database_name);
    	}
   		return $this->dropbox_table;
    }

    public function get_icon_name() {
		return 'dropbox';
	}

}