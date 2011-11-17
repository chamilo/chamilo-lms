<?php
/* For licensing terms, see /license.txt */

/**
 * These files are a complete rework of the forum. The database structure is
 * based on phpBB but all the code is rewritten. A lot of new functionalities
 * are added:
 * - forum categories and forums can be sorted up or down, locked or made invisible
 * - consistent and integrated forum administration
 * - forum options:     are students allowed to edit their post?
 *                         moderation of posts (approval)
 *                         reply only forums (students cannot create new threads)
 *                         multiple forums per group
 * - sticky messages
 * - new view option: nested view
 * - quoting a message
 *
 * @package chamilo.forum
 *
 * @todo several functions have to be moved to the itemmanager library
 * @todo displaying icons => display library
 * @todo complete the missing phpdoc the correct order should be
 */
/**
 * code
 */
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';

get_notifications_of_user();

/* Javascript */

$htmlHeadXtra[] = '<script type="text/javascript">
function setFocus(){
    $("#forum_title").focus();
    $("#category_title").focus();
    $("#search_title").focus();
}
$(document).ready(function () {
    setFocus();
});
</script>';

/**
 * This function handles all the forum and forumcategories actions. This is a wrapper for the
 * forum and forum categories. All this code code could go into the section where this function is
 * called but this make the code there cleaner.
 * @param   int Learning path ID
 * @return void
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Juan Carlos Raña Trabado (return to lp_id)
 * @version may 2011, Chamilo 1.8.8
 */
function handle_forum_and_forumcategories($lp_id = null) {
    $action_forum_cat   = isset($_GET['action']) ? $_GET['action'] : '';
    $post_submit_cat    = isset($_POST['SubmitForumCategory']) ?  true : false;
    $post_submit_forum  = isset($_POST['SubmitForum']) ? true : false;
    $get_id = isset($_GET['id']) ? $_GET['id'] : '';
    // Adding a forum category
    if (($action_forum_cat == 'add' && $_GET['content'] == 'forumcategory') || $post_submit_cat) {
        show_add_forumcategory_form(array(), $lp_id);//$lp_id when is called from learning path
    }
    // Adding a forum
    if ((($action_forum_cat == 'add' || $action_forum_cat == 'edit') && $_GET['content'] == 'forum') || $post_submit_forum) {
        if ($action_forum_cat == 'edit' && $get_id || $post_submit_forum) {
            $inputvalues = get_forums(intval($get_id)); // Note: This has to be cleaned first.
        } else {
            $inputvalues = array();
        }
        show_add_forum_form($inputvalues,$lp_id);
    }
    // Edit a forum category
    if (($action_forum_cat == 'edit' && $_GET['content'] == 'forumcategory' && isset($_GET['id'])) || (isset($_POST['SubmitEditForumCategory'])) ? true : false ) {
        $forum_category = get_forum_categories(strval(intval($_GET['id']))); // Note: This has to be cleaned first.
        show_edit_forumcategory_form($forum_category);
    }
    // Delete a forum category
    if ((isset($_GET['action']) && $_GET['action'] == 'delete') && isset($_GET['content']) && $get_id) {
        $id_forum = intval($get_id);
        $list_threads = get_threads($id_forum);

        for ($i = 0; $i < count($list_threads); $i++) {
            $messaje = delete_forum_forumcategory_thread('thread', $list_threads[$i]['thread_id']);         
            require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
            $link_id = is_resource_in_course_gradebook(api_get_course_id(), 5 , intval($list_threads[$i]['thread_id']), api_get_session_id());
            if ($link_id !== false) {
                remove_resource_from_course_gradebook($link_id);
            }
        }
        $return_message = delete_forum_forumcategory_thread($_GET['content'], $_GET['id']);
        Display::display_confirmation_message($return_message, false);
    }
    // Change visibility of a forum or a forum category.
    if (($action_forum_cat == 'invisible' || $action_forum_cat == 'visible') && isset($_GET['content']) && isset($_GET['id'])) {
        $return_message = change_visibility($_GET['content'], $_GET['id'], $_GET['action']); // Note: This has to be cleaned first.
        Display::display_confirmation_message($return_message, false);
    }
    // Change lock status of a forum or a forum category.
    if (($action_forum_cat == 'lock' || $action_forum_cat == 'unlock') && isset($_GET['content']) && isset($_GET['id'])) {
        $return_message = change_lock_status($_GET['content'], $_GET['id'], $_GET['action']); // Note: This has to be cleaned first.
        Display::display_confirmation_message($return_message, false);
    }
    // Move a forum or a forum category.
    if ($action_forum_cat == 'move' && isset($_GET['content']) && isset($_GET['id']) && isset($_GET['direction'])) {
        $return_message = move_up_down($_GET['content'], $_GET['direction'], $_GET['id']); // Note: This has to be cleaned first.
        Display::display_confirmation_message($return_message, false);
    }
}

/**
 * This function displays the form that is used to add a forum category.
 *
 * @param array input values (deprecated, set to null when calling)
 * @param   int Learning path ID
 * @return void HTML
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Juan Carlos Raña Trabado (return to lp_id)
 * @version may 2011, Chamilo 1.8.8
 */
function show_add_forumcategory_form($inputvalues = array(),$lp_id) {
    $gradebook = Security::remove_XSS($_GET['gradebook']);

    // Initialize the object.
    $form = new FormValidator('forumcategory', 'post', 'index.php?gradebook='.$gradebook.'');
   	// hidden field if from learning path
   	
	$form->addElement('hidden', 'lp_id', $lp_id);
    // Settting the form elements.
    $form->addElement('header', '', get_lang('AddForumCategory'));
    $form->addElement('text', 'forum_category_title', get_lang('Title'), 'class="input_titles" id="category_title"');

    //$form->applyFilter('forum_category_title', 'html_filter');
    $form->addElement('html_editor', 'forum_category_comment', get_lang('Description'), null, array('ToolbarSet' => 'Forum', 'Width' => '98%', 'Height' => '200'));

    //$form->applyFilter('forum_category_comment', 'html_filter');
    $form->addElement('style_submit_button', 'SubmitForumCategory', get_lang('CreateCategory'), 'class="add"');

    // Setting the rules.
    $form->addRule('forum_category_title', get_lang('ThisFieldIsRequired'), 'required');

    // The validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();
            
            store_forumcategory($values);
        }
        Security::clear_token();
    } else {
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(array('sec_token' => $token));
        $form->display();
    }
}

/**
 * This function displays the form that is used to add a forum category.
 *
 * @param array
 * @return void HTML
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * Juan Carlos Raña Trabado (return to lp_id)
 *
 * @version may 2011, Chamilo 1.8.8
 */
