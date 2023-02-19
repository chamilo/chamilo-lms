<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class AnnouncementSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'hide_global_announcements_when_not_connected' => 'false',
                    'hide_send_to_hrm_users' => 'true',
                    'disable_announcement_attachment' => 'false',
                    'admin_chamilo_announcements_disable' => 'false',
                    'allow_scheduled_announcements' => 'false',
                    'disable_delete_all_announcements' => 'false',
                    'hide_announcement_sent_to_users_info' => 'false',
                ]
            )
        ;

        $allowedTypes = [
            'hide_global_announcements_when_not_connected' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('hide_global_announcements_when_not_connected', YesNoType::class)
            ->add('hide_send_to_hrm_users', YesNoType::class)
            ->add('disable_announcement_attachment', YesNoType::class)
            ->add('admin_chamilo_announcements_disable', YesNoType::class)
            ->add('allow_scheduled_announcements', YesNoType::class)
            ->add('disable_delete_all_announcements', YesNoType::class)
            ->add('hide_announcement_sent_to_users_info', YesNoType::class)
        ;
    }
}
