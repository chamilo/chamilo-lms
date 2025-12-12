<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\Node\UserRepository;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_TRACKING;

$csv_content = [];
$nameTools = get_lang('Reporting');

$allowToTrack = api_is_platform_admin(true, true);
if (!$allowToTrack) {
    api_not_allowed(true);
}

$userInfo       = [];
$action         = $_REQUEST['a'] ?? null;
$languageFilter = $_REQUEST['language'] ?? '';
$content        = '';

switch ($action) {
    case 'add_user':
        $bossId   = isset($_REQUEST['boss_id']) ? (int) $_REQUEST['boss_id'] : 0;
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
            $values      = $form->getSubmitValues();
            $studentInfo = api_get_user_info($values['user_id']);

            // Subscribe learner to boss list.
            UserManager::subscribeUserToBossList($values['user_id'], [$values['boss_id']], true);

            Display::addFlash(
                Display::return_message(get_lang('Saved').' '.$studentInfo['complete_name'])
            );

            header('Location: '.api_get_self());
            exit;
        }

        $content = $form->returnForm();

        break;
}

// Ajax URL used to dynamically add learners to a boss.
$url = api_get_path(WEB_AJAX_PATH).'statistics.ajax.php?a=add_student_to_boss';

$htmlHeadXtra[] = '<script>
$(function() {
    $(".add_user form").on("submit", function(e) {
        e.preventDefault();
        var id = $(this).attr("id");
        var data = $("#" + id).serializeArray();
        var bossId = id.replace("add_user_to_", "");

        for (var i = 0; i < data.length; i += 1) {
            if (data[i].name === "user_id") {
                var userId = data[i].value;
                var params = "boss_id=" + bossId + "&student_id=" + userId + "&";

                $.get(
                    "'.$url.'",
                    params,
                    function(response) {
                        $("#table_" + bossId).html(response);
                        $("#table_" + bossId).append("'.addslashes(
        Display::label(get_lang('Added'), 'success')
    ).'");
                        $("#add_user_to_" + bossId + "_user_id").val(null).trigger("change");
                    }
                );
            }
        }
    });
});
</script>';

Display::display_header($nameTools);

// -----------------------------------------------------------------------------
// Toolbar (MySpace menu + print button)
// -----------------------------------------------------------------------------
$actionsLeft = Display::mySpaceMenu('admin_view');

$actionsRight = Display::url(
    Display::getMdiIcon(
        ActionIcon::PRINT,
        'ch-tool-icon',
        null,
        ICON_SIZE_MEDIUM,
        get_lang('Print')
    ),
    'javascript: void(0);',
    ['onclick' => 'javascript: window.print();']
);

$toolbar = Display::toolbarAction('toolbar-admin', [$actionsLeft, $actionsRight]);

// Wrapper for the whole page content.
echo '<div class="w-full px-4 md:px-8 pb-8 space-y-6">';

// Toolbar row.
echo '  <div class="flex flex-wrap gap-2">';
echo        $toolbar;
echo '  </div>';

// Page header.
echo '  <div class="space-y-1">';
echo        Display::page_subheader($nameTools);
echo '  </div>';

// -----------------------------------------------------------------------------
// Navigation cards (shared helper used by admin_view, tc_report, etc.)
// -----------------------------------------------------------------------------
$currentScript = basename($_SERVER['SCRIPT_FILENAME'] ?? ($_SERVER['SCRIPT_NAME'] ?? ''));
echo MySpace::renderAdminReportCardsSection(null, $currentScript, true);

// -----------------------------------------------------------------------------
// Language filter form (when not in add_user mode)
// -----------------------------------------------------------------------------
$filterForm = null;

if ('add_user' !== $action) {
    $filterForm = new FormValidator('language_filter');
    $filterForm->addHidden('a', 'language_filter');
    $filterForm->addSelectLanguage(
        'language',
        get_lang('Language'),
        ['placeholder' => get_lang('Please select an option')]
    );
    $filterForm->addButtonSearch(get_lang('Search'));

    echo '  <section class="reporting-admin-card bg-white rounded-xl shadow-sm border border-gray-200 w-full">';
    echo '      <div class="p-4 md:p-5 space-y-4">';
    echo '          <h2 class="text-base md:text-lg font-semibold text-gray-800">'.get_lang('Filters').'</h2>';
    echo            $filterForm->returnForm();
    echo '      </div>';
    echo '  </section>';
} else {
    // Show the add-user form in a dedicated card.
    echo '  <section class="reporting-admin-card bg-white rounded-xl shadow-sm border border-gray-200 w-full">';
    echo '      <div class="p-4 md:p-5">';
    echo            $content;
    echo '      </div>';
    echo '  </section>';
}

// -----------------------------------------------------------------------------
// Boss list with learners (only when not in add_user mode)
// -----------------------------------------------------------------------------
$tableContent = '';

if ('add_user' !== $action) {
    $conditions = ['status' => STUDENT_BOSS, 'active' => 1];

    if (!empty($languageFilter) && 'placeholder' !== $languageFilter) {
        $conditions['language'] = $languageFilter;
    }

    /** @var UserRepository $userRepo */
    $userRepo = Container::getUserRepository();

    // Retrieve bosses for current URL.
    $bossList = $userRepo->findByRole('ROLE_STUDENT_BOSS', '', api_get_current_access_url_id());

    $tableContent .= '<div class="container-fluid">';
    $tableContent .= '  <div class="row flex-row flex-nowrap overflow-x-auto">';

    foreach ($bossList as $boss) {
        if (!empty($languageFilter) && $languageFilter !== $boss->getLocale()) {
            continue;
        }

        $bossId = $boss->getId();

        $tableContent .= '<div class="col-md-1">';
        $tableContent .= '  <div class="boss_column">';
        $tableContent .= '      <h5 class="mb-2"><strong>'.UserManager::formatUserFullName($boss).'</strong></h5>';
        $tableContent .=        Statistics::getBossTable($bossId);

        // Inline form for adding learners to this boss (handled via JS/Ajax).
        $tableContent .= '      <div class="add_user">';
        $tableContent .= '          <strong>'.get_lang('Add learner').'</strong>';

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
                'url'   => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=user_by_role&status='.STUDENT,
            ]
        );
        $addUserForm->addButtonSave(get_lang('Add'));

        $tableContent .= $addUserForm->returnForm();
        $tableContent .= '      </div>'; // .add_user

        $tableContent .= '  </div>'; // .boss_column
        $tableContent .= '</div>';    // .col-md-1
    }

    $tableContent .= '  </div>'; // .row
    $tableContent .= '</div>';   // .container-fluid

    echo '  <section class="bg-white rounded-xl shadow-sm border border-gray-200 w-full">';
    echo '      <div class="p-4 md:p-5 overflow-x-auto">';
    echo '          <h2 class="text-base md:text-lg font-semibold text-gray-800 mb-3">'.get_lang('Tracking for superior').'</h2>';
    echo            $tableContent;
    echo '      </div>';
    echo '  </section>';
}

echo '</div>'; // wrapper

Display::display_footer();
