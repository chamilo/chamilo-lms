<?php

namespace JMS\DiExtraBundle\Tests\Functional\Bundle\TestBundle\Inheritance;

use Symfony\Component\Templating\EngineInterface;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("abstract_class")
 *
 * @author johannes
 */
abstract class AbstractClass
{
    private $templating;

    /**
     * @DI\InjectParams
     *
     * @param EngineInterface $templating
     */
    public function setTemplating(EngineInterface $templating)
    {
        $this->templating = $templating;
    }

    public function getTemplating()
    {
        return $this->templating;
    }
}
