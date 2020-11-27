<?php

/* For licensing terms, see /license.txt */

use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\Interaction\ChoiceInteractionDefinition;
use Xabbuh\XApi\Model\Interaction\InteractionComponent;
use Xabbuh\XApi\Model\Interaction\LongFillInInteractionDefinition;
use Xabbuh\XApi\Model\Interaction\MatchingInteractionDefinition;
use Xabbuh\XApi\Model\Interaction\OtherInteractionDefinition;
use Xabbuh\XApi\Model\Interaction\SequencingInteractionDefinition;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Result as ActivityResult;
use Xabbuh\XApi\Model\Score;

/**
 * Class XApiQuizQuestionAnsweredHook.
 */
class XApiQuizQuestionAnsweredHookObserver extends XApiActivityHookObserver implements HookQuizQuestionAnsweredObserverInterface
{
    use XApiStatementTrait;

    /**
     * @var \Chamilo\CoreBundle\Entity\TrackEExercises
     */
    private $exe;
    /**
     * @var \Chamilo\CoreBundle\Entity\TrackEAttempt
     */
    private $attempt;
    /**
     * @var \Chamilo\CourseBundle\Entity\CQuizQuestion
     */
    private $question;
    /**
     * @var array
     */
    private $quizInfo;

    /**
     * {@inheritdoc}
     */
    public function hookQuizQuestionAnswered(HookQuizQuestionAnsweredEventInterface $event)
    {
        $data = $event->getEventData();

        $em = Database::getManager();

        $this->exe = $em->find('ChamiloCoreBundle:TrackEExercises', $data['exe_id']);
        $this->quizInfo = $data['quiz'];
        $this->question = $em->find('ChamiloCourseBundle:CQuizQuestion', $data['question']['id']);
        $this->attempt = $em
            ->getRepository('ChamiloCoreBundle:TrackEAttempt')
            ->findOneBy(['exeId' => $this->exe->getExeId(), 'questionId' => $this->question->getId()]);
        $this->user = api_get_user_entity($this->exe->getExeUserId());
        $this->course = api_get_course_entity($this->exe->getCId());
        $this->session = api_get_session_entity($this->exe->getSessionId());

        try {
            $statement = $this->createStatement(
                $this->attempt->getTms()
            );
        } catch (Exception $e) {
            return;
        }

        $this->saveSharedStatement($statement);
    }

    /**
     * {@inheritdoc}
     */
    protected function getActor()
    {
        return $this->generateActor(
            $this->user
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getVerb()
    {
        return $this->generateVerb(
            'answered',
            XApiPlugin::VERB_ANSWERED
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getActivity()
    {
        $id = $this->plugin->generateIri(
            $this->question->getId(),
            'quiz_question'
        );

        return new Activity(
            $id,
            $this->generateActivityDefinitionFromQuestionType()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getActivityResult()
    {
        $raw = $this->attempt->getMarks();
        $max = $this->question->getPonderation();
        $scaled = $raw / $max;

        return new ActivityResult(
            new Score($scaled, $raw, null, $max),
            null,
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getContext()
    {
        $languageIso = api_get_language_isocode($this->course->getCourseLanguage());

        $id = $this->plugin->generateIri($this->quizInfo['id'], XApiPlugin::TYPE_QUIZ);

        $quizActivity = new Activity(
            $id,
            new Definition(
                LanguageMap::create([$languageIso => $this->quizInfo['title']]),
                null,
                IRI::fromString(XApiPlugin::IRI_QUIZ)
            )
        );

        $context = parent::getContext();
        $contextActivities = $context->getContextActivities()->withAddedGroupingActivity($quizActivity);

        return $context->withContextActivities($contextActivities);
    }

    /**
     * {@inheritdoc}
     */
    protected function getId()
    {
        return $this->generateId(
            XApiPlugin::DATA_TYPE_ATTEMPT,
            $this->attempt->getId()
        );
    }

    /**
     * @return \Xabbuh\XApi\Model\Interaction\InteractionDefinition
     */
    private function generateActivityDefinitionFromQuestionType()
    {
        $languageIso = api_get_language_isocode($this->course->getCourseLanguage());

        $questionTitle = strip_tags($this->question->getQuestion());
        $questionTitle = trim($questionTitle);
        $questionDescription = strip_tags($this->question->getDescription());
        $questionDescription = trim($questionDescription);

        $titleMap = LanguageMap::create([$languageIso => $questionTitle]);
        $descriptionMap = $questionDescription ? LanguageMap::create([$languageIso => $questionDescription]) : null;

        $objAnswer = new Answer($this->question->getId(), $this->course->getId());
        $objAnswer->read();

        $type = IRI::fromString(XApiPlugin::IRI_INTERACTION);

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
                return new LongFillInInteractionDefinition(
                    $titleMap,
                    $descriptionMap,
                    $type
                );
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
                return new OtherInteractionDefinition(
                    $titleMap,
                    $descriptionMap,
                    $type
                );
        }
    }
}
