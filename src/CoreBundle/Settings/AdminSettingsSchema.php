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
                    'chamilo_latest_news' => 'true',
                    'chamilo_support' => 'true',
                    'usergroup_do_not_unsubscribe_users_from_course_nor_session_on_user_unsubscribe' => 'false',
                    'usergroup_do_not_unsubscribe_users_from_course_on_course_unsubscribe' => 'false',
                    'usergroup_do_not_unsubscribe_users_from_session_on_session_unsubscribe' => 'false',
                    'drh_allow_access_to_all_students' => 'false',
                    'user_status_option_only_for_admin_enabled' => 'false',
                    'user_status_option_show_only_for_admin' => '',
                ]
            )
        ;
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('administrator_name', TextType::class)
            ->add('administrator_surname')
            ->add('administrator_email', EmailType::class)
            ->add('administrator_phone')
            ->add('redirect_admin_to_courses_list', YesNoType::class)
            ->add('show_link_request_hrm_user', YesNoType::class)
            ->add('max_anonymous_users', TextType::class)
            ->add('send_inscription_notification_to_general_admin_only', YesNoType::class)
            ->add('plugin_redirection_enabled', YesNoType::class)
            ->add('chamilo_latest_news', YesNoType::class)
            ->add('chamilo_support', YesNoType::class)
            ->add('usergroup_do_not_unsubscribe_users_from_course_nor_session_on_user_unsubscribe', YesNoType::class)
            ->add('usergroup_do_not_unsubscribe_users_from_course_on_course_unsubscribe', YesNoType::class)
            ->add('usergroup_do_not_unsubscribe_users_from_session_on_session_unsubscribe', YesNoType::class)
            ->add('drh_allow_access_to_all_students', YesNoType::class)
            ->add('user_status_option_only_for_admin_enabled', YesNoType::class)
            ->add(
                'user_status_option_show_only_for_admin',
                TextareaType::class,
                [
                    'help_html' => true,
                ]
            )
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
