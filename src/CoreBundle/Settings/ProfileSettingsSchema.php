<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Chamilo\SettingsBundle\Transformer\ArrayToIdentifierTransformer;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ProfileSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class ProfileSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults(
                [
                    'changeable_options' => [],
                    'extended_profile' => 'false',
                    'account_valid_duration' => '3660',
                    'split_users_upload_directory' => 'true',
                    'user_selected_theme' => 'false',
                    'use_users_timezone' => 'true',
                    'allow_users_to_change_email_with_no_password' => 'false',
                    'login_is_email' => 'false',
                    'profiling_filter_adding_users' => '',
                    'enable_profile_user_address_geolocalization' => '',
                    'allow_show_skype_account' => '',
                    'allow_show_linkedin_url' => '',
                    'is_editable' => 'true',
                    'hide_username_with_complete_name' => 'false',
                    'hide_username_in_course_chat' => 'false',
                    'show_official_code_whoisonline' => 'false',
                ]
            )
            ->setTransformer(
                'changeable_options',
                new ArrayToIdentifierTransformer()
            )
        ;
        $allowedTypes = [
            'changeable_options' => ['array'],
            'account_valid_duration' => ['string'],
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
                'changeable_options',
                ChoiceType::class,
                [
                    'multiple' => true,
                    'choices' => [
                        'Name' => 'name',
                        'Official code' => 'officialcode',
                        'E-mail' => 'email',
                        'Picture' => 'picture',
                        'Login' => 'login',
                        'Password' => 'password',
                        'Language' => 'language',
                        'Phone' => 'phone',
                        //'openid' => 'openid',
                        'Theme' => 'theme',
                        //'apikeys' => 'apikeys',
                    ],
                ]
            )
            ->add(
                'extended_profile',
                YesNoType::class,
                ['label' => 'ExtendedProfileTitle', 'help_block' => 'ExtendedProfileComment']
            )
            ->add('account_valid_duration')
            ->add('split_users_upload_directory', YesNoType::class)
            ->add('user_selected_theme', YesNoType::class)
            ->add('use_users_timezone', YesNoType::class)
            ->add('allow_users_to_change_email_with_no_password', YesNoType::class)
            ->add('login_is_email', YesNoType::class, ['label' => 'LoginIsEmailTitle'])
            ->add('profiling_filter_adding_users', YesNoType::class)
            ->add('enable_profile_user_address_geolocalization', YesNoType::class)
            ->add('allow_show_skype_account', YesNoType::class)
            ->add('allow_show_linkedin_url', YesNoType::class)
            ->add('is_editable', YesNoType::class)
            ->add('hide_username_with_complete_name', YesNoType::class)
            ->add('hide_username_in_course_chat', YesNoType::class)
            ->add('show_official_code_whoisonline', YesNoType::class)
        ;
    }
}
