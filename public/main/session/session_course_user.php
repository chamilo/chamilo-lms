<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;

// resetting the course id
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
$tool_name = get_lang('Edit session courses by user');
$id_session = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;
$session = api_get_session_entity($id_session);
SessionManager::protectSession($session);

$id_user = intval($_GET['id_user']);

$em = Database::getManager();
$session = api_get_session_entity($id_session);
$user = api_get_user_entity($id_user);
$currentUser = api_get_user_entity();

if (!api_is_platform_admin() && !$session->hasUserAsSessionAdmin($currentUser)) {
    api_not_allowed(true);
}

if (!$session->getCourses()->count()) {
    Display::addFlash(Display::return_message(get_lang('No course for this session'), 'warning'));
    header('Location: session_course_user.php?id_session='.$id_session.'&id_user='.$id_user);
    exit;
}

$avoidedCourseIds = SessionManager::getAvoidedCoursesInSession($user, $session);

$form = new FormValidator(
    'session_course_user',
    'post',
    api_get_self().'?id_user='.$user->getId().'&id_session='.$session->getId()
);
$form->addMultiSelect(
    'courses_to_avoid',
    [
        '',
        get_lang('Courses in this session'),
        get_lang('Unaccessible courses'),
    ],
    getSessionCourseList($session)
);

$courseSelector = $form->getElement('courses_to_avoid');
$courseSelector->setElementTemplate(
    '
    {javascript}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_5rem_minmax(0,1fr)] lg:items-stretch">
        <section class="min-w-0 rounded-2xl border border-gray-20 bg-white p-4 shadow-sm">
            <label for="courses_to_avoid" class="mb-3 block text-body-2 font-semibold text-gray-90">
                {label_2}
            </label>
            <div class="min-w-0">
                {unselected}
            </div>
        </section>

        <section class="min-w-0 rounded-2xl border border-gray-20 bg-support-2 p-4 shadow-sm">
            <div class="flex h-full min-h-24 items-center justify-center gap-3 lg:flex-col">
                {add}
                {remove}
            </div>
        </section>

        <section class="min-w-0 rounded-2xl border border-gray-20 bg-white p-4 shadow-sm">
            <label for="courses_to_avoid_to" class="mb-3 block text-body-2 font-semibold text-gray-90">
                {label_3}
            </label>
            <div class="min-w-0">
                {selected}
            </div>
        </section>
    </div>'
);

$transferButtonClasses = 'inline-flex h-12 w-12 min-h-12 min-w-12 items-center justify-center rounded-xl border-0 bg-secondary p-0 text-center text-secondary-button-text hover:bg-secondary-hover focus:outline-none focus:ring-2 focus:ring-secondary';
$courseSelector->setButtonAttributes('add', [
    'class' => $transferButtonClasses,
    'title' => get_lang('Add'),
    'aria-label' => get_lang('Add'),
    'data-bs-toggle' => 'tooltip',
    'data-bs-placement' => 'right',
]);
$courseSelector->setButtonAttributes('remove', [
    'class' => $transferButtonClasses,
    'title' => get_lang('Remove'),
    'aria-label' => get_lang('Remove'),
    'data-bs-toggle' => 'tooltip',
    'data-bs-placement' => 'right',
]);

$renderer = $form->defaultRenderer();
$renderer->setElementTemplate(
    '
    <div class="{error_class}">
        {element}
        <!-- BEGIN error -->
            <p class="mt-2 text-sm text-danger">{error}</p>
        <!-- END error -->
    </div>',
    'courses_to_avoid'
);

$form->addButtonSave(get_lang('Save'));
$renderer->setElementTemplate(
    '<div class="mt-5 flex justify-end border-t border-gray-20 pt-4">{element}</div>',
    'submit'
);

if ($form->validate()) {
    $values = $form->exportValues();
    $values['courses_to_avoid'] = !empty($values['courses_to_avoid']) ? $values['courses_to_avoid'] : [];

    if ($session->getCourses()->count() == count($values['courses_to_avoid'])) {
        Display::addFlash(Display::return_message(get_lang('Maybe you want to delete the user, instead of unsubscribing him from all courses...?')));
        header('Location: session_course_user.php?id_session='.$id_session.'&id_user='.$id_user);
        exit;
    }

    foreach ($values['courses_to_avoid'] as $courseId) {
        $course = api_get_course_entity($courseId);

        if (!$session->getUserInCourse($user, $course)->count()) {
            continue;
        }

        $session->removeUserCourseSubscription($user, $course);
    }

    $coursesToResubscribe = array_diff($avoidedCourseIds, $values['courses_to_avoid']);

    foreach ($coursesToResubscribe as $courseId) {
        $course = api_get_course_entity($courseId);

        if ($session->getUserInCourse($user, $course)->count()) {
            continue;
        }

        $session->addUserInCourse(Session::STUDENT, $user, $course);
    }

    $em->persist($session);
    $em->flush();

    Display::addFlash(Display::return_message(get_lang('Courses updated')));
    header('Location: session_course_user.php?id_session='.$session->getId().'&id_user='.$user->getId());
    exit;
}

$form->setDefaults(['courses_to_avoid' => $avoidedCourseIds]);

/* View */
// setting breadcrumbs
$interbreadcrumb[] = ['url' => '/admin/session-list', 'name' => get_lang('Session list')];
$interbreadcrumb[] = [
    'url' => 'resume_session.php?id_session='.$id_session,
    'name' => get_lang('Session overview'),
];

Display::display_header($tool_name);
echo Display::page_header($session->getTitle().' - '.UserManager::formatUserFullName($user));
echo '<div class="mx-auto w-full max-w-6xl py-2">';
echo $form->returnForm();
echo '</div>';

Display::display_footer();

/**
 * @return array
 */
function getSessionCourseList(Session $session)
{
    $return = [];

    foreach ($session->getCourses() as $sessionCourse) {
        /** @var Course $course */
        $course = $sessionCourse->getCourse();
        $return[$course->getId()] = $course->getTitle().' ('.$course->getCode().')';
    }

    return $return;
}
