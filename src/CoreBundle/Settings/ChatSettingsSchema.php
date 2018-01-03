<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ChatSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class ChatSettingsSchema extends AbstractSettingsSchema
{

    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(
                array(
                    'show_chat_folder' => 'true',
                    'allow_global_chat' => 'true',
                )
            );
        $allowedTypes = array(
            'show_chat_folder' => array('string'),
        );
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('allow_global_chat', YesNoType::class)
            ->add('show_chat_folder', YesNoType::class)
        ;
    }
}
