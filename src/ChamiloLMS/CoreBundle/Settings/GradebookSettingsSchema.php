<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

class GradebookSettingsSchema implements SchemaInterface
{
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'gradebook_enable' => '',
                'gradebook_score_display_coloring' => '',
                'gradebook_score_display_custom' => '',
                'gradebook_score_display_colorspl' => '',
                'gradebook_score_display_upperlim' => '',
                'gradebook_number_decimals' => '',
                'allow_hr_skills_management' => '',
                'teachers_can_change_score_settin' => '',
                'gradebook_enable_grade_model' => '',
                'teachers_can_change_grade_model_' => '',
                'gradebook_default_weight' => '',
                'gradebook_locking_enabled' => '',
                'gradebook_default_grade_model_id' => '',
                'gradebook_show_percentage_in_rep' => ''
            ))
            ->setAllowedTypes(array(
                'gradebook_enable' => array('string')
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('gradebook_enable')
        ;
    }
}
