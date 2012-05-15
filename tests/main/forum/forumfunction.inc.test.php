<?php
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/gradebookitem.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/abstractlink.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/evallink.class.php';

class TestForumFunction extends UnitTestCase {
	
	public function TestForumFunction() {
		$this->UnitTestCase('Test forum function');
	}
	
	public function __construct() {
		// The constructor acts like a global setUp for the class			
		require_once api_get_path(SYS_TEST_PATH).'setup.inc.php';
	}
	
	/**
	 * This function add a attachment file into forum
	 * @param string  a comment about file
	 * @param int last id from forum_post table
	 * @return void
	 */
	public function testadd_forum_attachment_file() {
		 global $_course;
		 $file_comment='testcoment';
		 $last_id = 1;
		 $res = add_forum_attachment_file($file_comment,$last_id);
		 $this->assertTrue(is_null($res));
		 //var_dump($res); 	
	 }
	 
	 /**
	* This function approves a post = change
	*
	* @param $post_id the id of the post that will be deleted
	* @param $action make the post visible or invisible
	* @return string language variable
	*/
	
	public function testapprove_post() {
		 $action= 'invisible';
		 $post_id = 1;
		 $res = approve_post($post_id, $action);
		 $this->assertTrue(is_string($res));
		 //var_dump($res); 	
	 }
	 
	 /**
	* This function changes the lock status in the database
	*
	* @param $content what is it that we want to (un)lock: forum category, forum, thread, post
	* @param $id the id of the content we want to (un)lock
	* @param $action do we lock (=>locked value in db = 1) or unlock (=> locked value in db = 0)
	* @return string, language variable
	* @todo move to itemmanager
	*/
	
	public function testchange_lock_status() {
		 $content = 'testcontent';
		 $action= 'invisible';
		 $id = 1;
		 $res = change_lock_status($content, $id, $action);
		 $this->assertTrue(is_string($res));
		 //var_dump($res); 	
	 }
	 
	 /**
	* This function changes the visibility in the database (item_property)
	*
	* @param $content what is it that we want to make (in)visible: forum category, forum, thread, post
	* @param $id the id of the content we want to make invisible
	* @param $target_visibility what is the current status of the visibility (0 = invisible, 1 = visible)
	* @todo change the get parameter so that it matches the tool constants.
	* @todo check if api_item_property_update returns true or false => returnmessage depends on it.
	* @todo move to itemmanager
	* @return string language variable
	*/
	
	public function testchange_visibility() {
		 $content= 'testcontent';
		 $target_visibility = 1;
		 $id = 1;
		 $res = change_visibility($content, $id, $target_visibility);
		 $this->assertTrue(is_string($res));
		 //var_dump($res); 	
	 }
	 
	 /**
	* This function gets the all information of the last (=most recent) post of the thread
	* This can be done by sorting the posts that have the field thread_id=$thread_id and sort them by post_date
	* @param $thread_id the id of the thread we want to know the last post of.
	* @return an bool or array if there is a last post found, false if there is no post entry linked to that thread => thread will be deleted
	*/
	
	public function testcheck_if_last_post_of_thread() {
		 $thread_id = 1;
		 $res = check_if_last_post_of_thread($thread_id);
		 if(!is_bool($res)) {
		  $this->assertTrue(is_array($res));
		 }
		 //var_dump($res); 	
	 }
	 
	 /**
	* This function returns a piece of html code that make the links grey (=invisible for the student)
	* @param boolean 0/1: 0 = invisible, 1 = visible
	* @return string language variable
	*/
	
	public function testclass_visible_invisible() {
		 $current_visibility_status = 0;
		 $res = class_visible_invisible($current_visibility_status);
		 $this->assertTrue(is_string($res));
		 //var_dump($res); 	
	 }
	 
	 /**
	* This function counts the number of forums inside a given category
	* @param $cat_id the id of the forum category
	* @todo an additional parameter that takes the visibility into account. For instance $countinvisible=0 would return the number
	* 		of visible forums, $countinvisible=1 would return the number of visible and invisible forums
	* @return int the number of forums inside the given category
	*/
	
	public function testcount_number_of_forums_in_category() {
		 $cat_id = 1;
		 $res = count_number_of_forums_in_category($cat_id);
		 $this->assertTrue(is_numeric($res));
		 //var_dump($res); 	
	 }
	 
	 /**
	* This function counts the number of post inside a thread user
	* @param 	int Thread ID
	* @param 	int User ID
	* @return	int the number of post inside a thread user
	*/
	
	public function testcount_number_of_post_for_user_thread() {
		 $thread_id = 1;
		 $user_id = 1;
		 $res = count_number_of_post_for_user_thread($thread_id, $user_id);
		 $this->assertTrue(is_numeric($res));
		 //var_dump($res); 	
	 }
	
