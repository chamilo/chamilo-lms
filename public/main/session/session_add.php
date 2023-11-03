<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Entity\User;

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

$interbreadcrumb[] = [
    'url' => 'session_list.php',
    'name' => get_lang('Session list'),
];

function search_coachs($needle)
{
    $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
    $xajax_response = new xajaxResponse();
    $return = '';

    if (!empty($needle)) {
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
            if (-1 != $access_url_id) {
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
        while ($user = Database :: fetch_array($rs)) {
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

$tool_name = get_lang('Add a training session');

$urlAction = api_get_self();

function check_session_name($name)
{
    $session = SessionManager::get_session_by_name($name);

    return empty($session) ? true : false;
}

$session = null;
$fromSessionId = null;
if (isset($_GET['fromSessionId'])) {
    $fromSessionId = (int) $_GET['fromSessionId'];
    $session = api_get_session_entity($fromSessionId);
    $urlAction .= '?fromSessionId=' . $fromSessionId;
}
$form = new FormValidator('add_session', 'post', $urlAction);
$form->addElement('header', $tool_name);
$result = SessionManager::setForm($form, null, $fromSessionId);

$url = api_get_path(WEB_AJAX_PATH).'session.ajax.php';
$urlAjaxExtraField = api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php?1=1';

$htmlHeadXtra[] = "
<script>
$(function() {
    ".$result['js']."
    $('#system_template').on('change', function() {
        var sessionId = $(this).find('option:selected').val();
        window.location.href = '/main/session/session_add.php?fromSessionId=' + sessionId;
    });
});
</script>";

$form->addButtonNext(get_lang('Next step'));

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
            'coach_username' => array_map(
                function (User $user) {
                    return $user->getId();
                },
                $session->getGeneralCoaches()->getValues()
            ),
            'session_template' => $session->getName(),
        ];
    } else {
        $formDefaults['access_start_date'] = $formDefaults['display_start_date'] = api_get_local_time();
        $formDefaults['coach_username'] = [api_get_user_id()];
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
    $coachUsername = $params['coach_username'];
    $id_session_category = (int) $params['session_category'];
    $id_visibility = $params['session_visibility'];
    $duration = isset($params['duration']) ? $params['duration'] : null;
    $description = $params['description'];
    $showDescription = isset($params['show_description']) ? 1 : 0;
    $sendSubscriptionNotification = isset($params['send_subscription_notification']);
    $isThisImageCropped = isset($params['picture_crop_result']);
    $status = isset($params['status']) ? $params['status'] : 0;

    $extraFields = [];
    foreach ($params as $key => $value) {
        if (0 === strpos($key, 'extra_')) {
            $extraFields[$key] = $value;
        }
    }

    if (isset($extraFields['extra_image']) && !empty($extraFields['extra_image']['name']) && $isThisImageCropped) {
        $extraFields['extra_image']['crop_parameters'] = $params['picture_crop_result'];
    }

    $return = SessionManager::create_session(
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

$actions = '<a href="../session/session_list.php">'.
    Display::return_icon('back.png', get_lang('Back to').' '.get_lang('Administration'), '', ICON_SIZE_MEDIUM).'</a>';
echo Display::toolbarAction('session', [$actions]);
$form->display();

Display::display_footer();
