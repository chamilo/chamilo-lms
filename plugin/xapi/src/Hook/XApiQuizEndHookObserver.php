<?php

/* For licensing terms, see /license.txt */

use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Result as ActivityResult;
use Xabbuh\XApi\Model\Score;

/**
 * Class XApiQuizEndHookObserver.
 */
class XApiQuizEndHookObserver extends XApiActivityHookObserver implements HookQuizEndObserverInterface
{
    use XApiStatementTrait;

    /**
     * @var \Chamilo\CoreBundle\Entity\TrackEExercises
     */
    private $exe;
    /**
     * @var \Chamilo\CourseBundle\Entity\CQuiz
     */
    private $quiz;

    /**
     * {@inheritdoc}
     */
    public function hookQuizEnd(HookQuizEndEventInterface $hookEvent)
    {
        $data = $hookEvent->getEventData();
        $em = Database::getManager();

        $this->exe = $em->find('ChamiloCoreBundle:TrackEExercises', $data['exe_id']);
        $this->quiz = $em->find('ChamiloCourseBundle:CQuiz', $this->exe->getExeExoId());
        $this->user = api_get_user_entity($this->exe->getExeUserId());
        $this->course = api_get_course_entity($this->exe->getCId());
        $this->session = api_get_session_entity($this->exe->getSessionId());

        try {
            $statement = $this->createStatement(
                $this->exe->getExeDate()
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
            'terminated',
            XApiPlugin::VERB_TERMINATED
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getActivity()
    {
        $title = strip_tags($this->quiz->getTitle());
        $title = trim($title);
        $description = strip_tags($this->quiz->getDescription());
        $description = trim($description);

        $languageIso = api_get_language_isocode($this->course->getCourseLanguage());

        $titleMap = LanguageMap::create([$languageIso => $title]);
        $descriptionMap = $description ? LanguageMap::create([$languageIso => $description]) : null;

        $id = $this->plugin->generateIri(
            $this->quiz->getId(),
            'quiz'
        );

        return new Activity(
            $id,
            new Definition(
                $titleMap,
                $descriptionMap,
                IRI::fromString(XApiPlugin::IRI_QUIZ)
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getActivityResult()
    {
        $raw = $this->exe->getExeResult();
        $max = $this->exe->getExeWeighting();
        $scaled = $raw / $max;

        $duration = $this->exe->getExeDuration();

        return new ActivityResult(
            new Score($scaled, $raw, 0, $max),
            null,
            true,
            null,
            $duration ? "PT{$duration}S" : null
        );
    }

    /**
     * @return \Xabbuh\XApi\Model\StatementId
     */
    protected function getId()
    {
        return $this->generateId(
            XApiPlugin::DATA_TYPE_EXERCISE,
            $this->exe->getExeId()
        );
    }
}
