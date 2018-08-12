<?php

namespace Unoconv\Tests\Functional;

use Unoconv\Unoconv;
use Symfony\Component\Process\ExecutableFinder;

class UnoconvTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $finder = new ExecutableFinder();
        $unoconv = $finder->find('unoconv');

        if (null === $unoconv) {
            $this->markTestSkipped('Unable to detect unoconv, mandatory for this test');
        }
    }

    public function testOdtToPDFConversion()
    {
        $dest = 'Hello.pdf';

        $unoconv = Unoconv::create();
        $unoconv->transcode(__DIR__ . '/../../files/Hello.odt', 'pdf', $dest);

        $this->assertTrue(file_exists($dest));
        unlink($dest);
    }

    public function testOdtToPDFConversionWithPageRange()
    {
        $dest = 'Hello.pdf';

        $unoconv = Unoconv::create();
        $unoconv->transcode(__DIR__ . '/../../files/Hello.odt', 'pdf', $dest, '1-1');

        $this->assertTrue(file_exists($dest));
        unlink($dest);
    }
}
