<?php
/* For licensing terms, see /license.txt */

/**
 * These files are a complete rework of the forum. The database structure is
 * based on phpBB but all the code is rewritten. A lot of new functionalities
 * are added:
 * - forum categories and forums can be sorted up or down, locked or made invisible
 * - consistent and integrated forum administration
 * - forum options:     are students allowed to edit their post?
 *                      moderation of posts (approval)
 *                      reply only forums (students cannot create new threads)
 *                      multiple forums per group
 * - sticky messages
 * - new view option: nested view
 * - quoting a message 
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @copyright Ghent University
 * @copyright Patrick Cool
 *
 * @package chamilo.forum
 */
/**
 * Code
 */
// Name of the language file that needs to be included.
$language_file = 'forum';

// Including the global initialization file.
require_once '../inc/global.inc.php';

$htmlHeadXtra[] = '<script type="text/javascript">
    $(document).ready(function(){ $(\'.hide-me\').slideUp() });
    function hidecontent(content){ $(content).slideToggle(\'normal\'); }
    </script>';
$htmlHeadXtra[] = '<script type="text/javascript">
        function advanced_parameters() {
            if(document.getElementById(\'options\').style.display == \'none\') {
                    document.getElementById(\'options\').style.display = \'block\';
                    document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_hide.gif',get_lang('Hide'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
            } else {
                    document.getElementById(\'options\').style.display = \'none\';
                    document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
            }
        }
    </script>';

// The section (tabs).
$this_section = SECTION_COURSES;

// Notification for unauthorized people.
api_protect_course_script(true);

// Including additional library scripts.
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

$nameTools = get_lang('Forums');

// Including necessary files.
require_once 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';

/* MAIN DISPLAY SECTION */

/* Header */

if (!empty($_GET['gradebook']) && $_GET['gradebook'] == 'view') {
    $_SESSION['gradebook'] = Security::remove_XSS($_GET['gradebook']);
    $gradebook = $_SESSION['gradebook'];
} elseif (empty($_GET['gradebook'])) {
    unset($_SESSION['gradebook']);
    $gradebook = '';
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
        'url' => '../gradebook/' . $_SESSION['gradebook_dest'],
        'name' => get_lang('ToolGradebook')
    );
}

$search_forum = isset($_GET['search']) ? Security::remove_XSS($_GET['search']) : '';

if (isset($_GET['action']) && $_GET['action'] == 'add') {
    switch ($_GET['content']) {
        case 'forum':
            $interbreadcrumb[] = array('url' => 'index.php?gradebook='.$gradebook.'&amp;search='.$search_forum, 'name' => get_lang('ForumCategories'));
            $interbreadcrumb[] = array('url' =>'#', 'name' => get_lang('AddForum'));
            break;
        case 'forumcategory':
            $interbreadcrumb[] = array('url' =>'index.php?gradebook='.$gradebook.'&amp;search='.$search_forum, 'name' => get_lang('ForumCategories'));
            $interbreadcrumb[] = array('url' =>'#', 'name' => get_lang('AddForumCategory'));
            break;
        default:            
            break;
    }
} else {
    $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('ForumCategories'));    
}

Display::display_header('');

// Tool introduction
Display::display_introduction_section(TOOL_FORUM);

$form_count = 0;

/* ACTIONS */

$get_actions = isset($_GET['action']) ? $_GET['action'] : '';
if (api_is_allowed_to_edit(false, true)) {
	
	//if is called from a learning path lp_id	
	$lp_id = isset($_REQUEST['lp_id']) ? Security::remove_XSS($_REQUEST['lp_id']): null;		
	handle_forum_and_forumcategories($lp_id);
}

// Notification
if (isset($_GET['action']) && $_GET['action'] == 'notify' && isset($_GET['content']) && isset($_GET['id'])) {
    if (api_get_session_id() != 0 && api_is_allowed_to_session_edit(false, true) == false) {
        api_not_allowed();
    }
    $return_message = set_notification($_GET['content'], $_GET['id']);
    Display :: display_confirmation_message($return_message, false);
}

get_whats_new();
$whatsnew_post_info = array();
$whatsnew_post_info = $_SESSION['whatsnew_post_info'];

/* TRACKING */

event_access_tool(TOOL_FORUM);

/*
    RETRIEVING ALL THE FORUM CATEGORIES AND FORUMS
    note: we do this here just after het handling of the actions to be sure that we already incorporate the
    latest changes
*/

// Step 1: We store all the forum categories in an array $forum_categories.
$forum_categories = array();
$forum_categories_list = get_forum_categories();


// Step 2: We find all the forums (only the visible ones if it is a student).
$forum_list	= array();
$forum_list	= get_forums();

$user_id = api_get_user_id();

/* RETRIEVING ALL GROUPS AND THOSE OF THE USER */

// The groups of the user.
$groups_of_user = array();
$groups_of_user = GroupManager::get_group_ids($_course['real_id'], $user_id);
// All groups in the course (and sorting them as the id of the group = the key of the array).
if (!api_is_anonymous()) {
    $all_groups = GroupManager::get_group_list();
    if (is_array($all_groups)) {
        foreach ($all_groups as $group) {
            $all_groups[$group['id']] = $group;
        }
    }
}

/* CLEAN GROUP ID FOR AJAXFILEMANAGER */

if (isset($_SESSION['_gid'])) {
    unset($_SESSION['_gid']);
}

/* ACTION LINKS */

$session_id = isset($_SESSION['id_session']) ? $_SESSION['id_session'] : false;

echo '<div class="actions">';

//if is called from learning path
if (!empty($_GET['lp_id']) || !empty($_POST['lp_id'])){	
    echo "<a href=\"../newscorm/lp_controller.php?".api_get_cidreq()."&gradebook=&action=add_item&type=step&lp_id=".$lp_id."#resource_tab-5\">".Display::return_icon('back.png', get_lang("BackTo").' '.get_lang("LearningPaths"),'',ICON_SIZE_MEDIUM)."</a>";
}
if (!empty($forum_list)) {
    echo search_link();
}

if (api_is_allowed_to_edit(false, true)) {
    echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;gradebook='.$gradebook.'&amp;action=add&amp;content=forumcategory&amp;lp_id='.$lp_id.'"> '.Display::return_icon('new_folder.png', get_lang('AddForumCategory'),'',ICON_SIZE_MEDIUM).'</a>';
    if (is_array($forum_categories_list) and !empty($forum_categories_list)) {
        echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;gradebook='.$gradebook.'&amp;action=add&amp;content=forum&amp;lp_id='.$lp_id.'"> '.Display::return_icon('new_forum.png', get_lang('AddForum'),'',ICON_SIZE_MEDIUM).'</a>';
    }
}

echo '</div>';

/* Display Forum Categories and the Forums in it */

// Step 3: We display the forum_categories first.
if (is_array($forum_categories_list)) {
    foreach ($forum_categories_list as $forum_category_key => $forum_category) {

         // The forums in this category.
        $forums_in_category = get_forums_in_category($forum_category['cat_id']);
        
        echo '<table class="forum_table">';

        // Validacion when belongs to a session.
        $session_img = api_get_session_image($forum_category['session_id'], $_user['status']);

        if ((!isset($_SESSION['id_session']) || $_SESSION['id_session'] == 0) && !empty($forum_category['session_name'])) {
            $session_displayed = ' ('.Security::remove_XSS($forum_category['session_name']).')';
        } else {
            $session_displayed = '';
        }
        echo '<thead>';
        echo '<tr><th class="forum_head" colspan="5">';
        echo '<a href="viewforumcategory.php?'.api_get_cidreq().'&amp;forumcategory='.strval(intval($forum_category['cat_id'])).'" '.class_visible_invisible(strval(intval($forum_category['visibility']))).'>'.prepare4display($forum_category['cat_title']).$session_displayed.'</a>'. $session_img .'<br />';

        if ($forum_category['cat_comment'] != '' && trim($forum_category['cat_comment']) != '&nbsp;') {
            echo '<span class="forum_description">'.prepare4display($forum_category['cat_comment']).'</span>';
        }
        echo '</th>';

        echo '<th style="vertical-align: top;" align="center" >';
        if (api_is_allowed_to_edit(false, true) && !($forum_category['session_id'] == 0 && intval($session_id) != 0)) {
            echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;gradebook='.$gradebook.'&amp;action=edit&amp;content=forumcategory&amp;id='.intval($forum_category['cat_id']).'">'.Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL).'</a>';
            echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;gradebook='.$gradebook.'&amp;action=delete&amp;content=forumcategory&amp;id='.intval($forum_category['cat_id'])."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('DeleteForumCategory'), ENT_QUOTES))."')) return false;\">".Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL).'</a>';
            display_visible_invisible_icon('forumcategory', strval(intval($forum_category['cat_id'])), strval(intval($forum_category['visibility'])));
            display_lock_unlock_icon('forumcategory', strval(intval($forum_category['cat_id'])), strval(intval($forum_category['locked'])));
            display_up_down_icon('forumcategory', strval(intval($forum_category['cat_id'])), $forum_categories_list);
        }
        echo '</th>';
        echo '</tr>';
        echo '</thead>';

        if (!empty($forums_in_category)) {

            // Step 4: The interim headers (for the forum).
            echo '<tr class="forum_header">';
            echo '<td></td>';
            echo '<td>'.get_lang('Forum').'</td>';
            echo '<td>'.get_lang('Topics').'</td>';
            echo '<td>'.get_lang('Posts').'</td>';
            echo '<td>'.get_lang('LastPosts').'</td>';
            echo '<td>'.get_lang('Actions').'</td>';
            echo '</tr>';

            // Step 5: We display all the forums in this category.
            $forum_count = 0;

            foreach ($forum_list as $key => $forum) {
                // Here we clean the whatnew_post_info array a little bit because to display the icon we
                // test if $whatsnew_post_info[$forum['forum_id']] is empty or not.
                if (!empty($whatsnew_post_info)) {
                    if (is_array(isset($whatsnew_post_info[$forum['forum_id']]) ? $whatsnew_post_info[$forum['forum_id']] : null)) {
                        foreach ($whatsnew_post_info[$forum['forum_id']] as $key_thread_id => $new_post_array) {
                            if (empty($whatsnew_post_info[$forum['forum_id']][$key_thread_id]))	{
                                unset($whatsnew_post_info[$forum['forum_id']][$key_thread_id]);
                                unset($_SESSION['whatsnew_post_info'][$forum['forum_id']][$key_thread_id]);
                            }
                        }
                    }
                }

                // Note: This can be speeded up if we transform the $forum_list to an array that uses the forum_category as the key.
                if ($forum['forum_category'] == $forum_category['cat_id']) {                    
                    // The forum has to be showed if
                    // 1.v it is a not a group forum (teacher and student)
                    // 2.v it is a group forum and it is public (teacher and student)
                    // 3. it is a group forum and it is private (always for teachers only if the user is member of the forum
                    // if the forum is private and it is a group forum and the user is not a member of the group forum then it cannot be displayed
                    //if (!($forum['forum_group_public_private']=='private' AND !is_null($forum['forum_of_group']) AND !in_array($forum['forum_of_group'], $groups_of_user)))
                    //{
                    $show_forum = false;

                    // SHOULD WE SHOW THIS PARTICULAR FORUM
                    // you are teacher => show forum

                    if (api_is_allowed_to_edit(false, true)) {
                        //echo 'teacher';
                        $show_forum = true;
                    } else {
                        // you are not a teacher
                        //echo 'student';
                        // it is not a group forum => show forum (invisible forums are already left out see get_forums function)
                        if ($forum['forum_of_group'] == '0') {
                            //echo '-gewoon forum';
                            $show_forum = true;
                        } else {
                            $show_forum = GroupManager::user_has_access($user_id, $forum['forum_of_group'], GROUP_TOOL_FORUM);                                   
                            //var_dump($forum['forum_id'].' -  '.$show_forum);
                            /*
                            // it is a group forum
                            //echo '-groepsforum';
                            // it is a group forum but it is public => show
                            if ($forum['forum_group_public_private'] == 'public') {
                                $show_forum = true;
                                //echo '-publiek';
                            } elseif ($forum['forum_group_public_private'] == 'private') {
                                // it is a group forum and it is private
                                //echo '-prive';
                                // it is a group forum and it is private but the user is member of the group
                                if (in_array($forum['forum_of_group'], $groups_of_user)) {
                                    //echo '-is lid';
                                    $show_forum = true;
                                } else {
                                    //echo '-is GEEN lid';
                                    $show_forum = false;
                                }
                            } else {
                                $show_forum = false;
                            }*/

                        }
                    }

                    if ($show_forum) {
                        $form_count++;
                        $mywhatsnew_post_info = isset($whatsnew_post_info[$forum['forum_id']]) ? $whatsnew_post_info[$forum['forum_id']] : null;

                        $forum_image = '';

                        echo '<td width="20">';

                        // Showing the image
                        if (!empty($forum['forum_image'])) {

                            $image_path = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/upload/forum/images/'.$forum['forum_image'];
                            $image_size = api_getimagesize($image_path);

                            $img_attributes = '';
                            if (!empty($image_size)) {
                                if ($image_size['width'] > 100 || $image_size['height'] > 100) {
                                    //limit display width and height to 100px
                                    $img_attributes = 'width="100" height="100"';
                                }
                                $forum_image =  "<img src=\"$image_path\" $img_attributes>";
                            } else {
                                $forum_image = '';
                            }
                            echo $forum_image;

                        } else {
                            if ($forum['forum_of_group'] !== '0') {
                                if (is_array($mywhatsnew_post_info) && !empty($mywhatsnew_post_info)) {
                                    echo Display::return_icon('forumgroupnew.gif');
                                } else {
                                    echo Display::return_icon('forumgroup.gif', get_lang('GroupForum'));
                                }
                            } else {
                                if (is_array($mywhatsnew_post_info) && !empty($mywhatsnew_post_info)) {
                                    echo Display::return_icon('forum.gif', get_lang('Forum'));
                                } else {
                                    echo Display::return_icon('forum.gif');
                                }
                            }
                        }

                        echo '</td>';

                        // Validacion when belongs to a session
                        $session_img = api_get_session_image($forum['session_id'], $_user['status']);

                        if ($forum['forum_of_group'] != '0') {
                            $my_all_groups_forum_name = isset($all_groups[$forum['forum_of_group']]['name']) ? $all_groups[$forum['forum_of_group']]['name'] : null;
                            $my_all_groups_forum_id = isset($all_groups[$forum['forum_of_group']]['id']) ? $all_groups[$forum['forum_of_group']]['id'] : null;
                            $group_title = api_substr($my_all_groups_forum_name, 0, 30);
                            $forum_title_group_addition = ' (<a href="../group/group_space.php?'.api_get_cidreq().'&amp;gidReq='.$forum['forum_of_group'].'" class="forum_group_link">'.get_lang('GoTo').' '.$group_title.'</a>)' . $session_img;
                        } else {
                            $forum_title_group_addition = '';
                        }

                        if ((!isset($_SESSION['id_session']) || $_SESSION['id_session'] == 0) && !empty($forum['session_name'])) {
                            $session_displayed = ' ('.$forum['session_name'].')';
                        } else {
                            $session_displayed = '';
                        }
                        $forum['forum_of_group'] == 0 ? $groupid = '' : $groupid = $forum['forum_of_group'];

                        echo '<td><a href="viewforum.php?'.api_get_cidreq().'&amp;gidReq='.intval($groupid).'&amp;forum='.intval($forum['forum_id']).'" '.class_visible_invisible(strval(intval($forum['visibility']))).'>';

                        //Forum title
                        echo prepare4display($forum['forum_title']).$session_displayed.'</a>'.$forum_title_group_addition.'<br />';

                        echo '<span class="forum_description">'.prepare4display($forum['forum_comment']).'</span>';
                        echo '</td>';

                        //$number_forum_topics_and_posts = get_post_topics_of_forum($forum['forum_id']); // deprecated

                        // The number of topics and posts.
                        $number_threads = isset($forum['number_of_threads']) ? $forum['number_of_threads'] : null;
                        $number_posts = isset($forum['number_of_posts']) ? $forum['number_of_posts'] : null;
                        echo '<td>'.$number_threads.'</td>';
                        echo '<td>'.$number_posts.'</td>';
                        // The last post in the forum.
                        if ($forum['last_poster_name'] != '') {
                            $name = $forum['last_poster_name'];
                            $poster_id = 0;
                            $username = "";
                        } else {
                            $name = api_get_person_name($forum['last_poster_firstname'], $forum['last_poster_lastname']);
                            $poster_id = $forum['last_poster_id'];
                            $userinfo = api_get_user_info($poster_id);
                            $username = sprintf(get_lang('LoginX'), $userinfo['username']);
                        }
                        echo '<td nowrap="nowrap">';

                        if (!empty($forum['last_post_id'])) {
                            echo api_convert_and_format_date($forum['last_post_date']).'<br /> '.get_lang('By').' '.display_user_link($poster_id, $name, '', $username);
                        }
                        echo '</td>';
                        echo '<td nowrap="nowrap" align="center">';
                        if (api_is_allowed_to_edit(false, true) && !($forum['session_id'] == 0 && intval($session_id) != 0)) {
                            echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;gradebook='.$gradebook.'&amp;action=edit&amp;content=forum&amp;id='.$forum['forum_id'].'">'.Display::return_icon('edit.png',get_lang('Edit'), array(), ICON_SIZE_SMALL).'</a>';
                            echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;gradebook='.$gradebook.'&amp;action=delete&amp;content=forum&amp;id='.$forum['forum_id']."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('DeleteForum'), ENT_QUOTES))."')) return false;\">".Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL).'</a>';
                            display_visible_invisible_icon('forum', $forum['forum_id'], $forum['visibility']);
                            display_lock_unlock_icon('forum', $forum['forum_id'], $forum['locked']);
                            display_up_down_icon('forum', $forum['forum_id'], $forums_in_category);
                        }
                        $iconnotify = 'send_mail.gif';
                        $session_forum_notification = isset($_SESSION['forum_notification']['forum']) ? $_SESSION['forum_notification']['forum'] : false;
                        if (is_array($session_forum_notification)) {
                            if (in_array($forum['forum_id'], $session_forum_notification)) {
                                $iconnotify = 'send_mail_checked.gif';
                            }
                        }

                        if (!api_is_anonymous() && api_is_allowed_to_session_edit(false, true) ) {
                            echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;gradebook='.$gradebook.'&amp;action=notify&amp;content=forum&amp;id='.$forum['forum_id'].'">'.Display::return_icon($iconnotify, get_lang('NotifyMe')).'</a>';
                        }
                        echo '</td></tr>';
                    }
                }
            }
        } else {     
            echo '<tr><td>'.get_lang('NoForumInThisCategory').'</td>'.(api_is_allowed_to_edit(false, true) ? '<td colspan="6"></td>' : '<td colspan="6"></td>').'</tr>';     
        }
        echo '</table>';
    }
}

Display :: display_footer();