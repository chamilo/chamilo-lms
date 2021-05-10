<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CForumForum;
use Chamilo\CourseBundle\Entity\CForumThread;

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
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$nameTools = get_lang('Forum Categories');
$origin = api_get_origin();
$_user = api_get_user_info();

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

$forumId = isset($_GET['forum']) ? (int) $_GET['forum'] : 0;
$threadId = isset($_GET['thread']) ? (int) $_GET['thread'] : 0;

$repo = Container::getForumRepository();
/** @var CForumForum $forum */
$forum = $repo->find($forumId);

if (empty($forum)) {
    api_not_allowed();
}

$repoThread = Container::getForumThreadRepository();
$threadEntity = null;
if (!empty($threadId)) {
    /** @var CForumThread $threadEntity */
    $threadEntity = $repoThread->find($threadId);
}

$courseEntity = api_get_course_entity(api_get_course_int_id());
$sessionEntity = api_get_session_entity(api_get_session_id());
$current_forum_category = $forum->getForumCategory();

/* Is the user allowed here? */
// The user is not allowed here if
// 1. the forumcategory, forum or thread is invisible (visibility==0
// 2. the forumcategory, forum or thread is locked (locked <>0)
// 3. if anonymous posts are not allowed
// The only exception is the course manager
// I have split this is several pieces for clarity.
if (!api_is_allowed_to_edit(false, true) &&
    (($current_forum_category && !$current_forum_category->isVisible($courseEntity, $sessionEntity)) ||
        !$forum->isVisible($courseEntity, $sessionEntity))
) {
    api_not_allowed(true);
}
if (!api_is_allowed_to_edit(false, true) &&
    (($current_forum_category && 0 != $current_forum_category->getLocked()) ||
        0 != $forum->getLocked() || 0 != $threadEntity->getLocked())
) {
    api_not_allowed(true);
}
if (!$_user['user_id'] &&
    0 == $forum->getAllowAnonymous()) {
    api_not_allowed(true);
}

if (0 != $forum->getForumOfGroup()) {
    $show_forum = GroupManager::userHasAccess(
        api_get_user_id(),
        api_get_group_entity($forum->getForumOfGroup()),
        GroupManager::GROUP_TOOL_FORUM
    );
    if (!$show_forum) {
        api_not_allowed();
    }
}

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}
$groupId = api_get_group_id();
if (!empty($groupId)) {
    $group_properties = GroupManager::get_group_properties($groupId);
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups'),
    ];

    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('Group area').' '.$group_properties['name'],
    ];

    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?forum='.$forumId.'&'.api_get_cidreq(),
        'name' => $forum->getForumTitle(),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewthread.php?forum='.$forumId.'&thread='.$threadId.'&'.api_get_cidreq(),
        'name' => $threadEntity->getThreadTitle(),
    ];

    $interbreadcrumb[] = [
        'url' => 'javascript: void(0);',
        'name' => get_lang('Reply'),
    ];
} else {
    $interbreadcrumb[] = [
        'url' => 'index.php?'.api_get_cidreq(),
        'name' => $nameTools,
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/index.php?forumcategory='.$current_forum_category->getIid().'&'.api_get_cidreq(),
        'name' => $current_forum_category->getCatTitle(),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?forum='.$forumId.'&'.api_get_cidreq(),
        'name' => $forum->getForumTitle(),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewthread.php?forum='.$forumId.'&thread='.$threadId.'&'.api_get_cidreq(),
        'name' => $threadEntity->getThreadTitle(),
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Reply')];
}

/* Header */
$htmlHeadXtra[] = <<<JS
    <script>
    $(function() {
        $('#reply-add-attachment').on('click', function(e) {
            e.preventDefault();

            var newInputFile = $('<input>', {
                type: 'file',
                name: 'user_upload[]'
            });

            $('[name="user_upload[]"]').parent().append(newInputFile);
        });
    });
    </script>
JS;

/* End new display forum */
// The form for the reply
$my_action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';
$my_post = isset($_GET['post']) ? Security::remove_XSS($_GET['post']) : '';
$my_elements = isset($_SESSION['formelements']) ? $_SESSION['formelements'] : '';

$logInfo = [
    'tool' => TOOL_FORUM,
    'tool_id' => $forumId,
    'tool_id_detail' => $threadId,
    'action' => !empty($my_action) ? $my_action : 'reply',
];
Event::registerLog($logInfo);

$postRepo = Container::getForumPostRepository();
$post = $postRepo->find($my_post);

$form = show_add_post_form(
    $forum,
    $threadEntity,
    $post,
    $my_action,
    $my_elements
);

if ('learnpath' === $origin) {
    Display::display_reduced_header();
} else {
    // The last element of the breadcrumb navigation is already set in interbreadcrumb, so give an empty string.
    Display::display_header();
}

if ('learnpath' !== $origin) {
    //$actionsLeft = '<span style="float:right;">'.search_link().'</span>';
    $actionsLeft = '<a href="viewthread.php?'.api_get_cidreq().'&forum='.$forumId.'&thread='.$threadId.'">';
    $actionsLeft .= Display::return_icon(
        'back.png',
        get_lang('Back to thread'),
        '',
        ICON_SIZE_MEDIUM
    ).'</a>';

    echo Display::toolbarAction('toolbar', [$actionsLeft]);
}
/*New display forum div*/
echo '<div class="forum_title">';
echo '<h1>';
echo Display::url(
    prepare4display($forum->getForumTitle()),
    'viewforum.php?'.api_get_cidreq().'&'.http_build_query(['forum' => $forumId]),
    ['class' => empty($forum->isVisible($courseEntity, $sessionEntity)) ? 'text-muted' : null]
);
echo '</h1>';
echo '<p class="forum_description">'.prepare4display($forum->getForumComment()).'</p>';
echo '</div>';
if ($form) {
    $form->display();
}

if ('learnpath' === $origin) {
    Display::display_reduced_footer();
} else {
    Display::display_footer();
}
