<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraFieldOptionRelFieldOption;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;

api_protect_admin_script();

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

$tool_name = null;

$action = isset($_GET['action']) ? $_GET['action'] : null;
$field_id = isset($_GET['field_id']) ? (int) $_GET['field_id'] : null;

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
    $interbreadcrumb[] = ['url' => 'extra_fields.php?type='.$extraField->type, 'name' => $extraField->pageName];
    $interbreadcrumb[] = [
        'url' => 'extra_fields.php?type='.$extraField->type.'&action=edit&id='.$extraFieldInfo['id'],
        'name' => $extraFieldInfo['display_text'],
    ];
    $interbreadcrumb[] = [
        'url' => 'extra_field_options.php?type='.$extraField->type.'&field_id='.$extraFieldInfo['id'],
        'name' => get_lang('EditExtraFieldOptions'),
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Add')];
} elseif ($action == 'edit') {
    $interbreadcrumb[] = [
        'url' => 'extra_fields.php?type='.$extraField->type,
        'name' => $extraField->pageName,
    ];
    $interbreadcrumb[] = [
        'url' => 'extra_fields.php?type='.$extraField->type.'&action=edit&id='.$extraFieldInfo['id'],
        'name' => $extraFieldInfo['display_text'],
    ];
    $interbreadcrumb[] = [
        'url' => 'extra_field_options.php?type='.$extraField->type.'&field_id='.$extraFieldInfo['id'],
        'name' => get_lang('EditExtraFieldOptions'),
    ];

    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Edit')];
} else {
    $interbreadcrumb[] = [
        'url' => 'extra_fields.php?type='.$extraField->type,
        'name' => $extraField->pageName,
    ];
    $interbreadcrumb[] = [
        'url' => 'extra_fields.php?type='.$extraField->type.'&action=edit&id='.$extraFieldInfo['id'],
        'name' => $extraFieldInfo['display_text'],
    ];
    $interbreadcrumb[] = [
        'url' => '#',
        'name' => get_lang('EditExtraFieldOptions'),
    ];
}

$roleId = isset($_REQUEST['roleId']) ? (int) $_REQUEST['roleId'] : null;

//jqgrid will use this URL to do the selects
$params = 'field_id='.$field_id.'&type='.$extraField->type.'&roleId='.$roleId;
$paramsNoRole = 'field_id='.$field_id.'&type='.$extraField->type;

// The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [get_lang('Name'), get_lang('Value'), get_lang('Order'), get_lang('Actions')];

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

    $(function() {
        $("#workflow_status").on("change", function() {
            var roleId = $(this).find(":selected").val();
            if (roleId != 0) {
                window.location.replace("'.api_get_self().'?'.$paramsNoRole.'&roleId="+roleId);
            }
        });

        $("[name=select_all]").on("click", function() {
            $("#workflow :checkbox").prop("checked", 1);
            $("#workflow :hidden").prop("value", 1);
            return false;
        });

        $("[name=unselect_all]").on("click", function() {
            $("#workflow :checkbox").prop("checked", 0);
            $("#workflow :hidden").prop("value", 0);
            return false;
        });
    });
</script>';

$obj = new ExtraFieldOption($type);
$columns = ['display_text', 'option_value', 'option_order'];
$result = Database::select(
    '*',
    $obj->table,
    [
        'where' => ['field_id = ? ' => $field_id],
        'order' => 'option_order ASC',
    ]
);

$table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
$column = 0;
$row = 0;
$table->setHeaderContents($row, $column, get_lang('CurrentStatus'));
$column++;
foreach ($result as $item) {
    $table->setHeaderContents($row, $column, $item['display_text']);
    $column++;
}
$row++;

$form = new FormValidator('workflow', 'post', api_get_self().'?'.$params);
//$options = api_get_user_roles();
$options[0] = get_lang('SelectAnOption');
$options[STUDENT] = get_lang('Student');
$options[COURSEMANAGER] = get_lang('Teacher');

ksort($options);
$form->addElement('select', 'status', get_lang('SelectRole'), $options);

$em = Database::getManager();
$repo = $em->getRepository('ChamiloCoreBundle:ExtraFieldOptionRelFieldOption');

$checks = $repo->findBy(
    ['fieldId' => $field_id, 'roleId' => $roleId]
);
$includedFields = [];
if (!empty($checks)) {
    foreach ($checks as $availableField) {
        $includedFields[$availableField->getFieldOptionId()][] = $availableField->getRelatedFieldOptionId();
    }
}

foreach ($result as $item) {
    $column = 0;
    $table->setCellContents($row, $column, $item['display_text']);
    $column++;
    $value = null;

    foreach ($result as $itemCol) {
        $id = 'extra_field_status_'.$item['id'].'_'.$itemCol['id'];
        $idForm = 'extra_field_status['.$item['id'].']['.$itemCol['id'].']';
        $attributes = ['onclick' => 'setHidden(this)'];
        $value = 0;

        if (isset($includedFields[$itemCol['id']]) && in_array($item['id'], $includedFields[$itemCol['id']])) {
            $value = 1;
            $attributes['checked'] = 'checked';
        }

        $element = Display::input('checkbox', $id, null, $attributes);
        $table->setCellContents($row, $column, $element);
        $form->addElement('hidden', 'hidden_'.$idForm, $value, ['id' => 'hidden_'.$id]);
        $column++;
    }
    $row++;
}

if (!empty($roleId)) {
    $form->addElement('html', $table->toHtml());
    $group = [];
    $group[] = $form->addButtonSave(get_lang('Save'), 'submit', true);
    $group[] = $form->addButton(
        'select_all',
        get_lang('SelectAll'),
        'check',
        'default',
        'default',
        null,
        [],
        true
    );
    $group[] = $form->addButton(
        'unselect_all',
        get_lang('UnSelectAll'),
        'check',
        'default',
        'default',
        null,
        [],
        true
    );

    $form->addGroup($group, '', null, ' ');
    $form->setDefaults(['status' => $roleId]);
} else {
    $form->addButtonUpdate(get_lang('Edit'));
}

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $result = $values['hidden_extra_field_status'];

    if (!empty($result)) {
        foreach ($result as $id => $items) {
            foreach ($items as $subItemId => $value) {
                $extraFieldOptionRelFieldOption = $repo->findOneBy(
                    [
                        'fieldId' => $field_id,
                        'fieldOptionId' => $subItemId,
                        'roleId' => $roleId,
                        'relatedFieldOptionId' => $id,
                    ]
                );

                if ($value == 1) {
                    if (empty($extraFieldOptionRelFieldOption)) {
                        $extraFieldOptionRelFieldOption = new ExtraFieldOptionRelFieldOption();
                        $extraFieldOptionRelFieldOption
                            ->setFieldId($field_id)
                            ->setFieldOptionId($subItemId)
                            ->setRelatedFieldOptionId($id)
                            ->setRoleId($roleId)
                        ;

                        $em->persist($extraFieldOptionRelFieldOption);
                    }
                } else {
                    if ($extraFieldOptionRelFieldOption) {
                        $em->remove($extraFieldOptionRelFieldOption);
                    }
                }
            }
        }
        $em->flush();

        Display::addFlash(Display::return_message(get_lang('Updated')));
        header('Location:'.api_get_self().'?'.$params);
        exit;
    }
}

Display::display_header($tool_name);
echo Display::page_header($extraFieldInfo['display_text']);
$form->display();

Display::display_footer();
