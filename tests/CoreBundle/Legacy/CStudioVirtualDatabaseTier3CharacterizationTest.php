<?php

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use ChamiloSession;
use VirtualDatabase;

/**
 * Pins the language lookup of VirtualDatabase::w_api_get_course_locale() (CStudio plugin). The course language is
 * always the isocode of an existing language row, so the lookup returns it; the platform default is the fallback when
 * there is no course.
 */
final class CStudioVirtualDatabaseTier3CharacterizationTest extends PluginCharacterizationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require_once api_get_path(SYS_PLUGIN_PATH).'CStudio/0_dal/dal.vdatabase.php';
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('_real_cid');
        ChamiloSession::erase('_cid');
        ChamiloSession::erase('_course');
        parent::tearDown();
    }

    private function enterCourseWithLanguage(string $isocode): void
    {
        $languageId = (int) $this->conn->fetchOne('SELECT id FROM language WHERE isocode = ?', [$isocode]);
        $this->assertGreaterThan(0, $languageId);
        $this->conn->executeStatement('UPDATE course SET language_id = ? WHERE id = 1', [$languageId]);
        $code = (string) $this->conn->fetchOne('SELECT code FROM course WHERE id = 1');
        ChamiloSession::write('_real_cid', 1);
        ChamiloSession::write('_cid', $code);
        ChamiloSession::write('_course', api_get_course_info($code));
        self::getContainer()->get('doctrine')->getManager()->clear();
    }

    public function testCourseLanguageIsResolvedThroughTheLanguageTable(): void
    {
        $this->enterCourseWithLanguage('bn_BD');

        $this->assertSame('bn_BD', (new VirtualDatabase())->w_api_get_course_locale());
    }

    public function testAnAliasLanguageResolvesToItsIsocode(): void
    {
        $this->enterCourseWithLanguage('fr_FR');

        $this->assertSame('fr_FR', (new VirtualDatabase())->w_api_get_course_locale());
    }

    public function testWithoutCourseThePlatformDefaultIsUsed(): void
    {
        $this->assertSame(api_get_platform_default_isocode() ?: 'en_US', (new VirtualDatabase())->w_api_get_course_locale());
    }
}
