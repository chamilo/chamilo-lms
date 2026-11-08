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

/**
 * Characterization of the participant list behind the course reporting learners table and overview
 * (CourseReportingQueryService::getLearners/getOverview). The participant source decides which accounts a course
 * teacher sees: enrolled learners (and teachers on demand) of that course or session only, never HR-relation
 * subscriptions, inactive or soft-deleted accounts. Authorisation (teacher/admin of the course) is the job of the
 * context resolver and the providers; the service trusts the context it is given.
 */
final class CourseReportingLearnersTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    private CourseReportingQueryService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(CourseReportingQueryService::class);
    }

    private function ctx(int $courseId = 1, ?int $sessionId = null, bool $email = false): CourseReportingContext
    {
        $this->fresh();
        $em = $this->getEntityManager();

        return new CourseReportingContext(
            $em->find(Course::class, $courseId),
            null === $sessionId ? null : $em->find(Session::class, $sessionId),
            0,
            $this->entityUser(1),
            true,
            true,
            false,
            $email,
            false,
            false,
            false,
            false,
            [],
            [],
            [],
        );
    }

    /**
     * @return int[]
     */
    private function ids(array $result): array
    {
        $ids = array_column($result['items'], 'id');
        sort($ids);

        return $ids;
    }

    private function learner(string $suffix, int $courseId = 1, int $active = 1, int $relationType = 0, int $status = 5): int
    {
        $id = $this->rawUser($suffix, 5, $active);
        $this->courseUser($id, $courseId, $status, $relationType);

        return $id;
    }

    public function testLearnersOfTheCourseAreListedWithoutTeachersByDefault(): void
    {
        $a = $this->learner('cl1a');
        $b = $this->learner('cl1b');
        $other = $this->learner('cl1c', 2);
        $teacher = $this->learner('cl1t', 1, 1, 0, 1);

        $result = $this->service->getLearners($this->ctx(), ['itemsPerPage' => 100]);
        $ids = $this->ids($result);
        $this->assertContains($a, $ids);
        $this->assertContains($b, $ids);
        $this->assertNotContains($other, $ids, 'a learner of another course never shows up');
        $this->assertNotContains($teacher, $ids);
        $this->assertSame(\count($result['items']), $result['total']);
        $this->assertSame('student', $result['items'][0]['role']);

        $withTeachers = $this->service->getLearners($this->ctx(), ['itemsPerPage' => 100, 'showTeachers' => '1']);
        $roles = array_column($withTeachers['items'], 'role', 'id');
        $this->assertSame('teacher', $roles[$teacher]);
        $this->assertSame('student', $roles[$a]);
    }

    public function testInactiveSoftDeletedAndHumanResourcesSubscriptionsAreLeftOut(): void
    {
        $ok = $this->learner('cl2a');
        $inactive = $this->learner('cl2b', 1, 0);
        $deleted = $this->learner('cl2c', 1, -2);
        $hr = $this->learner('cl2d', 1, 1, 1);
        $ids = $this->ids($this->service->getLearners($this->ctx(), ['itemsPerPage' => 100]));
        $this->assertContains($ok, $ids);
        foreach ([$inactive, $deleted, $hr] as $excluded) {
            $this->assertNotContains($excluded, $ids);
        }
    }

    public function testSessionContextListsTheSessionSubscriptionsOnly(): void
    {
        $s = $this->rawSession('cl3');
        $inSession = $this->rawUser('cl3a');
        $coach = $this->rawUser('cl3b');
        $courseOnly = $this->learner('cl3c');
        $otherSession = $this->rawSession('cl3o');
        $elsewhere = $this->rawUser('cl3d');
        $this->sessionCourseUser($s, 1, $inSession, 0);
        $this->sessionCourseUser($s, 1, $coach, 2);
        $this->sessionCourseUser($otherSession, 1, $elsewhere, 0);

        $students = $this->service->getLearners($this->ctx(1, $s), ['itemsPerPage' => 100]);
        $this->assertSame([$inSession], $this->ids($students));
        $all = $this->service->getLearners($this->ctx(1, $s), ['itemsPerPage' => 100, 'showTeachers' => true]);
        $this->assertSame(['teacher', 'student'], [
            array_column($all['items'], 'role', 'id')[$coach],
            array_column($all['items'], 'role', 'id')[$inSession],
        ]);
        $this->assertNotContains($courseOnly, $this->ids($all));
    }

    public function testKeywordSortAndPaging(): void
    {
        $this->conn()->executeStatement('UPDATE course_rel_user SET status = 1 WHERE c_id = 1 AND user_id = 1');
        $ids = [];
        foreach (['b', 'a', 'c'] as $i => $letter) {
            $ids[$letter] = $this->learner('kw_'.$letter);
            $this->conn()->executeStatement('UPDATE user SET lastname = ? WHERE id = ?', ['Kw'.$letter, $ids[$letter]]);
        }
        $filters = ['keyword' => 'Kw', 'itemsPerPage' => 2, 'sort' => 'lastname'];
        $page1 = $this->service->getLearners($this->ctx(), $filters);
        $this->assertSame(3, $page1['total']);
        $this->assertSame([$ids['a'], $ids['b']], array_column($page1['items'], 'id'));
        $page2 = $this->service->getLearners($this->ctx(), $filters + ['page' => 2]);
        $this->assertSame([$ids['c']], array_column($page2['items'], 'id'));
        $desc = $this->service->getLearners($this->ctx(), ['keyword' => 'Kw', 'sort' => 'lastname', 'direction' => 'desc']);
        $this->assertSame([$ids['c'], $ids['b'], $ids['a']], array_column($desc['items'], 'id'));
        // Unknown sort key falls back to the last name.
        $bad = $this->service->getLearners($this->ctx(), ['keyword' => 'Kw', 'sort' => '1; DROP TABLE user']);
        $this->assertSame([$ids['a'], $ids['b'], $ids['c']], array_column($bad['items'], 'id'));
        $this->assertSame(0, $this->service->getLearners($this->ctx(), ['keyword' => 'zz-nobody-zz'])['total']);
    }

    public function testEmailIsHiddenUnlessTheContextShowsIt(): void
    {
        $a = $this->learner('em1');
        $hidden = $this->service->getLearners($this->ctx(), ['keyword' => 'rp_em1_']);
        $this->assertSame('', $hidden['items'][0]['email']);
        $shown = $this->service->getLearners($this->ctx(1, null, true), ['keyword' => 'rp_em1_']);
        $this->assertStringEndsWith('@example.com', $shown['items'][0]['email']);
        $this->assertSame($a, $shown['items'][0]['id']);
    }

    public function testActiveUsersOptionAddsVisitorsThatAreNotEnrolled(): void
    {
        $enrolled = $this->learner('ac1');
        $visitor = $this->rawUser('ac2');
        $deletedVisitor = $this->rawUser('ac3', 5, -2);
        foreach ([$enrolled, $visitor, $deletedVisitor] as $uid) {
            $this->conn()->executeStatement(
                "INSERT INTO track_e_course_access (user_id, c_id, login_course_date, logout_course_date, counter, session_id, user_ip)
                 VALUES (?, 1, '2001-01-01 10:00:00', NULL, 1, 0, '')",
                [$uid]
            );
        }
        $without = $this->ids($this->service->getLearners($this->ctx(), ['itemsPerPage' => 100]));
        $this->assertNotContains($visitor, $without);
        $with = $this->service->getLearners($this->ctx(), ['itemsPerPage' => 100, 'showActiveUsers' => '1', 'showTeachers' => '1']);
        $roles = array_column($with['items'], 'role', 'id');
        $this->assertSame('active', $roles[$visitor]);
        $this->assertSame('student', $roles[$enrolled], 'an enrolled visitor is listed once, as a student');
        $this->assertArrayNotHasKey($deletedVisitor, $roles);
        $this->assertSame(1, array_count_values(array_column($with['items'], 'id'))[$enrolled]);
    }

    public function testOverviewCountsTheEnrolledLearnersAndDistributesTheirScores(): void
    {
        $before = $this->service->getOverview($this->ctx());
        $this->learner('ov1a');
        $this->learner('ov1b');
        $this->learner('ov1c', 1, -2);
        $after = $this->service->getOverview($this->ctx());
        $this->assertSame($before['numberStudents'] + 2, $after['numberStudents']);
        $this->assertSame($after['numberStudents'], array_sum($after['scoreDistribution']));
        $this->assertCount(10, $after['scoreDistribution']);
        $this->assertSame($before['scoreDistribution'][0] + 2, $after['scoreDistribution'][0], 'no exercise: bucket 0');
        $this->assertLessThanOrEqual(5, \count($after['topStudents']));
        $this->assertCount($after['numberStudents'], $after['timeStudents']);
    }

    public function testOverviewOfACourseWithoutLearnersIsZeroed(): void
    {
        $this->conn()->executeStatement('DELETE FROM course_rel_user WHERE c_id = 2');
        $overview = $this->service->getOverview($this->ctx(2));
        $this->assertSame(0, $overview['numberStudents']);
        $this->assertSame(0.0, $overview['exerciseAverage']);
        $this->assertSame([], $overview['topStudents']);
    }
}
