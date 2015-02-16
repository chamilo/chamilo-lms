<?php
/* For licensing terms, see /license.txt */
/**
 * Sessions edition script
 * @package chamilo.admin
 */
/**
 * Code
 */

// name of the language file that needs to be included
$language_file ='admin';
$cidReset = true;
require_once '../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

$formSent = 0;

// Database Table Definitions
$tbl_user		= Database::get_main_table(TABLE_MAIN_USER);
$tbl_session	= Database::get_main_table(TABLE_MAIN_SESSION);

$id = intval($_GET['id']);

SessionManager::protect_session_edit($id);
$infos = SessionManager::fetch($id);

$id_coach = $infos['id_coach'];
$tool_name = get_lang('EditSession');

$interbreadcrumb[] = array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => "session_list.php","name" => get_lang('SessionList'));
$interbreadcrumb[] = array('url' => "resume_session.php?id_session=".$id,"name" => get_lang('SessionOverview'));

list($year_start, $month_start, $day_start) = explode('-', $infos['date_start']);
list($year_end, $month_end, $day_end) = explode('-', $infos['date_end']);

// Default value
$showDescriptionChecked = 'checked';

if (isset($infos['show_description'])) {
    if (!empty($infos['show_description'])) {
        $showDescriptionChecked = 'checked';
    } else {
        $showDescriptionChecked = null;
    }
}

$end_year_disabled = $end_month_disabled = $end_day_disabled = '';

if (isset($_POST['formSent']) && $_POST['formSent']) {
	$formSent = 1;
}

$order_clause = 'ORDER BY ';
$order_clause .= api_sort_by_first_name() ? 'firstname, lastname, username' : 'lastname, firstname, username';

$sql="SELECT user_id,lastname,firstname,username FROM $tbl_user WHERE status='1'".$order_clause;

if (api_is_multiple_url_enabled()) {
	$table_access_url_rel_user= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
	$access_url_id = api_get_current_access_url_id();
	if ($access_url_id != -1) {
		$sql="SELECT DISTINCT u.user_id,lastname,firstname,username FROM $tbl_user u INNER JOIN $table_access_url_rel_user url_rel_user ON (url_rel_user.user_id = u.user_id)
			  WHERE status='1' AND access_url_id = '$access_url_id' $order_clause";
	}
}

$result     = Database::query($sql);
$coaches    = Database::store_result($result);
$thisYear   = date('Y');

$daysOption = array();

for ($i = 1; $i <= 31; $i++) {
    $day = sprintf("%02d", $i);
    $daysOption[$day] = $day;
}

$monthsOption = array();

for ($i = 1; $i <= 12; $i++) {
    $month = sprintf("%02d", $i);
    
    $monthsOption[$month] = $month;
}

$yearsOption = array();

for ($i = $thisYear - 5; $i <= ($thisYear + 5); $i++) {
    $yearsOption[$i] = $i;
}

$coachesOption = array(
    '' => '----- ' . get_lang('None') . ' -----'
);

foreach ($coaches as $coach) {
    $personName = api_get_person_name($coach['firstname'], $coach['lastname']);

    $coachesOption[$coach['user_id']] = "$personName ({$coach['username']})";
}

$Categories = SessionManager::get_all_session_category();

$categoriesOption = array(
    '0' => get_lang('None')
);

if ($Categories != false) {
    foreach ($categoriesList as $categoryItem) {
        $categoriesOption[$categoryItem['id']] = $categoryItem['name'];
    }
}

$formAction = api_get_self() . '?';
$formAction .= http_build_query(array(
    'page' => Security::remove_XSS($_GET['page']),
    'id' => $id
));

$form = new FormValidator('edit_session', 'post', $formAction);

$form->addElement('header', $tool_name);

$form->addElement('text', 'name', get_lang('SessionName'), array(
    'class' => 'span4',
    'maxlength' => 50,
    'value' => $formSent ? api_htmlentities($name,ENT_QUOTES,$charset) : ''
));
$form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('name', get_lang('SessionNameAlreadyExists'), 'callback', 'check_session_name');

$form->addElement('select', 'id_coach', get_lang('CoachName'), $coachesOption, array(
    'id' => 'coach_username',
    'class' => 'chzn-select',
    'style' => 'width:370px;',
    'title' => get_lang('Choose')
));
$form->addRule('id_coach', get_lang('ThisFieldIsRequired'), 'required');

$form->add_select('session_category', get_lang('SessionCategory'), $categoriesOption, array(
    'id' => 'session_category',
    'class' => 'chzn-select',
    'style' => 'width:370px;'
));

$form->addElement('advanced_settings','<a class="btn-show" id="show-options" href="#">'.get_lang('DefineSessionOptions').'</a>');

if ($infos['nb_days_access_before_beginning'] != 0 || $infos['nb_days_access_after_end'] != 0) {
    $form->addElement('html','<div id="options" style="display:block;">');
} else {
    $form->addElement('html','<div id="options" style="display:none;">');
}

$form->addElement('text', 'nb_days_access_before', array('', '', get_lang('DaysBefore')), array(
    'style' => 'width: 30px;'
));

$form->addElement('text', 'nb_days_access_after', array('', '', get_lang('DaysAfter')), array(
    'style' => 'width: 30px;'
));

$form->addElement('html','</div>');

