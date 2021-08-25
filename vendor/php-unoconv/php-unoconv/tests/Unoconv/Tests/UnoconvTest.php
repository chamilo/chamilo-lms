<?php

namespace Unoconv\Tests;

use Alchemy\BinaryDriver\BinaryDriverTestCase;
use Alchemy\BinaryDriver\Configuration;
use Unoconv\Unoconv;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;

class UnoconvTest extends BinaryDriverTestCase
{
    public function testCreate()
    {
        $finder = new ExecutableFinder();
        $unoconv = $finder->find('unoconv');

        if (null === $unoconv) {
            $this->markTestSkipped('Unable to detect unoconv, mandatory for this test');
        }

        $unoconv = Unoconv::create();
        $this->assertInstanceOf('Unoconv\Unoconv', $unoconv);
    }

    public function testCreateWithLogger()
    {
        $finder = new ExecutableFinder();
        $unoconv = $finder->find('unoconv');

        if (null === $unoconv) {
            $this->markTestSkipped('Unable to detect unoconv, mandatory for this test');
        }

        $logger = $this->createLoggerMock();

        $unoconv = Unoconv::create(array(), $logger);
        $this->assertEquals($logger, $unoconv->getProcessRunner()->getLogger());
    }

    public function testCreateWithConfiguration()
    {
        $finder = new ExecutableFinder();
        $unoconv = $finder->find('unoconv');

        if (null === $unoconv) {
            $this->markTestSkipped('Unable to detect unoconv, mandatory for this test');
        }

        $conf = new Configuration(array('unoconv.binaries' => $unoconv));

        $unoconv = Unoconv::create($conf);
        $this->assertEquals($conf, $unoconv->getConfiguration());
    }

    public function testTranscode()
    {
        $dest = 'Hello.pdf';

        $rand = mt_rand();
        $process = $this->createProcessMock(1, true, null, $rand);
        $Unoconv = $this->getUnoconv($process, array(
            '--format=pdf',
            '--stdout',
            __DIR__ . '/../../files/Hello.odt'
        ));

        $Unoconv->transcode(__DIR__ . '/../../files/Hello.odt', 'pdf', $dest);

        $this->assertTrue(file_exists($dest));
        $this->assertEquals($rand, file_get_contents($dest));
        unlink($dest);
    }

    public function testTranscodeWithPageRange()
    {
        $dest = 'Hello.pdf';

        $rand = mt_rand();
        $process = $this->createProcessMock(1, true, null, $rand);
        $Unoconv = $this->getUnoconv($process, array(
            '--format=pdf',
            '--stdout',
            '-e',
            'PageRange=1-14',
            __DIR__ . '/../../files/Hello.odt'
        ));

        $Unoconv->transcode(__DIR__ . '/../../files/Hello.odt', 'pdf', $dest, '1-14');

        $this->assertTrue(file_exists($dest));
        $this->assertEquals($rand, file_get_contents($dest));
        unlink($dest);
    }

    /**
     * @expectedException \Unoconv\Exception\RuntimeException
     */
    public function testTranscodeInvalidDest()
    {
        $dest = '/tmp/' . mt_rand(10000, 99999) . '/Hello.pdf';

        $process = $this->createProcessMock(1, true);
        $Unoconv = $this->getUnoconv($process, array(
            '--format=pdf',
            '--stdout',
            __DIR__ . '/../../files/Hello.odt'
        ));

        $Unoconv->transcode(__DIR__ . '/../../files/Hello.odt', 'pdf', $dest);
    }

    /**
     * @expectedException \Unoconv\Exception\RuntimeException
     */
    public function testTranscodeWithoutFile()
    {
        $process = $this->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();

        $process->expects($this->once())
            ->method('run')
            ->will($this->throwException(new ProcessRuntimeException('Process failed')));

        $unoconv = $this->getUnoconv($process, array(
            '--format=pdf',
            '--stdout',
            __DIR__ . '/../../files/Hello.odt'
        ));

        $unoconv->transcode(__DIR__ . '/../../files/Hello.odt', 'pdf', 'Hello.pdf');
    }

    /**
     * @expectedException \Unoconv\Exception\InvalidFileArgumentException
     */
    public function testTranscodeWithInvalidFile()
    {
        $factory = $this->createProcessBuilderFactoryMock();
        $configuration = $this->createConfigurationMock();
        $logger = $this->createLoggerMock();

        $unoconv = new Unoconv($factory, $logger, $configuration);
        $unoconv->transcode('/path/to/nofile', 'pdf', 'hello.pdf');
    }

    private function getUnoconv($process, $args)
    {
        $factory = $this->createProcessBuilderFactoryMock();
        $configuration = $this->createConfigurationMock();
        $logger = $this->createLoggerMock();

        $unoconv = new Unoconv($factory, $logger, $configuration);
        $unoconv
            ->getProcessBuilderFactory()
            ->expects($this->once())
            ->method('create')
            ->with($args)
            ->will($this->returnValue($process));

        return $unoconv;
    }
}
