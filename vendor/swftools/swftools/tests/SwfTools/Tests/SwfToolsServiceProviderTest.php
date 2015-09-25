<?php

namespace SwfTools\Tests;

use Silex\Application;
use Symfony\Component\Process\ExecutableFinder;
use SwfTools\SwfToolsServiceProvider;

class SwfToolsServiceProviderTest extends TestCase
{
    public function testInitialize()
    {
        $app = new Application();
        $app->register(new SwfToolsServiceProvider());

        $this->assertInstanceOf('\\SwfTools\\Processor\\FlashFile', $app['swftools.flash-file']);
        $this->assertInstanceOf('\\SwfTools\\Processor\\PdfFile', $app['swftools.pdf-file']);
    }

    public function testInitializeFailOnSwfRender()
    {
        $finder = new ExecutableFinder();
        $php = $finder->find('php');

        if (null === $php) {
            $this->markTestSkipped('Unable to find PHP, required for this test');
        }

        $app = new Application();
        $app->register(new SwfToolsServiceProvider(), array(
            'swftools.configuration' => array(
                'pdf2swf.binaries'    => $php,
                'swfrender.binaries'  => $php,
                'swfextract.binaries' => $php,
                'timeout'             => 42,
            )
        ));

        $this->assertEquals($php, $app['swftools.driver-container']['pdf2swf']->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $app['swftools.driver-container']['swfrender']->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $app['swftools.driver-container']['swfextract']->getProcessBuilderFactory()->getBinary());
        $this->assertEquals(42, $app['swftools.driver-container']['pdf2swf']->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $app['swftools.driver-container']['swfrender']->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $app['swftools.driver-container']['swfextract']->getProcessBuilderFactory()->getTimeout());
    }
}
