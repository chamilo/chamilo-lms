<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraFieldOptionRelFieldOption;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;

api_protect_admin_script();

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];

$tool_name = null;

$action = isset($_GET['action']) ? $_GET['action'] : null;
$fieldId = isset($_GET['field_id']) ? (int) $_GET['field_id'] : null;

if (empty($fieldId)) {
    api_not_allowed();
}
if (!in_array($type, ExtraField::getValidExtraFieldTypes())) {
    api_not_allowed();
}

$em = Database::getManager();
$repoExtraField = $em->getRepository(\Chamilo\CoreBundle\Entity\ExtraField::class);
$extraFieldEntity = $repoExtraField->find($fieldId);

$extraField = new ExtraField($type);

$check = Security::check_token('request');
$token = Security::get_token();

if ('add' === $action) {
    $interbreadcrumb[] = ['url' => 'extra_fields.php?type='.$extraField->type, 'name' => $extraField->pageName];
    $interbreadcrumb[] = [
        'url' => 'extra_fields.php?type='.$extraField->type.'&action=edit&id='.$fieldId,
        'name' => $extraFieldEntity->getDisplayText(),
    ];
    $interbreadcrumb[] = [
        'url' => 'extra_field_options.php?type='.$extraField->type.'&field_id='.$fieldId,
        'name' => get_lang('Edit extra field options'),
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Add')];
} elseif ('edit' === $action) {
    $interbreadcrumb[] = [
        'url' => 'extra_fields.php?type='.$extraField->type,
        'name' => $extraField->pageName,
    ];
    $interbreadcrumb[] = [
        'url' => 'extra_fields.php?type='.$extraField->type.'&action=edit&id='.$fieldId,
        'name' => $extraFieldEntity->getDisplayText(),
    ];
    $interbreadcrumb[] = [
        'url' => 'extra_field_options.php?type='.$extraField->type.'&field_id='.$fieldId,
        'name' => get_lang('Edit extra field options'),
    ];

    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Edit')];
} else {
    $interbreadcrumb[] = [
        'url' => 'extra_fields.php?type='.$extraField->type,
        'name' => $extraField->pageName,
    ];
    $interbreadcrumb[] = [
        'url' => 'extra_fields.php?type='.$extraField->type.'&action=edit&id='.$fieldId,
        'name' => $extraFieldEntity->getDisplayText(),
    ];
    $interbreadcrumb[] = [
        'url' => '#',
        'name' => get_lang('Edit extra field options'),
    ];
}

$roleId = isset($_REQUEST['roleId']) ? (int) $_REQUEST['roleId'] : null;

//jqgrid will use this URL to do the selects
$params = 'field_id='.$fieldId.'&type='.$extraField->type.'&roleId='.$roleId;
$paramsNoRole = 'field_id='.$fieldId.'&type='.$extraField->type;

// The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [get_lang('Name'), get_lang('Value'), get_lang('Order'), get_lang('Detail')];

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
        'where' => ['field_id = ? ' => $fieldId],
        'order' => 'option_order ASC',
    ]
);

$table = new HTML_Table(['class' => 'data_table']);
$column = 0;
$row = 0;
$table->setHeaderContents($row, $column, get_lang('Current status'));
$column++;
foreach ($result as $item) {
    $table->setHeaderContents($row, $column, $item['display_text']);
    $column++;
}
$row++;

$form = new FormValidator('workflow', 'post', api_get_self().'?'.$params);
//$options = api_get_user_roles();
$options[0] = get_lang('Please select an option');
$options[STUDENT] = get_lang('Learner');
$options[COURSEMANAGER] = get_lang('Trainer');

ksort($options);
$form->addSelect('status', get_lang('Select role'), $options);

$repo = $em->getRepository(ExtraFieldOptionRelFieldOption::class);

$checks = $repo->findBy(['fieldId' => $fieldId, 'roleId' => $roleId]);
$includedFields = [];
if (!empty($checks)) {
    /** @var ExtraFieldOptionRelFieldOption $availableField */
    foreach ($checks as $availableField) {
        $includedFields[$availableField->getExtraFieldOption()->getId()][] = $availableField->getRelatedFieldOption()->getId();
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
        get_lang('Select all'),
        'check',
        'default',
        'default',
        null,
        [],
        true
    );
    $group[] = $form->addButton(
        'unselect_all',
        get_lang('UnSelect all'),
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
            $relatedExtraField = $repoExtraField->find($id);
            foreach ($items as $subItemId => $value) {
                $subExtraField = $repoExtraField->find($subItemId);
                $extraFieldOptionRelFieldOption = $repo->findOneBy(
                    [
                        'fieldId' => $fieldId,
                        'fieldOptionId' => $subItemId,
                        'roleId' => $roleId,
                        'relatedFieldOptionId' => $id,
                    ]
                );

                if (1 == $value) {
                    if (empty($extraFieldOptionRelFieldOption)) {
                        $extraFieldOptionRelFieldOption = new ExtraFieldOptionRelFieldOption();
                        $extraFieldOptionRelFieldOption
                            ->setField($extraFieldEntity)
                            ->setExtraFieldOption($subExtraField)
                            ->setRelatedFieldOption($relatedExtraField)
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

        Display::addFlash(Display::return_message(get_lang('Update successful')));
        header('Location:'.api_get_self().'?'.$params);
        exit;
    }
}

Display::display_header($tool_name);
echo Display::page_header($extraFieldEntity->getDisplayText());
$form->display();

Display::display_footer();
