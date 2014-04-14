<?php

namespace Braincrafted\Bundle\BootstrapBundle\Tests\Session;

use \Mockery as m;

use Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage;

/**
 * FlashMessageTest
 *
 * @group unit
 */
class FlashMessageTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface */
    private $session;

    /** @var \Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface */
    private $flashBag;

    /** @var FlashMessage */
    private $flash;

    public function setUp()
    {
        $this->flashBag = m::mock('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface');
        $this->session = m::mock('Symfony\Component\HttpFoundation\Session\SessionInterface');
        $this->session
            ->shouldReceive('getFlashBag')
            ->withNoArgs()
            ->atLeast()->once()
            ->andReturn($this->flashBag);

        $this->flash = new FlashMessage($this->session);
    }

    /**
     * Tests the alert() method.
     *
     * @covers Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage::__construct()
     * @covers Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage::alert()
     */
    public function testAlert()
    {
        $this->flashBag
            ->shouldReceive('add')
            ->with('alert', 'Foobar Alert')
            ->once();

        $this->flash->alert('Foobar Alert');
    }

    /**
     * Tests the error() method.
     *
     * @covers Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage::__construct()
     * @covers Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage::error()
     */
    public function testError()
    {
        $this->flashBag
            ->shouldReceive('add')
            ->with('error', 'Foobar Error')
            ->once();

        $this->flash->error('Foobar Error');
    }

    /**
     * Tests the info() method.
     *
     * @covers Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage::__construct()
     * @covers Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage::info()
     */
    public function testInfo()
    {
        $this->flashBag
            ->shouldReceive('add')
            ->with('info', 'Foobar Info')
            ->once();

        $this->flash->info('Foobar Info');
    }

    /**
     * Tests the success() method.
     *
     * @covers Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage::__construct()
     * @covers Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage::success()
     */
    public function testSuccess()
    {
        $this->flashBag
            ->shouldReceive('add')
            ->with('success', 'Foobar Success')
            ->once();

        $this->flash->success('Foobar Success');
    }
}
