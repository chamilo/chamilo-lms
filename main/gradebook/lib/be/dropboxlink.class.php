<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos Latinoamerica SAC
	Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
 * Gradebook link to dropbox item
 * @author Bert Steppé
 * @package dokeos.gradebook
 */
class DropboxLink extends EvalLink
{

// INTERNAL VARIABLES

    private $dropbox_table = null;

// CONSTRUCTORS

    function DropboxLink() {
    	$this->set_type(LINK_DROPBOX);
    }


// FUNCTIONS IMPLEMENTING ABSTRACTLINK

	public function get_view_url ($stud_id) {
		// find a file uploaded by the given student,
		// with the same title as the evaluation name

    	$eval = $this->get_evaluation();

		$sql = 'SELECT filename'
				.' FROM '.$this->get_dropbox_table()
				.' WHERE uploader_id = '.$stud_id
				." AND title = '".Database::escape_string($eval->get_name())."'";

		$result = Database::query($sql, __FILE__, __LINE__);
		if ($fileurl = Database::fetch_row($result)) {
	    	$course_info = Database :: get_course_info($this->get_course_code());

			$url = api_get_path(WEB_PATH)
					.'main/gradebook/open_document.php?file='
					.$course_info['directory']
					.'/'
					.$fileurl[0];

			return $url;
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
}