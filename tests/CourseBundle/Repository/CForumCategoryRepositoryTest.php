<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Repository\CForumCategoryRepository;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CForumCategoryRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $categoryRepo = self::getContainer()->get(CForumCategoryRepository::class);
        $forumRepo = self::getContainer()->get(CForumRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $category = (new CForumCategory())
            ->setCatTitle('cat 1')
            ->setCatComment('comment')
            ->setCatOrder(1)
            ->setLocked(1)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $this->assertHasNoEntityViolations($category);
        $categoryRepo->create($category);

        $this->assertSame($category->getIid(), $category->getResourceIdentifier());
        $this->assertSame(1, $category->getLocked());
        $this->assertSame(1, $category->getCatOrder());
        $this->assertSame('comment', $category->getCatComment());

        $this->assertSame('cat 1', (string) $category);

        $forum = (new CForum())
            ->setForumTitle('forum')
            ->setParent($course)
            ->setCreator($teacher)
            ->setForumCategory($category)
            ->addCourseLink($course)
        ;
        $forumRepo->create($forum);

        /** @var CForumCategory $category */
        $category = $categoryRepo->find($category->getIid());
        $this->assertSame(1, $category->getForums()->count());
        $this->assertSame(1, $categoryRepo->count([]));
        $this->assertSame(1, $forumRepo->count([]));

        $this->assertNotNull($categoryRepo->getForumCategoryByTitle('cat 1', $course));

        $categoryRepo->delete($category);

        $this->assertSame(0, $categoryRepo->count([]));
        $this->assertSame(1, $forumRepo->count([]));
    }
}
