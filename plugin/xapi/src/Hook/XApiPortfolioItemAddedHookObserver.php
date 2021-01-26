<?php

/* For licensing terms, see /license.txt */

use Xabbuh\XApi\Common\Exception\StatementIdAlreadyExistsException;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Verb;

/**
 * Class XApiPortfolioItemAddedHookObserver.
 */
class XApiPortfolioItemAddedHookObserver extends XApiActivityHookObserver
    implements HookPortfolioItemAddedObserverInterface
{
    /**
     * @var \Chamilo\CoreBundle\Entity\Portfolio
     */
    private $item;

    /**
     * @inheritDoc
     */
    public function hookItemAdded(HookPortfolioItemAddedEventInterface $hookEvent)
    {
        $this->item = $hookEvent->getEventData()['portfolio'];

        $this->user = $this->item->getUser();
        $this->course = $this->item->getCourse();
        $this->session = $this->item->getSession();

        try {
            $statement = $this->createStatement(
                $this->item->getCreationDate()
            );
        } catch (StatementIdAlreadyExistsException $e) {
            return;
        }

        $this->saveSharedStatement($statement);
    }

    /**
     * @inheritDoc
     */
    protected function getId()
    {
        return $this->generateId(
            XApiPlugin::DATA_TYPE_PORTFOLIO_ITEM,
            $this->item->getId()
        );
    }

    /**
     * @inheritDoc
     */
    protected function getActor()
    {
        return $this->generateActor(
            $this->item->getUser()
        );
    }

    /**
     * @inheritDoc
     */
    protected function getVerb()
    {
        $languageMap = XApiPlugin::create()->getLangMap('shared');

        return new Verb(
            IRI::fromString(XApiPlugin::VERB_SHARED),
            LanguageMap::create($languageMap)
        );
    }

    /**
     * @inheritDoc
     */
    protected function getActivity()
    {
        $languageIso = api_get_language_isocode($this->course->getCourseLanguage());

        $id = $this->plugin->generateIri($this->item->getId(), 'portfolio-item');

        return new Activity(
            $id,
            new Definition(
                LanguageMap::create([$languageIso => $this->item->getTitle()])
            )
        );
    }

    /**
     * @inheritDoc
     */
    protected function getActivityResult()
    {
        return null;
    }
}
