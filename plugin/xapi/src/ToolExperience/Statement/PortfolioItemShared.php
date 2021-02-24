<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\CoreBundle\Entity\Portfolio as PortfolioEntity;
use Chamilo\CoreBundle\Entity\PortfolioAttachment;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioCategory as PortfolioCategoryActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioItem as PortfolioItemActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User as UserActor;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Shared as SharedVerb;
use Xabbuh\XApi\Model\Statement;

/**
 * Class PortfolioItemShared.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Statement
 */
class PortfolioItemShared extends BaseStatement
{
    /**
     * @var \Chamilo\CoreBundle\Entity\Portfolio
     */
    private $portfolioItem;

    public function __construct(PortfolioEntity $item)
    {
        $this->portfolioItem = $item;
    }

    public function generate(): Statement
    {
        $userActor = new UserActor(
            $this->portfolioItem->getUser()
        );
        $sharedVerb = new SharedVerb();
        $itemActivity = new PortfolioItemActivity($this->portfolioItem);

        $context = $this->generateContext();

        if ($this->portfolioItem->getCategory()) {
            $categoryActivity = new PortfolioCategoryActivity($this->portfolioItem->getCategory());

            $contextActivities = $context
                ->getContextActivities()
                ->withAddedCategoryActivity(
                    $categoryActivity->generate()
                );

            $context = $context->withContextActivities($contextActivities);
        }

        $em = \Database::getManager();
        $itemAttachments = $em->getRepository(PortfolioAttachment::class)->findFromItem($this->portfolioItem);

        $attachments = $this->generateAttachments(
            $itemAttachments,
            $this->portfolioItem->getUser()
        );

        return new Statement(
            null,
            $userActor->generate(),
            $sharedVerb->generate(),
            $itemActivity->generate(),
            null,
            null,
            $this->portfolioItem->getCreationDate(),
            null,
            $context,
            $attachments
        );
    }
}
