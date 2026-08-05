<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\PluginBundle\StudentFollowUp\Entity\CarePost;
use Doctrine\ORM\Tools\Pagination\Paginator;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();

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
$canCreatePost = $isAllow && $studentId !== $currentUserId;

if (false === $isAllow) {
    api_not_allowed(true);
}

StudentFollowUpPlugin::normalizeLegacyTags();

$em = Database::getManager();
$qb = $em->createQueryBuilder();

$pageSize = StudentFollowUpPlugin::getPageSize();

$qb
    ->select('p')
    ->distinct()
    ->from(CarePost::class, 'p')
    ->andWhere('IDENTITY(p.user) = :studentId')
    ->setParameter('studentId', $studentId)
    ->setFirstResult($pageSize * ($currentPage - 1))
    ->setMaxResults($pageSize)
    ->orderBy('p.createdAt', 'desc')
;

if (false === $showPrivate) {
    $qb
        ->andWhere('p.private = :private')
        ->setParameter('private', false)
    ;
}

$posts = new Paginator($qb->getQuery(), true);

$totalItems = count($posts);
$pagesCount = (int) ceil($totalItems / $pageSize);

$pagination = '';
$url = api_get_self().'?student_id='.$studentId;
if ($totalItems > 1 && $pagesCount > 1) {
    $pagination .= '<ul class="pagination">';
    for ($i = 0; $i < $pagesCount; $i++) {
        $newPage = $i + 1;
        if ($currentPage === $newPage) {
            $pagination .= '<li class="active"><a href="'.$url.'&page='.$newPage.'">'.$newPage.'</a></li>';
        } else {
            $pagination .= '<li><a href="'.$url.'&page='.$newPage.'">'.$newPage.'</a></li>';
        }
    }
    $pagination .= '</ul>';
}

$tpl = new Template($plugin->get_lang('plugin_title'));
$tpl->assign('posts', $posts);
$tpl->assign('current_url', $url);
$tpl->assign('my_students_url', api_get_path(WEB_PLUGIN_PATH).'StudentFollowUp/my_students.php');

$url = api_get_path(WEB_PLUGIN_PATH).'StudentFollowUp/post.php?student_id='.$studentId;
$tpl->assign('post_url', $url);
$tpl->assign('create_post_url', $url.'&action=create');
$tpl->assign('can_create_post', $canCreatePost);
$tpl->assign('information_icon', Display::getMdiIcon(ActionIcon::INFORMATION, 'ch-tool-icon', null, ICON_SIZE_SMALL));
$tpl->assign('student_info', api_get_user_info($studentId));
$tpl->assign('pagination', $pagination);
$tpl->assign('care_title', $plugin->get_lang('CareDetailView'));
$content = $tpl->fetch('/'.$plugin->get_name().'/view/posts.html.twig');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
