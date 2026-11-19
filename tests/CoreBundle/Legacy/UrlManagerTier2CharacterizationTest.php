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
 * Extra pins for UrlManager that UrlManagerCharacterizationTest leaves loose: the exact url count,
 * the orders of the list queries, the "existing rows" read of the sync functions (each URL holds a
 * DIFFERENT row, so reading another URL's rows cannot pass by coincidence) and the 11 row limit of
 * the course search.
 */
final class UrlManagerTier2CharacterizationTest extends KernelTestCase
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

    public function testUrlCountIsTheNumberOfRowsOfTheTable(): void
    {
        $this->url('a');

        $this->assertSame((int) $this->conn()->fetchOne('SELECT COUNT(*) FROM access_url'), UrlManager::url_count());
    }

    public function testGetUrlRelUserDataOrderFollowsTheRequestedNameOrder(): void
    {
        $a = $this->url('a');
        $x = $this->createUser('um2_b_user');
        $y = $this->createUser('um2_a_user');
        $this->conn()->executeStatement("UPDATE user SET firstname = 'Aaa', lastname = 'Zzz' WHERE id = ?", [$x->getId()]);
        $this->conn()->executeStatement("UPDATE user SET firstname = 'Zzz', lastname = 'Aaa' WHERE id = ?", [$y->getId()]);
        $this->link('access_url_rel_user', 'user_id', (int) $x->getId(), $a);
        $this->link('access_url_rel_user', 'user_id', (int) $y->getId(), $a);

        $ids = static fn (array $rows): array => array_map(static fn (array $r): int => (int) $r['user_id'], $rows);

        $this->assertSame(
            [(int) $x->getId(), (int) $y->getId()],
            $ids(UrlManager::get_url_rel_user_data($a, ' ORDER BY firstname, lastname, username'))
        );
        $this->assertSame(
            [(int) $y->getId(), (int) $x->getId()],
            $ids(UrlManager::get_url_rel_user_data($a, ' ORDER BY lastname, firstname, username'))
        );
        // No explicit order: the platform's person-name convention decides.
        $expected = api_sort_by_first_name() ? [(int) $x->getId(), (int) $y->getId()] : [(int) $y->getId(), (int) $x->getId()];
        $this->assertSame($expected, $ids(UrlManager::get_url_rel_user_data($a)));
    }

    public function testGetUrlRelCourseCategoryIsSortedByTitleAndScopedToTheUrlRootsOnly(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $zeta = $this->category('um2_zeta');
        $alpha = $this->category('um2_alpha');
        $child = $this->category('um2_child', $alpha);
        $this->link('access_url_rel_course_category', 'course_category_id', $zeta, $a);
        $this->link('access_url_rel_course_category', 'course_category_id', $alpha, $a);
        $this->link('access_url_rel_course_category', 'course_category_id', $child, $a);
        $this->link('access_url_rel_course_category', 'course_category_id', $alpha, $b);

        $rows = UrlManager::getUrlRelCourseCategory($a);

        $this->assertSame(['um2_alpha', 'um2_zeta'], array_column($rows, 'title'));
        $this->assertSame([$a, $a], array_map('intval', array_column($rows, 'access_url_id')));
    }

    public function testUpdateUrlsRelCourseOnlyRemovesWhatTheUrlOwns(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        // A course that no other URL holds: reading the rows of the wrong URL would never find it.
        $own = $this->course('Um2Own', 'UM2OWN');
        UrlManager::add_course_to_url($own, $a);
        UrlManager::add_course_to_url(2, $b);

        UrlManager::update_urls_rel_course([], $a);

        $this->assertSame(0, $this->countPair('access_url_rel_course', 'c_id', $own, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_course', 'c_id', 2, $b));
    }

    public function testUpdateUrlsRelSessionOnlyRemovesWhatTheUrlOwns(): void
    {
        $a = $this->url('a');
        $b = $this->url('b');
        $s1 = $this->session('um2_s1');
        $s2 = $this->session('um2_s2');
        UrlManager::add_session_to_url($s1, $a);
        UrlManager::add_session_to_url($s2, $b);

        UrlManager::update_urls_rel_session([], $a);

        $this->assertSame(0, $this->countPair('access_url_rel_session', 'session_id', $s1, $a));
        $this->assertSame(1, $this->countPair('access_url_rel_session', 'session_id', $s2, $b));
    }

    public function testSearchCoursesByTitleOrCodeOrdersByTitleThenCodeAndStopsAtEleven(): void
    {
        foreach (['ZB', 'ZA'] as $code) {
            $this->course('Um2SameTitle', 'UM2'.$code);
        }
        $this->assertSame(['UM2ZA', 'UM2ZB'], array_column(UrlManager::searchCoursesByTitleOrCode('Um2SameTitle'), 'code'));

        for ($i = 1; $i <= 12; ++$i) {
            $this->course('Um2Many'.\sprintf('%02d', $i), 'UM2MANY'.$i);
        }
        $rows = UrlManager::searchCoursesByTitleOrCode('Um2Many');

        $this->assertCount(11, $rows);
        $this->assertSame('Um2Many01', $rows[0]['title']);
        $this->assertSame('Um2Many11', $rows[10]['title']);
    }

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function url(string $suffix): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO access_url (url, description, active, created_by, lft, lvl, rgt, is_login_only)
             VALUES (?, ?, 1, 1, 1, 0, 2, 0)',
            ['http://um2-'.$suffix.'.test/', 'um2']
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function session(string $name): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, position)
             VALUES (?, 0, 0, 0, 1, 0, 0)',
            [$name]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function course(string $title, string $code): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO course (language_id, code, title, visibility, video_url, sticky, creation_date, subscribe, unsubscribe)
             VALUES ((SELECT language_id FROM (SELECT language_id FROM course WHERE id = 1) l), ?, ?, 1, '', 0, '2030-01-01 00:00:00', 1, 0)",
            [$code, $title]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function category(string $title, ?int $parent = null): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO course_category (parent_id, title, code, tree_pos, children_count) VALUES (?, ?, ?, 0, 0)',
            [$parent, $title, strtoupper($title)]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function link(string $table, string $column, int $id, int $urlId): void
    {
        $this->conn()->executeStatement("INSERT INTO $table ($column, access_url_id) VALUES (?, ?)", [$id, $urlId]);
    }

    private function countPair(string $table, string $column, int $id, int $urlId): int
    {
        return (int) $this->conn()->fetchOne("SELECT COUNT(*) FROM $table WHERE $column = ? AND access_url_id = ?", [$id, $urlId]);
    }
}
