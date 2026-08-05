<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Enums\ObjectIcon;

/**
 * Implements the edition of course-session settings.
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

$sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;
$session = api_get_session_entity($sessionId);
SessionManager::protectSession($session);

$courseCode = isset($_GET['course_code']) ? trim((string) $_GET['course_code']) : '';
$courseInfo = api_get_course_info($courseCode);

if (empty($courseInfo)) {
    api_not_allowed(true);
}

$userTable = Database::get_main_table(TABLE_MAIN_USER);
$sessionRelCourseRelUserTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$courseId = (int) $courseInfo['real_id'];
$courseTitle = (string) $courseInfo['title'];
$sessionTitle = (string) $session->getTitle();
$entityManager = Database::getManager();
$sessionRelCourse = $entityManager->getRepository(SessionRelCourse::class)->findOneBy([
    'session' => $sessionId,
    'course' => $courseId,
]);

if (!$sessionRelCourse instanceof SessionRelCourse) {
    header('Location: session_course_list.php?id_session='.$sessionId);
    exit;
}

$allowedPages = [
    'session_course_list.php',
    'resume_session.php',
];
$page = isset($_GET['page']) ? basename((string) $_GET['page']) : 'session_course_list.php';

if (!in_array($page, $allowedPages, true)) {
    $page = 'session_course_list.php';
}

$returnUrl = $page.'?id_session='.$sessionId;
$sessionListUrl = '/admin/session-list';
$sessionOverviewUrl = 'resume_session.php?id_session='.$sessionId;
$courseListUrl = 'session_course_list.php?id_session='.$sessionId;
$courseHomeUrl = api_get_course_url($courseId, $sessionId);
$toolName = get_lang('Edit session course');

$interbreadcrumb[] = ['url' => $sessionListUrl, 'name' => get_lang('Session list')];
$interbreadcrumb[] = ['url' => $sessionOverviewUrl, 'name' => get_lang('Session overview')];
$interbreadcrumb[] = ['url' => $courseListUrl, 'name' => get_lang('Courses in this session')];
$interbreadcrumb[] = ['url' => $courseHomeUrl, 'name' => api_htmlentities($courseTitle, ENT_QUOTES)];

$accessUrlId = api_get_current_access_url_id();
$rooms = $entityManager->createQueryBuilder()
    ->select('room', 'branch')
    ->from(Room::class, 'room')
    ->innerJoin('room.branch', 'branch')
    ->where('IDENTITY(branch.url) = :accessUrlId')
    ->setParameter('accessUrlId', $accessUrlId)
    ->orderBy('branch.title', 'ASC')
    ->addOrderBy('room.title', 'ASC')
    ->getQuery()
    ->getResult()
;

$roomOptions = [0 => get_lang('No room')];
foreach ($rooms as $room) {
    $roomOptions[$room->getId()] = $room->getBranch()->getTitle().' — '.$room->getTitle();
}

$currentTutorIds = [];
$sql = "SELECT user_id
        FROM $sessionRelCourseRelUserTable
        WHERE session_id = $sessionId
          AND c_id = $courseId
          AND status = ".Session::COURSE_COACH;
$result = Database::query($sql);

while ($row = Database::fetch_array($result)) {
    $currentTutorIds[] = (int) $row['user_id'];
}

if (isset($_POST['formSent']) && (int) $_POST['formSent'] === 1) {
    $roomId = isset($_POST['room_id']) ? (int) $_POST['room_id'] : 0;
    $room = null;

    if ($roomId > 0) {
        $room = $entityManager->createQueryBuilder()
            ->select('room')
            ->from(Room::class, 'room')
            ->innerJoin('room.branch', 'branch')
            ->where('room.id = :roomId')
            ->andWhere('IDENTITY(branch.url) = :accessUrlId')
            ->setParameter('roomId', $roomId)
            ->setParameter('accessUrlId', $accessUrlId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$room instanceof Room) {
            api_not_allowed(true);
        }
    }

    $sessionRelCourse->setRoom($room);
    $entityManager->persist($sessionRelCourse);

    $submittedTutorIds = array_values(array_unique(array_filter(array_map(
        'intval',
        (array) ($_POST['id_coach'] ?? [])
    ))));

    foreach (array_diff($submittedTutorIds, $currentTutorIds) as $tutorId) {
        SessionManager::set_coach_to_course_session(
            $tutorId,
            $sessionId,
            $courseId
        );
    }

    foreach (array_diff($currentTutorIds, $submittedTutorIds) as $tutorId) {
        SessionManager::set_coach_to_course_session(
            $tutorId,
            $sessionId,
            $courseId,
            true
        );
    }

    $entityManager->flush();

    Display::addFlash(Display::return_message(get_lang('Update successful')));
    header('Location: '.$returnUrl);
    exit;
}

$orderClause = api_sort_by_first_name()
    ? ' ORDER BY firstname, lastname, username'
    : ' ORDER BY lastname, firstname, username';

if (api_is_multiple_url_enabled()) {
    $accessUrlRelUserTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    $sql = "SELECT u.id AS user_id, u.lastname, u.firstname, u.username
            FROM $userTable u
            INNER JOIN $accessUrlRelUserTable a ON u.id = a.user_id
            WHERE u.status = 1
              AND u.active = 1
              AND a.access_url_id = $accessUrlId".$orderClause;
} else {
    $sql = "SELECT id AS user_id, lastname, firstname, username
            FROM $userTable
            WHERE status = 1
              AND active = 1".$orderClause;
}

$result = Database::query($sql);
$tutors = Database::store_result($result);

if (!api_is_platform_admin() && api_is_teacher()) {
    $userInfo = api_get_user_info();
    $tutors = [$userInfo];
}

$tutorOptions = [];
foreach ($tutors as $tutor) {
    $tutorOptions[(int) $tutor['user_id']] = api_get_person_name(
        $tutor['firstname'],
        $tutor['lastname']
    ).' ('.$tutor['username'].')';
}

$form = new FormValidator(
    'session_course_settings',
    'post',
    api_get_self().'?id_session='.$sessionId.'&course_code='.urlencode($courseCode).'&page='.urlencode($page)
);
$form->addSelect('room_id', get_lang('Room'), $roomOptions, ['class' => 'w-full']);
$tutorSelect = $form->addSelect(
    'id_coach',
    get_lang('Tutor name'),
    $tutorOptions,
    [
        'multiple' => 'multiple',
        'size' => 8,
        'class' => 'w-full',
    ]
);
$tutorSelect->setSelected($currentTutorIds);

$form->addHidden('formSent', 1);
$form->addButtonSave(get_lang('Save'));
$form->setDefaults([
    'room_id' => $sessionRelCourse->getRoom()?->getId() ?? 0,
]);
$formContent = $form->returnForm();

Display::display_header($toolName);

$toolbarActions = '<div class="flex items-center gap-3">';
$toolbarActions .= '<a href="'.$returnUrl.'" class="inline-flex items-center" aria-label="'.api_htmlentities(get_lang('Back'), ENT_QUOTES).'">'
    .Display::getMdiIcon('arrow-left', 'ch-tool-icon-gradient', null, 32, get_lang('Back')).'</a>';
$toolbarActions .= '<a href="'.$sessionOverviewUrl.'" class="inline-flex items-center" aria-label="'.api_htmlentities(get_lang('Session overview'), ENT_QUOTES).'">'
    .Display::getMdiIcon(ObjectIcon::SESSION, 'ch-tool-icon-gradient', null, 32, get_lang('Session overview')).'</a>';
$toolbarActions .= '<a href="'.$courseHomeUrl.'" class="inline-flex items-center" aria-label="'.api_htmlentities(get_lang('Course'), ENT_QUOTES).'">'
    .Display::getMdiIcon(ObjectIcon::HOME, 'ch-tool-icon-gradient', null, 32, get_lang('Course')).'</a>';
$toolbarActions .= '</div>';

echo '<div class="mx-auto w-full space-y-4 p-4">';
echo '  <div class="rounded-lg border border-gray-30 bg-white p-4 shadow-sm">';
echo '    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">';
echo '      <div class="min-w-0">';
echo '        <h1 class="text-xl font-semibold text-gray-90">'.api_htmlentities($toolName, ENT_QUOTES).'</h1>';
echo '        <p class="mt-1 text-sm text-gray-50">'.api_htmlentities($sessionTitle.' — '.$courseTitle, ENT_QUOTES).'</p>';
echo '      </div>';
echo        $toolbarActions;
echo '    </div>';
echo '  </div>';
echo '  <div class="rounded-lg border border-gray-30 bg-white p-4 shadow-sm">';
echo        $formContent;
echo '  </div>';
echo '</div>';

Display::display_footer();
