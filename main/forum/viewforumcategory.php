<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CForumPost;
use ChamiloSession as Session;

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
 * - quoting a message.
 *
 * @Author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @Copyright Ghent University
 * @Copyright Patrick Cool
 *
 * @package chamilo.forum
 */
require_once __DIR__.'/../inc/global.inc.php';

Session::erase('_gid');

// Notification for unauthorized people.
api_protect_course_script(true);

$htmlHeadXtra[] = '<script>
$(function() {
    $(\'.hide-me\').slideUp()
});

function hidecontent(content){
    $(content).slideToggle(\'normal\');
}
</script>';

// The section (tabs)
$this_section = SECTION_COURSES;

// Including additional library scripts.
$nameTools = get_lang('ToolForum');

$_user = api_get_user_info();
$_course = api_get_course_info();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$hideNotifications = api_get_course_setting('hide_forum_notifications');
$hideNotifications = $hideNotifications == 1;

require_once 'forumfunction.inc.php';

// Are we in a lp ?
$origin = api_get_origin();

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
}

$sessionId = api_get_session_id();
$current_forum_category = get_forum_categories($_GET['forumcategory']);
$interbreadcrumb[] = [
    'url' => 'index.php?'.api_get_cidreq().'&search='.Security::remove_XSS(urlencode(isset($_GET['search']) ? $_GET['search'] : '')),
    'name' => get_lang('Forum'),
];

if (!empty($action) && !empty($_GET['content'])) {
    if ($action == 'add' && $_GET['content'] == 'forum') {
        $interbreadcrumb[] = [
            'url' => 'viewforumcategory.php?'.api_get_cidreq().'&forumcategory='.$current_forum_category['cat_id'],
            'name' => $current_forum_category['cat_title'],
        ];
        $interbreadcrumb[] = [
            'url' => '#',
            'name' => get_lang('AddForum'),
        ];
    }
} else {
    $interbreadcrumb[] = [
        'url' => '#',
        'name' => $current_forum_category['cat_title'],
    ];
}

if ($origin == 'learnpath') {
    Display::display_reduced_header();
} else {
    Display::display_header(null);
}

/* ACTIONS */
$whatsnew_post_info = isset($_SESSION['whatsnew_post_info']) ? $_SESSION['whatsnew_post_info'] : null;

/* Is the user allowed here? */

// if the user is not a course administrator and the forum is hidden
// then the user is not allowed here.
if (!api_is_allowed_to_edit(false, true) &&
    ($current_forum_category && $current_forum_category['visibility'] == 0)
) {
    api_not_allowed();
}

/* Action Links */
$html = '<div class="actions">';
$html .= '<a href="index.php?'.api_get_cidreq().'">'.
    Display::return_icon('back.png', get_lang('BackToForumOverview'), '', ICON_SIZE_MEDIUM).'</a>';
if (api_is_allowed_to_edit(false, true)) {
    $html .= '<a href="'.api_get_path(WEB_CODE_PATH).'forum/index.php?'.api_get_cidreq().'&forumcategory='
        .$current_forum_category['cat_id'].'&action=add&content=forum"> '
        .Display::return_icon('new_forum.png', get_lang('AddForum'), '', ICON_SIZE_MEDIUM).'</a>';
}
$html .= search_link();
$html .= '</div>';

echo $html;

$logInfo = [
    'tool' => TOOL_FORUM,
    'action' => $action,
    'info' => isset($_GET['content']) ? $_GET['content'] : '',
];
Event::registerLog($logInfo);

if (api_is_allowed_to_edit(false, true)) {
    handle_forum_and_forumcategories();
}

// Notification
if ($action == 'notify' && isset($_GET['content']) && isset($_GET['id'])) {
    $return_message = set_notification($_GET['content'], $_GET['id']);
    echo Display::return_message($return_message, 'confirm', false);
}

if ($action != 'add') {
    /*
    RETRIEVING ALL THE FORUM CATEGORIES AND FORUMS
    Note: We do this here just after het handling of the actions to be sure that we already incorporate the
    latest changes.
    */
    // Step 1: We store all the forum categories in an array $forum_categories.
    $forum_categories = [];
    $forum_category = get_forum_categories($_GET['forumcategory']);

    // Step 2: We find all the forums.
    $forum_list = get_forums();

    /* RETRIEVING ALL GROUPS AND THOSE OF THE USER */

    // The groups of the user.
    $groups_of_user = GroupManager::get_group_ids($_course['real_id'], $_user['user_id']);
    // All groups in the course (and sorting them as the id of the group = the key of the array.
    $all_groups = GroupManager::get_group_list();
    if (is_array($all_groups)) {
        foreach ($all_groups as $group) {
            $all_groups[$group['id']] = $group;
        }
    }

    /* Display Forum Categories and the Forums in it */
    $html = '';
    $html .= '<div class="category-forum">';

    $session_displayed = '';
    if ((!isset($sessionId) || $sessionId == 0) &&
        !empty($forum_category['session_name'])
    ) {
        $session_displayed = ' ('.Security::remove_XSS($forum_category['session_name']).')';
    }

    $forum_categories_list = [];
    $forumId = $forum_category['cat_id'];
    $forumTitle = Security::remove_XSS($forum_category['cat_title']);
    $linkForumCategory = 'viewforumcategory.php?'.api_get_cidreq().'&forumcategory='.strval(intval($forumId));
    $descriptionCategory = $forum_category['cat_comment'];
    $icoCategory = Display::return_icon(
        'forum_blue.png',
        get_lang($forum_category['cat_title']),
        ['class' => ''],
        ICON_SIZE_MEDIUM
    );

    if (api_is_allowed_to_edit(false, true) &&
        !($forum_category['session_id'] == 0 && $sessionId != 0)
    ) {
        $iconsEdit = '<a href="'.api_get_self().'?'.api_get_cidreq().'&forumcategory='
            .Security::remove_XSS($_GET['forumcategory']).'&action=edit&content=forumcategory&id='
            .''.$forumId.'">'
            .Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL).'</a>';
        $iconsEdit .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&forumcategory='
            .Security::remove_XSS($_GET['forumcategory'])
            .'&action=delete&content=forumcategory&id='.$forumId
            ."\" onclick=\"javascript:if(!confirm('"
            .addslashes(api_htmlentities(get_lang('DeleteForumCategory'), ENT_QUOTES))
            ."')) return false;\">".Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL)
            .'</a>';
        $iconsEdit .= return_visible_invisible_icon(
            'forumcategory',
            $forum_category['cat_id'],
            $forum_category['visibility'],
            ['forumcategory' => $_GET['forumcategory']]
        );
        $iconsEdit .= return_lock_unlock_icon(
            'forumcategory',
            $forum_category['cat_id'],
            $forum_category['locked'],
            ['forumcategory' => $_GET['forumcategory']]
        );
        $iconsEdit .= return_up_down_icon(
            'forumcategory',
            $forum_category['cat_id'],
            $forum_categories_list
        );
        $html .= Display::tag(
            'div',
            $iconsEdit,
            ['class' => 'pull-right']
        );
    }

    $session_img = api_get_session_image($forum_category['session_id'], $_user['status']);

    $html .= Display::tag(
        'h3',
        $icoCategory.
        Display::tag(
            'a',
            $forumTitle,
            [
                'href' => $linkForumCategory,
                'class' => empty($forum_category['visibility']) ? 'text-muted' : null,
            ]
        ).$session_displayed.$session_img,
        null
    );

    if ($descriptionCategory != '' && trim($descriptionCategory) != '&nbsp;') {
        $html .= '<div class="forum-description">'.$descriptionCategory.'</div>';
    }

    $html .= '</div>';
    echo $html;
    echo '<div class="forum_display">';
    // The forums in this category.
    $forums_in_category = get_forums_in_category($forum_category['cat_id']);

    // Step 4: We display all the forums in this category.
    $forum_count = 0;
    foreach ($forum_list as $forum) {
        if (!empty($forum['forum_category'])) {
            if ($forum['forum_category'] == $forum_category['cat_id']) {
                // The forum has to be showed if
                // 1.v it is a not a group forum (teacher and student)
                // 2.v it is a group forum and it is public (teacher and student)
                // 3. it is a group forum and it is private (always for teachers only if the user is member of the forum
                // if the forum is private and it is a group forum and the user
                // is not a member of the group forum then it cannot be displayed
                $show_forum = false;
                // SHOULD WE SHOW THIS PARTICULAR FORUM
                // you are teacher => show forum

                if (api_is_allowed_to_edit(false, true)) {
                    $show_forum = true;
                } else {
                    // you are not a teacher
                    // it is not a group forum => show forum
                    // (invisible forums are already left out see get_forums function)
                    if ($forum['forum_of_group'] == '0') {
                        $show_forum = true;
                    } else {
                        // it is a group forum
                        //echo '-groepsforum';
                        // it is a group forum but it is public => show
                        if ($forum['forum_group_public_private'] == 'public') {
                            $show_forum = true;
                        } else {
                            // it is a group forum and it is private
                            //echo '-prive';
                            // it is a group forum and it is private but the user is member of the group
                            if (in_array($forum['forum_of_group'], $groups_of_user)) {
                                $show_forum = true;
                            } else {
                                $show_forum = false;
                            }
                        }
                    }
                }
                $form_count = isset($form_count) ? $form_count : 0;
                if ($show_forum === true) {
                    $form_count++;
                    $html = '<div class="panel panel-default forum">';
                    $html .= '<div class="panel-body">';
                    $my_whatsnew_post_info = isset($whatsnew_post_info[$forum['forum_id']]) ? $whatsnew_post_info[$forum['forum_id']] : null;

                    if ($forum['forum_of_group'] == '0') {
                        $forum_image = Display::return_icon(
                            'forum_group.png',
                            get_lang('GroupForum'),
                            null,
                            ICON_SIZE_LARGE
                        );
                    } else {
                        $forum_image = Display::return_icon(
                            'forum.png',
                            get_lang('Forum'),
                            null,
                            ICON_SIZE_LARGE
                        );
                    }

                    if ($forum['forum_of_group'] != '0') {
                        $my_all_groups_forum_name = isset($all_groups[$forum['forum_of_group']]['name'])
                            ? $all_groups[$forum['forum_of_group']]['name']
                            : null;
                        $my_all_groups_forum_id = isset($all_groups[$forum['forum_of_group']]['id'])
                            ? $all_groups[$forum['forum_of_group']]['id']
                            : null;
                        $group_title = api_substr($my_all_groups_forum_name, 0, 30);
                        $forum_title_group_addition = ' (<a href="../group/group_space.php?'.api_get_cidreq()
                            .'&gidReq='.$my_all_groups_forum_id.'" class="forum_group_link">'
                            .get_lang('GoTo').' '.$group_title.'</a>)';
                    } else {
                        $forum_title_group_addition = '';
                    }

                    if (!empty($sessionId) && !empty($forum['session_name'])) {
                        $session_displayed = ' ('.$forum['session_name'].')';
                    } else {
                        $session_displayed = '';
                    }

                    // the number of topics and posts
                    $my_number_threads = isset($forum['number_of_threads']) ? $forum['number_of_threads'] : 0;
                    $my_number_posts = isset($forum['number_of_posts']) ? $forum['number_of_posts'] : 0;

                    $html .= '<div class="row">';
                    $html .= '<div class="col-md-6">';
                    $html .= '<div class="col-md-3">';
                    $html .= '<div class="number-post">'.$forum_image.
                        '<p>'.$my_number_threads.' '.get_lang('ForumThreads').'</p></div>';
                    $html .= '</div>';

                    $html .= '<div class="col-md-9">';
                    $iconForum = Display::return_icon(
                        'forum_yellow.png',
                        Security::remove_XSS(get_lang($forum_category['cat_title'])),
                        null,
                        ICON_SIZE_MEDIUM
                    );

                    $linkForum = Display::tag(
                        'a',
                        Security::remove_XSS($forum['forum_title']).$session_displayed,
                        [
                            'href' => 'viewforum.php?'.api_get_cidreq(true, false)."&gidReq={$forum['forum_of_group']}&forum={$forum['forum_id']}&search=".Security::remove_XSS(urlencode(isset($_GET['search']) ? $_GET['search'] : '')),
                            'class' => empty($forum['visibility']) ? 'text-muted' : null,
                        ]
                    );
                    $html .= Display::tag(
                        'h3',
                        $linkForum.' '.$forum_title_group_addition,
                        [
                            'class' => 'title',
                        ]
                    );
                    $html .= Display::tag(
                        'p',
                        Security::remove_XSS(strip_tags($forum['forum_comment'])),
                        [
                            'class' => 'description',
                        ]
                    );

                    if ($forum['moderated'] == 1 && api_is_allowed_to_edit(false, true)) {
                        $waitingCount = getCountPostsWithStatus(
                            CForumPost::STATUS_WAITING_MODERATION,
                            $forum
                        );
                        if (!empty($waitingCount)) {
                            $html .= Display::label(
                                get_lang('PostsPendingModeration').': '.$waitingCount,
                                'warning'
                            );
                        }
                    }

                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<div class="col-md-6">';
                    $iconEmpty = '';
                    // The number of topics and posts.
                    if ($forum['forum_of_group'] !== '0') {
                        $newPost = $iconEmpty;
                        if (is_array($my_whatsnew_post_info) && !empty($my_whatsnew_post_info)) {
                            $newPost = ' '.Display::return_icon('alert.png', get_lang('Forum'), null, ICON_SIZE_SMALL);
                        }
                    } else {
                        $newPost = $iconEmpty;
                        if (is_array($my_whatsnew_post_info) && !empty($my_whatsnew_post_info)) {
                            $newPost = ' '.Display::return_icon('alert.png', get_lang('Forum'), null, ICON_SIZE_SMALL);
                        }
                    }

                    $html .= '<div class="row">';
                    $html .= '<div class="col-md-2">';
                    $html .= $newPost.'</div>';

                    $poster_id = 0;
                    $name = '';
                    // the last post in the forum
                    if (isset($forum['last_poster_name']) && $forum['last_poster_name'] != '') {
                        $name = $forum['last_poster_name'];
                    } else {
                        if (isset($forum['last_poster_lastname'])) {
                            $name = api_get_person_name($forum['last_poster_firstname'], $forum['last_poster_lastname']);
                            $poster_id = $forum['last_poster_id'];
                        }
                    }
                    $html .= '<div class="col-md-6">';
                    if (!empty($forum['last_post_id'])) {
                        $html .= Display::return_icon('post-item.png', null, null, ICON_SIZE_TINY).' ';
                        $html .= Display::dateToStringAgoAndLongDate($forum['last_post_date'])
                            .' '.get_lang('By').' '
                            .display_user_link($poster_id, $name);
                    }
                    $html .= '</div>';
                    $html .= '<div class="col-md-4">';

                    $url = api_get_path(WEB_CODE_PATH).'forum/index.php';
                    $forumCategoryId = (int) $_GET['forumcategory'];

                    if (api_is_allowed_to_edit(false, true) &&
                        !($forum['session_id'] == 0 && $sessionId != 0)
                    ) {
                        $html .= '<a href="'.$url.'?'.api_get_cidreq().'&forumcategory='.$forumCategoryId.'&action=edit&content=forum&id='.$forum['forum_id'].'">'
                            .Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL).'</a>';
                        $html .= '<a href="'.$url.'?'.api_get_cidreq().'&forumcategory='.$forumCategoryId.'&action=delete&content=forum&id='.$forum['forum_id']
                            ."\" onclick=\"javascript:if(!confirm('"
                            .addslashes(api_htmlentities(get_lang('DeleteForum'), ENT_QUOTES))
                            ."')) return false;\">"
                            .Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL)
                            .'</a>';
                        $html .= return_visible_invisible_icon(
                            'forum',
                            $forum['forum_id'],
                            $forum['visibility'],
                            ['forumcategory' => $_GET['forumcategory']]
                        );
                        $html .= return_lock_unlock_icon(
                            'forum',
                            $forum['forum_id'],
                            $forum['locked'],
                            ['forumcategory' => $_GET['forumcategory']]
                        );
                        $html .= return_up_down_icon(
                            'forum',
                            $forum['forum_id'],
                            $forums_in_category
                        );
                    }

                    $iconnotify = 'notification_mail_na.png';
                    if (is_array(isset($_SESSION['forum_notification']['forum']) ? $_SESSION['forum_notification']['forum'] : null)) {
                        if (in_array($forum['forum_id'], $_SESSION['forum_notification']['forum'])) {
                            $iconnotify = 'notification_mail.png';
                        }
                    }

                    if (!api_is_anonymous() && $hideNotifications == false) {
                        $html .= '<a href="'.$url.'?'.api_get_cidreq().'&forumcategory='.$forumCategoryId.'&action=notify&content=forum&id='.$forum['forum_id'].'">'.
                            Display::return_icon($iconnotify, get_lang('NotifyMe')).
                        '</a>';
                    }
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div></div>';
                }
                echo $html;
            }
        }
    }
    if (count($forum_list) == 0) {
        echo '<div class="alert alert-warning">'.get_lang('NoForumInThisCategory').'</div>';
    }
    echo '</div>';
}

/* FOOTER */
if ($origin != 'learnpath') {
    Display::display_footer();
}
