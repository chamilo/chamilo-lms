<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\StudentSuccess;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Service\StudentSuccess\StudentLearningActivityCollector;
use Chamilo\Tests\ChamiloTestTrait;
use Chamilo\Tests\CoreBundle\Service\GenericRowInsertTrait;
use Chamilo\Tests\CoreBundle\Service\RawReportingFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The forum, assignment, survey, attendance and gradebook sections of StudentLearningActivityCollector (the data
 * handed to the Student Success AI feedback). Complements StudentLearningActivityCollectorTest (learning paths and
 * exercises). Content rows are inserted raw with FIXED dates; each one borrows an installer resource node that has
 * no resource_link (their own link is created here), the foreign keys are off for the test (session variable).
 * Learners are raw "rp_" users. Tag sv3_.
 */
final class StudentLearningActivityCollectorSectionsTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;
    use GenericRowInsertTrait;

    private StudentLearningActivityCollector $collector;
    /** @var int[] */
    private array $nodes = [];
    private int $sid = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->collector = self::getContainer()->get(StudentLearningActivityCollector::class);
        $this->sid = $this->rawSession('sv3');
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $this->nodes = array_map('intval', $this->conn()->fetchFirstColumn(
            'SELECT rn.id FROM resource_node rn
             WHERE rn.id NOT IN (SELECT resource_node_id FROM c_forum_post WHERE resource_node_id IS NOT NULL)
               AND rn.id NOT IN (SELECT resource_node_id FROM c_student_publication WHERE resource_node_id IS NOT NULL)
               AND rn.id NOT IN (SELECT resource_node_id FROM c_survey WHERE resource_node_id IS NOT NULL)
               AND rn.id NOT IN (SELECT resource_node_id FROM c_attendance WHERE resource_node_id IS NOT NULL)
               AND rn.id NOT IN (SELECT resource_node_id FROM c_forum_category WHERE resource_node_id IS NOT NULL)
               AND rn.id NOT IN (SELECT resource_node_id FROM c_forum_forum WHERE resource_node_id IS NOT NULL)
               AND rn.id NOT IN (SELECT resource_node_id FROM c_forum_thread WHERE resource_node_id IS NOT NULL)
             ORDER BY rn.id DESC LIMIT 40'
        ));
        if ([] !== $this->nodes) {
            // The sections join on the node: free the borrowed nodes of their installer links (rolled back).
            $this->conn()->executeStatement('DELETE FROM resource_link WHERE resource_node_id IN ('.implode(',', $this->nodes).')');
        }
        if (\count($this->nodes) < 20) {
            $this->markTestSkipped('Not enough unlinked resource nodes.');
        }
    }

    protected function tearDown(): void
    {
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        parent::tearDown();
    }

    private function node(): int
    {
        return (int) array_pop($this->nodes);
    }

    /** A resource node of its own, linked to a course (and optionally a session). */
    private function linkedNode(int $courseId = 1, ?int $sessionId = null): int
    {
        $node = $this->node();
        $this->insert('resource_link', [
            'resource_node_id' => $node, 'c_id' => $courseId, 'session_id' => $sessionId, 'visibility' => 2,
            'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00',
        ]);

        return $node;
    }

    private function row(string $table, array $values): int
    {
        $this->insert($table, $values);

        return (int) $this->conn()->lastInsertId();
    }

    private function collect(int $userId, ?int $sessionId = null, int $courseId = 1): array
    {
        $this->fresh();
        $em = $this->getEntityManager();

        return $this->collector->collect(
            $em->find(Course::class, $courseId),
            null === $sessionId ? null : $em->find(Session::class, $sessionId),
            $this->entityUser($userId)
        )['sections'];
    }

    public function testForumPostsOfTheLearnerOrderedNewestFirstWithTheLatestThreadGrade(): void
    {
        $u = $this->rawUser('learner');
        $other = $this->rawUser('other');
        $cat = $this->row('c_forum_category', ['title' => '<b>Cat</b>']);
        $forum = $this->row('c_forum_forum', ['title' => 'Forum A', 'forum_category' => $cat]);
        $thread = $this->row('c_forum_thread', ['title' => 'Thread&nbsp;1', 'forum_id' => $forum, 'thread_qualify_max' => 10]);
        $old = $this->row('c_forum_post', ['thread_id' => $thread, 'forum_id' => $forum, 'poster_id' => $u, 'title' => 'old', 'post_text' => "<p>Hello   <i>world</i></p>\n", 'post_date' => '2020-01-01 10:00:00', 'resource_node_id' => $this->linkedNode()]);
        $newA = $this->row('c_forum_post', ['thread_id' => $thread, 'forum_id' => $forum, 'poster_id' => $u, 'title' => 'new', 'post_text' => 'second', 'post_date' => '2020-02-01 10:00:00', 'resource_node_id' => $this->linkedNode()]);
        $newB = $this->row('c_forum_post', ['thread_id' => $thread, 'forum_id' => $forum, 'poster_id' => $u, 'title' => 'new same date', 'post_text' => 'third', 'post_date' => '2020-02-01 10:00:00', 'resource_node_id' => $this->linkedNode()]);
        $this->row('c_forum_post', ['thread_id' => $thread, 'forum_id' => $forum, 'poster_id' => $other, 'title' => 'other', 'post_date' => '2020-03-01 10:00:00', 'resource_node_id' => $this->linkedNode()]);
        $this->row('c_forum_post', ['thread_id' => $thread, 'forum_id' => $forum, 'poster_id' => $u, 'title' => 'other course', 'post_date' => '2020-04-01 10:00:00', 'resource_node_id' => $this->linkedNode(2)]);
        $this->row('c_forum_post', ['thread_id' => $thread, 'forum_id' => $forum, 'poster_id' => $u, 'title' => 'session post', 'post_date' => '2020-05-01 10:00:00', 'resource_node_id' => $this->linkedNode(1, $this->sid)]);
        $this->row('c_forum_thread_qualify', ['user_id' => $u, 'thread_id' => $thread, 'c_id' => 1, 'qualify' => 4.5, 'qualify_time' => '2020-03-01 00:00:00']);
        $this->row('c_forum_thread_qualify', ['user_id' => $u, 'thread_id' => $thread, 'c_id' => 1, 'qualify' => 8.5, 'qualify_time' => '2020-03-05 00:00:00']);
        $this->row('c_forum_thread_qualify', ['user_id' => $other, 'thread_id' => $thread, 'c_id' => 1, 'qualify' => 1.0, 'qualify_time' => '2020-03-09 00:00:00']);
        $this->row('c_forum_thread_qualify', ['user_id' => $u, 'thread_id' => $thread, 'c_id' => 2, 'qualify' => 2.0, 'qualify_time' => '2020-03-09 00:00:00']);

        $items = $this->collect($u)['forums']['items'];

        $this->assertSame([$newB, $newA, $old], array_map(static fn (array $i): int => $i['post']['id'], $items), 'Newest first, ties by id desc; other learner, other course and session posts excluded');
        $first = $items[0];
        $this->assertSame(['id' => $cat, 'title' => 'Cat'], $first['category']);
        $this->assertSame(['id' => $forum, 'title' => 'Forum A'], $first['forum']);
        $this->assertSame('Thread 1', $first['thread']['title']);
        $this->assertSame(10.0, $first['thread']['maxScore']);
        $this->assertSame(['score' => 8.5, 'gradedAt' => '2020-03-05 00:00:00'], $first['thread']['grade'], 'The latest grade of this learner in this course');
        $this->assertSame('Hello world', $items[2]['post']['message'], 'HTML stripped, spaces collapsed');
        $this->assertSame('2020-01-01 10:00:00', $items[2]['post']['postedAt']);
        $this->assertSame(['session post'], array_map(static fn (array $i): string => $i['post']['title'], $this->collect($u, $this->sid)['forums']['items']), 'Session context');
        $this->assertSame([], $this->collect($this->rawUser('silent'))['forums']['items']);
    }

    public function testForumPostWithoutCategoryHasNullCategoryAndNoGrade(): void
    {
        $u = $this->rawUser('learner');
        $forum = $this->row('c_forum_forum', ['title' => 'Loose', 'forum_category' => null]);
        $thread = $this->row('c_forum_thread', ['title' => 'T', 'forum_id' => $forum]);
        $this->row('c_forum_post', ['thread_id' => $thread, 'forum_id' => $forum, 'poster_id' => $u, 'post_date' => '2020-01-01 10:00:00', 'resource_node_id' => $this->linkedNode()]);

        $item = $this->collect($u)['forums']['items'][0];

        $this->assertNull($item['category']['id']);
        // PINNED QUIRK: thread_qualify_max is NOT NULL, so "no maximum" is reported as 0.0, never null.
        $this->assertSame(0.0, $item['thread']['maxScore']);
        $this->assertNull($item['thread']['grade']);
    }

    public function testAssignmentGradesAreCollectedWithoutTheSubmissionContent(): void
    {
        $u = $this->rawUser('learner');
        $other = $this->rawUser('other');
        $assignment = $this->row('c_student_publication', ['title' => 'Essay <b>1</b>', 'filetype' => 'folder']);
        $s1 = $this->row('c_student_publication', ['parent_id' => $assignment, 'user_id' => $u, 'title' => 'SECRET TITLE', 'description' => 'SECRET TEXT', 'qualification' => 14.5, 'weight' => 20, 'accepted' => 1, 'sent_date' => '2020-01-01 10:00:00', 'date_of_qualification' => '2020-01-05 10:00:00', 'resource_node_id' => $this->linkedNode()]);
        $s2 = $this->row('c_student_publication', ['parent_id' => $assignment, 'user_id' => $u, 'qualification' => 0, 'weight' => 0, 'accepted' => 0, 'sent_date' => '2020-02-01 10:00:00', 'resource_node_id' => $this->linkedNode()]);
        $this->row('c_student_publication', ['parent_id' => $assignment, 'user_id' => $other, 'sent_date' => '2020-03-01 10:00:00', 'resource_node_id' => $this->linkedNode()]);
        $this->row('c_student_publication', ['parent_id' => $assignment, 'user_id' => $u, 'sent_date' => '2020-04-01 10:00:00', 'resource_node_id' => $this->linkedNode(2)]);
        $this->row('c_student_publication', ['parent_id' => $assignment, 'user_id' => $u, 'sent_date' => '2020-05-01 10:00:00', 'resource_node_id' => $this->linkedNode(1, $this->sid)]);

        $items = $this->collect($u)['assignments']['items'];

        $this->assertSame([$s2, $s1], array_map(static fn (array $i): int => $i['submissionId'], $items), 'Newest first');
        $this->assertSame([
            'assignmentId' => $assignment, 'title' => 'Essay 1', 'submissionId' => $s1, 'grade' => 14.5, 'maxScore' => 20.0,
            'accepted' => true, 'submittedAt' => '2020-01-01 10:00:00', 'gradedAt' => '2020-01-05 10:00:00', 'submissionContentIncluded' => false,
        ], $items[1]);
        // PINNED QUIRK: the columns are NOT NULL, so an ungraded submission reports grade 0.0 / max 0.0 /
        // accepted false and cannot be told apart from a graded zero (the nullable branches are unreachable).
        $this->assertSame(0.0, $items[0]['grade']);
        $this->assertFalse($items[0]['accepted']);
        $this->assertStringNotContainsString('SECRET', json_encode($items));
        $this->assertCount(1, $this->collect($u, $this->sid)['assignments']['items']);
    }

    public function testSurveyAnswersOfTheLearnerWithSelectedOptionAndFreeText(): void
    {
        $u = $this->rawUser('learner');
        $other = $this->rawUser('other');
        $survey = $this->row('c_survey', ['title' => 'Satisfaction', 'anonymous' => '0', 'resource_node_id' => $this->linkedNode()]);
        $emptySurvey = $this->row('c_survey', ['title' => 'Unanswered', 'anonymous' => '0', 'resource_node_id' => $this->linkedNode()]);
        $q1 = $this->row('c_survey_question', ['survey_id' => $survey, 'survey_question' => 'Rate <b>it</b>', 'type' => 'yesno', 'sort' => 2]);
        $q2 = $this->row('c_survey_question', ['survey_id' => $survey, 'survey_question' => 'Comment?', 'type' => 'open', 'sort' => 1]);
        $opt = $this->row('c_survey_question_option', ['question_id' => $q1, 'survey_id' => $survey, 'option_text' => '<i>Yes</i>', 'sort' => 1]);
        $a1 = $this->row('c_survey_answer', ['survey_id' => $survey, 'question_id' => $q1, 'option_id' => (string) $opt, 'value' => 1, 'user' => (string) $u, 'session_id' => null]);
        $a2 = $this->row('c_survey_answer', ['survey_id' => $survey, 'question_id' => $q2, 'option_id' => '<p>Great course</p>', 'value' => 0, 'user' => (string) $u, 'session_id' => null]);
        $this->row('c_survey_answer', ['survey_id' => $survey, 'question_id' => $q1, 'option_id' => (string) $opt, 'value' => 1, 'user' => (string) $other, 'session_id' => null]);
        $this->row('c_survey_answer', ['survey_id' => $survey, 'question_id' => $q1, 'option_id' => (string) $opt, 'value' => 1, 'user' => (string) $u, 'session_id' => $this->sid]);

        $items = $this->collect($u)['surveys']['items'];
        $byId = [];
        foreach ($items as $i) {
            $byId[$i['surveyId']] = $i;
        }

        $this->assertContains($survey, array_keys($byId));
        $this->assertContains($emptySurvey, array_keys($byId));
        $this->assertSame([], $byId[$emptySurvey]['answers']);
        $this->assertTrue($byId[$survey]['responsesIncluded']);
        $answers = $byId[$survey]['answers'];
        $this->assertSame([$a2, $a1], array_map(static fn (array $a): int => $a['answerId'], $answers), 'Ordered by question sort');
        $this->assertSame('Great course', $answers[0]['freeText']);
        $this->assertNull($answers[0]['selectedOption']);
        $this->assertSame('Rate it', $answers[1]['question']);
        $this->assertSame(['id' => $opt, 'title' => 'Yes'], $answers[1]['selectedOption']);
        $this->assertSame(1, $answers[1]['value']);
        $this->assertArrayNotHasKey('rawOptionId', $answers[1]);
    }

    public function testAnonymousSurveyAnswersAreNeverReidentified(): void
    {
        $u = $this->rawUser('learner');
        $survey = $this->row('c_survey', ['title' => 'Anon', 'anonymous' => '1', 'resource_node_id' => $this->linkedNode()]);
        $q = $this->row('c_survey_question', ['survey_id' => $survey, 'survey_question' => 'Q', 'type' => 'open', 'sort' => 1]);
        $this->row('c_survey_answer', ['survey_id' => $survey, 'question_id' => $q, 'option_id' => 'private words', 'value' => 0, 'user' => (string) $u, 'session_id' => null]);

        $items = array_values(array_filter($this->collect($u)['surveys']['items'], static fn (array $i): bool => $i['surveyId'] === $survey));
        $this->assertCount(1, $items);
        $item = $items[0];

        $this->assertTrue($item['anonymous']);
        $this->assertFalse($item['responsesIncluded']);
        $this->assertSame([], $item['answers']);
        $this->assertStringNotContainsString('private words', json_encode($item));
    }

    public function testSurveyOfAnotherCourseOrSessionIsNotListed(): void
    {
        $u = $this->rawUser('learner');
        $this->row('c_survey', ['title' => 'Other course', 'anonymous' => '0', 'resource_node_id' => $this->linkedNode(2)]);
        $inSession = $this->row('c_survey', ['title' => 'Session only', 'anonymous' => '0', 'resource_node_id' => $this->linkedNode(1, $this->sid)]);

        $this->assertNotContains($inSession, array_column($this->collect($u)['surveys']['items'], 'surveyId'));
        $this->assertContains($inSession, array_column($this->collect($u, $this->sid)['surveys']['items'], 'surveyId'));
        $this->assertNotContains('Other course', array_column($this->collect($u, $this->sid)['surveys']['items'], 'title'));
    }

    public function testAttendanceRecordsPerCalendarWithThePresenceOfThisLearner(): void
    {
        $u = $this->rawUser('learner');
        $other = $this->rawUser('other');
        $att = $this->row('c_attendance', ['title' => 'Roll <b>call</b>', 'attendance_qualify_max' => 10, 'resource_node_id' => $this->linkedNode()]);
        $empty = $this->row('c_attendance', ['title' => 'No dates', 'attendance_qualify_max' => 0, 'resource_node_id' => $this->linkedNode()]);
        $c2 = $this->row('c_attendance_calendar', ['attendance_id' => $att, 'date_time' => '2020-02-01 09:00:00', 'done_attendance' => 0]);
        $c1 = $this->row('c_attendance_calendar', ['attendance_id' => $att, 'date_time' => '2020-01-01 09:00:00', 'done_attendance' => 1]);
        $this->row('c_attendance_sheet', ['user_id' => $u, 'attendance_calendar_id' => $c1, 'presence' => 1]);
        $this->row('c_attendance_sheet', ['user_id' => $other, 'attendance_calendar_id' => $c2, 'presence' => 1]);
        $this->row('c_attendance_result', ['user_id' => $u, 'attendance_id' => $att, 'score' => 7]);
        $this->row('c_attendance_result', ['user_id' => $other, 'attendance_id' => $att, 'score' => 3]);

        $items = $this->collect($u)['attendance']['items'];

        $this->assertSame([$att, $empty], array_column($items, 'attendanceId'));
        $this->assertSame('Roll call', $items[0]['title']);
        $this->assertSame(10, $items[0]['maxScore']);
        $this->assertSame(7, $items[0]['aggregateScore']);
        $this->assertSame([
            ['calendarId' => $c1, 'date' => '2020-01-01 09:00:00', 'closed' => true, 'presenceCode' => 1],
            ['calendarId' => $c2, 'date' => '2020-02-01 09:00:00', 'closed' => false, 'presenceCode' => null],
        ], $items[0]['records'], 'Chronological; the other learner presence is not shown');
        $this->assertSame([], $items[1]['records']);
        $this->assertNull($items[1]['aggregateScore']);
    }

    public function testGradebookSectionListsRootCategoriesOfTheContextWithTheLearnerResult(): void
    {
        $u = $this->rawUser('learner');
        $cat = $this->row('gradebook_category', ['title' => 'Root <b>cat</b>', 'c_id' => 1, 'weight' => 100, 'visible' => 1, 'session_id' => null, 'parent_id' => null]);
        $this->row('gradebook_category', ['title' => 'Other course root', 'c_id' => 2, 'weight' => 100, 'visible' => 1]);
        $this->row('gradebook_category', ['title' => 'Session root', 'c_id' => 1, 'weight' => 100, 'visible' => 1, 'session_id' => $this->sid]);
        $this->row('gradebook_category', ['title' => 'Child', 'c_id' => 1, 'weight' => 10, 'visible' => 1, 'parent_id' => $cat]);
        $ev = $this->row('gradebook_evaluation', ['title' => 'Exam', 'category_id' => $cat, 'c_id' => 1, 'weight' => 40, 'max' => 20, 'visible' => 1, 'type' => 'evalcourse']);
        $this->row('gradebook_evaluation', ['title' => 'Hidden', 'category_id' => $cat, 'c_id' => 1, 'weight' => 10, 'max' => 20, 'visible' => 0, 'type' => 'evalcourse']);
        $this->row('gradebook_result', ['evaluation_id' => $ev, 'user_id' => $u, 'score' => 15]);

        $items = $this->collect($u)['gradebook']['items'];

        $titles = array_column($items, 'title');
        $this->assertContains('Root cat', $titles);
        $this->assertNotContains('Other course root', $titles);
        $this->assertNotContains('Session root', $titles);
        $this->assertNotContains('Child', $titles, 'Sub-categories are nested, not listed as roots');
        $root = $items[array_search('Root cat', $titles, true)];
        $this->assertSame($cat, $root['categoryId']);
        $this->assertSame(['Exam'], array_column($root['evaluations'], 'title'), 'Hidden evaluations are skipped');
        $this->assertSame(15.0, (float) $root['evaluations'][0]['score']);
        $this->assertSame(20.0, (float) $root['evaluations'][0]['maxScore']);
        $this->assertTrue($root['evaluations'][0]['hasResult']);
        $this->assertContains('Session root', array_column($this->collect($u, $this->sid)['gradebook']['items'], 'title'));
    }
}
