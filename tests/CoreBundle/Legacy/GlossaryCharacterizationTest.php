<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use GlossaryManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\Uuid;

/**
 * Characterization tests for GlossaryManager (glossary.lib.php).
 *
 * The glossary tool itself is Vue + API Platform now. The legacy page public/main/glossary/index.php, its AJAX
 * helper and the library survive; the library reads and writes through the CGlossary repository (resource nodes
 * and links), only get_glossary_term_by_glossary_name() and move_glossary() still carry raw SQL.
 *
 * LIVE: get_glossary_terms, get_glossary_term_by_glossary_id, get_glossary_term_by_glossary_name, save_glossary,
 * update_glossary, glossary_exists, delete_glossary, display_glossary helpers (get_number_glossary_terms,
 * get_glossary_data), exportToFormat/movePdfToDocuments (output side effects, not covered).
 * DEAD: move_glossary (its only callers are commented out), get_max_glossary_item (only used by save_glossary),
 * reorder_glossary (only used by delete_glossary).
 *
 * Fixtures: resource_node + c_glossary + resource_link rows are raw INSERTs on course 1 (no repository creation,
 * which deadlocks under concurrent suites); titles start with "gl_". The user is anonymous (no token), so the
 * visibility rule that applies is the student one: only PUBLISHED links are listed.
 */
