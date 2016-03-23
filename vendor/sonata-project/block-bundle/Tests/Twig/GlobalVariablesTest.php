<?php

namespace Sonata\BlockBundle\Twig\Extension;

use Sonata\BlockBundle\Twig\GlobalVariables;

class GlobalVariablesTest extends \PHPUnit_Framework_TestCase
{

    public function testGlobalVariables()
    {
        $variables = new GlobalVariables(array());

        $this->assertEmpty($variables->getTemplates());
    }
}