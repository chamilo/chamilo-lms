<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Symfony\Component\Serializer\Annotation\Groups;

abstract class AbstractTool implements ToolInterface
{
    /**
     * @Groups({"ctool:read"})
     */
    protected string $name;

    /**
     * @Groups({"ctool:read"})
     */
    protected string $icon;
    protected string $category;
    protected string $link;
    protected string $image;
    protected string $admin;
    protected ?SchemaInterface $settings = null;
    protected ?array $resourceTypes;

    /**
     * @var string
     *
     *  00 disabled tool
     *  01 course tool
     *  10 global tool
     *  11 global or course or both
     */
    protected string $scope;

    public function __construct(
        string $name,
        string $category,
        string $link,
        ?SchemaInterface $settings = null,
        ?array $resourceTypes = []
    ) {
        $this->name = $name;
        $this->category = $category;
        $this->link = $link;
        $this->image = $name.'.png';
        $this->settings = $settings;
        $this->resourceTypes = $resourceTypes;
        $this->icon = 'mdi-crop-square';
    }

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

    public function getLink(): string
    {
        return $this->link ?: '';
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getTarget(): string
    {
        return '_self';
    }

    public function getImage(): string
    {
        return $this->image;
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

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }
}
