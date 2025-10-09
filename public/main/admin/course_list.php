<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

/*
 * This script shows a list of courses and allows searching for courses codes
 * and names.
 */

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\CatalogueCourseRelAccessUrlRelUsergroup;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\StateIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\CatalogueCourseRelAccessUrlRelUsergroupRepository;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();
$sessionId = $_GET['session_id'] ?? null;

/**
 * Get the number of courses which will be displayed.
 *
 * @return int The number of matching courses
 *
 * @throws Exception
 */
function get_number_of_courses(): int
{
    return get_course_data(0, 0, 0, 'ASC', [], true);
}

/**
 * Get course data to display.
 *
 * @throws Doctrine\DBAL\Exception
 * @throws Exception
 */
function get_course_data(
    int $from,
    int $number_of_items,
    int $column,
    string $direction,
    array $dataFunctions = [],
    bool $getCount = false
): int|array {
    $course_table = Database::get_main_table(TABLE_MAIN_COURSE);

    if (!in_array(strtolower($direction), ['asc', 'desc'])) {
        $direction = 'desc';
    }

    $tblCourseCategory = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $tblCourseRelCategory = Database::get_main_table(TABLE_MAIN_COURSE_REL_CATEGORY);

    $select = 'SELECT
                course.code AS col0,
                course.title AS col1,
                course.code AS col2,
                course_language AS col3,
                subscribe AS col5,
                unsubscribe AS col6,
                course.code AS col7,
                course.visibility AS col8,
                directory as col9,
                visual_code,
                directory,
                course.id';

    if ($getCount) {
        $select = 'SELECT COUNT(DISTINCT(course.id)) as count ';
    }

    $sql = "$select FROM $course_table course ";

    if (!empty($_GET['keyword_category'])) {
        $sql .= "INNER JOIN $tblCourseRelCategory course_rel_category ON course.id = course_rel_category.course_id
            INNER JOIN $tblCourseCategory category ON course_rel_category.course_category_id = category.id ";
    }

    if ((api_is_platform_admin() || api_is_session_admin())
        && api_is_multiple_url_enabled() && -1 != api_get_current_access_url_id()
    ) {
        $access_url_rel_course_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $sql .= " INNER JOIN $access_url_rel_course_table url_rel_course
                 ON (course.id = url_rel_course.c_id)";
    }

    if (!empty($_GET['session_id'])) {
        $session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $session = Database::get_main_table(TABLE_MAIN_SESSION);

        $sql .= " INNER JOIN $session_rel_course r ON course.id = r.c_id
            INNER JOIN $session s ON r.session_id = s.id ";
    }

    if (isset($_GET['keyword'])) {
        $keyword = Database::escape_string('%'.trim($_GET['keyword']).'%');
        $sql .= " WHERE (
            course.title LIKE '".$keyword."' OR
            course.code LIKE '".$keyword."' OR
            visual_code LIKE '".$keyword."'
        )
        ";
    } elseif (isset($_GET['keyword_code'])) {
        $keyword_code = Database::escape_string('%'.$_GET['keyword_code'].'%');
        $keyword_title = Database::escape_string('%'.$_GET['keyword_title'].'%');
        $keyword_category = isset($_GET['keyword_category'])
            ? Database::escape_string($_GET['keyword_category'])
            : null;
        $keyword_language = Database::escape_string('%'.$_GET['keyword_language'].'%');
        $keyword_visibility = Database::escape_string('%'.$_GET['keyword_visibility'].'%');
        $keyword_subscribe = Database::escape_string($_GET['keyword_subscribe']);
        $keyword_unsubscribe = Database::escape_string($_GET['keyword_unsubscribe']);

        $sql .= " WHERE
                (course.code LIKE '".$keyword_code."' OR visual_code LIKE '".$keyword_code."') AND
                course.title LIKE '".$keyword_title."' AND
                course_language LIKE '".$keyword_language."' AND
                visibility LIKE '".$keyword_visibility."' AND
                subscribe LIKE '".$keyword_subscribe."' AND
                unsubscribe LIKE '".$keyword_unsubscribe."'";

        if (!empty($keyword_category)) {
            $sql .= ' AND category.id = '.$keyword_category.' ';
        }
    }

    // Adding the filter to see the user's only of the current access_url.
    if ((api_is_platform_admin() || api_is_session_admin())
        && api_is_multiple_url_enabled() && -1 != api_get_current_access_url_id()
    ) {
        $sql .= ' AND url_rel_course.access_url_id='.api_get_current_access_url_id();
    }

    if (!empty($_GET['session_id'])) {
        $sessionId = (int) $_GET['session_id'];
        $sql .= ' AND s.id = '.$sessionId.' ';
    }

    if ($getCount) {
        $res = Database::query($sql);
        $row = Database::fetch_array($res);
        if ($row) {
            return (int) $row['count'];
        }

        return 0;
    }

    $sql .= ' GROUP BY course.code';
    $sql .= " ORDER BY col$column $direction ";
    $sql .= " LIMIT $from, $number_of_items";

    $res = Database::query($sql);
    $courses = [];
    $languages = api_get_languages();

    $path = api_get_path(WEB_CODE_PATH);

    while ($course = Database::fetch_array($res)) {
        $courseInfo = api_get_course_info_by_id($course['id']);

        // get categories
        $sqlCategoriesByCourseId = "SELECT category.title FROM $tblCourseCategory category
            INNER JOIN $tblCourseRelCategory course_rel_category ON category.id = course_rel_category.course_category_id
            WHERE course_rel_category.course_id = ".$course['id'];
        $resultCategories = Database::query($sqlCategoriesByCourseId);
        $categories = [];

        while ($category = Database::fetch_array($resultCategories)) {
            $categories[] = $category['title'];
        }

        // Place colour icons in front of courses.
        $show_visual_code = $course['visual_code'] != $course['col2'] ? Display::label($course['visual_code'], 'info') : null;
        $course['col1'] = get_course_visibility_icon($courseInfo['visibility']).\PHP_EOL
            .Display::url(Security::remove_XSS($course['col1']), $courseInfo['course_public_url']).\PHP_EOL
            .$show_visual_code;
        $course['col5'] = SUBSCRIBE_ALLOWED == $course['col5'] ? get_lang('Yes') : get_lang('No');
        $course['col6'] = UNSUBSCRIBE_ALLOWED == $course['col6'] ? get_lang('Yes') : get_lang('No');

        $courseId = $course['id'];

        $actions = [];
        $actions[] = Display::url(
            Display::getMdiIcon(
                ActionIcon::INFORMATION,
                'ch-tool-icon',
                null,
                ICON_SIZE_SMALL,
                get_lang('Information')
            ),
            "course_information.php?id=$courseId"
        );
        $actions[] = Display::url(
            Display::getMdiIcon(
                ToolIcon::COURSE_HOME,
                'ch-tool-icon',
                null,
                ICON_SIZE_SMALL,
                get_lang('Course home')
            ),
            $courseInfo['course_public_url']
        );
        $actions[] = Display::url(
            Display::getMdiIcon(
                ToolIcon::TRACKING,
                'ch-tool-icon',
                null,
                ICON_SIZE_SMALL,
                get_lang('Reporting')
            ),
            $path.'tracking/courseLog.php?'.api_get_cidreq_params($courseId)
        );
        $actions[] = Display::url(
            Display::getMdiIcon(
                ActionIcon::EDIT,
                'ch-tool-icon',
                null,
                ICON_SIZE_SMALL,
                get_lang('Edit')
            ),
            $path.'admin/course_edit.php?id='.$courseId
        );
        $actions[] = Display::url(
            Display::getMdiIcon(
                ActionIcon::TAKE_BACKUP,
                'ch-tool-icon',
                null,
                ICON_SIZE_SMALL,
                get_lang('Create a backup')
            ),
            $path.'course_copy/create_backup.php?'.api_get_cidreq_params($courseId)
        );
        // Delete course action
        $actions[] = '
        <form method="post" style="display:inline;" onsubmit="return confirm(\'' . 
            addslashes(api_htmlentities(get_lang('Please confirm your choice'), \ENT_QUOTES)) . 
            '\');">
            <input type="hidden" name="action" value="delete_course">
            <input type="hidden" name="course_code" value="' . $course['col0'] . '">
            <input type="hidden" name="sec_token" value="' . Security::getTokenFromSession() . '">
            <button type="submit" class="btn btn-link p-0 text-decoration-none cursor-pointer" title="' . get_lang('Delete') . '">
                ' . Display::getMdiIcon(
                    ActionIcon::DELETE,
                    'ch-tool-icon',
                    null,
                    ICON_SIZE_SMALL,
                    get_lang('Delete')
                ) . '
            </button>
        </form>';

        $em = Database::getManager();
        /** @var CatalogueCourseRelAccessUrlRelUsergroupRepository $repo */
        $repo = $em->getRepository(CatalogueCourseRelAccessUrlRelUsergroup::class);
        $record = $repo->findOneBy([
            'course' => $courseId,
            'accessUrl' => api_get_current_access_url_id(),
            'usergroup' => null,
        ]);

        $isInCatalogue = null !== $record;

        $actions[] = '
        <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="toggle_catalogue">
            <input type="hidden" name="course_id" value="' . $course['id'] . '">
            <input type="hidden" name="sec_token" value="' . Security::getTokenFromSession() . '">
            <button type="submit" class="btn btn-link p-0 text-decoration-none cursor-pointer" title="' . 
                ($isInCatalogue ? get_lang('Remove from catalogue') : get_lang('Add to catalogue')) . '">
                ' . Display::getMdiIcon(
                    $isInCatalogue ? StateIcon::CATALOGUE_OFF : StateIcon::CATALOGUE_ON,
                    'ch-tool-icon',
                    null,
                    ICON_SIZE_SMALL,
                    $isInCatalogue ? get_lang('Remove from catalogue') : get_lang('Add to catalogue')
                ) . '
            </button>
        </form>';

        $courseItem = [
            $course['col0'],
            $course['col1'],
            $course['col2'],
            $languages[$course['col3']] ?? $course['col3'],
            implode(', ', $categories),
            $course['col5'],
            $course['col6'],
            implode(\PHP_EOL, $actions),
        ];
        $courses[] = $courseItem;
    }

    return $courses;
}

