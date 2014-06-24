<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Tests\Functional\Routing;

use PHPCR\Util\NodeHelper;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Cmf\Component\Routing\ChainRouter;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\Route;
use Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\RedirectRoute;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;

use Symfony\Cmf\Bundle\RoutingBundle\Tests\Functional\BaseTestCase;
use Symfony\Cmf\Bundle\RoutingBundle\Tests\Resources\Document\Content;

/**
 * The goal of these tests is to test the interoperation with DI and everything.
 * We do not aim to cover all edge cases and exceptions - that is was the unit
 * test is here for.
 */
class DynamicRouterTest extends BaseTestCase
{
    /**
     * @var ChainRouter
     */
    protected $router;
    protected $routeNamePrefix;

    const ROUTE_ROOT = '/test/routing';

    public function setUp()
    {
        parent::setUp();

        $this->db('PHPCR')->createTestNode();
        $this->createRoute(self::ROUTE_ROOT);

        $this->router = $this->getContainer()->get('router');

        $root = $this->getDm()->find(null, self::ROUTE_ROOT);

        // do not set a content here, or we need a valid request and so on...
        $route = new Route();
        $route->setPosition($root, 'testroute');
        $route->setVariablePattern('/{slug}/{id}');
        $route->setDefault('id', '0');
        $route->setRequirement('id', '[0-9]+');
        $route->setDefault(RouteObjectInterface::CONTROLLER_NAME, 'testController');

        //TODO options

        $this->getDm()->persist($route);

        $childroute = new Route();
        $childroute->setPosition($route, 'child');
        $childroute->setDefault(RouteObjectInterface::CONTROLLER_NAME, 'testController');
        $this->getDm()->persist($childroute);

        $formatroute = new Route(array('add_format_pattern' => true));
        $formatroute->setPosition($root, 'format');
        $formatroute->setVariablePattern('/{id}');
        $formatroute->setRequirement('_format', 'html|json');
        $formatroute->setDefault(RouteObjectInterface::CONTROLLER_NAME, 'testController');
        $this->getDm()->persist($formatroute);

        $format2jsonroute = new Route(array('add_format_pattern' => true));
        $format2jsonroute->setPosition($root, 'format2.json');
        $format2jsonroute->setDefault('_format', 'json');
        $format2jsonroute->setRequirement('_format', 'json');
        $format2jsonroute->setDefault(RouteObjectInterface::CONTROLLER_NAME, 'testJsonController');
        $this->getDm()->persist($format2jsonroute);

        $format2route = new Route(array('add_format_pattern' => true));
        $format2route->setPosition($root, 'format2');
        $format2route->setDefault(RouteObjectInterface::CONTROLLER_NAME, 'testController');
        $this->getDm()->persist($format2route);

        $this->getDm()->flush();
    }

    public function testMatch()
    {
        $expected = array(
            RouteObjectInterface::CONTROLLER_NAME,
            RouteObjectInterface::ROUTE_NAME,
        );

        $request = Request::create('/testroute/child');
        $matches = $this->router->matchRequest($request);
        ksort($matches);

        $this->assertTrue($request->attributes->has(DynamicRouter::ROUTE_KEY));
        $this->assertEquals($expected, array_keys($matches));
        $this->assertEquals('/test/routing/testroute/child', $matches[RouteObjectInterface::ROUTE_NAME]);
    }

