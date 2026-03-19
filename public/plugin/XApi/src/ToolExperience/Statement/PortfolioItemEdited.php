<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioItem as PortfolioItemActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Edited;

/**
 * Class PortfolioItemEdited.
 */
class PortfolioItemEdited extends PortfolioItem
{
    use PortfolioAttachmentsTrait;

    public function generate(): array
    {
        $user = api_get_user_entity(api_get_user_id());

        $actor = new User($user);
        $verb = new Edited();
        $object = new PortfolioItemActivity($this->item);
        $context = $this->generateContext();
        $attachments = $this->generateAttachmentsForItem($this->item);

        $statement = [
            'id' => $this->generateStatementId('portfolio-item'),
            'actor' => $actor->generate(),
            'verb' => $verb->generate(),
            'object' => $object->generate(),
            'timestamp' => api_get_utc_datetime(null, false, true)->format(DATE_ATOM),
            'context' => $context,
        ];

        if (!empty($attachments)) {
            $statement['attachments'] = $attachments;
        }

        return $statement;
    }
}
