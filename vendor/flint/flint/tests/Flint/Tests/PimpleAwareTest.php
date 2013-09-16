<?php

namespace Flint\Tests;

class PimpleAwareTest extends \PHPUnit_Framework_TestCase
{
    public function testImplementesInterface()
    {
        $aware = $this->getMockForAbstractClass('Flint\PimpleAware');

        $this->assertInstanceOf('Flint\PimpleAwareInterface', $aware);
    }

    public function testAppPropertyIsSet()
    {
        $refl = new \ReflectionProperty('Flint\PimpleAware', 'pimple');
        $refl->setAccessible(true);

        $mock = $this->getMockForAbstractClass('Flint\PimpleAware');

        $this->assertInternalType('null', $refl->getValue($mock));

        $pimple = new \Pimple();
        $mock->setPimple($pimple);

        $this->assertSame($pimple, $refl->getValue($mock));
    }
}
