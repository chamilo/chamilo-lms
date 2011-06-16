<?php
//include_once(api_get_path(LIBRARY_PATH)."/pclzip/pclzip.lib.php");
require_once(api_get_path(LIBRARY_PATH).'document.lib.php');
class TestDropboxFunctions extends UnitTestCase {

	/**
	* This function is a wrapper function for the multiple actions feature.
	* @return	Mixed	If there is a problem, return a string message, otherwise nothing
	*/

	function testhandle_multiple_actions() {
		global $_user, $is_courseAdmin, $is_courseTutor;
		$res= handle_multiple_actions();
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}


	/**
	* Displays the form to move one individual file to a category
	* @return html code of the form that appears in a dokeos message box.
	*/

	function testdisplay_move_form() {
		ob_start();
		$id= 1;
		$part = 'test';
		$res= display_move_form($part, $id, $target=array());
		ob_end_clean();
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* This functions displays all teh possible actions that can be performed on multiple files. This is the dropdown list that
	* appears below the sortable table of the sent / or received files.
	* @return html value for the dropdown list
	*/

	function testdisplay_action_options() {
		ob_start();
		$categories= 1;
		$part = 'test';
		$res= display_action_options($part, $categories, $current_category=0);
		ob_end_clean();
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* this function returns the html code that displays the checkboxes next to the files so that
	* multiple actions on one file are possible.
	* @param $id the unique id of the file
	* @param $part are we dealing with a sent or with a received file?
	* @return html code
	*/

	function testdisplay_file_checkbox() {
		$id= 1;
		$part = 'test';
		$res= display_file_checkbox($id, $part);
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* This function displays the form to add a new category.
	*
	* @param $category_name this parameter is the name of the category (used when no section is selected)
	* @param $id this is the id of the category we are editing.
	*
	* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	* @version march 2006
	*/

	function testdisplay_addcategory_form() {
		global $dropbox_cnf;
		ob_start();
		$action= 'test';
		$res= display_addcategory_form($category_name='', $id='',$action);
		ob_end_clean();
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* this function displays the form to upload a new item to the dropbox.
	*
	* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	* @version march 2006
	*/

	function testDisplay_add_form() {
		global $_user, $is_courseAdmin, $is_courseTutor, $course_info, $origin, $dropbox_unid;
		ob_start();
		$res= display_add_form();
		ob_end_clean();
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* This function displays the firstname and lastname of the user as a link to the user tool.
	* @see this is the same function as in the new forum, so this probably has to move to a user library.
	* @todo move this function to the user library
	*/

	function testdisplayuserlink() {
		global $_otherusers;
		$user_id = 1;
		$res= display_user_link($user_id, $name='');
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* returns username or false if user isn't registered anymore
	* @todo check if this function is still necessary. There might be a library function for this.
	*/

	function testGetUserNameFromId() {
		global $dropbox_cnf;
		$id = 1;
		$res= getUserNameFromId($id);
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* returns loginname or false if user isn't registered anymore
	* @todo check if this function is still necessary. There might be a library function for this.
	*/

	function testGetLoginFromId() {
		$id = 1;
		$res= getLoginFromId($id);
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}


	/**
	* @desc This function retrieves the number of feedback messages on every document. This function might become obsolete when
	* 		the feedback becomes user individual.
	*/

	function testget_total_number_feedback() {
		global $dropbox_cnf;
		$res= get_total_number_feedback($file_id='');
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* This function retrieves all the dropbox categories and returns them as an array
	* @param $filter default '', when we need only the categories of the sent or the received part.
	* @return array
	*/

	function testGetdropbox_categories() {
		$res= get_dropbox_categories($filter='');
		if(!is_null($res)){
		$this->assertTrue(is_array($res));
		}
        //var_dump($res);
	}

	/**
	* Mailing zip-file is posted to (dest_user_id = ) mailing pseudo_id
	* and is only visible to its uploader (user_id).
	* Mailing content files have uploader_id == mailing pseudo_id, a normal recipient,
	* and are visible initially to recipient and pseudo_id.
	* @todo check if this function is still necessary.
	*/

	function testgetUserOwningThisMailing() {
		global $dropbox_cnf;
		$mailingPseudoId = '1';
		$res= getUserOwningThisMailing($mailingPseudoId, $owner = 0, $or_die = '');
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	 * Get the last access to a given tool of a given user
	 * @param $tool string the tool constant
	 * @param $course_code the course_id
	 * @param $user_id the id of the user
	 * @return string last tool access date
	 * @todo consider moving this function to a more appropriate place.
	 */

	function testget_last_tool_access() {
		global $_course, $_user;
		$tool = '1';
		$res= get_last_tool_access($tool, $course_code='', $user_id='');
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* This functions stores a new dropboxcategory
	* @var 	it might not seem very elegant if you create a category in sent and in received with the same name that you get two entries in the
	*		dropbox_category table but it is the easiest solution. You get
	*		cat_name | received | sent | user_id
	*		test	 |	  1		|	0  |	237
	*		test	 |	  0		|	1  |	237
	*		more elegant would be
	*		test	 |	  1		|	1  |	237
	*/

	function teststoreaddcategory() {
		global $_user,$dropbox_cnf;
		$res= store_addcategory();
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* This function moves a file to a different category
	* @param $id the id of the file we are moving
	* @param $target the id of the folder we are moving to
	* @param $part are we moving a received file or a sent file?
	* @return language string
	*/

	function testStoremove() {
		$id= 1;
		$part = 'test';
		$target = array();
		$res= store_move($id, $target, $part);
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	*@return selected string
	*/

	function teststoreadddropbox() {
		global $dropbox_cnf;
		global $_user;
		global $_course;
		$res= store_add_dropbox();
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* @return boolean indicating if user with user_id=$user_id is a course member
	* @todo eliminate global
	* @todo check if this function is still necessary. There might be a library function for this.
	*/

	function testIsCourseMember() {
		$user_id = 1;
		$res= isCourseMember($user_id);
		if(!is_null($res)){
		$this->assertTrue(is_bool($res));
		}
        //var_dump($res);
	}

	/**
	* this function transforms the array containing all the feedback into something visually attractive.
	* @param an array containing all the feedback about the given message.
	*/

	function testfeedback() {
		$array = array();
		$res= feedback($array);
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* This function returns the html code to display the feedback messages on a given dropbox file
	* @param $feedback_array an array that contains all the feedback messages about the given document.
	* @return html code
	* @todo add the form for adding new comment (if the other party has not deleted it yet).
	* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	* @version march 2006
	*/

	function testformat_feedback() {
		$feedback = array();
		$res= format_feedback($feedback);
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* this function returns the code for the form for adding a new feedback message to a dropbox file.
	* @return html code
	*/

	function testfeedback_form() {
		global $dropbox_cnf;
		$res= feedback_form();
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* @return a language string (depending on the success or failure.
	*/

	function teststore_feedback() {
		global $dropbox_cnf;
		global $_user;
		$res= store_feedback();
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* @desc This function checks if the real filename of the dropbox files doesn't already exist in the temp folder. If this is the case then
	*		it will generate a different filename;
	*/

	function testcheck_file_name() {
		global $_course;
		$file_name_2_check = 'test';
		$res= check_file_name($file_name_2_check, $counter=0);
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* @desc this function checks if the key exists. If this is the case it returns the value, if not it returns 0
	*/

	function testcheck_number_feedback() {
		$key = 'test';
		$array = array();
		$res= check_number_feedback($key, $array);
		if(!is_null($res)){
		$this->assertTrue(is_numeric($res));
		}
        //var_dump($res);
	}

	/**
	* @desc generates the contents of a html file that gives an overview of all the files in the zip file.
	*		This is to know the information of the files that are inside the zip file (who send it, the comment, ...)
	*/

	function testgenerate_html_overview() {
		$files = array();
		$res= generate_html_overview($files, $dont_show_columns=array(), $make_link=array());
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* This function downloads all the files of the inputarray into one zip
	* @param $array an array containing all the ids of the files that have to be downloaded.
	* @todo consider removing the check if the user has received or sent this file (zip download of a folder already sufficiently checks for this).
	* @todo integrate some cleanup function that removes zip files that are older than 2 days
	*/
	/*
	function testzip_download() {
		global $_course;
		global $dropbox_cnf;
		global $_user;
		global $files;
		$array = array();
		$res= zip_download($array);
		if(!is_string($res)){
		$this->assertTrue(is_null($res));
		}
        //var_dump($res);
	}
	*/

	/**
	* Function that finds a given config setting
	*/

	function testdropbox_cnf() {
		$variable = 'test';
		$res= dropbox_cnf($variable);
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	/**
	* This is a callback function to decrypt the files in the zip file to their normal filename (as stored in the database)
	* @param $p_event a variable of PCLZip
	* @param $p_header a variable of PCLZip
	*/

	function testmy_pre_add_callback() {
		global $files;
		$p_event = 'test';
		$res= my_pre_add_callback($p_event, &$p_header);
		if(!is_null($res)){
		$this->assertTrue(is_numeric($res));
		}
        //var_dump($res);
	}

	/**
	* @desc Cleans the temp zip files that were created when users download several files or a whole folder at once.
	*		T
	* @return true
	*/

	function testcleanup_temp_dropbox() {
		global $_course;
		$res= cleanup_temp_dropbox();
		if(!is_null($res)){
		$this->assertTrue(is_bool($res));
		}
        //var_dump($res);
	}

	/**
	* This function deletes a dropbox category
	* @todo give the user the possibility what needs to be done with the files in this category: move them to the root, download them as a zip, delete them
	*/

	function testdelete_category() {
		global $_user, $is_courseAdmin, $is_courseTutor,$dropbox_cnf;
		$id= 1;
		$action = 'test';
		$res= delete_category($action, $id);
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}


		/**
	* Checks if there are files in the dropbox_file table that aren't used anymore in dropbox_person table.
	* If there are, all entries concerning the file are deleted from the db + the file is deleted from the server
	*/

	function testremoveUnusedFiles() {
		$res= removeUnusedFiles();
		if(!is_null($res)){
		$this->assertTrue(is_array($res));
		}
        //var_dump($res);
	}

	/**
	* @todo check if this function is still necessary.
	*/

	function testremoveMoreIfMailing() {
		$file_id = 1;
		$res= removeMoreIfMailing($file_id);
		if(!is_null($res)){
		$this->assertTrue(is_array($res));
		}
        //var_dump($res);
	}
}
?>