<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

class ExerciseSettingsSchema implements SchemaInterface
{
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'exercise_min_score' => 0,
                'exercise_max_score' => 0,
                'enable_quiz_scenario' => '',
            ))
            ->setAllowedTypes(array(
                'exercise_min_score' => array('integer'),
                'exercise_max_score' => array('integer'),
                'enable_quiz_scenario' => array('string'),
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('exercise_min_score')
            ->add('exercise_max_score')
            ->add('enable_quiz_scenario')
        ;
    }
}
