<?php
//If you want to test this file you nedd change the path in work.lib.php inside the line 19,20 for this:
//require_once (api_get_path(SYS_CODE_PATH).'document/document.inc.php');
//require_once (api_get_path(LIBRARY_PATH).'fileDisplay.lib.php');

require_once(api_get_path(SYS_CODE_PATH).'work/work.lib.php');

class TestWork extends UnitTestCase {
	
	 /**
	 * Builds the form thats enables the user to
	 * select a directory to browse/upload in
	 * This function has been copied from the document/document.inc.php library
	 *
	 * @param array $folders
	 * @param string $curdirpath
	 * @param string $group_dir
	 * @return string html form
	 */
	 
	function testbuild_work_directory_selector() {
		$folders=array();
		$curdirpath='';
		$group_dir='';
		$res=build_work_directory_selector($folders,$curdirpath,$group_dir='');
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	 /**
	 * Builds the form thats enables the user to
	 * move a document from one directory to another
	 * This function has been copied from the document/document.inc.php library
	 *
	 * @param array $folders
	 * @param string $curdirpath
	 * @param string $move_file
	 * @return string html form
	 */
	 
	function testbuild_work_move_to_selector() {
		$folders=array();
		$curdirpath='';
		$move_file='';
		$group_dir='';
		$res=build_work_move_to_selector($folders,$curdirpath,$move_file,$group_dir='');
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	function testconvert_date_to_array() {
		$date=date('d/m/Y',time());
		$group=array();
		$res=convert_date_to_array($date,$group);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	
	/**
	 * @param string e.g converts "2008-10-06 12:45:00" to timestamp
	 */
	 
	function testconvert_date_to_number() {
		$default='2008-10-06 12:45:00';
		$res=convert_date_to_number($default);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	/**
	* This function displays the number of files contained in a directory
	* @param	string the path of the directory complete e.g. /var/www/dokeos
	* @param	boolean true if we want the total quantity of files include in others child directorys , false only  files in the directory		
	* @return	array the first element is an integer with the number of files in the folder, the second element is the number of directories
	*/
	
	function testcount_dir() {
		$path_dir='/var/www';
		$recurse=0;
		ob_start();
		$res=count_dir($path_dir, $recurse);
		$this->assertTrue(is_array($res));
		ob_end_clean();
		//var_dump($res);
	}
	
	/**
	* create a group of select from a date
	* @return array list 
	*/		

	function testcreate_group_date_select() {
		$res=create_group_date_select($prefix='');
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	
	/**
	 * @param	string	Base work dir (.../work)
	 * @param 	string $desiredDirName complete path of the desired name
 	 * @return 	string actual directory name if it succeeds, boolean false 
 	 * otherwise
 	 */
 	
	function testcreate_unexisting_work_directory() {
		$base_work_dir='/var/www/dokeos/courses/ABCD/work/';
		$desired_dir_name='/var/www/merdokeos/main/work';
		$res=create_unexisting_work_directory();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	/**
	 * Delete a work-tool directory
	 * @param	string	Base "work" directory for this course as /var/www/dokeos/courses/ABCD/work/
	 * @param	string	The directory name as the bit after "work/", without trailing slash
	 * @return	integer	-1 on error
	 */
	 
	function testdel_dir() {
		$base_work_dir='/var/www/dokeos/';
		$dir='work/';
		$id=-1;
		$res=del_dir();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	/**
	 * Transform an all directory structure (only directories) in an array 
	 * @param	string path of the directory
	 * @return	array the directory structure into an array
	 */ 
	 
	function testdirectory_to_array() {
		$directory='/var/www/dokeos/';
		$res=directory_to_array($directory);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	
	/**
	 * Displays action links (for admins, authorized groups members and authorized students)
	 * @param	string	Current dir
	 * @param	integer	Whether to show tool options
	 * @param	integer	Whether to show upload form option
	 * @return	void
	 */
	
	function testdisplay_action_links() { 
		$cur_dir_path='';
		$always_show_tool_options=1;
		$always_show_upload_form=1;
		ob_start();
		$res=display_action_links($cur_dir_path, $always_show_tool_options, $always_show_upload_form);
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	} 
	
	/**
	* Displays the form where course admins can specify wether uploaded documents
	* are visible or invisible by default.
	*
	* @param $uploadvisibledisabled
	* @return html with two list radio button
	*/

	function testdisplay_default_visibility_form() {
		$uploadvisibledisabled='';
		ob_start();
		$res=display_default_visibility_form($uploadvisibledisabled);
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}
	
	/**
	* Display the list of student publications, taking into account the user status
	*
	* @param $currentCourseRepositoryWeb, the web location of the course folder
	* @param $link_target_parameter - should there be a target parameter for the links
	* @param $dateFormatLong - date format
	* @param $origin - typically empty or 'learnpath'
	*/
	
	function testdisplay_student_publications_list() {
		global $charset,$timeNoSecFormat,$dateFormatShort,$gradebook,$dateFormatLong;
		$work_dir='';
		$sub_course_dir='';
		$currentCourseRepositoryWeb='';
		$link_target_parameter='';
		$origin='learnpath';
		$add_in_where_query='';
		ob_start();
		$res=display_student_publications_list($work_dir,$sub_course_dir,$currentCourseRepositoryWeb, $link_target_parameter, $dateFormatLong, $origin,$add_in_where_query='');
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}
	
	/**
	* Displays all options for this tool.
	* These are
	* - make all files visible / invisible
	* - set the default visibility of uploaded files
	*
	* @param $uploadvisibledisabled
	* @param $origin
	* @param $base_work_dir Base working directory (up to '/work')
	* @param $cur_dir_path	Current subdirectory of 'work/'
	* @param $cur_dir_path_url Current subdirectory of 'work/', url-encoded
	*/
	
	function testdisplay_tool_options() {
		global $charset, $group_properties,$gradebook,$base_work_dir;
		$uploadvisibledisabled='';
		$origin='';
		$cur_dir_path='';
		$cur_dir_path_url='';
		ob_start();
		$res=display_tool_options($uploadvisibledisabled, $origin,$base_work_dir,$cur_dir_path,$cur_dir_path_url);
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}
	
	/**
	* This function displays the firstname and lastname of the user as a link to the user tool.
	* @see this is the same function as in the new forum, so this probably has to move to a user library.
	* @todo move this function to the user library
	*/

	function testdisplay_user_link() {
		global $_otherusers;
		$user_id=1;
		$name='';
		$res=display_user_link($user_id, $name='');
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	/**
	* create a group of select from a date
	*/	

	function testget_date_from_group() {
		$group='';
		$res=get_date_from_group($group);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	/**
	 * @param	string Path of the directory  
	 * @return	array The list of ids of all the directories in the path 
	 */
	
	function testget_parent_directories() {
		$my_cur_dir_path='';
		$res=get_parent_directories($my_cur_dir_path);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	
	/**
	 * Returns a list of subdirectories found in the given directory.
	 *
	 * The list return starts from the given base directory.
	 * If you require the subdirs of /var/www/ (or /var/www), you will get 'abc/', 'def/', but not '/var/www/abc/'...
	 * @param	string	Base dir
	 * @param	integer	0 if we only want dirs from this level, 1 if we want to recurse into subdirs
	 * @return	strings_array	The list of subdirs in 'abc/' form, -1 on error, and 0 if none found
	 * @todo	Add a session check to see if subdirs_list doesn't exist yet (cached copy)
	 */
 
	function testget_subdirs_list() {
		$basedir = $basedir.'/';
		$dh = opendir($basedir);
		$entry = readdir($dh);
		$basedir = $basedir.'/';
		$dirs_list[] = $entry;
		$res=get_subdirs_list($basedir='',$recurse=0);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue(is_array($dirs_list));
		//var_dump($res);
		//var_dump($dirs_list);
	}
	
	/**
	 * Gets the id of a student publication with a given path
	 * @param string $path
	 * @return true if is found / false if not found
	 */
 
	function testget_work_id() {
		$path='/var/www/path';
		$res=get_work_id($path);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	/**
	 * Get the path of a document in the student_publication table (path relative to the course directory)
	 * @param	integer	Element ID
	 * @return	string	Path (or -1 on error)
	 */
 
	function testget_work_path() {
		$id=1;
		$res=get_work_path($id);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	/**
	 * Insert into the DB of the course all the directories  
	 * @param	string path of the /work directory of the course 
	 * @return	-1 on error, sql query result on success
	 * @version April 2008
	 */ 
 
	function testinsert_all_directory_in_course_table() {
		global $work_table;
		$base_work_dir='/var/www/path';
		$dir_to_array =directory_to_array($base_work_dir,true);	
		$res=insert_all_directory_in_course_table($base_work_dir);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * Checks if the first given directory exists as a subdir of the second given directory
	 * This function should now be deprecated by Security::check_abs_path()
	 * @param	string	Subdir
	 * @param	string	Base dir
	 * @return	integer	-1 on error, 0 if not subdir, 1 if subdir
	 */
 
	function testis_subdir_of() {
		$subdir='';
		$basedir='';
		$res=is_subdir_of($subdir,$basedir);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	/**
	* returns all the javascript that is required for easily
	* validation when you create a work
	* this goes into the $htmlHeadXtra[] array
	*/

	function testto_javascript_work() {
		$res=to_javascript_work();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	/**
	* converts 1-9 to 01-09
	*/
	
	function testtwo_digits() {
		$number=1;
		$res=two_digits($number);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	/**
	 * Update the url of a dir in the student_publication table
	 * @param	string old path
	 * @param	string new path
	 */
	function testupdate_dir_name() {
		global $base_work_dir; 
		$path='';
		$new_name='';
		$res=update_dir_name($path,$new_name);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * Update the url of a work in the student_publication table
	 * @param	integer	ID of the work to update
	 * @param	string	Destination directory where the work has been moved (must end with a '/')
	 * @return	-1 on error, sql query result on success
	 */
	  
	function testupdate_work_url() {
		$id=1;
		$new_path='/var/www/path/';
		$res=update_work_url($id,$new_path);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}	
}
?>
