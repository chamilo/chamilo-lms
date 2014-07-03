<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Tests\Unit\Doctrine\Phpcr;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ODM\PHPCR\DocumentManager;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\IdPrefixListener;

use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\Route;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\PrefixCandidates;
use Symfony\Cmf\Component\Routing\Test\CmfUnitTestCase;

class IdPrefixListenerTest extends CmfUnitTestCase
{
    /**
     * @var IdPrefixListener
     */
    protected $listener;

    /**
     * @var PrefixCandidates|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $candidatesMock;

    /**
     * @var DocumentManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dmMock;

    /**
     * @var Route|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $routeMock;

    public function setUp()
    {
        $this->candidatesMock = $this->buildMock('Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\PrefixCandidates');
        $this->candidatesMock
            ->expects($this->any())
            ->method('getPrefixes')
            ->will($this->returnValue(array('/cms/routes', '/cms/simple')))
        ;
        $this->dmMock = $this->buildMock('Doctrine\ODM\PHPCR\DocumentManager');
        $this->routeMock = $this->buildMock('Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\Route');

        $this->listener = new IdPrefixListener($this->candidatesMock);
    }

    public function testNoRoute()
    {
        $args = new LifecycleEventArgs($this, $this->dmMock);

        $this->listener->postLoad($args);
    }

    private function prepareMatch()
    {
        $this->routeMock
            ->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('/cms/routes'))
        ;
        $this->routeMock
            ->expects($this->once())
            ->method('setPrefix')
            ->with('/cms/routes')
        ;

        return new LifecycleEventArgs($this->routeMock, $this->dmMock);
    }

    public function testPostLoad()
    {
        $this->listener->postLoad($this->prepareMatch());
    }

    public function testPostPersist()
    {
        $this->listener->postPersist($this->prepareMatch());
    }

    public function testPostMove()
    {
        $this->listener->postMove($this->prepareMatch());
    }

    public function testSecond()
    {

        $this->routeMock
            ->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue('/cms/simple/test'))
        ;
        $this->routeMock
            ->expects($this->once())
            ->method('setPrefix')
            ->with('/cms/simple')
        ;

        $args = new LifecycleEventArgs($this->routeMock, $this->dmMock);

        $this->listener->postLoad($args);
    }

    public function testOutside()
    {
        $this->routeMock
            ->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue('/outside'))
        ;
        $this->routeMock
            ->expects($this->never())
            ->method('setPrefix')
        ;

        $args = new LifecycleEventArgs($this->routeMock, $this->dmMock);

        $this->listener->postLoad($args);
    }
}
