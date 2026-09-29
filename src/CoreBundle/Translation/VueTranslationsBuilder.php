<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Translation;

use Chamilo\CoreBundle\Entity\Language;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Turns a Symfony translation catalogue into the message map vue-i18n expects.
 *
 * Shared by chamilo:update_vue_translations and LocaleController, which must agree key
 * for key. vue-i18n numbers its placeholders ({0}) and reads { } @ $ | as syntax;
 * gettext uses %s and %d and treats nothing specially.
 *
 * The two format transforms are static, because tests/scripts/lang/sync_json_translations.php
 * needs them without a container.
 */
final class VueTranslationsBuilder
{
    private readonly TranslatorBagInterface $translatorBag;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        // TranslatorBagInterface has no service of its own in this container.
        \assert($translator instanceof TranslatorBagInterface, 'The translator service must implement TranslatorBagInterface.');
        $this->translatorBag = $translator;
    }

    /**
     * The master key list: assets/locales/en_US.json decides what any locale may answer.
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
     * A sublanguage is a handful of overrides; the browser falls back to the parent
     * bundle for the rest, so sending the whole key list would be wasted.
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

        // Read the file, not the translator: in prod its compiled catalogue ignores the
        // .po's modification time, so an edit would stay invisible until a cache clear.
        $catalogue = (new PoFileLoader())->load($poFile, $iso, 'messages');
        $messages = [];

        foreach ($this->masterKeys() as $vueKey) {
            $gettextKey = self::toGettextKey($vueKey);

            if (!$catalogue->defines($gettextKey, 'messages')) {
                // The Vue key may carry a numeric placeholder, which gettext spells %d.
                $gettextKey = self::toGettextKey($vueKey, true);

                if (!$catalogue->defines($gettextKey, 'messages')) {
                    continue;
                }
            }

            $messages[$vueKey] = self::toVueValue($catalogue->get($gettextKey, 'messages'));
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
     * {0} maps to %s, or to %d for catalogues that spell it that way: callers try both.
     */
    public static function toGettextKey(string $vueKey, bool $numericPlaceholder = false): string
    {
        return preg_replace('/\{([0-9]+)\}/', $numericPlaceholder ? '%d' : '%s', $vueKey);
    }

    /**
     * Rewrites a gettext translation as a vue-i18n message.
     *
     * Escaping runs first, so a literal brace is quoted before the placeholders, which
     * the compiler must read as syntax, are introduced.
     */
    public static function toVueValue(string $translated): string
    {
        return self::replaceMarkersGettextToVue(self::escapeVueI18nSpecialChars($translated));
    }

    /**
     * Numbers the gettext placeholders the way vue-i18n reads them: %s -> {0}, {1}, ...
     */
    private static function replaceMarkersGettextToVue(string $text): string
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
    private static function escapeVueI18nSpecialChars(string $text): string
    {
        return preg_replace_callback(
            '/[\{\}\@\$\|]/',
            static fn (array $matches): string => "{'".$matches[0]."'}",
            $text
        );
    }
}
