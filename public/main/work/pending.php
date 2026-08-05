<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$isPlatformAdmin = api_is_platform_admin(false, true);
$canAccessPendingWork = $isPlatformAdmin || api_is_teacher();

if (!$canAccessPendingWork) {
    api_not_allowed(true);
}

$this_section = SECTION_COURSES;
$is_allowed_to_edit = $canAccessPendingWork;

$userId = api_get_user_id();
$type = 'simple';

$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-upload']);

$htmlHeadXtra[] = <<<HTML
<style>
    .pending-work-grid-wrapper {
        width: 100%;
        overflow-x: hidden;
    }

    .pending-work-grid-wrapper .ui-jqgrid,
    .pending-work-grid-wrapper .ui-jqgrid-view,
    .pending-work-grid-wrapper .ui-jqgrid-hdiv,
    .pending-work-grid-wrapper .ui-jqgrid-bdiv,
    .pending-work-grid-wrapper .ui-jqgrid-pager {
        width: 100% !important;
        max-width: 100% !important;
    }

    .pending-work-grid-wrapper .ui-jqgrid {
        border: 0;
        font-size: 0.875rem;
    }

    .pending-work-grid-wrapper .ui-jqgrid-hbox {
        float: none;
        width: 100% !important;
        padding-right: 0 !important;
    }

    .pending-work-grid-wrapper .ui-jqgrid-htable,
    .pending-work-grid-wrapper .ui-jqgrid-btable {
        width: 100% !important;
        table-layout: fixed;
    }

    .pending-work-grid-wrapper .ui-jqgrid-hdiv {
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
        overflow: hidden;
    }

    .pending-work-grid-wrapper .ui-jqgrid-htable th {
        height: 44px;
        background: #f8fafc;
        border-color: #e5e7eb;
        color: #111827;
        font-weight: 600;
    }

    .pending-work-grid-wrapper .ui-jqgrid-htable th div {
        height: auto;
        padding: 0.5rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .pending-work-grid-wrapper .ui-jqgrid-btable td {
        min-height: 44px;
        padding: 0.5rem;
        border-color: #e5e7eb;
        vertical-align: middle;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .pending-work-grid-wrapper .ui-jqgrid-btable td[aria-describedby$="_course"],
    .pending-work-grid-wrapper .ui-jqgrid-btable td[aria-describedby$="_work_name"],
    .pending-work-grid-wrapper .ui-jqgrid-btable td[aria-describedby$="_fullname"],
    .pending-work-grid-wrapper .ui-jqgrid-btable td[aria-describedby$="_title"] {
        text-align: left;
    }

    .pending-work-grid-wrapper .ui-jqgrid-btable td[aria-describedby$="_qualification"],
    .pending-work-grid-wrapper .ui-jqgrid-btable td[aria-describedby$="_sent_date"],
    .pending-work-grid-wrapper .ui-jqgrid-btable td[aria-describedby$="_qualificator_id"],
    .pending-work-grid-wrapper .ui-jqgrid-btable td[aria-describedby$="_correction"],
    .pending-work-grid-wrapper .ui-jqgrid-btable td[aria-describedby$="_actions"] {
        text-align: center;
    }

    .pending-work-grid-wrapper .ui-jqgrid-btable tr:hover td {
        background: #f9fafb;
    }

    .pending-work-grid-wrapper .ui-jqgrid-pager {
        min-height: 52px;
        border-color: #e5e7eb;
        background: #ffffff;
    }

    .pending-work-grid-wrapper .ui-pg-table td {
        padding: 0.25rem;
    }

    .pending-work-grid-wrapper .work-action {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .pending-work-grid-wrapper .work-action a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .pending-work-grid-wrapper .ch-tool-icon {
        font-size: 1.125rem;
    }

    @media (max-width: 1024px) {
        .pending-work-grid-wrapper {
            overflow-x: auto;
        }

        .pending-work-grid-wrapper .ui-jqgrid,
        .pending-work-grid-wrapper .ui-jqgrid-view,
        .pending-work-grid-wrapper .ui-jqgrid-hdiv,
        .pending-work-grid-wrapper .ui-jqgrid-bdiv,
        .pending-work-grid-wrapper .ui-jqgrid-pager {
            min-width: 980px;
        }
    }
</style>
HTML;

function pending_work_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function pending_work_selected(int $currentValue, int $selectedValue): string
{
    return $currentValue === $selectedValue ? ' selected' : '';
}

$selectedCourseId = isset($_GET['course']) ? (int) $_GET['course'] : 0;
$selectedStatus = isset($_GET['status']) ? (int) $_GET['status'] : 1;

if (!in_array($selectedStatus, [1, 2, 3], true)) {
    $selectedStatus = 1;
}

$columns = [
    get_lang('Course'),
    get_lang('Assignment'),
    get_lang('Student'),
    get_lang('Submission'),
    get_lang('Score'),
    get_lang('Sent date'),
    get_lang('Status'),
    get_lang('Correction'),
    get_lang('Actions'),
];

$column_model = [
    [
        'name' => 'course',
        'index' => 'course',
        'width' => '145',
        'align' => 'left',
        'search' => 'false',
        'sortable' => 'true',
    ],
    [
        'name' => 'work_name',
        'index' => 'work_name',
        'width' => '180',
        'align' => 'left',
        'search' => 'false',
        'sortable' => 'true',
    ],
    [
        'name' => 'fullname',
        'index' => 'fullname',
        'width' => '145',
        'align' => 'left',
        'search' => 'true',
        'sortable' => 'true',
    ],
    [
        'name' => 'title',
        'index' => 'title',
        'width' => '185',
        'align' => 'left',
        'search' => 'false',
        'sortable' => 'true',
    ],
    [
        'name' => 'qualification',
        'index' => 'qualification',
        'width' => '70',
        'align' => 'center',
        'search' => 'true',
        'sortable' => 'true',
    ],
    [
        'name' => 'sent_date',
        'index' => 'sent_date',
        'width' => '150',
        'align' => 'center',
        'search' => 'true',
        'sortable' => 'true',
    ],
    [
        'name' => 'qualificator_id',
        'index' => 'qualificator_id',
        'width' => '120',
        'align' => 'center',
        'search' => 'true',
        'sortable' => 'true',
    ],
    [
        'name' => 'correction',
        'index' => 'correction',
        'width' => '105',
        'align' => 'center',
        'search' => 'false',
        'sortable' => 'false',
        'title' => 'false',
    ],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '90',
        'align' => 'center',
        'search' => 'false',
        'sortable' => 'false',
        'title' => 'false',
    ],
];

$extra_params = [
    'autowidth' => 'true',
    'height' => 'auto',
    'shrinkToFit' => 'true',
    'forceFit' => 'true',
    'rowNum' => '20',
    'sortname' => 'sent_date',
    'sortorder' => 'desc',
    'sortable' => 'false',
    'multiselect' => 'false',
    'viewrecords' => 'true',
];

$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_work_pending_list&type='.$type;
$showAllSessionAssignments = (
    'true' === api_get_setting('session.assignment_base_course_teacher_access_to_all_session') &&
    !$isPlatformAdmin &&
    api_is_teacher()
);

if ($showAllSessionAssignments) {
    $url .= '&include_all_sessions=1';
}

if ($selectedCourseId > 0) {
    $url .= '&course='.$selectedCourseId;
}

if ($selectedStatus > 0) {
    $url .= '&status='.$selectedStatus;
}

$deleteUrl = '';

$courseRows = $isPlatformAdmin
    ? CourseManager::get_course_list()
    : CourseManager::get_courses_list_by_user_id($userId, false, false, false);

$courseOptions = [
    0 => get_lang('All'),
];

foreach ($courseRows as $course) {
    $courseId = (int) ($course['real_id'] ?? $course['id'] ?? $course['c_id'] ?? 0);
    $courseTitle = trim((string) ($course['title'] ?? $course['name'] ?? $course['code'] ?? ''));

    if ($courseId <= 0 || '' === $courseTitle) {
        continue;
    }

    $courseOptions[$courseId] = $courseTitle;
}

$statusOptions = [
    1 => get_lang('All'),
    2 => get_lang('Not reviewed'),
    3 => get_lang('Reviewed'),
];

Display::display_header(get_lang('Assignments'));
?>

    <script>
        $(function() {
            <?php echo Display::grid_js('results', $url, $columns, $column_model, $extra_params); ?>

            const resizePendingWorkGrid = function () {
                const grid = $("#results")
                const wrapper = $(".pending-work-grid-wrapper")
                const wrapperWidth = Math.floor(wrapper.innerWidth())

                if (wrapperWidth <= 0 || !grid.length) {
                    return
                }

                grid.jqGrid("setGridWidth", wrapperWidth, true)

                wrapper
                    .find(".ui-jqgrid, .ui-jqgrid-view, .ui-jqgrid-hdiv, .ui-jqgrid-bdiv, .ui-jqgrid-pager")
                    .css("width", wrapperWidth + "px")

                wrapper
                    .find(".ui-jqgrid-htable, .ui-jqgrid-btable")
                    .css("width", "100%")
            }

            $("#results").jqGrid(
                "navGrid",
                "#results_pager",
                { edit: false, add: false, search: false, del: false },
                { height: 280, reloadAfterSubmit: false },
                { height: 280, reloadAfterSubmit: false },
                { reloadAfterSubmit: false, url: <?php echo json_encode($deleteUrl); ?> },
                { width: 500 }
            )

            resizePendingWorkGrid()

            setTimeout(resizePendingWorkGrid, 100)
            setTimeout(resizePendingWorkGrid, 300)

            $("#results").on("jqGridLoadComplete jqGridAfterLoadComplete", function () {
                resizePendingWorkGrid()
            })
        })
    </script>

    <div class="mx-auto w-full max-w-[1600px] px-4 py-6">
        <div class="space-y-6">
            <section class="rounded-xl border border-gray-25 bg-white p-6 shadow-sm">
                <h1 class="mb-3 text-2xl font-semibold text-gray-90">
                    <?php echo get_lang("Student's assignments to be corrected"); ?>
                </h1>

                <p class="max-w-5xl text-sm leading-6 text-gray-70">
                    <?php echo get_lang('You will find below all the work that have been submitted by students in one of your course (it could be in the base course or in a course in a session). You can filter the list selecting a specific course or a work status.'); ?>
                </p>
            </section>

            <?php if (!empty($courseRows) || $isPlatformAdmin) { ?>
                <section class="rounded-xl border border-gray-25 bg-white p-6 shadow-sm">
                    <form
                        action="<?php echo pending_work_escape(api_get_self()); ?>"
                        class="grid grid-cols-1 gap-4 md:grid-cols-[minmax(0,1fr)_220px] lg:grid-cols-[minmax(0,1fr)_220px_auto]"
                        method="get"
                    >
                        <div class="flex flex-col gap-1">
                            <label
                                class="text-sm font-medium text-gray-70"
                                for="pending-course"
                            >
                                <?php echo get_lang('Course'); ?>
                            </label>

                            <select
                                class="h-11 w-full rounded-lg border border-gray-30 bg-white px-3 text-sm text-gray-90 shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                                id="pending-course"
                                name="course"
                            >
                                <?php foreach ($courseOptions as $courseId => $courseTitle) { ?>
                                    <option
                                        value="<?php echo (int) $courseId; ?>"
                                        <?php echo pending_work_selected((int) $courseId, $selectedCourseId); ?>
                                    >
                                        <?php echo pending_work_escape((string) $courseTitle); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label
                                class="text-sm font-medium text-gray-70"
                                for="pending-status"
                            >
                                <?php echo get_lang('Status'); ?>
                            </label>

                            <select
                                class="h-11 w-full rounded-lg border border-gray-30 bg-white px-3 text-sm text-gray-90 shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                                id="pending-status"
                                name="status"
                            >
                                <?php foreach ($statusOptions as $statusId => $statusLabel) { ?>
                                    <option
                                        value="<?php echo (int) $statusId; ?>"
                                        <?php echo pending_work_selected((int) $statusId, $selectedStatus); ?>
                                    >
                                        <?php echo pending_work_escape((string) $statusLabel); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="flex items-end justify-start lg:justify-end">
                            <button
                                class="inline-flex h-11 w-auto min-w-[120px] items-center justify-center gap-2 rounded-lg bg-primary px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                                type="submit"
                            >
                            <span
                                aria-hidden="true"
                                class="mdi mdi-magnify text-lg"
                            ></span>
                                <span><?php echo get_lang('Search'); ?></span>
                            </button>
                        </div>
                    </form>
                </section>

                <section class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-90">
                                <?php echo get_lang('Pending assignments'); ?>
                            </h2>
                            <p class="text-sm text-gray-50">
                                <?php echo get_lang('Use the filters above to narrow the list.'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="pending-work-grid-wrapper">
                        <?php echo Display::grid_html('results'); ?>
                    </div>
                </section>
            <?php } else { ?>
                <section class="rounded-xl border border-yellow-200 bg-yellow-50 p-6 text-sm text-yellow-900 shadow-sm">
                    <?php echo get_lang("This user isn't subscribed in a course"); ?>
                </section>
            <?php } ?>
        </div>
    </div>

<?php

Display::display_footer();
