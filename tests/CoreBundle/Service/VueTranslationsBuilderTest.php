<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Service\VueTranslationsBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Translation\Translator;

/**
 * Pins the map LocaleController serves for a sublanguage.
 *
 * A sublanguage is created after the assets were built, so webpack never saw it and the
 * browser can only get its terms over HTTP. Two halves have to hold for that to work, and
 * both are silent when they break: the answer must carry the locale's own terms and
 * nothing else, and the placeholder spelling must survive the trip from gettext to
 * vue-i18n. A wrong answer here shows up as one untranslated string in one language, which
 * nobody reports.
 */
final class VueTranslationsBuilderTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/chamilo-vue-translations-'.uniqid('', true);

        mkdir($this->projectDir.'/assets/locales', 0o777, true);
        mkdir($this->projectDir.'/var/translations', 0o777, true);

        // The master key list. Only a key listed here can ever be answered.
        file_put_contents(
            $this->projectDir.'/assets/locales/en_US.json',
            json_encode([
                'Calculation mode' => 'Calculation mode',
                'Weighted average' => 'Weighted average',
                '{0} created' => '{0} created',
                'Never touched' => 'Never touched',
            ])
        );
    }

    protected function tearDown(): void
    {
        foreach (['/assets/locales/en_US.json', '/var/translations/messages.cs_66.po'] as $file) {
            if (is_file($this->projectDir.$file)) {
                unlink($this->projectDir.$file);
            }
        }

        foreach (['/assets/locales', '/assets', '/var/translations', '/var', ''] as $dir) {
            if (is_dir($this->projectDir.$dir)) {
                rmdir($this->projectDir.$dir);
            }
        }
    }

    /**
     * The whole point of the endpoint: send the overrides, not the language. A key the
     * sublanguage never touched must be absent, so the parent's bundle keeps answering it
     * through the fallback chain.
     */
    public function testOnlyTheTermsTheLocaleDefinesAreReturned(): void
    {
        $this->writePoFile(<<<'PO'
            msgid "Calculation mode"
            msgstr "Rezim vypoctu"

            msgid "Weighted average"
            msgstr "Vazeny prumer"
            PO);

        $messages = $this->builder()->buildOverrides($this->language());

        $this->assertSame(
            ['Calculation mode' => 'Rezim vypoctu', 'Weighted average' => 'Vazeny prumer'],
            $messages
        );
        $this->assertArrayNotHasKey('Never touched', $messages);
    }

    /**
     * The two formats spell a placeholder differently. The key travels one way to find the
     * message id, and the value travels back — so a term with a placeholder is the case
     * that proves both conversions, and the only one that can silently produce a broken
     * string on screen.
     */
    public function testPlaceholdersSurviveTheRoundTrip(): void
    {
        $this->writePoFile(<<<'PO'
            msgid "%s created"
            msgstr "%s byl vytvoren"
            PO);

        $this->assertSame(
            ['{0} created' => '{0} byl vytvoren'],
            $this->builder()->buildOverrides($this->language())
        );
    }

    /**
     * vue-i18n reads { } @ $ | as syntax, so a literal one has to be quoted before the
     * numbered placeholders are introduced. Reversing that order would escape the
     * placeholders themselves and print them verbatim.
     */
    public function testLiteralSyntaxCharactersAreEscapedBeforePlaceholders(): void
    {
        $this->writePoFile(<<<'PO'
            msgid "Calculation mode"
            msgstr "50% @ {here} | %s"
            PO);

        $messages = $this->builder()->buildOverrides($this->language());

        $this->assertSame("50% {'@'} {'{'}here{'}'} {'|'} {0}", $messages['Calculation mode']);
    }

    /**
     * A locale with no file of its own is not an error. Every language shipped with the
     * code is in that position, and the browser already has its bundle.
     */
    public function testALocaleWithoutAPoFileAnswersNothing(): void
    {
        $this->assertSame([], $this->builder()->buildOverrides($this->language()));
    }

    /**
     * The file is read on every build, never the translator's compiled catalogue. In prod
     * that catalogue ignores the .po's modification time, so an administrator's edit would
     * stay invisible until someone cleared the cache by hand.
     */
    public function testAnEditedFileIsReadAgain(): void
    {
        $builder = $this->builder();

        $this->writePoFile(<<<'PO'
            msgid "Calculation mode"
            msgstr "first"
            PO);
        $this->assertSame(['Calculation mode' => 'first'], $builder->buildOverrides($this->language()));

        $this->writePoFile(<<<'PO'
            msgid "Calculation mode"
            msgstr "second"
            PO);
        $this->assertSame(['Calculation mode' => 'second'], $builder->buildOverrides($this->language()));
    }

    private function builder(): VueTranslationsBuilder
    {
        return new VueTranslationsBuilder(
            new Translator('en_US'),
            new ParameterBag(['kernel.project_dir' => $this->projectDir])
        );
    }

    private function language(): Language
    {
        $language = new Language();
        $language->setIsocode('cs_66');

        return $language;
    }

    private function writePoFile(string $body): void
    {
        $header = 'msgid ""'."\n".'msgstr ""'."\n".'"Language: cs_66\n"'."\n".
            '"Content-Type: text/plain; charset=UTF-8\n"'."\n\n";

        file_put_contents($this->projectDir.'/var/translations/messages.cs_66.po', $header.$body."\n");
    }
}
