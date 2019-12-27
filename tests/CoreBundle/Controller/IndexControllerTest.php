<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class IndexControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient([], []);
        $client->request('GET', '/');
        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }
}
