<?php

/* For licensing terms, see /license.txt */

/**
 * Class Blog.
 *
 * Contains several functions dealing with displaying editing of a blog
 *
 * @author Toon Keppens <toon@vi-host.net>
 * @author Julio Montoya - Cleaning code
 */
class Blog
{
    /**
     * Get the title of a blog.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id The internal ID of the blog
     *
     * @return string Blog Title
     */
    public static function getBlogTitle($blog_id)
    {
        $course_id = api_get_course_int_id();

        if (is_numeric($blog_id)) {
            $table = Database::get_course_table(TABLE_BLOGS);

            $sql = "SELECT blog_name
                    FROM $table
                    WHERE c_id = $course_id AND blog_id = ".intval($blog_id);

            $result = Database::query($sql);
            $blog = Database::fetch_array($result);

            return Security::remove_XSS(stripslashes($blog['blog_name']));
        }
    }

    /**
     * Get the description of a blog.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id The internal ID of the blog
     *
     * @return string Blog description
     */
    public static function getBlogSubtitle($blog_id)
    {
        $table = Database::get_course_table(TABLE_BLOGS);
        $course_id = api_get_course_int_id();
        $sql = "SELECT blog_subtitle FROM $table
                WHERE c_id = $course_id AND blog_id ='".intval($blog_id)."'";
        $result = Database::query($sql);
        $blog = Database::fetch_array($result);

        return Security::remove_XSS(stripslashes($blog['blog_subtitle']));
    }

    /**
     * Get the users of a blog.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id The ID of the blog
     *
     * @return array Returns an array with [userid]=>[username]
     */
    public static function getBlogUsers($blog_id)
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
                    blogs_rel_user.blog_id = '".(int) $blog_id."'";
        $result = Database::query($sql);
        $blog_members = [];
        while ($user = Database::fetch_array($result)) {
            $blog_members[$user['user_id']] = api_get_person_name(
                $user['firstname'],
                $user['lastname']
            );
        }

