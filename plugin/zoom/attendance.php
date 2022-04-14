<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\Meeting;
use Chamilo\PluginBundle\Zoom\Registrant;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

require_once __DIR__.'/config.php';

api_block_anonymous_users();

$httpRequest = HttpRequest::createFromGlobals();

$meetingId = $httpRequest->get('meetingId', 0);

if (empty($meetingId)) {
    api_not_allowed(true);
}

$plugin = ZoomPlugin::create();
$em = Database::getManager();
/** @var Meeting $meeting */
$meeting = $plugin->getMeetingRepository()->findOneBy(['meetingId' => $meetingId]);
$registrantsRepo = $em->getRepository(Registrant::class);

if (null === $meeting) {
    api_not_allowed(
        true,
        Display::return_message($plugin->get_lang('MeetingNotFound'), 'error')
    );
}

if (!$plugin->userIsConferenceManager($meeting)
    || !$meeting->isSignAttendance()
) {
    api_not_allowed(
        true,
        Display::return_message(get_lang('NotAvailable'), 'warning')
    );
}

$getNumberOfSignatures = function () use ($meeting) {
    return $meeting->getRegistrants()->count();
};

$getSignaturesData = function (
    $from,
    $limit,
    $column,
    $direction
) use ($registrantsRepo, $meeting) {
    if (0 === $column) {
        $columnField = 'u.lastname';
    } elseif (1 === $column) {
        $columnField = 'u.firstname';
    } else {
        $columnField = 's.registeredAt';
    }

    $result = $registrantsRepo->findByMeetingPaginated($meeting, $from, $limit, $columnField, $direction);

    return array_map(
        function (Registrant $registrant) {
            $signature = $registrant->getSignature();

            return [
                $registrant->getUser()->getLastname(),
                $registrant->getUser()->getFirstname(),
                $signature ? $signature->getRegisteredAt() : null,
                $signature ? $signature->getFile() : null,
            ];
        },
        $result
    );
};

if ($httpRequest->query->has('export')) {
    $plugin->exportSignatures(
        $meeting,
        $httpRequest->query->getAlnum('export')
    );
}

$table = new SortableTable('zoom_signatures', $getNumberOfSignatures, $getSignaturesData, 2);
$table->set_header(0, get_lang('LastName'));
$table->set_header(1, get_lang('FirstName'));
$table->set_header(2, get_lang('DateTime'), true, ['class' => 'text-center'], ['class' => 'text-center']);
$table->set_header(3, $plugin->get_lang('Signature'), false, ['style' => 'width: 200px', 'class' => 'text-center']);
$table->set_additional_parameters(
    array_filter(
        $httpRequest->query->all(),
        function ($key): bool {
            return strpos($key, 'zoom_signatures_') === false;
        },
        ARRAY_FILTER_USE_KEY
    )
);
$table->set_column_filter(
    2,
    function ($dateTime) {
        return $dateTime ? api_convert_and_format_date($dateTime, DATE_TIME_FORMAT_LONG) : null;
    }
);
$table->set_column_filter(
    3,
    function ($imgData) use ($plugin) {
        if (empty($imgData)) {
            return null;
        }

        return Display::img(
            $imgData,
            $plugin->get_lang('SignatureDone'),
            ['class' => 'img-thumbnail'],
            false
        );
    }
);

$cidReq = api_get_cidreq();
$queryParams = 'meetingId='.$meeting->getMeetingId().'&'.$cidReq;
$returnURL = 'meetings.php';

if ($meeting->isCourseMeeting()) {
    api_protect_course_script(true);

    $this_section = SECTION_COURSES;

    $returnURL = 'start.php?'.$cidReq;

    if (api_is_in_group()) {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.$cidReq,
            'name' => get_lang('Groups'),
        ];
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.$cidReq,
            'name' => get_lang('GroupSpace').' '.$meeting->getGroup()->getName(),
        ];
    }
}

$interbreadcrumb[] = [
    'url' => $returnURL,
    'name' => $plugin->get_lang('ZoomVideoConferences'),
];
$interbreadcrumb[] = [
    'url' => 'meeting.php?'.$queryParams,
    'name' => $meeting->getMeetingInfoGet()->topic,
];

$exportPdfLink = Display::url(
    Display::return_icon('pdf.png', get_lang('ExportToPDF'), [], ICON_SIZE_MEDIUM),
    api_get_self().'?'.$queryParams.'&export=pdf'
);
$exportXlsLink = Display::url(
    Display::return_icon('excel.png', get_lang('ExportAsXLS'), [], ICON_SIZE_MEDIUM),
    api_get_self().'?'.$queryParams.'&export=xls'
);

$pageTitle = $plugin->get_lang('Attendance');

$content = '
        <dl>
            <dt>'.$plugin->get_lang('ReasonToSign').'</dt>
            <dd>'.$meeting->getReasonToSignAttendance().'</dd>
        </dl>
    '.$table->return_table();

$tpl = new Template($pageTitle);
$tpl->assign(
    'actions',
    Display::toolbarAction(
        'attendance-actions',
        [$exportPdfLink.PHP_EOL.$exportXlsLink]
    )
);
$tpl->assign('header', $pageTitle);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
