<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Tool;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class BaseTool
 * @package Chamilo\CourseBundle\Tool
 */
abstract class BaseTool implements ToolInterface
{
    protected $name;
    protected $category;
    protected $link;
    protected $image;
    protected $admin;

    /**
     * @param $name
     * @param $category
     * @param $link
     * @param $image
     */
    public function __construct($name, $category, $link, $image)
    {
        $this->name = $name;
        $this->category = $category;
        $this->link = $link;
        $this->image = $image;
        $this->admin = 0;
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
     * {@inheritdoc}
     */
    public function setDefaultSettings(OptionsResolverInterface $resolver)
    {

    }


}
