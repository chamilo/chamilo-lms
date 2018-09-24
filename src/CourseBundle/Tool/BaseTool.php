<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Tool;

use Sonata\CoreBundle\Model\BaseEntityManager;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class BaseTool.
 *
 * @package Chamilo\CourseBundle\Tool
 */
abstract class BaseTool implements ToolInterface
{
    protected $name;
    protected $category;
    protected $link;
    protected $image;
    protected $admin;
    protected $courseSettings;
    protected $platformSettings;
    protected $manager;
    protected $types;

    /**
     * @param string $name
     * @param string $category
     * @param string $link
     * @param string $image
     * @param $courseSettings
     */
    public function __construct($name, $category, $link, $image, $courseSettings, $types, $manager = null)
    {
        $this->name = $name;
        $this->category = $category;
        $this->link = $link;
        $this->image = $image;
        $this->admin = 0;
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
        return $this->link;
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

    /**
     * @return int
     */
    public function getAdmin()
    {
        return $this->admin;
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

    /**
     * {@inheritdoc}
     */
    public function setDefaultSettings(OptionsResolverInterface $resolver)
    {
    }
}
