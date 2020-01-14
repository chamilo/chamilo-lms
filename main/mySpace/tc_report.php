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


/*$form = new FormValidator('survey');
$form->addSelectAjax(
    'user_id',
    get_lang('User'),
    [],
    [
        'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_like',
    ]
);
$form->addButtonSearch();*/

$userInfo = [];
/*if ($form->validate()) {
    $userId = $form->exportValue('user_id');
    $userInfo = api_get_user_info($userId);
}*/
//$form->display();

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;
$languageFilter = isset($_REQUEST['language']) ? $_REQUEST['language'] : '';

$content = '';

switch ($action) {
    case 'add_user':
        $bossId = isset($_REQUEST['boss_id']) ? (int) $_REQUEST['boss_id'] : 0;
        $bossInfo = api_get_user_info($bossId);

        $form = new FormValidator('add_user');
        $form->addHeader(get_lang('AddUser').' '.$bossInfo['complete_name']);
        $form->addHidden('a', 'add_user');
        $form->addHidden('boss_id', $bossId);
        $form->addSelectAjax(
            'user_id',
            get_lang('User'),
            [],
            [
                'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=user_by_role&status='.STUDENT,
            ]
        );
        $form->addButtonSave(get_lang('Add'));
        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $studentInfo = api_get_user_info($values['user_id']);
            UserManager::subscribeUserToBossList($values['user_id'], [$values['boss_id']]);
            Display::addFlash(Display::return_message(get_lang('Saved').' '.$studentInfo['complete_name']));
            header('Location: '.api_get_self());
            exit;
        }
        $content = $form->returnForm();

        break;
}

Display::display_header($nameTools);
echo '<div class="actions">';
echo MySpace::getTopMenu();
echo '</div>';
echo MySpace::getAdminActions();

if ($action !== 'add_user') {
    $form = new FormValidator('language_filter');
    $form->addHidden('a', 'language_filter');
    $form->addSelectLanguage(
        'language',
        get_lang('Language'),
        ['placeholder' => get_lang('SelectAnOption')]
    );
    $form->addButtonSearch(get_lang('Search'));

    echo $form->returnForm();
}

echo $content;

if ($action !== 'add_user') {
    $conditions = ['status' => STUDENT_BOSS];
    if (!empty($languageFilter) && $languageFilter !== 'placeholder') {
        $conditions['language'] = $languageFilter;
    }
    $bossList = UserManager::get_user_list($conditions);

    foreach ($bossList as $boss) {
        $bossId = $boss['id'];
        echo Display::page_subheader2(api_get_person_name($boss['firstname'], $boss['lastname']));
        $students = UserManager::getUsersFollowedByStudentBoss($bossId);

        if (!empty($students)) {
            $table = new HTML_Table(['class' => 'table table-responsive']);
            $headers = [
                get_lang('FirstName'),
                get_lang('LastName'),
            ];
            $row = 0;
            $column = 0;
            foreach ($headers as $header) {
                $table->setHeaderContents($row, $column, $header);
                $column++;
            }
            $row++;
            foreach ($students as $student) {
                $column = 0;
                $table->setCellContents($row, $column++, $student['firstname']);
                $table->setCellContents($row, $column++, $student['lastname']);
                $row++;
            }

            echo $table->toHtml();
        }

        $url = api_get_self().'?a=add_user&boss_id='.$bossId;

        echo Display::url(get_lang('AddUser'), $url, ['class' => 'btn btn-primary']);
    }
}

Display::display_footer();
