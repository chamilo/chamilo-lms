<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Database;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\UuidV4;
use TrackingCourseLog;

/**
 * Wave 3 characterization (part C6) of the legacy TrackingCourseLog class, which had no test at all: the resource
 * log counters and lists, and the per-learner / per-course report getters, pinned before their raw SQL is rewritten.
 *
 * Fixtures are prepared statements on raw rows (course 1 and 2 are installer fixtures), rolled back by DAMA.
 */
final class TrackingCourseLogTier3C6CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const COURSE = 1;
    private const COURSE_TWO = 2;

    private const TYPE_FILES = 17;
    private const TYPE_EVENTS = 1;
    private const TYPE_LPS = 40;
    private const TYPE_FORUM_POSTS = 28;
    private const TYPE_FORUM_THREADS = 29;
    private const TYPE_FORUMS = 25;

    private Connection $conn;
    private int $seq = 0;
    private int $creator = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new Session(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/tracking/course_log_resources.php');
        $request->setSession($httpSession);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        Database::setManager($container->get('doctrine')->getManager());
        $this->context(self::COURSE, 0);
        $this->creator = $this->user('c6l_creator');
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        ChamiloSession::erase('_real_cid');
        ChamiloSession::erase('sid');
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // countItemResources / getItemResourcesData
    // ------------------------------------------------------------------

    public function testCountItemResourcesCountsTheAllowedTypesOfTheCourseInTheBaseContextOnly(): void
    {
        $session = $this->session('c6l_s');
        $before = TrackingCourseLog::countItemResources();
        $this->link($this->node(self::TYPE_FILES, 'c6l_doc'), self::COURSE, null);
        $this->link($this->node(self::TYPE_LPS, 'c6l_lp'), self::COURSE, null);
        // Not counted: a type outside the list, another course, a session link.
        $this->link($this->node(self::TYPE_EVENTS, 'c6l_event'), self::COURSE, null);
        $this->link($this->node(self::TYPE_FILES, 'c6l_doc2'), self::COURSE_TWO, null);
        $this->link($this->node(self::TYPE_FILES, 'c6l_doc_session'), self::COURSE, $session);

        $this->assertSame($before + 2, TrackingCourseLog::countItemResources());
        $this->assertIsInt(TrackingCourseLog::countItemResources());

        // In the session only the links of that session count.
        $this->context(self::COURSE, $session);
        $this->assertSame(1, TrackingCourseLog::countItemResources());
        $this->context(self::COURSE, $this->session('c6l_empty'));
        $this->assertSame(0, TrackingCourseLog::countItemResources());
    }

    public function testCountItemResourcesKeywordMatchesCreatorNodeTitleOrTypeTitle(): void
    {
        $alice = $this->user('c6l_zulu_creator');
        $this->link($this->node(self::TYPE_FILES, 'c6l_kw_alpha', $alice), self::COURSE, null);
        $this->link($this->node(self::TYPE_LPS, 'c6l_kw_beta'), self::COURSE, null);

        $_GET['keyword'] = ' c6l_kw_alpha ';
        $this->assertSame(1, TrackingCourseLog::countItemResources(), 'node title (trimmed)');
        $_GET['keyword'] = 'c6l_zulu_creator';
        $this->assertSame(1, TrackingCourseLog::countItemResources(), 'creator username');
        $_GET['keyword'] = 'lps';
        $this->assertGreaterThanOrEqual(1, TrackingCourseLog::countItemResources(), 'type title');
        $_GET['keyword'] = 'c6l_kw_';
        $this->assertSame(2, TrackingCourseLog::countItemResources());
        $_GET['keyword'] = 'c6l_no_such_thing';
        $this->assertSame(0, TrackingCourseLog::countItemResources());
        // A quote in the keyword is data, not SQL.
        $_GET['keyword'] = "c6l'; DROP TABLE x; --";
        $this->assertSame(0, TrackingCourseLog::countItemResources());
        // The LIKE wildcards are not escaped: "%" matches everything.
        $_GET['keyword'] = '%';
        $this->assertGreaterThanOrEqual(2, TrackingCourseLog::countItemResources());
    }

    public function testGetItemResourcesDataBuildsTheRowsAndSkipsTheTypesWithoutALegacyTool(): void
    {
        $session = $this->session('c6l_s');
        $creator = $this->user('c6l_row_creator');
        $node = $this->node(self::TYPE_FILES, 'c6l_row_doc', $creator);
        $link = $this->link($node, self::COURSE, null, 1, '2001-05-06 07:08:09');
        $this->link($this->node(self::TYPE_EVENTS, 'c6l_row_event'), self::COURSE, null);
        $this->link($this->node(self::TYPE_FILES, 'c6l_row_other_course'), self::COURSE_TWO, null);
        $this->link($this->node(self::TYPE_FILES, 'c6l_row_in_session'), self::COURSE, $session);
        $_GET['keyword'] = ' c6l_row_ ';

        $rows = TrackingCourseLog::getItemResourcesData(0, 10, 6, 'asc');

        $this->assertCount(1, $rows);
        $row = $rows[0];
        $this->assertSame($link, $row['ref']);
        $this->assertSame('2001-05-06 07:08:09', $row['col6']);
        $this->assertSame(1, $row['col7']);
        $this->assertSame($creator, $row['user_id']);
        $this->assertSame('c6l_row_doc', $row['document_title']);
        $this->assertSame(0, $row['parent_resource_node_id']);
        $this->assertSame('files', $row['resource_type_title']);
        $this->assertSame('', $row['session_name']);
        $this->assertSame('', $row['coach_username']);
        $this->assertSame('document', $row['col0']);
        $this->assertSame('Created', $row['col1']);
        $this->assertSame('c6l_row_doc', $row[5]);
        $this->assertStringContainsString('c6l_row_creator', (string) $row[3]);
        $this->assertSame('', $row[2]);

        // In the session context the session link only, with the session name and every session user as "coach"
        // (one row per session user: the join does not filter on the relation type).
        $this->context(self::COURSE, $session);
        $this->conn->executeStatement(
            "INSERT INTO session_rel_user (session_id, user_id, relation_type, duration, registered_at) VALUES (?, ?, 3, 0, '2001-01-01 00:00:00')",
            [$session, $creator]
        );
        $_GET['keyword'] = 'c6l_row_';
        $inSession = TrackingCourseLog::getItemResourcesData(0, 10, 6, 'asc');
        $this->assertCount(1, $inSession);
        $this->assertStringStartsWith('c6l_s_', $inSession[0]['session_name']);
        $this->assertSame($inSession[0]['session_name'].'<br />Tutor: '.$inSession[0]['coach_username'], $inSession[0][2]);
        $this->assertStringStartsWith('c6l_row_creator', $inSession[0]['coach_username']);

        // The keyword also matches the creator username and the type title, not only the node title.
        $this->context(self::COURSE, 0);
        $this->link($this->node(self::TYPE_LPS, 'c6l_row_lp', $creator), self::COURSE, null);
        $_GET['keyword'] = 'c6l_row_creator';
        $byCreator = array_column(TrackingCourseLog::getItemResourcesData(0, 10, 5, 'asc'), 'document_title');
        $this->assertSame(['c6l_row_doc', 'c6l_row_lp'], $byCreator);
        $alone = $this->user('c6l_lonely');
        $this->link($this->node(self::TYPE_LPS, 'c6l_row_plain', $alone), self::COURSE, null);
        $_GET['keyword'] = 'lps';
        $byType = array_column(TrackingCourseLog::getItemResourcesData(0, 100, 5, 'asc'), 'document_title');
        $this->assertContains('c6l_row_plain', $byType);
        $this->assertNotContains('c6l_row_doc', $byType);
    }

    public function testGetItemResourcesDataSortsByTheRequestedColumnAndDirectionAndPaginatesFromAnOffset(): void
    {
        $a = $this->user('c6l_a_user');
        $b = $this->user('c6l_b_user');
        $this->link($this->node(self::TYPE_LPS, 'c6l_ord_1', $a), self::COURSE, null, 1, '2001-01-01 00:00:00');
        $this->link($this->node(self::TYPE_FILES, 'c6l_ord_2', $b), self::COURSE, null, 1, '2001-01-03 00:00:00');
        $this->link($this->node(self::TYPE_FILES, 'c6l_ord_3', $a), self::COURSE, null, 1, '2001-01-02 00:00:00');
        $_GET['keyword'] = 'c6l_ord_';
        $titles = fn (array $rows): array => array_column($rows, 'document_title');

        // Column 5: node title; 6 and unknown columns: link creation date; 0: type title (files before lps);
        // 3: creator username.
        $this->assertSame(['c6l_ord_1', 'c6l_ord_2', 'c6l_ord_3'], $titles(TrackingCourseLog::getItemResourcesData(0, 10, 5, 'asc')));
        $this->assertSame(['c6l_ord_3', 'c6l_ord_2', 'c6l_ord_1'], $titles(TrackingCourseLog::getItemResourcesData(0, 10, 5, 'DESC')));
        $this->assertSame(['c6l_ord_1', 'c6l_ord_3', 'c6l_ord_2'], $titles(TrackingCourseLog::getItemResourcesData(0, 10, 6, 'asc')));
        $this->assertSame(['c6l_ord_2', 'c6l_ord_3', 'c6l_ord_1'], $titles(TrackingCourseLog::getItemResourcesData(0, 10, 99, 'desc')));
        $this->assertSame('c6l_ord_1', $titles(TrackingCourseLog::getItemResourcesData(0, 10, 0, 'desc'))[0], 'lps after files');
        $this->assertSame('c6l_ord_1', $titles(TrackingCourseLog::getItemResourcesData(0, 10, 0, 'asc'))[2], 'lps after files');
        $byUser = TrackingCourseLog::getItemResourcesData(0, 10, 3, 'asc');
        $this->assertSame('c6l_ord_2', $titles($byUser)[2], 'creator b_user last');
        // An invalid direction falls back to ASC.
        $this->assertSame(['c6l_ord_1', 'c6l_ord_2', 'c6l_ord_3'], $titles(TrackingCourseLog::getItemResourcesData(0, 10, 5, 'sideways')));
        // The limit only applies when the offset is not zero: LIMIT from, count.
        $this->assertCount(3, TrackingCourseLog::getItemResourcesData(0, 1, 5, 'asc'));
        $this->assertSame(['c6l_ord_2'], $titles(TrackingCourseLog::getItemResourcesData(1, 1, 5, 'asc')));
        $this->assertSame(['c6l_ord_2', 'c6l_ord_3'], $titles(TrackingCourseLog::getItemResourcesData(1, 10, 5, 'asc')));
    }

    // ------------------------------------------------------------------
    // Per-learner getters
    // ------------------------------------------------------------------

    public function testGetCourseUserDownloadedDocumentsListsTheDownloadsOfTheUserInTheCourseAndSession(): void
    {
        $u = $this->user('c6l_u');
        $o = $this->user('c6l_o');
        $session = $this->session('c6l_s');
        $doc = $this->document('c6l_dl_doc');
        $docLink = $this->link($doc['node'], self::COURSE, null);
        $sessionLink = $this->link($this->document('c6l_dl_sdoc')['node'], self::COURSE, $session);
        $otherCourseLink = $this->link($this->document('c6l_dl_c2')['node'], self::COURSE_TWO, null);
        $d1 = $this->download($u, $docLink, '2001-03-01 10:00:00', '/c6l/doc1');
        $d2 = $this->download($u, $docLink, '2001-03-03 10:00:00', '/c6l/doc2');
        $this->download($o, $docLink, '2001-03-05 10:00:00', '/c6l/other');
        $this->download($u, $otherCourseLink, '2001-03-06 10:00:00', '/c6l/c2');
        $d3 = $this->download($u, $sessionLink, '2001-03-07 10:00:00', '/c6l/session');

        $rows = TrackingCourseLog::getCourseUserDownloadedDocuments($u, self::COURSE, 0, 100);

        // Newest first, base context only.
        $this->assertSame([$d2, $d1], array_map(fn (array $r): int => (int) $r['down_id'], $rows));
        $this->assertSame(
            ['down_id', 'down_date', 'down_doc_path', 'resource_link_id', 'resource_node_id', 'resource_title', 'resource_path', 'document_id', 'document_title', 'document_filetype'],
            $this->named($rows[0])
        );
        $this->assertSame('2001-03-03 10:00:00', $rows[0]['down_date']);
        $this->assertSame('/c6l/doc2', $rows[0]['down_doc_path']);
        $this->assertSame($docLink, (int) $rows[0]['resource_link_id']);
        $this->assertSame($doc['node'], (int) $rows[0]['resource_node_id']);
        $this->assertSame('c6l_dl_doc', $rows[0]['resource_title']);
        $this->assertSame($doc['id'], (int) $rows[0]['document_id']);
        $this->assertSame('c6l_dl_doc', $rows[0]['document_title']);
        $this->assertSame('file', $rows[0]['document_filetype']);

        $inSession = TrackingCourseLog::getCourseUserDownloadedDocuments($u, self::COURSE, $session, 100);
        $this->assertSame([$d3], array_map(fn (array $r): int => (int) $r['down_id'], $inSession));

        // The limit is clamped to 1..500; a link without a document row still lists (LEFT JOIN).
        $this->assertCount(1, TrackingCourseLog::getCourseUserDownloadedDocuments($u, self::COURSE, 0, 0));
        $this->assertCount(1, TrackingCourseLog::getCourseUserDownloadedDocuments($u, self::COURSE, 0, -5));
        $bare = $this->link($this->node(self::TYPE_FILES, 'c6l_dl_bare'), self::COURSE, null);
        $this->download($u, $bare, '2001-03-09 10:00:00', '/c6l/bare');
        $first = TrackingCourseLog::getCourseUserDownloadedDocuments($u, self::COURSE, 0, 1)[0];
        $this->assertSame('/c6l/bare', $first['down_doc_path']);
        $this->assertNull($first['document_id']);
        $this->assertNull($first['document_title']);
    }

    public function testGetCourseUserForumThreadsListsTheThreadsStartedByTheUser(): void
    {
        $u = $this->user('c6l_u');
        $o = $this->user('c6l_o');
        $session = $this->session('c6l_s');
        $forum = $this->forum('c6l_forum');
        $t1 = $this->thread($forum, $u, 'c6l_t1', '2001-04-01 10:00:00', 3, 7);
        $t2 = $this->thread($forum, $u, 'c6l_t2', '2001-04-02 10:00:00');
        $this->thread($forum, $o, 'c6l_t_other', '2001-04-03 10:00:00');
        $tSession = $this->thread($forum, $u, 'c6l_t_session', '2001-04-04 10:00:00', 0, 0, $session);
        $tCourse2 = $this->thread($forum, $u, 'c6l_t_course2', '2001-04-05 10:00:00', 0, 0, null, self::COURSE_TWO);

        $rows = TrackingCourseLog::getCourseUserForumThreads($u, self::COURSE, 0, 100);

        $this->assertSame([$t2, $t1], array_map(fn (array $r): int => (int) $r['thread_id'], $rows));
        $this->assertSame(
            ['thread_id', 'thread_title', 'thread_date', 'thread_replies', 'thread_views', 'forum_id', 'forum_title'],
            $this->named($rows[1])
        );
        $this->assertSame('c6l_t1', $rows[1]['thread_title']);
        $this->assertSame(3, (int) $rows[1]['thread_replies']);
        $this->assertSame(7, (int) $rows[1]['thread_views']);
        $this->assertSame($forum, (int) $rows[1]['forum_id']);
        $this->assertSame('c6l_forum', $rows[1]['forum_title']);
        $this->assertSame(
            [$tSession],
            array_map(fn (array $r): int => (int) $r['thread_id'], TrackingCourseLog::getCourseUserForumThreads($u, self::COURSE, $session, 100))
        );
        $this->assertSame(
            [$tCourse2],
            array_map(fn (array $r): int => (int) $r['thread_id'], TrackingCourseLog::getCourseUserForumThreads($u, self::COURSE_TWO, 0, 100))
        );
        $this->assertCount(1, TrackingCourseLog::getCourseUserForumThreads($u, self::COURSE, 0, 1));
    }

    public function testGetCourseUserForumPostsListsThePostsOfTheUserLinkedThroughThePostOrItsThread(): void
    {
        $u = $this->user('c6l_u');
        $o = $this->user('c6l_o');
        $session = $this->session('c6l_s');
        $forum = $this->forum('c6l_forum');
        $thread = $this->thread($forum, $o, 'c6l_pt', '2001-04-01 10:00:00');
        $threadSession = $this->thread($forum, $o, 'c6l_pt_s', '2001-04-01 10:00:00', 0, 0, $session);
        // Linked through the post itself (base context).
        $p1 = $this->post($forum, $thread, $u, 'c6l_p1', '2001-04-01 11:00:00', true, null);
        $threadNoLink = $this->thread($forum, $o, 'c6l_pt_nolink', '2001-04-01 10:00:00', 0, 0, null, self::COURSE, false);
        // Linked only through its thread (the post has no link).
        $p2 = $this->post($forum, $thread, $u, 'c6l_p2', '2001-04-02 11:00:00', false, false);
        // Linked through the post only (its thread has no link).
        $pOwn = $this->post($forum, $threadNoLink, $u, 'c6l_p_own', '2001-04-06 11:00:00', true, null);
        // Linked nowhere: not listed.
        $this->post($forum, $threadNoLink, $u, 'c6l_p_nolink', '2001-04-03 11:00:00', false, false);
        // Another author.
        $this->post($forum, $thread, $o, 'c6l_p_other', '2001-04-04 11:00:00', true, null);
        // In the session: through its own link / through the thread of the session.
        $pSession = $this->post($forum, $threadSession, $u, 'c6l_p_s', '2001-04-05 11:00:00', true, $session);

        $rows = TrackingCourseLog::getCourseUserForumPosts($u, self::COURSE, 0, 100);

        $this->assertSame([$pOwn, $p2, $p1], array_map(fn (array $r): int => (int) $r['post_id'], $rows));
        $rows = \array_slice($rows, 1);
        $this->assertSame(
            ['post_id', 'post_title', 'post_date', 'visible', 'status', 'thread_id', 'thread_title', 'forum_id', 'forum_title'],
            $this->named($rows[0])
        );
        $this->assertSame('c6l_p2', $rows[0]['post_title']);
        $this->assertSame($thread, (int) $rows[0]['thread_id']);
        $this->assertSame('c6l_pt', $rows[0]['thread_title']);
        $this->assertSame('c6l_forum', $rows[0]['forum_title']);

        $this->assertSame('c6l_p_own', TrackingCourseLog::getCourseUserForumPosts($u, self::COURSE, 0, 100)[0]['post_title']);
        $inSession = TrackingCourseLog::getCourseUserForumPosts($u, self::COURSE, $session, 100);
        $this->assertSame([$pSession], array_map(fn (array $r): int => (int) $r['post_id'], $inSession));
        $this->assertCount(1, TrackingCourseLog::getCourseUserForumPosts($u, self::COURSE, 0, 1));
        $this->assertSame([], TrackingCourseLog::getCourseUserForumPosts($u, self::COURSE_TWO, 0, 100));
    }

    public function testGetCourseUserAccessListsAreFilteredBySessionExactlyAndNewestFirst(): void
    {
        $u = $this->user('c6l_u');
        $o = $this->user('c6l_o');
        $session = $this->session('c6l_s');
        $a1 = $this->courseAccess($u, self::COURSE, '2001-03-01 10:00:00', 0, 2);
        $a2 = $this->courseAccess($u, self::COURSE, '2001-03-02 10:00:00', 0, 5);
        $this->courseAccess($o, self::COURSE, '2001-03-03 10:00:00', 0);
        $this->courseAccess($u, self::COURSE_TWO, '2001-03-04 10:00:00', 0);
        $aSession = $this->courseAccess($u, self::COURSE, '2001-03-05 10:00:00', $session);

        $rows = TrackingCourseLog::getCourseUserAccessList($u, self::COURSE, 0, 100);

        $this->assertSame([$a2, $a1], array_map(fn (array $r): int => (int) $r['course_access_id'], $rows));
        $this->assertSame(['course_access_id', 'login_course_date', 'logout_course_date', 'counter', 'user_ip'], $this->named($rows[0]));
        $this->assertSame(5, (int) $rows[0]['counter']);
        $this->assertSame('127.0.0.1', $rows[0]['user_ip']);
        $this->assertSame(
            [$aSession],
            array_map(fn (array $r): int => (int) $r['course_access_id'], TrackingCourseLog::getCourseUserAccessList($u, self::COURSE, $session, 100))
        );
        $this->assertCount(1, TrackingCourseLog::getCourseUserAccessList($u, self::COURSE, 0, 1));

        $r1 = $this->toolAccess($u, self::COURSE, '2001-03-01 10:00:00', 'agenda', 0);
        $r2 = $this->toolAccess($u, self::COURSE, '2001-03-02 10:00:00', 'forum', 0);
        $this->toolAccess($o, self::COURSE, '2001-03-03 10:00:00', 'forum', 0);
        $this->toolAccess($u, self::COURSE_TWO, '2001-03-04 10:00:00', 'forum', 0);
        $rSession = $this->toolAccess($u, self::COURSE, '2001-03-05 10:00:00', 'forum', $session);
        $tools = TrackingCourseLog::getCourseUserResourceAccessList($u, self::COURSE, 0, 100);
        $this->assertSame([$r2, $r1], array_map(fn (array $r): int => (int) $r['access_id'], $tools));
        $this->assertSame(['access_id', 'access_date', 'access_tool', 'user_ip'], $this->named($tools[0]));
        $this->assertSame('forum', $tools[0]['access_tool']);
        $this->assertSame(
            [$rSession],
            array_map(fn (array $r): int => (int) $r['access_id'], TrackingCourseLog::getCourseUserResourceAccessList($u, self::COURSE, $session, 100))
        );
        $this->assertCount(1, TrackingCourseLog::getCourseUserResourceAccessList($u, self::COURSE, 0, 1));
    }

    // ------------------------------------------------------------------
    // Course activity getters
    // ------------------------------------------------------------------

    public function testGetCourseConnectedUsersSinceAggregatesTheConnectionsPerUser(): void
    {
        $u = $this->user('c6l_u');
        $o = $this->user('c6l_o');
        $session = $this->session('c6l_s');
        $this->courseAccess($u, self::COURSE, '2001-03-01 10:00:00', 0, 1, '2001-03-01 10:10:00');
        $this->courseAccess($u, self::COURSE, '2001-03-03 10:00:00', 0, 1, '2001-03-03 10:05:00');
        $this->courseAccess($u, self::COURSE, '2001-02-01 10:00:00', 0, 1, '2001-02-01 10:05:00');
        $this->courseAccess($o, self::COURSE, '2001-03-02 10:00:00', 0, 1, '2001-03-02 10:20:00');
        $this->courseAccess($o, self::COURSE, '2001-03-02 12:00:00', $session, 1, '2001-03-02 12:20:00');
        $this->courseAccess($o, self::COURSE_TWO, '2001-03-02 12:00:00', 0, 1, '2001-03-02 12:20:00');
        $since = new DateTimeImmutable('2001-03-01 00:00:00');

        $rows = TrackingCourseLog::getCourseConnectedUsersSince(self::COURSE, 0, $since, [$u, $o]);

        // Most recently connected first.
        $this->assertSame([$u, $o], array_map(fn (array $r): int => (int) $r['user_id'], $rows));
        $this->assertSame(
            ['user_id', 'firstname', 'lastname', 'username', 'connection_count', 'first_access', 'last_access', 'total_seconds'],
            $this->named($rows[0])
        );
        $this->assertSame(2, (int) $rows[0]['connection_count']);
        $this->assertSame('2001-03-01 10:00:00', $rows[0]['first_access']);
        $this->assertSame('2001-03-03 10:00:00', $rows[0]['last_access']);
        $this->assertSame(900, (int) $rows[0]['total_seconds']);
        $this->assertSame(1, (int) $rows[1]['connection_count']);
        $this->assertSame(1200, (int) $rows[1]['total_seconds']);
        // Restricted to the given users, limited, per session.
        $this->assertCount(1, TrackingCourseLog::getCourseConnectedUsersSince(self::COURSE, 0, $since, [$o]));
        $this->assertCount(1, TrackingCourseLog::getCourseConnectedUsersSince(self::COURSE, 0, $since, [$u, $o], 1));
        $this->assertCount(1, TrackingCourseLog::getCourseConnectedUsersSince(self::COURSE, $session, $since, [$u, $o]));
    }

    public function testCourseUsersLastAccessMapAndTheNotConnectedListAreBuiltPerUser(): void
    {
        $u = $this->user('c6l_u');
        $o = $this->user('c6l_o');
        $never = $this->user('c6l_never');
        $session = $this->session('c6l_s');
        $this->courseAccess($u, self::COURSE, '2001-03-01 10:00:00', 0);
        $this->courseAccess($u, self::COURSE, '2001-03-09 10:00:00', 0);
        $this->courseAccess($o, self::COURSE, '2001-01-01 10:00:00', 0);
        $this->courseAccess($o, self::COURSE, '2001-03-20 10:00:00', $session);
        $this->courseAccess($u, self::COURSE_TWO, '2001-03-30 10:00:00', 0);

        $map = TrackingCourseLog::getCourseUsersLastAccessMap(self::COURSE, 0, [$u, $o, $never]);

        $this->assertSame([$u, $o], array_keys($map));
        $this->assertSame(['user_id', 'last_access', 'connection_count'], $this->named($map[$u]));
        $this->assertSame('2001-03-09 10:00:00', $map[$u]['last_access']);
        $this->assertSame(2, (int) $map[$u]['connection_count']);
        $this->assertSame('2001-01-01 10:00:00', $map[$o]['last_access']);
        $this->assertSame([$o], array_keys(TrackingCourseLog::getCourseUsersLastAccessMap(self::COURSE, $session, [$u, $o, $never])));
        // Without a user list every user of the course is returned.
        $all = TrackingCourseLog::getCourseUsersLastAccessMap(self::COURSE, 0);
        $this->assertArrayHasKey($u, $all);
        $this->assertArrayHasKey($o, $all);
        // Non-positive ids are ignored (no filter at all when none is left).
        $this->assertArrayHasKey($o, TrackingCourseLog::getCourseUsersLastAccessMap(self::COURSE, 0, [0, -3]));

        $students = [
            $u => ['user_id' => $u, 'firstname' => 'U', 'lastname' => 'Uu', 'username' => 'uu', 'email' => 'u@example.com'],
            $o => ['user_id' => $o, 'firstname' => 'O', 'lastname' => 'Oo'],
            $never => ['id' => $never, 'firstName' => 'N', 'lastName' => 'Nn'],
        ];
        $inactive = TrackingCourseLog::getCourseUsersNotConnectedSince($students, self::COURSE, 0, new DateTimeImmutable('2001-03-05 00:00:00'));
        // Never connected first, then the oldest last access; U connected after the date.
        $this->assertSame([$never, $o], array_column($inactive, 'user_id'));
        $this->assertNull($inactive[0]['last_access']);
        $this->assertSame(0, $inactive[0]['connection_count']);
        $this->assertSame('2001-01-01 10:00:00', $inactive[1]['last_access']);
        $this->assertSame(1, $inactive[1]['connection_count']);
    }

    public function testCourseResourceUsageSummaryAndTheConnectedUsersCountAreFilteredByCourseSessionAndDate(): void
    {
        $u = $this->user('c6l_u');
        $o = $this->user('c6l_o');
        $session = $this->session('c6l_s');
        foreach ([[$u, 'forum', '2001-03-01 10:00:00'], [$o, 'forum', '2001-03-05 10:00:00'], [$u, 'forum', '2001-03-06 10:00:00'], [$u, 'agenda', '2001-03-02 10:00:00'], [$u, 'agenda', '2001-01-02 10:00:00'], [$o, 'blog', '2001-03-03 10:00:00']] as [$user, $tool, $date]) {
            $this->toolAccess($user, self::COURSE, $date, $tool, 0);
        }
        $this->toolAccess($u, self::COURSE, '2001-03-03 10:00:00', 'forum', $session);
        $this->toolAccess($u, self::COURSE_TWO, '2001-03-03 10:00:00', 'forum', 0);
        // Exactly at the date: included (>=).
        $this->toolAccess($u, self::COURSE, '2001-03-01 00:00:00', 'chat', 0);
        $since = new DateTimeImmutable('2001-03-01 00:00:00');

        $rows = TrackingCourseLog::getCourseResourceUsageSummarySince(self::COURSE, 0, $since);

        // Most events first, then the tool name.
        $this->assertSame(['forum', 'agenda', 'blog', 'chat'], array_column($rows, 'access_tool'));
        $this->assertSame(['access_tool', 'event_count', 'user_count', 'last_access'], $this->named($rows[0]));
        $this->assertSame([3, 1, 1, 1], array_map('intval', array_column($rows, 'event_count')));
        $this->assertSame([2, 1, 1, 1], array_map('intval', array_column($rows, 'user_count')));
        $this->assertSame('2001-03-06 10:00:00', $rows[0]['last_access']);
        $this->assertSame(['forum'], array_column(TrackingCourseLog::getCourseResourceUsageSummarySince(self::COURSE, $session, $since), 'access_tool'));
        $this->assertCount(1, TrackingCourseLog::getCourseResourceUsageSummarySince(self::COURSE, 0, $since, 1));
        $this->assertSame(['forum'], array_column(TrackingCourseLog::getCourseResourceUsageSummarySince(self::COURSE, 0, $since, 0), 'access_tool'));

        $this->courseAccess($u, self::COURSE, '2001-03-01 10:00:00', 0);
        $this->courseAccess($u, self::COURSE, '2001-03-04 10:00:00', 0);
        $this->courseAccess($o, self::COURSE, '2001-03-02 10:00:00', 0);
        $this->courseAccess($o, self::COURSE, '2001-02-02 10:00:00', 0);
        $this->courseAccess($o, self::COURSE, '2001-03-02 10:00:00', $session);
        $this->courseAccess($o, self::COURSE_TWO, '2001-03-02 10:00:00', 0);
        $elsewhere = $this->user('c6l_elsewhere');
        $this->courseAccess($elsewhere, self::COURSE_TWO, '2001-03-02 10:00:00', 0);
        $boundary = $this->user('c6l_boundary');
        $this->courseAccess($boundary, self::COURSE, '2001-03-01 00:00:00', 0);
        $this->assertSame(2, TrackingCourseLog::countCourseConnectedUsersSince(self::COURSE, 0, $since, [$u, $o, $elsewhere]));
        $this->assertSame(3, TrackingCourseLog::countCourseConnectedUsersSince(self::COURSE, 0, $since, [$u, $o, $boundary]), 'exactly at the date counts');
        $this->assertSame(1, TrackingCourseLog::countCourseConnectedUsersSince(self::COURSE, 0, $since, [$u]));
        $this->assertSame(1, TrackingCourseLog::countCourseConnectedUsersSince(self::COURSE, $session, $since, [$u, $o]));
        $this->assertSame(0, TrackingCourseLog::countCourseConnectedUsersSince(self::COURSE, 0, new DateTimeImmutable('2002-01-01'), [$u, $o]));
        $this->assertGreaterThanOrEqual(2, TrackingCourseLog::countCourseConnectedUsersSince(self::COURSE, 0, $since));
        $this->assertIsInt(TrackingCourseLog::countCourseConnectedUsersSince(self::COURSE, 0, $since, [$u]));
    }

    // ------------------------------------------------------------------
    // Fixtures
    // ------------------------------------------------------------------

    /**
     * The rows come from Database::fetch_array(), which repeats every value under a numeric key after the named ones:
     * pinned as is (the named keys are the contract, the numeric copies are asserted once).
     *
     * @return list<string>
     */
    private function named(array $row): array
    {
        $names = array_values(array_filter(array_keys($row), 'is_string'));
        foreach ($names as $i => $name) {
            $this->assertSame($row[$name], $row[$i], 'numeric copy of '.$name);
        }
        $this->assertCount(2 * \count($names), $row);

        return $names;
    }

    private function context(int $courseId, int $sessionId): void
    {
        ChamiloSession::write('_real_cid', $courseId);
        ChamiloSession::write('sid', $sessionId);
    }

    private function user(string $name): int
    {
        $name .= '_'.(++$this->seq).'_'.substr(md5((string) microtime(true)), 0, 6);
        $this->conn->executeStatement(
            "INSERT INTO user (username, username_canonical, locale, password, timezone, email, email_canonical,
                locked, expired, credentials_expired, salt, roles, status, active, uuid, created_at, updated_at,
                firstname, lastname)
             VALUES (?, ?, 'en_US', 'x', 'UTC', ?, ?, 0, 0, 0, 'x', '[]', 5, 1, ?,
                '2001-01-01 00:00:00', '2001-01-01 00:00:00', ?, 'c6l')",
            [$name, $name, $name.'@example.com', $name.'@example.com', UuidV4::v4()->toBinary(), $name]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function session(string $title): int
    {
        $this->conn->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status) VALUES (?, 0, 0, 0, 1, 0)',
            [$title.'_'.(++$this->seq)]
        );
        $id = (int) $this->conn->lastInsertId();
        $this->conn->executeStatement(
            'INSERT INTO access_url_rel_session (access_url_id, session_id) VALUES (?, ?)',
            [api_get_current_access_url_id(), $id]
        );

        return $id;
    }

    private function node(int $typeId, string $title, ?int $creatorId = null): int
    {
        $this->conn->executeStatement(
            "INSERT INTO resource_node (resource_type_id, creator_id, title, slug, level, path, created_at, updated_at, public, uuid)
             VALUES (?, ?, ?, ?, 0, ?, '2001-01-01 00:00:00', '2001-01-01 00:00:00', 0, ?)",
            [$typeId, $creatorId ?? $this->creator, $title, 'c6l-'.(++$this->seq), 'c6l-'.$this->seq.'/', UuidV4::v4()->toBinary()]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function link(int $nodeId, int $courseId, ?int $sessionId, int $visibility = 2, string $createdAt = '2001-01-01 00:00:00'): int
    {
        $this->conn->executeStatement(
            'INSERT INTO resource_link (resource_node_id, c_id, session_id, group_id, visibility, display_order,
                resource_type_group, created_at, updated_at)
             VALUES (?, ?, ?, NULL, ?, 0, 0, ?, ?)',
            [$nodeId, $courseId, $sessionId, $visibility, $createdAt, $createdAt]
        );

        return (int) $this->conn->lastInsertId();
    }

    /**
     * @return array{id: int, node: int}
     */
    private function document(string $title): array
    {
        $node = $this->node(self::TYPE_FILES, $title);
        $this->conn->executeStatement(
            "INSERT INTO c_document (title, filetype, readonly, template, resource_node_id) VALUES (?, 'file', 0, 0, ?)",
            [$title, $node]
        );

        return ['id' => (int) $this->conn->lastInsertId(), 'node' => $node];
    }

    private function download(int $userId, int $linkId, string $date, string $path): int
    {
        $this->conn->executeStatement(
            'INSERT INTO track_e_downloads (down_user_id, down_date, down_doc_path, resource_link_id) VALUES (?, ?, ?, ?)',
            [$userId, $date, $path, $linkId]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function forum(string $title): int
    {
        $this->conn->executeStatement(
            "INSERT INTO c_forum_forum (title, locked, forum_image, resource_node_id) VALUES (?, 0, '', ?)",
            [$title, $this->node(self::TYPE_FORUMS, $title)]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function thread(int $forumId, int $posterId, string $title, string $date, int $replies = 0, int $views = 0, ?int $sessionId = null, int $courseId = self::COURSE, bool $linked = true): int
    {
        $node = $this->node(self::TYPE_FORUM_THREADS, $title);
        $this->conn->executeStatement(
            'INSERT INTO c_forum_thread (title, thread_date, thread_sticky, locked, thread_qualify_max, thread_weight, thread_peer_qualify,
                forum_id, thread_poster_id, thread_replies, thread_views, resource_node_id)
             VALUES (?, ?, 0, 0, 0, 0, 0, ?, ?, ?, ?, ?)',
            [$title, $date, $forumId, $posterId, $replies, $views, $node]
        );
        $id = (int) $this->conn->lastInsertId();
        if ($linked) {
            $this->link($node, $courseId, $sessionId);
        }

        return $id;
    }

    /**
     * @param int|false|null $postLinkSession false: no resource link on the post itself; null: link in the base
     *                                        context; int: link in that session
     */
    private function post(int $forumId, int $threadId, int $posterId, string $title, string $date, bool $linked, false|int|null $postLinkSession): int
    {
        $node = $this->node(self::TYPE_FORUM_POSTS, $title);
        $this->conn->executeStatement(
            'INSERT INTO c_forum_post (title, post_date, visible, thread_id, forum_id, poster_id, resource_node_id)
             VALUES (?, ?, 1, ?, ?, ?, ?)',
            [$title, $date, $threadId, $forumId, $posterId, $node]
        );
        $id = (int) $this->conn->lastInsertId();
        if ($linked) {
            $this->link($node, self::COURSE, false === $postLinkSession ? null : $postLinkSession);
        }

        return $id;
    }

    private function courseAccess(int $userId, int $courseId, string $in, int $sessionId, int $counter = 1, ?string $out = null): int
    {
        $this->conn->executeStatement(
            "INSERT INTO track_e_course_access (user_id, c_id, login_course_date, logout_course_date, counter, session_id, user_ip)
             VALUES (?, ?, ?, ?, ?, ?, '127.0.0.1')",
            [$userId, $courseId, $in, $out ?? $in, $counter, $sessionId]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function toolAccess(int $userId, int $courseId, string $date, string $tool, int $sessionId): int
    {
        $this->conn->executeStatement(
            "INSERT INTO track_e_access (access_user_id, access_date, c_id, access_tool, session_id, user_ip)
             VALUES (?, ?, ?, ?, ?, '127.0.0.1')",
            [$userId, $date, $courseId, $tool, $sessionId]
        );

        return (int) $this->conn->lastInsertId();
    }
}
