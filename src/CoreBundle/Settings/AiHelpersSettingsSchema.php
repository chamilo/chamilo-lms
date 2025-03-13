<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class AiHelpersSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'enable_ai_helpers' => 'false',
                    'ai_providers' => '',
                    'learning_path_generator' => 'false',
                    'exercise_generator' => 'false',
                    'open_answers_grader' => 'false',
                    'tutor_chatbot' => 'false',
                    'task_grader' => 'false',
                    'content_analyser' => 'false',
                    'image_generator' => 'false',
                ]
            )
        ;
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('enable_ai_helpers', YesNoType::class)
            ->add('ai_providers', TextareaType::class, [
                'help_html' => true,
                'help' => $this->settingArrayHelpValue('ai_providers'),
                'attr' => ['rows' => 10, 'style' => 'font-family: monospace;'],
            ])
            ->add('learning_path_generator', YesNoType::class)
            ->add('exercise_generator', YesNoType::class)
            ->add('open_answers_grader', YesNoType::class)
            ->add('tutor_chatbot', YesNoType::class)
            ->add('task_grader', YesNoType::class)
            ->add('content_analyser', YesNoType::class)
            ->add('image_generator', YesNoType::class)
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }

    private function settingArrayHelpValue(string $variable): string
    {
        $values = [
            'ai_providers' => '<pre>
            {
                "openai": {
                    "url": "https://api.openai.com/v1/chat/completions",
                    "api_key": "your-key",
                    "model": "gpt-4o",
                    "temperature": 0.7,
                    "organization_id": "org123",
                    "monthly_token_limit": 10000
                },
                "deepseek": {
                    "url": "https://api.deepseek.com/chat/completions",
                    "api_key": "your-key",
                    "model": "deepseek-chat",
                    "temperature": 0.7,
                    "organization_id": "org456",
                    "monthly_token_limit": 5000
                }
            }
            </pre>',
        ];

        $returnValue = [];
        if (isset($values[$variable])) {
            $returnValue = $values[$variable];
        }

        return $returnValue;
    }
}
