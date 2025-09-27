<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class TrackingSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
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
                    'tracking_skip_generic_data' => 'false',
                    'block_my_progress_page' => 'false',
                    'my_progress_course_tools_order' => '',
                ]
            )
        ;
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('header_extra_content', TextareaType::class)
            ->add('footer_extra_content', TextareaType::class)
            ->add('meta_title')
            ->add('meta_description', TextareaType::class)
            ->add('meta_image_path')
            ->add('meta_twitter_site')
            ->add('meta_twitter_creator')
            ->add('tracking_skip_generic_data', YesNoType::class)
            ->add('block_my_progress_page', YesNoType::class)
            ->add('my_progress_course_tools_order', TextareaType::class)
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
