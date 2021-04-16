<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumNotification;
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
 */
require_once __DIR__.'/../inc/global.inc.php';

$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);
$htmlHeadXtra[] = '<script>

function check_unzip() {
    if (document.upload.unzip.checked){
        document.upload.if_exists[0].disabled=true;
        document.upload.if_exists[1].checked=true;
        document.upload.if_exists[2].disabled=true;
    } else {
        document.upload.if_exists[0].checked=true;
        document.upload.if_exists[0].disabled=false;
        document.upload.if_exists[2].disabled=false;
    }
}
function setFocus() {
    $("#title_file").focus();
}
</script>';
// The next javascript script is to manage ajax upload file
$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);

// Recover Thread ID, will be used to generate delete attachment URL to do ajax
$threadId = isset($_REQUEST['thread']) ? (int) ($_REQUEST['thread']) : 0;
$forumId = isset($_REQUEST['forum']) ? (int) ($_REQUEST['forum']) : 0;

$ajaxUrl = api_get_path(WEB_AJAX_PATH).'forum.ajax.php?'.api_get_cidreq();
// The next javascript script is to delete file by ajax
$htmlHeadXtra[] = '<script>
$(function () {
    $(document).on("click", ".deleteLink", function(e) {
        e.preventDefault();
        e.stopPropagation();
        var l = $(this);
        var id = l.closest("tr").attr("id");
        var filename = l.closest("tr").find(".attachFilename").html();
        if (confirm("'.get_lang('Are you sure to delete').'", filename)) {
            $.ajax({
                type: "POST",
                url: "'.$ajaxUrl.'&a=delete_file&attachId=" + id +"&thread='.$threadId.'&forum='.$forumId.'",
                dataType: "json",
                success: function(data) {
                    if (data.error == false) {
                        l.closest("tr").remove();
                        if ($(".files td").length < 1) {
                            $(".files").closest(".control-group").hide();
                        }
                    }
                }
            })
        }
    });
});
</script>';

Session::erase('_gid');

api_protect_course_script(true);

$repo = Container::getForumCategoryRepository();

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
$nameTools = get_lang('Forums');
$action = isset($_GET['action']) ? $_GET['action'] : '';
$categoryId = isset($_GET['forumcategory']) ? $_GET['forumcategory'] : 0;

$mainUrl = api_get_path(WEB_CODE_PATH).'forum/index.php?'.api_get_cidreq();
$url = api_get_path(WEB_CODE_PATH).'forum/viewforumcategory.php?'.api_get_cidreq().'&forumcategory='.$categoryId;
$formContent = handleForum($url);

if (empty($categoryId)) {
    header('Location: '.$mainUrl);
    exit;
}

$_user = api_get_user_info();
$_course = api_get_course_info();
$courseEntity = api_get_course_entity();
$sessionEntity = api_get_session_entity();
$hideNotifications = api_get_course_setting('hide_forum_notifications');
$hideNotifications = 1 == $hideNotifications;

/** @var CForumCategory $forumCategory */
$forumCategory = $repo->find($categoryId);
if (null === $forumCategory) {
    header('Location: '.$mainUrl);
    exit;
}
$categoryId = $forumCategory->getIid();

// Are we in a lp ?
$origin = api_get_origin();

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

$sessionId = api_get_session_id();
$interbreadcrumb[] = [
    'url' => 'index.php?'.api_get_cidreq().'&search='.Security::remove_XSS(urlencode(isset($_GET['search']) ? $_GET['search'] : '')),
    'name' => get_lang('Forum'),
];

if (!empty($action) && !empty($_GET['content'])) {
    if ('add' === $action) {
        $interbreadcrumb[] = [
            'url' => 'viewforumcategory.php?'.api_get_cidreq().'&forumcategory='.$categoryId,
            'name' => $forumCategory->getCatTitle(),
        ];
        $interbreadcrumb[] = [
            'url' => '#',
            'name' => get_lang('Add a forum'),
        ];
    }
} else {
    $interbreadcrumb[] = [
        'url' => '#',
        'name' => $forumCategory->getCatTitle(),
    ];
}

if ('learnpath' === $origin) {
    Display::display_reduced_header();
} else {
    Display::display_header(null);
}

