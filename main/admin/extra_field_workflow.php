<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;

api_protect_admin_script();

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

$tool_name = null;

$action = isset($_GET['action']) ? $_GET['action'] : null;
$field_id = isset($_GET['field_id']) ? $_GET['field_id'] : null;

if (empty($field_id)) {
    api_not_allowed();
}
if (!in_array($type, ExtraField::getValidExtraFieldTypes())) {
    api_not_allowed();
}

$extraField = new ExtraField($type);
$extraFieldInfo = $extraField->get($field_id);

$check = Security::check_token('request');
$token = Security::get_token();

if ($action == 'add') {
    $interbreadcrumb[] = array('url' => 'extra_fields.php?type='.$extraField->type, 'name' => $extraField->pageName);
    $interbreadcrumb[] = array(
        'url' => 'extra_fields.php?type='.$extraField->type.'&action=edit&id='.$extraFieldInfo['id'],
        'name' => $extraFieldInfo['display_text']
    );
    $interbreadcrumb[] = array(
        'url' => 'extra_field_options.php?type='.$extraField->type.'&field_id='.$extraFieldInfo['id'],
        'name' => get_lang('EditExtraFieldOptions')
    );
    $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('Add'));
} elseif ($action == 'edit') {
    $interbreadcrumb[] = array(
        'url' => 'extra_fields.php?type='.$extraField->type,
        'name' => $extraField->pageName
    );
    $interbreadcrumb[] = array(
        'url' => 'extra_fields.php?type='.$extraField->type.'&action=edit&id='.$extraFieldInfo['id'],
        'name' => $extraFieldInfo['display_text']
    );
    $interbreadcrumb[] = array(
        'url' => 'extra_field_options.php?type='.$extraField->type.'&field_id='.$extraFieldInfo['id'],
        'name' => get_lang('EditExtraFieldOptions')
    );

    $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('Edit'));
} else {
    $interbreadcrumb[] = array(
        'url' => 'extra_fields.php?type='.$extraField->type,
        'name' => $extraField->pageName
    );
    $interbreadcrumb[] = array(
        'url' => 'extra_fields.php?type='.$extraField->type.'&action=edit&id='.$extraFieldInfo['id'],
        'name' => $extraFieldInfo['display_text']
    );
    $interbreadcrumb[] = array(
        'url' => '#',
        'name' => get_lang('EditExtraFieldOptions')
    );
}

$roleId = isset($_REQUEST['roleId']) ? $_REQUEST['roleId'] : null;

//jqgrid will use this URL to do the selects
$params = 'field_id='.$field_id.'&type='.$extraField->type.'&roleId='.$roleId;
$paramsNoRole = 'field_id='.$field_id.'&type='.$extraField->type;

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = array(get_lang('Name'), get_lang('Value'), get_lang('Order'), get_lang('Actions'));

$htmlHeadXtra[] = '<script>

    function setHidden(obj) {
        var name = $(obj).attr("name");
        var hiddenName = "hidden_" + name;
        if ($("#" + hiddenName).attr("value") == 1) {
            $("#" + hiddenName).attr("value", 0);
        } else {
            $("#" + hiddenName).attr("value", 1);
        }
    }

    function changeStatus(obj) {
        var roleId = $(obj).find(":selected").val();
        if (roleId != 0) {
            window.location.replace("'.api_get_self().'?'.$paramsNoRole.'&roleId="+roleId);
        }
    }
    $().ready( function() {
        $(".select_all").on("click", function() {
            $("#workflow :checkbox").prop("checked", 1);
            $("#workflow :hidden").prop("value", 1);
            return false;
        });
        $(".unselect_all").on("click", function() {
            $("#workflow :checkbox").prop("checked", 0);
            $("#workflow :hidden").prop("value", 0);
            return false;
        });
    });
</script>';

// The header.
Display::display_header($tool_name);

echo Display::page_header($extraFieldInfo['display_text']);

$obj = new ExtraFieldOption($type);
$columns = array('display_text', 'option_value', 'option_order');
$result = Database::select(
    '*',
    $obj->table,
    array(
        'where' => array("field_id = ? " => $field_id),
        'order' => "option_order ASC"
    )
);

