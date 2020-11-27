<?php

/* For licensing terms, see /license.txt */

use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Result as ActivityResult;
use Xabbuh\XApi\Model\Score;

/**
 * Class XApiLearningPathEndHookObserver.
 */
class XApiLearningPathEndHookObserver extends XApiActivityHookObserver implements HookLearningPathEndObserverInterface
{
    use XApiStatementTrait;

    /**
     * @var \Chamilo\CourseBundle\Entity\CLpView
     */
    private $lpView;
    /**
     * @var \Chamilo\CourseBundle\Entity\CLp
     */
    private $lp;

    /**
     * {@inheritdoc}
     */
    public function notifyLearningPathEnd(HookLearningPathEndEventInterface $event)
    {
        $data = $event->getEventData();
        $em = Database::getManager();

        $this->lpView = $em->find('ChamiloCourseBundle:CLpView', $data['lp_view_id']);
        $this->lp = $em->find('ChamiloCourseBundle:CLp', $this->lpView->getLpId());
        $this->user = api_get_user_entity($this->lpView->getUserId());
        $this->course = api_get_course_entity($this->lpView->getCId());
        $this->session = api_get_session_entity($this->lpView->getSessionId());

        try {
            $statement = $this->createStatement();
        } catch (Exception $e) {
            return;
        }

        $this->saveSharedStatement($statement);
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityResult()
    {
        $raw = (float) $this->lpView->getProgress();
        $max = 100;
        $scaled = $raw / $max;

        return new ActivityResult(
            new Score($scaled, $raw, 0, $max),
            null,
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getActivity()
    {
        $lpName = strip_tags($this->lp->getName());
        $lpName = trim($lpName);

        $languageIso = api_get_language_isocode($this->course->getCourseLanguage());

        $nameMap = LanguageMap::create([$languageIso => $lpName]);

        $id = $this->plugin->generateIri(
            $this->lp->getId(),
            'lp'
        );

        return new Activity(
            $id,
            new Definition(
                $nameMap,
                null,
                IRI::fromString(XApiPlugin::IRI_LESSON)
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getId()
    {
        return $this->generateId(
            XApiPlugin::DATA_TYPE_LP_VIEW,
            $this->lpView->getId()
        );
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
}
