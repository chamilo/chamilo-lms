<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script(true);

$this_section = SECTION_PLATFORM_ADMIN;
$tool_name = get_lang('AdvancedUserEdition');
$message = '';

// Secure GET parameters
$parameters = [];
if (!empty($_GET)) {
    foreach ($_GET as $key => $value) {
        $parameters[$key] = Security::remove_XSS($value);
    }
}

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

// Toolbar actions
$toolbarActions = '';

// Filter GET params
$keywordUsername = !empty($_GET['keywordUsername']) ? Security::remove_XSS($_GET['keywordUsername']) : '';
$keywordEmail = !empty($_GET['keywordEmail']) ? Security::remove_XSS($_GET['keywordEmail']) : '';
$keywordFirstname = !empty($_GET['keywordFirstname']) ? Security::remove_XSS($_GET['keywordFirstname']) : '';
$keywordLastname = !empty($_GET['keywordLastname']) ? Security::remove_XSS($_GET['keywordLastname']) : '';
$keywordOfficialCode = !empty($_GET['keywordOfficialCode']) ? Security::remove_XSS($_GET['keywordOfficialCode']) : '';
$keywordStatus = !empty($_GET['keywordStatus']) ? Security::remove_XSS($_GET['keywordStatus']) : '';

// Advanced search form
$form = new FormValidator('advancedSearch', 'get', '', '', [], FormValidator::LAYOUT_HORIZONTAL);
$form->addElement('header', '', get_lang('AdvancedSearch'));
$form->addText('keywordUsername', get_lang('LoginName'), false);
$form->addText('keywordEmail', get_lang('Email'), false);
$form->addText('keywordFirstname', get_lang('FirstName'), false);
$form->addText('keywordLastname', get_lang('LastName'), false);
$form->addText('keywordOfficialCode', get_lang('OfficialCode'), false);

$statusOptions = [
    '%' => get_lang('All'),
    STUDENT => get_lang('Student'),
    COURSEMANAGER => get_lang('Teacher'),
    DRH => get_lang('Drh'),
    SESSIONADMIN => get_lang('SessionsAdmin'),
    PLATFORM_ADMIN => get_lang('Administrator'),
];
$form->addElement('select', 'keywordStatus', get_lang('Profile'), $statusOptions);

$form->setDefaults(
    [
        'keywordUsername' => $keywordUsername,
        'keywordEmail' => $keywordEmail,
        'keywordFirstname' => $keywordFirstname,
        'keywordLastname' => $keywordLastname,
        'keywordOfficialCode' => $keywordOfficialCode,
        'keywordStatus' => $keywordStatus,
    ]
);

$activeGroup = [];
$activeGroup[] = $form->createElement('checkbox', 'keywordActive', '', get_lang('Active'), ['checked' => isset($_GET['keywordActive'])]);
$activeGroup[] = $form->createElement('checkbox', 'keywordInactive', '', get_lang('Inactive'), ['checked' => isset($_GET['keywordInactive'])]);
$form->addGroup($activeGroup, '', get_lang('ActiveAccount'), null, false);

$parameters = array_map(function ($value) {
    return Security::remove_XSS($value);
}, $_GET);

$extraUserField = new ExtraField('user');
$returnParams = $extraUserField->addElements(
    $form,
    0,
    [],
    true,
    false,
    [],
    [],
    $_REQUEST,
    false,
    true
);

$htmlHeadXtra[] = '<script>
    $(function () {
        '.$returnParams['jquery_ready_content'].'
    })
</script>';
$form->addButtonSearch(get_lang('SearchUsers'), 'filter');

$users = [];
if (isset($_GET['filter'])) {
    $users = UserManager::searchUsers($parameters);
}

$fieldSelector = '';
$jqueryReadyContent = '';
if (!empty($users)) {
    $extraFields = $extraUserField->get_all(['filter = ?' => 1], 'option_order');

    $editableFields = [
        'firstname' => get_lang('FirstName'),
        'lastname' => get_lang('LastName'),
        'email' => get_lang('Email'),
        'phone' => get_lang('PhoneNumber'),
        'official_code' => get_lang('OfficialCode'),
        'status' => get_lang('Profile'),
        'active' => get_lang('ActiveAccount'),
        'password' => get_lang('Password'),
    ];

    foreach ($extraFields as $field) {
        $editableFields[$field['variable']] = ucfirst($field['variable']);
    }

    $form->addElement('select', 'editableFields', get_lang('FieldsToEdit'), $editableFields, [
        'multiple' => 'multiple',
        'size' => 7,
    ]);
    $form->addElement('submit', 'filter', get_lang('View'));
}

