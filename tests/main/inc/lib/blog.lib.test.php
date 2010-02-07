<?php
require_once(api_get_path(LIBRARY_PATH).'blog.lib.php');
ob_start();
require_once (api_get_path(INCLUDE_PATH).'lib/fckeditor/fckeditor.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');

//require_once(api_get_path(SYS_CODE_PATH).'permissions/blog_permissions.inc.php');
ob_end_clean();
class TestBlog extends UnitTestCase

{
	 public $oblog;
	 public function TestBlog()

	 {

	 	$this->UnitTestCase('Blog Manipulation tests');

	 }

	 public function setUp()
	 {
 	 	$this-> oblog = new Blog();
	 }

	 public function tearDown()
	 {
	 	$this->oblog = null;
	 }

	 /*
	 * todo public function testGetBlobTitle()
	 * todo public function testGetBlogSubtitle()
	 * todo public function testGetBlogUsers()
	 * todo public function testCreateBlog()
	 * todo public function testEditBlog()
	 * todo public function testDeleteBlog()
	 * todo public function testCreatePost()
	 * todo public function testEditPost()
	 * todo public function testDeletePost()
	 * todo public function testCreateComment()
	 * todo public function testDeleteComment()
	 * todo public function testCreateTask()
	 * todo public function testEditTask()
	 * todo public function testDeleteTask()
	 * todo public function testDeleteAssignedTask()
	 * todo public function testGetPersonalTaskList()
	 * todo public function testChangeBlogVisibility()
	 * todo public function testDisplayBlogPosts()
	 * todo public function testDisplaySearchResults()
	 * todo public function testDisplayDayResults()
	 * todo public function testDisplayPost()
	 * todo public function testAddRating()
	 * todo public function testDisplayRating()
	 * todo public function testDisplayRatingForm()
	 * todo public function testGetThreadedComments()
	 * todo public function testDisplayformNewPost()
	 * todo public function testDisplayFormEditPost()
	 * todo public function testDisplayTaskList()
	 * todo public function testDisplayAssignedTaskList()
	 * todo public function testDisplayNewTaskForm()
	 * todo public function testDisplayEditTaskForm()
	 * todo public function testDisplayAssignTaskForm()
	 * todo public function testDisplayEditAssignedTaskForm()
	 * todo public function testAssignTask()
	 * todo public function testEditAssignedTask()
	 * todo public function testDisplaySelectTaskPost()
	 * todo public function testSetUserSubscribed()
	 * todo public function testSetUserUnsubscribed()
	 * todo public function testDisplayFormUserSubscribe()
	 * todo public function testDisplayFormUserUnsubscribe()
	 * todo public function testDisplayNewCommentForm()
	 * todo public function testDisplayMinimonthcalendar()
	 * todo public function testDisplayNewBlogForm()
	 * todo public function testDisplayEditBlogForm()
	 * todo public function testDisplayBlogList()
	 * todo public function testDisplayBlogList()
	 */

	 /**
	  * Test about get Title to a Blog
	 */
	 
	 public function testGetBlogTitle(){
	 	
	 	global $_course;
	 	$res = $this->oblog->get_Blog_title(11);
	 	$this->assertFalse($this->oblog->get_Blog_title(11)===String);
	 	$this->assertTrue(is_String($res));
	 }

	 public function testGetBlogSubtitle(){
	  	$res = $this->oblog->get_Blog_subtitle(0);
	 	$this->assertFalse($this->oblog->get_Blog_subtitle(0)=== null);
	 	$this->assertTrue(is_String($res));
	 	$this->assertNotNull($res);
	 }

	 public function testGetBlogUsers(){
	 	$res = $this->oblog->get_Blog_users(11);
	 	$this->assertTrue($this->oblog->get_Blog_users(1110)===array());
	 	$this->assertTrue(is_array($res));
	 }

	 public function testCreateBlog(){
	 	global $_user;
	 	$res = $this->oblog->create_Blog('testingBlog','pass');
	 	$this->assertTrue(is_null($res));
	 	$this->assertNull($res);
		$this->assertFalse($res);
	 }

	 public function testEditBlog(){
	 	global $_user;
	 	$blog_id = 1;
	 	$title = 'titulo1';
	 	$subtitle = 'subtitulo1';
	 	$res = $this->oblog->edit_Blog($blog_id, $title, $subtitle);
	 	$this->assertNull($res);
	 	$this->assertTrue($this->oblog->edit_Blog($blog_id, $title, $subtitle)=== $res);
		$this->assertFalse($res);
	 }

