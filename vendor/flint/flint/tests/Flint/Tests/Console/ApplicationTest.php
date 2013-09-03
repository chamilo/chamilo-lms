<?php

namespace Flint\Tests\Console;

use Flint\Console\Application;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function testPimpleIsAccesible()
    {
        $pimple = new \Pimple();
        $application = new Application($pimple);

        $this->assertSame($pimple, $application->getPimple());
    }

    public function testApplicationIsPimpleAware()
    {
        $this->assertInstanceOf('Flint\PimpleAwareInterface', new Application(new \Pimple()));
    }
}
