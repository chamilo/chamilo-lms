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

$whereCondition = '';

if (!empty($startDate)) {
    $whereCondition .= " AND $tblItemProperty.lastedit_date >= '$startDate' ";
}
if (!empty($endDate)) {
    $whereCondition .= " AND $tblItemProperty.lastedit_date <= '$endDate' ";

}

// get id of company


$query = "
SELECT
	*,
       (
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
	) AS company
FROM
	$tblItemProperty
WHERE
	c_id IN (
		SELECT
			c_id
		FROM
			".TABLE_MAIN_COURSE_USER."
		WHERE
		/*
			user_id IN (
				SELECT
					item_id
				FROM
					".TABLE_EXTRA_FIELD_VALUES."
				WHERE
					field_id IN (
						SELECT
							id
						FROM
							".TABLE_EXTRA_FIELD."
						WHERE
							variable = 'company'
					)
			)
		AND
		*/
		STATUS = 5
	)
AND lastedit_type = 'LearnpathSubscription'";

if(strlen($whereCondition) > 2){
    $query .= $whereCondition;
}
$queryResult =  Database::query($query);


$cursos = [];
$estudiantes = [];
$estudiantesCompany = [];
$estudiantesPorCurso = [];
$estudiantesCompanyPorCurso = [];
$NumeroEstudiantesCompany = 0;
$detalleCurso = [];
$studentInfo = [];

$elementos = [];
if(!empty($startDate) and !empty($endDate)){
    while ($row = Database::fetch_array($queryResult,'ASSOC')) {
        $courseId = (int)$row['c_id'];
        $studentId = (int)$row['to_user_id'];
        $studentCompanyId = (int)$row['company'];
        $cursos[] = $courseId;
        $estudiantes[]= $studentId;
        if($studentCompanyId != 0){
            $estudiantesCompany[] = $studentCompanyId;
            $estudiantesCompanyPorCurso[$courseId][] = $studentCompanyId;
            $estudiantesCompanyPorCurso[$courseId] =  array_unique($estudiantesCompanyPorCurso[$courseId]);
        }else{
            $estudiantesPorCurso[$courseId][] = $studentId;
            $estudiantesPorCurso[$courseId] =  array_unique($estudiantesPorCurso[$courseId]);
        }
        $row = array_merge($row,api_get_course_info_by_id($courseId));
        $elementos[] = $row;
        $detalleCurso[$courseId] = api_get_course_info_by_id($courseId);
    }
}
$cursos=  array_unique($cursos);
$estudiantes=  array_unique($estudiantes);
$estudiantesCompany=  array_unique($estudiantesCompany);
$NumeroEstudiantes  = count($estudiantes);
$NumeroEstudiantesCompany = count($estudiantesCompany);
for($i = 0;$i<count($estudiantes);$i++){
    $studentInfo[$estudiantes[$i]] = api_get_user_info($estudiantes[$i]);
}
$cursoText = "Cantidad de alumnos inscritos $NumeroEstudiantes <br> <br> Cantidad de estudiantes con company $NumeroEstudiantesCompany";
$cursoText .= 'Listado de cursos<br><br>';
for($i = 0;$i<count($cursos);$i++){
    $studentsInCourse=$estudiantesCompanyPorCurso[$cursos[$i]];
    $courseDetailled = $detalleCurso[$cursos[$i]];
    $cursoText.= "<br>".$cursos[$i]." - ".var_export($courseDetailled,true)."<strong>".var_export($studentsInCourse,true)."</strong><br>";
    foreach($studentInfo as $k=>$v){
        //$nombreCompleto = $v['dependietne rh, dpr'];
        $cursoText.=" <br><strong>".var_export($v,true)."</strong>";

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
    $form = new FormValidator('searchExtra','get');
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
