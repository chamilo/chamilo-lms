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
 *  @package chamilo.forum
 */

/* INIT SECTION */

// Language files that need to be included.
$language_file = array ('forum', 'group');

// Including the global initialization file.
require_once '../inc/global.inc.php';
require_once '../gradebook/lib/gradebook_functions.inc.php';
require_once '../gradebook/lib/be/gradebookitem.class.php';
require_once '../gradebook/lib/be/evaluation.class.php';
require_once '../gradebook/lib/be/abstractlink.class.php';
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
include 'forumconfig.inc.php';
include 'forumfunction.inc.php';

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

/* Retrieving forum and forum categorie information */

// We are getting all the information about the current forum and forum category.
// Note pcool: I tried to use only one sql statement (and function) for this,
// but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table.
$current_thread = get_thread_information($_GET['thread']); // Note: This has to be validated that it is an existing thread.
$current_forum = get_forum_information($_GET['forum']); // Note: This has to be validated that it is an existing forum.
$current_forum_category = get_forumcategory_information($current_forum['forum_category']);
$current_post = get_post_information($_GET['post']);

/* Header and Breadcrumbs */

if (isset($_SESSION['gradebook'])) {
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
            'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
            'name' => get_lang('ToolGradebook')
        );
}

if ($origin == 'group') {
    $_clean['toolgroup'] = (int)$_SESSION['toolgroup'];
    $group_properties = GroupManager :: get_group_properties($_clean['toolgroup']);
    $interbreadcrumb[] = array('url' => '../group/group.php', 'name' => get_lang('Groups'));
    $interbreadcrumb[] = array('url'=>'../group/group_space.php?gidReq='.$_SESSION['toolgroup'], 'name'=> get_lang('GroupSpace').' '.$group_properties['name']);
    $interbreadcrumb[] = array('url' => 'viewforum.php?origin='.$origin.'&amp;gidReq='.$_SESSION['toolgroup'].'&amp;forum='.Security::remove_XSS($_GET['forum']), 'name' => prepare4display($current_forum['forum_title']));
    $interbreadcrumb[] = array('url' => 'javascript: void (0);', 'name' => get_lang('EditPost'));

} else {
    $interbreadcrumb[] = array('url' => 'index.php?gradebook='.$gradebook, 'name' => $nameTools);
    $interbreadcrumb[] = array('url' => 'viewforumcategory.php?forumcategory='.$current_forum_category['cat_id'], 'name' => prepare4display($current_forum_category['cat_title']));
    $interbreadcrumb[] = array('url' => 'viewforum.php?origin='.$origin.'&amp;forum='.Security::remove_XSS($_GET['forum']), 'name' => prepare4display($current_forum['forum_title']));
    $interbreadcrumb[] = array('url' => 'viewthread.php?gradebook='.$gradebook.'&amp;origin='.$origin.'&amp;forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']), 'name' => prepare4display($current_thread['thread_title']));
    $interbreadcrumb[] = array('url' => 'javascript: void (0);', 'name' => get_lang('EditPost'));
}

/* Resource Linker */

if (isset($_POST['add_resources']) AND $_POST['add_resources'] == get_lang('Resources')) {
    $_SESSION['formelements'] = $_POST;
    $_SESSION['origin'] = $_SERVER['REQUEST_URI'];
    $_SESSION['breadcrumbs'] = $interbreadcrumb;
    header('Location: ../resourcelinker/resourcelinker.php');
}
$table_link = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

/* Header */

// Are we in a lp ?
$origin = '';
if (isset($_GET['origin'])) {
    $origin =  Security::remove_XSS($_GET['origin']);
}

if ($origin == 'learnpath') {
    include api_get_path(INCLUDE_PATH).'reduced_header.inc.php';
} else {
    Display :: display_header(null);
    //api_display_tool_title($nameTools);
}

/* Is the user allowed here? */

