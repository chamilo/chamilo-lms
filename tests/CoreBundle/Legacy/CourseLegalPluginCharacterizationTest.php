<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use CourseLegalPlugin;

/**
 * Characterization of public/plugin/CourseLegal/CourseLegalPlugin.php.
 *
 * LIVE: getData / save (plugin start.php, course_info/legal.php), getUserAcceptedLegal, isUserAcceptedLegal
 * (CourseManager::is_user_accepted_legal), saveUserLegal, saveUserMailLegal (course_info/legal.php),
 * getUserAgreementList, deleteUserAgreement, updateMailAgreementLink (plugin user_list.php), removePreviousAgreements
 * (save), getCurrentFile (start.php, legal.php), install/uninstall (plugin installer).
 * Not exercised: the methods that send mails or messages (sendMailLink, warnUsersByEmail, saveUserLegal with
 * $sendEmail, updateMailAgreementLink) and the file upload branch of save() (is_uploaded_file()): they only add
 * delivery on top of the rows pinned here.
 *
 * Fixture tag: cl_ (fixture users cl_*, courses 1 and 2, session ids 7001/7002 which have no row of their own).
 */
final class CourseLegalPluginCharacterizationTest extends PluginCharacterizationTestCase
{
    private const SESSION = 7001;

    private CourseLegalPlugin $plugin;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::ensurePluginTables(
            ['session_rel_course_legal', 'session_rel_course_rel_user_legal'],
            'CourseLegal',
            'CourseLegal/CourseLegalPlugin.php',
            'CourseLegalPlugin'
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        require_once api_get_path(SYS_PLUGIN_PATH).'CourseLegal/CourseLegalPlugin.php';
        $this->plugin = CourseLegalPlugin::create();
    }

    // ---------------------------------------------------------------- fixtures

