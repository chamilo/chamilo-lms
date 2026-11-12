<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use UrlManager;

/**
 * UrlManager::delete() must not be blocked by the access_url foreign keys declared RESTRICT: session_category,
 * branch, ticket_ticket/project/priority/status, access_url_rel_plugin and access_url_rel_color_theme. Rows that
 * only exist to serve the deleted URL are removed or detached, the rows of a sibling URL stay untouched.
 * Fixtures are prepared-statement INSERTs on throw-away URLs (http://umd-*.test/); no ON DELETE CASCADE is relied on.
 */
final class UrlManagerDeleteRestrictedRelationsTest extends KernelTestCase
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/admin/access_urls.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('_user');
        self::getContainer()->get('security.token_storage')->setToken(null);
        parent::tearDown();
    }

    public function testDeleteIsNotBlockedByRestrictForeignKeysAndLeavesTheSiblingUrlAlone(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $pluginId = $this->insert("INSERT INTO plugin (title, installed, installed_version) VALUES ('umd', 1, '1.0')");
        $themeId = $this->insert("INSERT INTO color_theme (title, variables, slug, created_at, updated_at) VALUES ('umd', '{}', 'umd-theme', '2030-01-01 00:00:00', '2030-01-01 00:00:00')");
        $ids = [];
        foreach ([$a, $b] as $u) {
            $ids[$u]['category'] = $this->insert('INSERT INTO session_category (title, access_url_id) VALUES (?, ?)', ['umd cat', $u]);
            $ids[$u]['session'] = $this->insert(
                'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, position, session_category_id)
                 VALUES (?, 0, 0, 0, 1, 0, 0, ?)',
                ['umd session '.$u, $ids[$u]['category']]
            );
            $ids[$u]['branch'] = $this->insert('INSERT INTO branch (unique_id, title, access_url_id) VALUES (?, ?, ?)', ['umd-'.$u, 'umd branch', $u]);
            $ids[$u]['project'] = $this->insert("INSERT INTO ticket_project (title, sys_insert_user_id, sys_insert_datetime, access_url_id) VALUES ('umd', 1, '2030-01-01 00:00:00', ?)", [$u]);
            $ids[$u]['priority'] = $this->insert("INSERT INTO ticket_priority (title, code, color, urgency, sys_insert_user_id, sys_insert_datetime, access_url_id) VALUES ('umd', 'umd', 'red', '1', 1, '2030-01-01 00:00:00', ?)", [$u]);
            $ids[$u]['status'] = $this->insert('INSERT INTO ticket_status (code, title, access_url_id) VALUES (?, ?, ?)', ['umd'.$u, 'umd', $u]);
            $ids[$u]['ticket'] = $this->insert(
                "INSERT INTO ticket_ticket (code, subject, personal_email, total_messages, sys_insert_user_id, sys_insert_datetime, access_url_id, project_id, priority_id, status_id)
                 VALUES (?, 'umd', 'umd@example.invalid', 0, 1, '2030-01-01 00:00:00', ?, ?, ?, ?)",
                ['umd'.$u, $u, $ids[$u]['project'], $ids[$u]['priority'], $ids[$u]['status']]
            );
            $this->insert('INSERT INTO access_url_rel_plugin (plugin_id, url_id, active) VALUES (?, ?, 1)', [$pluginId, $u]);
            $this->insert("INSERT INTO access_url_rel_color_theme (url_id, color_theme_id, active, created_at, updated_at) VALUES (?, ?, 1, '2030-01-01 00:00:00', '2030-01-01 00:00:00')", [$u, $themeId]);
        }

        $this->assertTrue(UrlManager::delete($a));
        $this->getEntityManager()->clear();

        $this->assertSame(0, $this->rowsWhere('access_url', 'id', $a), 'The URL row is gone');
        $this->assertSame(1, $this->rowsWhere('access_url', 'id', $b));

        // Link rows that only serve the URL are deleted.
        $this->assertSame(0, $this->rowsWhere('access_url_rel_plugin', 'url_id', $a));
        $this->assertSame(0, $this->rowsWhere('access_url_rel_color_theme', 'url_id', $a));
        // Session categories of the URL are deleted, their sessions survive without a category.
        $this->assertSame(0, $this->rowsWhere('session_category', 'id', $ids[$a]['category']));
        $this->assertSame(1, $this->rowsWhere('session', 'id', $ids[$a]['session']));
        $this->assertNull($this->conn()->fetchOne('SELECT session_category_id FROM session WHERE id = ?', [$ids[$a]['session']]) ?: null);
        // Branches, tickets and ticket dictionaries are detached, never destroyed.
        foreach (['branch' => 'branch', 'ticket_ticket' => 'ticket', 'ticket_project' => 'project', 'ticket_priority' => 'priority', 'ticket_status' => 'status'] as $table => $key) {
            $this->assertSame(1, $this->rowsWhere($table, 'id', $ids[$a][$key]), $table.' row kept');
            $this->assertNull($this->conn()->fetchOne("SELECT access_url_id FROM $table WHERE id = ?", [$ids[$a][$key]]) ?: null, $table.' detached');
        }

        // The sibling URL keeps everything, links included.
        $this->assertSame(1, $this->rowsWhere('access_url_rel_plugin', 'url_id', $b));
        $this->assertSame(1, $this->rowsWhere('access_url_rel_color_theme', 'url_id', $b));
        $this->assertSame(1, $this->rowsWhere('session_category', 'id', $ids[$b]['category']));
        $this->assertSame($ids[$b]['category'], (int) $this->conn()->fetchOne('SELECT session_category_id FROM session WHERE id = ?', [$ids[$b]['session']]));
        foreach (['branch' => 'branch', 'ticket_ticket' => 'ticket', 'ticket_project' => 'project', 'ticket_priority' => 'priority', 'ticket_status' => 'status'] as $table => $key) {
            $this->assertSame($b, (int) $this->conn()->fetchOne("SELECT access_url_id FROM $table WHERE id = ?", [$ids[$b][$key]]), $table);
        }
    }

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function url(string $suffix): int
    {
        return $this->insert(
            'INSERT INTO access_url (url, description, active, created_by, lft, lvl, rgt, is_login_only)
             VALUES (?, ?, 1, 1, 1, 0, 2, 0)',
            ['http://umd-'.$suffix.'.test/', 'umd']
        );
    }

    private function insert(string $sql, array $params = []): int
    {
        $this->conn()->executeStatement($sql, $params);

        return (int) $this->conn()->lastInsertId();
    }

    private function rowsWhere(string $table, string $column, int $id): int
    {
        return (int) $this->conn()->fetchOne("SELECT COUNT(*) FROM $table WHERE $column = ?", [$id]);
    }
}
