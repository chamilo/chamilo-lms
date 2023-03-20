<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class SocialSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'allow_social_tool' => 'true',
                    'allow_students_to_create_groups_in_social' => 'false',
                    'social_enable_messages_feedback' => 'false',
                    'disable_dislike_option' => 'false',
                ]
            )
        ;
        $allowedTypes = [
            'allow_social_tool' => ['string'],
            'allow_students_to_create_groups_in_social' => ['string'],
            'social_enable_messages_feedback' => ['string'],
            'disable_dislike_option' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('allow_social_tool', YesNoType::class)
            ->add('allow_students_to_create_groups_in_social', YesNoType::class)
            ->add('social_enable_messages_feedback', YesNoType::class)
            ->add('disable_dislike_option', YesNoType::class)
        ;
    }
}
