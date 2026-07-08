<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class AiHelpersSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults([
                'enable_ai_helpers' => 'false',
                'ai_providers' => '',
                'learning_path_generator' => 'false',
                'exercise_generator' => 'false',
                'open_answers_grader' => 'false',
                'tutor_chatbot' => 'false',
                'task_grader' => 'false',
                'content_analyser' => 'false',
                'image_generator' => 'false',
                'glossary_terms_generator' => 'false',
                'video_generator' => 'false',
                'course_analyser' => 'false',
                'disclose_ai_assistance' => 'true',
            ])
        ;
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('enable_ai_helpers', YesNoType::class)
            ->add('ai_providers', TextareaType::class, [
                'attr' => [
                    'rows' => 10,
                    'cols' => 100,
                    'style' => 'font-family: monospace;',
                ],
            ])
        ;

        $featureChoices = [
            'Yes' => 'true',
            'No' => 'false',
            'Plugin-defined' => 'plugin_defined',
        ];

        foreach ([
            'learning_path_generator',
            'exercise_generator',
            'open_answers_grader',
            'tutor_chatbot',
            'task_grader',
            'content_analyser',
            'image_generator',
            'glossary_terms_generator',
            'video_generator',
            'course_analyser',
        ] as $feature) {
            $builder->add($feature, ChoiceType::class, [
                'choices' => $featureChoices,
            ]);
        }

        $builder->add('disclose_ai_assistance', YesNoType::class);

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
