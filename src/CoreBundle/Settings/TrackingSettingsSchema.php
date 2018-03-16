<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class TrackingSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class TrackingSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(
                [
                    'header_extra_content' => '',
                    'footer_extra_content' => '',
                    'meta_title' => '',
                    'meta_description' => '',
                    'meta_image_path' => '',
                    'meta_twitter_site' => '',
                    'meta_twitter_creator' => '',
                ]
            );
//            ->setAllowedTypes(
//                array(
//                    // commenting this line allows setting to be null
//                    //'header_extra_content' => array('string'),
//                    //'footer_extra_content' => array('string'),
//                )
//            );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('header_extra_content', 'textarea')
            ->add('footer_extra_content', 'textarea')
            ->add('meta_title', 'text')
            ->add('meta_description', 'textarea')
            ->add('meta_image_path', 'text')
            ->add('meta_twitter_site', 'text')
            ->add('meta_twitter_creator', 'text')
        ;
    }
}
