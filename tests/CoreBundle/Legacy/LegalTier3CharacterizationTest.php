<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Database;
use Doctrine\DBAL\Connection;
use LegalManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for the read functions and sendLegal() of LegalManager (public/main/inc/lib/legal.lib.php).
 * Fixtures use language ids 900001/900002 (no such language exists, except the ones created here).
 */
final class LegalTier3CharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const LANG_A = 900001;
    private const LANG_B = 900002;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/inc/lib/legal.lib.php');
        $request->setSession($session);
        Container::setRequest($request);
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function legal(int $lang, ?int $version, string $content, int $type = 0, int $date = 1700000000): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO legal (date, content, type, changes, version, language_id) VALUES (?, ?, ?, ?, ?, ?)',
            [$date, $content, $type, 'chg_'.$content, $version, $lang]
        );

        return (int) $this->conn()->lastInsertId();
    }

    public function testGetLastVersionTakesTheHighestIdNotTheHighestVersion(): void
    {
        $this->legal(self::LANG_A, 5, 'old-high-version');
        $this->legal(self::LANG_A, 2, 'new-low-version');
        $this->legal(self::LANG_B, 9, 'other-language');

        $this->assertSame(2, LegalManager::getLastVersion(self::LANG_A));
        $this->assertSame(9, LegalManager::getLastVersion((string) self::LANG_B));
        $this->assertSame(0, LegalManager::getLastVersion(900003));
    }

    public function testGetLastVersionIsZeroWhenTheLastRowHasNoVersion(): void
    {
        $this->legal(self::LANG_A, 4, 'versioned');
        $this->legal(self::LANG_A, null, 'unversioned');

        $this->assertSame(0, LegalManager::getLastVersion(self::LANG_A));
    }

    public function testGetLastConditionTakesTheHighestVersionAndReturnsTheRawRow(): void
    {
        $this->legal(self::LANG_A, 3, 'v3');
        $id = $this->legal(self::LANG_A, 7, 'v7');
        $this->legal(self::LANG_A, 5, 'v5');
        $this->legal(self::LANG_B, 99, 'other-lang');

        $row = LegalManager::get_last_condition(self::LANG_A);

        $this->assertSame(['id', 'date', 'content', 'type', 'changes', 'version', 'language_id'], array_keys($row));
        $this->assertSame($id, (int) $row['id']);
        $this->assertSame('v7', $row['content']);
        $this->assertSame('chg_v7', $row['changes']);
        $this->assertSame(1700000000, (int) $row['date']);
        $this->assertSame(7, (int) $row['version']);
        $this->assertSame(self::LANG_A, (int) $row['language_id']);
        $this->assertSame(0, (int) $row['type']);
        $this->dumpTypes($row, 'get_last_condition');
    }

    public function testGetLastConditionOfUnknownLanguageIsFalse(): void
    {
        $this->assertFalse(LegalManager::get_last_condition(900003));
    }

    public function testHasVersion(): void
    {
        $this->legal(self::LANG_A, 3, 'v3');

        $this->assertTrue(LegalManager::hasVersion(self::LANG_A, 3));
        $this->assertTrue(LegalManager::hasVersion((string) self::LANG_A, '3'));
        $this->assertFalse(LegalManager::hasVersion(self::LANG_A, 4));
        $this->assertFalse(LegalManager::hasVersion(self::LANG_B, 3));
        $this->assertFalse(LegalManager::hasVersion(0, 3));
    }

    public function testGetLastVersionStringTakesTheHighestVersionOrFalse(): void
    {
        $this->legal(self::LANG_A, 8, 'v8');
        $this->legal(self::LANG_A, 3, 'v3');
        $this->legal(self::LANG_B, 12, 'b12');

        $this->assertSame('8', (string) LegalManager::get_last_version(self::LANG_A));
        $this->assertSame('12', (string) LegalManager::get_last_version(self::LANG_B));
        $this->assertFalse(LegalManager::get_last_version(900003));
        $this->dumpTypes([LegalManager::get_last_version(self::LANG_A)], 'get_last_version');
    }

    public function testCountCountsEveryRow(): void
    {
        $before = (int) LegalManager::count();
        $this->legal(self::LANG_A, 1, 'c1');
        $this->legal(self::LANG_A, 2, 'c2');
        $this->legal(self::LANG_B, 1, 'c3');

        $this->assertSame($before + 3, (int) LegalManager::count());
        $this->dumpTypes([LegalManager::count()], 'count');
    }

    public function testGetTypeOfTermsAndConditionsNeedsBothIds(): void
    {
        $id = $this->legal(self::LANG_A, 1, 'typed', 1);
        $other = $this->legal(self::LANG_B, 1, 'typed-html', 0);

        $this->assertSame(1, (int) LegalManager::get_type_of_terms_and_conditions($id, self::LANG_A));
        $this->assertSame(0, (int) LegalManager::get_type_of_terms_and_conditions($other, self::LANG_B));
        $this->assertNull(LegalManager::get_type_of_terms_and_conditions($id, self::LANG_B));
        $this->assertNull(LegalManager::get_type_of_terms_and_conditions($id + 100000, self::LANG_A));
        $this->dumpTypes([LegalManager::get_type_of_terms_and_conditions($id, self::LANG_A)], 'type');
    }

    public function testGetLegalDataJoinsTheLanguageOrdersByLanguageThenVersionAndFormatsTheType(): void
    {
        $conn = $this->conn();
        $conn->executeStatement(
            "INSERT INTO language (id, original_name, english_name, isocode, available) VALUES (?, 'zzz_lgl_b', 'zzz_lgl_b', 'zb', 0)",
            [self::LANG_B]
        );
        $conn->executeStatement(
            "INSERT INTO language (id, original_name, english_name, isocode, available) VALUES (?, 'zzz_lgl_a', 'zzz_lgl_a', 'za', 0)",
            [self::LANG_A]
        );
        $this->legal(self::LANG_B, 1, 'b1', 1, 1700000100);
        $this->legal(self::LANG_A, 2, 'a2', 0, 1700000200);
        $this->legal(self::LANG_A, 1, str_repeat('x', 2100), 0, 1700000300);
        $this->legal(self::LANG_A, 3, '<b>a3</b>', 5, 1700000400);

        $all = LegalManager::get_legal_data(0, 1000, 0);
        $mine = array_values(array_filter($all, static fn ($r) => str_starts_with((string) $r[1], 'zzz_lgl_')));

        $this->assertCount(4, $mine);
        $this->assertSame(['zzz_lgl_a', 'zzz_lgl_a', 'zzz_lgl_a', 'zzz_lgl_b'], array_column($mine, 1));
        $this->assertSame([1, 2, 3, 1], array_map(static fn ($r) => (int) $r[0], $mine));
        $this->assertSame(str_repeat('x', 2000).' ... ', $mine[0][2]);
        $this->assertSame('a2', $mine[1][2]);
        $this->assertSame('chg_a2', $mine[1][3]);
        $this->assertSame(get_lang('HTML'), $mine[1][4]);
        $this->assertSame(5, (int) $mine[2][4]);
        $this->assertSame(get_lang('Page Link'), $mine[3][4]);
        $this->assertSame(
            $conn->fetchOne('SELECT FROM_UNIXTIME(?)', [1700000200]),
            $mine[1][5]
        );
        $this->assertSame(
            ['version', 'language', 'content', 'changes', 'type', 'FROM_UNIXTIME(date)'],
            array_slice(array_keys($mine[1]), 0, 6)
        );

        $page = LegalManager::get_legal_data(1, 2, 0);
        $this->assertCount(2, $page);
        $this->assertSame(array_column(array_slice($all, 1, 2), 2), array_column($page, 2));
    }

    public function testSendLegalMarksTheProfileCompletedWhenNoTermIsMissing(): void
    {
        $user = $this->createUser('lgl3_send');
        $this->conn()->executeStatement('UPDATE user SET profile_completed = 0 WHERE id = ?', [$user->getId()]);
        /** @var SettingsManager $settings */
        $settings = self::getContainer()->get(SettingsManager::class);
        $settings->updateSetting('profile.show_terms_if_profile_completed', 'true');

        LegalManager::sendLegal((int) $user->getId(), (int) $this->getUser('admin')->getId());

        $this->assertSame(
            '1',
            (string) $this->conn()->fetchOne('SELECT profile_completed FROM user WHERE id = ?', [$user->getId()])
        );
        $this->assertSame([], ChamiloSession::read('profile_completed_result'));
    }

    public function testSendLegalLeavesTheProfileAloneWhenTheSettingIsOff(): void
    {
        $user = $this->createUser('lgl3_send_off');
        $this->conn()->executeStatement('UPDATE user SET profile_completed = 0 WHERE id = ?', [$user->getId()]);
        /** @var SettingsManager $settings */
        $settings = self::getContainer()->get(SettingsManager::class);
        $settings->updateSetting('profile.show_terms_if_profile_completed', 'false');

        LegalManager::sendLegal((int) $user->getId(), (int) $this->getUser('admin')->getId());

        $this->assertSame(
            '0',
            (string) $this->conn()->fetchOne('SELECT profile_completed FROM user WHERE id = ?', [$user->getId()])
        );
    }

    private function dumpTypes(array $row, string $label): void
    {
        if (getenv('DUMP_TYPES')) {
            fwrite(STDERR, $label.': '.json_encode(array_map('gettype', $row))."\n");
            fwrite(STDERR, $label.' values: '.var_export($row, true)."\n");
        }
    }
}
