<?php
/* For licensing terms, see /license.txt */

/**
*	@package chamilo.admin
*/

$cidReset = true;

// including the global Chamilo file
require_once '../inc/global.inc.php';

$xajax = new xajax();
$xajax->registerFunction('search_coachs');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

SessionManager::protectSession(null, false);

api_protect_limit_for_session_admin();

$formSent=0;
$errorMsg='';

/*$interbreadcrumb[] = array(
    'url' => 'index.php',
    'name' => get_lang('PlatformAdmin'),
);*/
$interbreadcrumb[] = array(
    'url' => 'session_list.php',
    'name' => get_lang('SessionList'),
);

// Database Table Definitions
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);

function search_coachs($needle) {
	global $tbl_user;

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
			$tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
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
		while ($user = Database :: fetch_array($rs)) {
			$return .= '<a href="javascript: void(0);" onclick="javascript: fill_coach_field(\''.$user['username'].'\')">'.api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].')</a><br />';
		}
	}
	$xajax_response -> addAssign('ajax_list_coachs','innerHTML', api_utf8_encode($return));
	return $xajax_response;
}
$xajax -> processRequests();

$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = "
<script type=\"text/javascript\">
function fill_coach_field (username) {
	document.getElementById('coach_username').value = username;
	document.getElementById('ajax_list_coachs').innerHTML = '';
}

function setDisable(select){
	document.forms['edit_session'].elements['session_visibility'].disabled = (select.checked) ? true : false;
	document.forms['edit_session'].elements['session_visibility'].selectedIndex = 0;

    document.forms['edit_session'].elements['start_limit'].disabled = (select.checked) ? true : false;
    document.forms['edit_session'].elements['start_limit'].checked = false;
    document.forms['edit_session'].elements['end_limit'].disabled = (select.checked) ? true : false;
    document.forms['edit_session'].elements['end_limit'].checked = false;

    var end_div = document.getElementById('end_date');
    end_div.style.display = 'none';

    var start_div = document.getElementById('start_date');
    start_div.style.display = 'none';
}

function disable_endtime(select) {
    var end_div = document.getElementById('end_date');
    if (end_div.style.display == 'none')
        end_div.style.display = 'block';
     else
        end_div.style.display = 'none';

    emptyDuration();
}

