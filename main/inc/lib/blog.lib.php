<?php
/* For licensing terms, see /license.txt */

/**
 * Class Blog
 *
 * Contains several functions dealing with displaying,
 * editing,... of a blog

 * @package chamilo.blogs
 * @author Toon Keppens <toon@vi-host.net>
 * @author Julio Montoya - Cleaning code
 */
class Blog
{
    /**
     * Get the title of a blog
     * @author Toon Keppens
     *
     * @param int $blog_id
     *
     * @return String Blog Title
     */
    public static function get_blog_title($blog_id)
    {
        $course_id = api_get_course_int_id();

        if (is_numeric($blog_id)) {
            $tbl_blogs = Database::get_course_table(TABLE_BLOGS);

            $sql = "SELECT blog_name
                    FROM " . $tbl_blogs . "
                    WHERE c_id = $course_id AND blog_id = " . intval($blog_id);

            $result = Database::query($sql);
            $blog = Database::fetch_array($result);

            return stripslashes($blog['blog_name']);
        }
    }

    /**
     * Get the description of a blog
     * @author Toon Keppens
     *
     * @param Integer $blog_id
     *
     * @return String Blog description
     */
    public static function get_blog_subtitle($blog_id)
    {
        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);
        $course_id = api_get_course_int_id();
        $sql = "SELECT blog_subtitle FROM $tbl_blogs
                WHERE c_id = $course_id AND blog_id ='".intval($blog_id)."'";
        $result = Database::query($sql);
        $blog = Database::fetch_array($result);

