<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$xajax = new xajax();
$xajax->registerFunction('search_coachs');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

SessionManager::protectSession(null, false);
api_protect_limit_for_session_admin();

$formSent = 0;
$errorMsg = '';

// Crop picture plugin for session images
$htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
$htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');

$interbreadcrumb[] = [
    'url' => 'session_list.php',
    'name' => get_lang('SessionList'),
];

function search_coachs($needle)
{
    $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
    $xajax_response = new xajaxResponse();
    $return = '';

    if (!empty($needle)) {
        $needle = Database::escape_string($needle);
        $order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';

        // search users where username or firstname or lastname begins likes $needle
        $sql = 'SELECT username, lastname, firstname
                FROM '.$tbl_user.' user
                WHERE (username LIKE "'.$needle.'%"
                OR firstname LIKE "'.$needle.'%"
                OR lastname LIKE "'.$needle.'%")
                AND status=1'.
            $order_clause.
            ' LIMIT 10';

        if (api_is_multiple_url_enabled()) {
            $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $sql = 'SELECT username, lastname, firstname
                        FROM '.$tbl_user.' user
                        INNER JOIN '.$tbl_user_rel_access_url.' url_user
                        ON (url_user.user_id=user.user_id)
                        WHERE
                            access_url_id = '.$access_url_id.'  AND
                            (
                                username LIKE "'.$needle.'%" OR
                                firstname LIKE "'.$needle.'%" OR
                                lastname LIKE "'.$needle.'%"
                            )
                            AND status=1'.
                    $order_clause.'
                        LIMIT 10';
            }
        }

        $rs = Database::query($sql);
        while ($user = Database::fetch_array($rs)) {
            $return .= '<a href="javascript: void(0);" onclick="javascript: fill_coach_field(\''.$user['username'].'\')">'.api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].')</a><br />';
        }
    }
    $xajax_response->addAssign('ajax_list_coachs', 'innerHTML', api_utf8_encode($return));

    return $xajax_response;
}

$xajax->processRequests();
$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = "
<script>
$(function() {
    accessSwitcher(0);
});

function fill_coach_field (username) {
    document.getElementById('coach_username').value = username;
    document.getElementById('ajax_list_coachs').innerHTML = '';
}

function accessSwitcher(accessFromReady) {
    var access = $('#access option:selected').val();

    if (accessFromReady >= 0) {
        access  = accessFromReady;
    }

    if (access == 1) {
        $('#duration_div').hide();
        $('#date_fields').show();
    } else {
        $('#duration_div').show();
        $('#date_fields').hide();
    }
    emptyDuration();
}

function emptyDuration() {
    if ($('#duration').val()) {
        $('#duration').val('');
    }
}
</script>";

if (isset($_POST['formSent']) && $_POST['formSent']) {
    $formSent = 1;
}

$tool_name = get_lang('AddSession');

$urlAction = api_get_self();

function check_session_name($name)
{
    $session = SessionManager::get_session_by_name($name);

    return empty($session) ? true : false;
}

$form = new FormValidator('add_session', 'post', $urlAction);
$form->addElement('header', $tool_name);
$result = SessionManager::setForm($form);

$url = api_get_path(WEB_AJAX_PATH).'session.ajax.php';
$urlUpload = api_get_path(WEB_UPLOAD_PATH);
$sysUploadPath = api_get_path(SYS_UPLOAD_PATH);
$urlAjaxExtraField = api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php?1=1';

