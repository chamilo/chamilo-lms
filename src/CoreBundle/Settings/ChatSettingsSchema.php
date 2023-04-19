<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class ChatSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'show_chat_folder' => 'true',
                    'allow_global_chat' => 'true',
                    'hide_chat_video' => 'false',
                    'course_chat_restrict_to_coach' => 'false',
                ]
            )
        ;
        $allowedTypes = [
            'show_chat_folder' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('allow_global_chat', YesNoType::class)
            ->add('show_chat_folder', YesNoType::class)
            ->add('hide_chat_video', YesNoType::class)
            ->add('course_chat_restrict_to_coach', YesNoType::class)
        ;
    }
}
