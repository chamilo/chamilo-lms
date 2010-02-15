<?php

class TestForumFunction extends UnitTestCase {
	
	/**
	 * This function add a attachment file into forum
	 * @param string  a comment about file
	 * @param int last id from forum_post table
	 * @return void
	 */
	 function testadd_forum_attachment_file() {
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
	
	 function testapprove_post() {
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
	
	 function testchange_lock_status() {
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
	
	function testchange_visibility() {
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
	* @return an bool array if there is a last post found, false if there is no post entry linked to that thread => thread will be deleted
	*/
	
	function testcheck_if_last_post_of_thread() {
	 $thread_id = 1;
	 $res = check_if_last_post_of_thread($thread_id);
	 if(!is_bool($res)){
	  $this->assertTrue(is_array($res));
	 }
	 //var_dump($res); 	
	 }
	 
	 /**
	* This function returns a piece of html code that make the links grey (=invisible for the student)
	* @param boolean 0/1: 0 = invisible, 1 = visible
	* @return string language variable
	*/
	
	function testclass_visible_invisible() {
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
	
	function testcount_number_of_forums_in_category() {
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
	
	function testcount_number_of_post_for_user_thread() {
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
	
	function testcount_number_of_post_in_thread() {
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
	
	function testcount_number_of_user_in_course() {
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
	* @return void	
	*/
	
	function testcurrent_qualify_of_thread() {
	 $thread_id = 1;
	 $session_id = 1;
	 $res = current_qualify_of_thread($thread_id,$session_id);
	 $this->assertTrue(is_null($res));
	 //var_dump($res); 	
	 }
	 
	 /**
	 * Delete the all the attachments from the DB and the file according to the post's id or attach id(optional)
	 * @param post id
	 * @param attach id (optional)
	 * @return void
	 */
	 
	 function testdelete_attachment() {
	 global $_course;
	 $post_id = 1;
	 $res = delete_attachment($post_id,$id_attach=0);
	 $this->assertTrue(is_null($res));
	 //var_dump($res); 	
	 }
	 
	 /**
	* This function deletes a forum or a forum category
	* This function currently does not delete the forums inside the category, nor the threads and replies inside these forums.
	* For the moment this is the easiest method and it has the advantage that it allows to recover fora that were acidently deleted
	* when the forum category got deleted.
	*
	* @param $content = what we are deleting (a forum or a forum category)
	* @param $id The id of the forum category that has to be deleted.
	* @return void
	* @todo write the code for the cascading deletion of the forums inside a forum category and also the threads and replies inside these forums
	* @todo config setting for recovery or not (see also the documents tool: real delete or not).
	*/
	
	function testdelete_forum_forumcategory_thread() {
	 $content= 'testcontent';
	 $id = 1;
	 $res = delete_forum_forumcategory_thread($content, $id);
	 $this->assertTrue(is_null($res));
	 //var_dump($res); 	
	 }
	 
	 /**
	* This function deletes the forum image if exists
	* @param int forum id
	* @return boolean true if success
	*/
	 
	 function testdelete_forum_image() {
	 $forum_id = 1;
	 $res = delete_forum_image($forum_id);
	 $this->assertTrue(is_bool($res));
	 //var_dump($res); 	
	 }
	
	/**
	* This function deletes a forum post. This separate function is needed because forum posts do not appear in the item_property table (yet)
	* and because deleting a post also has consequence on the posts that have this post as parent_id (they are also deleted).
	* an alternative would be to store the posts also in item_property and mark this post as deleted (visibility = 2).
	* We also have to decrease the number of replies in the thread table
	* @return string language variable
	* @param $post_id the id of the post that will be deleted
	* @todo write recursive function that deletes all the posts that have this message as parent
	*/
		
	 function testdelete_post() {
	 $table_posts 			= Database :: get_course_table(TABLE_FORUM_POST);
	 $table_threads 		= Database :: get_course_table(TABLE_FORUM_THREAD);
	 $post_id = 1;
	 $res = delete_post($post_id);
	 $this->assertTrue(is_string($res));
	 //var_dump($res); 	
	 }
	 
	 /**
	 * Display the search results
	 * @return void HTML
	 */
	 
	 function testdisplay_forum_search_results() {
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
	
	function testdisplay_up_down_icon() {
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
	
	function testdisplay_user_image() {
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
	
	function testdisplay_user_link() {
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
	
	function testdisplay_visible_invisible_icon() {
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
	 
	 function testedit_forum_attachment_file() {
	 $file_comment = 'testcontent';
	 $id_attach = 1;
	 $post_id = 1;
	 $res = edit_forum_attachment_file($file_comment,$post_id,$id_attach);
	 $this->assertTrue(is_null($res));
	 //var_dump($res); 	
	 }
	 
	 /**
	* This function is called when the user is not allowed in this forum/thread/...
	* @return bool display message of "not allowed"
	*/
	
	function testforum_not_allowed_here() {
	 ob_start();
	 $res = forum_not_allowed_here();
	 ob_end_clean();
	 $this->assertTrue(is_bool($res));
	 //var_dump($res); 	
	 }
	 
	 /**
	 * Display the search form for the forum and display the search results
	 * @return void display an HTML search results
	 */
	 
	 function testforum_search() {
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
	 
	
	
 
 
	
	
	 
	 
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	 
		
 
	 
	
	
	
}
?>
