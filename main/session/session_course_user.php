<?php
/* For licensing terms, see /license.txt */
/**
 *	@package chamilo.admin
 */
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;

// resetting the course id
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
$tool_name = get_lang('EditSessionCoursesByUser');
$id_session = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;
SessionManager::protectSession($id_session);

$id_user = intval($_GET['id_user']);

$em = Database::getManager();
/** @var Session $session */
$session = $em->find('ChamiloCoreBundle:Session', $id_session);
$user = api_get_user_entity($id_user);

if (!api_is_platform_admin() && $session->getSessionAdminId() != api_get_user_id()) {
    api_not_allowed(true);
}

if (!$session->getCourses()->count()) {
    Display::addFlash(Display::return_message(get_lang('NoCoursesForThisSession'), 'warning'));
    header('Location: session_course_user.php?id_session='.$id_session.'&id_user='.$id_user);
    exit;
}

$avoidedCourseIds = SessionManager::getAvoidedCoursesInSession($user, $session);

$form = new FormValidator(
    'session_course_user',
    'post',
    api_get_self().'?id_user='.$user->getId().'&id_session='.$session->getId()
);
$form->addElement(
    'advmultiselect',
    'courses_to_avoid',
    $tool_name,
    getSessionCourseList($session)
);
$form->addButtonSave(get_lang('Save'));

if ($form->validate()) {
    $values = $form->exportValues();
    $values['courses_to_avoid'] = !empty($values['courses_to_avoid']) ? $values['courses_to_avoid'] : [];

    if ($session->getCourses()->count() == count($values['courses_to_avoid'])) {
        Display::addFlash(Display::return_message(get_lang('MaybeYouWantToDeleteThisUserFromSession')));
        header('Location: session_course_user.php?id_session='.$id_session.'&id_user='.$id_user);
        exit;
    }

    foreach ($values['courses_to_avoid'] as $courseId) {
        /** @var Course $course */
        $course = $em->find('ChamiloCoreBundle:Course', $courseId);

        if (!$session->getUserInCourse($user, $course)->count()) {
            continue;
        }

        $session->removeUserCourseSubscription($user, $course);
    }

    $coursesToResubscribe = array_diff($avoidedCourseIds, $values['courses_to_avoid']);

    foreach ($coursesToResubscribe as $courseId) {
        /** @var Course $course */
        $course = $em->find('ChamiloCoreBundle:Course', $courseId);

        if ($session->getUserInCourse($user, $course)->count()) {
            continue;
        }

        $session->addUserInCourse(Session::STUDENT, $user, $course);
    }

    $em->persist($session);
    $em->flush();

    Display::addFlash(Display::return_message(get_lang('CoursesUpdated')));
    header('Location: session_course_user.php?id_session='.$session->getId().'&id_user='.$user->getId());
    exit;
}

$form->setDefaults(['courses_to_avoid' => $avoidedCourseIds]);

/* View */
// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'session_list.php', 'name' => get_lang('SessionList')];
$interbreadcrumb[] = [
    'url' => 'resume_session.php?id_session='.$id_session,
    'name' => get_lang('SessionOverview'),
];

Display::display_header($tool_name);
echo Display::page_header($session->getName().' - '.UserManager::formatUserFullName($user));
?>
<div class="row">
    <div class="col-sm-8 col-sm-offset-2">
        <div class="row">
            <div class="col-sm-5">
                <label for="courses_to_avoid-f"><?php echo get_lang('CourseListInSession'); ?></label>
            </div>
            <div class="col-sm-5 col-sm-offset-2">
                <label for="courses_to_avoid-t"><?php echo get_lang('CoursesToAvoid'); ?></label>
            </div>
        </div>
    </div>
</div>
<?php
echo $form->returnForm();

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
