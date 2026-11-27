<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingContext;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingSectionQueryService;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use const SORT_STRING;

/**
 * Characterization of the learning-path-authors, learning-path-item-authors and works-in-session sections of
 * GlobalReportingSectionQueryService. Fixtures are "r3_" resources and "rp_" users, rolled back with the
 * transaction. The installer courses 1 and 2 are both on access URL 1.
 */
final class GlobalReportingAuthorsWorksTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use GenericRowInsertTrait;
    use RawReportingFixturesTrait;
    use ReportingResourceFixturesTrait;

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
        Database::setManager($this->getEntityManager());
        Database::setConnection($this->getEntityManager()->getConnection());
        $this->service = self::getContainer()->get(GlobalReportingSectionQueryService::class);
    }

    private function ctx(int $userId, bool $admin = false, bool $hr = false, int $urlId = 1): GlobalReportingContext
    {
        $this->fresh();

        return new GlobalReportingContext(
            $this->entityUser($userId),
            $urlId,
            $admin,
            $hr,
            false,
            true,
            false,
            false,
            false,
            false,
            false,
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

    private function lpBy(string $title, string $author, int $courseId = 1, ?int $sessionId = null): int
    {
        $lp = $this->newLp($title, $courseId, $sessionId);
        $this->conn()->executeStatement('UPDATE c_lp SET author = ? WHERE iid = ?', [$author, $lp]);

        return $lp;
    }

    // -------------------------------------------------- learning path authors

    public function testLearningPathAuthorsCountThePathsOfTheCoursesOfTheCurrentUrl(): void
    {
        $this->conn()->executeStatement('DELETE FROM access_url_rel_course WHERE c_id = 2');
        $before = array_column($this->section($this->ctx(1, true), 'learning-path-authors')['items'], 'items', 'author');
        $this->lpBy('r3_a1', ' R3 Author One ');
        $this->lpBy('r3_a2', 'R3 Author One');
        $this->lpBy('r3_a3', '');
        $this->lpBy('r3_a5', 'R3 Author Hidden', 2);

        $result = $this->section($this->ctx(1, true), 'learning-path-authors');

        $byAuthor = array_column($result['items'], null, 'author');
        $this->assertSame(2, $byAuthor['R3 Author One']['items'], 'authors are trimmed before grouping');
        $this->assertSame('AI Act', $byAuthor['R3 Author One']['courses']);
        $this->assertSame(($before['Unknown'] ?? 0) + 1, $byAuthor['Unknown']['items'], 'an empty author is "Unknown"');
        $this->assertArrayNotHasKey('R3 Author Hidden', $byAuthor, 'courses of other URLs are out of scope');
        $authors = array_column($result['items'], 'author');
        $sorted = $authors;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $authors, 'alphabetical');
    }

    public function testLearningPathCountsEachPathOnceEvenWhenItIsLinkedToSeveralContexts(): void
    {
        $lp = $this->lpBy('r3_multi', 'R3 Multi Author');
        $session = $this->rawSession('r3mu1');
        $node = (int) $this->conn()->fetchOne('SELECT resource_node_id FROM c_lp WHERE iid = ?', [$lp]);
        $this->link($node, 1, $session);
        $this->lpBy('r3_single', 'R3 Multi Author');

        $row = array_column($this->section($this->ctx(1, true), 'learning-path-authors')['items'], null, 'author')['R3 Multi Author'];

        $this->assertSame(2, $row['items'], 'two learning paths, one of them linked twice');
    }

    /**
     * PINNED QUIRK: the section itself accepts human resources managers (assertAdministratorOrHumanResources), but the
     * outer section gate lists it as administrator-only, so the HR branch is unreachable. Kept: the stricter gate wins.
     */
    public function testLearningPathAuthorsAreAdministratorOnlyInPractice(): void
    {
        $hr = $this->rawUser('r3ah1', 4);
        $teacher = $this->rawUser('r3ah2', 1);

        foreach ([$this->ctx($hr, false, true), $this->ctx($teacher)] as $ctx) {
            try {
                $this->section($ctx, 'learning-path-authors');
                $this->fail('served to a non administrator');
            } catch (AccessDeniedHttpException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testLearningPathItemAuthorsGroupTheItemsByTheAuthorExtraField(): void
    {
        $this->conn()->executeStatement('DELETE FROM extra_field_values WHERE field_id IN (SELECT id FROM extra_field WHERE variable = \'authorlpitem\')');
        $this->conn()->executeStatement('DELETE FROM extra_field WHERE variable = \'authorlpitem\'');
        $this->insert('extra_field', ['item_type' => 0, 'value_type' => 1, 'variable' => 'authorlpitem']);
        $field = (int) $this->conn()->lastInsertId();
        $lp = $this->lpBy('r3_items', 'someone');
        $items = [];
        foreach ([' R3 Item Author ', 'R3 Item Author', null] as $i => $author) {
            $this->insert('c_lp_item', ['lp_id' => $lp, 'title' => 'r3_item'.$i, 'item_type' => 'document', 'ref' => '', 'path' => '', 'launch_data' => '']);
            $items[] = (int) $this->conn()->lastInsertId();
            if (null !== $author) {
                $this->insert('extra_field_values', ['field_id' => $field, 'item_id' => $items[$i], 'field_value' => $author]);
            }
        }

        $result = $this->section($this->ctx(1, true), 'learning-path-item-authors');

        $byAuthor = array_column($result['items'], null, 'author');
        $this->assertSame(2, $byAuthor['R3 Item Author']['items']);
        $this->assertGreaterThanOrEqual(1, $byAuthor['Unknown']['items'], 'items without a value');
    }

    // ------------------------------------------------------ works in session

    /**
     * A work sent by a learner: a child of the assignment, linked to the course and session.
     */
    private function submission(int $userId, int $courseId, int $sessionId, string $sentAt, ?int $parent): void
    {
        $node = $this->node('student_publications', 'r3_work_'.$userId.'_'.$sentAt);
        $this->insert('c_student_publication', ['resource_node_id' => $node, 'parent_id' => $parent, 'user_id' => $userId, 'title' => 'r3_work', 'sent_date' => $sentAt]);
        $this->link($node, $courseId, $sessionId);
    }

    private function assignment(): int
    {
        $this->insert('c_student_publication', ['title' => 'r3_assignment']);

        return (int) $this->conn()->lastInsertId();
    }

    private function courseCode(int $courseId): string
    {
        return (string) $this->conn()->fetchOne('SELECT code FROM course WHERE id = ?', [$courseId]);
    }

    /**
     * Without a session the 1.11 page only shows the session selector: nothing is computed until one is chosen,
     * and only sessions in the user's scope can be chosen.
     */
    public function testWorksInSessionAsksForASessionOfTheScope(): void
    {
        $inUrl = $this->rawSession('r3ws1');
        $foreign = $this->rawSession('r3ws2', null);
        $admin = $this->ctx(1, true);

        $result = $this->section($admin, 'works-in-session');

        $this->assertSame(0, $result['total']);
        $this->assertSame([], $result['items']);
        $this->assertTrue($result['meta']['requiresSession']);
        $options = array_column($result['meta']['sessionOptions'], 'id');
        $this->assertContains($inUrl, $options);
        $this->assertNotContains($foreign, $options, 'sessions outside the URL');

        $this->expectException(AccessDeniedHttpException::class);
        $this->section($admin, 'works-in-session', ['sessionId' => $foreign]);
    }

    /**
     * 1.11 layout: one row per learner of the session, three columns per course (best score, progress and last
     * sent work date). A learner of the session who is not subscribed to a course has empty values for it.
     */
    public function testWorksInSessionShowsOneRowPerLearnerWithThreeColumnsPerCourse(): void
    {
        $session = $this->rawSession('r3ws3');
        $this->sessionCourse($session, 1);
        $this->sessionCourse($session, 2);
        $inCourse = $this->rawUser('r3ws4');
        $onlySession = $this->rawUser('r3ws5');
        $hr = $this->rawUser('r3ws6', 4);
        $this->conn()->executeStatement('UPDATE user SET official_code = ? WHERE id = ?', ['R3CODE', $inCourse]);
        $this->sessionUser($session, $inCourse, 0);
        $this->sessionUser($session, $onlySession, 0);
        $this->sessionUser($session, $hr, 1);
        $this->sessionCourseUser($session, 1, $inCourse);
        $assignment = $this->assignment();
        $this->submission($inCourse, 1, $session, '2001-04-01 10:00:00', $assignment);
        $this->submission($inCourse, 1, $session, '2001-04-02 10:00:00', $assignment);
        $this->submission($inCourse, 1, $session, '2001-04-03 10:00:00', null);
        $this->insert('track_e_login', ['login_user_id' => $inCourse, 'login_date' => '2001-01-01 10:00:00', 'logout_date' => '2001-01-01 10:30:00', 'access_url_id' => 1]);
        $this->insert('track_e_login', ['login_user_id' => $inCourse, 'login_date' => '2001-01-05 10:00:00', 'logout_date' => '2001-01-05 10:10:00', 'access_url_id' => 1]);

        $result = $this->section($this->ctx(1, true), 'works-in-session', ['sessionId' => $session]);

        $this->assertSame(
            ['code', 'learner', 'timeSpent', 'firstLogin', 'lastLogin', 'score1', 'progress1', 'lastSentDate1', 'score2', 'progress2', 'lastSentDate2'],
            array_column($result['columns'], 'key'),
        );
        $this->assertSame($this->courseCode(1), $result['columns'][5]['prefix'], 'the course code heads its columns');
        $this->assertSame(
            [$this->courseCode(1), $this->courseCode(1), $this->courseCode(1), $this->courseCode(2)],
            array_column(\array_slice($result['columns'], 5, 4), 'group'),
            'the page groups the three columns of each course under it',
        );
        $this->assertArrayNotHasKey('group', $result['columns'][4], 'the learner columns belong to no course');
        $this->assertSame(2, $result['total'], 'learners only, not the human resources manager of the session');
        $rows = array_column($result['items'], null, 'id');
        $row = $rows[$inCourse];
        $this->assertSame('R3CODE', $row['code']);
        $this->assertSame(2400, $row['timeSpent']);
        $this->assertSame('2001-01-01 10:00:00', $row['firstLogin']);
        $this->assertSame('2001-01-05 10:00:00', $row['lastLogin']);
        $this->assertSame('2001-04-02 10:00:00', $row['lastSentDate1'], 'the latest work, the assignment itself excluded');
        $this->assertIsFloat($row['progress1']);
        $this->assertNull($row['progress2'], 'not subscribed to course 2');
        $this->assertNull($row['lastSentDate2']);
        foreach (['score1', 'progress1', 'lastSentDate1'] as $key) {
            $this->assertNull($rows[$onlySession][$key], 'a learner of the session only has no course values');
        }
    }

    /**
     * Owner rule: a course coach is shown the courses he coaches and their learners, not every course of the
     * session. A general coach coaches the whole session, so he still sees every course.
     */
    public function testWorksInSessionOfACourseCoachIsLimitedToTheCoursesHeCoaches(): void
    {
        $session = $this->rawSession('r3ws7');
        $theirs = $this->rawSession('r3ws8');
        $this->sessionCourse($session, 1);
        $this->sessionCourse($session, 2);
        $courseCoach = $this->rawUser('r3ws9', 1);
        $generalCoach = $this->rawUser('r3ws10', 1);
        $learner1 = $this->rawUser('r3ws11');
        $learner2 = $this->rawUser('r3ws12');
        $this->sessionCourseUser($session, 1, $courseCoach, 2);
        $this->sessionUser($session, $generalCoach, 3);
        foreach ([$learner1, $learner2] as $learner) {
            $this->sessionUser($session, $learner, 0);
        }
        $this->sessionCourseUser($session, 1, $learner1);
        $this->sessionCourseUser($session, 2, $learner2);

        $course = $this->section($this->ctx($courseCoach), 'works-in-session', ['sessionId' => $session]);
        $this->assertSame(['score1', 'progress1', 'lastSentDate1'], \array_slice(array_column($course['columns'], 'key'), 5));
        $this->assertSame([$learner1], array_column($course['items'], 'id'));

        $general = $this->section($this->ctx($generalCoach), 'works-in-session', ['sessionId' => $session]);
        $this->assertCount(11, $general['columns']);
        $ids = array_column($general['items'], 'id');
        sort($ids);
        $this->assertSame([$learner1, $learner2], $ids);

        $this->expectException(AccessDeniedHttpException::class);
        $this->section($this->ctx($courseCoach), 'works-in-session', ['sessionId' => $theirs]);
    }
}
