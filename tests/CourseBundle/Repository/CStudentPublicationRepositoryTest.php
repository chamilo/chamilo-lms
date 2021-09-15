<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CStudentPublicationRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

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
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame('publi', (string) $item);
        $this->assertSame(1, $repo->count([]));
    }

    public function testFindAllByCourse(): void
    {
        self::bootKernel();

        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CStudentPublicationRepository::class);

        $course = $this->createCourse('new');

        $qb = $repo->findAllByCourse($course);
        $this->assertSame(0, \count($qb->getQuery()->getResult()));

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
        $this->assertSame(1, \count($qb->getQuery()->getResult()));
    }

    public function testGetStudentAssignments(): void
    {
        self::bootKernel();

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
        $this->assertSame(0, \count($qb->getQuery()->getResult()));

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
        $this->assertSame(1, \count($qb->getQuery()->getResult()));

        //$this->assertSame(1, $repo->countUserPublications($student, $course));
        //$this->assertSame(1, $repo->findWorksByTeacher($teacher, $course));
    }

    public function testGetStudentPublicationByUser(): void
    {
        self::bootKernel();

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