function show_add_forum_form($inputvalues = array(), $lp_id) {
    global $_course;

    $gradebook = Security::remove_XSS($_GET['gradebook']);
    // Initialize the object.
    $form = new FormValidator('forumcategory', 'post', 'index.php?gradebook='.$gradebook.'');

    // The header for the form
    if (!empty($inputvalues)) {
        $form_title = get_lang('EditForum');	
    } else {
        $form_title = get_lang('AddForum');
    }
    $session_header = isset($_SESSION['session_name']) ? ' ('.$_SESSION['session_name'].') ' : '';
    $form->addElement('header', '', $form_title.$session_header);

    // We have a hidden field if we are editing.
    if (!empty($inputvalues) && is_array($inputvalues)) {
        $my_forum_id = isset($inputvalues['forum_id']) ? $inputvalues['forum_id'] : null;
        $form->addElement('hidden', 'forum_id', $my_forum_id);
    }
    $lp_id = intval($lp_id);
   	// hidden field if from learning path
	$form->addElement('hidden', 'lp_id', $lp_id);
	
	// The title of the forum
    $form->addElement('text', 'forum_title', get_lang('Title'),'class="input_titles" id="forum_title"');

    //$form->applyFilter('forum_title', 'html_filter');
    // The comment of the forum.
    $form->addElement('html_editor', 'forum_comment', get_lang('Description'), null, array('ToolbarSet' => 'Forum', 'Width' => '98%', 'Height' => '200'));

    //$form->applyFilter('forum_comment', 'html_filter');
    // Dropdown list: Forum categories
    $forum_categories = get_forum_categories();
    foreach ($forum_categories as $key => $value) {
        $forum_categories_titles[$value['cat_id']] = $value['cat_title'];
    }
    $form->addElement('select', 'forum_category', get_lang('InForumCategory'), $forum_categories_titles);
    $form->applyFilter('forum_category', 'html_filter');

    if ($_course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD) {
        // This is for vertical
        //$form->addElement('radio', 'allow_anonymous', get_lang('AllowAnonymousPosts'), get_lang('Yes'), 1);
        //$form->addElement('radio', 'allow_anonymous', '', get_lang('No'), 0);
        // This is for horizontal
        $group = '';
        $group[] =& HTML_QuickForm::createElement('radio', 'allow_anonymous', null, get_lang('Yes'), 1);
        $group[] =& HTML_QuickForm::createElement('radio', 'allow_anonymous', null, get_lang('No'), 0);
        $form->addGroup($group, 'allow_anonymous_group', get_lang('AllowAnonymousPosts'), '&nbsp;');
    }

    // This is for vertical.
    //$form->addElement('radio', 'students_can_edit', get_lang('StudentsCanEdit'), get_lang('Yes'), 1);
    //$form->addElement('radio', 'students_can_edit', '', get_lang('No'), 0);
    // This is for horizontal.

            /*      if (document.getElementById('id_qualify').style.display == 'none') {
                    document.getElementById('id_qualify').style.display = 'block';
                    document.getElementById('plus').innerHTML='&nbsp;'.Display::return_icon('div_hide.gif').'&nbsp;".get_lang('AddAnAttachment')."';
                } else {
                document.getElementById('options').style.display = 'none';
                document.getElementById('plus').innerHTML='&nbsp;'.Display::return_icon('div_show.gif').'&nbsp;".get_lang('AddAnAttachment')."';
                }*/

    $form->addElement('html', '<div class="row"><div class="label"></div><div class="formw">');
    $form->addElement('html', '<a href="javascript://" onclick="advanced_parameters()" ><span id="plus_minus">&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'</span></a>','');
    $form->addElement('html', '</div></div>');
    $form->addElement('html', '<div id="options" style="display:none">');

    $group = '';
    $group[] =& HTML_QuickForm::createElement('radio', 'students_can_edit', null, get_lang('Yes'), 1);
    $group[] =& HTML_QuickForm::createElement('radio', 'students_can_edit', null, get_lang('No'), 0);
    $form->addGroup($group, 'students_can_edit_group', get_lang('StudentsCanEdit'), '&nbsp;');

    // This is for vertical.
    //$form->addElement('radio', 'approval_direct', get_lang('ApprovalDirect'), get_lang('Approval'), 1);
    //$form->addElement('radio', 'approval_direct', '', get_lang('Direct'), 0);
    // This is for horizontal.
    $group = '';
    $group[] =& HTML_QuickForm::createElement('radio', 'approval_direct', null, get_lang('Approval'), 1);
    $group[] =& HTML_QuickForm::createElement('radio', 'approval_direct', null, get_lang('Direct'), 0);
    //$form->addGroup($group, 'approval_direct_group', get_lang('ApprovalDirect'), '&nbsp;');

    // This is for vertical.
    //$form->addElement('radio', 'allow_attachments', get_lang('AllowAttachments'), get_lang('Yes'), 1);
    //$form->addElement('radio', 'allow_attachments', '', get_lang('No'), 0);
    // This is for horizontal.
    $group = '';
    $group[] =& HTML_QuickForm::createElement('radio', 'allow_attachments', null, get_lang('Yes'), 1);
    $group[] =& HTML_QuickForm::createElement('radio', 'allow_attachments', null, get_lang('No'), 0);
    //$form->addGroup($group, 'allow_attachments_group', get_lang('AllowAttachments'), '&nbsp;');

    // This is for vertical.
    //$form->addElement('radio', 'allow_new_threads', get_lang('AllowNewThreads'), 1, get_lang('Yes'));
    //$form->addElement('radio', 'allow_new_threads', '', 0, get_lang('No'));
    // This is for horizontal.
    $group = '';
    $group[] =& HTML_QuickForm::createElement('radio', 'allow_new_threads', null, get_lang('Yes'),1 );
    $group[] =& HTML_QuickForm::createElement('radio', 'allow_new_threads', null, get_lang('No'), 0);
    $form->addGroup($group, 'allow_new_threads_group', get_lang('AllowNewThreads'), '&nbsp;');

    $group = '';
    $group[] =& HTML_QuickForm::createElement('radio', 'default_view_type', null, get_lang('Flat'), 'flat');
    $group[] =& HTML_QuickForm::createElement('radio', 'default_view_type', null, get_lang('Threaded'), 'threaded');
    $group[] =& HTML_QuickForm::createElement('radio', 'default_view_type', null, get_lang('Nested'), 'nested');
    $form->addGroup($group, 'default_view_type_group', get_lang('DefaultViewType'), '&nbsp;');

    //$form->addElement('static','Group', '<br /><strong>'.get_lang('GroupSettings').'</strong>');

    // Dropdown list: Groups.
    $groups = GroupManager::get_group_list();
    $groups_titles[0] = get_lang('NotAGroupForum');
    foreach ($groups as $key => $value) {
        $groups_titles[$value['id']] = $value['name'];
    }
    $form->addElement('select', 'group_forum', get_lang('ForGroup'), $groups_titles);

    // Public or private group forum
    $group='';
    $group[] =& HTML_QuickForm::createElement('radio', 'public_private_group_forum', null, get_lang('Public'), 'public');
    $group[] =& HTML_QuickForm::createElement('radio', 'public_private_group_forum', null, get_lang('Private'), 'private');
    $form->addGroup($group, 'public_private_group_forum_group', get_lang('PublicPrivateGroupForum'), '&nbsp;');

    // Forum image
    $form->add_progress_bar();
    if (isset($inputvalues['forum_image']) && strlen($inputvalues['forum_image']) > 0) {

        $image_path = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/upload/forum/images/'.$inputvalues['forum_image'];
        $image_size = api_getimagesize($image_path);

        $img_attributes = '';
        if (!empty($image_size)) {
            if ($image_size['width'] > 100 || $image_size['height'] > 100) {
                //limit display width and height to 100px
                $img_attributes = 'width="100" height="100"';
            }
            $show_preview_image='<img src="'.$image_path.'" '.$img_attributes.'>';
            $div = '<div class="row">
            <div class="label">'.get_lang('PreviewImage').'</div>
            <div class="formw">
            '.$show_preview_image.'
            </div>
            </div>';

            $form->addElement('html', $div .'<br/>');
            $form->addElement('checkbox', 'remove_picture', null, get_lang('DelImage'));
        }

    }
    $forum_image = isset($inputvalues['forum_image']) ? $inputvalues['forum_image'] : '';
    $form->addElement('file', 'picture', ($forum_image != '' ? get_lang('UpdateImage') : get_lang('AddImage')));
    $form->addRule('picture', get_lang('OnlyImagesAllowed'), 'filetype', array ('jpg', 'jpeg', 'png', 'gif'));
    $form->addElement('html', '</div>');

    // The OK button
    if (isset($_GET['id']) && $_GET['action'] == 'edit') {
        $class = 'save';
        $text = get_lang('ModifyForum');
    } else {
        $class = 'add';
        $text = get_lang('CreateForum');
    }
    $form->addElement('style_submit_button', 'SubmitForum', $text, 'class="'.$class.'"');
    // setting the rules
    $form->addRule('forum_title', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('forum_category', get_lang('ThisFieldIsRequired'), 'required');

    // Settings the defaults.
    if (empty($inputvalues) || !is_array($inputvalues)) {
        $defaults['allow_anonymous_group']['allow_anonymous'] = 0;
        $defaults['students_can_edit_group']['students_can_edit'] = 0;
        $defaults['approval_direct_group']['approval_direct'] = 0;
        $defaults['allow_attachments_group']['allow_attachments'] = 1;
        $defaults['allow_new_threads_group']['allow_new_threads'] = 0;
        $defaults['default_view_type_group']['default_view_type'] = api_get_setting('default_forum_view');
        $defaults['public_private_group_forum_group']['public_private_group_forum'] = 'public';
        if (isset($_GET['forumcategory'])) {
            $defaults['forum_category'] = Security::remove_XSS($_GET['forumcategory']);
        }
    } else {   // the default values when editing = the data in the table
        $defaults['forum_id'] = isset($inputvalues['forum_id']) ? $inputvalues['forum_id'] : null;
        $defaults['forum_title'] = prepare4display(isset($inputvalues['forum_title']) ? $inputvalues['forum_title'] : null);
        $defaults['forum_comment'] = prepare4display(isset($inputvalues['forum_comment']) ? $inputvalues['forum_comment'] : null);
        $defaults['forum_category'] = isset($inputvalues['forum_category']) ? $inputvalues['forum_category'] : null;
        $defaults['allow_anonymous_group']['allow_anonymous'] = isset($inputvalues['allow_anonymous']) ? $inputvalues['allow_anonymous'] :null;
        $defaults['students_can_edit_group']['students_can_edit'] = isset($inputvalues['allow_edit']) ? $inputvalues['allow_edit'] : null;
        $defaults['approval_direct_group']['approval_direct'] = isset($inputvalues['approval_direct_post']) ? $inputvalues['approval_direct_post'] : null;
        $defaults['allow_attachments_group']['allow_attachments'] = isset($inputvalues['allow_attachments']) ? $inputvalues['allow_attachments'] : null;
        $defaults['allow_new_threads_group']['allow_new_threads'] = isset($inputvalues['allow_new_threads']) ? $inputvalues['allow_new_threads'] : null;
        $defaults['default_view_type_group']['default_view_type'] = isset($inputvalues['default_view']) ? $inputvalues['default_view'] : null;
        $defaults['public_private_group_forum_group']['public_private_group_forum'] = isset($inputvalues['forum_group_public_private']) ? $inputvalues['forum_group_public_private'] : null;
        $defaults['group_forum'] = isset($inputvalues['forum_of_group']) ? $inputvalues['forum_of_group'] : null;
    }
    $form->setDefaults($defaults);
    // Validation or display
    if( $form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();
            $return_message = store_forum($values);
            Display :: display_confirmation_message($return_message);
        }
        Security::clear_token();
    } else {
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(array('sec_token' => $token));
        $form->display();
    }
}

/**
 * This function deletes the forum image if exists
 *
 * @param int forum id
 * @return boolean true if success
 * @author Julio Montoya <gugli100@gmail.com>
 * @version february 2006, dokeos 1.8
 */
function delete_forum_image($forum_id) {
    $table_forums = Database::get_course_table(TABLE_FORUM);
    $course_id = api_get_course_int_id();

    $forum_id = Database::escape_string($forum_id);
    $sql = "SELECT forum_image FROM $table_forums WHERE forum_id = '".$forum_id."' AND c_id = $course_id";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    if ($row['forum_image'] != '') {
        $del_file = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/upload/forum/images/'.$row['forum_image'];
        return @unlink($del_file);
    } else {
        return false;
    }
}

/**
 * This function displays the form that is used to edit a forum category.
 * This is more or less a copy from the show_add_forumcategory_form function with the only difference that is uses
 * some default values. I tried to have both in one function but this gave problems with the handle_forum_and_forumcategories function
 * (storing was done twice)
 *
 * @param array
 * @return void HTML
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function show_edit_forumcategory_form($inputvalues = array()) {
    // Initialize the object.
    $gradebook = Security::remove_XSS($_GET['gradebook']);
    $form = new FormValidator('forumcategory', 'post', 'index.php?&amp;gradebook='.$gradebook.'');

    // Settting the form elements.
    $form->addElement('header', '', get_lang('EditForumCategory'));
    $form->addElement('hidden', 'forum_category_id');
    $form->addElement('text', 'forum_category_title', get_lang('Title'),'class="input_titles"');

    //$form->applyFilter('forum_category_title', 'html_filter');
    $form->addElement('html_editor', 'forum_category_comment', get_lang('Comment'), null, array('ToolbarSet' => 'Forum', 'Width' => '98%', 'Height' => '200'));

    //$form->applyFilter('forum_category_comment', 'html_filter');
    $form->addElement('style_submit_button', 'SubmitEditForumCategory',get_lang('ModifyCategory'), 'class="save"');

    // Setting the default values.
    $defaultvalues['forum_category_id'] = $inputvalues['cat_id'];
    $defaultvalues['forum_category_title'] = $inputvalues['cat_title'];
    $defaultvalues['forum_category_comment'] = $inputvalues['cat_comment'];
    $form->setDefaults($defaultvalues);

    // Setting the rules.
    $form->addRule('forum_category_title', get_lang('ThisFieldIsRequired'), 'required');

    // Validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();
            store_forumcategory($values);
        }
        Security::clear_token();
    } else {
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(array('sec_token' => $token));
        $form->display();
    }
}

/**
 * This function stores the forum category in the database. The new category is added to the end.
 *
 * @param array
 * @return void HMTL language variable
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function store_forumcategory($values) {
    global $_course;
    global $_user;
    
    $course_id = api_get_course_int_id();

    $table_categories = Database::get_course_table(TABLE_FORUM_CATEGORY);

    // Find the max cat_order. The new forum category is added at the end => max cat_order + &
    $sql = "SELECT MAX(cat_order) as sort_max FROM ".Database::escape_string($table_categories)." WHERE c_id = $course_id";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    $new_max = $row['sort_max'] + 1;
    $session_id = api_get_session_id();

    $clean_cat_title = Database::escape_string($values['forum_category_title']);

    if (isset($values['forum_category_id'])) { // Storing after edition.
        $sql = "UPDATE ".$table_categories." SET cat_title='".$clean_cat_title."', cat_comment='".Database::escape_string($values['forum_category_comment'])."'
                WHERE c_id = $course_id AND cat_id='".Database::escape_string($values['forum_category_id'])."'";
        Database::query($sql);
        $last_id = Database::insert_id();
        api_item_property_update(api_get_course_info(), TOOL_FORUM_CATEGORY, $values['forum_category_id'], 'ForumCategoryUpdated', api_get_user_id());
        $return_message = get_lang('ForumCategoryEdited');
    } else {
        $sql = "INSERT INTO ".$table_categories." (c_id, cat_title, cat_comment, cat_order, session_id) 
        		VALUES (".$course_id.", '".$clean_cat_title."','".Database::escape_string($values['forum_category_comment'])."','".Database::escape_string($new_max)."','".Database::escape_string($session_id)."')";
        Database::query($sql);
        $last_id = Database::insert_id();
        if ($last_id > 0) {
            api_item_property_update(api_get_course_info(), TOOL_FORUM_CATEGORY, $last_id, 'ForumCategoryAdded', api_get_user_id());
        }
        $return_message = get_lang('ForumCategoryAdded');
    }
    Display :: display_confirmation_message($return_message);
}

/**
 * This function stores the forum in the database. The new forum is added to the end.
 *
 * @param array
 * @return string language variable
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function store_forum($values) {
    global $_course;
    global $_user;
    $course_id = api_get_course_int_id();
    $table_forums = Database::get_course_table(TABLE_FORUM);

    // Find the max forum_order for the given category. The new forum is added at the end => max cat_order + &
    if (is_null($values['forum_category'])) {
        $new_max = null;
    } else {
        $sql = "SELECT MAX(forum_order) as sort_max FROM ".$table_forums." 
        		WHERE c_id = $course_id AND forum_category='".Database::escape_string($values['forum_category'])."'";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $new_max = $row['sort_max'] + 1;
    }

    $session_id = api_get_session_id();
    $clean_title = Database::escape_string($values['forum_title']);

    // Forum images
    $image_moved = false;
    if (!empty($_FILES['picture']['name'])) {
        $upload_ok = process_uploaded_file($_FILES['picture']);
        $has_attachment = true;
    } else {
        $image_moved = true;
    }

    // Remove existing picture if it was requested.
    if (!empty($_POST['remove_picture'])) {
        delete_forum_image($values['forum_id']);
    }

    if (isset($upload_ok)) {
        if ($has_attachment) {
            $course_dir = $_course['path'].'/upload/forum/images';
            $sys_course_path = api_get_path(SYS_COURSE_PATH);
            $updir = $sys_course_path.$course_dir;
            // Try to add an extension to the file if it hasn't one.
            $new_file_name = add_ext_on_mime(Database::escape_string($_FILES['picture']['name']), $_FILES['picture']['type']);
            // User's file name
            $file_name = $_FILES['picture']['name'];

            if (!filter_extension($new_file_name)) {
                 //Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
                 $image_moved = false;
            } else {
                 $file_extension = explode('.', $_FILES['picture']['name']);
                 $file_extension = strtolower($file_extension[sizeof($file_extension) - 1]);
                 $new_file_name = uniqid('').'.'.$file_extension;
                 $new_path = $updir.'/'.$new_file_name;
                 $result = @move_uploaded_file($_FILES['picture']['tmp_name'], $new_path);
                 // Storing the attachments if any
                 if ($result) {
                     $image_moved = true;
                 }
            }
        }
    }

    if (isset($values['forum_id'])) {
        $sql_image = isset($sql_image) ? $sql_image : '';
        $new_file_name = isset($new_file_name) ? $new_file_name : '';
          if ($image_moved) {
              if (empty($_FILES['picture']['name'])) {
                  $sql_image = " ";
              } else {
                  $sql_image = " forum_image='".Database::escape_string($new_file_name)."', ";
                  delete_forum_image($values['forum_id']);
              }
        }

        // Storing after edition.
        $sql = "UPDATE ".$table_forums." SET
                forum_title='".$clean_title."',
                ".$sql_image."
                forum_comment='".	Database::escape_string(stripslashes($values['forum_comment']))."',
                forum_category='".	Database::escape_string(stripslashes($values['forum_category']))."',
                allow_anonymous='".	Database::escape_string(isset($values['allow_anonymous_group']['allow_anonymous'])?$values['allow_anonymous_group']['allow_anonymous']:null)."',
                allow_edit='".		Database::escape_string($values['students_can_edit_group']['students_can_edit'])."',
                approval_direct_post='".Database::escape_string(isset($values['approval_direct_group']['approval_direct'])?$values['approval_direct_group']['approval_direct']:null)."',
                allow_attachments='".Database::escape_string(isset($values['allow_attachments_group']['allow_attachments'])?$values['allow_attachments_group']['allow_attachments']:null)."',
                allow_new_threads='".Database::escape_string($values['allow_new_threads_group']['allow_new_threads'])."',
                forum_group_public_private='".Database::escape_string($values['public_private_group_forum_group']['public_private_group_forum'])."',
                default_view='".	Database::escape_string($values['default_view_type_group']['default_view_type'])."',
                forum_of_group='".	Database::escape_string($values['group_forum'])."'
            WHERE c_id = $course_id AND forum_id='".Database::escape_string($values['forum_id'])."'";
            Database::query($sql);
         
            api_item_property_update($_course, TOOL_FORUM, Database::escape_string($values['forum_id']), 'ForumUpdated', api_get_user_id());
            $return_message = get_lang('ForumEdited');
    } else {
        $sql_image = '';
        if ($image_moved) {
            $new_file_name = isset($new_file_name) ? $new_file_name : '';
            $sql_image = "'".$new_file_name."', ";
        }
        $b = $values['forum_comment'];

        $sql = "INSERT INTO ".$table_forums." (c_id, forum_title, forum_image, forum_comment, forum_category, allow_anonymous, allow_edit, approval_direct_post, allow_attachments, allow_new_threads, default_view, forum_of_group, forum_group_public_private, forum_order, session_id)
            VALUES (
            	".$course_id.",
            	'".$clean_title."',
                ".$sql_image."
                '".Database::escape_string(isset($values['forum_comment'])?$values['forum_comment']:null)."',
                '".Database::escape_string(isset($values['forum_category'])?$values['forum_category']:null)."',
                '".Database::escape_string(isset($values['allow_anonymous_group']['allow_anonymous'])?$values['allow_anonymous_group']['allow_anonymous']:null)."',
                '".Database::escape_string(isset($values['students_can_edit_group']['students_can_edit'])?$values['students_can_edit_group']['students_can_edit']:null)."',
                '".Database::escape_string(isset($values['approval_direct_group']['approval_direct'])?$values['approval_direct_group']['approval_direct']:null)."',
                '".Database::escape_string(isset($values['allow_attachments_group']['allow_attachments'])?$values['allow_attachments_group']['allow_attachments']:null)."',
                '".Database::escape_string(isset($values['allow_new_threads_group']['allow_new_threads'])?$values['allow_new_threads_group']['allow_new_threads']:null)."',
                '".Database::escape_string(isset($values['default_view_type_group']['default_view_type'])?$values['default_view_type_group']['default_view_type']:null)."',
                '".Database::escape_string(isset($values['group_forum'])?$values['group_forum']:null)."',
                '".Database::escape_string(isset($values['public_private_group_forum_group']['public_private_group_forum'])?$values['public_private_group_forum_group']['public_private_group_forum']:null)."',
                '".Database::escape_string(isset($new_max)?$new_max:null)."',
                ".intval($session_id).")";                   
        Database::query($sql);
        $last_id = Database::insert_id();
        if ($last_id > 0) {
            api_item_property_update($_course, TOOL_FORUM, $last_id, 'ForumAdded', api_get_user_id());
        }
        $return_message = get_lang('ForumAdded');
    }
    return $return_message;
}

/**
 * This function deletes a forum or a forum category
 * This function currently does not delete the forums inside the category, nor the threads and replies inside these forums.
 * For the moment this is the easiest method and it has the advantage that it allows to recover fora that were acidently deleted
 * when the forum category got deleted.
 *
 * @param $content = what we are deleting (a forum or a forum category)
 * @param $id The id of the forum category that has to be deleted.
 *
 * @todo write the code for the cascading deletion of the forums inside a forum category and also the threads and replies inside these forums
 * @todo config setting for recovery or not (see also the documents tool: real delete or not).
 * @return void
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function delete_forum_forumcategory_thread($content, $id) {
    global $_course;

    $table_forums       = Database::get_course_table(TABLE_FORUM);
    $table_forums_post  = Database::get_course_table(TABLE_FORUM_POST);
    $table_forum_thread = Database::get_course_table(TABLE_FORUM_THREAD);
    $course_id = api_get_course_int_id();

    // Delete all attachment file about this tread id.
    $sql = "SELECT post_id FROM $table_forums_post WHERE c_id = $course_id AND thread_id = '".(int)$id."' ";
    $res = Database::query($sql);
    while ($poster_id = Database::fetch_row($res)) {
        delete_attachment($poster_id[0]);
    }

    if ($content == 'forumcategory') {
        $tool_constant = TOOL_FORUM_CATEGORY;
        $return_message = get_lang('ForumCategoryDeleted');

        if (!empty($forum_list)){
            $sql = "SELECT forum_id FROM ". $table_forums . "WHERE c_id = $course_id AND forum_category='".$id."'";
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            foreach ($row as $arr_forum) {
                $forum_id = $arr_forum['forum_id'];
                api_item_property_update($_course, 'forum', $forum_id, 'delete', api_get_user_id());
            }
        }
    }
    if ($content == 'forum') {
        $tool_constant = TOOL_FORUM;
        $return_message = get_lang('ForumDeleted');

        if (!empty($number_threads)){
            $sql = "SELECT thread_id FROM". $table_forum_thread . "WHERE c_id = $course_id AND forum_id='".$id."'";
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            foreach ($row as $arr_forum) {
                $forum_id = $arr_forum['thread_id'];
                api_item_property_update($_course, 'forum_thread', $forum_id, 'delete', api_get_user_id());
            }
        }
    }
    if ($content == 'thread') {
        $tool_constant = TOOL_FORUM_THREAD;
        $return_message = get_lang('ThreadDeleted');
    }
    api_item_property_update($_course, $tool_constant, $id, 'delete', api_get_user_id()); // Note: Check if this returns a true and if so => return $return_message, if not => return false;
    return $return_message;
}

/**
 * This function deletes a forum post. This separate function is needed because forum posts do not appear in the item_property table (yet)
 * and because deleting a post also has consequence on the posts that have this post as parent_id (they are also deleted).
 * an alternative would be to store the posts also in item_property and mark this post as deleted (visibility = 2).
 * We also have to decrease the number of replies in the thread table
 *
 * @param $post_id the id of the post that will be deleted
 * @todo write recursive function that deletes all the posts that have this message as parent
 * @return string language variable
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Hubert Borderiou Function cleanead and fixed
 * @version february 2006
 */
function delete_post($post_id) {
    $table_posts 		= Database :: get_course_table(TABLE_FORUM_POST);
    $table_threads 		= Database :: get_course_table(TABLE_FORUM_THREAD);
    $post_id = intval($post_id);
    $course_id = api_get_course_int_id();

    // Get parent_post_id of deleted post.
    $tab_post_info = get_post_information($post_id);
    $post_parent_id_of_deleted_post = $tab_post_info['post_parent_id'];
    $thread_id_of_deleted_post = $tab_post_info['thread_id'];
    $forum_if_of_deleted_post = $tab_post_info['forum_id'];
    $sql = "UPDATE $table_posts SET post_parent_id=$post_parent_id_of_deleted_post 
            WHERE c_id = $course_id AND post_parent_id=$post_id AND thread_id=$thread_id_of_deleted_post AND forum_id=$forum_if_of_deleted_post;";
    Database::query($sql);

    $sql = "DELETE FROM $table_posts WHERE c_id = $course_id AND post_id='".Database::escape_string($post_id)."'"; // Note: This has to be a recursive function that deletes all of the posts in this block.
    Database::query($sql);

    // Delete attachment file about this post id.
    delete_attachment($post_id);

    $last_post_of_thread = check_if_last_post_of_thread(intval($_GET['thread']));

    if (is_array($last_post_of_thread)) {
        // Decreasing the number of replies for this thread and also changing the last post information.
        $sql = "UPDATE $table_threads SET thread_replies=thread_replies-1,
                    thread_last_post='".Database::escape_string($last_post_of_thread['post_id'])."',
                    thread_date='".Database::escape_string($last_post_of_thread['post_date'])."'
            WHERE c_id = $course_id AND thread_id='".intval($_GET['thread'])."'";
        Database::query($sql);
        return 'PostDeleted';
    }
    if (!$last_post_of_thread) {
        // We deleted the very single post of the thread so we need to delete the entry in the thread table also.
        $sql = "DELETE FROM $table_threads WHERE c_id = $course_id AND thread_id='".intval($_GET['thread'])."'";
        Database::query($sql);
        return 'PostDeletedSpecial';
    }
}

/**
 * This function gets the all information of the last (=most recent) post of the thread
 * This can be done by sorting the posts that have the field thread_id=$thread_id and sort them by post_date
 *
 * @param $thread_id the id of the thread we want to know the last post of.
 * @return an array or bool if there is a last post found, false if there is no post entry linked to that thread => thread will be deleted
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function check_if_last_post_of_thread($thread_id) {
    $table_posts	= Database :: get_course_table(TABLE_FORUM_POST);
    $course_id      = api_get_course_int_id();
    $sql = "SELECT * FROM $table_posts WHERE c_id = $course_id AND thread_id='".Database::escape_string($thread_id)."' ORDER BY post_date DESC";
    $result = Database::query($sql);
    if (Database::num_rows($result) > 0) {
        $row = Database::fetch_array($result);
        return $row;
    } else {
        return false;
    }
}

/**
 * This function takes care of the display of the visibility icon
 *
 * @param $content what is it that we want to make (in)visible: forum category, forum, thread, post
 * @param $id the id of the content we want to make invisible
 * @param $current_visibility_status what is the current status of the visibility (0 = invisible, 1 = visible)
 * @return void string HTML
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function display_visible_invisible_icon($content, $id, $current_visibility_status, $additional_url_parameters = '') {
    global $origin;
    $gradebook = Security::remove_XSS($_GET['gradebook']);
    $id = Security::remove_XSS($id);
    if ($current_visibility_status == '1') {
        echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;gidReq='.api_get_group_id().'&amp;';
        if (is_array($additional_url_parameters)) {
            foreach ($additional_url_parameters as $key => $value) {
                echo $key.'='.$value.'&amp;';
            }
        }
        echo 'action=invisible&amp;content='.$content.'&amp;id='.$id.'&gradebook='.$gradebook.'&amp;origin='.$origin.'">'.Display::return_icon('visible.png', get_lang('MakeInvisible'), array(), 22).'</a>';
    }
    if ($current_visibility_status == '0') {
        echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&amp;';
        if (is_array($additional_url_parameters)) {
            foreach ($additional_url_parameters as $key => $value) {
                echo $key.'='.$value.'&amp;';
            }
        }
        echo 'action=visible&amp;content='.$content.'&amp;id='.$id.'&gradebook='.$gradebook.'&amp;origin='.$origin.'">'.Display::return_icon('invisible.png', get_lang('MakeVisible'), array(), 22).'</a>';
    }
}

/**
 * This function takes care of the display of the lock icon
 *
 * @param $content what is it that we want to (un)lock: forum category, forum, thread, post
 * @param $id the id of the content we want to (un)lock
 * @param $current_visibility_status what is the current status of the visibility (0 = invisible, 1 = visible)
 * @return void display the lock HTML.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function display_lock_unlock_icon($content, $id, $current_lock_status, $additional_url_parameters = '') {
    $id = Security::remove_XSS($id);
    if ($current_lock_status == '1') {
        echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&amp;';
        if (is_array($additional_url_parameters)) {
            foreach ($additional_url_parameters as $key => $value) {
                echo $key.'='.$value.'&amp;';
            }
        }
        echo 'action=unlock&amp;content='.$content.'&amp;id='.$id.'">'.Display::return_icon('lock.png', get_lang('Unlock'), array(), 22).'</a>';
    }
    if ($current_lock_status == '0') {
        echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;gidReq='.api_get_group_id().'&amp;';
        if (is_array($additional_url_parameters)) {
            foreach ($additional_url_parameters as $key => $value) {
                echo $key.'='.$value.'&amp;';
            }
        }
        echo 'action=lock&amp;content='.$content.'&amp;id='.$id.'">'.Display::return_icon('unlock.png', get_lang('Lock'), array(), 22).'</a>';
    }
}

/**
 * This function takes care of the display of the up and down icon
 *
 * @param $content what is it that we want to make (in)visible: forum category, forum, thread, post
 * @param $id is the id of the item we want to display the icons for
 * @param $list is an array of all the items. All items in this list should have an up and down icon except for the first (no up icon) and the last (no down icon)
 * 		 The key of this $list array is the id of the item.
 *
 * @return void HTML
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function display_up_down_icon($content, $id, $list) {
    $id = strval(intval($id));
    $total_items = count($list);
    $position = 0;
    $internal_counter = 0;

    if (is_array($list)) {
        foreach ($list as $key => $listitem) {
            $internal_counter++;
            if ($id == $key) {
                $position = $internal_counter;
            }
        }
    }
    if ($position > 1) {
        $return_value = '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&action=move&amp;direction=up&amp;content='.$content.'&amp;forumcategory='.Security::remove_XSS($_GET['forumcategory']).'&amp;id='.$id.'" title="'.get_lang('MoveUp').'">'.Display::return_icon('up.png', get_lang('MoveUp'), array(), 22).'</a>';
    } else {
        $return_value = Display::return_icon('up_na.png', '-', array(), 22);
    }

    if ($position<$total_items) {
        $return_value .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&action=move&amp;direction=down&amp;content='.$content.'&amp;forumcategory='.Security::remove_XSS($_GET['forumcategory']).'&amp;id='.$id.'" title="'.get_lang('MoveDown').'" >'.Display::return_icon('down.png', get_lang('MoveDown'), array(), 22).'</a>';
    } else {
      $return_value .= Display::return_icon('down_na.png', '-', array(), 22);
    }
    echo $return_value;
}

/**
 * This function changes the visibility in the database (item_property)
 *
 * @param $content what is it that we want to make (in)visible: forum category, forum, thread, post
 * @param $id the id of the content we want to make invisible
 * @param $target_visibility what is the current status of the visibility (0 = invisible, 1 = visible)
 *
 * @todo change the get parameter so that it matches the tool constants.
 * @todo check if api_item_property_update returns true or false => returnmessage depends on it.
 * @todo move to itemmanager
 *
 * @return string language variable
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function change_visibility($content, $id, $target_visibility) {
    global $_course;
    $constants = array('forumcategory' => TOOL_FORUM_CATEGORY, 'forum' => TOOL_FORUM, 'thread' => TOOL_FORUM_THREAD);
    api_item_property_update($_course, $constants[$content], $id, $target_visibility, api_get_user_id()); // Note: Check if this returns true or false => returnmessage depends on it.
    if ($target_visibility == 'visible') {
        handle_mail_cue($content, $id);
    }
    return get_lang('VisibilityChanged');
}

/**
 * This function changes the lock status in the database
 *
 * @param $content what is it that we want to (un)lock: forum category, forum, thread, post
 * @param $id the id of the content we want to (un)lock
 * @param $action do we lock (=>locked value in db = 1) or unlock (=> locked value in db = 0)
 * @return string, language variable
 *
 * @todo move to itemmanager
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function change_lock_status($content, $id, $action) {
    $table_categories 		= Database :: get_course_table(TABLE_FORUM_CATEGORY);
    $table_forums 			= Database :: get_course_table(TABLE_FORUM);
    $table_threads 			= Database :: get_course_table(TABLE_FORUM_THREAD);
    $table_posts 			= Database :: get_course_table(TABLE_FORUM_POST);

    // Determine the relevant table.
    if ($content == 'forumcategory') {
        $table = $table_categories;
        $id_field = 'cat_id';
    } elseif ($content == 'forum') {
        $table = $table_forums;
        $id_field = 'forum_id';
    } elseif ($content == 'thread') {
        $table = $table_threads;
        $id_field = 'thread_id';
    } else {
        return get_lang('Error');
    }

    // Determine what we are doing => defines the value for the database and the return message.
    if ($action == 'lock') {
        $db_locked = 1;
        $return_message = get_lang('Locked');
    } elseif ($action == 'unlock') {
        $db_locked = 0;
        $return_message = get_lang('Unlocked');
    } else {
        return get_lang('Error');
    }
    
    $course_id = api_get_course_int_id();

    // Doing the change in the database
    $sql = "UPDATE $table SET locked='".Database::escape_string($db_locked)."' WHERE c_id = $course_id AND $id_field='".Database::escape_string($id)."'";
    if (Database::query($sql)) {
        return $return_message;
    } else {
        return get_lang('Error');
    }
}

/**
 * This function moves a forum or a forum category up or down
 *
 * @param $content what is it that we want to make (in)visible: forum category, forum, thread, post
 * @param $direction do we want to move it up or down.
 * @param $id the id of the content we want to make invisible
 * @todo consider removing the table_item_property calls here but this can prevent unwanted side effects when a forum does not have an entry in
 * 		the item_property table but does have one in the forum table.
 * @return string language variable
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function move_up_down($content, $direction, $id) {
    $table_categories 		= Database :: get_course_table(TABLE_FORUM_CATEGORY);
    $table_forums 			= Database :: get_course_table(TABLE_FORUM);
    $table_item_property 	= Database :: get_course_table(TABLE_ITEM_PROPERTY);
    $course_id = api_get_course_int_id();

    // Determine which field holds the sort order.
    if ($content == 'forumcategory') {
        $table = $table_categories;
        $sort_column = 'cat_order';
        $id_column = 'cat_id';
        $sort_column = 'cat_order';
    } elseif ($content == 'forum') {
        $table = $table_forums;
        $sort_column = 'forum_order';
        $id_column = 'forum_id';
        $sort_column = 'forum_order';
        // We also need the forum_category of this forum.
        $sql = "SELECT forum_category FROM $table_forums WHERE c_id = $course_id AND forum_id=".Database::escape_string($id);
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $forum_category = $row['forum_category'];
    } else {
        return get_lang('Error');
    }

    // Determine the need for sorting ascending or descending order.
    if ($direction == 'down') {
        $sort_direction = 'ASC';
    } elseif ($direction == 'up') {
        $sort_direction = 'DESC';
    } else {
        return get_lang('Error');
    }

    // The SQL statement
    if ($content == 'forumcategory') {
        $sql = "SELECT * FROM".$table_categories." forum_categories, ".$table_item_property." item_properties
                WHERE
                forum_categories.c_id = $course_id AND 
                item_properties.c_id = $course_id AND  
                forum_categories.cat_id=item_properties.ref
                AND item_properties.tool='".TOOL_FORUM_CATEGORY."'
                ORDER BY forum_categories.cat_order $sort_direction";
    }
    if ($content == 'forum') {
        $sql = "SELECT * FROM".$table." WHERE c_id = $course_id AND forum_category='".Database::escape_string($forum_category)."' ORDER BY forum_order $sort_direction";
    }
    // echo $sql.'<br />';
    // Finding the items that need to be switched.
    $result = Database::query($sql);
    $found = false;
    while ($row = Database::fetch_array($result)) {
        //echo $row[$id_column].'-';
        if ($found) {
            $next_id = $row[$id_column];
            $next_sort = $row[$sort_column];
            $found = false;
        }
        if ($id == $row[$id_column]) {
            $this_id = $id;
            $this_sort = $row[$sort_column];
            $found = true;
        }
    }

    // Committing the switch.
    // We do an extra check if we do not have illegal values. If your remove this if statment you will
    // be able to mess with the sorting by refreshing the page over and over again.
    if ($this_sort != '' && $next_sort != '' && $next_id != '' && $this_id != '') {
        $sql_update1 = "UPDATE $table SET $sort_column='".Database::escape_string($this_sort)."' WHERE c_id = $course_id AND $id_column='".Database::escape_string($next_id)."'";
        $sql_update2 = "UPDATE $table SET $sort_column='".Database::escape_string($next_sort)."' WHERE c_id = $course_id AND $id_column='".Database::escape_string($this_id)."'";
        Database::query($sql_update1);
        Database::query($sql_update2);
    }
    return get_lang(ucfirst($content).'Moved');
}

/**
 * This function returns a piece of html code that make the links grey (=invisible for the student)
 *
 * @param boolean 0/1: 0 = invisible, 1 = visible
 * @return string language variable
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function class_visible_invisible($current_visibility_status) {
    if ($current_visibility_status == '0') {
        return 'class="invisible"';
    }
}

/**
 * Retrieve all the information off the forum categories (or one specific) for the current course.
 * The categories are sorted according to their sorting order (cat_order
 *
 * @param $id default ''. When an id is passed we only find the information about that specific forum category. If no id is passed we get all the forum categories.
 * @return an array containing all the information about all the forum categories
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function get_forum_categories($id = '') {
    $table_categories		= Database :: get_course_table(TABLE_FORUM_CATEGORY);
    $table_item_property	= Database :: get_course_table(TABLE_ITEM_PROPERTY);
    $forum_categories_list = array();

    // Condition for the session
    $session_id = api_get_session_id();
    $condition_session = api_get_session_condition($session_id);
    $course_id = api_get_course_int_id();
    $condition_session .= "AND forum_categories.c_id = $course_id ";

    if ($id == '') {
        $sql = "SELECT * FROM".$table_categories." forum_categories, ".$table_item_property." item_properties
                    WHERE forum_categories.cat_id=item_properties.ref
                    AND item_properties.visibility=1
                    AND item_properties.tool='".TOOL_FORUM_CATEGORY."' $condition_session
                    ORDER BY forum_categories.cat_order ASC";
        if (is_allowed_to_edit()) {
            $sql = "SELECT * FROM".$table_categories." forum_categories, ".$table_item_property." item_properties
                    WHERE forum_categories.cat_id=item_properties.ref
                    AND item_properties.visibility<>2
                    AND item_properties.tool='".TOOL_FORUM_CATEGORY."' $condition_session
                    ORDER BY forum_categories.cat_order ASC";
        }
    } else {
        $sql = "SELECT * FROM".$table_categories." forum_categories, ".$table_item_property." item_properties
                WHERE forum_categories.cat_id=item_properties.ref
                AND item_properties.tool='".TOOL_FORUM_CATEGORY."'
                AND forum_categories.cat_id='".Database::escape_string($id)."' $condition_session
                ORDER BY forum_categories.cat_order ASC";
    }
    
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        if ($id == '') {
            $forum_categories_list[$row['cat_id']] = $row;
        } else {
            $forum_categories_list = $row;
        }
    }
    return $forum_categories_list;
}

/**
 * This function retrieves all the fora in a given forum category
 *
 * @param integer $cat_id the id of the forum category
 * @return an array containing all the information about the forums (regardless of their category)
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function get_forums_in_category($cat_id) {
    $table_forums 			= Database :: get_course_table(TABLE_FORUM);
    $table_item_property 	= Database :: get_course_table(TABLE_ITEM_PROPERTY);

    $forum_list = array();
    $course_id = api_get_course_int_id();
    
    $sql = "SELECT * FROM ".$table_forums." forum , ".$table_item_property." item_properties
            WHERE forum.forum_category='".Database::escape_string($cat_id)."'
                AND forum.forum_id=item_properties.ref
                AND item_properties.visibility=1
                AND item_properties.tool='".TOOL_FORUM."' AND
                forum.c_id = $course_id
                ORDER BY forum.forum_order ASC";
    if (is_allowed_to_edit()) {
        $sql = "SELECT * FROM ".$table_forums." forum , ".$table_item_property." item_properties
                WHERE 	forum.forum_category		= '".Database::escape_string($cat_id)."' AND 
                		forum.forum_id				= item_properties.ref AND 
                		item_properties.visibility<>2 AND 
                		item_properties.tool		= '".TOOL_FORUM."' AND 
                		forum.c_id 					= $course_id
                ORDER BY forum_order ASC";
    }
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        $forum_list[$row['forum_id']] = $row;
    }
    return $forum_list;
}

/**
 * Retrieve all the forums (regardless of their category) or of only one. The forums are sorted according to the forum_order.
 * Since it does not take the forum category into account there probably will be two or more forums that have forum_order=1, ...
 * @param int forum id
 * @param string course db name
 * @return an array containing all the information about the forums (regardless of their category)
 * @todo check $sql4 because this one really looks fishy.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function get_forums($id='', $course_code = '') {
    $course_info = api_get_course_info($course_code);
    
    $table_users 			= Database :: get_main_table(TABLE_MAIN_USER);
    $table_forums 			= Database :: get_course_table(TABLE_FORUM);
    $table_threads 			= Database :: get_course_table(TABLE_FORUM_THREAD);
    $table_posts 			= Database :: get_course_table(TABLE_FORUM_POST);
    $table_item_property 	= Database :: get_course_table(TABLE_ITEM_PROPERTY);


    // GETTING ALL THE FORUMS //

    // Condition for the session
    $session_id = api_get_session_id();
    $condition_session = api_get_session_condition($session_id);
    $course_id = $course_info['real_id'];
    
    $condition_course = " AND forum.c_id = $course_id";

    $forum_list = array();
    if ($id == '') {
        // Student 
        // Select all the forum information of all forums (that are visible to students).
        $sql = "SELECT * FROM $table_forums forum , ".$table_item_property." item_properties
                        WHERE forum.forum_id=item_properties.ref
                        AND item_properties.visibility=1
                        AND item_properties.tool='".TOOL_FORUM."'
                        $condition_session AND forum.c_id = $course_id
                        ORDER BY forum.forum_order ASC";

        // Select the number of threads of the forums (only the threads that are visible).
        $sql2 = "SELECT count(*) AS number_of_threads, threads.forum_id FROM $table_threads threads, ".$table_item_property." item_properties
                        WHERE threads.thread_id=item_properties.ref
                        AND item_properties.visibility=1
                        AND item_properties.tool='".TOOL_FORUM_THREAD."' AND threads.c_id = $course_id
                        GROUP BY threads.forum_id";

        // Select the number of posts of the forum (post that are visible and that are in a thread that is visible).
        $sql3 = "SELECT count(*) AS number_of_posts, posts.forum_id FROM $table_posts posts, $table_threads threads, ".$table_item_property." item_properties
                        WHERE posts.visible=1
                        AND posts.thread_id=threads.thread_id
                        AND threads.thread_id=item_properties.ref
                        AND item_properties.visibility=1
                        AND item_properties.tool='".TOOL_FORUM_THREAD."' AND posts.c_id = $course_id
                        GROUP BY threads.forum_id";

        //-------------- Course Admin  -----------------//
        if (is_allowed_to_edit()) {
            // Select all the forum information of all forums (that are not deleted).
            $sql = "SELECT * FROM ".$table_forums." forum , ".$table_item_property." item_properties
                            WHERE forum.forum_id=item_properties.ref
                            AND item_properties.visibility<>2
                            AND item_properties.tool='".TOOL_FORUM."'
                            $condition_session AND forum.c_id = $course_id
                            ORDER BY forum_order ASC";
            //echo $sql.'<hr />';
            // Select the number of threads of the forums (only the threads that are not deleted).
            $sql2 = "SELECT count(*) AS number_of_threads, threads.forum_id FROM $table_threads threads, ".$table_item_property." item_properties
                            WHERE threads.thread_id=item_properties.ref
                            AND item_properties.visibility<>2
                            AND item_properties.tool='".TOOL_FORUM_THREAD."' AND threads.c_id = $course_id
                            GROUP BY threads.forum_id";
            //echo $sql2.'<hr />';
            // Select the number of posts of the forum.
            $sql3 = "SELECT count(*) AS number_of_posts, posts.forum_id FROM $table_posts posts, $table_threads threads, ".$table_item_property." item_properties
                            WHERE posts.thread_id=threads.thread_id
                            AND threads.thread_id=item_properties.ref
                            AND item_properties.visibility=1
                            AND item_properties.tool='".TOOL_FORUM_THREAD."' AND posts.c_id = $course_id
                            GROUP BY threads.forum_id";
            //echo $sql3.'<hr />';
        }
    }
    
    // GETTING ONE SPECIFIC FORUM 
    
    // We could do the splitup into student and course admin also but we want to have as much as information about a certain forum as possible
    // so we do not take too much information into account. This function (or this section of the function) is namely used to fill the forms
    // when editing a forum (and for the moment it is the only place where we use this part of the function)
        else {
            // Select all the forum information of the given forum (that is not deleted).
            $sql = "SELECT * FROM $table_forums forum , ".$table_item_property." item_properties
                                WHERE forum.forum_id=item_properties.ref
                                AND forum_id='".Database::escape_string($id)."'
                                AND item_properties.visibility<>2
                                AND item_properties.tool='".TOOL_FORUM."'
                                $condition_session  AND forum.c_id = $course_id
                                ORDER BY forum_order ASC";

            // Select the number of threads of the forum.
            $sql2 = "SELECT count(*) AS number_of_threads, forum_id FROM $table_threads
						WHERE forum_id=".Database::escape_string($id)." AND c_id = $course_id
                    	GROUP BY forum_id";

            // Select the number of posts of the forum.
            $sql3 = "SELECT count(*) AS number_of_posts, forum_id FROM $table_posts
                                WHERE forum_id=".Database::escape_string($id)."  AND c_id = $course_id
                                GROUP BY forum_id";

            // Select the last post and the poster (note: this is probably no longer needed).
            $sql4 = "SELECT  post.post_id, post.forum_id, post.poster_id, post.poster_name, post.post_date, users.lastname, users.firstname
                                FROM $table_posts post, $table_users users
                                WHERE forum_id=".Database::escape_string($id)."
                                AND post.poster_id=users.user_id  AND post.c_id = $course_id
                                GROUP BY post.forum_id
                                ORDER BY post.post_id ASC";
        }

    // Handling all the forum information.
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        if ($id == '') {
            $forum_list[$row['forum_id']] = $row;
        } else {
            $forum_list = $row;
        }
    }

    // Handling the threadcount information.
    $result2 = Database::query($sql2);
    while ($row2=Database::fetch_array($result2)) {
        if ($id == '') {
            $forum_list[$row2['forum_id']]['number_of_threads'] = $row2['number_of_threads'];
        } else {
            $forum_list['number_of_threads'] = $row2['number_of_threads'];
        }
    }

    // Handling the postcount information.
    $result3 = Database::query($sql3);
    while ($row3 = Database::fetch_array($result3)) {
        if ($id == '') {
            if (array_key_exists($row3['forum_id'], $forum_list)) { // This is needed because sql3 takes also the deleted forums into account.
                $forum_list[$row3['forum_id']]['number_of_posts'] = $row3['number_of_posts'];
            }
        } else {
            $forum_list['number_of_posts'] = $row3['number_of_posts'];
        }
    }

    // Finding the last post information (last_post_id, last_poster_id, last_post_date, last_poster_name, last_poster_lastname, last_poster_firstname).
    if ($id == '') {
        if (is_array($forum_list)) {
            foreach ($forum_list as $key => $value) {
                $last_post_info_of_forum = get_last_post_information($key, api_is_allowed_to_edit(), $course_id);
                $forum_list[$key]['last_post_id'] = $last_post_info_of_forum['last_post_id'];
                $forum_list[$key]['last_poster_id'] = $last_post_info_of_forum['last_poster_id'];
                $forum_list[$key]['last_post_date'] = $last_post_info_of_forum['last_post_date'];
                $forum_list[$key]['last_poster_name'] = $last_post_info_of_forum['last_poster_name'];
                $forum_list[$key]['last_poster_lastname'] = $last_post_info_of_forum['last_poster_lastname'];
                $forum_list[$key]['last_poster_firstname'] = $last_post_info_of_forum['last_poster_firstname'];
            }
        } else {
            $forum_list = array();
        }
    } else {
        $last_post_info_of_forum = get_last_post_information($id, api_is_allowed_to_edit(), $course_id);
        $forum_list['last_post_id'] = $last_post_info_of_forum['last_post_id'];
        $forum_list['last_poster_id'] = $last_post_info_of_forum['last_poster_id'];
        $forum_list['last_post_date'] = $last_post_info_of_forum['last_post_date'];
        $forum_list['last_poster_name'] = $last_post_info_of_forum['last_poster_name'];
        $forum_list['last_poster_lastname'] = $last_post_info_of_forum['last_poster_lastname'];
        $forum_list['last_poster_firstname'] = $last_post_info_of_forum['last_poster_firstname'];
    }
    return $forum_list;
}

/**
 * This function gets all the last post information of a certain forum
 *
 * @param int 	$forum_id the id of the forum we want to know the last post information of.
 * @param bool 	$show_invisibles
 * @param string course db name
 * @return array containing all the information about the last post (last_post_id, last_poster_id, last_post_date, last_poster_name, last_poster_lastname, last_poster_firstname)
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function get_last_post_information($forum_id, $show_invisibles = false, $course_id = null) {
    if (!isset($course_id)) {
        $course_id = api_get_course_int_id();
    } else {
        $course_id = intval($course_id);
    }

    $table_forums 			= Database :: get_course_table(TABLE_FORUM);
    $table_threads 			= Database :: get_course_table(TABLE_FORUM_THREAD);
    $table_posts 			= Database :: get_course_table(TABLE_FORUM_POST);
    $table_item_property 	= Database :: get_course_table(TABLE_ITEM_PROPERTY);
    $table_users 			= Database :: get_main_table(TABLE_MAIN_USER);

    $sql = "SELECT post.post_id, post.forum_id, post.poster_id, post.poster_name, post.post_date, users.lastname, users.firstname, post.visible, thread_properties.visibility AS thread_visibility, forum_properties.visibility AS forum_visibility
                FROM $table_posts post, $table_users users, $table_item_property thread_properties,  $table_item_property forum_properties
                WHERE post.forum_id=".Database::escape_string($forum_id)."
                AND post.poster_id=users.user_id
                AND post.thread_id=thread_properties.ref
                AND thread_properties.tool='".TOOL_FORUM_THREAD."'
                AND post.forum_id=forum_properties.ref
                AND forum_properties.tool='".TOOL_FORUM."'
                AND post.c_id = $course_id AND 
                thread_properties.c_id = $course_id AND
                forum_properties.c_id = $course_id 
                ORDER BY post.post_id DESC";
    $result = Database::query($sql);

    if ($show_invisibles) {
        $row = Database::fetch_array($result);
        $return_array['last_post_id'] = $row['post_id'];
        $return_array['last_poster_id'] = $row['poster_id'];
        $return_array['last_post_date'] = $row['post_date'];
        $return_array['last_poster_name'] = $row['poster_name'];
        $return_array['last_poster_lastname'] = $row['lastname'];
        $return_array['last_poster_firstname'] = $row['firstname'];
        return $return_array;
    } else {
        // We have to loop through the results to find the first one that is actually visible to students (forum_category, forum, thread AND post are visible).
        while ($row = Database::fetch_array($result)) {
            if ($row['visible'] == '1' && $row['thread_visibility'] == '1' && $row['forum_visibility'] == '1') {
                $return_array['last_post_id'] = $row['post_id'];
                $return_array['last_poster_id'] = $row['poster_id'];
                $return_array['last_post_date'] = $row['post_date'];
                $return_array['last_poster_name'] = $row['poster_name'];
                $return_array['last_poster_lastname'] = $row['lastname'];
                $return_array['last_poster_firstname'] = $row['firstname'];
                return $return_array;
            }
        }
    }
}

/**
 * Retrieve all the threads of a given forum
 *
 * @param int 	forum id
 * @param string course db name
 * @return an array containing all the information about the threads
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function get_threads($forum_id, $course_code = '') {

	$course_info = api_get_course_info($course_code);
	$course_id = $course_info['real_id'];
	
    $table_item_property 	= Database :: get_course_table(TABLE_ITEM_PROPERTY);
    $table_threads 			= Database :: get_course_table(TABLE_FORUM_THREAD);
    $table_posts 			= Database :: get_course_table(TABLE_FORUM_POST);
    $table_users 			= Database :: get_main_table(TABLE_MAIN_USER);

    $thread_list = array();
    // important note: 	it might seem a little bit awkward that we have 'thread.locked as locked' in the sql statement
    //					because we also have thread.* in it. This is because thread has a field locked and post also has the same field
    // 					since we are merging these we would have the post.locked value but in fact we want the thread.locked value
    //					This is why it is added to the end of the field selection

    $sql = "SELECT thread.*, item_properties.*, post.*, users.firstname, users.lastname, users.user_id,
                last_poster_users.firstname as last_poster_firstname , last_poster_users.lastname as last_poster_lastname, last_poster_users.user_id as last_poster_user_id, thread.locked as locked
            FROM $table_threads thread
            INNER JOIN $table_item_property item_properties
                ON thread.thread_id=item_properties.ref
                AND item_properties.visibility='1'
                AND item_properties.tool='".TABLE_FORUM_THREAD."'
            LEFT JOIN $table_users users
                ON thread.thread_poster_id=users.user_id
            LEFT JOIN $table_posts post
                ON thread.thread_last_post = post.post_id
            LEFT JOIN $table_users last_poster_users
                ON post.poster_id= last_poster_users.user_id
            WHERE
            post.c_id = $course_id AND
            item_properties.c_id = $course_id AND
            thread.c_id = $course_id AND             
            thread.forum_id='".Database::escape_string($forum_id)."'
            ORDER BY thread.thread_sticky DESC, thread.thread_date DESC";
    if (is_allowed_to_edit()) {
        // important note: 	it might seem a little bit awkward that we have 'thread.locked as locked' in the sql statement
        //					because we also have thread.* in it. This is because thread has a field locked and post also has the same field
        // 					since we are merging these we would have the post.locked value but in fact we want the thread.locked value
        //					This is why it is added to the end of the field selection
        $sql = "SELECT thread.*, item_properties.*, post.*, users.firstname, users.lastname, users.user_id,
                    last_poster_users.firstname as last_poster_firstname , last_poster_users.lastname as last_poster_lastname, last_poster_users.user_id as last_poster_user_id, thread.locked as locked
                FROM $table_threads thread
                INNER JOIN $table_item_property item_properties
                    ON thread.thread_id=item_properties.ref
                    AND item_properties.visibility<>2
                    AND item_properties.tool='".TABLE_FORUM_THREAD."'
                LEFT JOIN $table_users users
                    ON thread.thread_poster_id=users.user_id
                LEFT JOIN $table_posts post
                    ON thread.thread_last_post = post.post_id
                LEFT JOIN $table_users last_poster_users
                    ON post.poster_id= last_poster_users.user_id
                WHERE
                post.c_id = $course_id AND
            	item_properties.c_id = $course_id AND
            	thread.c_id = $course_id AND 
                thread.forum_id='".Database::escape_string($forum_id)."'
                ORDER BY thread.thread_sticky DESC, thread.thread_date DESC";
    }
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $thread_list[] = $row;
    }
    return $thread_list;
}

/**
 * Retrieve all posts of a given thread
 *
 * @return an array containing all the information about the posts of a given thread
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function get_posts($thread_id) {
    $table_users 			= Database :: get_main_table(TABLE_MAIN_USER);
    $table_posts 			= Database :: get_course_table(TABLE_FORUM_POST);
    
    $course_id = api_get_course_int_id();    

    // note: change these SQL so that only the relevant fields of the user table are used
    if (api_is_allowed_to_edit(null,true)) {
        $sql = "SELECT * FROM $table_posts posts
                LEFT JOIN  $table_users users
                    ON posts.poster_id=users.user_id
                WHERE
                c_id = $course_id AND  
                posts.thread_id='".Database::escape_string($thread_id)."'
                ORDER BY posts.post_id ASC";
    } else {
        // students can only se the posts that are approved (posts.visible='1')
        $sql = "SELECT * FROM $table_posts posts
                LEFT JOIN  $table_users users
                    ON posts.poster_id=users.user_id
                WHERE
                c_id = $course_id AND  
                posts.thread_id='".Database::escape_string($thread_id)."'
                AND posts.visible='1'
                ORDER BY posts.post_id ASC";
    }
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        $post_list[] = $row;
    }
    return $post_list;
}

//                    NEW TOPIC FUNCTIONS

/**
 * This function retrieves all the information of a post
 *
 * @param $forum_id integer that indicates the forum
 * @return array returns
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function get_post_information($post_id) {
    $table_posts 	= Database :: get_course_table(TABLE_FORUM_POST);
    $table_users 	= Database :: get_main_table(TABLE_MAIN_USER);
    
    $course_id = api_get_course_int_id();
    
    $sql = "SELECT * FROM ".$table_posts."posts, ".$table_users." users 
            WHERE c_id = $course_id AND posts.poster_id=users.user_id AND posts.post_id='".Database::escape_string($post_id)."'";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    return $row;
}

/**
 * This function retrieves all the information of a thread
 *
 * @param $forum_id integer that indicates the forum
 * @return array returns
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function get_thread_information($thread_id) {
    $table_item_property 	= Database :: get_course_table(TABLE_ITEM_PROPERTY);
    $table_threads 			= Database :: get_course_table(TABLE_FORUM_THREAD);
    $course_id = api_get_course_int_id();

    $sql = "SELECT * FROM ".$table_threads." threads, ".$table_item_property." item_properties
            WHERE 	item_properties.tool= '".TOOL_FORUM_THREAD."' AND 
            		item_properties.ref	= '".Database::escape_string($thread_id)."' AND 
    				threads.thread_id	= '".Database::escape_string($thread_id)."' AND
    				threads.c_id = $course_id 
   			";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    return $row;
}

/**
 * This function retrieves forum thread users details
 * @param 	int Thread ID
 * @param	string	Course DB name (optional)
 * @return	resource array Array of type ([user_id=>w,lastname=>x,firstname=>y,thread_id=>z],[])
 * @author Christian Fasanando <christian.fasanando@dokeos.com>,
 * @todo     this function need to be improved
 * @version octubre 2008, dokeos 1.8
 */
function get_thread_users_details($thread_id, $course_id = null) {
    $t_posts               = Database :: get_course_table(TABLE_FORUM_POST);
    $t_users               = Database :: get_main_table(TABLE_MAIN_USER);
    $t_course_user         = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
    $t_session_rel_user    = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    
    if (empty($course_id)) {
        $course_id = api_get_course_int_id();
    } else {
        $course_id = intval($course_id);
    }

    $is_western_name_order = api_is_western_name_order();
    if ($is_western_name_order) {
        $orderby = 'ORDER BY user.firstname, user.lastname ';
    } else {
        $orderby = 'ORDER BY user.lastname, user.firstname';
    }

    if (api_get_session_id()) {
        $session_info = api_get_session_info(api_get_session_id());
        $user_to_avoid = "'".$session_info['id_coach']."', '".$session_info['session_admin_id']."'";
        //not showing coaches
        $sql = "SELECT DISTINCT user.user_id, user.lastname, user.firstname, thread_id
                  FROM $t_posts , $t_users user, $t_session_rel_user session_rel_user_rel_course
                  WHERE poster_id = user.user_id
                  AND user.user_id = session_rel_user_rel_course.id_user
                  AND session_rel_user_rel_course.status<>'2'
                  AND session_rel_user_rel_course.id_user NOT IN ($user_to_avoid)
                  AND thread_id = '".Database::escape_string($thread_id)."'
                  AND id_session = '".api_get_session_id()."'
                  AND course_code = '".$course_id."' $orderby ";

    } else {
        $sql = "SELECT DISTINCT user.user_id, user.lastname, user.firstname, thread_id
                  FROM $t_posts , $t_users user, $t_course_user course_user
                  WHERE poster_id = user.user_id
                  AND user.user_id = course_user.user_id
                  AND course_user.relation_type<>".COURSE_RELATION_TYPE_RRHH."
                  AND thread_id = '".Database::escape_string($thread_id)."'
                  AND course_user.status NOT IN('1')
                  AND course_code = '".$course_id."' $orderby";
    }
    $result = Database::query($sql);
    return $result;
}

/**
 * This function retrieves forum thread users qualify
 * @param 	int Thread ID
 * @param	string	Course DB name (optional)
 * @return	array Array of type ([user_id=>w,lastname=>x,firstname=>y,thread_id=>z],[])
 * @author Jhon Hinojosa<jhon.hinojosa@dokeos.com>,
 * @todo     this function need to be improved
 * @version octubre 2008, dokeos 1.8
 */
function get_thread_users_qualify($thread_id, $course_id = null) {
    $t_posts = Database :: get_course_table(TABLE_FORUM_POST);
    $t_qualify = Database :: get_course_table(TABLE_FORUM_THREAD_QUALIFY);
    $t_users = Database :: get_main_table(TABLE_MAIN_USER);
    $t_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
    $t_session_rel_user    = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    
    if (empty($course_id)) {
        $course_id = api_get_course_int_id();
    } else {
        $course_id = intval($course_id);
    }    

    $is_western_name_order = api_is_western_name_order();
    if ($is_western_name_order) {
        $orderby = 'ORDER BY user.firstname, user.lastname ';
    } else {
        $orderby = 'ORDER BY user.lastname, user.firstname';
    }

    if (api_get_session_id()) {
        $session_info = api_get_session_info(api_get_session_id());
        $user_to_avoid = "'".$session_info['id_coach']."', '".$session_info['session_admin_id']."'";
        //not showing coaches
        $sql = "SELECT DISTINCT post.poster_id, user.lastname, user.firstname, post.thread_id,user.user_id,qualify.qualify
                  FROM $t_posts post , $t_users user, $t_session_rel_user session_rel_user_rel_course, $t_qualify qualify
                  WHERE poster_id = user.user_id
                  AND post.poster_id = qualify.user_id
                  AND user.user_id = session_rel_user_rel_course.id_user
                  AND session_rel_user_rel_course.status<>'2'
                  AND session_rel_user_rel_course.id_user NOT IN ($user_to_avoid)
                  AND qualify.thread_id = '".Database::escape_string($thread_id)."
                  AND thread_id = '".Database::escape_string($thread_id)."'
                  AND id_session = '".api_get_session_id()."'
                  AND course_code = '".$course_id."'
                  $orderby ";
    } else {
          $sql = "SELECT DISTINCT post.poster_id, user.lastname, user.firstname, post.thread_id,user.user_id,qualify.qualify
                                FROM $t_posts post,
                                     $t_qualify qualify,
                                     $t_users user,
                                     $t_course_user course_user
                                WHERE
                                     post.poster_id = user.user_id
                                     AND post.poster_id = qualify.user_id
                                     AND user.user_id = course_user.user_id
                                     AND course_user.relation_type<>".COURSE_RELATION_TYPE_RRHH."
                                     AND qualify.thread_id = '".Database::escape_string($thread_id)."'
                                     AND post.thread_id = '".Database::escape_string($thread_id)."'
                                     AND course_user.status not in('1')
                                     AND course_code = '".$course_id."'
                                     $orderby ";
    }
    $result = Database::query($sql);
    return $result; 
}
 
/**
 * This function retrieves forum thread users not qualify
 * @param 	int Thread ID
 * @param	string	Course DB name (optional)
 * @return	array Array of type ([user_id=>w,lastname=>x,firstname=>y,thread_id=>z],[])
 * @author   Jhon Hinojosa<jhon.hinojosa@dokeos.com>,
 * @todo     i'm a horrible function fix me
 * @version octubre 2008, dokeos 1.8
 */
function get_thread_users_not_qualify($thread_id, $course_id = null) {
    $t_posts = Database :: get_course_table(TABLE_FORUM_POST);
    $t_qualify = Database :: get_course_table(TABLE_FORUM_THREAD_QUALIFY);
    $t_users = Database :: get_main_table(TABLE_MAIN_USER);
    $t_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
    $t_session_rel_user    = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

    $is_western_name_order = api_is_western_name_order();
    if ($is_western_name_order) {
        $orderby = 'ORDER BY user.firstname, user.lastname ';
    } else {
        $orderby = 'ORDER BY user.lastname, user.firstname';
    }

    if (empty($course_id)) {
        $course_id = api_get_course_int_id();
    } else {
        $course_id = intval($course_id);
    }    



    $sql1 = "select user_id FROM  $t_qualify WHERE thread_id = '".$thread_id."'";
    $result1 = Database::query($sql1);
    $cad = '';
    while ($row = Database::fetch_array($result1)) {
        $cad .= $row['user_id'].',';
    }
    if ($cad == '') {
        $cad = '0';
    } else  {
        $cad = substr($cad, 0, strlen($cad) - 1);
    }

    if (api_get_session_id()) {
        $session_info = api_get_session_info(api_get_session_id());
        $user_to_avoid = "'".$session_info['id_coach']."', '".$session_info['session_admin_id']."'";
        //not showing coaches
        $sql = "SELECT DISTINCT user.user_id, user.lastname, user.firstname, post.thread_id
                  FROM $t_posts post , $t_users user, $t_session_rel_user session_rel_user_rel_course
                  WHERE poster_id = user.user_id
                  AND user.user_id NOT IN (".$cad.")
                  AND user.user_id = session_rel_user_rel_course.id_user
                  AND session_rel_user_rel_course.status<>'2'
                  AND session_rel_user_rel_course.id_user NOT IN ($user_to_avoid)
                  AND post.thread_id = '".Database::escape_string($thread_id)."'
                  AND id_session = '".api_get_session_id()."'
                  AND course_code = '".$course_id."' $orderby ";
    } else {
        $sql = "SELECT DISTINCT user.user_id, user.lastname, user.firstname, post.thread_id
                  FROM $t_posts post, $t_users user,$t_course_user course_user
                  WHERE post.poster_id = user.user_id
                  AND user.user_id NOT IN (".$cad.")
                  AND user.user_id = course_user.user_id
                  AND course_user.relation_type<>".COURSE_RELATION_TYPE_RRHH."
                  AND post.thread_id = '".Database::escape_string($thread_id)."'
                  AND course_user.status not in('1')
                  AND course_code = '".$course_id."' $orderby";
    }
    $result = Database::query($sql);
    return $result;
}


/**
 * This function retrieves all the information of a given forum_id
 *
 * @param $forum_id integer that indicates the forum
 * @return array returns
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 *
 * @deprecated this functionality is now moved to get_forums($forum_id)
 */
function get_forum_information($forum_id) {
    $table_forums 			= Database :: get_course_table(TABLE_FORUM);
    $table_item_property 	= Database :: get_course_table(TABLE_ITEM_PROPERTY);

    $sql = "SELECT * FROM ".$table_forums." forums, ".$table_item_property." item_properties
            WHERE 	item_properties.tool	= '".TOOL_FORUM."' AND 
            		item_properties.ref		= '".Database::escape_string($forum_id)."' AND 
    				forums.forum_id			= '".Database::escape_string($forum_id)."' AND
    				forums.c_id = ".api_get_course_int_id()."
    				
   			";
    
    $result = Database::query($sql);
    $row 	= Database::fetch_array($result);
    $row['approval_direct_post'] = 0; // We can't anymore change this option, so it should always be activated.
    return $row;
}

/**
 * This function retrieves all the information of a given forumcategory id
 *
 * @param $forum_id integer that indicates the forum
 * @return array returns if there are category or bool returns if there aren't category
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function get_forumcategory_information($cat_id) {
    $table_categories 		= Database :: get_course_table(TABLE_FORUM_CATEGORY);
    $table_item_property 	= Database :: get_course_table(TABLE_ITEM_PROPERTY);
    
	$course_id = api_get_course_int_id();
    $sql = "SELECT * FROM ".$table_categories." forumcategories, ".$table_item_property." item_properties
            WHERE 	forumcategories.c_id = $course_id AND
            		item_properties.c_id = $course_id AND
            		item_properties.tool='".TOOL_FORUM_CATEGORY."' AND 
            		item_properties.ref='".Database::escape_string($cat_id)."' AND 
    				forumcategories.cat_id='".Database::escape_string($cat_id)."'";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    return $row;
}

/**
 * This function counts the number of forums inside a given category
 *
 * @param $cat_id the id of the forum category
 * @todo an additional parameter that takes the visibility into account. For instance $countinvisible=0 would return the number
 * 		of visible forums, $countinvisible=1 would return the number of visible and invisible forums
 * @return int the number of forums inside the given category
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function count_number_of_forums_in_category($cat_id) {
    $table_forums 	= Database :: get_course_table(TABLE_FORUM);
    $course_id = api_get_course_int_id();
    $sql = "SELECT count(*) AS number_of_forums FROM ".$table_forums." WHERE c_id = $course_id AND forum_category='".Database::escape_string($cat_id)."'";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    return $row['number_of_forums'];
}

/**
 * This function stores a new thread. This is done through an entry in the forum_thread table AND
 * in the forum_post table because. The threads are also stored in the item_property table. (forum posts are not (yet))
 *
 * @param array
 * @return void HTML
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function store_thread($values) {
    global $_user;
    global $_course;
    global $current_forum;
    global $origin;

    $forum_table_attachment = Database :: get_course_table(TABLE_FORUM_ATTACHMENT);
    $table_threads 			= Database :: get_course_table(TABLE_FORUM_THREAD);
    $table_posts 			= Database :: get_course_table(TABLE_FORUM_POST);
    
    $course_id = api_get_course_int_id();    

    $gradebook = Security::remove_XSS($_GET['gradebook']);
    $upload_ok = 1;
    $has_attachment = false;

    if (!empty($_FILES['user_upload']['name'])) {
        $upload_ok = process_uploaded_file($_FILES['user_upload']);
        $has_attachment = true;
    }
    if ($upload_ok) {

        $post_date = api_get_utc_datetime();

        if ($current_forum['approval_direct_post'] == '1' && !api_is_allowed_to_edit(null, true)) {
            $visible = 0; // The post has not been approved yet.
        } else {
            $visible = 1;
        }

        $clean_post_title = Database::escape_string(stripslashes($values['post_title']));

        // We first store an entry in the forum_thread table because the thread_id is used in the forum_post table.
        $sql = "INSERT INTO $table_threads (c_id, thread_title, forum_id, thread_poster_id, thread_poster_name, thread_date, thread_sticky,thread_title_qualify,thread_qualify_max,thread_weight,session_id)
                VALUES (
                		".$course_id.",
                		'".$clean_post_title."',
                        '".Database::escape_string($values['forum_id'])."',
                        '".Database::escape_string($_user['user_id'])."',
                        '".Database::escape_string(stripslashes(isset($values['poster_name'])?$values['poster_name']:null))."',
                        '".Database::escape_string($post_date)."',
                        '".Database::escape_string(isset($values['thread_sticky'])?$values['thread_sticky']:null)."'," .
                        "'".Database::escape_string(stripslashes($values['calification_notebook_title']))."'," .
                        "'".Database::escape_string($values['numeric_calification'])."'," .
                        "'".Database::escape_string($values['weight_calification'])."'," .
                        "'".api_get_session_id()."')";
        $result = Database::query($sql);
        $last_thread_id = Database::insert_id();

        // Add option gradebook qualify.

        if (isset($values['thread_qualify_gradebook']) && 1 == $values['thread_qualify_gradebook']) {
            // Add function gradebook.
            $coursecode = api_get_course_id();
            $resourcetype = 5;
            $resourceid = $last_thread_id;
            $resourcename = stripslashes($values['calification_notebook_title']);
            $maxqualify = $values['numeric_calification'];
            $weigthqualify = $values['weight_calification'];
            $resourcedescription = '';
            $date = time();
            //is_resource_in_course_gradebook($course_code, $resource_type, $resource_id);
            add_resource_to_course_gradebook($coursecode, $resourcetype, $resourceid, $resourcename, $weigthqualify, $maxqualify, $resourcedescription, $date, 0, api_get_session_id());
        }

        api_item_property_update($_course, TOOL_FORUM_THREAD, $last_thread_id, 'ForumThreadAdded', api_get_user_id());
        // If the forum properties tell that the posts have to be approved we have to put the whole thread invisible,
        // because otherwise the students will see the thread and not the post in the thread.
        // We also have to change $visible because the post itself has to be visible in this case (otherwise the teacher would have
        // to make the thread visible AND the post.

        if ($visible == 0) {
            api_item_property_update($_course, TOOL_FORUM_THREAD, $last_thread_id, 'invisible', api_get_user_id());
            $visible = 1;
        }
        // We now store the content in the table_post table.
        $sql = "INSERT INTO $table_posts (c_id, post_title, post_text, thread_id, forum_id, poster_id, poster_name, post_date, post_notification, post_parent_id, visible)
                VALUES (
                ".$course_id.",
                '".$clean_post_title."',
                '".Database::escape_string($values['post_text'])."',
                '".Database::escape_string($last_thread_id)."',
                '".Database::escape_string($values['forum_id'])."',
                '".Database::escape_string($_user['user_id'])."',
                '".Database::escape_string(stripslashes(isset($values['poster_name']) ? $values['poster_name'] : null))."',
                '".Database::escape_string($post_date)."',
                '".Database::escape_string(isset($values['post_notification']) ? $values['post_notification'] : null)."','0',
                '".Database::escape_string($visible)."')";
        Database::query($sql);
        $last_post_id = Database::insert_id();

        // Now we have to update the thread table to fill the thread_last_post field (so that we know when the thread has been updated for the last time).
        $sql = "UPDATE $table_threads SET thread_last_post='".Database::escape_string($last_post_id)."'  WHERE c_id = $course_id AND thread_id='".Database::escape_string($last_thread_id)."'";
        $result = Database::query($sql);
        $message = get_lang('NewThreadStored');
        // Storing the attachments if any.
        if ($has_attachment) {
            $course_dir = $_course['path'].'/upload/forum';
            $sys_course_path = api_get_path(SYS_COURSE_PATH);
            $updir = $sys_course_path.$course_dir;

            // Try to add an extension to the file if it hasn't one.
            $new_file_name = add_ext_on_mime(stripslashes($_FILES['user_upload']['name']), $_FILES['user_upload']['type']);

            // User's file name
            $file_name = $_FILES['user_upload']['name'];

            if (!filter_extension($new_file_name)) {
                Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
            } else {
                if ($result) {
                    $comment = Database::escape_string($comment);
                    add_forum_attachment_file($comment,$last_post_id);
                }
            }
        } else {
            $message .= '<br />';
        }

        if ($current_forum['approval_direct_post'] == '1' && !api_is_allowed_to_edit(null, true)) {
            $message .= get_lang('MessageHasToBeApproved').'<br />';
            $message .= get_lang('ReturnTo').' <a href="viewforum.php?'.api_get_cidreq().'&amp;forum='.$values['forum_id'].'&amp;gidReq='.$_SESSION['toolgroup'].'&amp;origin='.$origin.'">'.get_lang('Forum').'</a><br />';
        } else {
            $message .= get_lang('ReturnTo').' <a href="viewforum.php?'.api_get_cidreq().'&amp;forum='.$values['forum_id'].'&amp;gidReq='.$_SESSION['toolgroup'].'&amp;origin='.$origin.'">'.get_lang('Forum').'</a><br />';
            $message .= get_lang('ReturnTo').' <a href="viewthread.php?'.api_get_cidreq().'&amp;forum='.$values['forum_id'].'&amp;gidReq='.$_SESSION['toolgroup'].'&amp;origin='.$origin.'&amp;gradebook='.$gradebook.'&amp;thread='.$last_thread_id.'">'.get_lang('Message').'</a>';
        }
        $reply_info['new_post_id'] = $last_post_id;
        $my_post_notification = isset($values['post_notification']) ? $values['post_notification'] : null;
        if ($my_post_notification == 1) {
            set_notification('thread', $last_thread_id, true);
        }

        send_notification_mails($last_thread_id, $reply_info);

        session_unregister('formelements');
        session_unregister('origin');
        session_unregister('breadcrumbs');
        session_unregister('addedresource');
        session_unregister('addedresourceid');

        Display :: display_confirmation_message($message, false);
    } else {
        Display::display_error_message(get_lang('UplNoFileUploaded'));
    }
}

/**
 * This function displays the form that is used to add a post. This can be a new thread or a reply.
 * @param $action is the parameter that determines if we are
 *					1. newthread: adding a new thread (both empty) => No I-frame
 *					2. replythread: Replying to a thread ($action = replythread) => I-frame with the complete thread (if enabled)
 *					3. replymessage: Replying to a message ($action =replymessage) => I-frame with the complete thread (if enabled) (I first thought to put and I-frame with the message only)
 * 					4. quote: Quoting a message ($action= quotemessage) => I-frame with the complete thread (if enabled). The message will be in the reply. (I first thought not to put an I-frame here)
 * @return void HMTL
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function show_add_post_form($action = '', $id = '', $form_values = '') {
    global $forum_setting;
    global $current_forum;
    global $_user;
    global $origin;
    global $charset;

    $gradebook = Security::remove_XSS($_GET['gradebook']);
    // Setting the class and text of the form title and submit button.
    if ($_GET['action'] == 'quote') {
        $class = 'save';
        $text = get_lang('QuoteMessage');
    } elseif ($_GET['action'] == 'replythread') {
        $class = 'save';
        $text = get_lang('ReplyToThread');
    } elseif ($_GET['action'] == 'replymessage') {
        $class = 'save';
        $text = get_lang('ReplyToMessage');
    }else {
        $class = 'add';
        $text = get_lang('CreateThread');
    }

    // Initialize the object.
    $my_thread  = isset($_GET['thread']) ? $_GET['thread'] : '';
    $my_forum   = isset($_GET['forum'])  ? $_GET['forum'] : '';
    $my_action  = isset($_GET['action']) ? $_GET['action'] : '';
    $my_post    = isset($_GET['post'])   ? $_GET['post'] : '';
    $my_gradebook = isset($_GET['gradebook']) ? Security::remove_XSS($_GET['gradebook']) : '';
    $form = new FormValidator('thread', 'post', api_get_self().'?forum='.Security::remove_XSS($my_forum).'&gradebook='.$gradebook.'&thread='.Security::remove_XSS($my_thread).'&post='.Security::remove_XSS($my_post).'&action='.Security::remove_XSS($my_action).'&origin='.$origin);
    $form->setConstants(array('forum' => '5'));

    $form->addElement('header', '', $text);

    // Settting the form elements.
    $form->addElement('hidden', 'forum_id', strval(intval($my_forum)));
    $form->addElement('hidden', 'thread_id', strval(intval($my_thread)));
    $form->addElement('hidden', 'gradebook', $my_gradebook);

    // If anonymous posts are allowed we also display a form to allow the user to put his name or username in.
    if ($current_forum['allow_anonymous'] == 1 && !isset($_user['user_id'])) {
        $form->addElement('text', 'poster_name', get_lang('Name'));
        $form->applyFilter('poster_name', 'html_filter');
    }

    $form->addElement('text', 'post_title', get_lang('Title'),'class="input_titles"');
    //$form->applyFilter('post_title', 'html_filter');
    $form->addElement('html_editor', 'post_text', get_lang('Text'), null,
        api_is_allowed_to_edit(null, true)
            ? array('ToolbarSet' => 'Forum', 'Width' => '100%', 'Height' => '300')
            : array('ToolbarSet' => 'ForumStudent', 'Width' => '100%', 'Height' => '300', 'UserStatus' => 'student')
    );
    //$form->applyFilter('post_text', 'html_filter');

    $form->addElement('html', '<div class="row"><div class="label"></div><div class="formw">');
    $form->addElement('html', '<a href="javascript://" onclick="return advanced_parameters()">
    						  <span id="img_plus_and_minus">&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).' '.get_lang('AdvancedParameters').'</span></a></div></div>');
    
    $form->addElement('html', '<div id="id_qualify" style="display:none">');

    if( (api_is_course_admin() || api_is_course_coach() || api_is_course_tutor()) && !($my_thread) ) {

        // Thread qualify        
        $form->applyFilter('numeric_calification', 'html_filter');
        $form->addElement('checkbox', 'thread_qualify_gradebook', '', get_lang('QualifyThreadGradebook'), 'onclick="javascript:if(this.checked==true){document.getElementById(\'options_field\').style.display = \'block\';}else{document.getElementById(\'options_field\').style.display = \'none\';}"');
        
        $form -> addElement('html', '<div id="options_field" style="display:none">');
        $form->addElement('text', 'numeric_calification', get_lang('QualificationNumeric'),'Style="width:40px"');
        $form->addElement('text', 'calification_notebook_title', get_lang('TitleColumnGradebook'));
        $form->applyFilter('calification_notebook_title', 'html_filter');
        $form->addElement('text', 'weight_calification', get_lang('QualifyWeight'),'value="0.00" Style="width:40px" onfocus="javascript: this.select();"');
        $form->applyFilter('weight_calification', 'html_filter');
        $form->addElement('html', '</div>');
    }

    if ($forum_setting['allow_post_notificiation'] && isset($_user['user_id'])) {
        $form->addElement('checkbox', 'post_notification', '', get_lang('NotifyByEmail').' ('.$_user['mail'].')');
    }

    if ($forum_setting['allow_sticky'] && api_is_allowed_to_edit(null, true) && $action == 'newthread') {
        $form->addElement('checkbox', 'thread_sticky', '', get_lang('StickyPost'));
    }

    if ($current_forum['allow_attachments'] == '1' || api_is_allowed_to_edit(null, true)) {
        //$form->add_resource_button();
        $values = $form->exportValues();
    }

    // User upload
    $form->addElement('html', '<b><div class="row"><div class="label">'.get_lang('AddAnAttachment').'</div></div></b><br /><br />');
    $form->addElement('file', 'user_upload',get_lang('FileName'),'');
    $form->addElement('textarea', 'file_comment', get_lang('FileComment'), array ('rows' => 4, 'cols' => 34));
    $form->applyFilter('file_comment', 'html_filter');
    $form->addElement('html', '</div>');
    $userid  = api_get_user_id();
    $info    = api_get_user_info($userid);
    $courseid = api_get_course_id();

    $form->addElement('style_submit_button', 'SubmitPost', $text, 'class="'.$class.'"');
    $form->add_real_progress_bar('DocumentUpload', 'user_upload');

    if (!empty($form_values)) {
        $defaults['post_title'] = prepare4display($form_values['post_title']);
        $defaults['post_text'] = prepare4display($form_values['post_text']);
        $defaults['post_notification'] = strval(intval($form_values['post_notification']));
        $defaults['thread_sticky'] = strval(intval($form_values['thread_sticky']));
    }

    // If we are quoting a message we have to retrieve the information of the post we are quoting so that
    // we can add this as default to the textarea.
    if (($action == 'quote' || $action == 'replymessage') && isset($my_post)) {
        // We also need to put the parent_id of the post in a hidden form when we are quoting or replying to a message (<> reply to a thread !!!)
        $form->addElement('hidden', 'post_parent_id', strval(intval($my_post))); // Note: This has to be cleaned first.

        // If we are replying or are quoting then we display a default title.
         $values = get_post_information($my_post); // Note: This has to be cleaned first.
        $defaults['post_title'] = get_lang('ReplyShort').api_html_entity_decode($values['post_title'], ENT_QUOTES);
        // When we are quoting a message then we have to put that message into the wysiwyg editor.
        // Note: The style has to be hardcoded here because using class="quote" didn't work.
        if ($action == 'quote') {
            $defaults['post_text'] = '<div>&nbsp;</div><div style="margin: 5px;"><div style="font-size: 90%; font-style: italic;">'.get_lang('Quoting').' '.api_get_person_name($values['firstname'], $values['lastname']).':</div><div style="color: #006600; font-size: 90%;	font-style: italic; background-color: #FAFAFA; border: #D1D7DC 1px solid; padding: 3px;">'.prepare4display($values['post_text']).'</div></div><div>&nbsp;</div><div>&nbsp;</div>';
        }
    }
    $form->setDefaults(isset($defaults) ? $defaults : null);

    // The course admin can make a thread sticky (=appears with special icon and always on top).
    $form->addRule('post_title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
    if ($current_forum['allow_anonymous'] == 1 && !isset($_user['user_id'])) {
        $form->addRule('poster_name', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
    }

    // Validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();
            if ($values['thread_qualify_gradebook'] == '1' && empty($values['weight_calification'])) {
                Display::display_error_message(get_lang('YouMustAssignWeightOfQualification').'&nbsp;<a href="javascript:window.back()">'.get_lang('Back').'</a>',false);
                return false;
            }
            Security::clear_token();
            return $values;
        }

    } else {
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(array('sec_token' => $token));
        $form->display();
        echo '<br />';
        if ($forum_setting['show_thread_iframe_on_reply'] && $action != 'newthread') {
            echo '<div class="row">
                    <div class="label">'.get_lang('Thread').'
                    </div>
                    <div class="formw">';
            echo "<iframe style=\"border: 1px solid black\" src=\"iframe_thread.php?forum=".Security::remove_XSS($my_forum)."&amp;thread=".Security::remove_XSS($my_thread)."#".Security::remove_XSS($my_post)."\" width=\"100%\"></iframe>";
            echo '	</div>
                </div>';
        }
    }
}

/**
 * @param integer contains the information of user id
 * @param integer contains the information of thread id
 * @param integer contains the information of thread qualify
 * @param integer contains the information of user id of qualifier
 * @param integer contains the information of time
 * @param integer contains the information of session id
 * @return Array() optional
 * @author Isaac Flores <isaac.flores@dokeos.com>, U.N.A.S University
 * @version October 2008, dokeos  1.8.6
 */
function store_theme_qualify($user_id, $thread_id, $thread_qualify = 0, $qualify_user_id = 0, $qualify_time, $session_id = null) {
    $table_threads_qualify = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY);
    $table_threads		   = Database::get_course_table(TABLE_FORUM_THREAD);
    
    $course_id = api_get_course_int_id();

    if ($user_id == strval(intval($user_id)) && $thread_id == strval(intval($thread_id)) && $thread_qualify == strval(floatval($thread_qualify))) {
        // Testing
        $sql_string = "SELECT thread_qualify_max FROM ". $table_threads ." WHERE c_id = $course_id AND thread_id=".$thread_id.";";
        $res_string = Database::query($sql_string);
        $row_string = Database::fetch_array($res_string);
        if ($thread_qualify <= $row_string[0]) {

            $sql1 = "SELECT COUNT(*) FROM ".$table_threads_qualify." WHERE c_id = $course_id AND user_id=".$user_id." and thread_id=".$thread_id.";";
            $res1 = Database::query($sql1);
            $row = Database::fetch_array($res1);

            if ($row[0] == 0) {
                $sql = "INSERT INTO $table_threads_qualify (c_id, user_id, thread_id,qualify,qualify_user_id,qualify_time,session_id) 
				VALUES (".$course_id.", '".$user_id."','".$thread_id."',".(float)$thread_qualify.", '".$qualify_user_id."','".$qualify_time."','".$session_id."')";
                $res = Database::query($sql);
                return $res;
            } else {
                $sql1 = "SELECT qualify FROM ".$table_threads_qualify." WHERE c_id = $course_id AND user_id=".$user_id." and thread_id=".$thread_id.";";
                $rs = Database::query($sql1);
                $row = Database::fetch_array($rs);
                $row[1] = "update";
                return $row;
            }
        } else {
            return null;
        }
    }
}

