<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_TRACKING;

$csv_content = [];
$nameTools = get_lang('Reporting');

$allowToTrack = api_is_platform_admin(true, true);
if (!$allowToTrack) {
    api_not_allowed(true);
}

// -----------------------------------------------------------------------------
// Form: user selector (Ajax)
// -----------------------------------------------------------------------------
$form = new FormValidator(
    'survey',
    'get', // use GET so the selected user stays in the URL on refresh
    api_get_self(),
    '',
    null,
    false
);

$form->addSelectAjax(
    'user_id',
    get_lang('User'),
    [],
    [
        'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_like',
    ]
);
$form->addButtonSearch(get_lang('Search'));

$userInfo = [];
if ($form->validate()) {
    $userId = (int) $form->exportValue('user_id');
    if (!empty($userId)) {
        $userInfo = api_get_user_info($userId);
    }
}

// -----------------------------------------------------------------------------
// Header + toolbar (MySpace admin style)
// -----------------------------------------------------------------------------
Display::display_header($nameTools);

echo '<style>
    .reporting-admin-card {
        border-color: #e5e7eb !important;
        border-width: 1px !important;
    }
    .reporting-admin-card .panel,
    .reporting-admin-card fieldset {
        border-color: #e5e7eb !important;
    }
</style>';

// Left side: MySpace admin menu icons
$actionsLeft = Display::mySpaceMenu('admin_view');

// Right side: print icon
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

$toolbar = Display::toolbarAction('toolbar-survey-user-report', [$actionsLeft, $actionsRight]);

// -----------------------------------------------------------------------------
// Layout wrapper
// -----------------------------------------------------------------------------
echo '<div class="w-full px-4 md:px-8 pb-8 space-y-6">';

// Toolbar row
echo '  <div class="flex flex-wrap gap-2">';
echo        $toolbar;
echo '  </div>';

// Page header
echo '  <div class="space-y-1">';
echo        Display::page_subheader($nameTools);
echo '  </div>';

// MySpace admin cards (highlight current report)
$currentScriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
echo MySpace::renderAdminReportCardsSection(null, $currentScriptName, true);

// -----------------------------------------------------------------------------
// Filter form card
// -----------------------------------------------------------------------------
echo '  <section class="reporting-admin-card bg-white rounded-xl shadow-sm border border-gray-200 w-full">';
echo '      <div class="p-4 md:p-5">';
$form->display();
echo '      </div>';
echo '  </section>';

// -----------------------------------------------------------------------------
// Results: user survey reports
// -----------------------------------------------------------------------------
if (!empty($userInfo)) {
    echo '  <section class="reporting-admin-card bg-white rounded-xl shadow-sm border border-gray-200 w-full">';
    echo '      <div class="p-4 md:p-5 space-y-4">';

    echo            Display::page_subheader($userInfo['complete_name']);

    // Global survey report for the user
    echo '          <div class="overflow-x-auto">';
    echo                SurveyManager::surveyReport($userInfo);
    echo '          </div>';

    // Detailed survey report for the user (per question)
    echo '          <div class="overflow-x-auto">';
    echo                SurveyManager::surveyReport($userInfo, 1);
    echo '          </div>';

    echo '      </div>';
    echo '  </section>';
}

echo '</div>';

Display::display_footer();
