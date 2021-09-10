<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CLpItemRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $lpRepo = self::getContainer()->get(CLpRepository::class);
        $lpItemRepo = self::getContainer()->get(CLpItemRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $lp = (new CLp())
            ->setName('lp')
            ->setParent($course)
            ->setCreator($teacher)
            ->setLpType(CLp::LP_TYPE)
        ;
        $lpRepo->createLp($lp);

        $rootItem = $lpItemRepo->getRootItem($lp->getIid());
        $this->assertNotNull($rootItem);

        $this->assertSame('root', $rootItem->getPath());

        $lpItem = (new CLpItem())
            ->setDescription('lp')
            ->setTitle('lp item')
            ->setLp($lp)
            ->setItemType('document')
        ;
        $this->assertHasNoEntityViolations($lpItem);
        $lpItemRepo->create($lpItem);

        $this->assertSame(1, $lp->getItems()->count());
        $this->assertSame('lp', (string) $lp);
        $this->assertSame(1, $lpRepo->count([]));
        $this->assertSame(2, $lpItemRepo->count([]));
    }
}
