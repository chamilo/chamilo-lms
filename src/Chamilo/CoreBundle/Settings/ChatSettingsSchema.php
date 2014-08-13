<?php

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ChatSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class ChatSettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'show_chat_folder' => '',
                'allow_global_chat' => '',
            ))
            ->setAllowedTypes(array(
                'allow_personal_agenda' => array('string')
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('allow_personal_agenda', 'yes_no')
            ->add('allow_global_chat', 'yes_no')
        ;
    }
}
