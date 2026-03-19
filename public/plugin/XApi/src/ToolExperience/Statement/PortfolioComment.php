<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\CoreBundle\Entity\Portfolio as PortfolioEntity;
use Chamilo\CoreBundle\Entity\PortfolioComment as PortfolioCommentEntity;

/**
 * Class PortfolioComment.
 */
abstract class PortfolioComment extends BaseStatement
{
    protected PortfolioCommentEntity $comment;
    protected ?PortfolioCommentEntity $parentComment;
    protected PortfolioEntity $item;

    public function __construct(PortfolioCommentEntity $comment)
    {
        $this->comment = $comment;
        $this->item = $this->comment->getItem();
        $this->parentComment = $this->comment->getParent();
    }

    protected function generateContext(): array
    {
        $context = parent::generateContext();

        $category = $this->item->getCategory();
        if ($category) {
            $categoryActivity = new \Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioCategory($category);
            $context = $this->mergeGroupingActivity($context, $categoryActivity->generate());
        }

        $itemActivity = new \Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioItem($this->item);
        $context = $this->mergeGroupingActivity($context, $itemActivity->generate());

        return $context;
    }

    protected function normalizeTimestamp($value): string
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
