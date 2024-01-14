<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Tool\AbstractTool;
use Symfony\Component\Serializer\Annotation\Groups;

class CourseTool extends AbstractResource
{
    #[Groups(['ctool:read'])]
    public ?int $iid = null;

    #[Groups(['ctool:read'])]
    public string $name;

    #[Groups(['ctool:read'])]
    public ?bool $visibility = null;

    #[Groups(['ctool:read'])]
    public AbstractTool $tool;

    #[Groups(['ctool:read'])]
    public ?ResourceNode $resourceNode = null;

    #[Groups(['ctool:read'])]
    public string $url;

    #[Groups(['ctool:read'])]
    public string $category = '';

    #[Groups(['ctool:read'])]
    public function getNameToTranslate(): string
    {
        return ucfirst(str_replace('_', ' ', $this->name));
    }
}
