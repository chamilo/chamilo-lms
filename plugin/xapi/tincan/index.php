<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_course_script(true);
api_block_anonymous_users();

$plugin = XApiPlugin::create();

$isAllowedToEdit = api_is_allowed_to_edit();

$em = Database::getManager();

$course = api_get_course_entity();
$session = api_get_session_entity();

$cidReq = api_get_cidreq();

$table = new SortableTable(
    'tbl_xapi',
    function () use ($em, $course) {
        return $em
            ->createQuery('SELECT COUNT(tl) FROM ChamiloPluginBundle:XApi\ToolLaunch tl WHERE tl.course = :course')
            ->setParameter('course', $course)
            ->getSingleScalarResult();
    },
    function ($start, $limit, $orderBy, $orderDir) use ($em, $course, $isAllowedToEdit) {
        $tools = $em->getRepository('ChamiloPluginBundle:XApi\ToolLaunch')
            ->findBy(
                ['course' => $course],
                ['title' => $orderDir],
                $limit,
                $start
            );

        $data = [];

        /** @var ToolLaunch $toolLaunch */
        foreach ($tools as $toolLaunch) {
            $datum = [];
            $datum[] = [
                $toolLaunch->getId(),
                $toolLaunch->getTitle(),
                $toolLaunch->getDescription(),
                $toolLaunch->getActivityType(),
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
    function (array $toolInfo) use ($cidReq) {
        list($id, $title, $description, $ativityType) = $toolInfo;

        $data = Display::url(
            $title,
            ('cmi5' === $ativityType ? '../cmi5/tool.php' : 'tool.php')."?id=$id&$cidReq",
            ['class' => 'show']
        );

        if ($description) {
            $data .= PHP_EOL.Display::tag('small', $description, ['class' => 'text-muted']);
        }

        return $data;
    }
);

if ($isAllowedToEdit) {
    $table->set_header(1, get_lang('Actions'), false, ['class' => 'text-right'], ['class' => 'text-right']);
    $table->set_column_filter(
        1,
        function ($id) use ($cidReq, $isAllowedToEdit) {
            $actions = [];

            if ($isAllowedToEdit) {
                $actions[] = Display::url(
                    Display::return_icon('statistics.png', get_lang('Reporting')),
                    "stats.php?$cidReq&id=$id"
                );
                $actions[] = Display::url(
                    Display::return_icon('edit.png', get_lang('Edit')),
                    "edit.php?$cidReq&edit=$id"
                );
                $actions[] = Display::url(
                    Display::return_icon('delete.png', get_lang('Delete')),
                    "delete.php?$cidReq&delete=$id"
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
            Display::return_icon('add.png', get_lang('Add'), [], ICON_SIZE_MEDIUM),
            "add.php?$cidReq"
        )
        .PHP_EOL
        .Display::url(
            Display::return_icon('add.png', get_lang('Add'), [], ICON_SIZE_MEDIUM),
            "../cmi5/add.php?$cidReq"
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
