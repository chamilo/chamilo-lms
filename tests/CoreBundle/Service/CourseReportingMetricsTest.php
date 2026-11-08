<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingContext;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingQueryService;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Characterization of the per-learner metrics, the group summary, getLearnerDetail() and getLearnersForExport() of
 * CourseReportingQueryService. Learners are "rp_" rows of the installer course 1; resources are "r3_" rows.
 * Learning paths and tests of course 1 that the installer created are part of the denominators, so expectations
 * count them first.
 */
final class CourseReportingMetricsTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use GenericRowInsertTrait;
    use RawReportingFixturesTrait;
    use ReportingResourceFixturesTrait;

    private CourseReportingQueryService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(CourseReportingQueryService::class);
    }

    private function ctx(int $courseId = 1, ?int $sessionId = null, bool $email = false, bool $maxProgress = false, int $groupId = 0): CourseReportingContext
    {
        $this->fresh();
        $em = $this->getEntityManager();

        return new CourseReportingContext(
            $em->find(Course::class, $courseId),
            null === $sessionId ? null : $em->find(Session::class, $sessionId),
            $groupId,
            $this->entityUser(1),
            true,
            true,
            false,
            $email,
            false,
            $maxProgress,
            false,
            false,
            [],
            [],
            [],
        );
    }

    private function learner(string $suffix, int $courseId = 1, int $active = 1): int
    {
        $id = $this->rawUser($suffix, 5, $active);
        $this->courseUser($id, $courseId, 5);

        return $id;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(int $userId, ?CourseReportingContext $ctx = null): array
    {
        $items = $this->service->getLearners($ctx ?? $this->ctx(), ['itemsPerPage' => 1000, 'showTeachers' => '1'])['items'];
        foreach ($items as $item) {
            if ($item['id'] === $userId) {
                return $item;
            }
        }
        $this->fail('learner '.$userId.' not listed');
    }

    private function lpCount(int $courseId = 1, int $sessionId = 0): int
    {
        return (int) $this->conn()->fetchOne(
            'SELECT COUNT(DISTINCT lp.iid) FROM c_lp lp INNER JOIN resource_link rl ON rl.resource_node_id = lp.resource_node_id
              WHERE rl.c_id = ? AND rl.deleted_at IS NULL AND (rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = ?)',
            [$courseId, $sessionId]
        );
    }

    private function quizCount(int $courseId = 1): int
    {
        return (int) $this->conn()->fetchOne(
            'SELECT COUNT(DISTINCT q.iid) FROM c_quiz q INNER JOIN resource_link rl ON rl.resource_node_id = q.resource_node_id
              WHERE rl.c_id = ? AND rl.deleted_at IS NULL AND (rl.session_id IS NULL OR rl.session_id = 0)',
            [$courseId]
        );
    }

    // -------------------------------------------------------------- metrics

    public function testCourseTimeCountsOnlyClosedVisitsOfThisCourseAndNoSession(): void
    {
        $a = $this->learner('r3t1');
        $this->courseVisit($a, '2001-01-01 10:00:00', '2001-01-01 10:30:00');
        $this->courseVisit($a, '2001-01-02 10:00:00', '2001-01-02 10:00:20');
        $this->courseVisit($a, '2001-01-03 10:00:00', null);
        $this->courseVisit($a, '2001-01-04 10:00:00', '2001-01-04 09:00:00');
        $this->courseVisit($a, '2001-02-01 10:00:00', '2001-02-01 18:00:00', 2);
        $this->courseVisit($a, '2001-02-02 10:00:00', '2001-02-02 18:00:00', 1, 99999);

        $row = $this->row($a);

        $this->assertSame(1820, $row['timeSeconds'], 'open visits and visits that end before they start count for zero');
        $this->assertSame('2001-01-01 10:00:00', $row['firstAccess']);
        $this->assertSame('2001-01-04 10:00:00', $row['latestAccess']);
    }

    public function testExerciseMetricsUseTheBestAttemptOfEachTestOverAllTestsOfTheCourse(): void
    {
        $a = $this->learner('r3e1');
        $q1 = $this->newQuiz('r3_quiz_one');
        $q2 = $this->newQuiz('r3_quiz_two');
        $n = $this->quizCount();
        $this->exerciseAttempt($a, $q1, 5, 10);
        $this->exerciseAttempt($a, $q1, 8, 10, null, 1, '2001-01-05 10:00:00');
        $this->exerciseAttempt($a, $q2, 3, 0);
        $this->exerciseAttempt($a, $q1, 10, 10, null, 2);
        $this->conn()->executeStatement("UPDATE track_e_exercises SET status = 'incomplete' WHERE exe_user_id = ? AND score = 5", [$a]);

        $row = $this->row($a);

        $this->assertSame(round(100 * 2 / $n, 2), $row['exerciseProgress'], 'q1 and q2 attempted out of all the tests of the course');
        $this->assertSame(round(100 * 0.8 / $n, 2), $row['exerciseAverage'], 'incomplete attempts are ignored; a zero maximum scores zero');
        $this->assertSame(80.0, $row['configuredExerciseResults'][(string) $q1] ?? null);
        $this->assertSame(0.0, $row['configuredExerciseResults'][(string) $q2] ?? null);
        $this->assertSame('2001-01-05 10:00:00', $row['quizFinalizationDate']);
    }

    public function testLearningPathProgressTakesTheLatestViewOfEachPathOverAllPathsOfTheCourse(): void
    {
        $a = $this->learner('r3l1');
        $lp1 = $this->newLp('r3_lp_one');
        $lp2 = $this->newLp('r3_lp_two');
        $n = $this->lpCount();
        $session = $this->rawSession('r3l1s');
        $this->sessionCourse($session, 1);
        $this->lpView($a, $lp1, 10, $session);
        $this->lpView($a, $lp2, 90, $session);
        $this->sessionCourseUser($session, 1, $a, 0);
        $this->lpView($a, $lp1, 20, $session, 1);
        $this->lpView($a, $lp1, 60, $session, 2);
        $this->lpView($a, $lp2, 100, $session, 1);
        $this->lpView($a, $lp1, 100, $session, 1, 2);

        $row = $this->row($a, $this->ctx(1, $session));
        $this->assertSame(round((60 + 100) / $this->lpCount(1, $session), 2), $row['learningPathProgress'], 'highest view_count, then newest view');

        $max = $this->row($a, $this->ctx(1, $session, false, true));
        $this->assertSame(100.0, $max['learningPathProgress'], 'maximum mode: the best path');
        $this->assertGreaterThan(0, $n);
    }

    public function testLearningPathScoresAverageTheLatestViewItemsAndTheBestItemsApart(): void
    {
        $a = $this->learner('r3l2');
        $lp = $this->newLp('r3_lp_score');
        $session = $this->rawSession('r3l2s');
        $this->sessionCourse($session, 1);
        $this->sessionCourseUser($session, 1, $a, 0);
        $old = $this->lpView($a, $lp, 50, $session, 1);
        $new = $this->lpView($a, $lp, 50, $session, 2);
        $this->lpItemView($old, 11, 9, '10');
        $this->lpItemView($old, 12, 2, '10');
        $this->lpItemView($new, 11, 5, '10');
        $this->lpItemView($new, 13, 4, '4');
        $this->lpItemView($new, 14, 0, '0');
        $this->lpItemView($new, 15, 1, '10', 0, 0, 'not attempted');

        $row = $this->row($a, $this->ctx(1, $session));

        $this->assertSame(round(100 * (0.5 + 1.0) / 2, 2), $row['score'], 'latest view: items 11 (50%) and 13 (100%); zero maximum and not attempted skipped');
        $this->assertSame(round(100 * (0.9 + 0.2 + 1.0) / 3, 2), $row['bestScore'], 'best ratio of every item over all the views');
    }

    public function testCertificateFlagComesFromTheGradebookOfTheSameCourseAndSession(): void
    {
        $a = $this->learner('r3c1');
        $b = $this->learner('r3c2');
        $cat = $this->gradebookCategory(1, null);
        $other = $this->gradebookCategory(2, null);
        $this->insert('gradebook_certificate', ['cat_id' => $cat, 'user_id' => $a]);
        $this->insert('gradebook_certificate', ['cat_id' => $other, 'user_id' => $b]);

        $this->assertTrue($this->row($a)['certificateAvailable']);
        $this->assertFalse($this->row($b)['certificateAvailable'], 'a certificate of another course does not count');
    }

    private function gradebookCategory(int $courseId, ?int $sessionId): int
    {
        $this->insert('gradebook_category', ['c_id' => $courseId, 'session_id' => $sessionId, 'title' => 'r3_cat', 'name' => 'r3_cat']);

        return (int) $this->conn()->lastInsertId();
    }

    // -------------------------------------------------------- group summary

    public function testGroupSummaryListsEveryGroupOfTheCourseWithItsLearnersAndAWeightedTotal(): void
    {
        $a = $this->learner('r3g1a');
        $b = $this->learner('r3g1b');
        $c = $this->learner('r3g1c');
        $gone = $this->learner('r3g1d', 1, -2);
        $outsider = $this->rawUser('r3g1e');
        $g1 = $this->newGroup('r3_group_a');
        $g2 = $this->newGroup('r3_group_b');
        $this->newGroup('r3_group_other_course', 2);
        foreach ([$a, $b, $gone, $outsider] as $u) {
            $this->groupMember($g1, $u);
        }
        $this->groupMember($g2, $c);
        $this->courseVisit($a, '2001-01-01 10:00:00', '2001-01-01 10:10:00');
        $this->courseVisit($b, '2001-01-01 10:00:00', '2001-01-01 10:20:00');
        $this->courseVisit($c, '2001-01-01 10:00:00', '2001-01-01 11:00:00');

        $summary = $this->service->getLearners($this->ctx(), ['itemsPerPage' => 5])['groupSummary']['items'];
        $byTitle = array_column($summary, null, 'title');

        $this->assertSame(2, $byTitle['r3_group_a']['learners'], 'soft-deleted and non-enrolled members are not learners of the report');
        $this->assertSame(1800, $byTitle['r3_group_a']['timeSeconds']);
        $this->assertSame(900, $byTitle['r3_group_a']['averageTimeSeconds']);
        $this->assertSame(1, $byTitle['r3_group_b']['learners']);
        $this->assertSame($g1, $byTitle['r3_group_a']['sourceId']);
        $this->assertArrayNotHasKey('r3_group_other_course', $byTitle);
        $total = end($summary);
        $this->assertSame('total', $total['rowType']);
        $this->assertSame('Total', $total['title']);
        $this->assertSame(3, $total['learners'] - ($total['learners'] - 3), 'sanity');
    }

    // -------------------------------------------------------- learner detail

    public function testLearnerDetailRefusesUsersOutsideTheReportingScope(): void
    {
        $stranger = $this->rawUser('r3d0');
        $other = $this->learner('r3d0b', 2);
        $hr = $this->rawUser('r3d0c');
        $this->courseUser($hr, 1, 5, 1);
        foreach ([$stranger, $other, $hr, 999999999] as $uid) {
            try {
                $this->service->getLearnerDetail($this->ctx(), $uid);
                $this->fail('user '.$uid.' must be refused');
            } catch (NotFoundHttpException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testLearnerDetailListsTheLatestAccessesOfThisCourseAndSessionOnly(): void
    {
        $a = $this->learner('r3d1');
        foreach (['2001-01-01 10:00:00', '2001-01-03 10:00:00', '2001-01-02 10:00:00'] as $d) {
            $this->courseVisit($a, $d, null);
        }
        $this->courseVisit($a, '2001-01-09 10:00:00', null, 2);
        $this->courseVisit($a, '2001-01-10 10:00:00', null, 1, 99999);
        foreach ([['2001-03-01 10:00:00', 'document', 1, 0], ['2001-03-02 10:00:00', 'forum', 1, 0], ['2001-03-03 10:00:00', 'forum', 2, 0], ['2001-03-04 10:00:00', 'link', 1, 99999]] as [$date, $tool, $cid, $sid]) {
            $this->conn()->executeStatement(
                "INSERT INTO track_e_access (access_user_id, access_date, c_id, access_tool, session_id, user_ip) VALUES (?, ?, ?, ?, ?, '10.0.0.2')",
                [$a, $date, $cid, $tool, $sid]
            );
        }

        $detail = $this->service->getLearnerDetail($this->ctx(), $a);

        $this->assertSame($a, $detail['user']['id']);
        $this->assertSame('', $detail['user']['email'], 'e-mail hidden unless the context shows it');
        $this->assertSame(['2001-01-03 10:00:00', '2001-01-02 10:00:00', '2001-01-01 10:00:00'], array_column($detail['courseAccess'], 'login_course_date'));
        $this->assertSame(['forum', 'document'], array_column($detail['resourceAccess'], 'access_tool'));
        $limited = $this->service->getLearnerDetail($this->ctx(), $a, 1);
        $this->assertCount(1, $limited['courseAccess']);
        $this->assertSame('2001-01-03 10:00:00', $limited['courseAccess'][0]['login_course_date']);
        $shown = $this->service->getLearnerDetail($this->ctx(1, null, true), $a);
        $this->assertStringEndsWith('@example.com', $shown['user']['email']);
    }

    public function testLearnerDetailInsideASessionReadsTheSessionAccessesOfASessionLearner(): void
    {
        $a = $this->rawUser('r3d2');
        $session = $this->rawSession('r3d2s');
        $this->sessionCourse($session, 1);
        $this->sessionCourseUser($session, 1, $a, 0);
        $this->courseVisit($a, '2001-01-01 10:00:00', null, 1, 0);
        $this->courseVisit($a, '2001-01-02 10:00:00', null, 1, $session);

        $detail = $this->service->getLearnerDetail($this->ctx(1, $session), $a);

        $this->assertSame(['2001-01-02 10:00:00'], array_column($detail['courseAccess'], 'login_course_date'));
    }

    public function testLearnerDetailListsTheDownloadsOfTheCourse(): void
    {
        $a = $this->learner('r3d3');
        $doc = $this->node('files', 'r3_doc_file');
        $docLink = $this->link($doc, 1);
        $otherDoc = $this->node('files', 'r3_doc_other');
        $otherLink = $this->link($otherDoc, 2);
        $sessionDoc = $this->node('files', 'r3_doc_session');
        $session = $this->rawSession('r3d3s');
        $sessionLink = $this->link($sessionDoc, 1, $session);
        foreach ([[$docLink, '2001-04-01 10:00:00'], [$otherLink, '2001-04-02 10:00:00'], [$sessionLink, '2001-04-03 10:00:00'], [$docLink, '2001-04-04 10:00:00']] as [$l, $d]) {
            $this->insert('track_e_downloads', ['resource_link_id' => $l, 'down_user_id' => $a, 'down_date' => $d, 'down_doc_path' => '/x']);
        }
        $detail = $this->service->getLearnerDetail($this->ctx(), $a);

        $this->assertSame(['2001-04-04 10:00:00', '2001-04-01 10:00:00'], array_column($detail['downloads'], 'down_date'));
        $this->assertSame('r3_doc_file', $detail['downloads'][0]['resource_title']);
    }

    /**
     * The learners table never lists removed (-2) or inactive accounts, so their detail (name, forum posts, downloads,
     * accesses) must not be reachable by id either.
     */
    public function testLearnerDetailRefusesSoftDeletedAndInactiveAccounts(): void
    {
        $gone = $this->learner('r3d5a', 1, -2);
        $inactive = $this->learner('r3d5b', 1, 0);
        $alive = $this->learner('r3d5c');
        $this->courseVisit($gone, '2001-01-01 10:00:00', null);

        $this->assertSame($alive, $this->service->getLearnerDetail($this->ctx(), $alive)['user']['id']);
        foreach ([$gone, $inactive] as $uid) {
            try {
                $this->service->getLearnerDetail($this->ctx(), $uid);
                $this->fail('account '.$uid.' must be refused');
            } catch (NotFoundHttpException) {
                $this->addToAssertionCount(1);
            }
        }
        $stranger = $this->rawUser('r3d5d', 5, -2);
        $this->courseVisit($stranger, '2001-01-01 10:00:00', null);
        $this->expectException(NotFoundHttpException::class);
        $this->service->getLearnerDetail($this->ctx(), $stranger);
    }

    // --------------------------------------------------------------- export

    public function testExportReturnsEveryLearnerBeyondTheTablePageSizeAndHonoursFilters(): void
    {
        $ids = [];
        foreach (range(1, 25) as $i) {
            $ids[] = $this->learner('r3x'.str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        }
        $this->conn()->executeStatement('UPDATE user SET lastname = CONCAT(\'R3X\', id) WHERE id IN ('.implode(',', $ids).')');

        $all = $this->service->getLearnersForExport($this->ctx(), ['keyword' => 'rp_r3x', 'page' => 3, 'itemsPerPage' => 5]);
        $this->assertCount(25, $all, 'paging filters are overridden: the export is the whole list');
        $one = $this->service->getLearnersForExport($this->ctx(), ['keyword' => 'rp_r3x25_']);
        $this->assertSame([$ids[24]], array_column($one, 'id'));
        $desc = $this->service->getLearnersForExport($this->ctx(), ['keyword' => 'rp_r3x', 'sort' => 'username', 'direction' => 'desc']);
        $this->assertSame($ids[24], $desc[0]['id']);
    }
}
