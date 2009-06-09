<?php
require_once(api_get_path(LIBRARY_PATH).'blog.lib.php');

class TestBlog extends UnitTestCase 

{
	
	function TestBlog() 
	
	{
		
		$this->UnitTestCase('Blog Manipulation tests');
		
	}
	
	/*
	 * todo function testGetBlobTitle()
	 * todo function testGetBlogSubtitle()
	 * todo function testGetBlogUsers()
	 * todo function testCreateBlog()
	 * todo function testEditBlog()
	 * todo function testDeleteBlog()
	 * todo function testCreatePost()
	 * todo function testEditPost()
	 * todo function testDeletePost()
	 * todo function testCreateComment()
	 * todo function testDeleteComment()
	 * todo function testCreateTask()
	 * todo function testEditTask()
	 * todo function testDeleteTask()
	 * todo function testDeleteAssignedTask()
	 * todo function testGetPersonalTaskList()
	 * todo function testChangeBlogVisibility()
	 * todo function testDisplayBlogPosts()
	 * todo function testDisplaySearchResults()
	 * todo function testDisplayDayResults()
	 * todo function testDisplayPost()
	 * todo function testAddRating()
	 * todo function testDisplayRating()
	 * todo function testDisplayRatingForm()
	 * todo function testGetThreadedComments()
	 * todo function testDisplayformNewPost()
	 * todo function testDisplayFormEditPost()
	 * todo function testDisplayTaskList()
	 * todo function testDisplayAssignedTaskList()
	 * todo function testDisplayNewTaskForm()
	 * todo function testDisplayEditTaskForm()
	 * todo function testDisplayAssignTaskForm()
	 * todo function testDisplayEditAssignedTaskForm()
	 * todo function testAssignTask()
	 * todo function testEditAssignedTask()
	 * todo function testDisplaySelectTaskPost()
	 * todo function testSetUserSubscribed()
	 * todo function testSetUserUnsubscribed()
	 * todo function testDisplayFormUserSubscribe()
	 * todo function testDisplayFormUserUnsubscribe()
	 * todo function testDisplayNewCommentForm()
	 * todo function testDisplayMinimonthcalendar()
	 * todo function testDisplayNewBlogForm()
	 * todo function testDisplayEditBlogForm()
	 * todo function testDisplayBlogList()
	 * todo function testDisplayBlogList()
	 */
	 
	 /**
	  * Test about get Title to a blog
	 */
	 
	 function testGetBlogTitle(){
	 	ob_start();
	 	$blog_id = 11;
	 	Blog::get_blog_title($blog_id);
	 	$res = ob_get_contents();
	 	ob_end_clean();
	 	$this->assertFalse($res);
	 
	 }
	 
	 function testGetBlogSubtitle(){
	 	$blog_id = 11;
	 	$res = Blog::get_blog_subtitle($blog_id);
	 	$this->assertFalse($res);
	 
	 }
	 
	 function testGetBlogUsers(){
	 	$blog_id = 11;
	 	$res = Blog::get_blog_users($blog_id);
	 	$this->assertFalse($res);
	 	
	 }
	 
	 function testCreateBlog(){
	 	global $_user;
	 	ob_start();
	 	$_user = array('title'=>'TestingBlog','subtitle'=>'PassOrNotPass');
	 	$res = ob_get_contents();
	 	ob_end_clean();
	 	Blog::create_blog($_user['title'],$_user['subtitle']);
	 	$this->assertFalse($res);
	 	$res = Blog::delete_blog($_user);
	 	$this->assertTrue($res);
	 	
	 }
	 
	 function testEditBlog(){
	 	ob_start();
	 	global $_user;
	 	$_user = array('blog_id'=>1,'title'=>'TestBlog','subtitle'=>'testing');
	 	Blog::edit_blog($_user['blog_id'],$_user['title'],$_user['subtitle']);
	 	$res = ob_get_contents();
	 	ob_end_clean();
	 	$this->assertFalse($res);
	
	 }
	 
	 function testDeleteBlog(){
	 	$blog_id = 11;
	 	$res = Blog::delete_blog($blog_id);
	 	$this->assertTrue($res);
	 
	 }
	 
	 function testCreatePost(){
	 	global $_user, $_course, $blog_table_attachment;
	 	ob_start();
	 	$blog_table_attachment = array('title' => 'xxxxtestxxxx', 
	 								   'full_text'=>'xxxxx', 
 	 	  						       'file_comment'=>'xxxxx',
	 								   'blog_id'=>11
	 								  );
	 	$res = ob_get_contents();
	 	ob_end_clean();	 								  	   
	  	Blog::create_post($blog_table_attachment['title'], $blog_table_attachment['full_text'],$blog_table_attachment['file_comment'], $blog_table_attachment['blog_id']);
	 	$this->assertFalse($res);
	 	$res = BLog::delete_post($blog_table_attachment);
	 	$this->assertTrue($res);
	 		   
	 }
	 
	 function testEditPost(){
	 	ob_start();
	 	$post_id =3;
	 	$title = 'xxTestxx';
	 	$full_text = 'testing function';
	 	$blog_id = 11;
	 	Blog::edit_post($post_id, $title, $full_text, $blog_id);
	 	$res = ob_get_contents();
	 	ob_end_clean();
	 	$this->assertFalse($res);
	 	
	 }
	 
	 function testDeletePost(){
	 	$blog_id = 11;
	 	$post_id = 21;
	 	$res = Blog::delete_post($blog_id, $post_id);
	 	$this->assertTrue($res);
	 	
	 }
	 /*
	 function*/
}

?>
