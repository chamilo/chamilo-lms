<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

if (api_get_configuration_value('allow_exercise_categories') === false) {
    api_not_allowed();
}

api_protect_course_script();

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Exercises'),
];

$courseId = api_get_course_int_id();

$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_exercise_categories&c_id='.$courseId.'&'.api_get_cidreq();
$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';

$obj = new ExerciseCategoryManager();

$check = Security::check_token('request');
$token = Security::get_token();

//Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [
    get_lang('Name'),
    get_lang('Actions'),
];

// Column config
$column_model = [
    [
        'name' => 'name',
        'index' => 'name',
        'width' => '140',
        'align' => 'left',
    ],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '40',
        'align' => 'left',
        'formatter' => 'action_formatter',
        'sortable' => 'false',
    ],
];

// Autowidth
$extra_params['autowidth'] = 'true';
// height auto
$extra_params['height'] = 'auto';

$action_links = $obj->getJqgridActionLinks($token);

$htmlHeadXtra[] = '<script>
$(function() {
    // grid definition see the $obj->display() function
    '.Display::grid_js(
        'categories',
        $url,
        $columns,
        $column_model,
        $extra_params,
        [],
        $action_links,
        true
    ).'
});
</script>';

$url = api_get_self().'?'.api_get_cidreq();

switch ($action) {
    case 'add':
        $interbreadcrumb[] = ['url' => $url, 'name' => get_lang('Categories')];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Add')];
        $form = $obj->return_form($url.'&action=add', 'add');
        // The validation or display
        if ($form->validate()) {
            $values = $form->exportValues();
            unset($values['id']);
            $res = $obj->save($values);
            if ($res) {
                Display::addFlash(Display::return_message(get_lang('ItemAdded'), 'confirmation'));
            }
            header('Location: '.$url);
            exit;
        } else {
            $content = '<div class="actions">';
            $content .= '<a href="'.$url.'">'.
                Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).'</a>';
            $content .= '</div>';
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(['sec_token' => $token]);
            $content .= $form->returnForm();
        }
        break;
    case 'edit':
        $interbreadcrumb[] = ['url' => $url, 'name' => get_lang('Categories')];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Edit')];
        $form = $obj->return_form($url.'&action=edit&id='.intval($_GET['id']), 'edit');

        // The validation or display
        if ($form->validate()) {
            $values = $form->exportValues();
            $res = $obj->update($values);
            if ($res) {
                Display::addFlash(Display::return_message(get_lang('ItemUpdated'), 'confirmation'));
            }
            header('Location: '.$url);
            exit;
        } else {
            $content = '<div class="actions">';
            $content .= '<a href="'.$url.'">'.
                Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).'</a>';
            $content .= '</div>';
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(['sec_token' => $token]);
            $content .= $form->returnForm();
        }
        break;
    case 'delete':
        $res = $obj->delete($_GET['id']);
        if ($res) {
            Display::addFlash(Display::return_message(get_lang('ItemDeleted'), 'confirmation'));
        }
        header('Location: '.$url);
        exit;
        break;
    default:
        $content = $obj->display();
        break;
}

Display::display_header();
echo $content;
