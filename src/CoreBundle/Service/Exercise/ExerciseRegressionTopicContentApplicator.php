<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestionEditor;
use InvalidArgumentException;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

final class ExerciseRegressionTopicContentApplicator
{
    /**
     * @param list<ExerciseQuestionEditor> $payloads
     * @param array<string, mixed>         $content
     */
    public function apply(array $payloads, array $content): void
    {
        foreach ($payloads as $payload) {
            if (!$payload instanceof ExerciseQuestionEditor) {
                throw new InvalidArgumentException('Invalid exercise regression payload.');
            }

            $type = (int) $payload->type;

            switch ($type) {
                case ExerciseRegressionFixtureQuestionFactory::UNIQUE_ANSWER:
                    $this->applySingleChoice($payload, $content['single_choice']);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::MULTIPLE_ANSWER:
                    $this->applyMultipleChoice($payload, $content['multiple_choice'], 5.0);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::FILL_IN_BLANKS:
                    $this->applyFillBlanks($payload, $content['fill_blanks'], false);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::MATCHING:
                case ExerciseRegressionFixtureQuestionFactory::MATCHING_DRAGGABLE:
                    $this->applyMatching($payload, $content['matching'], false);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::FREE_ANSWER:
                    $this->applyPrompt($payload, $content['open']);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::HOT_SPOT:
                case ExerciseRegressionFixtureQuestionFactory::HOT_SPOT_DELINEATION:
                    $this->applyHotspot($payload, $content['hotspot']);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::MULTIPLE_ANSWER_COMBINATION:
                    $this->applyMultipleChoice($payload, $content['multiple_choice'], 0.0);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::UNIQUE_ANSWER_NO_OPTION:
                    $this->applySingleChoiceWithUnknown($payload, $content['single_choice']);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::MULTIPLE_ANSWER_TRUE_FALSE:
                case ExerciseRegressionFixtureQuestionFactory::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE:
                case ExerciseRegressionFixtureQuestionFactory::MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY:
                    $this->applyTrueFalse($payload, $content['true_false']);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::ORAL_EXPRESSION:
                    $this->applyPrompt($payload, $content['oral']);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::GLOBAL_MULTIPLE_ANSWER:
                    $this->applyMultipleChoice($payload, $content['multiple_choice'], 0.0);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::MEDIA_QUESTION:
                    $payload->title = (string) $content['media']['title'];
                    $payload->description = $this->paragraph((string) $content['media']['context']);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::CALCULATED_ANSWER:
                    $this->applyCalculated($payload, $content['calculated']);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::UNIQUE_ANSWER_IMAGE:
                    $this->applyImageChoice($payload, $content['image_choice']);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::DRAGGABLE:
                    $this->applyOrdering($payload, $content['ordering']);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::ANNOTATION:
                    $this->applyPrompt($payload, $content['annotation']);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::READING_COMPREHENSION:
                    $payload->title = (string) $content['reading']['title'];
                    $payload->description = $this->paragraph((string) $content['reading']['passage']);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::UPLOAD_ANSWER:
                    $this->applyPrompt($payload, $content['upload']);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::MATCHING_COMBINATION:
                case ExerciseRegressionFixtureQuestionFactory::MATCHING_DRAGGABLE_COMBINATION:
                    $this->applyMatching($payload, $content['matching'], true);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::HOT_SPOT_COMBINATION:
                    $this->applyHotspot($payload, $content['hotspot']);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::FILL_IN_BLANKS_COMBINATION:
                    $this->applyFillBlanks($payload, $content['fill_blanks'], true);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::MULTIPLE_ANSWER_DROPDOWN_COMBINATION:
                    $this->applyDropdownCombination($payload, $content['multiple_choice']);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::MULTIPLE_ANSWER_DROPDOWN:
                    $this->applyDropdown($payload, $content['dropdown']);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::ANSWER_IN_OFFICE_DOC:
                    $this->applyPrompt($payload, $content['office']);

                    break;

                case ExerciseRegressionFixtureQuestionFactory::PAGE_BREAK:
                    $payload->title = (string) $content['page_break']['title'];
                    $payload->description = $this->paragraph((string) $content['page_break']['text']);

                    break;
            }
        }
    }

