<?php
/* For licensing terms, see /license.txt */

/**
 * @todo use a controller
 *  @package chamilo.admin
 */

// Language files that need to be included.
$language_file = array('admin');

$cidReset = true;
////require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);

//Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));
$action = isset($_GET['action']) ? $_GET['action'] : null;
if ($action == 'add') {
    $interbreadcrumb[]=array('url' => 'usergroups.php','name' => get_lang('Classes'));
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('Add'));
} elseif ($action == 'edit') {
    $interbreadcrumb[]=array('url' => 'usergroups.php','name' => get_lang('Classes'));
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('Edit'));
} else {
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('Classes'));
}

// The header.
Display::display_header();

// Tool name
if (isset($_GET['action']) && $_GET['action'] == 'add') {
    $tool = 'Add';
    $interbreadcrumb[] = array ('url' => api_get_self(), 'name' => get_lang('Group'));
}
if (isset($_GET['action']) && $_GET['action'] == 'editnote') {
    $tool = 'Modify';
    $interbreadcrumb[] = array ('url' => api_get_self(), 'name' => get_lang('Group'));
}

//jqgrid will use this URL to do the selects

$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_usergroups';

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = array(get_lang('Name'), get_lang('Users'), get_lang('Courses'), get_lang('Sessions'), get_lang('Type'), get_lang('Actions'));

//Column config
$column_model   = array(
    array('name'=>'name',           'index'=>'name',        'width'=>'35',   'align'=>'left'),
    //array('name'=>'description',    'index'=>'description', 'width'=>'500',  'align'=>'left'),
    array('name'=>'users',    		'index'=>'users', 		'width'=>'15',  'align'=>'left'),
    array('name'=>'courses',    	'index'=>'courses', 	'width'=>'15',  'align'=>'left'),
    array('name'=>'sessions',    	'index'=>'sessions', 	'width'=>'15',  'align'=>'left'),
    array('name'=>'group_type',    	'index'=>'group_type', 	    'width'=>'15',  'align'=>'left'),
    array('name'=>'actions',        'index'=>'actions',     'width'=>'20',  'align'=>'left','sortable'=>'false','formatter'=>'action_formatter'),
);

//Autowidth
$extra_params['autowidth'] = 'true';
//height auto
$extra_params['height'] = 'auto';

//With this function we can add actions to the jgrid
$action_links = 'function action_formatter (cellvalue, options, rowObject) {
return \''
.' <a href="add_users_to_usergroup.php?id=\'+options.rowId+\'">'.Display::return_icon('user_to_class.png', get_lang('SubscribeUsersToClass')).'</a>'
.' <a href="add_courses_to_usergroup.php?id=\'+options.rowId+\'">'.Display::return_icon('course_to_class.png', get_lang('SubscribeClassToCourses')).'</a>'
.' <a href="add_sessions_to_usergroup.php?id=\'+options.rowId+\'">'.Display::return_icon('sessions_to_class.png', get_lang('SubscribeClassToSessions')).'</a>'
.' <a href="?action=edit&id=\'+options.rowId+\'">'.Display::return_icon('edit.png', get_lang('Edit')).'</a>'
.' <a onclick="javascript:if(!confirm('."\'".addslashes(get_lang("ConfirmYourChoice"))."\'".')) return false;" href="?action=delete&id=\'+options.rowId+\'">'.Display::return_icon('delete.png', get_lang('Delete')).'</a>\';
}';
?>
<script>
$(function() {
<?php
    // grid definition see the $usergroup>display() function
    echo Display::grid_js('usergroups',  $url,$columns,$column_model,$extra_params, array(), $action_links,true);
?>
});
</script>
<?php
// Tool introduction
Display::display_introduction_section(get_lang('Classes'));

$usergroup = new UserGroup();

// Action handling: Adding a note
if (isset($_GET['action']) && $_GET['action'] == 'add') {
    if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
        api_not_allowed();
    }

    //@todo move this in the .lib.php

    // Initiate the object
    $form = new FormValidator('note', 'post', api_get_self().'?action='.Security::remove_XSS($_GET['action']));
    $usergroup->setForm($form, 'add');

    // The validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();
            $res = $usergroup->save($values);
            if ($res) {
                Display::display_confirmation_message(get_lang('ItemAdded'));
            }
        }
        Security::clear_token();
        $usergroup->display();
    } else {
        echo '<div class="actions">';
        echo '<a href="'.api_get_self().'">'.Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM).'</a>';
        echo '</div>';
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(array('sec_token' => $token));
        $form->display();
    }
}// Action handling: Edition
elseif (isset($_GET['action']) && $_GET['action'] == 'edit' && is_numeric($_GET['id'])) {
    // Initialize the object
    $form = new FormValidator('usergroup', 'post', api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&id='.Security::remove_XSS($_GET['id']));
    $defaults = $usergroup->get($_GET['id']);

    $usergroup->setForm($form, 'edit', $defaults);

    // Setting the form elements
    $form->addElement('hidden', 'id', intval($_GET['id']));

    // Setting the defaults
    $form->setDefaults($defaults);

    // The validation or display
    $form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');

    // The validation or display.
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();
            $res = $usergroup->update($values);
            if ($res) {
                Display::display_confirmation_message(get_lang('Updated'));
            }
        }
        Security::clear_token();
        $usergroup->display();
    } else {
        echo '<div class="actions">';
        echo '<a href="'.api_get_self().'">'.Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM).'</a>';
        echo '</div>';
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(array('sec_token' => $token));
        $form->display();
    }
} elseif (isset($_GET['action']) && $_GET['action'] == 'delete' && is_numeric($_GET['id'])) {
    $res = $usergroup->delete(Security::remove_XSS($_GET['id']));
    if ($res) {
        Display::display_confirmation_message(get_lang('Deleted'));
    }
    $usergroup->display();
} else {
    $usergroup->display();
}
Display :: display_footer();
