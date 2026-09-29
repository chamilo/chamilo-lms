<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Html;

use Chamilo\CoreBundle\Service\Html\TranslateHtmlLanguageService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TranslateHtmlLanguageServiceTest extends TestCase
{
    private TranslateHtmlLanguageService $service;

    protected function setUp(): void
    {
        $this->service = new TranslateHtmlLanguageService();
    }

    public function testInspectFindsLanguagesAndSource(): void
    {
        $html = '<div class="mce-translatehtml" lang="en"><p>Hello</p></div>'
            .'<div class="mce-translatehtml" lang="es"><p>Hola</p></div>';

        $info = $this->service->inspect($html, 'en');

        self::assertTrue($info['hasMarkers']);
        self::assertContains('en', $info['presentLanguages']);
        self::assertContains('es', $info['presentLanguages']);
        self::assertStringContainsString('Hello', $info['sourceHtml']);
        self::assertStringNotContainsString('Hola', $info['sourceHtml']);
        self::assertSame(64, \strlen($info['contentSha256']));
    }

    public function testUpsertAppendsNewLanguageWithoutResendingOthers(): void
    {
        $html = '<div class="mce-translatehtml" lang="en"><p>Hello</p></div>';

        $result = $this->service->upsertLanguage(
            $html,
            'fr',
            '<p>Bonjour</p>',
            TranslateHtmlLanguageService::MODE_UPSERT,
            'en',
        );

        self::assertSame('created', $result['action']);
        self::assertStringContainsString('lang="en"', $result['html']);
        self::assertStringContainsString('Hello', $result['html']);
        self::assertStringContainsString('lang="fr"', $result['html']);
        self::assertStringContainsString('Bonjour', $result['html']);
        self::assertTrue($this->service->containsMatchingLanguage($result['presentLanguages'], 'fr'));
    }

    public function testUpsertReplacesExistingLanguage(): void
    {
        $html = '<div class="mce-translatehtml" lang="en"><p>Hello</p></div>'
            .'<div class="mce-translatehtml" lang="fr"><p>Salut</p></div>';

        $result = $this->service->upsertLanguage(
            $html,
            'fr',
            '<p>Bonjour le monde</p>',
            TranslateHtmlLanguageService::MODE_UPSERT,
            'en',
        );

        self::assertSame('replaced', $result['action']);
        self::assertStringContainsString('Bonjour le monde', $result['html']);
        self::assertStringNotContainsString('Salut', $result['html']);
        self::assertStringContainsString('Hello', $result['html']);
    }

    public function testCreateOnlyFailsWhenLanguageExists(): void
    {
        $html = '<div class="mce-translatehtml" lang="en"><p>Hello</p></div>';

        $this->expectException(InvalidArgumentException::class);
        $this->service->upsertLanguage(
            $html,
            'en',
            '<p>Hi</p>',
            TranslateHtmlLanguageService::MODE_CREATE_ONLY,
            'en',
        );
    }

    public function testWrapsUnmarkedContentWhenAddingSecondLanguage(): void
    {
        $html = '<p>Original English only</p>';

        $result = $this->service->upsertLanguage(
            $html,
            'es',
            '<p>Español</p>',
            TranslateHtmlLanguageService::MODE_UPSERT,
            'en',
        );

        self::assertSame('created', $result['action']);
        self::assertStringContainsString('lang="en"', $result['html']);
        self::assertStringContainsString('Original English only', $result['html']);
        self::assertStringContainsString('lang="es"', $result['html']);
        self::assertStringContainsString('Español', $result['html']);
    }

    public function testIfMatchSha256RejectsStaleWrite(): void
    {
        $html = '<div class="mce-translatehtml" lang="en"><p>Hello</p></div>';

        $this->expectException(InvalidArgumentException::class);
        $this->service->upsertLanguage(
            $html,
            'fr',
            '<p>Bonjour</p>',
            TranslateHtmlLanguageService::MODE_UPSERT,
            'en',
            'not-the-real-sha',
        );
    }

    public function testRejectsNestedTranslateHtmlMarkers(): void
    {
        $html = '<div class="mce-translatehtml" lang="en"><p>Hello</p></div>';

        $this->expectException(InvalidArgumentException::class);
        $this->service->upsertLanguage(
            $html,
            'fr',
            // Not a single outer wrapper — nested markers remaining after normalize.
            '<p>Intro</p><div class="mce-translatehtml" lang="fr"><p>Bonjour</p></div>',
            TranslateHtmlLanguageService::MODE_UPSERT,
            'en',
        );
    }

    public function testAcceptsOuterWrapperMatchingTargetLanguage(): void
    {
        $html = '<div class="mce-translatehtml" lang="en"><p>Hello</p></div>';

        // Client mistakenly wrapped the payload — server strips the outer wrapper.
        // Nested mce is only rejected when markers remain AFTER strip.
        // Full single-root wrapper for the same language is stripped.
        $result = $this->service->upsertLanguage(
            $html,
            'de',
            '<div class="mce-translatehtml" lang="de"><p>Hallo</p></div>',
            TranslateHtmlLanguageService::MODE_UPSERT,
            'en',
        );

        self::assertSame('created', $result['action']);
        self::assertStringContainsString('Hallo', $result['html']);
        // Only one de block expected (stripped then re-wrapped once).
        self::assertSame(1, preg_match_all('/lang="de"/', $result['html']));
    }

    public function testMergeTranslationsAppendsOnly(): void
    {
        $html = '<div class="mce-translatehtml" lang="en"><p>Hello</p></div>';
        $merged = $this->service->mergeTranslations(
            $html,
            'en',
            '<p>Hello</p>',
            true,
            false,
            ['it' => '<p>Ciao</p>'],
        );

        self::assertStringContainsString('lang="en"', $merged);
        self::assertStringContainsString('lang="it"', $merged);
        self::assertStringContainsString('Ciao', $merged);
    }

    public function testProjectHtmlFieldInventoryOmitsBody(): void
    {
        $html = '<div class="mce-translatehtml" lang="en"><p>Hello</p></div>';
        $projected = $this->service->projectHtmlField($html, 'inventory', 'en', 'description');

        self::assertArrayNotHasKey('description', $projected);
        self::assertArrayNotHasKey('content', $projected);
        self::assertArrayHasKey('present_languages', $projected);
        self::assertArrayHasKey('content_sha256', $projected);
    }

    public function testUpsertLanguageSanitizedRunsSanitizer(): void
    {
        $html = '<div class="mce-translatehtml" lang="en"><p>Hello</p></div>';
        $result = $this->service->upsertLanguageSanitized(
            $html,
            'fr',
            '<p>Bonjour</p>',
            'upsert',
            'en',
            null,
            static fn (string $merged): string => str_replace('Bonjour', 'Bonjour!', $merged),
        );

        self::assertStringContainsString('Bonjour!', $result['html']);
        self::assertSame('created', $result['action']);
        self::assertArrayHasKey('present_languages', $result);
    }
}
