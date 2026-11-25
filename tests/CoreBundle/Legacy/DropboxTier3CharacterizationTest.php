<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Dropbox_Person;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Pins the deletions of Dropbox_Person (public/main/dropbox/dropbox_class.inc.php) that still work: the work and
 * category rows removed for a folder, and the dropbox_person rows removed for a received or sent file.
 *
 * The other methods of that file (Dropbox_Work, Dropbox_SentWork, the loops of the Dropbox_Person constructor) query
 * the removed "id" column of c_dropbox_file and fail today; they are left untouched and cannot be pinned.
 */
final class DropboxTier3CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;

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
        $_GET = [];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        parent::tearDown();
    }

    private function category(int $courseId = 1): int
    {
        $this->conn->insert('c_dropbox_category', [
            'c_id' => $courseId, 'cat_id' => 0, 'title' => 'db3_cat_'.(++$this->seq),
            'received' => 1, 'sent' => 0, 'user_id' => 1, 'session_id' => 0,
        ]);
        $id = (int) $this->conn->lastInsertId();
        $this->conn->update('c_dropbox_category', ['cat_id' => $id], ['iid' => $id]);

        return $id;
    }

    private function file(int $catId = 0, int $courseId = 1): int
    {
        $this->conn->insert('c_dropbox_file', [
            'c_id' => $courseId, 'uploader_id' => 1, 'filename' => 'db3_file_'.(++$this->seq).'.txt', 'filesize' => 1,
            'title' => 'db3_t', 'description' => 'd', 'author' => 'a',
            'upload_date' => '2020-01-01 10:00:00', 'last_upload_date' => '2020-01-01 10:00:00',
            'cat_id' => $catId, 'session_id' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function person(int $fileId, int $userId, int $courseId = 1): void
    {
        $this->conn->insert('c_dropbox_person', ['c_id' => $courseId, 'file_id' => $fileId, 'user_id' => $userId]);
    }

    private function post(int $fileId, int $catId, int $courseId = 1): void
    {
        $this->conn->insert('c_dropbox_post', [
            'c_id' => $courseId, 'file_id' => $fileId, 'dest_user_id' => 1, 'cat_id' => $catId,
            'session_id' => 0, 'feedback_date' => '2020-01-01 10:00:00', 'feedback' => null,
        ]);
    }

    private function rows(string $table, string $where, array $params): int
    {
        return (int) $this->conn->fetchOne("SELECT COUNT(*) FROM $table WHERE $where", $params);
    }

    private function emptyPerson(): Dropbox_Person
    {
        // Built before the fixtures: its constructor loads the works through the classes that cannot run anymore.
        return new Dropbox_Person(1, false, false);
    }

    public function testDeleteReceivedWorkFolderRemovesTheFilesCategoryAndPostsOfThatFolderInThisCourseOnly(): void
    {
        $person = $this->emptyPerson();
        $cat = $this->category();
        $otherCat = $this->category();
        $otherCourseCat = $this->category(2);
        $inFolder = $this->file($cat);
        $this->post($inFolder, $cat);
        $inOtherFolder = $this->file($otherCat);
        $this->post($inOtherFolder, $otherCat);
        $sameCatOtherCourse = $this->file($cat, 2);
        $this->post($sameCatOtherCourse, $cat, 2);
        $this->conn->update('c_dropbox_category', ['cat_id' => $cat], ['iid' => $otherCourseCat]);

        $this->assertTrue($person->deleteReceivedWorkFolder($cat));

        $this->assertSame(0, $this->rows('c_dropbox_file', 'iid = ?', [$inFolder]));
        $this->assertSame(0, $this->rows('c_dropbox_post', 'file_id = ?', [$inFolder]));
        $this->assertSame(0, $this->rows('c_dropbox_category', 'iid = ?', [$cat]));
        $this->assertSame(1, $this->rows('c_dropbox_file', 'iid = ?', [$inOtherFolder]), 'another folder is kept');
        $this->assertSame(1, $this->rows('c_dropbox_post', 'file_id = ?', [$inOtherFolder]));
        $this->assertSame(1, $this->rows('c_dropbox_category', 'iid = ?', [$otherCat]));
        $this->assertSame(1, $this->rows('c_dropbox_file', 'iid = ?', [$sameCatOtherCourse]), 'another course is kept');
        $this->assertSame(1, $this->rows('c_dropbox_post', 'file_id = ?', [$sameCatOtherCourse]));
        $this->assertSame(1, $this->rows('c_dropbox_category', 'iid = ?', [$otherCourseCat]));
    }

    public function testDeleteReceivedWorkRemovesOnlyThisUsersPersonRowOfThatFileInThisCourse(): void
    {
        $person = $this->emptyPerson();
        $shared = $this->file();
        $this->person($shared, 1);
        $this->person($shared, 2);
        $this->person($shared, 1, 2);
        $other = $this->file();
        $this->person($other, 1);

        $person->deleteReceivedWork($shared);

        $this->assertSame(0, $this->rows('c_dropbox_person', 'file_id = ? AND user_id = 1 AND c_id = 1', [$shared]));
        $this->assertSame(1, $this->rows('c_dropbox_person', 'file_id = ? AND user_id = 2', [$shared]), 'the other user keeps it');
        $this->assertSame(1, $this->rows('c_dropbox_person', 'file_id = ? AND user_id = 1 AND c_id = 2', [$shared]), 'other course kept');
        $this->assertSame(1, $this->rows('c_dropbox_person', 'file_id = ?', [$other]), 'another file kept');
        $this->assertSame(1, $this->rows('c_dropbox_file', 'iid = ?', [$shared]), 'still seen by user 2');
    }

    public function testDeleteReceivedWorkOfTheLastOwnerAlsoRemovesTheFile(): void
    {
        $person = $this->emptyPerson();
        $alone = $this->file();
        $this->person($alone, 1);

        $person->deleteReceivedWork($alone);

        $this->assertSame(0, $this->rows('c_dropbox_file', 'iid = ?', [$alone]));
    }

    public function testDeleteSentWorkRemovesOnlyThisUsersPersonRowOfThatFileInThisCourse(): void
    {
        $person = $this->emptyPerson();
        $shared = $this->file();
        $this->person($shared, 1);
        $this->person($shared, 2);
        $this->person($shared, 1, 2);
        $other = $this->file();
        $this->person($other, 1);

        $person->deleteSentWork($shared);

        $this->assertSame(0, $this->rows('c_dropbox_person', 'file_id = ? AND user_id = 1 AND c_id = 1', [$shared]));
        $this->assertSame(1, $this->rows('c_dropbox_person', 'file_id = ? AND user_id = 2', [$shared]));
        $this->assertSame(1, $this->rows('c_dropbox_person', 'file_id = ? AND user_id = 1 AND c_id = 2', [$shared]));
        $this->assertSame(1, $this->rows('c_dropbox_person', 'file_id = ?', [$other]));
        $this->assertSame(1, $this->rows('c_dropbox_file', 'iid = ?', [$shared]));
    }

    public function testDeleteSentWorkOfTheLastOwnerAlsoRemovesTheFile(): void
    {
        $person = $this->emptyPerson();
        $alone = $this->file();
        $this->person($alone, 1);

        $person->deleteSentWork($alone);

        $this->assertSame(0, $this->rows('c_dropbox_file', 'iid = ?', [$alone]));
    }
}
