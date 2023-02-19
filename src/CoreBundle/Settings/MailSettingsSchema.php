<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class MailSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'noreply_email_address' => 'no_reply@example.com',
                    'update_users_email_to_dummy_except_admins' => 'false',
                    'hosting_total_size_limit' => '0',
                    'mail_header_style' => '',
                    'mail_content_style' => '',
                    'allow_email_editor_for_anonymous' => 'true',
                    'messages_hide_mail_content' => 'false',
                    'send_inscription_msg_to_inbox' => 'false',
                    'allow_user_message_tracking' => 'false',
                    'send_two_inscription_confirmation_mail' => 'false',
                    'show_user_email_in_notification' => 'false',
                    'send_notification_score_in_percentage' => 'false',
                ]
            )
        ;
        //$this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('noreply_email_address', EmailType::class)
            ->add('update_users_email_to_dummy_except_admins', YesNoType::class)
            ->add('hosting_total_size_limit', TextType::class)
            ->add('mail_header_style', TextType::class)
            ->add('mail_content_style', TextType::class)
            ->add('allow_email_editor_for_anonymous', YesNoType::class)
            ->add('messages_hide_mail_content', YesNoType::class)
            ->add('send_inscription_msg_to_inbox', YesNoType::class)
            ->add('allow_user_message_tracking', YesNoType::class)
            ->add('send_two_inscription_confirmation_mail', YesNoType::class)
            ->add('show_user_email_in_notification', YesNoType::class)
            ->add('send_notification_score_in_percentage', YesNoType::class)
        ;
    }
}
