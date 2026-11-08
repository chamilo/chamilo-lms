<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Justification;
use Plugin;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Characterization of public/plugin/Justification/Justification.php.
 *
 * Unlike the other plugins the two tables (justification_document, justification_document_rel_users) are doctrine
 * entities, so they exist in the test database and need no installer step.
 *
 * LIVE: getList, getJustification (plugin list/edit/upload pages), getUserJustificationList (upload.php,
 * justification_by_user.php, cron.php), getUserJustification / getUserJustificationFileSystemPath (download.php),
 * deleteUserJustification (justification_by_user.php), saveUploadedJustification + resolveValidityDate
 * (upload.php), getDefaultCourseId (cron.php, set_course.php). getUserJustificationByDocument is live through
 * saveUploadedJustification only. Page-level access control (admin / session admin / owner) lives in the plugin
 * pages and cannot be exercised here.
 *
 * Fixture tag: ju_.
 */
final class JustificationPluginCharacterizationTest extends PluginCharacterizationTestCase
{
    private Justification $plugin;

    protected function setUp(): void
    {
        parent::setUp();
        require_once api_get_path(SYS_PLUGIN_PATH).'Justification/Justification.php';
        $this->plugin = Justification::create();
    }

    // ---------------------------------------------------------------- fixtures

    private function document(string $title, string $code, ?int $duration = null, int $manual = 0, string $comment = ''): int
    {
        $this->conn->executeStatement(
            'INSERT INTO justification_document (code, title, validity_duration, comment, date_manual_on)
             VALUES (?, ?, ?, ?, ?)',
            [$code, 'ju_'.$title, $duration, $comment, $manual]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function justification(int $userId, int $documentId, string $path = '', string $validity = '2026-12-31'): int
    {
        $this->conn->executeStatement(
            'INSERT INTO justification_document_rel_users (justification_document_id, user_id, file_path, date_validity)
             VALUES (?, ?, ?, ?)',
            [$documentId, $userId, $path, $validity]
        );

        return (int) $this->conn->lastInsertId();
    }

    // ---------------------------------------------------------------- documents

    public function testGetJustificationAddsTheLegacyNameKey(): void
    {
        $id = $this->document('diploma', 'JU_DIP', 365, 1, 'ju comment');

        $doc = $this->plugin->getJustification($id);

        $this->assertSame('ju_diploma', $doc['title']);
        $this->assertSame('ju_diploma', $doc['name']);
        $this->assertSame('JU_DIP', $doc['code']);
        $this->assertSame(365, (int) $doc['validity_duration']);
        $this->assertSame(1, (int) $doc['date_manual_on']);
        $this->assertFalse($this->plugin->getJustification(0));
        $this->assertFalse($this->plugin->getJustification(987654321));
        $this->assertSame($id, (int) $this->plugin->getJustification((string) $id)['id']);
    }

    public function testListIsSortedByTitleAndCarriesTheLegacyNameKey(): void
    {
        $this->document('mid', 'JU_M');
        $this->document('zebra', 'JU_Z');
        $this->document('alpha', 'JU_A');

        $titles = array_values(array_filter(
            array_column($this->plugin->getList(), 'title'),
            static fn (string $title): bool => str_starts_with($title, 'ju_')
        ));

        $this->assertSame(['ju_alpha', 'ju_mid', 'ju_zebra'], $titles);
        foreach ($this->plugin->getList() as $doc) {
            $this->assertSame($doc['title'], $doc['name']);
        }
    }

    // ---------------------------------------------------------------- user justifications

    public function testUserJustificationListHoldsOnlyTheRowsOfThatUser(): void
    {
        $doc1 = $this->document('d1', 'JU_1');
        $doc2 = $this->document('d2', 'JU_2');
        $user = $this->rawUser('ju_u');
        $other = $this->rawUser('ju_o');
        $a = $this->justification($user, $doc1, 'user_'.$user.'/a.pdf');
        $b = $this->justification($user, $doc2, 'user_'.$user.'/b.pdf');
        $this->justification($other, $doc1, 'user_'.$other.'/c.pdf');

        $list = $this->plugin->getUserJustificationList($user);

        $this->assertSame([$a, $b], array_map('intval', array_column($list, 'id')));
        $this->assertSame([], $this->plugin->getUserJustificationList(0));
        $this->assertSame($b, (int) $this->plugin->getUserJustification($b)['id']);
        $this->assertFalse($this->plugin->getUserJustification(987654321));
    }

    public function testUserJustificationByDocumentIsScopedByUserAndDocument(): void
    {
        $doc1 = $this->document('bd1', 'JU_B1');
        $doc2 = $this->document('bd2', 'JU_B2');
        $user = $this->rawUser('ju_bd');
        $other = $this->rawUser('ju_bd_o');
        $row = $this->justification($user, $doc1, 'user_'.$user.'/a.pdf');
        $this->justification($other, $doc2, 'x/y.pdf');

        $this->assertSame($row, (int) $this->plugin->getUserJustificationByDocument($user, $doc1)['id']);
        $this->assertNull($this->plugin->getUserJustificationByDocument($user, $doc2));
        $this->assertNull($this->plugin->getUserJustificationByDocument($other, $doc1));
    }

    public function testFileSystemPathRefusesEmptyAndTraversingPaths(): void
    {
        $root = $this->plugin->getStorageRoot();

        $this->assertNull($this->plugin->getUserJustificationFileSystemPath(['file_path' => '']));
        $this->assertNull($this->plugin->getUserJustificationFileSystemPath([]));
        $this->assertNull($this->plugin->getUserJustificationFileSystemPath(['file_path' => '../../../etc/passwd']));
        $this->assertNull($this->plugin->getUserJustificationFileSystemPath(['file_path' => 'user_1/..%2f..']));
        $this->assertSame($root.'/user_4/doc.pdf', $this->plugin->getUserJustificationFileSystemPath(['file_path' => 'user_4/doc.pdf']));
        $this->assertSame($root.'/user_7', $this->plugin->getStoragePathForUser(7));
        $this->assertStringEndsWith('/var/upload/justification', $root);
    }

    public function testDownloadUrlPointsAtTheControlledDownloadScript(): void
    {
        $this->assertStringEndsWith('/plugin/Justification/download.php?id=42', $this->plugin->getUserJustificationDownloadUrl(42));
    }

    public function testDeleteUserJustificationRemovesOnlyThatRow(): void
    {
        $doc = $this->document('del', 'JU_DEL');
        $user = $this->rawUser('ju_del');
        $keep = $this->justification($user, $doc, '');
        $doc2 = $this->document('del2', 'JU_DEL2');
        $gone = $this->justification($user, $doc2, '');

        $this->assertTrue($this->plugin->deleteUserJustification($gone));

        $this->assertFalse($this->plugin->getUserJustification($gone));
        $this->assertNotFalse($this->plugin->getUserJustification($keep));
        $this->assertFalse($this->plugin->deleteUserJustification($gone));
        $this->assertFalse($this->plugin->deleteUserJustification(0));
        $this->assertFalse($this->plugin->deleteUserJustification(-3));
    }

    public function testDeletingADocumentCascadesToTheJustificationsUploadedForIt(): void
    {
        $doc = $this->document('cascade', 'JU_CAS');
        $user = $this->rawUser('ju_cas');
        $row = $this->justification($user, $doc, '');

        // list.php deletes the document with a plain DELETE; the foreign key removes the user rows (the stored
        // files of those rows are left on disk).
        $this->conn->executeStatement('DELETE FROM justification_document WHERE id = ?', [$doc]);

        $this->assertFalse($this->plugin->getUserJustification($row));
    }

    public function testUploadIsRefusedWhenTheFileIsNotAnUploadedOne(): void
    {
        $doc = $this->document('up', 'JU_UP');
        $user = $this->rawUser('ju_up');
        $tmp = tempnam(sys_get_temp_dir(), 'ju_');
        file_put_contents($tmp, 'x');
        try {
            $file = ['tmp_name' => $tmp, 'name' => 'proof.pdf'];

            // is_uploaded_file() is false for anything that did not come through a real HTTP upload.
            $this->assertFalse($this->plugin->saveUploadedJustification($user, $doc, $file));
            $this->assertFalse($this->plugin->saveUploadedJustification(0, $doc, $file));
            $this->assertFalse($this->plugin->saveUploadedJustification($user, 0, $file));
            $this->assertFalse($this->plugin->saveUploadedJustification($user, $doc, []));
            $this->assertNull($this->plugin->getUserJustificationByDocument($user, $doc));
        } finally {
            unlink($tmp);
        }
    }

    // ---------------------------------------------------------------- validity date

    private function validity(array $document, ?string $manual): string
    {
        $method = new ReflectionMethod(Justification::class, 'resolveValidityDate');
        $method->setAccessible(true);

        return $method->invoke($this->plugin, $document, $manual);
    }

    public function testValidityDateTakesTheManualDateOnlyWhenTheDocumentAllowsIt(): void
    {
        $this->assertSame('2030-02-03', $this->validity(['date_manual_on' => 1, 'validity_duration' => 10], '2030-02-03'));
        $this->assertSame('2030-02-03', $this->validity(['date_manual_on' => 1], '3 February 2030'));
        // Manual date ignored when the document does not allow it: the duration applies.
        $this->assertSame(
            date('Y-m-d', strtotime('+10 days')),
            $this->validity(['date_manual_on' => 0, 'validity_duration' => 10], '2030-02-03')
        );
    }

    public function testValidityDateFallsBackToTheDurationThenToToday(): void
    {
        $this->assertSame(date('Y-m-d', strtotime('+365 days')), $this->validity(['validity_duration' => '365'], null));
        $this->assertSame(
            date('Y-m-d', strtotime('+30 days')),
            $this->validity(['date_manual_on' => 1, 'validity_duration' => 30], 'not a date')
        );
        $this->assertSame(date('Y-m-d'), $this->validity(['validity_duration' => 0], null));
        $this->assertSame(date('Y-m-d'), $this->validity(['validity_duration' => -5], ''));
        $this->assertSame(date('Y-m-d'), $this->validity([], null));
    }

    // ---------------------------------------------------------------- settings

    public function testDefaultCourseIdComesFromThePluginSetting(): void
    {
        $prop = new ReflectionProperty(Plugin::class, 'settings');
        $prop->setAccessible(true);
        $prop->setValue($this->plugin, ['default_course_id' => '7']);

        $this->assertSame(7, $this->plugin->getDefaultCourseId());
        $this->assertStringEndsWith('/Justification/list.php', $this->plugin->getAdminUrl());
    }
}
