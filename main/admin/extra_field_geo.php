<?php
/* For licensing terms, see /license.txt */
exit;
/**
 *  @package chamilo.admin
 */
$cidReset = true;
require_once '../inc/global.inc.php';

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
    api_not_allowed(true);
}
if (!in_array($type, ExtraField::getValidExtraFieldTypes())) {
    api_not_allowed(true);
}

$extra_field = new ExtraField($type);
$extra_field_info = $extra_field->get($field_id);

$extraFieldValue = new ExtraFieldValue($type);
$values = $extraFieldValue->get_values_by_handler_and_field_id(
    $extra_field_info['id'],
    $field_id
);

$check = Security::check_token('request');
$token = Security::get_token();

$interbreadcrumb[] = ['url' => 'extra_fields.php?type='.$extra_field->type, 'name' => $extra_field->pageName];
$interbreadcrumb[] = [
    'url' => 'extra_fields.php?type='.$extra_field->type.'&action=edit&id='.$extra_field_info['id'],
    'name' => $extra_field_info['display_text'],
];
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Geolocalization')];

//jqgrid will use this URL to do the selects
$params = 'field_id='.$field_id.'&type='.$extra_field->type;
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_extra_field_options&'.$params;

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

// The header.
Display::display_header($tool_name);

echo Display::page_header($extra_field_info['display_text']);

Display :: display_footer();
