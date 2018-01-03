<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DropboxSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class DropboxSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(
                array(
                    'dropbox_allow_overwrite' => 'true',
                    'dropbox_max_filesize' => '100000000',
                    'dropbox_allow_just_upload' => 'true',
                    'dropbox_allow_student_to_student' => 'true',
                    'dropbox_allow_group' => 'true',
                    'dropbox_allow_mailing' => 'false'
                )
            );

        $allowedTypes = array(
            'dropbox_allow_overwrite' => array('string'),
        );
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('dropbox_allow_overwrite', YesNoType::class)
            ->add('dropbox_max_filesize')
            ->add('dropbox_allow_just_upload', YesNoType::class)
            ->add('dropbox_allow_student_to_student', YesNoType::class)
            ->add('dropbox_allow_group', YesNoType::class)
            ->add('dropbox_allow_mailing', YesNoType::class)
        ;
    }
}
