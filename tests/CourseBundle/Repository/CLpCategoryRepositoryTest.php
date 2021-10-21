<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CLpCategoryUser;
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

        $category = (new CLpCategory())
            ->setName('cat')
            ->setPosition(1)
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $this->assertHasNoEntityViolations($category);
        $em->persist($category);
        $em->flush();

        $this->assertSame('cat', (string) $category);
        $this->assertSame(1, $repo->count([]));

        $link = $repo->getLink($category, $this->getContainer()->get('router'));
        $this->assertSame('/main/lp/lp_controller.php?id='.$category->getIid().'&action=view_category', $link);
    }

    public function testCreateWithUser(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CLpCategoryRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');
        $student = $this->createUser('student');
        $student2 = $this->createUser('student2');

        $category = (new CLpCategory())
            ->setName('cat')
            ->setPosition(1)
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $em->persist($category);
        $em->flush();

        $categoryRelUser = (new CLpCategoryUser())
            ->setCategory($category)
            ->setUser($teacher)
        ;
        $em->persist($categoryRelUser);
        $category->addUser($categoryRelUser);
        $em->flush();

        $this->assertSame(1, $repo->count([]));
        /** @var CLpCategory $category */
        $category = $repo->find($category->getIid());

        $this->assertTrue($category->hasResourceNode());
        $this->assertTrue($category->hasUserAdded($teacher));
        $this->assertFalse($category->hasUserAdded($student));
    }
}
