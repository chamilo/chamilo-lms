<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class GlossarySettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class GlossarySettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(
                array(
                    'show_glossary_in_extra_tools' => '',
                )
            );

        $allowedTypes = array(
            'show_glossary_in_extra_tools' => array('string'),
        );
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add(
                'show_glossary_in_extra_tools',
                'choice',
                [
                    'choices' => [
                        'none' => 'None',
                        'exercise' => 'Exercise',
                        'lp' => 'LearningPath',
                        'exercise_and_lp' => 'ExerciseAndLearningPath'
                    ]
                ]
            )
        ;
    }
}
