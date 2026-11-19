<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use CourseManager;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization tests for the functions of CourseManager (public/main/inc/lib/course.lib.php) that
 * CourseManagerCharacterizationTest and CourseManagerTier3CharacterizationTest do not reach (subscribe, delete, tutors, lists). Same conventions: pin today's behaviour, fixtures start with "cm3_".
 */
final class CourseManagerTier3bCharacterizationTest extends KernelTestCase
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

    private function sub(User $user, Course $course, int $status = STUDENT, int $relationType = 0, int $sort = 0, int $cat = 0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO course_rel_user (user_id, c_id, relation_type, status, is_tutor, sort, user_course_cat, legal_agreement, progress)
             VALUES (?, ?, ?, ?, 0, ?, ?, 0, 0)',
            [$user->getId(), $course->getId(), $relationType, $status, $sort, $cat]
        );
    }

    // ---------------------------------------------------------------- subscribeUser

    public function testSubscribeUserIgnoresAnHrManagerRelationWhenLookingForADuplicate(): void
    {
        $course = $this->createCourse('cm3b_subhr_a');
        $user = $this->createUser('cm3b_subhr_user');
        $this->sub($user, $course, STUDENT, COURSE_RELATION_TYPE_RRHH);

        $this->assertTrue(
            CourseManager::subscribeUser($user->getId(), $course->getId(), STUDENT, 0, 0, false, ['flash' => false, 'emails' => false]),
            'an HR-manager link is not a registration as a learner'
        );
        $this->assertSame(
            2,
            (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM course_rel_user WHERE user_id = ? AND c_id = ?', [$user->getId(), $course->getId()])
        );
    }
}