	/**
	* This function counts the number of post inside a thread
	* @param 	int Thread ID
	* @return	int the number of post inside a thread
	*/
	
	public function testcount_number_of_post_in_thread() {
		 $thread_id = 1;
		 $res = count_number_of_post_in_thread($thread_id);
		 $this->assertTrue(is_numeric($res));
		 //var_dump($res); 	
	 }
	 
	 /**
	* This function counts the number of user register in course
	* @param 	int Course ID
	* @return	int the number of user register in course
	*/
	
	public function testcount_number_of_user_in_course() {
		 global $cidReq;
		 $course_id = $cidReq;
		 $res = count_number_of_user_in_course($course_id);
		 $this->assertTrue(is_numeric($res));
		 //var_dump($res); 	
	 }
	 
	 /**
	*
	*  This function show current thread qualify .
	* @param integer contains the information the current thread id
	* @param integer contains the information the current session id
	* @return array or null if is empty
	*/
	
	public function testcurrent_qualify_of_thread() {
		 $thread_id = 1;
		 $session_id = 1;
		 $res = current_qualify_of_thread($thread_id,$session_id);
		 $this->assertTrue(is_null($res));
		 //var_dump($res); 	
	 }
	 
	 /**
	 * Display the search results
	 * @return void HTML - display the results
	 */
	 
	 public function testdisplay_forum_search_results() {
		 global $origin;
		 $search_term = 'testterm';
		 ob_start();
		 $res = display_forum_search_results($search_term);
		 ob_end_clean();
		 $this->assertTrue(is_null($res));
		 //var_dump($res); 	
	 }
	 
	 /**
	* This function takes care of the display of the lock icon
	* @param $content what is it that we want to (un)lock: forum category, forum, thread, post
	* @param $id the id of the content we want to (un)lock
	* @param $current_visibility_status what is the current status of the visibility (0 = invisible, 1 = visible)
	* @return void display the lock HTML.
	*/
	 
	 function testdisplay_lock_unlock_icon() {
		 $content = 'testterm';
		 $id = 1;
		 $current_lock_status = 0;
		 ob_start();
		 $res = display_lock_unlock_icon($content, $id, $current_lock_status, $additional_url_parameters='');
		 ob_end_clean();
		 $this->assertTrue(is_null($res));
		 //var_dump($res); 	
	 }
	 
	 /**
	* This function takes care of the display of the up and down icon
	* @param $content what is it that we want to make (in)visible: forum category, forum, thread, post
	* @param $id is the id of the item we want to display the icons for
	* @param $list is an array of all the items. All items in this list should have an up and down icon except for the first (no up icon) and the last (no down icon)
	* 		 The key of this $list array is the id of the item.
	* @return void HTML
	*/
	
	public function testdisplay_up_down_icon() {
		 $content = 'testcontent';
		 $id = 1;
		 $list = array('test');
		 ob_start();
		 $res = display_up_down_icon($content, $id, $list);
		 ob_end_clean();
		 $this->assertTrue(is_null($res));
		 //var_dump($res); 	
	 }
	 
	 /**
	* This function displays the user image from the profile, with a link to the user's details.
	* @param 	int 	User's database ID
	* @param 	str 	User's name
	* @return 	string 	An HTML with the anchor and the image of the user
	*/
	
	public function testdisplay_user_image() {
		 $name = 'testcontent';
		 $user_id = 1;
		 $res = display_user_image($user_id,$name, $origin='');
		 $this->assertTrue(is_string($res));
		 //var_dump($res); 	
	 }
	 
	 /**
	* This function displays the firstname and lastname of the user as a link to the user tool.
	* @param string 
	* @return string HTML
	*/
	
	public function testdisplay_user_link() {
		 $name = 'testcontent';
		 $user_id = 1;
		 $res = display_user_link($user_id, $name, $origin='');
		 $this->assertTrue(is_string($res));
		 //var_dump($res); 	
	 }
	 
	/**
	* This function takes care of the display of the visibility icon
	* @param $content what is it that we want to make (in)visible: forum category, forum, thread, post
	* @param $id the id of the content we want to make invisible
	* @param $current_visibility_status what is the current status of the visibility (0 = invisible, 1 = visible)
	* @return void string HTML 
	*/
	
	public function testdisplay_visible_invisible_icon() {
		 $content = 'testcontent';
		 $current_visibility_status = 0;
		 $id = 1;
		 ob_start();
		 $res = display_visible_invisible_icon($content, $id, $current_visibility_status, $additional_url_parameters='');
		 ob_end_clean();
		 $this->assertTrue(is_null($res));
		 //var_dump($res); 	
	 }
	 
