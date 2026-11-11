<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Service\AdminStatistics\AdminStatisticsMaintenanceActionService;
use Chamilo\CoreBundle\Service\AdminStatistics\AdminStatisticsMaintenanceQueryService;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Zombie users and duplicate users of the admin statistics maintenance reports, and the actions that run on them.
 * Users are "rp_" rows, logins are dated in 2001 so that the ceilings below only see the fixtures. The destructive
 * path (soft delete / merge) is only exercised through its guards: it goes through the resource-node repository,
 * which deadlocks against concurrent suites.
 */
final class AdminStatisticsMaintenanceTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    private AdminStatisticsMaintenanceQueryService $query;
    private AdminStatisticsMaintenanceActionService $action;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->query = self::getContainer()->get(AdminStatisticsMaintenanceQueryService::class);
        $this->action = self::getContainer()->get(AdminStatisticsMaintenanceActionService::class);
    }

    private function login(int $userId, string $date): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO track_e_login (login_user_id, login_date, user_ip, logout_date) VALUES (?, ?, '', ?)",
            [$userId, $date, $date]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function zombies(array $parameters = []): array
    {
        $this->fresh();

        return $this->query->getReport('zombies', $parameters + ['ceiling' => '2001-06-01', 'itemsPerPage' => 200]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function duplicates(array $parameters): array
    {
        $this->fresh();

        return $this->query->getReport('duplicated_users', $parameters)['table']['items'];
    }

    private function setNames(int $id, string $first, string $last, string $email): void
    {
        $this->conn()->executeStatement('UPDATE user SET firstname = ?, lastname = ?, email = ?, email_canonical = ? WHERE id = ?', [$first, $last, $email, $email, $id]);
    }

    // ---------------------------------------------------------- dispatch

    public function testOnlyTwoReportsAreSupportedAndOnlyDuplicatesAreExportable(): void
    {
        $this->assertTrue($this->query->supports('zombies'));
        $this->assertTrue($this->query->supports('duplicated_users'));
        $this->assertFalse($this->query->supports('users'));

        try {
            $this->query->getReport('users');
            $this->fail('unknown report');
        } catch (NotFoundHttpException) {
            $this->addToAssertionCount(1);
        }
        $this->expectException(BadRequestHttpException::class);
        $this->query->getExportData('zombies', []);
    }

    // ----------------------------------------------------------- zombies

    public function testZombiesAreUsersWhoseLatestLoginIsOlderThanTheCeiling(): void
    {
        $old = $this->rawUser('r2z1', 1);
        $this->login($old, '2001-01-01 10:00:00');
        $this->login($old, '2001-03-01 11:30:15');
        $returned = $this->rawUser('r2z2');
        $this->login($returned, '2001-01-05 10:00:00');
        $this->login($returned, '2001-07-01 10:00:00');
        $neverLoggedIn = $this->rawUser('r2z3');
        $deleted = $this->rawUser('r2z4', 5, -2);
        $this->login($deleted, '2001-01-01 10:00:00');
        $onTheCeiling = $this->rawUser('r2z5');
        $this->login($onTheCeiling, '2001-06-01 00:00:00');
        $justAfter = $this->rawUser('r2z6');
        $this->login($justAfter, '2001-06-01 00:00:01');

        $report = $this->zombies();

        $ids = array_column($report['table']['items'], 'id');
        sort($ids);
        $this->assertSame([$old, $onTheCeiling], $ids);
        $this->assertSame(2, $report['table']['totalItems']);
        $this->assertNotContains($returned, $ids, 'only the latest login counts');
        $this->assertNotContains($neverLoggedIn, $ids);
        $this->assertNotContains($deleted, $ids);
        $this->assertNotContains($justAfter, $ids);
        $row = array_values(array_filter($report['table']['items'], static fn (array $r): bool => $r['id'] === $old))[0];
        $this->assertSame('2001-03-01 11:30:15', $row['lastAccess']);
        $this->assertSame('Teacher', $row['profile']);
        $this->assertSame('2020-01-01 00:00:00', $row['registeredDate']);
        $this->assertSame('Firstr2z1', $row['firstname']);
        $this->assertSame(1, $row['active']);
        $this->assertSame('Yes', $row['activeLabel']);
        $this->assertSame('2001-06-01', $report['filters']['ceiling']);
    }

    public function testZombiesCanBeRestrictedToActiveAccounts(): void
    {
        $active = $this->rawUser('r2z7');
        $inactive = $this->rawUser('r2z8', 5, 0);
        $this->login($active, '2001-01-01 10:00:00');
        $this->login($inactive, '2001-01-01 10:00:00');

        $all = array_column($this->zombies()['table']['items'], 'id');
        $activeOnly = $this->zombies(['activeOnly' => true]);
        $snake = $this->zombies(['active_only' => '1']);

        sort($all);
        $this->assertSame([$active, $inactive], $all);
        $this->assertSame([$active], array_column($activeOnly['table']['items'], 'id'));
        $this->assertSame([$active], array_column($snake['table']['items'], 'id'));
        $inactiveRow = array_values(array_filter($this->zombies()['table']['items'], static fn (array $r): bool => $r['id'] === $inactive))[0];
        $this->assertSame('No', $inactiveRow['activeLabel']);
    }

    public function testZombiesSortByAnAllowedFieldAndPage(): void
    {
        $ids = [];
        foreach (['b', 'a', 'c', 'e', 'd', 'f'] as $i => $letter) {
            $id = $this->rawUser('r2z9'.$letter);
            $this->conn()->executeStatement('UPDATE user SET lastname = ? WHERE id = ?', ['r2sort_'.$letter, $id]);
            $this->login($id, '2001-01-0'.($i + 1).' 10:00:00');
            $ids[$letter] = $id;
        }

        $byLastname = $this->zombies(['sortField' => 'lastname', 'sortOrder' => 'ASC']);
        $this->assertSame(
            [$ids['a'], $ids['b'], $ids['c'], $ids['d'], $ids['e'], $ids['f']],
            array_column($byLastname['table']['items'], 'id')
        );
        $byAccess = $this->zombies(['sortField' => 'lastAccess', 'sortOrder' => 'DESC']);
        $this->assertSame(
            [$ids['f'], $ids['d'], $ids['e'], $ids['c'], $ids['a'], $ids['b']],
            array_column($byAccess['table']['items'], 'id')
        );
        $page2 = $this->zombies(['sortField' => 'lastname', 'sortOrder' => 'ASC', 'itemsPerPage' => 5, 'page' => 2]);
        $this->assertSame([$ids['f']], array_column($page2['table']['items'], 'id'));
        $this->assertSame(6, $page2['table']['totalItems']);
        $this->assertSame('ASC', $page2['filters']['sortOrder']);
        // The sort field and the page size are bounded: unknown values fall back, never reach the SQL.
        $bad = $this->zombies(['sortField' => 'id; DROP TABLE user', 'itemsPerPage' => 1]);
        $this->assertSame('firstname', $bad['filters']['sortField']);
        $this->assertSame(50, $bad['table']['itemsPerPage'], 'a page size below the minimum of 5 falls back to the default');
    }

    public function testZombiesAreConfinedToTheUsersOfTheCurrentUrl(): void
    {
        $other = $this->secondUrl();
        $mine = $this->rawUser('r2z10');
        $foreign = $this->rawUser('r2z11', 5, 1, $other);
        $this->login($mine, '2001-01-01 10:00:00');
        $this->login($foreign, '2001-01-01 10:00:00');

        $this->assertSame([$mine], array_column($this->zombies()['table']['items'], 'id'));
    }

    // -------------------------------------------------------- duplicates

    /**
     * The service as it behaves when the request comes in through the portal $currentUrlId and $userId is signed in
     * (the CLI always resolves the first URL, so the portal is pinned through a stub helper).
     */
    private function duplicatesFrom(int $currentUrlId, int $userId, array $parameters): array
    {
        $this->fresh();
        $current = $this->getEntityManager()->find(AccessUrl::class, $currentUrlId);
        $urlHelper = new readonly class($current) extends AccessUrlHelper {
            public function __construct(private ?AccessUrl $fixed) {}

            public function isMultiple(): bool
            {
                return true;
            }

            public function getCurrent(): ?AccessUrl
            {
                return $this->fixed;
            }
        };
        $user = $this->entityUser($userId);
        $storage = self::getContainer()->get('security.token_storage');
        $storage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
        $service = new AdminStatisticsMaintenanceQueryService(
            $this->getEntityManager(),
            $urlHelper,
            self::getContainer()->get(TranslatorInterface::class),
            self::getContainer()->get(UserHelper::class),
            self::getContainer()->get(AccessUrlScopeHelper::class),
        );

        return array_column($service->getReport('duplicated_users', $parameters)['table']['items'], 'id');
    }

    /**
     * A twin that lives on another, unrelated portal is not a duplicate of this portal: the report only compares
     * the users of the portal it is opened from.
     */
    public function testDuplicateReportIgnoresTwinsOfAnUnrelatedUrl(): void
    {
        $other = $this->secondUrl();
        $mine = $this->rawUser('r3d1');
        $foreign = $this->rawUser('r3d2', 5, 1, $other);
        $this->setNames($mine, 'Scope3-r3', 'Twin', 'r3d1@example.com');
        $this->setNames($foreign, 'Scope3-r3', 'Twin', 'r3d2@example.com');

        $this->assertSame([], array_values(array_intersect([$mine, $foreign], $this->duplicatesFrom(1, 1, ['dupMode' => 'name']))));
        $this->assertSame([], array_values(array_intersect([$mine, $foreign], $this->duplicatesFrom(1, 1, ['dupMode' => 'email']))));
    }

    /**
     * Owner rule: a global admin sees the duplicates across the portals they manage, but only from the interface of
     * the parent portal of that subtree (the portal they are registered in). From a child portal, only that portal.
     */
    public function testGlobalAdminSeesDuplicatesOfTheManagedSubtreeOnlyFromTheParentPortal(): void
    {
        $child = $this->secondUrl(true);
        $this->conn()->executeStatement('UPDATE access_url SET rgt = 4 WHERE id = 1');
        $onParent = $this->rawUser('r3d3');
        $onChild = $this->rawUser('r3d4', 5, 1, $child);
        $onChild2 = $this->rawUser('r3d5', 5, 1, $child);
        foreach ([$onParent, $onChild, $onChild2] as $i => $id) {
            $this->setNames($id, 'Tree3-r3', 'Twin', 'r3d'.($i + 3).'@example.com');
        }
        // A grandchild: the admin is registered in the root only, so from the middle portal it is not "their parent".
        $this->conn()->executeStatement(
            'INSERT INTO access_url (url, active, description, created_by, lft, rgt, lvl, parent_id, tree_root)
             VALUES (?, 1, \'\', 1, 3, 4, 2, ?, 1)',
            ['http://rp-grandchild.invalid/'.getmypid(), $child]
        );
        $grandchild = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement('UPDATE access_url SET rgt = 5 WHERE id = ?', [$child]);
        $this->conn()->executeStatement('UPDATE access_url SET rgt = 6 WHERE id = 1');
        $onGrandchild = $this->rawUser('r3d9', 5, 1, $grandchild);
        $this->setNames($onGrandchild, 'Tree3-r3', 'Twin', 'r3d9@example.com');
        $names = ['dupMode' => 'name'];

        $fromParent = $this->duplicatesFrom(1, 1, $names);
        $this->assertSame([$onParent, $onChild, $onChild2, $onGrandchild], array_values(array_intersect([$onParent, $onChild, $onChild2, $onGrandchild], $fromParent)));

        $fromChild = $this->duplicatesFrom($child, 1, $names);
        $this->assertSame([$onChild, $onChild2], array_values(array_intersect([$onParent, $onChild, $onChild2, $onGrandchild], $fromChild)), 'the middle portal only compares its own users: the admin is registered in the root, not here');
    }

    public function testAdministratorWithoutTheGlobalRoleNeverSeesTheSubtreeEvenFromTheParentPortal(): void
    {
        $child = $this->secondUrl(true);
        $this->conn()->executeStatement('UPDATE access_url SET rgt = 4 WHERE id = 1');
        $admin = $this->rawUser('r3d6');
        $this->conn()->executeStatement('UPDATE user SET roles = ? WHERE id = ?', ['["ROLE_ADMIN"]', $admin]);
        $onParent = $this->rawUser('r3d7');
        $onChild = $this->rawUser('r3d8', 5, 1, $child);
        $this->setNames($onParent, 'Plain3-r3', 'Twin', 'r3d7@example.com');
        $this->setNames($onChild, 'Plain3-r3', 'Twin', 'r3d8@example.com');

        $this->assertSame([], array_values(array_intersect([$onParent, $onChild], $this->duplicatesFrom(1, $admin, ['dupMode' => 'name']))));
    }

    public function testDuplicateNamesAreMatchedIgnoringCaseAndSpacesAndGroupedOldestFirst(): void
    {
        $a = $this->rawUser('r2d1');
        $b = $this->rawUser('r2d2');
        $c = $this->rawUser('r2d3');
        $single = $this->rawUser('r2d4');
        $deleted = $this->rawUser('r2d5', 5, -2);
        $this->setNames($a, 'Zoe-r2', 'Dupont', 'r2d1@example.com');
        $this->setNames($b, ' zoe-R2 ', 'DUPONT', 'r2d2@example.com');
        $this->setNames($c, 'Zoe-r2', 'Other', 'r2d3@example.com');
        $this->setNames($single, 'Solo-r2', 'Dupont', 'r2d4@example.com');
        $this->setNames($deleted, 'zoe-r2', 'dupont', 'r2d5@example.com');
        $this->conn()->executeStatement("UPDATE user SET created_at = '2019-01-01 00:00:00' WHERE id = ?", [$b]);
        $this->courseUser($a, 1);
        $this->courseUser($a, 2);
        $sid = $this->rawSession('r2d');
        $this->sessionUser($sid, $a, 0);

        $items = array_values(array_filter(
            $this->duplicates(['dupMode' => 'name']),
            static fn (array $r): bool => \in_array($r['id'], [$a, $b, $c, $single, $deleted], true)
        ));

        $this->assertSame([$b, $a], array_column($items, 'id'), 'the soft-deleted twin and the unique names are left out; oldest first');
        $this->assertSame(2, $items[1]['courses']);
        $this->assertSame(1, $items[1]['sessions']);
        $this->assertSame(0, $items[0]['courses']);
        $this->assertSame('Student', $items[0]['role']);
        $this->assertSame('Active', $items[0]['activeLabel']);
        $this->assertSame('/main/admin/user_information.php?user_id='.$b, $items[0]['detailsUrl']);
        $this->assertSame('2019-01-01 00:00:00', $items[0]['registrationDate']);
    }

    public function testDuplicateEmailsAreMatchedIgnoringCaseAndKeepEmptyOnesOut(): void
    {
        $a = $this->rawUser('r2e1');
        $b = $this->rawUser('r2e2');
        $emptyA = $this->rawUser('r2e3');
        $emptyB = $this->rawUser('r2e4');
        $this->setNames($a, 'A', 'A', 'Shared.R2@Example.com');
        $this->setNames($b, 'B', 'B', ' shared.r2@example.COM');
        $this->setNames($emptyA, 'C', 'C', '');
        $this->setNames($emptyB, 'D', 'D', '');
        $mine = [$a, $b, $emptyA, $emptyB];

        $items = array_values(array_filter(
            $this->duplicates(['dupMode' => 'email']),
            static fn (array $r): bool => \in_array($r['id'], $mine, true)
        ));

        $ids = array_column($items, 'id');
        sort($ids);
        $this->assertSame([$a, $b], $ids);
        $report = $this->query->getReport('duplicated_users', ['dupMode' => 'email']);
        $this->assertSame('email', $report['meta']['duplicateMode']);
        $labels = array_column($report['meta']['duplicateGroups'], 'label');
        $this->assertContains('Shared.R2@Example.com', $labels);
    }

    public function testDuplicateExtraFieldValuesAndAdditionalColumns(): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO extra_field (item_type, value_type, variable, display_text, field_order, visible_to_self, visible_to_others, changeable, filter, created_at, auto_remove)
             VALUES (1, 1, 'r2_dupfield', 'R2 Dup field', 1, 0, 0, 0, 0, '2020-01-01 00:00:00', 0)"
        );
        $fieldId = (int) $this->conn()->lastInsertId();
        $a = $this->rawUser('r2x1');
        $b = $this->rawUser('r2x2');
        $c = $this->rawUser('r2x3');
        foreach ([[$a, 'ABC-1'], [$b, 'abc-1 '], [$c, 'XYZ']] as [$uid, $value]) {
            $this->conn()->executeStatement(
                "INSERT INTO extra_field_values (field_id, field_value, item_id, created_at, updated_at) VALUES (?, ?, ?, '2020-01-01 00:00:00', '2020-01-01 00:00:00')",
                [$fieldId, $value, $uid]
            );
        }
        $this->fresh();

        $report = $this->query->getReport('duplicated_users', [
            'dupMode' => 'extra',
            'extraFieldId' => $fieldId,
            'additionalProfileFields' => [$fieldId, 999999999],
        ]);

        $ids = array_column($report['table']['items'], 'id');
        sort($ids);
        $this->assertSame([$a, $b], $ids);
        $this->assertSame('ABC-1', $report['table']['items'][array_search($a, array_column($report['table']['items'], 'id'), true)]['extra_'.$fieldId]);
        $this->assertContains('extra_'.$fieldId, array_column($report['table']['columns'], 'key'));
        $this->assertSame([$fieldId], $report['filters']['additionalProfileFields'], 'unknown extra fields are dropped');
        $this->assertContains(['value' => $fieldId, 'label' => 'R2 Dup field'], $report['filters']['extraFieldOptions']);
        // Without a (valid) extra field there is nothing to compare.
        $this->assertSame([], $this->query->getReport('duplicated_users', ['dupMode' => 'extra', 'extraFieldId' => 999999999])['table']['items']);
        // An unknown mode falls back to names.
        $this->assertSame('name', $this->query->getReport('duplicated_users', ['dupMode' => 'bogus'])['filters']['dupMode']);
    }

    public function testDuplicatesExportDropsTheActionsColumn(): void
    {
        $a = $this->rawUser('r2dx1');
        $b = $this->rawUser('r2dx2');
        $this->setNames($a, 'Exp-r2', 'Same', 'r2dx1@example.com');
        $this->setNames($b, 'Exp-r2', 'Same', 'r2dx2@example.com');
        $this->fresh();

        $export = $this->query->getExportData('duplicated_users', ['dupMode' => 'name']);

        $this->assertNotContains('actions', array_column($export['columns'], 'key'));
        $this->assertContains('activeLabel', array_column($export['columns'], 'key'));
        $this->assertContains($a, array_column($export['items'], 'id'));
        $this->assertContains($b, array_column($export['items'], 'id'));
    }

    // ----------------------------------------------------- zombie actions

    private function zombie(string $tag, int $active = 1): int
    {
        $id = $this->rawUser($tag, 5, $active);
        $this->login($id, '2001-01-01 10:00:00');

        return $id;
    }

    private function activeOf(int $id): int
    {
        return (int) $this->conn()->fetchOne('SELECT active FROM user WHERE id = ?', [$id]);
    }

    public function testDeactivatingZombiesOnlyTouchesTheMatchingOnesAndLogsTheEvent(): void
    {
        $zombie = $this->zombie('r2a1');
        $returned = $this->rawUser('r2a2');
        $this->login($returned, '2001-09-01 10:00:00');
        $deleted = $this->zombie('r2a3', -2);

        $result = $this->action->runZombieAction('deactivate', [$zombie, $returned, $deleted, -5, 0], '2001-06-01', false);

        $this->assertSame(['message' => 'Update successful', 'affectedCount' => 1], $result);
        $this->assertSame(0, $this->activeOf($zombie));
        $this->assertSame(1, $this->activeOf($returned), 'a user with a recent login is not a zombie');
        $this->assertSame(-2, $this->activeOf($deleted));
        $logged = $this->conn()->fetchOne("SELECT default_value FROM track_e_default WHERE default_event_type = 'user_disable' ORDER BY default_id DESC LIMIT 1");
        $this->assertSame((string) $zombie, $logged);
    }

    public function testActivatingZombiesReactivatesInactiveAccounts(): void
    {
        $inactive = $this->zombie('r2a4', 0);
        $result = $this->action->runZombieAction('activate', [$inactive], '2001-06-01', false);
        $this->assertSame(1, $result['affectedCount']);
        $this->assertSame(1, $this->activeOf($inactive));
        $this->assertSame((string) $inactive, $this->conn()->fetchOne("SELECT default_value FROM track_e_default WHERE default_event_type = 'user_enable' ORDER BY default_id DESC LIMIT 1"));
    }

    public function testZombieActionWithAnActiveOnlyFilterIgnoresInactiveIds(): void
    {
        $inactive = $this->zombie('r2a5', 0);
        $this->expectException(BadRequestHttpException::class);
        $this->action->runZombieAction('deactivate', [$inactive], '2001-06-01', true);
    }

    public function testZombieActionWithoutMatchingUserOrWithUnknownActionIsRejected(): void
    {
        $zombie = $this->zombie('r2a6');

        try {
            $this->action->runZombieAction('activate', [], '2001-06-01', false);
            $this->fail('no ids');
        } catch (BadRequestHttpException $e) {
            $this->assertSame('No matching zombie users were found.', $e->getMessage());
        }
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Unsupported zombie-user action.');
        $this->action->runZombieAction('explode', [$zombie], '2001-06-01', false);
    }

    public function testZombieActionIsConfinedToTheUsersOfTheCurrentUrl(): void
    {
        $other = $this->secondUrl();
        $foreign = $this->rawUser('r2a7', 5, 1, $other);
        $this->login($foreign, '2001-01-01 10:00:00');

        try {
            $this->action->runZombieAction('deactivate', [$foreign], '2001-06-01', false);
            $this->fail('foreign user must not be reachable');
        } catch (BadRequestHttpException) {
            $this->assertSame(1, $this->activeOf($foreign));
        }
    }

    public function testDeletingAZombieWhoIsTheOnlyTeacherOfACourseIsRefused(): void
    {
        $teacher = $this->zombie('r2a8');
        // Make him the one and only teacher of course 1 (rolled back with the test transaction).
        $this->conn()->executeStatement('DELETE FROM course_rel_user WHERE c_id = 1 AND status = 1');
        $this->courseUser($teacher, 1, 1);

        $result = $this->action->runZombieAction('delete', [$teacher], '2001-06-01', false);

        $this->assertSame(['message' => '', 'affectedCount' => 0], $result);
        $this->assertSame(1, $this->activeOf($teacher), 'the course would be left without teacher');
    }

    public function testDenyDeleteUsersSettingBlocksTheDeletionOfZombies(): void
    {
        $zombie = $this->zombie('r2a9');
        $_SERVER['DENY_DELETE_USERS'] = 'yes';

        try {
            $result = $this->action->runZombieAction('delete', [$zombie], '2001-06-01', false);
        } finally {
            unset($_SERVER['DENY_DELETE_USERS']);
        }
        $this->assertSame(['message' => '', 'affectedCount' => 0], $result);
        $this->assertSame(1, $this->activeOf($zombie));
    }

    // --------------------------------------------------- duplicate actions

    public function testDuplicateDeactivateAndActivateRequireAnotherUserWithTheSameValue(): void
    {
        $a = $this->rawUser('r2u1');
        $b = $this->rawUser('r2u2');
        $solo = $this->rawUser('r2u3');
        $this->setNames($a, 'Twin-r2', 'Pair', 'r2u1@example.com');
        $this->setNames($b, ' TWIN-r2', 'pair', 'r2u2@example.com');
        $this->setNames($solo, 'Unique-r2', 'Pair', 'r2u3@example.com');

        $off = $this->action->runDuplicateAction('deactivate', $a, 0, 'name', 0);
        $this->assertSame(['message' => 'User deactivated', 'affectedCount' => 1], $off);
        $this->assertSame(0, $this->activeOf($a));
        $this->assertSame(1, $this->activeOf($b));
        $on = $this->action->runDuplicateAction('activate', $a, 0, 'name', 0);
        $this->assertSame(['message' => 'User enabled', 'affectedCount' => 1], $on);
        $this->assertSame(1, $this->activeOf($a));

        try {
            $this->action->runDuplicateAction('deactivate', $solo, 0, 'name', 0);
            $this->fail('no twin');
        } catch (BadRequestHttpException $e) {
            $this->assertSame('No other duplicates found for this user.', $e->getMessage());
        }
        $this->assertSame(1, $this->activeOf($solo));
        // Same e-mail address: matched too, and the e-mail mode ignores names.
        $this->setNames($b, 'Other', 'Other', 'R2U1@example.com');
        $this->assertSame(1, $this->action->runDuplicateAction('deactivate', $b, 0, 'email', 0)['affectedCount']);
    }

    public function testDuplicateActionsValidateTheirArguments(): void
    {
        foreach ([['activate', 0, 0], ['unify', 1, 0], ['bogus', 1, 1]] as [$action, $user, $target]) {
            try {
                $this->action->runDuplicateAction($action, $user, $target, 'name', 0);
                $this->fail($action);
            } catch (BadRequestHttpException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testUnifyNeedsAGroupOfAtLeastTwoUsersOfTheCurrentUrl(): void
    {
        $other = $this->secondUrl();
        $a = $this->rawUser('r2m1');
        $foreign = $this->rawUser('r2m2', 5, 1, $other);
        $this->setNames($a, 'Merge-r2', 'Pair', 'r2m1@example.com');
        $this->setNames($foreign, 'Merge-r2', 'Pair', 'r2m2@example.com');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('No other duplicates found for this user.');
        $this->action->runDuplicateAction('unify', 0, $a, 'name', 0);
    }

    /**
     * Like every other maintenance action, enabling or disabling a duplicate is confined to the users of the current
     * access URL: an administrator of one portal must not switch the accounts of another portal on or off.
     */
    public function testDuplicateActivationIsConfinedToTheUsersOfTheCurrentUrl(): void
    {
        $other = $this->secondUrl();
        $foreign = $this->rawUser('r2m3', 5, 1, $other);
        $twin = $this->rawUser('r2m4', 5, 1, $other);
        $this->setNames($foreign, 'Scope-r2', 'Pair', 'r2m3@example.com');
        $this->setNames($twin, 'Scope-r2', 'Pair', 'r2m4@example.com');

        try {
            $this->action->runDuplicateAction('deactivate', $foreign, 0, 'name', 0);
            $this->fail('a user of another portal must not be reachable');
        } catch (BadRequestHttpException) {
            $this->assertSame(1, $this->activeOf($foreign));
        }
    }
}
