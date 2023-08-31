<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Chamilo\CoreBundle\Transformer\ArrayToIdentifierTransformer;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class RegistrationSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
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
                    'required_extra_fields_in_inscription' => '',
                    'allow_fields_inscription' => '',
                    'send_inscription_msg_to_inbox' => 'false',
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

    public function buildForm(FormBuilderInterface $builder): void
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
                    'help' => 'ExtendedProfileRegistrationComment',
                ]
            )
            ->add(
                'extendedprofile_registrationrequired',
                ChoiceType::class,
                [
                    'multiple' => true,
                    'choices' => $extendedProfileOptions,
                    'label' => 'ExtendedProfileRegistrationRequiredTitle',
                    'help' => 'ExtendedProfileRegistrationRequiredComment',
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
            ->add(
                'required_extra_fields_in_inscription',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Set extra fields as required in the inscription.php page').
                        $this->settingArrayHelpValue('required_extra_fields_in_inscription'),
                ]
            )
            ->add(
                'allow_fields_inscription',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Only shows the fields in this list').
                        $this->settingArrayHelpValue('allow_fields_inscription'),
                ]
            )
            ->add('send_inscription_msg_to_inbox', YesNoType::class)
        ;
    }

    private function settingArrayHelpValue(string $variable): string
    {
        $values = [
            'required_extra_fields_in_inscription' => "<pre>
                [
                    'options' => [
                        'terms_adresse',
                        'terms_codepostal',
                        'terms_ville',
                        'terms_paysresidence',
                        'terms_datedenaissance',
                        'terms_genre',
                        'filiere_user',
                        'terms_formation_niveau',
                        'langue_cible',
                    ]
                ]
                </pre>",
            'allow_fields_inscription' => "<pre>
                [
                    'fields' => [
                        'lastname',
                        'firstname',
                        'email',
                        'language',
                        'phone',
                        'address',
                    ],
                    'extra_fields' => [
                        'terms_nationalite',
                        'terms_numeroderue',
                        'terms_nomderue',
                        'terms_codepostal',
                        'terms_paysresidence',
                        'terms_ville',
                        'terms_datedenaissance',
                        'terms_genre',
                        'filiere_user',
                        'terms_formation_niveau',
                        'terms_villedustage',
                        'terms_adresse',
                        'gdpr',
                        'langue_cible'
                    ]
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
