<?php

namespace ChamiloLMS\CourseBundle\Tool;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


abstract class BaseTool implements ToolInterface
{
    protected $name;
    protected $link;

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

    /**
     * {@inheritdoc}
     */
    public function setDefaultSettings(OptionsResolverInterface $resolver)
    {

    }
}
