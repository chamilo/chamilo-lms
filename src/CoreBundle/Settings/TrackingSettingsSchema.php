<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
    public function buildSettings(AbstractSettingsBuilder $builder)
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
            ->add('header_extra_content', TextareaType::class)
            ->add('footer_extra_content', TextareaType::class)
            ->add('meta_title')
            ->add('meta_description', TextareaType::class)
            ->add('meta_image_path')
            ->add('meta_twitter_site')
            ->add('meta_twitter_creator')
        ;
    }
}
