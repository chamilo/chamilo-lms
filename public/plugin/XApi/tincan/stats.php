<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\XApiToolLaunch;
use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../../../main/inc/global.inc.php';

/**
 * Check whether the launch belongs to the current course/session context.
 */
function xapi_stats_matches_current_context(XApiToolLaunch $toolLaunch): bool
{
    $currentCourse = api_get_course_entity();
    $currentSession = api_get_session_entity();

    if (null === $currentCourse || null === $toolLaunch->getCourse()) {
        return false;
    }

    if ($toolLaunch->getCourse()->getId() !== $currentCourse->getId()) {
        return false;
    }

    $toolSession = $toolLaunch->getSession();

    if (null === $currentSession && null === $toolSession) {
        return true;
    }

    if (null === $currentSession || null === $toolSession) {
        return false;
    }

    return $currentSession->getId() === $toolSession->getId();
}

/**
 * Render a simple pagination bar.
 */
function xapi_render_stats_pagination(int $currentPage, int $pageCount, string $baseUrl): string
{
    if ($pageCount <= 1) {
        return '';
    }

    $items = '';

    for ($i = 1; $i <= $pageCount; $i++) {
        if ($i === $currentPage) {
            $items .= '<li class="active"><a href="#">'.$i.'</a></li>';
            continue;
        }

        $items .= '<li><a href="'.$baseUrl.'&page='.$i.'">'.$i.'</a></li>';
    }

    return '<ul class="pagination">'.$items.'</ul>';
}

api_protect_course_script(true);
api_protect_teacher_script();

$request = Container::getRequest();
$em = Database::getManager();

$toolLaunch = $em->find(
    XApiToolLaunch::class,
    $request->query->getInt('id')
);

if (null === $toolLaunch || !xapi_stats_matches_current_context($toolLaunch)) {
    api_not_allowed(true);
}

$normalizedActivityType = strtolower(trim((string) $toolLaunch->getActivityType()));

// This page is intended for TinCan-style launches.
// Only block explicit cmi5 activities here.
if ('cmi5' === $normalizedActivityType) {
    Display::addFlash(
        Display::return_message('Reporting is not available for this activity type on this page.', 'warning')
    );

    header('Location: ../start.php?'.api_get_cidreq());
    exit;
}

$course = api_get_course_entity();
$session = api_get_session_entity();
$cidReq = api_get_cidreq();
$plugin = XApiPlugin::create();

$length = 20;
$page = max(1, $request->query->getInt('page', 1));
$start = ($page - 1) * $length;

$countStudentList = CourseManager::get_student_list_from_course_code(
    $course->getCode(),
    (bool) $session,
    $session ? $session->getId() : 0,
    null,
    null,
    true,
    0,
    true
);

$pageCount = (int) ceil($countStudentList / $length);
$statsUrl = api_get_self().'?'.$cidReq.'&id='.$toolLaunch->getId();

$students = CourseManager::get_student_list_from_course_code(
    $course->getCode(),
    (bool) $session,
    $session ? $session->getId() : 0,
    null,
    null,
    true,
    0,
    false,
    $start,
    $length
);

$loadingMessage = Display::returnFontAwesomeIcon('spinner', '', true, 'fa-pulse').' '.get_lang('Loading');

if ($countStudentList <= 0 || empty($students)) {
    $content = Display::return_message(
        'No learners found in this course/session for reporting.',
        'info'
    );
} else {
    $content = '<div class="xapi-students">';

    foreach ($students as $studentInfo) {
        $studentId = (int) $studentInfo['id'];

        $content .= Display::panelCollapse(
            api_get_person_name($studentInfo['firstname'], $studentInfo['lastname']),
            $loadingMessage,
            "pnl-student-$studentId",
            [
                'class' => 'pnl-student',
                'data-student' => $studentId,
                'data-tool' => $toolLaunch->getId(),
            ],
            "pnl-student-$studentId-accordion",
            "pnl-student-$studentId-collapse",
            false
        );
    }

    $content .= '</div>';
    $content .= xapi_render_stats_pagination($page, $pageCount, $statsUrl);
}

$interbreadcrumb[] = [
    'name' => $plugin->get_title(),
    'url' => '../start.php?'.$cidReq,
];

$htmlHeadXtra[] = "<script>
    $(function () {
        $('.pnl-student').on('show.bs.collapse', function () {
            var \$self = \$(this);
            var \$body = \$self.find('.panel-body');

            if (!\$self.data('loaded')) {
                $.post(
                    'stats_attempts.ajax.php?' + _p.web_cid_query,
                    \$self.data(),
                    function (response) {
                        \$self.data('loaded', true);
                        \$body.html(response);
                    }
                );
            }
        });

        $('.xapi-students').on('click', '.btn_xapi_attempt_detail', function (e) {
            e.preventDefault();

            var \$self = \$(this)
                .addClass('disabled')
                .html('".$loadingMessage."');

            $.post(
                'stats_statements.ajax.php?' + _p.web_cid_query,
                \$self.data(),
                function (response) {
                    \$self.replaceWith(response);
                }
            );
        });
    });
</script>";

$actions = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    '../start.php?'.$cidReq
);

$view = new Template($toolLaunch->getTitle());
$view->assign(
    'actions',
    Display::toolbarAction('xapi_actions', [$actions])
);
$view->assign('header', $toolLaunch->getTitle());
$view->assign('content', $content);
$view->display_one_col_template();
