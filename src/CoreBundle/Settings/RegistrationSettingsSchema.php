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
            ->setDefaults([
                'required_profile_fields' => [],
                'allow_registration' => 'false',
                'allow_registration_as_teacher' => 'false',
                'allow_lostpassword' => 'true',
                'extendedprofile_registration' => [],
                'extendedprofile_registrationrequired' => [],
                'allow_terms_conditions' => 'false',
                'student_autosubscribe' => '',
                'teacher_autosubscribe' => '',
                'drh_autosubscribe' => '',
                'sessionadmin_autosubscribe' => '',
                'platform_unsubscribe_allowed' => 'false',
                'required_extra_fields_in_inscription' => '',
                'allow_fields_inscription' => '',
                'send_inscription_msg_to_inbox' => 'false',
                'redirect_after_login' => '',
                'hide_legal_accept_checkbox' => 'false',
            ])
            ->setTransformer('required_profile_fields', new ArrayToIdentifierTransformer())
            ->setTransformer('extendedprofile_registration', new ArrayToIdentifierTransformer())
            ->setTransformer('extendedprofile_registrationrequired', new ArrayToIdentifierTransformer())
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
            ->add('required_profile_fields', ChoiceType::class, [
                'multiple' => true,
                'choices' => [
                    'Official code' => 'officialcode',
                    'E-mail' => 'email',
                    'Language' => 'language',
                    'Phone' => 'phone',
                ],
            ])
            ->add('allow_registration', ChoiceType::class, [
                'choices' => [
                    'Yes' => 'true',
                    'No' => 'false',
                    'Mail confirmation' => 'confirmation',
                    'Approval' => 'approval',
                ],
            ])
            ->add('allow_registration_as_teacher', YesNoType::class)
            ->add('allow_lostpassword', YesNoType::class)
            ->add('extendedprofile_registration', ChoiceType::class, [
                'multiple' => true,
                'choices' => $extendedProfileOptions,
            ])
            ->add('extendedprofile_registrationrequired', ChoiceType::class, [
                'multiple' => true,
                'choices' => $extendedProfileOptions,
            ])
            ->add('allow_terms_conditions', YesNoType::class)
            ->add('student_autosubscribe')
            ->add('teacher_autosubscribe')
            ->add('drh_autosubscribe')
            ->add('sessionadmin_autosubscribe')
            ->add('platform_unsubscribe_allowed', YesNoType::class)
            ->add('required_extra_fields_in_inscription', TextareaType::class)
            ->add('allow_fields_inscription', TextareaType::class)
            ->add('send_inscription_msg_to_inbox', YesNoType::class)
            ->add('redirect_after_login', TextareaType::class, [
                'required' => false,
            ])
            ->add('hide_legal_accept_checkbox', YesNoType::class)
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
