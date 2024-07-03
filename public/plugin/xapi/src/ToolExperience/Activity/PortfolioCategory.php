<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

use Chamilo\CoreBundle\Entity\PortfolioCategory as PortfolioCategoryEntity;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;

/**
 * Class PortfolioCategory.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Activity
 */
class PortfolioCategory extends BaseActivity
{
    /**
     * @var \Chamilo\CoreBundle\Entity\PortfolioCategory
     */
    private $category;

    public function __construct(PortfolioCategoryEntity $category)
    {
        $this->category = $category;
    }

    public function generate(): Activity
    {
        $iri = $this->generateIri(
            WEB_PATH,
            'xapi/portfolio/',
            [
                'user' => $this->category->getUser()->getId(),
                'category' => $this->category->getId(),
            ]
        );

        $langIso = api_get_language_isocode();

        $categoryDescription = $this->category->getDescription();

        $definitionDescription = $categoryDescription
            ? LanguageMap::create([$langIso => $categoryDescription])
            : null;

        return new Activity(
            IRI::fromString($iri),
            new Definition(
                LanguageMap::create([$langIso => $this->category->getTitle()]),
                $definitionDescription,
                IRI::fromString('http://id.tincanapi.com/activitytype/category')
            )
        );
    }
}
