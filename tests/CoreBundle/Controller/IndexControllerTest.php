<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class IndexControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testToggleStudentViewAction(): void
    {
        $client = static::createClient();

        // retrieve the admin
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $client->request('GET', '/toggle_student_view');
        $this->assertResponseIsSuccessful();
    }

    public function testLogout(): void
    {
        $client = static::createClient();
        $response = $client->request('GET', '/');
        $defaultUrl = $response->getUri();

        // retrieve the admin
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $client->request('GET', '/account/home');
        $this->assertResponseIsSuccessful();

        //$client->request('GET', '/logout');
        //$this->assertResponseRedirects($defaultUrl);

        //$client->request('GET', '/main/admin/index.php');
        //$this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $client->getResponse()->getStatusCode());
    }
}
