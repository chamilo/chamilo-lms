<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioItem as PortfolioItemActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Shared;

/**
 * Class PortfolioItemShared.
 */
class PortfolioItemShared extends PortfolioItem
{
    use PortfolioAttachmentsTrait;

    public function generate(): array
    {
        $itemAuthor = $this->item->getUser();

        $userActor = new User($itemAuthor);
        $sharedVerb = new Shared();
        $itemActivity = new PortfolioItemActivity($this->item);
        $context = $this->generateContext();
        $attachments = $this->generateAttachmentsForItem($this->item);

        $statement = [
            'id' => $this->generateStatementId('portfolio-item'),
            'actor' => $userActor->generate(),
            'verb' => $sharedVerb->generate(),
            'object' => $itemActivity->generate(),
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
