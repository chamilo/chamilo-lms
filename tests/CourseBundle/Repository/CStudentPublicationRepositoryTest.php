<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationRelUser;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

class CStudentPublicationRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CStudentPublicationRepository::class);
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $item = (new CStudentPublication())
            ->setTitle('publi')
            ->setDescription('desc')
            ->setAuthor('author')
            ->setAccepted(false)
            ->setPostGroupId(0)
            ->setSentDate(new DateTime())
            ->setHasProperties(0)
            ->setViewProperties(false)
            ->setQualification(0)
            ->setDateOfQualification(new DateTime())
            ->setQualificatorId(0)
            ->setAllowTextAssignment(0)
            ->setContainsFile(0)
            ->setDocumentId(0)
            ->setFileSize(0)
            ->setAssignment(null)
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame('publi', (string) $item);
        $this->assertSame(1, $repo->count([]));

        $count = $repo->countUserPublications($teacher, $course);
        $this->assertSame(1, $count);

        $count = $repo->countCoursePublications($course);
        $this->assertSame(1, $count);

        $courseRepo->delete($course);
        $this->assertSame(0, $repo->count([]));
    }

    public function testCreateWithPublicationRelUser(): void
    {
        $em = $this->getEntityManager();

        $repo = self::getContainer()->get(CStudentPublicationRepository::class);
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $publicationRelUserRepo = $em->getRepository(CStudentPublicationRelUser::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');
        $student = $this->createUser('student');

        $publication = (new CStudentPublication())
            ->setTitle('publi')
            ->setDescription('desc')
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($teacher)
        ;
        $em->persist($publication);

        $pubRelUser = (new CStudentPublicationRelUser())
            ->setUser($student)
            ->setPublication($publication)
        ;
        $em->persist($pubRelUser);
        $em->flush();

        $this->assertSame(1, $repo->count([]));
        $this->assertSame(1, $courseRepo->count([]));
        $this->assertSame(1, $publicationRelUserRepo->count([]));

        $course = $this->getCourse($course->getId());

        $courseRepo->delete($course);

        $this->assertSame(0, $repo->count([]));
        $this->assertSame(0, $courseRepo->count([]));
        $this->assertSame(0, $publicationRelUserRepo->count([]));
    }

    public function testFindAllByCourse(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CStudentPublicationRepository::class);

        $course = $this->createCourse('new');

        $qb = $repo->findAllByCourse($course);
        $this->assertCount(0, $qb->getQuery()->getResult());

        $teacher = $this->createUser('teacher');

        $item = (new CStudentPublication())
            ->setTitle('publi')
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($item);
        $em->flush();

        $qb = $repo->findAllByCourse($course);
        $this->assertCount(1, $qb->getQuery()->getResult());
    }

    public function testGetStudentAssignments(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CStudentPublicationRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');
        $student = $this->createUser('student');

        $item = (new CStudentPublication())
            ->setTitle('publi')
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($item);
        $em->flush();

        $qb = $repo->getStudentAssignments($item, $course);
        $this->assertCount(0, $qb->getQuery()->getResult());

        $studentResult = (new CStudentPublication())
            ->setTitle('work from student')
            ->setPublicationParent($item)
            ->setParent($item)
            ->setFiletype('file')
            ->setWeight(100)
            ->setCreator($student)
            ->setActive(1)
            ->addCourseLink($course)
        ;
        $em->persist($studentResult);
        $em->flush();

        $qb = $repo->getStudentAssignments($item, $course);
        $this->assertCount(1, $qb->getQuery()->getResult());

        //$this->assertSame(1, $repo->countUserPublications($student, $course));
        //$this->assertSame(1, $repo->findWorksByTeacher($teacher, $course));
    }

    public function testGetStudentPublicationByUser(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CStudentPublicationRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $item = (new CStudentPublication())
            ->setTitle('publi')
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($item);
        $em->flush();

        $result = $repo->getStudentPublicationByUser($teacher, $course);
        $this->assertNotEmpty($result);
    }
}
