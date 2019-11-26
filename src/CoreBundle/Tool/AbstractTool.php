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
    protected $manager;
    protected $types;

    /**
     * @param string $name
     * @param string $category
     * @param string $link
     * @param        $courseSettings
     * @param array  $types
     * @param array  $admin
     */
    public function __construct($name, $category, $link, $courseSettings, $types, $admin)
    {
        $this->name = $name;
        $this->category = $category;
        $this->link = $link;
        $this->image = $name.'.png';
        $this->admin = (int) $admin;
        $this->courseSettings = $courseSettings;
        $this->types = $types;
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
     * @return BaseEntityManager;
     */
    public function getManager()
    {
        return $this->manager;
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
     * @param string $type
     */
    public function addType($type)
    {
        $this->types[] = $type;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }
}