$table = new HTML_Table(array('class' => 'data_table'));
$column = 0;
$row = 0;
$table->setHeaderContents($row, $column, get_lang('CurrentStatus'));
$column++;
foreach ($result as $item) {
    $table->setHeaderContents($row, $column, $item['option_display_text']);
    $column++;
}
$row++;

$form = new FormValidator('workflow', 'post', api_get_self().'?'.$params);
$options = api_get_user_roles();
$options[0] = get_lang('SelectAnOption');
ksort($options);
$form->addElement('select', 'status', get_lang('SelectRole'), $options, array('onclick' => 'changeStatus(this)'));

$checks = $app['orm.em']->getRepository('ChamiloLMS\Entity\ExtraFieldOptionRelFieldOption')->findBy(array('fieldId' => $field_id, 'roleId' => $roleId));
$includedFields = array();
if (!empty($checks)) {
    foreach ($checks as $availableField) {
        $includedFields[$availableField->getFieldOptionId()][] = $availableField->getRelatedFieldOptionId();
    }
}

foreach ($result as $item) {
    $column = 0;
    $table->setCellContents($row, $column, $item['option_display_text']);
    $column++;
    $value = null;

    foreach ($result as $itemCol) {
        $id = 'extra_field_status_'.$item['id'].'_'.$itemCol['id'];
        $idForm = 'extra_field_status['.$item['id'].']['.$itemCol['id'].']';
        $attributes = array('onclick' => 'setHidden(this)');
        $value = 0;

        if (isset($includedFields[$itemCol['id']]) && in_array($item['id'], $includedFields[$itemCol['id']])) {
            $value = 1;
            $attributes['checked'] = 'checked';
        }

        $element = Display::input('checkbox', $id, null, $attributes);
        $table->setCellContents($row, $column, $element);
        $form->addElement('hidden', 'hidden_'.$idForm, $value, array('id' => 'hidden_'.$id));
        $column++;
    }
    $row++;
}

if (!empty($roleId)) {
    $form->addElement('html', $table->toHtml());
    $group = array();
    $group[] = $form->createElement('button', 'submit', get_lang('Save'));
    $group[] = $form->createElement('button', 'select_all', get_lang('SelectAll'), array('class' => 'btn select_all'));
    $group[] = $form->createElement('button', 'unselect_all', get_lang('UnSelectAll'), array('class' => 'btn unselect_all'));
    $form->addGroup($group, '', null, ' ');

    $form->setDefaults(array('status' => $roleId));
} else {
    $form->addButtonUpdate(get_lang('Edit'));
}

$form->display();

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $result = $values['hidden_extra_field_status'];
    if (!empty($result)) {
        foreach ($result as $id => $items) {
            foreach ($items as $subItemId => $value) {
                $extraFieldOptionRelFieldOption = $app['orm.em']->getRepository('ChamiloLMS\Entity\ExtraFieldOptionRelFieldOption')->findOneBy(
                    array(
                    'fieldId' => $field_id,
                    'fieldOptionId' => $subItemId,
                    'roleId' => $roleId,
                    'relatedFieldOptionId' => $id
                    )
                );

                if ($value == 1) {
                    if (empty($extraFieldOptionRelFieldOption)) {
                        $extraFieldOptionRelFieldOption = new \ChamiloLMS\Entity\ExtraFieldOptionRelFieldOption();
                        $extraFieldOptionRelFieldOption->setFieldId($field_id);
                        $extraFieldOptionRelFieldOption->setFieldOptionId($subItemId);
                        $extraFieldOptionRelFieldOption->setRelatedFieldOptionId($id);
                        $extraFieldOptionRelFieldOption->setRoleId($roleId);
                        $app['orm.ems']['db_write']->persist($extraFieldOptionRelFieldOption);
                    }
                } else {

                    if ($extraFieldOptionRelFieldOption) {
                        $app['orm.ems']['db_write']->remove($extraFieldOptionRelFieldOption);
                    }
                }

            }
        }
        $app['orm.ems']['db_write']->flush();
        header('Location:'.api_get_self().'?'.$params);
        exit;
    }
}

Display :: display_footer();
