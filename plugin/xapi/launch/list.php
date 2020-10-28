<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_course_script(true);
api_protect_teacher_script();

$plugin = XApiPlugin::create();

$em = Database::getManager();

$course = api_get_course_entity();
$session = api_get_session_entity();

$cidReq = api_get_cidreq();

$table = new SortableTable(
    'tbl_xapi',
    function () use ($em) {
        return $em
            ->createQuery('SELECT COUNT(tl) FROM ChamiloPluginBundle:XApi\ToolLaunch tl')
            ->getSingleScalarResult();
    },
    function ($start, $limit, $orderBy, $orderDir) use ($em) {
        $tools = $em->getRepository('ChamiloPluginBundle:XApi\ToolLaunch')
            ->findBy(
                [],
                ['title' => $orderDir],
                $limit,
                $start
            );

        return array_map(
            function (ToolLaunch $toolLaunch) {
                return [
                    [$toolLaunch->getTitle(), $toolLaunch->getDescription()],
                    $toolLaunch->getId(),
                ];
            },
            $tools
        );
    },
    0
);
$table->set_header(0, $plugin->get_lang('ActivityTitle'), true);
$table->set_header(1, get_lang('Actions'), false, ['class' => 'text-right'], ['class' => 'text-right']);
$table->set_column_filter(
    0,
    function (array $toolInfo) {
        list($title, $description) = $toolInfo;

        return "<span class='show'>$title</span>"
            .($description ? "<small class='text-muted'>$description</small>" : null);
    }
);
$table->set_column_filter(
    1,
    function ($id) use ($cidReq) {
        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('edit.png', get_lang('Edit')),
            "edit.php?$cidReq&edit=$id"
        );
        $actions[] = Display::url(
            Display::return_icon('delete.png', get_lang('Delete')),
            "delete.php?$cidReq&delete=$id"
        );

        return implode(PHP_EOL, $actions);
    }
);

$actions = Display::url(
    Display::return_icon('add.png', get_lang('Add'), [], ICON_SIZE_MEDIUM),
    "add.php?$cidReq"
);

$pageTitle = $plugin->get_title();

if ($table->get_total_number_of_items() > 0) {
    $pageContent = $table->return_table();
} else {
    $pageContent = Display::return_message($plugin->get_lang('NoActivities'), 'info');
}

$view = new Template($pageTitle);
$view->assign('header', $pageTitle);
$view->assign(
    'actions',
    Display::toolbarAction(
        'xapi_actions',
        [$actions]
    )
);
$view->assign('content', $pageContent);
$view->display_one_col_template();
