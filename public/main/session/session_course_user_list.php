<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

$userTable = Database::get_main_table(TABLE_MAIN_USER);
$courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
$sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
$sessionRelCourseTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$sessionRelUserTable = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$sessionRelCourseRelUserTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;
$session = api_get_session_entity($sessionId);
SessionManager::protectSession($session);

if (empty($sessionId)) {
    api_not_allowed();
}

$courseCode = isset($_GET['course_code']) ? trim((string) $_GET['course_code']) : '';
$courseInfo = api_get_course_info($courseCode);

if (empty($courseInfo)) {
    api_not_allowed(true);
}

$courseId = (int) $courseInfo['real_id'];
$page = max(0, isset($_GET['page']) ? (int) $_GET['page'] : 0);
$action = $_REQUEST['action'] ?? null;
$defaultSort = api_sort_by_first_name() ? 'firstname' : 'lastname';
$sort = isset($_GET['sort']) && in_array($_GET['sort'], ['lastname', 'firstname', 'username'], true)
    ? $_GET['sort']
    : $defaultSort;
$queryDirection = isset($_GET['direction']) && in_array($_GET['direction'], ['desc', 'asc'], true)
    ? $_GET['direction']
    : 'asc';
$nextDirection = 'asc' === $queryDirection ? 'desc' : 'asc';
$checkedIds = isset($_GET['idChecked']) && is_array($_GET['idChecked'])
    ? $_GET['idChecked']
    : ((isset($_POST['idChecked']) && is_array($_POST['idChecked'])) ? $_POST['idChecked'] : []);
$checkedIds = array_values(array_unique(array_filter(array_map('intval', $checkedIds))));

$sql = "SELECT s.title, c.title
        FROM $sessionRelCourseTable src
        INNER JOIN $sessionTable s ON s.id = src.session_id
        INNER JOIN $courseTable c ON c.id = src.c_id
        WHERE src.session_id = $sessionId
          AND src.c_id = $courseId";
$result = Database::query($sql);

if (!list($sessionTitle, $courseTitle) = Database::fetch_row($result)) {
    header('Location: session_course_list.php?id_session='.$sessionId);
    exit;
}

switch ($action) {
    case 'delete':
        foreach ($checkedIds as $userId) {
            SessionManager::unSubscribeUserFromCourseSession($userId, $courseId, $sessionId);
        }

        Display::addFlash(Display::return_message(get_lang('Update successful')));
        header(
            'Location: '.api_get_self()
            .'?id_session='.$sessionId
            .'&course_code='.urlencode($courseCode)
            .'&sort='.$sort
        );
        exit;

    case 'add':
        $usersToAdd = $checkedIds;

        if (SessionManager::isCourseUserSubscriptionLimitedToSessionUsers()) {
            $usersToAdd = SessionManager::filterUsersSubscribedToSession($sessionId, $usersToAdd);

            if (empty($usersToAdd)) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('No users from the selected list are subscribed to this session.'),
                        'warning'
                    )
                );

                header(
                    'Location: '.api_get_self()
                    .'?id_session='.$sessionId
                    .'&course_code='.urlencode($courseCode)
                    .'&sort='.$sort
                );
                exit;
            }
        }

        if (!empty($usersToAdd)) {
            SessionManager::subscribe_users_to_session_course($usersToAdd, $sessionId, $courseCode);
            Display::addFlash(Display::return_message(get_lang('Update successful')));
        }

        header(
            'Location: '.api_get_self()
            .'?id_session='.$sessionId
            .'&course_code='.urlencode($courseCode)
            .'&sort='.$sort
        );
        exit;
}

$limit = 20;
$from = $page * $limit;
$isWesternNameOrder = api_is_western_name_order();
$accessUrlRelUserTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
$accessUrlId = api_get_current_access_url_id();

$sql = "SELECT DISTINCT
            u.id AS user_id, "
        .($isWesternNameOrder ? 'u.firstname, u.lastname' : 'u.lastname, u.firstname')
        .", u.username, scru.user_id AS is_subscribed
        FROM $sessionRelUserTable s
        INNER JOIN $userTable u ON u.id = s.user_id
        INNER JOIN $accessUrlRelUserTable url ON url.user_id = u.id
        LEFT JOIN $sessionRelCourseRelUserTable scru
            ON s.session_id = scru.session_id
            AND s.user_id = scru.user_id
            AND scru.c_id = $courseId
        WHERE s.session_id = $sessionId
          AND url.access_url_id = $accessUrlId
        ORDER BY `$sort` $queryDirection
        LIMIT $from, ".($limit + 1);
$result = Database::query($sql);
$userRows = Database::store_result($result);
$hasNextPage = count($userRows) > $limit;
$userRows = array_slice($userRows, 0, $limit);

$toolName = get_lang('Users');
$sessionListUrl = '/admin/session-list';
$sessionOverviewUrl = 'resume_session.php?id_session='.$sessionId;
$courseListUrl = 'session_course_list.php?id_session='.$sessionId;
$courseHomeUrl = api_get_course_url($courseId, $sessionId);
$selfUrl = api_get_self().'?id_session='.$sessionId.'&course_code='.urlencode($courseCode);

$interbreadcrumb[] = ['url' => $sessionListUrl, 'name' => get_lang('Session list')];
$interbreadcrumb[] = ['url' => $sessionOverviewUrl, 'name' => get_lang('Session overview')];
$interbreadcrumb[] = ['url' => $courseListUrl, 'name' => get_lang('Courses in this session')];
$interbreadcrumb[] = ['url' => $courseHomeUrl, 'name' => api_htmlentities($courseTitle, ENT_QUOTES)];

Display::display_header($toolName);