/**
 * Return an icon representing the visibility of the course.
 *
 * @param int $visibility
 */
function get_course_visibility_icon(int $visibility): string
{
    $style = 'margin-bottom:0;margin-right:5px;';

    return match ($visibility) {
        0 => Display::getMdiIcon(
            StateIcon::CLOSED_VISIBILITY,
            'ch-tool-icon',
            null,
            22,
            get_lang('Closed - the course is only accessible to the teachers')
        ),
        1 => Display::getMdiIcon(
            StateIcon::PRIVATE_VISIBILITY,
            'ch-tool-icon',
            null,
            22,
            get_lang(
                'Private'
            )
        ),
        2 => Display::getMdiIcon(
            StateIcon::OPEN_VISIBILITY,
            'ch-tool-icon',
            null,
            22,
            get_lang('Open - access allowed for users registered on the platform')
        ),
        3 => Display::getMdiIcon(
            StateIcon::PUBLIC_VISIBILITY,
            'ch-tool-icon',
            null,
            22,
            get_lang('Public - access allowed for the whole world')
        ),
        4 => Display::getMdiIcon(
            StateIcon::HIDDEN_VISIBILITY,
            'ch-tool-icon',
            null,
            22,
            get_lang('Hidden - Completely hidden to all users except the administrators')
        ),
        default => '',
    };
}

