<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Repository\CLinkCategoryRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CLinkCategoryRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = $this->getManager();
        $repo = self::getContainer()->get(CLinkCategoryRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $item = (new CLinkCategory())
            ->setCategoryTitle('cat')
            ->setParent($course)
            ->setCreator($teacher)
        ;

        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame('cat', (string) $item);
        $this->assertSame(1, $repo->count([]));
    }
}
