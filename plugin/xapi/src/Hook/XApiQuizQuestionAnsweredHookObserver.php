<?php
/* For licensing terms, see /license.txt */

use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\ContextActivities;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Result as ActivityResult;
use Xabbuh\XApi\Model\Score;

/**
 * Class XApiQuizQuestionAnsweredHook.
 */
class XApiQuizQuestionAnsweredHookObserver
    extends XApiActivityHookObserver
    implements HookQuizQuestionAnsweredObserverInterface
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
     * @var \Chamilo\CoreBundle\Entity\Course
     */
    private $course;
    /**
     * @var \Chamilo\CoreBundle\Entity\Session|null
     */
    private $session;

    /**
     * @inheritDoc
     */
    public function hookQuizQuestionAnswered(HookQuizQuestionAnsweredEventInterface $event)
    {
        $data = $event->getEventData();

        $em = Database::getManager();

        $this->exe = $em->find('ChamiloCoreBundle:TrackEExercises', $data['exe_id']);
        $this->question = $em->find('ChamiloCourseBundle:CQuizQuestion', $data['question']['id']);
        $this->attempt = $em
            ->getRepository('ChamiloCoreBundle:TrackEAttempt')
            ->findOneBy(['exeId' => $this->exe->getExeId(), 'questionId' => $this->question->getId()]);
        $this->user = api_get_user_entity($this->exe->getExeUserId());
        $this->course = api_get_course_entity($this->exe->getCId());
        $this->session = api_get_session_entity($this->exe->getSessionId());

        try {
            $statement = $this
                ->createStatement()
                ->withCreated(
                    $this->attempt->getTms()
                );

            $sharedStmt = $this->sendStatementToLrs($statement);

            $this->saveSharedStatement(
                $sharedStmt->getId(),
                XApiPlugin::DATA_TYPE_ATTEMPT,
                $this->attempt->getId()
            );
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * @inheritDoc
     */
    protected function getActor()
    {
        return $this->generateActor(
            $this->user
        );
    }

    /**
     * @inheritDoc
     */
    protected function getVerb()
    {
        return $this->generateVerb(
            'answered',
            XApiPlugin::VERB_ANSWERED
        );
    }

    /**
     * @inheritDoc
     */
    protected function getActivity()
    {
        $questionTitle = strip_tags($this->question->getQuestion());
        $questionTitle = trim($questionTitle);
        $questionDescription = strip_tags($this->question->getDescription());
        $questionDescription = trim($questionDescription);

        $languageIso = api_get_language_isocode($this->course->getCourseLanguage());

        $titleMap = LanguageMap::create([$languageIso => $questionTitle]);
        $descriptionMap = $questionDescription ? LanguageMap::create([$languageIso => $questionDescription]) : null;

        $activityIdIri = $this->plugin->generateIri(
            $this->question->getId(),
            'quiz_question'
        );

        return new Activity(
            IRI::fromString($activityIdIri),
            new Definition(
                $titleMap,
                $descriptionMap,
                IRI::fromString(XApiPlugin::IRI_QUIZ_QUESTION)
            )
        );
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    protected function getContext()
    {
        $quizIri = $this->plugin->generateIri(
            $this->exe->getExeExoId(),
            XApiPlugin::TYPE_QUIZ
        );

        $quizActivity = new Activity(
            IRI::fromString($quizIri)
        );

        $activities = new ContextActivities(
            [$quizActivity]
        );

        return parent::getContext()->withContextActivities($activities);
    }

    /**
     * @inheritDoc
     */
    protected function getId()
    {
        return $this->generateId(
            XApiPlugin::DATA_TYPE_ATTEMPT,
            $this->attempt->getId()
        );
    }
}
