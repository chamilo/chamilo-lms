<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\CoreBundle\Repository\CourseCategoryRepository;
use Chamilo\UserBundle\Entity\User;

/**
 * @package chamilo.admin
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
$em = Database::getManager();
/** @var CourseCategoryRepository $courseCategoriesRepo */
$courseCategoriesRepo = $em->getRepository('ChamiloCoreBundle:CourseCategory');
// Get all possible teachers.
$urlId = api_get_current_access_url_id();

$courseId = isset($_GET['id']) ? $_GET['id'] : null;

if (empty($courseId)) {
    api_not_allowed(true);
}

$courseInfo = api_get_course_info_by_id($courseId);

if (empty($courseInfo)) {
    api_not_allowed(true);
}

$tool_name = get_lang('Edit course information');
$interbreadcrumb[] = ["url" => 'index.php', "name" => get_lang('Administration')];
$interbreadcrumb[] = ["url" => "course_list.php", "name" => get_lang('Course list')];

// Get all course categories
$table_user = Database::get_main_table(TABLE_MAIN_USER);
$course_code = $courseInfo['code'];
$courseId = $courseInfo['real_id'];

// Get course teachers
$table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
$sql = "SELECT user.user_id,lastname,firstname
        FROM $table_user as user,$table_course_user as course_user
        WHERE
            course_user.status='1' AND
            course_user.user_id=user.user_id AND
            course_user.c_id ='".$courseId."'".
        $order_clause;
$res = Database::query($sql);
$course_teachers = [];
while ($obj = Database::fetch_object($res)) {
    $course_teachers[] = $obj->user_id;
}

// Get all possible teachers without the course teachers
if (api_is_multiple_url_enabled()) {
    $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    $sql = "SELECT u.user_id,lastname,firstname
            FROM $table_user as u
            INNER JOIN $access_url_rel_user_table url_rel_user
            ON (u.user_id=url_rel_user.user_id)
            WHERE
                url_rel_user.access_url_id = $urlId AND
                status = 1".$order_clause;
} else {
    $sql = "SELECT user_id, lastname, firstname
            FROM $table_user WHERE status='1'".$order_clause;
}
$courseInfo['tutor_name'] = null;

$res = Database::query($sql);
$teachers = [];
$allTeachers = [];
$platform_teachers[0] = '-- '.get_lang('No administrator').' --';
while ($obj = Database::fetch_object($res)) {
    $allTeachers[$obj->user_id] = api_get_person_name($obj->firstname, $obj->lastname);
    if (!array_key_exists($obj->user_id, $course_teachers)) {
        $teachers[$obj->user_id] = api_get_person_name($obj->firstname, $obj->lastname);
    }

    if (isset($course_teachers[$obj->user_id]) &&
        $courseInfo['tutor_name'] == $course_teachers[$obj->user_id]
    ) {
        $courseInfo['tutor_name'] = $obj->user_id;
    }
    // We add in the array platform teachers
    $platform_teachers[$obj->user_id] = api_get_person_name($obj->firstname, $obj->lastname);
}

// Case where there is no teacher in the course
if (count($course_teachers) == 0) {
    $sql = 'SELECT tutor_name FROM '.$course_table.' WHERE code="'.$course_code.'"';
    $res = Database::query($sql);
    $tutor_name = Database::result($res, 0, 0);
    $courseInfo['tutor_name'] = array_search($tutor_name, $platform_teachers);
}

// Build the form
$form = new FormValidator(
    'update_course',
    'post',
    api_get_self().'?id='.$courseId
);
$form->addElement('header', get_lang('Course').'  #'.$courseInfo['real_id'].' '.$course_code);
$form->addElement('hidden', 'code', $course_code);

//title
$form->addText('title', get_lang('Title'), true);
$form->applyFilter('title', 'html_filter');
$form->applyFilter('title', 'trim');

// Code
$element = $form->addElement(
    'text',
    'real_code',
    [get_lang('Code'), get_lang('This value can\'t be changed.')]
);
$element->freeze();

// Visual code
$form->addText(
    'visual_code',
    [
        get_lang('visual code'),
        get_lang('Only letters (a-z) and numbers (0-9)'),
        get_lang('This value is used in the course URL'),
    ],
    true,
    [
        'maxlength' => CourseManager::MAX_COURSE_LENGTH_CODE,
        'pattern' => '[a-zA-Z0-9]+',
        'title' => get_lang('Only letters (a-z) and numbers (0-9)'),
    ]
);

