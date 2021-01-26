<?php

/* For licensing terms, see /license.txt */

use Xabbuh\XApi\Common\Exception\StatementIdAlreadyExistsException;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Verb;

/**
 * Class XApiPortfolioItemCommentedHookObserver.
 */
class XApiPortfolioItemCommentedHookObserver extends XApiActivityHookObserver
    implements HookPortfolioItemCommentedObserverInterface
{
    /**
     * @var \Chamilo\CoreBundle\Entity\PortfolioComment
     */
    private $comment;

    /**
     * @inheritDoc
     */
    public function hookItemCommented(HookPortfolioItemCommentedEventInterface $hookEvent)
    {
        $this->comment = $hookEvent->getEventData()['comment'];

        $this->user = $this->comment->getAuthor();
        $this->course = $this->comment->getItem()->getCourse();
        $this->session = $this->comment->getItem()->getSession();

        try {
            $statement = $this->createStatement(
                $this->comment->getDate()
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
            XApiPlugin::DATA_TYPE_PORTFOLIO_COMMENT,
            $this->comment->getId()
        );
    }

    /**
     * @inheritDoc
     */
    protected function getActor()
    {
        return $this->generateActor(
            $this->comment->getAuthor()
        );
    }

    /**
     * @inheritDoc
     */
    protected function getVerb()
    {
        $languageMap = XApiPlugin::create()->getLangMap('commented');

        return new Verb(
            IRI::fromString(XApiPlugin::VERB_COMMENTED),
            LanguageMap::create($languageMap)
        );
    }

    /**
     * @inheritDoc
     */
    protected function getActivity()
    {
        if ($this->comment->getParent()) {
            $parent = $this->comment->getParent();

            $id = $this->plugin->generateIri($parent->getId(), 'portfolio-comment');
            $titleMap = $this->plugin->getLangMap('AReplyOnAPortfolioComment');
        } else {
            $item = $this->comment->getItem();

            $id = $this->plugin->generateIri($item->getId(), 'portfolio-item');
            $languageIso = api_get_language_isocode($this->course->getCourseLanguage());
            $titleMap = [$languageIso => $item->getTitle()];
        }

        return new Activity(
            $id,
            new Definition(
                LanguageMap::create($titleMap)
            )
        );
    }

    /**
     * @inheritDoc
     */
    protected function getActivityResult()
    {
        return new \Xabbuh\XApi\Model\Result(
            null,
            null,
            null,
            $this->comment->getContent()
        );
    }
}
