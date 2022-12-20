<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioComment as PortfolioCommentActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Edited;
use Xabbuh\XApi\Model\Statement;

class PortfolioCommentEdited extends PortfolioComment
{
    use PortfolioAttachmentsTrait;

    public function generate(): Statement
    {
        $user = api_get_user_entity(api_get_user_id());

        $actor = new User($user);
        $verb = new Edited();
        $object = new PortfolioCommentActivity($this->comment);
        $context = $this->generateContext();
        $attachements = $this->generateAttachmentsForComment($this->comment);

        return new Statement(
            $this->generateStatementId('portfolio-comment'),
            $actor->generate(),
            $verb->generate(),
            $object->generate(),
            null,
            null,
            api_get_utc_datetime(null, false, true),
            null,
            $context,
            $attachements
        );
    }
}
