<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class AdminSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class AdminSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(
                array(
                    'administrator_email' => '',
                    'administrator_name' => '',
                    'administrator_surname' => '',
                    'administrator_phone' => '',
                    'redirect_admin_to_courses_list' => 'false'
                )
            );
//            ->setAllowedTypes(
//                array(
//                    //'administrator_email' => array('string'),
//                    //'administrator_name' => array('string'),
//                    //'administrator_surname' => array('string'),
//                )
//            );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('administrator_name')
            ->add('administrator_surname')
            ->add('administrator_email', 'email')
            ->add('administrator_phone')
            ->add('redirect_admin_to_courses_list', YesNoType::class)

        ;
    }
}
