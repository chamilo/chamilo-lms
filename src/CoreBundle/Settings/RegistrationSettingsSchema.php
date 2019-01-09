<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Chamilo\SettingsBundle\Transformer\ArrayToIdentifierTransformer;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class RegistrationSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class RegistrationSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults(
                [
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
                ]
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
        $allowedTypes = [
            'required_profile_fields' => ['array'],
            'extendedprofile_registration' => ['array'],
            'extendedprofile_registrationrequired' => ['array'],
            'allow_registration' => ['string'],
            'allow_registration_as_teacher' => ['string'],
            'allow_lostpassword' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $extendedProfileOptions = [
            'MyCompetences' => 'mycompetences',
            'MyDiplomas' => 'mydiplomas',
            'MyTeach' => 'myteach',
            'MyPersonalOpenArea' => 'mypersonalopenarea',
        ];

        $builder
            ->add(
                'required_profile_fields',
                ChoiceType::class,
                [
                    'multiple' => true,
                    'choices' => [
                        'Official code' => 'officialcode',
                        'E-mail' => 'email',
                        'Language' => 'language',
                        'Phone' => 'phone',
                    ],
                ]
            )
            ->add(
                'allow_registration',
                ChoiceType::class,
                [
                    'choices' => [
                        'Yes' => 'true',
                        'No' => 'false',
                        'MailConfirmation' => 'confirmation',
                        'Approval' => 'approval',
                    ],
                ]
            )
            ->add('allow_registration_as_teacher', YesNoType::class)
            ->add('allow_lostpassword', YesNoType::class)
            ->add(
                'page_after_login',
                ChoiceType::class,
                [
                    'choices' => [
                        'CampusHomepage' => 'index.php',
                        'MyCourses' => 'user_portal.php',
                        'CourseCatalog' => 'main/auth/courses.php',
                    ],
                ]
            )
            ->add(
                'extendedprofile_registration',
                ChoiceType::class,
                [
                    'multiple' => true,
                    'choices' => $extendedProfileOptions,
                    'label' => 'ExtendedProfileRegistrationTitle',
                    'help_block' => 'ExtendedProfileRegistrationComment',
                ]
            )
            ->add(
                'extendedprofile_registrationrequired',
                ChoiceType::class,
                [
                    'multiple' => true,
                    'choices' => $extendedProfileOptions,
                    'label' => 'ExtendedProfileRegistrationRequiredTitle',
                    'help_block' => 'ExtendedProfileRegistrationRequiredComment',
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
