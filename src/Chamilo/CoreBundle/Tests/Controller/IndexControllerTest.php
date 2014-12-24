<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class IndexControllerTest
 * @package Chamilo\CoreBundle\Tests\Controller
 */
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
