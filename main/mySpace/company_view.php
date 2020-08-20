<?php

/* For licensing terms, see /license.txt */
/*
No es 'usersubscribe' pero 'LearnpathSubscription' y en la columna to_user_id tienes el user_id del usuario que ha sido
inscrito y en la columna ref tienes el lp_id al cual ha sido inscrito.

*/
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_TRACKING;

$csv_content = [];
$nameTools = get_lang('MySpace');

$allowToTrack = api_is_platform_admin(true, true);
if (!$allowToTrack) {
    api_not_allowed(true);
}

$userInfo = [];
$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;
$languageFilter = isset($_REQUEST['language']) ? $_REQUEST['language'] : '';
$content = '';




$tblItemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
$tblLp = Database::get_course_table(TABLE_LP_MAIN);

$whereCondition = '';

// Getting dates
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;
//Validating dates
if (!empty($startDate)) {
    $startDate = new DateTime($startDate);
}
if (!empty($endDate)) {
    $endDate = new DateTime($endDate);
}
if (!empty($startDate) and !empty($endDate)) {
    if ($startDate > $endDate) {
        $dateTemp = $endDate;
        $endDate = $startDate;
        $startDate = $dateTemp;
        unset($dateTemp);
    }
}
// Settings condition and parametter GET to right date
if (!empty($startDate)) {
    $startDate = $startDate->format('Y-m-d');
    $_GET['startDate'] = $startDate;
    $whereCondition .= " AND $tblItemProperty.lastedit_date >= '$startDate' ";
}
if (!empty($endDate)) {
    $endDate = $endDate->format('Y-m-d');
    $_GET['endDate'] = $endDate;
    $whereCondition .= " AND $tblItemProperty.lastedit_date <= '$endDate' ";

}

// Get lp name
$selectToNameLp = "(
SELECT
	name
FROM
	$tblLp
WHERE
	$tblLp.iid = c_item_property.ref
) as name_lp";
// get Compnay data
$selectToCompany = " (
SELECT
    value
FROM
	extra_field_values
WHERE
	field_id IN (
		SELECT
			id
		FROM
			extra_field
		WHERE
			variable = 'company'
	)
AND item_id = $tblItemProperty.to_user_id
) ";


$query = "
SELECT
    * ,
     $selectToCompany  as company,
    $selectToNameLp
FROM
	$tblItemProperty
WHERE
	c_id IN (
		SELECT
			c_id
		FROM
			".TABLE_MAIN_COURSE_USER."
		WHERE
		    STATUS = 5
	)
    AND
    lastedit_type = 'LearnpathSubscription'
    and $selectToCompany is not null ";

if (strlen($whereCondition) > 2) {
    $query .= $whereCondition;
}
$queryResult = Database::query($query);
$companys =[];
if (!empty($startDate) and !empty($endDate)) {
    while ($row = Database::fetch_array($queryResult, 'ASSOC')) {
        $courseId = (int)$row['c_id'];
        $studentId = (int)$row['to_user_id'];
        $company = isset($row['company']) ? $row['company'] : '';
        $lpId = $row['ref'];
        $companys[$company][] = $studentId;
        $companys[$company] = array_unique($companys[$company]);
    }
}


// Printing table
$total = 0;
$table = '<div class="table-responsive"><table class="table table-bordered">';
$query = "SELECT
			display_text
		FROM
			extra_field
		WHERE
			variable = 'company'";
$displayText = Database::fetch_assoc(Database::query($query));
$displayText = $displayText['display_text'];
$table.="<thead><tr><td>$displayText</td><td> numero de inscription en leccion </td></tr></thead><tbody>";

foreach($companys as $entity => $student) {
    $table.="<tr><td>$entity</td><td>".count($student)."</td></tr>";
    $total += count($student);
}
$table.="<tr><td>Total nscritos</td><td>$total</td></tr>";
$table .= '</tbody></table></div>';


$cursoText = $table;



//bloque exportar
$fileName = 'works_in_session_'.api_get_local_time();
$exportTo = strtolower(isset($_GET['export'])?$_GET['export']:null);
switch (!empty($exportTo)) {
    case 'csv':
        Export::arrayToCsv($companys, $fileName);
        break;
    case 'xls':
        Export::export_table_xls_html($companys, $fileName);
        break;
    default:
        //do nothing
        continue;
}
//bloque exportar


$htmlHeadXtra[] = '<script>
$(function() {

});

</script>';

Display::display_header($nameTools);
echo '<div class="actions">';
echo MySpace::getTopMenu();
echo '</div>';
echo MySpace::getAdminActions();
if (!empty($startDate)) {
    $form = new FormValidator('searchDate', 'get');
    $form->addHidden('a', 'searchDate');
    $form->addDatePicker(
        'startDate',
        'startDate',
        []);
    $form->addDatePicker(
        'endDate',
        'endDate',
        []);
    $form->addButtonSearch(get_lang('Search'));

    echo $form->returnForm();
    echo $cursoText;

}

echo $content;
$style = '<style>
    .boss_column {
        display: block;
    }
    .row .col-md-1 {
        display:flex;
        flex: 0 0 20%;
    }

    .flex-nowrap {
        -webkit-flex-wrap: nowrap!important;
        -ms-flex-wrap: nowrap!important;
        flex-wrap: nowrap!important;
    }
    .flex-row {
        display:flex;
        -webkit-box-orient: horizontal!important;
        -webkit-box-direction: normal!important;
        -webkit-flex-direction: row!important;
        -ms-flex-direction: row!important;
        flex-direction: row!important;
    }

    .add_user {
        //display:none;
    }
</style>';
echo $style;


Display::display_footer();
