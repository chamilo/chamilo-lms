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
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CLinkCategoryRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $category = (new CLinkCategory())
            ->setCategoryTitle('cat')
            ->setDescription('desc')
            ->setDisplayOrder(1)
            ->setParent($course)
            ->setCreator($teacher)
        ;

        $this->assertHasNoEntityViolations($category);
        $em->persist($category);
        $em->flush();

        $this->assertSame($category->getResourceIdentifier(), $category->getIid());
        $this->assertSame('cat', (string) $category);
        $this->assertSame('desc', $category->getDescription());
        $this->assertSame('cat', $category->getCategoryTitle());
        $this->assertSame(1, $category->getDisplayOrder());

        $this->assertSame(1, $repo->count([]));
    }
}
