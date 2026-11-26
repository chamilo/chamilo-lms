<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Assignment;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Service\Assignment\AssignmentSessionDuplicator;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationAssignment;
use Chamilo\CourseBundle\Entity\CStudentPublicationRelUser;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class AssignmentSessionDuplicatorTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCopiesAreSessionScopedIdempotentAndNotSubmissions(): void
    {
        $em = $this->getEntityManager();
        $course = $this->createCourse('Assignment duplication course');
        $first = $this->createSession('Assignment duplication first');
        $second = $this->createSession('Assignment duplication second');
        $first->addCourse($course);
        $second->addCourse($course);
        $teacher = $this->getUser('admin');
        $learner = $this->createUser('assignment_copy_learner');
        $source = (new CStudentPublication())
            ->setTitle('Original task')
            ->setDescription('Instructions')
            ->setUser($teacher)
            ->setCreator($teacher)
            ->setQualification(20)
            ->setWeight(30)
            ->setAllowTextAssignment(1)
            ->setParent($course)
            ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_PUBLISHED)
        ;
        $source->setAssignment((new CStudentPublicationAssignment())
            ->setExpiresOn(new DateTime('2027-01-01'))
            ->setEndsOn(new DateTime('2027-01-02'))
            ->setEventCalendarId(123));
        $em->persist($source);
        $em->persist((new CStudentPublicationRelUser())->setPublication($source)->setUser($learner));
        $em->flush();

        $submission = (new CStudentPublication())
            ->setTitle('Base submission')
            ->setFiletype('file')
            ->setUser($learner)
            ->setCreator($learner)
            ->setPublicationParent($source)
            ->setParent($source)
            ->addCourseLink($course)
        ;
        $em->persist($submission);
        $em->flush();

        $service = self::getContainer()->get(AssignmentSessionDuplicator::class);
        $copy = $service->getOrCreate($source, $course, $first);
        $other = $service->getOrCreate($source, $course, $second);

        self::assertSame($copy->getIid(), $service->getOrCreate($source, $course, $first)->getIid());
        self::assertNotSame($copy->getIid(), $other->getIid());
        self::assertNotSame($source->getResourceNode()->getId(), $copy->getResourceNode()->getId());
        self::assertSame($source->getIid(), $copy->getPublicationParent()->getIid());
        self::assertSame($teacher->getId(), $copy->getCreator()->getId());
        self::assertSame($teacher->getId(), $copy->getUser()->getId());
        self::assertSame(20.0, $copy->getQualification());
        self::assertSame(30.0, $copy->getWeight());
        self::assertSame(1, $copy->getAllowTextAssignment());
        self::assertNull($copy->getAssignment()->getExpiresOn());
        self::assertNull($copy->getAssignment()->getEndsOn());
        self::assertSame(0, $copy->getAssignment()->getEventCalendarId());
        self::assertNull($service->findExactCourseLink($copy, $course, null));
        self::assertNull($service->findExactCourseLink($copy, $course, $second));
        self::assertNotNull($service->findExactCourseLink($copy, $course, $first));
        self::assertSame(1, $em->getRepository(CStudentPublicationRelUser::class)->count(['publication' => $copy, 'user' => $learner]));

        $repo = self::getContainer()->get(CStudentPublicationRepository::class);
        self::assertSame([(int) $learner->getId()], array_map('intval', $repo->findUserIdsWithSubmissions($source->getIid())));
        self::assertSame([], $repo->findUserIdsWithSubmissions($copy->getIid()));
        self::assertSame([], $repo->findUserIdsWithSubmissions($other->getIid()));
        $em->refresh($source);
        self::assertSame(1, $source->getUniqueStudentAttemptsTotal(), 'Session copies are not learner attempts.');
        self::assertSame(1, $repo->count(['publicationParent' => $source, 'filetype' => 'file']));
        self::assertSame(2, $repo->count(['publicationParent' => $source, 'filetype' => 'folder']));
    }

    public function testDeletedSourceCannotBeCopiedFromAStaleManagedEntity(): void
    {
        $em = $this->getEntityManager();
        $course = $this->createCourse('Assignment soft deletion race');
        $session = $this->createSession('Assignment soft deletion session');
        $session->addCourse($course);
        $teacher = $this->getUser('admin');
        $source = (new CStudentPublication())
            ->setTitle('Original soft-deleted task')
            ->setUser($teacher)
            ->setCreator($teacher)
            ->setParent($course)
            ->addCourseLink($course)
        ;
        $em->persist($source);
        $em->flush();
        $id = $source->getIid();

        // The legacy soft-delete path writes directly without refreshing managed objects.
        $em->getConnection()->update('c_student_publication', ['active' => 2], ['iid' => $id]);
        self::assertSame(1, $source->getActive());
        $service = self::getContainer()->get(AssignmentSessionDuplicator::class);

        try {
            $service->getOrCreate($source, $course, $session);
            self::fail('A deleted original must not be duplicated.');
        } catch (InvalidArgumentException) {
            self::assertSame(2, $source->getActive());
            self::assertSame(0, $em->getRepository(CStudentPublication::class)->count(['publicationParent' => $id, 'filetype' => 'folder']));
        }
    }

    public function testRepositoryDeletionDoesNotCascadeToSessionCopies(): void
    {
        $em = $this->getEntityManager();
        $course = $this->createCourse('Assignment repository deletion');
        $session = $this->createSession('Assignment repository deletion session');
        $session->addCourse($course);
        $teacher = $this->getUser('admin');
        $source = (new CStudentPublication())
            ->setTitle('Original protected task')
            ->setUser($teacher)
            ->setCreator($teacher)
            ->setParent($course)
            ->addCourseLink($course)
        ;
        $em->persist($source);
        $em->flush();
        $copy = self::getContainer()->get(AssignmentSessionDuplicator::class)->getOrCreate($source, $course, $session);
        $repo = self::getContainer()->get(CStudentPublicationRepository::class);

        foreach (['delete', 'hardDelete'] as $method) {
            try {
                $repo->{$method}($source);
                self::fail('Original assignment deletion must be blocked.');
            } catch (ConflictHttpException $exception) {
                self::assertSame(409, $exception->getStatusCode());
                self::assertSame(1, $repo->count(['iid' => $source->getIid()]));
                self::assertSame(1, $repo->count(['iid' => $copy->getIid()]));
            }
        }
    }

    public function testUnrelatedSessionCannotReceiveACopy(): void
    {
        $em = $this->getEntityManager();
        $course = $this->createCourse('Assignment isolated course');
        $session = $this->createSession('Assignment unrelated session');
        $teacher = $this->getUser('admin');
        $source = (new CStudentPublication())
            ->setTitle('Original task')
            ->setUser($teacher)
            ->setCreator($teacher)
            ->setParent($course)
            ->addCourseLink($course)
        ;
        $em->persist($source);
        $em->flush();

        $this->expectException(InvalidArgumentException::class);
        self::getContainer()->get(AssignmentSessionDuplicator::class)->getOrCreate($source, $course, $session);
    }
}
