<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\Uuid;
use Throwable;

/**
 * Characterization of the procedural forum library (public/main/forum/forumfunction.inc.php).
 *
 * The forum tool now runs on Vue + API Platform (src/CoreBundle/State/Forum/*), so most of this library has no
 * caller left: only saveForumCategory/store_forum/saveThread (course creation, imports), set_notification, the
 * read helpers (get_forums, get_threads, get_forum_categories, get_forums_of_group, getForumCreatedByUser,
 * getForumToolUrl) are still used. The dead helpers keep their raw SQL, which is what a rewrite would have to
 * reproduce, so both groups are pinned. "PINNED QUIRK" marks behaviour that looks wrong and is not changed here.
 *
 * Fixtures are raw prepared inserts (no createCourse()/createUser(): they deadlock when sibling suites share the
 * database) on fixture users (admin 1, anon 2) and courses 1 and 2. Every title starts with "fm_".
 */
final class ForumCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const T0 = '2020-01-01 10:00:00';

    private Connection $conn;
    private int $seq = 0;

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__.'/../../../public/main/forum/forumfunction.inc.php';
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/forum/index.php');
        $request->setSession($session);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        \ChamiloSession::erase('login_as');
        \ChamiloSession::erase('studentview');
        \ChamiloSession::erase('groupId');
        unset($_SESSION['forum_notification'], $_POST['forum'], $_POST['thread_id']);
    }

    protected function tearDown(): void
    {
        unset($_SESSION['forum_notification'], $_POST['forum'], $_POST['thread_id']);
        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function setContext(int $userId, int $courseId, int $sessionId = 0): void
    {
        $this->getEntityManager()->clear();
        \ChamiloSession::write('_user', ['id' => $userId]);
        $course = $this->getCourse($courseId);
        \ChamiloSession::write('_real_cid', $courseId);
        \ChamiloSession::write('_cid', $course->getCode());
        \ChamiloSession::write('_course', api_get_course_info($course->getCode()));
        \ChamiloSession::write('sid', $sessionId);
    }

    private function u1(): array
    {
        return api_get_user_info(1);
    }

    private function sessionRow(): int
    {
        $this->conn->insert('session', [
            'title' => 'fm_session_'.(++$this->seq).'_'.uniqid(),
            'nbr_courses' => 0,
            'nbr_users' => 0,
            'nbr_classes' => 0,
            'visibility' => 1,
            'status' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function node(string $type, string $title, ?int $parentId = null): int
    {
        $typeId = (int) $this->conn->fetchOne('SELECT id FROM resource_type WHERE title = ?', [$type]);
        $n = ++$this->seq;
        $slug = 'fm-node-'.$n.'-'.uniqid();
        $this->conn->executeStatement(
            'INSERT INTO resource_node (resource_type_id, creator_id, parent_id, title, slug, level, path, created_at, updated_at, public, uuid)
             VALUES (?, 1, ?, ?, ?, 1, ?, ?, ?, 0, ?)',
            [$typeId, $parentId, $title, $slug, $slug.'/', self::T0, self::T0, Uuid::v4()->toBinary()]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function link(int $nodeId, int $courseId, ?int $sessionId = null, int $visibility = 2, int $order = 0): void
    {
        $this->conn->insert('resource_link', [
            'resource_node_id' => $nodeId,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'visibility' => $visibility,
            'display_order' => $order,
            'resource_type_group' => 0,
            'created_at' => self::T0,
            'updated_at' => self::T0,
        ]);
    }

    private function courseNode(int $courseId): int
    {
        return (int) $this->conn->fetchOne('SELECT resource_node_id FROM course WHERE id = ?', [$courseId]);
    }

    /** @return int category iid */
    private function category(int $courseId, ?int $sessionId = null, int $visibility = 2, int $order = 0): int
    {
        $node = $this->node('forum_categories', 'fm_cat_'.(++$this->seq), $this->courseNode($courseId));
        $this->conn->insert('c_forum_category', [
            'resource_node_id' => $node,
            'title' => 'fm_cat_'.$this->seq,
            'locked' => 0,
        ]);
        $iid = (int) $this->conn->lastInsertId();
        $this->link($node, $courseId, $sessionId, $visibility, $order);

        return $iid;
    }

    /** @return int forum iid */
    private function forum(?int $courseId, ?int $categoryId = null, ?int $sessionId = null, int $visibility = 2, array $o = []): int
    {
        $node = $this->node('forums', 'fm_forum_'.(++$this->seq), $this->courseNode($courseId ?? 1));
        $this->conn->insert('c_forum_forum', $o + [
            'resource_node_id' => $node,
            'forum_category' => $categoryId,
            'title' => 'fm_forum_'.$this->seq,
            'locked' => 0,
            'forum_image' => '',
            'forum_threads' => 0,
            'forum_posts' => 0,
        ]);
        $iid = (int) $this->conn->lastInsertId();
        if (null !== $courseId) {
            $this->link($node, $courseId, $sessionId, $visibility);
        }

        return $iid;
    }

    /** @return int thread iid */
    private function thread(int $forumId, ?int $courseId = null, ?int $sessionId = null, array $o = []): int
    {
        $node = null;
        if (null !== $courseId) {
            $node = $this->node('forum_threads', 'fm_thread_'.(++$this->seq));
        }
        $this->conn->insert('c_forum_thread', $o + [
            'resource_node_id' => $node,
            'forum_id' => $forumId,
            'thread_poster_id' => 1,
            'title' => 'fm_thread_'.(++$this->seq),
            'thread_date' => self::T0,
            'thread_replies' => 0,
            'thread_views' => 0,
            'thread_sticky' => 0,
            'locked' => 0,
            'thread_qualify_max' => 0,
            'thread_weight' => 0,
            'thread_peer_qualify' => 0,
        ]);
        $iid = (int) $this->conn->lastInsertId();
        if (null !== $courseId) {
            $this->link($node, $courseId, $sessionId);
        }

        return $iid;
    }

    /** @return int post iid */
    private function post(int $threadId, int $forumId, int $posterId, array $o = []): int
    {
        $this->conn->insert('c_forum_post', $o + [
            'thread_id' => $threadId,
            'forum_id' => $forumId,
            'poster_id' => $posterId,
            'post_parent_id' => null,
            'title' => 'fm_post_'.(++$this->seq),
            'post_text' => 'text',
            'post_date' => self::T0,
            'post_notification' => 0,
            'visible' => 1,
            'status' => CForumPost::STATUS_VALIDATED,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function qualify(int $threadId, int $userId, int $courseId, float $score, int $graderId = 1): int
    {
        $this->conn->insert('c_forum_thread_qualify', [
            'user_id' => $userId,
            'thread_id' => $threadId,
            'qualify_user_id' => $graderId,
            'c_id' => $courseId,
            'qualify' => $score,
            'qualify_time' => self::T0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function courseStudent(int $userId, int $courseId): void
    {
        $this->conn->insert('course_rel_user', [
            'c_id' => $courseId,
            'user_id' => $userId,
            'status' => 5,
            'relation_type' => 0,
            'sort' => 0, 'progress' => 0,
            'user_course_cat' => 0,
            'legal_agreement' => 0,
        ]);
    }

    /** @return list<array<string, mixed>> */
    private function rows($result): array
    {
        $out = [];
        while ($row = \Database::fetch_assoc($result)) {
            $out[] = $row;
        }

        return $out;
    }

    private function ids(array $rows, string $key = 'id'): array
    {
        return array_map(static fn (array $r) => (int) $r[$key], $rows);
    }

    private function titles(array $entities): array
    {
        $t = array_map(static fn ($e) => $e->getTitle(), $entities);
        sort($t);

        return $t;
    }

    // ------------------------------------------------- thread participants

    public function testThreadUsersDetailsListsOnlyValidatedStudentPostersOfTheCourse(): void
    {
        $this->setContext(1, 1);
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $this->courseStudent(2, 1);
        // admin is a teacher of course 1 (fixture) and anon a student: only the student is a participant.
        $this->post($thread, $forum, 2);
        $this->post($thread, $forum, 1);

        $this->assertSame([2], $this->ids($this->rows(get_thread_users_details($thread))));
    }

    public function testThreadUsersDetailsIgnoresPostsWaitingForModerationAndOtherCourses(): void
    {
        $this->setContext(1, 1);
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $this->courseStudent(2, 2);
        $this->post($thread, $forum, 2, ['status' => CForumPost::STATUS_WAITING_MODERATION]);

        // Not a student of course 1, and its only post is not validated.
        $this->assertSame([], $this->rows(get_thread_users_details($thread)));

        $this->courseStudent(2, 1);
        $this->assertSame([], $this->rows(get_thread_users_details($thread)), 'a post waiting for moderation does not count');

        $this->post($thread, $forum, 2, ['status' => null]);
        $rows = $this->rows(get_thread_users_details($thread));
        $this->assertSame([2], $this->ids($rows), 'a NULL status counts as validated');
        $this->assertSame($thread, (int) $rows[0]['thread_id']);
    }

    public function testThreadUsersDetailsInSessionUsesTheSessionSubscriptions(): void
    {
        $sid = $this->sessionRow();
        $this->setContext(1, 1, $sid);
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $this->post($thread, $forum, 2);
        // Subscribed to the base course only: not a participant of the session.
        $this->courseStudent(2, 1);
        $this->assertSame([], $this->rows(get_thread_users_details($thread)));

        $this->conn->insert('session_rel_course_rel_user', [
            'user_id' => 2, 'session_id' => $sid, 'c_id' => 1, 'status' => 0,
            'visibility' => 1, 'legal_agreement' => 0, 'progress' => 0,
        ]);
        $this->assertSame([2], $this->ids($this->rows(get_thread_users_details($thread))));
    }

    public function testThreadUsersQualifyAndNotQualifySplitTheStudentsByGrade(): void
    {
        $this->setContext(1, 1);
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $this->courseStudent(2, 1);
        $this->post($thread, $forum, 2);

        $notQualified = $this->rows(get_thread_users_not_qualify($thread));
        $this->assertSame([2], $this->ids($notQualified));
        $this->assertSame([], $this->rows(get_thread_users_qualify($thread)));

        $this->qualify($thread, 2, 1, 7.5);
        $this->assertSame([], $this->rows(get_thread_users_not_qualify($thread)));
        $qualified = $this->rows(get_thread_users_qualify($thread));
        $this->assertSame([2], $this->ids($qualified));
        $this->assertSame(7.5, (float) $qualified[0]['qualify']);
    }

    public function testThreadUsersQualifyOnlyKeepsTheQualificationsOfTheCurrentCourse(): void
    {
        $this->setContext(1, 1);
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $this->courseStudent(2, 1);
        $this->post($thread, $forum, 2);
        $this->qualify($thread, 2, 2, 3.0); // recorded for another course (c_id = 2)

        $this->assertSame([], $this->rows(get_thread_users_qualify($thread)));
        // PINNED QUIRK: "not qualify" does not look at c_id, so a grade of another course already removes the
        // student from the "to grade" list.
        $this->assertSame([], $this->rows(get_thread_users_not_qualify($thread)));
    }

    public function testThreadUsersQualifyInSessionWithoutCoachDoesNotBreakTheQuery(): void
    {
        $sid = $this->sessionRow();
        $this->setContext(1, 1, $sid);
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $this->post($thread, $forum, 2);
        $this->conn->insert('session_rel_course_rel_user', [
            'user_id' => 2, 'session_id' => $sid, 'c_id' => 1, 'status' => 0,
            'visibility' => 1, 'legal_agreement' => 0, 'progress' => 0,
        ]);
        $this->qualify($thread, 2, 1, 5.0);

        // A session with no general coach and no session admin used to build "NOT IN ()", a SQL error.
        $this->assertSame([2], $this->ids($this->rows(get_thread_users_qualify($thread))));
        $this->conn->delete('c_forum_thread_qualify', ['thread_id' => $thread]);
        $this->assertSame([2], $this->ids($this->rows(get_thread_users_not_qualify($thread))));
    }

    // --------------------------------------------------------------- counts

    public function testCountNumberOfForumsInCategory(): void
    {
        $cat = $this->category(1);
        $this->forum(1, $cat);
        $this->forum(1, $cat);
        $this->forum(2, $cat);
        $other = $this->category(1);
        $this->forum(1, $other);

        $this->setContext(1, 1);
        // PINNED QUIRK: not scoped by course (the category id is already unique), and it returns a numeric string.
        $this->assertEquals(3, count_number_of_forums_in_category($cat));
        $this->assertEquals(1, count_number_of_forums_in_category($other));
        $this->assertEquals(0, count_number_of_forums_in_category(999999));
    }

    public function testCountNumberOfPostsInThreadCountsHiddenPostsToo(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $this->post($thread, $forum, 1);
        $this->post($thread, $forum, 2, ['visible' => 0]);
        $this->post($this->thread($forum), $forum, 2);

        $this->setContext(1, 1);
        $this->assertEquals(2, count_number_of_post_in_thread($thread));
        $this->assertSame(0, count_number_of_post_in_thread(999999));

        \ChamiloSession::erase('_real_cid');
        $this->assertSame(0, count_number_of_post_in_thread($thread), 'no course context, no count');
    }

    public function testCountNumberOfPostsForUserInThreadOnlyCountsVisiblePosts(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $this->post($thread, $forum, 2);
        $this->post($thread, $forum, 2);
        $this->post($thread, $forum, 2, ['visible' => 0]);
        $this->post($thread, $forum, 1);

        $this->assertEquals(2, count_number_of_post_for_user_thread($thread, 2));
        $this->assertEquals(1, count_number_of_post_for_user_thread($thread, 1));
        $this->assertEquals(0, count_number_of_post_for_user_thread($thread, 3));
    }

    public function testUnapprovedMessagesListsThreadsWithAnInvisiblePost(): void
    {
        $forum = $this->forum(1);
        $t1 = $this->thread($forum);
        $t2 = $this->thread($forum);
        $this->post($t1, $forum, 2, ['visible' => 0]);
        $this->post($t1, $forum, 2, ['visible' => 0]);
        $this->post($t2, $forum, 2);

        $this->setContext(1, 1);
        // PINNED QUIRK: filters on c_forum_post.c_id, a column that no longer exists, so it always throws.
        $this->expectException(Throwable::class);
        get_unaproved_messages($forum);
    }

    // ------------------------------------------------------ user posts

    public function testThreadUserPostReturnsThePostsFollowedByTheirReplies(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $mine = $this->post($thread, $forum, 2);
        $reply2 = $this->post($thread, $forum, 1, ['post_parent_id' => $mine]);
        $reply1 = $this->post($thread, $forum, 1, ['post_parent_id' => $mine]);
        $this->post($thread, $forum, 1); // unrelated post of another user

        $list = get_thread_user_post($this->getCourse(1), $thread, 2);

        $this->assertSame([$mine, $reply2, $reply1], array_map(static fn ($r) => (int) $r['iid'], $list));
        $this->assertSame(['1', '0', '0'], array_column($list, 'status'), 'own post "1", replies "0"');
    }

    public function testThreadUserPostLimitReturnsTheLatestPostsFirst(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $first = $this->post($thread, $forum, 2);
        $second = $this->post($thread, $forum, 2);
        $this->post($thread, $forum, 1);

        // PINNED QUIRK: orders by posts.post_id, a column c_forum_post no longer has, so it always throws.
        $this->expectException(Throwable::class);
        get_thread_user_post_limit(1, $thread, 2, 10);
    }

    public function testGetNameThreadById(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum, null, null, ['title' => 'fm_named_thread']);

        $this->assertSame('fm_named_thread', get_name_thread_by_id($thread));
    }

    // ------------------------------------------------------- thread info

    public function testUpdateThreadInfoIncrementsRepliesAndMovesTheLastPost(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum, null, null, ['thread_replies' => 2]);
        $post = $this->post($thread, $forum, 2);

        updateThreadInfo($thread, $post, '2021-02-03 04:05:06');

        $row = $this->conn->fetchAssociative('SELECT thread_replies, thread_last_post, thread_date FROM c_forum_thread WHERE iid = ?', [$thread]);
        $this->assertSame(3, (int) $row['thread_replies']);
        $this->assertSame($post, (int) $row['thread_last_post']);
        $this->assertSame('2021-02-03 04:05:06', $row['thread_date']);
    }

    public function testLastPostOfThreadIsTheMostRecentOne(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $this->post($thread, $forum, 2, ['post_date' => '2020-01-01 10:00:00']);
        $latest = $this->post($thread, $forum, 2, ['post_date' => '2020-03-01 10:00:00']);
        $this->post($thread, $forum, 2, ['post_date' => '2020-02-01 10:00:00']);

        $last = getLastPostOfThread($thread);
        $this->assertSame($latest, $last['iid']);
        $this->assertSame('2020-03-01 10:00:00', $last['post_date']);
        $this->assertSame([], getLastPostOfThread(999999));
    }

    public function testDeletePostRepairsTheThreadCountersAndDeletesTheEmptyThread(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum, null, null, ['thread_replies' => 1]);
        $p1 = $this->post($thread, $forum, 2, ['post_date' => '2020-01-01 10:00:00']);
        $p2 = $this->post($thread, $forum, 2, ['post_date' => '2020-02-01 10:00:00']);
        $this->setContext(1, 1);

        deletePost($this->getEntityManager()->find(CForumPost::class, $p2));
        $row = $this->conn->fetchAssociative('SELECT thread_replies, thread_last_post, thread_date FROM c_forum_thread WHERE iid = ?', [$thread]);
        $this->assertSame(0, (int) $row['thread_replies']);
        $this->assertSame($p1, (int) $row['thread_last_post']);
        $this->assertSame('2020-01-01 10:00:00', $row['thread_date']);

        $this->getEntityManager()->clear();
        deletePost($this->getEntityManager()->find(CForumPost::class, $p1));
        $this->assertFalse($this->conn->fetchOne('SELECT iid FROM c_forum_thread WHERE iid = ?', [$thread]), 'the thread goes with its last post');
    }

    // ----------------------------------------------------- qualifications

    private function thread_entity(int $threadId): CForumThread
    {
        return $this->getEntityManager()->find(CForumThread::class, $threadId);
    }

    public function testSaveThreadScoreInsertsThenUpdatesAndArchivesTheOldScore(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum, null, null, ['thread_qualify_max' => 10]);
        $this->setContext(1, 1);

        $this->assertSame('insert', saveThreadScore($this->thread_entity($thread), 2, $thread, 6, self::T0, 0));
        $this->assertEquals(6, showQualify(1, 2, $thread));

        $this->assertSame('update', saveThreadScore($this->thread_entity($thread), 2, $thread, 8, '2020-02-02 10:00:00', 0));
        $this->assertEquals(8, showQualify(1, 2, $thread));
        $this->assertEquals(8, current_qualify_of_thread($thread, 0, 2));

        $log = getThreadScoreHistory(2, $thread, 'false');
        $this->assertCount(1, $log);
        $this->assertSame(6.0, (float) $log[0]['qualify'], 'the replaced score is archived');
        $this->assertSame(self::T0, $log[0]['qualify_time']);
        $this->assertSame(1, (int) $log[0]['c_id']);
    }

    public function testSaveThreadScoreRefusesAScoreAboveTheMaximum(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum, null, null, ['thread_qualify_max' => 10]);
        $this->setContext(1, 1);

        $this->assertSame('', saveThreadScore($this->thread_entity($thread), 2, $thread, 10.5, self::T0, 0));
        $this->assertSame(0, showQualify(1, 2, $thread));
        $this->assertSame('insert', saveThreadScore($this->thread_entity($thread), 2, $thread, 10, self::T0, 0), 'the maximum itself is accepted');
    }

    public function testShowQualifyReadsTheMaximumOrTheScoreAndDefaultsToZero(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum, null, null, ['thread_qualify_max' => 12]);
        $this->qualify($thread, 2, 1, 4.5);
        $this->qualify($thread, 1, 2, 9.0); // other course

        $this->setContext(1, 1);
        $this->assertEquals(4.5, showQualify(1, 2, $thread));
        $this->assertEquals(12, showQualify(2, 2, $thread));
        $this->assertSame(0, showQualify(1, 1, $thread), 'a score of another course is not shown');
        $this->assertSame(0, showQualify(1, 0, $thread));
        $this->assertSame(0, showQualify(3, 2, $thread));
    }

    public function testCurrentQualifyOfThreadIsTheOneGivenByTheCurrentUser(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $this->qualify($thread, 2, 1, 3.0, 2);
        $this->setContext(1, 1);

        // PINNED QUIRK: a grade given by someone else is invisible, so a second teacher sees 0.
        $this->assertSame(0, current_qualify_of_thread($thread, 0, 2));
        $this->qualify($thread, 2, 1, 5.0, 1);
        $this->assertEquals(5, current_qualify_of_thread($thread, 0, 2));
    }

    public function testThreadScoreHistoryIsOrderedByTimeAndScopedByCourse(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        foreach ([[1, 'b', '2020-02-01 00:00:00'], [1, 'a', '2020-01-01 00:00:00'], [2, 'c', '2020-03-01 00:00:00']] as [$cid, , $time]) {
            $this->conn->insert('c_forum_thread_qualify_log', [
                'c_id' => $cid, 'user_id' => 2, 'thread_id' => $thread, 'qualify' => 1, 'qualify_user_id' => 1, 'qualify_time' => $time,
            ]);
        }
        $this->setContext(1, 1);

        $asc = array_column(getThreadScoreHistory(2, $thread, 'false'), 'qualify_time');
        $this->assertSame(['2020-01-01 00:00:00', '2020-02-01 00:00:00'], $asc);
        $desc = array_column(getThreadScoreHistory(2, $thread, 'true'), 'qualify_time');
        $this->assertSame(['2020-02-01 00:00:00', '2020-01-01 00:00:00'], $desc);
    }

    // -------------------------------------------------------- notifications

    public function testSetNotificationTogglesTheSubscription(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $this->setContext(1, 1);

        $this->assertNotEmpty(set_notification('thread', $thread, false, $this->u1()));
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_forum_notification WHERE thread_id = ? AND user_id = 1 AND c_id = 1', [$thread]));

        // addOnly never unsubscribes.
        set_notification('thread', $thread, true, $this->u1());
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_forum_notification WHERE thread_id = ?', [$thread]));

        set_notification('thread', $thread, false, $this->u1());
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_forum_notification WHERE thread_id = ?', [$thread]));
    }

    public function testSetNotificationForAForumStoresTheForumIdAndRejectsEmptyInput(): void
    {
        $forum = $this->forum(1);
        $this->setContext(1, 1);

        set_notification('forum', $forum, false, $this->u1());
        $row = $this->conn->fetchAssociative('SELECT forum_id, thread_id FROM c_forum_notification WHERE forum_id = ?', [$forum]);
        $this->assertSame($forum, (int) $row['forum_id']);
        $this->assertSame(0, (int) $row['thread_id']);

        $this->assertFalse(set_notification('forum', 0, false, $this->u1()));
        $this->assertFalse(set_notification('', $forum, false, $this->u1()));
    }

    public function testGetNotificationsListsActiveSubscribersOfTheCurrentCourse(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $this->setContext(1, 1);
        foreach ([[1, 1], [2, 1], [3, 1]] as [$uid, $cid]) {
            $this->conn->insert('c_forum_notification', ['c_id' => $cid, 'user_id' => $uid, 'forum_id' => 0, 'thread_id' => $thread, 'post_id' => 0]);
        }
        $this->conn->insert('c_forum_notification', ['c_id' => 2, 'user_id' => 2, 'forum_id' => 0, 'thread_id' => $thread, 'post_id' => 0]);

        $list = get_notifications('thread', $thread);
        // user 3 is soft deleted (active = -2): not notified.
        $this->assertSame(['user1', 'user2'], array_keys($list));
        $this->assertSame(1, (int) $list['user1']['user_id']);
        $this->assertSame([], get_notifications('forum', $forum));

        $this->setContext(1, 2);
        $this->assertSame(['user2'], array_keys(get_notifications('thread', $thread)));
    }

    public function testGetNotificationsPerUserCachesTheForumsAndThreadsInSession(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $this->setContext(1, 1);
        $this->conn->insert('c_forum_notification', ['c_id' => 1, 'user_id' => 1, 'forum_id' => $forum, 'thread_id' => 0, 'post_id' => 0]);
        $this->conn->insert('c_forum_notification', ['c_id' => 1, 'user_id' => 1, 'forum_id' => 0, 'thread_id' => $thread, 'post_id' => 0]);

        getNotificationsPerUser(0, true);

        $this->assertSame(1, $_SESSION['forum_notification']['course']);
        // PINNED QUIRK: the 0 left in the unused column is not NULL, so it is stored as a forum/thread id.
        $this->assertEqualsCanonicalizing([$forum, 0], array_map('intval', $_SESSION['forum_notification']['forum']));
        $this->assertEqualsCanonicalizing([$thread, 0], array_map('intval', $_SESSION['forum_notification']['thread']));
    }

    // ----------------------------------------------------------- mail queue

    public function testHandleMailCueForAThreadClearsOnlyTheMailQueueOfTheCurrentCourse(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $this->setContext(1, 1);
        $this->conn->insert('c_forum_mailcue', ['c_id' => 1, 'thread_id' => $thread, 'user_id' => 2, 'post_id' => 0]);
        $this->conn->insert('c_forum_mailcue', ['c_id' => 2, 'thread_id' => $thread, 'user_id' => 2, 'post_id' => 0]);

        handle_mail_cue('thread', $thread);

        $this->assertSame([2], array_map('intval', $this->conn->fetchFirstColumn('SELECT c_id FROM c_forum_mailcue WHERE thread_id = ?', [$thread])));
        $this->assertNotEmpty(handle_mail_cue('unknown', 1), 'unknown content type returns an error message');
    }

    public function testHandleMailCueForAForumWalksItsThreads(): void
    {
        $forum = $this->forum(1);
        $t1 = $this->thread($forum);
        $t2 = $this->thread($forum);
        $other = $this->thread($this->forum(1));
        $this->setContext(1, 1);
        foreach ([$t1, $t2, $other] as $t) {
            $this->conn->insert('c_forum_mailcue', ['c_id' => 1, 'thread_id' => $t, 'user_id' => 2, 'post_id' => 0]);
        }

        handle_mail_cue('forum', $forum);

        $this->assertSame([$other], array_map('intval', $this->conn->fetchFirstColumn('SELECT thread_id FROM c_forum_mailcue WHERE thread_id IN (?, ?, ?)', [$t1, $t2, $other])));
    }

    public function testHandleMailCueForACategoryWalksItsForums(): void
    {
        $cat = $this->category(1);
        $forum = $this->forum(1, $cat);
        $thread = $this->thread($forum);
        $this->setContext(1, 1);
        $this->conn->insert('c_forum_mailcue', ['c_id' => 1, 'thread_id' => $thread, 'user_id' => 2, 'post_id' => 0]);

        handle_mail_cue('forum_category', $cat);

        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_forum_mailcue WHERE thread_id = ?', [$thread]));
    }

    // ------------------------------------------------------ moving a thread

    public function testStoreMoveThreadMovesTheThreadAndItsPosts(): void
    {
        $from = $this->forum(1);
        $to = $this->forum(1);
        $thread = $this->thread($from);
        $p = $this->post($thread, $from, 2);
        $stay = $this->post($this->thread($from), $from, 2);
        $this->setContext(1, 1);
        $_POST['forum'] = (string) $to;
        $_POST['thread_id'] = (string) $thread;

        $this->assertNotEmpty(store_move_thread([]));

        $this->assertSame($to, (int) $this->conn->fetchOne('SELECT forum_id FROM c_forum_thread WHERE iid = ?', [$thread]));
        $this->assertSame($to, (int) $this->conn->fetchOne('SELECT forum_id FROM c_forum_post WHERE iid = ?', [$p]));
        $this->assertSame($from, (int) $this->conn->fetchOne('SELECT forum_id FROM c_forum_post WHERE iid = ?', [$stay]));
    }

    // ------------------------------------------------------------ attachments

    public function testAttachmentHelpersAreScopedByCourseAndPost(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $post = $this->post($thread, $forum, 2);
        $ids = [];
        foreach ([[1, 'fm_a.txt'], [1, 'fm_b.txt'], [2, 'fm_c.txt']] as [$cid, $name]) {
            $this->conn->insert('c_forum_attachment', [
                'post_id' => $post, 'c_id' => $cid, 'path' => $name, 'comment' => 'cmt', 'size' => 10, 'filename' => $name,
            ]);
            $ids[$name] = (int) $this->conn->lastInsertId();
        }
        $this->setContext(1, 1);

        $this->assertSame([$ids['fm_a.txt'], $ids['fm_b.txt']], array_map('intval', getAttachmentIdsByPostId($post)));
        $this->assertSame([$ids['fm_c.txt']], array_map('intval', getAttachmentIdsByPostId($post, 2)));
        $this->assertSame([], getAttachmentIdsByPostId($post, 0 === 1 ? 1 : 3));

        $first = get_attachment($post);
        $this->assertSame('fm_a.txt', $first['filename']);
        $this->assertSame('cmt', $first['comment']);
        $this->assertSame([], get_attachment(0));
    }

    public function testEditAttachedFileUpdatesOnlyTheAttachmentOfTheCourse(): void
    {
        $forum = $this->forum(1);
        $post = $this->post($this->thread($forum), $forum, 2);
        $this->conn->insert('c_forum_attachment', ['post_id' => $post, 'c_id' => 1, 'path' => 'fm_p', 'comment' => 'old', 'size' => 1, 'filename' => 'fm_p']);
        $att = (int) $this->conn->lastInsertId();
        $this->setContext(1, 1);

        $this->assertSame(0, editAttachedFile(['comment' => 'x'], $att, 2), 'wrong course: nothing is updated');
        $this->assertSame(1, editAttachedFile(['comment' => 'new "quoted"'], $att));
        $this->assertSame('new "quoted"', $this->conn->fetchOne('SELECT comment FROM c_forum_attachment WHERE iid = ?', [$att]));
        $this->assertSame(0, editAttachedFile([], $att));
        $this->assertSame(0, editAttachedFile(['comment' => 'x'], 0));
    }

    // ----------------------------------------------- entity based read helpers

    public function testGetForumCategoriesAreOrderedAndScopedToTheCourseAndSession(): void
    {
        $this->setContext(1, 1);
        $before = $this->titles(get_forum_categories(1));
        $second = $this->category(1, null, 2, 5);
        $first = $this->category(1, null, 2, 4);
        $this->category(2);
        $this->category(1, null, 0); // invisible
        $sid = $this->sessionRow();
        $this->category(1, $sid);

        $all = get_forum_categories(1);
        $titles = array_map(static fn ($c) => $c->getTitle(), $all);
        $iids = array_map(static fn ($c) => $c->getIid(), $all);
        $this->assertCount(count($before) + 2, $all, 'other course, invisible and session categories are left out');
        $this->assertLessThan(array_search($second, $iids, true), array_search($first, $iids, true), 'ordered by display_order');
        $this->assertNotContains('fm_cat_', $titles);
    }

    public function testGetForumsInCategoryAndGetForumsFilterOnCourseCategoryAndVisibility(): void
    {
        $this->setContext(1, 1);
        $cat = $this->category(1);
        $a = $this->forum(1, $cat);
        $this->forum(1, $cat, null, 0); // invisible
        $this->forum(2, $cat);
        $this->forum(1); // no category

        $inCat = get_forums_in_category($cat, 1);
        $this->assertSame([$a], array_map(static fn ($f) => $f->getIid(), $inCat));

        $course1 = array_map(static fn ($f) => $f->getIid(), get_forums(1));
        $this->assertContains($a, $course1);
        $this->assertCount(2, array_filter($course1, fn ($i) => $this->conn->fetchOne('SELECT title FROM c_forum_forum WHERE iid = ?', [$i]) !== false && str_starts_with((string) $this->conn->fetchOne('SELECT title FROM c_forum_forum WHERE iid = ?', [$i]), 'fm_forum_')), 'visible forums of course 1 only');
    }

    public function testGetThreadsListsAndCountsTheThreadsOfAForum(): void
    {
        $this->setContext(1, 1);
        $forum = $this->forum(1);
        $t1 = $this->thread($forum, 1);
        $this->thread($forum, 2); // linked to another course
        $this->thread($this->forum(1), 1);
        $sid = $this->sessionRow();
        $tSession = $this->thread($forum, 1, $sid);

        $this->assertSame([$t1], array_map(static fn ($t) => $t->getIid(), get_threads($forum, 1)));
        $this->assertSame(1, get_threads($forum, 1, null, true));
        $inSession = array_map(static fn ($t) => $t->getIid(), get_threads($forum, 1, $sid));
        $this->assertContains($tSession, $inSession);
        $this->assertContains($t1, $inSession, 'base course threads stay visible in a session');
    }

    public function testThreadCountBatchCountsPerForum(): void
    {
        $this->setContext(1, 1);
        $f1 = $this->forum(1);
        $f2 = $this->forum(1);
        $f3 = $this->forum(1);
        $this->thread($f1, 1);
        $this->thread($f1, 1);
        $this->thread($f2, 1);
        $this->thread($f2, 2);

        $this->assertSame([$f1 => 2, $f2 => 1], getThreadCountBatch([$f1, $f2, $f3], 1));
        $this->assertSame([], getThreadCountBatch([]));
    }

    public function testCountPostsWithStatusBatchOnlyCountsVisiblePostsOfTheGivenForums(): void
    {
        $f1 = $this->forum(1);
        $f2 = $this->forum(1);
        $t1 = $this->thread($f1);
        $t2 = $this->thread($f2);
        $w = CForumPost::STATUS_WAITING_MODERATION;
        $this->post($t1, $f1, 2, ['status' => $w]);
        $this->post($t1, $f1, 2, ['status' => $w]);
        $this->post($t1, $f1, 2, ['status' => $w, 'visible' => 0]);
        $this->post($t1, $f1, 2);
        $this->post($t2, $f2, 2, ['status' => $w]);

        $this->assertSame([$f1 => 2], getCountPostsWithStatusBatch($w, [$f1]));
        $this->assertSame([$f1 => 2, $f2 => 1], getCountPostsWithStatusBatch($w, [$f1, $f2]));
    }

    public function testCountPostsWithStatusIsRestrictedToTheGivenForumAndThread(): void
    {
        $f1 = $this->forum(1);
        $f2 = $this->forum(1);
        $t1 = $this->thread($f1);
        $t2 = $this->thread($f2);
        $w = CForumPost::STATUS_WAITING_MODERATION;
        $this->post($t1, $f1, 2, ['status' => $w]);
        $this->post($t2, $f2, 2, ['status' => $w]);
        $this->post($t2, $f2, 2, ['status' => $w, 'visible' => 0]);
        $forum1 = $this->getEntityManager()->find(CForum::class, $f1);

        $this->assertSame(1, (int) getCountPostsWithStatus($w, $forum1, $t1));
        // PINNED QUIRK: the $forum argument is ignored, so a thread of another forum is still counted, and without
        // a thread this counts the waiting posts of every forum of the platform.
        $this->assertSame(1, (int) getCountPostsWithStatus($w, $forum1, $t2));
        $this->assertGreaterThanOrEqual(2, (int) getCountPostsWithStatus($w, $forum1));
    }

    public function testGetForumsOfGroupReturnsOnlyTheForumsLinkedToTheGroup(): void
    {
        $this->setContext(1, 1);
        $groupId = (int) $this->conn->fetchOne('SELECT iid FROM c_group_info LIMIT 1');
        if (0 === $groupId) {
            $this->conn->insert('c_group_info', ['title' => 'fm_group', 'status' => 1, 'doc_state' => 1, 'calendar_state' => 1, 'work_state' => 1, 'announcements_state' => 1, 'forum_state' => 1, 'wiki_state' => 1, 'chat_state' => 1, 'self_registration_allowed' => 0, 'self_unregistration_allowed' => 0, 'max_student' => 0]);
            $groupId = (int) $this->conn->lastInsertId();
        }
        $group = $this->getEntityManager()->find(CGroup::class, $groupId);
        $this->assertNotNull($group);
        $forum = $this->forum(1);
        $linkId = null;
        $node = (int) $this->conn->fetchOne('SELECT resource_node_id FROM c_forum_forum WHERE iid = ?', [$forum]);
        $this->conn->update('resource_link', ['group_id' => $groupId], ['resource_node_id' => $node]);
        $plain = $this->forum(1);

        $ids = array_map(static fn ($f) => $f->getIid(), get_forums_of_group($group));
        $this->assertContains($forum, $ids);
        $this->assertNotContains($plain, $ids);
    }

    public function testForumCreatedByUserListsTheForumsOfTheCreatorWithTheirDates(): void
    {
        $forum = $this->forum(1);
        $other = $this->forum(1);
        $node = (int) $this->conn->fetchOne('SELECT resource_node_id FROM c_forum_forum WHERE iid = ?', [$other]);
        $this->conn->update('resource_node', ['creator_id' => 2], ['id' => $node]);
        $this->setContext(1, 1);
        $info = api_get_course_info($this->getCourse(1)->getCode());

        $mine = getForumCreatedByUser(1, $info, 0);
        $titles = array_column($mine, 0);
        $this->assertContains($this->conn->fetchOne('SELECT title FROM c_forum_forum WHERE iid = ?', [$forum]), $titles);
        $this->assertNotContains($this->conn->fetchOne('SELECT title FROM c_forum_forum WHERE iid = ?', [$other]), $titles);
        $this->assertCount(3, $mine[0]);
        $this->assertSame([], getForumCreatedByUser(0, $info, 0));
        $this->assertSame([], getForumCreatedByUser(1, [], 0));
    }

    public function testGetForumToolUrl(): void
    {
        $node = $this->courseNode(1);
        $base = api_get_path(WEB_PATH).'resources/forum/'.$node.'/';

        $this->assertSame($base.'?cid=1&sid=0&gid=0', getForumToolUrl(1));
        $this->assertSame($base.'forum/7?cid=1&sid=3&gid=2', getForumToolUrl(1, 3, 2, 7));
        $this->assertSame($base.'forum/7/thread/9?cid=1&sid=0&gid=0&origin=group', getForumToolUrl(1, 0, 0, 7, 9, ['origin' => 'group']));
        // A thread without its forum is ignored.
        $this->assertSame($base.'?cid=1&sid=0&gid=0', getForumToolUrl(1, 0, 0, 0, 9));
    }

    public function testVisibleForumsInASessionKeepBaseCourseForumsOnlyWhenTheyHaveSessionThreads(): void
    {
        $sid = $this->sessionRow();
        $this->setContext(1, 1, $sid);
        $inSession = $this->forum(1, null, $sid);
        $baseWithThread = $this->forum(1);
        $baseEmpty = $this->forum(1);
        $this->thread($baseWithThread, 1, $sid);

        $ids = array_map(static fn ($f) => $f->getIid(), getVisibleForums(1, $sid));
        $this->assertContains($inSession, $ids);
        $this->assertContains($baseWithThread, $ids);
        $this->assertNotContains($baseEmpty, $ids);

        $noSession = array_map(static fn ($f) => $f->getIid(), getVisibleForums(1, 0));
        $this->assertContains($baseEmpty, $noSession);
    }
}
