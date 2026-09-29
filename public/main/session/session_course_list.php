<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

$sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;
$session = api_get_session_entity($sessionId);
SessionManager::protectSession($session);

if (empty($sessionId)) {
    api_not_allowed();
}

$courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
$sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
$sessionRelCourseTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$sessionRelCourseRelUserTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$page = max(0, isset($_GET['page']) ? (int) $_GET['page'] : 0);
$action = $_REQUEST['action'] ?? '';
$sort = isset($_GET['sort']) && in_array($_GET['sort'], ['title', 'nbr_users'], true)
    ? $_GET['sort']
    : 'title';
$sortColumn = 'nbr_users' === $sort ? 'src.nbr_users' : 'c.title';

if ('delete' === $action) {
    $checkedIds = $_REQUEST['idChecked'] ?? [];

    if (is_array($checkedIds) && !empty($checkedIds)) {
        $courseIds = array_values(array_unique(array_filter(array_map('intval', $checkedIds))));

        if (!empty($courseIds)) {
            $courseIdList = implode(',', $courseIds);
            $result = Database::query(
                "DELETE FROM $sessionRelCourseTable
                 WHERE session_id = $sessionId AND c_id IN ($courseIdList)"
            );
            $affectedRows = Database::affected_rows($result);

            Database::query(
                "DELETE FROM $sessionRelCourseRelUserTable
                 WHERE session_id = $sessionId AND c_id IN ($courseIdList)"
            );
            Database::query(
                "UPDATE $sessionTable
                 SET nbr_courses = GREATEST(0, nbr_courses - $affectedRows)
                 WHERE id = $sessionId"
            );

            Display::addFlash(Display::return_message(get_lang('Update successful')));
        }
    }

    header('Location: '.api_get_self().'?id_session='.$sessionId.'&sort='.$sort);
    exit;
}

$limit = 20;
$from = $page * $limit;
$sql = "SELECT
            c.id,
            c.code,
            c.title,
            src.nbr_users,
            r.title AS room_title,
            b.title AS branch_title
        FROM $sessionRelCourseTable src
        INNER JOIN $courseTable c ON src.c_id = c.id
        LEFT JOIN room r ON src.room_id = r.id
        LEFT JOIN branch_sync b ON r.branch_id = b.id
        WHERE src.session_id = $sessionId
        ORDER BY $sortColumn
        LIMIT $from, ".($limit + 1);
$result = Database::query($sql);
$courseRows = Database::store_result($result);
$hasNextPage = count($courseRows) > $limit;
$courseRows = array_slice($courseRows, 0, $limit);

$toolName = get_lang('Courses in this session');
$sessionTitle = (string) $session->getTitle();
$sessionListUrl = '/admin/session-list';
$sessionOverviewUrl = 'resume_session.php?id_session='.$sessionId;
$addCoursesUrl = 'add_courses_to_session.php?page=resume_session.php&id_session='.$sessionId;

$interbreadcrumb[] = ['url' => $sessionListUrl, 'name' => get_lang('Session list')];
$interbreadcrumb[] = ['url' => $sessionOverviewUrl, 'name' => get_lang('Session overview')];

Display::display_header($toolName);

$toolbarActions = '<div class="flex items-center gap-3">';
$toolbarActions .= '<a href="'.$sessionOverviewUrl.'" class="inline-flex items-center" aria-label="'.api_htmlentities(get_lang('Back'), ENT_QUOTES).'">'
    .Display::getMdiIcon('arrow-left', 'ch-tool-icon-gradient', null, 32, get_lang('Back')).'</a>';
$toolbarActions .= '<a href="'.$addCoursesUrl.'" class="inline-flex items-center" aria-label="'.api_htmlentities(get_lang('Add courses to this session'), ENT_QUOTES).'">'
    .Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon-gradient', null, 32, get_lang('Add courses to this session')).'</a>';
$toolbarActions .= '<a href="'.$sessionListUrl.'" class="inline-flex items-center" aria-label="'.api_htmlentities(get_lang('Session list'), ENT_QUOTES).'">'
    .Display::getMdiIcon('format-list-bulleted', 'ch-tool-icon-gradient', null, 32, get_lang('Session list')).'</a>';
$toolbarActions .= '</div>';

echo '<div class="mx-auto w-full space-y-4 p-4">';
echo '  <div class="rounded-lg border border-gray-30 bg-white p-4 shadow-sm">';
echo '    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">';
echo '      <div class="min-w-0">';
echo '        <h1 class="text-xl font-semibold text-gray-90">'.api_htmlentities($toolName, ENT_QUOTES).'</h1>';
echo '        <p class="mt-1 text-sm text-gray-50">'.api_htmlentities($sessionTitle, ENT_QUOTES).'</p>';
echo '      </div>';
echo        $toolbarActions;
echo '    </div>';
echo '  </div>';

