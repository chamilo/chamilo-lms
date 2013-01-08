<?php

use \ChamiloSession as Session;

$language_file = array('exercice','tracking');

// including the global library
require_once '../inc/global.inc.php';

// Access control
api_protect_course_script(true);

$course_id = api_get_course_int_id();

//Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

$interbreadcrumb[] = array("url" => "exercice.php", "name" => get_lang('Exercices'));
Display::display_header(get_lang('Media'));

$action = isset($_GET['action']) ? $_GET['action'] : null;

$page_url = "media.php?".api_get_cidreq();

$token = Security::get_token();

switch ($action) {
    case 'add':
        $url  = $page_url.'&action='.Security::remove_XSS($_GET['action']);
        $objQuestion = Question::getInstance(MEDIA_QUESTION);
        $form = new FormValidator('question_admin_form', 'post' , $url);
        // question form elements
        $objQuestion->createForm($form);
        $objQuestion->createAnswersForm($form);
        if ($form->validate()) {
            // question
            $objQuestion->processCreation($form, null);
            // answers
            $objQuestion->processAnswersCreation($form, null);
            header('Location: '.$page_url);
            exit;
        }
        $form->display();
        break;
    case 'edit':
        $url  = $page_url.'&action='.Security::remove_XSS($_GET['action']).'&id='.intval($_GET['id']);
        $objQuestion = Question::read($_GET['id']);

        $form = new FormValidator('question_admin_form','post', $url);
        // question form elements
        $objQuestion->createForm($form);
        $objQuestion->createAnswersForm($form);
        $form->addElement('hidden', 'id', intval($_GET['id']));
        $defaults = array();
		$defaults['questionName']           = $objQuestion->question;
		$defaults['questionDescription']    = $objQuestion->description;
		$defaults['questionLevel']          = $objQuestion->level;
		$defaults['questionCategory']       = $objQuestion->category_list;
        $defaults['parent_id']              = $objQuestion->parent_id;
        $form->setDefaults($defaults);
        $form->display();

        if ($form->validate()) {
            // question
            $objQuestion->processCreation($form, null);
            // answers
            $objQuestion->processAnswersCreation($form, null);
        }
        break;
    case 'delete':
        $objQuestion = Question::read($_GET['id']);
        $objQuestion->delete();
        break;
}

//jqgrid will use this URL to do the selects
$url            = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_course_exercise_medias';

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns        = array(get_lang('Name'), get_lang('Actions'));

//Column config
$column_model   = array(
                        array('name'=>'name',           'index'=>'name',        'width'=>'80',   'align'=>'left'),
                        array('name'=>'actions',        'index'=>'actions',     'width'=>'100',  'align'=>'left','formatter'=>'action_formatter','sortable'=>'false')
                       );
//Autowidth
$extra_params['autowidth'] = 'true';
//height auto
$extra_params['height'] = 'auto';

//With this function we can add actions to the jgrid (edit, delete, etc)
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
                         return \'<a href="?action=edit&id=\'+options.rowId+\'">'.Display::return_icon('edit.png',get_lang('Edit'),'',ICON_SIZE_SMALL).'</a>'.
                         '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=delete&id=\'+options.rowId+\'">'.Display::return_icon('delete.png',get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>'.
                         '\';
                 }';
?>
<script>
$(function() {
<?php
    // grid definition see the $career->display() function
    echo Display::grid_js('medias',  $url, $columns, $column_model, $extra_params, array(), $action_links, true);
?>
});
</script>
<?php
$items = array(
    array('content' => Display::return_icon('add.png'), 'url' => $page_url.'&action=add')
);
echo Display::actions($items);
echo Display::grid_html('medias');