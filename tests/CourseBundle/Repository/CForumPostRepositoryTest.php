<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Repository\CForumPostRepository;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Chamilo\CourseBundle\Repository\CForumThreadRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CForumPostRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();
        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $forumRepo = self::getContainer()->get(CForumRepository::class);
        $threadRepo = self::getContainer()->get(CForumThreadRepository::class);
        $postRepo = self::getContainer()->get(CForumPostRepository::class);

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
            ->setParent($course)
            ->setCreator($teacher)
            ->setThread($thread)
            ->setForum($forum)
            ->setUser($teacher)
        ;
        $postRepo->create($post);
        $this->assertSame('post', (string) $post);
        $this->assertSame(1, $postRepo->count([]));
        $this->assertSame(1, $threadRepo->count([]));
        $this->assertSame(1, $forumRepo->count([]));

        /** @var CForumThread $thread */
        $thread = $threadRepo->find($thread->getIid());
        /** @var CForum $forum */
        $forum = $forumRepo->find($forum->getIid());

        $this->assertSame(1, $thread->getPosts()->count());
        $this->assertSame(1, $forum->getThreads()->count());
        $this->assertSame(1, $forum->getPosts()->count());

        $forumRepo->delete($forum);

        $this->assertSame(0, $postRepo->count([]));
        $this->assertSame(0, $threadRepo->count([]));
        $this->assertSame(0, $forumRepo->count([]));
    }
}
