<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioItem as PortfolioItemActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Viewed;

/**
 * Class PortfolioItemViewed.
 */
class PortfolioItemViewed extends PortfolioItem
{
    use PortfolioAttachmentsTrait;

    public function generate(): array
    {
        $user = api_get_user_entity(api_get_user_id());

        $actor = new User($user);
        $verb = new Viewed();
        $object = new PortfolioItemActivity($this->item);
        $context = $this->generateContext();
        $attachments = $this->generateAttachmentsForItem($this->item);

        $statement = [
            'id' => $this->generateStatementId('portfolio-item'),
            'actor' => $actor->generate(),
            'verb' => $verb->generate(),
            'object' => $object->generate(),
            'timestamp' => $this->normalizeTimestamp($this->item->getCreationDate()),
            'context' => $context,
        ];

        if (!empty($attachments)) {
            $statement['attachments'] = $attachments;
        }

        return $statement;
    }

    private function normalizeTimestamp($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        $stringValue = trim((string) $value);

        if ('' === $stringValue) {
            return gmdate(DATE_ATOM);
        }

        $timestamp = strtotime($stringValue);

        return false !== $timestamp ? gmdate(DATE_ATOM, $timestamp) : gmdate(DATE_ATOM);
    }
}
