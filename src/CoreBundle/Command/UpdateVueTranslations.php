<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

#[AsCommand(
    name: 'chamilo:update_vue_translations',
    description: 'This command updates the json vue locale files based in the Symfony /translation folder',
)]
class UpdateVueTranslations extends Command
{
    private LanguageRepository $languageRepository;
    private ParameterBagInterface $parameterBag;
    private TranslatorInterface $translator;

    public function __construct(LanguageRepository $languageRepository, ParameterBagInterface $parameterBag, TranslatorInterface $translator)
    {
        $this->languageRepository = $languageRepository;
        $this->parameterBag = $parameterBag;
        $this->translator = $translator;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $languages = $this->languageRepository->findAll();
        $dir = $this->parameterBag->get('kernel.project_dir');

        $vueLocalePath = $dir.'/assets/locales/';
        $englishJson = file_get_contents($vueLocalePath.'en_US.json');
        $translations = json_decode($englishJson, true);

        foreach ($languages as $language) {
            $iso = $language->getIsocode();

            if ('en_US' === $iso) {
                // Only update with the same variables.
                $newLanguage = [];
                foreach ($translations as $variable => $translation) {
                    $gettextVariable = $this->replaceMarkersVueToGettext($variable);
                    $translated = $this->getTranslationWithFallback($gettextVariable, $language);
                    if (empty($translated)) {
                        $gettextVariable = $this->replaceMarkersVueToGettext($variable, true);
                        $translated = $this->getTranslationWithFallback($gettextVariable, $language);
                    }
                    $newLanguage[$variable] = $this->replaceMarkersGettextToVue($translated);
                }
                $newLanguageToString = json_encode($newLanguage, JSON_PRETTY_PRINT);
                $fileToSave = $vueLocalePath.'en_US.json';
                file_put_contents($fileToSave, $newLanguageToString);

                continue;
            }

            $newLanguage = [];
            foreach ($translations as $variable => $translation) {
                // $translated = $this->translator->trans($variable, [], null, $iso);
                $gettextVariable = $this->replaceMarkersVueToGettext($variable);
                $translated = $this->getTranslationWithFallback($gettextVariable, $language);
                if (empty($translated)) {
                    $gettextVariable = $this->replaceMarkersVueToGettext($variable, true);
                    $translated = $this->getTranslationWithFallback($gettextVariable, $language);
                }
                $newLanguage[$variable] = $this->replaceMarkersGettextToVue($translated);
            }
            $newLanguage = array_filter($newLanguage);
            $newLanguageToString = json_encode($newLanguage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $newLanguageToString = str_replace('</br>', '<br>', $newLanguageToString);
            $fileToSave = $vueLocalePath.$iso.'.json';
            file_put_contents($fileToSave, $newLanguageToString);
            $output->writeln("json file generated for iso $iso: $fileToSave");
        }
        $output->writeln('');
        $output->writeln("Now you can commit the changes in $vueLocalePath ");

        return Command::SUCCESS;
    }

    /**
     * Gets the translation for a given variable with fallbacks to parent language and base language.
     *
     * @param string   $variable the variable to be translated
     * @param Language $language the Language entity for the current language
     *
     * @return string the translated string
     */
    private function getTranslationWithFallback(string $variable, Language $language): string
    {
        // Get the ISO code of the current language
        $iso = $language->getIsocode();
        // Try to translate the variable in the current language
        $translated = $this->translator->trans($variable, [], 'messages', $iso);

        // Check if the translation is not found and if there is a parent language
        if ($translated === $variable) {
            if ($language->getParent()) {
                // Get the parent language entity and its ISO code
                $parentLanguage = $language->getParent();
                $parentIso = $parentLanguage->getIsocode();
                // Try to translate the variable in the parent language
                $translated = $this->translator->trans($variable, [], 'messages', $parentIso);

                // Check if translation is still not found and use the base language (English)
                if ($translated === $variable) {
                    $translated = $this->translator->trans($variable, [], 'messages', 'en_US');
                }
            } else {
                $translated = $this->translator->trans($variable, [], 'messages', 'en_US');
            }
        }

        return $translated;
    }

    /**
     * Replace specifiers in a string to allow rendering them by i18n.
     *
     * <code>
     *     $txt = "Bonjour %s. Je m’appelle %s";
     *     $replaced = replaceMarkersGettextToVue($txt); // Bonjour {0}. Je m’appelle {1}
     * </code>
     */
    private function replaceMarkersGettextToVue(string $text): string
    {
        $count = 0;

        $replace = function ($matches) use (&$count) {
            $type = $matches[1];

            return match ($type) {
                's', 'd', 'f' => '{'.$count++.'}',
                default => $matches[0],
            };
        };

        $pattern = '/%([sdf])/';

        return preg_replace_callback($pattern, $replace, $text);
    }

    /**
     * Replace specifiers in a Vue string to allow finding them in Gettext.
     * This method only supports the %s specifier (%d will not be replaced).
     *
     * <code>
     *     $txt = "Bonjour {0}. Je m'appelle {1};
     *     $replaced = replaceMarkersVueToGettext($txt); // Bonjour %s. Je m'appelle %s
     * </code>
     */
    private function replaceMarkersVueToGettext(string $text, bool $alternativeSpecifier = false): string
    {
        $pattern = '/\{([0-9]+)\}/';

        if ($alternativeSpecifier) {
            return preg_replace($pattern, '%d', $text);
        }

        return preg_replace($pattern, '%s', $text);
    }
}
