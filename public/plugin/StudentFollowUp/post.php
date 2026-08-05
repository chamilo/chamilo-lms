<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\PluginBundle\StudentFollowUp\Entity\CarePost;
use Gaufrette\Adapter\Ftp as FtpAdapter;
use Gaufrette\Filesystem;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();

$plugin = StudentFollowUpPlugin::create();

$currentUserId = api_get_user_id();
$studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : api_get_user_id();
$postId = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';

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
$student = api_get_user_entity($studentId);
$currentUser = api_get_user_entity($currentUserId);

if (null === $student || null === $currentUser) {
    api_not_allowed(true);
}

$createMode = 'create' === $action;
$form = null;
$post = null;
$relatedPosts = [];

if ($createMode) {
    if (false === $canCreatePost) {
        api_not_allowed(true);
    }

    $form = new FormValidator(
        'student_follow_up_post',
        'post',
        api_get_path(WEB_PLUGIN_PATH).'StudentFollowUp/post.php?student_id='.$studentId.'&action=create'
    );

    $form->addText(
        'title',
        $plugin->get_lang('FollowUpNoteTitle'),
        true,
        [
            'maxlength' => 255,
        ]
    );
    $form->addRule('title', get_lang('Required field'), 'required');

    $form->addHtmlEditor(
        'content',
        $plugin->get_lang('FollowUpNoteContent'),
        true,
        false,
        [
            'ToolbarSet' => 'Minimal',
        ]
    );
    $form->addRule('content', get_lang('Required field'), 'required');

    $form->addText(
        'tags',
        [
            $plugin->get_lang('Tags'),
            $plugin->get_lang('TagsHelp'),
        ],
        false,
        [
            'placeholder' => $plugin->get_lang('TagsPlaceholder'),
        ]
    );

    $form->addElement('checkbox', 'private', null, $plugin->get_lang('PrivateNote'));

    $form->addHtml('<div class="mt-6 flex justify-end gap-2">');
    $form->addButtonSave($plugin->get_lang('SaveFollowUpNote'));
    $form->addHtml('</div>');

    if ($form->validate()) {
        $values = $form->exportValues();
        $title = isset($values['title']) ? trim(Security::remove_XSS((string) $values['title'])) : '';
        $content = isset($values['content']) ? trim(Security::remove_XSS((string) $values['content'])) : '';
        $tagsValue = isset($values['tags']) ? (string) $values['tags'] : '';
        $tags = [];

        if ('' !== trim($tagsValue)) {
            foreach (explode(',', $tagsValue) as $tag) {
                $tag = trim(Security::remove_XSS($tag));
                if ('' === $tag || in_array($tag, $tags, true)) {
                    continue;
                }

                $tags[] = $tag;
            }
        }

        if ('' === $title || '' === strip_tags($content)) {
            Display::addFlash(Display::return_message($plugin->get_lang('TitleAndContentRequired'), 'warning'));
        } else {
            $post = new CarePost();
            $post
                ->setTitle($title)
                ->setContent($content)
                ->setUser($student)
                ->setInsertUser($currentUser)
                ->setPrivate(!empty($values['private']))
                ->setExternalSource(false)
                ->setAttachment('')
                ->setTags($tags)
                ->setCreatedAt(new DateTime())
                ->setUpdatedAt(new DateTime())
            ;

            $em->persist($post);
            $em->flush();

            Display::addFlash(Display::return_message($plugin->get_lang('FollowUpNoteCreated'), 'confirmation'));

            header('Location: '.api_get_path(WEB_PLUGIN_PATH).'StudentFollowUp/posts.php?student_id='.$studentId);
            exit;
        }
    }
} else {
    if (empty($postId)) {
        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'StudentFollowUp/posts.php?student_id='.$studentId);
        exit;
    }

    $qb = $em->createQueryBuilder();

    $qb
        ->select('p')
        ->from(CarePost::class, 'p')
        ->andWhere('IDENTITY(p.user) = :studentId')
        ->andWhere('p.id = :postId')
        ->setParameter('studentId', $studentId)
        ->setParameter('postId', $postId)
        ->setMaxResults(1)
    ;

    if (false === $showPrivate) {
        $qb
            ->andWhere('p.private = :private')
            ->setParameter('private', false)
        ;
    }

    /** @var CarePost|null $post */
    $post = $qb->getQuery()->getOneOrNullResult();

    if ($post) {
        if ('download' === $action) {
            $attachment = $post->getAttachment();
            $attachmentUrlData = parse_url($attachment);
            if (!empty($attachment) && !empty($attachmentUrlData) && !empty($attachmentUrlData['host'])) {
                $adapter = new FtpAdapter(
                    '/',
                    $attachmentUrlData['host'],
                    [
                        'port' => 21,
                        'username' => $attachmentUrlData['user'] ?? '',
                        'password' => $attachmentUrlData['pass'] ?? '',
                        'passive' => true,
                        'create' => false,
                        'mode' => FTP_BINARY,
                        'ssl' => false,
                    ]
                );
                $filesystem = new Filesystem($adapter);
                $path = $attachmentUrlData['path'] ?? '';
                if (!empty($path) && $filesystem->has($path)) {
                    $contentType = DocumentManager::file_get_mime_type($path);
                    $response = new Symfony\Component\HttpFoundation\Response();
                    $response->headers->set('Cache-Control', 'private');
                    $response->headers->set('Content-type', $contentType);
                    $response->headers->set('Content-Disposition', 'attachment; filename="'.basename($path).'";');
                    $response->sendHeaders();
                    $response->setContent($filesystem->read($path));
                    $response->send();
                    exit;
                }

                api_not_allowed(true);
            }

            api_not_allowed(true);
        }

        $qb = $em->createQueryBuilder();
        $qb
            ->select('p')
            ->distinct()
            ->from(CarePost::class, 'p')
            ->orderBy('p.createdAt', 'desc')
        ;

        $parent = $post->getParent();
        if (null !== $parent) {
            $qb
                ->andWhere('IDENTITY(p.parent) IN (:parentIds) OR p.id = :currentPostId')
                ->setParameter('parentIds', [$parent->getId(), $post->getId()])
                ->setParameter('currentPostId', $post->getId())
            ;
        } else {
            $qb
                ->andWhere('IDENTITY(p.parent) = :postId OR p.id = :currentPostId')
                ->setParameter('postId', $post->getId())
                ->setParameter('currentPostId', $post->getId())
            ;
        }

        if (false === $showPrivate) {
            $qb
                ->andWhere('p.private = :private')
                ->setParameter('private', false)
            ;
        }

        $relatedPosts = $qb->getQuery()->getResult();
    }
}

$tpl = new Template($plugin->get_lang('plugin_title'));
$tpl->assign('create_mode', $createMode);
$tpl->assign('create_form', $form ? $form->returnForm() : '');
$tpl->assign('post', $post);
$tpl->assign('related_posts', $relatedPosts);
$url = api_get_path(WEB_PLUGIN_PATH).'StudentFollowUp/post.php?student_id='.$studentId;
$tpl->assign('post_url', $url);
$tpl->assign(
    'back_link',
    Display::url(
        '<span class="mdi mdi-arrow-left" aria-hidden="true"></span> '.($createMode ? $plugin->get_lang('BackToTimeline') : get_lang('Back')),
        api_get_path(WEB_PLUGIN_PATH).'StudentFollowUp/posts.php?student_id='.$studentId,
        ['class' => 'btn btn--plain inline-flex items-center gap-2']
    )
);
$tpl->assign('information_icon', Display::getMdiIcon(ActionIcon::INFORMATION, 'ch-tool-icon', null, ICON_SIZE_SMALL));
$tpl->assign('student_info', api_get_user_info($studentId));
$tpl->assign('care_title', $plugin->get_lang('CareDetailView'));

$content = $tpl->fetch('/'.$plugin->get_name().'/view/post.html.twig');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
