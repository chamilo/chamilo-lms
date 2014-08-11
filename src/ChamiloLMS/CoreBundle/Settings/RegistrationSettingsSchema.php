<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

class CourseSettingsSchema implements SchemaInterface
{
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'registration' => '', //officialcode, email, language, phone
                'allow_registration' =>'' ,
                'allow_registration_as_teacher' => '',
                'allow_lostpassword' => '',
                'page_after_login' => '',
                'extendedprofile_registration' => '',
                'allow_terms_conditions' => '',
                'student_page_after_login' => '',
                'teacher_page_after_login' => '',
                'drh_page_after_login' => '',
                'sessionadmin_page_after_login' => '',
                'student_autosubscribe' => '',
                'teacher_autosubscribe' => '',
                'drh_autosubscribe' => '',
                'sessionadmin_autosubscribe' => '',
                'platform_unsubscribe_allowed' => '',
            ))
            ->setAllowedTypes(array(
                'registration' => array('string'),
                'allow_registration' => array('string'),
                'allow_registration_as_teacher' => array('string'),
                'allow_lostpassword' => array('string'),
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('registration')
            ->add('allow_registration')
            ->add('allow_registration_as_teacher')
            ->add('allow_lostpassword')


            /*->add('enable_help_link', 'choice', array('choices' =>
                array('true' => 'Yes', 'no' => 'No'))
            )*/
        ;
    }
}
