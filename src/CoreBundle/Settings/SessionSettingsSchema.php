<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SessionSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class SessionSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults(
                [
                    'add_users_by_coach' => 'false',
                    'extend_rights_for_coach' => 'false',
                    'show_session_coach' => 'false',
                    'show_session_data' => 'false',
                    'allow_coach_to_edit_course_session' => 'true',
                    'show_groups_to_users' => 'false',
                    'hide_courses_in_sessions' => 'false',
                    'allow_session_admins_to_manage_all_sessions' => 'false',
                    'session_tutor_reports_visibility' => 'false',
                    'session_page_enabled' => 'true',
                    'allow_teachers_to_create_sessions' => 'false',
                    'prevent_session_admins_to_manage_all_users' => 'false',
                    'session_course_ordering' => 'false',
                    'limit_session_admin_role' => 'false',
                    'allow_tutors_to_assign_students_to_session' => 'false',
                    'drh_can_access_all_session_content' => 'true',
                    'catalog_allow_session_auto_subscription' => 'false',
                    'allow_session_course_copy_for_teachers' => 'false',
                    'my_courses_view_by_session' => 'false',
                    'session_days_after_coach_access' => '',
                    'session_days_before_coach_access' => '',
                    'show_session_description' => 'false',
                    'remove_session_url' => 'false',
                    'hide_tab_list' => '',
                    'session_admins_edit_courses_content' => 'false',
                    'allow_session_admin_login_as_teacher' => 'false',
                ]
            )
        ;

        $allowedTypes = [
            'add_users_by_coach' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add(
                'add_users_by_coach',
                YesNoType::class,
                [
                    'label' => 'AddUsersByCoachTitle',
                    'help_block' => 'AddUsersByCoachComment',
                ]
            )
            ->add('extend_rights_for_coach', YesNoType::class)
            ->add('show_session_coach', YesNoType::class)
            ->add('show_session_data', YesNoType::class)
            ->add('allow_coach_to_edit_course_session', YesNoType::class)
            ->add('show_groups_to_users', YesNoType::class)
            ->add('hide_courses_in_sessions', YesNoType::class)
            ->add('allow_session_admins_to_manage_all_sessions', YesNoType::class)
            ->add('session_tutor_reports_visibility', YesNoType::class)
            ->add('session_page_enabled', YesNoType::class)
            ->add('allow_teachers_to_create_sessions', YesNoType::class)
            ->add('prevent_session_admins_to_manage_all_users', YesNoType::class)
            ->add(
                'session_course_ordering',
                TextType::class,
                [
                    'label' => 'SessionCourseOrderingTitle',
                    'help_block' => 'SessionCourseOrderingComment', ]
            )
            ->add('limit_session_admin_role', YesNoType::class)
            ->add('allow_tutors_to_assign_students_to_session', YesNoType::class)
            ->add('drh_can_access_all_session_content', YesNoType::class)
            ->add('catalog_allow_session_auto_subscription', YesNoType::class)
            ->add('allow_session_course_copy_for_teachers', YesNoType::class)
            ->add('my_courses_view_by_session', YesNoType::class)
            ->add('session_days_after_coach_access')
            ->add('session_days_before_coach_access')
            ->add('show_session_description', YesNoType::class)
            ->add('remove_session_url', YesNoType::class)
            ->add('hide_tab_list')
            ->add('session_admins_edit_courses_content', YesNoType::class)
            ->add('allow_session_admin_login_as_teacher', YesNoType::class)
        ;
    }
}
