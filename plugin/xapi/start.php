<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script(true);
api_block_anonymous_users();

$plugin = XApiPlugin::create();

$isAllowedToEdit = api_is_allowed_to_edit();

$em = Database::getManager();
$toolLaunchRepo = $em->getRepository(ToolLaunch::class);

$course = api_get_course_entity();
$session = api_get_session_entity();
$userInfo = api_get_user_info();

$cidReq = api_get_cidreq();

$table = new SortableTable(
    'tbl_xapi',
    function () use ($em, $course, $session, $isAllowedToEdit) {
        return $em->getRepository(ToolLaunch::class)
            ->countByCourseAndSession($course, $session, !$isAllowedToEdit);
    },
    function ($start, $limit, $orderBy, $orderDir) use ($toolLaunchRepo, $course, $session, $isAllowedToEdit) {
        $tools = $toolLaunchRepo->findByCourseAndSession($course, $session, ['title' => $orderDir], $limit, $start);

        $data = [];

        /** @var ToolLaunch $toolLaunch */
        foreach ($tools as $toolLaunch) {
            $wasAddedInLp = $toolLaunchRepo->wasAddedInLp($toolLaunch);

            if ($wasAddedInLp && !$isAllowedToEdit) {
                continue;
            }

            $datum = [];
            $datum[] = [
                $toolLaunch->getId(),
                $toolLaunch->getTitle(),
                $toolLaunch->getDescription(),
                $toolLaunch->getActivityType(),
                $wasAddedInLp,
            ];

            if ($isAllowedToEdit) {
                $datum[] = $toolLaunch->getId();
            }

            $data[] = $datum;
        }

        return $data;
    },
    0
);
$table->set_header(0, $plugin->get_lang('ActivityTitle'), true);
$table->set_column_filter(
    0,
    function (array $toolInfo) use ($cidReq, $session, $userInfo, $plugin) {
        list($id, $title, $description, $activityType, $wasAddedInLp) = $toolInfo;

        $sessionStar = api_get_session_image(
            $session ? $session->getId() : 0,
            $userInfo['status']
        );

        $data = Display::url(
            $title.$sessionStar,
            ('cmi5' === $activityType ? 'cmi5/view.php' : 'tincan/view.php')."?id=$id&$cidReq",
            ['class' => 'show']
        );

        if ($description) {
            $data .= PHP_EOL.Display::tag('small', $description, ['class' => 'text-muted']);
        }

        if ($wasAddedInLp) {
            $data .= Display::div(
                $plugin->get_lang('ActivityAddedToLPCannotBeAccessed'),
                ['class' => 'lp_content_type_label']
            );
        }

        return $data;
    }
);

if ($isAllowedToEdit) {
    $thAttributes = ['class' => 'text-right', 'style' => 'width: 100px;'];

    $table->set_header(1, get_lang('Actions'), false, $thAttributes, $thAttributes);
    $table->set_column_filter(
        1,
        function ($id) use ($cidReq, $isAllowedToEdit) {
            $actions = [];

            if ($isAllowedToEdit) {
                $actions[] = Display::url(
                    Display::return_icon('statistics.png', get_lang('Reporting')),
                    "tincan/stats.php?$cidReq&id=$id"
                );
                $actions[] = Display::url(
                    Display::return_icon('edit.png', get_lang('Edit')),
                    "tool_edit.php?$cidReq&edit=$id"
                );
                $actions[] = Display::url(
                    Display::return_icon('delete.png', get_lang('Delete')),
                    "tool_delete.php?$cidReq&delete=$id"
                );
            }

            return implode(PHP_EOL, $actions);
        }
    );
}

$pageTitle = $plugin->get_lang('ToolTinCan');
$pageContent = Display::return_message($plugin->get_lang('NoActivities'), 'info');

if ($table->get_total_number_of_items() > 0) {
    $pageContent = $table->return_table();
}

$view = new Template($pageTitle);
$view->assign('header', $pageTitle);

if ($isAllowedToEdit) {
    $actions = Display::url(
        Display::return_icon('import_scorm.png', get_lang('Import'), [], ICON_SIZE_MEDIUM),
        "tool_import.php?$cidReq"
    );

    $view->assign(
        'actions',
        Display::toolbarAction(
            'xapi_actions',
            [$actions]
        )
    );
}

$view->assign('content', $pageContent);
$view->display_one_col_template();
