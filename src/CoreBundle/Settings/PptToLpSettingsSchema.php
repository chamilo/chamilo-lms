<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class PptToLpSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class PptToLpSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults(
                [
                    'active' => '',
                    'size' => '',
                    'host' => '',
                    'port' => '',
                    'user' => '',
                    'ftp_password' => '',
                    'path_to_lzx' => '',
                ]
            )
//            ->setAllowedTypes(
//                array(
//
//                )
//            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('active', YesNoType::class)
            ->add('size')
            ->add('host')
            ->add('port')
            ->add('user')
            ->add('ftp_password')
            ->add('path_to_lzx');
    }
}
