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
 * - quoting a message.
 *
 * @Author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @Copyright Ghent University
 * @Copyright Patrick Cool
 *
 * @package chamilo.forum
 */
require_once __DIR__.'/../inc/global.inc.php';

// The section (tabs).
$this_section = SECTION_COURSES;

// Notification for unauthorized people.
api_protect_course_script(true);

$cidreq = api_get_cidreq();
$_user = api_get_user_info();

$nameTools = get_lang('ToolForum');

require_once 'forumfunction.inc.php';

// Are we in a lp ?
$origin = api_get_origin();
/* MAIN DISPLAY SECTION */
$current_forum = get_forum_information($_GET['forum']);
$current_forum_category = get_forumcategory_information($current_forum['forum_category']);

$logInfo = [
    'tool' => TOOL_FORUM,
    'tool_id' => (int) $_GET['forum'],
    'tool_id_detail' => 0,
    'action' => 'add-thread',
    'action_details' => '',
];
Event::registerLog($logInfo);

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
}

/* Is the user allowed here? */

// The user is not allowed here if:

// 1. the forumcategory or forum is invisible (visibility==0) and the user is not a course manager
if (!api_is_allowed_to_edit(false, true) &&
    (($current_forum_category && $current_forum_category['visibility'] == 0) || $current_forum['visibility'] == 0)
) {
    api_not_allowed();
}

// 2. the forumcategory or forum is locked (locked <>0) and the user is not a course manager
if (!api_is_allowed_to_edit(false, true) &&
    (($current_forum_category['visibility'] && $current_forum_category['locked'] != 0) || $current_forum['locked'] != 0)
) {
    api_not_allowed();
}

// 3. new threads are not allowed and the user is not a course manager
if (!api_is_allowed_to_edit(false, true) &&
    $current_forum['allow_new_threads'] != 1
) {
    api_not_allowed();
}
// 4. anonymous posts are not allowed and the user is not logged in
if (!$_user['user_id'] && $current_forum['allow_anonymous'] != 1) {
    api_not_allowed();
}

// 5. Check user access
if ($current_forum['forum_of_group'] != 0) {
    $show_forum = GroupManager::user_has_access(
        api_get_user_id(),
        $current_forum['forum_of_group'],
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
        'name' => get_lang('GroupSpace').' '.$groupProperties['name'],
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.$cidreq.'&forum='.intval($_GET['forum']),
        'name' => $current_forum['forum_title'],
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/newthread.php?'.$cidreq.'&forum='.intval($_GET['forum']),
        'name' => get_lang('NewTopic'),
    ];
} else {
    $interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'forum/index.php?'.$cidreq, 'name' => $nameTools];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforumcategory.php?'.$cidreq.'&forumcategory='.$current_forum_category['cat_id'],
        'name' => $current_forum_category['cat_title'],
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.$cidreq.'&forum='.intval($_GET['forum']),
        'name' => $current_forum['forum_title'],
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('NewTopic')];
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

$form = show_add_post_form(
    $current_forum,
    'newthread',
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
    Display::return_icon('back.png', get_lang('BackToForum'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

// Set forum attachment data into $_SESSION
getAttachedFiles($current_forum['forum_id'], 0, 0);

if ($form) {
    $form->display();
}

if ($origin == 'learnpath') {
    Display::display_reduced_footer();
} else {
    Display::display_footer();
}