        return stripslashes($blog['blog_subtitle']);
    }

    /**
     * Get the users of a blog
     * @author Toon Keppens
     *
     * @param Integer $blog_id
     *
     * @return Array Returns an array with [userid]=>[username]
     */
    public static function get_blog_users($blog_id)
    {
        // Database table definitions
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_blogs_rel_user = Database::get_course_table(TABLE_BLOGS_REL_USER);

        $course_id = api_get_course_int_id();

        // Get blog members
        $sql = "SELECT user.user_id, user.firstname, user.lastname
                FROM  $tbl_blogs_rel_user blogs_rel_user
                INNER JOIN $tbl_users user
                ON (blogs_rel_user.user_id = user.user_id)
                WHERE
                    blogs_rel_user.c_id = $course_id AND
                    blogs_rel_user.blog_id = '" . (int)$blog_id."'";
        $result = Database::query($sql);
        $blog_members = array ();
        while ($user = Database::fetch_array($result)) {
            $blog_members[$user['user_id']] = api_get_person_name(
                $user['firstname'],
                $user['lastname']
            );
        }

        return $blog_members;
    }

    /**
     * Creates a new blog in the given course
     * @author Toon Keppens
     * @param string $title
     */
    public static function create_blog($title, $subtitle)
    {
        $_user = api_get_user_info();
        $course_id = api_get_course_int_id();

        $current_date = date('Y-m-d H:i:s', time());
        $session_id = api_get_session_id();
        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);
        $tbl_tool = Database::get_course_table(TABLE_TOOL_LIST);
        $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);

        //verified if exist blog
        $sql = 'SELECT COUNT(*) as count FROM '.$tbl_blogs.'
                WHERE
                    c_id = '.$course_id.' AND
                    blog_name="'.Database::escape_string($title).'" AND
                    blog_subtitle="'.Database::escape_string($subtitle).'"';
        $res = Database::query($sql);
        $info_count = Database::result($res, 0, 0);

        if ($info_count == 0) {
            // Create the blog
            $params = [
                'blog_id' => 0,
                'c_id' => $course_id,
                'blog_name' => $title,
                'blog_subtitle' =>  $subtitle,
                'date_creation' => $current_date,
                'visibility' => 1 ,
                'session_id' => $session_id,
            ];
            $this_blog_id = Database::insert($tbl_blogs, $params);

            if ($this_blog_id > 0) {
                $sql = "UPDATE $tbl_blogs SET blog_id = iid WHERE iid = $this_blog_id";
                Database::query($sql);

                // insert into item_property
                api_item_property_update(
                    api_get_course_info(),
                    TOOL_BLOGS,
                    $this_blog_id,
                    'BlogAdded',
                    api_get_user_id()
                );
            }

            // Make first post. :)
            $params = [
                'post_id' => 0,
                'c_id' => $course_id,
                'title' => get_lang("Welcome"),
                'full_text' => get_lang('FirstPostText'),
                'date_creation' => $current_date,
                'blog_id' => $this_blog_id,
                'author_id' => $_user['user_id'],
            ];
            $postId = Database::insert($tbl_blogs_posts, $params);
            if ($postId) {
                $sql = "UPDATE $tbl_blogs_posts SET post_id = iid WHERE iid = $postId";
                Database::query($sql);
            }

            // Put it on course homepage
            $sql = "INSERT INTO $tbl_tool (c_id, name, link, image, visibility, admin, address, added_tool, session_id, target)
                    VALUES ($course_id, '".Database::escape_string($title)."','blog/blog.php?blog_id=".(int)$this_blog_id."','blog.gif','1','0','pastillegris.gif',0,'$session_id', '')";
            Database::query($sql);

            $toolId = Database::insert_id();
            if ($toolId) {
                $sql = "UPDATE $tbl_tool SET id = iid WHERE iid = $toolId";
                Database::query($sql);
            }

            // Subscribe the teacher to this blog
            Blog::set_user_subscribed($this_blog_id, $_user['user_id']);
        }
    }

    /**
     * Update title and subtitle of a blog in the given course
     * @author Toon Keppens
     * @param string $title
     */
    public static function edit_blog($blog_id, $title, $subtitle)
    {
        // Table definitions
        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);
        $tbl_tool = Database::get_course_table(TABLE_TOOL_LIST);

        $course_id = api_get_course_int_id();

        // Update the blog
        $sql = "UPDATE $tbl_blogs SET
                blog_name = '".Database::escape_string($title)."',
                blog_subtitle = '".Database::escape_string($subtitle)."'
                WHERE
                    c_id = $course_id AND
                    blog_id ='".Database::escape_string((int)$blog_id)."'
                LIMIT 1";
        Database::query($sql);

        //update item_property (update)
        api_item_property_update(
            api_get_course_info(),
            TOOL_BLOGS,
            intval($blog_id),
            'BlogUpdated',
            api_get_user_id()
        );

        // Update course homepage link
        $sql = "UPDATE $tbl_tool SET
                name = '".Database::escape_string($title)."'
                WHERE c_id = $course_id AND link = 'blog/blog.php?blog_id=".(int)$blog_id."' 
                LIMIT 1";
        Database::query($sql);
    }

    /**
     * Deletes a blog and it's posts from the course database
     * @author Toon Keppens
     * @param Integer $blog_id
     */
    public static function delete_blog($blog_id)
    {
        // Init
        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);
        $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);
        $tbl_blogs_comment = Database::get_course_table(TABLE_BLOGS_COMMENTS);
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
        $tbl_tool = Database::get_course_table(TABLE_TOOL_LIST);
        $tbl_blogs_rating = Database::get_course_table(TABLE_BLOGS_RATING);

        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);

        // Delete posts from DB and the attachments
        delete_all_blog_attachment($blog_id);

        //Delete comments
        $sql = "DELETE FROM $tbl_blogs_comment WHERE c_id = $course_id AND blog_id ='".$blog_id."'";
        Database::query($sql);

        // Delete posts
        $sql = "DELETE FROM $tbl_blogs_posts WHERE c_id = $course_id AND blog_id ='".$blog_id."'";
        Database::query($sql);

        // Delete tasks
        $sql = "DELETE FROM $tbl_blogs_tasks WHERE c_id = $course_id AND blog_id ='".$blog_id."'";
        Database::query($sql);

        // Delete ratings
        $sql = "DELETE FROM $tbl_blogs_rating WHERE c_id = $course_id AND blog_id ='".$blog_id."'";
        Database::query($sql);

        // Delete blog
        $sql ="DELETE FROM $tbl_blogs WHERE c_id = $course_id AND blog_id ='".$blog_id."'";
        Database::query($sql);

        // Delete from course homepage
        $sql = "DELETE FROM $tbl_tool WHERE c_id = $course_id AND link = 'blog/blog.php?blog_id=".$blog_id."'";
        Database::query($sql);

        //update item_property (delete)
        api_item_property_update(
            api_get_course_info(),
            TOOL_BLOGS,
            $blog_id,
            'delete',
            api_get_user_id()
        );
    }

    /**
     * Creates a new post in a given blog
     * @author Toon Keppens
     * @param String $title
     * @param String $full_text
     * @param Integer $blog_id
     */
    public static function create_post($title, $full_text, $file_comment, $blog_id)
    {
        $_user = api_get_user_info();
        $_course = api_get_course_info();
        $course_id = $_course['real_id'];

        $blog_table_attachment = Database::get_course_table(TABLE_BLOGS_ATTACHMENT);
        $upload_ok=true;
        $has_attachment=false;
        $current_date = api_get_utc_datetime();

        if (!empty($_FILES['user_upload']['name'])) {
            $upload_ok = process_uploaded_file($_FILES['user_upload']);
            $has_attachment=true;
        }

        if ($upload_ok) {
            // Table Definitions
            $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);

            // Create the post
            $sql = "INSERT INTO $tbl_blogs_posts (c_id, title, full_text, date_creation, blog_id, author_id )
                    VALUES ($course_id, '".Database::escape_string($title)."', '".Database::escape_string($full_text)."','".$current_date."', '".(int)$blog_id."', '".(int)$_user['user_id']."');";

            Database::query($sql);
            $last_post_id = Database::insert_id();

            if ($last_post_id) {
                $sql = "UPDATE $tbl_blogs_posts SET post_id = iid WHERE iid = $last_post_id";
                Database::query($sql);
            }

            if ($has_attachment) {
                $courseDir   = $_course['path'].'/upload/blog';
                $sys_course_path = api_get_path(SYS_COURSE_PATH);
                $updir = $sys_course_path.$courseDir;

                // Try to add an extension to the file if it hasn't one
                $new_file_name = add_ext_on_mime(stripslashes($_FILES['user_upload']['name']), $_FILES['user_upload']['type']);

                // user's file name
                $file_name = $_FILES['user_upload']['name'];

                if (!filter_extension($new_file_name)) {
                    Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
                } else {
                    $new_file_name = uniqid('');
                    $new_path = $updir.'/'.$new_file_name;
                    $result = @move_uploaded_file($_FILES['user_upload']['tmp_name'], $new_path);
                    $comment = Database::escape_string($file_comment);

                    // Storing the attachments if any
                    if ($result) {
                        $sql = 'INSERT INTO '.$blog_table_attachment.'(c_id, filename,comment, path, post_id,size, blog_id,comment_id) '.
                               "VALUES ($course_id, '".Database::escape_string($file_name)."', '".$comment."', '".Database::escape_string($new_file_name)."' , '".$last_post_id."', '".intval($_FILES['user_upload']['size'])."',  '".$blog_id."', '0' )";
                        Database::query($sql);
                        $id = Database::insert_id();
                        if ($id) {
                            $sql = "UPDATE $blog_table_attachment SET id = iid WHERE iid = $id";
                            Database::query($sql);
                        }
                    }
                }
            }
        } else {
            Display::display_error_message(get_lang('UplNoFileUploaded'));
        }
    }

    /**
     * Edits a post in a given blog
     * @author Toon Keppens
     * @param Integer $blog_id
     * @param String $title
     * @param String $full_text
     * @param Integer $blog_id
     */
    public static function edit_post($post_id, $title, $full_text, $blog_id)
    {
        $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);
        $course_id = api_get_course_int_id();

        // Create the post
        $sql = "UPDATE $tbl_blogs_posts SET
                title = '" . Database::escape_string($title)."',
                full_text = '" . Database::escape_string($full_text)."'
                WHERE c_id = $course_id AND post_id ='".(int)$post_id."' AND blog_id ='".(int)$blog_id."'
                LIMIT 1 ";
        Database::query($sql);
    }

    /**
     * Deletes an article and it's comments
     * @author Toon Keppens
     * @param int $blog_id
     * @param int $post_id
     */
    public static function delete_post($blog_id, $post_id)
    {
        $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);
        $tbl_blogs_comments = Database::get_course_table(TABLE_BLOGS_COMMENTS);
        $tbl_blogs_rating = Database::get_course_table(TABLE_BLOGS_RATING);

        $course_id = api_get_course_int_id();

        // Delete ratings on this comment
        $sql = "DELETE FROM $tbl_blogs_rating
                WHERE c_id = $course_id AND blog_id = '".(int)$blog_id."' AND item_id = '".(int)$post_id."' AND rating_type = 'post'";
        Database::query($sql);

        // Delete the post
        $sql = "DELETE FROM $tbl_blogs_posts
                WHERE c_id = $course_id AND post_id = '".(int)$post_id."'";
        Database::query($sql);

        // Delete the comments
        $sql = "DELETE FROM $tbl_blogs_comments
                WHERE c_id = $course_id AND post_id = '".(int)$post_id."' AND blog_id = '".(int)$blog_id."'";
        Database::query($sql);

        // Delete posts and attachments
        delete_all_blog_attachment($blog_id, $post_id);
    }

    /**
     * Creates a comment on a post in a given blog
     * @author Toon Keppens
     * @param String $title
     * @param String $full_text
     * @param Integer $blog_id
     * @param Integer $post_id
     * @param Integer $parent_id
     */
    public static function create_comment(
        $title,
        $full_text,
        $file_comment,
        $blog_id,
        $post_id,
        $parent_id,
        $task_id = 'NULL'
    ) {
        $_user = api_get_user_info();
        $_course = api_get_course_info();
        $blog_table_attachment 	= Database::get_course_table(TABLE_BLOGS_ATTACHMENT);

        $upload_ok = true;
        $has_attachment = false;
        $current_date = api_get_utc_datetime();
        $course_id = api_get_course_int_id();

        if (!empty($_FILES['user_upload']['name'])) {
            $upload_ok = process_uploaded_file($_FILES['user_upload']);
            $has_attachment=true;
        }

        if ($upload_ok) {
            // Table Definition
            $tbl_blogs_comments = Database::get_course_table(TABLE_BLOGS_COMMENTS);

            // Create the comment
            $sql = "INSERT INTO $tbl_blogs_comments (c_id, title, comment, author_id, date_creation, blog_id, post_id, parent_comment_id, task_id )
                    VALUES ($course_id, '".Database::escape_string($title)."', '".Database::escape_string($full_text)."', '".(int)$_user['user_id']."','".$current_date."', '".(int)$blog_id."', '".(int)$post_id."', '".(int)$parent_id."', '".(int)$task_id."')";
            Database::query($sql);

            // Empty post values, or they are shown on the page again
            $last_id = Database::insert_id();

            if ($last_id) {
                $sql = "UPDATE $tbl_blogs_comments SET comment_id = iid WHERE iid = $last_id";
                Database::query($sql);
            }

            if ($has_attachment) {
                $courseDir   = $_course['path'].'/upload/blog';
                $sys_course_path = api_get_path(SYS_COURSE_PATH);
                $updir = $sys_course_path.$courseDir;

                // Try to add an extension to the file if it hasn't one
                $new_file_name = add_ext_on_mime(
                    stripslashes($_FILES['user_upload']['name']),
                    $_FILES['user_upload']['type']
                );

                // user's file name
                $file_name =$_FILES['user_upload']['name'];

                if (!filter_extension($new_file_name)) {
                    Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
                } else {
                    $new_file_name = uniqid('');
                    $new_path=$updir.'/'.$new_file_name;
                    $result= @move_uploaded_file($_FILES['user_upload']['tmp_name'], $new_path);
                    $comment = Database::escape_string($file_comment);

                    // Storing the attachments if any
                    if ($result) {
                        $sql='INSERT INTO '.$blog_table_attachment.'(c_id, filename,comment, path, post_id,size,blog_id,comment_id) '.
                             "VALUES ($course_id, '".Database::escape_string($file_name)."', '".$comment."', '".Database::escape_string($new_file_name)."' , '".$post_id."', '".$_FILES['user_upload']['size']."',  '".$blog_id."', '".$last_id."'  )";
                        Database::query($sql);

                        $id = Database::insert_id();

                        if ($id) {
                            $sql = "UPDATE $blog_table_attachment SET id = iid WHERE iid = $id";
                            Database::query($sql);
                        }
                    }
                }
            }
        }
    }

    /**
     * Deletes a comment from a blogpost
     * @author Toon Keppens
     * @param int $blog_id
     * @param int $comment_id
     */
    public static function delete_comment($blog_id, $post_id, $comment_id)
    {
        $tbl_blogs_comments = Database::get_course_table(TABLE_BLOGS_COMMENTS);
        $tbl_blogs_rating = Database::get_course_table(TABLE_BLOGS_RATING);
        $blog_id = intval($blog_id);
        $post_id = intval($post_id);
        $comment_id = intval($comment_id);
        $course_id = api_get_course_int_id();

        delete_all_blog_attachment($blog_id, $post_id, $comment_id);

        // Delete ratings on this comment
        $sql = "DELETE FROM $tbl_blogs_rating
                WHERE
                    c_id = $course_id AND
                    blog_id = '".$blog_id."' AND
                    item_id = '".$comment_id."' AND
                    rating_type = 'comment'";
        Database::query($sql);

        // select comments that have the selected comment as their parent
        $sql = "SELECT comment_id FROM $tbl_blogs_comments
                WHERE c_id = $course_id AND parent_comment_id = '".$comment_id."'";
        $result = Database::query($sql);

        // Delete them recursively
        while ($comment = Database::fetch_array($result)) {
            Blog::delete_comment($blog_id, $post_id, $comment['comment_id']);
        }

        // Finally, delete the selected comment to
        $sql = "DELETE FROM $tbl_blogs_comments
                WHERE c_id = $course_id AND comment_id = '".$comment_id."'";
        Database::query($sql);
    }

    /**
     * Creates a new task in a blog
     * @author Toon Keppens
     * @param Integer $blog_id
     * @param String $title
     * @param String $description
     * @param String $color
     */
    public static function create_task($blog_id, $title, $description, $articleDelete, $articleEdit, $commentsDelete, $color)
    {
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
        $tbl_tasks_permissions = Database::get_course_table(TABLE_BLOGS_TASKS_PERMISSIONS);

        $course_id = api_get_course_int_id();

        // Create the task
        $sql = "INSERT INTO $tbl_blogs_tasks (c_id, blog_id, title, description, color, system_task)
                VALUES ($course_id , '".(int)$blog_id."', '" . Database::escape_string($title)."', '" . Database::escape_string($description)."', '" . Database::escape_string($color)."', '0');";
        Database::query($sql);

        $task_id = Database::insert_id();

        if ($task_id) {
            $sql = "UPDATE $tbl_blogs_tasks SET task_id = iid WHERE iid = $task_id";
            Database::query($sql);
        }

        $tool = 'BLOG_' . $blog_id;

        if ($articleDelete == 'on') {
            $sql = " INSERT INTO " . $tbl_tasks_permissions . " ( c_id,  task_id, tool, action) VALUES (
                    '" . (int)$course_id . "',
                    '" . (int)$task_id . "',
                    '" . Database::escape_string($tool) . "',
                    'article_delete'
                )";
            Database::query($sql);

            $id = Database::insert_id();

            if ($id) {
                $sql = "UPDATE $tbl_tasks_permissions SET id = iid WHERE iid = $id";
                Database::query($sql);
            }
        }

        if ($articleEdit == 'on') {
            $sql = "
                INSERT INTO " . $tbl_tasks_permissions . " (c_id, task_id, tool, action ) VALUES (
                    '" . (int)$course_id . "',
                    '" . (int)$task_id . "',
                    '" . Database::escape_string($tool) . "',
                    'article_edit'
                )";
            Database::query($sql);
            $id = Database::insert_id();

            if ($id) {
                $sql = "UPDATE $tbl_tasks_permissions SET id = iid WHERE iid = $id";
                Database::query($sql);
            }
        }

        if ($commentsDelete == 'on') {
            $sql = "
                INSERT INTO " . $tbl_tasks_permissions . " (c_id, task_id, tool, action ) VALUES (
                    '" . (int)$course_id . "',
                    '" . (int)$task_id . "',
                    '" . Database::escape_string($tool) . "',
                    'article_comments_delete'
                )";
            Database::query($sql);
            $id = Database::insert_id();

            if ($id) {
                $sql = "UPDATE $tbl_tasks_permissions SET id = iid WHERE iid = $id";
                Database::query($sql);
            }
        }
    }

    /**
     * Edit a task in a blog
     * @author Toon Keppens
     * @param Integer $task_id
     * @param String $title
     * @param String $description
     * @param String $color
     */
    public static function edit_task(
        $blog_id,
        $task_id,
        $title,
        $description,
        $articleDelete,
        $articleEdit,
        $commentsDelete,
        $color
    ) {
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
        $tbl_tasks_permissions = Database::get_course_table(TABLE_BLOGS_TASKS_PERMISSIONS);

        $course_id = api_get_course_int_id();

        // Create the task
        $sql = "UPDATE $tbl_blogs_tasks SET
                    title = '".Database::escape_string($title)."',
                    description = '".Database::escape_string($description)."',
                    color = '".Database::escape_string($color)."'
                WHERE c_id = $course_id AND task_id ='".(int)$task_id."' LIMIT 1";
        Database::query($sql);

        $tool = 'BLOG_' . $blog_id;

        $sql = "DELETE FROM " . $tbl_tasks_permissions . "
                WHERE c_id = $course_id AND task_id = '" . (int)$task_id."'";
        Database::query($sql);

        if ($articleDelete == 'on') {
            $sql = "INSERT INTO " . $tbl_tasks_permissions . " ( c_id, task_id, tool, action) VALUES (
                    '" . (int)$course_id . "',
                    '" . (int)$task_id . "',
                    '" . Database::escape_string($tool) . "',
                    'article_delete'
                )";
            Database::query($sql);
            $id = Database::insert_id();

            if ($id) {
                $sql = "UPDATE $tbl_tasks_permissions SET id = iid WHERE iid = $id";
                Database::query($sql);
            }
        }

        if ($articleEdit == 'on') {
            $sql = "INSERT INTO " . $tbl_tasks_permissions . " (c_id, task_id, tool, action) VALUES (
                    '" . (int)$course_id . "',
                    '" . (int)$task_id . "',
                    '" . Database::escape_string($tool) . "',
                    'article_edit'
                )";
            Database::query($sql);
            $id = Database::insert_id();

            if ($id) {
                $sql = "UPDATE $tbl_tasks_permissions SET id = iid WHERE iid = $id";
                Database::query($sql);
            }
        }

        if ($commentsDelete == 'on') {
            $sql = " INSERT INTO " . $tbl_tasks_permissions . " (c_id, task_id, tool, action) VALUES (
                    '" . (int)$course_id . "',
                    '" . (int)$task_id . "',
                    '" . Database::escape_string($tool) . "',
                    'article_comments_delete'
                )";
            Database::query($sql);
            $id = Database::insert_id();

            if ($id) {
                $sql = "UPDATE $tbl_tasks_permissions SET id = iid WHERE iid = $id";
                Database::query($sql);
            }
        }
    }

    /**
     * Deletes a task from a blog
     * @param Integer $blog_id
     * @param Integer $task_id
     */
    public static function delete_task($blog_id, $task_id)
    {
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
        $course_id = api_get_course_int_id();

        // Delete posts
        $sql = "DELETE FROM $tbl_blogs_tasks
                WHERE c_id = $course_id AND blog_id = '".(int)$blog_id."' AND task_id = '".(int)$task_id."'";
        Database::query($sql);
    }

    /**
     * Deletes an assigned task from a blog
     * @param Integer $blog_id
     */
    public static function delete_assigned_task($blog_id, $task_id, $user_id)
    {
        $tbl_blogs_tasks_rel_user = Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER);
        $course_id = api_get_course_int_id();

        // Delete posts
        $sql = "DELETE FROM $tbl_blogs_tasks_rel_user
                WHERE
                    c_id = $course_id AND
                    blog_id = '".(int)$blog_id."' AND
                    task_id = '".(int)$task_id."' AND
                    user_id = '".(int)$user_id."'";
        Database::query($sql);
    }

    /**
     * Get personal task list
     * @author Toon Keppens
     * @return Returns an unsorted list (<ul></ul>) with the users' tasks
     */
    public static function get_personal_task_list()
    {
        $_user = api_get_user_info();

        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);
        $tbl_blogs_tasks_rel_user 	= Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER);
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);

        $course_id = api_get_course_int_id();

        if ($_user['user_id']) {
            $sql = "SELECT task_rel_user.*, task.title, blog.blog_name
                    FROM $tbl_blogs_tasks_rel_user task_rel_user
                    INNER JOIN $tbl_blogs_tasks task
                    ON task_rel_user.task_id = task.task_id
                    INNER JOIN $tbl_blogs blog
                    ON task_rel_user.blog_id = blog.blog_id
                    AND blog.blog_id = ".intval($_GET['blog_id'])."
                    WHERE
                        task.c_id = $course_id AND
                        blog.c_id = $course_id AND
                        task_rel_user.c_id = $course_id AND
                        task_rel_user.user_id = ".(int)$_user['user_id']."
                    ORDER BY target_date ASC";

            $result = Database::query($sql);

            if (Database::num_rows($result) > 0) {
                echo '<ul>';
                while ($mytask = Database::fetch_array($result)) {
                    echo '<li><a href="blog.php?action=execute_task&blog_id=' . $mytask['blog_id'] . '&task_id='.stripslashes($mytask['task_id']) . '" title="[Blog: '.stripslashes($mytask['blog_name']) . '] ' . get_lang('ExecuteThisTask') . '">'.stripslashes($mytask['title']) . '</a></li>';
                }
                echo '<ul>';
            } else {
                echo get_lang('NoTasks');
            }
        } else {
            echo get_lang('NoTasks');
        }
    }

    /**
     * Changes the visibility of a blog
     * @author Toon Keppens
     * @param Integer $blog_id
     */
    public static function change_blog_visibility($blog_id)
    {
        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);
        $tbl_tool = Database::get_course_table(TABLE_TOOL_LIST);
        $course_id = api_get_course_int_id();

        // Get blog properties
        $sql = "SELECT blog_name, visibility FROM $tbl_blogs
                WHERE c_id = $course_id AND blog_id='".(int)$blog_id."'";
        $result = Database::query($sql);
        $blog = Database::fetch_array($result);
        $visibility = $blog['visibility'];
        $title = $blog['blog_name'];

        if ($visibility == 1) {
            // Change visibility state, remove from course home.
            $sql = "UPDATE $tbl_blogs SET visibility = '0'
                    WHERE c_id = $course_id AND blog_id ='".(int)$blog_id."' LIMIT 1";
            Database::query($sql);

            $sql = "DELETE FROM $tbl_tool
                    WHERE c_id = $course_id AND name = '".Database::escape_string($title)."' LIMIT 1";
            Database::query($sql);
        } else {
            // Change visibility state, add to course home.
            $sql = "UPDATE $tbl_blogs SET visibility = '1'
                    WHERE c_id = $course_id AND blog_id ='".(int)$blog_id."' LIMIT 1";
            Database::query($sql);

            $sql = "INSERT INTO $tbl_tool (c_id, name, link, image, visibility, admin, address, added_tool, target )
                    VALUES ($course_id, '".Database::escape_string($title)."', 'blog/blog.php?blog_id=".(int)$blog_id."', 'blog.gif', '1', '0', 'pastillegris.gif', '0', '_self')";
            Database::query($sql);
            $id = Database::insert_id();

            if ($id) {
                $sql = "UPDATE $tbl_tool SET id = iid WHERE iid = $id";
                Database::query($sql);
            }
        }
    }

    /**
     * Shows the posts of a blog
     * @author Toon Keppens
     *
     * @param Integer $blog_id
     */
    public static function display_blog_posts($blog_id, $filter = '1=1', $max_number_of_posts = 20)
    {
        // Init
        $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);
        $tbl_blogs_comments = Database::get_course_table(TABLE_BLOGS_COMMENTS);
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);

        $course_id = api_get_course_int_id();

        // Get posts and authors
        $sql = "SELECT post.*, user.lastname, user.firstname, user.username
                FROM $tbl_blogs_posts post
                INNER JOIN $tbl_users user
                ON post.author_id = user.user_id
                WHERE 	post.blog_id = '".(int)$blog_id."' AND
                        post.c_id = $course_id AND
                        $filter
                ORDER BY post_id DESC LIMIT 0,".(int)$max_number_of_posts;
        $result = Database::query($sql);

        // Display
        if(Database::num_rows($result) > 0) {
            $limit = 200;
            while ($blog_post = Database::fetch_array($result)) {
                // Get number of comments
                $sql = "SELECT COUNT(1) as number_of_comments
                        FROM $tbl_blogs_comments
                        WHERE
                            c_id = $course_id AND
                            blog_id = '".(int)$blog_id."' AND
                            post_id = '" . (int)$blog_post['post_id']."'";
                $tmp = Database::query($sql);
                $blog_post_comments = Database::fetch_array($tmp);

                // Prepare data
                $blog_post_id = $blog_post['post_id'];
                $blog_post_text = make_clickable(stripslashes($blog_post['full_text']));
                $blog_post_date = api_convert_and_format_date($blog_post['date_creation'], null, date_default_timezone_get());

                // Create an introduction text (but keep FULL sentences)
                $words = 0;
                $blog_post_text_cut = cut($blog_post_text, $limit) ;
                $words = strlen($blog_post_text);

                if ($words >= $limit) {
                    $readMoreLink = ' <div class="link" onclick="document.getElementById(\'blogpost_text_' . $blog_post_id . '\').style.display=\'block\'; document.getElementById(\'blogpost_introduction_' . $blog_post_id . '\').style.display=\'none\'">' . get_lang('ReadMore') . '</div>';
                    $introduction_text = $blog_post_text_cut;
                } else {
                    $introduction_text = $blog_post_text;
                    $readMoreLink = '';
                }

                $introduction_text = stripslashes($introduction_text);

                echo '<div class="blogpost">';
                echo '<span class="blogpost_title"><a href="blog.php?action=view_post&blog_id=' . $blog_id . '&post_id=' . $blog_post['post_id'] . '#add_comment" title="' . get_lang('ReadPost') . '" >'.stripslashes($blog_post['title']) . '</a></span>';
                echo '<span class="blogpost_date"><a href="blog.php?action=view_post&blog_id=' . $blog_id . '&post_id=' . $blog_post['post_id'] . '#add_comment" title="' . get_lang('ReadPost') . '" >' . $blog_post_date . '</a></span>';
                echo '<div class="blogpost_introduction" id="blogpost_introduction_'.$blog_post_id.'">' . $introduction_text.$readMoreLink.'</div>';
                echo '<div class="blogpost_text" id="blogpost_text_' . $blog_post_id . '" style="display: none">' . $blog_post_text . '</div>';

                $file_name_array = get_blog_attachment($blog_id,$blog_post_id,0);

                if (!empty($file_name_array)) {
                    echo '<br /><br />';
                    echo Display::return_icon('attachment.gif',get_lang('Attachment'));
                    echo '<a href="download.php?file=';
                    echo $file_name_array['path'];
                    echo ' "> '.$file_name_array['filename'].' </a><br />';
                    echo '</span>';
                }
                $username = api_htmlentities(sprintf(get_lang('LoginX'), $blog_post['username']), ENT_QUOTES);
                echo '<span class="blogpost_info">' . get_lang('Author') . ': ' . Display::tag('span', api_get_person_name($blog_post['firstname'], $blog_post['lastname']), array('title'=>$username)) .' - <a href="blog.php?action=view_post&blog_id=' . $blog_id . '&post_id=' . $blog_post['post_id'] . '#add_comment" title="' . get_lang('ReadPost') . '" >' . get_lang('Comments') . ': ' . $blog_post_comments['number_of_comments'] . '</a></span>';
                echo '</div>';
            }
        } else {
            if($filter == '1=1') {
                echo get_lang('NoArticles');
            } else {
                echo get_lang('NoArticleMatches');
            }
        }
    }

    /**
     * Display the search results
     *
     * @param Integer $blog_id
     * @param String $query_string
     */
    public static function display_search_results ($blog_id, $query_string)
    {
        // Init
        $query_string = Database::escape_string($query_string);
        $query_string_parts = explode(' ',$query_string);
        $query_string = array();
        foreach ($query_string_parts as $query_part) {
            $query_string[] = " full_text LIKE '%" . $query_part."%' OR title LIKE '%" . $query_part."%' ";
        }
        $query_string = '('.implode('OR',$query_string) . ')';

        // Display the posts
        echo '<span class="blogpost_title">' . get_lang('SearchResults') . '</span>';
        Blog::display_blog_posts($blog_id, $query_string);
    }

    /**
     * Display posts from a certain date
     *
     * @param Integer $blog_id
     * @param String $query_string
     */
    public static function display_day_results($blog_id, $query_string)
    {
        $date_output = $query_string;
        $date = explode('-',$query_string);
        $query_string = ' DAYOFMONTH(date_creation) =' . intval($date[2]) . ' AND MONTH(date_creation) =' . intval($date[1]) . ' AND YEAR(date_creation) =' . intval($date[0]);

        // Put date in correct output format
        $date_output = api_format_date($date_output, DATE_FORMAT_LONG);

        // Display the posts
        echo '<span class="blogpost_title">' . get_lang('PostsOf') . ': ' . $date_output . '</span>';
        Blog::display_blog_posts($blog_id, $query_string);
    }

    /**
     * Displays a post and his comments
     *
     * @param Integer $blog_id
     * @param Integer $post_id
     */
    public static function display_post($blog_id, $post_id)
    {
        $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);
        $tbl_blogs_comments = Database::get_course_table(TABLE_BLOGS_COMMENTS);
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);

        global $charset, $dateFormatLong;

        $course_id = api_get_course_int_id();

        // Get posts and author
        $sql = "SELECT post.*, user.lastname, user.firstname, user.username
                FROM $tbl_blogs_posts post
                    INNER JOIN $tbl_users user
                    ON post.author_id = user.user_id
                WHERE
                    post.c_id = $course_id AND
                    post.blog_id = '".(int)$blog_id."' AND
                    post.post_id = '".(int)$post_id."'
                ORDER BY post_id DESC";
        $result = Database::query($sql);
        $blog_post = Database::fetch_array($result);

        // Get number of comments
        $sql = "SELECT COUNT(1) as number_of_comments
                FROM $tbl_blogs_comments
                WHERE c_id = $course_id AND blog_id = '".(int)$blog_id."' AND post_id = '".(int)$post_id."'";
        $result = Database::query($sql);
        $blog_post_comments = Database::fetch_array($result);

        // Prepare data
        $blog_post_text = make_clickable(stripslashes($blog_post['full_text']));
        $blog_post_date = api_convert_and_format_date($blog_post['date_creation'], null, date_default_timezone_get());
        $blog_post_actions = "";

        $task_id = (isset($_GET['task_id']) && is_numeric($_GET['task_id'])) ? intval($_GET['task_id']) : 0;

        if (api_is_allowed('BLOG_' . $blog_id, 'article_edit', $task_id)) {
            $blog_post_actions .= '<a href="blog.php?action=edit_post&blog_id=' . $blog_id . '&post_id=' . $post_id . '&article_id=' . $blog_post['post_id'] . '&task_id=' . $task_id . '" title="' . get_lang('EditThisPost') . '">';
            $blog_post_actions .=  Display::return_icon('edit.png');
            $blog_post_actions .= '</a>';
        }

        if (api_is_allowed('BLOG_' . $blog_id, 'article_delete', $task_id)) {
            $blog_post_actions .= '<a href="blog.php?action=view_post&blog_id=' . $blog_id . '&post_id=' . $post_id . '&do=delete_article&article_id=' . $blog_post['post_id'] . '&task_id=' . $task_id . '" title="' . get_lang('DeleteThisArticle') . '" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset)). '\')) return false;">';
            $blog_post_actions .= Display::return_icon('delete.png');
            $blog_post_actions .= '</a>';
        }

        if (api_is_allowed('BLOG_' . $blog_id, 'article_rate'))
            $rating_select = Blog::display_rating_form('post',$blog_id,$post_id);

        $blog_post_text=stripslashes($blog_post_text);

        // Display post
        echo '<div class="blogpost">';
        echo '<span class="blogpost_title"><a href="blog.php?action=view_post&blog_id=' . $blog_id . '&post_id=' . $blog_post['post_id'] . '" title="' . get_lang('ReadPost') . '" >'.stripslashes($blog_post['title']) . '</a></span>';
        echo '<span class="blogpost_date">' . $blog_post_date . '</span>';
        echo '<span class="blogpost_text">' . $blog_post_text . '</span><br />';

        $file_name_array = get_blog_attachment($blog_id, $post_id);

        if (!empty($file_name_array)) {
            echo ' <br />';
            echo Display::return_icon('attachment.gif',get_lang('Attachment'));
            echo '<a href="download.php?file=';
            echo $file_name_array['path'];
            echo ' "> '.$file_name_array['filename'].' </a>';
            echo '</span>';
            echo '<span class="attachment_comment">';
            echo $file_name_array['comment'];
            echo '</span>';
            echo '<br />';
        }
        $username = api_htmlentities(sprintf(get_lang('LoginX'), $blog_post['username']), ENT_QUOTES);
        echo '<span class="blogpost_info">'.get_lang('Author').': ' .Display::tag('span', api_get_person_name($blog_post['firstname'], $blog_post['lastname']), array('title'=>$username)).' - '.get_lang('Comments').': '.$blog_post_comments['number_of_comments'].' - '.get_lang('Rating').': '.Blog::display_rating('post',$blog_id,$post_id).$rating_select.'</span>';
        echo '<span class="blogpost_actions">' . $blog_post_actions . '</span>';
        echo '</div>';

        // Display comments if there are any
        if($blog_post_comments['number_of_comments'] > 0) {
            echo '<div class="comments">';
                echo '<span class="blogpost_title">' . get_lang('Comments') . '</span><br />';
                Blog::get_threaded_comments(0, 0, $blog_id, $post_id, $task_id);
            echo '</div>';
        }

        // Display comment form
        if (api_is_allowed('BLOG_' . $blog_id, 'article_comments_add')) {
            Blog::display_new_comment_form($blog_id, $post_id, $blog_post['title']);
        }
    }

    /**
     * Adds rating to a certain post or comment
     * @author Toon Keppens
     *
     * @param String $type
     * @param Integer $blog_id
     * @param Integer $item_id
     * @param Integer $rating
     *
     * @return Boolean success
     */
    public static function add_rating($type, $blog_id, $item_id, $rating)
    {
        $_user = api_get_user_info();

        // Init
        $tbl_blogs_rating = Database::get_course_table(TABLE_BLOGS_RATING);
        $course_id = api_get_course_int_id();

        // Check if the user has already rated this post/comment
        $sql = "SELECT rating_id FROM $tbl_blogs_rating
                WHERE
                    c_id = $course_id AND
                    blog_id = '".(int)$blog_id."' AND
                    item_id = '".(int)$item_id."' AND
                    rating_type = '".Database::escape_string($type)."' AND
                    user_id = '".(int)$_user['user_id']."'";
        $result = Database::query($sql);

        // Add rating
        if (Database::num_rows($result) == 0) {
            $sql = "INSERT INTO $tbl_blogs_rating (c_id, blog_id, rating_type, item_id, user_id, rating )
                    VALUES ($course_id, '".(int)$blog_id."', '".Database::escape_string($type)."', '".(int)$item_id."', '".(int)$_user['user_id']."', '".Database::escape_string($rating)."')";
            Database::query($sql);

            $id = Database::insert_id();
            if ($id) {
                $sql = "UPDATE $tbl_blogs_rating SET rating_id = iid WHERE iid = $id";
                Database::query($sql);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Shows the rating of user
     * @param String $type
     * @param Integer $blog_id
     * @param Integer $item_id
     * @return array
     */
    public static function display_rating($type, $blog_id, $item_id)
    {
        $tbl_blogs_rating = Database::get_course_table(TABLE_BLOGS_RATING);
        $course_id = api_get_course_int_id();

        // Calculate rating
        $sql = "SELECT AVG(rating) as rating FROM $tbl_blogs_rating
                WHERE
                    c_id = $course_id AND
                    blog_id = '".(int)$blog_id."' AND
                    item_id = '".(int)$item_id."' AND
                    rating_type = '".Database::escape_string($type)."' ";
        $result = Database::query($sql);
        $result = Database::fetch_array($result);
        return round($result['rating'], 2);
    }

    /**
     * Shows the rating form if not already rated by that user
     * @author Toon Keppens
     *
     * @param String $type
     * @param Integer $blog_id
     * @param integer $post_id
     */
    public static function display_rating_form ($type, $blog_id, $post_id, $comment_id = NULL)
    {
        $_user = api_get_user_info();
        $tbl_blogs_rating = Database::get_course_table(TABLE_BLOGS_RATING);
        $course_id = api_get_course_int_id();

        if ($type == 'post') {
            // Check if the user has already rated this post
            $sql = "SELECT rating_id FROM $tbl_blogs_rating
                    WHERE c_id = $course_id AND
                    blog_id = '".(int)$blog_id."'
                    AND item_id = '".(int)$post_id."'
                    AND rating_type = '".Database::escape_string($type)."'
                    AND user_id = '".(int)$_user['user_id']."'";
            $result = Database::query($sql);
            // Add rating
            if (Database::num_rows($result) == 0) {
                return ' - ' . get_lang('RateThis') . ': <form method="get" action="blog.php" style="display: inline" id="frm_rating_' . $type . '_' . $post_id . '" name="frm_rating_' . $type . '_' . $post_id . '"><select name="rating" onchange="document.forms[\'frm_rating_' . $type . '_' . $post_id . '\'].submit()"><option value="">-</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option></select><input type="hidden" name="action" value="view_post" /><input type="hidden" name="type" value="' . $type . '" /><input type="hidden" name="do" value="rate" /><input type="hidden" name="blog_id" value="' . $blog_id . '" /><input type="hidden" name="post_id" value="' . $post_id . '" /></form>';
            } else {
                return '';
            }
        }

        if ($type = 'comment') {
            // Check if the user has already rated this comment
            $sql = "SELECT rating_id FROM $tbl_blogs_rating
                    WHERE c_id = $course_id AND blog_id = '".(int)$blog_id ."'
                    AND item_id = '".(int)$comment_id."'
                    AND rating_type = '".Database::escape_string($type)."'
                    AND user_id = '".(int)$_user['user_id']."'";
            $result = Database::query($sql);

            if (Database::num_rows($result) == 0) {
                return ' - ' . get_lang('RateThis') . ': <form method="get" action="blog.php" style="display: inline" id="frm_rating_' . $type . '_' . $comment_id . '" name="frm_rating_' . $type . '_' . $comment_id . '"><select name="rating" onchange="document.forms[\'frm_rating_' . $type . '_' . $comment_id . '\'].submit()"><option value="">-</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option></select><input type="hidden" name="action" value="view_post" /><input type="hidden" name="type" value="' . $type . '" /><input type="hidden" name="do" value="rate" /><input type="hidden" name="blog_id" value="' . $blog_id . '" /><input type="hidden" name="post_id" value="' . $post_id . '" /><input type="hidden" name="comment_id" value="' . $comment_id . '" /></form>';
            } else {
                return '';
            }
        }
    }

    /**
     * This functions gets all replys to a post, threaded.
     *
     * @param Integer $current
     * @param Integer $current_level
     * @param Integer $blog_id
     * @param Integer $post_id
     */
    public static function get_threaded_comments($current = 0, $current_level = 0, $blog_id, $post_id, $task_id = 0)
    {
        $tbl_blogs_comments = Database::get_course_table(TABLE_BLOGS_COMMENTS);
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
        global $charset;

        $course_id = api_get_course_int_id();

        // Select top level comments
        $next_level = $current_level + 1;
        $sql = "SELECT comments.*, user.lastname, user.firstname, user.username, task.color
                FROM $tbl_blogs_comments comments
                INNER JOIN $tbl_users user
                ON comments.author_id = user.user_id
                LEFT JOIN $tbl_blogs_tasks task
                ON comments.task_id = task.task_id AND task.c_id = $course_id
                WHERE
                    comments.c_id = $course_id AND
                    parent_comment_id = $current AND
                    comments.blog_id = '".(int)$blog_id."' AND
                    comments.post_id = '".(int)$post_id."'";
        $result = Database::query($sql);

        while($comment = Database::fetch_array($result)) {
            // Select the children recursivly
            $tmp = "SELECT comments.*, user.lastname, user.firstname, user.username
                    FROM $tbl_blogs_comments comments
                    INNER JOIN $tbl_users user
                    ON comments.author_id = user.user_id
                    WHERE
                        comments.c_id = $course_id AND
                        comment_id = $current
                        AND blog_id = '".(int)$blog_id."'
                        AND post_id = '".(int)$post_id."'";
            $tmp = Database::query($tmp);
            $tmp = Database::fetch_array($tmp);
            $parent_cat = $tmp['parent_comment_id'];
            $border_color = '';

            // Prepare data
            $comment_text = make_clickable(stripslashes($comment['comment']));
            $blog_comment_date = api_convert_and_format_date($comment['date_creation'], null, date_default_timezone_get());
            $blog_comment_actions = "";
            if (api_is_allowed('BLOG_'.$blog_id, 'article_comments_delete', $task_id)) {
                $blog_comment_actions .= '<a href="blog.php?action=view_post&blog_id='.$blog_id.'&post_id='.$post_id.'&do=delete_comment&comment_id='.$comment['comment_id'].'&task_id='.$task_id.'" title="'.get_lang(
                        'DeleteThisComment'
                    ).'" onclick="javascript:if(!confirm(\''.addslashes(
                        api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES, $charset)
                    ).'\')) return false;">';
                $blog_comment_actions .= Display::return_icon('delete.png');
                $blog_comment_actions .= '</a>';
            }

            if (api_is_allowed('BLOG_'.$blog_id, 'article_comments_rate')) {
                $rating_select = Blog::display_rating_form('comment', $blog_id, $post_id, $comment['comment_id']);
            }

            if (!is_null($comment['task_id'])) {
                $border_color = ' border-left: 3px solid #' . $comment['color'];
            }

            $comment_text = stripslashes($comment_text);

            // Output...
            $margin = $current_level * 30;
            echo '<div class="blogpost_comment" style="margin-left: ' . $margin . 'px;' . $border_color . '">';
                echo '<span class="blogpost_comment_title"><a href="#add_comment" onclick="document.getElementById(\'comment_parent_id\').value=\'' . $comment['comment_id'] . '\'; document.getElementById(\'comment_title\').value=\'Re: '.addslashes($comment['title']) . '\'" title="' . get_lang('ReplyToThisComment') . '" >'.stripslashes($comment['title']) . '</a></span>';
                echo '<span class="blogpost_comment_date">' . $blog_comment_date . '</span>';
                echo '<span class="blogpost_text">' . $comment_text . '</span>';

                $file_name_array = get_blog_attachment($blog_id,$post_id, $comment['comment_id']);
                if (!empty($file_name_array)) {
                    echo '<br /><br />';
                    echo Display::return_icon('attachment.gif',get_lang('Attachment'));
                    echo '<a href="download.php?file=';
                    echo $file_name_array['path'];
                    echo ' "> '.$file_name_array['filename'].' </a>';
                    echo '<span class="attachment_comment">';
                    echo $file_name_array['comment'];
                    echo '</span><br />';
                }
                $username = api_htmlentities(sprintf(get_lang('LoginX'), $comment['username']), ENT_QUOTES);
                echo '<span class="blogpost_comment_info">'.get_lang('Author').': '.Display::tag('span', api_get_person_name($comment['firstname'], $comment['lastname']), array('title'=>$username)).' - '.get_lang('Rating').': '.Blog::display_rating('comment', $blog_id, $comment['comment_id']).$rating_select.'</span>';
                echo '<span class="blogpost_actions">' . $blog_comment_actions . '</span>';
            echo '</div>';

            // Go further down the tree.
            Blog::get_threaded_comments($comment['comment_id'], $next_level, $blog_id, $post_id);
        }
    }

    /**
     * Displays the form to create a new post
     * @author Toon Keppens
     *
     * @param Integer $blog_id
     */
    public static function display_form_new_post($blog_id)
    {
        if (api_is_allowed('BLOG_' . $blog_id, 'article_add')) {
            $form = new FormValidator(
                'add_post',
                'post',
                api_get_path(WEB_CODE_PATH)."blog/blog.php?action=new_post&blog_id=" . $blog_id . "&" . api_get_cidreq(),
                null,
                array('enctype' => 'multipart/form-data')
            );
            $form->addHidden('post_title_edited', 'false');
            $form->addHeader(get_lang('NewPost'));
            $form->addText('title', get_lang('Title'));
            $config = array();
            if (!api_is_allowed_to_edit()) {
                $config['ToolbarSet'] = 'ProjectStudent';
            } else {
                $config['ToolbarSet'] = 'Project';
            }
            $form->addHtmlEditor('full_text', get_lang('Content'), false, false, $config);
            $form->addFile('user_upload', get_lang('AddAnAttachment'));
            $form->addTextarea('post_file_comment', get_lang('FileComment'));
            $form->addHidden('new_post_submit', 'true');
            $form->addButton('save', get_lang('Save'));

            $form->display();
        } else {
            api_not_allowed();
        }
    }

    /**
     * Displays the form to edit a post
     * @author Toon Keppens
     *
     * @param Integer $blog_id
     */
    public static function display_form_edit_post($blog_id, $post_id)
    {
        $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);

        $course_id = api_get_course_int_id();

        // Get posts and author
        $sql = "SELECT post.*, user.lastname, user.firstname
                FROM $tbl_blogs_posts post
                INNER JOIN $tbl_users user ON post.author_id = user.user_id
                WHERE
                post.c_id 			= $course_id AND
                post.blog_id 		= '".(int)$blog_id ."'
                AND post.post_id	= '".(int)$post_id."'
                ORDER BY post_id DESC";
        $result = Database::query($sql);
        $blog_post = Database::fetch_array($result);

        // Form
        $form = new FormValidator(
            'edit_post',
            'post',
            api_get_path(WEB_CODE_PATH).'blog/blog.php?action=edit_post&post_id=' . intval($_GET['post_id']) . '&blog_id=' . intval($blog_id) . '&article_id='.intval($_GET['article_id']).'&task_id='.intval($_GET['task_id'])
        );

        $form->addHeader(get_lang('EditPost'));
        $form->addText('title', get_lang('Title'));

        if (!api_is_allowed_to_edit()) {
            $config['ToolbarSet'] = 'ProjectStudent';
        } else {
            $config['ToolbarSet'] = 'Project';
        }
        $form->addHtmlEditor('full_text', get_lang('Content'), false, false, $config);

        $form->addHidden('action', '');
        $form->addHidden('edit_post_submit', 'true');
        $form->addHidden('post_id', intval($_GET['post_id']));
        $form->addButton('save', get_lang('Save'));
        $form->setDefaults($blog_post);
        $form->display();
    }

    /**
     * Displays a list of tasks in this blog
     * @author Toon Keppens
     *
     * @param Integer $blog_id
     */
    public static function display_task_list($blog_id)
    {
        global $charset;
        $course_id = api_get_course_int_id();

        if (api_is_allowed('BLOG_' . $blog_id, 'article_add')) {
            $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
            $counter = 0;
            global $color2;

            echo '<div class="actions">';
            echo '<a href="' .api_get_self(). '?action=manage_tasks&blog_id=' . $blog_id . '&do=add">';
            echo Display::return_icon('blog_newtasks.gif', get_lang('AddTasks'));
            echo get_lang('AddTasks') . '</a> ';
            echo '<a href="' .api_get_self(). '?action=manage_tasks&blog_id=' . $blog_id . '&do=assign">';
            echo Display::return_icon('blog_task.gif', get_lang('AssignTasks'));
            echo get_lang('AssignTasks') . '</a>';
            ?>
                <a href="<?php echo api_get_self(); ?>?action=manage_rights&blog_id=<?php echo $blog_id ?>" title="<?php echo get_lang('ManageRights') ?>">
                    <?php echo Display::return_icon('blog_admin_users.png', get_lang('RightsManager'),'',ICON_SIZE_SMALL). get_lang('RightsManager') ?></a>
            <?php
            echo '</div>';

            echo '<span class="blogpost_title">' . get_lang('TaskList') . '</span><br />';
            echo "<table class=\"data_table\">";
            echo	"<tr bgcolor=\"$color2\" align=\"center\" valign=\"top\">",
                     "<th width='240'><b>",get_lang('Title'),"</b></th>",
                     "<th><b>",get_lang('Description'),"</b></th>",
                     "<th><b>",get_lang('Color'),"</b></th>",
                     "<th width='50'><b>",get_lang('Modify'),"</b></th>",
                "</tr>";


            $sql = " SELECT
                        blog_id,
                        task_id,
                        blog_id,
                        title,
                        description,
                        color,
                        system_task
                    FROM " . $tbl_blogs_tasks . "
                    WHERE c_id = $course_id AND blog_id = " . (int)$blog_id . "
                    ORDER BY system_task, title";
            $result = Database::query($sql);

            while ($task = Database::fetch_array($result)) {
                $counter++;
                $css_class = (($counter % 2) == 0) ? "row_odd" : "row_even";
                $delete_icon = ($task['system_task'] == '1') ? "delete_na.png" : "delete.png";
                $delete_title = ($task['system_task'] == '1') ? get_lang('DeleteSystemTask') : get_lang('DeleteTask');
                $delete_link = ($task['system_task'] == '1') ? '#' : api_get_self() . '?action=manage_tasks&blog_id=' . $task['blog_id'] . '&do=delete&task_id=' . $task['task_id'];
                $delete_confirm = ($task['system_task'] == '1') ? '' : 'onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset)). '\')) return false;"';

                echo '<tr class="' . $css_class . '" valign="top">';
                echo '<td width="240">'.Security::remove_XSS($task['title']).'</td>';
                echo '<td>'.Security::remove_XSS($task['description']).'</td>';
                echo '<td><span style="background-color: #'.$task['color'].'">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></td>';
                echo '<td width="50">';
                echo '<a href="'.api_get_self().'?action=manage_tasks&blog_id='.$task['blog_id'].'&do=edit&task_id='.$task['task_id'].'">';
                echo Display::return_icon('edit.png', get_lang('EditTask'));
                      echo "</a>";
                      echo '<a href="'.$delete_link.'"';
                      echo $delete_confirm;
                       echo '>';
                        echo Display::return_icon($delete_icon, $delete_title);
                       echo "</a>";
                     echo '</td>';
                   echo '</tr>';
            }
            echo "</table>";
        }
    }

    /**
     * Displays a list of tasks assigned to a user in this blog
     * @author Toon Keppens
     *
     * @param Integer $blog_id
     */
    public static function display_assigned_task_list ($blog_id)
    {
        // Init
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
        $tbl_blogs_tasks_rel_user = Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER);
        $counter = 0;
        global $charset,$color2;

        echo '<span class="blogpost_title">' . get_lang('AssignedTasks') . '</span><br />';
        echo "<table class=\"data_table\">";
        echo	"<tr bgcolor=\"$color2\" align=\"center\" valign=\"top\">",
                 "<th width='240'><b>",get_lang('Member'),"</b></th>",
                 "<th><b>",get_lang('Task'),"</b></th>",
                 "<th><b>",get_lang('Description'),"</b></th>",
                 "<th><b>",get_lang('TargetDate'),"</b></th>",
                 "<th width='50'><b>",get_lang('Modify'),"</b></th>",
            "</tr>";

        $course_id = api_get_course_int_id();

        $sql = "SELECT task_rel_user.*, task.title, user.firstname, user.lastname, user.username, task.description, task.system_task, task.blog_id, task.task_id
                FROM $tbl_blogs_tasks_rel_user task_rel_user
                INNER JOIN $tbl_blogs_tasks task ON task_rel_user.task_id = task.task_id
                INNER JOIN $tbl_users user ON task_rel_user.user_id = user.user_id
                WHERE
                    task_rel_user.c_id = $course_id AND
                    task.c_id = $course_id AND
                    task_rel_user.blog_id = '".(int)$blog_id."'
                ORDER BY target_date ASC";
        $result = Database::query($sql);

        while ($assignment = Database::fetch_array($result)) {
            $counter++;
            $css_class = (($counter % 2)==0) ? "row_odd" : "row_even";
            $delete_icon = ($assignment['system_task'] == '1') ? "delete_na.png" : "delete.png";
            $delete_title = ($assignment['system_task'] == '1') ? get_lang('DeleteSystemTask') : get_lang('DeleteTask');
            $delete_link = ($assignment['system_task'] == '1') ? '#' : api_get_self() . '?action=manage_tasks&blog_id=' . $assignment['blog_id'] . '&do=delete&task_id=' . $assignment['task_id'];
            $delete_confirm = ($assignment['system_task'] == '1') ? '' : 'onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset)). '\')) return false;"';

            $username = api_htmlentities(sprintf(get_lang('LoginX'), $assignment['username']), ENT_QUOTES);

            echo '<tr class="'.$css_class.'" valign="top">';
            echo '<td width="240">'.Display::tag(
                    'span',
                    api_get_person_name($assignment['firstname'], $assignment['lastname']),
                    array('title' => $username)
                ).'</td>';
            echo '<td>'.stripslashes($assignment['title']).'</td>';
            echo '<td>'.stripslashes($assignment['description']).'</td>';
            echo '<td>'.$assignment['target_date'].'</td>';
            echo '<td width="50">';
            echo '<a href="'.api_get_self().'?action=manage_tasks&blog_id='.$assignment['blog_id'].'&do=edit_assignment&task_id='.$assignment['task_id'].'&user_id='.$assignment['user_id'].'">';
                echo Display::return_icon('edit.png', get_lang('EditTask'));
                echo "</a>";
                echo '<a href="'.api_get_self().'?action=manage_tasks&blog_id='.$assignment['blog_id'].'&do=delete_assignment&task_id='.$assignment['task_id'].'&user_id='.$assignment['user_id'].'" ';
                echo 'onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES, $charset)).'\')) return false;"';
                echo Display::return_icon($delete_icon, $delete_title);
                echo "</a>";
                echo '</td>';
                echo '</tr>';
        }
        echo "</table>";
    }

    /**
     * Displays new task form
     * @author Toon Keppens
     *
     */
    public static function display_new_task_form ($blog_id)
    {
        // Init
        $colors = array(
            'FFFFFF',
            'FFFF99',
            'FFCC99',
            'FF9933',
            'FF6699',
            'CCFF99',
            'CC9966',
            '66FF00',
            '9966FF',
            'CF3F3F',
            '990033',
            '669933',
            '0033FF',
            '003366',
            '000000'
        );

        // form
        echo '<form name="add_task" method="post" action="blog.php?action=manage_tasks&blog_id=' . $blog_id . '">';

        // form title
        echo '<legend>'.get_lang('AddTask').'</legend>';

        // task title
        echo '	<div class="control-group">
                    <label class="control-label">
                        <span class="form_required">*</span>' . get_lang('Title') . '
                    </label>
                    <div class="controls">
                        <input name="task_name" type="text" size="70" />
                    </div>
                </div>';

        // task comment
        echo '	<div class="control-group">
                    <label class="control-label">
                        ' . get_lang('Description') . '
                    </label>
                    <div class="controls">
                        <textarea name="task_description" cols="45"></textarea>
                    </div>
                </div>';

        // task management
        echo '	<div class="control-group">
                    <label class="control-label">
                        ' . get_lang('TaskManager') . '
                    </label>
                    <div class="controls">';
                echo '<table class="data_table" cellspacing="0" style="border-collapse:collapse; width:446px;">';
                    echo '<tr>';
                        echo '<th colspan="2" style="width:223px;">' . get_lang('ArticleManager') . '</th>';
                        echo '<th width:223px;>' . get_lang('CommentManager') . '</th>';
                    echo '</tr>';
                    echo '<tr>';
                        echo '<th style="width:111px;"><label for="articleDelete">' . get_lang('Delete') . '</label></th>';
                        echo '<th style="width:112px;"><label for="articleEdit">' . get_lang('Edit') . '</label></th>';
                        echo '<th style="width:223px;"><label for="commentsDelete">' . get_lang('Delete') . '</label></th>';
                    echo '</tr>';
                    echo '<tr>';
                        echo '<td style="text-align:center;"><input id="articleDelete" name="chkArticleDelete" type="checkbox" /></td>';
                        echo '<td style="text-align:center;"><input id="articleEdit" name="chkArticleEdit" type="checkbox" /></td>';
                        echo '<td style="border:1px dotted #808080; text-align:center;"><input id="commentsDelete" name="chkCommentsDelete" type="checkbox" /></td>';
                    echo '</tr>';
                echo '</table>';
        echo '		</div>
                </div>';


        // task color
        echo '	<div class="control-group">
                    <label class="control-label">
                        ' . get_lang('Color') . '
                    </label>
                    <div class="controls">';
        echo '<select name="task_color" id="color" style="width: 150px; background-color: #eeeeee" onchange="document.getElementById(\'color\').style.backgroundColor=\'#\'+document.getElementById(\'color\').value" onkeypress="document.getElementById(\'color\').style.backgroundColor=\'#\'+document.getElementById(\'color\').value">';
                foreach ($colors as $color) {
                    $style = 'style="background-color: #' . $color . '"';
                    echo '<option value="' . $color . '" ' . $style . '>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>';
                }
        echo '</select>';
        echo '		</div>
                </div>';

        // submit
        echo '	<div class="control-group">
                    <div class="controls">
                            <input type="hidden" name="action" value="" />
                            <input type="hidden" name="new_task_submit" value="true" />
                        <button class="save" type="submit" name="Submit">' . get_lang('Save') . '</button>
                    </div>
                </div>';
        echo '</form>';

        echo '<div style="clear:both; margin-bottom: 10px;"></div>';
    }


    /**
     * Displays edit task form
     * @author Toon Keppens
     *
     */
    public static function display_edit_task_form ($blog_id, $task_id) {
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
        $course_id = api_get_course_int_id();

        $colors = array('FFFFFF','FFFF99','FFCC99','FF9933','FF6699','CCFF99','CC9966','66FF00', '9966FF', 'CF3F3F', '990033','669933','0033FF','003366','000000');

        $sql = "SELECT blog_id, task_id, title, description, color FROM $tbl_blogs_tasks WHERE c_id = $course_id AND task_id = '".(int)$task_id."'";
        $result = Database::query($sql);
        $task = Database::fetch_array($result);

        // Display
        echo '<form name="edit_task" method="post" action="blog.php?action=manage_tasks&blog_id=' . $blog_id . '">
                    <legend>' . get_lang('EditTask') . '</legend>
                    <table width="100%" border="0" cellspacing="2">
                        <tr>
                       <td align="right">' . get_lang('Title') . ':&nbsp;&nbsp;</td>
                       <td><input name="task_name" type="text" size="70" value="'.Security::remove_XSS($task['title']) . '" /></td>
                        </tr>
                        <tr>
                       <td align="right">' . get_lang('Description') . ':&nbsp;&nbsp;</td>
                       <td><textarea name="task_description" cols="45">'.Security::remove_XSS($task['description']).'</textarea></td>
                        </tr>';

                        /* edit by Kevin Van Den Haute (kevin@develop-it.be) */
                        $tbl_tasks_permissions = Database::get_course_table(TABLE_BLOGS_TASKS_PERMISSIONS);

                        $sql = " SELECT id, action FROM " . $tbl_tasks_permissions . "
                                 WHERE c_id = $course_id AND task_id = '" . (int)$task_id."'";
                        $result = Database::query($sql);

                        $arrPermissions = array();

                        while ($row = Database::fetch_array($result))
                            $arrPermissions[] = $row['action'];

                            echo '<tr>';
                            echo '<td style="text-align:right; vertical-align:top;">' . get_lang('TaskManager') . ':&nbsp;&nbsp;</td>';
                            echo '<td>';
                                echo '<table  class="data_table" cellspacing="0" style="border-collapse:collapse; width:446px;">';
                                    echo '<tr>';
                                        echo '<th colspan="2" style="width:223px;">' . get_lang('ArticleManager') . '</th>';
                                        echo '<th width:223px;>' . get_lang('CommentManager') . '</th>';
                                    echo '</tr>';
                                    echo '<tr>';
                                        echo '<th style="width:111px;"><label for="articleDelete">' . get_lang('Delete') . '</label></th>';
                                        echo '<th style="width:112px;"><label for="articleEdit">' . get_lang('Edit') . '</label></th>';
                                        echo '<th style="width:223px;"><label for="commentsDelete">' . get_lang('Delete') . '</label></th>';
                                    echo '</tr>';
                                    echo '<tr>';
                                        echo '<td style="text-align:center;"><input ' . ((in_array('article_delete', $arrPermissions)) ? 'checked ' : '') . 'id="articleDelete" name="chkArticleDelete" type="checkbox" /></td>';
                                        echo '<td style="text-align:center;"><input ' . ((in_array('article_edit', $arrPermissions)) ? 'checked ' : '') . 'id="articleEdit" name="chkArticleEdit" type="checkbox" /></td>';
                                        echo '<td style="text-align:center;"><input ' . ((in_array('article_comments_delete', $arrPermissions)) ? 'checked ' : '') . 'id="commentsDelete" name="chkCommentsDelete" type="checkbox" /></td>';
                                    echo '</tr>';
                                echo '</table>';
                            echo '</td>';
                        echo '</tr>';
                        /* end of edit */

                        echo '<tr>
                       <td align="right">' . get_lang('Color') . ':&nbsp;&nbsp;</td>
                       <td>
                        <select name="task_color" id="color" style="width: 150px; background-color: #' . $task['color'] . '" onchange="document.getElementById(\'color\').style.backgroundColor=\'#\'+document.getElementById(\'color\').value" onkeypress="document.getElementById(\'color\').style.backgroundColor=\'#\'+document.getElementById(\'color\').value">';
                            foreach ($colors as $color) {
                                $selected = ($color == $task['color']) ? ' selected' : '';
                                $style = 'style="background-color: #' . $color . '"';
                                echo '<option value="' . $color . '" ' . $style . ' ' . $selected . ' >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>';
                            }
        echo '			   </select>
                          </td>
                        </tr>
                        <tr>
                            <td align="right">&nbsp;</td>
                            <td><br /><input type="hidden" name="action" value="" />
                            <input type="hidden" name="edit_task_submit" value="true" />
                            <input type="hidden" name="task_id" value="' . $task['task_id'] . '" />
                            <input type="hidden" name="blog_id" value="' . $task['blog_id'] . '" />
                            <button class="save" type="submit" name="Submit">' . get_lang('Save') . '</button></td>
                        </tr>
                    </table>
                </form>';
    }

    /**
     * @param $blog_id
     * @return FormValidator
     */
    public static function getTaskForm($blog_id)
    {
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_blogs_rel_user = Database::get_course_table(TABLE_BLOGS_REL_USER);
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
        $course_id = api_get_course_int_id();

        // Get users in this blog / make select list of it
        $sql = "SELECT user.user_id, user.firstname, user.lastname, user.username
                FROM $tbl_users user
                INNER JOIN $tbl_blogs_rel_user blogs_rel_user
                ON user.user_id = blogs_rel_user.user_id
                WHERE blogs_rel_user.c_id = $course_id AND blogs_rel_user.blog_id = '".(int)$blog_id."'";
        $result = Database::query($sql);

        $options = array();
        while ($user = Database::fetch_array($result)) {
            $options[$user['user_id']] = api_get_person_name($user['firstname'], $user['lastname']);
        }

        // Get tasks in this blog / make select list of it
        $sql = "
            SELECT
                blog_id,
                task_id,
                blog_id,
                title,
                description,
                color,
                system_task
            FROM $tbl_blogs_tasks
            WHERE c_id = $course_id AND blog_id = " . (int)$blog_id . "
            ORDER BY system_task, title";
        $result = Database::query($sql);

        $taskOptions = array();
        while ($task = Database::fetch_array($result)) {
            $taskOptions[$task['task_id']] = stripslashes($task['title']);
        }

        $form = new FormValidator(
            'assign_task',
            'post',
            api_get_path(
                WEB_CODE_PATH
            ).'blog/blog.php?action=manage_tasks&blog_id='.$blog_id
        );

        $form->addHeader(get_lang('AssignTask'));
        $form->addSelect('task_user_id', get_lang('SelectUser'), $options);
        $form->addSelect('task_task_id', get_lang('SelectTask'), $taskOptions);
        $form->addDatePicker('task_day', get_lang('SelectTargetDate'));

        $form->addHidden('action', '');
        $form->addButtonSave(get_lang('Ok'));

        return $form;
    }

    /**
     * Displays assign task form
     * @author Toon Keppens
     *
     */
    public static function display_assign_task_form($blog_id)
    {
        $form = self::getTaskForm($blog_id);
        $form->addHidden('assign_task_submit', 'true');
        $form->display();
        echo '<div style="clear: both; margin-bottom:10px;"></div>';
    }

    /**
     * Displays assign task form
     * @author Toon Keppens
     *
     */
    public static function display_edit_assigned_task_form($blog_id, $task_id, $user_id)
    {
        $tbl_blogs_tasks_rel_user 	= Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER);

        $course_id = api_get_course_int_id();

        // Get assignd date;
        $sql = "
            SELECT target_date
            FROM $tbl_blogs_tasks_rel_user
            WHERE c_id = $course_id AND
                  blog_id = '".(int)$blog_id."' AND
                  user_id = '".(int)$user_id."' AND
                  task_id = '".(int)$task_id."'";
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        $date = $row['target_date'];

        $defaults = [
            'task_user_id' => $user_id,
            'task_task_id' => $task_id,
            'task_day' => $date
        ];
        $form = self::getTaskForm($blog_id);
        $form->addHidden('old_task_id', $task_id);
        $form->addHidden('old_user_id', $user_id);
        $form->addHidden('old_target_date', $date);
        $form->addHidden('assign_task_edit_submit', 'true');
        $form->setDefaults($defaults);
        $form->display();
    }

    /**
     * Assigns a task to a user in a blog
     *
     * @param Integer $blog_id
     * @param Integer $user_id
     * @param Integer $task_id
     * @param Date $target_date
     */
    public static function assign_task($blog_id, $user_id, $task_id, $target_date)
    {
        $tbl_blogs_tasks_rel_user = Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER);
        $course_id = api_get_course_int_id();

        $sql = "
            SELECT COUNT(*) as 'number'
            FROM " . $tbl_blogs_tasks_rel_user . "
            WHERE c_id = $course_id AND
            blog_id = " . (int)$blog_id . "
            AND	user_id = " . (int)$user_id . "
            AND	task_id = " . (int)$task_id . "
        ";

        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        if ($row['number'] == 0) {
            $sql = "
                INSERT INTO " . $tbl_blogs_tasks_rel_user . " (
                    c_id,
                    blog_id,
                    user_id,
                    task_id,
                    target_date
                ) VALUES (
                    '" . (int)$course_id . "',
                    '" . (int)$blog_id . "',
                    '" . (int)$user_id . "',
                    '" . (int)$task_id . "',
                    '" . Database::escape_string($target_date) . "'
                )";

            Database::query($sql);
        }
    }

    /**
     * @param $blog_id
     * @param $user_id
     * @param $task_id
     * @param $target_date
     * @param $old_user_id
     * @param $old_task_id
     * @param $old_target_date
     */
    public static function edit_assigned_task(
        $blog_id,
        $user_id,
        $task_id,
        $target_date,
        $old_user_id,
        $old_task_id,
        $old_target_date
    ) {
        $tbl_blogs_tasks_rel_user = Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER);

        $course_id = api_get_course_int_id();

        $sql = "SELECT COUNT(*) as 'number'
                FROM " . $tbl_blogs_tasks_rel_user . "
                WHERE
                    c_id = $course_id AND
                    blog_id = " . (int)$blog_id . " AND
                    user_id = " . (int)$user_id . " AND
                    task_id = " . (int)$task_id . "
            ";

        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        if ($row['number'] == 0 || ($row['number'] != 0 && $task_id == $old_task_id && $user_id == $old_user_id)) {
            $sql = "
                UPDATE " . $tbl_blogs_tasks_rel_user . "
                SET
                    user_id = " . (int)$user_id . ",
                    task_id = " . (int)$task_id . ",
                    target_date = '" . Database::escape_string($target_date) . "'
                WHERE
                    c_id = $course_id AND
                    blog_id = " . (int)$blog_id . " AND
                    user_id = " . (int)$old_user_id . " AND
                    task_id = " . (int)$old_task_id . " AND
                    target_date = '" . Database::escape_string($old_target_date) . "'
            ";
            Database::query($sql);
        }
    }

    /**
     * Displays a list with posts a user can select to execute his task.
     *
     * @param Integer $blog_id
     * @param unknown_type $task_id
     */
    public static function display_select_task_post($blog_id, $task_id)
    {
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
        $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);
        $course_id = api_get_course_int_id();


        $sql = "SELECT title, description FROM $tbl_blogs_tasks
                WHERE task_id = '".(int)$task_id."'
                AND c_id = $course_id";
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);
        // Get posts and authors
        $sql = "SELECT post.*, user.lastname, user.firstname, user.username
                FROM $tbl_blogs_posts post
                INNER JOIN $tbl_users user ON post.author_id = user.user_id
                WHERE post.blog_id = '".(int)$blog_id."' AND post.c_id = $course_id
                ORDER BY post_id DESC
                LIMIT 0, 100";
        $result = Database::query($sql);

        // Display
        echo '<span class="blogpost_title">' . get_lang('SelectTaskArticle') . ' "' . stripslashes($row['title']) . '"</span>';
        echo '<span style="font-style: italic;"">'.stripslashes($row['description']) . '</span><br><br>';

        if (Database::num_rows($result) > 0) {
            while($blog_post = Database::fetch_array($result)) {
                $username = api_htmlentities(sprintf(get_lang('LoginX'), $blog_post['username']), ENT_QUOTES);
                echo '<a href="blog.php?action=execute_task&blog_id=' . $blog_id . '&task_id=' . $task_id . '&post_id=' . $blog_post['post_id'] . '#add_comment">'.stripslashes($blog_post['title']) . '</a>, ' . get_lang('WrittenBy') . ' ' . stripslashes(Display::tag('span', api_get_person_name($blog_post['firstname'], $blog_post['lastname']), array('title'=>$username))) . '<br />';
            }
        } else {
            echo get_lang('NoArticles');
        }
    }

    /**
     * Subscribes a user to a given blog
     * @author Toon Keppens
     *
     * @param Integer $blog_id
     * @param Integer $user_id
     */
    public static function set_user_subscribed($blog_id, $user_id)
    {
        // Init
        $tbl_blogs_rel_user = Database::get_course_table(TABLE_BLOGS_REL_USER);
        $tbl_user_permissions = Database::get_course_table(TABLE_PERMISSION_USER);

        $course_id = api_get_course_int_id();

        // Subscribe the user
        $sql = "INSERT INTO $tbl_blogs_rel_user (c_id, blog_id, user_id )
                VALUES ($course_id, '".(int)$blog_id."', '".(int)$user_id."');";
        Database::query($sql);

        // Give this user basic rights
        $sql = "INSERT INTO $tbl_user_permissions (c_id, user_id,tool,action)
                VALUES ($course_id, '".(int)$user_id."','BLOG_" . (int)$blog_id."','article_add')";
        Database::query($sql);

        $id = Database::insert_id();
        if ($id) {
            $sql = "UPDATE $tbl_user_permissions SET id = iid WHERE iid = $id";
            Database::query($sql);
        }

        $sql = "INSERT INTO $tbl_user_permissions (c_id, user_id,tool,action)
                VALUES ($course_id, '".(int)$user_id."','BLOG_" . (int)$blog_id."','article_comments_add')";
        Database::query($sql);

        $id = Database::insert_id();
        if ($id) {
            $sql = "UPDATE $tbl_user_permissions SET id = iid WHERE iid = $id";
            Database::query($sql);
        }

    }

    /**
     * Unsubscribe a user from a given blog
     * @author Toon Keppens
     *
     * @param Integer $blog_id
     * @param Integer $user_id
     */
    public static function set_user_unsubscribed($blog_id, $user_id)
    {
        // Init
        $tbl_blogs_rel_user = Database::get_course_table(TABLE_BLOGS_REL_USER);
        $tbl_user_permissions = Database::get_course_table(TABLE_PERMISSION_USER);

        // Unsubscribe the user
        $sql = "DELETE FROM $tbl_blogs_rel_user
                WHERE blog_id = '".(int)$blog_id."' AND user_id = '".(int)$user_id."'";
        Database::query($sql);

        // Remove this user's permissions.
        $sql = "DELETE FROM $tbl_user_permissions
                WHERE user_id = '".(int)$user_id."'";
        Database::query($sql);
    }

    /**
     * Displays the form to register users in a blog (in a course)
     * The listed users are users subcribed in the course.
     * @author Toon Keppens
     *
     * @param Integer $blog_id
     *
     * @return Html Form with sortable table with users to subcribe in a blog, in a course.
     */
    public static function display_form_user_subscribe($blog_id)
    {
        $_course = api_get_course_info();
        $is_western_name_order = api_is_western_name_order();
        $session_id = api_get_session_id();
        $course_id = $_course['real_id'];

        $currentCourse = $_course['code'];
        $tbl_users 			= Database::get_main_table(TABLE_MAIN_USER);
        $tbl_blogs_rel_user = Database::get_course_table(TABLE_BLOGS_REL_USER);

        echo '<legend>'.get_lang('SubscribeMembers').'</legend>';

        $properties["width"] = "100%";

        // Get blog members' id.
        $sql = "SELECT user.user_id FROM $tbl_users user
                INNER JOIN $tbl_blogs_rel_user blogs_rel_user
                ON user.user_id = blogs_rel_user.user_id
                WHERE blogs_rel_user.c_id = $course_id AND blogs_rel_user.blog_id = '".intval($blog_id)."'";
        $result = Database::query($sql);

        $blog_member_ids = array();
        while($user = Database::fetch_array($result)) {
            $blog_member_ids[] = $user['user_id'];
        }

        // Set table headers
        $column_header[] = array ('', false, '');
        if ($is_western_name_order) {
            $column_header[] = array(get_lang('FirstName'), true, '');
            $column_header[] = array(get_lang('LastName'), true, '');
        } else {
            $column_header[] = array(get_lang('LastName'), true, '');
            $column_header[] = array(get_lang('FirstName'), true, '');
        }
        $column_header[] = array(get_lang('Email'), false, '');
        $column_header[] = array(get_lang('Register'), false, '');

        $student_list = CourseManager:: get_student_list_from_course_code(
            $currentCourse,
            false,
            $session_id
        );
        $user_data = array();

        // Add users that are not in this blog to the list.
        foreach ($student_list as $key=>$user) {
            if(isset($user['id_user'])) {
                $user['user_id'] = $user['id_user'];
            }
            if(!in_array($user['user_id'],$blog_member_ids)) {
                $a_infosUser = api_get_user_info($user['user_id']);
                $row = array ();
                $row[] = '<input type="checkbox" name="user[]" value="' . $a_infosUser['user_id'] . '" '.((isset($_GET['selectall']) && $_GET['selectall'] == "subscribe") ? ' checked="checked" ' : '') . '/>';
                $username = api_htmlentities(sprintf(get_lang('LoginX'), $a_infosUser["username"]), ENT_QUOTES);
                if ($is_western_name_order) {
                    $row[] = $a_infosUser["firstname"];
                    $row[] = Display::tag('span', $a_infosUser["lastname"], array('title'=>$username));
                } else {
                    $row[] = Display::tag('span', $a_infosUser["lastname"], array('title'=>$username));
                    $row[] = $a_infosUser["firstname"];
                }
                $row[] = Display::icon_mailto_link($a_infosUser["email"]);

                //Link to register users
                if ($a_infosUser["user_id"] != $_SESSION['_user']['user_id']){
                    $row[] = "<a class=\"btn btn-primary \" href=\"" .api_get_self()."?action=manage_members&blog_id=$blog_id&register=yes&user_id=" . $a_infosUser["user_id"]."\">" . get_lang('Register')."</a>";
                } else {
                    $row[] = '';
                }
                $user_data[] = $row;
            }
        }

        // Display
        $query_vars['action'] = 'manage_members';
        $query_vars['blog_id'] = $blog_id;
        echo '<form method="post" action="blog.php?action=manage_members&blog_id=' . $blog_id . '">';
            Display::display_sortable_table($column_header, $user_data,null,null,$query_vars);
            $link = '';
            $link .= isset ($_GET['action']) ? 'action=' . Security::remove_XSS($_GET['action']) . '&' : '';
            $link .= "blog_id=$blog_id&";

            echo '<a href="blog.php?' . $link . 'selectall=subscribe">' . get_lang('SelectAll') . '</a> - ';
            echo '<a href="blog.php?' . $link . '">' . get_lang('UnSelectAll') . '</a> ';
            echo get_lang('WithSelected') . ' : ';
            echo '<select name="action">';
            echo '<option value="select_subscribe">' . get_lang('Register') . '</option>';
            echo '</select>';
            echo '<input type="hidden" name="register" value="true" />';
            echo '<button class="save" type="submit">' . get_lang('Ok') . '</button>';
        echo '</form>';
    }

    /**
     * Displays the form to register users in a blog (in a course)
     * The listed users are users subcribed in the course.
     * @author Toon Keppens
     *
     * @param Integer $blog_id
     *
     * @return false|null Form with sortable table with users to unsubcribe from a blog.
     */
    public static function display_form_user_unsubscribe ($blog_id)
    {
        $_user = api_get_user_info();
        $is_western_name_order = api_is_western_name_order();

        // Init
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_blogs_rel_user = Database::get_course_table(TABLE_BLOGS_REL_USER);

        echo '<legend>'.get_lang('UnsubscribeMembers').'</legend>';

        $properties["width"] = "100%";
        //table column titles
        $column_header[] = array ('', false, '');
        if ($is_western_name_order) {
            $column_header[] = array (get_lang('FirstName'), true, '');
            $column_header[] = array (get_lang('LastName'), true, '');
        } else {
            $column_header[] = array (get_lang('LastName'), true, '');
            $column_header[] = array (get_lang('FirstName'), true, '');
        }
        $column_header[] = array (get_lang('Email'), false, '');
        $column_header[] = array (get_lang('TaskManager'), true, '');
        $column_header[] = array (get_lang('UnRegister'), false, '');

        $course_id = api_get_course_int_id();

        $sql = "SELECT user.user_id, user.lastname, user.firstname, user.email, user.username
                FROM $tbl_users user INNER JOIN $tbl_blogs_rel_user blogs_rel_user
                ON user.user_id = blogs_rel_user.user_id
                WHERE blogs_rel_user.c_id = $course_id AND  blogs_rel_user.blog_id = '".(int)$blog_id."'";

        if (!($sql_result = Database::query($sql))) {
            return false;
        }

        $user_data = array ();

        while ($myrow = Database::fetch_array($sql_result)) {
            $row = array ();
            $row[] = '<input type="checkbox" name="user[]" value="' . $myrow['user_id'] . '" '.((isset($_GET['selectall']) && $_GET['selectall'] == "unsubscribe") ? ' checked="checked" ' : '') . '/>';
            $username = api_htmlentities(sprintf(get_lang('LoginX'), $myrow["username"]), ENT_QUOTES);
            if ($is_western_name_order) {
                $row[] = $myrow["firstname"];
                $row[] = Display::tag('span', $myrow["lastname"], array('title'=>$username));
            } else {
                $row[] = Display::tag('span', $myrow["lastname"], array('title'=>$username));
                $row[] = $myrow["firstname"];
            }
            $row[] = Display::icon_mailto_link($myrow["email"]);

            $sql = "SELECT bt.title task
                    FROM " . Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER) . " btu
                    INNER JOIN " . Database::get_course_table(TABLE_BLOGS_TASKS) . " bt
                    ON btu.task_id = bt.task_id
                    WHERE 	btu.c_id 	= $course_id  AND
                            bt.c_id 	= $course_id  AND
                            btu.blog_id = $blog_id AND
                            btu.user_id = " . $myrow['user_id'];
            $sql_res = Database::query($sql);

            $task = '';

            while($r = Database::fetch_array($sql_res)) {
                $task .= stripslashes($r['task']) . ', ';
            }
            //echo $task;
            $task = (api_strlen(trim($task)) != 0) ? api_substr($task, 0, api_strlen($task) - 2) : get_lang('Reader');
            $row[] = $task;
            //Link to register users

            if ($myrow["user_id"] != $_user['user_id']) {
                $row[] = "<a class=\"btn btn-primary\" href=\"" .api_get_self()."?action=manage_members&blog_id=$blog_id&unregister=yes&user_id=" . $myrow['user_id']."\">" . get_lang('UnRegister')."</a>";
            } else {
                $row[] = '';
            }

            $user_data[] = $row;
        }

        $query_vars['action'] = 'manage_members';
        $query_vars['blog_id'] = $blog_id;
        echo '<form method="post" action="blog.php?action=manage_members&blog_id=' . $blog_id . '">';
        Display::display_sortable_table($column_header, $user_data,null,null,$query_vars);
        $link = '';
        $link .= isset ($_GET['action']) ? 'action=' . Security::remove_XSS($_GET['action']). '&' : '';
        $link .= "blog_id=$blog_id&";

        echo '<a href="blog.php?' . $link . 'selectall=unsubscribe">' . get_lang('SelectAll') . '</a> - ';
        echo '<a href="blog.php?' . $link . '">' . get_lang('UnSelectAll') . '</a> ';
        echo get_lang('WithSelected') . ' : ';
        echo '<select name="action">';
        echo '<option value="select_unsubscribe">' . get_lang('UnRegister') . '</option>';
        echo '</select>';
        echo '<input type="hidden" name="unregister" value="true" />';
        echo '<button class="save" type="submit">' . get_lang('Ok') . '</button>';
        echo '</form>';
    }

    /**
     * Displays a matrix with selectboxes. On the left: users, on top: possible rights.
     * The blog admin can thus select what a certain user can do in the current blog
     *
     * @param Integer $blog_id
     */
    public static function display_form_user_rights ($blog_id)
    {
        echo '<legend>'.get_lang('RightsManager').'</legend>';
        echo '<br />';

        // Integration of patricks permissions system.
        require_once api_get_path(SYS_CODE_PATH).'permissions/blog_permissions.inc.php';
    }

    /**
     * Displays the form to create a new post
     * @author Toon Keppens
     *
     * @param Integer $blog_id
     * @param integer $post_id
     */
    public static function display_new_comment_form($blog_id, $post_id, $title)
    {
        $form = new FormValidator(
            'add_post',
            'post',
            api_get_path(WEB_CODE_PATH)."blog/blog.php?action=view_post&blog_id=" . intval($blog_id)  . "&post_id=".intval($post_id)."&".api_get_cidreq(),
            null,
            array('enctype' => 'multipart/form-data')
        );

        $header = get_lang('AddNewComment');
        if (isset($_GET['task_id'])) {
            $header = get_lang('ExecuteThisTask');
        }
        $form->addHeader($header);
        $form->addText('title', get_lang('Title'));

        $config = array();
        if (!api_is_allowed_to_edit()) {
            $config['ToolbarSet'] = 'ProjectComment';
        } else {
            $config['ToolbarSet'] = 'ProjectCommentStudent';
        }
        $form->addHtmlEditor('comment', get_lang('Comment'), false, false, $config);
        $form->addFile('user_upload', get_lang('AddAnAttachment'));

        $form->addTextarea('post_file_comment', get_lang('FileComment'));

        $form->addHidden('action', null);
        $form->addHidden('comment_parent_id', 0);

        if (isset($_GET['task_id'])) {
            $form->addHidden('new_task_execution_submit', 'true');
            $form->addHidden('task_id', intval($_GET['task_id']));
        } else {
            $form->addHidden('new_comment_submit', 'true');
        }
        $form->addButton('save', get_lang('Save'));
        $form->display();
    }


    /**
     * show the calender of the given month
     * @author Patrick Cool
     * @author Toon Keppens
     *
     * @param Integer $month: the integer value of the month we are viewing
     * @param Integer $year: the 4-digit year indication e.g. 2005
     *
     * @return html code
    */
    public static function display_minimonthcalendar($month, $year, $blog_id)
    {
        // Init
        $_user = api_get_user_info();
        global $DaysShort;
        global $MonthsLong;

        $posts = array();
        $tasks = array();

        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
        $tbl_blogs_tasks_rel_user = Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER);
        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);

        $course_id = api_get_course_int_id();

        //Handle leap year
        $numberofdays = array (0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

        if(($year % 400 == 0) or ($year % 4 == 0 and $year % 100 <> 0))
            $numberofdays[2] = 29;

        //Get the first day of the month
        $dayone = getdate(mktime(0, 0, 0, $month, 1, $year));
        $monthName = $MonthsLong[$month-1];

        //Start the week on monday
        $startdayofweek = $dayone['wday'] <> 0 ? ($dayone['wday'] - 1) : 6;
        $blogId = isset($_GET['blog_id']) ? intval($_GET['blog_id']) : null;
        $filter = isset($_GET['filter']) ? Security::remove_XSS($_GET['filter']) : null;
        $backwardsURL = api_get_self()."?blog_id=" . $blogId."&filter=" . $filter."&month=". ($month == 1 ? 12 : $month -1)."&year=". ($month == 1 ? $year -1 : $year);
        $forewardsURL = api_get_self()."?blog_id=" . $blogId."&filter=" . $filter."&month=". ($month == 12 ? 1 : $month +1)."&year=". ($month == 12 ? $year +1 : $year);

        // Get posts for this month
        $sql = "SELECT post.*, DAYOFMONTH(date_creation) as post_day, user.lastname, user.firstname
                FROM $tbl_blogs_posts post
                INNER JOIN $tbl_users user
                ON post.author_id = user.user_id
                WHERE
                    post.c_id = $course_id AND
                    post.blog_id = '".(int)$blog_id."' AND
                    MONTH(date_creation) = '".(int)$month."' AND
                    YEAR(date_creation) = '".(int)$year."'
                ORDER BY date_creation";
        $result = Database::query($sql);

        // We will create an array of days on which there are posts.
        if( Database::num_rows($result) > 0) {
            while($blog_post = Database::fetch_array($result)) {
                // If the day of this post is not yet in the array, add it.
                if (!in_array($blog_post['post_day'], $posts))
                    $posts[] = $blog_post['post_day'];
            }
        }

        // Get tasks for this month
        if ($_user['user_id']) {
            $sql = " SELECT task_rel_user.*,  DAYOFMONTH(target_date) as task_day, task.title, blog.blog_name
                FROM $tbl_blogs_tasks_rel_user task_rel_user
                INNER JOIN $tbl_blogs_tasks task ON task_rel_user.task_id = task.task_id
                INNER JOIN $tbl_blogs blog ON task_rel_user.blog_id = blog.blog_id
                WHERE
                    task_rel_user.c_id = $course_id AND
                    task.c_id = $course_id AND
                    blog.c_id = $course_id AND
                    task_rel_user.user_id = '".(int)$_user['user_id']."' AND
                    MONTH(target_date) = '".(int)$month."' AND
                    YEAR(target_date) = '".(int)$year."'
                ORDER BY target_date ASC";
            $result = Database::query($sql);

            if (Database::num_rows($result) > 0) {
                while ($mytask = Database::fetch_array($result)) {
                    $tasks[$mytask['task_day']][$mytask['task_id']]['task_id'] = $mytask['task_id'];
                    $tasks[$mytask['task_day']][$mytask['task_id']]['title'] = $mytask['title'];
                    $tasks[$mytask['task_day']][$mytask['task_id']]['blog_id'] = $mytask['blog_id'];
                    $tasks[$mytask['task_day']][$mytask['task_id']]['blog_name'] = $mytask['blog_name'];
                    $tasks[$mytask['task_day']][$mytask['task_id']]['day'] = $mytask['task_day'];
                }
            }
        }

        echo 	'<table id="smallcalendar" class="table table-responsive">',
                "<tr id=\"title\">",
                "<th width=\"10%\"><a href=\"", $backwardsURL, "\">&laquo;</a></th>",
                "<th align=\"center\" width=\"80%\" colspan=\"5\">", $monthName, " ", $year, "</th>",
                "<th width=\"10%\" align=\"right\"><a href=\"", $forewardsURL, "\">&raquo;</a></th>", "</tr>";

        echo "<tr>";

        for($ii = 1; $ii < 8; $ii ++)
            echo "<td class=\"weekdays\">", $DaysShort[$ii % 7], "</td>";

        echo "</tr>";

        $curday = -1;
        $today = getdate();

        while ($curday <= $numberofdays[$month]) {
            echo "<tr>";
            for ($ii = 0; $ii < 7; $ii ++) {
                if (($curday == -1) && ($ii == $startdayofweek))
                    $curday = 1;

                if (($curday > 0) && ($curday <= $numberofdays[$month])) {
                    $bgcolor = $ii < 5 ? $class="class=\"days_week\"" : $class="class=\"days_weekend\"";
                    $dayheader = "$curday";

                    if(($curday == $today['mday']) && ($year == $today['year']) && ($month == $today['mon'])) {
                        $dayheader = "$curday";
                        $class = "class=\"days_today\"";
                    }

                    echo "<td " . $class.">";

                    // If there are posts on this day, create a filter link.
                    if(in_array($curday, $posts))
                        echo '<a href="blog.php?blog_id=' . $blog_id . '&filter=' . $year . '-' . $month . '-' . $curday . '&month=' . $month . '&year=' . $year . '" title="' . get_lang('ViewPostsOfThisDay') . '">' . $curday . '</a>';
                    else
                        echo $dayheader;

                    if (count($tasks) > 0) {
                        if (isset($tasks[$curday]) && is_array($tasks[$curday])) {
                            // Add tasks to calendar
                            foreach ($tasks[$curday] as $task) {
                                echo '<a href="blog.php?action=execute_task&blog_id=' . $task['blog_id'] . '&task_id='.stripslashes($task['task_id']) . '" title="' . $task['title'] . ' : ' . get_lang('InBlog') . ' : ' . $task['blog_name'] . ' - ' . get_lang('ExecuteThisTask') . '">';
                                echo Display::return_icon('blog_task.gif', get_lang('ExecuteThisTask'));
                                echo '</a>';
                            }
                        }
                    }

                    echo "</td>";
                    $curday ++;
                } else
                    echo "<td>&nbsp;</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }

    /**
     * Blog admin | Display the form to add a new blog.
     *
     */
    public static function display_new_blog_form()
    {
        $form = new FormValidator('add_blog', 'post', 'blog_admin.php?action=add');
        $form->addElement('header', get_lang('AddBlog'));
        $form->addElement('text', 'blog_name', get_lang('Title'));
        $form->addElement('textarea', 'blog_subtitle', get_lang('SubTitle'));

        $form->addElement('hidden', 'new_blog_submit', 'true');
        $form->addButtonSave(get_lang('SaveProject'));

        $defaults = array(
            'blog_name' => isset($_POST['blog_name']) ? Security::remove_XSS($_POST['blog_name']) : null,
            'blog_subtitle' => isset($_POST['blog_subtitle']) ? Security::remove_XSS($_POST['blog_subtitle']) : null
        );
        $form->setDefaults($defaults);
        $form->display();
    }

    /**
     * Blog admin | Display the form to edit a blog.
     *
     */
    public static function display_edit_blog_form($blog_id)
    {
        $course_id = api_get_course_int_id();
        $blog_id= intval($blog_id);
        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);

        $sql = "SELECT blog_id, blog_name, blog_subtitle
                FROM $tbl_blogs
                WHERE c_id = $course_id AND blog_id = '".$blog_id."'";
        $result = Database::query($sql);
        $blog = Database::fetch_array($result);

        // the form contained errors but we do not want to lose the changes the user already did
        if ($_POST) {
            $blog['blog_name'] = Security::remove_XSS($_POST['blog_name']);
            $blog['blog_subtitle'] = Security::remove_XSS($_POST['blog_subtitle']);
        }

        $form = new FormValidator('edit_blog', 'post','blog_admin.php?action=edit&blog_id='.intval($_GET['blog_id']));
        $form->addElement('header', get_lang('EditBlog'));
        $form->addElement('text', 'blog_name', get_lang('Title'));
        $form->addElement('textarea', 'blog_subtitle', get_lang('SubTitle'));

        $form->addElement('hidden', 'edit_blog_submit', 'true');
        $form->addElement('hidden', 'blog_id', $blog['blog_id']);
        $form->addButtonSave(get_lang('Save'));

        $defaults = array();
        $defaults['blog_name'] = $blog['blog_name'];
        $defaults['blog_subtitle'] = $blog['blog_subtitle'];
        $form->setDefaults($defaults);
        $form->display();
    }

    /**
     * Blog admin | Returns table with blogs in this course
     */
    public static function display_blog_list()
    {
        global $charset;
        $_user = api_get_user_info();
        $course_id = api_get_course_int_id();

        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);

        //condition for the session
        $session_id = api_get_session_id();

        $sql = "SELECT blog_name, blog_subtitle, visibility, blog_id, session_id
                FROM $tbl_blogs WHERE c_id = $course_id
                ORDER BY date_creation DESC";
        $result = Database::query($sql);
        $list_info = array();
        if (Database::num_rows($result)) {
            while ($row_project=Database::fetch_row($result)) {
                $list_info[]=$row_project;
            }
        }

        $list_content_blog = array();
        $list_body_blog = array();

        if (is_array($list_info)) {
            foreach ($list_info as $key => $info_log) {
                // Validation when belongs to a session
                $session_img = api_get_session_image($info_log[4], $_user['status']);

                $url_start_blog = 'blog.php' ."?". "blog_id=".$info_log[3]. "&".api_get_cidreq();
                $title = $info_log[0];
                        $image = Display::return_icon('blog.png', $title);
                $list_name = '<div style="float: left; width: 35px; height: 22px;"><a href="'.$url_start_blog.'">' . $image . '</a></div><a href="'.$url_start_blog.'">' .$title. '</a>' . $session_img;

                $list_body_blog[] = $list_name;
                $list_body_blog[] = $info_log[1];

                $visibility_icon=($info_log[2]==0) ? 'invisible' : 'visible';
                $visibility_info=($info_log[2]==0) ? 'Visible' : 'Invisible';
                $my_image = '<a href="' .api_get_self(). '?action=edit&blog_id=' . $info_log[3] . '">';
                                $my_image.= Display::return_icon('edit.png', get_lang('EditBlog'));

                $my_image.= "</a>";
                $my_image.= '<a href="' .api_get_self(). '?action=delete&blog_id=' . $info_log[3] . '" ';
                $my_image.= 'onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset)). '\')) return false;" >';
                                $my_image.= Display::return_icon('delete.png', get_lang('DeleteBlog'));

                $my_image.= "</a>";
                $my_image.= '<a href="' .api_get_self(). '?action=visibility&blog_id=' . $info_log[3] . '">';
                                $my_image.= Display::return_icon($visibility_icon . '.gif', get_lang($visibility_info));

                $my_image.= "</a>";
                $list_body_blog[]=$my_image;
                $list_content_blog[]=$list_body_blog;
                $list_body_blog = array();
            }

            $table = new SortableTableFromArrayConfig($list_content_blog, 1,20,'project');
            $table->set_header(0, get_lang('Title'));
            $table->set_header(1, get_lang('SubTitle'));
            $table->set_header(2, get_lang('Modify'));
            $table->display();
        }
    }
}

