<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Service\Attribute\Required;

class StylesheetsSettingsSchema extends AbstractSettingsSchema
{
    private ParameterBagInterface $parameterBag;

    #[Required]
    public function setParameterBag(ParameterBagInterface $parameterBag): void
    {
        $this->parameterBag = $parameterBag;
    }

    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'stylesheets' => 'chamilo',
                ]
            )
        ;
        $allowedTypes = [
            'stylesheets' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('stylesheets', ChoiceType::class, [
                'choices' => $this->getThemeChoices(),
                'label' => 'Select Stylesheet Theme',
            ])
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }

    private function getThemeChoices(): array
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $themesDirectory = $projectDir.'/assets/css/themes/';

        $finder = new Finder();
        $choices = [];

        $finder->directories()->in($themesDirectory)->depth('== 0');
        if ($finder->hasResults()) {
            foreach ($finder as $folder) {
                $folderName = $folder->getRelativePathname();
                $choices[$this->formatFolderName($folderName)] = $folderName;
            }
        }

        return $choices;
    }

    private function formatFolderName(string $name): string
    {
        return ucwords(str_replace('_', ' ', $name));
    }
}
