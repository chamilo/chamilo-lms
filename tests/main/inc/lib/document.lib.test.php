<?php
require_once(api_get_path(LIBRARY_PATH).'document.lib.php');

class TestDocumentManager extends UnitTestCase {
	
	/**
	 * This check if a document has the readonly property checked, then see if the user
	 * is the owner of this file, if all this is true then return true.
	 * 
	 * @param array  $_course
	 * @param int    $user_id id of the current user
	 * @param string $file path stored in the database
	 * @param int    $document_id in case you dont have the file path ,insert the id of the file here and leave $file in blank ''
	 * @return boolean true/false	 
	 **/
	public function testcheck_readonly() {
		$_course='';
		$user_id='';
		$file='';
		$res=DocumentManager::check_readonly($_course,$user_id,$file);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	/**
	 * This deletes a document by changing visibility to 2, renaming it to filename_DELETED_#id
	 * Files/folders that are inside a deleted folder get visibility 2
	 *
	 * @param array $_course
	 * @param string $path, path stored in the database
	 * @param string ,$base_work_dir, path to the documents folder
	 * @return boolean true/false
	 * @todo now only files/folders in a folder get visibility 2, we should rename them too.
	 */
	function testdelete_document() {
		$_course['dbName']='';
		$path=''; 
		$base_work_dir='';
		$res=DocumentManager::delete_document($_course, $path, $base_work_dir);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	/**
	 * Removes documents from search engine database
	 *
	 * @param string $course_id Course code
	 * @param int $document_id Document id to delete
	 * @return void
	 */
	function testdelete_document_from_search_engine() {
		$course_id='';
		$document_id='';
		$res=DocumentManager::delete_document_from_search_engine($course_id, $document_id);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	*	Get the content type of a file by checking the extension
	*	We could use mime_content_type() with php-versions > 4.3,
	*	but this doesn't work as it should on Windows installations
	*
	*	@param string $filename or boolean TRUE to return complete array
	*
	*/
	function testfile_get_mime_type() {
		$filename='';
		$res=DocumentManager::file_get_mime_type($filename);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	/**
	* This function streams a file to the client
	*
	* @param string $full_file_name
	* @param boolean $forced
	* @param string $name
	* @return false if file doesn't exist, true if stream succeeded
	*/
	function testfile_send_for_download() {
		$full_file_name='';
		$res=DocumentManager::file_send_for_download();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	/**
	*   @todo ??not only check if a file is visible, but also check if the user is allowed to see the file??
	*   @return true if the user is allowed to see the document, false otherwise (bool)
	*/
	function testfile_visible_to_user() {
		$this_course='';
		$doc_url='';
		$res=DocumentManager::file_visible_to_user($this_course, $doc_url);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	/**
	* Fetches all document data for the given user/group
	*
	* @param array $_course
	* @param string $path
	* @param int $to_group_id
	* @param int $to_user_id
	* @param boolean $can_see_invisible
	* @return array with all document data 
	*/
	function testget_all_document_data() {
		$_course['dbName']='';
		$res=DocumentManager::get_all_document_data();
		$this->assertTrue(is_array($_course));
		//var_dump($_course);
	} 
	
	/**
	 * Gets the paths of all folders in a course
	 * can show all folders (exept for the deleted ones) or only visible ones
	 * @param array $_course
	 * @param boolean $can_see_invisible
	 * @param int $to_group_id
	 * @return array with paths
	 */
	function testget_all_document_folders() {
		$_course['dbName']='';
		$res=DocumentManager::get_all_document_folders($_course);
		$this->assertTrue(is_array($_course));
		//var_dump($_course);
	}
	
	/**
	* @return the document folder quuta of the current course, in bytes
	*/
	function testget_course_quota() {
		global $_course, $maxFilledSpace;
		$res=DocumentManager::get_course_quota();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	/** Gets the id of a document with a given path
	 *
	 * @param array $_course
	 * @param string $path
	 * @return int id of document / false if no doc found
	 */
	function testget_document_id() {
		$_course['dbName']='';
		$path = Database::escape_string($path);
		$res=DocumentManager::get_document_id($_course, $path);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	/** This check if a document is a folder or not	 
	 * @param array  $_course
	 * @param int    $document_id of the item
	 * @return boolean true/false	 
	 **/
	function testis_folder() {
		$_course['dbName']='';
		$document_id = Database::escape_string($document_id);
		$res=DocumentManager::is_folder($_course, $document_id);
		$this->assertTrue(is_bool($res));
	}
	
	/**
	 * return true if the documentpath have visibility=1 as item_property
	 *
	 * @param string $document_path the relative complete path of the document
     * @param array  $course the _course array info of the document's course
	 */
	function testis_visible() {
		$course['dbName']='';
		$doc_path = Database::escape_string($doc_path);
		$res=DocumentManager::is_visible($doc_path, $course);
		$this->assertTrue(is_bool($res));
	}
	
	/**
	 * Allow to set a specific document as a new template for FCKEditor for a particular user in a particular course
	 *
	 * @param string $title
	 * @param string $description
	 * @param int $document_id_for_template the document id
	 * @param string $couse_code
	 * @param int $user_id
	 */
	function testset_document_as_template() {
		$title='';
		$description='';
		$document_id_for_template='';
		$couse_code='';
		$user_id=''; 
		$image='';
		$res=DocumentManager::set_document_as_template($title, $description, $document_id_for_template, $couse_code, $user_id, $image);
		$this->assertTrue(is_bool($res));
	}
	
	function teststring_send_for_download() {
		$full_string='';
		$res=DocumentManager::string_send_for_download($full_string);
		$this->assertTrue(is_bool($res));
	}
	
	/**
	 * Unset a document as template
	 *
	 * @param int $document_id
	 * @param string $couse_code
	 * @param int $user_id
	 * @return void null
	 */
	function testunset_document_as_template() {
		$document_id=Database::escape_string($document_id);
		$course_code=Database::escape_string($course_code);
		$user_id=Database::escape_string($user_id);
		$res=DocumentManager::unset_document_as_template($document_id, $course_code, $user_id);
		$this->assertTrue(is_null($res));
		var_dump($res);
	}
}
?>
