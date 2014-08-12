<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ProfileSettingsSchema
 * @package ChamiloLMS\CoreBundle\Settings
 */
class ProfileSettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'profile' => '',  //
                'extended_profile' => '',
                'account_valid_duration' => '',
                'split_users_upload_directory' => '',
                'user_selected_theme' => '',
                'use_users_timezone' => '',
                'allow_users_to_change_email_with_no_password' => '',
                'login_is_email' => '',
            ))
            ->setAllowedTypes(array(
                'profile' => array('string'),
                'account_valid_duration' => array('integer')
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
                'profile',
                'choice',
                array(
                    'choices' => array(
                        'name',
                        'officialcode',
                        'email',
                        'picture',
                        'login',
                        'password',
                        'language',
                        'phone',
                        'openid',
                        'theme',
                        'apikeys'
                    )
                )
            )
            ->add('extended_profile', 'yes_no')
            ->add('account_valid_duration')
            ->add('split_users_upload_directory', 'yes_no')
            ->add('user_selected_theme', 'yes_no')
            ->add('use_users_timezone', 'yes_no')
            ->add('allow_users_to_change_email_with_no_password', 'yes_no')
            ->add('login_is_email', 'yes_no')
        ;
    }
}
