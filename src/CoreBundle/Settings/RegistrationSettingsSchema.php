<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Chamilo\SettingsBundle\Transformer\ArrayToIdentifierTransformer;

/**
 * Class RegistrationSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class RegistrationSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(
                array(
                    'required_profile_fields' => [],
                    'allow_registration' => 'false',
                    'allow_registration_as_teacher' => 'false',
                    'allow_lostpassword' => 'true',
                    'page_after_login' => 'user_portal.php',
                    'extendedprofile_registration' => [],
                    'extendedprofile_registrationrequired' => [],
                    'allow_terms_conditions' => 'false',
                    'student_page_after_login' => '',
                    'teacher_page_after_login' => '',
                    'drh_page_after_login' => '',
                    'sessionadmin_page_after_login' => '',
                    'student_autosubscribe' => '',
                    'teacher_autosubscribe' => '',
                    'drh_autosubscribe' => '',
                    'sessionadmin_autosubscribe' => '',
                    'platform_unsubscribe_allowed' => 'false',
                )
            )
            ->setTransformer(
                'required_profile_fields',
                new ArrayToIdentifierTransformer()
            )
            ->setTransformer(
                'extendedprofile_registration',
                new ArrayToIdentifierTransformer()
            )
            ->setTransformer(
                'extendedprofile_registrationrequired',
                new ArrayToIdentifierTransformer()
            )
        ;
        $allowedTypes = array(
            'required_profile_fields' => array('array'),
            'extendedprofile_registration' => array('array'),
            'extendedprofile_registrationrequired' => array('array'),
            'allow_registration' => array('string'),
            'allow_registration_as_teacher' => array('string'),
            'allow_lostpassword' => array('string'),
        );
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $extendedProfileOptions = [
            'mycompetences' => 'MyCompetences',
            'mydiplomas' => 'MyDiplomas',
            'myteach' => 'MyTeach',
            'mypersonalopenarea' => 'MyPersonalOpenArea',
        ];

        $builder
            ->add(
                'required_profile_fields',
                'choice',
                array(
                    'multiple' => true,
                    'choices' => array(
                        'officialcode' => 'officialcode',
                        'email' => 'email',
                        'language' => 'language',
                        'phone' => 'phone',
                    ),
                )
            )
            ->add(
                'allow_registration',
                'choice',
                array(
                    'choices' => array(
                        'true' => 'Yes',
                        'false' => 'No',
                        'confirmation' => 'MailConfirmation',
                        'approval' => 'Approval',
                    ),
                )
            )
            ->add('allow_registration_as_teacher', YesNoType::class)
            ->add('allow_lostpassword', YesNoType::class)
            ->add(
                'page_after_login',
                'choice',
                array(
                    'choices' => array(
                        'index.php' => 'CampusHomepage',
                        'user_portal.php' => 'MyCourses',
                        'main/auth/courses.php' => 'CourseCatalog',
                    ),
                )
            )
            ->add('extendedprofile_registration',
                'choice',
                [
                    'multiple' => true,
                    'choices' => $extendedProfileOptions,
                    'label' => 'ExtendedProfileRegistrationTitle',
                    'help_block' => 'ExtendedProfileRegistrationComment'
                ]
            )
            ->add('extendedprofile_registrationrequired',
                'choice',
                [
                    'multiple' => true,
                    'choices' => $extendedProfileOptions,
                    'label' => 'ExtendedProfileRegistrationRequiredTitle',
                    'help_block' => 'ExtendedProfileRegistrationRequiredComment'
                ]
            )
            ->add('allow_terms_conditions', YesNoType::class)
            ->add('student_page_after_login')
            ->add('teacher_page_after_login')
            ->add('drh_page_after_login')
            ->add('sessionadmin_page_after_login')
            ->add('student_autosubscribe')// ?
            ->add('teacher_autosubscribe')// ?
            ->add('drh_autosubscribe')//?
            ->add('sessionadmin_autosubscribe')// ?
            ->add('platform_unsubscribe_allowed', YesNoType::class)
        ;
    }
}
