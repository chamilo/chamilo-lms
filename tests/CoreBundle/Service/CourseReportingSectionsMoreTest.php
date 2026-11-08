<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingContext;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingSectionQueryService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Characterization of the groups, resources, tools, exams, audit and learning-paths sections of
 * CourseReportingSectionQueryService::getSection(). Learners are "rp_" rows of the installer course 1,
 * resources are "r3_" rows with fixed timestamps.
 */
final class CourseReportingSectionsMoreTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use GenericRowInsertTrait;
    use RawReportingFixturesTrait;
    use ReportingResourceFixturesTrait;

    private CourseReportingSectionQueryService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(CourseReportingSectionQueryService::class);
    }

    private function ctx(int $courseId = 1, ?int $sessionId = null, int $groupId = 0): CourseReportingContext
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
            false,
            false,
            false,
            false,
            true,
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

    private function learner(string $tag, int $courseId = 1, int $active = 1): int
    {
        $id = $this->rawUser($tag, 5, $active);
        $this->courseUser($id, $courseId, 5);

        return $id;
    }

    // --------------------------------------------------------------- groups

    public function testGroupsListTheGroupsOfTheCourseByTitleWithMembersAndTime(): void
    {
        $a = $this->learner('r3gr1a');
        $b = $this->learner('r3gr1b');
        $g2 = $this->newGroup('r3_grp_b');
        $g1 = $this->newGroup('r3_grp_a');
        $this->newGroup('r3_grp_other_course', 2);
        $session = $this->rawSession('r3gr1s');
        $this->newGroup('r3_grp_session', 1, $session);
        $removed = $this->newGroup('r3_grp_removed');
        $this->conn()->executeStatement('UPDATE resource_link SET deleted_at = \'2001-01-01 00:00:00\' WHERE resource_node_id = (SELECT resource_node_id FROM c_group_info WHERE iid = ?)', [$removed]);
        $this->groupMember($g1, $a);
        $this->groupMember($g1, $b);
        $this->groupMember($g2, $b);
        $this->courseVisit($a, '2001-01-01 10:00:00', '2001-01-01 10:10:00');
        $this->courseVisit($b, '2001-01-01 10:00:00', '2001-01-01 10:20:00');
        $this->courseVisit($b, '2001-01-02 10:00:00', null);
        $this->courseVisit($b, '2001-01-03 10:00:00', '2001-01-03 11:00:00', 1, $session);

        $result = $this->section('groups', ['keyword' => 'r3_grp_']);

        $this->assertSame(['r3_grp_a', 'r3_grp_b'], array_column($result['items'], 'title'), 'sorted by title; other course, session-only and removed groups are not listed');
        $this->assertSame(2, $result['total']);
        $this->assertSame([2, 1], array_column($result['items'], 'members'));
        $this->assertSame([1800, 1200], array_column($result['items'], 'time'));
        $this->assertSame([$g1, $g2], array_column($result['items'], 'details'));
        $this->assertSame(2, $result['summary'][0]['value']);
        $this->assertSame(2, $result['summary'][1]['value'], 'distinct members over the listed groups');

        $inSession = $this->section('groups', ['keyword' => 'r3_grp_'], $this->ctx(1, $session));
        $this->assertSame(['r3_grp_a', 'r3_grp_b', 'r3_grp_session'], array_column($inSession['items'], 'title'), 'a session lists the course groups and its own');

        $page = $this->section('groups', ['keyword' => 'r3_grp_', 'itemsPerPage' => 1, 'page' => 2]);
        $this->assertSame(['r3_grp_b'], array_column($page['items'], 'title'));
        $this->assertSame(2, $page['total']);
        $one = $this->section('groups', ['keyword' => 'r3_grp_'], $this->ctx(1, null, $g2));
        $this->assertSame(['r3_grp_b'], array_column($one['items'], 'title'), 'the group of the context');
    }

    public function testGroupsReportProgressAssignmentsAndForumMessagesOfTheMembers(): void
    {
        $a = $this->learner('r3gr2a');
        $g = $this->newGroup('r3_grp_stats');
        $this->groupMember($g, $a);
        $lp = $this->newLp('r3_lp_grp');
        $n = (int) $this->conn()->fetchOne('SELECT COUNT(DISTINCT lp.iid) FROM c_lp lp INNER JOIN resource_link rl ON rl.resource_node_id = lp.resource_node_id WHERE rl.c_id = 1 AND rl.deleted_at IS NULL AND rl.session_id IS NULL');
        $this->lpView($a, $lp, 40);
        $view = (int) $this->conn()->lastInsertId();
        $this->lpItemView($view, 1, 3, '4');
        $this->insert('c_student_publication', ['user_id' => $a, 'title' => 'r3_assignment']);
        $assignment = (int) $this->conn()->lastInsertId();
        foreach ([[$assignment, 'r3_work_1'], [$assignment, 'r3_work_2'], [null, 'r3_work_root']] as [$parent, $title]) {
            $node = $this->node('student_publications', $title);
            $this->insert('c_student_publication', ['resource_node_id' => $node, 'user_id' => $a, 'parent_id' => $parent, 'title' => $title]);
            $this->link($node, 1);
        }
        $forumNode = $this->node('forums', 'r3_forum_grp');
        $this->insert('c_forum_forum', ['resource_node_id' => $forumNode, 'title' => 'r3_forum_grp']);
        $forum = (int) $this->conn()->lastInsertId();
        foreach ([1, 1, 2] as $i => $cid) {
            $node = $this->node('forum_posts', 'r3_gpost'.$i);
            $this->insert('c_forum_post', ['resource_node_id' => $node, 'forum_id' => $forum, 'poster_id' => $a, 'title' => 'r3_gpost'.$i, 'post_date' => '2001-01-01 10:00:00', 'visible' => 1]);
            $this->link($node, $cid);
        }

        $row = $this->section('groups', ['keyword' => 'r3_grp_stats'])['items'][0];

        $this->assertSame(round(40 / $n, 2), $row['progress'], 'mean over every learning path of the course');
        $this->assertSame(75.0, $row['score']);
        $this->assertSame(2, $row['works'], 'only the work items below an assignment (a parent_id is set)');
        $this->assertSame(2, $row['messages'], 'forum posts linked to this course');
    }

    /**
     * A removed account (-2) is not a member of the group report: not in the member counts, nor in the time,
     * assignments and messages the group is credited with.
     */
    public function testGroupsIgnoreRemovedAccountsEverywhere(): void
    {
        $a = $this->learner('r3gr3a');
        $gone = $this->learner('r3gr3b', 1, -2);
        $g = $this->newGroup('r3_grp_removed_member');
        $this->groupMember($g, $a);
        $this->groupMember($g, $gone);
        $this->courseVisit($a, '2001-01-01 10:00:00', '2001-01-01 10:10:00');
        $this->courseVisit($gone, '2001-01-01 10:00:00', '2001-01-01 12:00:00');
        $this->insert('c_student_publication', ['user_id' => $a, 'title' => 'r3_assignment_gone']);
        $assignment = (int) $this->conn()->lastInsertId();
        foreach ([$a, $gone] as $i => $uid) {
            $node = $this->node('student_publications', 'r3_gwork'.$i);
            $this->insert('c_student_publication', ['resource_node_id' => $node, 'user_id' => $uid, 'parent_id' => $assignment, 'title' => 'r3_gwork'.$i]);
            $this->link($node, 1);
        }
        $forumNode = $this->node('forums', 'r3_forum_gone');
        $this->insert('c_forum_forum', ['resource_node_id' => $forumNode, 'title' => 'r3_forum_gone']);
        $forum = (int) $this->conn()->lastInsertId();
        foreach ([$a, $gone] as $i => $uid) {
            $node = $this->node('forum_posts', 'r3_ggpost'.$i);
            $this->insert('c_forum_post', ['resource_node_id' => $node, 'forum_id' => $forum, 'poster_id' => $uid, 'title' => 'r3_ggpost'.$i, 'post_date' => '2001-01-01 10:00:00', 'visible' => 1]);
            $this->link($node, 1);
        }

        $result = $this->section('groups', ['keyword' => 'r3_grp_removed_member']);
        $row = $result['items'][0];

        $this->assertSame(1, $row['members']);
        $this->assertSame(600, $row['time']);
        $this->assertSame(1, $row['works']);
        $this->assertSame(1, $row['messages']);
        $this->assertSame(1, $result['summary'][1]['value']);
    }

    // ------------------------------------------------------------ resources

    public function testResourcesListTheLinkedResourcesOfTheCourseMostRecentFirst(): void
    {
        $author = $this->rawUser('r3rs1');
        $doc = $this->node('files', 'r3_res_doc', $author);
        $this->link($doc, 1, null, '2001-06-01 10:00:00');
        $lp = $this->node('lps', 'r3_res_lp', $author);
        $this->link($lp, 1, null, '2001-06-03 10:00:00');
        $this->link($this->node('files', 'r3_res_other_course', $author), 2, null, '2001-06-02 10:00:00');
        $this->link($this->node('files', 'r3_res_removed', $author), 1, null, '2001-06-04 10:00:00', '2001-06-05 00:00:00');
        $this->link($this->node('blog', 'r3_res_untracked_type', $author), 1, null, '2001-06-06 10:00:00');
        $session = $this->rawSession('r3rs1s');
        $this->link($this->node('files', 'r3_res_session_doc', $author), 1, $session, '2001-06-07 10:00:00');
        $this->conn()->executeStatement("INSERT INTO track_e_login (login_user_id, login_date, user_ip) VALUES (?, '2001-05-01 00:00:00', '10.1.1.1'), (?, '2001-06-02 00:00:00', '10.2.2.2'), (?, '2001-07-01 00:00:00', '10.3.3.3')", [$author, $author, $author]);

        $result = $this->section('resources', ['keyword' => 'r3_res_']);

        $this->assertSame(['r3_res_lp', 'r3_res_doc'], array_column($result['items'], 'document'));
        $this->assertSame(2, $result['total']);
        $this->assertSame(['Learning paths', 'Documents'], array_column($result['items'], 'tool'));
        $this->assertSame(['Created', 'Created'], array_column($result['items'], 'eventType'));
        $this->assertSame($author, $result['items'][0]['userId']);
        $this->assertSame(0, $result['items'][0]['sessionId']);
        $this->assertSame('', $result['items'][0]['session']);
        $this->assertArrayNotHasKey('userIp', $result['items'][0], 'IP hidden by default');

        $asc = $this->section('resources', ['keyword' => 'r3_res_', 'direction' => 'asc']);
        $this->assertSame(['r3_res_doc', 'r3_res_lp'], array_column($asc['items'], 'document'));
        $byName = $this->section('resources', ['keyword' => 'r3_res_', 'sort' => 'document', 'direction' => 'asc']);
        $this->assertSame(['r3_res_doc', 'r3_res_lp'], array_column($byName['items'], 'document'));
        $byTool = $this->section('resources', ['keyword' => 'r3_res_', 'sort' => 'tool', 'direction' => 'asc']);
        $this->assertSame(['Documents', 'Learning paths'], array_column($byTool['items'], 'tool'), 'by resource type title (files < lps)');
        $injection = $this->section('resources', ['keyword' => 'r3_res_', 'sort' => 'rl.id; DROP TABLE user']);
        $this->assertSame(2, $injection['total'], 'unknown sort keys fall back to the date');
        $page = $this->section('resources', ['keyword' => 'r3_res_', 'itemsPerPage' => 1, 'page' => 2]);
        $this->assertSame(['r3_res_doc'], array_column($page['items'], 'document'));
        $byCreator = $this->section('resources', ['keyword' => 'rp_r3rs1']);
        $this->assertSame(2, $byCreator['total'], 'the keyword also searches the creator');
        $inSession = $this->section('resources', ['keyword' => 'r3_res_'], $this->ctx(1, $session));
        $this->assertSame(['r3_res_session_doc'], array_column($inSession['items'], 'document'), 'a session lists only the links of that session');
        $this->assertSame($session, $inSession['items'][0]['sessionId']);
    }

    public function testResourcesShowTheLastKnownLoginAddressBeforeTheCreationWhenIpsAreVisible(): void
    {
        $author = $this->rawUser('r3rs2');
        $this->link($this->node('files', 'r3_ip_doc', $author), 1, null, '2001-06-01 10:00:00');
        $this->conn()->executeStatement("INSERT INTO track_e_login (login_user_id, login_date, user_ip) VALUES (?, '2001-05-01 00:00:00', '10.1.1.1'), (?, '2001-05-30 00:00:00', '10.2.2.2'), (?, '2001-07-01 00:00:00', '10.3.3.3')", [$author, $author, $author]);
        self::getContainer()->get(SettingsManager::class)->updateSetting('tracking.hide_ip_address', 'false');

        $row = $this->section('resources', ['keyword' => 'r3_ip_doc'])['items'][0];

        $this->assertSame('10.2.2.2', $row['userIp']);
    }

    // ---------------------------------------------------------------- tools

    public function testToolsCountTheLinkedItemsAndRankTheMostUsedOnes(): void
    {
        $a = $this->learner('r3tl1');
        $b = $this->learner('r3tl2');
        $before = $this->section('tools');
        $beforeTotals = array_column($before['items'], 'value', 'title');
        $this->newLp('r3_tl_lp');
        $this->newQuiz('r3_tl_quiz');
        $this->newQuiz('r3_tl_quiz_other', 2);
        $removed = $this->newQuiz('r3_tl_quiz_removed');
        $this->conn()->executeStatement('UPDATE resource_link SET deleted_at = \'2001-01-01 00:00:00\' WHERE resource_node_id = (SELECT resource_node_id FROM c_quiz WHERE iid = ?)', [$removed]);
        foreach ([[$a, 'r3tool', 1, 0, '2001-07-01 10:00:00'], [$b, 'r3tool', 1, 0, '2001-07-02 10:00:00'], [$a, 'r3tool', 1, 0, '2001-07-03 10:00:00'], [$a, 'r3other', 1, 0, '2001-07-04 10:00:00'], [$a, 'r3tool', 2, 0, '2001-07-05 10:00:00'], [$a, 'r3tool', 1, 99999, '2001-07-06 10:00:00']] as [$user, $tool, $cid, $sid, $date]) {
            $this->conn()->executeStatement(
                "INSERT INTO track_e_access (access_user_id, access_date, c_id, access_tool, session_id, user_ip) VALUES (?, ?, ?, ?, ?, '')",
                [$user, $date, $cid, $tool, $sid]
            );
        }

        $result = $this->section('tools');

        $totals = array_column($result['items'], 'value', 'title');
        $this->assertSame($beforeTotals['Learning paths'] + 1, $totals['Learning paths']);
        $this->assertSame($beforeTotals['Tests'] + 1, $totals['Tests'], 'other course and removed links are not counted');
        $sections = array_column($result['sections'], null, 'key');
        $usage = array_column($sections['usage']['items'], null, 'title');
        $this->assertSame(3, $usage['r3tool']['accesses']);
        $this->assertSame(2, $usage['r3tool']['users']);
        $this->assertSame('2001-07-03 10:00:00', $usage['r3tool']['lastAccess']);
        $this->assertSame(1, $usage['r3other']['accesses']);
        $titles = array_column($sections['tests']['items'], 'title');
        $this->assertContains('r3_tl_quiz', $titles);
        $this->assertNotContains('r3_tl_quiz_other', $titles);
        $this->assertNotContains('r3_tl_quiz_removed', $titles);
        $this->assertContains('r3_tl_lp', array_column($sections['learning-paths']['items'], 'title'));
        $filtered = $this->section('tools', ['keyword' => 'learning']);
        $this->assertSame(['Learning paths'], array_column($filtered['items'], 'title'), 'keyword filters the totals, case-insensitively');
    }

    public function testToolsGiveTestStatisticsAndLearningPathStatisticsOfTheCourse(): void
    {
        $a = $this->learner('r3tl3a');
        $b = $this->learner('r3tl3b');
        $quiz = $this->newQuiz('r3_tl_stats_quiz');
        $this->exerciseAttempt($a, $quiz, 5, 10);
        $this->exerciseAttempt($a, $quiz, 9, 10);
        $this->exerciseAttempt($b, $quiz, 10, 10);
        $this->exerciseAttempt($b, $quiz, 10, 10, null, 2);
        $lp = $this->newLp('r3_tl_stats_lp');
        $v1 = $this->lpView($a, $lp, 20);
        $this->lpView($b, $lp, 80);
        $this->lpItemView($v1, 1, 5, '10', 100, 1000000000);
        $this->lpItemView($v1, 2, 5, '10', 50, 1000003600);

        $sections = array_column($this->section('tools')['sections'], null, 'key');
        $testRow = array_column($sections['tests']['items'], null, 'title')['r3_tl_stats_quiz'];
        $lpRow = array_column($sections['learning-paths']['items'], null, 'title')['r3_tl_stats_lp'];

        $this->assertSame(2, $testRow['learners']);
        $this->assertSame(3, $testRow['attempts']);
        $this->assertSame(80.0, $testRow['averageScore']);
        $this->assertSame(100.0, $testRow['bestScore']);
        $this->assertSame(2, $lpRow['learners']);
        $this->assertSame(50.0, $lpRow['averageProgress']);
        $this->assertSame(50.0, $lpRow['averageScore']);
        $this->assertSame(150, $lpRow['totalTime']);
    }

    // ---------------------------------------------------------------- exams

    public function testExamsCrossEveryTestWithEveryLearnerAndGradeTheBestAttempt(): void
    {
        $a = $this->learner('r3ex1a');
        $b = $this->learner('r3ex1b');
        $c = $this->learner('r3ex1c');
        $gone = $this->learner('r3ex1d', 1, -2);
        $quiz = $this->newQuiz('r3_exam_quiz');
        $this->exerciseAttempt($a, $quiz, 5, 10);
        $this->exerciseAttempt($a, $quiz, 6, 10);
        $this->exerciseAttempt($b, $quiz, 7, 10);
        $this->exerciseAttempt($gone, $quiz, 10, 10);
        $this->exerciseAttempt($c, $quiz, 10, 10, null, 2);

        $result = $this->section('exams', ['exerciseId' => $quiz]);

        $byUser = array_column($result['items'], null, 'userId');
        $this->assertArrayNotHasKey($gone, $byUser);
        $this->assertSame(['Fail', 'Pass', 'No attempts'], [$byUser[$a]['status'], $byUser[$b]['status'], $byUser[$c]['status']]);
        $this->assertSame([60.0, 70.0, 0.0], [$byUser[$a]['percentage'], $byUser[$b]['percentage'], $byUser[$c]['percentage']], 'the best attempt; the threshold (70) is inclusive');
        $this->assertSame([2, 1, 0], [$byUser[$a]['attempts'], $byUser[$b]['attempts'], $byUser[$c]['attempts']]);
        $this->assertSame($quiz.'-'.$a, $byUser[$a]['id']);
        $this->assertSame(70, $result['meta']['score']);
        $this->assertSame($quiz, $result['meta']['exerciseId']);
        $this->assertContains($quiz, array_column($result['meta']['exercises'], 'value'));
        $this->assertSame(3, array_sum(array_column($result['items'], 'attempts')), 'summary attempts');

        $strict = $this->section('exams', ['exerciseId' => $quiz, 'score' => 60]);
        $this->assertSame('Pass', array_column($strict['items'], null, 'userId')[$a]['status']);
        $clamped = $this->section('exams', ['exerciseId' => $quiz, 'score' => 500]);
        $this->assertSame(100, $clamped['meta']['score']);
        $byKeyword = $this->section('exams', ['exerciseId' => $quiz, 'keyword' => 'RP_R3EX1B']);
        $this->assertSame([$b], array_column($byKeyword['items'], 'userId'));
        $page = $this->section('exams', ['exerciseId' => $quiz, 'itemsPerPage' => 1, 'page' => 2]);
        $this->assertCount(1, $page['items']);
        $this->assertSame(3, $page['total']);
    }

    // ---------------------------------------------------------------- audit

    private function audit(int $userId, string $event, string $date, string $value = 'v', ?int $courseId = 1, ?int $sessionId = 0): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_default (default_user_id, c_id, default_date, default_event_type, default_value_type, default_value, session_id)
             VALUES (?, ?, ?, ?, 'r3_value_type', ?, ?)",
            [$userId, $courseId, $date, $event, $value, $sessionId]
        );
    }

    public function testAuditListsTheEventsOfTheCourseNewestFirstWithoutRemovedUsers(): void
    {
        $a = $this->rawUser('r3au1a');
        $gone = $this->rawUser('r3au1b', 5, -2);
        $session = $this->rawSession('r3au1s');
        $this->audit($a, 'r3_ev_old', '2001-08-01 10:00:00', '<b>bold</b> text');
        $this->audit($a, 'r3_ev_new', '2001-08-03 10:00:00', str_repeat('x', 2100));
        $this->audit($gone, 'r3_ev_gone', '2001-08-04 10:00:00');
        $this->audit($a, 'r3_ev_course2', '2001-08-05 10:00:00', 'v', 2);
        $this->audit($a, 'r3_ev_session', '2001-08-06 10:00:00', 'v', 1, $session);

        $result = $this->section('audit', ['keyword' => 'r3_value_type']);

        $this->assertSame(['r3_ev_new', 'r3_ev_old'], array_column($result['items'], 'eventType'));
        $this->assertSame(2, $result['total']);
        $this->assertSame('bold text', $result['items'][1]['value'], 'tags are stripped');
        $this->assertSame(2000, mb_strlen($result['items'][0]['value']));
        $this->assertStringEndsWith('...', $result['items'][0]['value']);
        $this->assertSame($a, $result['items'][0]['userId']);
        $this->assertSame('', $result['items'][0]['session']);
        $this->assertArrayNotHasKey('userIp', $result['items'][0]);

        $asc = $this->section('audit', ['keyword' => 'r3_value_type', 'direction' => 'asc']);
        $this->assertSame(['r3_ev_old', 'r3_ev_new'], array_column($asc['items'], 'eventType'));
        $byEvent = $this->section('audit', ['keyword' => 'r3_value_type', 'sort' => 'eventType', 'direction' => 'asc']);
        $this->assertSame(['r3_ev_new', 'r3_ev_old'], array_column($byEvent['items'], 'eventType'));
        $bad = $this->section('audit', ['keyword' => 'r3_value_type', 'sort' => '1;DROP']);
        $this->assertSame(['r3_ev_new', 'r3_ev_old'], array_column($bad['items'], 'eventType'));
        $inSession = $this->section('audit', ['keyword' => 'r3_value_type'], $this->ctx(1, $session));
        $this->assertSame(['r3_ev_session'], array_column($inSession['items'], 'eventType'));
        $this->assertSame(substr($inSession['items'][0]['session'], 0, 3), 'rp_');
        $page = $this->section('audit', ['keyword' => 'r3_value_type', 'itemsPerPage' => 1, 'page' => 2]);
        $this->assertSame(['r3_ev_old'], array_column($page['items'], 'eventType'));
        $this->assertSame(2, $page['total']);
    }

    // -------------------------------------------------------- learning paths

    public function testLearningPathsModeListsThePathsOfTheCourseWithTheirStatistics(): void
    {
        $a = $this->learner('r3lp1a');
        $b = $this->learner('r3lp1b');
        $lp = $this->newLp('r3_lpm_a');
        $this->newLp('r3_lpm_b');
        $this->newLp('r3_lpm_other', 2);
        $session = $this->rawSession('r3lp1s');
        $this->newLp('r3_lpm_session', 1, $session);
        $v = $this->lpView($a, $lp, 30);
        $this->lpView($b, $lp, 70);
        $this->lpItemView($v, 1, 1, '2', 60, 1000000000);

        $result = $this->section('learning-paths', ['keyword' => 'r3_lpm_']);

        $this->assertSame(['r3_lpm_a', 'r3_lpm_b'], array_column($result['items'], 'title'));
        $this->assertSame(2, $result['total']);
        $first = $result['items'][0];
        $this->assertSame([2, 50.0, 50.0, 60], [$first['learners'], $first['averageProgress'], $first['averageScore'], $first['totalTime']]);
        $this->assertSame(0, $result['items'][1]['learners']);
        $this->assertSame('paths', $result['meta']['mode']);
        $this->assertSame(2, $result['summary'][1]['value']);
        $inSession = $this->section('learning-paths', ['keyword' => 'r3_lpm_'], $this->ctx(1, $session));
        $this->assertSame(['r3_lpm_a', 'r3_lpm_b', 'r3_lpm_session'], array_column($inSession['items'], 'title'));
        $unknownMode = $this->section('learning-paths', ['mode' => 'nope', 'keyword' => 'r3_lpm_']);
        $this->assertSame('paths', $unknownMode['meta']['mode']);
        $page = $this->section('learning-paths', ['keyword' => 'r3_lpm_', 'itemsPerPage' => 1, 'page' => 2]);
        $this->assertSame(['r3_lpm_b'], array_column($page['items'], 'title'));
    }

    public function testLearningPathsByLearnerShowTheLatestViewOfEachLearnerAndPath(): void
    {
        $a = $this->learner('r3lp2a');
        $b = $this->learner('r3lp2b');
        $lp = $this->newLp('r3_lpu_a');
        $old = $this->lpView($a, $lp, 10, null, 1);
        $new = $this->lpView($a, $lp, 90, null, 2);
        $this->lpView($b, $lp, 40);
        $this->lpView($a, $lp, 100, null, 1, 2);
        $this->lpItemView($new, 1, 3, '4', 120, 1000000000);
        $this->lpItemView($new, 2, 1, '4', 30, 1000003600);
        $this->lpItemView($old, 1, 0, '4', 999, 5);
        $this->conn()->executeStatement("UPDATE c_lp_view SET completion_date = '2001-09-09' WHERE iid = ?", [$new]);
        $this->conn()->executeStatement('UPDATE user SET lastname = ? WHERE id = ?', ['R3Alpha', $a]);
        $this->conn()->executeStatement('UPDATE user SET lastname = ? WHERE id = ?', ['R3Beta', $b]);

        $result = $this->section('learning-paths', ['mode' => 'users', 'keyword' => 'r3_lpu_']);

        $this->assertSame([$a, $b], array_column($result['items'], 'userId'));
        $this->assertSame([90.0, 40.0], array_column($result['items'], 'progress'));
        $first = $result['items'][0];
        $this->assertSame(150, $first['totalTime'], 'time of the latest view only');
        $this->assertSame(50.0, round($first['score'], 2), '(3 + 1) / (4 + 4)');
        $this->assertSame('2001-09-09', $first['completionDate']);
        $this->assertSame('r3_lpu_a', $first['title']);
        $this->assertSame($this->conn()->fetchOne('SELECT FROM_UNIXTIME(1000000000)'), $first['firstAccess'], 'earliest started item of the latest view');
        $this->assertSame(2, $result['total']);
        $this->assertSame('users', $result['meta']['mode']);
        $byUserName = $this->section('learning-paths', ['mode' => 'users', 'keyword' => 'rp_r3lp2b_']);
        $this->assertSame([$b], array_column($byUserName['items'], 'userId'));
    }

    public function testLearningPathQuestionReportCountsLpAttemptsOnly(): void
    {
        $a = $this->learner('r3lp3');
        $quiz = $this->newQuiz('r3_qr_quiz');
        $lp = $this->newLp('r3_qr_lp');
        $this->insert('c_quiz_question', ['question' => 'r3_question_one', 'ponderation' => 10, 'position' => 1]);
        $q1 = (int) $this->conn()->lastInsertId();
        $this->insert('c_quiz_question', ['question' => 'r3_question_two', 'ponderation' => 4, 'position' => 2]);
        $q2 = (int) $this->conn()->lastInsertId();
        $inLp1 = $this->exerciseAttempt($a, $quiz, 5, 14, null, 1, '2001-01-01 10:00:00', $lp);
        $inLp2 = $this->exerciseAttempt($a, $quiz, 9, 14, null, 1, '2001-01-02 10:00:00', $lp);
        $standalone = $this->exerciseAttempt($a, $quiz, 14, 14);
        $otherCourse = $this->exerciseAttempt($a, $quiz, 14, 14, null, 2, '2001-01-01 10:00:00', $lp);
        foreach ([[$inLp1, $q1, 5], [$inLp2, $q1, 10], [$inLp1, $q2, 0], [$standalone, $q2, 4], [$otherCourse, $q2, 4]] as [$exe, $q, $marks]) {
            $this->insert('track_e_attempt', ['exe_id' => $exe, 'user_id' => $a, 'question_id' => $q, 'marks' => $marks]);
        }

        $result = $this->section('learning-paths', ['mode' => 'questions', 'keyword' => 'r3_question']);

        $this->assertSame(['r3_question_one', 'r3_question_two'], array_column($result['items'], 'title'), 'most attempted first');
        $this->assertSame([2, 1], array_column($result['items'], 'quantity'));
        $this->assertSame(['7.5 / 10', '0 / 4'], array_column($result['items'], 'score'));
        $this->assertSame(2, $result['total']);
        $this->assertSame('questions', $result['meta']['mode']);
    }
}
