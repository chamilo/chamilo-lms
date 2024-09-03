<?php
/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users(true);

if (api_is_student()) {
    api_not_allowed(true);
}

$formValidator = new FormValidator('time_report_form', 'post', api_get_self());

// Get the list of users based on the role
$userId = api_get_user_id();
$userOptions = [];

if (api_is_platform_admin() || (api_is_session_admin() && api_get_setting('prevent_session_admins_to_manage_all_users') !== 'true')) {
    $userList = UserManager::get_user_list();
} else {
    $userList = $studentList = UserManager::getUsersFollowedByUser(
        $userId,
        STUDENT,
        false,
        false,
        false,
        null,
        null,
        null,
        null,
        null,
        null,
        COURSEMANAGER
    );
}

$formValidator->addElement('checkbox', 'select_all_users', get_lang('SelectAllUsers'), null, ['id' => 'select_all_users']);
$userOptions = [];
foreach ($userList as $user) {
    $userOptions[$user['user_id']] = $user['lastname'].' '.$user['firstname'];
}
$formValidator->addElement('select', 'users', get_lang('SelectUsers'), $userOptions, [
    'multiple' => 'multiple',
    'id' => 'user_selector',
]);

$htmlHeadXtra[] = '
<script>
    $(function() {
        var selectAllCheckbox = $("#select_all_users");
        var userSelector = $("#user_selector");

        userSelector.select2({
            placeholder: "'.get_lang('SelectAnOption').'",
            allowClear: true,
            width: "100%"
        });

        selectAllCheckbox.on("change", function() {
            if (this.checked) {
                var allOptions = userSelector.find("option");
                var allValues = [];
                allOptions.each(function() {
                    allValues.push($(this).val());
                });
                userSelector.val(allValues).trigger("change");
            } else {
                userSelector.val(null).trigger("change");
            }
        });
    });
</script>';

// Date selectors
$formValidator->addDatePicker('start_date', get_lang('StartDate'));
$formValidator->addDatePicker('end_date', get_lang('EndDate'));

// Report type selector
$reportTypeValues = [
    'time_report' => get_lang('TimeReport'),
    'billing_report' => get_lang('BillingReport'),
];
$formValidator->addElement('select', 'report_type', get_lang('ReportType'), $reportTypeValues);

// Button to generate the report
$formValidator->addButtonSend(get_lang('GenerateReport'));

// Form validation rules
$formValidator->addRule('start_date', get_lang('ThisFieldIsRequired'), 'required');
$formValidator->addRule('end_date', get_lang('ThisFieldIsRequired'), 'required');
$formValidator->addRule('users', get_lang('ThisFieldIsRequired'), 'required');
$formValidator->addRule('report_type', get_lang('ThisFieldIsRequired'), 'required');

if ($formValidator->validate()) {
    $values = $formValidator->exportValues();
    $users = $values['users'];
    $startDate = $values['start_date'];
    $endDate = $values['end_date'];
    $reportType = $values['report_type'];
    $exportXls = isset($_POST['export']);

    if (empty($users)) {
        Display::addFlash(Display::return_message(get_lang('NoUsersSelected'), 'warning'));
    } else {
        $data = Tracking::generateReport($reportType, $users, $startDate, $endDate);
        if (empty($data)) {
            Display::addFlash(Display::return_message(get_lang('NoDataToExport'), 'warning'));
        } else {
            $headers = $data['headers'];
            $rows = $data['rows'];
            array_unshift($rows, $headers);
            $fileName = get_lang('Export').'-'.$reportTypeValues[$reportType].'_'.api_get_local_time();
            Export::arrayToCsv($rows, $fileName);
        }
    }
}

$nameTools = get_lang('TimeReport');
Display::display_header($nameTools);

$formValidator->display();

Display::display_footer();
