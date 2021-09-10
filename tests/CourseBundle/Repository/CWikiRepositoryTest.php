<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Repository\CThematicRepository;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CWikiRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CWikiRepository::class);

        $this->assertSame(0, $repo->count([]));
        /*
        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $item = (new CWiki())
            ->setTitle('wiki')
            ->setContent('wiki content')
            ->setReflink('wiki')
            ->setUserId($teacher->getId())
            ->setAddlock(0)
            ->setEditlock(0)
            ->setVisibility(0)

            ->setVisibilityDisc(0)
            ->setAddlockDisc(0)
            ->setRatinglockDisc(0)
            ->setParent($course)
            ->setCreator($teacher)
            ->setCId($course->getId())
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame('wiki', (string) $item);
        $this->assertSame(1, $repo->count([]));*/
    }
}
