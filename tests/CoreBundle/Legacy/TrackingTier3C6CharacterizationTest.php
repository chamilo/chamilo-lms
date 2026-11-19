<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\UuidV4;
use Tracking;

/**
 * Wave 3 characterization (part C6) of Tracking::processUserDataMove() and of the two private helpers it uses
 * (getSessionResourceRows, moveResourceLinksToSession).
 *
 * This is data-moving code: every table it touches gets an exact before/after row assertion (the whole row is
 * compared, only session_id may change) and the rows that must stay (other user, other course, other session,
 * other file) are compared whole as well. Rows are raw prepared statements, rolled back by DAMA.
 */
final class TrackingTier3C6CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const COURSE = 1;
    private const COURSE_TWO = 2;

    private Connection $conn;
    private int $seq = 0;
    private int $creator = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new Session(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/session/move_users_from_course_to_session.php');
        $request->setSession($httpSession);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        Database::setManager($container->get('doctrine')->getManager());
        $this->creator = $this->user('c6_creator');
    }

    // ------------------------------------------------------------------
    // Whole-row before/after of every table touched
    // ------------------------------------------------------------------

    public function testMoveToASessionChangesOnlyTheSessionOfEveryTableAndKeepsTheOtherRowsWhole(): void
    {
        $u = $this->user('c6_u');
        $o = $this->user('c6_other');
        $from = $this->session('c6_from');
        $to = $this->session('c6_to');
        $third = $this->session('c6_third');
        $mine = $this->seed($u, $from);
        // Rows that must not move: another user, another course, another session.
        $noise = array_merge(
            $this->seed($o, $from),
            $this->seedOtherCourseAndSession($u, $from, $third)
        );

        $before = $this->snapshot($mine, $noise);
        $out = $this->moveData($u, $from, $to, true);
        $after = $this->snapshot($mine, $noise);

        foreach ($before['mine'] as $table => $rows) {
            $this->assertNotEmpty($rows, $table);
            foreach ($rows as $key => $row) {
                $expected = $row;
                $expected['session_id'] = (string) $to;
                $this->assertSame($expected, $after['mine'][$table][$key], "moved row of $table");
            }
        }
        $this->assertSame($before['noise'], $after['noise'], 'rows that must stay');

        $this->assertStringContainsString('Stats moved.', $out);
        // One line per updated table, in this order (the table names are those of the legacy constants).
        $lines = [];
        preg_match_all('/Table (\S+) - (\d+) records updated/', $out, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $lines[$match[1]] = (int) $match[2];
        }
        $this->assertSame(
            [
                'track_e_exercises' => 1,
                'track_e_course_access' => 1,
                'track_e_lastaccess' => 1,
                'c_lp_view' => 1,
                'agenda' => 1,
                'c_student_publication' => 1,
                'c_dropbox_file' => 1,
            ],
            $lines
        );
    }

    public function testMoveToTheBaseContextWritesNullWhereNullableAndZeroWhereNotNull(): void
    {
        $u = $this->user('c6_u');
        $from = $this->session('c6_from');
        $mine = $this->seed($u, $from);

        $before = $this->snapshot($mine, []);
        $this->moveData($u, $from, 0, true);
        $after = $this->snapshot($mine, []);

        $nullable = ['track_e_exercises', 'track_e_lastaccess', 'c_lp_view', 'resource_link_agenda', 'resource_link_work'];
        foreach ($before['mine'] as $table => $rows) {
            foreach ($rows as $key => $row) {
                $expected = $row;
                $expected['session_id'] = in_array($table, $nullable, true) ? null : '0';
                $this->assertSame($expected, $after['mine'][$table][$key], $table);
            }
        }
    }

    public function testMoveFromTheBaseContextFindsTheNullAndZeroRowsAndLeavesTheSessionRows(): void
    {
        $u = $this->user('c6_u');
        $from = $this->session('c6_from');
        $to = $this->session('c6_to');
        $mineNull = $this->seed($u, null);
        $mineZero = $this->seed($u, 0);
        $inSession = $this->seed($u, $from);

        $beforeNull = $this->snapshot($mineNull, []);
        $beforeZero = $this->snapshot($mineZero, []);
        $beforeSession = $this->snapshot($inSession, []);
        $this->moveData($u, 0, $to, true);

        // The nullable columns hold NULL for the base context and the exercises and last access also match 0;
        // the NOT NULL columns can only hold 0 (matched by "session_id = 0"). A stored NULL is found by the
        // queries that go through api_get_session_condition() only.
        $afterNull = $this->snapshot($mineNull, []);
        foreach (['track_e_exercises', 'track_e_lastaccess', 'c_lp_view', 'resource_link_agenda', 'resource_link_work'] as $table) {
            foreach ($beforeNull['mine'][$table] as $key => $row) {
                $this->assertNull($row['session_id'], $table);
                $this->assertSame((string) $to, $afterNull['mine'][$table][$key]['session_id'], "NULL row of $table moved");
            }
        }
        $afterZero = $this->snapshot($mineZero, []);
        foreach ($beforeZero['mine'] as $table => $rows) {
            foreach ($rows as $key => $row) {
                $this->assertSame((string) $to, $afterZero['mine'][$table][$key]['session_id'], "0 row of $table moved");
                $expected = $row;
                $expected['session_id'] = (string) $to;
                $this->assertSame($expected, $afterZero['mine'][$table][$key], $table);
            }
        }
        // The rows of a real session stay where they are.
        $this->assertSame($beforeSession, $this->snapshot($inSession, []));
    }

    public function testDropboxFilesAreSelectedByUploaderCourseAndSessionAndTheirPostsFollowByFileOnly(): void
    {
        $u = $this->user('c6_u');
        $o = $this->user('c6_other');
        $from = $this->session('c6_from');
        $to = $this->session('c6_to');
        $mine = $this->dropboxFile(self::COURSE, $u, $from);
        $postMine = $this->dropboxPost(self::COURSE, $mine, $from, $o);
        // A post of the same file recorded in another session still follows the file (no session filter on posts).
        $postOtherSession = $this->dropboxPost(self::COURSE, $mine, 0, $o);
        $otherUploader = $this->dropboxFile(self::COURSE, $o, $from);
        $postOtherUploader = $this->dropboxPost(self::COURSE, $otherUploader, $from, $u);
        $otherCourse = $this->dropboxFile(self::COURSE_TWO, $u, $from);
        $otherSession = $this->dropboxFile(self::COURSE, $u, $this->session('c6_other_session'));

        $files = fn (): array => $this->conn->fetchAllAssociative(
            'SELECT * FROM c_dropbox_file WHERE iid IN (?, ?, ?, ?) ORDER BY iid',
            [$mine, $otherUploader, $otherCourse, $otherSession]
        );
        $posts = fn (): array => $this->conn->fetchAllAssociative(
            'SELECT * FROM c_dropbox_post WHERE iid IN (?, ?, ?) ORDER BY iid',
            [$postMine, $postOtherSession, $postOtherUploader]
        );
        $filesBefore = $files();
        $postsBefore = $posts();

        $this->moveData($u, $from, $to, true);

        $expectedFiles = $filesBefore;
        $expectedFiles[0]['session_id'] = $to;
        $this->assertSame($expectedFiles, $files());
        $expectedPosts = $postsBefore;
        $expectedPosts[0]['session_id'] = $to;
        $expectedPosts[1]['session_id'] = $to;
        $this->assertSame($expectedPosts, $posts());
    }

    // ------------------------------------------------------------------
    // Preview (no database change): exact lists of both sides
    // ------------------------------------------------------------------

    public function testPreviewListsTheExercisesOfBothSidesAndChangesNothing(): void
    {
        $u = $this->user('c6_u');
        $from = $this->session('c6_from');
        $to = $this->session('c6_to');
        $mine = $this->seed($u, $from);
        // An exercise of a learning path (orig_lp_id and orig_lp_item_id set) and one of the destination session.
        $lpExe = $this->exercise($u, self::COURSE, $from, 7, '2001-02-02 10:00:00', 4, 8, 3, 5);
        $destExe = $this->exercise($u, self::COURSE, $to, 9, '2001-02-03 10:00:00', 6, 12);
        $o = $this->user('c6_other');
        $otherUserDest = $this->exercise($o, self::COURSE, $to, 11, '2001-02-04 10:00:00', 1, 2);
        $otherCourseDest = $this->exercise($u, self::COURSE_TWO, $to, 12, '2001-02-05 10:00:00', 1, 2);
        $before = $this->snapshot($mine, []);

        $out = $this->flat($this->moveData($u, $from, $to, false));

        $this->assertSame($before, $this->snapshot($mine, []));
        $this->assertStringNotContainsString('Stats moved.', $out);
        $this->assertStringContainsString('Original session #'.$from, $out);
        $this->assertStringContainsString('Destination session #'.$to, $out);
        // The labels are swapped in the code: the exercises WITH a learning path go to the "Tests" list,
        // the ones without to "Tests in learning paths". Pinned as is.
        $origin = substr($out, 0, (int) strpos($out, 'Destination session #'));
        $this->assertMatchesRegularExpression('/Tests.*Attempt #'.$lpExe.' Test #7 Learning path #3 Date = 2001-02-02 10:00:00 Score = 4 Score = 8/', $origin);
        $this->assertMatchesRegularExpression('/Tests in learning paths.*Attempt #'.$mine['exercise'].' Test #1 Date = 2001-02-01 10:00:00 Score = 5 Score = 10/', $origin);
        // The destination column lists the exercise of the destination session only.
        $destination = substr($out, (int) strpos($out, 'Destination session #'));
        $this->assertStringContainsString('Attempt #'.$destExe.' Test #9', $destination);
        $this->assertStringNotContainsString('Attempt #'.$mine['exercise'], $destination);
        $this->assertStringNotContainsString('Attempt #'.$lpExe, $destination);
        $this->assertStringNotContainsString('Attempt #'.$otherUserDest, $out);
        $this->assertStringNotContainsString('Attempt #'.$otherCourseDest, $out);
    }

    public function testPreviewListsTheAgendaAndTheStudentPublicationsOfBothSides(): void
    {
        $u = $this->user('c6_u');
        $o = $this->user('c6_other');
        $from = $this->session('c6_from');
        $to = $this->session('c6_to');
        $mine = $this->seed($u, $from);
        $destAgenda = $this->agenda($u, self::COURSE, $to, 'c6_dest_agenda');
        $destWork = $this->work($u, self::COURSE, $to, 'c6_dest_work');
        // Another creator/user in the origin session, and the same user in another course: not listed.
        $this->agenda($o, self::COURSE, $from, 'c6_other_agenda');
        $this->work($o, self::COURSE, $from, 'c6_other_work');
        $this->agenda($u, self::COURSE_TWO, $from, 'c6_course2_agenda');

        $out = $this->flat($this->moveData($u, $from, $to, false));
        $origin = substr($out, 0, (int) strpos($out, 'Destination session #'));
        $destination = substr($out, (int) strpos($out, 'Destination session #'));

        // Rows are printed key by key: resource_link_id, item_id, title, indexed by the resource link id.
        $this->assertStringContainsString(
            'Id #'.$mine['agendaLink'].' resource_link_id = '.$mine['agendaLink'].' item_id = '.$mine['agenda'].' title = c6_agenda',
            $origin
        );
        $this->assertStringContainsString(
            'Id #'.$mine['workLink'].' resource_link_id = '.$mine['workLink'].' item_id = '.$mine['work'].' title = c6_work',
            $origin
        );
        $this->assertStringNotContainsString('c6_other_', $out);
        $this->assertStringNotContainsString('c6_course2_agenda', $out);
        $this->assertStringContainsString('item_id = '.$destAgenda['id'].' title = c6_dest_agenda', $destination);
        $this->assertStringContainsString('item_id = '.$destWork['id'].' title = c6_dest_work', $destination);
        $this->assertStringNotContainsString('c6_agenda', $destination);
    }

    public function testPreviewListsTheLearningPathViewsOfEachSideOnly(): void
    {
        $u = $this->user('c6_u');
        $o = $this->user('c6_other');
        $from = $this->session('c6_from');
        $to = $this->session('c6_to');
        $lpFrom = $this->lp();
        $lpTo = $this->lp();
        $viewFrom = $this->lpView($u, $lpFrom, self::COURSE, $from, 40);
        $viewTo = $this->lpView($u, $lpTo, self::COURSE, $to, 60);
        // Another user and another course in the origin session: not listed.
        $this->lpView($o, $this->lp(), self::COURSE, $from, 10);
        $this->lpView($u, $this->lp(), self::COURSE_TWO, $from, 10);
        $this->lpView($o, $this->lp(), self::COURSE, $to, 10);
        $this->lpView($u, $this->lp(), self::COURSE_TWO, $to, 10);

        $out = $this->flat($this->moveData($u, $from, $to, false));
        $origin = substr($out, 0, (int) strpos($out, 'Destination session #'));
        $destination = substr($out, (int) strpos($out, 'Destination session #'));

        // Printed by learning path id: "Id #<lp id> score = .. progress = ..".
        $this->assertSame(1, preg_match_all('/Id #(\d+) score/', $origin, $m1));
        $this->assertSame([(string) $lpFrom], $m1[1]);
        $this->assertSame(1, preg_match_all('/Id #(\d+) score/', $destination, $m2));
        $this->assertSame([(string) $lpTo], $m2[1]);
        $this->assertSame($viewFrom, (int) $this->conn->fetchOne('SELECT iid FROM c_lp_view WHERE iid = ? AND session_id = ?', [$viewFrom, $from]));
        $this->assertSame($viewTo, (int) $this->conn->fetchOne('SELECT iid FROM c_lp_view WHERE iid = ? AND session_id = ?', [$viewTo, $to]));
    }

    // ------------------------------------------------------------------
    // The private helpers
    // ------------------------------------------------------------------

    public function testGetSessionResourceRowsKeysTheRowsByLinkIdAndFiltersOwnerCourseAndSession(): void
    {
        $u = $this->user('c6_u');
        $o = $this->user('c6_other');
        $from = $this->session('c6_from');
        $mine = $this->seed($u, $from);
        $this->agenda($o, self::COURSE, $from, 'c6_other_agenda');
        $this->agenda($u, self::COURSE_TWO, $from, 'c6_course2_agenda');
        $this->agenda($u, self::COURSE, $this->session('c6_elsewhere'), 'c6_elsewhere_agenda');
        $baseAgenda = $this->agenda($u, self::COURSE, null, 'c6_base_agenda');

        $rows = $this->rows('c_calendar_event', self::COURSE, $u, $from, 'creator');
        $this->assertSame(
            [$mine['agendaLink'] => ['resource_link_id' => $mine['agendaLink'], 'item_id' => $mine['agenda'], 'title' => 'c6_agenda']],
            $rows
        );

        // Base context: NULL (and 0) links; owner by creator for the agenda, by user_id for the publications.
        $this->assertSame(
            [$baseAgenda['link'] => ['resource_link_id' => $baseAgenda['link'], 'item_id' => $baseAgenda['id'], 'title' => 'c6_base_agenda']],
            $this->rows('c_calendar_event', self::COURSE, $u, 0, 'creator')
        );
        // A publication is owned by its user_id, not by the creator of its node.
        $work = $this->work($o, self::COURSE, $from, 'c6_foreign_node_work', $u);
        $this->assertSame([$work['link']], array_keys($this->rows('c_student_publication', self::COURSE, $u, $from, 'creator')));
        $byUser = $this->rows('c_student_publication', self::COURSE, $u, $from, 'user');
        $this->assertSame([$mine['workLink']], array_keys($byUser));
        $this->assertSame(
            [$work['link']],
            array_keys($this->rows('c_student_publication', self::COURSE, $o, $from, 'user'))
        );
    }

    public function testMoveResourceLinksToSessionCountsTheRowsAndWritesNullForTheBaseContext(): void
    {
        $u = $this->user('c6_u');
        $from = $this->session('c6_from');
        $to = $this->session('c6_to');
        $a = $this->agenda($u, self::COURSE, $from, 'c6_a');
        $b = $this->agenda($u, self::COURSE, $from, 'c6_b');
        $untouched = $this->agenda($u, self::COURSE, $from, 'c6_untouched');
        $untouchedBefore = $this->conn->fetchAssociative('SELECT * FROM resource_link WHERE id = ?', [$untouched['link']]);

        $method = new ReflectionMethod(Tracking::class, 'moveResourceLinksToSession');
        $method->setAccessible(true);

        $this->assertSame(0, $method->invoke(null, [], $to));
        // A row without a usable resource_link_id is skipped and not counted.
        $this->assertSame(2, $method->invoke(null, [
            ['resource_link_id' => $a['link']],
            ['resource_link_id' => 0],
            ['item_id' => 5],
            ['resource_link_id' => (string) $b['link']],
        ], $to));
        $session = fn (int $link): mixed => $this->conn->fetchOne('SELECT session_id FROM resource_link WHERE id = ?', [$link]);
        $this->assertSame($to, (int) $session($a['link']));
        $this->assertSame($to, (int) $session($b['link']));
        $this->assertSame($untouchedBefore, $this->conn->fetchAssociative('SELECT * FROM resource_link WHERE id = ?', [$untouched['link']]));

        $this->assertSame(1, $method->invoke(null, [['resource_link_id' => $a['link']]], 0));
        $this->assertNull($session($a['link']));
        $this->assertSame($to, (int) $session($b['link']));
    }

    // ------------------------------------------------------------------
    // Fixtures
    // ------------------------------------------------------------------

    private function moveData(int $user, int $from, int $to, bool $update): string
    {
        ob_start();

        try {
            Tracking::processUserDataMove($user, api_get_course_info_by_id(self::COURSE), $from, $to, $update);
        } finally {
            $out = (string) ob_get_clean();
        }

        return $out;
    }

    private function flat(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/<[^>]*>/', ' ', $html)));
    }

    private function rows(string $table, int $courseId, int $userId, int $sessionId, string $owner): array
    {
        $method = new ReflectionMethod(Tracking::class, 'getSessionResourceRows');
        $method->setAccessible(true);

        return $method->invoke(null, $table, 'resource_link', 'resource_node', $courseId, $userId, $sessionId, $owner);
    }

    /**
     * Every row a move of $user from the session of $sessionId touches, in course 1.
     *
     * @return array<string, int>
     */
    private function seed(int $user, ?int $sessionId): array
    {
        $ids = [];
        // The nullable columns are foreign keys to session.id: the base context is stored as NULL there.
        $nullable = $sessionId ?: null;
        $ids['exercise'] = $this->exercise($user, self::COURSE, $nullable, 1, '2001-02-01 10:00:00', 5, 10);
        $this->conn->executeStatement(
            "INSERT INTO track_e_attempt_qualify (exe_id, question_id, marks, insert_date, author, teacher_comment, session_id, answer, final)
             VALUES (?, 1, 1, '2001-02-01 10:00:00', 1, 'c6', ?, 'c6', 0)",
            [$ids['exercise'], (int) $sessionId]
        );
        $ids['qualify'] = (int) $this->conn->lastInsertId();
        $this->conn->executeStatement(
            "INSERT INTO track_e_course_access (user_id, c_id, login_course_date, logout_course_date, counter, session_id, user_ip)
             VALUES (?, ?, '2001-03-01 10:00:00', '2001-03-01 11:00:00', 3, ?, '127.0.0.1')",
            [$user, self::COURSE, (int) $sessionId]
        );
        $ids['access'] = (int) $this->conn->lastInsertId();
        $this->conn->executeStatement(
            "INSERT INTO track_e_lastaccess (access_user_id, access_date, c_id, access_tool, session_id) VALUES (?, '2001-03-01 10:00:00', ?, 'learnpath', ?)",
            [$user, self::COURSE, $nullable]
        );
        $ids['last'] = (int) $this->conn->lastInsertId();
        $lp = $this->lp();
        $this->conn->executeStatement(
            'INSERT INTO c_lp_view (user_id, lp_id, c_id, session_id, view_count, last_item, progress) VALUES (?, ?, ?, ?, 2, 0, 40)',
            [$user, $lp, self::COURSE, $nullable]
        );
        $ids['lpView'] = (int) $this->conn->lastInsertId();
        $agenda = $this->agenda($user, self::COURSE, $nullable, 'c6_agenda');
        $ids['agenda'] = $agenda['id'];
        $ids['agendaLink'] = $agenda['link'];
        $work = $this->work($user, self::COURSE, $nullable, 'c6_work');
        $ids['work'] = $work['id'];
        $ids['workLink'] = $work['link'];
        $ids['dropboxFile'] = $this->dropboxFile(self::COURSE, $user, (int) $sessionId);
        $ids['dropboxPost'] = $this->dropboxPost(self::COURSE, $ids['dropboxFile'], (int) $sessionId, $this->creator);

        return $ids;
    }

    /**
     * Rows of the same user/other sessions that a move from $from must not touch.
     *
     * @return array<string, int>
     */
    private function seedOtherCourseAndSession(int $user, int $from, int $third): array
    {
        $ids = [];
        $ids['otherCourseExercise'] = $this->exercise($user, self::COURSE_TWO, $from, 1, '2001-02-01 10:00:00', 5, 10);
        $ids['otherSessionExercise'] = $this->exercise($user, self::COURSE, $third, 1, '2001-02-01 10:00:00', 5, 10);
        $this->conn->executeStatement(
            "INSERT INTO track_e_course_access (user_id, c_id, login_course_date, logout_course_date, counter, session_id, user_ip)
             VALUES (?, ?, '2001-03-01 10:00:00', '2001-03-01 11:00:00', 1, ?, '127.0.0.1')",
            [$user, self::COURSE_TWO, $from]
        );
        $ids['otherCourseAccess'] = (int) $this->conn->lastInsertId();
        $this->conn->executeStatement(
            "INSERT INTO track_e_course_access (user_id, c_id, login_course_date, logout_course_date, counter, session_id, user_ip)
             VALUES (?, ?, '2001-03-01 10:00:00', '2001-03-01 11:00:00', 1, ?, '127.0.0.1')",
            [$user, self::COURSE, $third]
        );
        $ids['otherSessionAccess'] = (int) $this->conn->lastInsertId();
        $this->conn->executeStatement(
            "INSERT INTO track_e_lastaccess (access_user_id, access_date, c_id, access_tool, session_id) VALUES (?, '2001-03-01 10:00:00', ?, 'learnpath', ?)",
            [$user, self::COURSE_TWO, $from]
        );
        $ids['otherCourseLast'] = (int) $this->conn->lastInsertId();
        $lp = $this->lp();
        $this->conn->executeStatement(
            'INSERT INTO c_lp_view (user_id, lp_id, c_id, session_id, view_count, last_item, progress) VALUES (?, ?, ?, ?, 1, 0, 10)',
            [$user, $lp, self::COURSE_TWO, $from]
        );
        $ids['otherCourseLpView'] = (int) $this->conn->lastInsertId();
        $this->conn->executeStatement(
            'INSERT INTO c_lp_view (user_id, lp_id, c_id, session_id, view_count, last_item, progress) VALUES (?, ?, ?, ?, 1, 0, 10)',
            [$user, $lp, self::COURSE, $third]
        );
        $ids['otherSessionLpView'] = (int) $this->conn->lastInsertId();
        $ids['otherCourseAgenda'] = $this->agenda($user, self::COURSE_TWO, $from, 'c6_course2_agenda')['link'];
        $ids['otherSessionAgenda'] = $this->agenda($user, self::COURSE, $third, 'c6_third_agenda')['link'];
        $ids['otherCourseWork'] = $this->work($user, self::COURSE_TWO, $from, 'c6_course2_work')['link'];
        $ids['otherSessionWork'] = $this->work($user, self::COURSE, $third, 'c6_third_work')['link'];
        $ids['otherCourseDropbox'] = $this->dropboxFile(self::COURSE_TWO, $user, $from);
        $ids['otherSessionDropbox'] = $this->dropboxFile(self::COURSE, $user, $third);

        return $ids;
    }

    /**
     * Whole rows of every table of the fixtures, keyed by table then primary key.
     *
     * @param array<string,int> $mine
     * @param array<string,int> $noise
     *
     * @return array{mine: array<string, array>, noise: array<string, array>}
     */
    private function snapshot(array $mine, array $noise): array
    {
        $fetch = function (string $table, string $pk, array $ids): array {
            $ids = array_values(array_unique(array_filter($ids)));
            if (empty($ids)) {
                return [];
            }
            $rows = [];
            $sql = "SELECT * FROM $table WHERE $pk IN (".implode(',', array_map('intval', $ids)).") ORDER BY $pk";
            foreach ($this->conn->fetchAllAssociative($sql) as $row) {
                // Drivers return ints or strings depending on the column: compare as strings (NULL stays NULL).
                $rows[(int) $row[$pk]] = array_map(fn ($v) => null === $v ? null : (string) $v, $row);
            }

            return $rows;
        };
        $build = function (array $ids) use ($fetch): array {
            $pick = fn (array $keys): array => array_map(fn (string $k): int => (int) ($ids[$k] ?? 0), $keys);
            $out = [];
            $out['track_e_exercises'] = $fetch('track_e_exercises', 'exe_id', $pick(['exercise', 'otherCourseExercise', 'otherSessionExercise']));
            $out['track_e_attempt_qualify'] = $fetch('track_e_attempt_qualify', 'id', $pick(['qualify']));
            $out['track_e_course_access'] = $fetch('track_e_course_access', 'course_access_id', $pick(['access', 'otherCourseAccess', 'otherSessionAccess']));
            $out['track_e_lastaccess'] = $fetch('track_e_lastaccess', 'access_id', $pick(['last', 'otherCourseLast']));
            $out['c_lp_view'] = $fetch('c_lp_view', 'iid', $pick(['lpView', 'otherCourseLpView', 'otherSessionLpView']));
            $out['resource_link_agenda'] = $fetch('resource_link', 'id', $pick(['agendaLink', 'otherCourseAgenda', 'otherSessionAgenda']));
            $out['resource_link_work'] = $fetch('resource_link', 'id', $pick(['workLink', 'otherCourseWork', 'otherSessionWork']));
            $out['c_dropbox_file'] = $fetch('c_dropbox_file', 'iid', $pick(['dropboxFile', 'otherCourseDropbox', 'otherSessionDropbox']));
            $out['c_dropbox_post'] = $fetch('c_dropbox_post', 'iid', $pick(['dropboxPost']));

            return array_filter($out);
        };

        return ['mine' => $build($mine), 'noise' => $build($noise)];
    }

    private function user(string $name): int
    {
        $name .= '_'.(++$this->seq).'_'.substr(md5((string) microtime(true)), 0, 6);
        $this->conn->executeStatement(
            "INSERT INTO user (username, username_canonical, locale, password, timezone, email, email_canonical,
                locked, expired, credentials_expired, salt, roles, status, active, uuid, created_at, updated_at,
                firstname, lastname)
             VALUES (?, ?, 'en_US', 'x', 'UTC', ?, ?, 0, 0, 0, 'x', '[]', 5, 1, ?,
                '2001-01-01 00:00:00', '2001-01-01 00:00:00', ?, 'c6')",
            [$name, $name, $name.'@example.com', $name.'@example.com', UuidV4::v4()->toBinary(), $name]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function session(string $title): int
    {
        $this->conn->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status) VALUES (?, 0, 0, 0, 1, 0)',
            [$title.'_'.(++$this->seq)]
        );
        $id = (int) $this->conn->lastInsertId();
        $this->conn->executeStatement(
            'INSERT INTO access_url_rel_session (access_url_id, session_id) VALUES (?, ?)',
            [api_get_current_access_url_id(), $id]
        );

        return $id;
    }

    private function node(int $resourceTypeId, string $title, int $creatorId): int
    {
        $this->conn->executeStatement(
            "INSERT INTO resource_node (resource_type_id, creator_id, title, slug, level, path, created_at, updated_at, public, uuid)
             VALUES (?, ?, ?, ?, 0, ?, '2001-01-01 00:00:00', '2001-01-01 00:00:00', 0, ?)",
            [$resourceTypeId, $creatorId, $title, 'c6-'.(++$this->seq), 'c6-'.$this->seq.'/', UuidV4::v4()->toBinary()]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function link(int $nodeId, int $courseId, ?int $sessionId): int
    {
        $this->conn->executeStatement(
            "INSERT INTO resource_link (resource_node_id, c_id, session_id, group_id, visibility, display_order,
                resource_type_group, created_at, updated_at)
             VALUES (?, ?, ?, NULL, 2, 0, 0, '2001-01-01 00:00:00', '2001-01-01 00:00:00')",
            [$nodeId, $courseId, $sessionId]
        );

        return (int) $this->conn->lastInsertId();
    }

    /**
     * @return array{id: int, link: int}
     */
    private function agenda(int $creator, int $courseId, ?int $sessionId, string $title): array
    {
        $node = $this->node(1, $title, $creator);
        $this->conn->executeStatement(
            'INSERT INTO c_calendar_event (title, all_day, collective, subscription_visibility, max_attendees, resource_node_id)
             VALUES (?, 0, 0, 0, 0, ?)',
            [$title, $node]
        );

        return ['id' => (int) $this->conn->lastInsertId(), 'link' => $this->link($node, $courseId, $sessionId)];
    }

    /**
     * @return array{id: int, link: int}
     */
    private function work(int $userId, int $courseId, ?int $sessionId, string $title, ?int $nodeCreator = null): array
    {
        $node = $this->node(6, $title, $nodeCreator ?? $this->creator);
        $this->conn->executeStatement(
            "INSERT INTO c_student_publication (parent_id, user_id, title, active, post_group_id, filetype, has_properties,
                qualification, qualificator_id, weight, allow_text_assignment, contains_file, document_id, resource_node_id)
             VALUES (NULL, ?, ?, 1, 0, 'file', 0, 0, 0, 0, 0, 0, 0, ?)",
            [$userId, $title, $node]
        );

        return ['id' => (int) $this->conn->lastInsertId(), 'link' => $this->link($node, $courseId, $sessionId)];
    }

    private function lpView(int $user, int $lp, int $courseId, ?int $sessionId, int $progress): int
    {
        $this->conn->executeStatement(
            'INSERT INTO c_lp_view (user_id, lp_id, c_id, session_id, view_count, last_item, progress) VALUES (?, ?, ?, ?, 1, 0, ?)',
            [$user, $lp, $courseId, $sessionId, $progress]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function lp(): int
    {
        $this->conn->executeStatement(
            "INSERT INTO c_lp (lp_type, title, path, force_commit, content_maker, content_license, js_lib, debug, theme,
                author, prerequisite, hide_toc_frame, seriousgame_mode, autolaunch, max_attempts, subscribe_users,
                created_on, modified_on)
             VALUES (1, 'c6_lp', '', 0, '', '', '', 0, '', '', 0, 0, 0, 0, 0, 0,
                '2001-01-01 00:00:00', '2001-01-01 00:00:00')"
        );

        return (int) $this->conn->lastInsertId();
    }

    private function exercise(
        int $userId,
        int $courseId,
        ?int $sessionId,
        int $quizId,
        string $date,
        float $score,
        float $maxScore,
        int $lpId = 0,
        int $lpItemId = 0
    ): int {
        $this->conn->executeStatement(
            "INSERT INTO track_e_exercises (exe_user_id, c_id, session_id, exe_exo_id, exe_date, score, max_score, user_ip,
                status, data_tracking, start_date, steps_counter, orig_lp_id, orig_lp_item_id, exe_duration,
                orig_lp_item_view_id, questions_to_check)
             VALUES (?, ?, ?, ?, ?, ?, ?, '127.0.0.1', '', '', ?, 0, ?, ?, 12, 0, '')",
            [$userId, $courseId, $sessionId, $quizId, $date, $score, $maxScore, $date, $lpId, $lpItemId]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function dropboxFile(int $courseId, int $uploader, int $sessionId): int
    {
        $this->conn->executeStatement(
            "INSERT INTO c_dropbox_file (c_id, uploader_id, filename, title, upload_date, last_upload_date, session_id)
             VALUES (?, ?, ?, 'c6', '2001-01-01 00:00:00', '2001-01-01 00:00:00', ?)",
            [$courseId, $uploader, 'c6_'.(++$this->seq).'.txt', $sessionId]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function dropboxPost(int $courseId, int $fileId, int $sessionId, int $dest): int
    {
        $this->conn->executeStatement(
            "INSERT INTO c_dropbox_post (feedback_date, cat_id, session_id, c_id, file_id, dest_user_id)
             VALUES ('2001-01-01 00:00:00', 0, ?, ?, ?, ?)",
            [$sessionId, $courseId, $fileId, $dest]
        );

        return (int) $this->conn->lastInsertId();
    }
}
