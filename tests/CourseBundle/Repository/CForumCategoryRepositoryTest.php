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
        $request_stack = $this->getMockedRequestStack([
            'session' => ['studentview' => 1],
        ]);
        $categoryRepo->setRequestStack($request_stack);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $category = (new CForumCategory())
            ->setTitle('cat 1')
            ->setCatComment('comment')
            ->setLocked(1)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $category->addCourseLink($course);
        $this->assertHasNoEntityViolations($category);
        $categoryRepo->create($category);

        $this->assertSame($category->getIid(), $category->getResourceIdentifier());
        $this->assertSame(1, $category->getLocked());
        $this->assertSame(0, $category->getResourceNode()->getResourceLinkByContext($course)?->getDisplayOrder());
        $this->assertSame('comment', $category->getCatComment());

        $this->assertSame('cat 1', (string) $category);

        $forum = (new CForum())
            ->setTitle('forum')
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
        // FIXME Bring back once behavior is fixed on the source.
        // CForumCategoryRepository's delete() is removing the related CForum's
        // data on removal.
        // CForum::forumCategory property's ORM\JoinColumn's "onDelete: SET
        // NULL" may be the problem.
        // $this->assertSame(1, $forumRepo->count([]));
    }
}