final class GlossaryCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private int $seq = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/glossary/index.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        \ChamiloSession::erase('studentview');
        \ChamiloSession::erase('glossary_view');
        \ChamiloSession::write('_real_cid', 1);
        \ChamiloSession::write('_cid', 'AIACT');
        \ChamiloSession::erase('id_session');
        unset($_GET['keyword'], $_GET['action']);
    }

    protected function tearDown(): void
    {
        \ChamiloSession::erase('_real_cid');
        \ChamiloSession::erase('_cid');
        unset($_GET['keyword'], $_GET['action']);
        self::getContainer()->get('security.token_storage')->setToken(null);
        parent::tearDown();
    }

    // ------------------------------------------------------------------ listing

    public function testGetGlossaryTermsListsPublishedTermsOfTheCourseWithIdTitleDescription(): void
    {
        $a = $this->term('gl_alpha', '<p>first</p>');
        $b = $this->term('gl_beta', 'second');
        $terms = $this->mine(GlossaryManager::get_glossary_terms());

        $this->assertSame(
            [
                ['id' => $a, 'title' => 'gl_alpha', 'description' => '<p>first</p>'],
                ['id' => $b, 'title' => 'gl_beta', 'description' => 'second'],
            ],
            array_values($terms)
        );
    }

    public function testStudentsDoNotSeeDraftTermsNorTermsOfOtherCourses(): void
    {
        $published = $this->term('gl_pub', 'x');
        $draft = $this->term('gl_draft', 'x', 1, 0);
        $other = $this->term('gl_other_course', 'x', 2);

        $titles = array_column($this->mineRows(GlossaryManager::get_glossary_data(0, 100, 0, 'ASC')), 0);

        $this->assertSame(['gl_pub'], $titles, 'Neither the draft nor the other course term is listed.');
        $this->assertGreaterThan(0, $published + $draft + $other);
    }

    public function testStaffSeeDraftTermsToo(): void
    {
        $draft = $this->term('gl_draft', 'x', 1, 0);
        $this->loginAsAdmin();

        $rows = $this->mineRows(GlossaryManager::get_glossary_data(0, 100, 0, 'ASC'));

        $this->assertCount(1, $rows);
        $this->assertSame($draft, $rows[0][2]);
    }

    public function testGetNumberGlossaryTermsCountsTheVisibleTermsOfTheCourse(): void
    {
        $before = (int) GlossaryManager::get_number_glossary_terms();
        $this->term('gl_one', 'x');
        $this->term('gl_two', 'x');
        $this->term('gl_hidden', 'x', 1, 0);
        $this->term('gl_elsewhere', 'x', 2);

        $this->assertSame($before + 2, (int) GlossaryManager::get_number_glossary_terms());
    }

    public function testGetGlossaryDataReturnsTitleAndDescriptionRowsInTableViewFormat(): void
    {
        $this->term('gl_row', '<p>one</p><p>two</p>');

        $rows = $this->mineRows(GlossaryManager::get_glossary_data(0, 100, 0, 'ASC'));

        $this->assertCount(1, $rows);
        $this->assertStringStartsWith('gl_row', $rows[0][0]);
        // Table view: <p> removed, </p> becomes <br />.
        $this->assertSame('one<br />two<br />', $rows[0][1]);
        $this->assertArrayNotHasKey(2, $rows[0], 'The id column is for editors only.');
    }

    public function testGetGlossaryDataKeywordSearchesTitleAndDescription(): void
    {
        $this->term('gl_apple', 'a fruit');
        $this->term('gl_pear', 'another fruit with apple notes');
        $this->term('gl_car', 'vehicle');
        $this->term('gl_elsewhere', 'apple', 2);

        $_GET['keyword'] = 'apple';
        $titles = array_map(static fn ($r) => substr((string) $r[0], 0, 8), $this->mineRows(GlossaryManager::get_glossary_data(0, 100, 0, 'ASC')));
        $this->assertEqualsCanonicalizing(['gl_apple', 'gl_pear'], $titles, 'Title or description, the term of another course is out.');

        $_GET['keyword'] = 'gl_ap';
        $this->assertCount(1, $this->mineRows(GlossaryManager::get_glossary_data(0, 100, 0, 'ASC')), 'A partial title matches.');

        $_GET['keyword'] = '%';
        $this->assertSame([], $this->mineRows(GlossaryManager::get_glossary_data(0, 100, 0, 'ASC')), 'LIKE wildcards are literal.');

        $_GET['keyword'] = "x' OR '1'='1";
        $this->assertSame([], $this->mineRows(GlossaryManager::get_glossary_data(0, 100, 0, 'ASC')));
    }

    public function testGetGlossaryDataShowsTheIdColumnToEditors(): void
    {
        $id = $this->term('gl_edit', 'x');
        $this->loginAsAdmin();

        $rows = $this->mineRows(GlossaryManager::get_glossary_data(0, 100, 0, 'ASC'));

        $this->assertSame($id, $rows[0][2]);
    }

    public function testGetGlossaryDataIgnoresFromLimitAndDirection(): void
    {
        $this->term('gl_a', 'x');
        $this->term('gl_b', 'x');
        $this->term('gl_c', 'x');

        // PINNED QUIRK: pagination and sort arguments are ignored (@todo in the source); the legacy SQL honoured
        // LIMIT/ORDER BY. All the visible terms come back, in insertion order, whatever $from/$number/$direction.
        $all = $this->mineRows(GlossaryManager::get_glossary_data(0, 1000, 0, 'ASC'));
        $page = $this->mineRows(GlossaryManager::get_glossary_data(2, 1, 0, 'DESC'));

        $this->assertCount(3, $all);
        $this->assertSame($all, $page);
    }

    // ------------------------------------------------------------------ single term

    public function testGetGlossaryTermByIdReturnsTheDescriptionOrEmptyString(): void
    {
        $id = $this->term('gl_desc', '<b>def</b>');

        $this->assertSame('<b>def</b>', GlossaryManager::get_glossary_term_by_glossary_id($id));
        $this->assertSame('', GlossaryManager::get_glossary_term_by_glossary_id(999999));
    }

    public function testGetGlossaryTermByNameFindsTheTermOfTheCourse(): void
    {
        $id = $this->term('gl_byname', 'the definition');
        $this->term('gl_byname_longer', 'other');

        // Case-insensitive (the column collation was), trimmed, whole title.
        $row = GlossaryManager::get_glossary_term_by_glossary_name('GL_BYNAME ');

        $this->assertSame('gl_byname', $row['title']);
        $this->assertSame('the definition', $row['description']);
        $this->assertSame($id, (int) $row['glossary_id']);
    }

    public function testGetGlossaryTermByNameDoesNotLeakAnotherCourseOrAnUnknownTerm(): void
    {
        $this->term('gl_elsewhere', 'x', 2);

        $this->assertSame([], GlossaryManager::get_glossary_term_by_glossary_name('gl_elsewhere'));
        $this->assertSame([], GlossaryManager::get_glossary_term_by_glossary_name('gl_does_not_exist'));
        $this->assertSame([], GlossaryManager::get_glossary_term_by_glossary_name("x' OR '1'='1"));
    }

    public function testGlossaryExistsComparesTitlesWithinTheCourseAndCanExcludeOneId(): void
    {
        $id = $this->term('gl_dup', 'x');
        $this->term('gl_elsewhere', 'x', 2);

        $this->assertTrue(GlossaryManager::glossary_exists('gl_dup'));
        $this->assertFalse(GlossaryManager::glossary_exists('gl_dup', $id), 'Editing a term must not clash with itself.');
        $this->assertFalse(GlossaryManager::glossary_exists('gl_elsewhere'));
        // PINNED QUIRK: the comparison is a loose PHP "==" on the whole title, case sensitive.
        $this->assertFalse(GlossaryManager::glossary_exists('GL_DUP'));
    }

    // ------------------------------------------------------------------ writes

    public function testSaveGlossaryRefusesAnExistingTitleAndInvalidInput(): void
    {
        $this->term('gl_taken', 'x');
        $before = (int) GlossaryManager::get_number_glossary_terms();

        $this->assertFalse(GlossaryManager::save_glossary(['title' => 'gl_taken', 'description' => 'y'], false));
        $this->assertFalse(GlossaryManager::save_glossary('nope', false));
        $this->assertFalse(GlossaryManager::save_glossary(['description' => 'y'], false));

        $this->assertSame($before, (int) GlossaryManager::get_number_glossary_terms());
    }

    public function testUpdateGlossaryChangesTitleAndDescription(): void
    {
        $id = $this->term('gl_old', 'old');

        $this->assertTrue(GlossaryManager::update_glossary(
            ['glossary_id' => $id, 'title' => 'gl_new', 'description' => 'new'],
            false
        ));
        $this->fresh();

        $row = $this->conn()->fetchAssociative('SELECT title, description FROM c_glossary WHERE iid = ?', [$id]);
        $this->assertSame(['title' => 'gl_new', 'description' => 'new'], $row);
    }

    public function testUpdateGlossaryAcceptsKeepingItsOwnTitle(): void
    {
        $id = $this->term('gl_same', 'old');

        $this->assertTrue(GlossaryManager::update_glossary(['glossary_id' => $id, 'title' => 'gl_same', 'description' => 'new'], false));
        $this->fresh();

        $this->assertSame('new', $this->conn()->fetchOne('SELECT description FROM c_glossary WHERE iid = ?', [$id]));
    }

    public function testUpdateGlossaryRefusesATitleUsedByAnotherTerm(): void
    {
        $a = $this->term('gl_a', 'a');
        $this->term('gl_b', 'b');

        $this->assertFalse(GlossaryManager::update_glossary(['glossary_id' => $a, 'title' => 'gl_b', 'description' => 'z'], false));
        $this->fresh();

        $this->assertSame('a', $this->conn()->fetchOne('SELECT description FROM c_glossary WHERE iid = ?', [$a]));
    }

    public function testUpdateGlossaryOnAnUnknownIdSucceedsWithoutChangingAnything(): void
    {
        // PINNED QUIRK: no term to update, yet true is returned.
        $this->assertTrue(GlossaryManager::update_glossary(['glossary_id' => 999999, 'title' => 'gl_nobody', 'description' => 'z'], false));
        $this->assertSame(0, (int) $this->conn()->fetchOne("SELECT COUNT(*) FROM c_glossary WHERE title = 'gl_nobody'"));
    }

    public function testSaveGlossaryCreatesTheTermInTheCourse(): void
    {
        $this->loginAsAdmin();
        $glossary = GlossaryManager::save_glossary(['title' => 'gl_new_term', 'description' => 'd'], false);
        $this->fresh();

        $this->assertNotFalse($glossary);
        $row = $this->conn()->fetchAssociative('SELECT title, description FROM c_glossary WHERE iid = ?', [$glossary->getIid()]);
        $this->assertSame(['title' => 'gl_new_term', 'description' => 'd'], $row);
        $this->assertSame(
            1,
            (int) $this->conn()->fetchOne(
                'SELECT COUNT(*) FROM resource_link l INNER JOIN c_glossary g ON g.resource_node_id = l.resource_node_id
                 WHERE g.iid = ? AND l.c_id = 1 AND l.session_id IS NULL',
                [$glossary->getIid()]
            ),
            'The term is linked to the current course.'
        );
        $titles = array_column($this->mineRows(GlossaryManager::get_glossary_data(0, 100, 0, 'ASC')), 0);
        $this->assertSame(['gl_new_term'], $titles);
        $this->assertFalse(GlossaryManager::save_glossary(['title' => 'gl_new_term', 'description' => 'again'], false));
    }

    public function testDeleteGlossaryRemovesTheTerm(): void
    {
        $id = $this->term('gl_gone', 'x');
        $other = $this->term('gl_kept', 'x');

        // reorder_glossary() iterates a QueryBuilder (always empty) so it never reaches setDisplayOrder().
        $this->assertTrue(GlossaryManager::delete_glossary($id, false));
        $this->fresh();

        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_glossary WHERE iid = ?', [$id]));
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_glossary WHERE iid = ?', [$other]));
    }

    public function testDeleteGlossaryOfAnUnknownIdReturnsTrueAndTouchesNothing(): void
    {
        $kept = $this->term('gl_stays', 'x');

        $this->assertTrue(GlossaryManager::delete_glossary(999999, false));
        $this->fresh();

        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_glossary WHERE iid = ?', [$kept]));
    }

    // ------------------------------------------------------------------ course scoping (Rule 13)

    public function testAPageOfCourseOneCannotDeleteATermOfAnotherCourse(): void
    {
        $foreign = $this->term('gl_course_two', 'secret', 2);
        $this->loginAsAdmin();

        GlossaryManager::delete_glossary($foreign, false);
        $this->fresh();

        $this->assertSame(
            1,
            (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM c_glossary WHERE iid = ?', [$foreign]),
            'The glossary_id comes from the request: it must belong to the current course.'
        );
    }

    public function testAPageOfCourseOneCannotUpdateATermOfAnotherCourse(): void
    {
        $foreign = $this->term('gl_course_two', 'original', 2);
        $this->loginAsAdmin();

        GlossaryManager::update_glossary(['glossary_id' => $foreign, 'title' => 'gl_hijacked', 'description' => 'changed'], false);
        $this->fresh();

        $this->assertSame(
            ['title' => 'gl_course_two', 'description' => 'original'],
            $this->conn()->fetchAssociative('SELECT title, description FROM c_glossary WHERE iid = ?', [$foreign])
        );
    }

    public function testTheAjaxDescriptionLookupDoesNotReadAnotherCoursesTerm(): void
    {
        $foreign = $this->term('gl_course_two', 'private definition', 2);
        $mine = $this->term('gl_course_one', 'public definition', 1);
        $draft = $this->term('gl_draft', 'not yet', 1, 0);

        $this->assertSame('public definition', GlossaryManager::get_glossary_term_by_glossary_id($mine));
        $this->assertSame('', GlossaryManager::get_glossary_term_by_glossary_id($foreign));
        $this->assertSame('', GlossaryManager::get_glossary_term_by_glossary_id($draft), 'A student does not read a draft.');
    }

    // ------------------------------------------------------------------ helpers

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function fresh(): void
    {
        $this->getEntityManager()->clear();
    }

    private function loginAsAdmin(): void
    {
        $admin = $this->getUser('admin');
        $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
            $admin,
            'main',
            $admin->getRoles()
        );
        self::getContainer()->get('security.token_storage')->setToken($token);
    }

    /** Raw fixture: resource node + c_glossary + resource link on a course. Returns the c_glossary iid. */
    private function term(string $title, string $description, int $courseId = 1, int $visibility = 2): int
    {
        $this->seq++;
        $this->conn()->executeStatement(
            "INSERT INTO resource_node (resource_type_id, creator_id, parent_id, title, slug, level, path, created_at, updated_at, public, uuid)
             VALUES (33, 1, ?, ?, ?, 1, ?, '2030-01-01 00:00:00', '2030-01-01 00:00:00', 0, ?)",
            [$this->conn()->fetchOne('SELECT resource_node_id FROM course WHERE id = ?', [$courseId]), $title, 'gl-'.$this->seq.'-'.uniqid(), '/gl'.$this->seq.'-'.uniqid(), Uuid::v4()->toBinary()]
        );
        $nodeId = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement(
            'INSERT INTO c_glossary (resource_node_id, title, description) VALUES (?, ?, ?)',
            [$nodeId, $title, $description]
        );
        $iid = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement(
            "INSERT INTO resource_link (resource_node_id, c_id, visibility, display_order, resource_type_group, created_at, updated_at)
             VALUES (?, ?, ?, 0, 0, '2030-01-01 00:00:00', '2030-01-01 00:00:00')",
            [$nodeId, $courseId, $visibility]
        );

        return $iid;
    }

    /** Keep only the fixture terms: the demo course may already hold some. */
    private function mine(array $terms): array
    {
        return array_filter($terms, static fn ($t) => str_starts_with($t['title'], 'gl_'));
    }

    private function mineRows(array $rows): array
    {
        return array_values(array_filter($rows, static fn ($r) => str_starts_with((string) $r[0], 'gl_')));
    }
}
