<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Chamilo\CourseBundle\Repository\CStudentPublicationCommentRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CStudentPublicationCommentRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $publicationRepo = self::getContainer()->get(CStudentPublicationRepository::class);
        $commentRepo = self::getContainer()->get(CStudentPublicationCommentRepository::class);

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

        $comment = (new CStudentPublicationComment())
            ->setComment('comment')
            ->setUser($teacher)
            ->setPublication($publication)
            ->setParent($publication)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($comment);
        $em->flush();
        $em->clear();

        /** @var CStudentPublication $publication */
        $publication = $publicationRepo->find($publication->getIid());

        $this->assertSame(1, $publication->getComments()->count());
        $this->assertSame(1, $publicationRepo->count([]));
        $this->assertSame(1, $commentRepo->count([]));

        $em->remove($publication);
        $em->flush();

        $this->assertSame(0, $publicationRepo->count([]));
        $this->assertSame(0, $commentRepo->count([]));
    }
}
