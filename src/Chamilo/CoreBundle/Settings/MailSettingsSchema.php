<?php

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class MailSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class MailSettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
               'noreply_email_address' => '',
               'activate_email_template' => '',

            ))
            ->setAllowedTypes(array(
                'noreply_email_address' => array('string')
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('noreply_email_address', 'email')
            ->add('activate_email_template', 'yes_no')
        ;
    }
}
