<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioComment as PortfolioCommentActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioItem as PortfolioItemActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User as UserActor;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Commented as CommentedVerb;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Replied as RepliedVerb;

/**
 * Class PortfolioItemCommented.
 */
class PortfolioItemCommented extends PortfolioComment
{
    use PortfolioAttachmentsTrait;

    public function generate(): array
    {
        $statementId = $this->generateStatementId('portfolio-comment');
        $userActor = new UserActor($this->comment->getAuthor());
        $context = $this->generateContext();
        $attachments = $this->generateAttachmentsForComment($this->comment);

        $statement = [
            'id' => $statementId,
            'actor' => $userActor->generate(),
            'result' => $this->buildResult([], null, null, (string) $this->comment->getContent()),
            'timestamp' => $this->normalizeTimestamp($this->comment->getDate()),
            'context' => $context,
        ];

        if ($this->parentComment) {
            $repliedVerb = new RepliedVerb();
            $parentCommentActivity = new PortfolioCommentActivity($this->parentComment);

            $statement['verb'] = $repliedVerb->generate();
            $statement['object'] = $parentCommentActivity->generate();
        } else {
            $commentedVerb = new CommentedVerb();
            $itemActivity = new PortfolioItemActivity($this->item);

            $statement['verb'] = $commentedVerb->generate();
            $statement['object'] = $itemActivity->generate();
        }

        if (!empty($attachments)) {
            $statement['attachments'] = $attachments;
        }

        return $statement;
    }
}