	 public function testDeleteBlog(){
	 	$blog_id = 1;
	 	$res = $this->oblog->delete_Blog($blog_id);
	 	$this->assertTrue(is_null($res));
	 	$this->assertFalse(is_array($res));
	 }

	 public function testCreatePost(){
	 	global $_user, $_course;
	 	$title = 'xxxxtestxxxx';
	 	$full_text = 'xxxxx';
	 	$file_comment = 'xxxxx';
	 	$blog_id = 1;
	 	$res = $this->oblog->create_post($title, $full_text, $file_comment, $blog_id);
	 	$this->assertTrue($this->oblog->create_post($title, $full_text, $file_comment, $blog_id)=== null);
	 	$this->assertNotNull(is_null,$res);
	 	$this->assertFalse($res);

	 }

	 public function testEditPost(){
	 	ob_start();
	 	$post_id =3;
	 	$title = 'xxTestxx';
	 	$full_text = 'testing public function';
	 	$Blog_id = 11;
		$res = $this->oblog->edit_post(3,'xtestx','test',11);
		ob_end_clean();
		$this->assertNotNull($this->oblog->edit_post(3, 'xtestx','test',11)===null);
		$this->assertFalse($res);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	 }

	 public function testDeletePost(){
	 	$Blog_id = 11;
	 	$post_id = 21;
	 	$res = $this->oblog->delete_post(11,21);
	 	$this->assertTrue($this->oblog->delete_post(11,21)===null);
	 	$this->assertNull(null,$res);
	 	$this->assertTrue(is_null($res));
		//var_dump($res);
	 }

	 public function testCreateComment(){
	 	global $_user, $_course, $Blog_table_attachment;
	 	$res = $this->oblog->create_comment('tesingBlog','xxxxxxx','xxx',12,1,null);
	 	$this->assertNotNull($this->oblog->create_comment('tesingBlog','xxxxxxx','xxx',12,1,null)===null);
	 	$this->assertTrue(is_null($res));
	 	$this->assertFalse($res);
	 }

	 public function testDeleteComment(){
	 	$res = $this->oblog->delete_comment(11,12,2);
	 	$this->assertNotNull($this->oblog->delete_comment(11,12,2)===null);
	 	$this->assertNull(null,$res);
	 }

	 public function testCreateTask(){
		$res = $this->oblog->create_task(1,'xxx','xxxxxxx','xxxx','zzzzz','xxzz','blue');
		$this->assertNotNull($this->oblog->create_task(1,'xxx','xxxxxxx','xxxx','zzzzz','xxzz','blue')=== null);
		$this->assertTrue(is_null($res));
		$this->assertFalse($res);
		$this->assertFalse(null,$res);
	 }

	 public function testEditTask() {
	 	$blog_id = 1;
	 	$task_id = 2;
	 	$title = 'xxxxxx';
	 	$description = 'xx';
	 	$articleDelete = 'aaa';
	 	$articleEdit = 'axa';
	 	$commentsDelete  = 'xax';
	 	$color = 'red';
	 	$res = $this->oblog->edit_task($blog_id, $task_id, $title, $description, $articleDelete, $articleEdit, $commentsDelete, $color);
	 	//$res = Blog::edit_task();
	 	$this->assertTrue($this->oblog->edit_task($blog_id, $task_id, $title, $description, $articleDelete, $articleEdit, $commentsDelete, $color)===null);
	 	$this->assertTrue(is_null($res));
	 	$this->assertFalse(is_string($res));
	 	$this->assertNull($res);
	 }

	 public function testDeleteTask(){
	 	$blog_id = 1;
	 	$task_id = 2;
	 	$res = $this->oblog->delete_task($blog_id, $task_id);
	 	$this->assertTrue($this->oblog->delete_task($blog_id, $task_id)===null);
	 	$this->assertTrue(is_null($res));
	 }

	 public function testDeleteAssignedTask(){
	 	$blog_id = 1;
	 	$task_id = 2;
	 	$user_id = 1;
	 	$res = $this->oblog->delete_assigned_task($blog_id, $task_id,$user_id);
	 	$this->assertTrue($this->oblog->delete_assigned_task($blog_id, $task_id,$user_id)===null);
	 	$this->assertNotNull(is_null($res));
	 	$this->assertFalse($res);
	 }

