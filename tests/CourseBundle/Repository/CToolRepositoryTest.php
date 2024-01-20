<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\ToolRepository;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Repository\CToolRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CToolRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $repo = self::getContainer()->get(CToolRepository::class);
        $toolRepo = self::getContainer()->get(ToolRepository::class);
        $this->assertSame(0, $repo->count([]));

        $em = $this->getEntityManager();

        $course = $this->createCourse('new');
        $defaultCount = $repo->count([]);
        $admin = $this->getUser('admin');

        $tool = $toolRepo->findOneBy(['name' => 'course_homepage']);
        $this->assertNotNull($tool);

        $cTool = (new CTool())
            ->setTitle('test')
            ->setCourse($course)
            ->setTool($tool)
            ->setParent($course)
            ->setPosition(1)
            ->setCreator($admin)
            ->addCourseLink($course)
        ;
        $this->assertHasNoEntityViolations($cTool);
        $em->persist($cTool);
        $em->flush();

        $this->assertSame('test', (string) $cTool);
        $this->assertSame(1, $cTool->getPosition());
        $this->assertSame($defaultCount + 1, $repo->count([]));
    }

    public function testDelete(): void
    {
        $repo = self::getContainer()->get(CToolRepository::class);
        $course_repo = self::getContainer()->get(CourseRepository::class);
        $this->assertSame(0, $repo->count([]));

        $course = $this->createCourse('new');
        $this->assertSame(1, $course_repo->count([]));
        $defaultCount = $repo->count([]);

        /** @var CTool $courseTool */
        $courseTool = $course->getTools()->first();
        $repo->delete($courseTool);
        $this->assertSame(1, $course_repo->count([]));

        $this->assertSame($defaultCount - 1, $repo->count([]));
    }

}
