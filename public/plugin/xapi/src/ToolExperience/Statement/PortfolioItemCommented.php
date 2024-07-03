<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\CoreBundle\Entity\PortfolioAttachment;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioComment as PortfolioCommentActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioItem as PortfolioItemActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User as UserActor;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Commented as CommentedVerb;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Replied as RepliedVerb;
use Xabbuh\XApi\Model\Result;
use Xabbuh\XApi\Model\Statement;

class PortfolioItemCommented extends PortfolioComment
{
    use PortfolioAttachmentsTrait;

    public function generate(): Statement
    {
        $statementId = $this->generateStatementId('portfolio-comment');

        $userActor = new UserActor($this->comment->getAuthor());
        $statementResult = new Result(null, null, null, $this->comment->getContent());

        $context = $this->generateContext();

        $em = \Database::getManager();
        $commentAttachments = $em->getRepository(PortfolioAttachment::class)->findFromComment($this->comment);

        $attachments = $this->generateAttachments(
            $commentAttachments,
            $this->comment->getAuthor()
        );

        if ($this->parentComment) {
            $repliedVerb = new RepliedVerb();

            $itemActivity = new PortfolioItemActivity($this->item);
            $parentCommentActivity = new PortfolioCommentActivity($this->parentComment);

            $contextActivities = $context
                ->getContextActivities()
                ->withAddedGroupingActivity($itemActivity->generate());

            return new Statement(
                $statementId,
                $userActor->generate(),
                $repliedVerb->generate(),
                $parentCommentActivity->generate(),
                $statementResult,
                null,
                $this->comment->getDate(),
                null,
                $context->withContextActivities($contextActivities),
                $attachments
            );
        } else {
            $itemShared = new PortfolioItemShared($this->item);

            $commentedVerb = new CommentedVerb();

            return $itemShared->generate()
                ->withId($statementId)
                ->withActor($userActor->generate())
                ->withVerb($commentedVerb->generate())
                ->withStored($this->comment->getDate())
                ->withResult($statementResult)
                ->withContext($context)
                ->withAttachments($attachments);
        }
    }
}
