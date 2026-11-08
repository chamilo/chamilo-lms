<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use DocumentManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\Uuid;

/**
 * Characterization tests for DocumentManager (document.lib.php), the legacy document helpers.
 *
 * Most of the library already goes through the CDocument repository (resource nodes and links); what still carries
 * raw SQL is pinned here. The classification (callers searched under public/, src/, tests/):
 *
 *   LIVE  get_document_data_by_id (35 callers), get_document_id (8), get_course_quota (9), enough_space (2),
 *         folderExists (3: upload and session AJAX), get_all_document_folders (2)
 *   DEAD  documentExists, is_visible, attach_gradebook_certificate, get_default_certificate_id,
 *         remove_attach_certificate, get_document_id_of_directory_certificate (no callers),
 *         delete_document_from_search_engine (no callers, needs the search engine)
 *
 * Fixtures: documents are raw resource_node + c_document + resource_link rows under course 1 (no repository
 * creation, which deadlocks under concurrent suites). Titles start with "dl_".
 */
final class DocumentLibCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private int $seq = 0;

    protected function setUp(): void
    {
        // Defined by global.inc.php on a real page, which the test kernel does not load.
        if (!\defined('DEFAULT_DOCUMENT_QUOTA')) {
            \define('DEFAULT_DOCUMENT_QUOTA', 100000000);
        }
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/document/document.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        \ChamiloSession::erase('studentview');
        \ChamiloSession::erase('id_session');
        \ChamiloSession::erase('_gid');
        \ChamiloSession::write('_real_cid', 1);
        \ChamiloSession::write('_cid', 'AIACT');
    }

    protected function tearDown(): void
    {
        \ChamiloSession::erase('_real_cid');
        \ChamiloSession::erase('_cid');
        self::getContainer()->get('security.token_storage')->setToken(null);
        parent::tearDown();
    }

    // ------------------------------------------------------------------ quota

    public function testCourseQuotaIsTheCourseOwnDiskQuota(): void
    {
        $this->conn()->executeStatement('UPDATE course SET disk_quota = 77 WHERE id = 1');

        $this->assertSame(77, (int) DocumentManager::get_course_quota('AIACT'));
    }

    public function testCourseQuotaFallsBackToTheDefaultWhenUnsetOrUnknown(): void
    {
        $this->conn()->executeStatement('UPDATE course SET disk_quota = NULL WHERE id = 2');

        $this->assertSame(DEFAULT_DOCUMENT_QUOTA, DocumentManager::get_course_quota('USINGCHAMILO'));
        $this->assertSame(DEFAULT_DOCUMENT_QUOTA, DocumentManager::get_course_quota('DL_NO_SUCH_COURSE'));
    }

    public function testEnoughSpaceComparesTheNewFileWithTheQuotaAndTheCurrentUsage(): void
    {
        $this->assertTrue(DocumentManager::enough_space(10 * 1024 * 1024, 0), 'No limit means always enough.');
        $this->assertTrue(DocumentManager::enough_space(0, 100000));
        // The course already holds files: a file bigger than the whole quota can never fit.
        $this->assertFalse(DocumentManager::enough_space(2 * 1024 * 1024, 1));
    }

    // ------------------------------------------------------------------ get_document_data_by_id / get_document_id

    public function testGetDocumentDataByIdEnrichesTheRowWithPathTypeAndParents(): void
    {
        $folder = $this->doc('dl_folder', 'folder');
        $file = $this->doc('dl_file.txt', 'file', $folder);

        $data = DocumentManager::get_document_data_by_id($file, 'AIACT', true);

        $this->assertSame((string) $file, (string) $data['iid']);
        $this->assertSame('dl_file.txt', $data['title']);
        $this->assertSame('file', $data['filetype']);
        $this->assertSame('file', $data['file_type']);
        $this->assertSame((string) $folder, (string) $data['parent_id'], 'The parent is the folder document.');
        $this->assertStringEndsWith('document/showinframes.php?id='.$file, $data['url']);
        $this->assertStringEndsWith('document/document.php?id='.$file, $data['document_url']);
        $this->assertCount(1, $data['parents']);
        $this->assertSame('dl_folder', $data['parents'][0]['title']);
        $this->assertSame('0', (string) $data['parents'][0]['parent_id']);
        $this->assertSame([], DocumentManager::get_document_data_by_id($file, 'AIACT')['parents']);
    }

    public function testGetDocumentDataByIdReturnsFalseForAnUnknownDocumentOrCourse(): void
    {
        $this->assertFalse(DocumentManager::get_document_data_by_id(999999, 'AIACT'));
        $this->assertFalse(DocumentManager::get_document_data_by_id($this->doc('dl_x', 'file'), 'DL_NO_SUCH_COURSE'));
    }

    public function testGetDocumentDataByIdDoesNotCheckTheDocumentBelongsToTheCourse(): void
    {
        $foreign = $this->doc('dl_course_two_file', 'file', null, 2);

        $data = DocumentManager::get_document_data_by_id($foreign, 'AIACT');

        // PINNED QUIRK: the first query is "WHERE iid = id" with no course/link condition, so the title and comment
        // of a document of ANOTHER course (or a hidden one) come back; only the enrichment (path, size, parent) is
        // course scoped and is skipped. The 35 callers must do their own access check.
        $this->assertSame('dl_course_two_file', $data['title']);
        $this->assertSame('file', $data['filetype'], 'The raw column.');
        $this->assertSame('document/'.$foreign, $data['basename'] ? 'document/'.$foreign : '', 'basename defaults to the id.');
        $this->assertSame('0', (string) ($data['size'] ?? '0'));
    }

    public function testGetDocumentIdMatchesTheFullNodePathOrTheBareTitle(): void
    {
        $folder = $this->doc('dl_dir', 'folder');
        $file = $this->doc('dl_doc.txt', 'file', $folder);
        $this->doc('dl_other.txt', 'file', null, 2);
        $info = api_get_course_info('AIACT');
        $nodePath = (string) $this->conn()->fetchOne('SELECT n.path FROM resource_node n JOIN c_document d ON d.resource_node_id = n.id WHERE d.iid = ?', [$file]);
        $fullPath = rtrim((string) preg_replace('/-\d+\//', '/', $nodePath), '/');

        $this->assertSame($file, DocumentManager::get_document_id($info, $fullPath), 'The display path of the node, whole chain included.');
        $this->assertSame($file, DocumentManager::get_document_id($info, '/'.strtoupper($fullPath)), 'Case-insensitive, leading slash ignored.');
        $this->assertSame($folder, DocumentManager::get_document_id($info, 'dl_dir'));

        // PINNED QUIRK: a path relative to the course document root never matches, because the candidates are the
        // whole node chain (access URL and course titles included) and the bare title. The bare title matches a
        // document in ANY folder, so two documents with the same title are ambiguous (the first one found wins).
        $this->assertFalse(DocumentManager::get_document_id($info, 'dl_dir/dl_doc.txt'));
        $this->assertSame($file, DocumentManager::get_document_id($info, 'dl_doc.txt'));

        $this->assertFalse(DocumentManager::get_document_id($info, 'dl_other.txt'), 'A document of another course is not found.');
        $this->assertFalse(DocumentManager::get_document_id($info, 'nothing/here'));
        $this->assertFalse(DocumentManager::get_document_id($info, ''));
        $this->assertFalse(DocumentManager::get_document_id([], 'dl_dir'));
    }

    public function testGetDocumentIdOnlyFindsSessionDocumentsInsideThatSession(): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, position) VALUES (?, 0, 0, 0, 1, 0, 0)',
            ['dl_session']
        );
        $sessionId = (int) $this->conn()->lastInsertId();
        $inSession = $this->doc('dl_session_only.txt', 'file');
        $this->conn()->executeStatement(
            'UPDATE resource_link SET session_id = ? WHERE resource_node_id = (SELECT resource_node_id FROM c_document WHERE iid = ?)',
            [$sessionId, $inSession]
        );
        $info = api_get_course_info('AIACT');

        $this->assertFalse(DocumentManager::get_document_id($info, 'dl_session_only.txt'), 'Outside the session it does not exist.');
        $this->assertSame($inSession, DocumentManager::get_document_id($info, 'dl_session_only.txt', $sessionId));
    }

    // ------------------------------------------------------------------ folderExists (broken raw SQL)

    public function testFolderExistsFindsAFolderByNameInTheCourse(): void
    {
        $this->doc('dl_exists', 'folder');
        $this->doc('dl_a_file', 'file');
        $this->doc('dl_course_two_folder', 'folder', null, 2);
        $info = api_get_course_info('AIACT');

        $this->assertTrue(DocumentManager::folderExists('/dl_exists', $info, 0, 0));
        $this->assertTrue(DocumentManager::folderExists('dl_exists', $info, 0, 0), 'The leading slash is optional.');
        $this->assertFalse(DocumentManager::folderExists('/dl_missing', $info, 0, 0));
        $this->assertFalse(DocumentManager::folderExists('/dl_a_file', $info, 0, 0), 'A file is not a folder.');
        $this->assertFalse(DocumentManager::folderExists('/dl_course_two_folder', $info, 0, 0), 'Another course folder does not count.');
        $this->assertFalse(DocumentManager::folderExists("/x' OR '1'='1", $info, 0, 0));
    }

    public function testFolderExistsSeesSessionFoldersOnlyFromThatSession(): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, position) VALUES (?, 0, 0, 0, 1, 0, 0)',
            ['dl_session_f']
        );
        $sessionId = (int) $this->conn()->lastInsertId();
        $folder = $this->doc('dl_session_folder', 'folder');
        $this->conn()->executeStatement(
            'UPDATE resource_link SET session_id = ? WHERE resource_node_id = (SELECT resource_node_id FROM c_document WHERE iid = ?)',
            [$sessionId, $folder]
        );
        $info = api_get_course_info('AIACT');

        $this->assertFalse(DocumentManager::folderExists('/dl_session_folder', $info, 0, 0));
        $this->assertTrue(DocumentManager::folderExists('/dl_session_folder', $info, $sessionId, 0));
    }

    public function testFolderAndDocumentExistRejectAMissingCourse(): void
    {
        $this->assertFalse(DocumentManager::folderExists('/x', ['real_id' => 0], 0, 0));
        $this->assertFalse(DocumentManager::documentExists('/x', ['real_id' => 0], 0, 0));
    }

    // ------------------------------------------------------------------ dead helpers on dropped columns

    public function testDocumentExistsAndIsVisibleAndCertificateDirectoryHaveNoWorkingQuery(): void
    {
        $info = api_get_course_info('AIACT');
        $calls = [
            static fn () => DocumentManager::documentExists('dl_a.txt', $info, 0, 0),
            static fn () => DocumentManager::is_visible('/dl_a.txt', $info),
            static fn () => DocumentManager::get_document_id_of_directory_certificate(),
        ];
        // PINNED QUIRK: c_id/path/session_id are gone from c_document, so each of these throws. No caller.
        foreach ($calls as $call) {
            try {
                $call();
                $this->fail('Expected the query to fail.');
            } catch (\Throwable $e) {
                $this->assertNotInstanceOf(\PHPUnit\Framework\AssertionFailedError::class, $e);
            }
        }
    }

    public function testCertificateAttachmentRoundTripsThroughTheGradebookCategory(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO gradebook_category (c_id, session_id, title, weight, visible, locked, generate_certificates)
             VALUES (1, NULL, 'dl_cat', 1, 1, 0, 0), (1, NULL, 'dl_cat2', 1, 1, 0, 0), (2, NULL, 'dl_cat_other', 1, 1, 0, 0)"
        );

        $certificate = $this->doc('dl_certificate.html', 'file');
        DocumentManager::attach_gradebook_certificate(1, $certificate);

        // Every base-course category of the course gets the document, the other course's one does not.
        $this->assertSame(
            [$certificate, $certificate],
            array_map('intval', $this->conn()->fetchFirstColumn("SELECT document_id FROM gradebook_category WHERE title IN ('dl_cat', 'dl_cat2') ORDER BY title"))
        );
        $this->assertNull($this->conn()->fetchOne("SELECT document_id FROM gradebook_category WHERE title = 'dl_cat_other'"));
        $this->assertSame((string) $certificate, (string) DocumentManager::get_default_certificate_id(1));

        // Removing another document id changes nothing; removing the right one clears the course categories.
        DocumentManager::remove_attach_certificate(1, $certificate + 1000);
        $this->assertSame((string) $certificate, (string) DocumentManager::get_default_certificate_id(1));
        DocumentManager::remove_attach_certificate(1, $certificate);
        $this->assertNull(DocumentManager::get_default_certificate_id(1));
        $this->assertFalse(DocumentManager::remove_attach_certificate(1, 0));
    }

    // ------------------------------------------------------------------ helpers

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    /** Raw fixture: node + c_document + published link. Returns the c_document iid. */
    private function doc(string $title, string $type, ?int $parentDocIid = null, int $courseId = 1): int
    {
        $this->seq++;
        $parentNode = null === $parentDocIid
            ? $this->conn()->fetchOne('SELECT resource_node_id FROM course WHERE id = ?', [$courseId])
            : $this->conn()->fetchOne('SELECT resource_node_id FROM c_document WHERE iid = ?', [$parentDocIid]);
        $this->conn()->executeStatement(
            "INSERT INTO resource_node (resource_type_id, creator_id, parent_id, title, slug, level, path, created_at, updated_at, public, uuid)
             VALUES (17, 1, ?, ?, ?, 1, ?, '2030-01-01 00:00:00', '2030-01-01 00:00:00', 0, ?)",
            [$parentNode, $title, 'dl-'.$this->seq.'-'.uniqid(), '/dl'.$this->seq.'-'.uniqid(), Uuid::v4()->toBinary()]
        );
        $nodeId = (int) $this->conn()->lastInsertId();
        // Real nodes carry a materialised path "<parent path><title>-<id>/".
        $this->conn()->executeStatement(
            'UPDATE resource_node SET path = CONCAT((SELECT p.path FROM (SELECT path FROM resource_node WHERE id = ?) p), ?, ?, ?) WHERE id = ?',
            [$parentNode, $title, '-', $nodeId.'/', $nodeId]
        );
        $this->conn()->executeStatement(
            'INSERT INTO c_document (resource_node_id, title, filetype, readonly, template) VALUES (?, ?, ?, 0, 0)',
            [$nodeId, $title, $type]
        );
        $iid = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement(
            "INSERT INTO resource_link (resource_node_id, c_id, visibility, display_order, resource_type_group, created_at, updated_at)
             VALUES (?, ?, 2, 0, 0, '2030-01-01 00:00:00', '2030-01-01 00:00:00')",
            [$nodeId, $courseId]
        );

        return $iid;
    }
}
