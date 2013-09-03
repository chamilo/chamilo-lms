<?php

namespace Flint\Tests\Console;

use Flint\Console\PimpleHelper;

class PimpleHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testPimpleIsAccesible()
    {
        $pimple = new \Pimple();
        $application = new PimpleHelper($pimple);

        $this->assertSame($pimple, $application->getPimple());
    }

    public function testPimpleHelperIsPimpleAware()
    {
        $this->assertInstanceOf('Flint\PimpleAwareInterface', new PimpleHelper(new \Pimple()));
    }
}
