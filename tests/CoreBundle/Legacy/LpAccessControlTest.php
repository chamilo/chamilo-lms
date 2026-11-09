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
 * The legacy learning path pages (lp_controller.php, lp_add_category.php, lp_stats.php, lp.ajax.php) take learning
 * path, item and category ids from the request. The guard helpers of the learnpath class must prove the id belongs to
 * the CURRENT course (behavioural tests below); the procedural page guards (roles, tokens) are source-level pins,
 * weak by nature but they fail when a guard is removed. Fixtures: prepared inserts, tag "lc2_".
 */
final class LpAccessControlTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const COURSE_ID = 1;

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
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function node(string $title, int $type): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO resource_node (resource_type_id, title, slug, created_at, updated_at, public, uuid)
             VALUES (?, ?, ?, NOW(), NOW(), 0, ?)',
            [$type, $title, $title.'-'.uniqid(), Uuid::v4()->toBinary()]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function link(int $nodeId, int $courseId, int $type): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO resource_link (resource_node_id, c_id, session_id, visibility, display_order, resource_type_group, created_at, updated_at)
             VALUES (?, ?, NULL, 2, 0, ?, NOW(), NOW())',
            [$nodeId, $courseId, $type]
        );
    }

    private function otherCourse(): int
    {
        return (int) $this->conn()->fetchOne('SELECT MIN(id) FROM course WHERE id <> ?', [self::COURSE_ID]);
    }

    /**
     * @return array{int, int} lp id, root item id
     */
    private function lp(int $courseId): array
    {
        $this->conn()->executeStatement(
            "INSERT INTO c_lp (lp_type, title, path, force_commit, content_maker, content_license, js_lib, debug, theme,
                author, prerequisite, hide_toc_frame, seriousgame_mode, autolaunch, max_attempts, subscribe_users,
                created_on, modified_on)
             VALUES (1, 'lc2_lp', '', 0, '', '', '', 0, '', '', 0, 0, 0, 0, 0, 0, '2030-01-01 00:00:00', '2030-01-01 00:00:00')"
        );
        $lpId = (int) $this->conn()->lastInsertId();
        $node = $this->node('lc2_lp', 40);
        $this->conn()->executeStatement('UPDATE c_lp SET resource_node_id = ? WHERE iid = ?', [$node, $lpId]);
        $this->link($node, $courseId, 40);

        $em = $this->getEntityManager();
        $root = (new CLpItem())
            ->setTitle('root')
            ->setPath('root')
            ->setLp($em->find(CLp::class, $lpId))
            ->setItemType('root')
        ;
        $em->persist($root);
        $em->flush();
        $rootId = (int) $root->getIid();
        $em->clear();

        return [$lpId, $rootId];
    }

    private function category(int $courseId): int
    {
        $node = $this->node('lc2_cat', 41);
        $this->conn()->executeStatement('INSERT INTO c_lp_category (resource_node_id, title) VALUES (?, ?)', [$node, 'lc2_cat']);
        $id = (int) $this->conn()->lastInsertId();
        $this->link($node, $courseId, 41);

        return $id;
    }

    private function source(string $path): string
    {
        $code = file_get_contents(__DIR__.'/../../../'.$path);
        $this->assertNotFalse($code);

        return $code;
    }

    /**
     * The source of one `case 'name':` block of lp_controller.php (up to the next case at the same indentation).
     */
    private function caseBlock(string $case): string
    {
        $code = $this->source('public/main/lp/lp_controller.php');
        $code = substr($code, (int) strpos($code, "\$redirectTo = '';"));
        $start = strpos($code, "\n    case '".$case."':");
        $this->assertNotFalse($start, "case $case exists");
        $end = strpos($code, "\n    case '", $start + 5);
        $end = false === $end ? strpos($code, "\n    default:", $start) : $end;

        return substr($code, $start, $end - $start);
    }

    // ------------------------------------------------------------- behavioural guards

    public function testLpOfTheCurrentCourseIsAccepted(): void
    {
        [$lpId] = $this->lp(self::COURSE_ID);

        $this->assertTrue(learnpath::lpBelongsToCurrentCourse($lpId));
    }

    public function testLpOfAnotherCourseUnknownOrEmptyIdIsRejected(): void
    {
        [$foreign] = $this->lp($this->otherCourse());

        $this->assertFalse(learnpath::lpBelongsToCurrentCourse($foreign), 'linked to another course only');
        $this->assertFalse(learnpath::lpBelongsToCurrentCourse(999999999));
        $this->assertFalse(learnpath::lpBelongsToCurrentCourse(0));
        $this->assertFalse(learnpath::lpBelongsToCurrentCourse(-5));
    }

    public function testCategoryGuardFollowsTheCourseLink(): void
    {
        $own = $this->category(self::COURSE_ID);
        $foreign = $this->category($this->otherCourse());

        $this->assertTrue(learnpath::categoryBelongsToCurrentCourse($own));
        $this->assertFalse(learnpath::categoryBelongsToCurrentCourse($foreign));
        $this->assertFalse(learnpath::categoryBelongsToCurrentCourse(0));
        $this->assertFalse(learnpath::categoryBelongsToCurrentCourse(999999999));
    }

    public function testRenamingACategoryOfAnotherCourseChangesNothing(): void
    {
        $foreign = $this->category($this->otherCourse());
        $own = $this->category(self::COURSE_ID);

        learnpath::updateCategory(['id' => $foreign, 'name' => 'lc2_hijacked']);
        learnpath::updateCategory(['id' => $own, 'name' => 'lc2_renamed']);

        $this->assertSame('lc2_cat', $this->conn()->fetchOne('SELECT title FROM c_lp_category WHERE iid = ?', [$foreign]));
        $this->assertSame('lc2_renamed', $this->conn()->fetchOne('SELECT title FROM c_lp_category WHERE iid = ?', [$own]));
    }

    public function testItemGuardRequiresTheItemToBelongToThatPath(): void
    {
        [$lpA, $rootA] = $this->lp(self::COURSE_ID);
        [$lpB] = $this->lp(self::COURSE_ID);

        $this->assertTrue(learnpath::lpItemBelongsToLp($rootA, $lpA));
        $this->assertFalse(learnpath::lpItemBelongsToLp($rootA, $lpB), 'an item id of another path of the same course');
        $this->assertFalse(learnpath::lpItemBelongsToLp(0, $lpA));
        $this->assertFalse(learnpath::lpItemBelongsToLp($rootA, 0));
        $this->assertFalse(learnpath::lpItemBelongsToLp(999999999, $lpA));
    }

    // ------------------------------------------------------------- source-level pins of the page guards

    /**
     * @return array<string, array{string}>
     */
    public static function stateChangingActions(): array
    {
        $names = [
            'delete', 'delete_lp_category', 'move_up_category', 'move_down_category', 'toggle_category_visibility',
            'toggle_visible', 'toggle_category_publish', 'toggle_publish', 'move_lp_up', 'move_lp_down', 'auto_launch',
            'copy', 'toggle_seriousgame', 'switch_force_commit', 'switch_attempt_mode', 'switch_scorm_debug',
            'switch_view_mode', 'set_previous_step_as_prerequisite', 'clear_prerequisites', 'dissociate_forum',
        ];

        return array_combine($names, array_map(static fn (string $n): array => [$n], $names));
    }

    /**
     * Legacy pages are outside CsrfProtectionListener and these actions are plain GET links: they must carry the
     * session token (checked, never regenerated, so the player and the other tabs keep working).
     *
     * @dataProvider stateChangingActions
     */
    public function testStateChangingControllerActionChecksTheToken(string $case): void
    {
        $this->assertStringContainsString('$requireToken()', $this->caseBlock($case), "$case must check the token");
    }

    /**
     * @dataProvider stateChangingActions
     */
    public function testStateChangingControllerActionIsTeacherOnly(string $case): void
    {
        $this->assertStringContainsString('$is_allowed_to_edit', $this->caseBlock($case), "$case must be teacher only");
    }

    public function testThePlayerActionsStayTokenFree(): void
    {
        // Learners use these through the player: a token requirement would break them.
        foreach (['view', 'content', 'save', 'next', 'previous', 'first', 'last', 'restart', 'stats'] as $case) {
            $this->assertStringNotContainsString('$requireToken()', $this->caseBlock($case), $case);
        }
    }

    public function testTheTokenCheckDoesNotRegenerateTheToken(): void
    {
        $code = $this->source('public/main/lp/lp_controller.php');
        $this->assertStringContainsString('Security::check_token(\'request\')', $code);
        $this->assertStringNotContainsString('Security::get_token(', $code, 'get_token() rotates the token on every request');
    }

    public function testPrerequisiteLinksCarryTheToken(): void
    {
        $code = $this->source('public/main/lp/learnpath.class.php');
        $this->assertSame(2, substr_count($code, "'sec_token' => Security::get_existing_token()"));
    }

    public function testAudioActionChecksTheItemBelongsToThePath(): void
    {
        $this->assertStringContainsString('lpItemBelongsToLp', $this->caseBlock('add_audio'));
    }

    public function testStatsOfAnotherUserAreForTeachersOnly(): void
    {
        $code = $this->source('public/main/lp/lp_stats.php');
        $this->assertMatchesRegularExpression('/api_get_user_id\(\)\s*!==\s*\$userId/', $code);
        $this->assertStringContainsString('api_is_allowed_to_edit', $code);
        $this->assertStringContainsString('api_not_allowed', $code);
    }

    public function testCategoryFormOnlyReadsCategoriesOfTheCurrentCourse(): void
    {
        $this->assertStringContainsString('categoryBelongsToCurrentCourse', $this->source('public/main/lp/lp_add_category.php'));
    }

    public function testAiCreationEndpointIsTeacherOnlyAndBoundToTheCurrentCourse(): void
    {
        $code = $this->source('public/main/inc/ajax/lp.ajax.php');
        $block = substr($code, (int) strpos($code, "case 'add_lp_ai':"), 900);
        $this->assertStringContainsString('api_is_allowed_to_edit', $block);
        $this->assertStringContainsString('getCode()', $block, 'course_code must be the current course');
    }

    public function testMoveOfAnotherCoursePathsIsRefusedByTheControllerLookup(): void
    {
        // Pre-existing guard (89b1e7aeb04): the controller ignores a lp_id not linked to the current course.
        $code = $this->source('public/main/lp/lp_controller.php');
        $this->assertStringContainsString('getFirstResourceLinkFromCourseSession($course)', $code);
    }

    public function testRequestValuesAreNotEchoedRawInTheSearchLinks(): void
    {
        $code = $this->source('public/main/lp/lp_list_search.php');
        $this->assertStringNotContainsString("\$_REQUEST['query'], \$get_params", $code);
    }
}
