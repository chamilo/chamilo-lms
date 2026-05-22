<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\Tools\Pagination\Paginator;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();

$plugin = StudentFollowUpPlugin::create();

$currentUserId = api_get_user_id();
$currentPage = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;
$keyword = isset($_REQUEST['keyword']) ? Security::remove_XSS($_REQUEST['keyword']) : '';
$sessionId = isset($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : 0;

$pageSize = StudentFollowUpPlugin::getPageSize();
$firstResults = $pageSize * ($currentPage - 1);
$totalItems = 0;
$pagesCount = 0;
$items = [];
$isAdmin = api_is_platform_admin();

$userList = [];
$fullSessionList = [];

if (!$isAdmin) {
    $status = COURSEMANAGER;
    if (api_is_drh()) {
        $status = DRH;
    }

    $data = StudentFollowUpPlugin::getUsers(
        $status,
        $currentUserId,
        $sessionId,
        0,
        0
    );
    $userList = array_map('intval', $data['users']);
    $fullSessionList = $data['sessions'];
} else {
    $fullSessionList = SessionManager::getSessionsCoachedByUser($currentUserId);
}

if (!empty($sessionId)) {
    $sessionUserList = SessionManager::get_users_by_session($sessionId);
    $userList = array_map('intval', array_column($sessionUserList, 'user_id'));
}

if (!empty($userList) || $isAdmin) {
    $em = Database::getManager();
    $qb = $em->createQueryBuilder();

    $qb
        ->select('u')
        ->from(User::class, 'u')
        ->andWhere('u.status = :studentStatus')
        ->setParameter('studentStatus', STUDENT)
        ->setFirstResult($firstResults)
        ->setMaxResults($pageSize)
        ->orderBy('u.lastname', 'ASC')
        ->addOrderBy('u.firstname', 'ASC')
        ->addOrderBy('u.username', 'ASC')
    ;

    if (!$isAdmin) {
        $qb
            ->andWhere('u.id IN (:userList)')
            ->setParameter('userList', $userList)
        ;
    }

    if (!empty($keyword)) {
        $keywordToArray = explode(' ', $keyword);
        foreach ($keywordToArray as $index => $key) {
            $key = trim($key);
            if (empty($key)) {
                continue;
            }

            $parameterName = 'keyword'.$index;
            $qb
                ->andWhere(
                    'u.firstname LIKE :'.$parameterName.
                    ' OR u.lastname LIKE :'.$parameterName.
                    ' OR u.username LIKE :'.$parameterName.
                    ' OR u.email LIKE :'.$parameterName
                )
                ->setParameter($parameterName, '%'.$key.'%')
            ;
        }
    }

    $items = new Paginator($qb->getQuery());

    $totalItems = $items->count();
    $pagesCount = (int) ceil($totalItems / $pageSize);
}

$pagination = '';
$url = api_get_self().'?session_id='.$sessionId.'&keyword='.urlencode($keyword).'&';
if ($totalItems > 1 && $pagesCount > 1) {
    $pagination .= '<nav aria-label="'.htmlspecialchars(get_lang('Pagination'), ENT_QUOTES, 'UTF-8').'">';
    $pagination .= '<ul class="flex flex-wrap items-center gap-2">';

    for ($i = 0; $i < $pagesCount; $i++) {
        $newPage = $i + 1;
        $pageUrl = htmlspecialchars($url.'page='.$newPage, ENT_QUOTES, 'UTF-8');

        if ($currentPage === $newPage) {
            $pagination .= '<li>';
            $pagination .= '<a aria-current="page" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg bg-primary px-3 text-sm font-semibold text-white shadow-sm" href="'.$pageUrl.'">'.$newPage.'</a>';
            $pagination .= '</li>';
            continue;
        }

        $pagination .= '<li>';
        $pagination .= '<a class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-25 bg-white px-3 text-sm font-semibold text-primary shadow-sm hover:bg-primary/10" href="'.$pageUrl.'">'.$newPage.'</a>';
        $pagination .= '</li>';
    }

    $pagination .= '</ul>';
    $pagination .= '</nav>';
}

$form = new FormValidator('search_simple', 'get', null, null, null, FormValidator::LAYOUT_HORIZONTAL);
$form->addText(
    'keyword',
    get_lang('Search'),
    false,
    [
        'aria-label' => get_lang('Search users'),
    ]
);

if (!empty($fullSessionList)) {
    $options = array_column($fullSessionList, 'title', 'id');
    $options[0] = get_lang('Please select an option');
    ksort($options);
    $form->addSelect('session_id', get_lang('Session'), $options);
}

$form->addButtonSearch(get_lang('Search'));

$defaults = [
    'session_id' => $sessionId,
    'keyword' => $keyword,
];

$form->setDefaults($defaults);

$tpl = new Template($plugin->get_lang('plugin_title'));
$tpl->assign('users', $items);
$tpl->assign('form', $form->returnForm());
$tpl->assign('post_url', api_get_path(WEB_PLUGIN_PATH).'StudentFollowUp/posts.php?');
$tpl->assign('my_students_url', api_get_path(WEB_CODE_PATH).'my_space/myStudents.php?');
$tpl->assign('reporting_url', api_get_path(WEB_CODE_PATH).'my_space/index.php');
$tpl->assign('pagination', $pagination);
$tpl->assign('care_title', $plugin->get_lang('CareDetailView'));
$content = $tpl->fetch('/'.$plugin->get_name().'/view/my_students.html.twig');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
