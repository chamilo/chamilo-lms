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
                    'usergroup_do_not_unsubscribe_users_from_course_nor_session_on_user_unsubscribe' => 'false',
                    'usergroup_do_not_unsubscribe_users_from_course_on_course_unsubscribe' => 'false',
                    'usergroup_do_not_unsubscribe_users_from_session_on_session_unsubscribe' => 'false',
                    'drh_allow_access_to_all_students' => 'false',
                    'user_status_option_only_for_admin_enabled' => 'false',
                    'user_status_option_show_only_for_admin' => '',
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
                    'help' => get_lang('The user status is hidden when is false, it requires user_status_option_only_for_admin_enabled = true').
                        $this->settingArrayHelpValue('user_status_option_show_only_for_admin'),
                ]
            )

        ;
    }

    private function settingArrayHelpValue(string $variable): string
    {
        $values = [
            'user_status_option_show_only_for_admin' => "<pre>
                [
                    'COURSEMANAGER' => false,
                    'STUDENT' => false,
                    'DRH' => false,
                    'SESSIONADMIN' => true,
                    'STUDENT_BOSS' => false,
                    'INVITEE' => false,
                ]
                </pre>",
        ];

        $returnValue = [];
        if (isset($values[$variable])) {
            $returnValue = $values[$variable];
        }

        return $returnValue;
    }
}
