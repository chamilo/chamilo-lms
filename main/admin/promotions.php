<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */

// Language files that need to be included.
$language_file = array('admin');

$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'promotion.lib.php';
require_once api_get_path(LIBRARY_PATH).'career.lib.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();
// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));

// The header.
Display::display_header($tool_name);

// Tool name
if (isset($_GET['action']) && $_GET['action'] == 'add') {
    $tool = 'Add';
    $interbreadcrumb[] = array ('url' => api_get_self(), 'name' => get_lang('Promotion'));
}
if (isset($_GET['action']) && $_GET['action'] == 'edit') {
    $tool = 'Modify';
    $interbreadcrumb[] = array ('url' => api_get_self(), 'name' => get_lang('Promotion'));
}

$url            = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_promotions';
//The order is important you need to check the model.ajax.php the $column variable
$columns        = array(get_lang('Name'),get_lang('Career'),get_lang('Description'),get_lang('Actions'));
$column_model   = array(array('name'=>'name',           'index'=>'name',        'width'=>'80',   'align'=>'left'),
                        array('name'=>'career',         'index'=>'career',      'width'=>'100',  'align'=>'left'),
                        array('name'=>'description',    'index'=>'description', 'width'=>'500',  'align'=>'left'),
                        array('name'=>'actions',        'index'=>'actions',     'formatter'=>'action_formatter','width'=>'100',  'align'=>'left'),
                       );                        
$extra_params['autowidth'] = 'true'; //use the width of the parent
//$extra_params['editurl'] = $url; //use the width of the parent

$extra_params['height'] = 'auto'; //use the width of the parent
//With this function we can add actions to the jgrid
$action_links = 'function action_formatter (cellvalue, options, rowObject) {
                    return \'<a href="add_sessions_to_promotion.php?id=\'+options.rowId+\'"><img title="'.get_lang('AddSession').'" src="../img/addd.gif"></a> <a href="?action=edit&id=\'+options.rowId+\'"><img src="../img/edit.gif" title="'.get_lang('Edit').'"></a> <a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="?action=delete&id=\'+options.rowId+\'"><img title="'.get_lang('Delete').'" src="../img/delete.gif"></a>\'; 
                 }';

?>
<script>
$(function() {    
    <?php 
         echo Display::grid_js('promotions',  $url,$columns,$column_model,$extra_params,array(), $action_links);       
    ?> 

});
</script>   
<?php
// Tool introduction
Display::display_introduction_section(get_lang('Promotions'));

$promotion = new Promotion();

// Action handling: Adding a note
if (isset($_GET['action']) && $_GET['action'] == 'add') {
    if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
        api_not_allowed();
    }

    // Initiate the object
    $form = new FormValidator('note', 'post', api_get_self().'?action='.Security::remove_XSS($_GET['action']));
    // Settting the form elements
    $form->addElement('header', '', get_lang('Add'));
    $form->addElement('text', 'name', get_lang('name'), array('size' => '95', 'id' => 'name'));
    
    $career = new Career();
    $careers = $career->get_all();
    $career_list = array();
    
    foreach($careers as $item) {        
        $career_list[$item['id']] = $item['name'];
    }
    $form->addElement('select', 'career_id', get_lang('Career'), $career_list);    
    $form->addElement('html_editor', 'description', get_lang('Description'), null);
    $form->addElement('style_submit_button', 'submit', get_lang('Add'), 'class="add"');
    // Setting the rules
    $form->addRule('name', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');

    // The validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();       
            $res    = $promotion->save($values);            
            if ($res) {
                Display::display_confirmation_message(get_lang('Added'));
            }
        }
        Security::clear_token();
        $promotion->display();
    } else {
        echo '<div class="actions">';
        echo '<a href="'.api_get_self().'">'.Display::return_icon('back.png').' '.get_lang('Back').'</a>';
        echo '</div>';
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(array('sec_token' => $token));
        $form->display();
    }
}// Action handling: Editing a note
elseif (isset($_GET['action']) && $_GET['action'] == 'edit' && is_numeric($_GET['id'])) {
    // Initialize the object
    //@todo this form should be generated in the class
    $form = new FormValidator('promotion', 'post', api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&id='.intval($_GET['id']));
    // Settting the form elements
    $form->addElement('header', '', get_lang('Modify'));
    $form->addElement('hidden', 'id',intval($_GET['id']));
    $form->addElement('text', 'name', get_lang('Name'), array('size' => '100'));
    $form->addElement('html_editor', 'description', get_lang('description'), null);
        
    $career = new Career();
    $careers = $career->get_all();
    $career_list = array();    
    foreach($careers as $item) {        
        $career_list[$item['id']] = $item['name'];
    }
    $form->addElement('select', 'career_id', get_lang('Career'), $career_list);  
     
    $form->addElement('style_submit_button', 'submit', get_lang('Modify'), 'class="save"');

    // Setting the defaults
    $defaults = $promotion->get($_GET['id']);    
    $form->setDefaults($defaults);

    // Setting the rules
    $form->addRule('name', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');

    // The validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();                    
            $res = $promotion->update($values);
            if ($res) {
                Display::display_confirmation_message(get_lang('Updated'));
            }
        }
        Security::clear_token();
        $promotion->display();
    } else {
        echo '<div class="actions">';
        echo '<a href="'.api_get_self().'">'.Display::return_icon('back.png').' '.get_lang('Back').'</a>';
        echo '</div>';
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(array('sec_token' => $token));
        $form->display();
    }
}

// Action handling: deleting a note
elseif (isset($_GET['action']) && $_GET['action'] == 'delete' && is_numeric($_GET['id'])) {
    $res = $promotion->delete($_GET['id']);
    if ($res) {
        Display::display_confirmation_message(get_lang('Deleted'));
    }
    $promotion->display();
} else {
    $promotion->display();   
}
Display::display_footer();
