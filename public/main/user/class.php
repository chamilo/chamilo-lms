<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;

api_protect_course_script(true, false, 'user');

if ('false' === api_get_setting('allow_user_course_subscription_by_course_admin')) {
    if (!api_is_platform_admin()) {
        api_not_allowed(true);
    }
}

if ('true' === api_get_setting('session.session_classes_tab_disable') && !api_is_platform_admin() && api_get_session_id()) {
    api_not_allowed(true);
}

$tool_name = get_lang('Classes');

$interbreadcrumb[] = [
    'url' => 'user.php?'.api_get_cidreq(),
    'name' => get_lang('Users'),
];

$type = isset($_GET['type']) ? Security::remove_XSS($_GET['type']) : 'registered';
$groupFilter = isset($_GET['group_filter']) ? (int) $_GET['group_filter'] : 0;
$keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : '';

$isRegisteredView = 'registered' === $type;

$infoMessage = $isRegisteredView
    ? 'Information: This list shows the classes already linked to this course. Use the add icon above to browse available classes.'
    : 'Information: This list shows the classes that are not yet linked to this course. Use the add action in the detail column to link a class to this course.';

$emptyRecordsMessage = $isRegisteredView
    ? 'No classes are currently linked to this course.'
    : 'No available classes were found for this course.';

$htmlHeadXtra[] = '
<script>
$(function() {
    $("#group_filter").change(function() {
        window.location = "class.php?'.api_get_cidreq().'&type='.$type.'" + "&group_filter=" + $(this).val();
    });
});
</script>';

$actionsLeft = '';
$actionsRight = '';
$usergroup = new UserGroupModel();
$actions = '';

$sessionId = api_get_session_id();
if (api_is_allowed_to_edit()) {
    if ($isRegisteredView) {
        $actionsLeft .= '<a href="class.php?'.api_get_cidreq().'&type=not_registered">'.
            Display::getMdiIcon(
                ActionIcon::ADD,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                'Browse available classes'
            ).'</a>';
    } else {
        $actionsLeft .= '<a href="class.php?'.api_get_cidreq().'&type=registered">'.
            Display::getMdiIcon(
                ActionIcon::BACK,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                'Back to linked classes'
            ).'</a>';

        $form = new FormValidator(
            'groups',
            'get',
            api_get_self().'?type='.$type,
            '',
            [],
            FormValidator::LAYOUT_INLINE
        );
        $options = [
            -1 => get_lang('All'),
            1 => get_lang('Social groups'),
            0 => get_lang('Classes'),
        ];
        $form->addSelect(
            'group_filter',
            get_lang('Groups'),
            $options,
            ['id' => 'group_filter', 'disable_js' => 'disable_js']
        );
        $form->addHidden('type', $type);
        $form->addText('keyword', '', false);
        $form->setDefaults(['group_filter' => $groupFilter]);
        $form->addCourseHiddenParams();
        $form->addButtonSearch(get_lang('Search'));

        $actionsRight .= $form->returnForm();
    }

    $actions = Display::toolbarAction('actions-class', [$actionsLeft, $actionsRight]);
    $action = isset($_GET['action']) ? $_GET['action'] : null;

    switch ($action) {
        case 'add_usergroup_to_course':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if (!empty($id) && 0 == $sessionId) {
                $usergroup->subscribe_courses_to_usergroup(
                    $id,
                    [api_get_course_int_id()],
                    false
                );
                Display::addFlash(Display::return_message(get_lang('Added')));
                header('Location: class.php?'.api_get_cidreq().'&type=registered');
                exit;
            } elseif (!empty($id) && 0 != $sessionId) {
                // Subscribe the class to the current session.
                $usergroup->subscribe_sessions_to_usergroup($id, [$sessionId]);
                Display::addFlash(Display::return_message(get_lang('Added')));
                header('Location: class.php?'.api_get_cidreq().'&type=registered');
                exit;
            }
            break;

        case 'remove_usergroup_from_course':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if (!empty($id)) {
                $usergroup->unsubscribe_courses_from_usergroup(
                    $id,
                    [api_get_course_int_id()]
                );
                Display::addFlash(Display::return_message(get_lang('Deleted')));
            }
            break;

        case 'remove_only_usergroup_from_course':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if (!empty($id)) {
                $usergroup->unsubscribeOnlyCoursesFromUsergroup(
                    $id,
                    [api_get_course_int_id()]
                );
                Display::addFlash(Display::return_message(get_lang('Removed')));
            }
            break;
    }
}

