<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */

// Language files that need to be included.
$language_file = array('admin');

$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'skill.lib.php';
require_once api_get_path(LIBRARY_PATH).'gradebook.lib.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jquery_ui_js(true);

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'display';


// setting breadcrumbs

$tool_name = get_lang('SkillGradebook');
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));
if ($action == 'add_skill') {
    $interbreadcrumb[]=array('url' => 'skills_gradebook.php','name' => get_lang('SkillGradebook'));
    $tool_name = get_lang('Add');    
}


Display::display_header($tool_name);




//jqgrid will use this URL to do the selects

$url            = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_gradebooks';

//The order is important you need to check the the $column variable in the model.ajax.php file 
$columns        = array(get_lang('Name'), get_lang('Skills'), get_lang('Actions'));

//Column config
$column_model   = array(
                        array('name'=>'name',           'index'=>'name',    'width'=>'200', 'align'=>'left'),
                        array('name'=>'skills',         'index'=>'skills',  'width'=>'300', 'align'=>'left','sortable'=>'false'),
                        array('name'=>'actions',        'index'=>'actions', 'width'=>'100', 'align'=>'left','formatter'=>'action_formatter','sortable'=>'false')
                       );            
//Autowidth             
$extra_params['autowidth'] = 'true';
//height auto 
$extra_params['height'] = 'auto'; 

//With this function we can add actions to the jgrid (edit, delete, etc)
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
                         return \'<a href="?action=add_skill&id=\'+options.rowId+\'">'.Display::return_icon('addd.gif', get_lang('AddSkill'),'',22).'</a>'.
                         '\'; 
                 }';
?>
<script>
$(function() {
<?php 
    // grid definition see the $career->display() function
    echo Display::grid_js('gradebooks',  $url,$columns,$column_model,$extra_params, array(), $action_links,true);       
?> 
});
</script>
<?php
$gradebook = new Gradebook();
 
switch($action) {
    case 'display':
        $gradebook->display();
        break;
    case 'add_skill':
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
        $gradebook_info = $gradebook->get($id);
        $url  = api_get_self().'?action='.$action.'&id='.$id;
        $form =  $gradebook->show_skill_form($id, $url, $gradebook_info['name']);
        if ($form->validate()) {
            $values = $form->exportValues();
            $res    = $gradebook->update_skills_to_gradebook($values['id'], $values['skill']);            
            if ($res) {
                Display::display_confirmation_message(get_lang('ItemAdded'));
            }
        }        
        $form->display();
        //echo Display::tag('h2',$gradebook_info['name']);
        break;
}
Display::display_footer();
