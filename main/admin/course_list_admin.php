<?php

/* For licensing terms, see /license.txt */

/**
 * This script shows a list of courses and allows searching for courses codes
 * and names.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();
$sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : null;
$addTeacherColumn = true;

/**
 * Get the number of courses which will be displayed.
 *
 * @throws Exception
 *
 * @return int The number of matching courses
 */
function get_number_of_courses()
{
    return get_course_data(0, 0, 0, 0, null, true);
}

/**
 * Get course data to display.
 *
 * @param int    $from
 * @param int    $number_of_items
 * @param int    $column
 * @param string $direction
 *
 * @throws Exception
 *
 * @return array
 */
function get_course_data($from, $number_of_items, $column, $direction, $dataFunctions = [], $getCount = false)
{
    $addTeacherColumn = true;
    $table = Database::get_main_table(TABLE_MAIN_COURSE);

    $from = (int) $from;
    $number_of_items = (int) $number_of_items;
    $column = (int) $column;

    if (!in_array(strtolower($direction), ['asc', 'desc'])) {
        $direction = 'desc';
    }

    $teachers = '';
    if ($addTeacherColumn) {
        $teachers = " GROUP_CONCAT(cu.user_id SEPARATOR ',') as col4, ";
    }
    $select = "SELECT
                code AS col0,
                title AS col1,
                creation_date AS col2,
                $teachers
                visibility,
                directory,
                visual_code,
                course.code,
                course.id ";

    if ($getCount) {
        $select = 'SELECT COUNT(DISTINCT(course.id)) as count ';
    }

    $sql = "$select FROM $table course";
    if (api_is_multiple_url_enabled()) {
        $access_url_rel_course_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $sql .= " INNER JOIN $access_url_rel_course_table url_rel_course
                  ON (course.id = url_rel_course.c_id)";
    }

    $tableCourseRelUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $sql .= "
            LEFT JOIN $tableCourseRelUser cu
            ON (course.id = cu.c_id AND cu.status = ".COURSEMANAGER." )
        ";

    $sql .= ' WHERE 1=1 ';
    if (isset($_GET['keyword'])) {
        $keyword = Database::escape_string("%".trim($_GET['keyword'])."%");
        $sql .= " AND  (
            title LIKE '".$keyword."' OR
            code LIKE '".$keyword."' OR
            visual_code LIKE '".$keyword."'
        )
        ";
    } elseif (isset($_GET['keyword_code'])) {
        $keyword_code = Database::escape_string("%".$_GET['keyword_code']."%");
        $keyword_title = Database::escape_string("%".$_GET['keyword_title']."%");
        $keyword_category = isset($_GET['keyword_category'])
            ? Database::escape_string("%".$_GET['keyword_category']."%")
            : null;
        $keyword_language = Database::escape_string("%".$_GET['keyword_language']."%");
        $keyword_visibility = Database::escape_string("%".$_GET['keyword_visibility']."%");
        $keyword_subscribe = Database::escape_string($_GET['keyword_subscribe']);
        $keyword_unsubscribe = Database::escape_string($_GET['keyword_unsubscribe']);

        $sql .= " AND
                title LIKE '".$keyword_title."' AND
                (code LIKE '".$keyword_code."' OR visual_code LIKE '".$keyword_code."') AND
                course_language LIKE '".$keyword_language."' AND
                visibility LIKE '".$keyword_visibility."' AND
                subscribe LIKE '".$keyword_subscribe."' AND
                unsubscribe LIKE '".$keyword_unsubscribe."'";

        if (!empty($keyword_category)) {
            $sql .= " AND category_code LIKE '".$keyword_category."' ";
        }
    }

    // Adding the filter to see the user's only of the current access_url.
    if (api_is_multiple_url_enabled()) {
        $sql .= " AND url_rel_course.access_url_id = ".api_get_current_access_url_id();
    }

    if ($addTeacherColumn) {
        $teachers = isset($_GET['course_teachers']) ? $_GET['course_teachers'] : [];
        if (!empty($teachers)) {
            $teachers = array_map('intval', $teachers);
            $addNull = '';
            foreach ($teachers as $key => $teacherId) {
                if (0 === $teacherId) {
                    $addNull = 'OR cu.user_id IS NULL ';
                    unset($key);
                }
            }
            $sql .= ' AND ( cu.user_id IN ("'.implode('", "', $teachers).'") '.$addNull.' ) ';
        }

        if (false === $getCount) {
            $sql .= " GROUP BY course.id ";
        }
    }

    if ($getCount) {
        $res = Database::query($sql);
        $row = Database::fetch_array($res);
        if ($row) {
            return (int) $row['count'];
        }

        return 0;
    }
    $sql .= " ORDER BY col$column $direction ";
    $sql .= " LIMIT $from, $number_of_items";

    $res = Database::query($sql);
    $courses = [];
    $path = api_get_path(WEB_CODE_PATH);
    $coursePath = api_get_path(WEB_COURSE_PATH);

    $icon = Display::return_icon('teacher.png', get_lang('Teacher'), [], ICON_SIZE_TINY);

    while ($course = Database::fetch_array($res)) {
        $courseId = $course['id'];
        $courseCode = $course['code'];

        // Place colour icons in front of courses.
        $showVisualCode = $course['visual_code'] != $courseCode ? Display::label($course['visual_code'], 'info') : null;
        $course[1] = get_course_visibility_icon($course['visibility']).PHP_EOL
            .Display::url(Security::remove_XSS($course[1]), $coursePath.$course['directory'].'/index.php').PHP_EOL
            .$showVisualCode;
        $course[5] = $course[5] == SUBSCRIBE_ALLOWED ? get_lang('Yes') : get_lang('No');
        $course[6] = $course[6] == UNSUBSCRIBE_ALLOWED ? get_lang('Yes') : get_lang('No');

        $actions = [];
        $actions[] = Display::url(
            Display::return_icon('info2.png', get_lang('Info')),
            "course_information.php?code=$courseCode"
        );
        /*$actions[] = Display::url(
            Display::return_icon('course_home.png', get_lang('CourseHomepage')),
            $coursePath.$course['directory'].'/index.php'
        );*/
        $actions[] = Display::url(
            Display::return_icon('statistics.png', get_lang('Tracking')),
            $path.'tracking/courseLog.php?'.api_get_cidreq_params($courseCode)
        );
        $actions[] = Display::url(
            Display::return_icon('edit.png', get_lang('Edit')),
            $path.'admin/course_edit.php?id='.$courseId
        );
        $actions[] = Display::url(
            Display::return_icon('backup.png', get_lang('CreateBackup')),
            $path.'coursecopy/create_backup.php?'.api_get_cidreq_params($courseCode)
        );
        $actions[] = Display::url(
            Display::return_icon('delete.png', get_lang('Delete')),
            $path.'admin/course_list_admin.php?'.http_build_query([
                  'delete_course' => $courseCode,
                    'sec_token' => Security::getTokenFromSession(),
                ]),
            [
                'onclick' => "javascript: if (!confirm('"
                    .addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."')) return false;",
            ]
        );

        $course['creation_date'] = api_get_local_time($course['col2']);
        $lastAccessLocalTime = '';
        $lastAccess = Tracking::getLastConnectionDateByCourse($courseId);
        if ($lastAccess) {
            $lastAccessLocalTime = api_get_local_time($lastAccess);
        }

        $courseItem = [
            $course[0],
            $course[1],
            $course['creation_date'],
            $lastAccessLocalTime,
        ];

        if ($addTeacherColumn) {
            $teacherIdList = array_filter(explode(',', $course['col4']));
            $teacherList = [];
            if (!empty($teacherIdList)) {
                foreach ($teacherIdList as $teacherId) {
                    $userInfo = api_get_user_info($teacherId);
                    if ($userInfo) {
                        $teacherList[] = $userInfo['complete_name'];
                    }
                }
            }
            $courseItem[] = '<ul class="list-inline"><li>'
                ."$icon ".implode("</li><li>$icon ", $teacherList)
                .'</li></ul>';
        }
        $courseItem[] = implode(PHP_EOL, $actions);
        $courses[] = $courseItem;
    }

    return $courses;
}

