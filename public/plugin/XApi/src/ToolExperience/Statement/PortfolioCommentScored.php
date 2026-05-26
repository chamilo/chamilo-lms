<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioComment as PortfolioCommentActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Scored;

/**
 * Class PortfolioCommentScored.
 */
class PortfolioCommentScored extends PortfolioComment
{
    use PortfolioAttachmentsTrait;

    public function generate(): array
    {
        $user = api_get_user_entity(api_get_user_id());

        $maxScore = (float) api_get_course_setting('portfolio_max_score');
        $rawScore = (float) $this->comment->getScore();
        $scaled = $maxScore > 0 ? ($rawScore / $maxScore) : 0.0;

        $actor = new User($user);
        $verb = new Scored();
        $object = new PortfolioCommentActivity($this->comment);
        $context = $this->generateContext();
        $attachments = $this->generateAttachmentsForComment($this->comment);

        $statement = [
            'id' => $this->generateStatementId('portfolio-comment'),
            'actor' => $actor->generate(),
            'verb' => $verb->generate(),
            'object' => $object->generate(),
            'result' => $this->buildResult(
                $this->buildScore($scaled, $rawScore, 0.0, $maxScore > 0 ? $maxScore : null)
            ),
            'timestamp' => api_get_utc_datetime(null, false, true)->format(DATE_ATOM),
            'context' => $context,
        ];

        if (!empty($attachments)) {
            $statement['attachments'] = $attachments;
        }

        return $statement;
    }
}
