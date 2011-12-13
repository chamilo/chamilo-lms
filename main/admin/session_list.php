<?php
/* For licensing terms, see /license.txt */

//@todo fix sort in this table or use jqgrid

$language_file = 'admin';
$cidReset = true;

require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);

//Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jquery_ui_js(true);

$action=$_REQUEST['action'];
$idChecked = $_REQUEST['idChecked'];

if ($action == 'delete') {
	SessionManager::delete_session($idChecked);
	header('Location: '.api_get_self());
	exit();
} elseif ($action == 'copy') {
	SessionManager::copy_session($idChecked);
    header('Location: '.api_get_self());
    exit();
}

$interbreadcrumb[]=array("url" => "index.php","name" => get_lang('PlatformAdmin'));


//table for the search

$tool_name = get_lang('SessionList');
Display::display_header($tool_name);



//jqgrid will use this URL to do the selects

$url            = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions';

//The order is important you need to check the the $column variable in the model.ajax.php file 
$columns        = array(get_lang('Name'), get_lang('NumberOfCourses'), get_lang('SessionCategoryName'), get_lang('StartDate'), get_lang('EndDate'), get_lang('Coach'),  get_lang('Visibility'), get_lang('Actions'));

//Column config
$column_model   = array(
                        array('name'=>'name',           'index'=>'s.name',          'width'=>'120',  'align'=>'left', 'search' => 'true'),                        
                        array('name'=>'nbr_courses',    'index'=>'s.nbr_courses',   'width'=>'30',   'align'=>'left', 'search' => 'true'),
                        array('name'=>'category_name',  'index'=>'sc.name',         'width'=>'80',   'align'=>'left', 'search' => 'true'),
                        array('name'=>'date_start',     'index'=>'s.date_start',    'width'=>'40',   'align'=>'left', 'search' => 'true'),
                        array('name'=>'date_end',       'index'=>'s.date_end',      'width'=>'40',   'align'=>'left', 'search' => 'true'),
                        array('name'=>'coach_name',     'index'=>'coach_name',      'width'=>'80',   'align'=>'left', 'search' => 'false'),                        
                        array('name'=>'visibility',     'index'=>'visibility',      'width'=>'40',   'align'=>'left', 'search' => 'false'),                        
                        array('name'=>'actions',        'index'=>'actions',         'width'=>'100',  'align'=>'left','formatter'=>'action_formatter','sortable'=>'false', 'search' => 'false')
                       );            
//Autowidth             
$extra_params['autowidth'] = 'true';
//height auto 
$extra_params['height'] = 'auto';

$extra_params['rowList'] = array(10, 20 ,30);


//With this function we can add actions to the jgrid (edit, delete, etc)
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
                         return \'<a href="resume_session.php?id_session=\'+options.rowId+\'">'.Display::return_icon('edit.png',get_lang('Edit'),'',22).'</a>'.
                         '&nbsp;<a href="add_users_to_session.php?page=session_list.php&id_session=\'+options.rowId+\'">'.Display::return_icon('user_subscribe_session.png',get_lang('SubscribeUsersToSession'),'',22).'</a>'.
                         '&nbsp;<a href="add_courses_to_session.php?page=session_list.php&id_session=\'+options.rowId+\'">'.Display::return_icon('courses_to_session.png',get_lang('SubscribeCoursesToSession'),'',22).'</a>'.
                         '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="session_list.php?action=copy&idChecked=\'+options.rowId+\'">'.Display::return_icon('copy.png',get_lang('Copy'),'',22).'</a>'.
                         '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="session_list.php?action=delete&idChecked=\'+options.rowId+\'">'.Display::return_icon('delete.png',get_lang('Delete'),'',22).'</a>'.
                         '\'; 
                 }';
?>
<script>
$(function() {
    <?php 
        echo Display::grid_js('sessions', $url,$columns,$column_model,$extra_params, array(), $action_links,true);      
    ?>

$("#sessions").jqGrid('navGrid','#sessions_pager', {edit:false,add:false,del:false},
    {height:280,reloadAfterSubmit:false}, // edit options 
    {height:280,reloadAfterSubmit:false}, // add options 
    {reloadAfterSubmit:false}, // del options 
    {width:500} // search options
);

});
</script>

<div class="actions">
<?php 
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_add.php">'.Display::return_icon('new_session.png',get_lang('AddSession'),'','32').'</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/add_many_session_to_category.php">'.Display::return_icon('session_to_category.png',get_lang('AddSessionsInCategories'),'','32').'</a>';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/session_category_list.php">'.Display::return_icon('folder.png',get_lang('ListSessionCategory'),'','32').'</a>';
echo '</div>';

echo Display::grid_html('sessions');
Display::display_footer();