if ($year_start!="0000") {
    $form->addElement('checkbox', 'start_limit', '', get_lang('DateStartSession'), array(
        'onchange' => 'disable_starttime(this)',
        'id' => 'start_limit',
        'checked' => ''
    ));

    $form->addElement('html','<div id="start_date" style="display:block">');
} else {
    $form->addElement('checkbox', 'start_limit', '', get_lang('DateStartSession'), array(
        'onchange' => 'disable_starttime(this)',
        'id' => 'start_limit'
    ));

    $form->addElement('html','<div id="start_date" style="display:none">');
}

$form->addElement('date_picker', 'date_start');

$form->addElement('html','</div>');

if ($year_end != "0000") {
    $form->addElement('checkbox', 'end_limit', '', get_lang('DateEndSession'), array(
        'onchange' => 'disable_endtime(this)',
        'id' => 'end_limit',
        'checked' => ''
    ));

    $form->addElement('html','<div id="end_date" style="display:block">');
} else {
    $form->addElement('checkbox', 'end_limit', '', get_lang('DateEndSession'), array(
        'onchange' => 'disable_endtime(this)',
        'id' => 'end_limit'
    ));

    $form->addElement('html','<div id="end_date" style="display:none">');
}

$form->addElement('date_picker', 'date_end');

$visibilityGroup = array();
$visibilityGroup[] = $form->createElement('advanced_settings', get_lang('SessionVisibility'));
$visibilityGroup[] = $form->createElement('select', 'session_visibility', null, array(
    SESSION_VISIBLE_READ_ONLY => get_lang('SessionReadOnly'),
    SESSION_VISIBLE => get_lang('SessionAccessible'),
    SESSION_INVISIBLE => api_ucfirst(get_lang('SessionNotAccessible'))
), array(
    'style' => 'width:250px;'
));

$form->addGroup($visibilityGroup, 'visibility_group', null, null, false);

$form->addElement('html','</div>');

if (array_key_exists('show_description', $infos)) {
    $form->addElement('textarea', 'description', get_lang('Description'));

    $chkDescriptionAttributes = array();

    if (!empty($showDescriptionChecked)) {
        $chkDescriptionAttributes['checked'] = '';
    }

    $form->addElement('checkbox', 'show_description', null, get_lang('ShowDescription'), $chkDescriptionAttributes);
}

$duration = empty($infos['duration']) ? null : $infos['duration'];

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
$extra = $extra_field->addElements($form, $id);

$htmlHeadXtra[] ='
<script>

$(function() {
    '.$extra['jquery_ready_content'].'
});
</script>';

$form->addElement('button', 'submit', get_lang('ModifyThisSession'), array(
    'class' => 'save'
));

$formDefaults = array(
    'id_coach' => $infos['id_coach'],
    'session_category' => $infos['session_category_id'],
    'date_start' => $infos['date_start'],
    'date_end' => $infos['date_end'],
    'session_visibility' => $infos['visibility'],
    'description' => array_key_exists('show_description', $infos) ? $infos['description'] : ''
);

if ($formSent) {
    $formDefaults['name'] = api_htmlentities($name,ENT_QUOTES,$charset);
    $formDefaults['nb_days_access_before'] = api_htmlentities($nb_days_access_before,ENT_QUOTES,$charset);
    $formDefaults['nb_days_access_after'] = api_htmlentities($nb_days_access_after,ENT_QUOTES,$charset);
    $formDefaults['duration'] = Security::remove_XSS($duration);
} else {
    $formDefaults['name'] = api_htmlentities($infos['name'],ENT_QUOTES,$charset);
    $formDefaults['nb_days_access_before'] = api_htmlentities($infos['nb_days_access_before_beginning'],ENT_QUOTES,$charset);
    $formDefaults['nb_days_access_after'] = api_htmlentities($infos['nb_days_access_after_end'],ENT_QUOTES,$charset);
    $formDefaults['duration'] = $duration;
}

$form->setDefaults($formDefaults);

if ($form->validate()) {
    $params = $form->getSubmitValues();

    $name = $params['name'];
    $startDate = $params['date_start'];
    $endDate = $params['date_end'];
    $nb_days_acess_before = $params['nb_days_access_before'];
    $nb_days_acess_after = $params['nb_days_access_after'];
    $id_coach = $params['id_coach'];
    $id_session_category = $params['session_category'];
    $id_visibility = $params['session_visibility'];
    $duration = isset($params['duration']) ? $params['duration'] : null;
    $description = isset($params['description']) ? $params['description'] : null;
    $showDescription = isset($params['show_description']) ? 1: 0;

    $end_limit = $params['end_limit'];
    $start_limit = $params['start_limit'];

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

    $return = SessionManager::edit_session(
        $id,
        $name,
        $startDate,
        $endDate,
        $nb_days_acess_before,
        $nb_days_acess_after,
        $nolimit,
        $id_coach,
        $id_session_category,
        $id_visibility,
        $start_limit,
        $end_limit,
        $description,
        $showDescription,
        $duration,
        $extraFields
    );

    if ($return == strval(intval($return))) {
		header('Location: resume_session.php?id_session=' . $return);
		exit();
	}
}

// display the header
Display::display_header($tool_name);

if (!empty($return)) {
    Display::display_error_message($return,false);
}

$form->display();
?>

<script type="text/javascript">

<?php
//if($year_start=="0000") echo "setDisable(document.form.nolimit);\r\n";
?>

function setDisable(select) {
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

$(document).on('ready', function (){
    $('#show-options').on('click', function (e) {
        e.preventDefault();

        var display = $('#options').css('display');

        display === 'block' ? $('#options').slideUp() : $('#options').slideDown() ;
    });
});

</script>
<?php
Display::display_footer();
