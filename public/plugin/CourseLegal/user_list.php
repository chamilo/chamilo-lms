<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;

require_once __DIR__.'/config.php';

api_protect_course_script(true);
api_block_anonymous_users();

$legal = CourseLegalPlugin::create();

if (!$legal->isEnabled()) {
    api_not_allowed(true);
}

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();
$url = api_get_self().'?'.api_get_cidreq();

$action = $_GET['action'] ?? null;
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

$order = ' ORDER BY firstname, lastname';
$userList = $legal->getUserAgreementList($courseId, $sessionId, $order);
$table = new HTML_Table(['class' => 'data_table']);
$table->setHeaderContents(0, 0, get_lang('User'));
$table->setHeaderContents(0, 1, $legal->get_lang('WebAgreement'));
$table->setHeaderContents(0, 2, $legal->get_lang('MailAgreement'));
$table->setHeaderContents(0, 3, get_lang('Actions'));

$row = 1;
$pluginPath = api_get_path(WEB_PLUGIN_PATH).'CourseLegal/';

if (!empty($userList)) {
    foreach ($userList as $user) {
        $userName = api_get_person_name($user['firstname'], $user['lastname']);

        $webDate = !empty($user['web_agreement_date']) ? api_get_local_time($user['web_agreement_date']) : '-';
        $mailDate = !empty($user['mail_agreement_date']) ? api_get_local_time($user['mail_agreement_date']) : '-';
        $resendUrl = $pluginPath.'user_list.php?action=resend&user_id='.$user['user_id'].'&'.api_get_cidreq();
        $deleteUrl = $pluginPath.'user_list.php?action=delete&user_id='.$user['user_id'].'&'.api_get_cidreq();

        $resendLink = Display::url(
            Display::getMdiIcon(
                ToolIcon::MESSAGE,
                'ch-tool-icon',
                null,
                ICON_SIZE_SMALL,
                $legal->get_lang('ReSendMailAgreementLink')
            ),
            $resendUrl
        );

        $deleteLink = Display::url(
            Display::getMdiIcon(
                ActionIcon::DELETE,
                'ch-tool-icon text-danger',
                null,
                ICON_SIZE_SMALL,
                get_lang('Delete')
            ),
            $deleteUrl
        );

        $table->setCellContents($row, 0, $userName);
        $table->setCellContents($row, 1, $webDate);
        $table->setCellContents($row, 2, $mailDate);
        $table->setCellContents($row, 3, $resendLink.' '.$deleteLink);
        $row++;
    }
}

$interbreadcrumb[] = [
    'url' => $pluginPath.'start.php?'.api_get_cidreq(),
    'name' => $legal->get_lang('CourseLegal'),
];

Display::display_header(get_lang('User list'));

echo '
<div class="mb-4 rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2">
                <span class="mdi mdi-account-check-outline ch-tool-icon text-primary"></span>
                <h2 class="m-0 text-h3 font-semibold text-gray-90">'.get_lang('User list').'</h2>
            </div>
            <p class="m-0 text-body-2 text-gray-70">
                Review learners who accepted the course legal agreement.
            </p>
        </div>
        '.Display::toolbarButton(
            get_lang('Back'),
            $pluginPath.'start.php?'.api_get_cidreq(),
            'arrow-left',
            'plain'
        ).'
    </div>
</div>';

if (!empty($userList)) {
    $table->display();
} else {
    echo Display::return_message(
        get_lang('There are no results to display.'),
        'info',
        false
    );
}

Display::display_footer();
