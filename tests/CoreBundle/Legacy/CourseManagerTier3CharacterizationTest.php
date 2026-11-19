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

    // ---------------------------------------------------------------- settings and sort

    public function testGetCourseSettingReturnsTheRowOfThatCourseAndVariable(): void
    {
        $a = $this->createCourse('cm3_setting_a');
        $b = $this->createCourse('cm3_setting_b');
        foreach ([[$a, 'cm3_var', 'A'], [$b, 'cm3_var', 'B'], [$a, 'cm3_other', 'O']] as [$course, $variable, $value]) {
            $this->conn()->executeStatement(
                "INSERT INTO c_course_setting (c_id, variable, value, type, category, title) VALUES (?, ?, ?, 'checkbox', 'cm3', 'cm3')",
                [$course->getId(), $variable, $value]
            );
        }

        $row = CourseManager::getCourseSetting("cm3_var", $a->getId());
        $this->assertSame('A', $row['value']);
        $this->assertSame('cm3_var', $row['variable']);
        $this->assertSame('A', $row[1], 'fetch_array adds numeric keys');
        $this->assertSame([], CourseManager::getCourseSetting("cm3_var'x", $a->getId()), 'no row gives an empty array');
    }

    public function testUserCourseSortWithoutAlphabeticalPlaceMovesEveryUncategorizedCourseDown(): void
    {
        $z1 = $this->createCourse('cm3_sort_z1');
        $z2 = $this->createCourse('cm3_sort_z2');
        $hr = $this->createCourse('cm3_sort_hr');
        $cat = $this->createCourse('cm3_sort_cat');
        $new = $this->createCourse('cm3_sort_a');
        $user = $this->createUser('cm3_sort_user');
        $this->sub($user, $z1, STUDENT, 0, 1);
        $this->sub($user, $z2, STUDENT, 0, 2);
        $this->sub($user, $hr, STUDENT, COURSE_RELATION_TYPE_RRHH, 5);
        $this->sub($user, $cat, STUDENT, 0, 9, 7);

        // The new course sorts before all of them: it takes the lowest sort value and the others are pushed down.
        $this->assertSame(1, CourseManager::userCourseSort($user->getId(), $new->getCode()));

        $sorts = $this->conn()->fetchAllKeyValue('SELECT c_id, sort FROM course_rel_user WHERE user_id = ?', [$user->getId()]);
        $this->assertSame(2, (int) $sorts[$z1->getId()]);
        $this->assertSame(3, (int) $sorts[$z2->getId()]);
        $this->assertSame(6, (int) $sorts[$hr->getId()], 'the HR relation is shifted too in this branch');
        $this->assertSame(9, (int) $sorts[$cat->getId()], 'a course inside a user category is not shifted');
    }

    // ---------------------------------------------------------------- course access tracking

    private function access(User $user, Course $course, string $date, int $sessionId): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO track_e_course_access (user_id, c_id, login_course_date, counter, session_id, user_ip) VALUES (?, ?, ?, 1, ?, ?)',
            [$user->getId(), $course->getId(), $date, $sessionId, '127.0.0.1']
        );
    }

    public function testCourseAccessPerCourseAndSessionIsBoundedByTheDatesInclusive(): void
    {
        $course = $this->createCourse('cm3_acc_a');
        $other = $this->createCourse('cm3_acc_b');
        $user = $this->createUser('cm3_acc_user');
        $this->access($user, $course, '2032-03-01 00:00:00', 0);
        $this->access($user, $course, '2032-03-05 12:00:00', 0);
        $this->access($user, $course, '2032-03-31 23:59:59', 0);
        $this->access($user, $course, '2032-04-02 00:00:00', 0);
        $this->access($user, $course, '2032-03-05 12:00:00', 9);
        $this->access($user, $other, '2032-03-05 12:00:00', 0);

        $rows = CourseManager::getCourseAccessPerCourseAndSession($course->getId(), 0, '2032-03-01 00:00:00', '2032-03-31 23:59:59');

        $dates = array_column($rows, 'login_course_date');
        sort($dates);
        $this->assertSame(['2032-03-01 00:00:00', '2032-03-05 12:00:00', '2032-03-31 23:59:59'], $dates, 'BETWEEN is inclusive; other course, other session and later dates are left out');
        $this->assertSame((string) $course->getId(), (string) $rows[0]['c_id']);
    }

    public function testFirstCourseAccessPerSessionAndUserIsTheEarliestOne(): void
    {
        $course = $this->createCourse('cm3_first_a');
        $user = $this->createUser('cm3_first_user');
        $other = $this->createUser('cm3_first_other');
        $this->access($user, $course, '2032-05-09 10:00:00', 4);
        $this->access($user, $course, '2032-05-02 10:00:00', 4);
        $this->access($user, $course, '2032-05-01 10:00:00', 0);
        $this->access($other, $course, '2032-04-01 10:00:00', 4);

        $row = CourseManager::getFirstCourseAccessPerSessionAndUser(4, $user->getId());

        $this->assertSame('2032-05-02 10:00:00', $row['login_course_date']);
        $this->assertSame([], CourseManager::getFirstCourseAccessPerSessionAndUser(4, 999999999), 'no row gives an empty array');
    }
}
