<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
                    'send_two_inscription_confirmation_mail' => 'false',
                    'show_user_email_in_notification' => 'false',
                    'send_notification_score_in_percentage' => 'false',
                    'mail_template_system' => 'false',
                    'cron_notification_mails' => '',
                    'cron_notification_help_desk' => '',
                    'notifications_extended_footer_message' => '',
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
            ->add('send_two_inscription_confirmation_mail', YesNoType::class)
            ->add('show_user_email_in_notification', YesNoType::class)
            ->add('send_notification_score_in_percentage', YesNoType::class)
            ->add('mail_template_system', YesNoType::class)
            ->add(
                'cron_notification_mails',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('E-mail accounts to send notifications to when executing cronjobs - works for main/cron/import_csv.php').
                        $this->settingArrayHelpValue('cron_notification_mails'),
                ]
            )
            ->add(
                'cron_notification_help_desk',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Help desk emails that will recieve email notifications in import_csv.php').
                        $this->settingArrayHelpValue('cron_notification_help_desk'),
                ]
            )
            ->add(
                'notifications_extended_footer_message',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Add a custom extra footer for notificacions emails for a specific language, for example for privacy policy notices. Multiple languages and paragraphs can be added').
                        $this->settingArrayHelpValue('notifications_extended_footer_message'),
                ]
            )

        ;
    }

    private function settingArrayHelpValue(string $variable): string
    {
        $values = [
            'cron_notification_mails' => "<pre>
                ['email@example.com', 'email2@example.com']
                </pre>",
            'cron_notification_help_desk' => "<pre>
                ['email@example.com', 'email2@example.com']
                </pre>",
            'notifications_extended_footer_message' => "<pre>
                ['english' => ['paragraphs' => [
                    'Change or delete this paragraph or add another one'
                ]]]
                </pre>",
        ];

        $returnValue = [];
        if (isset($values[$variable])) {
            $returnValue = $values[$variable];
        }

        return $returnValue;
    }
}
