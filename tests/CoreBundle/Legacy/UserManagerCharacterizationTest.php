<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use UserManager;

/**
 * Characterization tests for the raw-SQL methods of the legacy UserManager.
 *
 * They pin what the methods return today (row shape, ordering, NULL/false
 * conventions, DB side effects) so that a rewrite of the SQL to DQL which
 * changes any of it fails here. Every fixture is tagged "um_" because sibling
 * test files write to the same database.
 */
class UserManagerCharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $request = new Request();
        $request->setSession(new HttpSession(new MockArraySessionStorage()));
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        Database::setManager($this->getEntityManager());
    }

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function row(string $sql, array $params = []): array|false
    {
        return $this->conn()->fetchAssociative($sql, $params);
    }

    private function names(int $id, string $first, string $last, string $official = '', ?int $status = null): void
    {
        $this->conn()->executeStatement(
            'UPDATE user SET firstname = ?, lastname = ?, official_code = ? WHERE id = ?',
            [$first, $last, $official, $id]
        );
        if (null !== $status) {
            $this->conn()->executeStatement('UPDATE user SET status = ? WHERE id = ?', [$status, $id]);
        }
    }

    private function createExtraField(string $variable, int $valueType = 1, ?string $default = null): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO extra_field (item_type, value_type, variable, display_text, default_value, field_order,
                visible_to_self, visible_to_others, changeable, filter, created_at, auto_remove)
             VALUES (1, ?, ?, ?, ?, 1, 1, 1, 1, 0, NOW(), 0)',
            [$valueType, $variable, $variable, $default]
        );

        return (int) $this->conn()->lastInsertId();
    }

    /**
     * Raw-SQL course (no resource node): the ORM path through createCourse() proved flaky when sibling suites
     * run against the same database, and these tests only need a row in `course` to attach links to.
     */
    private function createRawCourse(string $code): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO course (language_id, code, visibility, video_url, sticky, creation_date, subscribe, unsubscribe)
             SELECT language_id, ?, 1, \'\', 0, NOW(), 1, 0 FROM course ORDER BY id LIMIT 1',
            [$code]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function createRawSession(string $title): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status)
             VALUES (?, 0, 0, 0, 1, 0)',
            [$title]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function setExtraValue(int $fieldId, int $userId, string $value): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO extra_field_values (field_id, field_value, item_id, created_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())',
            [$fieldId, $value, $userId]
        );
    }

    // ---------------------------------------------------------------- lookups

    public function testGetUserIdFromUsernameIsExactAndTrimmed(): void
    {
        $id = $this->createUser('um_lookup')->getId();

        $this->assertEquals($id, UserManager::get_user_id_from_username('um_lookup'));
        $this->assertEquals($id, UserManager::get_user_id_from_username("  um_lookup \n"), 'Input is trimmed.');
        $this->assertFalse(UserManager::get_user_id_from_username('um_lookup_nope'), 'Unknown user gives false, not 0/null.');
        $this->assertFalse(UserManager::get_user_id_from_username(''));
        $this->assertFalse(UserManager::get_user_id_from_username('um_look'), 'No prefix matching.');
    }

    public function testGetUserIdFromUsernameIsCaseInsensitiveUnderCollation(): void
    {
        $id = $this->createUser('um_casey')->getId();

        // utf8mb4_unicode_ci makes the equality comparison case-insensitive: a DQL rewrite must keep that.
        $this->assertEquals($id, UserManager::get_user_id_from_username('UM_CASEY'));
    }

    public function testGetUserListByIdsFiltersOrdersAndLimits(): void
    {
        $a = $this->createUser('um_ids_a')->getId();
        $b = $this->createUser('um_ids_b')->getId();
        $c = $this->createUser('um_ids_c')->getId();
        $this->conn()->executeStatement('UPDATE user SET active = 0 WHERE id = ?', [$b]);

        $this->assertSame([], UserManager::get_user_list_by_ids([]));
        $this->assertSame([], UserManager::get_user_list_by_ids(null));

        $all = UserManager::get_user_list_by_ids([$a, $b, $c], null, 'username DESC');
        $this->assertSame(['um_ids_c', 'um_ids_b', 'um_ids_a'], array_column($all, 'username'));
        $this->assertArrayHasKey('firstname', $all[0]);
        $this->assertArrayHasKey(0, $all[0], 'fetch_array returns both numeric and associative keys.');

        $active = UserManager::get_user_list_by_ids([$a, $b, $c], true, 'username');
        $this->assertSame(['um_ids_a', 'um_ids_c'], array_column($active, 'username'));

        $inactive = UserManager::get_user_list_by_ids([$a, $b, $c], false);
        $this->assertSame(['um_ids_b'], array_column($inactive, 'username'));

        $limited = UserManager::get_user_list_by_ids([$a, $b, $c], null, 'username', '1,1');
        $this->assertSame(['um_ids_b'], array_column($limited, 'username'), 'LIMIT offset,count is honoured.');

        $single = UserManager::get_user_list_by_ids($a);
        $this->assertCount(1, $single, 'A scalar id is accepted.');
    }

    public function testGetUserListByConditionsOrderAndComplete_name(): void
    {
        $a = $this->createUser('um_gl_a')->getId();
        $b = $this->createUser('um_gl_b')->getId();
        $this->names($a, 'Zed', 'UmGl', 'um_code_gl');
        $this->names($b, 'Amy', 'UmGl', 'um_code_gl');

        $list = UserManager::get_user_list(['official_code' => 'um_code_gl'], ['firstname']);

        $this->assertSame(['Amy', 'Zed'], array_column($list, 'firstname'));
        $this->assertEquals($b, $list[0]['user_id'], 'The id is exposed twice: id and user_id.');
        $this->assertEquals($b, $list[0]['id']);
        $this->assertSame('Amy UmGl', $list[0]['complete_name'], 'complete_name follows the platform name order.');

        $page = UserManager::get_user_list(['official_code' => 'um_code_gl'], ['firstname DESC'], 0, 1);
        $this->assertCount(1, $page);
        $this->assertSame('Zed', $page[0]['firstname']);

        $this->assertSame([], UserManager::get_user_list(['official_code' => 'um_no_such_code']));
    }

    public function testGetUserListExtraConditionsExcludesAnonymousAndCounts(): void
    {
        $a = $this->createUser('um_xc_a')->getId();
        $b = $this->createUser('um_xc_b')->getId();
        $c = $this->createUser('um_xc_c')->getId();
        $this->names($a, 'A', 'B', 'um_code_xc');
        $this->names($b, 'A', 'B', 'um_code_xc');
        // 6 is ANONYMOUS: the method silently drops them.
        $this->names($c, 'A', 'B', 'um_code_xc', 6);

        $rows = UserManager::getUserListExtraConditions(['official_code' => 'um_code_xc'], ['username']);
        $this->assertSame(['um_xc_a', 'um_xc_b'], array_column($rows, 'username'));

        $count = UserManager::getUserListExtraConditions(['official_code' => 'um_code_xc'], [], false, false, null, '', true);
        $this->assertEquals(2, $count);

        $extra = UserManager::getUserListExtraConditions(
            ['official_code' => 'um_code_xc'],
            [],
            false,
            false,
            null,
            " AND username = 'um_xc_b'"
        );
        $this->assertSame(['um_xc_b'], array_column($extra, 'username'), 'extraConditions is appended as raw SQL.');
    }

    public function testGetUserListLikeSimpleAndContainsModes(): void
    {
        $a = $this->createUser('um_lk_alpha')->getId();
        $b = $this->createUser('um_lk_beta')->getId();
        $this->names($a, 'Alpha', 'Umlk', 'um_lk_code1');
        $this->names($b, 'Beta', 'Umlk', 'um_lk_code2');

        // Contains mode: %value%
        $r = UserManager::getUserListLike(['user.lastname' => 'mlk'], ['user.username']);
        $this->assertSame(['um_lk_alpha', 'um_lk_beta'], array_column($r, 'username'));
        $this->assertSame(
            ['id', 'username', 'firstname', 'lastname', 'official_code', 'status'],
            array_values(array_unique(array_map(static fn ($k) => $k, array_filter(array_keys($r[0]), 'is_string'))))
        );

        // Simple mode: value%, so a mid-string match no longer works.
        $this->assertSame([], UserManager::getUserListLike(['user.lastname' => 'mlk'], [], true));
        $this->assertCount(2, UserManager::getUserListLike(['user.lastname' => 'Umlk'], [], true));

        // id and status are compared with = instead of LIKE.
        $byId = UserManager::getUserListLike(['user.id' => (string) $a]);
        $this->assertSame(['um_lk_alpha'], array_column($byId, 'username'));

        // OR combination
        $or = UserManager::getUserListLike(
            ['user.firstname' => 'Alpha', 'user.official_code' => 'um_lk_code2'],
            ['user.username'],
            false,
            'OR'
        );
        $this->assertSame(['um_lk_alpha', 'um_lk_beta'], array_column($or, 'username'));

        $and = UserManager::getUserListLike(
            ['user.firstname' => 'Alpha', 'user.official_code' => 'um_lk_code2'],
            [],
            false,
            'AND'
        );
        $this->assertSame([], $and);

        // limit / offset / restriction list
        $paged = UserManager::getUserListLike(['user.lastname' => 'Umlk'], ['user.username'], false, 'AND', [], 1, 1);
        $this->assertSame(['um_lk_beta'], array_column($paged, 'username'));
        $only = UserManager::getUserListLike(['user.lastname' => 'Umlk'], [], false, 'AND', [$b]);
        $this->assertSame(['um_lk_beta'], array_column($only, 'username'));
    }

    public function testGetUsersByNameMatchesPrefixesAndReturnsObjects(): void
    {
        $a = $this->createUser('um_nm_a')->getId();
        $b = $this->createUser('um_nm_b')->getId();
        $this->names($a, 'Umfirst', 'Umlast');
        $this->names($b, 'Umfirstly', 'Other');

        $rows = UserManager::getUsersByName('Umfirst', 'Umlast');
        $this->assertCount(1, $rows);
        $this->assertIsObject($rows[0]);
        $this->assertEquals($a, $rows[0]->id);
        $this->assertSame('um_nm_a', $rows[0]->username);

        $prefix = UserManager::getUsersByName('Umfirst', '');
        $this->assertCount(2, $prefix, 'Both names are matched as "prefix%".');
        $this->assertSame([], UserManager::getUsersByName('Umfirst', 'last'));
    }

    public function testOfficialCodeLookups(): void
    {
        $a = $this->createUser('um_oc_a')->getId();
        $b = $this->createUser('um_oc_b')->getId();
        $c = $this->createUser('um_oc_c')->getId();
        $this->names($a, 'A', 'A', 'um_OC1');
        $this->names($b, 'B', 'B', 'um_OC1');
        $this->names($c, 'C', 'C', 'um_OC2');

        $ids = UserManager::getUsersByOfficialCode('um_OC1');
        sort($ids);
        $this->assertEquals([$a, $b], $ids);
        $this->assertSame([], UserManager::getUsersByOfficialCode('um_OC_none'));

        $grouped = UserManager::getOfficialCodeGrouped();
        $this->assertSame('um_OC1', $grouped['um_OC1'], 'Result is keyed by the code itself, one entry per distinct code.');
        $this->assertSame('um_OC2', $grouped['um_OC2']);
        $this->assertSame(count($grouped), count(array_unique(array_keys($grouped))));
    }

    public function testGetNumberOfUsersCountsByStatusAndActive(): void
    {
        $before = UserManager::get_number_of_users(5, 1, 0);
        $beforeAll = UserManager::get_number_of_users(0, 1, null);
        $u = $this->createUser('um_cnt')->getId();
        $this->names($u, 'C', 'C', '', 5);
        $this->conn()->executeStatement('UPDATE user SET active = 0 WHERE id = ?', [$u]);

        $this->assertSame($before + 1, UserManager::get_number_of_users(5, 1, 0), 'Inactive status-5 user is counted.');
        $this->assertSame($beforeAll + 1, UserManager::get_number_of_users(0, 1, null));
        $this->assertIsInt(UserManager::get_number_of_users());

        $activeBefore = UserManager::get_number_of_users(5, 1, 1);
        $this->conn()->executeStatement('UPDATE user SET active = 1 WHERE id = ?', [$u]);
        $this->assertSame($activeBefore + 1, UserManager::get_number_of_users(5, 1, 1));

        // Date filters: created_at is "now", so a window in the past excludes it, a window around today includes it.
        $this->conn()->executeStatement('UPDATE user SET created_at = ? WHERE id = ?', ['2001-05-05 12:00:00', $u]);
        $this->assertSame(1, UserManager::get_number_of_users(5, 1, null, '2001-05-05', '2001-05-05'));
        $this->assertSame(0, UserManager::get_number_of_users(5, 1, null, '2001-05-06', '2001-05-07'));
    }

    // ------------------------------------------------------------ active state

    public function testActivateDeactivateAndEnableDisable(): void
    {
        $a = $this->createUser('um_act_a')->getId();
        $b = $this->createUser('um_act_b')->getId();
        $active = fn (int $id) => (int) $this->conn()->fetchOne('SELECT active FROM user WHERE id = ?', [$id]);

        $this->assertFalse(UserManager::deactivate_users([]));
        $this->assertTrue(UserManager::deactivate_users([$a, $b]));
        $this->assertSame([0, 0], [$active($a), $active($b)]);

        $this->assertTrue(UserManager::activate_users([$a]));
        $this->assertSame([1, 0], [$active($a), $active($b)], 'Only the listed ids change.');
        $this->assertFalse(UserManager::activate_users([]));

        $this->assertTrue(UserManager::disable($a));
        $this->assertSame(0, $active($a));
        $this->assertTrue(UserManager::enable($a));
        $this->assertSame(1, $active($a));
        $this->assertFalse(UserManager::disable(0));
        $this->assertFalse(UserManager::enable(0));
    }

    public function testChangeActiveStateReturnsFalseForEmptyIdAndStoresValue(): void
    {
        $id = $this->createUser('um_cas')->getId();

        $this->assertFalse(UserManager::change_active_state(0, 1));
        UserManager::change_active_state($id, 0);
        $this->assertSame('0', (string) $this->conn()->fetchOne('SELECT active FROM user WHERE id = ?', [$id]));
        UserManager::change_active_state($id, -2);
        $this->assertSame('-2', (string) $this->conn()->fetchOne('SELECT active FROM user WHERE id = ?', [$id]), 'Any int is stored (soft-delete value).');
    }

    public function testDeleteUsersSoftDeletesEveryValidId(): void
    {
        $a = $this->createUser('um_dels_a')->getId();
        $b = $this->createUser('um_dels_b')->getId();

        $this->assertFalse(UserManager::delete_users([]));
        $this->assertTrue(UserManager::delete_users([$a, 0, -4, $b]));

        foreach ([$a, $b] as $id) {
            $this->assertSame(
                '-2',
                (string) $this->conn()->fetchOne('SELECT active FROM user WHERE id = ?', [$id]),
                'delete_user() without destroy flag is a soft delete (active = -2).'
            );
        }
    }

    // ---------------------------------------------------------------- API keys

    public function testApiKeyLifecycle(): void
    {
        $id = $this->createUser('um_api')->getId();

        $this->assertFalse(UserManager::get_api_keys($id), 'No key yet: false, not an empty array.');
        $this->assertFalse(UserManager::get_api_key_id($id, 'default'));

        $keyId = UserManager::add_api_key($id, 'um_svc');
        $this->assertIsInt($keyId);
        $this->assertGreaterThan(0, $keyId);

        $keys = UserManager::get_api_keys($id, 'um_svc');
        $this->assertSame([$keyId], array_keys($keys), 'Keys are indexed by row id.');
        $this->assertSame(64, strlen($keys[$keyId]));
        $this->assertSame([], array_diff(array_keys(UserManager::get_api_keys($id, 'um_svc')), [$keyId]));
        $this->assertFalse(UserManager::get_api_keys($id, 'other'), 'Service name scopes the lookup.');
        $this->assertEquals($keyId, UserManager::get_api_key_id($id, 'um_svc'));

        $row = $this->row('SELECT user_id, api_service FROM user_api_key WHERE id = ?', [$keyId]);
        $this->assertEquals($id, $row['user_id']);
        $this->assertSame('um_svc', $row['api_service']);

        $this->assertFalse(UserManager::delete_api_key(999999999), 'Unknown id: false.');
        $this->assertTrue(UserManager::delete_api_key($keyId));
        $this->assertFalse($this->row('SELECT id FROM user_api_key WHERE id = ?', [$keyId]));
    }

    public function testUpdateApiKeyRegeneratesOrCreates(): void
    {
        $id = $this->createUser('um_apiupd')->getId();

        $first = UserManager::update_api_key($id, 'um_upd');
        $this->assertGreaterThan(0, $first, 'No key yet: update creates one.');
        $firstValue = UserManager::get_api_keys($id, 'um_upd')[$first];

        $second = UserManager::update_api_key($id, 'um_upd');
        $this->assertNotEquals($first, $second, 'The old row is deleted and a new one inserted.');
        $keys = UserManager::get_api_keys($id, 'um_upd');
        $this->assertCount(1, $keys);
        $this->assertNotSame($firstValue, $keys[$second]);
    }

    public function testApiKeyFunctionsRejectNonNumericUserIds(): void
    {
        $this->assertFalse(UserManager::get_api_keys('abc'));
        $this->assertFalse(UserManager::add_api_key('1; DROP'));
        $this->assertFalse(UserManager::delete_api_key('x'));
        $this->assertFalse(UserManager::update_api_key('x', 'default'));
        $this->assertFalse(UserManager::get_api_key_id('x', 'default'));
        $this->assertFalse(UserManager::get_api_key_id(1, ''));
    }

    // -------------------------------------------------------------------- tags

    public function testTagsAddCountSearchAndDelete(): void
    {
        $u1 = $this->createUser('um_tag_u1')->getId();
        $u2 = $this->createUser('um_tag_u2')->getId();
        $field = $this->createExtraField('um_tags_field', 10);

        $this->assertSame(0, UserManager::get_tag_id('um_php', $field));
        $this->assertTrue(UserManager::add_tag('um_php', $u1, $field));
        $this->assertTrue(UserManager::add_tag('um_php', $u2, $field));
        $this->assertTrue(UserManager::add_tag('um_php', $u2, $field), 'Re-adding the same relation is idempotent for the link.');
        $this->assertTrue(UserManager::add_tag('um_phd', $u1, $field));

        $tagId = UserManager::get_tag_id('um_php', $field);
        $this->assertGreaterThan(0, $tagId);
        $this->assertEquals($tagId, UserManager::get_tag_id('UM_PHP', $field), 'Tag lookup is case-insensitive under the collation.');
        $this->assertSame(0, UserManager::get_tag_id('um_php', $field + 1000));
        $this->assertEquals($tagId, UserManager::get_tag_id_from_id($tagId, $field));
        $this->assertFalse(UserManager::get_tag_id_from_id($tagId, $field + 1000));

        // Relation rows: u2 was added twice but linked once.
        $this->assertSame(
            '1',
            (string) $this->conn()->fetchOne('SELECT COUNT(*) FROM user_rel_tag WHERE user_id = ? AND tag_id = ?', [$u2, $tagId])
        );
                $this->assertSame(
            '3',
            (string) $this->conn()->fetchOne('SELECT count FROM tag WHERE id = ?', [$tagId]),
            'Pinned: the first INSERT stores 1 (its "count + 1" is evaluated without a stored value), then every further add_tag() call adds 1, even when the user link already existed.'
        );

        $mine = UserManager::get_user_tags($u1, $field);
        $this->assertSame(['um_phd', 'um_php'], array_column($mine, 'tag'), 'Ordered by tag.');
        $this->assertSame(
            'um_phd, um_php',
            UserManager::get_user_tags_to_string($u1, $field, false)
        );
        $this->assertStringContainsString('<a href=', UserManager::get_user_tags_to_string($u1, $field, true));
        $this->assertSame('', UserManager::get_user_tags_to_string($u1, $field + 1000, false));

        $json = UserManager::get_tags('um_ph', $field);
        $this->assertSame(
            [['id' => 'um_phd', 'text' => 'um_phd'], ['id' => 'um_php', 'text' => 'um_php']],
            json_decode($json, true)
        );
        $this->assertSame([['id' => 'um_php', 'text' => 'um_php']], UserManager::get_tags('um_php', $field, 'array'));
        $this->assertSame([], UserManager::get_tags('zzz_none', $field, 'array'));
        $this->assertCount(1, UserManager::get_tags('um_ph', $field, 'array', 1), 'Limit is applied.');

        $top = UserManager::get_top_tags($field);
        $this->assertSame('um_php', $top[0]['tag'], 'Most used tag first.');
        $this->assertEquals(2, $top[0]['count']);
        $this->assertEquals(1, $top[1]['count']);

        UserManager::delete_user_tags($u1, $field);
        $this->assertSame([], UserManager::get_user_tags($u1, $field));
        $this->assertSame(
            '2',
            (string) $this->conn()->fetchOne('SELECT count FROM tag WHERE id = ?', [$tagId]),
            'Deleting a user\'s tags decrements the counter of each tag.'
        );
        $this->assertCount(1, UserManager::get_user_tags($u2, $field), 'Other users keep their tags.');
    }

    public function testProcessTagsAcceptsArrayOrString(): void
    {
        $u = $this->createUser('um_ptag')->getId();
        $field = $this->createExtraField('um_ptags_field', 10);

        $this->assertTrue(UserManager::process_tags(['um_t1', 'um_t2'], $u, $field));
        $this->assertTrue(UserManager::process_tags('um_t3', $u, $field));

        $this->assertSame(['um_t1', 'um_t2', 'um_t3'], array_column(UserManager::get_user_tags($u, $field), 'tag'));
    }

    // ------------------------------------------------------------- extra fields

    public function testExtraUserDataUsesValueThenDefault(): void
    {
        $u = $this->createUser('um_ex')->getId();
        $withValue = $this->createExtraField('um_ex_text', 1, 'dflt');
        $this->createExtraField('um_ex_default', 1, 'fallback');
        $multi = $this->createExtraField('um_ex_multi', 5);
        $this->setExtraValue($withValue, $u, 'hello');
        $this->setExtraValue($multi, $u, 'a;b;c');

        $this->assertSame([], UserManager::get_extra_user_data(0));

        $data = UserManager::get_extra_user_data($u);
        $this->assertSame('hello', $data['um_ex_text']);
        $this->assertSame('fallback', $data['um_ex_default'], 'Fields without a value for the user expose the field default.');
        $this->assertSame(['a', 'b', 'c'], $data['um_ex_multi'], 'Select-multiple values are exploded on ";".');

        $prefixed = UserManager::get_extra_user_data($u, true);
        $this->assertSame('hello', $prefixed['extra_um_ex_text']);
        $this->assertArrayNotHasKey('um_ex_text', $prefixed);

        // Another user: default, not the first user's value.
        $other = $this->createUser('um_ex_other')->getId();
        $this->assertSame('dflt', UserManager::get_extra_user_data($other)['um_ex_text']);

        $one = UserManager::get_extra_user_data_by_field($u, 'um_ex_text');
        $this->assertSame(['um_ex_text' => 'hello'], $one);
        $this->assertSame(['um_ex_text' => ''], UserManager::get_extra_user_data_by_field($other, 'um_ex_text'), 'No default here: empty string.');
        $this->assertSame(['extra_um_ex_text' => 'hello'], UserManager::get_extra_user_data_by_field($u, 'um_ex_text', true));
        $this->assertSame([], UserManager::get_extra_user_data_by_field($u, 'um_ex_unknown'));
    }

    public function testExtraFieldVisibilityAndFilterConditions(): void
    {
        $u = $this->createUser('um_exv')->getId();
        $hidden = $this->createExtraField('um_exv_hidden', 1, 'h');
        $visible = $this->createExtraField('um_exv_visible', 1, 'v');
        $this->conn()->executeStatement('UPDATE extra_field SET visible_to_self = 0 WHERE id = ?', [$hidden]);
        $this->conn()->executeStatement('UPDATE extra_field SET filter = 1 WHERE id = ?', [$visible]);

        $all = UserManager::get_extra_user_data($u, false, true);
        $this->assertArrayHasKey('um_exv_hidden', $all);
        $this->assertArrayHasKey('um_exv_visible', $all);

        $selfVisible = UserManager::get_extra_user_data($u, false, false);
        $this->assertArrayNotHasKey('um_exv_hidden', $selfVisible);
        $this->assertArrayHasKey('um_exv_visible', $selfVisible);

        $filtered = UserManager::get_extra_user_data($u, false, true, false, 1);
        $this->assertArrayHasKey('um_exv_visible', $filtered);
        $this->assertArrayNotHasKey('um_exv_hidden', $filtered);

        $unfiltered = UserManager::get_extra_user_data($u, false, true, false, 0);
        $this->assertArrayHasKey('um_exv_hidden', $unfiltered);
        $this->assertArrayNotHasKey('um_exv_visible', $unfiltered);
    }

    public function testExtraFieldAvailabilityAndSearchByValue(): void
    {
        $u1 = $this->createUser('um_exs_1')->getId();
        $u2 = $this->createUser('um_exs_2')->getId();

        // UserManager::create_extra_field() is deliberately not exercised: ExtraField::save() raises a DB error
        // on this schema (the legacy INSERT omits NOT NULL columns), which Database::handleError() turns into
        // NotAllowedException. Fields are created by fixture SQL instead.
        $this->assertFalse(UserManager::is_extra_field_available('um_exs_field'));
        $fieldId = $this->createExtraField('um_exs_field', 1);
        $this->assertTrue(UserManager::is_extra_field_available('um_exs_field'));

        // update_extra_field_value() is not exercised either: ExtraFieldValue::save() hits the same legacy
        // Database::insert() failure (model.lib.php:176 -> Database::handleError) on this schema.
        $this->setExtraValue($fieldId, $u1, 'um_val_x');
        $this->setExtraValue($fieldId, $u2, 'um_val_y');

        $ids = UserManager::get_extra_user_data_by_value('um_exs_field', 'um_val_x');
        $this->assertEquals([$u1], $ids, 'Only users holding exactly that value are returned.');
        $this->assertSame([], UserManager::get_extra_user_data_by_value('um_exs_field', 'um_val_none'));
        $this->assertSame([], UserManager::get_extra_user_data_by_value('um_exs_unknown', 'um_val_x'));

        $info = UserManager::get_extra_field_information_by_name('um_exs_field');
        $this->assertEquals($fieldId, $info['id']);
        $this->assertSame('um_exs_field', $info['variable']);
        $this->assertEquals($fieldId, UserManager::get_extra_field_information($fieldId)['id']);

        $byVar = UserManager::get_extra_user_data_by_field_variable('um_exs_field');
        $this->assertSame('um_val_x', $byVar[$u1]['field_value']);
        $this->assertSame('um_val_y', $byVar[$u2]['field_value']);
    }

    public function testGetUserIdFromOriginalId(): void
    {
        $u = $this->createUser('um_orig')->getId();
        $field = $this->createExtraField('um_original_id', 1);
        $this->setExtraValue($field, $u, 'um_ORIG-42');

        $this->assertEquals($u, UserManager::get_user_id_from_original_id('um_ORIG-42', 'um_original_id'));
        $this->assertSame(0, UserManager::get_user_id_from_original_id('um_ORIG-43', 'um_original_id'), 'Not found is 0 (int), unlike get_user_id_from_username.');
        $this->assertSame(0, UserManager::get_user_id_from_original_id('um_ORIG-42', 'um_other_field'));
        $this->assertSame(0, UserManager::get_user_id_from_original_id("0' OR '1'='1", '---'));
    }

    // ------------------------------------------------------- user <-> user links

    public function testHrManagerSubscriptionAndFollowedLists(): void
    {
        $hrm = $this->createUser('um_hrm')->getId();
        $s1 = $this->createUser('um_hrm_s1')->getId();
        $s2 = $this->createUser('um_hrm_s2')->getId();
        $s3 = $this->createUser('um_hrm_s3')->getId();
        $this->names($s1, 'Bob', 'Umhrm');
        $this->names($s2, 'Al', 'Umhrm');
        $this->names($s3, 'Cy', 'Umhrm');

        UserManager::subscribeUsersToHRManager($hrm, [$s1, $s2]);
        $rrhh = UserRelUser::USER_RELATION_TYPE_RRHH;

        $row = $this->row('SELECT user_id, friend_user_id, relation_type FROM user_rel_user WHERE user_id = ? AND friend_user_id = ?', [$s1, $hrm]);
        $this->assertEquals($rrhh, $row['relation_type'], 'user_id is the followed user, friend_user_id the HR manager.');

        $this->assertTrue(UserManager::is_user_followed_by_hrm($s1, $hrm));
        $this->assertTrue(UserManager::is_user_followed_by_drh($s2, $hrm));
        $this->assertFalse(UserManager::is_user_followed_by_hrm($s3, $hrm));
        $this->assertFalse(UserManager::is_user_followed_by_hrm($hrm, $s1), 'The relation is directional.');

        $followed = UserManager::get_users_followed_by_hrm($hrm, 0, false, false, false, null, null, null, null, null, null, false);
        $this->assertSame([$s2, $s1], array_keys($followed), 'Keyed by user id; ordered by lastname, firstname.');
        $this->assertSame(['user_id', 'username', 'lastname', 'firstname', 'email'], array_values(array_filter(array_keys($followed[$s1]), 'is_string')));

        $count = UserManager::get_users_followed_by_hrm($hrm, 0, false, false, true);
        $this->assertEquals(2, $count);

        $onlyIds = UserManager::get_users_followed_by_hrm($hrm, 0, true);
        $this->assertEqualsCanonicalizing([$s1, $s2], array_keys($onlyIds));

        $sql = UserManager::get_users_followed_by_hrm($hrm, 0, false, true);
        $this->assertIsString($sql);
        $this->assertStringContainsString('friend_user_id', $sql);

        $kw = UserManager::getUsersFollowedByUser($hrm, null, false, false, false, null, null, null, null, null, null, DRH, 'Bob');
        $this->assertSame([$s1], array_keys($kw), 'Keyword search over names.');

        $desc = UserManager::getUsersFollowedByUser($hrm, null, false, false, false, null, null, 'username', 'desc', null, null, DRH);
        $this->assertSame([$s2, $s1], array_keys($desc));
        $paged = UserManager::getUsersFollowedByUser($hrm, null, false, false, false, 1, 1, 'username', 'asc', null, null, DRH);
        $this->assertSame([$s2], array_keys($paged));

        $this->conn()->executeStatement('UPDATE user SET active = 0 WHERE id = ?', [$s1]);
        $activeOnly = UserManager::getUsersFollowedByUser($hrm, null, false, false, false, null, null, null, null, 1, null, DRH);
        $this->assertSame([$s2], array_keys($activeOnly));

        // HR manager's own list (inverse direction).
        $managers = UserManager::getHrmListFromUser($s1);
        $this->assertCount(1, $managers);
        $this->assertEquals($hrm, $managers[0]['id']);
        $this->assertSame('um_hrm', $managers[0]['username']);
        $this->assertEquals($managers, UserManager::getDrhListFromUser($s1));
        $this->assertSame([], UserManager::getHrmListFromUser($s3));
    }

    public function testSubscribeUsersToHrManagerReplacesOnlyWhenAsked(): void
    {
        $hrm = $this->createUser('um_hrm2')->getId();
        $s1 = $this->createUser('um_hrm2_s1')->getId();
        $s2 = $this->createUser('um_hrm2_s2')->getId();

        UserManager::subscribeUsersToHRManager($hrm, [$s1]);
        UserManager::subscribeUsersToHRManager($hrm, [$s1], true);
        $this->assertSame(
            '1',
            (string) $this->conn()->fetchOne('SELECT COUNT(*) FROM user_rel_user WHERE friend_user_id = ?', [$hrm]),
            'No duplicate link is created.'
        );

        UserManager::subscribeUsersToHRManager($hrm, [$s2], false);
        $this->assertTrue(UserManager::is_user_followed_by_hrm($s1, $hrm));
        $this->assertTrue(UserManager::is_user_followed_by_hrm($s2, $hrm), 'deleteOther=false keeps existing links.');

        UserManager::subscribeUsersToHRManager($hrm, [$s2], true);
        $this->assertFalse(UserManager::is_user_followed_by_hrm($s1, $hrm), 'deleteOther=true drops links that are not listed.');
        $this->assertTrue(UserManager::is_user_followed_by_hrm($s2, $hrm));
    }

    public function testSubscribeUsersToUserDeleteUsersBeforeInsertRemovesOwnRows(): void
    {
        $a = $this->createUser('um_stu_a')->getId();
        $b = $this->createUser('um_stu_b')->getId();
        $c = $this->createUser('um_stu_c')->getId();
        $type = UserRelUser::USER_RELATION_TYPE_RRHH;

        // Row where $a is the *user_id* side.
        UserManager::subscribeUsersToUser($b, [$a], $type, false, false);
        // Row where $a is the friend side.
        UserManager::subscribeUsersToUser($a, [$c], $type, false, false);

        UserManager::subscribeUsersToUser($a, [], $type, true, false);

        $this->assertSame(
            '0',
            (string) $this->conn()->fetchOne('SELECT COUNT(*) FROM user_rel_user WHERE user_id = ? AND relation_type = ?', [$a, $type]),
            'deleteUsersBeforeInsert removes the rows where the given id is user_id.'
        );
        $this->assertSame(
            '1',
            (string) $this->conn()->fetchOne('SELECT COUNT(*) FROM user_rel_user WHERE friend_user_id = ? AND relation_type = ?', [$a, $type])
        );
    }

    public function testRequestUsersToHrManagerUsesSeparateRelationType(): void
    {
        $hrm = $this->createUser('um_req_hrm')->getId();
        $s = $this->createUser('um_req_s')->getId();

        UserManager::requestUsersToHRManager($hrm, [$s]);

        $this->assertFalse(UserManager::is_user_followed_by_hrm($s, $hrm), 'A request is not a subscription.');
        $this->assertEquals(
            UserRelUser::USER_RELATION_TYPE_HRM_REQUEST,
            $this->conn()->fetchOne('SELECT relation_type FROM user_rel_user WHERE user_id = ? AND friend_user_id = ?', [$s, $hrm])
        );

        $requests = UserManager::getUsersFollowedByUser($hrm, null, false, false, false, null, null, null, null, null, null, HRM_REQUEST);
        $this->assertSame([$s], array_keys($requests));
    }

    public function testStudentBossSubscriptionAndLookups(): void
    {
        $boss1 = $this->createUser('um_boss1')->getId();
        $boss2 = $this->createUser('um_boss2')->getId();
        $st = $this->createUser('um_bst')->getId();
        $st2 = $this->createUser('um_bst2')->getId();

        $this->assertFalse(UserManager::getFirstStudentBoss($st));
        $this->assertSame([], UserManager::getStudentBossList($st));
        $this->assertSame([], UserManager::getStudentBossList(0));
        $this->assertFalse(UserManager::userIsBossOfStudent($boss1, $st));

        UserManager::subscribeBossToUsers($boss1, [$st, $st2]);
        $this->assertTrue(UserManager::userIsBossOfStudent($boss1, $st));
        $this->assertEquals($boss1, UserManager::getFirstStudentBoss($st));
        $this->assertSame([['boss_id' => (string) $boss1]], array_map(static fn ($r) => ['boss_id' => (string) $r['boss_id']], UserManager::getStudentBossList($st)));

        $followed = UserManager::getUsersFollowedByUser($boss1, null, false, false, false, null, null, null, null, null, null, STUDENT_BOSS);
        $this->assertEqualsCanonicalizing([$st, $st2], array_keys($followed));

        // subscribeBossToUsers replaces the boss's other students by default.
        UserManager::subscribeBossToUsers($boss1, [$st]);
        $this->assertFalse(UserManager::userIsBossOfStudent($boss1, $st2));

        // subscribeUserToBossList: returns the number inserted, false when unchanged.
        $this->assertFalse(UserManager::subscribeUserToBossList($st, [$boss1]), 'Same list: nothing to do.');
        $this->assertSame(1, UserManager::subscribeUserToBossList($st, [$boss2]));
        $this->assertFalse(UserManager::userIsBossOfStudent($boss1, $st), 'Previous bosses are replaced.');
        $this->assertTrue(UserManager::userIsBossOfStudent($boss2, $st));
        $this->assertSame(2, UserManager::subscribeUserToBossList($st, [$boss2, $boss1]));
        $this->assertFalse(UserManager::subscribeUserToBossList(999999999, [$boss1]), 'Unknown student: false.');

        $this->assertSame(0, UserManager::subscribeUserToBossList($st, []));
        $this->assertSame([], UserManager::getStudentBossList($st), 'An empty list removes every boss.');

        $this->assertFalse(UserManager::removeAllBossFromStudent(0));
        UserManager::subscribeBossToUsers($boss1, [$st]);
        $this->assertTrue(UserManager::removeAllBossFromStudent($st));
        $this->assertFalse(UserManager::getFirstStudentBoss($st));
    }

    // ----------------------------------------------------------------- careers

    public function testUserCareerQueries(): void
    {
        $u = $this->createUser('um_car')->getId();
        $this->conn()->executeStatement(
            "INSERT INTO career (title, description, status, created_at, updated_at) VALUES ('um_career_b', '', 1, NOW(), NOW())"
        );
        $c1 = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement(
            "INSERT INTO career (title, description, status, created_at, updated_at) VALUES ('um_career_a', '', 1, NOW(), NOW())"
        );
        $c2 = (int) $this->conn()->lastInsertId();

        $this->assertFalse(UserManager::userHasCareer($u, $c1));
        $this->assertFalse(UserManager::getUserCareer($u, $c1));
        $this->assertSame([], UserManager::getUserCareers($u));

        $this->conn()->executeStatement(
            "INSERT INTO user_career (user_id, career_id, created_at, updated_at) VALUES (?, ?, '2020-01-02 00:00:00', NOW())",
            [$u, $c1]
        );
        $this->conn()->executeStatement(
            "INSERT INTO user_career (user_id, career_id, created_at, updated_at) VALUES (?, ?, '2020-01-01 00:00:00', NOW())",
            [$u, $c2]
        );

        $this->assertTrue(UserManager::userHasCareer($u, $c1));
        $this->assertFalse(UserManager::userHasCareer($u, $c2 + 1000));
        $this->assertEquals($c1, UserManager::getUserCareer($u, $c1)['career_id']);

        $careers = UserManager::getUserCareers($u);
        $this->assertSame(['um_career_a', 'um_career_b'], array_column($careers, 'title'), 'Ordered by the link creation date, not by title.');
        $this->assertSame(['id', 'title'], array_keys($careers[0]));
    }

    // ----------------------------------------------------------------- misc

    public function testUpdateAllUserLanguagesMovesOnlyMatchingLocales(): void
    {
        $a = $this->createUser('um_lang_a')->getId();
        $b = $this->createUser('um_lang_b')->getId();
        $this->conn()->executeStatement('UPDATE user SET locale = ? WHERE id = ?', ['um_xx', $a]);
        $this->conn()->executeStatement('UPDATE user SET locale = ? WHERE id = ?', ['um_yy', $b]);

        UserManager::update_all_user_languages('um_xx', 'um_zz');
        $this->assertSame('um_zz', $this->conn()->fetchOne('SELECT locale FROM user WHERE id = ?', [$a]));
        $this->assertSame('um_yy', $this->conn()->fetchOne('SELECT locale FROM user WHERE id = ?', [$b]));

        UserManager::update_all_user_languages('um_zz', 'um_zz');
        UserManager::update_all_user_languages('', 'um_qq');
        $this->assertSame('um_zz', $this->conn()->fetchOne('SELECT locale FROM user WHERE id = ?', [$a]), 'No-op guards.');
    }

    public function testGetAllAdministratorsListsAdminRolesExceptSoftDeleted(): void
    {
        $admin = $this->createUser('um_adm', '', '', 'ROLE_ADMIN')->getId();
        $global = $this->createUser('um_gadm', '', '', 'ROLE_GLOBAL_ADMIN')->getId();
        $gone = $this->createUser('um_adm_gone', '', '', 'ROLE_ADMIN')->getId();
        $plain = $this->createUser('um_notadm')->getId();
        $this->conn()->executeStatement('UPDATE user SET active = -2 WHERE id = ?', [$gone]);

        $admins = UserManager::get_all_administrators();

        $this->assertArrayHasKey($admin, $admins, 'Keyed by user id.');
        $this->assertArrayHasKey($global, $admins);
        $this->assertArrayNotHasKey($gone, $admins, 'Soft-deleted admins are excluded.');
        $this->assertArrayNotHasKey($plain, $admins);
        $this->assertSame(
            ['user_id', 'username', 'firstname', 'lastname', 'email', 'active', 'locale'],
            array_keys($admins[$admin])
        );
        $this->assertSame('um_adm', $admins[$admin]['username']);

        $this->assertTrue(UserManager::is_admin($admin));
        $this->assertFalse(UserManager::is_admin($plain));
        $this->assertFalse(UserManager::is_admin(0));
    }

    public function testIsSessionCourseCoachMatchesOnlyCourseCoachStatus(): void
    {
        $coach = $this->createUser('um_coach')->getId();
        $other = $this->createUser('um_notcoach')->getId();
        $cid = $this->createRawCourse('um_course_coach');
        $sid = $this->createRawSession('um_session_coach');

        $this->assertFalse(UserManager::is_session_course_coach($coach, $cid, $sid));

        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course (session_id, c_id, position, nbr_users) VALUES (?, ?, 0, 0)',
            [$sid, $cid]
        );
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course_rel_user (session_id, c_id, user_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, ?, 2, 0, 0, 0)',
            [$sid, $cid, $coach]
        );
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course_rel_user (session_id, c_id, user_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, ?, 0, 0, 0, 0)',
            [$sid, $cid, $other]
        );

        $this->assertTrue(UserManager::is_session_course_coach($coach, $cid, $sid));
        $this->assertFalse(UserManager::is_session_course_coach($other, $cid, $sid), 'A plain student (status 0) is not a coach.');
        $this->assertFalse(UserManager::is_session_course_coach($coach, $cid, $sid + 100000));
    }

    public function testGetUserIdOfCourseAdminOrSessionAdmin(): void
    {
        $teacher = $this->createUser('um_teach')->getId();
        $cid = $this->createRawCourse('um_course_admin');
        $this->conn()->executeStatement(
            'INSERT INTO course_rel_user (c_id, user_id, status, relation_type, user_course_cat, sort, legal_agreement, progress, is_tutor)
             VALUES (?, ?, 1, 0, 0, 0, 0, 0, 0)',
            [$cid, $teacher]
        );

        $id = UserManager::get_user_id_of_course_admin_or_session_admin(['real_id' => $cid, 'id' => $cid, 'session_id' => 0]);
        $this->assertSame($teacher, $id);
    }
}
