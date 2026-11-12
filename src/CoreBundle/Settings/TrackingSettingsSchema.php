<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Range;

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
                    'hide_ip_address' => 'true',
                    'block_my_progress_page' => 'false',
                    'my_progress_course_tools_order' => '',
                    'maximum_inactive_time_to_consider' => '15',
                    'max_time_spent_per_session_threshold' => '360',
                    'recycle_bin_retention_hours' => '720',
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
            ->add('hide_ip_address', YesNoType::class)
            ->add('block_my_progress_page', YesNoType::class)
            ->add('my_progress_course_tools_order', TextareaType::class)
            ->add('maximum_inactive_time_to_consider')
            ->add('max_time_spent_per_session_threshold', IntegerType::class, [
                'constraints' => [new Range(min: 1, max: 525600)],
            ])
            ->add('recycle_bin_retention_hours', IntegerType::class, [
                'constraints' => [new Range(min: 1, max: 87600)],
            ])
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
