<?php

namespace ChamiloLMS\CoreBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IndexControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/index');
    }

    public function testRootIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
    }

}
