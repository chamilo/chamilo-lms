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


// fechas
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;

$tblItemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
$tblLp = Database::get_course_table(TABLE_LP_MAIN);

$whereCondition = '';

if (!empty($startDate)) {
    $whereCondition .= " AND $tblItemProperty.lastedit_date >= '$startDate' ";
}
if (!empty($endDate)) {
    $whereCondition .= " AND $tblItemProperty.lastedit_date <= '$endDate' ";

}

// get id of company

$selectToNameLp = "(select name from $tblLp where $tblLp.iid =c_item_property.ref) as name_lp";
$selectToCompany = "(
		SELECT
			item_id
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
		LIMIT 1
	) AS company";
$query = "
SELECT
    * ,
    $selectToCompany ,
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
    lastedit_type = 'LearnpathSubscription'";

if (strlen($whereCondition) > 2) {
    $query .= $whereCondition;
}
$queryResult = Database::query($query);


$cursos = [];
$estudiantes = [];
$estudiantesCompany = [];
$estudiantesPorCurso = [];
// $estudiantesCompanyPorCurso = [];
$NumeroEstudiantesCompany = 0;
// $detalleCurso = [];
//$studentInfo = [];
//$print = [];

$elementos = [];
if (!empty($startDate) and !empty($endDate)) {
    while ($row = Database::fetch_array($queryResult, 'ASSOC')) {
        $courseId = (int)$row['c_id'];
        $studentId = (int)$row['to_user_id'];
        $studentCompanyId = (int)$row['company'];
        $lpId = $row['ref'];

        $studiantein = api_get_user_info($studentId);
        $courseInfo = api_get_course_info_by_id($courseId);

        $lpName = $row['name_lp'];

        $tempPrint['courseName'] = $courseInfo['name'];
        $tempPrint['insert_date'] = $row['insert_date'];
        $tempPrint['lastedit_date'] = $row['lastedit_date'];
        $tempPrint['lpId'] = $lpId;
        $tempPrint['lpName'] = $lpName;
        $tempPrint['studentName'] = $studiantein['complete_name'];
        $tempPrint['studentCompany'] = ($studentCompanyId != 0) ? true : false;

        // $studentInfo[$studentId] = $studiantein;


        //$cursos[] = $courseId;
        $cursos[$courseId][$lpId][] = $tempPrint;
        $estudiantes[] = $studentId;
        if ($studentCompanyId != 0) {
            $estudiantesCompany[] = $studentCompanyId;
            //$estudiantesCompanyPorCurso[$courseId][] = $studentCompanyId;
            //$estudiantesCompanyPorCurso[$courseId] = array_unique($estudiantesCompanyPorCurso[$courseId]);
        }
        /* else {
            $estudiantesPorCurso[$courseId][] = $studentId;
            $estudiantesPorCurso[$courseId] = array_unique($estudiantesPorCurso[$courseId]);
        }

        $print[] = $tempPrint;
        */
    }
}
$estudiantes = array_unique($estudiantes);
$estudiantesCompany = array_unique($estudiantesCompany);
$NumeroEstudiantes = count($estudiantes);
$NumeroEstudiantesCompany = count($estudiantesCompany);
$cursoText = "Cantidad de alumnos inscritos $NumeroEstudiantes <br> <br> Cantidad de estudiantes con company $NumeroEstudiantesCompany";
$cursoText .= '<br>Listado de cursos<br><br>';


$fileName = 'works_in_session_'.api_get_local_time();
switch ($_GET['export']) {
    case 'xls':
        Export::export_table_xls_html($cursos, $fileName);
        break;
    case 'csv':
        Export::arrayToCsv($cursos, $fileName);
        break;
}


foreach ($cursos as $courseId => $Course) {
    $cursoText .= "Curso id = $courseId<br>";
    foreach ($Course as $lpIndex => $lpData) {
        $cursoText .= "\t Lp id = $lpIndex<br>";
        foreach($lpData as $row) {
            $cursoText .= "<br> <strong>".var_export($row, true)."</strong><br>";
        }
    }
}
$htmlHeadXtra[] = '<script>
$(function() {

});

</script>';

Display::display_header($nameTools);
echo '<div class="actions">';
echo MySpace::getTopMenu();
echo '</div>';
echo MySpace::getAdminActions();
echo $cursoText;
// if (!empty($startDate)) {
$form = new FormValidator('searchExtra', 'get');
$form->addHidden('a', 'searchExtra');
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
// }

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

$tableContent = '';
if (!empty($startDate)) {
    $tableContent .= "<br><pre>".var_export($startDate, true)."</pre>";

}
if (!empty($endDate)) {
    $tableContent .= "<br><pre>".var_export($endDate, true)."</pre>";

}
if ($action !== 'add_user') {
    $conditions = ['status' => STUDENT_BOSS, 'active' => 1];
    if (!empty($languageFilter) && $languageFilter !== 'placeholder') {
        $conditions['language'] = $languageFilter;
    }
    $bossList = UserManager::get_user_list($conditions, ['firstname']);
    $tableContent .= '<div class="container-fluid"><div class="row flex-row flex-nowrap">';
    foreach ($bossList as $boss) {
        $bossId = $boss['id'];
        $tableContent .= '<div class="col-md-1">';
        $tableContent .= '<div class="boss_column">';
        $tableContent .= '<h5><strong>'.api_get_person_name($boss['firstname'], $boss['lastname']).'</strong></h5>';
        $tableContent .= Statistics::getBossTable($bossId);

        $url = api_get_self().'?a=add_user&boss_id='.$bossId;

        $tableContent .= '<div class="add_user">';
        $tableContent .= '<strong>'.get_lang('AddStudent').'</strong>';
        $addUserForm = new FormValidator(
            'add_user_to_'.$bossId,
            'post',
            '',
            '',
            [],
            FormValidator::LAYOUT_BOX_NO_LABEL
        );
        $addUserForm->addSelectAjax(
            'user_id',
            '',
            [],
            [
                'width' => '200px',
                'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=user_by_role&active=1&status='.STUDENT,
            ]
        );
        $addUserForm->addButtonSave(get_lang('Add'));
        $tableContent .= $addUserForm->returnForm();
        $tableContent .= '</div>';

        $tableContent .= '</div>';
        $tableContent .= '</div>';
    }
    $tableContent .= '</div></div>';
}

echo $tableContent;

Display::display_footer();
