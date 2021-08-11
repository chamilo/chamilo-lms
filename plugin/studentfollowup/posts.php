<?php
/* For licensing terms, see /license.txt */

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Tools\Pagination\Paginator;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = StudentFollowUpPlugin::create();

$currentUserId = api_get_user_id();
$studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : api_get_user_id();
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;

if (empty($studentId)) {
    api_not_allowed(true);
}
$permissions = StudentFollowUpPlugin::getPermissions($studentId, $currentUserId);
$isAllow = $permissions['is_allow'];
$showPrivate = $permissions['show_private'];

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

$pageSize = StudentFollowUpPlugin::getPageSize();

$qb
    ->select('p')
    ->distinct()
    ->from('ChamiloPluginBundle:StudentFollowUp\CarePost', 'p')
    ->addCriteria($criteria)
    ->setFirstResult($pageSize * ($currentPage - 1))
    ->setMaxResults($pageSize)
    ->orderBy('p.createdAt', 'desc')
;

$query = $qb->getQuery();
$posts = new Paginator($query, $fetchJoinCollection = true);

$totalItems = count($posts);
$pagesCount = ceil($totalItems / $pageSize);

$pagination = '';
$url = api_get_self().'?student_id='.$studentId;
if ($totalItems > 1) {
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
}

$showFullPage = isset($_REQUEST['iframe']) && 1 === (int) $_REQUEST['iframe'] ? false : true;

$tpl = new Template($plugin->get_lang('plugin_title'), $showFullPage, $showFullPage, !$showFullPage);
$tpl->assign('posts', $posts);
$tpl->assign('current_url', $url);

$url = api_get_path(WEB_PLUGIN_PATH).'studentfollowup/post.php?student_id='.$studentId;
$tpl->assign('post_url', $url);
$tpl->assign('information_icon', Display::return_icon('info.png'));
$tpl->assign('student_info', api_get_user_info($studentId));
$tpl->assign('pagination', $pagination);
$tpl->assign('care_title', $plugin->get_lang('CareDetailView'));
$content = $tpl->fetch('/'.$plugin->get_name().'/view/posts.html.twig');
// Assign into content
$tpl->assign('content', $content);
// Display
$tpl->display_one_col_template();
