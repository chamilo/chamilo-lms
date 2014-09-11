<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ExerciseSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class ExerciseSettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'exercise_min_score' => 0,
                'exercise_max_score' => 0,
                'enable_quiz_scenario' => '',
            ))
            ->setAllowedTypes(array(
                'exercise_min_score' => array('string'),
                'exercise_max_score' => array('string'),
                'enable_quiz_scenario' => array('string'),
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('exercise_min_score')
            ->add('exercise_max_score')
            ->add('enable_quiz_scenario')
        ;
    }
}
