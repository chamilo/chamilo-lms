<?php

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DropboxSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class DropboxSettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('dropbox_allow_overwrite', 'yes_no')
            ->add('dropbox_max_filesize')
            ->add('dropbox_allow_just_upload', 'yes_no')
            ->add('dropbox_allow_student_to_student', 'yes_no')
            ->add('dropbox_allow_group', 'yes_no')
            ->add('dropbox_allow_mailing', 'yes_no')
        ;
    }
}