if (isset($_POST['action']) && Security::check_token('post')) {
    // Delete selected courses
    if ('delete_courses' == $_POST['action']) {
        if (!empty($_POST['course'])) {
            $course_codes = $_POST['course'];
            if (count($course_codes) > 0) {
                foreach ($course_codes as $course_code) {
                    CourseManager::delete_course($course_code);
                }
            }

            Display::addFlash(Display::return_message(get_lang('Deleted')));
        }
    }

    if ('delete_course' == $_POST['action']) {
        $result = CourseManager::delete_course($_POST['course_code']);
        if ($result) {
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        }
    }

    // Toggle catalogue
    if ('toggle_catalogue' == $_POST['action']) {
        $courseId = (int) $_POST['course_id'];
        $accessUrlId = api_get_current_access_url_id();
        $em = Database::getManager();
        $repo = $em->getRepository(CatalogueCourseRelAccessUrlRelUsergroup::class);
        $course = api_get_course_entity($courseId);
        $accessUrl = $em->getRepository(AccessUrl::class)->find($accessUrlId);

        if ($course && $accessUrl) {
            $record = $repo->findOneBy([
                'course' => $course,
                'accessUrl' => $accessUrl,
                'usergroup' => null,
            ]);

            if ($record) {
                $em->remove($record);
                Display::addFlash(Display::return_message(get_lang('Removed from catalogue')));
            } else {
                $newRel = new CatalogueCourseRelAccessUrlRelUsergroup();
                $newRel->setCourse($course);
                $newRel->setAccessUrl($accessUrl);
                $newRel->setUsergroup(null);

                $em->persist($newRel);
                Display::addFlash(Display::return_message(get_lang('Added to catalogue'), 'success'));
            }

            $em->flush();
        }
    }
}

