<?php
/* For licensing terms, see /license.txt */

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Tools\Pagination\Paginator;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = StudentFollowUpPlugin::create();

$currentUserId = api_get_user_id();
$studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : api_get_user_id();
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$postId = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 1;

if (empty($studentId)) {
    api_not_allowed(true);
}

$isAllow = false;
$showPrivate = false;
if ($studentId === $currentUserId) {
    $isAllow = true;
} else {
    // Only admins and DRH that follow the user
    $isAdminOrDrh = UserManager::is_user_followed_by_drh($studentId, $currentUserId) || api_is_platform_admin();

    // Check if course session coach
    $sessions = SessionManager::get_sessions_by_user($studentId);

    $isCourseCoach = false;
    if (!empty($sessions)) {
        foreach ($sessions as $session) {
            $sessionId = $session['session_id'];
            foreach ($session['courses'] as $course) {
                //$isCourseCoach = api_is_coach($sessionId, $course['real_id']);
                $coachList = SessionManager::getCoachesByCourseSession($sessionId, $course['real_id']);
                if (!empty($coachList) && in_array($currentUserId, $coachList)) {
                    $isCourseCoach = true;
                    break(2);
                }
            }
        }
    }

    $isAllow = $isAdminOrDrh || $isCourseCoach;
    $showPrivate = $isAdminOrDrh;
}

if ($isAllow === false) {
    api_not_allowed(true);
}

$em = Database::getManager();
$qb = $em->createQueryBuilder();
$criteria = Criteria::create();
$criteria->where(Criteria::expr()->eq('user', $studentId));

if ($showPrivate == false) {
    $criteria->andWhere(Criteria::expr()->eq('private', false));
}

if (!empty($postId)) {
    //$criteria->andWhere(Criteria::expr()->eq('private', false));
}

$pageSize = 2;

$qb
    ->select('p')
    ->from('ChamiloPluginBundle:StudentFollowUp\CarePost', 'p')
    ->addCriteria($criteria)
    ->setFirstResult($pageSize * ($currentPage-1))
    ->setMaxResults($pageSize)
    ->orderBy('p.createdAt', 'desc')
;

$query = $qb->getQuery();

$posts = new Paginator($query, $fetchJoinCollection = true);

$totalItems = count($posts);
$pagesCount = ceil($totalItems / $pageSize);
$pagination = '';
$url = api_get_self().'?student_id='.$studentId;
$pagination .= '<ul class="pagination">';
for ($i = 0; $i < $pagesCount; $i++) {
    $newPage = $i + 1;
    if ($currentPage == $newPage) {
        $pagination .= '<li class="active"><a href="'.$url.'&page='.$newPage.'">'.$newPage.'</a></li>';
    } else {
        $pagination .= '<li><a href="'.$url.'&page='.$newPage.'">'.$newPage.'</a></li>';
    }
}
$pagination .= '</ul>';

$tpl = new Template($plugin->get_lang('plugin_title'));
$tpl->assign('posts', $posts);
$tpl->assign('current_url', $url);

$tpl->assign('pagination', $pagination);
$content = $tpl->fetch('/'.$plugin->get_name().'/view/post.html.twig');
// Assign into content
$tpl->assign('content', $content);
// Display
$tpl->display_one_col_template();
