<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Security\Authorization\Voter\SessionVoter;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class SessionVoterTest extends WebTestCase
{
    use ChamiloTestTrait;

    // @dataProvider provideVoteTests not working
    public function testVote(): void
    {
        $client = static::createClient();
        $tests = $this->provideVoteTests();
        $voter = $this->getContainer()->get(SessionVoter::class);
        foreach ($tests as $message => $test) {
            [$expected, $user, $session] = $test;
            $client->loginUser($user);
            $token = $this->getContainer()->get('security.untracked_token_storage')->getToken();
            $this->assertSame($expected, $voter->vote($token, $session, ['VIEW']), $message);
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

        $session = $this->createSession('session');
        $publicCourse = $this->createCourse('public');
        $publicCourse->addUser($studentWithAccess, 0, null, CourseRelUser::STUDENT);
        $publicCourse->addUser($teacherWithAccess, 0, null, CourseRelUser::TEACHER);
        $em->persist($publicCourse);

        $session->addCourse($publicCourse);
        $em->persist($session);
        $em->flush();

        $denied = VoterInterface::ACCESS_DENIED;
        $granted = VoterInterface::ACCESS_GRANTED;

        yield 'admin access to course' => [$granted, $admin, $session];
        yield 'student access to course' => [$denied, $student, $session];
        yield 'studentWithAccess access to course' => [$denied, $studentWithAccess, $session];
        yield 'teacher no access to course' => [$denied, $teacher, $session];
        yield 'teacher with access to course' => [$denied, $teacherWithAccess, $session];

        $session->addUserInCourse(Session::STUDENT, $studentWithAccess, $publicCourse);
        $session->addUserInCourse(Session::COURSE_COACH, $teacherWithAccess, $publicCourse);
        $em->persist($session);
        $em->flush();

        yield 'admin access to course in session' => [$granted, $admin, $session];
        yield 'student access to course in session' => [$denied, $student, $session];
        yield 'studentWithAccess access to course in session' => [$granted, $studentWithAccess, $session];
        yield 'teacher no access to course in session' => [$denied, $teacher, $session];
        //yield 'teacher with access to course in session' => [$granted, $teacherWithAccess, $session];
    }
}
