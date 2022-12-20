<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioItem as PortfolioItemActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioAttachmentsTrait;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\PortfolioItem as PortfolioItemStatement;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Highlighted;
use Xabbuh\XApi\Model\Statement;

class PortfolioItemHighlighted extends PortfolioItemStatement
{
    use PortfolioAttachmentsTrait;

    public function generate(): Statement
    {
        $user = api_get_user_entity(api_get_user_id());

        $actor = new User($user);
        $verb = new Highlighted();
        $object = new PortfolioItemActivity($this->item);
        $context = $this->generateContext();
        $attachments = $this->generateAttachmentsForItem($this->item);

        return new Statement(
            $this->generateStatementId('portfolio-item'),
            $actor->generate(),
            $verb->generate(),
            $object->generate(),
            null,
            null,
            api_get_utc_datetime(null, false, true),
            null,
            $context,
            $attachments
        );
    }
}
