<?php
/* For licensing terms, see /license.txt */

/**
*	@package chamilo.admin
*/

// name of the language file that needs to be included
$language_file='admin';

$cidReset=true;

// including the global Chamilo file
require_once '../inc/global.inc.php';

$xajax = new xajax();
//$xajax->debugOn();
$xajax -> registerFunction ('search_coachs');

// setting the section (for the tabs)
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);

$formSent=0;
$errorMsg='';

$interbreadcrumb[]=array('url' => 'index.php',       'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => 'session_list.php','name' => get_lang('SessionList'));

// Database Table Definitions
$tbl_user		= Database::get_main_table(TABLE_MAIN_USER);

function search_coachs($needle) {
	global $tbl_user;

	$xajax_response = new XajaxResponse();
	$return = '';

	if(!empty($needle)) {
		// xajax send utf8 datas... datas in db can be non-utf8 datas
		$charset = api_get_system_encoding();
		$needle = api_convert_encoding($needle, $charset, 'utf-8');

		$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';

		// search users where username or firstname or lastname begins likes $needle
		$sql = 'SELECT username, lastname, firstname FROM '.$tbl_user.' user
				WHERE (username LIKE "'.$needle.'%"
				OR firstname LIKE "'.$needle.'%"
				OR lastname LIKE "'.$needle.'%")
				AND status=1'.
				$order_clause.
				' LIMIT 10';

		if (api_is_multiple_url_enabled()) {
			$tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1){

				$sql = 'SELECT username, lastname, firstname FROM '.$tbl_user.' user
				INNER JOIN '.$tbl_user_rel_access_url.' url_user ON (url_user.user_id=user.user_id)
				WHERE access_url_id = '.$access_url_id.'  AND (username LIKE "'.$needle.'%"
				OR firstname LIKE "'.$needle.'%"
				OR lastname LIKE "'.$needle.'%")
				AND status=1'.
				$order_clause.
				' LIMIT 10';

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

$(document).on('ready', function () {
    var value = 1;
    $('#advanced_parameters').on('click', function() {
        $('#options').toggle(function() {
            if (value == 1) {
                $('#advanced_parameters').addClass('btn-hide');
                value = 0;
            } else {
                $('#advanced_parameters').removeClass('btn-hide');
                value = 1;
            }
        });
    });
});

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
$defaultBeforeDays = isset($_configuration['session_days_before_coach_access']) ?
    $_configuration['session_days_before_coach_access'] : 0;
$defaultAfterDays = isset($_configuration['session_days_after_coach_access'])
    ? $_configuration['session_days_after_coach_access'] : 0;

$nb_days_acess_before = $defaultBeforeDays;
$nb_days_acess_after = $defaultAfterDays;

$thisYear=date('Y');
$thisMonth=date('m');
$thisDay=date('d');

$dayList = array();

for ($i = 1; $i <= 31; $i++) {
    $day = sprintf("%02d", $i);
    $dayList[$day] = $day;
}

$monthList = array();

for ($i = 1; $i <= 12; $i++) {
    $month = sprintf("%02d", $i);

    $monthList[$month] = $month;
}

$yearList = array();

for ($i = $thisYear - 5; $i <= ($thisYear + 5); $i++) {
    $yearList[$i] = $i;
}

$tool_name = get_lang('AddSession');

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
    'class' => 'span4',
    'maxlength' => 50,
    'value' => $formSent ? api_htmlentities($name,ENT_QUOTES,$charset) : ''
));
$form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('name', get_lang('SessionNameAlreadyExists'), 'callback', 'check_session_name');

$sql = "SELECT COUNT(1) FROM $tbl_user WHERE status = 1";
$rs = Database::query($sql);
$countUsers = Database::result($rs, 0, 0);

if (intval($countUsers) < 50) {
    $orderClause = "ORDER BY ";
    $orderClause .= api_sort_by_first_name() ? "firstname, lastname, username"  : "lastname, firstname, username";

    $sql="SELECT user_id, lastname, firstname, username FROM $tbl_user "
        . "WHERE status = '1' "
        . $orderClause;

    if (api_is_multiple_url_enabled()) {
        $userRelAccessUrlTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $accessUrlId = api_get_current_access_url_id();

        if ($accessUrlId != -1) {
            $sql = "SELECT user.user_id, username, lastname, firstname FROM $tbl_user user "
                . "INNER JOIN $userRelAccessUrlTable url_user ON (url_user.user_id = user.user_id) "
                . "WHERE access_url_id = $accessUrlId AND status = 1 "
                . $orderClause;
        }
    }

    $result = Database::query($sql);
	$coachesList = Database::store_result($result);

    $coachesOptions = array();

    foreach($coachesList as $coachItem){
        $coachesOptions[$coachItem['username']] = api_get_person_name(
            $coachItem['firstname'],
            $coachItem['lastname']
        ).' ('.$coachItem['username'].')';
    }

    $form->addElement('select', 'coach_username', get_lang('CoachName'), $coachesOptions, array(
        'id' => 'coach_username',
        'class' => 'chzn-select',
        'style' => 'width:350px;'
    ));
    $form->addElement('advanced_settings', Display::return_icon('synthese_view.gif') . ' ' . get_lang('ActivityCoach'));
} else {
    $form->addElement('text', 'coach_username', get_lang('CoachName'), array(
        'class' => 'span4',
        'maxlength' => 50,
        'onkeyup' => "xajax_search_coachs(document.getElementById('coach_username').value)",
        'id' => 'coach_username'
    ));
}

$form->addRule('coach_username', get_lang('ThisFieldIsRequired'), 'required');
$form->add_html('<div id="ajax_list_coachs"></div>');

$form->add_select('session_category', get_lang('SessionCategory'), $categoriesOptions, array(
    'id' => 'session_category',
    'class' => 'chzn-select',
    'style' => 'width:350px;'
));

$form->addElement('advanced_settings','<a class="btn-show" id="advanced_parameters" href="javascript://">'.get_lang('DefineSessionOptions').'</a>');

$form->addElement('html','<div id="options" style="display:none">');

$form->addElement('text', 'nb_days_acess_before', array('', '', get_lang('DaysBefore')), array(
    'style' => 'width: 30px;',
    'value' => $nb_days_acess_before
));

$form->addElement('text', 'nb_days_acess_after', array('', '', get_lang('DaysAfter')), array(
    'style' => 'width: 30px;',
    'value' => $nb_days_acess_after
));

$form->addElement('html','</div>');

$form->addElement('checkbox', 'start_limit', '', get_lang('DateStartSession'), array(
    'onchange' => 'disable_starttime(this)',
    'id' => 'start_limit'
));

$form->addElement('html','<div id="start_date" style="display:none">');

$form->addElement('date_picker', 'date_start');

$form->addElement('html','</div>');

$form->addElement('checkbox', 'end_limit', '', get_lang('DateEndSession'), array(
    'onchange' => 'disable_endtime(this)',
    'id' => 'end_limit'
));

$form->addElement('html','<div id="end_date" style="display:none">');

$form->addElement('date_picker', 'date_end');

$visibilityGroup = array();
$visibilityGroup[] = $form->createElement('advanced_settings', get_lang('SessionVisibility'));
$visibilityGroup[] = $form->createElement('select', 'session_visibility', null, array(
    SESSION_VISIBLE_READ_ONLY => get_lang('SessionReadOnly'),
    SESSION_VISIBLE => get_lang('SessionAccessible'),
    SESSION_INVISIBLE => api_ucfirst(get_lang('SessionNotAccessible'))
));

$form->addGroup($visibilityGroup, 'visibility_group', null, null, false);

$form->addElement('html','</div>');

$form->addElement(
    'text',
    'duration',
    array(
        get_lang('SessionDurationTitle'),
        get_lang('SessionDurationDescription')
    ),
    array(
        'class' => 'span1',
        'maxlength' => 50
    )
);

//Extra fields
$extra_field = new ExtraField('session');
$extra = $extra_field->addElements($form, null);

$htmlHeadXtra[] ='
<script>

$(function() {
    '.$extra['jquery_ready_content'].'
});
</script>';

$form->addElement('button', 'submit', get_lang('NextStep'), array(
    'class' => 'save'
));

$formDefaults = array(
    'nb_days_acess_before' => $nb_days_acess_before,
    'nb_days_acess_after' => $nb_days_acess_after
);

if (!$formSent) {
    $formDefaults['date_start'] = "$thisYear-$thisMonth-$thisDay";

    $formDefaults['date_end'] = date('Y-m-d', strtotime("$thisYear-$thisMonth-$thisDay +1 year"));
} else {
    $formDefaults['name'] = api_htmlentities($name,ENT_QUOTES,$charset);
}

$form->setDefaults($formDefaults);

if ($form->validate()) {
    $params = $form->getSubmitValues();

    $name = $params['name'];
    $startDate = $params['date_start'];
    $endDate = $params['date_end'];
    $nb_days_acess_before = $params['nb_days_acess_before'];
    $nb_days_acess_after = $params['nb_days_acess_after'];
    $coach_username = $params['coach_username'];
    $id_session_category = $params['session_category'];
    $id_visibility = $params['session_visibility'];
    $end_limit = isset($params['end_limit']) ? true : false;
    $start_limit = isset($params['start_limit']) ? true : false;
    $duration = isset($params['duration']) ? $params['duration'] : null;

    if (empty($end_limit) && empty($start_limit)) {
        $nolimit = 1;
    } else {
        $nolimit = null;
    }

    $extraFields = array();

    foreach ($params as $key => $value) {
        if (strpos($key, 'extra_') === 0) {
            $extraFields[$key] = $value;
        }
    }

    $return = SessionManager::create_session(
        $name, $startDate, $endDate, $nb_days_acess_before,
        $nb_days_acess_after, $nolimit, $coach_username, $id_session_category, $id_visibility, $start_limit,
        $end_limit, false, $duration, $extraFields
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
echo '<a href="../admin/index.php">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

$form->display();

Display::display_footer();
