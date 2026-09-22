<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Security;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Security\CourseAccessResolver;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CourseAccessResolverTest extends WebTestCase
{
    use ChamiloTestTrait;

    /**
     * A CLOSED course is not a full lockout in Chamilo: legacy api_protect_course_script()
     * kept it reachable by the course's own teacher(s), unlike HIDDEN which is admin-only.
     * Regression coverage for the bug where CourseAccessResolver granted no contextual role
     * at all for a CLOSED course, 403-ing the teacher out of their own course tools
     * (c_tools / c_tool_intros) even though their course_rel_user subscription was untouched.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testClosedCourseKeepsTeacherAccessButNotStudentAccess(): void
    {
        static::createClient();
        $em = $this->getEntityManager();
        $resolver = static::getContainer()->get(CourseAccessResolver::class);

        $teacher = $this->createUser('closed_course_teacher');
        $student = $this->createUser('closed_course_student');

        $course = $this->createCourse('closed_course');
        $course->setVisibility(Course::CLOSED);
        $course->addSubscriptionForUser($teacher, 0, null, CourseRelUser::TEACHER);
        $course->addSubscriptionForUser($student, 0, null, CourseRelUser::STUDENT);
        $em->persist($course);
        $em->flush();

        $this->assertSame(
            [ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER],
            $resolver->resolveCourseRoles($teacher, $course),
            'The course teacher must keep access to their own course while it is closed.'
        );

        $this->assertSame(
            [],
            $resolver->resolveCourseRoles($student, $course),
            'A closed course must not be reachable by a merely subscribed student, only its teacher(s).'
        );
    }

    /**
     * Sanity check that the CLOSED fix did not loosen HIDDEN, which must stay admin-only
     * (no contextual role for anyone, teacher included) per legacy semantics.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testHiddenCourseGrantsNoRoleEvenToItsTeacher(): void
    {
        static::createClient();
        $em = $this->getEntityManager();
        $resolver = static::getContainer()->get(CourseAccessResolver::class);

        $teacher = $this->createUser('hidden_course_teacher');

        $course = $this->createCourse('hidden_course');
        $course->setVisibility(Course::HIDDEN);
        $course->addSubscriptionForUser($teacher, 0, null, CourseRelUser::TEACHER);
        $em->persist($course);
        $em->flush();

        $this->assertSame(
            [],
            $resolver->resolveCourseRoles($teacher, $course),
            'A HIDDEN course must stay admin-only, unlike CLOSED which keeps teacher access.'
        );
    }
}