    private function legal(int $courseId, int $sessionId, string $content, ?string $filename = null): int
    {
        $this->conn->executeStatement(
            'INSERT INTO session_rel_course_legal (c_id, session_id, content, filename) VALUES (?, ?, ?, ?)',
            [$courseId, $sessionId, $content, $filename]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function agreement(
        int $userId,
        int $courseId,
        int $sessionId,
        string $web = '1',
        string $mail = '0',
        string $link = 'cl_link',
        ?string $mailDate = null
    ): int {
        $this->conn->executeStatement(
            'INSERT INTO session_rel_course_rel_user_legal
                (user_id, c_id, session_id, web_agreement, web_agreement_date, mail_agreement, mail_agreement_date, mail_agreement_link)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$userId, $courseId, $sessionId, $web, '2026-01-01 10:00:00', $mail, $mailDate, $link]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function agreementRows(int $courseId, int $sessionId): array
    {
        return $this->conn->fetchAllAssociative(
            'SELECT * FROM session_rel_course_rel_user_legal WHERE c_id = ? AND session_id = ? ORDER BY id',
            [$courseId, $sessionId]
        );
    }

    // ---------------------------------------------------------------- legal text

    public function testGetDataIsScopedByCourseAndSession(): void
    {
        $this->legal(1, 0, 'cl_base text');
        $this->legal(1, self::SESSION, 'cl_session text');
        $this->legal(2, 0, 'cl_other course');

        $this->assertSame('cl_base text', $this->plugin->getData(1, 0)['content']);
        $this->assertSame('cl_session text', $this->plugin->getData(1, self::SESSION)['content']);
        $this->assertSame('cl_other course', $this->plugin->getData(2, 0)['content']);
        $this->assertSame([], $this->plugin->getData(2, self::SESSION));
    }

    public function testGetDataWithoutSessionReadsTheBaseCourseText(): void
    {
        // course_info/legal.php passes $session_id = null when the parameter is absent: Database::select() binds
        // it as 0, so the legal text of the base course is found (no cast needed in getData()).
        $this->legal(1, 0, 'cl_base text');
        $this->legal(1, self::SESSION, 'cl_session text');

        $this->assertSame('cl_base text', $this->plugin->getData(1, null)['content']);
    }

    public function testSaveCreatesTheLegalTextThenUpdatesTheSameRow(): void
    {
        $this->plugin->save(['c_id' => 1, 'session_id' => self::SESSION, 'content' => 'cl_v1']);
        $first = $this->plugin->getData(1, self::SESSION);
        $this->assertSame('cl_v1', $first['content']);
        $this->assertNull($first['filename']);

        $this->plugin->save(['c_id' => '1', 'session_id' => (string) self::SESSION, 'content' => 'cl_v2']);

        $this->assertSame('cl_v2', $this->plugin->getData(1, self::SESSION)['content']);
        $this->assertSame($first['id'], $this->plugin->getData(1, self::SESSION)['id']);
        $this->assertSame(
            1,
            (int) $this->conn->fetchOne('SELECT COUNT(*) FROM session_rel_course_legal WHERE c_id = 1 AND session_id = ?', [self::SESSION])
        );
        $this->assertSame([], $this->plugin->getData(1, 0));
    }

    public function testSaveUsesTheCourseOfTheValuesItIsGiven(): void
    {
        // PINNED QUIRK: save() trusts $values['c_id'] / ['session_id']; plugin start.php passes the hidden fields
        // of the posted form, so the caller is the one that must pin them to the course in the URL.
        $this->legal(2, 0, 'cl_other course');

        $this->plugin->save(['c_id' => 2, 'session_id' => 0, 'content' => 'cl_overwritten']);

        $this->assertSame('cl_overwritten', $this->plugin->getData(2, 0)['content']);
    }

    public function testSaveDeleteFileEmptiesTheFilenameButKeepsTheText(): void
    {
        $this->legal(1, self::SESSION, 'cl_with file', 'cl_file.pdf');

        $this->plugin->save(['c_id' => 1, 'session_id' => self::SESSION, 'content' => 'cl_with file v2'], [], true);

        $data = $this->plugin->getData(1, self::SESSION);
        $this->assertSame('cl_with file v2', $data['content']);
        $this->assertSame('', $data['filename']);
    }

    public function testSaveWithoutAnUploadKeepsTheExistingFilename(): void
    {
        $this->legal(1, self::SESSION, 'cl_text', 'cl_keep.pdf');

        $this->plugin->save(['c_id' => 1, 'session_id' => self::SESSION, 'content' => 'cl_text 2']);

        $this->assertSame('cl_keep.pdf', $this->plugin->getData(1, self::SESSION)['filename']);
    }

    public function testSaveRemovePreviousAgreementsOnlyClearsTheAgreementsOfThatCourseAndSession(): void
    {
        $u1 = $this->rawUser('cl_u1');
        $u2 = $this->rawUser('cl_u2');
        $this->agreement($u1, 1, self::SESSION);
        $this->agreement($u2, 1, self::SESSION);
        $this->agreement($u1, 1, 0);
        $this->agreement($u1, 2, self::SESSION);

        $this->plugin->save([
            'c_id' => 1,
            'session_id' => self::SESSION,
            'content' => 'cl_text',
            'remove_previous_agreements' => '1',
        ]);

        $this->assertSame([], $this->agreementRows(1, self::SESSION));
        $this->assertCount(1, $this->agreementRows(1, 0));
        $this->assertCount(1, $this->agreementRows(2, self::SESSION));
    }

    public function testSaveKeepsTheAgreementsWhenTheBoxIsNotTicked(): void
    {
        $this->agreement($this->rawUser('cl_k1'), 1, self::SESSION);

        $this->plugin->save(['c_id' => 1, 'session_id' => self::SESSION, 'content' => 'cl_text', 'remove_previous_agreements' => '0']);

        $this->assertCount(1, $this->agreementRows(1, self::SESSION));
    }

    public function testCurrentFileLinkIsEmptyWhenThereIsNoStoredFile(): void
    {
        $this->legal(1, self::SESSION, 'cl_text', 'cl_missing_'.getmypid().'.pdf');

        $this->assertSame('', $this->plugin->getCurrentFile(1, self::SESSION));
        $this->assertSame('', $this->plugin->getCurrentFile(1, 7002));
    }

    public function testStoredFilePathIsInsideTheCourseAndSessionDirectoryWhateverTheFilename(): void
    {
        $path = $this->plugin->getStoredFilePath(5, 6, '../../etc/passwd');

        $this->assertStringEndsWith('/var/upload/course_legal/course_5/session_6/passwd', $path);
    }

    // ---------------------------------------------------------------- user agreements

    public function testUserAcceptedLegalIsScopedByUserCourseAndSession(): void
    {
        $user = $this->rawUser('cl_a');
        $other = $this->rawUser('cl_b');
        $row = $this->agreement($user, 1, self::SESSION);

        $this->assertSame($row, (int) $this->plugin->getUserAcceptedLegal($user, 1, self::SESSION)['id']);
        $this->assertSame([], $this->plugin->getUserAcceptedLegal($other, 1, self::SESSION));
        $this->assertSame([], $this->plugin->getUserAcceptedLegal($user, 2, self::SESSION));
        $this->assertSame([], $this->plugin->getUserAcceptedLegal($user, 1, 0));
        $this->assertSame($row, (int) $this->plugin->getUserAcceptedLegal((string) $user, '1', (string) self::SESSION)['id']);
    }

    public function testIsUserAcceptedLegalNeedsTheWebAgreementToBeExactlyOne(): void
    {
        $accepted = $this->rawUser('cl_acc');
        $refused = $this->rawUser('cl_ref');
        $none = $this->rawUser('cl_none');
        $this->agreement($accepted, 1, 0, '1');
        $this->agreement($refused, 1, 0, '0');

        $this->assertTrue($this->plugin->isUserAcceptedLegal($accepted, 'AIACT', 0));
        $this->assertFalse($this->plugin->isUserAcceptedLegal($refused, 'AIACT', 0));
        $this->assertFalse($this->plugin->isUserAcceptedLegal($none, 'AIACT', 0));
        $this->assertFalse($this->plugin->isUserAcceptedLegal($accepted, 'USINGCHAMILO', 0));
        $this->assertFalse($this->plugin->isUserAcceptedLegal($accepted, 'AIACT', self::SESSION));
    }

    public function testSaveUserLegalRecordsTheWebAgreementWithoutMail(): void
    {
        $user = $this->rawUser('cl_save');

        $id = $this->plugin->saveUserLegal($user, 'AIACT', self::SESSION);

        $row = $this->conn->fetchAssociative('SELECT * FROM session_rel_course_rel_user_legal WHERE id = ?', [$id]);
        $this->assertSame($user, (int) $row['user_id']);
        $this->assertSame(1, (int) $row['c_id']);
        $this->assertSame(self::SESSION, (int) $row['session_id']);
        $this->assertSame('1', $row['web_agreement']);
        $this->assertNotNull($row['web_agreement_date']);
        // Without mail confirmation the mail side counts as already agreed, with the same date logic.
        $this->assertSame('1', $row['mail_agreement']);
        $this->assertNotNull($row['mail_agreement_date']);
        $this->assertNotSame('', (string) $row['mail_agreement_link']);
        $this->assertTrue($this->plugin->isUserAcceptedLegal($user, 'AIACT', self::SESSION));
    }

    public function testSaveUserLegalAgainRefreshesTheSameRowAndKeepsItsLink(): void
    {
        $user = $this->rawUser('cl_again');
        $row = $this->agreement($user, 1, self::SESSION, '0', '0', 'cl_orig_link');

        $id = $this->plugin->saveUserLegal($user, 'AIACT', self::SESSION);

        $this->assertSame($row, $id);
        $data = $this->plugin->getUserAcceptedLegal($user, 1, self::SESSION);
        $this->assertSame('1', $data['web_agreement']);
        $this->assertSame('cl_orig_link', $data['mail_agreement_link']);
        $this->assertNotSame('2026-01-01 10:00:00', $data['web_agreement_date']);
        $this->assertCount(1, $this->agreementRows(1, self::SESSION));
    }

    public function testSaveUserLegalWithoutSessionIsStoredForTheBaseCourse(): void
    {
        // legal.php hands $session_id = null over when the URL has none: it is the base course (session 0).
        $user = $this->rawUser('cl_nullsess');

        $id = $this->plugin->saveUserLegal($user, 'AIACT', null);

        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT session_id FROM session_rel_course_rel_user_legal WHERE id = ?', [$id]));
        $this->assertTrue($this->plugin->isUserAcceptedLegal($user, 'AIACT', null));
        $this->assertTrue($this->plugin->isUserAcceptedLegal($user, 'AIACT', 0));
    }

    public function testSaveUserMailLegalConfirmsTheMailWhenTheLinkMatches(): void
    {
        $user = $this->rawUser('cl_mail');
        $this->agreement($user, 1, self::SESSION, '1', '0', 'cl_good_link');

        $this->plugin->saveUserMailLegal('cl_bad_link', $user, 1, self::SESSION);
        $this->assertSame('0', $this->plugin->getUserAcceptedLegal($user, 1, self::SESSION)['mail_agreement']);

        $this->plugin->saveUserMailLegal('cl_good_link', $user, 1, self::SESSION);
        $data = $this->plugin->getUserAcceptedLegal($user, 1, self::SESSION);
        $this->assertSame('1', $data['mail_agreement']);
        $this->assertNotNull($data['mail_agreement_date']);
    }

    public function testSaveUserMailLegalOfAnotherUserNeverTouchesTheRow(): void
    {
        $owner = $this->rawUser('cl_mail_o');
        $thief = $this->rawUser('cl_mail_t');
        $this->agreement($owner, 1, self::SESSION, '1', '0', 'cl_owner_link');

        $this->assertNull($this->plugin->saveUserMailLegal('cl_owner_link', $thief, 1, self::SESSION));

        $this->assertSame('0', $this->plugin->getUserAcceptedLegal($owner, 1, self::SESSION)['mail_agreement']);
    }

    public function testSaveUserMailLegalComparesTheLinksExactly(): void
    {
        // Two different links that both read as "0e..." numbers must not be taken for each other.
        $user = $this->rawUser('cl_loose');
        $this->agreement($user, 1, self::SESSION, '1', '0', '0e111');

        $this->plugin->saveUserMailLegal('0e222', $user, 1, self::SESSION);
        $this->assertSame('0', $this->plugin->getUserAcceptedLegal($user, 1, self::SESSION)['mail_agreement']);

        $this->plugin->saveUserMailLegal('0e111', $user, 1, self::SESSION);
        $this->assertSame('1', $this->plugin->getUserAcceptedLegal($user, 1, self::SESSION)['mail_agreement']);
    }

    public function testAgreementListJoinsTheUsersAndHonoursTheOrderClause(): void
    {
        $zed = $this->rawUser('cl_zed');
        $abe = $this->rawUser('cl_abe');
        $this->conn->executeStatement('UPDATE user SET firstname = ? WHERE id = ?', ['Zed', $zed]);
        $this->conn->executeStatement('UPDATE user SET firstname = ? WHERE id = ?', ['Abe', $abe]);
        $this->agreement($zed, 1, self::SESSION);
        $this->agreement($abe, 1, self::SESSION);
        $this->agreement($abe, 1, 0);
        $this->agreement($abe, 2, self::SESSION);

        $byInsertion = $this->plugin->getUserAgreementList(1, self::SESSION);
        $ordered = $this->plugin->getUserAgreementList(1, self::SESSION, ' ORDER BY firstname, lastname');

        $this->assertCount(2, $byInsertion);
        $this->assertSame(['Abe', 'Zed'], array_column($ordered, 'firstname'));
        $this->assertSame([$abe, $zed], array_map('intval', array_column($ordered, 'user_id')));
        $this->assertSame([], $this->plugin->getUserAgreementList(2, 0));
    }

    public function testDeleteUserAgreementRemovesOnlyThatUsersRowInThatCourseAndSession(): void
    {
        $user = $this->rawUser('cl_del');
        $other = $this->rawUser('cl_del_o');
        $this->agreement($user, 1, self::SESSION);
        $this->agreement($user, 1, 0);
        $this->agreement($other, 1, self::SESSION);

        $this->plugin->deleteUserAgreement($user, 1, self::SESSION);

        $this->assertSame([$other], array_map(
            static fn (array $r): int => (int) $r['user_id'],
            $this->agreementRows(1, self::SESSION)
        ));
        $this->assertCount(1, $this->agreementRows(1, 0));
        // Deleting what does not exist is silent.
        $this->plugin->deleteUserAgreement($user, 1, self::SESSION);
        $this->assertCount(1, $this->agreementRows(1, self::SESSION));
    }

    public function testRemovePreviousAgreementsClearsTheWholeCourseSession(): void
    {
        $this->agreement($this->rawUser('cl_rp1'), 1, self::SESSION);
        $this->agreement($this->rawUser('cl_rp2'), 1, self::SESSION);
        $this->agreement($this->rawUser('cl_rp3'), 1, 0);

        $this->plugin->removePreviousAgreements(1, self::SESSION);

        $this->assertSame([], $this->agreementRows(1, self::SESSION));
        $this->assertCount(1, $this->agreementRows(1, 0));
    }
}