$htmlHeadXtra[] = "
<script>
$(function() {
    ".$result['js']."
    $('#system_template').on('change', function() {
        var sessionId = $(this).find('option:selected').val();

        $.ajax({
            type: 'GET',
            dataType: 'json',
            url: '".$url."',
            data: 'a=session_info&load_empty_extra_fields=true&session_id=' + sessionId,
            success: function(data) {
                if (data.session_category_id > 0) {
                    $('#session_category').val(data.session_category_id);
                    $('#session_category').selectpicker('render');
                } else {
                    $('#session_category').val(0);
                    $('#session_category').selectpicker('render');
                }

                CKEDITOR.instances.description.setData(data.description);

                if (data.duration > 0) {
                    $('#access').val(0);
                    $('#access').selectpicker('render');
                    accessSwitcher(0);
                    $('#duration').val(parseInt(data.duration));
                } else {
                    $('#access').val(1);
                    $('#access').selectpicker('render');
                    accessSwitcher(1);

                    var variables = [
                        'display_start_date',
                        'access_start_date',
                        'coach_access_start_date',
                        'display_end_date',
                        'access_end_date',
                        'coach_access_end_date'
                    ];
                    variables.forEach(function(variable) {
                        var variableName = variable + '_to_local_time';
                        if (data[variableName]) {
                            var parsedDate = $.datepicker.parseDateTime(
                                'yy-mm-dd',
                                'hh:mm:ss',
                                data[variableName]
                            );
                            if (parsedDate) {
                                $('#'+variable).datetimepicker('setDate', parsedDate);
                            }
                        }
                    });
                }

                $('[name=\'show_description\']').prop('checked', false);
                if (data.show_description) {
                    $('[name=\'show_description\']').prop('checked', true);
                }

                $('[name=\'send_subscription_notification\']').prop('checked', false);
                if (data.send_subscription_notification) {
                    $('[name=\'send_subscription_notification\']').prop('checked', true);
                }

                $.each(data.extra_fields, function(i, item) {
                    var fieldName = 'extra_'+item.variable;
                    /*
                    const FIELD_TYPE_TEXT = 1;
                    const FIELD_TYPE_TEXTAREA = 2;
                    const FIELD_TYPE_RADIO = 3;
                    const FIELD_TYPE_SELECT = 4;
                    const FIELD_TYPE_SELECT_MULTIPLE = 5;
                    const FIELD_TYPE_DATE = 6;
                    const FIELD_TYPE_DATETIME = 7;
                    const FIELD_TYPE_DOUBLE_SELECT = 8;
                    const FIELD_TYPE_DIVIDER = 9;
                    const FIELD_TYPE_TAG = 10;
                    const FIELD_TYPE_TIMEZONE = 11;
                    const FIELD_TYPE_SOCIAL_PROFILE = 12;
                    const FIELD_TYPE_CHECKBOX = 13;
                    const FIELD_TYPE_MOBILE_PHONE_NUMBER = 14;
                    const FIELD_TYPE_INTEGER = 15;
                    const FIELD_TYPE_FILE_IMAGE = 16;
                    const FIELD_TYPE_FLOAT = 17;
                    const FIELD_TYPE_FILE = 18;
                    const FIELD_TYPE_VIDEO_URL = 19;
                    const FIELD_TYPE_LETTERS_ONLY = 20;
                    const FIELD_TYPE_ALPHANUMERIC = 21;
                    const FIELD_TYPE_LETTERS_SPACE = 22;
                    const FIELD_TYPE_ALPHANUMERIC_SPACE = 23;*/
                    switch (item.field_type) {
                        case '1': // text
                        case '6': // date
                        case '7': // datetime
                        case '15': // integer
                        case '17': // float
                        case '20': // letters only
                        case '21': // alphanum
                            $('input[name='+fieldName+']').val(item.value);
                            break;
                        case '2': // textarea
                            CKEDITOR.instances[fieldName].setData(item.value);
                            break;
                        case '3': // radio
                            var radio = fieldName+'['+fieldName+']';
                            $('[name=\''+radio+'\']').val([item.value]);
                            break;
                        case '4': // simple select
                        case '5': // multiple select
                            var options = item.value.split(';');
                            $('#'+fieldName+'').val(options);
                            $('#'+fieldName+'').selectpicker('render');
                            break;
                        case '8': // double
                            var first = 'first_'+fieldName;
                            var second = 'second_'+fieldName;
                            // item.value has format : 85::86
                            if (item.value) {
                                var values = item.value.split('::');
                                var firstFieldId = values[0];
                                var secondFieldId = values[1];
                                $('#'+first+'').val(firstFieldId);
                                $('#'+first+'').selectpicker('render');

                                // Remove all options
                                 $('#'+second+'')
                                .find('option')
                                .remove()
                                .end();

                                // Load items for this item then update:
                                $.ajax({
                                    url: '".$urlAjaxExtraField."&a=get_second_select_options',
                                    dataType: 'json',
                                    data: 'type=session&field_id='+item.id+'&option_value_id='+firstFieldId,
                                    success: function(data) {
                                        $.each(data, function(index, value) {
                                            var my_select = $('#'+second+'');
                                            my_select.append($(\"<option/>\", {
                                                value: index,
                                                text: value
                                            }));
                                        });
                                        $('#'+second+'').selectpicker('refresh');
                                    }
                                });

                                $('#'+second+'').val(secondFieldId);
                                $('#'+second+'').selectpicker('render');
                            }
                            break;
                        case '10': // tags
                             // Remove all options
                            $('#'+fieldName+' option').each(function(i, optionItem) {
                                $(this).remove();
                            });

                            $('#'+fieldName).next().find('.bit-box').each(function(i, optionItem) {
                                $(this).remove();
                            });

                            // Add new options
                            if (item.value) {
                                $.each(item.value, function(i, tagItem) {
                                    // Select2 changes
                                    //console.log(tagItem.value);
                                    //$('#'+fieldName)[0].addItem(tagItem.value, tagItem.value);
                                    var option = new Option(tagItem.value, tagItem.value);
                                    option.selected = true;
                                    $('#'+fieldName).append(option);
                                    $('#'+fieldName).trigger(\"change\");
                                });
                            }
                            break;
                        case '13': // check
                            var check = fieldName+'['+fieldName+']';
                            // Default is uncheck
                            $('[name=\''+check+'\']').prop('checked', false);

                            if (item.value == 1) {
                               $('[name=\''+check+'\']').prop('checked', true);
                            }
                            break;
                        case '16':
                            if (item.value) {
                                //    $('input[name='+fieldName+']').val(item.value);
                                var url = '".$urlUpload."';

                                url = url + item.value;

                                var divFormGroup = fieldName + '-form-group';
                                var divWrapper = fieldName + '_crop_image';
                                var divPreview = fieldName + '_preview_image';
                                var divCropButton = fieldName + '_crop_button';
                                var cropResult = fieldName + '_crop_result';

                                $('[name=\''+cropResult+'\']').val('import_file_from_session::' + sessionId);
                                $('#' + divFormGroup).show();
                                $('#' + divWrapper).show();
                                $('#' + divCropButton).hide();
                                $('#' + divPreview).attr('src', url);
                                //$('[name=\''+fieldName+'\']')
                            }
                            break;
                    }
                });
            }
        });
    })
})
</script>";