$form->applyFilter('visual_code', 'strtoupper');
$form->applyFilter('visual_code', 'html_filter');

$countCategories = $courseCategoriesRepo->countAllInAccessUrl(
    $urlId,
    api_get_configuration_value('allow_base_course_category')
);
if ($countCategories >= 100) {
    // Category code
    $url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_category';

    $categorySelect = $form->addElement(
        'select_ajax',
        'category_code',
        get_lang('Category'),
        null,
        ['url' => $url]
    );

    if (!empty($courseInfo['categoryCode'])) {
        $data = \CourseCategory::getCategory($courseInfo['categoryCode']);
        $categorySelect->addOption($data['name'], $data['code']);
    }
} else {
    $courseInfo['category_code'] = $courseInfo['categoryCode'];
    $categories = $courseCategoriesRepo->findAllInAccessUrl(
        $urlId,
        api_get_configuration_value('allow_base_course_category')
    );
    $categoriesOptions = [null => get_lang('none')];

    /** @var CourseCategory $category */
    foreach ($categories as $category) {
        $categoriesOptions[$category->getCode()] = (string) $category;
    }

    $form->addSelect(
        'category_code',
        get_lang('Category'),
        $categoriesOptions
    );
}

$courseTeacherNames = [];

foreach ($course_teachers as $courseTeacherId) {
    /** @var User $courseTeacher */
    $courseTeacher = UserManager::getRepository()->find($courseTeacherId);
    $courseTeacherNames[$courseTeacher->getUserId()] = UserManager::formatUserFullName($courseTeacher, true);
}

$form->addSelectAjax(
    'course_teachers',
    get_lang('Teachers'),
    $courseTeacherNames,
    ['url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=teacher_to_basis_course', 'multiple' => 'multiple']
);
$courseInfo['course_teachers'] = $course_teachers;
if (array_key_exists('add_teachers_to_sessions_courses', $courseInfo)) {
    $form->addElement(
        'checkbox',
        'add_teachers_to_sessions_courses',
        null,
        get_lang('Teachers will be added as a coach in all course sessions.')
    );
}

$allowEditSessionCoaches = api_get_configuration_value('disabled_edit_session_coaches_course_editing_course') === false;
$coursesInSession = SessionManager::get_session_by_course($courseInfo['real_id']);
if (!empty($coursesInSession) && $allowEditSessionCoaches) {
    foreach ($coursesInSession as $session) {
        $sessionId = $session['id'];
        $coaches = SessionManager::getCoachesByCourseSession(
            $sessionId,
            $courseInfo['real_id']
        );
        $teachers = $allTeachers;

        $sessionTeachers = [];
        foreach ($coaches as $coachId) {
            $sessionTeachers[] = $coachId;

            if (isset($teachers[$coachId])) {
                unset($teachers[$coachId]);
            }
        }

        $groupName = 'session_coaches_'.$sessionId;
        $sessionUrl = api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$sessionId;
        $form->addElement(
            'advmultiselect',
            $groupName,
            Display::url(
                $session['name'],
                $sessionUrl,
                ['target' => '_blank']
            ).' - '.get_lang('Coaches'),
            $allTeachers
        );
        $courseInfo[$groupName] = $sessionTeachers;
    }
}

$form->addText('department_name', get_lang('Department'), false, ['size' => '60']);
$form->applyFilter('department_name', 'html_filter');
$form->applyFilter('department_name', 'trim');

$form->addText('department_url', get_lang('DepartmentURL'), false, ['size' => '60']);
$form->applyFilter('department_url', 'html_filter');
$form->applyFilter('department_url', 'trim');

$form->addSelectLanguage('course_language', get_lang('Course language'));

$group = [];
$group[] = $form->createElement('radio', 'visibility', get_lang("Course access"), get_lang('Public - access allowed for the whole world'), COURSE_VISIBILITY_OPEN_WORLD);
$group[] = $form->createElement('radio', 'visibility', null, get_lang(' Open - access allowed for users registered on the platform'), COURSE_VISIBILITY_OPEN_PLATFORM);
$group[] = $form->createElement('radio', 'visibility', null, get_lang('Private access (access authorized to group members only)'), COURSE_VISIBILITY_REGISTERED);
$group[] = $form->createElement('radio', 'visibility', null, get_lang('Closed - the course is only accessible to the teachers'), COURSE_VISIBILITY_CLOSED);
$group[] = $form->createElement('radio', 'visibility', null, get_lang('Hidden - Completely hidden to all users except the administrators'), COURSE_VISIBILITY_HIDDEN);
$form->addGroup($group, '', get_lang('Course access'));

