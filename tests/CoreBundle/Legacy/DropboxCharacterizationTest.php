<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Exception\NotAllowedException;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization of the legacy dropbox helpers (public/main/dropbox/dropbox_functions.inc.php).
 *
 * The dropbox tool itself now runs on Vue + API Platform (CDropboxFile resources); these helpers are only
 * reachable through public/main/dropbox/*.php typed by URL, see the decisions file. They pin the rows read and
 * written in c_dropbox_category/file/post/person/feedback so a rewrite to Doctrine keeps the same behaviour.
 * Fixtures are raw prepared inserts into course 1 (and 2 for the isolation checks); tags start with "db_".
 */
final class DropboxCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const MAILING_ID_BASE = 10000000;

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
        $this->setContext(1, 1);
        $_GET = [];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function setContext(int $userId, int $courseId, int $sessionId = 0): void
    {
        \ChamiloSession::write('_user', ['id' => $userId, 'user_id' => $userId]);
        \ChamiloSession::write('_real_cid', $courseId);
        \ChamiloSession::write('_cid', 1 === $courseId ? 'AIACT' : 'USINGCHAMILO');
        \ChamiloSession::write('_course', api_get_course_info(1 === $courseId ? 'AIACT' : 'USINGCHAMILO'));
        \ChamiloSession::write('sid', $sessionId);
        \ChamiloSession::erase('login_as');
        Container::getEntityManager()->clear();
    }

    /**
     * Inserts a category and sets cat_id to its iid, as store_addcategory() does.
     */
    private function category(int $userId, string $kind, int $courseId = 1, int $sessionId = 0, ?string $title = null): int
    {
        $this->conn->insert('c_dropbox_category', [
            'c_id' => $courseId,
            'cat_id' => 0,
            'title' => $title ?? 'db_cat_'.(++$this->seq),
            'received' => 'received' === $kind ? 1 : 0,
            'sent' => 'sent' === $kind ? 1 : 0,
            'user_id' => $userId,
            'session_id' => $sessionId,
        ]);
        $id = (int) $this->conn->lastInsertId();
        $this->conn->update('c_dropbox_category', ['cat_id' => $id], ['iid' => $id]);

        return $id;
    }

    private function file(int $uploader, int $catId = 0, int $courseId = 1, string $filename = ''): int
    {
        $n = ++$this->seq;
        $this->conn->insert('c_dropbox_file', [
            'c_id' => $courseId,
            'uploader_id' => $uploader,
            'filename' => '' !== $filename ? $filename : 'db_file_'.$n.'.txt',
            'filesize' => 10,
            'title' => 'db_title_'.$n,
            'description' => 'db_desc',
            'author' => 'db_author',
            'upload_date' => '2020-01-01 10:00:00',
            'last_upload_date' => '2020-01-01 10:00:00',
            'cat_id' => $catId,
            'session_id' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function person(int $fileId, int $userId, int $courseId = 1): void
    {
        $this->conn->insert('c_dropbox_person', ['c_id' => $courseId, 'file_id' => $fileId, 'user_id' => $userId]);
    }

    private function post(int $fileId, int $destUser, int $catId = 0, int $courseId = 1): void
    {
        $this->conn->insert('c_dropbox_post', [
            'c_id' => $courseId,
            'file_id' => $fileId,
            'dest_user_id' => $destUser,
            'cat_id' => $catId,
            'session_id' => 0,
            'feedback_date' => '2020-01-01 10:00:00',
            'feedback' => null,
        ]);
    }

    private function feedbackRow(int $fileId, int $courseId = 1): void
    {
        $this->conn->insert('c_dropbox_feedback', [
            'c_id' => $courseId,
            'feedback_id' => 0,
            'file_id' => $fileId,
            'author_user_id' => 1,
            'feedback' => 'db_fb',
            'feedback_date' => '2020-01-01 10:00:00',
        ]);
    }

    /**
     * Runs the callable and returns the first PHP warning it raised ('' if none), so a pinned warning does not
     * depend on PHPUnit's deprecated expectWarning().
     */
    private function warningRaisedBy(callable $call): string
    {
        $message = '';
        set_error_handler(static function (int $no, string $str) use (&$message): bool {
            $message = '' === $message ? $str : $message;

            return true;
        }, E_WARNING);
        try {
            $call();
        } finally {
            restore_error_handler();
        }

        return $message;
    }

    private function rows(string $table, string $where, array $params = []): int
    {
        return (int) $this->conn->fetchOne("SELECT COUNT(*) FROM $table WHERE $where", $params);
    }

    // ---------------------------------------------------------------- categories: reading

    public function testGetCategoriesReturnsOnlyTheCurrentUsersOfTheCurrentCourseKeyedByCatId(): void
    {
        $mine = $this->category(1, 'sent');
        $this->category(2, 'sent');
        $this->category(1, 'sent', 2);

        $list = get_dropbox_categories();

        $this->assertArrayHasKey($mine, $list);
        $this->assertSame([$mine], array_keys($list), 'only my own category of this course is listed');
        $this->assertSame('db_cat_1', $list[$mine]['title']);
    }

    public function testGetCategoriesFilterKeepsSentOrReceivedOnly(): void
    {
        $sent = $this->category(1, 'sent');
        $received = $this->category(1, 'received');

        $this->assertSame([$sent], array_keys(get_dropbox_categories('sent')));
        $this->assertSame([$received], array_keys(get_dropbox_categories('received')));
        $this->assertEqualsCanonicalizing([$sent, $received], array_keys(get_dropbox_categories()));
        // PINNED QUIRK: an unknown filter value matches nothing instead of falling back to "all".
        $this->assertSame([], get_dropbox_categories('whatever'));
    }

    public function testGetCategoriesInASessionHidesTheBaseCourseOnes(): void
    {
        $base = $this->category(1, 'sent');
        $inSession = $this->category(1, 'sent', 1, 7);

        // PINNED QUIRK: a session sees only its own categories (api_get_session_condition without base content).
        $this->setContext(1, 1, 7);
        $this->assertSame([$inSession], array_keys(get_dropbox_categories()));

        $this->setContext(1, 1, 0);
        $this->assertSame([$base], array_keys(get_dropbox_categories()));
    }

    public function testGetCategoryById(): void
    {
        $id = $this->category(2, 'received', 1, 0, 'db_other_user_cat');

        $row = get_dropbox_category($id);

        // The single-category lookup does not scope by user, only by course.
        $this->assertSame('db_other_user_cat', $row['title']);
        $this->assertSame(2, (int) $row['user_id']);
        $this->assertSame([], get_dropbox_category(0));
        $this->assertSame([], get_dropbox_category('abc'));

        $this->setContext(1, 2);
        $this->assertEmpty(get_dropbox_category($id), 'a category of another course is not reachable');
    }

    public function testGetCategoryOfUnknownIdReturnsFalseNotAnEmptyArray(): void
    {
        // PINNED QUIRK: fetch_assoc() on an empty result is returned as is, so the "not found" value is false for
        // an unknown id but [] for an empty id; callers only test count()/empty().
        $this->assertFalse(get_dropbox_category(999999999));
    }

    // ---------------------------------------------------------------- categories: writing

    public function testStoreAddCategoryCreatesRowWithCatIdEqualToIid(): void
    {
        $_POST = ['target' => 'sent', 'category_name' => 'db_new'];

        $result = store_addcategory();

        $this->assertSame('confirmation', $result['type']);
        $row = $this->conn->fetchAssociative("SELECT * FROM c_dropbox_category WHERE title = 'db_new'");
        $this->assertSame((int) $row['iid'], (int) $row['cat_id']);
        $this->assertSame(1, (int) $row['sent']);
        $this->assertSame(0, (int) $row['received']);
        $this->assertSame(1, (int) $row['user_id']);
        $this->assertSame(1, (int) $row['c_id']);
        $this->assertSame(0, (int) $row['session_id']);
    }

    public function testStoreAddCategoryReceivedTarget(): void
    {
        $_POST = ['target' => 'received', 'category_name' => 'db_new_r'];

        store_addcategory();

        $row = $this->conn->fetchAssociative("SELECT sent, received FROM c_dropbox_category WHERE title = 'db_new_r'");
        $this->assertSame([0, 1], [(int) $row['sent'], (int) $row['received']]);
    }

    public function testStoreAddCategoryRejectsDuplicateOnlyForTheSameUserKindAndSession(): void
    {
        $_POST = ['target' => 'sent', 'category_name' => 'db_dup'];
        store_addcategory();

        $again = store_addcategory();
        $this->assertSame('error', $again['type']);
        $this->assertSame(1, $this->rows('c_dropbox_category', "title = 'db_dup'"));

        // The same name in the other part (received) is a different row.
        $_POST['target'] = 'received';
        $this->assertSame('confirmation', store_addcategory()['type']);
        // And in a session too.
        $_POST['target'] = 'sent';
        $this->setContext(1, 1, 5);
        $this->assertSame('confirmation', store_addcategory()['type']);
        $this->assertSame(3, $this->rows('c_dropbox_category', "title = 'db_dup'"));
    }

    public function testStoreAddCategoryValidatesTargetAndName(): void
    {
        $_POST = ['target' => 'nowhere', 'category_name' => 'db_x'];
        // PINNED QUIRK: an invalid target returns a bare string while the other outcomes return arrays.
        $this->assertSame(get_lang('Error'), store_addcategory());

        $_POST = ['target' => 'sent', 'category_name' => ''];
        $result = store_addcategory();
        $this->assertSame('error', $result['type']);
        $this->assertSame(0, $this->rows('c_dropbox_category', "title = 'db_x' OR title = ''"));
    }

    public function testStoreAddCategoryEditChangesOnlyTheOwnCategoryOfTheCourse(): void
    {
        $mine = $this->category(1, 'sent', 1, 0, 'db_before');
        $foreignUser = $this->category(2, 'sent', 1, 0, 'db_before_other');
        $foreignCourse = $this->category(1, 'sent', 2, 0, 'db_before_c2');

        foreach ([$foreignUser, $foreignCourse, $mine] as $id) {
            $_POST = ['target' => 'received', 'category_name' => 'db_after', 'edit_id' => $id];
            $this->assertSame('confirmation', store_addcategory()['type']);
        }

        $this->assertSame('db_after', $this->conn->fetchOne('SELECT title FROM c_dropbox_category WHERE iid = ?', [$mine]));
        $this->assertSame('db_before_other', $this->conn->fetchOne('SELECT title FROM c_dropbox_category WHERE iid = ?', [$foreignUser]));
        $this->assertSame('db_before_c2', $this->conn->fetchOne('SELECT title FROM c_dropbox_category WHERE iid = ?', [$foreignCourse]));
        // The edit also switches the kind from sent to received.
        $row = $this->conn->fetchAssociative('SELECT sent, received FROM c_dropbox_category WHERE iid = ?', [$mine]);
        $this->assertSame([0, 1], [(int) $row['sent'], (int) $row['received']]);
    }

    // ---------------------------------------------------------------- delete_category

    public function testDeleteCategoryRefusesAnotherUsersCategoryAndUnknownIds(): void
    {
        $cat = $this->category(1, 'received');

        // User 2 (not an admin) cannot delete the category of user 1.
        $this->assertFalse(delete_category('deletereceivedcategory', $cat, 2));
        $this->assertSame(1, $this->rows('c_dropbox_category', 'iid = ?', [$cat]));
    }

    public function testDeleteCategoryOfUnknownIdReturnsFalse(): void
    {
        $this->assertFalse(delete_category('deletereceivedcategory', 999999999, 1));
        $this->assertFalse(delete_category('deletereceivedcategory', 0, 1));
    }

    public function testDeleteCategoryWithUnknownActionReturnsErrorAndKeepsTheCategory(): void
    {
        $cat = $this->category(1, 'received');

        $this->assertSame(get_lang('Error'), delete_category('nothing', $cat));
        $this->assertSame(1, $this->rows('c_dropbox_category', 'iid = ?', [$cat]));
    }

    public function testDeleteReceivedCategoryDeletesOnlyTheReceivedKindRow(): void
    {
        $received = $this->category(1, 'received');
        $sent = $this->category(1, 'sent');

        $this->assertSame(get_lang('The folder has been deleted'), delete_category('deletereceivedcategory', $received));
        $this->assertSame(0, $this->rows('c_dropbox_category', 'iid = ?', [$received]));

        // A "sent" category called with the "received" action keeps its row: the DELETE has "AND received = 1".
        $this->assertSame(get_lang('The folder has been deleted'), delete_category('deletereceivedcategory', $sent));
        $this->assertSame(1, $this->rows('c_dropbox_category', 'iid = ?', [$sent]));
    }

    public function testDeleteSentCategoryDeletesTheSentKindRow(): void
    {
        $sent = $this->category(1, 'sent');

        $this->assertSame(get_lang('The folder has been deleted'), delete_category('deletesentcategory', $sent));
        $this->assertSame(0, $this->rows('c_dropbox_category', 'iid = ?', [$sent]));
    }

    // ---------------------------------------------------------------- store_move

    public function testStoreMoveReceivedChangesOnlyMyPostOfThisFile(): void
    {
        $file = $this->file(2);
        $other = $this->file(2);
        $this->post($file, 1, 0);
        $this->post($file, 3, 0);
        $this->post($other, 1, 0);

        $this->assertSame(get_lang('The received file has been moved.'), store_move($file, 5, 'received'));

        $this->assertSame(5, (int) $this->conn->fetchOne('SELECT cat_id FROM c_dropbox_post WHERE file_id = ? AND dest_user_id = 1', [$file]));
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT cat_id FROM c_dropbox_post WHERE file_id = ? AND dest_user_id = 3', [$file]));
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT cat_id FROM c_dropbox_post WHERE file_id = ?', [$other]));
    }

    public function testStoreMoveReceivedIsScopedToTheCurrentCourse(): void
    {
        $file = $this->file(2, 0, 2);
        $this->post($file, 1, 0, 2);

        store_move($file, 5, 'received');

        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT cat_id FROM c_dropbox_post WHERE file_id = ?', [$file]));
    }

    public function testStoreMoveSentChangesOnlyMyUploadOfTheCurrentCourse(): void
    {
        $mine = $this->file(1);
        $notMine = $this->file(2);
        $otherCourse = $this->file(1, 0, 2);

        $this->assertSame(get_lang('The sent file has been moved'), store_move($mine, 7, 'sent'));
        store_move($notMine, 7, 'sent');
        store_move($otherCourse, 7, 'sent');

        $cat = fn (int $id): int => (int) $this->conn->fetchOne('SELECT cat_id FROM c_dropbox_file WHERE iid = ?', [$id]);
        $this->assertSame(7, $cat($mine));
        $this->assertSame(0, $cat($notMine), 'a file uploaded by someone else cannot be moved');
        $this->assertSame(0, $cat($otherCourse));
    }

    public function testStoreMoveWithMissingArgumentsMovesNothing(): void
    {
        $file = $this->file(2);
        $this->post($file, 1, 4);

        $this->assertSame(get_lang('The file(s) can not be moved.'), store_move('', 3, 'received'));
        $this->assertSame(get_lang('The file(s) can not be moved.'), store_move($file, '', 'received'));
        $this->assertSame(get_lang('The file(s) can not be moved.'), store_move($file, 3, ''));
        $this->assertSame(4, (int) $this->conn->fetchOne('SELECT cat_id FROM c_dropbox_post WHERE file_id = ?', [$file]));
        // A target of "0" (the root) is a non-empty string, so moving back to the root works.
        $this->assertSame(get_lang('The received file has been moved.'), store_move($file, '0', 'received'));
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT cat_id FROM c_dropbox_post WHERE file_id = ?', [$file]));
    }

    // ---------------------------------------------------------------- visibility of files

    public function testUserCanDownloadWhenInPersonOrPostOfTheCourse(): void
    {
        $viaPerson = $this->file(1);
        $this->person($viaPerson, 1);
        $viaPost = $this->file(2);
        $this->post($viaPost, 1);
        $stranger = $this->file(2);
        $this->person($stranger, 2);
        $this->post($stranger, 3);

        $this->assertTrue(user_can_download_file($viaPerson, 1));
        $this->assertTrue(user_can_download_file($viaPost, 1));
        $this->assertFalse(user_can_download_file($stranger, 1));
        $this->assertTrue(user_can_download_file($stranger, 2));
        $this->assertTrue(user_can_download_file($stranger, 3));
    }

    public function testUserCannotDownloadAFileLinkedInAnotherCourse(): void
    {
        $file = $this->file(1, 0, 2);
        $this->person($file, 1, 2);
        $this->post($file, 1, 0, 2);

        $this->assertFalse(user_can_download_file($file, 1), 'rows of course 2 do not grant access from course 1');

        $this->setContext(1, 2);
        $this->assertTrue(user_can_download_file($file, 1));
    }

    public function testCheckIfFileExistTellsIfAnyoneStillSeesIt(): void
    {
        $gone = $this->file(1);
        $viaPerson = $this->file(1);
        $this->person($viaPerson, 3);
        $viaPost = $this->file(1);
        $this->post($viaPost, 3);

        $this->assertFalse(check_if_file_exist($gone));
        $this->assertTrue(check_if_file_exist($viaPerson));
        $this->assertTrue(check_if_file_exist($viaPost));

        $this->setContext(1, 2);
        $this->assertFalse(check_if_file_exist($viaPerson), 'scoped to the current course');
    }

    // ---------------------------------------------------------------- feedback

    public function testStoreFeedbackInsertsTheRowAndSetsFeedbackIdToIid(): void
    {
        $file = $this->file(2);
        $this->post($file, 1);
        $_GET['id'] = (string) $file;
        $_POST['feedback'] = 'db_nice work';

        $this->assertSame(get_lang('The feedback message has been stored'), store_feedback());

        $row = $this->conn->fetchAssociative('SELECT * FROM c_dropbox_feedback WHERE file_id = ?', [$file]);
        $this->assertSame('db_nice work', $row['feedback']);
        $this->assertSame(1, (int) $row['author_user_id']);
        $this->assertSame(1, (int) $row['c_id']);
        $this->assertSame((int) $row['iid'], (int) $row['feedback_id']);
        $this->assertLessThanOrEqual(10, abs(time() - strtotime($row['feedback_date'].' UTC')));
    }

    public function testStoreFeedbackRejectsNonNumericIdAndEmptyText(): void
    {
        $_GET['id'] = 'abc';
        $_POST['feedback'] = 'text';
        $this->assertSame(get_lang('Feedback error'), store_feedback());

        $_GET['id'] = '5';
        $_POST['feedback'] = '';
        $this->assertSame(get_lang('Please type some text.'), store_feedback());
        $this->assertSame(0, $this->rows('c_dropbox_feedback', 'file_id = 5'));
    }

    public function testStoreFeedbackIsRefusedOnAFileTheUserCannotSee(): void
    {
        // Access control: the file id comes from the request. Feedback is only stored on a file the user sent
        // or received in this course, not on someone else's file nor on one of another course.
        $_POST['feedback'] = 'db_injected';
        $someoneElses = $this->file(2);
        $this->person($someoneElses, 2);
        $foreign = $this->file(2, 0, 2);
        $this->person($foreign, 1, 2);

        foreach ([$someoneElses, $foreign, 999999999] as $id) {
            $_GET['id'] = (string) $id;
            $this->assertSame(get_lang('Feedback error'), store_feedback());
        }

        $this->assertSame(0, $this->rows('c_dropbox_feedback', "feedback = 'db_injected'"));
    }

    public function testStoreFeedbackIsAcceptedOnASentOrReceivedFile(): void
    {
        $_POST['feedback'] = 'db_ok';
        $sent = $this->file(1);
        $this->person($sent, 1);
        $received = $this->file(2);
        $this->post($received, 1);

        foreach ([$sent, $received] as $id) {
            $_GET['id'] = (string) $id;
            $this->assertSame(get_lang('The feedback message has been stored'), store_feedback());
        }

        $this->assertSame(2, $this->rows('c_dropbox_feedback', "feedback = 'db_ok'"));
    }

    public function testFeedbackCountsAreGroupedByFileAndScopedToTheCourse(): void
    {
        $a = $this->file(1);
        $b = $this->file(1);
        $this->feedbackRow($a);
        $this->feedbackRow($a);
        $this->feedbackRow($b);
        $this->feedbackRow($b, 2);

        $counts = get_total_number_feedback();

        $this->assertSame(2, (int) $counts[$a]);
        $this->assertSame(1, (int) $counts[$b]);
        $this->assertSame(2, (int) check_number_feedback($a, $counts));
        $this->assertSame(0, check_number_feedback(999999999, $counts));
        $this->assertSame(0, check_number_feedback($a, null));
    }

    // ---------------------------------------------------------------- mailing helpers and cleanup

    public function testMailIdBaseIsBiggerThanAnyUserId(): void
    {
        $this->assertSame(self::MAILING_ID_BASE, get_mail_id_base());
    }

    public function testRemoveMoreIfMailingReassignsContentFilesOfAMailingToTheCurrentUser(): void
    {
        $pseudo = self::MAILING_ID_BASE + 5;
        $zip = $this->file(1);
        $this->post($zip, $pseudo);
        $content = $this->file($pseudo);
        $this->person($content, $pseudo);
        $this->person($content, 3);

        removeMoreIfMailing($zip);

        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT uploader_id FROM c_dropbox_file WHERE iid = ?', [$content]));
        $this->assertSame(0, $this->rows('c_dropbox_person', 'file_id = ? AND user_id = ?', [$content, $pseudo]));
        $this->assertSame(1, $this->rows('c_dropbox_person', 'file_id = ? AND user_id = 3', [$content]));
    }

    public function testRemoveMoreIfMailingIgnoresPlainRecipients(): void
    {
        $file = $this->file(2);
        $this->post($file, 3);
        $uploadedByRecipient = $this->file(3);

        removeMoreIfMailing($file);

        $this->assertSame(3, (int) $this->conn->fetchOne('SELECT uploader_id FROM c_dropbox_file WHERE iid = ?', [$uploadedByRecipient]));
    }

    public function testGetLastToolAccessReturnsTheMostRecentDateOfTheTool(): void
    {
        foreach ([['2020-01-01 10:00:00', 'dropbox'], ['2020-03-01 10:00:00', 'dropbox'], ['2020-05-01 10:00:00', 'forum']] as [$date, $tool]) {
            $this->conn->insert('track_e_lastaccess', [
                'access_user_id' => 1,
                'c_id' => 1,
                'access_date' => $date,
                'access_tool' => $tool,
                'session_id' => 0,
            ]);
        }

        $this->assertSame('2020-03-01 10:00:00', get_last_tool_access('dropbox'));
        $this->assertSame('2020-05-01 10:00:00', get_last_tool_access('forum', 1, 1));
    }

    public function testGetLastToolAccessWithoutAnyAccessRaisesAWarning(): void
    {
        // PINNED QUIRK: with no track_e_lastaccess row the function reads $row['access_date'] of false.
        $this->assertStringContainsString('access_date', $this->warningRaisedBy(
            static fn () => get_last_tool_access('dropbox', 2, 1)
        ));
    }

    public function testRemoveUnusedFilesDeletesFilesNobodyKeepsAndTheirPosts(): void
    {
        $orphan = $this->file(1, 0, 1, 'db_orphan_nonexistent.txt');
        $this->post($orphan, 3);
        $kept = $this->file(1);
        $this->person($kept, 1);
        $otherCourseOrphan = $this->file(1, 0, 2);

        removeUnusedFiles();

        $this->assertSame(0, $this->rows('c_dropbox_file', 'iid = ?', [$orphan]));
        $this->assertSame(0, $this->rows('c_dropbox_post', 'file_id = ?', [$orphan]));
        $this->assertSame(1, $this->rows('c_dropbox_file', 'iid = ?', [$kept]), 'a file somebody still has is kept');
        $this->assertSame(1, $this->rows('c_dropbox_file', 'iid = ?', [$otherCourseOrphan]), 'other courses are not touched');
    }

    public function testGetUserOwningThisMailingReturnsTheUploaderOfTheMailingFile(): void
    {
        $pseudo = self::MAILING_ID_BASE + 1;
        $zip = $this->file(1);
        $this->post($zip, $pseudo);

        $this->assertSame(1, (int) getUserOwningThisMailing($pseudo));
        $this->assertTrue(getUserOwningThisMailing($pseudo, 1));
    }

    public function testDeleteSentCategoryWithAFileStillFailsInDropboxClass(): void
    {
        // PINNED QUIRK (not fixable from this file): after the "id" -> "iid" repair here, the loop reaches
        // Dropbox_Person -> Dropbox_SentWork -> Dropbox_Work, whose own SQL (dropbox_class.inc.php) still
        // targets removed columns. The legacy dropbox classes belong to a later pass; see the decisions file.
        $cat = $this->category(1, 'sent');
        $this->person($this->file(1, $cat), 1);

        $this->expectException(NotAllowedException::class);

        delete_category('deletesentcategory', $cat);
    }
}
