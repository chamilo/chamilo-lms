<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Database;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\UuidV4;
use TrackingCourseLog;

/**
 * The two TrackingCourseLog queries that used the removed columns user.user_id, extra_field_values.value and
 * extra_field.extra_field_type: the extra profile fields shown as columns of the course report, and the data of
 * the "total time" report. Both raised an SQL error before the fix.
 */
final class TrackingCourseLogFixCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const COURSE = 1;

    private Connection $conn;
    private int $seq = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new Session(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/tracking/total_time.php');
        $request->setSession($httpSession);
        Container::setRequest($request);

        $this->conn = $container->get('doctrine')->getConnection();
        Database::setManager($container->get('doctrine')->getManager());
        ChamiloSession::write('_real_cid', self::COURSE);
        ChamiloSession::write('sid', 0);
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        unset($GLOBALS['user_ids'], $GLOBALS['course_code'], $GLOBALS['export_csv'], $GLOBALS['session_id']);
        ChamiloSession::erase('_real_cid');
        ChamiloSession::erase('sid');
        parent::tearDown();
    }

    public function testExtraProfileFieldValuesAreReturnedPerRequestedUserOnly(): void
    {
        $field = $this->extraField('fix_text');
        $alice = $this->user('fix_alice');
        $bob = $this->user('fix_bob');
        $carol = $this->user('fix_carol');
        $this->fieldValue($field, $alice, 'alice value');
        $this->fieldValue($field, $bob, 'bob value');
        $this->fieldValue($field, $carol, 'carol value');
        // Same user, another field: must not leak in.
        $this->fieldValue($this->extraField('fix_other'), $alice, 'other field');

        $result = TrackingCourseLog::getAdditionalProfileInformationOfFieldByUser($field, [$alice, $bob]);

        $this->assertSame([$alice => ['alice value'], $bob => ['bob value']], $result);
    }

    public function testExtraProfileFieldReturnsNothingWithoutUsers(): void
    {
        $this->assertSame([], TrackingCourseLog::getAdditionalProfileInformationOfFieldByUser($this->extraField('fix_empty'), []));
    }

    public function testTotalTimeReportListsTheRequestedUsersWithoutInviteesSortedAndPaged(): void
    {
        $zed = $this->user('fix_t_zed', 'Zed');
        $amy = $this->user('fix_t_amy', 'Amy');
        $invitee = $this->user('fix_t_inv', 'Inv', INVITEE);
        $other = $this->user('fix_t_other', 'Other');
        $this->reportContext([$zed, $amy, $invitee]);

        $rows = TrackingCourseLog::getTotalTimeReport(0, 10, 2, 'ASC');

        $this->assertCount(2, $rows, 'the invitee and the user that was not requested are left out');
        $this->assertCount(9, $rows[0]);
        $this->assertStringStartsWith('fix_t_amy', (string) $rows[0][3], 'sorted by first name ascending');
        $this->assertStringStartsWith('fix_t_zed', (string) $rows[1][3]);
        $this->assertStringContainsString('student='.$zed.'&', $rows[1][8]);
        $this->assertStringContainsString('student='.$amy.'&', $rows[0][8]);
        $this->assertStringNotContainsString('student='.$other.'&', $rows[0][8].$rows[1][8]);

        $desc = TrackingCourseLog::getTotalTimeReport(0, 1, 2, 'DESC');
        $this->assertCount(1, $desc, 'the page size limits the rows');
        $this->assertStringContainsString('student='.$zed.'&', $desc[0][8]);

        $withInvited = TrackingCourseLog::getTotalTimeReport(0, 10, 2, 'ASC', true);
        $this->assertCount(3, $withInvited, 'the invited users are included on request');
    }

    public function testTotalTimeReportAcceptsASingleUserId(): void
    {
        $solo = $this->user('fix_t_solo', 'Solo');
        $this->reportContext($solo);

        $rows = TrackingCourseLog::getTotalTimeReport(0, 10, 0, 'ASC');

        $this->assertCount(1, $rows);
        $this->assertStringContainsString('student='.$solo.'&', $rows[0][8]);
    }

    /**
     * @param int|int[] $userIds
     */
    private function reportContext($userIds): void
    {
        $GLOBALS['user_ids'] = $userIds;
        $GLOBALS['course_code'] = (string) $this->conn->fetchOne('SELECT code FROM course WHERE id = ?', [self::COURSE]);
        $GLOBALS['export_csv'] = false;
        $GLOBALS['session_id'] = 0;
    }

    private function user(string $name, string $firstname = 'fix', int $status = 5): int
    {
        $name .= '_'.(++$this->seq).'_'.substr(md5((string) microtime(true)), 0, 6);
        $this->conn->executeStatement(
            "INSERT INTO user (username, username_canonical, locale, password, timezone, email, email_canonical,
                locked, expired, credentials_expired, salt, roles, status, active, uuid, created_at, updated_at,
                firstname, lastname)
             VALUES (?, ?, 'en_US', 'x', 'UTC', ?, ?, 0, 0, 0, 'x', '[]', ?, 1, ?,
                '2001-01-01 00:00:00', '2001-01-01 00:00:00', ?, 'fix')",
            [$name, $name, $name.'@example.com', $name.'@example.com', $status, UuidV4::v4()->toBinary(), $firstname]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function extraField(string $variable): int
    {
        $this->conn->executeStatement(
            'INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, visible_to_others,
                changeable, filter, auto_remove, created_at)
             VALUES (?, 1, ?, ?, 1, 1, 1, 0, 0, ?)',
            [ExtraField::USER_FIELD_TYPE, $variable.'_'.(++$this->seq), $variable, '2001-01-01 00:00:00']
        );

        return (int) $this->conn->lastInsertId();
    }

    private function fieldValue(int $fieldId, int $userId, string $value): void
    {
        $this->conn->executeStatement(
            'INSERT INTO extra_field_values (field_id, item_id, field_value, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$fieldId, $userId, $value, '2001-01-01 00:00:00', '2001-01-01 00:00:00']
        );
    }
}
