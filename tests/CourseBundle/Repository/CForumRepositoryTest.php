<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

class CForumRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $forumRepo = self::getContainer()->get(CForumRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $forum = (new CForum())
            ->setForumTitle('forum')
            ->setForumComment('comment')
            ->setForumThreads(0)
            ->setForumPosts(0)
            ->setAllowAnonymous(1)
            ->setAllowEdit(1)
            ->setApprovalDirectPost('1')
            ->setAllowAttachments(1)
            ->setAllowNewThreads(1)
            ->setDefaultView('default')
            ->setForumOfGroup('1')
            ->setForumGroupPublicPrivate('1')
            ->setLocked(1)
            ->setForumImage('')
            ->setStartTime(new DateTime())
            ->setEndTime(new DateTime())
            ->setModerated(true)
            ->setResourceName('forum')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $this->assertHasNoEntityViolations($forum);
        $forumRepo->create($forum);

        $this->assertSame('forum', (string) $forum);

        $this->assertSame($forum->getIid(), $forum->getResourceIdentifier());
        $this->assertSame(1, $forum->getAllowAnonymous());
        $this->assertSame(1, $forumRepo->count([]));

        $this->assertSame(0, $forum->getForumPosts());

        $courseRepo->delete($course);

        $this->assertSame(0, $forumRepo->count([]));
        $this->assertSame(0, $courseRepo->count([]));
    }

    public function testCreateWithAttachment(): void
    {
        $repo = self::getContainer()->get(CForumRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $forum = (new CForum())
            ->setForumTitle('forum')
            ->setParent($course)
            ->setCreator($teacher)
        ;

        $this->assertHasNoEntityViolations($forum);
        $repo->create($forum);
    }

    public function testCreateWithLp(): void
    {
        $repo = self::getContainer()->get(CForumRepository::class);
        $lpRepo = self::getContainer()->get(CLpRepository::class);

        $this->assertSame(0, $repo->count([]));
        $this->assertSame(0, $lpRepo->count([]));

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $lp = (new CLp())
            ->setName('lp')
            ->setParent($course)
            ->setCreator($teacher)
            ->setLpType(CLp::LP_TYPE)
        ;
        $lpRepo->createLp($lp);

        $forum = (new CForum())
            ->setForumTitle('forum')
            ->setParent($course)
            ->setCreator($teacher)
            ->setLp($lp)
        ;
        $repo->create($forum);

        $this->assertNotNull($forum->getLp());
        $this->assertSame(1, $repo->count([]));
        $this->assertSame(1, $lpRepo->count([]));

        $lpRepo->delete($lp);

        $this->assertSame(1, $repo->count([]));
        $this->assertSame(0, $lpRepo->count([]));
    }

    public function testDelete(): void
    {
        self::bootKernel();

        $repo = self::getContainer()->get(CForumRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $forum = (new CForum())
            ->setForumTitle('forum')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $repo->create($forum);

        $this->assertSame(1, $repo->count([]));

        $repo->delete($forum);

        $this->assertSame(0, $repo->count([]));
    }
}
