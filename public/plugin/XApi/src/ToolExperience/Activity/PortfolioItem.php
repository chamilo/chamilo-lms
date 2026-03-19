<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

use Chamilo\CoreBundle\Entity\Portfolio;

/**
 * Class PortfolioItem.
 */
class PortfolioItem extends BaseActivity
{
    private Portfolio $item;

    public function __construct(Portfolio $item)
    {
        $this->item = $item;
    }

    public function generate(): array
    {
        $iri = $this->generateIri(
            WEB_CODE_PATH,
            'portfolio/index.php',
            [
                'action' => 'view',
                'id' => $this->item->getId(),
            ]
        );

        return $this->buildActivity(
            $iri,
            (string) $this->item->getTitle(),
            null,
            'http://activitystrea.ms/schema/1.0/article'
        );
    }
}
