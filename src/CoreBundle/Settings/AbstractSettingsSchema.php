<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Settings;

use Doctrine\ORM\EntityRepository;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractSettingsSchema implements SchemaInterface
{
    protected EntityRepository $repository;
    protected TranslatorInterface $translator;

    /**
     * @param array                   $allowedTypes
     * @param AbstractSettingsBuilder $builder
     */
    public function setMultipleAllowedTypes($allowedTypes, $builder): void
    {
        foreach ($allowedTypes as $name => $type) {
            $builder->setAllowedTypes($name, $type);
        }
    }

    public function getRepository(): EntityRepository
    {
        return $this->repository;
    }

    public function setRepository($repo): void
    {
        $this->repository = $repo;
    }

    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    protected function getSettingsInfoFromDatabase(): array
    {
        $settings = $this->getRepository()->findAll();
        $settingsInfo = [];

        foreach ($settings as $setting) {
            $settingsInfo[$setting->getVariable()] = [
                'label' => $this->translator->trans($setting->getTitle()),
                'help' => $this->translator->trans($setting->getComment()),
            ];
        }

        return $settingsInfo;
    }

    protected function updateFormFieldsFromSettingsInfo(FormBuilderInterface $builder): void
    {
        $settingsInfo = $this->getSettingsInfoFromDatabase();
        foreach ($builder->all() as $fieldName => $field) {
            $options = $field->getOptions();
            $labelAttributes = $options['label_attr'] ?? [];
            $labelAttributes['class'] = (isset($labelAttributes['class']) ? $labelAttributes['class'].' ' : '').'settings-label';
            $options['label_attr'] = $labelAttributes;

            if (isset($settingsInfo[$fieldName])) {
                $fieldConfig = $settingsInfo[$fieldName];

                $labelFromDb = $this->translator->trans($fieldConfig['label']);
                $helpFromDb = $this->translator->trans($fieldConfig['help']);

                $existingHelp = $options['help'] ?? '';
                $combinedHelp = !empty($existingHelp) ? $helpFromDb.'<br>'.$existingHelp : $helpFromDb;

                $options['label'] = $labelFromDb;
                $options['help'] = $combinedHelp;
                $options['label_html'] = true;
                $options['help_html'] = true;

                if ($field->getType()->getInnerType() instanceof ChoiceType && isset($options['choices'])) {
                    $translatedChoices = [];
                    foreach ($options['choices'] as $key => $value) {
                        $readableKey = ucfirst(strtolower(str_replace('_', ' ', $key)));
                        $translatedChoices[$this->translator->trans($readableKey)] = $value;
                    }
                    $options['choices'] = $translatedChoices;
                }
            }

            $builder->remove($fieldName);
            $builder->add($fieldName, \get_class($field->getType()->getInnerType()), $options);
        }
    }
}
