<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CoreBundle\Entity\Portfolio as PortfolioEntity;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Chamilo\CoreBundle\Enums\ToolIcon;

class Portfolio extends AbstractTool implements ToolInterface
{
    public function getTitle(): string
    {
        return 'portfolio';
    }

    public function getCategory(): string
    {
        return 'interaction';
    }

    public function getLink(): string
    {
        return '/main/portfolio/index.php';
    }

    public function getIcon(): string
    {
        return 'mdi-'.ToolIcon::PORTFOLIO->value;
    }

    public function getResourceTypes(): ?array
    {
        return [
            'portfolio_items' => PortfolioEntity::class,
            'portfolio_comments' => PortfolioComment::class,
        ];
    }
}
