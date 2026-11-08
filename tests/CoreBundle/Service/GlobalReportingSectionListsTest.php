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

    // ----------------------------------------------- learning path results

    public function testLearningResultsAskForACourseBeforeListingAnything(): void
    {
        $u = $this->rawUser('r2l0');
        $this->lpView($u, 1, 50);
        $result = $this->section($this->admin(), 'learning-results');
        $this->assertSame([], $result['items']);
        $this->assertSame(0, $result['total']);
        $this->assertTrue($result['meta']['requiresCourse']);
        $this->assertSame('Learning paths exercises results list', $result['title']);
        $session = $this->section($this->admin(), 'session-results', ['courseId' => 1]);
        $this->assertSame([], $session['items']);
        $this->assertTrue($session['meta']['requiresSession']);
    }

    public function testLearningResultsSummariseTheLearningPathViewsOfACourse(): void
    {
        $a = $this->rawUser('r2l1');
        $b = $this->rawUser('r2l2');
        $lp1 = $this->lp();
        $lp2 = $this->lp();
        $this->lpView($a, 2, 100, 0, $lp1);
        $this->lpView($a, 2, 40, 0, $lp2);
        $this->lpView($b, 2, 61, 0, $lp1);

        $result = $this->section($this->admin(), 'learning-results', ['courseId' => 2]);

        $this->assertCount(1, $result['items']);
        $row = $result['items'][0];
        $this->assertSame(2, $row['id']);
        $this->assertSame(2, $row['learners']);
        $this->assertSame(2, $row['learningPaths']);
        $this->assertSame(67.0, $row['progress'], '(100 + 40 + 61) / 3');
        $this->assertSame(1, $row['completed']);
        $this->assertSame(1, $result['total']);
    }

    public function testLearningResultsCanBeNarrowedToASessionAndNeverShowACourseOfAnotherUrl(): void
    {
        $session = $this->rawSession('r2l3');
        $u = $this->rawUser('r2l3a');
        $this->lpView($u, 2, 30, 0);
        $this->lpView($u, 2, 90, $session);

        $inSession = $this->section($this->admin(), 'learning-results', ['courseId' => 2, 'sessionId' => $session]);
        $this->assertSame(90.0, $inSession['items'][0]['progress']);
        $this->assertSame(1, $inSession['items'][0]['learners']);

        $other = $this->secondUrl();
        $this->conn()->executeStatement('UPDATE access_url_rel_course SET access_url_id = ? WHERE c_id = 2', [$other]);
        $this->assertSame([], $this->section($this->admin(), 'learning-results', ['courseId' => 2])['items']);
    }

    public function testSessionResultsListOneRowPerLearnerAndCourseSortedByName(): void
    {
        $session = $this->rawSession('r2l4');
        $z = $this->rawUser('r2l4z');
        $a = $this->rawUser('r2l4a');
        $this->conn()->executeStatement('UPDATE user SET lastname = ?, last_login = ? WHERE id = ?', ['R2Zed', '2001-02-03 04:05:06', $z]);
        $this->conn()->executeStatement('UPDATE user SET lastname = ? WHERE id = ?', ['R2Alpha', $a]);
        $this->lpView($z, 1, 20, $session);
        $this->lpView($z, 1, 60, $session);
        $this->lpView($a, 1, 100, $session);
        $this->lpView($a, 2, 10, $session);
        $this->lpView($a, 2, 99, 0);

        $result = $this->section($this->admin(), 'session-results', ['sessionId' => $session]);

        $this->assertSame(3, $result['total']);
        $this->assertSame(['R2Alpha', 'R2Alpha', 'R2Zed'], array_map(static fn (array $r): string => substr($r['user'], strpos($r['user'], ' ') + 1), $result['items']));
        $this->assertSame([100.0, 10.0, 40.0], array_column($result['items'], 'averageScore'));
        $this->assertSame($a.'-'.md5((string) $result['items'][0]['course']), $result['items'][0]['id']);
        $this->assertSame('2001-02-03 04:05:06', $result['items'][2]['lastAccess']);
    }

    // -------------------------------------------------------------- exams

    private function quiz(int $courseId, string $title, int $sessionId = 0): int
    {
        // Borrow a resource node that no quiz or link uses yet (the installer leaves a few unused ones).
        $node = (int) $this->conn()->fetchOne(
            'SELECT id FROM resource_node WHERE id NOT IN (SELECT resource_node_id FROM resource_link WHERE resource_node_id IS NOT NULL)
               AND id NOT IN (SELECT resource_node_id FROM c_quiz WHERE resource_node_id IS NOT NULL) ORDER BY id LIMIT 1'
        );
        $this->assertGreaterThan(0, $node, 'no free resource node to attach a quiz to');
        $this->conn()->executeStatement(
            "INSERT INTO c_quiz (resource_node_id, title, type, random, random_answers, results_disabled, max_attempt, feedback_type, expired_time,
                propagate_neg, review_answers, random_by_category, display_category_name, hide_question_title, page_result_configuration)
             VALUES (?, ?, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '[]')",
            [$node, $title]
        );
        $quiz = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement(
            "INSERT INTO resource_link (resource_node_id, c_id, session_id, visibility, display_order, resource_type_group, created_at, updated_at)
             VALUES (?, ?, ?, 2, 0, 0, '2001-01-01 00:00:00', '2001-01-01 00:00:00')",
            [$node, $courseId, 0 === $sessionId ? null : $sessionId]
        );

        return $quiz;
    }

    private function attempt(int $userId, int $courseId, int $quizId, float $score, float $max, int $sessionId = 0): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_exercises (exe_user_id, c_id, session_id, exe_exo_id, exe_date, score, max_score, user_ip, status, data_tracking,
                start_date, steps_counter, orig_lp_id, orig_lp_item_id, exe_duration, orig_lp_item_view_id, questions_to_check)
             VALUES (?, ?, ?, ?, '2001-01-01 10:00:00', ?, ?, '', '', '', '2001-01-01 09:00:00', 0, 0, 0, 0, 0, '')",
            [$userId, $courseId, 0 === $sessionId ? null : $sessionId, $quizId, $score, $max]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function examRow(array $result, int $courseId, int $quizId, int $sessionId = 0): array
    {
        $found = array_values(array_filter($result['items'], static fn (array $r): bool => $r['id'] === $courseId.'-'.$quizId.'-'.$sessionId));
        $this->assertCount(1, $found);

        return $found[0];
    }

    public function testExamsCountTakersPassersAndLearnersWhoDidNotTakeTheTest(): void
    {
        $quiz = $this->quiz(1, 'rp_r2 exam');
        $students = [];
        foreach (['a', 'b', 'c'] as $tag) {
            $students[$tag] = $this->rawUser('r2q'.$tag);
            $this->courseUser($students[$tag], 1, 5);
        }
        $teacher = $this->rawUser('r2qt', self::TEACHER);
        $this->courseUser($teacher, 1, 1);
        $this->attempt($students['a'], 1, $quiz, 10, 10);
        $this->attempt($students['a'], 1, $quiz, 2, 10);
        $this->attempt($students['b'], 1, $quiz, 6, 10);
        $totalLearners = (int) $this->conn()->fetchOne('SELECT COUNT(DISTINCT user_id) FROM course_rel_user WHERE c_id = 1 AND status = 5');

        $result = $this->section($this->admin(), 'exams', ['score' => 70]);
        $row = $this->examRow($result, 1, $quiz);

        $this->assertSame('rp_r2 exam', $row['test']);
        $this->assertSame($totalLearners, $row['totalLearners'], 'teachers are not learners');
        $this->assertSame(2, $row['taken']);
        $this->assertSame($totalLearners - 2, $row['notTaken']);
        $this->assertSame(1, $row['passed'], 'a learner passes with his BEST attempt (100%), the 60% one does not');
        $this->assertSame(1, $row['failed']);
        $this->assertSame(70, $result['meta']['scoreThreshold']);
        $this->assertSame($row['id'], '1-'.$quiz.'-0');
        $lowBar = $this->examRow($this->section($this->admin(), 'exams', ['score' => 60]), 1, $quiz);
        $this->assertSame(2, $lowBar['passed'], 'the threshold is inclusive');
    }

    public function testExamsOfASessionAreCountedAgainstTheLearnersOfThatSession(): void
    {
        $session = $this->rawSession('r2q2');
        $this->sessionCourse($session, 1);
        $quiz = $this->quiz(1, 'rp_r2 base exam');
        $learner = $this->rawUser('r2q2a');
        $this->sessionCourseUser($session, 1, $learner, 0);
        $this->courseUser($learner, 1, 5);
        $this->attempt($learner, 1, $quiz, 5, 10, $session);
        $this->attempt($learner, 1, $quiz, 9, 10, 0);

        $result = $this->section($this->admin(), 'exams', ['score' => 50]);

        $inSession = $this->examRow($result, 1, $quiz, $session);
        $this->assertSame(1, $inSession['totalLearners']);
        $this->assertSame(1, $inSession['taken']);
        $this->assertSame(1, $inSession['passed']);
        $this->assertSame(0, $inSession['notTaken']);
        $this->assertStringStartsWith('rp_r2 base exam (rp_r2q2_', $inSession['test']);
        $this->assertSame(1, $this->examRow($result, 1, $quiz)['taken'], 'the base course context keeps its own attempts (clamped to its registered learners)');
    }

    public function testExamsListACoursePlaceholderWhenItHasNoTestAndOnlyCoursesOfTheScope(): void
    {
        $other = $this->secondUrl();
        $this->conn()->executeStatement('UPDATE access_url_rel_course SET access_url_id = ? WHERE c_id = 2', [$other]);
        $result = $this->section($this->admin(), 'exams');
        $courseIds = array_values(array_unique(array_column($result['items'], 'courseId')));
        $this->assertContains(1, $courseIds);
        $this->assertNotContains(2, $courseIds);
        $placeholder = array_values(array_filter($result['items'], static fn (array $r): bool => $r['empty']));
        foreach ($placeholder as $row) {
            $this->assertSame('There is no test for the moment', $row['test']);
            $this->assertNull($row['taken']);
        }
        $this->assertSame(70, $result['meta']['scoreThreshold'], 'the default pass mark');
        $this->assertSame(100, $this->section($this->admin(), 'exams', ['score' => 500])['meta']['scoreThreshold'], 'the pass mark is capped at 100');
    }

    // ------------------------------------------------------ tracked courses

    /**
     * An administrator looking at the courses taught by $teacher (the "userId" filter of /reporting/courses).
     *
     * @return array{0: int, 1: GlobalReportingContext}
     */
    private function courseTeacher(string $tag, int $courseId): array
    {
        $teacher = $this->rawUser($tag, self::TEACHER);
        $this->conn()->executeStatement('DELETE FROM course_rel_user WHERE c_id = ? AND status = 1', [$courseId]);
        $this->courseUser($teacher, $courseId, 1);

        return [$teacher, $this->admin()];
    }

    public function testTrackedCoursesOfATeacherListLearnersAndAverageTimeSpent(): void
    {
        [$teacher, $ctx] = $this->courseTeacher('r2t1', 2);
        $a = $this->rawUser('r2t1a');
        $b = $this->rawUser('r2t1b');
        $manager = $this->rawUser('r2t1c');
        $this->courseUser($a, 2, 5);
        $this->courseUser($b, 2, 5);
        $this->courseUser($manager, 2, 5, 1);
        $this->courseVisit($a, 2, '2001-01-01 10:00:00', '2001-01-01 10:10:00');
        $this->courseVisit($b, 2, '2001-01-02 10:00:00', '2001-01-02 10:05:00');
        $this->courseVisit($b, 2, '2001-01-03 10:00:00', null);

        $result = $this->section($ctx, 'courses', ['userId' => $teacher]);

        $this->assertSame('Your courses', $result['title']);
        $this->assertSame(1, $result['total']);
        $row = $result['items'][0];
        $this->assertSame(2, $row['id']);
        $this->assertSame(2, $row['learners'], 'the teacher and the manager relation are not learners');
        $this->assertSame(450, $row['averageTimeSeconds'], '(600 + 300) seconds / 2 learners');
        $this->assertNull($row['learningPathProgress']);
        $this->assertNull($row['forumPosts']);
        $this->assertNull($row['thematicUrl']);
        $this->assertNull($row['attendanceUrl']);
        $this->assertSame((string) $this->conn()->fetchOne('SELECT code FROM course WHERE id = 2'), $row['code']);
    }

    public function testTrackedCoursesFilterByKeywordAndStayWithinTheUrl(): void
    {
        [$teacher, $ctx] = $this->courseTeacher('r2t2', 1);
        $title = (string) $this->conn()->fetchOne('SELECT title FROM course WHERE id = 1');
        $this->assertSame(1, $this->section($ctx, 'courses', ['userId' => $teacher, 'keyword' => substr($title, 0, 3)])['total']);
        $this->assertSame(0, $this->section($ctx, 'courses', ['userId' => $teacher, 'keyword' => 'zz-no-such-course'])['total']);
        $other = $this->secondUrl();
        $this->conn()->executeStatement('UPDATE access_url_rel_course SET access_url_id = ? WHERE c_id = 1', [$other]);
        $this->assertSame([], $this->section($ctx, 'courses', ['userId' => $teacher])['items']);
    }

    public function testTrackedCoursesInASessionCountSessionLearnersAndOfferTheAttendanceLink(): void
    {
        $session = $this->rawSession('r2t3');
        $this->sessionCourse($session, 1);
        $learner = $this->rawUser('r2t3a');
        $this->sessionCourseUser($session, 1, $learner, 0);
        $this->conn()->executeStatement(
            "INSERT INTO track_e_course_access (user_id, c_id, login_course_date, logout_course_date, counter, session_id, user_ip)
             VALUES (?, 1, '2001-01-01 10:00:00', '2001-01-01 11:00:00', 1, ?, '')",
            [$learner, $session]
        );

        $result = $this->section($this->admin(), 'courses', ['sessionId' => $session]);

        $this->assertSame('Courses in this session', $result['title']);
        $this->assertSame([1], array_column($result['items'], 'id'));
        $this->assertSame(1, $result['items'][0]['learners']);
        $this->assertSame(3600, $result['items'][0]['averageTimeSeconds']);
        $this->assertSame($session, $result['items'][0]['sessionId']);
        $this->assertNull($result['items'][0]['attendanceUrl'], 'no attendance sheet in the session');
        // A session of another URL shows no course at all.
        $other = $this->secondUrl();
        $this->conn()->executeStatement('UPDATE access_url_rel_session SET access_url_id = ? WHERE session_id = ?', [$other, $session]);
        $this->assertSame([], $this->section($this->admin(), 'courses', ['sessionId' => $session])['items']);
    }

    /**
     * The course progress is the average of the latest view of every (learner, learning path) pair, like the admin
     * courses overview: two learners who both finished the only learning path show 100 %.
     */
    public function testTrackedCoursesLearningPathProgressAveragesTheLearners(): void
    {
        [$teacher, $ctx] = $this->courseTeacher('r2t4', 2);
        $lp = $this->lp();
        foreach (['a', 'b'] as $tag) {
            $learner = $this->rawUser('r2t4'.$tag);
            $this->courseUser($learner, 2, 5);
            $this->lpView($learner, 2, 100, 0, $lp);
        }

        $row = $this->section($ctx, 'courses', ['userId' => $teacher])['items'][0];

        $this->assertSame(2, $row['learners']);
        $this->assertSame(100.0, $row['learningPathProgress']);
    }

    public function testTrackedCoursesLearningPathProgressCountsTheLatestViewOfEachLearnerAndLearningPath(): void
    {
        [$teacher, $ctx] = $this->courseTeacher('r2t5', 2);
        $lp1 = $this->lp();
        $lp2 = $this->lp();
        $a = $this->rawUser('r2t5a');
        $b = $this->rawUser('r2t5b');
        $this->courseUser($a, 2, 5);
        $this->courseUser($b, 2, 5);
        $this->lpView($a, 2, 10, 0, $lp1);
        $this->conn()->executeStatement('UPDATE c_lp_view SET view_count = 1 WHERE user_id = ?', [$a]);
        $this->conn()->executeStatement('INSERT INTO c_lp_view (user_id, lp_id, c_id, session_id, view_count, last_item, progress) VALUES (?, ?, 2, NULL, 2, 0, 100)', [$a, $lp1]);
        $this->lpView($a, 2, 50, 0, $lp2);
        $this->lpView($b, 2, 30, 0, $lp1);

        $row = $this->section($ctx, 'courses', ['userId' => $teacher])['items'][0];

        // Learner a: lp1 latest view 100 (not the older 10), lp2 50; learner b: lp1 30. (100 + 50 + 30) / 3 views.
        $this->assertSame(60.0, $row['learningPathProgress']);
    }

    public function testTrackedCoursesAverageLearningPathScoreIsTheMeanOfTheLatestExerciseAttempts(): void
    {
        [$teacher, $ctx] = $this->courseTeacher('r2t6', 2);
        $lp = $this->lp();
        $learners = [];
        foreach (['a', 'b'] as $tag) {
            $learners[$tag] = $this->rawUser('r2t6'.$tag);
            $this->courseUser($learners[$tag], 2, 5);
        }
        $insert = function (int $user, float $score, string $date) use ($lp): void {
            $this->conn()->executeStatement(
                "INSERT INTO track_e_exercises (exe_user_id, c_id, session_id, exe_exo_id, exe_date, score, max_score, user_ip, status, data_tracking,
                    start_date, steps_counter, orig_lp_id, orig_lp_item_id, exe_duration, orig_lp_item_view_id, questions_to_check)
                 VALUES (?, 2, NULL, 1, ?, ?, 10, '', '', '', '2001-01-01 09:00:00', 0, ?, 7, 0, 0, '')",
                [$user, $date, $score, $lp]
            );
        };
        $insert($learners['a'], 2, '2001-01-01 10:00:00');
        $insert($learners['a'], 8, '2001-01-02 10:00:00');
        $insert($learners['b'], 6, '2001-01-01 10:00:00');

        $row = $this->section($ctx, 'courses', ['userId' => $teacher])['items'][0];

        $this->assertSame(70.0, $row['averageLearningPathScore'], '(80 + 60) / 2: only the latest attempt of each learner counts');
    }

    // ------------------------------------------------------ access overview

    private function access(int $user, int $course, string $in, ?string $out, int $session = 0, string $ip = '10.0.0.1'): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO track_e_course_access (user_id, c_id, login_course_date, logout_course_date, counter, session_id, user_ip)
             VALUES (?, ?, ?, ?, 1, ?, ?)',
            [$user, $course, $in, $out, $session, $ip]
        );

        return (int) $this->conn()->lastInsertId();
    }

    public function testAccessOverviewNeedsACourseAndAProfileBeforeListingAnything(): void
    {
        $u = $this->rawUser('r2a0');
        $this->access($u, 1, '2001-01-01 10:00:00', '2001-01-01 11:00:00');
        foreach ([[], ['courseId' => 1], ['status' => 5]] as $filters) {
            $result = $this->section($this->admin(), 'access-overview', $filters);
            $this->assertSame([], $result['items']);
            $this->assertSame(0, $result['total']);
        }
        $this->assertTrue($this->section($this->admin(), 'access-overview')['meta']['requiresCourse']);
    }

    public function testAccessOverviewListsTheVisitsOfLiveUsersOfTheProfileNewestFirst(): void
    {
        $u = $this->rawUser('r2a1');
        $teacher = $this->rawUser('r2a2', self::TEACHER);
        $deleted = $this->rawUser('r2a3', 5, -2);
        $old = $this->access($u, 2, '2001-01-01 10:00:00', '2001-01-01 11:30:00');
        $new = $this->access($u, 2, '2001-02-01 10:00:00', '2001-02-01 10:00:45');
        $open = $this->access($u, 2, '2001-03-01 10:00:00', null);
        $this->access($teacher, 2, '2001-01-01 10:00:00', '2001-01-01 11:00:00');
        $this->access($deleted, 2, '2001-01-01 10:00:00', '2001-01-01 11:00:00');
        $this->access($u, 1, '2001-01-01 10:00:00', '2001-01-01 11:00:00');

        $result = $this->section($this->admin(), 'access-overview', ['courseId' => 2, 'status' => 5, 'keyword' => 'x']);
        $mine = array_values(array_filter($result['items'], static fn (array $r): bool => str_starts_with($r['username'], 'rp_r2a1_')));

        $this->assertSame([$open, $new, $old], array_column($mine, 'id'));
        $this->assertSame([0, 45, 5400], array_column($mine, 'durationSeconds'), 'an open visit lasts 0 seconds');
        // The IP column follows the platform's "hide IP" setting; a hidden column never leaks the value.
        $this->assertSame(\in_array('ip', array_column($result['columns'], 'key'), true), isset($mine[0]['ip']));
        $this->assertSame($result['total'], \count($result['items']));
        $this->assertNotContains('rp_r2a2', array_map(static fn (array $r): string => substr($r['username'], 0, 7), $result['items']));
        $this->assertSame(3, $result['total'], 'the teacher, the soft-deleted learner and the other course are left out');
    }

    public function testAccessOverviewFiltersBySessionUserAndDateRange(): void
    {
        $session = $this->rawSession('r2a4');
        $a = $this->rawUser('r2a4a');
        $b = $this->rawUser('r2a4b');
        $inSession = $this->access($a, 2, '2001-05-10 10:00:00', '2001-05-10 11:00:00', $session);
        $outside = $this->access($a, 2, '2001-05-11 10:00:00', '2001-05-11 11:00:00');
        $other = $this->access($b, 2, '2001-05-10 12:00:00', '2001-05-10 13:00:00', $session);

        $base = ['courseId' => 2, 'status' => 5];
        $this->assertSame([$other, $inSession], array_column($this->section($this->admin(), 'access-overview', $base + ['sessionId' => $session])['items'], 'id'));
        $this->assertSame([$inSession], array_column($this->section($this->admin(), 'access-overview', $base + ['sessionId' => $session, 'userId' => $a])['items'], 'id'));
        $range = $this->section($this->admin(), 'access-overview', $base + ['userId' => $a, 'startDate' => '2001-05-11', 'endDate' => '2001-05-11']);
        $this->assertSame([$outside], array_column($range['items'], 'id'), 'the end date is inclusive');
        $this->assertSame(0, $this->section($this->admin(), 'access-overview', $base + ['userId' => $a, 'startDate' => '2001-05-12'])['total']);
    }

    /**
     * PINNED QUIRK: with an end date, visits whose logout is still NULL (the visit is open) are never listed, since
     * "NULL < date" is not true.
     */
    public function testAccessOverviewEndDateHidesOpenVisits(): void
    {
        $a = $this->rawUser('r2a5');
        $this->access($a, 2, '2001-06-10 10:00:00', null);
        $base = ['courseId' => 2, 'status' => 5, 'userId' => $a];
        $this->assertSame(1, $this->section($this->admin(), 'access-overview', $base)['total']);
        $this->assertSame(0, $this->section($this->admin(), 'access-overview', $base + ['endDate' => '2001-12-31'])['total']);
    }

    public function testAccessOverviewRejectsACourseOfAnotherUrl(): void
    {
        $other = $this->secondUrl();
        $this->conn()->executeStatement('UPDATE access_url_rel_course SET access_url_id = ? WHERE c_id = 2', [$other]);
        $this->expectException(AccessDeniedHttpException::class);
        $this->section($this->admin(), 'access-overview', ['courseId' => 2, 'status' => 5]);
    }

    // ------------------------------------------------------------ coaches

    public function testAdminCoachesListTeachersOfTheUrlWithTheirSessionsAndCourses(): void
    {
        $other = $this->secondUrl();
        $coach = $this->rawUser('r2k1', self::TEACHER);
        $notTeacher = $this->rawUser('r2k2');
        $foreign = $this->rawUser('r2k3', self::TEACHER, 1, $other);
        $s1 = $this->rawSession('r2k1');
        $s2 = $this->rawSession('r2k2');
        $this->sessionUser($s1, $coach, 3);
        $this->sessionUser($s2, $coach, 3);
        $this->sessionUser($s2, $notTeacher, 3);
        $this->sessionCourseUser($s1, 1, $coach, 2);
        $this->sessionCourseUser($s1, 2, $coach, 2);
        $this->sessionCourseUser($s2, 1, $coach, 2);
        $this->conn()->executeStatement("INSERT INTO track_e_login (login_user_id, login_date, user_ip, logout_date) VALUES (?, '2001-01-01 10:00:00', '', NULL), (?, '2001-04-01 10:00:00', '', NULL)", [$coach, $coach]);
        $this->conn()->executeStatement('UPDATE user SET lastname = ? WHERE id IN (?, ?)', ['AAAr2k', $coach, $foreign]);

        $result = $this->section($this->admin(), 'admin-coaches', ['itemsPerPage' => 100]);
        $row = array_values(array_filter($result['items'], static fn (array $r): bool => $r['id'] === $coach))[0];

        $this->assertSame(2, $row['sessions']);
        $this->assertSame(2, $row['courses'], 'distinct courses');
        $this->assertSame('2001-04-01 10:00:00', $row['lastLogin']);
        $this->assertSame('Trainers Overview', $result['title']);
        $ids = array_column($result['items'], 'id');
        $this->assertNotContains($notTeacher, $ids);
        $this->assertNotContains($foreign, $ids);
        $this->assertSame($result['total'], $result['summary'][0]['value']);
        $this->assertArrayNotHasKey('email', $row);
    }

    public function testAdminCoachesArePagedAlphabetically(): void
    {
        $ids = [];
        foreach (['a', 'b', 'c'] as $letter) {
            $id = $this->rawUser('r2kp'.$letter, self::TEACHER);
            $this->conn()->executeStatement('UPDATE user SET lastname = ?, firstname = ? WHERE id = ?', ['0000r2kp'.$letter, 'F', $id]);
            $ids[] = $id;
        }
        $first = $this->section($this->admin(), 'admin-coaches', ['itemsPerPage' => 10]);
        $this->assertSame($ids, array_slice(array_column($first['items'], 'id'), 0, 3));
        $total = $first['total'];
        $page2 = $this->section($this->admin(), 'admin-coaches', ['itemsPerPage' => 10, 'page' => 2]);
        $this->assertCount(max(0, min(10, $total - 10)), $page2['items']);
    }

    // ------------------------------------------------------ current courses

    public function testCurrentCoursesCountLearnersSessionsVisitorsAndProgressPerCourse(): void
    {
        $session = $this->rawSession('r2cc1');
        $this->sessionCourse($session, 2);
        $a = $this->rawUser('r2cc1a');
        $b = $this->rawUser('r2cc1b');
        $teacher = $this->rawUser('r2cc1t', self::TEACHER);
        $this->courseUser($a, 2, 5);
        $this->courseUser($b, 2, 5);
        $this->courseUser($teacher, 2, 5);
        $this->courseVisit($a, 2, '2001-01-01 10:00:00', '2001-01-01 11:00:00');
        $this->courseVisit($a, 2, '2001-03-01 10:00:00', '2001-03-01 11:00:00');
        $this->courseVisit($teacher, 2, '2001-02-01 10:00:00', null);
        $this->lpView($a, 2, 100);
        $this->lpView($b, 2, 51);

        $result = $this->section($this->admin(), 'current-courses', ['itemsPerPage' => 100]);
        $row = array_values(array_filter($result['items'], static fn (array $r): bool => 2 === $r['id']))[0];

        $this->assertSame(2, $row['learners'], 'users with the learner profile subscribed to the course');
        $this->assertSame(1, $row['sessions']);
        $this->assertSame(2, $row['activeLearners'], 'distinct users who visited the course, the teacher included');
        $this->assertSame('2001-03-01 10:00:00', $row['lastAccess']);
        $this->assertSame(75.5, $row['progress']);
        $this->assertSame('Courses overview', $result['title']);
        $this->assertSame($result['total'], $result['summary'][0]['value']);
        $this->assertSame(['id', 'code', 'visualCode', 'title'], \array_slice(array_keys($row), 0, 4));
    }

    public function testCurrentCoursesAreFilteredByKeyword(): void
    {
        $title = (string) $this->conn()->fetchOne('SELECT title FROM course WHERE id = 1');
        $this->assertSame(0, $this->section($this->admin(), 'current-courses', ['keyword' => 'zz-no-such-course'])['total']);
        $result = $this->section($this->admin(), 'current-courses', ['keyword' => $title]);
        $this->assertContains(1, array_column($result['items'], 'id'));
        $this->assertSame([], array_values(array_filter($result['items'], static fn (array $r): bool => !str_contains($r['title'].$r['code'].$r['visualCode'], substr($title, 0, 1)))));
    }

    // ------------------------------------------------------- courses overview

    public function testAdminCoursesOverviewSumsStudentTimeProgressAndTestScoresOutsideSessions(): void
    {
        $session = $this->rawSession('r2co1');
        $learner = $this->rawUser('r2co1a');
        $teacher = $this->rawUser('r2co1t', self::TEACHER);
        $this->courseUser($learner, 2, 5);
        $this->courseUser($teacher, 2, 1);
        $this->courseVisit($learner, 2, '2001-01-01 10:00:00', '2001-01-01 10:20:00');
        $this->courseVisit($teacher, 2, '2001-01-02 10:00:00', '2001-01-02 12:00:00');
        $this->access($learner, 2, '2001-01-03 10:00:00', '2001-01-03 12:00:00', $session);
        $lp = $this->lp();
        $this->lpView($learner, 2, 20, 0, $lp);
        $this->conn()->executeStatement('INSERT INTO c_lp_view (user_id, lp_id, c_id, session_id, view_count, last_item, progress) VALUES (?, ?, 2, NULL, 5, 0, 80)', [$learner, $lp]);
        $this->lpView($learner, 2, 10, $session, $lp);
        foreach ([[6, 10], [9, 10]] as [$score, $max]) {
            $this->conn()->executeStatement(
                "INSERT INTO track_e_exercises (exe_user_id, c_id, session_id, exe_exo_id, exe_date, score, max_score, user_ip, status, data_tracking,
                    start_date, steps_counter, orig_lp_id, orig_lp_item_id, exe_duration, orig_lp_item_view_id, questions_to_check)
                 VALUES (?, 2, NULL, 1, '2001-01-01 10:00:00', ?, ?, '', '', '', '2001-01-01 09:00:00', 0, 0, 0, 0, 0, '')",
                [$learner, $score, $max]
            );
        }

        $result = $this->section($this->admin(), 'admin-courses', ['itemsPerPage' => 100]);
        $row = array_values(array_filter($result['items'], static fn (array $r): bool => 2 === $r['id']))[0];

        $this->assertSame(1, $row['learners']);
        $this->assertSame(1200, $row['timeSeconds'], 'only the learner, outside sessions');
        $this->assertSame('2001-01-01 10:00:00', $row['lastAccess']);
        $this->assertSame(80.0, $row['progress'], 'the latest view of the pair counts, session views are left out');
        $this->assertSame(15.0, $row['scoreObtained']);
        $this->assertSame(20.0, $row['scorePossible']);
        $this->assertSame(75.0, $row['scorePercentage']);
        $this->assertSame(2, $row['questionsAnswered']);
        $this->assertSame(0, $row['messages']);
        $this->assertSame(0, $row['assignments']);
        $this->assertNull($row['averageLearningPathScore']);
        $this->assertSame('Courses overview', $result['title']);
        $this->assertSame('course-cards', $result['meta']['renderMode']);
        $untouched = array_values(array_filter($result['items'], static fn (array $r): bool => 1 === $r['id']))[0];
        $this->assertNull($untouched['scorePercentage']);
        $this->assertSame(0.0, $untouched['progress']);
    }

    public function testAdminCoursesOverviewKeywordPagingAndUrlScope(): void
    {
        $other = $this->secondUrl();
        $this->conn()->executeStatement('UPDATE access_url_rel_course SET access_url_id = ? WHERE c_id = 2', [$other]);
        $result = $this->section($this->admin(), 'admin-courses', ['itemsPerPage' => 100]);
        $this->assertNotContains(2, array_column($result['items'], 'id'));
        $this->assertContains(1, array_column($result['items'], 'id'));
        $this->assertSame(0, $this->section($this->admin(), 'admin-courses', ['keyword' => 'zz-no-such-course'])['total']);
        $titles = array_column($result['items'], 'title');
        $sorted = $titles;
        sort($sorted, SORT_STRING | SORT_FLAG_CASE);
        $this->assertSame($sorted, $titles, 'ordered by title');
    }

    // ----------------------------------------------------- student bosses

    public function testStudentBossesReportListsActiveBossesOfTheUrlWithTheirLearners(): void
    {
        $other = $this->secondUrl();
        $boss = $this->rawUser('r2sb1', self::BOSS);
        $inactiveBoss = $this->rawUser('r2sb2', self::BOSS, 0);
        $foreignBoss = $this->rawUser('r2sb3', self::BOSS, 1, $other);
        $notBoss = $this->rawUser('r2sb4');
        $z = $this->rawUser('r2sb1z');
        $a = $this->rawUser('r2sb1a');
        $gone = $this->rawUser('r2sb1g', 5, -2);
        $this->conn()->executeStatement('UPDATE user SET lastname = ? WHERE id = ?', ['Zed', $z]);
        $this->conn()->executeStatement('UPDATE user SET lastname = ? WHERE id = ?', ['Alpha', $a]);
        $this->conn()->executeStatement('UPDATE user SET lastname = ?, locale = ? WHERE id = ?', ['0000r2sb', 'fr_FR', $boss]);
        foreach ([$z, $a, $gone] as $learner) {
            $this->userRel($learner, $boss, self::REL_BOSS);
        }
        $this->userRel($a, $inactiveBoss, self::REL_BOSS);
        $this->userRel($notBoss, $boss, 7);

        $result = $this->section($this->admin(), 'student-bosses', ['itemsPerPage' => 100]);
        $row = array_values(array_filter($result['items'], static fn (array $r): bool => $r['id'] === $boss))[0];

        $ids = array_column($result['items'], 'id');
        $this->assertNotContains($inactiveBoss, $ids);
        $this->assertNotContains($foreignBoss, $ids);
        $this->assertNotContains($notBoss, $ids);
        $this->assertSame([$a, $gone, $z], array_column($row['learners'], 'id'), 'ordered by learner last name; soft-deleted ones are listed too');
        $this->assertSame([true, false, true], array_column($row['learners'], 'active'));
        $this->assertSame('fr_FR', $row['locale']);
        $this->assertContains('fr_FR', $result["meta"]['languageOptions']);
        $this->assertTrue($result['meta']['canAddLearners']);
        $this->assertSame("Student's superior follow up", $result['title']);
        $byLanguage = $this->section($this->admin(), 'student-bosses', ['language' => 'fr_FR']);
        $this->assertContains($boss, array_column($byLanguage['items'], 'id'));
        $this->assertSame($byLanguage['total'], \count($byLanguage['items']));
        $this->assertSame(0, $this->section($this->admin(), 'student-bosses', ['language' => 'xx_XX'])['total']);
    }
}
