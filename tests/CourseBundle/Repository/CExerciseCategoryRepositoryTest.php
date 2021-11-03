<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CExerciseCategory;
use Chamilo\CourseBundle\Repository\CExerciseCategoryRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CExerciseCategoryRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CExerciseCategoryRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $item = (new CExerciseCategory())
            ->setName('cat')
            ->setDescription('desc')
            ->setCourse($course)
            ->setParent($course)
            ->setCreator($teacher)
            ->setPosition(1)
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame('cat', (string) $item);
        $this->assertSame($item->getId(), $item->getResourceIdentifier());
        $this->assertSame('desc', $item->getDescription());
        $this->assertSame('cat', $item->getName());

        $this->assertSame(1, $repo->count([]));
        $this->assertCount(1, $repo->getCategories($course->getId()));
    }
}
