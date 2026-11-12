<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\CourseStudentInfoHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use Chamilo\Tests\CoreBundle\Service\RawReportingFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * "New content" detection and last-access label of CourseStudentInfoHelper (raw DBAL over resource_link /
 * resource_node / track_e_lastaccess), the data behind the course cards' badge and notification popover.
 * Fixtures: raw learners ("rp_"); the content borrows installer resource nodes (documents = type "files",
 * exercises = type "exercises") whose dates are set to FIXED values and the installer's own links of the two
 * demo courses are soft-deleted, all inside the DAMA transaction. Tag sv3_.
 */
final class CourseStudentInfoHelperTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    private CourseStudentInfoHelper $helper;

    /**
     * @var int[]
     */
    private array $fileNodes = [];

    /**
     * @var int[]
     */
    private array $exerciseNodes = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $c = self::getContainer();
        // CourseStudentInfoHelper reads the legacy Container static, which an earlier test class leaves
        // pointing at its own, already shut down kernel.
        Container::setContainer($c);
        $this->helper = $c->get(CourseStudentInfoHelper::class);
        $this->fileNodes = array_map('intval', $this->conn()->fetchFirstColumn("SELECT rn.id FROM resource_node rn INNER JOIN resource_type rt ON rt.id = rn.resource_type_id INNER JOIN tool t ON t.id = rt.tool_id WHERE rt.title = 'files' AND t.title = 'document' ORDER BY rn.id LIMIT 3"));
        $this->exerciseNodes = array_map('intval', $this->conn()->fetchFirstColumn("SELECT rn.id FROM resource_node rn INNER JOIN resource_type rt ON rt.id = rn.resource_type_id INNER JOIN tool t ON t.id = rt.tool_id WHERE rt.title = 'exercises' AND t.title = 'quiz' ORDER BY rn.id LIMIT 2"));
        if (3 !== \count($this->fileNodes) || 2 !== \count($this->exerciseNodes)) {
            $this->markTestSkipped('The installer demo content is missing.');
        }
        // Groups, usergroups and sessions referenced by the links are not needed as rows.
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $c->get(SettingsManager::class)->updateSetting('course.course_student_info', '{"progress":true}');
        // Neutralise the installer content of the demo courses and freeze the borrowed nodes in the past.
        $this->conn()->executeStatement("UPDATE resource_link SET deleted_at = '2000-01-01 00:00:00' WHERE c_id IN (1, 2)");
        $ids = implode(',', array_merge($this->fileNodes, $this->exerciseNodes));
        $this->conn()->executeStatement("UPDATE resource_node SET created_at = '2019-01-01 00:00:00', updated_at = '2019-01-01 00:00:00' WHERE id IN ({$ids})");
    }

    protected function tearDown(): void
    {
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        parent::tearDown();
    }

    private function course(int $id = 1): Course
    {
        $this->fresh();

        return $this->getEntityManager()->find(Course::class, $id);
    }

    private function access(int $userId, string $tool, string $date, int $sessionId = 0, int $courseId = 1): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO track_e_lastaccess (access_user_id, access_date, c_id, access_tool, session_id) VALUES (?, ?, ?, ?, ?)',
            [$userId, $date, $courseId, $tool, $sessionId]
        );
    }

    private function link(int $nodeId, string $changed, array $over = []): void
    {
        $row = $over + [
            'resource_node_id' => $nodeId, 'c_id' => 1, 'session_id' => null, 'user_id' => null, 'group_id' => null,
            'usergroup_id' => null, 'visibility' => 2, 'deleted_at' => null,
        ];
        $this->conn()->executeStatement(
            'INSERT INTO resource_link (resource_node_id, c_id, session_id, user_id, group_id, usergroup_id, visibility, deleted_at, display_order, resource_type_group, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?)',
            [$row['resource_node_id'], $row['c_id'], $row['session_id'], $row['user_id'], $row['group_id'], $row['usergroup_id'], $row['visibility'], $row['deleted_at'], $changed, $changed]
        );
    }

    private function keys(array $tools): array
    {
        return array_map(static fn (array $t): string => $t['key'].':'.($t['count'] ?? 'null'), $tools);
    }

    public function testLastAccessLabelIsTheLatestAccessOfThatLearnerAndSession(): void
    {
        $u = $this->rawUser('learner');
        $other = $this->rawUser('other');
        $this->access($u, 'document', '2020-03-01 10:00:00');
        $this->access($u, 'link', '2020-05-01 10:00:00');
        $this->access($other, 'document', '2020-09-01 10:00:00');
        $this->access($u, 'document', '2020-08-01 10:00:00', 5);

        $this->assertSame('2020-05-01 10:00:00', $this->helper->getLastAccessLabelForCourse($u, 1, 0));
        $this->assertSame('2020-08-01 10:00:00', $this->helper->getLastAccessLabelForCourse($u, 1, 5), 'The session row wins');
        $this->assertSame('2020-05-01 10:00:00', $this->helper->getLastAccessLabelForCourse($u, 1, 7), 'Falls back to session 0 only');
        $this->assertNull($this->helper->getLastAccessLabelForCourse($u, 2, 0), 'Other course');
        $this->assertNull($this->helper->getLastAccessLabelForCourse($this->rawUser('never'), 1, 0));
    }

    public function testNoToolsWithoutAFirstAccess(): void
    {
        $u = $this->rawUser('learner');
        $this->link($this->fileNodes[0], '2020-06-01 00:00:00');

        $this->assertSame([], $this->helper->getNewContentToolsForCourse($u, $this->course()));
        $this->assertSame([], $this->helper->getNewContentToolsForCourse(0, $this->course()));
    }

    public function testToolsListChangesNewerThanTheToolAccessOrderedByLastChange(): void
    {
        $u = $this->rawUser('learner');
        $this->access($u, 'document', '2020-02-01 00:00:00');
        $this->access($u, 'link', '2020-01-01 00:00:00'); // earliest access = baseline of tools without own access
        $this->link($this->fileNodes[0], '2020-06-01 00:00:00'); // newer than the document access: counted
        $this->link($this->fileNodes[1], '2020-01-15 00:00:00'); // older than the document access: not counted
        $this->link($this->exerciseNodes[0], '2020-07-01 00:00:00'); // quiz: baseline is the first access

        $tools = $this->helper->getNewContentToolsForCourse($u, $this->course());

        $this->assertSame(['exercises:1', 'documents:1'], $this->keys($tools));
        $this->assertSame('2020-07-01 00:00:00', $tools[0]['lastChange']);
        $this->assertSame('2020-06-01 00:00:00', $tools[1]['lastChange']);
        $this->assertSame('Exercises', $tools[0]['label']);
        $this->assertSame('Documents', $tools[1]['label']);
        $this->assertStringContainsString('cid=1&sid=0&gid=0', (string) $tools[1]['url']);
        $this->assertCount(1, $this->helper->getNewContentToolsForCourse($u, $this->course(), 0, 1), 'Limit');
    }

    public function testContentOfTheSameToolIsCountedOncePerNode(): void
    {
        $u = $this->rawUser('learner');
        $this->access($u, 'document', '2020-02-01 00:00:00');
        $this->link($this->fileNodes[0], '2020-06-01 00:00:00');
        $this->link($this->fileNodes[1], '2020-06-02 00:00:00');
        $this->link($this->fileNodes[1], '2020-06-03 00:00:00', ['session_id' => null, 'c_id' => 1]); // second link of the same node

        $tools = $this->helper->getNewContentToolsForCourse($u, $this->course());

        $this->assertSame(['documents:2'], $this->keys($tools));
        $this->assertSame('2020-06-03 00:00:00', $tools[0]['lastChange']);
    }

    public function testOnlyVisiblePublishedLinksForThisLearnerCount(): void
    {
        $u = $this->rawUser('learner');
        $other = $this->rawUser('other');
        $this->access($u, 'document', '2020-02-01 00:00:00');
        $this->link($this->fileNodes[0], '2020-06-01 00:00:00', ['visibility' => 0]);                      // hidden
        $this->link($this->fileNodes[0], '2020-06-01 00:00:00', ['visibility' => 1]);                      // draft
        $this->link($this->fileNodes[0], '2020-06-01 00:00:00', ['deleted_at' => '2020-06-02 00:00:00']);   // deleted
        $this->link($this->fileNodes[0], '2020-06-01 00:00:00', ['user_id' => $other]);                    // for someone else
        $this->link($this->fileNodes[0], '2020-06-01 00:00:00', ['group_id' => 4]);                        // group content
        $this->link($this->fileNodes[0], '2020-06-01 00:00:00', ['usergroup_id' => 4]);                    // usergroup content
        $this->link($this->fileNodes[0], '2020-06-01 00:00:00', ['c_id' => 2]);                            // other course

        $this->assertSame([], $this->helper->getNewContentToolsForCourse($u, $this->course()));

        $this->link($this->fileNodes[1], '2020-06-05 00:00:00', ['user_id' => $u]);                        // addressed to the learner
        $this->assertSame(['documents:1'], $this->keys($this->helper->getNewContentToolsForCourse($u, $this->course())));
    }

    public function testSessionContentIsOnlyVisibleInThatSession(): void
    {
        $u = $this->rawUser('learner');
        $this->access($u, 'document', '2020-02-01 00:00:00', 0);
        $this->access($u, 'document', '2020-02-01 00:00:00', 9);
        $this->link($this->fileNodes[0], '2020-06-01 00:00:00', ['session_id' => 9]);

        $this->assertSame([], $this->helper->getNewContentToolsForCourse($u, $this->course(), 0));
        $this->assertSame(['documents:1'], $this->keys($this->helper->getNewContentToolsForCourse($u, $this->course(), 9)));
    }

    public function testStudentInfoReportsNewContentOutsideTheCacheAndHonoursTheFlags(): void
    {
        $u = $this->rawUser('learner');
        $this->access($u, 'document', '2020-02-01 00:00:00');
        $this->assertFalse($this->helper->getStudentInfoForCourse($u, $this->course())['hasNewContent']);

        $this->link($this->fileNodes[0], '2020-06-01 00:00:00');
        $info = $this->helper->getStudentInfoForCourse($u, $this->course());
        $this->assertTrue($info['hasNewContent'], 'hasNewContent is refreshed even when the rest comes from the cache');
        $this->assertSame(0.0, (float) $info['progress']);
        $this->assertNull($info['score'], 'Score flag is off');

        self::getContainer()->get(SettingsManager::class)->updateSetting('course.course_student_info', '{}');
        $empty = $this->helper->getStudentInfoForCourse($u, $this->course());
        $this->assertFalse($empty['hasNewContent'], 'No flag enabled: nothing is computed');
        $this->assertFalse($empty['completed']);
    }

    public function testBatchReportsNewContentPerCourse(): void
    {
        $u = $this->rawUser('learner');
        $this->access($u, 'document', '2020-02-01 00:00:00', 0, 1);
        $this->access($u, 'document', '2020-02-01 00:00:00', 0, 2);
        $this->link($this->fileNodes[0], '2020-06-01 00:00:00', ['c_id' => 1]);
        $this->link($this->fileNodes[1], '2020-01-01 00:00:00', ['c_id' => 2]); // older than the access

        $out = $this->helper->getStudentInfoBatchForCourses($u, [1, 2, 1, 0, 99999]);

        $this->assertSame([1, 2], array_keys($out), 'Unknown and invalid ids are skipped, duplicates merged');
        $this->assertTrue($out['1']['hasNewContent']);
        $this->assertFalse($out['2']['hasNewContent']);
        $this->assertSame([], $this->helper->getStudentInfoBatchForCourses($u, []));
    }

    public function testBatchWithoutAnyAccessHasNoNewContent(): void
    {
        $u = $this->rawUser('learner');
        $this->link($this->fileNodes[0], '2020-06-01 00:00:00');

        $this->assertFalse($this->helper->getStudentInfoBatchForCourses($u, [1])['1']['hasNewContent']);
    }

    public function testContentChangedExactlyAtTheLastAccessIsNotNew(): void
    {
        $u = $this->rawUser('learner');
        $this->access($u, 'document', '2020-06-01 00:00:00');
        $this->link($this->fileNodes[0], '2020-06-01 00:00:00');

        $this->assertSame([], $this->helper->getNewContentToolsForCourse($u, $this->course()));
        $this->assertFalse($this->helper->getStudentInfoForCourse($u, $this->course())['hasNewContent']);
        $this->assertFalse($this->helper->getStudentInfoBatchForCourses($u, [1])[1]['hasNewContent']);
    }

    public function testBatchIgnoresHiddenGroupAndOtherLearnerContent(): void
    {
        $u = $this->rawUser('learner');
        $other = $this->rawUser('other');
        $this->access($u, 'document', '2020-02-01 00:00:00');
        foreach ([['visibility' => 0], ['visibility' => 1], ['group_id' => 4], ['usergroup_id' => 4], ['user_id' => $other], ['deleted_at' => '2020-07-01 00:00:00'], ['session_id' => 9]] as $over) {
            $this->link($this->fileNodes[0], '2020-06-01 00:00:00', $over);
        }

        $this->assertFalse($this->helper->getStudentInfoBatchForCourses($u, [1])[1]['hasNewContent']);
        $this->assertFalse($this->helper->getStudentInfoForCourse($u, $this->course())['hasNewContent']);

        $this->link($this->fileNodes[1], '2020-06-02 00:00:00', ['user_id' => $u]);
        $this->assertTrue($this->helper->getStudentInfoBatchForCourses($u, [1])[1]['hasNewContent']);
    }
}
