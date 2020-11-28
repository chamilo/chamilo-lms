<?php

/* For licensing terms, see /license.txt */

use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\ContextActivities;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Result as ActivityResult;

/**
 * Class XApiLearningPathItemViewedHookObserver.
 */
class XApiLearningPathItemViewedHookObserver extends XApiActivityHookObserver implements HookLearningPathItemViewedObserverInterface
{
    use XApiStatementTrait;

    /**
     * @var \Chamilo\CourseBundle\Entity\CLpItemView
     */
    private $lpItemView;
    /**
     * @var \Chamilo\CourseBundle\Entity\CLpItem
     */
    private $lpItem;
    /**
     * @var \Chamilo\CourseBundle\Entity\CLpView;
     */
    private $lpView;

    /**
     * {@inheritdoc}
     */
    public function hookLearningPathItemViewed(HookLearningPathItemViewedEventInterface $event)
    {
        $data = $event->getEventData();
        $em = Database::getManager();

        $this->lpItemView = $em->find('ChamiloCourseBundle:CLpItemView', $data['item_view_id']);
        $this->lpItem = $em->find('ChamiloCourseBundle:CLpItem', $this->lpItemView->getLpItemId());

        if ('quiz' == $this->lpItem->getItemType()) {
            return null;
        }

        $this->lpView = $em->find('ChamiloCourseBundle:CLpView', $this->lpItemView->getLpViewId());
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
            'viewed',
            XApiPlugin::VERB_VIEWED
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getActivity()
    {
        $itemTitle = strip_tags($this->lpItem->getTitle());
        $itemTitle = trim($itemTitle);

        $languageIso = api_get_language_isocode($this->course->getCourseLanguage());

        $titleMap = LanguageMap::create([$languageIso => $itemTitle]);

        $id = $this->plugin->generateIri(
            $this->lpItem->getId(),
            'lp_item'
        );

        return new Activity(
            $id,
            new Definition(
                $titleMap,
                null,
                IRI::fromString(XApiPlugin::IRI_RESOURCE)
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getActivityResult()
    {
        if ('quiz' == $this->lpItem->getItemType()) {
            return null;
        }

        $completion = $this->lpItemView->getStatus() === 'completed';

        return new ActivityResult(
            null,
            null,
            $completion,
            null,
            "PT{$this->lpItemView->getTotalTime()}S"
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getContext()
    {
        $id = $this->plugin->generateIri(
            $this->lpView->getLpId(),
            XApiPlugin::TYPE_LP
        );

        $lpActivity = new Activity($id);

        $activities = new ContextActivities(
            [$lpActivity]
        );

        return parent::getContext()->withContextActivities($activities);
    }

    /**
     * {@inheritdoc}
     */
    protected function getId()
    {
        return $this->generateId(
            XApiPlugin::DATA_TYPE_LP_ITEM_VIEW,
            $this->lpItemView->getId()
        );
    }
}
