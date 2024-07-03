<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioItem;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioItem as PortfolioItemStatement;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Shared;
use Xabbuh\XApi\Model\Statement;

/**
 * Class PortfolioItemShared.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Statement
 */
class PortfolioItemShared extends PortfolioItemStatement
{
    use PortfolioAttachmentsTrait;

    public function generate(): Statement
    {
        $itemAuthor = $this->item->getUser();

        $userActor = new User($itemAuthor);
        $sharedVerb = new Shared();
        $itemActivity = new PortfolioItem($this->item);

        $context = $this->generateContext();

        $attachments = $this->generateAttachmentsForItem($this->item);

        return new Statement(
            $this->generateStatementId('portfolio-item'),
            $userActor->generate(),
            $sharedVerb->generate(),
            $itemActivity->generate(),
            null,
            null,
            $this->item->getCreationDate(),
            null,
            $context,
            $attachments
        );
    }
}
