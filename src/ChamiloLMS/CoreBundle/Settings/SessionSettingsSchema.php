<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

class SessionSettingsSchema implements SchemaInterface
{
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'add_users_by_coach' => '',
                'extend_rights_for_coach' => '',
                'show_session_coach' => '',
                'show_session_data' => '',
                'allow_coach_to_edit_course_session' => '',
                'show_groups_to_users' => '',
                'hide_courses_in_sessions' => '',
                'allow_session_admins_to_manage_all_sessions' => '',
                'session_tutor_reports_visibility' => '',
                'session_page_enabled' => '',
                'allow_teachers_to_create_sessions' => '',
            ))
            ->setAllowedTypes(array(
                'add_users_by_coach' => array('string')
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('add_users_by_coach')
        ;
    }
}
