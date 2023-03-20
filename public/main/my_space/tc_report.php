<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

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
$action = $_REQUEST['a'] ?? null;
$languageFilter = $_REQUEST['language'] ?? '';
$content = '';

switch ($action) {
    case 'add_user':
        $bossId = isset($_REQUEST['boss_id']) ? (int) $_REQUEST['boss_id'] : 0;
        $bossInfo = api_get_user_info($bossId);

        $form = new FormValidator('add_user');
        $form->addHeader(get_lang('Add user').' '.$bossInfo['complete_name']);
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
            UserManager::subscribeUserToBossList($values['user_id'], [$values['boss_id']], true);
            Display::addFlash(Display::return_message(get_lang('Saved').' '.$studentInfo['complete_name']));
            header('Location: '.api_get_self());
            exit;
        }
        $content = $form->returnForm();

        break;
}

$url = api_get_path(WEB_AJAX_PATH).'statistics.ajax.php?a=add_student_to_boss';

$htmlHeadXtra[] = '<script>
$(function() {
    $(".add_user form").on("submit", function(e) {
        e.preventDefault();
        var id = $(this).attr("id");
        var data = $("#" + id ).serializeArray();
        var bossId = id.replace("add_user_to_", "") ;

        for (i=0; i<data.length; i += 1) {
            if (data[i].name === "user_id") {
                var userId = data[i].value;
                var params = "boss_id="+ bossId + "&student_id="+ userId + "&";
                $.get(
                    "'.$url.'",
                    params,
                    function(response) {
                        $("#table_" + bossId ).html(response);
                        $("#table_" + bossId ).append("'.addslashes(Display::label(get_lang('Added'), 'success')).'");
                        $("#add_user_to_" + bossId + "_user_id").val(null).trigger("change");
                    }
                );
            }
        }
    });
});

</script>';

Display::display_header($nameTools);
echo '<div class="actions">';
echo MySpace::getTopMenu();
echo '</div>';
echo MySpace::getAdminActions();

if ('add_user' !== $action) {
    $form = new FormValidator('language_filter');
    $form->addHidden('a', 'language_filter');
    $form->addSelectLanguage(
        'language',
        get_lang('Language'),
        ['placeholder' => get_lang('Select an option')]
    );
    $form->addButtonSearch(get_lang('Search'));

    echo $form->returnForm();
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

$tableContent = '';

if ('add_user' !== $action) {
    $conditions = ['status' => STUDENT_BOSS, 'active' => 1];
    if (!empty($languageFilter) && 'placeholder' !== $languageFilter) {
        $conditions['language'] = $languageFilter;
    }
    $userRepo = Container::getUserRepository();
    $bossList = $userRepo->findByRole('ROLE_STUDENT_BOSS', '', api_get_current_access_url_id());
    //$bossList = UserManager::get_user_list($conditions, ['firstname']);
    $tableContent .= '<div class="container-fluid"><div class="row flex-row flex-nowrap">';
    foreach ($bossList as $boss) {
        if (!empty($languageFilter) && $languageFilter !== $boss->getLocale()) {
            continue;
        }
        $bossId = $boss->getId();
        $tableContent .= '<div class="col-md-1">';
        $tableContent .= '<div class="boss_column">';
        $tableContent .= '<h5><strong>'.UserManager::formatUserFullName($boss).'</strong></h5>';
        $tableContent .= Statistics::getBossTable($bossId);

        $url = api_get_self().'?a=add_user&boss_id='.$bossId;

        $tableContent .= '<div class="add_user">';
        $tableContent .= '<strong>'.get_lang('Add student').'</strong>';
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
                'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=user_by_role&status='.STUDENT,
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