/**
 * This function shows qualify.
 * @param string contains the information of option to run
 * @param string contains the information the current course id
 * @param integer contains the information the current forum id
 * @param integer contains the information the current user id
 * @param integer contains the information the current thread id
 * @return integer qualify
 * <code> $option=1 obtained the qualification of the current thread</code>
 * @author Isaac Flores <isaac.flores@dokeos.com>, U.N.A.S University
 * @version October 2008, dokeos  1.8.6
 */
function show_qualify($option, $couser_id, $forum_id, $user_id, $thread_id) {
    $table_threads_qualify = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY);
    $table_threads		   = Database::get_course_table(TABLE_FORUM_THREAD);
    
    $course_id = api_get_course_int_id();    

    if ($user_id == strval(intval($user_id)) && $thread_id == strval(intval($thread_id)) && $option == 1) {

        $sql = "SELECT qualify FROM ".$table_threads_qualify." WHERE c_id = $course_id AND user_id=".$user_id." and thread_id=".$thread_id.";";
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs);
        return $row[0];
    }

    if ($user_id == strval(intval($user_id)) && $option == 2) {

        $sql = "SELECT thread_qualify_max FROM ".$table_threads." WHERE c_id = $course_id AND thread_id=".$thread_id.";";
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs);
        return $row[0];
    }
}