/**
 *
 * END CLASS BLOG
 *
 */

/**
 * Show a list with all the attachments according the parameter's
 * @param the blog's id
 * @param the post's id
 * @param the comment's id
 * @param integer $blog_id
 * @return array with the post info according the parameters
 * @author Julio Montoya Dokeos
 * @version avril 2008, dokeos 1.8.5
 */
function get_blog_attachment($blog_id, $post_id=null,$comment_id=null)
{
	$blog_table_attachment = Database::get_course_table(TABLE_BLOGS_ATTACHMENT);

	$blog_id = intval($blog_id);
	$comment_id = intval($comment_id);
	$post_id = intval($post_id);
	$row=array();
	$where='';
	if (!empty ($post_id) && is_numeric($post_id)) {
		$where.=' AND post_id ="'.$post_id.'" ';
	}

	if (!empty ($comment_id) && is_numeric($comment_id)) {
		if (!empty ($post_id)) {
			$where.= ' AND ';
		}
		$where.=' comment_id ="'.$comment_id.'" ';
	}

    $course_id = api_get_course_int_id();

	$sql = 'SELECT path, filename, comment FROM '. $blog_table_attachment.'
	        WHERE c_id = '.$course_id.' AND blog_id ="'.intval($blog_id).'"  '.$where;

	$result=Database::query($sql);
	if (Database::num_rows($result)!=0) {
		$row=Database::fetch_array($result);
	}
	return $row;
}

