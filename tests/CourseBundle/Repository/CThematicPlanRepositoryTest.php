<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicPlan;
use Chamilo\CourseBundle\Repository\CThematicPlanRepository;
use Chamilo\CourseBundle\Repository\CThematicRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CThematicPlanRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $thematicRepo = self::getContainer()->get(CThematicRepository::class);
        $planRepo = self::getContainer()->get(CThematicPlanRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $thematic = (new CThematic())
            ->setTitle('thematic')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($thematic);

        $plan = (new CThematicPlan())
            ->setTitle('title')
            ->setDescription('desc')
            ->setThematic($thematic)
            ->setDescriptionType(1)
        ;
        $em->persist($plan);
        $em->flush();
        $em->clear();

        /** @var CThematic $thematic */
        $thematic = $thematicRepo->find($thematic->getIid());

        $this->assertSame(1, $thematic->getPlans()->count());
        $this->assertSame(1, $thematicRepo->count([]));
        $this->assertSame(1, $planRepo->count([]));
        $this->assertSame(1, $courseRepo->count([]));

        $plan = $planRepo->find($plan->getIid());
        $em->remove($plan);
        $em->flush();

        $this->assertSame(1, $thematicRepo->count([]));
        $this->assertSame(1, $courseRepo->count([]));
        $this->assertSame(0, $planRepo->count([]));
    }

    public function testCreateDeleteCourse(): void
    {
        $em = $this->getEntityManager();
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $thematicRepo = self::getContainer()->get(CThematicRepository::class);
        $planRepo = self::getContainer()->get(CThematicPlanRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $thematic = (new CThematic())
            ->setTitle('thematic')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($thematic);

        $plan = (new CThematicPlan())
            ->setTitle('title')
            ->setDescription('desc')
            ->setThematic($thematic)
            ->setDescriptionType(1)
        ;
        $em->persist($plan);
        $em->flush();
        $em->clear();

        /** @var CThematic $thematic */
        $thematic = $thematicRepo->find($thematic->getIid());

        $this->assertSame(1, $thematic->getPlans()->count());
        $this->assertSame(1, $thematicRepo->count([]));
        $this->assertSame(1, $planRepo->count([]));
        $this->assertSame(1, $courseRepo->count([]));

        $course = $this->getCourse($course->getId());
        $courseRepo->delete($course);

        $this->assertSame(0, $thematicRepo->count([]));
        $this->assertSame(0, $planRepo->count([]));
        $this->assertSame(0, $courseRepo->count([]));
    }
}
