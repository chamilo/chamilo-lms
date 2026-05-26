<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\XApiToolLaunch;
use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\Uid\Uuid;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_block_anonymous_users();
api_protect_course_script(true);

/**
 * Render a small activity type badge.
 */
function xapi_render_tincan_type_badge(): string
{
    return Display::tag(
        'span',
        'TinCan',
        [
            'class' => 'inline-flex items-center rounded-full border px-2 py-1 text-xs font-semibold',
            'style' => 'background:#ecfeff;color:#155e75;border-color:#a5f3fc;',
        ]
    );
}

/**
 * Build a styled button class string.
 */
function xapi_tincan_button_style(string $variant = 'secondary'): string
{
    $baseStyle = 'display:inline-flex;align-items:center;justify-content:center;'
        .'padding:10px 14px;border-radius:10px;border:1px solid transparent;'
        .'font-size:14px;font-weight:600;white-space:nowrap;cursor:pointer;';

    $variantStyle = match ($variant) {
        'primary' => 'background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;',
        'success' => 'background:#ecfdf5;color:#047857;border-color:#a7f3d0;',
        default => 'background:#f8fafc;color:#334155;border-color:#e2e8f0;',
    };

    return $baseStyle.$variantStyle;
}

/**
 * Render a launch form that can target the preview iframe.
 */
function xapi_render_tincan_launch_form(
    int $toolId,
    string $attemptId,
    string $cidReq,
    string $label,
    string $target,
    string $variant = 'secondary'
): string {
    $action = 'launch.php?'.$cidReq;
    $toolId = (int) $toolId;
    $attemptId = htmlspecialchars($attemptId, ENT_QUOTES, 'UTF-8');
    $target = htmlspecialchars($target, ENT_QUOTES, 'UTF-8');
    $label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

    $button = '<button type="submit" style="'.xapi_tincan_button_style($variant).'">'
        .$label
        .'</button>';

    return '<form method="post" action="'.$action.'" target="'.$target.'" style="margin:0;">'
        .'<input type="hidden" name="attempt_id" value="'.$attemptId.'">'
        .'<input type="hidden" name="id" value="'.$toolId.'">'
        .$button
        .'</form>';
}

$request = Container::getRequest();

$originIsLearnpath = 'learnpath' === api_get_origin();

$user = api_get_user_entity(api_get_user_id());

$em = Database::getManager();

$toolLaunch = $em->find(
    XApiToolLaunch::class,
    $request->query->getInt('id')
);

if (null === $toolLaunch
    || $toolLaunch->getCourse()->getId() !== api_get_course_entity()->getId()
) {
    api_not_allowed(true);
}

$plugin = XApiPlugin::create();
$actor = $plugin->buildTinCanActorPayload($user);
$stateId = $plugin->getTinCanStateId($toolLaunch->getId());

$cidReq = api_get_cidreq();
$previewFrameName = 'xapi_tincan_preview';
$launchTarget = $originIsLearnpath ? '_self' : $previewFrameName;

try {
    $stateDocument = $plugin->fetchActivityStateDocument(
        (string) $toolLaunch->getActivityId(),
        $actor,
        $stateId,
        null,
        $toolLaunch->getLrsUrl(),
        $toolLaunch->getLrsAuthUsername(),
        $toolLaunch->getLrsAuthPassword()
    );
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message($exception->getMessage(), 'error')
    );

    header('Location: '.api_get_course_url());
    exit;
}

if (!empty($stateDocument) && is_array($stateDocument)) {
    uasort(
        $stateDocument,
        static function ($attemptA, $attemptB): int {
            $timeA = isset($attemptA[XApiPlugin::STATE_LAST_LAUNCH])
                ? strtotime((string) $attemptA[XApiPlugin::STATE_LAST_LAUNCH])
                : 0;
            $timeB = isset($attemptB[XApiPlugin::STATE_LAST_LAUNCH])
                ? strtotime((string) $attemptB[XApiPlugin::STATE_LAST_LAUNCH])
                : 0;

            return $timeB <=> $timeA;
        }
    );
}

$interbreadcrumb[] = ['url' => '../start.php', 'name' => $plugin->get_lang('ToolTinCan')];

$pageTitle = $toolLaunch->getTitle();
$pageContent = '';

