<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Category;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use ExerciseLink;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Characterization of AbstractLink::add() (duplicate guard), delete() and lock() of
 * public/main/gradebook/lib/be/abstractlink.class.php, through ExerciseLink on the demo quiz (c_quiz iid 1,
 * type 1) of course 1.
 */
final class GradebookAbstractLinkTier3CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/gradebook/index.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        $this->conn = $container->get('doctrine')->getConnection();
        $_GET = [];
        $_POST = [];
        ChamiloSession::write('_user', ['id' => 1, 'user_id' => 1]);
        ChamiloSession::write('_real_cid', 1);
        ChamiloSession::write('_cid', 'AIACT');
        ChamiloSession::write('_course', api_get_course_info('AIACT'));
        ChamiloSession::write('sid', 0);
        ChamiloSession::write('gid', 0);
        ChamiloSession::erase('studentview');
        Container::getEntityManager()->clear();
        $user = $this->getUser('admin');
        $container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, 'main', $user->getRoles())
        );
    }

    protected function tearDown(): void
    {
        foreach (['_user', 'sid', '_real_cid', '_cid', '_course'] as $key) {
            ChamiloSession::erase($key);
        }
        self::getContainer()->get('security.token_storage')->setToken(null);
        parent::tearDown();
    }

    private function category(): int
    {
        $this->conn->executeStatement(
            "INSERT INTO gradebook_category (c_id, session_id, user_id, title, weight, visible, locked, generate_certificates,
                is_requirement, calculation_mode)
             VALUES (1, NULL, 1, ?, 100, 1, 0, 0, 0, 'weighted_average')",
            ['al_cat_'.(++$this->seq)]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function rawLink(int $categoryId): int
    {
        $node = (int) $this->conn->fetchOne('SELECT resource_node_id FROM c_quiz WHERE iid = 1');
        $this->conn->executeStatement(
            "INSERT INTO gradebook_link (ref_id, c_id, category_id, type, created_at, weight, visible, locked)
             VALUES (?, 1, ?, 1, '2001-01-01 00:00:00', 5, 1, 0)",
            [$node, $categoryId]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function exerciseLink(int $categoryId): ExerciseLink
    {
        $link = new ExerciseLink();
        $link->set_type(1);
        $link->set_ref_id(1);
        $link->setCourseId(1);
        $link->setCategory(Category::load($categoryId)[0]);
        $link->set_category_id($categoryId);
        $link->set_weight(3.0);
        $link->set_visible(1);

        return $link;
    }

    private function rows(string $where, array $params = []): int
    {
        return (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_link WHERE '.$where, $params);
    }

    public function testAddInsertsTheLinkOncePerCategoryAndRefusesADuplicate(): void
    {
        $catA = $this->category();
        $catB = $this->category();

        $first = $this->exerciseLink($catA)->add();
        $duplicate = $this->exerciseLink($catA)->add();
        $otherCategory = $this->exerciseLink($catB)->add();

        $this->assertGreaterThan(0, $first);
        $this->assertSame(0, $duplicate, 'The same quiz in the same category of the same course is linked once.');
        $this->assertGreaterThan(0, $otherCategory);
        $this->assertSame(1, $this->rows('category_id = ?', [$catA]));
        $this->assertSame(1, $this->rows('category_id = ?', [$catB]));
    }

    public function testAddIsNotBlockedByTheSameQuizLinkedInAnotherCourse(): void
    {
        $cat = $this->category();
        $node = (int) $this->conn->fetchOne('SELECT resource_node_id FROM c_quiz WHERE iid = 1');
        $this->conn->executeStatement(
            "INSERT INTO gradebook_link (ref_id, c_id, category_id, type, created_at, weight, visible, locked)
             VALUES (?, 2, ?, 1, '2001-01-01 00:00:00', 5, 1, 0)",
            [$node, $cat]
        );

        $this->assertGreaterThan(0, $this->exerciseLink($cat)->add());
        $this->assertSame(2, $this->rows('category_id = ?', [$cat]));
    }

    public function testAddIsNotBlockedByTheSameQuizLinkedWithAnotherType(): void
    {
        $cat = $this->category();
        $node = (int) $this->conn->fetchOne('SELECT resource_node_id FROM c_quiz WHERE iid = 1');
        $this->conn->executeStatement(
            "INSERT INTO gradebook_link (ref_id, c_id, category_id, type, created_at, weight, visible, locked)
             VALUES (?, 1, ?, 2, '2001-01-01 00:00:00', 5, 1, 0)",
            [$node, $cat]
        );

        $this->assertGreaterThan(0, $this->exerciseLink($cat)->add());
    }

    public function testDeleteRemovesOnlyThatLink(): void
    {
        $cat = $this->category();
        $mine = $this->rawLink($cat);
        $other = $this->rawLink($cat);
        $link = $this->exerciseLink($cat);
        $link->set_id($mine);

        $link->delete();

        $this->assertSame(0, $this->rows('id = ?', [$mine]));
        $this->assertSame(1, $this->rows('id = ?', [$other]));
    }

    public function testLockSetsAndClearsTheLockedFlagOfThatLinkOnly(): void
    {
        $cat = $this->category();
        $mine = $this->rawLink($cat);
        $other = $this->rawLink($cat);
        $link = $this->exerciseLink($cat);
        $link->set_id($mine);

        $link->lock(1);
        $this->assertSame(1, $this->rows('id = ? AND locked = 1', [$mine]));
        $this->assertSame(0, $this->rows('id = ? AND locked = 1', [$other]));

        $link->lock(0);
        $this->assertSame(0, $this->rows('id = ? AND locked = 1', [$mine]));
    }
}
