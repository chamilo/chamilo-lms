<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CoreBundle\Entity\Illustration;

class Asset extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'asset';
    }

    public function getCategory(): string
    {
        return 'admin';
    }

    public function getLink(): string
    {
        return '/';
    }

    public function getIcon(): string
    {
        return 'admin';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'illustrations' => Illustration::class,
        ];
    }
}