// jqGrid will use this URL to load the class list.
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_usergroups_teacher&type='.$type.'&group_filter='.$groupFilter.'&keyword='.$keyword.'&'.api_get_cidreq();

// The order is important; it must match the response in model.ajax.php.
$columns = [
    get_lang('Name'),
    get_lang('Users'),
    get_lang('Status'),
    get_lang('Type'),
    get_lang('Detail'),
];

// Use proportional widths so the grid can stretch naturally.
$columnModel = [
    [
        'name' => 'name',
        'index' => 'name',
        'align' => 'left',
        'width' => '34',
    ],
    [
        'name' => 'users',
        'index' => 'users',
        'align' => 'left',
        'width' => '12',
    ],
    [
        'name' => 'status',
        'index' => 'status',
        'align' => 'left',
        'width' => '16',
    ],
    [
        'name' => 'group_type',
        'index' => 'group_type',
        'align' => 'center',
        'width' => '14',
    ],
    [
        'name' => 'actions',
        'index' => 'actions',
        'align' => 'center',
        'sortable' => 'false',
        'width' => '24',
    ],
];

$extraParams['autowidth'] = 'true';
$extraParams['height'] = 'auto';
$extraParams['shrinkToFit'] = true;
$extraParams['forceFit'] = true;
$extraParams['viewrecords'] = true;
$extraParams['emptyrecords'] = get_lang($emptyRecordsMessage);

Display::display_header($tool_name, 'User');

?>
    <style>
        /* Page-specific responsive tweaks for the classes grid */
        .course-class-page {
            width: 100%;
        }

        .course-class-page .ui-jqgrid,
        .course-class-page .ui-jqgrid-view,
        .course-class-page .ui-jqgrid-hdiv,
        .course-class-page .ui-jqgrid-bdiv,
        .course-class-page .ui-jqgrid-sdiv,
        .course-class-page .ui-jqgrid-pager {
            width: 100% !important;
            max-width: none !important;
        }

        .course-class-page .ui-jqgrid .ui-jqgrid-htable,
        .course-class-page .ui-jqgrid .ui-jqgrid-btable {
            width: 100% !important;
        }

        .course-class-page .ui-jqgrid-bdiv {
            max-height: none !important;
        }

        .course-class-page .ui-jqgrid .ui-jqgrid-htable th,
        .course-class-page .ui-jqgrid .ui-jqgrid-btable td {
            white-space: normal;
        }

        .course-class-page .ui-jqgrid .ui-pg-table {
            width: auto !important;
        }

        .course-class-page .ui-jqgrid .ui-jqgrid-pager {
            overflow-x: auto;
        }

        .course-class-page .ui-jqgrid .ui-jqgrid-btable td[aria-describedby="usergroups_actions"] a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 0.2rem;
        }

        .course-class-page .alert {
            margin-bottom: 1rem;
        }

        .course-class-page .actions {
            margin-bottom: 1rem;
        }
    </style>

    <script>
        $(function() {
            <?php
            echo Display::grid_js(
                'usergroups',
                $url,
                $columns,
                $columnModel,
                $extraParams,
                [],
                '',
                true
            );
            ?>

            function resizeClassGrid() {
                var $grid = $("#usergroups");

                if (!$grid.length || !$grid[0].grid) {
                    return;
                }

                var $container = $(".course-class-page");

                if (!$container.length) {
                    return;
                }

                var newWidth = Math.floor($container.innerWidth());

                if (newWidth > 0) {
                    $grid.jqGrid("setGridWidth", newWidth, true);
                }
            }

            resizeClassGrid();

            $(window).on("resize.classGrid", function() {
                resizeClassGrid();
            });

            setTimeout(function() {
                resizeClassGrid();
            }, 0);
        });
    </script>
<?php

echo '<div class="course-class-page">';

echo UserManager::getUserSubscriptionTab(4);
echo $actions;
echo Display::return_message(get_lang('Information: The list of classes below contains the list of classes you have already registered in your course. If this list is empty, use the + green above to add classes.'));
$usergroup->display_teacher_view();

echo '</div>';

Display::display_footer();
