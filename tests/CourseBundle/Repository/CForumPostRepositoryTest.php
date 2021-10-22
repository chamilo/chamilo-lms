<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumAttachment;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Repository\CForumAttachmentRepository;
use Chamilo\CourseBundle\Repository\CForumPostRepository;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Chamilo\CourseBundle\Repository\CForumThreadRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

class CForumPostRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $forumRepo = self::getContainer()->get(CForumRepository::class);
        $threadRepo = self::getContainer()->get(CForumThreadRepository::class);
        $postRepo = self::getContainer()->get(CForumPostRepository::class);
        $attachmentRepo = self::getContainer()->get(CForumAttachmentRepository::class);

        $forum = (new CForum())
            ->setForumTitle('forum')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $forumRepo->create($forum);

        $thread = (new CForumThread())
            ->setThreadTitle('thread title')
            ->setForum($forum)
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $threadRepo->create($thread);

        $post = (new CForumPost())
            ->setPostTitle('post')
            ->setPostText('text')
            ->setPostDate(new DateTime())
            ->setPostNotification(true)
            ->setVisible(true)
            ->setStatus(1)
            ->setPostParent(null)
            ->setParent($course)
            ->setCreator($teacher)
            ->setThread($thread)
            ->setForum($forum)
            ->setUser($teacher)
            ->addCourseLink($course)
        ;
        $postRepo->create($post);

        $file = $this->getUploadedFile();

        $attachment = (new CForumAttachment())
            ->setComment('comment')
            ->setCId($course->getId())
            ->setFilename($file->getFilename())
            ->setPath('')
            ->setPost($post)
            ->setSize($file->getSize())
            ->setParent($post)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;

        $attachmentRepo->create($attachment);
        $attachmentRepo->addFile($attachment, $file);
        $attachmentRepo->update($attachment);

        $this->assertNotNull($attachment->getResourceNode());
        $this->assertNotNull($attachment->getResourceNode()->getResourceFile());

        $this->getEntityManager()->clear();

        $post = $postRepo->find($post->getIid());

        $this->assertSame('post', (string) $post);
        $this->assertSame(1, $post->getAttachments()->count());
        $this->assertSame(1, $postRepo->count([]));
        $this->assertSame(1, $threadRepo->count([]));
        $this->assertSame(1, $forumRepo->count([]));
        $this->assertSame(1, $attachmentRepo->count([]));

        /** @var CForumThread $thread */
        $thread = $threadRepo->find($thread->getIid());
        /** @var CForum $forum */
        $forum = $forumRepo->find($forum->getIid());

        $this->assertSame(1, $thread->getPosts()->count());
        $this->assertSame(1, $forum->getThreads()->count());
        $this->assertSame(1, $forum->getPosts()->count());

        $count = $postRepo->countCourseForumPosts($course);
        $this->assertSame(1, $count);

        $count = $postRepo->countUserForumPosts($teacher, $course);
        $this->assertSame(1, $count);

        $postRepo->delete($post);

        $this->assertSame(0, $postRepo->count([]));
        $this->assertSame(0, $attachmentRepo->count([]));
        $this->assertSame(1, $threadRepo->count([]));
        $this->assertSame(1, $forumRepo->count([]));

        $this->getEntityManager()->clear();

        /** @var CForum $forum */
        $forum = $forumRepo->find($forum->getIid());
        $forumRepo->delete($forum);

        $this->assertSame(0, $threadRepo->count([]));
        $this->assertSame(0, $forumRepo->count([]));
    }
}