$group = [];
$group[] = $form->createElement('radio', 'subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
$group[] = $form->createElement('radio', 'subscribe', null, get_lang('This function is only available to trainers'), 0);
$form->addGroup($group, '', get_lang('Subscription'));

$group = [];
$group[] = $form->createElement('radio', 'unsubscribe', get_lang('Unsubscribe'), get_lang('Users are allowed to unsubscribe from this course'), 1);
$group[] = $form->createElement('radio', 'unsubscribe', null, get_lang('NotUsers are allowed to unsubscribe from this course'), 0);
$form->addGroup($group, '', get_lang('Unsubscribe'));

$form->addElement('text', 'disk_quota', [get_lang('Disk Space'), null, get_lang('MB')]);
$form->addRule('disk_quota', get_lang('Required field'), 'required');
$form->addRule('disk_quota', get_lang('This field should be numeric'), 'numeric');

// Extra fields
$extra_field = new ExtraField('course');
$extra = $extra_field->addElements(
    $form,
    $courseId,
    [],
    false,
    false,
    [],
    [],
    [],
    false,
    true
);

$htmlHeadXtra[] = '
<script>
$(function() {
    '.$extra['jquery_ready_content'].'
});
</script>';

$form->addButtonUpdate(get_lang('Edit course information'));

// Set some default values
$courseInfo['disk_quota'] = round(DocumentManager::get_course_quota($courseInfo['code']) / 1024 / 1024, 1);
$courseInfo['real_code'] = $courseInfo['code'];
$courseInfo['add_teachers_to_sessions_courses'] = isset($courseInfo['add_teachers_to_sessions_courses']) ? $courseInfo['add_teachers_to_sessions_courses'] : 0;

$form->setDefaults($courseInfo);

// Validate form
if ($form->validate()) {
    $course = $form->getSubmitValues();
    $visibility = $course['visibility'];

    global $_configuration;

    if (isset($_configuration[$urlId]) &&
        isset($_configuration[$urlId]['hosting_limit_active_courses']) &&
        $_configuration[$urlId]['hosting_limit_active_courses'] > 0
    ) {
        // Check if
        if ($courseInfo['visibility'] == COURSE_VISIBILITY_HIDDEN &&
            $visibility != $courseInfo['visibility']
        ) {
            $num = CourseManager::countActiveCourses($urlId);
            if ($num >= $_configuration[$urlId]['hosting_limit_active_courses']) {
                api_warn_hosting_contact('hosting_limit_active_courses');

                Display::addFlash(
                    Display::return_message(get_lang('Sorry, this installation has an active courses limit, which has now been reached. You can still create new courses, but only if you hide/disable at least one existing active course. To do this, edit a course from the administration courses list, and change the visibility to \'hidden\', then try creating this course again. To increase the maximum number of active courses allowed on this Chamilo installation, please contact your hosting provider or, if available, upgrade to a superior hosting plan.'))
                );

                header('Location: course_list.php');
                exit;
            }
        }
    }

    $visual_code = $course['visual_code'];
    $visual_code = CourseManager::generate_course_code($visual_code);

    // Check if the visual code is already used by *another* course
    $visual_code_is_used = false;

    $warn = get_lang('TheFollowingCoursesAlreadyUseThisvisual code');
    if (!empty($visual_code)) {
        $list = CourseManager::get_courses_info_from_visual_code($visual_code);
        foreach ($list as $course_temp) {
            if ($course_temp['code'] != $course_code) {
                $visual_code_is_used = true;
                $warn .= ' '.$course_temp['title'].' ('.$course_temp['code'].'),';
            }
        }
        $warn = substr($warn, 0, -1);
    }

    $teachers = isset($course['course_teachers']) ? $course['course_teachers'] : '';
    $title = $course['title'];
    $category_code = isset($course['category_code']) ? $course['category_code'] : '';
    $department_name = $course['department_name'];
    $department_url = $course['department_url'];
    $course_language = $course['course_language'];
    $course['disk_quota'] = $course['disk_quota'] * 1024 * 1024;
    $disk_quota = $course['disk_quota'];
    $subscribe = $course['subscribe'];
    $unsubscribe = $course['unsubscribe'];
    $course['course_code'] = $course_code;

    if (!stristr($department_url, 'http://')) {
        $department_url = 'http://'.$department_url;
    }

    Database::query($sql);

    $title = str_replace('&amp;', '&', $title);
    $params = [
        'course_language' => $course_language,
        'title' => $title,
        'category_code' => $category_code,
        'visual_code' => $visual_code,
        'department_name' => $department_name,
        'department_url' => $department_url,
        'disk_quota' => $disk_quota,
        'visibility' => $visibility,
        'subscribe' => $subscribe,
        'unsubscribe' => $unsubscribe,
    ];
    Database::update($course_table, $params, ['id = ?' => $courseId]);

    // update the extra fields
    $courseFieldValue = new ExtraFieldValue('course');
    $courseFieldValue->saveFieldValues($course);
    $addTeacherToSessionCourses = isset($course['add_teachers_to_sessions_courses']) && !empty($course['add_teachers_to_sessions_courses']) ? 1 : 0;

    // Updating teachers
    if ($addTeacherToSessionCourses) {
        foreach ($coursesInSession as $session) {
            $sessionId = $session['id'];
            // Updating session coaches
            $sessionCoaches = isset($course['session_coaches_'.$sessionId]) ? $course['session_coaches_'.$sessionId] : [];

            if (!empty($sessionCoaches)) {
                foreach ($sessionCoaches as $teacherInfo) {
                    $coachesToSubscribe = isset($teacherInfo['coaches_by_session']) ? $teacherInfo['coaches_by_session'] : [];
                    SessionManager::updateCoaches(
                        $sessionId,
                        $courseId,
                        $coachesToSubscribe,
                        true
                    );
                }
            }
        }

        CourseManager::updateTeachers(
            $courseInfo,
            $teachers,
            true,
            true,
            false
        );
    } else {
        // Normal behaviour
        CourseManager::updateTeachers($courseInfo, $teachers, true, false);

        foreach ($coursesInSession as $session) {
            $sessionId = $session['id'];
            // Updating session coaches
            $sessionCoaches = isset($course['session_coaches_'.$sessionId]) ? $course['session_coaches_'.$sessionId] : [];

            if (!empty($sessionCoaches)) {
                SessionManager::updateCoaches(
                    $sessionId,
                    $courseId,
                    $sessionCoaches,
                    true
                );
            }
        }
    }

    if (array_key_exists('add_teachers_to_sessions_courses', $courseInfo)) {
        $sql = "UPDATE $course_table SET
                add_teachers_to_sessions_courses = '$addTeacherToSessionCourses'
                WHERE id = ".$courseInfo['real_id'];
        Database::query($sql);
    }

    $courseInfo = api_get_course_info($courseInfo['code']);
    $message = Display::url($courseInfo['title'], $courseInfo['course_public_url']);
    Display::addFlash(Display::return_message(get_lang('Item updated').': '.$message, 'info', false));
    if ($visual_code_is_used) {
        Display::addFlash(Display::return_message($warn));
        header('Location: course_list.php');
    } else {
        header('Location: course_list.php');
    }
    exit;
}

Display::display_header($tool_name);

echo '<div class="actions">';
echo Display::url(Display::return_icon('back.png', get_lang('Back')), api_get_path(WEB_CODE_PATH).'admin/course_list.php');
echo Display::url(Display::return_icon('course_home.png', get_lang('Course homepage')), $courseInfo['course_public_url'], ['target' => '_blank']);
echo '</div>';

echo "<script>
function moveItem(origin , destination) {
    for (var i = 0 ; i<origin.options.length ; i++) {
        if (origin.options[i].selected) {
            destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
            origin.options[i]=null;
            i = i-1;
        }
    }
    destination.selectedIndex = -1;
    sortOptions(destination.options);
}

function sortOptions(options) {

    newOptions = new Array();
    for (i = 0 ; i<options.length ; i++) {
        newOptions[i] = options[i];
    }
    newOptions = newOptions.sort(mysort);
    options.length = 0;
    for (i = 0 ; i < newOptions.length ; i++) {
        options[i] = newOptions[i];
    }
}

function mysort(a, b) {
    if (a.text.toLowerCase() > b.text.toLowerCase()) {
        return 1;
    }
    if (a.text.toLowerCase() < b.text.toLowerCase()) {
        return -1;
    }
    return 0;
}

function valide() {
    // Checking all multiple
    $('select').filter(function() {
        if ($(this).attr('multiple')) {
            $(this).find('option').each(function() {
                $(this).attr('selected', true);
            });
        }
    });
}
</script>";

// Display the form
$form->display();

Display :: display_footer();