/**
 * This function gets qualify historical.
 * @param integer contains the information the current user id
 * @param integer contains the information the current thread id
 * @param boolean contains the information of option to run
 * @return array()
 * @author Christian Fasanando <christian.fasanando@dokeos.com>,
 * @author Isaac Flores <isaac.flores@dokeos.com>,
 * @version October 2008, dokeos  1.8.6
 */
function get_historical_qualify($user_id, $thread_id, $opt) {
    $table_threads_qualify_log = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY_LOG);
    
    $course_id = api_get_course_int_id();

    $my_qualify_log = array();
    $opt = Database::escape_string($opt);
    if ($opt == 'false') {
        $sql = "SELECT * FROM ".$table_threads_qualify_log." WHERE c_id = $course_id AND thread_id='".Database::escape_string($thread_id)."' and user_id='".Database::escape_string($user_id)."' ORDER BY qualify_time";
    } else {
        $sql = "SELECT * FROM ".$table_threads_qualify_log." WHERE c_id = $course_id AND thread_id='".Database::escape_string($thread_id)."' and user_id='".Database::escape_string($user_id)."' ORDER BY qualify_time DESC";
    }
    $rs = Database::query($sql);
    while ($row = Database::fetch_array($rs, 'ASSOC')) {
        $my_qualify_log[] = $row;
    }
    return $my_qualify_log;
}

