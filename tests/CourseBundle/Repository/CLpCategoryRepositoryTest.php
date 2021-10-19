<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Repository\CLpCategoryRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CLpCategoryRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CLpCategoryRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $item = (new CLpCategory())
            ->setName('cat')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame('cat', (string) $item);
        $this->assertSame(1, $repo->count([]));

        $link = $repo->getLink($item, $this->getContainer()->get('router'));
        $this->assertSame('/main/lp/lp_controller.php?id='.$item->getIid().'&action=view_category', $link);
    }
}
