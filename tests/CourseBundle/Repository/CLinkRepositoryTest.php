<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CLinkRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CLinkRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $link = (new CLink())
            ->setUrl('https://chamilo.org')
            ->setTitle('link')
            ->setDescription('desc')
            ->setDisplayOrder(1)
            ->setTarget('_blank')
            ->setCategory(null)
            ->setParent($course)
            ->setCreator($teacher)
        ;

        $this->assertHasNoEntityViolations($link);
        $em->persist($link);
        $em->flush();

        $this->assertSame('link', (string) $link);
        $this->assertSame($link->getResourceIdentifier(), $link->getIid());
        $this->assertSame('https://chamilo.org', $link->getUrl());
        $this->assertSame('link', $link->getTitle());
        $this->assertSame('desc', $link->getDescription());
        $this->assertSame(1, $link->getDisplayOrder());
        $this->assertSame('_blank', $link->getTarget());

        $this->assertSame(1, $repo->count([]));
    }
}