$form->addButtonNext(get_lang('NextStep'));

if (!$formSent) {
    $formDefaults['access_start_date'] = $formDefaults['display_start_date'] = api_get_local_time();
    $formDefaults['coach_username'] = api_get_user_id();
} else {
    $formDefaults['name'] = api_htmlentities($name, ENT_QUOTES, $charset);
}

// Relation to prefill session extra field with user extra field
$fillExtraField = api_get_configuration_value('session_creation_user_course_extra_field_relation_to_prefill');
if (false !== $fillExtraField && !empty($fillExtraField['fields'])) {
    foreach ($fillExtraField['fields'] as $sessionVariable => $userVariable) {
        $extraValue = UserManager::get_extra_user_data_by_field(api_get_user_id(), $userVariable);
        $formDefaults['extra_'.$sessionVariable] = isset($extraValue[$userVariable]) ? $extraValue[$userVariable] : '';
    }
}

$form->setDefaults($formDefaults);

if ($form->validate()) {
    $params = $form->getSubmitValues();
    $name = $params['name'];
    $startDate = $params['access_start_date'];
    $endDate = $params['access_end_date'];
    $displayStartDate = $params['display_start_date'];
    $displayEndDate = $params['display_end_date'];
    $coachStartDate = $params['coach_access_start_date'];
    if (empty($coachStartDate)) {
        $coachStartDate = $displayStartDate;
    }
    $coachEndDate = $params['coach_access_end_date'];
    $coach_username = intval($params['coach_username']);
    $id_session_category = $params['session_category'];
    $id_visibility = $params['session_visibility'];
    $duration = isset($params['duration']) ? $params['duration'] : null;
    $description = $params['description'];
    $showDescription = isset($params['show_description']) ? 1 : 0;
    $sendSubscriptionNotification = isset($params['send_subscription_notification']);
    $isThisImageCropped = isset($params['picture_crop_result']);
    $status = isset($params['status']) ? $params['status'] : 0;

    $extraFields = [];
    foreach ($params as $key => $value) {
        if (strpos($key, 'extra_') === 0) {
            $extraFields[$key] = $value;
        }
    }

    if (isset($extraFields['extra_image']) && !empty($extraFields['extra_image']['name']) && $isThisImageCropped) {
        $extraFields['extra_image']['crop_parameters'] = $params['picture_crop_result'];
    }

    // Check if the session image will be copied from the template
    $importImageFromSession = false;
    $sessionIdToImport = explode('::', $params['extra_image_crop_result']);
    $sessionIdToImport = isset($sessionIdToImport[1]) ? (int) $sessionIdToImport[1] : 0;
    if (!empty($sessionIdToImport)) {
        $extraField = new ExtraField('session');
        $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable('image');

        $extraFieldValue = new ExtraFieldValue('session');
        $extraFieldValueData = $extraFieldValue->get_values_by_handler_and_field_id(
            $sessionIdToImport,
            $extraFieldInfo['id']
        );

        if ($extraFieldValueData && file_exists($sysUploadPath.$extraFieldValueData['value'])) {
            $extraFields['extra_image']['name'] = basename($extraFieldValueData['value']);
            $extraFields['extra_image']['tmp_name'] = $sysUploadPath.$extraFieldValueData['value'];
            $extraFields['extra_image']['type'] = 'image/png';
            $extraFields['extra_image']['error'] = 0;
            $extraFields['extra_image']['size'] = filesize($sysUploadPath.$extraFieldValueData['value']);
        }
    }

    $return = SessionManager::create_session(
        $name,
        $startDate,
        $endDate,
        $displayStartDate,
        $displayEndDate,
        $coachStartDate,
        $coachEndDate,
        $coach_username,
        $id_session_category,
        $id_visibility,
        false,
        $duration,
        $description,
        $showDescription,
        $extraFields,
        null,
        $sendSubscriptionNotification,
        api_get_current_access_url_id(),
        $status
    );

    if ($return == strval(intval($return))) {
        // integer => no error on session creation
        header('Location: add_courses_to_session.php?id_session='.$return.'&add=true');
        exit();
    }
}

Display::display_header($tool_name);

if (!empty($return)) {
    echo Display::return_message($return, 'error', false);
}

echo '<div class="actions">';
echo '<a href="../session/session_list.php">'.
    Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

$form->display();

Display::display_footer();
