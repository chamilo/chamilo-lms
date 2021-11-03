<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class CourseVoterTest extends WebTestCase
{
    use ChamiloTestTrait;

    // @dataProvider provideVoteTests not working
    public function testVote(): void
    {
        $client = static::createClient();
        $tests = $this->provideVoteTests();
        $voter = $this->getContainer()->get(CourseVoter::class);
        foreach ($tests as $message => $test) {
            [$expected, $user, $course] = $test;
            $client->loginUser($user);
            $token = $this->getContainer()->get('security.untracked_token_storage')->getToken();
            $this->assertSame($expected, $voter->vote($token, $course, ['VIEW']), $message);
        }
    }

    public function provideVoteTests()
    {
        $em = $this->getEntityManager();
        $admin = $this->getAdmin();
        $student = $this->createUser('student');
        $studentWithAccess = $this->createUser('student_access');

        $teacher = $this->createUser('teacher', '', '', 'ROLE_TEACHER');
        $teacherWithAccess = $this->createUser('teacher_with_access', '', '', 'ROLE_TEACHER');

        // Group in public course.
        $publicCourse = $this->createCourse('public');
        $publicCourse->addUser($studentWithAccess, 0, null, CourseRelUser::STUDENT);
        $publicCourse->addUser($teacherWithAccess, 0, null, CourseRelUser::TEACHER);
        $em->persist($publicCourse);
        $em->flush();

        $denied = VoterInterface::ACCESS_DENIED;
        $granted = VoterInterface::ACCESS_GRANTED;

        yield 'admin access to course' => [$granted, $admin, $publicCourse];
        yield 'student access to course' => [$granted, $student, $publicCourse];
        yield 'student access to course' => [$granted, $studentWithAccess, $publicCourse];
        yield 'teacher no access to course' => [$granted, $teacher, $publicCourse];
        yield 'teacher with access to course' => [$granted, $teacherWithAccess, $publicCourse];

        // REGISTERED course.
        $registeredCourse = $this->createCourse('registered');
        $registeredCourse->setVisibility(Course::REGISTERED);
        $registeredCourse->addUser($studentWithAccess, 0, null, CourseRelUser::STUDENT);
        $registeredCourse->addUser($teacherWithAccess, 0, null, CourseRelUser::TEACHER);
        $em->persist($registeredCourse);
        $em->flush();

        $admin = $this->getAdmin();

        yield 'admin access to reg course' => [$granted, $admin, $registeredCourse];
        yield 'teacher access to reg course' => [$granted, $teacherWithAccess, $registeredCourse];
        yield 'student access to reg course ' => [$granted, $studentWithAccess, $registeredCourse];
        yield 'teacher no access to reg course' => [$denied, $teacher, $registeredCourse];
        yield 'student no access to reg course' => [$denied, $student, $registeredCourse];

        // Hidden
        $registeredCourse->setVisibility(Course::HIDDEN);
        $em->persist($registeredCourse);
        $em->flush();

        yield 'admin access to reg course' => [$granted, $admin, $registeredCourse];
        yield 'teacher access to reg course' => [$denied, $teacherWithAccess, $registeredCourse];
        yield 'student access to reg course ' => [$denied, $studentWithAccess, $registeredCourse];
        yield 'teacher no access to reg course' => [$denied, $teacher, $registeredCourse];
        yield 'student no access to reg course' => [$denied, $student, $registeredCourse];
    }
}
