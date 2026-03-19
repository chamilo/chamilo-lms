<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

use Chamilo\CoreBundle\Entity\PortfolioComment as PortfolioCommentEntity;

/**
 * Class PortfolioComment.
 */
class PortfolioComment extends BaseActivity
{
    private PortfolioCommentEntity $comment;

    public function __construct(PortfolioCommentEntity $comment)
    {
        $this->comment = $comment;
    }

    public function generate(): array
    {
        $iri = $this->generateIri(
            WEB_CODE_PATH,
            'portfolio/index.php',
            [
                'action' => 'view',
                'id' => $this->comment->getItem()->getId(),
                'comment' => $this->comment->getId(),
            ]
        );

        return $this->buildActivity(
            $iri,
            null,
            null,
            'http://activitystrea.ms/schema/1.0/comment'
        );
    }
}