echo '<form method="post" action="'.api_get_self().'?id_session='.$sessionId.'&sort='.$sort.'" class="space-y-4" onsubmit="return confirm(\''.addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES)).'\');">';
echo '  <div class="overflow-hidden rounded-lg border border-gray-30 bg-white shadow-sm">';
echo '    <div class="overflow-x-auto">';
echo '      <table class="min-w-full divide-y divide-gray-20 text-sm">';
echo '        <thead class="bg-gray-10 text-left text-gray-70">';
echo '          <tr>';
echo '            <th class="w-12 px-4 py-3"><span class="sr-only">'.get_lang('Select').'</span></th>';
echo '            <th class="px-4 py-3 font-semibold"><a class="hover:underline" href="'.api_get_self().'?id_session='.$sessionId.'&sort=title">'.get_lang('Course title').'</a></th>';
echo '            <th class="px-4 py-3 font-semibold"><a class="hover:underline" href="'.api_get_self().'?id_session='.$sessionId.'&sort=nbr_users">'.get_lang('Users').'</a></th>';
echo '            <th class="px-4 py-3 font-semibold">'.get_lang('Room').'</th>';
echo '            <th class="px-4 py-3 font-semibold text-right">'.get_lang('Detail').'</th>';
echo '          </tr>';
echo '        </thead>';
echo '        <tbody class="divide-y divide-gray-20 bg-white">';

if (empty($courseRows)) {
    echo '      <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-50">'.get_lang('No course for this session').'</td></tr>';
} else {
    foreach ($courseRows as $courseRow) {
        $courseId = (int) $courseRow['id'];
        $courseCode = (string) $courseRow['code'];
        $courseHomeUrl = api_get_path(WEB_COURSE_PATH).$courseId.'/home?sid='.$sessionId;
        $usersUrl = 'session_course_user_list.php?id_session='.$sessionId.'&course_code='.urlencode($courseCode);
        $editUrl = 'session_course_edit.php?id_session='.$sessionId.'&page=session_course_list.php&course_code='.urlencode($courseCode);
        $deleteUrl = api_get_self().'?id_session='.$sessionId.'&sort='.$sort.'&action=delete&idChecked[]='.$courseId;
        $roomLabel = '-';

        if (!empty($courseRow['room_title'])) {
            $roomLabel = trim((!empty($courseRow['branch_title']) ? $courseRow['branch_title'].' — ' : '').$courseRow['room_title']);
        }

        echo '  <tr class="hover:bg-gray-10">';
        echo '    <td class="px-4 py-3 align-middle"><input class="rounded border-gray-30" type="checkbox" name="idChecked[]" value="'.$courseId.'"></td>';
        echo '    <td class="px-4 py-3 align-middle font-medium text-gray-90"><a class="hover:underline" href="'.$courseHomeUrl.'">'.api_htmlentities($courseRow['title'], ENT_QUOTES).'</a></td>';
        echo '    <td class="px-4 py-3 align-middle"><a class="text-primary hover:underline" href="'.$usersUrl.'">'.(int) $courseRow['nbr_users'].'</a></td>';
        echo '    <td class="px-4 py-3 align-middle text-gray-70">'.api_htmlentities($roomLabel, ENT_QUOTES).'</td>';
        echo '    <td class="px-4 py-3 align-middle">';
        echo '      <div class="flex items-center justify-end gap-2">';
        echo '        <a href="'.$courseHomeUrl.'" aria-label="'.api_htmlentities(get_lang('Course'), ENT_QUOTES).'">'.Display::getMdiIcon(ObjectIcon::HOME, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Course')).'</a>';
        echo '        <a href="'.$usersUrl.'" aria-label="'.api_htmlentities(get_lang('Users'), ENT_QUOTES).'">'.Display::getMdiIcon('account-multiple', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Users')).'</a>';
        echo '        <a href="'.$editUrl.'" aria-label="'.api_htmlentities(get_lang('Edit'), ENT_QUOTES).'">'.Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')).'</a>';
        echo '        <a href="'.$deleteUrl.'" aria-label="'.api_htmlentities(get_lang('Delete'), ENT_QUOTES).'" onclick="return confirm(\''.addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES)).'\');">'.Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')).'</a>';
        echo '      </div>';
        echo '    </td>';
        echo '  </tr>';
    }
}

echo '        </tbody>';
echo '      </table>';
echo '    </div>';

echo '    <div class="flex flex-col gap-3 border-t border-gray-20 bg-gray-10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">';
echo '      <div class="flex flex-col gap-2 sm:flex-row sm:items-center">';
echo '        <select name="action" class="rounded-md border border-gray-30 bg-white px-3 py-2 text-sm text-gray-90">';
echo '          <option value="delete">'.get_lang('Unsubscribe selected courses from this session').'</option>';
echo '        </select>';
echo '        <button class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90" type="submit">'.get_lang('Validate').'</button>';
echo '      </div>';

echo '      <div class="flex items-center justify-end gap-3 text-sm">';
if ($page > 0) {
    echo '    <a class="font-medium text-primary hover:underline" href="'.api_get_self().'?id_session='.$sessionId.'&sort='.$sort.'&page='.($page - 1).'">'.get_lang('Previous').'</a>';
} else {
    echo '    <span class="text-gray-40">'.get_lang('Previous').'</span>';
}
if ($hasNextPage) {
    echo '    <a class="font-medium text-primary hover:underline" href="'.api_get_self().'?id_session='.$sessionId.'&sort='.$sort.'&page='.($page + 1).'">'.get_lang('Next').'</a>';
} else {
    echo '    <span class="text-gray-40">'.get_lang('Next').'</span>';
}
echo '      </div>';
echo '    </div>';
echo '  </div>';
echo '</form>';
echo '</div>';

Display::display_footer();
