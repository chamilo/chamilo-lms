<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * Files a learner submits to an assignment are opened by the teachers who grade them.
 * Whatever name and type the learner declares, HTML bytes must never be rendered
 * inline in the Chamilo origin, or a submission could act with the teacher's session.
 */
final class StudentPublicationHtmlFileViewTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const string HTML_BYTES = '<!doctype html><html><body><script>window.probe = 1;</script></body></html>';

    public function testHtmlBytesSubmittedAsAnImageAreDownloadedByTheTeacher(): void
    {
        $ctx = $this->bootstrap('mobile');

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['student']))->request(
            'POST',
            '/api/mobile_assignment_submissions',
            [
                'json' => [
                    'assignmentId' => $ctx['assignment']->getIid(),
                    'courseId' => $ctx['course']->getId(),
                    'title' => 'Image submission',
                    'kind' => 'file',
                    'fileName' => 'payload.png',
                    'mimeType' => 'image/png',
                    'base64Content' => base64_encode(self::HTML_BYTES),
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(201);

        $submission = $this->getEntityManager()
            ->getRepository(CStudentPublication::class)
            ->find($response->toArray()['id'])
        ;
        $this->assertInstanceOf(CStudentPublication::class, $submission);

        $this->assertNotRenderedAsHtml($ctx['teacher'], $submission, $ctx['course']);
    }

    public function testHtmlSubmissionFileIsDownloadedByTheTeacher(): void
    {
        $ctx = $this->bootstrap('html');

        $submission = (new CStudentPublication())
            ->setTitle('HTML submission')
            ->setDescription('')
            ->setPublicationParent($ctx['assignment'])
            ->setParent($ctx['assignment'])
            ->setFiletype('file')
            ->setWeight(100)
            ->setCreator($ctx['student'])
            ->setUser($ctx['student'])
            ->setActive(1)
            ->setAccepted(true)
            ->addCourseLink($ctx['course'])
        ;

        /** @var CStudentPublicationRepository $repository */
        $repository = self::getContainer()->get(CStudentPublicationRepository::class);
        $repository->create($submission);
        $repository->addFileFromString($submission, 'submission.html', 'text/html', self::HTML_BYTES, true);

        $this->assertNotRenderedAsHtml($ctx['teacher'], $submission, $ctx['course']);
    }

    /**
     * @return array{course: Course, teacher: User, student: User, assignment: CStudentPublication}
     */
    private function bootstrap(string $suffix): array
    {
        /** @var CourseRepository $courseRepository */
        $courseRepository = self::getContainer()->get(CourseRepository::class);
        $course = $this->createCourse('SPHTML '.$suffix);
        $course->setVisibility(Course::REGISTERED);

        $teacher = $this->createUser('sphtml_teacher_'.$suffix);
        $student = $this->createUser('sphtml_student_'.$suffix, '', '', 'ROLE_STUDENT');
        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $courseRepository->update($course);

        $assignment = (new CStudentPublication())
            ->setTitle('Image assignment')
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($teacher)
            ->setUser($teacher)
            ->setActive(1)
            ->setAllowTextAssignment(2)
            ->setExtensions('png')
            ->addCourseLink($course)
        ;

        $em = $this->getEntityManager();
        $em->persist($assignment);
        $em->flush();

        return [
            'course' => $course,
            'teacher' => $teacher,
            'student' => $student,
            'assignment' => $assignment,
        ];
    }

    private function assertNotRenderedAsHtml(User $teacher, CStudentPublication $submission, Course $course): void
    {
        $node = $submission->getResourceNode();
        $this->assertNotNull($node);

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($teacher));
        $client->request(
            'GET',
            '/r/assignment/student_publications/'.$node->getUuid().'/view?cid='.$course->getId()
        );
        $this->assertResponseIsSuccessful();

        $headers = $client->getResponse()->getHeaders(false);
        $this->assertStringNotContainsString('html', $headers['content-type'][0] ?? '');
        $this->assertStringStartsWith('attachment', $headers['content-disposition'][0] ?? '');
    }
}
