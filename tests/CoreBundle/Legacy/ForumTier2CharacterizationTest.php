<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Uid\Uuid;

/**
 * Second pass over forumfunction.inc.php: the functions and branches ForumCharacterizationTest leaves unpinned.
 * Not covered on purpose: getAttachedFiles() has no caller; get_thread_users_details() / get_thread_users_qualify()
 * have no caller either and return a statement handle.
 */
final class ForumTier2CharacterizationTest extends KernelTestCase
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
        ChamiloSession::erase('login_as');
        ChamiloSession::erase('studentview');
        ChamiloSession::erase('groupId');
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
        ChamiloSession::write('_user', ['id' => $userId]);
        $course = $this->getCourse($courseId);
        ChamiloSession::write('_real_cid', $courseId);
        ChamiloSession::write('_cid', $course->getCode());
        ChamiloSession::write('_course', api_get_course_info($course->getCode()));
        ChamiloSession::write('sid', $sessionId);
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

    /**
     * @return int category iid
     */
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

    /**
     * @return int forum iid
     */
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

    /**
     * @return int thread iid
     */
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

    /**
     * @return int post iid
     */
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
            // Quoted: QUALIFY is a reserved word in MySQL 8.4 (not in MariaDB).
            '`qualify`' => $score,
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

    /**
     * @param mixed $result
     *
     * @return list<array<string, mixed>>
     */
    private function rows($result): array
    {
        $out = [];
        while ($row = Database::fetch_assoc($result)) {
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

    // ---------------------------------------------------------------- tests

    public function testSaveThreadStoresTheFirstPostAsTheLastPostOfTheThread(): void
    {
        $forum = $this->forum(1);
        $this->setContext(1, 1);
        $admin = $this->getUser('admin');
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken($admin, 'main', $admin->getRoles()));
        $entity = $this->getEntityManager()->find(CForum::class, $forum);

        $thread = saveThread($entity, ['post_title' => 'fm2_thread', 'post_text' => 'body', 'forum_id' => $forum], [], false);

        self::assertInstanceOf(CForumThread::class, $thread);
        $row = $this->conn->fetchAssociative('SELECT thread_last_post FROM c_forum_thread WHERE iid = ?', [$thread->getIid()]);
        $postId = (int) $this->conn->fetchOne('SELECT iid FROM c_forum_post WHERE thread_id = ?', [$thread->getIid()]);
        self::assertGreaterThan(0, $postId);
        self::assertSame($postId, (int) $row['thread_last_post']);
    }

    public function testThreadPreviewListsTheVisibleValidatedPostsOldestFirstWithTheirAuthor(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $this->post($thread, $forum, 2, ['title' => 'fm2_second', 'post_date' => '2020-03-01 10:00:00']);
        $this->post($thread, $forum, 1, ['title' => 'fm2_first', 'post_date' => '2020-02-01 10:00:00']);
        $this->post($thread, $forum, 1, ['title' => 'fm2_hidden', 'visible' => 0]);
        $this->post($thread, $forum, 1, ['title' => 'fm2_waiting', 'status' => CForumPost::STATUS_WAITING_MODERATION]);
        $this->post($thread, $forum, 1, ['title' => 'fm2_nostatus', 'status' => null, 'post_date' => '2020-04-01 10:00:00']);
        $this->post($this->thread($forum), $forum, 1, ['title' => 'fm2_other_thread']);
        $this->setContext(1, 1);

        $html = render_thread_preview_html($forum, $thread, 0, 20);

        self::assertLessThan(strpos($html, 'fm2_second'), strpos($html, 'fm2_first'));
        self::assertLessThan(strpos($html, 'fm2_nostatus'), strpos($html, 'fm2_second'));
        foreach (['fm2_hidden', 'fm2_waiting', 'fm2_other_thread'] as $absent) {
            self::assertStringNotContainsString($absent, $html);
        }

        $limited = render_thread_preview_html($forum, $thread, 0, 1);
        self::assertStringContainsString('fm2_first', $limited);
        self::assertStringNotContainsString('fm2_second', $limited);
    }

    public function testSendNotificationMailsQueuesTheSubscribersWhenTheThreadIsNotVisible(): void
    {
        $forum = $this->forum(1, null, null, 2, ['approval_direct_post' => '1']);
        $thread = $this->thread($forum);
        $otherThread = $this->thread($forum);
        $this->setContext(1, 1);
        $this->conn->insert('c_forum_notification', ['c_id' => 1, 'user_id' => 2, 'forum_id' => $forum, 'thread_id' => 0, 'post_id' => 0]);
        $this->conn->insert('c_forum_notification', ['c_id' => 1, 'user_id' => 3, 'forum_id' => 0, 'thread_id' => $thread, 'post_id' => 0]);
        $this->conn->insert('c_forum_notification', ['c_id' => 1, 'user_id' => 4, 'forum_id' => 0, 'thread_id' => $otherThread, 'post_id' => 0]);
        $this->conn->insert('c_forum_notification', ['c_id' => 2, 'user_id' => 5, 'forum_id' => $forum, 'thread_id' => 0, 'post_id' => 0]);

        send_notification_mails(
            $this->getEntityManager()->find(CForum::class, $forum),
            $this->getEntityManager()->find(CForumThread::class, $thread),
            ['new_post_id' => 77]
        );

        $rows = $this->conn->fetchAllAssociative('SELECT c_id, thread_id, post_id, user_id FROM c_forum_mailcue WHERE thread_id = ?', [$thread]);
        self::assertCount(2, $rows, 'one row per matching notification of this course (forum or thread)');
        foreach ($rows as $row) {
            self::assertSame(1, (int) $row['c_id']);
            self::assertSame(77, (int) $row['post_id']);
            self::assertSame(1, (int) $row['user_id']);
        }
        self::assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_forum_mailcue WHERE thread_id = ?', [$otherThread]));
    }

    public function testDeletePostOfAThreadWithoutRepliesKeepsTheCounterAtZero(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum, null, null, ['thread_replies' => 0]);
        $p1 = $this->post($thread, $forum, 2, ['post_date' => '2020-01-01 10:00:00']);
        $p2 = $this->post($thread, $forum, 2, ['post_date' => '2020-02-01 10:00:00']);
        $this->setContext(1, 1);

        deletePost($this->getEntityManager()->find(CForumPost::class, $p2));

        $row = $this->conn->fetchAssociative('SELECT thread_replies, thread_last_post FROM c_forum_thread WHERE iid = ?', [$thread]);
        self::assertSame(0, (int) $row['thread_replies'], 'never negative (unsigned column)');
        self::assertSame($p1, (int) $row['thread_last_post']);
    }

    public function testSaveThreadScoreInPeerModeCountsOnlyTheScoresOfTheCurrentGrader(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum, null, null, ['thread_qualify_max' => 10, 'thread_peer_qualify' => 1]);
        $this->qualify($thread, 2, 1, 4, 3); // graded by someone else
        $this->setContext(1, 1);

        $result = saveThreadScore($this->getEntityManager()->find(CForumThread::class, $thread), 2, $thread, 6, self::T0, 0);

        self::assertSame('insert', $result, 'the grade of another grader does not count in peer mode');
        self::assertSame(2, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM c_forum_thread_qualify WHERE thread_id = ? AND user_id = 2', [$thread]));
    }

    public function testThreadUserPostListsTheOwnPostsOldestFirstEachFollowedByItsReplies(): void
    {
        $forum = $this->forum(1);
        $thread = $this->thread($forum);
        $first = $this->post($thread, $forum, 2);
        $second = $this->post($thread, $forum, 2);
        $reply = $this->post($thread, $forum, 1, ['post_parent_id' => $first]);

        $list = get_thread_user_post($this->getCourse(1), $thread, 2);

        self::assertSame([$first, $reply, $second], array_map(static fn ($r) => (int) $r['iid'], $list));
        self::assertSame(['1', '0', '1'], array_column($list, 'status'));
    }
}
