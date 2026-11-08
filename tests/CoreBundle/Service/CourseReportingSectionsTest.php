<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingContext;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingSectionQueryService;
use Chamilo\Tests\ChamiloTestTrait;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Characterization of CourseReportingSectionQueryService::getSection(): the activity, total time, session and
 * messages sections of the course reporting page. Learners are "rp_" rows subscribed to the installer course 1
 * (nobody else is a learner there). Activity depends on the wall clock, so its visits are seeded relative to "now"
 * with margins of hours, and the reset date is fixed when the period does not matter.
 */
final class CourseReportingSectionsTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    private CourseReportingSectionQueryService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(CourseReportingSectionQueryService::class);
    }

    private function ctx(int $courseId = 1, ?int $sessionId = null, bool $messages = true, bool $teacher = true, bool $maxProgress = false): CourseReportingContext
    {
        $this->fresh();
        $em = $this->getEntityManager();

        return new CourseReportingContext(
            $em->find(Course::class, $courseId),
            null === $sessionId ? null : $em->find(Session::class, $sessionId),
            0,
            $this->entityUser(1),
            true,
            $teacher,
            false,
            false,
            false,
            $maxProgress,
            false,
            $messages,
            [],
            [],
            [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function section(string $name, array $filters = [], ?CourseReportingContext $ctx = null): array
    {
        return $this->service->getSection($ctx ?? $this->ctx(), $name, $filters);
    }

    private function learner(string $tag, int $courseId = 1): int
    {
        $id = $this->rawUser($tag);
        $this->courseUser($id, $courseId, 5);

        return $id;
    }

    private function visit(int $userId, string $in, ?string $out, int $courseId = 1, int $sessionId = 0): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_course_access (user_id, c_id, login_course_date, logout_course_date, counter, session_id, user_ip)
             VALUES (?, ?, ?, ?, 1, ?, '')",
            [$userId, $courseId, $in, $out, $sessionId]
        );
    }

    private function lp(): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_lp (lp_type, title, path, force_commit, content_maker, content_license, js_lib, debug, theme, author,
                prerequisite, hide_toc_frame, seriousgame_mode, autolaunch, max_attempts, subscribe_users, created_on, modified_on)
             VALUES (1, 'rp_r2lp', '', 0, '', '', '', 0, '', '', 0, 0, 0, 0, 0, 0, '2001-01-01 00:00:00', '2001-01-01 00:00:00')"
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function ago(string $interval): string
    {
        return (new DateTimeImmutable('-'.$interval))->format('Y-m-d H:i:s');
    }

    // ------------------------------------------------------------ dispatch

    public function testUnknownSectionIsABadRequest(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->section('drop-table');
    }

    // ---------------------------------------------------------- total time

    public function testTotalTimeListsLearnersAlphabeticallyWithCourseTimeAndAccessDates(): void
    {
        $z = $this->learner('r2c1z');
        $a = $this->learner('r2c1a');
        $this->conn()->executeStatement('UPDATE user SET lastname = ? WHERE id = ?', ['R2Zed', $z]);
        $this->conn()->executeStatement('UPDATE user SET lastname = ? WHERE id = ?', ['R2Alpha', $a]);
        $teacher = $this->rawUser('r2c1t', 1);
        $this->courseUser($teacher, 1, 1);
        $this->visit($a, '2001-01-01 10:00:00', '2001-01-01 10:30:00');
        $this->visit($a, '2001-01-05 10:00:00', '2001-01-05 10:00:20');
        $this->visit($a, '2001-01-09 10:00:00', null);
        $this->visit($a, '2001-01-10 10:00:00', '2001-01-10 09:00:00');
        $this->visit($z, '2001-02-01 10:00:00', '2001-02-01 11:00:00');
        $this->visit($a, '2001-03-01 10:00:00', '2001-03-01 12:00:00', 2);
        $this->visit($a, '2001-03-02 10:00:00', '2001-03-02 12:00:00', 1, 99999);

        $result = $this->section('total-time');

        $this->assertSame('Total time', $result['title'] ?? 'Total time');
        $this->assertSame([$a, $z], array_column($result['items'], 'id'));
        $this->assertSame(2, $result['total']);
        $first = $result['items'][0];
        $this->assertSame(1820, $first['courseTime'], '30m + 20s; open and negative visits count for zero; other course and session ignored');
        $this->assertSame('2001-01-01 10:00:00', $first['firstAccess']);
        $this->assertSame('2001-01-10 10:00:00', $first['lastAccess']);
        $this->assertSame(0, $first['learningPathTime']);
        $this->assertSame($a, $first['details']);
        $this->assertSame(3600, $result['items'][1]['courseTime']);
        $this->assertSame(1820 + 3600, $result['summary'][1]['value']);
        $this->assertSame(2, $result['summary'][0]['value']);
    }

    public function testTotalTimeFiltersByKeywordAndPages(): void
    {
        $ids = [];
        foreach (['a', 'b', 'c'] as $tag) {
            $ids[$tag] = $this->learner('r2c2'.$tag);
            $this->conn()->executeStatement('UPDATE user SET lastname = ? WHERE id = ?', ['R2Page'.$tag, $ids[$tag]]);
        }
        $keyword = $this->section('total-time', ['keyword' => 'R2PAGEb']);
        $this->assertSame([$ids['b']], array_column($keyword['items'], 'id'), 'case-insensitive, over name, username, code and e-mail');
        $byMail = $this->section('total-time', ['keyword' => 'rp_r2c2c_']);
        $this->assertSame([$ids['c']], array_column($byMail['items'], 'id'));
        $page = $this->section('total-time', ['itemsPerPage' => 2, 'page' => 2]);
        $this->assertSame([$ids['c']], array_column($page['items'], 'id'));
        $this->assertSame(3, $page['total']);
        $this->assertSame(2, $page['itemsPerPage'] ?? 2);
    }

    /**
     * The sections list the learners of the course like the learners table does: never the soft-deleted accounts
     * (a removed user must not come back in the time, activity and message reports of a teacher).
     */
    public function testTotalTimeLeavesSoftDeletedLearnersOut(): void
    {
        $alive = $this->learner('r2c3a');
        $gone = $this->learner('r2c3b');
        $this->conn()->executeStatement('UPDATE user SET active = -2 WHERE id = ?', [$gone]);

        $result = $this->section('total-time');

        $this->assertSame([$alive], array_column($result['items'], 'id'));
        $this->assertSame(1, $result['total']);
    }

    // ------------------------------------------------------------ activity

    public function testActivityGroupsConnectedLearnersByPeriodAndListsTheNeverConnectedOnes(): void
    {
        $recent = $this->learner('r2ac1');
        $lastWeek = $this->learner('r2ac2');
        $lastMonth = $this->learner('r2ac3');
        $old = $this->learner('r2ac4');
        $never = $this->learner('r2ac5');
        $this->visit($recent, $this->ago('2 hours'), $this->ago('1 hour'));
        $this->visit($recent, $this->ago('3 days'), $this->ago('3 days -10 minutes'));
        $this->visit($lastWeek, $this->ago('3 days'), null);
        $this->visit($lastMonth, $this->ago('20 days'), null);
        $this->visit($old, $this->ago('90 days'), null);

        $result = $this->section('activity', ['startDate' => (new DateTimeImmutable('-40 days'))->format('Y-m-d')]);

        $byKey = array_column($result['summary'], null, 'key');
        $this->assertSame(1, $byKey['last24Hours']['value']);
        $this->assertSame(2, $byKey['last7Days']['value']);
        $this->assertSame(3, $byKey['last30Days']['value']);
        $this->assertSame(3, $byKey['sinceStartDate']['value']);
        $this->assertSame(round(100 / 5, 1).'%', $byKey['last24Hours']['secondary']);
        $this->assertSame([$recent], array_column($result['items'], 'id'));
        $this->assertSame(1, $result['items'][0]['accesses']);
        $this->assertSame(3600, $result['items'][0]['totalTime']);
        $sections = array_column($result['sections'], null, 'key');
        $this->assertSame([$recent, $lastWeek], array_column($sections['last-week']['items'], 'id'), 'most recently connected first');
        $this->assertCount(3, $sections['last-month']['items']);
        $inactive = $sections['inactive']['items'];
        $inactiveIds = array_column($inactive, 'id');
        sort($inactiveIds);
        $this->assertSame([$old, $never], $inactiveIds);
        $byId = array_column($inactive, null, 'id');
        $this->assertSame(1, $byId[$old]['accesses'], 'the old visit still counts as an access');
        $this->assertSame(0, $byId[$never]['accesses']);
        $this->assertSame(5, $result['meta']['totalLearners']);
    }

    public function testActivityCountsToolAccessesSinceTheResetDate(): void
    {
        $a = $this->learner('r2ac6');
        $b = $this->learner('r2ac7');
        foreach ([[$a, 'document', '2001-05-01 10:00:00'], [$b, 'document', '2001-05-02 10:00:00'], [$a, 'document', '2001-05-03 10:00:00'], [$a, '', '2001-05-04 10:00:00'], [$a, 'forum', '2000-12-31 10:00:00']] as [$user, $tool, $date]) {
            $this->conn()->executeStatement(
                "INSERT INTO track_e_access (access_user_id, access_date, c_id, access_tool, session_id, user_ip) VALUES (?, ?, 1, ?, 0, '')",
                [$user, $date, $tool]
            );
        }

        $result = $this->section('activity', ['startDate' => '2001-01-01']);
        $resources = array_column(array_column($result['sections'], null, 'key')['resources']['items'], null, 'id');

        $this->assertSame(3, $resources['document']['accesses']);
        $this->assertSame(2, $resources['document']['users']);
        $this->assertSame('2001-05-03 10:00:00', $resources['document']['lastAccess']);
        $this->assertSame(1, $resources['unknown']['accesses'], 'an empty tool name is reported as unknown');
        $this->assertArrayNotHasKey('forum', $resources, 'before the reset date');
        $this->assertSame('2001-01-01', $result['meta']['startDate']);
        $this->assertSame('unknown', array_column(array_column($result['sections'], null, 'key')['resources']['items'], 'id')[1] ?? 'unknown', 'ties sorted by tool name');
    }

    // -------------------------------------------------------------- session

    public function testSessionReportOnlyExistsInsideASession(): void
    {
        $result = $this->section('session');
        $this->assertSame([], $result['items']);
        $this->assertSame(0, $result['total']);
    }

    public function testSessionReportGivesAccessesTimeProgressAndExerciseAverageOfEachSessionLearner(): void
    {
        $session = $this->rawSession('r2se1');
        $this->sessionCourse($session, 1);
        $learner = $this->rawUser('r2se1a');
        $statusCoach = $this->rawUser('r2se1c');
        $this->sessionCourseUser($session, 1, $learner, 0);
        $this->sessionCourseUser($session, 1, $statusCoach, 2);
        $this->visit($learner, '2001-01-01 10:00:00', '2001-01-01 10:10:00', 1, $session);
        $this->visit($learner, '2001-01-02 10:00:00', null, 1, $session);
        $this->visit($learner, '2001-01-03 10:00:00', '2001-01-03 12:00:00', 1, 0);
        $lp1 = $this->lp();
        $lp2 = $this->lp();
        $this->conn()->executeStatement('INSERT INTO c_lp_view (user_id, lp_id, c_id, session_id, view_count, last_item, progress) VALUES (?, ?, 1, ?, 1, 0, 20), (?, ?, 1, ?, 2, 0, 80), (?, ?, 1, ?, 1, 0, 40)', [$learner, $lp1, $session, $learner, $lp1, $session, $learner, $lp2, $session]);
        foreach ([[5, 10], [10, 10], [1, 0]] as [$score, $max]) {
            $this->conn()->executeStatement(
                "INSERT INTO track_e_exercises (exe_user_id, c_id, session_id, exe_exo_id, exe_date, score, max_score, user_ip, status, data_tracking,
                    start_date, steps_counter, orig_lp_id, orig_lp_item_id, exe_duration, orig_lp_item_view_id, questions_to_check)
                 VALUES (?, 1, ?, 1, '2001-01-01 10:00:00', ?, ?, '', '', '', '2001-01-01 09:00:00', 0, 0, 0, 0, 0, '')",
                [$learner, $session, $score, $max]
            );
        }
        $this->conn()->executeStatement(
            "INSERT INTO resource_link (resource_node_id, c_id, session_id, visibility, display_order, resource_type_group, created_at, updated_at)
             SELECT resource_node_id, 1, ?, 2, 0, 0, '2001-01-01 00:00:00', '2001-01-01 00:00:00' FROM c_lp WHERE iid IN (?, ?) AND resource_node_id IS NOT NULL",
            [$session, $lp1, $lp2]
        );

        $result = $this->section('session', [], $this->ctx(1, $session));

        $this->assertSame([$learner], array_column($result['items'], 'id'), 'course coaches are not learners');
        $row = $result['items'][0];
        $this->assertSame(2, $row['courseAccesses'], 'the visit outside the session does not count');
        $this->assertSame(600, $row['totalTime']);
        $this->assertSame(50.0, round((float) $row['exerciseAverage'], 2), '(50 + 100 + 0 for a zero maximum) / 3');
        $this->assertSame(0.0, (float) $row['learningPathProgress'], 'learning paths that are not linked to the course contribute nothing');
    }

    // ------------------------------------------------------------- messages

    private function message(int $from, int $to, string $title, string $date, string $content = 'hello'): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO message (user_sender_id, msg_type, status, send_date, title, content, mail_answer) VALUES (?, 1, 0, ?, ?, ?, 0)',
            [$from, $date, $title, $content]
        );
        $id = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement(
            'INSERT INTO message_rel_user (message_id, user_id, msg_read, receiver_type, starred) VALUES (?, ?, 0, 1, 0), (?, ?, 0, 8, 0)',
            [$id, $to, $id, $from]
        );

        return $id;
    }

    public function testMessagesNeedTheFeatureAndATeacherOrAdministrator(): void
    {
        try {
            $this->section('messages', [], $this->ctx(1, null, false));
            $this->fail('tracking disabled');
        } catch (AccessDeniedHttpException $e) {
            $this->assertSame('Message tracking is disabled.', $e->getMessage());
        }
        $em = $this->getEntityManager();
        $student = new CourseReportingContext(
            $em->find(Course::class, 1),
            null,
            0,
            $this->entityUser(1),
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            true,
            [],
            [],
            []
        );
        $this->expectException(AccessDeniedHttpException::class);
        $this->service->getSection($student, 'messages', []);
    }

    public function testMessagesAskForTwoLearnersFirstAndOfferTheLearnersOfTheCourse(): void
    {
        $a = $this->learner('r2m1');
        $result = $this->section('messages', ['userId' => $a]);
        $this->assertSame([], $result['items']);
        $this->assertTrue($result['meta']['requiresUsers']);
        $this->assertSame([$a], array_column($result['meta']['users'], 'value'));
        $this->assertStringContainsString('(rp_r2m1_', $result['meta']['users'][0]['label']);
    }

    public function testMessagesRefuseUsersWhoAreNotLearnersOfTheCourse(): void
    {
        $a = $this->learner('r2m2');
        $stranger = $this->rawUser('r2m3');
        $this->expectException(AccessDeniedHttpException::class);
        $this->section('messages', ['userId' => $a, 'peerUserId' => $stranger]);
    }
}
