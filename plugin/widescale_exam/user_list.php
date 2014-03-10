<?php
/* See license terms in /license.txt */

//require_once '../../main/inc/global.inc.php';

//Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

$allowed = api_is_platform_admin() || api_is_drh();

if (!$allowed) {
    api_not_allowed(true);
}

Display::display_header();

//jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_user_list_plugin_widescale';

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns  = array(get_lang('Username'), get_lang('Firstname'), get_lang('Lastname'), get_lang('Password'));

//Column config
$column_model   = array(
    array('name'=>'username',    'index'=>'username',        'width'=>'100',   'align'=>'left'),
    array('name'=>'firstname',    'index'=>'firstname',        'width'=>'100',   'align'=>'left'),
    array('name'=>'lastname',    'index'=>'lastname', 'width'=>'100',  'align'=>'left'),
    array('name'=>'exam_password',    'index'=>'exam_password', 'width'=>'100',  'align'=>'left','sortable'=>'false'),
    //array('name'=>'actions',        'index'=>'actions',     'width'=>'100',  'align'=>'left','formatter'=>'action_formatter','sortable'=>'false')
);
//Autowidth
$extra_params['autowidth'] = 'true';
//height auto
$extra_params['height'] = 'auto';

//With this function we can add actions to the jgrid (edit, delete, etc)
/*
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
     return \'<a href="?action=edit&id=\'+options.rowId+\'">'.Display::return_icon('edit.png',get_lang('Edit'),'',ICON_SIZE_SMALL).'</a>'.
     '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=copy&id=\'+options.rowId+\'">'.Display::return_icon('copy.png',get_lang('Copy'),'',ICON_SIZE_SMALL).'</a>'.
     '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=delete&id=\'+options.rowId+\'">'.Display::return_icon('delete.png',get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>'.
     '\';
 }';*/
$action_links = null;

$room = UserManager::get_extra_user_data_by_field(api_get_user_id(), 'exam_room');
$room = $room['exam_room'];
$schedule = UserManager::get_extra_user_data_by_field(api_get_user_id(), 'exam_schedule');
$schedule = $schedule['exam_schedule'];

echo Display::page_subheader(get_lang('UserList').": ".$room." - ".$schedule);

?>
<script>
$(function() {
<?php
    echo Display::grid_js('user_list',  $url, $columns, $column_model, $extra_params, array(), $action_links, true);
?>
    jQuery("#user_list").jqGrid("navGrid","#user_list_pager",{view:false, edit:false, add:false, del:false, search:false, excel:true});
    jQuery("#user_list").jqGrid("navButtonAdd","#user_list_pager",{
       caption:"",
       onClickButton : function () {
           jQuery("#user_list").jqGrid("excelExport",{"url": "<? echo $url?>&export_format=xls"});
    }
});

});
</script>
<?php

echo Display::grid_html('user_list');

Display :: display_footer();
