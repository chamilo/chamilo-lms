<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Repository\CThematicRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CThematicRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $repo = self::getContainer()->get(CThematicRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $thematic = (new CThematic())
            ->setTitle('thematic')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $this->assertHasNoEntityViolations($thematic);
        $em->persist($thematic);
        $em->flush();

        $this->assertSame('thematic', (string) $thematic);
        $this->assertSame(1, $repo->count([]));

        $courseRepo->delete($course);
        $this->assertSame(0, $repo->count([]));
        $this->assertSame(0, $courseRepo->count([]));
    }
}