    /**
     * @param array<string, mixed> $block
     */
    private function applySingleChoice(ExerciseQuestionEditor $payload, array $block): void
    {
        $payload->title = (string) $block['question'];
        $payload->answers = $this->choiceAnswers(
            $block['options'],
            [(int) $block['correct_index']],
            10.0,
        );
        $this->applyFeedback($payload, $block);
    }

    /**
     * @param array<string, mixed> $block
     */
    private function applySingleChoiceWithUnknown(ExerciseQuestionEditor $payload, array $block): void
    {
        $payload->title = (string) $block['question'];
        $payload->answers = $this->choiceAnswers(
            $block['options'],
            [(int) $block['correct_index']],
            10.0,
        );
        $payload->answers[] = [
            'answer' => "Don't know",
            'correct' => false,
            'correctChoice' => 0,
            'comment' => '',
            'score' => 0.0,
            'position' => 666,
            'isUnknown' => true,
        ];
        $this->applyFeedback($payload, $block);
    }

    /**
     * @param array<string, mixed> $block
     */
    private function applyMultipleChoice(ExerciseQuestionEditor $payload, array $block, float $correctScore): void
    {
        $payload->title = (string) $block['question'];
        $payload->answers = $this->choiceAnswers(
            $block['options'],
            $block['correct_indexes'],
            $correctScore,
        );
        $this->applyFeedback($payload, $block);
    }

    /**
     * @param array<string, mixed> $block
     */
    private function applyFillBlanks(ExerciseQuestionEditor $payload, array $block, bool $combination): void
    {
        $answers = array_map(
            static fn (string $answer): string => trim(str_replace(['[', ']'], '', $answer)),
            $block['answers'],
        );
        $text = str_replace(
            ['{blank1}', '{blank2}'],
            ['['.$answers[0].']', '['.$answers[1].']'],
            (string) $block['template'],
        );

        $payload->title = (string) $block['question'];
        $payload->fillBlanksText = $text;
        $payload->fillBlankItems = [
            ['answer' => $answers[0], 'score' => $combination ? 0.0 : 5.0, 'inputSize' => 200, 'position' => 1],
            ['answer' => $answers[1], 'score' => $combination ? 0.0 : 5.0, 'inputSize' => 200, 'position' => 2],
        ];
        $payload->fillBlanksComment = (string) ($block['feedback'] ?: 'Complete both blanks.');
        $this->applyFeedback($payload, $block);
    }

    /**
     * @param array<string, mixed> $block
     */
    private function applyMatching(ExerciseQuestionEditor $payload, array $block, bool $combination): void
    {
        $pairs = $block['pairs'];
        $payload->title = (string) $block['question'];
        $payload->matchingOptions = [
            ['localId' => 'option-1', 'answer' => (string) $pairs[0]['right'], 'position' => 1],
            ['localId' => 'option-2', 'answer' => (string) $pairs[1]['right'], 'position' => 2],
        ];
        $payload->matchingPairs = [
            ['answer' => (string) $pairs[0]['left'], 'optionLocalId' => 'option-1', 'comment' => '', 'score' => $combination ? 0.0 : 5.0, 'position' => 1],
            ['answer' => (string) $pairs[1]['left'], 'optionLocalId' => 'option-2', 'comment' => '', 'score' => $combination ? 0.0 : 5.0, 'position' => 2],
        ];
        $this->applyFeedback($payload, $block);
    }

    /**
     * @param array<string, mixed> $block
     */
    private function applyPrompt(ExerciseQuestionEditor $payload, array $block): void
    {
        $payload->title = (string) $block['question'];
        $payload->description = $this->paragraph((string) $block['question']);
    }

    /**
     * @param array<string, mixed> $block
     */
    private function applyTrueFalse(ExerciseQuestionEditor $payload, array $block): void
    {
        $payload->title = (string) $block['question'];
        $payload->answers = [
            [
                'answer' => (string) $block['true_statement'],
                'correct' => false,
                'correctChoice' => 1,
                'comment' => '',
                'score' => 0.0,
                'position' => 1,
                'isUnknown' => false,
            ],
            [
                'answer' => (string) $block['false_statement'],
                'correct' => false,
                'correctChoice' => 2,
                'comment' => '',
                'score' => 0.0,
                'position' => 2,
                'isUnknown' => false,
            ],
        ];
        $this->applyFeedback($payload, $block);
    }