	 public function testGetPersonalTaskList(){
	 	global $_user;
	 	ob_start();
	 	$res = Blog::get_personal_task_list('a');
	 	$this->assertFalse($res);
	 	ob_end_clean();
	 }

	 public function testChangeBlogVisibility(){
	 	$blog_id = 1;
	 	$res = $this->oblog->change_blog_visibility($blog_id);
	 	$this->assertTrue($this->oblog->change_blog_visibility($blog_id)=== null);
	 	$this->assertTrue(is_null($res));
	 }

	 public function testDisplayBlogPosts(){
	 	ob_start();
	 	$blog_id = 1;
	 	$filter = '1=1';
	 	$max_number_of_posts = 20;
	 	$res = BLog::display_blog_posts($blog_id, $filter, $max_number_of_posts);
	 	$this->assertTrue($this->oblog->display_blog_posts($blog_id, $filter, $max_number_of_posts)=== null);
	 	ob_end_clean();
	 	$this->assertNull($res);
	 	$this->assertTrue(is_null($res));
	 	$this->assertFalse(null, $res);
	 }

	 public function testDisplaySearchResults(){
	 	ob_start();
	 	$blog_id = 1;
	 	$query_string = '"SELECT post.*, user.lastname, user.firstname FROM $tbl_blogs_posts"';
	 	$res = $this->oblog->display_search_results($blog_id, $query_string);
		ob_end_clean();
		$this->assertTrue(is_null($res));
		$this->assertNull($res);
	 }

	 public function testDisplayDayResults(){
	 	ob_start();
	 	$blog_id = 1;
	 	$query_string = '01-01-2010';
	 	$res = $this->oblog->display_day_results($blog_id, $query_string);
	 	ob_end_clean();
	 	$this->assertTrue(is_null($res));
	 	$this->assertFalse($res);
	 	$this->assertNull(null,$res);
	 }

	 public function testDisplayPost(){
	 	ob_start();
	 	$blog_id = 1;
	 	$post_id = 2;
	 	$res = $this->oblog->display_post($blog_id, $post_id);
	 	ob_end_clean();
	 	$this->assertTrue(is_null($res));
	 	$this->assertFalse($res);
	 }

	 public function testAddRating(){
	 	global $_user;
	 	$res = $this->oblog->add_rating(null,11,2,5);
	 	$this->assertFalse($this->oblog->add_rating(null,11,2,5)=== bool);
	 	$this->assertTrue(is_bool($res));
	 	$this->assertFalse(null,$res);
	 }

	 public function testDisplayRating(){
	 	ob_start();
	 	$res = $this->oblog->display_rating('xxx',11,1);
	 	$this->assertFalse($this->oblog->display_rating('xxx',11,1)===null);
	 	$this->assertTrue(is_numeric($res));
	 	$this->assertFalse($res);
	 	ob_end_clean();
	 }

	 public function testDisplayRatingForm(){
	 	global $_user;
	 	$res = $this->oblog->display_rating_form('xxx',11,1,null);
	 	$this->assertFalse($this->oblog->display_rating_form('xxx',11,1,null)===null);
	 	$this->assertTrue(is_string($res));
	 	$this->assertNotNull($res,null);
	 }

	 public function testGetThreadedComments(){
	 	ob_start();
	 	$current = 0;
	 	$current_level = 0;
	 	$blog_id = 1;
	 	$post_id = 2;
	 	$task_id = 0;
	 	global $charset, $dataFormatLong;
	 	$res = $this->oblog->get_threaded_comments($current, $current_level, $blog_id, $post_id, $task_id);
	 	ob_end_clean();
	 	$this->assertFalse($res);
	 	$this->assertTrue(is_null($res));
	 }

	 public function testDisplayFormNewPost(){
	 	ob_start();
	 	$blog_id = 1;
	 	$res = Blog::display_form_new_post($blog_id);
	 	$this->assertTrue(is_null($res));
	 	$this->assertNull($res);
	 	ob_end_clean();	 	
	 } 
	