/**
 * This function stores qualify historical.
 * @param boolean contains the information of option to run
 * @param string contains the information the current course id
 * @param integer contains the information the current forum id
 * @param integer contains the information the current user id
 * @param integer contains the information the current thread id
 * @param integer contains the information the current qualify
 * @return void
 * <code>$option=1 obtained the qualification of the current thread</code>
 * @author Isaac Flores <isaac.flores@dokeos.com>, U.N.A.S University
 * @version October 2008, dokeos  1.8.6
 */
function store_qualify_historical($option, $couser_id, $forum_id, $user_id, $thread_id, $current_qualify, $qualify_user_id) {

    $table_threads_qualify = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY);
    $table_threads		   = Database::get_course_table(TABLE_FORUM_THREAD);
    $table_threads_qualify_log = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY_LOG);
    $current_date = date('Y-m-d H:i:s');
    
    $course_id = api_get_course_int_id();

    if ($user_id == strval(intval($user_id)) && $thread_id == strval(intval($thread_id)) && $option == 1) {

        // Extract information of thread_qualify.
        $sql = "SELECT qualify,qualify_time FROM ".$table_threads_qualify." WHERE c_id = $course_id AND user_id=".$user_id." and thread_id=".$thread_id.";";
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs);

        // Insert thread_historical.
        $sql1 = "INSERT INTO $table_threads_qualify_log (c_id, user_id, thread_id,qualify,qualify_user_id,qualify_time,session_id) 
 				 VALUES(".$course_id.", '".$user_id."','".$thread_id."',".(float)$row[0].", '".$qualify_user_id."','".$row[1]."','')";
        Database::query($sql1);

        // Update
        $sql2 = "UPDATE ".$table_threads_qualify." SET qualify=".$current_qualify.",qualify_time='".$current_date."' WHERE c_id = $course_id AND user_id=".$user_id." and thread_id=".$thread_id.";";
        Database::query($sql2);
    }
}

/**
 * This function shows current thread qualify .
 * @param integer contains the information the current thread id
 * @param integer contains the information the current session id
 * @return array or null if is empty
 * @author Isaac Flores <isaac.flores@dokeos.com>, U.N.A.S University
 * @version December 2008, dokeos  1.8.6
 */
function current_qualify_of_thread($thread_id, $session_id) {
    $table_threads_qualify = Database::get_course_table(TABLE_FORUM_THREAD_QUALIFY);
    
    $course_id = api_get_course_int_id();    
    
    $res = Database::query("SELECT qualify FROM $table_threads_qualify WHERE c_id = $course_id AND thread_id = $thread_id  AND session_id = $session_id");
    $row = Database::fetch_array($res, 'ASSOC');
    return $row['qualify'];
}

/**
 * This function stores a reply in the forum_post table.
 * It also updates the forum_threads table (thread_replies +1 , thread_last_post, thread_date)
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function store_reply($values) {    
    global $_course;
    global $current_forum;
    global $origin;

    $table_threads 			= Database :: get_course_table(TABLE_FORUM_THREAD);
    $forum_table_attachment = Database :: get_course_table(TABLE_FORUM_ATTACHMENT);
    $table_posts 			= Database :: get_course_table(TABLE_FORUM_POST);

    $gradebook = Security::remove_XSS($_GET['gradebook']);
    $post_date = api_get_utc_datetime();

    if ($current_forum['approval_direct_post']=='1' && !api_is_allowed_to_edit(null, true)) {
        $visible = 0; // The post has not been approved yet.
    } else {
        $visible = 1;
    }

    $upload_ok = 1;
    $has_attachment = false;
    if (!empty($_FILES['user_upload']['name'])) {
        $upload_ok = process_uploaded_file($_FILES['user_upload']);
        $has_attachment = true;
    }
    $return = array();
    
    if ($upload_ok) {
        // We first store an entry in the forum_post table.
        $sql = "INSERT INTO $table_posts (c_id, post_title, post_text, thread_id, forum_id, poster_id, post_date, post_notification, post_parent_id, visible)
                VALUES (
                		".api_get_course_int_id().",
                		'".Database::escape_string($values['post_title'])."',
                        '".Database::escape_string(isset($values['post_text']) ? ($values['post_text']) : null)."',
                        '".Database::escape_string($values['thread_id'])."',
                        '".Database::escape_string($values['forum_id'])."',
                        '".api_get_user_id()."',
                        '".$post_date."',
                        '".Database::escape_string(isset($values['post_notification'])?$values['post_notification']:null)."',
                        '".Database::escape_string(isset($values['post_parent_id'])?$values['post_parent_id']:null)."',
                        '".Database::escape_string($visible)."')";
        $result                 = Database::query($sql);
        $new_post_id            = Database::insert_id();
        $values['new_post_id']  = $new_post_id;
        $message = get_lang('ReplyAdded');

        if ($has_attachment) {
            $course_dir = $_course['path'].'/upload/forum';
            $sys_course_path = api_get_path(SYS_COURSE_PATH);
            $updir = $sys_course_path.$course_dir;

            // Try to add an extension to the file if it hasn't one.
            $new_file_name = add_ext_on_mime(stripslashes($_FILES['user_upload']['name']), $_FILES['user_upload']['type']);

            // User's file name
            $file_name = $_FILES['user_upload']['name'];

            if (!filter_extension($new_file_name)) {
            	$return['msg'] = get_lang('UplUnableToSaveFileFilteredExtension');
            	$return['type'] = 'error';            	
            } else {
                $new_file_name = uniqid('');
                $new_path = $updir.'/'.$new_file_name;
                $result = @move_uploaded_file($_FILES['user_upload']['tmp_name'], $new_path);
                $comment = $values['file_comment'];

                // Storing the attachments if any.
                if ($result) {
                    $sql = 'INSERT INTO '.$forum_table_attachment.'(c_id, filename,comment, path, post_id,size) '.
                         	"VALUES (".api_get_course_int_id().", '".Database::escape_string($file_name)."', '".Database::escape_string($comment)."', '".Database::escape_string($new_file_name)."' , '".$new_post_id."', '".intval($_FILES['user_upload']['size'])."' )";
                    $result = Database::query($sql);
                    $message .= ' / '.get_lang('FileUploadSucces');
                    $last_id = Database::insert_id();
                    api_item_property_update($_course, TOOL_FORUM_ATTACH, $last_id ,'ForumAttachmentAdded', api_get_user_id());
                }
            }
        }

        // Update the thread.
        update_thread($values['thread_id'], $new_post_id, $post_date);

        // Update the forum.
        api_item_property_update($_course, TOOL_FORUM, $values['forum_id'], 'NewMessageInForum', api_get_user_id());

        if ($current_forum['approval_direct_post'] == '1' && !api_is_allowed_to_edit(null, true)) {
            $message .= '<br />'.get_lang('MessageHasToBeApproved').'<br />';
        }
        //$message .= '<br />'.get_lang('ReturnTo').' <a href="viewforum.php?'.api_get_cidreq().'&amp;forum='.$values['forum_id'].'&amp;gidReq='.$_SESSION['toolgroup'].'&amp;origin='.$origin.'">'.get_lang('Forum').'</a><br />';
        //$message .= get_lang('ReturnTo').' <a href="viewthread.php?'.api_get_cidreq().'&amp;forum='.$values['forum_id'].'&amp;thread='.$values['thread_id'].'&amp;gidReq='.$_SESSION['toolgroup'].'&amp;origin='.$origin.'&amp;gradebook='.$gradebook.'">'.get_lang('Message').'</a>';

        // Setting the notification correctly.
        $my_post_notification = isset($values['post_notification']) ? $values['post_notification'] : null;
        if ($my_post_notification == 1) {
            set_notification('thread', $values['thread_id'], true);
        }
        send_notification_mails($values['thread_id'], $values);
        session_unregister('formelements');
        session_unregister('origin');
        session_unregister('breadcrumbs');
        session_unregister('addedresource');
        session_unregister('addedresourceid');
        $return['msg'] = $message;
        $return['type'] = 'confirmation';
        
    } else {
    	$return['msg'] = get_lang('UplNoFileUploaded').' '. get_lang('UplSelectFileFirst');
    	$return['type'] = 'error';
    }
    return $return;
}

/**
 * This function displays the form that is used to edit a post. This can be a new thread or a reply.
 * @param array contains all the information about the current post
 * @param array contains all the information about the current thread
 * @param array contains all info about the current forum (to check if attachments are allowed)
 * @param array contains the default values to fill the form
 * @return void
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function show_edit_post_form($current_post, $current_thread, $current_forum, $form_values = '', $id_attach = 0) {
    global $forum_setting;
    global $_user;
    global $origin;

    $gradebook = Security::remove_XSS($_GET['gradebook']);

    // Initialize the object.
    $form = new FormValidator('edit_post', 'post', api_get_self().'?forum='.Security::remove_XSS($_GET['forum']).'&amp;gradebook='.$gradebook.'&amp;origin='.$origin.'&amp;thread='.Security::remove_XSS($_GET['thread']).'&amp;post='.Security::remove_XSS($_GET['post']));
    $form->addElement('header', '', get_lang('EditPost'));
    // Settting the form elements.
    $form->addElement('hidden', 'post_id', $current_post['post_id']);
    $form->addElement('hidden', 'thread_id', $current_thread['thread_id']);
    $form->addElement('hidden', 'id_attach', $id_attach);
    if ($current_post['post_parent_id'] == 0) {
        $form->addElement('hidden', 'is_first_post_of_thread', '1');
    }
    $form->addElement('text', 'post_title', get_lang('Title'), 'class="input_titles"');
    $form->applyFilter('post_title', 'html_filter');
    $form->addElement('html_editor', 'post_text', get_lang('Text'), null,
        api_is_allowed_to_edit(null, true)
            ? array('ToolbarSet' => 'Forum', 'Width' => '100%', 'Height' => '400')
            : array('ToolbarSet' => 'ForumStudent', 'Width' => '100%', 'Height' => '400', 'UserStatus' => 'student')
    );
    //$form->applyFilter('post_text', 'html_filter');

    $form->addElement('html', '<div class="row"><div class="label">');
    $form->addElement('html', '<a href="javascript://" onclick="return advanced_parameters()"><span id="img_plus_and_minus">'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).''.get_lang('AdvancedParameters').'</span></a>','');
    $form->addElement('html', '</div><div class="formw"></div></div>');
    $form->addElement('html', '<div id="id_qualify" style="display:none">');

    if (!isset($_GET['edit'])) {
        $form->addElement('static', 'Group', '<strong>'.get_lang('AlterQualifyThread').'</strong>');
        $form->applyFilter('numeric_calification', 'html_filter');
        $form->addElement('checkbox', 'thread_qualify_gradebook', '', get_lang('QualifyThreadGradebook'), 'onclick="javascript: if(this.checked){document.getElementById(\'options_field\').style.display = \'block\';}else{document.getElementById(\'options_field\').style.display = \'none\';}"');
        $defaults['thread_qualify_gradebook'] = is_resource_in_course_gradebook(api_get_course_id(), 5, $_GET['thread'], api_get_session_id());

        if (!empty($defaults['thread_qualify_gradebook'])) {
            $form -> addElement('html', '<div id="options_field" style="display:block">');
        } else {
            $form -> addElement('html', '<div id="options_field" style="display:none">');
        }
        $form->addElement('text', 'numeric_calification', get_lang('QualificationNumeric'), 'value="'.$current_thread['thread_qualify_max'].'" style="width:40px"');
        $form->addElement('text', 'calification_notebook_title', get_lang('TitleColumnGradebook'), 'value="'.$current_thread['thread_title_qualify'].'"');
        $form->applyFilter('calification_notebook_title', 'html_filter');
        $form->addElement('text', 'weight_calification', get_lang('QualifyWeight'), 'value="'.$current_thread['thread_weight'].'" style="width:40px"');
        $form->applyFilter('weight_calification', 'html_filter');
        $form->addElement('html', '</div>');
        // Add gradebook.
    }

    if ($forum_setting['allow_post_notificiation']) {
        $form->addElement('checkbox', 'post_notification', '', get_lang('NotifyByEmail').' ('.$current_post['email'].')');
    }
    if ($forum_setting['allow_sticky'] && api_is_allowed_to_edit(null, true) && $current_post['post_parent_id'] == 0) { // The sticky checkbox only appears when it is the first post of a thread.
        $form->addElement('checkbox', 'thread_sticky', '', get_lang('StickyPost'));
        if ($current_thread['thread_sticky'] == 1) {
            $defaults['thread_sticky'] = true;
        }
    }

    $attachment_list = get_attachment($current_post['post_id']);
    $message = get_lang('AddAnAttachment');
    if (!empty($attachment_list)) {
        $message = get_lang('EditAnAttachment');
        $form->addElement('static', 'Group', '', '<br />'.Display::return_icon('attachment.gif', get_lang('Attachment')).'&nbsp;'.$attachment_list['filename'].(!empty($attachment_list['comment']) ? '('.$attachment_list['comment'].')' : ''));
        $form->addElement('checkbox', 'remove_attach', null, get_lang('DeleteAttachmentFile'));
    }
    // User upload
    $form->addElement('html', '<br /><b><div class="row"><div class="label">'.$message.'</div></div></b><br /><br />');
    $form->addElement('file', 'user_upload', get_lang('FileName'), '');
    $form->addElement('textarea', 'file_comment', get_lang('FileComment'), array ('rows' => 4, 'cols' => 34));
    $form->applyFilter('file_comment', 'html_filter');
    $form->addElement('html', '</div><br /><br />');
    if ($current_forum['allow_attachments'] == '1' || api_is_allowed_to_edit(null, true)) {
        if (empty($form_values) && !isset($_POST['SubmitPost'])) {
            //edit_added_resources('forum_post', $current_post['post_id']);
        }
        //$form->add_resource_button();        
    }

    $form->addElement('style_submit_button', 'SubmitPost', get_lang('ModifyThread'), 'class="save"');

    // Setting the default values for the form elements.
    $defaults['post_title'] = $current_post['post_title'];
    $defaults['post_text']  = $current_post['post_text'];
    if ($current_post['post_notification'] == 1) {
        $defaults['post_notification'] = true;
    }

    if (!empty($form_values)) {
        //$defaults['post_title']=Security::remove_XSS($form_values['post_title']);
        //$defaults['post_text']=Security::remove_XSS($form_values['post_text']);
        $defaults['post_notification'] = Security::remove_XSS($form_values['post_notification']);
        $defaults['thread_sticky'] = Security::remove_XSS($form_values['thread_sticky']);
    }

    $form->setDefaults($defaults);

    // The course admin can make a thread sticky (=appears with special icon and always on top).

    $form->addRule('post_title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');

    // Validation or display
    if ($form->validate()) {
        $values = $form->exportValues();  
        
        if ($values['thread_qualify_gradebook'] == '1' && empty($values['weight_calification'])) {
            Display::display_error_message(get_lang('YouMustAssignWeightOfQualification').'&nbsp;<a href="javascript:window.back()">'.get_lang('Back').'</a>', false);
            return false;
        }
        return $values;
    } else {
        $form->display();
    }
}

/**
 * This function stores the edit of a post in the forum_post table.
 *
 * @param array
 * @return void HTML
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function store_edit_post($values) {
    global $origin;

    $table_threads 	= Database :: get_course_table(TABLE_FORUM_THREAD);
    $table_posts 	= Database :: get_course_table(TABLE_FORUM_POST);

    $gradebook = Security::remove_XSS($_GET['gradebook']);
    
    $course_id = api_get_course_int_id();
    
    
    // First we check if the change affects the thread and if so we commit the changes (sticky and post_title=thread_title are relevant).
    //if (array_key_exists('is_first_post_of_thread',$values)  AND $values['is_first_post_of_thread']=='1') {
    $sql = "UPDATE $table_threads SET 
                thread_title            ='".Database::escape_string($values['post_title'])."',
                thread_sticky           ='".Database::escape_string(isset($values['thread_sticky']) ? $values['thread_sticky'] : null)."'," .
                "thread_title_qualify   ='".Database::escape_string($values['calification_notebook_title'])."'," .
                "thread_qualify_max     ='".Database::escape_string($values['numeric_calification'])."',".
                "thread_weight          ='".Database::escape_string($values['weight_calification'])."'".
                " WHERE c_id = $course_id AND thread_id='".intval($values['thread_id'])."'";

    Database::query($sql);
    //}
    // Update the post_title and the post_text.
    $sql = "UPDATE $table_posts SET 
                post_title          ='".Database::escape_string($values['post_title'])."',
                post_text           ='".Database::escape_string($values['post_text'])."',
                post_notification   ='".Database::escape_string(isset($values['post_notification'])?$values['post_notification']:null)."'
                WHERE c_id = $course_id AND post_id='".intval($values['post_id'])."'";
    var_dump($sql);
    Database::query($sql);

    if (!empty($values['remove_attach'])) {
        delete_attachment($values['post_id']);
    }

    if (empty($values['id_attach'])) {
        add_forum_attachment_file($values['file_comment'], $values['post_id']);
    } else {
        edit_forum_attachment_file($values['file_comment'], $values['post_id'], $values['id_attach']);
    }

    if (api_is_course_admin() == true) {
        $ccode = api_get_course_id();
        $sid = api_get_session_id();
        $link_id = is_resource_in_course_gradebook($ccode, 5, $values['thread_id'], $sid);
        $thread_qualify_gradebook = isset($values['thread_qualify_gradebook']) ? $values['thread_qualify_gradebook'] : null;
        if ($thread_qualify_gradebook != 1) {
            if ($link_id !== false) {
                remove_resource_from_course_gradebook($link_id);
            }
        } else {
            if ($link_id === false && !$_GET['thread']) {
                //$date_in_gradebook = date('Y-m-d H:i:s');
                $date_in_gradebook = null;
                $weigthqualify = $values['weight_calification'];
                add_resource_to_course_gradebook($ccode, 5, $values['thread_id'], Database::escape_string(stripslashes($values['calification_notebook_title'])), $weigthqualify, $values['numeric_calification'], null, $date_in_gradebook, 0, $sid);
            }
        }
    }
    // Storing the attachments if any.
    //update_added_resources('forum_post', $values['post_id']);

    $message = get_lang('EditPostStored').'<br />';
    $message .= get_lang('ReturnTo').' <a href="viewforum.php?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($_GET['forum']).'&amp;gidReq='.$_SESSION['toolgroup'].'&amp;origin='.$origin.'">'.get_lang('Forum').'</a><br />';
    $message .= get_lang('ReturnTo').' <a href="viewthread.php?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($_GET['forum']).'&amp;gidReq='.$_SESSION['toolgroup'].'&amp;origin='.$origin.'&amp;gradebook='.$gradebook.'&amp;thread='.$values['thread_id'].'&amp;post='.Security::remove_XSS($_GET['post']).'">'.get_lang('Message').'</a>';

    session_unregister('formelements');
    session_unregister('origin');
    session_unregister('breadcrumbs');
    session_unregister('addedresource');
    session_unregister('addedresourceid');

    Display :: display_confirmation_message($message,false);
}


/**
 * This function displays the firstname and lastname of the user as a link to the user tool.
 *
 * @param string names
 * @return string HTML
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function display_user_link($user_id, $name, $origin = '') {
    if ($user_id != 0) {
        return '<a href="../user/userInfo.php?uInfo='.$user_id.'" '. (!empty($origin)? 'target="_self"': '') .'>'.$name.'</a>';
    } else {
        return $name.' ('.get_lang('Anonymous').')';
    }
}

/**
 * This function displays the user image from the profile, with a link to the user's details.
 * @param 	int 	User's database ID
 * @param 	str 	User's name
 * @return 	string 	An HTML with the anchor and the image of the user
 * @author Julio Montoya <gugli100@gmail.com>
 */
