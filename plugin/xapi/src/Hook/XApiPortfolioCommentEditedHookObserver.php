<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\PortfolioComment;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioCommentEdited;

class XApiPortfolioCommentEditedHookObserver extends XApiActivityHookObserver implements HookPortfolioCommentEditedObserverInterface
{
    public function hookCommentEdited(HookPortfolioCommentEditedEventInterface $hookEvent)
    {
        /** @var PortfolioComment $comment */
        $comment = $hookEvent->getEventData()['comment'];

        $statement = (new PortfolioCommentEdited($comment))->generate();

        $this->saveSharedStatement($statement);
    }
}
