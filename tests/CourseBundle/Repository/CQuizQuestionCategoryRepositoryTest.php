<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CQuizQuestionCategoryRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $categoryRepo = self::getContainer()->get(CQuizQuestionCategoryRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $category = (new CQuizQuestionCategory())
            ->setTitle('category')
            ->setDescription('desc')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $this->assertHasNoEntityViolations($category);
        $categoryRepo->create($category);

        $category = $categoryRepo->find($category->getIid());

        $this->assertSame(0, $category->getQuestions()->count());
        $this->assertSame(1, $categoryRepo->count([]));

        $categoryRepo->delete($category);

        $this->assertSame(0, $categoryRepo->count([]));
    }
}
