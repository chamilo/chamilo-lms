<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CQuizCategory;
use Chamilo\CourseBundle\Repository\CQuizCategoryRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CQuizCategoryRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CQuizCategoryRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $item = (new CQuizCategory())
            ->setTitle('cat')
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
        $this->assertSame('cat', $item->getTitle());

        $this->assertSame(1, $repo->count([]));
        $this->assertCount(1, $repo->getCategories($course->getId()));
    }
}
