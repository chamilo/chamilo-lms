<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

class SecuritySettingsSchema implements SchemaInterface
{
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'filter_terms' => '',
                'allow_browser_sniffer' => '',
                'admins_can_set_users_pass' => '',
            ))
            ->setAllowedTypes(array(
                'homepage_view' => array('string'),
                'show_toolshortcuts' => array('string'),
                'course_create_active_tools' => array('string'),
                'display_coursecode_in_courselist' => array('string'),
                'display_teacher_in_courselist' => array('string'),
                'student_view_enabled' => array('string'),
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('homepage_view')
            ->add('show_toolshortcuts')
            ->add('course_create_active_tools')
            ->add('display_coursecode_in_courselist')
            ->add('display_teacher_in_courselist')
        ;
    }
}
