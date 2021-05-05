<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\FormBuilderInterface;

class LanguageSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'platform_language' => 'en',
                    'allow_use_sub_language' => 'false',
                    'auto_detect_language_custom_pages' => 'true',
                    'show_different_course_language' => 'true',
                    'language_priority_1' => 'platform_lang',
                    'language_priority_2' => 'platform_lang',
                    'language_priority_3' => 'platform_lang',
                    'language_priority_4' => 'platform_lang',
                    'hide_dltt_markup' => 'false',
                    'show_language_selector_in_menu' => 'true',
                    'user_name_order' => '',
                    'user_name_sort' => '',
                ]
            )
        ;

        $allowedTypes = [
            'platform_language' => ['string'],
            'allow_use_sub_language' => ['string'],
            'auto_detect_language_custom_pages' => ['string'],
            'show_different_course_language' => ['string'],
            'user_name_order' => ['string'],
            'user_name_sort' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $choices = [
            '' => '',
            'Platform language' => 'platform_lang',
            // default platform language
            'User profile language' => 'user_profil_lang',
            // profile language of current user
            'Selected from login' => 'user_selected_lang',
            // language selected by user at login
            'Course language' => 'course_lang',
            // language of the current course
        ];

        // @todo replace with a call to the Language repository.
        $languages = api_get_languages();
        $list = [];
        foreach ($languages as $index => $value) {
            $list[html_entity_decode($value)] = $index;
        }

        $options = [
            'choices' => $list,
            'choice_loader' => null,
        ];
        $builder
            ->add('platform_language', LanguageType::class, $options)
            ->add('allow_use_sub_language', YesNoType::class)
            ->add('auto_detect_language_custom_pages', YesNoType::class)
            ->add('show_different_course_language', YesNoType::class)
            ->add('language_priority_1', ChoiceType::class, [
                'choices' => $choices,
            ])
            ->add('language_priority_2', ChoiceType::class, [
                'choices' => $choices,
            ])
            ->add('language_priority_3', ChoiceType::class, [
                'choices' => $choices,
            ])
            ->add('language_priority_4', ChoiceType::class, [
                'choices' => $choices,
            ])
            ->add('hide_dltt_markup')
            ->add('show_language_selector_in_menu', YesNoType::class)
        ;

        $choices = [
            'last_name, first_name' => 'last_name-first_name',
            'first_name, last_name' => 'first_name-last_name',
        ];
        $builder->add(
            'user_name_order',
            ChoiceType::class,
            [
                'choices' => $choices,
            ]
        );
        $choices = [
            'First name' => 'first_name',
            'Last name' => 'last_name',
        ];
        $builder->add(
            'user_name_sort',
            ChoiceType::class,
            [
                'choices' => $choices,
            ]
        );
    }
}