$tableResult = '';
if (!empty($users)) {
    foreach ($users as &$user) {
        $userData = api_get_user_info($user['id']);
        if ($userData) {
            $user = array_merge($user, $userData);
        }

        $extraFieldValues = new ExtraFieldValue('user');
        $userExtraFields = $extraFieldValues->getAllValuesByItem($user['id']);

        $formattedExtraFields = [];
        foreach ($userExtraFields as $extraField) {
            $formattedExtraFields[$extraField['variable']] = $extraField['value'];
        }

        $user['extra_fields'] = $formattedExtraFields;
    }
    unset($user);

    $selectedFields = $_GET['editableFields'] ?? [];
    $filtersUsed = [
        'keywordUsername' => 'username',
        'keywordEmail' => 'email',
        'keywordFirstname' => 'firstname',
        'keywordLastname' => 'lastname',
        'keywordOfficialCode' => 'official_code',
        'keywordStatus' => 'status',
    ];

    foreach ($filtersUsed as $filterKey => $fieldName) {
        $getFilterKey = Security::remove_XSS($_GET[$filterKey]);
        if (!empty($getFilterKey) && !in_array($fieldName, $selectedFields)) {
            $selectedFields[] = $fieldName;
        }
    }

    foreach ($extraFields as $field) {
        $extraVariable = Security::remove_XSS($_GET['extra_'.$field['variable']]);
        if (is_array($extraVariable)) {
            $extraVariable = array_filter($extraVariable, function ($v) {
                return $v !== null && $v !== '';
            });
        }
        if (!empty($extraVariable) && !in_array($field['variable'], $selectedFields)) {
            $selectedFields[] = $field['variable'];
        }
    }

    $parameters = array_diff_key($parameters, array_flip(['users_direction', 'users_column']));
    $userTable = new SortableTable('users', null, null, 0, count($users));
    $userTable->set_additional_parameters($parameters);
    $userTable->setTotalNumberOfItems(count($users));
    $userTable->set_header(0, get_lang('ID'));
    $userTable->set_header(1, get_lang('Username'));

    $columnIndex = 2;
    foreach ($selectedFields as $field) {
        $userTable->set_header($columnIndex, ucfirst($field));
        $columnIndex++;
    }

    $userTable->set_header($columnIndex, get_lang('Actions'));
    $userTable->addRow([]);
    foreach ($users as $user) {
        $row = [$user['id'], $user['username']];

        foreach ($selectedFields as $field) {
            $value = isset($user[$field]) ? htmlspecialchars($user[$field]) : '';

            $extraFieldTypes = [];
            foreach ($extraFields as $extraField) {
                $extraFieldTypes[$extraField['variable']] = $extraField['field_type'];
            }

            if (isset($user['extra_fields'][$field])) {
                $fieldType = $extraFieldTypes[$field] ?? ExtraField::FIELD_TYPE_TEXT;
                $value = htmlspecialchars($user['extra_fields'][$field]);

                switch ($fieldType) {
                    case ExtraField::FIELD_TYPE_TEXTAREA:
                        $row[] = '<textarea name="extra_'.$field.'['.$user['id'].']" class="form-control">'.$value.'</textarea>';
                        break;

                    case ExtraField::FIELD_TYPE_SELECT:
                        $fieldHtml = '<select name="extra_'.$field.'['.$user['id'].']" class="form-control">';
                        foreach ($extraField['options'] as $option) {
                            $selected = ($option['option_value'] == $value) ? 'selected' : '';
                            $fieldHtml .= '<option value="'.$option['option_value'].'" '.$selected.'>'.$option['display_text'].'</option>';
                        }
                        $fieldHtml .= '</select>';
                        $row[] = $fieldHtml;
                        break;

                    case ExtraField::FIELD_TYPE_CHECKBOX:
                        $checked = ($value == '1') ? 'checked' : '';
                        $row[] = '<input type="checkbox" name="extra_'.$field.'['.$user['id'].']" value="1" '.$checked.'>';
                        break;

                    case ExtraField::FIELD_TYPE_RADIO:
                        $fieldHtml = '';
                        foreach ($extraField['options'] as $option) {
                            $checked = ($option['option_value'] == $value) ? 'checked' : '';
                            $fieldHtml .= '<label><input type="radio" name="extra_'.$field.'['.$user['id'].']" value="'.$option['option_value'].'" '.$checked.'> '.$option['display_text'].'</label>';
                        }
                        $row[] = $fieldHtml;
                        break;

                    case ExtraField::FIELD_TYPE_TAG:

                        $extraTagField = $extraUserField->get_handler_field_info_by_field_variable($field);
                        $formattedValue = UserManager::get_user_tags_to_string(
                            $user['id'],
                            $extraTagField['id'],
                            false
                        );

                        $row[] = '<input type="text" name="extra_'.$field.'['.$user['id'].']" value="'.$formattedValue.'" class="form-control">'.
                            '<small>'.get_lang('KeywordTip').'</small>';
                        break;

                    case ExtraField::FIELD_TYPE_DOUBLE_SELECT:
                        if (is_array($value) && isset($value["extra_{$field}"]) && isset($value["extra_{$field}_second"])) {
                            $formattedValue = $value["extra_{$field}"].','.$value["extra_{$field}_second"];
                        } else {
                            $formattedValue = '';
                        }
                        $row[] = '<input type="text" name="extra_'.$field.'['.$user['id'].']" value="'.$formattedValue.'" class="form-control">'.
                        '<small>'.get_lang('KeywordTip').'</small>';
                        break;

                    default:
                        $row[] = '<input type="text" name="extra_'.$field.'['.$user['id'].']" value="'.$value.'" class="form-control">';
                        break;
                }
            } else {
                if ($field === 'password') {
                    $row[] = '<input type="password" name="'.$field.'['.$user['id'].']" value="" class="form-control" placeholder="'.get_lang('Password').'">';
                } elseif ($field === 'status') {
                    $statusOptions = [
                        STUDENT => get_lang('Student'),
                        COURSEMANAGER => get_lang('Teacher'),
                        DRH => get_lang('Drh'),
                        SESSIONADMIN => get_lang('SessionsAdmin'),
                        PLATFORM_ADMIN => get_lang('Administrator'),
                    ];
                    $select = '<select name="status['.$user['id'].']" class="form-control">';
                    foreach ($statusOptions as $key => $label) {
                        $selected = ($key == $user['status']) ? 'selected' : '';
                        $select .= '<option value="'.$key.'" '.$selected.'>'.$label.'</option>';
                    }
                    $select .= '</select>';
                    $row[] = $select;
                } elseif ($field === 'active') {
                    $checkedActive = ($user['active'] == 1) ? 'checked' : '';
                    $checkedInactive = ($user['active'] == 0) ? 'checked' : '';
                    $row[] = '<label><input type="radio" name="active['.$user['id'].']" value="1" '.$checkedActive.'> '.get_lang('Active').'</label>
                      <label><input type="radio" name="active['.$user['id'].']" value="0" '.$checkedInactive.'> '.get_lang('Inactive').'</label>';
                } else {
                    $row[] = '<input type="text" name="'.$field.'['.$user['id'].']" value="'.$value.'" class="form-control">';
                }
            }
        }

        $row[] = '<button class="btn btn-primary saveUser" data-user-id="'.$user['id'].'">'.get_lang('SaveOne').'</button>';

        $userTable->addRow($row);
    }

    $tableResult = $userTable->return_table();
}

