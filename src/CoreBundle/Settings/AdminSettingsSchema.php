<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class AdminSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class AdminSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults(
                [
                    'administrator_email' => '',
                    'administrator_name' => '',
                    'administrator_surname' => '',
                    'administrator_phone' => '',
                    'redirect_admin_to_courses_list' => 'false',
                ]
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
            ->add('administrator_name',
                TextType::class,
                ['label' => 'emailAdministratorTitle', 'help_block' => 'emailAdministratorComment'])
            ->add('administrator_surname')
            ->add('administrator_email', EmailType::class)
            ->add('administrator_phone')
            ->add('redirect_admin_to_courses_list', YesNoType::class)

        ;
    }
}
