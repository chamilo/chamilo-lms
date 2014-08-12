<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class CourseSettingsSchema
 * @package ChamiloLMS\CoreBundle\Settings
 */
class CourseSettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add(
                'registration',
                'choice',
                array('choices' => array('officialcode', 'email', 'language', 'phone'))
            )
            ->add(
                'allow_registration',
                'choice',
                array(
                    'choices' => array(
                        'true' => 'Yes',
                        'false' => 'No',
                        'approval' => 'Approval'
                    )
                )
            )
            ->add('allow_registration_as_teacher', 'yes_no')
            ->add('allow_lostpassword', 'yes_no')
            ->add(
                'page_after_login', 'choice',
                array(
                    'choices' => array(
                        'index.php' => 'Homepage',
                        'user_portal.php' => 'My courses',
                        'main/auth/courses.php' => 'Course catalog'
                    )
                )
            )
            //->add('extendedprofile_registration', '') // ?
            ->add('allow_terms_conditions', 'yes_no')
            ->add('student_page_after_login')
            ->add('teacher_page_after_login')
            ->add('drh_page_after_login')
            ->add('sessionadmin_page_after_login')
            ->add('student_autosubscribe') // ?
            ->add('teacher_autosubscribe') // ?
            ->add('drh_autosubscribe', '') // ?
            ->add('sessionadmin_autosubscribe', '') // ?
            ->add('platform_unsubscribe_allowed', 'yes_no')
        ;
    }
}