/**
 * Delete the all the attachments according the parameters.
 * @param the blog's id
 * @param the post's id
 * @param the comment's id
 * @param integer $blog_id
 * @param integer $post_id
 * @param integer $comment_id
 * @author Julio Montoya Dokeos
 * @version avril 2008, dokeos 1.8.5
 */

function delete_all_blog_attachment($blog_id,$post_id=null,$comment_id=null)
{
	$_course = api_get_course_info();
	$blog_table_attachment = Database::get_course_table(TABLE_BLOGS_ATTACHMENT);
	$blog_id = intval($blog_id);
	$comment_id = intval($comment_id);
	$post_id = intval($post_id);

    $course_id = api_get_course_int_id();
	$where = null;

	// delete files in DB
    if (!empty ($post_id) && is_numeric($post_id)) {
        $where .= ' AND post_id ="'.$post_id.'" ';
    }

    if (!empty ($comment_id) && is_numeric($comment_id)) {
        if (!empty ($post_id)) {
            $where .= ' AND ';
        }
        $where .= ' comment_id ="'.$comment_id.'" ';
    }

	// delete all files in directory
	$courseDir   = $_course['path'].'/upload/blog';
	$sys_course_path = api_get_path(SYS_COURSE_PATH);
	$updir = $sys_course_path.$courseDir;

	$sql = 'SELECT path FROM '.$blog_table_attachment.'
	        WHERE c_id = '.$course_id.' AND blog_id ="'.intval($blog_id).'"  '.$where;
	$result=Database::query($sql);

	while ($row=Database::fetch_row($result)) {
		$file=$updir.'/'.$row[0];
		if (Security::check_abs_path($file,$updir) )
		{
			@ unlink($file);
		}
	}
	$sql = 'DELETE FROM '. $blog_table_attachment.'
	        WHERE c_id = '.$course_id.' AND  blog_id ="'.intval($blog_id).'"  '.$where;
	Database::query($sql);
}

