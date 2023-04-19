<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class AdminSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'administrator_email' => '',
                    'administrator_name' => '',
                    'administrator_surname' => '',
                    'administrator_phone' => '',
                    'redirect_admin_to_courses_list' => 'false',
                    'show_link_request_hrm_user' => 'false',
                    'max_anonymous_users' => '0',
                    'send_inscription_notification_to_general_admin_only' => 'false',
                    'plugin_redirection_enabled' => 'false',
                ]
            )
        ;
//            ->setAllowedTypes(
//                array(
//                    //'administrator_email' => array('string'),
//                    //'administrator_name' => array('string'),
//                    //'administrator_surname' => array('string'),
//                )
//            );
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add(
                'administrator_name',
                TextType::class,
                [
                    'label' => 'emailAdministratorTitle',
                    'help' => 'emailAdministratorComment',
                ]
            )
            ->add('administrator_surname')
            ->add('administrator_email', EmailType::class)
            ->add('administrator_phone')
            ->add('redirect_admin_to_courses_list', YesNoType::class)
            ->add('show_link_request_hrm_user', YesNoType::class)
            ->add('max_anonymous_users', TextType::class)
            ->add('send_inscription_notification_to_general_admin_only', YesNoType::class)
            ->add('plugin_redirection_enabled', YesNoType::class)
        ;
    }
}
