<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Sonata\CoreBundle\Model\BaseEntityManager;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;

/**
 * Class AbstractTool.
 */
abstract class AbstractTool implements ToolInterface
{
    protected $name;
    protected $category;
    protected $link;
    protected $image;
    protected $admin;
    protected $courseSettings;
    protected $resourceTypes;

    /**
     * @param string $name
     * @param string $category
     * @param string $link
     * @param        $courseSettings
     * @param array  $resourceTypes
     * @param array  $admin
     */
    public function __construct($name, $category, $link, $courseSettings, $resourceTypes, $admin)
    {
        $this->name = $name;
        $this->category = $category;
        $this->link = $link;
        $this->image = $name.'.png';
        $this->admin = (int) $admin;
        $this->courseSettings = $courseSettings;
        $this->resourceTypes = $resourceTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return $this->link ? $this->link : '';
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * {@inheritdoc}
     */
    public function getTarget()
    {
        return '_self';
    }

    /**
     * {@inheritdoc}
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param int $admin
     */
    public function setAdmin($admin)
    {
        $this->admin = $admin;
    }

    public function getAdmin(): int
    {
        return (int) $this->admin;
    }

    /**
     * @param $settings
     *
     * @return int
     */
    public function setCourseSettings($settings)
    {
        $this->courseSettings = $settings;
    }

    /**
     * @return SchemaInterface
     */
    public function getCourseSettings()
    {
        return $this->courseSettings;
    }

    /**
     * @return array
     */
    public function getResourceTypes()
    {
        return $this->resourceTypes;
    }

    /**
     * @param array $resourceTypes
     *
     * @return AbstractTool
     */
    public function setResourceTypes(array $resourceTypes): AbstractTool
    {
        $this->resourceTypes = $resourceTypes;

        return $this;
    }
}
