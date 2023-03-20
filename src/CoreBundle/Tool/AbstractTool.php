<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Symfony\Component\Serializer\Annotation\Groups;

abstract class AbstractTool
{
    /**
     * @Groups({"ctool:read"})
     */
    protected string $name;

    /**
     * @Groups({"ctool:read"})
     */
    protected string $nameToShow = '';

    /**
     * @Groups({"ctool:read"})
     */
    protected string $icon = '';
    protected string $category = '';
    protected string $link;
    protected string $image;
    protected string $admin;
    protected ?SchemaInterface $settings = null;
    protected array $resourceTypes = [];

    /**
     * @var string
     *
     *  00 disabled tool
     *  01 course tool
     *  10 global tool
     *  11 global or course or both
     */
    protected string $scope;

    abstract public function getCategory(): string;
    abstract public function getLink(): string;

    /*public function __construct(
        string $name,
        string $category,
        string $link,
        ?SchemaInterface $settings = null,
        ?array $resourceTypes = []
    ) {
        $this->name = $name;
        $this->nameToShow = $name;
        $this->category = $category;
        $this->link = $link;
        $this->image = $name.'.png';
        $this->settings = $settings;
        $this->resourceTypes = $resourceTypes;
        $this->icon = 'mdi-crop-square';
    }*/

    public function isCourseTool(): bool
    {
        return false;
    }

    public function isGlobal(): bool
    {
        return true;
    }

    public function getName(): string
    {
        return $this->name;
    }

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

    public function setResourceTypes(?array $resourceTypes): self
    {
        $this->resourceTypes = $resourceTypes;

        return $this;
    }

    public function getNameToShow(): string
    {
        //return $this->getName();
        return ucfirst(str_replace('_', ' ', $this->getName()));
    }

    public function setNameToShow(string $nameToShow): self
    {
        $this->nameToShow = $nameToShow;

        return $this;
    }
}
