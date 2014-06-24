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

use Doctrine\ODM\PHPCR\DocumentManager;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\LocaleListener;
use Doctrine\ODM\PHPCR\Event\MoveEventArgs;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\Route;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\PrefixCandidates;
use Symfony\Cmf\Component\Routing\Test\CmfUnitTestCase;

class LocaleListenerTest extends CmfUnitTestCase
{
    /** @var LocaleListener */
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

        $this->candidatesMock->expects($this->any())
            ->method('getPrefixes')
            ->will($this->returnValue(array('/cms/routes', '/cms/simple')))
        ;
        $this->listener = new LocaleListener($this->candidatesMock, array('en', 'de'));
        $this->routeMock = $this->buildMock('Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\Route');
        $this->dmMock = $this->buildMock('Doctrine\ODM\PHPCR\DocumentManager');
    }

    public function testNoRoute()
    {
        $args = new LifecycleEventArgs($this, $this->dmMock);
        $this->listener->postLoad($args);
        $this->listener->postPersist($args);
    }

    public function testNoPrefixMatch()
    {
        $this->routeMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('/cms/outside/de/my/route'))
        ;

        $this->routeMock->expects($this->never())
            ->method('setDefault')
        ;
        $this->routeMock->expects($this->never())
            ->method('setRequirement')
        ;

        $args = new LifecycleEventArgs($this->routeMock, $this->dmMock);

        $this->listener->postLoad($args);
    }

    private function prepareMatch()
    {
        $this->routeMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('/cms/routes/de/my/route'))
        ;

        $this->routeMock->expects($this->once())
            ->method('setDefault')
            ->with('_locale', 'de')
        ;
        $this->routeMock->expects($this->once())
            ->method('setRequirement')
            ->with('_locale', 'de')
        ;

        return new LifecycleEventArgs($this->routeMock, $this->dmMock);
    }

    public function testLoad()
    {
        $this->listener->postLoad($this->prepareMatch());
    }

    public function testPersist()
    {
        $this->listener->postPersist($this->prepareMatch());
    }

    public function testSecond()
    {
        $this->routeMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('/cms/simple/de'))
        ;

        $this->routeMock->expects($this->once())
            ->method('setDefault')
            ->with('_locale', 'de')
        ;
        $this->routeMock->expects($this->once())
            ->method('setRequirement')
            ->with('_locale', 'de')
        ;

        $args = new LifecycleEventArgs($this->routeMock, $this->dmMock);
        $this->listener->postLoad($args);
    }

    public function testMoveNoRoute()
    {
        $moveArgs = new MoveEventArgs(
            $this,
            $this->dmMock,
            '/cms/routes/de/my/route',
            '/cms/routes/en/my/route'
        );

        $this->listener->postMove($moveArgs);
    }

    public function testMoved()
    {
        $moveArgs = new MoveEventArgs(
            $this->routeMock,
            $this->dmMock,
            '/cms/routes/de/my/route',
            '/cms/routes/en/my/route'
        );

        $this->routeMock->expects($this->once())
            ->method('setDefault')
            ->with('_locale', 'en')
        ;
        $this->routeMock->expects($this->once())
            ->method('setRequirement')
            ->with('_locale', 'en')
        ;

        $this->listener->postMove($moveArgs);
    }

    public function testSetLocales()
    {
        $this->listener->setLocales(array('xx'));
        $this->assertAttributeEquals(array('xx'), 'locales', $this->listener);
    }

    public function testHaslocale()
    {
        $args = new LifecycleEventArgs($this->routeMock, $this->dmMock);

        $this->routeMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('/cms/routes/de/my/route'))
        ;

        $this->routeMock->expects($this->once())
            ->method('getDefault')
            ->with('_locale')
            ->will($this->returnValue('some'))
        ;

        $this->routeMock->expects($this->once())
            ->method('getRequirement')
            ->with('_locale')
            ->will($this->returnValue('some'))
        ;

        $this->routeMock->expects($this->never())
            ->method('setDefault')
        ;
        $this->routeMock->expects($this->never())
            ->method('setRequirement')
        ;

        $this->listener->postLoad($args);
    }

    /**
     * URL without locale, addLocalePattern not set.
     */
    public function testNolocaleUrl()
    {
        $args = new LifecycleEventArgs($this->routeMock, $this->dmMock);

        $this->routeMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('/cms/routes/my/route'))
        ;

        $this->routeMock->expects($this->never())
            ->method('setDefault')
        ;
        $this->routeMock->expects($this->never())
            ->method('setRequirement')
        ;

        $this->listener->postLoad($args);
    }

    /**
     * URL without locale, addLocalePattern set.
     */
    public function testLocalePattern()
    {
        $this->routeMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('/cms/simple/something'))
        ;

        $this->routeMock->expects($this->once())
            ->method('setOption')
            ->with('add_locale_pattern', true)
        ;

        $this->listener->setAddLocalePattern(true);
        $args = new LifecycleEventArgs($this->routeMock, $this->dmMock);
        $this->listener->postLoad($args);
    }

    /**
     * URL without locale, set available translations
     */
    public function testAvailableTranslations()
    {
        $this->routeMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('/cms/simple/something'))
        ;

        $this->dmMock->expects($this->once())
            ->method('isDocumentTranslatable')
            ->with($this->routeMock)
            ->will($this->returnValue(true))
        ;
        $this->dmMock->expects($this->once())
            ->method('getLocalesFor')
            ->with($this->routeMock, true)
            ->will($this->returnValue(array('en', 'de', 'fr')))
        ;
        $this->routeMock->expects($this->once())
            ->method('setRequirement')
            ->with('_locale', 'en|de|fr')
        ;

        $this->listener->setUpdateAvailableTranslations(true);
        $args = new LifecycleEventArgs($this->routeMock, $this->dmMock);
        $this->listener->postLoad($args);
    }
}
