<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

class DropboxSettingsSchema implements SchemaInterface
{
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'dropbox_allow_overwrite' => '',
                'dropbox_max_filesize' => '',
                'dropbox_allow_just_upload' => '',
                'dropbox_allow_student_to_student' => '',
                'dropbox_allow_group' => '',
                'dropbox_allow_mailing' => '',

            ))
            ->setAllowedTypes(array(
                'dropbox_allow_overwrite' => array('string')
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('dropbox_allow_overwrite')
        ;
    }
}