    /**
     * @param array<string, mixed> $block
     */
    private function applyCalculated(ExerciseQuestionEditor $payload, array $block): void
    {
        $unit = trim((string) $block['unit']);
        $suffix = '' !== $unit ? ' '.$unit : '';
        $payload->title = (string) $block['question'];
        $payload->calculatedText = $this->paragraph((string) $block['context'].' [x] + [y]'.$suffix.'. Result: []');
        $payload->calculatedComment = (string) ($block['feedback'] ?: 'Add the two generated values.');
        $this->applyFeedback($payload, $block);
    }

    /**
     * @param array<string, mixed> $block
     */
    private function applyImageChoice(ExerciseQuestionEditor $payload, array $block): void
    {
        $payload->title = (string) $block['question'];
        $labels = [(string) $block['correct_label'], (string) $block['wrong_label']];

        foreach ($payload->answers as $index => &$answer) {
            if (!isset($labels[$index])) {
                continue;
            }
            $caption = '<br><span>'.$this->escape($labels[$index]).'</span>';
            $html = (string) ($answer['answer'] ?? '');
            $answer['answer'] = str_contains($html, '</p>')
                ? str_replace('</p>', $caption.'</p>', $html)
                : $html.$caption;
        }
        unset($answer);

        $this->applyFeedback($payload, $block);
    }

    /**
     * @param array<string, mixed> $block
     */
    private function applyOrdering(ExerciseQuestionEditor $payload, array $block): void
    {
        $payload->title = (string) $block['question'];
        $payload->draggableItems = [
            ['answer' => (string) $block['items'][0], 'targetPosition' => 1, 'score' => 5.0, 'position' => 1],
            ['answer' => (string) $block['items'][1], 'targetPosition' => 2, 'score' => 5.0, 'position' => 2],
        ];
        $this->applyFeedback($payload, $block);
    }

    /**
     * @param array<string, mixed> $block
     */
    private function applyHotspot(ExerciseQuestionEditor $payload, array $block): void
    {
        $payload->title = (string) $block['question'];
        $payload->description = $this->paragraph((string) $block['question']);
        if (isset($payload->hotspotItems[0])) {
            $payload->hotspotItems[0]['answer'] = (string) $block['target_label'];
        }
    }

    /**
     * @param array<string, mixed> $block
     */
    private function applyDropdownCombination(ExerciseQuestionEditor $payload, array $block): void
    {
        $this->applyMultipleChoice($payload, $block, 0.0);
        $payload->dropdownListText = implode("\n", $block['options']);
    }

    /**
     * @param array<string, mixed> $block
     */
    private function applyDropdown(ExerciseQuestionEditor $payload, array $block): void
    {
        $this->applySingleChoice($payload, $block);
        $payload->dropdownListText = implode("\n", $block['options']);
    }

    /**
     * @param list<string> $labels
     * @param list<int>    $correctIndexes
     *
     * @return list<array<string, mixed>>
     */
    private function choiceAnswers(array $labels, array $correctIndexes, float $correctScore): array
    {
        $answers = [];
        foreach (array_values($labels) as $index => $label) {
            $correct = \in_array($index, $correctIndexes, true);
            $answers[] = [
                'answer' => (string) $label,
                'correct' => $correct,
                'correctChoice' => 0,
                'comment' => '',
                'score' => $correct ? $correctScore : 0.0,
                'position' => $index + 1,
                'isUnknown' => false,
            ];
        }

        return $answers;
    }

    /**
     * @param array<string, mixed> $block
     */
    private function applyFeedback(ExerciseQuestionEditor $payload, array $block): void
    {
        $feedback = trim((string) ($block['feedback'] ?? ''));
        if ('' !== $feedback) {
            $payload->feedback = $this->paragraph($feedback);
        }
    }

    private function paragraph(string $text): string
    {
        return '<p>'.$this->escape($text).'</p>';
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