// The user is not allowed here if
// 1. the forumcategory, forum or thread is invisible (visibility==0)
// 2. the forumcategory, forum or thread is locked (locked <>0)
// 3. if anonymous posts are not allowed
// 4. if editing of replies is not allowed
// The only exception is the course manager
// I have split this is several pieces for clarity.
//if (!api_is_allowed_to_edit() AND (($current_forum_category['visibility'] == 0 OR $current_forum['visibility'] == 0) OR ($current_forum_category['locked'] <> 0 OR $current_forum['locked'] <> 0 OR $current_thread['locked'] <> 0))) {
if (!api_is_allowed_to_edit(null, true) AND (($current_forum_category['visibility'] == 0 OR $current_forum['visibility'] == 0))) {
    $forum_allow = forum_not_allowed_here();
    if ($forum_allow === false) {
        exit;
    }
}
if (!api_is_allowed_to_edit(null, true) AND ($current_forum_category['locked'] <> 0 OR $current_forum['locked'] <> 0 OR $current_thread['locked'] <> 0)) {
    $forum_allow = forum_not_allowed_here();
    if ($forum_allow === false) {
        exit;
    }
}
if (!$_user['user_id'] AND $current_forum['allow_anonymous'] == 0) {
    $forum_allow = forum_not_allowed_here();
    if ($forum_allow === false) {
        exit;
    }
}
if (!api_is_allowed_to_edit(null, true) AND $current_forum['allow_edit'] == 0) {
    $forum_allow = forum_not_allowed_here();
    if ($forum_allow === false) {
        exit;
    }
}

// Action links
if ($origin != 'learnpath') {
    echo '<div class="actions">';
    echo '<span style="float:right;">'.search_link().'</span>';
    if ($origin == 'group') {
        echo '<a href="../group/group_space.php?'.api_get_cidreq().'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&amp;gradebook='.$gradebook.'">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('Groups'), '', '32').'</a>';
    } else {
        echo '<a href="index.php?gradebook='.$gradebook.'">'.Display::return_icon('back.png', get_lang('BackToForumOverview'), '', '32').'</a>';
    }
    echo '<a href="viewforum.php?forum='.Security::remove_XSS($_GET['forum']).'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&amp;origin='.$origin.'">'.Display::return_icon('forum.png', get_lang('BackToForum'), '', '32').'</a>';
    echo '</div>';
}

/* Display Forum Category and the Forum information */

echo "<table class=\"forum_table\" width=\"100%\">\n";
// The forum category
echo "<tr><th class=\"forum_head\" colspan=\"2\">";
echo '<a href="viewforum.php?&amp;origin='.$origin.'&amp;forum='.$current_forum['forum_id'].'" '.class_visible_invisible($current_forum['visibility']).'>'.prepare4display($current_forum['forum_title']).'</a><br />';
echo '<span class="forum_description">'.prepare4display($current_forum['forum_comment']).'</span>';echo "</th>\n";
echo "</th>\n";
echo "\t</tr>\n";
echo '</table>';

// The form for the reply
$values = show_edit_post_form($current_post, $current_thread, $current_forum, isset($_SESSION['formelements']) ? $_SESSION['formelements'] : '');

if (!empty($values) and isset($_POST['SubmitPost'])) {
    store_edit_post($values);

    $option_chek = isset($values['thread_qualify_gradebook']) ? $values['thread_qualify_gradebook'] : null; // values 1 or 0
    if (1 == $option_chek) {
        $id = $values['thread_id'];
        $title_gradebook = Security::remove_XSS(stripslashes($values['calification_notebook_title']));
        $value_calification = $values['numeric_calification'];
        $weight_calification = $values['weight_calification'];
        $description = '';
        $session_id = api_get_session_id();
        $link_id = is_resource_in_course_gradebook(api_get_course_id(), 5, $id, $session_id);
        if (!$link_id) {
            add_resource_to_course_gradebook(api_get_course_id(), 5, $id, $title_gradebook, $weight_calification, $value_calification, $description, time(), 1, api_get_session_id());
        } else {
            Database::query('UPDATE '.$table_link.' SET weight='.$weight_calification.' WHERE id='.$link_id.'');
        }
    }
}

// Footer
if ($origin != 'learnpath') {
    Display :: display_footer();
}
