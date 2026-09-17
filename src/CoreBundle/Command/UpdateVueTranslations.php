<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Translation\VueTranslationsBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

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
    private VueTranslationsBuilder $builder;

    public function __construct(LanguageRepository $languageRepository, ParameterBagInterface $parameterBag, VueTranslationsBuilder $builder)
    {
        $this->languageRepository = $languageRepository;
        $this->parameterBag = $parameterBag;
        $this->builder = $builder;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Clear the compiled translation catalogue cache first. In prod the translator
        // serves cached catalogues from var/cache/<env>/translations without checking
        // whether the .po files changed, so without this step the command would write
        // stale English fallbacks into the locale JSON files. The translator loads
        // catalogues lazily, so removing the cache here forces a rebuild from the .po
        // files on first use below.
        $translationCacheDir = $this->parameterBag->get('kernel.cache_dir').'/translations';
        $filesystem = new Filesystem();
        if ($filesystem->exists($translationCacheDir)) {
            $filesystem->remove($translationCacheDir);
            $output->writeln("Cleared compiled translation cache: $translationCacheDir");
        }

        $languages = $this->languageRepository->findAll();
        $dir = $this->parameterBag->get('kernel.project_dir');

        $vueLocalePath = $dir.'/assets/locales/';
        $englishJson = @file_get_contents($vueLocalePath.'en_US.json');

        if (false === $englishJson) {
            $output->writeln("<error>Could not read {$vueLocalePath}en_US.json. Nothing was written.</error>");

            return Command::FAILURE;
        }

        $translations = json_decode($englishJson, true);
        $unwritable = [];

        foreach ($languages as $language) {
            $iso = $language->getIsocode();

            if ('en_US' === $iso) {
                // Only update with the same variables.
                $newLanguage = [];
                foreach ($translations as $variable => $translation) {
                    $newLanguage[$variable] = $this->translateKey($variable, $language);
                }
                // No JSON_UNESCAPED_UNICODE: the committed files escape their accented
                // characters, and tests/scripts/lang/sync_json_translations.php writes them
                // the same way. Adding it here rewrites every accented line of all ~70
                // files on each run, a diff that says nothing about the change.
                $newLanguageToString = json_encode($newLanguage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                $fileToSave = $vueLocalePath.'en_US.json';

                if (false === @file_put_contents($fileToSave, $newLanguageToString)) {
                    $unwritable[] = $iso;
                }

                continue;
            }

            $newLanguage = [];
            foreach ($translations as $variable => $translation) {
                $newLanguage[$variable] = $this->translateKey($variable, $language);
            }
            $newLanguage = array_filter($newLanguage);
            $newLanguageToString = json_encode($newLanguage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $newLanguageToString = str_replace('</br>', '<br>', $newLanguageToString);
            $fileToSave = $vueLocalePath.$iso.'.json';

            // Suppressed and answered here: this also runs from a migration over the web,
            // where one warning per language would break the installer's JSON reply.
            if (false === @file_put_contents($fileToSave, $newLanguageToString)) {
                $unwritable[] = $iso;

                continue;
            }

            $output->writeln("json file generated for iso $iso: $fileToSave");
        }

        if ([] !== $unwritable) {
            $output->writeln('');
            $output->writeln(\sprintf(
                '<error>Could not write %d of the locale files in %s: %s. Make that directory and its'
                    .' files writable by the user running this command, then run it again.</error>',
                \count($unwritable),
                $vueLocalePath,
                implode(', ', $unwritable)
            ));

            return Command::FAILURE;
        }

        $output->writeln('');
        $output->writeln("Now you can commit the changes in $vueLocalePath ");

        return Command::SUCCESS;
    }

    /**
     * Answers one vue-i18n key for one language, in the shape the JSON files hold.
     *
     * The mapping lives in VueTranslationsBuilder, shared with LocaleController.
     */
    private function translateKey(string $vueKey, Language $language): string
    {
        $gettextKey = VueTranslationsBuilder::toGettextKey($vueKey);
        $translated = $this->builder->translateWithFallback($gettextKey, $language);

        if (empty($translated)) {
            $gettextKey = VueTranslationsBuilder::toGettextKey($vueKey, true);
            $translated = $this->builder->translateWithFallback($gettextKey, $language);
        }

        return VueTranslationsBuilder::toVueValue($translated);
    }
}
