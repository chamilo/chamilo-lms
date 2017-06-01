<?php

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Tools\Pagination\Paginator;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();

$plugin = StudentFollowUpPlugin::create();

$currentUserId = api_get_user_id();
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$totalItems = 0;
$items = [];
$showPrivate = false;
$pageSize = StudentFollowUpPlugin::getPageSize();
$firstResults = $pageSize * ($currentPage - 1);

$userList = [];
if (!api_is_platform_admin()) {
    $status = COURSEMANAGER;
    if (api_is_drh()) {
        $status = DRH;
    }
    $userList = StudentFollowUpPlugin::getUsers(
        $status,
        $currentUserId,
        $firstResults,
        $pageSize
    );
}

if (!empty($userList) || api_is_platform_admin()) {
    $em = Database::getManager();
    $qb = $em->createQueryBuilder();
    $criteria = Criteria::create();
    if (!api_is_platform_admin()) {
        $criteria->where(Criteria::expr()->in('user', $userList));
    }

    if ($showPrivate == false) {
        $criteria->andWhere(Criteria::expr()->eq('private', false));
    }

    $qb
        ->select('p')
        ->distinct()
        ->from('ChamiloPluginBundle:StudentFollowUp\CarePost', 'p')
        ->join('p.user', 'u')
        ->addCriteria($criteria)
        ->setFirstResult($firstResults)
        ->setMaxResults($pageSize)
        ->groupBy('p.user')
        ->orderBy('p.createdAt', 'desc')
    ;

    if (!empty($keyword)) {
        $keyword = explode(' ', $keyword);
        if (is_array($keyword)) {
            foreach ($keyword as $key) {
                $key = trim($key);
                //$key = api_replace_dangerous_char($key);
                if (empty($key)) {
                    continue;
                }
                $qb
                    ->andWhere('u.firstname LIKE :keyword OR u.lastname LIKE :keyword OR u.username LIKE :keyword')
                    ->setParameter('keyword', "%$key%")
                ;
            }
        } else {
            $qb
                ->andWhere('u.firstname LIKE :keyword OR u.lastname LIKE :keyword OR u.username LIKE :keyword')
                ->setParameter('keyword', "%$keyword%")
            ;
        }

    }

    $query = $qb->getQuery();
    $items = new Paginator($query, $fetchJoinCollection = true);
    $totalItems = count($items);
    $pagesCount = ceil($totalItems / $pageSize);
}

$pagination = '';
$url = api_get_self().'?';
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

// Create a search-box
$form = new FormValidator('search_simple', 'get', null, null, null, 'inline');
$form->addText(
    'keyword',
    get_lang('Search'),
    false,
    array(
        'aria-label' => get_lang("SearchUsers")
    )
);
$form->addButtonSearch(get_lang('Search'));

$tpl = new Template($plugin->get_lang('plugin_title'));
$tpl->assign('users', $items);
$tpl->assign('form', $form->returnForm());
$url = api_get_path(WEB_PLUGIN_PATH).'studentfollowup/posts.php?';
$tpl->assign('post_url', $url);
$url = api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?';
$tpl->assign('my_students_url', $url);
$tpl->assign('pagination', $pagination);
$tpl->assign('care_title', $plugin->get_lang('CareDetailView'));
$content = $tpl->fetch('/'.$plugin->get_name().'/view/my_students.html.twig');
// Assign into content
$tpl->assign('content', $content);
// Display
$tpl->display_one_col_template();