function display_user_image($user_id, $name, $origin = '') {
    $link = '<a href="../user/userInfo.php?uInfo='.$user_id.'" '. (!empty($origin)? 'target="_self"': '') .'>';
    $attrb = array();
    if ($user_id != 0) {
        $image_path = UserManager::get_user_picture_path_by_id($user_id, 'web', false, true);
        $image_repository = $image_path['dir'];
        $existing_image = $image_path['file'];
        $friends_profile = UserManager::get_picture_user($user_id, $image_path['file'], 0, USER_IMAGE_SIZE_MEDIUM , 'width="96" height="96" ');
        return 	$link.'<img src="'.$friends_profile['file'].'" '.$friends_profile['style'].' alt="'.$name.'"  title="'.$name.'" /></a>';
    } else {
        return $link.'<img src="'.api_get_path(WEB_CODE_PATH)."img/unknown.jpg".'" alt="'.$name.'"  title="'.$name.'" /></a>';
    }
}

/**
 * The thread view counter gets increased every time someone looks at the thread
 *
 * @param int
 * @return void
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function increase_thread_view($thread_id) {
    $table_threads 	= Database :: get_course_table(TABLE_FORUM_THREAD);
    $course_id = api_get_course_int_id();    

    $sql = "UPDATE $table_threads SET thread_views=thread_views+1
            WHERE c_id = $course_id AND  thread_id='".Database::escape_string($thread_id)."'"; // This needs to be cleaned first.
    $result = Database::query($sql);
}

/**
 * The relies counter gets increased every time somebody replies to the thread
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function update_thread($thread_id, $last_post_id,$post_date) {
    $table_threads 			= Database :: get_course_table(TABLE_FORUM_THREAD);
    
    $course_id = api_get_course_int_id();
    

    $sql = "UPDATE $table_threads SET thread_replies=thread_replies+1,
            thread_last_post='".Database::escape_string($last_post_id)."',
            thread_date='".Database::escape_string($post_date)."' 
            WHERE c_id = $course_id AND  thread_id='".Database::escape_string($thread_id)."'"; // this needs to be cleaned first
    $result = Database::query($sql);
}

/**
 * This function is called when the user is not allowed in this forum/thread/...
 * @return bool display message of "not allowed"
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function forum_not_allowed_here() {
    Display :: display_error_message(get_lang('NotAllowedHere'));
    Display :: display_footer();
    return false;
}

/**
 * This function is used to find all the information about what's new in the forum tool
 * @return void
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function get_whats_new() {
    global $_user;
    global $_course;

    $table_posts 			   = Database :: get_course_table(TABLE_FORUM_POST);
    $tracking_last_tool_access = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);

    // Note: This has to be replaced by the tool constant later. But temporarily bb_forum is used since this is the only thing that is in the tracking currently.
    //$tool = TOOL_FORUM;
    $tool = TOOL_FORUM; //
    // to do: Remove this. For testing purposes only.
    //session_unregister('last_forum_access');
    //session_unregister('whatsnew_post_info');
    
    $course_id = api_get_course_int_id();    

    if (!$_SESSION['last_forum_access']) {
        $sql = "SELECT * FROM ".$tracking_last_tool_access." 
                WHERE access_user_id='".Database::escape_string($_user['user_id'])."' AND access_cours_code='".Database::escape_string($_course['sysCode'])."' AND access_tool='".Database::escape_string($tool)."'";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $_SESSION['last_forum_access'] = $row['access_date'];
    }

    if (!$_SESSION['whatsnew_post_info']) {
        if ($_SESSION['last_forum_access'] != '') {
            $whatsnew_post_info = array();
            $sql = "SELECT * FROM ".$table_posts." WHERE c_id = $course_id AND post_date>'".Database::escape_string($_SESSION['last_forum_access'])."'"; // note: check the performance of this query.
            $result = Database::query($sql);
            while ($row = Database::fetch_array($result)) {
                $whatsnew_post_info[$row['forum_id']][$row['thread_id']][$row['post_id']] = $row['post_date'];
            }
            $_SESSION['whatsnew_post_info'] = $whatsnew_post_info;
        }
    }
}

/**
 * With this function we find the number of posts and topics in a given forum.
 *
 * @todo consider to call this function only once and let it return an array where the key is the forum id and the value is an array with number_of_topics and number of post
 * as key of this array and the value as a value. This could reduce the number of queries needed (especially when there are more forums)
 * @todo consider merging both in one query.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 *
 * @deprecated the counting mechanism is now inside the function get_forums
 */
function get_post_topics_of_forum($forum_id) {
    $table_posts 			= Database :: get_course_table(TABLE_FORUM_POST);
    $table_threads 			= Database :: get_course_table(TABLE_FORUM_THREAD);
    $table_item_property 	= Database :: get_course_table(TABLE_ITEM_PROPERTY);
    
    $course_id = api_get_course_int_id();

    $sql = "SELECT count(*) as number_of_posts FROM $table_posts WHERE forum_id='".$forum_id."'";
    if (api_is_allowed_to_edit(null, true)) {
        $sql = "SELECT count(*) as number_of_posts
                FROM $table_posts posts, $table_threads threads, $table_item_property item_property
                WHERE
                posts.c_id = $course_id AND
                item_property.c_id = $course_id AND   
                posts.forum_id='".Database::escape_string($forum_id)."'
                AND posts.thread_id=threads.thread_id
                AND item_property.ref=threads.thread_id
                AND item_property.visibility<>2
                AND item_property.tool='".TOOL_FORUM_THREAD."'
                ";
    } else {
        $sql = "SELECT count(*) as number_of_posts
                FROM $table_posts posts, $table_threads threads, $table_item_property item_property
                WHERE
                posts.c_id = $course_id AND
                item_property.c_id = $course_id AND  
                posts.forum_id='".Database::escape_string($forum_id)."'
                AND posts.thread_id=threads.thread_id
                AND item_property.ref=threads.thread_id
                AND item_property.visibility=1
                AND posts.visible=1
                AND item_property.tool='".TOOL_FORUM_THREAD."'
                ";
    }
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    $number_of_posts = $row['number_of_posts'];

    // We could loop through the result array and count the number of different group_ids, but I have chosen to use a second sql statement.
    if (api_is_allowed_to_edit(null, true)) {
        $sql = "SELECT count(*) as number_of_topics
                FROM $table_threads threads, $table_item_property item_property
                WHERE
                threads.c_id = $course_id AND
                item_property.c_id = $course_id AND  
                threads.forum_id='".Database::escape_string($forum_id)."'
                AND item_property.ref=threads.thread_id
                AND item_property.visibility<>2
                AND item_property.tool='".TOOL_FORUM_THREAD."'
                ";
    } else {
        $sql = "SELECT count(*) as number_of_topics
                FROM $table_threads threads, $table_item_property item_property
                WHERE 
                threads.c_id = $course_id AND
                item_property.c_id = $course_id AND  
                threads.forum_id='".Database::escape_string($forum_id)."'
                AND item_property.ref=threads.thread_id
                AND item_property.visibility=1
                AND item_property.tool='".TOOL_FORUM_THREAD."'
                ";
    }
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    $number_of_topics = $row['number_of_topics'];
    if ($number_of_topics == '') {
        $number_of_topics = 0; // Due to the nature of the group by this can result in an empty string.
    }

    $return = array('number_of_topics' => $number_of_topics, 'number_of_posts' => $number_of_posts);
    return $return;
}

/**
 * This function approves a post = change
 *
 * @param $post_id the id of the post that will be deleted
 * @param $action make the post visible or invisible
 * @return string language variable
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function approve_post($post_id, $action) {
    $table_posts 	= Database :: get_course_table(TABLE_FORUM_POST);
    $course_id = api_get_course_int_id();    

    if ($action == 'invisible') {
        $visibility_value = 0;
    }
    if ($action == 'visible') {
        $visibility_value = 1;
        handle_mail_cue('post', $post_id);
    }

    $sql = "UPDATE $table_posts SET visible='".Database::escape_string($visibility_value)."' 
            WHERE c_id = $course_id AND post_id='".Database::escape_string($post_id)."'";
    $return = Database::query($sql);
    if ($return) {
        return 'PostVisibilityChanged';
    }
}

/**
 * This function retrieves all the unapproved messages for a given forum
 * This is needed to display the icon that there are unapproved messages in that thread (only the courseadmin can see this)
 *
 * @param $forum_id the forum where we want to know the unapproved messages of
 * @return array returns
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function get_unaproved_messages($forum_id) {
    $table_posts = Database :: get_course_table(TABLE_FORUM_POST);
    $course_id = api_get_course_int_id();    

    $return_array = array();
    $sql = "SELECT DISTINCT thread_id FROM $table_posts WHERE c_id = $course_id AND forum_id='".Database::escape_string($forum_id)."' AND visible='0'";
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        $return_array[] = $row['thread_id'];
    }
    return $return_array;
}

/**
 * This function sends the notification mails to everybody who stated that they wanted to be informed when a new post
 * was added to a given thread.
 *
 * @param array reply information
 * @return void
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function send_notification_mails($thread_id, $reply_info) {
    $table_posts 			= Database :: get_course_table(TABLE_FORUM_POST);
    $table_mailcue			= Database :: get_course_table(TABLE_FORUM_MAIL_QUEUE);

    // First we need to check if
    // 1. the forum category is visible
    // 2. the forum is visible
    // 3. the thread is visible
    // 4. the reply is visible (=when there is)
    $current_thread	= get_thread_information($thread_id);
    $current_forum	= get_forum_information($current_thread['forum_id']);
    $current_forum_category = get_forumcategory_information($current_forum['forum_category']);
    if ($current_thread['visibility'] == '1' && $current_forum['visibility'] == '1' && $current_forum_category['visibility'] == '1' && $current_forum['approval_direct_post'] != '1') {
        $send_mails = true;
    } else {
        $send_mails = false;
    }

    // The forum category, the forum, the thread and the reply are visible to the user.
    if ($send_mails) {
        send_notifications($current_thread['forum_id'], $thread_id);
        /*
        $table_user 	= Database :: get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT DISTINCT user.firstname, user.lastname, user.email, user.user_id
                FROM $table_posts post, $table_user user
                WHERE post.thread_id='".Database::escape_string($thread_id)."'
                AND post.post_notification='1'
                AND post.poster_id=user.user_id";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            send_mail($row, $current_thread);
        }
        */
    } else {
        /*
        $sql = "SELECT * FROM $table_posts WHERE thread_id='".Database::escape_string($thread_id)."' AND post_notification='1'";
        $result = Database::query($sql);
        */
        $table_notification = Database::get_course_table(TABLE_FORUM_NOTIFICATION);
        $sql = "SELECT * FROM $table_notification WHERE c_id = ".api_get_course_int_id()." AND (forum_id = '".Database::escape_string($current_forum['forum_id'])."' OR thread_id = '".Database::escape_string($thread_id)."' ) ";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $sql_mailcue = "INSERT INTO $table_mailcue (c_id, thread_id, post_id) VALUES (".api_get_course_int_id().", '".Database::escape_string($thread_id)."', '".Database::escape_string($reply_info['new_post_id'])."')";
            $result_mailcue = Database::query($sql_mailcue);
        }
    }
}

