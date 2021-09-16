<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CForumRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $repo = self::getContainer()->get(CForumRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $item = (new CForum())
            ->setForumTitle('forum')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $this->assertHasNoEntityViolations($item);
        $repo->create($item);

        $this->assertSame('forum', (string) $item);
        $this->assertSame(1, $repo->count([]));
    }

    public function testCreateWithLp(): void
    {
        self::bootKernel();

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
