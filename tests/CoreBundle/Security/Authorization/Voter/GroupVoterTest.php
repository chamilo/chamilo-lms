<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Security\Authorization\Voter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Security\Authorization\Voter\GroupVoter;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CGroupRelTutor;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class GroupVoterTest extends WebTestCase
{
    use ChamiloTestTrait;

    // @dataProvider provideVoteTests not working
    public function testVote(): void
    {
        $client = static::createClient();
        $tests = $this->provideVoteTests();
        $voter = $this->getContainer()->get(GroupVoter::class);
        foreach ($tests as $message => $test) {
            [$expected, $user, $group] = $test;
            $client->loginUser($user);
            $token = $this->getContainer()->get('security.untracked_token_storage')->getToken();
            $this->assertSame($expected, $voter->vote($token, $group, ['VIEW']), $message);
        }
    }

    public function provideVoteTests()
    {
        $em = $this->getEntityManager();
        $admin = $this->getAdmin();
        $student = $this->createUser('student');
        $studentWithAccess = $this->createUser('student_access');
        $studentInGroup2IsTutor = $this->createUser('student_group2_tutor');
        $studentInGroup2IsMember = $this->createUser('student_group2');

        $teacher = $this->createUser('teacher', '', '', 'ROLE_TEACHER');
        $teacherWithAccess = $this->createUser('teacher_with_access', '', '', 'ROLE_TEACHER');

        // Group in public course.
        $publicCourse = $this->createCourse('public');
        $publicCourse->addUser($studentWithAccess, 0, null, CourseRelUser::STUDENT);
        $publicCourse->addUser($teacherWithAccess, 0, null, CourseRelUser::TEACHER);
        $em->persist($publicCourse);

        $group = (new CGroup())
            ->setName('Group')
            ->setParent($publicCourse)
            ->setCreator($admin)
            ->setMaxStudent(100)
        ;
        $this->assertHasNoEntityViolations($group);
        $em->persist($group);
        $em->flush();

        yield 'admin access to course' => [VoterInterface::ACCESS_GRANTED, $admin, $group];
        yield 'student with no access to course' => [VoterInterface::ACCESS_GRANTED, $student, $group];
        yield 'student with access to course' => [VoterInterface::ACCESS_GRANTED, $studentWithAccess, $group];
        yield 'teacher with no access to course' => [VoterInterface::ACCESS_GRANTED, $teacher, $group];
        yield 'teacher with access to course' => [VoterInterface::ACCESS_GRANTED, $teacherWithAccess, $group];

        $group->setStatus(false);
        $em->persist($group);
        $em->flush();

        yield 'admin access to course' => [VoterInterface::ACCESS_GRANTED, $admin, $group];
        yield 'student with no access to course' => [VoterInterface::ACCESS_DENIED, $student, $group];
        yield 'student with access to course' => [VoterInterface::ACCESS_DENIED, $studentWithAccess, $group];
        yield 'teacher with no access to course' => [VoterInterface::ACCESS_DENIED, $teacher, $group];
        yield 'teacher with access to course' => [VoterInterface::ACCESS_GRANTED, $teacherWithAccess, $group];

        // REGISTERED course.
        $registeredCourse = $this->createCourse('registered');
        $registeredCourse->setVisibility(Course::REGISTERED);
        $registeredCourse->addUser($studentWithAccess, 0, null, CourseRelUser::STUDENT);
        $registeredCourse->addUser($studentInGroup2IsTutor, 0, null, CourseRelUser::STUDENT);
        $registeredCourse->addUser($studentInGroup2IsMember, 0, null, CourseRelUser::STUDENT);
        $registeredCourse->addUser($teacherWithAccess, 0, null, CourseRelUser::TEACHER);
        $em->persist($registeredCourse);
        $em->flush();

        $admin = $this->getAdmin();

        $group2 = (new CGroup())
            ->setName('Group2')
            ->setParent($registeredCourse)
            ->setCreator($admin)
            ->setStatus(false)
            ->setMaxStudent(100)
        ;
        $em->persist($group2);
        $em->flush();

        $groupRelUser = (new CGroupRelUser())
            ->setStatus(1)
            ->setUser($studentInGroup2IsMember)
            ->setGroup($group2)
            ->setRole('test')
            ->setCId($registeredCourse->getId())
        ;
        $em->persist($groupRelUser);
        $em->flush();

        $groupRelTutor = (new CGroupRelTutor())
            ->setUser($studentInGroup2IsTutor)
            ->setGroup($group2)
            ->setCId($registeredCourse->getId())
        ;
        $em->persist($groupRelTutor);
        $em->flush();

        $denied = VoterInterface::ACCESS_DENIED;
        $granted = VoterInterface::ACCESS_GRANTED;

        yield 'admin access to reg course' => [$granted, $admin, $group2];
        yield 'teacher access to reg course' => [$granted, $teacherWithAccess, $group2];
        yield 'teacher no access to reg course' => [$denied, $teacher, $group2];
        yield 'student no access to reg course' => [$denied, $student, $group2];
        yield 'student access to reg course group status=false' => [$denied, $studentWithAccess, $group2];
        yield 'student in group2 access to reg course group status=false' => [$denied, $studentInGroup2IsMember, $group2];

        $group2->setStatus(true);
        $em->persist($group2);
        $em->flush();

        yield 'admin access to reg course status=true' => [$granted, $admin, $group2];
        yield 'teacher access to reg course status=true' => [$granted, $teacherWithAccess, $group2];
        yield 'teacher no access to reg course status=true' => [$denied, $teacher, $group2];
        yield 'student no access to reg course status=true' => [$denied, $student, $group2];
        yield 'student no access to group 2' => [$granted, $studentWithAccess, $group2];
        yield 'student access to reg course group status=true' => [$granted, $studentInGroup2IsMember, $group2];
    }
}
