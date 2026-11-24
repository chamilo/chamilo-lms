<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

SessionManager::protectSession(null, false);

api_protect_limit_for_session_admin();

$formSent = 0;
$errorMsg = '';

$interbreadcrumb[] = [
    'url' => '/admin/session-list',
    'name' => get_lang('Session list'),
];

$urlAction = api_get_self();
$session = null;
$fromSessionId = null;
$accessSelected = 1;
if (isset($_GET['fromSessionId'])) {
    $fromSessionId = (int) $_GET['fromSessionId'];
    $session = api_get_session_entity($fromSessionId);
    if ($session && 0 === (int) $session->getDuration()) {
        $accessSelected = 1;
    }
    $urlAction .= '?fromSessionId=' . $fromSessionId;
}

$htmlHeadXtra[] = "
<script>
$(function() {
   setTimeout(function() {
        $('#access').val('".$accessSelected."').trigger('change');
        accessSwitcher('".$accessSelected."');
    }, 1000);
});

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

if (isset($_POST['formSent'])) {
    $formSent = (int) $_POST['formSent'];
}

$tool_name = get_lang('Add a training session');

function check_session_name($name)
{
    $session = SessionManager::get_session_by_name($name);

    return empty($session) ? true : false;
}

$form = new FormValidator('add_session', 'post', $urlAction);
$form->addElement('header', $tool_name);
$result = SessionManager::setForm($form, null, $fromSessionId);

$url = api_get_path(WEB_AJAX_PATH).'session.ajax.php';
$urlAjaxExtraField = api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php?1=1';

$htmlHeadXtra[] = "
<script>
$(function() {
    var currentSessionId = new URL(window.location.href).searchParams.get('fromSessionId');

    function storeFormValues() {
        var formValues = $('#add_session').serializeArray();
        sessionStorage.setItem('formValues', JSON.stringify(formValues));
    }

    function repopulateFormValues() {
        var formValues = JSON.parse(sessionStorage.getItem('formValues'));
        $.each(formValues, function(i, field) {
            if (field.name === 'coach_username' || field.name === 'title' || field.name === 'system_template') {
                $('[name=\"' + field.name + '\"]').val(field.value);
            }
        });
    }

    function storeSelect2Values(selectId) {
        var selectedValues = $('#' + selectId).select2('data').map(function(item) {
            return {id: item.id, text: item.text};
        });
        sessionStorage.setItem(selectId + 'Values', JSON.stringify(selectedValues));
    }

    function repopulateSelect2Values(selectId) {
        if(sessionStorage.getItem(selectId + 'Values')) {
            var storedValues = JSON.parse(sessionStorage.getItem(selectId + 'Values'));
            $('#' + selectId).empty(); // Clear the select
            storedValues.forEach(function(item) {
                var newOption = new Option(item.text, item.id, true, true);
                $('#' + selectId).append(newOption).trigger('change');
            });
        }
    }

    if(currentSessionId) {
        if(sessionStorage.getItem('formValues')) {
            repopulateFormValues();
        }
        repopulateSelect2Values('coach_username');
        repopulateSelect2Values('system_template');
    } else {
        sessionStorage.clear(); // Clear session storage if no currentSessionId
    }

    $('#system_template').on('change', function() {
        storeFormValues();
        storeSelect2Values('coach_username');
        storeSelect2Values('system_template');
        var selectedSessionId = $(this).find('option:selected').val();
        window.location.href = '/main/session/session_add.php?fromSessionId=' + selectedSessionId;
    });

    // Attach event to form submit to clear sessionStorage
    $('#add_session').on('submit', function() {
        sessionStorage.removeItem('coach_usernameValues');
        sessionStorage.removeItem('system_templateValues');
        sessionStorage.removeItem('formValues');
    });

    ".$result['js']."
});
</script>";

$form->addButtonNext(get_lang('Next step'));
$showValidityField = 'true' === api_get_setting('session.enable_auto_reinscription') || 'true' === api_get_setting('session.enable_session_replication');

$formDefaults = [];
if (!$formSent) {
    if ($session) {
        $formDefaults = [
            'id' => $session->getId(),
            'session_category' => $session->getCategory()?->getId(),
            'description' => $session->getDescription(),
            'show_description' => $session->getShowDescription(),
            'duration' => $session->getDuration(),
            'session_visibility' => $session->getVisibility(),
            'display_start_date' => $session->getDisplayStartDate() ? api_get_local_time($session->getDisplayStartDate()) : null,
            'display_end_date' => $session->getDisplayEndDate() ? api_get_local_time($session->getDisplayEndDate()) : null,
            'access_start_date' => $session->getAccessStartDate() ? api_get_local_time($session->getAccessStartDate()) : null,
            'access_end_date' => $session->getAccessEndDate() ? api_get_local_time($session->getAccessEndDate()) : null,
            'coach_access_start_date' => $session->getCoachAccessStartDate() ? api_get_local_time($session->getCoachAccessStartDate()) : null,
            'coach_access_end_date' => $session->getCoachAccessEndDate() ? api_get_local_time($session->getCoachAccessEndDate()) : null,
            'send_subscription_notification' => $session->getSendSubscriptionNotification(),
            'notify_boss' => $session->getNotifyBoss(),
            'coach_username' => array_map(
                function (User $user) {
                    return $user->getId();
                },
                $session->getGeneralCoaches()->getValues()
            ),
            'session_template' => $session->getTitle(),
            'days_before_finishing_for_reinscription' => $session->getDaysToReinscription() ?? '',
            'days_before_finishing_to_create_new_repetition' => $session->getDaysToNewRepetition() ?? '',
            'last_repetition' => $session->getLastRepetition(),
            'parent_id' => $session->getParentId() ?? 0,
        ];

        if ($showValidityField) {
            $formDefaults['validity_in_days'] = $session->getValidityInDays();
        }

    } else {
        $formDefaults['access_start_date'] = $formDefaults['display_start_date'] = api_get_local_time();
        $formDefaults['coach_username'] = [api_get_user_id()];
        if ($showValidityField) {
            $formDefaults['validity_in_days'] = null;
        }
    }
}

