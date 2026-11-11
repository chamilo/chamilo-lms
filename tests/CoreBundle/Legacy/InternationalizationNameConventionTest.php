<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use PHPUnit\Framework\TestCase;

/**
 * The person name convention (display format and sort order) must be the one of the REQUESTED language.
 * The lookup used to overwrite its own $iso parameter in a loop, so every caller received the convention of
 * the last language of LanguageFixtures (first name first), whatever the language it asked for.
 */
final class InternationalizationNameConventionTest extends TestCase
{
    public function testLastNameFirstLanguageGetsItsOwnFormatAndSortOrder(): void
    {
        // Japanese writes the family name first: the last language of the list must not win.
        $this->assertSame('%t %l %f', _api_get_person_name_convention('ja', 'format'));
        $this->assertFalse(_api_get_person_name_convention('ja', 'sort_by'));
        $this->assertSame('%t %l %f', _api_get_person_name_convention('zh_CN', 'format'));
        $this->assertFalse(_api_get_person_name_convention('zh_CN', 'sort_by'));
    }

    /**
     * Owner decision: Spanish, Mexican Spanish, Galician and Malay put the given name first; Japanese, Korean,
     * Chinese, Vietnamese and Hungarian keep the family name first.
     */
    public function testNameOrderPerLanguageFollowsTheOwnerDecision(): void
    {
        foreach (['es', 'es_MX', 'gl', 'ms_MY'] as $iso) {
            $this->assertSame('%t %f %l', _api_get_person_name_convention($iso, 'format'), $iso);
            $this->assertTrue(_api_get_person_name_convention($iso, 'sort_by'), $iso);
        }
        foreach (['ja', 'ko_KR', 'zh_CN', 'zh_TW', 'vi_VN', 'hu_HU'] as $iso) {
            $this->assertSame('%t %l %f', _api_get_person_name_convention($iso, 'format'), $iso);
            $this->assertFalse(_api_get_person_name_convention($iso, 'sort_by'), $iso);
        }
    }

    public function testFirstNameFirstLanguageKeepsWesternConvention(): void
    {
        $this->assertSame('%t %f %l', _api_get_person_name_convention('fr_FR', 'format'));
        $this->assertTrue(_api_get_person_name_convention('fr_FR', 'sort_by'));
        $this->assertSame('%t %f %l', _api_get_person_name_convention('en_US', 'format'));
        $this->assertTrue(_api_get_person_name_convention('en_US', 'sort_by'));
    }

    public function testTheResultDependsOnTheRequestedLanguage(): void
    {
        $this->assertNotSame(
            _api_get_person_name_convention('ja', 'format'),
            _api_get_person_name_convention('de', 'format')
        );
        $this->assertNotSame(
            _api_get_person_name_convention('ja', 'sort_by'),
            _api_get_person_name_convention('de', 'sort_by')
        );
    }

    public function testRegionalVariantFallsBackToTheLanguageCode(): void
    {
        // 'es' is an isocode of its own; 'es_ES' is not and must resolve to it (first name first).
        $this->assertSame('%t %f %l', _api_get_person_name_convention('es_ES', 'format'));
        $this->assertTrue(_api_get_person_name_convention('es_ES', 'sort_by'));
        // 'ko' has only a regional entry (ko_KR).
        $this->assertFalse(_api_get_person_name_convention('ko', 'sort_by'));
        // Dash separator.
        $this->assertFalse(_api_get_person_name_convention('zh-TW', 'sort_by'));
    }

    public function testUnknownLanguageFallsBackToTheSafeDefaults(): void
    {
        $this->assertSame('%t %f %l', _api_get_person_name_convention('xx_YY', 'format'));
        $this->assertTrue(_api_get_person_name_convention('xx_YY', 'sort_by'));
        $this->assertSame('%t %f %l', _api_get_person_name_convention('', 'format'));
        $this->assertNull(_api_get_person_name_convention('ja', 'unknown_type'));
    }

    public function testPublicHelpersFollowTheRequestedLanguage(): void
    {
        $this->assertFalse(api_sort_by_first_name('ja'));
        $this->assertTrue(api_sort_by_first_name('fr_FR'));
    }
}
