<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

use Chamilo\CoreBundle\Entity\PortfolioCategory as PortfolioCategoryEntity;

/**
 * Class PortfolioCategory.
 */
class PortfolioCategory extends BaseActivity
{
    private PortfolioCategoryEntity $category;

    public function __construct(PortfolioCategoryEntity $category)
    {
        $this->category = $category;
    }

    public function generate(): array
    {
        $iri = $this->generateIri(
            WEB_PATH,
            'xapi/portfolio/',
            [
                'user' => $this->category->getUser()->getId(),
                'category' => $this->category->getId(),
            ]
        );

        return $this->buildActivity(
            $iri,
            (string) $this->category->getTitle(),
            $this->category->getDescription() ? (string) $this->category->getDescription() : null,
            'http://id.tincanapi.com/activitytype/category'
        );
    }
}
