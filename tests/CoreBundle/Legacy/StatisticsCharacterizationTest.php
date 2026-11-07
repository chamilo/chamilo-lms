<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Database;
use Doctrine\DBAL\Connection;
use Statistics;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization of the legacy Statistics library (public/main/inc/lib/statistics.lib.php).
 *
 * Every test pins what the raw SQL currently returns (counts, ordering, boundaries of BETWEEN ranges, NULL
 * handling, soft-deleted users, multi-URL scoping), so that a rewrite to DQL that changes a number fails here.
 * Fixtures are inserted with prepared statements (no resource_node rows) and carry the prefix st_; the
 * track_e_* dates are FIXED so that the DATE_FORMAT/TIMESTAMPDIFF results are exact. The few reports that are
 * relative to "now" by design (last 7 days...) seed rows relative to the current day and assert exact counts.
 */
class StatisticsCharacterizationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $httpSession = new HttpSession(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($httpSession);
        self::getContainer()->get('request_stack')->push($request);
        Container::setContainer(self::getContainer());
        Container::setSession($httpSession);
        Container::setRequest($request);
        Database::setManager($this->getEntityManager());
        Database::setConnection($this->getEntityManager()->getConnection());
        $_GET = [];
        // api_get_utc_datetime() converts from this timezone: the fixed boundaries below assume UTC.
        \ChamiloSession::write('system_timezone', 'UTC');
        \ChamiloSession::erase('_user');
    }

    protected function tearDown(): void
    {
        $_GET = [];
        \ChamiloSession::erase('system_timezone');
        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function conn(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function fresh(): void
    {
        $this->getEntityManager()->clear();
    }

    /**
     * @param array<string, mixed> $o status, active, created_at, picture_uri, creator_id, firstname, lastname,
     *                                email, url (access_url id or null for none)
     */
    private function rawUser(string $tag, array $o = []): int
    {
        $name = 'st_'.$tag.'_'.getmypid().'_'.(++$this->seq);
        $email = $o['email'] ?? $name.'@example.com';
        $this->conn()->executeStatement(
            'INSERT INTO user (username, username_canonical, email, email_canonical, firstname, lastname, locale,
                password, timezone, locked, expired, credentials_expired, salt, roles, status, active, uuid,
                mfa_enabled, created_at, updated_at, official_code, picture_uri, creator_id)
             VALUES (?, ?, ?, ?, ?, ?, \'en_US\', \'x\', \'UTC\', 0, 0, 0, \'x\', \'[]\', ?, ?,
                UNHEX(REPLACE(UUID(), \'-\', \'\')), 0, ?, ?, \'\', ?, ?)',
            [
                $name,
                $name,
                $email,
                $email,
                $o['firstname'] ?? 'First'.$tag,
                $o['lastname'] ?? 'Last'.$tag,
                $o['status'] ?? 5,
                $o['active'] ?? 1,
                $o['created_at'] ?? '2020-01-01 00:00:00',
                $o['created_at'] ?? '2020-01-01 00:00:00',
                $o['picture_uri'] ?? null,
                $o['creator_id'] ?? null,
            ]
        );
        $id = (int) $this->conn()->lastInsertId();
        $url = array_key_exists('url', $o) ? $o['url'] : 1;
        if (null !== $url) {
            $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (?, ?)', [$url, $id]);
        }

        return $id;
    }

    private function setSetting(string $name, string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting($name, $value);
        $this->fresh();
    }

    private function username(int $id): string
    {
        return (string) $this->conn()->fetchOne('SELECT username FROM user WHERE id = ?', [$id]);
    }

    private function rawCourse(string $tag, string $created, int $visibility = 2, int $languageId = 14, ?int $url = 1): int
    {
        $code = 'ST_'.strtoupper($tag).'_'.getmypid().'_'.(++$this->seq);
        $this->conn()->executeStatement(
            'INSERT INTO course (language_id, title, code, visual_code, directory, visibility, video_url, sticky,
                creation_date, subscribe, unsubscribe)
             VALUES (?, ?, ?, ?, ?, ?, \'\', 0, ?, 1, 0)',
            [$languageId, 'st_'.$tag, $code, $code, $code, $visibility, $created]
        );
        $id = (int) $this->conn()->lastInsertId();
        if (null !== $url) {
            $this->conn()->executeStatement('INSERT INTO access_url_rel_course (c_id, access_url_id) VALUES (?, ?)', [$id, $url]);
        }

        return $id;
    }

    private function enableMultiUrl(): int
    {
        // AccessUrlHelper::isMultiple() is "more than one access_url row"; in CLI the current URL is the first one.
        $this->conn()->executeStatement(
            'INSERT INTO access_url (url, active, description, created_by, lft, rgt, lvl)
             VALUES (?, 1, \'\', 1, 1, 2, 0)',
            ['http://st-second-url.invalid/']
        );
        $this->fresh();

        return (int) $this->conn()->lastInsertId();
    }

    private function rawLogin(int $userId, string $login, ?string $logout): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO track_e_login (login_user_id, login_date, user_ip, logout_date) VALUES (?, ?, \'10.1.2.3\', ?)',
            [$userId, $login, $logout]
        );
    }

    private function rawDefault(int $userId, string $date, string $event, string $valueType = 'st_vt', string $value = 'st_value', ?int $cid = null, ?int $sid = null): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO track_e_default (default_user_id, c_id, default_date, default_event_type, default_value_type, default_value, session_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$userId, $cid, $date, $event, $valueType, $value, $sid]
        );
    }

    private function rawAccess(int $userId, string $date, int $cid, string $tool, int $sid = 0): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO track_e_access (access_user_id, access_date, c_id, access_tool, session_id, user_ip)
             VALUES (?, ?, ?, ?, ?, \'10.1.2.3\')',
            [$userId, $date, $cid, $tool, $sid]
        );
    }

    private function rawLastAccess(int $userId, string $date, int $cid): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO track_e_lastaccess (access_user_id, access_date, c_id, access_tool, session_id)
             VALUES (?, ?, ?, \'st_tool\', 0)',
            [$userId, $date, $cid]
        );
    }

    private function courseUser(int $userId, int $cid): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO course_rel_user (user_id, c_id, relation_type, status, is_tutor, sort, user_course_cat, legal_agreement, progress)
             VALUES (?, ?, 0, 5, 0, 0, 0, 0, 0)',
            [$userId, $cid]
        );
    }

    private function rawCategory(string $code): int
    {
        $this->conn()->executeStatement('INSERT INTO course_category (title, code) VALUES (?, ?)', ['st_'.$code, $code]);

        return (int) $this->conn()->lastInsertId();
    }

    private function courseInCategory(int $cid, int $categoryId): void
    {
        $this->conn()->executeStatement('INSERT INTO course_rel_category (course_id, course_category_id) VALUES (?, ?)', [$cid, $categoryId]);
    }

    /**
     * @param int[] $recipients
     */
    private function rawMessage(int $sender, array $recipients): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO message (user_sender_id, msg_type, status, send_date, title, content, mail_answer)
             VALUES (?, 1, 1, \'2020-01-01 00:00:00\', \'st_msg\', \'st_content\', 0)',
            [$sender]
        );
        $id = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement(
            'INSERT INTO message_rel_user (message_id, user_id, msg_read, receiver_type, starred) VALUES (?, ?, 1, ?, 0)',
            [$id, $sender, MessageRelUser::TYPE_SENDER]
        );
        foreach ($recipients as $r) {
            $this->conn()->executeStatement(
                'INSERT INTO message_rel_user (message_id, user_id, msg_read, receiver_type, starred) VALUES (?, ?, 0, ?, 0)',
                [$id, $r, MessageRelUser::TYPE_TO]
            );
        }

        return $id;
    }

    private function rawFriend(int $userId, int $friendId, int $type = UserRelUser::USER_RELATION_TYPE_FRIEND): void
    {
        $this->conn()->executeStatement(
            'INSERT INTO user_rel_user (user_id, friend_user_id, relation_type, created_at, updated_at)
             VALUES (?, ?, ?, \'2020-01-01 00:00:00\', \'2020-01-01 00:00:00\')',
            [$userId, $friendId, $type]
        );
    }

    /**
     * Finds the key of a "Name<br />(username)" report that carries the given username.
     */
    private function keyFor(array $report, int $userId): ?string
    {
        $needle = '('.$this->username($userId).')';
        foreach (array_keys($report) as $key) {
            if (str_contains((string) $key, $needle)) {
                return (string) $key;
            }
        }

        return null;
    }

    /**
     * Parses the rows of the LAST printStats() table of an html block into [label => number].
     *
     * @return array<string, string>
     */
    private function statRows(string $html, bool $lastTableOnly = true): array
    {
        if ($lastTableOnly) {
            $tables = explode('<table', $html);
            $html = '<table'.end($tables);
        }
        preg_match_all(
            '#<td style="vertical-align:top;">(.*?)</td>\s*<td style="vertical-align:middle;">.*?</td>\s*<td class="text-end" style="vertical-align:top;">(.*?)</td>#s',
            $html,
            $m
        );
        $rows = [];
        foreach ($m[1] as $i => $label) {
            $rows[trim(strip_tags($label))] = trim($m[2][$i]);
        }

        return $rows;
    }

    private function dayAgo(int $days, string $time = '12:00:00'): string
    {
        return gmdate('Y-m-d', strtotime("-$days days")).' '.$time;
    }

    // ------------------------------------------------------------ pure helpers

    public function testMakeSizeStringUnitsAndBoundaries(): void
    {
        $this->assertSame('1023 bytes', Statistics::makeSizeString(1023));
        $this->assertSame('1 KB', Statistics::makeSizeString(1024));
        $this->assertSame('2 KB', Statistics::makeSizeString(1536), 'KB is rounded to 0 decimals');
        $this->assertSame('1 MB', Statistics::makeSizeString(1048576));
        $this->assertSame('1.5 MB', Statistics::makeSizeString(1572864));
        $this->assertSame('1.5 GB', Statistics::makeSizeString(1610612736));
        // exactly 2^30 bytes used to fall in no branch (`>` instead of `>=`) and render as ''.
        $this->assertSame('1 GB', Statistics::makeSizeString(1073741824));
    }

    public function testBuildDatesArrayExcludesTheEndDate(): void
    {
        // PINNED QUIRK: the docblock says "including limits" but the loop is `$time < $endTimestamp`:
        // the end date is never part of the array. 2024 is a leap year.
        $this->assertSame(
            ['2024-02-27' => 0, '2024-02-28' => 0, '2024-02-29' => 0, '2024-03-01' => 0],
            Statistics::buildDatesArray('2024-02-27', '2024-03-02')
        );
        $this->assertSame(
            ['02-27' => 0, '02-28' => 0],
            Statistics::buildDatesArray('2024-02-27 15:00:00', '2024-02-29 01:00:00', true)
        );
        $this->assertFalse(Statistics::buildDatesArray('garbage', '2024-03-02'));
        $this->assertFalse(Statistics::buildDatesArray('2024-03-02', '03/02/2024'));
    }

    public function testInitializeDateRangeArrayIncludesBothLimits(): void
    {
        $this->assertSame(
            ['2024-02-28' => 0, '2024-02-29' => 0, '2024-03-01' => 0],
            Statistics::initializeDateRangeArray('2024-02-28', '2024-03-01')
        );
    }

    public function testIsMoreThanAMonthBoundaries(): void
    {
        $this->assertFalse(Statistics::isMoreThanAMonth('2024-01-15', '2024-02-14'));
        $this->assertFalse(Statistics::isMoreThanAMonth('2024-01-15', '2024-02-15'), 'exactly one month is not more');
        $this->assertTrue(Statistics::isMoreThanAMonth('2024-01-15', '2024-02-16'));
        $this->assertTrue(Statistics::isMoreThanAMonth('2024-01-15', '2024-03-15'));
        $this->assertTrue(Statistics::isMoreThanAMonth('2023-01-15', '2024-01-15'), 'a year is more');
    }

    public function testGroupByMonthSumsCountsPerYearMonth(): void
    {
        $this->assertSame(
            ['2024-01' => 5, '2024-02' => 4],
            Statistics::groupByMonth([
                ['date' => '2024-01-03', 'count' => 2],
                ['date' => '2024-01-31', 'count' => 3],
                ['date' => '2024-02-01', 'count' => 4],
            ])
        );
    }

    // ------------------------------------------------------------ countCourses

    public function testCountCoursesDateRangeIsInclusiveOnBothDays(): void
    {
        $this->rawCourse('a', '2020-03-01 00:00:00');
        $this->rawCourse('b', '2020-03-31 23:59:59');
        $this->rawCourse('c', '2020-04-01 00:00:00');
        $this->rawCourse('d', '2020-02-29 23:59:59');

        $this->assertSame(2, Statistics::countCourses(null, '2020-03-01', '2020-03-31'));
        $this->assertSame(3, Statistics::countCourses(null, '2020-03-01', '2020-04-01'));
        // only a lower bound: those 3 plus the 2 fixture courses created in 2026
        $this->assertSame(5, Statistics::countCourses(null, '2020-03-01'));
        // only an upper bound
        $this->assertSame(1, Statistics::countCourses(null, null, '2020-02-29'));
    }

    public function testCountCoursesByCategoryCode(): void
    {
        $cat = $this->rawCategory('st_cat1');
        $cat2 = $this->rawCategory('st_cat2');
        $in = $this->rawCourse('catin', '2020-03-01 10:00:00');
        $both = $this->rawCourse('catboth', '2020-03-01 10:00:00');
        $this->rawCourse('catout', '2020-03-01 10:00:00');
        $this->courseInCategory($in, $cat);
        $this->courseInCategory($both, $cat);
        $this->courseInCategory($both, $cat2);

        // The filter used to read course.category_code, a column that no longer exists: it always threw.
        $this->assertSame(2, Statistics::countCourses('st_cat1'));
        $this->assertSame(1, Statistics::countCourses('st_cat2'));
        $this->assertSame(0, Statistics::countCourses('st_unknown_cat'));
        $this->assertSame(2, Statistics::countCourses('st_cat1', '2020-03-01', '2020-03-01'));
        $this->assertSame(0, Statistics::countCourses('st_cat1', '2021-01-01'));
        $this->assertSame(0, Statistics::countCourses("x' OR '1'='1"), 'the code is a bound value');
    }

    public function testCountCoursesByCategoryCodeInMultiUrl(): void
    {
        $second = $this->enableMultiUrl();
        $cat = $this->rawCategory('st_mcat');
        $mine = $this->rawCourse('mcat1', '2020-03-01 10:00:00', 2, 14, 1);
        $theirs = $this->rawCourse('mcat2', '2020-03-01 10:00:00', 2, 14, $second);
        $this->courseInCategory($mine, $cat);
        $this->courseInCategory($theirs, $cat);

        $this->assertSame(1, Statistics::countCourses('st_mcat'));
    }

    public function testCountCoursesIsScopedToTheCurrentUrlWhenMultiUrl(): void
    {
        $second = $this->enableMultiUrl();
        $this->rawCourse('u1', '2020-03-01 10:00:00', 2, 14, 1);
        $this->rawCourse('u2', '2020-03-01 10:00:00', 2, 14, $second);
        $this->rawCourse('nourl', '2020-03-01 10:00:00', 2, 14, null);

        // current URL = the first one: 2 fixture courses + u1; u2 (other URL) and nourl are not counted.
        $this->assertSame(3, Statistics::countCourses());
        $this->assertSame(1, Statistics::countCourses(null, '2020-03-01', '2020-03-01'));
    }

    public function testCountCoursesByVisibility(): void
    {
        $this->assertSame(0, Statistics::countCoursesByVisibility([]));
        $this->assertSame(0, Statistics::countCoursesByVisibility(null));

        $this->rawCourse('v0', '2020-03-01 10:00:00', 0);
        $this->rawCourse('v1', '2020-03-02 10:00:00', 1);
        $this->rawCourse('v3', '2020-03-31 23:59:59', 3);
        $this->rawCourse('v3b', '2020-04-01 00:00:00', 3);

        $this->assertSame(1, Statistics::countCoursesByVisibility([0]));
        $this->assertSame(2, Statistics::countCoursesByVisibility([0, 1]));
        $this->assertSame(2, Statistics::countCoursesByVisibility([3]));
        $this->assertSame(1, Statistics::countCoursesByVisibility([3], '2020-03-01', '2020-03-31'));
        $this->assertSame(3, Statistics::countCoursesByVisibility([0, 1, 3], '2020-03-01', '2020-03-31'));
        // the 2 fixture courses are visibility 2 and created in 2026
        $this->assertSame(2, Statistics::countCoursesByVisibility([2]));
        // Items are int-cast: a non-numeric string becomes 0 (closed courses).
        $this->assertSame(1, Statistics::countCoursesByVisibility(['0; DROP TABLE course']));
    }

    public function testCountCoursesByVisibilityIsScopedToTheCurrentUrlWhenMultiUrl(): void
    {
        $second = $this->enableMultiUrl();
        $this->rawCourse('mv1', '2020-03-01 10:00:00', 0, 14, 1);
        $this->rawCourse('mv2', '2020-03-01 10:00:00', 0, 14, $second);

        $this->assertSame(1, Statistics::countCoursesByVisibility([0]));
    }

    // ------------------------------------------------------------- countUsers

    public function testCountUsersFiltersStatusActiveAndSoftDeleted(): void
    {
        $all = Statistics::countUsers();
        $teachers = Statistics::countUsers(1);
        $students = Statistics::countUsers(5);
        $activeAll = Statistics::countUsers(null, null, true, true);

        $this->rawUser('t1', ['status' => 1]);
        $this->rawUser('s1', ['status' => 5]);
        $this->rawUser('s2', ['status' => 5, 'active' => 0]);
        $this->rawUser('s3', ['status' => 5, 'active' => -2]);
        $this->rawUser('s4', ['status' => 5, 'active' => -1]);

        $this->assertSame($all + 4, Statistics::countUsers(), 'soft-deleted (-2) is the only excluded state');
        $this->assertSame($teachers + 1, Statistics::countUsers(1));
        $this->assertSame($students + 3, Statistics::countUsers(5));
        $this->assertSame($activeAll + 2, Statistics::countUsers(null, null, true, true), 'only active = 1');
    }

    public function testCountUsersByCourseCategoryCountsDistinctSubscribedUsers(): void
    {
        $cat = $this->rawCategory('st_ucat');
        $c1 = $this->rawCourse('uc1', '2020-03-01 10:00:00');
        $c2 = $this->rawCourse('uc2', '2020-03-01 10:00:00');
        $this->courseInCategory($c1, $cat);
        $this->courseInCategory($c2, $cat);
        $teacher = $this->rawUser('uct', ['status' => 1]);
        $student = $this->rawUser('ucs');
        $deleted = $this->rawUser('ucd', ['active' => -2]);
        $this->courseUser($this->rawUser('uco'), $this->rawCourse('ucother', '2020-03-01 10:00:00'));
        foreach ([$teacher, $student, $deleted] as $u) {
            $this->courseUser($u, $c1);
            $this->courseUser($u, $c2);
        }

        $this->assertSame(2, Statistics::countUsers(null, 'st_ucat'), 'a user in 2 courses of the category counts once; soft-deleted excluded');
        $this->assertSame(1, Statistics::countUsers(1, 'st_ucat'));
        $this->assertSame(1, Statistics::countUsers(5, 'st_ucat'));
        $this->assertSame(0, Statistics::countUsers(null, 'st_nope'));
    }

    public function testCountUsersIsScopedToTheCurrentUrlWhenMultiUrl(): void
    {
        $second = $this->enableMultiUrl();
        $before = Statistics::countUsers();
        $this->rawUser('mu1', ['url' => 1]);
        $this->rawUser('mu2', ['url' => $second]);
        $this->rawUser('mu3', ['url' => null]);
        $this->rawUser('mu4', ['url' => 1, 'active' => -2]);

        $this->assertSame($before + 1, Statistics::countUsers());

        $cat = $this->rawCategory('st_mucat');
        $c = $this->rawCourse('mucat', '2020-03-01 10:00:00');
        $this->courseInCategory($c, $cat);
        $this->courseUser($this->rawUser('mu5', ['url' => 1]), $c);
        $this->courseUser($this->rawUser('mu6', ['url' => $second]), $c);
        $this->assertSame(1, Statistics::countUsers(null, 'st_mucat'));
    }

    // -------------------------------------------------- getCoursesWithActivity

    public function testGetCoursesWithActivityIsDistinctAndBetweenIsInclusive(): void
    {
        $u = $this->rawUser('cwa');
        $this->rawLastAccess($u, '2020-05-01 00:00:00', 1);
        $this->rawLastAccess($u, '2020-05-31 23:59:59', 1);
        $this->rawLastAccess($u, '2020-05-15 12:00:00', 2);
        $this->rawLastAccess($u, '2020-06-01 00:00:00', 3);

        $rows = Statistics::getCoursesWithActivity('2020-05-01 00:00:00', '2020-05-31 23:59:59');
        $ids = array_map(static fn (array $r): int => (int) $r['c_id'], $rows);
        sort($ids);
        $this->assertSame([1, 2], $ids, 'course 1 once despite two accesses; both limits inclusive; course 3 outside');

        $this->assertSame([], Statistics::getCoursesWithActivity('2021-01-01 00:00:00', '2021-01-02 00:00:00'));
    }

    public function testGetCoursesWithActivityIsScopedToTheCurrentUrlWhenMultiUrl(): void
    {
        $second = $this->enableMultiUrl();
        $c2 = $this->rawCourse('act', '2020-01-01 00:00:00', 2, 14, $second);
        $u = $this->rawUser('cwa2');
        $this->rawLastAccess($u, '2020-05-10 00:00:00', 1);
        $this->rawLastAccess($u, '2020-05-10 00:00:00', $c2);

        $rows = Statistics::getCoursesWithActivity('2020-05-01 00:00:00', '2020-05-31 23:59:59');
        $this->assertSame([1], array_map(static fn (array $r): int => (int) $r['c_id'], $rows));
    }

    // ------------------------------------------------------------- activities

    public function testGetNumberOfActivitiesCountsOnlyNonDeletedUsersAndHonoursKeyword(): void
    {
        $base = Statistics::getNumberOfActivities();
        $u = $this->rawUser('act');
        $gone = $this->rawUser('actgone', ['active' => -2]);
        $this->rawDefault($u, '2020-05-01 10:00:00', 'st_ev_a', 'st_vt', 'st_val_one');
        $this->rawDefault($u, '2020-05-02 10:00:00', 'st_ev_b', 'st_vt', 'st_val_two', 1, 0);
        $this->rawDefault($u, '2020-05-03 10:00:00', 'st_ev_b', 'st_vt', 'st_val_three', 1, 7);
        $this->rawDefault($u, '2020-05-04 10:00:00', 'st_ev_c', 'st_vt', 'st_val_four', 2, 0);
        $this->rawDefault($gone, '2020-05-05 10:00:00', 'st_ev_a', 'st_vt', 'st_val_gone');

        $this->assertSame($base + 4, Statistics::getNumberOfActivities(), 'the soft-deleted user is excluded');

        $_GET['keyword'] = 'st_ev_b';
        $this->assertSame(2, Statistics::getNumberOfActivities());
        $_GET['keyword'] = '  st_val_o ';
        $this->assertSame(1, Statistics::getNumberOfActivities(), 'keyword is trimmed and matched with LIKE on the value');
        $_GET['keyword'] = $this->username($u);
        $this->assertSame(4, Statistics::getNumberOfActivities(), 'keyword also matches the username');
        $_GET['keyword'] = 'st_vt';
        $this->assertSame(4, Statistics::getNumberOfActivities(), 'and the value type');

        $_GET['keyword'] = 'st_ev_';
        $this->assertSame(1, Statistics::getNumberOfActivities(1), 'course filter, default session 0: only the base-course row');
    }

    public function testGetNumberOfActivitiesCourseAndSessionFilter(): void
    {
        $u = $this->rawUser('actcs');
        $this->rawDefault($u, '2020-05-02 10:00:00', 'st_ev_b', 'st_vt', 'st_val_two', 1, 0);
        $this->rawDefault($u, '2020-05-03 10:00:00', 'st_ev_b', 'st_vt', 'st_val_three', 1, 7);
        $this->rawDefault($u, '2020-05-03 11:00:00', 'st_ev_b', 'st_vt', 'st_val_null', 1, null);
        $this->rawDefault($u, '2020-05-04 10:00:00', 'st_ev_c', 'st_vt', 'st_val_four', 2, 0);
        $_GET['keyword'] = 'st_ev_';

        // api_get_session_condition(0) restricts to the base course: session_id = 0 OR NULL.
        $this->assertSame(2, Statistics::getNumberOfActivities(1, 0));
        $this->assertSame(1, Statistics::getNumberOfActivities(1, 7));
        $this->assertSame(1, Statistics::getNumberOfActivities(2, 0));
        $this->assertSame(0, Statistics::getNumberOfActivities(3, 0));
        // an array (empty SortableTable param) means "no course"
        $this->assertSame(4, Statistics::getNumberOfActivities([]));
    }

    public function testGetActivitiesDataOrderingPagingAndRowShaping(): void
    {
        $a = $this->rawUser('acta', ['firstname' => 'Aa', 'lastname' => 'Aa']);
        $b = $this->rawUser('actb', ['firstname' => 'Bb', 'lastname' => 'Bb']);
        $this->rawDefault($a, '2020-05-01 10:00:00', 'st_ev_1', 'st_vt', 'st_val_1', 1, 0);
        $this->rawDefault($b, '2020-05-03 10:00:00', 'st_ev_3', 'st_vt', 'st_val_3', null, null);
        $this->rawDefault($a, '2020-05-02 10:00:00', 'st_ev_2', 'st_vt', 'st_val_2', 2, 5);
        $_GET['keyword'] = 'st_ev_';

        $rows = Statistics::getActivitiesData(0, 10, 0, 'ASC');
        $this->assertSame(['st_ev_3', 'st_ev_2', 'st_ev_1'], array_column($rows, 0), 'column 0 = default order: most recent first, whatever the direction');

        $rows = Statistics::getActivitiesData(0, 10, 1, 'ASC');
        $this->assertCount(3, $rows);
        $rows = Statistics::getActivitiesData(0, 10, 7, 'ASC');
        $this->assertSame(['st_ev_1', 'st_ev_2', 'st_ev_3'], array_column($rows, 0), 'col7 is default_date');
        $rows = Statistics::getActivitiesData(0, 10, 7, 'desc');
        $this->assertSame(['st_ev_3', 'st_ev_2', 'st_ev_1'], array_column($rows, 0), 'direction is case-insensitive');
        $rows = Statistics::getActivitiesData(0, 10, 7, 'sideways');
        $this->assertSame(['st_ev_3', 'st_ev_2', 'st_ev_1'], array_column($rows, 0), 'unknown direction falls back to DESC');

        $rows = Statistics::getActivitiesData(1, 1, 7, 'ASC');
        $this->assertSame(['st_ev_2'], array_column($rows, 0), 'LIMIT from, count');

        $rows = Statistics::getActivitiesData(0, 10, 5, 'ASC');
        $this->assertSame(['st_ev_1', 'st_ev_2', 'st_ev_3'], array_column($rows, 0), 'col5 sorts by username (a before b)');
        $this->assertSame($this->username($a), strip_tags((string) $rows[0][5]));
        $this->assertStringContainsString('admin/course_edit.php?id=1', (string) $rows[0][3]);
        $this->assertSame('-', $rows[0][4], 'session 0 is "no session"');
        $this->assertStringContainsString('resume_session.php?id_session=5', (string) $rows[1][4]);
        $this->assertSame($this->username($b), strip_tags((string) $rows[2][5]));
        $this->assertSame('-', $rows[2][3]);
    }

    public function testGetActivitiesDataCourseColumnDashesAndDateShape(): void
    {
        $a = $this->rawUser('actd');
        $this->rawDefault($a, '2020-05-03 10:30:00', 'st_ev_9', 'st_vt', 'st_val_9', null, null);
        $_GET['keyword'] = 'st_ev_9';
        $this->setSetting('tracking.hide_ip_address', 'false');

        $rows = Statistics::getActivitiesData(0, 10, 0, 'DESC');
        $this->assertCount(1, $rows);
        $this->assertSame('-', $rows[0][3], 'no course');
        $this->assertSame('-', $rows[0][4], 'no session');
        $this->assertSame('2020-05-03 10:30:00', $rows[0][7], 'UTC platform timezone: local time == stored time');
        $this->assertSame(get_lang('Unknown'), $rows[0][6], 'no login row to take the IP from');
        $this->assertSame('st_val_9', $rows[0][2]);

        $this->setSetting('tracking.hide_ip_address', 'true');
        $rows = Statistics::getActivitiesData(0, 10, 0, 'DESC');
        $this->assertSame('', $rows[0][6], 'hidden IPs are blank, not "Unknown"');
    }

    public function testGetActivitiesDataUnserializesObjectAndArrayValues(): void
    {
        $a = $this->rawUser('actser');
        $this->rawDefault($a, '2020-05-03 10:30:00', 'st_ev_ser', 'st_array', serialize(['k1' => 'v1', 'k2' => 'v2']));
        $this->rawDefault($a, '2020-05-03 10:31:00', 'st_ev_ser', 'st_object', 'not serialized at all');
        $_GET['keyword'] = 'st_ev_ser';

        $rows = Statistics::getActivitiesData(0, 10, 7, 'ASC');
        // PINNED QUIRK: implode_with_key() leaves a trailing separator ("v2 ,  ") and pads the pairs.
        $this->assertSame('k1 : v1 ,  k2 : v2 ,  ', $rows[0][2], 'arrays are flattened with implode_with_key');
        $this->assertSame('not serialized at all', $rows[1][2], 'unserialize failure keeps the original text');
    }

    public function testActivitiesCountAndDataAgreeOnSoftDeletedUsersInMultiUrl(): void
    {
        $this->enableMultiUrl();
        $live = $this->rawUser('mact');
        $gone = $this->rawUser('mactgone', ['active' => -2]);
        $this->rawDefault($live, '2020-05-03 10:30:00', 'st_ev_m', 'st_vt', 'st_val_live');
        $this->rawDefault($gone, '2020-05-03 10:31:00', 'st_ev_m', 'st_vt', 'st_val_gone');
        $_GET['keyword'] = 'st_ev_m';

        // The count query excludes active = -2 (soft-deleted) in multi-URL like in single-URL ...
        $this->assertSame(1, Statistics::getNumberOfActivities());
        // ... and so must the data query (it used to exclude active = -1 only in multi-URL, listing the
        // soft-deleted user's row: the pager showed 1 item while the page held 2).
        $this->assertSame(['st_val_live'], array_column(Statistics::getActivitiesData(0, 10, 0, 'DESC'), 2));
    }

    // ------------------------------------------------------------- login stats

    public function testPrintLoginStatsByMonthGroupsAndOrdersMostRecentFirst(): void
    {
        $u = $this->rawUser('lm');
        $this->rawLogin($u, '2001-03-05 10:00:00', '2001-03-05 10:10:00');
        $this->rawLogin($u, '2001-03-31 23:59:59', null);
        $this->rawLogin($u, '2001-05-01 00:00:00', null);
        $this->rawLogin($u, '2001-05-10 09:00:00', null);
        $this->rawLogin($u, '2001-05-31 23:59:59', null);

        $months = api_get_months_long();
        $rows = $this->statRows(Statistics::printLoginStats('month'));
        $this->assertSame(['2001 '.$months[4] => '3', '2001 '.$months[2] => '2'], $rows);
        $this->assertSame(['2001 '.$months[4], '2001 '.$months[2]], array_keys($rows), 'ORDER BY login_date DESC');
    }

    public function testPrintLoginStatsByHourAndByWeekday(): void
    {
        $u = $this->rawUser('lh');
        // 2001-03-05 is a Monday, 2001-03-11 a Sunday
        $this->rawLogin($u, '2001-03-05 09:05:00', null);
        $this->rawLogin($u, '2001-03-05 09:55:00', null);
        $this->rawLogin($u, '2001-03-11 14:00:00', null);

        $rows = $this->statRows(Statistics::printLoginStats('hour'));
        $this->assertSame(['09' => '2', '14' => '1'], $rows);

        $days = api_get_week_days_long();
        $rows = $this->statRows(Statistics::printLoginStats('day'));
        $this->assertSame([$days[0] => '1', $days[1] => '2'], $rows, 'DATE_FORMAT %w: 0 = Sunday, sorted by weekday number');
        $this->assertSame([$days[0], $days[1]], array_keys($rows));
    }

    public function testPrintLoginStatsLastXTableCountsOnlyTheLastPeriod(): void
    {
        $u = $this->rawUser('llx');
        $this->rawLogin($u, '2001-03-05 09:05:00', null);
        $this->rawLogin($u, $this->dayAgo(0, '00:00:01'), null);
        $this->rawLogin($u, $this->dayAgo(3, '00:00:01'), null);

        $html = Statistics::printLoginStats('day');
        $tables = explode('<table', $html);
        $this->assertCount(3, $tables, 'a "last logins" table then the "all logins" table');
        $lastRows = $this->statRows('<table'.$tables[1], false);
        $allRows = $this->statRows('<table'.$tables[2], false);
        $this->assertSame(3, array_sum(array_map('intval', $allRows)));
        $this->assertSame(2, array_sum(array_map('intval', $lastRows)), 'INTERVAL 1 WEEK from now: the 2001 login is out');
    }

    public function testPrintLoginStatsIsScopedToTheCurrentUrlWhenMultiUrl(): void
    {
        $second = $this->enableMultiUrl();
        $mine = $this->rawUser('lu1', ['url' => 1]);
        $theirs = $this->rawUser('lu2', ['url' => $second]);
        $this->rawLogin($mine, '2001-03-05 09:05:00', null);
        $this->rawLogin($theirs, '2001-03-05 09:06:00', null);
        $this->rawLogin($theirs, '2001-03-05 10:06:00', null);

        $this->assertSame(['09' => '1'], $this->statRows(Statistics::printLoginStats('hour')));
    }

    public function testRecentLoginStatsCountOnlyFinishedSessionsPerPeriod(): void
    {
        $u1 = $this->rawUser('rl1');
        $u2 = $this->rawUser('rl2');
        // two sessions of u1 today, one of u2 3 days ago, one of u1 20 days ago, one 100 days ago
        $this->rawLogin($u1, $this->dayAgo(0, '00:00:01'), $this->dayAgo(0, '00:10:01'));
        $this->rawLogin($u1, $this->dayAgo(0, '00:00:05'), $this->dayAgo(0, '00:10:05'));
        $this->rawLogin($u2, $this->dayAgo(3), $this->dayAgo(3, '12:10:00'));
        $this->rawLogin($u1, $this->dayAgo(20), $this->dayAgo(20, '12:10:00'));
        $this->rawLogin($u1, $this->dayAgo(100), $this->dayAgo(100, '12:10:00'));
        // never counted without a minimum duration: logout NULL, and logout == login
        $this->rawLogin($u1, $this->dayAgo(0, '00:00:09'), null);
        $this->rawLogin($u2, $this->dayAgo(0, '00:00:10'), $this->dayAgo(0, '00:00:10'));

        $all = array_values($this->recentRows(Statistics::printRecentLoginStats(false, 0)));
        $this->assertSame(['2', '3', '4', '5'], $all, 'today, last 7 days, last 31 days, total');

        $distinct = array_values($this->recentRows(Statistics::printRecentLoginStats(true, 0)));
        $this->assertSame(['1', '2', '2', '2'], $distinct, 'DISTINCT login_user_id');

        // 5 minutes: every finished session lasted 10 minutes (600 s > 300 s); no NULL logout, no zero length
        $this->assertSame(['2', '3', '4', '5'], array_values($this->recentRows(Statistics::printRecentLoginStats(false, 5))));
        // PINNED: the duration comparison is strict (600 s > 600 s is false)
        $this->assertSame(['0', '0', '0', '0'], array_values($this->recentRows(Statistics::printRecentLoginStats(false, 10))));
        // custom periods
        $custom = $this->recentRows(Statistics::printRecentLoginStats(false, 0, [1, 30]));
        $this->assertSame(['2', '4', '5'], array_values($custom));
    }

    /**
     * @return array<string, string> label (without the date span) => number
     */
    private function recentRows(string $html): array
    {
        $rows = $this->statRows($html);
        $out = [];
        foreach ($rows as $label => $number) {
            $out[trim((string) preg_replace('#\s*\[.*$#s', '', $label))] = $number;
        }

        return $out;
    }

    public function testGetRecentLoginStatsFillsDaysAndCountsPerDay(): void
    {
        $u1 = $this->rawUser('gl1');
        $u2 = $this->rawUser('gl2');
        $this->rawLogin($u1, $this->dayAgo(0, '00:00:01'), $this->dayAgo(0, '00:10:01'));
        $this->rawLogin($u1, $this->dayAgo(0, '00:00:05'), $this->dayAgo(0, '00:10:05'));
        $this->rawLogin($u2, $this->dayAgo(3), $this->dayAgo(3, '12:10:00'));
        $this->rawLogin($u1, $this->dayAgo(3, '13:00:00'), $this->dayAgo(3, '13:10:00'));
        $this->rawLogin($u1, $this->dayAgo(20), $this->dayAgo(20, '12:10:00'));
        $this->rawLogin($u1, $this->dayAgo(100), $this->dayAgo(100, '12:10:00'));
        $this->rawLogin($u1, $this->dayAgo(2), null);

        $stats = Statistics::getRecentLoginStats(false, 0);
        $today = gmdate('m-d');
        $d3 = gmdate('m-d', strtotime('-3 days'));
        $d20 = gmdate('m-d', strtotime('-20 days'));
        $this->assertSame(2, (int) $stats[$today]);
        $this->assertSame(2, (int) $stats[$d3]);
        $this->assertSame(1, (int) $stats[$d20]);
        $this->assertSame(0, (int) $stats[gmdate('m-d', strtotime('-2 days'))], 'NULL logout is not counted');
        $this->assertSame(5, array_sum($stats), 'the 100-day-old login is out of the 31-day window');
        // 31 zero-filled days (end date excluded by buildDatesArray) + today, which only exists because it has rows
        $this->assertCount(32, $stats);

        $distinct = Statistics::getRecentLoginStats(true, 0);
        $this->assertSame(1, (int) $distinct[$today]);
        $this->assertSame(2, (int) $distinct[$d3]);

        $this->assertSame(0, array_sum(Statistics::getRecentLoginStats(false, 15)), 'min duration 15 min excludes all 10 min sessions');
        $this->assertSame(5, array_sum(Statistics::getRecentLoginStats(false, 5)));
    }

    // -------------------------------------------------------------- tool stats

    public function testGetToolsStatsCountsPerToolOrderedByUsageAndIgnoresUnknownTools(): void
    {
        $u = $this->rawUser('tool');
        for ($i = 0; $i < 3; $i++) {
            $this->rawAccess($u, '2020-05-01 10:00:0'.$i, 1, 'quiz');
        }
        for ($i = 0; $i < 2; $i++) {
            $this->rawAccess($u, '2020-05-01 11:00:0'.$i, 1, 'document');
        }
        $this->rawAccess($u, '2020-05-01 12:00:00', 2, 'forum');
        $this->rawAccess($u, '2020-05-01 12:00:01', 2, 'st_custom_tool');
        $this->rawAccess($u, '2020-05-01 12:00:02', 2, 'Quiz');

        $stats = Statistics::getToolsStats();
        $this->assertSame([get_lang('Quiz', ''), get_lang('Document', ''), get_lang('Forum', '')], array_keys($stats), 'ordered by count desc');
        $this->assertSame([4, 2, 1], array_values($stats), 'MySQL IN() is case-insensitive: "Quiz" counts with quiz');
    }

    public function testGetToolsStatsIsScopedToTheCurrentUrlWhenMultiUrl(): void
    {
        $second = $this->enableMultiUrl();
        $c2 = $this->rawCourse('tool', '2020-01-01 00:00:00', 2, 14, $second);
        $u = $this->rawUser('tool2');
        $this->rawAccess($u, '2020-05-01 10:00:00', 1, 'quiz');
        $this->rawAccess($u, '2020-05-01 10:00:01', $c2, 'quiz');
        $this->rawAccess($u, '2020-05-01 10:00:02', $c2, 'quiz');
        $this->rawAccess($u, '2020-05-01 10:00:03', $c2, 'link');

        $this->assertSame([get_lang('Quiz', '') => 1], Statistics::getToolsStats());
    }

    // -------------------------------------------------- courses per language

    public function testPrintCourseByLanguageStatsCountsPerIsoCodeOrderedDescending(): void
    {
        $iso = fn (int $id): string => (string) $this->conn()->fetchOne('SELECT isocode FROM language WHERE id = ?', [$id]);
        $before = Statistics::printCourseByLanguageStats();
        // the two fixture courses use language 14
        $this->rawCourse('l1', '2020-01-01 00:00:00', 2, 1);
        $this->rawCourse('l2', '2020-01-01 00:00:00', 2, 1);
        $this->rawCourse('l3', '2020-01-01 00:00:00', 2, 1);
        $this->rawCourse('l4', '2020-01-01 00:00:00', 2, 1);
        $this->rawCourse('l5', '2020-01-01 00:00:00', 2, 2);

        $after = Statistics::printCourseByLanguageStats();
        $this->assertSame(($before[$iso(1)] ?? 0) + 4, (int) $after[$iso(1)]);
        $this->assertSame(($before[$iso(2)] ?? 0) + 1, (int) $after[$iso(2)]);
        $this->assertSame(($before[$iso(14)] ?? 0), (int) $after[$iso(14)]);
        $counts = array_map('intval', array_values($after));
        $sorted = $counts;
        rsort($sorted);
        $this->assertSame($sorted, $counts, 'ORDER BY number_of_courses DESC');
    }

    public function testPrintCourseByLanguageStatsIsScopedToTheCurrentUrlWhenMultiUrl(): void
    {
        $second = $this->enableMultiUrl();
        $iso = (string) $this->conn()->fetchOne('SELECT isocode FROM language WHERE id = 3');
        $before = Statistics::printCourseByLanguageStats();
        $this->rawCourse('lu1', '2020-01-01 00:00:00', 2, 3, 1);
        $this->rawCourse('lu2', '2020-01-01 00:00:00', 2, 3, $second);

        $after = Statistics::printCourseByLanguageStats();
        $this->assertSame(($before[$iso] ?? 0) + 1, (int) $after[$iso]);
    }

    // ----------------------------------------------------------- user pictures

    public function testPrintUserPicturesStatsCountsPictureUriAndIgnoresSoftDeleted(): void
    {
        $rows = $this->statRows(Statistics::printUserPicturesStats());
        $no = (int) $rows[get_lang('No')];
        $yes = (int) $rows[get_lang('Yes')];

        $this->rawUser('p1', ['picture_uri' => 'a.png']);
        $this->rawUser('p2', ['picture_uri' => '']);
        $this->rawUser('p3');
        $this->rawUser('p4', ['picture_uri' => 'b.png', 'active' => -2]);

        $rows = $this->statRows(Statistics::printUserPicturesStats());
        $this->assertSame($yes + 1, (int) $rows[get_lang('Yes')]);
        $this->assertSame($no + 2, (int) $rows[get_lang('No')]);
    }

    public function testPrintUserPicturesStatsIsScopedToTheCurrentUrlWhenMultiUrl(): void
    {
        $second = $this->enableMultiUrl();
        $rows = $this->statRows(Statistics::printUserPicturesStats());
        $yes = (int) $rows[get_lang('Yes')];
        $no = (int) $rows[get_lang('No')];
        $this->rawUser('mp1', ['picture_uri' => 'a.png', 'url' => $second]);
        $this->rawUser('mp2', ['url' => $second]);
        $this->rawUser('mp3', ['picture_uri' => 'a.png', 'url' => 1]);

        $rows = $this->statRows(Statistics::printUserPicturesStats());
        $this->assertSame($yes + 1, (int) $rows[get_lang('Yes')]);
        $this->assertSame($no, (int) $rows[get_lang('No')]);
    }

    // ---------------------------------------------------------------- messages

    public function testGetMessagesSentAndReceivedCountDistinctMessagesPerUserOrdered(): void
    {
        $a = $this->rawUser('ma');
        $b = $this->rawUser('mb');
        $c = $this->rawUser('mc');
        $gone = $this->rawUser('mgone', ['active' => -2]);
        $this->rawMessage($a, [$b, $c]);
        $this->rawMessage($a, [$b]);
        $this->rawMessage($a, [$b, $gone]);
        $this->rawMessage($a, []); // a message without recipient is still a sent message
        $this->rawMessage($b, [$a]);
        $this->rawMessage($gone, [$a]);

        $sent = Statistics::getMessages('sent');
        $this->assertSame(4, (int) $sent[$this->keyFor($sent, $a)]);
        $this->assertSame(1, (int) $sent[$this->keyFor($sent, $b)]);
        $this->assertNull($this->keyFor($sent, $gone), 'soft-deleted sender excluded');
        $this->assertNull($this->keyFor($sent, $c));
        $counts = array_map('intval', array_values($sent));
        $sorted = $counts;
        rsort($sorted);
        $this->assertSame($sorted, $counts, 'ORDER BY count_message DESC');

        $received = Statistics::getMessages('received');
        $this->assertSame(3, (int) $received[$this->keyFor($received, $b)]);
        $this->assertSame(2, (int) $received[$this->keyFor($received, $a)]);
        $this->assertSame(1, (int) $received[$this->keyFor($received, $c)]);
        $this->assertNull($this->keyFor($received, $gone));
    }

    public function testGetMessagesIsScopedToTheCurrentUrlWhenMultiUrl(): void
    {
        $second = $this->enableMultiUrl();
        $a = $this->rawUser('mua', ['url' => 1]);
        $b = $this->rawUser('mub', ['url' => $second]);
        $this->rawMessage($a, [$b]);
        $this->rawMessage($b, [$a]);

        $sent = Statistics::getMessages('sent');
        $this->assertNotNull($this->keyFor($sent, $a));
        $this->assertNull($this->keyFor($sent, $b));
        $received = Statistics::getMessages('received');
        $this->assertNotNull($this->keyFor($received, $a));
        $this->assertNull($this->keyFor($received, $b));
    }

    // ----------------------------------------------------------------- friends

    public function testGetFriendsCountsRelationsExceptHrAndOrdersByCount(): void
    {
        $a = $this->rawUser('fa');
        $b = $this->rawUser('fb');
        $c = $this->rawUser('fc');
        $this->rawFriend($a, $b);
        $this->rawFriend($a, $c);
        $this->rawFriend($a, $c, UserRelUser::USER_RELATION_TYPE_GOODFRIEND);
        $this->rawFriend($a, $b, UserRelUser::USER_RELATION_TYPE_RRHH);
        $this->rawFriend($b, $a);
        $this->rawFriend($c, $a, UserRelUser::USER_RELATION_TYPE_RRHH);

        $friends = Statistics::getFriends();
        $this->assertSame(3, (int) $friends[$this->keyFor($friends, $a)], 'HR relations (type 7) are not friends');
        $this->assertSame(1, (int) $friends[$this->keyFor($friends, $b)]);
        $this->assertNull($this->keyFor($friends, $c), 'its only relation is an HR one');
        $this->assertSame($this->keyFor($friends, $a), array_key_first($friends), 'most friends first');
    }

    public function testGetFriendsListsSoftDeletedUsersAsAnAnonymousRow(): void
    {
        $a = $this->rawUser('ffa');
        $gone = $this->rawUser('ffgone', ['active' => -2]);
        $this->rawFriend($a, $gone);
        $this->rawFriend($gone, $a);
        $this->rawFriend($gone, $a, UserRelUser::USER_RELATION_TYPE_GOODFRIEND);

        $friends = Statistics::getFriends();
        $this->assertSame(1, (int) $friends[$this->keyFor($friends, $a)]);
        // PINNED QUIRK: the soft-deleted filter sits in the LEFT JOIN's ON clause, so the user is not removed:
        // its relations are still counted, under a blank "<br />()" key (names/username are NULL).
        $this->assertNull($this->keyFor($friends, $gone));
        $this->assertSame(2, (int) $friends[api_get_person_name('', '').'<br />()']);
    }

    public function testGetFriendsIsScopedToTheCurrentUrlWhenMultiUrl(): void
    {
        $second = $this->enableMultiUrl();
        $a = $this->rawUser('fua', ['url' => 1]);
        $b = $this->rawUser('fub', ['url' => $second]);
        $this->rawFriend($a, $b);
        $this->rawFriend($b, $a);

        $friends = Statistics::getFriends();
        $this->assertNotNull($this->keyFor($friends, $a));
        $this->assertNull($this->keyFor($friends, $b));
    }

    // ------------------------------------------------------ not logged in stats

    public function testPrintUsersNotLoggedInStatsSubtractsDistinctLoggedUsersFromTheTotal(): void
    {
        $total = Statistics::countUsers();
        $u1 = $this->rawUser('nl1');
        $u2 = $this->rawUser('nl2');
        $u3 = $this->rawUser('nl3');
        $this->rawUser('nl4');
        $total += 4;
        $this->rawLogin($u1, $this->dayAgo(0, '00:00:01'), null);
        $this->rawLogin($u1, $this->dayAgo(0, '00:00:02'), null);
        $this->rawLogin($u2, $this->dayAgo(5, '12:00:00'), null);
        $this->rawLogin($u3, $this->dayAgo(100, '12:00:00'), null);

        $rows = $this->statRows(Statistics::printUsersNotLoggedInStats());
        $this->assertSame(
            [
                get_lang('This day') => (string) ($total - 1),
                get_lang('In the last 7 days') => (string) ($total - 2),
                get_lang('In the last 31 days') => (string) ($total - 2),
                sprintf(get_lang('Last %d months'), 6) => (string) ($total - 3),
                get_lang('Never connected') => (string) ($total - 3),
            ],
            array_map(static fn ($v): string => str_replace('.', '', (string) $v), $rows)
        );
    }

    // ---------------------------------------------------------- logins by date

    public function testGetLoginsByDateSumsSecondsPerUserWithinInclusiveRange(): void
    {
        $a = $this->rawUser('lda', ['firstname' => 'Ada', 'lastname' => 'Lo']);
        $b = $this->rawUser('ldb');
        $gone = $this->rawUser('ldg', ['active' => -2]);
        $this->rawLogin($a, '2020-06-01 00:00:00', '2020-06-01 00:10:00'); // 600
        $this->rawLogin($a, '2020-06-30 23:59:59', '2020-07-01 00:00:59'); // 60, logout outside range is fine
        $this->rawLogin($a, '2020-07-01 00:00:00', '2020-07-01 05:00:00'); // login outside range
        $this->rawLogin($b, '2020-06-15 10:00:00', null);                    // NULL logout: SUM ignores it
        $this->rawLogin($gone, '2020-06-15 10:00:00', '2020-06-15 11:00:00');

        $rows = Statistics::getLoginsByDate('2020-06-01', '2020-06-30');
        $byId = [];
        foreach ($rows as $r) {
            $byId[(int) $r['id']] = $r;
        }
        $this->assertCount(2, $rows, 'the soft-deleted user is excluded');
        $this->assertSame('660', (string) $byId[$a]['time_count']);
        $this->assertSame('Ada', $byId[$a]['firstname']);
        $this->assertSame('Lo', $byId[$a]['lastname']);
        $this->assertSame($this->username($a), $byId[$a]['username']);
        $this->assertNull($byId[$b]['time_count'], 'PINNED: only NULL logouts give a NULL total (rendered as 0 by api_time_to_hms)');
        $this->assertArrayNotHasKey($gone, $byId);
    }

    public function testGetLoginsByDateIsScopedToTheCurrentUrlWhenMultiUrl(): void
    {
        $second = $this->enableMultiUrl();
        $a = $this->rawUser('ldua', ['url' => 1]);
        $b = $this->rawUser('ldub', ['url' => $second]);
        $this->rawLogin($a, '2020-06-01 10:00:00', '2020-06-01 10:01:00');
        $this->rawLogin($b, '2020-06-01 10:00:00', '2020-06-01 10:01:00');

        $rows = Statistics::getLoginsByDate('2020-06-01', '2020-06-01');
        $this->assertSame([$a], array_map(static fn (array $r): int => (int) $r['id'], $rows));
    }

    // --------------------------------------------------------- registrations

    public function testGetNewUserRegistrationsGroupsPerDayWithInclusiveBounds(): void
    {
        $this->rawUser('r1', ['created_at' => '2019-06-01 00:00:00']);
        $this->rawUser('r2', ['created_at' => '2019-06-01 23:59:59']);
        $this->rawUser('r3', ['created_at' => '2019-06-02 12:00:00']);
        $this->rawUser('r4', ['created_at' => '2019-06-03 00:00:01']);
        $this->rawUser('r5', ['created_at' => '2019-05-31 23:59:59']);
        // soft-deleted users are still registrations
        $this->rawUser('r6', ['created_at' => '2019-06-02 13:00:00', 'active' => -2]);

        $this->assertSame(
            [['date' => '2019-06-01', 'count' => 2], ['date' => '2019-06-02', 'count' => 2]],
            Statistics::getNewUserRegistrations('2019-06-01 00:00:00', '2019-06-03 00:00:00')
        );
        $this->assertSame([], Statistics::getNewUserRegistrations('2018-01-01 00:00:00', '2018-01-02 00:00:00'));
    }

    public function testGetUserRegistrationsByCreatorNamesTheCreators(): void
    {
        $c1 = $this->rawUser('cr1', ['firstname' => 'Creator', 'lastname' => 'One']);
        $c2 = $this->rawUser('cr2', ['firstname' => '', 'lastname' => '']);
        $this->rawUser('rc1', ['created_at' => '2019-06-01 10:00:00', 'creator_id' => $c1]);
        $this->rawUser('rc2', ['created_at' => '2019-06-02 10:00:00', 'creator_id' => $c1]);
        $this->rawUser('rc3', ['created_at' => '2019-06-02 11:00:00', 'creator_id' => $c2]);
        $this->rawUser('rc4', ['created_at' => '2019-06-02 12:00:00', 'creator_id' => null]);
        $this->rawUser('rc5', ['created_at' => '2019-07-02 12:00:00', 'creator_id' => $c1]);

        $this->assertSame(
            [['name' => 'Creator One', 'count' => 2]],
            Statistics::getUserRegistrationsByCreator('2019-06-01 00:00:00', '2019-06-30 23:59:59'),
            'creator without a name is dropped, NULL creator ignored, out-of-range ignored'
        );
    }

    // ------------------------------------------------------------ certificates

    public function testCountCertificatesByQuarterRangesAndOpenBounds(): void
    {
        $ins = function (string $date): void {
            $this->conn()->executeStatement(
                'INSERT INTO gradebook_certificate (cat_id, user_id, score_certificate, created_at, publish) VALUES (NULL, 1, 50, ?, 0)',
                [$date]
            );
        };
        $total = Statistics::countCertificatesByQuarter();
        $ins('2020-01-01 00:00:00');
        $ins('2020-03-31 23:59:59');
        $ins('2020-04-01 00:00:00');
        $ins('2019-12-31 23:59:59');

        $this->assertSame($total + 4, Statistics::countCertificatesByQuarter());
        $this->assertSame(2, Statistics::countCertificatesByQuarter('2020-01-01', '2020-03-31'));
        $this->assertSame(3, Statistics::countCertificatesByQuarter('2020-01-01'), 'only a lower bound: nothing else is older than 2026 in the table');
        $this->assertSame(1, Statistics::countCertificatesByQuarter(null, '2019-12-31'));
        $this->assertSame(0, Statistics::countCertificatesByQuarter('2021-01-01', '2021-03-31'));
    }

    // ----------------------------------------------------- sessions by duration

    public function testGetSessionsByDurationBucketsWithStrictLowerBounds(): void
    {
        $u = $this->rawUser('sd');
        $base = '2020-08-10 10:00:00';
        $durations = [0, 300, 301, 600, 601, 900, 901, 1800, 1801, 3600, 3601, 90000];
        foreach ($durations as $d) {
            $this->rawLogin($u, $base, gmdate('Y-m-d H:i:s', strtotime($base) + $d));
        }
        // outside: NULL logout, login before range, logout after range
        $this->rawLogin($u, $base, null);
        $this->rawLogin($u, '2020-08-09 23:59:59', '2020-08-10 10:00:00');
        $this->rawLogin($u, '2020-08-31 23:00:00', '2020-09-01 00:00:00');

        $res = Statistics::getSessionsByDuration('2020-08-10', '2020-08-31');
        // PINNED: each bucket's lower bound is strict (> 300 s is "5", 300 s itself is "0")
        $this->assertSame(
            ['0' => 2, '5' => 2, '10' => 2, '15' => 2, '30' => 2, '60' => 2],
            $res
        );
        $this->assertSame(
            ['0' => 0, '5' => 0, '10' => 0, '15' => 0, '30' => 0, '60' => 0],
            Statistics::getSessionsByDuration('', '2020-08-31')
        );
    }

    public function testGetSessionsByDurationIsScopedToTheCurrentUrlWhenMultiUrl(): void
    {
        $second = $this->enableMultiUrl();
        $a = $this->rawUser('sda', ['url' => 1]);
        $b = $this->rawUser('sdb', ['url' => $second]);
        $this->rawLogin($a, '2020-08-10 10:00:00', '2020-08-10 10:20:00');
        $this->rawLogin($b, '2020-08-10 10:00:00', '2020-08-10 10:20:00');
        $this->rawLogin($b, '2020-08-10 10:00:00', '2020-08-10 10:20:00');

        $res = Statistics::getSessionsByDuration('2020-08-10', '2020-08-10');
        $this->assertSame(1, $res['15']);
    }

    // ---------------------------------------------------- (un)subscriptions

    public function testSubscriptionsAndUnsubscriptionsByDay(): void
    {
        $u = $this->rawUser('sub');
        $this->rawDefault($u, '2020-09-01 00:00:00', 'user_subscribed');
        $this->rawDefault($u, '2020-09-01 23:59:59', 'user_subscribed');
        $this->rawDefault($u, '2020-09-02 10:00:00', 'user_subscribed');
        $this->rawDefault($u, '2020-09-03 00:00:00', 'user_subscribed');
        $this->rawDefault($u, '2020-09-02 11:00:00', 'user_unsubscribed');
        $this->rawDefault($u, '2020-09-02 12:00:00', 'session_user_deleted');
        $this->rawDefault($u, '2020-09-04 12:00:00', 'session_user_deleted');
        $this->rawDefault($u, '2020-09-02 13:00:00', 'st_other');

        $subs = Statistics::getSubscriptionsByDay('2020-09-01', '2020-09-03');
        $this->assertSame(
            [['date' => '2020-09-01', 'count' => 2], ['date' => '2020-09-02', 'count' => 1], ['date' => '2020-09-03', 'count' => 1]],
            array_map(static fn (array $r): array => ['date' => $r['date'], 'count' => (int) $r['count']], $subs)
        );
        $unsubs = Statistics::getUnsubscriptionsByDay('2020-09-01', '2020-09-03');
        $this->assertSame(
            [['date' => '2020-09-02', 'count' => 2]],
            array_map(static fn (array $r): array => ['date' => $r['date'], 'count' => (int) $r['count']], $unsubs),
            'both event types are summed; 09-04 is out of range'
        );
    }

    // ---------------------------------------------- non registered active users

    public function testGetNonRegisteredActiveUsersInCourseOutsideASession(): void
    {
        $enrolled = $this->rawUser('nr1');
        $free = $this->rawUser('nr2', ['firstname' => 'Free', 'lastname' => 'Rider', 'email' => 'st_free@example.com']);
        $free2 = $this->rawUser('nr3');
        $gone = $this->rawUser('nr4', ['active' => -2]);
        $this->courseUser($enrolled, 1);
        $this->courseUser($free2, 2);
        $this->rawAccess($enrolled, '2020-10-01 10:00:00', 1, 'document');
        $this->rawAccess($free, '2020-10-01 10:00:00', 1, 'document');
        $this->rawAccess($free, '2020-10-05 08:30:00', 1, 'quiz');
        $this->rawAccess($free2, '2020-10-03 09:00:00', 1, 'document');
        $this->rawAccess($gone, '2020-10-02 09:00:00', 1, 'document');
        // another course and a session access are not this course in the base context
        $this->rawAccess($free, '2020-10-09 09:00:00', 2, 'document');

        $rows = Statistics::getNonRegisteredActiveUsersInCourse(1);
        $this->assertSame([$free, $free2, $gone], array_map(static fn (array $r): int => (int) $r['id'], $rows), 'ORDER BY lastAccess DESC');
        $this->assertSame('2020-10-05 08:30:00', $rows[0]['lastAccess'], 'MAX(access_date) over the user accesses');
        $this->assertSame('Free', $rows[0]['firstname']);
        $this->assertSame('st_free@example.com', $rows[0]['email']);
        // PINNED QUIRK: soft-deleted users are not filtered out ($gone is listed).
        // Course 2: $free2 is enrolled there, $enrolled and $gone never accessed it.
        $this->assertSame([$free], array_map(static fn (array $r): int => (int) $r['id'], Statistics::getNonRegisteredActiveUsersInCourse(2)));
    }

    public function testGetNonRegisteredActiveUsersInCourseInsideASession(): void
    {
        $member = $this->rawUser('ns1');
        $free = $this->rawUser('ns2');
        $this->conn()->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, duration, position)
             VALUES (\'st_sess\', 0, 0, 0, 1, 0, NULL, 0)'
        );
        $sid = (int) $this->conn()->lastInsertId();
        $this->conn()->executeStatement(
            'INSERT INTO session_rel_course_rel_user (user_id, session_id, c_id, status, visibility, legal_agreement, progress)
             VALUES (?, ?, 1, 0, 1, 0, 0)',
            [$member, $sid]
        );
        // course enrolment is irrelevant inside a session
        $this->courseUser($free, 1);
        $this->rawAccess($member, '2020-10-01 10:00:00', 1, 'document', $sid);
        $this->rawAccess($free, '2020-10-02 10:00:00', 1, 'document', $sid);
        $this->rawAccess($free, '2020-10-09 10:00:00', 1, 'document', 0);

        $rows = Statistics::getNonRegisteredActiveUsersInCourse(1, $sid);
        $this->assertSame([$free], array_map(static fn (array $r): int => (int) $r['id'], $rows));
        $this->assertSame('2020-10-02 10:00:00', $rows[0]['lastAccess'], 'only the accesses of that session');
    }

    // ---------------------------------------------------- duplicate users report

    public function testUpdateUserActiveStatusRules(): void
    {
        $u = $this->rawUser('ua');
        $gone = $this->rawUser('uagone', ['active' => -2]);

        $this->assertTrue(Statistics::updateUserActiveStatus($u, 0));
        $this->assertSame(0, (int) $this->conn()->fetchOne('SELECT active FROM user WHERE id = ?', [$u]));
        $this->assertTrue(Statistics::updateUserActiveStatus($u, 0), 'no-op when unchanged');
        $this->assertTrue(Statistics::updateUserActiveStatus($u, 1));
        $this->assertSame(1, (int) $this->conn()->fetchOne('SELECT active FROM user WHERE id = ?', [$u]));

        $this->assertThrowsAny(static fn () => Statistics::updateUserActiveStatus($u, -2), \InvalidArgumentException::class);
        $this->assertThrowsAny(static fn () => Statistics::updateUserActiveStatus($u, 2), \InvalidArgumentException::class);
        $this->assertThrowsAny(static fn () => Statistics::updateUserActiveStatus(0, 1), \InvalidArgumentException::class);
        $this->assertThrowsAny(static fn () => Statistics::updateUserActiveStatus(999999999, 1), \RuntimeException::class);
        $this->assertThrowsAny(static fn () => Statistics::updateUserActiveStatus($gone, 1), \RuntimeException::class);
        $this->assertSame(-2, (int) $this->conn()->fetchOne('SELECT active FROM user WHERE id = ?', [$gone]), 'soft-deleted stays so');
    }

    private function assertThrowsAny(callable $fn, string $class): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            $this->assertInstanceOf($class, $e);

            return;
        }
        $this->fail("Expected $class");
    }

    public function testGetDuplicateUserGroupUserIdsByName(): void
    {
        $a = $this->rawUser('da', ['firstname' => 'Dup', 'lastname' => 'Twin']);
        $b = $this->rawUser('db', ['firstname' => '  DUP ', 'lastname' => 'twin']);
        $this->rawUser('dc', ['firstname' => 'Dup', 'lastname' => 'Twin', 'active' => -2]);
        $d = $this->rawUser('dd', ['firstname' => 'Dup', 'lastname' => 'Other']);
        $e = $this->rawUser('de', ['firstname' => '', 'lastname' => '']);
        $f = $this->rawUser('df', ['firstname' => '', 'lastname' => '']);

        $this->assertSame([$a, $b], Statistics::getDuplicateUserGroupUserIds('name', $b), 'case/space-insensitive, soft-deleted ignored, sorted by id');
        $this->assertSame([], Statistics::getDuplicateUserGroupUserIds('name', $d), 'a group needs 2 users');
        $this->assertSame([], Statistics::getDuplicateUserGroupUserIds('name', $e), 'empty names never form a group');
        $this->assertNotNull($f);
        $this->assertSame([], Statistics::getDuplicateUserGroupUserIds('name', 0));
        $this->assertSame([], Statistics::getDuplicateUserGroupUserIds('name', 999999999));
        $this->assertSame([$a, $b], Statistics::getDuplicateUserGroupUserIds('bogus mode', $a), 'unknown mode = name');
    }

    public function testGetDuplicateUserGroupUserIdsByEmailAndExtraField(): void
    {
        $a = $this->rawUser('ea', ['email' => 'st_same@example.com']);
        $b = $this->rawUser('eb', ['email' => ' ST_SAME@example.com ']);
        $c = $this->rawUser('ec', ['email' => 'st_same@example.com', 'active' => -2]);
        $d = $this->rawUser('ed', ['email' => 'st_alone@example.com']);
        $e = $this->rawUser('ee', ['email' => '']);
        $f = $this->rawUser('ef', ['email' => '']);

        $this->assertSame([$a, $b], Statistics::getDuplicateUserGroupUserIds('email', $a));
        $this->assertSame([], Statistics::getDuplicateUserGroupUserIds('email', $d));
        $this->assertSame([], Statistics::getDuplicateUserGroupUserIds('email', $e), 'empty email never groups');
        $this->assertSame([], Statistics::getDuplicateUserGroupUserIds('email', $c), 'a soft-deleted target has no group');
        $this->assertNotNull($f);

        $field = (int) $this->conn()->fetchOne("SELECT id FROM extra_field WHERE item_type = 1 ORDER BY id LIMIT 1");
        $this->assertGreaterThan(0, $field);
        $val = function (int $uid, string $v) use ($field): void {
            $this->conn()->executeStatement(
                'INSERT INTO extra_field_values (field_id, field_value, item_id, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())',
                [$field, $v, $uid]
            );
        };
        $val($a, 'ST-Code-1');
        $val($b, ' st-code-1');
        $val($d, 'st-code-1');
        $val($e, 'st-unique');
        $this->assertSame([$a, $b, $d], Statistics::getDuplicateUserGroupUserIds('extra', $a, $field));
        $this->assertSame([], Statistics::getDuplicateUserGroupUserIds('extra', $e, $field));
        $this->assertSame([], Statistics::getDuplicateUserGroupUserIds('extra', $a, 0), 'extra mode needs a field id');
    }

    public function testGetDuplicateUserGroupUserIdsIsScopedToTheCurrentUrlWhenMultiUrl(): void
    {
        $second = $this->enableMultiUrl();
        $a = $this->rawUser('ua1', ['email' => 'st_url@example.com', 'url' => 1]);
        $b = $this->rawUser('ua2', ['email' => 'st_url@example.com', 'url' => 1]);
        $c = $this->rawUser('ua3', ['email' => 'st_url@example.com', 'url' => $second]);

        $this->assertSame([$a, $b], Statistics::getDuplicateUserGroupUserIds('email', $a));
        $this->assertSame([], Statistics::getDuplicateUserGroupUserIds('email', $c), 'target in another URL');
    }

    public function testReturnDuplicatedUsersTableListsGroupedRowsWithCounts(): void
    {
        $a = $this->rawUser('ta', ['email' => 'st_tbl@example.com', 'created_at' => '2019-02-01 10:00:00', 'firstname' => 'Tbl', 'lastname' => 'One']);
        $b = $this->rawUser('tb', ['email' => 'ST_TBL@example.com', 'created_at' => '2019-01-01 10:00:00', 'firstname' => 'Tbl', 'lastname' => 'Two']);
        $c = $this->rawUser('tc', ['email' => 'st_tbl@example.com', 'created_at' => '2019-01-01 10:00:00', 'firstname' => 'Tbl', 'lastname' => 'Three']);
        $this->rawUser('td', ['email' => 'st_tbl@example.com', 'active' => -2]);
        $this->courseUser($a, 1);
        $this->courseUser($a, 2);
        $this->courseUser($b, 1);

        $table = Statistics::returnDuplicatedUsersTable('email', [], 0, 'tok');
        $mine = array_values(array_filter($table->table_data, static fn (array $r): bool => in_array((int) $r[0], [$a, $b, $c], true)));
        // Group sorted by registration date then id: b (older) and c share a date, b has the lower id
        $this->assertSame([(string) $b, (string) $c, (string) $a], array_column($mine, 0));
        $this->assertSame(['Two', 'Three', 'One'], array_column($mine, 2));
        $this->assertSame(['1', '0', '2'], array_column($mine, 8), 'course counts');
        $this->assertSame(['0', '0', '0'], array_column($mine, 9), 'session counts');
        $this->assertCount(3, $mine, 'the soft-deleted duplicate is not listed');
    }

    public function testReturnDuplicatedUsersTableIsEmptyWhenNoGroupMatches(): void
    {
        $this->rawUser('te', ['email' => 'st_unique_only@example.com']);
        $table = Statistics::returnDuplicatedUsersTable('extra', [], 0, 'tok');
        $this->assertSame([], $table->table_data, 'extra mode without a field id: no query, empty table');
    }
}
