<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class SearchSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder->setDefaults(
            [
                'search_enabled' => 'false',
                'search_prefilter_prefix' => '',
                'search_show_unlinked_results' => 'true',
            ]
        );

        $allowedTypes = [
            'search_prefilter_prefix' => ['string'],
        ];

        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('search_enabled', YesNoType::class)
            ->add(
                'search_prefilter_prefix',
                TextareaType::class,
                [
                    'required' => false,
                    'attr' => [
                        'rows' => 10,
                        'class' => 'font-monospace',
                    ],
                    'help' => 'Define the fields or attributes you want to record about each indexed resource. If you want to add or remove fields in the future, make sure you change the fields one at a time, save the change and then do another change. If you want to modify field variables, is is best to do it directly in the database, in the search_engine_field table, as JSON-based changes can cause consistency issue an erase data you have in store for your resources.',
                ]
            )
            ->add(
                'search_show_unlinked_results',
                ChoiceType::class,
                [
                    'choices' => [
                        'Search shows unlinked results' => 'true',
                        'Search hides unlinked results' => 'false',
                    ],
                ]
            );

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
