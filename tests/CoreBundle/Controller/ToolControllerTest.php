<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ToolControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testToolUpdate(): void
    {
        $client = static::createClient();
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $client->request('GET', '/tool/update');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Updated', $client->getResponse()->getContent());
    }
}