$toolbarActions = '<div class="flex items-center gap-3">';
$toolbarActions .= '<a href="'.$courseListUrl.'" class="inline-flex items-center" aria-label="'.api_htmlentities(get_lang('Back'), ENT_QUOTES).'">'
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

echo '<form method="post" action="'.$selfUrl.'&sort='.$sort.'&direction='.$queryDirection.'" class="space-y-4" onsubmit="return confirm(\''.addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES)).'\');">';
echo '  <div class="overflow-hidden rounded-lg border border-gray-30 bg-white shadow-sm">';
echo '    <div class="overflow-x-auto">';
echo '      <table class="min-w-full divide-y divide-gray-20 text-sm">';
echo '        <thead class="bg-gray-10 text-left text-gray-70">';
echo '          <tr>';
echo '            <th class="w-12 px-4 py-3"><span class="sr-only">'.get_lang('Select').'</span></th>';

$firstNameHeader = '<a class="hover:underline" href="'.$selfUrl.'&sort=firstname&direction='.$nextDirection.'">'.get_lang('First name').'</a>';
$lastNameHeader = '<a class="hover:underline" href="'.$selfUrl.'&sort=lastname&direction='.$nextDirection.'">'.get_lang('Last name').'</a>';

if ($isWesternNameOrder) {
    echo '        <th class="px-4 py-3 font-semibold">'.$firstNameHeader.'</th>';
    echo '        <th class="px-4 py-3 font-semibold">'.$lastNameHeader.'</th>';
} else {
    echo '        <th class="px-4 py-3 font-semibold">'.$lastNameHeader.'</th>';
    echo '        <th class="px-4 py-3 font-semibold">'.$firstNameHeader.'</th>';
}

echo '            <th class="px-4 py-3 font-semibold"><a class="hover:underline" href="'.$selfUrl.'&sort=username&direction='.$nextDirection.'">'.get_lang('Login').'</a></th>';
echo '            <th class="px-4 py-3 font-semibold text-right">'.get_lang('Detail').'</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody class="divide-y divide-gray-20 bg-white">';

if (empty($userRows)) {
    echo '      <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-50">'.get_lang('No user').'</td></tr>';
} else {
    foreach ($userRows as $userRow) {
        $userId = (int) $userRow['user_id'];
        $rowAction = !empty($userRow['is_subscribed']) ? 'delete' : 'add';
        $rowActionLabel = 'delete' === $rowAction ? get_lang('Delete') : get_lang('Add');
        $rowActionIcon = 'delete' === $rowAction ? ActionIcon::DELETE : ActionIcon::ADD;
        $rowActionUrl = $selfUrl.'&sort='.$sort.'&direction='.$queryDirection.'&action='.$rowAction.'&idChecked[]='.$userId;

        echo '  <tr class="hover:bg-gray-10">';
        echo '    <td class="px-4 py-3 align-middle"><input class="rounded border-gray-30" type="checkbox" name="idChecked[]" value="'.$userId.'"></td>';

        if ($isWesternNameOrder) {
            echo '  <td class="px-4 py-3 align-middle text-gray-90">'.api_htmlentities($userRow['firstname'], ENT_QUOTES).'</td>';
            echo '  <td class="px-4 py-3 align-middle text-gray-90">'.api_htmlentities($userRow['lastname'], ENT_QUOTES).'</td>';
        } else {
            echo '  <td class="px-4 py-3 align-middle text-gray-90">'.api_htmlentities($userRow['lastname'], ENT_QUOTES).'</td>';
            echo '  <td class="px-4 py-3 align-middle text-gray-90">'.api_htmlentities($userRow['firstname'], ENT_QUOTES).'</td>';
        }

        echo '    <td class="px-4 py-3 align-middle text-gray-70">'.api_htmlentities($userRow['username'], ENT_QUOTES).'</td>';
        echo '    <td class="px-4 py-3 align-middle text-right"><a href="'.$rowActionUrl.'" aria-label="'.api_htmlentities($rowActionLabel, ENT_QUOTES).'" onclick="return confirm(\''.addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES)).'\');">'.Display::getMdiIcon($rowActionIcon, 'ch-tool-icon', null, ICON_SIZE_SMALL, $rowActionLabel).'</a></td>';
        echo '  </tr>';
    }
}

echo '        </tbody>';
echo '      </table>';
echo '    </div>';
echo '    <div class="flex flex-col gap-3 border-t border-gray-20 bg-gray-10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">';
echo '      <div class="flex flex-col gap-2 sm:flex-row sm:items-center">';
echo '        <select name="action" class="rounded-md border border-gray-30 bg-white px-3 py-2 text-sm text-gray-90">';
echo '          <option value="delete">'.get_lang('Unsubscribe selected users from session').'</option>';
echo '          <option value="add">'.get_lang('Add a user').'</option>';
echo '        </select>';
echo '        <button class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90" type="submit">'.get_lang('Validate').'</button>';
echo '      </div>';
echo '      <div class="flex items-center justify-end gap-3 text-sm">';
if ($page > 0) {
    echo '    <a class="font-medium text-primary hover:underline" href="'.$selfUrl.'&sort='.$sort.'&direction='.$queryDirection.'&page='.($page - 1).'">'.get_lang('Previous').'</a>';
} else {
    echo '    <span class="text-gray-40">'.get_lang('Previous').'</span>';
}
if ($hasNextPage) {
    echo '    <a class="font-medium text-primary hover:underline" href="'.$selfUrl.'&sort='.$sort.'&direction='.$queryDirection.'&page='.($page + 1).'">'.get_lang('Next').'</a>';
} else {
    echo '    <span class="text-gray-40">'.get_lang('Next').'</span>';
}
echo '      </div>';
echo '    </div>';
echo '  </div>';
echo '</form>';
echo '</div>';

Display::display_footer();
