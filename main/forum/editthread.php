<?php
/* For licensing terms, see /license.txt */

/**
 * Edit a Forum Thread
 * @Author José Loguercio <jose.loguercio@beeznest.com>
 *
 * @package chamilo.forum
 */

use ChamiloSession as Session;

require_once '../inc/global.inc.php';

// The section (tabs).
$this_section = SECTION_COURSES;
// Notification for unauthorized people.
api_protect_course_script(true);

$cidreq = api_get_cidreq();
$nameTools = get_lang('ToolForum');

/* Including necessary files */

require_once 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';

// Are we in a lp ?
$origin = '';
if (isset($_GET['origin'])) {
    $origin = Security::remove_XSS($_GET['origin']);
}

/* MAIN DISPLAY SECTION */
$currentForum = get_forum_information($_GET['forum']);
$currentForumCategory = get_forumcategory_information($currentForum['forum_category']);

// the variable $forum_settings is declared in forumconfig.inc.php
$forumSettings = $forum_setting;

/* Breadcrumbs */

if (isset($_SESSION['gradebook'])) {
    $gradebook = Security::remove_XSS($_SESSION['gradebook']);
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
        'url' => '../gradebook/'.Security::remove_XSS($_SESSION['gradebook_dest']),
        'name' => get_lang('ToolGradebook')
    );
}

$threadId = isset($_GET['thread']) ? intval($_GET['thread']) : 0;
$courseInfo = isset($_GET['cidReq']) ? api_get_course_info($_GET['cidReq']) : 0;
$cId = isset($courseInfo['real_id']) ? intval($courseInfo['real_id']) : 0;

/* Is the user allowed here? */

// The user is not allowed here if:

// 1. the forumcategory or forum is invisible (visibility==0) and the user is not a course manager
if (!api_is_allowed_to_edit(false, true) &&
    (($currentForumCategory['visibility'] && $currentForumCategory['visibility'] == 0) || $currentForum['visibility'] == 0)
) {
    api_not_allowed();
}

// 2. the forumcategory or forum is locked (locked <>0) and the user is not a course manager
if (!api_is_allowed_to_edit(false, true) &&
    (($currentForumCategory['visibility'] && $currentForumCategory['locked'] <> 0) OR $currentForum['locked'] <> 0)
) {
    api_not_allowed();
}

// 3. new threads are not allowed and the user is not a course manager
if (!api_is_allowed_to_edit(false, true) &&
    $currentForum['allow_new_threads'] <> 1
) {
    api_not_allowed();
}
// 4. anonymous posts are not allowed and the user is not logged in
if (!$_user['user_id'] && $currentForum['allow_anonymous'] <> 1) {
    api_not_allowed();
}

// 5. Check user access
if ($currentForum['forum_of_group'] != 0) {
    $show_forum = GroupManager::user_has_access(
        api_get_user_id(),
        $currentForum['forum_of_group'],
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
    $groupProperties = GroupManager :: get_group_properties($groupId);
    $interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.$cidreq, 'name' => get_lang('Groups'));
    $interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.$cidreq, 'name' => get_lang('GroupSpace').' '.$groupProperties['name']);
    $interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.$cidreq.'&forum='.intval($_GET['forum']), 'name' => $currentForum['forum_title']);
    $interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'forum/newthread.php?'.$cidreq.'&forum='.intval($_GET['forum']),'name' => get_lang('EditThread'));
} else {
    $interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'forum/index.php?'.$cidreq, 'name' => $nameTools);
    $interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'forum/viewforumcategory.php?'.$cidreq.'&forumcategory='.$currentForumCategory['cat_id'], 'name' => $currentForumCategory['cat_title']);
    $interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.$cidreq.'&forum='.intval($_GET['forum']), 'name' => $currentForum['forum_title']);
    $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('EditThread'));
}

$tableLink = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

/* Header */

$htmlHeadXtra[] = <<<JS
    <script>
    $(document).on('ready', function() {

        if ($('#thread_qualify_gradebook').is(':checked') == true) {
            document.getElementById('options_field').style.display = 'block';
        } else {
            document.getElementById('options_field').style.display = 'none';
        }

        $('#thread_qualify_gradebook').click(function() {
            if ($('#thread_qualify_gradebook').is(':checked') == true) {
                document.getElementById('options_field').style.display = 'block';
            } else {
                document.getElementById('options_field').style.display = 'none';
                $("[name='numeric_calification']").val(0);
                $("[name='calification_notebook_title']").val('');
                $("[name='weight_calification']").val(0);
                $("[name='thread_peer_qualify'][value='0']").prop('checked', true);
            }
        });
    });
    </script>
JS;

if ($origin == 'learnpath') {
    Display::display_reduced_header();
} else {
    Display :: display_header(null);
}

handle_forum_and_forumcategories();

// Action links
echo '<div class="actions">';
echo '<span style="float:right;">'.search_link().'</span>';
echo '<a href="viewforum.php?forum='.intval($_GET['forum']).'&'.$cidreq.'">'.
    Display::return_icon('back.png',get_lang('BackToForum'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

$threadData = getThreadInfo($threadId, $cId);

$values = showUpdateThreadForm(
    $currentForum,
    $forumSettings,
    $threadData
);

if (!empty($values) && isset($values['SubmitPost'])) {

    // update thread in table forum_thread.
    updateThread($values);
}

if (isset($origin) && $origin != 'learnpath') {
    Display :: display_footer();
}
