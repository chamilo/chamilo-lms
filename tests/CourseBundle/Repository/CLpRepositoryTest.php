<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Chamilo\CourseBundle\Repository\CLpCategoryRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CLpRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreateLp(): void
    {
        $repo = self::getContainer()->get(CLpRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $lp = (new CLp())
            ->setName('lp')
            ->setDescription('desc')
            ->setTheme('chamilo')
            ->setAccumulateScormTime(100)
            ->setAccumulateWorkTime(100)
            ->setAuthor('author')
            ->setContentMaker('maker')
            ->setContentLocal('local')
            ->setForceCommit(false)
            ->setUseMaxScore(100)
            ->setSubscribeUsers(1)
            ->setJsLib('lib')
            ->setHideTocFrame(true)
            ->setRef('ref')
            ->setPath('path')
            ->setAutolaunch(0)
            ->setCategory(null)
            ->setParent($course)
            ->setCreator($teacher)
            ->setLpType(CLp::LP_TYPE)
        ;
        $this->assertHasNoEntityViolations($lp);
        $repo->createLp($lp);

        $this->assertSame('lp', $lp->getName());
        $this->assertSame('desc', $lp->getDescription());
        $this->assertSame('chamilo', $lp->getTheme());
        $this->assertSame('author', $lp->getAuthor());
        $this->assertSame('local', $lp->getContentLocal());
        $this->assertSame('maker', $lp->getContentMaker());

        $this->assertNotNull($lp->getResourceNode());
        $this->assertSame(1, $lp->getItems()->count());
        $this->assertFalse($lp->hasCategory());
        $this->assertSame('lp', (string) $lp);
        $this->assertSame(1, $repo->count([]));

        $link = $repo->getLink($lp, $this->getContainer()->get('router'));
        $this->assertSame('/main/lp/lp_controller.php?lp_id='.$lp->getIid().'&action=view', $link);
    }

    public function testCreateWithCategory(): void
    {
        $lpRepo = self::getContainer()->get(CLpRepository::class);
        $categoryRepo = self::getContainer()->get(CLpCategoryRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $category = (new CLpCategory())
            ->setName('cat')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $categoryRepo->create($category);

        $lp = (new CLp())
            ->setName('lp')
            ->setTheme('chamilo')
            ->setAuthor('author')
            ->setParent($course)
            ->setCreator($teacher)
            ->setLpType(CLp::LP_TYPE)
            ->setCategory($category)
        ;
        $this->assertHasNoEntityViolations($lp);
        $lpRepo->createLp($lp);

        $this->assertSame(1, $lpRepo->count([]));
        $this->assertSame(1, $categoryRepo->count([]));
        $this->assertInstanceOf(CLpCategory::class, $lp->getCategory());

        $lpRepo->delete($lp);

        $this->assertSame(0, $lpRepo->count([]));
        $this->assertSame(1, $categoryRepo->count([]));
    }

    public function testCreateWithForum(): void
    {
        $lpRepo = self::getContainer()->get(CLpRepository::class);
        $forumRepo = self::getContainer()->get(CForumRepository::class);

        $course = $this->createCourse('new');
        $course2 = $this->createCourse('new2');
        $teacher = $this->createUser('teacher');

        $forum = (new CForum())
            ->setForumTitle('forum')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $forumRepo->create($forum);

        $forum2 = (new CForum())
            ->setForumTitle('forum2')
            ->setParent($course2)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $forumRepo->create($forum2);

        $lp = (new CLp())
            ->setName('lp')
            ->setParent($course)
            ->setCreator($teacher)
            ->setLpType(CLp::LP_TYPE)
            ->addCourseLink($course)
        ;
        $lp->getForums()->add($forum);
        $lp->getForums()->add($forum2);

        $lpRepo->createLp($lp);

        $this->assertSame(2, $lp->getForums()->count());

        $forum = $lpRepo->findForumByCourse($lp, $course);
        $this->assertNotNull($forum);
    }

    public function testFindAllByCourse(): void
    {
        $repo = self::getContainer()->get(CLpRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $lp = (new CLp())
            ->setName('lp')
            ->setParent($course)
            ->setCreator($teacher)
            ->setLpType(CLp::LP_TYPE)
            ->addCourseLink($course)
        ;
        $repo->createLp($lp);

        $qb = $repo->findAllByCourse($course);
        $this->assertCount(1, $qb->getQuery()->getResult());
    }
}
