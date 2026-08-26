<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Service\Exercise\ExerciseRegressionFixtureQuestionFactory;
use Chamilo\CoreBundle\Service\Exercise\ExerciseRegressionTopicContentApplicator;
use Chamilo\CoreBundle\Service\Exercise\ExerciseRegressionTopicContentParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use const JSON_THROW_ON_ERROR;

final class ExerciseRegressionTopicContentTest extends TestCase
{
    public function testParserAcceptsCompleteTopicContent(): void
    {
        $parser = new ExerciseRegressionTopicContentParser();
        $content = $parser->parse("```json\n".json_encode($this->validContent(), JSON_THROW_ON_ERROR)."\n```");

        self::assertSame('What is supervised learning?', $content['single_choice']['question']);
        self::assertSame(0, $content['single_choice']['correct_index']);
        self::assertSame([0, 1, 3], $content['multiple_choice']['correct_indexes']);
        self::assertSame(['Training', 'Evaluation'], $content['ordering']['items']);
        self::assertStringContainsString('{blank1}', $content['fill_blanks']['template']);
    }

    public function testParserRejectsIncompleteTopicContent(): void
    {
        $parser = new ExerciseRegressionTopicContentParser();
        $this->expectException(InvalidArgumentException::class);

        $parser->parse('{"single_choice":{"question":"Q","options":["A","B","C"],"correct_index":0}}');
    }

    public function testApplicatorReplacesQaLabelsWithTopicContentWithoutChangingTypes(): void
    {
        $parser = new ExerciseRegressionTopicContentParser();
        $content = $parser->parse(json_encode($this->validContent(), JSON_THROW_ON_ERROR));
        $factory = new ExerciseRegressionFixtureQuestionFactory();
        $payloads = [
            $factory->create(ExerciseRegressionFixtureQuestionFactory::UNIQUE_ANSWER),
            $factory->create(ExerciseRegressionFixtureQuestionFactory::FILL_IN_BLANKS),
            $factory->create(ExerciseRegressionFixtureQuestionFactory::UNIQUE_ANSWER_IMAGE),
            $factory->create(ExerciseRegressionFixtureQuestionFactory::DRAGGABLE),
            $factory->create(ExerciseRegressionFixtureQuestionFactory::ANSWER_IN_OFFICE_DOC),
        ];

        (new ExerciseRegressionTopicContentApplicator())->apply($payloads, $content);

        self::assertSame(1, $payloads[0]->type);
        self::assertSame('What is supervised learning?', $payloads[0]->title);
        self::assertSame('Learning from labeled examples', $payloads[0]->answers[0]['answer']);
        self::assertTrue($payloads[0]->answers[0]['correct']);

        self::assertSame(3, $payloads[1]->type);
        self::assertStringContainsString('[model]', $payloads[1]->fillBlanksText);
        self::assertStringContainsString('[data]', $payloads[1]->fillBlanksText);

        self::assertSame(17, $payloads[2]->type);
        self::assertStringContainsString('Training example', $payloads[2]->answers[0]['answer']);

        self::assertSame(18, $payloads[3]->type);
        self::assertSame('Training', $payloads[3]->draggableItems[0]['answer']);
        self::assertSame('Evaluation', $payloads[3]->draggableItems[1]['answer']);

        self::assertSame(30, $payloads[4]->type);
        self::assertSame('Write a short report explaining one responsible use of AI.', $payloads[4]->title);
        self::assertNotEmpty($payloads[4]->onlyofficeTemplateData);
    }

    /**
     * @return array<string, mixed>
     */
    private function validContent(): array
    {
        return [
            'single_choice' => [
                'question' => 'What is supervised learning?',
                'options' => ['Learning from labeled examples', 'Learning without data', 'Random guessing'],
                'correct_index' => 0,
                'feedback' => 'Supervised learning uses labeled examples.',
            ],
            'multiple_choice' => [
                'question' => 'Which practices support responsible AI?',
                'options' => ['Evaluate bias', 'Protect data', 'Ignore errors', 'Document limitations'],
                'correct_indexes' => [0, 1, 3],
                'feedback' => 'Responsible AI requires active evaluation and governance.',
            ],
            'fill_blanks' => [
                'question' => 'Complete the AI statement.',
                'template' => 'A {blank1} learns patterns from {blank2}.',
                'answers' => ['model', 'data'],
                'feedback' => 'Models learn patterns from data.',
            ],
            'matching' => [
                'question' => 'Match each concept with its role.',
                'pairs' => [
                    ['left' => 'Training data', 'right' => 'Examples used to learn'],
                    ['left' => 'Evaluation set', 'right' => 'Examples used to measure performance'],
                ],
                'feedback' => 'Training and evaluation data have different purposes.',
            ],
            'open' => ['question' => 'Explain one limitation of an AI model.'],
            'true_false' => [
                'question' => 'Classify each statement about AI.',
                'true_statement' => 'AI models can reproduce bias present in data.',
                'false_statement' => 'AI models are always correct.',
                'feedback' => 'AI outputs require evaluation.',
            ],
            'oral' => ['question' => 'Describe in your own words how AI can support learning.'],
            'media' => [
                'title' => 'AI systems and data',
                'context' => 'AI systems learn patterns from examples and must be evaluated before deployment.',
            ],
            'calculated' => [
                'question' => 'Calculate the total number of reviewed AI examples.',
                'context' => 'Two review batches contain',
                'unit' => 'examples',
                'feedback' => 'Add the two review batches.',
            ],
            'image_choice' => [
                'question' => 'Choose the image option labeled as the training example.',
                'correct_label' => 'Training example',
                'wrong_label' => 'Unrelated example',
                'feedback' => 'The training example is the intended option.',
            ],
            'ordering' => [
                'question' => 'Order these two steps in an AI workflow.',
                'items' => ['Training', 'Evaluation'],
                'feedback' => 'The model is trained before final evaluation.',
            ],
            'annotation' => ['question' => 'Mark the highlighted test region and relate it to model evaluation.'],
            'reading' => [
                'title' => 'Responsible use of AI',
                'passage' => 'AI systems can assist people with complex tasks. Their outputs should be evaluated for accuracy and bias. Sensitive data should be protected. Human oversight remains important.',
            ],
            'upload' => ['question' => 'Upload a short note describing one AI use case and one risk.'],
            'dropdown' => [
                'question' => 'Which term best describes data used to assess a trained model?',
                'options' => ['Evaluation data', 'Random noise', 'Password data'],
                'correct_index' => 0,
                'feedback' => 'Evaluation data measures model performance.',
            ],
            'hotspot' => [
                'question' => 'Select the highlighted test region used to verify the interaction.',
                'target_label' => 'AI evaluation region',
            ],
            'office' => ['question' => 'Write a short report explaining one responsible use of AI.'],
            'page_break' => [
                'title' => 'Continue with AI applications',
                'text' => 'The next section continues the topic with additional interaction types.',
            ],
        ];
    }
}
