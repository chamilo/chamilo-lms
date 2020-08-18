<?php

/* For licensing terms, see /license.txt */

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
$startDate = isset($_POST['startDate']) ? $_POST['startDate'] : null;
$endDate = isset($_POST['endDate']) ? $_POST['endDate'] : null;

// get id of company
$query = "select id, variable from ".TABLE_EXTRA_FIELD." where variable = 'company'";
$resultCompany = Database::fetch_array(Database::query($query));



$htmlHeadXtra[] = '<script>
$(function() {

});

</script>';

Display::display_header($nameTools);
echo '<div class="actions">';
echo MySpace::getTopMenu();
echo '</div>';
echo MySpace::getAdminActions();

// if (!empty($startDate)) {
    $form = new FormValidator('searchExtra');
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
