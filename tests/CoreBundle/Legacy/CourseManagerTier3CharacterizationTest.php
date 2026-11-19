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
 * CourseManagerCharacterizationTest does not reach. Same conventions: pin today's behaviour, fixtures start with "cm3_".
 */
final class CourseManagerTier3CharacterizationTest extends KernelTestCase
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

    // ---------------------------------------------------------------- course attributes

    public function testUpdateAttributesSetsOnlyNonEmptyValues(): void
    {
        $course = $this->createCourse('cm3_attr_a');
        $id = (int) $course->getId();

        $this->conn()->executeStatement("UPDATE course SET tutor_name = 'cm3_keep' WHERE id = ?", [$id]);

        CourseManager::update_attributes($id, ['title' => "cm3_attr_b'x", 'tutor_name' => '', 'visibility' => '1']);

        $row = $this->conn()->fetchAssociative('SELECT title, tutor_name, visibility FROM course WHERE id = ?', [$id]);
        $this->assertSame("cm3_attr_b'x", $row['title'], 'a quote in the value is stored as data');
        $this->assertSame('1', (string) $row['visibility']);
        $this->assertSame('cm3_keep', $row['tutor_name'], 'an empty value is skipped, not written');
    }

    public function testUpdateAttributesTouchesOnlyTheGivenCourse(): void
    {
        $a = $this->createCourse('cm3_attr_only_a');
        $b = $this->createCourse('cm3_attr_only_b');

        CourseManager::update_attributes((int) $a->getId(), ['title' => 'cm3_attr_only_renamed']);

        $this->assertSame('cm3_attr_only_renamed', $this->conn()->fetchOne('SELECT title FROM course WHERE id = ?', [$a->getId()]));
        $this->assertSame('cm3_attr_only_b', $this->conn()->fetchOne('SELECT title FROM course WHERE id = ?', [$b->getId()]));
    }

    // ---------------------------------------------------------------- sessions

    public function testSessionCategoryIdBySessionId(): void
    {
        $session = $this->createSession('cm3_session_cat');
        $sid = (int) $session->getId();

        $this->assertSame([], CourseManager::get_session_category_id_by_session_id(0), 'empty id gives an empty array');
        $this->assertNull(
            CourseManager::get_session_category_id_by_session_id($sid),
            'a session without category gives Database::result()\'s null'
        );

        $this->conn()->executeStatement("INSERT INTO session_category (title) VALUES ('cm3_cat')");
        $catId = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('UPDATE session SET session_category_id = ? WHERE id = ?', [$catId, $sid]);

        $this->assertEquals($catId, CourseManager::get_session_category_id_by_session_id($sid));
    }
}
