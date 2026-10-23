<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * The generic update operation edits the assignment settings, grading fields
 * included. A learner owns their submission's resource node, but must never use
 * that ownership to grade it.
 */
final class CStudentPublicationUpdateSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testLearnerCannotGradeOwnSubmission(): void
    {
        $ctx = $this->bootstrapScenario('learner');
        $iid = $ctx['submission']->getIid();

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['student']))->request(
            'PUT',
            '/api/c_student_publications/'.$iid.'?cid='.$ctx['course']->getId(),
            [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => ['qualification' => 100],
            ]
        );

        $this->assertResponseStatusCodeSame(403);

        $submission = $this->reload($iid);
        $this->assertSame(0.0, $submission->getQualification());
        $this->assertSame(0, $submission->getQualificatorId());
    }

    public function testTeacherCanStillUpdateAssignment(): void
    {
        $ctx = $this->bootstrapScenario('teacher');
        $iid = $ctx['assignment']->getIid();

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'PUT',
            '/api/c_student_publications/'.$iid.'?cid='.$ctx['course']->getId(),
            [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => ['qualification' => 20],
            ]
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(20.0, $this->reload($iid)->getQualification());
    }

    /**
     * @return array{course: Course, teacher: User, student: User, assignment: CStudentPublication, submission: CStudentPublication}
     */
    private function bootstrapScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('Publication Update Course '.$suffix);
        $course->setVisibility(Course::REGISTERED);

        $teacher = $this->createUser('pub_update_teacher_'.$suffix);
        $student = $this->createUser('pub_update_student_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $courseRepo->update($course);

        $assignment = (new CStudentPublication())
            ->setTitle('Assignment '.$suffix)
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setQualification(10)
            ->setCreator($teacher)
            ->setUser($teacher)
            ->setActive(1)
            ->addCourseLink($course)
        ;

        $submission = (new CStudentPublication())
            ->setTitle('Submission '.$suffix)
            ->setParent($assignment)
            ->setPublicationParent($assignment)
            ->setFiletype('file')
            ->setWeight(0)
            ->setCreator($student)
            ->setUser($student)
            ->setActive(1)
            ->addCourseLink($course)
        ;

        $em = $this->getEntityManager();
        $em->persist($assignment);
        $em->persist($submission);
        $em->flush();

        return [
            'course' => $course,
            'teacher' => $teacher,
            'student' => $student,
            'assignment' => $assignment,
            'submission' => $submission,
        ];
    }

    private function reload(int $iid): CStudentPublication
    {
        $em = $this->getEntityManager();
        $em->clear();

        return $em->getRepository(CStudentPublication::class)->find($iid);
    }
}
