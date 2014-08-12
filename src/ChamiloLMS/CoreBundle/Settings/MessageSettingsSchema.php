<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

class MessageSettingsSchema implements SchemaInterface
{
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'allow_message_tool' => '',
                'allow_send_message_to_all_platform_users' => '',
                'message_max_upload_filesize' => '',

            ))
            ->setAllowedTypes(array(
                'allow_message_tool' => array('string'),
                'message_max_upload_filesize' => array('integer')
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('allow_message_tool', 'yes_no')
            ->add('allow_send_message_to_all_platform_users', 'yes_no')
            ->add('message_max_upload_filesize')
        ;
    }
}