function disable_starttime(select) {
    var start_div = document.getElementById('start_date');
    if (start_div.style.display == 'none')
        start_div.style.display = 'block';
     else
        start_div.style.display = 'none';

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

global $_configuration;
$before = api_get_setting('session_days_before_coach_access');
$defaultBeforeDays =  $before ? $before : 0;
$after = api_get_setting('session_days_after_coach_access');
$defaultAfterDays = $after ? $after : 0;

$nb_days_access_before = $defaultBeforeDays;
$nb_days_access_after = $defaultAfterDays;

$thisYear = date('Y');
$thisMonth = date('m');
$thisDay = date('d');
$tool_name = get_lang('AddSession');

$defaultStart = "$thisYear-$thisMonth-$thisDay 00:00:00";
$defaultEnd = date('Y-m-d', strtotime("$thisYear-$thisMonth-$thisDay +364 day")) . ' 23:59:59';
$start = new DateTime($defaultStart);
$end = new DateTime($defaultEnd);
$startDiff = new DateInterval('P' . $nb_days_access_before . 'D');
$endDiff = new DateInterval('P' . $nb_days_access_after . 'D');
$defaultCoachStart = $start->sub($startDiff);
$defaultCoachEnd = $end->add($endDiff);

$urlAction = api_get_self();

$categoriesList = SessionManager::get_all_session_category();

$categoriesOptions = array(
    '0' => get_lang('None')
);

if ($categoriesList != false) {
    foreach ($categoriesList as $categoryItem) {
        $categoriesOptions[$categoryItem['id']] = $categoryItem['name'];
    }
}

function check_session_name($name) {
    $session = SessionManager::get_session_by_name($name);

    return empty($session) ? true : false;
}

$form = new FormValidator('add_session', 'post', $urlAction);

$form->addElement('header', $tool_name);

$form->addElement('text', 'name', get_lang('SessionName'), array(
    'maxlength' => 50,
    'value' => $formSent ? api_htmlentities($name, ENT_QUOTES, $charset) : '',
));
$form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('name', get_lang('SessionNameAlreadyExists'), 'callback', 'check_session_name');
$userInfo = api_get_user_info();

if (!api_is_platform_admin() && api_is_teacher()) {
    $form->addElement(
        'select',
        'coach_username',
        get_lang('CoachName'),
        [api_get_user_id() => $userInfo['complete_name']],
        array(
            'id' => 'coach_username',
            'class' => 'chzn-select',
            'style' => 'width:370px;'
        )
    );
} else {

    $sql = "SELECT COUNT(1) FROM $tbl_user WHERE status = 1";
    $rs = Database::query($sql);
    $countUsers = Database::result($rs, 0, 0);

    if (intval($countUsers) < 50) {
        $orderClause = "ORDER BY ";
        $orderClause .= api_sort_by_first_name() ? "firstname, lastname, username" : "lastname, firstname, username";

        $sql = "SELECT user_id, lastname, firstname, username
                FROM $tbl_user
                WHERE status = '1' ".
            $orderClause;

        if (api_is_multiple_url_enabled()) {
            $userRelAccessUrlTable = Database::get_main_table(
                TABLE_MAIN_ACCESS_URL_REL_USER
            );
            $accessUrlId = api_get_current_access_url_id();

            if ($accessUrlId != -1) {
                $sql = "SELECT user.user_id, username, lastname, firstname
                        FROM $tbl_user user
                        INNER JOIN $userRelAccessUrlTable url_user
                        ON (url_user.user_id = user.user_id)
                        WHERE
                            access_url_id = $accessUrlId AND
                            status = 1 "
                    .$orderClause;
            }
        }

        $result = Database::query($sql);
        $coachesList = Database::store_result($result);

        $coachesOptions = array();
        foreach ($coachesList as $coachItem) {
            $coachesOptions[$coachItem['user_id']] =
                api_get_person_name($coachItem['firstname'], $coachItem['lastname']).' ('.$coachItem['username'].')';
        }

        $form->addElement(
            'select',
            'coach_username',
            get_lang('CoachName'),
            $coachesOptions,
            array(
                'id' => 'coach_username',
                'class' => 'chzn-select',
                'style' => 'width:370px;'
            )
        );
    } else {
        $form->addElement(
            'text',
            'coach_username',
            get_lang('CoachName'),
            array(
                'maxlength' => 50,
                'onkeyup' => "xajax_search_coachs(document.getElementById('coach_username').value)",
                'id' => 'coach_username'
            )
        );
    }
}

$form->addRule('coach_username', get_lang('ThisFieldIsRequired'), 'required');
$form->addHtml('<div id="ajax_list_coachs"></div>');

$form->addButtonAdvancedSettings('advanced_params');
$form->addElement('html','<div id="advanced_params_options" style="display:none">');

$form->addSelect('session_category', get_lang('SessionCategory'), $categoriesOptions, array(
    'id' => 'session_category',
    'class' => 'chzn-select',
    'style' => 'width:370px;'
));

$form->addHtmlEditor(
    'description',
    get_lang('Description'),
    false,
    false,
    array(
        'ToolbarSet' => 'Minimal'
    )
);

$form->addElement('checkbox', 'show_description', null, get_lang('ShowDescription'));
$form->addElement('date_time_picker', 'display_start_date', array(get_lang('SessionDisplayStartDate'), get_lang('SessionDisplayStartDateComment')));
$form->addElement('date_time_picker', 'display_end_date', array(get_lang('SessionDisplayEndDate'), get_lang('SessionDisplayEndDateComment')));
$form->addElement('date_time_picker', 'access_start_date', array(get_lang('SessionAccessStartDate'), get_lang('SessionAccessStartDateComment')));
$form->addElement('date_time_picker', 'access_end_date', array(get_lang('SessionAccessEndDate'), get_lang('SessionAccessEndDateComment')));
$form->addElement('date_time_picker', 'coach_access_start_date', array(get_lang('CoachSessionAccessStartDate'), get_lang('CoachSessionAccessStartDateComment')));
$form->addElement('date_time_picker', 'coach_access_end_date', array(get_lang('CoachSessionAccessEndDate'), get_lang('CoachSessionAccessEndDateComment')));

$visibilityGroup = array();
$visibilityGroup[] = $form->createElement('select', 'session_visibility', null, array(
    SESSION_VISIBLE_READ_ONLY => get_lang('SessionReadOnly'),
    SESSION_VISIBLE => get_lang('SessionAccessible'),
    SESSION_INVISIBLE => api_ucfirst(get_lang('SessionNotAccessible'))
));
$form->addGroup($visibilityGroup, 'visibility_group', get_lang('SessionVisibility'), null, false);

$form->addElement('html','</div>');

$form->addElement(
    'text',
    'duration',
    array(
        get_lang('SessionDurationTitle'),
        get_lang('SessionDurationDescription')
    ),
    array(
        'maxlength' => 50
    )
);

// Extra fields
$extra_field = new ExtraField('session');
$extra = $extra_field->addElements($form, null);

$form->addElement('html','</div>');

$htmlHeadXtra[] ='
<script>

$(function() {
    '.$extra['jquery_ready_content'].'
});
</script>';

$form->addButtonNext(get_lang('NextStep'));

if (!$formSent) {
    $formDefaults['display_start_date'] = $defaultStart;
    $formDefaults['display_end_date'] = $defaultEnd;
    $formDefaults['access_start_date'] = $defaultStart;
    $formDefaults['access_end_date'] = $defaultEnd;
    $formDefaults['coach_access_start_date'] = $defaultCoachStart->format('Y-m-d H:i:s');
    $formDefaults['coach_access_end_date'] = $defaultCoachEnd->format('Y-m-d H:i:s');
} else {
    $formDefaults['name'] = api_htmlentities($name, ENT_QUOTES, $charset);
}

$form->setDefaults($formDefaults);

if ($form->validate()) {
    $params = $form->getSubmitValues();

    $name = $params['name'];
    $displayStartDate = $params['display_start_date'];
    $displayEndDate = $params['display_end_date'];
    $startDate = $params['access_start_date'];
    $endDate = $params['access_end_date'];
    $coachAccessStartDate = $params['coach_access_start_date'];
    $coachAccessEndDate = $params['coach_access_end_date'];
    $coach_username = intval($params['coach_username']);
    $id_session_category = $params['session_category'];
    $id_visibility = $params['session_visibility'];
    $end_limit = isset($params['end_limit']);
    $start_limit = isset($params['start_limit']);
    $duration = isset($params['duration']) ? $params['duration'] : null;
    $description = $params['description'];
    $showDescription = isset($params['show_description']) ? 1: 0;

    $noLimit = true;
    if (!empty($end_limit) && !empty($start_limit)) {
        $noLimit = false;
    }

    $extraFields = array();

    foreach ($params as $key => $value) {
        if (strpos($key, 'extra_') === 0) {
            $extraFields[$key] = $value;
        }
    }

    $return = SessionManager::create_session(
        $name,
        $startDate,
        $endDate,
        $coachAccessStartDate,
        $coachAccessEndDate,
        $noLimit,
        $coach_username,
        $id_session_category,
        $id_visibility,
        $start_limit,
        $end_limit,
        false,
        $duration,
        $displayStartDate,
        $displayEndDate,
        $description,
        $showDescription,
        $extraFields
    );

    if ($return == strval(intval($return))) {
        // integer => no error on session creation
        header('Location: add_courses_to_session.php?id_session=' . $return . '&add=true&msg=');
        exit();
    }
}

Display::display_header($tool_name);

if (!empty($return)) {
    Display::display_error_message($return,false);
}

echo '<div class="actions">';
echo '<a href="../session/session_list.php">'.
    Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

$form->display();

Display::display_footer();
