<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use CourseManager;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization tests for functions of CourseManager (public/main/inc/lib/course.lib.php) that no other
 * CourseManager*CharacterizationTest reaches. Fixtures start with "cm3c_".
 */
final class CourseManagerTier3cCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/inc/lib/course.lib.php');
        $request->setSession($session);
        Container::setRequest($request);
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function sub(User $user, Course $course, int $status = STUDENT, int $relationType = 0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO course_rel_user (user_id, c_id, relation_type, status, is_tutor, sort, user_course_cat, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 0, 0, 0, 0, 0)',
            [$user->getId(), $course->getId(), $relationType, $status]
        );
    }

    private function loginAs(User $user): void
    {
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        self::getContainer()->get('security.token_storage')->setToken($token);
        ChamiloSession::write('_user', ['id' => $user->getId()]);
    }

    public function testCanUserSubscribeToCourseRefusesAnonymous(): void
    {
        $course = $this->createCourse('cm3c_csub_anon');
        $this->assertFalse(CourseManager::canUserSubscribeToCourse((string) $course->getCode()));
    }

    public function testCanUserSubscribeToCourseRefusesAlreadySubscribedButNotHrm(): void
    {
        $course = $this->createCourse('cm3c_csub_a');
        $student = $this->createUser('cm3c_csub_student');
        $hrm = $this->createUser('cm3c_csub_hrm');
        $other = $this->createUser('cm3c_csub_other');
        $this->sub($student, $course, STUDENT);
        $this->sub($hrm, $course, DRH, COURSE_RELATION_TYPE_RRHH);

        $this->loginAs($student);
        $this->assertFalse(CourseManager::canUserSubscribeToCourse((string) $course->getCode()), 'already a member');
        $this->loginAs($hrm);
        $this->assertTrue(CourseManager::canUserSubscribeToCourse((string) $course->getCode()), 'an HR relation is not a membership');
        $this->loginAs($other);
        $this->assertTrue(CourseManager::canUserSubscribeToCourse((string) $course->getCode()));
    }

    public function testIsUserSubscribedInCourseAsHrmIsFalseForANonHrManager(): void
    {
        $course = $this->createCourse('cm3c_hrm_a');
        $user = $this->createUser('cm3c_hrm_user');
        $this->sub($user, $course, DRH, COURSE_RELATION_TYPE_RRHH);
        $this->loginAs($user);

        $info = ['real_id' => $course->getId()];
        $this->assertFalse(CourseManager::isUserSubscribedInCourseAsHrm($user->getId(), $info), 'the caller is not logged as an HR manager');
    }

    public function testIsUserSubscribedInCourseCountsTheGeneralCoachOfTheSessionOnlyWhenAsked(): void
    {
        $course = $this->createCourse('cm3c_gcoach_a');
        $session = $this->createSession('cm3c_gcoach_session'); // its general coach is the admin
        $admin = $this->getUser('admin');
        $other = $this->createUser('cm3c_gcoach_other');

        $this->assertFalse(CourseManager::is_user_subscribed_in_course($admin->getId(), $course->getCode(), false, $session->getId()));
        $this->assertTrue(CourseManager::is_user_subscribed_in_course($admin->getId(), $course->getCode(), true, $session->getId()));
        $this->assertFalse(CourseManager::is_user_subscribed_in_course($other->getId(), $course->getCode(), true, $session->getId()));
    }
}
