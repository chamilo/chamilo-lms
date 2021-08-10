<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\StudentFollowUp\CarePost;
use Doctrine\Common\Collections\Criteria;
use Gaufrette\Adapter\Ftp as FtpAdapter;
use Gaufrette\Filesystem;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = StudentFollowUpPlugin::create();

$currentUserId = api_get_user_id();
$studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : api_get_user_id();
$postId = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 1;
$action = isset($_GET['action']) ? $_GET['action'] : '';

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

$criteria->andWhere(Criteria::expr()->eq('id', $postId));
$qb
    ->select('distinct p')
    ->from('ChamiloPluginBundle:StudentFollowUp\CarePost', 'p')
    ->addCriteria($criteria)
    ->setMaxResults(1)
;
$query = $qb->getQuery();

/** @var CarePost $post */
$post = $query->getOneOrNullResult();

// Get related posts (post with same parent)
$relatedPosts = [];
if ($post) {
    if ($action == 'download') {
        $attachment = $post->getAttachment();
        $attachmentUrlData = parse_url($attachment);
        if (!empty($attachment) && !empty($attachmentUrlData)) {
            $adapter = new FtpAdapter(
                '/',
                $attachmentUrlData['host'],
                [
                    'port' => 21,
                    'username' => isset($attachmentUrlData['user']) ? $attachmentUrlData['user'] : '',
                    'password' => isset($attachmentUrlData['pass']) ? $attachmentUrlData['pass'] : '',
                    'passive' => true,
                    'create' => false, // Whether to create the remote directory if it does not exist
                    'mode' => FTP_BINARY, // Or FTP_TEXT
                    'ssl' => false,
                ]
            );
            $filesystem = new Filesystem($adapter);
            if ($filesystem->has($attachmentUrlData['path'])) {
                $contentType = DocumentManager::file_get_mime_type($attachmentUrlData['path']);
                $response = new \Symfony\Component\HttpFoundation\Response();
                $response->headers->set('Cache-Control', 'private');
                $response->headers->set('Content-type', $contentType);
                $response->headers->set('Content-Disposition', 'attachment; filename="'.basename($attachmentUrlData['path']).'";');
                //$response->headers->set('Content-length', filesize($filename));
                // Send headers before outputting anything
                $response->sendHeaders();
                $response->setContent($filesystem->read($attachmentUrlData['path']));
                $response->send();
                exit;
            } else {
                api_not_allowed(true);
            }
        } else {
            api_not_allowed(true);
        }
    }

    $qb = $em->createQueryBuilder();
    $criteria = Criteria::create();

    if (!empty($post->getParent())) {
        $criteria->where(Criteria::expr()->in('parent', [$post->getParent()->getId(), $post->getId()]));
    } else {
        $criteria->where(Criteria::expr()->eq('parent', $post->getId()));
    }

    if ($showPrivate == false) {
        $criteria->andWhere(Criteria::expr()->eq('private', false));
    }

    $criteria->orWhere(Criteria::expr()->eq('id', $post->getId()));

    $qb
        ->select('p')
        ->distinct()
        ->from('ChamiloPluginBundle:StudentFollowUp\CarePost', 'p')
        ->addCriteria($criteria)
        ->orderBy('p.createdAt', 'desc')
    ;
    $query = $qb->getQuery();
    $relatedPosts = $query->getResult();
}

$tpl = new Template($plugin->get_lang('plugin_title'));
$tpl->assign('post', $post);
$tpl->assign('related_posts', $relatedPosts);
$url = api_get_path(WEB_PLUGIN_PATH).'/studentfollowup/post.php?student_id='.$studentId;
$tpl->assign('post_url', $url);
$tpl->assign(
    'back_link',
    Display::url(
        Display::return_icon('back.png'),
        api_get_path(WEB_PLUGIN_PATH).'studentfollowup/posts.php?student_id='.$studentId
    )
);
$tpl->assign('information_icon', Display::return_icon('info.png'));
$tpl->assign('student_info', api_get_user_info($studentId));
$tpl->assign('care_title', $plugin->get_lang('CareDetailView'));

$content = $tpl->fetch('/'.$plugin->get_name().'/view/post.html.twig');
// Assign into content
$tpl->assign('content', $content);
// Display
$tpl->display_one_col_template();
