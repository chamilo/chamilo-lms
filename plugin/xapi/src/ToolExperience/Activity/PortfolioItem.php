<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

use Chamilo\CoreBundle\Entity\Portfolio;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;

/**
 * Class PortfolioItem.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Activity
 */
class PortfolioItem extends BaseActivity
{
    /**
     * @var \Chamilo\CoreBundle\Entity\Portfolio
     */
    private $item;

    public function __construct(Portfolio $item)
    {
        $this->item = $item;
    }

    public function generate(): Activity
    {
        $langIso = api_get_language_isocode();

        $iri = $this->generateIri(
            WEB_CODE_PATH,
            'portfolio/index.php',
            ['action' => 'view', 'id' => $this->item->getId()]
        );

        return new Activity(
            IRI::fromString($iri),
            new Definition(
                LanguageMap::create([$langIso => $this->item->getTitle()]),
                null,
                IRI::fromString('http://activitystrea.ms/schema/1.0/article')
            )
        );
    }
}
