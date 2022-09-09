<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioCategory;
use Xabbuh\XApi\Model\Context;

abstract class PortfolioItem extends BaseStatement
{
    protected $item;

    public function __construct(Portfolio $item)
    {
        $this->item = $item;
    }

    protected function generateContext(): Context
    {
        $context = parent::generateContext();

        $category = $this->item->getCategory();

        if ($category) {
            $categoryActivity = new PortfolioCategory($category);

            $contextActivities = $context
                ->getContextActivities()
                ->withAddedCategoryActivity(
                    $categoryActivity->generate()
                )
            ;

            $context = $context->withContextActivities($contextActivities);
        }

        return $context;
    }
}
