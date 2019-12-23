<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CForumForum;

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

// The section (tabs).
$this_section = SECTION_COURSES;

// Notification for unauthorized people.
api_protect_course_script(true);

$cidreq = api_get_cidreq();
$_user = api_get_user_info();

$nameTools = get_lang('Forums');

require_once 'forumfunction.inc.php';

// Are we in a lp ?
$origin = api_get_origin();

$forumId = isset($_GET['forum']) ? (int) $_GET['forum'] : 0;
$repo = Container::getForumRepository();

$forumEntity = null;
if (!empty($forumId)) {
    /** @var CForumForum $forumEntity */
    $forumEntity = $repo->find($forumId);
}

$courseEntity = api_get_course_entity(api_get_course_int_id());
$sessionEntity = api_get_session_entity(api_get_session_id());
$current_forum = get_forum_information($_GET['forum']);
$current_forum_category = $forumEntity->getForumCategory();

$logInfo = [
    'tool' => TOOL_FORUM,
    'tool_id' => $forumId,
    'tool_id_detail' => 0,
    'action' => 'add-thread',
    'action_details' => '',
];
Event::registerLog($logInfo);

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

/* Is the user allowed here? */
// The user is not allowed here if:
// 1. the forumcategory or forum is invisible (visibility==0) and the user is not a course manager
if (!api_is_allowed_to_edit(false, true) && //is a student
    (
        ($current_forum_category && false == $current_forum_category->isVisible($courseEntity, $sessionEntity)) ||
        false == $current_forum_category->isVisible($courseEntity, $sessionEntity)
    )
) {
    api_not_allowed(true);
}

// 2. the forumcategory or forum is locked (locked <>0) and the user is not a course manager
if (!api_is_allowed_to_edit(false, true) &&
    (($current_forum_category->isVisible($courseEntity, $sessionEntity) &&
        $current_forum_category->getLocked() != 0) || $forumEntity->getLocked() != 0)
) {
    api_not_allowed();
}

// 3. new threads are not allowed and the user is not a course manager
if (!api_is_allowed_to_edit(false, true) &&
    $forumEntity->getAllowNewThreads() != 1
) {
    api_not_allowed();
}
// 4. anonymous posts are not allowed and the user is not logged in
if (!$_user['user_id'] && $forumEntity->getAllowAnonymous() != 1) {
    api_not_allowed();
}

// 5. Check user access
if ($forumEntity->getForumOfGroup() != 0) {
    $show_forum = GroupManager::user_has_access(
        api_get_user_id(),
        $forumEntity->getForumOfGroup(),
        GroupManager::GROUP_TOOL_FORUM
    );
    if (!$show_forum) {
        api_not_allowed();
    }
}

// 6. Invited users can't create new threads
if (api_is_invitee()) {
    api_not_allowed(true);
}

$groupId = api_get_group_id();
if (!empty($groupId)) {
    $groupProperties = GroupManager::get_group_properties($groupId);
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.$cidreq,
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.$cidreq,
        'name' => get_lang('Group area').' '.$groupProperties['name'],
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.$cidreq.'&forum='.intval($_GET['forum']),
        'name' => $current_forum['forum_title'],
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/newthread.php?'.$cidreq.'&forum='.intval($_GET['forum']),
        'name' => get_lang('Create thread'),
    ];
} else {
    $interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'forum/index.php?'.$cidreq, 'name' => $nameTools];
    if ($current_forum_category) {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'forum/viewforumcategory.php?'.$cidreq.'&forumcategory='.$current_forum_category->getIid(),
            'name' => $current_forum_category->getCatTitle(),
        ];
    }
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.$cidreq.'&forum='.$forumId,
        'name' => $forumEntity->getForumTitle(),
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Create thread')];
}

$htmlHeadXtra[] = "
    <script>
        $(function() {
            $('#reply-add-attachment').on('click', function(e) {
                e.preventDefault();
                var newInputFile = $('<input>', {
                    type: 'file',
                    name: 'user_upload[]'
                });
                $('[name=\"user_upload[]\"]').parent().append(newInputFile);
            });
        });
    </script>
";

$form = newThread(
    $forumEntity,
    isset($_SESSION['formelements']) ? $_SESSION['formelements'] : null
);

if ($origin == 'learnpath') {
    Display::display_reduced_header();
} else {
    Display::display_header();
}
handle_forum_and_forumcategories();

// Action links
echo '<div class="actions">';
echo '<span style="float:right;">'.search_link().'</span>';
echo '<a href="viewforum.php?forum='.intval($_GET['forum']).'&'.$cidreq.'">'.
    Display::return_icon('back.png', get_lang('Back to forum'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

// Set forum attachment data into $_SESSION
getAttachedFiles($forumEntity->getIid(), 0, 0);

if ($form) {
    $form->display();
}

if ($origin == 'learnpath') {
    Display::display_reduced_footer();
} else {
    Display::display_footer();
}
