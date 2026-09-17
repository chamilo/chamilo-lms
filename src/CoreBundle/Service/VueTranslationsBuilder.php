<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service;

use Chamilo\CoreBundle\Entity\Language;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Turns a Symfony translation catalogue into the message map vue-i18n expects.
 *
 * Two callers share it, and they have to agree key for key: the
 * chamilo:update_vue_translations command, which writes assets/locales/*.json for the
 * languages shipped with the code, and LocaleController, which answers for a
 * sublanguage at runtime. A sublanguage has no file in assets/locales/ — webpack
 * enumerates that directory at build time — so the only way its terms reach the browser
 * without a rebuild is over HTTP.
 *
 * The two formats differ: vue-i18n numbers its placeholders ({0}, {1}) and treats
 * { } @ $ | as syntax, gettext uses %s and %d and treats nothing specially.
 */
final class VueTranslationsBuilder
{
    private readonly TranslatorBagInterface $translatorBag;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        // TranslatorBagInterface has no service of its own in this container, and the
        // catalogue is what tells an own translation apart from an inherited one.
        \assert($translator instanceof TranslatorBagInterface, 'The translator service must implement TranslatorBagInterface.');
        $this->translatorBag = $translator;
    }

    /**
     * The master key list: every key the interface can ask for.
     *
     * assets/locales/en_US.json is the one file that must list them all, so it decides
     * what any other locale may answer. A key absent from it cannot be translated.
     *
     * @return list<string>
     */
    public function masterKeys(): array
    {
        $file = $this->parameterBag->get('kernel.project_dir').'/assets/locales/en_US.json';
        $contents = @file_get_contents($file);

        if (false === $contents) {
            return [];
        }

        $decoded = json_decode($contents, true);

        return \is_array($decoded) ? array_keys($decoded) : [];
    }

    /**
     * The terms this locale defines on its own, and nothing else.
     *
     * A sublanguage is a handful of overrides over its parent, not a translation: its
     * catalogue holds only what an administrator changed. The browser already falls back
     * to the parent bundle for the rest (see buildFallbackChain() in assets/vue/i18n.js),
     * so sending the whole key list would send thousands of entries the browser already
     * has.
     *
     * @return array<string, string>
     */
    public function buildOverrides(Language $language): array
    {
        $iso = $language->getIsocode();
        $poFile = $this->parameterBag->get('kernel.project_dir').'/var/translations/messages.'.$iso.'.po';

        if (!is_file($poFile)) {
            return [];
        }

        // Read the file rather than the translator. In prod the translator serves a
        // catalogue compiled into var/cache and does not check whether the .po changed,
        // so an administrator's edit would stay invisible until someone cleared that
        // cache. This endpoint answers from the file, and the caller keys its own cache
        // on the file's modification time.
        $catalogue = (new PoFileLoader())->load($poFile, $iso, 'messages');
        $messages = [];

        foreach ($this->masterKeys() as $vueKey) {
            $gettextKey = $this->toGettextKey($vueKey);

            if (!$catalogue->defines($gettextKey, 'messages')) {
                // The Vue key may carry a numeric placeholder, which gettext spells %d.
                $gettextKey = $this->toGettextKey($vueKey, true);

                if (!$catalogue->defines($gettextKey, 'messages')) {
                    continue;
                }
            }

            $messages[$vueKey] = $this->toVueValue($catalogue->get($gettextKey, 'messages'));
        }

        return $messages;
    }

    /**
     * Translates one gettext key, falling back to the parent language and then English.
     */
    public function translateWithFallback(string $gettextKey, Language $language): string
    {
        $iso = $language->getIsocode();

        // A key this locale defines wins, even when the translation equals the key: that
        // is a deliberate identity translation, not a missing one.
        if ($this->translatorBag->getCatalogue($iso)->defines($gettextKey, 'messages')) {
            return $this->translator->trans($gettextKey, [], 'messages', $iso);
        }

        $parent = $language->getParent();

        if (!$parent instanceof Language) {
            return $this->translator->trans($gettextKey, [], 'messages', 'en_US');
        }

        $translated = $this->translator->trans($gettextKey, [], 'messages', $parent->getIsocode());

        if ($translated === $gettextKey) {
            $translated = $this->translator->trans($gettextKey, [], 'messages', 'en_US');
        }

        return $translated;
    }

    /**
     * Rewrites a vue-i18n key as the gettext message id that carries its translation.
     *
     * vue-i18n numbers its placeholders; gettext does not. The numbered form maps to %s,
     * or to %d for the catalogues that spell a numeric placeholder that way — which is
     * why the caller tries both.
     */
    public function toGettextKey(string $vueKey, bool $numericPlaceholder = false): string
    {
        return preg_replace('/\{([0-9]+)\}/', $numericPlaceholder ? '%d' : '%s', $vueKey);
    }

    /**
     * Rewrites a gettext translation as a vue-i18n message.
     *
     * The order matters. Escaping runs first, so a brace that belongs to the text is
     * escaped; only then are the numbered placeholders introduced, which the compiler
     * must read as syntax.
     */
    public function toVueValue(string $translated): string
    {
        return $this->replaceMarkersGettextToVue($this->escapeVueI18nSpecialChars($translated));
    }

    /**
     * Numbers the gettext placeholders the way vue-i18n reads them: %s -> {0}, {1}, ...
     */
    private function replaceMarkersGettextToVue(string $text): string
    {
        $count = 0;

        return preg_replace_callback(
            '/%([sdf])/',
            static function (array $matches) use (&$count): string {
                return match ($matches[1]) {
                    's', 'd', 'f' => '{'.$count++.'}',
                    default => $matches[0],
                };
            },
            $text
        );
    }

    /**
     * The message compiler reads { } @ $ | as syntax, so a literal one has to be quoted.
     */
    private function escapeVueI18nSpecialChars(string $text): string
    {
        return preg_replace_callback(
            '/[\{\}\@\$\|]/',
            static fn (array $matches): string => "{'".$matches[0]."'}",
            $text
        );
    }
}
