<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\User;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

$id = (int) $_GET['id'];

$session = api_get_session_entity($id);
SessionManager::protectSession($session);

$tool_name = get_lang('Edit this session');

$interbreadcrumb[] = ['url' => 'session_list.php', 'name' => get_lang('Session list')];
$interbreadcrumb[] = ['url' => 'resume_session.php?id_session='.$id, 'name' => get_lang('Session overview')];

$categoriesList = SessionManager::get_all_session_category();

$categoriesOption = [
    '0' => get_lang('none'),
];

if (false != $categoriesList) {
    foreach ($categoriesList as $categoryItem) {
        $categoriesOption[$categoryItem['id']] = $categoryItem['name'];
    }
}

$formAction = api_get_self().'?';
$formAction .= http_build_query([
    'page' => Security::remove_XSS($_GET['page']),
    'id' => $id,
]);

$form = new FormValidator('edit_session', 'post', $formAction);
$form->addElement('header', $tool_name);
$result = SessionManager::setForm($form, $session);
$htmlHeadXtra[] = '
<script>
$(function() {
    '.$result['js'].'
});
</script>';

$form->addButtonUpdate(get_lang('Edit this session'));

$formDefaults = [
    'id' => $session->getId(),
    'session_category' => $session->getCategory()?->getId(),
    'name' => $session->getName(),
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
    'coach_username' => array_map(
        function (User $user) {
            return $user->getId();
        },
        $session->getGeneralCoaches()->getValues()
    ),
];

$form->setDefaults($formDefaults);

if ($form->validate()) {
    $params = $form->getSubmitValues();

    $name = $params['name'];
    $startDate = $params['access_start_date'];
    $endDate = $params['access_end_date'];
    $displayStartDate = $params['display_start_date'];
    $displayEndDate = $params['display_end_date'];
    $coachStartDate = $params['coach_access_start_date'];
    $coachEndDate = $params['coach_access_end_date'];
    $coachUsername = $params['coach_username'];
    $id_session_category = $params['session_category'];
    $id_visibility = $params['session_visibility'];
    $duration = isset($params['duration']) ? $params['duration'] : null;
    if (1 == $params['access']) {
        $duration = null;
    }

    $description = $params['description'];
    $showDescription = isset($params['show_description']) ? 1 : 0;
    $sendSubscriptionNotification = isset($params['send_subscription_notification']);
    $isThisImageCropped = isset($params['picture_crop_result']);

    $extraFields = [];
    foreach ($params as $key => $value) {
        if (0 === strpos($key, 'extra_')) {
            $extraFields[$key] = $value;
        }
    }

    if (isset($extraFields['extra_image']) && $isThisImageCropped) {
        $extraFields['extra_image']['crop_parameters'] = $params['picture_crop_result'];
    }

    $status = $params['status'] ?? 0;

    $return = SessionManager::edit_session(
        $id,
        $name,
        $startDate,
        $endDate,
        $displayStartDate,
        $displayEndDate,
        $coachStartDate,
        $coachEndDate,
        $coachUsername,
        $id_session_category,
        $id_visibility,
        $description,
        $showDescription,
        $duration,
        $extraFields,
        null,
        $sendSubscriptionNotification,
        $status
    );

    if ($return) {
        Display::addFlash(Display::return_message(get_lang('Update successful')));
        header('Location: resume_session.php?id_session='.$return);
        exit();
    }
}

Display::display_header($tool_name);
$form->display();
?>

<script>
$(function() {
<?php
echo $session->getDuration() > 0 ? 'accessSwitcher(0);' : 'accessSwitcher(1);';
?>
});

function accessSwitcher(accessFromReady) {
    var access = $('#access option:selected').val();

    if (accessFromReady >= 0) {
        access = accessFromReady;
        $('[name=access]').val(access);
    }
    if (access == 1) {
        $('#duration_div').hide();
        $('#date_fields').show();
        emptyDuration();
    } else {
        $('#duration_div').show();
        $('#date_fields').hide();
    }
}

function emptyDuration() {
    if ($('#duration').val()) {
        $('#duration').val('');
    }
}

$(function() {
    $('#show-options').on('click', function (e) {
        e.preventDefault();
        var display = $('#options').css('display');
        display === 'block' ? $('#options').slideUp() : $('#options').slideDown() ;
    });
});

</script>
<?php
Display::display_footer();
