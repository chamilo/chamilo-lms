<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Link;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tier 3 characterization tests for Link (link.lib.php): the raw row helpers getLinkInfo() and getCategory().
 *
 * Not pinned, left raw on purpose (see the commit message): updateLink (no callers), delete_link_from_search_engine
 * (queries search_engine_ref columns that no longer exist), movecatlink (queries c_link_category.c_id, which does not
 * exist, and has no callers). Rows are inserted raw, titles start with "lt3_".
 */
final class LinkTier3CharacterizationTest extends KernelTestCase
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
        $request = Request::create('/main/link/link.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        ChamiloSession::erase('studentview');
        ChamiloSession::erase('sid');
        ChamiloSession::erase('_gid');
        ChamiloSession::write('_real_cid', 1);
        ChamiloSession::write('_cid', 'AIACT');
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('_real_cid');
        ChamiloSession::erase('_cid');
        self::getContainer()->get('security.token_storage')->setToken(null);
        parent::tearDown();
    }

    public function testGetLinkInfoReturnsTheRawLinkRow(): void
    {
        $category = $this->category('lt3_cat');
        $link = $this->link('lt3_link', 'https://example.org/lt3', $category);

        $info = Link::getLinkInfo($link);

        // Database::fetch_array() semantics: the named columns, then the same values again under 0..7.
        $this->assertSame(
            ['iid', 'resource_node_id', 'category_id', 'custom_image_id', 'url', 'title', 'description', 'target', 0, 1, 2, 3, 4, 5, 6, 7],
            array_keys($info)
        );
        $this->assertSame('https://example.org/lt3', $info[4]);
        $this->assertSame($category, $info[2]);
        $this->assertSame($link, $info['iid']);
        $this->assertSame($category, $info['category_id'], 'Integer columns stay integers.');
        $this->assertSame('https://example.org/lt3', $info['url']);
        $this->assertSame('lt3_link', $info['title']);
        $this->assertSame('lt3 description', $info['description']);
        $this->assertSame('_blank', $info['target']);
        $this->assertNull($info['resource_node_id']);
        $this->assertNull($info['custom_image_id']);
    }

    public function testGetLinkInfoKeepsTheCustomImageAsRawBinaryId(): void
    {
        $assetId = str_repeat("\x01", 16);
        $this->conn()->executeStatement(
            "INSERT INTO asset (id, title, category, compressed, size, updated_at, created_at)
             VALUES (?, 'lt3_asset', 'link', 0, 1, '2030-01-01 00:00:00', '2030-01-01 00:00:00')",
            [$assetId]
        );
        $link = $this->link('lt3_img', 'https://example.org/img', null);
        $this->conn()->executeStatement('UPDATE c_link SET custom_image_id = ? WHERE iid = ?', [$assetId, $link]);

        $this->assertSame($assetId, Link::getLinkInfo($link)['custom_image_id']);
    }

    public function testGetLinkInfoKeepsNullColumnsAndIsEmptyWithoutARow(): void
    {
        $link = $this->link('lt3_nocat', 'https://example.org/none', null);
        $info = Link::getLinkInfo($link);

        $this->assertNull($info['category_id']);
        $this->assertSame([], Link::getLinkInfo(999999));
        $this->assertSame([], Link::getLinkInfo(0));
        $this->assertSame([], Link::getLinkInfo('not a number'));

        ChamiloSession::erase('_real_cid');
        $this->assertSame([], Link::getLinkInfo($link), 'Without a course context nothing is read.');
    }

    public function testGetCategoryReturnsTheRawCategoryRow(): void
    {
        $category = $this->category('lt3_category');

        $row = Link::getCategory($category);

        $this->assertSame(['iid', 'resource_node_id', 'title', 'description'], array_keys($row));
        $this->assertSame($category, $row['iid']);
        $this->assertSame('lt3_category', $row['title']);
        $this->assertSame('lt3 category description', $row['description']);
        $this->assertNull($row['resource_node_id']);
    }

    public function testGetCategoryIsFalseForAnUnknownIdAndEmptyWithoutIdOrCourse(): void
    {
        $category = $this->category('lt3_other');

        $this->assertFalse(Link::getCategory(999999));
        $this->assertSame([], Link::getCategory(0));

        ChamiloSession::erase('_real_cid');
        $this->assertSame([], Link::getCategory($category));
    }

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function category(string $title): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO c_link_category (title, description) VALUES (?, ?)',
            [$title, 'lt3 category description']
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function link(string $title, string $url, ?int $categoryId): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO c_link (category_id, url, title, description, target) VALUES (?, ?, ?, ?, ?)',
            [$categoryId, $url, $title, 'lt3 description', '_blank']
        );

        return (int) $this->conn()->lastInsertId();
    }
}
