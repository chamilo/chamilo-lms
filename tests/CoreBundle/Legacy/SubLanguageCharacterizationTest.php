<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\UuidV4;
use SubLanguageManager;

/**
 * Characterization of SubLanguageManager (public/main/inc/lib/sub_language.class.php).
 *
 * LIVE: everything the admin pages main/admin/languages.php, sub_language.php and sub_language_add.php
 * call (getAllLanguages, get_all_information_of_language, get_name_of_language_by_id,
 * check_if_language_is_sub_language / _used / _father, make_(un)available_language,
 * set_platform_language, removeSubLanguage, addSubLanguage, languageExistsById, isParentOfSubLanguage,
 * getAllInformationOfSubLanguage, checkIfLanguageExists), getParentLocale (internationalization.lib.php),
 * get_platform_language_id (Onlyoffice plugin). DEAD (no caller, marked @deprecated in the source):
 * get_parent_language_path, getLanguageFromIsocode and getLanguageFromBrowserPreference (only call
 * each other), getIsoCodes/searchTranslations (only each other), get_lang_folder_files_list
 * (only tests/scripts), the file helpers get_all_language_variable_in_file, add_file_in_language_directory
 * and write_data_in_file.
 *
 * All the operations are admin-only pages (api_protect_admin_script()); the class itself does no role
 * check, which is pinned nowhere because the gate is the page. Fixtures: raw INSERTs into `language`
 * (english names prefixed sl_) and users with a chosen locale.
 */
