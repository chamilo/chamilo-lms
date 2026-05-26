<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\PortfolioCategory;

/**
 * Class PortfolioItem.
 */
abstract class PortfolioItem extends BaseStatement
{
    protected Portfolio $item;

    public function __construct(Portfolio $item)
    {
        $this->item = $item;
    }

    protected function generateContext(): array
    {
        $context = parent::generateContext();

        $category = $this->item->getCategory();

        if ($category) {
            $categoryActivity = new PortfolioCategory($category);
            $context = $this->mergeGroupingActivity($context, $categoryActivity->generate());
        }

        return $context;
    }
}
