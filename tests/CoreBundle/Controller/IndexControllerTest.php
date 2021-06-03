<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class IndexControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testUserAccess()
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = $this->getContainer()->get(UserRepository::class);

        // retrieve the test user
        $testUser = $userRepository->findByUsername('admin');

        // simulate $testUser being logged in
        $client->loginUser($testUser);

        $client->request('GET', '/account/edit');

        //$this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }
}
