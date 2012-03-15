<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */

// Language files that need to be included.
$language_file = array('admin');

$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'career.lib.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();


//Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => 'career_dashboard.php','name' => get_lang('CareersAndPromotions'));

$action = isset($_GET['action']) ? $_GET['action'] : null;

$check = Security::check_token('request');
$token = Security::get_token();    

if ($action == 'add') {
    $interbreadcrumb[]=array('url' => 'careers.php','name' => get_lang('Careers'));
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('Add'));
} elseif ($action == 'edit') {
    $interbreadcrumb[]=array('url' => 'careers.php','name' => get_lang('Careers'));    
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('Edit'));
} else {
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('Careers'));
}

// The header.
Display::display_header($tool_name);

//jqgrid will use this URL to do the selects
$url            = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_careers';

//The order is important you need to check the the $column variable in the model.ajax.php file 
$columns        = array(get_lang('Name'), get_lang('Description'), get_lang('Actions'));

//Column config
$column_model   = array(
                        array('name'=>'name',           'index'=>'name',        'width'=>'80',   'align'=>'left'),
                        array('name'=>'description',    'index'=>'description', 'width'=>'500',  'align'=>'left','sortable'=>'false'),
                        array('name'=>'actions',        'index'=>'actions',     'width'=>'100',  'align'=>'left','formatter'=>'action_formatter','sortable'=>'false')
                       );            
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
?>
<script>
$(function() {
<?php 
    // grid definition see the $career->display() function
    echo Display::grid_js('careers',  $url,$columns,$column_model,$extra_params, array(), $action_links,true);       
?> 
});
</script>
<?php
$career = new Career();

// Action handling: Add
switch ($action) {
    case 'add':
        if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
            api_not_allowed();
        }
        $_SESSION['notebook_view'] = 'creation_date';

        $url  = api_get_self().'?action='.Security::remove_XSS($_GET['action']);
        $form = $career->return_form($url, 'add');

        // The validation or display
        if ($form->validate()) {            
            if ($check) {
                $values = $form->exportValues();       
                $res    = $career->save($values);            
                if ($res) {
                    Display::display_confirmation_message(get_lang('ItemAdded'));
                }
            }        
            $career->display();
        } else {
            echo '<div class="actions">';
            echo '<a href="'.api_get_self().'">'.Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM).'</a>';
            echo '</div>';            
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(array('sec_token' => $token));
            $form->display();
        }
        break;
    case 'edit':
        // Action handling: Editing 
        $url  = api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&id='.intval($_GET['id']);
        $form = $career->return_form($url, 'edit');    

        // The validation or display
        if ($form->validate()) {            
            if ($check) {
                $values = $form->exportValues();
                $career->update_all_promotion_status_by_career_id($values['id'],$values['status']);               
                $res    = $career->update($values);
                if ($values['status']) {
                    Display::display_confirmation_message(sprintf(get_lang('CareerXUnarchived'), $values['name']), false);
                } else {
                    Display::display_confirmation_message(sprintf(get_lang('CareerXArchived'), $values['name']), false);
                }
            }            
            $career->display();
        } else {
            echo '<div class="actions">';
            echo '<a href="'.api_get_self().'">'.Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM).'</a>';
            echo '</div>';
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(array('sec_token' => $token));
            $form->display();
        }
        break;
    case 'delete':
        // Action handling: delete
        if ($check) {
            $res = $career->delete($_GET['id']);
            if ($res) {
                Display::display_confirmation_message(get_lang('ItemDeleted'));
            }
        }
        $career->display();
        break;
    case 'copy':        
        if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
            api_not_allowed();
        }
        if ($check) {
            $res = $career->copy($_GET['id'], true); //copy career and promotions inside
            if ($res) {
                Display::display_confirmation_message(get_lang('ItemCopied'));
            }
        }
        $career->display();
        break;
    default:
        $career->display();   
        break;
}
Display :: display_footer();