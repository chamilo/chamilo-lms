<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Settings;

use Doctrine\ORM\EntityRepository;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
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
            if (isset($settingsInfo[$fieldName])) {
                $fieldConfig = $settingsInfo[$fieldName];
                $options = $field->getOptions();
                $options['label'] = $this->translator->trans($fieldConfig['label']);
                $options['help'] = $this->translator->trans($fieldConfig['help']);
                $builder->remove($fieldName);
                $builder->add($fieldName, \get_class($field->getType()->getInnerType()), $options);
            }
        }
    }
}
