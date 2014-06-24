<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Tests\WebTest;

use Symfony\Cmf\Component\Testing\Functional\BaseTestCase;

class RouteAdminTest extends BaseTestCase
{
    public function setUp()
    {
        $this->db('PHPCR')->loadFixtures(array(
            'Symfony\Cmf\Bundle\RoutingBundle\Tests\Resources\DataFixtures\Phpcr\LoadRouteData',
        ));
        $this->client = $this->createClient();
    }

    public function testRouteList()
    {
        $crawler = $this->client->request('GET', '/admin/cmf/routing/route/list');
        $res = $this->client->getResponse();
        $this->assertEquals(200, $res->getStatusCode());
        $this->assertCount(1, $crawler->filter('html:contains("route-1")'));
    }

    public function testRouteEdit()
    {
        $crawler = $this->client->request('GET', '/admin/cmf/routing/route/test/routing/route-1/edit');
        $res = $this->client->getResponse();
        $this->assertEquals(200, $res->getStatusCode());
        $this->assertCount(1, $crawler->filter('input[value="route-1"]'));
    }

    public function testRouteShow()
    {
        $this->markTestSkipped('Not implemented yet.');
    }

    public function testRouteCreate()
    {
        $crawler = $this->client->request('GET', '/admin/cmf/routing/route/create');
        $res = $this->client->getResponse();
        $this->assertEquals(200, $res->getStatusCode());

        $button = $crawler->selectButton('Create');
        $form = $button->form();
        $node = $form->getFormNode();
        $actionUrl = $node->getAttribute('action');
        $uniqId = substr(strchr($actionUrl, '='), 1);

        $form[$uniqId.'[parent]'] = '/test/routing';
        $form[$uniqId.'[name]'] = 'foo-test';

        $this->client->submit($form);
        $res = $this->client->getResponse();

        // If we have a 302 redirect, then all is well
        $this->assertEquals(302, $res->getStatusCode());
    }
}
