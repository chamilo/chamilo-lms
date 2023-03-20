<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CNotebook;
use Chamilo\CourseBundle\Repository\CNotebookRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CNotebookRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $notebookRepo = self::getContainer()->get(CNotebookRepository::class);
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $notebook = (new CNotebook())
            ->setTitle('item')
            ->setDescription('desc')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $this->assertHasNoEntityViolations($notebook);
        $em->persist($notebook);
        $em->flush();

        $this->assertSame('item', (string) $notebook);
        $this->assertSame($notebook->getResourceIdentifier(), $notebook->getIid());

        $this->assertSame(1, $notebookRepo->count([]));

        $courseRepo->delete($course);

        $this->assertSame(0, $courseRepo->count([]));
        $this->assertSame(0, $notebookRepo->count([]));
    }
}
