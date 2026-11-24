<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use learnpath;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\Uuid;

/**
 * Characterization of the learnpath methods that the other Learnpath*CharacterizationTest files never execute:
 * displayItemPrerequisitesForm() and the stored-ref read of getFinalItemForm().
 *
 * Every title starts with "lp_t2_" (sibling files run against the same database).
 */
final class LearnpathTier2CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const COURSE_ID = 1;
    private const USER_ID = 1;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/lp/lp_controller.php');
        $request->setSession($session);
        Container::setRequest($request);
        $session->set('_real_cid', self::COURSE_ID);
        $session->set('_cid', 'TEMP');
        $session->set('_course', api_get_course_info_by_id(self::COURSE_ID));
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function newLpRow(string $title, int $type = 1): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_lp (lp_type, title, path, force_commit, content_maker, content_license, js_lib, debug, theme,
                author, prerequisite, hide_toc_frame, seriousgame_mode, autolaunch, max_attempts, subscribe_users,
                created_on, modified_on)
             VALUES (?, ?, '', 0, '', '', '', 0, '', '', 0, 0, 0, 0, 0, 0, '2030-01-01 00:00:00', '2030-01-01 00:00:00')",
            [$type, $title]
        );
        $lpId = (int) $this->conn()->lastInsertId();

        $em = $this->getEntityManager();
        $root = (new CLpItem())
            ->setTitle('root')
            ->setPath('root')
            ->setLp($em->find(CLp::class, $lpId))
            ->setItemType('root')
        ;
        $em->persist($root);
        $em->flush();
        $em->clear();

        return $lpId;
    }

    private function lp(int $lpId, int $userId = self::USER_ID): learnpath
    {
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(CLp::class, $lpId);

        return new learnpath($entity, api_get_course_info_by_id(self::COURSE_ID), $userId);
    }

    private function rootId(int $lpId): int
    {
        return (int) $this->conn()->fetchOne("SELECT iid FROM c_lp_item WHERE lp_id = ? AND path = 'root'", [$lpId]);
    }

    private function addItem(int $lpId, string $title, string $type, int $order, int $path = 0): int
    {
        $lp = $this->lp($lpId);
        $parent = $this->getEntityManager()->find(CLpItem::class, $this->rootId($lpId));
        $id = (int) $lp->add_item($parent, 0, $type, $path, $title);
        $this->conn()->executeStatement('UPDATE c_lp_item SET display_order = ? WHERE iid = ?', [$order, $id]);
        $this->getEntityManager()->clear();

        return $id;
    }

    // ---------------------------------------------------------------- displayItemPrerequisitesForm

    public function testPrerequisitesFormShowsTheStoredScoresOfTheCurrentItemPrerequisite(): void
    {
        $lpId = $this->newLpRow('lp_t2_prereq_form');
        $scored = $this->addItem($lpId, 'lp_t2_prereq_scored', 'hotpotatoes', 1, 0);
        $other = $this->addItem($lpId, 'lp_t2_prereq_other', 'hotpotatoes', 2, 0);
        $current = $this->addItem($lpId, 'lp_t2_prereq_current', 'document', 3, 0);
        $this->conn()->executeStatement(
            'UPDATE c_lp_item SET prerequisite = ?, prerequisite_min_score = 30, prerequisite_max_score = 80 WHERE iid = ?',
            [(string) $scored, $current]
        );
        // Scores stored on an item that is not the current one must not leak into the form.
        $this->conn()->executeStatement(
            'UPDATE c_lp_item SET prerequisite = ?, prerequisite_min_score = 11, prerequisite_max_score = 22 WHERE iid = ?',
            [(string) $other, $scored]
        );

        $lp = $this->lp($lpId);
        $item = $this->getEntityManager()->find(CLpItem::class, $current);
        $html = $lp->displayItemPrerequisitesForm($item);

        self::assertStringContainsString('lp_t2_prereq_current', $html);
        self::assertMatchesRegularExpression('/name="min_'.$scored.'"[^>]*value="30"/', $html);
        self::assertMatchesRegularExpression('/name="max_'.$scored.'"[^>]*value="80"/', $html);
        self::assertMatchesRegularExpression('/name="min_'.$other.'"[^>]*value="0"/', $html);
        self::assertMatchesRegularExpression('/value="'.$scored.'"[^>]*\/>/s', $html);
        self::assertDoesNotMatchRegularExpression('/id="id'.$current.'"/', $html, 'The current item is not offered as its own prerequisite.');
        $checkedPos = strpos($html, 'checked="checked"', strpos($html, 'id="id'.$scored.'"') - 120);
        self::assertNotFalse($checkedPos);
    }

    public function testPrerequisitesFormChecksNoneWhenTheItemHasNoPrerequisite(): void
    {
        $lpId = $this->newLpRow('lp_t2_prereq_none');
        $this->addItem($lpId, 'lp_t2_prereq_first', 'document', 1, 0);
        $current = $this->addItem($lpId, 'lp_t2_prereq_second', 'document', 2, 0);

        $lp = $this->lp($lpId);
        $html = $lp->displayItemPrerequisitesForm($this->getEntityManager()->find(CLpItem::class, $current));

        self::assertMatchesRegularExpression('/checked="checked"\s+id="idnone"/', $html);
    }

    // ---------------------------------------------------------------- getFinalItemForm

    public function testFinalItemFormDefaultsTheRefToTheValueStoredOnTheFinalItem(): void
    {
        $lpId = $this->newLpRow('lp_t2_final_form');
        $finalItem = $this->addItem($lpId, 'lp_t2_final', 'final_item', 1, 0);
        $this->conn()->executeStatement('UPDATE c_lp_item SET ref = ? WHERE iid = ?', ['7', $finalItem]);

        $html = $this->lp($lpId)->getFinalItemForm();

        self::assertMatchesRegularExpression('/name="final_item_ref"[^>]*value="7"|value="7"[^>]*name="final_item_ref"/', $html);

        $this->conn()->executeStatement('UPDATE c_lp_item SET ref = ? WHERE iid = ?', ['', $finalItem]);
        $html = $this->lp($lpId)->getFinalItemForm();

        self::assertDoesNotMatchRegularExpression('/name="final_item_ref"[^>]*value="7"|value="7"[^>]*name="final_item_ref"/', $html);
    }

    // ---------------------------------------------------------------- ordering / sums left unpinned elsewhere

    public function testPreviousItemIdIsTheLastItemAndTheNewestOneOnATie(): void
    {
        $lpId = $this->newLpRow('lp_t2_previous');
        $this->addItem($lpId, 'lp_t2_prev_first', 'document', 5, 0);
        $second = $this->addItem($lpId, 'lp_t2_prev_second', 'document', 5, 0);
        $this->addItem($lpId, 'lp_t2_prev_lower', 'document', 1, 0);

        self::assertSame($second, (int) $this->lp($lpId)->select_previous_item_id());
    }

    public function testJsDropdownListsTheItemsOfTheLearningPathByAscendingId(): void
    {
        $lpId = $this->newLpRow('lp_t2_js_dropdown');
        $a = $this->addItem($lpId, 'lp_t2_js_a', 'document', 3, 0);
        $b = $this->addItem($lpId, 'lp_t2_js_b', 'document', 2, 0);
        $c = $this->addItem($lpId, 'lp_t2_js_c', 'document', 1, 0);

        $js = $this->lp($lpId)->get_js_dropdown_array();

        $posA = strpos($js, 'child_name['.$a.'] = new Array();');
        $posB = strpos($js, 'child_name['.$b.'] = new Array();');
        $posC = strpos($js, 'child_name['.$c.'] = new Array();');
        self::assertNotFalse($posA);
        self::assertLessThan($posB, $posA);
        self::assertLessThan($posC, $posB);
    }

    public function testAccumulatedWorkTimeTotalSumsTheLearningPathsLinkedToTheCourse(): void
    {
        $expected = (int) $this->conn()->fetchOne(
            'SELECT COALESCE(SUM(lp.accumulate_work_time), 0) FROM c_lp lp WHERE EXISTS (
                SELECT 1 FROM resource_link rl WHERE rl.resource_node_id = lp.resource_node_id AND rl.c_id = ? AND rl.deleted_at IS NULL
            )',
            [self::COURSE_ID]
        );

        $lpId = $this->newLpRow('lp_t2_work_time');
        $this->conn()->executeStatement(
            'INSERT INTO resource_node (resource_type_id, title, slug, created_at, updated_at, public, uuid)
             VALUES (40, ?, ?, NOW(), NOW(), 0, ?)',
            ['lp_t2_work_time', 'lp_t2_work_time-'.uniqid(), Uuid::v4()->toBinary()]
        );
        $nodeId = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('UPDATE c_lp SET resource_node_id = ?, accumulate_work_time = 15 WHERE iid = ?', [$nodeId, $lpId]);
        $this->conn()->executeStatement(
            'INSERT INTO resource_link (resource_node_id, c_id, session_id, visibility, display_order, resource_type_group, created_at, updated_at)
             VALUES (?, ?, NULL, 2, 0, (SELECT resource_type_id FROM resource_node WHERE id = ?), NOW(), NOW())',
            [$nodeId, self::COURSE_ID, $nodeId]
        );

        self::assertSame($expected + 15, learnpath::getAccumulateWorkTimeTotal(self::COURSE_ID));
        self::assertSame(15, (int) learnpath::getAccumulateWorkTimePrerequisite($lpId, self::COURSE_ID));
    }
}
