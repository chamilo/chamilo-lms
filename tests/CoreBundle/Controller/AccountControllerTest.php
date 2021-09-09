<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccountControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testEdit(): void
    {
        $client = static::createClient();

        // retrieve the admin
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $client->request('GET', '/account/edit');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Update profile', [
            'profile[firstname]' => 'admin firstname',
        ]);
        $this->assertResponseRedirects('/account/home');

        $client->request('GET', '/account/edit');
        $this->assertStringContainsString('admin firstname', $client->getResponse()->getContent());
    }
}
