<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
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

$table = new SortableTable(
    'tbl_xapi',
    function () use ($course, $session) {
        if ($session) {
            return CourseManager::get_student_list_from_course_code(
                $course->getCode(),
                true,
                $session->getId(),
                null,
                null,
                true,
                0,
                true
            );
        }

        return CourseManager::get_student_list_from_course_code(
            $course->getCode(),
            false,
            0,
            null,
            null,
            true,
            0,
            true
        );
    },
    function ($start, $limit, $orderBy, $orderDir) use ($course, $session) {
        if ($session) {
            $students = CourseManager::get_student_list_from_course_code(
                $course->getCode(),
                true,
                $session->getId(),
                null,
                null,
                true,
                0,
                false,
                $start,
                $limit
            );
        } else {
            $students = CourseManager::get_student_list_from_course_code(
                $course->getCode(),
                false,
                0,
                null,
                null,
                true,
                0,
                false,
                $start,
                $limit
            );
        }

        return array_map(
            function (array $studentInfo) {
                return [
                    $studentInfo['firstname'],
                    $studentInfo['lastname'],
                    $studentInfo['id'],
                ];
            },
            $students
        );
    }
);
$table->set_header(0, get_lang('FirstName'), false);
$table->set_header(1, get_lang('LastName'), false);
$table->set_header(2, get_lang('Attempts'), false, [], ['style' => 'width: 65%;']);
$table->set_column_filter(
    2,
    function ($id) use ($toolLaunch) {
        return Display::button(
            "xapi_state_$id",
            get_lang('ShowAllAttempts'),
            [
                'class' => 'btn btn-default btn_xapi_attempts',
                'data-student' => $id,
                'data-tool' => $toolLaunch->getId(),
            ]
        );
    }
);
$table->set_additional_parameters(
    [
        'id' => $toolLaunch->getId(),
        'cidReq' => $course->getCode(),
        'id_session' => $session ? $session->getId() : 0,
    ]
);

// View
$interbreadcrumb[] = [
    'name' => $plugin->get_title(),
    'url' => 'list.php',
];

$htmlHeadXtra[] = "<script>
    $(function () {
        $('.btn_xapi_attempts').on('click', function () {
            var \$self = $(this);

            \$self
                .prop('disabled', true)
                .html('<em class=\"fa fa-spinner fa-pulse\"></em> ".get_lang('Loading')."');

            $.post(
                'stats_attempts.ajax.php?' + _p.web_cid_query,
                \$self.data(),
                function (response) {
                    \$self.parent().html(response);
                }
            );
        });
    })
</script>";

$actions = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    "list.php?$cidReq"
);

$view = new Template($toolLaunch->getTitle());
$view->assign(
    'actions',
    Display::toolbarAction('xapi_actions', [$actions])
);
$view->assign('header', $toolLaunch->getTitle());
$view->assign(
    'content',
    Display::page_subheader(get_lang('Reporting'), null, 'h4').PHP_EOL.$table->return_table()
);
$view->display_one_col_template();
