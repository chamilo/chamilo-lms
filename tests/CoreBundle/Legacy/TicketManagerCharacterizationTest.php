<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\Ticket\TicketWorkflowService;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Throwable;
use TicketManager;

use const STR_PAD_LEFT;
use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_PARTIAL;

/**
 * Characterization tests for the raw-SQL methods of TicketManager (public/main/inc/lib/TicketManager.php).
 *
 * They pin what the legacy queries return / write TODAY (rows, keys, ordering, NULL handling, DB side effects)
 * so a later rewrite to Doctrine/DQL that changes any of it fails here. The bugs once pinned here were fixed
 * (each fixed test says "Fixed:" with the reason); what is left as-is is documented in the method docblocks.
 *
 * Every fixture title/code/email starts with "tm_" (sibling test files share the database). Fixture users are the
 * stock admin (id 1), anon (2) and fallback_user (3).
 */
final class TicketManagerCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;
    private User $admin;
    private User $other;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/ticket/tickets.php');
        $request->setSession($session);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        $this->admin = $this->getUser('admin');
        $this->other = $this->getUser('anon');
        $_GET = [];
        $this->loginAs($this->admin);
    }

    protected function tearDown(): void
    {
        $_GET = [];
        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function loginAs(User $user): void
    {
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        self::getContainer()->get('security.token_storage')->setToken($token);
        ChamiloSession::write('_user', ['id' => $user->getId()]);
        ChamiloSession::erase('login_as');
        $this->getEntityManager()->clear();
    }

    private function utc(string $modify = 'now'): string
    {
        return gmdate('Y-m-d H:i:s', strtotime($modify));
    }

    private function assertNowish(?string $dbDate, int $tolerance = 15): void
    {
        $this->assertNotNull($dbDate);
        $this->assertLessThanOrEqual($tolerance, abs(time() - strtotime($dbDate.' UTC')), "$dbDate is not now (UTC)");
    }

    private function project(string $title, ?int $otherArea = null, ?string $email = null): int
    {
        $this->conn->insert('ticket_project', [
            'access_url_id' => 1,
            'title' => $title,
            'email' => $email,
            'other_area' => $otherArea,
            'sys_insert_user_id' => 1,
            'sys_insert_datetime' => '2020-01-01 00:00:00',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function category(int $projectId, string $title, int $total = 0, ?string $description = null): int
    {
        $this->conn->insert('ticket_category', [
            'project_id' => $projectId,
            'title' => $title,
            'description' => $description,
            'total_tickets' => $total,
            'course_required' => 0,
            'sys_insert_user_id' => 1,
            'sys_insert_datetime' => '2020-01-01 00:00:00',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * @param array<string, mixed> $o
     */
    private function ticket(int $projectId, int $categoryId, array $o = []): int
    {
        ++$this->seq;
        $this->conn->insert('ticket_ticket', $o + [
            'project_id' => $projectId,
            'category_id' => $categoryId,
            'priority_id' => 1,
            'status_id' => 1,
            'access_url_id' => 1,
            'code' => 'tm_code_'.$this->seq,
            'subject' => 'tm_subject_'.$this->seq,
            'message' => 'tm_body_'.$this->seq,
            'personal_email' => '',
            'total_messages' => 0,
            'start_date' => '2021-03-15 10:00:00',
            'sys_insert_user_id' => 1,
            'sys_insert_datetime' => '2021-03-15 10:00:00',
            'sys_lastedit_user_id' => 1,
            'sys_lastedit_datetime' => '2021-03-15 10:00:00',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * @param array<string, mixed> $o
     */
    private function message(int $ticketId, int $userId, array $o = []): int
    {
        ++$this->seq;
        $this->conn->insert('ticket_message', $o + [
            'ticket_id' => $ticketId,
            'subject' => 'tm_msg_'.$this->seq,
            'message' => 'tm_msgbody_'.$this->seq,
            'status' => 'NOL',
            'ip_address' => '10.0.0.1',
            'sys_insert_user_id' => $userId,
            'sys_insert_datetime' => '2021-03-15 11:00:00',
            'sys_lastedit_user_id' => $userId,
            'sys_lastedit_datetime' => '2021-03-15 11:00:00',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(int $ticketId): array
    {
        return $this->conn->fetchAssociative('SELECT * FROM ticket_ticket WHERE id = ?', [$ticketId]);
    }

    /**
     * @return list<mixed>
     */
    private function col(array $rows, string $key): array
    {
        return array_values(array_map(static fn ($r) => $r[$key], $rows));
    }

    /**
     * Subject of each row of getTicketsByCurrentUser(): column 0 is "<icon><a>code</a> subject (+admin html)".
     *
     * @return list<string>
     */
    private function subjects(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            preg_match('#</a> (tm_subject_\w+)#', $r[0], $m);
            $out[] = $m[1] ?? '?';
        }

        return $out;
    }

    // ---------------------------------------------------------------- categories

    public function testGetAllTicketsCategoriesDefaultsToMostUsedFirstAndJoinsProjectColumns(): void
    {
        $p = $this->project('tm_proj_cat', 7, 'tm_help@example.com');
        $low = $this->category($p, 'tm_cat_low', 1);
        $high = $this->category($p, 'tm_cat_high', 9);
        $this->category($this->project('tm_proj_other'), 'tm_cat_elsewhere', 100);

        $rows = TicketManager::get_all_tickets_categories($p);

        $this->assertSame(['tm_cat_high', 'tm_cat_low'], $this->col($rows, 'title'), 'ORDER BY total_tickets DESC, project-scoped');
        $this->assertSame([$high, $low], array_map('intval', $this->col($rows, 'category_id')));
        $this->assertSame('7', (string) $rows[0]['other_area']);
        $this->assertSame('tm_help@example.com', $rows[0]['email']);
        $this->assertSame((string) $high, (string) $rows[0]['id']);
        $this->assertSame((string) $p, (string) $rows[0]['project_id']);
    }

    public function testGetAllTicketsCategoriesHonoursCustomOrderAndIgnoresUnknownProject(): void
    {
        $p = $this->project('tm_proj_cat2');
        $this->category($p, 'tm_cat_b', 5);
        $this->category($p, 'tm_cat_a', 1);

        $this->assertSame(
            ['tm_cat_a', 'tm_cat_b'],
            $this->col(TicketManager::get_all_tickets_categories($p, 'category.title ASC'), 'title')
        );
        $this->assertSame([], TicketManager::get_all_tickets_categories(999999));
        $this->assertSame([], TicketManager::get_all_tickets_categories('abc'), 'project id is cast to int (0)');
    }

    public function testGetCategoriesPaginatesWithinProjectAndReturnsBothAssociativeAndNumericKeys(): void
    {
        $p = $this->project('tm_proj_page');
        for ($i = 1; $i <= 3; ++$i) {
            $this->category($p, 'tm_cat_p'.$i, $i, 'desc'.$i);
        }
        $_GET['project_id'] = (string) $p;

        $all = TicketManager::getCategories(0, 10, 0, 'ASC');
        $this->assertCount(3, $all);
        $this->assertSame(['id', 'title', 'description', 'total_tickets', 0, 1, 2, 3], array_keys($all[0]));
        $this->assertEqualsCanonicalizing(['tm_cat_p1', 'tm_cat_p2', 'tm_cat_p3'], $this->col($all, 'title'));
        $this->assertSame($all[0]['title'], $all[0][1]);

        $this->assertCount(2, TicketManager::getCategories(1, 10, 0, 'DESC'), 'LIMIT from,count');
        $this->assertCount(1, TicketManager::getCategories(0, 1, 0, 'bogus'));
        $this->assertCount(0, TicketManager::getCategories(3, 10, 0, 'ASC'));
    }

    public function testGetCategoryExposesNameAliasAndFalseWhenMissing(): void
    {
        $p = $this->project('tm_proj_one');
        $id = $this->category($p, 'tm_cat_one', 4, 'tm_desc');

        $cat = TicketManager::getCategory($id);

        $this->assertSame('tm_cat_one', $cat['title']);
        $this->assertSame('tm_cat_one', $cat['name'], 'title AS name');
        $this->assertSame('tm_desc', $cat['description']);
        $this->assertSame(4, (int) $cat['total_tickets']);
        $this->assertSame('tm_cat_one', $cat[1]);
        // Kept on purpose (documented in the docblock): a missing category is an empty array, not false/null.
        $this->assertSame([], TicketManager::getCategory(987654321));
    }

    public function testGetCategoriesCountIsGlobalUnlessProjectFilterGiven(): void
    {
        $globalBefore = (int) TicketManager::getCategoriesCount();
        $p = $this->project('tm_proj_count');
        $this->category($p, 'tm_cat_c1');
        $this->category($p, 'tm_cat_c2');

        $this->assertSame($globalBefore + 2, (int) TicketManager::getCategoriesCount());

        $_GET['project_id'] = (string) $p;
        $this->assertSame(2, (int) TicketManager::getCategoriesCount());
        $_GET['project_id'] = '0';
        $this->assertSame($globalBefore + 2, (int) TicketManager::getCategoriesCount(), 'project_id 0 means no filter');
    }

    public function testAddUpdateCategoryWriteTheGivenColumns(): void
    {
        $p = $this->project('tm_proj_crud');
        TicketManager::addCategory([
            'project_id' => $p,
            'title' => 'tm_cat_new',
            'description' => 'd1',
            'total_tickets' => 0,
            'course_required' => 0,
            'sys_insert_user_id' => 1,
            'sys_insert_datetime' => '2020-05-05 05:05:05',
        ]);
        $id = (int) $this->conn->fetchOne("SELECT id FROM ticket_category WHERE title = 'tm_cat_new'");
        $this->assertGreaterThan(0, $id);

        TicketManager::updateCategory($id, ['title' => 'tm_cat_renamed', 'total_tickets' => 3]);

        $row = $this->conn->fetchAssociative('SELECT * FROM ticket_category WHERE id = ?', [$id]);
        $this->assertSame('tm_cat_renamed', $row['title']);
        $this->assertSame(3, (int) $row['total_tickets']);
        $this->assertSame('d1', $row['description'], 'untouched columns kept');
        $this->assertSame((string) $p, (string) $row['project_id']);
    }

    public function testDeleteCategoryDetachesTicketsButKeepsThem(): void
    {
        $p = $this->project('tm_proj_del');
        $cat = $this->category($p, 'tm_cat_del');
        $keep = $this->category($p, 'tm_cat_keep');
        $t1 = $this->ticket($p, $cat);
        $t2 = $this->ticket($p, $keep);

        $this->assertTrue(TicketManager::deleteCategory($cat));

        $this->assertFalse($this->conn->fetchOne('SELECT id FROM ticket_category WHERE id = ?', [$cat]));
        $this->assertNull($this->row($t1)['category_id'], 'ticket survives with category_id NULL');
        $this->assertSame((string) $keep, (string) $this->row($t2)['category_id']);
        $this->assertSame((string) $keep, (string) $this->conn->fetchOne('SELECT id FROM ticket_category WHERE id = ?', [$keep]));
        $this->assertFalse(TicketManager::deleteCategory(0));
        $this->assertFalse(TicketManager::deleteCategory('x'));
    }

    public function testCategoryUserAssignmentIsIdempotentAndScopedToTheCategory(): void
    {
        $p = $this->project('tm_proj_users');
        $c1 = $this->category($p, 'tm_cat_u1');
        $c2 = $this->category($p, 'tm_cat_u2');

        $this->assertFalse(TicketManager::addUsersToCategory($c1, []));
        $this->assertFalse(TicketManager::addUsersToCategory(0, [1]));
        $this->assertFalse(TicketManager::userIsAssignedToCategory(1, $c1));

        $this->assertTrue(TicketManager::addUsersToCategory($c1, [1, 2]));
        $this->assertTrue(TicketManager::addUsersToCategory($c1, [2, 3]), 'already-assigned user 2 is not duplicated');
        TicketManager::addUsersToCategory($c2, [3]);

        $this->assertTrue(TicketManager::userIsAssignedToCategory(2, $c1));
        $this->assertFalse(TicketManager::userIsAssignedToCategory(2, $c2));
        $users = TicketManager::getUsersInCategory($c1);
        $this->assertCount(3, $users);
        $this->assertEqualsCanonicalizing([1, 2, 3], array_map('intval', $this->col($users, 'user_id')));
        $this->assertSame((string) $c1, (string) $users[0]['category_id']);

        TicketManager::deleteAllUserInCategory($c1);
        $this->assertSame([], TicketManager::getUsersInCategory($c1));
        $this->assertCount(1, TicketManager::getUsersInCategory($c2), 'other category untouched');
    }

    // ---------------------------------------------------------------- lookups (statuses, priorities, projects)

    public function testGetAllTicketsStatusReturnsEveryStatusRowWithItsColumns(): void
    {
        $rows = TicketManager::get_all_tickets_status();

        $byCode = [];
        foreach ($rows as $r) {
            $byCode[$r['code']] = $r['title'];
        }
        $this->assertSame('New', $byCode['1']);
        $this->assertSame('Pending', $byCode['2']);
        $this->assertSame('Unconfirmed', $byCode['3']);
        $this->assertSame('Closed', $byCode['4']);
        $this->assertSame('Forwarded', $byCode['5']);
        $this->assertSame(['id', 'access_url_id', 'code', 'title', 'description'], array_keys($rows[0]));
    }

    public function testStatusPriorityAndProjectListsKeyById(): void
    {
        $this->assertSame('New', TicketManager::getStatusList()[TicketManager::STATUS_NEW]);
        $this->assertSame('Closed', TicketManager::getStatusList()[TicketManager::STATUS_CLOSED]);
        $this->assertSame(TicketManager::STATUS_CLOSED, TicketManager::getStatusIdFromCode('4'));
        $this->assertSame(0, TicketManager::getStatusIdFromCode('tm_no_such_code'));

        $priorities = TicketManager::getPriorityList();
        $this->assertSame('Normal', $priorities[TicketManager::PRIORITY_NORMAL]);
        $this->assertSame('High', $priorities[TicketManager::PRIORITY_HIGH]);

        $p = $this->project('tm_proj_list');
        $this->getEntityManager()->clear();
        $found = array_values(array_filter(TicketManager::getProjects(), static fn ($r) => $r['id'] === $p));
        $this->assertSame([$p, 'tm_proj_list'], [$found[0][0], $found[0][1]]);
        $this->assertGreaterThanOrEqual(2, (int) TicketManager::getProjectsCount());
    }

    // ---------------------------------------------------------------- creating tickets

    public function testAddCreatesTicketWithGeneratedCodeCounterBumpAndSubscription(): void
    {
        $p = $this->project('tm_proj_add');
        $cat = $this->category($p, 'tm_cat_add', 5);

        $ok = TicketManager::add($cat, 0, 0, $p, 0, 'tm_subject_add', 'tm_body_add');

        $this->assertTrue($ok);
        $row = $this->conn->fetchAssociative('SELECT * FROM ticket_ticket WHERE subject = ?', ['tm_subject_add']);
        $this->assertSame('A'.str_pad((string) $row['id'], 11, '0', STR_PAD_LEFT), $row['code']);
        $this->assertSame('tm_body_add', $row['message']);
        $this->assertSame(TicketManager::STATUS_NEW, (int) $row['status_id']);
        $this->assertSame(TicketManager::PRIORITY_NORMAL, (int) $row['priority_id']);
        $this->assertSame((string) $p, (string) $row['project_id']);
        $this->assertSame((string) $cat, (string) $row['category_id']);
        $this->assertSame('1', (string) $row['access_url_id']);
        $this->assertSame(0, (int) $row['total_messages']);
        $this->assertSame((string) $this->admin->getId(), (string) $row['sys_insert_user_id']);
        $this->assertNull($row['course_id']);
        $this->assertNull($row['session_id']);
        $this->assertNull($row['assigned_last_user'], 'no user in category and none given');
        $this->assertNowish($row['start_date']);
        $this->assertSame($row['start_date'], $row['sys_insert_datetime']);
        $this->assertSame(6, (int) $this->conn->fetchOne('SELECT total_tickets FROM ticket_category WHERE id = ?', [$cat]));
        $this->assertSame(
            1,
            (int) $this->conn->fetchOne('SELECT COUNT(*) FROM ticket_rel_user WHERE ticket_id = ? AND user_id = ?', [$row['id'], 1]),
            'creator is subscribed'
        );
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM ticket_assigned_log WHERE ticket_id = ?', [$row['id']]));
    }

    public function testAddAssignsToFirstUserOfCategoryAndLogsTheAssignment(): void
    {
        $p = $this->project('tm_proj_add2');
        $cat = $this->category($p, 'tm_cat_add2');
        TicketManager::addUsersToCategory($cat, [$this->other->getId()]);

        $this->assertTrue(TicketManager::add($cat, 0, 0, $p, 0, 'tm_subject_auto', 'b', 'tm_pe@example.com', [], 'tm_src', 2));

        $row = $this->conn->fetchAssociative('SELECT * FROM ticket_ticket WHERE subject = ?', ['tm_subject_auto']);
        $this->assertSame((string) $this->other->getId(), (string) $row['assigned_last_user']);
        $this->assertSame(TicketManager::PRIORITY_HIGH, (int) $row['priority_id']);
        $this->assertSame('tm_src', $row['source']);
        $this->assertSame('tm_pe@example.com', $row['personal_email']);
        $log = $this->conn->fetchAllAssociative('SELECT * FROM ticket_assigned_log WHERE ticket_id = ?', [$row['id']]);
        $this->assertCount(1, $log);
        $this->assertSame((string) $this->other->getId(), (string) $log[0]['user_id']);
        $this->assertSame((string) $this->admin->getId(), (string) $log[0]['sys_insert_user_id']);
        $this->assertNowish($log[0]['assigned_date']);
    }

    public function testAddRejectsMissingCategoryAndUnknownAssignee(): void
    {
        $p = $this->project('tm_proj_add3');
        $cat = $this->category($p, 'tm_cat_add3', 2);
        $before = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM ticket_ticket');

        $this->assertFalse(TicketManager::add(0, 0, 0, $p, 0, 'tm_subject_nocat', 'b'));
        $this->assertFalse(TicketManager::add($cat, 0, 0, $p, 0, 'tm_subject_ghost', 'b', '', [], '', '', '', 987654321));

        $this->assertSame($before, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM ticket_ticket'));
        $this->assertSame(2, (int) $this->conn->fetchOne('SELECT total_tickets FROM ticket_category WHERE id = ?', [$cat]));
    }

    public function testAddWithOtherAreaCreatesForwardedTicketAndStoresCourse(): void
    {
        $p = $this->project('tm_proj_add4');
        $cat = $this->category($p, 'tm_cat_add4');

        $this->assertTrue(TicketManager::add($cat, 1, 0, $p, 3, 'tm_subject_fwd', 'b'));

        $row = $this->conn->fetchAssociative('SELECT * FROM ticket_ticket WHERE subject = ?', ['tm_subject_fwd']);
        $this->assertSame(TicketManager::STATUS_FORWARDED, (int) $row['status_id']);
        $this->assertSame('1', (string) $row['course_id']);

        $this->assertTrue(TicketManager::add($cat, 0, 0, $p, 0, 'tm_subject_explicit', 'b', '', [], '', '', TicketManager::STATUS_PENDING));
        $row = $this->conn->fetchAssociative('SELECT * FROM ticket_ticket WHERE subject = ?', ['tm_subject_explicit']);
        $this->assertSame(TicketManager::STATUS_PENDING, (int) $row['status_id'], 'an explicit status beats the default');
    }

    // ---------------------------------------------------------------- assignment

    public function testAssignTicketToUserUpdatesTicketAndAppendsLog(): void
    {
        $p = $this->project('tm_proj_asg');
        $cat = $this->category($p, 'tm_cat_asg');
        $t = $this->ticket($p, $cat);

        $this->assertTrue(TicketManager::assignTicketToUser($t, $this->other->getId()));
        $this->assertTrue(TicketManager::assignTicketToUser((string) $t, 3));

        $this->assertSame('3', (string) $this->row($t)['assigned_last_user']);
        $log = $this->conn->fetchAllAssociative('SELECT * FROM ticket_assigned_log WHERE ticket_id = ? ORDER BY id', [$t]);
        $this->assertSame(
            [(string) $this->other->getId(), '3'],
            array_map(static fn ($r) => (string) $r['user_id'], $log),
            'one log row per assignment, history is never rewritten'
        );
        $this->assertSame((string) $this->admin->getId(), (string) $log[0]['sys_insert_user_id']);
        $this->assertNowish($log[0]['assigned_date']);
    }

    public function testAssignTicketToUserRefusesUnknownTicketsButAcceptsCategorylessOnes(): void
    {
        $p = $this->project('tm_proj_asg2');
        $cat = $this->category($p, 'tm_cat_asg2');
        $orphan = $this->ticket($p, $cat);
        $this->conn->executeStatement('UPDATE ticket_ticket SET category_id = NULL WHERE id = ?', [$orphan]);

        $this->assertFalse(TicketManager::assignTicketToUser(0, 2));
        $this->assertFalse(TicketManager::assignTicketToUser(987654321, 2));
        // Fixed: the existence check is get_ticket_detail_by_id(), which no longer hides a ticket without category
        // (deleteCategory() sets category_id to NULL on its tickets, which made them unassignable).
        $this->assertTrue(TicketManager::assignTicketToUser($orphan, 2));
        $this->assertSame('2', (string) $this->row($orphan)['assigned_last_user']);
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM ticket_assigned_log WHERE ticket_id = ?', [$orphan]));
    }

    public function testGetAssignLogIsNewestFirstAndLabelsUnassignRows(): void
    {
        $p = $this->project('tm_proj_log');
        $t = $this->ticket($p, $this->category($p, 'tm_cat_log'));
        foreach ([[2, '2021-01-01 10:00:00'], [null, '2021-03-01 10:00:00'], [3, '2021-02-01 10:00:00']] as [$uid, $date]) {
            $this->conn->insert('ticket_assigned_log', [
                'ticket_id' => $t, 'user_id' => $uid, 'sys_insert_user_id' => 1, 'assigned_date' => $date,
            ]);
        }
        $this->conn->insert('ticket_assigned_log', [
            'ticket_id' => $this->ticket($p, $this->category($p, 'tm_cat_log2')),
            'user_id' => 2, 'sys_insert_user_id' => 1, 'assigned_date' => '2021-04-01 10:00:00',
        ]);

        $log = TicketManager::get_assign_log($t);

        $this->assertCount(3, $log, 'other tickets log rows are not mixed in');
        $this->assertSame([null, '3', '2'], array_map(static fn ($r) => null === $r['user_id'] ? null : (string) $r['user_id'], $log), 'ORDER BY assigned_date DESC');
        $this->assertSame('Unassign', $log[0]['assignuser'], 'NULL user means "Unassign"');
        $this->assertStringContainsString('user_id=3', $log[1]['assignuser']);
        $this->assertStringContainsString('>fallback_user</a>', $log[1]['assignuser']);
        $this->assertStringContainsString('>admin</a>', $log[0]['insertuser']);
        $this->assertSame([], TicketManager::get_assign_log(987654321));
    }

    // ---------------------------------------------------------------- detail & messages

    public function testGetTicketDetailJoinsCategoryStatusPriorityAndKeepsRawDates(): void
    {
        $p = $this->project('tm_proj_det');
        $cat = $this->category($p, 'tm_cat_det');
        $t = $this->ticket($p, $cat, ['status_id' => 2, 'priority_id' => 2, 'course_id' => 1]);

        $d = TicketManager::get_ticket_detail_by_id($t);

        $this->assertSame(['user', 'ticket', 'messages'], array_keys($d));
        $this->assertSame('tm_cat_det', $d['ticket']['title'], 'cat.title');
        $this->assertSame('Pending', $d['ticket']['status']);
        $this->assertSame('High', $d['ticket']['priority']);
        $this->assertSame('2021-03-15 10:00:00', $d['ticket']['start_date_from_db']);
        $this->assertNull($d['ticket']['end_date_from_db']);
        $this->assertSame('2021-03-15 10:00:00', $d['ticket']['sys_lastedit_datetime_from_db']);
        $this->assertNotSame('2021-03-15 10:00:00', $d['ticket']['start_date'], 'start_date is replaced by a formatted string');
        $this->assertStringContainsString('2021', $d['ticket']['start_date']);
        $this->assertStringContainsString('user_id=1', $d['ticket']['user_url']);
        $this->assertSame('admin', $d['user']['username']);
        $this->assertNotNull($d['ticket']['course_url'], 'ticket with a course gets a course link');
        $this->assertSame([], $d['messages']);
        $this->assertSame([], TicketManager::get_ticket_detail_by_id(987654321));
    }

    public function testGetTicketDetailKeepsATicketWhoseCategoryWasDeleted(): void
    {
        $p = $this->project('tm_proj_det_nocat');
        $t = $this->ticket($p, $this->category($p, 'tm_cat_det_nocat'));
        $this->conn->executeStatement('UPDATE ticket_ticket SET category_id = NULL WHERE id = ?', [$t]);

        $d = TicketManager::get_ticket_detail_by_id($t);

        $this->assertSame($t, (int) $d['ticket']['id']);
        $this->assertNull($d['ticket']['title'], 'no category: cat.title is NULL, the ticket is still returned');
        $this->assertSame('New', $d['ticket']['status']);
    }

    public function testGetTicketDetailListsMessagesOfActiveUsersOnly(): void
    {
        $p = $this->project('tm_proj_det2');
        $t = $this->ticket($p, $this->category($p, 'tm_cat_det2'));
        // fixture user 3 (fallback_user) is soft-deleted (active = -2): its messages are hidden.
        $mAdmin = $this->message($t, 1, ['subject' => 'tm_msg_admin']);
        $this->message($t, 3, ['subject' => 'tm_msg_gone']);
        $mThird = $this->message($t, 2, ['subject' => 'tm_msg_third']);
        $this->message($this->ticket($p, $this->category($p, 'tm_cat_det3')), 1, ['subject' => 'tm_msg_other_ticket']);

        $d = TicketManager::get_ticket_detail_by_id($t);

        $this->assertSame(['tm_msg_admin', 'tm_msg_third'], $this->col($d['messages'], 'subject'));
        $this->assertSame([$mAdmin, $mThird], array_map('intval', $this->col($d['messages'], 'message_id')));
        $this->assertEquals([true, false], $this->col($d['messages'], 'admin'));
        $this->assertSame('anon', $d['messages'][1]['user_info']['username']);
        // Fixed: "SELECT *" over message and user made the duplicate `id` (and `status`) columns the USER's.
        $this->assertSame([$mAdmin, $mThird], array_map('intval', $this->col($d['messages'], 'id')), 'id is the message id');
        $this->assertSame(['NOL', 'NOL'], $this->col($d['messages'], 'status'), 'status is the message status, not the user status');
        $this->assertSame([1, 2], array_map('intval', $this->col($d['messages'], 'user_id')));
        $this->assertArrayNotHasKey('password', $d['messages'][0], 'raw user columns (password hash...) are not copied into the message');
        $this->assertArrayNotHasKey('attachments', $d['messages'][0]);
    }

    public function testInsertMessageStoresRowRecountsTotalAndSubscribesAuthor(): void
    {
        $p = $this->project('tm_proj_msg');
        $t = $this->ticket($p, $this->category($p, 'tm_cat_msg'), ['sys_lastedit_user_id' => 3]);
        $this->message($t, 3);

        $this->assertTrue(TicketManager::insertMessage($t, 'tm_s', 'tm_c', [], $this->other->getId()));
        $this->assertTrue(TicketManager::insertMessage($t, 'tm_s2', 'tm_c2', [], 1, 'LEI'));

        $msgs = $this->conn->fetchAllAssociative('SELECT * FROM ticket_message WHERE ticket_id = ? ORDER BY id', [$t]);
        $this->assertCount(3, $msgs);
        $this->assertSame('tm_s', $msgs[1]['subject']);
        $this->assertSame('tm_c', $msgs[1]['message']);
        $this->assertSame('NOL', $msgs[1]['status'], 'default status is NOL (unread)');
        $this->assertSame('LEI', $msgs[2]['status']);
        $this->assertSame((string) $this->other->getId(), (string) $msgs[1]['sys_insert_user_id']);
        $this->assertNowish($msgs[1]['sys_insert_datetime']);
        $ticket = $this->row($t);
        $this->assertSame(3, (int) $ticket['total_messages'], 'total_messages is recounted, not incremented');
        $this->assertSame('1', (string) $ticket['sys_lastedit_user_id']);
        $this->assertNowish($ticket['sys_lastedit_datetime']);
        $this->assertSame(
            [1, 2],
            array_map('intval', $this->conn->fetchFirstColumn('SELECT user_id FROM ticket_rel_user WHERE ticket_id = ? ORDER BY user_id', [$t]))
        );
    }

    public function testInsertMessageAppendsConfirmationFormAndRejectsUploadErrorsWithoutWriting(): void
    {
        $p = $this->project('tm_proj_msg2');
        $t = $this->ticket($p, $this->category($p, 'tm_cat_msg2'));

        TicketManager::insertMessage($t, 'tm_s', 'tm_answer', [], 1, 'NOL', true);
        $content = $this->conn->fetchOne('SELECT message FROM ticket_message WHERE ticket_id = ?', [$t]);
        $this->assertStringStartsWith('tm_answer<form', $content);
        $this->assertStringContainsString('ticket_details.php?ticket_id='.$t, $content);

        // Fixed: an upload error used to be reported as false only AFTER the message row was written and counted.
        // The uploads are checked first now: on failure nothing is persisted.
        $this->assertFalse(TicketManager::insertMessage($t, 'tm_s2', 'x', [['error' => UPLOAD_ERR_INI_SIZE]], 1));
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM ticket_message WHERE ticket_id = ?', [$t]));
        $this->assertSame(0, (int) $this->conn->fetchOne("SELECT COUNT(*) FROM ticket_message WHERE subject = 'tm_s2'"));
        $this->assertSame(1, (int) $this->row($t)['total_messages']);
        $this->assertFalse(
            TicketManager::insertMessage($t, 'tm_s2b', 'x', [['error' => UPLOAD_ERR_NO_FILE], ['error' => UPLOAD_ERR_PARTIAL]], 1),
            'one bad file anywhere in the list rejects the whole message'
        );
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM ticket_message WHERE ticket_id = ?', [$t]));
        // "no file" entries are ignored.
        $this->assertTrue(TicketManager::insertMessage($t, 'tm_s3', 'x', [['error' => UPLOAD_ERR_NO_FILE]], 1));
    }

    public function testUpdateMessageStatusAsAdminMarksOnlyTheGivenUsersMessagesReadAndMovesNewTicketToPending(): void
    {
        $p = $this->project('tm_proj_read');
        $cat = $this->category($p, 'tm_cat_read');
        $t = $this->ticket($p, $cat, ['status_id' => 1]);
        $tUnconfirmed = $this->ticket($p, $cat, ['status_id' => 3]);
        $mUser = $this->message($t, 2);
        $mAdmin = $this->message($t, 1);
        $this->message($tUnconfirmed, 2);

        $this->assertTrue(TicketManager::update_message_status($t, 2));

        $status = fn (int $id) => $this->conn->fetchOne('SELECT status FROM ticket_message WHERE id = ?', [$id]);
        $this->assertSame('LEI', $status($mUser));
        $this->assertSame('NOL', $status($mAdmin), 'admin acts on messages written by $userId only');
        $this->assertSame(TicketManager::STATUS_PENDING, (int) $this->row($t)['status_id']);
        $this->assertSame('1', (string) $this->conn->fetchOne('SELECT sys_lastedit_user_id FROM ticket_message WHERE id = ?', [$mUser]));

        $this->assertTrue(TicketManager::update_message_status($tUnconfirmed, 2));
        $this->assertSame(3, (int) $this->row($tUnconfirmed)['status_id'], 'only NEW tickets become PENDING');
        $this->assertFalse(TicketManager::update_message_status($t, 3), 'nothing written by that user');
    }

    public function testUpdateMessageStatusAsNonAdminMarksMessagesOfOthersRead(): void
    {
        $p = $this->project('tm_proj_read2');
        $t = $this->ticket($p, $this->category($p, 'tm_cat_read2'), ['status_id' => 1]);
        $mine = $this->message($t, 2);
        $theirs = $this->message($t, 1);
        $this->loginAs($this->other);

        $this->assertTrue(TicketManager::update_message_status($t, 2));

        $status = fn (int $id) => $this->conn->fetchOne('SELECT status FROM ticket_message WHERE id = ?', [$id]);
        $this->assertSame('NOL', $status($mine), 'non-admin never marks their own messages');
        $this->assertSame('LEI', $status($theirs));
        $this->assertSame(TicketManager::STATUS_PENDING, (int) $this->row($t)['status_id']);
    }

    // ---------------------------------------------------------------- status transitions

    public function testUpdateTicketStatusWritesStatusAndEditorOrReturnsFalseForUnknownTicket(): void
    {
        $p = $this->project('tm_proj_st');
        $t = $this->ticket($p, $this->category($p, 'tm_cat_st'));

        $this->assertTrue(TicketManager::update_ticket_status(TicketManager::STATUS_PENDING, $t, 3));

        $row = $this->row($t);
        $this->assertSame(TicketManager::STATUS_PENDING, (int) $row['status_id']);
        $this->assertSame('3', (string) $row['sys_lastedit_user_id']);
        $this->assertNowish($row['sys_lastedit_datetime']);
        $this->assertFalse(TicketManager::update_ticket_status(2, 987654321, 1));
    }

    public function testUpdateTicketSetsPriorityAndStatusFromParams(): void
    {
        $p = $this->project('tm_proj_upd');
        $t = $this->ticket($p, $this->category($p, 'tm_cat_upd'));

        $this->assertTrue(TicketManager::updateTicket(['priority_id' => '3', 'status_id' => '2'], $t, 3));

        $row = $this->row($t);
        $this->assertSame(3, (int) $row['priority_id']);
        $this->assertSame(2, (int) $row['status_id']);
        $this->assertSame('3', (string) $row['sys_lastedit_user_id']);
        $this->assertNowish($row['sys_lastedit_datetime']);
    }

    public function testUpdateTicketLeavesPriorityAndStatusUntouchedWhenNotGiven(): void
    {
        $p = $this->project('tm_proj_upd2');
        $t = $this->ticket($p, $this->category($p, 'tm_cat_upd2'), ['priority_id' => 2, 'status_id' => 3]);

        // Fixed: missing params were written as '' (0 / NULL), wiping the ticket's priority and status.
        $this->assertTrue(TicketManager::updateTicket([], $t, 3));
        $row = $this->row($t);
        $this->assertSame(2, (int) $row['priority_id']);
        $this->assertSame(3, (int) $row['status_id']);
        $this->assertSame('3', (string) $row['sys_lastedit_user_id'], 'the edit is still recorded');
        $this->assertNowish($row['sys_lastedit_datetime']);

        $this->assertTrue(TicketManager::updateTicket(['status_id' => '4'], $t, 1));
        $row = $this->row($t);
        $this->assertSame(2, (int) $row['priority_id'], 'only the given column changes');
        $this->assertSame(4, (int) $row['status_id']);
    }

    public function testSendAlertRaisesPriorityToHigh(): void
    {
        $p = $this->project('tm_proj_alert');
        $t = $this->ticket($p, $this->category($p, 'tm_cat_alert'), ['priority_id' => 3]);

        TicketManager::send_alert($t, 2);

        $row = $this->row($t);
        $this->assertSame(TicketManager::PRIORITY_HIGH, (int) $row['priority_id']);
        $this->assertSame('2', (string) $row['sys_lastedit_user_id']);
        $this->assertNowish($row['sys_lastedit_datetime']);
    }

    public function testCloseTicketSetsStatusClosedAndEndDate(): void
    {
        $p = $this->project('tm_proj_close');
        $cat = $this->category($p, 'tm_cat_close');
        $t = $this->ticket($p, $cat);
        $other = $this->ticket($p, $cat);

        TicketManager::close_ticket($t, 1);

        $row = $this->row($t);
        $this->assertSame(TicketManager::STATUS_CLOSED, (int) $row['status_id']);
        $this->assertNowish($row['end_date']);
        $this->assertSame($row['end_date'], $row['sys_lastedit_datetime']);
        $this->assertNull($this->row($other)['end_date']);
        $this->assertSame(1, (int) $this->row($other)['status_id']);
    }

    public function testCloseOldTicketsOnlyClosesStaleTicketsInOpenWorkflowStatuses(): void
    {
        $p = $this->project('tm_proj_old');
        $cat = $this->category($p, 'tm_cat_old');
        $old = '2020-01-01 00:00:00';
        $stalePending = $this->ticket($p, $cat, ['status_id' => 2, 'sys_lastedit_datetime' => $old]);
        $staleUnconfirmed = $this->ticket($p, $cat, ['status_id' => 3, 'sys_lastedit_datetime' => $old]);
        $staleNew = $this->ticket($p, $cat, ['status_id' => 1, 'sys_lastedit_datetime' => $old]);
        $staleForwarded = $this->ticket($p, $cat, ['status_id' => 5, 'sys_lastedit_datetime' => $old]);
        $staleClosed = $this->ticket($p, $cat, ['status_id' => 4, 'sys_lastedit_datetime' => $old]);
        $fresh = $this->ticket($p, $cat, ['status_id' => 2, 'sys_lastedit_datetime' => $this->utc('-3 days')]);
        $edge = $this->ticket($p, $cat, ['status_id' => 2, 'sys_lastedit_datetime' => $this->utc('-8 days')]);

        TicketManager::close_old_tickets();

        $status = fn (int $id) => (int) $this->row($id)['status_id'];
        $this->assertSame(4, $status($stalePending));
        $this->assertSame(4, $status($staleUnconfirmed));
        $this->assertSame(4, $status($edge), 'more than 7 days idle');
        $this->assertSame(1, $status($staleNew), 'NEW tickets are never auto-closed');
        $this->assertSame(5, $status($staleForwarded));
        $this->assertSame(2, $status($fresh));
        $this->assertSame($old, $this->row($staleClosed)['sys_lastedit_datetime'], 'already closed rows are not touched');
        $closed = $this->row($stalePending);
        $this->assertNowish($closed['end_date']);
        $this->assertSame('1', (string) $closed['sys_lastedit_user_id']);
    }

    // ---------------------------------------------------------------- listing & counting

    /**
     * Five tickets in a fresh project; the 5th (listed last by id, distinct status/dates/messages so no sort ties) has no
     * category, which is what deleteCategory() leaves behind.
     * Dates (start_date): t1 15/03/2021, t2 02/12/2019, t3 01/07/2022, t4 01/06/2020.
     *
     * @return array{0: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int}
     */
    private function listFixture(): array
    {
        $p = $this->project('tm_proj_list2');
        $catA = $this->category($p, 'tm_cat_alpha');
        $catB = $this->category($p, 'tm_cat_beta');
        $t1 = $this->ticket($p, $catA, [
            'subject' => 'tm_subject_1', 'status_id' => 1, 'start_date' => '2021-03-15 10:00:00',
            'sys_lastedit_datetime' => '2021-03-20 10:00:00', 'total_messages' => 4, 'source' => 'tm_src_a',
        ]);
        $t2 = $this->ticket($p, $catB, [
            'subject' => 'tm_subject_2', 'status_id' => 2, 'priority_id' => 2, 'start_date' => '2019-12-02 10:00:00',
            'sys_lastedit_datetime' => '2021-03-10 10:00:00', 'total_messages' => 1, 'assigned_last_user' => 3,
            'sys_insert_user_id' => 2, 'keyword' => 'tm_kw_zebra', 'course_id' => 1,
        ]);
        $t3 = $this->ticket($p, $catA, [
            'subject' => 'tm_subject_3', 'status_id' => 4, 'start_date' => '2022-07-01 10:00:00',
            'sys_lastedit_datetime' => '2021-03-30 10:00:00', 'total_messages' => 9, 'assigned_last_user' => 2,
            'sys_insert_user_id' => 3, 'personal_email' => 'tm_pe@example.com', 'message' => 'tm_unique_body',
        ]);
        $t4 = $this->ticket($p, $catA, [
            'subject' => 'tm_subject_4', 'status_id' => 5, 'start_date' => '2020-06-01 10:00:00',
            'sys_lastedit_datetime' => '2021-03-15 10:00:00',
        ]);
        $orphan = $this->ticket($p, $catA, [
            'subject' => 'tm_subject_orphan', 'status_id' => 3, 'start_date' => '2021-12-31 10:00:00',
            'sys_lastedit_datetime' => '2021-03-25 10:00:00', 'total_messages' => 2,
        ]);
        $this->conn->executeStatement('UPDATE ticket_ticket SET category_id = NULL WHERE id = ?', [$orphan]);
        $_GET['project_id'] = (string) $p;

        return [$p, $t1, $t2, $t3, $t4, $orphan, $catA, $catB];
    }

    /**
     * @param array<string, string> $get
     *
     * @return list<string>
     */
    private function listSubjects(array $get = [], int $column = 0, string $direction = 'ASC'): array
    {
        $pid = $_GET['project_id'];
        $_GET = $get + ['project_id' => $pid];

        return $this->subjects(TicketManager::getTicketsByCurrentUser(0, 50, $column, $direction));
    }

    public function testGetTicketsByCurrentUserAsAdminReturnsFullRowsInTheRequestedOrder(): void
    {
        $this->listFixture();

        $byId = TicketManager::getTicketsByCurrentUser(0, 50, 0, 'ASC');
        $this->assertSame(
            ['tm_subject_1', 'tm_subject_2', 'tm_subject_3', 'tm_subject_4', 'tm_subject_orphan'],
            $this->subjects($byId),
            'Fixed: a ticket whose category was deleted is still listed (the category join is a LEFT JOIN)'
        );
        $this->assertNull($byId[4][4], 'no category title for it');
        $this->assertCount(8, $byId[0], 'admins get 8 columns');
        $this->assertSame('New', $byId[0][1]);
        $this->assertSame('tm_cat_alpha', $byId[0][4]);
        $this->assertSame(4, (int) $byId[0][7]);
        $this->assertStringContainsString('>tm_code_', $byId[0][0]);
        $this->assertStringContainsString('ticket_details.php?ticket_id=', $byId[0][0]);

        $this->assertSame(['tm_subject_orphan', 'tm_subject_4', 'tm_subject_3', 'tm_subject_2', 'tm_subject_1'], $this->listSubjects([], 0, 'DESC'));
        $this->assertSame(['tm_subject_3', 'tm_subject_1', 'tm_subject_orphan', 'tm_subject_2', 'tm_subject_4'], $this->listSubjects([], 7, 'DESC'), 'column 7 = total_messages 9,4,2,1,0');
        $this->assertSame(['tm_subject_1', 'tm_subject_2', 'tm_subject_3', 'tm_subject_4', 'tm_subject_orphan'], $this->listSubjects([], 8, 'ASC'), 'column 8 = subject');
        $this->assertSame(['tm_subject_2', 'tm_subject_4', 'tm_subject_1', 'tm_subject_orphan', 'tm_subject_3'], $this->listSubjects([], 2, 'ASC'), 'column 2 = start_date');
        $this->assertSame(['tm_subject_3', 'tm_subject_orphan', 'tm_subject_1', 'tm_subject_4', 'tm_subject_2'], $this->listSubjects([], 3, 'DESC'), 'column 3 = sys_lastedit_datetime');
        $this->assertSame(['tm_subject_3', 'tm_subject_4', 'tm_subject_1', 'tm_subject_2', 'tm_subject_orphan'], $this->listSubjects([], 1, 'ASC'), 'column 1 = status title: Closed, Forwarded, New, Pending, Unconfirmed');
        $this->assertSame(['tm_subject_1', 'tm_subject_2', 'tm_subject_3', 'tm_subject_4', 'tm_subject_orphan'], $this->listSubjects([], 99, 'ASC'), 'unknown column falls back to ticket id');
    }

    public function testGetTicketsByCurrentUserPaginatesWithOffsetAndCount(): void
    {
        $this->listFixture();

        $this->assertSame(
            ['tm_subject_3', 'tm_subject_orphan'],
            $this->subjects(TicketManager::getTicketsByCurrentUser(0, 2, 3, 'DESC'))
        );
        $this->assertSame(
            ['tm_subject_1', 'tm_subject_4', 'tm_subject_2'],
            $this->subjects(TicketManager::getTicketsByCurrentUser(2, 5, 3, 'DESC')),
            'LIMIT offset,count'
        );
    }

    public function testGetTicketsByCurrentUserLabelsAssigneeAndUnassignedStates(): void
    {
        $this->listFixture();

        $rows = TicketManager::getTicketsByCurrentUser(0, 50, 0, 'ASC');

        $this->assertStringContainsString('To be assigned', $rows[0][6], 'no assignee, not forwarded');
        $this->assertStringContainsString('fallback_user', $rows[1][6]);
        $this->assertStringContainsString('user_id=3', $rows[1][6]);
        $this->assertStringContainsString('anon', $rows[2][6]);
        $this->assertStringContainsString('Message resent', $rows[3][6], 'forwarded + unassigned');
    }

    public function testGetTicketsByCurrentUserSimpleKeywordSearchesTicketCategoryStatusAndPriorityTexts(): void
    {
        [, , $t2] = $this->listFixture();

        $this->assertSame(['tm_subject_2'], $this->listSubjects(['keyword' => 'tm_kw_zebra']), 'ticket.keyword');
        $this->assertSame(['tm_subject_1', 'tm_subject_3', 'tm_subject_4'], $this->listSubjects(['keyword' => 'tm_cat_alpha']), 'category title');
        $this->assertSame(['tm_subject_3'], $this->listSubjects(['keyword' => 'tm_pe@']), 'personal_email');
        $this->assertSame(['tm_subject_1'], $this->listSubjects(['keyword' => 'tm_src_a']), 'source');
        $this->assertSame(['tm_subject_3'], $this->listSubjects(['keyword' => 'tm_unique_body']), 'message body');
        $this->assertSame(['tm_subject_3'], $this->listSubjects(['keyword' => 'Closed']), 'status title');
        $this->assertSame(['tm_subject_2'], $this->listSubjects(['keyword' => ' High ']), 'priority title, keyword is trimmed');
        $this->assertSame([], $this->listSubjects(['keyword' => 'tm_nothing_matches']));
        $this->assertContains('tm_subject_2', $this->listSubjects(['keyword' => (string) $t2]), 'ticket id is matched with LIKE');
    }

    public function testGetTicketsByCurrentUserStructuredFilters(): void
    {
        [, , , , , , $catA, $catB] = $this->listFixture();
        $all = ['tm_subject_1', 'tm_subject_2', 'tm_subject_3', 'tm_subject_4', 'tm_subject_orphan'];

        $this->assertSame(['tm_subject_2'], $this->listSubjects(['keyword_category' => (string) $catB]));
        $this->assertSame(['tm_subject_1', 'tm_subject_3', 'tm_subject_4'], $this->listSubjects(['keyword_category' => (string) $catA]));
        $this->assertSame(['tm_subject_2'], $this->listSubjects(['keyword_assigned_to' => '3']));
        $this->assertSame(['tm_subject_3'], $this->listSubjects(['keyword_status' => '4']));
        $this->assertSame(['tm_subject_2'], $this->listSubjects(['keyword_priority' => '2']));
        $this->assertSame(['tm_subject_1'], $this->listSubjects(['keyword_source' => 'tm_src_a']));
        $this->assertSame($all, $this->listSubjects(['keyword_status' => '']), 'empty filter value is ignored');
        $this->assertSame([], $this->listSubjects(['project_id' => '987654']), 'project filter');
    }

    public function testGetTicketsByCurrentUserCourseKeywordMatchesCourseTitleOrCodes(): void
    {
        $this->listFixture();
        $course = $this->conn->fetchAssociative('SELECT title, code FROM course WHERE id = 1');

        $this->assertSame(['tm_subject_2'], $this->listSubjects(['keyword_course' => substr((string) $course['code'], 0, 4)]));
        $this->assertSame(['tm_subject_2'], $this->listSubjects(['keyword_course' => (string) $course['title']]));
        $this->assertSame([], $this->listSubjects(['keyword_course' => 'tm_no_such_course']));
    }

    public function testGetTicketsByCurrentUserDateFiltersCompareRealDatesNotFormattedStrings(): void
    {
        $this->listFixture();

        // Fixed: the dd/mm/YYYY input is parsed and compared as a date (it used to be a string comparison against
        // DATE_FORMAT(start_date, '%d/%m/%Y'), where "01/07/2022" < "10/03/2021").
        // t1 = 15/03/2021, t2 = 02/12/2019, t3 = 01/07/2022, t4 = 01/06/2020, orphan = 31/12/2021.
        $this->assertSame(
            ['tm_subject_1', 'tm_subject_3', 'tm_subject_orphan'],
            $this->listSubjects(['keyword_start_date_start' => '10/03/2021']),
            'start only: real >=, the later year 2022 is kept'
        );
        $this->assertSame(
            ['tm_subject_1', 'tm_subject_2', 'tm_subject_3', 'tm_subject_4', 'tm_subject_orphan'],
            $this->listSubjects(['keyword_start_date_start' => '01/01/2000'])
        );
        $this->assertSame(
            ['tm_subject_1'],
            $this->listSubjects(['keyword_start_date_start' => '10/03/2021', 'keyword_start_date_end' => '20/03/2021'])
        );
        $this->assertSame(
            ['tm_subject_1', 'tm_subject_4'],
            $this->listSubjects(['keyword_start_date_start' => '01/06/2020', 'keyword_start_date_end' => '15/03/2021']),
            'both bounds are inclusive days, across years'
        );
        $this->assertSame(
            [],
            $this->listSubjects(['keyword_start_date_start' => '20/03/2021', 'keyword_start_date_end' => '10/03/2021'])
        );
        $this->assertSame(
            ['tm_subject_1', 'tm_subject_2', 'tm_subject_3', 'tm_subject_4', 'tm_subject_orphan'],
            $this->listSubjects(['keyword_start_date_end' => '10/03/2021']),
            'an end date alone is ignored'
        );
        $this->assertSame(
            ['tm_subject_1', 'tm_subject_2', 'tm_subject_3', 'tm_subject_4', 'tm_subject_orphan'],
            $this->listSubjects(['keyword_start_date_start' => "31/02/2021' OR '1'='1"]),
            'a value that is not a real dd/mm/YYYY date is ignored (and never reaches the SQL text)'
        );
    }

    public function testGetTicketsByCurrentUserNeverInterpolatesDirectionOrLimitIntoSql(): void
    {
        $this->listFixture();

        // Security hardening: $direction and the LIMIT values reach the SQL text, so they are allowlisted / cast.
        // A smuggled "ASC, 1=1" used to be executed as part of ORDER BY and kept the ascending order; now any
        // value that is not ASC/DESC falls back to the default DESC.
        $this->assertSame(
            ['tm_subject_orphan', 'tm_subject_4', 'tm_subject_3', 'tm_subject_2', 'tm_subject_1'],
            $this->subjects(TicketManager::getTicketsByCurrentUser(0, 50, 0, 'ASC, 1=1'))
        );
        $this->assertSame(
            ['tm_subject_1', 'tm_subject_2', 'tm_subject_3', 'tm_subject_4', 'tm_subject_orphan'],
            $this->subjects(TicketManager::getTicketsByCurrentUser(0, 50, 0, 'asc')),
            'direction is case-insensitive'
        );
        // "2 OFFSET 1" used to produce "LIMIT 0, 2 OFFSET 1": a SQL syntax error. The values are cast to int.
        $this->assertCount(2, TicketManager::getTicketsByCurrentUser('0', '2 OFFSET 1', 0, 'ASC'));
    }

    public function testGetTicketsByCurrentUserNonAdminSeesOnlyOwnOrAssignedTicketsWithFiveColumns(): void
    {
        $this->listFixture();
        $this->loginAs($this->other);

        $rows = TicketManager::getTicketsByCurrentUser(0, 50, 0, 'ASC');

        // anon (id 2): t2 inserted by 2, t3 assigned to 2.
        $this->assertSame(['tm_subject_2', 'tm_subject_3'], $this->subjects($rows));
        $this->assertCount(5, $rows[0], 'non admins do not get creator/assignee/messages columns');
        $this->assertStringNotContainsString('load_history_ticket', $rows[0][0]);
    }

    public function testListingsReturnNothingWithoutCurrentUser(): void
    {
        $this->listFixture();
        ChamiloSession::write('_user', null);
        self::getContainer()->get('security.token_storage')->setToken(null);

        $this->assertSame([], TicketManager::getTicketsByCurrentUser(0, 50, 0, 'ASC'));
        $this->assertSame(0, TicketManager::getTotalTicketsCurrentUser());
    }

    public function testGetTotalTicketsCurrentUserCountsWhatTheListShows(): void
    {
        $this->listFixture();
        $pid = $_GET['project_id'];

        $this->assertSame(5, TicketManager::getTotalTicketsCurrentUser(), 'the ticket without category is counted, as listed (LEFT JOIN)');

        // Fixed: the total searches the same texts as the list, including category/status/priority titles.
        $_GET = ['project_id' => $pid, 'keyword' => 'tm_cat_alpha'];
        $this->assertSame(3, TicketManager::getTotalTicketsCurrentUser());
        $_GET = ['project_id' => $pid, 'keyword' => 'Closed'];
        $this->assertSame(1, TicketManager::getTotalTicketsCurrentUser());
        $_GET = ['project_id' => $pid, 'keyword' => 'tm_kw_zebra'];
        $this->assertSame(1, TicketManager::getTotalTicketsCurrentUser());
        $_GET = ['project_id' => $pid, 'keyword' => 'tm_pe@example'];
        $this->assertSame(1, TicketManager::getTotalTicketsCurrentUser());

        $_GET = ['project_id' => $pid, 'keyword_status' => '5'];
        $this->assertSame(1, TicketManager::getTotalTicketsCurrentUser());
        $_GET = ['project_id' => $pid, 'keyword_assigned_to' => '2', 'keyword_priority' => '1'];
        $this->assertSame(1, TicketManager::getTotalTicketsCurrentUser());
        $_GET = ['project_id' => '987654'];
        $this->assertSame(0, TicketManager::getTotalTicketsCurrentUser());
    }

    public function testGetTotalTicketsCurrentUserDateAndCourseFilters(): void
    {
        $this->listFixture();
        $pid = $_GET['project_id'];

        // Fixed: a start date alone is ">=" a real date, as in the list (it used to be an exact string match,
        // and the range only applied when an undocumented keyword_dates flag was also sent).
        $_GET = ['project_id' => $pid, 'keyword_start_date_start' => '15/03/2021'];
        $this->assertSame(3, TicketManager::getTotalTicketsCurrentUser());
        $_GET = ['project_id' => $pid, 'keyword_start_date_start' => '10/03/2021'];
        $this->assertSame(3, TicketManager::getTotalTicketsCurrentUser());
        $_GET = ['project_id' => $pid, 'keyword_start_date_start' => '10/03/2021', 'keyword_start_date_end' => '20/03/2021'];
        $this->assertSame(1, TicketManager::getTotalTicketsCurrentUser(), 'range without any keyword_dates flag');

        $course = $this->conn->fetchAssociative('SELECT code FROM course WHERE id = 1');
        $_GET = ['project_id' => $pid, 'keyword_course' => (string) $course['code']];
        $this->assertSame(1, TicketManager::getTotalTicketsCurrentUser());
    }

    public function testGetTotalTicketsCurrentUserEqualsTheNumberOfRowsTheListReturnsForEveryFilter(): void
    {
        [, , , , , , $catA] = $this->listFixture();
        $pid = $_GET['project_id'];
        $course = $this->conn->fetchAssociative('SELECT code FROM course WHERE id = 1');
        $filters = [
            [],
            ['keyword' => 'tm_cat_alpha'],
            ['keyword' => 'Closed'],
            ['keyword' => ' High '],
            ['keyword' => (string) $catA],
            ['keyword_category' => (string) $catA],
            ['keyword_status' => '4'],
            ['keyword_start_date_start' => '10/03/2021'],
            ['keyword_start_date_start' => '01/06/2020', 'keyword_start_date_end' => '15/03/2021'],
            ['keyword_start_date_end' => '10/03/2021'],
            ['keyword_course' => (string) $course['code']],
            ['keyword' => 'tm_cat_alpha', 'keyword_start_date_start' => '10/03/2021', 'keyword_priority' => '1'],
        ];

        foreach ([$this->admin, $this->other] as $user) {
            $this->loginAs($user);
            foreach ($filters as $i => $filter) {
                $_GET = $filter + ['project_id' => $pid];
                $this->assertSame(
                    \count(TicketManager::getTicketsByCurrentUser(0, 500, 0, 'ASC')),
                    TicketManager::getTotalTicketsCurrentUser(),
                    'user '.$user->getUsername().', filter #'.$i.' '.json_encode($filter)
                );
            }
        }
    }

    public function testGetTotalTicketsCurrentUserNonAdminIsRestrictedToOwnOrAssigned(): void
    {
        $this->listFixture();

        $this->loginAs($this->other);
        $this->assertSame(2, TicketManager::getTotalTicketsCurrentUser(), 'anon: t2 inserted by 2, t3 assigned to 2');
        $this->loginAs($this->getUser('fallback_user'));
        $this->assertSame(2, TicketManager::getTotalTicketsCurrentUser(), 'fallback_user (soft-deleted fixture): t2 assigned to 3, t3 inserted by 3');
    }

    // ---------------------------------------------------------------- broken legacy queries

    public function testGetNumberOfMessagesCountsTicketsWithUnreadMessagesFromTheOtherSide(): void
    {
        $p = $this->project('tm_proj_unread');
        $cat = $this->category($p, 'tm_cat_unread');
        $before = (int) TicketManager::getNumberOfMessages();
        $this->loginAs($this->other);
        $beforeUser = (int) TicketManager::getNumberOfMessages();
        $this->loginAs($this->admin);

        // Fixed: the query used the pre-2.0 columns user.user_id / ticket.request_user and always threw; the
        // requester is now ticket.sys_insert_user_id and users are matched on user.id.
        $answered = $this->ticket($p, $cat, ['sys_insert_user_id' => 2]);
        $this->message($answered, 2);
        $this->message($answered, 2);
        $this->message($answered, 1);
        $forwarded = $this->ticket($p, $cat, ['sys_insert_user_id' => 2, 'status_id' => TicketManager::STATUS_FORWARDED]);
        $this->message($forwarded, 2);
        $readOnly = $this->ticket($p, $cat, ['sys_insert_user_id' => 2]);
        $this->message($readOnly, 2, ['status' => 'LEI']);
        $adminTicket = $this->ticket($p, $cat, ['sys_insert_user_id' => 1]);
        $this->message($adminTicket, 1);

        $this->assertSame($before + 1, TicketManager::getNumberOfMessages(), 'admin: tickets with an unread message written by a non-admin, forwarded ones excluded, tickets counted once');

        $this->loginAs($this->other);
        $this->assertSame($beforeUser + 1, TicketManager::getNumberOfMessages(), 'non-admin: only own tickets with an unread message written by an admin');
        $this->message($forwarded, 1);
        $this->message($readOnly, 1);
        $this->assertSame($beforeUser + 3, TicketManager::getNumberOfMessages());
        $this->message($adminTicket, 1);
        $this->assertSame($beforeUser + 3, TicketManager::getNumberOfMessages(), "someone else's ticket is never counted");
    }

    public function testExportTicketsByUserIdListsTicketsOfActiveUsersWithHeaderRow(): void
    {
        $p = $this->project('tm_proj_export');
        $cat = $this->category($p, 'tm_cat_export');
        $mine = $this->ticket($p, $cat, ['code' => 'tm_exp_1', 'sys_insert_user_id' => 2, 'total_messages' => 2, 'assigned_last_user' => 1]);
        $this->ticket($p, $cat, ['code' => 'tm_exp_gone', 'sys_insert_user_id' => 3]);

        // Fixed: joined on ticket.request_user / user.user_id (both gone), so it always threw.
        $_GET = ['submit_simple' => '1', 'keyword' => 'tm_exp_1'];
        $rows = TicketManager::export_tickets_by_user_id(0, 10, 0, 'DESC');

        $this->assertCount(2, $rows, 'header + the matching ticket');
        $this->assertSame(9, \count($rows[0]));
        $this->assertSame('tm_exp_1', $rows[1]['code']);
        $this->assertSame('tm_cat_export', $rows[1]['category']);
        $this->assertSame('Joe Anonymous', $rows[1]['fullname'], 'CONCAT(lastname, firstname) of the creator');
        $this->assertSame('2', (string) $rows[1]['messages']);
        $this->assertSame('Joe Doe', $rows[1]['responsable'], 'assignee as "firstname lastname"');

        $_GET = ['submit_simple' => '1', 'keyword' => 'tm_exp_gone'];
        $this->assertCount(1, TicketManager::export_tickets_by_user_id(0, 10, 0, 'DESC'), 'tickets of soft-deleted users are not exported');
        $this->assertSame($mine, (int) $this->conn->fetchOne("SELECT id FROM ticket_ticket WHERE code = 'tm_exp_1'"));
    }

    public function testExportTicketsByUserIdAdvancedFiltersUnreadAndRealDates(): void
    {
        $p = $this->project('tm_proj_export2');
        $cat = $this->category($p, 'tm_cat_export2');
        $unread = $this->ticket($p, $cat, ['code' => 'tm_exp_unread', 'sys_insert_user_id' => 2, 'start_date' => '2022-07-01 10:00:00']);
        $this->message($unread, 2);
        $answered = $this->ticket($p, $cat, ['code' => 'tm_exp_answered', 'sys_insert_user_id' => 2, 'start_date' => '2020-06-01 10:00:00']);
        $this->message($answered, 2, ['status' => 'LEI']);

        $base = [
            'submit_advanced' => '1', 'keyword_category' => '', 'keyword_request_user' => 'anon', 'keyword_assigned_to' => '',
            'keyword_start_date_start' => '', 'keyword_start_date_end' => '', 'keyword_status' => '', 'keyword_source' => '',
            'keyword_priority' => '', 'keyword_dates' => '', 'keyword_unread' => '', 'keyword_course' => '',
        ];
        $codes = function (array $get) use ($base): array {
            $_GET = $get + $base;
            $rows = TicketManager::export_tickets_by_user_id(0, 100, 0, 'DESC');
            $codes = array_filter(array_column(\array_slice($rows, 1), 'code'), static fn ($c) => str_starts_with($c, 'tm_exp_'));
            sort($codes);

            return $codes;
        };

        $this->assertSame(['tm_exp_answered', 'tm_exp_unread'], $codes([]), 'request user matched by username');
        $this->assertSame(['tm_exp_unread'], $codes(['keyword_unread' => 'yes']));
        $this->assertSame(['tm_exp_answered'], $codes(['keyword_unread' => 'no']));
        $this->assertSame(['tm_exp_unread'], $codes(['keyword_start_date_start' => '01/07/2022']), 'single date: that day, as a real date');
        $this->assertSame(
            ['tm_exp_answered', 'tm_exp_unread'],
            $codes(['keyword_dates' => '1', 'keyword_start_date_start' => '10/03/2019', 'keyword_start_date_end' => '15/08/2022']),
            'range across years (string comparison used to drop 01/07/2022 < 10/03/2019)'
        );
    }

    // ---------------------------------------------------------------- user removal & ORM-backed helpers

    public function testDeleteUserFromTicketSystemRemovesCategoryAssignmentsAndNullsNullableReferences(): void
    {
        $p = $this->project('tm_proj_gdpr');
        $cat = $this->category($p, 'tm_cat_gdpr');
        $t = $this->ticket($p, $cat, ['assigned_last_user' => 2, 'sys_lastedit_user_id' => 2]);
        TicketManager::addUsersToCategory($cat, [2, 1]);
        $this->conn->insert('ticket_assigned_log', ['ticket_id' => $t, 'user_id' => 2, 'sys_insert_user_id' => 1, 'assigned_date' => '2021-01-01 00:00:00']);

        TicketManager::deleteUserFromTicketSystem(2);

        $this->assertNull($this->row($t)['assigned_last_user']);
        $this->assertNull($this->row($t)['sys_lastedit_user_id']);
        $this->assertSame([1], array_map('intval', $this->col(TicketManager::getUsersInCategory($cat), 'user_id')), 'only the other user stays assigned');
        $this->assertNull($this->conn->fetchAssociative('SELECT user_id FROM ticket_assigned_log WHERE ticket_id = ?', [$t])['user_id']);
        $this->assertSame('1', (string) $this->row($t)['sys_insert_user_id'], 'tickets written by someone else are untouched');
    }

    public function testWorkflowServiceRemovesADeletedUserFromTheTicketSystem(): void
    {
        $p = $this->project('tm_proj_svc');
        $cat = $this->category($p, 'tm_cat_svc');
        $t = $this->ticket($p, $cat, ['assigned_last_user' => 2, 'sys_insert_user_id' => 2]);
        TicketManager::addUsersToCategory($cat, [2]);
        $fallback = (int) Container::getUserRepository()->getFallbackUser()->getId();

        self::getContainer()->get(TicketWorkflowService::class)->removeUserFromTicketSystem(2);

        $this->assertNull($this->row($t)['assigned_last_user']);
        $this->assertSame((string) $fallback, (string) $this->row($t)['sys_insert_user_id']);
        $this->assertSame([], TicketManager::getUsersInCategory($cat));
    }

    public function testDeleteUserFromTicketSystemReassignsAuthoredRowsToTheFallbackUser(): void
    {
        $p = $this->project('tm_proj_gdpr2');
        $cat = $this->category($p, 'tm_cat_gdpr2');
        $t = $this->ticket($p, $cat, ['sys_insert_user_id' => 2]);
        $other = $this->ticket($p, $cat, ['sys_insert_user_id' => 1]);
        $msg = $this->message($t, 2);
        $this->conn->insert('ticket_assigned_log', ['ticket_id' => $t, 'user_id' => 1, 'sys_insert_user_id' => 2, 'assigned_date' => '2021-01-01 00:00:00']);
        $this->conn->executeStatement('UPDATE ticket_category SET sys_insert_user_id = 2 WHERE id = ?', [$cat]);
        $fallback = (int) Container::getUserRepository()->getFallbackUser()->getId();
        $this->assertNotSame(2, $fallback);

        // Fixed: it used to set the NOT NULL sys_insert_user_id columns to NULL and throw half-way. Authored rows are
        // now kept (audit trail) and handed to the platform's fallback user, the way UserManager::delete_user() does.
        TicketManager::deleteUserFromTicketSystem(2);

        $this->assertSame((string) $fallback, (string) $this->row($t)['sys_insert_user_id']);
        $this->assertSame((string) $fallback, (string) $this->conn->fetchOne('SELECT sys_insert_user_id FROM ticket_message WHERE id = ?', [$msg]));
        $this->assertSame((string) $fallback, (string) $this->conn->fetchOne('SELECT sys_insert_user_id FROM ticket_assigned_log WHERE ticket_id = ?', [$t]));
        $this->assertSame((string) $fallback, (string) $this->conn->fetchOne('SELECT sys_insert_user_id FROM ticket_category WHERE id = ?', [$cat]));
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM ticket_message WHERE id = ?', [$msg]), 'rows are kept, not deleted');
        $this->assertSame('1', (string) $this->row($other)['sys_insert_user_id'], "someone else's rows are untouched");
    }

    public function testDeleteUserFromTicketSystemWithoutFallbackUserChangesNothingAndThrows(): void
    {
        $p = $this->project('tm_proj_gdpr3');
        $t = $this->ticket($p, $this->category($p, 'tm_cat_gdpr3'), ['sys_insert_user_id' => 2, 'assigned_last_user' => 2]);
        // Hide the fallback user for this test (rolled back with the test transaction).
        $this->conn->executeStatement('UPDATE user SET status = 6 WHERE username = ?', ['fallback_user']);

        $threw = false;

        try {
            TicketManager::deleteUserFromTicketSystem(2);
        } catch (Throwable) {
            $threw = true;
        }

        $this->assertTrue($threw, 'no sentinel user to take the NOT NULL columns: fail before touching anything');
        $this->assertSame('2', (string) $this->row($t)['sys_insert_user_id']);
        $this->assertSame('2', (string) $this->row($t)['assigned_last_user'], 'nothing half-done');
    }

    public function testTicketSubscriptionHelpersAndFollowers(): void
    {
        $p = $this->project('tm_proj_sub');
        $t = $this->ticket($p, $this->category($p, 'tm_cat_sub'));
        $this->getEntityManager()->clear();

        $this->assertFalse(TicketManager::isUserSubscribedToTicket($t, 2));
        TicketManager::subscribeUserToTicket($t, 2);
        $this->getEntityManager()->clear();
        $this->assertTrue(TicketManager::isUserSubscribedToTicket($t, 2));
        $this->assertSame([2], array_map(static fn (User $u) => $u->getId(), TicketManager::getFollowers($t)));
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM ticket_rel_user WHERE ticket_id = ? AND user_id = 2', [$t]));

        TicketManager::unsubscribeUserFromTicket($t, 2);
        $this->getEntityManager()->clear();
        $this->assertFalse(TicketManager::isUserSubscribedToTicket($t, 2));
        $this->assertFalse(TicketManager::isUserSubscribedToTicket(987654321, 2), 'unknown ticket is simply not subscribed');
    }

    public function testAttachmentLookupsReturnFalseWhenNothingStoredAndCriteriaListIsKeyedById(): void
    {
        $p = $this->project('tm_proj_att');
        $t = $this->ticket($p, $this->category($p, 'tm_cat_att'));
        $this->getEntityManager()->clear();

        $this->assertFalse(TicketManager::getTicketMessageAttachment(987654321));
        // Kept on purpose (documented in the docblock): "no attachments" is false, not an empty array.
        $this->assertFalse(TicketManager::getTicketMessageAttachmentsByTicketId($t));
        $this->assertSame([$t => $this->row($t)['code']], TicketManager::getTicketsFromCriteria(['id' => $t]));
    }
}
