<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioItem;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioItem as PortfolioItemStatement;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Viewed;
use Xabbuh\XApi\Model\Statement;

class PortfolioItemViewed extends PortfolioItemStatement
{
    use PortfolioAttachmentsTrait;

    public function generate(): Statement
    {
        $user = api_get_user_entity(api_get_user_id());

        $actor = new User($user);
        $verb = new Viewed();
        $object = new PortfolioItem($this->item);
        $context = $this->generateContext();
        $attachments = $this->generateAttachmentsForItem($this->item);

        return new Statement(
            $this->generateStatementId('portfolio-item'),
            $actor->generate(),
            $verb->generate(),
            $object->generate(),
            null,
            null,
            $this->item->getCreationDate(),
            null,
            $context,
            $attachments
        );
    }
}
