<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\Routing\RouterInterface;

class CGlossaryRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $glossaryRepo = self::getContainer()->get(CGlossaryRepository::class);
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $glossary = (new CGlossary())
            ->setName('glossary')
            ->setDescription('desc')
            ->setDisplayOrder(1)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $this->assertHasNoEntityViolations($glossary);
        $em->persist($glossary);
        $em->flush();

        $this->assertSame('glossary', (string) $glossary);
        $this->assertSame('desc', $glossary->getDescription());
        $this->assertSame(1, $glossary->getDisplayOrder());
        $this->assertSame($glossary->getResourceIdentifier(), $glossary->getIid());

        $router = $this->getContainer()->get(RouterInterface::class);

        $link = $glossaryRepo->getLink($glossary, $router);

        $this->assertSame($link, '/main/glossary/index.php?glossary_id='.$glossary->getIid());
        $this->assertSame(1, $glossaryRepo->count([]));

        $courseRepo->delete($course);

        $this->assertSame(0, $courseRepo->count([]));
        $this->assertSame(0, $glossaryRepo->count([]));
    }
}