final class SubLanguageCharacterizationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;
    /** @var array<string, string> */
    private array $originalSettings = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/admin/languages.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        $this->conn = $container->get('doctrine')->getConnection();
    }

    protected function tearDown(): void
    {
        foreach ($this->originalSettings as $name => $value) {
            Container::getSettingsManager()->updateSetting($name, $value);
        }
        parent::tearDown();
    }

    // ---------------------------------------------------------------- fixtures

    private function language(string $name, ?int $parent = null, int $available = 1, ?string $iso = null): int
    {
        ++$this->seq;
        $iso ??= 'sl'.$this->seq;
        $this->conn->executeStatement(
            'INSERT INTO language (parent_id, original_name, english_name, isocode, available) VALUES (?, ?, ?, ?, ?)',
            [$parent, 'Orig '.$name, 'sl_'.$name.'_'.$this->seq, $iso, $available]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function userWithLocale(string $locale, int $active = 1): int
    {
        $name = 'sl_u_'.(++$this->seq).'_'.substr(md5((string) microtime(true)), 0, 6);
        $this->conn->executeStatement(
            "INSERT INTO user (username, username_canonical, locale, password, timezone, email, email_canonical,
                locked, expired, credentials_expired, salt, roles, status, active, uuid, created_at, updated_at,
                firstname, lastname)
             VALUES (?, ?, ?, 'x', 'UTC', ?, ?, 0, 0, 0, 'x', '[]', 5, ?, ?,
                '2001-01-01 00:00:00', '2001-01-01 00:00:00', ?, 'sl')",
            [$name, $name, $locale, $name.'@example.com', $name.'@example.com', $active, UuidV4::v4()->toBinary(), $name]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function row(int $id): array|false
    {
        return $this->conn->fetchAssociative('SELECT * FROM language WHERE id = ?', [$id]);
    }

    private function tmpDir(): string
    {
        $dir = sys_get_temp_dir().'/sl_test_'.getmypid().'_'.(++$this->seq);
        mkdir($dir);

        return $dir;
    }

    // ---------------------------------------------------------------- listings

    public function testGetAllLanguagesIsKeyedByEnglishNameAndSortedAlphabetically(): void
    {
        $b = $this->language('b');
        $a = $this->language('a');

        $all = SubLanguageManager::getAllLanguages();

        $this->assertSame((string) $a, (string) $all[$this->row($a)['english_name']]['id']);
        $names = array_keys($all);
        $sorted = $names;
        sort($sorted, SORT_STRING | SORT_FLAG_CASE);
        // ORDER BY english_name uses a case-insensitive collation.
        $this->assertSame(array_map('strtolower', $sorted), array_map('strtolower', $names));
        $this->assertLessThan(
            array_search($this->row($b)['english_name'], $names, true),
            array_search($this->row($a)['english_name'], $names, true)
        );
    }

    public function testGetAllLanguagesOnlyActiveHidesUnavailableOnes(): void
    {
        $on = $this->language('on', null, 1);
        $off = $this->language('off', null, 0);

        $active = SubLanguageManager::getAllLanguages(true);
        $all = SubLanguageManager::getAllLanguages();

        $this->assertArrayHasKey($this->row($on)['english_name'], $active);
        $this->assertArrayNotHasKey($this->row($off)['english_name'], $active);
        $this->assertArrayHasKey($this->row($off)['english_name'], $all);
    }

    public function testGetAllInformationOfLanguageReturnsTheRowOrAnEmptyArray(): void
    {
        $id = $this->language('x', null, 1, 'slx');

        $info = SubLanguageManager::get_all_information_of_language($id);

        $this->assertSame('slx', $info['isocode']);
        $this->assertSame('Orig x', $info['original_name']);
        $this->assertSame([], SubLanguageManager::get_all_information_of_language(999999));
        // The id goes through intval(): "1 OR 1=1" is just id 1 (the first installed language), not a wildcard.
        $this->assertSame('arabic', SubLanguageManager::get_all_information_of_language('1 OR 1=1')['english_name']);
    }

    public function testGetNameOfLanguageByIdReturnsTheOriginalNameOrAnEmptyString(): void
    {
        $id = $this->language('x');

        $this->assertSame('Orig x', SubLanguageManager::get_name_of_language_by_id($id));
        $this->assertSame('', SubLanguageManager::get_name_of_language_by_id(999999));
    }

    // ---------------------------------------------------------------- predicates

    public function testCheckIfLanguageIsSubLanguageIsTrueOnlyWhenTheLanguageHasAParent(): void
    {
        $parent = $this->language('p');
        $child = $this->language('c', $parent);

        $this->assertTrue(SubLanguageManager::check_if_language_is_sub_language($child));
        $this->assertFalse(SubLanguageManager::check_if_language_is_sub_language($parent));
        $this->assertFalse(SubLanguageManager::check_if_language_is_sub_language(999999));
    }

    public function testCheckIfLanguageIsFatherWithASingleChild(): void
    {
        $parent = $this->language('p');
        $lonely = $this->language('lonely');
        $this->language('c', $parent);

        $this->assertTrue(SubLanguageManager::check_if_language_is_father($parent));
        $this->assertFalse(SubLanguageManager::check_if_language_is_father($lonely));
    }

    public function testCheckIfLanguageIsFatherWithSeveralChildren(): void
    {
        $parent = $this->language('p');
        $this->language('c1', $parent);
        $this->language('c2', $parent);

        // A language with two sub-languages is a father too (the query used to test count(*) = 1).
        $this->assertTrue(SubLanguageManager::check_if_language_is_father($parent));
    }

    public function testCheckIfLanguageIsUsedMatchesTheUserLocaleAgainstTheEnglishName(): void
    {
        $used = $this->language('used', null, 1, 'slu');
        $free = $this->language('free', null, 1, 'slf');
        $this->userWithLocale('slu');
        $this->userWithLocale($this->row($free)['english_name']);

        // PINNED QUIRK: user.locale stores the ISO code (en_US), but the query compares it with the
        // english_name column, so a language that users really use is reported as unused and one whose
        // english name happens to be a locale value is reported as used.
        $this->assertFalse(SubLanguageManager::check_if_language_is_used($used));
        $this->assertTrue(SubLanguageManager::check_if_language_is_used($free));
    }

    public function testCheckIfLanguageIsUsedIgnoresSoftDeletedUsers(): void
    {
        $lang = $this->language('gone', null, 1, 'slg');
        $this->userWithLocale($this->row($lang)['english_name'], -2);

        $this->assertFalse(SubLanguageManager::check_if_language_is_used($lang));
    }

    // ---------------------------------------------------------------- availability

    public function testMakeUnavailableAndAvailableFlipOnlyTheTargetedRow(): void
    {
        $a = $this->language('a', null, 1);
        $b = $this->language('b', null, 1);

        $this->assertTrue(SubLanguageManager::make_unavailable_language($a));
        $this->assertSame(0, (int) $this->row($a)['available']);
        $this->assertSame(1, (int) $this->row($b)['available']);

        $this->assertTrue(SubLanguageManager::make_available_language($a));
        $this->assertSame(1, (int) $this->row($a)['available']);
    }

    public function testMakeUnavailableOnAnUnknownIdSucceedsAndChangesNothing(): void
    {
        $a = $this->language('a', null, 1);

        // PINNED: "only return false on sql error": an id that matches nothing is still true.
        $this->assertTrue(SubLanguageManager::make_unavailable_language(999999));
        $this->assertSame(1, (int) $this->row($a)['available']);
    }

    public function testMakeUnavailableCastsTheIdToAnInteger(): void
    {
        $a = $this->language('a', null, 1);

        SubLanguageManager::make_unavailable_language($a.' OR 1=1');

        // intval("<id> OR 1=1") is the id: only that row is touched.
        $this->assertSame(0, (int) $this->row($a)['available']);
        $this->assertSame(
            0,
            (int) $this->conn->fetchOne("SELECT COUNT(*) FROM language WHERE available = 0 AND id <> ?", [$a]),
        );
    }

    // ---------------------------------------------------------------- platform language

    public function testSetPlatformLanguageStoresTheIsocodeInTheSetting(): void
    {
        $this->originalSettings['language.platform_language'] = (string) api_get_setting('language.platform_language');
        $id = $this->language('plat', null, 1, 'slp');

        $this->assertTrue(SubLanguageManager::set_platform_language($id));

        $this->assertSame(
            'slp',
            $this->conn->fetchOne("SELECT selected_value FROM settings WHERE variable = 'platform_language' LIMIT 1")
        );
    }

    public function testSetPlatformLanguageRefusesEmptyOrNonIntegerIds(): void
    {
        $before = $this->conn->fetchOne("SELECT selected_value FROM settings WHERE variable = 'platform_language' LIMIT 1");

        $this->assertFalse(SubLanguageManager::set_platform_language(0));
        $this->assertFalse(SubLanguageManager::set_platform_language(''));
        $this->assertFalse(SubLanguageManager::set_platform_language('1.5'));

        $this->assertSame(
            $before,
            $this->conn->fetchOne("SELECT selected_value FROM settings WHERE variable = 'platform_language' LIMIT 1")
        );
    }

    public function testGetPlatformLanguageIdResolvesTheConfiguredPlatformLanguage(): void
    {
        $english = (int) $this->conn->fetchOne("SELECT id FROM language WHERE isocode = 'en_US'");
        $this->originalSettings['language.platform_language'] = (string) api_get_setting('language.platform_language');
        Container::getSettingsManager()->updateSetting('language.platform_language', 'en_US');

        // PINNED QUIRK: it reads the legacy setting 'platformLanguage' and compares it with english_name,
        // but the setting is now language.platform_language and holds an isocode: always false.
        $this->assertFalse(SubLanguageManager::get_platform_language_id());
        $this->assertGreaterThan(0, $english);
    }

    // ---------------------------------------------------------------- legacy lookups (dead callers)

    public function testGetParentLanguagePathQueriesAColumnThatNoLongerExists(): void
    {
        // PINNED QUIRK (dead, @deprecated): dokeos_folder was dropped from `language`.
        $this->expectException(\Throwable::class);

        SubLanguageManager::get_parent_language_path('sl_nothing');
    }

    public function testGetLanguageFromIsocodeReturnsTheLatestAvailableLanguage(): void
    {
        $old = $this->language('old', null, 1, 'slz');
        $new = $this->language('new', null, 1, 'slz');
        $hidden = $this->language('hidden', null, 0, 'slz');

        $this->assertSame($this->row($new)['english_name'], SubLanguageManager::getLanguageFromIsocode('slz'));
        $this->assertNotSame($this->row($hidden)['english_name'], SubLanguageManager::getLanguageFromIsocode('slz'));
        $this->assertNotSame($this->row($old)['english_name'], SubLanguageManager::getLanguageFromIsocode('slz'));
        $this->assertFalse(SubLanguageManager::getLanguageFromIsocode('slnone'));
    }

    public function testGetLanguageFromBrowserPreferencePicksTheFirstKnownCode(): void
    {
        $id = $this->language('known', null, 1, 'slq');

        $this->assertSame(
            $this->row($id)['english_name'],
            SubLanguageManager::getLanguageFromBrowserPreference('slnone,slq;q=0.7')
        );
        $this->assertFalse(SubLanguageManager::getLanguageFromBrowserPreference('slnone'));
        $this->assertFalse(SubLanguageManager::getLanguageFromBrowserPreference(''));
    }

    // ---------------------------------------------------------------- Doctrine based operations

    public function testAddSubLanguagePersistsAChildOfTheParent(): void
    {
        $parent = $this->language('p', null, 1, 'slpa');

        $child = SubLanguageManager::addSubLanguage('Orig child', 'sl_child', true, $parent, 'slpa_x');

        $this->assertInstanceOf(Language::class, $child);
        $row = $this->row((int) $child->getId());
        $this->assertSame($parent, (int) $row['parent_id']);
        // The LanguageListener rewrites a child's code as "<parent prefix>_<child id>".
        $this->assertSame('slpa_'.$child->getId(), $row['isocode']);
        $this->assertSame(1, (int) $row['available']);
        $this->assertSame('sl_child', $row['english_name']);
    }

    public function testAddSubLanguageWithAnUnknownParentThrows(): void
    {
        $this->expectException(\Exception::class);

        SubLanguageManager::addSubLanguage('a', 'sl_b', true, 999999, 'slzz');
    }

    public function testRemoveSubLanguageOnlyDeletesAChildOfTheGivenParent(): void
    {
        $parent = $this->language('p');
        $other = $this->language('o');
        $child = $this->language('c', $parent, 1, 'slrm1');

        // Wrong parent id: refused.
        $this->assertFalse(SubLanguageManager::removeSubLanguage($other, $child));
        // A main language (no parent) is never removed.
        $this->assertFalse(SubLanguageManager::removeSubLanguage($parent, $other));
        $this->assertNotFalse($this->row($child));
        $this->assertNotFalse($this->row($other));
        // Unknown id.
        $this->assertFalse(SubLanguageManager::removeSubLanguage($parent, 999999));

        \Database::getManager()->clear();
        $this->assertTrue(SubLanguageManager::removeSubLanguage($parent, $child));
        $this->assertFalse($this->row($child));
        $this->assertNotFalse($this->row($parent));
    }

    public function testLanguageExistsByIdAndIsParentOfSubLanguage(): void
    {
        $parent = $this->language('p');
        $child = $this->language('c', $parent);

        $this->assertTrue(SubLanguageManager::languageExistsById($parent));
        $this->assertFalse(SubLanguageManager::languageExistsById(999999));
        $this->assertTrue(SubLanguageManager::isParentOfSubLanguage($parent));
        $this->assertFalse(SubLanguageManager::isParentOfSubLanguage($child));
    }

    public function testGetAllInformationOfSubLanguageRequiresTheParentToMatch(): void
    {
        $parent = $this->language('p');
        $other = $this->language('o');
        $child = $this->language('c', $parent);

        $found = SubLanguageManager::getAllInformationOfSubLanguage($parent, $child);

        $this->assertInstanceOf(Language::class, $found);
        $this->assertSame($child, $found->getId());
        $this->assertNull(SubLanguageManager::getAllInformationOfSubLanguage($other, $child));
    }

    public function testCheckIfLanguageExistsFlagsEachDuplicatedField(): void
    {
        $id = $this->language('dup', null, 1, 'sldup');
        $row = $this->row($id);

        $ok = SubLanguageManager::checkIfLanguageExists('Fresh original', 'sl_fresh', 'sldup');
        $this->assertSame(
            ['original_name' => false, 'english_name' => false, 'isocode' => false, 'execute_add' => true],
            $ok
        );

        $dup = SubLanguageManager::checkIfLanguageExists($row['original_name'], $row['english_name'], 'sl_unknown_iso');
        $this->assertTrue($dup['original_name']);
        $this->assertTrue($dup['english_name']);
        // PINNED: the flag named "isocode" is true when the code is NOT one of the platform's iso codes
        // (the new sub-language code must be derived from an existing one).
        $this->assertTrue($dup['isocode']);
        $this->assertFalse($dup['execute_add']);
    }

    public function testGetParentLocaleReturnsTheIsocodeOfTheParent(): void
    {
        $parent = $this->language('p', null, 1, 'slpl');
        $this->language('c', $parent, 1, 'slpl_c');

        $this->assertSame('slpl', SubLanguageManager::getParentLocale('slpl_c'));
        $this->assertNull(SubLanguageManager::getParentLocale('slpl'));
        $this->assertNull(SubLanguageManager::getParentLocale('sl_nothing'));
    }

    public function testGetIsoCodesReturnsChildAndParentCodes(): void
    {
        $parent = $this->language('p', null, 1, 'slic');
        $child = $this->language('c', $parent, 1, 'slic_c');

        $this->assertSame(['slic_c', 'slic'], SubLanguageManager::getIsoCodes($child));
        $this->assertSame(['slic', null], SubLanguageManager::getIsoCodes($parent));
        $this->assertSame([null, null], SubLanguageManager::getIsoCodes(999999));
    }

    // ---------------------------------------------------------------- pure helpers and files

    public function testGenerateSublanguageCodeKeepsTheParentAndTruncatesTheVariant(): void
    {
        $this->assertSame('es_co', SubLanguageManager::generateSublanguageCode('ES', ' CO '));
        $this->assertSame(Language::ISO_MAX_LENGTH, strlen(SubLanguageManager::generateSublanguageCode('es', 'abcdefghijklmnop')));
        $this->assertSame('es_abcde', SubLanguageManager::generateSublanguageCode('es', 'abcdefghijklmnop', 8));
    }

    public function testStringToCamelCaseVariableName(): void
    {
        $this->assertSame('helloWorldFoo', SubLanguageManager::stringToCamelCaseVariableName('Hello, world! foo'));
        $this->assertSame(25, strlen(SubLanguageManager::stringToCamelCaseVariableName(str_repeat('abc ', 20))));
    }

    public function testUpdateOrAddMsgidRefusesFilenamesThatEscapeTheTranslationsDirectory(): void
    {
        $this->assertSame(
            ['success' => false, 'error' => 'Invalid filename'],
            SubLanguageManager::updateOrAddMsgid('../../public/shell.php', 'a', 'b')
        );
        $this->assertSame(
            ['success' => false, 'error' => 'Invalid filename'],
            SubLanguageManager::updateOrAddMsgid('messages.en.po/../x', 'a', 'b')
        );
        $this->assertSame(
            ['success' => false, 'error' => 'File does not exist'],
            SubLanguageManager::updateOrAddMsgid('messages.sl_nofile.po', 'a', 'b')
        );
    }

    public function testFileHelpersWriteAndReadLanguageVariables(): void
    {
        $dir = $this->tmpDir();
        $file = $dir.'/gradebook.inc.php';
        $this->assertSame(6, SubLanguageManager::add_file_in_language_directory($file));
        $this->assertTrue(SubLanguageManager::write_data_in_file($file, '"Foo";', '$Name'));
        // fopen(..., 'a') creates the file, so even a missing path is "written".
        $this->assertTrue(SubLanguageManager::write_data_in_file($dir.'/missing.inc.php', 'x', 'y'));
        unlink($dir.'/missing.inc.php');
        file_put_contents($dir.'/trad4all.inc.php', '<?php'.PHP_EOL);
        file_put_contents($dir.'/notes.txt', 'x');

        $this->assertSame(['$Name' => '"Foo";'], SubLanguageManager::get_all_language_variable_in_file($file));
        $this->assertSame(['Name' => '"Foo";'], SubLanguageManager::get_all_language_variable_in_file($file, true));
        $this->assertSame([], SubLanguageManager::get_all_language_variable_in_file($dir.'/none.php'));
        $list = SubLanguageManager::get_lang_folder_files_list($dir);
        sort($list);
        $this->assertSame(['gradebook.inc.php', 'trad4all.inc.php'], $list);
        $main = SubLanguageManager::get_lang_folder_files_list($dir, true);
        sort($main);
        $this->assertSame(['gradebook', 'trad4all'], $main);

        array_map('unlink', glob($dir.'/*'));
        rmdir($dir);
    }
}
