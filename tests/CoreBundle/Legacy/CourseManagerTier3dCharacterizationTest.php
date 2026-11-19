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
 * CourseManager*CharacterizationTest reaches. Fixtures start with "cm3d_".
 */
final class CourseManagerTier3dCharacterizationTest extends KernelTestCase
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

    public function testGetUsersCountInCourseIgnoresSoftDeletedUsers(): void
    {
        $course = $this->createCourse('cm3d_cnt_a');
        $alive = $this->createUser('cm3d_cnt_alive');
        $gone = $this->createUser('cm3d_cnt_gone');
        $this->sub($alive, $course);
        $this->sub($gone, $course);
        $this->assertEquals(2, CourseManager::get_users_count_in_course($course->getCode()));

        $this->conn()->executeStatement('UPDATE user SET active = ? WHERE id = ?', [USER_SOFT_DELETED, $gone->getId()]);
        $this->assertEquals(1, CourseManager::get_users_count_in_course($course->getCode()));
    }
}
