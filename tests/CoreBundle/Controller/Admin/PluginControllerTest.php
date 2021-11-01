<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller\Admin;

use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PluginControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testIndex(): void
    {
        $client = static::createClient();
        $admin = $this->getUser('admin');
        $client->loginUser($admin);

        $client->request('GET', '/plugins/');
        $this->assertResponseIsSuccessful();

        $content = (string) $client->getResponse()->getContent();

        $this->assertStringContainsString('Plugin', $content);
    }
}
