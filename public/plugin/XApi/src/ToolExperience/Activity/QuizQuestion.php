<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

use Answer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;

/**
 * Class QuizQuestion.
 */
class QuizQuestion extends BaseActivity
{
    private CQuizQuestion $question;

    public function __construct(CQuizQuestion $question)
    {
        $this->question = $question;
    }

    public function generate(): array
    {
        $iri = $this->generateIri(
            WEB_CODE_PATH,
            'xapi/quiz/',
            ['question' => $this->question->getId()]
        );

        return [
            'objectType' => 'Activity',
            'id' => $iri,
            'definition' => $this->generateActivityDefinitionFromQuestionType(),
        ];
    }

    private function generateActivityDefinitionFromQuestionType(): array
    {
        $languageIso = $this->resolveLanguageIso();
        $courseId = api_get_course_int_id();

        $questionTitle = trim(strip_tags((string) $this->question->getQuestion()));
        $questionDescription = trim(strip_tags((string) $this->question->getDescription()));

        $definition = [
            'name' => [
                $languageIso => $questionTitle,
            ],
            'type' => 'http://adlnet.gov/expapi/activities/question',
        ];

        if ('' !== $questionDescription) {
            $definition['description'] = [
                $languageIso => $questionDescription,
            ];
        }

        $objAnswer = new Answer($this->question->getId(), $courseId);
        $objAnswer->read();

        switch ($this->question->getType()) {
            case MULTIPLE_ANSWER:
            case UNIQUE_ANSWER:
            case UNIQUE_ANSWER_IMAGE:
            case READING_COMPREHENSION:
                $definition['interactionType'] = 'choice';
                $definition['choices'] = [];
                $correctResponsesPattern = [];

                for ($i = 1; $i <= $objAnswer->nbrAnswers; $i++) {
                    $choiceId = (string) $objAnswer->iid[$i];
                    $choiceText = trim((string) $objAnswer->selectAnswer($i));

                    $definition['choices'][] = [
                        'id' => $choiceId,
                        'description' => [
                            $languageIso => $choiceText,
                        ],
                    ];

                    if ($objAnswer->isCorrect($i)) {
                        $correctResponsesPattern[] = $choiceId;
                    }
                }

                if (!empty($correctResponsesPattern)) {
                    $definition['correctResponsesPattern'] = [
                        implode('[,]', $correctResponsesPattern),
                    ];
                }

                return $definition;

            case DRAGGABLE:
                $definition['interactionType'] = 'sequencing';
                $definition['choices'] = [];

                for ($i = 1; $i <= $objAnswer->nbrAnswers; $i++) {
                    if ((int) $objAnswer->correct[$i] > 0) {
                        $definition['choices'][] = [
                            'id' => (string) $objAnswer->correct[$i],
                            'description' => [
                                $languageIso => trim((string) $objAnswer->answer[$i]),
                            ],
                        ];
                    }
                }

                $correctResponsesPattern = array_slice(
                    (array) $objAnswer->autoId,
                    0,
                    (int) ($objAnswer->nbrAnswers / 2)
                );

                if (!empty($correctResponsesPattern)) {
                    $definition['correctResponsesPattern'] = [
                        implode('[,]', array_map('strval', $correctResponsesPattern)),
                    ];
                }

                return $definition;

            case MATCHING:
            case MATCHING_DRAGGABLE:
                $definition['interactionType'] = 'matching';
                $definition['source'] = [];
                $definition['target'] = [];
                $correctResponsesPattern = [];

                for ($i = 1; $i <= $objAnswer->nbrAnswers; $i++) {
                    $component = [
                        'id' => (string) $objAnswer->selectAutoId($i),
                        'description' => [
                            $languageIso => trim((string) $objAnswer->selectAnswer($i)),
                        ],
                    ];

                    if ((int) $objAnswer->correct[$i] > 0) {
                        $definition['source'][] = $component;
                        $correctResponsesPattern[] = (string) $objAnswer->selectAutoId($i).'[.]'.(string) $objAnswer->correct[$i];
                    } else {
                        $definition['target'][] = $component;
                    }
                }

                if (!empty($correctResponsesPattern)) {
                    $definition['correctResponsesPattern'] = [
                        implode('[,]', $correctResponsesPattern),
                    ];
                }

                return $definition;

            case FREE_ANSWER:
                $definition['interactionType'] = 'long-fill-in';

                return $definition;

            case FILL_IN_BLANKS:
            case HOT_SPOT:
            case HOT_SPOT_DELINEATION:
            case MULTIPLE_ANSWER_COMBINATION:
            case UNIQUE_ANSWER_NO_OPTION:
            case MULTIPLE_ANSWER_TRUE_FALSE:
            case MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY:
            case MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE:
            case GLOBAL_MULTIPLE_ANSWER:
            case CALCULATED_ANSWER:
            case ANNOTATION:
            case ORAL_EXPRESSION:
            default:
                $definition['interactionType'] = 'other';

                return $definition;
        }
    }
}