$whatsnew_post_info = isset($_SESSION['whatsnew_post_info']) ? $_SESSION['whatsnew_post_info'] : null;

/* Is the user allowed here? */
$categoryIsVisible = $forumCategory->isVisible($courseEntity, $sessionEntity);
// if the user is not a course administrator and the forum is hidden
// then the user is not allowed here.
if (!api_is_allowed_to_edit(false, true) && false === $categoryIsVisible) {
    api_not_allowed();
}

$actions = '<a href="'.$url.'">'.
    Display::return_icon('back.png', get_lang('Back to forum overview'), '', ICON_SIZE_MEDIUM).'</a>';
if (api_is_allowed_to_edit(false, true)) {
    $actions .= '<a href="'.$mainUrl.'&forumcategory='.$categoryId.'&action=add_forum"> '
        .Display::return_icon('new_forum.png', get_lang('Add a forum'), '', ICON_SIZE_MEDIUM).'</a>';
}
$actions .= search_link();

echo Display::toolbarAction('toolbar', [$actions]);

$logInfo = [
    'tool' => TOOL_FORUM,
    'action' => $action,
    'info' => $_GET['content'] ?? '',
];
Event::registerLog($logInfo);

if ('add' !== $action) {
    // Step 2: We find all the forums.
    $forum_list = get_forums();
    // The groups of the user.
    $groups_of_user = GroupManager::get_group_ids($_course['real_id'], $_user['user_id']);
    // All groups in the course (and sorting them as the id of the group = the key of the array.
    $groups = GroupManager::get_group_list();
    if (is_array($groups)) {
        foreach ($groups as $group) {
            $groups[$group['iid']] = $group;
        }
    }

    /* Display Forum Categories and the Forums in it */
    $html = '';
    $html .= '<div class="category-forum">';
    $session_displayed = '';
    //$sessionCategoryId = $forumCategory->getSessionId();
    $sessionCategoryId = 0;
    if (!empty($sessionCategoryId)) {
        $sessionInfo = api_get_session_entity($sessionCategoryId);
        $session_displayed = ' ('.Security::remove_XSS($sessionInfo->getName()).')';
    }

    $forum_categories_list = [];
    $linkForumCategory = 'viewforumcategory.php?'.api_get_cidreq().'&forumcategory='.$categoryId;
    $descriptionCategory = $forumCategory->getCatComment();
    $icoCategory = Display::return_icon(
        'forum_blue.png',
        $forumCategory->getCatTitle(),
        ['class' => ''],
        ICON_SIZE_MEDIUM
    );

    if (api_is_allowed_to_edit(false, true) &&
        !(0 == $sessionCategoryId && 0 != $sessionId)
    ) {
        $iconsEdit = '<a href="'.$mainUrl.'&action=edit_category&id='.$categoryId.'">'
            .Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL).'</a>';
        $iconsEdit .= '<a href="'.$url.'&action=delete&content=forumcategory&id='.$categoryId
            ."\" onclick=\"javascript:if(!confirm('"
            .addslashes(api_htmlentities(get_lang('Delete forum category ?'), ENT_QUOTES))
            ."')) return false;\">".Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL)
            .'</a>';
        $iconsEdit .= return_visible_invisible_icon(
            'forumcategory',
            $categoryId,
            $categoryIsVisible,
            ['forumcategory' => $_GET['forumcategory']]
        );
        $iconsEdit .= return_lock_unlock_icon(
            'forumcategory',
            $categoryId,
            $forumCategory->getLocked(),
            ['forumcategory' => $_GET['forumcategory']]
        );
        $iconsEdit .= return_up_down_icon(
            'forumcategory',
            $categoryId,
            $forum_categories_list
        );
        $html .= Display::tag(
            'div',
            $iconsEdit,
            ['class' => 'pull-right']
        );
    }

    $session_img = api_get_session_image($sessionCategoryId, $_user['status']);

    $html .= Display::tag(
        'h3',
        $icoCategory.
        Display::tag(
            'a',
            $forumCategory->getCatTitle(),
            [
                'href' => $linkForumCategory,
                'class' => empty($categoryIsVisible) ? 'text-muted' : null,
            ]
        ).$session_displayed.$session_img,
        null
    );

    if ('' != $descriptionCategory && '&nbsp;' != trim($descriptionCategory)) {
        $html .= '<div class="forum-description">'.$descriptionCategory.'</div>';
    }

    $html .= '</div>';
    echo $html;
    echo '<div class="forum_display">';
    $notifications = [];
    $em = Database::getManager();
    $notifications = $em->getRepository(CForumNotification::class)->findBy(
        ['userId' => api_get_user_id()]
    );

    $forumNotifications = [];
    foreach ($notifications as $notification) {
        if ($notification->getForumId()) {
            $forumNotifications[] = $notification->getForumId();
        }
    }

    // The forums in this category.
    $forum_list = get_forums_in_category($categoryId);
    $forum_count = 0;
    foreach ($forum_list as $forum) {
        if (!empty($forum->getForumCategory())) {
            if ($forum->getForumCategory()->getIid() == $categoryId) {
                $forumId = $forum->getIid();
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
                    //echo 'student';
                    // it is not a group forum => show forum
                    // (invisible forums are already left out see get_forums function)
                    if ('0' == $forum->getForumOfGroup()) {
                        $show_forum = true;
                    } else {
                        // it is a group forum
                        // it is a group forum but it is public => show
                        if ('public' === $forum->getForumGroupPublicPrivate()) {
                            $show_forum = true;
                        } else {
                            // it is a group forum and it is private
                            // it is a group forum and it is private but the user is member of the group
                            if (in_array($forum->getForumOfGroup(), $groups_of_user)) {
                                $show_forum = true;
                            } else {
                                $show_forum = false;
                            }
                        }
                    }
                }

                $form_count = isset($form_count) ? $form_count : 0;
                if (true === $show_forum) {
                    $form_count++;
                    $html = '<div class="panel panel-default forum">';
                    $html .= '<div class="panel-body">';
                    //$my_whatsnew_post_info = isset($whatsnew_post_info[$forum['forum_id']]) ? $whatsnew_post_info[$forum['forum_id']] : null;
                    $forumOfGroup = $forum->getForumOfGroup();

                    $forum_title_group_addition = '';
                    if ('0' == $forumOfGroup) {
                        $forum_image = Display::return_icon(
                            'forum_group.png',
                            get_lang('Group Forum'),
                            null,
                            ICON_SIZE_LARGE
                        );
                    } else {
                        $my_all_groups_forum_name = isset($groups[$forumOfGroup]['name']) ? $groups[$forumOfGroup]['name'] : null;
                        $my_all_groups_forum_id = isset($groups[$forumOfGroup]['id']) ? $groups[$forumOfGroup]['id'] : null;
                        $group_title = api_substr($my_all_groups_forum_name, 0, 30);
                        $forum_title_group_addition = ' (<a href="../group/group_space.php?'.api_get_cidreq(true, false)
                            .'&gid='.$my_all_groups_forum_id.'" class="forum_group_link">'
                            .get_lang('Go to').' '.$group_title.'</a>)';
                        $forum_image = Display::return_icon(
                            'forum.png',
                            get_lang('Forum'),
                            null,
                            ICON_SIZE_LARGE
                        );
                    }

                    $session_displayed = '';
                    // @todo fix session name
                    /*if (!empty($sessionId) && !empty($forum['session_name'])) {
                        $session_displayed = ' ('.$forum['session_name'].')';
                    }*/

                    // the number of topics and posts
                    $my_number_threads = $forum->getThreads() ? $forum->getThreads()->count() : 0;
                    $my_number_posts = $forum->getForumPosts();

                    $html .= '<div class="row">';
                    $html .= '<div class="col-md-6">';
                    $html .= '<div class="col-md-3">';
                    $html .= '<div class="number-post">'.$forum_image.
                        '<p>'.$my_number_threads.' '.get_lang('Forum threads').'</p></div>';
                    $html .= '</div>';

                    $html .= '<div class="col-md-9">';
                    $iconForum = Display::return_icon(
                        'forum_yellow.png',
                        get_lang($forumCategory->getCatTitle()),
                        null,
                        ICON_SIZE_MEDIUM
                    );

                    $forumIsVisible = $forum->isVisible($courseEntity, $sessionEntity);

                    $linkForum = Display::tag(
                        'a',
                        $forum->getForumTitle().$session_displayed,
                        [
                            'href' => 'viewforum.php?'.api_get_cidreq(true, false)."&gid={$forumOfGroup}&forum={$forumId}&search=".Security::remove_XSS(urlencode(isset($_GET['search']) ? $_GET['search'] : '')),
                            'class' => false === $forumIsVisible ? 'text-muted' : null,
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
                        strip_tags($forum->getForumComment()),
                        [
                            'class' => 'description',
                        ]
                    );

                    if ($forum->isModerated() && api_is_allowed_to_edit(false, true)) {
                        $waitingCount = getCountPostsWithStatus(CForumPost::STATUS_WAITING_MODERATION, $forum);
                        if (!empty($waitingCount)) {
                            $html .= Display::label(
                                get_lang('Posts pending moderation').': '.$waitingCount,
                                'warning'
                            );
                        }
                    }

                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<div class="col-md-6">';
                    $iconEmpty = '';
                    // The number of topics and posts.
                    if ('0' !== $forumOfGroup) {
                        $newPost = $iconEmpty;
                    } else {
                        $newPost = $iconEmpty;
                    }

                    $html .= '<div class="row">';
                    $html .= '<div class="col-md-2">';
                    $html .= $newPost.'</div>';

                    $poster_id = 0;
                    $name = '';

                    // the last post in the forum
                    /*if (isset($forum['last_poster_name']) && '' != $forum['last_poster_name']) {
                        $name = $forum['last_poster_name'];
                    } else {
                        if (isset($forum['last_poster_lastname'])) {
                            $name = api_get_person_name($forum['last_poster_firstname'], $forum['last_poster_lastname']);
                            $poster_id = $forum['last_poster_id'];
                        }
                    }*/
                    $html .= '<div class="col-md-6">';
                    if (!empty($forum->getForumLastPost())) {
                        $html .= Display::return_icon('post-item.png', null, null, ICON_SIZE_TINY).' ';
                        $html .= Display::dateToStringAgoAndLongDate($forum->getForumLastPost())
                            .' '.get_lang('By').' '
                            .displayUserLink($forum->getForumLastPost()->getUser());
                    }
                    $html .= '</div>';
                    $html .= '<div class="col-md-4">';

                    if (api_is_allowed_to_edit(false, true) &&
                        !(0 == $forum->getSessionId() && 0 != $sessionId)
                    ) {
                        $html .= '<a href="'.$mainUrl.'&forumcategory='.$categoryId.'&action=edit_forum&id='.$forumId.'">'
                            .Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL).'</a>';
                        $html .= '<a href="'.$url.'&forumcategory='.$categoryId.'&action=delete&content=forum&id='.$forumId
                            ."\" onclick=\"javascript:if(!confirm('"
                            .addslashes(api_htmlentities(get_lang('Delete forum ?'), ENT_QUOTES))
                            ."')) return false;\">"
                            .Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL)
                            .'</a>';
                        $html .= return_visible_invisible_icon(
                            'forum',
                            $forumId,
                            $forumIsVisible,
                            ['forumcategory' => $_GET['forumcategory']]
                        );
                        $html .= return_lock_unlock_icon(
                            'forum',
                            $forumId,
                            $forum->getLocked(),
                            ['forumcategory' => $_GET['forumcategory']]
                        );
                        $html .= return_up_down_icon(
                            'forum',
                            $forumId,
                            $forum_list
                        );
                    }

                    $iconnotify = 'notification_mail_na.png';
                    if (in_array($forum->getIid(), $forumNotifications)) {
                        $iconnotify = 'notification_mail.png';
                    }

                    if (!api_is_anonymous() && false == $hideNotifications) {
                        $html .= '<a href="'.$url.'&forumcategory='.$categoryId.'&action=notify&content=forum&id='.$forumId.'">'.
                            Display::return_icon($iconnotify, get_lang('Notify me')).
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
    if (0 == count($forum_list)) {
        echo '<div class="alert alert-warning">'.get_lang('There are no forums in this category').'</div>';
    }
    echo '</div>';
}

if ('learnpath' !== $origin) {
    Display::display_footer();
}