$descriptionHtml = '';
if ($toolLaunch->getDescription()) {
    $descriptionHtml = Display::tag(
        'p',
        $toolLaunch->getDescription(),
        ['class' => 'text-muted', 'style' => 'margin:0;']
    );
}

$newAttemptForm = '';
if ($toolLaunch->isAllowMultipleAttempts() || empty($stateDocument)) {
    $newAttemptForm = xapi_render_tincan_launch_form(
        $toolLaunch->getId(),
        Uuid::v4()->toRfc4122(),
        $cidReq,
        $plugin->get_lang('LaunchNewAttempt'),
        $launchTarget,
        'success'
    );
}

$attemptTableHtml = '';

if (!empty($stateDocument) && is_array($stateDocument)) {
    $table = new HTML_Table(['class' => 'table table-hover table-striped']);
    $table->setHeaderContents(0, 0, $plugin->get_lang('ActivityFirstLaunch'));
    $table->setHeaderContents(0, 1, $plugin->get_lang('ActivityLastLaunch'));
    $table->setHeaderContents(0, 2, get_lang('Actions'));

    $row = 1;

    foreach ($stateDocument as $attemptId => $attempt) {
        if (!is_array($attempt)) {
            continue;
        }

        $firstLaunch = !empty($attempt[XApiPlugin::STATE_FIRST_LAUNCH])
            ? api_convert_and_format_date(
                $attempt[XApiPlugin::STATE_FIRST_LAUNCH],
                DATE_TIME_FORMAT_LONG
            )
            : '-';

        $lastLaunch = !empty($attempt[XApiPlugin::STATE_LAST_LAUNCH])
            ? api_convert_and_format_date(
                $attempt[XApiPlugin::STATE_LAST_LAUNCH],
                DATE_TIME_FORMAT_LONG
            )
            : '-';

        $launchForm = xapi_render_tincan_launch_form(
            $toolLaunch->getId(),
            (string) $attemptId,
            $cidReq,
            $plugin->get_lang('ActivityLaunch'),
            $launchTarget
        );

        $table->setCellContents($row, 0, $firstLaunch);
        $table->setCellContents($row, 1, $lastLaunch);
        $table->setCellContents($row, 2, $launchForm);

        $row++;
    }

    $table->setColAttributes(0, ['class' => 'text-center', 'style' => 'width:35%;']);
    $table->setColAttributes(1, ['class' => 'text-center', 'style' => 'width:35%;']);
    $table->setColAttributes(2, ['class' => 'text-center', 'style' => 'width:30%;']);

    $attemptTableHtml = Display::tag(
        'div',
        $table->toHtml(),
        ['style' => 'margin-top:20px;']
    );
}

$topCard = '<div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm" '
    .'style="border:1px solid #e5e7eb;border-radius:18px;background:#fff;padding:24px;">'
    .'<div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:16px;">'
    .'<div style="display:flex;flex-direction:column;gap:10px;">'
    .Display::tag(
        'div',
        xapi_render_tincan_type_badge(),
        ['style' => 'display:flex;align-items:center;gap:8px;']
    )
    .$descriptionHtml
    .'</div>'
    .'<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">'
    .$newAttemptForm
    .'</div>'
    .'</div>'
    .$attemptTableHtml
    .'</div>';

$pageContent .= $topCard;

if (!$originIsLearnpath) {
    $pageContent .= '<div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm" '
        .'style="border:1px solid #e5e7eb;border-radius:18px;background:#fff;padding:24px;margin-top:24px;">'
        .Display::tag('h3', get_lang('Preview'), [
            'style' => 'margin:0 0 6px 0;font-size:22px;font-weight:700;',
        ])
        .Display::tag(
            'p',
            get_lang('The selected activity will open here.'),
            ['class' => 'text-muted', 'style' => 'margin:0 0 16px 0;']
        )
        .'<iframe '
        .'name="'.$previewFrameName.'" '
        .'title="TinCan preview" '
        .'src="about:blank" '
        .'style="width:100%;min-height:780px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;"'
        .'></iframe>'
        .'</div>';
}

$actions = '';

if (!$originIsLearnpath) {
    $actions = Display::url(
        Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
        '../start.php?'.api_get_cidreq()
    );
}

$view = new Template($pageTitle);
$view->assign('header', $pageTitle);

if ($actions) {
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
