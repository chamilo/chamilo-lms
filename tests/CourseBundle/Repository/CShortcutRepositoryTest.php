<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CShortcut;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CShortcutRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $shortcutRepo = self::getContainer()->get(CShortcutRepository::class);
        $forumRepo = self::getContainer()->get(CForumRepository::class);
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $resource = (new CForum())
            ->setForumTitle('forum')
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($resource);
        $em->flush();

        $shortcut = (new CShortcut())
            ->setName($resource->getResourceName())
            ->setShortCutNode($resource->getResourceNode())
            ->setCreator($teacher)
            ->setParent($resource)
            ->addCourseLink($course)
        ;
        $this->assertHasNoEntityViolations($shortcut);
        $em->persist($shortcut);
        $em->flush();

        $this->assertSame(1, $shortcutRepo->count([]));
        $this->assertSame($resource->getResourceName(), (string) $shortcut);
        $this->assertNotNull($shortcut->getUrl());
        $this->assertNotNull($shortcut->getTool());

        $shortcut = $shortcutRepo->getShortcutFromResource($resource);
        $this->assertInstanceOf(CShortcut::class, $shortcut);

        $shortcutRepo->removeShortCut($resource);

        $this->assertSame(0, $shortcutRepo->count([]));
        $this->assertSame(1, $forumRepo->count([]));

        $shortcutRepo->addShortCut($resource, $teacher, $course);

        $this->assertSame(1, $shortcutRepo->count([]));

        $course = $this->getCourse($course->getId());
        $courseRepo->delete($course);

        $this->assertSame(0, $shortcutRepo->count([]));
        $this->assertSame(0, $courseRepo->count([]));
    }
}
