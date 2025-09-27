<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class WorkflowsSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder->setDefaults([
            // admin → workflows
            'plugin_redirection_enabled' => 'false',
            'usergroup_do_not_unsubscribe_users_from_course_nor_session_on_user_unsubscribe' => 'false',
            'usergroup_do_not_unsubscribe_users_from_course_on_course_unsubscribe' => 'false',
            'usergroup_do_not_unsubscribe_users_from_session_on_session_unsubscribe' => 'false',
            'drh_allow_access_to_all_students' => 'false',

            // announcement → workflows
            'send_all_emails_to' => '',

            // course → workflows
            'go_to_course_after_login' => 'false',
            'allow_users_to_create_courses' => 'false',
            'allow_user_course_subscription_by_course_admin' => 'true',
            'teacher_can_select_course_template' => 'true',
            'disabled_edit_session_coaches_course_editing_course' => 'false',
            'course_visibility_change_only_admin' => 'false',

            // platform → workflows
            'multiple_url_hide_disabled_settings' => 'false',
            'gamification_mode' => '',
            'load_term_conditions_section' => 'login',
            'update_student_expiration_x_date' => '',
            'user_number_of_days_for_default_expiration_date_per_role' => '',
            'user_edition_extra_field_to_check' => 'ExtrafieldLabel',
            'allow_working_time_edition' => 'false',
            'disable_user_conditions_sender_id' => '0',
            'redirect_index_to_url_for_logged_users' => '',
            'default_menu_entry_for_course_or_session' => 'my_courses',
            'session_admin_user_subscription_search_extra_field_to_search' => '',
        ]);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        // admin → workflows
        $builder
            ->add('plugin_redirection_enabled', YesNoType::class, [
                // Nota: el título/descr. se ajusta vía SettingsInfo/translations.
            ])
            ->add('usergroup_do_not_unsubscribe_users_from_course_nor_session_on_user_unsubscribe', YesNoType::class)
            ->add('usergroup_do_not_unsubscribe_users_from_course_on_course_unsubscribe', YesNoType::class)
            ->add('usergroup_do_not_unsubscribe_users_from_session_on_session_unsubscribe', YesNoType::class)
            ->add('drh_allow_access_to_all_students', YesNoType::class)

            // announcement → workflows
            ->add('send_all_emails_to', TextareaType::class, [
                'attr' => ['rows' => 5, 'style' => 'font-family: monospace;'],
            ])

            // course → workflows
            ->add('go_to_course_after_login', YesNoType::class)
            ->add('allow_users_to_create_courses', YesNoType::class)
            ->add('allow_user_course_subscription_by_course_admin', YesNoType::class)
            ->add('teacher_can_select_course_template', YesNoType::class)
            ->add('disabled_edit_session_coaches_course_editing_course', YesNoType::class)
            ->add('course_visibility_change_only_admin', YesNoType::class)

            // platform → workflows
            ->add('multiple_url_hide_disabled_settings', YesNoType::class)
            ->add('gamification_mode', TextType::class)
            ->add(
                'load_term_conditions_section',
                ChoiceType::class,
                [
                    'choices' => [
                        'Login' => 'login',
                        'Course' => 'course',
                    ],
                ]
            )
            ->add('update_student_expiration_x_date', TextareaType::class)
            ->add('user_number_of_days_for_default_expiration_date_per_role', TextareaType::class)
            ->add('user_edition_extra_field_to_check', TextType::class)
            ->add('allow_working_time_edition', YesNoType::class)
            ->add('disable_user_conditions_sender_id', TextType::class)
            ->add('redirect_index_to_url_for_logged_users', TextType::class)
            ->add(
                'default_menu_entry_for_course_or_session',
                ChoiceType::class,
                [
                    'choices' => [
                        'My courses' => 'my_courses',
                        'My sessions' => 'my_sessions',
                    ],
                ]
            )
            ->add(
                'session_admin_user_subscription_search_extra_field_to_search',
                TextType::class,
                [
                    'required' => false,
                    'empty_data' => '',
                    'help' => 'User extra field key to use when searching and naming sessions from /admin-dashboard/register.',
                ]
            )
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
