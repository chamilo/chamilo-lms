<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class CatalogSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder->setDefaults([
            'course_catalog_settings' => '',
            'session_catalog_settings' => '',
            'show_courses_descriptions_in_catalog' => 'false',
            'course_catalog_published' => 'false',
            'course_catalog_display_in_home' => 'false',
            'hide_public_link' => 'false',
            'only_show_selected_courses' => 'false',
            'only_show_course_from_selected_category' => '',
            'allow_students_to_browse_courses' => 'true',
            'course_catalog_hide_private' => 'true',
            'show_courses_sessions' => '0',
            'allow_session_auto_subscription' => 'false',
            'course_subscription_in_user_s_session' => 'false',
        ]);

        $allowed = [
            'course_catalog_settings' => ['string'],
            'session_catalog_settings' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowed, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('course_catalog_settings', TextareaType::class, [
                'attr' => ['rows' => 10, 'cols' => 100],
            ])
            ->add('session_catalog_settings', TextareaType::class, [
                'attr' => ['rows' => 8, 'cols' => 100],
            ])
            ->add('show_courses_descriptions_in_catalog', YesNoType::class)
            ->add('course_catalog_published', YesNoType::class)
            ->add('course_catalog_display_in_home', YesNoType::class)
            ->add('hide_public_link', YesNoType::class)
            ->add('only_show_selected_courses', YesNoType::class)
            ->add('only_show_course_from_selected_category', TextareaType::class, [
                'attr' => ['rows' => 10, 'cols' => 100, 'style' => 'font-family: monospace;'],
            ])
            ->add('allow_students_to_browse_courses', YesNoType::class)
            ->add('course_catalog_hide_private', YesNoType::class)
            ->add(
                'show_courses_sessions',
                ChoiceType::class,
                [
                    'choices' => [
                        'Hide catalogue' => '-1',
                        'Show only courses' => '0',
                        'Show only sessions' => '1',
                        'Show courses & sessions' => '2',
                    ],
                ],
            )
            ->add('allow_session_auto_subscription', YesNoType::class)
            ->add('course_subscription_in_user_s_session', YesNoType::class)
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