	 public function testDisplayFormEditPost(){
	 	ob_start();
	 	$blog_id = 1;
	 	$post_id = 2;
	 	$res = $this->oblog->display_form_edit_post($blog_id, $post_id);
	 	$this->assertNotNull(is_null($res));
	 	$this->assertFalse($res);
	 	ob_end_clean();
	 	//var_dump($res);
	 }	

	 public function testDisplayTaskList(){
	 	ob_start();
	 	$res = $this->oblog->display_task_list(11);
	 	$this->assertTrue($this->oblog->display_task_list(11)===null);
	 	ob_end_clean();
	 }

	 public function testDisplayAssignedTaskList(){
	 	ob_start();
	 	global $charset, $color2;
	 	$res = $this->oblog->display_assigned_task_list(11);
	 	$this->assertTrue($this->oblog->display_assigned_task_list(11)===null);
	 	ob_end_clean();
	 	$this->assertFalse($res);
	 	
	 }

	 public function testDisplayNewTaskForm(){
	 	ob_start();
	 	$res = $this->oblog->display_new_task_form(11);
	 	$this->assertTrue($this->oblog->display_new_task_form(11)===null);
	 	ob_end_clean();
	 	$this->assertFalse($res);
	 	

	 }

	 public function testDisplayEditTaskForm(){
	 	ob_start();
	 	$res = $this->oblog->display_edit_task_form(11,12);
	 	$this->assertTrue($this->oblog->display_edit_task_form(11,12)===null);
	 	ob_end_clean();
	 	$this->assertTrue(is_null($res));
	 	$this->assertFalse($res);

	 }

	 public function testDisplayAssignTaskForm(){
	 	ob_start();
	 	$res = $this->oblog->display_assign_task_form(11);
	 	$this->assertTrue($this->oblog->display_assign_task_form(11)===null);
	 	ob_end_clean();
	 	$this->assertFalse($res);
	 	$this->assertTrue(is_null($res));
	 	
	 }

	 public function testDisplayEditAssignedTaskForm(){
	 	global $MonthsLong;
	 	ob_start();
	 	$res = $this-> oblog->display_edit_assigned_task_form(11,12,1);
	 	$this->assertTrue($this->oblog->display_edit_assigned_task_form(11,12,1)===null);
	 	ob_end_clean();
	 	$this->assertFalse($res);

	 }

	 public function testAssignTask(){
	 	ob_start();
	 	$res = $this->oblog->assign_task(11,1,12,null);
	 	$this->assertTrue($this->oblog->assign_task(11,1,12,null)===null);
	 	ob_end_clean();
	 	$this->assertFalse(is_numeric($res));
	 	$this->assertNull(null,$res);
	 	
	 }

	 public function testEditAssignedTask(){
	 	$task = array('blog_id'=>11,
					  'user_id'=>1,
                      'task_id'=>12,
                      'target_date'=>'xxxxxxx',
                 	  'old_user_id'=>10,
                 	  'old_task_id'=>11,
                 	  'old_target_date'=>'xxxzxxx'
                 	 );
        $res = $this->oblog->edit_assigned_task($task['blog_id'],$task['user_id'], $task['task_id'], $task['target_date'], $task['old_user_id'], $task['old_task_id'], $task['old_target_date']);
        $this->assertNull($res);
        $this->assertTrue(is_null($res));
	 }

	 public function testDisplaySelectTaskPost(){
	 	ob_start();
	 	$res = $this->oblog->display_select_task_post(11,12);
	 	$this->assertTrue($this->oblog->display_select_task_post(11,12)===null);
	 	ob_end_clean();
	 	$this->assertTrue(is_null($res));
	 	$this->assertFalse($res);
	 	
     }

	 public function testSetUserSubscribed(){
	 	$res = $this->oblog->set_user_subscribed(11,12);
	 	$this->assertTrue($this->oblog->set_user_subscribed(11,12)===null);
	 	$this->assertFalse($res);
	 	$this->assertTrue(is_null($res));
     }

	 public function testUserUnsubscribed(){
	 	$res = $this->oblog->set_user_unsubscribed(11,12);
	 	$this->assertTrue($this->oblog->set_user_unsubscribed(11,12)===null);
	 	$this->assertFalse($res);
	 	$this->assertTrue(is_null($res));
	 }
	
	 public function testDisplayFormUserSubscribe(){
	 	ob_start();
	 	$res = $this->oblog->display_form_user_subscribe(12);
	 	$this->assertTrue($this->oblog->display_form_user_subscribe(12)===null);
	 	ob_end_clean();
	 	$this->assertNotNull(is_null($res));
	 	$this->assertFalse($res);
	 	
	}

