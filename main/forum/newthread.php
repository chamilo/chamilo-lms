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
 * @Author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @Copyright Ghent University
 * @Copyright Patrick Cool
 *
 * @package chamilo.forum
 */

// Language file that need to be included.
$language_file = array('forum', 'document');

// Including the global initialization file.
require_once '../inc/global.inc.php';

require_once '../gradebook/lib/gradebook_functions.inc.php';

// The section (tabs).
$this_section = SECTION_COURSES;

// Notification for unauthorized people.
api_protect_course_script(true);

// Including additional library scripts.
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

$nameTools = get_lang('ToolForum');

/* Including necessary files */

require_once 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';

// Are we in a lp ?
$origin = '';
if (isset($_GET['origin'])) {
    $origin =  Security::remove_XSS($_GET['origin']);
}

// javascript
$htmlHeadXtra[] = '<script>
        function advanced_parameters() {
            if(document.getElementById(\'id_qualify\').style.display == \'none\') {
                document.getElementById(\'id_qualify\').style.display = \'block\';
                document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_hide.gif',get_lang('Hide'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
            } else {
                document.getElementById(\'id_qualify\').style.display = \'none\';
                document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
            }
        }
</script>';

/* MAIN DISPLAY SECTION */

/* Retrieving forum and forum category information */

$current_forum = get_forum_information($_GET['forum']); // Note: This has to be validated that it is an existing forum.
$current_forum_category = get_forumcategory_information($current_forum['forum_category']);

/* Breadcrumbs */

if (isset($_SESSION['gradebook'])){
    $gradebook = Security::remove_XSS($_SESSION['gradebook']);
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
            'url' => '../gradebook/'.Security::remove_XSS($_SESSION['gradebook_dest']),
            'name' => get_lang('ToolGradebook')
        );
}

if (!empty($_GET['gidReq'])) {
    $toolgroup = intval($_GET['gidReq']);
    api_session_register('toolgroup');
}


/* Is the user allowed here? */

// The user is not allowed here if:
// 1. the forumcategory or forum is invisible (visibility==0) and the user is not a course manager
// 2. the forumcategory or forum is locked (locked <>0) and the user is not a course manager
// 3. new threads are not allowed and the user is not a course manager
// 4. anonymous posts are not allowed and the user is not logged in
// I have split this is several pieces for clarity.

if (!api_is_allowed_to_edit(false, true) && (($current_forum_category['visibility'] == 0 || $current_forum['visibility'] == 0))) {
    api_not_allowed();
}
// 2. the forumcategory or forum is locked (locked <>0) and the user is not a course manager
if (!api_is_allowed_to_edit(false, true) AND ($current_forum_category['locked'] <> 0 OR $current_forum['locked'] <> 0)) {
    api_not_allowed();
}
// 3. new threads are not allowed and the user is not a course manager
if (!api_is_allowed_to_edit(false, true) AND $current_forum['allow_new_threads'] <> 1) {
    api_not_allowed();
}
// 4. anonymous posts are not allowed and the user is not logged in
if (!$_user['user_id'] AND $current_forum['allow_anonymous'] <> 1) {
    api_not_allowed();
}

if ($current_forum['forum_of_group'] != 0) {
    $show_forum = GroupManager::user_has_access(api_get_user_id(), $current_forum['forum_of_group'], GROUP_TOOL_FORUM);
    if (!$show_forum) {
        api_not_allowed();
    }
}

$session_toolgroup = 0;
if ($origin == 'group') {
    $session_toolgroup = intval($_SESSION['toolgroup']);
    $group_properties = GroupManager :: get_group_properties($session_toolgroup);
    $interbreadcrumb[] = array('url' => '../group/group.php', 'name' => get_lang('Groups'));
    $interbreadcrumb[] = array('url' => '../group/group_space.php?gidReq='.$session_toolgroup, 'name' => get_lang('GroupSpace').' '.$group_properties['name']);
    $interbreadcrumb[] = array('url' => 'viewforum.php?origin='.$origin.'&amp;gidReq='.$session_toolgroup.'&amp;forum='.Security::remove_XSS($_GET['forum']), 'name' => $current_forum['forum_title']);
    $interbreadcrumb[] = array('url' => 'newthread.php?origin='.$origin.'&amp;forum='.Security::remove_XSS($_GET['forum']),'name' => get_lang('NewTopic'));
} else {
    $interbreadcrumb[] = array('url' => 'index.php?gradebook='.$gradebook, 'name' => $nameTools);
    $interbreadcrumb[] = array('url' => 'viewforumcategory.php?forumcategory='.$current_forum_category['cat_id'], 'name' => $current_forum_category['cat_title']);
    $interbreadcrumb[] = array('url' => 'viewforum.php?origin='.$origin.'&amp;forum='.Security::remove_XSS($_GET['forum']), 'name' => $current_forum['forum_title']);
    $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('NewTopic'));
}

/* Resource Linker */

if (isset($_POST['add_resources']) AND $_POST['add_resources'] == get_lang('Resources')) {
    $_SESSION['formelements']	= $_POST;
    $_SESSION['origin']			= $_SERVER['REQUEST_URI'];
    $_SESSION['breadcrumbs']	= $interbreadcrumb;
    header('Location: ../resourcelinker/resourcelinker.php');
}



/* Header */

if ($origin == 'learnpath') {
    require_once api_get_path(INCLUDE_PATH).'reduced_header.inc.php';
} else {
    Display :: display_header(null);
    //api_display_tool_title($nameTools);
}
/* Display forms / Feedback Messages */

handle_forum_and_forumcategories();

// Action links
echo '<div class="actions">';
echo '<span style="float:right;">'.search_link().'</span>';
/*
if ($origin == 'group') {
    echo '<a href="../group/group_space.php?'.api_get_cidreq().'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&amp;gradebook='.$gradebook.'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('Groups'),'','32').'</a>';
} else {
    echo '<a href="index.php?gradebook='.$gradebook.'">'.Display::return_icon('back.png',get_lang('BackToForumOverview'),'','32').'</a>';
}*/
echo '<a href="viewforum.php?origin='.$origin.'&forum='.Security::remove_XSS($_GET['forum']).'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'">'.Display::return_icon('back.png',get_lang('BackToForum'),'','32').'</a>';
echo '</div>';

/* Display Forum Category and the Forum information */
/*
echo "<table class=\"data_table\" width=\"100%\">\n";

if ($origin != 'learnpath') {
    echo "<tr>\n<th align=\"left\"  colspan=\"2\">";

    echo '<span class="forum_title">'.prepare4display($current_forum['forum_title']).'</span>';

    if (!empty($current_forum['forum_comment'])) {
        echo '<br><span class="forum_description">'.prepare4display($current_forum['forum_comment']).'</span>';
    }

    if (!empty($current_forum_category['cat_title'])) {
        echo '<br /><span class="forum_low_description">'.prepare4display($current_forum_category['cat_title'])."</span><br />";
    }
    echo "</th>\n";
    echo "</tr>\n";
}
echo '</table>';
*/
$values = show_add_post_form('newthread', '', isset($_SESSION['formelements']) ? $_SESSION['formelements'] : null);

if (!empty($values) && isset($values['SubmitPost'])) {
    // Add new thread in table forum_thread.
    store_thread($values);
}

/* FOOTER */

if ($origin != 'learnpath') {
    Display :: display_footer();
}