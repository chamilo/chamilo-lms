<?php
/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;

api_protect_admin_script();

//Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

$tool_name = null;

$action = isset($_GET['action']) ? $_GET['action'] : null;
$field_id = isset($_GET['field_id']) ? $_GET['field_id'] : null;

if (empty($field_id)) {
    api_not_allowed();
}
if (!in_array($type, ExtraField::getValidExtraFieldTypes())) {
    api_not_allowed();
}

$extra_field = new ExtraField($type);
$extra_field_info = $extra_field->get($field_id);

$check = Security::check_token('request');
$token = Security::get_token();

if ($action == 'add') {
    $interbreadcrumb[] = ['url' => 'extra_fields.php?type='.$extra_field->type, 'name' => $extra_field->pageName];
    $interbreadcrumb[] = [
        'url' => 'extra_fields.php?type='.$extra_field->type.'&action=edit&id='.$extra_field_info['id'],
        'name' => $extra_field_info['display_text'],
    ];
    $interbreadcrumb[] = [
        'url' => 'extra_field_options.php?type='.$extra_field->type.'&field_id='.$extra_field_info['id'],
        'name' => get_lang('EditExtraFieldOptions'),
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Add')];
} elseif ($action == 'edit') {
    $interbreadcrumb[] = ['url' => 'extra_fields.php?type='.$extra_field->type, 'name' => $extra_field->pageName];
    $interbreadcrumb[] = [
        'url' => 'extra_fields.php?type='.$extra_field->type.'&action=edit&id='.$extra_field_info['id'],
        'name' => $extra_field_info['display_text'],
    ];
    $interbreadcrumb[] = [
        'url' => 'extra_field_options.php?type='.$extra_field->type.'&field_id='.$extra_field_info['id'],
        'name' => get_lang('EditExtraFieldOptions'),
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Edit')];
} else {
    $interbreadcrumb[] = ['url' => 'extra_fields.php?type='.$extra_field->type, 'name' => $extra_field->pageName];
    $interbreadcrumb[] = [
        'url' => 'extra_fields.php?type='.$extra_field->type.'&action=edit&id='.$extra_field_info['id'],
        'name' => $extra_field_info['display_text'],
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('EditExtraFieldOptions')];
}

//jqgrid will use this URL to do the selects
$params = 'field_id='.$field_id.'&type='.$extra_field->type;
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_extra_field_options&'.$params;

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [
    get_lang('Name'),
    get_lang('Value'),
    get_lang('Order'),
    get_lang('Actions'),
];

//Column config
$column_model = [
    [
        'name' => 'display_text',
        'index' => 'display_text',
        'width' => '180',
        'align' => 'left',
    ],
    [
        'name' => 'option_value',
        'index' => 'option_value',
        'width' => '',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'option_order',
        'index' => 'option_order',
        'width' => '',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '100',
        'align' => 'left',
        'formatter' => 'action_formatter',
        'sortable' => 'false',
    ],
];

//Autowidth
$extra_params['autowidth'] = 'true';
//height auto
$extra_params['height'] = 'auto';

//With this function we can add actions to the jgrid (edit, delete, etc)
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
    return \'<a href="?action=edit&'.$params.'&id=\'+options.rowId+\'">'.Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>'.
    '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(get_lang("ConfirmYourChoice"))."\'".')) return false;"  href="?sec_token='.$token.'&action=delete&'.$params.'&id=\'+options.rowId+\'">'.Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>'.
    '\';
 }';

$htmlHeadXtra[] = '<script>
$(function() {
    // grid definition see the $obj->display() function
    '.Display::grid_js(
        'extra_field_options',
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

Display::display_header($tool_name);
echo Display::page_header($extra_field_info['display_text'], $extra_field_info['variable'], 'h1');

$obj = new ExtraFieldOption($extra_field->type);
$obj->fieldId = $field_id;

// Action handling: Add
switch ($action) {
    case 'add':
        if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
            api_not_allowed();
        }
        $url = api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&'.$params;
        $form = $obj->return_form($url, 'add');

        // The validation or display
        if ($form->validate()) {
            if ($check) {
                $values = $form->exportValues();
                $res = $obj->save_one_item($values);
                if ($res) {
                    echo Display::return_message(get_lang('ItemAdded'), 'confirmation');
                }
            }
            $obj->display();
        } else {
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(['sec_token' => $token]);
            $form->display();
        }
        break;
    case 'edit':
        // Action handling: Editing
        $url = api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&id='.intval($_GET['id']).'&'.$params;
        $form = $obj->return_form($url, 'edit');

        // The validation or display
        if ($form->validate()) {
            if ($check) {
                $values = $form->exportValues();
                $res = $obj->update($values);
                echo Display::return_message(
                    sprintf(get_lang('ItemUpdated'), $values['display_text']),
                    'confirmation',
                    false
                );
            }
            $obj->display();
        } else {
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(['sec_token' => $token]);
            $form->display();
        }
        break;
    case 'delete':
        // Action handling: delete
        if ($check) {
            $res = $obj->delete($_GET['id']);
            if ($res) {
                echo Display::return_message(get_lang('ItemDeleted'), 'confirmation');
            }
        }
        $obj->display();
        break;
    default:
        $obj->display();
        break;
}
Display::display_footer();
