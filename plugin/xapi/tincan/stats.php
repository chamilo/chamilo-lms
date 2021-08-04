<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
use Knp\Component\Pager\Paginator;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_course_script(true);
api_protect_teacher_script();

$request = HttpRequest::createFromGlobals();

$em = Database::getManager();

$toolLaunch = $em->find(
    ToolLaunch::class,
    $request->query->getInt('id')
);

if (null === $toolLaunch) {
    header('Location: '.api_get_course_url());
    exit;
}

$course = api_get_course_entity();
$session = api_get_session_entity();

$cidReq = api_get_cidreq();

$plugin = XApiPlugin::create();

$length = 20;
$page = $request->query->getInt('page', 1);
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

$statsUrl = api_get_self().'?'.api_get_cidreq().'&id='.$toolLaunch->getId();

$paginator = new Paginator();
$pagination = $paginator->paginate([]);
$pagination->setTotalItemCount($countStudentList);
$pagination->setItemNumberPerPage($length);
$pagination->setCurrentPageNumber($page);
$pagination->renderer = function ($data) use ($statsUrl) {
    $render = '';
    if ($data['pageCount'] > 1) {
        $render = '<ul class="pagination">';
        for ($i = 1; $i <= $data['pageCount']; $i++) {
            $pageContent = '<li><a href="'.$statsUrl.'&page='.$i.'">'.$i.'</a></li>';
            if ($data['current'] == $i) {
                $pageContent = '<li class="active"><a href="#" >'.$i.'</a></li>';
            }
            $render .= $pageContent;
        }
        $render .= '</ul>';
    }

    return $render;
};

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

$content = '';
$content .= '<div class="xapi-students">';

$loadingMessage = Display::returnFontAwesomeIcon('spinner', '', true, 'fa-pulse').' '.get_lang('Loading');

foreach ($students as $studentInfo) {
    $content .= Display::panelCollapse(
        api_get_person_name($studentInfo['firstname'], $studentInfo['lastname']),
        $loadingMessage,
        "pnl-student-{$studentInfo['id']}",
        [
            'class' => 'pnl-student',
            'data-student' => $studentInfo['id'],
            'data-tool' => $toolLaunch->getId(),
        ],
        "pnl-student-{$studentInfo['id']}-accordion",
        "pnl-student-{$studentInfo['id']}-collapse",
        false
    );
}

$content .= '</div>';
$content .= $pagination;

// View
$interbreadcrumb[] = [
    'name' => $plugin->get_title(),
    'url' => '../start.php',
];

$htmlHeadXtra[] = "<script>
    $(function () {
        $('.pnl-student').on('show.bs.collapse', function (e) {
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
    })
</script>";

$actions = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    "../start.php?$cidReq"
);

$view = new Template($toolLaunch->getTitle());
$view->assign(
    'actions',
    Display::toolbarAction('xapi_actions', [$actions])
);
$view->assign('header', $toolLaunch->getTitle());
$view->assign('content', $content);
$view->display_one_col_template();
