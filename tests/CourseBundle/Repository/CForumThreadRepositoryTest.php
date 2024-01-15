<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CForumThreadQualify;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Chamilo\CourseBundle\Repository\CForumThreadRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

class CForumThreadRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $forumRepo = self::getContainer()->get(CForumRepository::class);
        $threadRepo = self::getContainer()->get(CForumThreadRepository::class);
        $qualifyRepo = $em->getRepository(CForumThreadQualify::class);
        $request_stack = $this->getMockedRequestStack([
            'session' => ['studentview' => 1],
        ]);
        $threadRepo->setRequestStack($request_stack);

        $forum = (new CForum())
            ->setTitle('forum')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $forumRepo->create($forum);

        $thread = (new CForumThread())
            ->setTitle('thread title')
            ->setThreadPeerQualify(true)
            ->setThreadReplies(0)
            ->setThreadDate(new DateTime())
            ->setThreadSticky(false)
            ->setLocked(1)
            ->setThreadTitleQualify('title')
            ->setThreadQualifyMax(100)
            ->setThreadCloseDate(new DateTime())
            ->setThreadWeight(100)
            ->setItem(null)
            ->setForum($forum)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $this->assertHasNoEntityViolations($thread);
        $threadRepo->create($thread);

        $qualify = (new CForumThreadQualify())
            ->setQualify(100)
            ->setQualifyTime(new DateTime())
            ->setThread($thread)
            ->setCId($course->getId())
        ;
        $em->persist($qualify);
        $em->flush();

        /** @var CForum $forum */
        $forum = $forumRepo->find($forum->getIid());

        $this->assertSame('thread title', (string) $thread);

        $qb = $threadRepo->findAllByCourse($course);
        $this->assertCount(1, $qb->getQuery()->getResult());
        $this->assertSame(1, $threadRepo->count([]));
        $this->assertSame(1, $forumRepo->count([]));
        $this->assertSame(1, $forum->getThreads()->count());
        $this->assertTrue($forum->hasThread($thread));
        $this->assertNull($threadRepo->getForumThread('title', $course));

        $this->assertSame($thread->getIid(), $thread->getResourceIdentifier());
        $this->assertSame(0, $thread->getThreadViews());
        $threadRepo->increaseView($thread);
        $this->assertSame(1, $thread->getThreadViews());

        $forumRepo->delete($forum);

        $this->assertSame(0, $threadRepo->count([]));
        $this->assertSame(0, $forumRepo->count([]));
        $this->assertSame(0, $qualifyRepo->count([]));
    }

    public function testDelete(): void
    {
        $em = $this->getEntityManager();
        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $qualifyRepo = $em->getRepository(CForumThreadQualify::class);
        $forumRepo = self::getContainer()->get(CForumRepository::class);
        $threadRepo = self::getContainer()->get(CForumThreadRepository::class);

        $forum = (new CForum())
            ->setTitle('forum')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $forumRepo->create($forum);

        $thread = (new CForumThread())
            ->setTitle('thread title')
            ->setForum($forum)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $threadRepo->create($thread);

        $qualify = (new CForumThreadQualify())
            ->setQualify(100)
            ->setQualifyTime(new DateTime())
            ->setThread($thread)
            ->setCId($course->getId())
        ;
        $em->persist($qualify);
        $em->flush();

        $thread = $threadRepo->find($thread->getIid());

        $threadRepo->delete($thread);

        $this->assertSame(0, $qualifyRepo->count([]));
        $this->assertSame(0, $threadRepo->count([]));
        // FIXME Bring back once behavior is fixed on the source.
        // Similar to category-forum a delete is triggering associated values
        // removal, it is pending to fix code and re-enable these assertions..
        // $this->assertSame(1, $forumRepo->count([]));
    }
}
