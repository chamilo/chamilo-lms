<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationAssignment;
use Chamilo\CourseBundle\Repository\CStudentPublicationAssignmentRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

class CStudentPublicationAssignmentRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $publicationRepo = self::getContainer()->get(CStudentPublicationRepository::class);
        $assignmentRepo = self::getContainer()->get(CStudentPublicationAssignmentRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $publication = (new CStudentPublication())
            ->setTitle('publi')
            ->setDescription('desc')
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($teacher)
        ;
        $em->persist($publication);

        $assignment = (new CStudentPublicationAssignment())
            ->setAddToCalendar(0)
            ->setEnableQualification(true)
            ->setEndsOn(new DateTime())
            ->setExpiresOn(new DateTime())
            ->setPublication($publication)
        ;
        $em->persist($assignment);
        $em->flush();
        $em->clear();

        /** @var CStudentPublication $publication */
        $publication = $publicationRepo->find($publication->getIid());

        $this->assertNotNull($publication->getAssignment());
        $this->assertSame(1, $assignmentRepo->count([]));

        $em->remove($publication);
        $em->flush();

        $this->assertSame(0, $assignmentRepo->count([]));
        $this->assertSame(0, $publicationRepo->count([]));
    }
}