	 /**
	 * This function edit a attachment file into forum
	 * @param string  a comment about file
	 * @param int Post Id
	 * @param int attachment file Id
	 * @return void
	 */
	 
	 public function testedit_forum_attachment_file() {
		 $file_comment = 'testcontent';
		 $id_attach = 1;
		 $post_id = 1;
		 $res = edit_forum_attachment_file($file_comment,$post_id,$id_attach);
		 $this->assertTrue(is_null($res));
		 //var_dump($res); 	
	}
	 
	 /**
	 * Display the search form for the forum and display the search results
	 * @return void display an HTML search results
	 */
	 
	 public function testforum_search() {
		ob_start();
		$res = forum_search();
		ob_end_clean();
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }
	 
	 /** This function gets all the post written by an user
	 * @param int user id
	 * @param string db course name
	 * @return string
	 */
	 
	 public function testget_all_post_from_user() {
		global $_course;
		$course_db = $_course['dbName'];
		$user_id = 1;
		$res = get_all_post_from_user($user_id, $course_db);
		$this->assertTrue(is_string($res));
		//var_dump($res); 	
	 }
	 
	 /**
	 * Show a list with all the attachments according to the post's id
	 * @param the post's id
	 * @return array with the post info
	 */
	 
	 public function testget_attachment() {
		$post_id = 1;
		$res = get_attachment($post_id);
		$this->assertTrue(is_array($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* Retrieve all the information off the forum categories (or one specific) for the current course.
	* The categories are sorted according to their sorting order (cat_order
	* @param $id default ''. When an id is passed we only find the information about that specific forum category. If no id is passed we get all the forum categories.
	* @return an array containing all the information about all the forum categories
	*/
	
	public function testget_forum_categories() {
		$res = get_forum_categories($id='');
		$this->assertTrue(is_array($res));
		//var_dump($res); 	
	 }
	
	/**
	* This function retrieves all the information of a given forum_id
	* @param $forum_id integer that indicates the forum
	* @return array returns
	* @deprecated this functionality is now moved to get_forums($forum_id)
	*/
	/*
	function testget_forum_information() {
		$forum_id = 1;
		$res = get_forum_information($forum_id);
		$this->assertTrue(is_array($res));
		//var_dump($res); 	
	 }*/
	 
	 /**
	* This function retrieves all the information of a given forumcategory id
	* @param $forum_id integer that indicates the forum
	* @return array returns if there are category
	* @return bool returns if there aren't category
	*/
	
	public function testget_forumcategory_information() {
		$cat_id = 1;
		$res = get_forumcategory_information($cat_id);
		if(!is_bool($res)){
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_bool($res));
		}
		//var_dump($res); 	
	 }
	
	/**
	* Retrieve all the forums (regardless of their category) or of only one. The forums are sorted according to the forum_order.
	* Since it does not take the forum category into account there probably will be two or more forums that have forum_order=1, ...
	* @return an array containing all the information about the forums (regardless of their category)
	* @todo check $sql4 because this one really looks fishy.
	*/
	
	public function testget_forums() {
		$res = get_forums($id='');
		if(!is_bool($res)){
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_bool($res));
		}
		//var_dump($res); 	
	 }
	 
	 /**
	* This function retrieves all the fora in a given forum category
	* @param integer $cat_id the id of the forum category
	* @return an array containing all the information about the forums (regardless of their category)
	*/
	
	public function testget_forums_in_category() {
		$cat_id = 1;
		$res = get_forums_in_category($cat_id);
		if(!is_bool($res)){
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_bool($res));
		}
		//var_dump($res); 	
	 }
	 
	 /**
	 * This function gets all the forum information of the all the forum of the group
	 * @param integer $group_id the id of the group we need the fora of (see forum.forum_of_group)
	 * @return array
	 * @todo this is basically the same code as the get_forums function. Consider merging the two.
	 */
	 
	 public function testget_forums_of_group() {
		$group_id = 1;
		$res = get_forums_of_group($group_id);
		if(!is_null($res)){
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_null($res));
		}
		//var_dump($res); 	
	 }
	 
	  /**
	*
	*  This function get qualify historical.
	* @param integer contains the information the current user id
	* @param integer contains the information the current thread id
	* @param boolean contains the information of option to run
	* @return array()
	*/
	
