<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

class ProfileSettingsSchema implements SchemaInterface
{
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'profile' => '',  // name, officialcode, email, picture, login, password, language, phone openid, theme apikeys
                'extended_profile' => '',
                'account_valid_duration' => '',
                'split_users_upload_directory' => '',
                'user_selected_theme' => '',
                'use_users_timezone' => '',
                'allow_users_to_change_email_with_no_password' => '',
                'login_is_email' => '',


            ))
            ->setAllowedTypes(array(
                'profile' => array('string')
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('profile')

            /*->add('enable_help_link', 'choice', array('choices' =>
                array('true' => 'Yes', 'no' => 'No'))
            )*/
        ;
    }
}
