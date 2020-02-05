<?php

/* For licensing terms, see /license.txt */

/**
 * Sessions edition script.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
$formSent = 0;

// Crop picture plugin for session images
$htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
$htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');

// Database Table Definitions
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

$id = (int) $_GET['id'];

SessionManager::protectSession($id);

$sessionInfo = SessionManager::fetch($id);

// Sets to local time to show it correctly when you edit a session
if (!empty($sessionInfo['display_start_date'])) {
    $sessionInfo['display_start_date'] = api_get_local_time($sessionInfo['display_start_date']);
}
if (!empty($sessionInfo['display_end_date'])) {
    $sessionInfo['display_end_date'] = api_get_local_time($sessionInfo['display_end_date']);
}

if (!empty($sessionInfo['access_start_date'])) {
    $sessionInfo['access_start_date'] = api_get_local_time($sessionInfo['access_start_date']);
}

if (!empty($sessionInfo['access_end_date'])) {
    $sessionInfo['access_end_date'] = api_get_local_time($sessionInfo['access_end_date']);
}

if (!empty($sessionInfo['coach_access_start_date'])) {
    $sessionInfo['coach_access_start_date'] = api_get_local_time($sessionInfo['coach_access_start_date']);
}

if (!empty($sessionInfo['coach_access_end_date'])) {
    $sessionInfo['coach_access_end_date'] = api_get_local_time($sessionInfo['coach_access_end_date']);
}

$tool_name = get_lang('EditSession');

$interbreadcrumb[] = ['url' => 'session_list.php', 'name' => get_lang('SessionList')];
$interbreadcrumb[] = ['url' => 'resume_session.php?id_session='.$id, 'name' => get_lang('SessionOverview')];

if (isset($_POST['formSent']) && $_POST['formSent']) {
    $formSent = 1;
}

$order_clause = 'ORDER BY ';
$order_clause .= api_sort_by_first_name() ? 'firstname, lastname, username' : 'lastname, firstname, username';

$sql = "SELECT user_id,lastname,firstname,username
        FROM $tbl_user
        WHERE status='1'".$order_clause;

if (api_is_multiple_url_enabled()) {
    $table_access_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != -1) {
        $sql = "SELECT DISTINCT u.user_id,lastname,firstname,username
                FROM $tbl_user u
                INNER JOIN $table_access_url_rel_user url_rel_user
                ON (url_rel_user.user_id = u.user_id)
                WHERE status='1' AND access_url_id = '$access_url_id' $order_clause";
    }
}

$result = Database::query($sql);
$coaches = Database::store_result($result);
$thisYear = date('Y');

$coachesOption = [
    '' => '----- '.get_lang('None').' -----',
];

foreach ($coaches as $coach) {
    $personName = api_get_person_name($coach['firstname'], $coach['lastname']);
    $coachesOption[$coach['user_id']] = "$personName ({$coach['username']})";
}

$categoriesList = SessionManager::get_all_session_category();

$categoriesOption = [
    '0' => get_lang('None'),
];

if ($categoriesList != false) {
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
$result = SessionManager::setForm($form, $sessionInfo);

$htmlHeadXtra[] = '
<script>
$(function() {
    '.$result['js'].'
});
</script>';

$form->addButtonUpdate(get_lang('ModifyThisSession'));

$formDefaults = $sessionInfo;

$formDefaults['coach_username'] = $sessionInfo['id_coach'];
$formDefaults['session_category'] = $sessionInfo['session_category_id'];
$formDefaults['session_visibility'] = $sessionInfo['visibility'];

if ($formSent) {
    $formDefaults['name'] = api_htmlentities($name, ENT_QUOTES, $charset);
} else {
    $formDefaults['name'] = Security::remove_XSS($sessionInfo['name']);
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
    $coachEndDate = $params['coach_access_end_date'];
    $coach_username = intval($params['coach_username']);
    $id_session_category = $params['session_category'];
    $id_visibility = $params['session_visibility'];
    $duration = isset($params['duration']) ? $params['duration'] : null;
    if ($params['access'] == 1) {
        $duration = null;
    }

    $description = $params['description'];
    $showDescription = isset($params['show_description']) ? 1 : 0;
    $sendSubscriptionNotification = isset($params['send_subscription_notification']);
    $isThisImageCropped = isset($params['picture_crop_result']);

    $extraFields = [];
    foreach ($params as $key => $value) {
        if (strpos($key, 'extra_') === 0) {
            $extraFields[$key] = $value;
        }
    }

    if (isset($extraFields['extra_image']) && $isThisImageCropped) {
        $extraFields['extra_image']['crop_parameters'] = $params['picture_crop_result'];
    }

    $status = isset($params['status']) ? $params['status'] : 0;

    $return = SessionManager::edit_session(
        $id,
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
        $description,
        $showDescription,
        $duration,
        $extraFields,
        null,
        $sendSubscriptionNotification,
        $status
    );

    if ($return) {
        Display::addFlash(Display::return_message(get_lang('Updated')));
        header('Location: resume_session.php?id_session='.$return);
        exit();
    }
}

// display the header
Display::display_header($tool_name);
$form->display();
?>

<script>
$(function() {
<?php
    if (!empty($sessionInfo['duration'])) {
        echo 'accessSwitcher(0);';
    } else {
        echo 'accessSwitcher(1);';
    }
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
