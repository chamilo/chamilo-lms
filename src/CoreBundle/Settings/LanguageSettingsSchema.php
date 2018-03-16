<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class LanguageSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class LanguageSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(
                [
                    'platform_language' => 'en',
                    'allow_use_sub_language' => 'false',
                    'auto_detect_language_custom_pages' => 'true',
                    'show_different_course_language' => 'true',
                    'language_priority_1' => '',
                    'language_priority_2' => '',
                    'language_priority_3' => '',
                    'language_priority_4' => '',
                    'hide_dltt_markup' => 'false',
                ]
            );

        $allowedTypes = [
            'platform_language' => ['string'],
            'allow_use_sub_language' => ['string'],
            'auto_detect_language_custom_pages' => ['string'],
            'show_different_course_language' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('platform_language', 'language')
            ->add('allow_use_sub_language', YesNoType::class)
            ->add('auto_detect_language_custom_pages', YesNoType::class)
            ->add('show_different_course_language', YesNoType::class)
            ->add('language_priority_1')
            ->add('language_priority_2')
            ->add('language_priority_3')
            ->add('language_priority_4')
            ->add('hide_dltt_markup')
        ;
    }
}
