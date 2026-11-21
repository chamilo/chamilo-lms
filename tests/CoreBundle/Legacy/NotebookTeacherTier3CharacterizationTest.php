<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use NotebookTeacher;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for NotebookTeacher::getNoteInformation() and deleteNote() (table plugin_notebook_teacher):
 * a note is only reachable by its owner, inside the current course.
 */
final class NotebookTeacherTier3CharacterizationTest extends KernelTestCase
{
    use PluginTier3SchemaTrait;

    private const COURSE = 987010;
    private const OWNER = 987011;
    private const OTHER = 987012;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::ensurePluginTable('NotebookTeacher', 'plugin_notebook_teacher');
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new HttpSession(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/plugin/NotebookTeacher/src/index.php');
        $request->setSession($session);
        $request->setLocale('en_US');
        Container::setRequest($request);
        ChamiloSession::write('_user', ['id' => self::OWNER]);
        ChamiloSession::write('_real_cid', self::COURSE);

        require_once api_get_path(SYS_PLUGIN_PATH).'NotebookTeacher/src/NotebookTeacherPlugin.php';

        require_once api_get_path(SYS_PLUGIN_PATH).'NotebookTeacher/src/NotebookTeacher.php';
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('_user');
        ChamiloSession::erase('_real_cid');
        parent::tearDown();
    }

    public function testNoteInformationReturnsTheAliasedColumnsOfTheOwnNote(): void
    {
        $id = $this->note(self::COURSE, self::OWNER, 'My title', 'My comment', 55, 3);

        $info = NotebookTeacher::getNoteInformation($id);

        $this->assertSame($id, (int) $info['notebook_id']);
        $this->assertSame('My title', $info['note_title']);
        $this->assertSame('My comment', $info['note_comment']);
        $this->assertSame(3, (int) $info['session_id']);
        $this->assertSame(55, (int) $info['student_id']);
        $this->assertSame(['notebook_id', 'note_title', 'note_comment', 'session_id', 'student_id'], array_values(array_filter(array_keys($info), 'is_string')));
    }

    public function testNoteInformationIsEmptyForAnotherUsersNoteAnotherCourseOrAnEmptyId(): void
    {
        $foreign = $this->note(self::COURSE, self::OTHER, 'x', 'y', 1, 0);
        $elsewhere = $this->note(self::COURSE + 1, self::OWNER, 'x', 'y', 1, 0);

        $this->assertSame([], NotebookTeacher::getNoteInformation($foreign));
        $this->assertSame([], NotebookTeacher::getNoteInformation($elsewhere));
        $this->assertSame([], NotebookTeacher::getNoteInformation(0));
        $this->assertSame([], NotebookTeacher::getNoteInformation(0 + 999999999));
    }

    public function testDeleteNoteRemovesOnlyTheOwnNoteOfTheCurrentCourse(): void
    {
        $mine = $this->note(self::COURSE, self::OWNER, 'a', 'b', 1, 0);
        $foreign = $this->note(self::COURSE, self::OTHER, 'a', 'b', 1, 0);
        $elsewhere = $this->note(self::COURSE + 1, self::OWNER, 'a', 'b', 1, 0);

        $this->assertFalse(NotebookTeacher::deleteNote($foreign));
        $this->assertFalse(NotebookTeacher::deleteNote($elsewhere));
        $this->assertSame(1, $this->exists($foreign));
        $this->assertSame(1, $this->exists($elsewhere));

        $this->assertTrue(NotebookTeacher::deleteNote($mine));
        $this->assertSame(0, $this->exists($mine));
        $this->assertFalse(NotebookTeacher::deleteNote($mine), 'Already deleted: nothing affected');
    }

    public function testDeleteNoteRejectsNonIntegerIds(): void
    {
        $mine = $this->note(self::COURSE, self::OWNER, 'a', 'b', 1, 0);

        $this->assertFalse(NotebookTeacher::deleteNote(''));
        $this->assertFalse(NotebookTeacher::deleteNote(0));
        $this->assertFalse(NotebookTeacher::deleteNote($mine.' OR 1=1'));
        $this->assertSame(1, $this->exists($mine));
    }

    private function conn(): Connection
    {
        return self::getContainer()->get('doctrine')->getConnection();
    }

    private function exists(int $id): int
    {
        return (int) $this->conn()->fetchOne('SELECT COUNT(*) FROM plugin_notebook_teacher WHERE id = ?', [$id]);
    }

    private function note(int $course, int $user, string $title, string $comment, int $student, int $session): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO plugin_notebook_teacher (c_id, session_id, user_id, student_id, course, title, description, creation_date, update_date, status)
             VALUES (?, ?, ?, ?, 'X', ?, ?, '2030-01-02 03:04:05', '2030-01-02 03:04:05', 0)",
            [$course, $session, $user, $student, $title, $comment]
        );

        return (int) $this->conn()->lastInsertId();
    }
}