/**
 * This function is called whenever something is made visible because there might
 * be new posts and the user might have indicated that (s)he wanted to be
 * informed about the new posts by mail.
 *
 * @param    string  Content type (post, thread, forum, forum_category)
 * @param    int     Item DB ID
 * @return string language variable
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function handle_mail_cue($content, $id) {
    $table_mailcue		= Database :: get_course_table(TABLE_FORUM_MAIL_QUEUE);
    $table_forums 		= Database :: get_course_table(TABLE_FORUM);
    $table_threads 		= Database :: get_course_table(TABLE_FORUM_THREAD);
    $table_posts 		= Database :: get_course_table(TABLE_FORUM_POST);
    $table_users 		= Database :: get_main_table(TABLE_MAIN_USER);
    
    $course_id = api_get_course_int_id();

    // If the post is made visible we only have to send mails to the people who indicated that they wanted to be informed for that thread.
    if ($content == 'post') {
        // Getting the information about the post (need the thread_id).
        $post_info = get_post_information($id);
        $thread_id = Database::escape_string($post_info['thread_id']);

        // Sending the mail to all the users that wanted to be informed for replies on this thread.
        $sql = "SELECT users.firstname, users.lastname, users.user_id, users.email
                FROM $table_mailcue mailcue, $table_posts posts, $table_users users
                WHERE
                posts.c_id = $course_id AND
                mailcue.c_id = $course_id AND
                posts.thread_id='$thread_id'
                AND posts.post_notification='1'
                AND mailcue.thread_id='$thread_id'
                AND users.user_id=posts.poster_id
                AND users.active=1
                GROUP BY users.email";

        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            send_mail($row, get_thread_information($post_info['thread_id']));
        }

        // Deleting the relevant entries from the mailcue.
        $sql_delete_mailcue = "DELETE FROM $table_mailcue WHERE c_id = $course_id AND post_id='".Database::escape_string($id)."' AND thread_id='".Database::escape_string($post_info['thread_id'])."'";
        //$result = Database::query($sql_delete_mailcue);
    } elseif ($content == 'thread') {
        // Sending the mail to all the users that wanted to be informed for replies on this thread.
        $sql = "SELECT users.firstname, users.lastname, users.user_id, users.email
                FROM $table_mailcue mailcue, $table_posts posts, $table_users users
                WHERE
                posts.c_id = $course_id AND 
                mailcue.c_id = $course_id AND  
                posts.thread_id='".Database::escape_string($id)."'
                AND posts.post_notification='1'
                AND mailcue.thread_id='".Database::escape_string($id)."'
                AND users.user_id=posts.poster_id
                AND users.active=1
                GROUP BY users.email";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            send_mail($row, get_thread_information($id));
        }

        // Deleting the relevant entries from the mailcue.
        $sql_delete_mailcue = "DELETE FROM $table_mailcue WHERE c_id = $course_id AND thread_id='".Database::escape_string($id)."'";
        $result = Database::query($sql_delete_mailcue);
    } elseif ($content == 'forum') {
        $sql = "SELECT thread_id FROM $table_threads WHERE c_id = $course_id AND forum_id='".Database::escape_string($id)."'";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            handle_mail_cue('thread', $row['thread_id']);
        }
    } elseif ($content == 'forum_category') {
        $sql = "SELECT forum_id FROM $table_forums WHERE c_id = $course_id AND forum_category ='".Database::escape_string($id)."'";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            handle_mail_cue('forum', $row['forum_id']);
        }
    } else {
        return get_lang('Error');
    }
}

/**
 * This function sends the mails for the mail notification
 *
 * @param array
 * @param array
 * @return void
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function send_mail($user_info = array(), $thread_information = array()) {
    global $_course;
    global $_user;

    $email_subject = get_lang('NewForumPost').' - '.$_course['official_code'];

    if (isset($thread_information) && is_array($thread_information)) {
        $thread_link = api_get_path(WEB_CODE_PATH).'forum/viewthread.php?'.api_get_cidreq().'&amp;forum='.$thread_information['forum_id'].'&amp;thread='.$thread_information['thread_id'];
    }
    $email_body = api_get_person_name($user_info['firstname'], $user_info['lastname'], null, PERSON_NAME_EMAIL_ADDRESS)."\n\r";
    $email_body .= '['.$_course['official_code'].'] - ['.$_course['name']."]<br />\n";
    $email_body .= get_lang('NewForumPost')."\n";
    $email_body .= get_lang('YouWantedToStayInformed')."<br /><br />\n";
    $email_body .= get_lang('ThreadCanBeFoundHere')." : <a href=\"".$thread_link."\">".$thread_link."</a>\n";

    if ($user_info['user_id']<>$_user['user_id']) {
        @api_mail_html(api_get_person_name($user_info['firstname'], $user_info['lastname'], null, PERSON_NAME_EMAIL_ADDRESS), $user_info['email'], $email_subject, $email_body, api_get_person_name($_SESSION['_user']['firstName'], $_SESSION['_user']['lastName'], null, PERSON_NAME_EMAIL_ADDRESS), $_SESSION['_user']['mail']);
    }
}

/**
 * This function displays the form for moving a thread to a different (already existing) forum
 * @return void HTML
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function move_thread_form() {
    global $origin;
    $gradebook = Security::remove_XSS($_GET['gradebook']);
    // Initialize the object.
    $form = new FormValidator('movepost', 'post', api_get_self().'?forum='.Security::remove_XSS($_GET['forum']).'&gradebook='.$gradebook.'&thread='.Security::remove_XSS($_GET['thread']).'&action='.Security::remove_XSS($_GET['action']).'&origin='.$origin);
    // The header for the form
    $form->addElement('header', '', get_lang('MoveThread'));
    // Invisible form: the thread_id
    $form->addElement('hidden', 'thread_id', intval($_GET['thread'])); // Note: This has to be cleaned first.

    // the fora
    $forum_categories = get_forum_categories();
    $forums = get_forums();

    $htmlcontent .= '<div class="row">
        <div class="label">
            <span class="form_required">*</span>'.get_lang('MoveTo').'
        </div>
        <div class="formw">';
    $htmlcontent .= '<select name="forum">';
    foreach ($forum_categories as $key => $category) {
        $htmlcontent .= '<optgroup label="'.$category['cat_title'].'">';
        foreach ($forums as $key => $forum) {
            if ($forum['forum_category'] == $category['cat_id']) {
                $htmlcontent .= '<option value="'.$forum['forum_id'].'">'.$forum['forum_title'].'</option>';
            }
        }
        $htmlcontent .= '</optgroup>';
    }
    $htmlcontent .= "</select>";
    $htmlcontent .= '	</div>
                    </div>';

    $form->addElement('html', $htmlcontent);

    // The OK button
    $form->addElement('style_submit_button', 'SubmitForum', get_lang('MoveThread'), 'class="save"');

    // Validation or display
    if ($form->validate()) {
        $values = $form->exportValues();
        if (isset($_POST['forum'])) {
            store_move_thread($values);
        }
    } else {
        $form->display();
    }
}

/**
 * This function displays the form for moving a post message to a different (already existing) or a new thread.
 * @return void HTML
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function move_post_form() {
    global $origin;
    $gradebook = Security::remove_XSS($_GET['gradebook']);
    // initiate the object
    $form = new FormValidator('movepost', 'post', api_get_self().'?forum='.Security::remove_XSS($_GET['forum']).'&thread='.Security::remove_XSS($_GET['thread']).'&origin='.$origin.'&gradebook='.$gradebook.'&post='.Security::remove_XSS($_GET['post']).'&action='.Security::remove_XSS($_GET['action']).'&post='.Security::remove_XSS($_GET['post']));
    // The header for the form
    $form->addElement('header', '', get_lang('MovePost'));

    // Invisible form: the post_id
    $form->addElement('hidden', 'post_id', strval(intval($_GET['post']))); // Note: This has to be cleaned first.

    // Dropdown list: Threads of this forum
    $threads = get_threads(strval(intval($_GET['forum']))); // Note: This has to be cleaned.
    //my_print_r($threads);
    $threads_list[0] = get_lang('ANewThread');
    foreach ($threads as $key => $value) {
        $threads_list[$value['thread_id']] = $value['thread_title'];
    }
    $form->addElement('select', 'thread', get_lang('MoveToThread'), $threads_list);
    $form->applyFilter('thread', 'html_filter');

    // The OK button
    $form->addElement('style_submit_button', 'submit', get_lang('MovePost'), 'class="save"');

    // Setting the rules
    $form->addRule('thread', get_lang('ThisFieldIsRequired'), 'required');

    // Validation or display
    if ($form->validate()) {
       $values = $form->exportValues();
       store_move_post($values);
    } else {
        $form->display();
    }
}

/**
 *
 * @param array
 * @return string HTML language variable
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function store_move_post($values) {
    global $_course;

    $table_forums 			= Database :: get_course_table(TABLE_FORUM);
    $table_threads 			= Database :: get_course_table(TABLE_FORUM_THREAD);
    $table_posts 			= Database :: get_course_table(TABLE_FORUM_POST);
    
    $course_id = api_get_course_int_id();

    
    if ($values['thread'] == '0') {
        $current_post = get_post_information($values['post_id']);

        // Storing a new thread.
        $sql = "INSERT INTO $table_threads (c_id, thread_title, forum_id, thread_poster_id, thread_poster_name, thread_last_post, thread_date)
            VALUES (
            	".$course_id.",
                '".Database::escape_string($current_post['post_title'])."',
                '".Database::escape_string($current_post['forum_id'])."',
                '".Database::escape_string($current_post['poster_id'])."',
                '".Database::escape_string($current_post['poster_name'])."',
                '".Database::escape_string($values['post_id'])."',
                '".Database::escape_string($current_post['post_date'])."'
                )";
        $result = Database::query($sql);
        $new_thread_id = Database::insert_id();
        api_item_property_update($_course, TOOL_FORUM_THREAD, $new_thread_id, 'visible', $current_post['poster_id']);

        // Moving the post to the newly created thread.
        $sql = "UPDATE $table_posts SET thread_id='".Database::escape_string($new_thread_id)."', post_parent_id='0' WHERE c_id = $course_id AND post_id='".Database::escape_string($values['post_id'])."'";
        $result = Database::query($sql);

        // Resetting the parent_id of the thread to 0 for all those who had this moved post as parent.
        $sql = "UPDATE $table_posts SET post_parent_id='0' WHERE c_id = $course_id AND post_parent_id='".Database::escape_string($values['post_id'])."'";
        $result = Database::query($sql);

        // Updating updating the number of threads in the forum.
        $sql = "UPDATE $table_forums SET forum_threads=forum_threads+1 WHERE c_id = $course_id AND forum_id='".Database::escape_string($current_post['forum_id'])."'";
        $result = Database::query($sql);

        // Resetting the last post of the old thread and decreasing the number of replies and the thread.
        $sql = "SELECT * FROM $table_posts WHERE c_id = $course_id AND thread_id='".Database::escape_string($current_post['thread_id'])."' ORDER BY post_id DESC";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $sql = "UPDATE $table_threads SET thread_last_post='".$row['post_id']."', thread_replies=thread_replies-1 WHERE c_id = $course_id AND thread_id='".Database::escape_string($current_post['thread_id'])."'";
        $result = Database::query($sql);

    } else {
        // Moving to the chosen thread.
        
        //Old code
        //$sql = "UPDATE $table_posts SET thread_id='".Database::escape_string($_POST['thread'])."', post_parent_id='0' WHERE post_id='".Database::escape_string($values['post_id'])."'";
        //$result = Database::query($sql);
        
        // Resetting the parent_id of the thread to 0 for all those who had this moved post as parent.
        //$sql = "UPDATE $table_posts SET post_parent_id='0' WHERE post_parent_id='".Database::escape_string($values['post_id'])."'";
        //$result = Database::query($sql);
        
        // If this post is the last post of the thread we must update the thread_last_post with a new post_id
        // Search for the original thread_id
        
        $sql = "SELECT thread_id FROM ".$table_posts." WHERE c_id = $course_id AND post_id = '".$values['post_id']."' ";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        
        $original_thread_id = $row['thread_id'];
          
        $sql = "SELECT thread_last_post FROM ".$table_threads." WHERE c_id = $course_id AND thread_id = '".$original_thread_id."' ";
          
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $thread_is_last_post = $row['thread_last_post'];
        // If is this thread, update the thread_last_post with the last one.

        if ($thread_is_last_post == $values['post_id']) {
            $sql = "SELECT post_id FROM ".$table_posts." WHERE c_id = $course_id AND thread_id = '".$original_thread_id."' AND post_id <> '".$values['post_id']."' ORDER BY post_date DESC LIMIT 1";
            $result= Database::query($sql);

            $row=Database::fetch_array($result);
            $thread_new_last_post = $row['post_id'];

            $sql = "UPDATE ".$table_threads." SET thread_last_post = '".$thread_new_last_post."' WHERE c_id = $course_id AND thread_id = '".$original_thread_id."' ";
            $result= Database::query($sql);
        }

        $sql="UPDATE $table_threads SET thread_replies=thread_replies-1 WHERE c_id = $course_id AND thread_id='".$original_thread_id."'";
        $result = Database::query($sql);

        // moving to the chosen thread
        $sql="UPDATE $table_posts SET thread_id='".intval($_POST['thread'])."', post_parent_id='0' WHERE c_id = $course_id AND post_id='".intval($values['post_id'])."'";
        $result = Database::query($sql);

        // resetting the parent_id of the thread to 0 for all those who had this moved post as parent
        $sql="UPDATE $table_posts SET post_parent_id='0' WHERE c_id = $course_id AND post_parent_id='".intval($values['post_id'])."'";
        $result = Database::query($sql);

        $sql="UPDATE $table_threads SET thread_replies=thread_replies+1 WHERE c_id = $course_id AND thread_id='".intval($_POST['thread'])."'";
        $result = Database::query($sql);
        
    }
    return get_lang('ThreadMoved');
}

/**
 *
 * @param array
 * @return string HTML language variable
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */
function store_move_thread($values) {
    global $_course;

    $table_forums 			= Database :: get_course_table(TABLE_FORUM);
    $table_threads 			= Database :: get_course_table(TABLE_FORUM_THREAD);
    $table_posts 			= Database :: get_course_table(TABLE_FORUM_POST);
    
    $course_id = api_get_course_int_id();

    // Change the thread table: Setting the forum_id to the new forum.
    $sql = "UPDATE $table_threads SET forum_id='".Database::escape_string($_POST['forum'])."' WHERE c_id = $course_id AND thread_id='".Database::escape_string($_POST['thread_id'])."'";
    $result = Database::query($sql);

    // Changing all the posts of the thread: setting the forum_id to the new forum.
    $sql = "UPDATE $table_posts SET forum_id='".Database::escape_string($_POST['forum'])."' WHERE c_id = $course_id AND thread_id='".Database::escape_string($_POST['thread_id'])."'";
    $result = Database::query($sql);

    return get_lang('ThreadMoved');
}

/**
 * Prepares a string for displaying by highlighting the search results inside, if any.
 * @param string $input    The input string.
 * @return string          The same string with highlighted hits inside.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, February 2006 - the initial version.
 * @author Ivan Tcholakov, March 2011 - adaptation for Chamilo LMS.
 */
function prepare4display($input) {
    static $highlightcolors = array('yellow', '#33CC33', '#3399CC', '#9999FF', '#33CC33');
    static $search;

    if (!isset($search)) {
        if (isset($_POST['search_term'])) {
            $search = html_filter($_POST['search_term']); // No html at all.
        } elseif (isset($_GET['search'])) {
            $search = html_filter($_GET['search']);
        } else {
            $search = '';
        }
    }

    if (!empty($search)) {
        if (strstr($search, '+')) {
            $search_terms = explode('+', $search);
        } else  {
            $search_terms[] = trim($search);
        }
        $counter = 0;
        foreach ($search_terms as $key => $search_term) {
            $input = api_preg_replace('/'.preg_quote(trim($search_term), '/').'/i', '<span style="background-color: '.$highlightcolors[$counter].'">$0</span>', $input);
            $counter++;
        }
    }

    // TODO: Security should be implemented outside this function.
    // Change this to COURSEMANAGERLOWSECURITY or COURSEMANAGER to lower filtering and allow more styles (see comments of Security::remove_XSS() method to learn about other levels).
    
    return Security::remove_XSS($input, STUDENT, true);
}

/**
 * Display the search form for the forum and display the search results
 * @return void display an HTML search results
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version march 2008, dokeos 1.8.5
 */
function forum_search() {
    global $origin;

    // Initialize the object.
    $form = new FormValidator('forumsearch', 'post', 'forumsearch.php?origin='.$origin.'');

    // Settting the form elements.
    $form->addElement('header', '', get_lang('ForumSearch'));
    $form->addElement('text', 'search_term', get_lang('SearchTerm'), 'class="input_titles" id="search_title"');
    $form->applyFilter('search_term', 'html_filter');
    $form->addElement('static', 'search_information', '', get_lang('ForumSearchInformation')/*, $dissertation[$_GET['opleidingsonderdeelcode']]['code']*/);
    $form->addElement('style_submit_button', null, get_lang('Search'), 'class="search"');

    // Setting the rules.
    $form->addRule('search_term', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('search_term', get_lang('TooShort'), 'minlength', 3);

    // Validation or display.
    if( $form->validate() ) {
       $values = $form->exportValues();
       $form->setDefaults($values);
       $form->display();
       // Display the search results.
       display_forum_search_results(stripslashes($values['search_term']));
    } else {
        $form->display();
    }
}

/**
 * Display the search results
 * @param string
 * @return void display the results
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version march 2008, dokeos 1.8.5
 */
function display_forum_search_results($search_term) {
    global $origin;

    $table_categories 		= Database :: get_course_table(TABLE_FORUM_CATEGORY);
    $table_forums 			= Database :: get_course_table(TABLE_FORUM);
    $table_threads 			= Database :: get_course_table(TABLE_FORUM_THREAD);
    $table_posts 			= Database :: get_course_table(TABLE_FORUM_POST);

    $gradebook = Security::remove_XSS($_GET['gradebook']);
    
    $course_id = api_get_course_int_id();
    
    // Defining the search strings as an array.
    if (strstr($search_term, '+')) {
        $search_terms = explode('+', $search_term);
    } else  {
        $search_terms[] = $search_term;
    }

    // Search restriction.
    foreach ($search_terms as $key => $value) {
        $search_restriction[] = "(posts.post_title LIKE '%".Database::escape_string(trim($value))."%'
                                    OR posts.post_text LIKE '%".Database::escape_string(trim($value))."%')";
    }

    $sql = "SELECT * FROM $table_posts posts
                WHERE c_id = $course_id AND ".implode(' AND ',$search_restriction)."
                GROUP BY posts.post_id";

    // Getting all the information of the forum categories.
    $forum_categories_list = get_forum_categories();

    // Getting all the information of the forums.
    $forum_list = get_forums();

    $result = Database::query($sql);
    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $display_result = false;
        /*
            We only show it when
            1. forum cateogory is visible
            2. forum is visible
            3. thread is visible (to do)
            4. post is visible
        */
        if (!api_is_allowed_to_edit(null, true)) {
            if ($forum_categories_list[$row['forum_id']['forum_category']]['visibility'] == '1' AND $forum_list[$row['forum_id']]['visibility'] == '1' AND $row['visible'] == '1') {
                $display_result = true;
            }
        } else {
            $display_result = true;
        }

        if ($display_result) {
            $search_results_item = '<li><a href="viewforumcategory.php?forumcategory='.$forum_list[$row['forum_id']]['forum_category'].'&amp;origin='.$origin.'&amp;search='.urlencode($search_term).'">'.prepare4display($forum_categories_list[$row['forum_id']['forum_category']]['cat_title']).'</a> &gt; ';
            $search_results_item .= '<a href="viewforum.php?forum='.$row['forum_id'].'&amp;origin='.$origin.'&amp;search='.urlencode($search_term).'">'.prepare4display($forum_list[$row['forum_id']]['forum_title']).'</a> &gt; ';
            //$search_results_item .= '<a href="">THREAD</a> &gt; ';
            $search_results_item .= '<a href="viewthread.php?forum='.$row['forum_id'].'&amp;gradebook='.$gradebook.'&amp;origin='.$origin.'&amp;thread='.$row['thread_id'].'&amp;search='.urlencode($search_term).'">'.prepare4display($row['post_title']).'</a>';
            $search_results_item .= '<br />';
            if (api_strlen($row['post_title']) > 200 ) {
                $search_results_item .= prepare4display(api_substr(strip_tags($row['post_title']), 0, 200)).'...';
            } else {
                $search_results_item .= prepare4display($row['post_title']);
            }
            $search_results_item .= '</li>';
            $search_results[] = $search_results_item;
        }
    }
    echo '<div class="row"><div class="form_header">'.count($search_results).' '.get_lang('ForumSearchResults').'</div></div>';
    echo '<ol>';
    if ($search_results) {
        echo implode($search_results);
    }
    echo '</ol>';
}

/**
 * Return the link to the forum search page
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version April 2008, dokeos 1.8.5
 */
function search_link() {
    global $origin;

    $return = '';

    if ($origin != 'learnpath') {
    	
        $return = '<a href="forumsearch.php?'.api_get_cidreq().'&amp;gidReq='.api_get_group_id().'&amp;action=search&amp;origin='.$origin.'"> '; 
        $return .= Display::return_icon('search.png', get_lang('Search'),'','32').'</a>';
        
        if (!empty($_GET['search'])) {
            $return .= ': '.Security::remove_XSS($_GET['search']).' ';
            $url = api_get_self().'?';
            $url_parameter = array();
            foreach ($_GET as $key => $value) {
                if ($key != 'search') {
                    $url_parameter[] = Security::remove_XSS($key).'='.Security::remove_XSS($value);
                }
            }
            $url = $url.implode('&amp;', $url_parameter);
            $return .= '<a href="'.$url.'">'.Display::return_icon('delete.gif', get_lang('RemoveSearchResults')).'</a>';
        }
    }
    return $return;
}

/**
 * This function adds an attachment file into a forum
 * @param string  a comment about file
 * @param int last id from forum_post table
 * @return void
 */
function add_forum_attachment_file($file_comment,$last_id) {
    global $_course;

    $agenda_forum_attachment = Database::get_course_table(TABLE_FORUM_ATTACHMENT);

    // Storing the attachments
    if (!empty($_FILES['user_upload']['name'])) {
        $upload_ok = process_uploaded_file($_FILES['user_upload']);
    }

    if (!empty($upload_ok)) {
        $course_dir = $_course['path'].'/upload/forum';
        $sys_course_path = api_get_path(SYS_COURSE_PATH);
        $updir = $sys_course_path.$course_dir;

        // Try to add an extension to the file if it hasn't one.
        $new_file_name = add_ext_on_mime(stripslashes($_FILES['user_upload']['name']), $_FILES['user_upload']['type']);
        // User's file name
        $file_name = $_FILES['user_upload']['name'];

        if (!filter_extension($new_file_name))  {
            Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
        } else {
            $new_file_name = uniqid('');
            $new_path=$updir.'/'.$new_file_name;
            $result= @move_uploaded_file($_FILES['user_upload']['tmp_name'], $new_path);
            $safe_file_comment	= Database::escape_string($file_comment);
            $safe_file_name		= Database::escape_string($file_name);
            $safe_new_file_name = Database::escape_string($new_file_name);
            $last_id = intval($last_id);
            // Storing the attachments if any.
            if ($result) {
                $sql = "INSERT INTO $agenda_forum_attachment (c_id, filename, comment, path, post_id, size)
                      	VALUES (".api_get_course_int_id().", '$safe_file_name', '$safe_file_comment', '$safe_new_file_name' , '$last_id', '".intval($_FILES['user_upload']['size'])."' )";
                $result = Database::query($sql);
                $message .= ' / '.get_lang('FileUploadSucces').'<br />';

                $last_id_file = Database::insert_id();
                api_item_property_update($_course, TOOL_FORUM_ATTACH, $last_id_file , 'ForumAttachmentAdded', api_get_user_id());
            }
        }
    }
}

/**
 * This function edits an attachment file into a forum
 * @param string  a comment about file
 * @param int Post Id
 * @param int attachment file Id
 * @return void
 */
function edit_forum_attachment_file($file_comment,$post_id,$id_attach) {
    global $_course;

    $table_forum_attachment = Database::get_course_table(TABLE_FORUM_ATTACHMENT);
    $course_id = api_get_course_int_id();

    // Storing the attachments.
    if (!empty($_FILES['user_upload']['name'])) {
        $upload_ok = process_uploaded_file($_FILES['user_upload']);
    }

    if (!empty($upload_ok)) {
        $course_dir = $_course['path'].'/upload/forum';
        $sys_course_path = api_get_path(SYS_COURSE_PATH);
        $updir = $sys_course_path.$course_dir;

        // Try to add an extension to the file if it hasn't one.
        $new_file_name = add_ext_on_mime(stripslashes($_FILES['user_upload']['name']), $_FILES['user_upload']['type']);
        // User's file name
        $file_name = $_FILES['user_upload']['name'];

        if (!filter_extension($new_file_name))  {
            Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
        } else {
            $new_file_name = uniqid('');
            $new_path = $updir.'/'.$new_file_name;
            $result = @move_uploaded_file($_FILES['user_upload']['tmp_name'], $new_path);
            $safe_file_comment = Database::escape_string($file_comment);
            $safe_file_name = Database::escape_string($file_name);
            $safe_new_file_name = Database::escape_string($new_file_name);
            $safe_post_id = (int)$post_id;
            $safe_id_attach = (int)$id_attach;
            // Storing the attachments if any.
            if ($result) {
                $sql = "UPDATE $table_forum_attachment SET filename = '$safe_file_name', comment = '$safe_file_comment', path = '$safe_new_file_name', post_id = '$safe_post_id', size ='".$_FILES['user_upload']['size']."'
                       WHERE c_id = $course_id AND id = '$safe_id_attach'";
                $result = Database::query($sql);

                api_item_property_update($_course, TOOL_FORUM_ATTACH, $safe_id_attach, 'ForumAttachmentUpdated', api_get_user_id());
            }
        }
    }
}

/**
 * Show a list with all the attachments according to the post's id
 * @param the post's id
 * @return array with the post info
 * @author Julio Montoya Dokeos
 * @version avril 2008, dokeos 1.8.5
 */
function get_attachment($post_id) {
    $forum_table_attachment = Database :: get_course_table(TABLE_FORUM_ATTACHMENT);
    $course_id = api_get_course_int_id();
    $row = array();
    $post_id = intval($post_id);
    $sql = "SELECT id, path, filename,comment FROM $forum_table_attachment 
            WHERE c_id = $course_id AND post_id = $post_id";
    $result = Database::query($sql);
    if (Database::num_rows($result) != 0) {
        $row = Database::fetch_array($result);
    }
    return $row;
}

/**
 * Delete the all the attachments from the DB and the file according to the post's id or attach id(optional)
 * @param post id
 * @param attach id (optional)
 * @return void
 * @author Julio Montoya Dokeos
 * @version avril 2008, dokeos 1.8.5
 */
function delete_attachment($post_id, $id_attach = 0) {
    global $_course;

    $forum_table_attachment = Database::get_course_table(TABLE_FORUM_ATTACHMENT);
    $course_id = api_get_course_int_id();

    $cond = (!empty($id_attach)) ? " id = ".(int)$id_attach."" : " post_id = ".(int)$post_id."";
    $sql = "SELECT path FROM $forum_table_attachment WHERE c_id = $course_id AND $cond";
    $res = Database::query($sql);
    $row = Database::fetch_array($res);

    $course_dir      = $_course['path'].'/upload/forum';
    $sys_course_path = api_get_path(SYS_COURSE_PATH);
    $updir           = $sys_course_path.$course_dir;
    $my_path         = isset($row['path']) ? $row['path'] : null;
    $file            =  $updir.'/'.$my_path;
    if (Security::check_abs_path($file, $updir) ) {
        @unlink($file);
    }

    // Delete from forum_attachment table.
    $sql = "DELETE FROM $forum_table_attachment WHERE c_id = $course_id AND $cond ";
    $result = Database::query($sql);
    $last_id_file = Database::insert_id();

    // Update item_property.
    api_item_property_update($_course, TOOL_FORUM_ATTACH, $id_attach, 'ForumAttachmentDelete', api_get_user_id());

    if (!empty($result) && !empty($id_attach)) {
        $message = get_lang(get_lang('AttachmentFileDeleteSuccess'));
        Display::display_confirmation_message($message);
    }

}

