<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingContext;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingSectionQueryService;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Characterization of GlobalReportingSectionQueryService::getSection(): who may open which /reporting section,
 * which rows each list contains (scoped users, tracked sessions), filters, sorting and paging. Users and sessions
 * are tagged "rp_"; the scope itself is pinned in GlobalReportingScopeTest.
 */
final class GlobalReportingSectionTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    private const TEACHER = 1;
    private const HR = 4;
    private const STUDENT = 5;
    private const BOSS = 17;

    private GlobalReportingSectionQueryService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        // The learner detail calls legacy helpers (Attendance, ...) that read the static legacy container.
        $httpSession = new HttpSession(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($httpSession);
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        Container::setSession($httpSession);
        Container::setRequest($request);
        \Database::setManager($this->getEntityManager());
        \Database::setConnection($this->getEntityManager()->getConnection());
        $this->service = self::getContainer()->get(GlobalReportingSectionQueryService::class);
    }

    /**
     * @param array<string, bool> $flags
     */
    private function ctx(int $userId, array $flags = [], int $urlId = 1): GlobalReportingContext
    {
        $this->fresh();
        $f = $flags + [
            'admin' => false, 'hr' => false, 'sessionAdminOnly' => false, 'canViewGlobal' => true, 'boss' => false,
            'hrAll' => false, 'blockMyProgress' => false, 'email' => false,
        ];

        return new GlobalReportingContext(
            $this->entityUser($userId),
            $urlId,
            $f['admin'],
            $f['hr'],
            $f['sessionAdminOnly'],
            $f['canViewGlobal'],
            $f['boss'],
            $f['hrAll'],
            false,
            $f['email'],
            $f['blockMyProgress'],
            false,
            false,
            false,
            false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function section(GlobalReportingContext $ctx, string $name, array $filters = []): array
    {
        return $this->service->getSection($ctx, $name, $filters);
    }

    /**
     * A teacher of course 1 whose learners show up in his scope.
     *
     * @return array{0: int, 1: GlobalReportingContext}
     */
    private function teacher(string $tag): array
    {
        $me = $this->rawUser($tag, self::TEACHER);
        $this->courseUser($me, 1, 1);

        return [$me, $this->ctx($me)];
    }

    private function learner(string $suffix, array $extra = []): int
    {
        $id = $this->rawUser($suffix, self::STUDENT, $extra['active'] ?? 1);
        $this->courseUser($id, 1, 5);
        if (isset($extra['lastname'])) {
            $this->conn()->executeStatement('UPDATE user SET lastname = ? WHERE id = ?', [$extra['lastname'], $id]);
        }
        if (isset($extra['lastLogin'])) {
            $this->conn()->executeStatement('UPDATE user SET last_login = ? WHERE id = ?', [$extra['lastLogin'], $id]);
        }

        return $id;
    }

    // -------------------------------------------------------------- access

    public function testUnsupportedSectionIsABadRequest(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->section($this->ctx(1, ['admin' => true]), 'drop-table');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function adminOnlySections(): array
    {
        $sections = [
            'admin-index', 'admin-coaches', 'admin-users', 'admin-sessions', 'admin-courses', 'expired-certificates',
            'learning-results', 'session-results', 'access-overview', 'exercise-categories', 'surveys',
            'student-bosses', 'tutor-planning', 'question-stats', 'question-stats-detail', 'organization',
            'learning-path-authors', 'learning-path-item-authors',
        ];

        return array_combine($sections, array_map(static fn (string $s): array => [$s], $sections));
    }

    /**
     * @dataProvider adminOnlySections
     */
    public function testAdministrationSectionsAreDeniedToEveryoneWhoIsNotAnAdministrator(string $section): void
    {
        $me = $this->rawUser('acc_'.$section, self::TEACHER);
        foreach ([[], ['hr' => true, 'hrAll' => true], ['boss' => true], ['sessionAdminOnly' => true]] as $flags) {
            try {
                $this->section($this->ctx($me, $flags), $section);
                $this->fail('section '.$section.' served to a non administrator');
            } catch (AccessDeniedHttpException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testListSectionsRequireTheGlobalReportsPermission(): void
    {
        $me = $this->rawUser('acc1', self::TEACHER);
        foreach (['teachers', 'users', 'courses', 'sessions', 'exams', 'current-courses', 'works-in-session'] as $section) {
            try {
                $this->section($this->ctx($me, ['canViewGlobal' => false]), $section);
                $this->fail($section.' served without the global reports permission');
            } catch (AccessDeniedHttpException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testLearnerSectionsAreOpenToStudentBossesWithoutGlobalReports(): void
    {
        $boss = $this->rawUser('acc2', self::BOSS);
        $result = $this->section($this->ctx($boss, ['canViewGlobal' => false, 'boss' => true]), 'learners');
        $this->assertSame(0, $result['total']);
        $this->assertSame('Learners', $result['title']);
        $this->expectException(AccessDeniedHttpException::class);
        $this->section($this->ctx($boss, ['canViewGlobal' => false, 'boss' => true]), 'teachers');
    }

    public function testLearnersSectionIsDeniedToAUserWithNeitherPermissionNorBossRole(): void
    {
        $me = $this->rawUser('acc3', self::TEACHER);
        $this->expectException(AccessDeniedHttpException::class);
        $this->section($this->ctx($me, ['canViewGlobal' => false]), 'learners');
    }

    public function testMyProgressIsBlockedByTheCorrespondingSetting(): void
    {
        $me = $this->rawUser('acc4', self::STUDENT);
        $this->expectException(AccessDeniedHttpException::class);
        $this->section($this->ctx($me, ['blockMyProgress' => true]), 'my-progress');
    }

    public function testCertificatesAreRestrictedToAdministratorsAndStudentBosses(): void
    {
        $me = $this->rawUser('acc5', self::TEACHER);
        $this->expectException(AccessDeniedHttpException::class);
        // canViewGlobalReports passes the generic gate, the section's own check refuses the teacher.
        $this->section($this->ctx($me), 'certificates');
    }

    public function testCompanySectionsAreRestrictedToAdministratorsHrManagersAndStudentBosses(): void
    {
        $me = $this->rawUser('acc6', self::TEACHER);
        $this->expectException(AccessDeniedHttpException::class);
        $this->section($this->ctx($me, ['canViewGlobal' => false]), 'company');
    }

    // ------------------------------------------------------------ learners

    public function testLearnersListContainsOnlyTheScopedLearnersWithTheirCounters(): void
    {
        [, $ctx] = $this->teacher('sl1');
        $mine = $this->learner('sl1a', ['lastname' => 'Zed']);
        $this->courseUser($mine, 2, 5);
        $s = $this->rawSession('sl1');
        $this->sessionCourseUser($s, 1, $mine);
        $this->conn()->executeStatement(
            "INSERT INTO track_e_login (login_user_id, login_date, user_ip, logout_date) VALUES
             (?, '2001-01-01 10:00:00', '', '2001-01-01 10:01:30'), (?, '2001-01-02 10:00:00', '', NULL)",
            [$mine, $mine]
        );
        $stranger = $this->rawUser('sl1x', self::STUDENT);
        $this->courseUser($stranger, 2, 5);

        $result = $this->section($ctx, 'learners');
        $this->assertSame(1, $result['total']);
        $row = $result['items'][0];
        $this->assertSame($mine, $row['id']);
        $this->assertSame('Learner', $row['status']);
        $this->assertSame('Active', $row['active']);
        $this->assertSame(2, $row['courseCount']);
        $this->assertSame(1, $row['sessionCount']);
        $this->assertSame(90, $row['timeSeconds'], 'an open visit (no logout) adds nothing');
        $this->assertArrayNotHasKey('email', $row, 'e-mail addresses are hidden unless the context shows them');
        $this->assertSame(1, $result['summary'][0]['value']);
        $this->assertSame(1, $result['summary'][1]['value']);
    }

    public function testLearnersListShowsEmailsOnlyWhenAllowed(): void
    {
        [$me] = $this->teacher('sl2');
        $this->learner('sl2a');
        $result = $this->section($this->ctx($me, ['email' => true]), 'learners');
        $this->assertStringEndsWith('@example.com', $result['items'][0]['email']);
    }

    public function testLearnersListFiltersByKeywordActiveFlagAndInactivity(): void
    {
        [, $ctx] = $this->teacher('sl3');
        $old = $this->learner('sl3a', ['lastLogin' => '2001-01-01 00:00:00']);
        $recent = $this->learner('sl3b', ['lastLogin' => '2099-01-01 00:00:00']);
        $never = $this->learner('sl3c');
        $inactive = $this->learner('sl3d', ['active' => 0]);

        $ids = static fn (array $r): array => array_column($r['items'], 'id');
        $this->assertSame([$old], $ids($this->section($ctx, 'learners', ['keyword' => 'Firstsl3a'])));
        $this->assertSame([$inactive], $ids($this->section($ctx, 'learners', ['active' => '0'])));
        $active = $ids($this->section($ctx, 'learners', ['active' => '1']));
        sort($active);
        $this->assertSame([$old, $recent, $never], $active);
        $sleeping = $ids($this->section($ctx, 'learners', ['sleepingDays' => 30]));
        sort($sleeping);
        $this->assertSame([$old, $never, $inactive], $sleeping, 'never connected counts as sleeping');
        $this->assertSame('Inactive', $this->section($ctx, 'learners', ['active' => '0'])['items'][0]['active']);
    }

    public function testLearnersListSortsWithAnAllowlistAndPaginates(): void
    {
        [, $ctx] = $this->teacher('sl4');
        $ids = [];
        foreach (range(1, 12) as $i) {
            $ids[$i] = $this->learner('sl4_'.$i, ['lastname' => sprintf('L%02d', $i)]);
        }
        $asc = $this->section($ctx, 'learners', ['sort' => 'lastname', 'direction' => 'ASC', 'itemsPerPage' => 10]);
        $this->assertSame(12, $asc['total']);
        $this->assertCount(10, $asc['items'], 'ten rows per page');
        $this->assertSame($ids[1], $asc['items'][0]['id']);
        $page2 = $this->section($ctx, 'learners', ['sort' => 'lastname', 'direction' => 'ASC', 'page' => 2, 'itemsPerPage' => 10]);
        $this->assertSame([$ids[11], $ids[12]], array_column($page2['items'], 'id'));
        $desc = $this->section($ctx, 'learners', ['sort' => 'lastname', 'direction' => 'desc']);
        $this->assertSame($ids[12], $desc['items'][0]['id']);
        // An unknown sort key or direction falls back to lastname ASC, never reaches the SQL.
        $fallback = $this->section($ctx, 'learners', ['sort' => 'lastname; DROP TABLE user', 'direction' => 'sideways']);
        $this->assertSame($ids[1], $fallback['items'][0]['id']);
        $byCount = $this->section($ctx, 'learners', ['sort' => 'courseCount', 'direction' => 'DESC']);
        $this->assertSame(12, $byCount['total']);
    }

    public function testLearnersListIsEmptyWithoutAnyFollowedLearner(): void
    {
        $me = $this->rawUser('sl5', self::TEACHER);
        $result = $this->section($this->ctx($me), 'learners');
        $this->assertSame(0, $result['total']);
        $this->assertSame([], $result['items']);
    }

    public function testLearnersListNeverContainsSoftDeletedLearners(): void
    {
        [, $ctx] = $this->teacher('sl6');
        $alive = $this->learner('sl6a');
        $this->learner('sl6b', ['active' => -2]);
        $this->assertSame([$alive], array_column($this->section($ctx, 'learners')['items'], 'id'));
    }

    public function testTeachersAndUsersSectionsSelectByStatus(): void
    {
        [$me, $ctx] = $this->teacher('sl7');
        $teacher = $this->rawUser('sl7t', self::TEACHER);
        $this->courseUser($teacher, 1, 1);
        $student = $this->learner('sl7s');
        $boss = $this->rawUser('sl7b', self::BOSS);
        $this->courseUser($boss, 1, 5);

        $teachers = $this->section($ctx, 'teachers');
        $this->assertSame('Teachers', $teachers['title']);
        $this->assertContains($teacher, array_column($teachers['items'], 'id'));
        $this->assertNotContains($student, array_column($teachers['items'], 'id'));
        $this->assertNotContains($boss, array_column($teachers['items'], 'id'));
        $this->assertSame('Teacher', $teachers['items'][0]['status']);

        $bosses = $this->section($ctx, 'users', ['status' => 17]);
        $this->assertSame('Followed student bosses', $bosses['title']);
        $this->assertSame([$boss], array_column($bosses['items'], 'id'));
        $this->assertSame('Student boss', $bosses['items'][0]['status']);
        $this->assertFalse($bosses['meta']['supportsUserStatus']);

        $all = $this->section($ctx, 'users');
        $this->assertSame('Followed users', $all['title']);
        $this->assertContains($student, array_column($all['items'], 'id'));
        $this->assertContains($boss, array_column($all['items'], 'id'));
        $this->assertContains($me, array_column($all['items'], 'id'));
        $this->assertTrue($all['meta']['supportsUserStatus']);
        $this->assertSame('Followed students', $this->section($ctx, 'users', ['status' => 5])['title']);
    }

    // ------------------------------------------------------- learner detail

    public function testLearnerDetailRefusesLearnersOutsideTheScopeAndInvalidIds(): void
    {
        [, $ctx] = $this->teacher('ld1');
        $stranger = $this->rawUser('ld1x', self::STUDENT);
        foreach ([$stranger, 0, -3, 999999999] as $userId) {
            try {
                $this->section($ctx, 'learner-detail', ['userId' => $userId]);
                $this->fail('learner '.$userId.' served outside the scope');
            } catch (AccessDeniedHttpException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testLearnerDetailOfAScopedLearnerListsOnlyTheSubscriptionsTheTeacherFollows(): void
    {
        [, $ctx] = $this->teacher('ld2');
        $mine = $this->learner('ld2a');
        $this->courseUser($mine, 2, 5);
        $detail = $this->section($ctx, 'learner-detail', ['userId' => $mine]);
        $this->assertSame($mine, $detail['meta']['user']['id']);
        $this->assertSame('Learner', $detail['meta']['user']['status']);
        $this->assertTrue($detail['meta']['user']['active']);
        $this->assertArrayNotHasKey('email', $detail['meta']['user']);
        $titles = array_column($detail['items'], 'title');
        $this->assertSame([$this->conn()->fetchOne('SELECT title FROM course WHERE id = 1')], $titles);

        // An administrator sees every subscription of the learner.
        $admin = $this->section($this->ctx(1, ['admin' => true]), 'learner-detail', ['userId' => $mine]);
        $this->assertCount(2, $admin['items']);
    }

    public function testLearnerDetailOfASoftDeletedLearnerIsDenied(): void
    {
        [, $ctx] = $this->teacher('ld3');
        $gone = $this->learner('ld3a', ['active' => -2]);
        $this->expectException(AccessDeniedHttpException::class);
        $this->section($ctx, 'learner-detail', ['userId' => $gone]);
    }

    // ------------------------------------------------------------- sessions

    public function testSessionsListHoldsOnlySessionsTheUserCoachesOnThisUrl(): void
    {
        $me = $this->rawUser('se1', self::TEACHER);
        $general = $this->rawSession('se1a');
        $this->sessionUser($general, $me, 3);
        $this->sessionCourse($general, 1);
        $this->sessionCourse($general, 2);
        $course = $this->rawSession('se1b');
        $this->sessionCourseUser($course, 1, $me, 2);
        $this->sessionCourse($course, 1);
        $this->sessionCourse($course, 2);
        $learnerOnly = $this->rawSession('se1c');
        $this->sessionUser($learnerOnly, $me, 0);
        $foreignUrl = $this->rawSession('se1d', null);
        $this->sessionUser($foreignUrl, $me, 3);

        $result = $this->section($this->ctx($me), 'sessions');
        $this->assertSame(2, $result['total']);
        $byId = array_column($result['items'], null, 'id');
        $this->assertSame([$general, $course], array_keys($byId));
        $this->assertSame(2, $byId[$general]['courses'], 'a general coach sees every course of the session');
        $this->assertSame(1, $byId[$course]['courses'], 'a course coach sees only the courses he coaches');
        $this->assertSame('Your sessions', $result['title']);
        $this->assertSame(
            '/main/my_space/session.php?action=export_to_pdf&type=achievement&session_to_export='.$general.'&all_students=1',
            $byId[$general]['achievementPdfUrl']
        );
    }

    /**
     * The "Number of learners by session" column follows the legacy tracking grid: session-level learners of this
     * URL (or of no URL), never soft-deleted accounts.
     */
    public function testSessionsListLearnerCountFollowsTheLegacyTrackingGrid(): void
    {
        $me = $this->rawUser('se2', self::TEACHER);
        $s = $this->rawSession('se2');
        $this->sessionUser($s, $me, 3);
        $here = $this->rawUser('se2a');
        $noUrl = $this->rawUser('se2b', self::STUDENT, 1, null);
        $deleted = $this->rawUser('se2c', self::STUDENT, -2);
        $elsewhere = $this->rawUser('se2d', self::STUDENT, 1, null);
        $other = $this->secondUrl();
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (?, ?)', [$other, $elsewhere]);
        foreach ([$here, $noUrl, $deleted, $elsewhere] as $uid) {
            $this->sessionUser($s, $uid, 0);
        }
        $result = $this->section($this->ctx($me), 'sessions');
        $this->assertSame(2, $result['items'][0]['learners']);
    }

    public function testHrManagerSeesTheSessionsHeIsAttachedTo(): void
    {
        $hr = $this->rawUser('se3', self::HR);
        $s = $this->rawSession('se3');
        $this->sessionUser($s, $hr, 1);
        $coached = $this->rawSession('se3b');
        $this->sessionUser($coached, $hr, 3);
        $ids = array_column($this->section($this->ctx($hr, ['hr' => true]), 'sessions')['items'], 'id');
        $this->assertSame([$s], $ids);
    }

    public function testSessionAdministratorSeesTheSessionsHeAdministers(): void
    {
        $adm = $this->rawUser('se4', self::TEACHER);
        $s = $this->rawSession('se4');
        $this->sessionUser($s, $adm, 4);
        $other = $this->rawSession('se4b');
        $this->sessionUser($other, $adm, 3);
        $ids = array_column($this->section($this->ctx($adm, ['sessionAdminOnly' => true]), 'sessions')['items'], 'id');
        $this->assertSame([$s], $ids);
    }

    public function testSessionsListFiltersByKeywordAndSortsByTitle(): void
    {
        $me = $this->rawUser('se5', self::TEACHER);
        $a = $this->rawSession('se5_b');
        $b = $this->rawSession('se5_a');
        $this->sessionUser($a, $me, 3);
        $this->sessionUser($b, $me, 3);
        $result = $this->section($this->ctx($me), 'sessions');
        $this->assertSame([$b, $a], array_column($result['items'], 'id'), 'ordered by title');
        $title = $this->conn()->fetchOne('SELECT title FROM session WHERE id = ?', [$a]);
        $this->assertSame([$a], array_column($this->section($this->ctx($me), 'sessions', ['keyword' => $title])['items'], 'id'));
        $this->assertSame(0, $this->section($this->ctx($me), 'sessions', ['keyword' => 'zzz-no-such'])['total']);
    }

    public function testAdminSessionsListsEverySessionOfTheUrlWithCounters(): void
    {
        $s = $this->rawSession('as1', 1, '2001-01-01 00:00:00', '2001-12-31 00:00:00');
        $none = $this->rawSession('as2', null);
        $u = $this->rawUser('as1a');
        $this->sessionCourse($s, 1);
        $this->sessionCourseUser($s, 1, $u, 0);
        $coach = $this->rawUser('as1b', self::TEACHER);
        $this->sessionUser($s, $coach, 3);
        $result = $this->section($this->ctx(1, ['admin' => true]), 'admin-sessions', ['keyword' => 'rp_as1_']);
        $this->assertSame(1, $result['total']);
        $row = $result['items'][0];
        $this->assertSame(1, $row['courses']);
        $this->assertSame(1, $row['learners']);
        $this->assertSame(1, $row['coaches']);
        $this->assertSame('2001-01-01 00:00:00 - 2001-12-31 00:00:00', $row['date']);
        $this->assertSame(0.0, $row['progress']);
        $this->assertSame(0, $this->section($this->ctx(1, ['admin' => true]), 'admin-sessions', ['keyword' => $this->conn()->fetchOne('SELECT title FROM session WHERE id = ?', [$none])])['total']);
    }

    public function testAdminSessionsDateRangeKeepsSessionsOverlappingIt(): void
    {
        $inside = $this->rawSession('as3', 1, '2001-02-01 00:00:00', '2001-02-28 00:00:00');
        $before = $this->rawSession('as4', 1, '2000-01-01 00:00:00', '2000-01-31 00:00:00');
        $open = $this->rawSession('as5', 1, null, null);
        $ctx = $this->ctx(1, ['admin' => true]);
        $result = $this->section($ctx, 'admin-sessions', ['keyword' => 'rp_as', 'startDate' => '2001-01-01', 'endDate' => '2001-03-01']);
        $ids = array_column($result['items'], 'id');
        sort($ids);
        $this->assertSame([$inside, $open], $ids);
        $this->assertNotContains($before, $ids);
        $this->assertSame(2, $result['summary'][0]['value']);
    }
}
