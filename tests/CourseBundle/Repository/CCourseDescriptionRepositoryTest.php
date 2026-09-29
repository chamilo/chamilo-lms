<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CCourseDescriptionRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testGetDescriptions(): void
    {
        $repo = self::getContainer()->get(CCourseDescriptionRepository::class);
        $request_stack = $this->getMockedRequestStack([
            'session' => ['studentview' => 1],
        ]);
        $repo->setRequestStack($request_stack);
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
