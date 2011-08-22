<?php

require_once(dirname(__FILE__).'/../inc/global.inc.php');
require_once(dirname(__FILE__).'/../forum/forumconfig.inc.php');
require_once(dirname(__FILE__).'/../forum/forumfunction.inc.php');

$libpath = api_get_path(LIBRARY_PATH);

require_once $libpath.'usermanager.lib.php';
require_once $libpath.'course.lib.php';
require_once(dirname(__FILE__).'/cm_webservice.php');


/**
 * Description of cm_soap_inbox
 *
 * @author marcosousa
 */
class WSCMForum extends WSCM {

    public function get_foruns_id($username, $password, $course_code)
    {
        if($this->verifyUserPass($username, $password) == "valid")
        { 
            $course_db = CourseManager::get_course_information($course_code);
            $foruns_info = get_forums($id='', $course_db['db_name']);
            $foruns_id = '#';
            foreach ($foruns_info as $forum)
            {
                if( isset($forum['forum_id']))
                {
                    $foruns_id .= $forum['forum_id']."#";
                }
            }
            return $foruns_id;
        } else
            return get_lang('InvalidId');
    }
    
    public function get_forum_title($username, $password, $course_code, $forum_id)
    { 
        if($this->verifyUserPass($username, $password) == "valid")
        {
            $course_db = CourseManager::get_course_information($course_code);
            $table_forums = Database :: get_course_table(TABLE_FORUM, $course_db['db_name']);
            $table_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY, $course_db['db_name']);

            $sql="SELECT * FROM ".$table_forums." forums, ".$table_item_property." item_properties
                            WHERE item_properties.tool='".TOOL_FORUM."'
                            AND item_properties.ref='".Database::escape_string($forum_id)."'
                            AND forums.forum_id='".Database::escape_string($forum_id)."'";
            $result=Database::query($sql);
            $forum_info=Database::fetch_array($result);
            $forum_info['approval_direct_post'] = 0; // we can't anymore change this option, so it should always be activated

            $forum_title = utf8_decode($forum_info['forum_title']);
            return $forum_title;
        } else
            return get_lang('InvalidId');
    }
    
    public function get_forum_threads_id($username, $password, $course_code, $forum_id)
    {
        if($this->verifyUserPass($username, $password) == "valid")
        {
            $course_db = CourseManager::get_course_information($course_code);
            $threads_info = get_threads($forum_id, $course_db['db_name']);

            $threads_id = '#';
            foreach ($threads_info as $thread)
            {
                if( isset($thread['thread_id']))
                {
                    $threads_id .= $thread['thread_id']."#";
                }
            }

            return $threads_id;

        } else
            return get_lang('InvalidId');
    }

    public function get_forum_thread_data($username, $password, $course_code, $thread_id, $field)
    {
        if($this->verifyUserPass($username, $password) == "valid")
        {
            $course_db = CourseManager::get_course_information($course_code);
            $table_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY, $course_db['db_name']);
            $table_threads = Database :: get_course_table(TABLE_FORUM_THREAD, $course_db['db_name']);

            $sql="SELECT * FROM ".$table_threads." threads, ".$table_item_property." item_properties
                            WHERE item_properties.tool='".TOOL_FORUM_THREAD."'
                            AND item_properties.ref='".Database::escape_string($thread_id)."'
                            AND threads.thread_id='".Database::escape_string($thread_id)."'";
            $result=Database::query($sql);
            $thread_info=Database::fetch_array($result);

            switch ($field)
            {
                case 'title':
                    $htmlcode = true;
                    $field_table = "thread_title";
                    break;
                case 'date' :
                    $field_table = "thread_date";
                    break;
                case 'sender' :
                    $field_table = "insert_user_id";
                    break;
                case 'sender_name' :
                    $user_id = $thread_info[insert_user_id];
                    $user_info = UserManager::get_user_info_by_id($user_id);
                    return $user_info['firstname'];
                    break;
                default :
                    $field_table = "title";
            }

            return $thread_info[$field_table];

        } else
            return get_lang('InvalidId');
    }

    public function get_forum_thread_title($username, $password, $course_code, $thread_id)
    {
        if($this->verifyUserPass($username, $password) == "valid")
        {
            $course_db = CourseManager::get_course_information($course_code);
            $table_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY, $course_db['db_name']);
            $table_threads = Database :: get_course_table(TABLE_FORUM_THREAD, $course_db['db_name']);

            $sql="SELECT * FROM ".$table_threads." threads, ".$table_item_property." item_properties
                            WHERE item_properties.tool='".TOOL_FORUM_THREAD."'
                            AND item_properties.ref='".Database::escape_string($thread_id)."'
                            AND threads.thread_id='".Database::escape_string($thread_id)."'";
            $result=Database::query($sql);
            $thread_info=Database::fetch_array($result);

            $htmlcode = true;
            $field_table = "thread_title";

            return $thread_info[$field_table];

        } else
            return get_lang('InvalidId');
    }


    public function get_posts_id($username, $password, $course_code, $thread_id)
    {
        if($this->verifyUserPass($username, $password) == "valid")
        {
            $course_db = CourseManager::get_course_information($course_code);

            $table_users = Database :: get_main_table(TABLE_MAIN_USER);
            $table_posts = Database :: get_course_table(TABLE_FORUM_POST, $course_db['db_name']);

            // note: change these SQL so that only the relevant fields of the user table are used
            if (api_is_allowed_to_edit(null,true)) {
                    $sql = "SELECT * FROM $table_posts posts
                                    LEFT JOIN  $table_users users
                                            ON posts.poster_id=users.user_id
                                    WHERE posts.thread_id='".Database::escape_string($thread_id)."'
                                    ORDER BY posts.post_id ASC";
            } else {
                    // students can only se the posts that are approved (posts.visible='1')
                    $sql = "SELECT * FROM $table_posts posts
                                    LEFT JOIN  $table_users users
                                            ON posts.poster_id=users.user_id
                                    WHERE posts.thread_id='".Database::escape_string($thread_id)."'
                                    AND posts.visible='1'
                                    ORDER BY posts.post_id ASC";
            }
            $result=Database::query($sql);
            while ($row=Database::fetch_array($result)) {
                    $posts_info[]=$row;
            }

            $posts_id = '#';

            foreach ($posts_info as $post)
            {
                if( isset($post['post_id']))
                {
                    $posts_id .= $post['post_id']."#";
                }
            }
            return $posts_id;
        } else
            return get_lang('InvalidId');
    }

    public function get_post_data($username, $password, $course_code, $post_id, $field)
    {
        if($this->verifyUserPass($username, $password) == "valid")
        {
            $course_db = CourseManager::get_course_information($course_code);

            $table_posts 	= Database :: get_course_table(TABLE_FORUM_POST, $course_db['db_name']);
            $table_users 	= Database :: get_main_table(TABLE_MAIN_USER);

            $sql="SELECT * FROM ".$table_posts."posts, ".$table_users." users WHERE posts.poster_id=users.user_id AND posts.post_id='".Database::escape_string($post_id)."'";
            $result=Database::query($sql);
            $post_info =Database::fetch_array($result);

            $htmlcode = false;
            switch ($field)
            {
                case 'title':
                    $htmlcode = true;
                    $field_table = "post_title";
                    break;
                case 'text' :
                    $htmlcode = true;
                    $field_table = "post_text";
                    break;
                case 'date' :
                    $field_table = "post_date";
                    break;
                case 'sender' :
                    $field_table = "user_id";
                    break;
                case 'sender_name' :
                    $field_table = "firstname";
                    break;
                default :
                    $htmlcode = true;
                    $field_table = "title";
            }
            return (htmlcode) ? html_entity_decode($post_info[$field_table]) : $post_info[$field_table];
        } else
            return get_lang('InvalidId');
    }

    public function send_post($username, $password, $course_code, $forum_id, $thread_id, $title, $content)
    {
        if($this->verifyUserPass($username, $password) == "valid")
        {
            $course_db = CourseManager::get_course_information($course_code);

            $user_id = UserManager::get_user_id_from_username($username);
            $table_threads = Database :: get_course_table(TABLE_FORUM_THREAD, $course_db['db_name']);
            $forum_table_attachment = Database :: get_course_table(TABLE_FORUM_ATTACHMENT, $course_db['db_name']);
            $table_posts = Database :: get_course_table(TABLE_FORUM_POST, $course_db['db_name']);
            $post_date=date('Y-m-d H:i:s');
            $visible=1;
            $has_attachment=false;
            $my_post = '';
            $post_notification = '';

            $content = nl2br($content);

            $title = htmlentities($title);
            $content = htmlentities($content);

            $sql="INSERT INTO $table_posts (post_title, post_text, thread_id, forum_id, poster_id, post_date, post_notification, post_parent_id, visible)
                            VALUES ('".Database::escape_string($title)."',
                                            '".Database::escape_string(isset($content) ? (api_html_entity_decode($content)) : null)."',
                                            '".Database::escape_string($thread_id)."',
                                            '".Database::escape_string($forum_id)."',
                                            '".Database::escape_string($user_id)."',
                                            '".Database::escape_string($post_date)."',
                                            '".Database::escape_string(isset($post_notification)?$post_notification:null)."',
                                            '".Database::escape_string(isset($my_post)?$my_post:null)."',
                                            '".Database::escape_string($visible)."')";

            
            $result=Database::query($sql);
            return "Post enviado!";
//return $sql;

            //send_notification_mails($thread_id, $values);

        } else
            return get_lang('InvalidId');

    }

}

/*
echo "aqui: ";
$aqui = new WSCMForum();
echo "<pre>";

//print_r($aqui->unreadMessage("aluno", "e695f51fe3dd6b7cf2be3188a614f10f"));
//print_r($aqui->get_post_data("aluno", "c4ca4238a0b923820dcc509a6f75849b", "95", "sender_name"));

print_r($aqui->send_post("aluno", "c4ca4238a0b923820dcc509a6f75849b", "P0304", "3", "15", "títle", "conteúdo222222"));
echo "</pre>";
*/
