<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ForumSettingsSchema
 * @package ChamiloLMS\CoreBundle\Settings
 */
class ForumSettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'default_forum_view' => '',
            ))
            ->setAllowedTypes(array(
                'default_forum_view' => array('string')
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
                'default_forum_view',
                'choice',
                array(
                    'choices' => array(
                        'flat' => 'Flat',
                        'threaded' => 'Threaded',
                        'nested' => 'Nested'
                    )
                )
            )
        ;
    }
}
