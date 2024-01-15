<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CLpItemRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $lpRepo = self::getContainer()->get(CLpRepository::class);
        $lpItemRepo = self::getContainer()->get(CLpItemRepository::class);
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');
        $student = $this->createUser('student');

        $lp = (new CLp())
            ->setTitle('lp')
            ->setParent($course)
            ->setCreator($teacher)
            ->setLpType(CLp::LP_TYPE)
        ;
        $lpRepo->createLp($lp);

        $rootItem = $lpItemRepo->getRootItem($lp->getIid());
        $this->assertNotNull($rootItem);

        $this->assertSame('root', $rootItem->getPath());

        $lpItem = (new CLpItem())
            ->setDescription('lp')
            ->setTitle('lp item')
            ->setRef('ref')
            ->setMinScore(100)
            ->setMaxScore(100)
            ->setMasteryScore(100)
            ->setPreviousItemId(0)
            ->setNextItemId(0)
            ->setDisplayOrder(1)
            ->setPrerequisite('')
            ->setParameters('')
            ->setLaunchData('')
            ->setMaxTimeAllowed('100')
            ->setTerms('')
            ->setSearchDid(0)
            ->setAudio('')
            ->setPrerequisiteMinScore(100)
            ->setPrerequisiteMaxScore(100)
            ->setParent(null)
            ->setLvl(0)
            ->setLp($lp)
            ->setItemType('document')
        ;
        $this->assertHasNoEntityViolations($lpItem);
        $lpItemRepo->create($lpItem);

        $this->assertSame('lp item', $lpItem->getTitle());
        $this->assertSame('ref', $lpItem->getRef());
        $this->assertSame(100.0, $lpItem->getMinScore());
        $this->assertSame(100.0, $lpItem->getMaxScore());
        $this->assertSame(1, $lpItem->getDisplayOrder());
        $this->assertSame(100.0, $lpItem->getPrerequisiteMaxScore());
        $this->assertSame(100.0, $lpItem->getPrerequisiteMinScore());
        $this->assertSame('', $lpItem->getLaunchData());
        $this->assertSame('', $lpItem->getPrerequisite());
        $this->assertSame('', $lpItem->getParameters());
        $this->assertSame('', $lpItem->getAudio());
        $this->assertSame(0, $lpItem->getSearchDid());

        $this->assertSame(0, $lpItem->getLvl());

        $em = $this->getEntityManager();
        $view = (new CLpView())
            ->setUser($student)
            ->setViewCount(1)
            ->setLastItem(0)
            ->setLp($lp)
            ->setProgress(0)
            ->setCourse($course)
            ->setSession(null)
        ;
        $em->persist($view);

        $itemView = (new CLpItemView())
            ->setStatus('ok')
            ->setCoreExit('exit')
            ->setItem($lpItem)
            ->setLessonLocation('')
            ->setMaxScore('100')
            ->setScore(100)
            ->setStartTime(time())
            ->setSuspendData('')
            ->setTotalTime(100)
            ->setView($view)
            ->setViewCount(1)
        ;

        $em->persist($itemView);
        $em->flush();

        $this->assertSame(1, $lp->getItems()->count());
        $this->assertSame('lp', (string) $lp);
        $this->assertNotEmpty((string) $lpItem);
        $this->assertSame(1, $lpRepo->count([]));
        $this->assertSame(2, $lpItemRepo->count([]));

        $lpRepo->delete($lp);

        $this->assertSame(1, $courseRepo->count([]));
        $this->assertSame(0, $lpRepo->count([]));
        $this->assertSame(0, $lpItemRepo->count([]));
    }
}
