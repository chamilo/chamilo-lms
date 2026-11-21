<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use AttendanceLink;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use ForumThreadLink;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization of the gradebook link classes under public/main/gradebook/lib/be/ (attendance, forum thread,
 * exercise ...) before their Database::query() calls move to DQL.
 */
final class GradebookLinksTier3CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;
    /** @var int[] */
    private array $users = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/gradebook/index.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        $this->conn = $container->get('doctrine')->getConnection();
        $_GET = [];
        $_POST = [];
        ChamiloSession::write('_user', ['id' => 1, 'user_id' => 1]);
        ChamiloSession::write('_real_cid', 1);
        ChamiloSession::write('_cid', 'AIACT');
        ChamiloSession::write('_course', api_get_course_info('AIACT'));
        ChamiloSession::write('sid', 0);
        ChamiloSession::write('gid', 0);
        ChamiloSession::erase('studentview');
        Container::getEntityManager()->clear();
        $user = $this->getUser('admin');
        $container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, 'main', $user->getRoles())
        );
        $this->users = array_map('intval', $this->conn->fetchFirstColumn('SELECT id FROM user ORDER BY id LIMIT 4'));
    }

    protected function tearDown(): void
    {
        foreach (['_user', 'sid', '_real_cid', '_cid', '_course'] as $key) {
            ChamiloSession::erase($key);
        }
        $_GET = [];
        $_POST = [];
        self::getContainer()->get('security.token_storage')->setToken(null);
        parent::tearDown();
    }

    // ------------------------------------------------------------ attendance

    private function attendance(array $o = []): int
    {
        $this->conn->insert('c_attendance', $o + [
            'title' => 'gl_att_'.(++$this->seq),
            'description' => '',
            'active' => 1,
            'attendance_qualify_title' => null,
            'attendance_qualify_max' => 0,
            'attendance_weight' => 0,
            'locked' => 0,
            'require_unique' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function attendanceDate(int $attendanceId): int
    {
        $this->conn->insert('c_attendance_calendar', [
            'attendance_id' => $attendanceId,
            'date_time' => '2020-01-0'.(1 + $this->seq++ % 9).' 10:00:00',
            'done_attendance' => 1,
            'blocked' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function sheet(int $calendarId, int $userId, ?int $presence): void
    {
        $this->conn->insert('c_attendance_sheet', [
            'attendance_calendar_id' => $calendarId,
            'user_id' => $userId,
            'presence' => $presence,
        ]);
    }

    private function attResult(int $attendanceId, int $userId, int $score): void
    {
        $this->conn->insert('c_attendance_result', [
            'attendance_id' => $attendanceId,
            'user_id' => $userId,
            'score' => $score,
        ]);
    }

    private function attLink(int $attendanceId): AttendanceLink
    {
        $link = new AttendanceLink();
        $link->set_ref_id($attendanceId);

        return $link;
    }

    public function testAttendanceHasResultsIsValidLinkGetLinkAndName(): void
    {
        $att = $this->attendance(['attendance_qualify_title' => 'Qualify title']);
        $link = $this->attLink($att);
        $this->assertFalse($link->has_results());
        $this->attResult($att, $this->users[1], 5);
        $this->assertTrue($link->has_results());
        $this->assertFalse($this->attLink($this->attendance())->has_results());

        $this->assertTrue($link->is_valid_link());
        $this->assertFalse($this->attLink(999999999)->is_valid_link());

        $this->assertStringContainsString('attendance_id='.$att.'&', $link->get_link());
        $this->assertStringContainsString('attendance/index.php?action=attendance_sheet_list', $link->get_link());

        $this->assertSame('Qualify title', $link->get_name());
        // The legacy code reads a "name" column that does not exist: without a qualify title the name is ''.
        $this->assertSame('', $this->attLink($this->attendance())->get_name());
        $this->assertSame('', $this->attLink(999999999)->get_name());
    }

    public function testAttendanceCalcScoreFromResultsTable(): void
    {
        [$a, $b, $c] = $this->users;
        $att = $this->attendance(['attendance_qualify_max' => 10]);
        $this->attResult($att, $a, 10);
        $this->attResult($att, $b, 5);
        $other = $this->attendance(['attendance_qualify_max' => 10]);
        $this->attResult($other, $c, 9);
        $link = $this->attLink($att);

        $this->assertSame([10.0, 10.0], $link->calc_score($a));
        $this->assertSame([5.0, 10.0], $link->calc_score($b));
        $this->assertSame([0.0, 10.0], $link->calc_score($c));
        $this->assertSame([1.0, 1], $link->calc_score($a, 'default'));
        $this->assertSame([0.5, 1], $link->calc_score($b, 'default'));
        $this->assertSame([0.0, 1], $link->calc_score($c, 'default'));

        $this->assertSame([10.0, 10.0], $link->calc_score(null, 'best'));
        $this->assertSame([7.5, 10.0], $link->calc_score(null, 'average'));
        $this->assertSame([1.5, 2], $link->calc_score(null));
        $this->assertSame([null, null], $this->attLink($this->attendance(['attendance_qualify_max' => 10]))->calc_score());
    }

    public function testAttendanceCalcScoreMaxFallsBackToNumberOfDates(): void
    {
        [$a] = $this->users;
        $att = $this->attendance(['attendance_qualify_max' => 0]);
        $this->attendanceDate($att);
        $this->attendanceDate($att);
        $this->attendanceDate($this->attendance());
        $this->attResult($att, $a, 1);

        $this->assertSame([1.0, 2.0], $this->attLink($att)->calc_score($a));
        $this->assertSame([0.5, 1], $this->attLink($att)->calc_score($a, 'default'));
        // No dates, no max: no division by zero, nothing counted.
        $empty = $this->attendance(['attendance_qualify_max' => 0]);
        $this->attResult($empty, $a, 3);
        $this->assertSame([null, null], $this->attLink($empty)->calc_score());
        $this->assertSame([0.0, 0.0], $this->attLink($empty)->calc_score($this->users[1]));
    }

    public function testAttendanceCalcScoreRequireUniqueUsesSheets(): void
    {
        [$a, $b, $c] = $this->users;
        $att = $this->attendance(['attendance_qualify_max' => 0, 'require_unique' => 1]);
        $d1 = $this->attendanceDate($att);
        $d2 = $this->attendanceDate($att);
        $foreign = $this->attendanceDate($this->attendance(['require_unique' => 1]));
        // a: late once (3) -> present; b: absent only (0 and null); c: only on a foreign attendance.
        $this->sheet($d1, $a, 0);
        $this->sheet($d2, $a, 3);
        $this->sheet($d1, $b, 0);
        $this->sheet($d2, $b, null);
        $this->sheet($foreign, $c, 1);
        $link = $this->attLink($att);

        // qualify max 0 in unique mode becomes 100.
        $this->assertSame([100.0, 100.0], $link->calc_score($a));
        $this->assertSame([0.0, 100.0], $link->calc_score($b));
        $this->assertSame([0.0, 100.0], $link->calc_score($c));
        $this->assertSame([1.0, 1], $link->calc_score($a, 'default'));
        $this->assertSame([0.0, 1], $link->calc_score($b, 'default'));

        // users a and b count (c has no sheet on this attendance): only a is present.
        $this->assertSame([100.0, 100.0], $link->calc_score(null, 'best'));
        $this->assertEqualsWithDelta(50.0, $link->calc_score(null, 'average')[0], 0.0001);
        $res = $link->calc_score(null);
        $this->assertEqualsWithDelta(1.0, $res[0], 0.0001);
        $this->assertSame(2, $res[1]);
        $this->assertSame([null, null], $this->attLink($this->attendance(['require_unique' => 1]))->calc_score());
    }

    // ---------------------------------------------------------- forum thread

    private function forum(): int
    {
        $this->conn->insert('c_forum_forum', ['title' => 'gl_forum_'.(++$this->seq), 'locked' => 0, 'forum_image' => '']);

        return (int) $this->conn->lastInsertId();
    }

    private function thread(array $o = []): int
    {
        $this->conn->insert('c_forum_thread', $o + [
            'title' => 'gl_thread_'.(++$this->seq),
            'thread_date' => '2020-01-01 00:00:00',
            'thread_sticky' => 0,
            'locked' => 0,
            'thread_title_qualify' => null,
            'thread_qualify_max' => 10,
            'thread_weight' => 0,
            'thread_peer_qualify' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * The qualify table is read by ITS OWN iid (not by thread_id): a quirk the migration must keep.
     */
    private function qualify(int $iid, int $userId, float $qualify, string $time, ?int $threadId = null): void
    {
        $this->conn->insert('c_forum_thread_qualify', [
            'iid' => $iid,
            'user_id' => $userId,
            'thread_id' => $threadId,
            'qualify_user_id' => 1,
            'c_id' => 1,
            'qualify' => $qualify,
            'qualify_time' => $time,
        ]);
    }

    private function nextQualifyIid(): int
    {
        return 1 + (int) $this->conn->fetchOne('SELECT COALESCE(MAX(iid), 0) FROM c_forum_thread_qualify');
    }

    private function threadLink(int $threadId): ForumThreadLink
    {
        $link = new ForumThreadLink();
        $link->set_ref_id($threadId);

        return $link;
    }

    public function testForumThreadIsValidLinkNameAndLink(): void
    {
        $forum = $this->forum();
        $plain = $this->thread(['title' => 'Plain thread', 'forum_id' => $forum]);
        $qualified = $this->thread(['title' => 'Plain', 'thread_title_qualify' => 'Qualify name']);
        $this->assertTrue($this->threadLink($plain)->is_valid_link());
        $this->assertFalse($this->threadLink(999999999)->is_valid_link());

        $this->assertSame('Plain thread', $this->threadLink($plain)->get_name());
        $this->assertSame('Qualify name', $this->threadLink($qualified)->get_name());

        $url = $this->threadLink($plain)->get_link();
        $this->assertStringContainsString('/forum/'.$forum.'/thread/'.$plain.'?', $url);
        $this->assertStringContainsString('cid=1&sid=0&gid=0&gradebook=view', $url);
        $this->assertSame('', $this->threadLink(999999999)->get_link());
    }

    public function testForumThreadCalcScoreWithoutRowsAndPeerQualify(): void
    {
        [$a, $b] = $this->users;
        $thread = $this->thread(['thread_qualify_max' => 8]);
        // Nothing in the qualify table has iid = $thread: no row for a student, nothing to average.
        $this->assertSame([0, 8.0], $this->threadLink($thread)->calc_score($a));
        $this->assertSame([null, null], $this->threadLink($thread)->calc_score());

        // Peer qualify averages the rows of the student, but only from 3 rows on (the iid filter allows one).
        $peer = $this->thread(['thread_qualify_max' => 4, 'thread_peer_qualify' => 1]);
        $this->qualify($peer, $a, 3.0, '2020-01-01 10:00:00');
        $this->assertSame([0, 4.0], $this->threadLink($peer)->calc_score($a));
        $this->assertSame([0, 4.0], $this->threadLink($peer)->calc_score($b));
    }

    public function testForumThreadCalcScoreReadsRowsOfItsOwnIid(): void
    {
        [$a, $b] = $this->users;
        $iid = $this->nextQualifyIid() + 500;
        $this->conn->insert('c_forum_thread', [
            'iid' => $iid,
            'title' => 'gl_thread_same_iid',
            'thread_date' => '2020-01-01 00:00:00',
            'thread_sticky' => 0,
            'locked' => 0,
            'thread_qualify_max' => 8,
            'thread_weight' => 0,
            'thread_peer_qualify' => 0,
        ]);
        $this->qualify($iid, $a, 6.0, '2020-01-01 10:00:00', $iid);
        $link = $this->threadLink($iid);

        $this->assertSame([6.0, 8.0], $link->calc_score($a));
        $this->assertSame([0, 8.0], $link->calc_score($b));
        $this->assertSame([0.75, 1], $link->calc_score());
        $this->assertSame([6.0, 8.0], $link->calc_score(null, 'best'));
        $this->assertSame([6.0, 8.0], $link->calc_score(null, 'average'));
    }

    public function testForumThreadCalcScoreSaveAndDeleteLinkedData(): void
    {
        $thread = $this->thread(['thread_qualify_max' => 8, 'thread_weight' => 2, 'thread_title_qualify' => 'T']);
        $other = $this->thread(['thread_qualify_max' => 5, 'thread_weight' => 3, 'thread_title_qualify' => 'O']);
        $link = $this->threadLink($thread);
        $link->set_weight(6.25);
        $link->save_linked_data();
        $row = $this->conn->fetchAssociative('SELECT thread_weight, thread_qualify_max FROM c_forum_thread WHERE iid = ?', [$thread]);
        $this->assertEquals(6.25, $row['thread_weight']);
        $this->assertEquals(8, $row['thread_qualify_max']);
        $this->assertEquals(3, $this->conn->fetchOne('SELECT thread_weight FROM c_forum_thread WHERE iid = ?', [$other]));

        $link->delete_linked_data();
        $row = $this->conn->fetchAssociative('SELECT thread_weight, thread_qualify_max, thread_title_qualify FROM c_forum_thread WHERE iid = ?', [$thread]);
        $this->assertEquals(0, $row['thread_weight']);
        $this->assertEquals(0, $row['thread_qualify_max']);
        $this->assertSame('', $row['thread_title_qualify']);
        $row = $this->conn->fetchAssociative('SELECT thread_weight, thread_qualify_max, thread_title_qualify FROM c_forum_thread WHERE iid = ?', [$other]);
        $this->assertEquals(3, $row['thread_weight']);
        $this->assertEquals(5, $row['thread_qualify_max']);
        $this->assertSame('O', $row['thread_title_qualify']);

        // Without a ref id nothing is written.
        $none = new ForumThreadLink();
        $none->set_ref_id(0);
        $none->delete_linked_data();
        $this->assertEquals(5, $this->conn->fetchOne('SELECT thread_qualify_max FROM c_forum_thread WHERE iid = ?', [$other]));
    }
}
