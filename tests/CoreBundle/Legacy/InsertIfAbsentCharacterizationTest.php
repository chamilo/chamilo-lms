<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use CourseManager;
use Database;
use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use SessionManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UserManager;

/**
 * Pins the "insert if absent" behaviour that INSERT IGNORE used to give the legacy subscription code
 * (session users, session courses from a CSV import, HRM manager courses, student bosses), and tests
 * Database::insertIfAbsent() itself. Fixtures are tagged "iia_".
 */
final class InsertIfAbsentCharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $httpSession = new HttpSession(new MockArraySessionStorage());
        $httpSession->set('_user', ['id' => $this->getAdmin()->getId()]);
        $httpSession->set('access_url_id', $this->getAccessUrl()->getId());
        $request = new Request();
        $request->setSession($httpSession);
        Container::setSession($httpSession);
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        $admin = $this->getAdmin();
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($admin, 'main', $admin->getRoles())
        );
        Database::setManager($this->getEntityManager());
        Database::setConnection($this->getEntityManager()->getConnection());
    }

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function scalar(string $sql, array $params = []): int
    {
        return (int) $this->conn()->fetchOne($sql, $params);
    }

    // ------------------------------------------------------------------ the legacy callers

    public function testSubscribeUsersToSessionWithAccessDatesStoresThemAndDuration0(): void
    {
        $alice = $this->createUser('iia_alice');
        $sid = (int) SessionManager::create_session('iia_s_dates', '', '', '', '', '', '', [], 0, 1, false, null, '');
        $this->conn()->executeStatement(
            "UPDATE session SET duration = 0, access_start_date = '2031-01-01 10:00:00', access_end_date = '2031-06-30 11:00:00' WHERE id = ?",
            [$sid]
        );
        $this->getEntityManager()->clear();

        $this->assertTrue(SessionManager::subscribeUsersToSession($sid, [$alice->getId()], SESSION_VISIBLE_READ_ONLY, false));

        $row = $this->conn()->fetchAssociative(
            'SELECT relation_type, duration, access_start_date, access_end_date, registered_at FROM session_rel_user WHERE session_id = ? AND user_id = ?',
            [$sid, $alice->getId()]
        );
        $this->assertSame(Session::STUDENT, (int) $row['relation_type']);
        $this->assertSame(0, (int) $row['duration']);
        $this->assertSame('2031-01-01 10:00:00', $row['access_start_date']);
        $this->assertSame('2031-06-30 11:00:00', $row['access_end_date']);
        $this->assertNotNull($row['registered_at']);

        // Subscribing again changes nothing: still one row.
        SessionManager::subscribeUsersToSession($sid, [$alice->getId()], SESSION_VISIBLE_READ_ONLY, false);
        $this->assertSame(1, $this->scalar('SELECT COUNT(*) FROM session_rel_user WHERE session_id = ? AND user_id = ? AND relation_type = ?', [$sid, $alice->getId(), Session::STUDENT]));
    }

    public function testSubscribeUsersToSessionWithoutDatesStoresNullDates(): void
    {
        $alice = $this->createUser('iia_alice2');
        $sid = (int) SessionManager::create_session('iia_s_nodates', '', '', '', '', '', '', [], 0, 1, false, null, '');
        $this->conn()->executeStatement('UPDATE session SET duration = 0, access_start_date = NULL, access_end_date = NULL WHERE id = ?', [$sid]);
        $this->getEntityManager()->clear();

        SessionManager::subscribeUsersToSession($sid, [$alice->getId()], SESSION_VISIBLE_READ_ONLY, false);

        $row = $this->conn()->fetchAssociative('SELECT duration, access_start_date, access_end_date FROM session_rel_user WHERE session_id = ? AND user_id = ?', [$sid, $alice->getId()]);
        $this->assertSame(0, (int) $row['duration']);
        $this->assertNull($row['access_start_date']);
        $this->assertNull($row['access_end_date']);
    }

    public function testSubscribeUsersToSessionOfADurationSessionLeavesAccessDatesNull(): void
    {
        $alice = $this->createUser('iia_alice3');
        $sid = (int) SessionManager::create_session('iia_s_duration', '', '', '', '', '', '', [], 0, 1, false, null, '');
        $this->conn()->executeStatement(
            "UPDATE session SET duration = 30, access_start_date = '2031-01-01 10:00:00', access_end_date = '2031-06-30 11:00:00' WHERE id = ?",
            [$sid]
        );
        $this->getEntityManager()->clear();

        SessionManager::subscribeUsersToSession($sid, [$alice->getId()], SESSION_VISIBLE_READ_ONLY, false);

        $row = $this->conn()->fetchAssociative('SELECT duration, access_start_date, access_end_date FROM session_rel_user WHERE session_id = ? AND user_id = ?', [$sid, $alice->getId()]);
        $this->assertSame(0, (int) $row['duration']);
        $this->assertNull($row['access_start_date'], 'The statement of a duration session does not write the access dates.');
        $this->assertNull($row['access_end_date']);
    }

    public function testSubscribeUsersToSessionTreatsAStudentAndAnHrmAsDifferentRelations(): void
    {
        $alice = $this->createUser('iia_alice4');
        $sid = (int) SessionManager::create_session('iia_s_rel', '', '', '', '', '', '', [], 0, 1, false, null, '');
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_user (relation_type, session_id, user_id, duration, registered_at) VALUES (?, ?, ?, 0, NOW())',
            [Session::DRH, $sid, $alice->getId()]
        );

        SessionManager::subscribeUsersToSession($sid, [$alice->getId()], SESSION_VISIBLE_READ_ONLY, false);

        $this->assertSame(1, $this->scalar('SELECT COUNT(*) FROM session_rel_user WHERE session_id = ? AND user_id = ? AND relation_type = ?', [$sid, $alice->getId(), Session::DRH]));
        $this->assertSame(1, $this->scalar('SELECT COUNT(*) FROM session_rel_user WHERE session_id = ? AND user_id = ? AND relation_type = ?', [$sid, $alice->getId(), Session::STUDENT]));
    }

    public function testImportCsvAddsARepeatedCourseOnlyOnce(): void
    {
        $course = $this->createCourse('iia_csv_course');
        $code = $course->getCode();
        $csv = tempnam(sys_get_temp_dir(), 'iia').'.csv';
        file_put_contents(
            $csv,
            "SessionName,DateStart,DateEnd,Courses\niia_csv_session,2031-01-01,2031-06-30,\"".$code.'|'.$code."\"\n"
        );
        $tb = [];
        $gb = [];

        try {
            SessionManager::importCSV(
                $csv,
                false,
                $this->getAdmin()->getId(),
                null,
                [],
                null,
                null,
                null,
                1,
                [],
                false,
                false,
                false,
                true,
                true,
                null,
                $tb,
                $gb
            );
        } finally {
            unlink($csv);
        }

        $sid = $this->scalar('SELECT id FROM session WHERE title = ?', ['iia_csv_session']);
        $this->assertGreaterThan(0, $sid, 'The session was created.');
        $this->assertSame(1, $this->scalar('SELECT COUNT(*) FROM session_rel_course WHERE session_id = ? AND c_id = ?', [$sid, $course->getId()]));
    }

    public function testSubscribeCoursesToHrmManagerKeepsTheFirstOfARepeatedCourse(): void
    {
        $a = $this->createCourse('iia_hrm_a');
        $hrm = $this->createUser('iia_hrm');

        // course_rel_user has no unique index: the old INSERT IGNORE inserted the repeated course twice and
        // counted both. insertIfAbsent() inserts it once and counts one.
        $this->assertSame(1, CourseManager::subscribeCoursesToHrmManager($hrm->getId(), [$a->getCode(), $a->getCode()]));
        $this->assertSame(1, $this->scalar('SELECT COUNT(*) FROM course_rel_user WHERE c_id = ? AND user_id = ? AND relation_type = ?', [$a->getId(), $hrm->getId(), COURSE_RELATION_TYPE_RRHH]));
    }

    public function testSubscribeCoursesToHrmManagerKeepsTheSameCourseOfAnotherManager(): void
    {
        $a = $this->createCourse('iia_hrm_shared');
        $hrm1 = $this->createUser('iia_hrm_one');
        $hrm2 = $this->createUser('iia_hrm_two');

        $this->assertSame(1, CourseManager::subscribeCoursesToHrmManager($hrm1->getId(), [$a->getCode()]));
        $this->assertSame(1, CourseManager::subscribeCoursesToHrmManager($hrm2->getId(), [$a->getCode()]));
        $this->assertSame(2, $this->scalar('SELECT COUNT(*) FROM course_rel_user WHERE c_id = ? AND relation_type = ?', [$a->getId(), COURSE_RELATION_TYPE_RRHH]));
    }

    public function testSubscribeUserToBossListStoresOneRowPerRepeatedBossAndCountsEachInsertAttempt(): void
    {
        $boss = $this->createUser('iia_boss')->getId();
        $student = $this->createUser('iia_student')->getId();

        // INSERT IGNORE returned a result even when it ignored the row, so the caller counted (and notified) it.
        $this->assertSame(2, UserManager::subscribeUserToBossList($student, [$boss, $boss]));
        $this->assertSame(1, $this->scalar('SELECT COUNT(*) FROM user_rel_user WHERE user_id = ? AND friend_user_id = ?', [$student, $boss]));
        // INSERT IGNORE stored the implicit zero dates here (created_at has no default); real dates are written now.
        $row = $this->conn()->fetchAssociative('SELECT created_at, updated_at, relation_type FROM user_rel_user WHERE user_id = ?', [$student]);
        $this->assertSame(UserRelUser::USER_RELATION_TYPE_BOSS, (int) $row['relation_type']);
        $this->assertStringStartsNotWith('0000', $row['created_at']);
        $this->assertStringStartsNotWith('0000', $row['updated_at']);
    }

    // ------------------------------------------------------------------ Database::insertIfAbsent()

    public function testInsertIfAbsentInsertsWhenAbsentAndIgnoresWhenPresent(): void
    {
        $u = $this->createUser('iia_h_u')->getId();
        $f = $this->createUser('iia_h_f')->getId();

        $this->assertTrue(Database::insertIfAbsent('user_rel_user', ['user_id' => $u, 'friend_user_id' => $f, 'relation_type' => 3, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'], ['user_id', 'friend_user_id', 'relation_type']));
        $this->assertFalse(Database::insertIfAbsent('user_rel_user', ['user_id' => $u, 'friend_user_id' => $f, 'relation_type' => 3, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'], ['user_id', 'friend_user_id', 'relation_type']));
        $this->assertSame(1, $this->scalar('SELECT COUNT(*) FROM user_rel_user WHERE user_id = ? AND friend_user_id = ?', [$u, $f]));
    }

    public function testInsertIfAbsentUsesTheWholeCompositeKeyAndLeavesAnExistingRowUntouched(): void
    {
        $course = $this->createCourse('iia_h_course');
        $user = $this->createUser('iia_h_user')->getId();
        $cid = $course->getId();
        $key = ['c_id', 'user_id', 'relation_type'];

        $this->assertTrue(Database::insertIfAbsent('course_rel_user', ['c_id' => $cid, 'user_id' => $user, 'status' => 5, 'progress' => 0, 'relation_type' => 0], $key));
        // Same key, other non-key value: present, nothing changes.
        $this->assertFalse(Database::insertIfAbsent('course_rel_user', ['c_id' => $cid, 'user_id' => $user, 'status' => 1, 'progress' => 0, 'relation_type' => 0], $key));
        $this->assertSame(5, $this->scalar('SELECT status FROM course_rel_user WHERE c_id = ? AND user_id = ?', [$cid, $user]));
        // One column of the composite key differs: absent.
        $this->assertTrue(Database::insertIfAbsent('course_rel_user', ['c_id' => $cid, 'user_id' => $user, 'status' => 1, 'progress' => 0, 'relation_type' => 2], $key));
        $this->assertSame(2, $this->scalar('SELECT COUNT(*) FROM course_rel_user WHERE c_id = ? AND user_id = ?', [$cid, $user]));
    }

    public function testInsertIfAbsentNeverMatchesANullKeyValue(): void
    {
        $course = $this->createCourse('iia_h_null');
        $user = $this->createUser('iia_h_nulluser')->getId();
        $values = ['c_id' => $course->getId(), 'user_id' => $user, 'status' => 5, 'progress' => 0, 'relation_type' => 0, 'sort' => null];

        // NULL = NULL is not true in SQL and unique indexes accept several NULLs: such a key is always absent.
        $this->assertTrue(Database::insertIfAbsent('course_rel_user', $values, ['c_id', 'user_id', 'sort']));
        $this->assertTrue(Database::insertIfAbsent('course_rel_user', $values, ['c_id', 'user_id', 'sort']));
        $this->assertSame(2, $this->scalar('SELECT COUNT(*) FROM course_rel_user WHERE c_id = ? AND user_id = ?', [$course->getId(), $user]));
    }

    public function testInsertIfAbsentReportsAUniqueIndexCollisionAsPresentAndKeepsTheTransactionUsable(): void
    {
        $u = $this->createUser('iia_h_race_u')->getId();
        $f = $this->createUser('iia_h_race_f')->getId();
        $conn = $this->conn();
        $conn->executeStatement('INSERT INTO user_rel_user (user_id, friend_user_id, relation_type, created_at, updated_at) VALUES (?, ?, 3, NOW(), NOW())', [$u, $f]);

        // Adding created_at to the key makes the SELECT miss (the row was created now), so the INSERT runs and
        // collides on the unique index exactly as when a concurrent insert wins the race between both statements.
        $values = ['user_id' => $u, 'friend_user_id' => $f, 'relation_type' => 3, 'created_at' => '2000-01-01 00:00:00', 'updated_at' => '2000-01-01 00:00:00'];
        // Inside a transaction of its own, so that the savepoint path is the one exercised.
        $conn->beginTransaction();
        $this->assertTrue($conn->isTransactionActive());
        $this->assertFalse(Database::insertIfAbsent('user_rel_user', $values, ['user_id', 'friend_user_id', 'relation_type', 'created_at']));
        $this->assertTrue($conn->isTransactionActive());
        $this->assertSame(1, $this->scalar('SELECT COUNT(*) FROM user_rel_user WHERE user_id = ? AND friend_user_id = ?', [$u, $f]));
        // The surrounding transaction is still usable afterwards.
        $this->assertTrue(Database::insertIfAbsent('user_rel_user', ['user_id' => $u, 'friend_user_id' => $f, 'relation_type' => 4, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'], ['user_id', 'friend_user_id', 'relation_type']));
        $conn->commit();
    }

    public function testInsertIfAbsentRejectsBadArguments(): void
    {
        foreach ([
            ['user_rel_user; DROP TABLE x', ['user_id' => 1], ['user_id']],
            ['user_rel_user', ['user_id) VALUES (1); --' => 1], ['user_id) VALUES (1); --']],
            ['user_rel_user', ['user_id' => 1], ['friend_user_id']],
            ['user_rel_user', [], ['user_id']],
            ['user_rel_user', ['user_id' => 1], []],
        ] as [$table, $values, $unique]) {
            try {
                Database::insertIfAbsent($table, $values, $unique);
                $this->fail('InvalidArgumentException expected.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
