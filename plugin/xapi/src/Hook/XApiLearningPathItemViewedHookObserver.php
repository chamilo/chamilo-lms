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
class XApiLearningPathItemViewedHookObserver
    extends XApiActivityHookObserver
    implements HookLearningPathItemViewedObserverInterface
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
     * @inheritDoc
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

            $sharedStmt = $this->sendStatementToLrs($statement);

            $this->saveSharedStatement(
                $sharedStmt->getId(),
                XApiPlugin::DATA_TYPE_LP_ITEM_VIEW,
                $this->lpItemView->getId()
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
            'viewed',
            XApiPlugin::VERB_VIEWED
        );
    }

    /**
     * @inheritDoc
     */
    protected function getActivity()
    {
        $itemTitle = strip_tags($this->lpItem->getTitle());
        $itemTitle = trim($itemTitle);

        $languageIso = api_get_language_isocode($this->course->getCourseLanguage());

        $titleMap = LanguageMap::create([$languageIso => $itemTitle]);

        $activityIdIri = $this->plugin->generateIri(
            $this->lpItem->getId(),
            'lp_item'
        );

        return new Activity(
            IRI::fromString($activityIdIri),
            new Definition(
                $titleMap,
                null,
                IRI::fromString(XApiPlugin::IRI_RESOURCE)
            )
        );
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    protected function getContext()
    {
        $lpIri = $this->plugin->generateIri(
            $this->lpView->getLpId(),
            XApiPlugin::TYPE_LP
        );

        $lpActivity = new Activity(
            IRI::fromString($lpIri)
        );

        $activities = new ContextActivities(
            [$lpActivity]
        );

        return parent::getContext()->withContextActivities($activities);
    }

    /**
     * @inheritDoc
     */
    protected function getId()
    {
        return $this->generateId(
            XApiPlugin::DATA_TYPE_LP_ITEM_VIEW,
            $this->lpItemView->getId()
        );
    }
}