/**
 * Return an icon representing the visibility of the course.
 *
 * @param string $visibility
 *
 * @return string
 */
function get_course_visibility_icon($visibility)
{
    $style = 'margin-bottom:0;margin-right:5px;';
    switch ($visibility) {
        case 0:
            return Display::return_icon(
                'bullet_red.png',
                get_lang('CourseVisibilityClosed'),
                ['style' => $style]
            );
            break;
        case 1:
            return Display::return_icon(
                'bullet_orange.png',
                get_lang('Private'),
                ['style' => $style]
            );
            break;
        case 2:
            return Display::return_icon(
                'bullet_green.png',
                get_lang('OpenToThePlatform'),
                ['style' => $style]
            );
            break;
        case 3:
            return Display::return_icon(
                'bullet_blue.png',
                get_lang('OpenToTheWorld'),
                ['style' => $style]
            );
            break;
        case 4:
            return Display::return_icon(
                'bullet_grey.png',
                get_lang('CourseVisibilityHidden'),
                ['style' => $style]
            );
            break;
        default:
            return '';
    }
}

if (isset($_POST['action']) && Security::check_token('get')) {
    switch ($_POST['action']) {
        // Delete selected courses
        case 'delete_courses':
            if (!empty($_POST['course'])) {
                $course_codes = $_POST['course'];
                if (count($course_codes) > 0) {
                    foreach ($course_codes as $course_code) {
                        CourseManager::delete_course($course_code);
                    }
                }

                Display::addFlash(Display::return_message(get_lang('Deleted')));
            }
            break;
    }
}
$content = '';
$message = '';
$actions = '';

