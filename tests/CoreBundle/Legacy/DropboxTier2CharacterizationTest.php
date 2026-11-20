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
 * zip_download() streams a zip archive and exits, so only its file selection (dropbox_files_to_zip()) is tested.
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

    public function testZipSelectionKeepsOnlyTheSelectedFilesTheUserSentOrReceived(): void
    {
        // Current user is id 1.
        $sent = $this->file(1);
        $received = $this->file(2);
        $unrelated = $this->file(2);
        $notSelected = $this->file(1);
        $this->person($sent, 1);
        $this->post($sent, 2);
        $this->person($received, 2);
        $this->post($received, 1);
        $this->person($unrelated, 2);
        $this->post($unrelated, 3);
        $this->person($notSelected, 1);
        $this->post($notSelected, 2);

        $files = dropbox_files_to_zip([$sent, $received, $unrelated]);

        $names = array_map(fn (array $f): string => $f['filename'], array_values($files));
        $expected = array_map(
            fn (int $id): string => (string) $this->conn->fetchOne('SELECT filename FROM c_dropbox_file WHERE iid = ?', [$id]),
            [$sent, $received]
        );
        sort($names);
        sort($expected);
        $this->assertSame($expected, $names, 'only the selected files this user sent or received are zipped');
        $one = $files[$expected[0]];
        $this->assertSame(['filename', 'title', 'author', 'description'], array_keys($one));
        $this->assertSame('db2_title', $one['title']);
    }

    public function testZipSelectionIgnoresFilesOfAnotherCourse(): void
    {
        $file = $this->file(1);
        $this->conn->executeStatement('UPDATE c_dropbox_file SET c_id = 2 WHERE iid = ?', [$file]);
        $this->person($file, 1);
        $this->post($file, 2);

        $this->assertSame([], dropbox_files_to_zip([$file]));
    }

    private function person(int $fileId, int $userId): void
    {
        $this->conn->executeStatement('INSERT INTO c_dropbox_person (c_id, file_id, user_id) VALUES (1, ?, ?)', [$fileId, $userId]);
    }

    private function post(int $fileId, int $destUserId): void
    {
        $this->conn->executeStatement(
            "INSERT INTO c_dropbox_post (c_id, file_id, dest_user_id, feedback_date, feedback, cat_id, session_id) VALUES (1, ?, ?, '2020-01-01 10:00:00', '', 0, 0)",
            [$fileId, $destUserId]
        );
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
