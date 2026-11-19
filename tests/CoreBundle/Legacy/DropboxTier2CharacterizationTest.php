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
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Pins for dropbox_functions.inc.php that DropboxCharacterizationTest leaves out: the category form, which reads
 * the category it edits (by cat_id, in the current course) to prefill the name and the target.
 *
 * NOT covered, on purpose: zip_download() streams a zip archive and its query joins on a "file.id" column that
 * c_dropbox_file no longer has (it is "iid"), so it is an SQL error today.
 */
final class DropboxTier2CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__.'/../../../public/main/dropbox/dropbox_functions.inc.php';

        require_once __DIR__.'/../../../public/main/dropbox/dropbox_class.inc.php';
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/dropbox/index.php');
        $request->setSession($session);
        Container::setRequest($request);
        $this->conn = $container->get('doctrine')->getConnection();
        ChamiloSession::write('_user', ['id' => 1, 'user_id' => 1]);
        ChamiloSession::write('_real_cid', 1);
        ChamiloSession::write('_cid', 'AIACT');
        ChamiloSession::write('_course', api_get_course_info('AIACT'));
        ChamiloSession::write('sid', 0);
        ChamiloSession::erase('login_as');
        Container::getEntityManager()->clear();
        $_GET = ['view' => 'sent'];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        parent::tearDown();
    }

    public function testAddCategoryFormPrefillsTheNameAndTheTargetOfTheCategoryOfThisCourse(): void
    {
        // The same cat_id exists in another course: only the row of the current course may be used.
        $this->category(1, 7771, 'db2_mine', 1, 0);
        $this->category(2, 7771, 'db2_other', 0, 1);

        ob_start();
        display_addcategory_form('', 7771, 'editcategory');
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('value="db2_mine"', $html);
        $this->assertStringNotContainsString('db2_other', $html);
        $this->assertMatchesRegularExpression('/name="target"[^>]*value="received"|value="received"[^>]*name="target"/', $html);
        $this->assertMatchesRegularExpression('/name="edit_id"[^>]*value="7771"|value="7771"[^>]*name="edit_id"/', $html);
    }

    public function testAddCategoryFormKeepsTheNameTypedAfterAnErrorAndShowsTheSentTarget(): void
    {
        $this->category(1, 7772, 'db2_stored', 0, 1);

        ob_start();
        display_addcategory_form('db2_typed', 7772, 'editcategory');
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('value="db2_typed"', $html);
        $this->assertStringNotContainsString('db2_stored', $html);
        $this->assertMatchesRegularExpression('/name="target"[^>]*value="sent"|value="sent"[^>]*name="target"/', $html);
    }

    public function testStoreMoveSentMovesOnlyTheGivenFileOfTheUploader(): void
    {
        $moved = $this->file(1);
        $sibling = $this->file(1);

        store_move($moved, 7, 'sent');

        $cat = fn (int $id): int => (int) $this->conn->fetchOne('SELECT cat_id FROM c_dropbox_file WHERE iid = ?', [$id]);
        $this->assertSame(7, $cat($moved));
        $this->assertSame(0, $cat($sibling), 'another file of the same uploader stays where it is');
    }

    private function file(int $uploader): int
    {
        $this->conn->executeStatement(
            "INSERT INTO c_dropbox_file (c_id, uploader_id, filename, filesize, title, description, author, upload_date, last_upload_date, cat_id, session_id)
             VALUES (1, ?, ?, 10, 'db2_title', 'd', 'a', '2020-01-01 10:00:00', '2020-01-01 10:00:00', 0, 0)",
            [$uploader, uniqid('db2_', true).'.txt']
        );

        return (int) $this->conn->lastInsertId();
    }

    private function category(int $courseId, int $catId, string $title, int $received, int $sent): void
    {
        $this->conn->executeStatement(
            'INSERT INTO c_dropbox_category (c_id, cat_id, title, received, sent, user_id, session_id) VALUES (?, ?, ?, ?, ?, 1, 0)',
            [$courseId, $catId, $title, $received, $sent]
        );
    }
}