$htmlHeadXtra[] = '<script>
$(document).ready(function() {

    function getUserData(userId) {
        let userData = { user_id: userId };

        $("input[name$=\'[" + userId + "]\'], select[name$=\'[" + userId + "]\'], textarea[name$=\'[" + userId + "]\']").each(function() {
            let fieldName = $(this).attr("name").replace("[" + userId + "]", "");
            userData[fieldName] = $(this).val();
        });

        $("input[type=\'radio\'][name$=\'[" + userId + "]\']:checked").each(function() {
            let fieldName = $(this).attr("name").replace("[" + userId + "]", "");
            userData[fieldName] = $(this).val();
        });

        $("input[type=\'checkbox\'][name$=\'[" + userId + "]\']:checked").each(function() {
            let fieldName = $(this).attr("name").replace("[" + userId + "]", "");
            userData[fieldName] = "1";
        });

        $("input[name^=\'extra_[" + userId + "]\'], select[name^=\'extra_[" + userId + "]\'], textarea[name^=\'extra_[" + userId + "]\']").each(function() {
            let fieldName = $(this).attr("name").replace("extra_[" + userId + "]", "extra_");

            if ($(this).hasClass("tags-input")) {
                userData[fieldName] = $(this).val().split(",");
            }
            else if ($(this).hasClass("doubleselect-input")) {
                let values = $(this).val().split(",");
                if (values.length === 2) {
                    userData[fieldName] = values[0];
                    userData[fieldName + "_second"] = values[1];
                }
            }
            else {
                userData[fieldName] = $(this).val();
            }
        });

        return userData;
    }

    $(".saveUser").click(function() {
        let userId = $(this).data("user-id");
        if (!userId) {
            return;
        }

        let userData = getUserData(userId);

        $.post("'.api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php", {
            a: "update_users",
            users: JSON.stringify([userData])
        }, function(response) {
            alert(response.message);
        }, "json");
    });

    $("#saveAll").click(function() {
        let usersData = [];

        $(".saveUser").each(function() {
            let userId = $(this).data("user-id");
            let userData = getUserData(userId);
            if (userData) usersData.push(userData);
        });

        if (usersData.length === 0) {
            return;
        }

        $.post("'.api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php", {
            a: "update_users",
            users: JSON.stringify(usersData)
        }, function(response) {
            alert(response.message);
        }, "json");
    });

});
</script>';

$formContent = $form->returnForm();

// Render page
$tpl = new Template($tool_name);
$tpl->assign('actions', $toolbarActions);
$tpl->assign('message', $message);
$tpl->assign('content', $formContent.$fieldSelector.$tableResult.(!empty($users) ? '<button class="btn btn-success" id="saveAll">'.get_lang('SaveAll').'</button>' : ''));
$tpl->display_one_col_template();
