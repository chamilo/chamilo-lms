<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\XApiToolLaunch;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script(true);
api_block_anonymous_users();

$plugin = XApiPlugin::create();

$isAllowedToEdit = api_is_allowed_to_edit();

$em = Database::getManager();
$toolLaunchRepo = $em->getRepository(XApiToolLaunch::class);

$course = api_get_course_entity();
$session = api_get_session_entity();
$currentUser = api_get_user_entity();

$cidReq = api_get_cidreq();

/**
 * Render an MDI icon.
 */
function xapi_mdi_icon(string $iconClass): string
{
    return Display::tag('span', '', [
        'class' => 'mdi '.$iconClass,
        'aria-hidden' => 'true',
        'style' => 'margin-right: 6px;',
    ]);
}

/**
 * Render a small activity type badge.
 */
function xapi_render_type_badge(string $activityType): string
{
    $normalizedType = strtolower(trim($activityType));
    $label = 'cmi5' === $normalizedType ? 'CMI5' : 'TinCan';

    $class = 'inline-flex items-center rounded-full border px-2 py-1 text-xs font-semibold';
    $style = 'background: #ecfeff; color: #155e75; border-color: #a5f3fc;';

    if ('cmi5' === $normalizedType) {
        $style = 'background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe;';
    }

    return Display::tag('span', $label, [
        'class' => $class,
        'style' => $style,
    ]);
}

/**
 * Render a modern action button link.
 */
function xapi_render_action_button(
    string $url,
    string $iconClass,
    string $label,
    string $variant = 'secondary'
): string {
    $baseStyle = 'display:inline-flex;align-items:center;justify-content:center;'
        .'padding:8px 12px;border-radius:10px;border:1px solid transparent;'
        .'font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;';

    $variantStyle = match ($variant) {
        'primary' => 'background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;',
        'danger' => 'background:#fef2f2;color:#b91c1c;border-color:#fecaca;',
        default => 'background:#f8fafc;color:#334155;border-color:#e2e8f0;',
    };

    return Display::url(
        xapi_mdi_icon($iconClass).$label,
        $url,
        [
            'style' => $baseStyle.$variantStyle,
        ]
    );
}

$table = new SortableTable(
    'tbl_xapi',
    function () use ($em, $course, $session, $isAllowedToEdit) {
        return $em->getRepository(XApiToolLaunch::class)
            ->countByCourseAndSession($course, $session, !$isAllowedToEdit);
    },
    function ($start, $limit, $orderBy, $orderDir) use ($toolLaunchRepo, $course, $session, $isAllowedToEdit) {
        $tools = $toolLaunchRepo->findByCourseAndSession(
            $course,
            $session,
            ['title' => $orderDir],
            $limit,
            $start
        );

        $data = [];

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
                $datum[] = [
                    $toolLaunch->getId(),
                    $toolLaunch->getActivityType(),
                ];
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
    function (array $toolInfo) use ($cidReq, $session, $currentUser, $plugin) {
        [$id, $title, $description, $activityType, $wasAddedInLp] = $toolInfo;

        $sessionStar = '';

        if ($session && $currentUser) {
            $sessionStar = api_get_session_image(
                $session->getId(),
                $currentUser
            );
        }

        $viewUrl = ('cmi5' === strtolower(trim((string) $activityType)) ? 'cmi5/view.php' : 'tincan/view.php')."?id=$id&$cidReq";

        $titleLink = Display::url(
            $title.$sessionStar,
            $viewUrl,
            [
                'class' => 'show',
                'style' => 'font-weight:600;',
            ]
        );

        $headerLine = Display::tag(
            'div',
            $titleLink.xapi_render_type_badge((string) $activityType),
            [
                'style' => 'display:flex;flex-wrap:wrap;align-items:center;gap:8px;',
            ]
        );

        $data = $headerLine;

        if ($description) {
            $data .= Display::tag(
                'div',
                Display::tag('small', $description, ['class' => 'text-muted']),
                ['style' => 'margin-top:6px;']
            );
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
    $thAttributes = ['class' => 'text-right', 'style' => 'width: 330px;'];

    $table->set_header(1, get_lang('Actions'), false, $thAttributes, $thAttributes);
    $table->set_column_filter(
        1,
        function (array $actionInfo) use ($cidReq, $isAllowedToEdit) {
            [$id, $activityType] = $actionInfo;

            $actions = [];

            if ($isAllowedToEdit) {
                $normalizedType = strtolower(trim((string) $activityType));
                $statsUrl = null;

                // TinCan reporting is already implemented, so always expose it.
                if ('cmi5' === $normalizedType) {
                    if (file_exists(__DIR__.'/cmi5/stats.php')) {
                        $statsUrl = 'cmi5/stats.php';
                    }
                } else {
                    $statsUrl = 'tincan/stats.php';
                }

                if (null !== $statsUrl) {
                    $actions[] = xapi_render_action_button(
                        "$statsUrl?$cidReq&id=$id",
                        'mdi-chart-box-outline',
                        get_lang('Reporting'),
                        'primary'
                    );
                }

                $actions[] = xapi_render_action_button(
                    "tool_edit.php?$cidReq&edit=$id",
                    'mdi-pencil-outline',
                    get_lang('Edit')
                );

                $actions[] = xapi_render_action_button(
                    "tool_delete.php?$cidReq&delete=$id",
                    'mdi-delete-outline',
                    get_lang('Delete'),
                    'danger'
                );
            }

            return Display::tag(
                'div',
                implode('', $actions),
                [
                    'style' => 'display:flex;flex-wrap:wrap;justify-content:flex-end;gap:8px;min-width:300px;',
                ]
            );
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
    $actions = xapi_render_action_button(
        "tool_import.php?$cidReq",
        'mdi-file-import-outline',
        get_lang('Import'),
        'primary'
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
