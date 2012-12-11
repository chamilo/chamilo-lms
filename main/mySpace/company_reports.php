<?php

$language_file = array('admin');

$cidReset = true;
require_once '../inc/global.inc.php';

$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('MySpace'));
//$interbreadcrumb[] = array ("url" => 'user_list.php', "name" => get_lang('Report'));
$tool_name = get_lang('Report');

$this_section = SECTION_TRACKING;

$htmlHeadXtra[] = api_get_jqgrid_js();


//jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_user_course_report';

$extra_fields = UserManager::get_extra_fields(0, 100, null, null, true, true);


//The order is important you need to check the the $column variable in the model.ajax.php file
$columns        = array(get_lang('Course'), get_lang('User'), get_lang('TotalTime'), get_lang('Certificate'), get_lang('Score'));

//Column config
$column_model   = array(
                        array('name'=>'course',     'index'=>'title',   'width'=>'120', 'align'=>'left'),
                        array('name'=>'user',       'index'=>'user',    'width'=>'120', 'align'=>'left','sortable'=>'false'),
                        array('name'=>'time',       'index'=>'time',    'width'=>'50',  'align'=>'left','sortable'=>'false'),
                        array('name'=>'status',     'index'=>'status',  'width'=>'50',  'align'=>'left','sortable'=>'false'),
                        array('name'=>'score',      'index'=>'score',   'width'=>'50',  'align'=>'left','sortable'=>'false'),
);

if (!empty($extra_fields)) {
    foreach($extra_fields as $extra) {
        $col = array(
            'name' => $extra['1'],
            'index'=> $extra['1'],
            'width'=>'120',
            'sortable'=>'false'
        );
        $column_model[] = $col;

        $columns[] = $extra['3'];
    }
}

//Autowidth
$extra_params['autowidth'] = 'true';
//height auto
$extra_params['height'] = 'auto';


//With this function we can add actions to the jgrid (edit, delete, etc)
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
                         return \'<a href="?action=edit&id=\'+options.rowId+\'">'.Display::return_icon('edit.png',get_lang('Edit'),'',ICON_SIZE_SMALL).'</a>'.
                         '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=copy&id=\'+options.rowId+\'">'.Display::return_icon('copy.png',get_lang('Copy'),'',ICON_SIZE_SMALL).'</a>'.
                         '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=delete&id=\'+options.rowId+\'">'.Display::return_icon('delete.png',get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>'.
                         '\';
                 }';

$htmlHeadXtra[] = '<script>
$(function() {
    '.Display::grid_js('user_course_report',  $url,$columns,$column_model,$extra_params, array(), $action_links, true).'

    jQuery("#user_course_report").jqGrid("navGrid","#user_course_report_pager",{view:false, edit:false, add:false, del:false, search:false, excel:true});
    jQuery("#user_course_report").jqGrid("navButtonAdd","#user_course_report_pager",{
           caption:"",
           onClickButton : function () {
               jQuery("#user_course_report").jqGrid("excelExport",{"url":"'.$url.'"});
           }
    });

});




</script>';
$content = Display::grid_html('user_course_report');

$tpl = new Template($tool_name);
//$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();