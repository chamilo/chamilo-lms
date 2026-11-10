<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

/**
 * #1647: learners can review their own HTML assignment feedback without
 * gaining permission to read other students' submissions or bypass cid checks.
 */
final class StudentPublicationHtmlPreviewTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * @return array{course: Course, teacher: User, student: User, classmate: User, submission: CStudentPublication}
     */
    private function bootstrap(string $suffix): array
    {
        /** @var CourseRepository $courseRepository */
        $courseRepository = self::getContainer()->get(CourseRepository::class);
        $course = $this->createCourse('HTML1647 '.$suffix);
        $course->setVisibility(Course::REGISTERED);

        $teacher = $this->createUser('html1647_teacher_'.$suffix);
        $student = $this->createUser('html1647_student_'.$suffix);
        $classmate = $this->createUser('html1647_classmate_'.$suffix);
        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $course->addUserAsStudent($classmate);
        $courseRepository->update($course);

        $assignment = (new CStudentPublication())
            ->setTitle('HTML Preview Assignment')
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($teacher)
            ->setUser($teacher)
            ->setActive(1)
            ->addCourseLink($course)
        ;

        $submission = (new CStudentPublication())
            ->setTitle('HTML Preview Submission')
            ->setDescription('Student HTML content')
            ->setPublicationParent($assignment)
            ->setParent($assignment)
            ->setFiletype('file')
            ->setWeight(100)
            ->setCreator($student)
            ->setUser($student)
            ->setActive(1)
            ->setAccepted(true)
            ->setSentDate(new DateTime())
            ->setQualification(0.0)
            ->setQualificatorId(0)
            ->addCourseLink($course)
        ;

        $em = $this->getEntityManager();
        $em->persist($assignment);
        $em->persist($submission);
        $em->flush();

        /** @var CStudentPublicationRepository $publicationRepository */
        $publicationRepository = self::getContainer()->get(CStudentPublicationRepository::class);
        $publicationRepository->addFileFromString(
            $submission,
            'student-preview.html',
            'text/html',
            '<h2>Private HTML submission</h2><script>alert("unsafe")</script>',
            true
        );

        return [
            'course' => $course,
            'teacher' => $teacher,
            'student' => $student,
            'classmate' => $classmate,
            'submission' => $submission,
        ];
    }

    public function testSubmissionOwnerCanPreviewSanitizedHtml(): void
    {
        $ctx = $this->bootstrap('owner');

        // Fail at the fixture if the context link was not persisted.
        $this->assertNotNull($this->getEntityManager()->getRepository(ResourceLink::class)->findOneBy([
            'resourceNode' => $ctx['submission']->getResourceNode(),
            'course' => $ctx['course'],
            'session' => null,
            'group' => null,
        ]));

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['student']))->request(
            'GET',
            '/assignments/submissions/'.$ctx['submission']->getIid().'/html-preview?cid='.$ctx['course']->getId()
        );

        $this->assertResponseIsSuccessful();
        $html = (string) ($response->toArray()['html'] ?? '');
        $this->assertStringContainsString('Private HTML submission', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function testAnotherEnrolledStudentCannotPreviewTheSubmission(): void
    {
        $ctx = $this->bootstrap('classmate');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['classmate']))->request(
            'GET',
            '/assignments/submissions/'.$ctx['submission']->getIid().'/html-preview?cid='.$ctx['course']->getId()
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCourseTeacherCanStillPreviewTheSubmission(): void
    {
        $ctx = $this->bootstrap('teacher');

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'GET',
            '/assignments/submissions/'.$ctx['submission']->getIid().'/html-preview?cid='.$ctx['course']->getId()
        );

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Private HTML submission', (string) ($response->toArray()['html'] ?? ''));
    }

    public function testOwnerCannotPreviewUsingAnotherCourseContext(): void
    {
        $ctx = $this->bootstrap('wrongcid');

        /** @var CourseRepository $courseRepository */
        $courseRepository = self::getContainer()->get(CourseRepository::class);
        $otherCourse = $this->createCourse('HTML1647 Alt');
        $otherCourse->setVisibility(Course::REGISTERED);
        $otherCourse->addUserAsStudent($ctx['student']);
        $courseRepository->update($otherCourse);

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['student']))->request(
            'GET',
            '/assignments/submissions/'.$ctx['submission']->getIid().'/html-preview?cid='.$otherCourse->getId()
        );

        $this->assertResponseStatusCodeSame(404);
    }
}
