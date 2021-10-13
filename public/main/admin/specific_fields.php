<?php
/* For licensing terms, see /license.txt */
/**
 * Manage specific fields.
 */
// Resetting the course id.
$cidReset = true;

// Including some necessary chamilo files
require_once __DIR__.'/../inc/global.inc.php';

// User permissions
api_protect_admin_script();

// Breadcrumb
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'settings.php?category=Search', 'name' => get_lang('Configuration settings')];

$libpath = api_get_path(LIBRARY_PATH);

// Create an add-field box
$form = new FormValidator('add_field', 'post', '', '', null, false);
$renderer = &$form->defaultRenderer();
$renderer->setCustomElementTemplate('<span>{element}</span> ');
$form->addElement(
    'static',
    'search_advanced_link',
    null,
    '<a href="specific_fields_add.php">'.Display::return_icon('fieldadd.gif').get_lang('Add a specific search field').'</a>'
);

// Create a sortable table with specific fields data
$column_show = [1, 1, 1];
$column_order = [3, 2, 1];
$extra_fields = get_specific_field_list();
$number_of_extra_fields = count($extra_fields);

$table = new SortableTableFromArrayConfig(
    $extra_fields,
    2,
    50,
    '',
    $column_show,
    $column_order
);
$table->set_header(0, '&nbsp;', false, null, 'width="2%"', 'style="display:none"');
$table->set_header(1, get_lang('Course code'), true, 'width="10%"');
$table->set_header(2, get_lang('Name'));
$table->set_header(3, get_lang('Edit'), false, 'width="10%"');
$table->set_column_filter(3, 'edit_filter');

function edit_filter($id, $url_params, $row)
{
    global $charset;
    $return = '<a href="specific_fields_add.php?action=edit&field_id='.$row[0].'">'.
        Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
    $return .= ' <a href="'.api_get_self().'?action=delete&field_id='.$row[0].'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("Please confirm your choice"), ENT_QUOTES))."'".')) return false;">'.
        Display::return_icon('delete.gif', get_lang('Delete')).'</a>';

    return $return;
}

if (isset($_REQUEST['action']) && 'delete' == $_REQUEST['action']) {
    delete_specific_field($_REQUEST['field_id']);
    header('Location: specific_fields.php?message='.get_lang('Field removed'));
    exit;
}

// Displaying the header
Display::display_header(get_lang('Specific search fields'));
echo Display::addFlash(Display::return_message(get_lang('Specific search fieldsIntro')));

if (!empty($_GET['message'])) {
    Display::addFlash(Display::return_message($_GET['message'], 'confirm'));
}

echo '<div class="actions">';
$form->display();
echo '</div>';
if (!empty($extra_fields)) {
    $table->display();
}

// Displaying the footer
Display::display_footer();
