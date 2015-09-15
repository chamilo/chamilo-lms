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
// Including the global initialization file.
require_once '../inc/global.inc.php';

// The section (tabs).
$this_section = SECTION_COURSES;

// Notification for unauthorized people.
api_protect_course_script(true);

$nameTools = get_lang('ToolForum');

/* Including necessary files */
require_once 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';

$origin = '';
if (isset($_GET['origin'])) {
    $origin = Security::remove_XSS($_GET['origin']);
}

/* MAIN DISPLAY SECTION */

/* Retrieving forum and forum category information */

// We are getting all the information about the current forum and forum category.
// Note pcool: I tried to use only one sql statement (and function) for this,
// but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table.
$current_thread = get_thread_information($_GET['thread']);
$current_forum = get_forum_information($_GET['forum']);
$current_forum_category = get_forumcategory_information($current_forum['forum_category']);
$current_post = get_post_information($_GET['post']);

api_block_course_item_locked_by_gradebook($_GET['thread'], LINK_FORUM_THREAD);

/* Header and Breadcrumbs */

if (isset($_SESSION['gradebook'])) {
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array(
        'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
        'name' => get_lang('ToolGradebook')
    );
}

if ($origin == 'group') {
    $_clean['toolgroup'] = (int) $_SESSION['toolgroup'];
    $group_properties = GroupManager::get_group_properties($_clean['toolgroup']);
    $interbreadcrumb[] = array('url' => '../group/group.php?'.api_get_cidreq(), 'name' => get_lang('Groups'));
    $interbreadcrumb[] = array('url' => '../group/group_space.php?gidReq='.$_SESSION['toolgroup'], 'name' => get_lang('GroupSpace').' '.$group_properties['name']);
    $interbreadcrumb[] = array('url' => 'viewforum.php?origin='.$origin.'&gidReq='.$_SESSION['toolgroup'].'&forum='.Security::remove_XSS($_GET['forum']), 'name' => prepare4display($current_forum['forum_title']));
    $interbreadcrumb[] = array('url' => 'javascript: void (0);', 'name' => get_lang('EditPost'));
} else {
    $interbreadcrumb[] = array('url' => 'index.php?'.api_get_cidreq(), 'name' => $nameTools);
    $interbreadcrumb[] = array('url' => 'viewforumcategory.php?forumcategory='.$current_forum_category['cat_id'], 'name' => prepare4display($current_forum_category['cat_title']));
    $interbreadcrumb[] = array('url' => 'viewforum.php?origin='.$origin.'&forum='.Security::remove_XSS($_GET['forum']), 'name' => prepare4display($current_forum['forum_title']));
    $interbreadcrumb[] = array('url' => 'viewthread.php?'.api_get_cidreq().'&origin='.$origin.'&forum='.Security::remove_XSS($_GET['forum']).'&thread='.Security::remove_XSS($_GET['thread']), 'name' => prepare4display($current_thread['thread_title']));
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
$htmlHeadXtra[] = <<<JS
    <script>
    $(document).on('ready', function() {
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

// Are we in a lp ?
$origin = '';
if (isset($_GET['origin'])) {
    $origin = Security::remove_XSS($_GET['origin']);
}

if ($origin == 'learnpath') {
    Display::display_reduced_header();
} else {
    Display :: display_header(null);
}
/* Is the user allowed here? */

// The user is not allowed here if
// 1. the forum category, forum or thread is invisible (visibility==0)
// 2. the forum category, forum or thread is locked (locked <>0)
// 3. if anonymous posts are not allowed
// 4. if editing of replies is not allowed
// The only exception is the course manager
// I have split this is several pieces for clarity.
//if (!api_is_allowed_to_edit() AND (($current_forum_category['visibility'] == 0 OR $current_forum['visibility'] == 0) OR ($current_forum_category['locked'] <> 0 OR $current_forum['locked'] <> 0 OR $current_thread['locked'] <> 0))) {
if (!api_is_allowed_to_edit(null, true) &&
    (($current_forum_category && $current_forum_category['visibility'] == 0) ||
        $current_forum['visibility'] == 0)
) {
    $forum_allow = forum_not_allowed_here();
    if ($forum_allow === false) {
        exit;
    }
}

if (!api_is_allowed_to_edit(null, true) &&
    (
        ($current_forum_category && $current_forum_category['locked'] <> 0 ) ||
        $current_forum['locked'] <> 0 ||
        $current_thread['locked'] <> 0
    )
) {
    $forum_allow = forum_not_allowed_here();
    if ($forum_allow === false) {
        exit;
    }
}

if (!$_user['user_id'] && $current_forum['allow_anonymous'] == 0) {
    $forum_allow = forum_not_allowed_here();
    if ($forum_allow === false) {
        exit;
    }
}
$group_id = api_get_group_id();

if (!api_is_allowed_to_edit(null, true) &&
    $current_forum['allow_edit'] == 0 &&
    !GroupManager::is_tutor_of_group(api_get_user_id(), $group_id)
) {
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
        echo '<a href="../group/group_space.php?'.api_get_cidreq().'">'.
            Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('Groups'), '', ICON_SIZE_MEDIUM).'</a>';
    } else {
        echo '<a href="index.php?'.api_get_cidreq().'">'.
            Display::return_icon('back.png', get_lang('BackToForumOverview'), '', ICON_SIZE_MEDIUM).'</a>';
    }
    echo '<a href="viewforum.php?forum='.Security::remove_XSS($_GET['forum']).'&gidReq='.Security::remove_XSS($_GET['gidReq']).'&origin='.$origin.'">'.
        Display::return_icon('forum.png', get_lang('BackToForum'), '', ICON_SIZE_MEDIUM).'</a>';
    echo '</div>';
}

/* Display Forum Category and the Forum information */

/*New display forum div*/
echo '<div class="forum_title">';
echo '<h1><a href="viewforum.php?&origin='.$origin.'&forum='.$current_forum['forum_id'].'" '.class_visible_invisible($current_forum['visibility']).'>'.prepare4display($current_forum['forum_title']).'</a></h1>';
echo '<p class="forum_description">'.prepare4display($current_forum['forum_comment']).'</p>';
echo '</div>';
/* End new display forum */

// Set forum attachment data into $_SESSION
getAttachedFiles(
    $current_forum['forum_id'],
    $current_thread['thread_id'],
    $current_post['post_id']
);

$values = show_edit_post_form(
    $forum_setting,
    $current_post,
    $current_thread,
    $current_forum,
    isset($_SESSION['formelements']) ? $_SESSION['formelements'] : ''
);

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

        $link_info = GradebookUtils::is_resource_in_course_gradebook(
            api_get_course_id(),
            5,
            $id,
            $session_id
        );
        $link_id = $link_info['id'];

        if (!$link_info) {
            GradebookUtils::add_resource_to_course_gradebook(
                $values['category_id'],
                api_get_course_id(),
                5,
                $id,
                $title_gradebook,
                $weight_calification,
                $value_calification,
                $description,
                1,
                api_get_session_id()
            );
        } else {
            Database::query('UPDATE '.$table_link.' SET weight='.$weight_calification.' WHERE id='.$link_id.'');
        }
    }
}

// Footer
if (isset($origin) && $origin != 'learnpath') {
    Display :: display_footer();
}
