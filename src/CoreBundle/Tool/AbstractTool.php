<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;

/**
 * Class AbstractTool.
 */
abstract class AbstractTool implements ToolInterface
{
    protected string $name;
    protected string $category;
    protected string $link;
    protected string $image;
    protected string $admin;
    /** @var SchemaInterface|array|null */
    protected $settings;
    protected ?array $resourceTypes;

    /**
     * @var string
     *
     *  00 disabled tool
     *  01 course tool
     *  10 global tool
     *  11 global or course or both
     */
    protected $scope;

    /**
     * @param string          $name
     * @param string          $category
     * @param string          $link
     * @param SchemaInterface $settings
     * @param array           $resourceTypes
     */
    public function __construct($name, $category, $link, $settings = null, $resourceTypes = [])
    {
        $this->name = $name;
        $this->category = $category;
        $this->link = $link;
        $this->image = $name.'.png';
        $this->settings = $settings;
        $this->resourceTypes = $resourceTypes;
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

    public function getResourceTypes()
    {
        return $this->resourceTypes;
    }

    public function setResourceTypes($resourceTypes): self
    {
        var_dump($resourceTypes);
        $this->resourceTypes = $resourceTypes;

        return $this;
    }
}