    public function testMatchParameters()
    {
        $expected = array(
            RouteObjectInterface::CONTROLLER_NAME   => 'testController',
            RouteObjectInterface::ROUTE_NAME => '/test/routing/testroute',
            'id'          => '123',
            'slug'        => 'child',
        );

        $request = Request::create('/testroute/child/123');

        $matches = $this->router->matchRequest($request);
        $this->assertTrue($request->attributes->has(DynamicRouter::ROUTE_KEY));
        ksort($matches);

        $this->assertEquals($expected, $matches);
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testNoMatch()
    {
        $this->router->matchRequest(Request::create('/testroute/child/123a'));
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\MethodNotAllowedException
     */
    public function testNotAllowed()
    {
        $root = $this->getDm()->find(null, self::ROUTE_ROOT);

        // do not set a content here, or we need a valid request and so on...
        $route = new Route();
        $route->setPosition($root, 'notallowed');
        $route->setRequirement('_method', 'GET');
        $route->setDefault(RouteObjectInterface::CONTROLLER_NAME, 'testController');
        $this->getDm()->persist($route);
        $this->getDm()->flush();

        $this->router->matchRequest(Request::create('/notallowed', 'POST'));
    }

    public function testMatchDefaultFormat()
    {
        $expected = array(
            '_controller' => 'testController',
            '_format'     => 'html',
            RouteObjectInterface::ROUTE_NAME => '/test/routing/format',
            'id'          => '48',
        );
        $request = Request::create('/format/48');
        $matches = $this->router->matchRequest($request);
        ksort($matches);

        $this->assertTrue($request->attributes->has(DynamicRouter::ROUTE_KEY));
        $this->assertEquals($expected, $matches);
    }

    public function testMatchFormat()
    {
        $expected = array(
            '_controller' => 'testController',
            '_format'     => 'json',
            RouteObjectInterface::ROUTE_NAME => '/test/routing/format',
            'id'          => '48',
        );
        $request = Request::create('/format/48.json');
        $matches = $this->router->matchRequest($request);
        ksort($matches);

        $this->assertTrue($request->attributes->has(DynamicRouter::ROUTE_KEY));
        $this->assertEquals($expected, $matches);

        $expected = array(
            '_controller' => 'testController',
            '_format'     => 'html',
            RouteObjectInterface::ROUTE_NAME => '/test/routing/format2',
        );
        $request = Request::create('/format2.html');
        $matches = $this->router->matchRequest($request);
        ksort($matches);

        $this->assertTrue($request->attributes->has(DynamicRouter::ROUTE_KEY));
        $this->assertEquals($expected, $matches);

        $expected = array(
            '_controller' => 'testJsonController',
            '_format'     => 'json',
            RouteObjectInterface::ROUTE_NAME => '/test/routing/format2.json',
        );
        $request = Request::create('/format2.json');
        $matches = $this->router->matchRequest($request);
        ksort($matches);

        $this->assertTrue($request->attributes->has(DynamicRouter::ROUTE_KEY));
        $this->assertEquals($expected, $matches);
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testNoMatchingFormat()
    {
        $this->router->matchRequest(Request::create('/format/48.xml'));
    }

    public function testMatchLocale()
    {
        $route = new Route();
        $route->setPosition($this->getDm()->find(null, self::ROUTE_ROOT), 'de');
        $route->setDefault(RouteObjectInterface::CONTROLLER_NAME, 'testController');
        $this->getDm()->persist($route);
        $childroute = new Route();
        $childroute->setPosition($route, 'testroute');
        $childroute->setDefault(RouteObjectInterface::CONTROLLER_NAME, 'testController');
        $this->getDm()->persist($childroute);
        $nolocale = new Route();
        $nolocale->setPosition($this->getDm()->find(null, self::ROUTE_ROOT), 'es');
        $nolocale->setDefault(RouteObjectInterface::CONTROLLER_NAME, 'testController');
        $this->getDm()->persist($nolocale);
        $this->getDm()->flush();

        $expected = array(
            '_controller' => 'testController',
            '_locale' => 'de',
            '_route' => self::ROUTE_ROOT . '/de'
        );
        $this->assertEquals(
            $expected,
            $this->router->match('/de')
        );
        $expected = array(
            '_controller' => 'testController',
            '_locale' => 'de',
            '_route' => self::ROUTE_ROOT . '/de/testroute'
        );
        $this->assertEquals(
            $expected,
            $this->router->match('/de/testroute')
        );
        // es is not a configured locale
        $expected = array(
            '_controller' => 'testController',
            '_route' => self::ROUTE_ROOT . '/es'
        );
        $this->assertEquals(
            $expected,
            $this->router->match('/es')
        );
    }

    public function testEnhanceControllerByAlias()
    {
        // put a redirect route
        $root = $this->getDm()->find(null, self::ROUTE_ROOT);

        $route = new RedirectRoute;
        $route->setDefault('type', 'demo_alias');
        $route->setPosition($root, 'controlleralias');
        $this->getDm()->persist($route);
        $this->getDm()->flush();

        $expected = array(
            '_controller' => 'test.controller:aliasAction',
            RouteObjectInterface::ROUTE_NAME => '/test/routing/controlleralias',
            'type'        => 'demo_alias',
        );
        $request = Request::create('/controlleralias');
        $matches = $this->router->matchRequest($request);
        ksort($matches);

        $this->assertTrue($request->attributes->has(DynamicRouter::ROUTE_KEY));
        $this->assertEquals($expected, $matches);
    }

    public function testEnhanceControllerByClass()
    {
        // put a redirect route
        $root = $this->getDm()->find(null, self::ROUTE_ROOT);

        $route = new RedirectRoute;
        $route->setRouteTarget($root);
        $route->setPosition($root, 'redirect');
        $this->getDm()->persist($route);
        $this->getDm()->flush();

        $expected = array(
            '_controller' => 'cmf_routing.redirect_controller:redirectAction',
            RouteObjectInterface::ROUTE_NAME => '/test/routing/redirect',
        );
        $request = Request::create('/redirect');
        $matches = $this->router->matchRequest($request);
        ksort($matches);

        $this->assertTrue($request->attributes->has(DynamicRouter::ROUTE_KEY));
        $this->assertEquals($expected, $matches);
    }

    public function testEnhanceTemplateByClass()
    {
        if ($content = $this->getDm()->find(null, '/test/content/templatebyclass')) {
            $this->getDm()->remove($content);
            $this->getDm()->flush();
        }
        NodeHelper::createPath($this->getDm()->getPhpcrSession(), '/test/content');
        $document = new Content();
        $document->setId('/test/content/templatebyclass');
        $document->setTitle('the title');
        $this->getDm()->persist($document);

        // put a route for this content
        $root = $this->getDm()->find(null, self::ROUTE_ROOT);
        $route = new Route();
        $route->setContent($document);
        $route->setPosition($root, 'templatebyclass');
        $this->getDm()->persist($route);
        $this->getDm()->flush();

        $expected = array(
            '_controller' => 'cmf_content.controller:indexAction',
            RouteObjectInterface::ROUTE_NAME => self::ROUTE_ROOT . '/templatebyclass',
        );
        $request = Request::create('/templatebyclass');
        $matches = $this->router->matchRequest($request);
        ksort($matches);

        $this->assertEquals($expected, $matches);
        $this->assertTrue($request->attributes->has(DynamicRouter::ROUTE_KEY));
        $this->assertTrue($request->attributes->has(DynamicRouter::CONTENT_TEMPLATE));
        $this->assertEquals('TestBundle:Content:index.html.twig', $request->attributes->get(DynamicRouter::CONTENT_TEMPLATE));
    }

    public function testGenerate()
    {
        $route = $this->getDm()->find(null, self::ROUTE_ROOT.'/testroute/child');

        $url = $this->router->generate($route, array('test' => 'value'));
        $this->assertEquals('/testroute/child?test=value', $url);
    }

    public function testGenerateAbsolute()
    {
        $route = $this->getDm()->find(null, self::ROUTE_ROOT.'/testroute/child');
        $url = $this->router->generate($route, array('test' => 'value'), true);
        $this->assertEquals('http://localhost/testroute/child?test=value', $url);
    }

    public function testGenerateParameters()
    {
        $route = $this->getDm()->find(null, self::ROUTE_ROOT.'/testroute');

        $url = $this->router->generate($route, array('slug' => 'gen-slug', 'test' => 'value'));
        $this->assertEquals('/testroute/gen-slug?test=value', $url);
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\InvalidParameterException
     */
    public function testGenerateParametersInvalid()
    {
        $route = $this->getDm()->find(null, self::ROUTE_ROOT.'/testroute');

        $this->router->generate($route, array('slug' => 'gen-slug', 'id' => 'nonumber'));
    }

    public function testGenerateDefaultFormat()
    {
        $route = $this->getDm()->find(null, self::ROUTE_ROOT.'/format');

        $url = $this->router->generate($route, array('id' => 37));
        $this->assertEquals('/format/37', $url);
    }

    public function testGenerateFormat()
    {
        $route = $this->getDm()->find(null, self::ROUTE_ROOT.'/format');

        $url = $this->router->generate($route, array('id' => 37, '_format' => 'json'));
        $this->assertEquals('/format/37.json', $url);
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\InvalidParameterException
     */
    public function testGenerateNoMatchingFormat()
    {
        $route = $this->getDm()->find(null, self::ROUTE_ROOT.'/format');

        $this->router->generate($route, array('id' => 37, '_format' => 'xyz'));
    }
}
