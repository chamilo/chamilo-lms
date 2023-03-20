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

        $admin = $this->getUser('admin');

        $client->loginUser($admin);

        $client->request('GET', '/account/edit');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Update profile', [
            'profile[firstname]' => 'admin firstname',
            'profile[email]' => 'test@test.com',
            //'profile[mail_notify_invitation]' => 1,
        ]);
        $this->assertResponseRedirects('/account/home');

        $client->request('GET', '/account/edit');
        $this->assertStringContainsString('admin firstname', $client->getResponse()->getContent());
    }

    public function testEditNoLogin(): void
    {
        $client = static::createClient();

        $client->request('GET', '/account/edit');
        $this->assertResponseStatusCodeSame(302);
    }
}
