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
                    'allow_global_chat' => 'false',
                    'hide_chat_video' => 'true',
                    'course_chat_restrict_to_coach' => 'false',
                    'save_private_conversations_in_documents' => 'false',
                ]
            )
        ;
        $allowedTypes = [
            'show_chat_folder' => ['string'],
            'allow_global_chat' => ['string'],
            'hide_chat_video' => ['string'],
            'course_chat_restrict_to_coach' => ['string'],
            'save_private_conversations_in_documents' => ['string'],
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
            ->add('save_private_conversations_in_documents', YesNoType::class)
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
