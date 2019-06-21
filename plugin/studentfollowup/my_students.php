<?php
/* For licensing terms, see /license.txt */

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Tools\Pagination\Paginator;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();

$plugin = StudentFollowUpPlugin::create();

$currentUserId = api_get_user_id();
$currentPage = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;
$keyword = isset($_REQUEST['keyword']) ? Security::remove_XSS($_REQUEST['keyword']) : '';
$sessionId = isset($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : 0;
$selectedTag = isset($_REQUEST['tag']) ? Security::remove_XSS($_REQUEST['tag']) : '';

$totalItems = 0;
$items = [];
$tags = [];
$showPrivate = false;

$pageSize = StudentFollowUpPlugin::getPageSize();
$firstResults = $pageSize * ($currentPage - 1);
$pagesCount = 0;
$isAdmin = api_is_platform_admin();

$userList = [];
if (!$isAdmin) {
    $status = COURSEMANAGER;
    if (api_is_drh()) {
        $status = DRH;
    }
    $data = StudentFollowUpPlugin::getUsers(
        $status,
        $currentUserId,
        $sessionId,
        $firstResults,
        $pageSize
    );
    $userList = $data['users'];
    $fullSessionList = $data['sessions'];
} else {
    $fullSessionList = SessionManager::getSessionsCoachedByUser($currentUserId);
}

if (!empty($sessionId)) {
    $userList = SessionManager::get_users_by_session($sessionId);
    $userList = array_column($userList, 'user_id');
}
$tagList = [];

if (!empty($userList) || $isAdmin) {
    $em = Database::getManager();
    $qb = $em->createQueryBuilder();
    $criteria = Criteria::create();

    if (!$isAdmin) {
        $criteria->where(Criteria::expr()->in('user', $userList));
    }

    if (!empty($sessionId)) {
        $criteria->where(Criteria::expr()->in('user', $userList));
    }

    if ($showPrivate === false) {
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
        $keywordToArray = explode(' ', $keyword);
        if (is_array($keywordToArray)) {
            foreach ($keywordToArray as $key) {
                $key = trim($key);
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

    $queryBuilderOriginal = clone $qb;

    if (!empty($selectedTag)) {
        $qb->andWhere('p.tags LIKE :tags ');
        $qb->setParameter('tags', "%$selectedTag%");
    }

    $query = $qb->getQuery();

    $items = new Paginator($query);

    $queryBuilderOriginal->select('p.tags')
        ->distinct(false)
        ->setFirstResult(null)
        ->setMaxResults(null)
        ->groupBy('p.id')
    ;

    $tags = $queryBuilderOriginal->getQuery()->getResult();
    //var_dump($queryBuilderOriginal->getQuery()->getSQL());
    $tagList = [];
    foreach ($tags as $tag) {
        $itemTags = $tag['tags'];
        foreach ($itemTags as $itemTag) {
            if (in_array($itemTag, array_keys($tagList))) {
                $tagList[$itemTag]++;
            } else {
                $tagList[$itemTag] = 1;
            }
        }
    }

    $totalItems = $items->count();
    $pagesCount = ceil($totalItems / $pageSize);
}

$pagination = '';
$url = api_get_self().'?session_id='.$sessionId.'&tag='.$selectedTag.'&keyword='.$keyword.'&';
if ($totalItems > 1 && $pagesCount > 1) {
    $pagination .= '<ul class="pagination">';
    for ($i = 0; $i < $pagesCount; $i++) {
        $newPage = $i + 1;
        if ($currentPage == $newPage) {
            $pagination .= '<li class="active"><a href="'.$url.'page='.$newPage.'">'.$newPage.'</a></li>';
        } else {
            $pagination .= '<li><a href="'.$url.'page='.$newPage.'">'.$newPage.'</a></li>';
        }
    }
    $pagination .= '</ul>';
}

// Create a search-box
$form = new FormValidator('search_simple', 'get', null, null, null, FormValidator::LAYOUT_HORIZONTAL);
$form->addText(
    'keyword',
    get_lang('Search'),
    false,
    [
        'aria-label' => get_lang('SearchUsers'),
    ]
);

if (!empty($fullSessionList)) {
    $options = array_column($fullSessionList, 'name', 'id');
    $options[0] = get_lang('SelectAnOption');
    ksort($options);
    $form->addSelect('session_id', get_lang('Session'), $options);
}

if (!empty($tagList)) {
    $tagOptions = [];
    arsort($tagList);
    foreach ($tagList as $tag => $counter) {
        $tagOptions[$tag] = $tag.' ('.$counter.')';
    }
    $form->addSelect('tag', get_lang('Tags'), $tagOptions, ['placeholder' => get_lang('SelectAnOption')]);
}

$form->addButtonSearch(get_lang('Search'));

$defaults = [
    'session_id' => $sessionId,
    'keyword' => $keyword,
    'tag' => $selectedTag,
];

$form->setDefaults($defaults);

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
$tpl->assign('content', $content);
$tpl->display_one_col_template();
