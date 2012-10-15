<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.user
*/
/**
 * INIT SECTION
 */
// name of the language file that needs to be included
$language_file = array('registration','admin');
require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;

/**
 * MAIN CODE	
 */
api_protect_course_script();

if (api_get_setting('allow_user_course_subscription_by_course_admin') == 'false') {
    if (!api_is_platform_admin()) {
        api_not_allowed(true);
    }
}


$tool_name = get_lang("Classes");

$htmlHeadXtra[] = api_get_jqgrid_js();

//extra entries in breadcrumb
$interbreadcrumb[] = array ("url" => "user.php", "name" => get_lang("ToolUser"));

$type = isset($_GET['type']) ? Security::remove_XSS($_GET['type']) : 'registered';

Display :: display_header($tool_name, "User");

$usergroup = new UserGroup();

if (api_is_allowed_to_edit()) {
    echo '<div class="actions">';
    if ($type == 'registered') {
        echo '<a href="class.php?'.api_get_cidreq().'&type=not_registered">'.Display::return_icon('add.png', get_lang("AddClassesToACourse"), array(), ICON_SIZE_MEDIUM).'</a>';        
    } else {
        echo '<a href="class.php?'.api_get_cidreq().'&type=registered">'.Display::return_icon('empty_evaluation.png', get_lang("Classes"), array(), ICON_SIZE_MEDIUM).'</a>';
    }
    echo '</div>';
}

echo Display::page_header($tool_name);

if (api_is_allowed_to_edit()) {
    $action = isset($_GET['action']) ? $_GET['action'] : null;
    switch ($action) {
        case 'add_class_to_course':
            $id = $_GET['id'];
            if (!empty($id)) {
                $usergroup->subscribe_courses_to_usergroup($id, array(api_get_course_int_id()));
            }
            break;
        case 'remove_class_from_course':
            $id = $_GET['id'];            
            if (!empty($id)) {
                $usergroup->unsubscribe_courses_from_usergroup($id, array(api_get_course_int_id()));
            }
            break;
    }
}

//jqgrid will use this URL to do the selects


$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_usergroups_teacher&type='.$type;

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = array(get_lang('Name'), get_lang('Users'), get_lang('Actions'));

//Column config
$column_model = array(
                    array('name'=>'name',           'index'=>'name',        'width'=>'35',   'align'=>'left'),                        
                    array('name'=>'users',    		'index'=>'users', 		'width'=>'15',  'align'=>'left'),                                                
                    array('name'=>'actions',        'index'=>'actions',     'width'=>'20',  'align'=>'left','sortable'=>'false'),
);
//Autowidth
$extra_params['autowidth'] = 'true';
//height auto
$extra_params['height'] = 'auto';
$extra_params['rowList'] = array(50, 100, 500, 1000, 2000, 5000);


//With this function we can add actions to the jgrid
$action_links = 'function action_formatter (cellvalue, options, rowObject) {
                    return \''
                    .' <a href="class.php?action=add_class&id=\'+options.rowId+\'"><img src="../img/icons/22/user_to_class.png" title="'.get_lang('SubscribeUsersToClass').'"></a>'                    
                    .' <a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="?action=delete&id=\'+options.rowId+\'"><img title="'.get_lang('Delete').'" src="../img/delete.png"></a>\';
                 }';
?>
<script>
$(function() {
<?php
    // grid definition see the $usergroup>display() function
    echo Display::grid_js('usergroups',  $url, $columns, $column_model, $extra_params, array(), $action_links, true);
?>
});
</script>
<?php

$usergroup->display_teacher_view();

Display :: display_footer();
