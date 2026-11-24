<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use DocumentManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\Uuid;

/**
 * Tier 3 characterization tests for DocumentManager (document.lib.php): the helpers the first suite does not reach
 * (build_directory_selector) and the exact row shape of get_document_data_by_id. Fixtures are raw rows, titles start
 * with "dt3_".
 */
final class DocumentLibTier3CharacterizationTest extends KernelTestCase
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
        ChamiloSession::erase('studentview');
        ChamiloSession::erase('id_session');
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

    public function testGetDocumentDataByIdKeepsTheRawRowColumnsAndTypes(): void
    {
        $file = $this->doc('dt3_row.txt', 'file');
        $this->conn()->executeStatement('UPDATE c_document SET comment = ?, readonly = 1 WHERE iid = ?', ['dt3 comment', $file]);

        $data = DocumentManager::get_document_data_by_id($file, 'AIACT');

        // Legacy callers read the raw c_document columns: integers stay integers (readonly and template are 0/1).
        $this->assertSame($file, $data['iid']);
        $this->assertIsInt($data['resource_node_id']);
        $this->assertSame('dt3_row.txt', $data['title']);
        $this->assertSame('file', $data['filetype']);
        $this->assertSame(1, $data['readonly']);
        $this->assertSame(0, $data['template']);
        $this->assertSame('dt3 comment', $data['comment']);
        $this->assertSame(
            ['iid', 'resource_node_id', 'title', 'comment', 'filetype', 'readonly', 'template'],
            \array_slice(array_keys($data), 0, 7)
        );
    }

    public function testBuildDirectorySelectorListsTheFoldersOfTheCurrentCourseOnly(): void
    {
        $folder = $this->doc('dt3_folder', 'folder');
        $child = $this->doc('dt3_child', 'folder', $folder);
        $file = $this->doc('dt3_file', 'file');
        $foreign = $this->doc('dt3_foreign', 'folder', null, 2);
        $deleted = $this->doc('dt3_deleted', 'folder');
        $this->conn()->executeStatement(
            'UPDATE resource_link SET deleted_at = ? WHERE resource_node_id = (SELECT resource_node_id FROM c_document WHERE iid = ?)',
            ['2030-02-02 00:00:00', $deleted]
        );
        $folders = [];
        foreach ([$folder, $child, $file, $foreign, $deleted] as $iid) {
            $folders[$iid] = (string) $this->conn()->fetchOne('SELECT n.path FROM resource_node n JOIN c_document d ON d.resource_node_id = n.id WHERE d.iid = ?', [$iid]);
        }

        $html = DocumentManager::build_directory_selector($folders, $child, '', true, $form, 'dt3_select');

        $this->assertStringContainsString('<option  value="/">', $html);
        $this->assertStringContainsString('dt3_folder</option>', $html);
        $this->assertStringContainsString('dt3_child</option>', $html);
        $this->assertStringNotContainsString('dt3_file', $html, 'A file is not a folder.');
        $this->assertStringNotContainsString('dt3_foreign', $html, 'The folder of another course is not listed.');
        $this->assertStringNotContainsString('dt3_deleted', $html, 'A folder whose link is deleted is not listed.');
        $this->assertMatchesRegularExpression('/<option  value="'.$child.'" selected="selected">/', $html, 'The current folder is selected.');
        $this->assertDoesNotMatchRegularExpression('/<option  value="'.$folder.'" selected/', $html);
        // The child is indented one level deeper than its parent.
        $this->assertStringContainsString('&nbsp;&nbsp;&nbsp; &mdash; dt3_child', $html);
    }

    public function testBuildDirectorySelectorWithoutFoldersOnlyOffersTheRoot(): void
    {
        $html = DocumentManager::build_directory_selector([], '', '', true, $form, 'dt3_select');

        $this->assertStringContainsString('<option  value="/">', $html);
        $this->assertStringNotContainsString('&mdash;', $html);
    }

    public function testCertificateAttachmentIsScopedByTheSession(): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, position) VALUES (?, 0, 0, 0, 1, 0, 0)',
            ['dt3_session']
        );
        $sessionId = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement(
            "INSERT INTO gradebook_category (c_id, session_id, title, weight, visible, locked, generate_certificates)
             VALUES (1, NULL, 'dt3_base', 1, 1, 0, 0), (1, ?, 'dt3_sess', 1, 1, 0, 0)",
            [$sessionId]
        );
        $certificate = $this->doc('dt3_certificate.html', 'file');
        $read = fn (string $title) => $this->conn()->fetchOne('SELECT document_id FROM gradebook_category WHERE title = ?', [$title]);

        // A positive session id only touches the categories of that session.
        DocumentManager::attach_gradebook_certificate(1, $certificate, $sessionId);
        $this->assertSame($certificate, (int) $read('dt3_sess'));
        $this->assertNull($read('dt3_base') ?: null);
        $this->assertSame($certificate, DocumentManager::get_default_certificate_id(1, $sessionId));
        $this->assertNull(DocumentManager::get_default_certificate_id(1, 0), 'The base course has no default certificate.');

        // A negative session id means every category of the course.
        DocumentManager::attach_gradebook_certificate(1, $certificate, -1);
        $this->assertSame($certificate, (int) $read('dt3_base'));

        // remove_attach_certificate works in the current session context (the one of the request).
        ChamiloSession::write('sid', $sessionId);
        DocumentManager::remove_attach_certificate(1, $certificate);
        ChamiloSession::erase('sid');
        $this->assertNull($read('dt3_sess') ?: null);
        $this->assertSame($certificate, (int) $read('dt3_base'), 'The base-course category keeps its certificate.');
    }

    // ------------------------------------------------------------------ helpers

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    /**
     * Raw fixture: node + c_document + published link. Returns the c_document iid.
     */
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