	/**
	 * this function have been tested modified the function
	 * display_form_user_unsubscribe in the blog.lib.php
	 * main_table and course_table.
	 *
	 */
	 
    public function testDisplayFormUserUnsubscribe(){
		
		global $_user;
		ob_start();
		$blog_id = '1';
		$res = Blog::display_form_user_unsubscribe($blog_id);
		ob_end_clean();
		$this->assertTrue(is_null($res));		
		$this->assertNull($res);
		
	}

	public function testDisplayFormUserRights(){
		ob_start();
		$res = $this->oblog->display_form_user_rights(12);
		$this->assertTrue($this->oblog->display_form_user_rights(12)===null);
		ob_end_clean();
		$this->assertFalse($res);
		
	} 
	
	public function testDisplayNewCommentForm(){
		$blog_id = '12';
		$post_id='1';
		$title='test';
		ob_start();
		$res =$this->oblog->display_new_comment_form($blog_id,$post_id,$title);
		ob_end_clean();
		$this->assertFalse($res);
		$this->assertNotNull(is_null($res));
		
	}
    
	public function testDisplayMinimonthcalendar(){
		global $_user,$DaysShort, $MonthsLong;
		ob_start();
		$month = 12;
		$year = 2010;
		$blog_id = 1;
		$res = $this->oblog->display_minimonthcalendar($month, $year, $blog_id);
		$this->assertTrue($this->oblog->display_minimonthcalendar($month, $year, $blog_id)=== null);
		ob_end_clean();
		$this->assertTrue(is_null($res));
		
	}
     
	public function testDisplayNewBlogForm(){
		ob_start();
		$res = $this->oblog->display_new_blog_form();
		$this->assertFalse($res);
		$this->assertTrue(is_null($res));
		$this->assertTrue($this->oblog->display_new_blog_form()===null);
		ob_end_clean();
	}
    
	public function testDisplayEditBlogForm(){
		ob_start();
		$res = $this->oblog->display_edit_blog_form(12);
		$this->assertTrue($this->oblog->display_edit_blog_form(12)===null);
		ob_end_clean();
		$this->assertTrue(is_null($res));
		
	}

	public function testDisplayBlogList(){
		ob_start();
		$res = $this->oblog->display_blog_list();
		$this->assertTrue($this->oblog->display_blog_list()===null);
		ob_end_clean();
		$this->assertTrue(is_null($res));
		
	}
	
	public function testGetBlogAttachment(){
		ob_start();
		ob_end_clean();
		global $_configuration;
		$blog_id = '0';
		$post_id = null; 
		$comment_id = null;
		$res = get_blog_attachment($blog_id, $post_id,$comment_id);
		$this->assertFalse($res);
		$this->assertTrue(is_array($res));
	}
    
	public function testDeleteAllBlogAttachment(){
		global $_course,$_configuration;		
		$blog_id = 1;
		$post_id=null;
		$comment_id=null;
		$res = delete_all_blog_attachment($blog_id,$post_id,$comment_id);
		$this->assertFalse($res);
		$this->assertNull($res);
	} 
 	
	public function testGetBlogPostFromUser(){
		global $_configuration;
		$res = get_blog_post_from_user('chamilo_COURSETEST',1);
		$this->assertFalse($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
    
	public function testGetBlogCommentFromUser(){
		global $_configuration;
		$course_datos['wanted_code'] = 'chamilo_COURSETEST';
		$user_id = 1;
		$res = get_blog_comment_from_user($course_datos['wanted_code'],1);
		$this->assertFalse($res);
		$this->assertTrue(is_string($res));
		$path = api_get_path(SYS_PATH).'archive';		
		if ($handle = opendir($path)) {
			while (false !== ($file = readdir($handle))) {				
				if (strpos($file,'COURSETEST')!==false) {										
					if (is_dir($path.'/'.$file)) {						
						rmdirr($path.'/'.$file);						
					}				
				}				
			}
		closedir($handle);
		}
	} 
/*	
	public function testDeleteCourse() {
		global $cidReq;			
		$resu = CourseManager::delete_course($cidReq);				
	}
	
	*/
	
	
	
	
	
}   
?>