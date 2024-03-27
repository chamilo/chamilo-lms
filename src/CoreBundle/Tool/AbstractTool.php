<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Symfony\Component\Serializer\Annotation\Groups;

abstract class AbstractTool
{
    #[Groups(['ctool:read'])]
    protected string $title;

    #[Groups(['ctool:read'])]
    protected string $titleToShow;

    #[Groups(['ctool:read'])]
    protected string $icon = '';

    #[Groups(['ctool:read'])]
    protected string $category = '';

    protected string $image;

    protected array $resourceTypes = [];

    /**
     * Tool scope.
     *
     * Values can be the following.
     *
     * - 00 disabled tool
     * - 01 course tool
     * - 10 global tool
     * - 11 global or course or both
     */
    protected string $scope;

    public function isCourseTool(): bool
    {
        return false;
    }

    abstract public function getTitle(): string;

    public function getTarget(): string
    {
        return '_self';
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function getEntityByResourceType(string $type): ?string
    {
        return $this->getResourceTypes()[$type] ?? null;
    }

    public function getTypeNameByEntity(string $entityClass): ?string
    {
        if (empty($this->getResourceTypes())) {
            return null;
        }

        $list = array_flip($this->getResourceTypes());

        if (isset($list[$entityClass])) {
            return $list[$entityClass];
        }

        return null;
    }

    public function getResourceTypes(): ?array
    {
        return $this->resourceTypes;
    }

    public function getTitleToShow(): string
    {
        // return $this->getTitle();
        return ucfirst(str_replace('_', ' ', $this->getTitle()));
    }
}
