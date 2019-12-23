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

$this_section = SECTION_COURSES;

api_protect_course_script(true);

$nameTools = get_lang('Forum Categories');
$origin = api_get_origin();
$_user = api_get_user_info();

require_once 'forumfunction.inc.php';

$forumId = isset($_GET['forum']) ? (int) $_GET['forum'] : 0;
$threadId = isset($_GET['thread']) ? (int) $_GET['thread'] : 0;

$repo = Container::getForumRepository();
$forumEntity = null;
if (!empty($forumId)) {
    /** @var CForumForum $forumEntity */
    $forumEntity = $repo->find($forumId);
}

$repoThread = Container::getForumThreadRepository();
$threadEntity = null;
if (!empty($threadId)) {
    /** @var CForumThread $threadEntity */
    $threadEntity = $repoThread->find($threadId);
}

$courseEntity = api_get_course_entity(api_get_course_int_id());
$sessionEntity = api_get_session_entity(api_get_session_id());

/* Retrieving forum and forum categorie information */
// We are getting all the information about the current forum and forum category.
// Note pcool: I tried to use only one sql statement (and function) for this,
// but the problem is that the visibility of the forum AND forum category are stored in the item_property table.
// Note: This has to be validated that it is an existing thread.
$current_thread = get_thread_information($forumId, $threadId);
// Note: This has to be validated that it is an existing forum.
$current_forum = get_forum_information($current_thread['forum_id']);
$current_forum_category = $forumEntity->getForumCategory();

/* Is the user allowed here? */
// The user is not allowed here if
// 1. the forumcategory, forum or thread is invisible (visibility==0
// 2. the forumcategory, forum or thread is locked (locked <>0)
// 3. if anonymous posts are not allowed
// The only exception is the course manager
// I have split this is several pieces for clarity.
if (!api_is_allowed_to_edit(false, true) &&
    (($current_forum_category && !$current_forum_category->isVisible($courseEntity, $sessionEntity)) ||
        !$forumEntity->isVisible($courseEntity, $sessionEntity))
) {
    api_not_allowed(true);
}
if (!api_is_allowed_to_edit(false, true) &&
    (($current_forum_category && 0 != $current_forum_category->getLocked()) ||
        0 != $forumEntity->getLocked() || 0 != $threadEntity->getLocked())
) {
    api_not_allowed(true);
}
if (!$_user['user_id'] && 0 == $forumEntity->getAllowAnonymous()) {
    api_not_allowed(true);
}

if (0 != $forumEntity->getForumOfGroup()) {
    $show_forum = GroupManager::user_has_access(
        api_get_user_id(),
        $forumEntity->getForumOfGroup(),
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
        'name' => $forumEntity->getForumTitle(),
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
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforumcategory.php?forumcategory='.$current_forum_category->getIid().'&'.api_get_cidreq(),
        'name' => $current_forum_category->getCatTitle(),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?forum='.$forumId.'&'.api_get_cidreq(),
        'name' => $forumEntity->getForumTitle(),
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

$form = show_add_post_form(
    $forumEntity,
    $threadEntity,
    $my_action,
    $my_elements
);

if ('learnpath' == $origin) {
    Display::display_reduced_header();
} else {
    // The last element of the breadcrumb navigation is already set in interbreadcrumb, so give an empty string.
    Display::display_header();
}

if ('learnpath' != $origin) {
    echo '<div class="actions">';
    echo '<span style="float:right;">'.search_link().'</span>';
    echo '<a href="viewthread.php?'.api_get_cidreq().'&forum='.$forumId.'&thread='.$threadId.'">';
    echo Display::return_icon(
        'back.png',
        get_lang('Back to thread'),
        '',
        ICON_SIZE_MEDIUM
    ).'</a>';
    echo '</div>';
}
/*New display forum div*/
echo '<div class="forum_title">';
echo '<h1>';
echo Display::url(
    prepare4display($forumEntity->getForumTitle()),
    'viewforum.php?'.api_get_cidreq().'&'.http_build_query(['forum' => $forumId]),
    ['class' => empty($forumEntity->isVisible($courseEntity, $sessionEntity)) ? 'text-muted' : null]
);
echo '</h1>';
echo '<p class="forum_description">'.prepare4display($forumEntity->getForumComment()).'</p>';
echo '</div>';
if ($form) {
    $form->display();
}

if ('learnpath' == $origin) {
    Display::display_reduced_footer();
} else {
    Display::display_footer();
}