/**
 * Gets all the post from a given user id
 * @param string db course name
 * @param int user id
 */
function get_blog_post_from_user($course_code, $user_id)
{
	$tbl_blogs 		= Database::get_course_table(TABLE_BLOGS);
	$tbl_blog_post 	= Database::get_course_table(TABLE_BLOGS_POSTS);
	$course_info 	= api_get_course_info($course_code);
	$course_id 		= $course_info['real_id'];

	$sql = "SELECT DISTINCT blog.blog_id, post_id, title, full_text, post.date_creation
			FROM $tbl_blogs blog
			INNER JOIN  $tbl_blog_post post
			ON (blog.blog_id = post.blog_id)
			WHERE
				blog.c_id = $course_id AND
				post.c_id = $course_id AND
				author_id =  $user_id AND visibility = 1
			ORDER BY post.date_creation DESC ";
	$result = Database::query($sql);
	$return_data = '';

	if (Database::num_rows($result)!=0) {
		while ($row=Database::fetch_array($result)) {
			$return_data.=  '<div class="clear"></div><br />';
			$return_data.=  '<div class="actions" style="margin-left:5px;margin-right:5px;">'.Display::return_icon('blog_article.png',get_lang('BlogPosts')).' '.$row['title'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<div style="float:right;margin-top:-18px"><a href="../blog/blog.php?blog_id='.$row['blog_id'].'&gidReq=&cidReq='.$my_course_id.' " >'.get_lang('SeeBlog').'</a></div></div>';
			$return_data.=  '<br / >';
			$return_data.= $row['full_text'];
			$return_data.= '<br /><br />';
		}
	}
	return $return_data;
}

/**
 * Gets all the post comments from a given user id
 * @param string db course name
 * @param int user id
 */
function get_blog_comment_from_user($course_code, $user_id)
{
    $tbl_blogs = Database::get_course_table(TABLE_BLOGS);
    $tbl_blog_comment = Database::get_course_table(TABLE_BLOGS_COMMENTS);
    $user_id = intval($user_id);

    $course_info = api_get_course_info($course_code);
    $course_id = $course_info['real_id'];

	$sql = "SELECT DISTINCT blog.blog_id, comment_id, title, comment, comment.date_creation
			FROM $tbl_blogs blog INNER JOIN  $tbl_blog_comment comment
			ON (blog.blog_id = comment.blog_id)
			WHERE 	blog.c_id = $course_id AND
					comment.c_id = $course_id AND
					author_id =  $user_id AND
					visibility = 1
			ORDER BY blog_name";
	$result = Database::query($sql);
	$return_data = '';
	if (Database::num_rows($result)!=0) {
		while ($row=Database::fetch_array($result)) {
			$return_data.=  '<div class="clear"></div><br />';
			$return_data.=  '<div class="actions" style="margin-left:5px;margin-right:5px;">'.$row['title'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<div style="float:right;margin-top:-18px"><a href="../blog/blog.php?blog_id='.$row['blog_id'].'&gidReq=&cidReq='.Security::remove_XSS($course_code).' " >'.get_lang('SeeBlog').'</a></div></div>';
			$return_data.=  '<br / >';
			//$return_data.=  '<strong>'.$row['title'].'</strong>'; echo '<br>';*/
			$return_data.=  $row['comment'];
			$return_data.=  '<br />';
		}
	}
	return $return_data;
}

