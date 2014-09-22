<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SecuritySettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class SecuritySettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'filter_terms' => '',
                'allow_browser_sniffer' => '',
                'admins_can_set_users_pass' => '', // ?
            ))
            ->setAllowedTypes(array(
                'filter_terms' => array('string'),
                'allow_browser_sniffer' => array('string'),
                'admins_can_set_users_pass' => array('string'),
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('filter_terms', 'textarea')
            ->add('allow_browser_sniffer', 'yes_no')
            ->add('admins_can_set_users_pass')
        ;
    }
}
