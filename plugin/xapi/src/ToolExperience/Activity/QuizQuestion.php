<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

use Answer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Interaction\ChoiceInteractionDefinition;
use Xabbuh\XApi\Model\Interaction\InteractionComponent;
use Xabbuh\XApi\Model\Interaction\LongFillInInteractionDefinition;
use Xabbuh\XApi\Model\Interaction\MatchingInteractionDefinition;
use Xabbuh\XApi\Model\Interaction\OtherInteractionDefinition;
use Xabbuh\XApi\Model\Interaction\SequencingInteractionDefinition;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;

/**
 * Class QuizQuestion.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Activity
 */
class QuizQuestion extends BaseActivity
{
    private $question;

    public function __construct(CQuizQuestion $question)
    {
        $this->question = $question;
    }

    public function generate(): Activity
    {
        $iri = $this->generateIri(
            WEB_CODE_PATH,
            'xapi/quiz/',
            ['question' => $this->question->getId()]
        );

        return new Activity(
            IRI::fromString($iri),
            $this->generateActivityDefinitionFromQuestionType()
        );
    }

    /**
     * @return \Xabbuh\XApi\Model\Interaction\InteractionDefinition
     */
    private function generateActivityDefinitionFromQuestionType()
    {
        $languageIso = api_get_language_isocode();
        $courseId = api_get_course_int_id();

        $questionTitle = strip_tags($this->question->getQuestion());
        $questionTitle = trim($questionTitle);
        $questionDescription = strip_tags($this->question->getDescription());
        $questionDescription = trim($questionDescription);

        $titleMap = LanguageMap::create([$languageIso => $questionTitle]);
        $descriptionMap = $questionDescription ? LanguageMap::create([$languageIso => $questionDescription]) : null;

        $objAnswer = new Answer($this->question->getId(), $courseId);
        $objAnswer->read();

        $type = IRI::fromString('http://adlnet.gov/expapi/activities/question');

        switch ($this->question->getType()) {
            case MULTIPLE_ANSWER:
            case UNIQUE_ANSWER:
            case UNIQUE_ANSWER_IMAGE:
            case READING_COMPREHENSION:
                $choices = [];
                $correctResponsesPattern = [];

                for ($i = 1; $i <= $objAnswer->nbrAnswers; $i++) {
                    $choices[] = new InteractionComponent(
                        $objAnswer->iid[$i],
                        LanguageMap::create([$languageIso => $objAnswer->selectAnswer($i)])
                    );

                    if ($objAnswer->isCorrect($i)) {
                        $correctResponsesPattern[] = $objAnswer->iid[$i];
                    }
                }

                return new ChoiceInteractionDefinition(
                    $titleMap,
                    $descriptionMap,
                    $type,
                    null,
                    null,
                    [implode('[,]', $correctResponsesPattern)],
                    $choices
                );
            case DRAGGABLE:
                $choices = [];

                for ($i = 1; $i <= $objAnswer->nbrAnswers; $i++) {
                    if ((int) $objAnswer->correct[$i] > 0) {
                        $choices[] = new InteractionComponent(
                            $objAnswer->correct[$i],
                            LanguageMap::create([$languageIso => $objAnswer->answer[$i]])
                        );
                    }
                }

                $correctResponsesPattern = array_slice($objAnswer->autoId, 0, $objAnswer->nbrAnswers / 2);

                return new SequencingInteractionDefinition(
                    $titleMap,
                    $descriptionMap,
                    $type,
                    null,
                    null,
                    [implode('[,]', $correctResponsesPattern)],
                    $choices
                );
            case MATCHING:
            case MATCHING_DRAGGABLE:
                /** @var array|InteractionComponent[] $source */
                $source = [];
                /** @var array|InteractionComponent[] $source */
                $target = [];
                $correctResponsesPattern = [];

                for ($i = 1; $i <= $objAnswer->nbrAnswers; $i++) {
                    $interactionComponent = new InteractionComponent(
                        $objAnswer->selectAutoId($i),
                        LanguageMap::create([$languageIso => $objAnswer->selectAnswer($i)])
                    );

                    if ((int) $objAnswer->correct[$i] > 0) {
                        $source[] = $interactionComponent;

                        $correctResponsesPattern[] = $objAnswer->selectAutoId($i).'[.]'.$objAnswer->correct[$i];
                    } else {
                        $target[] = $interactionComponent;
                    }
                }

                return new MatchingInteractionDefinition(
                    $titleMap,
                    $descriptionMap,
                    $type,
                    null,
                    null,
                    [implode('[,]', $correctResponsesPattern)],
                    $source,
                    $target
                );
            case FREE_ANSWER:
                return new LongFillInInteractionDefinition($titleMap, $descriptionMap, $type);
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
                return new OtherInteractionDefinition($titleMap, $descriptionMap, $type);
        }
    }
}