$form->setDefaults($formDefaults);

if ($form->validate()) {
    $params = $form->getSubmitValues();
    $title = $params['title'];
    $startDate = $params['access_start_date'];
    $endDate = $params['access_end_date'];
    $displayStartDate = $params['display_start_date'];
    $displayEndDate = $params['display_end_date'];
    $coachStartDate = $params['coach_access_start_date'] ?? $displayStartDate;
    $coachEndDate = $params['coach_access_end_date'];
    $coachUsername = $params['coach_username'] ?? [];

    if (empty($coachUsername)) {
        // Keep the historical default behavior: current user becomes the coach
        $coachUsername = [api_get_user_id()];
    }

    if (!is_array($coachUsername)) {
        $coachUsername = [$coachUsername];
    }

    $id_session_category = (int) $params['session_category'];
    $id_visibility = $params['session_visibility'];
    $duration = $params['duration'] ?? null;
    $description = $params['description'];
    $showDescription = isset($params['show_description']) ? 1 : 0;
    $sendSubscriptionNotification = isset($params['send_subscription_notification']);
    $isThisImageCropped = isset($params['picture_crop_result']);
    $status = isset($params['status']) ? $params['status'] : 0;
    $notifyBoss = isset($params['notify_boss']) ? 1 : 0;

    $extraFields = [];
    foreach ($params as $key => $value) {
        if (0 === strpos($key, 'extra_')) {
            $extraFields[$key] = $value;
        }
    }

    if (isset($extraFields['extra_image']) && !empty($extraFields['extra_image']['name']) && $isThisImageCropped) {
        $extraFields['extra_image']['crop_parameters'] = $params['picture_crop_result'];
    }

    // Check if the session image will be copied from the template
    $importImageFromSession = false;
    $sessionIdToImport = !empty($params['extra_image_crop_result']) ? explode('::', $params['extra_image_crop_result']) : [];
    $sessionIdToImport = isset($sessionIdToImport[1]) ? (int) $sessionIdToImport[1] : 0;
    if (!empty($sessionIdToImport)) {
        $extraField = new ExtraField('session');
        $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable('image');

        $extraFieldValue = new ExtraFieldValue('session');
        $extraFieldValueData = $extraFieldValue->get_values_by_handler_and_field_id(
            $sessionIdToImport,
            $extraFieldInfo['id']
        );

        if ($extraFieldValueData) {
            $repo = Container::getAssetRepository();
            /** @var Asset $asset */
            $asset = $repo->find($extraFieldValueData);
            if ($asset) {
                $extraFields['extra_image']['id'] = $extraFieldValueData;
            }
        }
    }
    $status = $params['status'] ?? 0;

    $parentId = $params['parent_id'] ?? null;
    $daysBeforeFinishingForReinscription = $params['days_before_finishing_for_reinscription'] ?? null;
    $lastRepetition = isset($params['last_repetition']) ? true : false;
    $daysBeforeFinishingToCreateNewRepetition = $params['days_before_finishing_to_create_new_repetition'] ?? null;
    $validityInDays = $params['validity_in_days'] ?? null;
    if (is_null($coachUsername)) {
        $coachUsername = [];
    }

    $return = SessionManager::create_session(
        $title,
        $startDate,
        $endDate,
        $displayStartDate,
        $displayEndDate,
        $coachStartDate,
        $coachEndDate,
        $coachUsername,
        $id_session_category,
        $id_visibility,
        false,
        $duration,
        $description,
        $showDescription,
        $extraFields,
        0,
        $sendSubscriptionNotification,
        api_get_current_access_url_id(),
        $status,
        $notifyBoss,
        $parentId,
        $daysBeforeFinishingForReinscription,
        $lastRepetition,
        $daysBeforeFinishingToCreateNewRepetition,
        $validityInDays
    );

    if ($return == strval(intval($return))) {
        if (!empty($_FILES['picture']['tmp_name'])) {
            // Add image
            $picture = $_FILES['picture'];
            if (!empty($picture['name'])) {
                SessionManager::updateSessionPicture(
                    $return,
                    $picture,
                    $params['picture_crop_result']
                );
            }
        } else {
            if (isset($_POST['image_session_template'])) {
                $assetUrl = Security::remove_XSS($_POST['image_session_template']);
                $path = parse_url($assetUrl, PHP_URL_PATH);
                $filename = basename($path);
                $tmpName = api_get_path(SYS_PATH).'../var/upload'.$path;
                $fileArray = [
                    'tmp_name' => $tmpName,
                    'name' => $filename,
                    'error' => 0,
                    'size' => filesize($tmpName),
                ];
                SessionManager::updateSessionPicture(
                    $return,
                    $fileArray
                );
            }
        }

        // integer => no error on session creation
        header('Location: add_courses_to_session.php?id_session='.$return.'&add=true');
        exit();
    }
}

Display::display_header($tool_name);

if (!empty($return)) {
    echo Display::return_message($return, 'error', false);
}

$actions = '<a href="/admin/session-list">'.
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to').' '.get_lang('Administration')).'</a>';
echo Display::toolbarAction('session', [$actions]);
$form->display();

Display::display_footer();