/**
 * This function gets all the forum information of the all the forum of the group
 *
 * @param integer $group_id the id of the group we need the fora of (see forum.forum_of_group)
 * @return array
 *
 * @todo this is basically the same code as the get_forums function. Consider merging the two.
 */
function get_forums_of_group($group_id) {
    $table_forums 			= Database :: get_course_table(TABLE_FORUM);
    $table_threads 			= Database :: get_course_table(TABLE_FORUM_THREAD);
    $table_posts 			= Database :: get_course_table(TABLE_FORUM_POST);
    $table_item_property 	= Database :: get_course_table(TABLE_ITEM_PROPERTY);
    $table_users 			= Database :: get_main_table(TABLE_MAIN_USER);
    $course_id              = api_get_course_int_id();
    
    //-------------- Student -----------------//
    // Select all the forum information of all forums (that are visible to students).
    $sql = "SELECT * FROM ".$table_forums." forum , ".$table_item_property." item_properties
                WHERE forum.forum_of_group = '".Database::escape_string($group_id)."' AND
                forum.c_id = $course_id AND
                item_properties.c_id = $course_id AND                 
                forum.forum_id=item_properties.ref AND 
                item_properties.visibility=1 AND
                item_properties.tool='".TOOL_FORUM."'
                ORDER BY forum.forum_order ASC";
    // Select the number of threads of the forums (only the threads that are visible).
    $sql2 = "SELECT count(thread_id) AS number_of_threads, threads.forum_id FROM $table_threads threads, ".$table_item_property." item_properties
                    WHERE threads.thread_id=item_properties.ref AND
                    forum.c_id = $course_id AND
                    item_properties.c_id = $course_id AND 
                    item_properties.visibility=1 AND
                    item_properties.tool='".TOOL_FORUM_THREAD."'
                    GROUP BY threads.forum_id";
    // Select the number of posts of the forum (post that are visible and that are in a thread that is visible).
    $sql3 = "SELECT count(post_id) AS number_of_posts, posts.forum_id FROM $table_posts posts, $table_threads threads, ".$table_item_property." item_properties
            WHERE posts.visible=1 AND
            posts.c_id = $course_id AND
            item_properties.c_id = $course_id AND
            threads.c_id = $course_id
            AND posts.thread_id=threads.thread_id            
            AND threads.thread_id=item_properties.ref
            AND item_properties.visibility=1
            AND item_properties.tool='".TOOL_FORUM_THREAD."'
            GROUP BY threads.forum_id";

    //-------------- Course Admin  -----------------//
    if (is_allowed_to_edit()) {
        // Select all the forum information of all forums (that are not deleted).
        $sql = "SELECT * FROM ".$table_forums." forum , ".$table_item_property." item_properties
                    WHERE forum.forum_of_group = '".Database::escape_string($group_id)."' AND
                    forum.c_id = $course_id AND
                    item_properties.c_id = $course_id AND         
                    forum.forum_id=item_properties.ref AND
                    item_properties.visibility<>2 AND 
                    item_properties.tool='".TOOL_FORUM."' 
                    ORDER BY forum_order ASC";

        // Select the number of threads of the forums (only the threads that are not deleted).
        $sql2 = "SELECT count(thread_id) AS number_of_threads, threads.forum_id FROM $table_threads threads, ".$table_item_property." item_properties
                        WHERE threads.thread_id=item_properties.ref AND
                        threads.c_id = $course_id AND
                        item_properties.c_id = $course_id AND 
                        item_properties.visibility<>2 AND
                        item_properties.tool='".TOOL_FORUM_THREAD."'
                        GROUP BY threads.forum_id";
        // Select the number of posts of the forum.
        $sql3 = "SELECT count(post_id) AS number_of_posts, forum_id FROM $table_posts WHERE c_id = $course_id GROUP BY forum_id";

    }

    // Handling all the forum information.
    
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $forum_list[$row['forum_id']] = $row;
    }

    // Handling the threadcount information.
    $result2 = Database::query($sql2);
    while ($row2 = Database::fetch_array($result2, 'ASSOC')) {
        if (is_array($forum_list)) {
            if (array_key_exists($row2['forum_id'], $forum_list)) {
                $forum_list[$row2['forum_id']]['number_of_threads'] = $row2['number_of_threads'];
            }
        }
    }

    // Handling the postcount information.
    $result3 = Database::query($sql3);
    while ($row3 = Database::fetch_array($result3, 'ASSOC')) {
        if (is_array($forum_list)) {
            if (array_key_exists($row3['forum_id'], $forum_list)) { // This is needed because sql3 takes also the deleted forums into account.
                $forum_list[$row3['forum_id']]['number_of_posts'] = $row3['number_of_posts'];
            }
        }
    }

    // Finding the last post information (last_post_id, last_poster_id, last_post_date, last_poster_name, last_poster_lastname, last_poster_firstname).
    if (is_array($forum_list)) {
        foreach ($forum_list as $key => $value) {
            $last_post_info_of_forum		 		= get_last_post_information($key,is_allowed_to_edit());
            $forum_list[$key]['last_post_id']		= $last_post_info_of_forum['last_post_id'];
            $forum_list[$key]['last_poster_id']		= $last_post_info_of_forum['last_poster_id'];
            $forum_list[$key]['last_post_date']		= $last_post_info_of_forum['last_post_date'];
            $forum_list[$key]['last_poster_name']	= $last_post_info_of_forum['last_poster_name'];
            $forum_list[$key]['last_poster_lastname'] = $last_post_info_of_forum['last_poster_lastname'];
            $forum_list[$key]['last_poster_firstname'] = $last_post_info_of_forum['last_poster_firstname'];
        }
    }
    return $forum_list;
}

/**
 * This function stores which users have to be notified of which forums or threads
 *
 * @param string $content does the user want to be notified about a forum or about a thread
 * @param integer $id the id of the forum or thread
 * @return string language variable
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version May 2008, dokeos 1.8.5
 * @since May 2008, dokeos 1.8.5
 */
function set_notification($content,$id, $add_only = false) {
    global $_user;

    // Database table definition
    $table_notification = Database::get_course_table(TABLE_FORUM_NOTIFICATION);
    
    $course_id = api_get_course_int_id();

    // Which database field do we have to store the id in?
    if ($content == 'forum') {
        $database_field = 'forum_id';
    } else {
        $database_field = 'thread_id';
    }

    // First we check if the notification is already set for this.
    $sql = "SELECT * FROM $table_notification WHERE c_id = $course_id AND $database_field = '".Database::escape_string($id)."' AND user_id = '".Database::escape_string($_user['user_id'])."'";
    $result = Database::query($sql);
    $total = Database::num_rows($result);

    // If the user did not indicate that (s)he wanted to be notified already then we store the notification request (to prevent double notification requests).
    if ($total <= 0) {
        $sql = "INSERT INTO $table_notification (c_id, $database_field, user_id) VALUES (".$course_id.", '".Database::escape_string($id)."','".Database::escape_string($_user['user_id'])."')";
        $result = Database::query($sql);
        api_session_unregister('forum_notification');
        get_notifications_of_user(0, true);
        return get_lang('YouWillBeNotifiedOfNewPosts');
    } else {
        if (!$add_only) {
            $sql = "DELETE FROM $table_notification 
                    WHERE c_id = $course_id AND $database_field = '".Database::escape_string($id)."' AND user_id = '".Database::escape_string($_user['user_id'])."'";
            $result = Database::query($sql);
            api_session_unregister('forum_notification');
            get_notifications_of_user(0, true);
            return get_lang('YouWillNoLongerBeNotifiedOfNewPosts');
        }

    }
}

/**
 * This function retrieves all the email adresses of the users who wanted to be notified
 * about a new post in a certain forum or thread
 *
 * @param string $content does the user want to be notified about a forum or about a thread
 * @param integer $id the id of the forum or thread
 * @return array returns
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version May 2008, dokeos 1.8.5
 * @since May 2008, dokeos 1.8.5
 */
function get_notifications($content,$id) {
    // Database table definition
    $table_users		= Database :: get_main_table(TABLE_MAIN_USER);
    $table_notification = Database::get_course_table(TABLE_FORUM_NOTIFICATION);
    
    $course_id = api_get_course_int_id();

    // Which database field contains the notification?
    if ($content == 'forum') {
        $database_field = 'forum_id';
    } else {
        $database_field = 'thread_id';
    }

    $sql = "SELECT user.user_id, user.firstname, user.lastname, user.email, user.user_id user 
            FROM $table_users user, $table_notification notification
            WHERE notification.c_id = $course_id AND user.active = 1 AND 
            user.user_id = notification.user_id AND 
            notification.$database_field= '".Database::escape_string($id)."'";

    $result = Database::query($sql);
    $return = array();

    while ($row = Database::fetch_array($result)) {
        $return['user'.$row['user_id']]=array('email' => $row['email'], 'user_id' => $row['user_id']);
    }
    return $return;
}

/**
 * Get all the users who need to receive a notification of a new post (those subscribed to
 * the forum or the thread)
 *
 * @param integer $forum_id the id of the forum
 * @param integer $thread_id the id of the thread
 * @param integer $post_id the id of the post
 * @return bool
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version May 2008, dokeos 1.8.5
 * @since May 2008, dokeos 1.8.5
 */
function send_notifications($forum_id = 0, $thread_id = 0, $post_id = 0) {
    global $_course, $_user;

    // The content of the mail
    $email_subject = get_lang('NewForumPost').' - '.$_course['official_code'];
    $thread_link = api_get_path(WEB_CODE_PATH).'forum/viewthread.php?'.api_get_cidreq().'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&amp;forum='.$forum_id.'&amp;thread='.$thread_id;
    $my_link = isset($link) ? $link : '';
    $my_message = isset($message) ? $message : '';
    $my_message .= $my_link;

    // Users who subscribed to the forum
    if ($forum_id != 0) {
        $users_to_be_notified_by_forum = get_notifications('forum', $forum_id);
    } else {
        return false;
    }

    // User who subscribed to the thread
    if ($thread_id != 0) {
        $users_to_be_notified_by_thread = get_notifications('thread', $thread_id);
    }

    // Merging the two
    $users_to_be_notified = array_merge($users_to_be_notified_by_forum, $users_to_be_notified_by_thread);

    if (is_array($users_to_be_notified)) {
        foreach ($users_to_be_notified as $key => $value) {
            if ($value['email'] != $_user['email']) {
                $email_body = api_get_person_name($value['firstname'], $value['lastname'], null, PERSON_NAME_EMAIL_ADDRESS)."\n\r";
                $email_body .= '['.$_course['official_code'].'] - ['.$_course['name']."]<br />\n";
                $email_body .= get_lang('NewForumPost')."\n";
                $email_body .= get_lang('YouWantedToStayInformed')."<br /><br />\n";
                $email_body .= get_lang('ThreadCanBeFoundHere')." : <a href=\"".$thread_link."\">".$thread_link."</a>\n";
                @api_mail_html(api_get_person_name($value['firstname'], $value['lastname'], null, PERSON_NAME_EMAIL_ADDRESS), $value['email'], $email_subject, $email_body, api_get_person_name($_SESSION['_user']['firstName'], $_SESSION['_user']['lastName'], null, PERSON_NAME_EMAIL_ADDRESS), $_SESSION['_user']['mail']);
            }
        }
    }
}

/**
 * Get all the notification subscriptions of the user
 * = which forums and which threads does the user wants to be informed of when a new
 * post is added to this thread
 *
 * @param integer $user_id the user_id of a user (default = 0 => the current user)
 * @param boolean $force force get the notification subscriptions (even if the information is already in the session
 * @return array returns
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version May 2008, dokeos 1.8.5
 * @since May 2008, dokeos 1.8.5
 */
function get_notifications_of_user($user_id = 0, $force = false) {
    global $_course;

    // Database table definition
    $table_notification = Database::get_course_table(TABLE_FORUM_NOTIFICATION);        
    $course = api_get_course_id();
    $course_id = api_get_course_int_id();
    if (empty($course) || $course == -1) {
        return null;
    }
    if ($user_id == 0) {
        global $_user;
        $user_id = $_user['user_id'];
    }

    $my_code = isset($_course['code']) ? $_course['code'] : '';

    if (!isset($_SESSION['forum_notification']) || $_SESSION['forum_notification']['course'] != $my_code || $force = true) {
        $_SESSION['forum_notification']['course'] = $my_code;

        $sql = "SELECT * FROM $table_notification WHERE c_id = $course_id AND user_id='".Database::escape_string($user_id)."'";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            if (!is_null($row['forum_id'])) {
                $_SESSION['forum_notification']['forum'][] = $row['forum_id'];
            }
            if (!is_null($row['thread_id'])) {
                $_SESSION['forum_notification']['thread'][] = $row['thread_id'];
            }
        }
    }
}

/**
 * This function counts the number of post inside a thread
 * @param 	int Thread ID
 * @return	int the number of post inside a thread
 * @author Jhon Hinojosa <jhon.hinojosa@dokeos.com>,
 * @version octubre 2008, dokeos 1.8
 */
function count_number_of_post_in_thread($thread_id) {
    $table_posts 	= Database :: get_course_table(TABLE_FORUM_POST);
    $course_id = api_get_course_int_id();
    
    $sql = "SELECT * FROM $table_posts WHERE c_id = $course_id AND thread_id='".Database::escape_string($thread_id)."' ";
    $result = Database::query($sql);
    return count(Database::store_result($result));
}

/**
 * This function counts the number of post inside a thread user
 * @param 	int thread ID
 * @param 	int user ID
 * @return	int the number of post inside a thread user
 */
function count_number_of_post_for_user_thread($thread_id, $user_id) {
    $table_posts 	= Database::get_course_table(TABLE_FORUM_POST);
    $course_id = api_get_course_int_id();
    $sql = "SELECT count(*) as count FROM $table_posts 
            WHERE c_id = $course_id AND 
                  thread_id=".Database::escape_string($thread_id)." AND
                  poster_id = ".Database::escape_string($user_id)." AND visible = 1 ";
    $result = Database::query($sql);
    $count = 0;
    if (Database::num_rows($result) > 0) {
        $count = Database::fetch_array($result);        
        $count = $count['count'];
    }    
    return $count;
}

/**
 * This function counts the number of user register in course
 * @param 	int Course ID
 * @return	int the number of user register in course
 * @author Jhon Hinojosa <jhon.hinojosa@dokeos.com>,
 * @version octubre 2008, dokeos 1.8
 */
function count_number_of_user_in_course($course_id) {
    $table_course_rel_user = Database::get_main_table('course_rel_user');

    $sql = "SELECT * FROM $table_course_rel_user  WHERE course_code ='".Database::escape_string($course_id)."' ";
    $result = Database::query($sql);
    return count(Database::store_result($result));
}

/**
 * This function retrieves information of statistical
 * @param 	int Thread ID
 * @param 	int User ID
 * @param 	int Course ID
 * @return	array the information of statistical
 * @author Jhon Hinojosa <jhon.hinojosa@dokeos.com>,
 * @version octubre 2008, dokeos 1.8
 */
function get_statistical_information($thread_id, $user_id, $course_id) {
    $stadistic = array();
    $stadistic['user_course']	= count_number_of_user_in_course($course_id);
    $stadistic['post'] 			= count_number_of_post_in_thread($thread_id);
    $stadistic['user_post'] 	= count_number_of_post_for_user_thread($thread_id, $user_id);

    //$stadistic['average'] = get_average_of_thread_post_user();
    return $stadistic;
}

/**
 * This function return the posts inside a thread from a given user
 * @param 	course code
 * @param 	int Thread ID
 * @param 	int User ID
 * @return	int the number of post inside a thread
 * @author Jhon Hinojosa <jhon.hinojosa@dokeos.com>,
 * @version octubre 2008, dokeos 1.8
 */
function get_thread_user_post($course_code, $thread_id, $user_id ) {
    $table_posts =  Database::get_course_table(TABLE_FORUM_POST);
    $table_users =  Database::get_main_table(TABLE_MAIN_USER);
    $thread_id = intval($thread_id);
    $user_id = intval($user_id);
    $course_id = api_get_course_int_id();

    $sql = "SELECT * FROM $table_posts posts
            LEFT JOIN  $table_users users
                ON posts.poster_id=users.user_id
            WHERE
                posts.c_id = $course_id AND 
                posts.thread_id='$thread_id'
                AND posts.poster_id='$user_id'
            ORDER BY posts.post_id ASC";

    $result = Database::query($sql);

    while ($row = Database::fetch_array($result)) {
        $row['status'] = '1';
        $post_list[] = $row;
        $sql = "SELECT * FROM $table_posts posts
                    LEFT JOIN  $table_users users
                        ON posts.poster_id=users.user_id
                    WHERE
                        posts.c_id = $course_id AND 
                        posts.thread_id='$thread_id'
                        AND posts.post_parent_id='".$row['post_id']."'
                    ORDER BY posts.post_id ASC";
        $result2 = Database::query($sql);
        while ($row2 = Database::fetch_array($result2)) {
            $row2['status'] = '0';
            $post_list[] = $row2;
        }
    }
    return $post_list;
}

/**
 * This function get the name of an thread by id
 * @param int thread_id
 * @return String
 * @author Christian Fasanando
 * @author Julio Montoya <gugli100@gmail.com> Adding security
 */
function get_name_thread_by_id($thread_id) {
    $t_forum_thread = Database::get_course_table(TABLE_FORUM_THREAD);
    $course_id = api_get_course_int_id();
    $sql = "SELECT thread_title FROM ".$t_forum_thread." WHERE c_id = $course_id AND thread_id = '".intval($thread_id)."' ";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    return $row[0];
}

/**
 * This function gets all the post written by an user
 * @param int user id
 * @param string db course name
 * @return string
 */

function get_all_post_from_user($user_id, $course_code) {
    $j = 0;
    $forums = get_forums('', $course_code);
    krsort($forums);
    $forum_results = '';

     foreach ($forums as $forum) {
        if ($j <= 4) {
             $threads = get_threads($forum['forum_id'], $course_code);

             if (is_array($threads)) {
                 $my_course_code = CourseManager::get_course_id_by_database_name($course_code);
                 $i = 0;
                 $hand_forums = '';
                 $post_counter = 0;
                 foreach($threads as $thread) {
                     if ($i <= 4) {
                         $post_list = get_thread_user_post_limit($course_code, $thread['thread_id'], $user_id, 1);
                         $post_counter = count($post_list);
                         if (is_array($post_list) && count($post_list) > 0) {
                             $hand_forums.= '<div id="social-thread">';
                             $hand_forums.= Display::return_icon('thread.png', get_lang('Thread'), '', '32');
                             $hand_forums.= '&nbsp;'.Security::remove_XSS($thread['thread_title'], STUDENT);
                             $hand_forums.= '</div>';

                             foreach($post_list as $posts) {
                                 $hand_forums.= '<div id="social-post">';
                                 $hand_forums.= '<strong>'.Security::remove_XSS($posts['post_title'], STUDENT).'</strong>';
                                 $hand_forums.= '<br / >';
                                 $hand_forums.= Security::remove_XSS($posts['post_text'], STUDENT);
                                 $hand_forums.= '</div>';
                                 $hand_forums.= '<br / >';
                             }
                         }

                     }
                     $i++;
                 }
                 $forum_results .='<div id="social-forum">';
                 $forum_results .='<div class="clear"></div><br />';
                 $forum_results .='<div id="social-forum-title">'.
                                     Display::return_icon('forum.gif',get_lang('Forum')).'&nbsp;'.Security::remove_XSS($forum['forum_title'], STUDENT).
                                    '<div style="float:right;margin-top:-35px"><a href="../forum/viewforum.php?cidReq='.$my_course_code.'&amp;gidReq=&amp;forum='.$forum['forum_id'].' " >'.get_lang('SeeForum').'</a></div></div>';
                 $forum_results .='<br / >';
                 if ($post_counter > 0 ) {
                    $forum_results .=$hand_forums;
                 }
                $forum_results .='</div>';
             }$j++;
         }
     }
     return $forum_results;
}

/**
 * @param string
 * @param int
 * @param int
 * @param int
 * @return void
 */
function get_thread_user_post_limit($course_code, $thread_id, $user_id, $limit = 10) {
    $table_posts =  Database::get_course_table(TABLE_FORUM_POST);
    $table_users =  Database::get_main_table(TABLE_MAIN_USER);
    
    $course_info = api_get_course_info($course_code);
    $course_id = $course_info['real_id'];

    $sql = "SELECT * FROM $table_posts posts
            LEFT JOIN  $table_users users
                ON posts.poster_id=users.user_id
            WHERE
            	posts.c_id = $course_id AND 
            	posts.thread_id='".Database::escape_string($thread_id)."'
                AND posts.poster_id='".Database::escape_string($user_id)."'
            ORDER BY posts.post_id DESC LIMIT $limit ";
    $result = Database::query($sql);

    while ($row = Database::fetch_array($result)) {
        $row['status'] = '1';
        $post_list[] = $row;
    }
    return $post_list;
}

/**
 * This function builds an array of all the posts in a given thread where the key of the array is the post_id
 * It also adds an element children to the array which itself is an array that contains all the id's of the first-level children
 * @return an array containing all the information on the posts of a thread
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function calculate_children($rows) {
    foreach ($rows as $row) {
        $rows_with_children[$row['post_id']] = $row;
        $rows_with_children[$row['post_parent_id']]['children'][] = $row['post_id'];
    }

    $rows = $rows_with_children;
    $sorted_rows = array(0 => array());
    _phorum_recursive_sort($rows, $sorted_rows);
    unset($sorted_rows[0]);
    return $sorted_rows;
}

function _phorum_recursive_sort($rows, &$threads, $seed = 0, $indent = 0) {
    if ($seed > 0) {
        $threads[$rows[$seed]['post_id']] = $rows[$seed];
        $threads[$rows[$seed]['post_id']]['indent_cnt'] = $indent;
        $indent++;
    }

    if(isset($rows[$seed]['children'])) {
        foreach($rows[$seed]['children'] as $child) {
            _phorum_recursive_sort($rows, $threads, $child, $indent);
        }
    }
}
