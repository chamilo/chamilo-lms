<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Tests\Functional\Doctrine\Phpcr;

use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\Route;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\RedirectRoute;
use Symfony\Cmf\Bundle\RoutingBundle\Tests\Functional\BaseTestCase;

class RedirectRouteTest extends BaseTestCase
{
    const ROUTE_ROOT = '/test/redirectroute';

    public function setUp()
    {
        parent::setUp();
        $this->db('PHPCR')->createTestNode();
        $this->createRoute(self::ROUTE_ROOT);
    }

    public function testRedirectDoctrine()
    {
        $content = $this->createContent();
        $root = $this->getDm()->find(null, self::ROUTE_ROOT);

        $route = new Route;
        $route->setContent($content);
        $route->setPosition($root, 'testroute');
        $this->getDm()->persist($route);

        $redirect = new RedirectRoute;
        $redirect->setPosition($root, 'redirect');
        $redirect->setRouteTarget($route);
        $redirect->setDefault('test', 'toast');
        $this->getDm()->persist($redirect);

        $this->getDm()->flush();

        $this->getDm()->clear();

        $route = $this->getDm()->find(null, self::ROUTE_ROOT.'/testroute');
        $redirect = $this->getDm()->find(null, self::ROUTE_ROOT.'/redirect');

        $this->assertInstanceOf('Symfony\\Cmf\\Component\\Routing\\RedirectRouteInterface', $redirect);
        $this->assertSame($redirect, $redirect->getContent());
        $params = $redirect->getParameters();
        $this->assertSame($route, $redirect->getRouteTarget());
        $defaults = $redirect->getDefaults();
        $this->assertEquals(array('test' => 'toast'), $defaults);
    }

    /**
     * @expectedException \LogicException
     */
    public function testSetContent()
    {
        $content = $this->getMock('Symfony\\Cmf\\Component\\Routing\\RouteReferrersReadInterface');
        $redirect = new RedirectRoute;
        $redirect->setContent($content);
    }
}
