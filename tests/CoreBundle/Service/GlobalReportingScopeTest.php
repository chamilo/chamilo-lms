<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingContext;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingQueryService;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Characterization of GlobalReportingQueryService: the scope methods decide which users, courses and sessions
 * every /reporting page may show, so a rewrite of their raw SQL must keep the exact id lists, the access URL
 * confinement and the role-specific scopes (default coach/teacher/HR, student boss, session admin, HR with all
 * session content).
 */
final class GlobalReportingScopeTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    private const TEACHER = 1;
    private const HR = 4;
    private const STUDENT = 5;
    private const BOSS = 17;

    // user_rel_user relation types
    private const REL_HR = 7;
    private const REL_BOSS = 8;
    // session_rel_user relation types
    private const SESSION_HR = 1;
    private const SESSION_GENERAL_COACH = 3;
    private const SESSION_ADMIN = 4;

    private GlobalReportingQueryService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(GlobalReportingQueryService::class);
    }

    /**
     * @param array<string, bool> $flags
     */
    private function ctx(int $userId, array $flags = [], int $urlId = 1): GlobalReportingContext
    {
        $this->fresh();
        $f = $flags + [
            'admin' => false, 'hr' => false, 'sessionAdminOnly' => false, 'canViewGlobal' => true, 'boss' => false,
            'hrAll' => false, 'skipGeneric' => false, 'blockMyProgress' => false, 'addUsersByCoach' => false,
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
            $f['skipGeneric'],
            false,
            $f['blockMyProgress'],
            $f['addUsersByCoach'],
            false,
            false,
            false,
        );
    }

    // ------------------------------------------------------------ landing url

    public function testLandingUrlOfSessionAdministratorIsTheSessionList(): void
    {
        $u = $this->rawUser('l1', self::TEACHER);
        $this->assertSame('/reporting/sessions', $this->service->resolveLandingUrl($this->ctx($u, ['sessionAdminOnly' => true])));
    }

    public function testLandingUrlWithoutGlobalReportsDependsOnBossAndMyProgress(): void
    {
        $u = $this->rawUser('l2');
        $this->assertSame('/reporting/learners', $this->service->resolveLandingUrl($this->ctx($u, ['canViewGlobal' => false, 'boss' => true])));
        $this->assertSame('/', $this->service->resolveLandingUrl($this->ctx($u, ['canViewGlobal' => false, 'blockMyProgress' => true])));
        $this->assertSame('/reporting/my-progress', $this->service->resolveLandingUrl($this->ctx($u, ['canViewGlobal' => false])));
        // The student boss rule wins over the blocked "my progress" page.
        $this->assertSame('/reporting/learners', $this->service->resolveLandingUrl($this->ctx($u, ['canViewGlobal' => false, 'boss' => true, 'blockMyProgress' => true])));
    }

    public function testLandingUrlIsNullForUsersWhoMayViewGlobalReports(): void
    {
        $u = $this->rawUser('l3');
        $this->assertNull($this->service->resolveLandingUrl($this->ctx($u)));
    }

    // ---------------------------------------------------------- course scope

    public function testAdministratorSeesEveryCourseOfTheCurrentUrlOnly(): void
    {
        $this->assertSame([1, 2], $this->service->getScopedCourseIds($this->ctx(1, ['admin' => true])));
        $other = $this->secondUrl();
        $this->assertSame([], $this->service->getScopedCourseIds($this->ctx(1, ['admin' => true], $other)));
    }

    public function testNonAdministratorCourseScopeMergesCourseSessionAndSessionCourseSubscriptions(): void
    {
        $u = $this->rawUser('c1', self::TEACHER);
        $this->courseUser($u, 2, 1);
        $this->assertSame([2], $this->service->getScopedCourseIds($this->ctx($u)));

        $s = $this->rawSession('c1');
        $this->sessionCourse($s, 1);
        $this->sessionUser($s, $u, 0);
        $this->assertSame([1, 2], $this->service->getScopedCourseIds($this->ctx($u)));

        $v = $this->rawUser('c1b', self::TEACHER);
        $s2 = $this->rawSession('c1b');
        $this->sessionCourseUser($s2, 1, $v, 2);
        $this->assertSame([1], $this->service->getScopedCourseIds($this->ctx($v)));
    }

    public function testNonAdministratorCourseScopeIgnoresSessionsOfAnotherUrl(): void
    {
        $u = $this->rawUser('c2', self::TEACHER);
        $s = $this->rawSession('c2', null);
        $this->sessionCourse($s, 1);
        $this->sessionUser($s, $u, 0);
        $this->sessionCourseUser($s, 2, $u, 2);
        // The session is registered on no access URL, so neither relation reaches the user.
        $this->assertSame([], $this->service->getScopedCourseIds($this->ctx($u)));
    }

    public function testCourseScopeOfAUserSubscribedToNothingIsEmptyAndConfinedToTheUrl(): void
    {
        $u = $this->rawUser('c3', self::TEACHER);
        $this->courseUser($u, 1, 1);
        $other = $this->secondUrl();
        $this->assertSame([], $this->service->getScopedCourseIds($this->ctx($u, [], $other)));
    }

    public function testAssignedCoursesAreTheOnesTheUserTeachesNotJustFollows(): void
    {
        $u = $this->rawUser('a1', self::TEACHER);
        $this->courseUser($u, 1, 1);
        $this->courseUser($u, 2, 5);
        $this->assertSame([1], $this->service->getAssignedCourseIds($this->ctx($u)));
    }

    public function testAssignedCoursesOfAnHrManagerWithAllSessionContentComeFromHrSessions(): void
    {
        $u = $this->rawUser('a2', self::HR);
        $this->courseUser($u, 1, 1);
        $s = $this->rawSession('a2');
        $this->sessionCourse($s, 2);
        $this->sessionUser($s, $u, self::SESSION_HR);
        $notHr = $this->rawSession('a2b');
        $this->sessionCourse($notHr, 1);
        $this->sessionUser($notHr, $u, 0);

        $this->assertSame([2], $this->service->getAssignedCourseIds($this->ctx($u, ['hr' => true, 'hrAll' => true])));
        // Without the all-session-content permission the teacher subscription rule applies.
        $this->assertSame([1], $this->service->getAssignedCourseIds($this->ctx($u, ['hr' => true])));
    }

    public function testFollowedCoursesUseTheCourseManagerRelationOrTheHrRelation(): void
    {
        $u = $this->rawUser('f1', self::TEACHER);
        $this->courseUser($u, 1, 1, 1);
        $this->courseUser($u, 2, 1, 0);
        $this->assertSame([1], $this->service->getFollowedCourseIds($this->ctx($u)));

        $h = $this->rawUser('f2', self::HR);
        $this->courseUser($h, 2, 1, 1);
        $this->assertSame([2], $this->service->getFollowedCourseIds($this->ctx($h, ['hr' => true])));
        $this->assertSame([], $this->service->getFollowedCourseIds($this->ctx($h, ['hr' => true, 'hrAll' => true])));
    }

    // --------------------------------------------------------- session scope

    public function testAdministratorSeesEverySessionOfTheCurrentUrlOnly(): void
    {
        $a = $this->rawSession('s1');
        $b = $this->rawSession('s1b');
        $hidden = $this->rawSession('s1c', null);
        $ids = $this->service->getScopedSessionIds($this->ctx(1, ['admin' => true]));
        $this->assertSame([$a, $b], $ids);
        $this->assertNotContains($hidden, $ids);
        $this->assertSame([], $this->service->getScopedSessionIds($this->ctx(1, ['admin' => true], $this->secondUrl())));
    }

    public function testNonAdministratorSessionScopeMergesSessionAndSessionCourseRelations(): void
    {
        $u = $this->rawUser('s2', self::TEACHER);
        $a = $this->rawSession('s2a');
        $b = $this->rawSession('s2b');
        $c = $this->rawSession('s2c');
        $this->sessionUser($b, $u, self::SESSION_GENERAL_COACH);
        $this->sessionCourseUser($a, 1, $u, 2);
        // Subscribed to both: reported once.
        $this->sessionUser($a, $u, 0);
        $this->assertSame([$a, $b], $this->service->getScopedSessionIds($this->ctx($u)));
        $this->assertNotContains($c, $this->service->getScopedSessionIds($this->ctx($u)));
    }

    // ------------------------------------------------------------ user scope

    public function testDefaultScopeCollectsHrFollowedTeacherCourseAndCoachedUsers(): void
    {
        $me = $this->rawUser('u1', self::TEACHER);
        $hrFollowed = $this->rawUser('u1hr', self::STUDENT);
        $this->userRel($hrFollowed, $me, self::REL_HR);
        $courseStudent = $this->rawUser('u1cs', self::STUDENT);
        $this->courseUser($me, 1, 1);
        $this->courseUser($courseStudent, 1, 5);
        $generalCoached = $this->rawUser('u1gc', self::STUDENT);
        $s = $this->rawSession('u1');
        $this->sessionUser($s, $me, self::SESSION_GENERAL_COACH);
        $this->sessionCourseUser($s, 2, $generalCoached, 0);
        $courseCoached = $this->rawUser('u1cc', self::STUDENT);
        $s2 = $this->rawSession('u1b');
        $this->sessionCourseUser($s2, 1, $me, 2);
        $this->sessionCourseUser($s2, 1, $courseCoached, 0);
        $unrelated = $this->rawUser('u1x', self::STUDENT);

        $ids = $this->service->getScopedUserIds($this->ctx($me));
        // The installer admin (id 1) teaches course 1 too, and the teacher is his own course mate.
        $expected = [1, $me, $hrFollowed, $courseStudent, $generalCoached, $courseCoached];
        sort($expected);
        $this->assertSame($expected, $ids);
        $this->assertNotContains($unrelated, $ids);
    }

    public function testDefaultScopeFiltersByStatusAndKeepsStatusGroupsSeparate(): void
    {
        $me = $this->rawUser('u2', self::TEACHER);
        $this->courseUser($me, 1, 1);
        $student = $this->rawUser('u2s', self::STUDENT);
        $teacher = $this->rawUser('u2t', self::TEACHER);
        $boss = $this->rawUser('u2b', self::BOSS);
        $anon = $this->rawUser('u2a', 6);
        foreach ([$student, $teacher, $boss, $anon] as $uid) {
            $this->courseUser($uid, 1, 5);
        }
        $ctx = $this->ctx($me);
        $this->assertSame([$student], $this->service->getScopedUserIds($ctx, self::STUDENT));
        $this->assertSame([$boss], $this->service->getScopedUserIds($ctx, self::BOSS));
        $this->assertSame([], $this->service->getScopedUserIds($ctx, self::HR));
        // The teacher (me included, via his own subscription) is listed; the anonymous status 6 is never.
        $all = $this->service->getScopedUserIds($ctx);
        $this->assertContains($teacher, $all);
        $this->assertContains($me, $all);
        $this->assertNotContains($anon, $all);
    }

    public function testDefaultScopeExcludesUsersOfAnotherAccessUrl(): void
    {
        $me = $this->rawUser('u3', self::TEACHER);
        $this->courseUser($me, 1, 1);
        $here = $this->rawUser('u3a', self::STUDENT);
        $elsewhere = $this->rawUser('u3b', self::STUDENT, 1, null);
        $other = $this->secondUrl();
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (?, ?)', [$other, $elsewhere]);
        $this->courseUser($here, 1, 5);
        $this->courseUser($elsewhere, 1, 5);
        $this->assertSame([$here], $this->service->getScopedUserIds($this->ctx($me), self::STUDENT));
    }

    public function testDefaultScopeDoesNotDependOnTheStudentsOwnCourseStatusOrTheirSessions(): void
    {
        // A student that only shares a session (not the course) with the teacher is out of scope.
        $me = $this->rawUser('u4', self::TEACHER);
        $this->courseUser($me, 1, 1);
        $s = $this->rawUser('u4s', self::STUDENT);
        $this->courseUser($s, 2, 5);
        $this->assertSame([], $this->service->getScopedUserIds($this->ctx($me), self::STUDENT));
    }

    /**
     * Soft-deleted accounts keep their status (5) and only get active = -2. A report of "my learners" must not
     * list them: the legacy tracking grids and GlobalReportingSessionLearnerCountTest never count them.
     */
    public function testSoftDeletedLearnersAreNotInTheScope(): void
    {
        $me = $this->rawUser('u5', self::TEACHER);
        $this->courseUser($me, 1, 1);
        $alive = $this->rawUser('u5a', self::STUDENT);
        $deleted = $this->rawUser('u5b', self::STUDENT, -2);
        $this->courseUser($alive, 1, 5);
        $this->courseUser($deleted, 1, 5);
        $this->assertNotSame($deleted, $alive);
        $this->assertSame([$alive], $this->service->getScopedUserIds($this->ctx($me), self::STUDENT));
    }

    public function testStudentBossSeesOnlyTheUsersHeBosses(): void
    {
        $boss = $this->rawUser('b1', self::BOSS);
        $mine = $this->rawUser('b1a', self::STUDENT);
        $notMine = $this->rawUser('b1b', self::STUDENT);
        $this->userRel($mine, $boss, self::REL_BOSS);
        // An HR relation is not a boss relation.
        $hrOnly = $this->rawUser('b1c', self::STUDENT);
        $this->userRel($hrOnly, $boss, self::REL_HR);
        $this->courseUser($boss, 1, 1);
        $this->courseUser($notMine, 1, 5);
        $ids = $this->service->getScopedUserIds($this->ctx($boss, ['boss' => true]));
        $this->assertSame([$mine], $ids);
    }

    public function testSessionAdministratorSeesLearnersOfTheSessionsHeAdministers(): void
    {
        $adm = $this->rawUser('sa1', self::TEACHER);
        $s = $this->rawSession('sa1');
        $this->sessionUser($s, $adm, self::SESSION_ADMIN);
        $viaCourse = $this->rawUser('sa1a', self::STUDENT);
        $this->sessionCourseUser($s, 1, $viaCourse);
        $viaSession = $this->rawUser('sa1b', self::STUDENT);
        $this->sessionUser($s, $viaSession, 0);
        $otherSession = $this->rawSession('sa1o');
        $outside = $this->rawUser('sa1c', self::STUDENT);
        $this->sessionCourseUser($otherSession, 1, $outside);

        $ids = $this->service->getScopedUserIds($this->ctx($adm, ['sessionAdminOnly' => true]), self::STUDENT);
        $expected = [$viaCourse, $viaSession];
        sort($expected);
        $this->assertSame($expected, $ids);
    }

    public function testHrManagerWithAllSessionContentSeesHrSessionCourseAndRelatedUsers(): void
    {
        $hr = $this->rawUser('h1', self::HR);
        $s = $this->rawSession('h1');
        $this->sessionUser($s, $hr, self::SESSION_HR);
        $inSession = $this->rawUser('h1a', self::STUDENT);
        $this->sessionCourseUser($s, 1, $inSession);
        $this->courseUser($hr, 2, self::HR, 1);
        $inCourse = $this->rawUser('h1b', self::STUDENT);
        $this->courseUser($inCourse, 2, 5);
        $related = $this->rawUser('h1c', self::STUDENT);
        $this->userRel($related, $hr, self::REL_HR);
        $outsider = $this->rawUser('h1d', self::STUDENT);
        $this->courseUser($outsider, 1, 5);

        $ids = $this->service->getScopedUserIds($this->ctx($hr, ['hr' => true, 'hrAll' => true]), self::STUDENT);
        $expected = [$inSession, $inCourse, $related];
        sort($expected);
        $this->assertSame($expected, $ids);
    }

    // ----------------------------------------------------------- isUserInScope

    public function testAUserIsAlwaysInHisOwnScope(): void
    {
        $u = $this->rawUser('i1', self::STUDENT);
        $this->assertTrue($this->service->isUserInScope($this->ctx($u), $u));
    }

    public function testNonAdministratorScopeMembershipFollowsTheScopedIds(): void
    {
        $me = $this->rawUser('i2', self::TEACHER);
        $this->courseUser($me, 1, 1);
        $mine = $this->rawUser('i2a', self::STUDENT);
        $this->courseUser($mine, 1, 5);
        $other = $this->rawUser('i2b', self::STUDENT);
        $this->assertTrue($this->service->isUserInScope($this->ctx($me), $mine));
        $this->assertFalse($this->service->isUserInScope($this->ctx($me), $other));
    }

    public function testAdministratorMembershipIsLimitedToTheUrlsHeManages(): void
    {
        $here = $this->rawUser('i3a', self::STUDENT);
        $elsewhere = $this->rawUser('i3b', self::STUDENT, 1, null);
        $other = $this->secondUrl(true);
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (?, ?)', [$other, $elsewhere]);
        $ctx = $this->ctx(1, ['admin' => true]);
        $this->assertTrue($this->service->isUserInScope($ctx, $here));
        $this->assertTrue($this->service->isUserInScope($ctx, $elsewhere), 'the main-URL admin is unrestricted');

        // An admin registered only on a child URL manages that URL only.
        $localAdmin = $this->rawUser('i3c', self::TEACHER, 1, null);
        $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (?, ?)', [$other, $localAdmin]);
        $lctx = $this->ctx($localAdmin, ['admin' => true], $other);
        $this->assertTrue($this->service->isUserInScope($lctx, $elsewhere));
        $this->assertFalse($this->service->isUserInScope($lctx, $here));
    }

    // --------------------------------------------------------------- dashboard

    public function testDashboardCountsFollowedUsersCoursesAndGenericMetrics(): void
    {
        $me = $this->rawUser('d1', self::TEACHER);
        $this->courseUser($me, 1, 1, 1);
        $s1 = $this->rawUser('d1a', self::STUDENT);
        $s2 = $this->rawUser('d1b', self::STUDENT);
        $boss = $this->rawUser('d1c', self::BOSS);
        $this->courseUser($s1, 1, 5);
        $this->courseUser($s2, 1, 5);
        $this->courseUser($boss, 1, 5);
        // s1 never logged in (inactive); s2 logged in far in the future (active); only active accounts count.
        $this->conn()->executeStatement('UPDATE user SET last_login = ? WHERE id = ?', ['2099-01-01 00:00:00', $s2]);
        $this->conn()->executeStatement('UPDATE user SET last_login = ? WHERE id = ?', ['2001-01-01 00:00:00', $s1]);
        // 10 minutes on the platform for s1, a still-open visit (NULL logout) for s2.
        $this->conn()->executeStatement(
            "INSERT INTO track_e_login (login_user_id, login_date, user_ip, logout_date) VALUES
             (?, '2020-01-01 10:00:00', '', '2020-01-01 10:10:00'), (?, '2020-01-01 10:00:00', '', NULL)",
            [$s1, $s2]
        );
        $s = $this->rawSession('d1');
        $this->sessionUser($s, $me, self::SESSION_GENERAL_COACH);
        $s3 = $this->rawSession('d1b');
        $this->sessionCourseUser($s3, 1, $me, 2);
        // Subscribed as a plain learner only: not a followed session.
        $s4 = $this->rawSession('d1c');
        $this->sessionUser($s4, $me, 0);

        $d = $this->service->getDashboard($this->ctx($me, ['addUsersByCoach' => true]));

        $this->assertNull($d['redirectUrl']);
        $this->assertSame(2, $d['students']);
        $this->assertSame(1, $d['studentBosses']);
        $this->assertSame(2, $d['teachers'], 'the teacher and the installer admin, both teaching course 1');
        $this->assertSame(0, $d['humanResources']);
        $this->assertSame(5, $d['totalUsers']);
        $this->assertSame(1, $d['assignedCourses']);
        $this->assertSame(1, $d['followedCourses']);
        $this->assertSame(2, $d['followedSessions']);
        $this->assertTrue($d['canManageFollowedScope']);
        $this->assertSame(0.5, $d['averageCoursesPerStudent']);
        $this->assertSame(1, $d['inactiveStudents']);
        $this->assertSame(300, $d['averageTimeSpentSeconds']);
        $this->assertSame(0.0, $d['averageLearningPathProgress']);
        $this->assertSame(0.0, $d['averageScore']);
        $this->assertSame(0, $d['forumPosts']);
        $this->assertEquals(0.0, $d['averageAssignments']);
    }

    public function testDashboardSkipsGenericMetricsWhenAskedAndZeroesThemWithoutStudents(): void
    {
        $me = $this->rawUser('d2', self::TEACHER);
        $this->courseUser($me, 2, 1);
        $skipped = $this->service->getDashboard($this->ctx($me, ['skipGeneric' => true]));
        $this->assertNull($skipped['averageCoursesPerStudent']);
        $this->assertNull($skipped['forumPosts']);
        $this->assertSame(0, $skipped['students']);
        $this->assertFalse($skipped['canManageFollowedScope'], 'addUsersByCoach is off');

        $empty = $this->service->getDashboard($this->ctx($me));
        $this->assertSame(0.0, $empty['averageCoursesPerStudent']);
        $this->assertSame(0, $empty['inactiveStudents']);
        $this->assertSame(0, $empty['averageTimeSpentSeconds']);
        $this->assertSame(1, $empty['assignedCourses']);
    }

    public function testDashboardOfARedirectedUserCarriesNoCounters(): void
    {
        $me = $this->rawUser('d3', self::TEACHER);
        $d = $this->service->getDashboard($this->ctx($me, ['canViewGlobal' => false]));
        $this->assertSame('/reporting/my-progress', $d['redirectUrl']);
        $this->assertArrayNotHasKey('students', $d);
        $this->assertTrue($d['myProgressEnabled']);
    }

    public function testDashboardOfAnHrManagerWithAllSessionContentCountsHrSessions(): void
    {
        $hr = $this->rawUser('d4', self::HR);
        $s = $this->rawSession('d4');
        $this->sessionCourse($s, 1);
        $this->sessionCourse($s, 2);
        $this->sessionUser($s, $hr, self::SESSION_HR);
        $learner = $this->rawUser('d4a', self::STUDENT);
        $this->sessionCourseUser($s, 1, $learner);
        $d = $this->service->getDashboard($this->ctx($hr, ['hr' => true, 'hrAll' => true]));
        $this->assertSame(1, $d['followedSessions']);
        $this->assertSame(2, $d['assignedCourses']);
        $this->assertSame(0, $d['followedCourses']);
        $this->assertSame(1, $d['students']);
        $this->assertEquals(2.0, $d['averageCoursesPerStudent']);
    }
}
