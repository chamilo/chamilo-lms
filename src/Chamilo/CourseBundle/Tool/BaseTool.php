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
    protected $link;
    protected $image;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    public function getTarget()
    {
        return '_self';
    }

    public function getImage()
    {
        return $this->image;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultSettings(OptionsResolverInterface $resolver)
    {

    }





}
