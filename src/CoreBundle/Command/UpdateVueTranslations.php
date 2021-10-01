<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Repository\LanguageRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UpdateVueTranslations extends Command
{
    /**
     * @var string|null
     */
    protected static $defaultName = 'chamilo:update_vue_translations';

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

    protected function configure(): void
    {
        $description = 'This command updates the json vue locale files based in the Symfony /translation folder';
        $this
            ->setDescription($description)
            ->setHelp($description)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $languages = $this->languageRepository->findAll();
        $dir = $this->parameterBag->get('kernel.project_dir');

        $vueLocalePath = $dir.'/assets/vue/locales/';
        $englishJson = file_get_contents($vueLocalePath.'en.json');
        $translations = json_decode($englishJson, true);

        foreach ($languages as $language) {
            $iso = $language->getIsocode();

            if ('en_US' === $iso) {
                continue;
            }

            $newLanguage = [];
            foreach ($translations as $variable => $translation) {
                $translated = $this->translator->trans($variable, [], null, $iso);
                $newLanguage[$variable] = $translated;
            }
            $newLanguageToString = json_encode($newLanguage, JSON_PRETTY_PRINT);
            $fileToSave = $vueLocalePath.$iso.'.json';
            file_put_contents($fileToSave, $newLanguageToString);
            $output->writeln("json file generated for iso $iso: $fileToSave");
        }
        $output->writeln('');
        $output->writeln("Now you can commit the changes in $vueLocalePath ");

        return Command::SUCCESS;
    }
}
