<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Database;
use Doctrine\DBAL\Connection;
use Statistics;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Multi-URL scoping of the Statistics counters, with deliberately unequal counts inside and outside the current
 * URL (the main characterization test uses 1 against 1, which an inverted URL condition cannot tell apart).
 */
final class StatisticsTier2CharacterizationTest extends AbstractApiTest
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
        ChamiloSession::write('system_timezone', 'UTC');
        ChamiloSession::erase('_user');
    }

    protected function tearDown(): void
    {
        $_GET = [];
        ChamiloSession::erase('system_timezone');
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
        $url = \array_key_exists('url', $o) ? $o['url'] : 1;
        if (null !== $url) {
            $this->conn()->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (?, ?)', [$url, $id]);
        }

        return $id;
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

    // ---------------------------------------------------------------- tests

    public function testCountCoursesByVisibilityOnlyCountsTheCoursesOfTheCurrentUrl(): void
    {
        $second = $this->enableMultiUrl();
        $this->rawCourse('t2a', '2020-03-01 10:00:00', 0, 14, 1);
        $this->rawCourse('t2b', '2020-03-01 10:00:00', 0, 14, 1);
        $this->rawCourse('t2c', '2020-03-01 10:00:00', 0, 14, $second);

        $this->assertSame(2, Statistics::countCoursesByVisibility([0]));
    }

    public function testCountUsersOnlyCountsTheUsersOfTheCurrentUrl(): void
    {
        $second = $this->enableMultiUrl();
        $before = Statistics::countUsers();
        $this->rawUser('t2a', ['url' => 1]);
        $this->rawUser('t2b', ['url' => 1]);
        $this->rawUser('t2c', ['url' => $second]);
        $this->assertSame($before + 2, Statistics::countUsers());

        $cat = $this->rawCategory('st_t2cat');
        $c = $this->rawCourse('t2cat', '2020-03-01 10:00:00');
        $this->courseInCategory($c, $cat);
        $this->courseUser($this->rawUser('t2d', ['url' => 1]), $c);
        $this->courseUser($this->rawUser('t2e', ['url' => 1]), $c);
        $this->courseUser($this->rawUser('t2f', ['url' => $second]), $c);
        $this->assertSame(2, Statistics::countUsers(null, 'st_t2cat'));
    }

    public function testPrintCourseByLanguageStatsOnlyCountsTheCoursesOfTheCurrentUrl(): void
    {
        $iso = (string) $this->conn()->fetchOne('SELECT isocode FROM language WHERE id = 14');
        $second = $this->enableMultiUrl();
        $before = Statistics::printCourseByLanguageStats()[$iso] ?? 0;
        $this->rawCourse('t2l1', '2020-03-01 10:00:00', 2, 14, 1);
        $this->rawCourse('t2l2', '2020-03-01 10:00:00', 2, 14, 1);
        $this->rawCourse('t2l3', '2020-03-01 10:00:00', 2, 14, $second);

        $this->assertSame($before + 2, Statistics::printCourseByLanguageStats()[$iso]);
    }
}