if (isset($_GET['search']) && $_GET['search'] === 'advanced') {
    // Get all course categories
    $interbreadcrumb[] = [
        'url' => 'index.php',
        'name' => get_lang('PlatformAdmin'),
    ];
    $interbreadcrumb[] = [
        'url' => 'course_list_admin.php',
        'name' => get_lang('CourseList'),
    ];
    $tool_name = get_lang('SearchACourse');
    $form = new FormValidator('advanced_course_search', 'get');
    $form->addElement('header', $tool_name);
    $form->addText('keyword_code', get_lang('CourseCode'), false);
    $form->addText('keyword_title', get_lang('Title'), false);

    // Category code
    $url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_category';

    $form->addElement(
        'select_ajax',
        'keyword_category',
        get_lang('CourseFaculty'),
        null,
        [
            'url' => $url,
        ]
    );

    $el = $form->addSelectLanguage('keyword_language', get_lang('CourseLanguage'));
    $el->addOption(get_lang('All'), '%');

    if ($addTeacherColumn) {
        $form->addSelectAjax(
            'course_teachers',
            get_lang('CourseTeachers'),
            [0 => get_lang('None')],
            [
                'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=teacher_to_basis_course',
                'id' => 'course_teachers',
                'multiple' => 'multiple',
            ]
        );
        $form->addLabel('', '<button id="set_none_teacher" class="btn ">'.get_lang('None').'</button>');
    }

    $form->addElement('radio', 'keyword_visibility', get_lang('CourseAccess'), get_lang('OpenToTheWorld'), COURSE_VISIBILITY_OPEN_WORLD);
    $form->addElement('radio', 'keyword_visibility', null, get_lang('OpenToThePlatform'), COURSE_VISIBILITY_OPEN_PLATFORM);
    $form->addElement('radio', 'keyword_visibility', null, get_lang('Private'), COURSE_VISIBILITY_REGISTERED);
    $form->addElement('radio', 'keyword_visibility', null, get_lang('CourseVisibilityClosed'), COURSE_VISIBILITY_CLOSED);
    $form->addElement('radio', 'keyword_visibility', null, get_lang('CourseVisibilityHidden'), COURSE_VISIBILITY_HIDDEN);
    $form->addElement('radio', 'keyword_visibility', null, get_lang('All'), '%');
    $form->addElement('radio', 'keyword_subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
    $form->addElement('radio', 'keyword_subscribe', null, get_lang('Denied'), 0);
    $form->addElement('radio', 'keyword_subscribe', null, get_lang('All'), '%');
    $form->addElement('radio', 'keyword_unsubscribe', get_lang('Unsubscription'), get_lang('AllowedToUnsubscribe'), 1);
    $form->addElement('radio', 'keyword_unsubscribe', null, get_lang('NotAllowedToUnsubscribe'), 0);
    $form->addElement('radio', 'keyword_unsubscribe', null, get_lang('All'), '%');
    $form->addButtonSearch(get_lang('SearchCourse'));
    $defaults['keyword_language'] = '%';
    $defaults['keyword_visibility'] = '%';
    $defaults['keyword_subscribe'] = '%';
    $defaults['keyword_unsubscribe'] = '%';
    $form->setDefaults($defaults);
    $content .= $form->returnForm();
} else {
    $interbreadcrumb[] = [
        'url' => 'index.php',
        'name' => get_lang('PlatformAdmin'),
    ];
    $tool_name = get_lang('CourseList');
    if (isset($_GET['delete_course']) && Security::check_token('get')) {
        $result = CourseManager::delete_course($_GET['delete_course']);
        if ($result) {
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        }
    }
    // Create a search-box
    $form = new FormValidator(
        'search_simple',
        'get',
        '',
        '',
        [],
        FormValidator::LAYOUT_INLINE
    );
    $form->addElement(
        'text',
        'keyword',
        null,
        ['id' => 'course-search-keyword', 'aria-label' => get_lang('SearchCourse')]
    );
    $form->addButtonSearch(get_lang('SearchCourse'));
    $advanced = '<a class="btn btn-default" href="'.api_get_path(WEB_CODE_PATH).'admin/course_list_admin.php?search=advanced">
        <em class="fa fa-search"></em> '.
        get_lang('AdvancedSearch').'</a>';

    // Create a filter by session
    $sessionFilter = new FormValidator(
        'course_filter',
        'get',
        '',
        '',
        [],
        FormValidator::LAYOUT_INLINE
    );

    $courseListUrl = api_get_self();
    $actions1 = Display::url(
        Display::return_icon(
            'new_course.png',
            get_lang('AddCourse'),
            [],
            ICON_SIZE_MEDIUM
        ),
        api_get_path(WEB_CODE_PATH).'admin/course_add.php'
    );

    if (api_get_setting('course_validation') === 'true') {
        $actions1 .= Display::url(
            Display::return_icon(
                'course_request_pending.png',
                get_lang('ReviewCourseRequests'),
                [],
                ICON_SIZE_MEDIUM
            ),
            api_get_path(WEB_CODE_PATH).'admin/course_request_review.php'
        );
    }

    $actions2 = $form->returnForm();
    //$actions3 = $sessionFilter->returnForm();
    $actions4 = $advanced;

    $actions = Display::toolbarAction(
        'toolbar',
        [$actions1, $actions2, $actions4],
        [2, 4, 3, 3]
    );

    // Create a sortable table with the course data
    $table = new SortableTable(
        'course_list_admin',
        'get_number_of_courses',
        'get_course_data',
        1,
        20,
        'ASC',
        'course_list_admin'
    );

    $parameters = [];
    $parameters['sec_token'] = Security::get_token();
    if (isset($_GET['keyword'])) {
        $parameters = ['keyword' => Security::remove_XSS($_GET['keyword'])];
    } elseif (isset($_GET['keyword_code'])) {
        $parameters['keyword_code'] = Security::remove_XSS($_GET['keyword_code']);
        $parameters['keyword_title'] = Security::remove_XSS($_GET['keyword_title']);
        if (isset($_GET['keyword_category'])) {
            $parameters['keyword_category'] = Security::remove_XSS($_GET['keyword_category']);
        }
        $parameters['keyword_language'] = Security::remove_XSS($_GET['keyword_language']);
        $parameters['keyword_visibility'] = Security::remove_XSS($_GET['keyword_visibility']);
        $parameters['keyword_subscribe'] = Security::remove_XSS($_GET['keyword_subscribe']);
        $parameters['keyword_unsubscribe'] = Security::remove_XSS($_GET['keyword_unsubscribe']);
    }

    if (isset($_GET['course_teachers'])) {
        $parsed = array_map('intval', $_GET['course_teachers']);
        $parameters["course_teachers"] = '';
        foreach ($parsed as $key => $teacherId) {
            $parameters["course_teachers[$key]"] = $teacherId;
        }
    }

    $table->set_additional_parameters($parameters);
    $column = 0;
    $table->set_header($column++, '', false, 'width="8px"');
    $table->set_header($column++, get_lang('Title'), true, null, ['class' => 'title']);
    $table->set_header($column++, get_lang('CreationDate'), true, 'width="70px"');
    $table->set_header($column++, get_lang('LatestLoginInCourse'), false, 'width="70px"');
    //$table->set_header($column++, get_lang('Category'));
    //$table->set_header($column++, get_lang('SubscriptionAllowed'), true, 'width="60px"');
    //$table->set_header($column++, get_lang('UnsubscriptionAllowed'), false, 'width="50px"');
    if ($addTeacherColumn) {
        $table->set_header($column++, get_lang('Teachers'), true, ['style' => 'width:350px;']);
    }
    $table->set_header(
        $column++,
        get_lang('Action'),
        false,
        null,
        ['class' => 'td_actions', 'style' => 'width:145px;']
    );
    $table->set_form_actions(
        ['delete_courses' => get_lang('DeleteCourse')],
        'course'
    );
    $tab = CourseManager::getCourseListTabs('admin');
    $content .= $tab.$table->return_table();
}

$htmlHeadXtra[] = '
<script>
$(function() {
    $("#set_none_teacher").on("click", function () {
        $("#course_teachers").val("0").trigger("change");

        return false;
    });
});
</script>';

$tpl = new Template($tool_name);
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
