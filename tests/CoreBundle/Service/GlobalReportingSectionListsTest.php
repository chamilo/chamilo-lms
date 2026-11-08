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
 * Second pass over GlobalReportingSectionQueryService::getSection(): the admin and tracking lists that
 * GlobalReportingSectionTest does not reach (certificates, exams, company, learning results, tracked courses,
 * tools, ...), including the student-boss and HR scopes. Fixtures are "rp_" rows (RawReportingFixturesTrait) rolled
 * back with the transaction, dated at fixed timestamps.
 */
final class GlobalReportingSectionListsTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    private const TEACHER = 1;
    private const STUDENT = 5;
    private const BOSS = 17;
    private const HR = 4;
    private const REL_BOSS = 8;

    private GlobalReportingSectionQueryService $service;

    protected function setUp(): void
    {
        self::bootKernel();
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

    private function admin(): GlobalReportingContext
    {
        return $this->ctx(1, ['admin' => true]);
    }

    // ------------------------------------------------------ admin sessions

    /**
     * The learners column of the admin sessions overview counts the learners subscribed to the session's courses
     * who are live accounts of the current URL (or of no URL, as in the tracking list): never soft-deleted users
     * and never learners that only exist on another portal.
     */
    public function testAdminSessionsLearnerCountIgnoresSoftDeletedUsersAndLearnersOfOtherUrls(): void
    {
        $other = $this->secondUrl();
        $s = $this->rawSession('r2as1');
        $this->sessionCourse($s, 1);
        $live = $this->rawUser('r2as1a');
        $deleted = $this->rawUser('r2as1b', 5, -2);
        $foreign = $this->rawUser('r2as1c', 5, 1, $other);
        $noUrl = $this->rawUser('r2as1d', 5, 1, null);
        foreach ([$live, $deleted, $foreign, $noUrl] as $uid) {
            $this->sessionCourseUser($s, 1, $uid, 0);
        }
        $result = $this->section($this->admin(), 'admin-sessions', ['keyword' => 'rp_r2as1_']);
        $this->assertSame(1, $result['total']);
        $this->assertSame(2, $result['items'][0]['learners']);
        $this->assertSame(2, $result['summary'][1]['value']);
    }

    // ------------------------------------------------------- fixtures

    private function category(int $courseId, ?int $sessionId = null, ?int $parentId = null): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO gradebook_category (c_id, parent_id, session_id, title, weight, visible, locked, generate_certificates, is_requirement, calculation_mode)
             VALUES (?, ?, ?, 'rp_r2cat', 1, 1, 0, 1, 0, 'weighted_average')",
            [$courseId, $parentId, $sessionId]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function certificate(int $categoryId, int $userId, string $createdAt, ?string $expiry = null, ?string $path = null): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO gradebook_certificate (cat_id, user_id, score_certificate, created_at, path_certificate, publish, expiry_date)
             VALUES (?, ?, 80, ?, ?, 0, ?)',
            [$categoryId, $userId, $createdAt, $path, $expiry]
        );

        return (int) $this->conn()->lastInsertId();
    }

    // ------------------------------------------------------- certificates

    public function testCertificatesAreRestrictedToAdministratorsAndStudentBosses(): void
    {
        foreach ([['hr' => true], [], ['sessionAdminOnly' => true]] as $flags) {
            $me = $this->rawUser('r2c0', self::HR);
            try {
                $this->section($this->ctx($me, $flags), 'certificates');
                $this->fail('certificates must be denied');
            } catch (AccessDeniedHttpException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testCertificatesListNewestFirstWithTheirPublicUrlAndFilterByMonthAndYear(): void
    {
        $cat = $this->category(1);
        $a = $this->rawUser('r2c1');
        $b = $this->rawUser('r2c2');
        $first = $this->certificate($cat, $a, '2001-03-15 10:00:00', null, '/certs/abc123.html');
        $second = $this->certificate($cat, $b, '2001-05-01 08:00:00');
        $other = $this->certificate($cat, $a, '2002-05-01 08:00:00');

        $result = $this->section($this->admin(), 'certificates', ['year' => '2001']);
        $this->assertSame([$second, $first], array_column($result['items'], 'id'));
        $this->assertSame(2, $result['total']);
        $row = $result['items'][1];
        $this->assertSame('Firstr2c1 Lastr2c1', $row['fullName']);
        $this->assertSame('/certificates/abc123.html', $row['certificateUrl']);
        $this->assertSame('', $result['items'][0]['certificateUrl']);
        $this->assertSame('', $row['session']);
        $this->assertSame((string) $this->conn()->fetchOne('SELECT title FROM course WHERE id = 1'), $row['course']);
        $this->assertContains(['label' => '2001', 'value' => '2001'], $result['meta']['yearOptions']);
        $this->assertSame(12, \count($result['meta']['monthOptions']));

        $may = $this->section($this->admin(), 'certificates', ['year' => '2001', 'month' => 5]);
        $this->assertSame([$second], array_column($may['items'], 'id'));
        $this->assertNotContains($other, array_column($result['items'], 'id'));
    }

    public function testCertificatesRejectABadYearAndOutOfScopeCourseOrSession(): void
    {
        $this->assertBadRequest(fn () => $this->section($this->admin(), 'certificates', ['year' => '01']));
        $this->assertBadRequest(fn () => $this->section($this->admin(), 'certificates', ['year' => '20x1']));
        foreach (['courseId' => 999999999, 'sessionId' => 999999999] as $key => $value) {
            try {
                $this->section($this->admin(), 'certificates', [$key => $value]);
                $this->fail($key);
            } catch (AccessDeniedHttpException) {
                $this->addToAssertionCount(1);
            }
        }
        // A learner filter only exists for student bosses.
        try {
            $this->section($this->admin(), 'certificates', ['userId' => 1]);
            $this->fail('userId');
        } catch (AccessDeniedHttpException) {
            $this->addToAssertionCount(1);
        }
    }

    private function assertBadRequest(callable $call): void
    {
        try {
            $call();
            $this->fail('expected a bad request');
        } catch (BadRequestHttpException) {
            $this->addToAssertionCount(1);
        }
    }

    public function testCertificatesOfASessionRequireTheCourseToBelongToIt(): void
    {
        $session = $this->rawSession('r2c3');
        $this->sessionCourse($session, 1);
        $u = $this->rawUser('r2c3a');
        $inSession = $this->certificate($this->category(1, $session), $u, '2001-04-01 10:00:00');
        $this->certificate($this->category(1), $u, '2001-04-02 10:00:00');

        $result = $this->section($this->admin(), 'certificates', ['sessionId' => $session, 'courseId' => 1]);
        $this->assertSame([$inSession], array_column($result['items'], 'id'));
        $this->assertSame('rp_r2c3_', substr($result['items'][0]['session'], 0, 8));
        $this->assertStringStartsWith('/main/gradebook/gradebook_display_certificate.php?action=export_all_certificates', (string) $result['meta']['exportAllUrl'] ?? '');

        $this->assertBadRequest(fn () => $this->section($this->admin(), 'certificates', ['sessionId' => $session, 'courseId' => 2]));
    }

    public function testCertificatesOfCoursesOrUsersOfAnotherUrlAreNotListed(): void
    {
        $other = $this->secondUrl();
        $mine = $this->rawUser('r2c4');
        $foreign = $this->rawUser('r2c5', 5, 1, $other);
        $cat = $this->category(1);
        $ok = $this->certificate($cat, $mine, '2001-06-01 10:00:00');
        $this->certificate($cat, $foreign, '2001-06-02 10:00:00');
        $this->conn()->executeStatement('UPDATE access_url_rel_course SET access_url_id = ? WHERE c_id = 2', [$other]);
        $this->certificate($this->category(2), $mine, '2001-06-03 10:00:00');

        $result = $this->section($this->admin(), 'certificates', ['year' => '2001']);
        $this->assertSame([$ok], array_column($result['items'], 'id'));
    }

    public function testExpiredCertificatesListsOnlyThoseExpiredBeforeTodayMostRecentExpiryFirst(): void
    {
        $cat = $this->category(1);
        $u = $this->rawUser('r2x1');
        $old = $this->certificate($cat, $u, '2001-01-01 10:00:00', '2001-02-01');
        $older = $this->certificate($cat, $u, '2001-01-02 10:00:00', '2000-12-31');
        $this->certificate($cat, $u, '2001-01-03 10:00:00', null);
        $this->certificate($cat, $u, '2001-01-04 10:00:00', '2999-01-01');

        $result = $this->section($this->admin(), 'expired-certificates', ['year' => '2001']);

        $this->assertSame('Expired certificates', $result['title']);
        $this->assertSame([$old, $older], array_column($result['items'], 'id'));
        $this->assertSame('2001-02-01', $result['items'][0]['expiryDate']);
        $this->assertSame(['expired-certificates'], [$result['meta']['activeAdminSection']]);
        $keyword = $this->section($this->admin(), 'expired-certificates', ['year' => '2001', 'keyword' => 'Lastr2x1']);
        $this->assertSame(2, $keyword['total']);
        $this->assertSame(0, $this->section($this->admin(), 'expired-certificates', ['year' => '2001', 'keyword' => 'no-such-learner'])['total']);
    }

    /**
     * PINNED QUIRK: the administrator's certificate lists take every user of the URL, soft-deleted ones included
     * (allAccessUrlUserIds() does not look at user.active).
     */
    public function testAdministratorCertificatesStillListSoftDeletedUsers(): void
    {
        $gone = $this->rawUser('r2c6', 5, -2);
        $id = $this->certificate($this->category(1), $gone, '2001-07-01 10:00:00');
        $this->assertSame([$id], array_column($this->section($this->admin(), 'certificates', ['year' => '2001'])['items'], 'id'));
    }

    public function testStudentBossSeesTheCertificatesOfHisStudentsOnly(): void
    {
        $boss = $this->rawUser('r2c7', self::BOSS);
        $mine = $this->rawUser('r2c8');
        $other = $this->rawUser('r2c9');
        $this->userRel($mine, $boss, self::REL_BOSS);
        $this->courseUser($mine, 1, 5);
        $this->courseUser($other, 1, 5);
        $cat = $this->category(1);
        $ok = $this->certificate($cat, $mine, '2001-08-01 10:00:00');
        $this->certificate($cat, $other, '2001-08-02 10:00:00');

        $ctx = $this->ctx($boss, ['boss' => true]);
        $result = $this->section($ctx, 'certificates', ['year' => '2001']);

        $this->assertSame([$ok], array_column($result['items'], 'id'));
        $this->assertTrue($result['meta']['supportsLearnerFilter']);
        $this->assertSame([$mine], array_column($result['meta']['learnerOptions'], 'id'));
        $this->assertSame([$ok], array_column($this->section($ctx, 'certificates', ['year' => '2001', 'userId' => $mine])['items'], 'id'));
        try {
            $this->section($ctx, 'certificates', ['userId' => $other]);
            $this->fail('a learner of somebody else');
        } catch (AccessDeniedHttpException) {
            $this->addToAssertionCount(1);
        }
    }

    // ------------------------------------------------------------ company

    private function lp(string $title = 'rp_r2lp'): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_lp (lp_type, title, path, force_commit, content_maker, content_license, js_lib, debug, theme, author,
                prerequisite, hide_toc_frame, seriousgame_mode, autolaunch, max_attempts, subscribe_users, created_on, modified_on)
             VALUES (1, ?, '', 0, '', '', '', 0, '', '', 0, 0, 0, 0, 0, 0, '2001-01-01 00:00:00', '2001-01-01 00:00:00')",
            [$title]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function lpView(int $userId, int $courseId, int $progress, int $sessionId = 0, ?int $lpId = null): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO c_lp_view (user_id, lp_id, c_id, session_id, view_count, last_item, progress) VALUES (?, ?, ?, ?, 1, 0, ?)',
            [$userId, $lpId ?? $this->lp(), $courseId, 0 === $sessionId ? null : $sessionId, $progress]
        );
    }

    private function courseVisit(int $userId, int $courseId, string $in, ?string $out): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_course_access (user_id, c_id, login_course_date, logout_course_date, counter, session_id, user_ip)
             VALUES (?, ?, ?, ?, 1, 0, '')",
            [$userId, $courseId, $in, $out]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function companyRowsOf(array $result, array $userIds): array
    {
        return array_values(array_filter($result['items'], static fn (array $r): bool => in_array($r['userId'], $userIds, true)));
    }

    public function testCompanyReportIsOpenToAdministratorsHrManagersAndStudentBossesOnly(): void
    {
        $teacher = $this->rawUser('r2y0', self::TEACHER);
        foreach (['company', 'company-summary'] as $section) {
            try {
                $this->section($this->ctx($teacher), $section);
                $this->fail($section);
            } catch (AccessDeniedHttpException) {
                $this->addToAssertionCount(1);
            }
            $hr = $this->rawUser('r2y1', self::HR);
            $this->assertSame([], $this->section($this->ctx($hr, ['hr' => true]), $section)['items'], 'an HR manager without followed learners sees nothing');
            $boss = $this->rawUser('r2y2', self::BOSS);
            $this->assertSame([], $this->section($this->ctx($boss, ['boss' => true]), $section)['items']);
        }
    }

    public function testCompanyReportGivesOneRowPerSubscriptionWithTimeProgressAndCertificate(): void
    {
        $a = $this->rawUser('r2y3');
        $b = $this->rawUser('r2y4');
        $this->courseUser($a, 1);
        $this->courseUser($a, 2);
        $this->courseUser($b, 1);
        $this->courseVisit($a, 1, '2001-01-01 10:00:00', '2001-01-01 10:10:00');
        $this->courseVisit($a, 1, '2001-01-02 10:00:00', '2001-01-02 10:00:30');
        $this->courseVisit($a, 1, '2001-01-03 10:00:00', null);
        $this->lpView($a, 1, 50);
        $this->lpView($a, 1, 100);
        $this->certificate($this->category(1), $a, '2001-01-05 10:00:00');

        $result = $this->section($this->admin(), 'company', ['itemsPerPage' => 100]);
        $rows = $this->companyRowsOf($result, [$a, $b]);

        $this->assertCount(3, $rows);
        $byId = array_column($rows, null, 'id');
        $this->assertSame(['1-'.$a, '1-'.$b, '2-'.$a], array_column($rows, 'id'), 'ids are course-user, ordered by course then name');
        $this->assertSame(630, $byId['1-'.$a]['timeSeconds'], '10m + 30s, the open visit counts for zero');
        $this->assertSame(75.0, $byId['1-'.$a]['progress']);
        $this->assertSame(1, $byId['1-'.$a]['completedLearningPaths']);
        $this->assertSame('Yes', $byId['1-'.$a]['certificate']);
        $this->assertSame('No', $byId['2-'.$a]['certificate']);
        $this->assertSame(0, $byId['1-'.$b]['timeSeconds']);
        $this->assertSame(0.0, $byId['1-'.$b]['progress']);
        $this->assertSame('Firstr2y3 Lastr2y3', $byId['1-'.$a]['user']);
        $this->assertArrayNotHasKey('email', $byId['1-'.$a], 'e-mail addresses are hidden by default');
        $this->assertSame('Corporate report', $result['title']);
        $this->assertSame($result['total'], $result['summary'][0]['value']);
    }

    public function testCompanyReportShowsEmailAddressesWhenThePlatformAllowsIt(): void
    {
        $a = $this->rawUser('r2y5');
        $this->courseUser($a, 1);
        $result = $this->section($this->ctx(1, ['admin' => true, 'email' => true]), 'company', ['itemsPerPage' => 100]);
        $row = $this->companyRowsOf($result, [$a])[0];
        $this->assertStringStartsWith('rp_r2y5_', $row['email']);
        $this->assertContains('email', array_column($result['columns'], 'key'));
    }

    /**
     * A learner subscribed to a course is one row of the report (its id is "course-user"), however many gradebook
     * categories the course has: a root category and a sub-category are the normal layout.
     */
    public function testCompanyReportDoesNotDuplicateASubscriptionWhenTheCourseHasSeveralGradebookCategories(): void
    {
        $a = $this->rawUser('r2y6');
        $this->courseUser($a, 1);
        $root = $this->category(1);
        $this->category(1, null, $root);
        $this->certificate($root, $a, '2001-01-05 10:00:00');

        $rows = $this->companyRowsOf($this->section($this->admin(), 'company', ['itemsPerPage' => 100]), [$a]);

        $this->assertCount(1, $rows);
        $this->assertSame('Yes', $rows[0]['certificate']);
    }

    public function testCompanyReportOnlyListsCoursesOfTheUrlAndPages(): void
    {
        $other = $this->secondUrl();
        $a = $this->rawUser('r2y7');
        $this->courseUser($a, 1);
        $this->courseUser($a, 2);
        $this->conn()->executeStatement('UPDATE access_url_rel_course SET access_url_id = ? WHERE c_id = 2', [$other]);
        $result = $this->section($this->admin(), 'company', ['itemsPerPage' => 100]);
        $this->assertSame(['1-'.$a], array_column($this->companyRowsOf($result, [$a]), 'id'));
        $paged = $this->section($this->admin(), 'company', ['itemsPerPage' => 10, 'page' => 2]);
        $this->assertCount(max(0, min(10, $paged['total'] - 10)), $paged['items']);
        $this->assertSame(2, $paged['page']);
    }

    public function testCompanyBossScopeIsLimitedToHisStudents(): void
    {
        $boss = $this->rawUser('r2y8', self::BOSS);
        $mine = $this->rawUser('r2y9');
        $other = $this->rawUser('r2y10');
        $this->userRel($mine, $boss, self::REL_BOSS);
        $this->courseUser($mine, 1);
        $this->courseUser($other, 1);
        $result = $this->section($this->ctx($boss, ['boss' => true]), 'company');
        $this->assertSame(['1-'.$mine], array_column($result['items'], 'id'));
        $this->assertSame(1, $result['summary'][1]['value']);
    }

    public function testCompanySummaryGroupsByOrganizationExtraFieldAndCountsCertificates(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO extra_field (item_type, value_type, variable, display_text, field_order, visible_to_self, visible_to_others, changeable, filter, created_at, auto_remove)
             VALUES (1, 1, 'company', 'Company', 1, 0, 0, 0, 0, '2020-01-01 00:00:00', 0)"
        );
        $fieldId = (int) $this->conn()->lastInsertId();
        $boss = $this->rawUser('r2s0', self::BOSS);
        $users = [];
        foreach (['a' => 'R2 Acme', 'b' => 'R2 Acme', 'c' => null] as $tag => $company) {
            $uid = $this->rawUser('r2s'.$tag);
            $users[$tag] = $uid;
            $this->userRel($uid, $boss, self::REL_BOSS);
            $this->courseUser($uid, 1);
            $this->courseVisit($uid, 1, '2001-01-01 10:00:00', '2001-01-01 11:00:00');
            if (null !== $company) {
                $this->conn()->executeStatement(
                    "INSERT INTO extra_field_values (field_id, field_value, item_id, created_at, updated_at) VALUES (?, ?, ?, '2020-01-01 00:00:00', '2020-01-01 00:00:00')",
                    [$fieldId, $company, $uid]
                );
            }
        }
        $this->certificate($this->category(1), $users['a'], '2001-01-05 10:00:00');

        $result = $this->section($this->ctx($boss, ['boss' => true]), 'company-summary');

        $this->assertSame('Corporate report, short version', $result['title']);
        $this->assertSame(2, $result['total']);
        $this->assertSame('R2 Acme', $result['items'][0]['company']);
        $this->assertSame(7200, $result['items'][0]['timeSeconds']);
        $this->assertSame(2, $result['items'][0]['users']);
        $this->assertSame(2, $result['items'][0]['subscriptions']);
        $this->assertSame(3600, $result['items'][0]['averageTimeSeconds']);
        $this->assertSame(1, $result['items'][0]['certificates']);
        $this->assertSame('Without organization', $result['items'][1]['company']);
        $this->assertSame(0, $result['items'][1]['certificates']);
        $this->assertSame(md5('R2 Acme'), $result['items'][0]['id']);
    }
}
