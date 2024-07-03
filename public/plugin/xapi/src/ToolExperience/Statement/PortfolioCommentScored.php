<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioComment as PortfolioCommentActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Scored;
use Xabbuh\XApi\Model\Result;
use Xabbuh\XApi\Model\Score;
use Xabbuh\XApi\Model\Statement;

class PortfolioCommentScored extends PortfolioComment
{
    use PortfolioAttachmentsTrait;

    public function generate(): Statement
    {
        $user = api_get_user_entity(api_get_user_id());

        $maxScore = (float) api_get_course_setting('portfolio_max_score');
        $rawScore = $this->comment->getScore();
        $scaled = $maxScore ? ($rawScore / $maxScore) : 0;

        $actor = new User($user);
        $verb = new Scored();
        $object = new PortfolioCommentActivity($this->comment);
        $context = $this->generateContext();
        $attachments = $this->generateAttachmentsForComment($this->comment);
        $score = new Score($scaled, $rawScore, 0, $maxScore);
        $result = new Result($score);

        return new Statement(
            $this->generateStatementId('portfolio-comment'),
            $actor->generate(),
            $verb->generate(),
            $object->generate(),
            $result,
            null,
            api_get_utc_datetime(null, false, true),
            null,
            $context,
            $attachments
        );
    }
}