	public function testget_historical_qualify() {
		$user_id = 1;
		$thread_id = 1;
		$opt = true;
		$res = get_historical_qualify($user_id,$thread_id,$opt);
		if(!is_null($res)){
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_null($res));
		}
		//var_dump($res); 	
	 }
	 
	 /**
	* This functions gets all the last post information of a certain forum
	* @param $forum_id the id of the forum we want to know the last post information of.
	* @param $show_invisibles
	* @return array containing all the information about the last post (last_post_id, last_poster_id, last_post_date, last_poster_name, last_poster_lastname, last_poster_firstname)
	*/
	
	public function testget_last_post_information() {
		$forum_id = 1;
		$res = get_last_post_information($forum_id, $show_invisibles=false);
		if(!is_null($res)){
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_null($res));
		}
		//var_dump($res); 	
	 }
	 
	  /** This function get the name of an thread by id
	 * @param int thread_id
	 * @return String
	 **/
	 
	 public function testget_name_thread_by_id() {
		$thread_id = 1;
		$res = get_name_thread_by_id($thread_id);
		if(!is_null($res)){
			$this->assertTrue(is_string($res));
		} else {
			$this->assertTrue(is_null($res));
		}
		//var_dump($res); 	
	 }
	 
	 /** This function get the name of an user by id
	 * @param user_id int
	 * return String
	 */
	 
	 public function testget_name_user_by_id() {
		$user_id = 1;
		$res = get_name_user_by_id($user_id);
		if(!is_null($res)){
			$this->assertTrue(is_string($res));
		} else {
			$this->assertTrue(is_null($res));
		}
		//var_dump($res); 	
	 }
	 
	 /**
	 * This function retrieves all the email adresses of the users who wanted to be notified
	 * about a new post in a certain forum or thread
	 * @param string $content does the user want to be notified about a forum or about a thread
	 * @param integer $id the id of the forum or thread
	 * @return array
	 */
	 
	  public function testget_notifications() {
		$id = 1;
		$content = 'test message notified';
		$res = get_notifications($content,$id);
		if(!is_null($res)){
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_null($res));
		}
		//var_dump($res); 	
	 }
	 
	 /**
	 * Get all the notification subscriptions of the user
	 * = which forums and which threads does the user wants to be informed of when a new
	 * post is added to this thread
	 * @param integer $user_id the user_id of a user (default = 0 => the current user)
	 * @param boolean $force force get the notification subscriptions (even if the information is already in the session
	 * @return array
	 */
	 
	 public function testget_notifications_of_user() {
		$res = get_notifications_of_user($user_id = 0, $force = false);
		if(!is_null($res)){
			$this->assertTrue(is_string($res));
		} else {
			$this->assertTrue(is_null($res));
		}
		//var_dump($res); 	
	 }
	 
	 /**
	* This function retrieves all the information of a post
	* @param $forum_id integer that indicates the forum
	* @return array returns
	*/
	
	public function testget_post_information() {
		$post_id = 1;
		$res = get_post_information($post_id);
		if(!is_null($res)){
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_null($res));
		}
		//var_dump($res); 	
	 }
	 
	 /**
	* With this function we find the number of posts and topics in a given forum.
	* @param int
	* @return array
	* @todo consider to call this function only once and let it return an array where the key is the forum id and the value is an array with number_of_topics and number of post
	* as key of this array and the value as a value. This could reduce the number of queries needed (especially when there are more forums)
	* @todo consider merging both in one query.
	* @deprecated the counting mechanism is now inside the function get_forums
	*/
	/*
	function testget_post_topics_of_forum() {
		$forum_id = 1;
		$res = get_post_topics_of_forum($forum_id);
		if(!is_null($res)){
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_null($res));
		}
		//var_dump($res); 	
	 }*/
	 
	 /**
	* Retrieve all posts of a given thread
	* @param int $thread_id integer that indicates the forum
	* @return an array containing all the information about the posts of a given thread
	*/
	
	public function testget_posts() {
		$thread_id = 1;
		$res = get_posts($thread_id);
		if(!is_null($res)){
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_null($res));
		}
		//var_dump($res); 	
	 }
	 
	 /**
	* This function retrieves information of statistical
	* @param 	int Thread ID
	* @param 	int User ID
	* @param 	int Course ID
	* @return	array the information of statistical
	*/
	
	public function testget_statistical_information() {
		$thread_id = 1;
		$user_id = 1;
		$course_id = 'COURSETEST' ;
		$res = get_statistical_information($thread_id, $user_id, $course_id);
		if(!is_null($res)){
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_null($res));
		}
		//var_dump($res); 	
	 }
	
	/**
	* This function retrieves all the information of a thread
	* @param $forum_id integer that indicates the forum
	* @return array returns
	*/
	
	function testget_thread_information() {
		$thread_id = 1;
		$res = get_thread_information($thread_id);
		if(!is_bool($res)){
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_bool($res));
		}
		//var_dump($res); 	
	 }

	 /**
	* This function return the posts inside a thread from a given user
	* @param 	course code
	* @param 	int Thread ID
	* @param 	int User ID
	* @return	int the number of post inside a thread
	*/

	public function testget_thread_user_post() {
		global $_course;
		$thread_id = 1;
		$course_db = $_course['dbName'];
		$user_id = 1;
		$res = get_thread_user_post($course_db, $thread_id, $user_id );
		if(!is_null($res)){
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_null($res));
		}
		//var_dump($res); 	
	 }
	 /**
	  * @param string
	  * @param int
	  * @param int 
	  * @param int
	  * @return void
	  */
	 public function testget_thread_user_post_limit() {
	 	global $_course;
		$thread_id = 1;
		$course_db = $_course['dbName'];
		$user_id = 1;
		$res = get_thread_user_post_limit($course_db, $thread_id, $user_id, $limit=10);
		if(!is_null($res)){
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_null($res));
		}
		//var_dump($res); 	
	 }
	 
	 /**
	* This function retrieves forum thread users details
	* @param 	int Thread ID
	* @param	string	Course DB name (optional)
	* @return	resource Array of type ([user_id=>w,lastname=>x,firstname=>y,thread_id=>z],[])
	*/
	
	 public function testget_thread_users_details() {
		$thread_id = 1;
		$res = get_thread_users_details($thread_id, $db_name = null);
		if(!is_null($res)){
			$this->assertTrue(is_resource($res));
		} else {
			$this->assertTrue(is_null($res));
		}
		//var_dump($res); 	
	 }
	 
	 /**
	* This function retrieves forum thread users not qualify
	* @param 	int Thread ID
	* @param	string	Course DB name (optional)
	* @return	array Array of type ([user_id=>w,lastname=>x,firstname=>y,thread_id=>z],[])
	*/
	 
	 public function testget_thread_users_not_qualify() {
		$thread_id = 1;
		$res = get_thread_users_not_qualify($thread_id, $db_name = null);
		if(!is_null($res)){
			$this->assertTrue(is_resource($res));
		} else {
			$this->assertTrue(is_null($res));
		}
		//var_dump($res); 	
	 }
	 
	 /**
	* This function retrieves forum thread users qualify
	* @param 	int Thread ID
	* @param	string	Course DB name (optional)
	* @return	array Array of type ([user_id=>w,lastname=>x,firstname=>y,thread_id=>z],[])
	*/
	 
	 public function testget_thread_users_qualify() {
		$thread_id = 1;
		$res = get_thread_users_qualify($thread_id, $db_name = null);
		if(!is_bool($res)){
			$this->assertTrue(is_resource($res));
		} else {
			$this->assertTrue(is_bool($res));
		}
		//var_dump($res); 	
	 }
	 
	 /**
	* Retrieve all the threads of a given forum
	* @param int forum id
	* @return an array containing all the information about the threads
	*/
	
	 public function testget_threads() {
		$forum_id = 1;
		$res = get_threads($forum_id);
		if(!is_bool($res)){
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_bool($res));
		}
		//var_dump($res); 	
	 }
	 
	/**
	* This function retrieves all the unapproved messages for a given forum
	* This is needed to display the icon that there are unapproved messages in that thread (only the courseadmin can see this)
	* @param $forum_id the forum where we want to know the unapproved messages of
	* @return array returns
	*/
	
	 public function testget_unaproved_messages() {
		$forum_id = 1;
		$res = get_unaproved_messages($forum_id);
		if(!is_bool($res)){
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_bool($res));
		}
		//var_dump($res); 	
	 }
	 
	/**
	* This function is used to find all the information about what's new in the forum tool
	* @return void
	*/
	
	 public function testget_whats_new() {
		$res = get_whats_new();
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* This function handles all the forum and forumcategories actions. This is a wrapper for the
	* forum and forum categories. All this code code could go into the section where this function is
	* called but this make the code there cleaner.
	* @return void
	*/
	
	public function testhandle_forum_and_forumcategories() {
		$res = handle_forum_and_forumcategories();
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* This function is called whenever something is made visible because there might be new posts and the user might have indicated that (s)he wanted
	* to be informed about the new posts by mail.
	* @param string
	* @param int
	* @return string language variable
	*/
	
	public function testhandle_mail_cue() {
		$content = 'test content';
		$id = 1;
		$res = handle_mail_cue($content, $id);
		$this->assertTrue(is_string($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* This function return the html syntax for the image
	* @param $image_url The url of the image (absolute or relative)
	* @param $alt The alt text (when the images cannot be displayed). http://www.w3.org/TR/html4/struct/objects.html#adef-alt
	* @param $title The title of the image. Most browsers display this as 'tool tip'. http://www.w3.org/TR/html4/struct/global.html#adef-title
	* @todo this is the same as the Display::xxx function, so it can be removed => all calls have to be changed also
	* @return string url image
	*/
	
	public function testicon() {
		$image_url = api_get_path(WEB_IMG_PATH).'test.png';
		$res = icon($image_url,$alt='',$title='');
		$this->assertTrue(is_string($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* The thread view counter gets increased every time someone looks at the thread
	* @param int 
	* @return void
	*/
	
	public function testincrease_thread_view() {
		$thread_id = 1;
		$res = increase_thread_view($thread_id);
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* This function displays the form for moving a post message to a different (already existing) or a new thread.
	* @return void HTML
	*/
	
	public function testmove_post_form() {
		ob_start();
		$res = move_post_form();
		ob_end_clean();
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* This function displays the form for moving a thread to a different (already existing) forum
	* @return void HTML
	*/
	
	public function testmove_thread_form() {
		ob_start();
		$res = move_thread_form();
		ob_end_clean();
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* This function moves a forum or a forum category up or down
	* @param $content what is it that we want to make (in)visible: forum category, forum, thread, post
	* @param $direction do we want to move it up or down.
	* @param $id the id of the content we want to make invisible
	* @todo consider removing the table_item_property calls here but this can prevent unwanted side effects when a forum does not have an entry in
	* 		the item_property table but does have one in the forum table.
	* @return string language variable
	*/
	
	public function testmove_up_down() {
		$content = 'test content';
		$direction = 'test direction';
		$id = 1;
		$res = move_up_down($content, $direction, $id);
		$this->assertTrue(is_string($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* Prepares a string or an array of strings for display by stripping slashes
	* @param mixed	String or array of strings
	* @return mixed String or array of strings
	*/
	
	public function testprepare4display() {
		$res = prepare4display($input='');
		$this->assertTrue(is_string($res));
		//var_dump($res); 	
	 }
	 
	 /**
	 * Return the link to the forum search page
	 */
	 
	 public function testsearch_link() {
		$res = search_link();
		$this->assertTrue(is_string($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* This function sends the mails for the mail notification
	* @param array
	* @param array
	* @return void
	*/
	
	public function testsend_mail() {
		$res = send_mail($user_info=array(), $thread_information=array());
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* This function sends the notification mails to everybody who stated that they wanted to be informed when a new post
	* was added to a given thread.
	* @param int  id thread
	* @param array reply information
	* @return void
	*/
	
	public function testsend_notification_mails() {
		$thread_id = 1; 
		$reply_info = array('test');
		$res = send_notification_mails($thread_id, $reply_info);
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }
	 
	 /**
	 * Get all the users who need to receive a notification of a new post (those subscribed to
	 * the forum or the thread)
	 * @param integer $forum_id the id of the forum
	 * @param integer $thread_id the id of the thread
	 * @param integer $post_id the id of the post
	 * @return bool
	 */
	 
	 public function testsend_notifications() {
		$res = send_notifications($forum_id=0, $thread_id=0, $post_id=0);
		$this->assertTrue(is_bool($res));
		//var_dump($res); 	
	 }
	 
	 /**
	 * This function stores which users have to be notified of which forums or threads
	 * @param string $content does the user want to be notified about a forum or about a thread
	 * @param integer $id the id of the forum or thread
	 * @return string language variable
	 */
	 
	 public function testset_notification() {
	 	$content = 'test content';
	 	$id = 1;
		$res = set_notification($content,$id, $add_only = false);
		$this->assertTrue(is_string($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* This public function displays the form that is used to add a forum category.
	* @param array
	* @return void HTML
	*/
	
	 public function testshow_add_forum_form() {
	 	ob_start();
		$res = show_add_forum_form($inputvalues=array());
		ob_end_clean();
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* This public function displays the form that is used to add a forum category.
	* @param array input values
	* @return void HTML
	*/
	
	public function testshow_add_forumcategory_form() {
	 	ob_start();
		$res = show_add_forumcategory_form($inputvalues=array());
		ob_end_clean();
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* This public function displays the form that is used to add a post. This can be a new thread or a reply.
	* @param $action is the parameter that determines if we are
	*					1. newthread: adding a new thread (both empty) => No I-frame
	*					2. replythread: Replying to a thread ($action = replythread) => I-frame with the complete thread (if enabled)
	*					3. replymessage: Replying to a message ($action =replymessage) => I-frame with the complete thread (if enabled) (I first thought to put and I-frame with the message only)
	* 					4. quote: Quoting a message ($action= quotemessage) => I-frame with the complete thread (if enabled). The message will be in the reply. (I first thought not to put an I-frame here)
	* @return void HTML
	*/
	 
	public function testshow_add_post_form() {
	 	ob_start();
		$res = show_add_post_form($action='', $id='', $form_values='');
		ob_end_clean();
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* This public function displays the form that is used to edit a forum category.
	* This is more or less a copy from the show_add_forumcategory_form public function with the only difference that is uses
	* some default values. I tried to have both in one public function but this gave problems with the handle_forum_and_forumcategories public function
	* (storing was done twice)
	* @param array
	* @return void HTML
	*/
	
	public function testshow_edit_forumcategory_form() {
	 	ob_start();
		$res = show_edit_forumcategory_form($inputvalues=array());
		ob_end_clean();
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* This public function displays the form that is used to edit a post. This can be a new thread or a reply.
	* @param array contains all the information about the current post
	* @param array contains all the information about the current thread
	* @param array contains all info about the current forum (to check if attachments are allowed)
	* @param array contains the default values to fill the form
	* @return void
	*/
	
	public function testshow_edit_post_form() {
	 	ob_start();
	 	$current_post = array('test');
	 	$current_thread = array('test2');
	 	$current_forum = array('test3');
		$res = show_edit_post_form($current_post, $current_thread, $current_forum, $form_values='',$id_attach=0);
		ob_end_clean();
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }

	/**
	* This public function show qualify.
	* @param string contains the information of option to run
	* @param string contains the information the current course id
	* @param integer contains the information the current forum id
	* @param integer contains the information the current user id
	* @param integer contains the information the current thread id
	* @return integer qualify
	* @example $option=1 obtained the qualification of the current thread
	*/
	
	public function testshow_qualify() {
	 	$option =  1;	 	
	 	$user_id = 1;
	 	$thread_id = 1;
		$res = show_qualify($option,$user_id,$thread_id);
		if(!is_numeric($res)){
			$this->assertTrue(is_null($res));
		} else {
			$this->assertTrue(is_numeric($res));
		}
		//var_dump($res); 	
	 }
	 
	/**
	* This function builds an array of all the posts in a given thread where the key of the array is the post_id
	* It also adds an element children to the array which itself is an array that contains all the id's of the first-level children
	* @return an array containing all the information on the posts of a thread
	*/

	 public function testcalculate_children() {
		$rows = array();
		$res = calculate_children($rows);
		$this->assertTrue(is_array($res));
		//var_dump($res); 	
	 }
	 
	 public function test_phorum_recursive_sort() {
		$rows = array();
		$res = _phorum_recursive_sort($rows, &$threads, $seed=0, $indent=0);
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }
	 

	/**
	* This public function stores the edit of a post in the forum_post table.
	* @param array 
	* @return void HTML
	*/
	
	public function teststore_edit_post() {
		$values = array('test');
		ob_start();
		$res = store_edit_post($values);
		ob_end_clean();
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* This public function stores the forum in the database. The new forum is added to the end.
	* @param array
	* @return string language variable
	*/
	
	public function teststore_forum() {
		$values = array('test');
		ob_start();
		$res = store_forum($values);
		ob_end_clean();
		$this->assertTrue(is_string($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* This public function stores the forum category in the database. The new category is added to the end.
	* @param array
	* @return void HMTL language variable
	*/
	
	public function teststore_forumcategory() {
		$values = array('test');
		ob_start();
		$res = store_forumcategory($values);
		ob_end_clean();
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }
	 
	 /**
	*
	* @param array
	* @return string HTML language variable 
	*/
	
	public function teststore_move_post() {
		$values = array('test');
		ob_start();
		$res = store_move_post($values);
		ob_end_clean();
		$this->assertTrue(is_string($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* @param array
	* @return string HTML language variable 
	*/
	
	public function teststore_move_thread() {
		$values = array('test');
		ob_start();
		$res = store_move_thread($values);
		ob_end_clean();
		$this->assertTrue(is_string($res));
		//var_dump($res); 	
	 }
	 
	 /**
	*
	*  This public function store qualify historical.
	* @param boolean contains the information of option to run
	* @param string contains the information the current course id
	* @param integer contains the information the current forum id
	* @param integer contains the information the current user id
	* @param integer contains the information the current thread id
	* @param integer contains the information the current qualify
	* @return void
	* @example $option=1 obtained the qualification of the current thread
	*/
	
	public function teststore_qualify_historical() {
		$option = 1;
		$couser_id = 1;
		$forum_id = 1;
		$user_id = 1;
		$thread_id = 1;
		$current_qualify = 1;
		$qualify_user_id = 1;
		$res = store_qualify_historical($option,$couser_id,$forum_id,$user_id,$thread_id,$current_qualify,$qualify_user_id);
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }
	 
	 /**
	* This public function stores a reply in the forum_post table.
	* It also updates the forum_threads table (thread_replies +1 , thread_last_post, thread_date)
	*/
	
	public function teststore_reply() {
		$values = array('test');
		ob_start();
		$res = store_reply($values);
		ob_end_clean();
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }
	 
	 /**
	 * @param integer contains the information of user id
	 * @param integer contains the information of thread id
	 * @param integer contains the information of thread qualify
	 * @param integer contains the information of user id of qualifier
	 * @param integer contains the information of time
	 * @param integer contains the information of session id
	 * @return Array() optional
	 **/
	 
 	 public function teststore_theme_qualify() {
		$user_id = 1;
		$thread_id = 1;
		$qualify_time = 1;
		$res = store_theme_qualify($user_id,$thread_id,$thread_qualify=0,$qualify_user_id=0,$qualify_time,$session_id=null);
		if(!is_bool($res)) {
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_bool($res));
		}
		//var_dump($res); 	
	 }
 
	 /**
	* This public function stores a new thread. This is done through an entry in the forum_thread table AND
	* in the forum_post table because. The threads are also stored in the item_property table. (forum posts are not (yet))
	* @param array
	* @return void HTML
	*/
	
	public function teststore_thread() {
		$values = array();
		ob_start();
		$res = store_thread($values);
		ob_end_clean();
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	 }


	/**
	* The relies counter gets increased every time somebody replies to the thread
	* @param
	* @return void
	*/
	
	public function testupdate_thread() {
		$thread_id = 1;
		$last_post_id = 1;
		$post_date = 1;
		$res = update_thread($thread_id, $last_post_id,$post_date);
		$this->assertTrue(is_null($res));
		//var_dump($res); 	
	}
	
 
 	 /**
	* This public function is called when the user is not allowed in this forum/thread/...
	* @return bool display message of "not allowed"
	*/
	
	public function testforum_not_allowed_here() {
		 ob_start();
		 $res = forum_not_allowed_here();
		 ob_end_clean();
		 $this->assertTrue(is_bool($res));
		 //var_dump($res); 	
	 }
	 
	  /**
	 * Delete the all the attachments from the DB and the file according to the post's id or attach id(optional)
	 * @param post id
	 * @param attach id (optional)
	 * @return void
	 */
	 
	 public function testdelete_attachment() {
		 global $_course;
		 $post_id = 1;
		 $res = delete_attachment($post_id,$id_attach=0);
		 $this->assertTrue(is_null($res));
		 //var_dump($res); 	
	 }
	 
	 /**
	* This public function deletes a forum or a forum category
	* This public function currently does not delete the forums inside the category, nor the threads and replies inside these forums.
	* For the moment this is the easiest method and it has the advantage that it allows to recover fora that were acidently deleted
	* when the forum category got deleted.
	*
	* @param $content = what we are deleting (a forum or a forum category)
	* @param $id The id of the forum category that has to be deleted.
	* @return void
	* @todo write the code for the cascading deletion of the forums inside a forum category and also the threads and replies inside these forums
	* @todo config setting for recovery or not (see also the documents tool: real delete or not).
	*/
	
	public function testdelete_forum_forumcategory_thread() {
		 $content= 'testcontent';
		 $id = 1;
		 $res = delete_forum_forumcategory_thread($content, $id);
		 $this->assertTrue(is_null($res));
		 //var_dump($res); 	
	 }
	 
	 /**
	* This public function deletes the forum image if exists
	* @param int forum id
	* @return boolean true if success
	*/
	 
	 public function testdelete_forum_image() {
		 $forum_id = 1;
		 $res = delete_forum_image($forum_id);
		 $this->assertTrue(is_bool($res));
		 //var_dump($res); 	
	 }
	
	/**
	* This public function deletes a forum post. This separate public function is needed because forum posts do not appear in the item_property table (yet)
	* and because deleting a post also has consequence on the posts that have this post as parent_id (they are also deleted).
	* an alternative would be to store the posts also in item_property and mark this post as deleted (visibility = 2).
	* We also have to decrease the number of replies in the thread table
	* @return string language variable
	* @param $post_id the id of the post that will be deleted
	* @todo write recursive public function that deletes all the posts that have this message as parent
	*/
		
	 public function testdelete_post() {
		$table_posts 			= Database :: get_course_table(TABLE_FORUM_POST);
		$table_threads 		= Database :: get_course_table(TABLE_FORUM_THREAD);
		$post_id = 1;
		$res = delete_post($post_id);
		$this->assertTrue(is_string($res));
		//var_dump($res); 	
	 }
	 
	 public function __destruct() {
		// The destructor acts like a global tearDown for the class			
		require_once api_get_path(SYS_TEST_PATH).'teardown.inc.php';			
	}
}
?>
