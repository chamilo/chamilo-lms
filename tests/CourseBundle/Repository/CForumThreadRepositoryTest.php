<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Chamilo\CourseBundle\Repository\CForumThreadRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CForumThreadRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();
        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $forumRepo = self::getContainer()->get(CForumRepository::class);
        $threadRepo = self::getContainer()->get(CForumThreadRepository::class);

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

        /** @var CForum $forum */
        $forum = $forumRepo->find($forum->getIid());

        $this->assertSame('thread title', (string) $thread);
        $this->assertSame(1, $threadRepo->count([]));
        $this->assertSame(1, $forumRepo->count([]));
        $this->assertSame(1, $forum->getThreads()->count());

        $this->assertSame(0, $thread->getThreadViews());
        $threadRepo->increaseView($thread);

        $this->assertSame(1, $thread->getThreadViews());

        $forumRepo->delete($forum);

        $this->assertSame(0, $threadRepo->count([]));
        $this->assertSame(0, $forumRepo->count([]));
    }
}
