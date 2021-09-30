<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use function count;

class CCourseDescriptionRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CCourseDescriptionRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $item = (new CCourseDescription())
            ->setTitle('title')
            ->setContent('content')
            ->setDescriptionType(1)
            ->setProgress(100)
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame('title', (string) $item);
        $this->assertSame(1, $repo->count([]));
    }

    public function testGetDescriptions(): void
    {
        self::bootKernel();

        $repo = self::getContainer()->get(CCourseDescriptionRepository::class);
        $em = $this->getEntityManager();

        $course = $this->createCourse('Test');
        $session = $this->createSession('Test Session');
        $admin = $this->getUser('admin');

        $item = (new CCourseDescription())
            ->setTitle('title')
            ->setContent('content')
            ->setDescriptionType(CCourseDescription::TYPE_DESCRIPTION)
            ->setProgress(100)
            ->setCreator($admin)
            ->setParent($course)
            ->addCourseLink($course)
        ;
        $this->assertHasNoEntityViolations($item);

        $em->persist($item);
        $em->flush();

        $descriptionsInCourse = $repo->findByTypeInCourse(CCourseDescription::TYPE_DESCRIPTION, $course);
        $this->assertCount(1, $descriptionsInCourse);

        $descriptionsInSession = $repo->findByTypeInCourse(CCourseDescription::TYPE_DESCRIPTION, $course, $session);
        $this->assertCount(1, $descriptionsInSession);
    }
}
