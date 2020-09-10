<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/config.php';

// Course legal
$enabled = api_get_plugin_setting('courselegal', 'tool_enable');

if ($enabled != 'true') {
    api_not_allowed(true);
}

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$legal = CourseLegalPlugin::create();
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();
$url = api_get_self().'?'.api_get_cidreq();

$action = isset($_GET['action']) ? $_GET['action'] : null;
switch ($action) {
    case 'resend':
        if (isset($_GET['user_id'])) {
            $legal->updateMailAgreementLink($_GET['user_id'], $courseId, $sessionId);
            header('Location: '.$url);
            exit;
        }
        break;
    case 'delete':
        if (isset($_GET['user_id'])) {
            $legal->deleteUserAgreement($_GET['user_id'], $courseId, $sessionId);
            header('Location: '.$url);
            exit;
        }
        break;
}

$order = " ORDER BY firstname, lastname";
$userList = $legal->getUserAgreementList($courseId, $sessionId, $order);
$table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
$table->setHeaderContents(0, 0, get_lang('User'));
$table->setHeaderContents(0, 1, $legal->get_lang('WebAgreement'));
$table->setHeaderContents(0, 2, $legal->get_lang('MailAgreement'));
$table->setHeaderContents(0, 3, $legal->get_lang('Actions'));
$row = 1;

$pluginPath = api_get_path(WEB_PLUGIN_PATH).'courselegal/';
if (!empty($userList)) {
    foreach ($userList as $user) {
        $userName = api_get_person_name($user['firstname'], $user['lastname']);

        $webDate = !empty($user['web_agreement_date']) ? api_get_local_time($user['web_agreement_date']) : '-';
        $mailDate = !empty($user['mail_agreement_date']) ? api_get_local_time($user['mail_agreement_date']) : '-';
        $url = $pluginPath.'user_list.php?action=resend&user_id='.$user['user_id'].'&'.api_get_cidreq();
        $link = Display::url(
            Display::return_icon('inbox.png', $legal->get_lang('ReSendMailAgreementLink')),
            $url
        );

        $deleteLink = Display::url(
            Display::return_icon('delete.png', $legal->get_lang('Delete')),
            $pluginPath.'user_list.php?action=delete&user_id='.$user['user_id'].'&'.api_get_cidreq()
        );

        $table->setCellContents($row, 0, $userName);
        $table->setCellContents($row, 1, $webDate);
        $table->setCellContents($row, 2, $mailDate);
        $table->setCellContents($row, 3, $link.' '.$deleteLink);
        $row++;
    }
}
$url = $pluginPath.'start.php?'.api_get_cidreq();

$interbreadcrumb[] = ["url" => $url, "name" => $legal->get_lang('CourseLegal')];
Display::display_header($legal->get_lang('UserList'));

$table->display();

Display::display_footer();
