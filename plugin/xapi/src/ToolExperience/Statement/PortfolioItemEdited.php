<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioItem as PortfolioItemActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Edited;
use Xabbuh\XApi\Model\Statement;

class PortfolioItemEdited extends PortfolioItem
{
    use PortfolioAttachmentsTrait;

    public function generate(): Statement
    {
        $user = api_get_user_entity(api_get_user_id());

        $actor = new User($user);
        $verb = new Edited();
        $object = new PortfolioItemActivity($this->item);
        $context = $this->generateContext();
        $attachements = $this->generateAttachmentsForItem($this->item);

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
            $attachements
        );
    }
}