$content = '';
$message = '';
$actions = '';

$interbreadcrumb[] = [
    'url' => 'index.php',
    'name' => get_lang('Administration'),
];

if (isset($_GET['search']) && 'advanced' === $_GET['search']) {
    // Get all course categories
    $interbreadcrumb[] = [
        'url' => 'course_list.php',
        'name' => get_lang('Course list'),
    ];
    $tool_name = get_lang('Search for a course');
    $form = new FormValidator('advanced_course_search', 'get');
    $form->addElement('header', $tool_name);
    $form->addText('keyword_code', get_lang('Course code'), false);
    $form->addText('keyword_title', get_lang('Title'), false);

    // Category code
    $url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_category';

    $form->addElement(
        'select_ajax',
        'keyword_category',
        get_lang('Category'),
        null,
        [
            'url' => $url,
        ]
    );

    $el = $form->addSelectLanguage('keyword_language', get_lang('Course language'));
    $el->addOption(get_lang('All'), '%');
    $form->addElement('radio', 'keyword_visibility', get_lang('Course access'), get_lang('Public - access allowed for the whole world'), COURSE_VISIBILITY_OPEN_WORLD);
    $form->addElement('radio', 'keyword_visibility', null, get_lang('Open - access allowed for users registered on the platform'), COURSE_VISIBILITY_OPEN_PLATFORM);
    $form->addElement('radio', 'keyword_visibility', null, get_lang('Private'), COURSE_VISIBILITY_REGISTERED);
    $form->addElement('radio', 'keyword_visibility', null, get_lang('Closed - the course is only accessible to the teachers'), COURSE_VISIBILITY_CLOSED);
    $form->addElement('radio', 'keyword_visibility', null, get_lang('Hidden - Completely hidden to all users except the administrators'), COURSE_VISIBILITY_HIDDEN);
    $form->addElement('radio', 'keyword_visibility', null, get_lang('All'), '%');
    $form->addElement('radio', 'keyword_subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
    $form->addElement('radio', 'keyword_subscribe', null, get_lang('This function is only available to trainers'), 0);
    $form->addElement('radio', 'keyword_subscribe', null, get_lang('All'), '%');
    $form->addElement('radio', 'keyword_unsubscribe', get_lang('Unsubscribe'), get_lang('Users are allowed to unsubscribe from this course'), 1);
    $form->addElement('radio', 'keyword_unsubscribe', null, get_lang('Users are not allowed to unsubscribe from this course'), 0);
    $form->addElement('radio', 'keyword_unsubscribe', null, get_lang('All'), '%');
    $form->addButtonSearch(get_lang('Search courses'));
    $defaults['keyword_language'] = '%';
    $defaults['keyword_visibility'] = '%';
    $defaults['keyword_subscribe'] = '%';
    $defaults['keyword_unsubscribe'] = '%';
    $form->setDefaults($defaults);
    $content .= $form->returnForm();
} else {
    $tool_name = get_lang('Course list');
    if (isset($_GET['new_course_id'])) {
        $courseId = (int) $_GET['new_course_id'];
        $course = api_get_course_entity($courseId);
        if ($course) {
            $link = api_get_course_url($course->getId());
            $msg = sprintf(
                get_lang('Course %s added. You can access it directly %shere%s.'),
                '<strong>'.Security::remove_XSS($course->getTitle()).'</strong>',
                '<a href="'.$link.'" class="text-primary">',
                '</a>'
            );

            $content .=  Display::return_message($msg, 'confirmation', false);
        }
    }

    // Create a search-box
    $form = new FormValidator(
        'search_simple',
        'get',
        '',
        '',
        [],
        FormValidator::LAYOUT_BOX_SEARCH
    );
    $form->addElement(
        'text',
        'keyword',
        null,
        ['id' => 'course-search-keyword', 'aria-label' => get_lang('Search courses')]
    );
    $form->addButtonSearch(get_lang('Search courses'));
    $advanced = Display::toolbarButton(
        get_lang('Advanced search'),
        '/main/admin/course_list.php?'.http_build_query(['search' => 'advanced']),
        ActionIcon::SEARCH,
        'plain'
    );

    // Create a filter by session
    $sessionFilter = new FormValidator(
        'course_filter',
        'get',
        '',
        '',
        [],
        FormValidator::LAYOUT_INLINE
    );
    $url = api_get_path(WEB_AJAX_PATH).'session.ajax.php?a=search_session';
    $sessionSelect = $sessionFilter->addSelectAjax(
        'session_name',
        get_lang('Search course by session'),
        [],
        ['id' => 'session_name', 'url' => $url]
    );

    if (!empty($sessionId)) {
        $sessionInfo = SessionManager::fetch($sessionId);
        $sessionSelect->addOption(
            $sessionInfo['name'],
            $sessionInfo['id'],
            ['selected' => 'selected']
        );
    }

    $courseListUrl = api_get_self();
    $actions1 = Display::url(
        Display::getMdiIcon(
            ToolIcon::COURSE,
            'ch-tool-icon-gradient',
            null,
            32,
            get_lang('Create a course')
        ),
        api_get_path(WEB_CODE_PATH).'admin/course_add.php'
    );

    if ('true' === api_get_setting('course_validation')) {
        $actions1 .= Display::url(
            Display::getMdiIcon(
                'book-heart-outline',
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Review incoming course requests')
            ),
            api_get_path(WEB_CODE_PATH).'admin/course_request_review.php'
        );
    }

    $actions2 = $form->returnForm();
    $actions3 = $sessionFilter->returnForm();
    $actions4 = $advanced;
    $actions4 .= '
    <script>
        $(function() {
            $("#session_name").on("change", function() {
                var sessionId = $(this).val();
                if (!sessionId) {
                    return;
                }
                window.location = "'.$courseListUrl.'?session_id="+sessionId;
            });
        });
    </script>';

    $actions = Display::toolbarAction('toolbar', [$actions1, $actions3.$actions4.$actions2]);
    // Create a sortable table with the course data
    $table = new SortableTable(
        'courses',
        'get_number_of_courses',
        'get_course_data',
        2,
        20,
        'ASC',
        'course-list'
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

    $table->set_additional_parameters($parameters);

    $table->set_header(0, '', false, 'width="8px"');
    $table->set_header(1, get_lang('Title'), true, null, ['class' => 'title']);
    $table->set_header(2, get_lang('Course code'));
    $table->set_header(3, get_lang('Language'), false, 'width="70px"');
    $table->set_header(4, get_lang('Categories'));
    $table->set_header(5, get_lang('Registr. allowed'), true, 'width="60px"');
    $table->set_header(6, get_lang('Unreg. allowed'), false, 'width="50px"');
    $table->set_header(
        7,
        get_lang('Action'),
        false,
        null,
        ['class' => 'td_actions']
    );
    $table->set_form_actions(
        ['delete_courses' => get_lang('Delete selected course(s)')],
        'course'
    );

    $tab = CourseManager::getCourseListTabs('simple');

    $content .= $tab.$table->return_table();
}

$tpl = new Template($tool_name);
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
