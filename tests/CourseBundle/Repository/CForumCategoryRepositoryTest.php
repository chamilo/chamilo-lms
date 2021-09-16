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
            ->setCatTitle('cat')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $this->assertHasNoEntityViolations($category);
        $categoryRepo->create($category);

        $this->assertSame('cat', (string) $category);

        $forum = (new CForum())
            ->setForumTitle('forum')
            ->setParent($course)
            ->setCreator($teacher)
            ->setForumCategory($category)
        ;
        $forumRepo->create($forum);

        /** @var CForumCategory $category */
        $category = $categoryRepo->find($category->getIid());
        $this->assertSame(1, $category->getForums()->count());
        $this->assertSame(1, $categoryRepo->count([]));
        $this->assertSame(1, $forumRepo->count([]));

        $categoryRepo->delete($category);

        $this->assertSame(0, $categoryRepo->count([]));
        $this->assertSame(1, $forumRepo->count([]));
    }
}