        return $blog_members;
    }

    /**
     * Creates a new blog in the given course.
     *
     * @author Toon Keppens
     *
     * @param string $title    The title of the new blog
     * @param string $subtitle The description (or subtitle) of the new blog
     */
    public static function addBlog($title, $subtitle)
    {
        $_user = api_get_user_info();
        $course_id = api_get_course_int_id();

        $current_date = api_get_utc_datetime();
        $session_id = api_get_session_id();
        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);
        $tbl_tool = Database::get_course_table(TABLE_TOOL_LIST);
        $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);

        //verified if exist blog
        $sql = "SELECT COUNT(*) as count FROM $tbl_blogs
                WHERE
                    c_id = $course_id AND
                    blog_name = '".Database::escape_string($title)."' AND
                    blog_subtitle = '".Database::escape_string($subtitle)."'  ";
        $res = Database::query($sql);
        $info_count = Database::result($res, 0, 0);

        if (0 == $info_count) {
            // Create the blog
            $params = [
                'blog_id' => 0,
                'c_id' => $course_id,
                'blog_name' => $title,
                'blog_subtitle' => $subtitle,
                'date_creation' => $current_date,
                'visibility' => 1,
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
            $params = [
                'c_id' => $course_id,
                'name' => $title,
                'link' => 'blog/blog.php?blog_id='.$this_blog_id,
                'image' => 'blog.gif',
                'visibility' => '1',
                'admin' => '0',
                'address' => 'pastillegris.gif',
                'added_tool' => 0,
                'session_id' => $session_id,
                'target' => '',
            ];
            $toolId = Database::insert($tbl_tool, $params);
            if ($toolId) {
                $sql = "UPDATE $tbl_tool SET id = iid WHERE iid = $toolId";
                Database::query($sql);
            }

            // Subscribe the teacher to this blog
            self::subscribeUser($this_blog_id, $_user['user_id']);
        }
    }

    /**
     * Subscribes a user to a given blog.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id The internal blog ID
     * @param int $user_id The internal user ID (of the user to be subscribed)
     */
    public static function subscribeUser($blog_id, $user_id)
    {
        $tbl_blogs_rel_user = Database::get_course_table(TABLE_BLOGS_REL_USER);
        $tbl_user_permissions = Database::get_course_table(TABLE_PERMISSION_USER);

        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $user_id = intval($user_id);

        // Subscribe the user
        $sql = "INSERT INTO $tbl_blogs_rel_user (c_id, blog_id, user_id )
                VALUES ($course_id, $blog_id, $user_id)";
        Database::query($sql);

        // Give this user basic rights
        $sql = "INSERT INTO $tbl_user_permissions (c_id, user_id, tool, action)
                VALUES ($course_id, $user_id, 'BLOG_$blog_id', 'article_add')";
        Database::query($sql);

        $id = Database::insert_id();
        if ($id) {
            $sql = "UPDATE $tbl_user_permissions SET id = iid WHERE iid = $id";
            Database::query($sql);
        }

        $sql = "INSERT INTO $tbl_user_permissions (c_id, user_id, tool, action)
                VALUES ($course_id, $user_id,'BLOG_$blog_id', 'article_comments_add')";
        Database::query($sql);

        $id = Database::insert_id();
        if ($id) {
            $sql = "UPDATE $tbl_user_permissions SET id = iid WHERE iid = $id";
            Database::query($sql);
        }
    }

    /**
     * Update title and subtitle of a blog in the given course.
     *
     * @author Toon Keppens
     *
     * @param int    $blog_id  The internal ID of the blog
     * @param string $title    The title to be set
     * @param string $subtitle The subtitle (or description) to be set
     */
    public static function editBlog($blog_id, $title, $subtitle = '')
    {
        // Table definitions
        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);
        $tbl_tool = Database::get_course_table(TABLE_TOOL_LIST);

        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $title = Database::escape_string($title);
        $subtitle = Database::escape_string($subtitle);

        // Update the blog
        $sql = "UPDATE $tbl_blogs SET
                blog_name = '$title',
                blog_subtitle = '$subtitle'
                WHERE
                    c_id = $course_id AND
                    blog_id = $blog_id
                LIMIT 1";
        Database::query($sql);

        //update item_property (update)
        api_item_property_update(
            api_get_course_info(),
            TOOL_BLOGS,
            $blog_id,
            'BlogUpdated',
            api_get_user_id()
        );

        // Update course homepage link
        $sql = "UPDATE $tbl_tool SET
                name = '$title'
                WHERE c_id = $course_id AND link = 'blog/blog.php?blog_id=$blog_id'
                LIMIT 1";
        Database::query($sql);
    }

    /**
     * Deletes a blog and it's posts from the course database.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id The internal blog ID
     */
    public static function deleteBlog($blog_id)
    {
        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);
        $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);
        $tbl_blogs_comment = Database::get_course_table(TABLE_BLOGS_COMMENTS);
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
        $tbl_tool = Database::get_course_table(TABLE_TOOL_LIST);
        $tbl_blogs_rating = Database::get_course_table(TABLE_BLOGS_RATING);

        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);

        // Delete posts from DB and the attachments
        self::deleteAllBlogAttachments($blog_id);

        //Delete comments
        $sql = "DELETE FROM $tbl_blogs_comment WHERE c_id = $course_id AND blog_id = $blog_id";
        Database::query($sql);

        // Delete posts
        $sql = "DELETE FROM $tbl_blogs_posts WHERE c_id = $course_id AND blog_id = $blog_id";
        Database::query($sql);

        // Delete tasks
        $sql = "DELETE FROM $tbl_blogs_tasks WHERE c_id = $course_id AND blog_id = $blog_id";
        Database::query($sql);

        // Delete ratings
        $sql = "DELETE FROM $tbl_blogs_rating WHERE c_id = $course_id AND blog_id = $blog_id";
        Database::query($sql);

        // Delete blog
        $sql = "DELETE FROM $tbl_blogs WHERE c_id = $course_id AND blog_id = $blog_id";
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
     * Creates a new post in a given blog.
     *
     * @author Toon Keppens
     *
     * @param string $title        The title of the new post
     * @param string $full_text    The full text of the new post
     * @param string $file_comment The text of the comment (if any)
     * @param int    $blog_id      The internal blog ID
     *
     * @return int
     */
    public static function createPost($title, $full_text, $file_comment, $blog_id)
    {
        $_user = api_get_user_info();
        $_course = api_get_course_info();
        $course_id = $_course['real_id'];
        $blog_id = intval($blog_id);

        $blog_table_attachment = Database::get_course_table(TABLE_BLOGS_ATTACHMENT);
        $upload_ok = true;
        $has_attachment = false;
        $current_date = api_get_utc_datetime();

        if (!empty($_FILES['user_upload']['name'])) {
            $upload_ok = process_uploaded_file($_FILES['user_upload']);
            $has_attachment = true;
        }

        if ($upload_ok) {
            // Table Definitions
            $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);
            $title = Database::escape_string($title);
            $full_text = Database::escape_string($full_text);

            // Create the post
            $sql = "INSERT INTO $tbl_blogs_posts (c_id, title, full_text, date_creation, blog_id, author_id )
                    VALUES ($course_id, '$title', '$full_text', '$current_date', '$blog_id', ".$_user['user_id'].")";

            Database::query($sql);
            $last_post_id = Database::insert_id();

            if ($last_post_id) {
                $sql = "UPDATE $tbl_blogs_posts SET post_id = iid WHERE iid = $last_post_id";
                Database::query($sql);
            }

            if ($has_attachment) {
                $courseDir = $_course['path'].'/upload/blog';
                $sys_course_path = api_get_path(SYS_COURSE_PATH);
                $updir = $sys_course_path.$courseDir;

                // Try to add an extension to the file if it hasn't one
                $new_file_name = add_ext_on_mime(
                    stripslashes($_FILES['user_upload']['name']),
                    $_FILES['user_upload']['type']
                );

                // user's file name
                $file_name = $_FILES['user_upload']['name'];

                if (!filter_extension($new_file_name)) {
                    echo Display::return_message(get_lang('UplUnableToSaveFileFilteredExtension'), 'error');
                } else {
                    $new_file_name = uniqid('');
                    $new_path = $updir.'/'.$new_file_name;
                    $result = @move_uploaded_file($_FILES['user_upload']['tmp_name'], $new_path);
                    $comment = Database::escape_string($file_comment);
                    $file_name = Database::escape_string($file_name);
                    $size = intval($_FILES['user_upload']['size']);

                    // Storing the attachments if any
                    if ($result) {
                        $sql = "INSERT INTO $blog_table_attachment (c_id, filename,comment, path, post_id,size, blog_id,comment_id)
                            VALUES ($course_id, '$file_name', '$comment', '$new_file_name', $last_post_id, $size, $blog_id, 0)";
                        Database::query($sql);
                        $id = Database::insert_id();
                        if ($id) {
                            $sql = "UPDATE $blog_table_attachment SET id = iid WHERE iid = $id";
                            Database::query($sql);
                        }
                    }
                }
            }

            return $last_post_id;
        } else {
            echo Display::return_message(get_lang('UplNoFileUploaded'), 'error');

            return 0;
        }
    }

    /**
     * Edits a post in a given blog.
     *
     * @author Toon Keppens
     *
     * @param int    $post_id   The internal ID of the post to edit
     * @param string $title     The title
     * @param string $full_text The full post text
     * @param int    $blog_id   The internal ID of the blog in which the post is located
     */
    public static function editPost($post_id, $title, $full_text, $blog_id)
    {
        $table = Database::get_course_table(TABLE_BLOGS_POSTS);
        $course_id = api_get_course_int_id();
        $title = Database::escape_string($title);
        $full_text = Database::escape_string($full_text);
        $post_id = intval($post_id);
        $blog_id = intval($blog_id);

        // Create the post
        $sql = "UPDATE $table SET
                title = '$title',
                full_text = '$full_text'
                WHERE c_id = $course_id AND post_id = $post_id AND blog_id = $blog_id
                LIMIT 1";
        Database::query($sql);
    }

    /**
     * Deletes an article and its comments.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id The internal blog ID
     * @param int $post_id The internal post ID
     */
    public static function deletePost($blog_id, $post_id)
    {
        $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);
        $tbl_blogs_comments = Database::get_course_table(TABLE_BLOGS_COMMENTS);
        $tbl_blogs_rating = Database::get_course_table(TABLE_BLOGS_RATING);
        $blog_id = intval($blog_id);
        $post_id = intval($post_id);

        $course_id = api_get_course_int_id();

        // Delete ratings on this comment
        $sql = "DELETE FROM $tbl_blogs_rating
                WHERE c_id = $course_id AND blog_id = $blog_id AND item_id = $post_id AND rating_type = 'post'";
        Database::query($sql);

        // Delete the post
        $sql = "DELETE FROM $tbl_blogs_posts
                WHERE c_id = $course_id AND post_id = $post_id";
        Database::query($sql);

        // Delete the comments
        $sql = "DELETE FROM $tbl_blogs_comments
                WHERE c_id = $course_id AND post_id = $post_id AND blog_id = $blog_id";
        Database::query($sql);

        // Delete posts and attachments
        self::deleteAllBlogAttachments($blog_id, $post_id);
    }

    /**
     * Creates a comment on a post in a given blog.
     *
     * @author Toon Keppens
     *
     * @param string $title        The comment title
     * @param string $full_text    The full text of the comment
     * @param string $file_comment A comment on a file, if any was uploaded
     * @param int    $blog_id      The internal blog ID
     * @param int    $post_id      The internal post ID
     * @param int    $parent_id    The internal parent post ID
     * @param int    $task_id      The internal task ID (if any)
     */
    public static function createComment(
        $title,
        $full_text,
        $file_comment,
        $blog_id,
        $post_id,
        $parent_id,
        $task_id = null
    ) {
        $_user = api_get_user_info();
        $_course = api_get_course_info();
        $blog_table_attachment = Database::get_course_table(TABLE_BLOGS_ATTACHMENT);

        $upload_ok = true;
        $has_attachment = false;
        $current_date = api_get_utc_datetime();
        $course_id = api_get_course_int_id();

        if (!empty($_FILES['user_upload']['name'])) {
            $upload_ok = process_uploaded_file($_FILES['user_upload']);
            $has_attachment = true;
        }

        if ($upload_ok) {
            // Table Definition
            $tbl_blogs_comments = Database::get_course_table(TABLE_BLOGS_COMMENTS);
            $title = Database::escape_string($title);
            $full_text = Database::escape_string($full_text);
            $blog_id = intval($blog_id);
            $post_id = intval($post_id);
            $parent_id = intval($parent_id);
            $task_id = !empty($task_id) ? intval($task_id) : 'null';

            // Create the comment
            $sql = "INSERT INTO $tbl_blogs_comments (c_id, title, comment, author_id, date_creation, blog_id, post_id, parent_comment_id, task_id )
                    VALUES ($course_id, '$title', '$full_text', ".$_user['user_id'].", '$current_date', $blog_id, $post_id, $parent_id, '$task_id')";
            Database::query($sql);

            // Empty post values, or they are shown on the page again
            $last_id = Database::insert_id();

            if ($last_id) {
                $sql = "UPDATE $tbl_blogs_comments SET comment_id = iid WHERE iid = $last_id";
                Database::query($sql);

                if ($has_attachment) {
                    $courseDir = $_course['path'].'/upload/blog';
                    $sys_course_path = api_get_path(SYS_COURSE_PATH);
                    $updir = $sys_course_path.$courseDir;

                    // Try to add an extension to the file if it hasn't one
                    $new_file_name = add_ext_on_mime(
                        stripslashes($_FILES['user_upload']['name']),
                        $_FILES['user_upload']['type']
                    );

                    // user's file name
                    $file_name = Database::escape_string($_FILES['user_upload']['name']);

                    if (!filter_extension($new_file_name)) {
                        echo Display::return_message(get_lang('UplUnableToSaveFileFilteredExtension'), 'error');
                    } else {
                        $new_file_name = uniqid('');
                        $new_path = $updir.'/'.$new_file_name;
                        $result = @move_uploaded_file($_FILES['user_upload']['tmp_name'], $new_path);
                        $comment = Database::escape_string($file_comment);
                        $size = intval($_FILES['user_upload']['size']);

                        // Storing the attachments if any
                        if ($result) {
                            $sql = "INSERT INTO $blog_table_attachment (c_id, filename,comment, path, post_id,size,blog_id,comment_id)
                                VALUES ($course_id, '$file_name', '$comment', '$new_file_name', $post_id, $size, $blog_id, $last_id)";
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
    }

    /**
     * Deletes a comment from a blogpost.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id    The internal blog ID
     * @param int $post_id    The internal post ID
     * @param int $comment_id The internal comment ID
     */
    public static function deleteComment($blog_id, $post_id, $comment_id)
    {
        $tbl_blogs_comments = Database::get_course_table(TABLE_BLOGS_COMMENTS);
        $tbl_blogs_rating = Database::get_course_table(TABLE_BLOGS_RATING);
        $blog_id = intval($blog_id);
        $post_id = intval($post_id);
        $comment_id = intval($comment_id);
        $course_id = api_get_course_int_id();

        self::deleteAllBlogAttachments($blog_id, $post_id, $comment_id);

        // Delete ratings on this comment
        $sql = "DELETE FROM $tbl_blogs_rating
                WHERE
                    c_id = $course_id AND
                    blog_id = $blog_id AND
                    item_id = $comment_id AND
                    rating_type = 'comment'";
        Database::query($sql);

        // select comments that have the selected comment as their parent
        $sql = "SELECT comment_id FROM $tbl_blogs_comments
                WHERE c_id = $course_id AND parent_comment_id = $comment_id";
        $result = Database::query($sql);

        // Delete them recursively
        while ($comment = Database::fetch_array($result)) {
            self::deleteComment($blog_id, $post_id, $comment['comment_id']);
        }

        // Finally, delete the selected comment to
        $sql = "DELETE FROM $tbl_blogs_comments
                WHERE c_id = $course_id AND comment_id = $comment_id";
        Database::query($sql);
    }

    /**
     * Creates a new task in a blog.
     *
     * @author Toon Keppens
     *
     * @param int    $blog_id
     * @param string $title
     * @param string $description
     * @param string $articleDelete  Set to 'on' to register as 'article_delete' in tasks_permissions
     * @param string $articleEdit    Set to 'on' to register as 'article_edit' in tasks_permissions
     * @param string $commentsDelete Set to 'on' to register as 'article_comments_delete' in tasks permissions
     * @param string $color
     */
    public static function addTask(
        $blog_id,
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
        $blog_id = intval($blog_id);
        $title = Database::escape_string($title);
        $description = Database::escape_string($description);
        $color = Database::escape_string($color);

        // Create the task
        $sql = "INSERT INTO $tbl_blogs_tasks (c_id, blog_id, title, description, color, system_task)
                VALUES ($course_id , $blog_id, '$title', '$description', '$color', '0');";
        Database::query($sql);

        $task_id = Database::insert_id();

        if ($task_id) {
            $sql = "UPDATE $tbl_blogs_tasks SET task_id = iid WHERE iid = $task_id";
            Database::query($sql);
        }

        $tool = 'BLOG_'.$blog_id;

        if ('on' == $articleDelete) {
            $sql = "INSERT INTO $tbl_tasks_permissions ( c_id,  task_id, tool, action)
                    VALUES ($course_id, $task_id, '$tool', 'article_delete')";
            Database::query($sql);

            $id = Database::insert_id();

            if ($id) {
                $sql = "UPDATE $tbl_tasks_permissions SET id = iid WHERE iid = $id";
                Database::query($sql);
            }
        }

        if ('on' == $articleEdit) {
            $sql = "
                INSERT INTO $tbl_tasks_permissions (c_id, task_id, tool, action )
                VALUES ($course_id, $task_id, '$tool', 'article_edit')";
            Database::query($sql);
            $id = Database::insert_id();

            if ($id) {
                $sql = "UPDATE $tbl_tasks_permissions SET id = iid WHERE iid = $id";
                Database::query($sql);
            }
        }

        if ('on' == $commentsDelete) {
            $sql = "
                INSERT INTO $tbl_tasks_permissions (c_id, task_id, tool, action )
                VALUES ($course_id, $task_id, '$tool', 'article_comments_delete')";
            Database::query($sql);
            $id = Database::insert_id();

            if ($id) {
                $sql = "UPDATE $tbl_tasks_permissions SET id = iid WHERE iid = $id";
                Database::query($sql);
            }
        }
    }

    /**
     * Edit a task in a blog.
     *
     * @author Toon Keppens
     *
     * @param int    $blog_id        The internal blog ID
     * @param int    $task_id        The internal task ID
     * @param string $title          The task title
     * @param string $description    The task description
     * @param string $articleDelete  Set to 'on' to register as 'article_delete' in tasks_permissions
     * @param string $articleEdit    Set to 'on' to register as 'article_edit' in tasks_permissions
     * @param string $commentsDelete Set to 'on' to register as 'article_comments_delete' in tasks permissions
     * @param string $color          The color code
     */
    public static function editTask(
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
        $blog_id = intval($blog_id);
        $task_id = intval($task_id);
        $title = Database::escape_string($title);
        $description = Database::escape_string($description);
        $color = Database::escape_string($color);

        // Create the task
        $sql = "UPDATE $tbl_blogs_tasks SET
                    title = '$title',
                    description = '$description',
                    color = '$color'
                WHERE c_id = $course_id AND task_id = task_id LIMIT 1";
        Database::query($sql);

        $tool = 'BLOG_'.$blog_id;
        $sql = "DELETE FROM $tbl_tasks_permissions
                WHERE c_id = $course_id AND task_id = $task_id";
        Database::query($sql);

        if ($articleDelete == 'on') {
            $sql = "INSERT INTO $tbl_tasks_permissions ( c_id, task_id, tool, action)
                    VALUES ($course_id, $task_id, '$tool', 'article_delete')";
            Database::query($sql);
            $id = Database::insert_id();

            if ($id) {
                $sql = "UPDATE $tbl_tasks_permissions SET id = iid WHERE iid = $id";
                Database::query($sql);
            }
        }

        if ($articleEdit == 'on') {
            $sql = "INSERT INTO $tbl_tasks_permissions (c_id, task_id, tool, action)
                    VALUES ($course_id, $task_id, '$tool', 'article_edit')";
            Database::query($sql);
            $id = Database::insert_id();

            if ($id) {
                $sql = "UPDATE $tbl_tasks_permissions SET id = iid WHERE iid = $id";
                Database::query($sql);
            }
        }

        if ($commentsDelete == 'on') {
            $sql = "INSERT INTO $tbl_tasks_permissions (c_id, task_id, tool, action)
                    VALUES ($course_id, $task_id, '$tool', 'article_comments_delete')";
            Database::query($sql);
            $id = Database::insert_id();

            if ($id) {
                $sql = "UPDATE $tbl_tasks_permissions SET id = iid WHERE iid = $id";
                Database::query($sql);
            }
        }
    }

    /**
     * Deletes a task from a blog.
     *
     * @param int $blog_id
     * @param int $task_id
     */
    public static function deleteTask($blog_id, $task_id)
    {
        $table = Database::get_course_table(TABLE_BLOGS_TASKS);
        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $task_id = intval($task_id);

        // Delete posts
        $sql = "DELETE FROM $table
                WHERE c_id = $course_id AND blog_id = $blog_id AND task_id = $task_id";
        Database::query($sql);
    }

    /**
     * Deletes an assigned task from a blog.
     *
     * @param int $blog_id
     * @param int $task_id
     * @param int $user_id
     */
    public static function deleteAssignedTask($blog_id, $task_id, $user_id)
    {
        $table = Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER);
        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $task_id = intval($task_id);
        $user_id = intval($user_id);

        // Delete posts
        $sql = "DELETE FROM $table
                WHERE
                    c_id = $course_id AND
                    blog_id = $blog_id AND
                    task_id = $task_id AND
                    user_id = $user_id";
        Database::query($sql);
    }

    /**
     * Get personal task list.
     *
     * @author Toon Keppens
     *
     * @return string Returns an unsorted list (<ul></ul>) with the users' tasks
     */
    public static function getPersonalTasksList()
    {
        $_user = api_get_user_info();
        $html = null;
        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);
        $tbl_blogs_tasks_rel_user = Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER);
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);

        $course_id = api_get_course_int_id();
        $blog_id = intval($_GET['blog_id']);
        $cidReq = api_get_cidreq();

        if ($_user['user_id']) {
            $sql = "SELECT task_rel_user.*, task.title, blog.blog_name
                    FROM $tbl_blogs_tasks_rel_user task_rel_user
                    INNER JOIN $tbl_blogs_tasks task
                    ON task_rel_user.task_id = task.task_id
                    INNER JOIN $tbl_blogs blog
                    ON task_rel_user.blog_id = blog.blog_id
                    AND blog.blog_id = $blog_id
                    WHERE
                        task.c_id = $course_id AND
                        blog.c_id = $course_id AND
                        task_rel_user.c_id = $course_id AND
                        task_rel_user.user_id = ".$_user['user_id']."
                    ORDER BY target_date ASC";
            $result = Database::query($sql);
            $url = api_get_path(WEB_CODE_PATH).'blog/blog.php?'.$cidReq.'&action=execute_task';
            if (Database::num_rows($result) > 0) {
                $html .= '<ul>';
                while ($mytask = Database::fetch_array($result)) {
                    $html .= '<li>
                            <a
                            href="'.$url.'&blog_id='.$mytask['blog_id'].'&task_id='.intval($mytask['task_id']).'"
                            title="[Blog: '.stripslashes($mytask['blog_name']).'] '.
                        get_lang('ExecuteThisTask').'">'.
                        stripslashes($mytask['title']).'</a></li>';
                }
                $html .= '<ul>';
            } else {
                $html .= get_lang('NoTasks');
            }
        } else {
            $html .= get_lang('NoTasks');
        }

        return $html;
    }

    /**
     * Changes the visibility of a blog.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id
     */
    public static function changeBlogVisibility($blog_id)
    {
        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);
        $tbl_tool = Database::get_course_table(TABLE_TOOL_LIST);
        $course_id = api_get_course_int_id();

        // Get blog properties
        $sql = "SELECT blog_name, visibility FROM $tbl_blogs
                WHERE c_id = $course_id AND blog_id='".(int) $blog_id."'";
        $result = Database::query($sql);
        $blog = Database::fetch_array($result);
        $visibility = $blog['visibility'];
        $title = $blog['blog_name'];

        if ($visibility == 1) {
            // Change visibility state, remove from course home.
            $sql = "UPDATE $tbl_blogs SET visibility = '0'
                    WHERE c_id = $course_id AND blog_id ='".(int) $blog_id."' LIMIT 1";
            Database::query($sql);

            $sql = "DELETE FROM $tbl_tool
                    WHERE c_id = $course_id AND name = '".Database::escape_string($title)."'
                    LIMIT 1";
            Database::query($sql);
        } else {
            // Change visibility state, add to course home.
            $sql = "UPDATE $tbl_blogs SET visibility = '1'
                    WHERE c_id = $course_id AND blog_id ='".(int) $blog_id."' LIMIT 1";
            Database::query($sql);

            $sql = "INSERT INTO $tbl_tool (c_id, name, link, image, visibility, admin, address, added_tool, target)
                    VALUES ($course_id, '".Database::escape_string($title)."', 'blog/blog.php?blog_id=".(int) $blog_id."', 'blog.gif', '1', '0', 'pastillegris.gif', '0', '_self')";
            Database::query($sql);
            $id = Database::insert_id();

            if ($id) {
                $sql = "UPDATE $tbl_tool SET id = iid WHERE iid = $id";
                Database::query($sql);
            }
        }
    }

    /**
     * Display the search results.
     *
     * @param int    $blog_id
     * @param string $query_string
     *
     * @return string|array
     */
    public static function getSearchResults($blog_id, $query_string)
    {
        $query_string_parts = explode(' ', $query_string);
        $query_string = [];
        foreach ($query_string_parts as $query_part) {
            $query_part = Database::escape_string($query_part);
            $query_string[] = " full_text LIKE '%".$query_part."%' OR title LIKE '%".$query_part."%' ";
        }
        $query_string = '('.implode('OR', $query_string).')';

        // Display the posts
        return self::getPosts($blog_id, $query_string);
    }

    /**
     * Shows the posts of a blog.
     *
     * @author Toon Keppens
     *
     * @param int    $blog_id
     * @param string $filter
     * @param int    $max_number_of_posts
     *
     * @return string|array
     */
    public static function getPosts($blog_id, $filter = '1=1', $max_number_of_posts = 20)
    {
        $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);
        $tbl_blogs_comments = Database::get_course_table(TABLE_BLOGS_COMMENTS);
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);

        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $max_number_of_posts = intval($max_number_of_posts);
        // Get posts and authors
        $sql = "SELECT post.*, user.lastname, user.firstname, user.username
                FROM $tbl_blogs_posts post
                INNER JOIN $tbl_users user
                ON post.author_id = user.user_id
                WHERE
                    post.blog_id = $blog_id AND
                    post.c_id = $course_id AND
                    $filter
                ORDER BY post_id DESC
                LIMIT 0, $max_number_of_posts";
        $result = Database::query($sql);

        // Display
        if (Database::num_rows($result) > 0) {
            $limit = 200;
            $listArticle = [];
            while ($blog_post = Database::fetch_array($result)) {
                // Get number of comments
                $sql = "SELECT COUNT(1) as number_of_comments
                        FROM $tbl_blogs_comments
                        WHERE
                            c_id = $course_id AND
                            blog_id = $blog_id AND
                            post_id = ".$blog_post['post_id'];
                $tmp = Database::query($sql);
                $blog_post_comments = Database::fetch_array($tmp);

                $fileArray = self::getBlogAttachments($blog_id, $blog_post['post_id'], 0);
                $scoreRanking = self::displayRating(
                    'post',
                    $blog_id,
                    $blog_post['post_id']
                );

                // Prepare data
                $article = [
                    'id_blog' => $blog_post['blog_id'],
                    'c_id' => $blog_post['c_id'],
                    'id_post' => $blog_post['post_id'],
                    'id_autor' => $blog_post['author_id'],
                    'autor' => $blog_post['firstname'].' '.$blog_post['lastname'],
                    'username' => $blog_post['username'],
                    'title' => Security::remove_XSS($blog_post['title']),
                    'extract' => self::getPostExtract($blog_post['full_text'], BLOG_MAX_PREVIEW_CHARS),
                    'content' => Security::remove_XSS($blog_post['full_text']),
                    'post_date' => Display::dateToStringAgoAndLongDate($blog_post['date_creation']),
                    'n_comments' => $blog_post_comments['number_of_comments'],
                    'files' => $fileArray,
                    'score_ranking' => $scoreRanking,
                ];

                $listArticle[] = $article;
            }

            return $listArticle;
        } else {
            if ($filter == '1=1') {
                return get_lang('NoArticles');
            } else {
                return get_lang('NoArticleMatches');
            }
        }
    }

    /**
     * Display posts from a certain date.
     *
     * @param int    $blog_id
     * @param string $query_string
     *
     * @return string|array
     */
    public static function getDailyResults($blog_id, $query_string)
    {
        $date = explode('-', $query_string);
        $query_string = '
            DAYOFMONTH(date_creation) ='.intval($date[2]).' AND
            MONTH(date_creation) ='.intval($date[1]).' AND
            YEAR(date_creation) ='.intval($date[0]);
        $list = self::getPosts($blog_id, $query_string);

        return $list;
    }

    /**
     * Displays a post and his comments.
     *
     * @param int $blog_id
     * @param int $post_id
     *
     * @return array
     */
    public static function getSinglePost($blog_id, $post_id)
    {
        $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);
        $tbl_blogs_comments = Database::get_course_table(TABLE_BLOGS_COMMENTS);
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);
        $listComments = null;
        global $charset;

        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $post_id = intval($post_id);

        // Get posts and author
        $sql = "SELECT post.*, user.lastname, user.firstname, user.username
                FROM $tbl_blogs_posts post
                    INNER JOIN $tbl_users user
                    ON post.author_id = user.user_id
                WHERE
                    post.c_id = $course_id AND
                    post.blog_id = $blog_id AND
                    post.post_id = $post_id
                ORDER BY post_id DESC";
        $result = Database::query($sql);
        $blog_post = Database::fetch_array($result);

        // Get number of comments
        $sql = "SELECT COUNT(1) as number_of_comments
                FROM $tbl_blogs_comments
                WHERE c_id = $course_id AND blog_id = $blog_id AND post_id = $post_id";
        $result = Database::query($sql);
        $blog_post_comments = Database::fetch_array($result);
        $blogActions = null;

        $task_id = (isset($_GET['task_id']) && is_numeric($_GET['task_id'])) ? intval($_GET['task_id']) : 0;

        // Display comments if there are any
        if ($blog_post_comments['number_of_comments'] > 0) {
            $listComments = self::getThreadedComments(0, 0, $blog_id, $post_id, $task_id);
        }
        // Display comment form
        if (api_is_allowed('BLOG_'.$blog_id, 'article_comments_add')) {
            $formComments = self::displayCommentCreateForm($blog_id, $post_id, $blog_post['title'], false);
        }
        // Prepare data
        $fileArray = self::getBlogAttachments($blog_id, $post_id);

        $post_text = make_clickable(stripslashes($blog_post['full_text']));
        $post_text = stripslashes($post_text);

        $blogUrl = api_get_path(WEB_CODE_PATH).'blog/blog.php?blog_id='.$blog_id.
            '&post_id='.$post_id.'&article_id='.$blog_post['post_id'].'&task_id='.$task_id.'&'.api_get_cidreq();

        if (api_is_allowed('BLOG_'.$blog_id, 'article_edit', $task_id)) {
            $blogActions .= '<a
                class="btn btn-default"
                href="'.$blogUrl.'&action=edit_post"
                title="'.get_lang('EditThisPost').'">';
            $blogActions .= Display::return_icon('edit.png', get_lang('Edit'), null, ICON_SIZE_TINY);
            $blogActions .= '</a>';
        }

        if (api_is_allowed('BLOG_'.$blog_id, 'article_delete', $task_id)) {
            $blogActions .= '<a
                class="btn btn-default"
                href="'.$blogUrl.'&action=view_post&do=delete_article"
                title="'.get_lang(
                    'DeleteThisArticle'
                ).'" onclick="javascript:if(!confirm(\''.addslashes(
                    api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES, $charset)
                ).'\')) return false;">';
            $blogActions .= Display::return_icon(
                'delete.png',
                get_lang('Delete'),
                null,
                ICON_SIZE_TINY
            );
            $blogActions .= '</a>';
        }
        $scoreRanking = self::displayRating('post', $blog_id, $post_id);
        $article = [
            'id_blog' => $blog_post['blog_id'],
            'c_id' => $blog_post['c_id'],
            'id_post' => $blog_post['post_id'],
            'id_author' => $blog_post['author_id'],
            'author' => $blog_post['firstname'].' '.$blog_post['lastname'],
            'username' => $blog_post['username'],
            'title' => Security::remove_XSS($blog_post['title']),
            'extract' => api_get_short_text_from_html(
                Security::remove_XSS($blog_post['full_text']),
                400
            ),
            'content' => $post_text,
            'post_date' => Display::dateToStringAgoAndLongDate($blog_post['date_creation']),
            'n_comments' => $blog_post_comments['number_of_comments'],
            'files' => $fileArray,
            'id_task' => $task_id,
            'comments' => $listComments,
            'form_html' => $formComments,
            'actions' => $blogActions,
            'score_ranking' => (int) $scoreRanking,
            'frm_rating' => api_is_allowed('BLOG_'.$blog_id, 'article_rate')
                ? self::displayRatingCreateForm('post', $blog_id, $post_id)
                : null,
        ];

        return $article;
    }

    /**
     * This functions gets all replies to a post, threaded.
     *
     * @param int $current
     * @param int $current_level
     * @param int $blog_id
     * @param int $post_id
     * @param int $task_id
     *
     * @return array
     */
    public static function getThreadedComments(
        $current,
        $current_level,
        $blog_id,
        $post_id,
        $task_id = 0
    ) {
        $tbl_blogs_comments = Database::get_course_table(TABLE_BLOGS_COMMENTS);
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
        $charset = api_get_system_encoding();
        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $post_id = intval($post_id);
        $task_id = intval($task_id);
        $listComments = [];
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
                    comments.blog_id = $blog_id AND
                    comments.post_id = $post_id";

        $result = Database::query($sql);
        $html = null;
        $cidReq = api_get_cidreq();
        while ($comment = Database::fetch_array($result)) {
            $commentActions = null;
            $ratingSelect = null;
            $comment_text = make_clickable(stripslashes($comment['comment']));
            $comment_text = Security::remove_XSS($comment_text);
            $commentActions .= Display::toolbarButton(
                get_lang('ReplyToThisComment'),
                '#',
                'reply',
                'default',
                ['data-id' => $comment['iid'], 'role' => 'button', 'class' => 'btn-reply-to'],
                false
            );

            if (api_is_allowed('BLOG_'.$blog_id, 'article_comments_delete', $task_id)) {
                $commentActions .= ' <a
                class="btn btn-default"
                href="blog.php?'.$cidReq.'&action=view_post&blog_id='.$blog_id.'&post_id='.$post_id.'&do=delete_comment&comment_id='.$comment['comment_id'].'&task_id='.$task_id.'"
                title="'.get_lang(
                        'DeleteThisComment'
                    ).'" onclick="javascript:if(!confirm(\''.addslashes(
                        api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES, $charset)
                    ).'\')) return false;">';
                $commentActions .= Display::returnFontAwesomeIcon('trash');
                $commentActions .= '</a>';
            }
            if (api_is_allowed('BLOG_'.$blog_id, 'article_comments_rate')) {
                $ratingSelect = self::displayRatingCreateForm(
                    'comment',
                    $blog_id,
                    $post_id,
                    $comment['comment_id']
                );
            }

            $scoreRanking = self::displayRating(
                'comment',
                $blog_id,
                $comment['comment_id']
            );

            // Files
            $fileArray = self::getBlogAttachments(
                $blog_id,
                $post_id,
                $comment['comment_id']
            );
            $userInfo = api_get_user_info($comment['author_id']);
            $comments = [
                'iid' => $comment['iid'],
                'id_comment' => $comment['comment_id'],
                'id_curso' => $comment['c_id'],
                'title' => Security::remove_XSS($comment['title']),
                'content' => $comment_text,
                'id_author' => $comment['author_id'],
                'comment_date' => Display::dateToStringAgoAndLongDate($comment['date_creation']),
                'id_blog' => $comment['blog_id'],
                'id_post' => $comment['post_id'],
                'id_task' => $comment['task_id'],
                'id_parent' => $comment['parent_comment_id'],
                'user_info' => $userInfo,
                'color' => $comment['color'],
                'files' => $fileArray,
                'actions' => $commentActions,
                'form_ranking' => $ratingSelect,
                'score_ranking' => $scoreRanking,
                'comments' => self::getThreadedComments(
                    $comment['iid'],
                    $next_level,
                    $blog_id,
                    $post_id
                ),
            ];

            $listComments[] = $comments;
        }

        return $listComments;
    }

    /**
     * Shows the rating form if not already rated by that user.
     *
     * @author Toon Keppens
     *
     * @param string $type
     * @param int    $blog_id
     * @param int    $post_id
     * @param int    $comment_id
     *
     * @return string
     */
    public static function displayRatingCreateForm($type, $blog_id, $post_id, $comment_id = null)
    {
        $_user = api_get_user_info();
        $tbl_blogs_rating = Database::get_course_table(TABLE_BLOGS_RATING);
        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $post_id = intval($post_id);
        $comment_id = isset($comment_id) ? intval($comment_id) : null;
        $type = Database::escape_string($type);
        $html = null;

        if ($type === 'post') {
            // Check if the user has already rated this post
            $sql = "SELECT rating_id FROM $tbl_blogs_rating
                    WHERE c_id = $course_id AND
                    blog_id = $blog_id
                    AND item_id = $post_id
                    AND rating_type = '$type'
                    AND user_id = ".$_user['user_id'];
            $result = Database::query($sql);
            // Add rating
            $url = api_get_path(WEB_CODE_PATH).'blog/blog.php?'.api_get_cidreq();
            if (Database::num_rows($result) == 0) {
                $html .= '<form
                    class="form-horizontal"
                    method="get"
                    action="'.$url.'"
                    id="frm_rating_'.$type.'_'.$post_id.'"
                    name="frm_rating_'.$type.'_'.$post_id.'">';
                $html .= '<div class="form-group">';
                $html .= '<label class="col-sm-3 control-label">'.get_lang('RateThis').'</label>';
                $html .= '<div class="col-sm-9">';
                $html .= '<select
                    class="selectpicker"
                    name="rating"
                    onchange="document.forms[\'frm_rating_'.$type.'_'.$post_id.'\'].submit()">
                        <option value="">-</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                    </select>
                    <input type="hidden" name="action" value="view_post" />
                    <input type="hidden" name="type" value="'.$type.'" />
                    <input type="hidden" name="do" value="rate" />
                    <input type="hidden" name="blog_id" value="'.$blog_id.'" />
                    <input type="hidden" name="post_id" value="'.$post_id.'" />';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</form>';

                return $html;
            } else {
                return '';
            }
        }

        if ($type = 'comment') {
            // Check if the user has already rated this comment
            $sql = "SELECT rating_id FROM $tbl_blogs_rating
                    WHERE c_id = $course_id AND blog_id = $blog_id
                    AND item_id = $comment_id
                    AND rating_type = '$type'
                    AND user_id = ".$_user['user_id'];
            $result = Database::query($sql);
            $url = api_get_path(WEB_CODE_PATH).'blog/blog.php?'.api_get_cidreq();
            if (Database::num_rows($result) == 0) {
                $html .= '<form
                    class="form-horizontal"
                    method="get"
                    action="'.$url.'"
                    id="frm_rating_'.$type.'_'.$comment_id.'" name="frm_rating_'.$type.'_'.$comment_id.'">';
                $html .= '<div class="form-group">';
                $html .= '<label class="col-sm-3 control-label">'.get_lang('RateThis').'</label>';
                $html .= '<div class="col-sm-9">';
                $html .= '<select
                        class="selectpicker"
                        name="rating"
                        onchange="document.forms[\'frm_rating_'.$type.'_'.$comment_id.'\'].submit()">';
                $html .= '<option value="">-</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                            <option value="8">8</option>
                            <option value="9">9</option>
                            <option value="10">10</option>
                         </select>
                         <input type="hidden" name="action" value="view_post" />
                        <input type="hidden" name="type" value="'.$type.'" />
                        <input type="hidden" name="do" value="rate" />
                        <input type="hidden" name="blog_id" value="'.$blog_id.'" />
                        <input type="hidden" name="post_id" value="'.$post_id.'" />
                        <input type="hidden" name="comment_id" value="'.$comment_id.'" />';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</form>';

                return $html;
            } else {
                return '';
            }
        }
    }

    /**
     * Shows the rating of user.
     *
     * @param string $type
     * @param int    $blog_id
     * @param int    $item_id
     *
     * @return float
     */
    public static function displayRating($type, $blog_id, $item_id)
    {
        $table = Database::get_course_table(TABLE_BLOGS_RATING);
        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $item_id = intval($item_id);
        $type = Database::escape_string($type);

        // Calculate rating
        $sql = "SELECT AVG(rating) as rating FROM $table
                WHERE
                    c_id = $course_id AND
                    blog_id = $blog_id AND
                    item_id = $item_id AND
                    rating_type = '$type'";
        $result = Database::query($sql);
        $result = Database::fetch_array($result);

        return round($result['rating'], 2);
    }

    /**
     * Displays the form to create a new post.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id
     * @param int $post_id
     *
     * @return string HTML form
     */
    public static function displayCommentCreateForm($blog_id, $post_id)
    {
        $taskId = !empty($_GET['task_id']) ? intval($_GET['task_id']) : 0;
        $blog_id = intval($blog_id);
        $post_id = intval($post_id);

        $form = new FormValidator(
            'add_post',
            'post',
            api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
                'action' => 'view_post',
                'blog_id' => $blog_id,
                'post_id' => $post_id,
                'task_id' => $taskId,
            ]),
            null,
            ['enctype' => 'multipart/form-data']
        );

        $header = $taskId ? get_lang('ExecuteThisTask') : get_lang('AddNewComment');
        $form->addHeader($header);
        $form->addText('title', get_lang('Title'));

        $config = [];
        if (!api_is_allowed_to_edit()) {
            $config['ToolbarSet'] = 'ProjectComment';
        } else {
            $config['ToolbarSet'] = 'ProjectCommentStudent';
        }
        $form->addHtmlEditor(
            'comment',
            get_lang('Comment'),
            false,
            false,
            $config
        );
        $form->addFile('user_upload', get_lang('AddAnAttachment'));
        $form->addTextarea('post_file_comment', get_lang('FileComment'));
        $form->addHidden('action', null);
        $form->addHidden('comment_parent_id', 0);
        $form->addHidden('task_id', $taskId);
        $form->addButton('save', get_lang('Save'));

        if ($form->validate()) {
            $values = $form->exportValues();

            self::createComment(
                $values['title'],
                $values['comment'],
                $values['post_file_comment'],
                $blog_id,
                $post_id,
                $values['comment_parent_id'],
                $taskId
            );

            Display::addFlash(
                Display::return_message(get_lang('CommentAdded'), 'success')
            );

            header(
                'Location: '
                .api_get_self()
                .'?'
                .api_get_cidreq()
                .'&'
                .http_build_query([
                    'blog_id' => $blog_id,
                    'post_id' => $post_id,
                    'action' => 'view_post',
                    'task_id' => $taskId,
                ])
            );
            exit;
        }

        return $form->returnForm();
    }

    /**
     * Adds rating to a certain post or comment.
     *
     * @author Toon Keppens
     *
     * @param string $type
     * @param int    $blog_id
     * @param int    $item_id
     * @param int    $rating
     *
     * @return bool success
     */
    public static function addRating($type, $blog_id, $item_id, $rating)
    {
        $_user = api_get_user_info();
        $tbl_blogs_rating = Database::get_course_table(TABLE_BLOGS_RATING);
        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $item_id = intval($item_id);
        $type = Database::escape_string($type);
        $rating = Database::escape_string($rating);

        // Check if the user has already rated this post/comment
        $sql = "SELECT rating_id FROM $tbl_blogs_rating
                WHERE
                    c_id = $course_id AND
                    blog_id = $blog_id AND
                    item_id = $item_id AND
                    rating_type = '$type' AND
                    user_id = ".$_user['user_id'];
        $result = Database::query($sql);

        // Add rating
        if (Database::num_rows($result) == 0) {
            $sql = "INSERT INTO $tbl_blogs_rating (c_id, blog_id, rating_type, item_id, user_id, rating )
                    VALUES ($course_id, $blog_id, '$type', $item_id, ".$_user['user_id'].", '$rating')";
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
     * Displays the form to create a new post.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id
     *
     * @return string
     */
    public static function displayPostCreateForm($blog_id)
    {
        $blog_id = intval($blog_id);
        if (!api_is_allowed('BLOG_'.$blog_id, 'article_add')) {
            api_not_allowed();
        }

        $form = new FormValidator(
            'add_post',
            'post',
            api_get_path(WEB_CODE_PATH)."blog/blog.php?action=new_post&blog_id=".$blog_id."&".api_get_cidreq(),
            null,
            ['enctype' => 'multipart/form-data']
        );
        $form->addHidden('post_title_edited', 'false');
        $form->addHeader(get_lang('NewPost'));
        $form->addText('title', get_lang('Title'));
        $config = [];
        $config['ToolbarSet'] = !api_is_allowed_to_edit() ? 'ProjectStudent' : 'Project';
        $form->addHtmlEditor('full_text', get_lang('Content'), false, false, $config);
        $form->addFile('user_upload', get_lang('AddAnAttachment'));
        $form->addTextarea('post_file_comment', get_lang('FileComment'));
        $form->addHidden('new_post_submit', 'true');
        $form->addButton('save', get_lang('Save'));

        if ($form->validate()) {
            $values = $form->exportValues();

            $postId = self::createPost(
                $values['title'],
                $values['full_text'],
                $values['post_file_comment'],
                $blog_id
            );

            if ($postId) {
                Display::addFlash(
                    Display::return_message(get_lang('BlogAdded'), 'success')
                );

                header('Location: '.api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
                    'action' => 'view_post',
                    'blog_id' => $blog_id,
                    'post_id' => $postId,
                ]));
                exit;
            }
        }

        return $form->returnForm();
    }

    /**
     * Displays the form to edit a post.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id
     * @param int $post_id
     *
     * @return string
     */
    public static function displayPostEditForm($blog_id, $post_id)
    {
        $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);

        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $post_id = intval($post_id);

        // Get posts and author
        $sql = "SELECT post.*, user.lastname, user.firstname
                FROM $tbl_blogs_posts post
                INNER JOIN $tbl_users user ON post.author_id = user.user_id
                WHERE
                post.c_id 			= $course_id AND
                post.blog_id 		= $blog_id
                AND post.post_id	= $post_id
                ORDER BY post_id DESC";
        $result = Database::query($sql);
        $blog_post = Database::fetch_array($result);

        $form = new FormValidator(
            'edit_post',
            'post',
            api_get_path(WEB_CODE_PATH).
            'blog/blog.php?action=edit_post&post_id='.intval($_GET['post_id']).'&blog_id='.intval($blog_id).
            '&article_id='.intval($_GET['article_id']).'&task_id='.intval($_GET['task_id']).'&'.api_get_cidreq()
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

        return $form->returnForm();
    }

    /**
     * Displays a list of tasks in this blog.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id
     *
     * @return string
     */
    public static function displayTasksList($blog_id)
    {
        global $charset;
        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $html = '';
        if (api_is_allowed('BLOG_'.$blog_id, 'article_add')) {
            $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
            $counter = 0;
            global $color2;

            $html .= '<div class="actions">';
            $html .= '<a href="'.api_get_self().'?action=manage_tasks&blog_id='.$blog_id.'&do=add&'.api_get_cidreq().'">';
            $html .= Display::return_icon('blog_newtasks.gif', get_lang('AddTasks'));
            $html .= get_lang('AddTasks').'</a> ';
            $html .= '<a href="'.api_get_self().'?action=manage_tasks&blog_id='.$blog_id.'&do=assign&'.api_get_cidreq().'">';
            $html .= Display::return_icon('blog_task.gif', get_lang('AssignTasks'));
            $html .= get_lang('AssignTasks').'</a>';
            $html .= Display::url(
                Display::return_icon('blog_admin_users.png', get_lang('RightsManager')),
                api_get_self().'?'.http_build_query([
                    'action' => 'manage_rights',
                    'blog_id' => $blog_id,
                ]),
                ['title' => get_lang('ManageRights')]
            );

            $html .= '</div>';

            $html .= '<span class="blogpost_title">'.get_lang('TaskList').'</span><br />';
            $html .= "<table class=\"table table-hover table-striped data_table\">";
            $html .= "<tr bgcolor=\"$color2\" align=\"center\" valign=\"top\">"
                ."<th width='240'><b>".get_lang('Title')."</b></th>"
                ."<th><b>".get_lang('Description')."</b></th>"
                ."<th><b>".get_lang('Color')."</b></th>"
                ."<th width='50'><b>".get_lang('Modify')."</b></th></tr>";

            $sql = " SELECT
                        blog_id,
                        task_id,
                        blog_id,
                        title,
                        description,
                        color,
                        system_task
                    FROM $tbl_blogs_tasks
                    WHERE c_id = $course_id AND blog_id = $blog_id
                    ORDER BY system_task, title";
            $result = Database::query($sql);

            while ($task = Database::fetch_array($result)) {
                $counter++;
                $css_class = (($counter % 2) == 0) ? "row_odd" : "row_even";
                $delete_icon = $task['system_task'] == '1' ? "delete_na.png" : "delete.png";
                $delete_title = $task['system_task'] == '1' ? get_lang('DeleteSystemTask') : get_lang('DeleteTask');
                $delete_link = $task['system_task'] == '1' ? '#' : api_get_self().'?action=manage_tasks&blog_id='.$task['blog_id'].'&do=delete&task_id='.$task['task_id'].'&'.api_get_cidreq();
                $delete_confirm = ($task['system_task'] == '1') ? '' : 'onclick="javascript:if(!confirm(\''.addslashes(
                        api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES, $charset)
                    ).'\')) return false;"';

                $html .= '<tr class="'.$css_class.'" valign="top">';
                $html .= '<td width="240">'.Security::remove_XSS($task['title']).'</td>';
                $html .= '<td>'.Security::remove_XSS($task['description']).'</td>';
                $html .= '<td><span style="background-color: #'.$task['color'].'">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></td>';
                $html .= '<td width="50">';
                $html .= '<a href="'.api_get_self().'?action=manage_tasks&blog_id='.$task['blog_id'].'&do=edit&task_id='.$task['task_id'].'&'.api_get_cidreq().'">';
                $html .= Display::return_icon('edit.png', get_lang('EditTask'));
                $html .= "</a>";
                $html .= '<a href="'.$delete_link.'"';
                $html .= $delete_confirm;
                $html .= '>';
                $html .= Display::return_icon($delete_icon, $delete_title);
                $html .= "</a>";
                $html .= '</td>';
                $html .= '</tr>';
            }
            $html .= "</table>";
        }

        return $html;
    }

    /**
     * Displays a list of tasks assigned to a user in this blog.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id
     *
     * @return string
     */
    public static function displayAssignedTasksList($blog_id)
    {
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
        $tbl_blogs_tasks_rel_user = Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER);
        $counter = 0;
        global $charset, $color2;

        $return = '<span class="blogpost_title">'.get_lang('AssignedTasks').'</span><br />';
        $return .= "<table class=\"table table-hover table-striped data_table\">";
        $return .= "<tr bgcolor=\"$color2\" align=\"center\" valign=\"top\">"
            ."<th width='240'><b>".get_lang('Member')."</b></th>"
            ."<th><b>".get_lang('Task')."</b></th>"
            ."<th><b>".get_lang('Description')."</b></th>"
            ."<th><b>".get_lang('TargetDate')."</b></th>"
            ."<th width='50'><b>".get_lang('Modify')."</b></th>"
            ."</tr>";

        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);

        $sql = "SELECT task_rel_user.*, task.title, user.firstname, user.lastname, user.username, task.description, task.system_task, task.blog_id, task.task_id
                FROM $tbl_blogs_tasks_rel_user task_rel_user
                INNER JOIN $tbl_blogs_tasks task
                ON task_rel_user.task_id = task.task_id
                INNER JOIN $tbl_users user
                ON task_rel_user.user_id = user.user_id
                WHERE
                    task_rel_user.c_id = $course_id AND
                    task.c_id = $course_id AND
                    task_rel_user.blog_id = $blog_id
                ORDER BY target_date ASC";
        $result = Database::query($sql);

        while ($assignment = Database::fetch_array($result)) {
            $counter++;
            $css_class = (($counter % 2) == 0) ? "row_odd" : "row_even";
            $delete_icon = ($assignment['system_task'] == '1') ? "delete_na.png" : "delete.png";
            $delete_title = ($assignment['system_task'] == '1') ? get_lang('DeleteSystemTask') : get_lang('DeleteTask');
            $username = api_htmlentities(sprintf(get_lang('LoginX'), $assignment['username']), ENT_QUOTES);

            $return .= '<tr class="'.$css_class.'" valign="top">';
            $return .= '<td width="240">'.Display::tag(
                'span',
                api_get_person_name($assignment['firstname'], $assignment['lastname']),
                ['title' => $username]
            ).'</td>';
            $return .= '<td>'.Security::remove_XSS($assignment['title']).'</td>';
            $return .= '<td>'.Security::remove_XSS($assignment['description']).'</td>';
            $return .= '<td>'.$assignment['target_date'].'</td>';
            $return .= '<td width="50">';
            $return .= '<a
                href="'.api_get_self().'?action=manage_tasks&blog_id='.$assignment['blog_id'].'&do=edit_assignment&task_id='.$assignment['task_id'].'&user_id='.$assignment['user_id'].'&'.api_get_cidreq().'">';
            $return .= Display::return_icon('edit.png', get_lang('EditTask'));
            $return .= "</a>";
            $return .= '<a
                href="'.api_get_self().'?action=manage_tasks&blog_id='.$assignment['blog_id'].'&do=delete_assignment&task_id='.$assignment['task_id'].'&user_id='.$assignment['user_id'].'&'.api_get_cidreq().'" ';
            $return .= 'onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES, $charset)).'\')) return false;"';
            $return .= Display::return_icon($delete_icon, $delete_title);
            $return .= "</a>";
            $return .= '</td>';
            $return .= '</tr>';
        }
        $return .= "</table>";

        return $return;
    }

    /**
     * Displays new task form.
     *
     * @todo use FormValidator
     *
     * @author Toon Keppens
     *
     * @param int $blog_id
     *
     * @return string HTML form
     */
    public static function displayTaskCreateForm($blog_id)
    {
        $blog_id = intval($blog_id);

        $colors = [
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
            '000000',
        ];

        $url = api_get_path(WEB_CODE_PATH).'blog/blog.php?'.api_get_cidreq().'&action=manage_tasks';
        $return = '<form name="add_task" method="post" action="'.$url.'&blog_id='.$blog_id.'">';
        $return .= '<legend>'.get_lang('AddTask').'</legend>';
        $return .= '	<div class="control-group">
                    <label class="control-label">
                        <span class="form_required">*</span>'.get_lang('Title').'
                    </label>
                    <div class="controls">
                        <input name="task_name" type="text" size="70" />
                    </div>
                </div>';

        // task comment
        $return .= '	<div class="control-group">
                    <label class="control-label">
                        '.get_lang('Description').'
                    </label>
                    <div class="controls">
                        <textarea name="task_description" cols="45"></textarea>
                    </div>
                </div>';

        // task management
        $return .= '	<div class="control-group">
                    <label class="control-label">
                        '.get_lang('TaskManager').'
                    </label>
                    <div class="controls">';
        $return .= '<table class="table table-hover table-striped data_table" cellspacing="0" style="border-collapse:collapse; width:446px;">';
        $return .= '<tr>';
        $return .= '<th colspan="2" style="width:223px;">'.get_lang('ArticleManager').'</th>';
        $return .= '<th width:223px;>'.get_lang('CommentManager').'</th>';
        $return .= '</tr>';
        $return .= '<tr>';
        $return .= '<th style="width:111px;"><label for="articleDelete">'.get_lang('Delete').'</label></th>';
        $return .= '<th style="width:112px;"><label for="articleEdit">'.get_lang('Edit').'</label></th>';
        $return .= '<th style="width:223px;"><label for="commentsDelete">'.get_lang('Delete').'</label></th>';
        $return .= '</tr>';
        $return .= '<tr>';
        $return .= '<td style="text-align:center;"><input id="articleDelete" name="chkArticleDelete" type="checkbox" /></td>';
        $return .= '<td style="text-align:center;"><input id="articleEdit" name="chkArticleEdit" type="checkbox" /></td>';
        $return .= '<td style="border:1px dotted #808080; text-align:center;"><input id="commentsDelete" name="chkCommentsDelete" type="checkbox" /></td>';
        $return .= '</tr>';
        $return .= '</table>';
        $return .= '		</div>
                </div>';

        // task color
        $return .= '	<div class="control-group">
                    <label class="control-label">
                        '.get_lang('Color').'
                    </label>
                    <div class="controls">';
        $return .= '<select name="task_color" id="color" style="width: 150px; background-color: #eeeeee" onchange="document.getElementById(\'color\').style.backgroundColor=\'#\'+document.getElementById(\'color\').value" onkeypress="document.getElementById(\'color\').style.backgroundColor=\'#\'+document.getElementById(\'color\').value">';
        foreach ($colors as $color) {
            $style = 'style="background-color: #'.$color.'"';
            $return .= '<option value="'.$color.'" '.$style.'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>';
        }
        $return .= '</select>';
        $return .= '		</div>
                </div>';

        // submit
        $return .= '	<div class="control-group">
                    <div class="controls">
                            <input type="hidden" name="action" value="" />
                            <input type="hidden" name="new_task_submit" value="true" />
                        <button class="save" type="submit" name="Submit">'.get_lang('Save').'</button>
                    </div>
                </div>';
        $return .= '</form>';

        $return .= '<div style="clear:both; margin-bottom: 10px;"></div>';

        return $return;
    }

    /**
     * Displays edit task form.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id
     * @param int $task_id
     *
     * @return string
     */
    public static function displayTaskEditForm($blog_id, $task_id)
    {
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $task_id = intval($task_id);

        $colors = [
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
            '000000',
        ];

        $sql = "SELECT blog_id, task_id, title, description, color FROM $tbl_blogs_tasks
                WHERE c_id = $course_id AND task_id = $task_id";
        $result = Database::query($sql);
        $task = Database::fetch_array($result);

        $url = api_get_path(WEB_CODE_PATH).'blog/blog.php?'.api_get_cidreq().'&action=manage_tasks';
        $return = '<form name="edit_task" method="post" action="'.$url.'&blog_id='.$blog_id.'">
                    <legend>'.get_lang('EditTask').'</legend>
                    <table width="100%" border="0" cellspacing="2">
                        <tr>
                       <td align="right">'.get_lang('Title').':&nbsp;&nbsp;</td>
                       <td>
                        <input name="task_name" type="text" size="70" value="'.Security::remove_XSS($task['title']).'" />
                        </td>
                        </tr>
                        <tr>
                       <td align="right">'.get_lang('Description').':&nbsp;&nbsp;</td>
                       <td>
                        <textarea name="task_description" cols="45">'.
                            Security::remove_XSS($task['description']).'
                        </textarea>
                        </td>
                        </tr>';

        /* edit by Kevin Van Den Haute (kevin@develop-it.be) */
        $tbl_tasks_permissions = Database::get_course_table(TABLE_BLOGS_TASKS_PERMISSIONS);

        $sql = "SELECT id, action FROM $tbl_tasks_permissions
                WHERE c_id = $course_id AND task_id = $task_id";
        $result = Database::query($sql);

        $arrPermissions = [];

        while ($row = Database::fetch_array($result)) {
            $arrPermissions[] = $row['action'];
        }

        $return .= '<tr>';
        $return .= '<td style="text-align:right; vertical-align:top;">'.get_lang('TaskManager').':&nbsp;&nbsp;</td>';
        $return .= '<td>';
        $return .= '<table  class="table table-hover table-striped data_table" cellspacing="0" style="border-collapse:collapse; width:446px;">';
        $return .= '<tr>';
        $return .= '<th colspan="2" style="width:223px;">'.get_lang('ArticleManager').'</th>';
        $return .= '<th width:223px;>'.get_lang('CommentManager').'</th>';
        $return .= '</tr>';
        $return .= '<tr>';
        $return .= '<th style="width:111px;"><label for="articleDelete">'.get_lang('Delete').'</label></th>';
        $return .= '<th style="width:112px;"><label for="articleEdit">'.get_lang('Edit').'</label></th>';
        $return .= '<th style="width:223px;"><label for="commentsDelete">'.get_lang('Delete').'</label></th>';
        $return .= '</tr>';
        $return .= '<tr>';
        $return .= '<td style="text-align:center;"><input '.((in_array(
                'article_delete',
                $arrPermissions
            )) ? 'checked ' : '').'id="articleDelete" name="chkArticleDelete" type="checkbox" /></td>';
        $return .= '<td style="text-align:center;"><input '.((in_array(
                'article_edit',
                $arrPermissions
            )) ? 'checked ' : '').'id="articleEdit" name="chkArticleEdit" type="checkbox" /></td>';
        $return .= '<td style="text-align:center;"><input '.((in_array(
                'article_comments_delete',
                $arrPermissions
            )) ? 'checked ' : '').'id="commentsDelete" name="chkCommentsDelete" type="checkbox" /></td>';
        $return .= '</tr>';
        $return .= '</table>';
        $return .= '</td>';
        $return .= '</tr>';
        /* end of edit */

        $return .= '<tr>
                       <td align="right">'.get_lang('Color').':&nbsp;&nbsp;</td>
                       <td>
                        <select name="task_color" id="color" style="width: 150px; background-color: #'.$task['color'].'" onchange="document.getElementById(\'color\').style.backgroundColor=\'#\'+document.getElementById(\'color\').value" onkeypress="document.getElementById(\'color\').style.backgroundColor=\'#\'+document.getElementById(\'color\').value">';
        foreach ($colors as $color) {
            $selected = ($color == $task['color']) ? ' selected' : '';
            $style = 'style="background-color: #'.$color.'"';
            $return .= '<option value="'.$color.'" '.$style.' '.$selected.' >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>';
        }
        $return .= '</select>
                          </td>
                        </tr>
                        <tr>
                            <td align="right">&nbsp;</td>
                            <td><br /><input type="hidden" name="action" value="" />
                            <input type="hidden" name="edit_task_submit" value="true" />
                            <input type="hidden" name="task_id" value="'.$task['task_id'].'" />
                            <input type="hidden" name="blog_id" value="'.$task['blog_id'].'" />
                            <button class="save" type="submit" name="Submit">'.get_lang('Save').'</button></td>
                        </tr>
                    </table>
                </form>';

        return $return;
    }

    /**
     * Displays assign task form.
     *
     * @author Toon Keppens
     */
    public static function displayTaskAssignmentForm($blog_id)
    {
        $form = self::getTaskAssignmentForm($blog_id);
        $form->addHidden('assign_task_submit', 'true');

        return $form->returnForm()
            .PHP_EOL
            .'<div style="clear: both; margin-bottom:10px;"></div>';
    }

    /**
     * Returns an HTML form to assign a task.
     *
     * @param $blog_id
     *
     * @return FormValidator
     */
    public static function getTaskAssignmentForm($blog_id)
    {
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_blogs_rel_user = Database::get_course_table(TABLE_BLOGS_REL_USER);
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);

        // Get users in this blog / make select list of it
        $sql = "SELECT user.user_id, user.firstname, user.lastname, user.username
                FROM $tbl_users user
                INNER JOIN $tbl_blogs_rel_user blogs_rel_user
                ON user.user_id = blogs_rel_user.user_id
                WHERE blogs_rel_user.c_id = $course_id AND blogs_rel_user.blog_id = $blog_id";
        $result = Database::query($sql);

        $options = [];
        while ($user = Database::fetch_array($result)) {
            $options[$user['user_id']] = api_get_person_name($user['firstname'], $user['lastname']);
        }

        // Get tasks in this blog / make select list of it
        $sql = "SELECT
                    blog_id,
                    task_id,
                    blog_id,
                    title,
                    description,
                    color,
                    system_task
                FROM $tbl_blogs_tasks
                WHERE c_id = $course_id AND blog_id = $blog_id
                ORDER BY system_task, title";
        $result = Database::query($sql);

        $taskOptions = [];
        while ($task = Database::fetch_array($result)) {
            $taskOptions[$task['task_id']] = stripslashes($task['title']);
        }

        $form = new FormValidator(
            'assign_task',
            'post',
            api_get_path(WEB_CODE_PATH).
            'blog/blog.php?action=manage_tasks&blog_id='.$blog_id.'&'.api_get_cidreq()
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
     * Displays assign task form.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id
     * @param int $task_id
     * @param int $user_id
     *
     * @return string HTML form
     */
    public static function displayAssignedTaskEditForm($blog_id, $task_id, $user_id)
    {
        $table = Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER);

        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $task_id = intval($task_id);
        $user_id = intval($user_id);

        // Get assign date;
        $sql = "
            SELECT target_date
            FROM $table
            WHERE c_id = $course_id AND
                  blog_id = $blog_id AND
                  user_id = $user_id AND
                  task_id = $task_id";
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        $date = $row['target_date'];

        $defaults = [
            'task_user_id' => $user_id,
            'task_task_id' => $task_id,
            'task_day' => $date,
        ];
        $form = self::getTaskAssignmentForm($blog_id);
        $form->addHidden('old_task_id', $task_id);
        $form->addHidden('old_user_id', $user_id);
        $form->addHidden('old_target_date', $date);
        $form->addHidden('assign_task_edit_submit', 'true');
        $form->setDefaults($defaults);

        return $form->returnForm();
    }

    /**
     * Assigns a task to a user in a blog.
     *
     * @param int    $blog_id
     * @param int    $user_id
     * @param int    $task_id
     * @param string $target_date date
     */
    public static function assignTask($blog_id, $user_id, $task_id, $target_date)
    {
        $table = Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER);
        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $user_id = intval($user_id);
        $task_id = intval($task_id);
        $target_date = Database::escape_string($target_date);

        $sql = "
            SELECT COUNT(*) as 'number'
            FROM $table
            WHERE c_id = $course_id
            AND blog_id = $blog_id
            AND	user_id = $user_id
            AND	task_id = $task_id";

        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        if ($row['number'] == 0) {
            $sql = "
                INSERT INTO ".$table." (
                    c_id,
                    blog_id,
                    user_id,
                    task_id,
                    target_date
                ) VALUES (
                    $course_id,
                    $blog_id,
                    $user_id,
                    $task_id,
                    '$target_date'
                )";

            Database::query($sql);
        }
    }

    /**
     * Edit an assigned task.
     *
     * @param $blog_id
     * @param $user_id
     * @param $task_id
     * @param $target_date
     * @param $old_user_id
     * @param $old_task_id
     * @param $old_target_date
     */
    public static function updateAssignedTask(
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
        $blog_id = intval($blog_id);
        $user_id = intval($user_id);
        $task_id = intval($task_id);
        $target_date = Database::escape_string($target_date);
        $old_user_id = intval($old_user_id);
        $old_task_id = intval($old_task_id);
        $old_target_date = Database::escape_string($old_target_date);

        $sql = "SELECT COUNT(*) as 'number'
                FROM $tbl_blogs_tasks_rel_user
                WHERE
                    c_id = $course_id AND
                    blog_id = $blog_id AND
                    user_id = $user_id AND
                    task_id = $task_id";

        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        if ($row['number'] == 0 ||
            ($row['number'] != 0 && $task_id == $old_task_id && $user_id == $old_user_id)
        ) {
            $sql = "UPDATE $tbl_blogs_tasks_rel_user
                SET
                    user_id = $user_id,
                    task_id = $task_id,
                    target_date = '$target_date'
                WHERE
                    c_id = $course_id AND
                    blog_id = $blog_id AND
                    user_id = $old_user_id AND
                    task_id = $old_task_id AND
                    target_date = '$old_target_date'
            ";
            Database::query($sql);
        }
    }

    /**
     * Displays a list with posts a user can select to execute his task.
     *
     * @param int $blog_id
     * @param int $task_id
     *
     * @return string
     */
    public static function displayPostSelectionForTask($blog_id, $task_id)
    {
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
        $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);
        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $task_id = intval($task_id);

        $sql = "SELECT title, description FROM $tbl_blogs_tasks
                WHERE task_id = $task_id
                AND c_id = $course_id";
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        // Get posts and authors
        $sql = "SELECT post.*, user.lastname, user.firstname, user.username
                FROM $tbl_blogs_posts post
                INNER JOIN $tbl_users user ON post.author_id = user.user_id
                WHERE post.blog_id = $blog_id AND post.c_id = $course_id
                ORDER BY post_id DESC
                LIMIT 0, 100";
        $result = Database::query($sql);

        // Display
        $return = '<span class="blogpost_title">'.
                    get_lang('SelectTaskArticle').' "'.Security::remove_XSS($row['title']).'"</span>';
        $return .= '<span style="font-style: italic;"">'.Security::remove_XSS($row['description']).'</span><br><br>';

        if (Database::num_rows($result) == 0) {
            $return .= get_lang('NoArticles');

            return $return;
        }
        $url = api_get_path(WEB_CODE_PATH).'blog/blog.php?'.api_get_cidreq().'&action=execute_task';
        while ($blog_post = Database::fetch_array($result)) {
            $username = api_htmlentities(sprintf(get_lang('LoginX'), $blog_post['username']), ENT_QUOTES);
            $return .= '<a href="'.$url.'&blog_id='.$blog_id.'&task_id='.$task_id.'&post_id='.$blog_post['post_id'].'#add_comment">'.
                Security::remove_XSS($blog_post['title']).'</a>, '.
                get_lang('WrittenBy').' '.stripslashes(
                    Display::tag(
                        'span',
                        api_get_person_name($blog_post['firstname'], $blog_post['lastname']),
                        ['title' => $username]
                    )
                ).'<br />';
        }

        return $return;
    }

    /**
     * Unsubscribe a user from a given blog.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id
     * @param int $user_id
     */
    public static function unsubscribeUser($blog_id, $user_id)
    {
        $tbl_blogs_rel_user = Database::get_course_table(TABLE_BLOGS_REL_USER);
        $tbl_user_permissions = Database::get_course_table(TABLE_PERMISSION_USER);
        $blog_id = intval($blog_id);
        $user_id = intval($user_id);

        // Unsubscribe the user
        $sql = "DELETE FROM $tbl_blogs_rel_user
                WHERE blog_id = $blog_id AND user_id = $user_id";
        Database::query($sql);

        // Remove this user's permissions.
        $sql = "DELETE FROM $tbl_user_permissions
                WHERE user_id = $user_id";
        Database::query($sql);
    }

    /**
     * Displays the form to register users in a blog (in a course)
     * The listed users are users subscribed in the course.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id
     *
     * @return string html Form with sortable table with users to subcribe in a blog, in a course
     */
    public static function displayUserSubscriptionForm($blog_id)
    {
        $_course = api_get_course_info();
        $is_western_name_order = api_is_western_name_order();
        $session_id = api_get_session_id();
        $course_id = $_course['real_id'];
        $blog_id = intval($blog_id);

        $currentCourse = $_course['code'];
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_blogs_rel_user = Database::get_course_table(TABLE_BLOGS_REL_USER);
        $html = null;

        $html .= '<legend>'.get_lang('SubscribeMembers').'</legend>';

        // Get blog members' id.
        $sql = "SELECT user.user_id FROM $tbl_users user
                INNER JOIN $tbl_blogs_rel_user blogs_rel_user
                ON user.user_id = blogs_rel_user.user_id
                WHERE blogs_rel_user.c_id = $course_id AND blogs_rel_user.blog_id = $blog_id";
        $result = Database::query($sql);

        $blog_member_ids = [];
        while ($user = Database::fetch_array($result)) {
            $blog_member_ids[] = $user['user_id'];
        }

        // Set table headers
        $column_header[] = ['', false, ''];
        if ($is_western_name_order) {
            $column_header[] = [get_lang('FirstName'), true, ''];
            $column_header[] = [get_lang('LastName'), true, ''];
        } else {
            $column_header[] = [get_lang('LastName'), true, ''];
            $column_header[] = [get_lang('FirstName'), true, ''];
        }
        $column_header[] = [get_lang('Email'), false, ''];
        $column_header[] = [get_lang('Register'), false, ''];

        $student_list = CourseManager::get_student_list_from_course_code(
            $currentCourse,
            false,
            $session_id
        );
        $user_data = [];

        // Add users that are not in this blog to the list.
        foreach ($student_list as $key => $user) {
            if (isset($user['id_user'])) {
                $user['user_id'] = $user['id_user'];
            }
            if (!in_array($user['user_id'], $blog_member_ids)) {
                $a_infosUser = api_get_user_info($user['user_id']);
                $row = [];
                $row[] = '<input type="checkbox" name="user[]" value="'.$a_infosUser['user_id'].'" '.((isset($_GET['selectall']) && $_GET['selectall'] == "subscribe") ? ' checked="checked" ' : '').'/>';
                $username = api_htmlentities(sprintf(get_lang('LoginX'), $a_infosUser["username"]), ENT_QUOTES);
                if ($is_western_name_order) {
                    $row[] = $a_infosUser["firstname"];
                    $row[] = Display::tag(
                        'span',
                        $a_infosUser["lastname"],
                        ['title' => $username]
                    );
                } else {
                    $row[] = Display::tag(
                        'span',
                        $a_infosUser["lastname"],
                        ['title' => $username]
                    );
                    $row[] = $a_infosUser["firstname"];
                }
                $row[] = Display::icon_mailto_link($a_infosUser['email']);

                // Link to register users
                if ($a_infosUser['user_id'] != api_get_user_id()) {
                    $row[] = Display::url(
                        get_lang('Register'),
                        api_get_self()."?action=manage_members&blog_id=$blog_id&register=yes&user_id=".$a_infosUser["user_id"].'&'.api_get_cidreq(),
                        ['class' => 'btn btn-primary']
                    );
                } else {
                    $row[] = '';
                }
                $user_data[] = $row;
            }
        }

        // Display
        $query_vars['action'] = 'manage_members';
        $query_vars['blog_id'] = $blog_id;
        $html .= '<form
                class="form-inline"
                method="post"
                action="blog.php?action=manage_members&blog_id='.$blog_id.'&'.api_get_cidreq().'">';
        $html .= Display::return_sortable_table($column_header, $user_data, null, null, $query_vars);

        $link = isset($_GET['action']) ? 'action='.Security::remove_XSS($_GET['action']).'&' : '';
        $link .= "blog_id=$blog_id&".api_get_cidreq();

        $html .= '<a
                class="btn btn-default" href="blog.php?'.$link.'selectall=subscribe">'.
            get_lang('SelectAll').'</a> - ';
        $html .= '<a class="btn btn-default" href="blog.php?'.$link.'">'.get_lang('UnSelectAll').'</a> ';
        $html .= '<div class="form-group">';
        $html .= '<label>';
        $html .= get_lang('WithSelected').' : ';
        $html .= '</label>';
        $html .= '<select class="selectpicker" name="action">';
        $html .= '<option value="select_subscribe">'.get_lang('Register').'</option>';
        $html .= '</select>';
        $html .= '<input type="hidden" name="register" value="true" />';
        $html .= '<button class="btn btn-default" type="submit">'.get_lang('Ok').'</button>';
        $html .= '</div>';
        $html .= '</form>';

        return $html;
    }

    /**
     * Displays the form to register users in a blog (in a course)
     * The listed users are users subcribed in the course.
     *
     * @author Toon Keppens
     *
     * @param int $blog_id
     *
     * @return false|null form with sortable table with users to unsubcribe from a blog
     */
    public static function displayUserUnsubscriptionForm($blog_id)
    {
        $_user = api_get_user_info();
        $is_western_name_order = api_is_western_name_order();
        $html = null;

        // Init
        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_blogs_rel_user = Database::get_course_table(TABLE_BLOGS_REL_USER);
        $blog_id = intval($blog_id);

        $html .= '<legend>'.get_lang('UnsubscribeMembers').'</legend>';

        //table column titles
        $column_header[] = ['', false, ''];
        if ($is_western_name_order) {
            $column_header[] = [get_lang('FirstName'), true, ''];
            $column_header[] = [get_lang('LastName'), true, ''];
        } else {
            $column_header[] = [get_lang('LastName'), true, ''];
            $column_header[] = [get_lang('FirstName'), true, ''];
        }
        $column_header[] = [get_lang('Email'), false, ''];
        $column_header[] = [get_lang('TaskManager'), true, ''];
        $column_header[] = [get_lang('UnRegister'), false, ''];

        $course_id = api_get_course_int_id();

        $sql = "SELECT user.user_id, user.lastname, user.firstname, user.email, user.username
                FROM $tbl_users user
                INNER JOIN $tbl_blogs_rel_user blogs_rel_user
                ON user.user_id = blogs_rel_user.user_id
                WHERE blogs_rel_user.c_id = $course_id AND  blogs_rel_user.blog_id = $blog_id";

        if (!($sql_result = Database::query($sql))) {
            return false;
        }

        $user_data = [];
        while ($myrow = Database::fetch_array($sql_result)) {
            $row = [];
            $row[] = '<input
                type="checkbox"
                name="user[]"
                value="'.$myrow['user_id'].'" '.((isset($_GET['selectall']) && $_GET['selectall'] == "unsubscribe") ? ' checked="checked" ' : '').'/>';
            $username = api_htmlentities(sprintf(get_lang('LoginX'), $myrow["username"]), ENT_QUOTES);
            if ($is_western_name_order) {
                $row[] = $myrow["firstname"];
                $row[] = Display::tag(
                    'span',
                    $myrow["lastname"],
                    ['title' => $username]
                );
            } else {
                $row[] = Display::tag(
                    'span',
                    $myrow["lastname"],
                    ['title' => $username]
                );
                $row[] = $myrow["firstname"];
            }
            $row[] = Display::icon_mailto_link($myrow["email"]);

            $sql = "SELECT bt.title task
                    FROM ".Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER)." btu
                    INNER JOIN ".Database::get_course_table(TABLE_BLOGS_TASKS)." bt
                    ON btu.task_id = bt.task_id
                    WHERE 	btu.c_id 	= $course_id  AND
                            bt.c_id 	= $course_id  AND
                            btu.blog_id = $blog_id AND
                            btu.user_id = ".$myrow['user_id'];
            $sql_res = Database::query($sql);
            $task = '';
            while ($r = Database::fetch_array($sql_res)) {
                $task .= stripslashes($r['task']).', ';
            }
            $task = (api_strlen(trim($task)) != 0) ? api_substr($task, 0, api_strlen($task) - 2) : get_lang('Reader');
            $row[] = $task;
            //Link to register users

            if ($myrow["user_id"] != $_user['user_id']) {
                $row[] = Display::url(
                    get_lang('UnRegister'),
                    api_get_self()."?action=manage_members&blog_id=$blog_id&unregister=yes&user_id=".$myrow['user_id'].'&'.api_get_cidreq(),
                    ['class' => 'btn btn-primary']
                );
            } else {
                $row[] = '';
            }
            $user_data[] = $row;
        }

        $query_vars['action'] = 'manage_members';
        $query_vars['blog_id'] = $blog_id;
        $html .= '<form
            class="form-inline"
            method="post"
            action="blog.php?action=manage_members&blog_id='.$blog_id.'&'.api_get_cidreq().'">';
        $html .= Display::return_sortable_table($column_header, $user_data, null, null, $query_vars);

        $link = isset($_GET['action']) ? 'action='.Security::remove_XSS($_GET['action']).'&' : '';
        $link .= "blog_id=$blog_id&".api_get_cidreq();

        $html .= '<a class="btn btn-default" href="blog.php?'.$link.'selectall=unsubscribe">'.get_lang('SelectAll').'</a> - ';
        $html .= '<a class="btn btn-default" href="blog.php?'.$link.'">'.get_lang('UnSelectAll').'</a> ';
        $html .= '<div class="form-group">';
        $html .= '<label>';
        $html .= get_lang('WithSelected').' : ';
        $html .= '</label>';
        $html .= '<select name="action" class="selectpicker">';
        $html .= '<option value="select_unsubscribe">'.get_lang('UnRegister').'</option>';
        $html .= '</select>';
        $html .= '<input type="hidden" name="unregister" value="true" />';
        $html .= '<button class="btn btn-default" type="submit">'.get_lang('Ok').'</button>';
        $html .= '</div>';
        $html .= '</form>';

        return $html;
    }

    /**
     * Displays a matrix with selectboxes. On the left: users, on top: possible rights.
     * The blog admin can thus select what a certain user can do in the current blog.
     *
     * @param int $blog_id
     *
     * @return string
     */
    public static function displayUserRightsForm($blog_id)
    {
        ob_start();
        echo '<legend>'.get_lang('RightsManager').'</legend>';
        echo '<br />';

        // Integration of patricks permissions system.
        require_once api_get_path(SYS_CODE_PATH).'permissions/blog_permissions.inc.php';
        $content = ob_get_contents();
        ob_get_clean();

        return $content;
    }

    /**
     * show the calender of the given month.
     *
     * @author Patrick Cool
     * @author Toon Keppens
     *
     * @param int $month   The integer value of the month we are viewing
     * @param int $year    The 4-digit year indication e.g. 2005
     * @param int $blog_id
     *
     * @return string html code
     */
    public static function displayMiniMonthCalendar($month, $year, $blog_id)
    {
        $_user = api_get_user_info();
        global $DaysShort;
        global $MonthsLong;
        $html = null;

        $posts = [];
        $tasks = [];

        $tbl_users = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_blogs_posts = Database::get_course_table(TABLE_BLOGS_POSTS);
        $tbl_blogs_tasks = Database::get_course_table(TABLE_BLOGS_TASKS);
        $tbl_blogs_tasks_rel_user = Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER);
        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);

        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $month = intval($month);
        $year = intval($year);

        //Handle leap year
        $numberofdays = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        if (($year % 400 == 0) or ($year % 4 == 0 and $year % 100 != 0)) {
            $numberofdays[2] = 29;
        }

        //Get the first day of the month
        $dayone = getdate(mktime(0, 0, 0, $month, 1, $year));
        $monthName = $MonthsLong[$month - 1];
        $url = api_get_path(WEB_CODE_PATH).'blog/blog.php?'.api_get_cidreq();
        //Start the week on monday
        $startdayofweek = $dayone['wday'] != 0 ? ($dayone['wday'] - 1) : 6;
        $blogId = isset($_GET['blog_id']) ? intval($_GET['blog_id']) : null;
        $filter = isset($_GET['filter']) ? Security::remove_XSS($_GET['filter']) : null;
        $backwardsURL = $url."&blog_id=".$blogId."&filter=".$filter."&month=".($month == 1 ? 12 : $month - 1)."&year=".($month == 1 ? $year - 1 : $year);
        $forewardsURL = $url."&blog_id=".$blogId."&filter=".$filter."&month=".($month == 12 ? 1 : $month + 1)."&year=".($month == 12 ? $year + 1 : $year);

        // Get posts for this month
        $sql = "SELECT post.*, DAYOFMONTH(date_creation) as post_day, user.lastname, user.firstname
                FROM $tbl_blogs_posts post
                INNER JOIN $tbl_users user
                ON post.author_id = user.user_id
                WHERE
                    post.c_id = $course_id AND
                    post.blog_id = $blog_id AND
                    MONTH(date_creation) = '$month' AND
                    YEAR(date_creation) = '$year'
                ORDER BY date_creation";
        $result = Database::query($sql);
        // We will create an array of days on which there are posts.
        if (Database::num_rows($result) > 0) {
            while ($blog_post = Database::fetch_array($result)) {
                // If the day of this post is not yet in the array, add it.
                if (!in_array($blog_post['post_day'], $posts)) {
                    $posts[] = $blog_post['post_day'];
                }
            }
        }

        // Get tasks for this month
        if ($_user['user_id']) {
            $sql = "SELECT
                        task_rel_user.*,
                        DAYOFMONTH(target_date) as task_day,
                        task.title,
                        blog.blog_name
                    FROM $tbl_blogs_tasks_rel_user task_rel_user
                    INNER JOIN $tbl_blogs_tasks task
                    ON task_rel_user.task_id = task.task_id
                    INNER JOIN $tbl_blogs blog
                    ON task_rel_user.blog_id = blog.blog_id
                    WHERE
                        task_rel_user.c_id = $course_id AND
                        task.c_id = $course_id AND
                        blog.c_id = $course_id AND
                        task_rel_user.user_id = ".$_user['user_id']." AND
                        MONTH(target_date) = '$month' AND
                        YEAR(target_date) = '$year'
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

        $html .= '<table id="smallcalendar" class="table table-responsive">
                <tr id="title">
                <th width="10%"><a href="'.$backwardsURL.'">&laquo;</a></th>
                <th align="center" width="80%" colspan="5" class="month">'.$monthName.' '.$year.'</th>
                <th width="10%" align="right"><a href="'.$forewardsURL.'">&raquo;</a></th></tr>';

        $html .= '<tr>';
        for ($ii = 1; $ii < 8; $ii++) {
            $html .= '<td class="weekdays">'.$DaysShort[$ii % 7].'</td>';
        }
        $html .= '</tr>';
        $curday = -1;
        $today = getdate();

        while ($curday <= $numberofdays[$month]) {
            $html .= '<tr>';
            for ($ii = 0; $ii < 7; $ii++) {
                if (($curday == -1) && ($ii == $startdayofweek)) {
                    $curday = 1;
                }

                if (($curday > 0) && ($curday <= $numberofdays[$month])) {
                    $bgcolor = $ii < 5 ? $class = "class=\"days_week\"" : $class = "class=\"days_weekend\"";
                    $dayheader = "$curday";

                    if (($curday == $today['mday']) && ($year == $today['year']) && ($month == $today['mon'])) {
                        $dayheader = "$curday";
                        $class = "class=\"days_today\"";
                    }

                    $html .= '<td '.$class.'>';
                    // If there are posts on this day, create a filter link.
                    if (in_array($curday, $posts)) {
                        $html .= '<a
                        href="'.$url.'&blog_id='.$blog_id.'&filter='.$year.'-'.$month.'-'.$curday.'&month='.$month.'&year='.$year.'"
                        title="'.get_lang('ViewPostsOfThisDay').'">'.$curday.'</a>';
                    } else {
                        $html .= $dayheader;
                    }

                    if (count($tasks) > 0) {
                        if (isset($tasks[$curday]) && is_array($tasks[$curday])) {
                            // Add tasks to calendar
                            foreach ($tasks[$curday] as $task) {
                                $html .= '<a
                                    href="blog.php?action=execute_task&blog_id='.$task['blog_id'].'&task_id='.stripslashes($task['task_id']).'" title="'.$task['title'].' : '.get_lang('InBlog').' : '.$task['blog_name'].' - '.get_lang('ExecuteThisTask').'">';
                                $html .= Display::return_icon('blog_task.gif', get_lang('ExecuteThisTask'));
                                $html .= '</a>';
                            }
                        }
                    }

                    $html .= '</td>';
                    $curday++;
                } else {
                    $html .= '<td>&nbsp;</td>';
                }
            }
            $html .= '</tr>';
        }
        $html .= '</table>';

        return $html;
    }

    /**
     * Blog admin | Display the form to add a new blog.
     */
    public static function displayBlogCreateForm()
    {
        $form = new FormValidator(
            'add_blog',
            'post',
            'blog_admin.php?action=add&'.api_get_cidreq()
        );
        $form->addElement('header', get_lang('AddBlog'));
        $form->addElement('text', 'blog_name', get_lang('Title'));
        $form->addElement('textarea', 'blog_subtitle', get_lang('SubTitle'));
        $form->addElement('hidden', 'new_blog_submit', 'true');
        $form->addButtonSave(get_lang('SaveProject'));

        $defaults = [
            'blog_name' => isset($_POST['blog_name']) ? Security::remove_XSS($_POST['blog_name']) : null,
            'blog_subtitle' => isset($_POST['blog_subtitle']) ? Security::remove_XSS($_POST['blog_subtitle']) : null,
        ];
        $form->setDefaults($defaults);
        $form->display();
    }

    /**
     * Blog admin | Display the form to edit a blog.
     *
     * @param int $blog_id
     */
    public static function displayBlogEditForm($blog_id)
    {
        $course_id = api_get_course_int_id();
        $blog_id = intval($blog_id);
        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);

        $sql = "SELECT blog_id, blog_name, blog_subtitle
                FROM $tbl_blogs
                WHERE c_id = $course_id AND blog_id = $blog_id";
        $result = Database::query($sql);
        $blog = Database::fetch_array($result);

        // the form contained errors but we do not want to lose the changes the user already did
        if ($_POST) {
            $blog['blog_name'] = Security::remove_XSS($_POST['blog_name']);
            $blog['blog_subtitle'] = Security::remove_XSS($_POST['blog_subtitle']);
        }

        $form = new FormValidator(
            'edit_blog',
            'post',
            'blog_admin.php?action=edit&blog_id='.intval($_GET['blog_id'])
        );
        $form->addElement('header', get_lang('EditBlog'));
        $form->addElement('text', 'blog_name', get_lang('Title'));
        $form->addElement('textarea', 'blog_subtitle', get_lang('SubTitle'));
        $form->addElement('hidden', 'edit_blog_submit', 'true');
        $form->addElement('hidden', 'blog_id', $blog['blog_id']);
        $form->addButtonSave(get_lang('Save'));

        $defaults = [];
        $defaults['blog_name'] = $blog['blog_name'];
        $defaults['blog_subtitle'] = $blog['blog_subtitle'];
        $form->setDefaults($defaults);
        $form->display();
    }

    /**
     * Blog admin | Returns table with blogs in this course.
     */
    public static function displayBlogsList()
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
        $list_info = [];
        if (Database::num_rows($result)) {
            while ($row_project = Database::fetch_row($result)) {
                $list_info[] = $row_project;
            }
        }

        $list_content_blog = [];
        $list_body_blog = [];

        if (is_array($list_info)) {
            foreach ($list_info as $key => $info_log) {
                // Validation when belongs to a session
                $session_img = api_get_session_image($info_log[4], $_user['status']);

                $url_start_blog = 'blog.php'."?"."blog_id=".$info_log[3]."&".api_get_cidreq();
                $title = Security::remove_XSS($info_log[0]);
                $image = Display::return_icon('blog.png', $title);
                $list_name = '<div style="float: left; width: 35px; height: 22px;"><a href="'.$url_start_blog.'">'.$image.'</a></div><a href="'.$url_start_blog.'">'.$title.'</a>'.$session_img;

                $list_body_blog[] = $list_name;
                $list_body_blog[] = Security::remove_XSS($info_log[1]);

                $visibility_icon = ($info_log[2] == 0) ? 'invisible' : 'visible';
                $visibility_info = ($info_log[2] == 0) ? 'Visible' : 'Invisible';

                $my_image = '<a href="'.api_get_self().'?action=visibility&blog_id='.$info_log[3].'">';
                $my_image .= Display::return_icon($visibility_icon.'.png', get_lang($visibility_info));
                $my_image .= "</a>";

                $my_image .= '<a href="'.api_get_self().'?action=edit&blog_id='.$info_log[3].'">';
                $my_image .= Display::return_icon('edit.png', get_lang('EditBlog'));
                $my_image .= "</a>";

                $my_image .= '<a href="'.api_get_self().'?action=delete&blog_id='.$info_log[3].'" ';
                $my_image .= 'onclick="javascript:if(!confirm(\''.addslashes(
                        api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES, $charset)
                    ).'\')) return false;" >';
                $my_image .= Display::return_icon('delete.png', get_lang('DeleteBlog'));
                $my_image .= "</a>";

                $list_body_blog[] = $my_image;
                $list_content_blog[] = $list_body_blog;
                $list_body_blog = [];
            }

            $table = new SortableTableFromArrayConfig(
                $list_content_blog,
                1,
                20,
                'project'
            );
            $table->set_header(0, get_lang('Title'));
            $table->set_header(1, get_lang('SubTitle'));
            $table->set_header(2, get_lang('Modify'));
            $table->display();
        }
    }

    /**
     * Show a list with all the attachments according the parameter's.
     *
     * @param int $blog_id    the blog's id
     * @param int $post_id    the post's id
     * @param int $comment_id the comment's id
     *
     * @return array with the post info according the parameters
     *
     * @author Julio Montoya
     *
     * @version avril 2008, dokeos 1.8.5
     */
    public static function getBlogAttachments($blog_id, $post_id = 0, $comment_id = 0)
    {
        $blog_table_attachment = Database::get_course_table(TABLE_BLOGS_ATTACHMENT);

        $blog_id = intval($blog_id);
        $comment_id = intval($comment_id);
        $post_id = intval($post_id);
        $row = [];
        $where = '';
        if (!empty($post_id) && is_numeric($post_id)) {
            $where .= " AND post_id = $post_id ";
        }

        if (!empty($comment_id) && is_numeric($comment_id)) {
            if (!empty($post_id)) {
                $where .= ' AND ';
            }
            $where .= " comment_id = $comment_id ";
        }

        $course_id = api_get_course_int_id();

        $sql = "SELECT path, filename, comment
                FROM $blog_table_attachment
	            WHERE c_id = $course_id AND blog_id = $blog_id
	            $where";

        $result = Database::query($sql);
        if (Database::num_rows($result) != 0) {
            $row = Database::fetch_array($result);
        }

        return $row;
    }

    /**
     * Delete the all the attachments according the parameters.
     *
     * @param int $blog_id
     * @param int $post_id    post's id
     * @param int $comment_id the comment's id
     *
     * @author Julio Montoya
     *
     * @version avril 2008, dokeos 1.8.5
     */
    public static function deleteAllBlogAttachments(
        $blog_id,
        $post_id = 0,
        $comment_id = 0
    ) {
        $_course = api_get_course_info();
        $blog_table_attachment = Database::get_course_table(TABLE_BLOGS_ATTACHMENT);
        $blog_id = intval($blog_id);
        $comment_id = intval($comment_id);
        $post_id = intval($post_id);

        $course_id = api_get_course_int_id();
        $where = null;

        // delete files in DB
        if (!empty($post_id) && is_numeric($post_id)) {
            $where .= " AND post_id = $post_id ";
        }

        if (!empty($comment_id) && is_numeric($comment_id)) {
            if (!empty($post_id)) {
                $where .= ' AND ';
            }
            $where .= " comment_id = $comment_id ";
        }

        // delete all files in directory
        $courseDir = $_course['path'].'/upload/blog';
        $sys_course_path = api_get_path(SYS_COURSE_PATH);
        $updir = $sys_course_path.$courseDir;

        $sql = "SELECT path FROM $blog_table_attachment
	        WHERE c_id = $course_id AND blog_id = $blog_id $where";
        $result = Database::query($sql);

        while ($row = Database::fetch_row($result)) {
            $file = $updir.'/'.$row[0];
            if (Security::check_abs_path($file, $updir)) {
                @unlink($file);
            }
        }
        $sql = "DELETE FROM $blog_table_attachment
	        WHERE c_id = $course_id AND  blog_id = $blog_id $where";
        Database::query($sql);
    }

    /**
     * Gets all the post from a given user id.
     *
     * @param int    $courseId
     * @param int    $userId
     * @param string $courseCode
     *
     * @return string
     */
    public static function getBlogPostFromUser($courseId, $userId, $courseCode)
    {
        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);
        $tbl_blog_post = Database::get_course_table(TABLE_BLOGS_POSTS);
        $courseId = intval($courseId);
        $userId = intval($userId);

        $sql = "SELECT DISTINCT blog.blog_id, post_id, title, full_text, post.date_creation
                FROM $tbl_blogs blog
                INNER JOIN $tbl_blog_post post
                ON (blog.blog_id = post.blog_id AND blog.c_id = post.c_id)
                WHERE
                    blog.c_id = $courseId AND
                    post.c_id = $courseId AND
                    author_id =  $userId AND
                    visibility = 1
                ORDER BY post.date_creation DESC ";
        $result = Database::query($sql);
        $return_data = '';

        if (Database::num_rows($result) != 0) {
            while ($row = Database::fetch_array($result)) {
                $return_data .= '<div class="clear"></div><br />';
                $return_data .= '<div class="actions" style="margin-left:5px;margin-right:5px;">'.
                    Display::return_icon(
                        'blog_article.png',
                        get_lang('BlogPosts')
                    ).' '.
                    $row['title'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <div style="float:right;margin-top:-18px">
                    <a href="../blog/blog.php?blog_id='.$row['blog_id'].'&gidReq=&cidReq='.$courseCode.' " >'.
                    get_lang('SeeBlog').'</a></div></div>';
                $return_data .= '<br / >';
                $return_data .= $row['full_text'];
                $return_data .= '<br /><br />';
            }
        }

        return $return_data;
    }

    /**
     * Gets all the post comments from a given user id.
     *
     * @param int    $courseId
     * @param int    $userId
     * @param string $courseCode
     *
     * @return string
     */
    public static function getBlogCommentsFromUser($courseId, $userId, $courseCode)
    {
        $tbl_blogs = Database::get_course_table(TABLE_BLOGS);
        $tbl_blog_comment = Database::get_course_table(TABLE_BLOGS_COMMENTS);

        $userId = intval($userId);
        $courseId = intval($courseId);

        $sql = "SELECT DISTINCT blog.blog_id, comment_id, title, comment, comment.date_creation
                FROM $tbl_blogs blog
                INNER JOIN  $tbl_blog_comment comment
                ON (blog.blog_id = comment.blog_id AND blog.c_id = comment.c_id)
                WHERE 	blog.c_id = $courseId AND
                        comment.c_id = $courseId AND
                        author_id = $userId AND
                        visibility = 1
                ORDER BY blog_name";
        $result = Database::query($sql);
        $return_data = '';
        if (Database::num_rows($result) != 0) {
            while ($row = Database::fetch_array($result)) {
                $return_data .= '<div class="clear"></div><br />';
                $return_data .= '<div class="actions" style="margin-left:5px;margin-right:5px;">'.
                    $row['title'].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <div style="float:right;margin-top:-18px">
                        <a href="../blog/blog.php?blog_id='.$row['blog_id'].'&gidReq=&cidReq='.Security::remove_XSS($courseCode).' " >'.
                    get_lang('SeeBlog').'</a></div></div>';
                $return_data .= '<br / >';
                $return_data .= $row['comment'];
                $return_data .= '<br />';
            }
        }

        return $return_data;
    }

    /**
     * Filter the post $fullText to get a extract of $length characters.
     *
     * @param string $fullText
     * @param int    $length
     *
     * @return string|null
     */
    private static function getPostExtract($fullText, $length = BLOG_MAX_PREVIEW_CHARS)
    {
        $parts = explode(BLOG_PAGE_BREAK, $fullText);

        if (count($parts) > 1) {
            return $parts[0];
        }

        // Remove any HTML from the string
        $text = strip_tags($fullText);
        $text = api_html_entity_decode($text);
        // Replace end of lines with spaces
        $text = preg_replace('/\s+/', ' ', $text);
        // Count whitespaces to add to the cut() call below
        $countBlanks = substr_count($text, ' ');
        // Get a version of the string without spaces for comparison purposes
        $textWithoutBlanks = str_replace(' ', '', $text);
        // utf8_decode replaces non-ISO chars by '?' which avoids counting
        // multi-byte characters as more than one character
        $stringLength = strlen(utf8_decode($textWithoutBlanks));

        if ($stringLength <= $length) {
            return null;
        }

        // Cut the string to the BLOG_MAX_PREVIEX_CHARS limit, adding
        // whitespaces
        $extract = cut($text, $length + $countBlanks);

        // Return an HTML string for printing
        return api_htmlentities($extract);
    }
}
